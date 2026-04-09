<?php

namespace Database\Seeders;

use App\Models\Opportunity;
use App\Models\User;
use App\Models\Lead;
use App\Models\Client;
use Illuminate\Database\Seeder;

class OpportunitySeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $userIds = User::query()->whereIn('email', ['admin@example.com', 'user@example.com'])->pluck('id');
    $leadIds = Lead::query()->pluck('id');
    $clientIds = Client::query()->pluck('id');

    if ($userIds->isEmpty()) {
      $this->call(UserSeeder::class);
      $userIds = User::query()->whereIn('email', ['admin@example.com', 'user@example.com'])->pluck('id');
    }

    if ($leadIds->isEmpty()) {
      $this->call(LeadSeeder::class);
      $leadIds = Lead::query()->pluck('id');
    }

    if ($clientIds->isEmpty()) {
      $this->call(ClientSeeder::class);
      $clientIds = Client::query()->pluck('id');
    }

    $base = (int) env('SEED_QTD', (int) config('seeding.volumes.opportunities', 0));
    $default = (int) config('seeding.volumes.opportunities', 50);
    $count = $base > 0 ? max(10, (int) round($base * 0.25)) : $default;

    Opportunity::factory()
      ->count($count)
      ->state(fn() => [
        'owner_id' => $userIds->random(),
      ])
      ->create();
  }
}
