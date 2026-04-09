<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreAddressRequest;
use App\Models\Client;
use App\Models\User;
use Mockery;
use Tests\TestCase;

class StoreAddressRequestTest extends TestCase
{
  protected function tearDown(): void
  {
    Mockery::close();
    parent::tearDown();
  }

  public function test_authorize_returns_true_when_user_can_create_address()
  {
    $user = Mockery::mock(User::class);
    $client = Mockery::mock(Client::class);
    $user->shouldReceive('can')->with('createAddress', $client)->andReturn(true);

    $route = Mockery::mock();
    $route->shouldReceive('parameter')->with('client', null)->andReturn($client);

    $request = new StoreAddressRequest();
    $request->setUserResolver(fn() => $user);
    $request->setRouteResolver(function () use ($route) {
      return $route;
    });

    $this->assertTrue($request->authorize());
  }

  public function test_authorize_returns_false_when_user_cannot_create_address()
  {
    $user = Mockery::mock(User::class);
    $client = Mockery::mock(Client::class);
    $user->shouldReceive('can')->with('createAddress', $client)->andReturn(false);

    $route = Mockery::mock();
    $route->shouldReceive('parameter')->with('client', null)->andReturn($client);

    $request = new StoreAddressRequest();
    $request->setUserResolver(fn() => $user);
    $request->setRouteResolver(function () use ($route) {
      return $route;
    });

    $this->assertFalse($request->authorize());
  }

  public function test_rules_returns_correct_array()
  {
    $request = new StoreAddressRequest();
    $rules = $request->rules();

    $this->assertIsArray($rules);
    $this->assertArrayHasKey('description', $rules);
    $this->assertArrayHasKey('postal_code', $rules);
    $this->assertArrayHasKey('address', $rules);
    $this->assertArrayHasKey('address_number', $rules);
    $this->assertArrayHasKey('address_complement', $rules);
    $this->assertArrayHasKey('neighborhood', $rules);
    $this->assertArrayHasKey('city', $rules);
    $this->assertArrayHasKey('state', $rules);
    $this->assertArrayHasKey('status', $rules);
  }
}
