<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent reference data (resources, buildings, techtree, ships, costs). Safe to run on every
 * deploy and on a database that already holds player data: upsert only, never delete/replace.
 * Mechanic values (decay, supply, max_level, regolith/werkstoffe cost) are applied afterwards from
 * config/*.php via game:sync-config — config stays the source of truth for those.
 *
 * Caveat: `researches` has a unique index on (phase, row, column) WHERE phase > 0. A first run on an
 * empty database cannot collide, but if a later balance pass swaps the techtree positions of two
 * rows, the upsert would hit the index mid-statement. In that case set `phase` to 0 for the
 * affected rows inside the same transaction before upserting.
 */
class ReferenceDataSeeder extends Seeder
{
    /** table => unique key columns, in foreign-key order. */
    private const TABLES = [
        'resources' => ['id'],
        'buildings' => ['id'],
        'personell' => ['id'],
        'researches' => ['id'],
        'ships' => ['id'],
        'building_costs' => ['building_id', 'resource_id'],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::TABLES as $table => $uniqueBy) {
                $rows = require database_path("seeders/data/{$table}.php");
                $update = array_values(array_diff(array_keys($rows[0]), $uniqueBy));
                DB::table($table)->upsert($rows, $uniqueBy, $update);
            }
        });

        Artisan::call('game:sync-config');
    }
}
