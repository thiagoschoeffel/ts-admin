<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Operator::class);
    }

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
                Rule::unique('operators')->where('sector_id', $this->input('sector_id')),
            ],
            'status' => [
                'required',
                'in:active,inactive',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'sector_id' => 'setor',
            'name' => 'nome',
            'status' => 'status',
        ];
    }

    public function messages(): array
    {
        return [
            'sector_id.exists' => 'O setor selecionado não existe ou não está ativo.',
            'name.unique' => 'Já existe um operador com este nome neste setor.',
        ];
    }
}
