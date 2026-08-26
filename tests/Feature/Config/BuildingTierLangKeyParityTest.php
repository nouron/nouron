<?php

namespace Tests\Feature\Config;

use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

/**
 * Guards config/buildings.php `tiers` <-> lang/{de,en}/techtree.php `tier_*` key parity.
 *
 * ColonyController::resolveTierLabel() calls __("techtree.tier_{$key}_{$level}") for
 * every level listed in a building's `tiers` config array. If a future plan adds a
 * `tiers` entry without adding the matching lang key in both locales, the UI would
 * silently show the raw translation key instead of a name. This is a regression
 * guard for the 3 follow-up plans referenced in the design spec's "Offene Folge-
 * Tasks" (tradingPost/sciencelab/uplinkStation new mechanics).
 */
class BuildingTierLangKeyParityTest extends TestCase
{
    public function test_every_configured_tier_level_has_a_lang_key_in_both_locales(): void
    {
        $checked = 0;

        foreach (config('buildings') as $buildingKey => $cfg) {
            foreach ($cfg['tiers'] ?? [] as $level) {
                $langKey = "techtree.tier_{$buildingKey}_{$level}";

                $this->assertTrue(Lang::has($langKey, 'de'), "Missing lang key '{$langKey}' in lang/de/techtree.php");
                $this->assertTrue(Lang::has($langKey, 'en'), "Missing lang key '{$langKey}' in lang/en/techtree.php");
                $checked++;
            }
        }

        $this->assertGreaterThan(0, $checked, 'Expected at least one configured tiers level to check — config/buildings.php tiers arrays may be empty');
    }
}
