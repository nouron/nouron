<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The user table uses user_id as PK (not the Laravel default 'id').
        // The self-referencing FK (user_id REFERENCES user(user_id)) is intentionally
        // omitted — it has no semantic meaning in the original schema.
        Schema::create('user', function (Blueprint $table) {
            $table->integer('user_id')->primary();
            $table->text('username')->unique();
            $table->text('display_name');
            $table->text('role');
            $table->text('password');
            $table->text('email')->unique();
            $table->integer('state')->nullable();
            $table->integer('race_id')->nullable();
            $table->integer('faction_id')->nullable();
            $table->text('description')->nullable();
            $table->text('note')->nullable();
            $table->integer('disabled')->default(0);
            $table->integer('activated')->default(0);
            $table->text('activation_key');
            $table->integer('first_time_login')->default(1);
            $table->timestamp('last_activity')->useCurrent();
            $table->timestamp('registration')->default('0000-00-00 00:00:00');
            $table->text('theme')->default('darkred');
            $table->integer('tooltips_enabled')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};
