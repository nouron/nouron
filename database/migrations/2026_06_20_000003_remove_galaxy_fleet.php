<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Removes the galaxy / system-map + fleet layer (2026-06-20).
 *
 * The navigable galaxy + system map and fleet movement/combat were cut (Owner
 * decision: "Galaxie und Systemkarte bis auf weiteres gestrichen"). UI was already
 * gone; this drops the dead backend tables and decouples the colony from the
 * system-object schema.
 *
 * glx_colonies is rebuilt WITHOUT system_object_id + spot (their FK to
 * glx_system_objects is what blocked dropping that table). v_glx_colonies becomes a
 * plain passthrough; v_trade_resources is rebuilt unchanged (it joins glx_colonies).
 * Hangar/colony_ships are a separate system and untouched.
 */
return new class extends Migration
{
    private array $dropTables = [
        'fleet_orders', 'fleet_ships', 'fleet_resources', 'fleet_personell', 'fleet_researches', 'fleets',
        'glx_system_objects', 'glx_system_object_types', 'glx_system_types', 'glx_systems',
    ];

    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // Drop every view that references glx_colonies or the system tables.
        DB::statement('DROP VIEW IF EXISTS v_glx_colonies');
        DB::statement('DROP VIEW IF EXISTS v_trade_resources');
        DB::statement('DROP VIEW IF EXISTS v_glx_system_objects');
        DB::statement('DROP VIEW IF EXISTS v_glx_systems');

        // Rebuild glx_colonies without the galaxy columns (drops the FK + unique index).
        DB::statement('
            CREATE TABLE glx_colonies_new (
                id            INTEGER NOT NULL PRIMARY KEY,
                name          TEXT    NOT NULL DEFAULT \'Colony\',
                user_id       INTEGER DEFAULT NULL REFERENCES "user"(user_id),
                since_tick    INTEGER NOT NULL,
                is_primary    INTEGER NOT NULL DEFAULT 0,
                hunger_streak INTEGER NOT NULL DEFAULT 0
            )
        ');
        DB::statement('
            INSERT INTO glx_colonies_new (id, name, user_id, since_tick, is_primary, hunger_streak)
            SELECT id, name, user_id, since_tick, is_primary, hunger_streak FROM glx_colonies
        ');
        DB::statement('DROP TABLE glx_colonies');
        DB::statement('ALTER TABLE glx_colonies_new RENAME TO glx_colonies');

        // Rebuild advisors without the fleet-commander columns (fleet_id FK → fleets,
        // which is dropped below; is_commander is the now-meaningless companion flag).
        DB::statement('
            CREATE TABLE advisors_new (
                id                     INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                user_id                INTEGER NOT NULL REFERENCES "user"(user_id),
                personell_id           INTEGER NOT NULL REFERENCES personell(id),
                colony_id              INTEGER DEFAULT NULL REFERENCES glx_colonies(id),
                rank                   INTEGER NOT NULL DEFAULT 1,
                active_ticks           INTEGER NOT NULL DEFAULT 0,
                unavailable_until_tick INTEGER DEFAULT NULL
            )
        ');
        DB::statement('
            INSERT INTO advisors_new (id, user_id, personell_id, colony_id, rank, active_ticks, unavailable_until_tick)
            SELECT id, user_id, personell_id, colony_id, rank, active_ticks, unavailable_until_tick FROM advisors
        ');
        DB::statement('DROP TABLE advisors');
        DB::statement('ALTER TABLE advisors_new RENAME TO advisors');

        // Passthrough view (no coordinates any more).
        DB::statement('CREATE VIEW v_glx_colonies AS SELECT * FROM glx_colonies');

        DB::statement('
            CREATE VIEW v_trade_resources AS
            SELECT
                tr.colony_id, tr.direction, tr.resource_id, tr.amount, tr.price, tr.restriction,
                col.name AS colony, u.username, u.user_id, u.faction_id
            FROM trade_resources tr
            JOIN glx_colonies col ON tr.colony_id = col.id
            JOIN "user" u         ON col.user_id  = u.user_id
        ');

        foreach ($this->dropTables as $table) {
            DB::statement("DROP TABLE IF EXISTS {$table}");
        }

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // One-way removal: the galaxy/fleet schema is not restored.
    }
};
