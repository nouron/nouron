<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ship_costs', function (Blueprint $table) {
            $table->integer('ship_id');
            $table->integer('resource_id');
            $table->integer('amount');

            $table->primary(['ship_id', 'resource_id']);
            $table->foreign('ship_id')->references('id')->on('ships');
            $table->foreign('resource_id')->references('id')->on('resources');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ship_costs');
    }
};
