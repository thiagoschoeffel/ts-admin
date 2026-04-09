<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class UpdateProductRequestTest extends TestCase
{
  use RefreshDatabase;

  protected function tearDown(): void
  {
    Mockery::close();
    parent::tearDown();
  }

  public function test_authorize_returns_true_when_user_can_update_product()
  {
    DB::table('products')->insert(['id' => 1, 'name' => 'Test Product', 'price' => 10.0]);

    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with('update', Mockery::any())->andReturn(true);

    $route = new class {
      public function parameter($key, $default = null)
      {
        if ($key === 'product') return 1;
        return $default;
      }
    };

    $request = new UpdateProductRequest();
    $request->setUserResolver(fn() => $user);
    $request->setRouteResolver(function () use ($route) {
      return $route;
    });

    $this->assertTrue($request->authorize());
  }

  public function test_authorize_returns_false_when_user_cannot_update_product()
  {
    DB::table('products')->insert(['id' => 1, 'name' => 'Test Product', 'price' => 10.0]);

    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with('update', Mockery::any())->andReturn(false);

    $route = new class {
      public function parameter($key, $default = null)
      {
        if ($key === 'product') return 1;
        return $default;
      }
    };

    $request = new UpdateProductRequest();
    $request->setUserResolver(fn() => $user);
    $request->setRouteResolver(function () use ($route) {
      return $route;
    });

    $this->assertFalse($request->authorize());
  }

  public function test_rules_returns_correct_array()
  {
    $request = new UpdateProductRequest();
    $rules = $request->rules();

    $this->assertIsArray($rules);
    $this->assertArrayHasKey('name', $rules);
    $this->assertArrayHasKey('description', $rules);
    $this->assertArrayHasKey('price', $rules);
    $this->assertArrayHasKey('unit_of_measure', $rules);
    $this->assertArrayHasKey('status', $rules);
    $this->assertArrayHasKey('components', $rules);
  }
}
