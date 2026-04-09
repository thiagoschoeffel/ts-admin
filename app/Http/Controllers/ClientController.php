<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use DomainException;

class ClientController extends Controller
{
    public function index(Request $request): \Inertia\Response|\Illuminate\Http\RedirectResponse
    {
        $this->authorize('viewAny', Client::class);

        $query = Client::query();

        if ($search = $request->string('search')->toString()) {
            $digits = preg_replace('/\D+/', '', $search);

            $query->where(function ($inner) use ($search, $digits): void {
                $inner->where('name', 'like', "%{$search}%");

                if ($digits) {
                    $inner->orWhere('document', 'like', "%{$digits}%");
                }
            });
        }

        if ($personType = $request->get('person_type')) {
            $query->where('person_type', $personType);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Resolve per_page
        $allowedPerPage = [10, 25, 50, 100];
        $perPageCandidate = (int) $request->integer('per_page');
        $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

        $clients = $query
            ->with(['createdBy', 'updatedBy'])
            ->orderBy('name', 'asc')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Client $client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'person_type' => $client->person_type,
                    'status' => $client->status,
                    'document' => $client->document,
                    'formatted_document' => $client->formattedDocument(),
                    'created_at' => optional($client->created_at)->format('d/m/Y H:i'),
                ];
            });

        // If requested page exceeds last page, redirect to last valid
        $requestedPage = max(1, (int) $request->query('page', 1));
        if ($requestedPage > $clients->lastPage() && $clients->lastPage() > 0) {
            $queryParams = $request->query();
            $queryParams['page'] = $clients->lastPage();
            return redirect()->to($request->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/Clients/Index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
                'person_type' => $request->get('person_type'),
                'status' => $request->get('status'),
            ],
            'clients' => $clients,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Client::class);

        return Inertia::render('Admin/Clients/Create', [
            'states' => [
                'AC',
                'AL',
                'AP',
                'AM',
                'BA',
                'CE',
                'DF',
                'ES',
                'GO',
                'MA',
                'MT',
                'MS',
                'MG',
                'PA',
                'PB',
                'PR',
                'PE',
                'PI',
                'RJ',
                'RN',
                'RS',
                'RO',
                'RR',
                'SC',
                'SP',
                'SE',
                'TO'
            ],
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $data = $this->preparePayload($request->validated());
        $addresses = $data['addresses'] ?? [];
        unset($data['addresses']);

        $client = Client::create(array_merge($data, [
            'created_by_id' => Auth::id(),
        ]));

        // Criar endereços
        foreach ($addresses as $addressData) {
            $client->addresses()->create(array_merge($addressData, [
                'created_by_id' => Auth::id(),
            ]));
        }

        return redirect()
            ->route('clients.index')
            ->with('status', 'Cliente cadastrado com sucesso.');
    }

    public function modal(Client $client): JsonResponse
    {
        $this->authorize('view', $client);
        $client->load(['createdBy', 'updatedBy']);
        $addresses = $client->addresses()->orderBy('id', 'desc')->get();

        return response()->json([
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'person_type' => $client->person_type,
                'document' => $client->formattedDocument(),
                'observations' => $client->observations,
                'contact_name' => $client->contact_name,
                'contact_phone_primary' => $client->formattedPhone($client->contact_phone_primary),
                'contact_phone_secondary' => $client->formattedPhone($client->contact_phone_secondary),
                'contact_email' => $client->contact_email,
                'status' => $client->status,
                'created_at' => $client->created_at?->format('d/m/Y H:i'),
                'updated_at' => $client->updated_at?->format('d/m/Y H:i'),
                'created_by' => $client->createdBy?->name,
                'updated_by' => $client->updatedBy?->name,
                'addresses' => $addresses->map(function ($address) {
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
            ],
        ]);
    }

    public function edit(Client $client): Response
    {
        $this->authorize('update', $client);
        $client->load('addresses');

        return Inertia::render('Admin/Clients/Edit', [
            'states' => [
                'AC',
                'AL',
                'AP',
                'AM',
                'BA',
                'CE',
                'DF',
                'ES',
                'GO',
                'MA',
                'MT',
                'MS',
                'MG',
                'PA',
                'PB',
                'PR',
                'PE',
                'PI',
                'RJ',
                'RN',
                'RS',
                'RO',
                'RR',
                'SC',
                'SP',
                'SE',
                'TO'
            ],
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'person_type' => $client->person_type,
                'document' => $client->formattedDocument(),
                'observations' => $client->observations,
                'contact_name' => $client->contact_name,
                'contact_phone_primary' => $client->formattedPhone($client->contact_phone_primary),
                'contact_phone_secondary' => $client->formattedPhone($client->contact_phone_secondary),
                'contact_email' => $client->contact_email,
                'status' => $client->status,
                'addresses' => $client->addresses->map(function ($address) {
                    return [
                        'id' => $address->id,
                        'description' => $address->description,
                        'postal_code' => $address->postal_code,
                        'address' => $address->address,
                        'address_number' => $address->address_number,
                        'address_complement' => $address->address_complement,
                        'neighborhood' => $address->neighborhood,
                        'city' => $address->city,
                        'state' => $address->state,
                        'status' => $address->status,
                    ];
                })->toArray(),
            ],
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $data = $this->preparePayload($request->validated());
        unset($data['addresses']);

        $client->fill($data);
        $client->updated_by_id = Auth::id();
        $client->save();

        // Na edição, não manipula endereços. Eles são gerenciados separadamente.

        return redirect()
            ->route('clients.index')
            ->with('status', 'Cliente atualizado com sucesso.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        try {
            $this->authorize('delete', $client);
            $client->delete();

            return redirect()
                ->route('clients.index')
                ->with('status', 'Cliente removido com sucesso.');
        } catch (AuthorizationException $e) {
            $message = $e->getMessage();
            if ($message === __('client.delete_blocked_has_orders')) {
                Log::warning('Tentativa de exclusão de cliente com pedidos bloqueada', [
                    'client_id' => $client->id,
                    'user_id' => Auth::id(),
                    'message' => $message,
                ]);
                return back()->with('error', $message);
            }
            abort(403, $message);
        } catch (DomainException $e) {
            Log::warning('Tentativa de exclusão de cliente com pedidos bloqueada (Observer)', [
                'client_id' => $client->id,
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
            ]);
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Erro ao excluir cliente', [
                'client_id' => $client->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Erro interno ao excluir cliente.');
        }
    }

    protected function preparePayload(array $data): array
    {
        $data['contact_email'] = isset($data['contact_email']) && $data['contact_email'] !== ''
            ? strtolower($data['contact_email'])
            : null;

        // For individual persons, clear only contact_name
        if ($data['person_type'] === 'individual') {
            $data['contact_name'] = null;
        }

        return $data;
    }
}
