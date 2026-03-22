<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_resources', function (Blueprint $table) {
            $table->integer('fleet_id');
            $table->integer('resource_id');
            $table->integer('amount');

            $table->primary(['fleet_id', 'resource_id']);
            $table->foreign('fleet_id')->references('id')->on('fleets');
            $table->foreign('resource_id')->references('id')->on('resources');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_resources');
    }
};
