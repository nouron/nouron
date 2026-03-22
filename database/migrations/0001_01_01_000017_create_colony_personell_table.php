<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colony_personell', function (Blueprint $table) {
            $table->integer('colony_id');
            $table->integer('personell_id');
            $table->integer('level')->default(0);
            $table->integer('status_points')->default(10);

            $table->primary(['colony_id', 'personell_id']);
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
            $table->foreign('personell_id')->references('id')->on('personell');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colony_personell');
    }
};
