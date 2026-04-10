<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Enforce the slot-system rule: at most one advisor of each type per colony.
 *
 * Uses a SQLite partial unique index (WHERE colony_id IS NOT NULL) so that
 * fleet-assigned advisors (colony_id = NULL) are not affected — a fleet
 * may still receive a commander independently of the colony slot.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE UNIQUE INDEX advisors_colony_personell_unique
            ON advisors (colony_id, personell_id)
            WHERE colony_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS advisors_colony_personell_unique');
    }
};
