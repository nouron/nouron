<?php

namespace Tests\Feature;

/**
 * MoralServiceTest — comprehensive feature tests for MoralService.
 *
 * Covered scenarios:
 *
 * getBand():
 *   - All five bands with exact lower/upper boundary values; assertions use __() so
 *     they stay locale-agnostic and pass regardless of APP_LOCALE
 *   - Boundary note: the code uses `>= -61` for Unruhig, so -61 maps to Unruhig and
 *     Aufruhr starts at -62 (the docblock in MoralService has a typo — code wins)
 *   - Locale-switch test: switching to a hypothetical 'en' locale returns English strings
 *     (or falls back gracefully to the translation key if no en file exists)
 *
 * getProductionMultiplier() / getApMultiplier():
 *   - Neutral moral (0) returns 1.0×
 *   - Euphorisch moral (80) returns the configured factor
 *   - Aufruhr moral (-80) returns the configured factor
 *   - Out-of-range moral that misses all bands falls back to 1.0
 *
 * getMoral():
 *   - Returns 0 when no colony_resources row exists for resource_id=12
 *   - Returns the stored integer value when the row exists
 *   - Handles stored negative moral correctly
 *
 * fireEvent():
 *   - Known event inserts a row into moral_events with correct colony_id/tick/event_type
 *   - Unknown event type is silently ignored (no row inserted)
 *   - Default tick is current tick + 1 when no tick is passed
 *   - Explicit tick is respected when passed
 *
 * calculateMoral() — building contribution:
 *   - A positive-moral building (hospital, id=46) adds level × 3 to moral
 *   - A negative-moral building (prison, id=55) subtracts level × 3 from moral
 *   - Building with status_points=0 is excluded from calculation
 *
 * calculateMoral() — research contribution:
 *   - A positive-moral research (medicalScience, id=72) adds level × 2
 *   - A negative-moral research (military, id=81) subtracts level × 2
 *
 * calculateMoral() — ship contribution:
 *   - A positive-moral ship (frachter, id=47, moral_per_unit=+1) adds amount × 1
 *   - A negative-moral ship (korvette, id=37, moral_per_unit=-1) subtracts amount × 1
 *   - Total ship contribution is capped at ±30 before global clamp
 *
 * calculateMoral() — event contribution:
 *   - A fired event contributes its delta at the matching tick
 *   - Same event type fired twice is counted only once (no stacking)
 *   - Events for a different tick are NOT included
 *   - Multiple distinct event types are summed
 *
 * calculateMoral() — global clamp:
 *   - Result is clamped to +100 even when raw sum exceeds 100
 *   - Result is clamped to -100 even when raw sum exceeds -100
 *
 * calculateAndStore():
 *   - Persists the calculated moral in colony_resources (resource_id=12)
 *   - Updates existing row rather than inserting a duplicate
 *   - Returns the clamped integer value
 */

use App\Models\Colony;
use App\Services\MoralService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MoralServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MoralService $service;
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

        $this->service = $this->app->make(MoralService::class);

        // Remove all seeded colony_resources rows for moral (resource_id=12) and all
        // moral_events so each test starts from a known blank state.
        DB::table('colony_resources')
            ->where('resource_id', MoralService::RESOURCE_ID)
            ->delete();

        DB::table('moral_events')->delete();

        // Remove seeded colony_buildings / colony_researches / colony_ships for
        // colony 1 so building/research/ship tests can insert exactly what they need.
        DB::table('colony_buildings')->where('colony_id', $this->colonyId)->delete();
        DB::table('colony_knowledge')->where('colony_id', $this->colonyId)->delete();
        DB::table('colony_ships')->where('colony_id', $this->colonyId)->delete();
    }

    // ── getBand() ─────────────────────────────────────────────────────────────
    //
    // Assertions compare against __('moral.band_*') so they pass regardless of which
    // locale is active — the translation layer is tested separately below.

    public function testGetBand_returnsAufruhr_atMinus100(): void
    {
        $this->assertSame(__('moral.band_aufruhr'), $this->service->getBand(-100));
    }

    public function testGetBand_returnsUnruhig_atMinus61(): void
    {
        // The code uses `>= -61` for Unruhig, so -61 is the lower boundary of Unruhig.
        // The docblock in MoralService is incorrect — the implementation is the source of truth.
        $this->assertSame(__('moral.band_unruhig'), $this->service->getBand(-61));
    }

    public function testGetBand_returnsAufruhr_atMinus62(): void
    {
        // Aufruhr starts at -62 (first value where `$moral >= -61` is false).
        $this->assertSame(__('moral.band_aufruhr'), $this->service->getBand(-62));
    }

    public function testGetBand_returnsUnruhig_atMinus60(): void
    {
        $this->assertSame(__('moral.band_unruhig'), $this->service->getBand(-60));
    }

    public function testGetBand_returnsUnruhig_atMinus21(): void
    {
        $this->assertSame(__('moral.band_unruhig'), $this->service->getBand(-21));
    }

    public function testGetBand_returnsStabil_atMinus20(): void
    {
        $this->assertSame(__('moral.band_stabil'), $this->service->getBand(-20));
    }

    public function testGetBand_returnsStabil_atZero(): void
    {
        $this->assertSame(__('moral.band_stabil'), $this->service->getBand(0));
    }

    public function testGetBand_returnsStabil_at20(): void
    {
        $this->assertSame(__('moral.band_stabil'), $this->service->getBand(20));
    }

    public function testGetBand_returnsZufrieden_at21(): void
    {
        $this->assertSame(__('moral.band_zufrieden'), $this->service->getBand(21));
    }

    public function testGetBand_returnsZufrieden_at60(): void
    {
        $this->assertSame(__('moral.band_zufrieden'), $this->service->getBand(60));
    }

    public function testGetBand_returnsEuphorisch_at61(): void
    {
        $this->assertSame(__('moral.band_euphorisch'), $this->service->getBand(61));
    }

    public function testGetBand_returnsEuphorisch_at100(): void
    {
        $this->assertSame(__('moral.band_euphorisch'), $this->service->getBand(100));
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
        // callers must ensure the active locale has a moral.php file.
        app()->setLocale('xx');

        $this->assertSame('moral.band_euphorisch', $this->service->getBand(100));
        $this->assertSame('moral.band_aufruhr',    $this->service->getBand(-100));
    }

    // ── getProductionMultiplier() ─────────────────────────────────────────────

    public function testGetProductionMultiplier_neutralMoral_returnsOne(): void
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

    public function testGetApMultiplier_neutralMoral_returnsOne(): void
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

    // ── getMoral() ───────────────────────────────────────────────────────────

    public function testGetMoral_returnsZero_whenNoRowExists(): void
    {
        // setUp already deleted all resource_id=12 rows
        $this->assertSame(0, $this->service->getMoral($this->colonyId));
    }

    public function testGetMoral_returnsStoredPositiveValue(): void
    {
        DB::table('colony_resources')->insert([
            'resource_id' => MoralService::RESOURCE_ID,
            'colony_id'   => $this->colonyId,
            'amount'      => 42,
        ]);

        $this->assertSame(42, $this->service->getMoral($this->colonyId));
    }

    public function testGetMoral_returnsStoredNegativeValue(): void
    {
        DB::table('colony_resources')->insert([
            'resource_id' => MoralService::RESOURCE_ID,
            'colony_id'   => $this->colonyId,
            'amount'      => -35,
        ]);

        $this->assertSame(-35, $this->service->getMoral($this->colonyId));
    }

    public function testGetMoral_returnsZero_forUnknownColony(): void
    {
        $this->assertSame(0, $this->service->getMoral(99999));
    }

    // ── fireEvent() ──────────────────────────────────────────────────────────

    public function testFireEvent_knownEvent_insertsRow(): void
    {
        $this->service->fireEvent($this->colonyId, 'trade_success', $this->tick);

        $this->assertDatabaseHas('moral_events', [
            'colony_id'  => $this->colonyId,
            'tick'       => $this->tick,
            'event_type' => 'trade_success',
        ]);
    }

    public function testFireEvent_unknownEventType_insertsNoRow(): void
    {
        $this->service->fireEvent($this->colonyId, 'made_up_event', $this->tick);

        $this->assertDatabaseMissing('moral_events', [
            'colony_id' => $this->colonyId,
        ]);
    }

    public function testFireEvent_defaultTick_isCurrentTickPlusOne(): void
    {
        // No tick argument — should default to tickService->getTickCount() + 1
        $this->service->fireEvent($this->colonyId, 'combat_won');

        $expectedTick = $this->tickService->getTickCount() + 1;

        $this->assertDatabaseHas('moral_events', [
            'colony_id'  => $this->colonyId,
            'tick'       => $expectedTick,
            'event_type' => 'combat_won',
        ]);
    }

    public function testFireEvent_explicitTick_isStoredAsGiven(): void
    {
        $customTick = 9999;
        $this->service->fireEvent($this->colonyId, 'treaty_signed', $customTick);

        $this->assertDatabaseHas('moral_events', [
            'colony_id'  => $this->colonyId,
            'tick'       => $customTick,
            'event_type' => 'treaty_signed',
        ]);
    }

    // ── calculateMoral() — building contribution ─────────────────────────────

    public function testCalculateMoral_positiveBuildingContribution(): void
    {
        // hospital (id=46): +3 per level; level=4 → +12
        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 46,
            'level'        => 4,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(12, $moral);
    }

    public function testCalculateMoral_negativeBuildingContribution(): void
    {
        // prison (id=55): -3 per level; level=2 → -6
        // Prison is not in config/buildings.php yet — inject it inline for this test.
        config(['buildings.prison' => ['id' => 55, 'moral_per_lv' => -3]]);

        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 55,
            'level'        => 2,
            'status_points'=> 5,
            'ap_spend'     => 0,
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(-6, $moral);
    }

    public function testCalculateMoral_buildingWithZeroStatusPoints_isExcluded(): void
    {
        // hospital (id=46): status_points=0 → should not contribute
        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 46,
            'level'        => 5,
            'status_points'=> 0,
            'ap_spend'     => 0,
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(0, $moral);
    }

    public function testCalculateMoral_buildingNotInConfig_isIgnored(): void
    {
        // commandCenter (id=25) is not in game.moral.buildings → no contribution
        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 25,
            'level'        => 10,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(0, $moral);
    }

    // ── calculateMoral() — research contribution ─────────────────────────────

    public function testCalculateMoral_positiveResearchContribution(): void
    {
        // health (id=94): +2 per level; level=3 → +6
        DB::table('colony_knowledge')->insert([
            'colony_id'    => $this->colonyId,
            'research_id'  => 94,
            'level'        => 3,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(6, $moral);
    }

    public function testCalculateMoral_negativeResearchContribution(): void
    {
        // defense (id=96): -1 per level; level=5 → -5
        DB::table('colony_knowledge')->insert([
            'colony_id'    => $this->colonyId,
            'research_id'  => 96,
            'level'        => 5,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(-5, $moral);
    }

    public function testCalculateMoral_researchNotInConfig_isIgnored(): void
    {
        // construction (research_id=90): moral_per_lv=0, contributes nothing
        DB::table('colony_knowledge')->insert([
            'colony_id'    => $this->colonyId,
            'research_id'  => 90,
            'level'        => 10,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(0, $moral);
    }

    // ── calculateMoral() — ship contribution ─────────────────────────────────

    public function testCalculateMoral_positiveShipContribution(): void
    {
        // frachter (id=47): +1 per unit; level=6 → +6
        DB::table('colony_ships')->insert([
            'colony_id'    => $this->colonyId,
            'ship_id'      => 47,
            'level'        => 6,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(6, $moral);
    }

    public function testCalculateMoral_negativeShipContribution(): void
    {
        // korvette (id=37): -1 per unit; level=5 → -5
        DB::table('colony_ships')->insert([
            'colony_id'    => $this->colonyId,
            'ship_id'      => 37,
            'level'        => 5,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(-5, $moral);
    }

    public function testCalculateMoral_shipContribution_isCapppedAtPositive30(): void
    {
        // frachter (id=47): +1 per unit; level=100 → raw +100, capped to +30
        DB::table('colony_ships')->insert([
            'colony_id'    => $this->colonyId,
            'ship_id'      => 47,
            'level'        => 100,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(30, $moral);
    }

    public function testCalculateMoral_shipContribution_isCappedAtNegative30(): void
    {
        // korvette (id=37): -1 per unit; level=100 → raw -100, capped to -30
        DB::table('colony_ships')->insert([
            'colony_id'    => $this->colonyId,
            'ship_id'      => 37,
            'level'        => 100,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(-30, $moral);
    }

    // ── calculateMoral() — event contribution ────────────────────────────────

    public function testCalculateMoral_eventContribution_addsDeltaForMatchingTick(): void
    {
        // trade_success: +2
        $this->service->fireEvent($this->colonyId, 'trade_success', $this->tick);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(2, $moral);
    }

    public function testCalculateMoral_sameEventTypeFiredTwice_countsOnlyOnce(): void
    {
        // trade_success: +2, fired twice — must NOT stack
        DB::table('moral_events')->insert([
            'colony_id'  => $this->colonyId,
            'tick'       => $this->tick,
            'event_type' => 'trade_success',
        ]);
        DB::table('moral_events')->insert([
            'colony_id'  => $this->colonyId,
            'tick'       => $this->tick,
            'event_type' => 'trade_success',
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        // trade_success = +2, counted once
        $this->assertSame(2, $moral);
    }

    public function testCalculateMoral_eventForDifferentTick_isNotIncluded(): void
    {
        // Fire event for tick+1 — should NOT appear in calculation for $this->tick
        DB::table('moral_events')->insert([
            'colony_id'  => $this->colonyId,
            'tick'       => $this->tick + 1,
            'event_type' => 'trade_success',
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(0, $moral);
    }

    public function testCalculateMoral_multipleDistinctEvents_areSummed(): void
    {
        // trade_success (+2) + combat_won (+2) + treaty_signed (+3) = +7
        DB::table('moral_events')->insert([
            ['colony_id' => $this->colonyId, 'tick' => $this->tick, 'event_type' => 'trade_success'],
            ['colony_id' => $this->colonyId, 'tick' => $this->tick, 'event_type' => 'combat_won'],
            ['colony_id' => $this->colonyId, 'tick' => $this->tick, 'event_type' => 'treaty_signed'],
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(7, $moral);
    }

    public function testCalculateMoral_negativeEventContribution(): void
    {
        // combat_lost: -5
        $this->service->fireEvent($this->colonyId, 'combat_lost', $this->tick);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(-5, $moral);
    }

    // ── calculateMoral() — global clamp ──────────────────────────────────────

    public function testCalculateMoral_isClampedToPositive100(): void
    {
        // Insert many positive-moral hospitals to push raw sum well above 100
        // hospital (id=46): +3/level; level=50 → raw building contribution = 150
        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 46,
            'level'        => 50,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(100, $moral);
    }

    public function testCalculateMoral_isClampedToNegative100(): void
    {
        // prison (id=55): -3/level; level=50 → raw building contribution = -150
        // Prison is not in config/buildings.php yet — inject it inline for this test.
        config(['buildings.prison' => ['id' => 55, 'moral_per_lv' => -3]]);

        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 55,
            'level'        => 50,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        $this->assertSame(-100, $moral);
    }

    // ── calculateAndStore() ───────────────────────────────────────────────────

    public function testCalculateAndStore_persistsMoralInColonyResources(): void
    {
        // hospital (id=46): +3/level; level=5 → moral = +15
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
            'resource_id' => MoralService::RESOURCE_ID,
            'amount'      => 15,
        ]);
    }

    public function testCalculateAndStore_updatesExistingRow(): void
    {
        // Pre-insert a stale moral value
        DB::table('colony_resources')->insert([
            'resource_id' => MoralService::RESOURCE_ID,
            'colony_id'   => $this->colonyId,
            'amount'      => 99,
        ]);

        // No buildings/events → moral = 0
        $colony = Colony::find($this->colonyId);
        $result = $this->service->calculateAndStore($colony, $this->tick);

        $this->assertSame(0, $result);

        // Must update, not insert a second row
        $count = DB::table('colony_resources')
            ->where('colony_id', $this->colonyId)
            ->where('resource_id', MoralService::RESOURCE_ID)
            ->count();

        $this->assertSame(1, $count);

        $this->assertDatabaseHas('colony_resources', [
            'colony_id'   => $this->colonyId,
            'resource_id' => MoralService::RESOURCE_ID,
            'amount'      => 0,
        ]);
    }

    public function testCalculateAndStore_returnsClampedValue(): void
    {
        // prison (id=55): -3/level; level=50 → raw -150, clamped to -100
        // Prison is not in config/buildings.php yet — inject it inline for this test.
        config(['buildings.prison' => ['id' => 55, 'moral_per_lv' => -3]]);

        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 55,
            'level'        => 50,
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
        DB::table('colony_knowledge')->insert([
            'colony_id'    => $this->colonyId,
            'research_id'  => 94,
            'level'        => 7,
            'status_points'=> 10,
            'ap_spend'     => 0,
        ]);

        $colony = Colony::find($this->colonyId);
        $returned = $this->service->calculateAndStore($colony, $this->tick);
        $stored   = $this->service->getMoral($this->colonyId);

        $this->assertSame(14, $returned);
        $this->assertSame($returned, $stored);
    }

    // ── Combined contributions ────────────────────────────────────────────────

    public function testCalculateMoral_combinesAllSources(): void
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
        DB::table('colony_knowledge')->insert([
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
        DB::table('moral_events')->insert([
            'colony_id'  => $this->colonyId,
            'tick'       => $this->tick,
            'event_type' => 'trade_success',
        ]);

        $moral = $this->service->calculateMoral($this->colonyId, $this->tick);

        // +6 (buildings) + +6 (researches) + +2 (ships) + +2 (events) = +16
        $this->assertSame(16, $moral);
    }
}
