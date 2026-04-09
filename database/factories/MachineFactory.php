<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Machine;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class MachineFactory extends Factory
{
    protected $model = Machine::class;

    public function definition(): array
    {
        $this->faker->unique(true);
        $locale = config('seeding.faker_locale', config('app.faker_locale'));
        $faker = $locale === 'pt_BR' ? fake('pt_BR') : $this->faker;

        $machines = self::catalog();
        $machine = $faker->randomElement($machines);

        $statusWeights = config('seeding.weights.status', ['active' => 85, 'inactive' => 15]);
        $status = $this->pickWeighted($statusWeights);

        return [
            'sector_id' => Sector::active()->inRandomOrder()->value('id') ?? 1,
            'name' => $machine['name'],
            'status' => $status,
            'created_by' => $this->existingUserId(),
            'updated_by' => null,
        ];
    }

    public static function catalog(): array
    {
        return [
            ['name' => 'Máquina de Solda MIG'],
            ['name' => 'Torno Mecânico CNC'],
            ['name' => 'Fresadora Vertical'],
            ['name' => 'Prensa Hidráulica 50T'],
            ['name' => 'Cortadora a Plasma'],
            ['name' => 'Retífica Cilíndrica'],
            ['name' => 'Máquina de Usinagem CNC'],
            ['name' => 'Dobradeira de Chapas'],
            ['name' => 'Guilhotina Hidráulica'],
            ['name' => 'Máquina de Pintura Eletrostática'],
            ['name' => 'Forno de Tratamento Térmico'],
            ['name' => 'Centro de Usinagem Vertical'],
            ['name' => 'Máquina de Solda TIG'],
            ['name' => 'Torno Paralelo'],
            ['name' => 'Máquina de Polimento'],
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
