<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('innn_message_types', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->text('type')->unique();
            $table->integer('relationship_effect')->default(0);
            $table->integer('points')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('innn_message_types');
    }
};
