<?php

namespace Tests\Feature\GameTick;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sol-1 Harvester economics under the §4c depletion mechanic (2026-08-03).
 *
 * The bootstrap Harvester is seeded on tile (1,0), a colony-zone tile that is
 * deliberately terrain_empty (colony-zone tiles are never regolith, see
 * ColonyTileService::computeColonyZoneCoords()). Since the depletion mechanic
 * requires the Harvester's actual tile to be a regolith_* deposit, this is a
 * real pacing change from the old level-curve mechanic (which produced
 * regardless of tile type): the Sol-1 Harvester produces ZERO Regolith until
 * the player relocates it — the exact action onboarding's hint_2
 * (OnboardingHintService::checkHint2()) already teaches. Confirmed intentional
 * by cross-checking against OnboardingE2ETest's existing hint_2 flow, not
 * assumed — flag this pacing shift to game-designer/owner if it wasn't
 * expected to bite this hard (0 income, not just reduced income, until move).
 */
class HarvesterSol1BootstrapTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const RES_REGOLITH = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        DB::table('runs')->where('user_id', self::USER_ID)->delete();
        DB::table('user_resources')->updateOrInsert(
            ['user_id' => self::USER_ID],
            ['credits' => 10000, 'supply' => 20]
        );

        // Isolate production from the Organika sink and trust drift.
        config(['game.food.supply_per_eater' => PHP_INT_MAX]);
        DB::table('trust_events')->where('colony_id', self::COLONY_ID)->delete();
    }

    private function user(): User
    {
        return User::where('user_id', self::USER_ID)->firstOrFail();
    }

    private function regolithAmount(): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)->where('resource_id', self::RES_REGOLITH)
            ->value('amount');
    }

    public function test_sol1_seeded_harvester_produces_nothing_before_relocation(): void
    {
        $this->actingAs($this->user())->post(route('run.new'))->assertRedirect();

        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => 12],
            ['amount' => 0]
        );

        $before = $this->regolithAmount();
        Artisan::call('game:tick', ['--tick' => 30001]);

        $this->assertSame(
            $before,
            $this->regolithAmount(),
            'The Sol-1 Harvester sits on a colony-zone (terrain_empty) tile by design and must not produce Regolith before relocation'
        );
    }

    public function test_harvester_produces_after_relocating_to_the_preexplored_regolith_tile(): void
    {
        $this->actingAs($this->user())->post(route('run.new'))->assertRedirect();

        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => 12],
            ['amount' => 0]
        );

        // Same lookup OnboardingE2ETest uses: the guaranteed pre-explored ring-3
        // regolith tile (Nexus-Scout Harvester relocation target).
        $target = DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)
            ->where('is_explored', 1)
            ->where('tile_type', 'like', 'regolith_%')
            ->first();
        $this->assertNotNull($target, 'Sol-1 seeding must guarantee one pre-explored regolith tile');

        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 27)
            ->update(['tile_x' => $target->q, 'tile_y' => $target->r, 'pending_until_tick' => null]);

        $before = $this->regolithAmount();
        Artisan::call('game:tick', ['--tick' => 30002]);

        $this->assertGreaterThan(
            $before,
            $this->regolithAmount(),
            'Once relocated to its regolith tile, the Sol-1 Harvester must produce Regolith'
        );
    }
}
