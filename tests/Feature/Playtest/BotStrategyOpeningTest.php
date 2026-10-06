<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T30 step 0: the bot's opening profile decides which path building (sciencelab
 * 31 / hangar 44 / bar 52) it places first, and the fixed follow-up order. While
 * no path building stands, a non-auto opening waits instead of building another.
 */
class BotStrategyOpeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_places_the_sciencelab_first_as_before(): void
    {
        $bot = $this->bootAtCc2WithAgrardom();

        $this->assertSame(31, $this->firstCandidateId($bot, 'auto'));
        $this->assertSame([31, 44, 52], $this->orderedPathIds('auto'));
    }

    public function test_labor_places_the_sciencelab_first(): void
    {
        $bot = $this->bootAtCc2WithAgrardom();

        $this->assertSame(31, $this->firstCandidateId($bot, 'labor'));
    }

    public function test_hangar_places_the_hangar_first_and_waits_on_the_other_path_buildings(): void
    {
        $bot = $this->bootAtCc2WithAgrardom();

        $this->assertSame(44, $this->firstCandidateId($bot, 'hangar'));
        $this->assertSame([44], $this->orderedPathIds('hangar'));
    }

    public function test_cantina_places_the_bar_first_and_waits_on_the_other_path_buildings(): void
    {
        $bot = $this->bootAtCc2WithAgrardom();

        $this->assertSame(52, $this->firstCandidateId($bot, 'cantina'));
        $this->assertSame([52], $this->orderedPathIds('cantina'));
    }

    public function test_hangar_opening_continues_with_sciencelab_then_bar(): void
    {
        $bot = $this->bootAtCc2WithAgrardom();
        $this->placeBuilding($bot, 44);

        $this->assertSame(31, $this->firstCandidateId($bot, 'hangar'));

        $this->placeBuilding($bot, 31);
        $this->assertSame(52, $this->firstCandidateId($bot, 'hangar'));
    }

    public function test_cantina_opening_continues_with_sciencelab_before_hangar(): void
    {
        $bot = $this->bootAtCc2WithAgrardom();
        $this->placeBuilding($bot, 52);

        $this->assertSame(31, $this->firstCandidateId($bot, 'cantina'));
    }

    public function test_path_building_order_per_opening(): void
    {
        $this->assertSame([31, 44, 52], BotStrategy::pathBuildingOrder('auto'));
        $this->assertSame([31, 44, 52], BotStrategy::pathBuildingOrder('labor'));
        $this->assertSame([44, 31, 52], BotStrategy::pathBuildingOrder('hangar'));
        $this->assertSame([52, 31, 44], BotStrategy::pathBuildingOrder('cantina'));
    }

    public function test_hangar_opening_hires_the_pilot_right_after_the_hangar_without_a_ship(): void
    {
        $bot = $this->bootAtCc2WithAgrardom();
        $this->hireEngineer($bot);
        $this->placeBuilding($bot, 44);

        $this->assertSame(89, $this->rule('hangar', 'hire_advisor')['when']($bot));
    }

    public function test_cantina_opening_hires_the_trader_right_after_the_cantina(): void
    {
        $bot = $this->bootAtCc2WithAgrardom();
        $this->hireEngineer($bot);
        $this->placeBuilding($bot, 52);

        $this->assertSame(92, $this->rule('cantina', 'hire_advisor')['when']($bot));
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    /** CC Lv2, Agrardom placed, plenty of Regolith/Credits, no path building placed. */
    private function bootAtCc2WithAgrardom(): BotSession
    {
        $bot = BotSession::boot($this, 1);

        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::CommandCenter->value)
            ->update(['level' => 2, 'ap_spend' => 0]);
        foreach ([31, 44, 52] as $pathId) {
            DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', $pathId)->delete();
        }
        $this->placeBuilding($bot, 41);

        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'resource_id' => 3],
            ['amount' => 5000],
        );
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['credits' => 5000, 'supply' => 1000]);

        return $bot;
    }

    private function placeBuilding(BotSession $bot, int $buildingId): void
    {
        $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('is_colony_zone', 1)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('colony_buildings as cb')
                ->where('cb.colony_id', $bot->colonyId)
                ->whereColumn('cb.tile_x', 'colony_tiles.q')
                ->whereColumn('cb.tile_y', 'colony_tiles.r'))
            ->where(fn ($q) => $q->where('q', '!=', 0)->orWhere('r', '!=', 0))
            ->first();
        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId,
            'building_id' => $buildingId,
            'instance_id' => 1,
            'level' => 1,
            'status_points' => 20,
            'ap_spend' => 0,
            'tile_x' => $tile->q,
            'tile_y' => $tile->r,
        ]);
    }

    private function hireEngineer(BotSession $bot): void
    {
        DB::table('advisors')->where('colony_id', $bot->colonyId)->delete();
        DB::table('advisors')->insert([
            'user_id' => $bot->userId,
            'colony_id' => $bot->colonyId,
            'personell_id' => 35,
            'rank' => 1,
            'active_ticks' => 0,
        ]);
    }

    /** Path buildings left in the ordered candidate list of a colony with no path building placed. */
    private function orderedPathIds(string $opening): array
    {
        $rows = array_map(fn (int $id): array => ['building_id' => $id], [28, 31, 41, 44, 46, 52]);
        $ordered = BotStrategy::orderPlacementCandidates($rows, [41 => 1], $opening);

        return array_values(array_intersect(array_column($ordered, 'building_id'), [31, 44, 52]));
    }

    private function firstCandidateIdWithOnlyLaborAffordable(BotSession $bot, string $opening): ?int
    {
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['credits' => 60]);

        return $this->firstCandidateId($bot, $opening);
    }

    private function firstCandidateId(BotSession $bot, string $opening): ?int
    {
        // The candidate is memoized per BotSession until the next logged action; the
        // tests change DB state without acting, so drop the memo first.
        $memo = new \ReflectionProperty(BotSession::class, 'memo');
        $memo->setValue($bot, []);

        $candidate = $this->rule($opening, 'place_building')['when']($bot);

        return $candidate === null ? null : (int) $candidate[0]['building_id'];
    }

    /**
     * @return array{name:string, when:callable, do:callable}
     */
    private function rule(string $opening, string $name): array
    {
        foreach (BotStrategy::default(BotProfile::named('default')->withOpening($opening)) as $rule) {
            if ($rule['name'] === $name) {
                return $rule;
            }
        }

        $this->fail("Rule {$name} not found");
    }
}
