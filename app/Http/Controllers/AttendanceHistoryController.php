<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Attendance\Models\AttendanceCorrection;
use App\Domain\Attendance\Models\AttendanceSheet;
use App\Domain\Attendance\Models\LearnerAttendance;
use App\Domain\Attendance\Queries\LearnerAttendanceHistoryQuery;
use App\Domain\Attendance\Queries\TeacherSessionHistoryQuery;
use App\Domain\Attendance\Support\AttendancePresenter;
use App\Domain\Learner\Models\Learner;
use App\Domain\Learner\Support\LearnerPresenter;
use App\Domain\Scheduling\Models\CourseSession;
use App\Http\Requests\Attendance\AttendanceHistoryRequest;
use App\Models\OrganizationMembership;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AttendanceHistoryController
{
    public function __construct(
        private LearnerAttendanceHistoryQuery $learnerHistory,
        private TeacherSessionHistoryQuery $teacherHistory,
        private TenantContext $tenant,
    ) {}

    public function learner(AttendanceHistoryRequest $request, string $learnerUuid): Response
    {
        abort_unless($request->user()->can('viewLearnerHistory', AttendanceSheet::class), 403);
        $learner = Learner::query()->where('uuid', $learnerUuid)->firstOrFail();
        abort_unless($request->user()->can('view', $learner), 403);
        [$from, $to] = $this->period($request->validated());
        $history = $this->learnerHistory->build($learner, $from, $to)
            ->paginate(20)->withQueryString()->through(fn (LearnerAttendance $attendance): array => [
                'date_label' => $attendance->sheet->courseSession->starts_at
                    ->setTimezone($this->tenant->organization()->timezone)->format('d/m/Y'),
                'group_name' => $attendance->sheet->group->name,
                'session_uuid' => $attendance->sheet->courseSession->uuid,
                'status' => $attendance->status?->value,
                'status_label' => $attendance->status?->label(),
                'corrections' => $attendance->corrections->map(fn (AttendanceCorrection $correction): array => [
                    'before_label' => $correction->before_status->label(),
                    'after_label' => $correction->after_status->label(),
                    'reason' => $correction->reason,
                    'actor' => $correction->corrector->name,
                ])->all(),
            ]);

        return Inertia::render('Attendance/LearnerHistory', [
            'learner' => LearnerPresenter::detail($learner),
            'history' => $history,
            'summary' => $this->learnerHistory->summary($learner, $from, $to),
            'filters' => ['from' => $from->format('Y-m-d'), 'to' => $to->subDay()->format('Y-m-d')],
        ]);
    }

    public function teacher(AttendanceHistoryRequest $request, string $membershipUuid): Response
    {
        $teacher = OrganizationMembership::query()->with('user')
            ->where('organization_id', $this->tenant->id())->where('uuid', $membershipUuid)->firstOrFail();
        abort_unless($request->user()->can('viewTeacherHistory', [AttendanceSheet::class, $teacher]), 403);
        [$from, $to] = $this->period($request->validated());
        $sessions = $this->teacherHistory->build($teacher, $from, $to)
            ->paginate(20)->withQueryString()->through(function (CourseSession $session): array {
                $presented = AttendancePresenter::session($session, $this->tenant->organization()->timezone);
                $presented['teacher_attendance'] = $session->attendanceSheet?->teacherAttendance?->status->value;
                $presented['teacher_attendance_label'] = $session->attendanceSheet?->teacherAttendance?->status->label() ?? 'Non pointé';

                return $presented;
            });

        return Inertia::render('Attendance/TeacherHistory', [
            'teacher' => ['uuid' => $teacher->uuid, 'name' => $teacher->user->name],
            'sessions' => $sessions,
            'filters' => ['from' => $from->format('Y-m-d'), 'to' => $to->subDay()->format('Y-m-d')],
        ]);
    }

    /** @param array<string, mixed> $filters
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function period(array $filters): array
    {
        $timezone = $this->tenant->organization()->timezone;
        $from = CarbonImmutable::parse($filters['from'] ?? 'first day of January', $timezone)->startOfDay();
        $to = CarbonImmutable::parse($filters['to'] ?? 'today', $timezone)->addDay()->startOfDay();

        return [$from, $to];
    }
}
