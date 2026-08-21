<?php

declare(strict_types=1);

namespace App\Domain\Learner\Enums;

enum LearnerLevel: string
{
    case A1 = 'A1';
    case A2 = 'A2';
    case B1 = 'B1';
    case B2 = 'B2';
    case C1 = 'C1';
    case C2 = 'C2';
}
