<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Actions;

use App\Domain\Pedagogy\Enums\PlacementAttemptStatus;
use App\Domain\Pedagogy\Models\PlacementAttempt;
use App\Domain\Pedagogy\Queries\GroupSuggestionQuery;
use App\Domain\Pedagogy\Services\PlacementScorer;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Clock\Clock;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CompletePlacementAttempt
{
    public function __construct(
        private PlacementScorer $scorer,
        private GroupSuggestionQuery $groups,
        private AuditLogger $audit,
        private TenantContext $tenant,
        private Clock $clock,
    ) {}

    public function execute(PlacementAttempt $attempt, User $actor): PlacementAttempt
    {
        return DB::transaction(function () use ($attempt, $actor): PlacementAttempt {
            $locked = PlacementAttempt::query()->lockForUpdate()->findOrFail($attempt->getKey());
            if (! $locked->isStarted()) {
                throw ValidationException::withMessages(['test' => 'Ce test a déjà été finalisé.']);
            }
            $questions = $locked->questions()->lockForUpdate()->get();
            if ($questions->count() !== $locked->question_count || $questions->contains(fn ($question): bool => $question->selected_choice === null)) {
                throw ValidationException::withMessages(['test' => 'Toutes les questions doivent recevoir une réponse avant la finalisation.']);
            }
            $score = $questions->where('is_correct', true)->count();
            $percentage = round(($score / $locked->question_count) * 100, 2);
            $level = $this->scorer->levelFor($percentage, $locked->scoring_version);
            $group = $this->groups->firstAvailable($level, $locked->language);
            $locked->update([
                'status' => PlacementAttemptStatus::Completed,
                'raw_score' => $score,
                'percentage' => $percentage,
                'suggested_level' => $level,
                'suggested_group_id' => $group?->getKey(),
                'completed_by' => $actor->getKey(),
                'completed_at' => $this->clock->now(),
            ]);
            $this->audit->record('placement_test.completed', $actor, $this->tenant->organization(), $locked, [
                'attempt_uuid' => $locked->uuid,
                'learner_uuid' => $locked->learner->uuid,
                'score' => $score,
                'question_count' => $locked->question_count,
                'percentage' => $percentage,
                'suggested_level' => $level->value,
                'suggested_group_uuid' => $group?->uuid,
                'scoring_version' => $locked->scoring_version,
            ]);

            return $locked->refresh();
        });
    }
}
