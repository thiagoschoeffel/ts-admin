<?php

namespace Tests\Feature;

use App\Models\RawMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class RawMaterialControllerTest extends TestCase
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

  public function test_index_displays_raw_materials_list()
  {
    RawMaterial::factory()->create(['name' => 'Produção']);
    RawMaterial::factory()->create(['name' => 'Manutenção']);
    RawMaterial::factory()->create(['name' => 'Qualidade']);

    $response = $this->get(route('raw-materials.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/RawMaterials/Index')
        ->has('raw-materials')
        ->has('filters')
    );
  }

  public function test_index_filters_by_search()
  {
    RawMaterial::factory()->create(['name' => 'Produção']);
    RawMaterial::factory()->create(['name' => 'Manutenção']);

    $response = $this->get(route('raw-materials.index', ['search' => 'Produção']));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/RawMaterials/Index')
        ->where('filters.search', 'Produção')
    );
  }

  public function test_index_filters_by_status()
  {
    RawMaterial::factory()->create(['name' => 'Produção', 'status' => 'active']);
    RawMaterial::factory()->create(['name' => 'Manutenção', 'status' => 'inactive']);

    $response = $this->get(route('raw-materials.index', ['status' => 'inactive']));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/RawMaterials/Index')
        ->where('filters.status', 'inactive')
    );
  }

  public function test_create_displays_form()
  {
    $response = $this->get(route('raw-materials.create'));
    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/RawMaterials/Create')
    );
  }

  public function test_store_creates_rawMaterial()
  {
    $this->get(route('raw-materials.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Qualidade',
      'status' => 'active',
    ];

    $response = $this->from(route('raw-materials.create'))->withHeaders(['X-Inertia' => true])->post(route('raw-materials.store'), $payload);

    $response->assertRedirect(route('raw-materials.index'));
    $response->assertSessionHas('status', 'Setor criado com sucesso!');
    $this->assertDatabaseHas('raw-materials', [
      'name' => 'Qualidade',
      'status' => 'active',
    ]);
  }

  public function test_store_validates_unique_name()
  {
    RawMaterial::factory()->create(['name' => 'Produção']);

    $this->get(route('raw-materials.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Produção',
      'status' => 'active',
    ];

    $response = $this->from(route('raw-materials.create'))->withHeaders(['X-Inertia' => true])->post(route('raw-materials.store'), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_edit_displays_form()
  {
    $rawMaterial = RawMaterial::factory()->create();

    $response = $this->get(route('raw-materials.edit', $rawMaterial));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/RawMaterials/Edit')
        ->where('rawMaterial.id', $rawMaterial->id)
    );
  }

  public function test_update_modifies_rawMaterial()
  {
    $rawMaterial = RawMaterial::factory()->create(['name' => 'Old Name', 'status' => 'active']);

    $this->get(route('raw-materials.edit', $rawMaterial));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'New Name',
      'status' => 'inactive',
    ];

    $response = $this->from(route('raw-materials.edit', $rawMaterial))->withHeaders(['X-Inertia' => true])->patch(route('raw-materials.update', $rawMaterial), $payload);

    $response->assertRedirect(route('raw-materials.index'));
    $response->assertSessionHas('status', 'Setor atualizado com sucesso!');
    $this->assertDatabaseHas('raw-materials', [
      'id' => $rawMaterial->id,
      'name' => 'New Name',
      'status' => 'inactive',
    ]);
  }

  public function test_update_validates_unique_name()
  {
    $rawMaterial1 = RawMaterial::factory()->create(['name' => 'Produção']);
    $rawMaterial2 = RawMaterial::factory()->create(['name' => 'Manutenção']);

    $this->get(route('raw-materials.edit', $rawMaterial2));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Produção',
      'status' => 'active',
    ];

    $response = $this->from(route('raw-materials.edit', $rawMaterial2))->withHeaders(['X-Inertia' => true])->patch(route('raw-materials.update', $rawMaterial2), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_destroy_deletes_rawMaterial()
  {
    $rawMaterial = RawMaterial::factory()->create();

    $this->get(route('raw-materials.index'));
    $token = csrf_token();

    $response = $this->withHeaders(['X-Inertia' => true])->delete(route('raw-materials.destroy', $rawMaterial), ['_token' => $token]);

    $response->assertRedirect(route('raw-materials.index'));
    $response->assertSessionHas('status', 'Setor removido com sucesso!');
    $this->assertDatabaseMissing('raw-materials', ['id' => $rawMaterial->id]);
  }

  public function test_modal_returns_rawMaterial_details()
  {
    $rawMaterial = RawMaterial::factory()->create();

    $response = $this->get(route('raw-materials.modal', $rawMaterial));

    $response->assertStatus(200);
    $response->assertJson([
      'rawMaterial' => [
        'id' => $rawMaterial->id,
        'name' => $rawMaterial->name,
        'status' => $rawMaterial->status,
        'created_at' => $rawMaterial->created_at->format('d/m/Y H:i'),
        'updated_at' => $rawMaterial->updated_at->format('d/m/Y H:i'),
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

    $response = $this->get(route('raw-materials.index'));

    $response->assertStatus(403);
  }

  public function test_index_pagination_works()
  {
    RawMaterial::factory()->count(25)->create();

    $response = $this->get(route('raw-materials.index', ['per_page' => 10]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/RawMaterials/Index')
        ->has('raw-materials')
        ->where('raw-materials.per_page', 10)
    );
  }

  public function test_index_pagination_defaults_to_10()
  {
    RawMaterial::factory()->count(15)->create();

    $response = $this->get(route('raw-materials.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/RawMaterials/Index')
        ->has('raw-materials')
        ->where('raw-materials.per_page', 10)
    );
  }

  public function test_index_pagination_respects_allowed_values()
  {
    RawMaterial::factory()->count(30)->create();

    $response = $this->get(route('raw-materials.index', ['per_page' => 50]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/RawMaterials/Index')
        ->has('raw-materials')
        ->where('raw-materials.per_page', 50)
    );
  }

  public function test_index_pagination_ignores_invalid_values()
  {
    RawMaterial::factory()->count(15)->create();

    $response = $this->get(route('raw-materials.index', ['per_page' => 99]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/RawMaterials/Index')
        ->has('raw-materials')
        ->where('raw-materials.per_page', 10) // Should default to 10
    );
  }

  public function test_index_handles_out_of_range_page()
  {
    RawMaterial::factory()->count(5)->create();

    $response = $this->get(route('raw-materials.index', ['page' => 10]));

    $response->assertRedirect(route('raw-materials.index', ['page' => 1]));
  }

  public function test_index_orders_by_name_ascending()
  {
    $rawMaterialC = RawMaterial::factory()->create(['name' => 'Setor C']);
    $rawMaterialA = RawMaterial::factory()->create(['name' => 'Setor A']);
    $rawMaterialB = RawMaterial::factory()->create(['name' => 'Setor B']);

    $response = $this->get(route('raw-materials.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/RawMaterials/Index')
        ->has('raw-materials.data', 3)
        ->where('raw-materials.data.0.name', 'Setor A')
        ->where('raw-materials.data.1.name', 'Setor B')
        ->where('raw-materials.data.2.name', 'Setor C')
    );
  }

  public function test_store_sets_created_by_user()
  {
    $this->get(route('raw-materials.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Setor Teste',
      'status' => 'active',
    ];

    $this->from(route('raw-materials.create'))->withHeaders(['X-Inertia' => true])->post(route('raw-materials.store'), $payload);

    $this->assertDatabaseHas('raw-materials', [
      'name' => 'Setor Teste',
      'status' => 'active',
      'created_by' => $this->admin->id,
    ]);
  }

  public function test_store_validates_required_fields()
  {
    $this->get(route('raw-materials.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
    ];

    $response = $this->from(route('raw-materials.create'))->withHeaders(['X-Inertia' => true])->post(route('raw-materials.store'), $payload);

    $response->assertSessionHasErrors(['name', 'status']);
  }

  public function test_store_validates_name_max_length()
  {
    $this->get(route('raw-materials.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => str_repeat('a', 256),
      'status' => 'active',
    ];

    $response = $this->from(route('raw-materials.create'))->withHeaders(['X-Inertia' => true])->post(route('raw-materials.store'), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_store_validates_status_values()
  {
    $this->get(route('raw-materials.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Setor Teste',
      'status' => 'invalid',
    ];

    $response = $this->from(route('raw-materials.create'))->withHeaders(['X-Inertia' => true])->post(route('raw-materials.store'), $payload);

    $response->assertSessionHasErrors('status');
  }

  public function test_update_sets_updated_by_user()
  {
    $rawMaterial = RawMaterial::factory()->create();

    $this->get(route('raw-materials.edit', $rawMaterial));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Nome Atualizado',
      'status' => 'active',
    ];

    $this->from(route('raw-materials.edit', $rawMaterial))->withHeaders(['X-Inertia' => true])->patch(route('raw-materials.update', $rawMaterial), $payload);

    $this->assertDatabaseHas('raw-materials', [
      'id' => $rawMaterial->id,
      'name' => 'Nome Atualizado',
      'updated_by' => $this->admin->id,
    ]);
  }

  public function test_update_validates_required_fields()
  {
    $rawMaterial = RawMaterial::factory()->create();

    $this->get(route('raw-materials.edit', $rawMaterial));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
    ];

    $response = $this->from(route('raw-materials.edit', $rawMaterial))->withHeaders(['X-Inertia' => true])->patch(route('raw-materials.update', $rawMaterial), $payload);

    $response->assertSessionHasErrors(['name', 'status']);
  }

  public function test_update_validates_name_max_length()
  {
    $rawMaterial = RawMaterial::factory()->create();

    $this->get(route('raw-materials.edit', $rawMaterial));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => str_repeat('a', 256),
      'status' => 'active',
    ];

    $response = $this->from(route('raw-materials.edit', $rawMaterial))->withHeaders(['X-Inertia' => true])->patch(route('raw-materials.update', $rawMaterial), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_update_validates_status_values()
  {
    $rawMaterial = RawMaterial::factory()->create();

    $this->get(route('raw-materials.edit', $rawMaterial));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Nome Atualizado',
      'status' => 'invalid',
    ];

    $response = $this->from(route('raw-materials.edit', $rawMaterial))->withHeaders(['X-Inertia' => true])->patch(route('raw-materials.update', $rawMaterial), $payload);

    $response->assertSessionHasErrors('status');
  }

  public function test_update_allows_same_name_for_same_rawMaterial()
  {
    $rawMaterial = RawMaterial::factory()->create(['name' => 'Setor Original']);

    $this->get(route('raw-materials.edit', $rawMaterial));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Setor Original',
      'status' => 'inactive',
    ];

    $response = $this->from(route('raw-materials.edit', $rawMaterial))->withHeaders(['X-Inertia' => true])->patch(route('raw-materials.update', $rawMaterial), $payload);

    $response->assertRedirect(route('raw-materials.index'));
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

    $rawMaterial = RawMaterial::factory()->create();

    $response = $this->delete(route('raw-materials.destroy', $rawMaterial));

    $response->assertStatus(403);
  }

  public function test_modal_includes_relationships()
  {
    $user = User::factory()->create();
    $rawMaterial = RawMaterial::factory()->create([
      'created_by' => $user->id,
      'updated_by' => $user->id,
    ]);

    $response = $this->get(route('raw-materials.modal', $rawMaterial));

    $response->assertStatus(200);
    $response->assertJson([
      'rawMaterial' => [
        'id' => $rawMaterial->id,
        'name' => $rawMaterial->name,
        'status' => $rawMaterial->status,
        'created_at' => $rawMaterial->created_at->format('d/m/Y H:i'),
        'updated_at' => $rawMaterial->updated_at->format('d/m/Y H:i'),
        'created_by' => $user->name,
        'updated_by' => $user->name,
      ],
    ]);
  }

  public function test_modal_handles_null_relationships()
  {
    $rawMaterial = RawMaterial::factory()->create([
      'created_by' => null,
      'updated_by' => null,
    ]);

    $response = $this->get(route('raw-materials.modal', $rawMaterial));

    $response->assertStatus(200);
    $response->assertJson([
      'rawMaterial' => [
        'id' => $rawMaterial->id,
        'name' => $rawMaterial->name,
        'status' => $rawMaterial->status,
        'created_at' => $rawMaterial->created_at->format('d/m/Y H:i'),
        'updated_at' => $rawMaterial->updated_at->format('d/m/Y H:i'),
        'created_by' => null,
        'updated_by' => null,
      ],
    ]);
  }

  public function test_user_with_partial_permissions()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => ['raw-materials' => ['view' => true, 'create' => false]],
      'email_verified_at' => now(),
    ]);
    $this->actingAs($user);

    // Should be able to view
    $response = $this->get(route('raw-materials.index'));
    $response->assertStatus(200);

    // Should not be able to create
    $response = $this->get(route('raw-materials.create'));
    $response->assertStatus(403);
  }
}
