<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the fleet positioning model: removes the legacy `spot` column
 * (an integer flag for the docking position on a system object) and adds
 * grid_x / grid_y for placement on the 12×12 system view grid.
 *
 * SQLite does not support DROP COLUMN on tables that have constraints, so
 * we rebuild the table via rename → create → copy → drop.
 *
 * PRAGMA legacy_alter_table = ON is set to prevent SQLite from rewriting
 * FK references in child tables (fleet_ships, fleet_orders, etc.) to point
 * to the temporary fleets_old table during the rename.
 *
 * Design context: DS-2 (System View grid positioning).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('PRAGMA legacy_alter_table = ON');

        DB::statement('ALTER TABLE fleets RENAME TO fleets_old');

        DB::statement('
            CREATE TABLE fleets (
                id        INTEGER NOT NULL,
                fleet     TEXT    NOT NULL,
                user_id   INTEGER NOT NULL REFERENCES "user"(user_id),
                artefact  INTEGER DEFAULT NULL,
                x         INTEGER NOT NULL,
                y         INTEGER NOT NULL,
                grid_x    INTEGER DEFAULT NULL,
                grid_y    INTEGER DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ');

        DB::statement('
            INSERT INTO fleets (id, fleet, user_id, artefact, x, y, grid_x, grid_y)
            SELECT id, fleet, user_id, artefact, x, y, NULL, NULL
            FROM fleets_old
        ');

        DB::statement('DROP TABLE fleets_old');

        DB::statement('PRAGMA legacy_alter_table = OFF');
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('PRAGMA legacy_alter_table = ON');

        DB::statement('ALTER TABLE fleets RENAME TO fleets_new');

        DB::statement('
            CREATE TABLE fleets (
                id        INTEGER NOT NULL,
                fleet     TEXT    NOT NULL,
                user_id   INTEGER NOT NULL REFERENCES "user"(user_id),
                artefact  INTEGER DEFAULT NULL,
                x         INTEGER NOT NULL,
                y         INTEGER NOT NULL,
                spot      INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY (id)
            )
        ');

        DB::statement('
            INSERT INTO fleets (id, fleet, user_id, artefact, x, y, spot)
            SELECT id, fleet, user_id, artefact, x, y, 0
            FROM fleets_new
        ');

        DB::statement('DROP TABLE fleets_new');

        DB::statement('PRAGMA legacy_alter_table = OFF');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};
