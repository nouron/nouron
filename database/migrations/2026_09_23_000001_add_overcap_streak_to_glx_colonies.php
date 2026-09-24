<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('glx_colonies', function (Blueprint $table) {
            // Consecutive over-capacity Sols (GDD §6 "Überkapazität — Konsequenzen",
            // A14) — mirrors hunger_streak's per-colony-state pattern on this table.
            $table->unsignedInteger('overcap_streak')->default(0)->after('hunger_streak');
        });
    }

    public function down(): void
    {
        Schema::table('glx_colonies', function (Blueprint $table) {
            $table->dropColumn('overcap_streak');
        });
    }
};
