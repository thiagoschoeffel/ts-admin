<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\MachineDowntime;
use App\Models\Reason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MachineDowntimeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $userWithoutPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'role' => 'user',
            'permissions' => ['machine_downtimes' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true]],
        ]);
        $this->userWithoutPermissions = User::factory()->create(['role' => 'user', 'permissions' => []]);
    }

    public function test_lists_machine_downtimes_with_pagination(): void
    {
        $machine = Machine::factory()->create(['status' => 'active']);
        $reason = Reason::factory()->create(['status' => 'active']);
        MachineDowntime::factory()->count(12)->create(['machine_id' => $machine->id, 'reason_id' => $reason->id]);

        $response = $this->actingAs($this->user)->get(route('machine_downtimes.index'));
        $response->assertStatus(200);
    }

    public function test_creates_machine_downtime_with_valid_data(): void
    {
        $machine = Machine::factory()->create(['status' => 'active']);
        $reason = Reason::factory()->create(['status' => 'active']);

        $payload = [
            'machine_id' => $machine->id,
            'reason_id' => $reason->id,
            'started_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'ended_at' => now()->format('Y-m-d\TH:i'),
            'notes' => 'Teste',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->user)->post(route('machine_downtimes.store'), $payload);
        $response->assertRedirect(route('machine_downtimes.index'));
        $this->assertDatabaseHas('machine_downtimes', [
            'machine_id' => $machine->id,
            'reason_id' => $reason->id,
            'status' => 'active',
        ]);
    }

    public function test_denies_create_without_permission(): void
    {
        $response = $this->actingAs($this->userWithoutPermissions)->get(route('machine_downtimes.create'));
        $response->assertStatus(403);
    }

    public function test_updates_machine_downtime(): void
    {
        $machine = Machine::factory()->create(['status' => 'active']);
        $reason = Reason::factory()->create(['status' => 'active']);
        $downtime = MachineDowntime::factory()->create(['machine_id' => $machine->id, 'reason_id' => $reason->id]);

        $payload = [
            'machine_id' => $machine->id,
            'reason_id' => $reason->id,
            'started_at' => now()->subHours(2)->format('Y-m-d\TH:i'),
            'ended_at' => now()->format('Y-m-d\TH:i'),
            'notes' => 'Atualizado',
            'status' => 'inactive',
        ];
        $response = $this->actingAs($this->user)->patch(route('machine_downtimes.update', $downtime), $payload);
        $response->assertRedirect(route('machine_downtimes.index'));
        $this->assertDatabaseHas('machine_downtimes', [ 'id' => $downtime->id, 'notes' => 'Atualizado', 'status' => 'inactive' ]);
    }

    public function test_denies_update_without_permission(): void
    {
        $downtime = MachineDowntime::factory()->create();
        $response = $this->actingAs($this->userWithoutPermissions)->get(route('machine_downtimes.edit', $downtime));
        $response->assertStatus(403);
    }

    public function test_deletes_machine_downtime_with_permission(): void
    {
        $downtime = MachineDowntime::factory()->create();
        $response = $this->actingAs($this->user)->delete(route('machine_downtimes.destroy', $downtime));
        $response->assertRedirect(route('machine_downtimes.index'));
        $this->assertDatabaseMissing('machine_downtimes', ['id' => $downtime->id]);
    }

    public function test_denies_delete_without_permission(): void
    {
        $downtime = MachineDowntime::factory()->create();
        $response = $this->actingAs($this->userWithoutPermissions)->delete(route('machine_downtimes.destroy', $downtime));
        $response->assertStatus(403);
    }

    public function test_shows_machine_downtime_details_modal_payload(): void
    {
        $downtime = MachineDowntime::factory()->create();
        $response = $this->actingAs($this->user)->get(route('machine_downtimes.modal', $downtime));
        $response->assertOk();
        $response->assertJsonStructure(['downtime' => ['id', 'machine_id', 'reason_id', 'started_at', 'ended_at', 'duration', 'status', 'created_at', 'updated_at']]);
    }
}

