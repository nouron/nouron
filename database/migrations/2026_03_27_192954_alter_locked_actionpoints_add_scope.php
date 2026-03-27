<?php

/**
 * Migration: alter locked_actionpoints to use scope_type + scope_id
 * instead of a hard-coded colony_id foreign key.
 *
 * This is needed because Navigation-AP belong to a fleet (fleet scope),
 * while construction/research/economy AP belong to a colony (colony scope).
 *
 * SQLite does not support ALTER TABLE ... DROP COLUMN or changing primary
 * keys, so we recreate the table via rename → create → copy → drop.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Rename old table so we can copy data from it.
        DB::statement('ALTER TABLE locked_actionpoints RENAME TO locked_actionpoints_old');

        // Step 2: Create the new table with scope_type + scope_id.
        DB::statement('
            CREATE TABLE locked_actionpoints (
                tick         INTEGER NOT NULL,
                scope_type   TEXT    NOT NULL,
                scope_id     INTEGER NOT NULL,
                personell_id INTEGER NOT NULL,
                spend_ap     INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY (tick, scope_type, scope_id, personell_id)
            )
        ');

        // Step 3: Migrate existing rows — all old rows were colony-scoped.
        DB::statement("
            INSERT INTO locked_actionpoints (tick, scope_type, scope_id, personell_id, spend_ap)
            SELECT tick, 'colony', colony_id, personell_id, spend_ap
            FROM locked_actionpoints_old
        ");

        // Step 4: Drop the old table.
        DB::statement('DROP TABLE locked_actionpoints_old');
    }

    public function down(): void
    {
        // Step 1: Rename new table out of the way.
        DB::statement('ALTER TABLE locked_actionpoints RENAME TO locked_actionpoints_new');

        // Step 2: Recreate the original schema (without FK constraints so
        //         SQLite does not complain about missing glx_colonies rows
        //         that may have been deleted during tests).
        DB::statement('
            CREATE TABLE locked_actionpoints (
                tick         INTEGER NOT NULL,
                colony_id    INTEGER NOT NULL,
                personell_id INTEGER NOT NULL,
                spend_ap     INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY (tick, colony_id, personell_id)
            )
        ');

        // Step 3: Migrate back — only colony-scoped rows can be restored.
        DB::statement("
            INSERT INTO locked_actionpoints (tick, colony_id, personell_id, spend_ap)
            SELECT tick, scope_id, personell_id, spend_ap
            FROM locked_actionpoints_new
            WHERE scope_type = 'colony'
        ");

        // Step 4: Drop the intermediate table.
        DB::statement('DROP TABLE locked_actionpoints_new');
    }
};
