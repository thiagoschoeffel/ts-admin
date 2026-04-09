<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BaseSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure core references exist (users, roles, etc.)
        $this->call(UserSeeder::class);
    }
}

