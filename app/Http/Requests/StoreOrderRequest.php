<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Order::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $isActive = Client::where('id', $value)->where('status', 'active')->exists();
                        if (!$isActive) {
                            Log::warning('Tentativa de criar pedido com cliente inativo', [
                                'user_id' => Auth::id(),
                                'client_id' => $value,
                            ]);
                            $fail(__('order.client_inactive_on_create'));
                        }
                    }
                },
            ],
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    $isActive = Product::where('id', $value)->where('status', 'active')->exists();
                    if (!$isActive) {
                        Log::warning('Tentativa de criar pedido com produto inativo', [
                            'user_id' => Auth::id(),
                            'product_id' => $value,
                        ]);
                        $fail(__('order.product_inactive_on_create'));
                    }
                },
            ],
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'nullable|string',
            'delivery_type' => 'nullable|in:pickup,delivery',
            'address_id' => 'nullable|exists:addresses,id',
        ];
    }
}
