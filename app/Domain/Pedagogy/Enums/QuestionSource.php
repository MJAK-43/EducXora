<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Enums;

enum QuestionSource: string
{
    case System = 'system';
    case Organization = 'organization';
}
