<?php

namespace Tests\Feature\Config;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guards config/buildings.php <-> DB `buildings` table `max_level` parity.
 *
 * Every enforcement path (ColonyController::investBuilding, AbstractTechnologyService,
 * ...) reads `max_level` from the `buildings` DB table, NOT from config/buildings.php.
 * The config is only synced into the DB via `php artisan game:sync-config`, and the
 * checked-in seed file `data/sql/testdata.sqlite.sql` must be kept in step by hand.
 * This test catches drift between the two the moment a future config change forgets
 * to also update the seed row (see 2026-08-26 whole-branch review finding #1).
 */
class BuildingMaxLevelParityTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, int> building config key => buildings.id */
    private const BUILDING_IDS = [
        'housingComplex' => 28,
        'bioFacility' => 41,
        'infirmary' => 46,
        'bar' => 52,
        'sciencelab' => 31,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_db_max_level_matches_config_for_all_five_corrected_buildings(): void
    {
        foreach (self::BUILDING_IDS as $key => $buildingId) {
            $dbMaxLevel = DB::table('buildings')->where('id', $buildingId)->value('max_level');
            $configMaxLevel = config("buildings.{$key}.max_level");

            $this->assertSame(
                $configMaxLevel,
                $dbMaxLevel === null ? null : (int) $dbMaxLevel,
                "DB max_level for building_id={$buildingId} ({$key}) must match config('buildings.{$key}.max_level')"
            );
        }
    }
}
