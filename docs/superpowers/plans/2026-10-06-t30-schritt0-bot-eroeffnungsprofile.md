# T30 Schritt 0 — Bot-Eröffnungsprofile, Messung und Vergleich — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Der PlaytestBot kann Labor-First, Hangar-First und Cantina-First spielen (`opening`), `game:playtest` fährt Eröffnung × Seed, und ein Vergleichswerkzeug wertet die Läufe gepaart nach den Gleichwertigkeits-Kriterien K1–K7 aus — Grundlage der Baseline-Messung für T30.

**Architecture:** Die Eröffnung ist eine zu den Profilen (`default/thrifty/focus/eager`) orthogonale, diskrete Dimension `BotProfile::$opening` (`auto|labor|hangar|cantina`). Sie wirkt ausschließlich in der Bot-Platzierungsregel (`BotStrategy::placeCandidate`): das erste Pfadgebäude ist das der Eröffnung, die Folge-Reihenfolge ist fest. `game:playtest` bekommt `--openings`, gibt sie per Env `PLAYTEST_OPENING` an die Kinder und trägt sie in Report-Dateiname und -JSON. Ein reiner Auswertungsteil (`OpeningComparison` + `game:playtest-compare`) liest die Report-JSONs und berechnet K1–K7 je Eröffnung und gepaart je Seed.

**Tech Stack:** PHP 8.2, Laravel 12, PHPUnit 11, MySQL 8 (`nouron_test` für Tests, `nouron_playtest` für den Bot).

**Spec:** `docs/superpowers/specs/2026-10-05-t30-gleichwertige-eroeffnungen.md` (Abschnitte 2.1 Kriterien K1–K7, 5 Messplan; Owner-Entscheidungen ganz oben). Dieser Plan setzt nur **Schritt 0** der Spec um (Messwerkzeug + Baseline mit **heutigen** Spielwerten). Hints, Prospektionsflug, Geschenk-Drohne und Regolith-Quellen sind Folgepläne.

## Global Constraints

- TDD (CLAUDE.md): Test zuerst, rot sehen, dann Code. Ausnahmen nur Doku/Config.
- Tests laufen ausschließlich gegen `nouron_test` (phpunit.xml erzwingt das), der Bot ausschließlich gegen `nouron_playtest` (Guard `App\Console\Support\PlaytestDatabase`). Niemals `migrate:fresh`/`db:seed`/Tests gegen die Dev-DB `nouron`.
- Schnelle Suite: `php artisan test --parallel --processes=8 --testsuite=laravel-feature,laravel-unit` (ca. 2,5 min); Playtest-Suite (`--testsuite=playtest`, ca. 20 min) NICHT lokal fahren — läuft in der CI. Fokussierte Läufe: `bin/phpunit <Datei>`.
- Bot-Läufe: höchstens 8 parallel (`--concurrency=8`), ein Lauf ≈ 8–14 min; lange Läufe per `nohup … &` + Poll-Datei, nie minutenlang blockierend warten. Eine Balance-Aussage braucht mind. 6–10 Seeds.
- Branch `feat/t30-bot-openings`; Commit-Messages deutsch/englisch wie im Repo, am Ende die Zeile `Claude-Session: https://claude.ai/code/session_01LL6guV81LSkZ4tjTvj5xCH`. Nie `git add -A` (das Repo hat ungetrackte Fremddateien), Pre-commit-Hook nie umgehen.
- Code und Code-Kommentare Englisch; Doku/ROADMAP/CHANGELOG Deutsch. CHANGELOG: genau ein Block pro Tag (`## 2026-10-06`), 1–2 Sätze pro Thema.
- Der Default `auto` muss das heutige Bot-Verhalten **unverändert** lassen (Regressionsschutz, bestehende Batches bleiben vergleichbar).
- Reproduzierbarkeit: dieselbe Kombination Eröffnung × Profil × Seed liefert denselben Lauf (RNG nur aus `runs.rng_seed`, siehe `App\Support\RunSeed`); der Bot darf keine nicht-deterministische Auswahl einführen (Gleichstände über feste Reihenfolge/Koordinaten brechen).

## Review Focus

Fehlerklassen, die kein Task-Test von selbst abdeckt (Tests dafür stehen in den genannten Tasks):

1. `auto` verändert still die Platzierungsreihenfolge (heute: Labor 31 vor Hangar 44 vor Cantina 52) → Messungen wären nicht mit früheren Batches vergleichbar (Task 2, Regressionstest).
2. Der Bot baut bei einer Nicht-`auto`-Eröffnung doch zuerst ein anderes Pfadgebäude, weil das gewünschte gerade nicht bezahlbar ist (alle drei kosten gleich viel Regolith, aber andere Gates/Verzug sind möglich) (Task 2).
3. Report-Dateien zweier Eröffnungen mit gleichem Seed überschreiben sich oder `latestReportFor()` liest den falschen Report (Task 3).
4. Der Vergleich paart Läufe mit unterschiedlichem Seed oder wertet halb fertige/abgebrochene Läufe als „0“ (Task 4).
5. Die Kennzahlen-Definitionen driften von der Spec (K1–K7) ab, ohne dass es auffällt: jede Kennzahl hat eine in der Klasse dokumentierte Definition und einen Test mit synthetischen Reports (Task 4).

---

### Task 1: `BotProfile::$opening` und Env-Auflösung

**Files:**
- Modify: `tests/Feature/Playtest/BotProfile.php`
- Modify: `tests/Feature/Playtest/PlaytestBotTest.php` (Methode `resolveProfile()`, ca. Z. 165–168; Test `test_env_vars_override_seed_and_profile`, ca. Z. 84–99)
- Test: `tests/Unit/Playtest/BotProfileTest.php` (existiert; ergänzen)

**Interfaces:**
- Produces: `BotProfile::OPENINGS` (`['auto','labor','hangar','cantina']`), Konstruktor-Parameter `public readonly string $opening = 'auto'` (ungültiger Wert → `InvalidArgumentException`), `BotProfile::withOpening(string $opening): self` (Kopie mit geänderter Eröffnung, alle anderen Regler und der Name bleiben). `PlaytestBotTest::resolveProfile()` liefert `BotProfile::named(PLAYTEST_PROFILE ?: 'default')->withOpening(PLAYTEST_OPENING ?: 'auto')`.

- [ ] **Step 1: Failing Tests schreiben** (in `tests/Unit/Playtest/BotProfileTest.php`, im Stil der vorhandenen Tests dieser Datei)

```php
public function test_opening_defaults_to_auto(): void
{
    $this->assertSame('auto', BotProfile::named('default')->opening);
}

public function test_with_opening_keeps_all_other_dials(): void
{
    $focus = BotProfile::named('focus');
    $hangar = $focus->withOpening('hangar');

    $this->assertSame('hangar', $hangar->opening);
    $this->assertSame('focus', $hangar->name);
    $this->assertSame($focus->savingsAggressiveness, $hangar->savingsAggressiveness);
    $this->assertSame($focus->objectiveFocus, $hangar->objectiveFocus);
    $this->assertSame('auto', $focus->opening, 'original must stay unchanged (readonly copy)');
}

public function test_unknown_opening_throws(): void
{
    $this->expectException(\InvalidArgumentException::class);
    BotProfile::named('default')->withOpening('forge');
}
```

Plus in `PlaytestBotTest` den Env-Test erweitern: `putenv('PLAYTEST_OPENING=cantina')` setzen, `self::resolveProfile()->opening === 'cantina'` prüfen und die Variable im `finally` wieder entfernen (analog zu `PLAYTEST_PROFILE`).

- [ ] **Step 2: Rot bestätigen**

Run: `bin/phpunit tests/Unit/Playtest/BotProfileTest.php`
Expected: FAIL (`opening`/`withOpening` existieren nicht).

- [ ] **Step 3: Implementieren** in `BotProfile.php`

```php
public const OPENINGS = ['auto', 'labor', 'hangar', 'cantina'];

public function __construct(
    public readonly string $name = 'default',
    public readonly float $savingsAggressiveness = 0.0,
    public readonly float $objectiveFocus = 0.0,
    // Discrete dimension, orthogonal to the float dials above: which path building the
    // bot builds FIRST (labor = sciencelab 31, hangar 44, cantina 52). 'auto' keeps
    // today's behaviour (sciencelab-first by menu order). Only affects Sol 1-~15.
    public readonly string $opening = 'auto',
) {
    if (! in_array($opening, self::OPENINGS, true)) {
        throw new \InvalidArgumentException("Unknown bot opening: {$opening}");
    }
}

public function withOpening(string $opening): self
{
    return new self($this->name, $this->savingsAggressiveness, $this->objectiveFocus, $opening);
}
```

`resolveProfile()` in `PlaytestBotTest.php` anpassen (siehe Interfaces). Docblock der Klasse um die Opening-Dimension ergänzen.

- [ ] **Step 4: Grün**

Run: `bin/phpunit tests/Unit/Playtest tests/Feature/Playtest/PlaytestBotTest.php --filter 'BotProfile|env_vars'`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Playtest/BotProfile.php tests/Feature/Playtest/PlaytestBotTest.php tests/Unit/Playtest/BotProfileTest.php
git commit -m "feat(bot): BotProfile::\$opening (auto|labor|hangar|cantina) und PLAYTEST_OPENING"
```

---

### Task 2: Platzierungs-Priorität je Eröffnung in `BotStrategy`

**Files:**
- Modify: `tests/Feature/Playtest/BotStrategy.php` (`placeCandidate()` ab ca. Z. 972; die Regel, die es aufruft, in `BotStrategy::default(BotProfile $profile)` ab ca. Z. 89; `$pathIds` an den Stellen ca. Z. 1014, 1139, 2237 prüfen)
- Test: `tests/Feature/Playtest/BotStrategyOpeningTest.php` (neu; Aufbau/Hilfsmethoden wie `BotStrategyPlacementTileTest.php` und `BotStrategyServerGatesTest.php` — dort nachlesen, wie eine Kolonie auf CC Lv2 mit genug Regolith hergestellt und `placeCandidate` bzw. die Platzierungsregel ausgewertet wird)

**Interfaces:**
- Consumes: `BotProfile::$opening` (Task 1).
- Produces: `BotStrategy::pathBuildingOrder(string $opening): array<int>` mit `auto → [31, 44, 52]`, `labor → [31, 44, 52]`, `hangar → [44, 31, 52]`, `cantina → [52, 31, 44]` (feste Folge-Reihenfolge laut Spec 5(b)); `placeCandidate()` kennt das Profil (als Parameter oder über die Regel-Closure) und wendet an: Priorität 0 bioFacility (erste Instanz, wie heute); Priorität 1 das **nächste noch nicht platzierte** Gebäude der Reihenfolge; Priorität 2 die übrigen noch nicht platzierten Pfadgebäude; Trust-Gebäude 3; alles andere 4. **Solange noch kein Pfadgebäude platziert ist und die Eröffnung nicht `auto` ist, werden die anderen Pfadgebäude aus der Kandidatenliste ausgeschlossen** (der Bot wartet, statt ein anderes Pfadgebäude zuerst zu bauen). Alle Instanz-Cap-Regeln (`[44 => 2, 28 => 2]`) bleiben unverändert.

- [ ] **Step 1: Failing Tests schreiben** (`BotStrategyOpeningTest`)

Vier Fälle mit derselben Ausgangslage (Kolonie bei CC Lv2, Agrardom (41) platziert, genug Regolith/AP für jedes Pfadgebäude, noch kein Pfadgebäude platziert):
1. `auto` → erster Kandidat = Gebäude 31 (Regressionsschutz: heutiges Verhalten).
2. `labor` → 31.
3. `hangar` → 44, und die Kandidatenliste enthält 31/52 **nicht**.
4. `cantina` → 52, Kandidatenliste ohne 31/44.
Zwei weitere: (5) `hangar` nach platziertem Hangar → nächster Pfad-Kandidat ist 31 (dann 52); (6) `cantina` nach platzierter Cantina → 31 vor 44. Zusätzlich (7) bestehender Test-Schutz: vor dem Ändern `bin/phpunit tests/Feature/Playtest --filter BotStrategy` laufen lassen und danach erneut (alle bestehenden Strategie-Tests bleiben grün).

- [ ] **Step 2: Rot bestätigen**

Run: `bin/phpunit tests/Feature/Playtest/BotStrategyOpeningTest.php`
Expected: FAIL für Fälle 3–6 (Fälle 1–2 dürfen schon grün sein).

- [ ] **Step 3: Implementieren**

`pathBuildingOrder()` als `public static` Methode; in `placeCandidate()` die Tier-Tabelle auf die fünf Stufen umstellen (siehe Interfaces) und den Ausschluss der anderen Pfadgebäude einbauen. Das Profil kommt per Parameter (`placeCandidate(BotSession $b, BotProfile $profile)`); alle Aufrufstellen (grep `placeCandidate(`) anpassen. Die Memoisierung (`$b->remember('place_candidate', …)`) bleibt — der Schlüssel ändert sich nicht, weil ein Bot nur ein Profil hat.

- [ ] **Step 4: Berater-Reihenfolge absichern** — Test: Bei Eröffnung `hangar` wird der Raumfahrer (personell_id 89, siehe `HIRE_ORDER`/`nextHireCandidate()` ab ca. Z. 789) direkt nach Hangar Lv1 angeheuert, **ohne** auf ein angekommenes Schiff zu warten (Spec 5(b): der Bot ignoriert Hints und nimmt die +2 AP mit); bei `cantina` der Konsul nach der Cantina. Falls die Gebäude-Gates das bereits leisten, genügt der Test; sonst `nextHireCandidate()` minimal anpassen.

- [ ] **Step 5: Grün**

Run: `bin/phpunit tests/Feature/Playtest --filter 'BotStrategy'`
Expected: PASS (neue und alte Strategie-Tests).

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Playtest/BotStrategy.php tests/Feature/Playtest/BotStrategyOpeningTest.php
git commit -m "feat(bot): Pfadgebäude-Reihenfolge je Eröffnung (labor/hangar/cantina), auto unverändert"
```

---

### Task 3: `game:playtest --openings`, Env, Report-Dateiname und -Feld

**Files:**
- Modify: `app/Console/Commands/Playtest.php` (Signatur ab ca. Z. 30, Kombinationsbildung/Env/Tabelle/`latestReportFor()` ca. Z. 60–160)
- Modify: `tests/Feature/Playtest/RunReport.php` (Konstruktor Z. 48, `build()` ca. Z. 459, `write()` ca. Z. 496–507, `printTable()` ca. Z. 509)
- Modify: `tests/Feature/Playtest/PlaytestBotTest.php` (Aufruf `new RunReport(...)`, ca. Z. 50)
- Modify (nur falls sie Report-Dateinamen/-Felder lesen): `tools/playtest-dashboard.php` — vorher `grep -rn "storage/logs/playtest\|glob(" tools app tests` ausführen und jede Fundstelle prüfen
- Test: `tests/Feature/Console/PlaytestCommandOpeningsTest.php` (neu; Stil/Hilfen wie `PlaytestCommandTimeoutTest.php`, `PlaytestCommandGuardTest.php`, `PlaytestCommandFailureOutputTest.php`)

**Interfaces:**
- Consumes: `PLAYTEST_OPENING` (Task 1).
- Produces: Option `--openings=auto` (kommagetrennt, geprüft gegen `BotProfile::OPENINGS`, sonst Fehlermeldung + `FAILURE`); Kombinationen = Profil × Eröffnung × Seed; Kinder bekommen zusätzlich `PLAYTEST_OPENING`. `RunReport::__construct(int $seed, string $profile = 'default', string $opening = 'auto')`; JSON-Feld `opening`; Dateiname `{profile}-{opening}-{seed}-{Ymd_His}.json` (Eröffnung `auto` bekommt ebenfalls `-auto-`, damit die Namen eindeutig sind); `latestReportFor(string $profile, string $opening, string $seed)` sucht nach diesem Muster; Ergebnistabelle bekommt die Spalte `Opening`. Alte Report-Dateien ohne Eröffnung im Namen bleiben unberührt (werden nicht mehr gefunden — gewollt).

- [ ] **Step 1: Failing Tests schreiben**

(a) Unit-ähnlicher Test für `RunReport`: `new RunReport(7, 'default', 'hangar')` → `build()`-Ergebnis enthält `'opening' => 'hangar'`, `write()` erzeugt eine Datei, deren Name `default-hangar-7-` enthält.
(b) Command-Test: ungültige Eröffnung (`--openings=forge`) → Ausgabe nennt die erlaubten Werte, Exit-Code `FAILURE`, es wird nichts zurückgesetzt. Mit zwei Eröffnungen und einem Seed (`--openings=labor,hangar --seeds=1`) werden zwei Kindprozesse gestartet, deren Env `PLAYTEST_OPENING` `labor` bzw. `hangar` ist (die vorhandenen Command-Tests zeigen, wie Kinder ersetzt/abgefangen werden — dem folgen, nicht neu erfinden).
(c) Zwei Report-Dateien gleicher Seed, verschiedene Eröffnung: `latestReportFor('default','labor','1')` liefert nie den `hangar`-Report (Test mit zwei im Test-Report-Verzeichnis angelegten Dateien; das Verzeichnis nach dem Test aufräumen).

- [ ] **Step 2: Rot bestätigen**

Run: `bin/phpunit tests/Feature/Console/PlaytestCommandOpeningsTest.php`
Expected: FAIL.

- [ ] **Step 3: Implementieren** gemäß Interfaces. `--openings` Default `auto`; die Kombinationsliste und die Batch-Beschriftung (`Running batch: …`) tragen die Eröffnung; Docblock der Klasse und `$signature`-Beschreibung ergänzen.

- [ ] **Step 4: Grün + Nachbarn**

Run: `bin/phpunit tests/Feature/Console tests/Feature/Playtest/PlaytestBotTest.php --filter 'Playtest|RunReport|env_vars'`
Expected: PASS. Zusätzlich `bin/phpunit tests/Feature/Playtest --filter RunReport` (alle RunReport-Tests; die Konstruktor-Änderung hat Default-Parameter, bestehende Aufrufer bleiben gültig).

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/Playtest.php tests/Feature/Playtest/RunReport.php tests/Feature/Playtest/PlaytestBotTest.php tests/Feature/Console/PlaytestCommandOpeningsTest.php
git commit -m "feat(playtest): --openings, PLAYTEST_OPENING, Eröffnung in Report-Dateiname und -JSON"
```

(zusätzlich `tools/playtest-dashboard.php`, falls angepasst.)

---

### Task 4: Kennzahlen K1–K7 und gepaarter Vergleich (`OpeningComparison`, `game:playtest-compare`)

**Files:**
- Create: `app/Support/OpeningComparison.php`
- Create: `app/Console/Commands/PlaytestCompare.php`
- Test: `tests/Unit/OpeningComparisonTest.php` (synthetische Report-Arrays im Format von `RunReport::build()`), `tests/Feature/Console/PlaytestCompareTest.php`
- Referenz (lesen, nicht ändern): `tests/Feature/Playtest/RunReport.php` (Snapshot-Felder pro Sol: `sol`, `regolith`, `credits`, `ap.total/inflow/unspent`, `ap_unspent`, `cc_level`, `advisors`, `regolith_sources.{harvester,mission,trade}`, `ship_states`, `buildings`; Kopf: `outcome.{status,sols,score}`, `phase2_start_sol`, `objectives`, `log` mit Aktionen je Sol/Regel), `docs/superpowers/specs/2026-10-05-t30-gleichwertige-eroeffnungen.md` Abschnitt 2.1

**Interfaces:**
- Consumes: Report-JSONs aus Task 3 (`opening` im JSON).
- Produces: `OpeningComparison::fromReports(array $reports): self` (Liste decodierter Report-Arrays); `metrics(array $report): array{k1: ?int, k2: ?int, k3: ?int, k4: float, k5: int, k6: ?int, k7: array{won: bool, sols: int}}` mit **in der Klasse dokumentierten Definitionen**:
  - **K1** `first_path_action_sol − path_building_level1_sol`: Sol der ersten pfadspezifischen Aktion minus Sol, in dem das Pfadgebäude Lv1 hat. Pfadspezifische Aktion je Eröffnung (Regelnamen aus `BotStrategy`/`log` heraussuchen und im Docblock festhalten): `labor` = erste Kenntnis-Investition/Forschung, `hangar` = erster Schiffskauf bzw. erste Mission, `cantina` = erste Bar-Aktion.
  - **K2** erster Sol mit einem Ertrag in einer Phase-1-relevanten Größe (Regolith, Supply, AP, Vertrauen) aus Pfadquelle: Sol des ersten `regolith_sources.mission|trade > 0` bzw. der ersten pfadspezifischen Aktion, deren Wirkung im nächsten Snapshot sichtbar ist — Definition präzise festlegen und dokumentieren; `null`, wenn nie.
  - **K3** `phase2_start_sol`.
  - **K4** Σ (unverbrauchte AP) über Sol 4–12 (aus `ap_unspent`), plus reine Erkundungs-AP nach Kartenende falls aus dem Log ableitbar (sonst nur die erste Hälfte, im Docblock vermerken).
  - **K5** Anzahl „leerer" Sole in 4–15: ≥ 50 % der AP des Sols (`ap.unspent` ≥ 0,5 · `ap.total`) ungenutzt **und** im Log dieses Sols keine Bau-/Pfad-/Forschungsaktion.
  - **K6** `regolith` im Snapshot von Sol 10 (`null`, falls der Lauf vorher endete).
  - **K7** `won = outcome.status === 'completed'`, `sols = outcome.sols`.
  `compare(): array` — gruppiert nach `opening`, paart je Seed (nur Seeds, die in **allen** verglichenen Eröffnungen einen **beendeten** Lauf haben; andere werden im Ergebnis als `skipped_seeds` ausgewiesen), berechnet je Kennzahl Median und Spannweite je Eröffnung und das Delta zur Referenz `labor` je Seed (Median/Spannweite der Deltas). `game:playtest-compare {--profile=default} {--openings=labor,hangar,cantina} {--seeds=} {--since=}` liest `storage/logs/playtest/*.json`, nimmt je (Profil, Eröffnung, Seed) den neuesten Report, ruft `OpeningComparison` auf und druckt eine Tabelle sowie die Prüfung gegen die K-Schwellen aus Spec 2.1 (`ok`/`verfehlt` je Kriterium).

- [ ] **Step 1: Failing Tests schreiben** (`OpeningComparisonTest`)

Synthetische Reports (je 15 Sole, handgemachte Werte) für drei Eröffnungen und zwei Seeds; Tests: (1) jede Kennzahl K1–K7 ergibt den erwarteten Wert (je ein Test pro Kennzahl, Erwartungswerte von Hand aus dem Report abgeleitet); (2) ein Lauf, der vor Sol 10 endet, liefert `k6 === null` und zählt nicht als 0; (3) Seeds, die nicht in allen Eröffnungen fertig sind, landen in `skipped_seeds`; (4) Median/Spannweite und Delta zu `labor` stimmen bei zwei bekannten Seeds; (5) die Prüfung gegen die Schwellen (z. B. K3-Abstand ≤ 2 Sole) kennzeichnet ein bewusst verletztes Kriterium als `verfehlt`.

- [ ] **Step 2: Rot bestätigen** (`bin/phpunit tests/Unit/OpeningComparisonTest.php` → FAIL, Klasse fehlt)

- [ ] **Step 3: Implementieren** `OpeningComparison` (reine Funktionen auf Arrays, kein DB-Zugriff) und den Command (`PlaytestCompare`, dünn: Dateien lesen, Klasse aufrufen, Tabelle drucken); Command-Test mit zwei im Test angelegten Report-Dateien je Eröffnung (Verzeichnis aufräumen), prüft Ausgabe und Exit-Code, sowie dass ungültige `--openings` abbrechen.

- [ ] **Step 4: Grün**

Run: `bin/phpunit tests/Unit/OpeningComparisonTest.php tests/Feature/Console/PlaytestCompareTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Support/OpeningComparison.php app/Console/Commands/PlaytestCompare.php tests/Unit/OpeningComparisonTest.php tests/Feature/Console/PlaytestCompareTest.php
git commit -m "feat(playtest): Kennzahlen K1-K7 und gepaarter Eröffnungsvergleich (game:playtest-compare)"
```

---

### Task 5: Frühabbruch für Phase-1-Messläufe (`--until-sol`)

Hintergrund (Owner 2026-10-06: Baseline soll < 30 min dauern): K1–K6 liegen in Sol 1–~15, ein voller Lauf spielt aber bis Sol 100 (8–14 min, 8 parallel ≈ 17 min). Mit Abbruch bei Sol 20 dauert ein Lauf grob ein Fünftel; die Baseline (24 Läufe) sinkt von ≈ 51 min auf ≈ 10–15 min. K7 (Siegquote/Sieg-Sol) wird dabei bewusst nicht gemessen — das übernimmt die zweite Messrunde als Volllauf.

**Files:**
- Modify: `app/Console/Commands/Playtest.php` (Option `--until-sol=` + Env-Weitergabe + Validierung)
- Modify: `tests/Feature/Playtest/PlaytestBotTest.php` (Stop-Bedingung), `tests/Feature/Playtest/PlaysSolLoop.php` (nur falls die Schleife die Bedingung nicht schon erlaubt — lesen), `tests/Feature/Playtest/RunReport.php` (Feld `truncated_at_sol`)
- Modify: `app/Support/OpeningComparison.php`, `app/Console/Commands/PlaytestCompare.php` (Task 4: abgeschnittene Läufe behandeln)
- Test: `tests/Feature/Console/PlaytestCommandOpeningsTest.php` bzw. neue `PlaytestCommandUntilSolTest.php`, `tests/Unit/OpeningComparisonTest.php` (ergänzen), RunReport-Test

**Interfaces:**
- Produces: Option `--until-sol=N` (ganze Zahl 5–100, sonst Fehlermeldung + `FAILURE` vor jedem DB-Zugriff), Env `PLAYTEST_UNTIL_SOL` an die Kinder; der Bot spielt Sole, bis `$bot->sol >= N` oder der Lauf regulär endet (was zuerst eintritt) und schreibt dann den Report mit `outcome.status` wie im Run-Zustand (`active`) **und** neuem Feld `truncated_at_sol` (= N) — nur gesetzt, wenn wegen der Grenze gestoppt wurde. Der Report-Dateiname bleibt unverändert. `OpeningComparison`: ein Lauf zählt als **fertig** (paarbar), wenn er regulär endete **oder** `truncated_at_sol` gesetzt ist; für abgeschnittene Läufe sind K1–K6 wie gewohnt auswertbar (K6 `null`, falls Sol 10 nicht erreicht), **K7 ist `null`** und wird in Median/Delta/Schwellenprüfung als „nicht gemessen“ ausgewiesen (nicht als 0, nicht als verfehlt). Läufe, die weder regulär endeten noch abgeschnitten sind, bleiben `skipped`.

- [ ] **Step 1: Failing Tests schreiben**: (a) Command: `--until-sol=20` → Kinder bekommen `PLAYTEST_UNTIL_SOL=20`; ungültige Werte (`0`, `abc`, `500`) → Fehler vor DB-Reset, nichts gestartet (Muster wie der Test für ungültige `--openings`); ohne die Option wird die Env-Variable **nicht** gesetzt. (b) RunReport: Report eines abgeschnittenen Laufs enthält `truncated_at_sol`; ein regulär beendeter nicht. (c) Bot: eine Bot-Schleife mit Grenze bricht nach genau N Solen ab (Test auf der Schleifen-/Stop-Ebene mit der vorhandenen Harness, kein voller Lauf). (d) OpeningComparison: abgeschnittene Läufe sind paarbar; K1–K6 werden berechnet; K7 `null` und in der Schwellenprüfung als „nicht gemessen“; ein weder beendeter noch abgeschnittener Lauf landet in `skipped_seeds`.
- [ ] **Step 2: Rot bestätigen** (fokussiert, FAIL).
- [ ] **Step 3: Implementieren** gemäß Interfaces; `PlaytestBotTest` liest `PLAYTEST_UNTIL_SOL` (`getenv`, wie die anderen Variablen) und übergibt die Stop-Bedingung an `playSolsUntil`.
- [ ] **Step 4: Grün** — `bin/phpunit tests/Feature/Console --filter Playtest`, `bin/phpunit tests/Feature/Playtest --filter 'RunReport|PlaysSolLoop'`, `bin/phpunit tests/Unit/OpeningComparisonTest.php`, Pint, phpstan.
- [ ] **Step 5: Commit** (`feat(playtest): --until-sol für Phase-1-Messläufe, abgeschnittene Läufe im Vergleich`).

---

### Task 6: Doku, ROADMAP, CHANGELOG, Gesamtprüfung

**Files:**
- Modify: `docs/dev-setup-mysql.md` (Abschnitt PlaytestBot: `--openings`, Report-Namen, `game:playtest-compare`)
- Modify: `docs/superpowers/specs/2026-08-14-bot-playstyle-profiles-design.md` (kurzer Hinweis „Dimension `opening`“ mit Verweis auf die T30-Spec; sonst unverändert)
- Modify: `ROADMAP.md` (T30: Schritt 0 umgesetzt; Baseline-Messung als nächster Punkt), `CHANGELOG.md` (ein Eintrag im Block `## 2026-10-06`)

- [ ] **Step 1: Doku/ROADMAP/CHANGELOG schreiben** (Deutsch, knapp; ROADMAP T30 existiert nach PR #372 — vorher `grep -n "T30" ROADMAP.md`).

- [ ] **Step 2: Gesamtprüfung**

Run: `php artisan test --parallel --processes=8 --testsuite=laravel-feature,laravel-unit` → grün; `bin/pint --test` → pass; `bin/phpstan analyse` → keine Fehler.

- [ ] **Step 3: Commit + Push**

```bash
git add docs ROADMAP.md CHANGELOG.md
git commit -m "docs: T30 Schritt 0 — Bot-Eröffnungsprofile und Vergleich"
git push -u origin feat/t30-bot-openings
```

PR erst auf Owner-Okay.

---

### Task 7: Baseline-Messung (Controller, kein Entwickler-Task)

Nach Merge/Abnahme der Tasks 1–5 (oder auf dem Branch): Owner wählt vorab 8/16/24 Läufe (Vorschlag: 24 = 3 Eröffnungen × 8 Seeds mit Frühabbruch bei Sol 20 ≈ 10–15 min statt ≈ 51 min; 12 parallel; K7 wird erst in der zweiten Messrunde als Volllauf gemessen). Aufruf:

```bash
nohup php artisan game:playtest --profiles=default --openings=labor,hangar,cantina --seeds=1,2,3,4,5,6,7,8 --until-sol=20 --concurrency=12 > <scratchpad>/t30_baseline.txt 2>&1 &
```

Danach `php artisan game:playtest-compare --profile=default --openings=labor,hangar,cantina` und die Ergebnistabelle samt K-Prüfung als Abschnitt „Baseline 2026-10-xx“ in die T30-Spec übernehmen (Messwerte, Seeds, Laufzeit; Annahmen der Spec aus Abschnitt 1/2 gegen die Messung bestätigen oder korrigieren — insbesondere die Annahme „Cantina-First hat ähnlichen Leerlauf wie Hangar-First“). Auf dem Rechner kein ressourcenhungriges Spiel nebenbei laufen lassen (verfälscht Laufzeiten).
