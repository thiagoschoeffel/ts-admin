<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\User;
use App\Models\Lead;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityControllerTest extends TestCase
{
  use RefreshDatabase;

  protected User $user;
  protected User $userWithoutPermissions;

  protected function setUp(): void
  {
    parent::setUp();

    $this->user = User::factory()->create([
      'email_verified_at' => now(),
      'permissions' => ['opportunities' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true]],
    ]);

    $this->userWithoutPermissions = User::factory()->create([
      'email_verified_at' => now(),
      'permissions' => [], // No permissions
    ]);
  }

  public function test_index_requires_authentication(): void
  {
    $response = $this->get(route('opportunities.index'));

    $response->assertRedirect(route('login'));
  }

  public function test_index_requires_view_permission(): void
  {
    $response = $this->actingAs($this->userWithoutPermissions)->get(route('opportunities.index'));

    $response->assertForbidden();
  }

  public function test_index_returns_opportunities_list(): void
  {
    Opportunity::factory()->count(3)->create();

    $response = $this->actingAs($this->user)->get(route('opportunities.index'));

    $response->assertInertia(function ($page) {
      $page->component('Admin/Opportunities/Index')
        ->has('opportunities.data', 3);
    });
  }

  public function test_create_requires_authentication(): void
  {
    $response = $this->get(route('opportunities.create'));

    $response->assertRedirect(route('login'));
  }

  public function test_create_requires_create_permission(): void
  {
    $response = $this->actingAs($this->userWithoutPermissions)->get(route('opportunities.create'));

    $response->assertForbidden();
  }

  public function test_create_returns_create_form(): void
  {
    $response = $this->actingAs($this->user)->get(route('opportunities.create'));

    $response->assertInertia(function ($page) {
      $page->component('Admin/Opportunities/Create');
    });
  }

  public function test_store_requires_authentication(): void
  {
    $response = $this->post(route('opportunities.store'));

    $response->assertRedirect(route('login'));
  }

  public function test_store_requires_create_permission(): void
  {
    $lead = Lead::factory()->create();
    $client = Client::factory()->create();

    $data = [
      'lead_id' => $lead->id,
      'client_id' => $client->id,
      'title' => 'Test Opportunity',
      'stage' => 'new',
      'probability' => 50,
      'expected_value' => 10000.00,
      'status' => 'active',
      'items' => [],
    ];

    $response = $this->actingAs($this->userWithoutPermissions)->post(route('opportunities.store'), $data);

    $response->assertForbidden();
  }

  public function test_store_creates_opportunity(): void
  {
    $lead = Lead::factory()->create();
    $client = Client::factory()->create();

    $data = [
      'lead_id' => $lead->id,
      'client_id' => $client->id,
      'title' => 'Test Opportunity',
      'stage' => 'new',
      'probability' => 50,
      'expected_value' => 10000.00,
      'status' => 'active',
      'items' => [],
    ];

    $response = $this->actingAs($this->user)->post(route('opportunities.store'), $data);

    $response->assertRedirect(route('opportunities.index'));
    $this->assertDatabaseHas('opportunities', [
      'title' => 'Test Opportunity',
      'lead_id' => $lead->id,
      'client_id' => $client->id,
    ]);
  }
}
