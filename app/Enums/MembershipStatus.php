<?php

declare(strict_types=1);

namespace App\Enums;

enum MembershipStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
