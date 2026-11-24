<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use Tests\TestCase;

/**
 * M3 2026-11-24: the bot stopped at two housing complexes (tile reserve for trust buildings,
 * A37-Rest), so with every colonist employed no building could level any further and
 * "Ingenieursleistung" stuck at 21–24 of 35. Once the colonists are the blocker, more
 * housing is the way out.
 */
class BotStrategyHousingCapTest extends TestCase
{
    private const ROWS = [
        ['building_id' => 28, 'locked' => false],
        ['building_id' => 54, 'locked' => false],
    ];

    public function test_two_housing_complexes_stay_the_cap_while_colonists_are_free(): void
    {
        $ordered = BotStrategy::orderPlacementCandidates(self::ROWS, [41 => 1, 28 => 2], 'auto');

        $this->assertNotContains(BuildingId::Housing->value, array_column($ordered, 'building_id'));
    }

    public function test_more_housing_is_a_candidate_once_the_colonists_run_out(): void
    {
        $ordered = BotStrategy::orderPlacementCandidates(self::ROWS, [41 => 1, 28 => 2], 'auto', colonistsExhausted: true);

        $this->assertContains(BuildingId::Housing->value, array_column($ordered, 'building_id'));
    }
}
