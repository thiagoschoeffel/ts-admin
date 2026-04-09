<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Observers\OrderObserver;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderObserverTest extends TestCase
{
    use RefreshDatabase;

    private OrderObserver $observer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->observer = new OrderObserver();
    }

    public function test_deleting_order_with_pending_status_does_not_throw_exception()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $this->observer->deleting($order);

        // If no exception is thrown, the test passes
        $this->assertTrue(true);
    }

    public function test_deleting_order_with_confirmed_status_throws_exception()
    {
        $order = Order::factory()->create(['status' => 'confirmed']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(__('order.delete_blocked_not_pending'));

        $this->observer->deleting($order);
    }

    public function test_deleting_order_with_shipped_status_throws_exception()
    {
        $order = Order::factory()->create(['status' => 'shipped']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(__('order.delete_blocked_not_pending'));

        $this->observer->deleting($order);
    }

    public function test_deleting_order_with_delivered_status_throws_exception()
    {
        $order = Order::factory()->create(['status' => 'delivered']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(__('order.delete_blocked_not_pending'));

        $this->observer->deleting($order);
    }

    public function test_deleting_order_with_cancelled_status_throws_exception()
    {
        $order = Order::factory()->create(['status' => 'cancelled']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(__('order.delete_blocked_not_pending'));

        $this->observer->deleting($order);
    }
}
