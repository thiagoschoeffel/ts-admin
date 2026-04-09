<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateSectorRequest;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UpdateSectorRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $userWithPermission;
    private User $userWithoutPermission;
    private Sector $sector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->userWithPermission = User::factory()->create([
            'role' => 'user',
            'permissions' => ['sectors' => ['update' => true]]
        ]);
        $this->userWithoutPermission = User::factory()->create([
            'role' => 'user',
            'permissions' => ['sectors' => ['update' => false]]
        ]);
        $this->sector = Sector::factory()->create();
    }

    public function test_admin_can_authorize_update_request()
    {
        Auth::login($this->admin);

        $request = new UpdateSectorRequest();
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->sector));

        $this->assertTrue($request->authorize());
    }

    public function test_user_with_update_permission_can_authorize_update_request()
    {
        Auth::login($this->userWithPermission);

        $request = new UpdateSectorRequest();
        $request->setUserResolver(fn() => $this->userWithPermission);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->sector));

        $this->assertTrue($request->authorize());
    }

    public function test_user_without_update_permission_cannot_authorize_update_request()
    {
        Auth::login($this->userWithoutPermission);

        $request = new UpdateSectorRequest();
        $request->setUserResolver(fn() => $this->userWithoutPermission);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->sector));

        $this->assertFalse($request->authorize());
    }

    public function test_validation_rules_require_name()
    {
        Auth::login($this->admin);

        $request = new UpdateSectorRequest();
        $request->setRouteResolver(fn() => $this->createMockRoute($this->sector));
        $rules = $request->rules();

        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:255', $rules['name']);
    }

    public function test_validation_rules_require_unique_name_ignoring_current_sector()
    {
        Auth::login($this->admin);

        $request = new UpdateSectorRequest();
        $request->setRouteResolver(fn() => $this->createMockRoute($this->sector));
        $rules = $request->rules();

        $this->assertStringContainsString('unique:sectors,name', $rules['name']);
        $this->assertStringContainsString($this->sector->id, $rules['name']);
    }

    public function test_validation_rules_require_status()
    {
        Auth::login($this->admin);

        $request = new UpdateSectorRequest();
        $request->setRouteResolver(fn() => $this->createMockRoute($this->sector));
        $rules = $request->rules();

        $this->assertContains('required', $rules['status']);
        $this->assertContains('in:active,inactive', $rules['status']);
    }

    public function test_validation_passes_with_valid_data()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'Setor Atualizado',
            'status' => 'active',
        ];

        $request = new UpdateSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->sector));

        $validator = validator($data, $request->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_validation_fails_without_name()
    {
        Auth::login($this->admin);

        $data = [
            'status' => 'active',
        ];

        $request = new UpdateSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->sector));

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

        $request = new UpdateSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->sector));

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

        $request = new UpdateSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->sector));

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validation_allows_same_name_for_current_sector()
    {
        Auth::login($this->admin);

        $data = [
            'name' => $this->sector->name,
            'status' => 'active',
        ];

        $request = new UpdateSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->sector));

        $validator = validator($data, $request->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_validation_fails_with_duplicate_name_from_other_sector()
    {
        $otherSector = Sector::factory()->create(['name' => 'Outro Setor']);
        Auth::login($this->admin);

        $data = [
            'name' => 'Outro Setor',
            'status' => 'active',
        ];

        $request = new UpdateSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->sector));

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validation_fails_without_status()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'Setor Atualizado',
        ];

        $request = new UpdateSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->sector));

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_invalid_status()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'Setor Atualizado',
            'status' => 'invalid',
        ];

        $request = new UpdateSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->sector));

        $validator = validator($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_validation_passes_with_inactive_status()
    {
        Auth::login($this->admin);

        $data = [
            'name' => 'Setor Atualizado',
            'status' => 'inactive',
        ];

        $request = new UpdateSectorRequest();
        $request->merge($data);
        $request->setUserResolver(fn() => $this->admin);
        $request->setRouteResolver(fn() => $this->createMockRoute($this->sector));

        $validator = validator($data, $request->rules());
        $this->assertFalse($validator->fails());
    }

    private function createMockRoute($sector)
    {
        $route = $this->createMock(\Illuminate\Routing\Route::class);
        $route->method('parameter')->willReturn($sector);
        return $route;
    }
}
