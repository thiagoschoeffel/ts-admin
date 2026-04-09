<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreMachineRequest;
use App\Models\Machine;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class StoreMachineRequestTest extends TestCase
{
  use RefreshDatabase;

  private User $admin;
  private User $userWithPermission;
  private User $userWithoutPermission;
  private Sector $activeSector;
  private Sector $inactiveSector;

  protected function setUp(): void
  {
    parent::setUp();

    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->userWithPermission = User::factory()->create([
      'role' => 'user',
      'permissions' => ['machines' => ['create' => true]]
    ]);
    $this->userWithoutPermission = User::factory()->create([
      'role' => 'user',
      'permissions' => ['machines' => ['create' => false]]
    ]);
    $this->activeSector = Sector::factory()->create(['status' => 'active']);
    $this->inactiveSector = Sector::factory()->create(['status' => 'inactive']);
  }

  public function test_admin_can_authorize_store_request()
  {
    Auth::login($this->admin);

    $request = new StoreMachineRequest();
    $request->setUserResolver(fn() => $this->admin);

    $this->assertTrue($request->authorize());
  }

  public function test_user_with_create_permission_can_authorize_store_request()
  {
    Auth::login($this->userWithPermission);

    $request = new StoreMachineRequest();
    $request->setUserResolver(fn() => $this->userWithPermission);

    $this->assertTrue($request->authorize());
  }

  public function test_user_without_create_permission_cannot_authorize_store_request()
  {
    Auth::login($this->userWithoutPermission);

    $request = new StoreMachineRequest();
    $request->setUserResolver(fn() => $this->userWithoutPermission);

    $this->assertFalse($request->authorize());
  }

  public function test_validation_rules_require_sector_id()
  {
    Auth::login($this->admin);

    $request = new StoreMachineRequest();
    $rules = $request->rules();

    $this->assertContains('required', $rules['sector_id']);
    $this->assertContains('exists:sectors,id', $rules['sector_id']);
  }

  public function test_validation_rules_require_active_sector()
  {
    Auth::login($this->admin);

    $request = new StoreMachineRequest();
    $rules = $request->rules();

    $this->assertStringContainsString('where(\'status\', \'active\')', $rules['sector_id'][1]);
  }

  public function test_validation_rules_require_name()
  {
    Auth::login($this->admin);

    $request = new StoreMachineRequest();
    $rules = $request->rules();

    $this->assertContains('required', $rules['name']);
    $this->assertContains('string', $rules['name']);
    $this->assertContains('min:2', $rules['name']);
    $this->assertContains('max:255', $rules['name']);
  }

  public function test_validation_rules_require_unique_name_per_sector()
  {
    Auth::login($this->admin);

    $request = new StoreMachineRequest();
    $rules = $request->rules();

    $this->assertStringContainsString('unique:machines', $rules['name'][4]);
    $this->assertStringContainsString('where(\'sector_id\'', $rules['name'][4]);
  }

  public function test_validation_rules_require_status()
  {
    Auth::login($this->admin);

    $request = new StoreMachineRequest();
    $rules = $request->rules();

    $this->assertContains('required', $rules['status']);
    $this->assertContains('in:active,inactive', $rules['status']);
  }

  public function test_validation_passes_with_valid_data()
  {
    Auth::login($this->admin);

    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => 'Máquina de Teste',
      'status' => 'active',
    ];

    $request = new StoreMachineRequest();
    $request->merge($data);
    $request->setUserResolver(fn() => $this->admin);

    $validator = validator($data, $request->rules());
    $this->assertFalse($validator->fails());
  }

  public function test_validation_fails_without_sector_id()
  {
    Auth::login($this->admin);

    $data = [
      'name' => 'Máquina de Teste',
      'status' => 'active',
    ];

    $request = new StoreMachineRequest();
    $request->merge($data);
    $request->setUserResolver(fn() => $this->admin);

    $validator = validator($data, $request->rules());
    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('sector_id', $validator->errors()->toArray());
  }

  public function test_validation_fails_with_invalid_sector_id()
  {
    Auth::login($this->admin);

    $data = [
      'sector_id' => 99999,
      'name' => 'Máquina de Teste',
      'status' => 'active',
    ];

    $request = new StoreMachineRequest();
    $request->merge($data);
    $request->setUserResolver(fn() => $this->admin);

    $validator = validator($data, $request->rules());
    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('sector_id', $validator->errors()->toArray());
  }

  public function test_validation_fails_with_inactive_sector()
  {
    Auth::login($this->admin);

    $data = [
      'sector_id' => $this->inactiveSector->id,
      'name' => 'Máquina de Teste',
      'status' => 'active',
    ];

    $request = new StoreMachineRequest();
    $request->merge($data);
    $request->setUserResolver(fn() => $this->admin);

    $validator = validator($data, $request->rules());
    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('sector_id', $validator->errors()->toArray());
  }

  public function test_validation_fails_without_name()
  {
    Auth::login($this->admin);

    $data = [
      'sector_id' => $this->activeSector->id,
      'status' => 'active',
    ];

    $request = new StoreMachineRequest();
    $request->merge($data);
    $request->setUserResolver(fn() => $this->admin);

    $validator = validator($data, $request->rules());
    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('name', $validator->errors()->toArray());
  }

  public function test_validation_fails_with_empty_name()
  {
    Auth::login($this->admin);

    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => '',
      'status' => 'active',
    ];

    $request = new StoreMachineRequest();
    $request->merge($data);
    $request->setUserResolver(fn() => $this->admin);

    $validator = validator($data, $request->rules());
    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('name', $validator->errors()->toArray());
  }

  public function test_validation_fails_with_name_too_short()
  {
    Auth::login($this->admin);

    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => 'A',
      'status' => 'active',
    ];

    $request = new StoreMachineRequest();
    $request->merge($data);
    $request->setUserResolver(fn() => $this->admin);

    $validator = validator($data, $request->rules());
    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('name', $validator->errors()->toArray());
  }

  public function test_validation_fails_with_name_too_long()
  {
    Auth::login($this->admin);

    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => str_repeat('a', 256),
      'status' => 'active',
    ];

    $request = new StoreMachineRequest();
    $request->merge($data);
    $request->setUserResolver(fn() => $this->admin);

    $validator = validator($data, $request->rules());
    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('name', $validator->errors()->toArray());
  }

  public function test_validation_fails_with_duplicate_name_in_same_sector()
  {
    Machine::factory()->create([
      'sector_id' => $this->activeSector->id,
      'name' => 'Máquina Existente',
    ]);

    Auth::login($this->admin);

    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => 'Máquina Existente',
      'status' => 'active',
    ];

    $request = new StoreMachineRequest();
    $request->merge($data);
    $request->setUserResolver(fn() => $this->admin);

    $validator = validator($data, $request->rules());
    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('name', $validator->errors()->toArray());
  }

  public function test_validation_allows_same_name_in_different_sector()
  {
    $otherSector = Sector::factory()->create(['status' => 'active']);
    Machine::factory()->create([
      'sector_id' => $otherSector->id,
      'name' => 'Máquina Existente',
    ]);

    Auth::login($this->admin);

    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => 'Máquina Existente',
      'status' => 'active',
    ];

    $request = new StoreMachineRequest();
    $request->merge($data);
    $request->setUserResolver(fn() => $this->admin);

    $validator = validator($data, $request->rules());
    $this->assertFalse($validator->fails());
  }

  public function test_validation_fails_without_status()
  {
    Auth::login($this->admin);

    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => 'Máquina de Teste',
    ];

    $request = new StoreMachineRequest();
    $request->merge($data);
    $request->setUserResolver(fn() => $this->admin);

    $validator = validator($data, $request->rules());
    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('status', $validator->errors()->toArray());
  }

  public function test_validation_fails_with_invalid_status()
  {
    Auth::login($this->admin);

    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => 'Máquina de Teste',
      'status' => 'invalid',
    ];

    $request = new StoreMachineRequest();
    $request->merge($data);
    $request->setUserResolver(fn() => $this->admin);

    $validator = validator($data, $request->rules());
    $this->assertTrue($validator->fails());
    $this->assertArrayHasKey('status', $validator->errors()->toArray());
  }

  public function test_validation_passes_with_inactive_status()
  {
    Auth::login($this->admin);

    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => 'Máquina de Teste',
      'status' => 'inactive',
    ];

    $request = new StoreMachineRequest();
    $request->merge($data);
    $request->setUserResolver(fn() => $this->admin);

    $validator = validator($data, $request->rules());
    $this->assertFalse($validator->fails());
  }

  public function test_custom_attributes_are_correct()
  {
    Auth::login($this->admin);

    $request = new StoreMachineRequest();
    $attributes = $request->attributes();

    $this->assertEquals('setor', $attributes['sector_id']);
    $this->assertEquals('nome', $attributes['name']);
    $this->assertEquals('status', $attributes['status']);
  }

  public function test_custom_messages_are_correct()
  {
    Auth::login($this->admin);

    $request = new StoreMachineRequest();
    $messages = $request->messages();

    $this->assertEquals('O setor selecionado não existe ou não está ativo.', $messages['sector_id.exists']);
    $this->assertEquals('Já existe uma máquina com este nome neste setor.', $messages['name.unique']);
  }
}
