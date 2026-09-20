<?php

namespace Tests\Feature\Bar;

/**
 * BarService::talkToBartender() feature tests (Cantina-Barkeeper Tomas, A40).
 *
 * Covered scenarios:
 *  TALK TO BARTENDER
 *    - test_talk_injects_no_ap_bonus_below_first_tier
 *    - test_talk_injects_ap_bonus_once_tier_reached
 *    - test_talk_increments_interaction_count_even_without_bonus
 *    - test_talk_uses_correct_tier_based_on_count_before_interaction
 *    - test_talk_enforces_once_per_tick_cooldown
 *    - test_talk_allows_interaction_again_on_next_tick
 *    - test_talk_does_not_lock_or_consume_shared_ap_pool
 *    - test_talk_caps_bonus_at_highest_tier
 *    - test_talk_injects_bonus_ap_even_when_shared_pool_is_empty
 */

use App\Services\AdvisorService;
use App\Services\BarService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarBartenderTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;   // Springfield — user_id = 3 (Bart)

    private const KNOWLEDGE_ID = 96;   // defense — colony_researches level=0, ap_spend=0

    private BarService $barService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->barService = $this->app->make(BarService::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function mockTick(int $tick): void
    {
        $this->app->instance(TickService::class, new TickService($tick));
    }

    private function apSpend(): int
    {
        return (int) DB::table('colony_researches')
            ->where('colony_id', self::COLONY_ID)
            ->where('research_id', self::KNOWLEDGE_ID)
            ->value('ap_spend');
    }

    private function interactionCount(): int
    {
        return (int) DB::table('colony_bartender_state')
            ->where('colony_id', self::COLONY_ID)
            ->value('interaction_count');
    }

    private function setInteractionCount(int $count, ?int $lastTick = null): void
    {
        DB::table('colony_bartender_state')->updateOrInsert(
            ['colony_id' => self::COLONY_ID],
            ['interaction_count' => $count, 'last_interaction_tick' => $lastTick],
        );
    }

    // ── talkToBartender ─────────────────────────────────────────────────────

    public function test_talk_injects_no_ap_bonus_below_first_tier(): void
    {
        $this->mockTick(10);

        $result = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['ap_added']);
        $this->assertSame(0, $this->apSpend());
        $this->assertSame(1, $this->interactionCount());
    }

    public function test_talk_injects_ap_bonus_once_tier_reached(): void
    {
        $this->mockTick(10);
        $this->setInteractionCount(5, lastTick: 5);

        $result = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['ap_added']);
        $this->assertSame(1, $this->apSpend());
        $this->assertSame(6, $this->interactionCount());
    }

    public function test_talk_increments_interaction_count_even_without_bonus(): void
    {
        $this->mockTick(10);
        $this->setInteractionCount(2, lastTick: 5);

        $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $this->assertSame(3, $this->interactionCount());
    }

    public function test_talk_uses_correct_tier_based_on_count_before_interaction(): void
    {
        $this->mockTick(10);
        // count = 4 before the interaction -> still tier 0 (threshold is 5), even
        // though the count becomes 5 afterwards.
        $this->setInteractionCount(4, lastTick: 5);

        $result = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $this->assertSame(0, $result['ap_added']);
        $this->assertSame(5, $this->interactionCount());
    }

    public function test_talk_enforces_once_per_tick_cooldown(): void
    {
        $this->mockTick(10);

        $first = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);
        $second = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $this->assertTrue($first['success']);
        $this->assertFalse($second['success']);
        $this->assertSame('cooldown_active', $second['error']);
        $this->assertSame(1, $this->interactionCount());
    }

    public function test_talk_allows_interaction_again_on_next_tick(): void
    {
        $this->mockTick(10);
        $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $this->mockTick(11);
        $result = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 11);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $this->interactionCount());
    }

    public function test_talk_does_not_lock_or_consume_shared_ap_pool(): void
    {
        $this->mockTick(10);
        $this->setInteractionCount(5, lastTick: 5);

        $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $locked = DB::table('locked_actionpoints')
            ->where('scope_type', 'colony')
            ->where('scope_id', self::COLONY_ID)
            ->where('tick', 10)
            ->count();

        $this->assertSame(0, $locked);
    }

    public function test_talk_caps_bonus_at_highest_tier(): void
    {
        $this->mockTick(10);
        $this->setInteractionCount(30, lastTick: 5);

        $result = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $this->assertSame(3, $result['ap_added']);

        $this->setInteractionCount(500, lastTick: 5);
        $result = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $this->assertSame(3, $result['ap_added']);
    }

    /**
     * Tomas' bonus AP is a separate, earmarked channel — it does not come out of
     * the shared colony pool and must therefore never be blocked by the pool's
     * current availability. Regression test for the bug where talkToBartender()
     * silently dropped the bonus because ResearchService::invest() ran its normal
     * investBlocker() pool-availability gate on it.
     */
    public function test_talk_injects_bonus_ap_even_when_shared_pool_is_empty(): void
    {
        config(['game.bypass.ap_checks' => false]);
        $this->mockTick(10);
        // Re-resolve barService now that TickService(10) is bound — the instance
        // built in setUp() would otherwise keep referencing the TickService
        // resolved before this test's mockTick() call (readonly constructor
        // injection, not re-resolved on rebind).
        $this->barService = $this->app->make(BarService::class);
        $this->setInteractionCount(5, lastTick: 5);

        // Drain the shared AP pool to exactly 0.
        $advisorService = $this->app->make(AdvisorService::class);
        $totalAp = $advisorService->getTotalActionPoints(self::COLONY_ID);
        $advisorService->lockActionPoints(self::COLONY_ID, $totalAp);
        $this->assertSame(0, $advisorService->getAvailableActionPoints(self::COLONY_ID));

        $result = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['ap_added']);
        $this->assertSame(1, $this->apSpend());
    }
}
