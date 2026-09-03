<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GameTick — Encounters (GDD §9 "Begegnungen & Gefahren"), Sturm type only.
 *
 * Sturm ("storm") is the first of three danger types (instability, plague follow
 * in later tasks). Pipeline: each Sol, GameTick::processEncounters() first
 * resolves any warning logged at tick-1 (using CURRENT status_points), then rolls
 * a new warning for the current Sol if the colony is not on cooldown.
 *
 * Covered scenarios:
 *  - A successful storm roll writes a `encounter.storm_warning` colony_log entry
 *    (area='encounter'); one Sol later the pipeline resolves it into one of
 *    `encounter.storm_abgewehrt` / `_beschaedigt` / `_kritisch`.
 *  - `defense` knowledge (research_id 96) reduces the storm trigger chance per
 *    config('game.defense_storm_risk_reduction_per_lv') — at Lv5 (20% cumulative
 *    reduction) a roll that would trigger at Lv0 must not trigger at Lv5.
 *
 * Fixture summary (TestSeeder):
 *   Colony 1 (Springfield), user_id=3 (Bart), run #1 rng_seed=NULL → coerced to 0
 *     CC (building_id=25): level=3       — reduced to sole eligible building in some tests
 *     harvester (building_id=27): level=1 — always excluded from storm targeting
 *     housing (28), 31, 46, hangar bays (44×2): leveled to 0 in single-building tests so
 *       CC is the only non-Harvester building with level>0 (deterministic setup)
 *     defense knowledge (research_id=96): level=0 by default
 *
 * Wirkbereich (GDD §9, Owner-Entscheidung 2026-09-03): Sturm is colony-wide — it hits
 * ALL eligible (level>0, non-Harvester) Colony-Zone buildings in the SAME resolution
 * step, each with its OWN SP-based outcome tier (no shared roll). Trust reacts once
 * per storm, using the WORST tier among affected buildings (kritisch > beschaedigt >
 * abgewehrt), not once per building. The per-building `encounter.storm_abgewehrt` /
 * `_beschaedigt` / `_kritisch` colony_log events from the old single-building model
 * are replaced by ONE aggregated `encounter.storm_resolved` event carrying tier counts.
 * See the "── Storm: colony-wide scope ──" section below for the tests that pin this
 * contract down (as of this file's current state, still failing against the
 * single-building implementation — see this task's handoff notes).
 *
 * RNG note: GameTick::rollStorm() seeds via
 *   $seed = $rngSeed + $colony->id * 7919 + $tick * 104729
 * with the same LCG hash as GameTick::seededRoll(). Since the fixture run's
 * rng_seed is NULL (coerced to 0 by `(int) ($run->rng_seed ?? 0)`), the roll for
 * (colony_id=1, tick=T) is a pure function of T. Test 2 below replicates that
 * exact formula locally (see rollFor()) to pre-select tick numbers whose roll
 * falls in a known window — this gives a deterministic (not statistical)
 * assertion without needing to control the RNG seed via test doubles.
 *
 * Uses tick numbers 11700–11749.
 */
class GameTickEncounterTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const CC_ID = 25;

    private const DEFENSE_RESEARCH_ID = 96;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Replicates GameTick::seededRoll()'s LCG hash locally so tests can
     * pre-select deterministic tick numbers without reflection into the
     * private method.
     */
    private function seededRoll(int $seed, int $min, int $max): int
    {
        if ($max <= $min) {
            return $min;
        }
        $hash = abs(($seed * 1664525 + 1013904223) & 0x7FFFFFFF);

        return $min + ($hash % ($max - $min + 1));
    }

    /** Roll fraction in [0, 1) for (colony_id=1, tick=$tick, rngSeed=0) — mirrors rollStorm(). */
    private function rollFor(int $tick): float
    {
        $seed = 0 + self::COLONY_ID * 7919 + $tick * 104729;

        return $this->seededRoll($seed, 0, 9999) / 10000;
    }

    /**
     * Mirrors GameTick::encounterLogMatchesColony() — a raw LIKE '%"colony_id":N%'
     * on the JSON string would false-match colony_id=10, 11, 100, … so tests must
     * decode too, not just production code.
     */
    private function colonyLogQuery(): Builder
    {
        return DB::table('colony_log')->where('area', 'encounter');
    }

    private function rowsForColony(Builder $query, int $colonyId): Collection
    {
        return $query->get()->filter(function ($row) use ($colonyId) {
            $params = json_decode($row->parameters, true);

            return is_array($params) && (int) ($params['colony_id'] ?? -1) === $colonyId;
        })->values();
    }

    // ── Storm: colony-wide scope (GDD §9, Owner-Entscheidung 2026-09-03) ────────

    /**
     * Sets up colony 1 with exactly three eligible (level>0, non-Harvester)
     * buildings, each pinned to a different SP ratio so each resolves to a
     * different outcome tier deterministically (all max_status_points=20 in the
     * fixture, see game-reference.md):
     *   CC (25):        level=3, sp=20 → ratio 1.00 → abgewehrt
     *   housing (28):   level=2, sp=10 → ratio 0.50 → beschaedigt
     *   infirmary (46): level=3, sp=5  → ratio 0.25 → kritisch (forces level-down)
     * sciencelab (31) and both hangar bays (44×1/44×2) are leveled to 0 so they
     * are NOT eligible and do not add a fourth data point. Harvester (27) is left
     * at its fixture level (1) specifically to prove it is excluded from storm
     * targeting even though it is eligible by level.
     */
    private function setUpThreeTierStormFixture(): void
    {
        config([
            'game.encounter.storm.base_chance' => 1.0,
            'game.encounter.storm.chance_cap' => 1.0, // default cap (0.10) would clamp base_chance back down
            'game.encounter.cooldown_sols' => 0,
        ]);

        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->whereIn('building_id', [31, 44])
            ->update(['level' => 0]);

        // Ordinary per-tick building decay (processBuildingDecay(), step 3 of the
        // pipeline) runs independently of, and BEFORE, storm resolution on every
        // tick — including the warning tick. Zero it for the three buildings this
        // fixture pins to exact SP ratios, so the storm-specific assertions below
        // aren't polluted by unrelated decay drift over the two ticks used here.
        DB::table('buildings')
            ->whereIn('id', [self::CC_ID, 28, 46, 27])
            ->update(['decay_rate' => 0]);

        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::CC_ID)
            ->update(['level' => 3, 'status_points' => 20]);
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 28)
            ->update(['level' => 2, 'status_points' => 10]);
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 46)
            ->update(['level' => 3, 'status_points' => 5]);
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 27)
            ->update(['level' => 1, 'status_points' => 20]);
    }

    /**
     * The warning no longer references any specific building — a storm targets
     * the whole colony, so there is nothing building-specific to warn about yet.
     */
    public function test_storm_warning_no_longer_references_a_single_building(): void
    {
        $this->setUpThreeTierStormFixture();

        Artisan::call('game:tick', ['--tick' => 11700]); // Sol N: warning fires

        $warning = $this->rowsForColony(
            $this->colonyLogQuery()->where('event', 'encounter.storm_warning')->where('tick', 11700),
            self::COLONY_ID
        )->sortByDesc('id')->first();
        $this->assertNotNull($warning, 'a storm warning colony_log entry must exist after the roll succeeds');

        $params = json_decode($warning->parameters, true);
        $this->assertArrayHasKey('colony_id', $params);
        $this->assertArrayNotHasKey('building_id', $params, 'Sturm is colony-wide now — the warning must not name a single target building');
        $this->assertArrayNotHasKey('instance_id', $params);
    }

    /**
     * Each affected building gets its OWN SP-based outcome tier — a storm is not
     * a single shared roll applied to every building. CC (full SP) is unharmed,
     * housing (half SP) is damaged, infirmary (low SP) is forced to level down.
     * The Harvester is excluded from storm targeting entirely, even though it is
     * eligible by level (it has its own hazard, geological instability).
     */
    public function test_storm_resolution_applies_independent_outcome_tier_per_building(): void
    {
        $this->setUpThreeTierStormFixture();

        Artisan::call('game:tick', ['--tick' => 11700]); // warning
        Artisan::call('game:tick', ['--tick' => 11701]); // resolution

        $cc = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::CC_ID)->first();
        $this->assertSame(20, (int) $cc->status_points, 'CC at full SP (ratio 1.0 >= 0.66) must be abgewehrt — unharmed');
        $this->assertSame(3, (int) $cc->level, 'abgewehrt must not level-down the building');

        $housing = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 28)->first();
        // damaged_sp_loss_pct=0.20, no securityHub mitigation (not built in the fixture): loss = round(20*0.20) = 4.
        $this->assertSame(6, (int) $housing->status_points, 'housing at half SP (ratio 0.5, in [0.33,0.66)) must be beschaedigt — loses 20% of max SP');
        $this->assertSame(2, (int) $housing->level, 'beschaedigt must not level-down the building (SP stays > 0)');

        $infirmary = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 46)->first();
        $this->assertSame(2, (int) $infirmary->level, 'infirmary at low SP (ratio 0.25 < 0.33) must be kritisch — forced level-down (3 -> 2)');
        $this->assertSame(20, (int) $infirmary->status_points, 'a forced level-down resets status_points to the (new) max, per applyLevelDown()');

        $harvester = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 27)->first();
        $this->assertSame(20, (int) $harvester->status_points, 'Harvester must never be a storm target, even though it is eligible by level');
        $this->assertSame(1, (int) $harvester->level, 'Harvester level must be untouched by a colony-wide storm');

        $levelDownEvents = $this->rowsForColony(
            DB::table('colony_log')->where('area', 'techtree')->where('event', 'techtree.level_down')->where('tick', 11701),
            self::COLONY_ID
        );
        $this->assertCount(1, $levelDownEvents, 'exactly one building (infirmary) must have been forced to level down');
    }

    /**
     * Trust must react ONCE per colony-wide storm, not once per affected
     * building — otherwise trust loss would scale with colony size for the same
     * kind of incident. The single trust event uses the WORST tier among the
     * affected buildings (kritisch present here -> colony_threatened), even
     * though two of the three buildings resolved better than that.
     */
    public function test_storm_fires_exactly_one_trust_event_using_the_worst_tier(): void
    {
        $this->setUpThreeTierStormFixture();

        // Ticks chosen so a single-building implementation would pick building
        // index 1 (housing, beschaedigt) as its lone target (see rollFor()'s
        // seed formula) — proving this assertion actually exercises the
        // "worst-among-all" aggregation, not merely re-confirming whichever one
        // building the old single-target code happens to land on.
        Artisan::call('game:tick', ['--tick' => 11702]);
        Artisan::call('game:tick', ['--tick' => 11703]);

        $trustEvents = DB::table('trust_events')
            ->where('colony_id', self::COLONY_ID)
            ->where('tick', 11703)
            ->whereIn('event_type', ['encounter_won', 'encounter_lost', 'colony_threatened'])
            ->get();

        $this->assertCount(1, $trustEvents, 'a colony-wide storm must fire exactly one trust event, regardless of how many buildings were affected');
        $this->assertSame('colony_threatened', $trustEvents->first()->event_type, 'with a kritisch tier among the affected buildings, the single trust event must be the worst one (colony_threatened)');
    }

    /**
     * Without any kritisch tier, the worst-tier trust event must be
     * encounter_lost (from beschaedigt), not encounter_won (from the
     * better-off abgewehrt buildings) and not colony_threatened.
     */
    public function test_storm_trust_event_is_encounter_lost_when_worst_tier_is_beschaedigt(): void
    {
        $this->setUpThreeTierStormFixture();
        // Pull the infirmary out of kritisch range so beschaedigt is the worst tier present.
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 46)
            ->update(['status_points' => 10]); // ratio 0.5 -> beschaedigt

        // Ticks chosen so a single-building implementation would pick building
        // index 0 (CC, abgewehrt) as its lone target — proving this assertion
        // exercises the aggregation, not the one building the old code happens
        // to land on (see the sibling test's comment for the same rationale).
        Artisan::call('game:tick', ['--tick' => 11701]);
        Artisan::call('game:tick', ['--tick' => 11702]);

        $trustEvents = DB::table('trust_events')
            ->where('colony_id', self::COLONY_ID)
            ->where('tick', 11702)
            ->whereIn('event_type', ['encounter_won', 'encounter_lost', 'colony_threatened'])
            ->get();

        $this->assertCount(1, $trustEvents);
        $this->assertSame('encounter_lost', $trustEvents->first()->event_type);
    }

    /**
     * The resolution must write ONE aggregated colony_log entry summarizing the
     * distribution of tiers over affected buildings (contract: event
     * `encounter.storm_resolved`, area `encounter`, parameters `colony_id`,
     * `counts` (abgewehrt/beschaedigt/kritisch), `trust_event`) — not one row
     * per building using the old per-building event names
     * (`encounter.storm_abgewehrt` / `_beschaedigt` / `_kritisch`), which must
     * no longer be written at all now that Sturm is colony-wide.
     */
    public function test_storm_resolution_writes_a_single_aggregated_log_entry(): void
    {
        $this->setUpThreeTierStormFixture();

        Artisan::call('game:tick', ['--tick' => 11700]);
        Artisan::call('game:tick', ['--tick' => 11701]);

        $oldStyleEvents = $this->rowsForColony(
            $this->colonyLogQuery()
                ->whereIn('event', ['encounter.storm_abgewehrt', 'encounter.storm_beschaedigt', 'encounter.storm_kritisch'])
                ->where('tick', 11701),
            self::COLONY_ID
        );
        $this->assertCount(0, $oldStyleEvents, 'the old per-building event names must not be written anymore under the colony-wide model');

        $resolved = $this->rowsForColony(
            $this->colonyLogQuery()->where('event', 'encounter.storm_resolved')->where('tick', 11701),
            self::COLONY_ID
        );
        $this->assertCount(1, $resolved, 'exactly one aggregated encounter.storm_resolved entry must exist, not one per affected building');

        $params = json_decode($resolved->first()->parameters, true);
        $this->assertSame(self::COLONY_ID, $params['colony_id']);
        $this->assertSame(['abgewehrt' => 1, 'beschaedigt' => 1, 'kritisch' => 1], $params['counts']);
        $this->assertSame('colony_threatened', $params['trust_event']);
    }

    /**
     * No warning is created when the colony has zero eligible (non-Harvester,
     * level>0) buildings — the roll is skipped entirely.
     */
    public function test_storm_roll_skipped_when_colony_has_no_eligible_buildings(): void
    {
        config([
            'game.encounter.storm.base_chance' => 1.0,
            'game.encounter.storm.chance_cap' => 1.0,
            'game.encounter.cooldown_sols' => 0,
        ]);

        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', '!=', 27)
            ->update(['level' => 0]);

        Artisan::call('game:tick', ['--tick' => 11702]);

        $warning = $this->rowsForColony(
            $this->colonyLogQuery()->where('event', 'encounter.storm_warning')->where('tick', 11702),
            self::COLONY_ID
        )->first();
        $this->assertNull($warning, 'No eligible buildings must mean no storm warning, even with base_chance=1.0');
    }

    // ── Defense knowledge reduces trigger probability ────────────────────────

    /**
     * With storm.base_chance=0.5, chance_per_building=0, chance_cap=0.5, the
     * trigger chance is a flat 0.5 at defense Lv0 and 0.5 × (1 - 0.20) = 0.4 at
     * defense Lv5 (cumulative curve [1=>3,2=>5,3=>5,4=>4,5=>3] = 20%).
     *
     * Ticks 11718, 11721, 11722, 11741 were pre-selected (see rollFor()) because
     * their roll for (colony_id=1, rngSeed=0) falls in [0.4, 0.5): below the Lv0
     * chance (must trigger) but at/above the Lv5 chance (must not trigger). This
     * is a deterministic partition, not a statistical sample — every tick in the
     * set is expected to flip outcome, none are expected to behave the same.
     */
    public function test_defense_knowledge_reduces_storm_trigger_probability(): void
    {
        $curatedTicks = [11718, 11721, 11722, 11741];
        foreach ($curatedTicks as $t) {
            $this->assertGreaterThanOrEqual(0.4, $this->rollFor($t), "tick {$t} roll must be >= 0.4 (test fixture assumption)");
            $this->assertLessThan(0.5, $this->rollFor($t), "tick {$t} roll must be < 0.5 (test fixture assumption)");
        }

        config([
            'game.encounter.storm.base_chance' => 0.5,
            'game.encounter.storm.chance_per_building' => 0.0,
            'game.encounter.storm.chance_cap' => 0.5,
            'game.encounter.cooldown_sols' => 0,
        ]);

        // Scenario A: defense Lv0 (fixture default) — every curated tick must trigger.
        DB::table('colony_researches')
            ->where('colony_id', self::COLONY_ID)->where('research_id', self::DEFENSE_RESEARCH_ID)
            ->update(['level' => 0]);

        foreach ($curatedTicks as $t) {
            Artisan::call('game:tick', ['--tick' => $t]);
        }

        $lv0Count = $this->rowsForColony(
            $this->colonyLogQuery()->where('event', 'encounter.storm_warning')->whereIn('tick', $curatedTicks),
            self::COLONY_ID
        )->count();
        $this->assertEquals(count($curatedTicks), $lv0Count, 'At defense Lv0 (chance 0.5) every curated tick must trigger a storm warning');

        // Scenario B: defense Lv5 — same ticks must NOT trigger (chance drops to 0.4).
        DB::table('colony_log')->where('area', 'encounter')->whereIn('tick', $curatedTicks)->delete();
        DB::table('colony_researches')
            ->where('colony_id', self::COLONY_ID)->where('research_id', self::DEFENSE_RESEARCH_ID)
            ->update(['level' => 5]);

        foreach ($curatedTicks as $t) {
            Artisan::call('game:tick', ['--tick' => $t]);
        }

        $lv5Count = $this->rowsForColony(
            $this->colonyLogQuery()->where('event', 'encounter.storm_warning')->whereIn('tick', $curatedTicks),
            self::COLONY_ID
        )->count();
        $this->assertEquals(0, $lv5Count, 'At defense Lv5 (chance 0.4) none of the curated ticks may trigger a storm warning');
        $this->assertLessThan($lv0Count, $lv5Count, 'Lv5 trigger count must be strictly lower than Lv0');
    }

    // ── Geologische Instabilität ─────────────────────────────────────────────

    private const HARVESTER_ID = 27;

    private const GEOLOGY_RESEARCH_ID = 92;

    /**
     * Instability has no warning/resolution split: a successful roll directly
     * writes pending_until_tick onto the Harvester row within the same tick call.
     */
    public function test_geological_instability_causes_harvester_production_outage(): void
    {
        config([
            'game.encounter.instability.chance_per_sol_since_relocation' => 1.0,
            'game.encounter.instability.chance_cap' => 1.0,
            'game.encounter.cooldown_sols' => 0,
        ]);

        // sols_since_relocation × 1.0 ≥ 1.0 for any tick > placed_at_tick, so a
        // relocation far in the past guarantees min($cap, ...) saturates at 1.0.
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER_ID)
            ->update(['placed_at_tick' => 11000]);

        $this->artisan('game:tick', ['--tick' => 11800])->assertExitCode(0);

        $harvester = DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER_ID)
            ->first();
        $this->assertNotNull($harvester->instability_outage_until_tick, 'Harvester must be put into a production-outage state (instability_outage_until_tick set)');
        $this->assertEquals(11800 + config('game.encounter.instability.outage_sols'), (int) $harvester->instability_outage_until_tick);
        $this->assertNull($harvester->pending_until_tick, 'instability must not set pending_until_tick — that field means "relocating" and must stay free so the player can still relocate during the outage');
    }

    public function test_geological_instability_skipped_when_harvester_never_relocated(): void
    {
        config([
            'game.encounter.instability.chance_per_sol_since_relocation' => 1.0,
            'game.encounter.instability.chance_cap' => 1.0,
            'game.encounter.cooldown_sols' => 0,
        ]);

        // Fixture default: placed_at_tick is NULL (never relocated) — no trigger possible.
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER_ID)
            ->update(['placed_at_tick' => null]);

        $this->artisan('game:tick', ['--tick' => 11801])->assertExitCode(0);

        $harvester = DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER_ID)
            ->first();
        $this->assertNull($harvester->instability_outage_until_tick, 'A Harvester that was never relocated must not roll for instability');
    }

    /**
     * geology knowledge (research_id 92) reduces the instability trigger chance per
     * config('game.geology_instability_risk_reduction_per_lv') — same mechanism as
     * defense/storm. At Lv5 the cumulative reduction is 20% ([1=>3,2=>5,3=>5,4=>4,5=>3]).
     * With chance_per_sol_since_relocation and chance_cap tuned so the pre-reduction
     * chance is a flat 0.5, Lv0 gives 0.5 and Lv5 gives 0.5 × 0.8 = 0.4 — mirrors the
     * storm/defense test's curated-tick approach, but via rollInstability()'s own seed
     * formula ($rngSeed + colony_id * 15485863 + tick * 32452843).
     */
    public function test_geology_knowledge_reduces_instability_trigger_probability(): void
    {
        $rollFor = function (int $tick): float {
            $seed = 0 + self::COLONY_ID * 15485863 + $tick * 32452843;

            return $this->seededRoll($seed, 0, 9999) / 10000;
        };

        $curatedTicks = [];
        for ($t = 11810; $t < 11900 && count($curatedTicks) < 4; $t++) {
            $roll = $rollFor($t);
            if ($roll >= 0.4 && $roll < 0.5) {
                $curatedTicks[] = $t;
            }
        }
        $this->assertCount(4, $curatedTicks, 'test fixture assumption: at least 4 ticks with roll in [0.4, 0.5) must exist in the scanned window');

        config([
            // chance_per_sol_since_relocation × sols_since = 0.5 flat, independent of tick,
            // by setting placed_at_tick so that sols_since is always 1 and the per-sol rate is 0.5.
            'game.encounter.instability.chance_per_sol_since_relocation' => 0.5,
            'game.encounter.instability.chance_cap' => 0.5,
            'game.encounter.cooldown_sols' => 0,
        ]);

        $harvesterOutage = function () {
            return (int) DB::table('colony_buildings')
                ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER_ID)
                ->value('instability_outage_until_tick');
        };

        // Scenario A: geology Lv0 — no colony_researches row is seeded for geology
        // (research_id=92) in the fixture, unlike defense (96), so insert explicitly.
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => self::GEOLOGY_RESEARCH_ID],
            ['level' => 0]
        );

        $lv0Triggers = 0;
        foreach ($curatedTicks as $t) {
            DB::table('colony_buildings')
                ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER_ID)
                ->update(['placed_at_tick' => $t - 1, 'pending_until_tick' => null, 'instability_outage_until_tick' => null]);
            Artisan::call('game:tick', ['--tick' => $t]);
            if ($harvesterOutage() !== 0) {
                $lv0Triggers++;
            }
        }
        $this->assertEquals(count($curatedTicks), $lv0Triggers, 'At geology Lv0 (chance 0.5) every curated tick must trigger instability');

        // Scenario B: geology Lv5 — same ticks must NOT trigger (chance drops to 0.4).
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => self::GEOLOGY_RESEARCH_ID],
            ['level' => 5]
        );

        $lv5Triggers = 0;
        foreach ($curatedTicks as $t) {
            DB::table('colony_buildings')
                ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER_ID)
                ->update(['placed_at_tick' => $t - 1, 'pending_until_tick' => null, 'instability_outage_until_tick' => null]);
            Artisan::call('game:tick', ['--tick' => $t]);
            if ($harvesterOutage() !== 0) {
                $lv5Triggers++;
            }
        }
        $this->assertEquals(0, $lv5Triggers, 'At geology Lv5 (chance 0.4) none of the curated ticks may trigger instability');
        $this->assertLessThan($lv0Triggers, $lv5Triggers, 'Lv5 trigger count must be strictly lower than Lv0');
    }

    // ── Seuchenausbruch (plague) ─────────────────────────────────────────────

    /**
     * Plague is emergent-only (GDD §9): it must never roll on a healthy colony
     * (hunger_streak<3 AND trust>=-20), even at chance_per_sol_when_emergent=1.0 —
     * the gate is hard, not a very-low-probability roll.
     */
    public function test_plague_triggers_only_when_hunger_streak_or_low_trust_condition_met(): void
    {
        config([
            'game.encounter.plague.chance_per_sol_when_emergent' => 1.0,
            'game.encounter.cooldown_sols' => 0,
            'game.encounter.phase1_ramp_sols' => 1, // this test isn't about the ramp; keep it at full strength
        ]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['hunger_streak' => 3]);
        // processFoodConsumption() runs BEFORE processEncounters() in the pipeline and
        // resets hunger_streak to 0 whenever the colony is fed — zero the Organika
        // stock so the colony stays hungry (streak only grows) through that step too.
        DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->update(['amount' => 0]);

        $this->artisan('game:tick')->assertExitCode(0);

        $colony = DB::table('glx_colonies')->where('id', self::COLONY_ID)->first();
        $this->assertNotNull($colony->plague_until_tick, 'plague debuff must trigger when hunger_streak >= 3 and roll succeeds');
    }

    public function test_plague_does_not_trigger_on_a_healthy_colony(): void
    {
        config(['game.encounter.plague.chance_per_sol_when_emergent' => 1.0]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['hunger_streak' => 0]);
        // Fixture default trust for colony 1 (colony_resources resource_id=12) is 0,
        // well above the -20 emergent-trust threshold — healthy colony.

        $this->artisan('game:tick')->assertExitCode(0);

        $colony = DB::table('glx_colonies')->where('id', self::COLONY_ID)->first();
        $this->assertNull($colony->plague_until_tick, 'plague must never roll on a healthy colony — 0% base risk is a hard gate, not just a low chance');
    }

    // ── Onboarding hint (Task 7) ─────────────────────────────────────────────

    /**
     * The one-shot `colony.onboarding_encounter` hint must fire exactly once,
     * on the FIRST danger-type trigger of the run — not again on resolution
     * (storm's second tick) and not again on any subsequent trigger.
     *
     * chance_cap is also forced to 1.0 (matching test 1's established fixture
     * pattern) since the default cap (0.10) would clamp base_chance=1.0 back
     * down and the roll might not actually succeed.
     *
     * The fixture has two real (non-NPC) colonies — colony 1/Bart and colony
     * 2/Homer (see test 1's fixture-summary docblock) — so with base_chance
     * forced globally to 1.0, BOTH colonies' users independently earn the
     * hint. That is correct per-user behavior, not a bug; this test scopes
     * its assertion to colony 1's owner (user 3) to keep the "exactly once"
     * claim about a single user's onboarding, same isolation approach test 1
     * uses for its "other colonies roll too" ambiguity.
     */
    public function test_first_encounter_ever_fires_the_onboarding_hint_once(): void
    {
        config([
            'game.encounter.storm.base_chance' => 1.0,
            'game.encounter.storm.chance_cap' => 1.0,
            'game.encounter.cooldown_sols' => 0,
            'game.encounter.phase1_ramp_sols' => 1, // this test isn't about the ramp; keep it at full strength
        ]);

        $this->artisan('game:tick')->assertExitCode(0);   // warning fires — this is the trigger point, not resolution
        $this->artisan('game:tick')->assertExitCode(0);   // resolution — no second hint here

        $fired = DB::table('colony_log')->where('event', 'onboarding_encounter')->where('user', 3)->count();
        $this->assertSame(1, $fired, 'the onboarding hint must fire exactly once, on the FIRST encounter of the run');
    }

    // ── Phase 1 ramp ─────────────────────────────────────────────────────────

    /**
     * A freshly-landed colony (Sol 1 of Phase 1) has no realistic mitigation
     * (securityHub/geology/defense all hang off the Analytik-Labor, a Phase-2
     * building) against a Sol-30 deadline with only ~5-10 Sol slack — GDD §9's
     * own flagged spiral-risk concern. Trigger chance ramps 0 -> full strength
     * over game.encounter.phase1_ramp_sols (default 15) Sols instead of applying
     * full strength from Sol 1.
     *
     * base_chance/chance_cap forced to 1.0 so the ONLY thing gating the roll is
     * the ramp multiplier. rollFor(1)=0.9047, rollFor(15)=0.3421 — pre-computed
     * locally (see rollFor()) for colony_id=1, rngSeed=0, matching rollStorm()'s
     * exact seed formula.
     */
    public function test_phase1_ramp_dampens_storm_chance_at_sol1_but_not_at_ramp_boundary(): void
    {
        config([
            'game.encounter.storm.base_chance' => 1.0,
            'game.encounter.storm.chance_cap' => 1.0,
            'game.encounter.cooldown_sols' => 0,
            'game.encounter.phase1_ramp_sols' => 15,
        ]);
        DB::table('runs')->where('id', 1)->update(['phase' => 1]);
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->whereNotIn('building_id', [self::CC_ID, 27])
            ->update(['level' => 0]);

        // Sol 1: multiplier = 1/15 ≈ 0.067 -> chance ≈ 0.067, roll 0.9047 >= chance -> no trigger.
        Artisan::call('game:tick', ['--tick' => 1]);
        $sol1Warning = $this->rowsForColony(
            $this->colonyLogQuery()->where('event', 'encounter.storm_warning')->where('tick', 1),
            self::COLONY_ID
        )->first();
        $this->assertNull($sol1Warning, 'Sol 1 of Phase 1 must be dampened enough that a forced chance=1.0 roll still misses');

        // Sol 15 (= ramp boundary): multiplier = 1.0 -> chance = 1.0, any roll < 1.0 triggers.
        Artisan::call('game:tick', ['--tick' => 15]);
        $sol15Warning = $this->rowsForColony(
            $this->colonyLogQuery()->where('event', 'encounter.storm_warning')->where('tick', 15),
            self::COLONY_ID
        )->first();
        $this->assertNotNull($sol15Warning, 'At the ramp boundary the multiplier must be 1.0, so a forced chance=1.0 roll must trigger');
    }

    public function test_phase1_ramp_does_not_apply_in_phase2(): void
    {
        config([
            'game.encounter.storm.base_chance' => 1.0,
            'game.encounter.storm.chance_cap' => 1.0,
            'game.encounter.cooldown_sols' => 0,
            'game.encounter.phase1_ramp_sols' => 15,
        ]);
        DB::table('runs')->where('id', 1)->update(['phase' => 2]);
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->whereNotIn('building_id', [self::CC_ID, 27])
            ->update(['level' => 0]);

        // Sol 1 in Phase 2: no ramp, multiplier = 1.0 -> chance = 1.0, roll 0.9047 < 1.0 -> triggers.
        Artisan::call('game:tick', ['--tick' => 1]);
        $warning = $this->rowsForColony(
            $this->colonyLogQuery()->where('event', 'encounter.storm_warning')->where('tick', 1),
            self::COLONY_ID
        )->first();
        $this->assertNotNull($warning, 'the Phase 1 ramp must not apply once the colony has reached Phase 2');
    }

    // ── health Kenntnis-Beitrag zur Seuchenausbruch-Risikoreduktion (Owner-Entscheidung 2026-08-27) ──

    private const HEALTH_RESEARCH_ID = 94;

    private const INFIRMARY_ID = 46;

    /** Roll fraction in [0, 1) for (colony_id=1, tick=$tick, rngSeed=0) — mirrors rollPlague()'s own seed formula (NOT rollFor()'s storm formula). */
    private function rollForPlague(int $tick): float
    {
        $seed = 0 + self::COLONY_ID * 179424673 + $tick * 32416187;

        return $this->seededRoll($seed, 0, 9999) / 10000;
    }

    /**
     * Scans a tick range at runtime for one whose plague roll falls in
     * [$lowerInclusive, $upperExclusive) — i.e. a roll that WOULD trigger at the
     * lower (higher-reduction) chance but would NOT trigger at the higher
     * (lower-reduction) chance. Unlike this file's rollFor()-based storm/defense
     * tests, which hardcode a pre-computed tick, the returned tick is used
     * directly, not hardcoded — so this test keeps working if the underlying
     * plague/infirmary/health curves ever change.
     */
    private function findPlagueTickInBand(float $lowerInclusive, float $upperExclusive, int $rangeStart = 20000, int $rangeEnd = 20500): int
    {
        for ($tick = $rangeStart; $tick <= $rangeEnd; $tick++) {
            $roll = $this->rollForPlague($tick);
            if ($roll >= $lowerInclusive && $roll < $upperExclusive) {
                return $tick;
            }
        }

        throw new \RuntimeException('No tick found in band ['.$lowerInclusive.','.$upperExclusive.') within the scanned range — widen rangeEnd.');
    }

    public function test_health_knowledge_adds_to_infirmary_plague_reduction(): void
    {
        config([
            'game.encounter.plague.chance_per_sol_when_emergent' => 1.0,
            'game.encounter.cooldown_sols' => 0,
            'game.encounter.phase1_ramp_sols' => 1,
        ]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['hunger_streak' => 3]);
        DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->update(['amount' => 0]);

        // Infirmary at level 3: 3 × 0.08 = 0.24 reduction, health at 0.
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::INFIRMARY_ID)
            ->update(['level' => 3, 'status_points' => 20]);
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => self::HEALTH_RESEARCH_ID],
            ['level' => 0, 'ap_spend' => 0, 'status_points' => 20]
        );

        $infirmaryOnlyReduction = 3 * (float) config('buildings.infirmary.plague_risk_reduction_pct_per_level');
        $healthCurve = config('game.health_plague_risk_reduction_per_lv');
        $healthLv5Reduction = array_sum($healthCurve) / 100;
        $combinedReduction = min(0.50, $infirmaryOnlyReduction + $healthLv5Reduction);

        $this->assertGreaterThan($infirmaryOnlyReduction, $combinedReduction, 'precondition: health must meaningfully add to the reduction (config curve too small otherwise — widen it)');

        // Find a tick whose roll would trigger plague at the infirmary-only chance
        // but NOT at the combined (infirmary+health) chance.
        $chanceInfirmaryOnly = 1.0 * (1 - $infirmaryOnlyReduction);
        $chanceCombined = 1.0 * (1 - $combinedReduction);
        $tick = $this->findPlagueTickInBand($chanceCombined, $chanceInfirmaryOnly);

        // Control run: health still at 0 — plague MUST trigger at this tick (roll < chanceInfirmaryOnly).
        // Tick control: this file's OWN existing pattern (verified against GameTickEncounterTest's
        // storm/instability tests) is simply `Artisan::call('game:tick', ['--tick' => N])` / the
        // `$this->artisan('game:tick', ['--tick' => N])` assertable form — the command accepts the
        // target tick directly as a CLI option, no `runs.current_tick` DB manipulation needed or used
        // anywhere else in this test file. `rngSeed` in rollPlague()'s seed formula comes from
        // `(int) ($run->rng_seed ?? 0)` (GameTick.php line ~151) — testdata.sqlite.sql sets no explicit
        // `rng_seed` for run 1, so it defaults to 0, matching this test's `rollForPlague()` helper.
        $this->artisan('game:tick', ['--tick' => $tick])->assertExitCode(0);
        $colonyAfterControl = DB::table('glx_colonies')->where('id', self::COLONY_ID)->first();
        $this->assertNotNull($colonyAfterControl->plague_until_tick, "control: plague must trigger at tick {$tick} with infirmary-only reduction (precondition for this test to be meaningful)");

        // Reset state, set health to Lv5, re-run the SAME tick — plague must NOT trigger now.
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['plague_until_tick' => null, 'hunger_streak' => 3]);
        DB::table('colony_researches')->where('colony_id', self::COLONY_ID)->where('research_id', self::HEALTH_RESEARCH_ID)
            ->update(['level' => 5]);

        $this->artisan('game:tick', ['--tick' => $tick])->assertExitCode(0);
        $colonyWithHealth = DB::table('glx_colonies')->where('id', self::COLONY_ID)->first();
        $this->assertNull($colonyWithHealth->plague_until_tick, "health Lv5 combined with infirmary must push the reduction high enough to avoid the roll at tick {$tick}");
        $this->assertGreaterThanOrEqual(3, $colonyWithHealth->hunger_streak, 'precondition guard: the plague emergent-gate (hunger_streak>=3) must still hold — otherwise this test would pass for the wrong reason (gate closed, not reduction working)');
    }

    /**
     * The plan's core architectural decision is a SHARED cap over the SUM of
     * infirmary + health reductions — `min($cap, infirmary + health)` — not two
     * independently-capped terms and not an uncapped sum. Infirmary Lv5 (0.40)
     * and health Lv5 (0.20) sum to 0.60, which must clamp to the cap (0.50);
     * the existing test above never reaches the cap (0.24 + 0.20 = 0.44 < 0.50)
     * and therefore cannot distinguish a correct capped formula from a buggy
     * uncapped one.
     */
    public function test_combined_infirmary_and_health_reduction_is_capped_not_summed_uncapped(): void
    {
        config([
            'game.encounter.plague.chance_per_sol_when_emergent' => 1.0,
            'game.encounter.cooldown_sols' => 0,
            'game.encounter.phase1_ramp_sols' => 1,
        ]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['hunger_streak' => 3]);
        DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->update(['amount' => 0]);

        // Infirmary Lv5: 5 x 0.08 = 0.40. Health Lv5: sum(3,5,5,4,3)/100 = 0.20.
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::INFIRMARY_ID)
            ->update(['level' => 5, 'status_points' => 20]);
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => self::HEALTH_RESEARCH_ID],
            ['level' => 5, 'ap_spend' => 0, 'status_points' => 20]
        );

        $perLevel = (float) config('buildings.infirmary.plague_risk_reduction_pct_per_level');
        $cap = (float) config('buildings.infirmary.plague_risk_reduction_cap', 0.50);
        $healthCurve = config('game.health_plague_risk_reduction_per_lv');
        $infirmaryReduction = 5 * $perLevel;
        $healthReduction = array_sum($healthCurve) / 100;
        $uncappedSum = $infirmaryReduction + $healthReduction;

        $this->assertGreaterThan($cap, $uncappedSum, 'precondition: the uncapped sum must exceed the cap for this test to be able to distinguish the two formulas');

        $cappedReduction = min($cap, $uncappedSum);
        $chanceCapped = 1.0 * (1 - $cappedReduction);
        $chanceUncapped = 1.0 * (1 - $uncappedSum);

        // A roll in [$chanceUncapped, $chanceCapped) triggers under the correct
        // capped formula (chance = $chanceCapped) but would NOT trigger under a
        // buggy uncapped-sum formula (chance = $chanceUncapped).
        $tick = $this->findPlagueTickInBand($chanceUncapped, $chanceCapped);

        $this->artisan('game:tick', ['--tick' => $tick])->assertExitCode(0);
        $colony = DB::table('glx_colonies')->where('id', self::COLONY_ID)->first();
        $this->assertNotNull(
            $colony->plague_until_tick,
            "the shared cap ({$cap}) must apply to the SUM of infirmary+health reductions ({$uncappedSum}), not leave it uncapped — plague must still trigger at tick {$tick} under the correct capped formula"
        );
    }
}
