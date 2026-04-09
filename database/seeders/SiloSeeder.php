<?php

namespace Database\Seeders;

use App\Models\Silo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Database\Factories\SiloFactory;

class SiloSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $base = (int) env('SEED_QTD', (int) config('seeding.volumes.silos', 0));
    $default = (int) config('seeding.volumes.silos', 10);
    $desired = $base > 0 ? max(5, (int) round($base * 0.2)) : $default;

    $catalog = SiloFactory::catalog();
    $existingNames = Silo::query()->pluck('name')->all();
    $available = array_values(array_filter($catalog, function (array $silo) use ($existingNames): bool {
      return !in_array($silo['name'], $existingNames, true);
    }));

    $availableCount = count($available);
    $target = min($desired, $availableCount);

    if ($this->command && $target < $desired) {
      $this->command->warn(sprintf(
        'SiloSeeder: solicitados %d silos, mas apenas %d únicos disponíveis no catálogo (excluindo já existentes). Limitando para evitar duplicidade.',
        $desired,
        $availableCount
      ));
    }

    $selected = array_slice(Arr::shuffle($available), 0, $target);

    foreach ($selected as $silo) {
      Silo::factory()->create($silo);
    }
  }
}
