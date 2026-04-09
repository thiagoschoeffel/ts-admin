<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\ProductComponentController;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductComponentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_circular_dependency_returns_true_when_component_already_visited()
    {
        $a = Product::factory()->create();
        $b = Product::factory()->create();

        $controller = new ProductComponentController();
        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('hasCircularDependency');
        $method->setAccessible(true);

        // If the component ID is already in visited, it should early-return true
        $result = $method->invoke($controller, $a->id, $b->id, [$b->id]);
        $this->assertTrue($result);
    }

    public function test_has_circular_dependency_returns_false_when_component_not_found()
    {
        $a = Product::factory()->create();
        $nonExistentId = 999999;

        $controller = new ProductComponentController();
        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('hasCircularDependency');
        $method->setAccessible(true);

        $result = $method->invoke($controller, $a->id, $nonExistentId, []);
        $this->assertFalse($result);
    }
}

