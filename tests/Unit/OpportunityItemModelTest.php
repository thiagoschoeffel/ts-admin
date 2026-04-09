<?php

namespace Tests\Unit;

use App\Models\OpportunityItem;
use App\Models\Opportunity;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityItemModelTest extends TestCase
{
  use RefreshDatabase;

  public function test_opportunity_item_model_can_be_created_with_valid_data()
  {
    $opportunity = Opportunity::factory()->create();
    $product = Product::factory()->create();

    $itemData = [
      'opportunity_id' => $opportunity->id,
      'product_id' => $product->id,
      'quantity' => 5,
      'unit_price' => 100.00,
      'subtotal' => 500.00,
    ];

    $item = OpportunityItem::create($itemData);

    $this->assertInstanceOf(OpportunityItem::class, $item);
    $this->assertEquals(5, $item->quantity);
    $this->assertEquals(100.00, $item->unit_price);
    $this->assertEquals(500.00, $item->subtotal);
  }

  public function test_opportunity_item_fillable_attributes_are_correct()
  {
    $fillable = [
      'opportunity_id',
      'product_id',
      'quantity',
      'unit_price',
      'subtotal',
    ];

    $this->assertEquals($fillable, (new OpportunityItem)->getFillable());
  }

  public function test_opportunity_item_opportunity_relationship()
  {
    $opportunity = Opportunity::factory()->create();
    $item = OpportunityItem::factory()->create(['opportunity_id' => $opportunity->id]);

    $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $item->opportunity());
    $this->assertInstanceOf(Opportunity::class, $item->opportunity);
    $this->assertEquals($opportunity->id, $item->opportunity->id);
  }

  public function test_opportunity_item_product_relationship()
  {
    $product = Product::factory()->create();
    $item = OpportunityItem::factory()->create(['product_id' => $product->id]);

    $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $item->product());
    $this->assertInstanceOf(Product::class, $item->product);
    $this->assertEquals($product->id, $item->product->id);
  }
}
