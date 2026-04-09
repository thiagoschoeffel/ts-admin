<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlockTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('blockType'));
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('block_types', 'name')->ignore($this->route('blockType')->id),
            ],
            'raw_material_percentage' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ];
    }
}