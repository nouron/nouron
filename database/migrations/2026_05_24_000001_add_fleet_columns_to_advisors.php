<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Re-introduce fleet_id and is_commander to the advisors table.
 *
 * These columns were removed in 2026_04_16_000002_drop_advisor_fleet_columns
 * and are now required for the commander-assignment feature.
 *
 * SQLite does not support ADD COLUMN with REFERENCES or CHECK constraints,
 * so we use the standard table-recreation pattern.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('ALTER TABLE advisors RENAME TO advisors_old');

        DB::statement('
            CREATE TABLE advisors (
                id                      INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                user_id                 INTEGER NOT NULL REFERENCES "user"(user_id),
                personell_id            INTEGER NOT NULL REFERENCES personell(id),
                colony_id               INTEGER DEFAULT NULL REFERENCES glx_colonies(id),
                fleet_id                INTEGER DEFAULT NULL REFERENCES fleets(id),
                is_commander            INTEGER NOT NULL DEFAULT 0,
                rank                    INTEGER NOT NULL DEFAULT 1,
                active_ticks            INTEGER NOT NULL DEFAULT 0,
                unavailable_until_tick  INTEGER DEFAULT NULL,
                CHECK (colony_id IS NULL OR fleet_id IS NULL)
            )
        ');

        // Copy existing rows; fleet_id defaults to NULL, is_commander defaults to 0.
        DB::statement('
            INSERT INTO advisors (id, user_id, personell_id, colony_id, fleet_id, is_commander,
                                  rank, active_ticks, unavailable_until_tick)
            SELECT id, user_id, personell_id, colony_id, NULL, 0,
                   rank, active_ticks, unavailable_until_tick
            FROM advisors_old
        ');

        DB::statement('DROP TABLE advisors_old');

        // Restore partial unique index: one advisor type per colony slot.
        DB::statement('
            CREATE UNIQUE INDEX advisors_colony_personell_unique
            ON advisors (colony_id, personell_id)
            WHERE colony_id IS NOT NULL
        ');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('ALTER TABLE advisors RENAME TO advisors_old');

        DB::statement('
            CREATE TABLE advisors (
                id                      INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                user_id                 INTEGER NOT NULL REFERENCES "user"(user_id),
                personell_id            INTEGER NOT NULL REFERENCES personell(id),
                colony_id               INTEGER DEFAULT NULL REFERENCES glx_colonies(id),
                rank                    INTEGER NOT NULL DEFAULT 1,
                active_ticks            INTEGER NOT NULL DEFAULT 0,
                unavailable_until_tick  INTEGER DEFAULT NULL
            )
        ');

        // Copy only colony-scoped rows back; fleet-scoped rows are discarded.
        DB::statement('
            INSERT INTO advisors (id, user_id, personell_id, colony_id,
                                  rank, active_ticks, unavailable_until_tick)
            SELECT id, user_id, personell_id, colony_id,
                   rank, active_ticks, unavailable_until_tick
            FROM advisors_old
            WHERE colony_id IS NOT NULL
        ');

        DB::statement('DROP TABLE advisors_old');

        DB::statement('
            CREATE UNIQUE INDEX advisors_colony_personell_unique
            ON advisors (colony_id, personell_id)
            WHERE colony_id IS NOT NULL
        ');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
