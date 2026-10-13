<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Spec 2.10/2.11: the report carries the decision log and the K18/K20 summary. */
class RunReportUtilitySummaryTest extends TestCase
{
    use RefreshDatabase;

    private static function d(int $sol, string $source, bool $ok = true): array
    {
        return ['sol' => $sol, 'source' => $source, 'name' => 'x', 'key' => 'x', 'band' => 1, 'score' => null, 'net' => null,
            'terms' => [], 'alternatives' => [], 'candidates_n' => 1, 'ok' => $ok];
    }

    public function test_legacy_run_has_no_summary(): void
    {
        $bot = BotSession::boot($this, 7);

        $data = (new RunReport(7))->build($bot);

        $this->assertSame([], $data['decisions']);
        $this->assertNull($data['utility_summary']);
    }

    public function test_summary_counts_sources_and_median_decisions_per_sol(): void
    {
        $bot = BotSession::boot($this, 7);
        $bot->sol = 3;
        $bot->decisions = [self::d(0, 'legacy'), self::d(0, 'utility'), self::d(1, 'mandatory'), self::d(1, 'legacy', false)];

        $summary = (new RunReport(7, engine: 'utility'))->build($bot)['utility_summary'];

        $this->assertSame(4, $summary['decisions']);
        $this->assertSame(50.0, $summary['legacy_share']);
        $this->assertSame(25.0, $summary['mandatory_share']);
        $this->assertSame(25.0, $summary['utility_share']);
        // ok decisions per Sol 0..2: [2, 1, 0] -> median 1
        $this->assertSame(1.0, $summary['median_decisions_per_sol']);
    }

    public function test_shadow_divergence_is_the_share_of_scored_decisions_the_utility_would_change(): void
    {
        $bot = BotSession::boot($this, 7);
        $bot->sol = 1;
        $same = self::d(0, 'legacy') + ['shadow' => ['name' => 'x', 'key' => 'x', 'score' => 100]];
        $other = self::d(0, 'legacy') + ['shadow' => ['name' => 'y', 'key' => 'y', 'score' => 300]];
        $none = self::d(0, 'legacy') + ['shadow' => null];
        $bot->decisions = [$same, $other, $other, $none];

        $this->assertEqualsWithDelta(66.7, (new RunReport(7, engine: 'shadow'))->build($bot)['utility_summary']['shadow_divergence'], 0.05);
    }

    public function test_shadow_free_divergence_is_reported_next_to_the_band_faithful_k22(): void
    {
        $bot = BotSession::boot($this, 7);
        $bot->sol = 1;
        $sameBoth = self::d(0, 'legacy') + ['shadow' => ['name' => 'x', 'key' => 'x', 'score' => 100, 'free' => ['name' => 'x', 'key' => 'x', 'score' => 100]]];
        $freeOther = self::d(0, 'legacy') + ['shadow' => ['name' => 'x', 'key' => 'x', 'score' => 100, 'free' => ['name' => 'y', 'key' => 'y', 'score' => 900]]];
        $keyOnly = self::d(0, 'legacy') + ['shadow' => ['name' => 'x', 'key' => 'x2', 'score' => 100, 'free' => null]];
        $bot->decisions = [$sameBoth, $freeOther, $keyOnly];

        $summary = (new RunReport(7, engine: 'shadow'))->build($bot)['utility_summary'];

        $this->assertEqualsWithDelta(33.3, $summary['shadow_divergence'], 0.05);
        $this->assertSame(50.0, $summary['shadow_free_divergence']);
    }
}
