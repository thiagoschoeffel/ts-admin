<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Operator;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class OperatorFactory extends Factory
{
    protected $model = Operator::class;

    public function definition(): array
    {
        $this->faker->unique(true);
        $locale = config('seeding.faker_locale', config('app.faker_locale'));
        $faker = $locale === 'pt_BR' ? fake('pt_BR') : $this->faker;

        $operators = self::catalog();
        $operator = $faker->randomElement($operators);

        $statusWeights = config('seeding.weights.status', ['active' => 85, 'inactive' => 15]);
        $status = $this->pickWeighted($statusWeights);

        return [
            'sector_id' => Sector::active()->inRandomOrder()->value('id') ?? 1,
            'name' => $operator['name'],
            'status' => $status,
            'created_by' => $this->existingUserId(),
            'updated_by' => null,
        ];
    }

    public static function catalog(): array
    {
        return [
            ['name' => 'Operador de Produção A'],
            ['name' => 'Operador de Linha B'],
            ['name' => 'Operador de Máquinas C'],
            ['name' => 'Técnico Operacional D'],
            ['name' => 'Auxiliar de Produção E'],
            ['name' => 'Operador de Empilhadeira'],
            ['name' => 'Operador de Ponte Rolante'],
            ['name' => 'Operador de Embalagem'],
            ['name' => 'Operador de Qualidade'],
            ['name' => 'Operador CNC'],
        ];
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

    private function existingUserId(): int
    {
        $ids = User::query()
            ->whereIn('email', ['admin@example.com', 'user@example.com'])
            ->pluck('id')
            ->all();

        if (!empty($ids)) {
            return Arr::random($ids);
        }

        $userId = User::query()->inRandomOrder()->value('id');
        if ($userId) {
            return (int) $userId;
        }

        return 1;
    }
}
