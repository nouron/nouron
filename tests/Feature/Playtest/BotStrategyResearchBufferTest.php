<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T30 finding 2026-10-06: researchCandidate() withheld research below 3 advisors while
 * Regolith was under the next path building's cost. Knowledge research costs AP only
 * (config/knowledge.php credits = 0, no Regolith), so the buffer only delayed it.
 */
class BotStrategyResearchBufferTest extends TestCase
{
    use RefreshDatabase;

    public function test_research_is_not_buffered_by_regolith_below_three_advisors(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->placeSciencelab($bot);
        DB::table('advisors')->where('colony_id', $bot->colonyId)->delete();
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'resource_id' => 3],
            ['amount' => 0],
        );

        $this->assertNotNull($this->candidate($bot), 'research costs no Regolith, so low Regolith must not block it');
    }

    public function test_no_candidate_when_sciencelab_missing(): void
    {
        $bot = BotSession::boot($this, 1);
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::Sciencelab->value)->delete();

        $this->assertNull($this->candidate($bot));
    }

    private function candidate(BotSession $bot): ?int
    {
        $ref = new \ReflectionMethod(BotStrategy::class, 'researchCandidate');
        $ref->setAccessible(true);

        return $ref->invoke(null, $bot);
    }

    private function placeSciencelab(BotSession $bot): void
    {
        $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('is_colony_zone', 1)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('colony_buildings as cb')
                ->where('cb.colony_id', $bot->colonyId)
                ->whereColumn('cb.tile_x', 'colony_tiles.q')
                ->whereColumn('cb.tile_y', 'colony_tiles.r'))
            ->first();
        $this->assertNotNull($tile);
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'building_id' => BuildingId::Sciencelab->value, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => $tile->q, 'tile_y' => $tile->r],
        );
    }
}
