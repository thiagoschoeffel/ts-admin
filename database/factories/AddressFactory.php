<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        $locale = config('seeding.faker_locale', config('app.faker_locale'));
        $faker = $locale === 'pt_BR' ? fake('pt_BR') : fake();
        $statusWeights = config('seeding.weights.address_status', ['active' => 85, 'inactive' => 15]);
        $status = $this->pickWeighted($statusWeights);

        return [
            'client_id' => null, // Definir ao usar
            'postal_code' => $faker->postcode(),
            'address' => $faker->streetAddress(),
            'address_number' => (string) $faker->numberBetween(1, 9999),
            'address_complement' => $faker->optional()->secondaryAddress(),
            'neighborhood' => $faker->streetName(),
            'city' => $faker->city(),
            'state' => Str::upper($faker->randomElement([
                'AC',
                'AL',
                'AP',
                'AM',
                'BA',
                'CE',
                'DF',
                'ES',
                'GO',
                'MA',
                'MT',
                'MS',
                'MG',
                'PA',
                'PB',
                'PR',
                'PE',
                'PI',
                'RJ',
                'RN',
                'RS',
                'RO',
                'RR',
                'SC',
                'SP',
                'SE',
                'TO',
            ])),
            'description' => $faker->randomElement([
                'Casa principal',
                'Apartamento',
                'Escritório',
                'Casa de praia',
                'Loja física',
                'Endereço de entrega',
                'Casa dos pais',
                'Cobrança',
                'Residencial',
                'Comercial',
                'Casa de campo',
                'Galpão',
                'Sala comercial',
                'Cobertura',
                'Casa de veraneio',
                'Escritório central',
                'Filial',
                'Depósito',
                'Showroom',
                'Ateliê',
                'Consultório',
                'Clínica',
                'Escola',
                'Academia',
                'Restaurante',
            ]),
            'status' => $status,
            'created_by_id' => $this->existingUserId(),
            'updated_by_id' => null,
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

        return (int) (User::query()->inRandomOrder()->value('id') ?? 1);
    }
}
