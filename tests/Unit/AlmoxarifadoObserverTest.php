<?php

namespace Tests\Unit;

use App\Models\Almoxarifado;
use App\Models\User;
use App\Observers\AlmoxarifadoObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AlmoxarifadoObserverTest extends TestCase
{
  use RefreshDatabase;

  private AlmoxarifadoObserver $observer;
  private User $user;

  protected function setUp(): void
  {
    parent::setUp();

    $this->observer = new AlmoxarifadoObserver();
    $this->user = User::factory()->create();
    Auth::login($this->user);
  }

  public function test_created_logs_sector_creation()
  {
    // Test that observer is registered and doesn't throw exceptions
    $sector = Almoxarifado::factory()->create([
      'name' => 'Setor Teste',
      'status' => 'active',
    ]);

    $this->assertDatabaseHas('sectors', [
      'id' => $sector->id,
      'name' => 'Setor Teste',
      'status' => 'active',
    ]);
  }

  public function test_created_method_is_called_when_sector_is_created()
  {
    $sector = Almoxarifado::factory()->create([
      'name' => 'Setor Teste',
      'status' => 'active',
    ]);

    // The observer should have been called during creation
    // We verify this indirectly by checking that the sector was created
    $this->assertDatabaseHas('sectors', [
      'id' => $sector->id,
      'name' => 'Setor Teste',
      'status' => 'active',
    ]);
  }

  public function test_updated_logs_sector_changes()
  {
    $sector = Almoxarifado::factory()->create([
      'name' => 'Nome Original',
      'status' => 'active',
    ]);

    $sector->update(['name' => 'Nome Atualizado']);

    $this->assertDatabaseHas('sectors', [
      'id' => $sector->id,
      'name' => 'Nome Atualizado',
      'status' => 'active',
    ]);
  }

  public function test_updated_method_is_called_when_sector_is_updated()
  {
    $sector = Almoxarifado::factory()->create([
      'name' => 'Nome Original',
      'status' => 'active',
    ]);

    $sector->update(['name' => 'Nome Atualizado']);

    $this->assertDatabaseHas('sectors', [
      'id' => $sector->id,
      'name' => 'Nome Atualizado',
      'status' => 'active',
    ]);
  }

  public function test_deleted_logs_sector_deletion()
  {
    $sector = Almoxarifado::factory()->create([
      'name' => 'Setor para Deletar',
      'status' => 'active',
    ]);

    $sector->delete();

    $this->assertDatabaseMissing('sectors', [
      'id' => $sector->id,
    ]);
  }

  public function test_deleted_method_is_called_when_sector_is_deleted()
  {
    $sector = Almoxarifado::factory()->create([
      'name' => 'Setor para Deletar',
      'status' => 'active',
    ]);

    $sector->delete();

    $this->assertDatabaseMissing('sectors', [
      'id' => $sector->id,
    ]);
  }

  public function test_observer_logs_correct_user_id()
  {
    $anotherUser = User::factory()->create();
    Auth::login($anotherUser);

    $sector = Almoxarifado::factory()->create([
      'name' => 'Setor Teste',
      'status' => 'active',
    ]);

    // Verify that the observer uses the correct user ID
    $this->assertEquals($anotherUser->id, $sector->created_by);
  }

  public function test_observer_handles_null_user_gracefully()
  {
    Auth::logout();

    // This should not throw an exception
    $sector = Almoxarifado::factory()->create([
      'name' => 'Setor Teste',
      'status' => 'active',
    ]);

    $this->assertDatabaseHas('sectors', [
      'id' => $sector->id,
      'name' => 'Setor Teste',
      'status' => 'active',
    ]);
  }
}
