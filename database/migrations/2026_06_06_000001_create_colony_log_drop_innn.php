<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create colony_log (replaces innn_events) with added is_read flag
        Schema::create('colony_log', function (Blueprint $table) {
            $table->id();
            $table->integer('user');
            $table->integer('tick');
            $table->string('event');
            $table->string('area');
            $table->text('parameters');
            $table->timestamp('created_at')->nullable();
            $table->boolean('is_read')->default(true);
        });

        // Copy existing events; all pre-existing entries are treated as read
        DB::statement('INSERT INTO colony_log (id, user, tick, event, area, parameters, created_at, is_read)
                       SELECT id, user, tick, event, area, parameters, created_at, 1 FROM innn_events');

        Schema::dropIfExists('innn_events');
        DB::statement('DROP VIEW IF EXISTS v_innn_messages');
        Schema::dropIfExists('innn_messages');
        Schema::dropIfExists('innn_news');
        Schema::dropIfExists('innn_message_types');
    }

    public function down(): void
    {
        Schema::create('innn_events', function (Blueprint $table) {
            $table->id();
            $table->integer('user');
            $table->integer('tick');
            $table->string('event');
            $table->string('area');
            $table->text('parameters');
            $table->timestamp('created_at')->nullable();
        });

        DB::statement('INSERT INTO innn_events (id, user, tick, event, area, parameters, created_at)
                       SELECT id, user, tick, event, area, parameters, created_at FROM colony_log');

        Schema::dropIfExists('colony_log');
    }
};
