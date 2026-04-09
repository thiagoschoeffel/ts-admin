<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class StoreOrderItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $order = \App\Models\Order::findOrFail($this->route('order'));
        return $this->user()->can('addItem', $order);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    $isActive = Product::where('id', $value)->where('status', 'active')->exists();
                    if (!$isActive) {
                        Log::warning('Tentativa de adicionar item com produto inativo ao pedido', [
                            'order_id' => $this->route('order'),
                            'user_id' => Auth::id(),
                            'product_id' => $value,
                        ]);
                        $fail(__('order.product_inactive_on_create'));
                    }
                },
            ],
            'quantity' => 'required|numeric|min:0.01|max:99999.99',
        ];
    }
}
