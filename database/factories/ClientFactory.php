<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        $this->faker->unique(true);
        // Ensure pt_BR names/phone when configured; fall back to default faker
        $localeFaker = function () {
            $locale = config('seeding.faker_locale', config('app.faker_locale'));
            return $locale === 'pt_BR' ? fake('pt_BR') : $this->faker;
        };
        $faker = $localeFaker();

        $personType = $faker->randomElement(['individual', 'company']);

        $document = $personType === 'company'
            ? $faker->unique()->cnpj(false)
            : $faker->unique()->cpf(false);

        $statusWeights = config('seeding.weights.status', ['active' => 70, 'inactive' => 30]);
        $status = $this->pickWeighted($statusWeights);

        return [
            'name' => $faker->name(),
            'person_type' => $personType,
            'document' => $document,
            'observations' => $faker->optional()->randomElement([
                'Cliente preferencial - desconto de 10%',
                'Paga sempre em dia',
                'Contato apenas por e-mail',
                'Cliente VIP',
                'Possui contrato ativo',
                'Referenciado por João Silva',
                'Empresa em expansão',
                'Necessita de atendimento urgente',
                'Cliente desde 2020',
                'Observar prazo de entrega',
                'Cliente com histórico de atrasos',
                'Prefere contato por telefone',
                'Empresa do ramo de tecnologia',
                'Cliente internacional',
                'Possui filiais em outras cidades',
                'Cliente com potencial de crescimento',
                'Já fez pedidos grandes anteriormente',
                'Solicitou orçamento personalizado',
                'Cliente com necessidades especiais',
                'Empresa certificada ISO 9001',
                'Contato principal: Maria Santos',
                'Cliente com desconto progressivo',
                'Empresa familiar',
                'Cliente com contrato de manutenção',
                'Observar condições de pagamento',
            ]),
            'status' => $status,
            'contact_name' => $personType === 'company' ? $faker->name() : null,
            'contact_phone_primary' => $faker->phoneNumber(),
            'contact_phone_secondary' => $faker->optional()->phoneNumber(),
            'contact_email' => $faker->safeEmail(),
            'created_by_id' => $this->existingUserId(),
            'updated_by_id' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Client $client) {
            $locale = config('seeding.faker_locale', config('app.faker_locale'));
            $faker = $locale === 'pt_BR' ? fake('pt_BR') : fake();
            $statusWeights = config('seeding.weights.address_status', ['active' => 85, 'inactive' => 15]);
            $status = $this->pickWeighted($statusWeights);

            // Criar pelo menos um endereço para cada cliente
            $client->addresses()->create([
                'description' => fake()->randomElement([
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
                'status' => $status,
                'created_by_id' => $client->created_by_id,
            ]);
        });
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
