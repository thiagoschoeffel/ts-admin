<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        $this->faker->unique(true);
        $faker = config('seeding.faker_locale', config('app.faker_locale')) === 'pt_BR' ? fake('pt_BR') : $this->faker;

        $sourceWeights = config('seeding.weights.lead_source', [
            'site' => 35,
            'indicacao' => 35,
            'evento' => 15,
            'manual' => 15,
        ]);
        $statusWeights = config('seeding.weights.lead_status', [
            'new' => 40,
            'in_contact' => 30,
            'qualified' => 20,
            'discarded' => 10,
        ]);

        $sourceValue = $this->pickWeighted($sourceWeights);
        $statusValue = $this->pickWeighted($statusWeights);
        $source = collect(LeadSource::cases())->firstWhere('value', $sourceValue) ?? LeadSource::MANUAL;
        $status = collect(LeadStatus::cases())->firstWhere('value', $statusValue) ?? LeadStatus::NOVO;

        return [
            'name' => $faker->name(),
            'email' => $faker->unique()->safeEmail(),
            'phone' => $faker->phoneNumber(),
            'company' => $faker->optional()->company(),
            'source' => $source,
            'status' => $status,
            'owner_id' => $this->existingUserId(),
            'created_by_id' => $this->existingUserId(),
            'updated_by_id' => $this->existingUserId(),
        ];
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
