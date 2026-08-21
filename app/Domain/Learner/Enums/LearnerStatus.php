<?php

declare(strict_types=1);

namespace App\Domain\Learner\Enums;

enum LearnerStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
}
