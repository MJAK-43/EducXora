<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\MembershipAuthorizer;
use App\Support\Tenancy\TenantContext;

final readonly class RolePolicy
{
    public function __construct(private TenantContext $tenant, private MembershipAuthorizer $authorizer) {}

    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, 'roles.view');
    }

    public function create(User $user): bool
    {
        return $this->authorizer->allows($user, 'roles.create') && $this->authorizer->allows($user, 'permissions.assign');
    }

    public function update(User $user, Role $role): bool
    {
        return $this->editable($role) && $this->authorizer->allows($user, 'roles.update') && $this->authorizer->allows($user, 'permissions.assign');
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->editable($role) && $this->authorizer->allows($user, 'roles.delete');
    }

    private function editable(Role $role): bool
    {
        return $this->tenant->resolved() && (int) $role->organization_id === $this->tenant->id() && ! $role->is_system;
    }
}
