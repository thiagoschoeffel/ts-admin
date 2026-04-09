<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $faker = config('seeding.faker_locale', config('app.faker_locale')) === 'pt_BR' ? fake('pt_BR') : fake();

        $product = Product::where('status', 'active')->inRandomOrder()->first() ?? Product::factory()->create(['status' => 'active']);
        $quantity = $faker->randomFloat(2, 0.5, 10);
        $unitPrice = $product->price;

        return [
            'order_id' => null, // Definir ao usar
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $quantity * $unitPrice,
        ];
    }
}
