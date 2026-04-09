<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateReasonTypeRequest;
use App\Models\ReasonType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UpdateReasonTypeRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $userWithPermission;
    private User $userWithoutPermission;
    private ReasonType $reasonType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->userWithPermission = User::factory()->create([
            'role' => 'user',
            'permissions' => ['reason_types' => ['update' => true]]
        ]);
        $this->userWithoutPermission = User::factory()->create([
            'role' => 'user',
            'permissions' => ['reason_types' => ['update' => false]]
        ]);
        $this->reasonType = ReasonType::factory()->create();
    }

    public function test_admin_can_authorize_update_request()
    {
        Auth::login($this->admin);

        $request = new UpdateReasonTypeRequest();
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->reasonType));

        $this->assertTrue($request->authorize());
    }

    public function test_user_with_update_permission_can_authorize_update_request()
    {
        Auth::login($this->userWithPermission);

        $request = new UpdateReasonTypeRequest();
        $request->setUserResolver(fn() => $this->userWithPermission);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->reasonType));

        $this->assertTrue($request->authorize());
    }

    public function test_user_without_update_permission_cannot_authorize_update_request()
    {
        Auth::login($this->userWithoutPermission);

        $request = new UpdateReasonTypeRequest();
        $request->setUserResolver(fn() => $this->userWithoutPermission);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->reasonType));

        $this->assertFalse($request->authorize());
    }

    public function test_validation_rules_require_name()
    {
        Auth::login($this->admin);

        $request = new UpdateReasonTypeRequest();
        $request->setRouteResolver(fn() => $this->createMockRoute($this->reasonType));
        $rules = $request->rules();

        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('min:2', $rules['name']);
        $this->assertContains('max:255', $rules['name']);
    }

    public function test_validation_rules_require_unique_name_ignoring_current_reason_type()
    {
        Auth::login($this->admin);

        $request = new UpdateReasonTypeRequest();
        $request->setRouteResolver(fn() => $this->createMockRoute($this->reasonType));
        $rules = $request->rules();

        $this->assertStringContainsString('unique:reason_types', $rules['name'][4]);
        $this->assertStringContainsString('ignore(' . $this->reasonType->id, $rules['name'][4]);
    }

    public function test_validation_rules_require_status()
    {
        Auth::login($this->admin);

        $request = new UpdateReasonTypeRequest();
        $request->setRouteResolver(fn() => $this->createMockRoute($this->reasonType));
        $rules = $request->rules();

        $this->assertContains('required', $rules['status']);
        $this->assertContains('in:active,inactive', $rules['status']);
    }

    public function test_validation_passes_with_valid_data()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'Tipo de Motivo Atualizado',
            'status' => 'active',
        ];

        $request = new UpdateReasonTypeRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->reasonType));

        $validator = validator($data, $request->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_validation_fails_without_name()
    {
        Auth::login($this->admin);

        $data = [
            'status' => 'active',
        ];

        $request = new UpdateReasonTypeRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->reasonType));

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

        $request = new UpdateReasonTypeRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->reasonType));

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

        $request = new UpdateReasonTypeRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->reasonType));

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

        $request = new UpdateReasonTypeRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->reasonType));

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validation_allows_same_name_for_same_reason_type()
    {
        Auth::login($this->admin);

        $data = [
            'name' => $this->reasonType->name,
            'status' => 'active',
        ];

        $request = new UpdateReasonTypeRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->reasonType));

        $validator = validator($data, $request->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_validation_fails_with_duplicate_name()
    {
        $otherReasonType = ReasonType::factory()->create(['name' => 'Outro Tipo']);

        Auth::login($this->admin);

        $data = [
            'name' => 'Outro Tipo',
            'status' => 'active',
        ];

        $request = new UpdateReasonTypeRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->reasonType));

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validation_fails_without_status()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'Tipo de Motivo Atualizado',
        ];

        $request = new UpdateReasonTypeRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->reasonType));

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_invalid_status()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'Tipo de Motivo Atualizado',
            'status' => 'invalid',
        ];

        $request = new UpdateReasonTypeRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->reasonType));

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_validation_passes_with_inactive_status()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'Tipo de Motivo Atualizado',
            'status' => 'inactive',
        ];

        $request = new UpdateReasonTypeRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->reasonType));

        $validator = validator($data, $request->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_custom_attributes_are_correct()
    {
        Auth::login($this->admin);

        $request = new UpdateReasonTypeRequest();
        $attributes = $request->attributes();

        $this->assertEquals('nome', $attributes['name']);
        $this->assertEquals('status', $attributes['status']);
    }

    public function test_custom_messages_are_correct()
    {
        Auth::login($this->admin);

        $request = new UpdateReasonTypeRequest();
        $messages = $request->messages();

        $this->assertEquals('Já existe um tipo de motivo com este nome.', $messages['name.unique']);
    }

    private function createMockRoute($reasonType)
    {
        $route = $this->createMock(\Illuminate\Routing\Route::class);
        $route->method('parameter')->willReturn($reasonType);
        return $route;
    }
}
