<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreProductComponentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Resolve product from route parameter safely (avoid collisions with input keys)
        $route = $this->route();
        $param = $route ? $route->parameter('product') : null;

        if ($param instanceof \App\Models\Product) {
            $product = $param;
        } elseif (is_scalar($param) && ctype_digit((string)$param)) {
            $product = \App\Models\Product::find((int)$param);
        } else {
            return false;
        }

        return $this->user()?->can('createComponent', $product) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'component_id' => [
                'required',
                Rule::exists('products', 'id')->where('status', 'active'),
            ],
            'quantity' => 'required|numeric|min:0.01',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('component_id')) {
                $componentId = $this->component_id;
                $productId = $this->route('product')->id;
                \Illuminate\Support\Facades\Log::warning('Tentativa de adicionar componente inativo via API', [
                    'product_id' => $productId,
                    'component_product_id' => $componentId,
                    'user_id' => Auth::id(),
                ]);
            }
        });
    }
}
