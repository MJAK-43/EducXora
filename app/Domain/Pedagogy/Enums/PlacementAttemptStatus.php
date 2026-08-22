<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Enums;

enum PlacementAttemptStatus: string
{
    case Started = 'started';
    case Completed = 'completed';
    case Reviewed = 'reviewed';
}
