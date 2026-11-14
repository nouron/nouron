<?php

namespace Tests\Feature;

use App\Enums\BuildingId;
use App\Models\Run;
use App\Models\RunObjective;
use App\Models\User;
use App\Services\MerchantService;
use App\Services\ResourceFlowService;
use App\Services\RunProgressService;
use App\Services\TickService;
use App\Services\TrustService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T59 trust scale 0..100 (docs/superpowers/specs/2026-11-14-vertrauen-skala.md):
 * base value 50, five bands with absolute AP, defeat below 5, no production factor,
 * absolute Security Hub mitigation, fading merchant boost, prosperity at band
 * "content", and the trust number only leaving the API with a science lab.
 */
class TrustScaleTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const TICK = 100;

    private TrustService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->make(TickService::class)->setTickCount(self::TICK);
        $this->service = $this->app->make(TrustService::class);

        DB::table('trust_events')->delete();
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_researches')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['hunger_streak' => 0]);
    }

    private function setTrust(int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => 12],
            ['amount' => $amount]
        );
    }

    private function setBuilding(int $buildingId, int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => $buildingId, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 10, 'ap_spend' => 0]
        );
    }

    // ── Bands and AP ─────────────────────────────────────────────────────────

    public function test_bands_and_ap_follow_the_owner_table_with_inclusive_lower_bounds(): void
    {
        $expected = [
            0 => ['riot', -2], 14 => ['riot', -2],
            15 => ['restless', -1], 39 => ['restless', -1],
            40 => ['stable', 0], 59 => ['stable', 0],
            60 => ['content', 1], 79 => ['content', 1],
            80 => ['euphoric', 2], 100 => ['euphoric', 2],
        ];

        foreach ($expected as $trust => [$key, $ap]) {
            $this->assertSame($key, $this->service->bandKey($trust), "band at {$trust}");
            $this->assertSame($ap, $this->service->getApBonus($trust), "AP at {$trust}");
        }
    }

    public function test_band_label_comes_from_the_band_key(): void
    {
        $this->assertSame(__('trust.band_content'), $this->service->getBand(65));
    }

    public function test_bands_list_is_ascending_with_ranges(): void
    {
        $this->assertSame([
            ['key' => 'riot', 'min' => 0, 'max' => 14, 'ap' => -2],
            ['key' => 'restless', 'min' => 15, 'max' => 39, 'ap' => -1],
            ['key' => 'stable', 'min' => 40, 'max' => 59, 'ap' => 0],
            ['key' => 'content', 'min' => 60, 'max' => 79, 'ap' => 1],
            ['key' => 'euphoric', 'min' => 80, 'max' => 100, 'ap' => 2],
        ], $this->service->bands());
    }

    // ── Base value and clamp ─────────────────────────────────────────────────

    public function test_breakdown_contains_base_value_as_own_source(): void
    {
        $b = $this->service->breakdown(self::COLONY_ID, self::TICK);

        $this->assertSame(50, $b['base']);
        $this->assertSame(50, $b['total']);
    }

    public function test_total_is_clamped_to_zero_and_hundred(): void
    {
        config(['game.trust.base' => 0]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['hunger_streak' => 30]);
        $this->assertSame(0, $this->service->calculateTrust(self::COLONY_ID, self::TICK));

        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['hunger_streak' => 0]);
        config(['game.trust.base' => 50]);
        $this->setBuilding(46, 50); // hospital far beyond max level
        $this->assertSame(100, $this->service->calculateTrust(self::COLONY_ID, self::TICK));
    }

    public function test_missing_trust_row_reads_as_base_value(): void
    {
        DB::table('colony_resources')->where('resource_id', 12)->delete();

        $this->assertSame(50, $this->service->getTrust(self::COLONY_ID));
    }

    // ── Defeat below 5 ───────────────────────────────────────────────────────

    public function test_run_fails_below_five_not_at_five(): void
    {
        $run = Run::create([
            'user_id' => self::USER_ID, 'colony_id' => self::COLONY_ID,
            'current_tick' => 30, 'status' => 'active', 'phase' => 2,
        ]);
        $progress = $this->app->make(RunProgressService::class);

        $this->setTrust(5);
        $this->assertNotSame('trust_collapse', $progress->checkFailStates($run));

        $this->setTrust(4);
        $this->assertSame('trust_collapse', $progress->checkFailStates($run));
    }

    // ── Production factor removed ────────────────────────────────────────────

    public function test_production_factor_is_gone(): void
    {
        $this->assertNull(config('game.trust.production_multiplier'));
        $this->assertFalse(method_exists(TrustService::class, 'getProductionMultiplier'));
    }

    public function test_trust_band_does_not_change_production_per_sol(): void
    {
        DB::table('colony_buildings')->insert([
            'colony_id' => self::COLONY_ID, 'building_id' => BuildingId::BioFacility->value,
            'instance_id' => 1, 'level' => 3, 'status_points' => 10, 'ap_spend' => 0,
        ]);
        $flow = $this->app->make(ResourceFlowService::class);

        $this->setTrust(10);
        $low = $flow->perSol(self::COLONY_ID)['organika']['sources'];
        $this->setTrust(95);
        $high = $flow->perSol(self::COLONY_ID)['organika']['sources'];

        $this->assertSame($low, $high);
        $this->assertGreaterThan(0, $high['agrardom']);
    }

    // ── Security Hub: absolute mitigation ────────────────────────────────────

    public function test_security_hub_weakens_negative_events_by_one(): void
    {
        $this->setBuilding(53, 1);
        DB::table('trust_events')->insert([
            ['colony_id' => self::COLONY_ID, 'tick' => self::TICK, 'event_type' => 'colony_threatened'],
            ['colony_id' => self::COLONY_ID, 'tick' => self::TICK, 'event_type' => 'story_concern_failed'],
            ['colony_id' => self::COLONY_ID, 'tick' => self::TICK, 'event_type' => 'trade_success'],
        ]);

        $values = collect($this->service->eventLines(self::COLONY_ID, self::TICK))->pluck('value', 'type');

        $this->assertSame(config('game.trust.events.colony_threatened') + 1, $values['colony_threatened']);
        $this->assertArrayNotHasKey('story_concern_failed', $values->all(), '-1 mitigated to 0 drops out');
        $this->assertSame(config('game.trust.events.trade_success'), $values['trade_success']);
    }

    // ── Merchant trust boost as a fading event ───────────────────────────────

    public function test_merchant_trust_boost_fires_event_that_fades_over_five_sols(): void
    {
        DB::table('colony_buildings')->insert([
            'colony_id' => self::COLONY_ID, 'building_id' => BuildingId::Bar->value,
            'instance_id' => 1, 'level' => 1, 'status_points' => 10, 'ap_spend' => 0,
        ]);
        $visitId = DB::table('merchant_visits')->insertGetId([
            'colony_id' => self::COLONY_ID, 'tick_start' => self::TICK, 'tick_end' => self::TICK + 1,
            'was_visited' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $itemId = DB::table('merchant_items')->insertGetId([
            'visit_id' => $visitId, 'item_type' => 'trust_boost', 'label' => 'x',
            'cost_credits' => 100, 'payload' => null, 'sold' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['credits' => 10000]);
        $this->setTrust(50);

        $result = $this->app->make(MerchantService::class)->buyItem($itemId, self::COLONY_ID, self::USER_ID);

        $this->assertTrue($result['ok']);
        $this->assertSame(50, $this->service->getTrust(self::COLONY_ID), 'stored value is not poked directly');
        $this->assertDatabaseHas('trust_events', [
            'colony_id' => self::COLONY_ID, 'tick' => self::TICK + 1, 'event_type' => 'merchant_trust_boost',
        ]);

        $perSol = [];
        for ($t = self::TICK + 1; $t <= self::TICK + 6; $t++) {
            $perSol[] = $this->service->breakdown(self::COLONY_ID, $t)['events'];
        }
        $this->assertSame([8, 6, 5, 3, 2, 0], $perSol);
    }

    // ── Colony prosperity: at least "content" ────────────────────────────────

    public function test_prosperity_counts_from_band_content_inclusive(): void
    {
        $run = Run::create([
            'user_id' => self::USER_ID, 'colony_id' => self::COLONY_ID, 'current_tick' => 40,
            'status' => 'active', 'phase' => 2, 'phase2_start_tick' => 20, 'started_at' => now()->subHour(),
        ]);
        $objective = RunObjective::create([
            'run_id' => $run->id, 'task_key' => 'task_colony_prosperity', 'target_value' => 12,
            'current_value' => 3, 'streak_value' => 3, 'completed_at' => null,
        ]);
        $progress = $this->app->make(RunProgressService::class);

        $this->setTrust(60);
        $progress->updateObjectiveProgress($run);
        $this->assertSame(4, $objective->refresh()->streak_value);

        $this->setTrust(59);
        $progress->updateObjectiveProgress($run);
        $this->assertSame(0, $objective->refresh()->streak_value);
    }

    // ── API: trust number only with a science lab ────────────────────────────

    private function shell(): array
    {
        $user = User::where('user_id', self::USER_ID)->firstOrFail();

        return $this->actingAs($user)->getJson('/api/v1/shell')->assertOk()->json();
    }

    public function test_shell_without_lab_hides_number_and_source_amounts(): void
    {
        $this->setBuilding(46, 1); // hospital: a building source
        $this->setTrust(62);

        $shell = $this->shell();
        $trust = $shell['trust'];

        $this->assertSame('content', $trust['band']);
        $this->assertSame(1, $trust['ap']);
        $this->assertFalse($trust['lab']);
        $this->assertCount(5, $trust['bands']);
        $this->assertArrayNotHasKey('total', $trust);
        $this->assertSame(['base', 'buildings'], array_column($trust['sources'], 'key'));
        foreach ($trust['sources'] as $source) {
            $this->assertSame(['key'], array_keys($source));
        }

        $this->assertArrayNotHasKey('trust_breakdown', $shell);
        $this->assertArrayNotHasKey('trust_chip_red', $shell);
        $this->assertArrayNotHasKey('hunger_penalty', $shell);
        $this->assertArrayNotHasKey('12', $shell['resource_bar_possessions']);
        $this->assertStringNotContainsString('62', json_encode($trust));
    }

    public function test_shell_with_lab_shows_sources_with_amounts_and_total(): void
    {
        $this->setBuilding(BuildingId::Sciencelab->value, 1);
        $this->setBuilding(46, 1);
        $this->setTrust(62);

        $trust = $this->shell()['trust'];

        $this->assertTrue($trust['lab']);
        $this->assertSame(62, $trust['total']);
        $sources = collect($trust['sources'])->pluck('value', 'key')->all();
        $this->assertSame(50, $sources['base']);
        $this->assertSame((int) config('buildings.infirmary.trust_per_lv'), $sources['buildings']);
        $this->assertArrayHasKey('hunger', $sources);
    }

    public function test_lab_at_level_zero_does_not_count(): void
    {
        $this->setBuilding(BuildingId::Sciencelab->value, 0);
        $this->setTrust(62);

        $this->assertFalse($this->shell()['trust']['lab']);
    }
}
