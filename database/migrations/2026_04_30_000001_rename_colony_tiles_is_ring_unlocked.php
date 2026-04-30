<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // v_trade_researches references the already-dropped trade_researches table.
        // SQLite validates all views during RENAME COLUMN, so drop the broken view first.
        DB::statement('DROP VIEW IF EXISTS v_trade_researches');

        DB::statement('PRAGMA legacy_alter_table = ON');
        DB::statement('ALTER TABLE colony_tiles RENAME COLUMN is_ring_unlocked TO is_colony_zone');
        DB::statement('PRAGMA legacy_alter_table = OFF');
    }

    public function down(): void
    {
        DB::statement('PRAGMA legacy_alter_table = ON');
        DB::statement('ALTER TABLE colony_tiles RENAME COLUMN is_colony_zone TO is_ring_unlocked');
        DB::statement('PRAGMA legacy_alter_table = OFF');
    }
};
