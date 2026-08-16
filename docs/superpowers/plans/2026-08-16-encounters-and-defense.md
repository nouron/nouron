# Begegnungen & Gefahren (GDD §9) + defense-Kenntnis Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement GDD §9 "Begegnungen & Gefahren" (specified, never coded) end-to-end — three danger types (Sturm, Geologische Instabilität, Seuchenausbruch) that damage buildings/Trust or disrupt production, each mitigated by a different existing lever (`securityHub`, `geology`, `infirmary`). Give `defense` its first active effect: reduces Sturm trigger chance, justifying its existing Trust malus.

**Architecture:** A new `EncounterService` computes the outcome tier (Abgewehrt/Beschädigt/Kritisch) from a building's SP% and returns the SP delta + trust event to fire — pure decision logic, no DB writes. `GameTick` owns all state changes (SP updates, level-downs, `colony_log` entries) and a new `processEncounters()` step in the existing per-Sol pipeline. Warning-then-resolution reuses `colony_log` (no new pending-state table): a danger warns at Sol N, resolves at Sol N+1 by reading whatever SP the building has at that point — exactly mirrors the GDD's "1 Sol Vorwarnung" text. Geologische Instabilität's "production outage" reuses the existing `colony_buildings.pending_until_tick` field (already means "no Harvester output until this tick"). Seuchenausbruch's temporary AP-reduction is a new `glx_colonies.plague_until_tick` column, mirroring the existing `hunger_streak` column's per-colony-state pattern.

**Tech Stack:** Laravel 12, PHP 8.2, SQLite, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-08-15-knowledge-effects-and-encounters-design.md` §5 (this plan's binding design authority), plus GDD.md §9 (mechanic source), §14 (Trust-Events, referenced by §9).

## Global Constraints

- **No combat system, no strength values** (§9 explicit) — every outcome derives from the affected building's `status_points` percentage at resolution time, never from a rolled "enemy strength".
- Outcome tiers (unchanged from GDD §9): ≥66% SP → Abgewehrt (`encounter_won`, +2 Trust, minimal/no SP loss); 33–65% → Beschädigt (`encounter_lost`, −4 Trust, SP loss = 20% of `max_status_points`); <33% → Kritisch (`colony_threatened`, −5 Trust, SP loss forces an immediate level-down using the exact same mechanics as `GameTick::processBuildingDecay()`'s level-down block — securityHub recycle included).
- `securityHub`'s existing `event_mitigation_pct` (0.25) already dampens negative Trust-event contributions generically in `TrustService::eventContribution()` — do NOT re-implement Trust dampening. This plan adds the missing half: dampening the SP-loss amount itself by the same 25% when `securityHub` (building_id 53, level > 0) is active for the colony.
- All new bell/curve config arrays are 5-level, bell-shaped (small Lv1/Lv5, peak Lv2-4), delta-per-level, summed via `GameTick::cumulativeCurveYield()` — the established convention from the knowledge-effects branch (`geology_harvester_bonus_per_level`, `agronomy_agrardom_bonus_per_level`).
- Deterministic RNG: every random roll uses `GameTick::seededRoll(seed, min, max)` with a seed derived from `$run->rng_seed` (matches the existing `processHangarMissions`/mission-reward pattern) — never `rand()`/`mt_rand()` directly, so PlaytestBot runs stay reproducible.
- Colonies with `defense`/`geology` Lv0 or no `securityHub`/`infirmary` built must see identical behavior to a version of this feature with mitigation curves at their Lv0 default (0% reduction) — the base danger chances and outcome math must work standalone.
- A cooldown (`game.encounter.cooldown_sols`, default 3) blocks new danger *warnings* for a colony within N Sols of its last *resolved* encounter of any type — GDD's own flagged spiral-risk concern (§9 "⚠️ BALANCE CONCERN").
- PHP code/comments in English, config keys in English, `lang/de/*.php` values in German.
- TDD-Pflicht: every new piece of behavior gets a test first (red, then green) — this is exactly the class of "Game-Mechanik" CLAUDE.md's TDD mandate covers without exception.

---

### Task 1: Config foundation

**Files:**
- Modify: `config/game.php` (trust events, `defense` storm-risk curve, `geology` instability-risk curve, encounter base chances + cooldown)
- Modify: `config/buildings.php` (`infirmary` plague-mitigation fields)
- Modify: `config/knowledge.php` (nothing new needed here — `defense`'s curve lives in `game.php` mirroring `geology_harvester_bonus_per_level`'s existing placement, not in `knowledge.php`, for consistency with that established pattern)

**Interfaces:**
- Produces: all config keys below, consumed by Tasks 3-6.

- [ ] **Step 1: Add trust events**

In `config/game.php`, inside `'trust' => ['events' => [...]]` (the array already containing `'encounter_won' => 2`), add:

```php
            'encounter_lost' => -4,        // damaged outcome (GDD §9, 33-65% SP)
            'colony_threatened' => -5,     // critical outcome (GDD §9, <33% SP)
```

- [ ] **Step 2: Add `defense`'s Sturm-risk-reduction curve**

In `config/game.php`, near `geology_harvester_bonus_per_level`, add:

```php
    // defense Kenntnis-Bonus: reduces Sturm trigger chance (GDD §9, docs/superpowers/
    // specs/2026-08-15-knowledge-effects-and-encounters-design.md §5). Bell-shaped,
    // Σ20% at Lv5, ~17% at Lv4 (spec's "~15-20% bei Lv4" target).
    'defense_storm_risk_reduction_per_lv' => [1 => 3, 2 => 5, 3 => 5, 4 => 4, 5 => 3],
```

- [ ] **Step 3: Add `geology`'s instability-risk-reduction curve (separate from its Regolith bonus)**

Directly below the previous entry:

```php
    // geology Kenntnis: SEPARATE from geology_harvester_bonus_per_level (Regolith
    // production) — this curve reduces Geologische-Instabilität trigger chance (GDD
    // §9), geology's second, independent effect. Same bell shape as defense's curve.
    'geology_instability_risk_reduction_per_lv' => [1 => 3, 2 => 5, 3 => 5, 4 => 4, 5 => 3],
```

- [ ] **Step 4: Add encounter base-chance + cooldown config**

New top-level `'encounter' => [...]` block in `config/game.php`, placed near `'decay'`:

```php
    // GDD §9 "Begegnungen & Gefahren" — first-pass calibration figures (Richtwerte),
    // to be tuned after PlaytestBot runs, same convention as other "erste Fassung"
    // numbers in this file.
    'encounter' => [
        // Cooldown: no new danger WARNING for a colony within N Sols of its last
        // RESOLVED encounter (any type) — GDD §9's own flagged spiral-risk guard.
        'cooldown_sols' => 3,

        'storm' => [
            'base_chance' => 0.02,
            'chance_per_building' => 0.01,   // additive per colony_zone building (excl. Harvester)
            'chance_cap' => 0.10,
        ],
        'instability' => [
            'chance_per_sol_since_relocation' => 0.0015,
            'chance_cap' => 0.05,
            'outage_sols' => 3,               // Harvester produces nothing for N Sols on trigger
        ],
        'plague' => [
            'chance_per_sol_when_emergent' => 0.05,   // only rolled when hunger_streak≥3 or trust<-20
            'debuff_sols' => 5,
            'ap_reduction_pct' => 0.20,        // total AP reduced by this fraction while active
        ],

        // Outcome tiers (GDD §9 table) — SP% thresholds and consequences.
        'damaged_threshold_pct' => 0.66,   // ≥66% SP → Abgewehrt; below → Beschädigt tier starts
        'critical_threshold_pct' => 0.33,  // <33% SP → Kritisch
        'damaged_sp_loss_pct' => 0.20,     // fraction of max_status_points lost on Beschädigt
    ],
```

- [ ] **Step 5: Add `infirmary`'s plague-mitigation fields**

In `config/buildings.php`, inside the `'infirmary' => [...]` block (after `'max_level' => null,`):

```php
        // Reduces Seuchenausbruch trigger chance (GDD §9) — flat per-level, capped.
        // Not a bell curve: infirmary has no max_level, so a 5-slot array doesn't fit;
        // this mirrors decay_rate's own flat-per-level-times-multiplier style instead.
        'plague_risk_reduction_pct_per_level' => 0.08,
        'plague_risk_reduction_cap' => 0.50,
```

- [ ] **Step 6: Verify config loads**

Run: `php artisan config:clear && php artisan tinker --execute="echo json_encode(config('game.encounter'));"`
Expected: prints the encounter config block without error (no PHP syntax error in either file).

- [ ] **Step 7: Commit**

```bash
git add config/game.php config/buildings.php
git commit -m "feat: add config foundation for GDD §9 encounters + defense knowledge effect"
```

---

### Task 2: Schema + Harvester relocation tracking fix

**Files:**
- Create: `database/migrations/2026_08_16_000001_add_plague_until_tick_to_glx_colonies.php`
- Modify: `app/Http/Controllers/Colony/ColonyController.php`
- Test: `tests/Feature/Colony/HarvesterSecondInstanceTest.php` (extend — verify `placed_at_tick` now updates on relocation) or a new focused test if that file's fixtures don't fit; check the file first.

**Interfaces:**
- Produces: `glx_colonies.plague_until_tick` (nullable int, consumed by Task 6), corrected `colony_buildings.placed_at_tick` semantics (now "last placed OR relocated", consumed by Task 5's "Sols seit Relocation" calculation).

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('glx_colonies', function (Blueprint $table) {
            // Seuchenausbruch debuff expiry (GDD §9) — mirrors hunger_streak's
            // per-colony-state pattern already on this table.
            $table->unsignedInteger('plague_until_tick')->nullable()->after('hunger_streak');
        });
    }

    public function down(): void
    {
        Schema::table('glx_colonies', function (Blueprint $table) {
            $table->dropColumn('plague_until_tick');
        });
    }
};
```

- [ ] **Step 2: Run the migration**

Run: `php artisan migrate`
Expected: migration applies cleanly, `glx_colonies.plague_until_tick` column exists.

- [ ] **Step 3: Locate the existing Harvester-move test coverage**

Run: `grep -n "isHarvesterMove\|Harvester.*move\|relocat" tests/Feature/Colony/*.php`

Read whichever file already exercises the Harvester relocation flow (likely `HarvesterSecondInstanceTest.php` or a dedicated relocation test) to match its fixture/colony/user conventions exactly.

- [ ] **Step 4: Write the failing test**

Add a test asserting that relocating an already-placed Harvester updates `placed_at_tick` to the current tick (adapt fixture setup to match the file found in Step 3):

```php
    public function test_harvester_relocation_updates_placed_at_tick(): void
    {
        // Seed an existing Harvester with a stale placed_at_tick, then move it.
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 27)
            ->update(['tile_x' => 1, 'tile_y' => 0, 'placed_at_tick' => 5]);
        $this->ensureBuildableTile(2, 0);   // target tile for the move (adapt helper name to file)

        $this->actingAs($this->bart())
            ->postJson(route('colony.building.place'), ['building_id' => 27, 'q' => 2, 'r' => 0])
            ->assertOk();

        $row = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 27)->first();
        $this->assertGreaterThan(5, (int) $row->placed_at_tick, 'placed_at_tick must advance on relocation, not just on first placement');
    }
```

- [ ] **Step 5: Run test to verify it fails**

Run the filtered test.
Expected: FAIL — `placed_at_tick` stays at 5 (current code only sets it inside the `if ($existingBuilding->tile_x === null)` branch, never on the `$isHarvesterMove` branch).

- [ ] **Step 6: Fix `ColonyController::placeBuilding()`**

Locate the `$isHarvesterMove` block (around line 490-494, inside the `else` branch handling non-fresh-instanced buildings):

```php
                // Harvester move: tile updates, ap_spend unchanged.
                // Relocation takes 1 Sol — no production until arrival.
                if ($isHarvesterMove) {
                    $update['pending_until_tick'] = $this->getTick();
                }
```

Replace with:

```php
                // Harvester move: tile updates, ap_spend unchanged. Relocation takes
                // 1 Sol — no production until arrival. placed_at_tick also advances
                // here (GDD §9 "Geologische Instabilität": risk is keyed off Sols
                // since the LAST relocation, not just the original placement).
                if ($isHarvesterMove) {
                    $update['pending_until_tick'] = $this->getTick();
                    $update['placed_at_tick'] = $this->getTick();
                }
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `php bin/phpunit tests/Feature/Colony/HarvesterSecondInstanceTest.php` (or the actual located file) full-file, to confirm no regression alongside the new test.
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_08_16_000001_add_plague_until_tick_to_glx_colonies.php app/Http/Controllers/Colony/ColonyController.php tests/Feature/Colony/HarvesterSecondInstanceTest.php
git commit -m "feat: add plague_until_tick column + fix placed_at_tick on Harvester relocation"
```

---

### Task 3: `EncounterService` — outcome-tier resolution

**Files:**
- Create: `app/Services/EncounterService.php`
- Test: `tests/Unit/EncounterServiceTest.php`

**Interfaces:**
- Produces: `EncounterService::resolveOutcome(int $statusPoints, int $maxStatusPoints, bool $securityHubActive): array{tier: string, trust_event: string, sp_after: int, forces_level_down: bool}` — consumed by Task 4-6's `GameTick` wiring.

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Unit;

use App\Services\EncounterService;
use Tests\TestCase;

class EncounterServiceTest extends TestCase
{
    public function test_high_sp_resolves_to_abgewehrt(): void
    {
        $service = new EncounterService();
        // 80% SP (≥66% threshold) — Abgewehrt, minimal/no loss.
        $result = $service->resolveOutcome(statusPoints: 16, maxStatusPoints: 20, securityHubActive: false);

        $this->assertSame('abgewehrt', $result['tier']);
        $this->assertSame('encounter_won', $result['trust_event']);
        $this->assertSame(16, $result['sp_after']);
        $this->assertFalse($result['forces_level_down']);
    }

    public function test_mid_sp_resolves_to_beschaedigt_with_20pct_loss(): void
    {
        $service = new EncounterService();
        // 50% SP (33-65% band) — Beschädigt, loses 20% of max (4 of 20).
        $result = $service->resolveOutcome(statusPoints: 10, maxStatusPoints: 20, securityHubActive: false);

        $this->assertSame('beschaedigt', $result['tier']);
        $this->assertSame('encounter_lost', $result['trust_event']);
        $this->assertSame(6, $result['sp_after']);
        $this->assertFalse($result['forces_level_down']);
    }

    public function test_low_sp_resolves_to_kritisch_and_forces_level_down(): void
    {
        $service = new EncounterService();
        // 20% SP (<33%) — Kritisch, forces a level-down.
        $result = $service->resolveOutcome(statusPoints: 4, maxStatusPoints: 20, securityHubActive: false);

        $this->assertSame('kritisch', $result['tier']);
        $this->assertSame('colony_threatened', $result['trust_event']);
        $this->assertTrue($result['forces_level_down']);
    }

    public function test_security_hub_dampens_beschaedigt_sp_loss_by_25_percent(): void
    {
        $service = new EncounterService();
        // Without hub: loses 4 (20% of 20). With hub: loses round(4 * 0.75) = 3.
        $result = $service->resolveOutcome(statusPoints: 10, maxStatusPoints: 20, securityHubActive: true);

        $this->assertSame(7, $result['sp_after']);
    }

    public function test_boundary_at_exactly_66_percent_is_abgewehrt(): void
    {
        $service = new EncounterService();
        $result = $service->resolveOutcome(statusPoints: 13, maxStatusPoints: 20, securityHubActive: false); // exactly 65%... below threshold

        // 13/20 = 0.65, below the 0.66 threshold → Beschädigt, not Abgewehrt.
        $this->assertSame('beschaedigt', $result['tier']);
    }

    public function test_boundary_at_exactly_33_percent_is_beschaedigt_not_kritisch(): void
    {
        $service = new EncounterService();
        $result = $service->resolveOutcome(statusPoints: 7, maxStatusPoints: 20, securityHubActive: false); // 7/20 = 0.35

        $this->assertSame('beschaedigt', $result['tier']);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php bin/phpunit tests/Unit/EncounterServiceTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `EncounterService`**

```php
<?php

namespace App\Services;

/**
 * Resolves a colonist-danger outcome (GDD §9 "Begegnungen & Gefahren") from a
 * building's current status_points — no enemy strength, no combat roll. Pure
 * decision logic: takes state, returns state deltas, writes nothing itself.
 * The caller (GameTick) applies the returned sp_after and, if forces_level_down
 * is true, runs the same level-down mechanics processBuildingDecay() already uses.
 */
class EncounterService
{
    /**
     * @return array{tier: string, trust_event: string, sp_after: int, forces_level_down: bool}
     */
    public function resolveOutcome(int $statusPoints, int $maxStatusPoints, bool $securityHubActive): array
    {
        $ratio = $maxStatusPoints > 0 ? $statusPoints / $maxStatusPoints : 0.0;
        $damagedThreshold = (float) config('game.encounter.damaged_threshold_pct', 0.66);
        $criticalThreshold = (float) config('game.encounter.critical_threshold_pct', 0.33);
        $mitigationPct = $securityHubActive
            ? (float) config('buildings.securityHub.event_mitigation_pct', 0.25)
            : 0.0;

        if ($ratio >= $damagedThreshold) {
            return [
                'tier' => 'abgewehrt',
                'trust_event' => 'encounter_won',
                'sp_after' => $statusPoints,
                'forces_level_down' => false,
            ];
        }

        if ($ratio >= $criticalThreshold) {
            $lossPct = (float) config('game.encounter.damaged_sp_loss_pct', 0.20);
            $loss = (int) round($maxStatusPoints * $lossPct * (1 - $mitigationPct));
            $spAfter = max(0, $statusPoints - $loss);

            return [
                'tier' => 'beschaedigt',
                'trust_event' => 'encounter_lost',
                'sp_after' => $spAfter,
                'forces_level_down' => $spAfter <= 0,
            ];
        }

        return [
            'tier' => 'kritisch',
            'trust_event' => 'colony_threatened',
            'sp_after' => 0,
            'forces_level_down' => true,
        ];
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php bin/phpunit tests/Unit/EncounterServiceTest.php`
Expected: PASS (6 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/EncounterService.php tests/Unit/EncounterServiceTest.php
git commit -m "feat: add EncounterService for GDD §9 outcome-tier resolution"
```

---

### Task 4: Sturm — GameTick wiring

**Files:**
- Modify: `app/Console/Commands/GameTick.php`
- Test: `tests/Feature/GameTick/GameTickEncounterTest.php` (new file — this is a new feature area, no existing file to extend)

**Interfaces:**
- Consumes: `EncounterService::resolveOutcome()` (Task 3), `config('game.encounter.storm.*')`, `config('game.defense_storm_risk_reduction_per_lv')`, `config('game.trust.events.encounter_lost'/'colony_threatened')` (Task 1).
- Produces: `GameTick::processEncounters(int $tick, int $rngSeed): int` (return count of resolved encounters this tick) — called from `handle()`, extended by Tasks 5-6 to also roll/resolve the other two danger types inside the same method.
- Produces: `GameTick::applyLevelDown(object $cb, int $tick, array $maxSPMap, array $buildingNames, array $secHubColonies, array $buildCostMap): void` — extracted from `processBuildingDecay()`'s inline level-down block, reused by this task's Kritisch-tier handling.

- [ ] **Step 1: Extract the level-down block from `processBuildingDecay()`**

This is a pure refactor (no behavior change) — do it first, in isolation, and confirm the existing decay tests still pass before adding any new encounter logic.

In `app/Console/Commands/GameTick.php`, locate `processBuildingDecay()`'s inline block starting at `if ($newStatus <= 0) {` (the level-down branch, roughly lines 543-577 per the pre-refactor file — read the current file to get exact line numbers, they may have shifted).

Extract it into a new private method:

```php
    /**
     * Levels a building down by 1 (min 0), restores its status_points to max, logs
     * a techtree.level_down event, and applies securityHub build-cost recycling if
     * active. Shared by processBuildingDecay() (SP hits 0 from ordinary decay) and
     * processEncounters() (SP hits 0 from a Kritisch-tier danger, GDD §9).
     */
    private function applyLevelDown(
        object $cb,
        int $tick,
        array $maxSPMap,
        array $buildingNames,
        array $secHubColonies,
        array $buildCostMap
    ): void {
        $maxSP = (int) ($maxSPMap[$cb->building_id] ?? 20);
        $newLevel = max(0, $cb->level - 1);
        $where = [
            'colony_id' => $cb->colony_id,
            'building_id' => $cb->building_id,
            'instance_id' => $cb->instance_id,
        ];

        DB::table('colony_buildings')->where($where)->update([
            'level' => $newLevel,
            'status_points' => $maxSP,
        ]);

        $colony = Colony::find($cb->colony_id);
        $this->eventService->createEvent([
            'user' => $colony === null ? 0 : ($colony->user_id ?? 0),
            'tick' => $tick,
            'event' => 'techtree.level_down',
            'area' => 'techtree',
            'parameters' => json_encode([
                'entity_type' => 'building',
                'entity_name' => $buildingNames[$cb->building_id] ?? '',
                'new_level' => $newLevel,
                'tech_id' => $cb->building_id,
                'colony_id' => $cb->colony_id,
            ]),
        ]);

        if (isset($secHubColonies[$cb->colony_id]) && isset($buildCostMap[$cb->building_id])) {
            $recyclePct = (float) config('buildings.securityHub.recycle_pct', 0.10);
            foreach ($buildCostMap[$cb->building_id] as $resId => $baseAmount) {
                $returned = (int) max(1, floor($baseAmount * $recyclePct));
                DB::table('colony_resources')->updateOrInsert(
                    ['colony_id' => $cb->colony_id, 'resource_id' => $resId],
                    ['amount' => DB::raw("amount + {$returned}")]
                );
            }
        }
    }
```

Replace the original inline block in `processBuildingDecay()` with a call to `$this->applyLevelDown($cb, $tick, $maxSPMap, $buildingNames, $secHubColonies, $buildCostMap); $levelled++;` (keep the surrounding `if ($newStatus <= 0) { ... } else { ... }` structure, just swap the body).

- [ ] **Step 2: Run existing decay tests to confirm the refactor is behavior-preserving**

Run: `php bin/phpunit --filter=Decay tests/` (or the specific decay test file if `grep -rln "processBuildingDecay\|techtree.level_down" tests/` finds one)
Expected: PASS, identical to before the refactor.

- [ ] **Step 3: Commit the refactor separately**

```bash
git add app/Console/Commands/GameTick.php
git commit -m "refactor: extract applyLevelDown() from processBuildingDecay for reuse by encounters"
```

- [ ] **Step 4: Write the failing test for Sturm**

Create `tests/Feature/GameTick/GameTickEncounterTest.php` — read a neighboring `tests/Feature/GameTick/*.php` file first (e.g. `GameTickResourceGenerationTest.php` from the knowledge-effects branch) to match its exact fixture/colony/user/`artisan('game:tick')` conventions, then adapt:

```php
    public function test_storm_warning_then_resolution_damages_a_building_two_sols_later(): void
    {
        // Force the storm roll to succeed by maxing out building count and chance_cap
        // via config override, and force a specific target via a single-building colony.
        config(['game.encounter.storm.base_chance' => 1.0, 'game.encounter.cooldown_sols' => 0]);
        // (adapt to whatever single-building-colony fixture is cheapest in the located file —
        //  the goal is a colony with exactly one non-Harvester building so target selection
        //  is deterministic without needing to control the RNG seed precisely)

        $this->artisan('game:tick')->assertExitCode(0);   // Sol N: warning fires
        $warning = DB::table('colony_log')->where('area', 'encounter')->where('event', 'encounter.storm_warning')->latest('id')->first();
        $this->assertNotNull($warning, 'a storm warning colony_log entry must exist after the roll succeeds');

        $this->artisan('game:tick')->assertExitCode(0);   // Sol N+1: resolution fires
        $resolution = DB::table('colony_log')->where('area', 'encounter')->whereIn('event', ['encounter.storm_abgewehrt', 'encounter.storm_beschaedigt', 'encounter.storm_kritisch'])->latest('id')->first();
        $this->assertNotNull($resolution, 'a storm resolution colony_log entry must exist one Sol after the warning');
    }

    public function test_defense_knowledge_reduces_storm_trigger_probability(): void
    {
        // With defense at Lv5 (20% cumulative reduction) and storm chance_cap forced
        // low enough that the reduction can flip a roll from trigger to no-trigger,
        // assert fewer storm_warning entries accumulate over N ticks than at defense Lv0.
        // (Exact assertion shape depends on the located test file's RNG-control conventions —
        //  if a deterministic seed-forcing helper already exists there, prefer it over a
        //  statistical multi-run assertion.)
    }
```

Note in your report if the deterministic single-building-colony setup or the defense-reduction test needs a different shape once you see the actual fixtures — this is the kind of test where "read the real file first, adapt the template" (per this plan's Global Constraints and established convention from the knowledge-effects branch) matters most.

- [ ] **Step 5: Run test to verify it fails**

Run the filtered test.
Expected: FAIL — no `processEncounters()` method exists yet, no `encounter.*` colony_log entries are ever created.

- [ ] **Step 6: Implement `processEncounters()` — Sturm only for this task**

Add to `GameTick.php`, wired into `handle()`'s `DB::transaction()` closure right after `processFoodConsumption()` and before `calculateTrust()`:

```php
            $n = $this->processEncounters($tick, (int) ($run->rng_seed ?? 0));
            $this->line("  Encounters processed:     {$n}");
```

New private method:

```php
    /**
     * GDD §9 "Begegnungen & Gefahren" — per-Sol encounter pipeline. Two phases per
     * call: (1) resolve any encounter WARNED at tick-1 for each colony (reads
     * colony_log for area='encounter', event='...instability_warning'|'storm_warning'
     * etc. at tick-1, resolves using CURRENT status_points), (2) roll new warnings
     * for tick, respecting the cooldown against each colony's last resolved encounter.
     * Danger-type-specific roll/resolve logic (rollStorm, rollInstability — Task 5,
     * rollPlague — Task 6) is dispatched from here; this task implements Sturm.
     *
     * @return int number of encounters resolved this call (warnings + resolutions)
     */
    private function processEncounters(int $tick, int $rngSeed): int
    {
        $processed = 0;
        $encounterService = new EncounterService();
        $cooldownSols = (int) config('game.encounter.cooldown_sols', 3);
        $securityHubColonies = DB::table('colony_buildings')
            ->where('building_id', 53)
            ->where('level', '>', 0)
            ->pluck('colony_id')
            ->flip()
            ->all();

        $colonies = Colony::all();

        foreach ($colonies as $colony) {
            // Phase 1: resolve yesterday's storm warning, if any.
            $processed += $this->resolveStormWarning($colony, $tick, $encounterService, $securityHubColonies);

            // Phase 2: roll a new storm warning for today, if not on cooldown.
            if (! $this->encounterOnCooldown($colony->id, $tick, $cooldownSols)) {
                $processed += $this->rollStorm($colony, $tick, $rngSeed);
            }
        }

        return $processed;
    }

    /**
     * Whether this colony resolved ANY encounter within the last $cooldownSols Sols.
     * "Resolved" = any encounter.* colony_log event that is NOT a warning — storm's
     * three outcome-tier events, plus instability/plague's immediate trigger events
     * (which have no separate warning phase, so the trigger event IS the resolution).
     */
    private function encounterOnCooldown(int $colonyId, int $tick, int $cooldownSols): bool
    {
        if ($cooldownSols <= 0) {
            return false;
        }

        return DB::table('colony_log')
            ->where('area', 'encounter')
            ->where('tick', '>=', $tick - $cooldownSols)
            ->where('parameters', 'like', '%"colony_id":'.$colonyId.'%')
            ->where('event', 'not like', '%_warning')
            ->exists();
    }

    private function rollStorm(Colony $colony, int $tick, int $rngSeed): int
    {
        $buildingCount = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('level', '>', 0)
            ->where('building_id', '!=', BuildingId::Harvester->value)
            ->count();

        if ($buildingCount === 0) {
            return 0;
        }

        $cfg = config('game.encounter.storm', []);
        $baseChance = (float) ($cfg['base_chance'] ?? 0.02);
        $perBuilding = (float) ($cfg['chance_per_building'] ?? 0.01);
        $cap = (float) ($cfg['chance_cap'] ?? 0.10);

        $defenseId = (int) config('knowledge.defense.id', 96);
        $defenseLevel = (int) DB::table('colony_researches')
            ->where('colony_id', $colony->id)->where('research_id', $defenseId)->value('level');
        $reductionPct = self::cumulativeCurveYield(config('game.defense_storm_risk_reduction_per_lv', []), $defenseLevel) / 100;

        $chance = min($cap, $baseChance + $buildingCount * $perBuilding) * (1 - $reductionPct);

        $seed = $rngSeed + $colony->id * 7919 + $tick * 104729;
        $roll = self::seededRoll($seed, 0, 9999) / 10000;
        if ($roll >= $chance) {
            return 0;
        }

        // Target: 1 random non-Harvester building of the colony zone (deterministic pick).
        $targets = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('level', '>', 0)
            ->where('building_id', '!=', BuildingId::Harvester->value)
            ->orderBy('building_id')->orderBy('instance_id')
            ->get(['building_id', 'instance_id']);
        $targetIdx = self::seededRoll($seed + 1, 0, $targets->count() - 1);
        $target = $targets[$targetIdx];

        $this->eventService->createEvent([
            'user' => $colony->user_id ?? 0,
            'tick' => $tick,
            'event' => 'encounter.storm_warning',
            'area' => 'encounter',
            'parameters' => json_encode([
                'colony_id' => $colony->id,
                'building_id' => $target->building_id,
                'instance_id' => $target->instance_id,
            ]),
        ]);

        return 1;
    }

    private function resolveStormWarning(Colony $colony, int $tick, EncounterService $service, array $securityHubColonies): int
    {
        $warning = DB::table('colony_log')
            ->where('area', 'encounter')->where('event', 'encounter.storm_warning')
            ->where('tick', $tick - 1)
            ->where('parameters', 'like', '%"colony_id":'.$colony->id.'%')
            ->first();

        if (! $warning) {
            return 0;
        }

        $params = json_decode($warning->parameters, true);
        $cb = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', $params['building_id'])
            ->where('instance_id', $params['instance_id'])
            ->first();

        if (! $cb || $cb->level <= 0) {
            return 0;   // building was demolished/relocated between warning and resolution
        }

        $maxSP = (int) DB::table('buildings')->where('id', $cb->building_id)->value('max_status_points') ?: 20;
        $hubActive = isset($securityHubColonies[$colony->id]);
        $outcome = $service->resolveOutcome((int) $cb->status_points, $maxSP, $hubActive);

        DB::table('colony_buildings')
            ->where('colony_id', $colony->id)->where('building_id', $cb->building_id)->where('instance_id', $cb->instance_id)
            ->update(['status_points' => $outcome['sp_after']]);

        $this->trustService->fireEvent($colony->id, $outcome['trust_event'], $tick);

        if ($outcome['forces_level_down']) {
            $maxSPMap = DB::table('buildings')->pluck('max_status_points', 'id')->all();
            $buildingNames = DB::table('buildings')->pluck('name', 'id')->all();
            $buildCostMap = DB::table('building_costs')->whereIn('resource_id', [3, 4, 5])
                ->get()->groupBy('building_id')->map(fn ($rows) => $rows->pluck('amount', 'resource_id')->all())->all();
            $this->applyLevelDown((object) (array) $cb, $tick, $maxSPMap, $buildingNames, $securityHubColonies, $buildCostMap);
        }

        $this->eventService->createEvent([
            'user' => $colony->user_id ?? 0,
            'tick' => $tick,
            'event' => 'encounter.storm_'.$outcome['tier'],
            'area' => 'encounter',
            'parameters' => json_encode([
                'colony_id' => $colony->id,
                'building_id' => $cb->building_id,
                'instance_id' => $cb->instance_id,
                'tier' => $outcome['tier'],
            ]),
        ]);

        return 1;
    }
```

Add `use App\Services\EncounterService;` to the file's imports.

- [ ] **Step 7: Run tests to verify they pass**

Run: `php bin/phpunit tests/Feature/GameTick/GameTickEncounterTest.php`
Expected: PASS.

- [ ] **Step 8: Full regression check**

Run: `php artisan test`
Expected: no new failures beyond the tests this task added.

- [ ] **Step 9: Commit**

```bash
git add app/Console/Commands/GameTick.php tests/Feature/GameTick/GameTickEncounterTest.php
git commit -m "feat: implement Sturm encounter type with defense-knowledge risk reduction"
```

---

### Task 5: Geologische Instabilität — GameTick wiring

**Files:**
- Modify: `app/Console/Commands/GameTick.php`
- Test: `tests/Feature/GameTick/GameTickEncounterTest.php` (same file as Task 4, add methods)

**Interfaces:**
- Consumes: `config('game.encounter.instability.*')`, `config('game.geology_instability_risk_reduction_per_lv')` (Task 1), `colony_buildings.placed_at_tick` (Task 2's fix makes this correctly track "last relocation").
- Produces: extends `processEncounters()` (Task 4) with a second danger-type roll/resolve pair. No new public interface — self-contained addition to the same private method.

- [ ] **Step 1: Write the failing test**

```php
    public function test_geological_instability_causes_harvester_production_outage(): void
    {
        config(['game.encounter.instability.chance_per_sol_since_relocation' => 1.0, 'game.encounter.instability.chance_cap' => 1.0, 'game.encounter.cooldown_sols' => 0]);
        // Set the Harvester's placed_at_tick far enough in the past that
        // chance_per_sol_since_relocation × sols_since ≥ 1.0 guarantees a trigger
        // (adapt exact tick math once you see the located file's tick/fixture setup).

        $this->artisan('game:tick')->assertExitCode(0);

        $harvester = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 27)->first();
        $this->assertNotNull($harvester->pending_until_tick, 'Harvester must be put into a production-outage state (pending_until_tick set)');
    }

    public function test_geology_knowledge_reduces_instability_trigger_probability(): void
    {
        // Same shape as Task 4's defense-reduction test — adapt to whatever
        // RNG-control convention that test settled on.
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run the filtered test.
Expected: FAIL — `pending_until_tick` stays null, no instability logic exists yet.

- [ ] **Step 3: Extend `processEncounters()`**

Add a third pipeline step inside the `foreach ($colonies as $colony)` loop from Task 4, after the storm phase:

```php
            // Geologische Instabilität: tied to the Harvester tile, no warning/
            // resolution split (GDD §9 — production outage triggers immediately,
            // it's not a building-SP encounter, EncounterService's tiers don't apply).
            if (! $this->encounterOnCooldown($colony->id, $tick, $cooldownSols)) {
                $processed += $this->rollInstability($colony, $tick, $rngSeed);
            }
```

New private method:

```php
    /**
     * Geologische Instabilität (GDD §9): unlike Sturm/Seuche, this has no SP-based
     * outcome tier — it directly disrupts Harvester production for N Sols by reusing
     * pending_until_tick (the same field Harvester relocation already uses to mean
     * "no output until this tick"). No warning/resolution split either: it triggers
     * and resolves in the same call, since there's nothing to "defend" against.
     */
    private function rollInstability(Colony $colony, int $tick, int $rngSeed): int
    {
        $harvester = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', BuildingId::Harvester->value)
            ->where('level', '>', 0)
            ->orderBy('instance_id')
            ->first();

        if (! $harvester || $harvester->placed_at_tick === null) {
            return 0;
        }

        // Already in an outage (relocating or a prior instability trigger) — skip.
        if ($harvester->pending_until_tick !== null && (int) $harvester->pending_until_tick >= $tick) {
            return 0;
        }

        $solsSinceRelocation = max(0, $tick - (int) $harvester->placed_at_tick);
        $cfg = config('game.encounter.instability', []);
        $chancePerSol = (float) ($cfg['chance_per_sol_since_relocation'] ?? 0.0015);
        $cap = (float) ($cfg['chance_cap'] ?? 0.05);

        $geologyId = (int) config('knowledge.geology.id', 92);
        $geologyLevel = (int) DB::table('colony_researches')
            ->where('colony_id', $colony->id)->where('research_id', $geologyId)->value('level');
        $reductionPct = self::cumulativeCurveYield(config('game.geology_instability_risk_reduction_per_lv', []), $geologyLevel) / 100;

        $chance = min($cap, $solsSinceRelocation * $chancePerSol) * (1 - $reductionPct);

        $seed = $rngSeed + $colony->id * 15485863 + $tick * 32452843;
        $roll = self::seededRoll($seed, 0, 9999) / 10000;
        if ($roll >= $chance) {
            return 0;
        }

        $outageSols = (int) ($cfg['outage_sols'] ?? 3);
        DB::table('colony_buildings')
            ->where('colony_id', $colony->id)->where('building_id', BuildingId::Harvester->value)->where('instance_id', $harvester->instance_id)
            ->update(['pending_until_tick' => $tick + $outageSols]);

        $this->eventService->createEvent([
            'user' => $colony->user_id ?? 0,
            'tick' => $tick,
            'event' => 'encounter.instability_triggered',
            'area' => 'encounter',
            'parameters' => json_encode([
                'colony_id' => $colony->id,
                'instance_id' => $harvester->instance_id,
                'outage_until_tick' => $tick + $outageSols,
            ]),
        ]);

        return 1;
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php bin/phpunit tests/Feature/GameTick/GameTickEncounterTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/GameTick.php tests/Feature/GameTick/GameTickEncounterTest.php
git commit -m "feat: implement Geologische Instabilität with geology-knowledge risk reduction"
```

---

### Task 6: Seuchenausbruch — GameTick wiring + AP-reduction debuff

**Files:**
- Modify: `app/Console/Commands/GameTick.php`
- Modify: `app/Services/AdvisorService.php`
- Test: `tests/Feature/GameTick/GameTickEncounterTest.php` (same file, add methods), `tests/Unit/AdvisorServiceApBreakdownTest.php` or wherever `getTotalActionPoints`/`getApBreakdown` is already tested (locate first)

**Interfaces:**
- Consumes: `glx_colonies.hunger_streak` (existing), `TrustService::getTrust()` (existing), `config('game.encounter.plague.*')`, `config('buildings.infirmary.plague_risk_reduction_*')` (Task 1), `glx_colonies.plague_until_tick` (Task 2).
- Produces: `AdvisorService::getApBreakdown()` now applies a temporary reduction when `plague_until_tick` is active — this changes the `total` figure the resource-bar AP chip reads, so re-verify `tests/Feature/*Advisor*Test.php` stays green.

- [ ] **Step 1: Write the failing tests**

```php
    public function test_plague_triggers_only_when_hunger_streak_or_low_trust_condition_met(): void
    {
        config(['game.encounter.plague.chance_per_sol_when_emergent' => 1.0, 'game.encounter.cooldown_sols' => 0]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['hunger_streak' => 3]);

        $this->artisan('game:tick')->assertExitCode(0);

        $colony = DB::table('glx_colonies')->where('id', self::COLONY_ID)->first();
        $this->assertNotNull($colony->plague_until_tick, 'plague debuff must trigger when hunger_streak >= 3 and roll succeeds');
    }

    public function test_plague_does_not_trigger_on_a_healthy_colony(): void
    {
        config(['game.encounter.plague.chance_per_sol_when_emergent' => 1.0]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['hunger_streak' => 0]);
        // (also ensure Trust is not below -20 in this fixture — adapt to whatever the
        //  located file's default fixture Trust value is)

        $this->artisan('game:tick')->assertExitCode(0);

        $colony = DB::table('glx_colonies')->where('id', self::COLONY_ID)->first();
        $this->assertNull($colony->plague_until_tick, 'plague must never roll on a healthy colony — 0% base risk is a hard gate, not just a low chance');
    }
```

Add to whichever file already tests `AdvisorService::getApBreakdown()`/`getTotalActionPoints()`:

```php
    public function test_active_plague_debuff_reduces_total_ap_by_configured_percent(): void
    {
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['plague_until_tick' => 999999]);
        $withoutPlague = $this->app->make(AdvisorService::class)->getTotalActionPoints(self::COLONY_ID);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['plague_until_tick' => null]);
        $baseline = $this->app->make(AdvisorService::class)->getTotalActionPoints(self::COLONY_ID);

        // ap_reduction_pct default 0.20 → plague total should be ~80% of baseline.
        $this->assertLessThan($baseline, $withoutPlague);
        $this->assertEqualsWithDelta((int) round($baseline * 0.80), $withoutPlague, 1);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run both filtered test files.
Expected: FAIL — no plague logic exists, `getApBreakdown()` ignores `plague_until_tick`.

- [ ] **Step 3: Extend `processEncounters()`**

Add a fourth pipeline step, after the instability phase:

```php
            // Seuchenausbruch: emergent trigger only (GDD §9) — never rolls on a
            // healthy colony, unlike Sturm/Instabilität which always have a nonzero
            // base chance.
            if (! $this->encounterOnCooldown($colony->id, $tick, $cooldownSols)) {
                $processed += $this->rollPlague($colony, $tick, $rngSeed);
            }
```

New private method:

```php
    /**
     * Seuchenausbruch (GDD §9): emergent, not ambient — only rolls when
     * hunger_streak≥3 OR Trust<-20 (a healthy colony has 0% base risk, hard-gated,
     * not just a very low chance). Resolves immediately (no warning/resolution
     * split, matching instability's shape, not storm's) as a colony_threatened Trust
     * hit plus a temporary AP-reduction debuff via glx_colonies.plague_until_tick.
     */
    private function rollPlague(Colony $colony, int $tick, int $rngSeed): int
    {
        $hungerStreak = (int) DB::table('glx_colonies')->where('id', $colony->id)->value('hunger_streak');
        $trust = $this->trustService->getTrust($colony->id);

        if ($hungerStreak < 3 && $trust >= -20) {
            return 0;   // healthy colony — hard gate, not a chance roll
        }

        $alreadyActive = DB::table('glx_colonies')->where('id', $colony->id)->value('plague_until_tick');
        if ($alreadyActive !== null && (int) $alreadyActive >= $tick) {
            return 0;   // don't stack a second plague on top of an active one
        }

        $chance = (float) config('game.encounter.plague.chance_per_sol_when_emergent', 0.05);

        $infirmaryLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colony->id)->where('building_id', 46)->value('level');
        $perLevel = (float) config('buildings.infirmary.plague_risk_reduction_pct_per_level', 0.08);
        $cap = (float) config('buildings.infirmary.plague_risk_reduction_cap', 0.50);
        $reductionPct = min($cap, $infirmaryLevel * $perLevel);

        $chance *= (1 - $reductionPct);

        $seed = $rngSeed + $colony->id * 179424673 + $tick * 32416187;
        $roll = self::seededRoll($seed, 0, 9999) / 10000;
        if ($roll >= $chance) {
            return 0;
        }

        $debuffSols = (int) config('game.encounter.plague.debuff_sols', 5);
        DB::table('glx_colonies')->where('id', $colony->id)->update(['plague_until_tick' => $tick + $debuffSols]);
        $this->trustService->fireEvent($colony->id, 'colony_threatened', $tick);

        $this->eventService->createEvent([
            'user' => $colony->user_id ?? 0,
            'tick' => $tick,
            'event' => 'encounter.plague_triggered',
            'area' => 'encounter',
            'parameters' => json_encode(['colony_id' => $colony->id, 'debuff_until_tick' => $tick + $debuffSols]),
        ]);

        return 1;
    }
```

- [ ] **Step 4: Apply the AP-reduction debuff in `AdvisorService`**

Read `AdvisorService::getApBreakdown()` in full first to confirm the exact current structure (shown in this plan's research as lines 61-70ish, but confirm before editing). Add the plague check after the existing `$multiplier` calculation, before the `'total'` figure is computed — locate the line that computes `'total' => ...` (likely `(int) round(($baseAp + $advisorAp) * $multiplier)` or similar) and multiply in the plague reduction:

```php
        $trust = $this->trustService->getTrust($colonyId);
        $multiplier = $this->trustService->getApMultiplier($trust);

        $plagueUntilTick = DB::table('glx_colonies')->where('id', $colonyId)->value('plague_until_tick');
        $plagueActive = $plagueUntilTick !== null && (int) $plagueUntilTick >= $this->tickService->getTickCount();
        $plagueMultiplier = $plagueActive ? (1 - (float) config('game.encounter.plague.ap_reduction_pct', 0.20)) : 1.0;
```

Then multiply `$plagueMultiplier` into whatever the final `total` expression already is (read the exact current line before editing — do not guess its form, this plan's earlier research only confirmed the surrounding structure, not the precise final expression).

- [ ] **Step 5: Run tests to verify they pass**

Run both filtered test files, then the full `AdvisorService`-related suite to catch any AP-figure regression.
Expected: PASS.

- [ ] **Step 6: Full regression check**

Run: `php artisan test`
Expected: no new failures.

- [ ] **Step 7: Commit**

```bash
git add app/Console/Commands/GameTick.php app/Services/AdvisorService.php tests/
git commit -m "feat: implement Seuchenausbruch with infirmary risk reduction and AP debuff"
```

---

### Task 7: Onboarding hint

**Files:**
- Modify: `app/Console/Commands/GameTick.php` (fire the trigger inside the first encounter's resolution — piggyback on whichever of Tasks 4-6 fires first for a given run, simplest to add to the storm-resolution/instability/plague-trigger points uniformly)
- Modify: `lang/de/events.php`, `lang/de/colony.php` (or wherever `onboarding_decay`'s hint string lives — match that file)

**Interfaces:**
- Consumes: `OnboardingTriggerService::hasFired(int $userId, string $triggerKey): bool` / `markFired(int $userId, string $triggerKey): void` (existing, per `processBuildingDecay()`'s `onboarding_decay` usage — same pattern, new key `onboarding_encounter`).

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/GameTick/GameTickEncounterTest.php`:

```php
    public function test_first_encounter_ever_fires_the_onboarding_hint_once(): void
    {
        config(['game.encounter.storm.base_chance' => 1.0, 'game.encounter.cooldown_sols' => 0]);
        // (force a guaranteed storm trigger per Task 4's established fixture pattern)

        $this->artisan('game:tick')->assertExitCode(0);   // warning fires — this is the trigger point, not resolution
        $this->artisan('game:tick')->assertExitCode(0);   // resolution — no second hint here

        $fired = DB::table('colony_log')->where('event', 'colony.onboarding_encounter')->count();
        $this->assertSame(1, $fired, 'the onboarding hint must fire exactly once, on the FIRST encounter of the run');
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run the filtered test.
Expected: FAIL — no `colony.onboarding_encounter` event exists.

- [ ] **Step 3: Wire the trigger into `rollStorm()`, `rollInstability()`, and `rollPlague()`**

At the point each of the three roll-methods (from Tasks 4-6) successfully triggers a NEW warning/event — the earliest, most natural place is right where each already calls `$this->eventService->createEvent([...'event' => 'encounter.storm_warning'...])` / `'encounter.instability_triggered'` / `'encounter.plague_triggered'` — add immediately before that `createEvent()` call, in all three methods:

```php
        $userId = $colony->user_id;
        if ($userId !== null && ! $this->onboardingTriggerService->hasFired($userId, 'onboarding_encounter')) {
            $this->onboardingTriggerService->markFired($userId, 'onboarding_encounter');
            $this->eventService->createEvent([
                'user' => $userId,
                'tick' => $tick,
                'event' => 'colony.onboarding_encounter',
                'area' => 'colony',
                'parameters' => json_encode(['colony_id' => $colony->id]),
            ]);
        }
```

- [ ] **Step 4: Add the German hint string**

In `lang/de/colony.php` (matching `onboarding_hint_explore`'s existing style):

```php
    'onboarding_hint_encounter' => 'Gebäude mit niedrigem Zustand sind anfälliger für Zwischenfälle — regelmäßige Reparatur zahlt sich doppelt aus.',
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php bin/phpunit tests/Feature/GameTick/GameTickEncounterTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Console/Commands/GameTick.php lang/de/colony.php tests/Feature/GameTick/GameTickEncounterTest.php
git commit -m "feat: add one-shot onboarding hint for first colonist-danger encounter"
```

---

### Task 8: Full verification, GDD nachtrag, CHANGELOG

**Files:**
- Modify: `docs/GDD.md`
- Modify: `CHANGELOG.md`

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test`
Expected: all green, no regressions vs. the pre-task baseline (996 passed as of 2026-08-15).

- [ ] **Step 2: GDD nachtrag on §9**

In `docs/GDD.md`, right after §9's header note ("**Design fertig, Implementierung ausstehend.**" line), add:

```markdown
> **Nachtrag 2026-08-16:** Implementiert. `EncounterService` löst die Ausgangsstufe
> (Abgewehrt/Beschädigt/Kritisch) rein aus SP-Zustand auf; `GameTick::processEncounters()`
> würfelt/löst alle drei Gefahrentypen pro Sol auf. Sturm nutzt Vorwarnung+Auflösung über
> `colony_log` (1 Sol Abstand, wie spezifiziert). Geologische Instabilität und
> Seuchenausbruch lösen ohne Vorwarnung sofort auf (kein SP-Ausgang, andere
> Konsequenz-Form — Produktionsausfall bzw. AP-Debuff). `defense` (Sturm-Risiko) und
> `geology`s zweiter Effekt (Instabilitäts-Risiko, zusätzlich zum Regolith-Bonus)
> nutzen die etablierte Glockenkurve. Cooldown (`game.encounter.cooldown_sols`,
> Default 3 Sole) adressiert die im Abschnitt "Offene Punkte" genannte Spiral-Sorge.
> Alle Basis-Chancen sind erste Fassung, Kalibrierung nach PlaytestBot-Läufen aussteht.
> Siehe `app/Services/EncounterService.php`, `docs/superpowers/plans/2026-08-16-encounters-and-defense.md`.
```

- [ ] **Step 3: CHANGELOG entry**

In `CHANGELOG.md`, new `## 2026-08-16` section at the top:

```markdown
## 2026-08-16

- Feature: GDD §9 „Begegnungen & Gefahren" implementiert (Sturm/Geologische Instabilität/Seuchenausbruch) — bisher nur spezifiziert, nie codiert. `defense`-Kenntnis bekommt ihren ersten aktiven Effekt (Sturm-Risikominderung), `geology` einen zweiten (Instabilitäts-Risikominderung, zusätzlich zum Regolith-Bonus). Siehe `docs/superpowers/plans/2026-08-16-encounters-and-defense.md`.
```

- [ ] **Step 4: Commit**

```bash
git add docs/GDD.md CHANGELOG.md
git commit -m "docs: GDD §9 nachtrag + CHANGELOG for encounters-and-defense plan"
```

---

## Post-plan follow-ups (explicitly out of scope here, tracked elsewhere)

- Exact base-chance/SP-loss-percentage calibration — this plan's numbers are a documented first pass (GDD's own convention for "Richtwerte"), tuning happens via PlaytestBot after this merges.
- UI surfacing of active encounters/debuffs (a dashboard indicator for an active plague debuff or an upcoming storm warning, beyond the raw `colony_log` entry) — not requested by the spec, `colony_log`/Komm-Log is the existing surface for this class of event.
- Whether the cooldown should differ per danger type (currently one shared `cooldown_sols` value across all three) — GDD's own "Offene Punkte" leaves this open, revisit after playtest data shows whether one shared cooldown is too coarse.
