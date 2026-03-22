<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('researches', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->text('purpose');
            $table->text('name')->unique();
            $table->integer('required_building_id')->nullable();
            $table->integer('required_building_level')->nullable();
            $table->integer('row');
            $table->integer('column');
            $table->integer('ap_for_levelup')->default(1);
            $table->integer('max_status_points')->nullable();

            $table->unique(['row', 'column'], 'researches_row_column');
            $table->foreign('required_building_id')->references('id')->on('buildings');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('researches');
    }
};
