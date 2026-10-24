<?php

namespace Tests\Feature\PathDepth;

use App\Exceptions\GameRuleException;
use App\Services\AdvisorService;
use App\Services\PathDepthService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PathDepthServiceTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const FAMILY = 'exploration';

    private PathDepthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(10));
        $this->service = $this->app->make(PathDepthService::class);
        DB::table('colony_path_levels')->where('colony_id', self::COLONY_ID)->delete();
        $this->setBuilding(44, 3);
        $this->setBuilding(52, 3);
        config(['game.bypass.ap_checks' => false]);
    }

    private function setBuilding(int $buildingId, int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => $buildingId, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20.0, 'ap_spend' => 0]
        );
    }

    private function lockedAp(): int
    {
        return (int) DB::table('locked_actionpoints')->where('scope_id', self::COLONY_ID)->sum('spend_ap');
    }

    private function setLevel(string $kind, string $key, int $level, int $apSpend = 0): void
    {
        DB::table('colony_path_levels')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'kind' => $kind, 'entity_key' => $key],
            ['level' => $level, 'ap_spend' => $apSpend]
        );
    }

    private function assertRejected(string $code, callable $fn): void
    {
        try {
            $fn();
            $this->fail("expected GameRuleException {$code}");
        } catch (GameRuleException $e) {
            $this->assertSame($code, $e->errorCode);
        }
    }

    public function test_invest_adds_ap_spend_and_locks_ap(): void
    {
        $result = $this->service->invest(self::COLONY_ID, 'mission_family', self::FAMILY, 2);

        $this->assertSame(2, $result['ap_spend']);
        $this->assertSame(0, $result['level']);
        $this->assertFalse($result['leveled_up']);
        $this->assertSame(2, $this->lockedAp());
    }

    public function test_invest_levels_up_when_threshold_reached_and_resets_ap_spend(): void
    {
        $cost = config('path_depth.levelup_ap.mission_family.1');
        $result = $this->service->invest(self::COLONY_ID, 'mission_family', self::FAMILY, $cost);

        $this->assertTrue($result['leveled_up']);
        $this->assertSame(1, $result['level']);
        $this->assertSame(0, $result['ap_spend']);
        $this->assertSame(config('path_depth.levelup_ap.mission_family.2'), $result['ap_for_levelup']);
    }

    public function test_invest_caps_points_to_remaining_cost(): void
    {
        $cost = config('path_depth.levelup_ap.mission_family.1');
        $this->service->invest(self::COLONY_ID, 'mission_family', self::FAMILY, $cost + 5);

        $this->assertSame($cost, $this->lockedAp());
    }

    public function test_invest_rejected_when_not_enough_ap_leaves_state_unchanged(): void
    {
        $adv = app(AdvisorService::class);
        $adv->lockActionPoints(self::COLONY_ID, $adv->getAvailableActionPoints(self::COLONY_ID));
        $before = $this->lockedAp();

        $this->assertRejected('insufficient_ap', fn () => $this->service->invest(self::COLONY_ID, 'mission_family', self::FAMILY, 1));

        $this->assertSame($before, $this->lockedAp());
        $this->assertSame(0, DB::table('colony_path_levels')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_invest_rejected_above_building_cap_without_spending_ap(): void
    {
        $this->setBuilding(44, 1);
        $this->setLevel('mission_family', self::FAMILY, 2);

        $this->assertSame(2, $this->service->levelCap(self::COLONY_ID, 'mission_family'));
        $this->assertRejected('building_level_cap', fn () => $this->service->invest(self::COLONY_ID, 'mission_family', self::FAMILY, 1));
        $this->assertSame(0, $this->lockedAp());
    }

    public function test_level_cap_is_zero_without_building(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->delete();

        $this->assertSame(0, $this->service->levelCap(self::COLONY_ID, 'mission_family'));
        $this->assertRejected('building_level_cap', fn () => $this->service->invest(self::COLONY_ID, 'mission_family', self::FAMILY, 1));
    }

    public function test_invest_rejected_at_level_5(): void
    {
        $this->setBuilding(44, 3);
        $this->setLevel('mission_family', self::FAMILY, 5);

        $this->assertRejected('max_level', fn () => $this->service->invest(self::COLONY_ID, 'mission_family', self::FAMILY, 1));
        $this->assertSame(0, $this->lockedAp());
        $this->assertSame(0, $this->service->state(self::COLONY_ID, 'mission_family', self::FAMILY)['ap_for_levelup']);
    }

    public function test_character_not_met_is_rejected(): void
    {
        $this->assertRejected('not_met', fn () => $this->service->invest(self::COLONY_ID, 'character', 'veteran', 1));
        $this->assertSame(0, $this->lockedAp());
        $this->assertSame(0, DB::table('colony_path_levels')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_character_met_is_investable(): void
    {
        $slug = array_key_first(array_diff_key(config('characters'), ['bartender' => 1]));
        $this->service->markMet(self::COLONY_ID, $slug);

        $this->assertTrue($this->service->state(self::COLONY_ID, 'character', $slug)['met']);
        $this->assertSame(1, $this->service->invest(self::COLONY_ID, 'character', $slug, 1)['ap_spend']);
    }

    public function test_bartender_is_investable_without_meeting(): void
    {
        $result = $this->service->invest(self::COLONY_ID, 'character', 'bartender', 1);

        $this->assertSame(1, $result['ap_spend']);
        $this->assertTrue($result['met']);
    }

    public function test_mark_met_is_idempotent(): void
    {
        $this->service->markMet(self::COLONY_ID, 'veteran');
        $this->service->invest(self::COLONY_ID, 'character', 'veteran', 2);
        $this->service->markMet(self::COLONY_ID, 'veteran');

        $rows = DB::table('colony_path_levels')->where('colony_id', self::COLONY_ID)->where('entity_key', 'veteran')->get();
        $this->assertCount(1, $rows);
        $this->assertSame(2, (int) $rows[0]->ap_spend);
    }

    public function test_unknown_family_and_unknown_character_rejected(): void
    {
        $this->assertRejected('unknown_entity', fn () => $this->service->invest(self::COLONY_ID, 'mission_family', 'nonsense', 1));
        $this->assertRejected('unknown_entity', fn () => $this->service->invest(self::COLONY_ID, 'character', 'nobody', 1));
        $this->assertRejected('unknown_entity', fn () => $this->service->invest(self::COLONY_ID, 'bogus_kind', 'x', 1));
        $this->assertSame(0, DB::table('colony_path_levels')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_non_positive_points_rejected(): void
    {
        $this->assertRejected('insufficient_ap', fn () => $this->service->invest(self::COLONY_ID, 'mission_family', self::FAMILY, 0));
    }

    public function test_yield_factor_is_one_at_level_zero_and_grows_per_level(): void
    {
        $mission = 'mission_recon_flight';
        $this->assertSame(1.0, $this->service->yieldFactor(self::COLONY_ID, $mission));

        $this->setLevel('mission_family', self::FAMILY, 2);
        $this->assertEqualsWithDelta(1 + 2 * config('path_depth.yield_per_level'), $this->service->yieldFactor(self::COLONY_ID, $mission), 0.0001);
        $this->assertSame(1.0, $this->service->yieldFactor(self::COLONY_ID, 'mission_unknown'));
    }

    public function test_family_of_maps_every_catalog_mission(): void
    {
        foreach (array_keys(config('missions.catalog')) as $key) {
            $this->assertNotNull($this->service->familyOf($key), $key);
        }
        $this->assertNull($this->service->familyOf('mission_unknown'));
    }
}
