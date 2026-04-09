<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_model_can_be_created_with_valid_data()
    {
        $user = User::factory()->create();
        $clientData = [
            'name' => 'Client Name',
            'person_type' => 'individual',
            'document' => '12345678901',
            'observations' => 'Some observations',
            'contact_name' => 'Contact Name',
            'contact_phone_primary' => '11987654321',
            'contact_phone_secondary' => '11876543210',
            'contact_email' => 'contact@example.com',
            'status' => 'active',
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ];

        $client = Client::create($clientData);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('Client Name', $client->name);
        $this->assertEquals('individual', $client->person_type);
        $this->assertEquals('12345678901', $client->document);
        $this->assertEquals('active', $client->status);
    }

    public function test_client_fillable_attributes_are_correct()
    {
        $fillable = [
            'name',
            'person_type',
            'document',
            'observations',
            'contact_name',
            'contact_phone_primary',
            'contact_phone_secondary',
            'contact_email',
            'status',
            'created_by_id',
            'updated_by_id',
        ];
        $this->assertEquals($fillable, (new Client)->getFillable());
    }

    public function test_client_casts_are_correct()
    {
        $casts = [
            'id' => 'int',
            'person_type' => 'string',
            'status' => 'string',
        ];

        $this->assertEquals($casts, (new Client)->getCasts());
    }

    public function test_client_document_attribute_cleans_digits_on_set()
    {
        $client = new Client;
        $client->document = '123.456.789-01';
        $this->assertEquals('12345678901', $client->getAttributes()['document']);
    }

    public function test_client_contact_phone_primary_cleans_digits_on_set()
    {
        $client = new Client;
        $client->contact_phone_primary = '(11) 98765-4321';
        $this->assertEquals('11987654321', $client->getAttributes()['contact_phone_primary']);
    }

    public function test_client_contact_phone_secondary_cleans_digits_on_set()
    {
        $client = new Client;
        $client->contact_phone_secondary = '(11) 8765-4321';
        $this->assertEquals('1187654321', $client->getAttributes()['contact_phone_secondary']);
    }

    public function test_client_created_by_relationship()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['created_by_id' => $user->id]);

        $this->assertInstanceOf(User::class, $client->createdBy);
        $this->assertEquals($user->id, $client->createdBy->id);
    }

    public function test_client_updated_by_relationship()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['updated_by_id' => $user->id]);

        $this->assertInstanceOf(User::class, $client->updatedBy);
        $this->assertEquals($user->id, $client->updatedBy->id);
    }

    public function test_client_addresses_relationship()
    {
        $client = Client::factory()->create();
        $user = User::factory()->create();
        $address = $client->addresses()->create([
            'postal_code' => '12345678',
            'address' => 'Street Name',
            'address_number' => '123',
            'neighborhood' => 'Neighborhood',
            'city' => 'City',
            'state' => 'State',
            'status' => 'active',
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->addresses());
        $this->assertTrue($client->addresses->contains($address));
    }

    public function test_client_formatted_document_for_individual()
    {
        $client = Client::factory()->create([
            'person_type' => 'individual',
            'document' => '12345678901'
        ]);

        $this->assertEquals('123.456.789-01', $client->formattedDocument());
    }

    public function test_client_formatted_document_for_company()
    {
        $client = Client::factory()->create([
            'person_type' => 'company',
            'document' => '12345678000123'
        ]);

        $this->assertEquals('12.345.678/0001-23', $client->formattedDocument());
    }

    public function test_client_formatted_phone_for_11_digits()
    {
        $client = new Client;
        $formatted = $client->formattedPhone('11987654321');
        $this->assertEquals('(11) 98765-4321', $formatted);
    }

    public function test_client_formatted_phone_for_10_digits()
    {
        $client = new Client;
        $formatted = $client->formattedPhone('1187654321');
        $this->assertEquals('(11) 8765-4321', $formatted);
    }

    public function test_client_formatted_phone_returns_null_for_null_input()
    {
        $client = new Client;
        $formatted = $client->formattedPhone(null);
        $this->assertNull($formatted);
    }

    public function test_client_formatted_phone_returns_input_for_other_lengths()
    {
        $client = new Client;
        $formatted = $client->formattedPhone('123');
        $this->assertEquals('123', $formatted);
    }
}
