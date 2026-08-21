<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Queries;

use App\Domain\Attendance\Enums\AttendanceSheetStatus;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\LearnerAttendance;
use App\Domain\Learning\Models\Group;
use App\Domain\Scheduling\Enums\CourseSessionStatus;

final class GroupAttendanceSummaryQuery
{
    /** @return array{sessions: int, records: int, present: int, absent: int, excused: int, rate: float} */
    public function forGroup(Group $group): array
    {
        $base = LearnerAttendance::query()->select('learner_attendances.*')
            ->join('attendance_sheets', 'attendance_sheets.id', '=', 'learner_attendances.attendance_sheet_id')
            ->join('course_sessions', 'course_sessions.id', '=', 'attendance_sheets.course_session_id')
            ->where('attendance_sheets.group_id', $group->getKey())
            ->where('attendance_sheets.status', AttendanceSheetStatus::Validated)
            ->where('course_sessions.status', CourseSessionStatus::Scheduled);
        $records = (clone $base)->count();
        $present = (clone $base)->where('learner_attendances.status', AttendanceStatus::Present)->count();
        $absent = (clone $base)->where('learner_attendances.status', AttendanceStatus::Absent)->count();
        $excused = (clone $base)->where('learner_attendances.status', AttendanceStatus::Excused)->count();
        $sessions = (clone $base)->distinct('attendance_sheets.id')->count('attendance_sheets.id');

        return [
            'sessions' => $sessions,
            'records' => $records,
            'present' => $present,
            'absent' => $absent,
            'excused' => $excused,
            'rate' => $records > 0 ? round(($present / $records) * 100, 1) : 0.0,
        ];
    }
}
