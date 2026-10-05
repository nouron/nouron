<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

/**
 * R5b: tile lookups ordered only by a non-unique column (resource_amount, ring)
 * returned an arbitrary tile among ties — on MySQL that order depends on the
 * physical layout of the shared table, so the same seed relocated its
 * Harvester to (0,-3) in the 8-bot batch but to (1,-3) when run alone. Ties
 * must break on the tile coordinates (q, r).
 */
class BotStrategyTileTieBreakTest extends TestCase
{
    use RefreshDatabase;

    private function candidate(string $method, BotSession $bot): ?object
    {
        return (new ReflectionMethod(BotStrategy::class, $method))->invoke(null, $bot);
    }

    public function test_relocation_target_ties_break_on_the_lowest_coordinates(): void
    {
        $bot = BotSession::boot($this, seed: 11);
        $harvester = DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', 27)->first();
        DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->update(['resource_amount' => 0]);
        DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->whereIn('ring', [1, 2])
            ->where(fn ($q) => $q->where('q', '!=', $harvester->tile_x)->orWhere('r', '!=', $harvester->tile_y))
            ->update(['tile_type' => 'regolith_y1_d2', 'is_explored' => 1, 'resource_amount' => 100, 'resource_max' => 450]);
        // Reverse physical order: the expected winner (lowest q, then r) is written last.
        $expected = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('tile_type', 'regolith_y1_d2')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('colony_buildings as cb')
                ->where('cb.colony_id', $bot->colonyId)->whereColumn('cb.tile_x', 'colony_tiles.q')->whereColumn('cb.tile_y', 'colony_tiles.r'))
            ->orderBy('q')->orderBy('r')->first();
        DB::table('colony_tiles')->where('id', $expected->id)->delete();
        unset($expected->id);
        DB::table('colony_tiles')->insert((array) $expected);

        $tile = $this->candidate('harvesterRelocateCandidate', $bot);

        $this->assertNotNull($tile);
        $this->assertSame([(int) $expected->q, (int) $expected->r], [(int) $tile->q, (int) $tile->r]);
    }

    public function test_exploration_target_ties_break_on_the_lowest_coordinates(): void
    {
        $bot = BotSession::boot($this, seed: 11);
        DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('ring', 3)->update(['is_explored' => 0, 'is_colony_zone' => 0]);
        DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('ring', '<', 3)->update(['is_explored' => 1]);
        $expected = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('ring', 3)->orderBy('q')->orderBy('r')->first();
        DB::table('colony_tiles')->where('id', $expected->id)->delete();
        unset($expected->id);
        DB::table('colony_tiles')->insert((array) $expected);

        $tile = $this->candidate('exploreCandidate', $bot);

        $this->assertNotNull($tile);
        $this->assertSame([(int) $expected->q, (int) $expected->r], [(int) $tile->q, (int) $tile->r]);
    }
}
