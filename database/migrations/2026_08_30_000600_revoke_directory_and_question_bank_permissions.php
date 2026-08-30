<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, array<int, string>> */
    private const REVOKED_PERMISSIONS = [
        'Teacher/Trainer' => ['users.view', 'placement_questions.view'],
        'Staff' => ['placement_questions.view'],
    ];

    public function up(): void
    {
        foreach (self::REVOKED_PERMISSIONS as $roleName => $permissionNames) {
            DB::table('role_has_permissions')
                ->whereIn('role_id', DB::table('roles')
                    ->select('id')
                    ->where('name', $roleName)
                    ->where('is_system', true)
                    ->whereNotNull('organization_id'))
                ->whereIn('permission_id', DB::table('permissions')
                    ->select('id')
                    ->whereIn('name', $permissionNames)
                    ->where('guard_name', 'web'))
                ->delete();
        }
    }

    public function down(): void
    {
        foreach (self::REVOKED_PERMISSIONS as $roleName => $permissionNames) {
            $roleIds = DB::table('roles')
                ->where('name', $roleName)
                ->where('is_system', true)
                ->whereNotNull('organization_id')
                ->pluck('id');
            $permissionIds = DB::table('permissions')
                ->whereIn('name', $permissionNames)
                ->where('guard_name', 'web')
                ->pluck('id');

            foreach ($roleIds as $roleId) {
                foreach ($permissionIds as $permissionId) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $permissionId,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }
    }
};
