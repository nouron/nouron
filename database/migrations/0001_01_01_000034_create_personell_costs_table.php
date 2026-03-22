<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Note: original schema has no PRIMARY KEY or UNIQUE constraint on personell_costs.
        Schema::create('personell_costs', function (Blueprint $table) {
            $table->integer('personell_id');
            $table->integer('resource_id');
            $table->integer('amount');

            $table->foreign('personell_id')->references('id')->on('personell');
            $table->foreign('resource_id')->references('id')->on('resources');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personell_costs');
    }
};
