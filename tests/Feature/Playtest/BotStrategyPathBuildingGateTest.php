<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\PathBuildingGateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Playtest\Utility\BotContext;
use Tests\Feature\Playtest\Utility\HeuristicGoalProvider;
use Tests\TestCase;

/**
 * The server-side path-building gate (PathBuildingGateService, 2026-10-14) and the
 * bot: place_building must not pick a path building the build menu marks as locked
 * (it would collect path_building_gate rejections), and the utility goal's
 * `placed < CC level − 1` rule must agree with the server.
 */
class BotStrategyPathBuildingGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_menu_entries_are_no_placement_candidates(): void
    {
        $rows = [
            ['building_id' => 31, 'locked' => true],
            ['building_id' => 52, 'locked' => true],
            ['building_id' => 54, 'locked' => false],
        ];

        $ordered = BotStrategy::orderPlacementCandidates($rows, [41 => 1, 44 => 1], 'auto');

        $this->assertSame([54], array_column($ordered, 'building_id'));
    }

    public function test_place_building_skips_the_second_path_building_at_cc2(): void
    {
        $bot = $this->boot(ccLevel: 2);
        $this->placePath($bot, 31);

        $candidate = $this->placeCandidate($bot);

        $this->assertNotContains($candidate === null ? null : (int) $candidate[0]['building_id'], PathBuildingGateService::PATH_BUILDING_IDS);
    }

    public function test_place_building_takes_the_second_path_building_at_cc3(): void
    {
        $bot = $this->boot(ccLevel: 3);
        $this->placePath($bot, 31);

        $this->assertSame(44, (int) $this->placeCandidate($bot)[0]['building_id']);
    }

    /** @return array<string, array{int, int}> */
    public static function gateStates(): array
    {
        $states = [];
        foreach ([1, 2, 3, 4, 5] as $cc) {
            foreach ([0, 1, 2] as $placed) {
                $states["cc{$cc}_placed{$placed}"] = [$cc, $placed];
            }
        }

        return $states;
    }

    /** HeuristicGoalProvider::nextPathBuilding()'s own `placed >= CC level − 1` stop matches the server gate. */
    #[DataProvider('gateStates')]
    public function test_goal_provider_path_rule_matches_the_server_gate(int $ccLevel, int $placed): void
    {
        $bot = $this->boot($ccLevel);
        foreach (array_slice([31, 44], 0, $placed) as $id) {
            $this->placePath($bot, $id);
        }
        $ctx = BotContext::capture($bot, BotProfile::named('default'));

        $next = HeuristicGoalProvider::nextPathBuilding($bot, $ctx);
        $serverBlocks = app(PathBuildingGateService::class)->blockingCcLevel($bot->colonyId, 52, $ccLevel) !== null;

        $this->assertSame($serverBlocks, $next === null);
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function boot(int $ccLevel): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::CommandCenter->value)->update(['level' => $ccLevel, 'ap_spend' => 0]);
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->whereIn('building_id', [31, 44, 52])->delete();
        $this->placePath($bot, 41);
        DB::table('colony_resources')->updateOrInsert(['colony_id' => $bot->colonyId, 'resource_id' => 3], ['amount' => 5000]);
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['credits' => 5000, 'supply' => 1000]);

        return $bot;
    }

    private function placePath(BotSession $bot, int $buildingId): void
    {
        $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('is_colony_zone', 1)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('colony_buildings as cb')
                ->where('cb.colony_id', $bot->colonyId)
                ->whereColumn('cb.tile_x', 'colony_tiles.q')
                ->whereColumn('cb.tile_y', 'colony_tiles.r'))
            ->where(fn ($q) => $q->where('q', '!=', 0)->orWhere('r', '!=', 0))
            ->first();
        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId, 'building_id' => $buildingId, 'instance_id' => 1, 'level' => 1,
            'status_points' => 20, 'ap_spend' => 0, 'tile_x' => $tile->q, 'tile_y' => $tile->r,
        ]);
    }

    private function placeCandidate(BotSession $bot): ?array
    {
        foreach (BotStrategy::default(BotProfile::named('default')) as $rule) {
            if ($rule['name'] === 'place_building') {
                return $rule['when']($bot);
            }
        }

        $this->fail('place_building rule not found');
    }
}
