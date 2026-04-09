<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionPointingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ProductionPointing::class);
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:active,inactive',
            'sheet_number' => 'required|integer|min:1',
            'started_at' => 'required|date',
            'ended_at' => 'required|date|after_or_equal:started_at',
            'raw_material_id' => 'required|exists:raw_materials,id',
            'quantity' => 'required|numeric|min:0.01',
            'operator_ids' => 'required|array|min:1',
            'operator_ids.*' => 'integer|exists:operators,id',
            'silo_ids' => 'required|array|min:1',
            'silo_ids.*' => 'integer|exists:silos,id',
        ];
    }
}
