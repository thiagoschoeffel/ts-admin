<?php

namespace Database\Seeders;

use App\Models\BlockProduction;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\MoldedProduction;
use App\Models\ProductionPointing;
use App\Models\RawMaterial;
use App\Models\Silo;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        // Ensure prerequisites
        if (RawMaterial::count() === 0) {
            $this->call(RawMaterialSeeder::class);
        }
        if (Silo::count() === 0) {
            $this->call(SiloSeeder::class);
        }

        // Optional: clean tables in isolation when running directly
        if (Schema::hasTable('inventory_movements')) {
            DB::table('inventory_movements')->truncate();
        }
        if (Schema::hasTable('inventory_reservations')) {
            DB::table('inventory_reservations')->truncate();
        }

        // 1) Seed inbound loads for each silo and raw material
        $now = Carbon::now();
        $silos = Silo::all('id');
        $raws = RawMaterial::all('id');
        foreach ($silos as $silo) {
            foreach ($raws as $rm) {
                $qty = mt_rand(2000, 6000); // 2 a 6 toneladas
                InventoryMovement::query()->create([
                    'occurred_at' => $now->copy()->subDays(mt_rand(10, 30)),
                    'item_type' => 'raw_material',
                    'item_id' => $rm->id,
                    'location_type' => 'silo',
                    'location_id' => $silo->id,
                    'direction' => 'in',
                    'quantity' => $qty,
                    'unit' => 'kg',
                    'reference_type' => null,
                    'reference_id' => null,
                    'notes' => 'Abastecimento inicial de silo (seed)',
                    'created_by' => null,
                ]);
            }
        }

        // 2) Create reservations for each production pointing
        $service = app(InventoryService::class);
        ProductionPointing::query()->orderBy('id')->chunk(200, function ($chunk) use ($service) {
            foreach ($chunk as $pp) {
                $service->reserveForProductionPointing($pp);
            }
        });

        // 3) Generate movements for produced blocks and molded
        BlockProduction::query()->orderBy('id')->chunk(200, function ($chunk) use ($service) {
            foreach ($chunk as $bp) {
                $service->syncBlockProduction($bp);
            }
        });
        MoldedProduction::query()->orderBy('id')->chunk(200, function ($chunk) use ($service) {
            foreach ($chunk as $mp) {
                $service->syncMoldedProduction($mp);
            }
        });
    }
}
