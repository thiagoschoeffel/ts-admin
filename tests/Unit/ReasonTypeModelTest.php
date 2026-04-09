<?php

namespace Tests\Unit;

use App\Models\ReasonType;
use App\Models\User;
use App\Observers\ReasonTypeObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ReasonTypeModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_reason_type_has_fillable_attributes()
    {
        $fillable = ['name', 'status', 'created_by', 'updated_by'];
        $reasonType = new ReasonType();

        foreach ($fillable as $attribute) {
            $this->assertContains($attribute, $reasonType->getFillable());
        }
    }

    public function test_reason_type_has_correct_casts()
    {
        $reasonType = new ReasonType();
        $casts = $reasonType->getCasts();

        $this->assertEquals('string', $casts['status']);
    }

    public function test_reason_type_registers_observer()
    {
        $reasonType = new ReasonType();
        $observers = $reasonType->getObservableEvents();

        $this->assertContains('created', $observers);
        $this->assertContains('updated', $observers);
        $this->assertContains('deleted', $observers);
    }

    public function test_reason_type_belongs_to_creator()
    {
        $user = User::factory()->create();
        $reasonType = ReasonType::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $reasonType->creator);
        $this->assertEquals($user->id, $reasonType->creator->id);
    }

    public function test_reason_type_belongs_to_updater()
    {
        $user = User::factory()->create();
        $reasonType = ReasonType::factory()->create(['updated_by' => $user->id]);

        $this->assertInstanceOf(User::class, $reasonType->updater);
        $this->assertEquals($user->id, $reasonType->updater->id);
    }

    public function test_reason_type_scope_active()
    {
        ReasonType::factory()->create(['status' => 'active']);
        ReasonType::factory()->create(['status' => 'inactive']);

        $activeReasonTypes = ReasonType::active()->get();

        $this->assertCount(1, $activeReasonTypes);
        $this->assertEquals('active', $activeReasonTypes->first()->status);
    }

    public function test_reason_type_scope_search()
    {
        ReasonType::factory()->create(['name' => 'Qualidade']);
        ReasonType::factory()->create(['name' => 'Manutenção']);
        ReasonType::factory()->create(['name' => 'Setup']);

        $searchResults = ReasonType::search('Qual')->get();

        $this->assertCount(1, $searchResults);
        $this->assertEquals('Qualidade', $searchResults->first()->name);
    }

    public function test_reason_type_can_be_created()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $reasonType = ReasonType::create([
            'name' => 'Test Reason Type',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('reason_types', [
            'name' => 'Test Reason Type',
            'status' => 'active',
            'created_by' => $user->id,
        ]);
    }

    public function test_reason_type_can_be_updated()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $reasonType = ReasonType::factory()->create(['name' => 'Original Name']);

        $reasonType->update([
            'name' => 'Updated Name',
            'status' => 'inactive',
            'updated_by' => $user->id,
        ]);

        $this->assertDatabaseHas('reason_types', [
            'name' => 'Updated Name',
            'status' => 'inactive',
            'updated_by' => $user->id,
        ]);
    }

    public function test_reason_type_can_be_deleted()
    {
        $reasonType = ReasonType::factory()->create();

        $reasonType->delete();

        $this->assertSoftDeleted($reasonType);
    }
}
