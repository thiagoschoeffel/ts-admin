<?php

namespace Tests\Unit\Policies;

use App\Models\Machine;
use App\Models\Sector;
use App\Models\User;
use App\Policies\MachinePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MachinePolicyTest extends TestCase
{
  use RefreshDatabase;

  private MachinePolicy $policy;
  private User $admin;
  private User $userWithAllPermissions;
  private User $userWithViewPermission;
  private User $userWithCreatePermission;
  private User $userWithUpdatePermission;
  private User $userWithDeletePermission;
  private User $userWithoutPermissions;
  private Machine $machine;

  protected function setUp(): void
  {
    parent::setUp();

    $this->policy = new MachinePolicy();
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

    $sector = Sector::factory()->create(['status' => 'active']);
    $this->machine = Machine::factory()->create(['sector_id' => $sector->id]);
  }

  public function test_admin_can_view_any_machines()
  {
    $this->assertTrue($this->policy->viewAny($this->admin));
  }

  public function test_user_with_view_permission_can_view_any_machines()
  {
    $this->assertTrue($this->policy->viewAny($this->userWithViewPermission));
  }

  public function test_user_without_view_permission_cannot_view_any_machines()
  {
    $this->assertFalse($this->policy->viewAny($this->userWithoutPermissions));
  }

  public function test_admin_can_view_machine()
  {
    $this->assertTrue($this->policy->view($this->admin, $this->machine));
  }

  public function test_user_with_view_permission_can_view_machine()
  {
    $this->assertTrue($this->policy->view($this->userWithViewPermission, $this->machine));
  }

  public function test_user_without_view_permission_cannot_view_machine()
  {
    $this->assertFalse($this->policy->view($this->userWithoutPermissions, $this->machine));
  }

  public function test_admin_can_create_machines()
  {
    $this->assertTrue($this->policy->create($this->admin));
  }

  public function test_user_with_create_permission_can_create_machines()
  {
    $this->assertTrue($this->policy->create($this->userWithCreatePermission));
  }

  public function test_user_without_create_permission_cannot_create_machines()
  {
    $this->assertFalse($this->policy->create($this->userWithoutPermissions));
  }

  public function test_admin_can_update_machine()
  {
    $this->assertTrue($this->policy->update($this->admin, $this->machine));
  }

  public function test_user_with_update_permission_can_update_machine()
  {
    $this->assertTrue($this->policy->update($this->userWithUpdatePermission, $this->machine));
  }

  public function test_user_without_update_permission_cannot_update_machine()
  {
    $this->assertFalse($this->policy->update($this->userWithoutPermissions, $this->machine));
  }

  public function test_admin_can_delete_machine()
  {
    $this->assertTrue($this->policy->delete($this->admin, $this->machine));
  }

  public function test_user_with_delete_permission_can_delete_machine()
  {
    $this->assertTrue($this->policy->delete($this->userWithDeletePermission, $this->machine));
  }

  public function test_user_without_delete_permission_cannot_delete_machine()
  {
    $this->assertFalse($this->policy->delete($this->userWithoutPermissions, $this->machine));
  }

  public function test_no_one_can_restore_machine()
  {
    $this->assertFalse($this->policy->restore($this->admin, $this->machine));
    $this->assertFalse($this->policy->restore($this->userWithAllPermissions, $this->machine));
  }

  public function test_no_one_can_force_delete_machine()
  {
    $this->assertFalse($this->policy->forceDelete($this->admin, $this->machine));
    $this->assertFalse($this->policy->forceDelete($this->userWithAllPermissions, $this->machine));
  }

  public function test_user_with_null_permissions_defaults_to_false()
  {
    $userWithNullPermissions = User::factory()->create([
      'role' => 'user',
      'permissions' => null
    ]);

    $this->assertFalse($this->policy->viewAny($userWithNullPermissions));
    $this->assertFalse($this->policy->view($userWithNullPermissions, $this->machine));
    $this->assertFalse($this->policy->create($userWithNullPermissions));
    $this->assertFalse($this->policy->update($userWithNullPermissions, $this->machine));
    $this->assertFalse($this->policy->delete($userWithNullPermissions, $this->machine));
  }

  public function test_user_with_empty_machines_permissions_defaults_to_false()
  {
    $userWithEmptyPermissions = User::factory()->create([
      'role' => 'user',
      'permissions' => ['machines' => []]
    ]);

    $this->assertFalse($this->policy->viewAny($userWithEmptyPermissions));
    $this->assertFalse($this->policy->view($userWithEmptyPermissions, $this->machine));
    $this->assertFalse($this->policy->create($userWithEmptyPermissions));
    $this->assertFalse($this->policy->update($userWithEmptyPermissions, $this->machine));
    $this->assertFalse($this->policy->delete($userWithEmptyPermissions, $this->machine));
  }

  public function test_user_with_missing_machines_permissions_defaults_to_false()
  {
    $userWithMissingPermissions = User::factory()->create([
      'role' => 'user',
      'permissions' => ['other' => ['view' => true]]
    ]);

    $this->assertFalse($this->policy->viewAny($userWithMissingPermissions));
    $this->assertFalse($this->policy->view($userWithMissingPermissions, $this->machine));
    $this->assertFalse($this->policy->create($userWithMissingPermissions));
    $this->assertFalse($this->policy->update($userWithMissingPermissions, $this->machine));
    $this->assertFalse($this->policy->delete($userWithMissingPermissions, $this->machine));
  }
}
