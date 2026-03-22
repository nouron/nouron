<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_ships', function (Blueprint $table) {
            $table->integer('fleet_id');
            $table->integer('ship_id');
            $table->integer('count');
            $table->integer('is_cargo')->default(0);

            $table->unique(['fleet_id', 'ship_id', 'is_cargo'], 'fleet_ships_unique');
            $table->foreign('fleet_id')->references('id')->on('fleets');
            $table->foreign('ship_id')->references('id')->on('ships');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_ships');
    }
};
