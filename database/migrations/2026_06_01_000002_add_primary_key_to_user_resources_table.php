<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite does not support ALTER TABLE ADD PRIMARY KEY.
 * This migration recreates user_resources with user_id as the explicit
 * primary key, preserving all existing rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            // Copy existing data to a temporary table.
            DB::statement('CREATE TABLE user_resources_backup AS SELECT * FROM user_resources');

            Schema::drop('user_resources');

            DB::statement('
                CREATE TABLE user_resources (
                    user_id  INTEGER NOT NULL PRIMARY KEY,
                    credits  INTEGER NOT NULL,
                    supply   INTEGER NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES "user" (user_id)
                )
            ');

            DB::statement('INSERT INTO user_resources SELECT * FROM user_resources_backup');

            DB::statement('DROP TABLE user_resources_backup');
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement('CREATE TABLE user_resources_backup AS SELECT * FROM user_resources');

            Schema::drop('user_resources');

            DB::statement('
                CREATE TABLE user_resources (
                    user_id  INTEGER NOT NULL UNIQUE,
                    credits  INTEGER NOT NULL,
                    supply   INTEGER NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES "user" (user_id)
                )
            ');

            DB::statement('INSERT INTO user_resources SELECT * FROM user_resources_backup');

            DB::statement('DROP TABLE user_resources_backup');
        });
    }
};
