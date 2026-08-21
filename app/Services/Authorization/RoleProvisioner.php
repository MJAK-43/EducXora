<?php

declare(strict_types=1);

namespace App\Services\Authorization;

use App\Models\Organization;
use App\Models\Role;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final readonly class RoleProvisioner
{
    public function provisionPlatform(): Role
    {
        $permissions = $this->permissions();
        $role = Role::query()->firstOrCreate(
            ['organization_id' => null, 'name' => 'Super Admin', 'guard_name' => 'web'],
            ['is_system' => true],
        );
        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role;
    }

    public function provisionOrganization(Organization $organization): void
    {
        $permissions = $this->permissions()->keyBy('name');

        foreach (config('authorization.roles') as $name => $names) {
            $role = Role::query()->firstOrCreate(
                ['organization_id' => $organization->getKey(), 'name' => $name, 'guard_name' => 'web'],
                ['is_system' => true],
            );
            $selected = $names === ['*'] ? $permissions : $permissions->only($names);
            $role->syncPermissions($selected->values());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @return Collection<int, Permission> */
    private function permissions(): Collection
    {
        /** @var array<int, string> $permissionNames */
        $permissionNames = config('authorization.permissions');

        return collect($permissionNames)
            ->map(fn (string $name): Permission => Permission::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]));
    }
}
