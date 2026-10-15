<?php

namespace Tests\Feature;

use App\Console\Commands\GameTick;
use App\Models\ColonyTile;
use App\Services\BarService;
use App\Services\ColonyTileService;
use App\Services\ResourcesService;
use App\Support\RegolithLedger;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regolith ledger (Wirtschafts-Neuordnung step 1, budget spec E.1 M1/M2): every
 * Regolith booking carries a source/sink tag. The recorder is a pure observer —
 * off by default, switched on only by the PlaytestBot — so these tests switch it
 * on explicitly and check that each booking site reports the right tag and the
 * amount that actually reached the stock.
 *
 * Fixture: Colony 1 (Springfield), user 3 (Bart), run 1.
 */
class RegolithLedgerTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const RES_REGOLITH = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        RegolithLedger::start();
    }

    protected function tearDown(): void
    {
        RegolithLedger::stop();
        parent::tearDown();
    }

    private function regolith(): int
    {
        return (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)
            ->where('resource_id', self::RES_REGOLITH)->value('amount');
    }

    /** @return array<string, int> tag => signed sum of the drained entries */
    private function drained(): array
    {
        $sums = [];
        foreach (RegolithLedger::drain(self::COLONY_ID) as $entry) {
            $sums[$entry['tag']] = ($sums[$entry['tag']] ?? 0) + $entry['delta'];
        }

        return $sums;
    }

    private function callPrivate(object $target, string $method, array $args): mixed
    {
        $ref = new \ReflectionMethod($target, $method);

        return $ref->invokeArgs($target, $args);
    }

    // ── Recorder ─────────────────────────────────────────────────────────────

    public function test_recorder_is_inert_unless_started(): void
    {
        RegolithLedger::stop();
        app(ResourcesService::class)->increaseAmount(self::COLONY_ID, self::RES_REGOLITH, 5, tag: 'import');

        $this->assertFalse(RegolithLedger::active());
        $this->assertSame([], RegolithLedger::drain(self::COLONY_ID));
    }

    public function test_resources_service_records_regolith_with_its_tag_only(): void
    {
        $service = app(ResourcesService::class);
        $service->increaseAmount(self::COLONY_ID, self::RES_REGOLITH, 7, tag: 'import');
        $service->decreaseAmount(self::COLONY_ID, self::RES_REGOLITH, 3, tag: 'repair');
        $service->payCosts([['resource_id' => self::RES_REGOLITH, 'amount' => 2]], self::COLONY_ID, tag: 'build_place');
        $service->increaseAmount(self::COLONY_ID, 4, 9, tag: 'import'); // Werkstoffe: not Regolith
        $service->increaseAmount(self::COLONY_ID, self::RES_REGOLITH, 1); // no tag

        $this->assertSame(['import' => 7, 'repair' => -3, 'build_place' => -2, 'untagged' => 1], $this->drained());
    }

    public function test_drain_returns_only_the_colony_and_empties_the_buffer(): void
    {
        RegolithLedger::record(self::COLONY_ID, 'import', 4);
        RegolithLedger::record(999, 'import', 8);

        $this->assertSame(['import' => 4], $this->drained());
        $this->assertSame([], RegolithLedger::drain(self::COLONY_ID));
    }

    // ── Sources ──────────────────────────────────────────────────────────────

    public function test_tick_books_harvester_base_and_geology_separately(): void
    {
        DB::table('colony_resources')->updateOrInsert(['colony_id' => self::COLONY_ID, 'resource_id' => 12], ['amount' => 0]);
        DB::table('trust_events')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => 92],
            ['level' => 2, 'status_points' => 20, 'ap_spend' => 0]
        );
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 27, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 16, 'ap_spend' => 0, 'tile_x' => 3, 'tile_y' => 0, 'pending_until_tick' => null]
        );
        DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->where('q', 3)->where('r', 0)->delete();
        DB::table('colony_tiles')->insert([
            'colony_id' => self::COLONY_ID, 'q' => 3, 'r' => 0, 'ring' => 3,
            'tile_type' => 'regolith_y2_d2', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0,
            'resource_amount' => 300, 'resource_max' => 300,
        ]);
        $before = $this->regolith();

        Artisan::call('game:tick', ['--tick' => 20005]);

        $sums = $this->drained();
        // y2_d2 fresh 26, geology level 2 = +6, neutral trust (multiplier 1.0).
        $this->assertSame(26, $sums['harvester_base'] ?? null);
        $this->assertSame(6, $sums['geology'] ?? null);
        $this->assertSame($this->regolith() - $before, array_sum($sums), 'ledger must explain the whole stock change of the tick');
    }

    public function test_tick_books_a_delivered_uplink_import(): void
    {
        DB::table('nexus_imports')->insert(['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_REGOLITH, 'amount' => 40, 'deliver_at_tick' => 1]);

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 20010]);

        $this->assertSame(40, $this->drained()['import'] ?? null);
    }

    public function test_mission_rewards_book_direct_regolith_as_ore_and_loot_table_regolith_as_loot(): void
    {
        $tick = app(GameTick::class);
        $before = $this->regolith();

        $this->callPrivate($tick, 'payMissionRewards', [self::COLONY_ID, 3, ['regolith' => 10], null, 1, 100]);
        $this->callPrivate($tick, 'payMissionRewards', [self::COLONY_ID, 3, ['loot_table' => [['regolith' => 7]]], null, 1, 100]);

        $this->assertSame(['mission_ore' => 10, 'mission_loot' => 7], $this->drained());
        $this->assertSame($before + 17, $this->regolith());
    }

    public function test_level_down_recycling_is_booked_as_recycle(): void
    {
        $cb = (object) ['colony_id' => self::COLONY_ID, 'building_id' => 31, 'instance_id' => 1, 'level' => 2];
        $before = $this->regolith();

        $this->callPrivate(app(GameTick::class), 'applyLevelDown', [$cb, 100, [], [], [self::COLONY_ID => 5], [31 => [self::RES_REGOLITH => 40]]]);

        $sums = $this->drained();
        $this->assertGreaterThan(0, $sums['recycle'] ?? 0);
        $this->assertSame($this->regolith() - $before, $sums['recycle']);
    }

    public function test_self_salvage_and_salvage_flight_are_booked_separately(): void
    {
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => 2, 'r' => 0],
            ['ring' => 2, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 1, 'event_type' => 'find_small']
        );
        $tile = ColonyTile::where('colony_id', self::COLONY_ID)->where('q', 2)->where('r', 0)->firstOrFail();
        $service = app(ColonyTileService::class);

        $service->completeFind($tile, 10);
        $service->completeFind($tile, 15, 'find_flight');

        $this->assertSame(['find_self' => 10, 'find_flight' => 15], $this->drained());
    }

    public function test_prospector_concern_is_booked_as_cantina_concern(): void
    {
        $result = $this->callPrivate(app(BarService::class), 'resolveProspectorConcern', [self::COLONY_ID, true, ['regolith_min' => 20, 'regolith_max' => 30], 1, 100]);

        $this->assertSame(['cantina_concern' => $result['amount']], $this->drained());
    }

    public function test_bar_offer_books_guest_purchase_and_sale(): void
    {
        config(['game.bypass.ap_checks' => true]);
        DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', self::RES_REGOLITH)->update(['amount' => 500]);
        DB::table('colony_resources')->updateOrInsert(['colony_id' => self::COLONY_ID, 'resource_id' => 4], ['amount' => 500]);
        $buy = DB::table('bar_offers')->insertGetId(['colony_id' => self::COLONY_ID, 'give_resource_id' => 4, 'give_amount' => 5,
            'get_resource_id' => self::RES_REGOLITH, 'get_amount' => 20, 'expires_tick' => 99999]);
        $sell = DB::table('bar_offers')->insertGetId(['colony_id' => self::COLONY_ID, 'give_resource_id' => self::RES_REGOLITH, 'give_amount' => 30,
            'get_resource_id' => 4, 'get_amount' => 5, 'expires_tick' => 99999]);
        $bar = app(BarService::class);

        $before = $this->regolith();
        $this->assertTrue($bar->acceptOffer(self::COLONY_ID, $buy, 3, 100)['ok']);
        $gained = $this->regolith() - $before;
        $this->assertTrue($bar->acceptOffer(self::COLONY_ID, $sell, 3, 100)['ok']);
        $paid = $gained + $before - $this->regolith();

        $this->assertSame(['cantina_guest' => $gained, 'cantina_sell' => -$paid], $this->drained());
    }
}
