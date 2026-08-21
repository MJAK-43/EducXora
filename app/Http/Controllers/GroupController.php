<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Attendance\Queries\GroupAttendanceSummaryQuery;
use App\Domain\Learner\Enums\LearnerLanguage;
use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learner\Enums\LearnerStatus;
use App\Domain\Learner\Models\Learner;
use App\Domain\Learning\Actions\ArchiveGroup;
use App\Domain\Learning\Actions\AttachLearnerToGroup;
use App\Domain\Learning\Actions\CreateGroup;
use App\Domain\Learning\Actions\DetachLearnerFromGroup;
use App\Domain\Learning\Actions\RestoreGroup;
use App\Domain\Learning\Actions\UpdateGroup;
use App\Domain\Learning\Models\Group;
use App\Domain\Learning\Queries\GroupIndexQuery;
use App\Domain\Learning\Queries\TeacherMembershipQuery;
use App\Domain\Learning\Support\GroupPresenter;
use App\Http\Requests\Groups\AttachLearnerRequest;
use App\Http\Requests\Groups\IndexGroupRequest;
use App\Http\Requests\Groups\StoreGroupRequest;
use App\Http\Requests\Groups\UpdateGroupRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class GroupController
{
    public function __construct(
        private GroupIndexQuery $indexQuery,
        private GroupAttendanceSummaryQuery $attendanceSummary,
        private TeacherMembershipQuery $teachers,
        private CreateGroup $createGroup,
        private UpdateGroup $updateGroup,
        private ArchiveGroup $archiveGroup,
        private RestoreGroup $restoreGroup,
        private AttachLearnerToGroup $attachLearner,
        private DetachLearnerFromGroup $detachLearner,
    ) {}

    public function index(IndexGroupRequest $request): Response
    {
        $filters = $request->validated();
        $groups = $this->indexQuery->build($filters, $request->user())->paginate(20)->withQueryString()
            ->through(fn (Group $group): array => GroupPresenter::summary($group));

        return Inertia::render('Groups/Index', [
            'groups' => $groups,
            'filters' => ['search' => (string) ($filters['search'] ?? ''), 'status' => (string) ($filters['status'] ?? 'active'), 'level' => (string) ($filters['level'] ?? ''), 'teacher' => (string) ($filters['teacher'] ?? '')],
            'options' => $this->options(),
            'can' => ['create' => $request->user()->can('create', Group::class), 'archive' => $request->user()->can('group.archive'), 'restore' => $request->user()->can('group.restore')],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->can('create', Group::class), 403);

        return Inertia::render('Groups/Create', ['options' => $this->options()]);
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $group = $this->createGroup->execute($request->validated(), $request->user());

        return redirect()->route('groups.show', ['groupUuid' => $group->uuid])->with('status', 'Groupe créé avec succès.');
    }

    public function show(Request $request, string $groupUuid): Response
    {
        $group = $this->group($groupUuid);
        abort_unless($request->user()->can('view', $group), 403);
        $group->load(['teacherMembership.user', 'activeAssignments.learner'])->loadCount('activeAssignments');

        return Inertia::render('Groups/Show', [
            'group' => GroupPresenter::detail($group),
            'candidates' => $this->candidates($group, (string) $request->query('candidate_search', '')),
            'candidateSearch' => (string) $request->query('candidate_search', ''),
            'attendanceSummary' => $request->user()->can('attendance.view_reports')
                ? $this->attendanceSummary->forGroup($group)
                : null,
            'can' => ['update' => $request->user()->can('update', $group), 'archive' => $request->user()->can('archive', $group), 'restore' => $request->user()->can('restore', $group), 'manageLearners' => $request->user()->can('manageLearners', $group)],
        ]);
    }

    public function edit(Request $request, string $groupUuid): Response
    {
        $group = $this->group($groupUuid);
        abort_unless($request->user()->can('update', $group), 403);
        $group->load(['teacherMembership.user'])->loadCount('activeAssignments');

        return Inertia::render('Groups/Edit', ['group' => GroupPresenter::summary($group), 'options' => $this->options()]);
    }

    public function update(UpdateGroupRequest $request, string $groupUuid): RedirectResponse
    {
        $group = $this->group($groupUuid);
        abort_unless($request->user()->can('update', $group), 403);
        $this->updateGroup->execute($group, $request->validated(), $request->user());

        return redirect()->route('groups.show', ['groupUuid' => $group->uuid])->with('status', 'Groupe mis à jour.');
    }

    public function archive(Request $request, string $groupUuid): RedirectResponse
    {
        $group = $this->group($groupUuid);
        abort_unless($request->user()->can('archive', $group), 403);
        $this->archiveGroup->execute($group, $request->user());

        return back()->with('status', 'Groupe archivé.');
    }

    public function restore(Request $request, string $groupUuid): RedirectResponse
    {
        $group = $this->group($groupUuid);
        abort_unless($request->user()->can('restore', $group), 403);
        $this->restoreGroup->execute($group, $request->user());

        return back()->with('status', 'Groupe restauré.');
    }

    public function attach(AttachLearnerRequest $request, string $groupUuid): RedirectResponse
    {
        $group = $this->group($groupUuid);
        abort_unless($request->user()->can('manageLearners', $group), 403);
        $learner = Learner::query()->where('uuid', $request->validated('learner_uuid'))->firstOrFail();
        $this->attachLearner->execute($group, $learner, $request->user());

        return back()->with('status', 'Apprenant ajouté au groupe.');
    }

    public function detach(Request $request, string $groupUuid, string $learnerUuid): RedirectResponse
    {
        $group = $this->group($groupUuid);
        abort_unless($request->user()->can('manageLearners', $group), 403);
        $this->detachLearner->execute($group, $learnerUuid, $request->user());

        return back()->with('status', 'Apprenant retiré du groupe.');
    }

    private function group(string $uuid): Group
    {
        return Group::query()->where('uuid', $uuid)->firstOrFail();
    }

    /** @return array<int, array{value: string, label: string, phone: string}> */
    private function candidates(Group $group, string $search): array
    {
        return Learner::query()->where('status', LearnerStatus::Active)->where('language', $group->language)->where('initial_level', $group->level)
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('group_learner_assignments')->whereColumn('group_learner_assignments.learner_id', 'learners.id')->whereNull('detached_at'))
            ->when($search !== '', fn (Builder $query) => $query->whereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", ['%'.trim($search).'%']))
            ->orderBy('last_name')->limit(50)->get()->map(fn (Learner $learner): array => ['value' => $learner->uuid, 'label' => $learner->fullName(), 'phone' => $learner->phone])->all();
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        return [
            'levels' => array_map(fn (LearnerLevel $level): array => ['value' => $level->value, 'label' => $level->value], LearnerLevel::cases()),
            'languages' => array_map(fn (LearnerLanguage $language): array => ['value' => $language->value, 'label' => $language->label()], LearnerLanguage::cases()),
            'teachers' => $this->teachers->options(),
        ];
    }
}
