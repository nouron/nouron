<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop and recreate both trade views without u.race_id.
        // SQLite does not support CREATE OR REPLACE VIEW — must drop first.
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
                 u.faction_id
             FROM trade_researches tr
             JOIN glx_colonies col ON tr.colony_id = col.id
             JOIN user u           ON col.user_id  = u.user_id"
        );

        DB::statement('DROP VIEW IF EXISTS v_trade_resources');
        DB::statement(
            "CREATE VIEW v_trade_resources AS
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
             JOIN user u           ON col.user_id  = u.user_id"
        );
    }

    public function down(): void
    {
        // Restore views with u.race_id (original state from 0001_01_01_999999_create_views.php).
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

        DB::statement('DROP VIEW IF EXISTS v_trade_resources');
        DB::statement(
            "CREATE VIEW v_trade_resources AS
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
                 u.race_id,
                 u.faction_id
             FROM trade_resources tr
             JOIN glx_colonies col ON tr.colony_id = col.id
             JOIN user u           ON col.user_id  = u.user_id"
        );
    }
};
