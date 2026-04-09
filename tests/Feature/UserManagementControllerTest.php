<?php

namespace Tests\Feature;

use App\Http\Controllers\UserManagementController;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class UserManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;
    protected UserManagementController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->regularUser = User::factory()->create([
            'status' => 'inactive',
            'email_verified_at' => now(),
        ]);

        $this->controller = new UserManagementController();

        $this->actingAs($this->admin);
    }

    public function test_index_displays_users_list()
    {
        User::factory()->count(5)->create();

        $response = $this->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->has('users.data', 7) // 5 + admin + regularUser
                ->has(
                    'users.data.0',
                    fn($user) => $user
                        ->hasAll(['id', 'name', 'email', 'role', 'status', 'email_verified_at'])
                )
        );
    }

    public function test_index_filters_by_search()
    {
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);

        $response = $this->get(route('users.index', ['search' => 'John']));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.name', 'John Doe')
        );
    }

    public function test_index_filters_by_status()
    {
        User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'inactive']);

        $response = $this->get(route('users.index', ['status' => 'active']));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->has('users.data', 2) // admin + active user
                ->where('users.data.0.status', 'active')
                ->where('users.data.1.status', 'active')
        );
    }

    public function test_create_displays_create_form()
    {
        $response = $this->get(route('users.create'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->has('resources')
        );
    }

    public function test_store_creates_new_user()
    {
        $data = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'role' => 'user',
            'modules' => ['clients' => true, 'products' => false],
            'permissions' => [
                'clients' => ['view' => true, 'create' => false],
            ],
        ];

        $response = $this->post(route('users.store'), $data);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('status', 'Usuário criado com sucesso.');

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'status' => 'active',
            'role' => 'user',
        ]);

        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
        $this->assertArrayHasKey('clients', $user->permissions);
    }

    public function test_edit_displays_edit_form()
    {
        $user = User::factory()->create();

        $response = $this->get(route('users.edit', $user));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->has('resources')
                ->has(
                    'user',
                    fn($u) => $u
                        ->where('id', $user->id)
                        ->where('name', $user->name)
                        ->where('email', $user->email)
                        ->where('status', $user->status)
                        ->where('role', $user->role)
                        ->where('permissions', $user->permissions ?? [])
                )
        );
    }

    public function test_update_modifies_user()
    {
        $user = User::factory()->create();

        $data = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'status' => 'inactive',
            'role' => 'admin',
            'modules' => ['clients' => true],
            'permissions' => [
                'clients' => ['view' => true],
            ],
        ];

        $response = $this->patch(route('users.update', $user), $data);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('status', 'Usuário atualizado com sucesso.');

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
        $this->assertEquals('inactive', $user->status);
        $this->assertEquals('admin', $user->role);
    }

    public function test_update_without_password_does_not_change_password()
    {
        $user = User::factory()->create();
        $originalPassword = $user->password;

        $data = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'status' => $user->status,
            'role' => $user->role,
        ];

        $response = $this->patch(route('users.update', $user), $data);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('status', 'Usuário atualizado com sucesso.');

        $user->refresh();
        $this->assertEquals($originalPassword, $user->password);
    }

    public function test_update_with_password_changes_password_and_verifies_email_if_unverified()
    {
        $user = User::factory()->unverified()->create();
        $originalPasswordHash = $user->password;

        $data = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'status' => $user->status,
            'role' => $user->role,
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ];

        $response = $this->patch(route('users.update', $user), $data);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('status', 'Usuário atualizado com sucesso.');

        $user->refresh();
        $this->assertTrue(Hash::check('new-secret-123', $user->password));
        $this->assertNotEquals($originalPasswordHash, $user->password);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_update_sets_admin_permissions_to_all_true()
    {
        $user = User::factory()->create(['role' => 'user']);

        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'role' => 'admin',
            'modules' => [],
            'permissions' => [],
        ];

        $this->patch(route('users.update', $user), $data)->assertRedirect(route('users.index'));

        $user->refresh();
        $resources = config('permissions.resources', []);
        foreach ($resources as $resourceKey => $resource) {
            foreach (array_keys($resource['abilities'] ?? []) as $ability) {
                $this->assertTrue($user->permissions[$resourceKey][$ability] ?? false, "Expected admin to have {$resourceKey}.{$ability}");
            }
        }
    }

    public function test_update_sets_permissions_based_on_modules_for_user_role()
    {
        $user = User::factory()->create(['role' => 'user']);

        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'role' => 'user',
            'modules' => [
                'clients' => true,
                'products' => false,
                'orders' => true,
            ],
            'permissions' => [
                'clients' => [
                    'view' => true,
                    // 'create' omitted to ensure defaults to false
                ],
                'orders' => [
                    'view' => false,
                    'create' => true,
                ],
                'products' => [
                    'view' => true, // should be ignored because module disabled
                ],
            ],
        ];

        $this->patch(route('users.update', $user), $data)->assertRedirect(route('users.index'));

        $user->refresh();
        // clients: module enabled, reflect provided values and default others to false
        $this->assertTrue($user->permissions['clients']['view']);
        $this->assertFalse($user->permissions['clients']['create']);

        // orders: module enabled, reflect provided values
        $this->assertFalse($user->permissions['orders']['view']);
        $this->assertTrue($user->permissions['orders']['create']);

        // products: module disabled, all abilities must be false regardless of input
        foreach (array_keys(config('permissions.resources.products.abilities')) as $ability) {
            $this->assertFalse($user->permissions['products'][$ability]);
        }
    }

    public function test_ensure_not_current_user_throws_for_self()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $controller = new \App\Http\Controllers\UserManagementController();
        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('ensureNotCurrentUser');
        $method->setAccessible(true);

        $this->expectException(HttpException::class);
        $method->invoke($controller, $user);
    }

    public function test_ensure_not_current_user_allows_for_different_user()
    {
        $controller = new \App\Http\Controllers\UserManagementController();
        $method = (new \ReflectionClass($controller))->getMethod('ensureNotCurrentUser');
        $method->setAccessible(true);

        $this->actingAs($this->admin);
        $other = User::factory()->create();

        // Should not throw
        $method->invoke($controller, $other);
        $this->assertTrue(true);
    }

    public function test_ensure_not_current_user_allows_when_not_authenticated()
    {
        $controller = new \App\Http\Controllers\UserManagementController();
        $method = (new \ReflectionClass($controller))->getMethod('ensureNotCurrentUser');
        $method->setAccessible(true);

        // Logout to ensure Auth::check() === false
        Auth::logout();

        $someone = User::factory()->create();
        // Should not throw
        $method->invoke($controller, $someone);
        $this->assertTrue(true);
    }

    public function test_destroy_deletes_user()
    {
        $user = User::factory()->create();

        $response = $this->delete(route('users.destroy', $user));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('status', 'Usuário removido com sucesso.');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_destroy_fails_if_user_has_clients()
    {
        $user = User::factory()->create();
        Client::factory()->create(['created_by_id' => $user->id]);

        $response = $this->delete(route('users.destroy', $user));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Usuário possui registros relacionados (clientes, produtos ou pedidos) e não pode ser excluído.');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_modal_returns_user_data()
    {
        $user = User::factory()->create();

        $response = $this->get(route('users.modal', $user));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => [
                'id',
                'name',
                'email',
                'role',
                'status',
                'permissions',
                'email_verified_at',
                'created_at',
                'updated_at',
            ],
        ]);
        $response->assertJson([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'permissions' => $user->permissions ?? [],
            ],
        ]);
    }

    public function test_cannot_edit_self()
    {
        $response = $this->get(route('users.edit', $this->admin));

        $response->assertStatus(403);
    }

    public function test_cannot_update_self()
    {
        $data = [
            'name' => 'New Name',
            'email' => $this->admin->email,
            'status' => $this->admin->status,
            'role' => $this->admin->role,
        ];

        $response = $this->patch(route('users.update', $this->admin), $data);

        $response->assertStatus(403);
    }

    public function test_cannot_delete_self()
    {
        $response = $this->delete(route('users.destroy', $this->admin));

        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_access_index()
    {
        $this->actingAs($this->regularUser);

        $response = $this->get(route('users.index'));

        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_create()
    {
        $this->actingAs($this->regularUser);

        $response = $this->get(route('users.create'));

        $response->assertStatus(403);
    }

    // Note: ensureNotCurrentUser is tested indirectly through the cannot edit/update/delete self tests
    // as the policy prevents self-actions, but the method provides an additional check
}
