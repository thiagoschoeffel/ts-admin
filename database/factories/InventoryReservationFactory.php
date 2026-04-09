<?php

namespace Database\Factories;

use App\Models\InventoryReservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\InventoryReservation>
 */
class InventoryReservationFactory extends Factory
{
    protected $model = InventoryReservation::class;

    public function definition(): array
    {
        $reserved = $this->faker->randomFloat(3, 100, 2000);
        $consumed = $this->faker->boolean(70)
            ? $this->faker->randomFloat(3, 0, $reserved)
            : 0.0;
        $status = $consumed >= $reserved ? 'closed' : 'active';

        return [
            'production_pointing_id' => 1,
            'raw_material_id' => 1,
            'reserved_kg' => $reserved,
            'consumed_kg' => $consumed,
            'status' => $status,
        ];
    }
}

