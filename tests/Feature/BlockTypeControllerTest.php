<?php

namespace Tests\Feature;

use App\Models\BlockType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class BlockTypeControllerTest extends TestCase
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

  public function test_index_displays_block_types_list()
  {
    BlockType::factory()->create(['name' => 'Produção']);
    BlockType::factory()->create(['name' => 'Manutenção']);
    BlockType::factory()->create(['name' => 'Qualidade']);

    $response = $this->get(route('block-types.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/BlockTypes/Index')
        ->has('blockTypes')
        ->has('filters')
    );
  }

  public function test_index_filters_by_search()
  {
    BlockType::factory()->create(['name' => 'Produção']);
    BlockType::factory()->create(['name' => 'Manutenção']);

    $response = $this->get(route('block-types.index', ['search' => 'Produção']));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/BlockTypes/Index')
        ->where('filters.search', 'Produção')
    );
  }

  public function test_index_filters_by_status()
  {
    BlockType::factory()->create(['name' => 'Produção', 'status' => 'active']);
    BlockType::factory()->create(['name' => 'Manutenção', 'status' => 'inactive']);

    $response = $this->get(route('block-types.index', ['status' => 'inactive']));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/BlockTypes/Index')
        ->where('filters.status', 'inactive')
    );
  }

  public function test_create_displays_form()
  {
    $response = $this->get(route('block-types.create'));
    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/BlockTypes/Create')
    );
  }

  public function test_store_creates_blockType()
  {
    $this->get(route('block-types.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Qualidade',
      'raw_material_percentage' => 85.50,
      'status' => 'active',
    ];

    $response = $this->from(route('block-types.create'))->withHeaders(['X-Inertia' => true])->post(route('block-types.store'), $payload);

    $response->assertRedirect(route('block-types.index'));
    $response->assertSessionHas('status', 'Tipo de bloco criado com sucesso!');
    $this->assertDatabaseHas('block_types', [
      'name' => 'Qualidade',
      'raw_material_percentage' => 85.50,
      'status' => 'active',
    ]);
  }

  public function test_store_validates_unique_name()
  {
    BlockType::factory()->create(['name' => 'Produção']);

    $this->get(route('block-types.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Produção',
      'status' => 'active',
    ];

    $response = $this->from(route('block-types.create'))->withHeaders(['X-Inertia' => true])->post(route('block-types.store'), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_edit_displays_form()
  {
    $blockType = BlockType::factory()->create();

    $response = $this->get(route('block-types.edit', $blockType));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/BlockTypes/Edit')
        ->where('blockType.id', $blockType->id)
    );
  }

  public function test_update_modifies_blockType()
  {
    $blockType = BlockType::factory()->create(['name' => 'Old Name', 'status' => 'active']);

    $this->get(route('block-types.edit', $blockType));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'New Name',
      'raw_material_percentage' => 75.25,
      'status' => 'inactive',
    ];

    $response = $this->from(route('block-types.edit', $blockType))->withHeaders(['X-Inertia' => true])->patch(route('block-types.update', $blockType), $payload);

    $response->assertRedirect(route('block-types.index'));
    $response->assertSessionHas('status', 'Tipo de bloco atualizado com sucesso!');
    $this->assertDatabaseHas('block_types', [
      'id' => $blockType->id,
      'name' => 'New Name',
      'raw_material_percentage' => 75.25,
      'status' => 'inactive',
    ]);
  }

  public function test_update_validates_unique_name()
  {
    $blockType1 = BlockType::factory()->create(['name' => 'Produção']);
    $blockType2 = BlockType::factory()->create(['name' => 'Manutenção']);

    $this->get(route('block-types.edit', $blockType2));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Produção',
      'status' => 'active',
    ];

    $response = $this->from(route('block-types.edit', $blockType2))->withHeaders(['X-Inertia' => true])->patch(route('block-types.update', $blockType2), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_destroy_deletes_blockType()
  {
    $blockType = BlockType::factory()->create();

    $this->get(route('block-types.index'));
    $token = csrf_token();

    $response = $this->withHeaders(['X-Inertia' => true])->delete(route('block-types.destroy', $blockType), ['_token' => $token]);

    $response->assertRedirect(route('block-types.index'));
    $response->assertSessionHas('status', 'Tipo de bloco removido com sucesso!');
    $this->assertDatabaseMissing('block_types', ['id' => $blockType->id]);
  }

  public function test_modal_returns_blockType_details()
  {
    $blockType = BlockType::factory()->create();

    $response = $this->get(route('block-types.modal', $blockType));

    $response->assertStatus(200);
    $response->assertJson([
      'blockType' => [
        'id' => $blockType->id,
        'name' => $blockType->name,
        'status' => $blockType->status,
        'created_at' => $blockType->created_at->format('d/m/Y H:i'),
        'updated_at' => $blockType->updated_at->format('d/m/Y H:i'),
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

    $response = $this->get(route('block-types.index'));

    $response->assertStatus(403);
  }

  public function test_index_pagination_works()
  {
    BlockType::factory()->count(25)->create();

    $response = $this->get(route('block-types.index', ['per_page' => 10]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/BlockTypes/Index')
        ->has('blockTypes')
        ->where('blockTypes.per_page', 10)
    );
  }

  public function test_index_pagination_defaults_to_10()
  {
    BlockType::factory()->count(15)->create();

    $response = $this->get(route('block-types.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/BlockTypes/Index')
        ->has('blockTypes')
        ->where('blockTypes.per_page', 10)
    );
  }

  public function test_index_pagination_respects_allowed_values()
  {
    BlockType::factory()->count(30)->create();

    $response = $this->get(route('block-types.index', ['per_page' => 50]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/BlockTypes/Index')
        ->has('blockTypes')
        ->where('blockTypes.per_page', 50)
    );
  }

  public function test_index_pagination_ignores_invalid_values()
  {
    BlockType::factory()->count(15)->create();

    $response = $this->get(route('block-types.index', ['per_page' => 99]));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/BlockTypes/Index')
        ->has('blockTypes')
        ->where('blockTypes.per_page', 10) // Should default to 10
    );
  }

  public function test_index_handles_out_of_range_page()
  {
    BlockType::factory()->count(5)->create();

    $response = $this->get(route('block-types.index', ['page' => 10]));

    $response->assertRedirect(route('block-types.index', ['page' => 1]));
  }

  public function test_index_orders_by_name_ascending()
  {
    $blockTypeC = BlockType::factory()->create(['name' => 'Setor C']);
    $blockTypeA = BlockType::factory()->create(['name' => 'Setor A']);
    $blockTypeB = BlockType::factory()->create(['name' => 'Setor B']);

    $response = $this->get(route('block-types.index'));

    $response->assertStatus(200);
    $response->assertInertia(
      fn($page) => $page
        ->component('Admin/BlockTypes/Index')
        ->has('blockTypes.data', 3)
        ->where('blockTypes.data.0.name', 'Setor A')
        ->where('blockTypes.data.1.name', 'Setor B')
        ->where('blockTypes.data.2.name', 'Setor C')
    );
  }

  public function test_store_sets_created_by_user()
  {
    $this->get(route('block-types.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Setor Teste',
      'raw_material_percentage' => 90.00,
      'status' => 'active',
    ];

    $this->from(route('block-types.create'))->withHeaders(['X-Inertia' => true])->post(route('block-types.store'), $payload);

    $this->assertDatabaseHas('block_types', [
      'name' => 'Setor Teste',
      'raw_material_percentage' => 90.00,
      'status' => 'active',
    ]);
  }

  public function test_store_validates_required_fields()
  {
    $this->get(route('block-types.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
    ];

    $response = $this->from(route('block-types.create'))->withHeaders(['X-Inertia' => true])->post(route('block-types.store'), $payload);

    $response->assertSessionHasErrors(['name', 'raw_material_percentage', 'status']);
  }

  public function test_store_validates_name_max_length()
  {
    $this->get(route('block-types.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => str_repeat('a', 256),
      'status' => 'active',
    ];

    $response = $this->from(route('block-types.create'))->withHeaders(['X-Inertia' => true])->post(route('block-types.store'), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_store_validates_status_values()
  {
    $this->get(route('block-types.create'));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Setor Teste',
      'status' => 'invalid',
    ];

    $response = $this->from(route('block-types.create'))->withHeaders(['X-Inertia' => true])->post(route('block-types.store'), $payload);

    $response->assertSessionHasErrors('status');
  }

  public function test_update_sets_updated_by_user()
  {
    $blockType = BlockType::factory()->create();

    $this->get(route('block-types.edit', $blockType));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Nome Atualizado',
      'raw_material_percentage' => 80.50,
      'status' => 'active',
    ];

    $this->from(route('block-types.edit', $blockType))->withHeaders(['X-Inertia' => true])->patch(route('block-types.update', $blockType), $payload);

    $blockType->refresh();
    $this->assertEquals('Nome Atualizado', $blockType->name);
    $this->assertEquals(80.50, $blockType->raw_material_percentage);
  }

  public function test_update_validates_required_fields()
  {
    $blockType = BlockType::factory()->create();

    $this->get(route('block-types.edit', $blockType));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
    ];

    $response = $this->from(route('block-types.edit', $blockType))->withHeaders(['X-Inertia' => true])->patch(route('block-types.update', $blockType), $payload);

    $response->assertSessionHasErrors(['name', 'raw_material_percentage', 'status']);
  }

  public function test_update_validates_name_max_length()
  {
    $blockType = BlockType::factory()->create();

    $this->get(route('block-types.edit', $blockType));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => str_repeat('a', 256),
      'status' => 'active',
    ];

    $response = $this->from(route('block-types.edit', $blockType))->withHeaders(['X-Inertia' => true])->patch(route('block-types.update', $blockType), $payload);

    $response->assertSessionHasErrors('name');
  }

  public function test_update_validates_status_values()
  {
    $blockType = BlockType::factory()->create();

    $this->get(route('block-types.edit', $blockType));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Nome Atualizado',
      'status' => 'invalid',
    ];

    $response = $this->from(route('block-types.edit', $blockType))->withHeaders(['X-Inertia' => true])->patch(route('block-types.update', $blockType), $payload);

    $response->assertSessionHasErrors('status');
  }

  public function test_update_allows_same_name_for_same_blockType()
  {
    $blockType = BlockType::factory()->create(['name' => 'Setor Original']);

    $this->get(route('block-types.edit', $blockType));
    $token = csrf_token();

    $payload = [
      '_token' => $token,
      'name' => 'Setor Original',
      'raw_material_percentage' => 70.25,
      'status' => 'inactive',
    ];

    $response = $this->from(route('block-types.edit', $blockType))->withHeaders(['X-Inertia' => true])->patch(route('block-types.update', $blockType), $payload);

    $response->assertRedirect(route('block-types.index'));
    $response->assertSessionHas('status', 'Tipo de bloco atualizado com sucesso!');
  }

  public function test_destroy_handles_authorization_exception()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => [],
      'email_verified_at' => now(),
    ]);
    $this->actingAs($user);

    $blockType = BlockType::factory()->create();

    $response = $this->delete(route('block-types.destroy', $blockType));

    $response->assertStatus(403);
  }

  public function test_modal_includes_relationships()
  {
    $user = User::factory()->create();
    $blockType = BlockType::factory()->create([
      'created_by' => $user->id,
      'updated_by' => $user->id,
    ]);

    $response = $this->get(route('block-types.modal', $blockType));

    $response->assertStatus(200);
    $response->assertJson([
      'blockType' => [
        'id' => $blockType->id,
        'name' => $blockType->name,
        'status' => $blockType->status,
        'created_at' => $blockType->created_at->format('d/m/Y H:i'),
        'updated_at' => $blockType->updated_at->format('d/m/Y H:i'),
        'created_by' => $user->name,
        'updated_by' => $user->name,
      ],
    ]);
  }

  public function test_modal_handles_null_relationships()
  {
    $blockType = BlockType::factory()->create([
      'created_by' => null,
      'updated_by' => null,
    ]);

    $response = $this->get(route('block-types.modal', $blockType));

    $response->assertStatus(200);
    $response->assertJson([
      'blockType' => [
        'id' => $blockType->id,
        'name' => $blockType->name,
        'status' => $blockType->status,
        'created_at' => $blockType->created_at->format('d/m/Y H:i'),
        'updated_at' => $blockType->updated_at->format('d/m/Y H:i'),
        'created_by' => null,
        'updated_by' => null,
      ],
    ]);
  }

  public function test_user_with_partial_permissions()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => ['block_types' => ['view' => true, 'create' => false]],
      'email_verified_at' => now(),
    ]);
    $this->actingAs($user);

    // Should be able to view
    $response = $this->get(route('block-types.index'));
    $response->assertStatus(200);

    // Should not be able to create
    $response = $this->get(route('block-types.create'));
    $response->assertStatus(403);
  }
}
