<?php

namespace Tests\Feature\Colony;

use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Owner handtest 2026-10-17: after a Fehlalarm (find_false) the map kept showing the orange
 * event marker on the hex. The server is fine (has_signal is already false once sondiert);
 * the client treated every deep-scanned tile with an event_type as "active event".
 * Runs the real colony-hexgrid.js in a Node vm sandbox.
 */
class HexgridFalseAlarmDisplayTest extends TestCase
{
    private function evalInHexgrid(string $expr): mixed
    {
        $script = <<<'JS'
const vm = require('vm'), fs = require('fs');
const ctx = { console, document: { addEventListener() {}, createElementNS() { return { setAttribute() {}, appendChild() {} }; } } };
ctx.window = ctx;
vm.createContext(ctx);
vm.runInContext(fs.readFileSync(process.argv[1], 'utf8'), ctx);
console.log(JSON.stringify(vm.runInContext(process.argv[2], ctx)));
JS;
        $p = new Process(['node', '-e', $script, public_path('js/colony-hexgrid.js'), $expr]);
        $p->run();
        if (! $p->isSuccessful()) {
            $this->markTestSkipped('node unavailable or script failed: '.$p->getErrorOutput());
        }

        return json_decode(trim($p->getOutput()), true);
    }

    private const TILE = "{ q: 2, r: 1, is_explored: true, is_colony_zone: true, tile_type: 'terrain_empty', is_deep_scanned: true, ";

    public function test_false_alarm_tile_is_not_an_active_event(): void
    {
        $this->assertFalse($this->evalInHexgrid('hasActiveEvent('.self::TILE."event_type: 'find_false' })"));
    }

    public function test_real_find_and_other_events_stay_active(): void
    {
        $this->assertTrue($this->evalInHexgrid('hasActiveEvent('.self::TILE."event_type: 'find_small' })"));
        $this->assertTrue($this->evalInHexgrid('hasActiveEvent('.self::TILE."event_type: 'event_ruin' })"));
    }

    public function test_false_alarm_tile_uses_plain_terrain_colour_and_stroke(): void
    {
        $plain = $this->evalInHexgrid('[getTileColor('.self::TILE.'event_type: null }), getTileStroke('.self::TILE.'event_type: null }, false)]');
        $false = $this->evalInHexgrid('[getTileColor('.self::TILE."event_type: 'find_false' }), getTileStroke(".self::TILE."event_type: 'find_false' }, false)]");

        $this->assertSame($plain, $false);
    }
}
