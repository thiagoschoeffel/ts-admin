<?php

namespace Database\Factories;

use App\Models\MoldType;
use App\Models\MoldedDispatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MoldedDispatch>
 */
class MoldedDispatchFactory extends Factory
{
    protected $model = MoldedDispatch::class;

    public function definition(): array
    {
        return [
            'dispatched_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'manufacturing_order_number' => 'OF-' . $this->faker->bothify('#####'),
            'mold_type_id' => MoldType::factory(),
            'quantity' => $this->faker->numberBetween(1, 500),
            'created_by_id' => null,
            'updated_by_id' => null,
        ];
    }
}

