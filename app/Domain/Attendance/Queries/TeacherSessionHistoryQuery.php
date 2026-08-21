<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Queries;

use App\Domain\Scheduling\Models\CourseSession;
use App\Models\OrganizationMembership;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class TeacherSessionHistoryQuery
{
    /** @return Builder<CourseSession> */
    public function build(OrganizationMembership $teacher, CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return CourseSession::query()->with(['group', 'teacherMembership.user', 'attendanceSheet.teacherAttendance'])
            ->where('teacher_membership_id', $teacher->getKey())
            ->where('starts_at', '>=', $from->utc())
            ->where('starts_at', '<', $to->utc())
            ->orderByDesc('starts_at');
    }
}
