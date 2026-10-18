<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** tools/economy-model.php: book-keeping invariant (end stock = start + income - spending). */
class EconomyModelToolTest extends TestCase
{
    public function test_ledger_balances_for_every_opening(): void
    {
        $json = shell_exec('php '.escapeshellarg(dirname(__DIR__, 2).'/tools/economy-model.php').' --sols=30 --start=300 --json 2>/dev/null');
        $res = json_decode((string) $json, true);

        $this->assertSame(['labor', 'hangar', 'cantina'], array_keys($res));
        foreach ($res as $name => $r) {
            $in = array_sum($r['tot']['in']);
            $out = array_sum($r['tot']['out']);
            $this->assertEqualsWithDelta(300 + $in - $out, $r['rg'], 0.5, $name);
            $this->assertGreaterThan(0, $r['tot']['in']['harvester_base'], $name);
        }
    }
}
