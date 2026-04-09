<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_item_model_can_be_created_with_valid_data()
    {
        $order = Order::factory()->create();
        $product = Product::factory()->create();
        $itemData = [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3.0,
            'unit_price' => 25.50,
            'total' => 76.50,
        ];

        $orderItem = OrderItem::create($itemData);

        $this->assertInstanceOf(OrderItem::class, $orderItem);
        $this->assertEquals($order->id, $orderItem->order_id);
        $this->assertEquals($product->id, $orderItem->product_id);
        $this->assertEquals(3.0, $orderItem->quantity);
        $this->assertEquals(25.50, $orderItem->unit_price);
        $this->assertEquals(76.50, $orderItem->total);
    }

    public function test_order_item_fillable_attributes_are_correct()
    {
        $fillable = [
            'order_id',
            'product_id',
            'quantity',
            'unit_price',
            'total',
        ];
        $this->assertEquals($fillable, (new OrderItem)->getFillable());
    }

    public function test_order_item_casts_are_correct()
    {
        $casts = [
            'id' => 'int',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];

        $this->assertEquals($casts, (new OrderItem)->getCasts());
    }

    public function test_order_item_order_relationship()
    {
        $order = Order::factory()->create();
        $product = Product::factory()->create();
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3.0,
            'unit_price' => 25.50,
            'total' => 76.50,
        ]);

        $this->assertInstanceOf(Order::class, $orderItem->order);
        $this->assertEquals($order->id, $orderItem->order->id);
    }

    public function test_order_item_product_relationship()
    {
        $order = Order::factory()->create();
        $product = Product::factory()->create();
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3.0,
            'unit_price' => 25.50,
            'total' => 76.50,
        ]);

        $this->assertInstanceOf(Product::class, $orderItem->product);
        $this->assertEquals($product->id, $orderItem->product->id);
    }
}
