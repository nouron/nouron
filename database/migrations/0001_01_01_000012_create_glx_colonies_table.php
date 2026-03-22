<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('glx_colonies', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->text('name')->default('Colony');
            $table->integer('system_object_id');
            $table->integer('spot');
            $table->integer('user_id')->nullable();
            $table->integer('since_tick');
            $table->integer('is_primary')->default(0);

            $table->foreign('system_object_id')->references('id')->on('glx_system_objects');
            $table->foreign('user_id')->references('user_id')->on('user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('glx_colonies');
    }
};
