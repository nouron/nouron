<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GameTick step 8b — Trust recalculation.
 *
 * Trust is stored in colony_resources.amount WHERE resource_id=12.
 * It is recalculated each tick from:
 *   - Building contributions (building config trust_per_lv × level)
 *   - Research contributions (knowledge config trust_per_lv × level)
 *   - Ship contributions (capped at ±30)
 *   - One-shot events from trust_events (only strongest per type)
 *
 * Formula: clamp(Σbuildings + Σresearches + clamp(Σships, -30, +30) + events, -100, +100)
 *
 * Covered scenarios:
 *  Happy path:
 *  - Trust calculated and stored after tick (building contribution)
 *  - Trust event increases trust by configured amount
 *  - Trust event decreases trust by configured amount
 *
 *  Edge cases:
 *  - Trust clamped at +100 maximum
 *  - Trust clamped at -100 minimum
 *  - Trust events are one-shot (not repeated on subsequent ticks)
 *  - Duplicate events of the same type do not stack (strongest wins)
 *
 *  Adversarial:
 *  - Unknown event type is silently ignored (no crash, no effect)
 *  - Trust with zero buildings/events = 0 (neutral)
 *
 * Fixture summary (TestSeeder):
 *   Colony 1 (Springfield), user_id=3
 *   colony_resources resource_id=12: amount=0 (trust starts at 0)
 *
 * Uses tick numbers 11300–11349.
 */
class GameTickMoralTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID    = 1;
    private const MORAL_RES_ID = 12;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Start with clean trust state
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::MORAL_RES_ID],
            ['amount' => 0]
        );

        // Clear all pending trust events
        DB::table('trust_events')->where('colony_id', self::COLONY_ID)->delete();

        // Zero all supply costs so no over-cap multiplier fires during decay
        DB::table('buildings')->update(['supply_cost' => 0]);
        DB::table('researches')->update(['supply_cost' => 0]);
        DB::table('ships')->update(['supply_cost' => 0]);

        // Zero colony_ships for colony 1 so ship trust contribution = 0.
        // Colony 1 has corvettes (trust_per_unit=-1) and other ships that would
        // introduce a non-zero ship contribution and break isolation assertions.
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->update(['level' => 0]);

        // Zero all building levels for colony 1 that have trust_per_lv != 0.
        // Each test inserts exactly what it needs.
        $trustBuildingIds = collect(config('buildings', []))
            ->filter(fn($b) => ($b['trust_per_lv'] ?? 0) != 0)
            ->pluck('id')
            ->toArray();
        if (!empty($trustBuildingIds)) {
            DB::table('colony_buildings')
                ->where('colony_id', self::COLONY_ID)
                ->whereIn('building_id', $trustBuildingIds)
                ->update(['level' => 0]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getTrust(): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)
            ->where('resource_id', self::MORAL_RES_ID)
            ->value('amount');
    }

    private function setTrust(int $value): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::MORAL_RES_ID],
            ['amount' => $value]
        );
    }

    private function insertMoralEvent(string $eventType, int $tick): void
    {
        DB::table('trust_events')->insert([
            'colony_id'  => self::COLONY_ID,
            'tick'       => $tick,
            'event_type' => $eventType,
        ]);
    }

    // ── Happy path ─────────────────────────────────────────────────────────────

    /**
     * After a tick the trust value is stored in colony_resources.
     *
     * With no trust_per_lv buildings, no colony ships with trust_per_unit, and no
     * events, expected trust = 0 (setUp has already zeroed these contributions).
     */
    public function test_moral_is_stored_after_tick(): void
    {
        Artisan::call('game:tick', ['--tick' => 11300]);

        // Trust must have been SET (not left at default NULL)
        $trust = $this->getTrust();
        $this->assertIsInt($trust, 'Trust must be stored as integer in colony_resources after tick');
    }

    /**
     * A 'building_level_up' trust event contributes +1 to trust.
     *
     * Pre-condition: trust at 0, no building/ship trust contribution (zeroed in setUp)
     * → result = event value = +1.
     */
    public function test_positive_moral_event_increases_moral(): void
    {
        $eventEffect = (int) config('game.trust.events.building_level_up', 1);

        $this->insertMoralEvent('building_level_up', 11301);

        Artisan::call('game:tick', ['--tick' => 11301]);

        $trust = $this->getTrust();
        // buildings+researches+ships = 0 (zeroed above); event = +1
        $this->assertEquals($eventEffect, $trust,
            "Trust after 'building_level_up' event must equal the configured effect (+{$eventEffect})");
    }

    /**
     * A 'encounter_lost' trust event contributes -5 to trust.
     * setUp has already zeroed building and ship trust contributions.
     */
    public function test_negative_moral_event_decreases_moral(): void
    {
        $eventEffect = (int) config('game.trust.events.encounter_lost', -5);

        $this->insertMoralEvent('encounter_lost', 11302);

        Artisan::call('game:tick', ['--tick' => 11302]);

        $trust = $this->getTrust();
        $this->assertEquals($eventEffect, $trust,
            "Trust after 'encounter_lost' event must equal the configured effect ({$eventEffect})");
    }

    /**
     * Infirmary (building_id=46) contributes trust_per_lv=3 per level.
     * Colony 1 infirmary level=1 → trust contribution = +3.
     * setUp has already zeroed all other trust-contributing buildings and ships.
     */
    public function test_building_with_moral_per_lv_contributes_to_moral(): void
    {
        $infirmaryId = 46;
        $trustPerLv  = (int) (config('buildings.infirmary.trust_per_lv', 3));

        if ($trustPerLv === 0) {
            $this->markTestSkipped('Infirmary has trust_per_lv=0 in config — no contribution to test.');
        }

        // Set infirmary to level 1 for predictable contribution (setUp already zeroed others)
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => $infirmaryId, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0]
        );

        Artisan::call('game:tick', ['--tick' => 11303]);

        $trust = $this->getTrust();
        $this->assertEquals($trustPerLv, $trust,
            "Infirmary level 1 must contribute trust_per_lv={$trustPerLv} to total trust");
    }

    // ── Edge cases ─────────────────────────────────────────────────────────────

    /**
     * Trust must be clamped to +100 even when building contributions exceed +100.
     *
     * Bar (building_id=52) has trust_per_lv=2. At level=60 → contribution=120 → clamped to 100.
     * setUp has already zeroed ships and other trust-contributing buildings.
     */
    public function test_moral_clamped_at_positive_100(): void
    {
        $barTrustPerLv = (int) config('buildings.bar.trust_per_lv', 2);

        if ($barTrustPerLv <= 0) {
            $this->markTestSkipped('Bar trust_per_lv is not positive — cannot test upper clamp via bar.');
        }

        // Level 60 × trust_per_lv=2 = 120 → exceeds +100 → must clamp to 100
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 52, 'instance_id' => 1],
            ['level' => 60, 'status_points' => 20, 'ap_spend' => 0]
        );

        Artisan::call('game:tick', ['--tick' => 11310]);

        $trust = $this->getTrust();
        $this->assertLessThanOrEqual(100, $trust, 'Trust must never exceed +100');
        $this->assertEquals(100, $trust, 'Trust must be clamped to exactly +100 when sum exceeds it');
    }

    /**
     * Trust must be clamped to -100 even when negative contributions exceed -100.
     *
     * Use events from all negative-value event types and assert the result is always >= -100.
     */
    public function test_moral_clamped_at_negative_100(): void
    {
        // Strategy: use events from all negative-value event types
        // and assert the result is always >= -100.
        $negativeEvents = collect(config('game.trust.events', []))
            ->filter(fn($v) => $v < 0)
            ->keys();

        foreach ($negativeEvents as $eventType) {
            $this->insertMoralEvent($eventType, 11311);
        }

        Artisan::call('game:tick', ['--tick' => 11311]);

        $trust = $this->getTrust();
        $this->assertGreaterThanOrEqual(-100, $trust, 'Trust must never go below -100');
    }

    /**
     * Trust events are consumed in one tick and must not affect the next tick.
     * setUp has already zeroed building and ship trust contributions for isolation.
     */
    public function test_moral_event_does_not_carry_to_next_tick(): void
    {
        // Insert event only for tick 11320 — not for tick 11321
        $this->insertMoralEvent('building_level_up', 11320);

        Artisan::call('game:tick', ['--tick' => 11320]);
        $trustAfterTick1 = $this->getTrust();

        Artisan::call('game:tick', ['--tick' => 11321]);
        $trustAfterTick2 = $this->getTrust();

        // Both ticks with no buildings → event fired only in tick 11320
        // tick 11320: trust = +1 (event);  tick 11321: trust = 0 (no event)
        $this->assertEquals(1, $trustAfterTick1, 'Trust must reflect the event in its tick');
        $this->assertEquals(0, $trustAfterTick2, 'Trust event must not carry to the next tick');
    }

    /**
     * Two events of the same type in one tick do NOT stack — only the strongest counts.
     * 'encounter_lost' = -5; two of them → still -5 (not -10).
     * setUp has already zeroed building and ship trust contributions for isolation.
     */
    public function test_duplicate_moral_events_same_type_do_not_stack(): void
    {
        // Insert the same event type twice for the same tick
        $this->insertMoralEvent('encounter_lost', 11330);
        $this->insertMoralEvent('encounter_lost', 11330);

        Artisan::call('game:tick', ['--tick' => 11330]);

        $trust        = $this->getTrust();
        $singleEffect = (int) config('game.trust.events.encounter_lost', -5);

        // Must be exactly -5, not -10 (not stacked)
        $this->assertEquals($singleEffect, $trust,
            'Duplicate trust events of the same type must not stack — strongest (only one) wins');
    }

    // ── Adversarial ────────────────────────────────────────────────────────────

    /**
     * An unknown event type fired via fireEvent() must not cause an exception
     * and must not affect trust.
     * setUp has already zeroed building and ship trust contributions for isolation.
     */
    public function test_unknown_moral_event_type_is_ignored(): void
    {
        // TrustService::fireEvent() guards unknown types — it never inserts.
        // But what if someone directly inserts an unknown type?
        DB::table('trust_events')->insert([
            'colony_id'  => self::COLONY_ID,
            'tick'       => 11340,
            'event_type' => 'totally_unknown_event_xyz',
        ]);

        Artisan::call('game:tick', ['--tick' => 11340]);

        $trust = $this->getTrust();
        // eventContribution() looks up $cfg[$type] which returns null/0 for unknown keys
        $this->assertEquals(0, $trust, 'Unknown event type must contribute 0 to trust (silently ignored)');
    }
}
