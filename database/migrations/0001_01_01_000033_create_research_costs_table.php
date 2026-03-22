<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_costs', function (Blueprint $table) {
            $table->integer('research_id');
            $table->integer('resource_id');
            $table->integer('amount');

            $table->primary(['research_id', 'resource_id']);
            $table->foreign('research_id')->references('id')->on('researches');
            $table->foreign('resource_id')->references('id')->on('resources');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_costs');
    }
};
