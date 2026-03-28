<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE personell ADD COLUMN can_command_fleet INTEGER NOT NULL DEFAULT 0');
        DB::statement('UPDATE personell SET can_command_fleet = 1 WHERE id = 89');
    }

    public function down(): void
    {
        // SQLite DROP COLUMN requires rebuilding the table
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
                CONSTRAINT row UNIQUE (row, "column")
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
    }
};
