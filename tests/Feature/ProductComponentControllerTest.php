<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductComponentController;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductComponentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($this->admin);
    }

    public function test_store_denies_adding_inactive_component()
    {
        $product = Product::factory()->create(['status' => 'active']);
        $inactiveComponent = Product::factory()->create(['status' => 'inactive']);

        $data = [
            'component_id' => $inactiveComponent->id,
            'quantity' => 1.0,
        ];

        $response = $this->post(route('products.components.store', $product), $data);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('component_id');
        $this->assertDatabaseMissing('product_components', [
            'product_id' => $product->id,
            'component_id' => $inactiveComponent->id,
        ]);
    }

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
