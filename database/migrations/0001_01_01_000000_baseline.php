<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline schema (R5b): replaces the 132 historical migrations, which remain
 * in the Git history. Creates the final schema on SQLite and MySQL with the
 * schema builder and inserts no data — reference data comes from
 * ReferenceDataSeeder, fixtures from data/sql/testdata.sql.
 *
 * Fixtures insert with named columns. All integers are signed (auto-increment PKs via integer('id', true)) so that foreign keys
 * are type-compatible on MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->integer('id');
            $table->text('name');
            $table->text('abbreviation');
            $table->primary('id');
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->integer('id');
            $table->text('purpose');
            $table->string('name', 255);
            $table->integer('required_building_id')->nullable();
            $table->integer('required_building_level')->nullable();
            $table->integer('row');
            $table->integer('column');
            $table->integer('max_level')->nullable();
            $table->integer('ap_for_levelup')->default(1);
            $table->integer('max_status_points')->nullable();
            $table->double('decay_rate')->nullable();
            $table->integer('supply_cost')->nullable();
            $table->boolean('is_instanced')->default(false);
            $table->integer('is_active')->default(1);
            $table->integer('phase')->default(0);
            $table->integer('max_instances')->nullable();
            $table->primary('id');
            $table->unique(['name'], 'buildings_name_unique');
            $table->foreign('required_building_id')->references('id')->on('buildings');
        });

        Schema::create('researches', function (Blueprint $table) {
            $table->integer('id');
            $table->text('purpose');
            $table->string('name', 255);
            $table->integer('required_building_id')->nullable();
            $table->integer('required_building_level')->nullable();
            $table->integer('row');
            $table->integer('column');
            $table->integer('ap_for_levelup')->default(1);
            $table->integer('max_status_points')->nullable();
            $table->double('decay_rate')->nullable();
            $table->integer('supply_cost')->nullable();
            $table->integer('is_active')->default(1);
            $table->integer('required_building2_id')->nullable();
            $table->integer('required_building2_level')->nullable()->default(1);
            $table->integer('phase')->default(0);
            $table->primary('id');
            $table->unique(['name'], 'researches_name_unique');
            $table->foreign('required_building_id')->references('id')->on('buildings');
        });

        Schema::create('user', function (Blueprint $table) {
            $table->integer('user_id', true);
            $table->string('username', 255);
            $table->text('display_name');
            $table->text('role');
            $table->text('password');
            $table->string('email', 255);
            $table->integer('activated')->default(0);
            $table->text('activation_key');
            $table->dateTime('registration')->useCurrent();
            $table->string('remember_token', 255)->nullable();
            $table->unique(['email'], 'user_email_unique');
            $table->unique(['username'], 'user_username_unique');
        });

        Schema::create('glx_colonies', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 255)->default('Colony');
            $table->integer('user_id')->nullable();
            $table->integer('is_primary')->default(0);
            $table->integer('hunger_streak')->default(0);
            $table->integer('plague_until_tick')->nullable();
            $table->float('plague_ap_reduction_pct')->nullable();
            $table->integer('overcap_streak')->default(0);
            $table->integer('overcap_departed')->default(0);
            $table->foreign('user_id')->references('user_id')->on('user');
        });

        Schema::create('colony_resources', function (Blueprint $table) {
            $table->integer('resource_id');
            $table->integer('colony_id');
            $table->integer('amount')->default(0);
            $table->primary(['resource_id', 'colony_id']);
            $table->index(['colony_id'], 'colony_resources_colony_id_index');
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
            $table->foreign('resource_id')->references('id')->on('resources');
        });

        Schema::create('colony_researches', function (Blueprint $table) {
            $table->integer('colony_id');
            $table->integer('research_id');
            $table->integer('level')->default(0);
            $table->double('status_points')->default(20);
            $table->integer('ap_spend')->default(0);
            $table->primary(['colony_id', 'research_id']);
            $table->foreign('research_id')->references('id')->on('researches');
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });

        Schema::create('building_costs', function (Blueprint $table) {
            $table->integer('building_id');
            $table->integer('resource_id');
            $table->integer('amount');
            $table->unique(['building_id', 'resource_id'], 'building_resource');
            $table->foreign('resource_id')->references('id')->on('resources');
            $table->foreign('building_id')->references('id')->on('buildings');
        });

        Schema::create('locked_actionpoints', function (Blueprint $table) {
            $table->integer('tick');
            $table->string('scope_type', 255);
            $table->integer('scope_id');
            $table->integer('personell_id');
            $table->integer('spend_ap')->default(0);
            $table->primary(['tick', 'scope_type', 'scope_id', 'personell_id']);
        });

        Schema::create('trust_events', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('colony_id');
            $table->integer('tick');
            $table->string('event_type', 255);
            $table->index(['colony_id', 'tick'], 'moral_events_colony_id_tick_index');
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });

        Schema::create('colony_tiles', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('colony_id');
            $table->integer('q');
            $table->integer('r');
            $table->integer('ring');
            $table->string('tile_type', 255);
            $table->string('event_type', 255)->nullable();
            $table->boolean('is_colony_zone')->default(false);
            $table->boolean('is_explored')->default(false);
            $table->boolean('is_deep_scanned')->default(false);
            $table->integer('resource_amount')->nullable();
            $table->integer('resource_max')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['colony_id', 'q', 'r'], 'colony_tiles_colony_q_r_unique');
            $table->foreign('colony_id')->references('id')->on('glx_colonies')->cascadeOnDelete();
        });

        Schema::create('colony_buildings', function (Blueprint $table) {
            $table->integer('colony_id');
            $table->integer('building_id');
            $table->integer('instance_id')->default(1);
            $table->integer('level')->default(0);
            $table->double('status_points')->default(20);
            $table->integer('ap_spend')->default(0);
            $table->integer('tile_x')->nullable();
            $table->integer('tile_y')->nullable();
            $table->integer('pending_until_tick')->nullable();
            $table->integer('placed_at_tick')->nullable();
            $table->integer('instability_outage_until_tick')->nullable();
            $table->unique(['colony_id', 'building_id', 'instance_id'], 'colony_building');
            $table->foreign('building_id')->references('id')->on('buildings');
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });

        Schema::create('user_preferences', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id');
            $table->boolean('onboarding_hints')->default(true);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->text('dismissed_hints')->nullable();
            $table->text('fired_triggers')->nullable();
            $table->boolean('sol_report_skip')->default(false);
            $table->unique(['user_id'], 'user_preferences_user_id_unique');
            $table->foreign('user_id')->references('user_id')->on('user')->cascadeOnDelete();
        });

        Schema::create('personell', function (Blueprint $table) {
            $table->integer('id');
            $table->text('purpose');
            $table->string('name', 255);
            $table->integer('required_building_id')->nullable();
            $table->integer('required_building_level')->nullable();
            $table->integer('row');
            $table->integer('column');
            $table->integer('max_status_points')->nullable();
            $table->integer('is_active')->default(1);
            $table->integer('phase')->default(0);
            $table->primary('id');
            $table->unique(['name'], 'personell_name_unique');
            $table->foreign('required_building_id')->references('id')->on('buildings');
        });

        Schema::create('merchant_visits', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('colony_id');
            $table->integer('tick_start');
            $table->integer('tick_end');
            $table->boolean('was_visited')->default(false);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });

        Schema::create('merchant_items', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('visit_id');
            $table->string('item_type', 255);
            $table->string('label', 255);
            $table->integer('cost_credits');
            $table->text('payload')->nullable();
            $table->boolean('sold')->default(false);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->foreign('visit_id')->references('id')->on('merchant_visits')->cascadeOnDelete();
        });

        Schema::create('runs', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id');
            $table->integer('colony_id');
            $table->integer('current_tick')->default(0);
            $table->string('status', 255)->default('active');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->text('settings')->nullable();
            $table->integer('phase')->default(1);
            $table->string('fail_reason', 50)->nullable();
            $table->integer('nexus_debt')->default(3000);
            $table->integer('phase2_start_tick')->nullable();
            $table->bigInteger('rng_seed')->nullable();
            $table->integer('score')->nullable();
            $table->index(['user_id', 'status'], 'runs_user_id_status_index');
            $table->foreign('colony_id')->references('id')->on('glx_colonies')->cascadeOnDelete();
            $table->foreign('user_id')->references('user_id')->on('user')->cascadeOnDelete();
        });

        Schema::create('run_objectives', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('run_id');
            $table->string('task_key', 30);
            $table->integer('target_value')->default(0);
            $table->integer('current_value')->default(0);
            $table->integer('streak_value')->default(0);
            $table->integer('completed_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->integer('best_streak_value')->default(0);
            $table->index(['run_id'], 'run_objectives_run_id_index');
            $table->foreign('run_id')->references('id')->on('runs')->cascadeOnDelete();
        });

        Schema::create('user_resources', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('credits');
            $table->integer('supply');
            $table->primary('user_id');
            $table->foreign('user_id')->references('user_id')->on('user');
        });

        Schema::create('ships', function (Blueprint $table) {
            $table->integer('id', true);
            $table->text('purpose');
            $table->string('name', 255);
            $table->integer('required_building_id')->nullable();
            $table->integer('required_building_level')->nullable();
            $table->integer('row');
            $table->integer('column');
            $table->integer('ap_for_levelup')->default(1);
            $table->integer('max_status_points')->nullable();
            $table->integer('is_active')->default(1);
            $table->integer('phase')->default(0);
            $table->unique(['name'], 'ships_name_unique');
            $table->unique(['phase', 'row', 'column'], 'ships_phase_row_col');
            $table->foreign('required_building_id')->references('id')->on('buildings');
        });

        Schema::create('colony_hangar_missions', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('colony_id');
            $table->integer('instance_id');
            $table->integer('ship_id');
            $table->text('destination');
            $table->integer('sol_distance');
            $table->integer('dispatch_tick');
            $table->integer('recall_tick')->nullable();
            $table->string('state', 255)->default('active');
            $table->dateTime('created_at')->useCurrent();
            $table->text('target')->nullable();
            $table->string('difficulty', 255)->default('normal');
            $table->boolean('succeeded')->nullable();
            $table->index(['colony_id', 'instance_id'], 'colony_hangar_missions_colony_id_instance_id_index');
            $table->index(['colony_id', 'state'], 'colony_hangar_missions_colony_id_state_index');
            $table->foreign('ship_id')->references('id')->on('ships');
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });

        Schema::create('colony_ships', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('colony_id');
            $table->integer('ship_id');
            $table->integer('level')->default(0);
            $table->double('status_points')->default(10);
            $table->integer('ap_spend')->default(0);
            $table->integer('hangar_instance_id')->nullable();
            $table->string('ship_state', 255)->default('docked');
            $table->integer('deliver_at_tick')->nullable();
            $table->integer('pending_until_tick')->nullable();
            $table->foreign('ship_id')->references('id')->on('ships');
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });

        Schema::create('colony_log', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user');
            $table->integer('tick');
            $table->string('event', 255);
            $table->string('area', 255);
            $table->text('parameters');
            $table->dateTime('created_at')->nullable();
            $table->boolean('is_read')->default(true);
        });

        Schema::create('advisors', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id');
            $table->integer('personell_id');
            $table->integer('colony_id')->nullable();
            $table->integer('rank')->default(1);
            $table->integer('active_ticks')->default(0);
            $table->integer('unavailable_until_tick')->nullable();
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
            $table->foreign('personell_id')->references('id')->on('personell');
            $table->foreign('user_id')->references('user_id')->on('user');
        });

        Schema::create('bar_offers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('colony_id');
            $table->integer('give_resource_id');
            $table->integer('give_amount');
            $table->integer('get_resource_id');
            $table->integer('get_amount');
            $table->integer('expires_tick');
            $table->boolean('is_accepted')->default(false);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->boolean('is_negotiated')->default(false);
            $table->integer('visit_id')->nullable();
            $table->foreign('visit_id')->references('id')->on('merchant_visits')->cascadeOnDelete();
        });

        Schema::create('nexus_imports', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('colony_id');
            $table->integer('resource_id');
            $table->integer('amount');
            $table->integer('deliver_at_tick');
            $table->index(['deliver_at_tick'], 'nexus_imports_deliver_at_tick_index');
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });

        Schema::create('bar_encounters', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('colony_id');
            $table->string('type', 255);
            $table->integer('give_resource_id')->nullable();
            $table->integer('give_amount')->nullable();
            $table->float('win_chance')->nullable();
            $table->integer('credits_amount')->nullable();
            $table->integer('duration_ticks')->nullable();
            $table->integer('expires_tick');
            $table->boolean('is_accepted')->default(false);
            $table->boolean('resolved')->default(false);
            $table->boolean('won')->nullable();
            $table->integer('ends_tick')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('colony_bartender_state', function (Blueprint $table) {
            $table->integer('colony_id');
            $table->integer('interaction_count')->default(0);
            $table->integer('last_interaction_tick')->nullable();
            $table->primary('colony_id');
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });

        Schema::create('bar_concerns', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('colony_id');
            $table->string('character_slug', 255);
            $table->integer('created_tick');
            $table->integer('expires_tick');
            $table->boolean('is_resolved')->default(false);
            $table->boolean('success')->nullable();
            $table->text('outcome')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['colony_id', 'character_slug', 'created_tick'], 'bar_concerns_colony_id_character_slug_created_tick_index');
            $table->index(['colony_id', 'is_resolved', 'expires_tick'], 'bar_concerns_colony_id_is_resolved_expires_tick_index');
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });

        Schema::create('colony_building_discount_vouchers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('colony_id');
            $table->integer('discount_pct');
            $table->string('source', 255);
            $table->integer('granted_tick');
            $table->integer('expires_tick')->nullable();
            $table->integer('consumed_tick')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['colony_id', 'consumed_tick'], 'colony_building_discount_vouchers_colony_id_consumed_tick_index');
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });

        Schema::create('character_codex_entries', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id');
            $table->string('character_slug', 255);
            $table->integer('entry_number');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['user_id', 'character_slug', 'entry_number'], 'character_codex_entries_user_slug_entry_unique');
            $table->foreign('user_id')->references('user_id')->on('user')->cascadeOnDelete();
        });

        Schema::create('colony_information_pool_state', function (Blueprint $table) {
            $table->integer('colony_id');
            $table->boolean('deva_buff_used')->default(false);
            $table->boolean('deva_knowledge_used')->default(false);
            $table->boolean('lenn_nav_used')->default(false);
            $table->boolean('lenn_knowledge_used')->default(false);
            $table->boolean('active_drill_buffer')->default(false);
            $table->boolean('active_nav_voucher')->default(false);
            $table->primary('colony_id');
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });

        Schema::create('bar_information_encounters', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('colony_id');
            $table->string('character_slug', 255);
            $table->string('outcome_key', 255);
            $table->integer('created_tick');
            $table->integer('expires_tick');
            $table->boolean('is_resolved')->default(false);
            $table->text('outcome')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['colony_id', 'is_resolved', 'expires_tick'], 'bar_information_encounters_colony_resolved_expires_index');
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });

        Schema::create('feedback', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id');
            $table->integer('run_id')->nullable();
            $table->integer('sol')->nullable();
            $table->string('category', 255);
            $table->text('message');
            $table->string('page', 255)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['created_at'], 'feedback_created_at_index');
            $table->foreign('user_id')->references('user_id')->on('user')->cascadeOnDelete();
        });

        // Unique tech-tree position per phase, only for placed entries (phase > 0);
        // phase-0 entries may share a position. MySQL has no partial indexes: a
        // functional key part that is NULL for phase 0 gives the same semantics
        // (NULLs never collide in a unique index) without adding a visible column.
        foreach (['buildings', 'researches', 'personell'] as $t) {
            DB::statement(DB::getDriverName() === 'mysql'
                ? "CREATE UNIQUE INDEX `{$t}_phase_row_col` ON `$t` ((CASE WHEN `phase` > 0 THEN `phase` END), `row`, `column`)"
                : "CREATE UNIQUE INDEX \"{$t}_phase_row_col\" ON \"$t\" (\"phase\",\"row\",\"column\") WHERE \"phase\" > 0");
        }

        // Framework tables (Laravel defaults; sessions.user_id signed int like user.user_id).
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key', 255)->primary();
            $table->mediumText('value');
            $table->bigInteger('expiration')->index();
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key', 255)->primary();
            $table->string('owner', 255);
            $table->bigInteger('expiration')->index();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 255)->primary();
            $table->integer('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // migrate:fresh drops tables but not views on MySQL (without --drop-views),
        // so a view left over from a previous run must be replaced.
        DB::statement('DROP VIEW IF EXISTS v_glx_colonies');
        DB::statement('CREATE VIEW v_glx_colonies AS SELECT * FROM glx_colonies');
    }

    public function down(): void
    {
        throw new RuntimeException('Baseline is not reversible — use migrate:fresh');
    }
};
