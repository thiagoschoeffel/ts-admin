<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\LoginRequest;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    public function test_authorize_returns_true()
    {
        $request = new LoginRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_rules_returns_correct_array()
    {
        $request = new LoginRequest();
        $expected = [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
        $this->assertEquals($expected, $request->rules());
    }
}
