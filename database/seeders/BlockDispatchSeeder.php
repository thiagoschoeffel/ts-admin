<?php

namespace Database\Seeders;

use App\Models\BlockDispatch;
use App\Models\BlockDispatchItem;
use App\Models\BlockProduction;
use App\Models\ProductionPointing;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BlockDispatchSeeder extends Seeder
{
    public function run(): void
    {
        if (ProductionPointing::count() === 0) {
            $this->call(ProductionPointingSeeder::class);
        }
        if (BlockProduction::count() === 0) {
            $this->call(BlockProductionSeeder::class);
        }

        $adminId = User::query()->where('role', 'admin')->value('id');
        $service = app(InventoryService::class);

        $pps = ProductionPointing::query()->latest('id')->take(10)->get(['id']);
        foreach ($pps as $pp) {
            $availableBlocks = BlockProduction::query()
                ->where('production_pointing_id', $pp->id)
                ->where('is_scrap', false)
                ->whereNotIn('id', function ($q) {
                    $q->select('block_production_id')->from('block_dispatch_items');
                })
                ->inRandomOrder()
                ->limit(mt_rand(1, 3))
                ->get(['id']);

            if ($availableBlocks->isEmpty()) {
                continue;
            }

            $dispatch = BlockDispatch::query()->create([
                'dispatched_at' => Carbon::now()->subDays(mt_rand(0, 15))->subMinutes(mt_rand(0, 720)),
                'manufacturing_order_number' => 'OF-' . mt_rand(1000, 9999),
                'production_pointing_id' => $pp->id,
                'created_by_id' => $adminId,
                'updated_by_id' => $adminId,
            ]);

            $now = now();
            $rows = $availableBlocks->map(fn ($b) => [
                'block_dispatch_id' => $dispatch->id,
                'block_production_id' => (int) $b->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            BlockDispatchItem::query()->insert($rows);

            $service->syncBlockDispatch($dispatch);
        }
    }
}

