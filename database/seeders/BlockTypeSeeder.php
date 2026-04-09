<?php

namespace Database\Seeders;

use App\Models\BlockType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Database\Factories\BlockTypeFactory;

class BlockTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $base = (int) env('SEED_QTD', (int) config('seeding.volumes.block_types', 0));
        $default = (int) config('seeding.volumes.block_types', 10);
        $desired = $base > 0 ? max(5, (int) round($base * 0.2)) : $default;

        $catalog = BlockTypeFactory::catalog();
        $existingNames = BlockType::query()->pluck('name')->all();
        $available = array_values(array_filter($catalog, function (array $blockType) use ($existingNames): bool {
            return !in_array($blockType['name'], $existingNames, true);
        }));

        $availableCount = count($available);
        $target = min($desired, $availableCount);

        if ($this->command && $target < $desired) {
            $this->command->warn(sprintf(
                'BlockTypeSeeder: solicitados %d tipos de bloco, mas apenas %d únicos disponíveis no catálogo (excluindo já existentes). Limitando para evitar duplicidade.',
                $desired,
                $availableCount
            ));
        }

        $selected = array_slice(Arr::shuffle($available), 0, $target);

        foreach ($selected as $blockType) {
            BlockType::factory()->create($blockType);
        }
    }
}