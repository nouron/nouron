<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use App\Services\BuildingCostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\TestCase;

/** T30 §14 U4: while the utility engine saves for a goal, legacy Regolith spenders only use the surplus. */
class RegolithHoldTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    private function placeCandidate(BotSession $bot): mixed
    {
        return collect(BotStrategy::default(BotProfile::named('default')))->firstWhere('name', 'place_building')['when']($bot);
    }

    private function bootRich(): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->setCc($bot, 2);
        $this->placeAt($bot, 41, 1);
        $this->setResource($bot, 3, 200);
        $this->setCredits($bot, 5000);
        $this->setSupply($bot, 1000);
        $this->giveAp($bot, 15);

        return $bot;
    }

    public function test_without_a_hold_legacy_placement_is_unchanged(): void
    {
        $this->assertNotNull($this->placeCandidate($this->bootRich()));
    }

    public function test_a_hold_keeps_legacy_placements_above_the_saved_regolith(): void
    {
        $bot = $this->bootRich();
        $bot->regolithHold = 150;

        $candidate = $this->placeCandidate($bot);

        if ($candidate !== null) {
            $placementRegolith = (int) (app(BuildingCostService::class)->placementCost((int) $candidate[0]['building_id'])[3] ?? 0);
            $this->assertGreaterThanOrEqual(150, 200 - $placementRegolith, 'placement must not dip into the hold: '.json_encode($candidate[0]));
        }
        $this->assertTrue(true);
    }

    /** Phase 2, CC Lv3, Regolith short of CC Lv4 (HeuristicGoalProviderTest fixture). */
    private function bootSavingForCc4(): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->setPhase($bot, 2);
        $this->setCc($bot, 3);
        $this->placeAt($bot, 41, 1);
        $this->placeAt($bot, BuildingId::Sciencelab->value, 2);
        $this->placeAt($bot, BuildingId::Hangar->value, 2);
        $this->setResource($bot, 3, 50);
        $this->setSupply($bot, 1000);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }
        $this->giveAp($bot, 15);

        return $bot;
    }

    public function test_the_utility_engine_sets_the_hold_from_its_goal(): void
    {
        $bot = $this->bootSavingForCc4();

        UtilityStrategy::rules(BotProfile::named('default'))[0]['when']($bot);

        $this->assertSame(120, $bot->regolithHold);
    }

    public function test_shadow_and_adapter_engines_never_set_a_hold(): void
    {
        $bot = $this->bootSavingForCc4();
        $profile = BotProfile::named('default');

        UtilityStrategy::shadow($profile)[0]['when']($bot);
        UtilityStrategy::adapterOnly($profile)[0]['when']($bot);

        $this->assertSame(0, $bot->regolithHold, 'shadow/adapter must leave legacy spending (and K22) untouched');
    }
}
