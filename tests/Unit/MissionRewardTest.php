<?php

namespace Tests\Unit;

use App\Support\MissionReward;
use Tests\TestCase;

/**
 * The reward text on a mission card is derived from config/missions.php, so the number the
 * player sees is the number that is paid out (the old hard-coded lang strings still showed the
 * pre-2026-09-30 amounts after the credit rewards were doubled).
 */
class MissionRewardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('de');
    }

    public function test_fixed_credits(): void
    {
        $this->assertSame('180 Credits', MissionReward::label(['credits' => 180]));
    }

    public function test_range_uses_an_en_dash(): void
    {
        $this->assertSame('20–30 Regolith', MissionReward::label(['regolith' => [20, 30]]));
    }

    public function test_several_parts_are_joined_with_plus(): void
    {
        $this->assertSame('25 Regolith + 10 Organika', MissionReward::label(['regolith' => 25, 'organics' => 10]));
    }

    public function test_trust_event_appends_trust(): void
    {
        $this->assertSame('520 Credits + Vertrauen', MissionReward::label(['credits' => 520, 'trust_event' => 'trade_success']));
    }

    public function test_compounds_are_called_werkstoffe(): void
    {
        $this->assertSame('6–10 Werkstoffe', MissionReward::label(['compounds' => [6, 10]]));
    }

    public function test_loot_table_lists_every_option_with_its_amount(): void
    {
        $label = MissionReward::label(['loot_table' => [
            ['credits' => [700, 1100]],
            ['compounds' => [8, 12]],
            ['regolith' => [30, 45]],
        ]]);

        $this->assertStringStartsWith('Zufallsfund:', $label);
        $this->assertStringContainsString('700–1100 Credits', $label);
        $this->assertStringContainsString('8–12 Werkstoffe', $label);
        $this->assertStringContainsString('30–45 Regolith', $label);
    }

    public function test_non_numeric_rewards(): void
    {
        $this->assertSame('2 Sektoren kartiert', MissionReward::label(['reveal_tiles' => 2]));
        $this->assertSame('Tiefenscan des Signals', MissionReward::label(['deep_scan' => 1]));
        $this->assertSame('+8 Forschungs-AP', MissionReward::label(['research_ap' => 8]));
        $this->assertSame('Geborgener Harvester (beschädigt)', MissionReward::label(['harvester_instance' => true]));
    }

    public function test_every_catalog_mission_shows_every_configured_amount(): void
    {
        foreach (config('missions.catalog') as $key => $mission) {
            $label = MissionReward::label($mission['reward']);

            $this->assertNotSame('', $label, $key);
            $this->assertStringNotContainsString(':amount', $label, $key);
            foreach (self::amountsIn($mission['reward']) as $amount) {
                $this->assertStringContainsString((string) $amount, $label, "{$key}: configured amount {$amount} missing in '{$label}'");
            }
        }
    }

    /** @return list<int> every number in a reward definition (ranges contribute min and max) */
    private static function amountsIn(array $reward): array
    {
        $numbers = [];
        foreach ($reward as $name => $value) {
            if ($name === 'loot_table') {
                foreach ($value as $option) {
                    $numbers = [...$numbers, ...self::amountsIn($option)];
                }
            } elseif (is_array($value)) {
                $numbers = [...$numbers, ...array_map('intval', $value)];
            } elseif (is_int($value) && ! in_array($name, ['deep_scan'], true)) {
                $numbers[] = $value;
            }
        }

        return $numbers;
    }
}
