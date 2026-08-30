<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsPhaseTwoTenancy;
use Tests\TestCase;

final class UiExperienceTest extends TestCase
{
    use BuildsPhaseTwoTenancy;
    use RefreshDatabase;

    public function test_dashboard_exposes_real_empty_operational_state_for_the_current_tenant(): void
    {
        [$organization, $admin] = $this->tenantWithUser();

        $this->actingAs($admin)->withSession($this->tenantSession($organization))
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('organization.uuid', $organization->uuid)
                ->where('overview.metrics.activeLearners', 0)
                ->where('overview.metrics.activeGroups', 0)
                ->where('overview.metrics.sessionsToday', 0)
                ->where('overview.metrics.attendanceRate', null)
                ->has('overview.todaySessions', 0)
                ->where('overview.can.viewAudit', true));
    }

    public function test_audit_screen_is_permission_protected_and_tenant_isolated(): void
    {
        [$organizationA, $adminA] = $this->tenantWithUser();
        [$organizationB, $adminB] = $this->tenantWithUser();
        [, $staff] = $this->tenantWithUser('Staff');

        AuditLog::query()->create([
            'uuid' => (string) Str::uuid7(),
            'organization_id' => $organizationA->getKey(),
            'actor_id' => $adminA->getKey(),
            'action' => 'learner.created',
            'resource_type' => 'Learner',
            'resource_id' => '11',
            'metadata' => [],
            'created_at' => now(),
        ]);
        AuditLog::query()->create([
            'uuid' => (string) Str::uuid7(),
            'organization_id' => $organizationB->getKey(),
            'actor_id' => $adminB->getKey(),
            'action' => 'foreign.action',
            'resource_type' => 'Learner',
            'resource_id' => '12',
            'metadata' => [],
            'created_at' => now(),
        ]);

        $this->actingAs($adminA)->withSession($this->tenantSession($organizationA))
            ->get('/organization/audit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Organization/Audit/Index')
                ->has('logs.data', 1)
                ->where('logs.data.0.action', 'learner.created'));

        $staffOrganization = $staff->organizations()->sole();
        $this->actingAs($staff)->withSession($this->tenantSession($staffOrganization))
            ->get('/organization/audit')->assertForbidden();
    }

    public function test_dashboard_does_not_serialize_unauthorized_operational_datasets(): void
    {
        [$organization, $accountant] = $this->tenantWithUser('Accountant');

        $this->actingAs($accountant)->withSession($this->tenantSession($organization))
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('overview.can.viewLearners', true)
                ->where('overview.can.viewGroups', false)
                ->where('overview.can.viewSchedule', false)
                ->where('overview.can.viewAttendance', false)
                ->where('overview.metrics.activeGroups', null)
                ->has('overview.groups', 0)
                ->has('overview.todaySessions', 0)
                ->has('overview.recentActivity', 0));
    }
}
