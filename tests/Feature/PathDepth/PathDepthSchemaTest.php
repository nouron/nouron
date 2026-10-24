<?php

namespace Tests\Feature\PathDepth;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PathDepthSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_exists_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('colony_path_levels'));
        foreach (['colony_id', 'kind', 'entity_key', 'level', 'ap_spend'] as $column) {
            $this->assertTrue(Schema::hasColumn('colony_path_levels', $column), "missing $column");
        }
    }

    public function test_primary_key_is_colony_kind_key(): void
    {
        $primary = collect(Schema::getIndexes('colony_path_levels'))->firstWhere('primary', true);

        $this->assertNotNull($primary);
        $this->assertSame(['colony_id', 'kind', 'entity_key'], $primary['columns']);
    }

    public function test_every_catalog_mission_belongs_to_exactly_one_family(): void
    {
        $assigned = collect(config('path_depth.families'))->flatten()->all();

        foreach (array_keys(config('missions.catalog')) as $mission) {
            $this->assertSame(1, count(array_keys($assigned, $mission, true)), "$mission must be in exactly one family");
        }
        $this->assertSame(count($assigned), count(array_unique($assigned)));
    }

    public function test_levelup_costs_are_progressive(): void
    {
        foreach (['mission_family', 'character'] as $kind) {
            $costs = config("path_depth.levelup_ap.$kind");
            $this->assertCount(config('path_depth.max_level'), $costs);
            $sorted = $costs;
            sort($sorted);
            $this->assertSame($sorted, array_values($costs));
            $this->assertCount(count($costs), array_unique($costs), 'strictly increasing');
        }
    }

    public function test_unknown_colony_is_rejected_by_foreign_key(): void
    {
        $this->expectException(QueryException::class);
        DB::table('colony_path_levels')->insert(['colony_id' => 999999, 'kind' => 'mission_family', 'entity_key' => 'salvage']);
    }
}
