<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 'text' is used as a column name here — it is also a Blueprint method,
        // but as a string argument to ->text('text') it is unambiguous in Laravel.
        Schema::create('innn_messages', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('sender_id');
            $table->text('attitude');
            $table->integer('recipient_id');
            $table->integer('tick');
            $table->integer('type');
            $table->text('subject');
            $table->text('text');
            $table->integer('is_read')->default(0);
            $table->integer('is_archived')->default(0);
            $table->integer('is_deleted')->default(0);

            $table->foreign('sender_id')->references('user_id')->on('user');
            $table->foreign('recipient_id')->references('user_id')->on('user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('innn_messages');
    }
};
