<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Building level-up — POST colony/building/invest.
 *
 * Rules:
 * - Each invest adds 1 construction AP towards ap_for_levelup.
 * - On reaching the threshold: level +1, ap_spend resets to 0, and
 *   status_points is restored to max_status_points.
 * - Investing past max_level is rejected.
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart), CC building_id=25
 * (ap_for_levelup=10, max_level=5, max_status_points=20).
 */
class BuildingInvestTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    private const CC_ID = 25;

    private const CC_AP_FOR_LEVELUP = 10;

    private const CC_MAX_SP = 20;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function makeUser(int $userId): User
    {
        return User::where('user_id', $userId)->firstOrFail();
    }

    private function setCcState(array $attrs): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::CC_ID)
            ->update($attrs);
    }

    private function ccRow(): object
    {
        return DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::CC_ID)
            ->first();
    }

    private function invest(int $buildingId = self::CC_ID)
    {
        return $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->postJson(route('colony.building.invest'), [
                'building_id' => $buildingId,
            ]);
    }

    public function test_invest_increments_ap_progress_without_levelup(): void
    {
        $this->setCcState(['level' => 1, 'ap_spend' => 0]);

        $response = $this->invest();

        $response->assertOk()->assertJsonPath('ok', true);
        $response->assertJsonPath('leveled_up', false);
        $this->assertSame(1, (int) $this->ccRow()->ap_spend);
        $this->assertSame(1, (int) $this->ccRow()->level);
    }

    public function test_invest_levels_up_at_threshold(): void
    {
        // One invest away from the threshold so a single AP completes the level.
        $this->setCcState([
            'level' => 1,
            'ap_spend' => self::CC_AP_FOR_LEVELUP - 1,
            'status_points' => 16,
        ]);

        $response = $this->invest();

        $response->assertOk()->assertJsonPath('ok', true);
        $response->assertJsonPath('leveled_up', true);

        $row = $this->ccRow();
        $this->assertSame(2, (int) $row->level, 'Level must increment');
        $this->assertSame(0, (int) $row->ap_spend, 'ap_spend resets after level-up');
        $this->assertSame(self::CC_MAX_SP, (int) $row->status_points, 'Status restored to max on level-up');
    }

    public function test_invest_rejected_at_max_level(): void
    {
        $this->setCcState(['level' => 5, 'ap_spend' => 0]);

        $response = $this->invest();

        $response->assertOk()->assertJsonPath('ok', false);
        $this->assertSame(__('colony.error_max_level_reached'), $response->json('error'));
        $this->assertSame(5, (int) $this->ccRow()->level);
    }

    public function test_invest_writes_protocol_event(): void
    {
        $this->setCcState(['level' => 1, 'ap_spend' => 0]);

        $this->invest()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('colony_log', [
            'user' => self::BART_USER_ID,
            'event' => 'colony.building_invested',
        ]);
    }
}
