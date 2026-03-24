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
 * Keeps Laravel test data in sync with the Laminas test.db automatically.
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

        DB::statement('PRAGMA foreign_keys = OFF');

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }
            // Strip trailing semicolon-and-quote artifacts from sqlite3 dumps
            DB::statement(rtrim($statement, ';') . ';');
        }

        DB::statement('PRAGMA foreign_keys = ON');
    }
}
