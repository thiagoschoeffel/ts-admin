<?php

namespace Tests\Feature;

use App\Models\Silo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class SiloControllerTest extends TestCase
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

  public function test_index_displays_silos_list()
  {
    Silo::factory()->create(['name' => 'Silo 1']);
    Silo::factory()->create(['name' => 'Silo 2']);
    Silo::factory()->create(['name' => 'Silo 3']);

    $response = $this->get(route('silos.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Silos/Index')
        ->has('silos')
        ->has('filters')
    );
  }

  public function test_index_filters_by_search()
  {
    Silo::factory()->create(['name' => 'Silo 1']);
    Silo::factory()->create(['name' => 'Silo 2']);

    $response = $this->get(route('silos.index', ['search' => 'Silo 1']));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Silos/Index')
        ->where('filters.search', 'Silo 1')
    );
  }

  public function test_index_filters_by_status()
  {
    Silo::factory()->create(['name' => 'Silo 1', 'status' => 'active']);
    Silo::factory()->create(['name' => 'Silo 2', 'status' => 'inactive']);

    $response = $this->get(route('silos.index', ['status' => 'inactive']));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Silos/Index')
        ->where('filters.status', 'inactive')
    );
  }

  public function test_create_displays_form()
  {
    $response = $this->get(route('silos.create'));
    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Silos/Create')
    );
  }

  public function test_store_creates_silo()
  {
    $this->get(route('silos.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Silo Teste',
      'status' => 'active',
    ];

    $response = $this->from(route('silos.create'))->withHeaders(['X-Inertia' => true])->post(route('silos.store'), $payload);

    $response->assertRedirect(route('silos.index'));
    $response->assertSessionHas('status', 'Silo criado com sucesso!');
    $this->assertDatabaseHas('silos', [
      'name' => 'Silo Teste',
      'status' => 'active',
    ]);
  }

  public function test_store_validates_unique_name()
  {
    Silo::factory()->create(['name' => 'Silo 1']);

    $this->get(route('silos.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Silo 1',
      'status' => 'active',
    ];

    $response = $this->from(route('silos.create'))->withHeaders(['X-Inertia' => true])->post(route('silos.store'), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_edit_displays_form()
  {
    $silo = Silo::factory()->create();

    $response = $this->get(route('silos.edit', $silo));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Silos/Edit')
        ->where('silo.id', $silo->id)
    );
  }

  public function test_update_modifies_silo()
  {
    $silo = Silo::factory()->create(['name' => 'Old Name', 'status' => 'active']);

    $this->get(route('silos.edit', $silo));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'New Name',
      'status' => 'inactive',
    ];

    $response = $this->from(route('silos.edit', $silo))->withHeaders(['X-Inertia' => true])->patch(route('silos.update', $silo), $payload);

    $response->assertRedirect(route('silos.index'));
    $response->assertSessionHas('status', 'Silo atualizado com sucesso!');
    $this->assertDatabaseHas('silos', [
      'id' => $silo->id,
      'name' => 'New Name',
      'status' => 'inactive',
    ]);
  }

  public function test_update_validates_unique_name()
  {
    $silo1 = Silo::factory()->create(['name' => 'Silo 1']);
    $silo2 = Silo::factory()->create(['name' => 'Silo 2']);

    $this->get(route('silos.edit', $silo2));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Silo 1',
      'status' => 'active',
    ];

    $response = $this->from(route('silos.edit', $silo2))->withHeaders(['X-Inertia' => true])->patch(route('silos.update', $silo2), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_destroy_deletes_silo()
  {
    $silo = Silo::factory()->create();

    $this->get(route('silos.index'));
    $token = csrf_token();

    $response = $this->withHeaders(['X-Inertia' => true])->delete(route('silos.destroy', $silo), ['_token' => $token]);

    $response->assertRedirect(route('silos.index'));
    $response->assertSessionHas('status', 'Silo removido com sucesso!');
    $this->assertDatabaseMissing('silos', ['id' => $silo->id]);
  }

  public function test_modal_returns_silo_details()
  {
    $silo = Silo::factory()->create();

    $response = $this->get(route('silos.modal', $silo));

    $response->assertStatus(200);
    $response->assertJson([
      'silo' => [
        'id' => $silo->id,
        'name' => $silo->name,
        'status' => $silo->status,
        'created_at' => $silo->created_at->format('d/m/Y H:i'),
        'updated_at' => $silo->updated_at->format('d/m/Y H:i'),
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

    $response = $this->get(route('silos.index'));

    $response->assertStatus(403);
  }

  public function test_index_pagination_works()
  {
    Silo::factory()->count(25)->create();

    $response = $this->get(route('silos.index', ['per_page' => 10]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Silos/Index')
        ->has('silos')
        ->where('silos.per_page', 10)
    );
  }

  public function test_index_pagination_defaults_to_10()
  {
    Silo::factory()->count(15)->create();

    $response = $this->get(route('silos.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Silos/Index')
        ->has('silos')
        ->where('silos.per_page', 10)
    );
  }

  public function test_index_pagination_respects_allowed_values()
  {
    Silo::factory()->count(30)->create();

    $response = $this->get(route('silos.index', ['per_page' => 50]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Silos/Index')
        ->has('silos')
        ->where('silos.per_page', 50)
    );
  }

  public function test_index_pagination_ignores_invalid_values()
  {
    Silo::factory()->count(15)->create();

    $response = $this->get(route('silos.index', ['per_page' => 99]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Silos/Index')
        ->has('silos')
        ->where('silos.per_page', 10) // Should default to 10
    );
  }

  public function test_index_handles_out_of_range_page()
  {
    Silo::factory()->count(5)->create();

    $response = $this->get(route('silos.index', ['page' => 10]));

    $response->assertRedirect(route('silos.index', ['page' => 1]));
  }

  public function test_index_orders_by_name_ascending()
  {
    $siloC = Silo::factory()->create(['name' => 'Silo C']);
    $siloA = Silo::factory()->create(['name' => 'Silo A']);
    $siloB = Silo::factory()->create(['name' => 'Silo B']);

    $response = $this->get(route('silos.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/Silos/Index')
        ->has('silos.data', 3)
        ->where('silos.data.0.name', 'Silo A')
        ->where('silos.data.1.name', 'Silo B')
        ->where('silos.data.2.name', 'Silo C')
    );
  }

  public function test_store_sets_created_by_user()
  {
    $this->get(route('silos.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Silo Teste',
      'status' => 'active',
    ];

    $this->from(route('silos.create'))->withHeaders(['X-Inertia' => true])->post(route('silos.store'), $payload);

    $this->assertDatabaseHas('silos', [
      'name' => 'Silo Teste',
      'status' => 'active',
      'created_by' => $this->admin->id,
    ]);
  }

  public function test_store_validates_required_fields()
  {
    $this->get(route('silos.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
    ];

    $response = $this->from(route('silos.create'))->withHeaders(['X-Inertia' => true])->post(route('silos.store'), $payload);

    $response->assertSessionHasErrors(['name', 'status']);
  }

  public function test_store_validates_name_max_length()
  {
    $this->get(route('silos.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => str_repeat('a', 256),
      'status' => 'active',
    ];

    $response = $this->from(route('silos.create'))->withHeaders(['X-Inertia' => true])->post(route('silos.store'), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_store_validates_status_values()
  {
    $this->get(route('silos.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Silo Teste',
      'status' => 'invalid',
    ];

    $response = $this->from(route('silos.create'))->withHeaders(['X-Inertia' => true])->post(route('silos.store'), $payload);

    $response->assertSessionHasErrors('status');
  }

  public function test_update_sets_updated_by_user()
  {
    $silo = Silo::factory()->create();

    $this->get(route('silos.edit', $silo));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Nome Atualizado',
      'status' => 'active',
    ];

    $this->from(route('silos.edit', $silo))->withHeaders(['X-Inertia' => true])->patch(route('silos.update', $silo), $payload);

    $this->assertDatabaseHas('silos', [
      'id' => $silo->id,
      'name' => 'Nome Atualizado',
      'updated_by' => $this->admin->id,
    ]);
  }

  public function test_update_validates_required_fields()
  {
    $silo = Silo::factory()->create();

    $this->get(route('silos.edit', $silo));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
    ];

    $response = $this->from(route('silos.edit', $silo))->withHeaders(['X-Inertia' => true])->patch(route('silos.update', $silo), $payload);

    $response->assertSessionHasErrors(['name', 'status']);
  }

  public function test_update_validates_name_max_length()
  {
    $silo = Silo::factory()->create();

    $this->get(route('silos.edit', $silo));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => str_repeat('a', 256),
      'status' => 'active',
    ];

    $response = $this->from(route('silos.edit', $silo))->withHeaders(['X-Inertia' => true])->patch(route('silos.update', $silo), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_update_validates_status_values()
  {
    $silo = Silo::factory()->create();

    $this->get(route('silos.edit', $silo));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Nome Atualizado',
      'status' => 'invalid',
    ];

    $response = $this->from(route('silos.edit', $silo))->withHeaders(['X-Inertia' => true])->patch(route('silos.update', $silo), $payload);

    $response->assertSessionHasErrors('status');
  }

  public function test_update_allows_same_name_for_same_silo()
  {
    $silo = Silo::factory()->create(['name' => 'Silo Original']);

    $this->get(route('silos.edit', $silo));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Silo Original',
      'status' => 'inactive',
    ];

    $response = $this->from(route('silos.edit', $silo))->withHeaders(['X-Inertia' => true])->patch(route('silos.update', $silo), $payload);

    $response->assertRedirect(route('silos.index'));
    $response->assertSessionHas('status', 'Silo atualizado com sucesso!');
  }

  public function test_destroy_handles_authorization_exception()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => [],
      'email_verified_at' => now(),
    ]);
    $this->actingAs($user);

    $silo = Silo::factory()->create();

    $response = $this->delete(route('silos.destroy', $silo));

    $response->assertStatus(403);
  }

  public function test_modal_includes_relationships()
  {
    $user = User::factory()->create();
    $silo = Silo::factory()->create([
      'created_by' => $user->id,
      'updated_by' => $user->id,
    ]);

    $response = $this->get(route('silos.modal', $silo));

    $response->assertStatus(200);
    $response->assertJson([
      'silo' => [
        'id' => $silo->id,
        'name' => $silo->name,
        'status' => $silo->status,
        'created_at' => $silo->created_at->format('d/m/Y H:i'),
        'updated_at' => $silo->updated_at->format('d/m/Y H:i'),
        'created_by' => $user->name,
        'updated_by' => $user->name,
      ],
    ]);
  }

  public function test_modal_handles_null_relationships()
  {
    $silo = Silo::factory()->create([
      'created_by' => null,
      'updated_by' => null,
    ]);

    $response = $this->get(route('silos.modal', $silo));

    $response->assertStatus(200);
    $response->assertJson([
      'silo' => [
        'id' => $silo->id,
        'name' => $silo->name,
        'status' => $silo->status,
        'created_at' => $silo->created_at->format('d/m/Y H:i'),
        'updated_at' => $silo->updated_at->format('d/m/Y H:i'),
        'created_by' => null,
        'updated_by' => null,
      ],
    ]);
  }

  public function test_user_with_partial_permissions()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => ['silos' => ['view' => true, 'create' => false]],
      'email_verified_at' => now(),
    ]);
    $this->actingAs($user);

    // Should be able to view
    $response = $this->get(route('silos.index'));
    $response->assertStatus(200);

    // Should not be able to create
    $response = $this->get(route('silos.create'));
    $response->assertStatus(403);
  }
}
