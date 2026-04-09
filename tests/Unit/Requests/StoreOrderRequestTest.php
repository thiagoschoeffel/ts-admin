<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\User;
use Mockery;
use Tests\TestCase;

class StoreOrderRequestTest extends TestCase
{
  protected function tearDown(): void
  {
    Mockery::close();
    parent::tearDown();
  }

  public function test_authorize_returns_true_when_user_can_create_order()
  {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with('create', Order::class)->andReturn(true);

    $request = new StoreOrderRequest();
    $request->setUserResolver(fn() => $user);

    $this->assertTrue($request->authorize());
  }

  public function test_authorize_returns_false_when_user_cannot_create_order()
  {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with('create', Order::class)->andReturn(false);

    $request = new StoreOrderRequest();
    $request->setUserResolver(fn() => $user);

    $this->assertFalse($request->authorize());
  }

  public function test_rules_returns_correct_array()
  {
    $request = new StoreOrderRequest();
    $rules = $request->rules();

    $this->assertIsArray($rules);
    $this->assertArrayHasKey('client_id', $rules);
    $this->assertArrayHasKey('items', $rules);
    $this->assertArrayHasKey('payment_method', $rules);
    $this->assertArrayHasKey('delivery_type', $rules);
    $this->assertArrayHasKey('address_id', $rules);
  }
}
