<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ships')->where('name', 'ship_probe')->update(['name' => 'ship_drone']);
    }

    public function down(): void
    {
        DB::table('ships')->where('name', 'ship_drone')->update(['name' => 'ship_probe']);
    }
};
