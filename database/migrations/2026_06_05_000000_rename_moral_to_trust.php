<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Renames the moral_events table to trust_events and updates the
     * resource abbreviation slug from res_moral to res_trust.
     */
    public function up(): void
    {
        Schema::rename('moral_events', 'trust_events');

        DB::table('resources')
            ->where('name', 'res_moral')
            ->update(['name' => 'res_trust']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('trust_events', 'moral_events');

        DB::table('resources')
            ->where('name', 'res_trust')
            ->update(['name' => 'res_moral']);
    }
};
