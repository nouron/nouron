<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use App\Services\Techtree\PersonellService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Building repair — POST colony/building/repair.
 *
 * Rules:
 * - 1 construction AP restores 1 status point.
 * - Buildings under construction (level 0) cannot be repaired.
 * - Fully intact buildings (status_points == max_status_points) cannot be repaired.
 * - Repair never raises ap_spend or level (separate from levelup invest).
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart), CC building_id=25
 * (max_status_points=20, level 3 in testdata).
 */
class BuildingRepairTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    private const CC_ID = 25;

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

    private function repair(int $buildingId = self::CC_ID)
    {
        return $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->postJson(route('colony.building.repair'), [
                'building_id' => $buildingId,
            ]);
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function test_repair_restores_one_status_point(): void
    {
        $this->setCcState(['status_points' => 16]);

        $response = $this->repair();

        $response->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(17, (int) $this->ccRow()->status_points);
        $this->assertSame(17, (int) $response->json('building.status_points'));
    }

    public function test_repair_does_not_touch_level_or_ap_spend(): void
    {
        $this->setCcState(['status_points' => 16, 'ap_spend' => 3, 'level' => 3]);

        $this->repair()->assertJsonPath('ok', true);

        $row = $this->ccRow();
        $this->assertSame(3, (int) $row->level, 'Repair must not change level');
        $this->assertSame(3, (int) $row->ap_spend, 'Repair must not change ap_spend');
    }

    public function test_repair_returns_updated_construction_ap(): void
    {
        $this->setCcState(['status_points' => 16]);

        $response = $this->repair();

        $response->assertJsonPath('ok', true);
        $this->assertIsInt($response->json('apConstruction'));
    }

    public function test_repair_returns_resource_amounts_for_build_chip_affordability(): void
    {
        $this->setCcState(['status_points' => 16]);

        $response = $this->repair();

        $response->assertJsonPath('ok', true);
        $this->assertIsInt($response->json('regolith'));
        $this->assertIsInt($response->json('werkstoffe'));
        $this->assertIsInt($response->json('freeSupply'));
    }

    public function test_repair_caps_at_max_status_points(): void
    {
        $this->setCcState(['status_points' => self::CC_MAX_SP - 1]);

        $this->repair()->assertJsonPath('ok', true);

        $this->assertSame(self::CC_MAX_SP, (int) $this->ccRow()->status_points);
    }

    public function test_repair_dismisses_teaching_hint_after_first_click(): void
    {
        $this->setCcState(['status_points' => 16]);

        $this->repair()->assertJsonPath('ok', true);

        $dismissed = json_decode(
            DB::table('user_preferences')->where('user_id', self::BART_USER_ID)->value('dismissed_hints') ?? '[]',
            true
        );
        $this->assertContains('hint_repair', $dismissed, 'First repair must dismiss the teaching repair hint');
        $this->assertNotContains('hint_repair_urgent', $dismissed, 'Urgent repair hint must never be auto-dismissed');
    }

    // ── Gates ─────────────────────────────────────────────────────────────────

    public function test_repair_rejected_when_fully_intact(): void
    {
        $this->setCcState(['status_points' => self::CC_MAX_SP]);

        $response = $this->repair();

        $response->assertOk()->assertJsonPath('ok', false);
        $this->assertSame(__('colony.error_repair_full'), $response->json('error'));
        $this->assertSame(self::CC_MAX_SP, (int) $this->ccRow()->status_points);
    }

    public function test_repair_rejected_for_building_under_construction(): void
    {
        $this->setCcState(['level' => 0, 'status_points' => 0]);

        $response = $this->repair();

        $response->assertOk()->assertJsonPath('ok', false);
        $this->assertSame(__('colony.error_repair_under_construction'), $response->json('error'));
    }

    public function test_repair_rejected_for_unknown_building(): void
    {
        $response = $this->repair(9999);

        $response->assertOk()->assertJsonPath('ok', false);
        $this->assertSame(__('colony.error_building_not_found'), $response->json('error'));
    }

    public function test_repair_rejected_without_construction_ap(): void
    {
        if (config('game.bypass.ap_checks')) {
            $this->markTestSkipped('AP checks bypassed in this environment');
        }

        $this->setCcState(['status_points' => 16]);

        // Drain the construction AP pool by locking more than available.
        $personell = $this->app->make(PersonellService::class);
        $available = $personell->getConstructionPoints(self::COLONY_ID);
        if ($available > 0) {
            $personell->lockActionPoints('construction', self::COLONY_ID, $available);
        }

        $response = $this->repair();

        $response->assertOk()->assertJsonPath('ok', false);
        $this->assertSame('ap_limit', $response->json('error'));
        $this->assertSame(16, (int) $this->ccRow()->status_points);
    }

    public function test_repair_writes_protocol_event(): void
    {
        $this->setCcState(['status_points' => 16]);

        $this->repair()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('colony_log', [
            'user' => self::BART_USER_ID,
            'event' => 'colony.building_repaired',
        ]);
    }
}
