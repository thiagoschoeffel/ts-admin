<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\User;
use App\Models\Order;
use App\Policies\ClientPolicy;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ClientPolicy $policy;
    private Client $client;
    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ClientPolicy();
        $this->client = Client::factory()->create();

        // Create admin user
        $this->adminUser = User::factory()->create(['role' => 'admin']);

        // Create regular user with permissions
        $this->regularUser = User::factory()->create([
            'role' => 'user',
            'permissions' => [
                'clients' => [
                    'view' => true,
                    'create' => true,
                    'update' => true,
                    'delete' => true,
                ]
            ]
        ]);
    }

    public function test_admin_can_view_any_clients()
    {
        $this->assertTrue($this->policy->viewAny($this->adminUser));
    }

    public function test_admin_can_view_client()
    {
        $this->assertTrue($this->policy->view($this->adminUser, $this->client));
    }

    public function test_admin_can_create_clients()
    {
        $this->assertTrue($this->policy->create($this->adminUser));
    }

    public function test_admin_can_update_client()
    {
        $this->assertTrue($this->policy->update($this->adminUser, $this->client));
    }

    public function test_admin_can_delete_client()
    {
        $this->assertTrue($this->policy->delete($this->adminUser, $this->client));
    }

    public function test_admin_cannot_delete_client_with_orders()
    {
        $client = Client::factory()->create();
        Order::factory()->create(['client_id' => $client->id]);

        $response = $this->policy->delete($this->adminUser, $client);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertFalse($response->allowed());
        $this->assertEquals(__('client.delete_blocked_has_orders'), $response->message());
    }

    public function test_admin_can_restore_client()
    {
        $this->assertTrue($this->policy->restore($this->adminUser, $this->client));
    }

    public function test_admin_can_force_delete_client()
    {
        $this->assertTrue($this->policy->forceDelete($this->adminUser, $this->client));
    }

    public function test_admin_cannot_force_delete_client_with_orders()
    {
        $client = Client::factory()->create();
        Order::factory()->create(['client_id' => $client->id]);

        $response = $this->policy->forceDelete($this->adminUser, $client);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertFalse($response->allowed());
        $this->assertEquals(__('client.delete_blocked_has_orders'), $response->message());
    }

    public function test_admin_can_manage_addresses()
    {
        $this->assertTrue($this->policy->manageAddresses($this->adminUser, $this->client));
    }

    public function test_admin_can_create_address()
    {
        $this->assertTrue($this->policy->createAddress($this->adminUser, $this->client));
    }

    public function test_admin_can_update_address()
    {
        $this->assertTrue($this->policy->updateAddress($this->adminUser, $this->client));
    }

    public function test_admin_can_delete_address()
    {
        $this->assertTrue($this->policy->deleteAddress($this->adminUser, $this->client));
    }

    public function test_user_with_view_permission_can_view_any_clients()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['view' => true]]
        ]);

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_user_with_view_permission_can_view_client()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['view' => true]]
        ]);

        $this->assertTrue($this->policy->view($user, $this->client));
    }

    public function test_user_without_view_permission_cannot_view_any_clients()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['view' => false]]
        ]);

        $this->assertFalse($this->policy->viewAny($user));
    }

    public function test_user_without_view_permission_cannot_view_client()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['view' => false]]
        ]);

        $this->assertFalse($this->policy->view($user, $this->client));
    }

    public function test_user_with_create_permission_can_create_clients()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['create' => true]]
        ]);

        $this->assertTrue($this->policy->create($user));
    }

    public function test_user_without_create_permission_cannot_create_clients()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['create' => false]]
        ]);

        $this->assertFalse($this->policy->create($user));
    }

    public function test_user_with_update_permission_can_update_client()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['update' => true]]
        ]);

        $this->assertTrue($this->policy->update($user, $this->client));
    }

    public function test_user_without_update_permission_cannot_update_client()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['update' => false]]
        ]);

        $this->assertFalse($this->policy->update($user, $this->client));
    }

    public function test_user_with_delete_permission_can_delete_client()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['delete' => true]]
        ]);

        $this->assertTrue($this->policy->delete($user, $this->client));
    }

    public function test_user_without_delete_permission_cannot_delete_client()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['delete' => false]]
        ]);

        $this->assertFalse($this->policy->delete($user, $this->client));
    }

    public function test_user_with_update_permission_can_restore_client()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['update' => true]]
        ]);

        $this->assertTrue($this->policy->restore($user, $this->client));
    }

    public function test_user_without_update_permission_cannot_restore_client()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['update' => false]]
        ]);

        $this->assertFalse($this->policy->restore($user, $this->client));
    }

    public function test_user_with_delete_permission_can_force_delete_client()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['delete' => true]]
        ]);

        $this->assertTrue($this->policy->forceDelete($user, $this->client));
    }

    public function test_user_without_delete_permission_cannot_force_delete_client()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['delete' => false]]
        ]);

        $this->assertFalse($this->policy->forceDelete($user, $this->client));
    }

    public function test_user_with_view_permission_can_manage_addresses()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['view' => true]]
        ]);

        $this->assertTrue($this->policy->manageAddresses($user, $this->client));
    }

    public function test_user_without_view_permission_cannot_manage_addresses()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['view' => false]]
        ]);

        $this->assertFalse($this->policy->manageAddresses($user, $this->client));
    }

    public function test_user_with_create_permission_can_create_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['create' => true]]
        ]);

        $this->assertTrue($this->policy->createAddress($user, $this->client));
    }

    public function test_user_with_update_permission_can_create_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['update' => true]]
        ]);

        $this->assertTrue($this->policy->createAddress($user, $this->client));
    }

    public function test_user_without_create_or_update_permission_cannot_create_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['create' => false, 'update' => false]]
        ]);

        $this->assertFalse($this->policy->createAddress($user, $this->client));
    }

    public function test_user_with_update_permission_can_update_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['update' => true]]
        ]);

        $this->assertTrue($this->policy->updateAddress($user, $this->client));
    }

    public function test_user_without_update_permission_cannot_update_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['update' => false]]
        ]);

        $this->assertFalse($this->policy->updateAddress($user, $this->client));
    }

    public function test_user_with_update_permission_can_delete_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['update' => true]]
        ]);

        $this->assertTrue($this->policy->deleteAddress($user, $this->client));
    }

    public function test_user_with_delete_permission_can_delete_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['delete' => true]]
        ]);

        $this->assertTrue($this->policy->deleteAddress($user, $this->client));
    }

    public function test_user_without_update_or_delete_permission_cannot_delete_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['update' => false, 'delete' => false]]
        ]);

        $this->assertFalse($this->policy->deleteAddress($user, $this->client));
    }

    public function test_user_with_null_permissions_defaults_to_false()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => null
        ]);

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $this->client));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $this->client));
        $this->assertFalse($this->policy->delete($user, $this->client));
        $this->assertFalse($this->policy->restore($user, $this->client));
        $this->assertFalse($this->policy->forceDelete($user, $this->client));
        $this->assertFalse($this->policy->manageAddresses($user, $this->client));
        $this->assertFalse($this->policy->createAddress($user, $this->client));
        $this->assertFalse($this->policy->updateAddress($user, $this->client));
        $this->assertFalse($this->policy->deleteAddress($user, $this->client));
    }
}
