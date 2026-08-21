<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Enums;

enum AttendanceSheetStatus: string
{
    case Draft = 'draft';
    case Validated = 'validated';
}
