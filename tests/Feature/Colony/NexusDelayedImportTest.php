<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Delayed Uplink-Direktimport for Regolith/Organika (Owner-Entscheidung
 * F5/A28, 2026-09-08/11). Payment happens immediately at request time,
 * delivery is deferred by `game.economy.delayed_import_delivery_ticks[uplinkLevel]`
 * Sole and processed by GameTick.
 *
 * Config: delayed_import_price = [3 => 35, 5 => 65], delivery_ticks = [1=>5, 2=>4, 3=>3].
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart).
 */
class NexusDelayedImportTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    private const RES_REGOLITH = 3;

    private const RES_ORGANICS = 5;

    private const UPLINK_BUILDING_ID = 54;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        config(['game.bypass.resource_costs' => false]);
    }

    private function bart(): User
    {
        return User::where('user_id', self::BART_USER_ID)->firstOrFail();
    }

    private function setUplinkLevel(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::UPLINK_BUILDING_ID, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 2, 'tile_y' => 0]
        );
    }

    private function credits(): int
    {
        return (int) DB::table('user_resources')->where('user_id', self::BART_USER_ID)->value('credits');
    }

    private function setCredits(int $amount): void
    {
        DB::table('user_resources')->where('user_id', self::BART_USER_ID)->update(['credits' => $amount]);
    }

    private function colonyRes(int $resourceId): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)->where('resource_id', $resourceId)->value('amount');
    }

    public function test_requires_uplink(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::UPLINK_BUILDING_ID)->delete();

        $this->actingAs($this->bart())
            ->postJson(route('colony.nexus.import-delayed'), ['resource_id' => self::RES_REGOLITH, 'amount' => 5])
            ->assertStatus(422)->assertJsonPath('ok', false)->assertJsonPath('error', 'uplink_required');
    }

    public function test_rejects_werkstoffe_resource_id(): void
    {
        $this->setUplinkLevel(1);

        $this->actingAs($this->bart())
            ->postJson(route('colony.nexus.import-delayed'), ['resource_id' => 4, 'amount' => 5])
            ->assertStatus(422);
    }

    public function test_charges_credits_immediately_and_creates_pending_delivery(): void
    {
        $this->setUplinkLevel(1);
        $this->setCredits(10_000);
        $credits = $this->credits();
        $price = (int) config('game.economy.delayed_import_price.3', 35);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.nexus.import-delayed'), ['resource_id' => self::RES_REGOLITH, 'amount' => 10])
            ->assertOk()->assertJsonPath('ok', true);

        $this->assertSame($credits - 10 * $price, $this->credits());
        $this->assertSame(0, $this->colonyRes(self::RES_REGOLITH) - $this->colonyRes(self::RES_REGOLITH), 'sanity: no double-read side effect');

        $row = DB::table('nexus_imports')->where('colony_id', self::COLONY_ID)->first();
        $this->assertNotNull($row, 'A pending delivery row must be created');
        $this->assertSame(self::RES_REGOLITH, (int) $row->resource_id);
        $this->assertSame(10, (int) $row->amount);
        $response->assertJsonPath('deliver_at_tick', (int) $row->deliver_at_tick);
    }

    public function test_delivery_tick_uses_uplink_level_schedule(): void
    {
        $this->setUplinkLevel(3);
        $this->setCredits(10_000);
        $currentTick = app(TickService::class)->getTickCount();

        $this->actingAs($this->bart())
            ->postJson(route('colony.nexus.import-delayed'), ['resource_id' => self::RES_ORGANICS, 'amount' => 5])
            ->assertOk();

        $row = DB::table('nexus_imports')->where('colony_id', self::COLONY_ID)->first();
        $deliveryTicks = (int) config('game.economy.delayed_import_delivery_ticks.3', 3);
        $this->assertSame($currentTick + $deliveryTicks, (int) $row->deliver_at_tick);
    }

    public function test_rejects_when_insufficient_credits(): void
    {
        $this->setUplinkLevel(1);
        $this->setCredits(0);

        $this->actingAs($this->bart())
            ->postJson(route('colony.nexus.import-delayed'), ['resource_id' => self::RES_REGOLITH, 'amount' => 100])
            ->assertStatus(422)->assertJsonPath('ok', false)->assertJsonPath('error', 'credit_limit');

        $this->assertNull(DB::table('nexus_imports')->where('colony_id', self::COLONY_ID)->first());
    }

    // ── GameTick delivery processing ─────────────────────────────────────────

    /** Removes Harvester/bioFacility tile placement so a real game:tick doesn't also produce Regolith/Organika. */
    private function neutralizeOtherProduction(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->whereIn('building_id', [27, 41])
            ->update(['tile_x' => null, 'tile_y' => null, 'level' => 0]);
    }

    public function test_gametick_credits_resource_once_delivery_tick_reached(): void
    {
        $this->neutralizeOtherProduction();
        $before = $this->colonyRes(self::RES_REGOLITH);
        DB::table('nexus_imports')->insert([
            'colony_id' => self::COLONY_ID,
            'resource_id' => self::RES_REGOLITH,
            'amount' => 42,
            'deliver_at_tick' => 30001,
        ]);

        Artisan::call('game:tick', ['--tick' => 30001]);

        $this->assertSame($before + 42, $this->colonyRes(self::RES_REGOLITH));
        $this->assertNull(DB::table('nexus_imports')->where('colony_id', self::COLONY_ID)->first(), 'delivered row must be removed');
    }

    public function test_gametick_does_not_deliver_before_the_scheduled_tick(): void
    {
        $this->neutralizeOtherProduction();
        DB::table('nexus_imports')->insert([
            'colony_id' => self::COLONY_ID,
            'resource_id' => self::RES_ORGANICS,
            'amount' => 42,
            'deliver_at_tick' => 30099,
        ]);

        Artisan::call('game:tick', ['--tick' => 30002]);

        $this->assertNotNull(DB::table('nexus_imports')->where('colony_id', self::COLONY_ID)->first(),
            'delivery must not fire before deliver_at_tick');
    }
}
