<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Actions;

use App\Domain\Learner\Enums\LearnerStatus;
use App\Domain\Learner\Models\Learner;
use App\Domain\Pedagogy\Enums\PlacementAttemptStatus;
use App\Domain\Pedagogy\Models\PlacementAttempt;
use App\Domain\Pedagogy\Models\PlacementAttemptQuestion;
use App\Domain\Pedagogy\Queries\PlacementQuestionSelector;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\Clock\Clock;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class StartPlacementAttempt
{
    public function __construct(
        private PlacementQuestionSelector $selector,
        private AuditLogger $audit,
        private TenantContext $tenant,
        private Clock $clock,
    ) {}

    public function execute(Learner $learner, User $actor): PlacementAttempt
    {
        return DB::transaction(function () use ($learner, $actor): PlacementAttempt {
            $lockedLearner = Learner::query()->lockForUpdate()->findOrFail($learner->getKey());
            if ($lockedLearner->status !== LearnerStatus::Active) {
                throw ValidationException::withMessages(['learner' => 'Le test exige un apprenant actif.']);
            }
            if (PlacementAttempt::query()->where('learner_id', $lockedLearner->getKey())->where('status', PlacementAttemptStatus::Started)->exists()) {
                throw ValidationException::withMessages(['learner' => 'Un test non finalisé existe déjà pour cet apprenant.']);
            }
            $questions = $this->selector->select();
            $attempt = PlacementAttempt::query()->create([
                'learner_id' => $lockedLearner->getKey(),
                'status' => PlacementAttemptStatus::Started,
                'language' => $lockedLearner->language,
                'question_count' => $questions->count(),
                'scoring_version' => config('placement.scoring_version'),
                'started_by' => $actor->getKey(),
                'started_at' => $this->clock->now(),
            ]);
            foreach ($questions as $index => $question) {
                PlacementAttemptQuestion::query()->create([
                    'attempt_id' => $attempt->getKey(),
                    'source_question_id' => $question->getKey(),
                    'position' => $index + 1,
                    'prompt_snapshot' => $question->prompt,
                    'choices_snapshot' => $question->choices,
                    'correct_choice_snapshot' => $question->correct_choice,
                    'level_snapshot' => $question->level,
                ]);
            }
            $this->audit->record('placement_test.started', $actor, $this->tenant->organization(), $attempt, [
                'attempt_uuid' => $attempt->uuid,
                'learner_uuid' => $lockedLearner->uuid,
                'question_count' => $attempt->question_count,
                'scoring_version' => $attempt->scoring_version,
            ]);

            return $attempt;
        });
    }
}
