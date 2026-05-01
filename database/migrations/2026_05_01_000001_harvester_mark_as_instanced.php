<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('buildings')->where('id', 27)->update(['is_instanced' => true]);
    }

    public function down(): void
    {
        DB::table('buildings')->where('id', 27)->update(['is_instanced' => false]);
    }
};
