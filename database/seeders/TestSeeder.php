<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        // Minimal, deterministic dataset for tests
        // Seed RNG and Faker so factories obey determinism
        $seed = (int) config('seeding.seed', 12345);
        mt_srand($seed);
        app(\Faker\Generator::class)->seed($seed);

        Schema::disableForeignKeyConstraints();
        foreach ([
            'order_items', 'orders',
            'product_components', 'products',
            'production_pointing_operator', 'production_pointing_silo', 'production_pointings',
            'raw_materials', 'operators', 'silos',
            'addresses', 'clients',
            'lead_interactions', 'opportunity_items', 'opportunities', 'leads',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }
        Schema::enableForeignKeyConstraints();

        $this->call(UserSeeder::class);

        // Small volumes
        \App\Models\Client::factory()->count(5)->create();
        \App\Models\Product::factory()->count(8)->create(['status' => 'active']);
        \App\Models\Lead::factory()->count(6)->create();
        \App\Models\Order::factory()->count(10)->create();
        \App\Models\Opportunity::factory()->count(6)->create();
    }
}
