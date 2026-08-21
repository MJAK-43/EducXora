<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Learner\Enums\LearnerLanguage;
use App\Domain\Learner\Enums\LearnerLevel;
use App\Domain\Learning\Enums\GroupStatus;
use App\Domain\Learning\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Group> */
final class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name' => 'Groupe '.fake()->unique()->bothify('??-##'),
            'language' => LearnerLanguage::German,
            'level' => fake()->randomElement(LearnerLevel::cases()),
            'capacity' => 20,
            'status' => GroupStatus::Active,
        ];
    }
}
