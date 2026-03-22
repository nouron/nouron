<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ships', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->text('purpose');
            $table->text('name')->unique();
            $table->integer('required_building_id')->nullable();
            $table->integer('required_building_level')->nullable();
            $table->integer('required_research_id')->nullable();
            $table->integer('required_research_level')->nullable();
            $table->integer('prime_colony_only')->default(0);
            $table->integer('row');
            $table->integer('column');
            $table->integer('ap_for_levelup')->default(1);
            $table->integer('max_status_points')->nullable();
            $table->integer('moving_speed')->nullable();

            $table->unique(['row', 'column'], 'ships_row_column');
            $table->foreign('required_building_id')->references('id')->on('buildings');
            $table->foreign('required_research_id')->references('id')->on('researches');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ships');
    }
};
