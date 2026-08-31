<?php

namespace Tests\Feature\CommLog;

use App\Models\ColonyLog;
use App\Models\User;
use App\Services\EventService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CommLogController had 0% test coverage — this exercises log()/nexus() and,
 * through them, the private decorate()/buildDescription() branches for every
 * event type the comm log renders.
 *
 * Fixture: user_id=3 (Bart), colony 1 (Springfield), run 1 already active
 * (data/sql/testdata.sqlite.sql) — no extra Run setup needed.
 */
class CommLogControllerTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Test data pre-populates colony_log with ~14 rows for this user (playtest
        // fixture) — clear them so each test starts from a known, empty log.
        DB::table('colony_log')->where('user', self::USER_ID)->delete();
    }

    private function user(): User
    {
        return User::where('user_id', self::USER_ID)->firstOrFail();
    }

    // Mirrors EventService::createEvent()'s is_read logic (nexus events start
    // unread, everything else starts read) so unread-count assertions match
    // real behaviour instead of ColonyLog's own column default.
    private function log(string $event, array $params = [], string $area = 'colony', int $tick = 10): int
    {
        return app(EventService::class)->createEvent([
            'user' => self::USER_ID,
            'tick' => $tick,
            'event' => $event,
            'area' => $area,
            'parameters' => json_encode($params),
        ]);
    }

    // ── log(): filtering ────────────────────────────────────────────────────

    public function test_log_excludes_nexus_area_and_onboarding_events(): void
    {
        $this->log('colony.building_placed', ['building_id' => 25], area: 'colony');
        $this->log('run.nexus_warning_sol30', [], area: 'nexus');
        $this->log('run.run_completed', [], area: 'colony'); // nexus key, non-nexus area
        $this->log('onboarding_decay', [], area: 'techtree');
        $this->log('run.sol_advanced', [], area: 'colony');

        $response = $this->actingAs($this->user())->get(route('comm.log'));

        $response->assertOk();
        $entries = $response->viewData('entries');
        $this->assertCount(1, $entries);
        $this->assertSame('colony.building_placed', $entries->first()['event']);
    }

    public function test_log_reports_unread_nexus_count(): void
    {
        $this->log('run.nexus_warning_sol30', [], area: 'nexus');
        $this->log('run.nexus_warning_sol50', [], area: 'nexus');

        $response = $this->actingAs($this->user())->get(route('comm.log'));

        $response->assertOk();
        $this->assertSame(2, $response->viewData('unreadCount'));
    }

    // ── nexus(): filtering + read-marking ───────────────────────────────────

    public function test_nexus_includes_nexus_area_and_nexus_keys_regardless_of_area(): void
    {
        $this->log('run.nexus_sanction_sol65', [], area: 'colony'); // nexus key, non-nexus area
        $this->log('run.run_failed_trust', [], area: 'nexus');
        $this->log('colony.building_placed', ['building_id' => 25], area: 'colony');

        $response = $this->actingAs($this->user())->get(route('comm.nexus'));

        $response->assertOk();
        $entries = $response->viewData('entries');
        $this->assertCount(2, $entries);
        $this->assertSame(0, $response->viewData('unreadCount'));
    }

    public function test_nexus_includes_phase1_deadline_warning_and_fail_events(): void
    {
        $this->log('run.nexus_phase1_warning', [], area: 'colony'); // nexus key, non-nexus area
        $this->log('run.run_failed_phase1_deadline', [], area: 'colony'); // nexus key, non-nexus area
        $this->log('colony.building_placed', ['building_id' => 25], area: 'colony');

        $response = $this->actingAs($this->user())->get(route('comm.nexus'));

        $response->assertOk();
        $entries = $response->viewData('entries');
        $this->assertCount(2, $entries);
        $events = $entries->pluck('event')->all();
        $this->assertContains('run.nexus_phase1_warning', $events);
        $this->assertContains('run.run_failed_phase1_deadline', $events);
    }

    public function test_nexus_marks_entries_as_read(): void
    {
        $this->log('run.nexus_warning_sol30', [], area: 'nexus');
        $this->assertSame(1, app(EventService::class)->countUnreadNexus(self::USER_ID));

        $this->actingAs($this->user())->get(route('comm.nexus'))->assertOk();

        $this->assertSame(0, app(EventService::class)->countUnreadNexus(self::USER_ID));
    }

    // ── decorate()/buildDescription(): one assertion per event branch ───────

    public function test_building_placed_description(): void
    {
        $this->log('colony.building_placed', ['building_id' => 25, 'building_name' => 'building_commandCenter']);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $segments = $entries->first()['segments'];

        $this->assertSame('building', $segments[0]['type']);
        $this->assertSame(__('techtree.building_commandCenter'), $segments[0]['label']);
        $this->assertStringContainsString('platziert', $segments[1]['value']);
    }

    public function test_building_invested_description_without_levelup(): void
    {
        $this->log('colony.building_invested', [
            'building_id' => 25, 'building_name' => 'building_commandCenter',
            'ap_spend' => 3, 'ap_for_levelup' => 10, 'level_up' => false,
        ]);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $segments = $entries->first()['segments'];

        // Regression (Owner-Playtest 2026-08-31): the AP amount must render as
        // an amount chip (matching the resourcebar's AP chip), not plain text.
        $this->assertSame('amount', $segments[0]['type']);
        $this->assertSame('AP', $segments[0]['abbr']);
        $this->assertSame(3, $segments[0]['value']);
        $this->assertStringContainsString('7 / 10 AP', $segments[3]['value']);
    }

    public function test_building_invested_description_with_levelup(): void
    {
        $this->log('colony.building_invested', [
            'building_id' => 25, 'building_name' => 'building_commandCenter',
            'ap_spend' => 10, 'ap_for_levelup' => 10, 'level_up' => true, 'new_level' => 4,
        ]);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $segments = $entries->first()['segments'];

        $this->assertSame('amount', $segments[0]['type']);
        $this->assertSame(10, $segments[0]['value']);
        $this->assertSame(4, $segments[2]['tooltip']['level']);
        $this->assertStringContainsString('Level 4 erreicht', $segments[3]['value']);
    }

    public function test_building_repaired_description(): void
    {
        $this->log('colony.building_repaired', [
            'building_id' => 25, 'building_name' => 'building_commandCenter',
            'status_points' => 15, 'max_status_points' => 20,
        ]);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $segments = $entries->first()['segments'];

        $this->assertStringContainsString('15 / 20', $segments[1]['value']);
    }

    public function test_tile_deep_scanned_with_coords(): void
    {
        $this->log('colony.tile_deep_scanned', ['q' => 2, 'r' => -1]);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $this->assertNotEmpty($entries->first()['segments']);
    }

    public function test_tile_deep_scanned_without_coords(): void
    {
        $this->log('colony.tile_deep_scanned', []);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $this->assertNotEmpty($entries->first()['segments']);
    }

    public function test_level_up_finished_for_knowledge(): void
    {
        $this->log('techtree.level_up_finished', [
            'entity_name' => 'knowledge_cartography', 'new_level' => 2,
        ]);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $segments = $entries->first()['segments'];

        $this->assertSame('knowledge', $segments[1]['type']);
        $this->assertStringContainsString('Kenntnis', $segments[0]['value']);
        $this->assertStringContainsString('Level 2 gestiegen', $segments[2]['value']);
    }

    public function test_level_up_finished_falls_back_to_tech_id_lookup(): void
    {
        // No entity_name/entity_type — resolved via tech_id against the building map.
        $this->log('techtree.level_up_finished', ['tech_id' => 25, 'new_level' => 3]);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $segments = $entries->first()['segments'];

        $this->assertSame('building', $segments[1]['type']);
        $this->assertSame(__('techtree.building_commandCenter'), $segments[1]['label']);
    }

    public function test_level_down_for_ship(): void
    {
        $this->log('techtree.level_down', ['entity_type' => 'ship', 'entity_name' => 'ship_scout']);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $segments = $entries->first()['segments'];

        $this->assertSame('ship', $segments[1]['type']);
        $this->assertStringContainsString('zerstört', $segments[2]['value']);
    }

    public function test_level_down_for_building(): void
    {
        $this->log('techtree.level_down', [
            'entity_type' => 'building', 'entity_name' => 'building_commandCenter', 'new_level' => 2,
        ]);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $segments = $entries->first()['segments'];

        $this->assertStringContainsString('auf 2 gesunken', $segments[2]['value']);
    }

    public function test_advisor_hired_with_cost(): void
    {
        $this->log('techtree.advisor_hired', ['advisor_type' => 'engineer', 'credits_cost' => 200]);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $segments = $entries->first()['segments'];

        $this->assertSame('advisor', $segments[0]['type']);
        $this->assertSame('amount', $segments[2]['type']);
        $this->assertSame('CR', $segments[2]['abbr']);
        $this->assertSame(200, $segments[2]['value']);
    }

    public function test_advisor_hired_without_cost(): void
    {
        $this->log('techtree.advisor_hired', ['advisor_type' => 'engineer', 'credits_cost' => 0]);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $segments = $entries->first()['segments'];

        $this->assertStringNotContainsString('CR', $segments[1]['value']);
    }

    // Regression (Owner-Playtest 2026-08-31): colony.passive_credits and
    // colony.stipend_purchased had no buildDescription() case at all — the log
    // rendered the raw event key ("colony.passive_credits") instead of a
    // translated line.
    public function test_passive_credits_description(): void
    {
        $this->log('colony.passive_credits', [
            'subsidy' => 30, 'relay_bonus' => 10, 'contract' => 0, 'total' => 40,
        ]);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $segments = $entries->first()['segments'];

        $this->assertNotEmpty($segments, 'must not fall back to the raw event key');
        $this->assertSame('amount', $segments[1]['type']);
        $this->assertSame('CR', $segments[1]['abbr']);
        $this->assertSame(40, $segments[1]['value']);
    }

    public function test_stipend_purchased_description(): void
    {
        $this->log('colony.stipend_purchased', ['tier' => 'medium', 'cost' => 150]);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $segments = $entries->first()['segments'];

        $this->assertNotEmpty($segments, 'must not fall back to the raw event key');
        $this->assertStringContainsString(__('colony.stipend_tier_medium'), $segments[0]['value']);
        $this->assertSame('amount', $segments[1]['type']);
        $this->assertSame(150, $segments[1]['value']);
    }

    // Regression: none of the other tests here force a full HTML render
    // (viewData() reads the View object before Blade compiles it) — this is
    // the only test that would have caught a Blade syntax error in the
    // amount-chip segment branch (Owner-Playtest 2026-08-31 follow-up).
    public function test_log_page_with_amount_chip_segment_renders_without_error(): void
    {
        $this->log('techtree.advisor_hired', ['advisor_type' => 'engineer', 'credits_cost' => 200]);

        $this->actingAs($this->user())
            ->get(route('comm.log'))
            ->assertOk()
            ->assertSee('200', false);
    }

    public function test_bar_accepted_with_resources(): void
    {
        $this->log('trade.bar_accepted', [
            'give_resource_id' => 3, 'give_amount' => 20,
            'get_resource_id' => 4, 'get_amount' => 5,
        ]);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $segments = $entries->first()['segments'];

        $this->assertSame('resource', $segments[1]['type']);
        $this->assertSame('Regolith', $segments[1]['label']);
    }

    public function test_bar_accepted_fallback_text(): void
    {
        $this->log('trade.bar_accepted', []);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $this->assertNotEmpty($entries->first()['segments']);
    }

    public function test_unmapped_event_yields_empty_segments(): void
    {
        $this->log('some.unmapped_event', ['foo' => 'bar']);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $this->assertSame([], $entries->first()['segments']);
    }

    // ── collapseEntries(): consecutive same-key events within a Sol ─────────

    public function test_consecutive_building_invested_entries_collapse(): void
    {
        $this->log('colony.building_invested', ['building_id' => 25, 'ap_spend' => 1], tick: 5);
        $this->log('colony.building_invested', ['building_id' => 25, 'ap_spend' => 2], tick: 5);
        $this->log('colony.building_invested', ['building_id' => 25, 'ap_spend' => 3], tick: 5);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');

        // Collapsed to one representative entry (newest first) with a _collapsed count.
        $this->assertCount(1, $entries);
        $this->assertSame(3, $entries->first()['_collapsed']);
    }

    public function test_building_invested_entries_for_different_buildings_do_not_collapse(): void
    {
        $this->log('colony.building_invested', ['building_id' => 25, 'ap_spend' => 1], tick: 5);
        $this->log('colony.building_invested', ['building_id' => 28, 'ap_spend' => 1], tick: 5);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');

        $this->assertCount(2, $entries);
    }

    public function test_non_collapsible_events_never_merge(): void
    {
        $this->log('colony.renamed', [], tick: 5);
        $this->log('colony.renamed', [], tick: 5);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');

        $this->assertCount(2, $entries);
    }

    // ── parseParams(): legacy serialized payload fallback ────────────────────

    public function test_legacy_php_serialized_parameters_are_parsed(): void
    {
        ColonyLog::create([
            'user' => self::USER_ID,
            'tick' => 5,
            'event' => 'colony.building_placed',
            'area' => 'colony',
            'parameters' => serialize(['building_id' => 25, 'building_name' => 'building_commandCenter']),
        ]);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $this->assertSame(__('techtree.building_commandCenter'), $entries->first()['segments'][0]['label']);
    }

    public function test_missing_parameters_yield_empty_params(): void
    {
        ColonyLog::create([
            'user' => self::USER_ID,
            'tick' => 5,
            'event' => 'colony.renamed',
            'area' => 'colony',
            'parameters' => '',
        ]);

        $entries = $this->actingAs($this->user())->get(route('comm.log'))->viewData('entries');
        $this->assertSame([], $entries->first()['params']);
    }
}
