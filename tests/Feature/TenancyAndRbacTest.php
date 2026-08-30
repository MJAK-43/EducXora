<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrganizationStatus;
use App\Models\UserInvitation;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use Tests\Concerns\BuildsPhaseTwoTenancy;
use Tests\TestCase;

final class TenancyAndRbacTest extends TestCase
{
    use BuildsPhaseTwoTenancy;
    use RefreshDatabase;

    public function test_tenant_scoped_models_fail_closed_and_never_leak_between_organizations(): void
    {
        [$organizationA, $userA] = $this->tenantWithUser();
        [$organizationB] = $this->tenantWithUser();
        $context = app(TenantContext::class);
        $context->set($organizationA);
        UserInvitation::query()->create([
            'email' => 'invitee@example.test', 'token_hash' => hash('sha256', 'secret'),
            'role_id' => $organizationA->roles()->where('name', 'Staff')->value('id'),
            'expires_at' => now()->addDay(), 'invited_by' => $userA->getKey(),
        ]);
        self::assertSame(1, UserInvitation::query()->count());
        $context->set($organizationB);
        self::assertSame(0, UserInvitation::query()->count());
        $context->clear();
        $this->expectException(LogicException::class);
        UserInvitation::query()->count();
    }

    public function test_user_cannot_switch_to_an_unrelated_organization(): void
    {
        [$organizationA, $userA] = $this->tenantWithUser();
        [$organizationB] = $this->tenantWithUser();
        $this->actingAs($userA)->withSession($this->tenantSession($organizationA))
            ->post('/organizations/select', ['organization_uuid' => $organizationB->uuid])->assertForbidden();
    }

    public function test_manager_cannot_manage_roles_but_organization_admin_can(): void
    {
        [$managerOrganization, $manager] = $this->tenantWithUser('Manager');
        $this->actingAs($manager)->withSession($this->tenantSession($managerOrganization))
            ->post('/organization/roles', ['name' => 'Custom', 'permissions' => ['users.view']])->assertForbidden();

        [$adminOrganization, $admin] = $this->tenantWithUser();
        $this->actingAs($admin)->withSession($this->tenantSession($adminOrganization))
            ->post('/organization/roles', ['name' => 'Custom', 'permissions' => ['users.view']])->assertRedirect();
        $this->assertDatabaseHas('roles', ['organization_id' => $adminOrganization->getKey(), 'name' => 'Custom']);
    }

    public function test_teacher_cannot_open_user_directory_and_navigation_capabilities_are_restricted(): void
    {
        [$organization, $teacher] = $this->tenantWithUser('Teacher/Trainer');
        $client = $this->actingAs($teacher)->withSession($this->tenantSession($organization));

        $client->get('/organization/users')->assertForbidden();
        $client->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('auth.canViewUsers', false)
            ->where('auth.canViewQuestionBank', false)
            ->where('auth.canViewGroups', true)
            ->where('auth.canViewSchedule', true)
            ->where('auth.canViewAttendance', true));
    }

    public function test_suspended_organization_is_blocked(): void
    {
        [$organization, $user] = $this->tenantWithUser();
        $organization->update(['status' => OrganizationStatus::Suspended]);
        $this->actingAs($user)->withSession($this->tenantSession($organization))->get('/dashboard')->assertForbidden();
    }

    public function test_role_routes_reject_cross_tenant_resource_binding(): void
    {
        [$organizationA, $adminA] = $this->tenantWithUser();
        [$organizationB] = $this->tenantWithUser();
        $foreignRole = $organizationB->roles()->where('name', 'Staff')->sole();
        $this->actingAs($adminA)->withSession($this->tenantSession($organizationA))
            ->delete('/organization/roles/'.$foreignRole->uuid)->assertForbidden();
    }
}
