<?php

namespace Tests\Unit\Http\Controllers;

use App\Models\ReasonType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class ReasonTypesControllerTest extends TestCase
{
  use RefreshDatabase;

  private User $admin;
  private User $userWithAllPermissions;
  private User $userWithoutPermissions;

  protected function setUp(): void
  {
    parent::setUp();

    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->userWithAllPermissions = User::factory()->create([
      'role' => 'user',
      'permissions' => [
        'reason_types' => [
          'view' => true,
          'create' => true,
          'update' => true,
          'delete' => true,
        ]
      ]
    ]);
    $this->userWithoutPermissions = User::factory()->create([
      'role' => 'user',
      'permissions' => [
        'reason_types' => [
          'view' => false,
          'create' => false,
          'update' => false,
          'delete' => false,
        ]
      ]
    ]);
  }

  public function test_index_returns_paginated_reason_types_for_admin()
  {
    ReasonType::factory()->count(15)->create();

    $response = $this->actingAs($this->admin)
      ->getJson('/admin/reason-types');

    $response->assertStatus(Response::HTTP_OK)
      ->assertJsonStructure([
        'data' => [
          '*' => [
            'id',
            'name',
            'status',
            'created_at',
            'updated_at',
            'created_by',
            'updated_by'
          ]
        ],
        'links',
        'meta'
      ])
      ->assertJsonCount(10, 'data'); // Default pagination
  }

  public function test_index_returns_paginated_reason_types_for_user_with_permissions()
  {
    ReasonType::factory()->count(5)->create();

    $response = $this->actingAs($this->userWithAllPermissions)
      ->getJson('/admin/reason-types');

    $response->assertStatus(Response::HTTP_OK)
      ->assertJsonCount(5, 'data');
  }

  public function test_index_denies_access_for_user_without_permissions()
  {
    $response = $this->actingAs($this->userWithoutPermissions)
      ->getJson('/admin/reason-types');

    $response->assertStatus(Response::HTTP_FORBIDDEN);
  }

  public function test_index_filters_by_search_term()
  {
    ReasonType::factory()->create(['name' => 'Qualidade']);
    ReasonType::factory()->create(['name' => 'Manutenção']);
    ReasonType::factory()->create(['name' => 'Setup']);

    $response = $this->actingAs($this->admin)
      ->getJson('/admin/reason-types?search=Qual');

    $response->assertStatus(Response::HTTP_OK)
      ->assertJsonCount(1, 'data')
      ->assertJsonFragment(['name' => 'Qualidade']);
  }

  public function test_index_filters_by_status()
  {
    ReasonType::factory()->create(['status' => 'active']);
    ReasonType::factory()->create(['status' => 'inactive']);

    $response = $this->actingAs($this->admin)
      ->getJson('/admin/reason-types?status=active');

    $response->assertStatus(Response::HTTP_OK)
      ->assertJsonCount(1, 'data')
      ->assertJsonFragment(['status' => 'active']);
  }

  public function test_store_creates_reason_type_for_admin()
  {
    $data = [
      'name' => 'Novo Tipo de Motivo',
      'status' => 'active'
    ];

    $response = $this->actingAs($this->admin)
      ->postJson('/admin/reason-types', $data);

    $response->assertStatus(Response::HTTP_CREATED)
      ->assertJsonFragment($data);

    $this->assertDatabaseHas('reason_types', $data);
  }

  public function test_store_creates_reason_type_for_user_with_create_permission()
  {
    $data = [
      'name' => 'Tipo Criado por Usuário',
      'status' => 'active'
    ];

    $response = $this->actingAs($this->userWithAllPermissions)
      ->postJson('/admin/reason-types', $data);

    $response->assertStatus(Response::HTTP_CREATED)
      ->assertJsonFragment($data);

    $this->assertDatabaseHas('reason_types', $data);
  }

  public function test_store_denies_access_for_user_without_create_permission()
  {
    $data = [
      'name' => 'Tipo Não Autorizado',
      'status' => 'active'
    ];

    $response = $this->actingAs($this->userWithoutPermissions)
      ->postJson('/admin/reason-types', $data);

    $response->assertStatus(Response::HTTP_FORBIDDEN);

    $this->assertDatabaseMissing('reason_types', $data);
  }

  public function test_store_validates_required_fields()
  {
    $response = $this->actingAs($this->admin)
      ->postJson('/admin/reason-types', []);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
      ->assertJsonValidationErrors(['name', 'status']);
  }

  public function test_store_validates_unique_name()
  {
    ReasonType::factory()->create(['name' => 'Tipo Existente']);

    $data = [
      'name' => 'Tipo Existente',
      'status' => 'active'
    ];

    $response = $this->actingAs($this->admin)
      ->postJson('/admin/reason-types', $data);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
      ->assertJsonValidationErrors(['name']);
  }

  public function test_store_validates_status_enum()
  {
    $data = [
      'name' => 'Tipo Inválido',
      'status' => 'invalid'
    ];

    $response = $this->actingAs($this->admin)
      ->postJson('/admin/reason-types', $data);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
      ->assertJsonValidationErrors(['status']);
  }

  public function test_show_returns_reason_type_for_admin()
  {
    $reasonType = ReasonType::factory()->create();

    $response = $this->actingAs($this->admin)
      ->getJson("/admin/reason-types/{$reasonType->id}");

    $response->assertStatus(Response::HTTP_OK)
      ->assertJsonFragment([
        'id' => $reasonType->id,
        'name' => $reasonType->name,
        'status' => $reasonType->status
      ]);
  }

  public function test_show_returns_reason_type_for_user_with_view_permission()
  {
    $reasonType = ReasonType::factory()->create();

    $response = $this->actingAs($this->userWithAllPermissions)
      ->getJson("/admin/reason-types/{$reasonType->id}");

    $response->assertStatus(Response::HTTP_OK)
      ->assertJsonFragment([
        'id' => $reasonType->id,
        'name' => $reasonType->name,
        'status' => $reasonType->status
      ]);
  }

  public function test_show_denies_access_for_user_without_view_permission()
  {
    $reasonType = ReasonType::factory()->create();

    $response = $this->actingAs($this->userWithoutPermissions)
      ->getJson("/admin/reason-types/{$reasonType->id}");

    $response->assertStatus(Response::HTTP_FORBIDDEN);
  }

  public function test_show_returns_404_for_nonexistent_reason_type()
  {
    $response = $this->actingAs($this->admin)
      ->getJson('/admin/reason-types/999');

    $response->assertStatus(Response::HTTP_NOT_FOUND);
  }

  public function test_update_modifies_reason_type_for_admin()
  {
    $reasonType = ReasonType::factory()->create([
      'name' => 'Nome Antigo',
      'status' => 'active'
    ]);

    $data = [
      'name' => 'Nome Atualizado',
      'status' => 'inactive'
    ];

    $response = $this->actingAs($this->admin)
      ->putJson("/admin/reason-types/{$reasonType->id}", $data);

    $response->assertStatus(Response::HTTP_OK)
      ->assertJsonFragment($data);

    $this->assertDatabaseHas('reason_types', array_merge($data, ['id' => $reasonType->id]));
  }

  public function test_update_modifies_reason_type_for_user_with_update_permission()
  {
    $reasonType = ReasonType::factory()->create([
      'name' => 'Nome Antigo',
      'status' => 'active'
    ]);

    $data = [
      'name' => 'Nome Atualizado',
      'status' => 'inactive'
    ];

    $response = $this->actingAs($this->userWithAllPermissions)
      ->putJson("/admin/reason-types/{$reasonType->id}", $data);

    $response->assertStatus(Response::HTTP_OK)
      ->assertJsonFragment($data);

    $this->assertDatabaseHas('reason_types', array_merge($data, ['id' => $reasonType->id]));
  }

  public function test_update_denies_access_for_user_without_update_permission()
  {
    $reasonType = ReasonType::factory()->create([
      'name' => 'Nome Antigo',
      'status' => 'active'
    ]);

    $data = [
      'name' => 'Nome Atualizado',
      'status' => 'inactive'
    ];

    $response = $this->actingAs($this->userWithoutPermissions)
      ->putJson("/admin/reason-types/{$reasonType->id}", $data);

    $response->assertStatus(Response::HTTP_FORBIDDEN);

    $this->assertDatabaseMissing('reason_types', array_merge($data, ['id' => $reasonType->id]));
  }

  public function test_update_validates_unique_name_ignoring_self()
  {
    $reasonType1 = ReasonType::factory()->create(['name' => 'Tipo 1']);
    $reasonType2 = ReasonType::factory()->create(['name' => 'Tipo 2']);

    $data = [
      'name' => 'Tipo 1', // Same as reasonType1
      'status' => 'active'
    ];

    $response = $this->actingAs($this->admin)
      ->putJson("/admin/reason-types/{$reasonType2->id}", $data);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
      ->assertJsonValidationErrors(['name']);
  }

  public function test_update_allows_same_name_for_same_reason_type()
  {
    $reasonType = ReasonType::factory()->create([
      'name' => 'Tipo Original',
      'status' => 'active'
    ]);

    $data = [
      'name' => 'Tipo Original', // Same name
      'status' => 'inactive' // Different status
    ];

    $response = $this->actingAs($this->admin)
      ->putJson("/admin/reason-types/{$reasonType->id}", $data);

    $response->assertStatus(Response::HTTP_OK)
      ->assertJsonFragment($data);
  }

  public function test_destroy_deletes_reason_type_for_admin()
  {
    $reasonType = ReasonType::factory()->create();

    $response = $this->actingAs($this->admin)
      ->deleteJson("/admin/reason-types/{$reasonType->id}");

    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseMissing('reason_types', ['id' => $reasonType->id]);
  }

  public function test_destroy_deletes_reason_type_for_user_with_delete_permission()
  {
    $reasonType = ReasonType::factory()->create();

    $response = $this->actingAs($this->userWithAllPermissions)
      ->deleteJson("/admin/reason-types/{$reasonType->id}");

    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseMissing('reason_types', ['id' => $reasonType->id]);
  }

  public function test_destroy_denies_access_for_user_without_delete_permission()
  {
    $reasonType = ReasonType::factory()->create();

    $response = $this->actingAs($this->userWithoutPermissions)
      ->deleteJson("/admin/reason-types/{$reasonType->id}");

    $response->assertStatus(Response::HTTP_FORBIDDEN);

    $this->assertDatabaseHas('reason_types', ['id' => $reasonType->id]);
  }

  public function test_destroy_returns_404_for_nonexistent_reason_type()
  {
    $response = $this->actingAs($this->admin)
      ->deleteJson('/admin/reason-types/999');

    $response->assertStatus(Response::HTTP_NOT_FOUND);
  }
}
