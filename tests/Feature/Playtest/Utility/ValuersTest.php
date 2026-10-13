<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\TestCase;

/**
 * Spec 2.3 + P0: relative expectations only — no fixed score numbers, so weight
 * tuning never breaks these tests.
 */
class ValuersTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    private function ctx(BotSession $bot, string $opening = 'auto'): BotContext
    {
        return BotContext::capture($bot, BotProfile::named('default')->withOpening($opening));
    }

    public function test_hangar_level_2_is_worth_more_with_a_waiting_large_find(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->placeAt($bot, BuildingId::Hangar->value, 1);
        $without = Valuers::upgrade($bot, $this->ctx($bot), Valuers::buildingRow($bot, 44, 1))->net();

        $this->makeFind($bot, 'find_large');
        $with = Valuers::upgrade($bot, $this->ctx($bot), Valuers::buildingRow($bot, 44, 1));

        $this->assertGreaterThan($without, $with->net());
        $this->assertArrayHasKey('unlock', $with->terms);
    }

    public function test_waiting_large_find_never_lowers_hangar_level_2_when_credits_are_scarce(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->placeAt($bot, BuildingId::Hangar->value, 1);
        $this->setCredits($bot, 0);
        DB::table('run_objectives')->insert(['run_id' => $bot->runId, 'task_key' => 'task_credit_reserve', 'target_value' => 1, 'current_value' => 0]);
        $this->assertSame(4.0 * 0.04, round($this->ctx($bot)->price('credits'), 4), 'precondition: credits at maximum scarcity');
        $without = Valuers::upgrade($bot, $this->ctx($bot), Valuers::buildingRow($bot, 44, 1))->net();

        $this->makeFind($bot, 'find_large');
        $with = Valuers::upgrade($bot, $this->ctx($bot), Valuers::buildingRow($bot, 44, 1));

        $this->assertGreaterThanOrEqual($without, $with->net());
        $this->assertGreaterThanOrEqual(0.0, $with->terms['unlock'] ?? 0.0);
    }

    public function test_geology_beats_construction_per_ap_once_the_lab_stands(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->placeAt($bot, BuildingId::Sciencelab->value, 2);
        $ctx = $this->ctx($bot);

        $geology = Valuers::research($bot, $ctx, 92, 0, 0);
        $construction = Valuers::research($bot, $ctx, 90, 0, 0);

        $this->assertGreaterThan($construction->net() / $construction->apCost, $geology->net() / $geology->apCost);
        $this->assertSame(0, $geology->regolithCost);
    }

    public function test_every_knowledge_is_worth_its_ap(): void
    {
        $bot = BotSession::boot($this, 1);
        $ctx = $this->ctx($bot);

        foreach (range(90, 96) as $id) {
            $this->assertGreaterThan(0.0, Valuers::research($bot, $ctx, $id, 0, 0)->net(), "knowledge {$id}");
        }
    }

    public function test_housing_level_up_in_phase_2_without_a_need_is_not_worth_its_regolith(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->setPhase($bot, 2);
        $this->placeAt($bot, BuildingId::Housing->value, 1);

        $this->assertLessThan(0.0, Valuers::upgrade($bot, $this->ctx($bot), Valuers::buildingRow($bot, 28, 1))->net());
    }

    public function test_own_path_building_level_2_beats_housing_level_2_in_a_hangar_opening(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->placeAt($bot, BuildingId::Hangar->value, 1);
        $this->placeAt($bot, BuildingId::Housing->value, 1);
        $ctx = $this->ctx($bot, 'hangar');

        $hangar = Valuers::upgrade($bot, $ctx, Valuers::buildingRow($bot, 44, 1));
        $housing = Valuers::upgrade($bot, $ctx, Valuers::buildingRow($bot, 28, 1));

        $this->assertGreaterThan($housing->net(), $hangar->net());
        $this->assertArrayHasKey('opening', $hangar->terms);
    }

    public function test_path_building_level_3_stays_positive_in_phase_2(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->setPhase($bot, 2);
        $this->placeAt($bot, BuildingId::Hangar->value, 2);

        $this->assertGreaterThan(0.0, Valuers::upgrade($bot, $this->ctx($bot), Valuers::buildingRow($bot, 44, 1))->net());
    }

    public function test_cc_steps_are_positive_in_phase_1_and_towards_level_4(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->setCc($bot, 2);
        $this->assertGreaterThan(0.0, Valuers::cc($bot, $this->ctx($bot), Valuers::ccRow($bot))->net());

        $this->setPhase($bot, 2);
        $this->setCc($bot, 3);
        $cc4 = Valuers::cc($bot, $this->ctx($bot), Valuers::ccRow($bot));
        $this->assertGreaterThan(0.0, $cc4->net());
        $this->assertSame(120, $cc4->regolithCost);
    }

    public function test_path_placement_is_cost_neutral_and_positive(): void
    {
        $bot = BotSession::boot($this, 1);
        $e = Valuers::pathPlacement($bot, $this->ctx($bot, 'hangar'), 44);

        $this->assertGreaterThan(100, $e->regolithCost);
        $this->assertGreaterThan(0.0, $e->net());
        $this->assertSame(-$e->terms['cost_rg'], $e->terms['structural']);
    }
}
