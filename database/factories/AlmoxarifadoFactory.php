<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Almoxarifado;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class AlmoxarifadoFactory extends Factory
{
    protected $model = Almoxarifado::class;

    public function definition(): array
    {
        $this->faker->unique(true);
        $locale = config('seeding.faker_locale', config('app.faker_locale'));
        $faker = $locale === 'pt_BR' ? fake('pt_BR') : $this->faker;

        $almoxarifados = self::catalog();
        $almoxarifado = $faker->randomElement($almoxarifados);

        $statusWeights = config('seeding.weights.status', ['active' => 85, 'inactive' => 15]);
        $status = $this->pickWeighted($statusWeights);

        return [
            'name' => $almoxarifado['name'],
            'status' => $status,
            'created_by' => $this->existingUserId(),
            'updated_by' => null,
        ];
    }

    public static function catalog(): array
    {
        return [
            ['name' => 'Almoxarifado Central'],
            ['name' => 'Almoxarifado de Produção'],
            ['name' => 'Almoxarifado de Manutenção'],
            ['name' => 'Almoxarifado de Qualidade'],
            ['name' => 'Almoxarifado de Expedição'],
            ['name' => 'Almoxarifado de Logística'],
            ['name' => 'Almoxarifado de Recursos Humanos'],
            ['name' => 'Almoxarifado de Financeiro'],
            ['name' => 'Almoxarifado de Compras'],
            ['name' => 'Almoxarifado de Vendas'],
            ['name' => 'Almoxarifado de TI'],
            ['name' => 'Almoxarifado de Administração'],
            ['name' => 'Almoxarifado de Marketing'],
            ['name' => 'Almoxarifado de Pesquisa e Desenvolvimento'],
            ['name' => 'Almoxarifado de Segurança'],
            ['name' => 'Almoxarifado de Limpeza'],
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