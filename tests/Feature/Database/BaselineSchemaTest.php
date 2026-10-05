<?php

namespace Tests\Feature\Database;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Guards the squashed baseline migration (R5b): it builds the schema without
 * any data, keeps the v_glx_colonies view, and preserves the behaviour of the
 * former SQLite-only details on every driver.
 */
class BaselineSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_baseline_creates_all_tables_and_the_view_without_any_rows(): void
    {
        // Only the test database itself: on MySQL getTables() without a schema lists every
        // database the user can see (e.g. the parallel workers' nouron_test_test_N).
        $schema = DB::connection()->getDatabaseName();
        $tables = collect(Schema::getTables($schema))->pluck('name')->reject(fn ($n) => $n === 'migrations');

        $this->assertCount(37, $tables);
        foreach ($tables as $table) {
            $this->assertSame(0, DB::table($table)->count(), "baseline must not insert data into $table");
        }
        $this->assertContains('v_glx_colonies', collect(Schema::getViews($schema))->pluck('name'));
    }

    public function test_tech_tree_position_is_unique_only_for_placed_entries(): void
    {
        $row = ['purpose' => 'civil', 'row' => 3, 'column' => 4, 'phase' => 0];
        DB::table('buildings')->insert([...$row, 'id' => 901, 'name' => 'building_a']);
        DB::table('buildings')->insert([...$row, 'id' => 902, 'name' => 'building_b']);
        DB::table('buildings')->insert([...$row, 'id' => 903, 'name' => 'building_c', 'phase' => 2]);

        $this->assertSame(3, DB::table('buildings')->whereIn('id', [901, 902, 903])->count(), 'phase-0 entries may share a position');

        $this->expectException(QueryException::class);
        DB::table('buildings')->insert([...$row, 'id' => 904, 'name' => 'building_d', 'phase' => 2]);
    }

    public function test_user_ids_are_assigned_automatically_with_a_registration_date(): void
    {
        $id = DB::table('user')->insertGetId([
            'username' => 'baseline',
            'display_name' => '',
            'role' => 'player',
            'password' => 'x',
            'email' => 'baseline@nouron.de',
            'activation_key' => '',
        ], 'user_id');

        $this->assertGreaterThan(0, $id);
        $this->assertNotNull(DB::table('user')->where('user_id', $id)->value('registration'));
    }

    public function test_colony_ids_are_assigned_automatically(): void
    {
        // glx_colonies is the only player-data table whose id used to be a bare integer PK
        // (SQLite rowid alias); new colonies must get an id from the database, not max()+1.
        $first = DB::table('glx_colonies')->insertGetId(['name' => 'A', 'user_id' => null, 'is_primary' => 0]);
        $second = DB::table('glx_colonies')->insertGetId(['name' => 'B', 'user_id' => null, 'is_primary' => 0]);

        $this->assertGreaterThan(0, $first);
        $this->assertGreaterThan($first, $second);
    }

    /**
     * R5b: colony_log is read and deleted per user (reset, snapshots, Sol report);
     * without an index every such statement scans — and under InnoDB locks — the
     * whole table, which deadlocked parallel onboardings.
     */
    public function test_colony_log_is_indexed_by_user(): void
    {
        $indexed = collect(Schema::getIndexes('colony_log'))->pluck('columns');

        $this->assertTrue($indexed->contains(fn ($columns) => ($columns[0] ?? null) === 'user'), 'colony_log needs an index starting with user');
    }

    /** R5b: MySQL freezes SELECT * and binds a DEFINER view to its creator (error 1449 after a restore). */
    public function test_glx_colonies_view_runs_with_invoker_rights(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('SQL SECURITY exists on MySQL only.');
        }

        $type = DB::selectOne(
            'SELECT SECURITY_TYPE AS t FROM information_schema.views WHERE table_schema = DATABASE() AND table_name = ?',
            ['v_glx_colonies']
        )->t;

        $this->assertSame('INVOKER', $type);
    }

    /** R5b: tick transactions delete/update by these column prefixes; without them the statements lock whole tables. */
    public function test_tick_hot_paths_have_leading_indexes(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Index layout asserted on MySQL only.');
        }

        $expected = [
            'bar_offers' => ['colony_id', 'expires_tick'],
            'bar_encounters' => ['colony_id', 'expires_tick'],
            'locked_actionpoints' => ['scope_type', 'scope_id'],
        ];
        foreach ($expected as $table => $columns) {
            $found = collect(Schema::getIndexes($table))->contains(
                fn ($index) => array_slice($index['columns'], 0, count($columns)) === $columns
            );
            $this->assertTrue($found, "$table needs an index starting with ".implode(', ', $columns));
        }
    }

    /** R5b: MySQL setups with sql_require_primary_key reject keyless tables. */
    public function test_every_base_table_has_a_primary_key(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('information_schema check is MySQL-specific.');
        }

        $keyless = DB::select(
            "SELECT t.table_name AS name FROM information_schema.tables t
             LEFT JOIN information_schema.table_constraints c
               ON c.table_schema = t.table_schema AND c.table_name = t.table_name AND c.constraint_type = 'PRIMARY KEY'
             WHERE t.table_schema = DATABASE() AND t.table_type = 'BASE TABLE' AND c.constraint_name IS NULL"
        );

        $this->assertSame([], array_map(fn ($r) => $r->name, $keyless));
    }

    public function test_baseline_cannot_be_rolled_back(): void
    {
        $migration = require database_path('migrations/0001_01_01_000000_baseline.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Baseline is not reversible');

        $migration->down();
    }
}
