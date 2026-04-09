<?php

namespace Database\Factories;

use App\Models\MoldedProductionScrap;
use App\Models\MoldedProduction;
use App\Models\Reason;
use Illuminate\Database\Eloquent\Factories\Factory;

class MoldedProductionScrapFactory extends Factory
{
  protected $model = MoldedProductionScrap::class;

  public function definition()
  {
    return [
      'molded_production_id' => MoldedProduction::factory(),
      'reason_id' => Reason::factory(),
      'quantity' => $this->faker->numberBetween(1, 20),
    ];
  }
}
