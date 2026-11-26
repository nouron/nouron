<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\BuildingCostService;
use App\Services\NexusImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\Utility\UtilityStrategy;
use Tests\TestCase;

/**
 * M4 2026-11-24: no bot ever built an infirmary or a security hub — each needs 25 Werkstoffe,
 * which temple and monument used up first, while thousands of Credits lay idle. Since
 * 2026-11-26 health and defense hang on exactly these buildings, so in Phase 2 the bot buys
 * the missing Werkstoffe from Nexus (Uplink-Station) when the Credits stay above a reserve.
 *
 * M5a 2026-11-26: that import fired twice in 48 utility runs and neither building ever stood —
 * the placement behind it was blocked (savings goal, path buffer) and the Regolith and the
 * Werkstoffe went elsewhere. Import and placement now share their gates and sit together.
 */
class BotStrategyCompoundsImportTest extends TestCase
{
    use PlaysSolLoop, RefreshDatabase;

    private const INFIRMARY = 46;

    private const SECURITY_HUB = 53;

    private const TEMPLE = 32;

    private const MONUMENT = 50;

    private function rule(string $name = 'import_compounds_for_building'): array
    {
        foreach (BotStrategy::default(BotProfile::named('default')) as $rule) {
            if ($rule['name'] === $name) {
                return $rule;
            }
        }

        $this->fail("Rule {$name} not found");
    }

    private function boot(int $credits, int $compounds): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => 2, 'phase2_start_tick' => 1]);
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', BuildingId::CommandCenter->value)->update(['level' => 3]);
        foreach ([BuildingId::UplinkStation->value => [-1, 1], 46 => null, 53 => null] as $id => $tile) {
            DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', $id)->delete();
        }
        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId, 'building_id' => BuildingId::UplinkStation->value, 'instance_id' => 1,
            'level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => -1, 'tile_y' => 1,
        ]);
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['credits' => $credits, 'supply' => 1000]);
        DB::table('colony_resources')->updateOrInsert(['colony_id' => $bot->colonyId, 'resource_id' => 3], ['amount' => 500]);
        DB::table('colony_resources')->updateOrInsert(['colony_id' => $bot->colonyId, 'resource_id' => 4], ['amount' => $compounds]);

        return $bot;
    }

    public function test_buys_the_missing_werkstoffe_for_an_unbuilt_security_hub(): void
    {
        $bot = $this->boot(credits: 8000, compounds: 5);

        $amount = ($this->rule()['when'])($bot);

        $this->assertSame(20, $amount, '25 needed, 5 in stock');
        $this->assertTrue(($this->rule()['do'])($bot, $amount)['ok']);
        $this->assertSame(25, (int) DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 4)->value('amount'));
    }

    public function test_keeps_its_credits_when_the_import_would_eat_the_reserve(): void
    {
        $bot = $this->boot(credits: 2000, compounds: 5);

        $this->assertNull(($this->rule()['when'])($bot));
    }

    public function test_does_nothing_in_phase_one(): void
    {
        $bot = $this->boot(credits: 8000, compounds: 5);
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => 1]);

        $this->assertNull(($this->rule()['when'])($bot));
    }

    /** Credits the import of $shortfall Werkstoffe for the security hub costs. */
    private function price(BotSession $bot, int $shortfall): int
    {
        return app(NexusImportService::class)->totalCost($bot->colonyId, 4, $shortfall)
            + (int) (app(BuildingCostService::class)->placementCost(self::SECURITY_HUB)[1] ?? 0);
    }

    /** CC 4 unlocks the temple, and its next level competes for the same Regolith. */
    private function bootTempleCompetes(int $credits, int $compounds): BotSession
    {
        $bot = $this->boot($credits, $compounds);
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', BuildingId::CommandCenter->value)->update(['level' => 4]);
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->whereIn('building_id', [self::TEMPLE, self::MONUMENT])->delete();
        DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 3)->update(['amount' => 200]);
        // The CC level is set directly, so the larger zone has to be opened by hand.
        DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('ring', 2)->where('tile_type', 'like', 'terrain_%')
            ->update(['is_colony_zone' => 1, 'tile_type' => 'terrain_plain', 'event_type' => null]);
        // Agrardom and all path buildings stand: neither the mandatory placement nor the path buffer interferes.
        foreach ([41, 31, 44, 52] as $id) {
            DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', $id)->delete();
            $tile = BotStrategy::freeZoneTile($bot);
            DB::table('colony_buildings')->insert([
                'colony_id' => $bot->colonyId, 'building_id' => $id, 'instance_id' => 1,
                'level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => $tile->q, 'tile_y' => $tile->r,
            ]);
        }

        return $bot;
    }

    private function isPlaced(BotSession $bot, int $buildingId): bool
    {
        return DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', $buildingId)->whereNotNull('tile_x')->exists();
    }

    private function playUtilitySol(BotSession $bot): void
    {
        $this->playOneSol($bot, UtilityStrategy::rules(BotProfile::named('default')->withEngine('utility')));
    }

    public function test_utility_engine_buys_and_places_a_knowledge_building_although_the_temple_is_affordable(): void
    {
        $bot = $this->bootTempleCompetes(credits: 8000, compounds: 20);

        $this->playUtilitySol($bot);

        $this->assertTrue($this->isPlaced($bot, self::SECURITY_HUB) || $this->isPlaced($bot, self::INFIRMARY), json_encode(array_column($bot->log, 'rule')));
        $this->assertFalse($this->isPlaced($bot, self::TEMPLE));
        $rules = array_column($bot->log, 'rule');
        $import = array_search('import_compounds_for_building', $rules, true);
        $this->assertNotFalse($import);
        $this->assertSame('place_knowledge_building', $rules[$import + 1], 'nothing may spend between import and placement');
    }

    public function test_utility_engine_places_a_knowledge_building_from_stock_before_the_temple(): void
    {
        $bot = $this->bootTempleCompetes(credits: 500, compounds: 25);

        $this->playUtilitySol($bot);

        $this->assertTrue($this->isPlaced($bot, self::SECURITY_HUB) || $this->isPlaced($bot, self::INFIRMARY), json_encode(array_column($bot->log, 'rule')));
        $this->assertFalse($this->isPlaced($bot, self::TEMPLE));
    }

    public function test_temple_and_monument_leave_the_werkstoffe_of_an_unbuilt_knowledge_building(): void
    {
        $bot = $this->bootTempleCompetes(credits: 0, compounds: 20);
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', BuildingId::CommandCenter->value)->update(['level' => 5]);

        $placement = $this->rule('place_building')['when']($bot);
        $focus = (new \ReflectionMethod(BotStrategy::class, 'trustBuildingCandidate'))->invoke(null, $bot);

        foreach ([$placement, $focus] as $candidate) {
            $this->assertNotContains((int) ($candidate[0]['building_id'] ?? 0), [self::TEMPLE, self::MONUMENT]);
        }
    }

    public function test_temple_is_free_to_use_the_surplus_and_everything_once_both_stand(): void
    {
        $bot = $this->bootTempleCompetes(credits: 0, compounds: 40);
        $focus = fn (): ?array => (new \ReflectionMethod(BotStrategy::class, 'trustBuildingCandidate'))->invoke(null, $bot);

        $this->assertContains((int) $focus()[0]['building_id'], [self::SECURITY_HUB, self::INFIRMARY], 'knowledge buildings rank first');

        foreach ([self::SECURITY_HUB => [1, -1], self::INFIRMARY => [-1, 0]] as $id => [$q, $r]) {
            DB::table('colony_buildings')->insert([
                'colony_id' => $bot->colonyId, 'building_id' => $id, 'instance_id' => 1,
                'level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => $q, 'tile_y' => $r,
            ]);
        }
        DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 4)->update(['amount' => 15]);
        $bot->log[] = ['rule' => 'test', 'ok' => true]; // drop memoized reads

        $this->assertSame(self::TEMPLE, (int) $focus()[0]['building_id']);
    }

    public function test_placement_order_ranks_knowledge_buildings_above_temple_and_monument(): void
    {
        $rows = array_map(fn (int $id): array => ['building_id' => $id], [28, 32, 50, 46, 53]);

        $ordered = array_column(BotStrategy::orderPlacementCandidates($rows, [], 'auto'), 'building_id');

        $this->assertEqualsCanonicalizing([46, 53], array_slice($ordered, 0, 2));
        $this->assertSame([32, 50, 28], array_slice($ordered, 2));
    }

    public function test_the_utility_savings_goal_does_not_hold_back_a_knowledge_building(): void
    {
        $bot = $this->boot(credits: 8000, compounds: 25);
        $bot->regolithHold = 500;

        $this->assertNotNull($this->rule('place_knowledge_building')['when']($bot));
    }

    public function test_buys_down_to_a_floor_of_1000_credits(): void
    {
        $bot = $this->boot(credits: 0, compounds: 5);
        $price = $this->price($bot, 20);

        DB::table('user_resources')->where('user_id', $bot->userId)->update(['credits' => $price + 999]);
        $this->assertNull(($this->rule()['when'])($bot));

        DB::table('user_resources')->where('user_id', $bot->userId)->update(['credits' => $price + 1000]);
        $this->assertSame(20, ($this->rule()['when'])($bot));
    }

    public function test_does_not_buy_while_the_placement_would_lack_regolith(): void
    {
        $bot = $this->boot(credits: 8000, compounds: 5);
        $cheapest = (int) app(BuildingCostService::class)->placementCost(self::INFIRMARY)[3];
        DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 3)->update(['amount' => $cheapest - 1]);

        $this->assertNull(($this->rule()['when'])($bot));
    }

    public function test_does_not_buy_without_a_free_tile(): void
    {
        $bot = $this->boot(credits: 8000, compounds: 5);
        DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->update(['is_colony_zone' => 0]);

        $this->assertNull(($this->rule()['when'])($bot));
    }

    public function test_does_not_buy_while_regolith_is_saved_for_a_path_building(): void
    {
        $bot = $this->boot(credits: 8000, compounds: 5);
        DB::table('advisors')->where('colony_id', $bot->colonyId)->delete();
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->whereIn('building_id', [31, 44, 52])->delete();
        $path = min(array_map(fn (int $id): int => (int) app(BuildingCostService::class)->placementCost($id)[3], [31, 44, 52]));
        $knowledge = (int) app(BuildingCostService::class)->placementCost(self::INFIRMARY)[3];
        $this->assertLessThan($path, $knowledge, 'fixture: the infirmary is cheaper than every path building');
        DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 3)->update(['amount' => $path - 1]);

        $this->assertNull(($this->rule()['when'])($bot));
    }
}
