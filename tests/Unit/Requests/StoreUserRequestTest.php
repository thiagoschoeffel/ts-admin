<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Mockery;
use Tests\TestCase;

class StoreUserRequestTest extends TestCase
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

    $request = new StoreUserRequest();
    $request->setUserResolver(fn() => $adminUser);

    $this->assertTrue($request->authorize());
  }

  public function test_authorize_returns_false_when_user_is_not_admin()
  {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isAdmin')->andReturn(false);

    $request = new StoreUserRequest();
    $request->setUserResolver(fn() => $user);

    $this->assertFalse($request->authorize());
  }

  public function test_authorize_returns_false_when_no_user()
  {
    $request = new StoreUserRequest();
    $request->setUserResolver(fn() => null);

    $this->assertFalse($request->authorize());
  }

  public function test_rules_returns_correct_array()
  {
    $request = new StoreUserRequest();
    $rules = $request->rules();

    $this->assertIsArray($rules);
    $this->assertArrayHasKey('name', $rules);
    $this->assertArrayHasKey('email', $rules);
    $this->assertArrayHasKey('password', $rules);
    $this->assertArrayHasKey('status', $rules);
    $this->assertArrayHasKey('role', $rules);
    $this->assertArrayHasKey('modules', $rules);
    $this->assertArrayHasKey('permissions', $rules);
    // Add more specific assertions if needed
  }
}
