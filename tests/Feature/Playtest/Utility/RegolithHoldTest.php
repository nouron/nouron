<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use App\Services\BuildingCostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_a_hold_leaves_legacy_placement_only_the_slack(): void
    {
        $bot = $this->bootRich();

        $bot->regolithHold = 190;   // 10 Rg slack: no placement is that cheap
        $this->assertNull($this->placeCandidate($bot));

        $bot->regolithHold = 130;   // 70 Rg slack: housing (65) fits, path buildings do not
        $this->logSolNext($bot, 0);  // invalidate the place_candidate memo
        $candidate = $this->placeCandidate($bot);
        $this->assertNotNull($candidate);
        $placementRegolith = (int) (app(BuildingCostService::class)->placementCost((int) $candidate[0]['building_id'])[3] ?? 0);
        $this->assertLessThanOrEqual(70, $placementRegolith, 'placement must not dip into the hold: '.json_encode($candidate[0]));
    }

    public function test_the_goal_s_own_path_placement_is_exempt_from_the_hold(): void
    {
        $bot = $this->bootRich();
        foreach ([31, 44, 52] as $pathId) {
            DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', $pathId)->delete();
        }
        $this->setResource($bot, 3, 130);
        $bot->regolithHold = 120;
        $place = fn () => collect(BotStrategy::default(BotProfile::named('default')->withOpening('hangar')))
            ->firstWhere('name', 'place_building')['when']($bot);

        $this->assertNull($place(), 'without the exemption the hold blocks the goal itself');

        $bot->holdExemptBuildingId = BuildingId::Hangar->value;
        $this->logSolNext($bot, 0);   // invalidate the place_candidate memo

        $this->assertSame(BuildingId::Hangar->value, (int) ($place()[0]['building_id'] ?? 0));
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

        $this->assertSame(42, $bot->regolithHold, 'CC Lv4 (120) in 3 Sols at 26/Sol from 50: 8 Rg slack');
        $this->assertNull($bot->holdExemptBuildingId);
    }

    public function test_a_path_goal_exempts_its_own_placement(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->setCc($bot, 2);
        $this->placeAt($bot, 41, 1);
        foreach ([31, 44, 52] as $pathId) {
            DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', $pathId)->delete();
        }
        $this->setResource($bot, 3, 60);
        $this->setSupply($bot, 1000);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }
        $this->giveAp($bot, 15);

        UtilityStrategy::rules(BotProfile::named('default')->withOpening('hangar'))[0]['when']($bot);

        $this->assertSame(BuildingId::Hangar->value, $bot->holdExemptBuildingId);
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
