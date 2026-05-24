<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds phase tracking and failure reason to the runs table.
 *
 * runs.phase       — current run phase (1 = early game, 2 = late game), non-nullable
 * runs.fail_reason — reason for failure when status = 'failed'; one of:
 *                    'trust_collapse' | 'time_limit' | NULL (active or completed runs)
 *
 * SQLite does not support DROP COLUMN, so down() recreates the table without
 * the two added columns using the rename-table pattern.
 */
return new class extends Migration
{
    public function up(): void
    {
        // SQLite supports ADD COLUMN directly for columns without table constraints.
        DB::statement('ALTER TABLE runs ADD COLUMN phase INTEGER NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE runs ADD COLUMN fail_reason VARCHAR(50)');
    }

    public function down(): void
    {
        // Recreate the table without the phase and fail_reason columns.
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('CREATE TABLE runs_backup (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id      INTEGER NOT NULL,
            colony_id    INTEGER NOT NULL,
            current_tick INTEGER NOT NULL DEFAULT 0,
            status       VARCHAR(20) NOT NULL DEFAULT \'active\',
            started_at   TIMESTAMP,
            ended_at     TIMESTAMP,
            created_at   TIMESTAMP,
            updated_at   TIMESTAMP,
            settings     TEXT,
            FOREIGN KEY (user_id)   REFERENCES user(user_id)         ON DELETE CASCADE,
            FOREIGN KEY (colony_id) REFERENCES glx_colonies(id)      ON DELETE CASCADE
        )');

        DB::statement('INSERT INTO runs_backup
            SELECT id, user_id, colony_id, current_tick, status,
                   started_at, ended_at, created_at, updated_at, settings
            FROM runs');

        DB::statement('DROP TABLE runs');
        DB::statement('ALTER TABLE runs_backup RENAME TO runs');

        // Recreate the index that the original migration defined.
        DB::statement('CREATE INDEX runs_user_id_status_index ON runs (user_id, status)');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
