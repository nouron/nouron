<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The CHECK constraint on 'topic' is not natively supported by Laravel's
        // Blueprint for SQLite. We add it via a raw DB::statement after table creation.
        // NOTE: SQLite-specific approach — revisit before prod migration.
        Schema::create('innn_news', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('tick');
            $table->text('icon');
            $table->text('topic');
            $table->text('headline');
            $table->text('text');
        });

        \DB::statement(
            "CREATE TRIGGER innn_news_topic_check
             BEFORE INSERT ON innn_news
             BEGIN
                 SELECT CASE
                     WHEN NEW.topic NOT IN ('economy','politics','diplomacy','culture','sports','misc')
                     THEN RAISE(ABORT, 'Invalid value for topic')
                 END;
             END"
        );

        \DB::statement(
            "CREATE TRIGGER innn_news_topic_check_update
             BEFORE UPDATE ON innn_news
             BEGIN
                 SELECT CASE
                     WHEN NEW.topic NOT IN ('economy','politics','diplomacy','culture','sports','misc')
                     THEN RAISE(ABORT, 'Invalid value for topic')
                 END;
             END"
        );
    }

    public function down(): void
    {
        \DB::statement('DROP TRIGGER IF EXISTS innn_news_topic_check');
        \DB::statement('DROP TRIGGER IF EXISTS innn_news_topic_check_update');
        Schema::dropIfExists('innn_news');
    }
};
