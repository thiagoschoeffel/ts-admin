<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Mockery;
use Tests\TestCase;

class UpdateUserRequestTest extends TestCase
{
  protected function tearDown(): void
  {
    Mockery::close();
    parent::tearDown();
  }

  public function test_authorize_returns_true_when_user_is_admin()
  {
    $adminUser = Mockery::mock(User::class);
    $adminUser->shouldReceive('isAdmin')->andReturn(true);

    $request = new UpdateUserRequest();
    $request->setUserResolver(fn() => $adminUser);

    $this->assertTrue($request->authorize());
  }

  public function test_authorize_returns_false_when_user_is_not_admin()
  {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isAdmin')->andReturn(false);

    $request = new UpdateUserRequest();
    $request->setUserResolver(fn() => $user);

    $this->assertFalse($request->authorize());
  }

  public function test_authorize_returns_false_when_no_user()
  {
    $request = new UpdateUserRequest();
    $request->setUserResolver(fn() => null);

    $this->assertFalse($request->authorize());
  }

  public function test_rules_returns_correct_array()
  {
    $route = Mockery::mock();
    $route->shouldReceive('parameter')->with('user', null)->andReturn((object) ['id' => 1]);

    $request = new UpdateUserRequest();
    $request->setRouteResolver(function () use ($route) {
      return $route;
    });
    $rules = $request->rules();

    $this->assertIsArray($rules);
    $this->assertArrayHasKey('name', $rules);
    $this->assertArrayHasKey('email', $rules);
    $this->assertArrayHasKey('password', $rules);
    $this->assertArrayHasKey('status', $rules);
    $this->assertArrayHasKey('role', $rules);
    $this->assertArrayHasKey('modules', $rules);
    $this->assertArrayHasKey('permissions', $rules);
  }
}
