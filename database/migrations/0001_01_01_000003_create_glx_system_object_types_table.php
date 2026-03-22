<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('glx_system_object_types', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->text('type');
            $table->text('image_url');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('glx_system_object_types');
    }
};
