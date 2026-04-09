<?php

namespace Tests\Unit\Policies;

use App\Models\ReasonType;
use App\Models\User;
use App\Policies\ReasonTypePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReasonTypePolicyTest extends TestCase
{
  use RefreshDatabase;

  private ReasonTypePolicy $policy;
  private User $admin;
  private User $userWithAllPermissions;
  private User $userWithViewPermission;
  private User $userWithCreatePermission;
  private User $userWithUpdatePermission;
  private User $userWithDeletePermission;
  private User $userWithoutPermissions;
  private ReasonType $reasonType;

  protected function setUp(): void
  {
    parent::setUp();

    $this->policy = new ReasonTypePolicy();
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
    $this->userWithViewPermission = User::factory()->create([
      'role' => 'user',
      'permissions' => [
        'reason_types' => [
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
        'reason_types' => [
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
        'reason_types' => [
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
        'reason_types' => [
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
        'reason_types' => [
          'view' => false,
          'create' => false,
          'update' => false,
          'delete' => false,
        ]
      ]
    ]);

    $this->reasonType = ReasonType::factory()->create();
  }

  public function test_admin_can_view_any_reason_types()
  {
    $this->assertTrue($this->policy->viewAny($this->admin));
  }

  public function test_user_with_view_permission_can_view_any_reason_types()
  {
    $this->assertTrue($this->policy->viewAny($this->userWithViewPermission));
  }

  public function test_user_without_view_permission_cannot_view_any_reason_types()
  {
    $this->assertFalse($this->policy->viewAny($this->userWithoutPermissions));
  }

  public function test_admin_can_view_reason_type()
  {
    $this->assertTrue($this->policy->view($this->admin, $this->reasonType));
  }

  public function test_user_with_view_permission_can_view_reason_type()
  {
    $this->assertTrue($this->policy->view($this->userWithViewPermission, $this->reasonType));
  }

  public function test_user_without_view_permission_cannot_view_reason_type()
  {
    $this->assertFalse($this->policy->view($this->userWithoutPermissions, $this->reasonType));
  }

  public function test_admin_can_create_reason_types()
  {
    $this->assertTrue($this->policy->create($this->admin));
  }

  public function test_user_with_create_permission_can_create_reason_types()
  {
    $this->assertTrue($this->policy->create($this->userWithCreatePermission));
  }

  public function test_user_without_create_permission_cannot_create_reason_types()
  {
    $this->assertFalse($this->policy->create($this->userWithoutPermissions));
  }

  public function test_admin_can_update_reason_type()
  {
    $this->assertTrue($this->policy->update($this->admin, $this->reasonType));
  }

  public function test_user_with_update_permission_can_update_reason_type()
  {
    $this->assertTrue($this->policy->update($this->userWithUpdatePermission, $this->reasonType));
  }

  public function test_user_without_update_permission_cannot_update_reason_type()
  {
    $this->assertFalse($this->policy->update($this->userWithoutPermissions, $this->reasonType));
  }

  public function test_admin_can_delete_reason_type()
  {
    $this->assertTrue($this->policy->delete($this->admin, $this->reasonType));
  }

  public function test_user_with_delete_permission_can_delete_reason_type()
  {
    $this->assertTrue($this->policy->delete($this->userWithDeletePermission, $this->reasonType));
  }

  public function test_user_without_delete_permission_cannot_delete_reason_type()
  {
    $this->assertFalse($this->policy->delete($this->userWithoutPermissions, $this->reasonType));
  }

  public function test_no_one_can_restore_reason_type()
  {
    $this->assertFalse($this->policy->restore($this->admin, $this->reasonType));
    $this->assertFalse($this->policy->restore($this->userWithAllPermissions, $this->reasonType));
  }

  public function test_no_one_can_force_delete_reason_type()
  {
    $this->assertFalse($this->policy->forceDelete($this->admin, $this->reasonType));
    $this->assertFalse($this->policy->forceDelete($this->userWithAllPermissions, $this->reasonType));
  }

  public function test_user_with_null_permissions_defaults_to_false()
  {
    $userWithNullPermissions = User::factory()->create([
      'role' => 'user',
      'permissions' => null
    ]);

    $this->assertFalse($this->policy->viewAny($userWithNullPermissions));
    $this->assertFalse($this->policy->view($userWithNullPermissions, $this->reasonType));
    $this->assertFalse($this->policy->create($userWithNullPermissions));
    $this->assertFalse($this->policy->update($userWithNullPermissions, $this->reasonType));
    $this->assertFalse($this->policy->delete($userWithNullPermissions, $this->reasonType));
  }

  public function test_user_with_empty_reason_types_permissions_defaults_to_false()
  {
    $userWithEmptyPermissions = User::factory()->create([
      'role' => 'user',
      'permissions' => ['reason_types' => []]
    ]);

    $this->assertFalse($this->policy->viewAny($userWithEmptyPermissions));
    $this->assertFalse($this->policy->view($userWithEmptyPermissions, $this->reasonType));
    $this->assertFalse($this->policy->create($userWithEmptyPermissions));
    $this->assertFalse($this->policy->update($userWithEmptyPermissions, $this->reasonType));
    $this->assertFalse($this->policy->delete($userWithEmptyPermissions, $this->reasonType));
  }

  public function test_user_with_missing_reason_types_permissions_defaults_to_false()
  {
    $userWithMissingPermissions = User::factory()->create([
      'role' => 'user',
      'permissions' => ['other' => ['view' => true]]
    ]);

    $this->assertFalse($this->policy->viewAny($userWithMissingPermissions));
    $this->assertFalse($this->policy->view($userWithMissingPermissions, $this->reasonType));
    $this->assertFalse($this->policy->create($userWithMissingPermissions));
    $this->assertFalse($this->policy->update($userWithMissingPermissions, $this->reasonType));
    $this->assertFalse($this->policy->delete($userWithMissingPermissions, $this->reasonType));
  }
}
