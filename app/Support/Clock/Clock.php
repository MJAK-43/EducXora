<?php

declare(strict_types=1);

namespace App\Support\Clock;

use Carbon\CarbonImmutable;

interface Clock
{
    public function now(?string $timezone = null): CarbonImmutable;
}
