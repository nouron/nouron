<?php

namespace Tests\Feature\Security;

use App\Models\Advisor;
use App\Models\User;
use App\Services\Techtree\PersonellService;
use App\Services\Techtree\ResearchService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Security tests covering cross-colony access vectors.
 *
 * Scenarios covered:
 *   1. Knowledge CC-level gate: levelup to level 4 blocked when CC is at level 3
 *   2. Knowledge CC-level gate: levelup to level 4 succeeds when CC is at level 4 (positive baseline)
 *   3. Knowledge CC-level gate: levelup to level 5 succeeds when CC equals the required level
 *   4. Knowledge CC-level gate: levelup to level 5 blocked when CC is at level 4
 *
 * Trade-, fleet- and galaxy-route scenarios were removed together with the legacy
 * trade/fleet/galaxy screens (controllers + routes + models deleted 2026-06).
 *
 * Fixture (TestSeeder / testdata.sqlite.sql):
 *   User 3 (Bart)  → colony 1 "Springfield"  (CC level=3)
 *   User 0 (Homer) → colony 2 "Shelbyville"  (CC level=5)
 */
class CrossColonyAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $bart;

    private User $homer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->bart = User::where('user_id', 3)->firstOrFail();
        $this->homer = User::where('user_id', 0)->firstOrFail();
    }

    // ── Knowledge CC-level gate ───────────────────────────────────────────────

    /**
     * Colony 1 (Springfield) has CC at level 3.
     * Config says knowledge level 4 requires CC level 4.
     * Levelup to knowledge level 4 must be rejected (returns false).
     *
     * Setup: bring knowledge_construction (90) to level 3 via direct DB writes
     * so we can attempt the level-4 transition without burning AP in a fixture.
     */
    public function test_knowledge_levelup_to_4_blocked_when_cc_is_level_3(): void
    {
        $colonyId = 1;
        $knowledgeId = 90; // knowledge_construction — exists in testdata

        // Precondition: colony 1 CC is at level 3
        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 25)
            ->value('level');
        $this->assertSame(3, $ccLevel, 'precondition: CC must be at level 3 for this test');

        // Manually set knowledge to level 3, ap_spend to full for level 3→4 transition.
        // config/knowledge.php: levelup_costs[4] = 28 → ap_spend must reach 28.
        // Setting it to the full threshold means only the CC-gate is the blocking condition.
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $colonyId, 'research_id' => $knowledgeId],
            ['level' => 3, 'status_points' => 20, 'ap_spend' => 28]
        );

        // Config: knowledge_cc_level_cap[4] = 4, but CC is 3 → must block
        $service = $this->app->make(ResearchService::class);
        $result = $service->levelup($colonyId, $knowledgeId);

        $this->assertFalse(
            $result,
            'Levelup to knowledge level 4 must fail when CC is only at level 3'
        );

        // Level must remain at 3 in the DB
        $level = (int) DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', $knowledgeId)
            ->value('level');
        $this->assertSame(3, $level, 'Knowledge level must not have changed after blocked levelup');
    }

    /**
     * Positive baseline: colony 2 (Shelbyville) has CC at level 5 (>= required 4).
     * Levelup to knowledge level 4 must succeed.
     */
    public function test_knowledge_levelup_to_4_succeeds_when_cc_is_level_5(): void
    {
        $colonyId = 2;
        $knowledgeId = 90; // knowledge_construction

        // Precondition: colony 2 CC is at level 5
        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 25)
            ->value('level');
        $this->assertSame(5, $ccLevel, 'precondition: CC must be at level 5 for this test');

        // Add a Wissenschaftler to colony 2 so AP pool is available
        Advisor::where('colony_id', $colonyId)->delete();
        Advisor::create([
            'user_id' => $this->homer->user_id,
            'personell_id' => PersonellService::idFor('scientist'),
            'colony_id' => $colonyId,
            'rank' => 2,
            'active_ticks' => 0,
        ]);

        // Set knowledge to level 3, ap_spend satisfied: levelup_costs[4] = 28
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $colonyId, 'research_id' => $knowledgeId],
            ['level' => 3, 'status_points' => 20, 'ap_spend' => 28]
        );

        $service = $this->app->make(ResearchService::class);
        $result = $service->levelup($colonyId, $knowledgeId);

        $this->assertTrue(
            $result,
            'Levelup to knowledge level 4 must succeed when CC is at level 5'
        );

        $level = (int) DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', $knowledgeId)
            ->value('level');
        $this->assertSame(4, $level);
    }

    /**
     * Edge case: CC at exactly the required threshold level.
     * Colony 2 CC=5. Knowledge level 5 requires CC level 5.
     * Must succeed (equals is allowed, only strict less-than blocks).
     */
    public function test_knowledge_levelup_to_5_succeeds_when_cc_is_exactly_5(): void
    {
        $colonyId = 2;
        $knowledgeId = 90; // knowledge_construction

        // Precondition: colony 2 CC is at level 5
        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 25)
            ->value('level');
        $this->assertSame(5, $ccLevel, 'precondition: CC must be at level 5 for this test');

        // Add a Wissenschaftler to colony 2
        Advisor::where('colony_id', $colonyId)->delete();
        Advisor::create([
            'user_id' => $this->homer->user_id,
            'personell_id' => PersonellService::idFor('scientist'),
            'colony_id' => $colonyId,
            'rank' => 2,
            'active_ticks' => 0,
        ]);

        // Set knowledge to level 4, ap_spend satisfied: levelup_costs[5] = 40
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $colonyId, 'research_id' => $knowledgeId],
            ['level' => 4, 'status_points' => 20, 'ap_spend' => 40]
        );

        $service = $this->app->make(ResearchService::class);
        $result = $service->levelup($colonyId, $knowledgeId);

        $this->assertTrue(
            $result,
            'Levelup to knowledge level 5 must succeed when CC equals the required level 5'
        );

        $level = (int) DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', $knowledgeId)
            ->value('level');
        $this->assertSame(5, $level);
    }

    /**
     * Adversarial: CC at level 4 cannot unlock knowledge level 5 (requires CC level 5).
     * Uses colony 1 after manually bumping CC to 4 — just below the level-5 gate.
     */
    public function test_knowledge_levelup_to_5_blocked_when_cc_is_level_4(): void
    {
        $colonyId = 1;
        $knowledgeId = 90; // knowledge_construction

        // Bump colony 1 CC to level 4 (was 3 in seed; still below the level-5 gate)
        DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 25)
            ->update(['level' => 4]);

        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 25)
            ->value('level');
        $this->assertSame(4, $ccLevel, 'precondition: CC must be at level 4 for this test');

        // Set knowledge to level 4, ap_spend satisfied: levelup_costs[5] = 40
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $colonyId, 'research_id' => $knowledgeId],
            ['level' => 4, 'status_points' => 20, 'ap_spend' => 40]
        );

        // Config: knowledge_cc_level_cap[5] = 5, but CC is 4 → must block
        $service = $this->app->make(ResearchService::class);
        $result = $service->levelup($colonyId, $knowledgeId);

        $this->assertFalse(
            $result,
            'Levelup to knowledge level 5 must fail when CC is only at level 4'
        );

        $level = (int) DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', $knowledgeId)
            ->value('level');
        $this->assertSame(4, $level, 'Knowledge level must not have changed after blocked levelup');
    }
}
