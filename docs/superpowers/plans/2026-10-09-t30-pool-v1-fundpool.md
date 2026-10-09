# T30 Pool v1 (Fundpool der Erkundung) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Auf der Karte liegen pro Run 7 verdeckte Signale (fester Start-Pool, 40 Rg brutto). Aufdecken, Tiefenscan und Bergung (AP-Projekt) machen daraus pfadneutral Regolith und binden die brach liegenden AP aus Sol 4–12 (K4/K5).

**Architecture:** Neuer `FindPoolService` erzeugt den Pool deterministisch aus `runs.rng_seed` mit eigenem Salt-Generator (bestehende Karten bleiben seed-identisch). `ColonyTileService` bekommt konfigurierbare Scan-Kosten und die neue Aktion `salvageFind()`. UI zeigt eine Fundkarte im Tile-Panel, der PlaytestBot bekommt die Regel `invest_find`, der RunReport die Quelle `find`.

**Tech Stack:** Laravel 12 / PHP 8.2, MySQL 8, Alpine.js + PicoCSS, PHPUnit (`bin/phpunit`).

**Spec:** `docs/superpowers/specs/2026-10-05-t30-gleichwertige-eroeffnungen.md` §11.3 (Pool), §11.9 Schritt 2. Owner-Freigabe 2026-10-09: F1 Wirkungs-Parität ja, F2 ja, F4 Nachschub erst nach Messung (NICHT in diesem Plan), F5 Cantina zweistufig.

## Global Constraints

- Nicht in diesem Plan: Nachschub-Signale, Hangar-Bergungsflug, Cantina-Gerüchte, Querverbindungen, `event_ruin`-Generator, Änderungen an Prospektionsflug/Versorgungsfahrt (eigene Pläne, Schritte 3–6).
- Start-Pool: **7 Signale** = 2 Fehlalarm (`find_false`), 3 klein (`find_small`), 1 mittel (`find_medium`), 1 groß (`find_large`); nur auf Kacheln mit `tile_type = terrain_empty` in Ring 2 und Ring 3 (nie Ring 0/1, Gefahr, unpassierbar, Regolith).
- Funde: klein 4 Rg / 12 AP, mittel 10 Rg / 16 AP, groß 18 Rg / 22 AP. Tiefenscan 2 AP, 1 AP mit Uplink-Station Lv2 (aus Config, nicht hartcodiert).
- Bergungsprojekt: Deckel **4 AP pro Sol je Projekt**, höchstens **2 offene Projekte** gleichzeitig.
- Größere Funde bevorzugt auf Ring-3-Kandidaten; fehlen Ring-3-Felder, Ring 2.
- Determinismus: eigener Generator `SeededRandom::generator($rngSeed + FindPoolService::SALT)`; niemals Zeilen-IDs (R5b, siehe `app/Support/RunSeed.php`). Der bestehende Kartenstrom (`randomizeOuterRingRows`) darf sich nicht ändern; gepaarte Baselines müssen gültig bleiben.
- Code/Kommentare Englisch, `lang/de/*` Deutsch, Blade-Texte via `__('key')`. Config ist Source of Truth; `docs/game-reference.md` nachziehen.
- TDD: erst roter Test, dann Code. Schnelle Suite während der Arbeit: `bin/phpunit --testsuite=laravel-feature,laravel-unit`; volle Suite vor PR.
- CHANGELOG: genau ein Block pro Tag (`## 2026-10-09` besteht bereits auf dem Branch → ergänzen, nicht neu anlegen).
- Nie auf `master` committen. Dieser Plan läuft auf einem neuen Branch `feat/t30-pool-v1`, abgezweigt von `feat/t30-harvester-yield`.

## Review Focus

- Karte ohne genug leere Ring-2/3-Kandidaten (<7): Generator setzt so viele Signale wie möglich, wirft nicht.
- Doppelter Scan, Scan unaufgedeckter Kachel, Scan eines Fehlalarms/leeren Felds: saubere Fehlercodes, keine AP-Abbuchung.
- Bergung: mehr AP als Rest oder Deckel angegeben, zweiter Aufruf im selben Sol, dritter offener Projekt-Start, Fehlalarm bergen, nicht gescannte Kachel bergen: abgelehnt ohne AP-Verlust.
- Letzte Bergungs-AP schließt exakt ab: Regolith genau einmal gutgeschrieben, Kachel danach `event_type = null`, frei bebaubar.
- Fund unter dem Harvester oder einer Baustelle: Scan/Bergung nur auf unbebauten Kacheln, Bauen auf einer Signal-Kachel ohne Scan bleibt erlaubt, aber verwirft den Fund nicht still (siehe Task 4).
- Zwei Läufe mit gleichem `rng_seed`: identische Signal-Lage.

---

### Task 1: Config und Migration

**Files:**
- Modify: `config/game.php` (neuer Block `finds`, neben `colony`)
- Create: `database/migrations/2026_10_09_000001_add_salvage_columns_to_colony_tiles.php`
- Test: `tests/Unit/FindsConfigTest.php`

**Interfaces:**
- Produces: `config('game.finds.start_pool')` (`array<string,int>`), `config('game.finds.types')` (`array<string,array{rg:int,ap:int}>`), `config('game.finds.scan_ap')` int, `config('game.finds.scan_ap_uplink')` int, `config('game.finds.salvage_cap_per_sol')` int, `config('game.finds.max_open_projects')` int. Spalten `colony_tiles.salvage_ap_spent` (int, default 0) und `colony_tiles.salvage_tick` (int, nullable).

- [ ] **Step 1: Failing test**

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;

class FindsConfigTest extends TestCase
{
    public function test_start_pool_is_seven_signals_worth_forty_regolith(): void
    {
        $pool = config('game.finds.start_pool');
        $types = config('game.finds.types');

        $this->assertSame(7, array_sum($pool));
        $rg = 0;
        foreach ($pool as $type => $count) {
            $rg += $count * $types[$type]['rg'];
        }
        $this->assertSame(40, $rg);
        $this->assertSame(['rg' => 18, 'ap' => 22], $types['find_large']);
        $this->assertSame(['rg' => 0, 'ap' => 0], $types['find_false']);
        $this->assertSame(2, config('game.finds.scan_ap'));
        $this->assertSame(1, config('game.finds.scan_ap_uplink'));
        $this->assertSame(4, config('game.finds.salvage_cap_per_sol'));
        $this->assertSame(2, config('game.finds.max_open_projects'));
    }

    public function test_colony_tiles_has_salvage_columns(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumns('colony_tiles', ['salvage_ap_spent', 'salvage_tick']));
    }
}
```

- [ ] **Step 2:** `bin/phpunit tests/Unit/FindsConfigTest.php` → FAIL (config null / Spalten fehlen).

- [ ] **Step 3: Implement.** In `config/game.php` direkt hinter dem `'colony' => [...]`-Block einfügen:

```php
    // T30 Pool v1 (Spec §11.3): fixed start pool of hidden finds, same size every run.
    'finds' => [
        'start_pool' => ['find_large' => 1, 'find_medium' => 1, 'find_small' => 3, 'find_false' => 2],
        'types' => [
            'find_small' => ['rg' => 4, 'ap' => 12],
            'find_medium' => ['rg' => 10, 'ap' => 16],
            'find_large' => ['rg' => 18, 'ap' => 22],
            'find_false' => ['rg' => 0, 'ap' => 0],
        ],
        'scan_ap' => 2,
        'scan_ap_uplink' => 1, // Uplink-Station Lv2+
        'salvage_cap_per_sol' => 4,
        'max_open_projects' => 2,
    ],
```

Migration (Muster der vorhandenen Migrationen in `database/migrations/` befolgen; `up()` fügt `$table->integer('salvage_ap_spent')->default(0)->after('is_deep_scanned'); $table->integer('salvage_tick')->nullable()->after('salvage_ap_spent');`, `down()` entfernt beide). Prüfen, ob die Baseline-Migration-Regel (CLAUDE.md: "eine Baseline-Migration") zusätzliche Migrationsdateien erlaubt; wenn `docs/dev-setup-mysql.md` das verbietet, stattdessen die zwei Spalten in `0001_01_01_000000_baseline.php` (Block `colony_tiles`, Zeile ~159) ergänzen und in der Übergabe melden.

- [ ] **Step 4:** Test erneut laufen lassen → PASS. `php artisan migrate --env=testing` ist nicht nötig; PHPUnit migriert selbst.
- [ ] **Step 5: Commit** `feat(T30): Config game.finds + Salvage-Spalten an colony_tiles`

---

### Task 2: FindPoolService (Generator)

**Files:**
- Create: `app/Services/FindPoolService.php`
- Modify: `app/Services/OnboardingService.php:247` (nach `randomizeOuterRingRows`)
- Test: `tests/Feature/Colony/FindPoolServiceTest.php`

**Interfaces:**
- Consumes: Zeilen im Format von `ColonyTileService::randomizeOuterRingRows()` (`q,r,ring,tile_type,...`), `config('game.finds.start_pool')`.
- Produces: `FindPoolService::SALT` (int const), `FindPoolService::assign(array $rows, int $rngSeed): array` – gibt dieselben Zeilen zurück, bei den gewählten Zeilen mit `event_type` ∈ `find_*`; alle anderen Felder unverändert, Zeilen ohne Signal behalten `event_type => null`.

- [ ] **Step 1: Failing tests**

```php
<?php

namespace Tests\Feature\Colony;

use App\Services\ColonyTileService;
use App\Services\FindPoolService;
use Tests\TestCase;

class FindPoolServiceTest extends TestCase
{
    private function rows(int $seed): array
    {
        return app(ColonyTileService::class)->randomizeOuterRingRows($seed);
    }

    public function test_assigns_the_fixed_mix_to_empty_ring_2_and_3_tiles_only(): void
    {
        foreach ([1, 2, 3, 4, 5, 6, 7, 8, 99, 12345] as $seed) {
            $rows = app(FindPoolService::class)->assign($this->rows($seed), $seed);
            $signals = array_values(array_filter($rows, fn ($r) => ($r['event_type'] ?? null) !== null));

            $this->assertCount(7, $signals, "seed {$seed}");
            $counts = array_count_values(array_column($signals, 'event_type'));
            ksort($counts);
            $this->assertSame(['find_false' => 2, 'find_large' => 1, 'find_medium' => 1, 'find_small' => 3], $counts, "seed {$seed}");
            foreach ($signals as $s) {
                $this->assertSame('terrain_empty', $s['tile_type']);
                $this->assertContains($s['ring'], [2, 3]);
            }
        }
    }

    public function test_does_not_change_any_other_field(): void
    {
        $base = $this->rows(7);
        $out = app(FindPoolService::class)->assign($base, 7);
        $this->assertCount(count($base), $out);
        foreach ($base as $i => $row) {
            $o = $out[$i];
            unset($o['event_type']);
            $this->assertSame($row, $o);
        }
    }

    public function test_is_deterministic_per_seed_and_varies_between_seeds(): void
    {
        $svc = app(FindPoolService::class);
        $this->assertSame($svc->assign($this->rows(3), 3), $svc->assign($this->rows(3), 3));
        $this->assertNotSame(
            array_column($svc->assign($this->rows(3), 3), 'event_type'),
            array_column($svc->assign($this->rows(4), 4), 'event_type'),
        );
    }

    public function test_large_find_prefers_ring_3_when_available(): void
    {
        foreach (range(1, 40) as $seed) {
            $rows = $this->rows($seed);
            $ring3Empty = count(array_filter($rows, fn ($r) => $r['ring'] === 3 && $r['tile_type'] === 'terrain_empty'));
            $out = app(FindPoolService::class)->assign($rows, $seed);
            $large = array_values(array_filter($out, fn ($r) => ($r['event_type'] ?? null) === 'find_large'))[0];
            if ($ring3Empty > 0) {
                $this->assertSame(3, $large['ring'], "seed {$seed}");
            }
        }
    }

    public function test_fewer_candidates_than_signals_does_not_throw(): void
    {
        $rows = array_map(fn ($r) => array_merge($r, ['tile_type' => 'hazard_x']), $this->rows(1));
        $rows[0]['tile_type'] = 'terrain_empty';
        $rows[0]['ring'] = 2;
        $out = app(FindPoolService::class)->assign($rows, 1);
        $this->assertCount(1, array_filter($out, fn ($r) => ($r['event_type'] ?? null) !== null));
    }

    public function test_does_not_shift_the_existing_map_stream(): void
    {
        $svc = app(ColonyTileService::class);
        $this->assertSame($svc->randomizeOuterRingRows(5), $svc->randomizeOuterRingRows(5));
        $before = $svc->randomizeOuterRingRows(5);
        app(FindPoolService::class)->assign($before, 5);
        $this->assertSame($before, $svc->randomizeOuterRingRows(5));
    }
}
```

- [ ] **Step 2:** `bin/phpunit tests/Feature/Colony/FindPoolServiceTest.php` → FAIL (Klasse fehlt).

- [ ] **Step 3: Implement**

```php
<?php

namespace App\Services;

use App\Support\SeededRandom;

/**
 * Places the fixed start pool of hidden finds (T30 Spec §11.3) on the outer-ring map.
 *
 * Own generator ($rngSeed + SALT) so the existing map stream (randomizeOuterRingRows) and
 * every paired playtest baseline stay identical — only event_type is added.
 */
class FindPoolService
{
    public const SALT = 0x46494E44;

    /** Largest first: bigger finds take the first (ring-3) candidates. */
    private const SIZE_ORDER = ['find_large', 'find_medium', 'find_small', 'find_false'];

    /**
     * @param  list<array<string,mixed>>  $rows  rows from ColonyTileService::randomizeOuterRingRows()
     * @return list<array<string,mixed>> same rows, chosen ones carry event_type find_*
     */
    public function assign(array $rows, int $rngSeed): array
    {
        $types = [];
        foreach (self::SIZE_ORDER as $type) {
            for ($i = 0, $n = (int) (config('game.finds.start_pool')[$type] ?? 0); $i < $n; $i++) {
                $types[] = $type;
            }
        }

        $candidates = [];
        foreach ($rows as $i => $row) {
            $rows[$i]['event_type'] = null;
            if ($row['tile_type'] === 'terrain_empty' && in_array($row['ring'], [2, 3], true)) {
                $candidates[] = $i;
            }
        }

        $rng = SeededRandom::generator($rngSeed + self::SALT);
        for ($i = count($candidates) - 1; $i > 0; $i--) {
            $j = $rng->getInt(0, $i);
            [$candidates[$i], $candidates[$j]] = [$candidates[$j], $candidates[$i]];
        }

        $chosen = array_slice($candidates, 0, count($types));
        // Ring 3 first (stable): usort is stable in PHP 8, so the seeded order is kept within a ring.
        usort($chosen, fn ($a, $b) => $rows[$b]['ring'] <=> $rows[$a]['ring']);
        foreach ($chosen as $k => $rowIndex) {
            $rows[$rowIndex]['event_type'] = $types[$k];
        }

        return $rows;
    }
}
```

In `OnboardingService::seedStartingTiles()` (Zeile 247) ändern auf:

```php
        $outer = $this->tileService->randomizeOuterRingRows($rngSeed);
        $tiles = array_merge($tiles, app(FindPoolService::class)->assign($outer, $rngSeed));
```

(Konstruktor-Injection statt `app()` bevorzugen, falls `OnboardingService` einen Konstruktor hat; `use App\Services\FindPoolService` nicht nötig im selben Namespace.) Ring 0/1-Zeilen haben kein `event_type`-Schlüssel; `DB::table('colony_tiles')->insert($rows)` verlangt gleiche Schlüssel je Zeile → bei den sechs Ring-1-Zeilen und der Ring-0-Zeile `'event_type' => null` ergänzen.

- [ ] **Step 4:** Tests grün. Zusätzlich `bin/phpunit --testsuite=laravel-feature --filter=Onboarding` – schlagen bestehende Tests wegen Signalen an? Diese anpassen, nur wenn sie Fund-Kacheln fälschlich erwarten (Begründung im Commit).
- [ ] **Step 5: Commit** `feat(T30): FindPoolService – 7 Signale pro Run, eigener Seed-Strom`

---

### Task 3: Tiefenscan aus Config + Fund-Info in der Kachel

**Files:**
- Modify: `app/Services/ColonyTileService.php` (`deepScanTile` ~79–112, `transformTile` ~303)
- Test: `tests/Feature/Colony/DeepScanFindTest.php`

**Interfaces:**
- Consumes: Task 1 Config.
- Produces: `transformTile()` liefert zusätzlich `find` = `null` oder `['type'=>string,'rg'=>int,'ap_total'=>int,'ap_spent'=>int,'ap_cap_per_sol'=>int,'false'=>bool]`, **nur** wenn `is_deep_scanned` und `event_type` mit `find_` beginnt. Vor dem Scan bleibt `event_type` null und `find` null (Transparenz-Regel: Menge erst nach Scan, vorab nur Kosten).

- [ ] **Step 1: Failing test** (Fixture-Kolonie wie in `tests/Feature/Colony/ColonyTileExploreCostTest.php` aufsetzen; dort Setup lesen und übernehmen). Fälle:
  - Tile `find_medium`, erkundet, nicht gescannt: `deepScanTile` ok, `tile.find` == `['type'=>'find_medium','rg'=>10,'ap_total'=>16,'ap_spent'=>0,'ap_cap_per_sol'=>4,'false'=>false]`, 2 AP gesperrt (`getAvailableActionPoints` sinkt um 2).
  - Mit Uplink-Station Lv2: 1 AP.
  - Vor dem Scan: `exploreTile`-Ergebnis hat `find === null` und `event_type === null`, `has_signal === true`.
  - `find_false` gescannt: `find.false === true`, `rg === 0`.
  - Scan eines Felds ohne `event_type`: `error = no_signal`, keine AP gesperrt (bestehendes Verhalten, als Regression).
- [ ] **Step 2:** FAIL (kein `find`-Key; Kosten hartcodiert).
- [ ] **Step 3: Implement.** In `deepScanTile`: `$scanApCost = ($uplinkLv >= 2) ? (int) config('game.finds.scan_ap_uplink') : (int) config('game.finds.scan_ap');`. In `transformTile` am Ende vor `return`:

```php
        $arr['find'] = null;
        if ($tile->is_deep_scanned && is_string($tile->event_type) && str_starts_with($tile->event_type, 'find_')) {
            $def = config('game.finds.types')[$tile->event_type] ?? ['rg' => 0, 'ap' => 0];
            $arr['find'] = [
                'type' => $tile->event_type,
                'rg' => $def['rg'],
                'ap_total' => $def['ap'],
                'ap_spent' => (int) $tile->salvage_ap_spent,
                'ap_cap_per_sol' => (int) config('game.finds.salvage_cap_per_sol'),
                'false' => $tile->event_type === 'find_false',
            ];
        }
```

`ColonyTile` Model: `salvage_ap_spent`, `salvage_tick` in `$fillable` ergänzen (Datei prüfen, wie `event_type` dort steht, Zeile 19).
- [ ] **Step 4:** grün; `bin/phpunit --filter='DeepScan|ColonyTile|Hangar'` grün (HangarService nutzt `signal_tile`/`event_type`, darf nicht brechen).
- [ ] **Step 5: Commit** `feat(T30): Tiefenscan-Kosten aus Config, Fund-Info nach Scan`

---

### Task 4: Bergungsprojekt (`salvageFind`)

**Files:**
- Modify: `app/Services/ColonyTileService.php` (neue Methode nach `deepScanTile`)
- Modify: `app/Http/Controllers/Colony/ColonyController.php` (neue Action `salvageTile`, neben `deepScanTile` ~250)
- Modify: `routes/web.php:85` (Route `colony.tile.salvage`, POST `/tile/salvage`, Gruppe wie `tile.deep-scan`)
- Modify: `lang/de/colony.php` (`error_no_find`, `error_find_false`, `error_salvage_cap`, `error_salvage_projects`, `error_salvage_ap`, `error_salvage_done_today`)
- Test: `tests/Feature/Colony/SalvageFindTest.php`

**Interfaces:**
- Consumes: Task 3 (`find`-Info, gescannte Kachel), Task 1 Config.
- Produces: `ColonyTileService::salvageFind(int $colonyId, int $q, int $r, int $ap): array` → `['ok'=>true,'tile'=>array,'completed'=>bool,'regolith'=>int]` oder `['ok'=>false,'error'=>string,'message'=>string]`. Fehlercodes: `tile_not_found`, `not_scanned`, `no_find`, `find_false`, `invalid_ap`, `salvage_cap` (ap > Deckel oder schon im selben Sol gearbeitet), `salvage_projects` (dritter offener Projektstart), `no_nav_ap`. Route `POST /colony/tile/salvage` mit `q,r,ap` (JSON wie `deep-scan`).

Regeln (exakt):
1. `ap` muss `1..cap(4)` sein, sonst `invalid_ap`; wird auf `min($ap, ap_total - ap_spent)` gekürzt (letzte Bergung nimmt nur den Rest).
2. Pro Kachel nur eine Einzahlung je Tick: `salvage_tick === currentTick` → `salvage_cap`.
3. Offenes Projekt = gescannte `find_*`-Kachel (ohne `find_false`) mit `salvage_ap_spent > 0`; hat die Kolonie bereits `max_open_projects` offene Projekte und diese Kachel ist keins davon (`salvage_ap_spent == 0`), dann `salvage_projects`.
4. AP prüfen (`getAvailableActionPoints`, Bypass `game.bypass.ap_checks` wie bei `deepScanTile`), dann `lockActionPoints`.
5. `salvage_ap_spent += ap`, `salvage_tick = tick`. Erreicht `salvage_ap_spent >= ap_total`: Regolith (`resource_id 3`) per `DB::table('colony_resources')->where('colony_id',$id)->where('resource_id',3)->increment('amount',$rg)`, dann `event_type = null`, `is_deep_scanned = 0`, `salvage_ap_spent = 0`, `salvage_tick = null`; Rückgabe `completed=true, regolith=$rg`. Alles in `DB::transaction`.
6. Bebaute Kacheln: Wenn bereits ein Gebäude auf der Kachel steht (`colony_buildings.tile_x/tile_y` passt, vgl. `activeHarvesterRegolithTiles`), kann der Fund nur noch geborgen werden, wenn das Feld frei ist → Fehler `no_find` ist falsch; stattdessen Fehlercode `tile_occupied`. (Fund unter Gebäude ist laut Spec "Detail der Umsetzung": bauen auf einem *gescannten* Signal-Feld ist erlaubt, der Fund bleibt dann unbergbar. Siehe Step 3b unten.)

- [ ] **Step 1: Failing tests** (`SalvageFindTest`), je ein Test pro Review-Focus-Zeile:
  - 4 AP auf `find_small`: `ap_spent=4`, `completed=false`, 4 AP gesperrt, Regolith unverändert.
  - Drei Sole à 4 AP (Tick per `Artisan::call('game:tick', ['--tick'=>N])` oder TickService wie in `tests/Feature/GameTick/*`) → `completed=true`, `regolith` +4 (genau einmal), Kachel `event_type=null`, `salvage_ap_spent=0`.
  - Letzter Beitrag kürzt: `find_small` mit 10 gezahlten AP, Aufruf `ap=4` sperrt nur 2.
  - Zweiter Aufruf im selben Tick → `salvage_cap`, keine zusätzlichen AP gesperrt.
  - `ap=5` und `ap=0` → `invalid_ap`.
  - Dritte Kachel neu starten bei zwei offenen → `salvage_projects`; fertigstellen eines offenen Projekts ist weiter möglich.
  - `find_false` → `find_false`; nicht gescannte Fund-Kachel → `not_scanned`; Kachel ohne `event_type` → `no_find`.
  - Zu wenig freie AP → `no_nav_ap`, `salvage_ap_spent` unverändert.
- [ ] **Step 2:** FAIL (Methode/Route fehlen).
- [ ] **Step 3: Implement** `salvageFind` nach den Regeln oben (Muster `deepScanTile`: Kachel laden, Validierungsreihenfolge `tile_not_found → not_scanned → no_find/find_false → invalid_ap → salvage_cap → salvage_projects → no_nav_ap`). Controller-Action analog `deepScanTile` mit `$request->validate(['q'=>'required|integer','r'=>'required|integer','ap'=>'required|integer'])`; Antwort `[...$result, ...$this->currentAp($colony->id)]` (Resourcebar-Live-Sync! Regolith ändert sich bei Abschluss: im Antwort-Array die aktuelle Regolith-Menge mitgeben, wie andere Aktionen, die Ressourcen ändern; `currentAp()` und die Nachbaraktionen im Controller dazu lesen). Event-Log `colony.tile_salvaged` wie bei `colony.tile_deep_scanned`.
- [ ] **Step 3b:** Bauen auf Signal-Kachel: Grep `placeBuilding`/`buildAt` im `ColonyController`/`BuildingService`, Kachel mit `event_type` prüfen. Falls nicht gescannt: Bau erlaubt, Fund geht verloren; das ist die Spec-Aussage "erst nach Scan". Setze: Bau auf Kachel mit unbergbarem Fund (event_type `find_*` ohne Bergung abgeschlossen) wird **abgelehnt** mit `error_tile_has_find` ("Erst bergen oder verwerfen"). Test dazu in `SalvageFindTest`. Falls der Implementer das für zu invasiv hält (viele Platzierungspfade), stattdessen: Fund wird beim Bauen still verworfen (`event_type=null`) und im Antworttext ausgewiesen; Entscheidung im Report begründen.
- [ ] **Step 4:** grün, plus `bin/phpunit --testsuite=laravel-feature --filter='Colony|Tile'`.
- [ ] **Step 5: Commit** `feat(T30): Bergungsprojekt (salvageFind) mit Deckel und Projektlimit`

---

### Task 5: UI – Fundkarte im Tile-Panel

**Files:**
- Modify: `resources/views/colony/partials/tile-terrain.blade.php` (Fundkarte), `resources/views/colony/hexview.blade.php` (Signal-Marker, `eventTypeName`, Salvage-Aktion, i18n-Block ~65), das zugehörige JS (Aktion analog `deepScan`; im Blade nach der Funktion `deepScan` suchen)
- Modify: `lang/de/colony.php`
- Test: `tests/Feature/Colony/ColonyViewTest.php` (Markup-Test) + Playwright-Check

**Interfaces:**
- Consumes: `selectedTile.find` (Task 3), `POST /colony/tile/salvage` (Task 4), bestehender `deep-scan`-Button (`hexview.blade.php:229`).
- Produces: sichtbare Anzeige nach Spec §11.3 "Was der Spieler vorab sieht".

Anforderungen:
- Vor Scan: Chip "Signal" (besteht), Button "Tiefenscan" mit AP-Chip (Kosten `config('game.finds.scan_ap')` bzw. Uplink-Wert, serverseitig an die View gegeben, nicht hartcodiert; AP-Kosten-Chip-Konvention, siehe `project_ap_cost_chip_convention`).
- Nach Scan, Fund echt: Fundkarte mit Zeilen "Fund: N Rg", "Bergungsaufwand: X/Y AP", "Deckel: 4 AP pro Sol", Button "Bergen" mit Eingabe 1–4 AP (Standard 4, auf Rest gekürzt) und AP-Chip. Bei Fehlalarm: Hinweis "Fehlalarm – kein Ertrag", kein Button.
- Nach Abschluss: Meldung "N Rg geborgen", Resourcebar sofort aktualisiert (`feedback_resourcebar_live_sync`).
- Kartenkopf: "Funde: x Signale, y gescannt, z Rg geborgen/offen" – nur Zähler aus bekannten Kacheln (x = aufgedeckte Signale), die Gesamtzahl 7 steht in der Hilfe, nicht im Kopf.
- Texte via `__()`, Names für `find_small|medium|large|false` in `eventTypeName`.
- Blade zweimal mit Prettier formatieren (`feedback_blade_prettier_double_pass`).

- [ ] **Step 1:** Markup-Test in `ColonyViewTest`: Tile mit gescanntem `find_small` gerendert → Seite enthält die i18n-Schlüsseltexte "Bergungsaufwand" und Button-Label; kein hartcodierter Text.
- [ ] **Step 2:** FAIL. **Step 3:** Implementieren (ui-specialist). **Step 4:** Test grün + Playwright-Check gegen eine Kopie der Dev-DB (`reference_artisan_serve_db_env_not_passed`, `feedback_agents_never_migrate_fresh_on_dev_db`: nie `migrate:fresh` gegen `nouron`): Kachel aufdecken → scannen → 4 AP bergen → Zähler/Resourcebar prüfen; Screenshot im Report.
- [ ] **Step 5: Commit** `feat(T30): Fundkarte im Tile-Panel (Scan, Bergung, Anzeige)`

---

### Task 6: PlaytestBot – Regel `invest_find`, Quelle `find`

**Files:**
- Modify: `tests/Feature/Playtest/BotStrategy.php` (neue Regel + Kandidatenfunktion, `deepScanCandidate` ~2001 auf Config-Kosten umstellen)
- Modify: `tests/Feature/Playtest/RunReport.php:112` (`regolithSources`: Schlüssel `find`)
- Modify: `app/Support/OpeningComparison.php` (K11: Pool-Rg bis Sol 10 / Sol 25; nur Auswertung, kein Ziel-Status "verfehlt")
- Test: `tests/Feature/Playtest/BotStrategyInvestFindTest.php`, `tests/Feature/Playtest/RunReportFindSourceTest.php`, Ergänzung in `tests/Unit/OpeningComparison*Test.php` (bestehende Datei zum Muster lesen)

**Interfaces:**
- Consumes: `POST /colony/tile/salvage`, `colony_tiles.event_type/is_deep_scanned/salvage_ap_spent`.
- Produces: Bot-Regel `invest_find` (Name im `act()`-Log), `regolith_sources.find` je Sol im Report, K11 in `game:playtest-compare` (Zeile "K11 Pool-Rg Sol 10 / Sol 25", Median je Eröffnung).

Regeln:
- `investFindCandidate(BotSession $b): ?object` → gescannte Kachel mit `event_type IN (find_small, find_medium, find_large)`, `is_deep_scanned=1`, sortiert nach Rendite `rg/ap_total` absteigend (offene Projekte vor neuen bei Gleichstand), nur wenn weniger als `max_open_projects` offen oder Kachel schon angefangen; Einzahlung `min(cap, Rest, availableAp($b))`, 0 → `null`.
- Position in der Regelliste: **niedrige Priorität**, direkt vor `sol_next`/Sol-Ende-Regeln und nach allen Bau-/Forschungs-/Erkundungs-/Einstellungsregeln, damit der Bot nur Rest-AP in Bergung steckt (Spec: Pool soll die brachliegenden AP binden, nicht Gebäude verdrängen). Die vorhandene Priorisierung (`deep_scan_signal_tile` etc.) vor dem Edit lesen.
- `regolithSources`: bei Regel `invest_find` mit positivem Regolith-Delta → `$sources['find'] += $delta`; Schlüssel `find => 0` in der Ausgangsliste ergänzen. Aufpassen auf `OpeningComparison` (K2 `mission`/`trade` unverändert) und `regolithPathAttribution`.
- [ ] **Step 1: Failing tests** (Bot wählt die höchste Rendite; ignoriert `find_false`/nicht gescannte; Deckel und Rest eingehalten; Report zählt Delta als `find`; K11 aus Beispiel-Snapshots berechnet). **Step 2:** FAIL. **Step 3:** Implementieren. **Step 4:** grün; `bin/phpunit --filter='BotStrategy|RunReport|OpeningComparison'` grün.
- [ ] **Step 5: Commit** `feat(T30): Bot-Regel invest_find, Report-Quelle find, K11`

---

### Task 7: Doku und Verifikation

**Files:**
- Modify: `docs/game-reference.md` (Abschnitt Erkundung/Karte: Fundtabelle, Scan, Bergung), `CHANGELOG.md` (Block `## 2026-10-09` ergänzen, kurz), `docs/superpowers/specs/2026-10-05-t30-gleichwertige-eroeffnungen.md` (§11.9 Schritt 2 Status "umgesetzt"; Zahlen-Drift 16/23/30→18/26/34 und "23 Rg/Sol" in §11 nachziehen), `docs/GDD.md` (zahlenfreie Prosa in §4b/§5 zur Erkundung: Signale, Scan, Bergung)
- Modify: `ROADMAP.md` (T30 Status)

- [ ] **Step 1:** Doku schreiben (kurz, deutsch, Zahlen nur in game-reference).
- [ ] **Step 2:** Volle Suite: `bin/phpunit` (oder `bin/paratest`) → alles grün; Pint (`bin/pint --test`) sauber.
- [ ] **Step 3:** Messung vorbereiten, NICHT starten ohne Owner-Wahl der Laufzahl (8/16/24, ~25 min bei 24): `php artisan game:playtest --profiles=default --openings=labor,hangar,cantina --seeds=1,2,3,4,5,6,7,8 --until-sol=25 --concurrency=12`, danach `game:playtest-compare … --since="<UTC-Startzeit>"`. Erwartung (Spec §11.3): K4 Hangar/Cantina 89/88 → ≤ 45 (Ziel), K5 ≤ 2, K11 ≈ 20 Rg bis Sol 10 / ≈ 40–50 bis Sol 25, K6-Abstand ≤ 25, Siegquote nicht > 65 % (nur mit vollen Läufen messbar).
- [ ] **Step 4: Commit** `docs(T30): Pool v1 – game-reference, GDD, CHANGELOG, Spec-Status`

---

## Self-Review

- **Spec-Abdeckung §11.3:** Pool-Aufbau (Task 1–2), Scan aus Config (3), Bergungsprojekt/Deckel/Projektlimit (4), Fundkarte/Transparenz (5), Bot-Regeln + K11 (6), Doku/Messung (7). Nachschub bewusst ausgeklammert (F4). `salvage_ap_spent` ist die Annahme aus §11.3 Datenhaltung.
- **Bekannte offene Punkte für Implementer-Entscheidung:** Bau auf Signal-Kachel (Task 4, Step 3b), Migration vs. Baseline (Task 1), Ring-3-Kandidaten-Knappheit (Task 2 Test deckt Fallback).
- **Typkonsistenz:** `find`-Array (Task 3) wird von Task 4 (Antwort), 5 (UI) gleichnamig genutzt; `FindPoolService::assign` Signatur in Task 2 und Onboarding-Hook identisch; Config-Schlüssel `game.finds.*` überall gleich.
