<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReasonType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class ReasonTypeFactory extends Factory
{
    protected $model = ReasonType::class;

    public function definition(): array
    {
        $this->faker->unique(true);
        $locale = config('seeding.faker_locale', config('app.faker_locale'));
        $faker = $locale === 'pt_BR' ? fake('pt_BR') : $this->faker;

        $reasonTypes = self::catalog();
        $reasonType = $faker->randomElement($reasonTypes);

        $statusWeights = config('seeding.weights.status', ['active' => 85, 'inactive' => 15]);
        $status = $this->pickWeighted($statusWeights);

        return [
            'name' => $reasonType['name'],
            'status' => $status,
            'created_by' => $this->existingUserId(),
            'updated_by' => null,
        ];
    }

    public static function catalog(): array
    {
        return [
            ['name' => 'Paradas de Máquina'],
            ['name' => 'Refugo de Moldados'],
            ['name' => 'Refugo de Produção'],
            ['name' => 'Manutenção Preventiva'],
            ['name' => 'Manutenção Corretiva'],
            ['name' => 'Setup e Preparação'],
            ['name' => 'Problemas de Qualidade'],
            ['name' => 'Falta de Matéria-Prima'],
            ['name' => 'Problemas Elétricos'],
            ['name' => 'Falhas Mecânicas'],
            ['name' => 'Treinamento e Capacitação'],
            ['name' => 'Limpeza e Higienização'],
            ['name' => 'Ajustes de Processo'],
            ['name' => 'Mudanças de Ferramentas'],
            ['name' => 'Inspeções e Auditorias'],
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
}
