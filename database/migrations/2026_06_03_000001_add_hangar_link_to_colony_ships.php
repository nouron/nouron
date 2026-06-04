<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extends colony_ships with hangar-assignment and dispatch-state columns.
 *
 * Changes:
 *   - Adds `hangar_instance_id` INTEGER NULL
 *     Soft-link to the colony_buildings row for the hangar that houses this
 *     ship. Matches colony_buildings.instance_id where building_id = 44.
 *     NULL means the ship is not assigned to any hangar bay.
 *     No FK constraint: SQLite FK enforcement on composite keys is awkward
 *     and the hangar row may not exist during older migrations.
 *
 *   - Adds `ship_state` TEXT NOT NULL DEFAULT 'docked'
 *     Enum-style state for the ship lifecycle:
 *       docked      — in hangar, available
 *       dispatched  — on a mission (has an active colony_hangar_missions row)
 *       building    — under construction / refit
 *
 * SQLite supports ADD COLUMN for nullable or DEFAULT-bearing columns without
 * a table rebuild, so we use ALTER TABLE ADD COLUMN here.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE colony_ships ADD COLUMN hangar_instance_id INTEGER NULL');
        DB::statement("ALTER TABLE colony_ships ADD COLUMN ship_state TEXT NOT NULL DEFAULT 'docked'");
    }

    public function down(): void
    {
        // SQLite does not support DROP COLUMN on tables without rebuilding.
        // Rebuild colony_ships without the two new columns.
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('PRAGMA legacy_alter_table = ON');

        DB::statement('ALTER TABLE colony_ships RENAME TO colony_ships_old');

        DB::statement('
            CREATE TABLE colony_ships (
                colony_id     INTEGER NOT NULL REFERENCES glx_colonies(id),
                ship_id       INTEGER NOT NULL REFERENCES ships(id),
                level         INTEGER NOT NULL DEFAULT 0,
                status_points INTEGER NOT NULL DEFAULT 10,
                ap_spend      INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY (colony_id, ship_id)
            )
        ');

        DB::statement('
            INSERT INTO colony_ships (colony_id, ship_id, level, status_points, ap_spend)
            SELECT colony_id, ship_id, level, status_points, ap_spend
            FROM colony_ships_old
        ');

        DB::statement('DROP TABLE colony_ships_old');

        DB::statement('PRAGMA legacy_alter_table = OFF');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};
