<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\ResourcesService;
use App\Services\TickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The bot must not walk into server-side gates it could have seen coming:
 *
 *  - C16 Agrardom gate (ColonyController::investBuilding(): CC Lv1 -> Lv2 needs a
 *    placed bioFacility) — the bot used to try invest_cc first, eat the
 *    agrardom_required rejection, and only place the Agrardom once explore_tile
 *    had already spent the Sol's AP.
 *  - A14 supply build gate (investBuilding() rejects with supply_limit when free
 *    supply < supply_cost; CC and housing exempt) — productionInvestCandidate()
 *    never looked at free supply.
 *
 * Plus the harness itself: at Sol 0 (before the first /sol/next) the
 * TickService singleton still carried the wall-clock fallback tick in the test
 * process, so a Sol-0 harvester relocation got pending_until_tick = ~20720 and
 * never produced again once the run clock restarted at Sol 1.
 */
class BotStrategyServerGatesTest extends TestCase
{
    use RefreshDatabase;

    private const BIO_FACILITY = 41;

    public function test_boot_aligns_the_tick_service_with_the_run_clock(): void
    {
        $bot = BotSession::boot($this, 1);

        $runTick = (int) DB::table('runs')->where('id', $bot->runId)->value('current_tick');

        $this->assertSame($runTick, app(TickService::class)->getTickCount());
    }

    public function test_invest_cc_is_not_attempted_at_cc_level_1_without_an_agrardom(): void
    {
        $bot = $this->bootAtCcLevel1WithoutAgrardom();

        $this->assertEmpty($this->rule('invest_cc')['when']($bot));
    }

    public function test_place_agrardom_offers_the_bio_facility_on_a_free_zone_tile_before_exploring(): void
    {
        $bot = $this->bootAtCcLevel1WithoutAgrardom();

        $candidate = $this->rule('place_agrardom')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame(1, (int) DB::table('colony_tiles')
            ->where('colony_id', $bot->colonyId)
            ->where('q', $candidate->q)
            ->where('r', $candidate->r)
            ->value('is_colony_zone'));

        $names = array_column(BotStrategy::default(), 'name');
        $this->assertLessThan(array_search('explore_tile', $names, true), array_search('place_agrardom', $names, true));
        $this->assertLessThan(array_search('invest_cc', $names, true), array_search('place_agrardom', $names, true));
    }

    public function test_placing_the_agrardom_unblocks_invest_cc(): void
    {
        $bot = $this->bootAtCcLevel1WithoutAgrardom();
        $rule = $this->rule('place_agrardom');

        $res = $rule['do']($bot, $rule['when']($bot));

        $this->assertTrue($res['ok'], 'place_agrardom must succeed: '.json_encode($res['body']));
        $this->assertTrue(DB::table('colony_buildings')
            ->where('colony_id', $bot->colonyId)
            ->where('building_id', self::BIO_FACILITY)
            ->whereNotNull('tile_x')
            ->exists());
        $this->assertNotEmpty($this->rule('invest_cc')['when']($bot));
        $this->assertEmpty($this->rule('place_agrardom')['when']($bot), 'Agrardom is a one-time gate, not a repeat build');
    }

    public function test_without_a_free_zone_tile_neither_agrardom_nor_cc_invest_is_attempted(): void
    {
        $bot = $this->bootAtCcLevel1WithoutAgrardom();
        DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->update(['is_colony_zone' => 0]);

        $this->assertEmpty($this->rule('place_agrardom')['when']($bot));
        $this->assertEmpty($this->rule('invest_cc')['when']($bot));
    }

    public function test_production_invest_skips_a_candidate_the_supply_gate_would_reject(): void
    {
        $bot = $this->bootWithPlacedAgrardom();
        // Housing maxed: nothing exempt from the gate is left to invest into.
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::Housing->value)->update(['level' => 3]);
        $this->squeezeFreeSupplyTo($bot, 1); // bioFacility supply_cost 2 > 1

        $candidate = $this->rule('invest_production')['when']($bot);

        $this->assertEmpty($candidate, 'bioFacility would hit supply_limit — must not be chosen: '.json_encode($candidate));
    }

    public function test_production_invest_falls_back_to_housing_when_supply_is_the_bottleneck(): void
    {
        $bot = $this->bootWithPlacedAgrardom();
        // Housing Lv2 is outside the normal "< Lv2" production target but below max_level 3.
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::Housing->value)->update(['level' => 2]);
        $this->squeezeFreeSupplyTo($bot, 1);

        $candidate = $this->rule('invest_production')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame(BuildingId::Housing->value, (int) $candidate->building_id);

        $res = $this->rule('invest_production')['do']($bot, $candidate);
        $this->assertTrue($res['ok'], 'housing is exempt from the supply gate: '.json_encode($res['body']));
    }

    public function test_production_invest_keeps_a_placed_level_0_building_despite_low_free_supply(): void
    {
        $bot = $this->bootWithPlacedAgrardom();
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::Housing->value)->update(['level' => 3]);
        // Placed but not built yet: its first level's workplaces are already reserved,
        // so the server never checks supply on the 0 -> 1 step.
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', self::BIO_FACILITY)->update(['level' => 0]);
        $this->squeezeFreeSupplyTo($bot, 0);

        $candidate = $this->rule('invest_production')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame(self::BIO_FACILITY, (int) $candidate->building_id);

        $res = $this->rule('invest_production')['do']($bot, $candidate);
        $this->assertTrue($res['ok'], 'level 0 -> 1 of a placed building never fails on supply: '.json_encode($res['body']));
    }

    public function test_production_invest_is_unchanged_when_supply_is_free(): void
    {
        $bot = $this->bootWithPlacedAgrardom();
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::Housing->value)->update(['level' => 3]);

        $candidate = $this->rule('invest_production')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame(self::BIO_FACILITY, (int) $candidate->building_id);
    }

    // ── helpers ───────────────────────────────────────────────────────────────

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

    private function bootAtCcLevel1WithoutAgrardom(): BotSession
    {
        $bot = BotSession::boot($this, 1);

        $this->assertSame(1, BotStrategy::ccLevel($bot), 'Sol-1 fixture must start at CC Lv1');
        $this->assertFalse(DB::table('colony_buildings')
            ->where('colony_id', $bot->colonyId)
            ->where('building_id', self::BIO_FACILITY)
            ->exists(), 'Sol-1 fixture must start without an Agrardom');

        return $bot;
    }

    /** Agrardom placed at Lv1 on a zone tile, plenty of Regolith (no path-building Rg buffer). */
    private function bootWithPlacedAgrardom(): BotSession
    {
        $bot = BotSession::boot($this, 1);

        $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('is_colony_zone', 1)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('colony_buildings as cb')
                ->where('cb.colony_id', $bot->colonyId)
                ->whereColumn('cb.tile_x', 'colony_tiles.q')
                ->whereColumn('cb.tile_y', 'colony_tiles.r'))
            ->first();
        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId,
            'building_id' => self::BIO_FACILITY,
            'instance_id' => 1,
            'level' => 1,
            'status_points' => 20,
            'ap_spend' => 0,
            'tile_x' => $tile->q,
            'tile_y' => $tile->r,
        ]);
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'resource_id' => 3],
            ['amount' => 1000],
        );

        return $bot;
    }

    /** Lower the stored cap so exactly $free supply is left (used supply untouched). */
    private function squeezeFreeSupplyTo(BotSession $bot, int $free): void
    {
        $resources = app(ResourcesService::class);
        $used = $resources->getSupplyBreakdown($bot->colonyId)['cap'] - $resources->getFreeSupply($bot->colonyId);
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['supply' => $used + $free]);

        $this->assertSame($free, $resources->getFreeSupply($bot->colonyId));
    }
}
