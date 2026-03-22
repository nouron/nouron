<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_researches', function (Blueprint $table) {
            $table->integer('fleet_id');
            $table->integer('research_id');
            $table->integer('count');
            $table->integer('is_cargo')->default(0);

            $table->unique(['fleet_id', 'research_id', 'is_cargo'], 'fleet_researches_unique');
            $table->foreign('fleet_id')->references('id')->on('fleets');
            $table->foreign('research_id')->references('id')->on('researches');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_researches');
    }
};
