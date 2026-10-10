<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FindsConfigTest extends TestCase
{
    public function test_start_pool_is_seven_signals_worth_forty_regolith(): void
    {
        $pool = config('game.finds.start_pool');
        $types = config('game.finds.types');

        $this->assertSame(7, array_sum($pool));
        $rg = 0;
        foreach ($pool as $type => $count) {
            $rg += $count * $types[$type]['rg'];
        }
        $this->assertSame(40, $rg);
        $this->assertSame(['rg' => 18, 'ap' => 22], $types['find_large']);
        $this->assertSame(['rg' => 0, 'ap' => 0], $types['find_false']);
        $this->assertSame(2, config('game.finds.scan_ap'));
        $this->assertSame(1, config('game.finds.scan_ap_uplink'));
        $this->assertSame(4, config('game.finds.salvage_cap_per_sol'));
        $this->assertSame(2, config('game.finds.max_open_projects'));
    }

    public function test_colony_tiles_has_salvage_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('colony_tiles', ['salvage_ap_spent', 'salvage_tick']));
    }
}
