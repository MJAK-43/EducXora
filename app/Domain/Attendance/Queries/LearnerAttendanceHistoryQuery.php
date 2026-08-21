<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Queries;

use App\Domain\Attendance\Enums\AttendanceSheetStatus;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\LearnerAttendance;
use App\Domain\Learner\Models\Learner;
use App\Domain\Scheduling\Enums\CourseSessionStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class LearnerAttendanceHistoryQuery
{
    /** @return Builder<LearnerAttendance> */
    public function build(Learner $learner, CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return LearnerAttendance::query()->select('learner_attendances.*')
            ->join('attendance_sheets', function ($join): void {
                $join->on('attendance_sheets.id', '=', 'learner_attendances.attendance_sheet_id')
                    ->on('attendance_sheets.organization_id', '=', 'learner_attendances.organization_id');
            })
            ->join('course_sessions', function ($join): void {
                $join->on('course_sessions.id', '=', 'attendance_sheets.course_session_id')
                    ->on('course_sessions.organization_id', '=', 'attendance_sheets.organization_id');
            })
            ->where('learner_attendances.learner_id', $learner->getKey())
            ->where('attendance_sheets.status', AttendanceSheetStatus::Validated)
            ->where('course_sessions.status', CourseSessionStatus::Scheduled)
            ->where('course_sessions.starts_at', '>=', $from->utc())
            ->where('course_sessions.starts_at', '<', $to->utc())
            ->with(['sheet.group', 'sheet.courseSession', 'corrections.corrector'])
            ->orderByDesc('course_sessions.starts_at');
    }

    /** @return array{sessions: int, present: int, absent: int, excused: int, rate: float} */
    public function summary(Learner $learner, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $base = $this->build($learner, $from, $to);
        $sessions = (clone $base)->count();
        $present = (clone $base)->where('learner_attendances.status', AttendanceStatus::Present)->count();
        $absent = (clone $base)->where('learner_attendances.status', AttendanceStatus::Absent)->count();
        $excused = (clone $base)->where('learner_attendances.status', AttendanceStatus::Excused)->count();

        return [
            'sessions' => $sessions,
            'present' => $present,
            'absent' => $absent,
            'excused' => $excused,
            'rate' => $sessions > 0 ? round(($present / $sessions) * 100, 1) : 0.0,
        ];
    }
}
