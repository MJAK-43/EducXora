<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Learner\Actions\ArchiveLearner;
use App\Domain\Learner\Actions\CreateLearner;
use App\Domain\Learner\Actions\RestoreLearner;
use App\Domain\Learner\Actions\UpdateLearner;
use App\Domain\Learner\Enums\LearnerLanguage;
use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learner\Models\Learner;
use App\Domain\Learner\Queries\LearnerIndexQuery;
use App\Domain\Learner\Support\LearnerPresenter;
use App\Http\Requests\Learners\IndexLearnerRequest;
use App\Http\Requests\Learners\StoreLearnerRequest;
use App\Http\Requests\Learners\UpdateLearnerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class LearnerController
{
    public function __construct(
        private LearnerIndexQuery $indexQuery,
        private CreateLearner $createLearner,
        private UpdateLearner $updateLearner,
        private ArchiveLearner $archiveLearner,
        private RestoreLearner $restoreLearner,
    ) {}

    public function index(IndexLearnerRequest $request): Response
    {
        $filters = $request->validated();
        $learners = $this->indexQuery->build($filters)->paginate(20)->withQueryString()
            ->through(fn (Learner $learner): array => LearnerPresenter::summary($learner));

        return Inertia::render('Learners/Index', [
            'learners' => $learners,
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'status' => (string) ($filters['status'] ?? 'active'),
                'level' => (string) ($filters['level'] ?? ''),
                'language' => (string) ($filters['language'] ?? ''),
            ],
            'options' => $this->options(),
            'can' => [
                'create' => $request->user()->can('create', Learner::class),
                'archive' => $request->user()->can('learners.archive'),
                'restore' => $request->user()->can('learners.restore'),
                'export' => $request->user()->can('learners.export'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->can('create', Learner::class), 403);

        return Inertia::render('Learners/Create', ['options' => $this->options()]);
    }

    public function store(StoreLearnerRequest $request): RedirectResponse
    {
        $learner = $this->createLearner->execute(
            $request->safe()->except(['photo']),
            $request->user(),
            $request->file('photo'),
        );

        return redirect()->route('learners.show', ['learnerUuid' => $learner->uuid])
            ->with('status', 'Apprenant créé avec succès.');
    }

    public function show(Request $request, string $learnerUuid): Response
    {
        $learner = $this->learner($learnerUuid);
        abort_unless($request->user()->can('view', $learner), 403);

        return Inertia::render('Learners/Show', [
            'learner' => LearnerPresenter::detail($learner),
            'can' => [
                'update' => $request->user()->can('update', $learner),
                'archive' => $request->user()->can('archive', $learner),
                'restore' => $request->user()->can('restore', $learner),
            ],
        ]);
    }

    public function edit(Request $request, string $learnerUuid): Response
    {
        $learner = $this->learner($learnerUuid);
        abort_unless($request->user()->can('update', $learner), 403);

        return Inertia::render('Learners/Edit', [
            'learner' => LearnerPresenter::detail($learner),
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateLearnerRequest $request, string $learnerUuid): RedirectResponse
    {
        $learner = $this->learner($learnerUuid);
        abort_unless($request->user()->can('update', $learner), 403);
        $this->updateLearner->execute(
            $learner,
            $request->safe()->except(['photo', 'remove_photo']),
            $request->user(),
            $request->file('photo'),
            $request->boolean('remove_photo'),
        );

        return redirect()->route('learners.show', ['learnerUuid' => $learner->uuid])
            ->with('status', 'Apprenant mis à jour.');
    }

    public function archive(Request $request, string $learnerUuid): RedirectResponse
    {
        $learner = $this->learner($learnerUuid);
        abort_unless($request->user()->can('archive', $learner), 403);
        $this->archiveLearner->execute($learner, $request->user());

        return back()->with('status', 'Apprenant archivé.');
    }

    public function restore(Request $request, string $learnerUuid): RedirectResponse
    {
        $learner = $this->learner($learnerUuid);
        abort_unless($request->user()->can('restore', $learner), 403);
        $this->restoreLearner->execute($learner, $request->user());

        return back()->with('status', 'Apprenant restauré.');
    }

    private function learner(string $uuid): Learner
    {
        return Learner::query()->where('uuid', $uuid)->firstOrFail();
    }

    /** @return array<string, array<int, array{value: string, label: string}>> */
    private function options(): array
    {
        return [
            'levels' => array_map(fn (LearnerLevel $level): array => ['value' => $level->value, 'label' => $level->value], LearnerLevel::cases()),
            'languages' => array_map(fn (LearnerLanguage $language): array => ['value' => $language->value, 'label' => $language->label()], LearnerLanguage::cases()),
        ];
    }
}
