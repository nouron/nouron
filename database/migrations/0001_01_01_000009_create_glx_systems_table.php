<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('glx_systems', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('x');
            $table->integer('y');
            $table->text('name');
            $table->integer('type_id');
            $table->text('background_image_url');
            $table->integer('sight')->default(9);
            $table->integer('density')->default(0);
            $table->integer('radiation')->default(0);

            $table->foreign('type_id')->references('id')->on('glx_system_types');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('glx_systems');
    }
};
