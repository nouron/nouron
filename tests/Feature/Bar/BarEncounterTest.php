<?php

namespace Tests\Feature\Bar;

/**
 * BarService Cantina-Begegnungspool tests (GDD §12 Kanal 1, A35).
 *
 * Covered scenarios:
 *  GENERATE
 *    - test_generate_does_nothing_when_bar_not_built
 *    - test_generate_skips_when_a_pending_encounter_already_exists
 *    - test_generate_creates_at_most_one_encounter_of_a_known_type
 *
 *  ACCEPT WAGER
 *    - test_accept_wager_win_deducts_stake_and_pays_credits
 *    - test_accept_wager_loss_deducts_stake_only
 *
 *  ACCEPT AUCTION
 *    - test_accept_auction_deducts_resource_and_pays_credits_immediately
 *
 *  ACCEPT CONTRACT
 *    - test_accept_contract_marks_accepted_without_resolving
 *
 *  SHARED VALIDATION
 *    - test_accept_returns_error_for_expired_encounter
 *    - test_accept_returns_error_when_already_accepted
 *    - test_accept_returns_error_when_insufficient_stake
 */

use App\Services\BarService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarEncounterTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1; // Springfield — user_id = 3 (Bart)

    private const USER_ID = 3; // Bart

    private const BAR_BUILDING_ID = 52;

    private const RES_REGOLITH = 3;

    private const RES_ORGANICS = 5;

    private BarService $barService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->barService = $this->app->make(BarService::class);
    }

    private function setBarLevel(int $level): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->update(['level' => $level]);
    }

    private function insertEncounter(array $overrides = []): int
    {
        $defaults = [
            'colony_id' => self::COLONY_ID,
            'type' => 'wager',
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 20,
            'win_chance' => 0.45,
            'credits_amount' => 50,
            'duration_ticks' => null,
            'expires_tick' => 50,
            'is_accepted' => false,
            'resolved' => false,
            'won' => null,
            'ends_tick' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return DB::table('bar_encounters')->insertGetId(array_merge($defaults, $overrides));
    }

    private function setColonyResource(int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }

    private function getColonyResource(int $resourceId): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)
            ->where('resource_id', $resourceId)
            ->value('amount');
    }

    private function setCredits(int $amount): void
    {
        DB::table('user_resources')
            ->where('user_id', self::USER_ID)
            ->update(['credits' => $amount]);
    }

    private function getCredits(): int
    {
        return (int) DB::table('user_resources')
            ->where('user_id', self::USER_ID)
            ->value('credits');
    }

    // ── GENERATE ─────────────────────────────────────────────────────────────

    public function test_generate_does_nothing_when_bar_not_built(): void
    {
        $this->setBarLevel(0);

        $this->barService->generateEncounterForColony(self::COLONY_ID, 10);

        $this->assertSame(0, DB::table('bar_encounters')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_generate_skips_when_a_pending_encounter_already_exists(): void
    {
        $this->setBarLevel(3);
        $this->insertEncounter(['expires_tick' => 99]);

        // Force a spawn-eligible roll by pinning the chance to 100%.
        config(['game.bar.encounter.spawn_chance_per_level' => [3 => 1.0]]);

        $this->barService->generateEncounterForColony(self::COLONY_ID, 10);

        $this->assertSame(1, DB::table('bar_encounters')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_generate_creates_at_most_one_encounter_of_a_known_type(): void
    {
        $this->setBarLevel(3);
        config(['game.bar.encounter.spawn_chance_per_level' => [3 => 1.0]]);

        $this->barService->generateEncounterForColony(self::COLONY_ID, 10);

        $encounters = DB::table('bar_encounters')->where('colony_id', self::COLONY_ID)->get();
        $this->assertCount(1, $encounters);
        $this->assertContains($encounters->first()->type, ['wager', 'auction', 'contract']);
        $this->assertFalse((bool) $encounters->first()->is_accepted);
    }

    // ── ACCEPT WAGER ─────────────────────────────────────────────────────────

    public function test_accept_wager_win_deducts_stake_and_pays_credits(): void
    {
        $this->setColonyResource(self::RES_REGOLITH, 100);
        $this->setCredits(0);
        $id = $this->insertEncounter([
            'type' => 'wager',
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 20,
            'win_chance' => 1.0, // force a win
            'credits_amount' => 50,
        ]);

        $result = $this->barService->acceptEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['won']);
        $this->assertSame(80, $this->getColonyResource(self::RES_REGOLITH));
        $this->assertSame(50, $this->getCredits());
    }

    public function test_accept_wager_loss_deducts_stake_only(): void
    {
        $this->setColonyResource(self::RES_REGOLITH, 100);
        $this->setCredits(0);
        $id = $this->insertEncounter([
            'type' => 'wager',
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 20,
            'win_chance' => 0.0, // force a loss
            'credits_amount' => 50,
        ]);

        $result = $this->barService->acceptEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['won']);
        $this->assertSame(80, $this->getColonyResource(self::RES_REGOLITH));
        $this->assertSame(0, $this->getCredits());
    }

    // ── ACCEPT AUCTION ───────────────────────────────────────────────────────

    public function test_accept_auction_deducts_resource_and_pays_credits_immediately(): void
    {
        $this->setColonyResource(self::RES_ORGANICS, 100);
        $this->setCredits(0);
        $id = $this->insertEncounter([
            'type' => 'auction',
            'give_resource_id' => self::RES_ORGANICS,
            'give_amount' => 30,
            'win_chance' => null,
            'credits_amount' => 40,
        ]);

        $result = $this->barService->acceptEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertSame(70, $this->getColonyResource(self::RES_ORGANICS));
        $this->assertSame(40, $this->getCredits());
        $this->assertTrue(
            DB::table('bar_encounters')->where('id', $id)->value('resolved') ? true : false
        );
    }

    // ── ACCEPT CONTRACT ──────────────────────────────────────────────────────

    public function test_accept_contract_marks_accepted_without_resolving(): void
    {
        $id = $this->insertEncounter([
            'type' => 'contract',
            'give_resource_id' => null,
            'give_amount' => null,
            'win_chance' => null,
            'credits_amount' => 10,
            'duration_ticks' => 3,
        ]);

        $result = $this->barService->acceptEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $row = DB::table('bar_encounters')->where('id', $id)->first();
        $this->assertTrue((bool) $row->is_accepted);
        $this->assertFalse((bool) $row->resolved);
        $this->assertSame(13, $row->ends_tick);
    }

    // ── SHARED VALIDATION ────────────────────────────────────────────────────

    public function test_accept_returns_error_for_expired_encounter(): void
    {
        $id = $this->insertEncounter(['expires_tick' => 5]);

        $result = $this->barService->acceptEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
    }

    public function test_accept_returns_error_when_already_accepted(): void
    {
        $id = $this->insertEncounter(['is_accepted' => true]);

        $result = $this->barService->acceptEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
    }

    public function test_accept_returns_error_when_insufficient_stake(): void
    {
        $this->setColonyResource(self::RES_REGOLITH, 5);
        $id = $this->insertEncounter([
            'type' => 'wager',
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 20,
        ]);

        $result = $this->barService->acceptEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
    }
}
