<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colony_ships', function (Blueprint $table) {
            $table->integer('colony_id');
            $table->integer('ship_id');
            $table->integer('level')->default(0);
            $table->integer('status_points')->default(10);
            $table->integer('ap_spend')->default(0);

            $table->primary(['colony_id', 'ship_id']);
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
            $table->foreign('ship_id')->references('id')->on('ships');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colony_ships');
    }
};
