<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlockTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\BlockType::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:block_types,name',
            'raw_material_percentage' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ];
    }
}