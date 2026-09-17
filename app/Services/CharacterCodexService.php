<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Charakter-Kodex (GDD §12, A42) — a pure lore/meta progression track for the
 * whole Cantina cast, with no gameplay effect. Unlike everything else in the
 * Cantina system, this is USER-persistent, not colony-/run-scoped: entries
 * unlocked in one run stay unlocked after the run ends.
 *
 * recordProgress() is called from several trigger points across the codebase
 * (BarService for information/bar_trade/dedicated/story_hook triggers,
 * CorporateContactService for Orin's dedicated deal, ColonyController for
 * Tomas' Cantina-level milestones) — see each call site's own comment for
 * which figure/trigger it covers.
 */
class CharacterCodexService
{
    /**
     * Unlocks the next un-unlocked codex entry for this user + character, up
     * to config('game.character_codex.entries_per_character'). Idempotent
     * once the pool is exhausted — further calls are a no-op.
     */
    public function recordProgress(int $userId, string $characterSlug): void
    {
        $maxEntries = (int) config('game.character_codex.entries_per_character', 5);

        $unlockedCount = DB::table('character_codex_entries')
            ->where('user_id', $userId)
            ->where('character_slug', $characterSlug)
            ->count();

        if ($unlockedCount >= $maxEntries) {
            return;
        }

        DB::table('character_codex_entries')->insertOrIgnore([
            'user_id' => $userId,
            'character_slug' => $characterSlug,
            'entry_number' => $unlockedCount + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return int[] Sorted, 1-based entry numbers unlocked for this user + character. */
    public function getUnlockedEntries(int $userId, string $characterSlug): array
    {
        return DB::table('character_codex_entries')
            ->where('user_id', $userId)
            ->where('character_slug', $characterSlug)
            ->orderBy('entry_number')
            ->pluck('entry_number')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /**
     * Read path for the Charakter-Kodex screen (A42) — every roster character
     * from config('characters') plus this user's unlocked entry numbers, up
     * to entries_per_character. Cheap: one query for the whole roster instead
     * of N calls to getUnlockedEntries().
     *
     * @return array<int, array{slug: string, name: ?string, role: ?string, unlocked: int[], max_entries: int}>
     */
    public function getRosterWithUnlockedEntries(int $userId): array
    {
        $maxEntries = (int) config('game.character_codex.entries_per_character', 5);

        $unlockedByCharacter = DB::table('character_codex_entries')
            ->where('user_id', $userId)
            ->orderBy('entry_number')
            ->get(['character_slug', 'entry_number'])
            ->groupBy('character_slug')
            ->map(fn ($rows) => $rows->pluck('entry_number')->map(fn ($n) => (int) $n)->all());

        $roster = [];
        foreach (config('characters', []) as $slug => $character) {
            $roster[] = [
                'slug' => $slug,
                'name' => $character['name'] ?? null,
                'role' => $character['role'] ?? null,
                'unlocked' => $unlockedByCharacter[$slug] ?? [],
                'max_entries' => $maxEntries,
            ];
        }

        return $roster;
    }
}
