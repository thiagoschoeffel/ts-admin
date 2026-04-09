<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreReasonTypeRequest;
use App\Models\ReasonType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class StoreReasonTypeRequestTest extends TestCase
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
            'permissions' => ['reason_types' => ['create' => true]]
        ]);
        $this->userWithoutPermission = User::factory()->create([
            'role' => 'user',
            'permissions' => ['reason_types' => ['create' => false]]
        ]);
    }

    public function test_admin_can_authorize_store_request()
    {
        Auth::login($this->admin);

        $request = new StoreReasonTypeRequest();

        $this->assertTrue($request->authorize());
    }

    public function test_user_with_create_permission_can_authorize_store_request()
    {
        Auth::login($this->userWithPermission);

        $request = new StoreReasonTypeRequest();

        $this->assertTrue($request->authorize());
    }

    public function test_user_without_create_permission_cannot_authorize_store_request()
    {
        Auth::login($this->userWithoutPermission);

        $request = new StoreReasonTypeRequest();

        $this->assertFalse($request->authorize());
    }

    public function test_validation_rules_require_name()
    {
        Auth::login($this->admin);

        $request = new StoreReasonTypeRequest();
        $rules = $request->rules();

        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('min:2', $rules['name']);
        $this->assertContains('max:255', $rules['name']);
        $this->assertContains('unique:reason_types', $rules['name']);
    }

    public function test_validation_rules_require_status()
    {
        Auth::login($this->admin);

        $request = new StoreReasonTypeRequest();
        $rules = $request->rules();

        $this->assertContains('required', $rules['status']);
        $this->assertContains('in:active,inactive', $rules['status']);
    }

    public function test_validation_passes_with_valid_data()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'Novo Tipo de Motivo',
            'status' => 'active',
        ];

        $request = new StoreReasonTypeRequest();
        $request->merge($data);

        $validator = validator($data, $request->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_validation_fails_without_name()
    {
        Auth::login($this->admin);

        $data = [
            'status' => 'active',
        ];

        $request = new StoreReasonTypeRequest();
        $request->merge($data);

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

        $request = new StoreReasonTypeRequest();
        $request->merge($data);

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_name_too_short()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'A',
            'status' => 'active',
        ];

        $request = new StoreReasonTypeRequest();
        $request->merge($data);

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

        $request = new StoreReasonTypeRequest();
        $request->merge($data);

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_duplicate_name()
    {
        ReasonType::factory()->create(['name' => 'Tipo Existente']);

        Auth::login($this->admin);

        $data = [
            'name' => 'Tipo Existente',
            'status' => 'active',
        ];

        $request = new StoreReasonTypeRequest();
        $request->merge($data);

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validation_fails_without_status()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'Novo Tipo de Motivo',
        ];

        $request = new StoreReasonTypeRequest();
        $request->merge($data);

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_invalid_status()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'Novo Tipo de Motivo',
            'status' => 'invalid',
        ];

        $request = new StoreReasonTypeRequest();
        $request->merge($data);

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_validation_passes_with_inactive_status()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'Novo Tipo de Motivo',
            'status' => 'inactive',
        ];

        $request = new StoreReasonTypeRequest();
        $request->merge($data);

        $validator = validator($data, $request->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_custom_attributes_are_correct()
    {
        Auth::login($this->admin);

        $request = new StoreReasonTypeRequest();
        $attributes = $request->attributes();

        $this->assertEquals('nome', $attributes['name']);
        $this->assertEquals('status', $attributes['status']);
    }

    public function test_custom_messages_are_correct()
    {
        Auth::login($this->admin);

        $request = new StoreReasonTypeRequest();
        $messages = $request->messages();

        $this->assertEquals('Já existe um tipo de motivo com este nome.', $messages['name.unique']);
    }
}
