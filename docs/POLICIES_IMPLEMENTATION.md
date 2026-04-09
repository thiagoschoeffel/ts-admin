# Implementação de Laravel Policies

Este documento explica como foi implementado o sistema de Laravel Policies na aplicação TSAdmin para substituir as verificações manuais de permissões e proporcionar maior organização e segurança.

## Estrutura Implementada

### 1. Policies Criadas

#### UserPolicy (`app/Policies/UserPolicy.php`)
- **Finalidade**: Controla acesso ao módulo de usuários (apenas administradores)
- **Métodos**:
  - `viewAny()`: Permite listar usuários (apenas admin)
  - `view()`: Permite visualizar usuário específico (apenas admin)
  - `create()`: Permite criar usuários (apenas admin)
  - `update()`: Permite editar usuários (apenas admin, exceto próprio usuário)
  - `delete()`: Permite excluir usuários (apenas admin, exceto próprio usuário)

#### ClientPolicy (`app/Policies/ClientPolicy.php`)
- **Finalidade**: Controla acesso ao módulo de clientes baseado em permissões granulares
- **Métodos CRUD padrão**:
  - `viewAny()`: Requer permissão `clients.view`
  - `view()`: Requer permissão `clients.view`
  - `create()`: Requer permissão `clients.create`
  - `update()`: Requer permissão `clients.update`
  - `delete()`: Requer permissão `clients.delete`
- **Métodos para endereços**:
  - `manageAddresses()`: Requer permissão `clients.view`
  - `createAddress()`: Requer permissão `clients.create` ou `clients.update`
  - `updateAddress()`: Requer permissão `clients.update`
  - `deleteAddress()`: Requer permissão `clients.update` ou `clients.delete`

#### ProductPolicy (`app/Policies/ProductPolicy.php`)
- **Finalidade**: Controla acesso ao módulo de produtos
- **Métodos CRUD**: Baseados nas permissões `products.*`
- **Métodos para componentes**: Controle granular de componentes de produtos

#### OrderPolicy (`app/Policies/OrderPolicy.php`)
- **Finalidade**: Controla acesso ao módulo de pedidos
- **Métodos CRUD**: Baseados nas permissões `orders.*`
- **Métodos para itens**: Controle granular de itens de pedidos

#### AddressPolicy (`app/Policies/AddressPolicy.php`)
- **Finalidade**: Controla endereços vinculados às permissões de clientes
- **Métodos**: Todos baseados nas permissões `clients.*`

### 2. AuthServiceProvider

Criado o `AuthServiceProvider` (`app/Providers/AuthServiceProvider.php`) para registrar todas as policies:

```php
protected $policies = [
    User::class => UserPolicy::class,
    Client::class => ClientPolicy::class,
    Product::class => ProductPolicy::class,
    Order::class => OrderPolicy::class,
    Address::class => AddressPolicy::class,
];
```

### 3. Controladores Atualizados

Todos os controladores foram atualizados para usar `$this->authorize()` ao invés de `abort_unless()` com verificações manuais:

**Antes:**
```php
abort_unless(Auth::user()->canManage('clients', 'view'), 403);
```

**Depois:**
```php
$this->authorize('view', $client);
```

### 4. FormRequests Atualizados

Classes de Request agora usam policies para autorização:

**Antes:**
```php
public function authorize(): bool
{
    return $this->user()->canManage('clients', 'create');
}
```

**Depois:**
```php
public function authorize(): bool
{
    $client = $this->route('client');
    return $this->user()->can('createAddress', $client);
}
```

### 5. Middleware Personalizado

Criado middleware `CheckPolicy` (`app/Http/Middleware/CheckPolicy.php`) para aplicar policies diretamente nas rotas:

```php
// Exemplo de uso
Route::get('/clients', [ClientController::class, 'index'])
    ->middleware('check_policy:viewAny,Client');
```

## Vantagens da Implementação

### 1. **Organização e Manutenibilidade**
- Lógica de autorização centralizada em policies
- Separação clara de responsabilidades
- Mais fácil de testar e manter

### 2. **Flexibilidade e Expressividade**
- Métodos com nomes descritivos (`canCreateAddress`, `canManageComponents`)
- Suporte a lógicas complexas de autorização
- Reutilização de policies em diferentes contextos

### 3. **Integração com o Ecossistema Laravel**
- Uso nativo de `$this->authorize()` nos controladores
- Integração com `can()` e `cannot()` helpers
- Suporte para middleware de autorização

### 4. **Segurança Aprimorada**
- Menor chance de esquecimento de verificações
- Aplicação consistente de regras
- Verificações automáticas em FormRequests

## Como Usar

### Em Controladores
```php
public function index()
{
    $this->authorize('viewAny', Client::class);
    // ...
}

public function show(Client $client)
{
    $this->authorize('view', $client);
    // ...
}
```

### Em Blade/Inertia (Frontend)
```php
@can('create', App\Models\Client::class)
    <button>Criar Cliente</button>
@endcan

@can('update', $client)
    <button>Editar</button>
@endcan
```

### Em Middleware de Rotas
```php
Route::prefix('clients')
    ->middleware('check_policy:viewAny,Client')
    ->group(function () {
        Route::get('/', [ClientController::class, 'index']);
        Route::get('create', [ClientController::class, 'create'])
            ->middleware('check_policy:create,Client');
    });
```

### Programaticamente
```php
if (auth()->user()->can('create', Client::class)) {
    // Usuário pode criar clientes
}

if (auth()->user()->cannot('delete', $client)) {
    // Usuário não pode excluir este cliente
}
```

## Estrutura de Permissões Preservada

O sistema continua usando as permissões JSON armazenadas no banco de dados (`users.permissions`), mas agora elas são acessadas **apenas através das policies**. 

### ⚠️ MUDANÇA IMPORTANTE

O método `canManage()` do modelo `User` está **DEPRECATED** e não deve ser usado diretamente:

```php
// ❌ NÃO FAZER MAIS
if (Auth::user()->canManage('clients', 'view')) {
    // ...
}

// ✅ FAZER AGORA
if (auth()->user()->can('viewAny', Client::class)) {
    // ...
}
```

O método `canManage()` foi mantido apenas para uso **interno das policies** através do método privado `hasPermission()`. Ele está marcado com `@deprecated` e `@internal` para indicar que não deve ser chamado diretamente.

### Estrutura JSON Mantida

A estrutura JSON de permissões no banco de dados permanece exatamente igual:

```json
{
    "clients": {
        "view": true,
        "create": true,
        "update": false,
        "delete": false
    },
    "products": {
        "view": true,
        "create": false,
        "update": false,
        "delete": false
    }
}
```

As policies leem essas permissões internamente, proporcionando uma camada de abstração organizada.

## Exemplo de Rota Completa com Policies

Veja o arquivo `routes/web_with_policies_example.php` para um exemplo completo de como organizar todas as rotas usando policies e middleware.

## Próximos Passos Recomendados

1. **Testes**: Criar testes unitários para todas as policies
2. **Documentação**: Documentar policies específicas por módulo
3. **Middleware Global**: Considerar aplicar middleware de policy em grupos de rotas
4. **Cache**: Implementar cache de permissões para otimização se necessário
5. **Auditoria**: Adicionar logs de tentativas de acesso negado

## Migração Gradual

A implementação permite migração gradual:
1. Policies já estão criadas e registradas
2. Controladores principais já foram atualizados
3. Middleware está disponível para uso nas rotas
4. Sistema antigo continua funcionando até substituição completa

Esta implementação mantém toda a funcionalidade existente enquanto proporciona uma base sólida e extensível para controle de permissões granular.
