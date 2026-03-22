<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('glx_system_types', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->text('class');
            $table->integer('size')->default(5);
            $table->text('icon_url')->nullable();
            $table->text('image_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('glx_system_types');
    }
};
