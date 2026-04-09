<?php

namespace Database\Seeders;

use App\Models\ReasonType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Database\Factories\ReasonTypeFactory;

class ReasonTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Exemplos específicos e idempotentes
        $examples = [
            ['name' => 'Paradas de Máquina', 'status' => 'active'],
            ['name' => 'Refugo de Moldados', 'status' => 'active'],
            ['name' => 'Refugo de Produção', 'status' => 'active'],
            ['name' => 'Manutenção Preventiva', 'status' => 'active'],
        ];

        foreach ($examples as $example) {
            ReasonType::updateOrCreate(
                ['name' => $example['name']],
                $example
            );
        }

        // Adicionar registros aleatórios da factory
        $base = (int) env('SEED_QTD', (int) config('seeding.volumes.reason_types', 0));
        $default = (int) config('seeding.volumes.reason_types', 10);
        $desired = $base > 0 ? max(3, (int) round($base * 0.2)) : $default;

        $catalog = ReasonTypeFactory::catalog();
        $existingNames = ReasonType::query()->pluck('name')->all();
        $available = array_values(array_filter($catalog, function (array $reasonType) use ($existingNames): bool {
            return !in_array($reasonType['name'], $existingNames, true);
        }));

        $availableCount = count($available);
        $target = min($desired, $availableCount);

        if ($this->command && $target < $desired) {
            $this->command->warn(sprintf(
                'ReasonTypeSeeder: solicitados %d tipos de motivo, mas apenas %d únicos disponíveis no catálogo (excluindo já existentes). Limitando para evitar duplicidade.',
                $desired,
                $availableCount
            ));
        }

        $selected = array_slice(Arr::shuffle($available), 0, $target);

        foreach ($selected as $reasonType) {
            ReasonType::factory()->create($reasonType);
        }
    }
}
