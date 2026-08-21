<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Attendance\Models\AttendanceSheet;
use App\Domain\Scheduling\Models\CourseSession;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;

final readonly class AttendanceSheetPolicy
{
    public function __construct(private TenantContext $tenant, private MembershipAuthorizer $authorizer) {}

    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, 'attendance.view');
    }

    public function view(User $user, AttendanceSheet $sheet): bool
    {
        return $this->sameTenant($sheet) && $this->viewAny($user)
            && $this->canManageTeacher($user, $sheet->teacher_membership_id);
    }

    public function start(User $user, CourseSession $session): bool
    {
        return $this->sameTenantSession($session)
            && $this->authorizer->allows($user, 'attendance.take')
            && $this->canManageTeacher($user, $session->teacher_membership_id);
    }

    public function take(User $user, AttendanceSheet $sheet): bool
    {
        return $this->sameTenant($sheet)
            && $this->authorizer->allows($user, 'attendance.take')
            && $this->canManageTeacher($user, $sheet->teacher_membership_id);
    }

    public function validate(User $user, AttendanceSheet $sheet): bool
    {
        return $this->sameTenant($sheet)
            && $this->authorizer->allows($user, 'attendance.validate')
            && $this->canManageTeacher($user, $sheet->teacher_membership_id);
    }

    public function correct(User $user, AttendanceSheet $sheet): bool
    {
        return $this->sameTenant($sheet) && $this->authorizer->allows($user, 'attendance.correct');
    }

    public function viewLearnerHistory(User $user): bool
    {
        return $this->authorizer->allows($user, 'attendance.view_reports');
    }

    public function viewTeacherHistory(User $user, OrganizationMembership $teacher): bool
    {
        return (int) $teacher->organization_id === $this->tenant->id()
            && $this->viewAny($user)
            && $this->canManageTeacher($user, (int) $teacher->getKey());
    }

    private function canManageTeacher(User $user, int $teacherMembershipId): bool
    {
        return $this->authorizer->allows($user, 'attendance.view_reports')
            || $teacherMembershipId === (int) $this->tenant->membership()?->getKey();
    }

    private function sameTenant(AttendanceSheet $sheet): bool
    {
        return $this->tenant->resolved() && (int) $sheet->organization_id === $this->tenant->id();
    }

    private function sameTenantSession(CourseSession $session): bool
    {
        return $this->tenant->resolved() && (int) $session->organization_id === $this->tenant->id();
    }
}
