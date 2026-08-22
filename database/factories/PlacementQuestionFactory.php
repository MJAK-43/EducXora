<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Learner\Enums\LearnerLanguage;
use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Pedagogy\Enums\QuestionSource;
use App\Domain\Pedagogy\Enums\QuestionStatus;
use App\Domain\Pedagogy\Models\PlacementQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlacementQuestion> */
final class PlacementQuestionFactory extends Factory
{
    protected $model = PlacementQuestion::class;

    public function definition(): array
    {
        return [
            'organization_id' => null,
            'source' => QuestionSource::System,
            'language' => LearnerLanguage::German,
            'level' => fake()->randomElement(LearnerLevel::cases()),
            'prompt' => fake()->sentence().'?',
            'choices' => ['A' => fake()->word(), 'B' => fake()->word(), 'C' => fake()->word(), 'D' => fake()->word()],
            'correct_choice' => 'A',
            'status' => QuestionStatus::Active,
        ];
    }
}
