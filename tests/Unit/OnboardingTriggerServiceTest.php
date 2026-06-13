<?php

namespace Tests\Unit;

use App\Services\OnboardingTriggerService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Unit tests for OnboardingTriggerService.
 *
 * Covered scenarios:
 *   - hasFired() → false when no user_preferences row exists
 *   - hasFired() → false when fired_triggers column is NULL
 *   - hasFired() → false when the key is absent from the JSON array
 *   - hasFired() → true when the key is present in the JSON array
 *   - markFired() creates a user_preferences row when none exists
 *   - markFired() is idempotent — calling twice does not duplicate the key
 *   - markFired() does not overwrite other previously fired trigger keys
 */
class OnboardingTriggerServiceTest extends TestCase
{
    use RefreshDatabase;

    private OnboardingTriggerService $service;

    /** A fresh user ID that has no rows in user or user_preferences. */
    private int $userId = 9001;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // user_preferences.user_id has a FK to user.user_id — insert a minimal row.
        DB::table('user')->insertOrIgnore([
            'user_id' => $this->userId,
            'username' => 'TriggerUnit',
            'display_name' => 'Trigger Unit',
            'role' => 'player',
            'password' => bcrypt('pw'),
            'email' => 'trigger-unit@test.local',
            'activation_key' => 'triggerunitkey',
            'faction_id' => 7,
        ]);

        $this->service = $this->app->make(OnboardingTriggerService::class);
    }

    // ── hasFired() ─────────────────────────────────────────────────────────────

    public function test_has_fired_returns_false_when_no_preferences_row_exists(): void
    {
        // No user_preferences row at all for this userId.
        $this->assertFalse(
            $this->service->hasFired($this->userId, 'onboarding_decay'),
            'hasFired() must return false when no user_preferences row exists'
        );
    }

    public function test_has_fired_returns_false_when_fired_triggers_is_null(): void
    {
        DB::table('user_preferences')->insert([
            'user_id' => $this->userId,
            'fired_triggers' => null,
        ]);

        $this->assertFalse(
            $this->service->hasFired($this->userId, 'onboarding_decay'),
            'hasFired() must return false when fired_triggers column is NULL'
        );
    }

    public function test_has_fired_returns_false_when_key_not_in_array(): void
    {
        DB::table('user_preferences')->insert([
            'user_id' => $this->userId,
            'fired_triggers' => json_encode(['supply_cap_full']),
        ]);

        $this->assertFalse(
            $this->service->hasFired($this->userId, 'onboarding_decay'),
            'hasFired() must return false when the specific key is not in the JSON array'
        );
    }

    public function test_has_fired_returns_true_when_key_is_present(): void
    {
        DB::table('user_preferences')->insert([
            'user_id' => $this->userId,
            'fired_triggers' => json_encode(['onboarding_decay', 'supply_cap_full']),
        ]);

        $this->assertTrue(
            $this->service->hasFired($this->userId, 'onboarding_decay'),
            'hasFired() must return true when the key is present in fired_triggers'
        );
    }

    // ── markFired() ────────────────────────────────────────────────────────────

    public function test_mark_fired_creates_preferences_row_when_none_exists(): void
    {
        $this->assertDatabaseMissing('user_preferences', ['user_id' => $this->userId]);

        $this->service->markFired($this->userId, 'onboarding_decay');

        $raw = DB::table('user_preferences')
            ->where('user_id', $this->userId)
            ->value('fired_triggers');

        $this->assertNotNull($raw, 'markFired() must create a user_preferences row');
        $decoded = json_decode($raw, true);
        $this->assertContains('onboarding_decay', $decoded, 'The trigger key must appear in the JSON array');
    }

    public function test_mark_fired_is_idempotent(): void
    {
        $this->service->markFired($this->userId, 'onboarding_decay');
        $this->service->markFired($this->userId, 'onboarding_decay');

        $raw = DB::table('user_preferences')->where('user_id', $this->userId)->value('fired_triggers');
        $decoded = json_decode($raw, true);

        $occurrences = array_count_values($decoded)['onboarding_decay'] ?? 0;
        $this->assertEquals(1, $occurrences, 'Calling markFired() twice must not duplicate the key');
    }

    public function test_mark_fired_does_not_overwrite_other_trigger_keys(): void
    {
        // Pre-load one existing trigger for this user.
        DB::table('user_preferences')->insert([
            'user_id' => $this->userId,
            'fired_triggers' => json_encode(['supply_cap_full']),
        ]);

        $this->service->markFired($this->userId, 'onboarding_trust');

        $raw = DB::table('user_preferences')->where('user_id', $this->userId)->value('fired_triggers');
        $decoded = json_decode($raw, true);

        $this->assertContains('supply_cap_full', $decoded, 'Pre-existing trigger key must be preserved');
        $this->assertContains('onboarding_trust', $decoded, 'New trigger key must be added');
        $this->assertCount(2, $decoded, 'Array must contain exactly the original and the new key');
    }

    // ── Edge cases / adversarial ───────────────────────────────────────────────

    public function test_has_fired_with_malformed_json_returns_false(): void
    {
        DB::table('user_preferences')->insert([
            'user_id' => $this->userId,
            'fired_triggers' => 'not-valid-json',
        ]);

        $this->assertFalse(
            $this->service->hasFired($this->userId, 'onboarding_decay'),
            'hasFired() must return false (not throw) when fired_triggers contains malformed JSON'
        );
    }

    public function test_mark_fired_for_two_different_users_are_isolated(): void
    {
        $userA = 9002;
        $userB = 9003;

        // user_preferences.user_id has a FK to user.user_id — insert minimal user rows.
        foreach ([$userA, $userB] as $uid) {
            DB::table('user')->insertOrIgnore([
                'user_id' => $uid,
                'username' => "testuser{$uid}",
                'display_name' => "Test {$uid}",
                'role' => 'player',
                'password' => bcrypt('pw'),
                'email' => "{$uid}@test.local",
                'activation_key' => "key{$uid}",
                'faction_id' => 7,
            ]);
        }

        $this->service->markFired($userA, 'onboarding_decay');

        $this->assertTrue($this->service->hasFired($userA, 'onboarding_decay'));
        $this->assertFalse(
            $this->service->hasFired($userB, 'onboarding_decay'),
            'Firing a trigger for user A must not affect user B'
        );
    }
}
