<?php

namespace Tests\Unit;

use App\Models\Sector;
use App\Models\User;
use App\Observers\SectorObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SectorModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_sector_has_fillable_attributes()
    {
        $fillable = ['name', 'status'];
        $sector = new Sector();

        foreach ($fillable as $attribute) {
            $this->assertContains($attribute, $sector->getFillable());
        }
    }

    public function test_sector_has_correct_casts()
    {
        $sector = new Sector();
        $casts = $sector->getCasts();

        $this->assertEquals('string', $casts['status']);
    }

    public function test_sector_registers_observer()
    {
        $sector = new Sector();
        $observers = $sector->getObservableEvents();

        $this->assertContains('created', $observers);
        $this->assertContains('updated', $observers);
        $this->assertContains('deleted', $observers);
    }

    public function test_sector_belongs_to_created_by_user()
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $sector->createdBy);
        $this->assertEquals($user->id, $sector->createdBy->id);
    }

    public function test_sector_belongs_to_updated_by_user()
    {
        $user = User::factory()->create();
        $sector = Sector::factory()->create(['updated_by' => $user->id]);

        $this->assertInstanceOf(User::class, $sector->updatedBy);
        $this->assertEquals($user->id, $sector->updatedBy->id);
    }

    public function test_scope_active_filters_active_sectors()
    {
        Sector::factory()->create(['status' => 'active']);
        Sector::factory()->create(['status' => 'inactive']);
        Sector::factory()->create(['status' => 'active']);

        $activeSectors = Sector::active()->get();

        $this->assertCount(2, $activeSectors);
        $activeSectors->each(function ($sector) {
            $this->assertEquals('active', $sector->status);
        });
    }

    public function test_scope_search_filters_by_name()
    {
        Sector::factory()->create(['name' => 'Produção']);
        Sector::factory()->create(['name' => 'Manutenção']);
        Sector::factory()->create(['name' => 'Qualidade']);

        $results = Sector::search('Produção')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Produção', $results->first()->name);
    }

    public function test_scope_search_filters_by_partial_name()
    {
        Sector::factory()->create(['name' => 'Produção']);
        Sector::factory()->create(['name' => 'Manutenção']);
        Sector::factory()->create(['name' => 'Qualidade']);

        $results = Sector::search('ção')->get();

        $this->assertCount(2, $results);
        $results->each(function ($sector) {
            $this->assertStringContainsString('ção', $sector->name);
        });
    }

    public function test_scope_search_is_case_insensitive()
    {
        Sector::factory()->create(['name' => 'Produção']);
        Sector::factory()->create(['name' => 'Manutenção']);

        $results = Sector::search('produção')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Produção', $results->first()->name);
    }

    public function test_observer_logs_creation()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Setor criado', \Mockery::on(function ($data) {
                return isset($data['sector_id']) &&
                    isset($data['name']) &&
                    isset($data['status']) &&
                    isset($data['user_id']);
            }));
    }

    public function test_observer_logs_update()
    {
        $sector = Sector::factory()->create();

        Log::shouldReceive('info')
            ->once()
            ->with('Setor atualizado', \Mockery::on(function ($data) {
                return isset($data['sector_id']) &&
                    isset($data['changes']) &&
                    isset($data['user_id']);
            }));
    }

    public function test_observer_logs_deletion()
    {
        $sector = Sector::factory()->create();

        Log::shouldReceive('info')
            ->once()
            ->with('Setor excluído', \Mockery::on(function ($data) {
                return isset($data['sector_id']) &&
                    isset($data['name']) &&
                    isset($data['status']) &&
                    isset($data['user_id']);
            }));
    }
}
