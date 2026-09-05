# Hangar-Missionen: Erfolgschance + Schwierigkeitsgrad Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Hangar-Missionen bekommen eine echte Erfolgschance mit wählbarem Schwierigkeitsgrad (leicht/normal/schwer) statt garantierter Belohnung.

**Architecture:** Dispatch bekommt einen neuen `difficulty`-Parameter (validiert gegen die 2 im Katalog freigeschalteten Stufen), gespeichert auf `colony_hangar_missions.difficulty`. Bei Tick-Resolution (`GameTick::processHangarMissions`) entscheidet ein deterministischer Seeded-Roll (reuse `runs.rng_seed`, gleiches LCG-Pattern wie der bestehende Reward-Roll, aber mit `+1`-Offset) über Erfolg/Fehlschlag — bei Erfolg werden die Katalog-Rewards mit einem globalen `reward_multiplier[$difficulty]` skaliert ausgezahlt, bei Fehlschlag entfällt der Payout (auf `schwer` zusätzlicher SP-Abzug).

**Tech Stack:** Laravel (PHP), SQLite, PHPUnit, Alpine.js (Dispatch-Dialog UI).

**Spec:** `docs/superpowers/specs/2026-09-02-hangar-mission-success-chance-design.md`

## Global Constraints

- Config ist canonical source of truth (CLAUDE.md) — alle Zahlenwerte in `config/game.php`/`config/missions.php`, GDD-Prosa bleibt zahlenlos (ADR 0004).
- TDD verbindlich: roter Test vor jedem Produktionscode-Schritt (CLAUDE.md TDD-Mandat).
- Dispatch-Kosten (Nav-AP/Organika) bleiben unverändert von der Schwierigkeit — hängen nur von `sol_distance` ab (Spec Punkt 4).
- RNG: kein neuer Mechanismus, reuse `runs.rng_seed` (ADR 0003) — Erfolgs-Roll nutzt `rngSeed + missionId + 1`, damit er nicht mit dem bestehenden Reward-Roll (`rngSeed + missionId`) kollidiert.
- `GameTick::seededRoll(int $seed, int $min, int $max): int` (GameTick.php:512-520) ist der bestehende deterministische LCG-Helper — für Prozent-Rolls wird er wie an drei bestehenden Stellen (GameTick.php:1233, 1391, 1455) als `seededRoll($seed, 0, 9999) / 10000` verwendet, kein neuer Zufallsmechanismus.
- Sprachregel: PHP/JS/Config-Keys englisch, `lang/de/*.php`-Werte deutsch (CLAUDE.md Sprachregeln).

---

## Task 1: Migration — `difficulty`-Spalte auf `colony_hangar_missions`

**Files:**
- Create: `database/migrations/2026_09_03_000001_add_difficulty_to_colony_hangar_missions.php`

**Interfaces:**
- Produces: Spalte `colony_hangar_missions.difficulty` (string, 20, `default('normal')`, nicht nullable) — spätere Tasks lesen/schreiben sie über `DB::table('colony_hangar_missions')`, kein Eloquent-Model vorhanden (Codebase nutzt durchgängig `DB::table()` für diese Tabelle).

**Kein TDD nötig** — reine additive Schema-Migration ohne Backfill-Logik (CLAUDE.md TDD-Ausnahme: "Migrations ohne Logik, Spalte hinzufügen, kein Backfill"). `default('normal')` ist der Punkt: bestehende Test-Fixtures und Code-Pfade, die die Spalte nicht explizit setzen, bekommen automatisch `'normal'` (Multiplikator 1.0) — keine bestehenden Inserts müssen angefasst werden.

- [ ] **Step 1: Migration schreiben**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the difficulty column to colony_hangar_missions (GDD §8b Erfolgschance +
 * Schwierigkeitsgrad, docs/superpowers/specs/2026-09-02-hangar-mission-success-chance-design.md).
 *
 * Values: 'leicht' | 'normal' | 'schwer'. default('normal') so pre-existing
 * dispatch code paths and test fixtures that don't set it explicitly keep
 * working unchanged (reward_multiplier['normal'] = 1.0, i.e. today's behavior).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colony_hangar_missions', function (Blueprint $table) {
            $table->string('difficulty', 20)->default('normal')->after('sol_distance');
        });
    }

    public function down(): void
    {
        Schema::table('colony_hangar_missions', function (Blueprint $table) {
            $table->dropColumn('difficulty');
        });
    }
};
```

- [ ] **Step 2: Migration laufen lassen und verifizieren**

Run: `php artisan migrate --database=sqlite`
Expected: `2026_09_03_000001_add_difficulty_to_colony_hangar_missions ....... DONE`

Run: `sqlite3 data/db/nouron.db ".schema colony_hangar_missions"` — Ausgabe muss `difficulty` als Spalte enthalten.

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_09_03_000001_add_difficulty_to_colony_hangar_missions.php
git commit -m "feat: difficulty-Spalte auf colony_hangar_missions"
```

---

## Task 2: `config/game.php` — neuer `missions.difficulty`-Block

**Files:**
- Modify: `config/game.php`

**Interfaces:**
- Produces: `config('game.missions.difficulty.base_chance')` (array `['leicht'=>float,'normal'=>float,'schwer'=>float]`), `config('game.missions.difficulty.reward_multiplier')` (gleiche Struktur), `config('game.missions.difficulty.pilot_rank_bonus_pct')` (float), `config('game.missions.difficulty.knowledge_bonus_pct_per_level')` (float), `config('game.missions.difficulty.chance_cap')` (float), `config('game.missions.difficulty.hard_fail_extra_wear')` (float) — von Task 4 (`successChanceFor`) und Task 7 (Reward-Skalierung, Hard-Fail-Wear) konsumiert.

**Kein TDD nötig** — reine Config-Ergänzung ohne eigenen Codepfad in diesem Task (der Codepfad, der die Werte konsumiert, wird in Task 4/7 getestet).

- [ ] **Step 1: Block in `config/game.php` ergänzen**

Füge im bestehenden `missions`-Bereich (falls noch kein `missions`-Top-Level-Key existiert, neu anlegen) folgenden Block hinzu:

```php
    'missions' => [
        'difficulty' => [
            // Platzhalter, Nachjustierung nach Playtest (ADR 0004).
            'base_chance' => ['leicht' => 0.85, 'normal' => 0.70, 'schwer' => 0.60],
            'reward_multiplier' => ['leicht' => 0.7, 'normal' => 1.0, 'schwer' => 1.4],
            'pilot_rank_bonus_pct' => 0.05,          // pro Pilot-Rang (1-3), additiv auf die Chance
            'knowledge_bonus_pct_per_level' => 0.03, // pro Kenntnis-Level über dem Mission-Gate
            'chance_cap' => 0.95,                    // harte Obergrenze nach allen Boni
            'hard_fail_extra_wear' => 1.0,           // zusätzlicher SP-Abzug bei Fehlschlag auf 'schwer'
        ],
    ],
```

- [ ] **Step 2: Config-Cache validieren**

Run: `php artisan config:clear && php -r "var_dump(config('game.missions.difficulty.base_chance'));" `
Expected: gibt das `['leicht'=>0.85,'normal'=>0.70,'schwer'=>0.60]`-Array aus, kein Fehler.

- [ ] **Step 3: Commit**

```bash
git add config/game.php
git commit -m "feat: game.missions.difficulty Config-Block"
```

---

## Task 3: `config/missions.php` — `difficulties` pro Katalog-Eintrag

**Files:**
- Modify: `config/missions.php`

**Interfaces:**
- Produces: `$mission['difficulties']` (Array mit genau 2 Strings aus `['leicht','normal','schwer']`) pro Katalog-Eintrag — von Task 5 (`dispatchShip`-Validierung) und Task 11 (Katalog-Preview) konsumiert.

**Zuordnung** (Erstbesetzung, game-designer-Entscheidung: ungegatete/frühe Missionen bekommen `leicht`, damit Sol-1-Runs schaffbar bleiben; kenntnisgegatete Missionen sind ohnehin Mid-Game und bekommen `normal`/`schwer`; die beiden non-repeatable Ruin-Missionen sind Sonderfälle — `mission_ruin_expedition` bleibt bei `normal`/`schwer` wie im Spec-Beispiel vorgegeben, `mission_harvester_salvage` bekommt `leicht`/`normal`, weil ein Fehlschlag hier eine knappe, einmalige Ruinen-Ressource verbrennt, ohne die begehrte Harvester-Entitlement zu bringen — höheres Risiko wäre hier unverhältnismäßig strafend). Platzhalter, nach Playtest vom game-designer nachjustierbar.

Kein TDD nötig für diesen Task selbst (reine Katalogdaten) — Validierung, dass jede Mission genau 2 Stufen hat, wird in Task 5 getestet.

- [ ] **Step 1: `difficulties` in jeden Katalog-Eintrag einfügen**

In `config/missions.php`, jeweils als letzter Key im Mission-Array (vor dem schließenden `],`):

```php
        'mission_courier_run' => [
            // ... unverändert ...
            'difficulties' => ['leicht', 'normal'],
        ],
        'mission_recon_flight' => [
            // ... unverändert ...
            'difficulties' => ['leicht', 'normal'],
        ],
        'mission_deep_survey' => [
            // ... unverändert ...
            'difficulties' => ['leicht', 'normal'],
        ],
        'mission_prospecting_flight' => [
            // ... unverändert ...
            'difficulties' => ['normal', 'schwer'],
        ],
        'mission_data_sweep' => [
            // ... unverändert ...
            'difficulties' => ['normal', 'schwer'],
        ],
        'mission_long_range_expedition' => [
            // ... unverändert ...
            'difficulties' => ['normal', 'schwer'],
        ],
        'mission_supply_run' => [
            // ... unverändert ...
            'difficulties' => ['leicht', 'normal'],
        ],
        'mission_trade_convoy' => [
            // ... unverändert ...
            'difficulties' => ['normal', 'schwer'],
        ],
        'mission_aid_transport' => [
            // ... unverändert ...
            'difficulties' => ['leicht', 'normal'],
        ],
        'mission_salvage_sweep' => [
            // ... unverändert ...
            'difficulties' => ['normal', 'schwer'],
        ],
        'mission_ruin_expedition' => [
            // ... unverändert ...
            'difficulties' => ['normal', 'schwer'],
        ],
        'mission_harvester_salvage' => [
            // ... unverändert ...
            'difficulties' => ['leicht', 'normal'],
        ],
        'mission_escort_convoy' => [
            // ... unverändert ...
            'difficulties' => ['normal', 'schwer'],
        ],
```

- [ ] **Step 2: Syntax validieren**

Run: `php -l config/missions.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add config/missions.php
git commit -m "feat: difficulties pro Katalog-Mission (leicht/normal/schwer)"
```

---

## Task 4: `HangarService::successChanceFor()` — Erfolgschance berechnen

**Files:**
- Modify: `app/Services/HangarService.php`
- Test: `tests/Feature/Hangar/HangarServiceTest.php` (falls die Datei nicht existiert, neu anlegen — prüfe zuerst mit `find tests/Feature/Hangar -iname "*Service*"`, ob bereits eine passende Testdatei für `HangarService`-Methoden existiert, sonst diese Datei anlegen mit `namespace Tests\Feature\Hangar;`, `use Illuminate\Foundation\Testing\RefreshDatabase;`, `use Database\Seeders\TestSeeder;`, `use Tests\TestCase;`)

**Interfaces:**
- Consumes: `config('game.missions.difficulty.base_chance')`, `config('game.missions.difficulty.pilot_rank_bonus_pct')`, `config('game.missions.difficulty.knowledge_bonus_pct_per_level')`, `config('game.missions.difficulty.chance_cap')` (Task 2); `$mission['requires']['knowledge']` (Katalog-Struktur, `config/missions.php`); `App\Models\Advisor` (existierendes Eloquent-Model, `where('colony_id', ...)->where('personell_id', config('advisors.pilot.id'))->value('rank')`); `HangarService::knowledgeLevel()` (bereits private in der Klasse, HangarService.php:536, wiederverwenden statt duplizieren).
- Produces: `public function successChanceFor(int $colonyId, array $mission, string $difficulty): float` — Rückgabe zwischen 0.0 und `chance_cap`. Von Task 5 (`dispatchShip`, zum Validieren/Anzeigen) und Task 11 (Katalog-Preview) konsumiert.

- [ ] **Step 1: Fehlschlagenden Test schreiben**

```php
public function test_success_chance_uses_base_chance_when_no_bonuses_apply(): void
{
    $mission = config('missions.catalog.mission_courier_run'); // ungegatet
    $service = $this->app->make(\App\Services\HangarService::class);

    $chance = $service->successChanceFor(self::COLONY_ID, $mission, 'leicht');

    $this->assertSame(0.85, $chance, 'no pilot, no knowledge gate => base_chance only');
}

public function test_success_chance_adds_pilot_rank_bonus(): void
{
    // Real advisors table columns (see data/sql/testdata.sqlite.sql): user_id,
    // colony_id, personell_id, rank, active_ticks — no hired_tick column.
    DB::table('advisors')->insert([
        'user_id' => self::USER_ID,
        'colony_id' => self::COLONY_ID,
        'personell_id' => config('advisors.pilot.id'),
        'rank' => 2,
        'active_ticks' => 0,
    ]);
    $mission = config('missions.catalog.mission_courier_run');
    $service = $this->app->make(\App\Services\HangarService::class);

    $chance = $service->successChanceFor(self::COLONY_ID, $mission, 'leicht');

    $this->assertEqualsWithDelta(0.85 + 2 * 0.05, $chance, 0.0001, 'rank 2 => +0.10 on top of base_chance');
}

public function test_success_chance_adds_knowledge_bonus_above_gate(): void
{
    // mission_prospecting_flight requires geology Lv1 — colony sits at Lv3, 2 levels above gate.
    DB::table('colony_researches')->updateOrInsert(
        ['colony_id' => self::COLONY_ID, 'research_id' => config('knowledge.geology.id')],
        ['level' => 3, 'ap_spend' => 0]
    );
    $mission = config('missions.catalog.mission_prospecting_flight');
    $service = $this->app->make(\App\Services\HangarService::class);

    $chance = $service->successChanceFor(self::COLONY_ID, $mission, 'normal');

    $this->assertEqualsWithDelta(0.70 + 2 * 0.03, $chance, 0.0001, '2 levels above the Lv1 gate => +0.06');
}

public function test_success_chance_is_capped(): void
{
    DB::table('advisors')->insert([
        'user_id' => self::USER_ID,
        'colony_id' => self::COLONY_ID,
        'personell_id' => config('advisors.pilot.id'),
        'rank' => 3,
        'active_ticks' => 0,
    ]);
    DB::table('colony_researches')->updateOrInsert(
        ['colony_id' => self::COLONY_ID, 'research_id' => config('knowledge.geology.id')],
        ['level' => 5, 'ap_spend' => 0]
    );
    $mission = config('missions.catalog.mission_prospecting_flight');
    $service = $this->app->make(\App\Services\HangarService::class);

    // leicht base_chance 0.85 + rank3*0.05=0.15 + 4 levels above gate*0.03=0.12 = 1.12 uncapped,
    // must clamp to chance_cap 0.95. ('schwer' base 0.60 would only reach 0.87 here — not high
    // enough to ever hit the cap with realistic level 5 knowledge, so this must use 'leicht'.)
    $chance = $service->successChanceFor(self::COLONY_ID, $mission, 'leicht');

    $this->assertSame(0.95, $chance, 'must clamp at chance_cap even with max pilot rank + knowledge overshoot');
}
```

Falls die Testdatei neu angelegt wird: `setUp()` mit `$this->app->make(TestSeeder::class)->run();` und `private const COLONY_ID = 1;` sowie `private const USER_ID = 3;` (gleiche Konvention wie `HangarMissionResolutionTest`), `use Illuminate\Support\Facades\DB;`.

- [ ] **Step 2: Tests laufen lassen, Fehlschlag bestätigen**

Run: `bin/phpunit --filter "test_success_chance"`
Expected: FAIL — `Call to undefined method App\Services\HangarService::successChanceFor()`

- [ ] **Step 3: `successChanceFor()` implementieren**

In `app/Services/HangarService.php`, direkt vor `dispatchShip()` (Zeile 390) einfügen:

```php
    /**
     * Success chance for a catalog mission at a given difficulty (Spec: docs/
     * superpowers/specs/2026-09-02-hangar-mission-success-chance-design.md).
     * base_chance[$difficulty] + generic Pilot-Rang bonus + missionsspezifischer
     * Kenntnis-Bonus (nur falls die Mission ein requires.knowledge-Gate hat,
     * pro Level über dem Gate), gecappt bei chance_cap.
     */
    public function successChanceFor(int $colonyId, array $mission, string $difficulty): float
    {
        $base = (float) config("game.missions.difficulty.base_chance.{$difficulty}", 0.70);

        $pilotRank = (int) (Advisor::where('colony_id', $colonyId)
            ->where('personell_id', config('advisors.pilot.id'))
            ->value('rank') ?? 0);
        $chance = $base + $pilotRank * (float) config('game.missions.difficulty.pilot_rank_bonus_pct', 0.05);

        $gate = $mission['requires']['knowledge'] ?? null;
        if ($gate !== null) {
            [$knowledgeKey, $requiredLevel] = [array_key_first($gate), reset($gate)];
            $levelsAbove = max(0, $this->knowledgeLevel($colonyId, $knowledgeKey) - $requiredLevel);
            $chance += $levelsAbove * (float) config('game.missions.difficulty.knowledge_bonus_pct_per_level', 0.03);
        }

        return min((float) config('game.missions.difficulty.chance_cap', 0.95), $chance);
    }
```

Prüfe, ob `use App\Models\Advisor;` bereits im Datei-Header importiert ist — falls nicht, ergänzen.

- [ ] **Step 4: Tests laufen lassen, Erfolg bestätigen**

Run: `bin/phpunit --filter "test_success_chance"`
Expected: PASS (4 Tests)

- [ ] **Step 5: Commit**

```bash
git add app/Services/HangarService.php tests/Feature/Hangar/HangarServiceTest.php
git commit -m "feat: HangarService::successChanceFor() für Missions-Erfolgschance"
```

---

## Task 5: `HangarService::dispatchShip()` — Schwierigkeit validieren + persistieren

**Files:**
- Modify: `app/Services/HangarService.php`
- Test: `tests/Feature/Hangar/HangarServiceTest.php` (aus Task 4)

**Interfaces:**
- Consumes: `successChanceFor()` (Task 4, nur zur Validierung dass die Methode existiert — der eigentliche Roll passiert erst bei Resolution in Task 7); `$mission['difficulties']` (Task 3).
- Produces: `dispatchShip(int $colonyId, int $instanceId, string $missionKey, ?array $target, string $difficulty): void` (neuer 5. Parameter) — schreibt `difficulty` in den `colony_hangar_missions`-Insert. Von Task 6 (`HangarController::dispatch`) konsumiert.

- [ ] **Step 1: Fehlschlagenden Test schreiben**

```php
public function test_dispatch_rejects_a_difficulty_not_offered_by_the_mission(): void
{
    $service = $this->app->make(\App\Services\HangarService::class);
    // mission_courier_run offers only ['leicht', 'normal'] (config/missions.php) — 'schwer' must be rejected.
    $this->expectException(\RuntimeException::class);

    $service->dispatchShip(self::COLONY_ID, self::HANGAR_INSTANCE, 'mission_courier_run', null, 'schwer');
}

public function test_dispatch_persists_the_chosen_difficulty(): void
{
    $service = $this->app->make(\App\Services\HangarService::class);

    $service->dispatchShip(self::COLONY_ID, self::HANGAR_INSTANCE, 'mission_courier_run', null, 'leicht');

    $this->assertSame('leicht', DB::table('colony_hangar_missions')
        ->where('colony_id', self::COLONY_ID)->where('instance_id', self::HANGAR_INSTANCE)
        ->value('difficulty'));
}
```

Diese beiden Tests brauchen ein Setup mit einem gedockten Schiff im Hangar (Nav-AP/Organika/SP-Preconditions erfüllt) — orientiere dich an `HangarServiceTest`s bestehendem Setup (falls in Task 4 neu angelegt: TestSeeder liefert bereits ein gedocktes Schiff in Instance 1 laut `HangarMissionResolutionTest`-Kommentar Zeile 24-25 — vor dem Dispatch NICHT wie `HangarMissionResolutionTest::setUp()` die Bay leeren, hier soll das TestSeeder-Schiff genutzt werden).

Falls `HangarServiceTest` bereits vor Task 4 existierte (nicht neu angelegt) und ein anderes Setup-Pattern nutzt: diesem Pattern folgen statt hier ein zweites zu etablieren.

- [ ] **Step 2: Tests laufen lassen, Fehlschlag bestätigen**

Run: `bin/phpunit --filter "test_dispatch_rejects_a_difficulty|test_dispatch_persists_the_chosen_difficulty"`
Expected: FAIL — `Too few arguments to function dispatchShip()` bzw. Missing-Argument-Error

- [ ] **Step 3: `dispatchShip()` erweitern**

In `app/Services/HangarService.php:390`, Signatur ändern:

```php
    public function dispatchShip(int $colonyId, int $instanceId, string $missionKey, ?array $target = null, string $difficulty = 'normal'): void
    {
        $mission = config("missions.catalog.{$missionKey}");
        if ($mission === null) {
            throw new RuntimeException("Unknown mission key: {$missionKey}.");
        }

        if (! in_array($difficulty, $mission['difficulties'] ?? [], true)) {
            throw new RuntimeException(__('missions.error_invalid_difficulty'));
        }
```

Und im `DB::table('colony_hangar_missions')->insert([...])`-Block (Zeile 496-506), `'difficulty' => $difficulty,` als neuen Key ergänzen (z.B. direkt nach `'target' => $targetJson,`).

Neuen Lang-Key `missions.error_invalid_difficulty` in `lang/de/missions.php` ergänzen (siehe Task 9 — falls Task 9 noch nicht ausgeführt ist, den Key hier bereits als `'error_invalid_difficulty' => 'Diese Schwierigkeitsstufe ist für diese Mission nicht verfügbar.',` im `// ── Dispatch errors ──` Block einfügen, damit dieser Test grün wird).

- [ ] **Step 4: Tests laufen lassen, Erfolg bestätigen**

Run: `bin/phpunit --filter "test_dispatch_rejects_a_difficulty|test_dispatch_persists_the_chosen_difficulty"`
Expected: PASS (2 Tests)

- [ ] **Step 5: Vollen Hangar-Test-Ordner laufen lassen (Regression)**

Run: `bin/phpunit tests/Feature/Hangar/`
Expected: alle bestehenden Tests weiterhin grün (der neue `$difficulty`-Parameter hat einen Default `'normal'`, bestehende Aufrufe ohne 5. Argument bleiben unverändert funktionsfähig)

- [ ] **Step 6: Commit**

```bash
git add app/Services/HangarService.php lang/de/missions.php tests/Feature/Hangar/HangarServiceTest.php
git commit -m "feat: dispatchShip validiert und persistiert Schwierigkeitsgrad"
```

---

## Task 6: `HangarController::dispatch()` — Request-Parameter durchreichen

**Files:**
- Modify: `app/Http/Controllers/Colony/HangarController.php`
- Test: `tests/Feature/Hangar/HangarControllerTest.php` (falls vorhanden — prüfe mit `find tests/Feature/Hangar -iname "*Controller*"`; falls keine existiert, neuen Feature-Test mit HTTP-Request gegen die Route anlegen, `use Illuminate\Foundation\Testing\RefreshDatabase;`, `$this->actingAs(...)`-Konvention wie in anderen Controller-Tests des Projekts — orientiere dich an einem bestehenden Controller-Test, z.B. `tests/Feature/Colony/*ControllerTest.php`, für das genaue Auth-Setup-Pattern)

**Interfaces:**
- Consumes: `HangarService::dispatchShip()` mit 5. Parameter `$difficulty` (Task 5).
- Produces: POST `/colony/hangar/{instanceId}/dispatch` akzeptiert zusätzlich `difficulty` im Request-Body (Pflichtfeld). Von Task 12 (Alpine-UI) konsumiert.

- [ ] **Step 1: Fehlschlagenden Test schreiben**

```php
public function test_dispatch_endpoint_rejects_missing_difficulty(): void
{
    $response = $this->actingAs(/* passendes Test-User-Setup, siehe bestehende Controller-Tests */)
        ->postJson('/colony/hangar/1/dispatch', ['mission_key' => 'mission_courier_run']);

    $response->assertStatus(422);
}

public function test_dispatch_endpoint_forwards_difficulty_to_the_service(): void
{
    $response = $this->actingAs(/* ... */)
        ->postJson('/colony/hangar/1/dispatch', ['mission_key' => 'mission_courier_run', 'difficulty' => 'leicht']);

    $response->assertStatus(200);
    $this->assertSame('leicht', DB::table('colony_hangar_missions')
        ->where('colony_id', 1)->orderByDesc('id')->value('difficulty'));
}
```

Exaktes Auth-/Fixture-Setup aus einem bestehenden Controller-Test in `tests/Feature/Colony/` oder `tests/Feature/Hangar/` übernehmen (Login-User, TestSeeder, freie Hangar-Instanz) — nicht neu erfinden, sondern das etablierte Projekt-Pattern kopieren.

- [ ] **Step 2: Tests laufen lassen, Fehlschlag bestätigen**

Run: `bin/phpunit --filter "test_dispatch_endpoint"`
Expected: FAIL — erster Test erwartet 422, bekommt aber 200 (kein Difficulty-Validierungsfehler, weil der Controller `difficulty` noch nicht validiert und `dispatchShip` den Default `'normal'` nimmt)

- [ ] **Step 3: Controller erweitern**

In `app/Http/Controllers/Colony/HangarController.php:161-189`:

```php
    public function dispatch(Request $request, int $instanceId): JsonResponse
    {
        $validated = $request->validate([
            'mission_key' => 'required|string|max:80',
            'difficulty' => 'required|string|in:leicht,normal,schwer',
            'target' => 'nullable|array',
            'target.q' => 'sometimes|integer',
            'target.r' => 'sometimes|integer',
            'target.research_id' => 'sometimes|integer',
        ]);

        $colony = $this->colonyService->getPrimeColony(Auth::id());

        try {
            $this->hangarService->dispatchShip(
                $colony->id,
                $instanceId,
                $validated['mission_key'],
                $validated['target'] ?? null,
                $validated['difficulty'],
            );
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'slot' => $this->fetchSlot($colony->id, $instanceId),
            ...$this->currentHangarResources($colony->id),
        ]);
    }
```

- [ ] **Step 4: Tests laufen lassen, Erfolg bestätigen**

Run: `bin/phpunit --filter "test_dispatch_endpoint"`
Expected: PASS (2 Tests)

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Colony/HangarController.php tests/Feature/Hangar/HangarControllerTest.php
git commit -m "feat: HangarController::dispatch validiert difficulty-Request-Feld"
```

---

## Task 7: `GameTick::processHangarMissions()` — Erfolgs-Roll + Reward-Skalierung + Fehlschlag

**Files:**
- Modify: `app/Console/Commands/GameTick.php`
- Test: `tests/Feature/GameTick/HangarMissionResolutionTest.php` — Achtung: existiert bereits unter `tests/Feature/Hangar/HangarMissionResolutionTest.php` (siehe Datei-Referenz), dort weiterschreiben statt neu anlegen.

**Interfaces:**
- Consumes: `HangarService::successChanceFor()` (Task 4) — `GameTick` braucht eine `HangarService`-Instanz; prüfe im Konstruktor von `GameTick`, ob `HangarService` bereits injiziert ist (`grep -n "private.*HangarService\|HangarService \$" app/Console/Commands/GameTick.php`) — falls nicht, per Constructor-Injection ergänzen (Laravel löst das automatisch über den Service-Container beim `Artisan::call`).
- Produces: `hangar.mission_failed`-Event (`colony_log`, Payload `{mission_key, ship_id, colony_id, difficulty}`); `payMissionRewards()` bekommt neuen Parameter `float $rewardMultiplier = 1.0`.

- [ ] **Step 1: `setUp()` der Testklasse um Determinismus-Guard erweitern**

In `tests/Feature/Hangar/HangarMissionResolutionTest.php`, `setUp()` (Zeile 47-60) ergänzen:

```php
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Neutralize the new success-roll for pre-existing reward tests below —
        // they assert reward payout, not RNG luck. Tests that specifically cover
        // success/failure override this locally with an explicit Config::set().
        \Illuminate\Support\Facades\Config::set('game.missions.difficulty.base_chance', [
            'leicht' => 1.0, 'normal' => 1.0, 'schwer' => 1.0,
        ]);

        DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)
            ->where('hangar_instance_id', self::HANGAR_INSTANCE)
            ->update(['hangar_instance_id' => null]);

        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
    }
```

Dieser Schritt allein macht noch keinen Test rot (nur Vorbereitung) — direkt mit Step 2 fortfahren.

- [ ] **Step 2: Fehlschlagende Tests für Erfolgs-Roll, Fehlschlag, Reward-Skalierung schreiben**

Am Ende der Klasse (vor der schließenden `}`), neue Sektion:

```php
    // ── Success chance / difficulty (Spec: docs/superpowers/specs/2026-09-02-hangar-mission-success-chance-design.md) ──

    public function test_success_rolls_pay_the_scaled_reward(): void
    {
        // base_chance forced to 1.0 in setUp() — success guaranteed regardless of seed.
        // reward_multiplier['schwer'] = 1.4 (config/game.php) → 90 * 1.4 = 126.
        $missionId = $this->dispatchFixture('mission_courier_run', 1, dispatchTick: 21000, difficulty: 'schwer');

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 21002]);

        $this->assertSame('completed', $this->missionState($missionId));
        $log = DB::table('colony_log')
            ->where('user', self::USER_ID)->where('tick', 21002)->where('event', 'hangar.mission_completed')
            ->first();
        $this->assertNotNull($log);
        $params = json_decode($log->parameters, true);
        $this->assertSame(126, $params['rewards']['credits'], 'schwer multiplies the base 90 credits by 1.4');
    }

    public function test_failed_roll_pays_no_reward_and_fires_mission_failed_event(): void
    {
        DB::table('runs')->where('id', 1)->update(['rng_seed' => 999]);
        \Illuminate\Support\Facades\Config::set('game.missions.difficulty.base_chance', [
            'leicht' => 0.0, 'normal' => 0.0, 'schwer' => 0.0,
        ]);
        $missionId = $this->dispatchFixture('mission_courier_run', 1, dispatchTick: 21100, difficulty: 'normal');

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 21102]);

        $this->assertSame('completed', $this->missionState($missionId), 'a failed roll is still a completed mission outcome, not aborted');
        $this->assertSame('docked', $this->shipState());
        $this->assertDatabaseMissing('colony_log', [
            'user' => self::USER_ID, 'tick' => 21102, 'event' => 'hangar.mission_completed',
        ]);
        $log = DB::table('colony_log')
            ->where('user', self::USER_ID)->where('tick', 21102)->where('event', 'hangar.mission_failed')
            ->first();
        $this->assertNotNull($log);
        $params = json_decode($log->parameters, true);
        $this->assertSame('mission_courier_run', $params['mission_key']);
        $this->assertSame('normal', $params['difficulty']);
    }

    public function test_hard_fail_on_schwer_applies_extra_wear_on_top_of_normal_wear(): void
    {
        \Illuminate\Support\Facades\Config::set('game.missions.difficulty.base_chance', [
            'leicht' => 0.0, 'normal' => 0.0, 'schwer' => 0.0,
        ]);
        // drone wear_per_sol = 1.5 (config/ships.php), hard_fail_extra_wear = 1.0 (config/game.php).
        // 20.0 - 1.5 (normal wear, applied every tick) - 1.0 (hard fail extra) = 17.5.
        $missionId = $this->dispatchFixture('mission_courier_run', 1, dispatchTick: 21200, statusPoints: 20.0, difficulty: 'schwer');

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 21202]);

        $this->assertEqualsWithDelta(17.5, $this->shipStatusPoints(), 0.001);
        $this->assertSame('completed', $this->missionState($missionId));
    }

    public function test_hard_fail_extra_wear_does_not_drop_ship_below_zero_sp(): void
    {
        \Illuminate\Support\Facades\Config::set('game.missions.difficulty.base_chance', [
            'leicht' => 0.0, 'normal' => 0.0, 'schwer' => 0.0,
        ]);
        // 1.6 SP - 1.5 normal wear = 0.1, stays above zero (no abort), then -1.0 hard-fail must floor at 0, not go negative.
        $missionId = $this->dispatchFixture('mission_courier_run', 1, dispatchTick: 21300, statusPoints: 1.6, difficulty: 'schwer');

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 21302]);

        $this->assertSame(0.0, $this->shipStatusPoints());
        $this->assertSame('completed', $this->missionState($missionId), 'hard-fail wear floor must not retroactively trigger an abort');
    }
```

`dispatchFixture()` braucht einen neuen optionalen Parameter — siehe Step 3.

- [ ] **Step 3: `dispatchFixture()`-Helper um `difficulty` erweitern**

In `tests/Feature/Hangar/HangarMissionResolutionTest.php`, Signatur (Zeile 66-72) und Insert (Zeile 84-95):

```php
    private function dispatchFixture(
        string $missionKey,
        int $solDistance,
        int $dispatchTick,
        float $statusPoints = 20.0,
        ?array $target = null,
        string $difficulty = 'normal'
    ): int {
        DB::table('colony_ships')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'ship_id' => self::SHIP_DRONE],
            [
                'hangar_instance_id' => self::HANGAR_INSTANCE,
                'ship_state' => 'dispatched',
                'level' => 1,
                'status_points' => $statusPoints,
                'ap_spend' => 0,
            ]
        );

        return DB::table('colony_hangar_missions')->insertGetId([
            'colony_id' => self::COLONY_ID,
            'instance_id' => self::HANGAR_INSTANCE,
            'ship_id' => self::SHIP_DRONE,
            'destination' => $missionKey,
            'sol_distance' => $solDistance,
            'target' => $target !== null ? json_encode($target) : null,
            'difficulty' => $difficulty,
            'dispatch_tick' => $dispatchTick,
            'recall_tick' => null,
            'state' => 'active',
            'created_at' => now(),
        ]);
    }
```

- [ ] **Step 4: Tests laufen lassen, Fehlschlag bestätigen**

Run: `bin/phpunit tests/Feature/Hangar/HangarMissionResolutionTest.php`
Expected: die 4 neuen Tests FAIL (kein Erfolgs-Roll implementiert, `mission_failed`-Event existiert nicht, keine Reward-Skalierung, kein `hard_fail_extra_wear`). Die bestehenden Tests bleiben grün (Determinismus-Guard aus Step 1 greift bereits, ändert an ihrem Verhalten nichts, da `payMissionRewards` noch nicht gated ist).

- [ ] **Step 5: `processHangarMissions()` und `payMissionRewards()` implementieren**

In `app/Console/Commands/GameTick.php`, ersetze den Block ab Zeile 338 (`// Mission complete: pay rewards, dock the ship.`) bis Zeile 373 (Ende der `foreach`) durch:

```php
            // Mission complete: roll success, pay rewards or fail.
            $catalogEntry = config("missions.catalog.{$mission->destination}");
            $difficulty = DB::table('colony_hangar_missions')->where('id', $mission->mission_id)->value('difficulty') ?? 'normal';
            $rewardDetails = [];
            $success = true;

            if ($catalogEntry !== null) {
                $successChance = $this->hangarService->successChanceFor((int) $mission->colony_id, $catalogEntry, $difficulty);
                $roll = $this->seededRoll($rngSeed + (int) $mission->mission_id + 1, 0, 9999) / 10000;
                $success = $roll <= $successChance;

                if ($success) {
                    $rewardMultiplier = (float) config("game.missions.difficulty.reward_multiplier.{$difficulty}", 1.0);
                    $rewardDetails = $this->payMissionRewards(
                        (int) $mission->colony_id,
                        $userId,
                        $catalogEntry['reward'],
                        $mission->target !== null ? json_decode($mission->target, true) : null,
                        $rngSeed + (int) $mission->mission_id,
                        $tick,
                        $rewardMultiplier
                    );
                } elseif ($difficulty === 'schwer') {
                    $extraWear = (float) config('game.missions.difficulty.hard_fail_extra_wear', 1.0);
                    $spAfterHardFail = max(0.0, (float) $newSp - $extraWear);
                    DB::table('colony_ships')->where('id', $mission->colony_ship_id)
                        ->update(['status_points' => $spAfterHardFail]);
                }
            }

            DB::table('colony_ships')->where('id', $mission->colony_ship_id)
                ->update(['ship_state' => 'docked']);
            DB::table('colony_hangar_missions')->where('id', $mission->mission_id)
                ->update(['state' => 'completed']);

            if ($userId !== null) {
                $this->eventService->createEvent([
                    'user' => $userId,
                    'tick' => $tick,
                    'event' => $success ? 'hangar.mission_completed' : 'hangar.mission_failed',
                    'area' => 'colony',
                    'parameters' => json_encode($success ? [
                        'mission_key' => $mission->destination,
                        'ship_id' => (int) $mission->ship_id,
                        'colony_id' => (int) $mission->colony_id,
                        'rewards' => $rewardDetails,
                    ] : [
                        'mission_key' => $mission->destination,
                        'ship_id' => (int) $mission->ship_id,
                        'colony_id' => (int) $mission->colony_id,
                        'difficulty' => $difficulty,
                    ]),
                ]);
            }

            $completed++;
```

`payMissionRewards()` (Zeile 384-438) Signatur und Skalierung erweitern:

```php
    private function payMissionRewards(
        int $colonyId,
        ?int $userId,
        array $reward,
        ?array $target,
        int $seed,
        int $tick,
        float $rewardMultiplier = 1.0
    ): array {
        // loot_table: seeded pick of one entry, then resolve that entry's rewards.
        if (isset($reward['loot_table'])) {
            $table = $reward['loot_table'];
            $reward = $table[$this->seededRoll($seed, 0, count($table) - 1)];
        }

        $resourceIds = ['regolith' => 3, 'compounds' => 4, 'organics' => 5];
        // Only quantity-shaped reward types scale with difficulty — flags/unlocks
        // (trust_event, harvester_instance, deep_scan) stay binary regardless of difficulty.
        $scalableTypes = ['credits', 'regolith', 'compounds', 'organics', 'research_ap', 'reveal_tiles'];
        $details = [];

        foreach ($reward as $type => $value) {
            if (is_array($value) && count($value) === 2 && isset($value[0], $value[1])) {
                $value = $this->seededRoll($seed + crc32($type), (int) $value[0], (int) $value[1]);
            }

            if (in_array($type, $scalableTypes, true)) {
                $value = (int) round((int) $value * $rewardMultiplier);
            }

            if ($type === 'credits') {
                if ($userId !== null) {
                    DB::table('user_resources')->where('user_id', $userId)->increment('credits', (int) $value);
                }
            } elseif (isset($resourceIds[$type])) {
                DB::table('colony_resources')
                    ->where('colony_id', $colonyId)
                    ->where('resource_id', $resourceIds[$type])
                    ->update(['amount' => DB::raw('amount + '.(int) $value)]);
            } elseif ($type === 'trust_event') {
                $this->trustService->fireEvent($colonyId, (string) $value, $tick);
            } elseif ($type === 'reveal_tiles') {
                $value = $this->revealTiles($colonyId, (int) $value);
            } elseif ($type === 'deep_scan') {
                $this->deepScanTarget($colonyId, $target);
            } elseif ($type === 'research_ap') {
                $this->grantResearchAp($colonyId, $target, (int) $value);
            } elseif ($type === 'harvester_instance') {
                if ($userId !== null) {
                    $this->harvesterEntitlementService->grantSalvage($userId);
                }
            }

            $details[$type] = $value;
        }

        return $details;
    }
```

Beachte: `reveal_tiles` wird zuerst skaliert (`$value = round($value * $multiplier)`) und danach durch `revealTiles()` überschrieben (Rückgabe = tatsächlich aufgedeckte Anzahl) — das ist bereits bestehendes Verhalten (Zeile 417-418 alt), bleibt unverändert korrekt, weil `revealTiles($colonyId, (int) $value)` den skalierten Wert als `$count`-Limit nimmt.

Falls `HangarService` noch nicht im `GameTick`-Konstruktor injiziert ist: `private HangarService $hangarService` als Property + Constructor-Parameter ergänzen, `use App\Services\HangarService;` im Header.

- [ ] **Step 6: Tests laufen lassen, Erfolg bestätigen**

Run: `bin/phpunit tests/Feature/Hangar/HangarMissionResolutionTest.php`
Expected: alle Tests PASS (bestehende + 4 neue)

- [ ] **Step 7: Vollen Feature-Suite-Lauf gegen Regression**

Run: `bin/phpunit --testsuite=laravel-feature`
Expected: alle Tests grün, keine Regression außerhalb Hangar

- [ ] **Step 8: Commit**

```bash
git add app/Console/Commands/GameTick.php tests/Feature/Hangar/HangarMissionResolutionTest.php
git commit -m "feat: Missions-Erfolgs-Roll, Reward-Skalierung, Hard-Fail-Wear"
```

---

## Task 8: `SolReportService::eventsGroup()` — Fehlschlag im Sol-Report

**Files:**
- Modify: `app/Services/SolReportService.php`
- Test: `tests/Feature/SolReportTest.php`

**Interfaces:**
- Consumes: `hangar.mission_failed`-Event-Payload (Task 7: `{mission_key, ship_id, colony_id, difficulty}`).
- Produces: eine zusätzliche Zeile in der `eventsGroup()`-Ausgabe für jeden `hangar.mission_failed`-Eintrag, Label `missions.sol_report_failed` (Task 9), Ton `warning`, `beat: true` (analog zu `hangar.mission_aborted`).

- [ ] **Step 1: Fehlschlagenden Test schreiben**

Orientiere dich am bestehenden Test-Pattern für `hangar.mission_aborted` in `tests/Feature/SolReportTest.php` (suche mit `grep -n "mission_aborted" tests/Feature/SolReportTest.php` nach dem exakten Vorbild-Test und kopiere dessen Fixture-Aufbau):

```php
public function test_hangar_mission_failed_shows_a_warning_line_in_events_group(): void
{
    DB::table('colony_log')->insert([
        'user' => self::USER_ID,
        'tick' => self::TICK,
        'event' => 'hangar.mission_failed',
        'area' => 'colony',
        'parameters' => json_encode([
            'mission_key' => 'mission_courier_run',
            'ship_id' => 85,
            'colony_id' => self::COLONY_ID,
            'difficulty' => 'schwer',
        ]),
        'created_at' => now(),
    ]);

    $report = $this->app->make(SolReportService::class)->buildFor(self::COLONY_ID, self::TICK);
    $eventsGroup = collect($report['groups'])->firstWhere('title', /* exakter Group-Titel, siehe bestehenden aborted-Test */);

    $line = collect($eventsGroup['lines'])->firstWhere('label', __('missions.sol_report_failed'));
    $this->assertNotNull($line);
    $this->assertSame(__('missions.mission_courier_run_name'), $line['detail']);
    $this->assertSame('warning', $line['tone']);
}
```

Passe `self::USER_ID`, `self::TICK`, `self::COLONY_ID` und den Group-Titel-Lookup exakt an das Muster des Nachbar-Tests für `mission_aborted` an (nicht raten — den bestehenden Test als Vorlage kopieren und nur Event-Name/Parameter ändern).

- [ ] **Step 2: Test laufen lassen, Fehlschlag bestätigen**

Run: `bin/phpunit --filter test_hangar_mission_failed_shows_a_warning_line_in_events_group`
Expected: FAIL — keine passende Zeile gefunden (`hangar.mission_failed` wird von `eventsGroup()` noch nicht gelesen)

- [ ] **Step 3: `eventsGroup()` erweitern**

In `app/Services/SolReportService.php`, direkt nach dem bestehenden `hangar.mission_aborted`-Block (Zeile 264-272):

```php
        foreach ($events['hangar.mission_failed'] ?? [] as $params) {
            $missionKey = $params['mission_key'] ?? '';
            $lines[] = [
                'label' => __('missions.sol_report_failed'),
                'detail' => __("missions.{$missionKey}_name"),
                'tone' => 'warning',
                'beat' => true,
            ];
        }
```

- [ ] **Step 4: Test laufen lassen, Erfolg bestätigen**

Run: `bin/phpunit --filter test_hangar_mission_failed_shows_a_warning_line_in_events_group`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/SolReportService.php tests/Feature/SolReportTest.php
git commit -m "feat: Sol-Report zeigt fehlgeschlagene Hangar-Missionen"
```

---

## Task 9: `lang/de/missions.php` — neue Strings

**Files:**
- Modify: `lang/de/missions.php`

**Interfaces:**
- Produces: `missions.error_invalid_difficulty` (Task 5, ggf. bereits dort ergänzt — hier nur verifizieren, nicht doppelt anlegen), `missions.sol_report_failed` (Task 8), `missions.difficulty_leicht`/`missions.difficulty_normal`/`missions.difficulty_schwer` (Task 12, UI-Labels), `missions.difficulty_chance_label` (Task 12, z.B. "Erfolgschance: :chance%").

Kein TDD nötig — reine Sprachdatei-Ergänzung ohne eigenen Codepfad (die Konsumenten-Tests in Task 5/8 decken bereits ab, dass die Keys existieren und korrekt aufgelöst werden; Task 12 ist UI, kein PHPUnit).

- [ ] **Step 1: Strings ergänzen**

In `lang/de/missions.php`, im `// ── Dispatch errors ──` Block (falls durch Task 5 noch nicht geschehen):

```php
    'error_invalid_difficulty' => 'Diese Schwierigkeitsstufe ist für diese Mission nicht verfügbar.',
```

Im `// ── Sol report ──` Block, nach `sol_report_aborted`:

```php
    'sol_report_failed' => 'Mission fehlgeschlagen — kein Fund',
```

Neuer Block vor `// ── Sol report ──`:

```php
    // ── Difficulty ───────────────────────────────────────────────────────────

    'difficulty_leicht' => 'Leicht',
    'difficulty_normal' => 'Normal',
    'difficulty_schwer' => 'Schwer',
    'difficulty_chance_label' => 'Erfolgschance: :chance%',
    'difficulty_reward_label' => 'Belohnung ×:multiplier',
```

- [ ] **Step 2: Syntax validieren**

Run: `php -l lang/de/missions.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add lang/de/missions.php
git commit -m "content: Lang-Strings für Schwierigkeitsgrad + Fehlschlag"
```

---

## Task 10: `HangarService::getMissionCatalogFor()` — Schwierigkeits-Preview

**Files:**
- Modify: `app/Services/HangarService.php`
- Test: `tests/Feature/Hangar/HangarServiceTest.php`

**Interfaces:**
- Consumes: `successChanceFor()` (Task 4), `config('game.missions.difficulty.reward_multiplier')` (Task 2), `$mission['difficulties']` (Task 3).
- Produces: jeder Katalog-Eintrag aus `getMissionCatalogFor()` bekommt einen neuen Key `'difficulty_options'` — Array von `['key' => string, 'label' => string, 'chance_pct' => int, 'reward_multiplier' => float]`, einen Eintrag pro in `$mission['difficulties']` freigeschalteter Stufe. Von Task 12 (Alpine-Dispatch-Dialog) konsumiert.

- [ ] **Step 1: Fehlschlagenden Test schreiben**

```php
public function test_mission_catalog_includes_difficulty_options_with_chance_and_multiplier(): void
{
    $service = $this->app->make(\App\Services\HangarService::class);

    $entries = $service->getMissionCatalogFor(self::COLONY_ID);
    $courierRun = collect($entries)->firstWhere('key', 'mission_courier_run');

    $this->assertNotNull($courierRun['difficulty_options']);
    $this->assertCount(2, $courierRun['difficulty_options'], 'mission_courier_run offers leicht + normal');
    $leicht = collect($courierRun['difficulty_options'])->firstWhere('key', 'leicht');
    $this->assertSame('Leicht', $leicht['label']);
    $this->assertSame(85, $leicht['chance_pct'], 'base_chance 0.85, no bonuses => 85%');
    $this->assertSame(0.7, $leicht['reward_multiplier']);
}
```

- [ ] **Step 2: Test laufen lassen, Fehlschlag bestätigen**

Run: `bin/phpunit --filter test_mission_catalog_includes_difficulty_options_with_chance_and_multiplier`
Expected: FAIL — `difficulty_options` ist `null`

- [ ] **Step 3: `getMissionCatalogFor()` erweitern**

In `app/Services/HangarService.php`, im `foreach (config('missions.catalog', []) as $key => $mission)`-Block (Zeile 623-684), vor dem `$entries[] = [...]`-Block:

```php
            $difficultyOptions = [];
            foreach ($mission['difficulties'] ?? [] as $difficultyKey) {
                $difficultyOptions[] = [
                    'key' => $difficultyKey,
                    'label' => __("missions.difficulty_{$difficultyKey}"),
                    'chance_pct' => (int) round($this->successChanceFor($colonyId, $mission, $difficultyKey) * 100),
                    'reward_multiplier' => (float) config("game.missions.difficulty.reward_multiplier.{$difficultyKey}", 1.0),
                ];
            }
```

Und im bestehenden `$entries[] = [...]`-Array (Zeile 666-683) einen neuen Key ergänzen:

```php
                'difficulty_options' => $difficultyOptions,
```

- [ ] **Step 4: Test laufen lassen, Erfolg bestätigen**

Run: `bin/phpunit --filter test_mission_catalog_includes_difficulty_options_with_chance_and_multiplier`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/HangarService.php tests/Feature/Hangar/HangarServiceTest.php
git commit -m "feat: Missionskatalog liefert Schwierigkeits-Preview (Chance + Reward-Multiplikator)"
```

---

## Task 11: Alpine.js Dispatch-Dialog — Schwierigkeitsauswahl

**Files:**
- Modify: `public/js/hangar.js`
- Modify: `resources/views/colony/hangar.blade.php`

**Interfaces:**
- Consumes: `mission.difficulty_options` (Task 10, bereits in `window.__hangarData.missionCatalog` enthalten, da `getMissionCatalogFor()` die Blade-Daten speist).
- Produces: `missionModal.selectedDifficulty` (State), POST-Payload an `/colony/hangar/{id}/dispatch` enthält `difficulty`.

Kein PHPUnit-Test (reines Frontend) — **manuelle Browser-Verifikation Pflicht** (CLAUDE.md: UI-Änderungen vor Fertigmeldung im Browser testen, golden path + Edge Cases).

- [ ] **Step 1: `missionModal`-State um `selectedDifficulty` erweitern**

In `public/js/hangar.js`, `missionModal`-Objekt (Zeile 40-48) und `openMissionDialog()` (Zeile 142-150):

```js
        missionModal: {
            open: false,
            instanceId: null,
            shipKey: null,
            selectedKey: null,
            selectedDifficulty: null,
            targetIndex: '',
            loading: false,
            error: null,
        },
```

```js
        openMissionDialog(instanceId) {
            const slot = this.slots.find((s) => s.instance_id === instanceId);
            const shipKey = slot?.ship ? (HANGAR_SHIP_ID_TO_KEY[slot.ship.ship_id] ?? null) : null;
            this.missionModal = {
                open: true,
                instanceId,
                shipKey,
                selectedKey: null,
                selectedDifficulty: null,
                targetIndex: '',
                loading: false,
                error: null,
            };
        },
```

- [ ] **Step 2: `selectMission()` setzt die erste verfügbare Schwierigkeit als Default**

In `public/js/hangar.js`, `selectMission()` (Zeile 166-172):

```js
        selectMission(mission) {
            if (mission.availability !== 'ok') return;
            if (this.missionModal.selectedKey === mission.key) return;
            this.missionModal.selectedKey = mission.key;
            this.missionModal.selectedDifficulty = mission.difficulty_options?.[0]?.key ?? null;
            this.missionModal.targetIndex = '';
            this.missionModal.error = null;
        },

        /**
         * Marks a difficulty tier as selected for the currently chosen mission.
         * @param {string} difficultyKey
         */
        selectDifficulty(difficultyKey) {
            this.missionModal.selectedDifficulty = difficultyKey;
        },
```

- [ ] **Step 3: `submitMission()` schickt `difficulty` mit**

In `public/js/hangar.js`, `submitMission()` (Zeile 241-262), Payload erweitern:

```js
                const res = await this._post(url, {
                    mission_key: mission.key,
                    difficulty: this.missionModal.selectedDifficulty,
                    target: this.missionRequiresTarget(mission) ? this._missionTargetPayload(mission) : null,
                });
```

- [ ] **Step 4: `startMission()`-Guard: Difficulty muss gewählt sein bevor abgeschickt wird**

In `public/js/hangar.js`, `startMission()` (Zeile 210-218), nach dem Target-Guard:

```js
        startMission(mission) {
            if (mission.availability !== 'ok' || this.missionModal.loading) return;
            if (this.missionModal.selectedKey !== mission.key) {
                this.selectMission(mission);
                if (this.missionRequiresTarget(mission)) return; // let the player pick a target first
            }
            if (this.missionRequiresTarget(mission) && this.missionModal.targetIndex === '') return;
            if (!this.missionModal.selectedDifficulty) return;
            this.submitMission(mission);
        },
```

- [ ] **Step 5: Blade-Template — Schwierigkeits-Toggle in der Mission-Card**

In `resources/views/colony/hangar.blade.php`, nach dem Target-Picker-Block (Zeile 499-510, vor dem `<div class="hangar-mission-card-footer">`):

```blade
                            {{-- Difficulty picker — only for the selected mission --}}
                            <template x-if="missionModal.selectedKey === mission.key">
                                <div class="hangar-mission-difficulty" @click.stop>
                                    <template x-for="option in mission.difficulty_options" :key="option.key">
                                        <button type="button" class="hangar-difficulty-chip"
                                            :class="{ 'hangar-difficulty-chip--selected': missionModal.selectedDifficulty === option.key }"
                                            @click="selectDifficulty(option.key)">
                                            <span x-text="option.label"></span>
                                            <span x-text="option.chance_pct + '%'"></span>
                                        </button>
                                    </template>
                                </div>
                            </template>
```

- [ ] **Step 6: `js/hangar.css` — Styling für den Difficulty-Toggle**

In `public/css/hangar.css`, analog zu bestehenden Chip-Klassen (`.hangar-mission-chip`) einen `.hangar-mission-difficulty`-Flex-Container und `.hangar-difficulty-chip`/`.hangar-difficulty-chip--selected` ergänzen — orientiere dich am bestehenden Chip-Styling in derselben Datei (Farben/Radius/Padding übernehmen, `--selected` bekommt die Akzentfarbe als Border/Background analog zu `.hangar-mission-card--selected`).

- [ ] **Step 7: Prettier laufen lassen**

Run: `npx prettier --write public/js/hangar.js resources/views/colony/hangar.blade.php public/css/hangar.css`
Run erneut (Blade braucht laut CLAUDE.md zwei Durchläufe für Idempotenz): `npx prettier --write resources/views/colony/hangar.blade.php`

- [ ] **Step 8: Manuelle Browser-Verifikation**

Dev-Server starten, Hangar-Screen öffnen, ein gedocktes Schiff dispatchen: Mission auswählen → Schwierigkeits-Chips erscheinen mit Chance-% → Auswahl wechseln aktualisiert `selectedDifficulty` → Dispatch abschicken → Netzwerk-Tab zeigt `difficulty` im Request-Body → Server-Antwort 200. Zusätzlich: Mission mit `target_type` (z.B. `mission_deep_survey`) testen — Target- UND Difficulty-Picker müssen beide gleichzeitig sichtbar und bedienbar sein, ohne sich zu überlappen.

- [ ] **Step 9: Commit**

```bash
git add public/js/hangar.js resources/views/colony/hangar.blade.php public/css/hangar.css
git commit -m "feat: Hangar-Dispatch-Dialog Schwierigkeitsauswahl (Alpine)"
```

---

## Task 12: GDD §8b + `game-reference.md`

**Files:**
- Modify: `docs/GDD.md` (§8b)
- Modify: `docs/game-reference.md`

Kein TDD nötig — reine Doku-Ergänzung ohne Codepfad (CLAUDE.md TDD-Ausnahme: Doku-Änderungen).

- [ ] **Step 1: GDD §8b Prosa-Ergänzung**

In `docs/GDD.md`, Abschnitt §8b (Hangar-Missionen), Prosa-Absatz ergänzen: Missionen haben jetzt eine Erfolgschance statt garantiertem Ausgang; die Chance hängt von der gewählten Schwierigkeitsstufe ab (jede Mission bietet zwei von drei Stufen zur Wahl) sowie vom Pilot-Rang und — falls die Mission ein Kenntnis-Gate hat — vom Kenntnis-Level über dem Gate. Ein Fehlschlag zahlt keine Belohnung aus; auf der höchsten Stufe kostet ein Fehlschlag das Schiff zusätzliche Abnutzung. Keine konkreten Zahlen im Fließtext (ADR 0004) — Verweis: "siehe `docs/game-reference.md#hangar-missionen-schwierigkeit`".

- [ ] **Step 2: `game-reference.md` neue Tabelle**

In `docs/game-reference.md`, neuer Abschnitt `## Hangar-Missionen: Schwierigkeit` (Anker `#hangar-missionen-schwierigkeit`) mit einer Tabelle: Stufe | Basis-Erfolgschance | Reward-Multiplikator, plus den globalen Werten (Pilot-Rang-Bonus, Kenntnis-Bonus/Level, Chance-Cap, Hard-Fail-Extra-Wear) — Werte aus `config/game.php` `missions.difficulty` übernehmen. Zusätzlich eine Tabelle "Mission → Schwierigkeitsstufen" mit den 13 Katalog-Einträgen und ihrer `difficulties`-Zuordnung aus Task 3.

- [ ] **Step 3: Commit**

```bash
git add docs/GDD.md docs/game-reference.md
git commit -m "docs: GDD + game-reference.md für Hangar-Missions-Erfolgschance"
```

---

## Self-Review-Notiz (bereits durchgeführt beim Schreiben dieses Plans)

- **Spec-Abdeckung**: alle 7 Entscheidungspunkte der Spec sind auf Tasks gemappt (Fehlschlag-Konsequenz → Task 7; Schwierigkeitswahl pro Dispatch, 2 von 3 → Task 3/5; Reward-Skalierung → Task 7; Dispatch-Kosten unverändert → keine Änderung nötig, in Task 5 nicht angefasst; Bonus-Quellen → Task 4; keine Zeit-Progression → keine Änderung nötig; RNG-Reuse → Task 7).
- **Datenmodell**: Task 1 deckt die Migration ab.
- **UI**: Task 11 deckt den Dispatch-Dialog ab; die Mission-Ergebnis-Anzeige (Sol-Report) ist Task 8.
- **Lokalisierung**: Task 9.
- Offene Nicht-Fragen der Spec (konkrete Zahlenwerte, Mission-zu-Stufen-Zuordnung) sind in Task 2/3 mit begründeten Platzhaltern besetzt, explizit als Playtest-Nachjustierung markiert — keine Blocker-Rückfrage nötig.
