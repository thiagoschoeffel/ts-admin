<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $userWithoutPermissions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'permissions' => ['leads' => ['view' => true, 'create' => true, 'update' => true, 'delete' => true]],
        ]);

        $this->userWithoutPermissions = User::factory()->create([
            'email_verified_at' => now(),
            'permissions' => [], // No permissions
        ]);
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->get(route('leads.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_index_requires_view_permission(): void
    {
        $response = $this->actingAs($this->userWithoutPermissions)->get(route('leads.index'));

        $response->assertForbidden();
    }

    public function test_index_returns_leads_list(): void
    {
        Lead::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('leads.index'));

        $response->assertInertia(function ($page) {
            $page->component('Admin/Leads/Index')
                ->has('leads.data', 3);
        });
    }

    public function test_index_orders_by_created_at_desc(): void
    {
        $oldLead = Lead::factory()->create(['created_at' => now()->subDays(2)]);
        $newLead = Lead::factory()->create(['created_at' => now()]);

        $response = $this->actingAs($this->user)->get(route('leads.index'));

        $response->assertInertia(function ($page) use ($oldLead, $newLead) {
            $page->component('Admin/Leads/Index')
                ->has('leads.data', 2)
                ->where('leads.data.0.id', $newLead->id)
                ->where('leads.data.1.id', $oldLead->id);
        });
    }

    public function test_create_requires_authentication(): void
    {
        $response = $this->get(route('leads.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_create_requires_create_permission(): void
    {
        $response = $this->actingAs($this->userWithoutPermissions)->get(route('leads.create'));

        $response->assertForbidden();
    }

    public function test_create_returns_create_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('leads.create'));

        $response->assertInertia(function ($page) {
            $page->component('Admin/Leads/Create');
        });
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->post(route('leads.store'));

        $response->assertRedirect(route('login'));
    }

    public function test_store_requires_create_permission(): void
    {
        $leadData = [
            'name' => 'João Silva',
            'email' => 'joao.silva@example.com',
            'phone' => '11999999999',
            'source' => 'site',
            'status' => 'new',
        ];

        $response = $this->actingAs($this->userWithoutPermissions)->post(route('leads.store'), $leadData);

        $response->assertForbidden();
    }

    public function test_store_creates_lead(): void
    {
        $leadData = [
            'name' => 'João Silva',
            'email' => 'joao.silva@example.com',
            'phone' => '11999999999',
            'company' => 'Empresa Teste',
            'source' => 'site',
            'status' => 'new',
        ];

        $response = $this->actingAs($this->user)->post(route('leads.store'), $leadData);

        $response->assertRedirect(route('leads.index'))
            ->assertSessionHas('status', 'Lead criado com sucesso!');

        $this->assertDatabaseHas('leads', array_merge($leadData, [
            'owner_id' => $this->user->id,
            'created_by_id' => $this->user->id,
        ]));
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post(route('leads.store'), []);

        $response->assertRedirect()
            ->assertSessionHasErrors(['name', 'source', 'status']);
    }

    public function test_store_validates_email_format(): void
    {
        $leadData = [
            'name' => 'João Silva',
            'email' => 'invalid-email',
            'phone' => '11999999999',
            'source' => 'site',
            'status' => 'new',
        ];

        $response = $this->actingAs($this->user)->post(route('leads.store'), $leadData);

        $response->assertRedirect()
            ->assertSessionHasErrors('email');
    }

    public function test_store_validates_unique_email(): void
    {
        Lead::factory()->create(['email' => 'existing@example.com']);

        $leadData = [
            'name' => 'João Silva',
            'email' => 'existing@example.com',
            'phone' => '11999999999',
            'source' => 'site',
            'status' => 'new',
        ];

        $response = $this->actingAs($this->user)->post(route('leads.store'), $leadData);

        $response->assertRedirect()
            ->assertSessionHasErrors('email');
    }

    public function test_store_validates_unique_phone(): void
    {
        Lead::factory()->create(['phone' => '11999999999']);

        $leadData = [
            'name' => 'João Silva',
            'email' => 'joao.silva@example.com',
            'phone' => '11999999999',
            'source' => 'site',
            'status' => 'new',
        ];

        $response = $this->actingAs($this->user)->post(route('leads.store'), $leadData);

        $response->assertRedirect()
            ->assertSessionHasErrors('phone');
    }

    public function test_store_validates_source_enum(): void
    {
        $leadData = [
            'name' => 'João Silva',
            'email' => 'joao.silva@example.com',
            'phone' => '11999999999',
            'source' => 'invalid_source',
            'status' => 'new',
        ];

        $response = $this->actingAs($this->user)->post(route('leads.store'), $leadData);

        $response->assertRedirect()
            ->assertSessionHasErrors('source');
    }

    public function test_store_validates_status_enum(): void
    {
        $leadData = [
            'name' => 'João Silva',
            'email' => 'joao.silva@example.com',
            'phone' => '11999999999',
            'source' => 'site',
            'status' => 'invalid_status',
        ];

        $response = $this->actingAs($this->user)->post(route('leads.store'), $leadData);

        $response->assertRedirect()
            ->assertSessionHasErrors('status');
    }

    public function test_modal_requires_authentication(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->get(route('leads.modal', $lead));

        $response->assertRedirect(route('login'));
    }

    public function test_modal_requires_view_permission(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->actingAs($this->userWithoutPermissions)->get(route('leads.modal', $lead));

        $response->assertForbidden();
    }

    public function test_modal_returns_lead_data(): void
    {
        $owner = User::factory()->create(['name' => 'Owner User']);
        $creator = User::factory()->create(['name' => 'Creator User']);
        $updater = User::factory()->create(['name' => 'Updater User']);

        $lead = Lead::factory()->create([
            'owner_id' => $owner->id,
            'created_by_id' => $creator->id,
            'updated_by_id' => $updater->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('leads.modal', $lead));

        $response->assertJsonStructure([
            'lead' => [
                'id',
                'name',
                'email',
                'phone',
                'company',
                'source',
                'status',
                'owner',
                'created_at',
                'updated_at',
                'created_by',
                'updated_by',
            ],
        ]);

        $response->assertJson([
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'source' => $lead->source->value,
                'status' => $lead->status->value,
                'owner' => ['name' => 'Owner User'],
                'created_by' => 'Creator User',
                'updated_by' => 'Updater User',
            ],
        ]);
    }

    public function test_edit_requires_authentication(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->get(route('leads.edit', $lead));

        $response->assertRedirect(route('login'));
    }

    public function test_edit_requires_update_permission(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->actingAs($this->userWithoutPermissions)->get(route('leads.edit', $lead));

        $response->assertForbidden();
    }

    public function test_edit_returns_edit_form(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->actingAs($this->user)->get(route('leads.edit', $lead));

        $response->assertInertia(function ($page) {
            $page->component('Admin/Leads/Edit')
                ->has('lead');
        });
    }

    public function test_update_requires_authentication(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->patch(route('leads.update', $lead));

        $response->assertRedirect(route('login'));
    }

    public function test_update_requires_update_permission(): void
    {
        $lead = Lead::factory()->create();
        $updateData = [
            'name' => 'Maria Santos',
            'email' => 'maria.santos@example.com',
            'phone' => '11888888888',
            'source' => 'indicacao',
            'status' => 'qualified',
        ];

        $response = $this->actingAs($this->userWithoutPermissions)->patch(route('leads.update', $lead), $updateData);

        $response->assertForbidden();
    }

    public function test_update_modifies_lead(): void
    {
        $lead = Lead::factory()->create();
        $updateData = [
            'name' => 'Maria Santos',
            'email' => 'maria.santos@example.com',
            'phone' => '11888888888',
            'company' => 'Empresa Atualizada',
            'source' => 'indicacao',
            'status' => 'qualified',
        ];

        $response = $this->actingAs($this->user)->patch(route('leads.update', $lead), $updateData);

        $response->assertRedirect(route('leads.index'))
            ->assertSessionHas('status', 'Lead atualizado com sucesso!');

        $this->assertDatabaseHas('leads', array_merge($updateData, [
            'id' => $lead->id,
            'updated_by_id' => $this->user->id,
        ]));
    }

    public function test_update_validates_required_fields(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->actingAs($this->user)->patch(route('leads.update', $lead), []);

        $response->assertRedirect()
            ->assertSessionHasErrors(['name', 'source', 'status']);
    }

    public function test_update_validates_unique_email_excluding_current(): void
    {
        $lead1 = Lead::factory()->create(['email' => 'lead1@example.com']);
        $lead2 = Lead::factory()->create(['email' => 'lead2@example.com']);

        $updateData = [
            'name' => 'Lead Atualizado',
            'email' => 'lead2@example.com', // Mesmo email do lead2
            'phone' => '11999999999',
            'source' => 'site',
            'status' => 'new',
        ];

        $response = $this->actingAs($this->user)->patch(route('leads.update', $lead1), $updateData);

        $response->assertRedirect()
            ->assertSessionHasErrors('email');
    }

    public function test_update_allows_same_email_for_same_lead(): void
    {
        $lead = Lead::factory()->create(['email' => 'lead@example.com']);

        $updateData = [
            'name' => 'Lead Atualizado',
            'email' => 'lead@example.com', // Mesmo email
            'phone' => '11999999999',
            'source' => 'site',
            'status' => 'new',
        ];

        $response = $this->actingAs($this->user)->patch(route('leads.update', $lead), $updateData);

        $response->assertRedirect(route('leads.index'));
    }

    public function test_destroy_requires_authentication(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->delete(route('leads.destroy', $lead));

        $response->assertRedirect(route('login'));
    }

    public function test_destroy_requires_delete_permission(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->actingAs($this->userWithoutPermissions)->delete(route('leads.destroy', $lead));

        $response->assertForbidden();
    }

    public function test_destroy_deletes_lead(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('leads.destroy', $lead));

        $response->assertRedirect(route('leads.index'))
            ->assertSessionHas('status', 'Lead removido com sucesso!');

        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }
}
