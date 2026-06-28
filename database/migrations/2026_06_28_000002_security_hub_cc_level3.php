<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SecurityHub (building_id=53) gates behind CC Lv3 (raised from Lv2).
        // It is the prerequisite for the Stratege advisor slot (Slot 5, Pfad D).
        DB::table('buildings')
            ->where('id', 53)
            ->update(['required_building_level' => 3]);
    }

    public function down(): void
    {
        DB::table('buildings')
            ->where('id', 53)
            ->update(['required_building_level' => 2]);
    }
};
