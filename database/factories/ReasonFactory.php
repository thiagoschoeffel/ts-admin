<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Reason;
use App\Models\ReasonType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class ReasonFactory extends Factory
{
    protected $model = Reason::class;

    public function definition(): array
    {
        $this->faker->unique(true);
        $locale = config('seeding.faker_locale', config('app.faker_locale'));
        $faker = $locale === 'pt_BR' ? fake('pt_BR') : $this->faker;

        $reasonType = ReasonType::inRandomOrder()->first() ?? ReasonType::factory()->create();

        $reasons = self::catalog()[$reasonType->name] ?? ['Motivo genérico'];
        $reason = $faker->randomElement($reasons);

        $statusWeights = config('seeding.weights.status', ['active' => 85, 'inactive' => 15]);
        $status = $this->pickWeighted($statusWeights);

        return [
            'reason_type_id' => $reasonType->id,
            'name' => $reason,
            'status' => $status,
            'created_by' => $this->existingUserId(),
            'updated_by' => null,
        ];
    }

    public static function catalog(): array
    {
        return [
            'Paradas de Máquina' => [
                'Parada corretiva',
                'Parada preventiva',
                'Falha elétrica',
                'Falha mecânica',
                'Manutenção programada',
                'Quebra de equipamento',
                'Problema hidráulico',
                'Problema pneumático',
            ],
            'Refugo de Moldados' => [
                'Defeito de injeção',
                'Queima de material',
                'Bolha no produto',
                'Rachadura',
                'Dimensão incorreta',
                'Cor incorreta',
                'Contaminação',
                'Problema de molde',
            ],
            'Refugo de Produção' => [
                'Defeito de montagem',
                'Peça danificada',
                'Erro de operador',
                'Material inadequado',
                'Problema de qualidade',
                'Rejeição por inspeção',
                'Produto fora de especificação',
                'Embalagem danificada',
            ],
            'Manutenção Preventiva' => [
                'Troca de óleo',
                'Verificação de filtros',
                'Calibração de equipamentos',
                'Limpeza programada',
                'Substituição de peças desgastadas',
                'Teste de segurança',
                'Verificação elétrica',
                'Manutenção de correias',
            ],
            'Manutenção Corretiva' => [
                'Reparo de emergência',
                'Substituição de peça quebrada',
                'Correção de vazamento',
                'Reparo elétrico',
                'Ajuste mecânico',
                'Substituição de componente',
                'Reparo hidráulico',
                'Correção de alinhamento',
            ],
            'Setup e Preparação' => [
                'Troca de ferramenta',
                'Ajuste de parâmetros',
                'Preparação de molde',
                'Configuração de máquina',
                'Troca de produto',
                'Setup de linha',
                'Calibração inicial',
                'Preparação de matéria-prima',
            ],
            'Problemas de Qualidade' => [
                'Não conformidade',
                'Rejeição por cliente',
                'Defeito crítico',
                'Problema de processo',
                'Erro de medição',
                'Falha de inspeção',
                'Controle estatístico fora de controle',
                'Auditoria reprovada',
            ],
            'Falta de Matéria-Prima' => [
                'Estoque insuficiente',
                'Atraso na entrega',
                'Problema de fornecedor',
                'Erro no planejamento',
                'Quebra de estoque',
                'Material rejeitado',
                'Especificação errada',
                'Perda de material',
            ],
            'Problemas Elétricos' => [
                'Falta de energia',
                'Sobrecarga elétrica',
                'Curto-circuito',
                'Problema no quadro elétrico',
                'Falha no motor',
                'Queima de fusível',
                'Problema no inversor',
                'Falha no sensor',
            ],
            'Falhas Mecânicas' => [
                'Quebra de engrenagem',
                'Desalinhamento',
                'Vazamento hidráulico',
                'Falha na bomba',
                'Problema na transmissão',
                'Quebra de correia',
                'Falha no atuador',
                'Problema no cilindro',
            ],
            'Treinamento e Capacitação' => [
                'Treinamento inicial',
                'Reciclagem obrigatória',
                'Capacitação específica',
                'Treinamento de segurança',
                'Atualização de procedimentos',
                'Treinamento de qualidade',
                'Capacitação técnica',
                'Avaliação de competência',
            ],
            'Limpeza e Higienização' => [
                'Limpeza programada',
                'Higienização de área',
                'Sanitização de equipamento',
                'Limpeza de emergência',
                'Descontaminação',
                'Limpeza final',
                'Manutenção de limpeza',
                'Verificação sanitária',
            ],
            'Ajustes de Processo' => [
                'Otimização de parâmetros',
                'Melhoria de processo',
                'Ajuste de temperatura',
                'Correção de pressão',
                'Ajuste de velocidade',
                'Calibração de instrumentos',
                'Otimização de ciclo',
                'Correção de setup',
            ],
            'Mudanças de Ferramentas' => [
                'Troca de matriz',
                'Substituição de punção',
                'Troca de molde',
                'Mudança de ferramenta de corte',
                'Substituição de lâmina',
                'Troca de cabeçote',
                'Mudança de bico',
                'Substituição de acessório',
            ],
            'Inspeções e Auditorias' => [
                'Inspeção de qualidade',
                'Auditoria interna',
                'Verificação dimensional',
                'Controle de processo',
                'Auditoria externa',
                'Inspeção final',
                'Verificação de conformidade',
                'Auditoria de sistema',
            ],
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
