# CRM — Leads e Oportunidades

## 1. Visão Geral

O módulo de CRM adiciona a gestão integrada de Leads (contatos ainda não qualificados) e Oportunidades (negociações em andamento com um cliente ou lead qualificado), mantendo o fluxo natural: Lead → Oportunidade → Pedido.

- Leads: potenciais clientes em prospecção.
- Oportunidades: negociações com etapas, valores e probabilidades.
- Fluxo: Lead (qualificado) → conversão em Cliente → criação de Oportunidade → fechamento (Ganhou) → conversão em Pedido (Order).
- Benefícios: visibilidade do funil, previsibilidade de vendas, rastreabilidade das conversões e integração com as entidades existentes (Cliente, Produto, Pedido).

## 2. Objetivos do Módulo

- Cadastrar e gerenciar leads e oportunidades.
- Converter lead em cliente e oportunidade em pedido.
- Rastrear etapas do funil de vendas.
- Associar responsáveis, produtos, valores estimados e probabilidades.
- Controlar permissões via Policies, status, notificações e auditoria.
- Integrar 100% com a arquitetura atual: Controllers → FormRequests → Services/UseCases → Policies → (opcional) Repositórios; validações customizadas; traduções em lang/pt_BR.

## 3. Modelagem de Dados

### Tabelas

- leads
  - id
  - name
  - email (nullable)
  - phone (nullable)
  - company (nullable)
  - source (enum: site, indicacao, evento, manual)
  - status (enum: new, in_contact, qualified, discarded)
  - owner_id (FK → users.id)
  - created_at / updated_at

- opportunities
  - id
  - lead_id (nullable, FK → leads.id)
  - client_id (nullable, FK → clients.id)
  - title
  - description (nullable)
  - stage (enum: new, contact, proposal, negotiation, won, lost)
  - probability (tinyint 0–100)
  - expected_value (decimal)
  - expected_close_date (date, nullable)
  - owner_id (FK → users.id)
  - status (enum: active, inactive)
  - created_at / updated_at

- opportunity_items
  - id
  - opportunity_id (FK → opportunities.id)
  - product_id (FK → products.id)
  - quantity (decimal)
  - unit_price (decimal)
  - subtotal (decimal)
  - created_at / updated_at

Observações:
- FKs com `restrictOnDelete()` conforme padrão do projeto para preservar integridade.
- Enums persistidos como strings (snake_case em inglês), mantendo traduções em lang/pt_BR. As classes Enum do PHP espelham nomes em português e valores em inglês (ver seção “Enumerações”).
- Índices: `leads.email` e `leads.phone` com `unique` (aceitando null), para reduzir duplicidades.

### Migrations (exemplos)

database/migrations/2025_10_20_000000_create_leads_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180);
            $table->string('email', 180)->nullable()->unique();
            $table->string('phone', 30)->nullable()->unique();
            $table->string('company', 180)->nullable();

            // enums em string para alinhar com o padrão atual do projeto
            $table->enum('source', ['site', 'indicacao', 'evento', 'manual'])->default('manual');
            $table->enum('status', ['new', 'in_contact', 'qualified', 'discarded'])->default('new');

            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
```

database/migrations/2025_10_20_000001_create_opportunities_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->restrictOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->restrictOnDelete();

            $table->string('title', 200);
            $table->text('description')->nullable();

            $table->enum('stage', ['new', 'contact', 'proposal', 'negotiation', 'won', 'lost'])->default('new');
            $table->unsignedTinyInteger('probability')->default(0); // 0–100
            $table->decimal('expected_value', 12, 2)->default(0);
            $table->date('expected_close_date')->nullable();

            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
```

database/migrations/2025_10_20_000002_create_opportunity_items_table.php
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('opportunity_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained('opportunities')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();

            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_items');
    }
};
```

### Relacionamentos (conceito)

- Lead belongsTo User (owner)
- Opportunity belongsTo Lead
- Opportunity belongsTo Client
- Opportunity hasMany OpportunityItem
- OpportunityItem belongsTo Product

## 4. Enumerações

Em alinhamento com o padrão do projeto (strings no banco + traduções), sugerimos PHP Enums apenas para domínio/validação e casts do Eloquent (opcional). Nomes em português, valores em inglês snake_case, integrados às mensagens em `lang/pt_BR`.

app/Enums/LeadStatus.php
```php
<?php

namespace App\Enums;

enum LeadStatus: string
{
    case NOVO = 'new';
    case EM_CONTATO = 'in_contact';
    case QUALIFICADO = 'qualified';
    case DESCARTADO = 'discarded';
}
```

app/Enums/OpportunityStage.php
```php
<?php

namespace App\Enums;

enum OpportunityStage: string
{
    case NOVO = 'new';
    case CONTATO = 'contact';
    case PROPOSTA = 'proposal';
    case NEGOCIACAO = 'negotiation';
    case FECHADO_GANHOU = 'won';
    case FECHADO_PERDIDO = 'lost';
}
```

app/Enums/LeadSource.php
```php
<?php

namespace App\Enums;

enum LeadSource: string
{
    case SITE = 'site';
    case INDICACAO = 'indicacao';
    case EVENTO = 'evento';
    case MANUAL = 'manual';
}
```

Exemplo de cast no Model (opcional):
```php
protected $casts = [
    'status' => \App\Enums\LeadStatus::class,
    'source' => \App\Enums\LeadSource::class,
];
```

## 5. Fluxos do CRM

- Fluxo 1 — Lead
  - Cadastra lead.
  - Atribui responsável (owner).
  - Evolui status conforme contato e qualificação.
  - Quando qualificado → converter em Cliente e criar Oportunidade vinculada.

- Fluxo 2 — Oportunidade
  - Criar manualmente (cliente existente) ou via conversão de lead.
  - Associar produtos, valores, probabilidade e etapa.
  - Acompanhar movimentações no funil (stage).
  - Converter em Pedido quando “won” (Fechado — Ganhou).

## 6. Regras de Negócio

- Leads
  - Não podem ser duplicados com mesmo e-mail/telefone (validação + índices únicos).
  - Apenas usuários com permissão podem converter lead em cliente.
  - Leads inativos (status ‘discarded’) não podem gerar novas oportunidades.

- Oportunidades
  - Cliente ou lead inativo → bloqueia criação.
  - Produtos inativos → não podem ser incluídos (mesma lógica de pedidos).
  - Probabilidade coerente com etapa (ex.: ‘won’ = 100, ‘lost’ = 0, limites mínimos por etapa).
  - Ao converter para pedido → gerar Order e vincular `opportunity_id`.
  - Apenas usuários com permissão podem alterar o estágio ou converter.

## 7. Permissões e Policies

Novas permissões (nomes como já usados nas outras policies):

- leads.view, leads.create, leads.edit, leads.delete, leads.convert
- opportunities.view, opportunities.create, opportunities.edit, opportunities.delete, opportunities.change_stage, opportunities.convert_to_order

Integração:
- Adicionar essas permissões no cadastro/edição de usuários (UI já existente).
- Policies verificam `$user->can('<permission>')` alinhando com o middleware `check_policy`.

Registro no AuthServiceProvider (mapeamento Model → Policy):
```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    \App\Models\Lead::class => \App\Policies\LeadPolicy::class,
    \App\Models\Opportunity::class => \App\Policies\OpportunityPolicy::class,
    // ...demais policies já existentes
];
```

Exemplo de Policy:
```php
// app/Policies/LeadPolicy.php
namespace App\Policies;

use App\Models\User;
use App\Models\Lead;

class LeadPolicy
{
    public function viewAny(User $user): bool { return $user->can('leads.view'); }
    public function view(User $user, Lead $lead): bool { return $user->can('leads.view'); }
    public function create(User $user): bool { return $user->can('leads.create'); }
    public function update(User $user, Lead $lead): bool { return $user->can('leads.edit'); }
    public function delete(User $user, Lead $lead): bool { return $user->can('leads.delete'); }
    public function convert(User $user, Lead $lead): bool { return $user->can('leads.convert'); }
}
```

```php
// app/Policies/OpportunityPolicy.php
namespace App\Policies;

use App\Models\User;
use App\Models\Opportunity;

class OpportunityPolicy
{
    public function viewAny(User $user): bool { return $user->can('opportunities.view'); }
    public function view(User $user, Opportunity $o): bool { return $user->can('opportunities.view'); }
    public function create(User $user): bool { return $user->can('opportunities.create'); }
    public function update(User $user, Opportunity $o): bool { return $user->can('opportunities.edit'); }
    public function delete(User $user, Opportunity $o): bool { return $user->can('opportunities.delete'); }
    public function changeStage(User $user, Opportunity $o): bool { return $user->can('opportunities.change_stage'); }
    public function convertToOrder(User $user, Opportunity $o): bool { return $user->can('opportunities.convert_to_order'); }
}
```

## 8. Validações e FormRequests

Requests:
- StoreLeadRequest, UpdateLeadRequest
- StoreOpportunityRequest, UpdateOpportunityRequest

Regras:
- Clientes e produtos devem estar ativos.
- Campos obrigatórios conforme status/etapa.
- Probabilidade depende do stage (ex.: ‘won’ → 100, ‘lost’ → 0; ‘proposal’ ≥ 40; ‘negotiation’ ≥ 60).

Validações customizadas (exemplo de Rules):

app/Rules/ActiveClient.php
```php
<?php

namespace App\Rules;

use App\Models\Client;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ActiveClient implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) return;

        $active = Client::where('id', $value)->where('status', 'active')->exists();
        if (!$active) {
            $fail(__('validation.client_inactive'));
        }
    }
}
```

app/Rules/ActiveProduct.php
```php
<?php

namespace App\Rules;

use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ActiveProduct implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $active = Product::where('id', $value)->where('status', 'active')->exists();
        if (!$active) {
            $fail(__('validation.product_inactive'));
        }
    }
}
```

app/Rules/ProbabilityByStage.php
```php
<?php

namespace App\Rules;

use App\Enums\OpportunityStage;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ProbabilityByStage implements ValidationRule
{
    public function __construct(private readonly ?string $stage) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $p = (int) $value;
        $stage = $this->stage;

        if (!$stage) return;

        if ($stage === OpportunityStage::FECHADO_GANHOU->value && $p !== 100) {
            $fail(__('validation.probability_must_be_100_on_won'));
        } elseif ($stage === OpportunityStage::FECHADO_PERDIDO->value && $p !== 0) {
            $fail(__('validation.probability_must_be_0_on_lost'));
        } elseif ($stage === OpportunityStage::PROPOSTA->value && $p < 40) {
            $fail(__('validation.probability_min_proposal'));
        } elseif ($stage === OpportunityStage::NEGOCIACAO->value && $p < 60) {
            $fail(__('validation.probability_min_negotiation'));
        }
    }
}
```

Exemplo de StoreLeadRequest:

app/Http/Requests/StoreLeadRequest.php
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Lead;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Lead::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:180'],
            'email' => ['nullable','email','max:180','unique:leads,email'],
            'phone' => ['nullable','string','max:30','unique:leads,phone'],
            'company' => ['nullable','string','max:180'],
            'source' => ['required', Rule::in(['site','indicacao','evento','manual'])],
            'status' => ['required', Rule::in(['new','in_contact','qualified','discarded'])],
            'owner_id' => ['required','exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('lead.name_required'),
            // …demais traduções
        ];
    }
}
```

StoreOpportunityRequest (resumo):

```php
public function authorize(): bool
{
    return $this->user()->can('create', \App\Models\Opportunity::class);
}

public function rules(): array
{
    $stage = $this->get('stage');

    return [
        'lead_id' => ['nullable','exists:leads,id'],
        'client_id' => ['nullable','exists:clients,id', new \App\Rules\ActiveClient()],
        'title' => ['required','string','max:200'],
        'description' => ['nullable','string'],
        'stage' => ['required', Rule::in(['new','contact','proposal','negotiation','won','lost'])],
        'probability' => ['required','integer','between:0,100', new \App\Rules\ProbabilityByStage($stage)],
        'expected_value' => ['required','numeric','min:0'],
        'expected_close_date' => ['nullable','date'],
        'owner_id' => ['required','exists:users,id'],
        'status' => ['required', Rule::in(['active','inactive'])],
        'items' => ['array'],
        'items.*.product_id' => ['required','exists:products,id', new \App\Rules\ActiveProduct()],
        'items.*.quantity' => ['required','numeric','min:0.01'],
        'items.*.unit_price' => ['required','numeric','min:0'],
    ];
}
```

## 9. Services / UseCases

Localização sugerida: `app/Services/CRM/`

- CreateLeadService
- ConvertLeadToClientService
- CreateOpportunityService
- ChangeOpportunityStageService
- ConvertOpportunityToOrderService

Requisitos:
- Respeitam regras de domínio (validações adicionais).
- Usam transações (`DB::transaction`) para operações compostas.
- Registram logs (via `Log::info|warning|error`).
- Disparam eventos (LeadCreated, LeadConverted, OpportunityWon, etc.).

Exemplo (CreateOpportunityService):

app/Services/CRM/CreateOpportunityService.php
```php
<?php

namespace App\Services\CRM;

use App\Models\Opportunity;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use DomainException;

class CreateOpportunityService
{
    public function __construct() {}

    public function handle(array $data): Opportunity
    {
        return DB::transaction(function () use ($data) {
            $op = Opportunity::create([
                'lead_id' => $data['lead_id'] ?? null,
                'client_id' => $data['client_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'stage' => $data['stage'],
                'probability' => $data['probability'],
                'expected_value' => $data['expected_value'],
                'expected_close_date' => $data['expected_close_date'] ?? null,
                'owner_id' => $data['owner_id'],
                'status' => $data['status'] ?? 'active',
            ]);

            $sum = 0;
            foreach (($data['items'] ?? []) as $item) {
                $product = Product::where('id', $item['product_id'])->where('status', 'active')->first();
                if (!$product) {
                    throw new DomainException(__('opportunity.product_inactive_or_missing'));
                }

                $subtotal = (float)$item['quantity'] * (float)$item['unit_price'];
                $op->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);
                $sum += $subtotal;
            }

            if ($sum > 0 && (float)$op->expected_value === 0.0) {
                $op->expected_value = $sum;
                $op->save();
            }

            event(new \App\Events\OpportunityCreated($op));
            Log::info('Opportunity created', ['id' => $op->id]);

            return $op;
        });
    }
}
```

Exemplo (ConvertOpportunityToOrderService) — cria Order e vincula `opportunity_id`:
```php
public function handle(Opportunity $op): \App\Models\Order
{
    if ($op->stage !== 'won') {
        throw new \DomainException(__('opportunity.must_be_won_to_convert'));
    }

    return DB::transaction(function () use ($op) {
        $order = \App\Models\Order::create([
            'client_id' => $op->client_id,
            'status' => 'pending', // conforme regras atuais de pedidos
            'opportunity_id' => $op->id,
            // ...campos adicionais obrigatórios
        ]);

        foreach ($op->items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ]);
        }

        event(new \App\Events\OpportunityWon($op, $order));

        return $order;
    });
}
```

## 10. Controllers e Rotas

Padrão REST, retornos JSON em arrays (o projeto já padroniza as respostas via filtro global; caso contrário, o wrapper aplicará o formato final). Controllers em `App\Http\Controllers\Api\CRM`.

Rotas (sugerido criar `routes/api.php`):

routes/api.php
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CRM\LeadController;
use App\Http\Controllers\Api\CRM\OpportunityController;

Route::middleware(['auth', 'check_policy'])
    ->prefix('api')
    ->group(function () {
        // Leads
        Route::get('/leads', [LeadController::class, 'index'])->name('api.leads.index');
        Route::post('/leads', [LeadController::class, 'store'])->name('api.leads.store');
        Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('api.leads.show');
        Route::patch('/leads/{lead}', [LeadController::class, 'update'])->name('api.leads.update');
        Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('api.leads.destroy');
        Route::post('/leads/{lead}/convert', [LeadController::class, 'convert'])->name('api.leads.convert');

        // Opportunities
        Route::get('/opportunities', [OpportunityController::class, 'index'])->name('api.opportunities.index');
        Route::post('/opportunities', [OpportunityController::class, 'store'])->name('api.opportunities.store');
        Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'show'])->name('api.opportunities.show');
        Route::patch('/opportunities/{opportunity}', [OpportunityController::class, 'update'])->name('api.opportunities.update');
        Route::delete('/opportunities/{opportunity}', [OpportunityController::class, 'destroy'])->name('api.opportunities.destroy');
        Route::patch('/opportunities/{opportunity}/stage', [OpportunityController::class, 'changeStage'])->name('api.opportunities.change_stage');
        Route::post('/opportunities/{opportunity}/convert', [OpportunityController::class, 'convertToOrder'])->name('api.opportunities.convert');
    });
```

Exemplo — LeadController (resumo, JSON puro):

app/Http/Controllers/Api/CRM/LeadController.php
```php
<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Models\Lead;
use App\Services\CRM\ConvertLeadToClientService;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Lead::class);

        $leads = Lead::query()
            ->with('owner')
            ->orderByDesc('id')
            ->paginate(15);

        return ['data' => $leads];
    }

    public function store(StoreLeadRequest $request)
    {
        $this->authorize('create', Lead::class);

        $lead = Lead::create($request->validated());
        event(new \App\Events\LeadCreated($lead));

        return ['message' => __('lead.created'), 'data' => $lead];
    }

    public function show(Lead $lead)
    {
        $this->authorize('view', $lead);
        $lead->load('owner');

        return ['data' => $lead];
    }

    public function update(UpdateLeadRequest $request, Lead $lead)
    {
        $this->authorize('update', $lead);
        $lead->update($request->validated());

        return ['message' => __('lead.updated'), 'data' => $lead];
    }

    public function destroy(Lead $lead)
    {
        $this->authorize('delete', $lead);
        $lead->delete();

        return ['message' => __('lead.deleted')];
    }

    public function convert(Lead $lead, ConvertLeadToClientService $service)
    {
        $this->authorize('convert', $lead);
        [$client, $opportunity] = $service->handle($lead);

        return [
            'message' => __('lead.converted'),
            'data' => [
                'client' => $client,
                'opportunity' => $opportunity,
            ],
        ];
    }
}
```

Oportunidades:

app/Http/Controllers/Api/CRM/OpportunityController.php
```php
public function store(StoreOpportunityRequest $request, \App\Services\CRM\CreateOpportunityService $service)
{
    $this->authorize('create', \App\Models\Opportunity::class);
    $op = $service->handle($request->validated());

    return ['message' => __('opportunity.created'), 'data' => $op->load('items')];
}

public function changeStage(\Illuminate\Http\Request $request, \App\Models\Opportunity $opportunity, \App\Services\CRM\ChangeOpportunityStageService $service)
{
    $this->authorize('changeStage', $opportunity);
    $validated = $request->validate(['stage' => ['required', \Illuminate\Validation\Rule::in(['new','contact','proposal','negotiation','won','lost'])]]);
    $opportunity = $service->handle($opportunity, $validated['stage']);

    return ['message' => __('opportunity.stage_changed'), 'data' => $opportunity];
}

public function convertToOrder(\App\Models\Opportunity $opportunity, \App\Services\CRM\ConvertOpportunityToOrderService $service)
{
    $this->authorize('convertToOrder', $opportunity);
    $order = $service->handle($opportunity);

    return ['message' => __('opportunity.converted_to_order'), 'data' => $order->load('items')];
}
```

Observação: o projeto usa Inertia no admin (`routes/web.php`). Estas rotas API podem coexistir (uso interno/AJAX) e serão autenticadas + `check_policy`.

## 11. UI/UX

- Leads:
  - Listagem e detalhes (Admin): nome, e-mail, telefone, origem, status, responsável.
  - Ação de “Converter para Cliente” com confirmação.
  - Filtros por status e responsável.

- Oportunidades:
  - Listagem com colunas: Cliente/Lead, Título, Etapa, Valor Estimado, Probabilidade, Responsável.
  - Visualização em Kanban por etapa (columns: Novo, Contato, Proposta, Negociação, Ganhou, Perdido).
  - Botão “Converter em Pedido” quando etapa = Ganhou.
  - Respeitar Policies e exibir apenas ações permitidas.

## 12. Logs e Auditoria

- Registrar: criação, alteração, conversão e exclusão de leads e oportunidades; mudanças de estágio, responsável e status.
- Padrão atual: uso de `Log::info|warning|error` (já utilizado no projeto) + Events.
- Opcional: Observers dedicados para Lead/Opportunity para logging consistente.

## 13. Testes

Cobertura recomendada:
- Feature:
  - Criar e editar lead.
  - Converter lead em cliente (gera Cliente e Oportunidade).
  - Criar oportunidade com itens.
  - Converter oportunidade em pedido (gera Order e vincula `opportunity_id`).
- Unit:
  - Regras (ActiveClient, ActiveProduct, ProbabilityByStage).
  - Services (CreateOpportunityService, ConvertOpportunityToOrderService, etc.).
- Policy:
  - Permissões de visualização/edição/alteração de estágio/conversão.
- Seeders/Factories:
  - Leads ativos/inativos (status).
  - Clientes e produtos ativos/inativos.

Exemplo de teste de feature (resumo):

tests/Feature/CRM/ConvertLeadToClientTest.php
```php
public function test_user_with_permission_can_convert_lead()
{
    $user = \App\Models\User::factory()->create();
    // conceder permissão leads.convert ao usuário (UI/Admin já existente)
    $lead = \App\Models\Lead::factory()->create(['status' => 'qualified', 'owner_id' => $user->id]);

    $this->actingAs($user)
        ->postJson(route('api.leads.convert', $lead))
        ->assertOk()
        ->assertJsonStructure(['message', 'data' => ['client' , 'opportunity']]);
}
```

## 14. Impactos e Integrações

- Migrations novas para leads/opportunities/opportunity_items.
- Integração com Clientes, Produtos e Pedidos (FKs, validações de ativo/inativo).
- Ajustes em Seeds (criar permissões e perfis com novas chaves).
- Performance: adicionar índices adequados (e.g. `owner_id`, `status`, `stage`).
- Deploy: migrar base de dados de forma incremental (`php artisan migrate`).

## 15. Futuras Extensões

- Dashboard de conversão e funil (métricas por etapa, taxa de ganho/perda).
- Automação de follow-up e lembretes por etapa.
- Integração com e-mail marketing / WhatsApp API (opt-in).
- Sincronização com CRMs externos (HubSpot, RD Station).

## 16. Conclusão

A proposta integra Leads e Oportunidades ao ecossistema atual com Controllers, FormRequests, Services, Policies e validações no mesmo padrão já consolidado. Benefícios:

- Estrutura modular e escalável.
- Rastreabilidade do funil e previsibilidade de vendas.
- Alinhamento total com arquitetura e convenções existentes.

Mensagens e labels em `lang/pt_BR`, responses JSON padronizadas por filtro global, FKs com `restrictOnDelete`, permissões gerenciáveis na UI de administradores, e testes cobrindo fluxo de conversão.

