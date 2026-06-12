<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Harvester relocation takes 1 Sol of transit time. While
 * pending_until_tick >= current tick the building is "in transit":
 * it does not produce and cannot be relocated again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colony_buildings', function (Blueprint $table) {
            $table->integer('pending_until_tick')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('colony_buildings', function (Blueprint $table) {
            $table->dropColumn('pending_until_tick');
        });
    }
};
