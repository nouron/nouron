<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T60: who sits in the cantina on a Sol besides Tomas (CantinaPresenceService::cast()).
 * Stored so the cast stays fixed for the whole Sol: resolving one figure never lets
 * another one in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cantina_casts', function (Blueprint $table) {
            $table->integer('colony_id');
            $table->integer('tick');
            $table->unsignedTinyInteger('size');
            $table->json('figures');
            $table->primary(['colony_id', 'tick']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cantina_casts');
    }
};
