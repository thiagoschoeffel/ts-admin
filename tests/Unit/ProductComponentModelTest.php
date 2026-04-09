<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductComponentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_component_model_can_be_created_with_valid_data()
    {
        $product = Product::factory()->create();
        $component = Product::factory()->create();
        $componentData = [
            'product_id' => $product->id,
            'component_id' => $component->id,
            'quantity' => 5.0,
        ];

        $productComponent = ProductComponent::create($componentData);

        $this->assertInstanceOf(ProductComponent::class, $productComponent);
        $this->assertEquals($product->id, $productComponent->product_id);
        $this->assertEquals($component->id, $productComponent->component_id);
        $this->assertEquals(5.0, $productComponent->quantity);
    }

    public function test_product_component_fillable_attributes_are_correct()
    {
        $fillable = [
            'product_id',
            'component_id',
            'quantity',
        ];
        $this->assertEquals($fillable, (new ProductComponent)->getFillable());
    }

    public function test_product_component_table_name_is_correct()
    {
        $productComponent = new ProductComponent;
        $this->assertEquals('product_components', $productComponent->getTable());
    }

    public function test_product_component_product_relationship()
    {
        $product = Product::factory()->create();
        $component = Product::factory()->create();
        $productComponent = ProductComponent::factory()->create([
            'product_id' => $product->id,
            'component_id' => $component->id,
        ]);

        $this->assertInstanceOf(Product::class, $productComponent->product);
        $this->assertEquals($product->id, $productComponent->product->id);
    }

    public function test_product_component_component_relationship()
    {
        $product = Product::factory()->create();
        $component = Product::factory()->create();
        $productComponent = ProductComponent::factory()->create([
            'product_id' => $product->id,
            'component_id' => $component->id,
        ]);

        $this->assertInstanceOf(Product::class, $productComponent->component);
        $this->assertEquals($component->id, $productComponent->component->id);
    }
}
