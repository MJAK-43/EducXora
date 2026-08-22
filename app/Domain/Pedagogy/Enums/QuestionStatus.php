<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Enums;

enum QuestionStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
