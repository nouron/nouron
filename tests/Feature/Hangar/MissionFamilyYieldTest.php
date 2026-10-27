<?php

namespace Tests\Feature\Hangar;

use App\Services\HangarService;
use App\Support\MissionReward;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T41: the family level raises the payout of scalable rewards; level 0 equals today.
 */
class MissionFamilyYieldTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BAY = 1;

    private const SHIP_DRONE = 85;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        Config::set('game.missions.difficulty.base_chance', ['easy' => 1.0, 'normal' => 1.0, 'hard' => 1.0]);
        Config::set('game.missions.difficulty.chance_cap', 1.0);

        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', self::BAY)->update(['bay' => null]);
        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
    }

    private function setFamilyLevel(string $family, int $level): void
    {
        DB::table('colony_path_levels')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'kind' => 'mission_family', 'entity_key' => $family],
            ['level' => $level, 'ap_spend' => 0]
        );
    }

    private function dispatchFixture(string $missionKey, int $dispatchTick): void
    {
        DB::table('colony_ships')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'ship_id' => self::SHIP_DRONE],
            ['bay' => self::BAY, 'ship_state' => 'dispatched', 'level' => 1, 'status_points' => 20.0, 'ap_spend' => 0]
        );
        DB::table('colony_hangar_missions')->insert([
            'colony_id' => self::COLONY_ID,
            'bay' => self::BAY,
            'ship_id' => self::SHIP_DRONE,
            'destination' => $missionKey,
            'sol_distance' => 1,
            'target' => null,
            'difficulty' => 'normal',
            'dispatch_tick' => $dispatchTick,
            'state' => 'active',
            'created_at' => now(),
        ]);
    }

    /** @return array<string, mixed> logged reward details of the completed mission */
    private function resolve(string $missionKey, int $dispatchTick = 30000): array
    {
        $this->dispatchFixture($missionKey, $dispatchTick);
        $resolveTick = $dispatchTick + 2;
        Artisan::call('game:tick', ['--run' => 1, '--tick' => $resolveTick]);

        $log = DB::table('colony_log')->where('tick', $resolveTick)->where('event', 'hangar.mission_completed')->first();
        $this->assertNotNull($log);

        return json_decode($log->parameters, true)['rewards'];
    }

    public function test_payout_at_level_zero_equals_current_behavior(): void
    {
        $rewards = $this->resolve('mission_courier_run');

        $this->assertSame((int) config('missions.catalog.mission_courier_run.reward.credits'), $rewards['credits']);
    }

    public function test_payout_scales_credits_with_family_level(): void
    {
        $this->setFamilyLevel('trade', 2);

        $rewards = $this->resolve('mission_courier_run');

        $base = (int) config('missions.catalog.mission_courier_run.reward.credits');
        $this->assertSame((int) round($base * (1 + 2 * (float) config('path_depth.yield_per_level'))), $rewards['credits']);
        $this->assertGreaterThan($base, $rewards['credits']);
    }

    public function test_non_scalable_rewards_not_scaled(): void
    {
        $this->setFamilyLevel('trade', 3);

        $rewards = $this->resolve('mission_trade_convoy');

        $this->assertSame(config('missions.catalog.mission_trade_convoy.reward.trust_event'), $rewards['trust_event']);
        $this->assertSame(__('missions.reward_trust'), MissionReward::label(['trust_event' => 'x'], 1.5));
    }

    public function test_dialog_reward_label_matches_paid_amount(): void
    {
        $this->setFamilyLevel('trade', 3);
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => 3, 'status_points' => 20, 'ap_spend' => 0]
        );

        $entry = collect($this->app->make(HangarService::class)->getMissionCatalogFor(self::COLONY_ID))
            ->firstWhere('key', 'mission_courier_run');
        $rewards = $this->resolve('mission_courier_run');

        $this->assertSame(__('missions.reward_credits', ['amount' => $rewards['credits']]), $entry['reward_label']);
    }

    public function test_catalog_entry_carries_family_and_level(): void
    {
        $this->setFamilyLevel('trade', 2);
        $catalog = collect($this->app->make(HangarService::class)->getMissionCatalogFor(self::COLONY_ID));

        $trade = $catalog->firstWhere('key', 'mission_courier_run');
        $this->assertSame('trade', $trade['family']);
        $this->assertSame(__('pathdepth.family_trade'), $trade['family_name']);
        $this->assertSame(2, $trade['family_level']);
        $this->assertTrue($trade['family_scales']);

        // Fixed rewards do not scale: the family is shown, the factor hint is not.
        $this->assertFalse(MissionReward::scales(['find_salvage' => true]));
        $this->assertTrue(MissionReward::scales(['loot_table' => [['credits' => 5]]]));
    }

    public function test_label_scales_amounts_and_ranges_with_factor(): void
    {
        $this->assertSame(__('missions.reward_credits', ['amount' => 198]), MissionReward::label(['credits' => 180], 1.1));
        $this->assertSame(__('missions.reward_credits', ['amount' => '770–1210']), MissionReward::label(['credits' => [700, 1100]], 1.1));
    }

    public function test_level_read_at_resolution_not_dispatch(): void
    {
        $this->dispatchFixture('mission_courier_run', 31000);
        $this->setFamilyLevel('trade', 1);

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 31002]);
        $log = DB::table('colony_log')->where('tick', 31002)->where('event', 'hangar.mission_completed')->first();
        $paid = json_decode($log->parameters, true)['rewards']['credits'];

        $base = (int) config('missions.catalog.mission_courier_run.reward.credits');
        $this->assertSame((int) round($base * (1 + (float) config('path_depth.yield_per_level'))), $paid);
    }
}
