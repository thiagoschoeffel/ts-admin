<?php

namespace App\Http\Middleware;

use Inertia\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user && $user->role === 'user') {
            // Ensure user permissions include all abilities from config
            $this->ensureUserPermissionsAreComplete($user);
        }

        return array_merge(parent::share($request), [
            'app' => [
                'name' => config('app.name', 'Example App'),
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'permissions' => $user->permissions,
                ] : null,
            ],
            'flash' => [
                'status' => fn() => session('status'),
                'success' => fn() => session('success'),
                'error' => fn() => session('error'),
                'flash_id' => fn() => session('flash_id'),
            ],
        ]);
    }

    /**
     * Ensure user permissions include all abilities from the current config.
     */
    private function ensureUserPermissionsAreComplete($user): void
    {
        $resources = config('permissions.resources', []);
        $permissions = $user->permissions ?? [];

        $updated = false;
        foreach ($resources as $resourceKey => $resource) {
            $abilities = array_keys($resource['abilities'] ?? []);

            if (!isset($permissions[$resourceKey])) {
                $permissions[$resourceKey] = [];
            }

            foreach ($abilities as $ability) {
                if (!isset($permissions[$resourceKey][$ability])) {
                    $permissions[$resourceKey][$ability] = false;
                    $updated = true;
                }
            }
        }

        if ($updated) {
            $user->update(['permissions' => $permissions]);
        }
    }
}
