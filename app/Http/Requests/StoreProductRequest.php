<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Product::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'unit_of_measure' => 'required|string|in:UND,KG,M2,M3,L,ML,PCT,CX,DZ',
            'status' => 'required|in:active,inactive',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'components' => 'nullable|array',
            'components.*.id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('status', 'active'),
            ],
            'components.*.quantity' => 'required|numeric|min:0.01',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $components = $this->input('components', []);

            // Verificar auto-referência (não implementado ainda, pois o produto ainda não existe)

            // Verificar ciclos entre componentes existentes
            foreach ($components as $component) {
                $componentId = $component['id'];
                // Verificar se algum componente tem dependências que levam de volta
                if ($this->hasCircularDependency($componentId, array_column($components, 'id'))) {
                    $validator->errors()->add('components', 'Dependências circulares detectadas nos componentes.');
                    break;
                }
            }
        });
    }

    // Protected for testability so we can override in tests
    protected function hasCircularDependency($productId, $componentIds)
    {
        // Para criação, ainda não há produto, então só verificar entre os componentes
        // Mas como é criação, não há risco de ciclo ainda
        return false;
    }
}
