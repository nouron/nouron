<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Make glx_colonies.system_object_id nullable.
 *
 * Colonies no longer require a planet assignment in the current singleplayer
 * design. The system map (Phase 4+) can assign planets retroactively.
 *
 * SQLite does not support ALTER COLUMN, so we recreate the table.
 * We also drop all views that reference glx_colonies to prevent SQLite from
 * blocking the rename step with view-validation errors.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('DROP VIEW IF EXISTS v_glx_colonies');
        DB::statement('DROP VIEW IF EXISTS v_trade_resources');

        DB::statement('
            CREATE TABLE glx_colonies_new (
                id               INTEGER NOT NULL PRIMARY KEY,
                name             TEXT    NOT NULL DEFAULT \'Colony\',
                system_object_id INTEGER DEFAULT NULL
                    REFERENCES glx_system_objects(id),
                spot             INTEGER NOT NULL,
                user_id          INTEGER DEFAULT NULL
                    REFERENCES "user"(user_id),
                since_tick       INTEGER NOT NULL,
                is_primary       INTEGER NOT NULL DEFAULT 0
            )
        ');

        DB::statement('INSERT INTO glx_colonies_new SELECT * FROM glx_colonies');
        DB::statement('DROP TABLE glx_colonies');
        DB::statement('ALTER TABLE glx_colonies_new RENAME TO glx_colonies');

        DB::statement('PRAGMA foreign_keys = ON');

        DB::statement("
            CREATE VIEW v_glx_colonies AS
            SELECT
                c.id,
                c.name,
                c.system_object_id,
                c.spot,
                c.user_id,
                c.since_tick,
                c.is_primary,
                o.name      AS system_object_name,
                o.x,
                o.y,
                o.type_id,
                o.sight,
                o.density,
                o.radiation
            FROM glx_colonies c
            LEFT JOIN glx_system_objects o ON c.system_object_id = o.id
        ");

        DB::statement("
            CREATE VIEW v_trade_resources AS
            SELECT
                tr.colony_id,
                tr.direction,
                tr.resource_id,
                tr.amount,
                tr.price,
                tr.restriction,
                col.name      AS colony,
                u.username,
                u.user_id,
                u.faction_id
            FROM trade_resources tr
            JOIN glx_colonies col ON tr.colony_id = col.id
            JOIN user u           ON col.user_id  = u.user_id
        ");
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('DROP VIEW IF EXISTS v_glx_colonies');
        DB::statement('DROP VIEW IF EXISTS v_trade_resources');

        DB::statement('
            CREATE TABLE glx_colonies_old (
                id               INTEGER NOT NULL PRIMARY KEY,
                name             TEXT    NOT NULL DEFAULT \'Colony\',
                system_object_id INTEGER NOT NULL
                    REFERENCES glx_system_objects(id),
                spot             INTEGER NOT NULL,
                user_id          INTEGER DEFAULT NULL
                    REFERENCES "user"(user_id),
                since_tick       INTEGER NOT NULL,
                is_primary       INTEGER NOT NULL DEFAULT 0
            )
        ');

        DB::statement('INSERT INTO glx_colonies_old SELECT * FROM glx_colonies');
        DB::statement('DROP TABLE glx_colonies');
        DB::statement('ALTER TABLE glx_colonies_old RENAME TO glx_colonies');

        DB::statement('PRAGMA foreign_keys = ON');

        DB::statement("
            CREATE VIEW v_glx_colonies AS
            SELECT
                c.id, c.name, c.system_object_id, c.spot,
                c.user_id, c.since_tick, c.is_primary,
                o.name AS system_object_name,
                o.x, o.y, o.type_id, o.sight, o.density, o.radiation
            FROM glx_colonies c
            JOIN glx_system_objects o ON c.system_object_id = o.id
        ");

        DB::statement("
            CREATE VIEW v_trade_resources AS
            SELECT
                tr.colony_id,
                tr.direction,
                tr.resource_id,
                tr.amount,
                tr.price,
                tr.restriction,
                col.name      AS colony,
                u.username,
                u.user_id,
                u.faction_id
            FROM trade_resources tr
            JOIN glx_colonies col ON tr.colony_id = col.id
            JOIN user u           ON col.user_id  = u.user_id
        ");
    }
};
