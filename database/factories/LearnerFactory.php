<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Learner\Enums\LearnerLanguage;
use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learner\Enums\LearnerStatus;
use App\Domain\Learner\Models\Learner;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Learner> */
final class LearnerFactory extends Factory
{
    protected $model = Learner::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-8 years')->format('Y-m-d'),
            'phone' => '+2376'.fake()->numerify('########'),
            'email' => fake()->optional()->safeEmail(),
            'language' => LearnerLanguage::German,
            'initial_level' => fake()->randomElement(LearnerLevel::cases()),
            'registered_on' => now()->toDateString(),
            'status' => LearnerStatus::Active,
        ];
    }
}
