<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * R5b/3: remove legacy tables and columns before the schema is squashed into a
 * baseline. Verified unread (grep over app/routes/resources/public/js/config/
 * seeders/factories/tests/lang) — see the R5b plan, Task 3.
 *
 * Kept on purpose: user.registration (shown on the profile page).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Views freezing SELECT * must go before their base columns/tables.
        DB::statement('DROP VIEW IF EXISTS v_trade_resources');
        DB::statement('DROP VIEW IF EXISTS v_glx_colonies');

        foreach (['trade_resources', 'personell_costs', 'colony_personell', 'research_costs', 'ship_costs'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('user', fn (Blueprint $t) => $t->dropColumn([
            'faction_id', 'description', 'note', 'state', 'theme', 'tooltips_enabled',
            'first_time_login', 'last_activity', 'disabled',
        ]));
        Schema::table('glx_colonies', fn (Blueprint $t) => $t->dropColumn('since_tick'));
        Schema::table('resources', fn (Blueprint $t) => $t->dropColumn(['start_amount', 'is_tradeable', 'trigger', 'icon']));
        Schema::table('buildings', fn (Blueprint $t) => $t->dropColumn('prime_colony_only'));
        Schema::table('ships', function (Blueprint $t) {
            $t->dropForeign(['required_research_id']);
            $t->dropColumn(['prime_colony_only', 'required_research_id', 'required_research_level', 'moving_speed', 'decay_rate', 'supply_cost']);
        });

        // Same passthrough definition as before (SELECT * freezes the column list at creation).
        DB::statement('CREATE VIEW v_glx_colonies AS SELECT * FROM glx_colonies');
    }

    public function down(): void
    {
        // Not reversible: legacy removal (use migrate:fresh).
    }
};
