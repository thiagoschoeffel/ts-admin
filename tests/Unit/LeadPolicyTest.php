<?php

namespace Tests\Unit;

use App\Models\Lead;
use App\Models\User;
use App\Policies\LeadPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadPolicyTest extends TestCase
{
  use RefreshDatabase;

  protected LeadPolicy $policy;
  protected User $admin;
  protected User $userWithPermissions;
  protected User $userWithoutPermissions;
  protected Lead $lead;

  protected function setUp(): void
  {
    parent::setUp();

    $this->policy = new LeadPolicy();

    $this->admin = User::factory()->create([
      'email_verified_at' => now(),
      'role' => 'admin',
    ]);

    $this->userWithPermissions = User::factory()->create([
      'email_verified_at' => now(),
      'permissions' => ['leads' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true]],
    ]);

    $this->userWithoutPermissions = User::factory()->create([
      'email_verified_at' => now(),
      'permissions' => [],
    ]);

    $this->lead = Lead::factory()->create();
  }

  public function test_admin_can_view_any_leads(): void
  {
    $this->assertTrue($this->policy->viewAny($this->admin));
  }

  public function test_admin_can_view_lead(): void
  {
    $this->assertTrue($this->policy->view($this->admin, $this->lead));
  }

  public function test_admin_can_create_leads(): void
  {
    $this->assertTrue($this->policy->create($this->admin));
  }

  public function test_admin_can_update_lead(): void
  {
    $this->assertTrue($this->policy->update($this->admin, $this->lead));
  }

  public function test_admin_can_delete_lead(): void
  {
    $this->assertTrue($this->policy->delete($this->admin, $this->lead));
  }

  public function test_admin_can_restore_lead(): void
  {
    $this->assertTrue($this->policy->restore($this->admin, $this->lead));
  }

  public function test_admin_can_force_delete_lead(): void
  {
    $this->assertTrue($this->policy->forceDelete($this->admin, $this->lead));
  }

  public function test_user_with_view_permission_can_view_any_leads(): void
  {
    $this->assertTrue($this->policy->viewAny($this->userWithPermissions));
  }

  public function test_user_with_view_permission_can_view_lead(): void
  {
    $this->assertTrue($this->policy->view($this->userWithPermissions, $this->lead));
  }

  public function test_user_with_create_permission_can_create_leads(): void
  {
    $this->assertTrue($this->policy->create($this->userWithPermissions));
  }

  public function test_user_with_update_permission_can_update_lead(): void
  {
    $this->assertTrue($this->policy->update($this->userWithPermissions, $this->lead));
  }

  public function test_user_with_delete_permission_can_delete_lead(): void
  {
    $this->assertTrue($this->policy->delete($this->userWithPermissions, $this->lead));
  }

  public function test_user_without_view_permission_cannot_view_any_leads(): void
  {
    $this->assertFalse($this->policy->viewAny($this->userWithoutPermissions));
  }

  public function test_user_without_view_permission_cannot_view_lead(): void
  {
    $this->assertFalse($this->policy->view($this->userWithoutPermissions, $this->lead));
  }

  public function test_user_without_create_permission_cannot_create_leads(): void
  {
    $this->assertFalse($this->policy->create($this->userWithoutPermissions));
  }

  public function test_user_without_update_permission_cannot_update_lead(): void
  {
    $this->assertFalse($this->policy->update($this->userWithoutPermissions, $this->lead));
  }

  public function test_user_without_delete_permission_cannot_delete_lead(): void
  {
    $this->assertFalse($this->policy->delete($this->userWithoutPermissions, $this->lead));
  }

  public function test_user_without_delete_permission_cannot_restore_lead(): void
  {
    $this->assertFalse($this->policy->restore($this->userWithoutPermissions, $this->lead));
  }

  public function test_user_without_delete_permission_cannot_force_delete_lead(): void
  {
    $this->assertFalse($this->policy->forceDelete($this->userWithoutPermissions, $this->lead));
  }
}
