<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('glx_colonies', function (Blueprint $table) {
            // Plague outbreak debuff expiry (GDD §9) — mirrors hunger_streak's
            // per-colony-state pattern already on this table.
            $table->unsignedInteger('plague_until_tick')->nullable()->after('hunger_streak');
        });
    }

    public function down(): void
    {
        Schema::table('glx_colonies', function (Blueprint $table) {
            $table->dropColumn('plague_until_tick');
        });
    }
};
