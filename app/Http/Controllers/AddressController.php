<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Models\Address;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function index(Client $client): JsonResponse
    {
        $this->authorize('manageAddresses', $client);

        return response()->json([
            'addresses' => $client->addresses()->orderBy('id', 'desc')->get()->map(function ($address) {
                return [
                    'id' => $address->id,
                    'description' => $address->description,
                    'postal_code' => $address->formattedPostalCode(),
                    'address' => $address->address,
                    'address_number' => $address->address_number,
                    'address_complement' => $address->address_complement,
                    'neighborhood' => $address->neighborhood,
                    'city' => $address->city,
                    'state' => $address->state,
                    'status' => $address->status,
                    'created_at' => $address->created_at?->format('d/m/Y H:i'),
                    'updated_at' => $address->updated_at?->format('d/m/Y H:i'),
                    'created_by' => $address->createdBy?->name,
                    'updated_by' => $address->updatedBy?->name,
                ];
            }),
        ]);
    }

    public function store(StoreAddressRequest $request, Client $client): JsonResponse
    {
        $this->authorize('createAddress', $client);

        $address = Address::create(array_merge($request->validated(), [
            'client_id' => $client->id,
            'created_by_id' => Auth::id(),
        ]));

        return response()->json([
            'address' => [
                'id' => $address->id,
                'description' => $address->description,
                'postal_code' => $address->formattedPostalCode(),
                'address' => $address->address,
                'address_number' => $address->address_number,
                'address_complement' => $address->address_complement,
                'neighborhood' => $address->neighborhood,
                'city' => $address->city,
                'state' => $address->state,
                'status' => $address->status,
                'created_at' => $address->created_at?->format('d/m/Y H:i'),
                'updated_at' => $address->updated_at?->format('d/m/Y H:i'),
                'created_by' => $address->createdBy?->name,
                'updated_by' => $address->updatedBy?->name,
            ],
        ], 201);
    }

    public function update(UpdateAddressRequest $request, Client $client, $addressId): JsonResponse
    {
        $this->authorize('updateAddress', $client);
        $address = $client->addresses()->findOrFail($addressId);

        $address->fill($request->validated());
        $address->updated_by_id = Auth::id();
        $address->updated_at = now();

        $address->save();

        return response()->json([
            'address' => [
                'id' => $address->id,
                'description' => $address->description,
                'postal_code' => $address->formattedPostalCode(),
                'address' => $address->address,
                'address_number' => $address->address_number,
                'address_complement' => $address->address_complement,
                'neighborhood' => $address->neighborhood,
                'city' => $address->city,
                'state' => $address->state,
                'status' => $address->status,
                'created_at' => $address->created_at?->format('d/m/Y H:i'),
                'updated_at' => $address->updated_at?->format('d/m/Y H:i'),
                'created_by' => $address->createdBy?->name,
                'updated_by' => $address->updatedBy?->name,
            ],
        ]);
    }

    public function destroy(Client $client, $addressId): JsonResponse
    {
        $this->authorize('deleteAddress', $client);
        $address = $client->addresses()->findOrFail($addressId);
        $address->delete();

        return response()->json(['message' => 'Endereço removido com sucesso.']);
    }
}
