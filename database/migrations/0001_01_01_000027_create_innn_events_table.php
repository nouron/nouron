<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('innn_events', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('user');
            $table->integer('tick');
            $table->text('event');
            $table->text('area');
            $table->text('parameters');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('innn_events');
    }
};
