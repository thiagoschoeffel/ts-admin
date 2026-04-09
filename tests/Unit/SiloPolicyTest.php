<?php

namespace Tests\Unit;

use App\Models\Silo;
use App\Models\User;
use App\Policies\SiloPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiloPolicyTest extends TestCase
{
  use RefreshDatabase;

  private SiloPolicy $policy;
  private Silo $silo;
  private User $adminUser;

  protected function setUp(): void
  {
    parent::setUp();

    $this->policy = new SiloPolicy();
    $this->silo = Silo::factory()->create();
    $this->adminUser = User::factory()->create(['role' => 'admin']);
  }

  public function test_admin_can_view_any_silos()
  {
    $this->assertTrue($this->policy->viewAny($this->adminUser));
  }

  public function test_admin_can_view_silo()
  {
    $this->assertTrue($this->policy->view($this->adminUser, $this->silo));
  }

  public function test_admin_can_create_silos()
  {
    $this->assertTrue($this->policy->create($this->adminUser));
  }

  public function test_admin_can_update_silo()
  {
    $this->assertTrue($this->policy->update($this->adminUser, $this->silo));
  }

  public function test_admin_can_delete_silo()
  {
    $this->assertTrue($this->policy->delete($this->adminUser, $this->silo));
  }

  public function test_user_with_view_permission_can_view_any_silos()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => ['silos' => ['view' => true]]
    ]);

    $this->assertTrue($this->policy->viewAny($user));
  }

  public function test_user_with_view_permission_can_view_silo()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => ['silos' => ['view' => true]]
    ]);

    $this->assertTrue($this->policy->view($user, $this->silo));
  }

  public function test_user_without_view_permission_cannot_view_any_silos()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => ['silos' => ['view' => false]]
    ]);

    $this->assertFalse($this->policy->viewAny($user));
  }

  public function test_user_without_view_permission_cannot_view_silo()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => ['silos' => ['view' => false]]
    ]);

    $this->assertFalse($this->policy->view($user, $this->silo));
  }

  public function test_user_with_create_permission_can_create_silos()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => ['silos' => ['create' => true]]
    ]);

    $this->assertTrue($this->policy->create($user));
  }

  public function test_user_without_create_permission_cannot_create_silos()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => ['silos' => ['create' => false]]
    ]);

    $this->assertFalse($this->policy->create($user));
  }

  public function test_user_with_update_permission_can_update_silo()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => ['silos' => ['update' => true]]
    ]);

    $this->assertTrue($this->policy->update($user, $this->silo));
  }

  public function test_user_without_update_permission_cannot_update_silo()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => ['silos' => ['update' => false]]
    ]);

    $this->assertFalse($this->policy->update($user, $this->silo));
  }

  public function test_user_with_delete_permission_can_delete_silo()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => ['silos' => ['delete' => true]]
    ]);

    $this->assertTrue($this->policy->delete($user, $this->silo));
  }

  public function test_user_without_delete_permission_cannot_delete_silo()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => ['silos' => ['delete' => false]]
    ]);

    $this->assertFalse($this->policy->delete($user, $this->silo));
  }

  public function test_user_with_null_permissions_defaults_to_false()
  {
    $user = User::factory()->create([
      'role' => 'user',
      'permissions' => null
    ]);

    $this->assertFalse($this->policy->viewAny($user));
    $this->assertFalse($this->policy->view($user, $this->silo));
    $this->assertFalse($this->policy->create($user));
    $this->assertFalse($this->policy->update($user, $this->silo));
    $this->assertFalse($this->policy->delete($user, $this->silo));
  }
}
