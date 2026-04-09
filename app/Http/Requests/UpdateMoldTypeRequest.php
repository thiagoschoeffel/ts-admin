<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMoldTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('moldType'));
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mold_types', 'name')->ignore($this->route('moldType')->id),
            ],
            'pieces_per_package' => 'required|numeric|min:0.01',
            'status' => 'required|in:active,inactive',
        ];
    }
}