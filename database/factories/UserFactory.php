<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $sequence = 0;
        // Reset unique scope for deterministic sequences when seeding repeatedly
        $this->faker->unique(true);

        $statusWeights = config('seeding.weights.status', ['active' => 70, 'inactive' => 30]);
        $status = $this->pickWeighted($statusWeights);

        return [
            'name' => $this->faker->name(),
            'email' => 'user' . (++$sequence) . '@example.com',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'status' => $status,
            'role' => 'user',
            'permissions' => $this->generateRandomPermissions(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Generate random permissions for the user.
     */
    private function generateRandomPermissions(): array
    {
        $resources = config('permissions.resources', []);
        $permissions = [];

        foreach ($resources as $resourceKey => $resource) {
            $abilities = array_keys($resource['abilities'] ?? []);
            $permissions[$resourceKey] = [];
            foreach ($abilities as $ability) {
                $permissions[$resourceKey][$ability] = $this->faker->boolean(70); // 70% chance de ter a permissão
            }
        }

        return $permissions;
    }

    private function pickWeighted(array $weights): string
    {
        $total = array_sum($weights);
        if ($total <= 0) {
            return array_key_first($weights);
        }
        $rand = mt_rand(1, (int) $total);
        $running = 0;
        foreach ($weights as $key => $weight) {
            $running += (int) $weight;
            if ($rand <= $running) {
                return (string) $key;
            }
        }
        return (string) array_key_first($weights);
    }
}
