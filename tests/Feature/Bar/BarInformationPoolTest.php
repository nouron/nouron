<?php

namespace Tests\Feature\Bar;

/**
 * BarService Deva & Lenn Vier-Ausgänge-Pool tests (GDD §12 "Deva & Lenn —
 * taktische Information", A42).
 *
 * Covered scenarios:
 *  GENERATE
 *    - test_generate_does_nothing_when_bar_not_built
 *    - test_generate_skips_when_a_pending_encounter_already_exists
 *    - test_generate_creates_an_encounter_when_roll_hits
 *    - test_generate_picks_veteran_when_split_roll_is_low
 *    - test_generate_picks_ai_researcher_when_split_roll_is_high
 *    - test_generate_excludes_exhausted_mechanical_outcomes_from_the_pool
 *    - test_generate_still_offers_narrative_outcomes_once_both_mechanical_are_used
 *
 *  RESOLVE — shared validation
 *    - test_resolve_returns_error_when_not_found
 *    - test_resolve_returns_error_when_already_resolved
 *    - test_resolve_returns_error_when_expired
 *
 *  RESOLVE — veteran (Deva)
 *    - test_resolve_veteran_drill_buffer_activates_buffer_and_marks_used_once
 *    - test_resolve_veteran_knowledge_boost_injects_ap_into_chosen_knowledge
 *    - test_resolve_veteran_knowledge_boost_without_choice_grants_no_bonus
 *    - test_resolve_veteran_narrative_outcome_has_no_mechanical_effect
 *
 *  RESOLVE — ai_researcher (Lenn)
 *    - test_resolve_ai_researcher_nav_discount_activates_voucher_and_marks_used_once
 *    - test_resolve_ai_researcher_knowledge_boost_injects_ap_into_cartography
 *
 *  RESOLVE — Charakter-Kodex hookup
 *    - test_resolve_records_codex_progress_for_the_character
 */

use App\Services\BarService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarInformationPoolTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1; // Springfield — user_id = 3 (Bart)

    private const USER_ID = 3;

    private const BAR_BUILDING_ID = 52;

    private const KNOWLEDGE_GEOLOGY_ID = 92;

    private const KNOWLEDGE_HEALTH_ID = 94;

    private const KNOWLEDGE_CARTOGRAPHY_ID = 91;

    private BarService $barService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->barService = $this->app->make(BarService::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

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
            'character_slug' => 'veteran',
            'outcome_key' => 'narrative_1',
            'created_tick' => 10,
            'expires_tick' => 20,
            'is_resolved' => false,
            'outcome' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return DB::table('bar_information_encounters')->insertGetId(array_merge($defaults, $overrides));
    }

    private function poolState(): ?object
    {
        return DB::table('colony_information_pool_state')->where('colony_id', self::COLONY_ID)->first();
    }

    private function knowledgeApSpend(int $knowledgeId): int
    {
        return (int) DB::table('colony_researches')
            ->where('colony_id', self::COLONY_ID)
            ->where('research_id', $knowledgeId)
            ->value('ap_spend');
    }

    // ── GENERATE ─────────────────────────────────────────────────────────────

    public function test_generate_does_nothing_when_bar_not_built(): void
    {
        $this->setBarLevel(0);
        config(['game.bar.information_pool.spawn_chance_per_tick' => 1.0]);

        $this->barService->generateInformationEncounterForColony(self::COLONY_ID, 10);

        $this->assertSame(0, DB::table('bar_information_encounters')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_generate_skips_when_a_pending_encounter_already_exists(): void
    {
        $this->setBarLevel(1);
        $this->insertEncounter(['expires_tick' => 99]);
        config(['game.bar.information_pool.spawn_chance_per_tick' => 1.0]);

        $this->barService->generateInformationEncounterForColony(self::COLONY_ID, 10);

        $this->assertSame(1, DB::table('bar_information_encounters')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_generate_creates_an_encounter_when_roll_hits(): void
    {
        $this->setBarLevel(1);
        config(['game.bar.information_pool.spawn_chance_per_tick' => 1.0]);

        $this->barService->generateInformationEncounterForColony(self::COLONY_ID, 10);

        $encounter = DB::table('bar_information_encounters')->where('colony_id', self::COLONY_ID)->first();
        $this->assertNotNull($encounter);
        $this->assertContains($encounter->character_slug, ['veteran', 'ai_researcher']);
        $this->assertContains($encounter->outcome_key, ['drill_buffer', 'knowledge_boost', 'nav_discount', 'narrative_1', 'narrative_2']);
        $this->assertFalse((bool) $encounter->is_resolved);
    }

    public function test_generate_picks_veteran_when_split_roll_is_low(): void
    {
        $this->setBarLevel(1);
        config([
            'game.bar.information_pool.spawn_chance_per_tick' => 1.0,
            'game.bar.information_pool.character_split' => 1.0, // always "low" side
        ]);

        $this->barService->generateInformationEncounterForColony(self::COLONY_ID, 10);

        $encounter = DB::table('bar_information_encounters')->where('colony_id', self::COLONY_ID)->first();
        $this->assertSame('veteran', $encounter->character_slug);
    }

    public function test_generate_picks_ai_researcher_when_split_roll_is_high(): void
    {
        $this->setBarLevel(1);
        config([
            'game.bar.information_pool.spawn_chance_per_tick' => 1.0,
            'game.bar.information_pool.character_split' => 0.0, // always "high" side
        ]);

        $this->barService->generateInformationEncounterForColony(self::COLONY_ID, 10);

        $encounter = DB::table('bar_information_encounters')->where('colony_id', self::COLONY_ID)->first();
        $this->assertSame('ai_researcher', $encounter->character_slug);
    }

    public function test_generate_excludes_exhausted_mechanical_outcomes_from_the_pool(): void
    {
        $this->setBarLevel(1);
        config([
            'game.bar.information_pool.spawn_chance_per_tick' => 1.0,
            'game.bar.information_pool.character_split' => 1.0, // force veteran
        ]);
        DB::table('colony_information_pool_state')->insert([
            'colony_id' => self::COLONY_ID,
            'deva_buff_used' => true,
            'deva_knowledge_used' => false,
            'lenn_nav_used' => false,
            'lenn_knowledge_used' => false,
            'active_drill_buffer' => false,
            'active_nav_voucher' => false,
        ]);

        // Roll many times (different ticks -> different pseudo-random draws)
        // and confirm the exhausted 'drill_buffer' outcome never appears.
        for ($tick = 10; $tick < 40; $tick++) {
            DB::table('bar_information_encounters')->where('colony_id', self::COLONY_ID)->delete();
            $this->barService->generateInformationEncounterForColony(self::COLONY_ID, $tick);
            $encounter = DB::table('bar_information_encounters')->where('colony_id', self::COLONY_ID)->first();
            if ($encounter) {
                $this->assertNotSame('drill_buffer', $encounter->outcome_key);
            }
        }
    }

    public function test_generate_still_offers_narrative_outcomes_once_both_mechanical_are_used(): void
    {
        $this->setBarLevel(1);
        config([
            'game.bar.information_pool.spawn_chance_per_tick' => 1.0,
            'game.bar.information_pool.character_split' => 1.0, // force veteran
        ]);
        DB::table('colony_information_pool_state')->insert([
            'colony_id' => self::COLONY_ID,
            'deva_buff_used' => true,
            'deva_knowledge_used' => true,
            'lenn_nav_used' => false,
            'lenn_knowledge_used' => false,
            'active_drill_buffer' => false,
            'active_nav_voucher' => false,
        ]);

        $this->barService->generateInformationEncounterForColony(self::COLONY_ID, 10);

        $encounter = DB::table('bar_information_encounters')->where('colony_id', self::COLONY_ID)->first();
        $this->assertNotNull($encounter);
        $this->assertContains($encounter->outcome_key, ['narrative_1', 'narrative_2']);
    }

    // ── RESOLVE — shared validation ─────────────────────────────────────────

    public function test_resolve_returns_error_when_not_found(): void
    {
        $result = $this->barService->resolveInformationEncounter(self::COLONY_ID, 999999, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
    }

    public function test_resolve_returns_error_when_already_resolved(): void
    {
        $id = $this->insertEncounter(['is_resolved' => true]);

        $result = $this->barService->resolveInformationEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
    }

    public function test_resolve_returns_error_when_expired(): void
    {
        $id = $this->insertEncounter(['expires_tick' => 5]);

        $result = $this->barService->resolveInformationEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
    }

    // ── RESOLVE — veteran (Deva) ─────────────────────────────────────────────

    public function test_resolve_veteran_drill_buffer_activates_buffer_and_marks_used_once(): void
    {
        $id = $this->insertEncounter(['character_slug' => 'veteran', 'outcome_key' => 'drill_buffer']);

        $result = $this->barService->resolveInformationEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $state = $this->poolState();
        $this->assertTrue((bool) $state->active_drill_buffer);
        $this->assertTrue((bool) $state->deva_buff_used);
    }

    public function test_resolve_veteran_knowledge_boost_injects_ap_into_chosen_knowledge(): void
    {
        $id = $this->insertEncounter(['character_slug' => 'veteran', 'outcome_key' => 'knowledge_boost']);

        $result = $this->barService->resolveInformationEncounter(
            self::COLONY_ID, $id, self::USER_ID, 10, self::KNOWLEDGE_GEOLOGY_ID
        );

        $this->assertTrue($result['ok']);
        $expected = (int) config('game.bar.information_pool.veteran.knowledge_boost_ap');
        $this->assertSame($expected, $this->knowledgeApSpend(self::KNOWLEDGE_GEOLOGY_ID));
        $this->assertTrue((bool) $this->poolState()->deva_knowledge_used);
    }

    public function test_resolve_veteran_knowledge_boost_without_choice_grants_no_bonus(): void
    {
        $id = $this->insertEncounter(['character_slug' => 'veteran', 'outcome_key' => 'knowledge_boost']);

        $result = $this->barService->resolveInformationEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertSame(0, $result['ap_bonus']);
        $this->assertFalse((bool) $this->poolState()?->deva_knowledge_used);
    }

    public function test_resolve_veteran_narrative_outcome_has_no_mechanical_effect(): void
    {
        $id = $this->insertEncounter(['character_slug' => 'veteran', 'outcome_key' => 'narrative_1']);

        $result = $this->barService->resolveInformationEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $state = $this->poolState();
        $this->assertFalse((bool) ($state->active_drill_buffer ?? false));
        $this->assertFalse((bool) ($state->deva_buff_used ?? false));
        $this->assertFalse((bool) ($state->deva_knowledge_used ?? false));
    }

    // ── RESOLVE — ai_researcher (Lenn) ───────────────────────────────────────

    public function test_resolve_ai_researcher_nav_discount_activates_voucher_and_marks_used_once(): void
    {
        $id = $this->insertEncounter(['character_slug' => 'ai_researcher', 'outcome_key' => 'nav_discount']);

        $result = $this->barService->resolveInformationEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $state = $this->poolState();
        $this->assertTrue((bool) $state->active_nav_voucher);
        $this->assertTrue((bool) $state->lenn_nav_used);
    }

    public function test_resolve_ai_researcher_knowledge_boost_injects_ap_into_cartography(): void
    {
        $id = $this->insertEncounter(['character_slug' => 'ai_researcher', 'outcome_key' => 'knowledge_boost']);

        $result = $this->barService->resolveInformationEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $expected = (int) config('game.bar.information_pool.ai_researcher.knowledge_boost_ap');
        $this->assertSame($expected, $this->knowledgeApSpend(self::KNOWLEDGE_CARTOGRAPHY_ID));
        $this->assertTrue((bool) $this->poolState()->lenn_knowledge_used);
    }

    // ── RESOLVE — Charakter-Kodex hookup ─────────────────────────────────────

    public function test_resolve_records_codex_progress_for_the_character(): void
    {
        $id = $this->insertEncounter(['character_slug' => 'veteran', 'outcome_key' => 'narrative_1']);

        $this->barService->resolveInformationEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $entry = DB::table('character_codex_entries')
            ->where('user_id', self::USER_ID)
            ->where('character_slug', 'veteran')
            ->first();
        $this->assertNotNull($entry);
        $this->assertSame(1, (int) $entry->entry_number);
    }
}
