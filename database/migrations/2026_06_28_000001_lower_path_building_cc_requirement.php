<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Analytiklabor (31) and Hangar (44) are now buildable from CC Lv1.
        // Previously required CC Lv2 — path gate (CC-Level−1) was the real limiter,
        // but that gate is being removed; natural resource/supply scarcity limits
        // what the player can actually build at CC1.
        DB::table('buildings')
            ->whereIn('id', [31, 44])
            ->update(['required_building_level' => 1]);
    }

    public function down(): void
    {
        DB::table('buildings')
            ->whereIn('id', [31, 44])
            ->update(['required_building_level' => 2]);
    }
};
