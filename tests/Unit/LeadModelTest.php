<?php

namespace Tests\Unit;

use App\Models\Lead;
use App\Models\User;
use App\Models\Opportunity;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_model_can_be_created_with_valid_data()
    {
        $user = User::factory()->create();
        $leadData = [
            'name' => 'João Silva',
            'email' => 'joao.silva@example.com',
            'phone' => '11999999999',
            'company' => 'Empresa Teste',
            'source' => LeadSource::SITE,
            'status' => LeadStatus::NOVO,
            'owner_id' => $user->id,
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ];

        $lead = Lead::create($leadData);

        $this->assertInstanceOf(Lead::class, $lead);
        $this->assertEquals('João Silva', $lead->name);
        $this->assertEquals('joao.silva@example.com', $lead->email);
        $this->assertEquals('11999999999', $lead->phone);
        $this->assertEquals('Empresa Teste', $lead->company);
        $this->assertEquals(LeadSource::SITE, $lead->source);
        $this->assertEquals(LeadStatus::NOVO, $lead->status);
        $this->assertEquals($user->id, $lead->owner_id);
    }

    public function test_lead_fillable_attributes_are_correct()
    {
        $fillable = [
            'name',
            'email',
            'phone',
            'company',
            'source',
            'status',
            'owner_id',
            'created_by_id',
            'updated_by_id',
        ];

        $this->assertEquals($fillable, (new Lead)->getFillable());
    }

    public function test_lead_casts_are_correct()
    {
        $casts = [
            'id' => 'int',
            'status' => LeadStatus::class,
            'source' => LeadSource::class,
        ];

        $this->assertEquals($casts, (new Lead)->getCasts());
    }

    public function test_lead_owner_relationship()
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create(['owner_id' => $user->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $lead->owner());
        $this->assertInstanceOf(User::class, $lead->owner);
        $this->assertEquals($user->id, $lead->owner->id);
    }

    public function test_lead_created_by_relationship()
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create(['created_by_id' => $user->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $lead->createdBy());
        $this->assertInstanceOf(User::class, $lead->createdBy);
        $this->assertEquals($user->id, $lead->createdBy->id);
    }

    public function test_lead_updated_by_relationship()
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create(['updated_by_id' => $user->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $lead->updatedBy());
        $this->assertInstanceOf(User::class, $lead->updatedBy);
        $this->assertEquals($user->id, $lead->updatedBy->id);
    }

    public function test_lead_opportunities_relationship()
    {
        // Skip this test as OpportunityFactory doesn't exist yet
        $this->markTestSkipped('OpportunityFactory not implemented yet');
    }

    public function test_lead_source_enum_casting()
    {
        $lead = Lead::factory()->create(['source' => LeadSource::SITE]);

        $this->assertInstanceOf(LeadSource::class, $lead->source);
        $this->assertEquals(LeadSource::SITE, $lead->source);
        $this->assertEquals('site', $lead->source->value);
    }

    public function test_lead_status_enum_casting()
    {
        $lead = Lead::factory()->create(['status' => LeadStatus::QUALIFICADO]);

        $this->assertInstanceOf(LeadStatus::class, $lead->status);
        $this->assertEquals(LeadStatus::QUALIFICADO, $lead->status);
        $this->assertEquals('qualified', $lead->status->value);
    }

    public function test_lead_source_enum_values()
    {
        $expectedSources = ['site', 'indicacao', 'evento', 'manual'];

        foreach (LeadSource::cases() as $source) {
            $this->assertContains($source->value, $expectedSources);
        }
    }

    public function test_lead_status_enum_values()
    {
        $expectedStatuses = ['new', 'in_contact', 'qualified', 'discarded'];

        foreach (LeadStatus::cases() as $status) {
            $this->assertContains($status->value, $expectedStatuses);
        }
    }
}
