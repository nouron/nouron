<?php

namespace Tests\Feature\Bar;

/**
 * BarService::talkToBartender() feature tests (Cantina-Barkeeper Tomas, A40; since
 * 2026-11-11 the bonus follows his relationship level, spec
 * 2026-11-11-tomas-rat-rueckmeldung sentences 13–17).
 */

use App\Services\AdvisorService;
use App\Services\BarService;
use App\Services\PathDepthService;
use App\Services\Techtree\ResearchService;
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
        // AP scale 2026-11-13: fixed bonus per advice, frequency by relationship level
        // (BarBartenderFrequencyTest covers the rhythm).
        config([
            'game.bartender.ap_bonus' => 1,
            'game.bartender.advice_every_sols_by_relationship_level' => [1 => 3, 2 => 3, 3 => 2, 4 => 2, 5 => 1],
        ]);
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

    private function setRelationship(int $level): void
    {
        DB::table('colony_path_levels')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'kind' => PathDepthService::KIND_CHARACTER, 'entity_key' => 'bartender'],
            ['level' => $level, 'ap_spend' => 0],
        );
    }

    // ── 13: relationship level 0 ─────────────────────────────────────────────

    public function test_talk_at_relationship_zero_is_refused_without_using_the_sol(): void
    {
        $this->setInteractionCount(40, lastTick: 5);

        $result = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $this->assertFalse($result['success']);
        $this->assertSame('relationship_too_low', $result['error']);
        $this->assertNotEmpty($result['message']);
        $this->assertSame(40, $this->interactionCount());
        $this->assertSame(5, (int) DB::table('colony_bartender_state')->where('colony_id', self::COLONY_ID)->value('last_interaction_tick'));
        $this->assertSame(0, $this->apSpend());
    }

    // ── 14: bonus by relationship level ──────────────────────────────────────

    public function test_bonus_follows_relationship_level_not_conversation_count(): void
    {
        $this->setRelationship(1);

        $result = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['ap_added']);
        $this->assertSame(1, $this->apSpend());
        $this->assertSame(1, $this->interactionCount());
    }

    public function test_higher_relationship_level_gives_the_same_configured_bonus(): void
    {
        $this->setRelationship(5);
        $this->setInteractionCount(0, lastTick: 5);

        $result = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $this->assertSame(1, $result['ap_added']);
        $this->assertSame(1, $this->apSpend());
    }

    public function test_bartender_ap_bonus_reads_the_relationship_table(): void
    {
        $this->assertSame(0, $this->barService->bartenderApBonus(self::COLONY_ID));
        $this->setRelationship(3);
        $this->assertSame(1, $this->barService->bartenderApBonus(self::COLONY_ID));
    }

    // ── 15: not selectable ───────────────────────────────────────────────────

    public function test_talk_with_unselectable_knowledge_is_refused_without_using_the_sol(): void
    {
        $this->setRelationship(1);
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => 90],
            ['level' => ResearchService::maxKnowledgeLevel(90), 'ap_spend' => 0],
        );
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 25)->update(['level' => 5]);

        $result = $this->barService->talkToBartender(self::COLONY_ID, 90, 10);

        $this->assertFalse($result['success']);
        $this->assertSame('knowledge_not_selectable', $result['error']);
        $this->assertSame('max_level', $result['block_reason']);
        $this->assertSame(0, $this->interactionCount());
        $this->assertNull($this->barService->bartenderToday(self::COLONY_ID, 10));
    }

    // ── cooldown / pool (unchanged rules) ────────────────────────────────────

    public function test_talk_enforces_once_per_tick_cooldown(): void
    {
        $this->setRelationship(1);

        $first = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);
        $second = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $this->assertTrue($first['success']);
        $this->assertFalse($second['success']);
        $this->assertSame('cooldown_active', $second['error']);
        $this->assertSame(1, $this->interactionCount());
    }

    public function test_talk_allows_interaction_again_on_next_tick(): void
    {
        $this->setRelationship(5);
        $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $result = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 11);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $this->interactionCount());
    }

    public function test_talk_does_not_lock_or_consume_shared_ap_pool(): void
    {
        $this->setRelationship(3);

        $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $locked = DB::table('locked_actionpoints')
            ->where('scope_type', 'colony')
            ->where('scope_id', self::COLONY_ID)
            ->where('tick', 10)
            ->count();

        $this->assertSame(0, $locked);
    }

    /**
     * Earmarked channel: never blocked by the shared pool's availability (regression,
     * see ResearchService::investBonus()).
     */
    public function test_talk_injects_bonus_ap_even_when_shared_pool_is_empty(): void
    {
        config(['game.bypass.ap_checks' => false]);
        $this->mockTick(10);
        $this->barService = $this->app->make(BarService::class);
        $this->setRelationship(1);

        $advisorService = $this->app->make(AdvisorService::class);
        $totalAp = $advisorService->getTotalActionPoints(self::COLONY_ID);
        $advisorService->lockActionPoints(self::COLONY_ID, $totalAp);
        $this->assertSame(0, $advisorService->getAvailableActionPoints(self::COLONY_ID));

        $result = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['ap_added']);
        $this->assertSame(1, $this->apSpend());
    }

    // ── 9 / 16: result and today's result ────────────────────────────────────

    public function test_talk_returns_the_knowledge_result(): void
    {
        $this->setRelationship(1);

        $result = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $this->assertSame(self::KNOWLEDGE_ID, $result['knowledge_result']['knowledge_id']);
        $this->assertSame(1, $result['knowledge_result']['ap_invested']);
        $this->assertFalse($result['knowledge_result']['leveled_up']);
        $this->assertSame(0, $result['knowledge_result']['ap_forfeited']);
    }

    public function test_todays_result_is_available_until_the_next_sol(): void
    {
        $this->setRelationship(1);
        $this->assertNull($this->barService->bartenderToday(self::COLONY_ID, 10));

        $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $today = $this->barService->bartenderToday(self::COLONY_ID, 10);
        $this->assertNotNull($today);
        $this->assertSame(self::KNOWLEDGE_ID, $today['knowledge_id']);
        $this->assertSame(1, $today['ap_invested']);
        $this->assertNull($this->barService->bartenderToday(self::COLONY_ID, 11));
    }

    // ── 17: journal entry ────────────────────────────────────────────────────

    public function test_talk_writes_a_journal_entry(): void
    {
        $this->setRelationship(1);

        $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $entry = DB::table('colony_log')->where('user', 3)->where('tick', 10)->where('event', 'knowledge.bonus_progress')->first();
        $this->assertNotNull($entry);
        $params = json_decode($entry->parameters, true);
        $this->assertSame('bartender', $params['source']);
        $this->assertSame(self::COLONY_ID, $params['colony_id']);
        $this->assertSame(self::KNOWLEDGE_ID, $params['knowledge_id']);
    }

    public function test_breakthrough_gets_its_own_journal_event(): void
    {
        $this->setRelationship(1);
        $threshold = $this->app->make(ResearchService::class)->knowledgeLevelupCost(self::COLONY_ID, self::KNOWLEDGE_ID);
        DB::table('colony_researches')
            ->where('colony_id', self::COLONY_ID)->where('research_id', self::KNOWLEDGE_ID)
            ->update(['ap_spend' => $threshold - 1]);

        $result = $this->barService->talkToBartender(self::COLONY_ID, self::KNOWLEDGE_ID, 10);

        $this->assertTrue($result['knowledge_result']['leveled_up']);
        $this->assertTrue(DB::table('colony_log')->where('tick', 10)->where('event', 'knowledge.bonus_breakthrough')->exists());
        $this->assertFalse(DB::table('colony_log')->where('tick', 10)->where('event', 'knowledge.bonus_progress')->exists());
    }
}
