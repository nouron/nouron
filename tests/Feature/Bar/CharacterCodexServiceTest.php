<?php

namespace Tests\Feature\Bar;

/**
 * CharacterCodexService::recordProgress() tests (Charakter-Kodex, GDD §12, A42).
 *
 * Pure lore/meta progression — no gameplay effect, user-persistent (survives
 * across runs).
 *
 * Covered scenarios:
 *  - test_first_call_unlocks_entry_1
 *  - test_repeated_calls_unlock_sequential_entries
 *  - test_stops_unlocking_once_pool_is_exhausted
 *  - test_tracks_characters_independently
 *  - test_tracks_users_independently
 *  - test_get_unlocked_entries_returns_empty_when_nothing_unlocked
 *  - test_get_unlocked_entries_returns_sorted_entry_numbers
 */

use App\Services\CharacterCodexService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CharacterCodexServiceTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3; // Bart

    private const OTHER_USER_ID = 4;

    private CharacterCodexService $codexService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->codexService = $this->app->make(CharacterCodexService::class);
    }

    private function unlockedEntries(int $userId, string $slug): array
    {
        return DB::table('character_codex_entries')
            ->where('user_id', $userId)
            ->where('character_slug', $slug)
            ->orderBy('entry_number')
            ->pluck('entry_number')
            ->all();
    }

    public function test_first_call_unlocks_entry_1(): void
    {
        $this->codexService->recordProgress(self::USER_ID, 'veteran');

        $this->assertSame([1], $this->unlockedEntries(self::USER_ID, 'veteran'));
    }

    public function test_repeated_calls_unlock_sequential_entries(): void
    {
        $this->codexService->recordProgress(self::USER_ID, 'veteran');
        $this->codexService->recordProgress(self::USER_ID, 'veteran');
        $this->codexService->recordProgress(self::USER_ID, 'veteran');

        $this->assertSame([1, 2, 3], $this->unlockedEntries(self::USER_ID, 'veteran'));
    }

    public function test_stops_unlocking_once_pool_is_exhausted(): void
    {
        $max = (int) config('game.character_codex.entries_per_character', 5);

        for ($i = 0; $i < $max + 5; $i++) {
            $this->codexService->recordProgress(self::USER_ID, 'veteran');
        }

        $this->assertSame(range(1, $max), $this->unlockedEntries(self::USER_ID, 'veteran'));
    }

    public function test_tracks_characters_independently(): void
    {
        $this->codexService->recordProgress(self::USER_ID, 'veteran');
        $this->codexService->recordProgress(self::USER_ID, 'ai_researcher');
        $this->codexService->recordProgress(self::USER_ID, 'ai_researcher');

        $this->assertSame([1], $this->unlockedEntries(self::USER_ID, 'veteran'));
        $this->assertSame([1, 2], $this->unlockedEntries(self::USER_ID, 'ai_researcher'));
    }

    public function test_tracks_users_independently(): void
    {
        $this->codexService->recordProgress(self::USER_ID, 'veteran');
        $this->codexService->recordProgress(self::USER_ID, 'veteran');

        $this->assertSame([1, 2], $this->unlockedEntries(self::USER_ID, 'veteran'));
        $this->assertSame([], $this->unlockedEntries(self::OTHER_USER_ID, 'veteran'));
    }

    public function test_get_unlocked_entries_returns_empty_when_nothing_unlocked(): void
    {
        $this->assertSame([], $this->codexService->getUnlockedEntries(self::USER_ID, 'veteran'));
    }

    public function test_get_unlocked_entries_returns_sorted_entry_numbers(): void
    {
        $this->codexService->recordProgress(self::USER_ID, 'veteran');
        $this->codexService->recordProgress(self::USER_ID, 'veteran');
        $this->codexService->recordProgress(self::USER_ID, 'veteran');

        $this->assertSame([1, 2, 3], $this->codexService->getUnlockedEntries(self::USER_ID, 'veteran'));
    }
}
