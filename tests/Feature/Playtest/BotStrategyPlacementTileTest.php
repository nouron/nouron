<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Baseline 2026-09-26 (B7/T23): place_building collected tile_occupied rejections
 * (13x in one seed). The free-zone-tile lookup only compared against
 * colony_buildings.tile_x/tile_y, but the Command Center has no tile row
 * (tile_x NULL) while ColonyController::placeBuilding() always treats the CC
 * centre (0,0) as occupied — and it also refuses non-terrain / impassable tiles.
 * The lookup must offer only tiles the server would accept.
 */
class BotStrategyPlacementTileTest extends TestCase
{
    use RefreshDatabase;

    private const BIO_FACILITY = 41;

    public function test_free_zone_tile_never_offers_the_cc_centre(): void
    {
        $bot = $this->bootWithoutAgrardom();

        $tile = $this->rule('place_agrardom')['when']($bot);

        $this->assertNotEmpty($tile);
        $this->assertFalse((int) $tile->q === 0 && (int) $tile->r === 0, 'The CC centre (0,0) is always occupied');
    }

    public function test_full_zone_leaves_no_candidate_even_though_the_cc_centre_has_no_building_row(): void
    {
        // Late game: every zone tile is built on — (0,0) is the only one without a
        // colony_buildings row and used to be offered forever (tile_occupied each Sol).
        $bot = $this->bootWithoutAgrardom();
        $this->occupyAllZoneTilesExcept($bot, 0, 0);

        $this->assertEmpty($this->rule('place_agrardom')['when']($bot));
    }

    public function test_free_zone_tile_skips_unbuildable_tiles(): void
    {
        $bot = $this->bootWithoutAgrardom();
        // Only one zone tile left free — and it is impassable, so there is none.
        $this->occupyAllZoneTilesExcept($bot, -1, 0);
        DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('q', -1)->where('r', 0)
            ->update(['tile_type' => 'terrain_impassable']);

        $this->assertEmpty($this->rule('place_agrardom')['when']($bot));
    }

    public function test_the_offered_tile_is_accepted_by_the_server(): void
    {
        $bot = $this->bootWithoutAgrardom();
        $this->occupyAllZoneTilesExcept($bot, -1, 0);
        $rule = $this->rule('place_agrardom');

        $tile = $rule['when']($bot);
        $this->assertNotEmpty($tile);
        $res = $rule['do']($bot, $tile);

        $this->assertTrue($res['ok'], json_encode($res['body']));
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function bootWithoutAgrardom(): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 3)
            ->update(['amount' => 500]);

        return $bot;
    }

    /** Puts a placeholder building on every zone tile except (0,0) and ($q,$r). */
    private function occupyAllZoneTilesExcept(BotSession $bot, int $q, int $r): void
    {
        $tiles = DB::table('colony_tiles')
            ->where('colony_id', $bot->colonyId)
            ->where('is_colony_zone', 1)
            ->get(['q', 'r']);

        $instance = 100;
        foreach ($tiles as $tile) {
            if (((int) $tile->q === 0 && (int) $tile->r === 0) || ((int) $tile->q === $q && (int) $tile->r === $r)) {
                continue;
            }
            $taken = DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
                ->where('tile_x', $tile->q)->where('tile_y', $tile->r)->exists();
            if ($taken) {
                continue;
            }
            DB::table('colony_buildings')->insert([
                'colony_id' => $bot->colonyId,
                'building_id' => 28, // housing, instanced
                'instance_id' => $instance++,
                'level' => 1,
                'status_points' => 20,
                'ap_spend' => 0,
                'tile_x' => $tile->q,
                'tile_y' => $tile->r,
            ]);
        }
    }

    /**
     * @return array{name:string, when:callable, do:callable}
     */
    private function rule(string $name): array
    {
        foreach (BotStrategy::default() as $rule) {
            if ($rule['name'] === $name) {
                return $rule;
            }
        }

        $this->fail("Rule {$name} not found");
    }
}
