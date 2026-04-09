<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = User::query()->whereIn('email', ['admin@example.com', 'user@example.com'])->pluck('id');
        $clientIds = \App\Models\Client::query()->pluck('id');

        if ($userIds->isEmpty()) {
            $this->call(UserSeeder::class);
            $userIds = User::query()->whereIn('email', ['admin@example.com', 'user@example.com'])->pluck('id');
        }

        if ($clientIds->isEmpty()) {
            $this->call(ClientSeeder::class);
            $clientIds = \App\Models\Client::query()->pluck('id');
        }

        $base = (int) env('SEED_QTD', (int) config('seeding.volumes.orders', 0));
        $default = (int) config('seeding.volumes.orders', 250);
        $count = $base > 0 ? max(10, (int) round($base * 1.2)) : $default;

        Order::factory()
            ->count($count)
            ->state(fn() => [
                'client_id' => $clientIds->random(),
                'user_id' => $userIds->random(),
                'created_by_id' => $userIds->random(),
                'updated_by_id' => fake()->boolean(40) ? $userIds->random() : null,
            ])
            ->create();
    }
}
