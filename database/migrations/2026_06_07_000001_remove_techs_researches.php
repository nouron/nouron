<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove deprecated techs_* research entries.
     *
     * These entries are dead code — superseded by knowledge_* entries (IDs 90-96).
     * Colony research progress for techs_* entries is also removed.
     */
    public function up(): void
    {
        // Remove all child rows referencing techs_* research IDs before deleting parent rows.
        DB::statement("
            DELETE FROM colony_researches
            WHERE research_id IN (
                SELECT id FROM researches WHERE name LIKE 'techs_%'
            )
        ");

        DB::statement("
            DELETE FROM fleet_researches
            WHERE research_id IN (
                SELECT id FROM researches WHERE name LIKE 'techs_%'
            )
        ");

        DB::statement("
            DELETE FROM research_costs
            WHERE research_id IN (
                SELECT id FROM researches WHERE name LIKE 'techs_%'
            )
        ");

        DB::statement("DELETE FROM researches WHERE name LIKE 'techs_%'");
    }

    /**
     * Reverse the migration.
     *
     * Data cannot be restored from migration alone — no-op.
     */
    public function down(): void
    {
        // Intentional no-op: deleted research master data cannot be reconstructed here.
        // Restore from a database snapshot or re-seed from testdata.sqlite.sql if needed.
    }
};
