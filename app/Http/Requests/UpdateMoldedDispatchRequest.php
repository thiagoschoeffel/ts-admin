<?php

namespace App\Http\Requests;

use App\Models\MoldedDispatch;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMoldedDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\MoldedDispatch|null $dispatch */
        $dispatch = $this->route('moldedDispatch');

        if (!$dispatch instanceof MoldedDispatch) {
            return false;
        }

        return $this->user()?->can('update', $dispatch) ?? false;
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

