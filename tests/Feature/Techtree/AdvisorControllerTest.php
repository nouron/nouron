<?php

namespace Tests\Feature\Techtree;

/**
 * AdvisorController feature tests.
 *
 * Covered scenarios:
 *  INDEX
 *    - test_index_returns_200
 *    - test_index_passes_page_data_with_five_slots
 *    - test_index_slots_have_correct_cc_required_values
 *    - test_locked_slots_when_cc_level_three
 *
 *  HIRE — redirect branch
 *    - test_hire_redirects_on_success
 *    - test_hire_fails_for_unknown_personell_id
 *
 *  HIRE — JSON branch
 *    - test_hire_returns_json_on_ajax_request
 *    - test_hire_json_returns_422_on_duplicate
 *
 *  FIRE — redirect branch
 *    - test_fire_redirects_on_success
 *    - test_fire_returns_404_for_foreign_advisor
 *
 *  FIRE — JSON branch
 *    - test_fire_returns_json_on_ajax_request
 *    - test_fire_json_returns_404_for_foreign_advisor
 */

use App\Models\User;
use App\Services\AdvisorService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdvisorControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Fixture constants ─────────────────────────────────────────────────────

    protected int $userIdBart = 3;   // owns colony 1 (Springfield), CC level 3

    protected int $userIdHomer = 0;   // owns advisors on colony 2 (Shelbyville)

    protected int $colonyIdBart = 1;

    protected int $colonyIdHomer = 2;

    // personell_id values from config/advisors.php
    protected int $personellEngineer = 35;

    protected int $personellScientist = 36;

    protected int $personellPilot = 89;

    protected int $personellTrader = 92;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Return a request session carrying the active colony for Bart.
     */
    private function bartSession(): array
    {
        return ['activeIds' => ['colonyId' => $this->colonyIdBart]];
    }

    /**
     * Remove all advisors for Bart's colony so hire tests start from zero.
     */
    private function clearBartAdvisors(): void
    {
        DB::table('advisors')
            ->where('colony_id', $this->colonyIdBart)
            ->delete();
    }

    /**
     * Hire one advisor for Bart directly via DB (bypasses credit cost) and
     * return the new advisor id.
     */
    private function insertAdvisor(int $userId, int $personellId, int $colonyId, int $rank = 1): int
    {
        return DB::table('advisors')->insertGetId([
            'user_id' => $userId,
            'personell_id' => $personellId,
            'colony_id' => $colonyId,
            'rank' => $rank,
            'active_ticks' => 0,
            'unavailable_until_tick' => null,
        ]);
    }

    /**
     * Ensure Bart's user_resources row has enough credits for hiring.
     */
    private function ensureCredits(int $userId, int $credits): void
    {
        DB::table('user_resources')
            ->where('user_id', $userId)
            ->update(['credits' => $credits]);
    }

    // ── INDEX tests ───────────────────────────────────────────────────────────

    public function test_index_returns_200(): void
    {
        $bart = User::find($this->userIdBart);

        $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->getJson(route('advisors.index'))
            ->assertOk();
    }

    public function test_index_passes_page_data_with_five_slots(): void
    {
        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->get(route('advisors.index'));

        $response->assertOk();
        $response->assertViewHas('pageData');

        $pageData = $response->viewData('pageData');

        $this->assertArrayHasKey('slots', $pageData);
        $this->assertArrayHasKey('slotInfo', $pageData);
        $this->assertArrayHasKey('routes', $pageData);
        $this->assertArrayHasKey('colonyId', $pageData);

        $slots = $pageData['slots'];
        $this->assertCount(4, $slots, 'There must be exactly 4 advisor slots');

        $requiredKeys = ['position', 'key', 'name', 'state', 'personell_id', 'hire_cost', 'cc_required', 'advisor'];
        foreach ($slots as $i => $slot) {
            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey($key, $slot, "Slot {$i} is missing key '{$key}'");
            }
        }
    }

    public function test_index_slots_have_correct_cc_required_values(): void
    {
        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->get(route('advisors.index'));

        $slots = $response->viewData('pageData')['slots'];

        // Engineer (pos 1): cc_required=1. Placed path slots (2-3): position-based gate (2, 3).
        // Unplaced path slot (pos 4, bar not yet built): cc_required=2 (path_open gate).
        // Strategist (pos 5) is postponed (GDD §13.6, decision 2026-08-02).
        $expected = [1 => 1, 2 => 2, 3 => 3, 4 => 2];
        foreach ($slots as $slot) {
            $this->assertEquals(
                $expected[$slot['position']],
                $slot['cc_required'],
                "Slot position {$slot['position']} must require CC level {$expected[$slot['position']]}"
            );
        }
    }

    /**
     * Bart's colony 1 has CC level 3.
     * Positions 1–3 are filled with fixed/path advisors and should be 'active' or 'empty'.
     * Position 4 is a path_open slot (bar not yet placed) which requires CC Lv2 and is therefore
     * 'empty' when no advisor hired (not locked).
     * The test data pre-seeds advisors 35, 36, 92, 93 for Bart on colony 1.
     * We clear them so all unlocked slots appear as 'empty'.
     * Slot 5 (strategist) is postponed (GDD §13.6, decision 2026-08-02).
     */
    public function test_locked_slots_when_cc_level_three(): void
    {
        $this->clearBartAdvisors();

        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->get(route('advisors.index'));

        $slots = $response->viewData('pageData')['slots'];

        // CC=3: slots 1-3 are fixed/path slots (unlocked with CC Lv3). Slot 4 is path_open
        // requiring CC Lv2 (unlocked with CC Lv3, shows as path_open state).
        foreach ($slots as $slot) {
            $this->assertNotEquals('locked', $slot['state'],
                "Slot {$slot['position']} should not be locked with CC level 3");
        }

    }

    public function test_index_slot_state_is_empty_when_no_advisor_hired(): void
    {
        $this->clearBartAdvisors();

        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->get(route('advisors.index'));

        $slots = $response->viewData('pageData')['slots'];

        // First unlocked slot (engineer / position 1) must be 'empty'
        $this->assertEquals('empty', $slots[0]['state'],
            'Engineer slot must be empty when no advisor is hired');
    }

    public function test_index_slot_state_is_active_when_advisor_is_hired(): void
    {
        $this->clearBartAdvisors();
        $this->insertAdvisor($this->userIdBart, $this->personellEngineer, $this->colonyIdBart);

        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->get(route('advisors.index'));

        $slots = $response->viewData('pageData')['slots'];

        // engineer is position 1 (index 0)
        $engineerSlot = $slots[0];
        $this->assertEquals('active', $engineerSlot['state'],
            'Engineer slot must be active when an advisor is hired');
        $this->assertNotNull($engineerSlot['advisor'],
            'Advisor data must be populated for an active slot');
    }

    // ── HIRE — redirect branch ────────────────────────────────────────────────

    public function test_hire_redirects_on_success(): void
    {
        $this->clearBartAdvisors();
        $this->ensureCredits($this->userIdBart, 10000);

        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->post(route('advisors.hire'), ['personell_id' => $this->personellEngineer]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_hire_creates_advisor_record_in_database(): void
    {
        $this->clearBartAdvisors();
        $this->ensureCredits($this->userIdBart, 10000);

        $bart = User::find($this->userIdBart);

        $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->post(route('advisors.hire'), ['personell_id' => $this->personellEngineer]);

        $this->assertDatabaseHas('advisors', [
            'user_id' => $this->userIdBart,
            'personell_id' => $this->personellEngineer,
            'colony_id' => $this->colonyIdBart,
            'rank' => 1,
        ]);
    }

    public function test_hire_fails_for_unknown_personell_id(): void
    {
        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->post(route('advisors.hire'), ['personell_id' => 999]);

        // Validation must reject unknown IDs (422 for JSON, redirect-with-errors for HTML)
        $response->assertStatus(302);
        $response->assertSessionHasErrors('personell_id');
    }

    // ── HIRE — JSON branch ────────────────────────────────────────────────────

    public function test_hire_returns_json_on_ajax_request(): void
    {
        $this->clearBartAdvisors();
        $this->ensureCredits($this->userIdBart, 10000);

        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('advisors.hire'), ['personell_id' => $this->personellEngineer]);

        $response->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonStructure(['ok', 'slots', 'slotInfo', 'credits']);
    }

    public function test_hire_json_returns_deducted_credits_for_resourcebar_sync(): void
    {
        // Regression: the resourcebar Credits chip only updates reactively via
        // this field (advisors.js patches the DOM with it) — without it the
        // player sees a stale balance until a full page reload.
        // phpunit.xml sets GAME_BYPASS_RESOURCES=true for the suite — override
        // it here so the credits deduction this test exercises actually runs.
        config(['game.bypass.resource_costs' => false]);
        $this->clearBartAdvisors();
        $this->ensureCredits($this->userIdBart, 10000);
        $hireCost = (int) (collect(config('advisors'))->firstWhere('id', $this->personellEngineer)['credits'] ?? 0);

        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('advisors.hire'), ['personell_id' => $this->personellEngineer]);

        $response->assertOk()->assertJson(['ok' => true, 'credits' => 10000 - $hireCost]);
    }

    public function test_hire_json_returns_ap_available_for_resourcebar_sync(): void
    {
        // Regression (Owner-Playtest 2026-08-31): the resourcebar AP chip only
        // updates reactively via this field (advisors.js patches the DOM with
        // it, mirroring the existing credits-chip sync) — without it a newly
        // hired advisor's AP contribution is invisible in the header until a
        // full page reload, even though onboarding hints (which read the AP
        // pool fresh server-side) already see it.
        $this->clearBartAdvisors();
        $this->ensureCredits($this->userIdBart, 10000);
        $apBefore = app(AdvisorService::class)->getAvailableActionPoints($this->colonyIdBart);

        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('advisors.hire'), ['personell_id' => $this->personellEngineer]);

        $apAfter = app(AdvisorService::class)->getAvailableActionPoints($this->colonyIdBart);
        $this->assertGreaterThan($apBefore, $apAfter, 'precondition: hiring must actually raise the AP pool');
        $response->assertOk()->assertJson(['ok' => true, 'apAvailable' => $apAfter]);
    }

    public function test_hire_json_returns_422_on_duplicate(): void
    {
        $this->clearBartAdvisors();
        $this->ensureCredits($this->userIdBart, 10000);

        $bart = User::find($this->userIdBart);

        // First hire via plain POST (seeds the advisor)
        $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->post(route('advisors.hire'), ['personell_id' => $this->personellEngineer]);

        // Second hire via AJAX should return 422 with ok=false
        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('advisors.hire'), ['personell_id' => $this->personellEngineer]);

        $response->assertStatus(422)
            ->assertJson(['ok' => false])
            ->assertJsonStructure(['ok', 'error']);
    }

    public function test_hire_json_returns_422_on_unknown_personell_id(): void
    {
        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('advisors.hire'), ['personell_id' => 999]);

        $response->assertStatus(422);
    }

    public function test_hire_json_returns_422_when_slot_full(): void
    {
        // Colony 1 has CC level 3, so maxSlots = min(cc_level=3, config max_slots=4) = 3
        // (AdvisorService::hire(), CC-Level gate). Fill exactly 3 distinct slots, then
        // attempt to hire a 4th, still-unused type — this must hit the slot_full branch,
        // not the duplicate branch (which fires earlier in AdvisorService::hire() and
        // would mask slot_full if we tried to re-hire an already-hired type instead).
        $this->clearBartAdvisors();
        $this->ensureCredits($this->userIdBart, 10000);

        $this->insertAdvisor($this->userIdBart, $this->personellEngineer, $this->colonyIdBart);
        $this->insertAdvisor($this->userIdBart, $this->personellScientist, $this->colonyIdBart);
        $this->insertAdvisor($this->userIdBart, $this->personellPilot, $this->colonyIdBart);

        $bart = User::find($this->userIdBart);

        // 3 of 3 slots filled (CC=3). Trader is a distinct, not-yet-hired type —
        // this exercises slot_full, since a duplicate check would not apply here.
        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('advisors.hire'), ['personell_id' => $this->personellTrader]);

        $response->assertStatus(422)
            ->assertJson(['ok' => false, 'error' => 'slot_full']);
    }

    // ── FIRE — redirect branch ────────────────────────────────────────────────

    public function test_fire_redirects_on_success(): void
    {
        $this->clearBartAdvisors();

        $advisorId = $this->insertAdvisor($this->userIdBart, $this->personellEngineer, $this->colonyIdBart);

        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->delete(route('advisors.fire', ['id' => $advisorId]));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_fire_sets_colony_id_to_null_on_advisor(): void
    {
        $this->clearBartAdvisors();

        $advisorId = $this->insertAdvisor($this->userIdBart, $this->personellEngineer, $this->colonyIdBart);

        $bart = User::find($this->userIdBart);

        $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->delete(route('advisors.fire', ['id' => $advisorId]));

        $this->assertDatabaseHas('advisors', [
            'id' => $advisorId,
            'colony_id' => null,
        ]);
    }

    public function test_fire_returns_404_for_foreign_advisor(): void
    {
        // Advisor id=5 belongs to Homer (user_id=0) on colony 2.
        // Bart must not be able to fire it.
        $homerAdvisorId = 5;

        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->delete(route('advisors.fire', ['id' => $homerAdvisorId]));

        $response->assertNotFound();
    }

    public function test_fire_returns_404_for_nonexistent_advisor(): void
    {
        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->delete(route('advisors.fire', ['id' => 99999]));

        $response->assertNotFound();
    }

    // ── FIRE — JSON branch ────────────────────────────────────────────────────

    public function test_fire_returns_json_on_ajax_request(): void
    {
        $this->clearBartAdvisors();

        $advisorId = $this->insertAdvisor($this->userIdBart, $this->personellEngineer, $this->colonyIdBart);

        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->withHeaders(['Accept' => 'application/json'])
            ->delete(route('advisors.fire', ['id' => $advisorId]));

        $response->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonStructure(['ok', 'slots', 'slotInfo']);
    }

    public function test_fire_json_returns_404_for_foreign_advisor(): void
    {
        // Advisor id=5 belongs to Homer — Bart cannot fire it via AJAX either.
        $homerAdvisorId = 5;

        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)
            ->withSession($this->bartSession())
            ->withHeaders(['Accept' => 'application/json'])
            ->delete(route('advisors.fire', ['id' => $homerAdvisorId]));

        $response->assertStatus(404)
            ->assertJson(['ok' => false]);
    }

    // ── Auth guard ────────────────────────────────────────────────────────────

    public function test_index_requires_authentication(): void
    {
        $response = $this->get(route('advisors.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_hire_requires_authentication(): void
    {
        $response = $this->post(route('advisors.hire'), ['personell_id' => $this->personellEngineer]);
        $response->assertRedirect(route('login'));
    }

    public function test_fire_requires_authentication(): void
    {
        $response = $this->delete(route('advisors.fire', ['id' => 1]));
        $response->assertRedirect(route('login'));
    }
}
