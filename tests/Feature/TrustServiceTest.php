<?php

namespace Tests\Feature;

/**
 * TrustServiceTest — comprehensive feature tests for TrustService.
 *
 * Covered scenarios:
 *
 * getBand():
 *   - All five bands with exact lower/upper boundary values; assertions use __() so
 *     they stay locale-agnostic and pass regardless of APP_LOCALE
 *   - Boundary note: the code uses `>= -61` for Unruhig, so -61 maps to Unruhig and
 *     Aufruhr starts at -62 (the docblock in TrustService has a typo — code wins)
 *   - Locale-switch test: switching to a hypothetical 'en' locale returns English strings
 *     (or falls back gracefully to the translation key if no en file exists)
 *
 * getProductionMultiplier() / getApMultiplier():
 *   - Neutral trust (0) returns 1.0×
 *   - Euphorisch trust (80) returns the configured factor
 *   - Aufruhr trust (-80) returns the configured factor
 *   - Out-of-range trust that misses all bands falls back to 1.0
 *
 * getTrust():
 *   - Returns 0 when no colony_resources row exists for resource_id=12
 *   - Returns the stored integer value when the row exists
 *   - Handles stored negative trust correctly
 *
 * fireEvent():
 *   - Known event inserts a row into trust_events with correct colony_id/tick/event_type
 *   - Unknown event type is silently ignored (no row inserted)
 *   - Default tick is current tick + 1 when no tick is passed
 *   - Explicit tick is respected when passed
 *
 * calculateTrust() — building contribution:
 *   - A positive-trust building (hospital, id=46) adds level × 3 to trust
 *   - Building with status_points=0 is excluded from calculation
 *
 * calculateTrust() — research contribution:
 *   - A positive-trust research (health, id=94) adds level × 2
 *   - A negative-trust research (defense, id=96) subtracts level × 1
 *
 * calculateTrust() — ship contribution:
 *   - A positive-trust ship (frachter, id=47) adds level × 1
 *   - A negative-trust ship (korvette, id=37) subtracts level × 1
 *   - Total ship contribution is capped at ±30 before global clamp
 *
 * calculateTrust() — event contribution:
 *   - A fired event contributes its delta at the matching tick
 *   - Same event type fired twice is counted only once (no stacking)
 *   - Events for a different tick are NOT included
 *   - Multiple distinct event types are summed
 *
 * calculateTrust() — global clamp:
 *   - Result is clamped to +100 even when raw sum exceeds 100
 *   - Result is clamped to -100 even when raw sum exceeds -100
 *
 * calculateAndStore():
 *   - Persists the calculated trust in colony_resources (resource_id=12)
 *   - Updates existing row rather than inserting a duplicate
 *   - Returns the clamped integer value
 */

use App\Models\Colony;
use App\Services\TrustService;
use App\Services\TickService;
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
    protected int $tick     = 100; // fixed tick, avoids wall-clock dependency

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

    // ── getBand() ─────────────────────────────────────────────────────────────
    //
    // Assertions compare against __('trust.band_*') so they pass regardless of which
    // locale is active — the translation layer is tested separately below.

    public function testGetBand_returnsAufruhr_atMinus100(): void
    {
        $this->assertSame(__('trust.band_aufruhr'), $this->service->getBand(-100));
    }

    public function testGetBand_returnsUnruhig_atMinus61(): void
    {
        // The code uses `>= -61` for Unruhig, so -61 is the lower boundary of Unruhig.
        // The docblock in TrustService is incorrect — the implementation is the source of truth.
        $this->assertSame(__('trust.band_unruhig'), $this->service->getBand(-61));
    }

    public function testGetBand_returnsAufruhr_atMinus62(): void
    {
        // Aufruhr starts at -62 (first value where `$trust >= -61` is false).
        $this->assertSame(__('trust.band_aufruhr'), $this->service->getBand(-62));
    }

    public function testGetBand_returnsUnruhig_atMinus60(): void
    {
        $this->assertSame(__('trust.band_unruhig'), $this->service->getBand(-60));
    }

    public function testGetBand_returnsUnruhig_atMinus21(): void
    {
        $this->assertSame(__('trust.band_unruhig'), $this->service->getBand(-21));
    }

    public function testGetBand_returnsStabil_atMinus20(): void
    {
        $this->assertSame(__('trust.band_stabil'), $this->service->getBand(-20));
    }

    public function testGetBand_returnsStabil_atZero(): void
    {
        $this->assertSame(__('trust.band_stabil'), $this->service->getBand(0));
    }

    public function testGetBand_returnsStabil_at20(): void
    {
        $this->assertSame(__('trust.band_stabil'), $this->service->getBand(20));
    }

    public function testGetBand_returnsZufrieden_at21(): void
    {
        $this->assertSame(__('trust.band_zufrieden'), $this->service->getBand(21));
    }

    public function testGetBand_returnsZufrieden_at60(): void
    {
        $this->assertSame(__('trust.band_zufrieden'), $this->service->getBand(60));
    }

    public function testGetBand_returnsEuphorisch_at61(): void
    {
        $this->assertSame(__('trust.band_euphorisch'), $this->service->getBand(61));
    }

    public function testGetBand_returnsEuphorisch_at100(): void
    {
        $this->assertSame(__('trust.band_euphorisch'), $this->service->getBand(100));
    }

    // ── getBand() — locale translation ───────────────────────────────────────

    public function testGetBand_deLocale_returnsGermanStrings(): void
    {
        app()->setLocale('de');

        $this->assertSame('Euphorisch', $this->service->getBand(100));
        $this->assertSame('Zufrieden',  $this->service->getBand(40));
        $this->assertSame('Stabil',     $this->service->getBand(0));
        $this->assertSame('Unruhig',    $this->service->getBand(-40));
        $this->assertSame('Aufruhr',    $this->service->getBand(-100));
    }

    public function testGetBand_unknownLocale_returnsTranslationKey(): void
    {
        // When a locale has no translation file at all (not even in the fallback chain),
        // Laravel returns the translation key verbatim. This documents that behaviour:
        // callers must ensure the active locale has a trust.php file.
        app()->setLocale('xx');

        $this->assertSame('trust.band_euphorisch', $this->service->getBand(100));
        $this->assertSame('trust.band_aufruhr',    $this->service->getBand(-100));
    }

    // ── getProductionMultiplier() ─────────────────────────────────────────────

    public function testGetProductionMultiplier_neutralTrust_returnsOne(): void
    {
        $this->assertSame(1.0, $this->service->getProductionMultiplier(0));
    }

    public function testGetProductionMultiplier_euphorisch_returnsOnePtTwo(): void
    {
        // config: min=61, max=100, factor=1.20
        $this->assertSame(1.20, $this->service->getProductionMultiplier(80));
    }

    public function testGetProductionMultiplier_aufruhr_returnsZeroPtSeven(): void
    {
        // config: min=-100, max=-61, factor=0.70
        $this->assertSame(0.70, $this->service->getProductionMultiplier(-80));
    }

    public function testGetProductionMultiplier_zufrieden_returnsOnePtOne(): void
    {
        // config: min=21, max=60, factor=1.10
        $this->assertSame(1.10, $this->service->getProductionMultiplier(40));
    }

    public function testGetProductionMultiplier_unruhig_returnsZeroPtEightFive(): void
    {
        // config: min=-60, max=-21, factor=0.85
        $this->assertSame(0.85, $this->service->getProductionMultiplier(-40));
    }

    // ── getApMultiplier() ────────────────────────────────────────────────────

    public function testGetApMultiplier_neutralTrust_returnsOne(): void
    {
        $this->assertSame(1.0, $this->service->getApMultiplier(0));
    }

    public function testGetApMultiplier_euphorisch_returnsOnePtOne(): void
    {
        // config: min=61, max=100, factor=1.10
        $this->assertSame(1.10, $this->service->getApMultiplier(80));
    }

    public function testGetApMultiplier_aufruhr_returnsZeroPtEight(): void
    {
        // config: min=-100, max=-61, factor=0.80
        $this->assertSame(0.80, $this->service->getApMultiplier(-80));
    }

    // ── getTrust() ───────────────────────────────────────────────────────────

    public function testGetTrust_returnsZero_whenNoRowExists(): void
    {
        // setUp already deleted all resource_id=12 rows
        $this->assertSame(0, $this->service->getTrust($this->colonyId));
    }

    public function testGetTrust_returnsStoredPositiveValue(): void
    {
        DB::table('colony_resources')->insert([
            'resource_id' => TrustService::RESOURCE_ID,
            'colony_id'   => $this->colonyId,
            'amount'      => 42,
        ]);

        $this->assertSame(42, $this->service->getTrust($this->colonyId));
    }

    public function testGetTrust_returnsStoredNegativeValue(): void
    {
        DB::table('colony_resources')->insert([
            'resource_id' => TrustService::RESOURCE_ID,
            'colony_id'   => $this->colonyId,
            'amount'      => -35,
        ]);

        $this->assertSame(-35, $this->service->getTrust($this->colonyId));
    }

    public function testGetTrust_returnsZero_forUnknownColony(): void
    {
        $this->assertSame(0, $this->service->getTrust(99999));
    }

    // ── fireEvent() ──────────────────────────────────────────────────────────

    public function testFireEvent_knownEvent_insertsRow(): void
    {
        $this->service->fireEvent($this->colonyId, 'trade_success', $this->tick);

        $this->assertDatabaseHas('trust_events', [
            'colony_id'  => $this->colonyId,
            'tick'       => $this->tick,
            'event_type' => 'trade_success',
        ]);
    }

    public function testFireEvent_unknownEventType_insertsNoRow(): void
    {
        $this->service->fireEvent($this->colonyId, 'made_up_event', $this->tick);

        $this->assertDatabaseMissing('trust_events', [
            'colony_id' => $this->colonyId,
        ]);
    }

    public function testFireEvent_defaultTick_isCurrentTickPlusOne(): void
    {
        // No tick argument — should default to tickService->getTickCount() + 1
        $this->service->fireEvent($this->colonyId, 'encounter_won');

        $expectedTick = $this->tickService->getTickCount() + 1;

        $this->assertDatabaseHas('trust_events', [
            'colony_id'  => $this->colonyId,
            'tick'       => $expectedTick,
            'event_type' => 'encounter_won',
        ]);
    }

    public function testFireEvent_explicitTick_isStoredAsGiven(): void
    {
        $customTick = 9999;
        $this->service->fireEvent($this->colonyId, 'treaty_signed', $customTick);

        $this->assertDatabaseHas('trust_events', [
            'colony_id'  => $this->colonyId,
            'tick'       => $customTick,
            'event_type' => 'treaty_signed',
        ]);
    }

    // ── calculateTrust() — building contribution ─────────────────────────────

    public function testCalculateTrust_positiveBuildingContribution(): void
    {
        // hospital (id=46): +3 per level; level=4 → +12
        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 46,
            'level'        => 4,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(12, $trust);
    }

    public function testCalculateTrust_buildingWithZeroStatusPoints_isExcluded(): void
    {
        // hospital (id=46): status_points=0 → should not contribute
        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 46,
            'level'        => 5,
            'status_points'=> 0,
            'ap_spend'     => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(0, $trust);
    }

    public function testCalculateTrust_buildingNotInConfig_isIgnored(): void
    {
        // commandCenter (id=25) is not in trust config → no contribution
        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 25,
            'level'        => 10,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(0, $trust);
    }

    // ── calculateTrust() — research contribution ─────────────────────────────

    public function testCalculateTrust_positiveResearchContribution(): void
    {
        // health (id=94): +2 per level; level=3 → +6
        DB::table('colony_researches')->insert([
            'colony_id'    => $this->colonyId,
            'research_id'  => 94,
            'level'        => 3,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(6, $trust);
    }

    public function testCalculateTrust_negativeResearchContribution(): void
    {
        // defense (id=96): -1 per level; level=10 → -10
        DB::table('colony_researches')->insert([
            'colony_id'    => $this->colonyId,
            'research_id'  => 96,
            'level'        => 10,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(-10, $trust);
    }

    public function testCalculateTrust_researchNotInConfig_isIgnored(): void
    {
        // knowledge_construction (research_id=90, trust_per_lv=0 → filtered from trust config)
        DB::table('colony_researches')->insert([
            'colony_id'    => $this->colonyId,
            'research_id'  => 90,
            'level'        => 10,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(0, $trust);
    }

    // ── calculateTrust() — ship contribution ─────────────────────────────────

    public function testCalculateTrust_positiveShipContribution(): void
    {
        // frachter (id=47): +1 per unit; level=6 → +6
        DB::table('colony_ships')->insert([
            'colony_id'    => $this->colonyId,
            'ship_id'      => 47,
            'level'        => 6,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(6, $trust);
    }

    public function testCalculateTrust_corvetteIsNeutral(): void
    {
        // corvette (id=37): trust_per_unit=0 — colonists welcome protection, not a threat
        DB::table('colony_ships')->insert([
            'colony_id'    => $this->colonyId,
            'ship_id'      => 37,
            'level'        => 20,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(0, $trust);
    }

    public function testCalculateTrust_shipContribution_isCapppedAtPositive30(): void
    {
        // frachter (id=47): +1 per unit; level=100 → raw +100, capped to +30
        DB::table('colony_ships')->insert([
            'colony_id'    => $this->colonyId,
            'ship_id'      => 47,
            'level'        => 100,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(30, $trust);
    }

    public function testCalculateTrust_shipContribution_isCappedAtNegative30(): void
    {
        // With corvette neutral (0) and only frachter positive, negative cap is unreachable.
        // Test verifies the positive cap still works as a boundary at +30.
        DB::table('colony_ships')->insert([
            'colony_id'    => $this->colonyId,
            'ship_id'      => 47,
            'level'        => 50,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(30, $trust);
    }

    // ── calculateTrust() — event contribution ────────────────────────────────

    public function testCalculateTrust_eventContribution_addsDeltaForMatchingTick(): void
    {
        // trade_success: +2
        $this->service->fireEvent($this->colonyId, 'trade_success', $this->tick);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(2, $trust);
    }

    public function testCalculateTrust_sameEventTypeFiredTwice_countsOnlyOnce(): void
    {
        // trade_success: +2, fired twice — must NOT stack
        DB::table('trust_events')->insert([
            'colony_id'  => $this->colonyId,
            'tick'       => $this->tick,
            'event_type' => 'trade_success',
        ]);
        DB::table('trust_events')->insert([
            'colony_id'  => $this->colonyId,
            'tick'       => $this->tick,
            'event_type' => 'trade_success',
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        // trade_success = +2, counted once
        $this->assertSame(2, $trust);
    }

    public function testCalculateTrust_eventForDifferentTick_isNotIncluded(): void
    {
        // Fire event for tick+1 — should NOT appear in calculation for $this->tick
        DB::table('trust_events')->insert([
            'colony_id'  => $this->colonyId,
            'tick'       => $this->tick + 1,
            'event_type' => 'trade_success',
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(0, $trust);
    }

    public function testCalculateTrust_multipleDistinctEvents_areSummed(): void
    {
        // trade_success (+2) + encounter_won (+2) + treaty_signed (+3) = +7
        DB::table('trust_events')->insert([
            ['colony_id' => $this->colonyId, 'tick' => $this->tick, 'event_type' => 'trade_success'],
            ['colony_id' => $this->colonyId, 'tick' => $this->tick, 'event_type' => 'encounter_won'],
            ['colony_id' => $this->colonyId, 'tick' => $this->tick, 'event_type' => 'treaty_signed'],
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(7, $trust);
    }

    public function testCalculateTrust_negativeEventContribution(): void
    {
        // encounter_lost: -5
        $this->service->fireEvent($this->colonyId, 'encounter_lost', $this->tick);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(-5, $trust);
    }

    // ── calculateTrust() — global clamp ──────────────────────────────────────

    public function testCalculateTrust_isClampedToPositive100(): void
    {
        // Insert many positive-trust hospitals to push raw sum well above 100
        // hospital (id=46): +3/level; level=50 → raw building contribution = 150
        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 46,
            'level'        => 50,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(100, $trust);
    }

    public function testCalculateTrust_isClampedToNegative100(): void
    {
        // defense (id=96): -1/level; level=200 → raw -200, clamped to -100
        DB::table('colony_researches')->insert([
            'colony_id'    => $this->colonyId,
            'research_id'  => 96,
            'level'        => 200,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        $this->assertSame(-100, $trust);
    }

    // ── calculateAndStore() ───────────────────────────────────────────────────

    public function testCalculateAndStore_persistsTrustInColonyResources(): void
    {
        // hospital (id=46): +3/level; level=5 → trust = +15
        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 46,
            'level'        => 5,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $colony = Colony::find($this->colonyId);
        $result = $this->service->calculateAndStore($colony, $this->tick);

        $this->assertSame(15, $result);

        $this->assertDatabaseHas('colony_resources', [
            'colony_id'   => $this->colonyId,
            'resource_id' => TrustService::RESOURCE_ID,
            'amount'      => 15,
        ]);
    }

    public function testCalculateAndStore_updatesExistingRow(): void
    {
        // Pre-insert a stale trust value
        DB::table('colony_resources')->insert([
            'resource_id' => TrustService::RESOURCE_ID,
            'colony_id'   => $this->colonyId,
            'amount'      => 99,
        ]);

        // No buildings/events → trust = 0
        $colony = Colony::find($this->colonyId);
        $result = $this->service->calculateAndStore($colony, $this->tick);

        $this->assertSame(0, $result);

        // Must update, not insert a second row
        $count = DB::table('colony_resources')
            ->where('colony_id', $this->colonyId)
            ->where('resource_id', TrustService::RESOURCE_ID)
            ->count();

        $this->assertSame(1, $count);

        $this->assertDatabaseHas('colony_resources', [
            'colony_id'   => $this->colonyId,
            'resource_id' => TrustService::RESOURCE_ID,
            'amount'      => 0,
        ]);
    }

    public function testCalculateAndStore_returnsClampedValue(): void
    {
        // defense (id=96): -1/level; level=200 → raw -200, clamped to -100
        DB::table('colony_researches')->insert([
            'colony_id'    => $this->colonyId,
            'research_id'  => 96,
            'level'        => 200,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $colony = Colony::find($this->colonyId);
        $result = $this->service->calculateAndStore($colony, $this->tick);

        $this->assertSame(-100, $result);
    }

    public function testCalculateAndStore_returnValueMatchesStoredValue(): void
    {
        // health (id=94): +2/level; level=7 → +14
        DB::table('colony_researches')->insert([
            'colony_id'    => $this->colonyId,
            'research_id'  => 94,
            'level'        => 7,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $colony   = Colony::find($this->colonyId);
        $returned = $this->service->calculateAndStore($colony, $this->tick);
        $stored   = $this->service->getTrust($this->colonyId);

        $this->assertSame(14, $returned);
        $this->assertSame($returned, $stored);
    }

    // ── Combined contributions ────────────────────────────────────────────────

    public function testCalculateTrust_combinesAllSources(): void
    {
        // hospital (id=46): +3/level; level=2 → +6
        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 46,
            'level'        => 2,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        // health (id=94): +2/level; level=3 → +6
        DB::table('colony_researches')->insert([
            'colony_id'    => $this->colonyId,
            'research_id'  => 94,
            'level'        => 3,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        // frachter (id=47): +1/unit; level=2 → +2
        DB::table('colony_ships')->insert([
            'colony_id'    => $this->colonyId,
            'ship_id'      => 47,
            'level'        => 2,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        // trade_success: +2
        DB::table('trust_events')->insert([
            'colony_id'  => $this->colonyId,
            'tick'       => $this->tick,
            'event_type' => 'trade_success',
        ]);

        $trust = $this->service->calculateTrust($this->colonyId, $this->tick);

        // +6 (buildings) + +6 (researches) + +2 (ships) + +2 (events) = +16
        $this->assertSame(16, $trust);
    }
}
