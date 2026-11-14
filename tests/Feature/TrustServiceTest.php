<?php

namespace Tests\Feature;

/**
 * TrustServiceTest — feature tests for TrustService source arithmetic.
 *
 * Bands, AP, base value, clamp, Security Hub mitigation and the player view of the
 * T59 scale (0..100) live in TrustScaleTest. Here every expected value is
 * base + source amounts, with the amounts read from config where possible.
 *
 * Covered: getTrust(), fireEvent(), building/research/ship/event contributions,
 * ship cap, global clamp, calculateAndStore(), breakdown().
 */

use App\Models\Colony;
use App\Services\TickService;
use App\Services\TrustService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrustServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TrustService $service;

    protected TickService $tickService;

    protected int $colonyId = 1;   // Springfield — owned by Bart (user_id=3)

    protected int $tick = 100; // fixed tick, avoids wall-clock dependency

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Use a fixed tick so tests are deterministic regardless of time of day
        $this->tickService = $this->app->make(TickService::class);
        $this->tickService->setTickCount($this->tick);

        $this->service = $this->app->make(TrustService::class);

        // Remove all seeded colony_resources rows for trust (resource_id=12) and all
        // trust_events so each test starts from a known blank state.
        DB::table('colony_resources')
            ->where('resource_id', TrustService::RESOURCE_ID)
            ->delete();

        DB::table('trust_events')->delete();

        // Remove seeded colony_buildings / colony_researches / colony_ships for
        // colony 1 so building/research/ship tests can insert exactly what they need.
        DB::table('colony_buildings')->where('colony_id', $this->colonyId)->delete();
        DB::table('colony_researches')->where('colony_id', $this->colonyId)->delete();
        DB::table('colony_ships')->where('colony_id', $this->colonyId)->delete();
    }

    private function base(): int
    {
        return (int) config('game.trust.base');
    }

    private function perLv(string $file, string $key): int
    {
        return (int) config("{$file}.{$key}.trust_per_lv");
    }

    // ── getTrust() ───────────────────────────────────────────────────────────

    public function test_get_trust_returns_base_when_no_row_exists(): void
    {
        // setUp already deleted all resource_id=12 rows
        $this->assertSame($this->base(), $this->service->getTrust($this->colonyId));
    }

    public function test_get_trust_returns_stored_positive_value(): void
    {
        DB::table('colony_resources')->insert([
            'resource_id' => TrustService::RESOURCE_ID,
            'colony_id' => $this->colonyId,
            'amount' => 42,
        ]);

        $this->assertSame(42, $this->service->getTrust($this->colonyId));
    }

    public function test_get_trust_returns_stored_low_value(): void
    {
        DB::table('colony_resources')->insert([
            'resource_id' => TrustService::RESOURCE_ID,
            'colony_id' => $this->colonyId,
            'amount' => 3,
        ]);

        $this->assertSame(3, $this->service->getTrust($this->colonyId));
    }

    public function test_get_trust_returns_base_for_unknown_colony(): void
    {
        $this->assertSame($this->base(), $this->service->getTrust(99999));
    }

    // ── fireEvent() ──────────────────────────────────────────────────────────

    public function test_fire_event_known_event_inserts_row(): void
    {
        $this->service->fireEvent($this->colonyId, 'trade_success', $this->tick);

        $this->assertDatabaseHas('trust_events', [
            'colony_id' => $this->colonyId,
            'tick' => $this->tick,
            'event_type' => 'trade_success',
        ]);
    }

    public function test_fire_event_unknown_event_type_inserts_no_row(): void
    {
        $this->service->fireEvent($this->colonyId, 'made_up_event', $this->tick);

        $this->assertDatabaseMissing('trust_events', [
            'colony_id' => $this->colonyId,
        ]);
    }

    public function test_fire_event_default_tick_is_current_tick_plus_one(): void
    {
        // No tick argument — should default to tickService->getTickCount() + 1
        $this->service->fireEvent($this->colonyId, 'trade_success');

        $expectedTick = $this->tickService->getTickCount() + 1;

        $this->assertDatabaseHas('trust_events', [
            'colony_id' => $this->colonyId,
            'tick' => $expectedTick,
            'event_type' => 'trade_success',
        ]);
    }

    public function test_fire_event_explicit_tick_is_stored_as_given(): void
    {
        $customTick = 9999;
        $this->service->fireEvent($this->colonyId, 'treaty_signed', $customTick);

        $this->assertDatabaseHas('trust_events', [
            'colony_id' => $this->colonyId,
            'tick' => $customTick,
            'event_type' => 'treaty_signed',
        ]);
    }

    // ── calculateTrust() — building contribution ─────────────────────────────

    public function test_calculate_trust_positive_building_contribution(): void
    {
        // infirmary (id=46): trust_per_lv × level 4
        DB::table('colony_buildings')->insert([
            'colony_id' => $this->colonyId,
            'building_id' => 46,
            'level' => 4,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame($this->base() + 4 * $this->perLv('buildings', 'infirmary'), $trust);
    }

    public function test_calculate_trust_building_with_zero_status_points_is_excluded(): void
    {
        // hospital (id=46): status_points=0 → should not contribute
        DB::table('colony_buildings')->insert([
            'colony_id' => $this->colonyId,
            'building_id' => 46,
            'level' => 5,
            'status_points' => 0,
            'ap_spend' => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame($this->base(), $trust);
    }

    public function test_calculate_trust_building_not_in_config_is_ignored(): void
    {
        // commandCenter (id=25) is not in trust config → no contribution
        DB::table('colony_buildings')->insert([
            'colony_id' => $this->colonyId,
            'building_id' => 25,
            'level' => 10,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame($this->base(), $trust);
    }

    // ── calculateTrust() — research contribution ─────────────────────────────

    public function test_calculate_trust_positive_research_contribution(): void
    {
        // health (id=94): trust_per_lv × level 3
        DB::table('colony_researches')->insert([
            'colony_id' => $this->colonyId,
            'research_id' => 94,
            'level' => 3,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame($this->base() + 3 * $this->perLv('knowledge', 'health'), $trust);
    }

    public function test_calculate_trust_positive_defense_contribution(): void
    {
        // defense (id=96): trust_per_lv × level 10
        DB::table('colony_researches')->insert([
            'colony_id' => $this->colonyId,
            'research_id' => 96,
            'level' => 10,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame($this->base() + 10 * $this->perLv('knowledge', 'defense'), $trust);
    }

    public function test_calculate_trust_research_not_in_config_is_ignored(): void
    {
        // knowledge_construction (research_id=90, trust_per_lv=0 → filtered from trust config)
        DB::table('colony_researches')->insert([
            'colony_id' => $this->colonyId,
            'research_id' => 90,
            'level' => 10,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame($this->base(), $trust);
    }

    // ── calculateTrust() — ship contribution ─────────────────────────────────

    public function test_calculate_trust_positive_ship_contribution(): void
    {
        // freighter (id=47): trust_per_unit × 6
        DB::table('colony_ships')->insert([
            'colony_id' => $this->colonyId,
            'ship_id' => 47,
            'level' => 6,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame($this->base() + 6 * (int) config('ships.freighter.trust_per_unit'), $trust);
    }

    public function test_calculate_trust_corvette_is_neutral(): void
    {
        // corvette (id=37): trust_per_unit=0 — colonists welcome protection, not a threat
        DB::table('colony_ships')->insert([
            'colony_id' => $this->colonyId,
            'ship_id' => 37,
            'level' => 20,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame($this->base(), $trust);
    }

    public function test_calculate_trust_ship_contribution_is_capped(): void
    {
        // freighter (id=47): level=100 → raw far above ships_cap, capped
        DB::table('colony_ships')->insert([
            'colony_id' => $this->colonyId,
            'ship_id' => 47,
            'level' => 100,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame($this->base() + (int) config('game.trust.ships_cap'), $trust);
    }

    public function test_calculate_trust_ship_contribution_cap_holds_for_mid_fleet(): void
    {
        // With corvette neutral (0) and only frachter positive, negative cap is unreachable.
        // Test verifies the positive cap still works as a boundary at ships_cap.
        DB::table('colony_ships')->insert([
            'colony_id' => $this->colonyId,
            'ship_id' => 47,
            'level' => 50,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame($this->base() + (int) config('game.trust.ships_cap'), $trust);
    }

    // ── calculateTrust() — event contribution ────────────────────────────────

    public function test_calculate_trust_event_contribution_adds_delta_for_matching_tick(): void
    {
        // trade_success: +2
        $this->service->fireEvent($this->colonyId, 'trade_success', $this->tick);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame($this->base() + (int) config('game.trust.events.trade_success'), $trust);
    }

    public function test_calculate_trust_same_event_type_fired_twice_counts_only_once(): void
    {
        // trade_success: +2, fired twice — must NOT stack
        DB::table('trust_events')->insert([
            'colony_id' => $this->colonyId,
            'tick' => $this->tick,
            'event_type' => 'trade_success',
        ]);
        DB::table('trust_events')->insert([
            'colony_id' => $this->colonyId,
            'tick' => $this->tick,
            'event_type' => 'trade_success',
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        // trade_success = +2, counted once
        $this->assertSame($this->base() + (int) config('game.trust.events.trade_success'), $trust);
    }

    public function test_calculate_trust_event_for_different_tick_is_not_included(): void
    {
        // Fire event for tick+1 — should NOT appear in calculation for $this->tick
        DB::table('trust_events')->insert([
            'colony_id' => $this->colonyId,
            'tick' => $this->tick + 1,
            'event_type' => 'trade_success',
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame($this->base(), $trust);
    }

    public function test_calculate_trust_multiple_distinct_events_are_summed(): void
    {
        // trade_success + research_level_up + treaty_signed, all summed
        DB::table('trust_events')->insert([
            ['colony_id' => $this->colonyId, 'tick' => $this->tick, 'event_type' => 'trade_success'],
            ['colony_id' => $this->colonyId, 'tick' => $this->tick, 'event_type' => 'research_level_up'],
            ['colony_id' => $this->colonyId, 'tick' => $this->tick, 'event_type' => 'treaty_signed'],
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame($this->base() + (int) config('game.trust.events.trade_success') + (int) config('game.trust.events.research_level_up') + (int) config('game.trust.events.treaty_signed'), $trust);
    }

    public function test_calculate_trust_negative_event_contribution(): void
    {
        // encounter_lost: -4
        $this->service->fireEvent($this->colonyId, 'encounter_lost', $this->tick);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame($this->base() + (int) config('game.trust.events.encounter_lost'), $trust);
    }

    // ── calculateTrust() — global clamp ──────────────────────────────────────

    public function test_calculate_trust_is_clamped_to_positive100(): void
    {
        // Insert many positive-trust infirmaries to push raw sum well above 100
        // infirmary (id=46): level=50 → raw building contribution far above 100
        DB::table('colony_buildings')->insert([
            'colony_id' => $this->colonyId,
            'building_id' => 46,
            'level' => 50,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(100, $trust);
    }

    public function test_calculate_trust_is_clamped_to_zero(): void
    {
        // health (id=94): level=-100 → raw sum far below 0, clamped to 0.
        // Negatives Level ist kein erreichbarer Spielzustand — reiner
        // Formel-Grenzfall-Test der Clamp-Logik selbst, unabhängig von einer
        // bestimmten Kenntnis. Ersetzt den vorigen defense-basierten Aufbau
        // (defense ist seit 2026-08-27 positiv, kann diesen Grenzfall nicht
        // mehr erzeugen).
        DB::table('colony_researches')->insert([
            'colony_id' => $this->colonyId,
            'research_id' => 94,
            'level' => -100,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(0, $trust);
    }

    // ── calculateAndStore() ───────────────────────────────────────────────────

    public function test_calculate_and_store_persists_trust_in_colony_resources(): void
    {
        // infirmary (id=46): trust_per_lv × level 5
        DB::table('colony_buildings')->insert([
            'colony_id' => $this->colonyId,
            'building_id' => 46,
            'level' => 5,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        $colony = Colony::find($this->colonyId);
        $result = $this->service->calculateAndStore($colony, $this->tick);

        $this->assertSame($this->base() + 5 * $this->perLv('buildings', 'infirmary'), $result);

        $this->assertDatabaseHas('colony_resources', [
            'colony_id' => $this->colonyId,
            'resource_id' => TrustService::RESOURCE_ID,
            'amount' => $result,
        ]);
    }

    public function test_calculate_and_store_updates_existing_row(): void
    {
        // Pre-insert a stale trust value
        DB::table('colony_resources')->insert([
            'resource_id' => TrustService::RESOURCE_ID,
            'colony_id' => $this->colonyId,
            'amount' => 99,
        ]);

        // No buildings/events → trust = base
        $colony = Colony::find($this->colonyId);
        $result = $this->service->calculateAndStore($colony, $this->tick);

        $this->assertSame($this->base(), $result);

        // Must update, not insert a second row
        $count = DB::table('colony_resources')
            ->where('colony_id', $this->colonyId)
            ->where('resource_id', TrustService::RESOURCE_ID)
            ->count();

        $this->assertSame(1, $count);

        $this->assertDatabaseHas('colony_resources', [
            'colony_id' => $this->colonyId,
            'resource_id' => TrustService::RESOURCE_ID,
            'amount' => $this->base(),
        ]);
    }

    public function test_calculate_and_store_returns_clamped_value(): void
    {
        // health (id=94): level=-100 → raw sum far below 0, clamped to 0.
        // Siehe test_calculate_trust_is_clamped_to_zero() für die
        // Begründung, warum health statt defense (defense ist seit
        // 2026-08-27 positiv).
        DB::table('colony_researches')->insert([
            'colony_id' => $this->colonyId,
            'research_id' => 94,
            'level' => -100,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        $colony = Colony::find($this->colonyId);
        $result = $this->service->calculateAndStore($colony, $this->tick);

        $this->assertSame(0, $result);
    }

    public function test_calculate_and_store_return_value_matches_stored_value(): void
    {
        // health (id=94): trust_per_lv × level 7
        DB::table('colony_researches')->insert([
            'colony_id' => $this->colonyId,
            'research_id' => 94,
            'level' => 7,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        $colony = Colony::find($this->colonyId);
        $returned = $this->service->calculateAndStore($colony, $this->tick);
        $stored = $this->service->getTrust($this->colonyId);

        $this->assertSame($this->base() + 7 * $this->perLv('knowledge', 'health'), $returned);
        $this->assertSame($returned, $stored);
    }

    // ── Combined contributions ────────────────────────────────────────────────

    public function test_calculate_trust_combines_all_sources(): void
    {
        // infirmary (id=46): level=2
        DB::table('colony_buildings')->insert([
            'colony_id' => $this->colonyId,
            'building_id' => 46,
            'level' => 2,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        // health (id=94): level=3
        DB::table('colony_researches')->insert([
            'colony_id' => $this->colonyId,
            'research_id' => 94,
            'level' => 3,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        // freighter (id=47): level=2
        DB::table('colony_ships')->insert([
            'colony_id' => $this->colonyId,
            'ship_id' => 47,
            'level' => 2,
            'status_points' => 10,
            'ap_spend' => 0,
        ]);

        // trade_success: +2
        DB::table('trust_events')->insert([
            'colony_id' => $this->colonyId,
            'tick' => $this->tick,
            'event_type' => 'trade_success',
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        // base + buildings + researches + ships + events
        $this->assertSame($this->base() + 2 * $this->perLv('buildings', 'infirmary') + 3 * $this->perLv('knowledge', 'health') + 2 * (int) config('ships.freighter.trust_per_unit') + (int) config('game.trust.events.trade_success'), $trust);
    }

    public function test_breakdown_lists_each_source_and_sums_to_calculate_trust(): void
    {
        DB::table('colony_buildings')->insert([
            'colony_id' => $this->colonyId, 'building_id' => 46, 'level' => 4, 'status_points' => 10, 'ap_spend' => 0,
        ]);
        DB::table('glx_colonies')->where('id', $this->colonyId)->update(['hunger_streak' => 2]);

        $b = $this->service->breakdown($this->colonyId, $this->tick);

        $this->assertSame(4 * $this->perLv('buildings', 'infirmary'), $b['buildings']);
        $this->assertSame(0, $b['research']);
        $this->assertSame(0, $b['ships']);
        $this->assertSame(0, $b['events']);
        $this->assertSame(-((int) config('game.food.hunger_base_malus') + (int) config('game.food.hunger_step')), $b['hunger']); // base + 1 step
        $this->assertSame($this->service->calculateTrust($this->colonyId, $this->tick), $b['total']);
        $this->assertSame($this->base() + $b['buildings'] + $b['hunger'], $b['total']);
    }
}
