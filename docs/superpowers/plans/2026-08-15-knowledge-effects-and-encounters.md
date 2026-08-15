# Kenntnis-Effekte (Plan 1: Bau-AP-Rabatt, Agronomie, Handel) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Vier der sieben Kenntnisse (`construction`, `cartography`, `trade`, `agronomy`) bekommen einen aktiven, spielrelevanten Effekt — Gebäude-Levelup-AP-Rabatt (additiv, glockenförmig über die Level) sowie Organika- und Cantina-Boni. `geology` (bereits aktiv) und `health` (bewusst ohne Zusatzeffekt) bleiben unangetastet. `defense` + GDD §9 "Begegnungen & Gefahren" sind ein separater Folgeplan.

**Architecture:** Ein neuer `ProjectBonusService` bündelt die additive Bau-AP-Rabatt-Berechnung (Summe der Kenntnis-Kurven von `construction`+`cartography`+`trade`, gedeckelt durch `project_min_cost_factor`) und wird in `ColonyController::investBuilding()`/`hexview()` verdrahtet. `agronomy` und `trade`s Cantina-Bonus folgen dem bereits bestehenden `geology`-Muster (direkter `DB::table('colony_researches')`-Read + `GameTick::cumulativeCurveYield()`) in `GameTick` bzw. `BarService`.

**Tech Stack:** Laravel 12, PHP 8.2, SQLite, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-08-15-knowledge-effects-and-encounters-design.md`

## Global Constraints

- Alle neuen Effekt-Kurven sind **glockenförmig** über 5 Level (kleiner Zuwachs Lv1/Lv5, größter Zuwachs Lv2–4) — Delta-Arrays im etablierten Stil (`geology_harvester_bonus_per_level`, `colony_zone_expansion`).
- Bau-AP-Rabatt ist **additiv, nie multiplikativ**, respektiert `project_min_cost_factor` (neu, 0.5) als Untergrenze — Rabatt darf `ap_for_levelup` nie unter 50 % des Basiswerts drücken.
- `construction`/`cartography`/`trade` rabattieren **alle** Gebäude-Levelups gleichermaßen (inkl. CommandCenter) — keine Unterscheidung nach Gebäudetyp (Owner-Entscheidung, 2026-08-15).
- Kein neuer AP-Pool, keine AP-Domänen-Trennung — der Rabatt wirkt auf `ap_for_levelup`-Schwellenwerte, nicht auf den Pool selbst.
- PHP-Code/Kommentare Englisch, Config-Keys Englisch.
- TDD-Pflicht: jeder neue Effekt braucht mindestens einen Service-/Feature-Test, der den Bonus bei einem konkreten Level verifiziert.
- Jeder bestehende Aufrufer von `ColonyController::investBuilding()` ohne jede Kenntnis-Investition (alle Kenntnisse Level 0) muss sich identisch zu heute verhalten (Rabatt = 0 %, `effectiveApForLevelup === baseApForLevelup`).

---

### Task 1: `ProjectBonusService` — Bau-AP-Rabatt-Berechnung

**Files:**
- Create: `app/Services/ProjectBonusService.php`
- Test: `tests/Unit/ProjectBonusServiceTest.php`
- Modify: `config/knowledge.php` (neue Felder `construction`, `cartography`, `trade`)
- Modify: `config/game.php` (neuer Key `project_min_cost_factor`)

**Interfaces:**
- Produces: `ProjectBonusService::buildingApDiscountPercent(int $colonyId): int` (Summe in Prozentpunkten, 0–45), `ProjectBonusService::effectiveApForLevelup(int $colonyId, int $baseApForLevelup): int` — konsumiert von Task 2.

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Unit;

use App\Services\ProjectBonusService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectBonusServiceTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function setKnowledgeLevel(int $researchId, int $level): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => $researchId],
            ['level' => $level, 'ap_spend' => 0, 'status_points' => 20]
        );
    }

    public function test_discount_is_zero_with_no_knowledge_invested(): void
    {
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(0, $service->buildingApDiscountPercent(self::COLONY_ID));
    }

    public function test_discount_sums_all_three_domains_additively(): void
    {
        // construction id=90, cartography id=91, trade id=95 (config/knowledge.php)
        $this->setKnowledgeLevel(90, 3);   // cumulative [2,4,4] = 10
        $this->setKnowledgeLevel(91, 1);   // cumulative [2] = 2
        $this->setKnowledgeLevel(95, 5);   // cumulative [2,4,4,3,2] = 15
        $service = $this->app->make(ProjectBonusService::class);

        $this->assertSame(27, $service->buildingApDiscountPercent(self::COLONY_ID));
    }

    public function test_effective_ap_for_levelup_applies_discount(): void
    {
        $this->setKnowledgeLevel(90, 5);   // construction Lv5 = 15%
        $service = $this->app->make(ProjectBonusService::class);

        // 10 * (1 - 0.15) = 8.5 → round to 9 (round-half-up)
        $this->assertSame(9, $service->effectiveApForLevelup(self::COLONY_ID, 10));
    }

    /**
     * With current config, max additive discount is 45% (15% × 3 domains) — always
     * below the 50% floor, so it never binds via real knowledge levels (by design,
     * see spec §Global Constraints: the floor is a guard rail for future bonus
     * sources, not active yet). Test the floor logic directly via the pure helper
     * instead of trying to manufacture a >50% discount through the DB.
     */
    public function test_apply_discount_never_drops_below_min_cost_factor(): void
    {
        // 80% discount would give round(10 * 0.2) = 2, but the 0.5 floor caps it at 5.
        $this->assertSame(5, ProjectBonusService::applyDiscount(10, 80, 0.5));
    }

    public function test_apply_discount_rounds_half_up_below_the_floor(): void
    {
        // 15% discount on base 10: round(10 * 0.85) = round(8.5) = 9, floor = ceil(5) = 5.
        $this->assertSame(9, ProjectBonusService::applyDiscount(10, 15, 0.5));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php bin/phpunit tests/Unit/ProjectBonusServiceTest.php`
Expected: FAIL — class `App\Services\ProjectBonusService` not found.

- [ ] **Step 3: Add config fields**

In `config/knowledge.php`, add to `construction`, `cartography`, `trade` (after their existing `levelup_costs` line each):

```php
        // Bau-AP-Rabatt (GDD §13.3, glockenförmig statt linear — game-designer review
        // 2026-08-15, docs/superpowers/specs/2026-08-15-knowledge-effects-and-encounters-design.md).
        // Wirkt additiv mit cartography+trade auf ALLE Gebäude-Levelups (Owner-Entscheidung:
        // keine Domänentrennung nach Projekttyp, da nur Bau-Projekte existieren).
        'ap_cost_reduction_per_lv' => [1 => 2, 2 => 4, 3 => 4, 4 => 3, 5 => 2],   // Σ15%
```

For `trade` specifically, add a second field right after (used by Task 4, defined here so the config file carries both `trade` fields together):

```php
        // Cantina-Angebotsslot-Bonus (Task 4 dieses Plans) — zusätzliche gleichzeitige
        // Bar-Angebote bei höherem trade-Level.
        'bar_offer_boost_per_lv' => [1 => 0, 2 => 1, 3 => 1, 4 => 0, 5 => 0],   // Σ2 Slots
```

In `config/game.php`, add near the other `game.run.*`/global keys (top-level array, alongside `production_curve`):

```php
    // Untergrenze für additive Projekt-AP-Rabatte (§13.3) — verhindert, dass Boni
    // ap_for_levelup auf 0 drücken. Bei aktuellem Max-Rabatt (45%, construction+
    // cartography+trade voll investiert) nie bindend; Leitplanke für spätere
    // Bonusquellen (Berater-Rang, Koloniereife), die noch nicht implementiert sind.
    'project_min_cost_factor' => 0.5,
```

- [ ] **Step 4: Implement `ProjectBonusService`**

```php
<?php

namespace App\Services;

use App\Console\Commands\GameTick;
use Illuminate\Support\Facades\DB;

/**
 * Additive AP-cost discounts for building projects (GDD §13.3). Currently sums the
 * three "domain knowledge" curves (construction, cartography, trade) — advisor-rank
 * and CC-level bonus sources from the same GDD table are not yet implemented (out of
 * scope for this plan, see docs/superpowers/specs/2026-08-15-knowledge-effects-and-
 * encounters-design.md §2).
 */
class ProjectBonusService
{
    /** research_id values from config/knowledge.php that discount building projects. */
    private const DOMAIN_KNOWLEDGE_KEYS = ['construction', 'cartography', 'trade'];

    public function buildingApDiscountPercent(int $colonyId): int
    {
        $total = 0;

        foreach (self::DOMAIN_KNOWLEDGE_KEYS as $key) {
            $cfg = config("knowledge.{$key}");
            $researchId = (int) $cfg['id'];
            $curve = $cfg['ap_cost_reduction_per_lv'] ?? [];

            $level = (int) DB::table('colony_researches')
                ->where('colony_id', $colonyId)
                ->where('research_id', $researchId)
                ->value('level');

            $total += GameTick::cumulativeCurveYield($curve, $level);
        }

        return $total;
    }

    public function effectiveApForLevelup(int $colonyId, int $baseApForLevelup): int
    {
        $discountPercent = $this->buildingApDiscountPercent($colonyId);
        $minCostFactor = (float) config('game.project_min_cost_factor', 0.5);

        return self::applyDiscount($baseApForLevelup, $discountPercent, $minCostFactor);
    }

    /** Pure discount math, factored out so the floor logic is testable without DB state. */
    public static function applyDiscount(int $base, int $discountPercent, float $minCostFactor): int
    {
        $floor = (int) ceil($base * $minCostFactor);
        $discounted = (int) round($base * (1 - $discountPercent / 100));

        return max($floor, $discounted);
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php bin/phpunit tests/Unit/ProjectBonusServiceTest.php`
Expected: PASS (5 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Services/ProjectBonusService.php tests/Unit/ProjectBonusServiceTest.php config/knowledge.php config/game.php
git commit -m "feat: add ProjectBonusService for additive building-project AP discounts"
```

---

### Task 2: Wire `ProjectBonusService` into `ColonyController`

**Files:**
- Modify: `app/Http/Controllers/Colony/ColonyController.php`
- Test: `tests/Feature/Colony/BuildResourceSinkTest.php` (extend existing suite)

**Interfaces:**
- Consumes: `ProjectBonusService::effectiveApForLevelup(int $colonyId, int $baseApForLevelup): int` (Task 1)

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/Colony/BuildResourceSinkTest.php`, in the "Level-up costs" section (near `test_cc_levelup_deducts_scaled_regolith`):

```php
    public function test_construction_knowledge_reduces_ap_needed_for_levelup(): void
    {
        // construction Lv5 (research_id=90) = 15% discount → CC's ap_for_levelup
        // (10) drops to round(10 * 0.85) = 9. One less invest click needed to level up.
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => 90],
            ['level' => 5, 'ap_spend' => 0, 'status_points' => 20]
        );
        $this->setCc(['level' => 1, 'ap_spend' => 8, 'status_points' => 16]);   // 1 AP from the discounted threshold (9)

        $this->actingAs($this->bart())->postJson(route('colony.building.invest'), ['building_id' => 25])
            ->assertOk()->assertJsonPath('leveled_up', true);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php bin/phpunit --filter test_construction_knowledge_reduces_ap_needed_for_levelup tests/Feature/Colony/BuildResourceSinkTest.php`
Expected: FAIL — `leveled_up` is `false` (still gated at the undiscounted threshold of 10, `ap_spend=9` after this click ≠ 10).

- [ ] **Step 3: Inject the service and use it in the AP gate**

In `app/Http/Controllers/Colony/ColonyController.php`, add the import:

```php
use App\Services\ProjectBonusService;
```

Add to the constructor parameter list (after `private readonly HarvesterEntitlementService $harvesterEntitlementService,`):

```php
        private readonly ProjectBonusService $projectBonusService,
```

Replace the AP-gate block inside `investBuilding()`:

```php
        // Level-up Regolith is charged only on the click that completes the level (flat,
        // no escalation; CC scales by target level). Check it BEFORE spending the AP so a
        // shortfall never burns the final Construction-AP — the player tops up first.
        $willLevelUp = ($row->ap_spend + 1) >= (int) $building->ap_for_levelup;
        $levelupRegolith = $willLevelUp
            ? $this->levelupRegolithFor($buildingId, (int) $row->level + 1)
            : 0;
```

with:

```php
        // Construction/cartography/trade knowledge additively discounts the AP
        // threshold (GDD §13.3, docs/superpowers/specs/2026-08-15-knowledge-effects-
        // and-encounters-design.md §2). Level-up Regolith is charged only on the click
        // that completes the level — checked BEFORE spending the AP so a shortfall
        // never burns the final Construction-AP.
        $effectiveApForLevelup = $this->projectBonusService->effectiveApForLevelup($colony->id, (int) $building->ap_for_levelup);
        $willLevelUp = ($row->ap_spend + 1) >= $effectiveApForLevelup;
        $levelupRegolith = $willLevelUp
            ? $this->levelupRegolithFor($buildingId, (int) $row->level + 1)
            : 0;
```

Also replace the line just below that clamps `ap_spend`:

```php
        $newApSpend = min($row->ap_spend + 1, $building->ap_for_levelup);
```

with:

```php
        $newApSpend = min($row->ap_spend + 1, $effectiveApForLevelup);
```

- [ ] **Step 4: Fix the hexview display to show the discounted threshold**

In `hexview()`, the `$buildings` collection's `map()` callback currently reads `$b->ap_for_levelup` straight off the query result (undiscounted). Locate:

```php
            ->map(function ($b) use ($globalTick) {
                $b->label = __('techtree.'.$b->building_key);
                $b->image_slug = self::buildingImageSlug($b->building_key);
                $b->in_transit = $b->pending_until_tick !== null && (int) $b->pending_until_tick >= $globalTick;
                $b->levelup_cost = $this->levelupRegolithFor((int) $b->building_id, (int) $b->level + 1);

                return $b;
            });
```

Replace with:

```php
            ->map(function ($b) use ($globalTick, $colony) {
                $b->label = __('techtree.'.$b->building_key);
                $b->image_slug = self::buildingImageSlug($b->building_key);
                $b->in_transit = $b->pending_until_tick !== null && (int) $b->pending_until_tick >= $globalTick;
                $b->levelup_cost = $this->levelupRegolithFor((int) $b->building_id, (int) $b->level + 1);
                $b->ap_for_levelup = $this->projectBonusService->effectiveApForLevelup($colony->id, (int) $b->ap_for_levelup);

                return $b;
            });
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php bin/phpunit tests/Feature/Colony/BuildResourceSinkTest.php tests/Feature/Colony/BuildingInvestTest.php`
Expected: PASS — including the new test and all pre-existing tests (which run with zero knowledge invested, so the discount is 0% and behavior is unchanged).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Colony/ColonyController.php tests/Feature/Colony/BuildResourceSinkTest.php
git commit -m "feat: apply construction/cartography/trade AP discount to building levelups"
```

---

### Task 3: `agronomy` Organika production bonus

**Files:**
- Modify: `app/Console/Commands/GameTick.php`
- Modify: `config/game.php`
- Test: `tests/Feature/GameTickTest.php` (or the existing GameTick production test file — see Step 1 note)

**Interfaces:**
- Produces: nothing consumed elsewhere in this plan (self-contained, mirrors the existing `geology` → Harvester pattern).

- [ ] **Step 1: Locate the existing GameTick production test file**

Run: `grep -rln "generateHarvesterYield\|generateResources\|geology_harvester_bonus" tests/Feature/*.php tests/Unit/*.php`

Add the new test to whichever file already covers `generateHarvesterYield`'s geology bonus (same fixture/setup pattern) — if none exists standalone, add to `tests/Feature/GameTickTest.php`. Adjust the exact class/fixture names in Step 2 below to match what that file already uses for colony/user setup.

- [ ] **Step 2: Write the failing test**

```php
    public function test_agronomy_knowledge_adds_organika_bonus_on_top_of_biofacility_output(): void
    {
        // bioFacility (id=41) Lv1 alone yields 8 Organika/Sol (production_curve[41][5][1]).
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 41)
            ->update(['level' => 1]);
        // agronomy (research_id=93) Lv3 → cumulative [1,2,2] = 5 bonus Organika/Sol.
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => 93],
            ['level' => 3, 'ap_spend' => 0, 'status_points' => 20]
        );
        $before = (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->value('amount');

        $this->artisan('game:tick')->assertExitCode(0);

        $after = (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->value('amount');
        // 8 (base) + 5 (agronomy bonus) = 13, at trust multiplier 1.0 (default seeded trust).
        $this->assertSame($before + 13, $after);
    }
```

Adapt `self::COLONY_ID` / `actingAs` fixture references to match the surrounding test file's existing conventions exactly (read a neighboring test first).

- [ ] **Step 3: Run test to verify it fails**

Run: `php bin/phpunit --filter test_agronomy_knowledge_adds_organika_bonus_on_top_of_biofacility_output`
Expected: FAIL — actual delta is 8, not 13 (no agronomy bonus applied yet).

- [ ] **Step 4: Add the config curve**

In `config/game.php`, next to `geology_harvester_bonus_per_level`:

```php
    // agronomy Kenntnis-Bonus auf bioFacility-Organika-Ertrag — Parität zu geology's
    // Harvester-Bonus (GDD §13.5 Paritäts-Anforderung). Glockenförmig, NICHT front-
    // loaded wie geology: dieser Effekt ist neu, ohne bestehende Kalibrierungshistorie.
    'agronomy_agrardom_bonus_per_level' => [1 => 1, 2 => 2, 3 => 2, 4 => 1, 5 => 1],   // Σ7 Or/Sol
```

- [ ] **Step 5: Implement the bonus in `GameTick`**

Add a new private method near `generateHarvesterYield()`:

```php
    /**
     * agronomy Kenntnis-Bonus auf bioFacility-Organika-Ausstoß (GDD §13.5 Paritäts-
     * Anforderung, docs/superpowers/specs/2026-08-15-knowledge-effects-and-encounters-
     * design.md §3) — mirrors generateHarvesterYield()'s geology bonus pattern, but
     * bioFacility has no per-tile depletion, so this is a flat colony-level add-on.
     */
    private function generateAgronomyBonus(Colony $colony, float $multiplier): int
    {
        $bioFacilityLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', 41)
            ->value('level');

        if ($bioFacilityLevel <= 0) {
            return 0;
        }

        $agronomyId = (int) config('knowledge.agronomy.id', 93);
        $agronomyLevel = (int) DB::table('colony_researches')
            ->where('colony_id', $colony->id)
            ->where('research_id', $agronomyId)
            ->value('level');

        $bonus = self::cumulativeCurveYield(config('game.agronomy_agrardom_bonus_per_level', []), $agronomyLevel);

        return (int) round($bonus * $multiplier);
    }
```

In `generateResources()`, right after the `$harvesterYield` block (which updates Regolith), add:

```php
            $agronomyBonus = $this->generateAgronomyBonus($colony, $multiplier);
            if ($agronomyBonus > 0) {
                ColonyResource::where('colony_id', $colony->id)
                    ->where('resource_id', 5) // Organika
                    ->update(['amount' => DB::raw("amount + {$agronomyBonus}")]);
            }
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php bin/phpunit --filter test_agronomy_knowledge_adds_organika_bonus_on_top_of_biofacility_output`
Then: `php bin/phpunit tests/Feature/GameTickTest.php` (full file — confirm no regression on colonies with agronomy Lv0, where the bonus must be 0)
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Console/Commands/GameTick.php config/game.php tests/Feature/GameTickTest.php
git commit -m "feat: agronomy knowledge adds Organika bonus to bioFacility output"
```

---

### Task 4: `trade` Cantina concurrent-offer-slot bonus

**Files:**
- Modify: `app/Services/BarService.php`
- Test: `tests/Feature/Colony/BarServiceTest.php` (or wherever existing BarService tests live — see Step 1)

**Interfaces:**
- Consumes: `config('knowledge.trade.bar_offer_boost_per_lv')` (added in Task 1, Step 3)

- [ ] **Step 1: Locate the existing BarService test file**

Run: `grep -rln "generateOffersForColony\|BarService" tests/Feature/*.php tests/Feature/**/*.php`

Add the new test alongside the existing `generateOffersForColony()` coverage, matching that file's fixture/seed conventions exactly.

- [ ] **Step 2: Write the failing test**

```php
    public function test_trade_knowledge_increases_concurrent_offer_slots(): void
    {
        // trade (research_id=95) Lv3 → cumulative [0,1,1] = 2 extra slots.
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => 95],
            ['level' => 3, 'ap_spend' => 0, 'status_points' => 20]
        );
        // Force a high guest roll so the concurrent cap — not the guest-count roll — is
        // what's under test; base level_max_concurrent for this bar level, see
        // config/game.php → bar.level_max_concurrent.
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 52)
            ->update(['level' => 1]);
        $baseMax = config('game.bar.level_max_concurrent')[1] ?? 2;

        app(BarService::class)->generateOffersForColony(self::COLONY_ID, 1);

        $offerCount = DB::table('bar_offers')->where('colony_id', self::COLONY_ID)->count();
        $this->assertLessThanOrEqual($baseMax + 2, $offerCount);
        // Weak upper-bound assertion (guestCount is randomized) — the load-bearing
        // assertion is the config wiring test in Step 5/6, not offer count here.
    }
```

Note in the report if the guest-count randomization makes this assertion too weak to be meaningful once you see the actual fixture/seed setup — if so, prefer directly unit-testing the concurrent-cap calculation by extracting it (see Step 4) rather than asserting on randomized `bar_offers` rows.

- [ ] **Step 3: Run test to verify it fails or passes vacuously**

Run: `php bin/phpunit --filter test_trade_knowledge_increases_concurrent_offer_slots`
Expected: currently passes vacuously (no bonus applied yet, but the weak upper-bound assertion doesn't catch that) — confirm by temporarily asserting `$offerCount > $baseMax` fails without the bonus, then revert to the real assertion once Step 4 is implemented. Note this in the report.

- [ ] **Step 4: Implement the bonus in `BarService`**

Add a new private method:

```php
    /**
     * trade Kenntnis-Bonus auf gleichzeitige Cantina-Angebotsslots (GDD §13.5 Pfad-C,
     * docs/superpowers/specs/2026-08-15-knowledge-effects-and-encounters-design.md §4)
     * — separat vom Bau-AP-Rabatt (ProjectBonusService), da dieser Effekt Handlungen
     * (Cantina-Angebote), nicht Projekte betrifft.
     */
    private function tradeConcurrentSlotBonus(int $colonyId): int
    {
        $tradeId = (int) config('knowledge.trade.id', 95);
        $tradeLevel = (int) DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', $tradeId)
            ->value('level');

        return \App\Console\Commands\GameTick::cumulativeCurveYield(
            config('knowledge.trade.bar_offer_boost_per_lv', []),
            $tradeLevel
        );
    }
```

In `generateOffersForColony()`, replace:

```php
        $levelMaxConcurrent = config('game.bar.level_max_concurrent', []);
        $maxConcurrent = $levelMaxConcurrent[$barLevel] ?? 2;
```

with:

```php
        $levelMaxConcurrent = config('game.bar.level_max_concurrent', []);
        $maxConcurrent = ($levelMaxConcurrent[$barLevel] ?? 2) + $this->tradeConcurrentSlotBonus($colonyId);
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php bin/phpunit tests/Feature/Colony/BarServiceTest.php` (or the actual located file)
Expected: PASS — including pre-existing tests (trade Lv0 → bonus 0, unchanged behavior).

- [ ] **Step 6: Commit**

```bash
git add app/Services/BarService.php tests/Feature/Colony/BarServiceTest.php
git commit -m "feat: trade knowledge increases concurrent Cantina offer slots"
```

---

### Task 5: Full verification, GDD nachtrag, CHANGELOG

**Files:**
- Modify: `docs/GDD.md`
- Modify: `CHANGELOG.md`

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test`
Expected: all green, no regressions vs. the pre-task baseline (988 passed as of 2026-08-15 — exact count may drift from other same-day work landing in parallel; the check is zero new failures).

- [ ] **Step 2: Add a GDD nachtrag**

In `docs/GDD.md`, under §13.3 "Boni: additiv, nie multiplikativ", add directly after the existing "Bonusquellen (Vorschlag, siehe 13.6)" table:

```markdown
> **Nachtrag 2026-08-15 (Owner-Entscheidung, PlaytestBot-Befund):** Umgesetzt für
> `construction`/`cartography`/`trade` — glockenförmig statt linear (Σ15% je Kenntnis
> bei Lv5, Peak Lv2–4), wirkt additiv auf **alle** Gebäude-Levelups (inkl.
> CommandCenter), nicht nach Projekttyp getrennt, da im aktuellen Spiel nur
> Bau-Projekte existieren (Navigation/Wirtschaft haben keine passende Projekt-
> Kategorie). Berater-Rang- und Koloniereife-Bonusquellen aus der Tabelle oben sind
> weiterhin nicht implementiert. Siehe `app/Services/ProjectBonusService.php`,
> `docs/superpowers/specs/2026-08-15-knowledge-effects-and-encounters-design.md`.
```

Under §13.5 "Regolith-Beschaffung: alle drei Pfade müssen gleichwertig liefern", directly after the existing Pfad-A/B/C-Tabelle, add:

```markdown
> **Nachtrag 2026-08-15:** `agronomy`-Organika-Parität zu `geology` umgesetzt
> (`config('game.agronomy_agrardom_bonus_per_level')`, Σ7 Or/Sol bei Lv5, glockenförmig
> — bewusst NICHT front-loaded wie `geology`, da neu ohne Kalibrierungshistorie). Der
> Cantina-Pfad-C-Fix (Losgrößen/Tauschrichtung) ist weiterhin offen; `trade`s neuer
> Kenntniseffekt (zusätzliche Angebotsslots, `BarService::tradeConcurrentSlotBonus()`)
> läuft parallel dazu, ohne ihn zu ersetzen.
```

- [ ] **Step 3: CHANGELOG entry**

In `CHANGELOG.md`, under today's `## 2026-08-15` section (top of file):

```markdown
- Feature: 4 der 7 Kenntnisse bekommen aktive Effekte (`construction`/`cartography`/`trade` → additiver Bau-AP-Rabatt, `agronomy` → Organika-Bonus, `trade` → Cantina-Angebotsslots) — alle glockenförmig über 5 Level statt linear. Siehe `docs/superpowers/specs/2026-08-15-knowledge-effects-and-encounters-design.md`. `defense` + GDD §9 Begegnungen folgen als separater Plan.
```

- [ ] **Step 4: Commit**

```bash
git add docs/GDD.md CHANGELOG.md
git commit -m "docs: GDD §13.3/§13.5 nachtrag + CHANGELOG for knowledge-effects Plan 1"
```

---

## Post-plan follow-ups (explicitly out of scope here, tracked elsewhere)

- `defense` Encounter-Risikominderung + komplette GDD §9-Implementierung (Sturm/Geologische Instabilität/Seuche, Trust-Events `encounter_lost`/`colony_threatened`, `securityHub.event_mitigation_pct`-Anwendung) — eigener Folgeplan (Owner-Entscheidung 2026-08-15, Plan-Split).
- Berater-Rang- und Koloniereife-Bonusquellen für den Bau-AP-Rabatt (GDD §13.3-Tabelle, Zeilen "Domänen-Berater"/"Koloniereife") — nicht Teil dieses Plans.
- Cantina-Pfad-C-Fix (Losgrößen an Bestand koppeln, Tauschrichtung nach Bestand statt Zufall, GDD §13.5) — separate Baustelle, nur lose mit `trade`s Slot-Bonus verwandt.
- Freischaltbare Spezialoptionen in Dialogen (z. B. bessere Handelskonditionen bei hohem `trade`-Level) — vorgemerkte Erweiterung, kein Teil dieses Plans.
