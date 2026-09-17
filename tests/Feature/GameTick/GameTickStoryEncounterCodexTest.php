<?php

namespace Tests\Feature\GameTick;

/**
 * GameTick — Charakter-Kodex (A42) hookup for the story_hook Cantina
 * encounter roll (BarService::pickStoryEncounter(), A36). Deterministic,
 * stateless roll — GameTick calls it once per Sol per bar-equipped colony and
 * records codex progress for whichever story_hook figure it picked.
 */

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GameTickStoryEncounterCodexTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1; // Springfield — user_id = 3 (Bart)

    private const USER_ID = 3;

    private const BAR_BUILDING_ID = 52;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->update(['level' => 1, 'status_points' => 999]);
    }

    private function totalCodexEntriesFor(array $slugs): int
    {
        return DB::table('character_codex_entries')
            ->where('user_id', self::USER_ID)
            ->whereIn('character_slug', $slugs)
            ->count();
    }

    public function test_story_encounter_roll_records_codex_progress_for_the_picked_figure(): void
    {
        config(['game.bar.story_encounter.chance' => 1.0]);

        $this->artisan('game:tick', ['--tick' => 5000])->assertExitCode(0);

        $this->assertSame(1, $this->totalCodexEntriesFor(config('game.bar.story_encounter.slugs')));
    }

    public function test_no_story_encounter_roll_records_no_codex_progress(): void
    {
        config(['game.bar.story_encounter.chance' => 0.0]);

        $this->artisan('game:tick', ['--tick' => 5001])->assertExitCode(0);

        $this->assertSame(0, $this->totalCodexEntriesFor(config('game.bar.story_encounter.slugs')));
    }
}
