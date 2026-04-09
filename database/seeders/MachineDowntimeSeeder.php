<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\MachineDowntime;
use App\Models\Reason;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class MachineDowntimeSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure we have active machines and reasons
        $machines = Machine::active()->get();
        if ($machines->isEmpty()) {
            $machines = Machine::factory()->count(3)->create(['status' => 'active']);
        }

        $reasons = Reason::active()->get();
        if ($reasons->isEmpty()) {
            $reasons = Reason::factory()->count(5)->create(['status' => 'active']);
        }

        // Deterministic small set via updateOrCreate for idempotency
        $examples = [];
        foreach ($machines->take(2) as $m) {
            foreach ($reasons->take(2) as $r) {
                $start = now()->subDays(rand(1, 5))->setTime(rand(0, 23), [0, 15, 30, 45][array_rand([0, 1, 2, 3])]);
                $end = (clone $start)->addMinutes([15, 30, 45, 60, 120][array_rand([0, 1, 2, 3, 4])]);
                $examples[] = [
                    'machine_id' => $m->id,
                    'reason_id' => $r->id,
                    'started_at' => $start,
                    'ended_at' => $end,
                    'status' => 'active',
                ];
            }
        }

        foreach ($examples as $ex) {
            MachineDowntime::updateOrCreate(
                [
                    'machine_id' => $ex['machine_id'],
                    'reason_id' => $ex['reason_id'],
                    'started_at' => $ex['started_at'],
                ],
                $ex
            );
        }

        // Random extra based on config volumes
        $base = (int) env('SEED_QTD', (int) config('seeding.volumes.machine_downtimes', 0));
        $default = (int) config('seeding.volumes.machine_downtimes', 20);
        $desired = $base > 0 ? max(5, (int) round($base * 0.2)) : $default;

        $count = max(0, $desired - count($examples));
        if ($count > 0) {
            MachineDowntime::factory()->count($count)->create();
        }
    }
}

