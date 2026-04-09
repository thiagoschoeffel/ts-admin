<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Operator;
use App\Models\ProductionPointing;
use App\Models\RawMaterial;
use App\Models\Silo;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ProductionPointingFactory extends Factory
{
    protected $model = ProductionPointing::class;

    public function definition(): array
    {
        $this->faker->unique(true);
        $locale = config('seeding.faker_locale', config('app.faker_locale'));
        $faker = $locale === 'pt_BR' ? fake('pt_BR') : $this->faker;

        $statusWeights = config('seeding.weights.status', ['active' => 85, 'inactive' => 15]);
        $status = $this->pickWeighted($statusWeights);

        $startedAt = $faker->dateTimeBetween('-10 days', 'now');
        $endedAt = (clone $startedAt)->modify('+' . $faker->numberBetween(1, 8) . ' hours');

        return [
            'status' => $status,
            'sheet_number' => $faker->unique()->numberBetween(1, 99999),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'raw_material_id' => $this->rawMaterialId(),
            'quantity' => $faker->randomFloat(2, 10, 500),
            'created_by' => $this->existingUserId(),
            'updated_by' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (ProductionPointing $productionPointing): void {
            $operatorIds = Operator::query()->inRandomOrder()->limit(mt_rand(1, 3))->pluck('id')->all();
            if (empty($operatorIds)) {
                $sector = Sector::query()->first();
                if (!$sector) {
                    $sector = Sector::factory()->create(['name' => 'Setor Industrial ' . Str::uuid()]);
                }

                Operator::factory()->count(3)->create(['sector_id' => $sector->id]);
                $operatorIds = Operator::query()->inRandomOrder()->limit(mt_rand(1, 3))->pluck('id')->all();
            }

            $siloIds = Silo::query()->inRandomOrder()->limit(mt_rand(1, 2))->pluck('id')->all();
            if (empty($siloIds)) {
                if (Silo::count() === 0) {
                    $siloBaseA = Silo::factory()->create(['name' => 'Silo Base ' . Str::uuid()]);
                    $siloBaseB = Silo::factory()->create(['name' => 'Silo Auxiliar ' . Str::uuid()]);
                    $siloIds = collect([$siloBaseA->id, $siloBaseB->id])->all();
                } else {
                    $siloIds = Silo::query()->inRandomOrder()->limit(mt_rand(1, 2))->pluck('id')->all();
                }
            }

            $productionPointing->operators()->sync($operatorIds);
            $productionPointing->silos()->sync($siloIds);
        });
    }

    private function existingUserId(): int
    {
        $ids = User::query()
            ->whereIn('email', ['admin@example.com', 'user@example.com'])
            ->pluck('id')
            ->all();

        if (!empty($ids)) {
            return Arr::random($ids);
        }

        $userId = User::query()->inRandomOrder()->value('id');
        if ($userId) {
            return (int) $userId;
        }

        return 1;
    }

    private function pickWeighted(array $weights): string
    {
        $total = array_sum($weights);
        if ($total <= 0) {
            return array_key_first($weights);
        }
        $rand = mt_rand(1, (int) $total);
        $running = 0;
        foreach ($weights as $key => $weight) {
            $running += (int) $weight;
            if ($rand <= $running) {
                return (string) $key;
            }
        }
        return (string) array_key_first($weights);
    }

    private function rawMaterialId(): int
    {
        $id = RawMaterial::query()->inRandomOrder()->value('id');
        if ($id) {
            return (int) $id;
        }

        return RawMaterial::factory()->create()->id;
    }
}
