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
        $this->assertSame(120, $ctx->regolithHold());
    }

    public function test_a_goal_out_of_reach_within_five_sols_is_dropped(): void
    {
        $bot = $this->phase2WithCc3(0);   // no sol_next yet: income floor 8 -> CC Lv4 (120 Rg) takes 15 Sols

        $ctx = $this->ctx($bot);

        $this->assertNotSame('cc:25:4', $ctx->goal?->key, 'CC Lv4 is out of reach');
        if ($ctx->goal !== null) {
            $this->assertLessThanOrEqual(5.0, $ctx->goal->regolithCost / 8, 'any remaining goal must be reachable within 5 Sols');
            $this->assertSame($ctx->goal->regolithCost, $ctx->regolithHold());
        }
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
        $this->assertSame($ctx->goal->regolithCost, $ctx->regolithHold());
        $this->assertGreaterThan(0, $ctx->regolithHold());
    }
}
