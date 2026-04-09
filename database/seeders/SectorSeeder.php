<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Database\Factories\SectorFactory;

class SectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $base = (int) env('SEED_QTD', (int) config('seeding.volumes.sectors', 0));
        $default = (int) config('seeding.volumes.sectors', 10);
        $desired = $base > 0 ? max(5, (int) round($base * 0.2)) : $default;

        $catalog = SectorFactory::catalog();
        $existingNames = Sector::query()->pluck('name')->all();
        $available = array_values(array_filter($catalog, function (array $sector) use ($existingNames): bool {
            return !in_array($sector['name'], $existingNames, true);
        }));

        $availableCount = count($available);
        $target = min($desired, $availableCount);

        if ($this->command && $target < $desired) {
            $this->command->warn(sprintf(
                'SectorSeeder: solicitados %d setores, mas apenas %d únicos disponíveis no catálogo (excluindo já existentes). Limitando para evitar duplicidade.',
                $desired,
                $availableCount
            ));
        }

        $selected = array_slice(Arr::shuffle($available), 0, $target);

        foreach ($selected as $sector) {
            Sector::factory()->create($sector);
        }
    }
}
