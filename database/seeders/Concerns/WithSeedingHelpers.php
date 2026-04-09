<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use Illuminate\Support\Arr;

trait WithSeedingHelpers
{
    protected function weightedPick(array $weights): string
    {
        // Example: ['active'=>70,'inactive'=>30]
        $total = array_sum($weights);
        if ($total <= 0) {
            // Fallback to first key when misconfigured
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

    protected function rangedPick(array $range): int
    {
        // Example: [1,5]
        $min = (int) Arr::get($range, 0, 1);
        $max = (int) Arr::get($range, 1, $min);
        return $min <= $max ? mt_rand($min, $max) : $min;
    }
}

