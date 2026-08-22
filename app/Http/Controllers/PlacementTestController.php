<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learner\Models\Learner;
use App\Domain\Learning\Enums\GroupStatus;
use App\Domain\Learning\Models\Group;
use App\Domain\Pedagogy\Actions\CompletePlacementAttempt;
use App\Domain\Pedagogy\Actions\RecordPlacementAnswer;
use App\Domain\Pedagogy\Actions\ReviewPlacementAttempt;
use App\Domain\Pedagogy\Actions\StartPlacementAttempt;
use App\Domain\Pedagogy\Enums\PlacementAttemptStatus;
use App\Domain\Pedagogy\Models\PlacementAttempt;
use App\Domain\Pedagogy\Models\PlacementAttemptQuestion;
use App\Http\Requests\Pedagogy\RecordPlacementAnswerRequest;
use App\Http\Requests\Pedagogy\ReviewPlacementAttemptRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PlacementTestController
{
    public function __construct(
        private StartPlacementAttempt $startAttempt,
        private RecordPlacementAnswer $recordAnswer,
        private CompletePlacementAttempt $completeAttempt,
        private ReviewPlacementAttempt $reviewAttempt,
    ) {}

    public function start(Request $request, string $learnerUuid): RedirectResponse
    {
        abort_unless($request->user()->can('placement_tests.start'), 403);
        $learner = Learner::query()->where('uuid', $learnerUuid)->firstOrFail();
        abort_unless($request->user()->can('viewPedagogy', $learner), 403);
        $attempt = $this->startAttempt->execute($learner, $request->user());

        return redirect()->route('pedagogy.tests.show', ['attemptUuid' => $attempt->uuid]);
    }

    public function show(Request $request, string $attemptUuid): Response
    {
        $attempt = $this->attempt($attemptUuid);
        abort_unless($request->user()->can('view', $attempt), 403);
        $attempt->load(['learner', 'questions', 'suggestedGroup', 'validatedGroup', 'reviewer']);

        return Inertia::render(
            $attempt->status === PlacementAttemptStatus::Started ? 'Pedagogy/Placement/Take' : 'Pedagogy/Placement/Result',
            [
                'attempt' => $this->present($attempt),
                'groups' => $request->user()->can('review', $attempt) ? $this->groupOptions() : [],
                'levels' => array_map(fn (LearnerLevel $level): array => ['value' => $level->value, 'label' => $level->value], LearnerLevel::cases()),
                'can' => [
                    'answer' => $request->user()->can('answer', $attempt),
                    'complete' => $request->user()->can('complete', $attempt),
                    'review' => $request->user()->can('review', $attempt),
                ],
            ],
        );
    }

    public function answer(RecordPlacementAnswerRequest $request, string $attemptUuid): RedirectResponse
    {
        $attempt = $this->attempt($attemptUuid);
        abort_unless($request->user()->can('answer', $attempt), 403);
        $this->recordAnswer->execute($attempt, (string) $request->validated('question_uuid'), (string) $request->validated('answer'), $request->user());

        return back()->with('status', 'Réponse enregistrée.');
    }

    public function complete(Request $request, string $attemptUuid): RedirectResponse
    {
        $attempt = $this->attempt($attemptUuid);
        abort_unless($request->user()->can('complete', $attempt), 403);
        $this->completeAttempt->execute($attempt, $request->user());

        return redirect()->route('pedagogy.tests.show', ['attemptUuid' => $attempt->uuid])->with('status', 'Test finalisé. La suggestion attend une validation humaine.');
    }

    public function review(ReviewPlacementAttemptRequest $request, string $attemptUuid): RedirectResponse
    {
        $attempt = $this->attempt($attemptUuid);
        abort_unless($request->user()->can('review', $attempt), 403);
        $groupUuid = $request->validated('group_uuid');
        $group = is_string($groupUuid) ? Group::query()->where('uuid', $groupUuid)->firstOrFail() : null;
        $this->reviewAttempt->execute(
            $attempt,
            LearnerLevel::from((string) $request->validated('validated_level')),
            $group,
            $request->validated('reason'),
            $request->user(),
        );

        return back()->with('status', 'Décision pédagogique validée.');
    }

    private function attempt(string $uuid): PlacementAttempt
    {
        return PlacementAttempt::query()->where('uuid', $uuid)->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function present(PlacementAttempt $attempt): array
    {
        return [
            'uuid' => $attempt->uuid,
            'status' => $attempt->status->value,
            'learner' => ['uuid' => $attempt->learner->uuid, 'name' => $attempt->learner->fullName(), 'current_level' => $attempt->learner->current_level->value],
            'question_count' => $attempt->question_count,
            'answered_count' => $attempt->questions->whereNotNull('selected_choice')->count(),
            'questions' => $attempt->questions->map(fn (PlacementAttemptQuestion $question): array => [
                'uuid' => $question->uuid,
                'position' => $question->position,
                'prompt' => $question->prompt_snapshot,
                'choices' => $question->choices_snapshot,
                'selected_choice' => $question->selected_choice,
            ])->values(),
            'raw_score' => $attempt->raw_score,
            'percentage' => $attempt->percentage,
            'scoring_version' => $attempt->scoring_version,
            'suggested_level' => $attempt->suggested_level?->value,
            'suggested_group' => $attempt->suggestedGroup ? ['uuid' => $attempt->suggestedGroup->uuid, 'name' => $attempt->suggestedGroup->name] : null,
            'validated_level' => $attempt->validated_level?->value,
            'validated_group' => $attempt->validatedGroup ? ['uuid' => $attempt->validatedGroup->uuid, 'name' => $attempt->validatedGroup->name] : null,
            'review_reason' => $attempt->review_reason,
            'started_at' => $attempt->started_at->format('d/m/Y H:i'),
            'completed_at' => $attempt->completed_at?->format('d/m/Y H:i'),
            'reviewed_at' => $attempt->reviewed_at?->format('d/m/Y H:i'),
            'reviewer' => $attempt->reviewer?->name,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function groupOptions(): array
    {
        return Group::query()->where('status', GroupStatus::Active)->withCount('activeAssignments')
            ->orderBy('level')->orderBy('name')->get()->map(fn (Group $group): array => [
                'uuid' => $group->uuid,
                'name' => $group->name,
                'level' => $group->level->value,
                'language' => $group->language->value,
                'capacity' => $group->capacity,
                'active_count' => (int) $group->active_assignments_count,
                'available' => (int) $group->active_assignments_count < $group->capacity,
            ])->all();
    }
}
