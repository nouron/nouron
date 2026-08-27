# Gebäude-Ausbaustufen-Grundgerüst Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Korrigiere die `max_level`-Deckel für 5 Gebäude auf das in der Spec festgelegte Maß und zeige den benannten Ausbaustufen-Beinamen (falls vorhanden) im Kolonie-Hex-View an, statt nur der rohen Levelzahl.

**Architecture:** Config-getriebene Beinamen (`config/buildings.php` → `tiers`-Array pro Gebäude, Werte sind Levelnummern mit Beiname) + Sprachdatei-Keys (`lang/de|en/techtree.php`, Muster `tier_{buildingKey}_{level}`). Ein neuer, kleiner Controller-Helper (`resolveTierLabel()`) löst Building-ID + Level in den passenden Beinamen auf (oder `null`, wenn keiner konfiguriert ist) und wird an den beiden bestehenden Stellen aufgerufen, die Building-Zeilen fürs Frontend serialisieren (`ColonyController::fetchBuildingRow()` für Einzel-Aktionen, `ColonyController::hexview()` für den initialen Seitenaufbau). Keine DB-Schema-Änderung — `colony_buildings.level` bleibt wie es ist, nur `buildings.max_level` wird niedriger gedeckelt.

**Tech Stack:** PHP/Laravel, Blade + Alpine.js (kein Build-Step, `@json()` embeddet Server-Daten direkt in die Seite), PHPUnit (`bin/phpunit`), Larastan (`bin/phpstan`).

**Spec:** `docs/superpowers/specs/2026-08-23-building-tier-system-design.md`

## Global Constraints

- Max. 3 Ausbaustufen pro Gebäude, Ausnahmen: Command Center (bleibt „Level", unverändert in diesem Plan) und Analytik-Labor (bleibt bei 5 — Lv1-3 Kenntnis-Gates unverändert, Lv4/5 sind ein separater Folge-Plan, hier nur der `max_level`-Wert).
- Beiname nur bei den in der Spec-Tabelle „Klassifizierung pro Gebäude" markierten Stufen — sonst bleibt es bei nackter Levelzahl (kein `tiers`-Eintrag für diese Level).
- Keine konkreten Zahlen-Rebalances in diesem Plan (ADR 0004) — nur Struktur (`max_level`, `tiers`) und Anzeige. `production_curve`/`ap_for_levelup`-Werte bleiben unverändert (Balance-Kalibrierung ist ein separater Folge-Task nach Playtest, siehe Spec Punkt 3).
- TDD-Pflicht für Task 4 (echter Codepfad). Tasks 1-3 sind reine Config-/Sprachdatei-/Datenwert-Änderungen ohne Codepfad — TDD-Ausnahme laut CLAUDE.md greift, kein Test-zuerst nötig, aber bestehende Tests müssen danach weiter grün sein.

---

### Task 1: Sprachdatei-Keys für Ausbaustufen-Beinamen

**Files:**
- Modify: `lang/de/techtree.php:17` (nach `building_tradingPost`)
- Modify: `lang/en/techtree.php:17` (nach `building_tradingPost`)

**Interfaces:**
- Produces: 10 neue Lang-Keys im Muster `tier_{buildingKey}_{level}`, gelesen von `ColonyController::resolveTierLabel()` in Task 4.

- [x] **Step 1: Keys in `lang/de/techtree.php` einfügen**

Nach der Zeile `'building_tradingPost' => 'Handelsposten',` einfügen:

```php
    // ── Ausbaustufen-Beinamen (tier_* Keys, Design-Spec 2026-08-23) ──────────
    'tier_hangar_1' => 'Startmodul',
    'tier_hangar_2' => 'Ladebucht',
    'tier_hangar_3' => 'Anlegestelle',
    'tier_securityHub_3' => 'Recyclingmodul',
    'tier_infirmary_3' => 'Vollausstattung',
    'tier_bioFacility_3' => 'Notvorrat',
    'tier_uplinkStation_1' => 'Erster Draht',
    'tier_tradingPost_1' => 'Bekannter Gast',
    'tier_tradingPost_2' => 'Fester Kunde',
    'tier_tradingPost_3' => 'Persönlicher Kontakt',
```

- [x] **Step 2: Keys in `lang/en/techtree.php` einfügen**

Nach der Zeile `'building_tradingPost' => 'Trading Post',` einfügen:

```php
    // ── Tier nicknames (tier_* keys, design spec 2026-08-23) ─────────────────
    'tier_hangar_1' => 'Launch Module',
    'tier_hangar_2' => 'Cargo Bay',
    'tier_hangar_3' => 'Docking Berth',
    'tier_securityHub_3' => 'Recycling Module',
    'tier_infirmary_3' => 'Fully Equipped',
    'tier_bioFacility_3' => 'Emergency Reserve',
    'tier_uplinkStation_1' => 'Open Line',
    'tier_tradingPost_1' => 'Familiar Face',
    'tier_tradingPost_2' => 'Regular Customer',
    'tier_tradingPost_3' => 'Personal Contact',
```

- [x] **Step 3: Verifizieren, dass beide Dateien noch valides PHP sind**

Run: `php -l lang/de/techtree.php && php -l lang/en/techtree.php`
Expected: `No syntax errors detected` für beide.

- [x] **Step 4: Commit**

```bash
git add lang/de/techtree.php lang/en/techtree.php
git commit -m "feat: Sprachdatei-Keys für Gebäude-Ausbaustufen-Beinamen"
```

---

### Task 2: Config — `max_level`-Korrekturen + `tiers`-Arrays

**Files:**
- Modify: `config/buildings.php` (housingComplex, bioFacility, infirmary, bar, sciencelab, hangar, securityHub, uplinkStation, tradingPost)

**Interfaces:**
- Produces: `config('buildings.{key}.tiers')` — Array von Levelnummern mit Beiname, gelesen von `ColonyController::resolveTierLabel()` in Task 4.
- Produces: korrigierte `config('buildings.{key}.max_level')`-Werte.

- [x] **Step 1: `housingComplex.max_level` von 6 auf 3 senken**

In `config/buildings.php`, im `housingComplex`-Block (aktuell `'max_level' => 6,`):

```php
        // Gesenkt von 6 auf 3 (2026-08-25, game-designer-Review): max_level und
        // max_instances waren identisch (6) aus historischem Zufall, keine
        // Design-Absicht (siehe alter Kommentar unten) — Doppelachse machte die
        // Bauentscheidung uninteressant (beide Wege füttern denselben Supply-Pool
        // ohne qualitativen Unterschied). Instanzen bleiben die primäre,
        // tile-limitierte Wachstumsachse; Level ist jetzt sekundäre Feinabstimmung.
        // supply_cap-Neukalibrierung ist ein separater Balance-Task nach Playtest
        // (docs/superpowers/specs/2026-08-23-building-tier-system-design.md, Punkt 9).
        'max_level' => 3,
```

(Den bestehenden alten Kommentar-Block Zeile 70-80, der die Historie der Doppelachse erklärt, NICHT löschen — bleibt als Kontext stehen, der neue Kommentar kommt direkt vor der Zeile `'max_level' => 3,`.)

- [x] **Step 2: `bioFacility.max_level` von `null` auf 3 setzen**

Im `bioFacility`-Block:

```php
        // Gedeckelt auf 3 (2026-08-25, Ausbaustufen-Umstellung) — production_curve[41]
        // (config/game.php) hat weiterhin Einträge für Lv4-8; die werden ab jetzt nie
        // erreicht und bleiben als inerte historische Daten stehen (gleiches Muster
        // wie production_curve[27] beim Harvester, siehe dortiger Kommentar).
        'max_level' => 3,
```

- [x] **Step 3: `infirmary.max_level` von `null` auf 3 setzen**

Im `infirmary`-Block:

```php
        // Gedeckelt auf 3 (2026-08-25) — Stufe 3 trifft den plague_risk_reduction_cap
        // exakt (siehe unten), keine Überinvestition über den Wirkungsdeckel hinaus
        // mehr möglich.
        'max_level' => 3,
```

- [x] **Step 4: `bar.max_level` von `null` auf 3 setzen**

Im `bar`-Block:

```php
        // Gedeckelt auf 3 (2026-08-25, Ausbaustufen-Umstellung) — reine
        // Mengensteigerung (Angebotszahl/-dauer), kein Fähigkeits-Sprung, daher
        // keine Beinamen.
        'max_level' => 3,
```

- [x] **Step 5: `sciencelab.max_level` von `null` auf 5 setzen**

Im `sciencelab`-Block:

```php
        // Gedeckelt auf 5 (2026-08-25) — Ausnahme von der max.-3-Regel: Lv1-3
        // bleiben Kenntnis-Freischalt-Gates (unverändert, siehe researches-
        // Migrationen), Lv4/5 bekommen einen Domänen-Effizienzbonus "Wissen"
        // (separater Folge-Plan, noch nicht implementiert — Level-Deckel wird
        // hier vorab gesetzt, damit er nicht vergessen wird).
        'max_level' => 5,
```

- [x] **Step 6: `tiers`-Array für `hangar` ergänzen**

Im `hangar`-Block, direkt nach `'max_level' => 3,` (bereits vorhanden, nicht ändern):

```php
        // Ausbaustufen-Beinamen (2026-08-25) — jede Stufe schaltet eine neue
        // Schiffsklasse frei (echter Fähigkeits-Sprung), siehe lang/de/techtree.php
        // tier_hangar_1/2/3.
        'tiers' => [1, 2, 3],
```

- [x] **Step 7: `tiers`-Array für `securityHub` ergänzen**

Im `securityHub`-Block, nach `'max_level' => 3,`:

```php
        // Ausbaustufen-Beiname nur bei Stufe 3 (Recycling-Effekt, aktuell nur
        // konfiguriert — siehe securityHub Folge-Plan zum Verdrahten von
        // recycle_pct) — Stufen 1/2 sind reine Trust-Bonus-Mengensteigerung.
        'tiers' => [3],
```

- [x] **Step 8: `tiers`-Array für `infirmary` ergänzen**

Im `infirmary`-Block, nach dem neuen `'max_level' => 3,` aus Step 3:

```php
        'tiers' => [3],
```

- [x] **Step 9: `tiers`-Array für `bioFacility` ergänzen**

Im `bioFacility`-Block, nach dem neuen `'max_level' => 3,` aus Step 2:

```php
        'tiers' => [3],
```

- [x] **Step 10: `tiers`-Array für `uplinkStation` ergänzen**

Im `uplinkStation`-Block, nach `'max_level' => 3,` (bereits vorhanden):

```php
        // Nur Stufe 1 hat einen Beiname (schaltet Nexus-Bestellungen überhaupt
        // erst frei — echter Fähigkeits-Sprung). Stufe 2 ist Mengensteigerung
        // (Scankosten -1 AP). Stufe 3 ist zurückgestellt (Design-Spec Punkt "Neue
        // Mechaniken" — braucht einen eigenen Meta-Progressions-Design-Sprint).
        'tiers' => [1],
```

- [x] **Step 11: `tiers`-Array für `tradingPost` ergänzen**

Im `tradingPost`-Block, nach `'max_level' => 3,` (bereits vorhanden):

```php
        // Alle 3 Stufen benannt — jede schaltet einen neuen Rabatt-Kanal frei
        // (Cantina → Reisender Händler → Nexus/Corporate Contact), echter
        // Fähigkeits-Sprung pro Stufe (Design-Spec, Abschnitt "Handelsposten").
        'tiers' => [1, 2, 3],
```

- [x] **Step 12: Config-Syntax prüfen**

Run: `php -l config/buildings.php`
Expected: `No syntax errors detected`

- [x] **Step 13: Testdaten-Kompatibilität prüfen**

Run: `sqlite3 data/db/nouron.db "select cb.building_id, cb.level from colony_buildings cb join buildings b on b.id=cb.building_id where b.name in ('building_housingComplex','building_bioFacility','building_infirmary','building_bar','building_sciencelab');"`

Erwartung: Kein Wert überschreitet den jeweils neuen `max_level` (3 bzw. 5 für sciencelab). Falls doch (z.B. eine manuell hochgelevelte Dev-Kolonie): kein Blocker für diesen Task (kein DB-Constraint erzwingt das, nur `investBuilding()` verweigert *weiteres* Investieren) — nur zur Kenntnis nehmen, keine Migration nötig.

- [x] **Step 14: Commit**

```bash
git add config/buildings.php
git commit -m "feat: max_level-Deckel korrigieren + tiers-Arrays für Ausbaustufen-Beinamen"
```

---

### Task 3: ResetPlayer-Szenario an neuen Agrardom-Deckel anpassen

**Files:**
- Modify: `app/Console/Commands/ResetPlayer.php:616`

**Interfaces:**
- Keine (reine Datenwert-Korrektur in einer bestehenden Methode).

- [x] **Step 1: Bestehenden Test als Baseline laufen lassen**

Run: `bin/phpunit --filter test_near_deadline_scenario_sets_tick_near_limit_and_completes_one_objective`
Expected: PASS (Baseline vor der Änderung — dieser Test prüft nicht den exakten Agrardom-Level, muss also unverändert grün bleiben)

- [x] **Step 2: Zeile 616 von Lv4 auf Lv3 ändern**

In `app/Console/Commands/ResetPlayer.php`, `scenarioNearDeadline()`:

```php
        DB::table('colony_buildings')
            ->where('colony_id', $cid)->where('building_id', 41)
            ->update(['level' => 3, 'status_points' => 20]);     // Agrardom Lv3 (Deckel seit 2026-08-25, war Lv4)
```

- [x] **Step 3: Test erneut laufen lassen**

Run: `bin/phpunit --filter test_near_deadline_scenario_sets_tick_near_limit_and_completes_one_objective`
Expected: PASS (unverändert — der Test prüft `current_tick`, Objective-Anzahl, Advisor-Anzahl, Harvester-Tile, keinen Agrardom-Wert)

- [x] **Step 4: Kompletten ResetPlayer-Testfile laufen lassen**

Run: `bin/phpunit tests/Feature/Console/ResetPlayerTest.php`
Expected: alle Tests PASS

- [x] **Step 5: Commit**

```bash
git add app/Console/Commands/ResetPlayer.php
git commit -m "fix: ResetPlayer near-deadline-Szenario Agrardom-Level an neuen max_level=3 anpassen"
```

---

### Task 4: `resolveTierLabel()`-Helper + Anzeige im Hex-View

**Files:**
- Modify: `app/Http/Controllers/Colony/ColonyController.php` (neue private Methode + 2 Aufrufstellen: `fetchBuildingRow()`, `hexview()`)
- Modify: `resources/views/colony/hexview.blade.php:171-181` (Beiname anzeigen)
- Test: `tests/Feature/Colony/BuildingRepairTest.php` (neue Testmethoden)
- Test: `tests/Feature/Colony/ColonyViewTest.php` (neue Testmethode)

**Interfaces:**
- Consumes: `config('buildings.{key}.tiers')` (Task 2), `lang/de|en/techtree.php` `tier_*`-Keys (Task 1).
- Produces: `resolveTierLabel(int $buildingId, int $level): ?string` — private Methode auf `ColonyController`. Fügt jeder von `fetchBuildingRow()`/`hexview()` gelieferten Building-Zeile ein Feld `tier_label` (string oder `null`) hinzu, das im Frontend als `selectedBuilding.tier_label`/`building.tier_label` ankommt.

- [x] **Step 1: Fehlschlagenden Test für `fetchBuildingRow()`-Ausgabe schreiben (Repair-Endpoint)**

In `tests/Feature/Colony/BuildingRepairTest.php`, neue Testmethoden am Ende der Klasse (vor der schließenden `}`) einfügen:

```php
    // ── tier_label (Ausbaustufen-Beinamen, Design-Spec 2026-08-23) ────────────

    public function test_repair_response_includes_tier_label_when_configured(): void
    {
        // Krankenstation (id=46) steht in den Testdaten auf Level 3, status_points=10
        // (max_status_points=20 → reparierbar). Level 3 ist laut config/buildings.php
        // 'infirmary'.'tiers' benannt ("Vollausstattung").
        $response = $this->repair(46);

        $response->assertOk()->assertJsonPath('ok', true);
        $response->assertJsonPath('building.tier_label', 'Vollausstattung');
    }

    public function test_repair_response_has_null_tier_label_when_level_has_no_name(): void
    {
        // Command Center (id=25) hat kein 'tiers'-Array in config/buildings.php —
        // jedes Level muss tier_label=null liefern, unabhängig vom aktuellen Level.
        $this->setCcState(['status_points' => 16]);

        $response = $this->repair(self::CC_ID);

        $response->assertOk()->assertJsonPath('ok', true);
        $response->assertJsonPath('building.tier_label', null);
    }
```

- [x] **Step 2: Tests laufen lassen, Fehlschlag bestätigen**

Run: `bin/phpunit --filter test_repair_response_includes_tier_label_when_configured`
Expected: FAIL — `building.tier_label` existiert noch nicht im JSON-Response (Assertion-Fehler "Unable to find data" oder `null` != `'Vollausstattung'`, je nach PHPUnit-Version)

- [x] **Step 3: `resolveTierLabel()`-Helper implementieren**

In `app/Http/Controllers/Colony/ColonyController.php`, direkt vor der bestehenden `private function fetchBuildingRow(...)`-Methode (aktuell ca. Zeile 1011) einfügen:

```php
    /**
     * Resolve the named tier beiname for a building at a given level, or null
     * if that level has no name (design-spec.md: "Beiname nur bei echtem
     * Fähigkeits-Sprung"). Looks the building's config key up by id (same
     * pattern as OnboardingHintService::canAffordBuildingPlacement()).
     */
    private function resolveTierLabel(int $buildingId, int $level): ?string
    {
        $key = collect(config('buildings'))->search(fn ($cfg) => $cfg['id'] === $buildingId);
        if ($key === false) {
            return null;
        }

        $tiers = config("buildings.{$key}.tiers", []);
        if (! in_array($level, $tiers, true)) {
            return null;
        }

        return __("techtree.tier_{$key}_{$level}");
    }
```

- [x] **Step 4: Helper in `fetchBuildingRow()` verdrahten**

In `fetchBuildingRow()` (aktuell ca. Zeile 1034-1037), nach der Zeile `$row->ap_for_levelup = $this->projectBonusService->effectiveApForLevelup(...)` ergänzen:

```php
        $row->ap_for_levelup = $this->projectBonusService->effectiveApForLevelup($colonyId, (int) $row->ap_for_levelup);
        $row->tier_label = $this->resolveTierLabel((int) $row->building_id, (int) $row->level);

        return $row;
```

- [x] **Step 5: Tests laufen lassen, Erfolg bestätigen**

Run: `bin/phpunit --filter BuildingRepairTest`
Expected: alle Tests inkl. der 2 neuen PASS

- [x] **Step 6: Helper auch in `hexview()` verdrahten (initialer Seitenaufbau)**

In `hexview()`, im `->map(function ($b) use ($globalTick, $colony) { ... })`-Block (aktuell ca. Zeile 138-145), nach der Zeile `$b->ap_for_levelup = $this->projectBonusService->effectiveApForLevelup(...)` ergänzen:

```php
                $b->ap_for_levelup = $this->projectBonusService->effectiveApForLevelup($colony->id, (int) $b->ap_for_levelup);
                $b->tier_label = $this->resolveTierLabel((int) $b->building_id, (int) $b->level);

                return $b;
```

- [x] **Step 7: Fehlschlagenden Test für den initialen Seitenaufbau schreiben**

In `tests/Feature/Colony/ColonyViewTest.php`, neue Testmethode am Ende der Klasse einfügen:

```php
    public function test_hexview_buildings_include_tier_label(): void
    {
        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->get(route('colony.view'));

        $response->assertOk();

        $buildings = $response->viewData('buildings');
        $infirmary = $buildings->firstWhere('building_id', 46);

        $this->assertNotNull($infirmary, 'Krankenstation (id=46) muss in den Testdaten vorhanden sein');
        $this->assertSame(3, (int) $infirmary->level, 'Testdaten-Annahme: Krankenstation steht auf Level 3');
        $this->assertSame('Vollausstattung', $infirmary->tier_label);
    }
```

- [x] **Step 8: Test laufen lassen, Fehlschlag dann Erfolg bestätigen**

Run: `bin/phpunit --filter test_hexview_buildings_include_tier_label`
Expected vor Step 6 (falls Reihenfolge vertauscht ausgeführt): FAIL. Nach Step 6: PASS.

- [x] **Step 9: Beiname im Tile-Panel anzeigen (Blade/Alpine)**

In `resources/views/colony/hexview.blade.php`, den Block um Zeile 171-181 ersetzen:

Aktuell:
```blade
                        <div class="tile-panel-title__row" x-data="{ hoverLevel: false }">
                            <span x-text="selectedBuilding.label"></span>
                            <span
                                @mouseenter="hoverLevel = true" @mouseleave="hoverLevel = false">
                                <template x-if="selectedBuilding.level > 0">
                                    <span x-text="' | ' + selectedBuilding.level"></span>
                                </template>
                                <div class="res-popup" x-show="hoverLevel && selectedBuilding.level > 0" x-cloak
                                    x-text="selectedBuilding.max_level
                                        ? `Level ${selectedBuilding.level} / ${selectedBuilding.max_level}`
                                        : `Level ${selectedBuilding.level}`">
                                </div>
                            </span>
                        </div>
```

Neu:
```blade
                        <div class="tile-panel-title__row" x-data="{ hoverLevel: false }">
                            <span x-text="selectedBuilding.label"></span>
                            <span
                                @mouseenter="hoverLevel = true" @mouseleave="hoverLevel = false">
                                <template x-if="selectedBuilding.level > 0">
                                    <span x-text="' | ' + selectedBuilding.level + (selectedBuilding.tier_label ? ' – ' + selectedBuilding.tier_label : '')"></span>
                                </template>
                                <div class="res-popup" x-show="hoverLevel && selectedBuilding.level > 0" x-cloak
                                    x-text="selectedBuilding.tier_label
                                        ? `${selectedBuilding.tier_label} — Level ${selectedBuilding.level}` + (selectedBuilding.max_level ? ` / ${selectedBuilding.max_level}` : '')
                                        : (selectedBuilding.max_level
                                            ? `Level ${selectedBuilding.level} / ${selectedBuilding.max_level}`
                                            : `Level ${selectedBuilding.level}`)">
                                </div>
                            </span>
                        </div>
```

- [x] **Step 10: Blade-Syntax prüfen**

Run: `php -l resources/views/colony/hexview.blade.php` (prüft nur eingebettetes PHP, keine Blade-Direktiven — reicht als Rauchtest gegen kaputte `@`-Syntax)
Expected: `No syntax errors detected`

- [x] **Step 11: Volle Test-Suite + Larastan laufen lassen**

Run: `bin/phpunit`
Expected: alle Tests PASS (keine Regression in anderen Colony-/Techtree-Tests)

Run: `bin/phpstan analyse --no-progress`
Expected: `[OK] No errors`

- [x] **Step 12: Manuelle Browser-Verifikation (CLAUDE.md-Pflicht für UI-Änderungen)**

Dev-Server starten (siehe `run`-Skill oder projektüblicher Start-Befehl), als Bart einloggen, Kolonieansicht öffnen, Krankenstation-Kachel anklicken. Erwartung: Tile-Panel-Titel zeigt „Krankenstation | 3 – Vollausstattung", Hover-Popup zeigt „Vollausstattung — Level 3 / 3". Ebenso Hangar-Kachel prüfen (zeigt je nach aktuellem Level „Startmodul"/„Ladebucht"/„Anlegestelle"). Ein Gebäude ohne Beiname (z.B. Wohnhabitat) zeigt weiterhin nur „Level X / max" ohne Bindestrich-Zusatz.

- [x] **Step 13: Commit**

```bash
git add app/Http/Controllers/Colony/ColonyController.php resources/views/colony/hexview.blade.php tests/Feature/Colony/BuildingRepairTest.php tests/Feature/Colony/ColonyViewTest.php
git commit -m "feat: Ausbaustufen-Beinamen im Kolonie-Hex-View anzeigen (resolveTierLabel)"
```

---

### Task 5: Blade-Prettier-Formatierung + Pint (Pre-Commit-Hook-Konformität)

**Files:**
- Modify: `resources/views/colony/hexview.blade.php` (falls der Hook Formatierung anmahnt)

**Interfaces:** keine (reiner Format-Pass, keine Verhaltensänderung).

- [x] **Step 1: Blade-Datei zweimal mit Prettier formatieren**

Laut Projekt-Konvention (Blade-Dateien müssen zweimal formatiert werden, der Pre-Commit-Hook erwartet Idempotenz):

Run: `npx prettier --write resources/views/colony/hexview.blade.php && npx prettier --write resources/views/colony/hexview.blade.php`

- [x] **Step 2: Pint über den geänderten PHP-Code laufen lassen**

Run: `bin/pint app/Http/Controllers/Colony/ColonyController.php app/Console/Commands/ResetPlayer.php tests/Feature/Colony/BuildingRepairTest.php tests/Feature/Colony/ColonyViewTest.php`
Expected: keine Fehler, ggf. Auto-Fixes angewendet

- [x] **Step 3: Volle Test-Suite erneut laufen lassen (Regressionscheck nach Formatierung)**

Run: `bin/phpunit`
Expected: alle Tests weiterhin PASS

- [x] **Step 4: Commit (falls Formatierung etwas geändert hat)**

```bash
git add -A
git commit -m "style: Pint/Prettier-Formatierung nach Ausbaustufen-Grundgerüst" --allow-empty
```

---

## Self-Review-Notiz (vom Planautor, nicht Teil der Ausführung)

- **Spec-Abdeckung:** Klassifizierungs-Tabelle (alle Beiname-Zuweisungen) → Task 1+2. Wohnhabitat-Korrektur (6→3) → Task 2 Step 1. `max_level`-Korrekturen für bioFacility/infirmary/bar/sciencelab → Task 2 Steps 2-5. Anzeige im Hex-View → Task 4. Neue Mechaniken (Handelsposten-Kanäle, Analytik-Labor-Effizienzbonus, Sicherheits-Hub-Recycling) sind laut Spec separate Folge-Pläne, absichtlich NICHT Teil dieses Plans.
- **Nicht abgedeckt (bewusst, siehe Spec Folge-Tasks):** Zahlen-Kalibrierung, GDD §4/§13-Umschreibung, exklusive Ausbau-Varianten. Diese bleiben eigene Tasks.
