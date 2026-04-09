<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\MoldType;
use App\Models\MoldedDispatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class MoldedDispatchControllerTest extends TestCase
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
    }

    public function test_index_displays_page(): void
    {
        $response = $this->get(route('molded-dispatches.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/MoldedDispatches/Index')
            ->has('dispatches')
            ->has('filters')
            ->has('moldTypes'));
    }

    public function test_create_displays_page(): void
    {
        MoldType::factory()->create(['status' => 'active']);

        $response = $this->get(route('molded-dispatches.create'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/MoldedDispatches/Create')
            ->has('moldTypes'));
    }

    public function test_store_creates_dispatch_and_inventory_movement(): void
    {
        $moldType = MoldType::factory()->create(['status' => 'active']);

        $payload = [
            'dispatched_at' => now()->format('Y-m-d H:i:s'),
            'manufacturing_order_number' => 'OF-123',
            'mold_type_id' => $moldType->id,
            'quantity' => 10,
        ];

        $response = $this->from(route('molded-dispatches.create'))
            ->withHeaders(['X-Inertia' => true])
            ->post(route('molded-dispatches.store'), $payload);

        $response->assertRedirect(route('molded-dispatches.index'));
        $response->assertSessionHas('status', 'Saída de moldados registrada com sucesso!');

        $this->assertDatabaseHas('molded_dispatches', [
            'manufacturing_order_number' => 'OF-123',
            'mold_type_id' => $moldType->id,
            'quantity' => 10,
        ]);

        $dispatchId = MoldedDispatch::query()->value('id');
        $this->assertNotNull($dispatchId);

        $this->assertDatabaseHas('inventory_movements', [
            'reference_type' => MoldedDispatch::class,
            'reference_id' => $dispatchId,
            'item_type' => 'molded',
            'direction' => 'out',
            'quantity' => 10,
            'unit' => 'unit',
            'mold_type_id' => $moldType->id,
        ]);
    }

    public function test_update_updates_inventory_movement(): void
    {
        $moldType1 = MoldType::factory()->create(['status' => 'active']);
        $moldType2 = MoldType::factory()->create(['status' => 'active']);

        $dispatch = MoldedDispatch::query()->create([
            'dispatched_at' => now()->subDay(),
            'manufacturing_order_number' => 'OF-OLD',
            'mold_type_id' => $moldType1->id,
            'quantity' => 5,
            'created_by_id' => $this->admin->id,
            'updated_by_id' => $this->admin->id,
        ]);

        app(\App\Services\Inventory\InventoryService::class)->syncMoldedDispatch($dispatch);

        $payload = [
            'dispatched_at' => now()->format('Y-m-d H:i:s'),
            'manufacturing_order_number' => 'OF-NEW',
            'mold_type_id' => $moldType2->id,
            'quantity' => 12,
        ];

        $response = $this->from(route('molded-dispatches.edit', $dispatch->id))
            ->withHeaders(['X-Inertia' => true])
            ->patch(route('molded-dispatches.update', $dispatch->id), $payload);

        $response->assertRedirect(route('molded-dispatches.index'));
        $response->assertSessionHas('status', 'Saída de moldados atualizada com sucesso!');

        $this->assertSame(
            1,
            InventoryMovement::query()
                ->where('reference_type', MoldedDispatch::class)
                ->where('reference_id', $dispatch->id)
                ->count()
        );

        $this->assertDatabaseHas('inventory_movements', [
            'reference_type' => MoldedDispatch::class,
            'reference_id' => $dispatch->id,
            'mold_type_id' => $moldType2->id,
            'quantity' => 12,
        ]);
    }

    public function test_destroy_deletes_inventory_movements(): void
    {
        $moldType = MoldType::factory()->create(['status' => 'active']);

        $dispatch = MoldedDispatch::query()->create([
            'dispatched_at' => now(),
            'manufacturing_order_number' => 'OF-DEL',
            'mold_type_id' => $moldType->id,
            'quantity' => 3,
            'created_by_id' => $this->admin->id,
            'updated_by_id' => $this->admin->id,
        ]);

        app(\App\Services\Inventory\InventoryService::class)->syncMoldedDispatch($dispatch);

        $this->assertSame(
            1,
            InventoryMovement::query()
                ->where('reference_type', MoldedDispatch::class)
                ->where('reference_id', $dispatch->id)
                ->count()
        );

        $response = $this->from(route('molded-dispatches.index'))
            ->withHeaders(['X-Inertia' => true])
            ->delete(route('molded-dispatches.destroy', $dispatch->id));

        $response->assertRedirect(route('molded-dispatches.index'));
        $response->assertSessionHas('status', 'Saída de moldados removida com sucesso!');

        $this->assertSame(
            0,
            InventoryMovement::query()
                ->where('reference_type', MoldedDispatch::class)
                ->where('reference_id', $dispatch->id)
                ->count()
        );
    }
}

