<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recreates colony_ships with an auto-increment surrogate PK.
 *
 * Motivation:
 *   The old composite PK (colony_id, ship_id) prevents a colony from owning
 *   multiple ships of the same type. The Nexus acquisition flow requires that
 *   a colony can order the same ship model more than once (each delivery is a
 *   distinct row while in transit).
 *
 * Schema changes:
 *   - id INTEGER PRIMARY KEY AUTOINCREMENT  — new surrogate PK
 *   - colony_id, ship_id, level, status_points, ap_spend, hangar_instance_id,
 *     ship_state  — carried over unchanged
 *   - deliver_at_tick INTEGER NULL  — tick on which the Nexus delivers this
 *     ship; NULL means the ship has already been delivered (is in hangar)
 *   - pending_until_tick INTEGER NULL  — decay deadline tick for unassigned
 *     ships that are not yet docked in a hangar bay; NULL means the ship is
 *     assigned (hangar_instance_id IS NOT NULL)
 *
 * ship_state allowed values (documented, not enforced by CHECK in SQLite):
 *   docked      — in hangar bay, available
 *   dispatched  — on an active mission
 *   building    — under construction / refit
 *   pending     — delivered by Nexus but not yet assigned to a hangar bay
 *
 * SQLite cannot ALTER PRIMARY KEY, so we use the standard recreate pattern:
 *   create new → copy data → drop old → rename new.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('PRAGMA legacy_alter_table = ON');

        // 1. Create the new table with the revised schema.
        DB::statement('
            CREATE TABLE colony_ships_new (
                id                  INTEGER PRIMARY KEY AUTOINCREMENT,
                colony_id           INTEGER NOT NULL REFERENCES glx_colonies(id),
                ship_id             INTEGER NOT NULL REFERENCES ships(id),
                level               INTEGER NOT NULL DEFAULT 0,
                status_points       INTEGER NOT NULL DEFAULT 10,
                ap_spend            INTEGER NOT NULL DEFAULT 0,
                hangar_instance_id  INTEGER NULL,
                ship_state          TEXT    NOT NULL DEFAULT \'docked\',
                deliver_at_tick     INTEGER NULL,
                pending_until_tick  INTEGER NULL
            )
        ');

        // 2. Copy all existing rows; new columns default to NULL.
        DB::statement('
            INSERT INTO colony_ships_new
                (colony_id, ship_id, level, status_points, ap_spend,
                 hangar_instance_id, ship_state)
            SELECT colony_id, ship_id, level, status_points, ap_spend,
                   hangar_instance_id, ship_state
            FROM colony_ships
        ');

        // 3. Drop the old table and promote the new one.
        DB::statement('DROP TABLE colony_ships');
        DB::statement('ALTER TABLE colony_ships_new RENAME TO colony_ships');

        DB::statement('PRAGMA legacy_alter_table = OFF');
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('PRAGMA legacy_alter_table = ON');

        // Recreate the original table (composite PK, no deliver/pending cols).
        DB::statement('
            CREATE TABLE colony_ships_old (
                colony_id           INTEGER NOT NULL REFERENCES glx_colonies(id),
                ship_id             INTEGER NOT NULL REFERENCES ships(id),
                level               INTEGER NOT NULL DEFAULT 0,
                status_points       INTEGER NOT NULL DEFAULT 10,
                ap_spend            INTEGER NOT NULL DEFAULT 0,
                hangar_instance_id  INTEGER NULL,
                ship_state          TEXT    NOT NULL DEFAULT \'docked\',
                PRIMARY KEY (colony_id, ship_id)
            )
        ');

        // Copy data back; discard deliver_at_tick, pending_until_tick and id.
        // Rows with duplicate (colony_id, ship_id) — only possible after
        // Nexus deliveries — are collapsed via INSERT OR IGNORE.
        DB::statement('
            INSERT OR IGNORE INTO colony_ships_old
                (colony_id, ship_id, level, status_points, ap_spend,
                 hangar_instance_id, ship_state)
            SELECT colony_id, ship_id, level, status_points, ap_spend,
                   hangar_instance_id, ship_state
            FROM colony_ships
        ');

        DB::statement('DROP TABLE colony_ships');
        DB::statement('ALTER TABLE colony_ships_old RENAME TO colony_ships');

        DB::statement('PRAGMA legacy_alter_table = OFF');
        Schema::enableForeignKeyConstraints();
    }
};
