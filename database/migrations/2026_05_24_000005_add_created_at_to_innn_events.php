<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite supports ADD COLUMN for nullable columns without constraints.
        DB::statement('ALTER TABLE innn_events ADD COLUMN created_at DATETIME');
    }

    public function down(): void
    {
        // SQLite cannot drop columns — rebuild the table without created_at.
        DB::statement('CREATE TABLE innn_events_backup AS SELECT id, user, tick, event, area, parameters FROM innn_events');
        Schema::drop('innn_events');
        DB::statement('ALTER TABLE innn_events_backup RENAME TO innn_events');
    }
};
