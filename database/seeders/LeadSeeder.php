<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\User;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (User::count() === 0) {
            $this->call(UserSeeder::class);
        }

        $base = (int) env('SEED_QTD', (int) config('seeding.volumes.leads', 0));
        $default = (int) config('seeding.volumes.leads', 30);
        $count = $base > 0 ? max(10, (int) round($base * 0.3)) : $default;

        Lead::factory()->count($count)->create();
    }
}
