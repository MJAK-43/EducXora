<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learner\Models\Learner;
use App\Domain\Learning\Models\GroupLearnerAssignment;
use App\Domain\Pedagogy\Actions\ChangeLearnerLevel;
use App\Domain\Pedagogy\Enums\LevelChangeSource;
use App\Domain\Pedagogy\Models\LearnerLevelHistory;
use App\Domain\Pedagogy\Models\PlacementAttempt;
use App\Http\Requests\Pedagogy\UpdateLearnerLevelRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class LearnerPedagogyController
{
    public function __construct(private ChangeLearnerLevel $changeLevel) {}

    public function show(Request $request, string $learnerUuid): Response
    {
        $learner = $this->learner($learnerUuid);
        abort_unless($request->user()->can('viewPedagogy', $learner), 403);
        $attempts = PlacementAttempt::query()->with(['suggestedGroup', 'validatedGroup'])
            ->where('learner_id', $learner->getKey())->latest('started_at')->limit(20)->get()
            ->map(fn (PlacementAttempt $attempt): array => [
                'uuid' => $attempt->uuid,
                'status' => $attempt->status->value,
                'score' => $attempt->raw_score,
                'question_count' => $attempt->question_count,
                'percentage' => $attempt->percentage,
                'suggested_level' => $attempt->suggested_level?->value,
                'validated_level' => $attempt->validated_level?->value,
                'started_at' => $attempt->started_at->format('d/m/Y H:i'),
            ]);
        $history = LearnerLevelHistory::query()->with('actor')
            ->where('learner_id', $learner->getKey())->latest('occurred_at')->limit(50)->get()
            ->map(fn (LearnerLevelHistory $item): array => [
                'uuid' => $item->uuid,
                'from_level' => $item->from_level->value,
                'to_level' => $item->to_level->value,
                'source' => $item->source->value,
                'reason' => $item->reason,
                'actor' => $item->actor?->name,
                'occurred_at' => $item->occurred_at->format('d/m/Y H:i'),
            ]);
        $activeGroup = GroupLearnerAssignment::query()->with('group')
            ->where('learner_id', $learner->getKey())->whereNull('detached_at')->first();

        return Inertia::render('Pedagogy/LearnerHistory', [
            'learner' => [
                'uuid' => $learner->uuid,
                'name' => $learner->fullName(),
                'status' => $learner->status->value,
                'initial_level' => $learner->initial_level->value,
                'current_level' => $learner->current_level->value,
                'active_group' => $activeGroup?->group ? ['uuid' => $activeGroup->group->uuid, 'name' => $activeGroup->group->name] : null,
            ],
            'attempts' => $attempts,
            'history' => $history,
            'levels' => array_map(fn (LearnerLevel $level): array => ['value' => $level->value, 'label' => $level->value], LearnerLevel::cases()),
            'can' => [
                'startTest' => $request->user()->can('placement_tests.start'),
                'updateLevel' => $request->user()->can('updateLevel', $learner),
            ],
        ]);
    }

    public function updateLevel(UpdateLearnerLevelRequest $request, string $learnerUuid): RedirectResponse
    {
        $learner = $this->learner($learnerUuid);
        abort_unless($request->user()->can('updateLevel', $learner), 403);
        $this->changeLevel->execute(
            $learner,
            LearnerLevel::from((string) $request->validated('level')),
            LevelChangeSource::TeacherEvaluation,
            $request->user(),
            (string) $request->validated('reason'),
        );

        return back()->with('status', 'Niveau actuel mis à jour et historisé.');
    }

    private function learner(string $uuid): Learner
    {
        return Learner::query()->where('uuid', $uuid)->firstOrFail();
    }
}
