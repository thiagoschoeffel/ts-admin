# Guia de Migração: De canManage() para Laravel Policies

## ⚠️ IMPORTANTE - MUDANÇAS BREAKING

O método `canManage()` do modelo `User` está **DEPRECATED** e não deve mais ser usado diretamente no código da aplicação. Agora todas as verificações de permissão devem ser feitas através de **Laravel Policies**.

## O que mudou?

### Antes (❌ Não usar mais)
```php
// Em Controladores
abort_unless(Auth::user()->canManage('clients', 'view'), 403);

// Em FormRequests
public function authorize(): bool
{
    return $this->user()->canManage('clients', 'create');
}

// Em Blade/Inertia
@if(Auth::user()->canManage('clients', 'update'))
    <button>Editar</button>
@endif
```

### Depois (✅ Usar agora)
```php
// Em Controladores
$this->authorize('viewAny', Client::class);
$this->authorize('view', $client);
$this->authorize('update', $client);

// Em FormRequests
public function authorize(): bool
{
    $client = $this->route('client');
    return $this->user()->can('update', $client);
}

// Em Blade/Inertia
@can('update', $client)
    <button>Editar</button>
@endcan

// Verificação programática
if (auth()->user()->can('create', Client::class)) {
    // ...
}
```

## Mapeamento de Permissões

### Módulo de Clientes
| Antes | Depois |
|-------|--------|
| `canManage('clients', 'view')` | `$user->can('viewAny', Client::class)` ou `$user->can('view', $client)` |
| `canManage('clients', 'create')` | `$user->can('create', Client::class)` |
| `canManage('clients', 'update')` | `$user->can('update', $client)` |
| `canManage('clients', 'delete')` | `$user->can('delete', $client)` |

### Módulo de Produtos
| Antes | Depois |
|-------|--------|
| `canManage('products', 'view')` | `$user->can('viewAny', Product::class)` ou `$user->can('view', $product)` |
| `canManage('products', 'create')` | `$user->can('create', Product::class)` |
| `canManage('products', 'update')` | `$user->can('update', $product)` |
| `canManage('products', 'delete')` | `$user->can('delete', $product)` |

### Módulo de Pedidos
| Antes | Depois |
|-------|--------|
| `canManage('orders', 'view')` | `$user->can('viewAny', Order::class)` ou `$user->can('view', $order)` |
| `canManage('orders', 'create')` | `$user->can('create', Order::class)` |
| `canManage('orders', 'update')` | `$user->can('update', $order)` |
| `canManage('orders', 'delete')` | `$user->can('delete', $order)` |

### Módulo de Usuários (Admin apenas)
| Antes | Depois |
|-------|--------|
| `$user->isAdmin()` | `$user->can('viewAny', User::class)` |
| `$user->isAdmin()` | `$user->can('create', User::class)` |
| `$user->isAdmin()` | `$user->can('update', $targetUser)` |
| `$user->isAdmin()` | `$user->can('delete', $targetUser)` |

## Policies Personalizadas

Além dos métodos CRUD padrão, as policies incluem métodos personalizados:

### ClientPolicy
- `manageAddresses($client)` - Gerenciar endereços de um cliente
- `createAddress($client)` - Criar endereço para um cliente
- `updateAddress($client)` - Atualizar endereço de um cliente
- `deleteAddress($client)` - Deletar endereço de um cliente

### ProductPolicy
- `manageComponents($product)` - Gerenciar componentes de um produto
- `createComponent($product)` - Adicionar componente a um produto
- `updateComponent($product)` - Atualizar componente de um produto
- `deleteComponent($product)` - Remover componente de um produto

### OrderPolicy
- `manageItems($order)` - Gerenciar itens de um pedido
- `addItem($order)` - Adicionar item a um pedido
- `updateItem($order)` - Atualizar item de um pedido
- `removeItem($order)` - Remover item de um pedido

## Exemplos de Uso

### Em Controladores

```php
class ClientController extends Controller
{
    public function index()
    {
        // Verificar se pode listar clientes
        $this->authorize('viewAny', Client::class);
        
        $clients = Client::paginate(10);
        return view('clients.index', compact('clients'));
    }
    
    public function show(Client $client)
    {
        // Verificar se pode ver este cliente específico
        $this->authorize('view', $client);
        
        return view('clients.show', compact('client'));
    }
    
    public function update(Request $request, Client $client)
    {
        // Verificar se pode atualizar este cliente
        $this->authorize('update', $client);
        
        $client->update($request->validated());
        return redirect()->route('clients.index');
    }
}
```

### Em FormRequests

```php
class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');
        return $this->user()->can('update', $client);
    }
    
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            // ...
        ];
    }
}
```

### Em Middleware (Rotas)

```php
// No arquivo web.php
Route::middleware('check_policy:viewAny,Client')->group(function () {
    Route::get('/clients', [ClientController::class, 'index']);
    Route::get('/clients/{client}', [ClientController::class, 'show'])
        ->middleware('check_policy:view');
});
```

### Em Views (Blade)

```blade
@can('create', App\Models\Client::class)
    <a href="{{ route('clients.create') }}">Novo Cliente</a>
@endcan

@can('update', $client)
    <a href="{{ route('clients.edit', $client) }}">Editar</a>
@endcan

@can('delete', $client)
    <button>Excluir</button>
@endcan
```

### Em Componentes Vue/Inertia

```vue
<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const permissions = computed(() => page.props.auth.permissions);

// Verificar permissões
const canCreateClients = computed(() => {
    return permissions.value?.clients?.create === true;
});

const canUpdateClients = computed(() => {
    return permissions.value?.clients?.update === true;
});
</script>

<template>
    <button v-if="canCreateClients">Criar Cliente</button>
    <button v-if="canUpdateClients">Editar Cliente</button>
</template>
```

## Por que essa mudança?

### Vantagens das Policies

1. **Organização**: Lógica de autorização centralizada em uma classe dedicada
2. **Testabilidade**: Mais fácil de testar policies isoladamente
3. **Manutenibilidade**: Mudanças de regras em um único lugar
4. **Expressividade**: Métodos com nomes descritivos (`canCreateAddress` vs `canManage('clients', 'create')`)
5. **Integração**: Funciona nativamente com todo o ecossistema Laravel
6. **Flexibilidade**: Permite lógicas complexas de autorização (ex: verificar propriedade do recurso)

### Status do canManage()

O método `canManage()` ainda existe no modelo `User`, mas está marcado como **@deprecated** e **@internal**. Ele é usado **apenas internamente pelas policies** para verificar as permissões granulares armazenadas no banco de dados.

```php
// No User.php - NÃO CHAMAR DIRETAMENTE
/**
 * @deprecated Use Laravel Policies instead
 * @internal This method should only be called from Policy classes
 */
public function canManage(string $resource, string $ability): bool
{
    return $this->hasPermission($resource, $ability);
}
```

## Checklist de Migração

Para migrar código existente:

- [ ] Substituir todas as chamadas `canManage()` em controladores por `$this->authorize()`
- [ ] Atualizar todos os `FormRequest::authorize()` para usar `$user->can()`
- [ ] Atualizar verificações em views Blade para usar `@can` / `@cannot`
- [ ] Atualizar verificações em componentes Vue/Inertia
- [ ] Remover imports desnecessários de `Auth` facade onde não é mais necessário
- [ ] Testar todos os endpoints para garantir que as permissões continuam funcionando
- [ ] Atualizar testes unitários e de integração

## Arquivos Afetados

### Controladores Atualizados
- ✅ `UserManagementController.php`
- ✅ `ClientController.php`
- ✅ `AddressController.php`
- ✅ `ProductController.php`
- ✅ `ProductComponentController.php`
- ✅ `OrderController.php`

### FormRequests Atualizados
- ✅ `StoreAddressRequest.php`
- ✅ `UpdateAddressRequest.php`

### Novos Arquivos
- ✅ `app/Policies/UserPolicy.php`
- ✅ `app/Policies/ClientPolicy.php`
- ✅ `app/Policies/ProductPolicy.php`
- ✅ `app/Policies/OrderPolicy.php`
- ✅ `app/Policies/AddressPolicy.php`
- ✅ `app/Providers/AuthServiceProvider.php`
- ✅ `app/Http/Middleware/CheckPolicy.php`

## Suporte

Se você encontrar problemas durante a migração ou tiver dúvidas:

1. Consulte a [documentação do Laravel sobre Authorization](https://laravel.com/docs/authorization)
2. Veja exemplos práticos em `docs/POLICIES_IMPLEMENTATION.md`
3. Verifique as policies em `app/Policies/` para entender a lógica implementada

## Estrutura de Permissões Mantida

**IMPORTANTE**: A estrutura JSON de permissões no banco de dados (`users.permissions`) permanece **exatamente a mesma**. Apenas mudou a forma de verificar essas permissões (de `canManage()` direto para Policies).

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

As policies leem essas permissões através do método interno `hasPermission()` do modelo User.
