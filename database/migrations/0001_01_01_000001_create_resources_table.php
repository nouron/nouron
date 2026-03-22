<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->text('name');
            $table->text('abbreviation');
            $table->text('trigger');
            $table->integer('is_tradeable')->default(1);
            $table->integer('start_amount')->default(0);
            $table->text('icon');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
