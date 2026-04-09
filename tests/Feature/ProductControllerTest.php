<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($this->admin);
        App::setLocale('pt_BR');
    }

    public function test_index_displays_products_list()
    {
        Product::factory()->count(3)->create();

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Products/Index')
                ->has('products')
                ->has('filters')
        );
    }

    public function test_index_filters_by_search()
    {
        Product::factory()->create(['name' => 'Arroz Especial']);
        Product::factory()->create(['name' => 'Feijão Premium']);

        $response = $this->get(route('products.index', ['search' => 'Arroz']));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Products/Index')
                ->where('filters.search', 'Arroz')
        );
    }

    public function test_index_filters_by_status()
    {
        Product::factory()->create(['status' => 'active']);
        Product::factory()->create(['status' => 'inactive']);

        $response = $this->get(route('products.index', ['status' => 'inactive']));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Products/Index')
                ->where('filters.status', 'inactive')
        );
    }

    public function test_create_displays_form_with_products()
    {
        Product::factory()->count(2)->create();

        $response = $this->get(route('products.create'));
        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Products/Create')
                ->has('products')
        );
    }

    public function test_store_creates_product_with_components()
    {
        $rice = Product::factory()->create(['name' => 'Arroz', 'price' => 6.00, 'unit_of_measure' => 'PCT', 'status' => 'active']);
        $beans = Product::factory()->create(['name' => 'Feijão', 'price' => 5.00, 'unit_of_measure' => 'PCT', 'status' => 'active']);

        $payload = [
            'name' => 'PF Arroz e Feijão',
            'description' => 'Prato feito com arroz e feijão',
            'price' => 20.50,
            'unit_of_measure' => 'UND',
            'status' => 'active',
            'length' => 20,
            'width' => 15,
            'height' => 6,
            'weight' => 1.2,
            'components' => [
                ['id' => $rice->id, 'quantity' => 1],
                ['id' => $beans->id, 'quantity' => 1],
            ],
        ];

        $response = $this->post(route('products.store'), $payload);
        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('status', 'Produto criado com sucesso!');

        $this->assertDatabaseHas('products', [
            'name' => 'PF Arroz e Feijão',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $product = Product::where('name', 'PF Arroz e Feijão')->first();
        $this->assertNotNull($product);
        $this->assertEquals(2, $product->components()->count());
        $this->assertEquals(1, $product->components()->where('products.id', $rice->id)->first()->pivot->quantity);
    }

    public function test_store_creates_product_without_components()
    {
        $payload = [
            'name' => 'Água Mineral',
            'description' => 'Garrafa de água 500ml',
            'price' => 3.50,
            'unit_of_measure' => 'UND',
            'status' => 'active',
        ];

        $response = $this->post(route('products.store'), $payload);
        $response->assertRedirect(route('products.index'));

        $product = Product::where('name', 'Água Mineral')->first();
        $this->assertNotNull($product);
        $this->assertEquals(0, $product->components()->count());
    }

    public function test_edit_displays_product_with_components_sorted_by_name()
    {
        $product = Product::factory()->create(['name' => 'PF']);
        $c1 = Product::factory()->create(['name' => 'Zeta Componente']);
        $c2 = Product::factory()->create(['name' => 'Alpha Componente']);
        $product->components()->sync([
            $c1->id => ['quantity' => 1],
            $c2->id => ['quantity' => 2],
        ]);

        $response = $this->get(route('products.edit', $product));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Products/Edit')
                ->where('product.id', $product->id)
                ->where('product.components.0.name', 'Alpha Componente')
        );
    }

    public function test_update_updates_product_and_syncs_components_and_defaults_status_when_omitted()
    {
        $product = Product::factory()->create(['status' => 'inactive']);
        $rice = Product::factory()->create(['name' => 'Arroz', 'price' => 6.00, 'unit_of_measure' => 'PCT']);
        // Adiciona componente antes
        $product->components()->sync([$rice->id => ['quantity' => 1]]);
        $product->load('components');
        $pivotId = $product->components()->first()->pivot->id;

        $payload = [
            'name' => 'PF Atualizado',
            'description' => 'Desc atualizada',
            'price' => 25.00,
            'unit_of_measure' => 'UND',
            // status omitido -> deve virar 'active' pelo controller
            'components' => [
                ['id' => $rice->id, 'quantity' => 3, 'pivot_id' => $pivotId],
            ],
        ];

        $response = $this->patch(route('products.update', $product), $payload);

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('status', 'Produto atualizado com sucesso!');

        $product->refresh();
        $this->assertEquals('PF Atualizado', $product->name);
        $this->assertEquals('active', $product->status); // default aplicado
        $this->assertEquals($this->admin->id, $product->updated_by);
        $this->assertEquals(1, $product->components()->count());
        $this->assertEquals(3, $product->components()->first()->pivot->quantity);
    }

    public function test_update_detaches_components_when_not_provided()
    {
        $product = Product::factory()->create();
        $comp = Product::factory()->create();
        $product->components()->sync([$comp->id => ['quantity' => 2]]);

        $payload = [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'unit_of_measure' => $product->unit_of_measure,
            // components omitido
        ];

        $response = $this->patch(route('products.update', $product), $payload);
        $response->assertRedirect(route('products.index'));

        $product->refresh();
        $this->assertEquals(0, $product->components()->count());
    }

    public function test_destroy_deletes_product()
    {
        $product = Product::factory()->create();
        $response = $this->delete(route('products.destroy', $product));
        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('status', 'Produto removido com sucesso!');
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_destroy_blocks_deleting_product_with_orders()
    {
        $product = Product::factory()->create();
        $client = Client::factory()->create();
        $order = Order::factory()->create(['client_id' => $client->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

        $response = $this->delete(route('products.destroy', $product));
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Produto possui pedidos e não pode ser excluído.');
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_destroy_allows_deleting_product_without_orders()
    {
        $product = Product::factory()->create();
        $response = $this->delete(route('products.destroy', $product));
        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('status', 'Produto removido com sucesso!');
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_modal_returns_product_data_with_component_tree()
    {
        $root = Product::factory()->create([
            'name' => 'Marmita Especial',
            'price' => 30.00,
            'unit_of_measure' => 'UND',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $c1 = Product::factory()->create(['name' => 'Arroz', 'price' => 6.00, 'unit_of_measure' => 'PCT']);
        $c2 = Product::factory()->create(['name' => 'Feijão', 'price' => 5.50, 'unit_of_measure' => 'PCT']);
        $c21 = Product::factory()->create(['name' => 'Tempero', 'price' => 1.00, 'unit_of_measure' => 'PCT']);

        // Root components
        $root->components()->sync([
            $c1->id => ['quantity' => 2],
            $c2->id => ['quantity' => 1],
        ]);
        // Nested component for c2
        $c2->components()->sync([$c21->id => ['quantity' => 3]]);

        $response = $this->get(route('products.modal', $root));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'product' => [
                'id',
                'name',
                'description',
                'price',
                'unit_of_measure',
                'status',
                'created_at',
                'updated_at',
                'created_by',
                'updated_by',
                'components',
                'component_tree',
            ],
        ]);

        $response->assertJson([
            'product' => [
                'id' => $root->id,
                'name' => 'Marmita Especial',
                'price' => 'R$ 30,00',
                'unit_of_measure' => 'UND',
                'created_by' => $this->admin->name,
                'updated_by' => $this->admin->name,
            ],
        ]);

        // Check component mapping (price formatted and totals)
        $resp = $response->json('product');
        $components = collect($resp['components']);
        $rice = $components->firstWhere('name', 'Arroz');
        $beans = $components->firstWhere('name', 'Feijão');
        $this->assertEquals('R$ 6,00', $rice['price']);
        $this->assertEquals('R$ 12,00', $rice['total']); // 6 * 2
        $this->assertEquals('R$ 5,50', $beans['price']);
        $this->assertEquals('R$ 5,50', $beans['total']); // 5.5 * 1

        // Check component tree levels
        $tree = collect($resp['component_tree']);
        $beansNode = $tree->firstWhere('name', 'Feijão');
        $this->assertEquals(0, $beansNode['level']);
        $this->assertTrue($beansNode['has_children']);
        $this->assertCount(1, $beansNode['children']);
        $this->assertEquals('Tempero', $beansNode['children'][0]['name']);
        $this->assertEquals(1, $beansNode['children'][0]['level']);
    }

    public function test_modal_component_tree_avoids_cycles()
    {
        $a = Product::factory()->create(['name' => 'A']);
        $b = Product::factory()->create(['name' => 'B']);
        $a->components()->sync([$b->id => ['quantity' => 1]]);
        $b->components()->sync([$a->id => ['quantity' => 1]]); // ciclo

        $response = $this->get(route('products.modal', $a));
        $response->assertStatus(200);
        $tree = collect($response->json('product.component_tree'));
        $bNode = $tree->firstWhere('name', 'B');
        $this->assertNotNull($bNode);
        // Cycle prevention should cut recursion at A, yielding one child with empty children
        $this->assertIsArray($bNode['children']);
        $this->assertCount(1, $bNode['children']);
        $this->assertEquals('A', $bNode['children'][0]['name']);
        $this->assertIsArray($bNode['children'][0]['children']);
        $this->assertCount(0, $bNode['children'][0]['children']);
    }

    public function test_non_admin_without_permissions_cannot_access_index()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => [
                'products' => [
                    'view' => false,
                    'create' => false,
                    'update' => false,
                    'delete' => false,
                ],
            ],
        ]);
        $this->actingAs($user);

        $this->get(route('products.index'))->assertStatus(403);
    }

    public function test_store_denies_creating_product_with_inactive_component()
    {
        $activeProduct = Product::factory()->create(['status' => 'active']);
        $inactiveProduct = Product::factory()->create(['status' => 'inactive']);

        $data = [
            'name' => 'Produto Composto',
            'price' => 100.00,
            'unit_of_measure' => 'UND',
            'status' => 'active',
            'components' => [
                ['id' => $inactiveProduct->id, 'quantity' => 1.0],
            ],
        ];

        $response = $this->post(route('products.store'), $data);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('components.0.id');
        $this->assertDatabaseMissing('products', ['name' => 'Produto Composto']);
    }

    public function test_store_allows_creating_product_with_only_active_components()
    {
        $activeProduct1 = Product::factory()->create(['status' => 'active']);
        $activeProduct2 = Product::factory()->create(['status' => 'active']);

        $data = [
            'name' => 'Produto Composto',
            'price' => 100.00,
            'unit_of_measure' => 'UND',
            'status' => 'active',
            'components' => [
                ['id' => $activeProduct1->id, 'quantity' => 1.0],
                ['id' => $activeProduct2->id, 'quantity' => 2.0],
            ],
        ];

        $response = $this->post(route('products.store'), $data);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['name' => 'Produto Composto']);
        $product = Product::where('name', 'Produto Composto')->first();
        $this->assertCount(2, $product->components);
    }

    public function test_update_allows_editing_existing_item_when_component_is_now_inactive_without_replacing_it()
    {
        $product = Product::factory()->create(['status' => 'active']);
        $component = Product::factory()->create(['status' => 'active']);
        $product->components()->attach($component->id, ['quantity' => 1.0]);
        $product->load('components');
        $pivotId = $product->components()->first()->pivot->id;

        // Change component to inactive
        $component->update(['status' => 'inactive']);

        $data = [
            'name' => 'Produto Atualizado',
            'price' => 150.00,
            'unit_of_measure' => 'UND',
            'status' => 'active',
            'components' => [
                ['pivot_id' => $pivotId, 'id' => $component->id, 'quantity' => 2.0],
            ],
        ];

        $response = $this->patch(route('products.update', $product), $data);

        $response->assertRedirect(route('products.index'));
        $product->refresh();
        $this->assertEquals('Produto Atualizado', $product->name);
        $this->assertEquals(2.0, $product->components()->first()->pivot->quantity);
    }

    public function test_update_denies_adding_new_inactive_component_to_product()
    {
        $product = Product::factory()->create(['status' => 'active']);
        $activeComponent = Product::factory()->create(['status' => 'active']);
        $inactiveComponent = Product::factory()->create(['status' => 'inactive']);

        $data = [
            'name' => 'Produto Atualizado',
            'price' => 150.00,
            'unit_of_measure' => 'UND',
            'status' => 'active',
            'components' => [
                ['id' => $activeComponent->id, 'quantity' => 1.0],
                ['id' => $inactiveComponent->id, 'quantity' => 1.0],
            ],
        ];

        $response = $this->patch(route('products.update', $product), $data);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('components.1.id');
    }

    public function test_update_denies_replacing_component_with_inactive_product()
    {
        $product = Product::factory()->create(['status' => 'active']);
        $oldComponent = Product::factory()->create(['status' => 'active']);
        $newInactiveComponent = Product::factory()->create(['status' => 'inactive']);
        $product->components()->attach($oldComponent->id, ['quantity' => 1.0]);

        $data = [
            'name' => 'Produto Atualizado',
            'price' => 150.00,
            'unit_of_measure' => 'UND',
            'status' => 'active',
            'components' => [
                ['pivot_id' => $product->components()->first()->pivot->id, 'id' => $newInactiveComponent->id, 'quantity' => 1.0],
            ],
        ];

        $response = $this->patch(route('products.update', $product), $data);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('components.0.id');
    }

    public function test_update_allows_updating_quantity_of_item_with_inactive_component_without_changing_component()
    {
        $product = Product::factory()->create(['status' => 'active']);
        $component = Product::factory()->create(['status' => 'inactive']);
        $product->components()->attach($component->id, ['quantity' => 1.0]);
        $product->load('components');
        $pivotId = $product->components()->first()->pivot->id;

        $data = [
            'name' => 'Produto Atualizado',
            'price' => 150.00,
            'unit_of_measure' => 'UND',
            'status' => 'active',
            'components' => [
                ['pivot_id' => $pivotId, 'id' => $component->id, 'quantity' => 3.0],
            ],
        ];

        $response = $this->patch(route('products.update', $product), $data);

        $response->assertRedirect(route('products.index'));
        $product->refresh();
        $this->assertEquals(3.0, $product->components()->first()->pivot->quantity);
    }
}
