<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Populates the test database with the canonical Nouron test fixtures.
 *
 * Reference data (resources, buildings, techtree, ships, costs) comes from ReferenceDataSeeder;
 * fixtures (data/sql/testdata.sql) cover player-side tables only: Simpsons test users
 * (Homer/Marge/Bart), Springfield colony, colony buildings/ships, runs, logs, etc.
 * The only reference-table row in the fixtures is research 9901 (test_decay_placeholder).
 *
 * Fixtures are plain INSERT/UPDATE statements and expect a freshly migrated database.
 * Used by Laravel Feature tests via RefreshDatabase + $seeder = TestSeeder::class.
 */
class TestSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ReferenceDataSeeder::class);

        $lines = array_filter(
            explode("\n", file_get_contents(base_path('data/sql/testdata.sql'))),
            fn (string $line) => (bool) preg_match('/^\s*(INSERT|UPDATE)\s/i', $line)
        );
        foreach ($lines as $line) {
            DB::statement(rtrim(trim($line), ';').';');
        }
    }
}
