<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\ResourcesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T9 (2026-09-25): the bot must play the food economy like a human would and
 * pick the Phase-1 "2 non-CC buildings >= Lv2" targets by their real cost.
 *
 *  - The bot only ever placed the Agrardom because the C16 gate demands it; the
 *    building then stayed on Lv0 for the whole of Phase 1 (Organika 0, hunger
 *    streak growing every Sol, trust pinned at the capped hunger malus).
 *  - productionInvestCandidate() ordered level-ups by "level DESC, building_id",
 *    which ignores Regolith already paid for a running cycle, AP still missing
 *    and the A14 supply build gate — the Agrardom never competed.
 */
class BotStrategyFoodAndLevel2Test extends TestCase
{
    use PlaysSolLoop;
    use RefreshDatabase;

    private const BIO_FACILITY = 41;

    private const SCIENCELAB = 31;

    private const HANGAR = 44;

    // ── food ──────────────────────────────────────────────────────────────────

    public function test_phase_1_never_runs_a_hunger_streak_of_two_sols(): void
    {
        $bot = BotSession::boot($this, seed: 4242);
        $maxStreak = 0;
        $streaks = [];

        $this->playSolsUntil($bot, BotStrategy::default(), function (BotSession $b) use (&$maxStreak, &$streaks): bool {
            $streak = (int) DB::table('glx_colonies')->where('id', $b->colonyId)->value('hunger_streak');
            $streaks[$b->sol] = $streak;
            $maxStreak = max($maxStreak, $streak);

            return (int) DB::table('runs')->where('id', $b->runId)->value('phase') >= 2 || $b->sol >= 30;
        });

        $this->assertLessThan(2, $maxStreak, 'hunger streak per Sol: '.json_encode($streaks));
        $this->assertGreaterThanOrEqual(1, $this->agrardomLevel($bot));
    }

    public function test_a_freshly_placed_agrardom_is_built_to_level_1_on_the_same_sol(): void
    {
        $bot = BotSession::boot($this, seed: 1);

        $this->playOneSol($bot, BotStrategy::default());

        $this->assertGreaterThanOrEqual(1, $this->agrardomLevel($bot), 'Agrardom must produce before the first food tick');
    }

    public function test_feed_colony_levels_the_agrardom_when_the_stock_would_run_short(): void
    {
        $bot = $this->bootWithAgrardomAtLevel(1);
        $this->placeAt($bot, self::SCIENCELAB, 1);
        $this->placeAt($bot, self::HANGAR, 1);
        // One eater per colonist: the need clearly exceeds Lv1 production (8).
        config(['game.food.supply_per_eater' => 1]);
        $this->setOrganics($bot, 0);
        $this->squeezeFreeSupplyTo($bot, 4);
        $this->assertGreaterThan(8, app(ResourcesService::class)->foodNeed($bot->colonyId));

        $candidate = $this->rule('feed_colony')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame(self::BIO_FACILITY, (int) $candidate->building_id);

        $res = $this->rule('feed_colony')['do']($bot, $candidate);
        $this->assertTrue($res['ok'], json_encode($res['body']));
    }

    public function test_feed_colony_stays_idle_while_the_stock_covers_the_need(): void
    {
        $bot = $this->bootWithAgrardomAtLevel(1);
        $this->setOrganics($bot, 500);

        $this->assertEmpty($this->rule('feed_colony')['when']($bot));
    }

    public function test_feed_colony_runs_before_invest_cc(): void
    {
        $names = array_column(BotStrategy::default(), 'name');

        $this->assertContains('feed_colony', $names);
        $this->assertLessThan(array_search('invest_cc', $names, true), array_search('feed_colony', $names, true));
    }

    // ── cheapest Lv2 ──────────────────────────────────────────────────────────

    public function test_level_2_choice_prefers_the_agrardom_over_a_supply_blocked_sciencelab(): void
    {
        $bot = $this->bootWithAgrardomAtLevel(1);
        $this->placeAt($bot, self::SCIENCELAB, 1);
        $this->placeAt($bot, self::HANGAR, 1);
        // Housing already on Lv2 (counts for the condition), not maxed.
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::Housing->value)->update(['level' => 2]);
        // Enough for the Agrardom (2), not for the Sciencelab (8): the lab would
        // need a housing level first (+25 Rg, +AP).
        $this->squeezeFreeSupplyTo($bot, 4);

        $candidate = $this->rule('invest_production')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame(self::BIO_FACILITY, (int) $candidate->building_id);
    }

    public function test_level_2_choice_prefers_a_running_cycle_whose_regolith_is_already_paid(): void
    {
        $bot = $this->bootWithAgrardomAtLevel(1);
        // Agrardom 1 -> 2 already started: Regolith paid, 5 of 10 AP invested.
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', self::BIO_FACILITY)->update(['ap_spend' => 5]);

        $candidate = $this->rule('invest_production')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame(self::BIO_FACILITY, (int) $candidate->building_id);
    }

    public function test_level_2_choice_prefers_a_level_1_agrardom_over_a_fresh_second_housing(): void
    {
        $bot = $this->bootWithAgrardomAtLevel(1);
        // Housing #1 already Lv2; housing #2 placed on Lv0 (needs 0->1 and 1->2).
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::Housing->value)->update(['level' => 2]);
        $this->placeAt($bot, BuildingId::Housing->value, 0, instanceId: 2);

        $candidate = $this->rule('invest_production')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame(self::BIO_FACILITY, (int) $candidate->building_id);
    }

    public function test_an_ap_only_step_is_not_held_back_by_the_path_building_buffer(): void
    {
        $bot = $this->bootWithAgrardomAtLevel(1);
        // No path building placed yet, too little Regolith for one: the buffer holds
        // every Regolith-costing level-up back — but a running cycle costs AP only.
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', self::BIO_FACILITY)->update(['ap_spend' => 3]);
        DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 3)->update(['amount' => 10]);

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

    private function agrardomLevel(BotSession $bot): int
    {
        return (int) (DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', self::BIO_FACILITY)->value('level') ?? -1);
    }

    private function bootWithAgrardomAtLevel(int $level): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->placeAt($bot, self::BIO_FACILITY, $level);
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'resource_id' => 3],
            ['amount' => 1000],
        );

        return $bot;
    }

    private function placeAt(BotSession $bot, int $buildingId, int $level, int $instanceId = 1): void
    {
        $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('is_colony_zone', 1)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('colony_buildings as cb')
                ->where('cb.colony_id', $bot->colonyId)
                ->whereColumn('cb.tile_x', 'colony_tiles.q')
                ->whereColumn('cb.tile_y', 'colony_tiles.r'))
            ->first();
        $this->assertNotNull($tile, 'fixture needs a free colony-zone tile');

        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId,
            'building_id' => $buildingId,
            'instance_id' => $instanceId,
            'level' => $level,
            'status_points' => 20,
            'ap_spend' => 0,
            'tile_x' => $tile->q,
            'tile_y' => $tile->r,
        ]);
    }

    private function setOrganics(BotSession $bot, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'resource_id' => 5],
            ['amount' => $amount],
        );
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
