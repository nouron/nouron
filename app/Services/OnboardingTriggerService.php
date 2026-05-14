<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * OnboardingTriggerService — tracks and fires one-shot onboarding triggers.
 *
 * Triggers are stored as a JSON array in user_preferences.fired_triggers.
 * Each trigger key is written at most once per user — subsequent calls to
 * markFired() are idempotent.
 */
class OnboardingTriggerService
{
    /**
     * Returns whether a trigger has already been fired for this user.
     */
    public function hasFired(int $userId, string $triggerKey): bool
    {
        return in_array($triggerKey, $this->getFired($userId), true);
    }

    /**
     * Marks a trigger as fired for this user.
     * Safe to call multiple times — only written once.
     */
    public function markFired(int $userId, string $triggerKey): void
    {
        $fired = $this->getFired($userId);

        if (in_array($triggerKey, $fired, true)) {
            return;
        }

        $fired[] = $triggerKey;

        DB::table('user_preferences')->updateOrInsert(
            ['user_id' => $userId],
            ['fired_triggers' => json_encode(array_values($fired))]
        );
    }

    /**
     * Parses the fired_triggers JSON column into a plain string array.
     *
     * @return list<string>
     */
    private function getFired(int $userId): array
    {
        $prefs = DB::table('user_preferences')
            ->where('user_id', $userId)
            ->first();

        $raw = $prefs->fired_triggers ?? null;

        if (empty($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, 'is_string'));
    }
}
