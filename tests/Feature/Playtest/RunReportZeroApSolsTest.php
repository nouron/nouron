<?php

namespace Tests\Feature\Playtest;

/**
 * B2c (docs/playtest-instrumentation-plan.md, Metrik 8): RunReport::build()
 * must report how many Sols ended with the colony's shared AP pool fully
 * spent (ap_unspent <= 0), plus which Sol numbers those were. Owner decision
 * F8/2: no domain breakdown — AP domains no longer exist post pool
 * consolidation (GDD §13.1), a single count + Sol list is all the plan asks
 * for.
 *
 * Forces ap_unspent to 0 the same way the game itself would exhaust the pool:
 * lockActionPoints() against locked_actionpoints for the tick under test,
 * rather than faking the snapshot's numbers directly — this way the test
 * exercises the exact AdvisorService::getAvailableActionPoints() read that
 * RunReport::snapshot() performs.
 */
use App\Services\AdvisorService;
use App\Services\TickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunReportZeroApSolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_ap_sols_lists_only_sols_with_no_ap_left(): void
    {
        $bot = BotSession::boot($this, seed: 1);
        $report = new RunReport(seed: 1);
        $tickService = app(TickService::class);
        $advisorService = app(AdvisorService::class);

        // Sol 1: exhaust the pool for tick 1 by locking everything available.
        $tickService->setTickCount(1);
        $bot->sol = 1;
        $available = $advisorService->getAvailableActionPoints($bot->colonyId);
        $advisorService->lockActionPoints($bot->colonyId, $available);
        $this->assertSame(0, $advisorService->getAvailableActionPoints($bot->colonyId));
        $report->snapshot($bot);

        // Sol 2: a different tick, nothing locked — AP is left over.
        $tickService->setTickCount(2);
        $bot->sol = 2;
        $this->assertGreaterThan(0, $advisorService->getAvailableActionPoints($bot->colonyId));
        $report->snapshot($bot);

        // Sol 3: exhaust the pool again for tick 3.
        $tickService->setTickCount(3);
        $bot->sol = 3;
        $available = $advisorService->getAvailableActionPoints($bot->colonyId);
        $advisorService->lockActionPoints($bot->colonyId, $available);
        $this->assertSame(0, $advisorService->getAvailableActionPoints($bot->colonyId));
        $report->snapshot($bot);

        $data = $report->build($bot);

        $this->assertArrayHasKey('zero_ap_sols', $data);
        $this->assertSame(2, $data['zero_ap_sols']['count']);
        $this->assertSame([1, 3], $data['zero_ap_sols']['sols']);
        $this->assertLessThanOrEqual(count($data['sols']), $data['zero_ap_sols']['count']);
    }

    public function test_zero_ap_sols_is_empty_when_no_sol_ran_out_of_ap(): void
    {
        $bot = BotSession::boot($this, seed: 2);
        $report = new RunReport(seed: 2);

        $bot->sol = 1;
        $report->snapshot($bot);
        $bot->sol = 2;
        $report->snapshot($bot);

        $data = $report->build($bot);

        $this->assertSame(0, $data['zero_ap_sols']['count']);
        $this->assertSame([], $data['zero_ap_sols']['sols']);
    }
}
