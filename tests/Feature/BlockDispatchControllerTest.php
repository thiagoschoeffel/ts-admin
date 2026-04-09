<?php

namespace Tests\Feature;

use App\Models\BlockProduction;
use App\Models\BlockDispatch;
use App\Models\BlockDispatchItem;
use App\Models\InventoryMovement;
use App\Models\Operator;
use App\Models\ProductionPointing;
use App\Models\RawMaterial;
use App\Models\Sector;
use App\Models\Silo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class BlockDispatchControllerTest extends TestCase
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

        $sector = Sector::factory()->create(['name' => 'Setor Base']);
        foreach (['Operador 1', 'Operador 2', 'Operador 3'] as $name) {
            Operator::factory()->create([
                'sector_id' => $sector->id,
                'name' => $name,
                'status' => 'active',
            ]);
        }
        foreach (['Silo 1', 'Silo 2'] as $name) {
            Silo::factory()->create(['name' => $name]);
        }
        RawMaterial::factory()->create(['name' => 'Matéria Base']);
    }

    public function test_index_displays_page(): void
    {
        $response = $this->get(route('block-dispatches.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/BlockDispatches/Index')
            ->has('dispatches')
            ->has('filters'));
    }

    public function test_create_displays_page(): void
    {
        $response = $this->get(route('block-dispatches.create'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/BlockDispatches/Create'));
    }

    public function test_available_blocks_returns_blocks_and_status_flags(): void
    {
        $pp = ProductionPointing::factory()->create();
        $b1 = BlockProduction::factory()->notScrap()->create(['production_pointing_id' => $pp->id]);
        $b2 = BlockProduction::factory()->notScrap()->create(['production_pointing_id' => $pp->id]);

        $response = $this->getJson(route('block-dispatches.available-blocks', [
            'production_pointing_id' => $pp->id,
        ]));

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $b1->id]);
        $response->assertJsonFragment(['id' => $b2->id]);
        $response->assertJsonFragment(['already_dispatched' => false]);
    }

    public function test_store_creates_dispatch_and_items(): void
    {
        $pp = ProductionPointing::factory()->create();
        $b1 = BlockProduction::factory()->notScrap()->create(['production_pointing_id' => $pp->id]);
        $b2 = BlockProduction::factory()->notScrap()->create(['production_pointing_id' => $pp->id]);

        $payload = [
            'dispatched_at' => now()->format('Y-m-d H:i:s'),
            'manufacturing_order_number' => 'OF-123',
            'production_pointing_id' => $pp->id,
            'block_production_ids' => [$b1->id, $b2->id],
        ];

        $response = $this->from(route('block-dispatches.create'))
            ->withHeaders(['X-Inertia' => true])
            ->post(route('block-dispatches.store'), $payload);

        $response->assertRedirect(route('block-dispatches.index'));
        $response->assertSessionHas('status', 'Saída de blocos registrada com sucesso!');

        $this->assertDatabaseHas('block_dispatches', [
            'manufacturing_order_number' => 'OF-123',
            'production_pointing_id' => $pp->id,
        ]);
        $this->assertDatabaseHas('block_dispatch_items', [
            'block_production_id' => $b1->id,
        ]);
        $this->assertDatabaseHas('block_dispatch_items', [
            'block_production_id' => $b2->id,
        ]);

        $dispatchId = BlockDispatch::query()->value('id');
        $this->assertNotNull($dispatchId);

        $this->assertDatabaseHas('inventory_movements', [
            'reference_type' => BlockDispatch::class,
            'reference_id' => $dispatchId,
            'item_type' => 'block',
            'direction' => 'out',
            'quantity' => 1,
            'unit' => 'unit',
        ]);
        $this->assertSame(
            2,
            InventoryMovement::query()
                ->where('reference_type', BlockDispatch::class)
                ->where('reference_id', $dispatchId)
                ->count()
        );
    }

    public function test_store_does_not_allow_dispatching_same_block_twice(): void
    {
        $pp = ProductionPointing::factory()->create();
        $b1 = BlockProduction::factory()->notScrap()->create(['production_pointing_id' => $pp->id]);

        $payload = [
            'dispatched_at' => now()->format('Y-m-d H:i:s'),
            'manufacturing_order_number' => 'OF-123',
            'production_pointing_id' => $pp->id,
            'block_production_ids' => [$b1->id],
        ];

        $this->withHeaders(['X-Inertia' => true])->post(route('block-dispatches.store'), $payload);

        $response = $this->from(route('block-dispatches.index'))
            ->withHeaders(['X-Inertia' => true])
            ->post(route('block-dispatches.store'), $payload);

        $response->assertSessionHasErrors('block_production_ids');
        $this->assertDatabaseCount('block_dispatch_items', 1);
    }

    public function test_available_blocks_allows_blocks_already_in_dispatch_when_editing(): void
    {
        $pp = ProductionPointing::factory()->create();
        $b1 = BlockProduction::factory()->notScrap()->create(['production_pointing_id' => $pp->id]);
        $b2 = BlockProduction::factory()->notScrap()->create(['production_pointing_id' => $pp->id]);

        $dispatch = BlockDispatch::query()->create([
            'dispatched_at' => now(),
            'manufacturing_order_number' => 'OF-1',
            'production_pointing_id' => $pp->id,
            'created_by_id' => $this->admin->id,
            'updated_by_id' => $this->admin->id,
        ]);
        BlockDispatchItem::query()->insert([
            [
                'block_dispatch_id' => $dispatch->id,
                'block_production_id' => $b1->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->getJson(route('block-dispatches.available-blocks', [
            'production_pointing_id' => $pp->id,
            'block_dispatch_id' => $dispatch->id,
        ]));

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $b1->id, 'already_dispatched' => false]);
        $response->assertJsonFragment(['id' => $b2->id, 'already_dispatched' => false]);
    }

    public function test_update_updates_items_and_inventory_movements(): void
    {
        $pp = ProductionPointing::factory()->create();
        $b1 = BlockProduction::factory()->notScrap()->create(['production_pointing_id' => $pp->id]);
        $b2 = BlockProduction::factory()->notScrap()->create(['production_pointing_id' => $pp->id]);

        $dispatch = BlockDispatch::query()->create([
            'dispatched_at' => now()->subDay(),
            'manufacturing_order_number' => 'OF-OLD',
            'production_pointing_id' => $pp->id,
            'created_by_id' => $this->admin->id,
            'updated_by_id' => $this->admin->id,
        ]);
        BlockDispatchItem::query()->insert([
            [
                'block_dispatch_id' => $dispatch->id,
                'block_production_id' => $b1->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'block_dispatch_id' => $dispatch->id,
                'block_production_id' => $b2->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        app(\App\Services\Inventory\InventoryService::class)->syncBlockDispatch($dispatch);

        $payload = [
            'dispatched_at' => now()->format('Y-m-d H:i:s'),
            'manufacturing_order_number' => 'OF-NEW',
            'production_pointing_id' => $pp->id,
            'block_production_ids' => [$b2->id],
        ];

        $response = $this->from(route('block-dispatches.edit', $dispatch->id))
            ->withHeaders(['X-Inertia' => true])
            ->patch(route('block-dispatches.update', $dispatch->id), $payload);

        $response->assertRedirect(route('block-dispatches.index'));
        $response->assertSessionHas('status', 'Saída de blocos atualizada com sucesso!');

        $this->assertDatabaseMissing('block_dispatch_items', [
            'block_dispatch_id' => $dispatch->id,
            'block_production_id' => $b1->id,
        ]);
        $this->assertDatabaseHas('block_dispatch_items', [
            'block_dispatch_id' => $dispatch->id,
            'block_production_id' => $b2->id,
        ]);

        $this->assertSame(
            1,
            InventoryMovement::query()
                ->where('reference_type', BlockDispatch::class)
                ->where('reference_id', $dispatch->id)
                ->count()
        );
    }

    public function test_destroy_deletes_inventory_movements(): void
    {
        $pp = ProductionPointing::factory()->create();
        $b1 = BlockProduction::factory()->notScrap()->create(['production_pointing_id' => $pp->id]);

        $dispatch = BlockDispatch::query()->create([
            'dispatched_at' => now(),
            'manufacturing_order_number' => 'OF-DEL',
            'production_pointing_id' => $pp->id,
            'created_by_id' => $this->admin->id,
            'updated_by_id' => $this->admin->id,
        ]);
        BlockDispatchItem::query()->insert([
            [
                'block_dispatch_id' => $dispatch->id,
                'block_production_id' => $b1->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        app(\App\Services\Inventory\InventoryService::class)->syncBlockDispatch($dispatch);

        $this->assertSame(
            1,
            InventoryMovement::query()
                ->where('reference_type', BlockDispatch::class)
                ->where('reference_id', $dispatch->id)
                ->count()
        );

        $response = $this->from(route('block-dispatches.index'))
            ->withHeaders(['X-Inertia' => true])
            ->delete(route('block-dispatches.destroy', $dispatch->id));

        $response->assertRedirect(route('block-dispatches.index'));
        $response->assertSessionHas('status', 'Saída de blocos removida com sucesso!');

        $this->assertSame(
            0,
            InventoryMovement::query()
                ->where('reference_type', BlockDispatch::class)
                ->where('reference_id', $dispatch->id)
                ->count()
        );
    }
}
