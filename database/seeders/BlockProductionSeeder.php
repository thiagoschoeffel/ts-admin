<?php

namespace Database\Seeders;

use App\Models\BlockProduction;
use App\Models\BlockType;
use App\Models\Operator;
use App\Models\ProductionPointing;
use App\Models\Silo;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlockProductionSeeder extends Seeder
{
    public function run(): void
    {
        if (BlockType::count() === 0) {
            \Database\Factories\BlockTypeFactory::new()->count(5)->create(['status' => 'active']);
        }
        if (Operator::count() === 0) {
            \App\Models\Operator::factory()->count(5)->create();
        }
        if (Silo::count() === 0) {
            \App\Models\Silo::factory()->count(5)->create();
        }
        if (ProductionPointing::count() === 0) {
            \App\Models\ProductionPointing::factory()->count(5)->create();
        }
        if (User::count() === 0) {
            User::factory()->count(3)->create();
        }

        // Create a few block production entries for recent production pointings
        $pps = ProductionPointing::query()->latest('id')->take(10)->get();
        foreach ($pps as $pp) {
            $count = mt_rand(1, 3);
            BlockProduction::factory()->count($count)->create([
                'production_pointing_id' => $pp->id,
            ]);

            // Criar pelo menos um refugo para demonstração (10% das vezes)
            if (mt_rand(1, 10) === 1) {
                BlockProduction::factory()->scrap()->create([
                    'production_pointing_id' => $pp->id,
                ]);
            }
        }
    }
}
