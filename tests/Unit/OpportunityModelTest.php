<?php

namespace Tests\Unit;

use App\Models\Opportunity;
use App\Models\Lead;
use App\Models\Client;
use App\Models\User;
use App\Models\OpportunityItem;
use App\Enums\OpportunityStage;
use App\Enums\OpportunityStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityModelTest extends TestCase
{
  use RefreshDatabase;

  public function test_opportunity_model_can_be_created_with_valid_data()
  {
    $lead = Lead::factory()->create();
    $client = Client::factory()->create();
    $user = User::factory()->create();

    $opportunityData = [
      'lead_id' => $lead->id,
      'client_id' => $client->id,
      'title' => 'Sistema ERP Empresarial',
      'description' => 'Implementação completa de sistema ERP',
      'stage' => OpportunityStage::PROPOSTA,
      'probability' => 60,
      'expected_value' => 150000.00,
      'expected_close_date' => now()->addMonths(3),
      'owner_id' => $user->id,
      'status' => OpportunityStatus::ATIVA,
    ];

    $opportunity = Opportunity::create($opportunityData);

    $this->assertInstanceOf(Opportunity::class, $opportunity);
    $this->assertEquals('Sistema ERP Empresarial', $opportunity->title);
    $this->assertEquals(60, $opportunity->probability);
    $this->assertEquals(150000.00, $opportunity->expected_value);
    $this->assertEquals(OpportunityStage::PROPOSTA, $opportunity->stage);
    $this->assertEquals(OpportunityStatus::ATIVA, $opportunity->status);
  }

  public function test_opportunity_fillable_attributes_are_correct()
  {
    $fillable = [
      'lead_id',
      'client_id',
      'title',
      'description',
      'stage',
      'probability',
      'expected_value',
      'expected_close_date',
      'owner_id',
      'status',
    ];

    $this->assertEquals($fillable, (new Opportunity)->getFillable());
  }

  public function test_opportunity_casts_are_correct()
  {
    $casts = [
      'id' => 'int',
      'stage' => OpportunityStage::class,
      'status' => OpportunityStatus::class,
      'expected_close_date' => 'date',
    ];

    $this->assertEquals($casts, (new Opportunity)->getCasts());
  }

  public function test_opportunity_lead_relationship()
  {
    $lead = Lead::factory()->create();
    $opportunity = Opportunity::factory()->create(['lead_id' => $lead->id]);

    $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $opportunity->lead());
    $this->assertInstanceOf(Lead::class, $opportunity->lead);
    $this->assertEquals($lead->id, $opportunity->lead->id);
  }

  public function test_opportunity_client_relationship()
  {
    $client = Client::factory()->create();
    $opportunity = Opportunity::factory()->create(['client_id' => $client->id]);

    $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $opportunity->client());
    $this->assertInstanceOf(Client::class, $opportunity->client);
    $this->assertEquals($client->id, $opportunity->client->id);
  }

  public function test_opportunity_owner_relationship()
  {
    $user = User::factory()->create();
    $opportunity = Opportunity::factory()->create(['owner_id' => $user->id]);

    $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $opportunity->owner());
    $this->assertInstanceOf(User::class, $opportunity->owner);
    $this->assertEquals($user->id, $opportunity->owner->id);
  }

  public function test_opportunity_items_relationship()
  {
    $opportunity = Opportunity::factory()->create();
    $item = OpportunityItem::factory()->create(['opportunity_id' => $opportunity->id]);

    $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $opportunity->items());
    $this->assertTrue($opportunity->items->contains($item));
  }

  public function test_opportunity_stage_enum_casting()
  {
    $opportunity = Opportunity::factory()->create(['stage' => OpportunityStage::NEGOCIACAO]);

    $this->assertInstanceOf(OpportunityStage::class, $opportunity->stage);
    $this->assertEquals(OpportunityStage::NEGOCIACAO, $opportunity->stage);
    $this->assertEquals('negotiation', $opportunity->stage->value);
  }

  public function test_opportunity_status_enum_casting()
  {
    $opportunity = Opportunity::factory()->create(['status' => OpportunityStatus::ATIVA]);

    $this->assertInstanceOf(OpportunityStatus::class, $opportunity->status);
    $this->assertEquals(OpportunityStatus::ATIVA, $opportunity->status);
    $this->assertEquals('active', $opportunity->status->value);
  }

  public function test_opportunity_stage_enum_values()
  {
    $expectedStages = ['new', 'contact', 'proposal', 'negotiation', 'won', 'lost'];

    foreach (OpportunityStage::cases() as $stage) {
      $this->assertContains($stage->value, $expectedStages);
    }
  }

  public function test_opportunity_status_enum_values()
  {
    $expectedStatuses = ['active', 'inactive'];

    foreach (OpportunityStatus::cases() as $status) {
      $this->assertContains($status->value, $expectedStatuses);
    }
  }
}
