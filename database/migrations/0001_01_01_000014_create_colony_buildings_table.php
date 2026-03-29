<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Note: in the original schema, building_id incorrectly references glx_colonies(id)
        // instead of buildings(id). We correct this here to reference buildings(id).
        Schema::create('colony_buildings', function (Blueprint $table) {
            $table->integer('colony_id');
            $table->integer('building_id');
            $table->integer('level')->default(0);
            $table->double('status_points')->default(20);
            $table->integer('ap_spend')->default(0);

            $table->unique(['colony_id', 'building_id'], 'colony_building');
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
            $table->foreign('building_id')->references('id')->on('buildings');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colony_buildings');
    }
};
