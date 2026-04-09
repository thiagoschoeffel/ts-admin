<?php

namespace Tests\Unit;

use App\Models\Almoxarifado;
use App\Models\User;
use App\Observers\AlmoxarifadoObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AlmoxarifadoModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_sector_has_fillable_attributes()
    {
        $fillable = ['name', 'status'];
        $sector = new Almoxarifado();

        foreach ($fillable as $attribute) {
            $this->assertContains($attribute, $sector->getFillable());
        }
    }

    public function test_sector_has_correct_casts()
    {
        $sector = new Almoxarifado();
        $casts = $sector->getCasts();

        $this->assertEquals('string', $casts['status']);
    }

    public function test_sector_registers_observer()
    {
        $sector = new Almoxarifado();
        $observers = $sector->getObservableEvents();

        $this->assertContains('created', $observers);
        $this->assertContains('updated', $observers);
        $this->assertContains('deleted', $observers);
    }

    public function test_sector_belongs_to_created_by_user()
    {
        $user = User::factory()->create();
        $sector = Almoxarifado::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $sector->createdBy);
        $this->assertEquals($user->id, $sector->createdBy->id);
    }

    public function test_sector_belongs_to_updated_by_user()
    {
        $user = User::factory()->create();
        $sector = Almoxarifado::factory()->create(['updated_by' => $user->id]);

        $this->assertInstanceOf(User::class, $sector->updatedBy);
        $this->assertEquals($user->id, $sector->updatedBy->id);
    }

    public function test_scope_active_filters_active_sectors()
    {
        Almoxarifado::factory()->create(['status' => 'active']);
        Almoxarifado::factory()->create(['status' => 'inactive']);
        Almoxarifado::factory()->create(['status' => 'active']);

        $activeAlmoxarifados = Almoxarifado::active()->get();

        $this->assertCount(2, $activeAlmoxarifados);
        $activeAlmoxarifados->each(function ($sector) {
            $this->assertEquals('active', $sector->status);
        });
    }

    public function test_scope_search_filters_by_name()
    {
        Almoxarifado::factory()->create(['name' => 'Produção']);
        Almoxarifado::factory()->create(['name' => 'Manutenção']);
        Almoxarifado::factory()->create(['name' => 'Qualidade']);

        $results = Almoxarifado::search('Produção')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Produção', $results->first()->name);
    }

    public function test_scope_search_filters_by_partial_name()
    {
        Almoxarifado::factory()->create(['name' => 'Produção']);
        Almoxarifado::factory()->create(['name' => 'Manutenção']);
        Almoxarifado::factory()->create(['name' => 'Qualidade']);

        $results = Almoxarifado::search('ção')->get();

        $this->assertCount(2, $results);
        $results->each(function ($sector) {
            $this->assertStringContainsString('ção', $sector->name);
        });
    }

    public function test_scope_search_is_case_insensitive()
    {
        Almoxarifado::factory()->create(['name' => 'Produção']);
        Almoxarifado::factory()->create(['name' => 'Manutenção']);

        $results = Almoxarifado::search('produção')->get();

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
        $sector = Almoxarifado::factory()->create();

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
        $sector = Almoxarifado::factory()->create();

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
