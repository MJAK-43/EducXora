<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learning\Enums\GroupStatus;
use App\Domain\Learning\Models\Group;
use App\Domain\Scheduling\Enums\CourseSessionStatus;
use App\Domain\Scheduling\Models\CourseSession;
use App\Enums\MembershipStatus;
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

final class CourseSessionManagementTest extends TestCase
{
    use BuildsPhaseTwoTenancy;
    use RefreshDatabase;

    public function test_staff_can_create_a_session_in_organization_timezone_with_audit(): void
    {
        [$organization, $staff, $membership] = $this->tenantWithUser('Staff');
        $teacher = $this->teacherFor($organization);
        $group = $this->groupFor($organization, $membership, $teacher);

        $this->actingAs($staff)->withSession($this->tenantSession($organization))
            ->post('/schedule', $this->payload($group, $teacher))->assertRedirect();

        $this->assertDatabaseHas('course_sessions', [
            'organization_id' => $organization->getKey(), 'group_id' => $group->getKey(),
            'teacher_membership_id' => $teacher->getKey(), 'room' => 'Salle A', 'status' => 'scheduled',
        ]);
        $this->setTenant($organization, $membership);
        $session = CourseSession::query()->sole();
        self::assertSame('2026-09-14T08:00:00+00:00', $session->starts_at->toIso8601String());
        $this->assertDatabaseHas('audit_logs', ['action' => 'lesson.created', 'resource_id' => (string) $session->getKey()]);
    }

    public function test_invalid_interval_and_archived_group_are_rejected(): void
    {
        [$organization, $staff, $membership] = $this->tenantWithUser('Staff');
        $teacher = $this->teacherFor($organization);
        $group = $this->groupFor($organization, $membership, $teacher);
        $session = $this->tenantSession($organization);

        $this->actingAs($staff)->withSession($session)->post('/schedule', $this->payload($group, $teacher, ['end_time' => '08:00']))->assertSessionHasErrors('end_time');
        $this->setTenant($organization, $membership);
        $group->update(['status' => GroupStatus::Archived, 'archived_at' => now()]);
        $this->actingAs($staff)->withSession($session)->post('/schedule', $this->payload($group, $teacher))->assertSessionHasErrors('group_uuid');
    }

    public function test_adjacent_group_sessions_are_allowed_but_overlaps_are_rejected(): void
    {
        [$organization, $staff, $membership] = $this->tenantWithUser('Staff');
        $teacher = $this->teacherFor($organization);
        $otherTeacher = $this->teacherFor($organization);
        $group = $this->groupFor($organization, $membership, $teacher);
        $httpSession = $this->tenantSession($organization);

        $this->actingAs($staff)->withSession($httpSession)->post('/schedule', $this->payload($group, $teacher, ['start_time' => '09:00', 'end_time' => '10:00']))->assertRedirect();
        $this->actingAs($staff)->withSession($httpSession)->post('/schedule', $this->payload($group, $otherTeacher, ['start_time' => '10:00', 'end_time' => '11:00']))->assertRedirect();
        $this->actingAs($staff)->withSession($httpSession)->post('/schedule', $this->payload($group, $otherTeacher, ['start_time' => '09:30', 'end_time' => '10:30']))->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('course_sessions', 2);
    }

    public function test_teacher_and_case_insensitive_room_overlaps_are_rejected(): void
    {
        [$organization, $staff, $membership] = $this->tenantWithUser('Staff');
        $teacher = $this->teacherFor($organization);
        $otherTeacher = $this->teacherFor($organization);
        $group = $this->groupFor($organization, $membership, $teacher);
        $otherGroup = $this->groupFor($organization, $membership, $otherTeacher, ['name' => 'Autre groupe']);
        $session = $this->tenantSession($organization);

        $this->actingAs($staff)->withSession($session)->post('/schedule', $this->payload($group, $teacher))->assertRedirect();
        $this->actingAs($staff)->withSession($session)->post('/schedule', $this->payload($otherGroup, $teacher, ['room' => 'Salle B']))->assertSessionHasErrors('teacher_membership_uuid');
        $this->actingAs($staff)->withSession($session)->post('/schedule', $this->payload($otherGroup, $otherTeacher, ['room' => 'salle a']))->assertSessionHasErrors('room');
    }

    public function test_cancellation_is_audited_and_releases_the_slot(): void
    {
        [$organization, $staff, $membership] = $this->tenantWithUser('Staff');
        $teacher = $this->teacherFor($organization);
        $group = $this->groupFor($organization, $membership, $teacher);
        $httpSession = $this->tenantSession($organization);
        $this->actingAs($staff)->withSession($httpSession)->post('/schedule', $this->payload($group, $teacher))->assertRedirect();
        $this->setTenant($organization, $membership);
        $courseSession = CourseSession::query()->sole();

        $this->actingAs($staff)->withSession($httpSession)->patch('/schedule/'.$courseSession->uuid.'/cancel')->assertRedirect();
        $this->actingAs($staff)->withSession($httpSession)->post('/schedule', $this->payload($group, $teacher))->assertRedirect();

        $this->assertDatabaseHas('course_sessions', ['id' => $courseSession->getKey(), 'status' => CourseSessionStatus::Cancelled->value]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'lesson.cancelled', 'resource_id' => (string) $courseSession->getKey()]);
    }

    public function test_session_can_be_updated_but_cancelled_session_is_immutable(): void
    {
        [$organization, $staff, $membership] = $this->tenantWithUser('Staff');
        $teacher = $this->teacherFor($organization);
        $group = $this->groupFor($organization, $membership, $teacher);
        $httpSession = $this->tenantSession($organization);
        $this->actingAs($staff)->withSession($httpSession)->post('/schedule', $this->payload($group, $teacher))->assertRedirect();
        $this->setTenant($organization, $membership);
        $courseSession = CourseSession::query()->sole();

        $this->actingAs($staff)->withSession($httpSession)->patch('/schedule/'.$courseSession->uuid, $this->payload($group, $teacher, ['room' => 'Salle C', 'start_time' => '11:00', 'end_time' => '12:00']))->assertRedirect();
        $this->actingAs($staff)->withSession($httpSession)->patch('/schedule/'.$courseSession->uuid.'/cancel')->assertRedirect();
        $this->actingAs($staff)->withSession($httpSession)->patch('/schedule/'.$courseSession->uuid, $this->payload($group, $teacher))->assertSessionHasErrors('session');
        $this->assertDatabaseHas('audit_logs', ['action' => 'lesson.updated', 'resource_id' => (string) $courseSession->getKey()]);
    }

    public function test_week_view_filters_by_group_and_exposes_navigation(): void
    {
        [$organization, $staff, $membership] = $this->tenantWithUser('Staff');
        $teacher = $this->teacherFor($organization);
        $group = $this->groupFor($organization, $membership, $teacher);
        $otherGroup = $this->groupFor($organization, $membership, $teacher, ['name' => 'Groupe soir']);
        $this->setTenant($organization, $membership);
        $this->sessionFor($group, $teacher, '2026-09-14 08:00:00+00', '2026-09-14 09:00:00+00');
        $this->sessionFor($otherGroup, $teacher, '2026-09-16 08:00:00+00', '2026-09-16 09:00:00+00');

        $this->actingAs($staff)->withSession($this->tenantSession($organization))->get('/schedule?week=2026-09-14&group='.$group->uuid)
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Schedule/Index')->has('sessions', 1)
            ->where('sessions.0.group_name', $group->name)->where('week.previous', '2026-09-07')->where('week.next', '2026-09-21'));
    }

    public function test_teacher_only_sees_own_sessions_and_cannot_manage_them(): void
    {
        [$organization, $staff, $membership] = $this->tenantWithUser('Staff');
        $teacher = $this->teacherFor($organization);
        $otherTeacher = $this->teacherFor($organization);
        $group = $this->groupFor($organization, $membership, $teacher);
        $otherGroup = $this->groupFor($organization, $membership, $otherTeacher, ['name' => 'Autre']);
        $this->setTenant($organization, $membership);
        $own = $this->sessionFor($group, $teacher, '2026-09-14 08:00:00+00', '2026-09-14 09:00:00+00');
        $foreign = $this->sessionFor($otherGroup, $otherTeacher, '2026-09-14 10:00:00+00', '2026-09-14 11:00:00+00');

        $teacherUser = $teacher->user;
        $session = $this->tenantSession($organization);
        $this->actingAs($teacherUser)->withSession($session)->get('/schedule?week=2026-09-14')
            ->assertInertia(fn (Assert $page) => $page->has('sessions', 1)->where('sessions.0.uuid', $own->uuid));
        $this->actingAs($teacherUser)->withSession($session)->get('/schedule/'.$own->uuid.'/edit')->assertForbidden();
        $this->actingAs($teacherUser)->withSession($session)->patch('/schedule/'.$own->uuid.'/cancel')->assertForbidden();
        $this->actingAs($teacherUser)->withSession($session)->get('/schedule/'.$foreign->uuid.'/edit')->assertForbidden();
    }

    public function test_tenant_a_cannot_edit_cancel_or_reference_tenant_b_resources(): void
    {
        [$organizationA, $staffA] = $this->tenantWithUser('Staff');
        [$organizationB, , $membershipB] = $this->tenantWithUser('Staff');
        $teacherB = $this->teacherFor($organizationB);
        $groupB = $this->groupFor($organizationB, $membershipB, $teacherB);
        $this->setTenant($organizationB, $membershipB);
        $sessionB = $this->sessionFor($groupB, $teacherB, '2026-09-14 08:00:00+00', '2026-09-14 09:00:00+00');
        $session = $this->tenantSession($organizationA);

        $this->actingAs($staffA)->withSession($session)->get('/schedule/'.$sessionB->uuid.'/edit')->assertNotFound();
        $this->actingAs($staffA)->withSession($session)->patch('/schedule/'.$sessionB->uuid, $this->payload($groupB, $teacherB))->assertNotFound();
        $this->actingAs($staffA)->withSession($session)->patch('/schedule/'.$sessionB->uuid.'/cancel')->assertNotFound();
        $this->actingAs($staffA)->withSession($session)->post('/schedule', $this->payload($groupB, $teacherB))->assertNotFound();
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(Group $group, OrganizationMembership $teacher, array $overrides = []): array
    {
        return [...['group_uuid' => $group->uuid, 'teacher_membership_uuid' => $teacher->uuid, 'room' => 'Salle A', 'date' => '2026-09-14', 'start_time' => '09:00', 'end_time' => '10:30'], ...$overrides];
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

        return Group::factory()->create([...['name' => 'Groupe A1', 'level' => LearnerLevel::A1, 'teacher_membership_id' => $teacher->getKey()], ...$overrides]);
    }

    private function sessionFor(Group $group, OrganizationMembership $teacher, string $startsAt, string $endsAt): CourseSession
    {
        return CourseSession::factory()->create(['group_id' => $group->getKey(), 'teacher_membership_id' => $teacher->getKey(), 'starts_at' => $startsAt, 'ends_at' => $endsAt, 'room' => null]);
    }

    private function setTenant(Organization $organization, ?OrganizationMembership $membership): void
    {
        app(TenantContext::class)->set($organization, $membership);
    }
}
