<?php

declare(strict_types=1);

namespace App\Services\Organizations;

use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Authorization\MembershipAuthorizer;
use App\Services\Authorization\RoleProvisioner;
use Illuminate\Support\Facades\DB;

final readonly class OrganizationCreator
{
    public function __construct(
        private RoleProvisioner $roles,
        private MembershipAuthorizer $authorizer,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: User, 1: Organization}
     */
    public function createForNewOwner(array $attributes): array
    {
        return DB::transaction(function () use ($attributes): array {
            $user = User::query()->create([
                'first_name' => $attributes['first_name'], 'last_name' => $attributes['last_name'],
                'name' => trim($attributes['first_name'].' '.$attributes['last_name']),
                'email' => strtolower($attributes['email']), 'phone' => $attributes['phone'] ?? null,
                'password' => $attributes['password'], 'status' => UserStatus::Active,
            ]);
            $organization = Organization::query()->create([
                'name' => $attributes['organization_name'], 'email' => strtolower($attributes['email']),
                'phone' => $attributes['phone'] ?? null, 'status' => OrganizationStatus::Active,
            ]);
            $this->roles->provisionOrganization($organization);
            $membership = OrganizationMembership::query()->create([
                'organization_id' => $organization->getKey(), 'user_id' => $user->getKey(),
                'status' => MembershipStatus::Active, 'joined_at' => now(),
            ]);
            $admin = Role::query()->where('organization_id', $organization->getKey())
                ->where('name', 'Organization Admin')->sole();
            $this->authorizer->syncRoles($membership, [$admin->getKey()]);
            $this->audit->record('organization.created', $user, $organization, $organization);
            $this->audit->record('membership.created', $user, $organization, $membership, ['role' => $admin->name]);

            return [$user, $organization];
        });
    }
}
