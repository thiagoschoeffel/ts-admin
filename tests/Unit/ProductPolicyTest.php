<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use App\Policies\ProductPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ProductPolicy $policy;
    private Product $product;
    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ProductPolicy();
        $this->product = Product::factory()->create();

        // Create admin user
        $this->adminUser = User::factory()->create(['role' => 'admin']);

        // Create regular user with permissions
        $this->regularUser = User::factory()->create([
            'role' => 'user',
            'permissions' => [
                'products' => [
                    'view' => true,
                    'create' => true,
                    'update' => true,
                    'delete' => true,
                ]
            ]
        ]);
    }

    public function test_admin_can_view_any_products()
    {
        $this->assertTrue($this->policy->viewAny($this->adminUser));
    }

    public function test_admin_can_view_product()
    {
        $this->assertTrue($this->policy->view($this->adminUser, $this->product));
    }

    public function test_admin_can_create_products()
    {
        $this->assertTrue($this->policy->create($this->adminUser));
    }

    public function test_admin_can_update_product()
    {
        $this->assertTrue($this->policy->update($this->adminUser, $this->product));
    }

    public function test_admin_can_delete_product()
    {
        $this->assertTrue($this->policy->delete($this->adminUser, $this->product));
    }

    public function test_admin_can_restore_product()
    {
        $this->assertTrue($this->policy->restore($this->adminUser, $this->product));
    }

    public function test_admin_can_force_delete_product()
    {
        $this->assertTrue($this->policy->forceDelete($this->adminUser, $this->product));
    }

    public function test_admin_can_manage_components()
    {
        $this->assertTrue($this->policy->manageComponents($this->adminUser, $this->product));
    }

    public function test_admin_can_create_component()
    {
        $this->assertTrue($this->policy->createComponent($this->adminUser, $this->product));
    }

    public function test_admin_can_update_component()
    {
        $this->assertTrue($this->policy->updateComponent($this->adminUser, $this->product));
    }

    public function test_admin_can_delete_component()
    {
        $this->assertTrue($this->policy->deleteComponent($this->adminUser, $this->product));
    }

    public function test_user_with_view_permission_can_view_any_products()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['view' => true]]
        ]);

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_user_with_view_permission_can_view_product()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['view' => true]]
        ]);

        $this->assertTrue($this->policy->view($user, $this->product));
    }

    public function test_user_without_view_permission_cannot_view_any_products()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['view' => false]]
        ]);

        $this->assertFalse($this->policy->viewAny($user));
    }

    public function test_user_without_view_permission_cannot_view_product()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['view' => false]]
        ]);

        $this->assertFalse($this->policy->view($user, $this->product));
    }

    public function test_user_with_create_permission_can_create_products()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['create' => true]]
        ]);

        $this->assertTrue($this->policy->create($user));
    }

    public function test_user_without_create_permission_cannot_create_products()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['create' => false]]
        ]);

        $this->assertFalse($this->policy->create($user));
    }

    public function test_user_with_update_permission_can_update_product()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['update' => true]]
        ]);

        $this->assertTrue($this->policy->update($user, $this->product));
    }

    public function test_user_without_update_permission_cannot_update_product()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['update' => false]]
        ]);

        $this->assertFalse($this->policy->update($user, $this->product));
    }

    public function test_user_with_delete_permission_can_delete_product()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['delete' => true]]
        ]);

        $this->assertTrue($this->policy->delete($user, $this->product));
    }

    public function test_user_without_delete_permission_cannot_delete_product()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['delete' => false]]
        ]);

        $this->assertFalse($this->policy->delete($user, $this->product));
    }

    public function test_user_with_update_permission_can_restore_product()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['update' => true]]
        ]);

        $this->assertTrue($this->policy->restore($user, $this->product));
    }

    public function test_user_without_update_permission_cannot_restore_product()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['update' => false]]
        ]);

        $this->assertFalse($this->policy->restore($user, $this->product));
    }

    public function test_user_with_delete_permission_can_force_delete_product()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['delete' => true]]
        ]);

        $this->assertTrue($this->policy->forceDelete($user, $this->product));
    }

    public function test_user_without_delete_permission_cannot_force_delete_product()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['delete' => false]]
        ]);

        $this->assertFalse($this->policy->forceDelete($user, $this->product));
    }

    public function test_user_with_view_permission_can_manage_components()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['view' => true]]
        ]);

        $this->assertTrue($this->policy->manageComponents($user, $this->product));
    }

    public function test_user_without_view_permission_cannot_manage_components()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['view' => false]]
        ]);

        $this->assertFalse($this->policy->manageComponents($user, $this->product));
    }

    public function test_user_with_create_permission_can_create_component()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['create' => true]]
        ]);

        $this->assertTrue($this->policy->createComponent($user, $this->product));
    }

    public function test_user_with_update_permission_can_create_component()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['update' => true]]
        ]);

        $this->assertTrue($this->policy->createComponent($user, $this->product));
    }

    public function test_user_without_create_or_update_permission_cannot_create_component()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['create' => false, 'update' => false]]
        ]);

        $this->assertFalse($this->policy->createComponent($user, $this->product));
    }

    public function test_user_with_update_permission_can_update_component()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['update' => true]]
        ]);

        $this->assertTrue($this->policy->updateComponent($user, $this->product));
    }

    public function test_user_without_update_permission_cannot_update_component()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['update' => false]]
        ]);

        $this->assertFalse($this->policy->updateComponent($user, $this->product));
    }

    public function test_user_with_update_permission_can_delete_component()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['update' => true]]
        ]);

        $this->assertTrue($this->policy->deleteComponent($user, $this->product));
    }

    public function test_user_with_delete_permission_can_delete_component()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['delete' => true]]
        ]);

        $this->assertTrue($this->policy->deleteComponent($user, $this->product));
    }

    public function test_user_without_update_or_delete_permission_cannot_delete_component()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['products' => ['update' => false, 'delete' => false]]
        ]);

        $this->assertFalse($this->policy->deleteComponent($user, $this->product));
    }

    public function test_user_with_null_permissions_defaults_to_false()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => null
        ]);

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $this->product));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $this->product));
        $this->assertFalse($this->policy->delete($user, $this->product));
        $this->assertFalse($this->policy->restore($user, $this->product));
        $this->assertFalse($this->policy->forceDelete($user, $this->product));
        $this->assertFalse($this->policy->manageComponents($user, $this->product));
        $this->assertFalse($this->policy->createComponent($user, $this->product));
        $this->assertFalse($this->policy->updateComponent($user, $this->product));
        $this->assertFalse($this->policy->deleteComponent($user, $this->product));
    }

    public function test_admin_cannot_delete_product_with_orders()
    {
        $client = Client::factory()->create();
        $order = Order::factory()->create(['client_id' => $client->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $this->product->id]);

        $result = $this->policy->delete($this->adminUser, $this->product);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($result->denied());
    }

    public function test_admin_cannot_force_delete_product_with_orders()
    {
        $client = Client::factory()->create();
        $order = Order::factory()->create(['client_id' => $client->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $this->product->id]);

        $result = $this->policy->forceDelete($this->adminUser, $this->product);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($result->denied());
    }

    public function test_user_cannot_delete_product_with_orders()
    {
        $client = Client::factory()->create();
        $order = Order::factory()->create(['client_id' => $client->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $this->product->id]);

        $result = $this->policy->delete($this->regularUser, $this->product);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($result->denied());
    }
}
