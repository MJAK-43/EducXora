<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Enums;

enum CourseSessionStatus: string
{
    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';
}
