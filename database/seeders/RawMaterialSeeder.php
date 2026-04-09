<?php

namespace Database\Seeders;

use App\Models\RawMaterial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Database\Factories\RawMaterialFactory;

class RawMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $base = (int) env('SEED_QTD', (int) config('seeding.volumes.raw_materials', 0));
        $default = (int) config('seeding.volumes.raw_materials', 10);
        $desired = $base > 0 ? max(5, (int) round($base * 0.2)) : $default;

        $catalog = RawMaterialFactory::catalog();
        $existingNames = RawMaterial::query()->pluck('name')->all();
        $available = array_values(array_filter($catalog, function (array $rawMaterial) use ($existingNames): bool {
            return !in_array($rawMaterial['name'], $existingNames, true);
        }));

        $availableCount = count($available);
        $target = min($desired, $availableCount);

        if ($this->command && $target < $desired) {
            $this->command->warn(sprintf(
                'RawMaterialSeeder: solicitados %d matérias-primas, mas apenas %d únicas disponíveis no catálogo (excluindo já existentes). Limitando para evitar duplicidade.',
                $desired,
                $availableCount
            ));
        }

        $selected = array_slice(Arr::shuffle($available), 0, $target);

        foreach ($selected as $rawMaterial) {
            RawMaterial::factory()->create($rawMaterial);
        }
    }
}