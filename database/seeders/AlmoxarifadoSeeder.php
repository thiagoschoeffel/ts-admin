<?php

namespace Database\Seeders;

use App\Models\Almoxarifado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Database\Factories\AlmoxarifadoFactory;

class AlmoxarifadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $base = (int) env('SEED_QTD', (int) config('seeding.volumes.almoxarifados', 0));
        $default = (int) config('seeding.volumes.almoxarifados', 10);
        $desired = $base > 0 ? max(5, (int) round($base * 0.2)) : $default;

        $catalog = AlmoxarifadoFactory::catalog();
        $existingNames = Almoxarifado::query()->pluck('name')->all();
        $available = array_values(array_filter($catalog, function (array $almoxarifado) use ($existingNames): bool {
            return !in_array($almoxarifado['name'], $existingNames, true);
        }));

        $availableCount = count($available);
        $target = min($desired, $availableCount);

        if ($this->command && $target < $desired) {
            $this->command->warn(sprintf(
                'AlmoxarifadoSeeder: solicitados %d almoxarifados, mas apenas %d únicos disponíveis no catálogo (excluindo já existentes). Limitando para evitar duplicidade.',
                $desired,
                $availableCount
            ));
        }

        $selected = array_slice(Arr::shuffle($available), 0, $target);

        foreach ($selected as $almoxarifado) {
            Almoxarifado::factory()->create($almoxarifado);
        }
    }
}