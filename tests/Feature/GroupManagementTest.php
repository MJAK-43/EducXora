<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learner\Models\Learner;
use App\Domain\Learning\Enums\GroupStatus;
use App\Domain\Learning\Models\Group;
use App\Domain\Learning\Models\GroupLearnerAssignment;
use App\Enums\MembershipStatus;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsPhaseTwoTenancy;
use Tests\TestCase;

final class GroupManagementTest extends TestCase
{
    use BuildsPhaseTwoTenancy;
    use RefreshDatabase;

    public function test_admin_can_create_a_group_with_an_eligible_teacher_and_audit(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $teacher = $this->teacherFor($organization);

        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->post('/groups', $this->payload($teacher))->assertRedirect();

        $this->assertDatabaseHas('groups', [
            'organization_id' => $organization->getKey(), 'name' => 'Allemand A1 matin',
            'teacher_membership_id' => $teacher->getKey(), 'capacity' => 20, 'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_logs', ['organization_id' => $organization->getKey(), 'action' => 'group.created']);
    }

    public function test_create_rejects_tenant_injection_and_foreign_or_non_teacher_membership(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        [$foreignOrganization, , $foreignMembership] = $this->tenantWithUser();

        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->post('/groups', $this->payload($foreignMembership, ['organization_id' => $foreignOrganization->getKey()]))
            ->assertSessionHasErrors('organization_id');
        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->post('/groups', $this->payload($foreignMembership))->assertSessionHasErrors('teacher_membership_uuid');
        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->post('/groups', $this->payload($membership))->assertSessionHasErrors('teacher_membership_uuid');

        $inactiveTeacher = $this->teacherFor($organization);
        $inactiveTeacher->update(['status' => MembershipStatus::Inactive]);
        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->post('/groups', $this->payload($inactiveTeacher))->assertSessionHasErrors('teacher_membership_uuid');
    }

    public function test_index_is_paginated_searchable_and_filterable(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $teacher = $this->teacherFor($organization);
        $this->setTenant($organization, $membership);
        Group::factory()->count(21)->create(['teacher_membership_id' => $teacher->getKey()]);
        Group::factory()->create(['name' => 'Groupe recherché', 'level' => LearnerLevel::B2, 'teacher_membership_id' => $teacher->getKey()]);
        Group::factory()->create(['status' => GroupStatus::Archived, 'archived_at' => now(), 'teacher_membership_id' => $teacher->getKey()]);

        $this->actingAs($admin)->withSession($this->tenantSession($organization))->get('/groups?search=recherché&level=B2')
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Groups/Index')->has('groups.data', 1)->where('groups.data.0.name', 'Groupe recherché'));
        $this->actingAs($admin)->withSession($this->tenantSession($organization))->get('/groups')
            ->assertInertia(fn (Assert $page) => $page->where('groups.per_page', 20)->where('groups.total', 22));
    }

    public function test_admin_can_update_change_teacher_archive_and_restore_with_audit(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $teacher = $this->teacherFor($organization);
        $otherTeacher = $this->teacherFor($organization);
        $group = $this->groupFor($organization, $membership, $teacher);

        $session = $this->tenantSession($organization);
        $this->actingAs($admin)->withSession($session)->patch('/groups/'.$group->uuid, $this->payload($otherTeacher, ['name' => 'Nouveau nom']))->assertRedirect();
        $this->actingAs($admin)->withSession($session)->patch('/groups/'.$group->uuid.'/archive')->assertRedirect();
        $this->actingAs($admin)->withSession($session)->patch('/groups/'.$group->uuid.'/restore')->assertRedirect();

        $this->assertDatabaseHas('groups', ['id' => $group->getKey(), 'name' => 'Nouveau nom', 'teacher_membership_id' => $otherTeacher->getKey(), 'status' => 'active']);
        self::assertEqualsCanonicalizing(
            ['group.updated', 'group.teacher_changed', 'group.archived', 'group.restored'],
            AuditLog::query()->where('resource_type', Group::class)->where('resource_id', (string) $group->getKey())->pluck('action')->all(),
        );
    }

    public function test_capacity_cannot_be_reduced_below_current_membership(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $teacher = $this->teacherFor($organization);
        $group = $this->groupFor($organization, $membership, $teacher, ['capacity' => 3]);
        $this->setTenant($organization, $membership);
        $learners = Learner::factory()->count(2)->create(['initial_level' => $group->level]);
        foreach ($learners as $learner) {
            GroupLearnerAssignment::query()->create(['group_id' => $group->getKey(), 'learner_id' => $learner->getKey(), 'assigned_at' => now()]);
        }

        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->patch('/groups/'.$group->uuid, $this->payload($teacher, ['capacity' => 1]))
            ->assertSessionHasErrors(['capacity']);
    }

    public function test_group_level_cannot_be_changed_while_active_learners_are_assigned(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $teacher = $this->teacherFor($organization);
        $group = $this->groupFor($organization, $membership, $teacher);
        $this->setTenant($organization, $membership);
        $learner = Learner::factory()->create(['initial_level' => LearnerLevel::A1]);
        GroupLearnerAssignment::query()->create([
            'group_id' => $group->getKey(),
            'learner_id' => $learner->getKey(),
            'assigned_at' => now(),
        ]);

        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->patch('/groups/'.$group->uuid, $this->payload($teacher, ['level' => LearnerLevel::B2->value]))
            ->assertSessionHasErrors(['level']);

        $this->assertDatabaseHas('groups', ['id' => $group->getKey(), 'level' => LearnerLevel::A1->value]);
    }

    public function test_learner_can_be_attached_and_detached_while_history_and_audit_are_preserved(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $teacher = $this->teacherFor($organization);
        $group = $this->groupFor($organization, $membership, $teacher);
        $this->setTenant($organization, $membership);
        $learner = Learner::factory()->create(['initial_level' => LearnerLevel::A1]);

        $session = $this->tenantSession($organization);
        $this->actingAs($admin)->withSession($session)->post('/groups/'.$group->uuid.'/learners', ['learner_uuid' => $learner->uuid])->assertRedirect();
        $this->actingAs($admin)->withSession($session)->delete('/groups/'.$group->uuid.'/learners/'.$learner->uuid)->assertRedirect();

        $this->setTenant($organization, $membership);
        $assignment = GroupLearnerAssignment::query()->sole();
        self::assertNotNull($assignment->detached_at);
        self::assertEqualsCanonicalizing(['group.learner_attached', 'group.learner_detached'], AuditLog::query()->whereIn('action', ['group.learner_attached', 'group.learner_detached'])->pluck('action')->all());
    }

    public function test_attach_rejects_incompatible_archived_duplicate_and_capacity_overflow(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $teacher = $this->teacherFor($organization);
        $group = $this->groupFor($organization, $membership, $teacher, ['capacity' => 1]);
        $this->setTenant($organization, $membership);
        $wrongLevel = Learner::factory()->create(['initial_level' => LearnerLevel::B2]);
        $archived = Learner::factory()->create(['initial_level' => LearnerLevel::A1, 'status' => 'archived', 'archived_at' => now()]);
        $first = Learner::factory()->create(['initial_level' => LearnerLevel::A1]);
        $second = Learner::factory()->create(['initial_level' => LearnerLevel::A1]);
        $session = $this->tenantSession($organization);

        $this->actingAs($admin)->withSession($session)->post('/groups/'.$group->uuid.'/learners', ['learner_uuid' => $wrongLevel->uuid])->assertSessionHasErrors('learner_uuid');
        $this->actingAs($admin)->withSession($session)->post('/groups/'.$group->uuid.'/learners', ['learner_uuid' => $archived->uuid])->assertSessionHasErrors('learner_uuid');
        $this->actingAs($admin)->withSession($session)->post('/groups/'.$group->uuid.'/learners', ['learner_uuid' => $first->uuid])->assertRedirect();
        $this->actingAs($admin)->withSession($session)->post('/groups/'.$group->uuid.'/learners', ['learner_uuid' => $first->uuid])->assertSessionHasErrors('learner_uuid');
        $this->actingAs($admin)->withSession($session)->post('/groups/'.$group->uuid.'/learners', ['learner_uuid' => $second->uuid])->assertSessionHasErrors('learner_uuid');

        $group->update(['status' => GroupStatus::Archived, 'archived_at' => now()]);
        $this->actingAs($admin)->withSession($session)->delete('/groups/'.$group->uuid.'/learners/'.$first->uuid)->assertSessionHasErrors('group');
    }

    public function test_staff_manages_groups_while_teacher_only_sees_own_group(): void
    {
        [$organization, $staff, $staffMembership] = $this->tenantWithUser('Staff');
        $teacher = $this->teacherFor($organization);
        $otherTeacher = $this->teacherFor($organization);
        $ownGroup = $this->groupFor($organization, $staffMembership, $teacher);
        $otherGroup = $this->groupFor($organization, $staffMembership, $otherTeacher);

        $this->actingAs($staff)->withSession($this->tenantSession($organization))->get('/groups')->assertOk();
        $teacherUser = $teacher->user;
        $this->actingAs($teacherUser)->withSession($this->tenantSession($organization))->get('/groups')
            ->assertInertia(fn (Assert $page) => $page->has('groups.data', 1)->where('groups.data.0.uuid', $ownGroup->uuid));
        $this->actingAs($teacherUser)->withSession($this->tenantSession($organization))->get('/groups/'.$ownGroup->uuid)->assertOk();
        $this->actingAs($teacherUser)->withSession($this->tenantSession($organization))->get('/groups/'.$otherGroup->uuid)->assertForbidden();
        $this->actingAs($teacherUser)->withSession($this->tenantSession($organization))->patch('/groups/'.$ownGroup->uuid, $this->payload($teacher))->assertForbidden();
    }

    public function test_tenant_a_cannot_view_update_archive_restore_attach_or_detach_tenant_b_group(): void
    {
        [$organizationA, $adminA] = $this->tenantWithUser();
        [$organizationB, $adminB, $membershipB] = $this->tenantWithUser();
        $teacherB = $this->teacherFor($organizationB);
        $groupB = $this->groupFor($organizationB, $membershipB, $teacherB);
        $this->setTenant($organizationB, $membershipB);
        $learnerB = Learner::factory()->create(['initial_level' => LearnerLevel::A1]);
        GroupLearnerAssignment::query()->create(['group_id' => $groupB->getKey(), 'learner_id' => $learnerB->getKey(), 'assigned_at' => now()]);
        $session = $this->tenantSession($organizationA);

        $this->actingAs($adminA)->withSession($session)->get('/groups/'.$groupB->uuid)->assertNotFound();
        $this->actingAs($adminA)->withSession($session)->patch('/groups/'.$groupB->uuid, $this->payload($teacherB))->assertNotFound();
        $this->actingAs($adminA)->withSession($session)->patch('/groups/'.$groupB->uuid.'/archive')->assertNotFound();
        $this->actingAs($adminA)->withSession($session)->patch('/groups/'.$groupB->uuid.'/restore')->assertNotFound();
        $this->actingAs($adminA)->withSession($session)->post('/groups/'.$groupB->uuid.'/learners', ['learner_uuid' => $learnerB->uuid])->assertNotFound();
        $this->actingAs($adminA)->withSession($session)->delete('/groups/'.$groupB->uuid.'/learners/'.$learnerB->uuid)->assertNotFound();
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(OrganizationMembership $teacher, array $overrides = []): array
    {
        return [...['name' => 'Allemand A1 matin', 'language' => 'de', 'level' => 'A1', 'capacity' => 20, 'teacher_membership_uuid' => $teacher->uuid], ...$overrides];
    }

    private function teacherFor(Organization $organization): OrganizationMembership
    {
        $user = User::factory()->create();
        $membership = OrganizationMembership::query()->create(['organization_id' => $organization->getKey(), 'user_id' => $user->getKey(), 'status' => MembershipStatus::Active, 'joined_at' => now()]);
        $role = Role::query()->where('organization_id', $organization->getKey())->where('name', 'Teacher/Trainer')->sole();
        app(MembershipAuthorizer::class)->syncRoles($membership, [$role->getKey()]);

        return $membership->load('user');
    }

    /** @param array<string, mixed> $overrides */
    private function groupFor(Organization $organization, OrganizationMembership $membership, OrganizationMembership $teacher, array $overrides = []): Group
    {
        $this->setTenant($organization, $membership);

        return Group::factory()->create([...['name' => 'Allemand A1 matin', 'level' => LearnerLevel::A1, 'teacher_membership_id' => $teacher->getKey()], ...$overrides]);
    }

    private function setTenant(Organization $organization, ?OrganizationMembership $membership): void
    {
        app(TenantContext::class)->set($organization, $membership);
    }
}
