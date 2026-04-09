<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\RegisterUserRequest;
use Tests\TestCase;

class RegisterUserRequestTest extends TestCase
{
  public function test_authorize_returns_true()
  {
    $request = new RegisterUserRequest();
    $this->assertTrue($request->authorize());
  }

  public function test_rules_returns_correct_array()
  {
    $request = new RegisterUserRequest();
    $expected = [
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
      'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
    ];
    $this->assertEquals($expected, $request->rules());
  }
}
