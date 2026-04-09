<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreSectorRequest;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class StoreSectorRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $userWithPermission;
    private User $userWithoutPermission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->userWithPermission = User::factory()->create([
            'role' => 'user',
            'permissions' => ['sectors' => ['create' => true]]
        ]);
        $this->userWithoutPermission = User::factory()->create([
            'role' => 'user',
            'permissions' => ['sectors' => ['create' => false]]
        ]);
    }

    public function test_admin_can_authorize_store_request()
    {
        Auth::login($this->admin);

        $request = new StoreSectorRequest();
        $request->setUserResolver(fn() => $this->admin);

        $this->assertTrue($request->authorize());
    }

    public function test_user_with_create_permission_can_authorize_store_request()
    {
        Auth::login($this->userWithPermission);

        $request = new StoreSectorRequest();
        $request->setUserResolver(fn() => $this->userWithPermission);

        $this->assertTrue($request->authorize());
    }

    public function test_user_without_create_permission_cannot_authorize_store_request()
    {
        Auth::login($this->userWithoutPermission);

        $request = new StoreSectorRequest();
        $request->setUserResolver(fn() => $this->userWithoutPermission);

        $this->assertFalse($request->authorize());
    }

    public function test_validation_rules_require_name()
    {
        Auth::login($this->admin);

        $request = new StoreSectorRequest();
        $rules = $request->rules();

        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:255', $rules['name']);
    }

    public function test_validation_rules_require_unique_name()
    {
        Auth::login($this->admin);

        $request = new StoreSectorRequest();
        $rules = $request->rules();

        $this->assertContains('unique:sectors,name', $rules['name']);
    }

    public function test_validation_rules_require_status()
    {
        Auth::login($this->admin);

        $request = new StoreSectorRequest();
        $rules = $request->rules();

        $this->assertContains('required', $rules['status']);
        $this->assertContains('in:active,inactive', $rules['status']);
    }

    public function test_validation_passes_with_valid_data()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'Setor de Teste',
            'status' => 'active',
        ];

        $request = new StoreSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);

        $validator = validator($data, $request->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_validation_fails_without_name()
    {
        Auth::login($this->admin);

        $data = [
            'status' => 'active',
        ];

        $request = new StoreSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_empty_name()
    {
        Auth::login($this->admin);

        $data = [
            'name' => '',
            'status' => 'active',
        ];

        $request = new StoreSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_name_too_long()
    {
        Auth::login($this->admin);

        $data = [
            'name' => str_repeat('a', 256),
            'status' => 'active',
        ];

        $request = new StoreSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_duplicate_name()
    {
        Sector::factory()->create(['name' => 'Setor Existente']);
        Auth::login($this->admin);

        $data = [
            'name' => 'Setor Existente',
            'status' => 'active',
        ];

        $request = new StoreSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validation_fails_without_status()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'Setor de Teste',
        ];

        $request = new StoreSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_invalid_status()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'Setor de Teste',
            'status' => 'invalid',
        ];

        $request = new StoreSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_validation_passes_with_inactive_status()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'Setor de Teste',
            'status' => 'inactive',
        ];

        $request = new StoreSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);

        $validator = validator($data, $request->rules());
        $this->assertFalse($validator->fails());
    }
}
