<?php

declare(strict_types=1);

namespace App\Services\Authorization;

use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final readonly class MembershipAuthorizer
{
    public function __construct(private TenantContext $tenant) {}

    public function allows(User $user, string $permission): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $membership = $this->tenant->membership();

        if (! $membership || (int) $membership->user_id !== (int) $user->getKey()
            || ! $membership->isActive()) {
            return false;
        }

        return $membership->roles()
            ->whereHas('permissions', fn ($query) => $query->where('name', $permission))
            ->exists();
    }

    /** @return Collection<int, string> */
    public function permissions(User $user): Collection
    {
        if ($user->isSuperAdmin()) {
            /** @var array<int, string> $permissions */
            $permissions = config('authorization.permissions', []);

            return collect($permissions);
        }

        $membership = $this->tenant->membership();
        if (! $membership || (int) $membership->user_id !== (int) $user->getKey()) {
            return collect();
        }

        return $membership->roles()->with('permissions')->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('name'))
            ->unique()->values();
    }

    /** @param array<int, int|string> $roleIds */
    public function syncRoles(OrganizationMembership $membership, array $roleIds): void
    {
        $roles = Role::query()
            ->where('organization_id', $membership->organization_id)
            ->whereIn('id', $roleIds)
            ->get();

        if ($roles->count() !== count(array_unique($roleIds))) {
            throw ValidationException::withMessages(['roles' => 'Un ou plusieurs rôles sont invalides pour cette organisation.']);
        }

        $payload = $roles->mapWithKeys(fn (Role $role): array => [
            $role->getKey() => ['organization_id' => $membership->organization_id],
        ])->all();

        $membership->roles()->sync($payload);
    }
}
