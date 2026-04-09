<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LeadController extends Controller
{
    public function index(): InertiaResponse|\Illuminate\Http\Response
    {
        $this->authorize('viewAny', Lead::class);
        $allowedPerPage = [10, 25, 50, 100];
        $perPageCandidate = (int) request()->integer('per_page');
        $perPage = in_array($perPageCandidate, $allowedPerPage, true) ? $perPageCandidate : 10;

        $leads = Lead::with('owner')->withCount('interactions')->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        $requestedPage = max(1, (int) request()->query('page', 1));
        if ($requestedPage > $leads->lastPage() && $leads->lastPage() > 0) {
            $queryParams = request()->query();
            $queryParams['page'] = $leads->lastPage();
            return Inertia::location(request()->url() . '?' . http_build_query($queryParams));
        }

        return Inertia::render('Admin/Leads/Index', [
            'leads' => $leads,
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', Lead::class);
        return Inertia::render('Admin/Leads/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Lead::class);
        $data = $request->validate([
            'name' => 'required|string|max:180',
            'email' => 'nullable|email|max:180|unique:leads,email',
            'phone' => 'nullable|string|max:30|unique:leads,phone',
            'company' => 'nullable|string|max:180',
            'source' => 'required|in:site,indicacao,evento,manual',
            'status' => 'required|in:new,in_contact,qualified,discarded',
            'interactions' => 'array',
            'interactions.*.type' => 'required|in:phone_call,email,meeting,whatsapp,visit,other',
            'interactions.*.interacted_at' => 'required|date',
            'interactions.*.description' => 'required|string|max:1000',
        ]);
        $data['owner_id'] = Auth::id();
        $lead = Lead::create($data);
        $lead->created_by_id = Auth::id();
        $lead->save();

        // Criar interações se fornecidas
        if (!empty($data['interactions'])) {
            foreach ($data['interactions'] as $interactionData) {
                $lead->interactions()->create(array_merge($interactionData, [
                    'created_by_id' => Auth::id(),
                ]));
            }
        }

        return redirect()->route('leads.index')->with('status', 'Lead criado com sucesso!');
    }

    public function modal(Lead $lead): JsonResponse
    {
        $this->authorize('view', $lead);
        $lead->load(['owner', 'createdBy', 'updatedBy', 'interactions.createdBy']);

        return response()->json([
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'source' => $lead->source,
                'status' => $lead->status,
                'owner' => $lead->owner ? ['name' => $lead->owner->name] : null,
                'interactions' => $lead->interactions->map(function ($interaction) {
                    return [
                        'id' => $interaction->id,
                        'type' => $interaction->type,
                        'type_label' => $interaction->type_label,
                        'interacted_at' => $interaction->interacted_at?->format('Y-m-d\TH:i'),
                        'description' => $interaction->description,
                        'created_by' => $interaction->createdBy?->name,
                    ];
                }),
                'created_at' => $lead->created_at?->format('d/m/Y H:i'),
                'updated_at' => $lead->updated_at?->format('d/m/Y H:i'),
                'created_by' => $lead->createdBy?->name,
                'updated_by' => $lead->updatedBy?->name,
            ],
        ]);
    }

    public function edit(Lead $lead): InertiaResponse
    {
        $this->authorize('update', $lead);
        $lead->load('interactions.createdBy');
        return Inertia::render('Admin/Leads/Edit', [
            'lead' => array_merge($lead->toArray(), [
                'interactions' => $lead->interactions->map(function ($interaction) {
                    return [
                        'id' => $interaction->id,
                        'type' => $interaction->type,
                        'type_label' => $interaction->type_label,
                        'interacted_at' => $interaction->interacted_at?->format('Y-m-d\TH:i'),
                        'description' => $interaction->description,
                        'created_by' => $interaction->createdBy?->name,
                    ];
                }),
            ]),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);
        $data = $request->validate([
            'name' => 'required|string|max:180',
            'email' => 'nullable|email|max:180|unique:leads,email,' . $lead->id,
            'phone' => 'nullable|string|max:30|unique:leads,phone,' . $lead->id,
            'company' => 'nullable|string|max:180',
            'source' => 'required|in:site,indicacao,evento,manual',
            'status' => 'required|in:new,in_contact,qualified,discarded',
            'interactions' => 'array',
            'interactions.*.type' => 'required|in:phone_call,email,meeting,whatsapp,visit,other',
            'interactions.*.interacted_at' => 'required|date',
            'interactions.*.description' => 'required|string|max:1000',
        ]);
        $lead->update($data);
        $lead->updated_by_id = Auth::id();
        $lead->save();

        // Sincronizar interações
        if (isset($data['interactions'])) {
            // Remover interações existentes que não estão mais na lista
            $existingIds = collect($data['interactions'])->pluck('id')->filter()->values();
            $lead->interactions()->whereNotIn('id', $existingIds)->delete();

            // Atualizar ou criar interações
            foreach ($data['interactions'] as $interactionData) {
                if (isset($interactionData['id'])) {
                    // Atualizar interação existente
                    $interaction = $lead->interactions()->find($interactionData['id']);
                    if ($interaction) {
                        $interaction->update($interactionData);
                    }
                } else {
                    // Criar nova interação
                    $lead->interactions()->create(array_merge($interactionData, [
                        'created_by_id' => Auth::id(),
                    ]));
                }
            }
        }

        return redirect()->route('leads.index')->with('status', 'Lead atualizado com sucesso!');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $this->authorize('delete', $lead);
        $lead->delete();
        return redirect()->route('leads.index')->with('status', 'Lead removido com sucesso!');
    }
}
