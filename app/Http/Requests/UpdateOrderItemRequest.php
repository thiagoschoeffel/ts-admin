<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UpdateOrderItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $order = \App\Models\Order::findOrFail($this->route('order'));
        return $this->user()->can('updateItem', $order);
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
                'sometimes',
                function ($attribute, $value, $fail) {
                    $item = \App\Models\OrderItem::findOrFail($this->route('item'));
                    if ($value == $item->product_id) {
                        return; // mantendo o mesmo produto
                    }
                    $isActive = Product::where('id', $value)->where('status', 'active')->exists();
                    if (!$isActive) {
                        Log::warning('Tentativa de alterar produto do item para inativo', [
                            'order_id' => $this->route('order'),
                            'item_id' => $this->route('item'),
                            'user_id' => Auth::id(),
                            'current_product_id' => $item->product_id,
                            'attempted_product_id' => $value,
                        ]);
                        $fail(__('order.product_inactive_on_change'));
                    }
                },
            ],
            'quantity' => 'required|numeric|min:0.01|max:99999.99',
        ];
    }
}
