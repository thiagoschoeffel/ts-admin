<?php

namespace Database\Factories;

use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    protected $model = InventoryMovement::class;

    public function definition(): array
    {
        $itemTypes = ['raw_material', 'block', 'molded'];
        $dirs = ['in', 'out', 'reserve', 'release', 'adjust'];
        $locTypes = ['silo', 'almoxarifado', 'none'];

        $itemType = $this->faker->randomElement($itemTypes);
        $data = [
            'occurred_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'item_type' => $itemType,
            'location_type' => $this->faker->randomElement($locTypes),
            'location_id' => null,
            'direction' => $this->faker->randomElement($dirs),
            'quantity' => $itemType === 'raw_material'
                ? $this->faker->randomFloat(3, 1, 500)
                : $this->faker->numberBetween(1, 500), // Inteiro para blocos e moldados
            'unit' => $itemType === 'raw_material' ? 'kg' : 'unit',
            'reference_type' => null,
            'reference_id' => null,
            'notes' => $this->faker->boolean(30) ? $this->faker->sentence(6) : null,
            'created_by' => null,
            'updated_by' => null,
        ];

        if ($itemType === 'raw_material') {
            $data['item_id'] = 1;
        } elseif ($itemType === 'molded') {
            $data['mold_type_id'] = 1; // mold_type_id para moldados
        } elseif ($itemType === 'block') {
            $data['block_type_id'] = 1;
            $data['length_mm'] = $this->faker->randomElement([400, 500, 600, 800, 1000, 1200, 1500]);
            $data['width_mm'] = $this->faker->randomElement([1000, 1020, 1200]);
            $data['height_mm'] = $this->faker->randomElement([200, 250, 300, 400]);
        }
        return $data;
    }
}
