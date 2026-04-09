# Controle de Estoque de Produtos — Proposta Técnica

## 1. Visão Geral
- Objetivo: implantar um módulo de estoque para controlar quantidades em posse, reservas vinculadas a pedidos e movimentações (entradas/saídas/ajustes), mantendo total aderência ao estilo atual do projeto (Laravel 12, Eloquent Models, Policies, FormRequests, Inertia, rotas web e respostas JSON simples onde aplicável).
- Motivação: garantir disponibilidade de produtos, evitar vendas acima do estoque, rastrear histórico de movimentações e suportar produtos compostos (componentes), respeitando as regras de negócio existentes (ex.: item/pedido não pode usar produtos inativos).
- Impacto: integração direta com Produtos e Pedidos. Reservas e baixas serão acionadas na criação/edição/cancelamento de pedidos e na manutenção manual do estoque. Produtos compostos propagam reservas/baixas para seus componentes.

## 2. Escopo do Estoque
- Entradas e saídas:
  - Entrada manual (reposição, devolução, inventário positivo).
  - Saída por pedido (baixa na conclusão/entrega), consumo em produção, inventário negativo.
- Níveis de estoque:
  - `min_stock` e `max_stock` por produto, com indicadores de atenção quando abaixo do mínimo.
- Reservas automáticas:
  - Ao criar/editar pedidos em status que reservam (ex.: `pending`/`confirmed`), reservar quantidade; na conclusão/entrega, efetivar baixa e liberar reserva.
- Itens compostos:
  - Reservar/baixar componentes conforme árvore de componentes (`product_components`), multiplicando pela quantidade pedida.
- Ações que não alteram estoque:
  - Cadastro/edição de metadados do produto (nome, descrição, preço, medidas, status), desde que não mudem itens do pedido ou status que disparem movimentação.

## 3. Modelagem de Dados
Serão adicionadas novas tabelas seguindo o padrão de migrations atual (arquivos anônimos com `up`/`down`, FKs explícitas, decimais para quantidades/valores).

### 3.1 Tabela `product_stocks`
- Objetivo: guardar saldo por produto e parâmetros de controle.
- Campos sugeridos:
  - `id`
  - `product_id` (FK `products.id`, `onDelete('cascade')`)
  - `quantity_on_hand` (decimal 12,2 — saldo em posse)
  - `quantity_reserved` (decimal 12,2 — saldo reservado)
  - `min_stock`, `max_stock` (decimal 12,2 — limites)
  - `created_by_id`, `updated_by_id` (FK `users.id`, `onDelete('set null')`)
  - `timestamps`
- Índices: `unique(product_id)`, índice em `product_id`.

Exemplo de migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('quantity_on_hand', 12, 2)->default(0);
            $table->decimal('quantity_reserved', 12, 2)->default(0);
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->decimal('max_stock', 12, 2)->default(0);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->unique(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
```

### 3.2 Tabela `stock_movements`
- Objetivo: registrar todo histórico de movimentações (auditoria detalhada + prevenção de duplicidade).
- Campos sugeridos:
  - `id`
  - `product_id` (FK `products.id`)
  - `movement_type` (string — tipo da movimentação, ver seção 4)
  - `quantity` (decimal 12,2 — positiva para entrada, negativa para saída)
  - `reserved_delta` (decimal 12,2 — variação na reserva; ex.: +N ao reservar, -N ao consumir/liberar)
  - `balance_after` (decimal 12,2 — saldo em posse após a movimentação)
  - `reference_type` (string nullable — ex.: `order`, `inventory`, `manual`)
  - `reference_id` (bigint nullable — ex.: `orders.id`)
  - `notes` (text nullable)
  - `created_by_id` (FK `users.id`)
  - `created_at`/`updated_at`
- Índices: `index(product_id, created_at)`, `index(reference_type, reference_id)`. Opcional: `unique(product_id, movement_type, reference_type, reference_id)` para idempotência.

Exemplo de migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('movement_type');
            $table->decimal('quantity', 12, 2); // +entrada, -saída
            $table->decimal('reserved_delta', 12, 2)->default(0);
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            // $table->unique(['product_id','movement_type','reference_type','reference_id']); // opcional
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
```

### 3.3 Ajustes de inventário (opcional)
- Caso deseje-se segregar ajustes periódicos, pode-se criar `stock_adjustments` para registrar contagens físicas e reconciliar diferenças. Alternativamente, tratar como `movement_type = 'inventory_adjustment'` em `stock_movements`.

## 4. Tipos de Movimentação
Seguindo o padrão atual do projeto (valores de status/tipos como strings), os tipos serão strings constantes. Sugestão de centralização em `App\Support\StockMovementTypes` (classe com constantes), mantendo a simplicidade do código existente.

```php
<?php

namespace App\Support;

final class StockMovementTypes
{
    public const ENTRADA_MANUAL     = 'manual_in';
    public const SAIDA_PEDIDO       = 'order_out';
    public const AJUSTE_INVENTARIO  = 'inventory_adjustment';
    public const CONSUMO_PRODUCAO   = 'production_consume';
    public const DEVOLUCAO          = 'return_in';
}
```

Observação: se o time preferir PHP Enums, pode-se criar `App\Enums\StockMovementType` futuramente, porém a base atual utiliza strings simples.

## 5. Regras de Negócio
- Bloqueio por saldo insuficiente:
  - Não permitir saída (baixa) se `quantity_on_hand` disponível for menor que o requerido no momento de efetivar a baixa.
- Reservas automáticas por Pedido:
  - Criar/editar pedido em status que reserva (ex.: `pending`/`confirmed`): registrar `reserved_delta` positivo e atualizar `product_stocks.quantity_reserved`.
  - Alteração de itens do pedido deve ajustar a reserva pela diferença.
- Baixa automática na conclusão:
  - Ao mudar status para `completed`/`delivered`: efetivar baixa (movimentação negativa) e reduzir `quantity_reserved` correspondente.
- Cancelamento de pedido:
  - Reverter a reserva (movimentação `reserved_delta` negativa) e não efetivar baixa.
- Produtos inativos:
  - Reaproveitar validações já existentes em `StoreOrderRequest`/`UpdateOrderItemRequest` (produtos inativos não entram em pedidos/itens novos).
- Produtos compostos:
  - Reservar/baixar componentes conforme árvore de componentes (`product_components`), multiplicando `quantidade_item * quantidade_componente` (suportar níveis).
- Ajustes manuais:
  - Apenas usuários com permissão podem ajustar. Todo ajuste gera `stock_movements` com `AJUSTE_INVENTARIO` e atualiza saldos.

## 6. Arquitetura e Fluxos
Aderir ao estilo atual: Controllers + FormRequests + Policies + Observers. Evitar introduzir camadas não existentes (Services/Repositories). Lógica principal concentrada nos Controllers e Models auxiliares, com Observers para consistência.

- Models novos:
  - `App\Models\ProductStock` (relacionado a `Product` via `hasOne`/`belongsTo`).
  - `App\Models\StockMovement` (relacionado a `Product`).
- Observers:
  - `OrderObserver` (existente): ampliar para reagir a transições de status (ex.: de `pending`→`confirmed` somente reserva; `confirmed`→`delivered` reserva→baixa). Alternativa: tratar no `OrderController::update` para manter simplicidade.
  - Opcional: `OrderItem` observer para reservas quando itens são adicionados/alterados/removidos, seguindo o padrão já usado de aplicar regras dentro do controller de itens.
- Integrações entre módulos:
  - `OrderController`:
    - `store`: criar pedido com status inicial (ex.: `pending`) → reservar.
    - `update`: se status mudou para concluído/entregue → efetivar baixa; se cancelado → liberar reserva.
    - `addItem/updateItem/removeItem`: ajustar reservas pela diferença.
  - `ProductController`:
    - Manter sem efeitos de estoque ao editar metadados. Ao alterar componentes, recalcular apenas reservas/baixas futuras; não retroagir automaticamente em pedidos existentes.
- Prevenção de duplicidade:
  - Antes de inserir `stock_movements` para um pedido, checar (via índice/consulta) se já existe registro com `product_id + movement_type + reference_type + reference_id`.
- Concorrência/Transações:
  - Envolver operações críticas em transações `DB::transaction(...)`. Quando efetivar baixa, ler/atualizar com travas otimizadas (ex.: `for update` via Eloquent `lockForUpdate()` se necessário em cenários de alta concorrência).

## 7. Permissões e Políticas
Seguir o padrão de `ProductPolicy`/`OrderPolicy` com `permissions` em `User` (ex.: `$user->permissions['stocks']['view']`).

- Novas chaves no JSON de permissões:
  - `stocks.view`, `stocks.adjust`, `stocks.report` → mapeadas como:
    - `['stocks' => ['view' => bool, 'adjust' => bool, 'report' => bool]]`.
- Nova policy: `App\Policies\StockPolicy`
  - Métodos: `viewAny`, `view`, `adjust`, `createMovement`.
- Middleware `CheckPolicy` pode proteger rotas sensíveis (`adjust`).

Exemplo (recorte) de `StockPolicy`:

```php
<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class StockPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) return true;
        $p = $user->permissions ?? [];
        return (bool)($p['stocks']['view'] ?? false);
    }

    public function adjust(User $user): bool
    {
        if ($user->isAdmin()) return true;
        $p = $user->permissions ?? [];
        return (bool)($p['stocks']['adjust'] ?? false);
    }
}
```

## 8. Interface (UI/UX)
- Listagem de Produtos (`Admin/Products/Index.vue`):
  - Adicionar colunas: “Em estoque” (`quantity_on_hand`), “Reservado” (`quantity_reserved`), “Disponível” (`on_hand - reservado`).
  - Destaque visual quando `on_hand < min_stock`.
- Tela de Movimentações:
  - Nova página `Admin/Stocks/Movements.vue` listando `stock_movements` com filtros (produto, tipo, período, referência).
- Ajuste manual:
  - Modal/fluxo em `Admin/Stocks/Adjust.vue` (ou modal em Produtos) para ajuste com motivo, quantidade e validação via Policy.

## 9. API / Endpoints (rotas web com JSON)
Seguir padrão atual (rotas web sob `admin`, respostas JSON simples quando não for Inertia).

- Listar estoque:
  - `GET /admin/stocks` → Inertia (listagem com filtros) ou JSON se `Accept: application/json`.
- Listar movimentações:
  - `GET /admin/stocks/movements` → JSON/Inertia conforme padrão.
- Criar movimentação manual (ajuste/entrada):
  - `POST /admin/stocks/movements` (Policy: `adjust`) — payload:

```json
{
  "product_id": 123,
  "movement_type": "manual_in",
  "quantity": 10.0,
  "notes": "Reposição fornecedor XYZ"
}
```

- Ajuste direto de estoque (atalho controlado):
  - `PUT /admin/stocks/adjust` (Policy: `adjust`) — payload:

```json
{
  "product_id": 123,
  "new_quantity_on_hand": 50.0,
  "notes": "Inventário trimestral"
}
```

Respostas devem seguir o padrão usado hoje (ex.: `success: true` e objetos atualizados), sem wrapper específico.

## 10. Logs e Auditoria
- Logar sempre: tipo, usuário, data/hora, referência (`reference_type`/`reference_id`), produto e valores antes/depois.
- Reaproveitar `Log::info|warning|error` como no restante do código. Traduções em `resources/lang/pt_BR/stock.php` para mensagens de erro comuns (ex.: `stock.insufficient_balance`).

## 11. Testes
- Feature (integração com pedidos):
  - Criar/editar pedido → gera reservas coerentes.
  - Alterar status para concluído/entregue → efetiva baixa.
  - Cancelar → reverte reservas.
  - Itens compostos → reservas/baixas nos componentes.
- Unit:
  - Cálculo de saldo disponível, propagação para componentes, validações de saldo insuficiente.
  - `StockPolicy` (`viewAny`, `adjust`).
- Requests:
  - `StoreStockMovementRequest`, `AdjustStockRequest` validam payloads e permissões (como já feito nos `Store/Update*Request`).

## 12. Impactos no Sistema Atual
- Migrations/Seeds:
  - Novas tabelas `product_stocks` e `stock_movements` e seed opcional para inicializar `product_stocks` com zero para produtos existentes.
- Controllers:
  - `OrderController::store|update|addItem|updateItem|removeItem`: adicionar chamadas para reservar/baixar/liberar.
- Performance/Transações:
  - Usar `DB::transaction` em operações que mexem em múltiplas linhas (`product_stocks` + `stock_movements` + itens do pedido). Em cenários concorrentes, considerar `lockForUpdate()` no registro de estoque.
- Deploy/Migração gradual:
  - Passo 1: criar tabelas e popular `product_stocks` com zero.
  - Passo 2: exibir colunas de estoque sem reservar/baixar (somente leitura, sem bloquear pedidos).
  - Passo 3: habilitar reservas em `store|addItem|updateItem|removeItem`.
  - Passo 4: habilitar baixas ao concluir pedido.

## 13. Futuras Extensões
- Relatórios de estoque por produto/categoria/período (export CSV/PDF, gráficos).
- Integração com compras/produção (entradas automáticas via NF/OP). 
- Webhooks/APIs externas (ERP) para sincronização de estoque e movimentos.
- Alertas (e-mail/toast) quando `on_hand < min_stock`.

## 14. Conclusão
- A proposta mantém o padrão do projeto (Controllers, FormRequests, Policies, Observers, traduções pt_BR, rotas web/JSON). Centralizamos estado em `product_stocks` e rastreamos tudo em `stock_movements`, com regras claras para reservas/baixas e suporte a produtos compostos. 
- Próximos passos: criar migrations, models (`ProductStock`, `StockMovement`), `StockPolicy`, Requests de ajuste, rotas e pontos de integração nos controllers de Pedido; adicionar traduções `resources/lang/pt_BR/stock.php` e telas Inertia para estoque/movimentações.
