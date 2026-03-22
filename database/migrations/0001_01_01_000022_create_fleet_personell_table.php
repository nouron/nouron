<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_personell', function (Blueprint $table) {
            $table->integer('fleet_id');
            $table->integer('personell_id');
            $table->integer('count');
            $table->integer('is_cargo')->default(0);

            $table->unique(['fleet_id', 'personell_id', 'is_cargo'], 'fleet_personell_unique');
            $table->foreign('fleet_id')->references('id')->on('fleets');
            $table->foreign('personell_id')->references('id')->on('personell');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_personell');
    }
};
