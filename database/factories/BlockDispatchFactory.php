<?php

namespace Database\Factories;

use App\Models\BlockDispatch;
use App\Models\BlockDispatchItem;
use App\Models\BlockProduction;
use App\Models\ProductionPointing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockDispatch>
 */
class BlockDispatchFactory extends Factory
{
    protected $model = BlockDispatch::class;

    public function definition(): array
    {
        return [
            'dispatched_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'manufacturing_order_number' => 'OF-' . $this->faker->bothify('#####'),
            'production_pointing_id' => ProductionPointing::factory(),
            'created_by_id' => null,
            'updated_by_id' => null,
        ];
    }

    public function withItems(int $count = 2): static
    {
        return $this->afterCreating(function (BlockDispatch $dispatch) use ($count) {
            $ppId = (int) $dispatch->production_pointing_id;

            $blocks = BlockProduction::query()
                ->where('production_pointing_id', $ppId)
                ->where('is_scrap', false)
                ->limit(max(1, $count))
                ->get(['id']);

            if ($blocks->count() < $count) {
                $missing = $count - $blocks->count();
                $created = BlockProduction::factory()
                    ->count($missing)
                    ->notScrap()
                    ->create(['production_pointing_id' => $ppId]);

                $blocks = $blocks->concat($created);
            }

            $ids = $blocks->pluck('id')->take($count)->map(fn ($v) => (int) $v)->values();
            $now = now();
            $rows = $ids->map(fn (int $id) => [
                'block_dispatch_id' => $dispatch->id,
                'block_production_id' => $id,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            BlockDispatchItem::query()->insert($rows);
        });
    }
}
