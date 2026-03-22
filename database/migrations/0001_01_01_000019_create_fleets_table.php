<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleets', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->text('fleet');
            $table->integer('user_id');
            $table->integer('artefact')->nullable();
            $table->integer('x');
            $table->integer('y');
            $table->integer('spot')->default(0);

            $table->foreign('user_id')->references('user_id')->on('user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleets');
    }
};
