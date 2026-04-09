<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlmoxarifadoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Almoxarifado::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:almoxarifados,name',
            'status' => 'required|in:active,inactive',
        ];
    }
}