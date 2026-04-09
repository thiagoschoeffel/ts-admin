<?php

namespace Database\Factories;

use App\Models\Machine;
use App\Models\MachineDowntime;
use App\Models\Reason;
use Illuminate\Database\Eloquent\Factories\Factory;

class MachineDowntimeFactory extends Factory
{
    protected $model = MachineDowntime::class;

    public function definition(): array
    {
        $machine = Machine::where('status', 'active')->inRandomOrder()->first() ?? Machine::factory()->create(['status' => 'active']);
        $reason = Reason::where('status', 'active')->inRandomOrder()->first() ?? Reason::factory()->create(['status' => 'active']);

        $start = $this->faker->dateTimeBetween('-10 days', 'now');
        $minutes = $this->faker->numberBetween(5, 240);
        $end = (clone $start)->modify("+{$minutes} minutes");

        return [
            'machine_id' => $machine->id,
            'reason_id' => $reason->id,
            'started_at' => $start,
            'ended_at' => $end,
            'notes' => $this->faker->boolean(60) ? $this->faker->sentence(8) : null,
            'status' => $this->faker->boolean(85) ? 'active' : 'inactive',
            'created_by' => $this->existingUserId(),
            'updated_by' => null,
        ];
    }

    protected function existingUserId(): int
    {
        $user = \App\Models\User::inRandomOrder()->first() ?? \App\Models\User::factory()->create();
        return $user->id;
    }
}

