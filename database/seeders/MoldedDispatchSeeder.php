<?php

namespace Database\Seeders;

use App\Models\InventoryMovement;
use App\Models\MoldType;
use App\Models\MoldedDispatch;
use App\Models\MoldedProduction;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MoldedDispatchSeeder extends Seeder
{
    public function run(): void
    {
        if (MoldType::count() === 0) {
            $this->call(MoldTypeSeeder::class);
        }
        if (MoldedProduction::count() === 0) {
            $this->call(MoldedProductionSeeder::class);
        }

        $adminId = User::query()->where('role', 'admin')->value('id');
        $service = app(InventoryService::class);

        $balances = InventoryMovement::query()
            ->where('item_type', 'molded')
            ->whereNotNull('mold_type_id')
            ->select('mold_type_id', DB::raw("
                SUM(CASE
                    WHEN direction = 'in' THEN quantity
                    WHEN direction = 'out' THEN -quantity
                    WHEN direction = 'adjust' THEN quantity
                    ELSE 0
                END) AS balance_units
            "))
            ->groupBy('mold_type_id')
            ->pluck('balance_units', 'mold_type_id')
            ->map(fn ($v) => (int) round((float) $v, 0))
            ->all();

        foreach ($balances as $moldTypeId => $balance) {
            $balance = (int) $balance;
            if ($balance <= 0) {
                continue;
            }

            $dispatchCount = mt_rand(0, 2);
            for ($i = 0; $i < $dispatchCount; $i++) {
                if ($balance <= 0) {
                    break;
                }

                $qty = min($balance, mt_rand(1, min(200, $balance)));
                $dispatch = MoldedDispatch::query()->create([
                    'dispatched_at' => Carbon::now()->subDays(mt_rand(0, 15))->subMinutes(mt_rand(0, 720)),
                    'manufacturing_order_number' => 'OF-' . mt_rand(1000, 9999),
                    'mold_type_id' => (int) $moldTypeId,
                    'quantity' => (int) $qty,
                    'created_by_id' => $adminId,
                    'updated_by_id' => $adminId,
                ]);

                $service->syncMoldedDispatch($dispatch);
                $balance -= $qty;
            }
        }
    }
}

