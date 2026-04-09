<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreMoldedDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', \App\Models\MoldedDispatch::class);
    }

    public function rules(): array
    {
        return [
            'dispatched_at' => ['required', 'date'],
            'manufacturing_order_number' => ['required', 'string', 'max:60'],
            'mold_type_id' => ['required', 'integer', 'exists:mold_types,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}

