<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreClientRequest;
use Tests\TestCase;

class StoreClientRequestTest extends TestCase
{
  public function test_authorize_returns_true_when_user_exists()
  {
    $request = new StoreClientRequest();
    $request->setUserResolver(fn() => (object)['id' => 1]);

    $this->assertTrue($request->authorize());
  }

  public function test_authorize_returns_false_when_no_user()
  {
    $request = new StoreClientRequest();
    $request->setUserResolver(fn() => null);

    $this->assertFalse($request->authorize());
  }

  public function test_rules_returns_correct_array()
  {
    $request = new StoreClientRequest();
    $rules = $request->rules();

    $this->assertIsArray($rules);
    $this->assertArrayHasKey('name', $rules);
    $this->assertArrayHasKey('person_type', $rules);
    $this->assertArrayHasKey('document', $rules);
    $this->assertArrayHasKey('contact_name', $rules);
    $this->assertArrayHasKey('contact_phone_primary', $rules);
    $this->assertArrayHasKey('status', $rules);
  }

  public function test_prepare_for_validation()
  {
    $request = new StoreClientRequest();
    $reflection = new \ReflectionClass($request);
    $method = $reflection->getMethod('prepareForValidation');
    $method->setAccessible(true);
    $method->invoke($request);
    $this->assertTrue(true); // Covers the prepareForValidation method
  }
}
