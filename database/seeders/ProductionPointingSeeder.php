<?php

namespace Database\Seeders;

use App\Models\Operator;
use App\Models\ProductionPointing;
use App\Models\RawMaterial;
use App\Models\Silo;
use Illuminate\Database\Seeder;

class ProductionPointingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (RawMaterial::count() === 0) {
            RawMaterial::factory()->count(10)->create();
        }

        if (Operator::count() === 0) {
            Operator::factory()->count(5)->create();
        }

        if (Silo::count() === 0) {
            Silo::factory()->count(5)->create();
        }

        $base = (int) env('SEED_QTD', (int) config('seeding.volumes.production_pointings', 0));
        $default = (int) config('seeding.volumes.production_pointings', 10);
        $desired = $base > 0 ? max(5, (int) round($base * 0.2)) : $default;

        $target = $desired;

        ProductionPointing::factory()->count($target)->create();
    }
}
