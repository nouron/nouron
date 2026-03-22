<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trade_researches', function (Blueprint $table) {
            $table->integer('colony_id');
            $table->integer('direction')->default(0);
            $table->integer('research_id');
            $table->bigInteger('amount')->default(0);
            $table->integer('price')->default(0);
            $table->integer('restriction')->nullable();

            $table->primary(['colony_id', 'research_id']);
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
            $table->foreign('research_id')->references('id')->on('researches');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_researches');
    }
};
