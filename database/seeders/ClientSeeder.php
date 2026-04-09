<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = User::query()->whereIn('email', ['admin@example.com', 'user@example.com'])->pluck('id');

        if ($userIds->isEmpty()) {
            $this->call(UserSeeder::class);
            $userIds = User::query()->whereIn('email', ['admin@example.com', 'user@example.com'])->pluck('id');
        }

        // Use env/config for volume scaling to avoid unknown CLI option errors
        $base = (int) env('SEED_QTD', (int) config('seeding.volumes.clients', 0));
        $default = (int) config('seeding.volumes.clients', 50);
        $count = $base > 0 ? max(10, (int) round($base * 0.25)) : $default;

        Client::factory()
            ->count($count)
            ->state(fn() => [
                'created_by_id' => $userIds->random(),
                'updated_by_id' => fake()->boolean(40) ? $userIds->random() : null,
            ])
            ->create();
    }
}
