<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;

final readonly class OrganizationPolicy
{
    public function __construct(private TenantContext $tenant, private MembershipAuthorizer $authorizer) {}

    public function view(User $user, Organization $organization): bool
    {
        return $this->sameTenant($organization) && $this->authorizer->allows($user, 'organization.view');
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->sameTenant($organization) && $this->authorizer->allows($user, 'organization.update');
    }

    private function sameTenant(Organization $organization): bool
    {
        return $this->tenant->resolved() && $this->tenant->organization()->is($organization);
    }
}
