<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Silo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class SiloFactory extends Factory
{
  protected $model = Silo::class;

  public function definition(): array
  {
    $this->faker->unique(true);
    $locale = config('seeding.faker_locale', config('app.faker_locale'));
    $faker = $locale === 'pt_BR' ? fake('pt_BR') : $this->faker;

    $silos = self::catalog();
    $silo = $faker->randomElement($silos);

    $statusWeights = config('seeding.weights.status', ['active' => 85, 'inactive' => 15]);
    $status = $this->pickWeighted($statusWeights);

    return [
      'name' => $silo['name'],
      'status' => $status,
      'created_by' => $this->existingUserId(),
      'updated_by' => null,
    ];
  }

  public static function catalog(): array
  {
    return [
      ['name' => 'Silo 1'],
      ['name' => 'Silo 2'],
      ['name' => 'Silo 3'],
      ['name' => 'Silo 4'],
      ['name' => 'Silo 5'],
      ['name' => 'Silo A'],
      ['name' => 'Silo B'],
      ['name' => 'Silo C'],
      ['name' => 'Silo D'],
      ['name' => 'Silo E'],
      ['name' => 'Silo Principal'],
      ['name' => 'Silo Secundário'],
      ['name' => 'Silo de Produção'],
      ['name' => 'Silo de Estoque'],
      ['name' => 'Silo Temporário'],
    ];
  }

  private function existingUserId(): int
  {
    // Primeiro tenta encontrar usuários específicos
    $ids = User::query()
      ->whereIn('email', ['admin@example.com', 'user@example.com'])
      ->pluck('id')
      ->all();

    if (!empty($ids)) {
      return Arr::random($ids);
    }

    // Se não encontrar, pega qualquer usuário existente
    $userId = User::query()->inRandomOrder()->value('id');
    if ($userId) {
      return (int) $userId;
    }

    // Fallback para ID 1 (caso não haja usuários)
    return 1;
  }

  private function pickWeighted(array $weights): string
  {
    $total = array_sum($weights);
    if ($total <= 0) {
      return array_key_first($weights);
    }
    $rand = mt_rand(1, (int) $total);
    $running = 0;
    foreach ($weights as $key => $weight) {
      $running += (int) $weight;
      if ($rand <= $running) {
        return (string) $key;
      }
    }
    return (string) array_key_first($weights);
  }
}
