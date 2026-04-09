<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Mockery;
use Tests\TestCase;

class StoreProductRequestTest extends TestCase
{
  protected function tearDown(): void
  {
    Mockery::close();
    parent::tearDown();
  }

  public function test_authorize_returns_true_when_user_can_create_product()
  {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with('create', Product::class)->andReturn(true);

    $request = new StoreProductRequest();
    $request->setUserResolver(fn() => $user);

    $this->assertTrue($request->authorize());
  }

  public function test_authorize_returns_false_when_user_cannot_create_product()
  {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->with('create', Product::class)->andReturn(false);

    $request = new StoreProductRequest();
    $request->setUserResolver(fn() => $user);

    $this->assertFalse($request->authorize());
  }

    public function test_rules_returns_correct_array()
    {
        $request = new StoreProductRequest();
        $rules = $request->rules();

    $this->assertIsArray($rules);
    $this->assertArrayHasKey('name', $rules);
    $this->assertArrayHasKey('description', $rules);
    $this->assertArrayHasKey('price', $rules);
    $this->assertArrayHasKey('unit_of_measure', $rules);
    $this->assertArrayHasKey('status', $rules);
        $this->assertArrayHasKey('components', $rules);
    }

    public function test_withValidator_without_circular_dependencies_adds_no_errors()
    {
        $request = new StoreProductRequest();
        $request->replace([
            'components' => [
                ['id' => 1, 'quantity' => 1],
                ['id' => 2, 'quantity' => 2],
            ],
        ]);

        // Use empty rules to avoid hitting DB-dependent exists rule
        $validator = Validator::make([], []);

        $request->withValidator($validator);

        // Trigger the after() callbacks
        $this->assertFalse($validator->fails());
        $this->assertFalse($validator->errors()->has('components'));
    }

    public function test_withValidator_adds_error_when_circular_dependencies_detected()
    {
        // Anonymous subclass overriding the circular check to force a violation
        $request = new class extends StoreProductRequest {
            protected function hasCircularDependency($productId, $componentIds)
            {
                return true; // force circular dependency detection
            }
        };

        $request->replace([
            'components' => [
                ['id' => 10, 'quantity' => 1],
            ],
        ]);

        // Use empty rules to avoid hitting DB-dependent exists rule
        $validator = Validator::make([], []);

        $request->withValidator($validator);

        // Trigger the after() callbacks
        $validator->fails();

        $this->assertTrue($validator->errors()->has('components'));
        $this->assertStringContainsString(
            'Dependências circulares detectadas nos componentes.',
            $validator->errors()->first('components')
        );
    }
}
