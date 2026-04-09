<?php

namespace Tests\Unit;

use App\Models\Address;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_model_can_be_created_with_valid_data()
    {
        $client = Client::factory()->create();
        $user = User::factory()->create();
        $address = Address::factory()->create(['client_id' => $client->id]);
        $creator = User::factory()->create();
        $orderData = [
            'client_id' => $client->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_method' => 'credit_card',
            'delivery_type' => 'delivery',
            'address_id' => $address->id,
            'total' => 150.00,
            'notes' => 'Order notes',
            'ordered_at' => now(),
            'created_by_id' => $creator->id,
            'updated_by_id' => $creator->id,
        ];

        $order = Order::create($orderData);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($client->id, $order->client_id);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals(150.00, $order->total);
    }

    public function test_order_fillable_attributes_are_correct()
    {
        $fillable = [
            'client_id',
            'user_id',
            'status',
            'payment_method',
            'delivery_type',
            'address_id',
            'total',
            'notes',
            'ordered_at',
            'created_by_id',
            'updated_by_id',
        ];
        $this->assertEquals($fillable, (new Order)->getFillable());
    }

    public function test_order_casts_are_correct()
    {
        $casts = [
            'id' => 'int',
            'total' => 'decimal:2',
            'ordered_at' => 'datetime',
        ];

        $this->assertEquals($casts, (new Order)->getCasts());
    }

    public function test_order_client_relationship()
    {
        $client = Client::factory()->create();
        $order = Order::factory()->create(['client_id' => $client->id]);

        $this->assertInstanceOf(Client::class, $order->client);
        $this->assertEquals($client->id, $order->client->id);
    }

    public function test_order_user_relationship()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
    }

    public function test_order_items_relationship()
    {
        $order = Order::factory()->create();
        $product = Product::factory()->create();
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2.0,
            'unit_price' => 50.00,
            'total' => 100.00,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $order->items());
        $this->assertTrue($order->items->contains($item));
    }

    public function test_order_address_relationship()
    {
        $client = Client::factory()->create();
        $address = Address::factory()->create(['client_id' => $client->id]);
        $order = Order::factory()->create([
            'address_id' => $address->id,
            'delivery_type' => 'pickup' // Evitar que o factory crie endereço automaticamente
        ]);

        $this->assertInstanceOf(Address::class, $order->address);
        $this->assertEquals($address->id, $order->address->id);
    }

    public function test_order_created_by_relationship()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['created_by_id' => $user->id]);

        $this->assertInstanceOf(User::class, $order->createdBy);
        $this->assertEquals($user->id, $order->createdBy->id);
    }

    public function test_order_updated_by_relationship()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['updated_by_id' => $user->id]);

        $this->assertInstanceOf(User::class, $order->updatedBy);
        $this->assertEquals($user->id, $order->updatedBy->id);
    }
}
