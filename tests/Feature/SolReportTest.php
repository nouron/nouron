<?php

namespace Tests\Feature;

/**
 * SolReportTest — covers SolReportService (snapshot/buildReport) + the SolController
 * HTTP endpoints that drive the end-of-Sol transition screen.
 *
 * Scenarios:
 *   1. Service base shape for an active run (groups, sol counters, status, finale/url null).
 *   2. Production delta: a grown resource yields a "+N" good line with from < to.
 *   3. Level-down beat: a techtree.level_down event produces a danger+beat decay line, force_show.
 *   4. Wear without level-down: lost status → single neutral decay line; no change → no decay group.
 *   5. Trust/credits lines: colony group always carries trust + credits with correct signs/tones.
 *   6. Finale on run end: win/lose finale, result_url set, force_show, run group replaced.
 *   7. HTTP: POST sol.next returns report JSON + increments runs.current_tick.
 *   8. Skip-pref toggle: POST sol.report-skip persists the pref; buildReport echoes skip_pref.
 *
 * Fixture: Bart (user_id=3) owns colony_id=1 (Springfield) with a seeded active run (id=1).
 * Resource IDs: 3=Regolith, 12=Trust. Credits/supply live in user_resources columns.
 */

use App\Models\Run;
use App\Models\User;
use App\Services\SolReportService;
use Database\Seeders\TestSeeder;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SolReportTest extends TestCase
{
    use RefreshDatabase;

    private const BART_ID = 3;

    private const COLONY_ID = 1;

    private const RES_REGOLITH = 3;

    private const RES_TRUST = 12;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function bart(): User
    {
        return User::find(self::BART_ID);
    }

    private function service(): SolReportService
    {
        return $this->app->make(SolReportService::class);
    }

    private function setRunTick(int $tick): Run
    {
        $run = Run::where('user_id', self::BART_ID)->where('status', 'active')->firstOrFail();
        $run->update(['current_tick' => $tick]);

        return $run->refresh();
    }

    private function snapshot(Run $run): array
    {
        return $this->service()->snapshot(
            (int) $run->colony_id,
            (int) $run->user_id,
            (int) $run->phase,
        );
    }

    private function setResource(int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['resource_id' => $resourceId, 'colony_id' => self::COLONY_ID],
            ['amount' => $amount],
        );
    }

    private function groupByKey(array $report, string $key): ?array
    {
        foreach ($report['groups'] as $group) {
            if ($group['key'] === $key) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Replace game:tick with a no-op so the HTTP layer can be exercised without
     * running the full tick pipeline. Artisan::fake() is unavailable in this stack.
     */
    private function fakeGameTick(): void
    {
        $this->app->make(Kernel::class)->registerCommand(
            new class extends Command
            {
                protected $signature = 'game:tick {--run=} {--tick=}';

                protected $description = 'No-op stub for SolReportTest';

                public function handle(): int
                {
                    return self::SUCCESS;
                }
            }
        );
    }

    public function test_build_report_base_shape_for_active_run(): void
    {
        $run = $this->setRunTick(1);
        $before = $this->snapshot($run);

        $report = $this->service()->buildReport($run, $before, false);

        $this->assertSame(1, $report['completed_sol']);
        $this->assertSame(2, $report['next_sol']);
        $this->assertSame('active', $report['run_status']);
        $this->assertNull($report['finale']);
        $this->assertNull($report['result_url']);
        $this->assertFalse($report['force_show']);

        $keys = array_column($report['groups'], 'key');
        $this->assertContains('production', $keys);
        $this->assertContains('colony', $keys);
        $this->assertContains('run', $keys);
    }

    public function test_production_group_shows_resource_growth(): void
    {
        $run = $this->setRunTick(1);

        // Seed a low "before" value, then write the higher "after" value into the DB.
        $this->setResource(self::RES_REGOLITH, 100);
        $before = $this->snapshot($run);
        $this->setResource(self::RES_REGOLITH, 142);

        $report = $this->service()->buildReport($run, $before, false);
        $production = $this->groupByKey($report, 'production');
        $this->assertNotNull($production);

        $regolithLine = collect($production['lines'])
            ->first(fn ($line) => ($line['from'] ?? null) === 100);

        $this->assertNotNull($regolithLine, 'Expected a production line for the grown regolith resource');
        $this->assertSame(100, $regolithLine['from']);
        $this->assertSame(142, $regolithLine['to']);
        $this->assertLessThan($regolithLine['to'], $regolithLine['from']);
        $this->assertStringStartsWith('+', $regolithLine['detail']);
        $this->assertSame('good', $regolithLine['tone']);
    }

    public function test_level_down_event_produces_danger_beat_and_forces_show(): void
    {
        $run = $this->setRunTick(7);
        $before = $this->snapshot($run);

        DB::table('colony_log')->insert([
            'user' => self::BART_ID,
            'tick' => 7,
            'event' => 'techtree.level_down',
            'area' => 'techtree',
            'parameters' => json_encode([
                'entity_type' => 'building',
                'entity_name' => 'commandCenter',
                'new_level' => 1,
            ]),
            'created_at' => now(),
            'is_read' => 1,
        ]);

        $report = $this->service()->buildReport($run, $before, false);

        $decay = $this->groupByKey($report, 'decay');
        $this->assertNotNull($decay, 'Expected a decay group when a level-down occurred');
        $this->assertCount(1, $decay['lines']);
        $this->assertSame('danger', $decay['lines'][0]['tone']);
        $this->assertTrue($decay['lines'][0]['beat']);
        $this->assertTrue($report['force_show']);
    }

    public function test_stipend_purchase_shows_in_events_group(): void
    {
        $run = $this->setRunTick(7);
        $before = $this->snapshot($run);

        DB::table('colony_log')->insert([
            'user' => self::BART_ID,
            'tick' => 7,
            'event' => 'colony.stipend_purchased',
            'area' => 'colony',
            'parameters' => json_encode(['colony_id' => self::COLONY_ID, 'tier' => 'medium', 'cost' => 300]),
            'created_at' => now(),
            'is_read' => 1,
        ]);

        $report = $this->service()->buildReport($run, $before, false);

        $events = $this->groupByKey($report, 'events');
        $this->assertNotNull($events, 'Expected an events group when a stipend was purchased');
        $line = collect($events['lines'])->first(fn ($l) => $l['label'] === __('colony.sol_report_stipend'));
        $this->assertNotNull($line, 'Expected a stipend line in the events group');
        $this->assertSame('good', $line['tone']);
        $this->assertStringContainsString('300', $line['detail']);
        $this->assertStringContainsString((string) config('game.trust.events.stipend_medium'), $line['detail']);
    }

    public function test_advisor_hire_shows_cost_in_events_group(): void
    {
        // Playtest finding (2026-07-14): hiring the Baumeister on Sol 1 left no
        // trace of the -300 Cr cost anywhere in the Sol-Report.
        $run = $this->setRunTick(7);
        $before = $this->snapshot($run);

        DB::table('colony_log')->insert([
            'user' => self::BART_ID,
            'tick' => 7,
            'event' => 'techtree.advisor_hired',
            'area' => 'techtree',
            'parameters' => json_encode(['colony_id' => self::COLONY_ID, 'advisor_type' => 'engineer', 'credits_cost' => 300]),
            'created_at' => now(),
            'is_read' => 1,
        ]);

        $report = $this->service()->buildReport($run, $before, false);

        $events = $this->groupByKey($report, 'events');
        $this->assertNotNull($events, 'Expected an events group when an advisor was hired');
        $line = collect($events['lines'])->first(fn ($l) => $l['label'] === __('colony.sol_report_advisor_hired'));
        $this->assertNotNull($line, 'Expected an advisor-hired line in the events group');
        $this->assertStringContainsString('300', $line['detail']);
        $this->assertStringContainsString(__('advisors.engineer'), $line['detail']);
    }

    public function test_wear_without_level_down_shows_single_neutral_line(): void
    {
        $run = $this->setRunTick(3);
        $before = $this->snapshot($run);

        // No level_down event; one building lost status points after the tick.
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', 25)
            ->update(['status_points' => DB::raw('status_points - 5')]);

        $report = $this->service()->buildReport($run, $before, false);

        $decay = $this->groupByKey($report, 'decay');
        $this->assertNotNull($decay, 'Expected a decay group when a building lost status');
        $this->assertCount(1, $decay['lines']);
        $this->assertSame('neutral', $decay['lines'][0]['tone']);
        $this->assertFalse($decay['lines'][0]['beat']);
        $this->assertFalse($report['force_show']);
    }

    public function test_no_decay_when_nothing_changed(): void
    {
        $run = $this->setRunTick(3);
        $before = $this->snapshot($run);

        // No event, no status loss → decay group must be omitted entirely.
        $report = $this->service()->buildReport($run, $before, false);

        $this->assertNull($this->groupByKey($report, 'decay'));
    }

    public function test_colony_group_carries_trust_and_credits_lines(): void
    {
        $run = $this->setRunTick(4);

        // Before: trust 50, credits 2700 (seeded).
        $this->setResource(self::RES_TRUST, 50);
        $before = $this->snapshot($run);

        // After: trust drops to 38 (-12), credits unchanged.
        $this->setResource(self::RES_TRUST, 38);

        $report = $this->service()->buildReport($run, $before, false);
        $colony = $this->groupByKey($report, 'colony');
        $this->assertNotNull($colony);

        $trustLine = collect($colony['lines'])->first(fn ($l) => ($l['from'] ?? null) === 50);
        $this->assertNotNull($trustLine, 'Expected a trust line in the colony group');
        $this->assertSame(50, $trustLine['from']);
        $this->assertSame(38, $trustLine['to']);
        $this->assertStringStartsWith('-', $trustLine['detail']);
        $this->assertSame('danger', $trustLine['tone']);

        $creditsLine = collect($colony['lines'])
            ->first(fn ($l) => ($l['from'] ?? null) === (int) $before['credits']
                && str_ends_with($l['detail'], 'Cr'));
        $this->assertNotNull($creditsLine, 'Expected a credits line in the colony group');
        $this->assertSame('neutral', $creditsLine['tone']);
    }

    public function test_passive_credits_shows_breakdown_in_colony_group(): void
    {
        $run = $this->setRunTick(7);
        $before = $this->snapshot($run);

        DB::table('colony_log')->insert([
            'user' => self::BART_ID,
            'tick' => 7,
            'event' => 'colony.passive_credits',
            'area' => 'colony',
            'parameters' => json_encode(['colony_id' => self::COLONY_ID, 'subsidy' => 30, 'housing_tax' => 40, 'total' => 70]),
            'created_at' => now(),
            'is_read' => 1,
        ]);

        $report = $this->service()->buildReport($run, $before, false);
        $colony = $this->groupByKey($report, 'colony');

        $line = collect($colony['lines'])->first(fn ($l) => $l['label'] === __('colony.sol_report_passive_credits'));
        $this->assertNotNull($line, 'Expected a passive-credits breakdown line in the colony group');
        $this->assertSame('+70 Cr', $line['detail']);
        $this->assertSame('good', $line['tone']);
    }

    public function test_finale_on_completed_run_replaces_run_group(): void
    {
        $run = $this->setRunTick(10);
        $before = $this->snapshot($run);
        $run->update(['status' => 'completed']);
        $run->refresh();

        $report = $this->service()->buildReport($run, $before, false);

        $this->assertNotNull($report['finale']);
        $this->assertSame('win', $report['finale']['outcome']);
        $this->assertNotNull($report['result_url']);
        $this->assertStringContainsString('/run/'.$run->id.'/result', $report['result_url']);
        $this->assertTrue($report['force_show']);
        $this->assertNull($this->groupByKey($report, 'run'), 'Run group must be replaced by the finale');
    }

    public function test_finale_on_failed_run_uses_trust_collapse_body(): void
    {
        $run = $this->setRunTick(10);
        $before = $this->snapshot($run);
        $run->update(['status' => 'failed', 'fail_reason' => 'trust_collapse']);
        $run->refresh();

        $report = $this->service()->buildReport($run, $before, false);

        $this->assertNotNull($report['finale']);
        $this->assertSame('lose', $report['finale']['outcome']);
        $this->assertSame(__('run.run_failed_trust'), $report['finale']['body']);
        $this->assertNotNull($report['result_url']);
        $this->assertTrue($report['force_show']);
        $this->assertNull($this->groupByKey($report, 'run'));
    }

    public function test_sol_next_endpoint_returns_report_and_increments_tick(): void
    {
        $this->fakeGameTick();
        $run = $this->setRunTick(2);

        $response = $this->actingAs($this->bart())->postJson(route('sol.next'));

        $response->assertOk();
        $response->assertJsonStructure([
            'completed_sol',
            'next_sol',
            'run_status',
            'groups',
            'skip_pref',
            'force_show',
        ]);

        $this->assertDatabaseHas('runs', ['id' => $run->id, 'current_tick' => 3]);
    }

    public function test_report_skip_endpoint_persists_preference(): void
    {
        $response = $this->actingAs($this->bart())->postJson(route('sol.report-skip'), ['skip' => true]);

        $response->assertOk();
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => self::BART_ID,
            'sol_report_skip' => 1,
        ]);

        $run = $this->setRunTick(1);
        $before = $this->snapshot($run);
        $report = $this->service()->buildReport($run, $before, true);

        $this->assertTrue($report['skip_pref']);
    }
}
