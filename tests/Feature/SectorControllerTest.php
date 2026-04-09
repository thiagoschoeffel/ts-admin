<?php

namespace Tests\Feature;

use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class SectorControllerTest extends TestCase
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

  public function test_index_displays_sectors_list()
  {
    Sector::factory()->create(['name' => 'Produção']);
    Sector::factory()->create(['name' => 'Manutenção']);
    Sector::factory()->create(['name' => 'Qualidade']);

    $response = $this->get(route('sectors.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Sectors/Index')
        ->has('sectors')
        ->has('filters')
    );
  }

  public function test_index_filters_by_search()
  {
    Sector::factory()->create(['name' => 'Produção']);
    Sector::factory()->create(['name' => 'Manutenção']);

    $response = $this->get(route('sectors.index', ['search' => 'Produção']));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Sectors/Index')
        ->where('filters.search', 'Produção')
    );
  }

  public function test_index_filters_by_status()
  {
    Sector::factory()->create(['name' => 'Produção', 'status' => 'active']);
    Sector::factory()->create(['name' => 'Manutenção', 'status' => 'inactive']);

    $response = $this->get(route('sectors.index', ['status' => 'inactive']));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Sectors/Index')
        ->where('filters.status', 'inactive')
    );
  }

  public function test_create_displays_form()
  {
    $response = $this->get(route('sectors.create'));
    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Sectors/Create')
    );
  }

  public function test_store_creates_sector()
  {
    $this->get(route('sectors.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Qualidade',
      'status' => 'active',
    ];

    $response = $this->from(route('sectors.create'))->withHeaders(['X-Inertia' => true])->post(route('sectors.store'), $payload);

    $response->assertRedirect(route('sectors.index'));
    $response->assertSessionHas('status', 'Setor criado com sucesso!');
    $this->assertDatabaseHas('sectors', [
      'name' => 'Qualidade',
      'status' => 'active',
    ]);
  }

  public function test_store_validates_unique_name()
  {
    Sector::factory()->create(['name' => 'Produção']);

    $this->get(route('sectors.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Produção',
      'status' => 'active',
    ];

    $response = $this->from(route('sectors.create'))->withHeaders(['X-Inertia' => true])->post(route('sectors.store'), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_edit_displays_form()
  {
    $sector = Sector::factory()->create();

    $response = $this->get(route('sectors.edit', $sector));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Sectors/Edit')
        ->where('sector.id', $sector->id)
    );
  }

  public function test_update_modifies_sector()
  {
    $sector = Sector::factory()->create(['name' => 'Old Name', 'status' => 'active']);

    $this->get(route('sectors.edit', $sector));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'New Name',
      'status' => 'inactive',
    ];

    $response = $this->from(route('sectors.edit', $sector))->withHeaders(['X-Inertia' => true])->patch(route('sectors.update', $sector), $payload);

    $response->assertRedirect(route('sectors.index'));
    $response->assertSessionHas('status', 'Setor atualizado com sucesso!');
    $this->assertDatabaseHas('sectors', [
      'id' => $sector->id,
      'name' => 'New Name',
      'status' => 'inactive',
    ]);
  }

  public function test_update_validates_unique_name()
  {
    $sector1 = Sector::factory()->create(['name' => 'Produção']);
    $sector2 = Sector::factory()->create(['name' => 'Manutenção']);

    $this->get(route('sectors.edit', $sector2));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Produção',
      'status' => 'active',
    ];

    $response = $this->from(route('sectors.edit', $sector2))->withHeaders(['X-Inertia' => true])->patch(route('sectors.update', $sector2), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_destroy_deletes_sector()
  {
    $sector = Sector::factory()->create();

    $this->get(route('sectors.index'));
    $token = csrf_token();

    $response = $this->withHeaders(['X-Inertia' => true])->delete(route('sectors.destroy', $sector), ['_token' => $token]);

    $response->assertRedirect(route('sectors.index'));
    $response->assertSessionHas('status', 'Setor removido com sucesso!');
    $this->assertDatabaseMissing('sectors', ['id' => $sector->id]);
  }

  public function test_modal_returns_sector_details()
  {
    $sector = Sector::factory()->create();

    $response = $this->get(route('sectors.modal', $sector));

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

    $response = $this->get(route('sectors.index'));

    $response->assertStatus(403);
  }

  public function test_index_pagination_works()
  {
    Sector::factory()->count(25)->create();

    $response = $this->get(route('sectors.index', ['per_page' => 10]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Sectors/Index')
        ->has('sectors')
        ->where('sectors.per_page', 10)
    );
  }

  public function test_index_pagination_defaults_to_10()
  {
    Sector::factory()->count(15)->create();

    $response = $this->get(route('sectors.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Sectors/Index')
        ->has('sectors')
        ->where('sectors.per_page', 10)
    );
  }

  public function test_index_pagination_respects_allowed_values()
  {
    Sector::factory()->count(30)->create();

    $response = $this->get(route('sectors.index', ['per_page' => 50]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Sectors/Index')
        ->has('sectors')
        ->where('sectors.per_page', 50)
    );
  }

  public function test_index_pagination_ignores_invalid_values()
  {
    Sector::factory()->count(15)->create();

    $response = $this->get(route('sectors.index', ['per_page' => 99]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Sectors/Index')
        ->has('sectors')
        ->where('sectors.per_page', 10) // Should default to 10
    );
  }

  public function test_index_handles_out_of_range_page()
  {
    Sector::factory()->count(5)->create();

    $response = $this->get(route('sectors.index', ['page' => 10]));

    $response->assertRedirect(route('sectors.index', ['page' => 1]));
  }

  public function test_index_orders_by_name_ascending()
  {
    $sectorC = Sector::factory()->create(['name' => 'Setor C']);
    $sectorA = Sector::factory()->create(['name' => 'Setor A']);
    $sectorB = Sector::factory()->create(['name' => 'Setor B']);

    $response = $this->get(route('sectors.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Sectors/Index')
        ->has('sectors.data', 3)
        ->where('sectors.data.0.name', 'Setor A')
        ->where('sectors.data.1.name', 'Setor B')
        ->where('sectors.data.2.name', 'Setor C')
    );
  }

  public function test_store_sets_created_by_user()
  {
    $this->get(route('sectors.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Setor Teste',
      'status' => 'active',
    ];

    $this->from(route('sectors.create'))->withHeaders(['X-Inertia' => true])->post(route('sectors.store'), $payload);

    $this->assertDatabaseHas('sectors', [
      'name' => 'Setor Teste',
      'status' => 'active',
      'created_by' => $this->admin->id,
    ]);
  }

  public function test_store_validates_required_fields()
  {
    $this->get(route('sectors.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
    ];

    $response = $this->from(route('sectors.create'))->withHeaders(['X-Inertia' => true])->post(route('sectors.store'), $payload);

    $response->assertSessionHasErrors(['name', 'status']);
  }

  public function test_store_validates_name_max_length()
  {
    $this->get(route('sectors.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => str_repeat('a', 256),
      'status' => 'active',
    ];

    $response = $this->from(route('sectors.create'))->withHeaders(['X-Inertia' => true])->post(route('sectors.store'), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_store_validates_status_values()
  {
    $this->get(route('sectors.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Setor Teste',
      'status' => 'invalid',
    ];

    $response = $this->from(route('sectors.create'))->withHeaders(['X-Inertia' => true])->post(route('sectors.store'), $payload);

    $response->assertSessionHasErrors('status');
  }

  public function test_update_sets_updated_by_user()
  {
    $sector = Sector::factory()->create();

    $this->get(route('sectors.edit', $sector));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Nome Atualizado',
      'status' => 'active',
    ];

    $this->from(route('sectors.edit', $sector))->withHeaders(['X-Inertia' => true])->patch(route('sectors.update', $sector), $payload);

    $this->assertDatabaseHas('sectors', [
      'id' => $sector->id,
      'name' => 'Nome Atualizado',
      'updated_by' => $this->admin->id,
    ]);
  }

  public function test_update_validates_required_fields()
  {
    $sector = Sector::factory()->create();

    $this->get(route('sectors.edit', $sector));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
    ];

    $response = $this->from(route('sectors.edit', $sector))->withHeaders(['X-Inertia' => true])->patch(route('sectors.update', $sector), $payload);

    $response->assertSessionHasErrors(['name', 'status']);
  }

  public function test_update_validates_name_max_length()
  {
    $sector = Sector::factory()->create();

    $this->get(route('sectors.edit', $sector));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => str_repeat('a', 256),
      'status' => 'active',
    ];

    $response = $this->from(route('sectors.edit', $sector))->withHeaders(['X-Inertia' => true])->patch(route('sectors.update', $sector), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_update_validates_status_values()
  {
    $sector = Sector::factory()->create();

    $this->get(route('sectors.edit', $sector));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Nome Atualizado',
      'status' => 'invalid',
    ];

    $response = $this->from(route('sectors.edit', $sector))->withHeaders(['X-Inertia' => true])->patch(route('sectors.update', $sector), $payload);

    $response->assertSessionHasErrors('status');
  }

  public function test_update_allows_same_name_for_same_sector()
  {
    $sector = Sector::factory()->create(['name' => 'Setor Original']);

    $this->get(route('sectors.edit', $sector));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Setor Original',
      'status' => 'inactive',
    ];

    $response = $this->from(route('sectors.edit', $sector))->withHeaders(['X-Inertia' => true])->patch(route('sectors.update', $sector), $payload);

    $response->assertRedirect(route('sectors.index'));
    $response->assertSessionHas('status', 'Setor atualizado com sucesso!');
  }

  public function test_destroy_handles_authorization_exception()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => [],
      'email_verified_at' => now(),
    ]);
    $this->actingAs($user);

    $sector = Sector::factory()->create();

    $response = $this->delete(route('sectors.destroy', $sector));

    $response->assertStatus(403);
  }

  public function test_modal_includes_relationships()
  {
    $user = User::factory()->create();
    $sector = Sector::factory()->create([
      'created_by' => $user->id,
      'updated_by' => $user->id,
    ]);

    $response = $this->get(route('sectors.modal', $sector));

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
    $sector = Sector::factory()->create([
      'created_by' => null,
      'updated_by' => null,
    ]);

    $response = $this->get(route('sectors.modal', $sector));

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
      'permissions' => ['sectors' => ['view' => true, 'create' => false]],
      'email_verified_at' => now(),
    ]);
    $this->actingAs($user);

    // Should be able to view
    $response = $this->get(route('sectors.index'));
    $response->assertStatus(200);

    // Should not be able to create
    $response = $this->get(route('sectors.create'));
    $response->assertStatus(403);
  }
}
