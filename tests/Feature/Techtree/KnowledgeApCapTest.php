<?php

namespace Tests\Feature\Techtree;

use App\Models\Advisor;
use App\Models\User;
use App\Services\AdvisorService;
use App\Services\DailyApCapService;
use App\Services\Techtree\ResearchService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Per-knowledge per-Sol AP cap in the lab (T43-G1) on the shared DailyApCapService. */
class KnowledgeApCapTest extends TestCase
{
    use RefreshDatabase;

    private int $colonyId = 1;

    private int $knowledgeId = 94;

    private ResearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        config(['game.bypass.ap_checks' => false]);
        Advisor::where('colony_id', $this->colonyId)->delete();
        Advisor::create([
            'user_id' => 3,
            'personell_id' => AdvisorService::idFor('scientist'),
            'colony_id' => $this->colonyId,
            'rank' => 3,
            'active_ticks' => 0,
        ]);
        // Mechanism tests: fixed cap 3 (no scientist field bonus) and pre-scale knowledge costs.
        config(['game.ap.base' => 40, 'game.knowledge_ap_cap_per_sol.base' => 3, 'game.advisor.cap_bonus_per_rank' => [1 => 0, 2 => 0, 3 => 0]]);
        foreach (array_keys(config('knowledge')) as $key) {
            config(["knowledge.{$key}.levelup_costs" => [1 => 20, 2 => 28, 3 => 36, 4 => 44, 5 => 52]]);
        }
        $this->service = $this->app->make(ResearchService::class);
    }

    private function apSpend(int $id): int
    {
        return (int) DB::table('colony_researches')
            ->where(['colony_id' => $this->colonyId, 'research_id' => $id])->value('ap_spend');
    }

    public function test_invest_beyond_cap_is_refused_with_code(): void
    {
        $this->assertTrue($this->service->invest($this->colonyId, $this->knowledgeId, 'add', 3));
        $this->assertFalse($this->service->invest($this->colonyId, $this->knowledgeId, 'add', 1));
        $this->assertSame('knowledge_ap_cap', $this->service->investBlocker($this->colonyId, $this->knowledgeId, 'add', 1));
        $this->assertSame(3, $this->apSpend($this->knowledgeId));
    }

    public function test_order_batch_invests_what_fits_instead_of_refusing_all(): void
    {
        // Three quick clicks (ap=5 asked, cap 3): the batch must apply the 3 that fit.
        $this->actingAs(User::where('user_id', 3)->firstOrFail())
            ->postJson("/api/v1/techtree/research/{$this->knowledgeId}/order", ['order' => 'add', 'ap' => 5])
            ->assertOk()->assertJsonPath('success', true);

        $this->assertSame(3, $this->apSpend($this->knowledgeId));
    }

    public function test_cap_is_per_knowledge(): void
    {
        $this->service->invest($this->colonyId, $this->knowledgeId, 'add', 3);
        $this->assertTrue($this->service->invest($this->colonyId, 93, 'add', 1));
    }

    public function test_cap_resets_with_next_sol(): void
    {
        $this->service->invest($this->colonyId, $this->knowledgeId, 'add', 3);
        Artisan::call('game:tick', ['--tick' => app(TickService::class)->getTickCount() + 1]);
        $this->assertTrue($this->service->invest($this->colonyId, $this->knowledgeId, 'add', 1));
    }

    public function test_bonus_ap_bypasses_and_does_not_consume_cap(): void
    {
        $this->service->invest($this->colonyId, $this->knowledgeId, 'add', 3);
        $this->assertTrue($this->service->investBonus($this->colonyId, $this->knowledgeId, 2)->accepted);
        $this->assertSame(5, $this->apSpend($this->knowledgeId));

        $cap = app(DailyApCapService::class);
        $this->assertSame(3, $cap->spent($this->colonyId, 'knowledge:'.$this->knowledgeId));
    }

    public function test_null_base_switches_cap_off(): void
    {
        config(['game.knowledge_ap_cap_per_sol.base' => null]);
        $this->assertTrue($this->service->invest($this->colonyId, $this->knowledgeId, 'add', 9));
    }

    public function test_service_state_reports_remaining(): void
    {
        $cap = app(DailyApCapService::class);
        $key = 'training:salvage';
        $this->assertSame(['cap' => 4, 'spent' => 0, 'remaining' => 4], $cap->state($this->colonyId, $key, 4));
        $cap->record($this->colonyId, $key, 3);
        $this->assertSame(['cap' => 4, 'spent' => 3, 'remaining' => 1], $cap->state($this->colonyId, $key, 4));
        $this->assertTrue($cap->wouldExceed($this->colonyId, $key, 4, 2));
        $this->assertFalse($cap->wouldExceed($this->colonyId, $key, 4, 1));
        $this->assertNull($cap->state($this->colonyId, $key, null));
    }

    public function test_order_endpoint_answers_422_with_code_and_techtree_exposes_cap(): void
    {
        $bart = User::find(3);
        $this->service->invest($this->colonyId, $this->knowledgeId, 'add', 3);

        $res = $this->actingAs($bart)->postJson(route('techtree.order', ['type' => 'research', 'id' => $this->knowledgeId]), ['order' => 'add', 'ap' => 1]);
        $res->assertStatus(422)->assertJson(['success' => false, 'error' => 'knowledge_ap_cap']);
        $this->assertStringContainsString('3', $res->json('message'));

        $res = $this->actingAs($bart)->postJson(route('techtree.order', ['type' => 'research', 'id' => 93]), ['order' => 'add', 'ap' => 1]);
        $res->assertOk()->assertJsonPath('tech.ap_cap', ['cap' => 3, 'spent' => 1, 'remaining' => 2]);
    }
}
