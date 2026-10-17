<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\Utility\Sources\UpgradeSource;
use Tests\TestCase;

/** T30 §14 U1/U3, spec P6: relative expectations, no fixed scores. */
class UpgradeSourceTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    private function pick(BotSession $bot, string $opening = 'auto'): ?Candidate
    {
        $ctx = BotContext::capture($bot, BotProfile::named('default')->withOpening($opening), withGoal: true);

        return UtilitySelector::select((new UpgradeSource(17))->candidates($bot, $ctx), [])?->chosen;
    }

    /** @return list<string> */
    private function keys(BotSession $bot): array
    {
        $ctx = BotContext::capture($bot, BotProfile::named('default'), withGoal: true);

        return array_map(fn (Candidate $c) => $c->key, (new UpgradeSource(17))->candidates($bot, $ctx));
    }

    private function boot(int $regolith): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->setResource($bot, 3, $regolith);
        $this->setSupply($bot, 1000);
        $this->giveAp($bot, 15);
        $this->setCc($bot, 2);   // Lv2 steps need CC Lv2 (building_cc_gate)

        return $bot;
    }

    public function test_own_path_building_goes_to_level_2_before_housing_and_agrardom(): void
    {
        $bot = $this->boot(60);
        $this->setPhase($bot, 2);   // Phase 1 buys no Lv2 steps while a requirement is missing
        $this->placeAt($bot, BuildingId::Hangar->value, 1);
        $this->placeAt($bot, BuildingId::Housing->value, 1);
        $this->placeAt($bot, 41, 1);
        $this->makeFind($bot, 'find_large');

        $this->assertSame('invest:44:1', $this->pick($bot, 'hangar')?->key);
    }

    public function test_path_buildings_level_beyond_2_in_phase_2(): void
    {
        $bot = $this->boot(100);
        $this->setPhase($bot, 2);
        $this->setCc($bot, 3);   // Lv3 step needs CC Lv3
        $this->placeAt($bot, BuildingId::Hangar->value, 2);

        $this->assertContains('invest:44:1', $this->keys($bot));
        $this->assertGreaterThan(0.0, $this->pick($bot)?->net);
    }

    public function test_a_supply_blocked_target_gives_housing_its_enabling_value(): void
    {
        $bot = $this->boot(100);
        $this->setPhase($bot, 2);
        $this->placeAt($bot, BuildingId::Sciencelab->value, 1);
        $this->placeAt($bot, BuildingId::Housing->value, 1);
        $this->setSupply($bot, 0);

        $chosen = $this->pick($bot, 'labor');

        $this->assertSame('invest:28:1', $chosen?->key);
        $this->assertGreaterThan(0.0, $chosen->terms['enable'] ?? 0.0);
        $this->assertNotContains('invest:31:1', $this->keys($bot));
    }

    public function test_a_supply_blocked_path_placement_gives_housing_its_enabling_value(): void
    {
        $bot = $this->boot(100);
        $this->setPhase($bot, 2);
        $this->setCc($bot, 3);
        $this->placeAt($bot, 41, 1);
        $this->placeAt($bot, BuildingId::Hangar->value, 3);   // max level: no blocked upgrade of its own
        $this->placeAt($bot, BuildingId::Housing->value, 1);
        $this->setSupply($bot, 0);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }

        $ctx = BotContext::capture($bot, BotProfile::named('default')->withOpening('hangar'), withGoal: true);
        $housing = collect((new UpgradeSource(17))->candidates($bot, $ctx))->firstWhere('key', 'invest:28:1');

        $this->assertSame('place:'.BuildingId::Sciencelab->value, $ctx->goal?->key, 'the supply-blocked placement stays the goal');
        $this->assertNotNull($housing);
        $this->assertGreaterThanOrEqual(Valuers::pathPlacement($bot, $ctx, BuildingId::Sciencelab->value)->net() - 0.001, $housing->terms['enable'] ?? 0.0);
        $this->assertArrayNotHasKey('opportunity', $housing->terms, 'housing is the goal\'s chain (spec 2.6)');
    }

    public function test_a_running_cycle_beats_an_otherwise_equal_new_one(): void
    {
        $bot = $this->boot(100);
        $this->setPhase($bot, 2);
        $this->placeAt($bot, BuildingId::Hangar->value, 1, apSpend: 5);
        $this->placeAt($bot, BuildingId::Sciencelab->value, 1);
        $this->setKnowledge($bot, 92, 1);   // geology known: no lab unlock term

        $this->assertSame('invest:44:1', $this->pick($bot)?->key);
    }

    public function test_unaffordable_steps_are_left_to_the_savings_goal(): void
    {
        $bot = $this->boot(10);
        $this->placeAt($bot, BuildingId::Hangar->value, 1);

        $this->assertNotContains('invest:44:1', $this->keys($bot), 'Hangar Lv2 needs 25 Rg');
    }

    public function test_a_non_goal_step_pays_opportunity_cost_while_a_goal_is_open(): void
    {
        $bot = $this->boot(50);
        $this->setPhase($bot, 2);
        $this->setCc($bot, 3);
        $this->placeAt($bot, 41, 1);
        $this->placeAt($bot, BuildingId::Hangar->value, 2);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }

        $ctx = BotContext::capture($bot, BotProfile::named('default'), withGoal: true);
        $hangar = collect((new UpgradeSource(17))->candidates($bot, $ctx))->firstWhere('key', 'invest:44:1');

        $this->assertNotNull($ctx->goal, 'fixture must open a savings goal');
        $this->assertNotNull($hangar);
        $this->assertLessThan(0.0, $hangar->terms['opportunity'] ?? 0.0);
    }

    public function test_phase_1_skips_non_required_level_ups_while_a_requirement_is_missing(): void
    {
        $bot = $this->boot(300);
        $this->placeAt($bot, BuildingId::Sciencelab->value, 1);
        $this->placeAt($bot, BuildingId::Housing->value, 1);
        $this->placeAt($bot, 41, 1);

        $keys = $this->keys($bot);

        $this->assertNotContains('invest:31:1', $keys, 'path building Lv2 is not required');
        $this->assertNotContains('invest:28:1', $keys, 'Wohnhabitat Lv2 is not required');
        $this->assertNotContains('invest:41:1', $keys, 'Agrardom Lv2 is not required');
    }

    public function test_phase_1_still_builds_a_placed_level_0_path_building(): void
    {
        $bot = $this->boot(300);
        $this->placeAt($bot, BuildingId::Sciencelab->value, 0);
        $this->placeAt($bot, BuildingId::Housing->value, 1);
        $this->placeAt($bot, 41, 1);

        $this->assertContains('invest:31:1', $this->keys($bot));
    }

    public function test_phase_1_housing_level_up_stays_while_supply_blocks_the_uplink(): void
    {
        $bot = $this->boot(300);
        $this->placeAt($bot, BuildingId::Sciencelab->value, 1);
        $this->placeAt($bot, BuildingId::Housing->value, 1);
        $this->placeAt($bot, 41, 1);
        $this->setSupply($bot, 0);

        $this->assertContains('invest:28:1', $this->keys($bot));
    }

    /** Phase 2, CC Lv3, every Phase-1 building stands, lambda decayed to ~0 by idle AP. */
    private function idleLambdaBot(int $sciencelabLevel): BotSession
    {
        $bot = $this->boot(10);
        $this->setPhase($bot, 2);
        $this->setCc($bot, 3);
        $this->placeAt($bot, 41, 1);
        $this->placeAt($bot, BuildingId::Hangar->value, 2);
        $this->placeAt($bot, BuildingId::Sciencelab->value, $sciencelabLevel, apSpend: 1);
        $bot->lambdaAp = 0.001;

        return $bot;
    }

    public function test_a_placed_level_0_path_building_is_offered_even_when_lambda_has_decayed(): void
    {
        // Measured (hangar seed 2): Sciencelab Lv0 from Sol 26 to 45 with idle AP; advisor_slot(lambda ~ 0) < 0 hid the step.
        $bot = $this->idleLambdaBot(0);

        $this->assertContains('invest:31:1', $this->keys($bot));
        $this->assertSame('invest:31:1', $this->pick($bot, 'hangar')?->key);
    }

    public function test_the_path_unlock_value_does_not_depend_on_lambda(): void
    {
        $net = function (float $lambda): float {
            $bot = $this->idleLambdaBot(0);
            $bot->lambdaAp = $lambda;
            $ctx = BotContext::capture($bot, BotProfile::named('default'), withGoal: true);
            $c = collect((new UpgradeSource(17))->candidates($bot, $ctx))->firstWhere('key', 'invest:31:1');

            return $c->terms['path_open'];
        };

        $this->assertSame($net(0.001), $net(2.5));
        $this->assertGreaterThan(0.0, $net(0.001));
    }

    public function test_a_level_1_path_building_gets_no_unlock_term(): void
    {
        $bot = $this->idleLambdaBot(1);
        $ctx = BotContext::capture($bot, BotProfile::named('default'), withGoal: true);
        $c = collect((new UpgradeSource(17))->candidates($bot, $ctx))->firstWhere('key', 'invest:31:1');

        $this->assertArrayNotHasKey('path_open', $c?->terms ?? []);
    }
}
