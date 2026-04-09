<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReasonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('reasons.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason_type_id' => [
                'required',
                'integer',
                Rule::exists('reason_types', 'id')->where('status', 'active'),
            ],
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('reasons')->where('reason_type_id', $this->reason_type_id),
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['active', 'inactive']),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reason_type_id.required' => 'O tipo de motivo é obrigatório.',
            'reason_type_id.exists' => 'O tipo de motivo selecionado é inválido ou inativo.',
            'name.required' => 'O nome do motivo é obrigatório.',
            'name.min' => 'O nome deve ter pelo menos :min caracteres.',
            'name.max' => 'O nome não pode ter mais de :max caracteres.',
            'name.unique' => 'Já existe um motivo com este nome para o tipo selecionado.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'O status deve ser ativo ou inativo.',
        ];
    }
}
