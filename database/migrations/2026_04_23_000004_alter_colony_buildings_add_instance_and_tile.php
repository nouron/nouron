<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extends colony_buildings for instanced buildings and tile-placement.
 *
 * Changes:
 *   - Adds `instance_id` INTEGER NOT NULL DEFAULT 1
 *     Each physical copy of an instanced building (e.g. housingComplex,
 *     hangar) gets its own row distinguished by instance_id.
 *     Non-instanced buildings always have instance_id = 1.
 *
 *   - Adds `tile_x` / `tile_y` INTEGER NULL
 *     Optional tile coordinates for harvesters and other buildings that
 *     occupy a specific exploration-zone tile on the hex grid.
 *
 *   - Replaces the UNIQUE constraint (colony_id, building_id)
 *     with             (colony_id, building_id, instance_id)
 *     so that multiple instances of the same building per colony are valid.
 *
 * SQLite cannot ALTER a unique constraint, so we rebuild the table.
 * PRAGMA legacy_alter_table = ON prevents SQLite from rewriting FK
 * references in other tables during the rename step.
 *
 * Design context: GDD §4 (Instanced Buildings), DS-4 (Tile placement).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('PRAGMA legacy_alter_table = ON');

        DB::statement('ALTER TABLE colony_buildings RENAME TO colony_buildings_old');

        DB::statement('
            CREATE TABLE colony_buildings (
                colony_id    INTEGER NOT NULL REFERENCES glx_colonies(id),
                building_id  INTEGER NOT NULL REFERENCES buildings(id),
                instance_id  INTEGER NOT NULL DEFAULT 1,
                level        INTEGER NOT NULL DEFAULT 0,
                status_points REAL   NOT NULL DEFAULT 20,
                ap_spend     INTEGER NOT NULL DEFAULT 0,
                tile_x       INTEGER DEFAULT NULL,
                tile_y       INTEGER DEFAULT NULL,
                CONSTRAINT colony_building UNIQUE (colony_id, building_id, instance_id)
            )
        ');

        DB::statement('
            INSERT INTO colony_buildings
                (colony_id, building_id, instance_id, level, status_points, ap_spend, tile_x, tile_y)
            SELECT colony_id, building_id, 1, level, status_points, ap_spend, NULL, NULL
            FROM colony_buildings_old
        ');

        DB::statement('DROP TABLE colony_buildings_old');

        DB::statement('PRAGMA legacy_alter_table = OFF');
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('PRAGMA legacy_alter_table = ON');

        // Collapse multiple instances back to one row per (colony_id, building_id)
        // by keeping only instance_id = 1. Instances > 1 are lost on rollback.
        DB::statement('ALTER TABLE colony_buildings RENAME TO colony_buildings_new');

        DB::statement('
            CREATE TABLE colony_buildings (
                colony_id    INTEGER NOT NULL REFERENCES glx_colonies(id),
                building_id  INTEGER NOT NULL REFERENCES buildings(id),
                level        INTEGER NOT NULL DEFAULT 0,
                status_points REAL   NOT NULL DEFAULT 20,
                ap_spend     INTEGER NOT NULL DEFAULT 0,
                CONSTRAINT colony_building UNIQUE (colony_id, building_id)
            )
        ');

        DB::statement('
            INSERT INTO colony_buildings (colony_id, building_id, level, status_points, ap_spend)
            SELECT colony_id, building_id, level, status_points, ap_spend
            FROM colony_buildings_new
            WHERE instance_id = 1
        ');

        DB::statement('DROP TABLE colony_buildings_new');

        DB::statement('PRAGMA legacy_alter_table = OFF');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};
