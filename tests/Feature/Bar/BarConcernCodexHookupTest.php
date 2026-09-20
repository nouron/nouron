<?php

namespace Tests\Feature\Bar;

/**
 * Charakter-Kodex (A42) trigger hookup for Charakter-Anliegen (A41) resolution
 * — ONLY for the 3 story_hook figures (founder/Aldra, preacher/Sorel,
 * stranger). The other 6 concern characters (smuggler/information_broker/
 * mechanic/doctor/prospector/mercenary) are bar_trade — their codex trigger is
 * the personalized-flavor guest trade (BarService::acceptOffer()), not the
 * concern mechanic, so resolving THEIR concern must NOT record codex progress.
 *
 * Covered scenarios:
 *  - test_resolve_founder_concern_records_codex_progress
 *  - test_resolve_preacher_concern_records_codex_progress
 *  - test_resolve_stranger_concern_records_codex_progress
 *  - test_resolve_doctor_concern_does_not_record_codex_progress
 */

use App\Services\BarService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarConcernCodexHookupTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const RES_COMPOUNDS = 4;

    private BarService $barService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->barService = $this->app->make(BarService::class);
    }

    private function insertConcern(array $overrides = []): int
    {
        $defaults = [
            'colony_id' => self::COLONY_ID,
            'character_slug' => 'mercenary',
            'created_tick' => 10,
            'expires_tick' => 20,
            'is_resolved' => false,
            'success' => null,
            'outcome' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return DB::table('bar_concerns')->insertGetId(array_merge($defaults, $overrides));
    }

    private function codexEntryCount(string $slug): int
    {
        return DB::table('character_codex_entries')
            ->where('user_id', self::USER_ID)
            ->where('character_slug', $slug)
            ->count();
    }

    public function test_resolve_founder_concern_records_codex_progress(): void
    {
        $id = $this->insertConcern(['character_slug' => 'founder']);

        $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertSame(1, $this->codexEntryCount('founder'));
    }

    public function test_resolve_preacher_concern_records_codex_progress(): void
    {
        $id = $this->insertConcern(['character_slug' => 'preacher']);

        $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertSame(1, $this->codexEntryCount('preacher'));
    }

    public function test_resolve_stranger_concern_records_codex_progress(): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_COMPOUNDS],
            ['amount' => 100]
        );
        $id = $this->insertConcern(['character_slug' => 'stranger']);

        $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertSame(1, $this->codexEntryCount('stranger'));
    }

    public function test_resolve_doctor_concern_does_not_record_codex_progress(): void
    {
        $id = $this->insertConcern(['character_slug' => 'doctor']);

        $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertSame(0, $this->codexEntryCount('doctor'));
    }
}
