<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn('race_id');
        });
    }

    public function down(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->unsignedBigInteger('race_id')->nullable()->after('state');
        });
    }
};
