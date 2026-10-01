<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // The seed data contains test accounts with a known password hash (R3/R4,
        // closed beta) — production must never be seeded.
        if (app()->isProduction()) {
            throw new RuntimeException('DatabaseSeeder refuses to run in production: it seeds test accounts.');
        }

        $this->call(TestSeeder::class);
    }
}
