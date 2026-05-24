<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds Nexus-related columns to the runs table.
 *
 * runs.nexus_debt        — opening Nexus advance balance (3000 Cr startup loan per GDD §15),
 *                          non-nullable; every new run starts with this debt outstanding
 * runs.phase2_start_tick — Sol number at which Phase 2 began; NULL until Phase 2 is triggered
 *
 * SQLite supports ADD COLUMN directly for columns that carry only a DEFAULT
 * or that are nullable, so the rename-table pattern is not required for up().
 *
 * down() uses the rename-table pattern to remove both columns.
 * Preserved columns after migration 2026_05_24_000002:
 *   id, user_id, colony_id, current_tick, status, started_at, ended_at,
 *   created_at, updated_at, settings, phase, fail_reason
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE runs ADD COLUMN nexus_debt INTEGER NOT NULL DEFAULT 3000');
        DB::statement('ALTER TABLE runs ADD COLUMN phase2_start_tick INTEGER');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('CREATE TABLE runs_backup (
            id                INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id           INTEGER NOT NULL,
            colony_id         INTEGER NOT NULL,
            current_tick      INTEGER NOT NULL DEFAULT 0,
            status            VARCHAR(20) NOT NULL DEFAULT \'active\',
            started_at        TIMESTAMP,
            ended_at          TIMESTAMP,
            created_at        TIMESTAMP,
            updated_at        TIMESTAMP,
            settings          TEXT,
            phase             INTEGER NOT NULL DEFAULT 1,
            fail_reason       VARCHAR(50),
            FOREIGN KEY (user_id)   REFERENCES user(user_id)        ON DELETE CASCADE,
            FOREIGN KEY (colony_id) REFERENCES glx_colonies(id)     ON DELETE CASCADE
        )');

        DB::statement('INSERT INTO runs_backup
            SELECT id, user_id, colony_id, current_tick, status,
                   started_at, ended_at, created_at, updated_at,
                   settings, phase, fail_reason
            FROM runs');

        DB::statement('DROP TABLE runs');
        DB::statement('ALTER TABLE runs_backup RENAME TO runs');

        // Recreate the index originally defined in create_runs_table.
        DB::statement('CREATE INDEX runs_user_id_status_index ON runs (user_id, status)');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
