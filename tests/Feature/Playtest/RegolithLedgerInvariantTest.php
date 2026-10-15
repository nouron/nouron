<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regolith ledger (budget spec E.1 M1): a short real bot run must explain every
 * Regolith change. Per Sol: Σ inflows − Σ outflows == stock change since the
 * previous snapshot (start stock for the first), nothing left unattributed and
 * no untagged booking — a booking site without a tag, or one that bypasses the
 * recorder, turns this red.
 */
class RegolithLedgerInvariantTest extends TestCase
{
    use PlaysSolLoop;
    use RefreshDatabase;

    public static function openings(): array
    {
        return ['cantina' => ['cantina'], 'hangar' => ['hangar']];
    }

    #[DataProvider('openings')]
    public function test_ledger_balances_every_sol_of_a_short_bot_run(string $opening): void
    {
        $profile = BotProfile::named('default')->withOpening($opening);
        $bot = BotSession::boot($this, 3);
        $report = new RunReport(3, $profile->name, $profile->opening, $profile->engine);

        $this->playSolsUntil($bot, BotEngine::rules($profile), self::stopAtSol(20), afterAction: fn (BotSession $b) => $report->snapshot($b));
        $data = $report->build($bot, 20);

        $ledger = $data['regolith_ledger'];
        $this->assertNotEmpty($ledger['per_sol']);
        $previous = $ledger['start_stock'];
        foreach ($ledger['per_sol'] as $i => $row) {
            $in = array_sum($row['in']);
            $out = array_sum($row['out']);
            $this->assertSame($row['stock'] - $previous, $in - $out, "Sol {$row['sol']}: in {$in} − out {$out} must equal the stock change ".json_encode($row));
            $this->assertSame(0, $row['unattributed'], "Sol {$row['sol']}");
            $this->assertArrayNotHasKey('untagged', $row['in'], "Sol {$row['sol']}");
            $this->assertArrayNotHasKey('untagged', $row['out'], "Sol {$row['sol']}");
            $this->assertSame($in, array_sum($data['sols'][$i]['regolith_sources']), "Sol {$row['sol']}: regolith_sources must add up to the ledger inflow");
            $previous = $row['stock'];
        }

        $this->assertSame(array_sum($ledger['sources']) - array_sum($ledger['sinks']), $previous - $ledger['start_stock']);
        $this->assertGreaterThan(0, $ledger['sources']['harvester_base'] ?? 0);
        $this->assertGreaterThan(0, $ledger['sinks']['build_place'] ?? 0);
        $this->assertGreaterThan(0, ($ledger['sinks']['build_levelup'] ?? 0) + ($ledger['sinks']['cc_upgrade'] ?? 0));
    }
}
