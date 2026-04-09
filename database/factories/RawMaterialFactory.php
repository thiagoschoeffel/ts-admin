<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RawMaterial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class RawMaterialFactory extends Factory
{
    protected $model = RawMaterial::class;

    public function definition(): array
    {
        $this->faker->unique(true);
        $locale = config('seeding.faker_locale', config('app.faker_locale'));
        $faker = $locale === 'pt_BR' ? fake('pt_BR') : $this->faker;

        $rawMaterials = self::catalog();
        $rawMaterial = $faker->randomElement($rawMaterials);

        $statusWeights = config('seeding.weights.status', ['active' => 85, 'inactive' => 15]);
        $status = $this->pickWeighted($statusWeights);

        return [
            'name' => $rawMaterial['name'],
            'status' => $status,
            'created_by' => $this->existingUserId(),
            'updated_by' => null,
        ];
    }

    public static function catalog(): array
    {
        return [
            ['name' => 'Aço'],
            ['name' => 'Alumínio'],
            ['name' => 'Cobre'],
            ['name' => 'Ferro'],
            ['name' => 'Plástico'],
            ['name' => 'Madeira'],
            ['name' => 'Vidro'],
            ['name' => 'Tecido'],
            ['name' => 'Couro'],
            ['name' => 'Papel'],
            ['name' => 'Borracha'],
            ['name' => 'Cerâmica'],
            ['name' => 'Concreto'],
            ['name' => 'Fibra de carbono'],
            ['name' => 'Silicone'],
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