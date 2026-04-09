<?php

namespace Tests\Feature;

use App\Models\Almoxarifado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class AlmoxarifadoControllerTest extends TestCase
{
  use RefreshDatabase;

  protected User $admin;

  protected function setUp(): void
  {
    parent::setUp();
    $this->admin = User::factory()->create([
      'role' => 'admin',
      'email_verified_at' => now(),
    ]);
    $this->actingAs($this->admin);
    App::setLocale('pt_BR');
    $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
  }

  public function test_index_displays_almoxarifados_list()
  {
    Almoxarifado::factory()->create(['name' => 'Produção']);
    Almoxarifado::factory()->create(['name' => 'Manutenção']);
    Almoxarifado::factory()->create(['name' => 'Qualidade']);

    $response = $this->get(route('almoxarifados.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Almoxarifados/Index')
        ->has('almoxarifados')
        ->has('filters')
    );
  }

  public function test_index_filters_by_search()
  {
    Almoxarifado::factory()->create(['name' => 'Produção']);
    Almoxarifado::factory()->create(['name' => 'Manutenção']);

    $response = $this->get(route('almoxarifados.index', ['search' => 'Produção']));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Almoxarifados/Index')
        ->where('filters.search', 'Produção')
    );
  }

  public function test_index_filters_by_status()
  {
    Almoxarifado::factory()->create(['name' => 'Produção', 'status' => 'active']);
    Almoxarifado::factory()->create(['name' => 'Manutenção', 'status' => 'inactive']);

    $response = $this->get(route('almoxarifados.index', ['status' => 'inactive']));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Almoxarifados/Index')
        ->where('filters.status', 'inactive')
    );
  }

  public function test_create_displays_form()
  {
    $response = $this->get(route('almoxarifados.create'));
    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Almoxarifados/Create')
    );
  }

  public function test_store_creates_sector()
  {
    $this->get(route('almoxarifados.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Qualidade',
      'status' => 'active',
    ];

    $response = $this->from(route('almoxarifados.create'))->withHeaders(['X-Inertia' => true])->post(route('almoxarifados.store'), $payload);

    $response->assertRedirect(route('almoxarifados.index'));
    $response->assertSessionHas('status', 'Almoxarifado criado com sucesso!');
    $this->assertDatabaseHas('almoxarifados', [
      'name' => 'Qualidade',
      'status' => 'active',
    ]);
  }

  public function test_store_validates_unique_name()
  {
    Almoxarifado::factory()->create(['name' => 'Produção']);

    $this->get(route('almoxarifados.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Produção',
      'status' => 'active',
    ];

    $response = $this->from(route('almoxarifados.create'))->withHeaders(['X-Inertia' => true])->post(route('almoxarifados.store'), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_edit_displays_form()
  {
    $sector = Almoxarifado::factory()->create();

    $response = $this->get(route('almoxarifados.edit', $sector));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Almoxarifados/Edit')
        ->where('sector.id', $sector->id)
    );
  }

  public function test_update_modifies_sector()
  {
    $sector = Almoxarifado::factory()->create(['name' => 'Old Name', 'status' => 'active']);

    $this->get(route('almoxarifados.edit', $sector));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'New Name',
      'status' => 'inactive',
    ];

    $response = $this->from(route('almoxarifados.edit', $sector))->withHeaders(['X-Inertia' => true])->patch(route('almoxarifados.update', $sector), $payload);

    $response->assertRedirect(route('almoxarifados.index'));
    $response->assertSessionHas('status', 'Almoxarifado atualizado com sucesso!');
    $this->assertDatabaseHas('almoxarifados', [
      'id' => $sector->id,
      'name' => 'New Name',
      'status' => 'inactive',
    ]);
  }

  public function test_update_validates_unique_name()
  {
    $sector1 = Almoxarifado::factory()->create(['name' => 'Produção']);
    $sector2 = Almoxarifado::factory()->create(['name' => 'Manutenção']);

    $this->get(route('almoxarifados.edit', $sector2));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Produção',
      'status' => 'active',
    ];

    $response = $this->from(route('almoxarifados.edit', $sector2))->withHeaders(['X-Inertia' => true])->patch(route('almoxarifados.update', $sector2), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_destroy_deletes_sector()
  {
    $sector = Almoxarifado::factory()->create();

    $this->get(route('almoxarifados.index'));
    $token = csrf_token();

    $response = $this->withHeaders(['X-Inertia' => true])->delete(route('almoxarifados.destroy', $sector), ['_token' => $token]);

    $response->assertRedirect(route('almoxarifados.index'));
    $response->assertSessionHas('status', 'Almoxarifado removido com sucesso!');
    $this->assertDatabaseMissing('almoxarifados', ['id' => $sector->id]);
  }

  public function test_modal_returns_sector_details()
  {
    $sector = Almoxarifado::factory()->create();

    $response = $this->get(route('almoxarifados.modal', $sector));

    $response->assertStatus(200);
    $response->assertJson([
      'sector' => [
        'id' => $sector->id,
        'name' => $sector->name,
        'status' => $sector->status,
        'created_at' => $sector->created_at->format('d/m/Y H:i'),
        'updated_at' => $sector->updated_at->format('d/m/Y H:i'),
      ],
    ]);
  }

  public function test_denies_access_without_permission()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => [],
      'email_verified_at' => now(),
    ]);
    $this->actingAs($user);

    $response = $this->get(route('almoxarifados.index'));

    $response->assertStatus(403);
  }

  public function test_index_pagination_works()
  {
    Almoxarifado::factory()->count(25)->create();

    $response = $this->get(route('almoxarifados.index', ['per_page' => 10]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Almoxarifados/Index')
        ->has('almoxarifados')
        ->where('almoxarifados.per_page', 10)
    );
  }

  public function test_index_pagination_defaults_to_10()
  {
    Almoxarifado::factory()->count(15)->create();

    $response = $this->get(route('almoxarifados.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Almoxarifados/Index')
        ->has('almoxarifados')
        ->where('almoxarifados.per_page', 10)
    );
  }

  public function test_index_pagination_respects_allowed_values()
  {
    Almoxarifado::factory()->count(30)->create();

    $response = $this->get(route('almoxarifados.index', ['per_page' => 50]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Almoxarifados/Index')
        ->has('almoxarifados')
        ->where('almoxarifados.per_page', 50)
    );
  }

  public function test_index_pagination_ignores_invalid_values()
  {
    Almoxarifado::factory()->count(15)->create();

    $response = $this->get(route('almoxarifados.index', ['per_page' => 99]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Almoxarifados/Index')
        ->has('almoxarifados')
        ->where('almoxarifados.per_page', 10) // Should default to 10
    );
  }

  public function test_index_handles_out_of_range_page()
  {
    Almoxarifado::factory()->count(5)->create();

    $response = $this->get(route('almoxarifados.index', ['page' => 10]));

    $response->assertRedirect(route('almoxarifados.index', ['page' => 1]));
  }

  public function test_index_orders_by_name_ascending()
  {
    $sectorC = Almoxarifado::factory()->create(['name' => 'Almoxarifado C']);
    $sectorA = Almoxarifado::factory()->create(['name' => 'Almoxarifado A']);
    $sectorB = Almoxarifado::factory()->create(['name' => 'Almoxarifado B']);

    $response = $this->get(route('almoxarifados.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Almoxarifados/Index')
        ->has('almoxarifados.data', 3)
        ->where('almoxarifados.data.0.name', 'Almoxarifado A')
        ->where('almoxarifados.data.1.name', 'Almoxarifado B')
        ->where('almoxarifados.data.2.name', 'Almoxarifado C')
    );
  }

  public function test_store_sets_created_by_user()
  {
    $this->get(route('almoxarifados.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Almoxarifado Teste',
      'status' => 'active',
    ];

    $this->from(route('almoxarifados.create'))->withHeaders(['X-Inertia' => true])->post(route('almoxarifados.store'), $payload);

    $this->assertDatabaseHas('almoxarifados', [
      'name' => 'Almoxarifado Teste',
      'status' => 'active',
      'created_by' => $this->admin->id,
    ]);
  }

  public function test_store_validates_required_fields()
  {
    $this->get(route('almoxarifados.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
    ];

    $response = $this->from(route('almoxarifados.create'))->withHeaders(['X-Inertia' => true])->post(route('almoxarifados.store'), $payload);

    $response->assertSessionHasErrors(['name', 'status']);
  }

  public function test_store_validates_name_max_length()
  {
    $this->get(route('almoxarifados.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => str_repeat('a', 256),
      'status' => 'active',
    ];

    $response = $this->from(route('almoxarifados.create'))->withHeaders(['X-Inertia' => true])->post(route('almoxarifados.store'), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_store_validates_status_values()
  {
    $this->get(route('almoxarifados.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Almoxarifado Teste',
      'status' => 'invalid',
    ];

    $response = $this->from(route('almoxarifados.create'))->withHeaders(['X-Inertia' => true])->post(route('almoxarifados.store'), $payload);

    $response->assertSessionHasErrors('status');
  }

  public function test_update_sets_updated_by_user()
  {
    $sector = Almoxarifado::factory()->create();

    $this->get(route('almoxarifados.edit', $sector));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Nome Atualizado',
      'status' => 'active',
    ];

    $this->from(route('almoxarifados.edit', $sector))->withHeaders(['X-Inertia' => true])->patch(route('almoxarifados.update', $sector), $payload);

    $this->assertDatabaseHas('almoxarifados', [
      'id' => $sector->id,
      'name' => 'Nome Atualizado',
      'updated_by' => $this->admin->id,
    ]);
  }

  public function test_update_validates_required_fields()
  {
    $sector = Almoxarifado::factory()->create();

    $this->get(route('almoxarifados.edit', $sector));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
    ];

    $response = $this->from(route('almoxarifados.edit', $sector))->withHeaders(['X-Inertia' => true])->patch(route('almoxarifados.update', $sector), $payload);

    $response->assertSessionHasErrors(['name', 'status']);
  }

  public function test_update_validates_name_max_length()
  {
    $sector = Almoxarifado::factory()->create();

    $this->get(route('almoxarifados.edit', $sector));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => str_repeat('a', 256),
      'status' => 'active',
    ];

    $response = $this->from(route('almoxarifados.edit', $sector))->withHeaders(['X-Inertia' => true])->patch(route('almoxarifados.update', $sector), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_update_validates_status_values()
  {
    $sector = Almoxarifado::factory()->create();

    $this->get(route('almoxarifados.edit', $sector));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Nome Atualizado',
      'status' => 'invalid',
    ];

    $response = $this->from(route('almoxarifados.edit', $sector))->withHeaders(['X-Inertia' => true])->patch(route('almoxarifados.update', $sector), $payload);

    $response->assertSessionHasErrors('status');
  }

  public function test_update_allows_same_name_for_same_sector()
  {
    $sector = Almoxarifado::factory()->create(['name' => 'Almoxarifado Original']);

    $this->get(route('almoxarifados.edit', $sector));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Almoxarifado Original',
      'status' => 'inactive',
    ];

    $response = $this->from(route('almoxarifados.edit', $sector))->withHeaders(['X-Inertia' => true])->patch(route('almoxarifados.update', $sector), $payload);

    $response->assertRedirect(route('almoxarifados.index'));
    $response->assertSessionHas('status', 'Almoxarifado atualizado com sucesso!');
  }

  public function test_destroy_handles_authorization_exception()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => [],
      'email_verified_at' => now(),
    ]);
    $this->actingAs($user);

    $sector = Almoxarifado::factory()->create();

    $response = $this->delete(route('almoxarifados.destroy', $sector));

    $response->assertStatus(403);
  }

  public function test_modal_includes_relationships()
  {
    $user = User::factory()->create();
    $sector = Almoxarifado::factory()->create([
      'created_by' => $user->id,
      'updated_by' => $user->id,
    ]);

    $response = $this->get(route('almoxarifados.modal', $sector));

    $response->assertStatus(200);
    $response->assertJson([
      'sector' => [
        'id' => $sector->id,
        'name' => $sector->name,
        'status' => $sector->status,
        'created_at' => $sector->created_at->format('d/m/Y H:i'),
        'updated_at' => $sector->updated_at->format('d/m/Y H:i'),
        'created_by' => $user->name,
        'updated_by' => $user->name,
      ],
    ]);
  }

  public function test_modal_handles_null_relationships()
  {
    $sector = Almoxarifado::factory()->create([
      'created_by' => null,
      'updated_by' => null,
    ]);

    $response = $this->get(route('almoxarifados.modal', $sector));

    $response->assertStatus(200);
    $response->assertJson([
      'sector' => [
        'id' => $sector->id,
        'name' => $sector->name,
        'status' => $sector->status,
        'created_at' => $sector->created_at->format('d/m/Y H:i'),
        'updated_at' => $sector->updated_at->format('d/m/Y H:i'),
        'created_by' => null,
        'updated_by' => null,
      ],
    ]);
  }

  public function test_user_with_partial_permissions()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => ['almoxarifados' => ['view' => true, 'create' => false]],
      'email_verified_at' => now(),
    ]);
    $this->actingAs($user);

    // Should be able to view
    $response = $this->get(route('almoxarifados.index'));
    $response->assertStatus(200);

    // Should not be able to create
    $response = $this->get(route('almoxarifados.create'));
    $response->assertStatus(403);
  }
}
