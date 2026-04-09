<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BlockProduction;
use App\Models\BlockType;
use App\Models\Operator;
use App\Models\ProductionPointing;
use App\Models\User;
use App\Models\Silo;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlockProductionFactory extends Factory
{
    protected $model = BlockProduction::class;

    public function definition(): array
    {
        $pp = ProductionPointing::query()->inRandomOrder()->first() ?? ProductionPointing::factory()->create();
        $bt = BlockType::query()->where('status', 'active')->inRandomOrder()->first() ?? BlockType::factory()->create(['status' => 'active']);
        $userId = User::query()->inRandomOrder()->value('id') ?? User::factory()->create()->id;

        // Times within today
        $start = $this->faker->dateTimeBetween('-2 days', 'now');
        $end = (clone $start)->modify('+' . mt_rand(1, 8) . ' hours');

        $customDims = $this->faker->boolean(30);
        $length = $customDims ? $this->faker->numberBetween(3600, 4600) : 4060;
        $width = $customDims ? $this->faker->numberBetween(900, 1200) : 1020;

        return [
            'production_pointing_id' => $pp->id,
            'block_type_id' => $bt->id,
            'started_at' => $start,
            'ended_at' => $end,
            'sheet_number' => $this->faker->numberBetween(1, 9999),
            'weight' => $this->faker->randomFloat(2, 10, 1000),
            'length_mm' => $length,
            'width_mm' => $width,
            'height_mm' => $this->faker->numberBetween(50, 600),
            'dimension_customization_enabled' => $customDims,
            'is_scrap' => $this->faker->boolean(15), // 15% chance de ser refugo
            'created_by_id' => $userId,
            'updated_by_id' => $userId,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (BlockProduction $bp) {
            $ops = Operator::query()->inRandomOrder()->limit(mt_rand(1, 3))->pluck('id')->all();
            $silos = Silo::query()->inRandomOrder()->limit(mt_rand(1, 3))->pluck('id')->all();
            $bp->operators()->sync($ops);
            $bp->silos()->sync($silos);
        });
    }

    /**
     * Indicate that the block production is scrap.
     */
    public function scrap(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_scrap' => true,
        ]);
    }

    /**
     * Indicate that the block production is not scrap.
     */
    public function notScrap(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_scrap' => false,
        ]);
    }
}
