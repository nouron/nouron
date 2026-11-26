<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\ResourceFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\Feature\Playtest\Utility\BotContext;
use Tests\Feature\Playtest\Utility\Candidate;
use Tests\Feature\Playtest\Utility\Sources\UpgradeSource;
use Tests\Feature\Playtest\Utility\UtilityFixtures;
use Tests\Feature\Playtest\Utility\Valuers;
use Tests\TestCase;

/**
 * Spec 2026-11-25 §3.3/§7: "Selbstversorgung" counts Sols on which the own harvest
 * covers the colonists' need (at least min_fed), the stock no longer matters. The
 * old Regolith reserve is gone. Instead, while the objective is drawn and open, an
 * objective-focused profile adds no workplaces beyond its own harvest (placement and
 * level-ups) and prefers the Agrardom and agronomy. Agrardom, housing and the
 * Command Center stay exempt: they raise the harvest or the colonists, not the need.
 */
class BotStrategySelfSufficiencyTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    private const RES_REGOLITH = 3;

    // ── workplace room ───────────────────────────────────────────────────────

    public function test_room_is_harvest_minus_need_for_a_focused_profile_with_the_objective(): void
    {
        $bot = $this->bootPhase2WithObjective();
        $this->fakeOrganics(harvest: 64, need: 60);

        $this->assertSame(4, BotStrategy::ownHarvestRoom($bot, objectiveFocused: true));
        $this->assertNull(BotStrategy::ownHarvestRoom($bot, objectiveFocused: false), 'default ignores objectives');

        DB::table('run_objectives')->where('run_id', $bot->runId)->update(['completed_at' => 50]);
        $this->assertNull(BotStrategy::ownHarvestRoom($bot, objectiveFocused: true), 'a completed objective no longer binds');
    }

    public function test_agrardom_housing_and_cc_never_outgrow_the_harvest(): void
    {
        $bot = $this->bootPhase2WithObjective();
        $this->fakeOrganics(harvest: 40, need: 60);

        foreach ([41, BuildingId::Housing->value, BuildingId::CommandCenter->value] as $id) {
            $this->assertFalse(BotStrategy::outgrowsOwnHarvest($bot, $id, 5, objectiveFocused: true), "building {$id}");
        }
        $this->assertTrue(BotStrategy::outgrowsOwnHarvest($bot, BuildingId::Sciencelab->value, 1, objectiveFocused: true));
        $this->assertFalse(BotStrategy::outgrowsOwnHarvest($bot, BuildingId::Sciencelab->value, 0, objectiveFocused: true), 'no new workplaces');
    }

    public function test_focus_profile_does_not_place_a_building_beyond_its_own_harvest(): void
    {
        $bot = $this->bootPhase2WithObjective();
        $this->placeBasics($bot);
        $this->setRegolith($bot, 5000);

        $this->fakeOrganics(harvest: 500, need: 60);
        $free = $this->rule(BotProfile::named('focus'), 'place_building')['when']($bot);
        $this->assertNotNull($free, 'fixture: with plenty of harvest something is placed');
        $this->assertGreaterThan(0, (int) $free[0]['supply_cost'], 'fixture: the pick adds workplaces');
        $this->assertNotContains((int) $free[0]['building_id'], [41, BuildingId::Housing->value]);

        (fn () => $this->memo = [])->call($bot);   // drop the memoized candidate
        $this->fakeOrganics(harvest: 60, need: 60);
        $tight = $this->rule(BotProfile::named('focus'), 'place_building')['when']($bot);

        $this->assertTrue(
            $tight === null || (int) $tight[0]['supply_cost'] === 0 || in_array((int) $tight[0]['building_id'], [41, BuildingId::Housing->value], true),
            'no placement that adds workplaces beyond the own harvest'
        );
    }

    public function test_default_profile_still_places_beyond_its_harvest(): void
    {
        $bot = $this->bootPhase2WithObjective();
        $this->placeBasics($bot);
        $this->setRegolith($bot, 5000);
        $this->fakeOrganics(harvest: 60, need: 60);

        $pick = $this->rule(BotProfile::named('default'), 'place_building')['when']($bot);

        $this->assertNotNull($pick);
        $this->assertGreaterThan(0, (int) $pick[0]['supply_cost']);
    }

    public function test_focus_profile_no_longer_keeps_a_regolith_reserve(): void
    {
        $bot = $this->bootPhase2WithObjective();
        $this->placeBasics($bot);
        $this->fakeOrganics(harvest: 500, need: 60);
        // Old rule: regolith_min (150) × 1.1 kept back. 170 now buys a placement.
        $this->setRegolith($bot, 170);

        $this->assertNotNull($this->rule(BotProfile::named('focus'), 'place_building')['when']($bot));
    }

    public function test_upgrade_source_skips_level_ups_beyond_the_harvest_but_keeps_the_agrardom(): void
    {
        $bot = $this->bootPhase2WithObjective();
        $this->setRegolith($bot, 5000);
        $this->setCc($bot, 3);
        $this->giveAp($bot, 4);
        $this->placeAt($bot, BuildingId::Sciencelab->value, 1);
        $this->placeAt($bot, 41, 1);
        $this->fakeOrganics(harvest: 60, need: 60);

        $keys = fn (BotProfile $profile): array => array_map(
            fn (Candidate $c): string => $c->key,
            (new UpgradeSource(17))->candidates($bot, BotContext::capture($bot, $profile, withGoal: true)),
        );

        $this->assertContains('invest:31:1', $keys(BotProfile::named('default')), 'fixture: the lab level-up is on offer');
        $this->assertNotContains('invest:31:1', $keys(BotProfile::named('focus')));
        $this->assertContains('invest:41:1', $keys(BotProfile::named('focus')), 'the Agrardom raises the harvest');
    }

    // ── Agrardom and agronomy first ──────────────────────────────────────────

    public function test_agrardom_and_agronomy_carry_the_objective_while_it_is_open(): void
    {
        $bot = $this->bootPhase2WithObjective();
        $this->setCc($bot, 3);
        $this->placeAt($bot, 41, 1);
        $this->fakeOrganics(harvest: 60, need: 60);
        $ctx = BotContext::capture($bot, BotProfile::named('focus'), withGoal: true);
        $agrardom = DB::table('colony_buildings as cb')->join('buildings as bld', 'bld.id', '=', 'cb.building_id')
            ->where('cb.colony_id', $bot->colonyId)->where('cb.building_id', 41)
            ->select('cb.building_id', 'cb.instance_id', 'cb.level', 'cb.ap_spend', 'cb.tile_x', 'bld.supply_cost', 'bld.ap_for_levelup')
            ->first();

        $this->assertGreaterThan(0.0, Valuers::upgrade($bot, $ctx, $agrardom)->terms['objective'] ?? 0.0, 'Agrardom level-up');
        $this->assertGreaterThan(0.0, Valuers::research($bot, $ctx, 93, 0, 0)->terms['objective'] ?? 0.0, 'agronomy level');

        $default = BotContext::capture($bot, BotProfile::named('default'), withGoal: true);
        $this->assertArrayNotHasKey('objective', Valuers::upgrade($bot, $default, $agrardom)->terms);
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function bootPhase2WithObjective(): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => 2, 'phase2_start_tick' => 1]);
        DB::table('run_objectives')->where('run_id', $bot->runId)->delete();
        DB::table('run_objectives')->insert([
            'run_id' => $bot->runId,
            'task_key' => 'task_self_sufficiency',
            'target_value' => (int) config('game.run.tasks.task_self_sufficiency.target'),
            'current_value' => 0,
            'streak_value' => 0,
            'completed_at' => null,
        ]);
        // Supply is not what this test is about — keep it out of the way.
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['supply' => 1000]);

        return $bot;
    }

    /** Phase-1 buildings and the science lab stand, so placement offers further buildings. */
    private function placeBasics(BotSession $bot): void
    {
        $this->setCc($bot, 3);
        $this->placeAt($bot, 41, 1);
        $this->placeAt($bot, BuildingId::UplinkStation->value, 1);
        $this->placeAt($bot, BuildingId::Sciencelab->value, 1);
    }

    private function fakeOrganics(int $harvest, int $need): void
    {
        $this->mock(ResourceFlowService::class, function (MockInterface $mock) use ($harvest, $need): void {
            $mock->shouldReceive('organicsBalance')->andReturn(['harvest' => $harvest, 'need' => $need]);
        });
    }

    private function setRegolith(BotSession $bot, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'resource_id' => self::RES_REGOLITH],
            ['amount' => $amount],
        );
    }

    /**
     * @return array{name:string, when:callable, do:callable}
     */
    private function rule(BotProfile $profile, string $name): array
    {
        foreach (BotStrategy::default($profile) as $rule) {
            if ($rule['name'] === $name) {
                return $rule;
            }
        }

        $this->fail("Rule {$name} not found");
    }
}
