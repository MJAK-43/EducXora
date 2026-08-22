<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Pedagogy\Actions\CreatePlacementQuestion;
use App\Domain\Pedagogy\Actions\DisablePlacementQuestion;
use App\Domain\Pedagogy\Actions\EnablePlacementQuestion;
use App\Domain\Pedagogy\Actions\UpdatePlacementQuestion;
use App\Domain\Pedagogy\Models\PlacementQuestion;
use App\Domain\Pedagogy\Queries\VisibleQuestionQuery;
use App\Http\Requests\Pedagogy\IndexPlacementQuestionRequest;
use App\Http\Requests\Pedagogy\StorePlacementQuestionRequest;
use App\Http\Requests\Pedagogy\UpdatePlacementQuestionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PlacementQuestionController
{
    public function __construct(
        private VisibleQuestionQuery $visibleQuestions,
        private CreatePlacementQuestion $createQuestion,
        private UpdatePlacementQuestion $updateQuestion,
        private DisablePlacementQuestion $disableQuestion,
        private EnablePlacementQuestion $enableQuestion,
    ) {}

    public function index(IndexPlacementQuestionRequest $request): Response
    {
        $filters = $request->validated();
        $questions = $this->visibleQuestions->build()
            ->when(($filters['search'] ?? '') !== '', fn ($query) => $query->where('prompt', 'ilike', '%'.trim((string) $filters['search']).'%'))
            ->when(($filters['level'] ?? '') !== '', fn ($query) => $query->where('level', $filters['level']))
            ->when(($filters['source'] ?? '') !== '', fn ($query) => $query->where('source', $filters['source']))
            ->when(($filters['status'] ?? '') !== '', fn ($query) => $query->where('status', $filters['status']))
            ->orderBy('level')->orderBy('prompt')->paginate(20)->withQueryString()
            ->through(fn (PlacementQuestion $question): array => $this->present($question));

        return Inertia::render('Pedagogy/Questions/Index', [
            'questions' => $questions,
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'level' => (string) ($filters['level'] ?? ''),
                'source' => (string) ($filters['source'] ?? ''),
                'status' => (string) ($filters['status'] ?? ''),
            ],
            'levels' => $this->levels(),
            'can' => ['create' => $request->user()->can('create', PlacementQuestion::class)],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->can('create', PlacementQuestion::class), 403);

        return Inertia::render('Pedagogy/Questions/Create', ['levels' => $this->levels()]);
    }

    public function store(StorePlacementQuestionRequest $request): RedirectResponse
    {
        $question = $this->createQuestion->execute($this->payload($request->validated()), $request->user());

        return redirect()->route('pedagogy.questions.edit', ['questionUuid' => $question->uuid])->with('status', 'Question créée.');
    }

    public function edit(Request $request, string $questionUuid): Response
    {
        $question = $this->question($questionUuid);
        abort_unless($request->user()->can('view', $question), 403);

        return Inertia::render('Pedagogy/Questions/Edit', [
            'question' => $this->present($question),
            'levels' => $this->levels(),
            'can' => [
                'update' => $request->user()->can('update', $question),
                'disable' => $request->user()->can('disable', $question),
                'enable' => $request->user()->can('enable', $question),
            ],
        ]);
    }

    public function update(UpdatePlacementQuestionRequest $request, string $questionUuid): RedirectResponse
    {
        $question = $this->question($questionUuid);
        abort_unless($request->user()->can('update', $question), 403);
        $this->updateQuestion->execute($question, $this->payload($request->validated()), $request->user());

        return back()->with('status', 'Question mise à jour. Les tests déjà démarrés conservent leur instantané.');
    }

    public function disable(Request $request, string $questionUuid): RedirectResponse
    {
        $question = $this->question($questionUuid);
        abort_unless($request->user()->can('disable', $question), 403);
        $this->disableQuestion->execute($question, $request->user());

        return back()->with('status', 'Question désactivée.');
    }

    public function enable(Request $request, string $questionUuid): RedirectResponse
    {
        $question = $this->question($questionUuid);
        abort_unless($request->user()->can('enable', $question), 403);
        $this->enableQuestion->execute($question, $request->user());

        return back()->with('status', 'Question réactivée.');
    }

    private function question(string $uuid): PlacementQuestion
    {
        return $this->visibleQuestions->build()->where('uuid', $uuid)->firstOrFail();
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return [
            'language' => $data['language'],
            'level' => $data['level'],
            'prompt' => trim((string) $data['prompt']),
            'choices' => [
                'A' => trim((string) $data['choice_a']),
                'B' => trim((string) $data['choice_b']),
                'C' => trim((string) $data['choice_c']),
                'D' => trim((string) $data['choice_d']),
            ],
            'correct_choice' => $data['correct_choice'],
        ];
    }

    /** @return array<string, mixed> */
    private function present(PlacementQuestion $question): array
    {
        return [
            'uuid' => $question->uuid,
            'source' => $question->source->value,
            'source_label' => $question->isSystem() ? 'Système' : 'Organisation',
            'language' => $question->language->value,
            'level' => $question->level->value,
            'prompt' => $question->prompt,
            'choices' => $question->choices,
            'correct_choice' => $question->correct_choice,
            'status' => $question->status->value,
            'readonly' => $question->isSystem(),
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function levels(): array
    {
        return array_map(fn (LearnerLevel $level): array => ['value' => $level->value, 'label' => $level->value], LearnerLevel::cases());
    }
}
