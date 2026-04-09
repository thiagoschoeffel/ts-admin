<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MoldedProduction;
use App\Models\MoldType;
use App\Models\Operator;
use App\Models\ProductionPointing;
use App\Models\User;
use App\Models\Silo;
use Illuminate\Database\Eloquent\Factories\Factory;

class MoldedProductionFactory extends Factory
{
    protected $model = MoldedProduction::class;

    public function definition(): array
    {
        $pp = ProductionPointing::query()->inRandomOrder()->first() ?? ProductionPointing::factory()->create();
        $mt = MoldType::query()->inRandomOrder()->first() ?? \App\Models\MoldType::factory()->create();
        $userId = User::query()->inRandomOrder()->value('id') ?? User::factory()->create()->id;

        $start = $this->faker->dateTimeBetween('-2 days', 'now');
        $end = (clone $start)->modify('+' . mt_rand(1, 6) . ' hours');

        $quantity = $this->faker->numberBetween(1, 200);
        $packageQty = (int) round((float) ($mt->pieces_per_package ?? $this->faker->numberBetween(1, 50)));
        $packageWeight = $this->faker->randomFloat(2, 5, 500); // kg
        $perUnit = $packageWeight / max(1, $packageQty);
        $lossEnabled = $this->faker->boolean(30);
        $lossFactor = $lossEnabled ? $this->faker->randomFloat(2, 0.10, 0.60) : 0.42;
        $weightConsideredUnit = $perUnit - ($perUnit * $lossFactor);
        $totalWeightConsidered = $quantity * $weightConsideredUnit;

        return [
            'production_pointing_id' => $pp->id,
            'mold_type_id' => $mt->id,
            'started_at' => $start,
            'ended_at' => $end,
            'sheet_number' => $this->faker->numberBetween(1, 9999),
            'quantity' => $quantity,
            'package_weight' => $packageWeight,
            'package_quantity' => $packageQty,
            'loss_factor_enabled' => $lossEnabled,
            'loss_factor' => $lossFactor,
            'weight_considered_unit' => $weightConsideredUnit,
            'total_weight_considered' => $totalWeightConsidered,
            'created_by_id' => $userId,
            'updated_by_id' => $userId,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (MoldedProduction $mp) {
            $ops = Operator::query()->inRandomOrder()->limit(mt_rand(1, 3))->pluck('id')->all();
            $silos = Silo::query()->inRandomOrder()->limit(mt_rand(1, 3))->pluck('id')->all();
            $mp->operators()->sync($ops);
            $mp->silos()->sync($silos);

            // Gerar scraps aleatórios
            $reasons = \App\Models\Reason::query()->inRandomOrder()->limit(5)->pluck('id')->all();
            $scrapCount = mt_rand(0, 3);
            for ($i = 0; $i < $scrapCount; $i++) {
                $mp->scraps()->create([
                    'reason_id' => $this->faker->randomElement($reasons),
                    'quantity' => $this->faker->numberBetween(1, 20),
                ]);
            }
        });
    }
}
