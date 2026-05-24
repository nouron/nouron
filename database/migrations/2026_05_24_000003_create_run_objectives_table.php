<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Creates the run_objectives table.
 *
 * Each row tracks one objective (task) within a run. A single run may have
 * multiple objectives simultaneously (no UNIQUE constraint on run_id + task_key).
 *
 * run_objectives:
 *   run_id        — FK → runs(id), CASCADE on delete
 *   task_key      — machine key identifying the objective type, e.g.
 *                   'task_expertenstab', 'task_kreditimperium'
 *   target_value  — the numeric goal that must be reached (e.g. 5 researches at Lv5)
 *   current_value — progress counter, incremented by the tick/game service
 *   streak_value  — consecutive-Sol counter used by streak-type objectives
 *   completed_at  — Sol number when the objective was fulfilled; NULL while open
 *   created_at    — wall-clock timestamp of row creation
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE TABLE run_objectives (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            run_id        INTEGER NOT NULL,
            task_key      VARCHAR(30) NOT NULL,
            target_value  INTEGER NOT NULL DEFAULT 0,
            current_value INTEGER NOT NULL DEFAULT 0,
            streak_value  INTEGER NOT NULL DEFAULT 0,
            completed_at  INTEGER,
            created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (run_id) REFERENCES runs(id) ON DELETE CASCADE
        )');

        // Fast lookup of all objectives belonging to a run.
        DB::statement('CREATE INDEX run_objectives_run_id_index ON run_objectives (run_id)');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS run_objectives');
    }
};
