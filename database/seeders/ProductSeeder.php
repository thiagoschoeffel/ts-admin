<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Database\Factories\ProductFactory;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = User::query()->whereIn('email', ['admin@example.com', 'user@example.com'])->pluck('id');

        if ($userIds->isEmpty()) {
            $this->call(UserSeeder::class);
            $userIds = User::query()->whereIn('email', ['admin@example.com', 'user@example.com'])->pluck('id');
        }

        $base = (int) env('SEED_QTD', (int) config('seeding.volumes.products', 0));
        $default = (int) config('seeding.volumes.products', 80);
        $desired = $base > 0 ? max(10, (int) round($base * 0.4)) : $default;

        // Use catálogo determinístico para evitar duplicidades
        $catalog = ProductFactory::catalog();
        $existingNames = Product::query()->pluck('name')->all();
        $available = array_values(array_filter($catalog, function (array $p) use ($existingNames): bool {
            return !in_array($p['name'], $existingNames, true);
        }));

        $availableCount = count($available);
        $target = min($desired, $availableCount);

        if ($this->command && $target < $desired) {
            $this->command->warn(sprintf(
                'ProductSeeder: solicitados %d produtos, mas apenas %d únicos disponíveis no catálogo (excluindo já existentes). Limitando para evitar duplicidade.',
                $desired,
                $availableCount
            ));
        }

        // Embaralhar e selecionar N únicos disponíveis
        $selected = array_slice(Arr::shuffle($available), 0, $target);

        foreach ($selected as $prod) {
            $dims = ProductFactory::getDimensionsForProduct($prod['name'], $prod['unit_of_measure']);
            Product::factory()
                ->state(array_merge($prod, $dims, [
                    'status' => 'active',
                    'created_by' => $userIds->random(),
                    'updated_by' => null,
                ]))
                ->create();
        }
    }
}
