<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Organization> */
final class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'email' => fake()->companyEmail(),
            'country_code' => 'CM',
            'timezone' => 'Africa/Douala',
            'currency' => 'XAF',
            'locale' => 'fr',
            'status' => OrganizationStatus::Active,
            'settings' => [],
        ];
    }
}
