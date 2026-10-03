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
        $tables = collect(Schema::getTables())->pluck('name')->reject(fn ($n) => $n === 'migrations');

        $this->assertCount(37, $tables);
        foreach ($tables as $table) {
            $this->assertSame(0, DB::table($table)->count(), "baseline must not insert data into $table");
        }
        $this->assertContains('v_glx_colonies', collect(Schema::getViews())->pluck('name'));
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
}
