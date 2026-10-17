<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use App\Services\Techtree\BuildingService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The per-building AP cap state (`ap_cap`: null | {cap, spent, remaining}) is exposed on the
 * building rows that feed the colony screen and the techtree detail panel, so the UI can
 * show "heute spent/cap" before the first click.
 */
class BuildingApCapStateTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const CC_ID = 25;

    private const AGRARDOM_ID = 41;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        app(TickService::class)->setTickCount(10);

        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::AGRARDOM_ID, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 2, 'tile_y' => 0]
        );
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::CC_ID)
            ->update(['level' => 3, 'ap_spend' => 0]);
        config(['game.ap.base' => 40]);
    }

    private function setCap(?int $base): void
    {
        config(['game.build.ap_cap_per_sol' => [
            'base' => $base,
            'engineer_bonus_per_rank' => [1 => 2, 2 => 3, 3 => 4],
        ]]);
    }

    private function bart(): User
    {
        return User::where('user_id', 3)->firstOrFail();
    }

    private function invest(int $buildingId = self::CC_ID): void
    {
        $this->actingAs($this->bart())->postJson(route('colony.building.invest'), ['building_id' => $buildingId])->assertOk();
    }

    /** @return array<string, mixed>|null the `ap_cap` of the colony-screen row */
    private function viewCap(int $buildingId = self::CC_ID): ?array
    {
        $rows = collect($this->actingAs($this->bart())->get(route('colony.view'))->assertOk()->viewData('buildings'));
        $row = $rows->firstWhere('building_id', $buildingId);
        $this->assertNotNull($row);
        $this->assertTrue(property_exists($row, 'ap_cap'), 'row carries ap_cap');

        return $row->ap_cap;
    }

    /** @return array<string, mixed>|null the `ap_cap` of the techtree instance entry */
    private function techtreeCap(int $buildingId = self::CC_ID): ?array
    {
        $instances = app(BuildingService::class)->placedInstances(self::COLONY_ID)[$buildingId];
        $this->assertArrayHasKey('ap_cap', $instances[0]);

        return $instances[0]['ap_cap'];
    }

    public function test_cap_off_is_null_everywhere(): void
    {
        $this->setCap(null);

        $this->assertNull($this->viewCap());
        $this->assertNull($this->techtreeCap());
    }

    public function test_fresh_building_has_nothing_spent(): void
    {
        $this->setCap(3); // + rank 1 engineer (+2) = 5

        $expected = ['cap' => 5, 'spent' => 0, 'remaining' => 5];
        $this->assertSame($expected, $this->viewCap());
        $this->assertSame($expected, $this->techtreeCap());
    }

    public function test_spent_follows_invests_per_instance(): void
    {
        $this->setCap(3);
        $this->invest();
        $this->invest();

        $this->assertSame(['cap' => 5, 'spent' => 2, 'remaining' => 3], $this->viewCap());
        $this->assertSame(['cap' => 5, 'spent' => 2, 'remaining' => 3], $this->techtreeCap());
        $this->assertSame(['cap' => 5, 'spent' => 0, 'remaining' => 5], $this->viewCap(self::AGRARDOM_ID));
    }

    public function test_building_at_cap_shows_zero_remaining(): void
    {
        $this->setCap(1); // cap 3
        for ($i = 0; $i < 3; $i++) {
            $this->invest();
        }

        $this->assertSame(['cap' => 3, 'spent' => 3, 'remaining' => 0], $this->viewCap());
    }

    public function test_stale_tick_counter_is_not_exposed(): void
    {
        $this->setCap(3);
        $this->invest();
        $this->invest();
        app(TickService::class)->setTickCount(11);

        $this->assertSame(['cap' => 5, 'spent' => 0, 'remaining' => 5], $this->viewCap());
        $this->assertSame(['cap' => 5, 'spent' => 0, 'remaining' => 5], $this->techtreeCap());
    }

    public function test_unavailable_engineer_gives_base_only(): void
    {
        $this->setCap(3);
        DB::table('advisors')->where('colony_id', self::COLONY_ID)->where('personell_id', 35)
            ->update(['unavailable_until_tick' => 15]);

        $this->assertSame(['cap' => 3, 'spent' => 0, 'remaining' => 3], $this->viewCap());
        $this->assertSame(['cap' => 3, 'spent' => 0, 'remaining' => 3], $this->techtreeCap());
    }

    public function test_building_row_in_invest_and_repair_responses_carries_ap_cap(): void
    {
        $this->setCap(3);
        $this->actingAs($this->bart());

        $this->postJson(route('colony.building.invest'), ['building_id' => self::CC_ID])
            ->assertOk()
            ->assertJsonPath('building.ap_cap.spent', 1)
            ->assertJsonPath('building.ap_cap.remaining', 4);

        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::AGRARDOM_ID)
            ->update(['status_points' => 5]);
        $this->postJson(route('colony.building.repair'), ['building_id' => self::AGRARDOM_ID])
            ->assertOk()
            ->assertJsonPath('building.ap_cap.cap', 5)
            ->assertJsonPath('building.ap_cap.spent', 0);
    }

    public function test_building_row_ap_cap_is_null_when_off(): void
    {
        $this->setCap(null);

        $this->actingAs($this->bart())
            ->postJson(route('colony.building.invest'), ['building_id' => self::CC_ID])
            ->assertOk()
            ->assertJsonPath('building.ap_cap', null);
    }

    public function test_list_path_query_count_does_not_grow_with_building_rows(): void
    {
        $this->setCap(3);
        $count = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($this->bart())->get(route('colony.view'))->assertOk();
            $n = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $n;
        };

        $before = $count();
        foreach ([2, 3, 4] as $instance) {
            DB::table('colony_buildings')->insert([
                'colony_id' => self::COLONY_ID, 'building_id' => self::AGRARDOM_ID, 'instance_id' => $instance,
                'level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => $instance, 'tile_y' => 1,
            ]);
        }

        // The per-row queries that already existed (labels, unlocks) may grow; the cap adds none.
        $withCapOff = (function () use ($count): int {
            $this->setCap(null);

            return $count();
        })();
        $this->setCap(3);
        $withCapOn = $count();

        // +3, constant per request (measured with 3 and with 6 added rows): the invest-site hint now runs at every CC level and resolves the cap once.
        $this->assertLessThanOrEqual($withCapOff + 3, $withCapOn, 'cap resolved once per request, not per row');
        $this->assertGreaterThan(0, $before);
    }
}
