<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MoldType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class MoldTypeFactory extends Factory
{
    protected $model = MoldType::class;

    public function definition(): array
    {
        $locale = config('seeding.faker_locale', config('app.faker_locale'));
        $faker = $locale === 'pt_BR' ? fake('pt_BR') : $this->faker;

        $moldTypes = self::catalog();
        $moldType = $faker->randomElement($moldTypes);

        $statusWeights = config('seeding.weights.status', ['active' => 85, 'inactive' => 15]);
        $status = $this->pickWeighted($statusWeights);

        return [
            'name' => $faker->unique()->word() . ' ' . $faker->randomElement(['Type', 'Mold', 'Material', 'Component']),
            'pieces_per_package' => $faker->randomFloat(2, 0.01, 100),
            'status' => $status,
            'created_by' => $this->existingUserId(),
            'updated_by' => null,
        ];
    }

    public static function catalog(): array
    {
        return [
            ['name' => 'Molde de concreto'],
            ['name' => 'Molde de plástico'],
            ['name' => 'Molde de metal'],
            ['name' => 'Molde de silicone'],
            ['name' => 'Molde de fibra'],
            ['name' => 'Molde de gesso'],
            ['name' => 'Molde de cerâmica'],
            ['name' => 'Molde de madeira'],
            ['name' => 'Molde de vidro'],
            ['name' => 'Molde estrutural'],
            ['name' => 'Molde decorativo'],
            ['name' => 'Molde de isolamento'],
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