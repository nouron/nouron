<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 'order' is a reserved SQL keyword — Laravel wraps column names in quotes
        // automatically for SQLite, so using the string 'order' here is safe.
        Schema::create('fleet_orders', function (Blueprint $table) {
            $table->integer('tick');
            $table->integer('fleet_id');
            $table->text('order');
            $table->text('coordinates');
            $table->text('data')->nullable();
            $table->integer('was_processed')->default(0);
            $table->integer('has_notified')->default(0);

            $table->primary(['tick', 'fleet_id']);
            $table->foreign('fleet_id')->references('id')->on('fleets');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_orders');
    }
};
