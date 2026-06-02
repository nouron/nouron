<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite's ALTER TABLE (column drop) triggers a full table rebuild which
        // re-validates every view in the schema. Three views reference the user table:
        // v_innn_messages, v_trade_resources, and v_trade_researches. The latter
        // references the already-dropped trade_researches table and would cause
        // validation to fail, so all three are dropped before the column drop and the
        // two valid views are recreated afterwards. v_trade_researches is intentionally
        // left dropped — the underlying table no longer exists.

        DB::statement('DROP VIEW IF EXISTS v_innn_messages');
        DB::statement('DROP VIEW IF EXISTS v_trade_researches');
        DB::statement('DROP VIEW IF EXISTS v_trade_resources');

        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn('race_id');
        });

        DB::statement(
            "CREATE VIEW v_innn_messages AS
             SELECT
                 m.id,
                 m.sender_id,
                 m.attitude,
                 m.recipient_id,
                 m.tick,
                 m.type,
                 m.subject,
                 m.text,
                 m.is_read,
                 m.is_archived,
                 m.is_deleted,
                 sender.username    AS sender,
                 recipient.username AS recipient
             FROM innn_messages m
             JOIN user sender    ON sender.user_id    = m.sender_id
             JOIN user recipient ON recipient.user_id = m.recipient_id"
        );

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
        Schema::table('user', function (Blueprint $table) {
            $table->unsignedBigInteger('race_id')->nullable()->after('state');
        });

        // Restore v_trade_researches with race_id (original state from
        // 0001_01_01_999999_create_views.php). Note: trade_researches table
        // was dropped in 2026_04_18_000001 — this view will exist but be unqueryable
        // until that migration is also rolled back.
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
