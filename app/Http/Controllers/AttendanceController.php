<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Attendance\Actions\CorrectLearnerAttendance;
use App\Domain\Attendance\Actions\CorrectTeacherAttendance;
use App\Domain\Attendance\Actions\RecordTeacherAttendance;
use App\Domain\Attendance\Actions\StartAttendanceSheet;
use App\Domain\Attendance\Actions\UpdateAttendanceDraft;
use App\Domain\Attendance\Actions\ValidateAttendanceSheet;
use App\Domain\Attendance\Enums\AttendanceStatus;
use App\Domain\Attendance\Models\AttendanceSheet;
use App\Domain\Attendance\Queries\AttendanceSessionIndexQuery;
use App\Domain\Attendance\Support\AttendancePresenter;
use App\Domain\Scheduling\Models\CourseSession;
use App\Http\Requests\Attendance\CorrectAttendanceRequest;
use App\Http\Requests\Attendance\IndexAttendanceRequest;
use App\Http\Requests\Attendance\RecordTeacherAttendanceRequest;
use App\Http\Requests\Attendance\UpdateAttendanceDraftRequest;
use App\Http\Requests\Attendance\ValidateAttendanceRequest;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AttendanceController
{
    public function __construct(
        private AttendanceSessionIndexQuery $indexQuery,
        private StartAttendanceSheet $startAttendance,
        private UpdateAttendanceDraft $updateDraft,
        private RecordTeacherAttendance $recordTeacher,
        private ValidateAttendanceSheet $validateSheet,
        private CorrectLearnerAttendance $correctLearner,
        private CorrectTeacherAttendance $correctTeacher,
        private TenantContext $tenant,
    ) {}

    public function index(IndexAttendanceRequest $request): Response
    {
        $filters = $request->validated();
        [$from, $to] = $this->period($filters);
        $sessions = $this->indexQuery->build($from, $to, $filters, $request->user())
            ->paginate(20)->withQueryString()->through(function (CourseSession $session) use ($request): array {
                return [
                    ...AttendancePresenter::session($session, $this->tenant->organization()->timezone),
                    'can_start' => $request->user()->can('start', [AttendanceSheet::class, $session]),
                    'can_open' => $session->attendanceSheet
                        ? $request->user()->can('view', $session->attendanceSheet)
                        : false,
                ];
            });

        return Inertia::render('Attendance/Index', [
            'sessions' => $sessions,
            'filters' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->subDay()->format('Y-m-d'),
                'status' => (string) ($filters['status'] ?? ''),
            ],
        ]);
    }

    public function start(Request $request, string $sessionUuid): RedirectResponse
    {
        $session = $this->session($sessionUuid);
        abort_unless($request->user()->can('start', [AttendanceSheet::class, $session]), 403);
        $sheet = $this->startAttendance->execute($session, $request->user());

        return redirect()->route($sheet->isValidated() ? 'attendance.show' : 'attendance.take', ['sheetUuid' => $sheet->uuid]);
    }

    public function show(Request $request, string $sheetUuid): Response
    {
        $sheet = $this->sheet($sheetUuid);
        abort_unless($request->user()->can('view', $sheet), 403);
        $this->loadSheet($sheet);

        return Inertia::render('Attendance/Show', [
            'sheet' => AttendancePresenter::sheet($sheet, $this->tenant->organization()->timezone),
            'options' => AttendancePresenter::statusOptions(),
            'can' => [
                'take' => $request->user()->can('take', $sheet),
                'validate' => $request->user()->can('validate', $sheet),
                'correct' => $request->user()->can('correct', $sheet),
            ],
        ]);
    }

    public function take(Request $request, string $sheetUuid): Response|RedirectResponse
    {
        $sheet = $this->sheet($sheetUuid);
        abort_unless($request->user()->can('take', $sheet), 403);
        if ($sheet->isValidated()) {
            return redirect()->route('attendance.show', ['sheetUuid' => $sheet->uuid]);
        }
        $this->loadSheet($sheet);

        return Inertia::render('Attendance/Take', [
            'sheet' => AttendancePresenter::sheet($sheet, $this->tenant->organization()->timezone),
            'options' => AttendancePresenter::statusOptions(),
            'canValidate' => $request->user()->can('validate', $sheet),
        ]);
    }

    public function updateDraft(UpdateAttendanceDraftRequest $request, string $sheetUuid): RedirectResponse
    {
        $sheet = $this->sheet($sheetUuid);
        abort_unless($request->user()->can('take', $sheet), 403);
        $this->updateDraft->execute($sheet, $request->validated('attendances'), $request->user());

        return back()->with('status', 'Brouillon enregistré.');
    }

    public function recordTeacher(RecordTeacherAttendanceRequest $request, string $sheetUuid): RedirectResponse
    {
        $sheet = $this->sheet($sheetUuid);
        abort_unless($request->user()->can('take', $sheet), 403);
        $this->recordTeacher->execute(
            $sheet,
            AttendanceStatus::from($request->validated('status')),
            $request->user(),
        );

        return back()->with('status', 'Présence enseignant enregistrée.');
    }

    public function validate(ValidateAttendanceRequest $request, string $sheetUuid): RedirectResponse
    {
        $sheet = $this->sheet($sheetUuid);
        abort_unless($request->user()->can('validate', $sheet), 403);
        $this->validateSheet->execute($sheet, $request->user());

        return redirect()->route('attendance.show', ['sheetUuid' => $sheet->uuid])
            ->with('status', 'Pointage validé et verrouillé.');
    }

    public function correctLearner(CorrectAttendanceRequest $request, string $sheetUuid, string $learnerAttendanceUuid): RedirectResponse
    {
        $sheet = $this->sheet($sheetUuid);
        abort_unless($request->user()->can('correct', $sheet), 403);
        $this->correctLearner->execute(
            $sheet,
            $learnerAttendanceUuid,
            AttendanceStatus::from($request->validated('status')),
            $request->validated('reason'),
            $request->user(),
        );

        return back()->with('status', 'Présence apprenant corrigée et auditée.');
    }

    public function correctTeacher(CorrectAttendanceRequest $request, string $sheetUuid): RedirectResponse
    {
        $sheet = $this->sheet($sheetUuid);
        abort_unless($request->user()->can('correct', $sheet), 403);
        $this->correctTeacher->execute(
            $sheet,
            AttendanceStatus::from($request->validated('status')),
            $request->validated('reason'),
            $request->user(),
        );

        return back()->with('status', 'Présence enseignant corrigée et auditée.');
    }

    private function session(string $uuid): CourseSession
    {
        return CourseSession::query()->where('uuid', $uuid)->firstOrFail();
    }

    private function sheet(string $uuid): AttendanceSheet
    {
        return AttendanceSheet::query()->where('uuid', $uuid)->firstOrFail();
    }

    private function loadSheet(AttendanceSheet $sheet): void
    {
        $sheet->load([
            'courseSession', 'group', 'teacherMembership.user', 'creator', 'validator',
            'learnerAttendances.learner', 'teacherAttendance', 'corrections.corrector',
            'corrections.learnerAttendance.learner',
            'corrections.teacherAttendance.teacherMembership.user',
        ]);
    }

    /** @param array<string, mixed> $filters
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function period(array $filters): array
    {
        $timezone = $this->tenant->organization()->timezone;
        $from = CarbonImmutable::parse($filters['from'] ?? 'first day of this month', $timezone)->startOfDay();
        $to = CarbonImmutable::parse($filters['to'] ?? $from->endOfMonth()->format('Y-m-d'), $timezone)->addDay()->startOfDay();

        return [$from, $to];
    }
}
