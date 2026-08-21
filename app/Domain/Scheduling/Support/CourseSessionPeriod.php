<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Support;

use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final readonly class CourseSessionPeriod
{
    public function __construct(private TenantContext $tenant) {}

    /** @param array<string, mixed> $attributes
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function fromInput(array $attributes): array
    {
        $timezone = $this->tenant->organization()->timezone;
        $startsAt = CarbonImmutable::createFromFormat('!Y-m-d H:i', $attributes['date'].' '.$attributes['start_time'], $timezone);
        $endsAt = CarbonImmutable::createFromFormat('!Y-m-d H:i', $attributes['date'].' '.$attributes['end_time'], $timezone);
        if (! $startsAt || ! $endsAt || $endsAt->lessThanOrEqualTo($startsAt)) {
            throw ValidationException::withMessages(['end_time' => 'L’heure de fin doit être postérieure à l’heure de début.']);
        }

        return [$startsAt->utc(), $endsAt->utc()];
    }
}
