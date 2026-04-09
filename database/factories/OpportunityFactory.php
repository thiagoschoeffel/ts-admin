<?php

namespace Database\Factories;

use App\Models\Opportunity;
use App\Models\Lead;
use App\Models\Client;
use App\Models\User;
use App\Enums\OpportunityStage;
use App\Enums\OpportunityStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition(): array
    {
        $faker = config('seeding.faker_locale', config('app.faker_locale')) === 'pt_BR' ? fake('pt_BR') : fake();

        // Títulos de oportunidades relacionados a negócios brasileiros
        $titles = [
            'Implementação de Sistema ERP',
            'Consultoria em Gestão Empresarial',
            'Desenvolvimento de E-commerce',
            'Solução de Logística e Distribuição',
            'Sistema de Gestão Financeira',
            'Plataforma de Delivery',
            'Automação de Processos Industriais',
            'Sistema de Controle de Estoque',
            'Consultoria em Marketing Digital',
            'Desenvolvimento de App Mobile',
            'Solução de Business Intelligence',
            'Sistema de Gestão de RH',
            'Plataforma de E-learning',
            'Consultoria em Transformação Digital',
            'Sistema de Gestão de Qualidade',
            'Desenvolvimento de Software Sob Medida',
            'Solução de Cibersegurança',
            'Sistema de Gestão Ambiental',
            'Consultoria em Gestão de Projetos',
            'Plataforma de Telemedicina',
            'Sistema de Gestão Escolar',
            'Solução de IoT Industrial',
            'Desenvolvimento de API',
            'Sistema de Gestão de Frota',
            'Consultoria em Compliance',
            'Plataforma de Marketplace',
            'Sistema de Gestão de Vendas',
            'Solução de Big Data',
            'Desenvolvimento de Chatbot',
            'Sistema de Gestão de Energia',
        ];

        $descriptions = [
            'Projeto para modernizar os processos internos da empresa com tecnologia de ponta.',
            'Implementação de solução completa para otimizar a gestão e aumentar a produtividade.',
            'Desenvolvimento de plataforma digital para expandir o alcance do negócio no mercado online.',
            'Solução integrada para melhorar a eficiência logística e reduzir custos operacionais.',
            'Sistema financeiro avançado para controle preciso e tomada de decisões estratégicas.',
            'Plataforma inovadora para conectar clientes e estabelecimentos de forma prática.',
            'Automação inteligente para processos industriais com foco em qualidade e produtividade.',
            'Sistema completo para controle de inventário e gestão de suprimentos.',
            'Estratégias digitais para aumentar a presença online e engajar o público-alvo.',
            'Aplicativo mobile nativo para melhorar a experiência do usuário final.',
            'Análise de dados em tempo real para insights estratégicos e vantagem competitiva.',
            'Sistema integrado para gestão de recursos humanos e desenvolvimento de talento.',
            'Plataforma educacional interativa para capacitação e aprendizado contínuo.',
            'Acompanhamento especializado na jornada de digitalização dos processos empresariais.',
            'Implementação de padrões de qualidade para excelência operacional.',
            'Solução personalizada desenvolvida especificamente para as necessidades do cliente.',
            'Proteção avançada contra ameaças digitais e garantia de segurança da informação.',
            'Sistema para monitoramento e gestão sustentável dos recursos ambientais.',
            'Metodologias ágeis para gestão eficiente de projetos complexos.',
            'Plataforma médica digital para consultas remotas e acompanhamento de pacientes.',
            'Sistema educacional completo para gestão acadêmica e administrativa.',
            'Conectividade inteligente para otimização de processos industriais.',
            'Integração de sistemas através de APIs robustas e escaláveis.',
            'Controle inteligente de frota para redução de custos e aumento da eficiência.',
            'Adequação às normas regulatórias e garantia de conformidade legal.',
            'Plataforma de comércio eletrônico para conectar compradores e vendedores.',
            'Sistema de CRM avançado para gestão completa do relacionamento com clientes.',
            'Processamento de grandes volumes de dados para insights estratégicos.',
            'Assistente virtual inteligente para atendimento automatizado.',
            'Monitoramento e otimização do consumo energético empresarial.',
        ];

        $stageValue = $this->pickWeighted(config('seeding.weights.opportunity_stage', [
            'new' => 25,
            'contact' => 25,
            'proposal' => 20,
            'negotiation' => 15,
            'won' => 10,
            'lost' => 5,
        ]));
        $stage = collect(OpportunityStage::cases())->firstWhere('value', $stageValue) ?? OpportunityStage::NOVO;
        $probability = match ($stage->value) {
            'won' => 100,
            'lost' => 0,
            'negotiation' => $faker->numberBetween(60, 80),
            'proposal' => $faker->numberBetween(40, 60),
            'contact' => $faker->numberBetween(20, 40),
            default => $faker->numberBetween(0, 20),
        };

        // Valores estimados baseados no mercado brasileiro
        $expectedValues = [
            15000.00,
            25000.00,
            35000.00,
            50000.00,
            75000.00,
            100000.00,
            150000.00,
            200000.00,
            300000.00,
            500000.00,
            750000.00,
            1000000.00,
            1500000.00,
            2000000.00
        ];

        return [
            'lead_id' => Lead::factory(),
            'client_id' => Client::factory(),
            'title' => $faker->randomElement($titles),
            'description' => $faker->randomElement($descriptions),
            'stage' => $stage,
            'probability' => $probability,
            'expected_value' => $faker->randomElement($expectedValues),
            'expected_close_date' => $faker->dateTimeBetween('now', '+6 months'),
            'owner_id' => $this->existingUserId(),
            'status' => $faker->randomElement(OpportunityStatus::cases()),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Opportunity $opportunity) {
            $faker = config('seeding.faker_locale', config('app.faker_locale')) === 'pt_BR' ? fake('pt_BR') : fake();

            // Criar alguns itens para a oportunidade
            $numItems = $faker->numberBetween(1, 5);

            for ($i = 0; $i < $numItems; $i++) {
                $product = \App\Models\Product::where('status', 'active')->inRandomOrder()->first()
                    ?? \App\Models\Product::factory()->create(['status' => 'active']);
                $quantity = $faker->numberBetween(1, 10);
                $unitPrice = $faker->randomFloat(2, 10, 1000); // Preço entre 10 e 1000

                $opportunity->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $quantity * $unitPrice,
                ]);
            }
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
