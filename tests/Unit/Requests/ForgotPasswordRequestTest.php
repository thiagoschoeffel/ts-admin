<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\ForgotPasswordRequest;
use Tests\TestCase;

class ForgotPasswordRequestTest extends TestCase
{
  public function test_authorize_returns_true()
  {
    $request = new ForgotPasswordRequest();
    $this->assertTrue($request->authorize());
  }

  public function test_rules_returns_correct_array()
  {
    $request = new ForgotPasswordRequest();
    $expected = [
      'email' => ['required', 'email'],
    ];
    $this->assertEquals($expected, $request->rules());
  }
}
