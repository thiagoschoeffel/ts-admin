<?php

namespace Tests\Unit;

use App\Models\Address;
use App\Models\Client;
use App\Models\User;
use App\Policies\AddressPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressPolicyTest extends TestCase
{
    use RefreshDatabase;

    private AddressPolicy $policy;
    private Address $address;
    private Client $client;
    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new AddressPolicy();
        $this->client = Client::factory()->create();
        $this->address = Address::factory()->create(['client_id' => $this->client->id]);

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

    public function test_admin_can_view_any_addresses()
    {
        $this->assertTrue($this->policy->viewAny($this->adminUser));
    }

    public function test_admin_can_view_address()
    {
        $this->assertTrue($this->policy->view($this->adminUser, $this->address));
    }

    public function test_admin_can_create_addresses()
    {
        $this->assertTrue($this->policy->create($this->adminUser));
    }

    public function test_admin_can_update_address()
    {
        $this->assertTrue($this->policy->update($this->adminUser, $this->address));
    }

    public function test_admin_can_delete_address()
    {
        $this->assertTrue($this->policy->delete($this->adminUser, $this->address));
    }

    public function test_admin_can_restore_address()
    {
        $this->assertTrue($this->policy->restore($this->adminUser, $this->address));
    }

    public function test_admin_can_force_delete_address()
    {
        $this->assertTrue($this->policy->forceDelete($this->adminUser, $this->address));
    }

    public function test_user_with_view_permission_can_view_any_addresses()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['view' => true]]
        ]);

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_user_with_view_permission_can_view_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['view' => true]]
        ]);

        $this->assertTrue($this->policy->view($user, $this->address));
    }

    public function test_user_without_view_permission_cannot_view_any_addresses()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['view' => false]]
        ]);

        $this->assertFalse($this->policy->viewAny($user));
    }

    public function test_user_without_view_permission_cannot_view_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['view' => false]]
        ]);

        $this->assertFalse($this->policy->view($user, $this->address));
    }

    public function test_user_with_create_permission_can_create_addresses()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['create' => true]]
        ]);

        $this->assertTrue($this->policy->create($user));
    }

    public function test_user_with_update_permission_can_create_addresses()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['update' => true]]
        ]);

        $this->assertTrue($this->policy->create($user));
    }

    public function test_user_without_create_or_update_permission_cannot_create_addresses()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['create' => false, 'update' => false]]
        ]);

        $this->assertFalse($this->policy->create($user));
    }

    public function test_user_with_update_permission_can_update_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['update' => true]]
        ]);

        $this->assertTrue($this->policy->update($user, $this->address));
    }

    public function test_user_without_update_permission_cannot_update_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['update' => false]]
        ]);

        $this->assertFalse($this->policy->update($user, $this->address));
    }

    public function test_user_with_update_permission_can_delete_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['update' => true]]
        ]);

        $this->assertTrue($this->policy->delete($user, $this->address));
    }

    public function test_user_with_delete_permission_can_delete_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['delete' => true]]
        ]);

        $this->assertTrue($this->policy->delete($user, $this->address));
    }

    public function test_user_without_update_or_delete_permission_cannot_delete_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['update' => false, 'delete' => false]]
        ]);

        $this->assertFalse($this->policy->delete($user, $this->address));
    }

    public function test_user_with_update_permission_can_restore_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['update' => true]]
        ]);

        $this->assertTrue($this->policy->restore($user, $this->address));
    }

    public function test_user_without_update_permission_cannot_restore_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['update' => false]]
        ]);

        $this->assertFalse($this->policy->restore($user, $this->address));
    }

    public function test_user_with_delete_permission_can_force_delete_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['delete' => true]]
        ]);

        $this->assertTrue($this->policy->forceDelete($user, $this->address));
    }

    public function test_user_without_delete_permission_cannot_force_delete_address()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['clients' => ['delete' => false]]
        ]);

        $this->assertFalse($this->policy->forceDelete($user, $this->address));
    }

    public function test_user_with_null_permissions_defaults_to_false()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => null
        ]);

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $this->address));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $this->address));
        $this->assertFalse($this->policy->delete($user, $this->address));
        $this->assertFalse($this->policy->restore($user, $this->address));
        $this->assertFalse($this->policy->forceDelete($user, $this->address));
    }
}
