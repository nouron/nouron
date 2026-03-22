<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colony_resources', function (Blueprint $table) {
            $table->integer('resource_id');
            $table->integer('colony_id');
            $table->integer('amount')->default(0);

            $table->primary(['resource_id', 'colony_id']);
            $table->foreign('resource_id')->references('id')->on('resources');
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colony_resources');
    }
};
