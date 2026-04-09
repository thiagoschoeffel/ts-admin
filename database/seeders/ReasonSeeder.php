<?php

namespace Database\Seeders;

use App\Models\Reason;
use App\Models\ReasonType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Database\Factories\ReasonFactory;

class ReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Exemplos específicos e idempotentes por tipo
        $examples = [
            'Paradas de Máquina' => [
                ['name' => 'Parada corretiva', 'status' => 'active'],
                ['name' => 'Parada preventiva', 'status' => 'active'],
                ['name' => 'Falha elétrica', 'status' => 'active'],
            ],
            'Refugo de Moldados' => [
                ['name' => 'Defeito de injeção', 'status' => 'active'],
                ['name' => 'Queima de material', 'status' => 'active'],
                ['name' => 'Bolha no produto', 'status' => 'active'],
            ],
            'Refugo de Produção' => [
                ['name' => 'Defeito de montagem', 'status' => 'active'],
                ['name' => 'Peça danificada', 'status' => 'active'],
                ['name' => 'Erro de operador', 'status' => 'active'],
            ],
            'Manutenção Preventiva' => [
                ['name' => 'Troca de óleo', 'status' => 'active'],
                ['name' => 'Verificação de filtros', 'status' => 'active'],
                ['name' => 'Calibração de equipamentos', 'status' => 'active'],
            ],
        ];

        foreach ($examples as $reasonTypeName => $reasons) {
            $reasonType = ReasonType::where('name', $reasonTypeName)->first();
            if (!$reasonType) {
                continue;
            }

            foreach ($reasons as $reason) {
                Reason::updateOrCreate(
                    [
                        'reason_type_id' => $reasonType->id,
                        'name' => $reason['name']
                    ],
                    $reason
                );
            }
        }

        // Adicionar registros aleatórios da factory
        $base = (int) env('SEED_QTD', (int) config('seeding.volumes.reasons', 0));
        $default = (int) config('seeding.volumes.reasons', 20);
        $desired = $base > 0 ? max(5, (int) round($base * 0.3)) : $default;

        $catalog = ReasonFactory::catalog();
        $existingReasons = Reason::query()->select('reason_type_id', 'name')->get();
        $available = [];

        foreach ($catalog as $reasonTypeName => $reasons) {
            $reasonType = ReasonType::where('name', $reasonTypeName)->first();
            if (!$reasonType) {
                continue;
            }

            foreach ($reasons as $reasonName) {
                $exists = $existingReasons->contains(function ($existing) use ($reasonType, $reasonName) {
                    return $existing->reason_type_id === $reasonType->id && $existing->name === $reasonName;
                });

                if (!$exists) {
                    $available[] = [
                        'reason_type_id' => $reasonType->id,
                        'name' => $reasonName,
                    ];
                }
            }
        }

        $availableCount = count($available);
        $target = min($desired, $availableCount);

        if ($this->command && $target < $desired) {
            $this->command->warn(sprintf(
                'ReasonSeeder: solicitados %d motivos, mas apenas %d únicos disponíveis no catálogo (excluindo já existentes). Limitando para evitar duplicidade.',
                $desired,
                $availableCount
            ));
        }

        $selected = array_slice(Arr::shuffle($available), 0, $target);

        foreach ($selected as $reason) {
            Reason::factory()->create($reason);
        }
    }
}
