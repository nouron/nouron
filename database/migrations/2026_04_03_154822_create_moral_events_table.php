<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('moral_events', function (Blueprint $table) {
            $table->id();
            $table->integer('colony_id');
            $table->integer('tick');
            $table->string('event_type', 64);

            $table->index(['colony_id', 'tick']);
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moral_events');
    }
};
