<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds planet classification and 12×12 grid position to glx_system_objects.
 *
 * planet_size / planet_type are VARCHAR (not DB ENUMs — SQLite has no ENUM type).
 * Valid values are enforced at the application layer, not via DB constraint.
 *   planet_size: 'small' | 'medium' | 'large'
 *   planet_type: 'rocky' | 'desert' | 'ice' | 'ocean' | 'volcanic'
 *
 * grid_x / grid_y place the object on the 12×12 system view grid (0–11).
 *
 * All four columns are nullable so existing rows are not invalidated.
 *
 * Design context: DS-2 (System View), DS-4 (Tile Catalogue / Planet Types).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('glx_system_objects', function (Blueprint $table) {
            $table->string('planet_size')->nullable()->after('radiation');
            $table->string('planet_type')->nullable()->after('planet_size');
            $table->integer('grid_x')->nullable()->after('planet_type');
            $table->integer('grid_y')->nullable()->after('grid_x');
        });
    }

    public function down(): void
    {
        Schema::table('glx_system_objects', function (Blueprint $table) {
            $table->dropColumn(['planet_size', 'planet_type', 'grid_x', 'grid_y']);
        });
    }
};
