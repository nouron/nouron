<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T30 Pool v1: Regolith credited by a completed salvage (rule invest_find) is
 * its own source 'find' in regolith_sources — not 'harvester', not 'trade'.
 */
class RunReportFindSourceTest extends TestCase
{
    use RefreshDatabase;

    private function pushEntry(BotSession $bot, string $rule, int $before, int $after, bool $ok = true): void
    {
        $bot->log[] = [
            'sol' => $bot->sol, 'rule' => $rule, 'url' => '', 'status' => $ok ? 200 : 422, 'ok' => $ok,
            'error' => $ok ? null : 'rejected', 'ap_before' => 10, 'ap_after' => 10,
            'regolith_before' => $before, 'regolith_after' => $after, 'organics_before' => 0, 'organics_after' => 0,
        ];
    }

    private function sources(BotSession $bot): array
    {
        $report = new RunReport(1);
        $report->snapshot($bot);

        return $report->build($bot)['sols'][0]['regolith_sources'];
    }

    public function test_completed_salvage_delta_counts_as_find(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->pushEntry($bot, 'invest_find', 100, 110);
        $this->pushEntry($bot, 'invest_find', 110, 110);

        $sources = $this->sources($bot);

        $this->assertSame(10, $sources['find']);
        $this->assertSame(0, $sources['harvester']);
        $this->assertSame(0, $sources['trade']);
    }

    public function test_find_defaults_to_zero_and_ignores_rejected_entries(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->pushEntry($bot, 'invest_find', 100, 118, ok: false);

        $this->assertSame(0, $this->sources($bot)['find']);
    }

    public function test_find_does_not_leak_into_the_path_attribution(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->pushEntry($bot, 'invest_find', 100, 104);
        $report = new RunReport(1);
        $report->snapshot($bot);

        $attr = $report->build($bot)['regolith_path_attribution'] ?? null;

        $this->assertNotNull($attr);
        $this->assertSame(0, array_sum($attr));
    }
}
