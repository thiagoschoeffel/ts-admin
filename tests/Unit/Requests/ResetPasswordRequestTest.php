<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\ResetPasswordRequest;
use Tests\TestCase;

class ResetPasswordRequestTest extends TestCase
{
  public function test_authorize_returns_true()
  {
    $request = new ResetPasswordRequest();
    $this->assertTrue($request->authorize());
  }

  public function test_rules_returns_correct_array()
  {
    $request = new ResetPasswordRequest();
    $expected = [
      'token' => ['required'],
      'email' => ['required', 'email'],
      'password' => ['required', 'confirmed', 'min:8'],
    ];
    $this->assertEquals($expected, $request->rules());
  }
}
