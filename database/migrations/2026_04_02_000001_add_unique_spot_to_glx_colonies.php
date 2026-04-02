<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('glx_colonies', function (Blueprint $table) {
            $table->unique(['system_object_id', 'spot'], 'glx_colonies_system_object_spot_unique');
        });
    }

    public function down(): void
    {
        Schema::table('glx_colonies', function (Blueprint $table) {
            $table->dropUnique('glx_colonies_system_object_spot_unique');
        });
    }
};
