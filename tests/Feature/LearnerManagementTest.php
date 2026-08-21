<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Learner\Enums\LearnerStatus;
use App\Domain\Learner\Models\Learner;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsPhaseTwoTenancy;
use Tests\TestCase;

final class LearnerManagementTest extends TestCase
{
    use BuildsPhaseTwoTenancy;
    use RefreshDatabase;

    public function test_admin_can_create_a_tenant_scoped_learner_with_normalized_phone_and_audit(): void
    {
        [$organization, $admin] = $this->tenantWithUser();

        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->post('/learners', $this->validPayload(['phone' => '6 99 12 34 56']))
            ->assertRedirect();

        $this->assertDatabaseHas('learners', [
            'organization_id' => $organization->getKey(),
            'phone' => '+237699123456',
            'status' => LearnerStatus::Active->value,
            'created_by' => $admin->getKey(),
        ]);
        $this->assertDatabaseHas('audit_logs', ['organization_id' => $organization->getKey(), 'action' => 'learner.created']);
    }

    public function test_create_rejects_tenant_injection_and_invalid_cameroon_phone(): void
    {
        [$organization, $admin] = $this->tenantWithUser();
        [$foreign] = $this->tenantWithUser();

        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->post('/learners', $this->validPayload(['organization_id' => $foreign->getKey(), 'phone' => '123']))
            ->assertSessionHasErrors(['organization_id', 'phone']);
    }

    public function test_index_is_paginated_searchable_and_defaults_to_active_learners(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $this->setTenant($organization, $membership);
        Learner::factory()->count(21)->create(['created_by' => $admin->getKey()]);
        Learner::factory()->create(['first_name' => 'Aïcha', 'last_name' => 'Ngono', 'phone' => '+237677112233', 'email' => 'aicha@example.test', 'created_by' => $admin->getKey()]);
        Learner::factory()->create(['status' => LearnerStatus::Archived, 'archived_at' => now(), 'created_by' => $admin->getKey()]);

        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->get('/learners?search=Aïcha')
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Learners/Index')->has('learners.data', 1)
            ->where('learners.data.0.phone', '+237677112233'));

        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->get('/learners')->assertInertia(fn (Assert $page) => $page
            ->where('learners.per_page', 20)->where('learners.total', 22));
    }

    public function test_admin_can_view_update_archive_and_restore_with_audit_events(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $learner = $this->learnerFor($organization, $membership, $admin);

        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->get('/learners/'.$learner->uuid)->assertOk();
        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->patch('/learners/'.$learner->uuid, $this->validPayload(['first_name' => 'Mireille']))->assertRedirect();
        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->patch('/learners/'.$learner->uuid.'/archive')->assertRedirect();
        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->patch('/learners/'.$learner->uuid.'/restore')->assertRedirect();

        $this->assertDatabaseHas('learners', ['id' => $learner->getKey(), 'first_name' => 'Mireille', 'status' => 'active', 'archived_at' => null]);
        self::assertEqualsCanonicalizing(
            ['learner.updated', 'learner.archived', 'learner.restored'],
            AuditLog::query()->where('resource_type', Learner::class)->where('resource_id', (string) $learner->getKey())->pluck('action')->all(),
        );
    }

    public function test_archived_filter_excludes_active_records(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $this->setTenant($organization, $membership);
        Learner::factory()->create(['status' => LearnerStatus::Active, 'created_by' => $admin->getKey()]);
        Learner::factory()->create(['status' => LearnerStatus::Archived, 'archived_at' => now(), 'created_by' => $admin->getKey()]);

        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->get('/learners?status=archived')->assertInertia(fn (Assert $page) => $page
            ->has('learners.data', 1)->where('learners.data.0.status', 'archived'));
    }

    public function test_staff_can_manage_but_cannot_restore_and_teacher_has_no_global_access(): void
    {
        [$organization, $staff, $membership] = $this->tenantWithUser('Staff');
        $learner = $this->learnerFor($organization, $membership, $staff);
        $this->actingAs($staff)->withSession($this->tenantSession($organization))->get('/learners')->assertOk();
        $this->actingAs($staff)->withSession($this->tenantSession($organization))->patch('/learners/'.$learner->uuid.'/archive')->assertRedirect();
        $this->actingAs($staff)->withSession($this->tenantSession($organization))->patch('/learners/'.$learner->uuid.'/restore')->assertForbidden();

        [$teacherOrganization, $teacher] = $this->tenantWithUser('Teacher/Trainer');
        $this->actingAs($teacher)->withSession($this->tenantSession($teacherOrganization))->get('/learners')->assertForbidden();
        $this->actingAs($teacher)->withSession($this->tenantSession($teacherOrganization))->post('/learners', $this->validPayload())->assertForbidden();
    }

    public function test_tenant_a_cannot_view_update_archive_restore_download_or_export_tenant_b_learner(): void
    {
        [$organizationA, $adminA] = $this->tenantWithUser();
        [$organizationB, $adminB, $membershipB] = $this->tenantWithUser();
        Storage::fake('local');
        $learnerB = $this->learnerFor($organizationB, $membershipB, $adminB, ['first_name' => 'SecretB', 'photo_path' => 'learners/b/photo.png']);
        Storage::disk('local')->put('learners/b/photo.png', 'private');

        $session = $this->tenantSession($organizationA);
        $this->actingAs($adminA)->withSession($session)->get('/learners/'.$learnerB->uuid)->assertNotFound();
        $this->actingAs($adminA)->withSession($session)->patch('/learners/'.$learnerB->uuid, $this->validPayload())->assertNotFound();
        $this->actingAs($adminA)->withSession($session)->patch('/learners/'.$learnerB->uuid.'/archive')->assertNotFound();
        $this->actingAs($adminA)->withSession($session)->patch('/learners/'.$learnerB->uuid.'/restore')->assertNotFound();
        $this->actingAs($adminA)->withSession($session)->get('/learners/'.$learnerB->uuid.'/photo')->assertNotFound();
        $this->actingAs($adminA)->withSession($session)->get('/learners/export')->assertOk()->assertDontSee('SecretB');
    }

    public function test_private_photo_is_validated_stored_and_replaced(): void
    {
        Storage::fake('local');
        [$organization, $admin] = $this->tenantWithUser();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        self::assertIsString($png);

        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->post('/learners', $this->validPayload(['photo' => UploadedFile::fake()->createWithContent('photo.png', $png)]))
            ->assertRedirect();
        $this->setTenant($organization, null);
        $learner = Learner::query()->sole();
        self::assertNotNull($learner->photo_path);
        Storage::disk('local')->assertExists($learner->photo_path);
        $oldPath = $learner->photo_path;
        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->post('/learners/'.$learner->uuid, $this->validPayload([
                '_method' => 'patch',
                'photo' => UploadedFile::fake()->createWithContent('replacement.png', $png),
            ]))
            ->assertRedirect();
        $this->setTenant($organization, null);
        $learner->refresh();
        self::assertNotSame($oldPath, $learner->photo_path);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($learner->photo_path);
        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->get('/learners/'.$learner->uuid.'/photo')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [...[
            'first_name' => 'Alice', 'last_name' => 'Mballa', 'birth_date' => '2002-04-15',
            'phone' => '+237699123456', 'email' => 'alice@example.test', 'language' => 'de',
            'initial_level' => 'A1',
        ], ...$overrides];
    }

    /** @param array<string, mixed> $overrides */
    private function learnerFor(Organization $organization, ?OrganizationMembership $membership, User $creator, array $overrides = []): Learner
    {
        $this->setTenant($organization, $membership);

        return Learner::factory()->create([...$overrides, 'created_by' => $creator->getKey()]);
    }

    private function setTenant(Organization $organization, ?OrganizationMembership $membership): void
    {
        app(TenantContext::class)->set($organization, $membership);
    }
}
