<?php

namespace Tests\Feature\Colony;

/**
 * Charakter-Kodex (A42) trigger hookup for Tomas (bartender, `permanent`
 * game_role) — his codex unlocks at Cantina-Ausbaustufen-Meilensteinen
 * (bar level 1 -> entry 1, ..., level 5 -> entry 5), not via a random
 * encounter — see ColonyController::investBuilding().
 */

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarLevelUpCodexHookupTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    private const BAR_ID = 52;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function codexEntryCount(): int
    {
        return DB::table('character_codex_entries')
            ->where('user_id', self::BART_USER_ID)
            ->where('character_slug', 'bartender')
            ->count();
    }

    public function test_bar_level_up_records_codex_progress_for_tomas(): void
    {
        $building = DB::table('buildings')->where('id', self::BAR_ID)->first();
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BAR_ID)
            ->update(['level' => 1, 'ap_spend' => (int) $building->ap_for_levelup - 1]);

        $response = $this->actingAs(User::where('user_id', self::BART_USER_ID)->firstOrFail())
            ->postJson(route('colony.building.invest'), ['building_id' => self::BAR_ID]);

        $response->assertOk()->assertJsonPath('leveled_up', true);
        $this->assertSame(1, $this->codexEntryCount());
    }

    public function test_bar_invest_without_levelup_records_no_codex_progress(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BAR_ID)
            ->update(['level' => 1, 'ap_spend' => 0]);

        $response = $this->actingAs(User::where('user_id', self::BART_USER_ID)->firstOrFail())
            ->postJson(route('colony.building.invest'), ['building_id' => self::BAR_ID]);

        $response->assertOk()->assertJsonPath('leveled_up', false);
        $this->assertSame(0, $this->codexEntryCount());
    }
}
