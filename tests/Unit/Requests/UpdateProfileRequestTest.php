<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Mockery;
use Tests\TestCase;

class UpdateProfileRequestTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_authorize_returns_true_when_user_exists()
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $request = new UpdateProfileRequest();
        $request->setUserResolver(fn() => $user);

        $this->assertTrue($request->authorize());
    }

    public function test_authorize_returns_false_when_no_user()
    {
        $request = new UpdateProfileRequest();
        $request->setUserResolver(fn() => null);

        $this->assertFalse($request->authorize());
    }

    public function test_rules_returns_correct_array()
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $request = new UpdateProfileRequest();
        $request->setUserResolver(fn() => $user);
        $rules = $request->rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('current_password', $rules);
        $this->assertArrayHasKey('password', $rules);
    }
}
