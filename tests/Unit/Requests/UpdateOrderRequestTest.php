<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class UpdateOrderRequestTest extends TestCase
{
  use RefreshDatabase;

  protected function tearDown(): void
  {
    Mockery::close();
    parent::tearDown();
  }

  public function test_authorize_returns_true_when_user_can_update_order()
  {
    DB::table('users')->insert(['id' => 1, 'name' => 'Test User', 'email' => 'test@example.com', 'password' => 'password', 'role' => 'user', 'status' => 'active']);
    DB::table('clients')->insert([
      'id' => 1,
      'name' => 'Test Client',
      'person_type' => 'individual',
      'document' => '12345678901',
      'contact_name' => null,
      'contact_phone_primary' => null,
      'contact_phone_secondary' => null,
      'contact_email' => null,
      'status' => 'active',
      'created_by_id' => null,
      'updated_by_id' => null,
      'observations' => null,
      'created_at' => now(),
      'updated_at' => now(),
    ]);
    DB::table('orders')->insert(['id' => 1, 'client_id' => 1, 'user_id' => 1, 'status' => 'pending', 'total' => 0.0]);

    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with('update', Mockery::type(\App\Models\Order::class))->andReturn(true);

    $route = new class {
      public function parameter($key, $default = null)
      {
        if ($key === 'order') return 1;
        return $default;
      }
    };

    $request = new UpdateOrderRequest();
    $request->setUserResolver(fn() => $user);
    $request->setRouteResolver(function () use ($route) {
      return $route;
    });

    $this->assertTrue($request->authorize());
  }

  public function test_authorize_returns_false_when_user_cannot_update_order()
  {
    DB::table('users')->insert(['id' => 1, 'name' => 'Test User', 'email' => 'test@example.com', 'password' => 'password', 'role' => 'user', 'status' => 'active']);
    DB::table('clients')->insert([
      'id' => 1,
      'name' => 'Test Client',
      'person_type' => 'individual',
      'document' => '12345678901',
      'contact_name' => null,
      'contact_phone_primary' => null,
      'contact_phone_secondary' => null,
      'contact_email' => null,
      'status' => 'active',
      'created_by_id' => null,
      'updated_by_id' => null,
      'observations' => null,
      'created_at' => now(),
      'updated_at' => now(),
    ]);
    DB::table('orders')->insert(['id' => 1, 'client_id' => 1, 'user_id' => 1, 'status' => 'pending', 'total' => 0.0]);

    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with('update', Mockery::type(\App\Models\Order::class))->andReturn(false);

    $route = new class {
      public function parameter($key, $default = null)
      {
        if ($key === 'order') return 1;
        return $default;
      }
    };

    $request = new UpdateOrderRequest();
    $request->setUserResolver(fn() => $user);
    $request->setRouteResolver(function () use ($route) {
      return $route;
    });

    $this->assertFalse($request->authorize());
  }

  public function test_rules_returns_correct_array()
  {
    $request = new UpdateOrderRequest();
    $rules = $request->rules();

    $this->assertIsArray($rules);
    $this->assertArrayHasKey('client_id', $rules);
    $this->assertArrayHasKey('status', $rules);
    $this->assertArrayHasKey('payment_method', $rules);
    $this->assertArrayHasKey('delivery_type', $rules);
    $this->assertArrayHasKey('address_id', $rules);
  }
}
