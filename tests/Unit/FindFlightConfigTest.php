<?php

namespace Tests\Unit;

use App\Support\HangarBays;
use Tests\TestCase;

/** T30 §13.3: carry matrix, flight yield and the catalog clean-up (E5, E8). */
class FindFlightConfigTest extends TestCase
{
    public function test_carry_matrix(): void
    {
        $this->assertSame(['find_small', 'find_medium'], config('ships.drone.find_carry'));
        $this->assertSame(['find_small', 'find_medium', 'find_large'], config('ships.freighter.find_carry'));
        $this->assertSame([], config('ships.corvette.find_carry'));
        $this->assertNull(config('game.finds.flight.yield_factor'), 'Flight yield = find Rg, no factor');
        $this->assertSame([1, 1], [config('game.finds.flight.nav_ap'), config('game.finds.flight.organika_per_sol')]);
    }

    /** min_hangar_level must equal the smallest class that carries the type — the lock reason must say what the guard checks. */
    public function test_min_hangar_level_matches_the_smallest_carrying_ship_class(): void
    {
        foreach (['find_small', 'find_medium', 'find_large'] as $type) {
            $levels = [];
            foreach (config('ships') as $cfg) {
                if (in_array($type, $cfg['find_carry'] ?? [], true)) {
                    $levels[] = HangarBays::requiredLevel((int) $cfg['id']);
                }
            }
            $this->assertNotEmpty($levels, "{$type}: no ship carries it");
            $this->assertSame(min($levels), config("game.finds.flight.min_hangar_level.{$type}"), $type);
        }
    }

    public function test_catalog_without_prospecting_survey_and_regolith_supply(): void
    {
        $catalog = config('missions.catalog');
        $this->assertArrayNotHasKey('mission_prospecting_flight', $catalog);
        $this->assertArrayNotHasKey('mission_deep_survey', $catalog);
        $this->assertSame(['organics' => 10], $catalog['mission_supply_run']['reward']);
        foreach ($catalog as $key => $mission) {
            $this->assertNotSame('signal_tile', $mission['target_type'] ?? null, $key);
            $this->assertArrayNotHasKey('deep_scan', $mission['reward'], $key);
        }
    }
}
