<?php

namespace Database\Seeders;

use App\Models\MoldType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Database\Factories\MoldTypeFactory;

class MoldTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $base = (int) env('SEED_QTD', (int) config('seeding.volumes.mold_types', 0));
        $default = (int) config('seeding.volumes.mold_types', 10);
        $desired = $base > 0 ? max(5, (int) round($base * 0.2)) : $default;

        $catalog = MoldTypeFactory::catalog();
        $existingNames = MoldType::query()->pluck('name')->all();
        $available = array_values(array_filter($catalog, function (array $moldType) use ($existingNames): bool {
            return !in_array($moldType['name'], $existingNames, true);
        }));

        $availableCount = count($available);
        $target = min($desired, $availableCount);

        if ($this->command && $target < $desired) {
            $this->command->warn(sprintf(
                'MoldTypeSeeder: solicitados %d tipos de moldado, mas apenas %d únicos disponíveis no catálogo (excluindo já existentes). Limitando para evitar duplicidade.',
                $desired,
                $availableCount
            ));
        }

        $selected = array_slice(Arr::shuffle($available), 0, $target);

        foreach ($selected as $moldType) {
            MoldType::factory()->create($moldType);
        }
    }
}