<?php

declare(strict_types=1);

namespace App\Domain\Learning\Enums;

enum GroupStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
}
