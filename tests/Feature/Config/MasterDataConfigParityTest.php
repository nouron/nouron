<?php

namespace Tests\Feature\Config;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guards config <-> DB master-data parity after TestSeeder (ROADMAP T20).
 *
 * GameTick, ResourcesService and the build/level-up paths read decay_rate,
 * supply_cost, max_status_points, max_level and max_instances from the DB,
 * not from config. If the seeded test DB drifts from config/buildings.php,
 * config/ships.php or config/knowledge.php, every test and bot batch silently
 * runs against wrong values. Covers exactly the columns game:sync-config syncs.
 */
class MasterDataConfigParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_building_columns_match_config(): void
    {
        $mismatches = [];

        foreach (config('buildings') as $key => $cfg) {
            $row = DB::table('buildings')->where('id', $cfg['id'])->first();
            $this->assertNotNull($row, "buildings row id={$cfg['id']} ({$key}) missing");

            foreach (['decay_rate', 'supply_cost', 'max_status_points', 'max_level', 'max_instances'] as $col) {
                if (! array_key_exists($col, $cfg)) {
                    continue;
                }
                $mismatches = array_merge($mismatches, $this->compare("buildings.{$key}.{$col}", $cfg[$col], $row->$col));
            }
        }

        $this->assertSame([], $mismatches, "DB buildings drift from config/buildings.php:\n".implode("\n", $mismatches));
    }

    public function test_building_costs_match_config_build_cost(): void
    {
        $mismatches = [];

        foreach (config('buildings') as $key => $cfg) {
            $desired = [];
            foreach ($cfg['build_cost'] ?? [] as $resourceId => $amount) {
                if (in_array((int) $resourceId, [3, 4], true)) {
                    $desired[(int) $resourceId] = (int) $amount;
                }
            }
            ksort($desired);

            $actual = DB::table('building_costs')
                ->where('building_id', $cfg['id'])
                ->whereIn('resource_id', [3, 4])
                ->orderBy('resource_id')
                ->pluck('amount', 'resource_id')
                ->map(fn ($a) => (int) $a)
                ->toArray();

            if ($desired !== $actual) {
                $mismatches[] = "building_costs.{$key}: config ".json_encode($desired).' vs DB '.json_encode($actual);
            }
        }

        $this->assertSame([], $mismatches, implode("\n", $mismatches));
    }

    public function test_ship_columns_match_config(): void
    {
        $mismatches = [];

        foreach (config('ships') as $key => $cfg) {
            $row = DB::table('ships')->where('id', $cfg['id'])->first();
            $this->assertNotNull($row, "ships row id={$cfg['id']} ({$key}) missing");

            foreach (['moving_speed', 'decay_rate', 'supply_cost', 'max_status_points'] as $col) {
                if (! array_key_exists($col, $cfg)) {
                    continue;
                }
                $mismatches = array_merge($mismatches, $this->compare("ships.{$key}.{$col}", $cfg[$col], $row->$col));
            }
        }

        $this->assertSame([], $mismatches, "DB ships drift from config/ships.php:\n".implode("\n", $mismatches));
    }

    public function test_knowledge_columns_match_config(): void
    {
        $mismatches = [];

        foreach (config('knowledge') as $key => $cfg) {
            if (! is_array($cfg) || ! isset($cfg['id'])) {
                continue;
            }
            $row = DB::table('researches')->where('id', $cfg['id'])->first();
            $this->assertNotNull($row, "researches row id={$cfg['id']} ({$key}) missing");

            foreach (['decay_rate', 'supply_cost', 'max_status_points'] as $col) {
                if (! array_key_exists($col, $cfg)) {
                    continue;
                }
                $mismatches = array_merge($mismatches, $this->compare("knowledge.{$key}.{$col}", $cfg[$col], $row->$col));
            }
        }

        $this->assertSame([], $mismatches, "DB researches drift from config/knowledge.php:\n".implode("\n", $mismatches));
    }

    /**
     * @return list<string>
     */
    private function compare(string $label, mixed $expected, mixed $actual): array
    {
        if ($expected === null || $actual === null) {
            return $expected === $actual ? [] : ["{$label}: config ".var_export($expected, true).' vs DB '.var_export($actual, true)];
        }

        return abs((float) $expected - (float) $actual) < 1e-9
            ? []
            : ["{$label}: config {$expected} vs DB {$actual}"];
    }
}
