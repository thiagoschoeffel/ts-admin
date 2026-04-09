<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateUserPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-user-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing users to include new permissions from config';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $resources = config('permissions.resources', []);
        $users = User::where('role', 'user')->get();

        $this->info("Updating permissions for {$users->count()} users...");

        foreach ($users as $user) {
            $permissions = $user->permissions ?? [];

            $updated = false;
            foreach ($resources as $resourceKey => $resource) {
                $abilities = array_keys($resource['abilities'] ?? []);

                if (!isset($permissions[$resourceKey])) {
                    $permissions[$resourceKey] = [];
                }

                foreach ($abilities as $ability) {
                    if (!isset($permissions[$resourceKey][$ability])) {
                        // Add missing permissions with default value (false for regular users)
                        $permissions[$resourceKey][$ability] = false;
                        $updated = true;
                    }
                }
            }

            if ($updated) {
                $user->update(['permissions' => $permissions]);
                $this->line("Updated user: {$user->email}");
            }
        }

        $this->info('User permissions update completed!');
    }
}
