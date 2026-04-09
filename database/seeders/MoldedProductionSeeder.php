<?php

namespace Database\Seeders;

use App\Models\MoldType;
use App\Models\Operator;
use App\Models\ProductionPointing;
use App\Models\Silo;
use App\Models\MoldedProduction;
use App\Models\User;
use Illuminate\Database\Seeder;

class MoldedProductionSeeder extends Seeder
{
    public function run(): void
    {
        if (MoldType::count() === 0) {
            \App\Models\MoldType::factory()->count(5)->create();
        }
        if (Operator::count() === 0) {
            Operator::factory()->count(5)->create();
        }
        if (Silo::count() === 0) {
            Silo::factory()->count(5)->create();
        }
        if (ProductionPointing::count() === 0) {
            ProductionPointing::factory()->count(5)->create();
        }
        if (User::count() === 0) {
            User::factory()->count(3)->create();
        }

        $pps = ProductionPointing::query()->latest('id')->take(10)->get();
        foreach ($pps as $pp) {
            MoldedProduction::factory()->count(mt_rand(1, 3))->create([
                'production_pointing_id' => $pp->id,
            ]);
        }
    }
}
