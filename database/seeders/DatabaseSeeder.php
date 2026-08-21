<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\RoleProvisioner;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $superAdminRole = app(RoleProvisioner::class)->provisionPlatform();

        Organization::query()->each(
            fn (Organization $organization) => app(RoleProvisioner::class)->provisionOrganization($organization),
        );
        if (app()->environment(['local', 'testing'])) {
            $user = User::query()->firstOrCreate(
                ['email' => 'admin@eduxora.local'],
                [
                    'name' => 'Admin EduXora', 'first_name' => 'Admin', 'last_name' => 'EduXora',
                    'password' => 'password', 'email_verified_at' => now(), 'status' => UserStatus::Active,
                ],
            );
            $user->assignRole($superAdminRole);
        }
    }
}
