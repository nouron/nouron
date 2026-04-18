<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // ── advisors: drop fleet_id and is_commander ──────────────────────────
        // SQLite does not support DROP COLUMN on tables with constraints,
        // so we recreate the table without the fleet-scoped columns.

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

        // Copy only colony-scoped rows (fleet-scoped advisors are discarded).
        DB::statement('
            INSERT INTO advisors (id, user_id, personell_id, colony_id, rank, active_ticks, unavailable_until_tick)
            SELECT id, user_id, personell_id, colony_id, rank, active_ticks, unavailable_until_tick
            FROM advisors_old
            WHERE colony_id IS NOT NULL
        ');

        DB::statement('DROP TABLE advisors_old');

        DB::statement('
            CREATE UNIQUE INDEX advisors_colony_personell_unique
            ON advisors (colony_id, personell_id)
            WHERE colony_id IS NOT NULL
        ');

        // ── personell: drop can_command_fleet ────────────────────────────────
        // Same approach: recreate without the column.
        // legacy_alter_table = ON prevents SQLite from updating FK references in
        // other tables (personell_costs) to point to the renamed personell_old,
        // which would leave a dangling FK after personell_old is dropped.
        DB::statement('PRAGMA legacy_alter_table = ON');
        DB::statement('ALTER TABLE personell RENAME TO personell_old');

        DB::statement('
            CREATE TABLE personell (
                id                      INTEGER NOT NULL,
                purpose                 TEXT    NOT NULL,
                name                    TEXT    UNIQUE NOT NULL,
                required_building_id    INTEGER DEFAULT NULL REFERENCES buildings(id),
                required_building_level INTEGER DEFAULT NULL,
                row                     INTEGER NOT NULL,
                "column"                INTEGER NOT NULL,
                max_status_points       INTEGER DEFAULT NULL,
                PRIMARY KEY (id),
                CONSTRAINT personell_row_column UNIQUE (row, "column")
            )
        ');

        DB::statement('
            INSERT INTO personell (id, purpose, name, required_building_id,
                                   required_building_level, row, "column", max_status_points)
            SELECT id, purpose, name, required_building_id,
                   required_building_level, row, "column", max_status_points
            FROM personell_old
        ');

        DB::statement('DROP TABLE personell_old');
        DB::statement('PRAGMA legacy_alter_table = OFF');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // Restore fleet_id and is_commander to advisors.
        DB::statement('ALTER TABLE advisors RENAME TO advisors_new');

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
                unavailable_until_tick  INTEGER DEFAULT NULL
            )
        ');

        DB::statement('
            INSERT INTO advisors (id, user_id, personell_id, colony_id, rank, active_ticks, unavailable_until_tick)
            SELECT id, user_id, personell_id, colony_id, rank, active_ticks, unavailable_until_tick
            FROM advisors_new
        ');

        DB::statement('DROP TABLE advisors_new');

        // Restore can_command_fleet to personell.
        DB::statement('ALTER TABLE personell ADD COLUMN can_command_fleet INTEGER NOT NULL DEFAULT 0');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
