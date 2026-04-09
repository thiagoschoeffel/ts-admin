<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_model_can_be_created_with_valid_data()
    {
        $user = User::factory()->create();
        $productData = [
            'name' => 'Product Name',
            'code' => 'PROD001',
            'description' => 'Product Description',
            'price' => 100.50,
            'unit_of_measure' => 'unit',
            'status' => 'active',
            'length' => 10.0,
            'width' => 5.0,
            'height' => 2.0,
            'weight' => 1.5,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];

        $product = Product::create($productData);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Product Name', $product->name);
        $this->assertEquals('PROD001', $product->code);
        $this->assertEquals(100.50, $product->price);
        $this->assertEquals('active', $product->status);
    }

    public function test_product_fillable_attributes_are_correct()
    {
        $fillable = [
            'name',
            'code',
            'description',
            'price',
            'unit_of_measure',
            'status',
            'length',
            'width',
            'height',
            'weight',
            'created_by',
            'updated_by',
            'deleted_by',
        ];
        $this->assertEquals($fillable, (new Product)->getFillable());
    }

    public function test_product_uses_soft_deletes()
    {
        $product = new Product;
        $this->assertContains('Illuminate\Database\Eloquent\SoftDeletes', class_uses($product));
    }

    public function test_product_created_by_relationship()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $product->createdBy);
        $this->assertEquals($user->id, $product->createdBy->id);
    }

    public function test_product_updated_by_relationship()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['updated_by' => $user->id]);

        $this->assertInstanceOf(User::class, $product->updatedBy);
        $this->assertEquals($user->id, $product->updatedBy->id);
    }

    public function test_product_components_relationship()
    {
        $product = Product::factory()->create();
        $component = Product::factory()->create();

        $product->components()->attach($component, ['quantity' => 2]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $product->components());
        $this->assertTrue($product->components->contains($component));
    }

    public function test_product_parents_relationship()
    {
        $component = Product::factory()->create();
        $parent = Product::factory()->create();

        // Ensure no existing relationship
        $parent->components()->detach($component);

        $parent->components()->attach($component, ['quantity' => 1]);

        $this->assertCount(1, $component->parents);
        $this->assertEquals($parent->id, $component->parents->first()->id);
    }

    public function test_product_formatted_price()
    {
        $product = Product::factory()->create(['price' => 1234.56]);
        $this->assertEquals('R$ 1.234,56', $product->formattedPrice());
    }
}
