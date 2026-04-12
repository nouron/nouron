<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rename all "research" tables to "knowledge".
 *
 * Background: the old "techs" umbrella (buildings + researches + ships) is gone.
 * Researches are now called "Kenntnisse" (knowledge) in the GDD. This migration
 * renames the five affected tables to reflect that terminology.
 *
 * Previous migration 2026_04_12_000001 ran against the old table names — that is
 * intentional and must not be changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('researches',       'knowledge');
        Schema::rename('research_costs',   'knowledge_costs');
        Schema::rename('colony_researches','colony_knowledge');
        Schema::rename('fleet_researches', 'fleet_knowledge');
        Schema::rename('trade_researches', 'trade_knowledge');

        // Recreate the v_trade_researches view to reference the renamed table.
        DB::statement('DROP VIEW IF EXISTS v_trade_researches');
        DB::statement(
            "CREATE VIEW v_trade_researches AS
             SELECT
                 tr.colony_id,
                 tr.direction,
                 tr.research_id,
                 tr.amount,
                 tr.price,
                 tr.restriction,
                 col.name      AS colony,
                 u.username,
                 u.user_id,
                 u.race_id,
                 u.faction_id
             FROM trade_knowledge tr
             JOIN glx_colonies col ON tr.colony_id = col.id
             JOIN user u           ON col.user_id  = u.user_id"
        );
    }

    public function down(): void
    {
        Schema::rename('knowledge',        'researches');
        Schema::rename('knowledge_costs',  'research_costs');
        Schema::rename('colony_knowledge', 'colony_researches');
        Schema::rename('fleet_knowledge',  'fleet_researches');
        Schema::rename('trade_knowledge',  'trade_researches');

        // Restore the view to reference the original table name.
        DB::statement('DROP VIEW IF EXISTS v_trade_researches');
        DB::statement(
            "CREATE VIEW v_trade_researches AS
             SELECT
                 tr.colony_id,
                 tr.direction,
                 tr.research_id,
                 tr.amount,
                 tr.price,
                 tr.restriction,
                 col.name      AS colony,
                 u.username,
                 u.user_id,
                 u.race_id,
                 u.faction_id
             FROM trade_researches tr
             JOIN glx_colonies col ON tr.colony_id = col.id
             JOIN user u           ON col.user_id  = u.user_id"
        );
    }
};
