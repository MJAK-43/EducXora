<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Scheduling\Enums\CourseSessionStatus;
use App\Domain\Scheduling\Models\CourseSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CourseSession> */
final class CourseSessionFactory extends Factory
{
    protected $model = CourseSession::class;

    public function definition(): array
    {
        $startsAt = now()->addDays(fake()->numberBetween(1, 20))->setTime(fake()->numberBetween(8, 16), 0);

        return [
            'room' => fake()->optional()->randomElement(['Salle A', 'Salle B']),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(90),
            'status' => CourseSessionStatus::Scheduled,
        ];
    }
}
