<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\TestCase;

/** Spec 2.6 + T30 §14.4: one savings goal replaces the binary Regolith buffers. */
class HeuristicGoalProviderTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    private function phase2WithCc3(int $regolith): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->setPhase($bot, 2);
        $this->setCc($bot, 3);
        $this->placeAt($bot, 41, 1);
        $this->placeAt($bot, BuildingId::Sciencelab->value, 2);
        $this->placeAt($bot, BuildingId::Hangar->value, 2);
        $this->setResource($bot, 3, $regolith);
        $this->setSupply($bot, 1000);

        return $bot;
    }

    private function ctx(BotSession $bot, string $opening = 'auto'): BotContext
    {
        return BotContext::capture($bot, BotProfile::named('default')->withOpening($opening), withGoal: true);
    }

    public function test_an_unaffordable_cc_level_becomes_the_goal_in_phase_2(): void
    {
        $bot = $this->phase2WithCc3(50);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }

        $ctx = $this->ctx($bot);

        $this->assertSame(['cc', 'cc:25:4', 120], [$ctx->goal?->kind, $ctx->goal?->key, $ctx->goal?->regolithCost]);
        // Review S3 I1: 70 Rg missing at 26/Sol -> bought in 3 Sols; 50 + 3 * 26 - 120 = 8 Rg are slack.
        $this->assertSame(42, $ctx->regolithHold());
    }

    public function test_the_hold_is_the_whole_cost_once_the_goal_is_affordable(): void
    {
        $bot = $this->phase2WithCc3(50);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }
        $this->ctx($bot);
        $this->setResource($bot, 3, 130);

        $ctx = $this->ctx($bot);

        $this->assertSame('cc:25:4', $ctx->goal?->key, 'review S3 I7: the goal stays until it is bought');
        $this->assertTrue($ctx->goal->ready);
        $this->assertSame(120, $ctx->regolithHold(), 'legacy spenders only get the 10 Rg above the goal');
    }

    public function test_a_better_unaffordable_option_does_not_replace_the_sticky_goal(): void
    {
        $bot = $this->phase2WithCc3(10);   // CC Lv4 (120, ratio 0.5) and the path Lv3 steps (25, ratio 0.2): all short
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }
        $this->assertSame('cc:25:4', $this->ctx($bot)->goal?->key, 'best ratio without a sticky step');

        $bot->savingsGoalStep = 'invest:31:1>3';

        $ctx = $this->ctx($bot);
        $this->assertSame('invest:31:1', $ctx->goal?->key, 'the sticky step stays the goal');
        $this->assertSame('invest:31:1>3', $bot->savingsGoalStep);

        $bot->savingsGoalStep = 'invest:31:1>9';   // outdated step: no longer an option
        $this->assertSame('cc:25:4', $this->ctx($bot)->goal?->key, 'an invalid sticky step falls back to the best ratio');
    }

    public function test_a_ready_option_with_a_better_ratio_goes_before_the_sticky_goal(): void
    {
        $bot = $this->phase2WithCc3(50);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }
        $options = HeuristicGoalProvider::options($bot, $this->ctx($bot));
        $bot->savingsGoalStep = end($options)->step;
        $this->setResource($bot, 3, 400);

        $options = HeuristicGoalProvider::options($bot, $this->ctx($bot));
        $this->assertTrue($options[0]->ready);

        $this->assertSame($options[0]->key, $this->ctx($bot)->goal?->key);
    }

    public function test_a_goal_out_of_reach_within_five_sols_is_dropped(): void
    {
        $bot = $this->phase2WithCc3(0);   // no sol_next yet: income floor 8 -> CC Lv4 (120 Rg) takes 15 Sols

        $options = HeuristicGoalProvider::options($bot, $this->ctx($bot));

        $this->assertNotContains('cc:25:4', array_map(fn (SavingsGoal $g): string => $g->key, $options), 'CC Lv4 is out of reach');
        foreach ($options as $g) {
            $this->assertLessThanOrEqual(5.0, $g->regolithCost / 8, "{$g->key} must be reachable within 5 Sols");
        }
        $this->assertNotSame('cc:25:4', $this->ctx($bot)->goal?->key);

        // Control: the same CC step is an option once the income makes it reachable.
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }
        $this->setResource($bot, 3, 50);
        $this->assertContains('cc:25:4', array_map(fn (SavingsGoal $g): string => $g->key, HeuristicGoalProvider::options($bot, $this->ctx($bot))));
    }

    public function test_without_the_flag_no_goal_is_computed(): void
    {
        $bot = $this->phase2WithCc3(50);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }

        $this->assertNull(BotContext::capture($bot, BotProfile::named('default'))->goal);
    }

    public function test_opportunity_cost_spares_the_goal_and_regolith_free_steps(): void
    {
        $bot = $this->phase2WithCc3(50);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }
        $ctx = $this->ctx($bot);

        $this->assertSame(0.0, $ctx->opportunityCost(120, 'cc:25:4'));
        $this->assertSame(0.0, $ctx->opportunityCost(0, 'invest:44:1'));
        $this->assertGreaterThan(0.0, $ctx->opportunityCost(25, 'invest:44:1'));
        $this->assertGreaterThan($ctx->opportunityCost(25, 'invest:44:1'), $ctx->opportunityCost(50, 'invest:31:1'));
    }

    public function test_the_slack_above_the_hold_costs_no_opportunity(): void
    {
        // Same slack the hold leaves legacy spenders (50 Rg, hold 42): spending it does not delay the goal.
        $bot = $this->phase2WithCc3(50);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }
        $ctx = $this->ctx($bot);
        $gamma = (float) $ctx->weights['gamma'];

        $this->assertSame(0.0, $ctx->opportunityCost(8, 'invest:44:1'));
        $this->assertEqualsWithDelta($ctx->goal->net * (1 - $gamma ** ((25 - 8) / 26)), $ctx->opportunityCost(25, 'invest:44:1'), 1e-9);
    }

    public function test_a_pending_path_building_is_a_goal_with_a_hold(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->setCc($bot, 2);
        $this->placeAt($bot, 41, 1);
        foreach ([31, 44, 52] as $pathId) {
            DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', $pathId)->delete();
        }
        $this->setResource($bot, 3, 60);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }

        $ctx = $this->ctx($bot, 'hangar');

        $this->assertSame(['path', 'place:44'], [$ctx->goal?->kind, $ctx->goal?->key]);
        // Review S3 I2: legacy place_building's own path buffer only applies below 3 advisors.
        // The hold keeps what is needed to buy it at the earliest Sol (missing / 26 rounded up).
        $sols = (int) ceil(($ctx->goal->regolithCost - 60) / 26);
        $this->assertSame($ctx->goal->regolithCost - $sols * 26, $ctx->regolithHold());
        $this->assertGreaterThan(0, $ctx->regolithHold());
    }

    private function pendingPathBot(): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->setCc($bot, 2);
        $this->placeAt($bot, 41, 1);
        foreach ([31, 44, 52] as $pathId) {
            DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', $pathId)->delete();
        }
        $this->setResource($bot, 3, 60);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }

        return $bot;
    }

    /** @return list<string> */
    private function optionKeys(BotSession $bot): array
    {
        return array_map(fn (SavingsGoal $g): string => $g->key, HeuristicGoalProvider::options($bot, $this->ctx($bot, 'hangar')));
    }

    public function test_a_supply_blocked_path_placement_stays_an_option_only_with_a_housing_step(): void
    {
        $bot = $this->pendingPathBot();
        $this->setSupply($bot, 0);
        $this->assertContains('place:44', $this->optionKeys($bot), 'housing can lift the supply block');

        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', BuildingId::Housing->value)->delete();

        $this->assertNotContains('place:44', $this->optionKeys($bot), 'nothing can lift the supply block');
    }

    public function test_a_path_placement_without_a_free_zone_tile_is_no_option(): void
    {
        $bot = $this->pendingPathBot();
        $this->assertContains('place:44', $this->optionKeys($bot));

        DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->update(['is_colony_zone' => 0]);

        $this->assertNotContains('place:44', $this->optionKeys($bot));
    }
}
