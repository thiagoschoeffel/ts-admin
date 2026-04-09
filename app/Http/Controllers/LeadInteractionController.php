<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadInteractionRequest;
use App\Http\Requests\UpdateLeadInteractionRequest;
use App\Models\Lead;
use App\Models\LeadInteraction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LeadInteractionController extends Controller
{
    public function index(Lead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        return response()->json([
            'interactions' => $lead->interactions()->with('createdBy')->get()->map(function ($interaction) {
                return [
                    'id' => $interaction->id,
                    'type' => $interaction->type,
                    'type_label' => $interaction->type_label,
                    'interacted_at' => $interaction->interacted_at?->format('Y-m-d\TH:i'),
                    'description' => $interaction->description,
                    'created_at' => $interaction->created_at?->format('d/m/Y H:i'),
                    'created_by' => $interaction->createdBy?->name,
                ];
            }),
        ]);
    }

    public function store(StoreLeadInteractionRequest $request, Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $interaction = LeadInteraction::create(array_merge($request->validated(), [
            'lead_id' => $lead->id,
            'created_by_id' => Auth::id(),
        ]));

        return response()->json([
            'interaction' => [
                'id' => $interaction->id,
                'type' => $interaction->type,
                'type_label' => $interaction->type_label,
                'interacted_at' => $interaction->interacted_at?->format('Y-m-d\TH:i'),
                'description' => $interaction->description,
                'created_at' => $interaction->created_at?->format('d/m/Y H:i'),
                'created_by' => $interaction->createdBy?->name,
            ],
        ], 201);
    }

    public function update(UpdateLeadInteractionRequest $request, Lead $lead, $interactionId): JsonResponse
    {
        $this->authorize('update', $lead);
        $interaction = $lead->interactions()->findOrFail($interactionId);

        $interaction->fill($request->validated());
        $interaction->save();

        return response()->json([
            'interaction' => [
                'id' => $interaction->id,
                'type' => $interaction->type,
                'type_label' => $interaction->type_label,
                'interacted_at' => $interaction->interacted_at?->format('Y-m-d\TH:i'),
                'description' => $interaction->description,
                'created_at' => $interaction->created_at?->format('d/m/Y H:i'),
                'created_by' => $interaction->createdBy?->name,
            ],
        ]);
    }

    public function destroy(Lead $lead, $interactionId): JsonResponse
    {
        $this->authorize('update', $lead);
        $interaction = $lead->interactions()->findOrFail($interactionId);
        $interaction->delete();

        return response()->json(['message' => 'Interação removida com sucesso.']);
    }
}
