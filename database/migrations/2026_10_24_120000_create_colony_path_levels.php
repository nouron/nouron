<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Path depth (T41): level and invested AP per colony and mission family / character.
 * A character row with level 0 and ap_spend 0 means "met".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colony_path_levels', function (Blueprint $table) {
            $table->integer('colony_id');
            $table->string('kind', 16);
            $table->string('entity_key', 40);
            $table->tinyInteger('level')->default(0);
            $table->integer('ap_spend')->default(0);
            $table->primary(['colony_id', 'kind', 'entity_key']);
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colony_path_levels');
    }
};
