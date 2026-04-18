<?php

namespace Tests\Feature\Techtree;

use App\Models\Advisor;
use App\Services\Techtree\PersonellService;
use App\Services\Techtree\ResearchService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests for the Kenntnisse (knowledge) system.
 *
 * Kenntnisse use the ResearchService (Wissenschaftler AP) with IDs 90–96.
 * Key properties: no decay (GDD §10), supply cap bonus per level (GDD §6).
 *
 * Fixture (TestSeeder / testdata.sqlite.sql):
 *   Colony 1 (Springfield), user_id=3 (Bart), CC level=10, housing level=2
 */
class KnowledgeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ResearchService $service;
    protected int $colonyId = 1;
    protected int $userId   = 3;

    // knowledge_health (94) — positive moral effect, representative for all Kenntnisse
    protected int $knowledgeId = 94;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(ResearchService::class);

        // Replace seeded advisors with a single Rank-2 Wissenschaftler (7 research AP)
        Advisor::where('colony_id', $this->colonyId)->delete();
        Advisor::create([
            'user_id'     => $this->userId,
            'personell_id'=> PersonellService::idFor('scientist'),
            'colony_id'   => $this->colonyId,
            'rank'        => 2,
            'active_ticks'=> 0,
        ]);
    }

    // ── Existence ────────────────────────────────────────────────────────────

    public function test_all_seven_kenntnisse_exist_in_db(): void
    {
        $ids = collect(config('knowledge'))->pluck('id')->sort()->values()->toArray();
        $this->assertEquals([90, 91, 92, 93, 94, 95, 96], $ids);

        foreach ($ids as $id) {
            $this->assertNotNull(
                DB::table('researches')->where('id', $id)->first(),
                "knowledge ID $id not found in researches table"
            );
        }
    }

    public function test_kenntnisse_have_zero_decay_rate(): void
    {
        foreach (config('knowledge') as $key => $cfg) {
            $row = DB::table('researches')->where('id', $cfg['id'])->first();
            $this->assertEquals(0, (float) $row->decay_rate, "decay_rate for $key must be 0");
        }
    }

    // ── Invest + levelup ─────────────────────────────────────────────────────

    public function test_invest_accumulates_ap_spend(): void
    {
        // Lv0→1 costs 5 AP; invest 3 → ap_spend=3
        $this->service->invest($this->colonyId, $this->knowledgeId, 'add', 1);
        $this->service->invest($this->colonyId, $this->knowledgeId, 'add', 1);
        $this->service->invest($this->colonyId, $this->knowledgeId, 'add', 1);

        $apSpend = (int) DB::table('colony_researches')
            ->where('colony_id', $this->colonyId)
            ->where('research_id', $this->knowledgeId)
            ->value('ap_spend');

        $this->assertEquals(3, $apSpend);
    }

    public function test_levelup_requires_full_ap_investment(): void
    {
        // Lv0→1 costs 5 AP; only 4 invested — levelup must fail
        $this->service->invest($this->colonyId, $this->knowledgeId, 'add', 4);

        $result = $this->service->levelup($this->colonyId, $this->knowledgeId);
        $this->assertFalse($result);

        $level = (int) DB::table('colony_researches')
            ->where('colony_id', $this->colonyId)
            ->where('research_id', $this->knowledgeId)
            ->value('level');
        $this->assertEquals(0, $level);
    }

    public function test_levelup_succeeds_after_full_ap_investment(): void
    {
        // Lv0→1 costs 5 AP (config/knowledge.php → levelup_costs[1])
        $this->service->invest($this->colonyId, $this->knowledgeId, 'add', 5);

        $result = $this->service->levelup($this->colonyId, $this->knowledgeId);
        $this->assertTrue($result);

        $level = (int) DB::table('colony_researches')
            ->where('colony_id', $this->colonyId)
            ->where('research_id', $this->knowledgeId)
            ->value('level');
        $this->assertEquals(1, $level);
    }

    public function test_levelup_costs_increase_per_level(): void
    {
        // Lv0→1 = 5 AP. Tick 9201 to invest, tick 9202 to clear AP locks for next round.
        $this->service->invest($this->colonyId, $this->knowledgeId, 'add', 5);
        $this->service->levelup($this->colonyId, $this->knowledgeId);
        Artisan::call('game:tick', ['--tick' => 9201]); // clears AP locks, knowledge stays at Lv1

        // Lv1→2 = 10 AP. With 9 invested levelup must still fail.
        $this->service->invest($this->colonyId, $this->knowledgeId, 'add', 7);
        $this->assertFalse($this->service->levelup($this->colonyId, $this->knowledgeId));
        Artisan::call('game:tick', ['--tick' => 9202]); // clear locks again

        $this->service->invest($this->colonyId, $this->knowledgeId, 'add', 3); // cumulative = 10
        $this->assertTrue($this->service->levelup($this->colonyId, $this->knowledgeId));

        $level = (int) DB::table('colony_researches')
            ->where('colony_id', $this->colonyId)
            ->where('research_id', $this->knowledgeId)
            ->value('level');
        $this->assertEquals(2, $level);
    }

    // ── No decay ─────────────────────────────────────────────────────────────

    public function test_knowledge_does_not_decay_after_tick(): void
    {
        // Unlock the knowledge (Lv0→1 = 5 AP)
        $this->service->invest($this->colonyId, $this->knowledgeId, 'add', 5);
        $this->service->levelup($this->colonyId, $this->knowledgeId);

        // Run a tick — decay_rate=0, so level must remain 1
        Artisan::call('game:tick', ['--tick' => 9100]);

        $level = (int) DB::table('colony_researches')
            ->where('colony_id', $this->colonyId)
            ->where('research_id', $this->knowledgeId)
            ->value('level');

        $this->assertEquals(1, $level, 'Knowledge level must not decay after a tick');
    }

    // ── Supply cap ───────────────────────────────────────────────────────────

    public function test_supply_cap_includes_knowledge_bonus(): void
    {
        // Colony 1: CC=10 (flat 10), housing=2 (16). No knowledge → cap=26.
        Artisan::call('game:tick', ['--tick' => 9101]);
        $before = (int) DB::table('user_resources')->where('user_id', $this->userId)->value('supply');
        $this->assertEquals(26, $before);

        // Unlock knowledge_health (level 1 → +3 cap per config knowledge_cap_per_level[1])
        $this->service->invest($this->colonyId, $this->knowledgeId, 'add', 5);
        $this->service->levelup($this->colonyId, $this->knowledgeId);

        Artisan::call('game:tick', ['--tick' => 9102]);
        $after = (int) DB::table('user_resources')->where('user_id', $this->userId)->value('supply');

        // Level 1 gives +3 (knowledge_cap_per_level[1] = 3)
        $this->assertEquals(29, $after);
    }
}
