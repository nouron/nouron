<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Note: the original schema has no PRIMARY KEY on trade_resources.
        // A unique constraint could be added later if business logic requires it.
        Schema::create('trade_resources', function (Blueprint $table) {
            $table->integer('colony_id');
            $table->integer('direction')->default(0);
            $table->integer('resource_id');
            $table->bigInteger('amount')->default(0);
            $table->integer('price')->default(0);
            $table->integer('restriction')->default(0);

            $table->foreign('colony_id')->references('id')->on('glx_colonies');
            $table->foreign('resource_id')->references('id')->on('resources');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trade_resources');
    }
};
