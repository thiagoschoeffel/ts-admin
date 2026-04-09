<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreBlockDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', \App\Models\BlockDispatch::class);
    }

    public function rules(): array
    {
        return [
            'dispatched_at' => ['required', 'date'],
            'manufacturing_order_number' => ['required', 'string', 'max:60'],
            'production_pointing_id' => ['required', 'integer', 'exists:production_pointings,id'],
            'block_production_ids' => ['required', 'array', 'min:1'],
            'block_production_ids.*' => ['required', 'integer', 'distinct', 'exists:block_productions,id'],
        ];
    }
}
