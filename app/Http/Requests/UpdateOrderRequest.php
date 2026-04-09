<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $order = \App\Models\Order::findOrFail($this->route('order'));
        return $this->user()->can('update', $order);
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
                'sometimes',
                'required',
                function ($attribute, $value, $fail) {
                    $order = \App\Models\Order::findOrFail($this->route('order'));
                    if ($value != $order->client_id) {
                        $isActive = Client::where('id', $value)->where('status', 'active')->exists();
                        if (!$isActive) {
                            Log::warning('Tentativa de alterar cliente do pedido para inativo', [
                                'order_id' => $order->id,
                                'user_id' => Auth::id(),
                                'current_client_id' => $order->client_id,
                                'attempted_client_id' => $value,
                            ]);
                            $fail(__('order.client_inactive_on_change'));
                        }
                    }
                },
            ],
            'status' => 'required|in:pending,confirmed,shipped,delivered,cancelled',
            'payment_method' => 'nullable|string',
            'delivery_type' => 'nullable|in:pickup,delivery',
            'address_id' => 'nullable|exists:addresses,id',
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
