<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Queries;

use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Pedagogy\Enums\QuestionStatus;
use App\Domain\Pedagogy\Models\PlacementQuestion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final readonly class PlacementQuestionSelector
{
    public function __construct(private VisibleQuestionQuery $visibleQuestions) {}

    /** @return Collection<int, PlacementQuestion> */
    public function select(): Collection
    {
        $perLevel = (int) config('placement.questions_per_level', 3);
        $configuredCount = (int) config('placement.question_count', 18);
        if ($configuredCount < 15 || $configuredCount > 30 || $perLevel * count(LearnerLevel::cases()) !== $configuredCount) {
            throw ValidationException::withMessages(['test' => 'La configuration du test de positionnement est invalide.']);
        }

        $selected = new Collection;
        foreach (LearnerLevel::cases() as $level) {
            $questions = $this->visibleQuestions->build()
                ->where('status', QuestionStatus::Active)
                ->where('language', config('placement.language', 'de'))
                ->where('level', $level)
                ->orderByRaw('organization_id IS NULL DESC')
                ->orderBy('uuid')
                ->limit($perLevel)
                ->get();
            if ($questions->count() !== $perLevel) {
                throw ValidationException::withMessages([
                    'test' => "Le niveau {$level->value} ne contient pas assez de questions actives pour démarrer le test.",
                ]);
            }
            $selected = $selected->concat($questions);
        }

        return $selected->values();
    }
}
