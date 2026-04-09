<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeadInteractionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leads = \App\Models\Lead::all();
        $userIds = \App\Models\User::pluck('id')->toArray();

        if ($leads->isEmpty() || empty($userIds)) {
            return;
        }

        $range = config('seeding.volumes.lead_interactions_per_lead', [0, 5]);
        foreach ($leads as $lead) {
            $interactionCount = fake()->numberBetween((int) $range[0], (int) $range[1]);
            \App\Models\LeadInteraction::factory()
                ->count($interactionCount)
                ->create([
                    'lead_id' => $lead->id,
                ]);
        }
    }
}
