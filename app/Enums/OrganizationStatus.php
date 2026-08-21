<?php

declare(strict_types=1);

namespace App\Enums;

enum OrganizationStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Pending = 'pending';
    case Archived = 'archived';

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
