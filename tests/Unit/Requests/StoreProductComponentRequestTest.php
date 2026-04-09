<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreProductComponentRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class StoreProductComponentRequestTest extends TestCase
{
  use RefreshDatabase;

  protected function tearDown(): void
  {
    Mockery::close();
    parent::tearDown();
  }

  public function test_authorize_returns_true_when_user_can_create_component()
  {
    DB::table('products')->insert(['id' => 1, 'name' => 'Test Product', 'price' => 10.0, 'unit_of_measure' => 'UND', 'status' => 'active']);

    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with('createComponent', Mockery::any())->andReturn(true);

    $route = new class {
      public function parameter($key, $default = null)
      {
        if ($key === 'product') return 1;
        return $default;
      }
    };

    $request = new StoreProductComponentRequest();
    $request->setUserResolver(fn() => $user);
    $request->setRouteResolver(function () use ($route) {
      return $route;
    });

    $this->assertTrue($request->authorize());
  }

  public function test_authorize_returns_false_when_user_cannot_create_component()
  {
    DB::table('products')->insert(['id' => 1, 'name' => 'Test Product', 'price' => 10.0, 'unit_of_measure' => 'UND', 'status' => 'active']);

    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with('createComponent', Mockery::any())->andReturn(false);

    $route = new class {
      public function parameter($key, $default = null)
      {
        if ($key === 'product') return 1;
        return $default;
      }
    };

    $request = new StoreProductComponentRequest();
    $request->setUserResolver(fn() => $user);
    $request->setRouteResolver(function () use ($route) {
      return $route;
    });

    $this->assertFalse($request->authorize());
  }

  public function test_rules_returns_correct_array()
  {
    $request = new StoreProductComponentRequest();
    $rules = $request->rules();

    $this->assertIsArray($rules);
    $this->assertArrayHasKey('component_id', $rules);
    $this->assertArrayHasKey('quantity', $rules);
  }

  public function test_authorize_returns_false_when_route_product_is_invalid()
  {
    // Simula um parâmetro de rota inválido (string não numérica e não-model)
    $route = new class {
      public function parameter($key, $default = null)
      {
        if ($key === 'product') return 'invalid-product-param';
        return $default;
      }
    };

    $request = new StoreProductComponentRequest();
    $request->setUserResolver(fn() => null); // usuário irrelevante neste branch
    $request->setRouteResolver(function () use ($route) {
      return $route;
    });

    $this->assertFalse($request->authorize()); // cobre o return false (linha 23)
  }
}
