<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use LogicException;

final class TenantContext
{
    private ?Organization $organization = null;

    private ?OrganizationMembership $membership = null;

    public function set(Organization $organization, ?OrganizationMembership $membership = null): void
    {
        $this->organization = $organization;
        $this->membership = $membership;
    }

    public function clear(): void
    {
        $this->organization = null;
        $this->membership = null;
    }

    public function resolved(): bool
    {
        return $this->organization !== null;
    }

    public function organization(): Organization
    {
        return $this->organization ?? throw new LogicException('No tenant organization has been resolved.');
    }

    public function membership(): ?OrganizationMembership
    {
        return $this->membership;
    }

    public function id(): int
    {
        return (int) $this->organization()->getKey();
    }
}
