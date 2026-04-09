<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeadInteraction>
 */
class LeadInteractionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = config('seeding.faker_locale', config('app.faker_locale')) === 'pt_BR' ? fake('pt_BR') : fake();
        return [
            'type' => $faker->randomElement(['phone_call', 'email', 'meeting', 'message', 'visit', 'other']),
            'interacted_at' => $faker->dateTimeBetween('-30 days', 'now'),
            'description' => $faker->sentence(),
            'created_by_id' => $this->existingUserId(),
        ];
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
