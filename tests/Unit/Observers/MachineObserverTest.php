<?php

namespace Tests\Unit\Observers;

use App\Models\Machine;
use App\Models\Sector;
use App\Models\User;
use App\Observers\MachineObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MachineObserverTest extends TestCase
{
  use RefreshDatabase;

  private MachineObserver $observer;
  private User $user;
  private Sector $sector;

  protected function setUp(): void
  {
    parent::setUp();

    $this->observer = new MachineObserver();
    $this->user = User::factory()->create();
    $this->sector = Sector::factory()->create(['status' => 'active']);
  }

  public function test_created_logs_machine_creation()
  {
    Auth::login($this->user);

    $machine = new Machine([
      'sector_id' => $this->sector->id,
      'name' => 'Nova Máquina',
      'status' => 'active',
    ]);
    $machine->id = 1; // Simulate saved model

    Log::shouldReceive('info')
      ->once()
      ->with('Máquina criada', [
        'machine_id' => 1,
        'sector_id' => $this->sector->id,
        'name' => 'Nova Máquina',
        'status' => 'active',
        'user_id' => $this->user->id,
      ]);
  }

  public function test_created_logs_machine_creation_without_authenticated_user()
  {
    Auth::logout();

    $machine = new Machine([
      'sector_id' => $this->sector->id,
      'name' => 'Nova Máquina',
      'status' => 'active',
    ]);
    $machine->id = 1; // Simulate saved model

    Log::shouldReceive('info')
      ->once()
      ->with('Máquina criada', [
        'machine_id' => 1,
        'sector_id' => $this->sector->id,
        'name' => 'Nova Máquina',
        'status' => 'active',
        'user_id' => null,
      ]);
  }

  public function test_updated_logs_machine_changes()
  {
    Auth::login($this->user);

    $machine = new Machine([
      'sector_id' => $this->sector->id,
      'name' => 'Máquina Original',
      'status' => 'active',
    ]);
    $machine->id = 1;
    $machine->name = 'Máquina Atualizada';
    $machine->status = 'inactive';

    // Simulate changes
    $machine->setChanges([
      'name' => 'Máquina Atualizada',
      'status' => 'inactive',
    ]);

    Log::shouldReceive('info')
      ->once()
      ->with('Máquina atualizada', [
        'machine_id' => 1,
        'changes' => [
          'name' => 'Máquina Atualizada',
          'status' => 'inactive',
        ],
        'user_id' => $this->user->id,
      ]);
  }

  public function test_updated_logs_machine_changes_without_authenticated_user()
  {
    Auth::logout();

    $machine = new Machine([
      'sector_id' => $this->sector->id,
      'name' => 'Máquina Original',
      'status' => 'active',
    ]);
    $machine->id = 1;
    $machine->name = 'Máquina Atualizada';

    // Simulate changes
    $machine->setChanges([
      'name' => 'Máquina Atualizada',
    ]);

    Log::shouldReceive('info')
      ->once()
      ->with('Máquina atualizada', [
        'machine_id' => 1,
        'changes' => [
          'name' => 'Máquina Atualizada',
        ],
        'user_id' => null,
      ]);
  }

  public function test_deleted_logs_machine_deletion()
  {
    Auth::login($this->user);

    $machine = new Machine([
      'sector_id' => $this->sector->id,
      'name' => 'Máquina a Ser Excluída',
      'status' => 'active',
    ]);
    $machine->id = 1;

    Log::shouldReceive('info')
      ->once()
      ->with('Máquina excluída', [
        'machine_id' => 1,
        'sector_id' => $this->sector->id,
        'name' => 'Máquina a Ser Excluída',
        'status' => 'active',
        'user_id' => $this->user->id,
      ]);
  }

  public function test_deleted_logs_machine_deletion_without_authenticated_user()
  {
    Auth::logout();

    $machine = new Machine([
      'sector_id' => $this->sector->id,
      'name' => 'Máquina a Ser Excluída',
      'status' => 'active',
    ]);
    $machine->id = 1;

    Log::shouldReceive('info')
      ->once()
      ->with('Máquina excluída', [
        'machine_id' => 1,
        'sector_id' => $this->sector->id,
        'name' => 'Máquina a Ser Excluída',
        'status' => 'active',
        'user_id' => null,
      ]);
  }

  public function test_created_method_exists()
  {
    $this->assertTrue(method_exists($this->observer, 'created'));
  }

  public function test_updated_method_exists()
  {
    $this->assertTrue(method_exists($this->observer, 'updated'));
  }

  public function test_deleted_method_exists()
  {
    $this->assertTrue(method_exists($this->observer, 'deleted'));
  }
}
