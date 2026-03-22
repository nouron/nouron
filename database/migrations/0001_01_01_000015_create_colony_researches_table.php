<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colony_researches', function (Blueprint $table) {
            $table->integer('colony_id');
            $table->integer('research_id');
            $table->integer('level')->default(0);
            $table->integer('status_points')->default(10);
            $table->integer('ap_spend')->default(0);

            $table->primary(['colony_id', 'research_id']);
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
            $table->foreign('research_id')->references('id')->on('researches');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colony_researches');
    }
};
