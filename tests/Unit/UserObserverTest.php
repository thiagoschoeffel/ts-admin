<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Observers\UserObserver;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserObserverTest extends TestCase
{
    use RefreshDatabase;

    private UserObserver $observer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->observer = new UserObserver();
    }

    public function test_deleting_user_with_clients_throws_exception()
    {
        $user = User::factory()->create();
        $user->clients()->createMany(Client::factory()->count(2)->make()->toArray());

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(__('user.delete_blocked_has_related_records'));

        $this->observer->deleting($user);
    }

    public function test_deleting_user_with_products_throws_exception()
    {
        $user = User::factory()->create();
        $user->products()->createMany(Product::factory()->count(2)->make()->toArray());

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(__('user.delete_blocked_has_related_records'));

        $this->observer->deleting($user);
    }

    public function test_deleting_user_with_orders_throws_exception()
    {
        $user = User::factory()->create();
        $user->orders()->createMany(Order::factory()->count(2)->make()->toArray());

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(__('user.delete_blocked_has_related_records'));

        $this->observer->deleting($user);
    }

    public function test_deleting_user_without_related_records_does_not_throw_exception()
    {
        $user = User::factory()->create();

        $this->observer->deleting($user);

        // If no exception is thrown, the test passes
        $this->assertTrue(true);
    }
}
