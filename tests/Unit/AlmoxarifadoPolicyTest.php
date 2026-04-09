<?php

namespace Tests\Unit;

use App\Models\Almoxarifado;
use App\Models\User;
use App\Policies\AlmoxarifadoPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlmoxarifadoPolicyTest extends TestCase
{
    use RefreshDatabase;

    private AlmoxarifadoPolicy $policy;
    private Almoxarifado $sector;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new AlmoxarifadoPolicy();
        $this->sector = Almoxarifado::factory()->create();
        $this->adminUser = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_view_any_almoxarifados()
    {
        $this->assertTrue($this->policy->viewAny($this->adminUser));
    }

    public function test_admin_can_view_sector()
    {
        $this->assertTrue($this->policy->view($this->adminUser, $this->sector));
    }

    public function test_admin_can_create_almoxarifados()
    {
        $this->assertTrue($this->policy->create($this->adminUser));
    }

    public function test_admin_can_update_sector()
    {
        $this->assertTrue($this->policy->update($this->adminUser, $this->sector));
    }

    public function test_admin_can_delete_sector()
    {
        $this->assertTrue($this->policy->delete($this->adminUser, $this->sector));
    }

    public function test_user_with_view_permission_can_view_any_almoxarifados()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['almoxarifados' => ['view' => true]]
        ]);

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_user_with_view_permission_can_view_sector()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['almoxarifados' => ['view' => true]]
        ]);

        $this->assertTrue($this->policy->view($user, $this->sector));
    }

    public function test_user_without_view_permission_cannot_view_any_almoxarifados()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['almoxarifados' => ['view' => false]]
        ]);

        $this->assertFalse($this->policy->viewAny($user));
    }

    public function test_user_without_view_permission_cannot_view_sector()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['almoxarifados' => ['view' => false]]
        ]);

        $this->assertFalse($this->policy->view($user, $this->sector));
    }

    public function test_user_with_create_permission_can_create_almoxarifados()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['almoxarifados' => ['create' => true]]
        ]);

        $this->assertTrue($this->policy->create($user));
    }

    public function test_user_without_create_permission_cannot_create_almoxarifados()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['almoxarifados' => ['create' => false]]
        ]);

        $this->assertFalse($this->policy->create($user));
    }

    public function test_user_with_update_permission_can_update_sector()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['almoxarifados' => ['update' => true]]
        ]);

        $this->assertTrue($this->policy->update($user, $this->sector));
    }

    public function test_user_without_update_permission_cannot_update_sector()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['almoxarifados' => ['update' => false]]
        ]);

        $this->assertFalse($this->policy->update($user, $this->sector));
    }

    public function test_user_with_delete_permission_can_delete_sector()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['almoxarifados' => ['delete' => true]]
        ]);

        $this->assertTrue($this->policy->delete($user, $this->sector));
    }

    public function test_user_without_delete_permission_cannot_delete_sector()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['almoxarifados' => ['delete' => false]]
        ]);

        $this->assertFalse($this->policy->delete($user, $this->sector));
    }

    public function test_user_with_null_permissions_defaults_to_false()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => null
        ]);

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $this->sector));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $this->sector));
        $this->assertFalse($this->policy->delete($user, $this->sector));
    }
}
