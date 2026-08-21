<?php

declare(strict_types=1);

namespace App\Support\Clock;

use Carbon\CarbonImmutable;

final class SystemClock implements Clock
{
    public function now(?string $timezone = null): CarbonImmutable
    {
        return CarbonImmutable::now($timezone);
    }
}
