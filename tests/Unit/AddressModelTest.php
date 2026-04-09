<?php

namespace Tests\Unit;

use App\Models\Address;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_address_model_can_be_created_with_valid_data()
    {
        $client = Client::factory()->create();
        $user = User::factory()->create();
        $addressData = [
            'client_id' => $client->id,
            'postal_code' => '12345678',
            'address' => 'Street Name',
            'address_number' => '123',
            'address_complement' => 'Apt 4',
            'neighborhood' => 'Neighborhood',
            'city' => 'City',
            'state' => 'State',
            'description' => 'Description',
            'status' => 'active',
            'created_by_id' => $user->id,
            'updated_by_id' => $user->id,
        ];

        $address = Address::create($addressData);

        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals($client->id, $address->client_id);
        $this->assertEquals('12345678', $address->postal_code);
        $this->assertEquals('active', $address->status);
    }

    public function test_address_fillable_attributes_are_correct()
    {
        $fillable = [
            'client_id',
            'postal_code',
            'address',
            'address_number',
            'address_complement',
            'neighborhood',
            'city',
            'state',
            'description',
            'status',
            'created_by_id',
            'updated_by_id',
        ];
        $this->assertEquals($fillable, (new Address)->getFillable());
    }

    public function test_address_casts_are_correct()
    {
        $casts = [
            'id' => 'int',
            'status' => 'string',
        ];

        $this->assertEquals($casts, (new Address)->getCasts());
    }

    public function test_address_postal_code_attribute_cleans_digits_on_set()
    {
        $address = new Address;
        $address->postal_code = '12345-678';
        $this->assertEquals('12345678', $address->getAttributes()['postal_code']);
    }

    public function test_address_client_relationship()
    {
        $client = Client::factory()->create();
        $address = Address::factory()->create(['client_id' => $client->id]);

        $this->assertInstanceOf(Client::class, $address->client);
        $this->assertEquals($client->id, $address->client->id);
    }

    public function test_address_created_by_relationship()
    {
        $client = Client::factory()->create();
        $user = User::factory()->create();
        $address = Address::factory()->create(['client_id' => $client->id, 'created_by_id' => $user->id]);

        $this->assertInstanceOf(User::class, $address->createdBy);
        $this->assertEquals($user->id, $address->createdBy->id);
    }

    public function test_address_updated_by_relationship()
    {
        $client = Client::factory()->create();
        $user = User::factory()->create();
        $address = Address::factory()->create(['client_id' => $client->id, 'updated_by_id' => $user->id]);

        $this->assertInstanceOf(User::class, $address->updatedBy);
        $this->assertEquals($user->id, $address->updatedBy->id);
    }

    public function test_address_formatted_postal_code()
    {
        $client = Client::factory()->create();
        $address = Address::factory()->create(['client_id' => $client->id, 'postal_code' => '12345678']);
        $this->assertEquals('12345-678', $address->formattedPostalCode());
    }

    public function test_address_formatted_postal_code_returns_null_for_null_postal_code()
    {
        $address = new Address;
        $address->postal_code = null;
        $this->assertNull($address->formattedPostalCode());
    }
}
