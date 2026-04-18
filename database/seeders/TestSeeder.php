<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Populates the test database with the canonical Nouron test fixtures.
 *
 * Data source: data/sql/testdata.sqlite.sql
 * Contains: Simpsons test users (Homer/Marge/Bart), Springfield colony,
 *           buildings, researches, ships, resources, fleets, INNN messages, etc.
 *
 * Used by Laravel Feature tests via RefreshDatabase + $seeder = TestSeeder::class.
 * Keeps test data in sync with the migration schema automatically.
 */
class TestSeeder extends Seeder
{
    public function run(): void
    {
        $sql = file_get_contents(base_path('data/sql/testdata.sqlite.sql'));

        // Execute only INSERT statements (schema is handled by migrations)
        $statements = array_filter(
            explode("\n", $sql),
            fn(string $line) => str_starts_with(ltrim($line), 'INSERT')
        );

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }
            $statement = rtrim($statement, ';') . ';';
            // OR REPLACE: avoids UNIQUE violations when migrations pre-insert master rows (e.g. add_stratege)
            $statement = preg_replace('/^INSERT INTO\b/i', 'INSERT OR REPLACE INTO', $statement);
            DB::statement($statement);
        }

        $this->call(MasterDataSeeder::class);
    }
}
