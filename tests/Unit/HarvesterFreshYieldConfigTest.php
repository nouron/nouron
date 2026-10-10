<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pins the harvester base yield per yield tier (T30 spec section 11, owner
 * decision 2026-10-09: 16/23/30 -> 18/26/34). resource_max stays untouched.
 */
class HarvesterFreshYieldConfigTest extends TestCase
{
    /** @return array<string, array{string, int}> */
    public static function tiles(): array
    {
        return [
            'y1_d1' => ['regolith_y1_d1', 18], 'y1_d2' => ['regolith_y1_d2', 18], 'y1_d3' => ['regolith_y1_d3', 18],
            'y2_d1' => ['regolith_y2_d1', 26], 'y2_d2' => ['regolith_y2_d2', 26], 'y2_d3' => ['regolith_y2_d3', 26],
            'y3_d1' => ['regolith_y3_d1', 34], 'y3_d2' => ['regolith_y3_d2', 34],
        ];
    }

    #[DataProvider('tiles')]
    public function test_fresh_yield_per_tile_type(string $tile, int $expected): void
    {
        $this->assertSame($expected, config("game.harvester.fresh_yield.$tile"));
    }

    public function test_resource_max_unchanged(): void
    {
        $this->assertSame(240, config('game.harvester.resource_max.regolith_y2_d1'));
        $this->assertSame(450, config('game.harvester.resource_max.regolith_y2_d2'));
        $this->assertSame(660, config('game.harvester.resource_max.regolith_y2_d3'));
    }
}
