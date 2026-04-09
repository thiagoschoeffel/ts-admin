<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_cannot_generate_pdf()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => [
                'orders' => ['export_pdf' => false],
            ],
        ]);
        $order = Order::factory()->create();

        $response = $this->actingAs($user)->get(route('orders.pdf.show', $order));

        $response->assertForbidden();
    }

    public function test_admin_can_generate_pdf()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->create(['client_id' => $client->id]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
            'total' => $product->price,
        ]);

        $response = $this->actingAs($user)->get(route('orders.pdf.show', $order));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_order_without_items_returns_422()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create();
        $order->items()->delete(); // Remove items to test empty order

        $response = $this->actingAs($user)->get(route('orders.pdf.show', $order));

        $response->assertStatus(422);
        $response->assertJson(['message' => __('order.pdf.empty_items')]);
    }

    public function test_pdf_download_with_download_param()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->create(['client_id' => $client->id]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
            'total' => $product->price,
        ]);

        $response = $this->actingAs($user)->get(route('orders.pdf.show', ['order' => $order, 'download' => 1]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="pedido_' . $order->id . '.pdf"');
    }
}
