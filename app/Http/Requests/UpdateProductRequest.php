<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ProductComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UpdateProductRequest extends FormRequest
{
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = redirect()->route('products.index')
            ->withErrors($validator)
            ->withInput();
        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
    public function authorize(): bool
    {
        $routeParam = $this->route('product');
        $product = $routeParam instanceof \App\Models\Product
            ? $routeParam
            : \App\Models\Product::findOrFail($routeParam);

        return $this->user()->can('update', $product);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'unit_of_measure' => 'required|string|in:UND,KG,M2,M3,L,ML,PCT,CX,DZ',
            'status' => 'in:active,inactive',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'components' => 'array',
            'components.*.pivot_id' => 'nullable|exists:product_components,id',
            'components.*.id' => 'required|exists:products,id',
            'components.*.quantity' => 'required|numeric|min:0.01',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $components = $this->input('components', []);
            $productId = $this->route('product')->id;

            foreach ($components as $index => $component) {
                $componentId = $component['id'];
                $pivotId = $component['pivot_id'] ?? null;

                // Verificar auto-referência
                if ($componentId == $productId) {
                    $validator->errors()->add("components.{$index}.id", 'Um produto não pode ser componente de si mesmo.');
                    continue;
                }

                if ($pivotId) {
                    // Item existente
                    $existing = ProductComponent::find($pivotId);
                    if ($existing) {
                        if ((string)$existing->component_id === (string)$componentId) {
                            // Mantendo o mesmo componente, não precisa validar ativo
                            continue;
                        } else {
                            // Componente mudou, verificar se ativo
                            $isActive = \App\Models\Product::where('id', $componentId)->where('status', 'active')->exists();
                            if (!$isActive) {
                                $validator->errors()->add("components.{$index}.id", __('product.component_inactive_on_change'));
                                Log::warning('Tentativa de trocar componente para inativo', [
                                    'product_id' => $productId,
                                    'component_product_id' => $componentId,
                                    'user_id' => Auth::id(),
                                ]);
                            }
                        }
                    }
                } else {
                    // Novo item, verificar ativo
                    $isActive = \App\Models\Product::where('id', $componentId)->where('status', 'active')->exists();
                    if (!$isActive) {
                        $validator->errors()->add("components.{$index}.id", __('product.component_inactive_on_create'));
                        Log::warning('Tentativa de adicionar componente inativo', [
                            'product_id' => $productId,
                            'component_product_id' => $componentId,
                            'user_id' => Auth::id(),
                        ]);
                    }
                }
                // Verificar ciclos (simplificado)
                // ...
            }
        });
    }
}
