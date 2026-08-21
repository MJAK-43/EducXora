<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Services\Authorization\RoleProvisioner;

trait BuildsPhaseTwoTenancy
{
    /** @return array{0: Organization, 1: User, 2: OrganizationMembership} */
    protected function tenantWithUser(string $roleName = 'Organization Admin'): array
    {
        $organization = Organization::factory()->create();
        app(RoleProvisioner::class)->provisionOrganization($organization);
        $user = User::factory()->create();
        $membership = OrganizationMembership::query()->create([
            'organization_id' => $organization->getKey(),
            'user_id' => $user->getKey(),
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);
        $role = Role::query()->where('organization_id', $organization->getKey())->where('name', $roleName)->sole();
        app(MembershipAuthorizer::class)->syncRoles($membership, [$role->getKey()]);

        return [$organization, $user, $membership];
    }

    /** @return array<string, int|string> */
    protected function tenantSession(Organization $organization): array
    {
        return [
            'authenticated_at' => now()->timestamp,
            'auth.password_confirmed_at' => now()->timestamp,
            'active_organization_uuid' => $organization->uuid,
        ];
    }
}
