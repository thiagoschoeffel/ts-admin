<?php

namespace Tests\Unit;

use App\Models\Operator;
use App\Models\ProductionPointing;
use App\Models\RawMaterial;
use App\Models\Sector;
use App\Models\Silo;
use App\Models\User;
use App\Policies\ProductionPointingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionPointingPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ProductionPointingPolicy $policy;
    private ProductionPointing $productionPointing;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $sector = Sector::factory()->create(['name' => 'Setor Política']);

        foreach (['Operador Política 1', 'Operador Política 2'] as $name) {
            Operator::factory()->create([
                'sector_id' => $sector->id,
                'name' => $name,
                'status' => 'active',
            ]);
        }

        foreach (['Silo Política 1', 'Silo Política 2'] as $name) {
            Silo::factory()->create(['name' => $name]);
        }

        RawMaterial::factory()->create(['name' => 'Matéria Política']);

        $this->policy = new ProductionPointingPolicy();
        $this->productionPointing = ProductionPointing::factory()->create();
        $this->adminUser = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_view_any_production_pointings(): void
    {
        $this->assertTrue($this->policy->viewAny($this->adminUser));
    }

    public function test_admin_can_view_production_pointing(): void
    {
        $this->assertTrue($this->policy->view($this->adminUser, $this->productionPointing));
    }

    public function test_admin_can_create_production_pointings(): void
    {
        $this->assertTrue($this->policy->create($this->adminUser));
    }

    public function test_admin_can_update_production_pointing(): void
    {
        $this->assertTrue($this->policy->update($this->adminUser, $this->productionPointing));
    }

    public function test_admin_can_delete_production_pointing(): void
    {
        $this->assertTrue($this->policy->delete($this->adminUser, $this->productionPointing));
    }

    public function test_user_with_view_permission_can_view_any(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['production_pointings' => ['view' => true]],
        ]);

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_user_with_view_permission_can_view(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['production_pointings' => ['view' => true]],
        ]);

        $this->assertTrue($this->policy->view($user, $this->productionPointing));
    }

    public function test_user_without_view_permission_cannot_view_any(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['production_pointings' => ['view' => false]],
        ]);

        $this->assertFalse($this->policy->viewAny($user));
    }

    public function test_user_without_view_permission_cannot_view(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['production_pointings' => ['view' => false]],
        ]);

        $this->assertFalse($this->policy->view($user, $this->productionPointing));
    }

    public function test_user_with_create_permission_can_create(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['production_pointings' => ['create' => true]],
        ]);

        $this->assertTrue($this->policy->create($user));
    }

    public function test_user_without_create_permission_cannot_create(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['production_pointings' => ['create' => false]],
        ]);

        $this->assertFalse($this->policy->create($user));
    }

    public function test_user_with_update_permission_can_update(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['production_pointings' => ['update' => true]],
        ]);

        $this->assertTrue($this->policy->update($user, $this->productionPointing));
    }

    public function test_user_without_update_permission_cannot_update(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['production_pointings' => ['update' => false]],
        ]);

        $this->assertFalse($this->policy->update($user, $this->productionPointing));
    }

    public function test_user_with_delete_permission_can_delete(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['production_pointings' => ['delete' => true]],
        ]);

        $this->assertTrue($this->policy->delete($user, $this->productionPointing));
    }

    public function test_user_without_delete_permission_cannot_delete(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['production_pointings' => ['delete' => false]],
        ]);

        $this->assertFalse($this->policy->delete($user, $this->productionPointing));
    }

    public function test_user_with_null_permissions_defaults_to_false(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => null,
        ]);

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $this->productionPointing));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $this->productionPointing));
        $this->assertFalse($this->policy->delete($user, $this->productionPointing));
    }
}
