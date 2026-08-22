<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Actions;

use App\Domain\Pedagogy\Models\PlacementAttempt;
use App\Domain\Pedagogy\Models\PlacementAttemptQuestion;
use App\Models\User;
use App\Support\Clock\Clock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RecordPlacementAnswer
{
    public function __construct(private Clock $clock) {}

    public function execute(PlacementAttempt $attempt, string $questionUuid, string $choice, User $actor): PlacementAttemptQuestion
    {
        return DB::transaction(function () use ($attempt, $questionUuid, $choice, $actor): PlacementAttemptQuestion {
            $lockedAttempt = PlacementAttempt::query()->lockForUpdate()->findOrFail($attempt->getKey());
            if (! $lockedAttempt->isStarted()) {
                throw ValidationException::withMessages(['answer' => 'Ce test est déjà finalisé.']);
            }
            $question = PlacementAttemptQuestion::query()
                ->where('attempt_id', $lockedAttempt->getKey())
                ->where('uuid', $questionUuid)
                ->lockForUpdate()
                ->firstOrFail();
            if ($question->selected_choice !== null) {
                throw ValidationException::withMessages(['answer' => 'Cette réponse a déjà été enregistrée.']);
            }
            $question->update([
                'selected_choice' => $choice,
                'is_correct' => hash_equals($question->correct_choice_snapshot, $choice),
                'answered_by' => $actor->getKey(),
                'answered_at' => $this->clock->now(),
            ]);

            return $question;
        });
    }
}
