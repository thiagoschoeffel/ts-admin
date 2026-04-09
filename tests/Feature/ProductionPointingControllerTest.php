<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\ProductionPointing;
use App\Models\RawMaterial;
use App\Models\Sector;
use App\Models\Silo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProductionPointingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($this->admin);
        App::setLocale('pt_BR');
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

        $sector = Sector::factory()->create(['name' => 'Setor Padrão']);

        foreach (['Operador Base 1', 'Operador Base 2', 'Operador Base 3'] as $name) {
            Operator::factory()->create([
                'sector_id' => $sector->id,
                'name' => $name,
                'status' => 'active',
            ]);
        }

        foreach (['Silo Base 1', 'Silo Base 2', 'Silo Base 3'] as $name) {
            Silo::factory()->create(['name' => $name]);
        }

        foreach (['Matéria Base 1', 'Matéria Base 2', 'Matéria Base 3'] as $name) {
            RawMaterial::factory()->create(['name' => $name]);
        }
    }

    public function test_index_displays_production_pointings_list(): void
    {
        ProductionPointing::factory()->create(['sheet_number' => 101]);
        ProductionPointing::factory()->create(['sheet_number' => 102]);

        $response = $this->get(route('production-pointings.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/ProductionPointings/Index')
                ->has('productionPointings')
                ->has('filters')
        );
    }

    public function test_index_filters_by_search(): void
    {
        ProductionPointing::factory()->create(['sheet_number' => 555]);
        ProductionPointing::factory()->create(['sheet_number' => 777]);

        $response = $this->get(route('production-pointings.index', ['search' => '555']));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/ProductionPointings/Index')
                ->where('filters.search', '555')
        );
    }

    public function test_index_filters_by_status(): void
    {
        ProductionPointing::factory()->create(['status' => 'active']);
        ProductionPointing::factory()->create(['status' => 'inactive']);

        $response = $this->get(route('production-pointings.index', ['status' => 'inactive']));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/ProductionPointings/Index')
                ->where('filters.status', 'inactive')
        );
    }

    public function test_create_displays_form(): void
    {
        $response = $this->get(route('production-pointings.create'));
        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/ProductionPointings/Create')
                ->has('rawMaterials')
                ->has('operators')
                ->has('silos')
        );
    }

    public function test_store_creates_production_pointing(): void
    {
        $resources = $this->makeProductionResources();
        $payload = $this->validPayload($resources);

        $response = $this->from(route('production-pointings.create'))
            ->withHeaders(['X-Inertia' => true])
            ->post(route('production-pointings.store'), $payload);

        $response->assertRedirect(route('production-pointings.index'));
        $response->assertSessionHas('status', 'Apontamento de produção criado com sucesso!');
        $created = ProductionPointing::where('sheet_number', $payload['sheet_number'])->first();
        $this->assertNotNull($created);
        $this->assertSame($payload['status'], $created->status);
        $this->assertSame($payload['sheet_number'], $created->sheet_number);
        $this->assertEquals($payload['raw_material_id'], $created->raw_material_id);
        $this->assertEquals((float) $payload['quantity'], (float) $created->quantity);
        $this->assertEquals($payload['ended_at'], $created->ended_at?->format('Y-m-d H:i'));

        foreach ($payload['operator_ids'] as $operatorId) {
            $this->assertDatabaseHas('production_pointing_operator', [
                'production_pointing_id' => $created->id,
                'operator_id' => $operatorId,
            ]);
        }

        foreach ($payload['silo_ids'] as $siloId) {
            $this->assertDatabaseHas('production_pointing_silo', [
                'production_pointing_id' => $created->id,
                'silo_id' => $siloId,
            ]);
        }
    }

    public function test_store_requires_sheet_number(): void
    {
        $resources = $this->makeProductionResources();
        $payload = $this->validPayload($resources, [
            'sheet_number' => null,
        ]);

        $response = $this->from(route('production-pointings.create'))
            ->withHeaders(['X-Inertia' => true])
            ->post(route('production-pointings.store'), $payload);

        $response->assertSessionHasErrors('sheet_number');
    }

    public function test_store_requires_ended_at(): void
    {
        $resources = $this->makeProductionResources();
        $payload = $this->validPayload($resources, [
            'ended_at' => null,
        ]);

        $response = $this->from(route('production-pointings.create'))
            ->withHeaders(['X-Inertia' => true])
            ->post(route('production-pointings.store'), $payload);

        $response->assertSessionHasErrors('ended_at');
    }

    public function test_edit_displays_form(): void
    {
        $productionPointing = ProductionPointing::factory()->create();

        $response = $this->get(route('production-pointings.edit', $productionPointing));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/ProductionPointings/Edit')
                ->where('productionPointing.id', $productionPointing->id)
                ->has('rawMaterials')
                ->has('operators')
                ->has('silos')
        );
    }

    public function test_update_modifies_production_pointing(): void
    {
        $productionPointing = ProductionPointing::factory()->create(['status' => 'active']);

        $resources = $this->makeProductionResources();
        $payload = $this->validPayload($resources, [
            'status' => 'inactive',
            'sheet_number' => 9999,
        ]);

        $response = $this->from(route('production-pointings.edit', $productionPointing))
            ->withHeaders(['X-Inertia' => true])
            ->patch(route('production-pointings.update', $productionPointing), $payload);

        $response->assertRedirect(route('production-pointings.index'));
        $response->assertSessionHas('status', 'Apontamento de produção atualizado com sucesso!');
        $this->assertDatabaseHas('production_pointings', [
            'id' => $productionPointing->id,
            'status' => 'inactive',
            'sheet_number' => $payload['sheet_number'],
            'raw_material_id' => $payload['raw_material_id'],
        ]);
        $this->assertEquals($payload['ended_at'], $productionPointing->fresh()->ended_at?->format('Y-m-d H:i'));

        foreach ($payload['operator_ids'] as $operatorId) {
            $this->assertDatabaseHas('production_pointing_operator', [
                'production_pointing_id' => $productionPointing->id,
                'operator_id' => $operatorId,
            ]);
        }

        foreach ($payload['silo_ids'] as $siloId) {
            $this->assertDatabaseHas('production_pointing_silo', [
                'production_pointing_id' => $productionPointing->id,
                'silo_id' => $siloId,
            ]);
        }
    }

    public function test_update_requires_sheet_number(): void
    {
        $productionPointing = ProductionPointing::factory()->create();

        $resources = $this->makeProductionResources();
        $payload = $this->validPayload($resources, [
            'sheet_number' => null,
        ]);

        $response = $this->from(route('production-pointings.edit', $productionPointing))
            ->withHeaders(['X-Inertia' => true])
            ->patch(route('production-pointings.update', $productionPointing), $payload);

        $response->assertSessionHasErrors('sheet_number');
    }

    public function test_update_requires_ended_at(): void
    {
        $productionPointing = ProductionPointing::factory()->create();

        $resources = $this->makeProductionResources();
        $payload = $this->validPayload($resources, [
            'ended_at' => null,
        ]);

        $response = $this->from(route('production-pointings.edit', $productionPointing))
            ->withHeaders(['X-Inertia' => true])
            ->patch(route('production-pointings.update', $productionPointing), $payload);

        $response->assertSessionHasErrors('ended_at');
    }

    public function test_destroy_deletes_production_pointing(): void
    {
        $productionPointing = ProductionPointing::factory()->create();

        $this->get(route('production-pointings.index'));
        $token = csrf_token();

        $response = $this->withHeaders(['X-Inertia' => true])
            ->delete(route('production-pointings.destroy', $productionPointing), ['_token' => $token]);

        $response->assertRedirect(route('production-pointings.index'));
        $response->assertSessionHas('status', 'Apontamento de produção removido com sucesso!');
        $this->assertDatabaseMissing('production_pointings', ['id' => $productionPointing->id]);
    }

    public function test_modal_returns_production_pointing_details(): void
    {
        $productionPointing = ProductionPointing::factory()->create();

        $response = $this->get(route('production-pointings.modal', $productionPointing));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'productionPointing' => [
                'id',
                'status',
                'sheet_number',
                'sheet_label',
                'started_at',
                'ended_at',
                'quantity',
                'raw_material',
                'operators',
                'silos',
                'created_at',
                'updated_at',
                'created_by',
                'updated_by',
            ],
        ]);
    }

    public function test_denies_access_without_permission(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'permissions' => [],
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $response = $this->get(route('production-pointings.index'));

        $response->assertStatus(403);
    }

    public function test_index_pagination_works(): void
    {
        ProductionPointing::factory()->count(25)->create();

        $response = $this->get(route('production-pointings.index', ['per_page' => 10]));

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/ProductionPointings/Index')
                ->has('productionPointings')
                ->where('productionPointings.per_page', 10)
        );
}

    private function makeProductionResources(): array
    {
        $rawMaterial = RawMaterial::inRandomOrder()->first();
        $operators = Operator::inRandomOrder()->take(2)->get();
        $silos = Silo::inRandomOrder()->take(2)->get();

        return compact('rawMaterial', 'operators', 'silos');
    }

    private function validPayload(array $resources, array $overrides = []): array
    {
        $startedAt = Carbon::now()->startOfMinute();
        $payload = [
            '_token' => csrf_token(),
            'status' => 'active',
            'sheet_number' => 123,
            'raw_material_id' => $resources['rawMaterial']->id,
            'quantity' => 150.75,
            'started_at' => $startedAt->format('Y-m-d H:i'),
            'ended_at' => $startedAt->copy()->addHour()->format('Y-m-d H:i'),
            'operator_ids' => $resources['operators']->pluck('id')->all(),
            'silo_ids' => $resources['silos']->pluck('id')->all(),
        ];

        return array_merge($payload, $overrides);
    }
}
