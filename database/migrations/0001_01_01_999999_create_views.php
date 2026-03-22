<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_glx_colonies');
        DB::statement(
            "CREATE VIEW v_glx_colonies AS
             SELECT
                 c.id,
                 c.name,
                 c.system_object_id,
                 c.spot,
                 c.user_id,
                 c.since_tick,
                 c.is_primary,
                 o.name   AS system_object_name,
                 o.x,
                 o.y,
                 o.type_id,
                 o.sight,
                 o.density,
                 o.radiation
             FROM glx_colonies c
             JOIN glx_system_objects o ON c.system_object_id = o.id"
        );

        DB::statement('DROP VIEW IF EXISTS v_glx_systems');
        DB::statement(
            "CREATE VIEW v_glx_systems AS
             SELECT
                 s.id,
                 s.x,
                 s.y,
                 s.name,
                 s.type_id,
                 s.background_image_url,
                 s.sight,
                 s.density,
                 s.radiation,
                 t.class,
                 t.size,
                 t.icon_url,
                 t.image_url
             FROM glx_systems s
             JOIN glx_system_types t ON s.type_id = t.id"
        );

        DB::statement('DROP VIEW IF EXISTS v_glx_system_objects');
        DB::statement(
            "CREATE VIEW v_glx_system_objects AS
             SELECT
                 o.id,
                 o.x,
                 o.y,
                 o.name,
                 o.type_id,
                 o.sight,
                 o.density,
                 o.radiation,
                 t.type,
                 t.image_url
             FROM glx_system_objects o
             JOIN glx_system_object_types t ON o.type_id = t.id"
        );

        DB::statement('DROP VIEW IF EXISTS v_innn_messages');
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

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_trade_resources');
        DB::statement('DROP VIEW IF EXISTS v_trade_researches');
        DB::statement('DROP VIEW IF EXISTS v_innn_messages');
        DB::statement('DROP VIEW IF EXISTS v_glx_system_objects');
        DB::statement('DROP VIEW IF EXISTS v_glx_systems');
        DB::statement('DROP VIEW IF EXISTS v_glx_colonies');
    }
};
