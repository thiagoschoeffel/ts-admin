<?php

namespace Database\Seeders;

use App\Models\Machine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Database\Factories\MachineFactory;

class MachineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $base = (int) env('SEED_QTD', (int) config('seeding.volumes.machines', 0));
        $default = (int) config('seeding.volumes.machines', 15);
        $desired = $base > 0 ? max(5, (int) round($base * 0.3)) : $default;

        $catalog = MachineFactory::catalog();
        $existingNames = Machine::query()->pluck('name')->all();
        $available = array_values(array_filter($catalog, function (array $machine) use ($existingNames): bool {
            return !in_array($machine['name'], $existingNames, true);
        }));

        $availableCount = count($available);
        $target = min($desired, $availableCount);

        if ($this->command && $target < $desired) {
            $this->command->warn(sprintf(
                'MachineSeeder: solicitados %d máquinas, mas apenas %d únicas disponíveis no catálogo (excluindo já existentes). Limitando para evitar duplicidade.',
                $desired,
                $availableCount
            ));
        }

        $selected = array_slice(Arr::shuffle($available), 0, $target);

        foreach ($selected as $machine) {
            Machine::factory()->create($machine);
        }
    }
}
