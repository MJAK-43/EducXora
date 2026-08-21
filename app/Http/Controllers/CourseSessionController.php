<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Learning\Enums\GroupStatus;
use App\Domain\Learning\Models\Group;
use App\Domain\Learning\Queries\TeacherMembershipQuery;
use App\Domain\Scheduling\Actions\CancelCourseSession;
use App\Domain\Scheduling\Actions\CreateCourseSession;
use App\Domain\Scheduling\Actions\UpdateCourseSession;
use App\Domain\Scheduling\Models\CourseSession;
use App\Domain\Scheduling\Queries\CourseSessionIndexQuery;
use App\Domain\Scheduling\Support\CourseSessionPresenter;
use App\Http\Requests\Schedule\IndexCourseSessionRequest;
use App\Http\Requests\Schedule\StoreCourseSessionRequest;
use App\Http\Requests\Schedule\UpdateCourseSessionRequest;
use App\Support\Clock\Clock;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CourseSessionController
{
    public function __construct(
        private CourseSessionIndexQuery $indexQuery,
        private TeacherMembershipQuery $teachers,
        private CreateCourseSession $createSession,
        private UpdateCourseSession $updateSession,
        private CancelCourseSession $cancelSession,
        private TenantContext $tenant,
        private Clock $clock,
    ) {}

    public function index(IndexCourseSessionRequest $request): Response
    {
        $filters = $request->validated();
        $timezone = $this->tenant->organization()->timezone;
        $weekStart = CarbonImmutable::parse($filters['week'] ?? 'now', $timezone)->startOfWeek();
        $sessions = $this->indexQuery->build($weekStart, $filters, $request->user())->get()
            ->map(fn (CourseSession $session): array => CourseSessionPresenter::present($session, $timezone));

        return Inertia::render('Schedule/Index', [
            'sessions' => $sessions,
            'week' => ['start' => $weekStart->format('Y-m-d'), 'end' => $weekStart->endOfWeek()->format('Y-m-d'), 'previous' => $weekStart->subWeek()->format('Y-m-d'), 'next' => $weekStart->addWeek()->format('Y-m-d')],
            'filters' => ['group' => (string) ($filters['group'] ?? ''), 'teacher' => (string) ($filters['teacher'] ?? '')],
            'options' => $this->options(),
            'can' => ['create' => $request->user()->can('create', CourseSession::class), 'update' => $request->user()->can('schedule.update'), 'cancel' => $request->user()->can('schedule.cancel')],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->can('create', CourseSession::class), 403);

        return Inertia::render('Schedule/Create', ['options' => $this->options(), 'initialDate' => (string) $request->query('date', $this->clock->now($this->tenant->organization()->timezone)->format('Y-m-d'))]);
    }

    public function store(StoreCourseSessionRequest $request): RedirectResponse
    {
        $session = $this->createSession->execute($request->validated(), $request->user());
        $week = $session->starts_at->setTimezone($this->tenant->organization()->timezone)->startOfWeek()->format('Y-m-d');

        return redirect()->route('schedule.index', ['week' => $week])->with('status', 'Séance créée avec succès.');
    }

    public function edit(Request $request, string $sessionUuid): Response
    {
        $session = $this->session($sessionUuid);
        abort_unless($request->user()->can('update', $session), 403);
        $session->load(['group', 'teacherMembership.user']);

        return Inertia::render('Schedule/Edit', ['session' => CourseSessionPresenter::present($session, $this->tenant->organization()->timezone), 'options' => $this->options()]);
    }

    public function update(UpdateCourseSessionRequest $request, string $sessionUuid): RedirectResponse
    {
        $session = $this->session($sessionUuid);
        abort_unless($request->user()->can('update', $session), 403);
        $this->updateSession->execute($session, $request->validated(), $request->user());

        return redirect()->route('schedule.index', ['week' => $request->validated('date')])->with('status', 'Séance mise à jour.');
    }

    public function cancel(Request $request, string $sessionUuid): RedirectResponse
    {
        $session = $this->session($sessionUuid);
        abort_unless($request->user()->can('cancel', $session), 403);
        $this->cancelSession->execute($session, $request->user());

        return back()->with('status', 'Séance annulée.');
    }

    private function session(string $uuid): CourseSession
    {
        return CourseSession::query()->where('uuid', $uuid)->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        return [
            'groups' => Group::query()->where('status', GroupStatus::Active)->orderBy('name')->get()->map(fn (Group $group): array => ['value' => $group->uuid, 'label' => $group->name])->all(),
            'teachers' => $this->teachers->options(),
        ];
    }
}
