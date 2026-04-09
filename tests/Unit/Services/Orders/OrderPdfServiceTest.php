<?php

namespace Tests\Unit\Services\Orders;

use App\Models\Order;
use App\Services\Orders\OrderPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPdfServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_make_html_returns_string()
    {
        $order = Order::factory()->create();
        $service = new OrderPdfService();

        $html = $service->makeHtml($order);

        $this->assertIsString($html);
        $this->assertStringContainsString('Pedido #' . $order->id, $html);
    }

    public function test_render_returns_binary()
    {
        $order = Order::factory()->create();
        $service = new OrderPdfService();

        $binary = $service->render($order);

        $this->assertIsString($binary);
        $this->assertNotEmpty($binary);
        // Check if it's PDF by checking %PDF-
        $this->assertStringStartsWith('%PDF-', $binary);
    }
}
