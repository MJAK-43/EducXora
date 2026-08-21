<?php

declare(strict_types=1);

namespace App\Domain\Learner\Enums;

enum LearnerLanguage: string
{
    case German = 'de';

    public function label(): string
    {
        return 'Allemand';
    }
}
