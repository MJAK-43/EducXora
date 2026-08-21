<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Attendance\Models\AttendanceCorrection;
use App\Domain\Attendance\Models\AttendanceSheet;
use App\Domain\Attendance\Models\LearnerAttendance;
use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learner\Models\Learner;
use App\Domain\Learning\Models\Group;
use App\Domain\Learning\Models\GroupLearnerAssignment;
use App\Domain\Scheduling\Enums\CourseSessionStatus;
use App\Domain\Scheduling\Models\CourseSession;
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

final class AttendanceManagementTest extends TestCase
{
    use BuildsPhaseTwoTenancy;
    use RefreshDatabase;

    public function test_start_creates_one_tenant_scoped_sheet_with_historical_roster_and_audit(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $teacher = $this->memberWithRole($organization, 'Teacher/Trainer');
        [$group, $session] = $this->scheduledSession($organization, $membership, $teacher);
        $included = $this->assignedLearner($group, $session->starts_at->subDay());
        $detached = $this->assignedLearner($group, $session->starts_at->subDays(2), $session->starts_at->subHour());
        $future = $this->assignedLearner($group, $session->starts_at->addHour());

        $url = '/attendance/sessions/'.$session->uuid;
        $client = $this->actingAs($admin)->withSession($this->tenantSession($organization));
        $client->post($url)->assertRedirect();
        $client->post($url)->assertRedirect();
        $this->setTenant($organization, $membership);

        $sheet = AttendanceSheet::query()->sole();
        self::assertSame($group->getKey(), $sheet->group_id);
        self::assertSame($teacher->getKey(), $sheet->teacher_membership_id);
        self::assertSame([$included->getKey()], $sheet->learnerAttendances()->pluck('learner_id')->all());
        self::assertNotContains($detached->getKey(), $sheet->learnerAttendances()->pluck('learner_id')->all());
        self::assertNotContains($future->getKey(), $sheet->learnerAttendances()->pluck('learner_id')->all());
        $this->assertDatabaseCount('attendance_sheets', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.created', 'organization_id' => $organization->getKey()]);
    }

    public function test_draft_validation_locking_and_required_teacher_status_are_enforced(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $teacher = $this->memberWithRole($organization, 'Teacher/Trainer');
        [$group, $session] = $this->scheduledSession($organization, $membership, $teacher);
        $learner = $this->assignedLearner($group, $session->starts_at->subDay());
        $client = $this->actingAs($admin)->withSession($this->tenantSession($organization));
        $client->post('/attendance/sessions/'.$session->uuid);
        $this->setTenant($organization, $membership);
        $sheet = AttendanceSheet::query()->sole();

        $client->patch('/attendance/'.$sheet->uuid.'/draft', ['attendances' => [
            ['learner_uuid' => $learner->uuid, 'status' => 'present'],
        ]])->assertRedirect();
        $client->patch('/attendance/'.$sheet->uuid.'/validate')->assertSessionHasErrors('teacher_status');
        $client->patch('/attendance/'.$sheet->uuid.'/teacher', ['status' => 'present'])->assertRedirect();
        $client->patch('/attendance/'.$sheet->uuid.'/validate')->assertRedirect('/attendance/'.$sheet->uuid);

        $this->setTenant($organization, $membership);
        $sheet->refresh();
        self::assertTrue($sheet->isValidated());
        self::assertNotNull($sheet->validated_at);
        self::assertSame($admin->getKey(), $sheet->validated_by);
        $client->patch('/attendance/'.$sheet->uuid.'/draft', ['attendances' => [
            ['learner_uuid' => $learner->uuid, 'status' => 'absent'],
        ]])->assertSessionHasErrors('attendance');
        $client->patch('/attendance/'.$sheet->uuid.'/validate')->assertSessionHasErrors('attendance');
        $this->setTenant($organization, $membership);
        self::assertSame('present', LearnerAttendance::query()->sole()->status->value);
        self::assertEqualsCanonicalizing(
            ['attendance.created', 'attendance.updated', 'teacher_attendance.recorded', 'attendance.validated'],
            AuditLog::query()->pluck('action')->all(),
        );
    }

    public function test_injected_or_cancelled_session_data_is_rejected(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $teacher = $this->memberWithRole($organization, 'Teacher/Trainer');
        [$group, $session] = $this->scheduledSession($organization, $membership, $teacher);
        $learner = $this->assignedLearner($group, $session->starts_at->subDay());
        $outsider = Learner::factory()->create(['initial_level' => LearnerLevel::A1]);
        $client = $this->actingAs($admin)->withSession($this->tenantSession($organization));
        $client->post('/attendance/sessions/'.$session->uuid);
        $this->setTenant($organization, $membership);
        $sheet = AttendanceSheet::query()->sole();

        $client->patch('/attendance/'.$sheet->uuid.'/draft', [
            'organization_id' => $organization->getKey(),
            'attendances' => [['learner_uuid' => $learner->uuid, 'status' => 'present']],
        ])->assertSessionHasErrors('organization_id');
        $client->patch('/attendance/'.$sheet->uuid.'/draft', ['attendances' => [
            ['learner_uuid' => $outsider->uuid, 'status' => 'present'],
        ]])->assertSessionHasErrors('attendances');

        [$otherGroup, $cancelled] = $this->scheduledSession($organization, $membership, $teacher, CourseSessionStatus::Cancelled);
        $client->post('/attendance/sessions/'.$cancelled->uuid)->assertSessionHasErrors('session');
        self::assertNotSame($group->getKey(), $otherGroup->getKey());
        $this->assertDatabaseCount('attendance_sheets', 1);
    }

    public function test_validated_attendance_can_only_be_corrected_with_reason_and_keeps_history(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $teacher = $this->memberWithRole($organization, 'Teacher/Trainer');
        [$group, $session] = $this->scheduledSession($organization, $membership, $teacher);
        $learner = $this->assignedLearner($group, $session->starts_at->subDay());
        $sheet = $this->validatedSheet($organization, $admin, $session, $learner);
        $record = LearnerAttendance::query()->sole();
        $client = $this->actingAs($admin)->withSession($this->tenantSession($organization));

        $client->patch('/attendance/'.$sheet->uuid.'/learners/'.$record->uuid.'/correct', [
            'status' => 'absent', 'reason' => '',
        ])->assertSessionHasErrors('reason');
        $client->patch('/attendance/'.$sheet->uuid.'/learners/'.$record->uuid.'/correct', [
            'status' => 'absent', 'reason' => 'Justificatif reçu après validation.',
        ])->assertRedirect();
        $client->patch('/attendance/'.$sheet->uuid.'/teacher/correct', [
            'status' => 'excused', 'reason' => 'Remplacement confirmé.',
        ])->assertRedirect();

        $this->setTenant($organization, $membership);
        self::assertSame('absent', $record->refresh()->status->value);
        $this->assertDatabaseCount('attendance_corrections', 2);
        $learnerCorrection = AttendanceCorrection::query()->whereNotNull('learner_attendance_id')->sole();
        self::assertSame('present', $learnerCorrection->before_status->value);
        self::assertSame('absent', $learnerCorrection->after_status->value);
        self::assertSame($admin->getKey(), $learnerCorrection->corrected_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.corrected']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'teacher_attendance.corrected']);
        $client->get('/learners/'.$learner->uuid.'/attendance?to='.now()->addDays(2)->format('Y-m-d'))->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Attendance/LearnerHistory')
                ->where('summary.sessions', 1)->where('summary.absent', 1)->where('summary.rate', 0),
        );
    }

    public function test_teacher_only_manages_own_sessions_and_cannot_correct_validated_sheet(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $teacher = $this->memberWithRole($organization, 'Teacher/Trainer');
        $otherTeacher = $this->memberWithRole($organization, 'Teacher/Trainer');
        [$group, $ownSession] = $this->scheduledSession($organization, $membership, $teacher);
        [, $otherSession] = $this->scheduledSession($organization, $membership, $otherTeacher);
        $learner = $this->assignedLearner($group, $ownSession->starts_at->subDay());
        $teacherClient = $this->actingAs($teacher->user)->withSession($this->tenantSession($organization));

        $teacherClient->get('/attendance')->assertOk()->assertInertia(
            fn (Assert $page) => $page->has('sessions.data', 1)->where('sessions.data.0.uuid', $ownSession->uuid),
        );
        $teacherClient->post('/attendance/sessions/'.$otherSession->uuid)->assertForbidden();
        $teacherClient->post('/attendance/sessions/'.$ownSession->uuid)->assertRedirect();
        $this->setTenant($organization, $teacher);
        $sheet = AttendanceSheet::query()->sole();
        $teacherClient->patch('/attendance/'.$sheet->uuid.'/draft', ['attendances' => [
            ['learner_uuid' => $learner->uuid, 'status' => 'present'],
        ]]);
        $teacherClient->patch('/attendance/'.$sheet->uuid.'/teacher', ['status' => 'present']);
        $teacherClient->patch('/attendance/'.$sheet->uuid.'/validate')->assertRedirect();
        $this->setTenant($organization, $teacher);
        $record = LearnerAttendance::query()->sole();
        $teacherClient->patch('/attendance/'.$sheet->uuid.'/learners/'.$record->uuid.'/correct', [
            'status' => 'absent', 'reason' => 'Tentative non autorisée.',
        ])->assertForbidden();
        $this->setTenant($organization, $teacher);
        self::assertSame('present', $record->refresh()->status->value);
        self::assertNotSame($admin->getKey(), $teacher->user->getKey());
    }

    public function test_accountant_has_no_attendance_access_and_tenant_cannot_read_foreign_sheet(): void
    {
        [$organizationA, $adminA] = $this->tenantWithUser();
        [$organizationB, $adminB, $membershipB] = $this->tenantWithUser();
        $accountant = $this->memberWithRole($organizationA, 'Accountant');
        $teacherB = $this->memberWithRole($organizationB, 'Teacher/Trainer');
        [$groupB, $sessionB] = $this->scheduledSession($organizationB, $membershipB, $teacherB);
        $learnerB = $this->assignedLearner($groupB, $sessionB->starts_at->subDay());
        $sheetB = $this->validatedSheet($organizationB, $adminB, $sessionB, $learnerB);

        $this->actingAs($accountant->user)->withSession($this->tenantSession($organizationA))
            ->get('/attendance')->assertForbidden();
        $this->actingAs($adminA)->withSession($this->tenantSession($organizationA))
            ->get('/attendance/'.$sheetB->uuid)->assertNotFound();
        $this->actingAs($adminA)->withSession($this->tenantSession($organizationA))
            ->post('/attendance/sessions/'.$sessionB->uuid)->assertNotFound();
        $this->actingAs($adminA)->withSession($this->tenantSession($organizationA))
            ->patch('/attendance/'.$sheetB->uuid.'/validate')->assertNotFound();
        $this->actingAs($adminA)->withSession($this->tenantSession($organizationA))
            ->get('/learners/'.$learnerB->uuid.'/attendance')->assertNotFound();
        $this->actingAs($adminA)->withSession($this->tenantSession($organizationA))
            ->patch('/attendance/'.$sheetB->uuid.'/teacher/correct', ['status' => 'absent', 'reason' => 'Injection'])
            ->assertNotFound();
    }

    public function test_present_absent_and_excused_statuses_are_persisted_for_the_server_roster(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $teacher = $this->memberWithRole($organization, 'Teacher/Trainer');
        [$group, $session] = $this->scheduledSession($organization, $membership, $teacher);
        $learners = collect([
            $this->assignedLearner($group, $session->starts_at->subDay()),
            $this->assignedLearner($group, $session->starts_at->subDay()),
            $this->assignedLearner($group, $session->starts_at->subDay()),
        ]);
        $client = $this->actingAs($admin)->withSession($this->tenantSession($organization));
        $client->post('/attendance/sessions/'.$session->uuid);
        $this->setTenant($organization, $membership);
        $sheet = AttendanceSheet::query()->sole();
        $client->patch('/attendance/'.$sheet->uuid.'/draft', ['attendances' => [
            ['learner_uuid' => $learners[0]->uuid, 'status' => 'present'],
            ['learner_uuid' => $learners[1]->uuid, 'status' => 'absent'],
            ['learner_uuid' => $learners[2]->uuid, 'status' => 'excused'],
        ]])->assertRedirect();
        $client->patch('/attendance/'.$sheet->uuid.'/teacher', [
            'status' => 'present', 'teacher_membership_uuid' => $teacher->uuid,
        ])->assertSessionHasErrors('teacher_membership_uuid');
        $this->setTenant($organization, $membership);
        self::assertEqualsCanonicalizing(
            ['present', 'absent', 'excused'],
            LearnerAttendance::query()->get()->map(fn (LearnerAttendance $record): string => $record->status->value)->all(),
        );
    }

    public function test_excused_absence_is_in_denominator_and_teacher_history_marks_non_pointed_sessions(): void
    {
        [$organization, $admin, $membership] = $this->tenantWithUser();
        $teacher = $this->memberWithRole($organization, 'Teacher/Trainer');
        [$group, $session] = $this->scheduledSession($organization, $membership, $teacher);
        $learner = $this->assignedLearner($group, $session->starts_at->subDay());
        $client = $this->actingAs($admin)->withSession($this->tenantSession($organization));
        $client->post('/attendance/sessions/'.$session->uuid);
        $this->setTenant($organization, $membership);
        $sheet = AttendanceSheet::query()->sole();
        $client->patch('/attendance/'.$sheet->uuid.'/draft', ['attendances' => [
            ['learner_uuid' => $learner->uuid, 'status' => 'excused'],
        ]]);
        $client->patch('/attendance/'.$sheet->uuid.'/teacher', ['status' => 'present']);
        $client->patch('/attendance/'.$sheet->uuid.'/validate');
        [, $unpointed] = $this->scheduledSession($organization, $membership, $teacher, CourseSessionStatus::Scheduled, 2);
        $to = now()->addDays(2)->format('Y-m-d');

        $client->get('/learners/'.$learner->uuid.'/attendance?to='.$to)->assertInertia(
            fn (Assert $page) => $page->where('summary.sessions', 1)
                ->where('summary.excused', 1)->where('summary.present', 0)->where('summary.rate', 0),
        );
        $client->get('/attendance/teachers/'.$teacher->uuid.'?to='.$to)->assertInertia(
            fn (Assert $page) => $page->component('Attendance/TeacherHistory')
                ->where('sessions.data.0.teacher_attendance_label', 'Non pointé')
                ->where('sessions.data.0.uuid', $unpointed->uuid),
        );
    }

    private function memberWithRole(Organization $organization, string $roleName): OrganizationMembership
    {
        $user = User::factory()->create();
        $membership = OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);
        $role = Role::query()->where('organization_id', $organization->getKey())
            ->where('name', $roleName)->sole();
        app(MembershipAuthorizer::class)->syncRoles($membership, [$role->getKey()]);

        return $membership->load('user');
    }

    /** @return array{0: Group, 1: CourseSession} */
    private function scheduledSession(
        Organization $organization,
        OrganizationMembership $actor,
        OrganizationMembership $teacher,
        CourseSessionStatus $status = CourseSessionStatus::Scheduled,
        int $dayOffset = 1,
    ): array {
        app(TenantContext::class)->set($organization, $actor);
        $group = Group::factory()->create([
            'level' => LearnerLevel::A1,
            'teacher_membership_id' => $teacher->getKey(),
            'created_by' => $actor->user_id,
        ]);
        $startsAt = now()->addDays($dayOffset)->startOfHour();
        $session = CourseSession::factory()->create([
            'group_id' => $group->getKey(),
            'teacher_membership_id' => $teacher->getKey(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(90),
            'status' => $status,
            'created_by' => $actor->user_id,
            'cancelled_at' => $status === CourseSessionStatus::Cancelled ? now() : null,
            'cancelled_by' => $status === CourseSessionStatus::Cancelled ? $actor->user_id : null,
        ]);

        return [$group, $session];
    }

    private function assignedLearner(
        Group $group,
        mixed $assignedAt,
        mixed $detachedAt = null,
    ): Learner {
        $learner = Learner::factory()->create(['initial_level' => $group->level]);
        GroupLearnerAssignment::query()->create([
            'group_id' => $group->getKey(),
            'learner_id' => $learner->getKey(),
            'assigned_at' => $assignedAt,
            'detached_at' => $detachedAt,
        ]);

        return $learner;
    }

    private function validatedSheet(
        Organization $organization,
        User $actor,
        CourseSession $session,
        Learner $learner,
    ): AttendanceSheet {
        $client = $this->actingAs($actor)->withSession($this->tenantSession($organization));
        $client->post('/attendance/sessions/'.$session->uuid)->assertRedirect();
        $membership = OrganizationMembership::query()->where('organization_id', $organization->getKey())
            ->where('user_id', $actor->getKey())->sole();
        $this->setTenant($organization, $membership);
        $sheet = AttendanceSheet::query()->where('course_session_id', $session->getKey())->sole();
        $client->patch('/attendance/'.$sheet->uuid.'/draft', ['attendances' => [
            ['learner_uuid' => $learner->uuid, 'status' => 'present'],
        ]])->assertRedirect();
        $client->patch('/attendance/'.$sheet->uuid.'/teacher', ['status' => 'present'])->assertRedirect();
        $client->patch('/attendance/'.$sheet->uuid.'/validate')->assertRedirect();
        $this->setTenant($organization, $membership);

        return $sheet->refresh();
    }

    private function setTenant(Organization $organization, OrganizationMembership $membership): void
    {
        app(TenantContext::class)->set($organization, $membership);
    }
}
