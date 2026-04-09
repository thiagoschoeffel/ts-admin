<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\Client;
use App\Models\User;
use App\Models\Address;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $faker = config('seeding.faker_locale', config('app.faker_locale')) === 'pt_BR' ? fake('pt_BR') : fake();

        $deliveryType = $this->pickWeighted(config('seeding.weights.delivery_type', [
            'pickup' => 40,
            'delivery' => 60,
        ]));
        $status = $this->pickWeighted(config('seeding.weights.order_status', [
            'pending' => 35,
            'confirmed' => 25,
            'shipped' => 10,
            'delivered' => 20,
            'cancelled' => 10,
        ]));
        $paymentMethod = $this->pickWeighted(config('seeding.weights.payment_method', [
            'cash' => 30,
            'card' => 45,
            'pix' => 25,
        ]));

        return [
            'client_id' => Client::factory(),
            'user_id' => $this->existingUserId(),
            'status' => $status,
            'payment_method' => $paymentMethod,
            'delivery_type' => $deliveryType,
            'address_id' => null, // será definido se for delivery
            'total' => 0, // será calculado depois
            'notes' => $faker->optional()->paragraph(),
            'ordered_at' => $faker->dateTimeBetween('-30 days', 'now'),
            'created_by_id' => $this->existingUserId(),
            'updated_by_id' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Order $order) {
            $faker = config('seeding.faker_locale', config('app.faker_locale')) === 'pt_BR' ? fake('pt_BR') : fake();

            // Se for entrega, criar endereço para o cliente
            if ($order->delivery_type === 'delivery') {
                $address = Address::factory()->create([
                    'client_id' => $order->client_id,
                    'created_by_id' => $order->created_by_id,
                ]);
                $order->update(['address_id' => $address->id]);
            }

            // Criar itens do pedido
            $numItems = $faker->numberBetween(1, 5);
            $total = 0;

            for ($i = 0; $i < $numItems; $i++) {
                $product = Product::where('status', 'active')->inRandomOrder()->first() ?? Product::factory()->create(['status' => 'active']);
                $quantity = $faker->randomFloat(2, 0.5, 10);
                $unitPrice = $product->price;
                $itemTotal = $quantity * $unitPrice;

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $itemTotal,
                ]);

                $total += $itemTotal;
            }

            // Atualizar total do pedido
            $order->update(['total' => $total]);
        });
    }

    private function pickWeighted(array $weights): string
    {
        $total = array_sum($weights);
        if ($total <= 0) {
            return array_key_first($weights);
        }
        $rand = mt_rand(1, (int) $total);
        $running = 0;
        foreach ($weights as $key => $weight) {
            $running += (int) $weight;
            if ($rand <= $running) {
                return (string) $key;
            }
        }
        return (string) array_key_first($weights);
    }

    private function existingUserId(): int
    {
        $ids = User::query()
            ->whereIn('email', ['admin@example.com', 'user@example.com'])
            ->pluck('id')
            ->all();

        if (!empty($ids)) {
            return Arr::random($ids);
        }

        return (int) (User::query()->inRandomOrder()->value('id') ?? 1);
    }
}
