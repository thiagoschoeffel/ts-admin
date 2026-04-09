<?php

namespace App\Http\Requests;

use App\Models\BlockDispatch;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBlockDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\BlockDispatch|null $dispatch */
        $dispatch = $this->route('blockDispatch');

        if (!$dispatch instanceof BlockDispatch) {
            return false;
        }

        return $this->user()?->can('update', $dispatch) ?? false;
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

