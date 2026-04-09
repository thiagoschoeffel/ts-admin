<?php

namespace Tests\Feature\Controllers;

use App\Models\Machine;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class MachinesControllerTest extends TestCase
{
  use RefreshDatabase;

  private User $admin;
  private User $userWithAllPermissions;
  private User $userWithViewPermission;
  private User $userWithCreatePermission;
  private User $userWithUpdatePermission;
  private User $userWithDeletePermission;
  private User $userWithoutPermissions;
  private Sector $activeSector;
  private Sector $inactiveSector;
  private Machine $machine;

  protected function setUp(): void
  {
    parent::setUp();

    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->userWithAllPermissions = User::factory()->create([
      'role' => 'user',
      'permissions' => [
        'machines' => [
          'view' => true,
          'create' => true,
          'update' => true,
          'delete' => true,
        ]
      ]
    ]);
    $this->userWithViewPermission = User::factory()->create([
      'role' => 'user',
      'permissions' => [
        'machines' => [
          'view' => true,
          'create' => false,
          'update' => false,
          'delete' => false,
        ]
      ]
    ]);
    $this->userWithCreatePermission = User::factory()->create([
      'role' => 'user',
      'permissions' => [
        'machines' => [
          'view' => false,
          'create' => true,
          'update' => false,
          'delete' => false,
        ]
      ]
    ]);
    $this->userWithUpdatePermission = User::factory()->create([
      'role' => 'user',
      'permissions' => [
        'machines' => [
          'view' => false,
          'create' => false,
          'update' => true,
          'delete' => false,
        ]
      ]
    ]);
    $this->userWithDeletePermission = User::factory()->create([
      'role' => 'user',
      'permissions' => [
        'machines' => [
          'view' => false,
          'create' => false,
          'update' => false,
          'delete' => true,
        ]
      ]
    ]);
    $this->userWithoutPermissions = User::factory()->create([
      'role' => 'user',
      'permissions' => [
        'machines' => [
          'view' => false,
          'create' => false,
          'update' => false,
          'delete' => false,
        ]
      ]
    ]);

    $this->activeSector = Sector::factory()->create(['status' => 'active']);
    $this->inactiveSector = Sector::factory()->create(['status' => 'inactive']);
    $this->machine = Machine::factory()->create(['sector_id' => $this->activeSector->id]);
  }

  public function test_admin_can_access_index()
  {
    $response = $this->actingAs($this->admin)->get(route('machines.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Machines/Index')
        ->has('machines')
        ->has('filters')
    );
  }

  public function test_user_with_view_permission_can_access_index()
  {
    $response = $this->actingAs($this->userWithViewPermission)->get(route('machines.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Machines/Index')
        ->has('machines')
        ->has('filters')
    );
  }

  public function test_user_without_view_permission_cannot_access_index()
  {
    $response = $this->actingAs($this->userWithoutPermissions)->get(route('machines.index'));

    $response->assertStatus(403);
  }

  public function test_index_filters_by_search()
  {
    Machine::factory()->create([
      'sector_id' => $this->activeSector->id,
      'name' => 'Máquina Especial',
    ]);
    Machine::factory()->create([
      'sector_id' => $this->activeSector->id,
      'name' => 'Outra Máquina',
    ]);

    $response = $this->actingAs($this->admin)->get(route('machines.index', ['search' => 'Especial']));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Machines/Index')
        ->has('machines.data', 1)
        ->where('machines.data.0.name', 'Máquina Especial')
    );
  }

  public function test_index_filters_by_status()
  {
    Machine::factory()->create([
      'sector_id' => $this->activeSector->id,
      'status' => 'active',
    ]);
    Machine::factory()->create([
      'sector_id' => $this->activeSector->id,
      'status' => 'inactive',
    ]);

    $response = $this->actingAs($this->admin)->get(route('machines.index', ['status' => 'active']));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->has('machines.data')
        ->whereAll([
          'machines.data.0.status' => 'active',
        ])
    );
  }

  public function test_index_filters_by_sector()
  {
    $otherSector = Sector::factory()->create(['status' => 'active']);
    Machine::factory()->create([
      'sector_id' => $otherSector->id,
      'name' => 'Máquina Outro Setor',
    ]);

    $response = $this->actingAs($this->admin)->get(route('machines.index', ['sector_id' => $otherSector->id]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->has('machines.data', 1)
        ->where('machines.data.0.name', 'Máquina Outro Setor')
    );
  }

  public function test_index_pagination_works()
  {
    Machine::factory()->count(15)->create(['sector_id' => $this->activeSector->id]);

    $response = $this->actingAs($this->admin)->get(route('machines.index', ['per_page' => 10]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->has('machines.data', 10)
        ->where('machines.per_page', 10)
    );
  }

  public function test_index_handles_invalid_per_page()
  {
    $response = $this->actingAs($this->admin)->get(route('machines.index', ['per_page' => 999]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->where('machines.per_page', 10)
    );
  }

  public function test_admin_can_access_create()
  {
    $response = $this->actingAs($this->admin)->get(route('machines.create'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Machines/Create')
        ->has('sectors')
    );
  }

  public function test_user_with_create_permission_can_access_create()
  {
    $response = $this->actingAs($this->userWithCreatePermission)->get(route('machines.create'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Machines/Create')
        ->has('sectors')
    );
  }

  public function test_user_without_create_permission_cannot_access_create()
  {
    $response = $this->actingAs($this->userWithoutPermissions)->get(route('machines.create'));

    $response->assertStatus(403);
  }

  public function test_admin_can_store_machine()
  {
    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => 'Nova Máquina',
      'status' => 'active',
    ];

    $response = $this->actingAs($this->admin)->post(route('machines.store'), $data);

    $response->assertRedirect(route('machines.index'));
    $response->assertSessionHas('status', 'Máquina criada com sucesso!');
    $this->assertDatabaseHas('machines', [
      'sector_id' => $this->activeSector->id,
      'name' => 'Nova Máquina',
      'status' => 'active',
      'created_by' => $this->admin->id,
    ]);
  }

  public function test_user_with_create_permission_can_store_machine()
  {
    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => 'Nova Máquina',
      'status' => 'active',
    ];

    $response = $this->actingAs($this->userWithCreatePermission)->post(route('machines.store'), $data);

    $response->assertRedirect(route('machines.index'));
    $response->assertSessionHas('status', 'Máquina criada com sucesso!');
    $this->assertDatabaseHas('machines', [
      'sector_id' => $this->activeSector->id,
      'name' => 'Nova Máquina',
      'status' => 'active',
      'created_by' => $this->userWithCreatePermission->id,
    ]);
  }

  public function test_user_without_create_permission_cannot_store_machine()
  {
    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => 'Nova Máquina',
      'status' => 'active',
    ];

    $response = $this->actingAs($this->userWithoutPermissions)->post(route('machines.store'), $data);

    $response->assertStatus(403);
    $this->assertDatabaseMissing('machines', [
      'name' => 'Nova Máquina',
    ]);
  }

  public function test_store_validates_required_fields()
  {
    $response = $this->actingAs($this->admin)->post(route('machines.store'), []);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['sector_id', 'name', 'status']);
  }

  public function test_store_validates_unique_name_per_sector()
  {
    Machine::factory()->create([
      'sector_id' => $this->activeSector->id,
      'name' => 'Máquina Existente',
    ]);

    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => 'Máquina Existente',
      'status' => 'active',
    ];

    $response = $this->actingAs($this->admin)->post(route('machines.store'), $data);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['name']);
  }

  public function test_admin_can_access_edit()
  {
    $response = $this->actingAs($this->admin)->get(route('machines.edit', $this->machine));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Machines/Edit')
        ->has('machine')
        ->has('sectors')
        ->where('machine.id', $this->machine->id)
    );
  }

  public function test_user_with_update_permission_can_access_edit()
  {
    $response = $this->actingAs($this->userWithUpdatePermission)->get(route('machines.edit', $this->machine));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Machines/Edit')
        ->has('machine')
        ->has('sectors')
    );
  }

  public function test_user_without_update_permission_cannot_access_edit()
  {
    $response = $this->actingAs($this->userWithoutPermissions)->get(route('machines.edit', $this->machine));

    $response->assertStatus(403);
  }

  public function test_admin_can_update_machine()
  {
    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => 'Máquina Atualizada',
      'status' => 'inactive',
    ];

    $response = $this->actingAs($this->admin)->put(route('machines.update', $this->machine), $data);

    $response->assertRedirect(route('machines.index'));
    $response->assertSessionHas('status', 'Máquina atualizada com sucesso!');
    $this->assertDatabaseHas('machines', [
      'id' => $this->machine->id,
      'sector_id' => $this->activeSector->id,
      'name' => 'Máquina Atualizada',
      'status' => 'inactive',
      'updated_by' => $this->admin->id,
    ]);
  }

  public function test_user_with_update_permission_can_update_machine()
  {
    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => 'Máquina Atualizada',
      'status' => 'inactive',
    ];

    $response = $this->actingAs($this->userWithUpdatePermission)->put(route('machines.update', $this->machine), $data);

    $response->assertRedirect(route('machines.index'));
    $response->assertSessionHas('status', 'Máquina atualizada com sucesso!');
    $this->assertDatabaseHas('machines', [
      'id' => $this->machine->id,
      'name' => 'Máquina Atualizada',
      'status' => 'inactive',
      'updated_by' => $this->userWithUpdatePermission->id,
    ]);
  }

  public function test_user_without_update_permission_cannot_update_machine()
  {
    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => 'Máquina Atualizada',
      'status' => 'inactive',
    ];

    $response = $this->actingAs($this->userWithoutPermissions)->put(route('machines.update', $this->machine), $data);

    $response->assertStatus(403);
    $this->assertDatabaseMissing('machines', [
      'id' => $this->machine->id,
      'name' => 'Máquina Atualizada',
    ]);
  }

  public function test_update_validates_unique_name_per_sector_ignoring_current_machine()
  {
    $otherMachine = Machine::factory()->create([
      'sector_id' => $this->activeSector->id,
      'name' => 'Outra Máquina',
    ]);

    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => 'Outra Máquina',
      'status' => 'active',
    ];

    $response = $this->actingAs($this->admin)->put(route('machines.update', $this->machine), $data);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['name']);
  }

  public function test_update_allows_same_name_for_current_machine()
  {
    $data = [
      'sector_id' => $this->activeSector->id,
      'name' => $this->machine->name,
      'status' => 'active',
    ];

    $response = $this->actingAs($this->admin)->put(route('machines.update', $this->machine), $data);

    $response->assertRedirect(route('machines.index'));
    $response->assertSessionHas('status', 'Máquina atualizada com sucesso!');
  }

  public function test_admin_can_delete_machine()
  {
    $response = $this->actingAs($this->admin)->delete(route('machines.destroy', $this->machine));

    $response->assertRedirect(route('machines.index'));
    $response->assertSessionHas('status', 'Máquina removida com sucesso!');
    $this->assertSoftDeleted($this->machine);
  }

  public function test_user_with_delete_permission_can_delete_machine()
  {
    $response = $this->actingAs($this->userWithDeletePermission)->delete(route('machines.destroy', $this->machine));

    $response->assertRedirect(route('machines.index'));
    $response->assertSessionHas('status', 'Máquina removida com sucesso!');
    $this->assertSoftDeleted($this->machine);
  }

  public function test_user_without_delete_permission_cannot_delete_machine()
  {
    $response = $this->actingAs($this->userWithoutPermissions)->delete(route('machines.destroy', $this->machine));

    $response->assertStatus(403);
    $this->assertDatabaseHas('machines', ['id' => $this->machine->id]);
  }

  public function test_admin_can_access_modal()
  {
    $response = $this->actingAs($this->admin)->get(route('machines.modal', $this->machine));

    $response->assertStatus(200);
    $response->assertJsonStructure([
      'machine' => [
        'id',
        'sector',
        'name',
        'status',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
      ]
    ]);
  }

  public function test_user_with_view_permission_can_access_modal()
  {
    $response = $this->actingAs($this->userWithViewPermission)->get(route('machines.modal', $this->machine));

    $response->assertStatus(200);
    $response->assertJsonStructure([
      'machine' => [
        'id',
        'sector',
        'name',
        'status',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
      ]
    ]);
  }

  public function test_user_without_view_permission_cannot_access_modal()
  {
    $response = $this->actingAs($this->userWithoutPermissions)->get(route('machines.modal', $this->machine));

    $response->assertStatus(403);
  }

  public function test_modal_returns_correct_data()
  {
    $creator = User::factory()->create(['name' => 'Criador']);
    $updater = User::factory()->create(['name' => 'Atualizador']);

    $machineWithUsers = Machine::factory()->create([
      'sector_id' => $this->activeSector->id,
      'created_by' => $creator->id,
      'updated_by' => $updater->id,
    ]);

    $response = $this->actingAs($this->admin)->get(route('machines.modal', $machineWithUsers));

    $response->assertStatus(200);
    $response->assertJson([
      'machine' => [
        'id' => $machineWithUsers->id,
        'sector' => $this->activeSector->name,
        'name' => $machineWithUsers->name,
        'status' => $machineWithUsers->status,
        'created_at' => $machineWithUsers->created_at?->format('d/m/Y H:i'),
        'updated_at' => $machineWithUsers->updated_at?->format('d/m/Y H:i'),
        'created_by' => 'Criador',
        'updated_by' => 'Atualizador',
      ]
    ]);
  }
}
