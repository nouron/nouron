<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colony_resources', function (Blueprint $table) {
            $table->index('colony_id');
        });
    }

    public function down(): void
    {
        Schema::table('colony_resources', function (Blueprint $table) {
            $table->dropIndex(['colony_id']);
        });
    }
};
