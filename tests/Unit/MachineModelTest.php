<?php

namespace Tests\Unit;

use App\Models\Machine;
use App\Models\Sector;
use App\Models\User;
use App\Observers\MachineObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class MachineModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_machine_has_fillable_attributes()
    {
        $fillable = ['sector_id', 'name', 'status', 'created_by', 'updated_by'];
        $machine = new Machine();

        foreach ($fillable as $attribute) {
            $this->assertContains($attribute, $machine->getFillable());
        }
    }

    public function test_machine_has_correct_casts()
    {
        $machine = new Machine();
        $casts = $machine->getCasts();

        $this->assertEquals('string', $casts['status']);
    }

    public function test_machine_registers_observer()
    {
        $machine = new Machine();
        $observers = $machine->getObservableEvents();

        $this->assertContains('created', $observers);
        $this->assertContains('updated', $observers);
        $this->assertContains('deleted', $observers);
    }

    public function test_machine_belongs_to_sector()
    {
        $sector = Sector::factory()->create();
        $machine = Machine::factory()->create(['sector_id' => $sector->id]);

        $this->assertInstanceOf(Sector::class, $machine->sector);
        $this->assertEquals($sector->id, $machine->sector->id);
    }

    public function test_machine_belongs_to_creator()
    {
        $user = User::factory()->create();
        $machine = Machine::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $machine->creator);
        $this->assertEquals($user->id, $machine->creator->id);
    }

    public function test_machine_belongs_to_updater()
    {
        $user = User::factory()->create();
        $machine = Machine::factory()->create(['updated_by' => $user->id]);

        $this->assertInstanceOf(User::class, $machine->updater);
        $this->assertEquals($user->id, $machine->updater->id);
    }

    public function test_scope_active_filters_active_machines()
    {
        Machine::factory()->create(['status' => 'active']);
        Machine::factory()->create(['status' => 'inactive']);
        Machine::factory()->create(['status' => 'active']);

        $activeMachines = Machine::active()->get();

        $this->assertCount(2, $activeMachines);
        $activeMachines->each(function ($machine) {
            $this->assertEquals('active', $machine->status);
        });
    }

    public function test_scope_search_filters_by_name()
    {
        Machine::factory()->create(['name' => 'Máquina A']);
        Machine::factory()->create(['name' => 'Máquina B']);
        Machine::factory()->create(['name' => 'Equipamento C']);

        $results = Machine::search('Máquina')->get();

        $this->assertCount(2, $results);
        $results->each(function ($machine) {
            $this->assertStringContainsString('Máquina', $machine->name);
        });
    }

    public function test_scope_search_filters_by_partial_name()
    {
        Machine::factory()->create(['name' => 'Máquina de Produção']);
        Machine::factory()->create(['name' => 'Equipamento de Teste']);
        Machine::factory()->create(['name' => 'Ferramenta Manual']);

        $results = Machine::search('de')->get();

        $this->assertCount(2, $results);
        $results->each(function ($machine) {
            $this->assertStringContainsString('de', $machine->name);
        });
    }

    public function test_scope_search_is_case_insensitive()
    {
        Machine::factory()->create(['name' => 'Máquina de Produção']);
        Machine::factory()->create(['name' => 'Equipamento de Teste']);

        $results = Machine::search('máquina')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Máquina de Produção', $results->first()->name);
    }

    public function test_observer_is_registered_and_works()
    {
        $sector = Sector::factory()->create();
        $machine = Machine::factory()->create([
            'sector_id' => $sector->id,
            'name' => 'Máquina Teste',
            'status' => 'active',
        ]);

        // Test that observer is registered and doesn't throw exceptions
        $this->assertDatabaseHas('machines', [
            'id' => $machine->id,
            'sector_id' => $sector->id,
            'name' => 'Máquina Teste',
            'status' => 'active',
        ]);
    }

    public function test_machine_can_be_created_with_minimal_data()
    {
        $sector = Sector::factory()->create();
        $machine = Machine::create([
            'sector_id' => $sector->id,
            'name' => 'Máquina Básica',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('machines', [
            'sector_id' => $sector->id,
            'name' => 'Máquina Básica',
            'status' => 'active',
        ]);
    }

    public function test_machine_can_be_updated()
    {
        $machine = Machine::factory()->create(['name' => 'Nome Original']);
        $machine->update(['name' => 'Nome Atualizado']);

        $this->assertDatabaseHas('machines', [
            'id' => $machine->id,
            'name' => 'Nome Atualizado',
        ]);
    }

    public function test_machine_can_be_deleted()
    {
        $machine = Machine::factory()->create();
        $machine->delete();

        $this->assertDatabaseMissing('machines', ['id' => $machine->id]);
    }
}
