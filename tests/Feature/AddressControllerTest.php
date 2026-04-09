<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);
    }

    public function test_index_requires_authentication(): void
    {
        $client = Client::factory()->create();
        $response = $this->get(route('clients.addresses.index', $client));
        $response->assertRedirect(route('login'));
    }

    public function test_index_returns_addresses_list(): void
    {
        $client = Client::factory()->create();
        Address::factory()->count(2)->create(['client_id' => $client->id]);
        $response = $this->actingAs($this->user)->get(route('clients.addresses.index', $client));
        $response->assertStatus(200);
    }

    public function test_store_creates_address(): void
    {
        $client = Client::factory()->create();
        $addressData = [
            'description' => 'Casa',
            'postal_code' => '12345678',
            'address' => 'Rua Teste',
            'address_number' => '10',
            'neighborhood' => 'Centro',
            'city' => 'Cidade',
            'state' => 'SP',
            'status' => 'active',
        ];
        $response = $this->actingAs($this->user)->post(route('clients.addresses.store', $client), $addressData);
        $response->assertStatus(201);
        $this->assertDatabaseHas('addresses', [
            'description' => 'Casa',
            'city' => 'Cidade',
        ]);
    }

    public function test_update_modifies_address(): void
    {
        $client = Client::factory()->create();
        $address = Address::factory()->create(['client_id' => $client->id]);
        $updateData = [
            'description' => 'Trabalho',
            // Use a valid 8-digit postal code to satisfy validation
            'postal_code' => '87654321',
            'address' => $address->address,
            'address_number' => $address->address_number,
            'neighborhood' => $address->neighborhood,
            'city' => 'Nova Cidade',
            'state' => $address->state,
            'status' => $address->status,
        ];
        $response = $this->actingAs($this->user)->patch(
            route('clients.addresses.update', ['client' => $client, 'addressId' => $address->id]),
            $updateData
        );
        $response->assertStatus(200);
        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'description' => 'Trabalho',
            'city' => 'Nova Cidade',
        ]);
    }

    public function test_destroy_deletes_address(): void
    {
        $client = Client::factory()->create();
        $address = Address::factory()->create(['client_id' => $client->id]);
        $response = $this->actingAs($this->user)->delete(
            route('clients.addresses.destroy', ['client' => $client, 'addressId' => $address->id])
        );
        $response->assertStatus(200);
        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }
}
