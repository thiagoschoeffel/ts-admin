<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'password' => 'password', // cast: hashed
                'status' => 'active',
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Default user (full permissions)
        User::query()->updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Usuário Comum',
                'password' => 'password', // cast: hashed
                'status' => 'active',
                'role' => 'user',
                'permissions' => $this->getAllPermissions(),
                'email_verified_at' => now(),
            ]
        );

        // Ensure all other users are inactive so only the two are active
        User::query()
            ->whereNotIn('email', ['admin@example.com', 'user@example.com'])
            ->update(['status' => 'inactive']);

        // Update existing users to include new permissions
        $this->updateExistingUserPermissions();
    }

    /**
     * Update existing users to include any new permissions from config.
     */
    private function updateExistingUserPermissions(): void
    {
        $resources = config('permissions.resources', []);
        $users = User::where('role', 'user')->get();

        foreach ($users as $user) {
            $permissions = $user->permissions ?? [];

            foreach ($resources as $resourceKey => $resource) {
                $abilities = array_keys($resource['abilities'] ?? []);

                if (!isset($permissions[$resourceKey])) {
                    $permissions[$resourceKey] = [];
                }

                foreach ($abilities as $ability) {
                    if (!isset($permissions[$resourceKey][$ability])) {
                        // Add missing permissions with default value (false for regular users)
                        $permissions[$resourceKey][$ability] = false;
                    }
                }
            }

            $user->update(['permissions' => $permissions]);
        }
    }

    /**
     * Get all permissions set to true for a user with full access.
     */
    private function getAllPermissions(): array
    {
        $resources = config('permissions.resources', []);
        $permissions = [];

        foreach ($resources as $resourceKey => $resource) {
            $abilities = array_keys($resource['abilities'] ?? []);
            $permissions[$resourceKey] = array_fill_keys($abilities, true);
        }

        return $permissions;
    }
}
