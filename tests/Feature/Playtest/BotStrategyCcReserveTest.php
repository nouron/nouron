<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\BuildingCostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T17 (2026-09-25): in Phase 2 the bot kept spending Regolith on new buildings,
 * level-ups and Regolith-giving bar trades while every remaining knowledge level
 * was locked behind the next CC level (game.knowledge_cc_level_cap). Research
 * had nothing left to take the AP, so 26-28 AP idled per Sol for ~30 Sols
 * (seed 5: CC5 only on Sol 83).
 *
 * Rule: while the next CC level is the research bottleneck, keep the Regolith
 * for that CC level-up — other Regolith spending may only use the surplus.
 */
class BotStrategyCcReserveTest extends TestCase
{
    use RefreshDatabase;

    private const RES_REGOLITH = 3;

    private const RES_COMPOUNDS = 4;

    public function test_place_building_keeps_the_regolith_for_the_cc_level_up_when_research_is_cc_gated(): void
    {
        $bot = $this->bootCcGatedPhase2();
        $reserve = $this->ccLevelUpRegolith();
        $this->setRegolith($bot, $reserve + 10);

        $candidate = $this->rule('place_building')['when']($bot);

        if ($candidate !== null) {
            $placementRegolith = $this->placementRegolith((int) $candidate[0]['building_id']);
            $this->assertGreaterThanOrEqual(
                $reserve,
                $reserve + 10 - $placementRegolith,
                'placement must not dip into the Regolith reserved for the CC level-up: '.json_encode($candidate[0]),
            );
        }
        $this->assertNull($candidate, 'no building is cheap enough to fit into a 10-Rg surplus');
    }

    public function test_place_building_uses_the_surplus_above_the_cc_reserve(): void
    {
        $bot = $this->bootCcGatedPhase2();
        $this->setRegolith($bot, $this->ccLevelUpRegolith() + 1000);

        $this->assertNotNull($this->rule('place_building')['when']($bot), 'with enough surplus the bot still builds');
    }

    public function test_place_building_is_not_held_back_while_research_can_still_use_the_ap(): void
    {
        $bot = $this->bootCcGatedPhase2();
        // One knowledge still below the CC-gated level: research has work for the AP.
        DB::table('colony_researches')->where('colony_id', $bot->colonyId)->where('research_id', 90)->update(['level' => 2]);
        $this->setRegolith($bot, $this->ccLevelUpRegolith() + 10);

        $this->assertNotNull($this->rule('place_building')['when']($bot));
    }

    public function test_production_invest_keeps_the_regolith_for_the_cc_level_up_when_research_is_cc_gated(): void
    {
        $bot = $this->bootCcGatedPhase2();
        $this->placeAt($bot, 41, 1); // bioFacility Lv1, next click charges Regolith
        $this->setRegolith($bot, $this->ccLevelUpRegolith() + 10);

        $candidate = $this->rule('invest_production')['when']($bot);

        $this->assertEmpty($candidate, 'a Regolith-charging level-up must wait for the CC level-up: '.json_encode($candidate));
    }

    public function test_bar_offer_giving_regolith_is_not_accepted_into_the_cc_reserve(): void
    {
        $bot = $this->bootCcGatedPhase2();
        $this->setRegolith($bot, $this->ccLevelUpRegolith() + 10);
        DB::table('bar_offers')->where('colony_id', $bot->colonyId)->delete();
        DB::table('bar_offers')->insert([
            'colony_id' => $bot->colonyId,
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 40,
            'get_resource_id' => self::RES_COMPOUNDS,
            'get_amount' => 10,
            'expires_tick' => 9999,
            'is_accepted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertEmpty($this->rule('accept_bar_offer')['when']($bot));
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    /**
     * Phase 2, CC Lv4 with no running cycle, all three path buildings built and
     * every knowledge on Lv4 — the next knowledge level needs CC Lv5.
     */
    private function bootCcGatedPhase2(): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => 2]);
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::CommandCenter->value)
            ->update(['level' => 4, 'ap_spend' => 0]);

        foreach ([BuildingId::Sciencelab->value, BuildingId::Hangar->value, BuildingId::Bar->value] as $pathId) {
            DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', $pathId)->delete();
            $this->placeAt($bot, $pathId, 3);
        }

        foreach (range(90, 96) as $researchId) {
            DB::table('colony_researches')->updateOrInsert(
                ['colony_id' => $bot->colonyId, 'research_id' => $researchId],
                ['level' => 4, 'status_points' => 20, 'ap_spend' => 0],
            );
        }

        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'resource_id' => self::RES_COMPOUNDS],
            ['amount' => 1000],
        );
        // Supply is not what this test is about — keep it out of the way.
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['supply' => 1000]);

        return $bot;
    }

    private function ccLevelUpRegolith(): int
    {
        return app(BuildingCostService::class)->levelupRegolith(BuildingId::CommandCenter->value, 5);
    }

    private function placementRegolith(int $buildingId): int
    {
        return (int) (app(BuildingCostService::class)->placementCost($buildingId)[self::RES_REGOLITH] ?? 0);
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
    private function rule(string $name): array
    {
        foreach (BotStrategy::default() as $rule) {
            if ($rule['name'] === $name) {
                return $rule;
            }
        }

        $this->fail("Rule {$name} not found");
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
}
