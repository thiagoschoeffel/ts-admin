<?php

namespace Database\Seeders;

use App\Models\MoldedProductionScrap;
use App\Models\MoldedProduction;
use App\Models\Reason;
use Illuminate\Database\Seeder;

class MoldedProductionScrapSeeder extends Seeder
{
  public function run(): void
  {
    $productions = MoldedProduction::all();
    $reasons = Reason::pluck('id')->all();
    foreach ($productions as $mp) {
      $scrapCount = mt_rand(0, 3);
      for ($i = 0; $i < $scrapCount; $i++) {
        MoldedProductionScrap::create([
          'molded_production_id' => $mp->id,
          'reason_id' => $reasons[array_rand($reasons)],
          'quantity' => rand(1, 20),
        ]);
      }
    }
  }
}
