<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMachineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Machine::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sector_id' => [
                'required',
                'exists:sectors,id',
                Rule::exists('sectors', 'id')->where('status', 'active'),
            ],
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('machines')->where('sector_id', $this->input('sector_id')),
            ],
            'status' => [
                'required',
                'in:active,inactive',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'sector_id' => 'setor',
            'name' => 'nome',
            'status' => 'status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'sector_id.exists' => 'O setor selecionado não existe ou não está ativo.',
            'name.unique' => 'Já existe uma máquina com este nome neste setor.',
        ];
    }
}
