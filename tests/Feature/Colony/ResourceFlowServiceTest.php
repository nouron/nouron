<?php

namespace Tests\Feature\Colony;

use App\Services\ResourceFlowService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Per-Sol balance shown in the resource-bar popups (Regolith, Organika, Werkstoffe):
 * the displayed net MUST equal what the next game:tick actually books ("shown number =
 * effective number"). Fixture: Colony 1, Harvester 27, Agrardom 41, resources 3/4/5.
 */
class ResourceFlowServiceTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const TICK = 12500;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(self::TICK));
    }

    private function amount(int $resourceId): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)->where('resource_id', $resourceId)->value('amount');
    }

    private function setAmount(int $resourceId, int $n): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => $resourceId],
            ['amount' => $n]
        );
    }

    private function placeProducers(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->whereIn('building_id', [27, 41])->delete();
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => 3, 'r' => 0],
            ['ring' => 3, 'tile_type' => 'regolith_y2_d2', 'is_explored' => true, 'resource_amount' => 450, 'resource_max' => 450]
        );
        DB::table('colony_buildings')->insert([
            ['colony_id' => self::COLONY_ID, 'building_id' => 27, 'instance_id' => 1, 'level' => 2, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 3, 'tile_y' => 0],
            ['colony_id' => self::COLONY_ID, 'building_id' => 41, 'instance_id' => 1, 'level' => 2, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 2, 'tile_y' => 0],
        ]);
    }

    public function test_projected_net_equals_what_the_next_tick_books(): void
    {
        $this->placeProducers();
        $this->setAmount(3, 500);
        $this->setAmount(5, 500);
        $this->setAmount(4, 77);

        $flow = app(ResourceFlowService::class)->perSol(self::COLONY_ID);

        $this->assertGreaterThan(0, $flow['regolith']['sources']['harvester']);
        $this->assertGreaterThan(0, $flow['organika']['sources']['agrardom']);

        $rgBefore = $this->amount(3);
        $orBefore = $this->amount(5);
        Artisan::call('game:tick', ['--tick' => self::TICK]);

        $this->assertSame($flow['regolith']['net'], $this->amount(3) - $rgBefore, 'Regolith net = booked delta');
        $this->assertSame($flow['organika']['net'], $this->amount(5) - $orBefore, 'Organika net = booked delta');
        $this->assertSame(77, $this->amount(4), 'Werkstoffe have no per-Sol flow');
        $this->assertSame(0, $flow['werkstoffe']['net']);
    }

    public function test_sources_sum_to_net_and_consumption_is_listed_separately(): void
    {
        $this->placeProducers();

        $flow = app(ResourceFlowService::class)->perSol(self::COLONY_ID);

        $this->assertSame(
            array_sum($flow['organika']['sources']) - $flow['organika']['consumption'],
            $flow['organika']['net']
        );
        $this->assertSame(array_sum($flow['regolith']['sources']), $flow['regolith']['net']);
    }

    public function test_harvester_in_transit_contributes_nothing(): void
    {
        $this->placeProducers();
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 27)
            ->update(['pending_until_tick' => self::TICK + 2]);

        $flow = app(ResourceFlowService::class)->perSol(self::COLONY_ID);

        $this->assertSame(0, $flow['regolith']['net']);
    }

    public function test_credits_flow_lists_subsidy_relay_and_upkeep_and_equals_the_booked_delta(): void
    {
        $userId = (int) DB::table('glx_colonies')->where('id', self::COLONY_ID)->value('user_id');
        DB::table('user_resources')->where('user_id', $userId)->update(['credits' => 1000]);

        $flow = app(ResourceFlowService::class)->perSol(self::COLONY_ID)['credits'];

        $this->assertSame((int) config('game.credits.nexus_subsidy'), $flow['sources']['subsidy']);
        $this->assertGreaterThan(0, $flow['upkeep'], 'fixture colony employs advisors');
        $this->assertSame(array_sum($flow['sources']) - $flow['upkeep'], $flow['net']);

        Artisan::call('game:tick', ['--tick' => self::TICK]);

        $this->assertSame(
            $flow['net'],
            (int) DB::table('user_resources')->where('user_id', $userId)->value('credits') - 1000,
            'Credits net = booked delta'
        );
    }
}
