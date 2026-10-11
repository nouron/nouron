# T30 Schritt 3: Hangar-Umbau B1 + Bergungsflug — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Der Hangar wird eine einzige Halle (Stufe n = Schiffsklasse n und n Buchten, Vorfeld mit Geschenk-Drohne). Gescannte Funde werden mit Hangar nur noch per Bergungsflug (Ertrag × 1,5, kein Wurf) geborgen; ohne Hangar bleibt die Selbstbergung. Prospektion und Signalvermessung fallen weg, die Versorgungsfahrt zahlt kein Regolith mehr. Eine Rückkehr-Garantie ersetzt die 25-%-Startsperre aller Missionen: Ein Schiff startet nur, wenn es nach der Verschleiß-Prognose mit SP > 0 zurückkommt. Reparatur bleibt pro Schiff, kostet nur AP und funktioniert auch auf dem Vorfeld.

**Architecture:** Schiffe adressiert man über `bay` (0 = Vorfeld, 1–3 = Bucht) statt über Gebäude-Instanzen. Reine Buchtregeln liegen in `App\Support\HangarBays` (statisch, ohne DB). Damit vermeiden wir einen DI-Zyklus zwischen `HangarService` und `ColonyTileService`. Dort liegt auch `flightReadiness()` (Rückkehr-Garantie), die Guard, Zielliste, `findAction` und Bot gemeinsam nutzen. Kosten, Entfernung, Tragkraft und Ertrag des Flugs liefert der neue `FindFlightService`. `ColonyTileService::findAction()` ist die einzige Quelle für „welche Aktion gilt für diesen Fund“. Sie wird von Guards (`salvageFind`, Dispatch), Fundkarte und Bot gelesen. Die Baseline-Migration wird direkt editiert (Neustart, keine Datenmigration).

**Tech Stack:** Laravel 12 / PHP 8.2, MySQL 8, Alpine.js + PicoCSS, PHPUnit (`bin/phpunit`), Playwright (lokal, Headless-Chromium) für den UI-Check.

**Spec:** `docs/superpowers/specs/2026-10-05-t30-gleichwertige-eroeffnungen.md` §13 (bindend, inkl. §13.x Owner-Entscheidungen 2026-10-11 Punkte 1–11 und Rulings; Reparatur-Entwurf: `.superpowers/sdd/2026-10-09-t30-pool-v1-fundpool/designer-schiffsreparatur-b1.md`), §12 (B1, §12.x), §11.3/§11.4 (Pool, Pfad-Eingriffe). Bei Widerspruch gilt §13 vor §11/§12.

**Branch:** neuer Branch `feat/t30-schritt3-hangar-b1`, abgezweigt von `feat/t30-schritt3-spec` (enthält Spec §13). Nie auf `master` committen.

## Global Constraints

- E1: genau **eine** Hangar-Halle (`is_instanced` = 0). Stufe n = Schiffsklasse n **und** n Buchten. Die Geschenk-Drohne steht auf dem **Vorfeld** (Bucht 0). Das Vorfeld existiert ab Stufe 1 und gehört nur ihr. Hangar-Stufen kosten weiter flat 25 Rg (keine Kostenänderung).
- E2: Spalten `colony_ships.hangar_instance_id` → `bay` (`tinyInteger`, nullable; null = `pending`) und `colony_hangar_missions.instance_id` → `bay`. Neu `unique(['colony_id','bay'])` auf `colony_ships`. **Baseline direkt editieren, keine Datenmigration.**
- Aktiv-Regel: ein Schiff ist inaktiv, wenn Hallenstufe < max(Klassenstufe, Bucht). Das Vorfeld zählt wie Stufe 1.
- E3/E4: `mission_find_salvage` („Bergungsflug“) fliegt nur zu **gescannten** echten Funden. Kein Fernscan, kein Erfolgswurf. Entfernung aus dem Ring (`sol_distance_by_ring` [2 => 1, 3 => 2], Fallback max(1, Ring − 1)). Ertrag `(int) round(rg × finds.flight.yield_factor)`.
- E5: Drohne trägt `find_small` und `find_medium`. Der Frachter trägt alle drei Größen, die Korvette keine.
- E6: Ohne Hangar (kein Gebäude oder Stufe 0) gibt es Selbstbergung für alle Größen. Mit Stufe ≥ 1 gibt es für Funde nur noch den Flug. Die Bedingung wird **aus der aktuellen Hallenstufe abgeleitet**, nie gespeichert.
- E7: Bestandsschutz: Eine begonnene Selbstbergung (`salvage_ap_spent` > 0) läuft als Selbstbergung zu Ende. Ein Flug dorthin ist gesperrt. Es gibt keine Erstattung und keine Umrechnung.
- E8: `mission_prospecting_flight` und `mission_deep_survey` werden gestrichen. Der Zieltyp `signal_tile` und der Belohnungstyp `deep_scan` entfallen. `mission_supply_run.reward` = `['organics' => 10]`.
- E9: Der Flug zählt für `task_expedition_coverage` (Schwierigkeit `normal`, `succeeded` = 1), aber nicht gegen `finds.max_open_projects`.
- §13.x/7: **Rückkehr-Garantie** für alle Missionen. Start nur, wenn `SP − wear_per_sol × 2 × sol_distance > 0`. Fehlercode `ship_too_worn` (ersetzt `ship_sp_too_low`). `missions.dispatch_min_sp_pct` entfällt. Eine Funktion `HangarBays::flightReadiness()`.
- §13.x/8: Reparatur pro Schiff über `bay` (Bucht 0 inklusive), 1 Klick = `game.ship_repair.ap_per_click` AP = `game.ship_repair.sp_per_click` SP, Deckel `game.ship_repair.max_status_points`. **Nur AP**, kein Rg, keine Credits. Keine passive Dock-Reparatur (§13.x/10).
- Ertrags-Anzeige und -Auszahlung kommen aus **einer** Funktion (`FindFlightService::findFlightYield`) mit additiven Zeilen `base`, `flight_bonus` (Memory „Discount-Wiring“). Die Werte für Fundkarte und Zielliste kommen aus denselben Funktionen wie Dispatch und Tick.
- Folgeschritt V (Verlade-AP) ist **nicht** Teil dieses Plans. Es entsteht kein Config-Schlüssel `loading_ap`.
- Config ist Source of Truth (`config/missions.php`, `config/ships.php`, `config/game.php`). Das GDD bleibt zahlenfrei (ADR 0004), die Zahlen stehen nur in `docs/game-reference.md`.
- Code und Kommentare auf Englisch. Spielertexte nur über `lang/de/*.php` **und** `lang/en/*.php`. Blade-Texte via `__()`.
- AP-verbrauchende Buttons zeigen ihre Kosten als Chip (`partials.ap-cost-chip` bzw. `ap-chip ap-cost-chip`). Jede AJAX-Aktion mit AP- oder Ressourcenänderung synchronisiert die Resourcebar live (`apAvailable`, `organika`, `regolith`).
- Blade-Dateien zweimal mit Prettier formatieren (`npx prettier --write <datei>` ×2), JS/CSS über den Pre-commit-Hook.
- TDD verbindlich: zuerst einen roten Test schreiben und rot laufen sehen, dann minimal grün machen. Schnelle Suite während der Arbeit: `bin/phpunit --testsuite=laravel-feature,laravel-unit`. Bot- und Report-Tests liegen in `tests/Feature/Playtest` und laufen **nur** mit `--testsuite=playtest`.
- **Nie** `migrate:fresh` oder `db:seed` gegen die Dev-DB `nouron`. Den Reset der Dev-DB macht der Owner (`php artisan db:reset --force`). Tests bauen ihre DB per `RefreshDatabase` selbst neu, paratest-Worker-DBs migriert paratest selbst. Schema-Checks gehören in Feature-Tests (`tests/Feature/Database/BaselineSchemaTest.php`), nicht in Unit-Tests ohne DB-Trait.
- Committen nur mit gezieltem `git add <dateien>` (nie `git add -A`, der Owner hat untracked Dateien im Checkout).
- CHANGELOG: genau ein Block `## 2026-10-11` (bzw. Tagesdatum der Umsetzung), kurz, mit Verweis auf §13.

## Review Focus

Diese sechs Eingabeklassen bzw. Fehlerfälle impliziert die Spec, ohne dass ein Kerntest sie automatisch trifft. Jede Zeile hat ihren Test im genannten Task.

1. **Doppel-Dispatch auf dieselbe Kachel** (Doppelklick, zwei Schiffe, parallele Requests): Der zweite Dispatch wird mit `find_in_flight` abgelehnt, ohne AP- oder Organika-Abzug, weil die Kachel per `lockForUpdate` reserviert ist. → Task 5, `test_second_dispatch_to_the_same_find_is_rejected_without_cost`.
2. **Hangar wird während eines Flugs auf Stufe 0 zurückgebaut:** Der Flug kehrt normal zurück und zahlt aus. Die Kachel ist bis dahin weder per Flug noch per Selbstbergung angreifbar (`find_in_flight`). → Task 5, `test_flight_still_pays_after_the_hall_was_demolished`; Task 6, `test_self_salvage_is_blocked_while_a_flight_is_out_even_without_hangar`.
3. **Fremde oder erfundene Zielkoordinaten im Dispatch-Payload:** `invalid_target`, keine Änderung an fremden Kacheln. → Task 5, `test_dispatch_to_a_foreign_colony_find_is_invalid`.
4. **Schiff verschleißt im Flug auf 0 SP oder wird zurückgerufen:** kein Ertrag, die Kachel bleibt Fund und ist wieder Flugziel. → Task 5, `test_recall_and_abort_free_the_target_without_yield`.
5. **Bot-Falle „Hangar, aber kein einsatzbereites Schiff“** (Drohne mit wenig SP, Bestandsschutz-Projekt offen, nur ungescannte Signale): Der Bot repariert, bergt weiter oder scannt, statt still leerzulaufen. → Task 8, `test_worn_gift_drone_gets_repaired_then_flies`, `test_grandfathered_project_is_continued_after_the_hangar_stands`, `test_hangar_colony_with_only_unscanned_signals_scans_first`.
6. **Drohne mit 5–6 SP startet zu einem Ring-3-Fund und bricht ab** (vorher erlaubt, weil ≥ 25 %; Verschleiß 6): Der Start wird mit `ship_too_worn` abgelehnt, ohne AP- oder Organika-Abzug. Die Fundkarte sagt „Wartet: Drohne reparieren (1 AP)“. Mit 7 SP startet sie und kommt mit 1 SP und Ertrag zurück. → Task 2, `test_readiness_rule_and_repair_clicks`; Task 5, `test_worn_drone_is_refused_for_ring_3_and_returns_with_one_sp_after_repair`; Task 8, `test_bot_repairs_before_a_ring_3_flight_instead_of_starting`.

---

## Datei-Landkarte

| Datei | Verantwortung | Task |
|---|---|---|
| `app/Support/HangarBays.php` (neu) | reine Buchtregeln: Klassenstufe, Bucht offen, aktiv, Schiffsschlüssel; ab Task 2 `maxStatus()` und `flightReadiness()` (Rückkehr-Garantie) | 1, 2 |
| `database/migrations/0001_01_01_000000_baseline.php:302-335` | `bay`-Spalten, Unique-Index | 1 |
| `database/seeders/data/buildings.php:10` | Hangar `is_instanced` 0 | 1 |
| `app/Services/HangarService.php` | Buchtlogik, Reparatur aus Config, Readiness-Guard, Geschenk-Drohne, Flug-Dispatch, Auszahlung | 1, 2, 3, 4, 5, 6 |
| `app/Http/Controllers/Colony/HangarController.php`, `routes/web.php:133-135` | `{bay}` statt `{instanceId}`, Reparatur-AP aus Config | 1, 2, 7 |
| `app/Http/Controllers/Colony/ColonyController.php` | Guard „schon platziert“, Hook Geschenk-Drohne, Bau-Hinweis | 1, 3, 7 |
| `app/Console/Commands/GameTick.php` | Join über `bay`, `roll === false`, Typ `find_salvage` | 1, 4, 5 |
| `app/Services/FindFlightService.php` (neu) | Entfernung, Kosten, Tragkraft, Ertrag des Bergungsflugs | 5 |
| `app/Services/ColonyTileService.php` | `completeFind`, `findFlightReturnSol`, `findAction` (inkl. `ship_worn`), Guard `salvage_by_flight`, `tileFor` | 5, 6, 7 |
| `config/game.php`, `config/missions.php`, `config/ships.php` | `ship_repair`, Wegfall `dispatch_min_sp_pct`, Katalog, `find_carry`, `finds.flight` | 2, 4, 5 |
| `resources/views/colony/hangar.blade.php`, `public/js/hangar.js` | Buchten, Vorfeld, Zielliste je Fund, Zustand vorher/nachher | 1, 7 |
| `resources/views/colony/partials/tile-terrain.blade.php`, `resources/views/colony/hexview.blade.php`, `public/js/colony-hexgrid.js` | Fundkarte: eine Aktion und eine Begründung | 7 |
| `app/Services/OnboardingHintService.php` | einmaliger Hinweis bei Hangar Stufe 1 | 7 |
| `tests/Feature/Playtest/BotStrategy.php`, `RunReport.php`, `app/Support/OpeningComparison.php`, `app/Console/Commands/PlaytestCompare.php`, `tools/playtest-dashboard.php` | Bot-Regeln (`repair_ship_for_flight`, `dispatch_find_flight`), Readiness statt `20 × 0,25`, Topf `find_flight`, K2/K11/K12/K16/K17, Reparatur-AP | 1, 2, 4, 8 |
| `app/Console/Commands/ResetPlayer.php`, `app/Console/Commands/ColonySeedDemo.php`, `data/sql/testdata.sql` | Szenarien und Fixtures | 1, 3 |
| `docs/game-reference.md`, `docs/GDD.md`, `CHANGELOG.md`, `ROADMAP.md`, Spec §13 | Doku | 9 |

---|---|---|
| `app/Support/HangarBays.php` (neu) | reine Buchtregeln: Klassenstufe, Bucht offen, aktiv, Mindest-SP, Schiffsschlüssel | 1 |
| `database/migrations/0001_01_01_000000_baseline.php:302-335` | `bay`-Spalten, Unique-Index | 1 |
| `database/seeders/data/buildings.php:10` | Hangar `is_instanced` 0 | 1 |
| `app/Services/HangarService.php` | Buchtlogik, Geschenk-Drohne, Flug-Dispatch, Auszahlung | 1, 2, 3, 4, 5 |
| `app/Http/Controllers/Colony/HangarController.php`, `routes/web.php:133-135` | `{bay}` statt `{instanceId}` | 1, 6 |
| `app/Http/Controllers/Colony/ColonyController.php` | Guard „schon platziert“, Hook Geschenk-Drohne, Bau-Hinweis | 1, 2, 6 |
| `app/Console/Commands/GameTick.php` | Join über `bay`, `roll === false`, Typ `find_salvage` | 1, 3, 4 |
| `app/Services/FindFlightService.php` (neu) | Entfernung, Kosten, Tragkraft, Ertrag des Bergungsflugs | 4 |
| `app/Services/ColonyTileService.php` | `completeFind`, `findFlightReturnSol`, `findAction`, Guard `salvage_by_flight`, `tileFor` | 4, 5, 6 |
| `config/missions.php`, `config/ships.php`, `config/game.php` | Katalog, `find_carry`, `finds.flight` | 3, 4 |
| `resources/views/colony/hangar.blade.php`, `public/js/hangar.js` | Buchten, Vorfeld, Zielliste je Fund | 1, 6 |
| `resources/views/colony/partials/tile-terrain.blade.php`, `resources/views/colony/hexview.blade.php`, `public/js/colony-hexgrid.js` | Fundkarte: eine Aktion und eine Begründung | 6 |
| `app/Services/OnboardingHintService.php` | einmaliger Hinweis bei Hangar Stufe 1 | 6 |
| `tests/Feature/Playtest/BotStrategy.php`, `RunReport.php`, `app/Support/OpeningComparison.php`, `app/Console/Commands/PlaytestCompare.php`, `tools/playtest-dashboard.php` | Bot-Regeln, Topf `find_flight`, K2/K11/K12/K16/K17 | 1, 3, 7 |
| `app/Console/Commands/ResetPlayer.php`, `app/Console/Commands/ColonySeedDemo.php`, `data/sql/testdata.sql` | Szenarien und Fixtures | 1, 2 |
| `docs/game-reference.md`, `docs/GDD.md`, `CHANGELOG.md`, `ROADMAP.md`, Spec §13 | Doku | 8 |

---

### Task 1: Datenmodell B1 (eine Halle, Buchten, Vorfeld)

**Files:**
- Create: `app/Support/HangarBays.php`
- Modify: `database/migrations/0001_01_01_000000_baseline.php:302-335`
- Modify: `database/seeders/data/buildings.php:10` (Hangar `'is_instanced' => 1` → `0`)
- Modify: `config/buildings.php:205-238` (nur Kommentare)
- Modify: `app/Services/HangarService.php` (Z. 37-41 Konstante, 56-146 `getHangarSlots`, 154-177 `getPendingShips`, 179-219, 441-465 `requestShip`, 499-545 `grantFreeShip`, 547-597 `assignToHangar`, 636-768 `dispatchShip`, 1010-1076 `recallShip`/`repairShip`)
- Modify: `app/Http/Controllers/Colony/HangarController.php:71-79, 138-268`
- Modify: `routes/web.php:133-135`
- Modify: `app/Http/Controllers/Colony/ColonyController.php:440-447` (Guard nach `$existingBuilding`)
- Modify: `app/Console/Commands/GameTick.php:373-385`
- Modify: `data/sql/testdata.sql:40-66`
- Modify: `resources/views/colony/hangar.blade.php`, `public/js/hangar.js` (Umbenennung und Buchtanzeige)
- Modify: `tests/Feature/Playtest/BotStrategy.php` (URLs `$...->hangar_instance_id` → `->bay`, Z. 227/241/253/276/356/482; `hasFreeHangarSlot` Z. 2259-2272 → `hasFreeBayFor`; Aufrufe Z. 1913/1924)
- Modify: `lang/de/colony.php`, `lang/en/colony.php` (Hangar-Block um Z. 563-608 und Fehler Z. 724-738)
- Modify (mechanische Umbenennung): `tests/Feature/Colony/OrganikaProvisioningTest.php`, `tests/Feature/GameTick/GameTickRunIsolationTest.php`, `tests/Feature/Hangar/HangarControllerTest.php`, `tests/Feature/Hangar/HangarMissionResolutionTest.php`, `tests/Feature/Hangar/HangarServiceTest.php`, `tests/Feature/Hangar/HangarShipInactiveTest.php`, `tests/Feature/Playtest/BotStrategyFocusProfileTest.php`, `tests/Feature/Playtest/BotStrategyInactiveShipTest.php`, `tests/Feature/Playtest/BotStrategyMissionChoiceTest.php`, `tests/Feature/Playtest/BotStrategyMissionsAndEventsTest.php`, `tests/Feature/Security/ErrorResponseContractTest.php`, `tests/Feature/Security/ForeignColonyActionTest.php`
- Test: `tests/Feature/Hangar/HangarBaysTest.php` (neu), `tests/Feature/Database/BaselineSchemaTest.php` (ergänzen)

**Interfaces:**
- Produces: `App\Support\HangarBays` mit `APRON = 0`, `MAX_BAY = 3`, `requiredLevel(int $shipId): int`, `bayOpen(int $bay, int $hangarLevel): bool`, `isInactive(int $shipId, int $hangarLevel, int $bay): bool`, `shipKey(int $shipId): ?string`. (`maxStatus()` und `flightReadiness()` kommen in Task 2 dazu.)
- Produces: `HangarService::getHangarSlots(int $colonyId): list<array{bay:int, open:bool, required_level:int, hangar_level:int, hangar_status:float, ship:?array}>` (bei vorhandener Halle immer Bucht 0–3, sonst `[]`); `isShipInactive(int $shipId, int $hangarLevel, int $bay): bool`; `freeBay(int $colonyId): ?int`; `assignToHangar(int $colonyId, int $shipRowId, int $bay): void`; `dispatchShip(int $colonyId, int $bay, string $missionKey, ?array $target = null, string $difficulty = 'normal'): void`; `recallShip(int $colonyId, int $bay)`; `repairShip(int $colonyId, int $bay)`.
- Produces: Routen `colony.hangar.dispatch|recall|repair` mit Parameter `bay`; `POST /colony/hangar/assign` mit `{ship_row_id, bay}`; neue Fehlercodes `bay_reserved`, `bay_closed` (HangarService) und `building_already_placed` (ColonyController).

- [ ] **Step 1: Failing tests schreiben**

`tests/Feature/Hangar/HangarBaysTest.php`:

```php
<?php

namespace Tests\Feature\Hangar;

use App\Exceptions\GameRuleException;
use App\Models\User;
use App\Services\HangarService;
use App\Services\TickService;
use App\Support\HangarBays;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T30 §13.2 B1: one hangar hall, level n = ship class n and n bays, bay 0 = apron. */
class HangarBaysTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const DRONE = 85;

    private const FREIGHTER = 47;

    private const CORVETTE = 37;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(100));
        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->delete();
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['credits' => 10000]);
    }

    private function hall(int $level, ?int $q = 5): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => $q, 'tile_y' => $q === null ? null : 0]
        );
    }

    private function ship(int $shipId, ?int $bay, string $state = 'docked'): int
    {
        return DB::table('colony_ships')->insertGetId([
            'colony_id' => self::COLONY_ID, 'ship_id' => $shipId, 'bay' => $bay,
            'ship_state' => $state, 'level' => 0, 'status_points' => 20, 'ap_spend' => 0,
        ]);
    }

    private function svc(): HangarService
    {
        return $this->app->make(HangarService::class);
    }

    public function test_bay_rules(): void
    {
        $this->assertFalse(HangarBays::bayOpen(0, 0));
        $this->assertTrue(HangarBays::bayOpen(0, 1));
        $this->assertTrue(HangarBays::bayOpen(1, 1));
        $this->assertFalse(HangarBays::bayOpen(2, 1));
        $this->assertTrue(HangarBays::bayOpen(3, 3));

        $this->assertFalse(HangarBays::isInactive(self::DRONE, 1, 0));
        $this->assertTrue(HangarBays::isInactive(self::DRONE, 0, 0), 'apron needs level 1');
        $this->assertTrue(HangarBays::isInactive(self::DRONE, 2, 3), 'closed bay makes even a drone inactive');
        $this->assertTrue(HangarBays::isInactive(self::FREIGHTER, 1, 1));
        $this->assertFalse(HangarBays::isInactive(self::FREIGHTER, 2, 2));
        $this->assertSame('freighter', HangarBays::shipKey(self::FREIGHTER));
    }

    public function test_hangar_is_no_longer_instanced(): void
    {
        $this->assertSame(0, (int) DB::table('buildings')->where('id', 44)->value('is_instanced'));
    }

    public function test_slots_list_apron_and_three_bays_with_open_state_per_level(): void
    {
        foreach ([1 => [true, true, false, false], 2 => [true, true, true, false], 3 => [true, true, true, true]] as $level => $open) {
            $this->hall($level);
            $slots = $this->svc()->getHangarSlots(self::COLONY_ID);
            $this->assertSame([0, 1, 2, 3], array_column($slots, 'bay'));
            $this->assertSame($open, array_column($slots, 'open'), "level {$level}");
            $this->assertSame([1, 1, 2, 3], array_column($slots, 'required_level'));
        }
    }

    public function test_no_hall_means_no_slots(): void
    {
        $this->assertSame([], $this->svc()->getHangarSlots(self::COLONY_ID));
    }

    public function test_request_lands_in_first_free_bay_never_the_apron(): void
    {
        $this->hall(2);
        $this->ship(self::DRONE, 0); // gift drone on the apron

        $this->svc()->requestShip(self::COLONY_ID, self::DRONE);
        $this->svc()->requestShip(self::COLONY_ID, self::FREIGHTER);
        $this->svc()->requestShip(self::COLONY_ID, self::DRONE); // no bay left → pending

        $bays = DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->orderBy('id')->pluck('bay')->all();
        $this->assertSame([0, 1, 2, null], array_map(fn ($b) => $b === null ? null : (int) $b, $bays));
        $this->assertSame('pending', DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->orderByDesc('id')->value('ship_state'));
    }

    public function test_assign_rejects_apron_and_closed_bay(): void
    {
        $this->hall(1);
        $pending = $this->ship(self::DRONE, null, 'pending');

        try {
            $this->svc()->assignToHangar(self::COLONY_ID, $pending, 0);
            $this->fail('apron must be reserved');
        } catch (GameRuleException $e) {
            $this->assertSame('bay_reserved', $e->errorCode);
        }
        try {
            $this->svc()->assignToHangar(self::COLONY_ID, $pending, 2);
            $this->fail('bay 2 is closed at level 1');
        } catch (GameRuleException $e) {
            $this->assertSame('bay_closed', $e->errorCode);
        }

        $this->svc()->assignToHangar(self::COLONY_ID, $pending, 1);
        $this->assertSame(1, (int) DB::table('colony_ships')->where('id', $pending)->value('bay'));
    }

    public function test_ship_in_bay_3_is_inactive_at_level_2_and_active_after_levelup(): void
    {
        $this->hall(2);
        $id = $this->ship(self::DRONE, 3);
        $this->assertSame([$id], $this->svc()->inactiveShipRowIds(self::COLONY_ID));

        $this->hall(3);
        $this->assertSame([], $this->svc()->inactiveShipRowIds(self::COLONY_ID));
    }

    public function test_dispatch_recall_and_tick_resolution_run_over_the_bay(): void
    {
        config(['game.missions.difficulty.base_chance' => ['easy' => 1.0, 'normal' => 1.0, 'hard' => 1.0], 'game.missions.difficulty.chance_cap' => 1.0]);
        $this->hall(1);
        $this->ship(self::DRONE, 1);

        $this->svc()->dispatchShip(self::COLONY_ID, 1, 'mission_courier_run', null, 'normal');
        $this->assertSame(1, (int) DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->value('bay'));

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 102]);

        $this->assertSame('completed', DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->value('state'));
        $this->assertSame('docked', DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 1)->value('ship_state'));
    }

    public function test_a_second_hangar_hall_cannot_be_placed(): void
    {
        $this->hall(1, 5);
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => 6, 'r' => 0],
            ['ring' => 3, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 1, 'is_deep_scanned' => 0, 'event_type' => null]
        );

        $res = $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->postJson(route('colony.building.place'), ['building_id' => 44, 'q' => 6, 'r' => 0]);

        $res->assertStatus(422)->assertJson(['error' => 'building_already_placed']);
        $this->assertSame(5, (int) DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->value('tile_x'));
    }

    public function test_hangar_routes_take_the_bay_and_assign_validates_it(): void
    {
        $this->hall(1);
        $pending = $this->ship(self::DRONE, null, 'pending');

        $res = $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->postJson(route('colony.hangar.assign'), ['ship_row_id' => $pending, 'bay' => 0]);

        $res->assertStatus(422)->assertJson(['error' => 'bay_reserved']);
        $this->assertStringEndsWith('/hangar/2/dispatch', route('colony.hangar.dispatch', ['bay' => 2]));
    }
}
```

In `tests/Feature/Database/BaselineSchemaTest.php` ergänzen (neue Methode, `use Illuminate\Database\QueryException;` ist schon importiert):

```php
    public function test_ships_and_missions_are_addressed_by_bay_with_one_ship_per_bay(): void
    {
        $this->assertTrue(Schema::hasColumn('colony_ships', 'bay'));
        $this->assertFalse(Schema::hasColumn('colony_ships', 'hangar_instance_id'));
        $this->assertTrue(Schema::hasColumn('colony_hangar_missions', 'bay'));
        $this->assertFalse(Schema::hasColumn('colony_hangar_missions', 'instance_id'));

        DB::table('ships')->insert(['id' => 85, 'name' => 'ship_drone', 'purpose' => 'civil', 'row' => 0, 'column' => 0]);
        DB::table('user')->insert(['user_id' => 3, 'username' => 'b', 'display_name' => '', 'role' => 'player', 'password' => 'x', 'email' => 'b@n.de', 'activation_key' => '']);
        DB::table('glx_colonies')->insert(['id' => 1, 'name' => 'S', 'user_id' => 3, 'is_primary' => 1]);
        $row = ['colony_id' => 1, 'ship_id' => 85, 'ship_state' => 'docked'];
        DB::table('colony_ships')->insert([...$row, 'bay' => null]);
        DB::table('colony_ships')->insert([...$row, 'bay' => null]); // several pending rows are fine
        DB::table('colony_ships')->insert([...$row, 'bay' => 1]);

        $this->expectException(QueryException::class);
        DB::table('colony_ships')->insert([...$row, 'bay' => 1]);
    }
```

(Die Pflichtspalten von `ships`/`glx_colonies`/`user` gegen die Baseline prüfen und bei Bedarf ergänzen; die Baseline ist die Quelle, nicht dieser Entwurf.)

Zusätzlich in `HangarBaysTest` (Reparatur ist nach dem Rekeying auch auf dem Vorfeld möglich, Owner §13.x/8):

```php
    public function test_apron_ship_can_be_repaired_via_the_bay_route(): void
    {
        config(['game.bypass.ap_checks' => true]);
        $this->hall(1);
        $this->ship(self::DRONE, 0);
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 0)->update(['status_points' => 10]);

        $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->postJson(route('colony.hangar.repair', ['bay' => 0]))->assertOk();

        $this->assertSame(12.0, (float) DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 0)->value('status_points'));
    }
```

- [ ] **Step 2: Rot bestätigen**

Run: `bin/phpunit --filter='HangarBaysTest|BaselineSchemaTest'`
Expected: FAIL (`Class "App\Support\HangarBays" not found`, Spalte `bay` fehlt).

- [ ] **Step 3: `App\Support\HangarBays` anlegen**

```php
<?php

namespace App\Support;

/**
 * Hangar B1 (T30 spec §13.2): one hall, level n = ship class n AND n bays.
 * Bay 0 is the apron, reserved for the gift drone. Pure rules without DB access —
 * shared by HangarService, ColonyTileService::findAction() and the PlaytestBot so
 * neither service has to inject the other.
 */
final class HangarBays
{
    public const APRON = 0;

    public const MAX_BAY = 3;

    /** ship DB id => hangar level the class needs (Lv1 drone, Lv2 freighter, Lv3 corvette) */
    private const REQUIRED_LEVEL = [85 => 1, 47 => 2, 37 => 3];

    public static function requiredLevel(int $shipId): int
    {
        return self::REQUIRED_LEVEL[$shipId] ?? 1;
    }

    /** The apron opens with level 1, bay n with level n. */
    public static function bayOpen(int $bay, int $hangarLevel): bool
    {
        return $hangarLevel >= max(1, $bay);
    }

    /** GDD §7 + B1: inactive while the hall is below max(class level, bay); the apron counts as 1. */
    public static function isInactive(int $shipId, int $hangarLevel, int $bay): bool
    {
        return $hangarLevel < max(self::requiredLevel($shipId), $bay, 1);
    }

    /** config/ships.php key for a ship DB id. */
    public static function shipKey(int $shipId): ?string
    {
        foreach (config('ships', []) as $key => $cfg) {
            if ((int) $cfg['id'] === $shipId) {
                return $key;
            }
        }

        return null;
    }
}
```

- [ ] **Step 4: Baseline, Seeder, Config-Kommentar**

`database/migrations/0001_01_01_000000_baseline.php`, `colony_hangar_missions` (Z. 302-320):

```php
            $table->tinyInteger('bay');                       // was instance_id (B1, T30 §13.2)
            ...
            $table->index(['colony_id', 'bay'], 'colony_hangar_missions_colony_id_bay_index');
```

`colony_ships` (Z. 322-335):

```php
            $table->tinyInteger('bay')->nullable();           // 0 = apron, 1-3 = bay, null = pending
            ...
            $table->unique(['colony_id', 'bay'], 'colony_ships_colony_id_bay_unique');
```

`database/seeders/data/buildings.php:10`: beim Hangar `'is_instanced' => 0` setzen. Hinweis: `game:sync-config` synchronisiert `is_instanced` nicht (`SyncConfig` Z. 16). Der Wert kommt nur über `ReferenceDataSeeder` in die DB. In Tests geschieht das automatisch über den `TestSeeder`, die Dev-DB setzt der Owner per Reset neu auf.

`config/buildings.php:205-238`: Kommentare ersetzen, Werte unverändert lassen. Die Zeile `'id' => 44, // ... 1 hangar = 1 ship slot` wird zu `// one hall; level n = ship class n and n bays (T30 §13.2 B1)`. Den Block zu `max_level` umschreiben auf „Level = Schiffsklasse **und** Anzahl Buchten; Vorfeld (Bucht 0) für die Geschenk-Drohne; eine Halle, keine Instanzen mehr“. `max_instances` bleibt `null`, bei `is_instanced` = 0 ist der Wert bedeutungslos.

- [ ] **Step 5: `HangarService` auf Buchten umstellen**

- `SHIP_ID_TO_REQUIRED_HANGAR_LEVEL` (Z. 37-41) entfernen. `requiredHangarLevel()` gibt `HangarBays::requiredLevel($shipId)` zurück, `requestShip` Z. 346 nutzt `HangarBays::requiredLevel($shipId)`. `SHIP_MAX_STATUS`, `REPAIR_SP_PER_AP` und die 25-%-Sperre bleiben in diesem Task unverändert. Sie wandern erst in Task 2 in die Config bzw. werden dort durch die Rückkehr-Garantie ersetzt.
- `isShipInactive(int $shipId, int $hangarLevel, int $bay): bool` gibt `HangarBays::isInactive($shipId, $hangarLevel, $bay)` zurück. Alle Aufrufer per `grep -rn "isShipInactive" app tests` anpassen.
- `getHangarSlots()` ersetzen:

```php
    public function getHangarSlots(int $colonyId): array
    {
        $hall = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', self::HANGAR_BUILDING_ID)
            ->orderBy('instance_id')
            ->first(['level', 'status_points']);
        if ($hall === null) {
            return [];
        }
        $level = (int) $hall->level;

        $ships = DB::table('colony_ships')
            ->join('ships', 'ships.id', '=', 'colony_ships.ship_id')
            ->where('colony_ships.colony_id', $colonyId)
            ->whereNotNull('colony_ships.bay')
            ->get([
                'colony_ships.id', 'colony_ships.ship_id', 'ships.name', 'colony_ships.ship_state',
                'colony_ships.level', 'colony_ships.status_points', 'colony_ships.ap_spend',
                'colony_ships.bay', 'colony_ships.deliver_at_tick',
            ])
            ->keyBy('bay')
            ->all();

        $missions = DB::table('colony_hangar_missions')
            ->where('colony_id', $colonyId)
            ->where('state', 'active')
            ->get()
            ->keyBy('bay')
            ->all();

        $slots = [];
        for ($bay = HangarBays::APRON; $bay <= HangarBays::MAX_BAY; $bay++) {
            $ship = $ships[$bay] ?? null;
            $shipData = null;
            if ($ship !== null) {
                $mission = $missions[$bay] ?? null;
                if ($mission !== null) {
                    $mission = (array) $mission;
                    $mission['mission_key'] = $mission['destination'];
                    $mission['mission_name'] = __("missions.{$mission['destination']}_name");
                    $mission['return_tick'] = (int) $mission['dispatch_tick'] + 2 * (int) $mission['sol_distance'];
                }
                $inactive = HangarBays::isInactive((int) $ship->ship_id, $level, $bay);
                $shipData = [
                    'id' => (int) $ship->id,
                    'ship_id' => (int) $ship->ship_id,
                    'name' => $ship->name,
                    'ship_state' => $ship->ship_state,
                    'level' => (int) $ship->level,
                    'status_points' => (float) $ship->status_points,
                    'ap_spend' => (int) $ship->ap_spend,
                    'deliver_at_tick' => $ship->deliver_at_tick !== null ? (int) $ship->deliver_at_tick : null,
                    'active_mission' => $mission,
                    'required_hangar_level' => $this->requiredHangarLevel((int) $ship->ship_id),
                    'inactive' => $inactive,
                    'inactive_reason' => $inactive ? $this->inactiveReason((int) $ship->ship_id, $bay) : null,
                ];
            }

            $slots[] = [
                'bay' => $bay,
                'open' => HangarBays::bayOpen($bay, $level),
                'required_level' => max(1, $bay),
                'hangar_level' => $level,
                'hangar_status' => (float) $hall->status_points,
                'ship' => $shipData,
            ];
        }

        return $slots;
    }
```

  `inactiveReason(int $shipId, int $bay = 1)` nennt die Stufe `max(requiredHangarLevel($shipId), $bay, 1)`.
- `getPendingShips()` Z. 159: `whereNull('colony_ships.bay')`.
- `inactiveShipRowIds()`:

```php
    public function inactiveShipRowIds(int $colonyId): array
    {
        $level = $this->hangarMaxLevel($colonyId);

        return DB::table('colony_ships')
            ->where('colony_id', $colonyId)
            ->whereNotNull('bay')
            ->get(['id', 'ship_id', 'bay'])
            ->filter(fn (object $row): bool => HangarBays::isInactive((int) $row->ship_id, $level, (int) $row->bay))
            ->map(fn (object $row): int => (int) $row->id)
            ->values()
            ->all();
    }
```

- Neu `freeBay()`:

```php
    /** First free bay 1..hall level (the apron is never assigned automatically), or null. */
    public function freeBay(int $colonyId): ?int
    {
        $level = min($this->hangarMaxLevel($colonyId), HangarBays::MAX_BAY);
        $occupied = DB::table('colony_ships')->where('colony_id', $colonyId)->whereNotNull('bay')
            ->pluck('bay')->map(fn ($b) => (int) $b)->all();
        for ($bay = 1; $bay <= $level; $bay++) {
            if (! in_array($bay, $occupied, true)) {
                return $bay;
            }
        }

        return null;
    }
```

- `requestShip` Z. 441-465 und `grantFreeShip` Z. 511-531: Instanzsuche ersetzen durch `$bay = $this->freeBay($colonyId);`. Beim Insert `'bay' => $bay` setzen. Für `pending` gilt `$bay === null`. Das Klassen-Gate in `requestShip` (Z. 346-349) bleibt und sichert Klasse ≤ Stufe ab.
- `assignToHangar(int $colonyId, int $shipRowId, int $bay)`:

```php
        if ($bay === HangarBays::APRON) {
            throw $this->ruleError('bay_reserved');
        }
        DB::transaction(function () use ($colonyId, $shipRowId, $bay): void {
            $ship = DB::table('colony_ships')->where('id', $shipRowId)->where('colony_id', $colonyId)->whereNull('bay')->first();
            if ($ship === null) {
                throw $this->ruleError('ship_not_pending');
            }
            if (! HangarBays::bayOpen($bay, $this->hangarMaxLevel($colonyId)) || $bay > HangarBays::MAX_BAY) {
                throw $this->ruleError('bay_closed');
            }
            if (DB::table('colony_ships')->where('colony_id', $colonyId)->where('bay', $bay)->exists()) {
                throw $this->ruleError('hangar_occupied');
            }
            DB::table('colony_ships')->where('id', $shipRowId)
                ->update(['bay' => $bay, 'pending_until_tick' => null, 'ship_state' => 'docked']);
        });
```

- `dispatchShip(int $colonyId, int $bay, ...)`: Schiff `->where('bay', $bay)`. Die Stufe kommt aus `$this->hangarMaxLevel($colonyId)` statt aus der Instanz (Z. 663-667), die Inaktiv-Prüfung läuft über `$this->isShipInactive((int) $ship->ship_id, $hangarLevel, $bay)`. Update `->where('bay', $bay)`, Insert `'bay' => $bay`.
- `recallShip(int $colonyId, int $bay)` und `repairShip(int $colonyId, int $bay)`: alle `instance_id`- bzw. `hangar_instance_id`-Filter auf `bay` umstellen.
- Docblock Z. 324-327: „hangar_instance_id = NULL“ → „bay = NULL“.

- [ ] **Step 6: Controller, Routen, GameTick, Platzierungs-Guard**

`routes/web.php:133-135`:

```php
    Route::post('/hangar/{bay}/dispatch', [HangarController::class, 'dispatch'])->name('hangar.dispatch');
    Route::post('/hangar/{bay}/recall', [HangarController::class, 'recall'])->name('hangar.recall');
    Route::post('/hangar/{bay}/repair', [HangarController::class, 'repair'])->name('hangar.repair');
```

(Bewusst kein `whereNumber`: Die Blade-Vorlagen erzeugen die URL mit dem Platzhalter `__ID__`, siehe Memory „`@js(route(...))` in `x-data`“. Der Typ `int $bay` und die Service-Fehlercodes reichen als Prüfung.)

`HangarController`:
- Z. 73-79 `commissionedShipIds`: `->whereNotNull('bay')`.
- `assignToHangar` Z. 144-156: Validierung `'bay' => 'required|integer|min:0|max:3'`, Aufruf mit `(int) $validated['bay']`. Bucht 0 lehnt der Service mit `bay_reserved` ab.
- `dispatch|recall|repair(Request $request, int $bay)`: Parameter und Weitergabe umbenennen.
- `fetchSlot(int $colonyId, int $bay)`: `if ($slot['bay'] === $bay)`.

`GameTick.php:373-385`:

```php
        $missions = DB::table('colony_hangar_missions as m')
            ->join('colony_ships as cs', function ($join) {
                $join->on('cs.colony_id', '=', 'm.colony_id')
                    ->on('cs.bay', '=', 'm.bay');
            })
            ...
            ->get([
                'm.id as mission_id', 'm.colony_id', 'm.bay', 'm.destination',
                ...
```

`ColonyController::placeBuilding`, direkt nach dem `$existingBuilding`-Block (nach Z. 446):

```php
        // A non-instanced building exists once: a second placement used to silently MOVE
        // it (and reset ap_spend). Hangar B1 relies on this (T30 §13.2) — and it is the
        // intended rule for every non-instanced building.
        if (! $isHarvester && ! $building->is_instanced && $existingBuilding !== null && $existingBuilding->tile_x !== null) {
            return $this->fail('building_already_placed');
        }
```

- [ ] **Step 7: Fixtures (`data/sql/testdata.sql:40-66`)**

```sql
-- colony_buildings: one hangar hall (building_id=44) for colony 1 (Springfield), B1 (T30 §13.2).
-- Lv3 = three bays and every class active: drone bay 1, freighter bay 2, corvette bay 3.
INSERT INTO colony_buildings (colony_id,building_id,instance_id,level,status_points,ap_spend,placed_at_tick) VALUES(1,44,1,3,20,0,1);
```

Die Zeile „Hangar 2“ entfällt. In den sieben `INSERT INTO colony_ships`-Zeilen wird die Spalte `hangar_instance_id` zu `bay` (Werte bleiben `NULL`). Die UPDATEs werden zu:

```sql
-- Bays: drone (85) bay 1 (dispatched), freighter (47) bay 2, corvette (37) bay 3.
UPDATE colony_ships SET bay=1, ship_state='dispatched' WHERE colony_id=1 AND ship_id=85;
UPDATE colony_ships SET bay=3, ship_state='docked'     WHERE colony_id=1 AND ship_id=37;
UPDATE colony_ships SET bay=2, ship_state='docked'     WHERE colony_id=1 AND ship_id=47;
```

Die Missionszeilen bekommen `(colony_id,bay,...)`: Die erste behält Bucht 1, die zweite (Frachter, `recalled`) bekommt Bucht 2. Kommentare entsprechend anpassen.

- [ ] **Step 8: Hangar-Screen auf Buchten**

`resources/views/colony/hangar.blade.php`:
- Z. 31-33: `route("colony.hangar.dispatch", ["bay" => "__ID__"])` (ebenso für `recall` und `repair`).
- i18n-Block (nach Z. 82) ergänzen:

```blade
                apron: @json(__("colony.hangar_apron")),
                bay: @json(__("colony.hangar_bay")),
                bayLocked: @json(__("colony.hangar_bay_locked")),
                apronReserved: @json(__("colony.hangar_apron_reserved")),
```

- Z. 118: `:key="slot.bay"`; Z. 123: `x-text="slot.bay === 0 ? i18n.apron : i18n.bay.replace(':n', slot.bay)"`.
- Den Leer-Block (Z. 178-205) dreiteilen:
  - `x-if="slot.ship === null && slot.open && slot.bay > 0"`: bisheriger Inhalt mit Anfrage-Button.
  - `x-if="slot.ship === null && !slot.open"`: `<span class="hangar-status-chip hangar-status-chip--empty" x-text="i18n.bayLocked.replace(':level', slot.required_level)"></span>`.
  - `x-if="slot.ship === null && slot.open && slot.bay === 0"`: `<span class="hangar-status-chip hangar-status-chip--empty" x-text="i18n.apronReserved"></span>`.
- Alle übrigen `slot.instance_id` durch `slot.bay` ersetzen.
- Pending-Auswahl Z. 367-369: `:key="freeSlot.bay"`, `:value="freeSlot.bay"`, `x-text="i18n.bay.replace(':n', freeSlot.bay)"`.

`public/js/hangar.js`: In `init()` Z. 77-81, `openMissionDialog`, `submitMission`, `recall`, `repair`, `assignShip` und `_updateSlot` wird `instance_id`/`instanceId` zu `bay`. Das Payload von `assignShip` lautet `bay: parseInt(bay, 10)`. Getter:

```js
        get slotInfo() {
            const bays = this.slots.filter((s) => s.open && s.bay > 0);
            return { used: bays.filter((s) => s.ship !== null).length, total: bays.length };
        },

        get freeSlots() {
            return this.slots.filter((s) => s.open && s.bay > 0 && s.ship === null);
        },
```

Lang (`lang/de/colony.php` / `lang/en/colony.php`):

```php
    'hangar_slot_count' => 'Buchten',                    // en: 'Bays'
    'hangar_apron' => 'Vorfeld',                         // en: 'Apron'
    'hangar_bay' => 'Bucht :n',                          // en: 'Bay :n'
    'hangar_bay_locked' => 'ab Stufe :level',            // en: 'from level :level'
    'hangar_apron_reserved' => 'Platz der Geschenk-Drohne', // en: 'Reserved for the gift drone'
    'hangar_error_bay_reserved' => 'Das Vorfeld ist der Geschenk-Drohne vorbehalten.', // en: 'The apron is reserved for the gift drone.'
    'hangar_error_bay_closed' => 'Diese Bucht ist noch geschlossen — erst den Hangar ausbauen.', // en: 'This bay is still closed — upgrade the hangar first.'
    'error_building_already_placed' => 'Dieses Gebäude steht bereits — es gibt nur eines davon.', // en: 'This building already stands — there is only one of it.'
```

- [ ] **Step 9: Bestehende Tests und Bot mechanisch umbenennen**

Run: `grep -rn "hangar_instance_id\|'instanceId'\|firstWhere('instance_id'" app tests resources public/js routes data`. Diese Regeln anwenden:
- `hangar_instance_id` → `bay`.
- In Inserts nach `colony_hangar_missions` wird `'instance_id' =>` zu `'bay' =>`.
- Bei `route('colony.hangar.*', ['instanceId' => …])` wird `'instanceId'` zu `'bay'`.
- Das Assign-Payload ändert sich von `'instance_id'` zu `'bay'`.
- `collect($slots)->firstWhere('instance_id', X)` wird zu `firstWhere('bay', X)`.
- Tests, die eine **zweite** Hangar-Zeile anlegen (z. B. `HangarServiceTest::insertHangar(2, …)`, `test_get_hangar_slots_returns_sorted_by_instance_id`, `ForeignColonyActionTest` Z. 121 `instance_id => 50`), stellen auf **eine** Halle mit passender Stufe und Schiffe in Bucht 1..3 um. Reine Instanz-Semantik-Tests (Sortierung nach `instance_id`) werden gelöscht, ihr Ersatz steht in `HangarBaysTest`.
- `HangarMissionResolutionTest` und `HangarShipInactiveTest`: Die Konstanten `HANGAR_INSTANCE`/`INSTANCE` heißen jetzt `BAY` (Wert 1). Der Frachter in Bucht 1 bei Stufe 2 bleibt aktiv (max(2, 1) = 2).

`tests/Feature/Playtest/BotStrategy.php`: Alle `"/colony/hangar/{$…->hangar_instance_id}/…"` werden zu `{$…->bay}`. `hasFreeHangarSlot` (Z. 2259-2272) wird ersetzt durch:

```php
    /** B1: a ship of class $classLevel docks only if the hall carries the class and a bay 1..level is free. */
    private static function hasFreeBayFor(BotSession $b, int $classLevel): bool
    {
        $level = self::hangarLevel($b);
        if ($level < $classLevel) {
            return false;
        }

        return DB::table('colony_ships')->where('colony_id', $b->colonyId)
            ->whereBetween('bay', [1, $level])->count() < $level;
    }
```

`shipToRequest` Z. 1913 → `self::hasFreeBayFor($b, 1)`, Z. 1924 → `self::hasFreeBayFor($b, 2)`.

- [ ] **Step 10: Grün**

Run: `bin/phpunit --filter='HangarBaysTest|BaselineSchemaTest|Hangar|OrganikaProvisioning|GameTickRunIsolation|ErrorResponseContract|ForeignColonyAction'`
Expected: PASS.
Run: `bin/phpunit --testsuite=playtest --filter='BotStrategyFocusProfile|BotStrategyInactiveShip|BotStrategyMissionChoice|BotStrategyMissionsAndEvents'`
Expected: PASS.
Run: `grep -rn "hangar_instance_id" app tests resources public/js routes data database` → keine Treffer. Zusätzlich `grep -rn "route('colony.hangar\|route(\"colony.hangar" resources/views` prüfen (Memory: Route-Parameter in `x-data` wird serverseitig ausgewertet).
Dann `npx prettier --write resources/views/colony/hangar.blade.php` zweimal ausführen.
Run: `bin/phpunit --testsuite=laravel-feature,laravel-unit` → grün.

- [ ] **Step 11: Commit**

```bash
git add app/Support/HangarBays.php database/migrations/0001_01_01_000000_baseline.php database/seeders/data/buildings.php config/buildings.php app/Services/HangarService.php app/Http/Controllers/Colony/HangarController.php app/Http/Controllers/Colony/ColonyController.php routes/web.php app/Console/Commands/GameTick.php data/sql/testdata.sql resources/views/colony/hangar.blade.php public/js/hangar.js lang/de/colony.php lang/en/colony.php tests/Feature/Hangar tests/Feature/Database/BaselineSchemaTest.php tests/Feature/Colony/OrganikaProvisioningTest.php tests/Feature/GameTick/GameTickRunIsolationTest.php tests/Feature/Security tests/Feature/Playtest
git commit -m "feat(T30): Hangar B1 — eine Halle, Buchten statt Instanzen (Baseline, Service, Routen, UI)"
```

---

### Task 2: Reparatur & Rückkehr-Garantie

**Files:**
- Modify: `config/game.php` (neuer Block `ship_repair` direkt vor `'hangar' => [` Z. 331; Kommentar Z. 162-165 bei `salvage_arrival_sp_pct`)
- Modify: `config/missions.php:34` (`dispatch_min_sp_pct` löschen)
- Modify: `app/Support/HangarBays.php` (`maxStatus()`, `flightReadiness()`)
- Modify: `app/Services/HangarService.php` (Konstanten Z. 19/21 löschen; `requestShip`/`grantFreeShip` Insert `status_points`; `dispatchShip` Z. 677-681 → Readiness-Guard nach der Distanzberechnung; `repairShip` Z. 1042-1076; `getHangarSlots` Schiffsdaten `readiness`)
- Modify: `app/Http/Controllers/Colony/HangarController.php:213-243` (`repair`: AP aus Config)
- Modify: `tests/Feature/Playtest/BotStrategy.php` (Z. 525-579 `expeditionMissionCandidate`, 1591-1611 `dispatchCandidate`, 1663-1711 `firstFlyableMission`, 2106-2133 `compoundsMissionCandidate`, 2139-2187 `salvageDispatchCandidate`: `20 × dispatch_min_sp_pct` → `flightReadiness`)
- Modify: `lang/de/missions.php:70`, `lang/en/missions.php:64` (`error_sp_too_low` → `error_ship_too_worn`)
- Modify: `tests/Feature/Hangar/HangarServiceTest.php:726-746` (zwei 25-%-Tests ersetzen), `tests/Feature/Hangar/HangarControllerTest.php:580` (Kommentar)
- Test: `tests/Feature/Hangar/ShipRepairReadinessTest.php` (neu), `tests/Feature/Playtest/BotStrategyFlightReadinessTest.php` (neu)

**Spec-Abweichung:** Der Entwurf sieht `flightReadiness` im `HangarService` vor. Die Funktion liegt hier als reine statische Funktion in `HangarBays`, weil `ColonyTileService::findAction()` (Task 6) sie braucht und `HangarService` seinerseits `ColonyTileService` injiziert (Task 5). Im Service gäbe das einen DI-Zyklus. `ship_too_worn` liefert die Zahlen (Zustand, Verschleiß, Reparatur-AP) im übersetzten `message` statt als eigenes JSON-Payload, weil `GameRuleException` nur Code und Text trägt.

**Interfaces:**
- Consumes: `bay`-Adressierung aus Task 1.
- Produces: `config('game.ship_repair')` = `['max_status_points' => 20, 'sp_per_click' => 2, 'ap_per_click' => 1]`; `HangarBays::maxStatus(): int`; `HangarBays::flightReadiness(int $shipId, float $statusPoints, int $solDistance): array{ready:bool, wear:float, sp_after:float, repair_clicks:int, repair_ap:int}`. In `getHangarSlots()[*]['ship']['readiness']` liegt eine Map Entfernung → `flightReadiness` für jede im Katalog vorkommende Entfernung (inkl. `sol_distance_by_ring`, sobald Task 5 sie einführt). Fehlercode `ship_too_worn` (Lang `missions.error_ship_too_worn`, Platzhalter `:current`, `:wear`, `:ap`). `missions.dispatch_min_sp_pct` existiert nicht mehr.

- [ ] **Step 1: Failing tests**

`tests/Feature/Hangar/ShipRepairReadinessTest.php`:

```php
<?php

namespace Tests\Feature\Hangar;

use App\Exceptions\GameRuleException;
use App\Models\User;
use App\Services\AdvisorService;
use App\Services\HangarService;
use App\Services\TickService;
use App\Support\HangarBays;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T30 §13.x/7–8: return guarantee replaces the 25 % floor; repair per ship, AP only, values from config. */
class ShipRepairReadinessTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const DRONE = 85;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(100));
        config(['game.bypass.ap_checks' => false, 'game.bypass.resource_costs' => false]);
        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_buildings')->updateOrInsert(['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 5, 'tile_y' => 5]);
        DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->update(['amount' => 50]);
    }

    private function drone(float $sp, int $bay = 0): void
    {
        DB::table('colony_ships')->insert(['colony_id' => self::COLONY_ID, 'ship_id' => self::DRONE, 'bay' => $bay,
            'ship_state' => 'docked', 'level' => 0, 'status_points' => $sp, 'ap_spend' => 0]);
    }

    private function ap(): int
    {
        return $this->app->make(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID);
    }

    public function test_config_replaces_constants_and_the_25_percent_floor(): void
    {
        $this->assertSame(['max_status_points' => 20, 'sp_per_click' => 2, 'ap_per_click' => 1], config('game.ship_repair'));
        $this->assertNull(config('missions.dispatch_min_sp_pct'));
        $this->assertSame(20, HangarBays::maxStatus());
    }

    public function test_readiness_rule_and_repair_clicks(): void
    {
        // Drone wear 1.5/Sol; ring 3 = 2 Sols out and back = 6.
        $this->assertSame(['ready' => false, 'wear' => 6.0, 'sp_after' => 0.0, 'repair_clicks' => 1, 'repair_ap' => 1],
            HangarBays::flightReadiness(self::DRONE, 6.0, 2));
        $this->assertFalse(HangarBays::flightReadiness(self::DRONE, 5.0, 2)['ready'], '5-6 SP used to pass the 25 % floor and abort on ring 3');
        $this->assertSame(1, HangarBays::flightReadiness(self::DRONE, 5.0, 2)['repair_clicks']);
        $this->assertSame(2, HangarBays::flightReadiness(self::DRONE, 4.0, 2)['repair_clicks']);
        $this->assertSame(['ready' => true, 'wear' => 6.0, 'sp_after' => 1.0, 'repair_clicks' => 0, 'repair_ap' => 0],
            HangarBays::flightReadiness(self::DRONE, 7.0, 2));
    }

    public function test_dispatch_refuses_a_ship_that_would_not_come_back_without_cost(): void
    {
        $this->drone(3.0); // courier run: 1 Sol out and back = 3 wear
        [$ap, $or] = [$this->ap(), (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->value('amount')];

        try {
            $this->app->make(HangarService::class)->dispatchShip(self::COLONY_ID, 0, 'mission_courier_run', null, 'normal');
            $this->fail('expected ship_too_worn');
        } catch (GameRuleException $e) {
            $this->assertSame('ship_too_worn', $e->errorCode);
            $this->assertStringContainsString('1', $e->getMessage(), 'message names the repair AP');
        }
        $this->assertSame($ap, $this->ap());
        $this->assertSame($or, (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->value('amount'));
    }

    public function test_short_mission_with_low_sp_is_allowed_when_the_ship_comes_back(): void
    {
        $this->drone(3.5); // below the old 25 % floor (5), but 3.5 − 3 > 0

        $this->app->make(HangarService::class)->dispatchShip(self::COLONY_ID, 0, 'mission_courier_run', null, 'normal');

        $this->assertSame('dispatched', DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 0)->value('ship_state'));
    }

    public function test_repair_uses_config_values_and_costs_only_ap(): void
    {
        config(['game.ship_repair.sp_per_click' => 3]);
        $this->drone(10.0);
        $rg = (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 3)->value('amount');
        $credits = (int) DB::table('user_resources')->where('user_id', 3)->value('credits');
        $ap = $this->ap();

        $this->actingAs(User::where('user_id', 3)->firstOrFail())
            ->postJson(route('colony.hangar.repair', ['bay' => 0]))->assertOk()->assertJsonStructure(['apAvailable']);

        $this->assertSame(13.0, (float) DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 0)->value('status_points'));
        $this->assertSame($ap - 1, $this->ap());
        $this->assertSame($rg, (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 3)->value('amount'));
        $this->assertSame($credits, (int) DB::table('user_resources')->where('user_id', 3)->value('credits'));
    }

    public function test_slots_carry_readiness_per_catalog_distance(): void
    {
        $this->drone(4.0);

        $ship = $this->app->make(HangarService::class)->getHangarSlots(self::COLONY_ID)[0]['ship'];

        $this->assertTrue($ship['readiness'][1]['ready']);      // wear 3
        $this->assertFalse($ship['readiness'][4]['ready']);     // wear 12
        $this->assertSame(HangarBays::flightReadiness(self::DRONE, 4.0, 4), $ship['readiness'][4]);
    }
}
```

`tests/Feature/Playtest/BotStrategyFlightReadinessTest.php`:

```php
<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\AdvisorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T30 §13.x/7: bot mission candidates use the return guarantee, not a mirrored 20 × 0.25. */
class BotStrategyFlightReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function bootWithDrone(float $sp): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('colony_buildings')->insert(['colony_id' => $bot->colonyId, 'building_id' => BuildingId::Hangar->value,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => -1, 'tile_y' => 1]);
        DB::table('colony_ships')->insert(['colony_id' => $bot->colonyId, 'ship_id' => 85, 'bay' => 1, 'ship_state' => 'docked',
            'level' => 0, 'status_points' => $sp, 'ap_spend' => 0]);
        $service = app(AdvisorService::class);
        DB::table('locked_actionpoints')->where('scope_type', 'colony')->where('scope_id', $bot->colonyId)->delete();
        $service->lockActionPoints($bot->colonyId, $service->getTotalActionPoints($bot->colonyId) - 10);

        return $bot;
    }

    private function recon(BotSession $bot): mixed
    {
        return collect(BotStrategy::default())->firstWhere('name', 'dispatch_mission')['when']($bot);
    }

    public function test_recon_flies_with_4_sp_because_the_drone_comes_back(): void
    {
        $this->assertNotEmpty($this->recon($this->bootWithDrone(4.0)), 'old 25 % floor (5) blocked this');
    }

    public function test_recon_waits_with_3_sp(): void
    {
        $this->assertEmpty($this->recon($this->bootWithDrone(3.0)));
    }

    public function test_bot_no_longer_mirrors_the_old_constants(): void
    {
        $source = file_get_contents(base_path('tests/Feature/Playtest/BotStrategy.php'));

        $this->assertStringNotContainsString('dispatch_min_sp_pct', $source);
        $this->assertStringNotContainsString('$shipMaxStatus = 20', $source);
    }
}
```

`HangarServiceTest` Z. 726-746: `test_dispatch_ship_throws_for_sp_too_low` → `test_dispatch_ship_throws_ship_too_worn_when_the_courier_would_not_return` (Drohne 3.0 SP, `expectException(GameRuleException::class)`; per `try/catch` den Code `ship_too_worn` prüfen). `test_dispatch_ship_allows_sp_exactly_at_threshold` → `test_dispatch_ship_allows_sp_just_above_the_wear` (3.5 SP). Kopf-Docblock Z. 40-41 anpassen. `HangarControllerTest` Z. 580: Kommentar `10.0 + game.ship_repair.sp_per_click (2) = 12.0`.

- [ ] **Step 2: Rot bestätigen**

Run: `bin/phpunit --filter='ShipRepairReadinessTest|HangarServiceTest'` und `bin/phpunit --testsuite=playtest --filter=BotStrategyFlightReadinessTest` → FAIL (`flightReadiness` fehlt, Config fehlt).

- [ ] **Step 3: Config**

`config/game.php`, direkt vor `'hangar' => [`:

```php
    // T30 §13.x/8: ship repair per ship (bay 0-3, apron included), AP only — no Regolith,
    // no Credits (repair_cost_per_point dropped). One click = ap_per_click AP → +sp_per_click SP,
    // capped at max_status_points. Ships wear only in flight (config/ships.php wear_per_sol);
    // the start gate is the return guarantee HangarBays::flightReadiness(), not a percentage.
    'ship_repair' => [
        'max_status_points' => 20,
        'sp_per_click' => 2,
        'ap_per_click' => 1,
    ],
```

`config/game.php` Z. 162-165 (Kommentar zu `salvage_arrival_sp_pct`): Statt „Deliberately mirrors missions.dispatch_min_sp_pct (0.25)“ steht dort „The former ship dispatch floor was also 25 %; it was replaced by the return guarantee (T30 §13.x/7), this value stands on its own“. Der Wert bleibt.

`config/missions.php:34`: Die Zeile `'dispatch_min_sp_pct' => 0.25, ...` ersetzen durch den Kommentar `// Start gate: return guarantee (T30 §13.x/7) — a ship starts only if SP − wear_per_sol × 2 × sol_distance > 0, see App\Support\HangarBays::flightReadiness().`

- [ ] **Step 4: `HangarBays`**

```php
    public static function maxStatus(): int
    {
        return (int) config('game.ship_repair.max_status_points', 20);
    }

    /**
     * T30 §13.x/7 return guarantee: a ship may start only if it comes home with SP > 0
     * (wear_per_sol × 2 × sol_distance). One function for the dispatch guard, the hangar
     * target list, ColonyTileService::findAction() and the PlaytestBot.
     *
     * @return array{ready: bool, wear: float, sp_after: float, repair_clicks: int, repair_ap: int}
     */
    public static function flightReadiness(int $shipId, float $statusPoints, int $solDistance): array
    {
        $wear = round((float) config('ships.'.self::shipKey($shipId).'.wear_per_sol', 1.0) * 2 * $solDistance, 2);
        $spAfter = round($statusPoints - $wear, 2);
        $perClick = max(1, (int) config('game.ship_repair.sp_per_click', 2));
        $clicks = $spAfter > 0 ? 0 : (int) floor(-$spAfter / $perClick) + 1;

        return [
            'ready' => $spAfter > 0,
            'wear' => $wear,
            'sp_after' => $spAfter,
            'repair_clicks' => $clicks,
            'repair_ap' => $clicks * (int) config('game.ship_repair.ap_per_click', 1),
        ];
    }
```

- [ ] **Step 5: `HangarService` + Controller**

- `SHIP_MAX_STATUS` und `REPAIR_SP_PER_AP` (Z. 19/21) löschen. Die Inserts in `requestShip`/`grantFreeShip` nutzen `'status_points' => HangarBays::maxStatus()`.
- `dispatchShip`: den Block „§7 dispatch block“ Z. 677-681 löschen. **Nach** der Zeile `$solDistance = …` (Task 5 setzt dort die Ring-Entfernung, der Guard muss dahinter bleiben):

```php
            // T30 §13.x/7: return guarantee instead of the 25 % floor — a ship never
            // starts a flight it cannot come back from.
            $readiness = HangarBays::flightReadiness((int) $ship->ship_id, (float) $ship->status_points, $solDistance);
            if (! $readiness['ready']) {
                throw $this->ruleError('ship_too_worn', 'missions.error_ship_too_worn', [
                    'current' => (float) $ship->status_points, 'wear' => $readiness['wear'], 'ap' => $readiness['repair_ap'],
                ]);
            }
```

- `repairShip(int $colonyId, int $bay)`: `$max = HangarBays::maxStatus();` und `$newStatus = min($max, $current + (int) config('game.ship_repair.sp_per_click', 2));`. `ap_spend` erhöht sich um `(int) config('game.ship_repair.ap_per_click', 1)`. Im Docblock „1 AP, +REPAIR_SP_PER_AP“ durch die Config-Schlüssel ersetzen.
- `getHangarSlots()`, Schiffsdaten: `'readiness' => $this->readinessByDistance((int) $ship->ship_id, (float) $ship->status_points),` und:

```php
    /** flightReadiness() for every distance a catalog mission can have — the dialog reads it, never recomputes. */
    private function readinessByDistance(int $shipId, float $statusPoints): array
    {
        $distances = [];
        foreach (config('missions.catalog', []) as $mission) {
            foreach ([$mission['sol_distance'] ?? null, ...array_values($mission['sol_distance_by_ring'] ?? [])] as $distance) {
                if ($distance !== null) {
                    $distances[(int) $distance] = true;
                }
            }
        }
        ksort($distances);

        $readiness = [];
        foreach (array_keys($distances) as $distance) {
            $readiness[$distance] = HangarBays::flightReadiness($shipId, $statusPoints, $distance);
        }

        return $readiness;
    }
```

- `HangarController::repair` (Z. 217-236): `$apPerClick = (int) config('game.ship_repair.ap_per_click', 1);` in AP-Prüfung (`< $apPerClick`) und `lockActionPoints($colony->id, $apPerClick)`.
- Lang: `missions.error_sp_too_low` löschen. Neu de `'error_ship_too_worn' => 'Zu verschlissen für diesen Flug: Zustand :current, Verschleiß :wear. Erst reparieren (:ap AP).'`, en `'error_ship_too_worn' => 'Too worn for this flight: condition :current, wear :wear. Repair first (:ap AP).'`.

- [ ] **Step 6: Bot auf `flightReadiness` umstellen (vier gespiegelte `20`)**

In `BotStrategy` eine gemeinsame Hilfsfunktion ergänzen (`use App\Support\HangarBays;`):

```php
    /** Same return guarantee the server's dispatch guard applies (HangarBays::flightReadiness, T30 §13.x/7). */
    private static function readyFor(object $ship, int $solDistance): bool
    {
        return HangarBays::flightReadiness((int) $ship->ship_id, (float) $ship->status_points, $solDistance)['ready'];
    }
```

- `expeditionMissionCandidate` (Z. 528-535) und `firstFlyableMission` (Z. 1666-1673): Zeile `$minSp = …` und `->where('status_points', '>=', $minSp)` löschen. Die Schiffswahl Z. 572/1704 wird zu `$ships->first(fn (object $ship): bool => in_array((int) $ship->ship_id, $shipIds, true) && self::readyFor($ship, (int) $mission['sol_distance']))`.
- `dispatchCandidate` (Z. 1593-1610): `$shipMaxStatus`/`$minSp` und die SP-Bedingung löschen. Statt `->first()` steht `->get()->first(fn (object $s): bool => self::readyFor($s, (int) config('missions.catalog.mission_recon_flight.sol_distance', 1)))`.
- `compoundsMissionCandidate` (Z. 2121-2132) und `salvageDispatchCandidate` (Z. 2150-2161): analog. Entfernung aus `config('missions.catalog.mission_salvage_sweep.sol_distance')` bzw. `config('missions.catalog.mission_harvester_salvage.sol_distance')`.

- [ ] **Step 7: Grün**

Run: `bin/phpunit --filter='ShipRepairReadinessTest|HangarServiceTest|HangarControllerTest|HangarBaysTest|HangarMissionResolutionTest'` → PASS.
Run: `bin/phpunit --testsuite=playtest --filter='BotStrategy'` → PASS. Bot-Tests, die mit SP < 5 einen fehlenden Kandidaten erwarteten, auf die Readiness-Semantik umschreiben: SP unter dem Verschleiß der jeweiligen Mission, nicht unter 5.
Run: `grep -rn "dispatch_min_sp_pct\|SHIP_MAX_STATUS\|REPAIR_SP_PER_AP\|ship_sp_too_low\|error_sp_too_low" app config lang tests resources public/js` → keine Treffer.

- [ ] **Step 8: Commit**

```bash
git add config/game.php config/missions.php app/Support/HangarBays.php app/Services/HangarService.php app/Http/Controllers/Colony/HangarController.php lang/de/missions.php lang/en/missions.php tests/Feature/Hangar/ShipRepairReadinessTest.php tests/Feature/Hangar/HangarServiceTest.php tests/Feature/Hangar/HangarControllerTest.php tests/Feature/Playtest/BotStrategy.php tests/Feature/Playtest/BotStrategyFlightReadinessTest.php
git commit -m "feat(T30): Rueckkehr-Garantie statt 25-%-Sperre, Schiffsreparatur aus Config (nur AP)"
```

---

### Task 3: Geschenk-Drohne auf dem Vorfeld

**Files:**
- Modify: `app/Services/HangarService.php` (Konstruktor Z. 46-52: `EventService` ergänzen; neue Methode `ensureGiftDrone`)
- Modify: `app/Http/Controllers/Colony/ColonyController.php` (Konstruktor Z. 36-56: `HangarService` ergänzen; Hook nach Z. 801 in `investBuilding`)
- Modify: `app/Console/Commands/ResetPlayer.php:325-332` (`placeHangar`)
- Modify: `app/Console/Commands/ColonySeedDemo.php` (`handle()` nach `assignBuildingTiles`, Z. ~105)
- Modify: `lang/de/comm_log.php:43-47`, `lang/en/comm_log.php:43-47` (`'gift_drone'`)
- Test: `tests/Feature/Hangar/GiftDroneTest.php` (neu), `tests/Feature/Onboarding/OnboardingHintServiceTest.php` (ergänzen), `tests/Feature/Console/ResetPlayerTest.php:120-133`, `tests/Feature/Console/ColonySeedDemoTest.php` (ergänzen)

**Spec-Abweichung:** §13.2 nennt als zweite Aufrufstelle `AbstractTechnologyService::levelup` (Z. 479-490). Gebäude laufen dort produktiv aber nie durch: `TechtreeController::order` lehnt `building` mit `use_colony_view` ab (Z. 449-451), und Bot wie Spieler leveln Gebäude nur über `POST /colony/building/invest`. Der Hook sitzt deshalb nur in `ColonyController::investBuilding`. Dazu kommen die Szenario-Kommandos, die Stufen direkt in die DB schreiben.

**Interfaces:**
- Consumes: `HangarBays::APRON`, `HangarService::hangarMaxLevel()`, `freeBay()` (Task 1).
- Produces: `HangarService::ensureGiftDrone(int $colonyId): bool` (true = Drohne neu angelegt), CommLog-Ereignis `hangar.gift_drone`.

- [ ] **Step 1: Failing tests**

`tests/Feature/Hangar/GiftDroneTest.php`:

```php
<?php

namespace Tests\Feature\Hangar;

use App\Models\User;
use App\Services\HangarService;
use App\Services\ProjectBonusService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T30 §13.2: hangar level 0 → 1 lands exactly one free gift drone on the apron (bay 0). */
class GiftDroneTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(100));
        config(['game.bypass.ap_checks' => true]);
        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->delete();
    }

    /** A placed hall one AP short of its next level. */
    private function hallReadyToLevel(int $level): void
    {
        $threshold = $this->app->make(ProjectBonusService::class)->effectiveApForLevelup(self::COLONY_ID, 10);
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => $threshold - 1, 'tile_x' => 5, 'tile_y' => 0]
        );
    }

    private function invest(): array
    {
        return $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->postJson(route('colony.building.invest'), ['building_id' => 44, 'instance_id' => 1])
            ->assertOk()->json();
    }

    private function ships(): \Illuminate\Support\Collection
    {
        return DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->get();
    }

    public function test_level_one_lands_one_docked_drone_on_the_apron_for_free(): void
    {
        $credits = (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('credits');
        $debt = (int) DB::table('runs')->where('id', 1)->value('nexus_debt');
        $this->hallReadyToLevel(0);

        $this->assertTrue($this->invest()['leveled_up']);

        $ships = $this->ships();
        $this->assertCount(1, $ships);
        $this->assertSame([85, 0, 'docked'], [(int) $ships[0]->ship_id, (int) $ships[0]->bay, $ships[0]->ship_state]);
        $this->assertSame($credits, (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('credits'));
        $this->assertSame($debt, (int) DB::table('runs')->where('id', 1)->value('nexus_debt'));
        $this->assertSame(1, $this->app->make(HangarService::class)->freeBay(self::COLONY_ID), 'bay 1 stays free');
        $this->assertTrue(DB::table('colony_log')->where('user', self::USER_ID)->where('event', 'hangar.gift_drone')->exists());
    }

    public function test_level_two_lands_no_second_drone(): void
    {
        $this->hallReadyToLevel(0);
        $this->invest();
        $this->hallReadyToLevel(1);
        $this->invest();

        $this->assertCount(1, $this->ships());
    }

    public function test_demolish_to_zero_and_rebuild_lands_no_second_drone(): void
    {
        $svc = $this->app->make(HangarService::class);
        $this->hallReadyToLevel(1);
        $this->assertTrue($svc->ensureGiftDrone(self::COLONY_ID));
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)
            ->update(['level' => 0, 'tile_x' => null, 'tile_y' => null]);
        $this->assertFalse($svc->ensureGiftDrone(self::COLONY_ID), 'level 0: nothing');

        $this->hallReadyToLevel(0);
        $this->invest();

        $this->assertCount(1, $this->ships());
    }

    public function test_ensure_is_idempotent(): void
    {
        $svc = $this->app->make(HangarService::class);
        $this->hallReadyToLevel(1);

        $this->assertTrue($svc->ensureGiftDrone(self::COLONY_ID));
        $this->assertFalse($svc->ensureGiftDrone(self::COLONY_ID));
        $this->assertCount(1, $this->ships());
    }
}
```

`tests/Feature/Onboarding/OnboardingHintServiceTest.php`, neue Methode neben Z. 518 (gleiche Helfer):

```php
    public function test_advisor_slot2_hint_names_hangar_as_soon_as_the_gift_drone_stands(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->upgradeCc();
        $this->placeAgrardome();
        $this->placeHangarOnly();
        $this->app->make(\App\Services\HangarService::class)->ensureGiftDrone($this->colonyId);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertSame('colony.onboarding_hint_advisor_slot2_hangar', $hint['text_key']);
    }
```

(`placeHangarOnly()` lesen. Steht die Halle dort auf Stufe 0, im Test vorher `level` = 1 setzen.)

`tests/Feature/Console/ResetPlayerTest.php`, in `test_phase2_scenario_path_hangar_places_hangar_and_pilot` (Z. 120-133) ergänzen:

```php
        $this->assertSame(1, DB::table('colony_ships')
            ->where('colony_id', $colony->id)->where('bay', 0)->where('ship_id', 85)->count()); // gift drone
```

`tests/Feature/Console/ColonySeedDemoTest.php`, neue Methode:

```php
    public function test_hangar_path_lands_the_gift_drone_on_the_apron(): void
    {
        $this->artisan('colony:seed-demo', ['colony_id' => self::COLONY_ID, '--path' => 'hangar'])->assertExitCode(0);

        $this->assertSame(85, (int) DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 0)->value('ship_id'));
    }
```

- [ ] **Step 2: Rot bestätigen**

Run: `bin/phpunit --filter='GiftDroneTest|advisor_slot2_hint_names_hangar_as_soon|ResetPlayerTest|ColonySeedDemoTest'`
Expected: FAIL (`ensureGiftDrone` undefiniert, Drohne fehlt).

- [ ] **Step 3: Implementierung**

`HangarService`: Konstruktor um `private readonly EventService $eventService` erweitern (`use App\Services\EventService` steht im selben Namespace). Neue Methode:

```php
    /**
     * T30 §13.2: the gift drone on the apron (bay 0). Idempotent — only when the hall
     * stands on level >= 1 and the apron is empty. Ships on the apron are never deleted
     * (only 'pending' ships decay, GameTick::processHangarDeliveries), so a hall
     * demolished to 0 and rebuilt never yields a second one. No Credits, debt or trust.
     */
    public function ensureGiftDrone(int $colonyId): bool
    {
        if ($this->hangarMaxLevel($colonyId) < 1) {
            return false;
        }

        return DB::transaction(function () use ($colonyId): bool {
            if (DB::table('colony_ships')->where('colony_id', $colonyId)->where('bay', HangarBays::APRON)->lockForUpdate()->exists()) {
                return false;
            }

            $tick = $this->tickService->getTickCount();
            DB::table('colony_ships')->insert([
                'colony_id' => $colonyId,
                'ship_id' => (int) config('ships.drone.id'),
                'bay' => HangarBays::APRON,
                'ship_state' => 'docked',
                'level' => 0,
                'status_points' => HangarBays::maxStatus(),
                'ap_spend' => 0,
                'deliver_at_tick' => $tick,
                'pending_until_tick' => null,
            ]);

            $this->eventService->createEvent([
                'user' => (int) DB::table('glx_colonies')->where('id', $colonyId)->value('user_id'),
                'tick' => $tick,
                'event' => 'hangar.gift_drone',
                'area' => 'colony',
                'parameters' => json_encode(['colony_id' => $colonyId]),
            ]);

            return true;
        });
    }
```

`ColonyController`: Konstruktor um `private readonly HangarService $hangarService` erweitern (`use App\Services\HangarService;`). In `investBuilding` direkt nach dem `if ($newApSpend >= $effectiveApForLevelup) { … }`-Block (nach Z. 801):

```php
        // T30 §13.2: reaching hangar level 1 lands the gift drone (idempotent).
        if ($leveledUp && $buildingId === BuildingId::Hangar->value) {
            $this->hangarService->ensureGiftDrone($colony->id);
        }
```

`ResetPlayer::placeHangar` (Z. 325-332), nach dem Insert:

```php
        if ($level >= 1) {
            app(HangarService::class)->ensureGiftDrone($colony->id); // T30 §13.2: gift drone on the apron
        }
```

(`use App\Services\HangarService;` ergänzen. In den Szenario-Docblocks Z. 571/603/692 den Satz „Geschenk-Drohne auf dem Vorfeld“ ergänzen.)

`ColonySeedDemo::handle()`, nach `assignBuildingTiles(...)`:

```php
        if (in_array($path, ['all', 'hangar'], true)) {
            app(HangarService::class)->ensureGiftDrone($colonyId);
            $this->info('  ✓ Gift drone on the apron');
        }
```

`lang/de/comm_log.php` / `lang/en/comm_log.php`, im `'hangar' => [...]`-Block: `'gift_drone' => 'Geschenk-Drohne gelandet',` / `'gift_drone' => 'Gift drone landed',`.

- [ ] **Step 4: Grün**

Run: `bin/phpunit --filter='GiftDroneTest|OnboardingHintServiceTest|ResetPlayerTest|ColonySeedDemoTest|BuildingInvestTest'`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/HangarService.php app/Http/Controllers/Colony/ColonyController.php app/Console/Commands/ResetPlayer.php app/Console/Commands/ColonySeedDemo.php lang/de/comm_log.php lang/en/comm_log.php tests/Feature/Hangar/GiftDroneTest.php tests/Feature/Onboarding/OnboardingHintServiceTest.php tests/Feature/Console/ResetPlayerTest.php tests/Feature/Console/ColonySeedDemoTest.php
git commit -m "feat(T30): Geschenk-Drohne auf dem Vorfeld bei Hangar Stufe 1"
```

---

### Task 4: Katalog-Bereinigung + Config `find_carry` / `finds.flight`

**Files:**
- Modify: `config/missions.php:1-26` (Kopfkommentar), `:59-75` (Deep Survey und Prospektion löschen), `:102-109` (Versorgungsfahrt)
- Modify: `config/ships.php` (Feld `find_carry` je Schiff, Kopfkommentar)
- Modify: `config/game.php:50-62` (`finds.flight`)
- Modify: `app/Services/HangarService.php:809-863` (`signal_tile`-Zweig), `:982-990` (`pickableTargets`)
- Modify: `app/Console/Commands/GameTick.php:519-521, 546-547, 591-604` (`deep_scan` und `deepScanTarget` entfernen)
- Modify: `app/Support/MissionReward.php:23-26` (`deep_scan` raus)
- Modify: `lang/de/missions.php:12-16, 98`, `lang/en/missions.php` (gleiche Schlüssel)
- Modify: `tests/Feature/Playtest/BotStrategy.php:55-58` (Prospektion aus `REGOLITH_MISSIONS`)
- Modify: `tests/Feature/Hangar/HangarServiceTest.php:748-807, 897-922, 1139-1180`, `tests/Unit/MissionRewardTest.php:63, 92`, `tests/Feature/Playtest/BotStrategyMissionChoiceTest.php:64-85`
- Test: `tests/Unit/FindFlightConfigTest.php` (neu)

**Interfaces:**
- Produces: `config('ships.<key>.find_carry')` (`list<string>`), `config('game.finds.flight.yield_factor')` (float 1.5), `config('game.finds.flight.min_hangar_level')` (`array<string,int>`). Der Katalog enthält weder `mission_prospecting_flight` noch `mission_deep_survey`. `mission_supply_run.reward === ['organics' => 10]`.

- [ ] **Step 1: Failing test** `tests/Unit/FindFlightConfigTest.php`

```php
<?php

namespace Tests\Unit;

use App\Support\HangarBays;
use Tests\TestCase;

/** T30 §13.3: carry matrix, flight yield and the catalog clean-up (E5, E8). */
class FindFlightConfigTest extends TestCase
{
    public function test_carry_matrix(): void
    {
        $this->assertSame(['find_small', 'find_medium'], config('ships.drone.find_carry'));
        $this->assertSame(['find_small', 'find_medium', 'find_large'], config('ships.freighter.find_carry'));
        $this->assertSame([], config('ships.corvette.find_carry'));
        $this->assertSame(1.5, config('game.finds.flight.yield_factor'));
    }

    /** min_hangar_level must equal the smallest class that carries the type — the lock reason must say what the guard checks. */
    public function test_min_hangar_level_matches_the_smallest_carrying_ship_class(): void
    {
        foreach (['find_small', 'find_medium', 'find_large'] as $type) {
            $levels = [];
            foreach (config('ships') as $cfg) {
                if (in_array($type, $cfg['find_carry'] ?? [], true)) {
                    $levels[] = HangarBays::requiredLevel((int) $cfg['id']);
                }
            }
            $this->assertNotEmpty($levels, "{$type}: no ship carries it");
            $this->assertSame(min($levels), config("game.finds.flight.min_hangar_level.{$type}"), $type);
        }
    }

    public function test_catalog_without_prospecting_survey_and_regolith_supply(): void
    {
        $catalog = config('missions.catalog');
        $this->assertArrayNotHasKey('mission_prospecting_flight', $catalog);
        $this->assertArrayNotHasKey('mission_deep_survey', $catalog);
        $this->assertSame(['organics' => 10], $catalog['mission_supply_run']['reward']);
        foreach ($catalog as $key => $mission) {
            $this->assertNotSame('signal_tile', $mission['target_type'] ?? null, $key);
            $this->assertArrayNotHasKey('deep_scan', $mission['reward'], $key);
        }
    }
}
```

- [ ] **Step 2: Rot bestätigen.** Run: `bin/phpunit tests/Unit/FindFlightConfigTest.php` → FAIL.

- [ ] **Step 3: Config**

`config/ships.php`: im Kopfkommentar `find_carry — find types this ship can bring home on mission_find_salvage (T30 §13.3, E5)` ergänzen. Je Schiff setzen: Drohne `'find_carry' => ['find_small', 'find_medium'],`, Korvette `'find_carry' => [],`, Frachter `'find_carry' => ['find_small', 'find_medium', 'find_large'],`.

`config/game.php` in `'finds'` nach `'max_open_projects' => 2,`:

```php
        // T30 §13.3: salvage flight (mission_find_salvage). yield = round(rg × yield_factor).
        // min_hangar_level = lowest hall level whose ship class carries the find (drone 1,
        // freighter 2) — shown as the lock reason on the find card; must match
        // ships.*.find_carry (tests/Unit/FindFlightConfigTest).
        'flight' => [
            'yield_factor' => 1.5,
            'min_hangar_level' => ['find_small' => 1, 'find_medium' => 1, 'find_large' => 2],
        ],
```

`config/missions.php`: Die Einträge `mission_deep_survey` und `mission_prospecting_flight` (Z. 59-75) löschen. `mission_supply_run` bekommt `'reward' => ['organics' => 10], // T30 §13 E8: no Regolith (F2)`. Im Kopfkommentar wird `target — 'signal_tile' | 'ruin_tile'` zu `'ruin_tile' | 'find_tile'`, die Zeile `(deep_survey: the signal tile to scan; ...)` zu `(find_salvage: the scanned find; data_sweep: the knowledge to invest in)`.

- [ ] **Step 4: Code bereinigen**

- `HangarService::validateTarget` (Z. 809-863): `signal_tile` aus Docblock und `match` streichen. `pickableTargets` Z. 986-990: `if ($targetType === 'signal_tile') {...} else { // ruin_tile` wird zu `$query->where('is_deep_scanned', 1)->where('event_type', 'event_ruin');`. Den Zweig `find_tile` fügt Task 5 hinzu.
- `GameTick::payMissionRewards`: Kommentar Z. 519-521 ohne `deep_scan`, Zweig Z. 546-547 und Methode `deepScanTarget` (Z. 591-604) löschen.
- `MissionReward::FIXED_PARTS`: `'deep_scan' => 'reward_deep_scan',` entfernen.
- Lang de/en `missions.php`: `mission_deep_survey_name/_desc`, `mission_prospecting_flight_name/_desc`, `reward_deep_scan` löschen.
- `BotStrategy::REGOLITH_MISSIONS` (Z. 55-58): Eintrag `mission_prospecting_flight` entfernen. Die Regel selbst ersetzt Task 8.

- [ ] **Step 5: Bestehende Tests nachziehen** (Ersatzmission `mission_long_range_expedition`: Drohne, Gate `cartography` 3, `sol_distance` 5, ohne Ziel)

- `HangarServiceTest::test_dispatch_ship_throws_for_missing_knowledge_gate` / `…_succeeds_with_knowledge_gate_met` (Z. 748-769): Mission `mission_long_range_expedition`, Kenntnis `setKnowledgeLevel('cartography', 3)`.
- `test_dispatch_ship_throws_for_missing_target` / `…_invalid_target` / `…_succeeds_with_valid_signal_tile_target` (Z. 771-801): auf `mission_ruin_expedition` umstellen. Dafür eine Halle Stufe 3 mit Korvette in Bucht 3 anlegen, Ziel `insertTile(3, -3, 'event_ruin', deepScanned: true)`. Den Erfolgsfall in `…_succeeds_with_valid_ruin_tile_target` umbenennen und im Kopf-Docblock die Szenarioliste anpassen.
- `test_organika_cost_scales_down…` (Z. 897-909): `mission_long_range_expedition`, `cartography` 3 → 15, 4 → 10, 5 → 5 (3/2/1 je Sol × 5). `…respects_floor` (Z. 911-917): `cartography` 9 → 5.
- `test_success_chance_adds_knowledge_bonus_above_gate` (Z. 1139-1154): `cartography` Lv5 mit `mission_long_range_expedition` ergibt +0,06. `…is_capped` (Z. 1156-1180): `cartography` Lv7, Rechnung im Kommentar anpassen (4 Stufen über dem Gate).
- `tests/Unit/MissionRewardTest.php`: Z. 63 (Assertion `deep_scan`) löschen, Z. 92 `! in_array($name, ['deep_scan'], true)` → `true`.
- `BotStrategyMissionChoiceTest`: `test_drone_flies_prospecting_when_scarce_and_geology_is_known` und `test_drone_without_geology_gets_no_regolith_mission` (Z. 64-85) löschen.

- [ ] **Step 6: Grün**

Run: `bin/phpunit --filter='FindFlightConfigTest|FindsConfigTest|HangarServiceTest|MissionRewardTest|HangarMissionResolutionTest'` → PASS.
Run: `bin/phpunit --testsuite=playtest --filter=BotStrategyMissionChoiceTest` → PASS.
Run: `grep -rn "prospecting\|deep_survey\|signal_tile\|'deep_scan'" app config lang resources public/js tests` → nur `deep_scan_signal_tile` (Bot-Regelname für den Spieler-Scan, bleibt) und `colony.deep_scan` (UI-Label).

- [ ] **Step 7: Commit**

```bash
git add config/missions.php config/ships.php config/game.php app/Services/HangarService.php app/Console/Commands/GameTick.php app/Support/MissionReward.php lang/de/missions.php lang/en/missions.php tests/Unit/FindFlightConfigTest.php tests/Unit/MissionRewardTest.php tests/Feature/Hangar/HangarServiceTest.php tests/Feature/Playtest/BotStrategy.php tests/Feature/Playtest/BotStrategyMissionChoiceTest.php
git commit -m "feat(T30): Katalog ohne Prospektion/Signalvermessung, Versorgungsfahrt ohne Rg, find_carry + finds.flight"
```

---

### Task 5: Bergungsflug `mission_find_salvage`

**Files:**
- Create: `app/Services/FindFlightService.php`
- Modify: `config/missions.php` (neuer Eintrag nach `mission_recon_flight`)
- Modify: `app/Services/HangarService.php` (Konstruktor; `dispatchShip` Z. 715-724; `organikaCostFor` Z. 774-791; `getMissionCatalogFor` Z. 870-958; `pickableTargets` Z. 963-1008; neu `assertFindFlyable`, `completeFindFlight`)
- Modify: `app/Services/ColonyTileService.php` (Konstruktor Z. 33-37; `salvageFind` Z. 194-206 → `completeFind`; neu `completeFind`, `findFlightReturnSol`, Konstante `FIND_TYPES`)
- Modify: `app/Console/Commands/GameTick.php:432-468` (`roll`), `:503-565` (`find_salvage`, Parameter `$shipId`)
- Modify: `app/Support/MissionReward.php` (`find_salvage` → `reward_find_salvage`)
- Modify: `lang/de/missions.php`, `lang/en/missions.php`
- Test: `tests/Feature/Hangar/FindFlightTest.php` (neu)

**Spec-Abweichung (verifiziert):** §13.3 nimmt an, dass `GameTick::processHangarMissions` Missionen unabhängig von der aktuellen Hallenstufe auflöst. Das stimmt: Der Join läuft nur über Schiff ↔ Mission (Z. 373-380), es gibt keinen Gebäude-Join. Ein Fix ist nicht nötig, der Test sichert das Verhalten ab.

**Interfaces:**
- Consumes: `HangarBays` (Task 1), `finds.flight`, `find_carry` (Task 4).
- Produces: `FindFlightService::MISSION_KEY = 'mission_find_salvage'`, `solDistance(int $ring): int`, `costs(int $colonyId, int $ring): array{sol_distance:int, nav_ap_base:int, nav_ap:int, organika:int, duration:int}`, `findFlightYield(int $colonyId, string $findType): array{base:int, flight_bonus:int, total:int}`, `canCarry(?string $shipKey, string $findType): bool`, `minHangarLevel(string $findType): int`.
- Produces: `ColonyTileService::FIND_TYPES = ['find_small','find_medium','find_large']`, `completeFind(ColonyTile $tile, int $rg): void`, `findFlightReturnSol(int $colonyId, int $q, int $r): ?int`.
- Produces: `HangarService::organikaCostFor(int $colonyId, array $mission, ?int $solDistance = null): int`, `completeFindFlight(int $colonyId, ?array $target, int $shipId): array{find_type:?string, find_regolith:int, flight_bonus:int, find_missing?:bool}`. Ein Katalogeintrag mit `target_type = find_tile` enthält `targets: list<array{q,r,ring,find_type,sol_distance,nav_ap,nav_ap_base,organika,duration,return_sol,carry:array<string,bool>,yield:array{base,flight_bonus,total}}>` und `roll: bool`.
- Produces: Fehlercodes `find_not_scanned`, `find_too_large`, `find_in_flight`, `find_salvage_in_progress` (Lang `missions.error_*`). Die Details von `hangar.mission_completed` enthalten `rewards.find_type`, `rewards.find_regolith`, `rewards.flight_bonus` (bewusst nicht unter `regolith`).

- [ ] **Step 1: Failing tests** `tests/Feature/Hangar/FindFlightTest.php`

```php
<?php

namespace Tests\Feature\Hangar;

use App\Exceptions\GameRuleException;
use App\Models\User;
use App\Services\AdvisorService;
use App\Services\FindFlightService;
use App\Services\HangarService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesForeignColony;
use Tests\TestCase;

/** T30 §13.3: salvage flight to a scanned find — distance from ring, no roll, yield × 1.5. */
class FindFlightTest extends TestCase
{
    use CreatesForeignColony;
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const DRONE = 85;

    private const FREIGHTER = 47;

    private const CORVETTE = 37;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(100));
        config(['game.bypass.ap_checks' => false, 'game.bypass.resource_costs' => false]);
        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->delete();
        DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->update(['amount' => 50]);
        $this->hall(3);
    }

    private function hall(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => $level > 0 ? 5 : null, 'tile_y' => $level > 0 ? 5 : null]
        );
    }

    private function ship(int $shipId, int $bay, float $sp = 20): void
    {
        DB::table('colony_ships')->insert([
            'colony_id' => self::COLONY_ID, 'ship_id' => $shipId, 'bay' => $bay,
            'ship_state' => 'docked', 'level' => 0, 'status_points' => $sp, 'ap_spend' => 0,
        ]);
    }

    private function find(int $q, int $ring, string $type, bool $scanned = true, int $spent = 0): void
    {
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => $q, 'r' => 0],
            ['ring' => $ring, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 0,
                'is_deep_scanned' => $scanned ? 1 : 0, 'event_type' => $type, 'salvage_ap_spent' => $spent, 'salvage_tick' => null]
        );
    }

    private function fly(int $bay, int $q): void
    {
        $this->app->make(HangarService::class)->dispatchShip(self::COLONY_ID, $bay, 'mission_find_salvage', ['q' => $q, 'r' => 0], 'normal');
    }

    private function assertRejected(string $code, callable $fn): void
    {
        try {
            $fn();
            $this->fail("expected {$code}");
        } catch (GameRuleException $e) {
            $this->assertSame($code, $e->errorCode);
        }
    }

    private function ap(): int
    {
        return $this->app->make(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID);
    }

    private function organics(): int
    {
        return (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->value('amount');
    }

    private function regolith(): int
    {
        return (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 3)->value('amount');
    }

    public function test_distance_costs_and_yield_come_from_config(): void
    {
        $svc = $this->app->make(FindFlightService::class);
        $this->assertSame([1, 2], [$svc->solDistance(2), $svc->solDistance(3)]);
        $this->assertSame(['base' => 10, 'flight_bonus' => 5, 'total' => 15], $svc->findFlightYield(self::COLONY_ID, 'find_medium'));
        $this->assertSame(27, $svc->findFlightYield(self::COLONY_ID, 'find_large')['total']);
        $ring3 = $svc->costs(self::COLONY_ID, 3);
        $this->assertSame([2, 4, 6, 4], [$ring3['sol_distance'], $ring3['nav_ap_base'], $ring3['organika'], $ring3['duration']]);
        // Same Organika formula as every other mission (preview = charge).
        $this->assertSame(
            $ring3['organika'],
            $this->app->make(HangarService::class)->organikaCostFor(self::COLONY_ID, config('missions.catalog.mission_find_salvage'), 2)
        );
    }

    public function test_dispatch_charges_ring_costs_and_stores_distance(): void
    {
        $this->ship(self::DRONE, 0);
        $this->find(3, 3, 'find_medium');
        [$ap, $or] = [$this->ap(), $this->organics()];

        $this->fly(0, 3);

        $cost = $this->app->make(FindFlightService::class)->costs(self::COLONY_ID, 3);
        $this->assertSame($ap - $cost['nav_ap'], $this->ap());
        $this->assertSame($or - $cost['organika'], $this->organics());
        $mission = DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->first();
        $this->assertSame([2, '{"q":3,"r":0}', 0], [(int) $mission->sol_distance, $mission->target, (int) $mission->bay]);
    }

    public function test_target_rules(): void
    {
        $this->ship(self::DRONE, 0);
        $this->ship(self::CORVETTE, 3);
        $this->find(2, 2, 'find_small', scanned: false);
        $this->assertRejected('find_not_scanned', fn () => $this->fly(0, 2));
        $this->find(2, 2, 'find_false');
        $this->assertRejected('invalid_target', fn () => $this->fly(0, 2));
        $this->find(2, 2, 'find_large');
        $this->assertRejected('find_too_large', fn () => $this->fly(0, 2));
        $this->find(2, 2, 'find_small', spent: 4);
        $this->assertRejected('find_salvage_in_progress', fn () => $this->fly(0, 2));
        $this->find(2, 2, 'find_small');
        $this->assertRejected('wrong_ship_type', fn () => $this->fly(3, 2)); // corvette carries nothing
    }

    public function test_second_dispatch_to_the_same_find_is_rejected_without_cost(): void
    {
        $this->ship(self::DRONE, 0);
        $this->ship(self::FREIGHTER, 2);
        $this->find(2, 2, 'find_small');
        $this->fly(0, 2);
        [$ap, $or] = [$this->ap(), $this->organics()];

        $this->assertRejected('find_in_flight', fn () => $this->fly(2, 2));
        $this->assertSame([$ap, $or], [$this->ap(), $this->organics()]);
    }

    public function test_dispatch_to_a_foreign_colony_find_is_invalid(): void
    {
        $foreign = $this->createForeignColony([25 => 1]);
        DB::table('colony_tiles')->insert(['colony_id' => $foreign['colony_id'], 'q' => 9, 'r' => 9, 'ring' => 2,
            'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 1, 'event_type' => 'find_small']);
        $this->ship(self::DRONE, 0);

        $this->assertRejected('invalid_target', fn () => $this->app->make(HangarService::class)
            ->dispatchShip(self::COLONY_ID, 0, 'mission_find_salvage', ['q' => 9, 'r' => 9], 'normal'));
        $this->assertSame('find_small', DB::table('colony_tiles')->where('colony_id', $foreign['colony_id'])->value('event_type'));
    }

    public function test_return_pays_round_rg_times_factor_without_roll_and_frees_the_tile(): void
    {
        config(['game.missions.difficulty.base_chance' => ['easy' => 0.0, 'normal' => 0.0, 'hard' => 0.0]]);
        $this->ship(self::DRONE, 0);
        $this->find(2, 2, 'find_medium');
        $rg = $this->regolith();
        $this->fly(0, 2);

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 102]);

        $this->assertSame($rg + 15, $this->regolith());
        $tile = DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->where('q', 2)->where('r', 0)->first();
        $this->assertSame([null, 0, 0], [$tile->event_type, (int) $tile->is_deep_scanned, (int) $tile->salvage_ap_spent]);
        $mission = DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->first();
        $this->assertSame(['completed', 1, 'normal'], [$mission->state, (int) $mission->succeeded, $mission->difficulty]);
        $this->assertContains('normal', app(\App\Services\RunTaskCatalog::class)->countedDifficulties(), 'E9: counts for task_expedition_coverage');
        $rewards = json_decode(DB::table('colony_log')->where('event', 'hangar.mission_completed')->where('tick', 102)->value('parameters'), true)['rewards'];
        $this->assertSame(['find_type' => 'find_medium', 'find_regolith' => 15, 'flight_bonus' => 5], $rewards);
    }

    public function test_worn_drone_is_refused_for_ring_3_and_returns_with_one_sp_after_repair(): void
    {
        $this->ship(self::DRONE, 0, 6.0); // passed the old 25 % floor, aborted in the last tick
        $this->find(3, 3, 'find_medium');
        [$ap, $or] = [$this->ap(), $this->organics()];
        $this->assertRejected('ship_too_worn', fn () => $this->fly(0, 3));
        $this->assertSame([$ap, $or], [$this->ap(), $this->organics()]);

        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 0)->update(['status_points' => 7.0]);
        $rg = $this->regolith();
        $this->fly(0, 3);
        foreach ([101, 102, 103, 104] as $tick) { // 4 Sols in flight × 1.5 wear
            Artisan::call('game:tick', ['--run' => 1, '--tick' => $tick]);
        }

        $this->assertSame(1.0, (float) DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 0)->value('status_points'));
        $this->assertSame($rg + 15, $this->regolith());
    }

    public function test_flight_still_pays_after_the_hall_was_demolished(): void
    {
        $this->ship(self::DRONE, 0);
        $this->find(2, 2, 'find_small');
        $rg = $this->regolith();
        $this->fly(0, 2);
        $this->hall(0);

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 102]);

        $this->assertSame($rg + 6, $this->regolith());
    }

    public function test_recall_and_abort_free_the_target_without_yield(): void
    {
        $this->ship(self::DRONE, 0);
        $this->find(2, 2, 'find_small');
        $rg = $this->regolith();
        $this->fly(0, 2);
        $this->app->make(HangarService::class)->recallShip(self::COLONY_ID, 0);
        $this->assertSame($rg, $this->regolith());
        $this->fly(0, 2); // target free again

        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('bay', 0)->update(['status_points' => 1]);
        Artisan::call('game:tick', ['--run' => 1, '--tick' => 101]);

        $this->assertSame('aborted', DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->orderByDesc('id')->value('state'));
        $this->assertSame($rg, $this->regolith());
        $this->assertSame('find_small', DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->where('q', 2)->value('event_type'));
    }

    public function test_catalog_lists_scanned_finds_with_costs_carry_and_yield_and_no_roll(): void
    {
        $this->ship(self::DRONE, 0);
        $this->find(2, 2, 'find_small');
        $this->find(3, 3, 'find_large');
        $this->find(4, 3, 'find_medium', scanned: false);

        $entry = collect($this->app->make(HangarService::class)->getMissionCatalogFor(self::COLONY_ID))->firstWhere('key', 'mission_find_salvage');

        $this->assertFalse($entry['roll']);
        $this->assertSame([100], array_column($entry['difficulty_options'], 'chance_pct'));
        $this->assertSame([[2, 0], [3, 0]], array_map(fn ($t) => [$t['q'], $t['r']], $entry['targets']));
        $large = $entry['targets'][1];
        $this->assertSame(['drone' => false, 'freighter' => true], $large['carry']);
        $this->assertSame(27, $large['yield']['total']);
        $this->assertSame(104, $large['return_sol']);
    }

    public function test_dispatch_route_answers_with_resourcebar_sync(): void
    {
        $this->ship(self::DRONE, 0);
        $this->find(2, 2, 'find_small');

        $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->postJson(route('colony.hangar.dispatch', ['bay' => 0]), ['mission_key' => 'mission_find_salvage', 'difficulty' => 'normal', 'target' => ['q' => 2, 'r' => 0]])
            ->assertOk()->assertJsonStructure(['ok', 'slot', 'apAvailable', 'organika']);
    }
}
```

- [ ] **Step 2: Rot bestätigen.** Run: `bin/phpunit tests/Feature/Hangar/FindFlightTest.php` → FAIL (`FindFlightService` fehlt, Mission unbekannt).

- [ ] **Step 3: Config-Eintrag** in `config/missions.php` nach `mission_recon_flight`:

```php
        // T30 §13.3: salvage flight to a SCANNED real find (no remote scan, E4). Distance
        // comes from the target's ring; no success roll (the find is known), wear only.
        // Yield = round(find rg × game.finds.flight.yield_factor), carry per ships.*.find_carry.
        // Counts for task_expedition_coverage (difficulty normal), not for finds.max_open_projects.
        'mission_find_salvage' => [
            'ships' => ['drone', 'freighter'],
            'sol_distance_by_ring' => [2 => 1, 3 => 2], // fallback max(1, ring − 1)
            'requires' => ['target' => 'find_tile'],
            'target_type' => 'find_tile',
            'reward' => ['find_salvage' => true],
            'roll' => false,
            'repeatable' => true,
            'difficulties' => ['normal'],
        ],
```

- [ ] **Step 4: `app/Services/FindFlightService.php`**

```php
<?php

namespace App\Services;

/**
 * T30 §13.3 salvage flight: distance, costs, carry and yield — one source for the
 * dispatch guard, the tick payout, the hangar target list and the find card.
 * Yield lines are additive (base + flight_bonus) so later modifiers (§13.6 voucher)
 * join as further lines instead of changing the factor.
 */
class FindFlightService
{
    public const MISSION_KEY = 'mission_find_salvage';

    public function __construct(private readonly ProjectBonusService $projectBonusService) {}

    public function solDistance(int $ring): int
    {
        $byRing = config('missions.catalog.'.self::MISSION_KEY.'.sol_distance_by_ring', []);

        return (int) ($byRing[$ring] ?? max(1, $ring - 1));
    }

    /** @return array{sol_distance:int, nav_ap_base:int, nav_ap:int, organika:int, duration:int} */
    public function costs(int $colonyId, int $ring): array
    {
        $distance = $this->solDistance($ring);
        $navBase = $distance * (int) config('missions.nav_ap_per_sol', 2);

        return [
            'sol_distance' => $distance,
            'nav_ap_base' => $navBase,
            'nav_ap' => $this->projectBonusService->effectiveNavigationApCost($colonyId, $navBase),
            // Ungated mission without extra_cost: the generic HangarService::organikaCostFor()
            // formula reduces to this — pinned by FindFlightTest (preview = charge).
            'organika' => $distance * (int) config('missions.organika_per_sol', 3),
            'duration' => 2 * $distance,
        ];
    }

    /** @return array{base:int, flight_bonus:int, total:int} */
    public function findFlightYield(int $colonyId, string $findType): array
    {
        $base = (int) (config("game.finds.types.{$findType}.rg") ?? 0);
        $total = (int) round($base * (float) config('game.finds.flight.yield_factor', 1.0));

        return ['base' => $base, 'flight_bonus' => $total - $base, 'total' => $total];
    }

    public function canCarry(?string $shipKey, string $findType): bool
    {
        return $shipKey !== null && in_array($findType, config("ships.{$shipKey}.find_carry", []), true);
    }

    public function minHangarLevel(string $findType): int
    {
        return (int) config("game.finds.flight.min_hangar_level.{$findType}", 1);
    }
}
```

- [ ] **Step 5: `ColonyTileService`**

Konstruktor um `private readonly FindFlightService $findFlightService` erweitern. Nach `deepScanCost` ergänzen:

```php
    public const FIND_TYPES = ['find_small', 'find_medium', 'find_large'];
```

In `salvageFind` den Abschluss Z. 195-206 ersetzen:

```php
            if ($completed) {
                $rg = (int) $def['rg'];
                $this->completeFind($tile, $rg);
            } else {
                $tile->salvage_ap_spent = $spent;
                $tile->salvage_tick = $tick;
                $tile->save();
            }
```

Neue Methoden:

```php
    /** Shared completion of self-salvage and salvage flight (§13.3): credit Rg, free the tile. */
    public function completeFind(ColonyTile $tile, int $rg): void
    {
        if ($rg > 0) {
            DB::table('colony_resources')->where('colony_id', $tile->colony_id)->where('resource_id', 3)->increment('amount', $rg);
        }
        $tile->event_type = null;
        $tile->is_deep_scanned = false;
        $tile->salvage_ap_spent = 0;
        $tile->salvage_tick = null;
        $tile->save();
    }

    /** Return Sol of the active salvage flight to this tile, null if none (reservation = state active only). */
    public function findFlightReturnSol(int $colonyId, int $q, int $r): ?int
    {
        $mission = DB::table('colony_hangar_missions')
            ->where('colony_id', $colonyId)
            ->where('destination', FindFlightService::MISSION_KEY)
            ->where('state', 'active')
            ->where('target', json_encode(['q' => $q, 'r' => $r]))
            ->first(['dispatch_tick', 'sol_distance']);

        return $mission === null ? null : (int) $mission->dispatch_tick + 2 * (int) $mission->sol_distance;
    }
```

- [ ] **Step 6: `HangarService`**

Konstruktor um `private readonly FindFlightService $findFlightService` und `private readonly ColonyTileService $colonyTileService` erweitern (kein Zyklus: `ColonyTileService` injiziert `HangarService` nicht).

`organikaCostFor(int $colonyId, array $mission, ?int $solDistance = null)`: Z. 787 wird zu `$cost = ($solDistance ?? (int) $mission['sol_distance']) * $perSol;`.

`dispatchShip`: Z. 715-724 ersetzen:

```php
            $targetJson = null;
            $solDistance = (int) ($mission['sol_distance'] ?? 0);
            $targetType = $mission['target_type'] ?? null;
            if ($targetType === 'find_tile') {
                $tile = DB::table('colony_tiles')->where('colony_id', $colonyId)
                    ->where('q', (int) ($target['q'] ?? PHP_INT_MIN))->where('r', (int) ($target['r'] ?? PHP_INT_MIN))
                    ->lockForUpdate()->first();
                $this->assertFindFlyable($colonyId, $tile, $shipKey);
                $targetJson = json_encode(['q' => (int) $tile->q, 'r' => (int) $tile->r]);
                $solDistance = $this->findFlightService->solDistance((int) $tile->ring);
            } elseif ($targetType !== null) {
                $targetJson = $this->validateTarget($colonyId, $missionKey, $targetType, $target);
            }

            $baseNavApCost = $solDistance * (int) config('missions.nav_ap_per_sol', 2);
            $navApCost = $this->projectBonusService->effectiveNavigationApCost($colonyId, $baseNavApCost);
            $organikaCost = $this->organikaCostFor($colonyId, $mission, $solDistance);
```

(Beim Insert bleibt `'sol_distance' => $solDistance`.) **Wichtig:** Der Readiness-Guard aus Task 2 (`HangarBays::flightReadiness(...)`, Fehler `ship_too_worn`) steht weiter **hinter** diesem Block. Er prüft also die Ring-Entfernung des Ziels und nicht `0` aus dem fehlenden `sol_distance`-Schlüssel. Neue private Methode:

```php
    /**
     * Tile-level gates of a salvage flight (§13.3). The tile row is locked by the caller,
     * so a second dispatch to the same find waits and then sees the active mission.
     */
    private function assertFindFlyable(int $colonyId, ?object $tile, ?string $shipKey): void
    {
        if ($tile === null || $tile->event_type === null) {
            throw $this->ruleError('invalid_target', 'missions.error_invalid_target');
        }
        if (! (bool) $tile->is_deep_scanned) {
            throw $this->ruleError('find_not_scanned', 'missions.error_find_not_scanned');
        }
        if (! in_array($tile->event_type, ColonyTileService::FIND_TYPES, true)) {
            throw $this->ruleError('invalid_target', 'missions.error_invalid_target');
        }
        if ((int) $tile->salvage_ap_spent > 0) {
            throw $this->ruleError('find_salvage_in_progress', 'missions.error_find_salvage_in_progress');
        }
        if ($this->colonyTileService->findFlightReturnSol($colonyId, (int) $tile->q, (int) $tile->r) !== null) {
            throw $this->ruleError('find_in_flight', 'missions.error_find_in_flight');
        }
        if (! $this->findFlightService->canCarry($shipKey, $tile->event_type)) {
            throw $this->ruleError('find_too_large', 'missions.error_find_too_large');
        }
    }
```

`getMissionCatalogFor`: Z. 884 wird zu `$dist = (int) ($mission['sol_distance'] ?? 0);`. Nach `$targets = $this->pickableTargets(...)`:

```php
                if ($targetType === 'find_tile') {
                    $dist = $targets === [] ? $this->findFlightService->solDistance(2) : min(array_column($targets, 'sol_distance'));
                }
```

Erst danach `$navApCost` und `$organikaCost` berechnen (bisherige Zeilen 885-886 hinter den Ziel-Block verschieben, `organikaCostFor($colonyId, $mission, $dist)`). Im Schwierigkeits-Loop: `'chance_pct' => ($mission['roll'] ?? true) === false ? 100 : (int) round(...)` und `'reward_multiplier' => ($mission['roll'] ?? true) === false ? 1.0 : (float) config(...)`. Im Eintrag `'roll' => ($mission['roll'] ?? true) !== false,` ergänzen.

`pickableTargets`: vor dem Kachel-Query einfügen:

```php
        if ($targetType === 'find_tile') {
            $tick = $this->tickService->getTickCount();
            $mission = config('missions.catalog.'.FindFlightService::MISSION_KEY);

            return DB::table('colony_tiles')
                ->where('colony_id', $colonyId)->where('is_deep_scanned', 1)
                ->whereIn('event_type', ColonyTileService::FIND_TYPES)->where('salvage_ap_spent', 0)
                ->orderBy('ring')->orderBy('q')->orderBy('r')
                ->get()
                ->reject(fn ($t) => $this->colonyTileService->findFlightReturnSol($colonyId, (int) $t->q, (int) $t->r) !== null)
                ->map(function ($t) use ($colonyId, $tick, $mission) {
                    $costs = $this->findFlightService->costs($colonyId, (int) $t->ring);
                    $carry = [];
                    foreach ($mission['ships'] as $shipKey) {
                        $carry[$shipKey] = $this->findFlightService->canCarry($shipKey, $t->event_type);
                    }

                    return ['q' => (int) $t->q, 'r' => (int) $t->r, 'ring' => (int) $t->ring, 'find_type' => $t->event_type,
                        ...$costs, 'return_sol' => $tick + $costs['duration'], 'carry' => $carry,
                        'yield' => $this->findFlightService->findFlightYield($colonyId, $t->event_type)];
                })
                ->values()
                ->all();
        }
```

Neue öffentliche Methode:

```php
    /**
     * Tick payout of a salvage flight (§13.3). Defensive: a tile that is no longer a
     * scanned real find, or a ship that cannot carry it, pays nothing (find_missing).
     *
     * @return array{find_type: ?string, find_regolith: int, flight_bonus: int, find_missing?: bool}
     */
    public function completeFindFlight(int $colonyId, ?array $target, int $shipId): array
    {
        $none = ['find_type' => null, 'find_regolith' => 0, 'flight_bonus' => 0, 'find_missing' => true];
        if (! isset($target['q'], $target['r'])) {
            return $none;
        }

        return DB::transaction(function () use ($colonyId, $target, $shipId, $none): array {
            $tile = ColonyTile::where('colony_id', $colonyId)->where('q', (int) $target['q'])->where('r', (int) $target['r'])
                ->lockForUpdate()->first();
            $type = $tile?->event_type;
            if ($tile === null || ! $tile->is_deep_scanned || ! in_array($type, ColonyTileService::FIND_TYPES, true)
                || ! $this->findFlightService->canCarry(HangarBays::shipKey($shipId), $type)) {
                return $none;
            }
            $yield = $this->findFlightService->findFlightYield($colonyId, $type);
            $this->colonyTileService->completeFind($tile, $yield['total']);

            return ['find_type' => $type, 'find_regolith' => $yield['total'], 'flight_bonus' => $yield['flight_bonus']];
        });
    }
```

(`use App\Models\ColonyTile;` und `use App\Support\HangarBays;` importieren.)

- [ ] **Step 7: `GameTick`**

Z. 432-463 ersetzen (Struktur bleibt, der Wurf wird optional):

```php
            $catalogEntry = config("missions.catalog.{$mission->destination}");
            $difficulty = DB::table('colony_hangar_missions')->where('id', $mission->mission_id)->value('difficulty') ?? 'normal';
            $rewardDetails = [];
            $success = true;

            if ($catalogEntry !== null) {
                $missionSeed = $rngSeed + RunSeed::rowOrdinal('colony_hangar_missions', (int) $mission->colony_id, (int) $mission->mission_id);
                // T30 §13.3: missions with roll === false (salvage flight) skip the success roll.
                $rolls = ($catalogEntry['roll'] ?? true) !== false;
                if ($rolls) {
                    $successChance = $this->hangarService->successChanceFor((int) $mission->colony_id, $catalogEntry, $difficulty);
                    $success = $this->seededRoll($missionSeed + 1, 0, 9999) / 10000 <= $successChance;
                }

                if ($success) {
                    $rewardMultiplier = $rolls ? (float) config("game.missions.difficulty.reward_multiplier.{$difficulty}", 1.0) : 1.0;
                    $rewardDetails = $this->payMissionRewards(
                        (int) $mission->colony_id, $userId, $catalogEntry['reward'],
                        $mission->target !== null ? json_decode($mission->target, true) : null,
                        $missionSeed, $tick, $rewardMultiplier, (int) $mission->ship_id
                    );
                } elseif ($difficulty === 'hard') {
                    // unchanged hard-fail wear block
                }
            }
```

`payMissionRewards(..., float $rewardMultiplier = 1.0, int $shipId = 0)`: im Typ-`if` vor dem `harvester_instance`-Zweig ergänzen:

```php
            } elseif ($type === 'find_salvage') {
                // T30 §13.3: own detail keys, deliberately NOT 'regolith' (RunReport topf find_flight).
                // Hook for §13.6 (Feldproben, Fahrtbericht) — find_type is in the details.
                $details += $this->hangarService->completeFindFlight($colonyId, $target, $shipId);

                continue;
```

- [ ] **Step 8: Lang und `MissionReward`**

`MissionReward::FIXED_PARTS`: `'find_salvage' => 'reward_find_salvage',`.

`lang/de/missions.php`:

```php
    'mission_find_salvage_name' => 'Bergungsflug',
    'mission_find_salvage_desc' => 'Die Crew fliegt einen gescannten Fund an und holt ihn mit dem Bergegerät an Bord — es kommt mehr ins Lager als zu Fuß.',
    'reward_find_salvage' => 'Fund + Flugbonus',
    'error_find_not_scanned' => 'Das Signal ist noch nicht gescannt — erst scannen.',
    'error_find_too_large' => 'Dieser Fund ist zu schwer für das Schiff.',
    'error_find_in_flight' => 'Zu diesem Fund ist schon ein Schiff unterwegs.',
    'error_find_salvage_in_progress' => 'Hier gräbt schon der Bergungstrupp — der Fund wird zu Fuß zu Ende geborgen.',
```

`lang/en/missions.php`:

```php
    'mission_find_salvage_name' => 'Salvage Flight',
    'mission_find_salvage_desc' => 'The crew flies out to a scanned find and hauls it aboard with the salvage rig — more reaches the stores than on foot.',
    'reward_find_salvage' => 'Find + flight bonus',
    'error_find_not_scanned' => 'The signal has not been scanned yet — scan it first.',
    'error_find_too_large' => 'This find is too heavy for the ship.',
    'error_find_in_flight' => 'A ship is already on its way to this find.',
    'error_find_salvage_in_progress' => 'The salvage crew is already digging here — the find is finished on foot.',
```

- [ ] **Step 9: Grün**

Run: `bin/phpunit --filter='FindFlightTest|FindFlightConfigTest|SalvageFindTest|HangarServiceTest|HangarMissionResolutionTest|MissionRewardTest'` → PASS.

- [ ] **Step 10: Commit**

```bash
git add app/Services/FindFlightService.php app/Services/HangarService.php app/Services/ColonyTileService.php app/Console/Commands/GameTick.php app/Support/MissionReward.php config/missions.php lang/de/missions.php lang/en/missions.php tests/Feature/Hangar/FindFlightTest.php
git commit -m "feat(T30): Bergungsflug mission_find_salvage (Ring-Entfernung, kein Wurf, Ertrag x1,5)"
```

---

### Task 6: Ablösung der Selbstbergung — `findAction()` als einzige Quelle

**Files:**
- Modify: `app/Services/ColonyTileService.php` (neu `findAction`, `hangarLevel`; `salvageFind` Z. 150-162 Guard; `transformTile` Z. 400-421)
- Modify: `app/Services/HangarService.php` (`assertFindFlyable` über `findAction`)
- Modify: `lang/de/colony.php`, `lang/en/colony.php` (um Z. 169-175 Fehler, um Z. 744-758 Gründe)
- Modify: `tests/Feature/Colony/SalvageFindTest.php:23-29` (setUp ohne Hangar), `tests/Feature/Colony/DeepScanFindTest.php:52-55`
- Test: `tests/Feature/Colony/FindActionTest.php` (neu)

**Spec-Abweichung:** Die Gründe sind feiner gefasst als in der Tabelle §13.4: `ship_busy` (Schiff unterwegs oder in Lieferung), `ship_worn` (Schiff gedockt, aber verfehlt die Rückkehr-Garantie für die Ring-Entfernung der Kachel, statt „in Reparatur“, Owner §13.x/7), `ship_inactive` (tragendes Schiff nur in geschlossener Bucht) und `no_ship` (gar kein tragendes Schiff, defensiv) sind getrennt. Für K16 werden sie so zusammengefasst: `ship_busy` → `ship_busy`; `ship_worn` und `no_ship` → `no_ship_ready`; `needs_freighter` und `ship_inactive` → `needs_freighter`; `needs_level` → `needs_level`.

**Interfaces:**
- Consumes: `FindFlightService` (Task 5), `HangarBays` inkl. `flightReadiness` (Task 1/2), `findFlightReturnSol` (Task 5).
- Produces: `ColonyTileService::findAction(int $colonyId, object $tile): array{action:string, reason:string, bay?:int, ship_key?:string, return_sol?:int, needs_level?:int, repair_ap?:int, readiness?:array, yield?:array, costs?:array}` mit `action` ∈ {`scan`, `none`, `self_salvage`, `continue_salvage`, `flight`, `wait`, `locked`} und `reason` ∈ {`unscanned`, `false`, `in_flight`, `self`, `grandfathered`, `flight`, `ship_busy`, `ship_worn`, `needs_level`, `ship_inactive`, `needs_freighter`, `no_ship`}. Bei `flight` sind `costs` (`FindFlightService::costs` + `return_sol`) und `yield` gesetzt.
- Produces: `transformTile()['find']['action']` (Ergebnis von `findAction` plus `text` = übersetzte Begründungszeile) für gescannte Fund-Kacheln. Fehlercodes `salvage_by_flight`, `find_in_flight` in `salvageFind`.

- [ ] **Step 1: Failing tests** `tests/Feature/Colony/FindActionTest.php`

```php
<?php

namespace Tests\Feature\Colony;

use App\Models\ColonyTile;
use App\Services\ColonyTileService;
use App\Services\HangarService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T30 §13.4: exactly one action plus one reason per find tile, read by guards, UI and bot. */
class FindActionTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(100));
        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->delete();
        config(['game.bypass.ap_checks' => false]);
    }

    private function hall(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => $level > 0 ? 5 : null, 'tile_y' => $level > 0 ? 5 : null]
        );
    }

    private function ship(int $shipId, int $bay, string $state = 'docked', float $sp = 20): void
    {
        DB::table('colony_ships')->insert(['colony_id' => self::COLONY_ID, 'ship_id' => $shipId, 'bay' => $bay,
            'ship_state' => $state, 'level' => 0, 'status_points' => $sp, 'ap_spend' => 0, 'deliver_at_tick' => $state === 'building' ? 103 : null]);
    }

    private function find(string $type, bool $scanned = true, int $spent = 0): ColonyTile
    {
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => 2, 'r' => 0],
            ['ring' => 2, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 0,
                'is_deep_scanned' => $scanned ? 1 : 0, 'event_type' => $type, 'salvage_ap_spent' => $spent, 'salvage_tick' => null]
        );

        return ColonyTile::where('colony_id', self::COLONY_ID)->where('q', 2)->where('r', 0)->firstOrFail();
    }

    private function action(ColonyTile $tile): array
    {
        return $this->app->make(ColonyTileService::class)->findAction(self::COLONY_ID, $tile);
    }

    private function assertAction(string $action, string $reason, ColonyTile $tile): array
    {
        $result = $this->action($tile);
        $this->assertSame([$action, $reason], [$result['action'], $result['reason']]);

        return $result;
    }

    public function test_unscanned_and_false_alarm(): void
    {
        $this->assertAction('scan', 'unscanned', $this->find('find_large', scanned: false));
        $this->assertAction('none', 'false', $this->find('find_false'));
    }

    public function test_without_hangar_or_on_level_zero_self_salvage_for_every_size(): void
    {
        $this->assertAction('self_salvage', 'self', $this->find('find_large'));
        $this->assertAction('continue_salvage', 'self', $this->find('find_large', spent: 4));
        $this->hall(0);
        $this->assertAction('self_salvage', 'self', $this->find('find_large'));
    }

    public function test_grandfathered_project_continues_with_hangar(): void
    {
        $this->hall(1);
        $this->ship(85, 0);
        $this->assertAction('continue_salvage', 'grandfathered', $this->find('find_small', spent: 4));
    }

    public function test_flight_with_the_gift_drone_carries_costs_and_yield(): void
    {
        $this->hall(1);
        $this->ship(85, 0);

        $r = $this->assertAction('flight', 'flight', $this->find('find_medium'));

        $this->assertSame([0, 'drone'], [$r['bay'], $r['ship_key']]);
        $this->assertSame(['base' => 10, 'flight_bonus' => 5, 'total' => 15], $r['yield']);
        $this->assertSame([2, 3, 102], [$r['costs']['nav_ap_base'], $r['costs']['organika'], $r['costs']['return_sol']]);
    }

    public function test_smallest_carrying_class_flies(): void
    {
        $this->hall(2);
        $this->ship(85, 0);
        $this->ship(47, 2);
        $this->assertSame('drone', $this->action($this->find('find_small'))['ship_key']);
        $this->assertSame(['freighter', 2], [$this->action($this->find('find_large'))['ship_key'], $this->action($this->find('find_large'))['bay']]);
    }

    public function test_waiting_reasons(): void
    {
        $this->hall(1);
        $this->ship(85, 0, 'dispatched');
        DB::table('colony_hangar_missions')->insert(['colony_id' => self::COLONY_ID, 'bay' => 0, 'ship_id' => 85,
            'destination' => 'mission_recon_flight', 'sol_distance' => 1, 'dispatch_tick' => 100, 'state' => 'active']);
        $this->assertSame(102, $this->assertAction('wait', 'ship_busy', $this->find('find_small'))['return_sol']);

        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->update(['ship_state' => 'docked', 'status_points' => 2]);
        // Ring 2 drone wear 3: 2 SP would not come back → 1 click (+2) → 4 > 3.
        $worn = $this->assertAction('wait', 'ship_worn', $this->find('find_small'));
        $this->assertSame([0, 1], [$worn['bay'], $worn['repair_ap']]);
    }

    public function test_drone_with_6_sp_waits_for_a_ring_3_find_but_flies_ring_2(): void
    {
        $this->hall(1);
        $this->ship(85, 0, 'docked', 6);
        $this->assertAction('flight', 'flight', $this->find('find_small')); // ring 2: wear 3

        DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->where('q', 2)->where('r', 0)->update(['ring' => 3]);
        $tile = ColonyTile::where('colony_id', self::COLONY_ID)->where('q', 2)->where('r', 0)->firstOrFail();
        $this->assertSame(1, $this->assertAction('wait', 'ship_worn', $tile)['repair_ap']); // ring 3: wear 6
    }

    public function test_locked_reasons_for_large_finds(): void
    {
        $this->hall(1);
        $this->ship(85, 0);
        $this->assertSame(2, $this->assertAction('locked', 'needs_level', $this->find('find_large'))['needs_level']);

        $this->hall(2);
        $this->assertAction('locked', 'needs_freighter', $this->find('find_large'));

        $this->ship(47, 3); // bay 3 closed at level 2
        $this->assertAction('locked', 'ship_inactive', $this->find('find_large'));
    }

    public function test_in_flight_wins_over_everything_but_scan(): void
    {
        $this->hall(1);
        $this->ship(85, 0, 'dispatched');
        DB::table('colony_hangar_missions')->insert(['colony_id' => self::COLONY_ID, 'bay' => 0, 'ship_id' => 85,
            'destination' => 'mission_find_salvage', 'sol_distance' => 1, 'dispatch_tick' => 100, 'state' => 'active', 'target' => '{"q":2,"r":0}']);

        $this->assertSame(102, $this->assertAction('none', 'in_flight', $this->find('find_small'))['return_sol']);
    }

    public function test_salvage_guard_follows_find_action(): void
    {
        $svc = fn () => $this->app->make(ColonyTileService::class);
        $this->hall(1);
        $this->ship(85, 0);
        $this->find('find_small');
        $ap = $this->app->make(\App\Services\AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID);

        $this->assertSame('salvage_by_flight', $svc()->salvageFind(self::COLONY_ID, 2, 0, 4)['error']);
        $this->assertSame($ap, $this->app->make(\App\Services\AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID));

        $this->find('find_small', spent: 8);
        $res = $svc()->salvageFind(self::COLONY_ID, 2, 0, 4);
        $this->assertTrue($res['ok']);
        $this->assertTrue($res['completed'], 'grandfathered project finishes normally');

        $this->find('find_small');
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->update(['level' => 0, 'tile_x' => null, 'tile_y' => null]);
        $this->assertTrue($svc()->salvageFind(self::COLONY_ID, 2, 0, 4)['ok'], 'demolished hangar: self-salvage is back');
    }

    public function test_self_salvage_is_blocked_while_a_flight_is_out_even_without_hangar(): void
    {
        $this->find('find_small');
        DB::table('colony_hangar_missions')->insert(['colony_id' => self::COLONY_ID, 'bay' => 0, 'ship_id' => 85,
            'destination' => 'mission_find_salvage', 'sol_distance' => 1, 'dispatch_tick' => 100, 'state' => 'active', 'target' => '{"q":2,"r":0}']);

        $this->assertSame('find_in_flight', $this->app->make(ColonyTileService::class)->salvageFind(self::COLONY_ID, 2, 0, 4)['error']);
    }

    public function test_flight_reason_matches_an_accepted_dispatch(): void
    {
        $this->hall(1);
        $this->ship(85, 0);
        $r = $this->action($this->find('find_medium'));

        $this->app->make(HangarService::class)->dispatchShip(self::COLONY_ID, $r['bay'], 'mission_find_salvage', ['q' => 2, 'r' => 0], 'normal');

        $this->assertSame('in_flight', $this->action($this->find('find_medium'))['reason']);
    }

    public function test_tile_payload_carries_action_and_translated_reason(): void
    {
        $this->hall(1);
        $this->ship(85, 0);
        $this->find('find_medium');

        $tile = $this->app->make(ColonyTileService::class)->getTilesForColony(self::COLONY_ID)->firstWhere(fn ($t) => $t['q'] === 2 && $t['r'] === 0);

        $this->assertSame('flight', $tile['find']['action']['action']);
        $this->assertStringContainsString('15', $tile['find']['action']['text']);
    }
}
```

`SalvageFindTest::setUp` (Z. 23-29): nach `TestSeeder` ergänzen: `DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->delete(); // Pool v1 self-salvage = colony without hangar (T30 §13.4)`.

`DeepScanFindTest::test_scan_reveals_find_and_locks_2_ap` (Z. 52-55): Das Array-Assert vergleicht jetzt ohne den neuen Schlüssel: `$find = $res['tile']['find']; unset($find['action']); $this->assertSame([...], $find);`. Danach `$this->assertSame('flight', $res['tile']['find']['action']['reason'] === 'flight' ? 'flight' : $res['tile']['find']['action']['reason'])` **nicht** schreiben. Stattdessen genügt `$this->assertArrayHasKey('action', $res['tile']['find']);`.

- [ ] **Step 2: Rot bestätigen.** Run: `bin/phpunit --filter='FindActionTest|SalvageFindTest|DeepScanFindTest'` → FAIL (`findAction` fehlt).

- [ ] **Step 3: `findAction` in `ColonyTileService`**

```php
    /**
     * T30 §13.4: the ONE allowed action for a find tile plus its reason key. Single source
     * for salvageFind(), HangarService::assertFindFlyable(), the find card and the bot.
     * Check order: unscanned → false alarm → in flight → without hangar → grandfathered
     * → level/carry → ship status. "Without hangar" is derived from the hall level
     * (no building or level 0), never stored (E6).
     */
    public function findAction(int $colonyId, object $tile): array
    {
        $type = $tile->event_type;
        if (! (bool) $tile->is_deep_scanned) {
            return ['action' => 'scan', 'reason' => 'unscanned'];
        }
        if (! in_array($type, self::FIND_TYPES, true)) {
            return ['action' => 'none', 'reason' => 'false'];
        }
        $returnSol = $this->findFlightReturnSol($colonyId, (int) $tile->q, (int) $tile->r);
        if ($returnSol !== null) {
            return ['action' => 'none', 'reason' => 'in_flight', 'return_sol' => $returnSol];
        }
        $started = (int) $tile->salvage_ap_spent > 0;
        $level = $this->hangarLevel($colonyId);
        if ($level < 1) {
            return ['action' => $started ? 'continue_salvage' : 'self_salvage', 'reason' => 'self'];
        }
        if ($started) {
            return ['action' => 'continue_salvage', 'reason' => 'grandfathered'];
        }

        $carriers = DB::table('colony_ships')
            ->where('colony_id', $colonyId)->whereNotNull('bay')->orderBy('bay')
            ->get(['ship_id', 'bay', 'ship_state', 'status_points', 'deliver_at_tick'])
            ->filter(fn ($s) => $this->findFlightService->canCarry(HangarBays::shipKey((int) $s->ship_id), $type));
        $active = $carriers->reject(fn ($s) => HangarBays::isInactive((int) $s->ship_id, $level, (int) $s->bay));

        $minLevel = $this->findFlightService->minHangarLevel($type);
        if ($level < $minLevel || $active->isEmpty()) {
            $inactive = $carriers->first();
            if ($inactive !== null) {
                return ['action' => 'locked', 'reason' => 'ship_inactive', 'ship_key' => HangarBays::shipKey((int) $inactive->ship_id),
                    'needs_level' => max(HangarBays::requiredLevel((int) $inactive->ship_id), (int) $inactive->bay)];
            }
            if ($level < $minLevel) {
                return ['action' => 'locked', 'reason' => 'needs_level', 'needs_level' => $minLevel];
            }

            return $type === 'find_large'
                ? ['action' => 'locked', 'reason' => 'needs_freighter']
                : ['action' => 'wait', 'reason' => 'no_ship'];
        }

        $ready = $active
            // T30 §13.x/7: "ready" = the return guarantee for this tile's ring distance.
            ->filter(fn ($s) => $s->ship_state === 'docked'
                && HangarBays::flightReadiness((int) $s->ship_id, (float) $s->status_points, $this->findFlightService->solDistance((int) $tile->ring))['ready'])
            ->sortBy([
                fn ($a, $b) => HangarBays::requiredLevel((int) $a->ship_id) <=> HangarBays::requiredLevel((int) $b->ship_id),
                fn ($a, $b) => (int) $a->bay <=> (int) $b->bay,
            ])
            ->first();
        if ($ready !== null) {
            $costs = $this->findFlightService->costs($colonyId, (int) $tile->ring);

            return ['action' => 'flight', 'reason' => 'flight', 'bay' => (int) $ready->bay,
                'ship_key' => HangarBays::shipKey((int) $ready->ship_id),
                'costs' => [...$costs, 'return_sol' => $this->tickService->getTickCount() + $costs['duration']],
                'yield' => $this->findFlightService->findFlightYield($colonyId, $type)];
        }

        $busy = $active->first(fn ($s) => in_array($s->ship_state, ['dispatched', 'building'], true));
        if ($busy !== null) {
            return ['action' => 'wait', 'reason' => 'ship_busy', 'ship_key' => HangarBays::shipKey((int) $busy->ship_id),
                'return_sol' => $this->shipBackSol($colonyId, $busy)];
        }
        // Docked but worn: the smallest class first (that is the ship the bot repairs).
        $worn = $active->sortBy([
            fn ($a, $b) => HangarBays::requiredLevel((int) $a->ship_id) <=> HangarBays::requiredLevel((int) $b->ship_id),
            fn ($a, $b) => (int) $a->bay <=> (int) $b->bay,
        ])->first();
        $readiness = HangarBays::flightReadiness((int) $worn->ship_id, (float) $worn->status_points, $this->findFlightService->solDistance((int) $tile->ring));

        return ['action' => 'wait', 'reason' => 'ship_worn', 'ship_key' => HangarBays::shipKey((int) $worn->ship_id),
            'bay' => (int) $worn->bay, 'repair_ap' => $readiness['repair_ap'], 'readiness' => $readiness];
    }

    private function hangarLevel(int $colonyId): int
    {
        return (int) (DB::table('colony_buildings')->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::Hangar->value)->max('level') ?? 0);
    }

    /** Sol a busy ship is back: return of its active mission, or its Nexus delivery. */
    private function shipBackSol(int $colonyId, object $ship): ?int
    {
        if ($ship->ship_state === 'building') {
            return $ship->deliver_at_tick !== null ? (int) $ship->deliver_at_tick : null;
        }
        $mission = DB::table('colony_hangar_missions')->where('colony_id', $colonyId)
            ->where('bay', (int) $ship->bay)->where('state', 'active')->first(['dispatch_tick', 'sol_distance']);

        return $mission === null ? null : (int) $mission->dispatch_tick + 2 * (int) $mission->sol_distance;
    }
```

(`use App\Support\HangarBays;` ergänzen.)

- [ ] **Step 4: Guard in `salvageFind`** — nach `if ($tile->event_type === 'find_false') {...}` (Z. 156-158):

```php
            // T30 §13.4: with a hangar >= 1 finds go by flight only (grandfathered projects excepted).
            $action = $this->findAction($colonyId, $tile);
            if ($action['reason'] === 'in_flight') {
                return $fail('find_in_flight');
            }
            if (! in_array($action['action'], ['self_salvage', 'continue_salvage'], true)) {
                return $fail('salvage_by_flight');
            }
```

- [ ] **Step 5: `transformTile`** (Z. 408-418), im `find`-Array ergänzen:

```php
                'action' => $this->findActionWithText((int) $tile->colony_id, $tile),
```

Und:

```php
    /** findAction() plus its player-facing reason line (numbers from the same source as the guards). */
    private function findActionWithText(int $colonyId, ColonyTile $tile): array
    {
        $a = $this->findAction($colonyId, $tile);
        $ship = isset($a['ship_key']) ? __("colony.hangar_ship_{$a['ship_key']}") : '';
        $a['text'] = __("colony.find_reason_{$a['reason']}", [
            'sol' => $a['return_sol'] ?? $a['costs']['return_sol'] ?? '',
            'ship' => $ship,
            'level' => $a['needs_level'] ?? '',
            'base' => $a['yield']['base'] ?? '',
            'bonus' => $a['yield']['flight_bonus'] ?? '',
            'total' => $a['yield']['total'] ?? '',
            'ap' => $a['costs']['nav_ap'] ?? $a['repair_ap'] ?? '',
            'organika' => $a['costs']['organika'] ?? '',
        ]);

        return $a;
    }
```

- [ ] **Step 6: Dispatch-Guard umverdrahten.** `HangarService::assertFindFlyable` setzt nach der `null`-Prüfung auf `findAction` auf (Task-4-Tests bleiben unverändert grün):

```php
        if ($tile === null || $tile->event_type === null) {
            throw $this->ruleError('invalid_target', 'missions.error_invalid_target');
        }
        match ($this->colonyTileService->findAction($colonyId, $tile)['reason']) {
            'unscanned' => throw $this->ruleError('find_not_scanned', 'missions.error_find_not_scanned'),
            'false' => throw $this->ruleError('invalid_target', 'missions.error_invalid_target'),
            'in_flight' => throw $this->ruleError('find_in_flight', 'missions.error_find_in_flight'),
            'grandfathered', 'self' => throw $this->ruleError('find_salvage_in_progress', 'missions.error_find_salvage_in_progress'),
            default => null,
        };
        if (! $this->findFlightService->canCarry($shipKey, $tile->event_type)) {
            throw $this->ruleError('find_too_large', 'missions.error_find_too_large');
        }
```

(`self` tritt beim Dispatch nur bei Halle < 1 auf. Diesen Fall fängt vorher schon `ship_inactive` ab; der Zweig ist reine Absicherung.)

- [ ] **Step 7: Lang** (`lang/de/colony.php`, Fehlerblock und Fundblock; en analog)

```php
    'error_salvage_by_flight' => 'Mit Hangar werden Funde per Bergungsflug geborgen.',   // en: 'With a hangar, finds are recovered by salvage flight.'
    'error_find_in_flight' => 'Zu diesem Fund ist gerade ein Schiff unterwegs.',         // en: 'A ship is on its way to this find.'
    'find_reason_unscanned' => 'Signal unbekannt. Erst scannen.',                       // en: 'Unknown signal. Scan it first.'
    'find_reason_false' => 'Fehlalarm. Das Feld ist frei.',                             // en: 'False alarm. The tile is free.'
    'find_reason_self' => 'Ohne Hangar birgt die Kolonie selbst.',                      // en: 'Without a hangar the colony salvages on foot.'
    'find_reason_grandfathered' => 'Begonnen vor dem Hangar. Der Trupp gräbt zu Ende.', // en: 'Started before the hangar. The crew digs it out.'
    'find_reason_in_flight' => 'Im Flug. Zurück an Sol :sol.',                          // en: 'In flight. Back on Sol :sol.'
    'find_reason_flight' => 'Fund :base Rg + Flug :bonus Rg = :total Rg · :ap AP · :organika Or · zurück Sol :sol', // en: 'Find :base Rg + flight :bonus Rg = :total Rg · :ap AP · :organika Or · back Sol :sol'
    'find_reason_ship_busy' => 'Wartet: :ship zurück an Sol :sol.',                     // en: 'Waiting: :ship back on Sol :sol.'
    'find_reason_ship_worn' => 'Wartet: :ship reparieren (:ap AP).',                    // en: 'Waiting: repair the :ship (:ap AP).'
    'find_reason_needs_level' => 'Gesperrt: Frachter nötig – Hangar Stufe :level.',     // en: 'Locked: needs a freighter – hangar level :level.'
    'find_reason_needs_freighter' => 'Gesperrt: Frachter nötig – im Nexus bestellen.',  // en: 'Locked: needs a freighter – order one from the Nexus.'
    'find_reason_ship_inactive' => 'Gesperrt: :ship inaktiv – Hangar Stufe :level nötig.', // en: 'Locked: :ship inactive – needs hangar level :level.'
    'find_reason_no_ship' => 'Wartet: kein Schiff, das den Fund tragen kann.',          // en: 'Waiting: no ship that can carry the find.'
```

- [ ] **Step 8: Grün**

Run: `bin/phpunit --filter='FindActionTest|FindFlightTest|SalvageFindTest|DeepScanFindTest|HangarServiceTest'` → PASS.

- [ ] **Step 9: Commit**

```bash
git add app/Services/ColonyTileService.php app/Services/HangarService.php lang/de/colony.php lang/en/colony.php tests/Feature/Colony/FindActionTest.php tests/Feature/Colony/SalvageFindTest.php tests/Feature/Colony/DeepScanFindTest.php
git commit -m "feat(T30): findAction als einzige Quelle — Selbstbergung nur ohne Hangar, Bestandsschutz"
```

---

### Task 7: UI — Zielliste im Hangar, Fundkarte mit einer Aktion, Hinweise

**Files:**
- Modify: `app/Http/Controllers/Colony/HangarController.php` (Konstruktor: `ColonyTileService`; `dispatch` Z. 193-197 liefert `tile` bei `find_tile`)
- Modify: `app/Services/ColonyTileService.php` (neu `tileFor`)
- Modify: `app/Http/Controllers/Colony/ColonyController.php:340-356` (`notice` für Gebäude 44)
- Modify: `app/Services/OnboardingHintService.php:264-271` (Rang 20 `hint_find_flight`, Methode `checkHintFindFlight`)
- Modify: `resources/views/colony/hangar.blade.php:510-557` (Ertrag je Ziel, Schwierigkeit nur bei Wurf), `public/js/hangar.js:249-295` (`targetLabel`, `missionTargetCarryable`)
- Modify: `resources/views/colony/partials/tile-terrain.blade.php:88-123` (Fundkarte)
- Modify: `resources/views/colony/hexview.blade.php:54-63` (Route), `:85-90` (i18n), `:446-460` (Baukarte `notice`), `:177` (`find_help`)
- Modify: `public/js/colony-hexgrid.js:363-405` (`doFindFlight`)
- Modify: `lang/de/colony.php`, `lang/en/colony.php`, `lang/de/missions.php`, `lang/en/missions.php`, `lang/de/buildings.php:29`, `lang/en/buildings.php` (`hangar_desc`)
- Test: `tests/Feature/Hangar/FindFlightUiTest.php` (neu) + Playwright-Check

**Interfaces:**
- Consumes: `findAction`/`transformTile()['find']['action']` (Task 6), Katalog `targets` und `roll` (Task 5).
- Produces: `ColonyTileService::tileFor(int $colonyId, int $q, int $r): ?array`. `POST /colony/hangar/{bay}/dispatch` antwortet zusätzlich mit `tile` (transformierte Kachel), wenn die Mission `target_type = find_tile` hat. `GET /colony/buildings/available` liefert für Gebäude 44 das Feld `notice`. Hint-Key `hint_find_flight`.

Texte: Die Vorschläge stehen unten, `content-writer` darf sie glätten (Ton nüchtern und knapp, §13.4). Lore-Zeilen aus §13.4 kommen in `find_help`.

- [ ] **Step 1: Failing tests** `tests/Feature/Hangar/FindFlightUiTest.php`

```php
<?php

namespace Tests\Feature\Hangar;

use App\Models\User;
use App\Services\OnboardingHintService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T30 §13.4/§13.7 Task 6: server side of the find card, the target list and the hints. */
class FindFlightUiTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(100));
        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_buildings')->updateOrInsert(['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 5, 'tile_y' => 5]);
        DB::table('colony_ships')->insert(['colony_id' => self::COLONY_ID, 'ship_id' => 85, 'bay' => 0, 'ship_state' => 'docked',
            'level' => 0, 'status_points' => 20, 'ap_spend' => 0]);
        DB::table('colony_tiles')->updateOrInsert(['colony_id' => self::COLONY_ID, 'q' => 2, 'r' => 0],
            ['ring' => 2, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 1,
                'event_type' => 'find_medium', 'salvage_ap_spent' => 0, 'salvage_tick' => null]);
    }

    private function bart(): User
    {
        return User::where('user_id', 3)->firstOrFail();
    }

    public function test_colony_view_ships_the_find_action_and_the_flight_route(): void
    {
        $html = $this->actingAs($this->bart())->get(route('colony.view'))->assertOk()->getContent();

        $this->assertStringContainsString('"reason":"flight"', $html);
        $this->assertStringContainsString('/colony/hangar/__BAY__/dispatch', $html);
    }

    public function test_flight_dispatch_answers_with_the_tile_in_flight(): void
    {
        $res = $this->actingAs($this->bart())->postJson(route('colony.hangar.dispatch', ['bay' => 0]),
            ['mission_key' => 'mission_find_salvage', 'difficulty' => 'normal', 'target' => ['q' => 2, 'r' => 0]]);

        $res->assertOk()->assertJsonPath('tile.find.action.reason', 'in_flight')
            ->assertJsonStructure(['apAvailable', 'organika']);
    }

    public function test_hangar_build_card_carries_the_salvage_notice(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->delete();

        $buildings = $this->actingAs($this->bart())->getJson(route('colony.buildings.available'))->json('buildings');
        $hangar = collect($buildings)->firstWhere('building_id', 44);

        if ($hangar !== null) {
            $this->assertSame(__('colony.hangar_build_notice'), $hangar['notice']);
        }
        $this->assertNotNull(collect($buildings)->first(fn ($b) => array_key_exists('notice', $b)), 'notice key present on every entry');
    }

    public function test_find_flight_hint_is_active_from_hangar_level_one(): void
    {
        $service = $this->app->make(OnboardingHintService::class);
        $list = (new \ReflectionMethod($service, 'buildHintList'))->invoke($service, self::COLONY_ID, 5, 3);

        $this->assertTrue(collect($list)->firstWhere('key', 'hint_find_flight')['active']);
    }
}
```

(Den Routennamen der Kolonieansicht vorher per `grep -n "hexview" routes/web.php` prüfen und `colony.view` bei Bedarf anpassen.)

- [ ] **Step 2: Rot bestätigen.** Run: `bin/phpunit tests/Feature/Hangar/FindFlightUiTest.php` → FAIL.

- [ ] **Step 3: Server**

`ColonyTileService`:

```php
    /** One transformed tile (find card refresh after a hangar dispatch), null if unknown. */
    public function tileFor(int $colonyId, int $q, int $r): ?array
    {
        $tile = ColonyTile::where('colony_id', $colonyId)->where('q', $q)->where('r', $r)->first();

        return $tile === null ? null : $this->transformTile($tile);
    }
```

`HangarController`: Konstruktor um `private readonly ColonyTileService $colonyTileService` erweitern. In `dispatch` vor dem Return:

```php
        $tile = config("missions.catalog.{$validated['mission_key']}.target_type") === 'find_tile' && isset($validated['target']['q'], $validated['target']['r'])
            ? $this->colonyTileService->tileFor($colony->id, (int) $validated['target']['q'], (int) $validated['target']['r'])
            : null;

        return response()->json([
            'ok' => true,
            'slot' => $this->fetchSlot($colony->id, $bay),
            'tile' => $tile,
            ...$this->currentHangarResources($colony->id),
        ]);
```

`ColonyController::availableBuildings`, im `->map(fn ($b) => [...])` ergänzen: `'notice' => (int) $b->id === BuildingId::Hangar->value ? __('colony.hangar_build_notice') : null,`.

`OnboardingHintService::buildHintList`, als letzter Eintrag:

```php
            [
                'rank' => 20,
                'key' => 'hint_find_flight',
                'active' => $this->checkHintFindFlight($colonyId),
                'text_key' => 'colony.onboarding_hint_find_flight',
                'target_url' => '/colony/hangar',
            ],
```

```php
    /** T30 §13.4: once the hangar stands, finds go by ship — shown once until dismissed. */
    private function checkHintFindFlight(int $colonyId): bool
    {
        return (int) (DB::table('colony_buildings')->where('colony_id', $colonyId)->where('building_id', 44)->max('level') ?? 0) >= 1;
    }
```

- [ ] **Step 4: Fundkarte** (`resources/views/colony/partials/tile-terrain.blade.php:94-121`)

Der bisherige Echt-Fund-Block wird nach `selectedTile.find.action.action` aufgeteilt. Die Begründungszeile steht immer, der Knopf höchstens einmal:

```blade
            <template x-if="!selectedTile.find.false">
                <div x-data="{ ap: 1 }" x-effect="ap = salvageMax(selectedTile.find)">
                    <dl class="tile-dl find-card__dl">
                        <div>
                            <dt>{{ __("colony.find_label") }}</dt>
                            <dd x-text="@js(__("colony.find_value")).replace(':rg', selectedTile.find.rg)"></dd>
                        </div>
                        <template x-if="['self_salvage', 'continue_salvage'].includes(selectedTile.find.action.action)">
                            <div>
                                <dt>{{ __("colony.find_effort") }}</dt>
                                <dd x-text="`${selectedTile.find.ap_spent}/${selectedTile.find.ap_total} AP`"></dd>
                            </div>
                        </template>
                    </dl>
                    <p class="find-card__reason" x-text="selectedTile.find.action.text"></p>
                    <template x-if="['self_salvage', 'continue_salvage'].includes(selectedTile.find.action.action)">
                        <div class="find-card__action">
                            <input type="number" class="find-card__ap" min="1" :max="salvageMax(selectedTile.find)"
                                step="1" x-model.number="ap" aria-label="{{ __("colony.find_salvage_ap_label") }}" />
                            <button type="button" class="tile-action-btn" @click="doSalvage(selectedTile, ap)">
                                <span class="tile-action-btn__body">{{ __("colony.find_salvage") }}</span>
                                <span class="ap-chip ap-cost-chip ap-chip--nav" aria-hidden="true"
                                    x-text="`${salvageClamp(selectedTile.find, ap)} AP`"></span>
                            </button>
                        </div>
                    </template>
                    <template x-if="selectedTile.find.action.action === 'flight'">
                        <div class="find-card__action">
                            <button type="button" class="tile-action-btn" @click="doFindFlight(selectedTile)">
                                <span class="tile-action-btn__body">{{ __("colony.find_flight") }}</span>
                                <span class="ap-chip ap-cost-chip ap-chip--nav" aria-hidden="true"
                                    x-text="`${selectedTile.find.action.costs.nav_ap} AP`"></span>
                            </button>
                        </div>
                    </template>
                </div>
            </template>
```

(Die bisherige Deckel-Zeile `find_cap` bleibt im Selbstbergungs-Zweig.) Dazu in `hexview.blade.php` unter `routes:` `hangarDispatch: '{{ route("colony.hangar.dispatch", ["bay" => "__BAY__"]) }}',` ergänzen.

`public/js/colony-hexgrid.js` nach `doSalvage`:

```js
        // T30 §13.4: the find card's flight button dispatches the ship findAction() chose.
        async doFindFlight(tile) {
            const action = tile.find?.action;
            if (!action || action.action !== 'flight') return;
            const res = await this.post(this.routes.hangarDispatch.replace('__BAY__', action.bay), {
                mission_key: 'mission_find_salvage',
                difficulty: 'normal',
                target: { q: tile.q, r: tile.r },
            });
            if (res.ok) {
                if (res.tile) {
                    this.updateTile(res.tile);
                    this.selectedTile = res.tile;
                }
                this.updateAp(res);
                if (res.organika !== undefined) this.syncResbarChip?.('.res-Or', res.organika);
                this.$nextTick(() => this.redrawGrid());
            } else {
                this.showToast(res.message ?? res.error, 'error');
            }
        },
```

(Vorher lesen, wie `updateAp(res)` die Resourcebar setzt. Behandelt es `organika` nicht, wird der Organika-Chip nach demselben Muster wie `hangar.js::syncResbarChip` synchronisiert. Dafür eine kleine Hilfsfunktion in `colony-hexgrid.js` anlegen, nicht aus `hangar.js` importieren.)

- [ ] **Step 5: Hangar-Dialog**

`public/js/hangar.js`:

```js
        targetLabel(mission, target) {
            if (mission.target_type === 'knowledge') {
                return target.label + ' (Lv ' + target.level + ')';
            }
            if (mission.target_type === 'find_tile') {
                const carry = target.carry?.[this.missionModal.shipKey] ? '' : ' — ' + this.i18n.targetTooLarge;
                const carto = target.nav_ap < target.nav_ap_base ? ' · ' + this.i18n.targetCartography.replace(':ap', target.nav_ap_base - target.nav_ap) : '';
                return '(' + target.q + '|' + target.r + ') Ring ' + target.ring + ' · '
                    + this.i18n.targetFindYield.replace(':base', target.yield.base).replace(':bonus', target.yield.flight_bonus).replace(':total', target.yield.total)
                    + ' · ' + target.nav_ap + ' AP' + carto + ' · Or ' + target.organika
                    + ' · ' + this.i18n.missionReturnLabel.replace(':sol', target.return_sol)
                    + ' · ' + this.readinessLine(target.sol_distance) + carry;
            }
            return '(' + target.q + '|' + target.r + ') — Ring ' + target.ring;
        },

        /** Find targets the docked ship cannot carry or would not come back from stay visible but unselectable. */
        targetDisabled(mission, target) {
            return mission.target_type === 'find_tile'
                && (!target.carry?.[this.missionModal.shipKey] || this.readinessFor(target.sol_distance)?.ready === false);
        },

        /** T30 §13.x/7: the server-computed return guarantee (slot.ship.readiness) of the dialog's ship. */
        readinessFor(distance) {
            const slot = this.slots.find((s) => s.bay === this.missionModal.bay);
            return slot?.ship?.readiness?.[distance] ?? null;
        },

        /** "Verschleiß −6 · Zustand 14 → 8" or the blocking line with the repair AP — never greyed out silently. */
        readinessLine(distance) {
            const r = this.readinessFor(distance);
            if (!r) return '';
            const sp = this.slots.find((s) => s.bay === this.missionModal.bay).ship.status_points;
            return r.ready
                ? this.i18n.missionWearLine.replace(':wear', r.wear).replace(':from', sp).replace(':to', r.sp_after)
                : this.i18n.missionTooWorn.replace(':current', sp).replace(':wear', r.wear).replace(':ap', r.repair_ap);
        },
```

`hangar.blade.php`:
- Z. 533: `<option :value="ti" :disabled="targetDisabled(mission, target)" x-text="targetLabel(mission, target)"></option>`.
- Z. 540: `x-if="missionModal.selectedKey === mission.key && mission.roll"` (ohne Wurf keine Schwierigkeitswahl; `selectMission` wählt die einzige Option `normal` automatisch vor).
- i18n: `targetFindYield: @json(__("missions.target_find_yield")), targetTooLarge: @json(__("missions.target_too_large")), targetCartography: @json(__("missions.target_cartography")), missionWearLine: @json(__("missions.wear_line")), missionTooWorn: @json(__("missions.too_worn_line")),`.
- Missionskarte (nach den Chips Z. 516-524): `<p class="hangar-mission-gate-hint" x-show="mission.target_type !== 'find_tile'" x-text="readinessLine(mission.sol_distance)"></p>`. Der Start-Button wird zusätzlich deaktiviert mit `|| (mission.target_type !== 'find_tile' && readinessFor(mission.sol_distance)?.ready === false)`. Bei Bergungsflug-Zielen steht die Zeile im Ziel-Label.
- Der Reparatur-Knopf je Schiffskarte bleibt unverändert und gilt auch für das Vorfeld (Task 1 rendert die Karte generisch, Task 2 testet die Route mit `bay` 0).

Lang:

```php
// lang/de/missions.php
    'target_find_yield' => 'Fund :base Rg + Flug :bonus Rg = :total Rg',
    'target_too_large' => 'zu groß für dieses Schiff',
    'target_cartography' => 'Kartografie −:ap AP',
// lang/en/missions.php
    'target_find_yield' => 'Find :base Rg + flight :bonus Rg = :total Rg',
    'target_too_large' => 'too large for this ship',
    'target_cartography' => 'Cartography −:ap AP',
// lang/de/missions.php (Ergänzung)
    'wear_line' => 'Verschleiß −:wear · Zustand :from → :to',
    'too_worn_line' => 'Zustand :current reicht nicht für Verschleiß :wear – erst reparieren (:ap AP)',
// (en) 'wear_line' => 'Wear −:wear · condition :from → :to', 'too_worn_line' => 'Condition :current is not enough for wear :wear – repair first (:ap AP)',
// lang/de/colony.php
    'find_flight' => 'Bergungsflug',
    'hangar_build_notice' => 'Danach werden Funde nur noch per Schiff geborgen; große Funde brauchen Stufe 2.',
    'onboarding_hint_find_flight' => 'Ab jetzt holen Schiffe die Funde ab: mehr Ertrag, kaum AP. Große Funde brauchen einen Frachter.',
    'find_help' => 'Insgesamt gibt es :total Signale in diesem Lauf; manche sind Fehlalarme. Ein Tiefenscan zeigt, ob und wie viel zu bergen ist. Ohne Landefeld gräbt die Kolonie selbst, Schicht für Schicht. Steht der Hangar, fliegen die Schiffe die Funde ab, und es kommt mehr ins Lager als zu Fuß. Große Funde sind zu schwer für die Drohne und warten auf einen Frachter.',
// lang/en/colony.php
    'find_flight' => 'Salvage flight',
    'hangar_build_notice' => 'Afterwards finds are recovered by ship only; large finds need level 2.',
    'onboarding_hint_find_flight' => 'From now on ships fetch the finds: more yield, hardly any AP. Large finds need a freighter.',
    'find_help' => 'There are :total signals in this run; some are false alarms. A deep scan shows whether and how much there is to salvage. Without a landing field the colony digs on foot, layer by layer. Once the hangar stands, ships fly the finds home and more reaches the stores than on foot. Large finds are too heavy for the drone and wait for a freighter.',
// lang/de/buildings.php:29
    'hangar_desc' => 'Landefeld mit Andockbuchten. Jede Ausbaustufe öffnet eine weitere Bucht und die nächste Schiffsklasse; auf dem Vorfeld steht die Geschenk-Drohne.',
// lang/en/buildings.php
    'hangar_desc' => 'Landing field with docking bays. Every level opens another bay and the next ship class; the gift drone stands on the apron.',
```

Baukarte `hexview.blade.php` nach Z. 459 (Ende `building-list-row--costs`):

```blade
                                        <p class="building-list-notice" x-show="b.notice" x-text="b.notice"></p>
```

Stil `building-list-notice` (Hilfstext, gedämpft, kleine Schrift) gemäß `docs/design-system/` in der bestehenden CSS-Datei der Bauliste ergänzen. Den Ort per `grep -rn "building-list-cost-breakdown" public/css` finden.

- [ ] **Step 6: Grün + Format**

Run: `bin/phpunit --filter='FindFlightUiTest|HangarControllerTest|OnboardingHintServiceTest|BuildingInstanceCapTest'` → PASS.
Dann `npx prettier --write resources/views/colony/hangar.blade.php resources/views/colony/hexview.blade.php resources/views/colony/partials/tile-terrain.blade.php` zweimal ausführen.

- [ ] **Step 7: Playwright-Check (echte Alpine-Darstellung)**

Gegen eine Wegwerf-DB, **nie** `nouron`. Port 8000 gehört dem Owner, deshalb Port 8010:

```bash
DB_DATABASE=nouron_playtest php artisan migrate:fresh --seed --force
DB_DATABASE=nouron_playtest php artisan game:reset-player bart --yes --scenario=phase2 --path=hangar
DB_DATABASE=nouron_playtest php -S 127.0.0.1:8010 -t public server.php   # Server per PID beenden, nicht pkill -f
```

Ein Playwright-Skript im Scratchpad meldet Bart (`test123`) an. Dann eine gescannte `find_medium`-Kachel per SQL vorbereiten (Ring 2, `is_explored` 1). Prüfen: (a) Die Fundkarte zeigt **genau einen** Aktionsknopf („Bergungsflug“ mit AP-Chip) und die Zeile „Fund 10 Rg + Flug 5 Rg = 15 Rg …“, kein „Bergen“-Knopf. (b) Nach dem Klick zeigt die Karte „Im Flug. Zurück an Sol N.“, und AP- und Organika-Chip der Resourcebar sinken ohne Reload. (c) Der Hangar-Screen zeigt „Vorfeld“ mit der Drohne, „Bucht 1“ leer, „Bucht 2/3: ab Stufe n“. Im Dispatch-Dialog der Drohne ist ein `find_large`-Ziel ausgegraut („zu groß für dieses Schiff“), und der Bergungsflug hat keine Schwierigkeitswahl. (d) Drohne per SQL auf 5 SP setzen und einen gescannten Ring-3-Fund anlegen: Die Fundkarte zeigt „Wartet: Drohne reparieren (1 AP)“ ohne Aktionsknopf. Im Dialog steht beim Ring-3-Ziel „Zustand 5 reicht nicht für Verschleiß 6 – erst reparieren (1 AP)“, die Zeile ist nicht wählbar. Nach einem Klick auf „Reparieren“ auf der Vorfeld-Karte steht dort „Verschleiß −6 · Zustand 7 → 1“, und der AP-Chip der Resourcebar sinkt um 1. Vor dem Klick die Marker-Prüfung machen (Memory: `artisan serve` ignoriert `DB_DATABASE`). Screenshots kommen in den Task-Report.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Colony/HangarController.php app/Http/Controllers/Colony/ColonyController.php app/Services/ColonyTileService.php app/Services/OnboardingHintService.php resources/views/colony/hangar.blade.php resources/views/colony/hexview.blade.php resources/views/colony/partials/tile-terrain.blade.php public/js/hangar.js public/js/colony-hexgrid.js public/css lang/de lang/en tests/Feature/Hangar/FindFlightUiTest.php
git commit -m "feat(T30): Fundkarte mit einer Aktion + Begruendung, Zielliste im Hangar, Hinweise"
```

---

### Task 8: PlaytestBot und Report

**Files:**
- Modify: `tests/Feature/Playtest/BotStrategy.php` (Z. 50-58 Konstanten, Z. 233-245 Regel, Z. 1613-1628 Kandidat, Z. 1034 `instanceCap`, Z. 2057-2096 `investFindCandidate`; neu `findFlightCandidate`, `repairForFlightCandidate`, Regel `repair_ship_for_flight`)
- Modify: `tests/Feature/Playtest/RunReport.php:30-46, 112-140, 160, 215-302 (snapshot), 447-451`
- Modify: `app/Support/OpeningComparison.php` (Z. 26-66 Docblock, 94-108, 163-263, 313-335, 377-479, 502-510)
- Modify: `app/Console/Commands/PlaytestCompare.php:30-33`
- Modify: `tools/playtest-dashboard.php:345-350, 404-409, 467`
- Modify: `tests/Feature/Playtest/BotStrategyMissionChoiceTest.php:27-95` (Regolith-Missions-Tests löschen)
- Test: `tests/Feature/Playtest/BotStrategyFindFlightTest.php` (neu), `tests/Feature/Playtest/RunReportFindSourceTest.php` (ergänzen), `tests/Unit/OpeningComparisonTest.php` (ergänzen), `tests/Feature/Console/PlaytestCompareTest.php` (ergänzen)

**Spec-Abweichung:** Die Annahme „der Bot repariert Schiffe“ (§13.3 Bot) trifft nicht zu. Es gibt keine Schiffsreparatur-Regel (nur `repair_critical`/`repair_maintenance` für Gebäude). Neu ist deshalb die Regel `repair_ship_for_flight` (Owner §13.x/11) direkt vor `dispatch_find_flight`, sonst entsteht die Bot-Falle aus §13.4 (ii). Sie repariert nur, wenn ein Fund laut `findAction` mit Grund `ship_worn` wartet, und zwar an der Bucht, die `findAction` nennt. Eine pauschale SP-Schwelle gibt es nicht. Die übrigen Missionskandidaten nutzen schon seit Task 2 `flightReadiness`. Außerdem zeigte das Dashboard den Topf `find` aus Pool v1 noch gar nicht an, er wird zusammen mit `find_flight` ergänzt.

**Interfaces:**
- Consumes: `ColonyTileService::findAction` (Task 6), `FIND_TYPES`, Route `POST /colony/hangar/{bay}/dispatch|repair` (Task 1), Event-Details `find_regolith` (Task 5).
- Produces: Bot-Regeln `repair_ship_for_flight` und `dispatch_find_flight` (an der früheren Stelle von `dispatch_regolith_mission`). Im Report je Sol `ap.ship_repair_spent`. `OpeningComparison::metrics()` liefert `ship_repair_4_12` und `ship_repair_25` (Information, §13.8). Im Report je Sol `regolith_sources.find_flight` und `find_waits{needs_level,needs_freighter,ship_busy,no_ship_ready,zone_blocked}`. `OpeningComparison::metrics()` liefert zusätzlich `k12` (?float, %), `k16_needs_level`, `k16_needs_freighter`, `k16_ship_busy`, `k16_no_ship_ready`, `k17` (int, Sole), dazu die Checks `K12`, `K16`, `K17` mit Status `info`.

- [ ] **Step 1: Failing Bot-Tests** `tests/Feature/Playtest/BotStrategyFindFlightTest.php`

```php
<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\AdvisorService;
use App\Services\HangarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T30 §13.3/§13.4: with a hangar the bot flies finds, never opens self-salvage, repairs ships, keeps scanning. */
class BotStrategyFindFlightTest extends TestCase
{
    use RefreshDatabase;

    private function bootWithHangar(int $level): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('colony_buildings')->insert(['colony_id' => $bot->colonyId, 'building_id' => BuildingId::Hangar->value,
            'instance_id' => 1, 'level' => $level, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => -1, 'tile_y' => 1]);
        app(HangarService::class)->ensureGiftDrone($bot->colonyId);
        DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 5)->update(['amount' => 50]);
        $this->giveAp($bot, 10);

        return $bot;
    }

    private function giveAp(BotSession $bot, int $ap): void
    {
        $service = app(AdvisorService::class);
        DB::table('locked_actionpoints')->where('scope_type', 'colony')->where('scope_id', $bot->colonyId)->delete();
        $service->lockActionPoints($bot->colonyId, $service->getTotalActionPoints($bot->colonyId) - $ap);
    }

    private function makeFind(BotSession $bot, string $type, bool $scanned = true, int $spent = 0, int $ring = 2): object
    {
        $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->whereNull('event_type')
            ->where('is_colony_zone', 0)->where('ring', $ring)->orderBy('q')->orderBy('r')->first();
        DB::table('colony_tiles')->where('id', $tile->id)->update(['event_type' => $type, 'is_explored' => 1,
            'is_deep_scanned' => $scanned ? 1 : 0, 'salvage_ap_spent' => $spent, 'salvage_tick' => null]);

        return DB::table('colony_tiles')->where('id', $tile->id)->first();
    }

    private function rule(string $name): array
    {
        return collect(BotStrategy::default())->firstWhere('name', $name) ?? $this->fail("rule {$name} missing");
    }

    public function test_flies_the_largest_carryable_find_with_the_smallest_class(): void
    {
        $bot = $this->bootWithHangar(1);
        $this->makeFind($bot, 'find_small');
        $medium = $this->makeFind($bot, 'find_medium');
        $this->makeFind($bot, 'find_large'); // locked at level 1

        $c = $this->rule('dispatch_find_flight')['when']($bot);

        $this->assertSame([0, (int) $medium->q, (int) $medium->r], [$c['bay'], $c['q'], $c['r']]);
        $this->assertTrue($this->rule('dispatch_find_flight')['do']($bot, $c)['ok']);
    }

    public function test_locked_large_find_alone_gives_no_candidate_and_no_error(): void
    {
        $bot = $this->bootWithHangar(1);
        $this->makeFind($bot, 'find_large');

        $this->assertEmpty($this->rule('dispatch_find_flight')['when']($bot));
        $this->assertEmpty($this->rule('invest_find')['when']($bot), 'no self-salvage with a hangar');
    }

    public function test_grandfathered_project_is_continued_after_the_hangar_stands(): void
    {
        $bot = $this->bootWithHangar(1);
        $tile = $this->makeFind($bot, 'find_small', spent: 4);

        $c = $this->rule('invest_find')['when']($bot);

        $this->assertSame([(int) $tile->q, (int) $tile->r], [$c->q, $c->r]);
    }

    public function test_hangar_colony_with_only_unscanned_signals_scans_first(): void
    {
        $bot = $this->bootWithHangar(1);
        $tile = $this->makeFind($bot, 'find_small', scanned: false);

        $this->assertEmpty($this->rule('dispatch_find_flight')['when']($bot));
        $this->assertSame((int) $tile->id, (int) $this->rule('deep_scan_signal_tile')['when']($bot)->id);
    }

    public function test_worn_gift_drone_gets_repaired_then_flies(): void
    {
        $bot = $this->bootWithHangar(1);
        $this->makeFind($bot, 'find_small'); // ring 2: drone wear 3
        DB::table('colony_ships')->where('colony_id', $bot->colonyId)->where('bay', 0)->update(['status_points' => 2]);
        $this->assertEmpty($this->rule('dispatch_find_flight')['when']($bot));

        $c = $this->rule('repair_ship_for_flight')['when']($bot);
        $this->assertSame(0, $c['bay']);
        $this->assertTrue($this->rule('repair_ship_for_flight')['do']($bot, $c)['ok']);

        $this->assertSame(4.0, (float) DB::table('colony_ships')->where('colony_id', $bot->colonyId)->where('bay', 0)->value('status_points'));
        $this->assertEmpty($this->rule('repair_ship_for_flight')['when']($bot), 'ready now — no further repair');
        $this->assertNotEmpty($this->rule('dispatch_find_flight')['when']($bot));
    }

    public function test_bot_repairs_before_a_ring_3_flight_instead_of_starting(): void
    {
        $bot = $this->bootWithHangar(1);
        $this->makeFind($bot, 'find_medium', ring: 3); // drone wear 6
        DB::table('colony_ships')->where('colony_id', $bot->colonyId)->where('bay', 0)->update(['status_points' => 5]);

        $this->assertEmpty($this->rule('dispatch_find_flight')['when']($bot), 'old 25 % floor would have started and aborted');
        $this->assertSame(0, $this->rule('repair_ship_for_flight')['when']($bot)['bay']);
    }

    public function test_no_repair_without_a_waiting_find(): void
    {
        $bot = $this->bootWithHangar(1);
        DB::table('colony_ships')->where('colony_id', $bot->colonyId)->where('bay', 0)->update(['status_points' => 2]);

        $this->assertEmpty($this->rule('repair_ship_for_flight')['when']($bot));
    }

    public function test_gift_drone_counts_as_first_ship_and_bays_gate_purchases(): void
    {
        $bot = $this->bootWithHangar(1);
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['credits' => 5000]);

        $this->assertEmpty($this->rule('request_ship')['when']($bot), 'no bootstrap drone: the gift drone is there (advisors < 3)');
    }

    public function test_rule_order(): void
    {
        $names = array_column(BotStrategy::default(), 'name');
        $this->assertNotContains('dispatch_regolith_mission', $names);
        $this->assertSame(array_search('dispatch_find_flight', $names, true) - 1, array_search('repair_ship_for_flight', $names, true), 'directly before');
        $this->assertLessThan(array_search('dispatch_credit_mission', $names, true), array_search('dispatch_find_flight', $names, true));
    }
}
```

`BotStrategyMissionChoiceTest`: Die Tests Z. 27-95 (`test_freighter_flies_the_supply_run…` bis `test_regolith_mission_outranks_the_compounds_mission`) löschen, Klassen-Docblock auf „Recon bleibt die Routine der Drohne“ kürzen. Konstante `KNOWLEDGE_GEOLOGY` entfernen, falls unbenutzt.

- [ ] **Step 2: Failing Report- und Vergleichstests**

`tests/Feature/Playtest/RunReportFindSourceTest.php` ergänzen:

```php
    public function test_find_flight_comes_from_the_mission_event_and_leaves_the_harvester_share(): void
    {
        $bot = BotSession::boot($this, 1);
        $bot->sol = 1;
        \Illuminate\Support\Facades\DB::table('colony_log')->insert(['user' => $bot->userId, 'tick' => 1, 'event' => 'hangar.mission_completed',
            'area' => 'colony', 'parameters' => json_encode(['rewards' => ['find_type' => 'find_medium', 'find_regolith' => 15, 'flight_bonus' => 5]]),
            'is_read' => true, 'created_at' => now()]);
        $this->pushEntry($bot, 'sol_next', 100, 140);

        $report = new RunReport(1);
        $report->snapshot($bot);
        $data = $report->build($bot);

        $this->assertSame(15, $data['sols'][0]['regolith_sources']['find_flight']);
        $this->assertSame(25, $data['sols'][0]['regolith_sources']['harvester']);
        $this->assertSame(15, $data['regolith_path_attribution']['pfad_b_frachter']);
    }

    public function test_snapshot_counts_find_waits_by_reason(): void
    {
        $bot = BotSession::boot($this, 1);
        \Illuminate\Support\Facades\DB::table('colony_buildings')->insert(['colony_id' => $bot->colonyId, 'building_id' => 44,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => -1, 'tile_y' => 1]);
        app(\App\Services\HangarService::class)->ensureGiftDrone($bot->colonyId);
        $tile = \Illuminate\Support\Facades\DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('is_colony_zone', 1)
            ->whereNull('event_type')->where('q', '!=', 0)->first();
        \Illuminate\Support\Facades\DB::table('colony_tiles')->where('id', $tile->id)
            ->update(['event_type' => 'find_large', 'is_explored' => 1, 'is_deep_scanned' => 1]);

        $report = new RunReport(1);
        $report->snapshot($bot);

        $this->assertSame(['needs_level' => 1, 'needs_freighter' => 0, 'ship_busy' => 0, 'no_ship_ready' => 0, 'zone_blocked' => 1],
            $report->build($bot)['sols'][0]['find_waits']);
    }
```

`tests/Unit/OpeningComparisonTest.php`: Im `report()`-Helfer zwei Optionen ergänzen: `'find_flight' => $o['find_flight'][$s] ?? 0` in `regolith_sources` und `'find_waits' => $o['waits'][$s] ?? ['needs_level' => 0, 'needs_freighter' => 0, 'ship_busy' => 0, 'no_ship_ready' => 0, 'zone_blocked' => 0]` im Snapshot (Docblock der Optionen ergänzen). Neue Tests:

```php
    public function test_hangar_k2_counts_find_flight_regolith(): void
    {
        $r = $this->report('hangar', 1, ['lv1' => 3, 'find_flight' => [5 => 6]]);

        $this->assertSame(2, OpeningComparison::metrics($r)['k2']);
    }

    public function test_k11_adds_find_flight_and_k12_is_its_share_at_sol_25(): void
    {
        $r = $this->report('hangar', 1, ['last' => 30, 'find' => [4 => 4], 'find_flight' => [6 => 6, 12 => 15, 26 => 99]]);

        $m = OpeningComparison::metrics($r);

        $this->assertSame(10, $m['k11_10']);
        $this->assertSame(25, $m['k11_25']);
        $this->assertSame(84.0, $m['k12']);
    }

    public function test_k16_and_k17_count_sols_per_reason(): void
    {
        $wait = fn (array $w) => $w + ['needs_level' => 0, 'needs_freighter' => 0, 'ship_busy' => 0, 'no_ship_ready' => 0, 'zone_blocked' => 0];
        $r = $this->report('hangar', 1, ['waits' => [5 => $wait(['needs_level' => 1, 'zone_blocked' => 1]), 6 => $wait(['needs_level' => 2]), 7 => $wait(['no_ship_ready' => 1])]]);

        $m = OpeningComparison::metrics($r);

        $this->assertSame([2, 0, 0, 1, 1], [$m['k16_needs_level'], $m['k16_needs_freighter'], $m['k16_ship_busy'], $m['k16_no_ship_ready'], $m['k17']]);
    }

    public function test_ship_repair_ap_is_summed_for_sol_4_to_12_and_before_25(): void
    {
        $r = $this->report('hangar', 1, ['last' => 30]);
        foreach ($r['sols'] as $i => $s) {
            $r['sols'][$i]['ap']['ship_repair_spent'] = in_array($s['sol'], [3, 5, 12, 20, 25], true) ? 2 : 0;
        }

        $m = OpeningComparison::metrics($r);

        $this->assertSame([4, 8], [$m['ship_repair_4_12'], $m['ship_repair_25']]);
    }

    public function test_k12_k16_k17_are_info_checks(): void
    {
        $result = OpeningComparison::fromReports([$this->report('labor', 1), $this->report('hangar', 1)], ['labor', 'hangar'])->compare();

        foreach (['K12', 'K16', 'K17', 'REP'] as $k) {
            $this->assertSame('info', $result['checks'][$k]['status'], $k);
        }
    }
```

`tests/Feature/Console/PlaytestCompareTest.php`: analog zum K11-Test (Z. 78) auf die Zeilen `K12 Schiffsanteil Pool-Rg`, `K16 Warten Stufe` und `K17 Zonen-Blockade` prüfen.

- [ ] **Step 3: Rot bestätigen**

Run: `bin/phpunit --testsuite=playtest --filter='BotStrategyFindFlightTest|RunReportFindSourceTest'` und `bin/phpunit --filter='OpeningComparisonTest|PlaytestCompareTest'` → FAIL.

- [ ] **Step 4: `BotStrategy`**

- Z. 50-58: `REGOLITH_SCARCE_BELOW` und `REGOLITH_MISSIONS` löschen.
- Regel Z. 233-245 ersetzen durch zwei Regeln an derselben Stelle:

```php
            [
                'name' => 'repair_ship_for_flight',
                // T30 §13.x/11: ships wear only in flight; a find that waits only because its
                // ship misses the return guarantee (findAction reason ship_worn) gets one repair
                // click per rule pass until the ship is ready. Without this a worn gift drone
                // would leave every find on the map (K16 no_ship_ready = bot bug, not balance).
                'when' => fn (BotSession $b) => self::availableAp($b) >= (int) config('game.ship_repair.ap_per_click', 1)
                    ? self::repairForFlightCandidate($b)
                    : null,
                'do' => fn (BotSession $b, array $c) => $b->act('repair_ship_for_flight', 'POST', "/colony/hangar/{$c['bay']}/repair"),
            ],
            [
                'name' => 'dispatch_find_flight',
                // T30 §13.3: with a hangar, finds are salvaged by flight only. Ship, lock and
                // wait reasons come from ColonyTileService::findAction() — the same source the
                // guards and the find card read, so bot and player see one rule.
                'when' => fn (BotSession $b) => self::findFlightCandidate($b),
                'do' => fn (BotSession $b, array $c) => $b->act('dispatch_find_flight', 'POST', "/colony/hangar/{$c['bay']}/dispatch", [
                    'mission_key' => 'mission_find_salvage',
                    'difficulty' => 'normal',
                    'target' => ['q' => $c['q'], 'r' => $c['r']],
                ]),
            ],
```

- `regolithMissionCandidate` (Z. 1613-1628) durch die beiden Kandidaten ersetzen:

```php
    /** @return array{bay:int, q:int, r:int}|null largest flyable find first; ship as chosen by findAction() */
    private static function findFlightCandidate(BotSession $b): ?array
    {
        $tiles = app(ColonyTileService::class);
        $types = config('game.finds.types');
        $best = DB::table('colony_tiles')
            ->where('colony_id', $b->colonyId)->where('is_deep_scanned', 1)
            ->whereIn('event_type', ColonyTileService::FIND_TYPES)
            ->orderBy('ring')->orderBy('q')->orderBy('r')->get()
            ->map(fn ($t) => ['tile' => $t, 'action' => $tiles->findAction($b->colonyId, $t)])
            ->filter(fn ($c) => $c['action']['action'] === 'flight'
                && self::availableAp($b) >= $c['action']['costs']['nav_ap']
                && self::organics($b) >= $c['action']['costs']['organika'])
            ->sortByDesc(fn ($c) => (int) $types[$c['tile']->event_type]['rg'])
            ->first();

        return $best === null ? null
            : ['bay' => (int) $best['action']['bay'], 'q' => (int) $best['tile']->q, 'r' => (int) $best['tile']->r];
    }

    /** @return array{bay:int}|null the bay findAction() names for the largest find waiting on a worn ship */
    private static function repairForFlightCandidate(BotSession $b): ?array
    {
        $tiles = app(ColonyTileService::class);
        $types = config('game.finds.types');
        $worn = DB::table('colony_tiles')
            ->where('colony_id', $b->colonyId)->where('is_deep_scanned', 1)
            ->whereIn('event_type', ColonyTileService::FIND_TYPES)
            ->orderBy('ring')->orderBy('q')->orderBy('r')->get()
            ->map(fn ($t) => ['tile' => $t, 'action' => $tiles->findAction($b->colonyId, $t)])
            ->filter(fn ($c) => $c['action']['reason'] === 'ship_worn')
            ->sortByDesc(fn ($c) => (int) $types[$c['tile']->event_type]['rg'])
            ->first();

        return $worn === null ? null : ['bay' => (int) $worn['action']['bay']];
    }
```

(`ColonyTileService` ist schon importiert, Z. 2030; `HangarBays` seit Task 2.)
- `investFindCandidate` (Z. 2066-2071): Die Kacheln laufen zusätzlich durch `findAction`, nur Selbstbergung oder Bestandsschutz zählt:

```php
        $service = app(ColonyTileService::class);
        $tiles = DB::table('colony_tiles')
            ->where('colony_id', $b->colonyId)
            ->where('is_deep_scanned', 1)
            ->whereIn('event_type', ColonyTileService::FIND_TYPES)
            ->orderBy('ring')->orderBy('q')->orderBy('r')
            ->get()
            // T30 §13.4: with a hangar only grandfathered projects continue; without, as in Pool v1.
            ->filter(fn ($t) => in_array($service->findAction($b->colonyId, $t)['action'], ['self_salvage', 'continue_salvage'], true))
            ->values();
```

  (Der Docblock Z. 2047-2055 bekommt den Satz „Mit Hangar nur Bestandsschutz-Projekte (T30 §13.4).“)
- Z. 1034: `$instanceCap = [28 => 2]; // housingComplex — the hangar is a single hall since B1 (T30 §13.2)`. Den Kommentar Z. 1022-1033 auf das Wohnhabitat kürzen.

- [ ] **Step 5: `RunReport`**

- `AP_CATEGORY_MAP`: `'dispatch_regolith_mission' => 'action',` → `'dispatch_find_flight' => 'action',` und `'repair_ship_for_flight' => 'repair',`.
- `snapshot()`, Block `'ap' => [...]`: `'ship_repair_spent' => $this->ruleApSpent($bot, 'repair_ship_for_flight'),` mit

```php
    /** AP actually spent this Sol by one rule (positive ap_before − ap_after of ok entries). */
    private function ruleApSpent(BotSession $bot, string $rule): int
    {
        $sum = 0;
        foreach ($bot->log as $entry) {
            if ($entry['sol'] === $bot->sol && $entry['rule'] === $rule && $entry['ok']) {
                $sum += max(0, ($entry['ap_before'] ?? 0) - ($entry['ap_after'] ?? 0));
            }
        }

        return $sum;
    }
```
- `regolithSources`: `$sources` bekommt `'find_flight' => 0`. Der `sol_next`-Zweig wird zu:

```php
            } elseif ($entry['rule'] === 'sol_next') {
                $missionAmount = $this->missionRewardAmount($bot, 'regolith');
                // T30 §13.3: salvage flights log their Regolith as rewards.find_regolith.
                $flightAmount = $this->missionRewardAmount($bot, 'find_regolith');
                $sources['mission'] += $missionAmount;
                $sources['find_flight'] += $flightAmount;
                $sources['harvester'] += max(0, $delta - $missionAmount - $flightAmount);
            }
```

- Z. 160 `$dispatchRules`: `'dispatch_regolith_mission'` → `'dispatch_find_flight'`.
- Im `snapshot()`-Array nach `'regolith_sources'`: `'find_waits' => $this->findWaits($colonyId),`. Methode:

```php
    /**
     * T30 K16/K17: scanned real finds waiting while a hangar stands, by findAction() reason
     * (§13.8 grouping); zone_blocked = locked finds on a colony-zone tile.
     *
     * @return array{needs_level:int, needs_freighter:int, ship_busy:int, no_ship_ready:int, zone_blocked:int}
     */
    private function findWaits(int $colonyId): array
    {
        $map = ['needs_level' => 'needs_level', 'needs_freighter' => 'needs_freighter', 'ship_inactive' => 'needs_freighter',
            'ship_busy' => 'ship_busy', 'ship_worn' => 'no_ship_ready', 'no_ship' => 'no_ship_ready'];
        $waits = ['needs_level' => 0, 'needs_freighter' => 0, 'ship_busy' => 0, 'no_ship_ready' => 0, 'zone_blocked' => 0];
        $service = app(ColonyTileService::class);
        foreach (DB::table('colony_tiles')->where('colony_id', $colonyId)->where('is_deep_scanned', 1)
            ->whereIn('event_type', ColonyTileService::FIND_TYPES)->get() as $tile) {
            $key = $map[$service->findAction($colonyId, $tile)['reason']] ?? null;
            if ($key === null) {
                continue;
            }
            $waits[$key]++;
            if (in_array($key, ['needs_level', 'needs_freighter'], true) && (int) $tile->is_colony_zone === 1) {
                $waits['zone_blocked']++;
            }
        }

        return $waits;
    }
```

- Z. 450: `$regolithPathAttribution['pfad_b_frachter'] += $sol['regolith_sources']['mission'] + ($sol['regolith_sources']['find_flight'] ?? 0);`.

- [ ] **Step 6: `OpeningComparison` + `PlaytestCompare` + Dashboard**

- `PATH_RULES['hangar']`: `'dispatch_find_flight'` und `'repair_ship_for_flight'` ergänzen. `'dispatch_regolith_mission'` bleibt für ältere Reports (Kommentar).
- `YIELD_LABELS['hangar']` → `'first mission or find-flight Rg'`. `hasPathYield` hangar → `(($snapshot['regolith_sources']['mission'] ?? 0) + ($snapshot['regolith_sources']['find_flight'] ?? 0)) > 0`.
- `$findBefore`: Summand `(int) ($s['regolith_sources']['find'] ?? 0) + (int) ($s['regolith_sources']['find_flight'] ?? 0)`. Dazu kommt eine zweite Closure `$flightBefore(int $sol)` mit derselben Null-Logik, die nur `find_flight` summiert.
- Rückgabe von `metrics()` ergänzen:

```php
            'k12' => ($k11_25 = $findBefore(25)) ? round($flightBefore(25) / $k11_25 * 100, 1) : null,
            'k16_needs_level' => self::solsWith($sols, 'needs_level'),
            'k16_needs_freighter' => self::solsWith($sols, 'needs_freighter'),
            'k16_ship_busy' => self::solsWith($sols, 'ship_busy'),
            'k16_no_ship_ready' => self::solsWith($sols, 'no_ship_ready'),
            'k17' => self::solsWith($sols, 'zone_blocked'),
            // §13.8 information next to K4: repair AP spent (Sol 4-12 / Sols 1-24).
            'ship_repair_4_12' => array_sum(array_map(fn ($s) => $s['sol'] >= 4 && $s['sol'] <= 12 ? (int) ($s['ap']['ship_repair_spent'] ?? 0) : 0, $sols)),
            'ship_repair_25' => array_sum(array_map(fn ($s) => $s['sol'] < 25 ? (int) ($s['ap']['ship_repair_spent'] ?? 0) : 0, $sols)),
```

  In `compare()` die beiden Schlüssel ebenfalls in die KPI-Liste aufnehmen. Ein Info-Check `'REP' => $info('Ship repair AP Sol 4-12, median', $kpis['ship_repair_4_12'], 'information next to K4')` kommt dazu, `$na`-Liste um `'REP'` erweitern. `PlaytestCompare::KPI_LABELS`: `'ship_repair_4_12' => 'Reparatur-AP Sol 4-12', 'ship_repair_25' => 'Reparatur-AP bis Sol 25'`.

(`$k11_25` vor dem `return` als Variable berechnen statt inline zuzuweisen, Pint lässt Inline-Zuweisungen nicht durch.)

```php
    /** Number of snapshots whose find_waits[$reason] > 0 (T30 K16/K17). */
    private static function solsWith(array $sols, string $reason): int
    {
        return count(array_filter($sols, fn ($s) => (int) ($s['find_waits'][$reason] ?? 0) > 0));
    }
```

- `compare()` Z. 315: Schlüsselliste um `'k12', 'k16_needs_level', 'k16_needs_freighter', 'k16_ship_busy', 'k16_no_ship_ready', 'k17'` erweitern.
- `checks()`: Die `$na`-Liste Z. 381 bekommt `'K12', 'K16', 'K17'`. Neue Info-Checks vor dem `return`:

```php
        $info = fn (string $label, array $byOpening, string $target) => [
            'status' => 'info',
            'detail' => $label.': '.implode('; ', array_map(fn ($o, $s) => "{$o} ".self::fmt($s['median']), array_keys($byOpening), $byOpening))." ({$target})",
        ];
```

  Im Rückgabe-Array ergänzen:

```php
            'K12' => $info('Pool-Rg share by ship at Sol 25, median %', $kpis['k12'], 'target hangar >= 85, warn < 70'),
            'K16' => $info('Sols waiting (needs_level / needs_freighter / ship_busy / no_ship_ready = bot bug if > 3), median of no_ship_ready', $kpis['k16_no_ship_ready'], 'see per-reason rows'),
            'K17' => $info('Sols with a locked find on a zone tile, median', $kpis['k17'], 'warn: > 0 in more than 2 of 8 runs'),
```

- Docblock der Klasse um die Definitionen von K12, K16 und K17 erweitern (je ein Spiegelstrich wie bei K11).
- `PlaytestCompare::KPI_LABELS` ergänzen: `'k12' => 'K12 Schiffsanteil Pool-Rg %', 'k16_needs_level' => 'K16 Warten Stufe', 'k16_needs_freighter' => 'K16 Warten Frachter', 'k16_ship_busy' => 'K16 Warten Schiff unterwegs', 'k16_no_ship_ready' => 'K16 Warten kein Schiff bereit', 'k17' => 'K17 Zonen-Blockade'`. Beschreibung Z. 30: „K1-K7, informational K11/K12/K16/K17“.
- `tools/playtest-dashboard.php`: Z. 345 `totals` um `find: 0, find_flight: 0` ergänzen, Labels um `find: 'Fund (selbst)', find_flight: 'Bergungsflug'`. Z. 404-409 und 467: Schlüssel-, Label- und Farbarrays um die beiden Töpfe erweitern.

- [ ] **Step 7: Grün**

Run: `bin/phpunit --testsuite=playtest --filter='BotStrategy|RunReport'` → PASS.
Run: `bin/phpunit --filter='OpeningComparisonTest|PlaytestCompareTest'` → PASS.

- [ ] **Step 8: Commit**

```bash
git add tests/Feature/Playtest/BotStrategy.php tests/Feature/Playtest/RunReport.php tests/Feature/Playtest/BotStrategyFindFlightTest.php tests/Feature/Playtest/BotStrategyMissionChoiceTest.php tests/Feature/Playtest/RunReportFindSourceTest.php app/Support/OpeningComparison.php app/Console/Commands/PlaytestCompare.php tools/playtest-dashboard.php tests/Unit/OpeningComparisonTest.php tests/Feature/Console/PlaytestCompareTest.php
git commit -m "feat(T30): Bot dispatch_find_flight/repair_ship_for_flight, Report find_flight + find_waits, K12/K16/K17"
```

---

### Task 9: Doku, volle Verifikation, Messvorbereitung

**Files:**
- Modify: `docs/game-reference.md` (§7 Schiffe: `find_carry`, Schiffs-Reparatur aus `game.ship_repair` (nur AP), Rückkehr-Garantie statt 25-%-Sperre; §8 Missionen: Bergungsflug, Prospektion und Signalvermessung raus, Versorgungsfahrt nur Organika; „Hangar-Missionen: Schwierigkeit“: Bergungsflug ohne Wurf; §17a Fundpool: `finds.flight`, Ablösungsregel; Hangar-Kosten-Zeile: eine Halle, Stufe = Klasse + Bucht)
- Modify: `docs/GDD.md` (zahlenfrei, über `game-designer`: §4a Z. ~498 Fundpool, §4b/§13.5 Hangar-Pfad, §4c Z. 804 „Hangar: der einzige Fall mit beiden Achsen“ → „Hangar: eine Halle, Stufe = Klasse und Bucht; Vorfeld für die Geschenk-Drohne“, §6 Schiffe/Supply, §7 Z. 1120 und Schiffs-Verschleiß: Startsperre = Rückkehr-Garantie, Config-Ort `game.ship_repair` statt `config/ships.php`, die Credit-Kosten-Idee `repair_cost_per_point` streichen, §8b Z. 1244-1362 Akquise/Besitzmodell/Katalog/Kosten/Resolution, Siegziele `task_expedition_coverage`; vollständige Liste in Spec §13.9 „GDD nachziehen“)
- Modify: `docs/superpowers/specs/2026-10-05-t30-gleichwertige-eroeffnungen.md` (§13.7: Status „umgesetzt“ + Verweis auf diesen Plan; Spec-Abweichungen aus diesem Plan als kurze Liste)
- Modify: `ROADMAP.md:223` (T30-Stand: Schritt 3 umgesetzt, Messung offen)
- Modify: `CHANGELOG.md` (Block `## 2026-10-11` bzw. Tagesdatum)

Doku-Task ohne Codepfad (TDD-Ausnahme). Die Verifikation läuft trotzdem als Pflichtschritt.

- [ ] **Step 1: Doku schreiben.** Zahlen nur in `game-reference.md` (aus Config abgeschrieben, nicht aus der Spec). Das GDD bleibt zahlenfrei, mit Fußnote auf `game-reference.md`. CHANGELOG kurz:

```markdown
## 2026-10-11

- Feat: T30 Schritt 3 (Spec §13) — Hangar ist eine Halle (Stufe = Schiffsklasse und Buchten, Vorfeld mit Geschenk-Drohne); gescannte Funde werden mit Hangar per Bergungsflug geborgen (×1,5, kein Wurf), ohne Hangar selbst, begonnene Bergungen laufen zu Ende. Prospektion und Signalvermessung gestrichen, Versorgungsfahrt ohne Regolith. Rückkehr-Garantie statt 25-%-Startsperre für alle Missionen, Schiffsreparatur nur AP (Werte in `game.ship_repair`). Bot: `dispatch_find_flight`, `repair_ship_for_flight`; Report: `find_flight`, K12/K16/K17, Reparatur-AP. Baseline geändert (`bay` statt `hangar_instance_id`) — Dev-DB mit `php artisan db:reset --force` neu aufbauen.
```

- [ ] **Step 2: Statische Checks**

Run: `bin/pint --test` → sauber.
Run: `bin/phpstan analyse --memory-limit=1G` (Larastan) → 0 Fehler.
Run: `grep -rn "hangar_instance_id\|mission_prospecting_flight\|mission_deep_survey\|signal_tile\|instanceId\|dispatch_min_sp_pct\|SHIP_MAX_STATUS\|REPAIR_SP_PER_AP" app config resources public/js routes tests lang data database tools` → keine Treffer.

- [ ] **Step 3: Volle Standard-Suite**

Run: `bin/phpunit --testsuite=laravel-feature,laravel-unit` (oder `bin/paratest --processes=4`)
Expected: PASS, 0 Fehler.

- [ ] **Step 4: Komplette Playtest-Suite (Pflicht vor dem PR, Lehre aus der roten CI nach #376)**

Run: `bin/phpunit --testsuite=playtest` (Dauer ca. 6–20 min; per `nohup … &` starten und mit Monitor-Poll beobachten, siehe Memory „run_in_background low memory“)
Expected: PASS, 0 Fehler. Rote Bot- oder Report-Tests werden hier behoben, nicht im PR „später“.

- [ ] **Step 5: Commit**

```bash
git add docs/game-reference.md docs/GDD.md docs/superpowers/specs/2026-10-05-t30-gleichwertige-eroeffnungen.md ROADMAP.md CHANGELOG.md
git commit -m "docs(T30): Schritt 3 — game-reference, GDD, Spec-Status, CHANGELOG"
```

- [ ] **Step 6: Messung vorbereiten — NICHT starten**

Der Owner wählt die Laufzahl (8/16/24). Vorher die Dauer schätzen und nennen (Memory). Vorher macht der Owner den **Dev-DB-Reset** (`php artisan db:reset --force`), weil sich die Baseline geändert hat. Erst danach:

```bash
php artisan game:playtest --profiles=default --openings=labor,hangar,cantina --seeds=1,2,3,4,5,6,7,8 --concurrency=12
php artisan game:playtest-compare --openings=labor,hangar,cantina --seeds=1,2,3,4,5,6,7,8 --since="<UTC-Startzeit>"
```

Volle Laufzeit (für K7 kein `--until-sol`). `--since` ist UTC. Die Auswertung richtet sich nach Spec §13.8: K1 ≤ 1, K2 ≤ 3, K3-Abstand ≤ 2, **K4 Hangar − Labor ≤ 20 AP (sonst Folgeschritt V „Verlade-AP“ als eigener Plan)**, K12 Hangar ≥ 85 %, K16 `no_ship_ready` > 3 = Bot-Bug (bleibt er trotz `repair_ship_for_flight` hoch: Folgeschritt passive Dock-Reparatur, §13.x/10), Reparatur-AP Sol 4–12 als Information neben K4, K17 > 0 in mehr als 2 von 8 Läufen → Owner-Frage §13.10/1, Starts `mission_supply_run` zählen.

- [ ] **Step 7:** PR erst nach Owner-Freigabe (Push, PR-Beschreibung mit allen Commits, Pre-Merge-Checkliste aus CLAUDE.md).

---

## Self-Review

**Spec-Abdeckung (§13.7 Tasks 1–8 plus Ergänzung §13.x/7–11):** B1-Datenmodell inkl. Routen, Join, Fixtures und Reparatur über `bay` 0 (Task 1). Rückkehr-Garantie für alle Missionen, Reparatur aus `game.ship_repair` (nur AP), Wegfall `dispatch_min_sp_pct`, Bot-Duplikate der 20 entfernt (Task 2). Geschenk-Drohne inkl. CommLog und Szenarien (Task 3; die Szenarien wurden aus Spec-Task 7 nach vorn gezogen, weil sie `ensureGiftDrone` brauchen). Katalog-Bereinigung und Config-Konsistenz (Task 4). Bergungsflug mit allen Regelzeilen aus §13.3 (Task 5): gescannt, Fehlalarm, Tragkraft, Korvette, Ring 2/3, Ertrag, Abschluss, Siegziel, Doppel-Dispatch, Bestandsschutz-Kachel, Recall, SP-Abbruch, fremde Kolonie, Rückbau im Flug. `findAction` und Ablösung inkl. aller Zeilen der Tabelle §13.4 und der Rückbau-Fälle (iii) (Task 6). UI mit Fundkarte, Zielliste, Kartografie-Zeile, Baukarten-Hinweis, einmaligem Hint und `find_help` (Task 7). Bot und Report inkl. K2/K11/K12/K16/K17 und der Bot-Fallen (ii) (Task 8). Doku und Messung (Task 9). Folgeschritt V und die Querverbindungen §13.6 sind bewusst nicht enthalten. Die Schnittstellen dafür bestehen: `find_type` in den Details, additive Ertragszeilen.

**Placeholder-Scan:** Keine TBD- oder TODO-Stellen. Drei Stellen verlangen bewusst eine Prüfung vor dem Edit, mit konkretem Befehl: Pflichtspalten im `BaselineSchemaTest`-Entwurf, `placeHangarOnly()`-Stufe und der Routenname der Kolonieansicht.

**Typkonsistenz:** `findAction()` liefert `costs.nav_ap`, `costs.organika`, `costs.return_sol`, `yield.total`, `bay`, `ship_key`. Genau so lesen es Task 7 (Fundkarte, `doFindFlight`) und Task 8 (`findFlightCandidate`). `FindFlightService::costs()`-Schlüssel (`sol_distance`, `nav_ap_base`, `nav_ap`, `organika`, `duration`) sind in Task 5 (Katalog-`targets`) und Task 7 (`targetLabel`) gleich. `HangarBays::isInactive(shipId, level, bay)` ist überall dreistellig. `HangarBays::flightReadiness(shipId, sp, solDistance)` liefert `ready`, `wear`, `sp_after`, `repair_clicks`, `repair_ap`. Genau diese Schlüssel lesen der Dispatch-Guard (Task 2), `slot.ship.readiness[d]` im Dialog (Task 7), `findAction` (`ship_worn`, `repair_ap`, Task 6) und der Bot (Task 2 `readyFor`, Task 8 `repairForFlightCandidate`). Der Grund heißt überall `ship_worn` (nicht mehr `ship_repair`). Die Route heißt überall `bay`.

**Review Focus:** Alle sechs Zeilen haben ihren Test im genannten Task (Task 2/5/8: Drohne mit 5–6 SP auf Ring 3; Task 5: Doppel-Dispatch, Rückbau, fremde Kolonie, Recall/Abbruch; Task 6: Selbstbergung während Flug; Task 8: Bot-Fallen).
