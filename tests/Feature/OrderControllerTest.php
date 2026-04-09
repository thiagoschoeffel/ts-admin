<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Product;
use App\Models\Order;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user for testing
        $this->user = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $this->actingAs($this->user);
    }

    public function test_index_displays_orders_with_filters()
    {
        // Create test data
        $client = Client::factory()->create();
        $product = Product::factory()->create(['status' => 'active']);
        $address = Address::factory()->create(['client_id' => $client->id]);

        $order = Order::factory()->create([
            'client_id' => $client->id,
            'user_id' => $this->user->id,
            'address_id' => $address->id,
        ]);

        $response = $this->get(route('orders.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Orders/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.id', $order->id)
                ->where('orders.data.0.client.name', $client->name)
        );
    }

    public function test_index_filters_by_search()
    {
        $client1 = Client::factory()->create(['name' => 'John Doe']);
        $client2 = Client::factory()->create(['name' => 'Jane Smith']);

        Order::factory()->create(['client_id' => $client1->id, 'user_id' => $this->user->id]);
        Order::factory()->create(['client_id' => $client2->id, 'user_id' => $this->user->id]);

        $response = $this->get(route('orders.index', ['search' => 'John']));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Orders/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.client.name', 'John Doe')
        );
    }

    public function test_index_filters_by_status()
    {
        Order::factory()->create(['status' => 'pending', 'user_id' => $this->user->id]);
        Order::factory()->create(['status' => 'completed', 'user_id' => $this->user->id]);

        $response = $this->get(route('orders.index', ['status' => 'pending']));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Orders/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.status', 'pending')
        );
    }

    public function test_index_handles_invalid_date_formats()
    {
        // Create test data
        Order::factory()->create(['user_id' => $this->user->id]);

        // Test with invalid date formats - should not crash and return orders
        $response = $this->get(route('orders.index', [
            'ordered_from' => 'invalid-date',
            'ordered_to' => 'another-invalid-date'
        ]));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Orders/Index')
                ->has('orders')
        );
    }

    public function test_index_filters_by_date_range()
    {
        // Create orders with different ordered_at dates
        $order1 = Order::factory()->create([
            'user_id' => $this->user->id,
            'ordered_at' => now()->subDays(5), // 5 days ago
        ]);
        $order2 = Order::factory()->create([
            'user_id' => $this->user->id,
            'ordered_at' => now()->subDays(2), // 2 days ago
        ]);
        $order3 = Order::factory()->create([
            'user_id' => $this->user->id,
            'ordered_at' => now()->addDays(1), // future
        ]);

        // Test filtering with both from and to dates (should cover whereBetween)
        $response = $this->get(route('orders.index', [
            'ordered_from' => now()->subDays(4)->format('Y-m-d H:i'), // 4 days ago
            'ordered_to' => now()->subDays(1)->format('Y-m-d H:i'),   // 1 day ago
        ]));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Orders/Index')
                ->has('orders.data', 1) // Should only return order2
                ->where('orders.data.0.id', $order2->id)
        );

        // Test filtering with only from date (should cover where >=)
        $response = $this->get(route('orders.index', [
            'ordered_from' => now()->subDays(3)->format('Y-m-d H:i'), // 3 days ago
        ]));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Orders/Index')
                ->has('orders.data', 2) // Should return order2 and order3
        );

        // Test filtering with only to date (should cover where <=)
        $response = $this->get(route('orders.index', [
            'ordered_to' => now()->subDays(3)->format('Y-m-d H:i'), // 3 days ago
        ]));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Orders/Index')
                ->has('orders.data', 1) // Should only return order1
                ->where('orders.data.0.id', $order1->id)
        );
    }

    public function test_create_displays_form_with_data()
    {
        $client = Client::factory()->create(['status' => 'active']);
        $product = Product::factory()->create(['status' => 'active']);
        $address = Address::factory()->create(['client_id' => $client->id]);

        $response = $this->get(route('orders.create'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Orders/Create')
                ->has('products')
                ->has('clients')
                ->has('addresses')
                ->has('recentOrders')
        );
    }

    public function test_create_displays_form_with_recent_orders()
    {
        // Create recent orders to test the mapping logic
        $client = Client::factory()->create(['status' => 'active']);
        $product = Product::factory()->create(['status' => 'active']);
        $address = Address::factory()->create(['client_id' => $client->id]);

        // Create 3 recent orders (the limit in the controller)
        for ($i = 0; $i < 3; $i++) {
            $order = Order::factory()->create([
                'client_id' => $client->id,
                'user_id' => $this->user->id,
                'address_id' => $address->id,
            ]);
            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => $product->price,
                'total' => $product->price,
            ]);
        }

        $response = $this->get(route('orders.create'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Orders/Create')
                ->has('recentOrders', 3)
                ->where('recentOrders.0.client.name', $client->name)
                ->where('recentOrders.0.user.name', $this->user->name)
        );
    }

    public function test_store_creates_order_with_items()
    {
        $client = Client::factory()->create(['status' => 'active']);
        $product = Product::factory()->create(['status' => 'active', 'price' => 10.00]);
        $address = Address::factory()->create(['client_id' => $client->id]);

        $orderData = [
            'client_id' => $client->id,
            'payment_method' => 'cash',
            'delivery_type' => 'delivery',
            'address_id' => $address->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ]
            ]
        ];

        $response = $this->post(route('orders.store'), $orderData);

        $response->assertRedirect(route('orders.create'));
        $response->assertSessionHas('status', 'Pedido criado com sucesso.');

        $this->assertDatabaseHas('orders', [
            'client_id' => $client->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'payment_method' => 'cash',
            'delivery_type' => 'delivery',
            'address_id' => $address->id,
            'total' => 20.00, // 2 * 10.00
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 10.00,
            'total' => 20.00,
        ]);
    }

    public function test_store_creates_order_with_pickup_delivery()
    {
        $client = Client::factory()->create(['status' => 'active']);
        $product = Product::factory()->create(['status' => 'active', 'price' => 15.00]);

        $orderData = [
            'client_id' => $client->id,
            'payment_method' => 'card',
            'delivery_type' => 'pickup',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ]
            ]
        ];

        $response = $this->post(route('orders.store'), $orderData);

        $response->assertRedirect(route('orders.create'));

        $this->assertDatabaseHas('orders', [
            'client_id' => $client->id,
            'delivery_type' => 'pickup',
            'address_id' => null,
            'total' => 15.00,
        ]);
    }

    public function test_edit_displays_form_with_order_data()
    {
        $client = Client::factory()->create(['status' => 'active']);
        $product = Product::factory()->create(['status' => 'active']);
        $address = Address::factory()->create(['client_id' => $client->id]);

        $order = Order::factory()->create([
            'client_id' => $client->id,
            'user_id' => $this->user->id,
            'address_id' => $address->id,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
            'total' => $product->price,
        ]);

        $response = $this->get(route('orders.edit', $order));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Orders/Edit')
                ->has('order')
                ->where('order.id', $order->id)
                ->where('order.client_id', $client->id)
                ->has('order.items')
                ->has('products')
                ->has('clients')
                ->has('addresses')
        );
    }

    public function test_edit_displays_form_with_recent_orders_excluding_current()
    {
        $client = Client::factory()->create(['status' => 'active']);
        $product = Product::factory()->create(['status' => 'active']);
        $address = Address::factory()->create(['client_id' => $client->id]);

        // Create the order being edited
        $orderBeingEdited = Order::factory()->create([
            'client_id' => $client->id,
            'user_id' => $this->user->id,
            'address_id' => $address->id,
        ]);

        // Create other recent orders (should appear in recentOrders)
        for ($i = 0; $i < 3; $i++) {
            $order = Order::factory()->create([
                'client_id' => $client->id,
                'user_id' => $this->user->id,
                'address_id' => $address->id,
            ]);
            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => $product->price,
                'total' => $product->price,
            ]);
        }

        $response = $this->get(route('orders.edit', $orderBeingEdited));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/Orders/Edit')
                ->has('recentOrders', 3) // Should have 3 recent orders, excluding the one being edited
                ->where('recentOrders.0.client.name', $client->name)
                ->where('recentOrders.0.user.name', $this->user->name)
        );
    }

    public function test_update_modifies_order()
    {
        $client = Client::factory()->create(['status' => 'active']);
        $newClient = Client::factory()->create(['status' => 'active']);
        $address = Address::factory()->create(['client_id' => $newClient->id]);

        $order = Order::factory()->create([
            'client_id' => $client->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $updateData = [
            'client_id' => $newClient->id,
            'status' => 'confirmed',
            'payment_method' => 'card',
            'delivery_type' => 'delivery',
            'address_id' => $address->id,
        ];

        $response = $this->patch(route('orders.update', $order), $updateData);

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('status', 'Pedido atualizado com sucesso.');

        $order->refresh();
        $this->assertEquals($newClient->id, $order->client_id);
        $this->assertEquals('confirmed', $order->status);
        $this->assertEquals('card', $order->payment_method);
        $this->assertEquals('delivery', $order->delivery_type);
        $this->assertEquals($address->id, $order->address_id);
    }

    public function test_destroy_deletes_order_and_items()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        $order->items()->create([
            'product_id' => Product::factory()->create()->id,
            'quantity' => 1,
            'unit_price' => 10.00,
            'total' => 10.00,
        ]);

        $response = $this->delete(route('orders.destroy', $order));

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('status', 'Pedido excluído com sucesso.');

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
    }

    public function test_destroy_fails_if_order_status_is_not_pending()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'confirmed']);

        $response = $this->delete(route('orders.destroy', $order));

        $response->assertRedirect();
        $response->assertSessionHas('error', __('order.delete_blocked_not_pending'));

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_update_item_modifies_quantity_and_total()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'total' => 10.00]);
        $item = $order->items()->create([
            'product_id' => Product::factory()->create()->id,
            'quantity' => 1,
            'unit_price' => 10.00,
            'total' => 10.00,
        ]);

        $response = $this->patch(route('orders.items.update', [$order, $item]), [
            'quantity' => 3,
        ]);

        $response->assertJson([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'quantity' => 3.0,
                'total' => 30.0,
            ],
        ]);

        $response->assertJsonStructure(['order_total']);
        $this->assertGreaterThan(0, $response->json('order_total'));
    }

    public function test_remove_item_deletes_item_and_updates_total()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'total' => 25.00]);
        $item1 = $order->items()->create([
            'product_id' => Product::factory()->create(['status' => 'active'])->id,
            'quantity' => 1,
            'unit_price' => 10.00,
            'total' => 10.00,
        ]);
        $item2 = $order->items()->create([
            'product_id' => Product::factory()->create(['status' => 'active'])->id,
            'quantity' => 1,
            'unit_price' => 15.00,
            'total' => 15.00,
        ]);

        $response = $this->delete(route('orders.items.destroy', [$order, $item1]));

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonStructure(['order_total']);
        $this->assertGreaterThan(0, $response->json('order_total'));

        $this->assertDatabaseMissing('order_items', ['id' => $item1->id]);
    }

    public function test_add_item_creates_new_item()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'total' => 0.00]);
        $product = Product::factory()->create(['price' => 20.00, 'status' => 'active']);

        $response = $this->post(route('orders.items.store', $order), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertJson([
            'success' => true,
            'item' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'unit_price' => 20.0,
                'quantity' => 2.0,
                'total' => 40.0,
            ],
        ]);

        $response->assertJsonStructure(['order_total']);
        $this->assertGreaterThan(0, $response->json('order_total'));

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 20.00,
            'total' => 40.00,
        ]);
    }

    public function test_add_item_increases_existing_item_quantity()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'total' => 10.00]);
        $product = Product::factory()->create(['price' => 10.00, 'status' => 'active']);
        $existingItem = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10.00,
            'total' => 10.00,
        ]);

        $response = $this->post(route('orders.items.store', $order), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertJson([
            'success' => true,
            'item' => [
                'product_id' => $product->id,
                'quantity' => 3.0, // 1 + 2
                'total' => 30.0,   // 3 * 10
            ],
        ]);

        $response->assertJsonStructure(['order_total']);
        $this->assertGreaterThan(0, $response->json('order_total'));

        $existingItem->refresh();
        $this->assertEquals(3, $existingItem->quantity);
        $this->assertEquals(30.00, $existingItem->total);
    }

    public function test_show_displays_order_details()
    {
        $client = Client::factory()->create();
        $product = Product::factory()->create();
        $address = Address::factory()->create(['client_id' => $client->id]);

        $order = Order::factory()->create([
            'client_id' => $client->id,
            'user_id' => $this->user->id,
            'address_id' => $address->id,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
            'total' => $product->price,
        ]);

        $response = $this->get(route('orders.modal', $order));

        $response->assertJsonStructure([
            'order' => [
                'id',
                'client' => ['id', 'name'],
                'user' => ['id', 'name'],
                'status',
                'payment_method',
                'delivery_type',
                'address' => ['id', 'description', 'address', 'city', 'state'],
                'total',
                'ordered_at',
                'created_at',
                'updated_at',
                'created_by',
                'updated_by',
                'items' => [
                    '*' => [
                        'product_id',
                        'name',
                        'code',
                        'unit_price',
                        'quantity',
                        'total',
                    ]
                ],
            ]
        ]);

        $responseData = $response->json();
        $this->assertEquals($order->id, $responseData['order']['id']);
        $this->assertEquals($client->id, $responseData['order']['client']['id']);
        $this->assertGreaterThanOrEqual(1, count($responseData['order']['items']));
        $this->assertEquals($product->id, $responseData['order']['items'][0]['product_id']);
    }

    public function test_modal_returns_order_details()
    {
        $client = Client::factory()->create();
        $product = Product::factory()->create();
        $address = Address::factory()->create(['client_id' => $client->id]);

        $order = Order::factory()->create([
            'client_id' => $client->id,
            'user_id' => $this->user->id,
            'address_id' => $address->id,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
            'total' => $product->price,
        ]);

        $response = $this->get(route('orders.modal', $order));

        $response->assertJsonStructure([
            'order' => [
                'id',
                'client' => ['id', 'name'],
                'user' => ['id', 'name'],
                'status',
                'payment_method',
                'delivery_type',
                'address' => ['id', 'description', 'address', 'city', 'state'],
                'total',
                'items' => [
                    '*' => [
                        'product_id',
                        'name',
                        'code',
                        'unit_price',
                        'quantity',
                        'total',
                    ]
                ],
            ]
        ]);

        $responseData = $response->json();
        $this->assertEquals($order->id, $responseData['order']['id']);
        $this->assertEquals($client->id, $responseData['order']['client']['id']);
        $this->assertGreaterThanOrEqual(1, count($responseData['order']['items']));
        $this->assertEquals($product->id, $responseData['order']['items'][0]['product_id']);
    }

    public function test_update_status_denies_without_permission()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => [
                'orders' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
            ],
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $updateData = [
            'client_id' => $order->client_id,
            'status' => 'confirmed',
            'payment_method' => 'cash',
            'delivery_type' => 'pickup',
            'address_id' => null,
        ];

        $response = $this->patch(route('orders.update', $order), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('error', __('auth.forbidden_orders_status_update'));

        $order->refresh();
        $this->assertEquals('pending', $order->status);
    }

    public function test_update_status_allows_with_permission()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => [
                'orders' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true, 'update_status' => true],
            ],
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $updateData = [
            'client_id' => $order->client_id,
            'status' => 'confirmed',
            'payment_method' => 'cash',
            'delivery_type' => 'pickup',
            'address_id' => null,
        ];

        $response = $this->patch(route('orders.update', $order), $updateData);

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('status', 'Pedido atualizado com sucesso.');

        $order->refresh();
        $this->assertEquals('confirmed', $order->status);
    }

    public function test_update_without_status_change_allows_without_permission()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => [
                'orders' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true],
            ],
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $updateData = [
            'client_id' => $order->client_id,
            'status' => 'pending', // same status
            'payment_method' => 'cash',
            'delivery_type' => 'pickup',
            'address_id' => null,
        ];

        $response = $this->patch(route('orders.update', $order), $updateData);

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('status', 'Pedido atualizado com sucesso.');

        $order->refresh();
        $this->assertEquals('pending', $order->status);
    }

    public function test_store_denies_creating_order_for_inactive_client()
    {
        $inactiveClient = Client::factory()->create(['status' => 'inactive']);
        $product = Product::factory()->create(['status' => 'active']);

        $orderData = [
            'client_id' => $inactiveClient->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ]
            ]
        ];

        $this->from(route('orders.create'));
        $response = $this->post(route('orders.store'), $orderData);

        $response->assertRedirect(route('orders.create'));
        $response->assertSessionHasErrors('client_id');
        $this->assertDatabaseMissing('orders', ['client_id' => $inactiveClient->id]);
    }

    public function test_store_denies_creating_order_with_inactive_product_item()
    {
        $client = Client::factory()->create(['status' => 'active']);
        $inactiveProduct = Product::factory()->create(['status' => 'inactive']);

        $orderData = [
            'client_id' => $client->id,
            'items' => [
                [
                    'product_id' => $inactiveProduct->id,
                    'quantity' => 1,
                ]
            ]
        ];

        $this->from(route('orders.create'));
        $response = $this->post(route('orders.store'), $orderData);

        $response->assertRedirect(route('orders.create'));
        $response->assertSessionHasErrors('items.0.product_id');
        $this->assertDatabaseMissing('orders', ['client_id' => $client->id]);
    }

    public function test_update_allows_editing_order_when_client_is_now_inactive_without_change()
    {
        $inactiveClient = Client::factory()->create(['status' => 'inactive']);
        $order = Order::factory()->create([
            'client_id' => $inactiveClient->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $updateData = [
            'client_id' => $inactiveClient->id, // same client
            'status' => 'confirmed',
        ];

        $response = $this->patch(route('orders.update', $order), $updateData);

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('status', 'Pedido atualizado com sucesso.');

        $order->refresh();
        $this->assertEquals('confirmed', $order->status);
    }

    public function test_update_denies_changing_order_client_to_inactive()
    {
        $activeClient = Client::factory()->create(['status' => 'active']);
        $inactiveClient = Client::factory()->create(['status' => 'inactive']);
        $order = Order::factory()->create([
            'client_id' => $activeClient->id,
            'user_id' => $this->user->id,
        ]);

        $updateData = [
            'client_id' => $inactiveClient->id,
            'status' => 'confirmed',
        ];

        $this->from(route('orders.edit', $order));
        $response = $this->patch(route('orders.update', $order), $updateData);

        $response->assertRedirect(route('orders.edit', $order));
        $response->assertSessionHasErrors('client_id');

        $order->refresh();
        $this->assertEquals($activeClient->id, $order->client_id);
    }

    public function test_update_allows_editing_existing_item_with_inactive_product_without_changing_product()
    {
        $client = Client::factory()->create(['status' => 'active']);
        $inactiveProduct = Product::factory()->create(['status' => 'inactive']);
        $order = Order::factory()->create(['client_id' => $client->id, 'user_id' => $this->user->id]);
        $item = $order->items()->create([
            'product_id' => $inactiveProduct->id,
            'quantity' => 1,
            'unit_price' => $inactiveProduct->price,
            'total' => $inactiveProduct->price,
        ]);

        $response = $this->withHeader('Accept', 'application/json')->patch(route('orders.items.update', [$order, $item]), [
            'quantity' => 2,
        ]);

        $response->assertJson([
            'success' => true,
            'item' => [
                'product_id' => $inactiveProduct->id,
                'quantity' => 2.0,
            ],
        ]);

        $item->refresh();
        $this->assertEquals(2, $item->quantity);
    }

    public function test_add_item_denies_adding_inactive_product()
    {
        $client = Client::factory()->create(['status' => 'active']);
        $inactiveProduct = Product::factory()->create(['status' => 'inactive']);
        $order = Order::factory()->create(['client_id' => $client->id, 'user_id' => $this->user->id]);

        $itemData = [
            'product_id' => $inactiveProduct->id,
            'quantity' => 1,
        ];

        $response = $this->withHeader('Accept', 'application/json')->post(route('orders.items.store', $order), $itemData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('product_id');
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id, 'product_id' => $inactiveProduct->id]);
    }

    public function test_update_item_denies_switching_product_to_inactive()
    {
        $client = Client::factory()->create(['status' => 'active']);
        $activeProduct = Product::factory()->create(['status' => 'active']);
        $inactiveProduct = Product::factory()->create(['status' => 'inactive']);
        $order = Order::factory()->create(['client_id' => $client->id, 'user_id' => $this->user->id]);
        $item = $order->items()->create([
            'product_id' => $activeProduct->id,
            'quantity' => 1,
            'unit_price' => $activeProduct->price,
            'total' => $activeProduct->price,
        ]);

        $updateData = [
            'product_id' => $inactiveProduct->id,
            'quantity' => 1,
        ];

        $response = $this->withHeader('Accept', 'application/json')->patch(route('orders.items.update', [$order, $item]), $updateData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('product_id');

        $item->refresh();
        $this->assertEquals($activeProduct->id, $item->product_id);
    }
}
