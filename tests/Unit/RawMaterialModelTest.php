<?php

namespace Tests\Unit;

use App\Models\RawMaterial;
use App\Models\User;
use App\Observers\RawMaterialObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RawMaterialModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_sector_has_fillable_attributes()
    {
        $fillable = ['name', 'status'];
        $sector = new RawMaterial();

        foreach ($fillable as $attribute) {
            $this->assertContains($attribute, $sector->getFillable());
        }
    }

    public function test_sector_has_correct_casts()
    {
        $sector = new RawMaterial();
        $casts = $sector->getCasts();

        $this->assertEquals('string', $casts['status']);
    }

    public function test_sector_registers_observer()
    {
        $sector = new RawMaterial();
        $observers = $sector->getObservableEvents();

        $this->assertContains('created', $observers);
        $this->assertContains('updated', $observers);
        $this->assertContains('deleted', $observers);
    }

    public function test_sector_belongs_to_created_by_user()
    {
        $user = User::factory()->create();
        $sector = RawMaterial::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $sector->createdBy);
        $this->assertEquals($user->id, $sector->createdBy->id);
    }

    public function test_sector_belongs_to_updated_by_user()
    {
        $user = User::factory()->create();
        $sector = RawMaterial::factory()->create(['updated_by' => $user->id]);

        $this->assertInstanceOf(User::class, $sector->updatedBy);
        $this->assertEquals($user->id, $sector->updatedBy->id);
    }

    public function test_scope_active_filters_active_sectors()
    {
        RawMaterial::factory()->create(['name' => 'Test Active', 'status' => 'active']);
        RawMaterial::factory()->create(['name' => 'Test Inactive', 'status' => 'inactive']);

        $active = RawMaterial::active()->get();

        $this->assertCount(1, $active);
        $this->assertEquals('Test Active', $active->first()->name);
    }

    public function test_scope_search_filters_by_name()
    {
        RawMaterial::factory()->create(['name' => 'Test Search 1']);
        RawMaterial::factory()->create(['name' => 'Test Search 2']);
        RawMaterial::factory()->create(['name' => 'Other']);

        $results = RawMaterial::search('Test Search 1')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Test Search 1', $results->first()->name);
    }

    public function test_scope_search_filters_by_partial_name()
    {
        RawMaterial::factory()->create(['name' => 'Test Partial']);
        RawMaterial::factory()->create(['name' => 'Other Partial']);
        RawMaterial::factory()->create(['name' => 'No Match']);

        $results = RawMaterial::search('Partial')->get();

        $this->assertCount(2, $results);
        $results->each(function ($sector) {
            $this->assertStringContainsString('Partial', $sector->name);
        });
    }

    public function test_scope_search_is_case_insensitive()
    {
        RawMaterial::factory()->create(['name' => 'Test Case']);
        RawMaterial::factory()->create(['name' => 'Other']);

        $results = RawMaterial::search('test')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Test Case', $results->first()->name);
    }

    public function test_observer_logs_creation()
    {
        $user = User::factory()->create();
        Auth::login($user);

        Log::shouldReceive('info')
            ->once()
            ->with('Matéria-prima criada', \Mockery::on(function ($data) {
                return isset($data['raw_material_id']) &&
                    isset($data['name']) &&
                    isset($data['status']) &&
                    isset($data['user_id']);
            }));

        $sector = RawMaterial::factory()->create();
    }

    public function test_observer_logs_update()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $sector = RawMaterial::factory()->create();

        Log::shouldReceive('info')
            ->once()
            ->with('Matéria-prima atualizada', \Mockery::on(function ($data) {
                return isset($data['raw_material_id']) &&
                    isset($data['changes']) &&
                    isset($data['user_id']);
            }));

        $sector->update(['name' => 'New Name']);
    }

    public function test_observer_logs_deletion()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $sector = RawMaterial::factory()->create();

        Log::shouldReceive('info')
            ->once()
            ->with('Matéria-prima excluída', \Mockery::on(function ($data) {
                return isset($data['raw_material_id']) &&
                    isset($data['name']) &&
                    isset($data['status']) &&
                    isset($data['user_id']);
            }));

        $sector->delete();
    }
}
