<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_costs', function (Blueprint $table) {
            $table->integer('building_id');
            $table->integer('resource_id');
            $table->integer('amount');

            $table->unique(['building_id', 'resource_id'], 'building_resource');
            $table->foreign('building_id')->references('id')->on('buildings');
            $table->foreign('resource_id')->references('id')->on('resources');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_costs');
    }
};
