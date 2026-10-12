# PlaytestBot Utility-Modell (Option A) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Der PlaytestBot entscheidet über einen zentralen Nutzen-Selektor statt über die starre Regelreihenfolge; Legacy- und Utility-Engine laufen parallel (`--engine=legacy|utility|shadow`), und der erste echte Hebel (Pfadgebäude-Ausbau, Sparziel/CC, Forschungsauswahl, Frachter-Ausnahme = T30 §14 U1–U6) ist umgesetzt und gemessen.

**Architecture:** Jede alte Regel wird als `LegacyRuleSource` hinter einen `UtilitySelector` gehängt; ein einziger Sammler (`Collector`) erscheint dem unveränderten `PlaysSolLoop` als eine Regel. Kandidaten werden nach **Band** (= alter Regelindex) und innerhalb des Bands nach Nutzen je AP (Milli-Rg-Ä) sortiert; S0 ist damit beweisbar identisch zu Legacy (Golden-Test). S2 ergänzt Kontext, Preise, Diskont und Bewertungsterme samt Schattenmodus; S3 ersetzt die Quellen `invest_cc`, `invest_production`, `focus_engineering_levelup`, `research_knowledge` durch Utility-Quellen, ergänzt eine Frachter-Quelle und ein heuristisches Sparziel.

**Tech Stack:** PHP 8.3, Laravel 12, PHPUnit 11, MySQL 8 (Test-DB `nouron_test`, Playtest-DB `nouron_playtest`), Artisan-Commands `game:playtest`, `game:playtest-compare`.

**Spec:** `docs/superpowers/specs/2026-10-12-playtestbot-utility-modell.md` (inkl. „Rulings 2026-10-12“). Zweitquelle für U1–U6: `git show feat/t30-schritt4-spec:docs/superpowers/specs/2026-10-05-t30-gleichwertige-eroeffnungen.md` §14.

## Global Constraints

- Code, Kommentare, Config-Keys: Englisch. Plan, Spec, CHANGELOG: Deutsch.
- Kein Zufall im Bot; jede SQL-Abfrage im Bot mit vollständigem `ORDER BY`; Scores vor jedem Vergleich auf Milli-Rg-Ä runden (`(int) round(x * 1000)`).
- Tie-Break: Band aufsteigend → Milli-Score absteigend → Name → Kandidatenschlüssel.
- `BotStrategy::default()` ändert sein Verhalten bis S8 nicht. Erlaubt sind nur: `private` → `public` bei Helfern, Refactoring `researchOptions()` → `researchableKnowledge()` ohne Verhaltensänderung, `BotSession::$regolithHold` (Legacy: immer 0). Der Golden-Test beweist das nach jeder dieser Änderungen.
- Log-Namen der Aktionen bleiben gleich (`invest_production`, `invest_cc`, `research_knowledge`, `request_ship` …); `RunReport::AP_CATEGORY_MAP`, `OpeningComparison::PATH_RULES`/`BUILD_RULES` bleiben unverändert.
- Gewichte liegen in `tests/Feature/Playtest/Utility/weights.php` (Test-Harness, nicht `config/`).
- Nur spielersichtbare Information: Funde zählen erst nach dem Tiefenscan (`is_deep_scanned = 1`).
- Feste Reihenfolge des zweiten Pfadgebäudes bleibt (Ruling 1): `place_building` bleibt in S3 Legacy.
- TDD verbindlich: Test zuerst, rot bestätigen, dann minimal implementieren.
- Schnelle Suite: `php artisan test --parallel --testsuite=laravel-feature,laravel-unit`. Playtest-Suite (ca. 20 min, mit Golden-Test ca. 24 min): `bin/phpunit --testsuite playtest`. Einzeldatei: `bin/phpunit <pfad>`.
- Vor jedem PR die komplette Playtest-Suite als eigener Verifikationsschritt (Lehre aus roter CI nach #376).
- Nie `git add -A`/`git add .` (Owner hat untracked Dateien) — immer Dateien einzeln stagen.
- Nie `migrate:fresh`/`db:seed` gegen die Dev-DB `nouron`.
- Messungen (`game:playtest`-Batches) führt der Plan nicht automatisch aus: Owner wählt Laufzahl 8/12/24 (ca. 15–25 min je Batch), vorher keine rechenintensiven Programme starten. `--since` von `game:playtest-compare` ist UTC.
- Commit-Nachrichten enden mit dem Trailer, den die ausführende Session vorgibt.
- Pre-commit-Hook lintet (Pint). Schlägt er fehl: Datei neu stagen, Commit wiederholen.

## Review Focus

1. **Ablehnungsschleifen der Utility-Quellen:** Eine Utility-Quelle bietet etwas an, das der Server ablehnt (Machbarkeitsfilter driftet vom Server-Guard weg). Erwartung: höchstens ein Fehlversuch je Name und Sol, nie Endlosschleife. → Task 21 (Smoke-Test ≤ 2 Ablehnungen der migrierten Regeln in 12 Solen), Task 18 (unbezahlbarer CC-Ausbau erzeugt keinen Kandidaten), Task 2 (Fallback-Sperre bei wiederholtem Sperrnamen).
2. **Alte Reports ohne `engine`/`decisions`/`utility_summary`:** `game:playtest-compare` darf nicht abbrechen, Utility-Kennzahlen sind dann `null`. → Task 8 (`test_legacy_report_without_utility_fields_yields_null_kpis`).
3. **Unerreichbares Sparziel:** Ein Ziel, das mit dem Einkommen nie (oder erst nach > 5 Solen) erreichbar ist, hält Regolith dauerhaft fest. Erwartung: kein Ziel, kein Hold. → Task 16 (`test_a_goal_out_of_reach_within_five_sols_is_dropped`).
4. **Schattenmodus verändert Entscheidungen:** `shadow` darf nur mitbewerten. Erwartung: identische Aktionsfolge wie Legacy. → Task 13 (Golden-Test für `shadow`), Task 21 (`regolithHold` nur im Modus `utility`).
5. **Gleichstände/Float-Rauschen:** Fast gleiche Scores dürfen keine reihenfolgeabhängige Wahl erzeugen. → Task 3 (`test_milli_rounding_turns_near_equal_scores_into_ties`, `test_order_of_the_input_does_not_change_the_choice`).

## Dateistruktur

| Datei | Verantwortung | Task |
|---|---|---|
| `tests/Feature/Playtest/PlaysSolLoop.php` | Sperrt den Entscheidungsnamen, fragt den Sammler nach Fehlschlag erneut | 2 |
| `tests/Feature/Playtest/BotSession.php` | neue Felder `blockedThisSol`, `decisions`, `lambdaAp`, `lambdaBySol`, `regolithHold` | 2, 7, 11, 21 |
| `tests/Feature/Playtest/BotProfile.php` | `engine`, `withEngine()`, `weights()` | 5, 10, 13 |
| `tests/Feature/Playtest/BotEngine.php` (neu) | Engine → Regelliste | 5, 13 |
| `tests/Feature/Playtest/BotStrategy.php` | nur Sichtbarkeit, `researchableKnowledge()`, `regolithHold` in `regolithReserve()` | 11, 12, 19, 20, 21 |
| `tests/Feature/Playtest/RunReport.php` | `engine`, `filePrefix()`, `decisions`, `utility_summary` | 6, 7, 13, 14 |
| `tests/Feature/Playtest/Utility/Candidate.php`, `Decision.php`, `CandidateSource.php`, `BotContext.php`, `UtilitySelector.php` | Kern des Selektors (rein, ohne DB) | 3 |
| `tests/Feature/Playtest/Utility/LegacyRuleSource.php`, `Collector.php`, `UtilityStrategy.php` | Adapter, Sammler, Engine-Aufbau | 4, 13, 14, 21 |
| `tests/Feature/Playtest/Utility/DecisionLog.php` | Entscheidungslog-Eintrag | 7, 13 |
| `tests/Feature/Playtest/Utility/weights.php`, `Valuation.php` | Gewichte, reine Rechenbausteine | 10 |
| `tests/Feature/Playtest/Utility/UtilityFixtures.php` | gemeinsame Test-Fixtures (Trait) | 11 |
| `tests/Feature/Playtest/Utility/Estimate.php`, `Valuers.php`, `LegacyValuation.php` | Bewertungsterme | 12, 13 |
| `tests/Feature/Playtest/Utility/SavingsGoal.php`, `HeuristicGoalProvider.php` | Sparziel | 16 |
| `tests/Feature/Playtest/Utility/Sources/{Upgrade,Cc,Research,Freighter}Source.php` | S3-Quellen | 17–20 |
| `app/Console/Commands/Playtest.php`, `PlaytestCompare.php`, `app/Support/OpeningComparison.php` | `--engine`, Kennzahlen K18–K23 | 6, 8, 13, 14 |

## Zwischenstände (Session-Limit)

- **Zwischenstand A** nach Task 9 (S0 + S1): Adapter, Engine-Schalter, Golden-Test, Entscheidungslog, Kennzahlen. Mergbar, Legacy-Verhalten unverändert.
- **Zwischenstand B** nach Task 15 (S2): Kontext, Gewichte, Bewertung, Schattenmodus (K22), λ_AP (K21). Mergbar, Legacy-Verhalten unverändert.
- **Zwischenstand C** nach Task 22 (S3): erste echte Utility-Entscheidungen + Messung + Kalibrierung.

Jeder Zwischenstand ist ein eigener PR. Für S2 und S3 einen gestapelten Branch nutzen (`feat/bot-utility-s2` von `feat/bot-utility-modell`, `feat/bot-utility-s3` von `feat/bot-utility-s2`); nach einem Squash-Merge des Vorgänger-PRs mit `git rebase --onto origin/master <letzter Commit des Vorgängers> <branch>` nachziehen.

---

## Task 1 (P0): Abgleich mit T30-Spec §14

**Files:**
- Modify: `docs/superpowers/specs/2026-10-12-playtestbot-utility-modell.md` (neuer Abschnitt nach „Rulings 2026-10-12“, Zeile 405 ff.; Tabelle 5.3 Zeile 344)

**Interfaces:** Produces: Kennzahl-Nummern K18–K23 und Zielwerte, die Tasks 8, 13, 14, 22 verwenden.

- [ ] **Step 1: §14 lesen**

Run: `git show feat/t30-schritt4-spec:docs/superpowers/specs/2026-10-05-t30-gleichwertige-eroeffnungen.md | sed -n '/^## 14\./,$p'`
Expected: Abschnitte 14.1–14.8 mit U1–U6 und den Messschwellen K18/K19.

- [ ] **Step 2: Abschnitt anhängen**

Am Ende der Spec einfügen:

```markdown
## 9. Abgleich mit T30-Spec §14 (P0)

| T30 §14 | Utility-Spec | Umsetzung |
|---|---|---|
| U1 Deckel Stufe 2 | B1 | S3 `UpgradeSource`: alle Stufen bis `max_level`, Stufenterm `w_tier` (Owner-Vorgabe 2026-10-12 „Stufe 2 und 3 bauen wollen“) |
| U2 Pfad-Puffer | B4 | S3 Sparziel (`HeuristicGoalProvider`) + Opportunitätskosten statt Puffer im Ausbau; Legacy-`place_building` behält seinen Puffer bis S4 |
| U3 Sortierung nur aufs Phase-1-Ziel | B2, B3 | S3 Bewertung: Phasen-, Stufen-, Freischalt-, Eröffnungsterm |
| U4 CC-Ausbau kurzsichtig | B4 | S3 `CcSource` bietet nur Bezahlbares an; unbezahlbarer CC-Ausbau wird Sparziel, Legacy-Regolith-Ausgaben halten es über `BotSession::$regolithHold` |
| U5 Frachter erst ab 3 Beratern | – (neu) | S3 `FreighterSource`: Frachter, wenn ein gescannter großer Fund auf `needs_freighter` wartet |
| U6 Forschung nach ID | B6 | S3 `ResearchSource`: Effekt je AP, Geologie vor Bauwesen |
| – | B5, B7 | Folgeplan S6/S7 |

**Kennzahlen:** T30 §14 K18 (Stufen-Sol je Pfadgebäude) heißt hier **K19**; T30 §14 K19 (Sol CC Lv4) heißt hier **K23** (neu). Zielwerte aus §14 übernommen: K19 eigenes Pfadgebäude Lv2 ≤ Sol 9 (Warnung > Sol 12), Lv3 ≤ Sol 30; K23 ≤ Sol 25 (Warnung > Sol 30). K20 enthält in v1 nur „Entscheidungen je Sol (Median)“; der Anteil verworfener positiver Kandidaten folgt mit S6.

**Phase-2-Sparziel (§14.4):** Das Utility-Sparziel wählt nach Net/Rg statt nach fester Liste. Die Gewichte (`w_tier`, `structural_offset`) sind so gesetzt, dass CC Lv4 in Phase 2 in der Regel vor Pfadgebäude Lv3 liegt (Annahme, Messung S3). Die 5-Sol-Erreichbarkeit aus §14.4 gilt (`goal_max_wait_sols`). `pathTierPolicy`/`hangarLv2Trigger` (§14.4) werden nicht als Schalter umgesetzt, sondern über Gewichte. Verlade-AP (§14.5) ist eine Spielregel und nicht Teil dieses Vorhabens.

**Präzisierungen für die Umsetzung (Plan 2026-10-12):**
- Statt `(N − Regelindex) × 10⁶` und `upperBound()` ordnet ein **Band** (= alter Regelindex) die Kandidaten; innerhalb eines Bands entscheidet der Score. Bis S8 bleiben auch die Pflicht-Regeln als Legacy-Quellen in ihren Bändern im Sammler (sonst wäre die Golden-Gleichheit unmöglich).
- Der Loop sperrt `res['block']` ?? Name des letzten neuen Log-Eintrags ?? Regelname und fragt eine Regel erneut, wenn nicht ihr eigener Name gesperrt wurde.
- Strukturschritte (CC-Stufe, Pfadgebäude platzieren) sind in v1 kostenneutral bewertet: ihr Rg-Preis wird als gleich hoher Strukturwert gegengerechnet (`structural_offset` = 1,0, Annahme).
- Neue Gewichte: `w_tier` 30, `structural_offset` 1,0, `objective_base` 150, `research_floor` 1,0, `build_ap_per_sol` 10, `nav_ap_per_sol` 4, `income_floor_rg` 8, `goal_max_wait_sols` 5, `trust_danger_margin` 10, `trust_danger_factor` 4. Profil-Gewichte werden aus den Reglern abgeleitet (`w_objective` = `objectiveFocus` × 150, `price.credits` × (1 + `savingsAggressiveness`)).
- Engine `shadow` ist dritter Wert von `--engine`. Reports anderer Engines heißen `{profil}.{engine}-{eröffnung}-{seed}-{zeit}.json`.
- Plan-S3 bündelt Spec-S3 (nur Ausbau), Spec-S4 (Sparziel/CC) und Spec-S5 (Forschung) sowie die Frachter-Ausnahme aus Spec-S7.
```

In Tabelle 5.3 (Zeile 344) die Zeile K19 ersetzen durch:

```markdown
| K19 | eigenes Pfadgebäude Lv2 ≤ Sol 9 (Median, Warnung > 12) bei allen Eröffnungen; Lv3 ≤ Sol 30 |
```

und darunter ergänzen:

```markdown
| K23 | CC Lv4 ≤ Sol 25 (Median, Warnung > 30) |
```

- [ ] **Step 3: Commit**

```bash
git add docs/superpowers/specs/2026-10-12-playtestbot-utility-modell.md
git commit -m "docs: Utility-Spec P0 — Abgleich mit T30 §14, Kennzahlen K19/K23, Umsetzungs-Präzisierungen"
```

---

## Task 2 (S0): Loop sperrt den Entscheidungsnamen

**Files:**
- Modify: `tests/Feature/Playtest/PlaysSolLoop.php:64-133`
- Modify: `tests/Feature/Playtest/BotSession.php:27-31`
- Test: `tests/Feature/Playtest/PlaysSolLoopBlockingTest.php` (neu)

**Interfaces:**
- Produces: `BotSession::$blockedThisSol` (`list<string>`, vom Loop je Sol zurückgesetzt). Regel-`do` darf `['ok' => false, 'block' => '<name>']` zurückgeben.

- [ ] **Step 1: Failing test schreiben**

```php
<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Spec 3.3: a failed action blocks the name it was logged under (or an explicit
 * `block`), not the rule — otherwise one rejected candidate would block the whole
 * utility collector for the Sol. Legacy rules log under their own name, so for
 * them nothing changes.
 */
class PlaysSolLoopBlockingTest extends TestCase
{
    use PlaysSolLoop;
    use RefreshDatabase;

    private static function log(BotSession $b, string $rule, bool $ok): void
    {
        $b->log[] = ['sol' => $b->sol, 'rule' => $rule, 'url' => '/x/'.$rule.'/'.count($b->log), 'status' => $ok ? 200 : 422,
            'ok' => $ok, 'error' => $ok ? null : 'rejected', 'ap_before' => 5, 'ap_after' => $ok ? 4 : 5,
            'regolith_before' => 9, 'regolith_after' => 9, 'organics_before' => 3, 'organics_after' => 3];
    }

    public function test_a_failed_decision_blocks_its_logged_name_and_the_same_rule_is_asked_again(): void
    {
        $bot = BotSession::boot($this, 1);
        $done = false;
        $rules = [[
            'name' => 'collector',
            'when' => function (BotSession $b) use (&$done) {
                if ($done) {
                    return null;
                }

                return in_array('a', $b->blockedThisSol, true) ? 'b' : 'a';
            },
            'do' => function (BotSession $b, string $pick) use (&$done) {
                self::log($b, $pick, $pick === 'b');
                $done = $pick === 'b';

                return ['ok' => $pick === 'b'];
            },
        ]];

        $this->playOneSol($bot, $rules);

        $this->assertSame(['a', 'b'], array_column($bot->log, 'rule'));
        $this->assertSame(['a'], $bot->blockedThisSol);
    }

    public function test_an_explicit_block_name_wins_over_the_log_entry(): void
    {
        $bot = BotSession::boot($this, 1);
        $rules = [[
            'name' => 'collector',
            'when' => fn (BotSession $b) => in_array('y', $b->blockedThisSol, true) ? null : 'go',
            'do' => function (BotSession $b) {
                self::log($b, 'x', false);

                return ['ok' => false, 'block' => 'y'];
            },
        ]];

        $this->playOneSol($bot, $rules);

        $this->assertSame(['y'], $bot->blockedThisSol);
    }

    public function test_legacy_rules_continue_with_the_next_rule_after_a_failure(): void
    {
        $bot = BotSession::boot($this, 1);
        $trace = [];
        $fired = 0;
        $rules = [
            ['name' => 'r1',
                'when' => function () use (&$trace) {
                    $trace[] = 'when:r1';

                    return true;
                },
                'do' => function (BotSession $b) use (&$trace) {
                    $trace[] = 'do:r1';
                    self::log($b, 'r1', false);

                    return ['ok' => false];
                }],
            ['name' => 'r2',
                'when' => function () use (&$trace, &$fired) {
                    $trace[] = 'when:r2';

                    return $fired < 1;
                },
                'do' => function (BotSession $b) use (&$trace, &$fired) {
                    $trace[] = 'do:r2';
                    $fired++;
                    self::log($b, 'r2', true);

                    return ['ok' => true];
                }],
        ];

        $this->playOneSol($bot, $rules);

        $this->assertSame(['when:r1', 'do:r1', 'when:r2', 'do:r2', 'when:r2'], $trace);
        $this->assertSame(['r1'], $bot->blockedThisSol);
    }

    public function test_a_failure_without_a_log_entry_blocks_the_rule_name(): void
    {
        $bot = BotSession::boot($this, 1);
        $calls = 0;
        $rules = [[
            'name' => 'silent',
            'when' => function () use (&$calls) {
                $calls++;

                return true;
            },
            'do' => fn () => ['ok' => false],
        ]];

        $this->playOneSol($bot, $rules);

        $this->assertSame(1, $calls);
        $this->assertSame(['silent'], $bot->blockedThisSol);
    }

    public function test_an_already_blocked_block_name_falls_back_to_the_rule_name(): void
    {
        $bot = BotSession::boot($this, 1);
        $calls = 0;
        $rules = [[
            'name' => 'loop',
            'when' => fn () => true,
            'do' => function () use (&$calls) {
                $calls++;

                return ['ok' => false, 'block' => 'same'];
            },
        ]];

        $this->playOneSol($bot, $rules);

        $this->assertSame(2, $calls);
        $this->assertSame(['same', 'loop'], $bot->blockedThisSol);
    }

    public function test_the_blocked_list_starts_empty_every_sol(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->playOneSol($bot, [['name' => 'silent', 'when' => fn () => true, 'do' => fn () => ['ok' => false]]]);
        $this->assertSame(['silent'], $bot->blockedThisSol);

        $seen = null;
        $this->playOneSol($bot, [['name' => 'probe', 'when' => function (BotSession $b) use (&$seen) {
            $seen ??= $b->blockedThisSol;

            return null;
        }, 'do' => fn () => ['ok' => true]]]);

        $this->assertSame([], $seen);
    }
}
```

- [ ] **Step 2: Test rot bestätigen**

Run: `bin/phpunit tests/Feature/Playtest/PlaysSolLoopBlockingTest.php`
Expected: FAIL/ERROR — `Undefined property: Tests\Feature\Playtest\BotSession::$blockedThisSol`.

- [ ] **Step 3: `BotSession` erweitern**

In `tests/Feature/Playtest/BotSession.php` nach Zeile 28 (`public array $log = [];`) einfügen:

```php

    /**
     * Names blocked for the rest of the current Sol (PlaysSolLoop, spec 3.3) —
     * mirrored here so the utility selector can skip them. Reset every Sol.
     *
     * @var list<string>
     */
    public array $blockedThisSol = [];
```

- [ ] **Step 4: `playOneSol()` und `noOpSignature()` ersetzen**

`tests/Feature/Playtest/PlaysSolLoop.php`, Zeilen 64–133 (von `/**` vor `playOneSol` bis Dateiende vor `}`) ersetzen durch:

```php
    /**
     * A failed action blocks the name it was logged under (or the response's
     * explicit `block`), falling back to the rule name. When that is the rule's own
     * name the loop moves on to the next rule, exactly like before; otherwise the
     * same rule is asked again (the utility collector then offers its next
     * candidate). A block name that is already blocked falls back to the rule name,
     * so the retry can never spin.
     *
     * @param  array<int, array{name:string, when:callable, do:callable}>  $rules
     */
    private function playOneSol(BotSession $bot, array $rules): void
    {
        $rules = array_values($rules);
        $ruleCount = count($rules);
        $blockedThisSol = [];
        $bot->blockedThisSol = [];
        $lastNoOp = null;
        $noOpRepeats = 0;

        for ($i = 0; $i < self::MAX_ACTIONS_PER_SOL; $i++) {
            $fired = false;
            $r = 0;

            while ($r < $ruleCount) {
                $rule = $rules[$r];
                if (in_array($rule['name'], $blockedThisSol, true)) {
                    $r++;

                    continue;
                }

                $candidate = $rule['when']($bot);
                if (! $candidate) {
                    $r++;

                    continue;
                }

                $logCount = count($bot->log);
                $res = $rule['do']($bot, $candidate);
                if (! $res['ok']) {
                    $block = self::blockName($bot, $logCount, $rule['name'], $res, $blockedThisSol);
                    $blockedThisSol[] = $block;
                    $bot->blockedThisSol = $blockedThisSol;
                    if ($block === $rule['name']) {
                        $r++;
                    }

                    continue;
                }

                $noOp = self::noOpSignature($bot, $logCount, $res);
                $noOpRepeats = ($noOp !== null && $noOp === $lastNoOp) ? $noOpRepeats + 1 : 0;
                $lastNoOp = $noOp;
                if ($noOpRepeats >= self::NO_OP_REPEAT_LIMIT) {
                    $this->fail("Rule '{$bot->log[$logCount]['rule']}' repeated a no-op success (identical response, no AP/Regolith/Organics change) on Sol {$bot->sol} — likely state desync. Response: "
                        .json_encode($res['body'] ?? null).' Log tail: '.json_encode(array_slice($bot->log, -5)));
                }

                $fired = true;
                break;
            }

            if (! $fired) {
                return;
            }
        }

        $this->fail('MAX_ACTIONS_PER_SOL exceeded on Sol '.$bot->sol.' — likely state desync. Log tail: '
            .json_encode(array_slice($bot->log, -20)));
    }

    /** @param list<string> $blocked */
    private static function blockName(BotSession $bot, int $logCountBefore, string $ruleName, array $res, array $blocked): string
    {
        $name = $res['block'] ?? (count($bot->log) > $logCountBefore ? $bot->log[count($bot->log) - 1]['rule'] : $ruleName);

        return in_array($name, $blocked, true) ? $ruleName : $name;
    }

    /**
     * Signature of a successful action that changed nothing observable — exactly one
     * act() log entry with no AP/Regolith/Organics delta — or null when it did change
     * something (or made no/several requests, which this check cannot judge). Keyed
     * by the logged name, so it also names the real action behind the collector.
     */
    private static function noOpSignature(BotSession $bot, int $logCountBefore, array $res): ?string
    {
        if (count($bot->log) !== $logCountBefore + 1) {
            return null;
        }
        $entry = $bot->log[$logCountBefore];
        if ($entry['ap_before'] !== $entry['ap_after']
            || $entry['regolith_before'] !== $entry['regolith_after']
            || $entry['organics_before'] !== $entry['organics_after']) {
            return null;
        }

        return $entry['rule'].'|'.$entry['url'].'|'.md5((string) json_encode($res['body'] ?? null));
    }
}
```

- [ ] **Step 5: Tests grün**

Run: `bin/phpunit tests/Feature/Playtest/PlaysSolLoopBlockingTest.php tests/Feature/Playtest/PlaysSolLoopActionCapTest.php tests/Feature/Playtest/PlaysSolLoopUntilSolTest.php`
Expected: PASS (alle).

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Playtest/PlaysSolLoop.php tests/Feature/Playtest/BotSession.php tests/Feature/Playtest/PlaysSolLoopBlockingTest.php
git commit -m "test(playtest): Loop sperrt den Entscheidungsnamen statt der Regel (Utility S0)"
```

---

## Task 3 (S0): Kandidat, Entscheidung, Selektor

**Files:**
- Create: `tests/Feature/Playtest/Utility/Candidate.php`, `Decision.php`, `CandidateSource.php`, `BotContext.php`, `UtilitySelector.php`
- Test: `tests/Unit/Playtest/UtilitySelectorTest.php` (neu, schnelle Suite)

**Interfaces:**
- Produces:
  - `Candidate(string $name, string $key, int $band, ?float $net, int $apCost, \Closure $do, array $terms = [], bool $mandatory = false, mixed $payload = null, int $regolithCost = 0)`; `milliScore(): int`, `isScored(): bool`.
  - `Decision(Candidate $chosen, int $candidatesN, array $alternatives, array $all = [])`.
  - `interface CandidateSource { name(): string; band(): int; candidates(BotSession $b, BotContext $ctx): array }`.
  - `BotContext(int $sol, array $weights = [])` (wird in Task 11 erweitert).
  - `UtilitySelector::DEFAULT_MIN_NET = 0.01`; `select(array $candidates, array $blocked, float $minNet = 0.01): ?Decision`; `selectFromSources(array $sources, BotSession $b, BotContext $ctx, array $blocked, int $minBand = -1, float $minNet = 0.01, bool $fullSearch = false): ?Decision`.

- [ ] **Step 1: Failing test schreiben**

```php
<?php

namespace Tests\Unit\Playtest;

use PHPUnit\Framework\TestCase;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\Utility\BotContext;
use Tests\Feature\Playtest\Utility\Candidate;
use Tests\Feature\Playtest\Utility\CandidateSource;
use Tests\Feature\Playtest\Utility\UtilitySelector;

final class FakeSource implements CandidateSource
{
    public int $calls = 0;

    /** @param list<Candidate> $candidates */
    public function __construct(private readonly string $name, private readonly int $band, private readonly array $candidates) {}

    public function name(): string
    {
        return $this->name;
    }

    public function band(): int
    {
        return $this->band;
    }

    public function candidates(BotSession $b, BotContext $ctx): array
    {
        $this->calls++;

        return $this->candidates;
    }
}

/** Spec 4, "Selektor (Unit, rein)": property tests of the band/score selection. */
class UtilitySelectorTest extends TestCase
{
    private static function c(string $name, int $band, ?float $net = null, int $ap = 1, string $key = '', bool $mandatory = false): Candidate
    {
        return new Candidate(name: $name, key: $key !== '' ? $key : $name, band: $band, net: $net, apCost: $ap,
            do: fn () => ['ok' => true], mandatory: $mandatory);
    }

    private static function bot(): BotSession
    {
        return (new \ReflectionClass(BotSession::class))->newInstanceWithoutConstructor();
    }

    public function test_empty_list_yields_null(): void
    {
        $this->assertNull(UtilitySelector::select([], []));
    }

    public function test_lowest_band_wins_over_any_score(): void
    {
        $this->assertSame('b', UtilitySelector::select([self::c('a', 5, 100.0), self::c('b', 2)], [])->chosen->name);
    }

    public function test_within_a_band_the_highest_score_per_ap_wins(): void
    {
        $chosen = UtilitySelector::select([self::c('x', 3, 10.0, 10), self::c('y', 3, 4.0, 2)], [])->chosen;

        $this->assertSame('y', $chosen->name);
        $this->assertSame(2000, $chosen->milliScore());
    }

    public function test_order_of_the_input_does_not_change_the_choice(): void
    {
        $set = [self::c('a', 1, 3.0), self::c('b', 1, 5.0, 2), self::c('c', 1, 2.6), self::c('d', 2, 99.0)];
        $expected = UtilitySelector::select($set, [])->chosen->key;

        foreach ([[3, 2, 1, 0], [1, 3, 0, 2], [2, 0, 3, 1]] as $order) {
            $this->assertSame($expected, UtilitySelector::select(array_map(fn (int $i) => $set[$i], $order), [])->chosen->key);
        }
    }

    public function test_equal_scores_fall_back_to_name_then_key(): void
    {
        $chosen = UtilitySelector::select([self::c('b', 1, 2.0, 1, 'k1'), self::c('a', 1, 2.0, 1, 'k2'), self::c('a', 1, 2.0, 1, 'k0')], [])->chosen;

        $this->assertSame(['a', 'k0'], [$chosen->name, $chosen->key]);
    }

    public function test_milli_rounding_turns_near_equal_scores_into_ties(): void
    {
        $chosen = UtilitySelector::select([self::c('b', 1, 1.0000001), self::c('a', 1, 1.0)], [])->chosen;

        $this->assertSame('a', $chosen->name);
    }

    public function test_blocked_names_are_never_chosen(): void
    {
        $this->assertSame('b', UtilitySelector::select([self::c('a', 1), self::c('b', 2)], ['a'])->chosen->name);
        $this->assertNull(UtilitySelector::select([self::c('a', 1)], ['a']));
    }

    public function test_scored_candidates_at_or_below_min_net_are_not_eligible(): void
    {
        $this->assertSame('b', UtilitySelector::select([self::c('a', 1, 0.01), self::c('b', 2)], [], 0.01)->chosen->name);
        $this->assertNull(UtilitySelector::select([self::c('a', 1, -3.0)], []));
    }

    public function test_unscored_candidates_are_always_eligible(): void
    {
        $this->assertFalse(self::c('a', 1)->isScored());
        $this->assertSame('a', UtilitySelector::select([self::c('a', 1)], [])->chosen->name);
    }

    public function test_positive_scaling_of_all_utilities_keeps_the_choice(): void
    {
        $nets = ['a' => 3.0, 'b' => 7.5, 'c' => 4.2];
        $pick = fn (float $f) => UtilitySelector::select(array_map(
            fn (string $n) => self::c($n, 1, $nets[$n] * $f, $n === 'b' ? 3 : 1), array_keys($nets)), [])->chosen->name;

        $this->assertSame($pick(1.0), $pick(10.0));
    }

    public function test_decision_lists_up_to_three_alternatives_and_the_candidate_count(): void
    {
        $d = UtilitySelector::select([self::c('a', 1, 5.0), self::c('b', 1, 4.0), self::c('c', 1, 3.0), self::c('d', 1, 2.0), self::c('e', 1, 1.0)], []);

        $this->assertSame(5, $d->candidatesN);
        $this->assertSame(['b', 'c', 'd'], array_column($d->alternatives, 'name'));
        $this->assertSame(4000, $d->alternatives[0]['score']);
    }

    public function test_source_search_stops_after_the_first_band_with_an_eligible_candidate(): void
    {
        $empty = new FakeSource('empty', 0, []);
        $hit = new FakeSource('hit', 1, [self::c('hit', 1)]);
        $late = new FakeSource('late', 2, [self::c('late', 2)]);

        $d = UtilitySelector::selectFromSources([$late, $hit, $empty], self::bot(), new BotContext(sol: 0), []);

        $this->assertSame('hit', $d->chosen->name);
        $this->assertSame([1, 1, 0], [$empty->calls, $hit->calls, $late->calls]);
    }

    public function test_sources_of_the_same_band_are_all_asked_before_stopping(): void
    {
        $x = new FakeSource('x', 1, [self::c('x', 1, 1.0)]);
        $y = new FakeSource('y', 1, [self::c('y', 1, 9.0)]);

        $this->assertSame('y', UtilitySelector::selectFromSources([$x, $y], self::bot(), new BotContext(sol: 0), [])->chosen->name);
    }

    public function test_full_search_gives_the_same_choice_as_the_early_stop(): void
    {
        $sources = fn () => [new FakeSource('a', 0, []), new FakeSource('b', 1, [self::c('b', 1)]), new FakeSource('c', 2, [self::c('c', 2, 50.0)])];

        $early = UtilitySelector::selectFromSources($sources(), self::bot(), new BotContext(sol: 0), []);
        $full = UtilitySelector::selectFromSources($sources(), self::bot(), new BotContext(sol: 0), [], fullSearch: true);

        $this->assertSame($early->chosen->key, $full->chosen->key);
        $this->assertCount(2, $full->all);
    }

    public function test_sources_below_the_cursor_band_and_blocked_sources_are_not_asked(): void
    {
        $below = new FakeSource('below', 0, [self::c('below', 0)]);
        $blocked = new FakeSource('blocked', 1, [self::c('blocked', 1)]);
        $open = new FakeSource('open', 2, [self::c('open', 2)]);

        $d = UtilitySelector::selectFromSources([$below, $blocked, $open], self::bot(), new BotContext(sol: 0), ['blocked'], minBand: 1);

        $this->assertSame('open', $d->chosen->name);
        $this->assertSame([0, 0, 1], [$below->calls, $blocked->calls, $open->calls]);
    }
}
```

- [ ] **Step 2: Test rot bestätigen**

Run: `bin/phpunit tests/Unit/Playtest/UtilitySelectorTest.php`
Expected: ERROR — `Interface "Tests\Feature\Playtest\Utility\CandidateSource" not found`.

- [ ] **Step 3: Wertobjekte anlegen**

`tests/Feature/Playtest/Utility/Candidate.php`:

```php
<?php

namespace Tests\Feature\Playtest\Utility;

/**
 * One possible bot action (spec 3.1). `net` is the utility in Rg-equivalents
 * after costs, or null for an unscored legacy candidate, which is ordered by its
 * band only. `band` is the legacy rule index the candidate stands in for.
 */
final readonly class Candidate
{
    /**
     * @param  array<string, float>  $terms  breakdown of `net` (log/report only)
     * @param  \Closure(\Tests\Feature\Playtest\BotSession): array  $do
     */
    public function __construct(
        public string $name,
        public string $key,
        public int $band,
        public ?float $net,
        public int $apCost,
        public \Closure $do,
        public array $terms = [],
        public bool $mandatory = false,
        public mixed $payload = null,
        public int $regolithCost = 0,
    ) {}

    public function isScored(): bool
    {
        return $this->net !== null;
    }

    /** Utility per AP in milli-Rg-equivalents (spec 2.10); 0 for unscored candidates. */
    public function milliScore(): int
    {
        return $this->net === null ? 0 : (int) round($this->net / max(1, $this->apCost) * 1000);
    }
}
```

`tests/Feature/Playtest/Utility/Decision.php`:

```php
<?php

namespace Tests\Feature\Playtest\Utility;

final readonly class Decision
{
    /**
     * @param  list<array{name:string, key:string, band:int, score:?int}>  $alternatives  next best (max 3)
     * @param  list<Candidate>  $all  every candidate collected for this decision
     */
    public function __construct(
        public Candidate $chosen,
        public int $candidatesN,
        public array $alternatives,
        public array $all = [],
    ) {}
}
```

`tests/Feature/Playtest/Utility/CandidateSource.php`:

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use Tests\Feature\Playtest\BotSession;

/** Spec 3.1: one producer of candidates; `band` orders sources like the legacy rule list. */
interface CandidateSource
{
    public function name(): string;

    public function band(): int;

    /** @return list<Candidate> */
    public function candidates(BotSession $b, BotContext $ctx): array;
}
```

`tests/Feature/Playtest/Utility/BotContext.php`:

```php
<?php

namespace Tests\Feature\Playtest\Utility;

/** State shared by all sources for one decision (spec 3.1). Extended in S2 (Task 11). */
final class BotContext
{
    /** @param array<string, mixed> $weights */
    public function __construct(
        public readonly int $sol,
        public readonly array $weights = [],
    ) {}
}
```

- [ ] **Step 4: Selektor implementieren**

`tests/Feature/Playtest/Utility/UtilitySelector.php`:

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use Tests\Feature\Playtest\BotSession;

/**
 * Spec 3.2: picks the best eligible candidate. Order: band ascending, milli-score
 * descending, name, key. Eligible = name not blocked and (unscored or net > min_net).
 * Pure: no DB access of its own.
 */
final class UtilitySelector
{
    public const DEFAULT_MIN_NET = 0.01;

    /**
     * @param  list<Candidate>  $candidates
     * @param  list<string>  $blocked
     */
    public static function select(array $candidates, array $blocked, float $minNet = self::DEFAULT_MIN_NET): ?Decision
    {
        $eligible = array_values(array_filter($candidates, fn (Candidate $c): bool => self::eligible($c, $blocked, $minNet)));
        if ($eligible === []) {
            return null;
        }
        usort($eligible, [self::class, 'compare']);

        $alternatives = array_map(fn (Candidate $c): array => [
            'name' => $c->name,
            'key' => $c->key,
            'band' => $c->band,
            'score' => $c->isScored() ? $c->milliScore() : null,
        ], array_slice($eligible, 1, 3));

        return new Decision($eligible[0], count($eligible), $alternatives, $candidates);
    }

    /**
     * Asks the sources band by band (stable for equal bands) and stops after the
     * first band that produced an eligible candidate — for legacy sources that is
     * exactly the first-match semantics, with the same `when` calls. Sources below
     * $minBand (the collector's cursor after a failure) and blocked sources are not
     * asked. $fullSearch asks every source (shadow mode); the choice is the same.
     *
     * @param  list<CandidateSource>  $sources
     * @param  list<string>  $blocked
     */
    public static function selectFromSources(array $sources, BotSession $b, BotContext $ctx, array $blocked, int $minBand = -1, float $minNet = self::DEFAULT_MIN_NET, bool $fullSearch = false): ?Decision
    {
        usort($sources, fn (CandidateSource $x, CandidateSource $y): int => $x->band() <=> $y->band());

        $collected = [];
        $bestBand = null;
        foreach ($sources as $source) {
            if ($bestBand !== null && ! $fullSearch && $source->band() > $bestBand) {
                break;
            }
            if ($source->band() < $minBand || in_array($source->name(), $blocked, true)) {
                continue;
            }
            foreach ($source->candidates($b, $ctx) as $candidate) {
                $collected[] = $candidate;
                if (self::eligible($candidate, $blocked, $minNet)) {
                    $bestBand = $bestBand === null ? $candidate->band : min($bestBand, $candidate->band);
                }
            }
        }

        $decision = self::select($collected, $blocked, $minNet);

        return $decision === null ? null
            : new Decision($decision->chosen, $decision->candidatesN, $decision->alternatives, $collected);
    }

    /** @param list<string> $blocked */
    private static function eligible(Candidate $c, array $blocked, float $minNet): bool
    {
        return ! in_array($c->name, $blocked, true) && ($c->net === null || $c->net > $minNet);
    }

    private static function compare(Candidate $a, Candidate $b): int
    {
        return [$a->band, -$a->milliScore(), $a->name, $a->key] <=> [$b->band, -$b->milliScore(), $b->name, $b->key];
    }
}
```

- [ ] **Step 5: Tests grün**

Run: `bin/phpunit tests/Unit/Playtest/UtilitySelectorTest.php`
Expected: PASS (15 Tests).

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Playtest/Utility/Candidate.php tests/Feature/Playtest/Utility/Decision.php tests/Feature/Playtest/Utility/CandidateSource.php tests/Feature/Playtest/Utility/BotContext.php tests/Feature/Playtest/Utility/UtilitySelector.php tests/Unit/Playtest/UtilitySelectorTest.php
git commit -m "test(playtest): UtilitySelector mit Band/Score-Ordnung (Utility S0)"
```

---
## Task 4 (S0): Legacy-Adapter, Sammler, Golden-Test

**Files:**
- Create: `tests/Feature/Playtest/Utility/LegacyRuleSource.php`, `Collector.php`, `UtilityStrategy.php`
- Test: `tests/Feature/Playtest/Utility/CollectorAdapterTest.php`, `tests/Feature/Playtest/Utility/AdapterGoldenTest.php` (beide neu, Playtest-Suite)

**Interfaces:**
- Consumes: Task 2 (`BotSession::$blockedThisSol`, `block`-Schlüssel), Task 3 (Selektor).
- Produces:
  - `LegacyRuleSource(array $rule, int $band, bool $mandatory)`; Kandidat: `name` = `key` = Regelname, `net` = null, `payload` = Rückgabe von `when`.
  - `Collector(array $sources, string $mode, BotProfile $profile, ?\Closure $apProbe = null)`; `rule(): array{name:'utility', when, do}`; Modi `adapter` | `utility` (ab Task 13 auch `shadow`).
  - `UtilityStrategy::MANDATORY = ['repair_critical', 'place_agrardom', 'feed_colony']`; `legacySourcesFrom(array $rules): list<LegacyRuleSource>`; `fromRules(array $rules, string $mode, BotProfile $profile): array`; `adapterOnly(BotProfile $profile): array`; `rules(BotProfile $profile): array` (S0–S2: wie `adapterOnly`, aber Modus `utility`).

- [ ] **Step 1: Failing tests schreiben**

`tests/Feature/Playtest/Utility/CollectorAdapterTest.php`:

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\PlaysSolLoop;
use Tests\TestCase;

/** Spec 3.2/3.3: behind the collector the legacy rules get the same when/do calls in the same order. */
class CollectorAdapterTest extends TestCase
{
    use PlaysSolLoop;
    use RefreshDatabase;

    private static function log(BotSession $b, string $rule, bool $ok): void
    {
        $b->log[] = ['sol' => $b->sol, 'rule' => $rule, 'url' => '/x/'.$rule.'/'.count($b->log), 'status' => $ok ? 200 : 422,
            'ok' => $ok, 'error' => $ok ? null : 'rejected', 'ap_before' => 5, 'ap_after' => $ok ? 4 : 5,
            'regolith_before' => 9, 'regolith_after' => 9, 'organics_before' => 3, 'organics_after' => 3];
    }

    /** @param list<string> $trace */
    private function rules(array &$trace): array
    {
        $fired = 0;

        return [
            ['name' => 'first',
                'when' => function () use (&$trace) {
                    $trace[] = 'when:first';

                    return null;
                },
                'do' => fn () => ['ok' => true]],
            ['name' => 'flaky',
                'when' => function () use (&$trace) {
                    $trace[] = 'when:flaky';

                    return 'f';
                },
                'do' => function (BotSession $b) use (&$trace) {
                    $trace[] = 'do:flaky';
                    self::log($b, 'flaky', false);

                    return ['ok' => false];
                }],
            ['name' => 'worker',
                'when' => function () use (&$trace, &$fired) {
                    $trace[] = 'when:worker';

                    return $fired < 2 ? 'w' : null;
                },
                'do' => function (BotSession $b, string $c) use (&$trace, &$fired) {
                    $trace[] = 'do:worker:'.$c;
                    $fired++;
                    self::log($b, 'worker', true);

                    return ['ok' => true];
                }],
        ];
    }

    public function test_adapter_calls_when_and_do_in_the_legacy_order(): void
    {
        $bot = BotSession::boot($this, 1);
        $legacyTrace = [];
        $this->playOneSol($bot, $this->rules($legacyTrace));
        $legacyLog = array_column($bot->log, 'rule');

        $bot->log = [];
        $adapterTrace = [];
        $this->playOneSol($bot, UtilityStrategy::fromRules($this->rules($adapterTrace), 'adapter', BotProfile::named('default')));

        $this->assertSame($legacyTrace, $adapterTrace);
        $this->assertSame($legacyLog, array_column($bot->log, 'rule'));
        $this->assertSame(['flaky'], $bot->blockedThisSol);
    }

    public function test_bands_follow_the_rule_index_and_mandatory_rules_are_marked(): void
    {
        $rules = BotStrategy::default(BotProfile::named('default'));
        $sources = UtilityStrategy::legacySourcesFrom($rules);

        $this->assertCount(count($rules), $sources);
        foreach ($sources as $i => $source) {
            $this->assertSame($rules[$i]['name'], $source->name());
            $this->assertSame($i, $source->band());
        }
        $mandatory = array_values(array_map(fn (LegacyRuleSource $s) => $s->name(), array_filter($sources, fn (LegacyRuleSource $s) => $s->mandatory)));
        $this->assertSame(['repair_critical', 'place_agrardom', 'feed_colony'], $mandatory);
    }
}
```

`tests/Feature/Playtest/Utility/AdapterGoldenTest.php`:

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\PlaysSolLoop;
use Tests\TestCase;

/**
 * Spec 4 (S0, Golden): every legacy rule as a LegacyRuleSource behind the selector
 * must play exactly the legacy action sequence. Both runs live in this test (no
 * fixture file that could drift). Short runs (Sols 0-7) keep the playtest suite
 * fast; R5b made all game rolls depend on runs.rng_seed only, so two bots with the
 * same seed in one database play the same game. No wall clock: BotSession::boot()
 * aligns TickService with the run.
 */
class AdapterGoldenTest extends TestCase
{
    use PlaysSolLoop;
    use RefreshDatabase;

    private const UNTIL_SOL = 8;

    /** URL segments carrying auto-increment row ids, which differ between two bots in one database. */
    private const ID_SEGMENTS = [
        '#^/advisors/\d+/#' => '/advisors/#/',
        '#^/colony/bar/(accept|accept-encounter|concern|information-encounter)/\d+$#' => '/colony/bar/$1/#',
        '#^/colony/merchant/buy/\d+$#' => '/colony/merchant/buy/#',
    ];

    public static function seeds(): array
    {
        return ['seed 4242 labor' => [4242, 'labor'], 'seed 7 hangar' => [7, 'hangar']];
    }

    public function test_two_legacy_runs_with_the_same_seed_play_the_same_sequence(): void
    {
        $profile = BotProfile::named('default')->withOpening('hangar');

        $first = $this->play(7, BotStrategy::default($profile));
        $second = $this->play(7, BotStrategy::default($profile));

        $this->assertSame($first, $second, 'Legacy itself is not deterministic — stop and report to the Owner before comparing engines. '.self::firstDifference($first, $second));
    }

    #[DataProvider('seeds')]
    public function test_adapter_plays_the_legacy_action_sequence(int $seed, string $opening): void
    {
        $profile = BotProfile::named('default')->withOpening($opening);

        $legacy = $this->play($seed, BotStrategy::default($profile));
        $adapter = $this->play($seed, UtilityStrategy::adapterOnly($profile));

        $this->assertGreaterThan(self::UNTIL_SOL + 5, count($legacy), 'the short run must exercise more than sol_next');
        $this->assertSame($legacy, $adapter, self::firstDifference($legacy, $adapter));
    }

    /** @return list<array{0:int, 1:string, 2:string, 3:bool, 4:?string}> */
    private function play(int $seed, array $rules): array
    {
        $bot = BotSession::boot($this, $seed);
        $this->playSolsUntil($bot, $rules, self::stopAtSol(self::UNTIL_SOL));

        return array_map(fn (array $e): array => [$e['sol'], $e['rule'], self::normalizeUrl($e['url']), $e['ok'], $e['error']], $bot->log);
    }

    private static function normalizeUrl(string $url): string
    {
        return (string) preg_replace(array_keys(self::ID_SEGMENTS), array_values(self::ID_SEGMENTS), $url);
    }

    private static function firstDifference(array $a, array $b): string
    {
        foreach ($a as $i => $entry) {
            if (($b[$i] ?? null) !== $entry) {
                return "First difference at action {$i}: first ".json_encode($entry).' vs second '.json_encode($b[$i] ?? null);
            }
        }

        return count($a) === count($b) ? 'identical' : 'second run has '.(count($b) - count($a)).' extra actions';
    }
}
```

- [ ] **Step 2: Tests rot bestätigen**

Run: `bin/phpunit tests/Feature/Playtest/Utility/CollectorAdapterTest.php tests/Feature/Playtest/Utility/AdapterGoldenTest.php`
Expected: ERROR `Class "Tests\Feature\Playtest\Utility\UtilityStrategy" not found` in den Adapter-Tests. `test_two_legacy_runs_with_the_same_seed_play_the_same_sequence` muss bereits **PASS** sein. Ist er rot: STOP, nicht weiterbauen — Legacy ist nicht deterministisch; Befund (erste Differenz) an den Owner geben (Memory „maskierte Bug-Kaskade“).

- [ ] **Step 3: `LegacyRuleSource` anlegen**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use Tests\Feature\Playtest\BotSession;

/**
 * Spec S0: one legacy rule as a candidate source. `when` is called exactly as the
 * old loop called it; its truthy result becomes one unscored candidate whose
 * `do` runs the rule's `do` with that result.
 */
final class LegacyRuleSource implements CandidateSource
{
    /** @param array{name:string, when:callable, do:callable} $rule */
    public function __construct(
        private readonly array $rule,
        private readonly int $band,
        public readonly bool $mandatory = false,
    ) {}

    public function name(): string
    {
        return $this->rule['name'];
    }

    public function band(): int
    {
        return $this->band;
    }

    public function candidates(BotSession $b, BotContext $ctx): array
    {
        $payload = ($this->rule['when'])($b);
        if (! $payload) {
            return [];
        }
        $do = $this->rule['do'];

        return [new Candidate(
            name: $this->rule['name'],
            key: $this->rule['name'],
            band: $this->band,
            net: null,
            apCost: 0,
            do: fn (BotSession $s): array => $do($s, $payload),
            mandatory: $this->mandatory,
            payload: $payload,
        )];
    }
}
```

- [ ] **Step 4: `Collector` anlegen**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;

/**
 * The utility engine as ONE rule for PlaysSolLoop (spec 3.3). After a failed
 * decision the cursor skips every band below the failed one until the next
 * success or Sol — the same "continue with the next rule" semantics the legacy
 * loop has, so the adapter makes the same `when` calls.
 */
final class Collector
{
    public const MODES = ['adapter', 'utility'];

    private int $sol = -1;

    private int $cursorBand = -1;

    /** @param list<CandidateSource> $sources */
    public function __construct(
        private readonly array $sources,
        private readonly string $mode,
        private readonly BotProfile $profile,
        private readonly ?\Closure $apProbe = null,
    ) {
        if (! in_array($mode, self::MODES, true)) {
            throw new \InvalidArgumentException("Unknown collector mode: {$mode}");
        }
    }

    /** @return array{name:string, when:callable, do:callable} */
    public function rule(): array
    {
        return [
            'name' => 'utility',
            'when' => fn (BotSession $b) => $this->when($b),
            'do' => fn (BotSession $b, Decision $d) => $this->do($b, $d),
        ];
    }

    public function when(BotSession $b): ?Decision
    {
        if ($b->sol !== $this->sol) {
            $this->sol = $b->sol;
            $this->cursorBand = -1;
        }

        return UtilitySelector::selectFromSources($this->sources, $b, new BotContext(sol: $b->sol), $b->blockedThisSol, $this->cursorBand);
    }

    public function do(BotSession $b, Decision $d): array
    {
        $res = ($d->chosen->do)($b);
        $this->cursorBand = $res['ok'] ? -1 : $d->chosen->band;
        $res['block'] = $d->chosen->name;

        return $res;
    }
}
```

- [ ] **Step 5: `UtilityStrategy` anlegen**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotStrategy;

/** Builds the rule list (old format) of the utility engines. */
final class UtilityStrategy
{
    /** Spec 2.8: stays rule-like (unscored, by band) until S8. */
    public const MANDATORY = ['repair_critical', 'place_agrardom', 'feed_colony'];

    /** Engine "utility". S0-S2: every rule is still a legacy source. */
    public static function rules(BotProfile $profile): array
    {
        return self::fromRules(BotStrategy::default($profile), 'utility', $profile);
    }

    /** Golden-test reference: every legacy rule behind the selector, nothing migrated. */
    public static function adapterOnly(BotProfile $profile): array
    {
        return self::fromRules(BotStrategy::default($profile), 'adapter', $profile);
    }

    /** @param list<array{name:string, when:callable, do:callable}> $rules */
    public static function fromRules(array $rules, string $mode, BotProfile $profile): array
    {
        return [(new Collector(self::legacySourcesFrom($rules), $mode, $profile))->rule()];
    }

    /**
     * @param  list<array{name:string, when:callable, do:callable}>  $rules
     * @return list<LegacyRuleSource>
     */
    public static function legacySourcesFrom(array $rules): array
    {
        $sources = [];
        foreach (array_values($rules) as $band => $rule) {
            $sources[] = new LegacyRuleSource($rule, $band, in_array($rule['name'], self::MANDATORY, true));
        }

        return $sources;
    }
}
```

- [ ] **Step 6: Tests grün, Laufzeit notieren**

Run: `time bin/phpunit tests/Feature/Playtest/Utility/CollectorAdapterTest.php tests/Feature/Playtest/Utility/AdapterGoldenTest.php`
Expected: PASS (5 Tests). Laufzeit des Golden-Tests notieren (Erwartung < 4 min). Liegt sie über 5 min: `UNTIL_SOL` auf 6 senken und im Commit vermerken. Schlägt der Adapter-Vergleich fehl: zuerst die erste Differenz lesen und prüfen, ob ein `when` mit Seiteneffekt (Spec R7) doppelt aufgerufen wird.

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/Playtest/Utility/LegacyRuleSource.php tests/Feature/Playtest/Utility/Collector.php tests/Feature/Playtest/Utility/UtilityStrategy.php tests/Feature/Playtest/Utility/CollectorAdapterTest.php tests/Feature/Playtest/Utility/AdapterGoldenTest.php
git commit -m "test(playtest): Legacy-Adapter + Sammler, Golden-Test Legacy = Adapter (Utility S0)"
```

---

## Task 5 (S0): Engine-Schalter im Profil und im Bot-Test

**Files:**
- Modify: `tests/Feature/Playtest/BotProfile.php:16-44`
- Create: `tests/Feature/Playtest/BotEngine.php`
- Modify: `tests/Feature/Playtest/PlaytestBotTest.php:49` und `:95-118`, `:190-194`
- Test: `tests/Unit/Playtest/BotProfileTest.php` (erweitern), `tests/Unit/Playtest/BotEngineTest.php` (neu)

**Interfaces:**
- Produces: `BotProfile::ENGINES = ['legacy', 'utility']`, `BotProfile::$engine` (Default `legacy`), `withEngine(string): self`; `BotEngine::rules(BotProfile): array`; Env `PLAYTEST_ENGINE`.

- [ ] **Step 1: Failing tests schreiben**

An `tests/Unit/Playtest/BotProfileTest.php` (vor der letzten `}`) anhängen:

```php
    public function test_engine_defaults_to_legacy(): void
    {
        $this->assertSame('legacy', BotProfile::named('default')->engine);
    }

    public function test_with_engine_keeps_all_other_dials(): void
    {
        $focus = BotProfile::named('focus')->withOpening('cantina');
        $utility = $focus->withEngine('utility');

        $this->assertSame('utility', $utility->engine);
        $this->assertSame(['focus', 1.0, 1.0, 'cantina'], [$utility->name, $utility->savingsAggressiveness, $utility->objectiveFocus, $utility->opening]);
        $this->assertSame('legacy', $focus->engine);
    }

    public function test_with_opening_keeps_the_engine(): void
    {
        $this->assertSame('utility', BotProfile::named('default')->withEngine('utility')->withOpening('hangar')->engine);
    }

    public function test_unknown_engine_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        BotProfile::named('default')->withEngine('magic');
    }
```

`tests/Unit/Playtest/BotEngineTest.php`:

```php
<?php

namespace Tests\Unit\Playtest;

use PHPUnit\Framework\TestCase;
use Tests\Feature\Playtest\BotEngine;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotStrategy;

class BotEngineTest extends TestCase
{
    public function test_legacy_engine_returns_the_legacy_rule_list(): void
    {
        $profile = BotProfile::named('default');

        $this->assertSame(array_column(BotStrategy::default($profile), 'name'), array_column(BotEngine::rules($profile), 'name'));
    }

    public function test_utility_engine_returns_the_single_collector_rule(): void
    {
        $this->assertSame(['utility'], array_column(BotEngine::rules(BotProfile::named('default')->withEngine('utility')), 'name'));
    }
}
```

In `tests/Feature/Playtest/PlaytestBotTest.php` den Env-Test (Zeilen 95–118) erweitern: nach `putenv('PLAYTEST_UNTIL_SOL=20');` die Zeile `putenv('PLAYTEST_ENGINE=utility');` einfügen, nach `$this->assertSame('cantina', $profile->opening);` die Zeile `$this->assertSame('utility', $profile->engine);`, und im `finally`-Block `putenv('PLAYTEST_ENGINE');` ergänzen.

- [ ] **Step 2: Tests rot bestätigen**

Run: `bin/phpunit tests/Unit/Playtest/BotProfileTest.php tests/Unit/Playtest/BotEngineTest.php`
Expected: FAIL — `Undefined property ... $engine` bzw. `Class "Tests\Feature\Playtest\BotEngine" not found`.

- [ ] **Step 3: `BotProfile` erweitern**

In `tests/Feature/Playtest/BotProfile.php` nach Zeile 18 einfügen:

```php

    /** legacy = BotStrategy rule list; utility = UtilityStrategy (spec 4, both until S9). */
    public const ENGINES = ['legacy', 'utility'];
```

Konstruktor (Zeilen 20–38) um den letzten Parameter und die Prüfung ergänzen:

```php
        public readonly string $opening = 'auto',
        // Which decision engine plays (spec 4): legacy rule list or utility selector.
        public readonly string $engine = 'legacy',
    ) {
        if (! in_array($opening, self::OPENINGS, true)) {
            throw new \InvalidArgumentException("Unknown bot opening: {$opening}");
        }
        if (! in_array($engine, self::ENGINES, true)) {
            throw new \InvalidArgumentException("Unknown bot engine: {$engine}");
        }
    }
```

`withOpening()` (Zeilen 41–44) ersetzen durch:

```php
    /** NOTE: when adding a new dial to the constructor, copy it here and in withEngine() too. */
    public function withOpening(string $opening): self
    {
        return new self($this->name, $this->savingsAggressiveness, $this->objectiveFocus, $opening, $this->engine);
    }

    public function withEngine(string $engine): self
    {
        return new self($this->name, $this->savingsAggressiveness, $this->objectiveFocus, $this->opening, $engine);
    }
```

- [ ] **Step 4: `BotEngine` anlegen**

```php
<?php

namespace Tests\Feature\Playtest;

use Tests\Feature\Playtest\Utility\UtilityStrategy;

/** Rule list for the profile's engine (spec 4: both engines selectable until S9). */
final class BotEngine
{
    /** @return list<array{name:string, when:callable, do:callable}> */
    public static function rules(BotProfile $profile): array
    {
        return match ($profile->engine) {
            'legacy' => BotStrategy::default($profile),
            'utility' => UtilityStrategy::rules($profile),
        };
    }
}
```

- [ ] **Step 5: `PlaytestBotTest` umstellen**

Zeile 49 `$rules = BotStrategy::default($profile);` → `$rules = BotEngine::rules($profile);`
`resolveProfile()` (Zeilen 190–194) ersetzen durch:

```php
    private static function resolveProfile(): BotProfile
    {
        return BotProfile::named(getenv('PLAYTEST_PROFILE') ?: 'default')
            ->withOpening(getenv('PLAYTEST_OPENING') ?: 'auto')
            ->withEngine(getenv('PLAYTEST_ENGINE') ?: 'legacy');
    }
```

- [ ] **Step 6: Tests grün**

Run: `bin/phpunit tests/Unit/Playtest && bin/phpunit --filter test_env_vars_override_seed_and_profile tests/Feature/Playtest/PlaytestBotTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/Playtest/BotProfile.php tests/Feature/Playtest/BotEngine.php tests/Feature/Playtest/PlaytestBotTest.php tests/Unit/Playtest/BotProfileTest.php tests/Unit/Playtest/BotEngineTest.php
git commit -m "test(playtest): Engine-Schalter legacy|utility in BotProfile und PlaytestBotTest (Utility S0)"
```

---
## Task 6 (S1): `game:playtest --engine`, Report trägt die Engine

**Files:**
- Modify: `tests/Feature/Playtest/RunReport.php:50`, `:511-516`, `:553-564`, `:566-582`
- Modify: `tests/Feature/Playtest/PlaytestBotTest.php:50`
- Modify: `app/Console/Commands/Playtest.php:50-55`, `:67-73`, `:110`, `:126-139`, `:176-183`, `:205-236`
- Test: `tests/Feature/Console/PlaytestCommandEngineTest.php` (neu, schnelle Suite)

**Interfaces:**
- Produces: `RunReport::__construct(int $seed, string $profile = 'default', string $opening = 'auto', string $engine = 'legacy')`; `RunReport::filePrefix(string $profile, string $engine): string` (`legacy` → `default`, sonst `default.utility`); Report-Feld `engine`; `Playtest::latestReportFor($profile, $opening, $seed, $engine = 'legacy')`.

- [ ] **Step 1: Failing test schreiben**

```php
<?php

namespace Tests\Feature\Console;

use App\Console\Commands\Playtest;
use App\Console\Support\PlaytestDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\RunReport;
use Tests\TestCase;

/** Spec S1: game:playtest --engine passes PLAYTEST_ENGINE; reports of two engines never collide. */
class PlaytestCommandEngineTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $createdReports = [];

    protected function tearDown(): void
    {
        foreach ($this->createdReports as $path) {
            @unlink($path);
        }
        parent::tearDown();
    }

    public function test_unknown_engine_is_refused_before_the_database_is_touched(): void
    {
        Process::fake();
        $this->partialMock(PlaytestDatabase::class, fn ($mock) => $mock->shouldNotReceive('reset'));

        $this->artisan('game:playtest', ['--engine' => 'magic', '--seeds' => '1'])
            ->expectsOutputToContain('Unknown engine: magic')
            ->assertFailed();

        Process::assertNothingRan();
    }

    public function test_engine_defaults_to_legacy(): void
    {
        Process::fake();
        $this->partialMock(PlaytestDatabase::class, fn ($mock) => $mock->shouldReceive('reset')->once()->andReturn('nouron_playtest'));

        $this->artisan('game:playtest', ['--seeds' => '1']);

        Process::assertRanTimes(fn (PendingProcess $p) => $p->environment['PLAYTEST_ENGINE'] === 'legacy', 1);
    }

    public function test_utility_engine_is_passed_to_every_child(): void
    {
        Process::fake();
        $this->partialMock(PlaytestDatabase::class, fn ($mock) => $mock->shouldReceive('reset')->once()->andReturn('nouron_playtest'));

        $this->artisan('game:playtest', ['--engine' => 'utility', '--openings' => 'labor,hangar', '--seeds' => '1'])
            ->expectsOutputToContain('default.utility/labor=1');

        Process::assertRanTimes(fn (PendingProcess $p) => $p->environment['PLAYTEST_ENGINE'] === 'utility', 2);
    }

    public function test_file_prefix_keeps_legacy_names_unchanged(): void
    {
        $this->assertSame('default', RunReport::filePrefix('default', 'legacy'));
        $this->assertSame('thrifty.utility', RunReport::filePrefix('thrifty', 'utility'));
    }

    public function test_run_report_carries_the_engine_in_json_and_filename(): void
    {
        $bot = BotSession::boot($this, seed: 7);
        $report = new RunReport(7, 'default', 'hangar', 'utility');

        $data = $report->build($bot);
        $this->assertSame('utility', $data['engine']);

        $path = $report->write($data);
        $this->createdReports[] = $path;
        $this->assertStringStartsWith('default.utility-hangar-7-', basename($path));
    }

    public function test_legacy_report_name_and_engine_default(): void
    {
        $bot = BotSession::boot($this, seed: 7);
        $report = new RunReport(7, 'default', 'hangar');

        $data = $report->build($bot);
        $this->assertSame('legacy', $data['engine']);
        $path = $report->write($data);
        $this->createdReports[] = $path;
        $this->assertStringStartsWith('default-hangar-7-', basename($path));
    }

    public function test_latest_report_for_never_mixes_engines(): void
    {
        $dir = storage_path('logs/playtest');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        // Seed 987655 never occurs in real runs.
        $legacy = "{$dir}/default-labor-987655-29990101_000000.json";
        $utility = "{$dir}/default.utility-labor-987655-29990101_000001.json";
        file_put_contents($legacy, json_encode(['engine' => 'legacy']));
        file_put_contents($utility, json_encode(['engine' => 'utility']));
        $this->createdReports = [$legacy, $utility];

        $command = $this->app->make(Playtest::class);
        $method = new \ReflectionMethod($command, 'latestReportFor');

        $this->assertSame(['engine' => 'legacy'], $method->invoke($command, 'default', 'labor', '987655'));
        $this->assertSame(['engine' => 'legacy'], $method->invoke($command, 'default', 'labor', '987655', 'legacy'));
        $this->assertSame(['engine' => 'utility'], $method->invoke($command, 'default', 'labor', '987655', 'utility'));
    }
}
```

- [ ] **Step 2: Test rot bestätigen**

Run: `bin/phpunit tests/Feature/Console/PlaytestCommandEngineTest.php`
Expected: FAIL — `The "--engine" option does not exist.` bzw. `Call to undefined method ...RunReport::filePrefix()`.

- [ ] **Step 3: `RunReport` erweitern**

Zeile 50 ersetzen:

```php
    public function __construct(private readonly int $seed, private readonly string $profile = 'default', private readonly string $opening = 'auto', private readonly string $engine = 'legacy') {}

    /** Report file prefix: legacy keeps the historic `{profile}` name, other engines `{profile}.{engine}`. */
    public static function filePrefix(string $profile, string $engine): string
    {
        return $engine === 'legacy' ? $profile : "{$profile}.{$engine}";
    }
```

In `build()` nach `'opening' => $this->opening,` (Zeile 514) einfügen: `'engine' => $this->engine,`.
In `write()` (Zeile 560) den Pfad ersetzen durch:

```php
        $path = "{$dir}/".self::filePrefix($this->profile, $this->engine)."-{$this->opening}-{$this->seed}-".now()->format('Ymd_His').'.json';
```

In `printTable()` das Format um ` engine=%s` nach `opening=%s` erweitern und `$report['engine'] ?? 'legacy',` nach `$report['opening'] ?? 'auto',` einfügen.

- [ ] **Step 4: `PlaytestBotTest` reicht die Engine weiter**

Zeile 50: `$report = new RunReport($seed, $profile->name, $profile->opening, $profile->engine);`

- [ ] **Step 5: `Playtest`-Command erweitern**

Signatur (Zeilen 50–55) um eine Option ergänzen (nach `--openings`):

```php
        {--engine=legacy : Bot engine (see BotProfile::ENGINES)}
```

Nach der Openings-Prüfung (nach Zeile 73) einfügen:

```php
        $engine = (string) $this->option('engine');
        if (! in_array($engine, BotProfile::ENGINES, true)) {
            $this->error('Unknown engine: '.$engine.'. Allowed: '.implode(', ', BotProfile::ENGINES));

            return self::FAILURE;
        }
```

`use Tests\Feature\Playtest\RunReport;` zu den Imports. Zeile 110 (Batch-Label) ersetzen:

```php
            $labels = collect($batch)->map(fn ($c) => RunReport::filePrefix($c[0], $engine)."/{$c[1]}={$c[2]}")->implode(', ');
```

Im Env-Array (Zeilen 126–139) nach `'PLAYTEST_OPENING' => $opening,` einfügen: `'PLAYTEST_ENGINE' => $engine,`.
Zeile 176: `$report = $this->latestReportFor($profile, $opening, $seed, $engine);`
Zeile 183: `$rows[] = $this->summarize(RunReport::filePrefix($profile, $engine), $opening, $seed, $report);`
`latestReportFor()` (Zeilen 205–217) ersetzen:

```php
    private function latestReportFor(string $profile, string $opening, string $seed, string $engine = 'legacy'): ?array
    {
        $pattern = storage_path('logs/playtest/'.RunReport::filePrefix($profile, $engine)."-{$opening}-{$seed}-*.json");
        $matches = glob($pattern) ?: [];
        if ($matches === []) {
            return null;
        }

        sort($matches);
        $latest = end($matches);

        return json_decode(file_get_contents($latest), true);
    }
```

- [ ] **Step 6: Tests grün**

Run: `bin/phpunit tests/Feature/Console/PlaytestCommandEngineTest.php tests/Feature/Console/PlaytestCommandOpeningsTest.php tests/Feature/Console/PlaytestCommandGuardTest.php tests/Feature/Console/PlaytestCommandTimeoutTest.php tests/Feature/Console/PlaytestCommandFailureOutputTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/Playtest/RunReport.php tests/Feature/Playtest/PlaytestBotTest.php app/Console/Commands/Playtest.php tests/Feature/Console/PlaytestCommandEngineTest.php
git commit -m "feat(playtest): game:playtest --engine, Report-Feld engine und Dateipräfix (Utility S1)"
```

---

## Task 7 (S1): Entscheidungslog und `utility_summary`

**Files:**
- Create: `tests/Feature/Playtest/Utility/DecisionLog.php`
- Modify: `tests/Feature/Playtest/Utility/Collector.php` (`do()`)
- Modify: `tests/Feature/Playtest/BotSession.php` (Feld `decisions`)
- Modify: `tests/Feature/Playtest/RunReport.php` (`build()`, neue Methode `utilitySummary()`)
- Test: `tests/Unit/Playtest/DecisionLogTest.php`, `tests/Feature/Playtest/RunReportUtilitySummaryTest.php` (neu)

**Interfaces:**
- Produces: `DecisionLog::entry(int $sol, Decision $d, bool $ok): array{sol, source: 'legacy'|'utility'|'mandatory', name, key, band, score: ?int, net: ?float, terms, alternatives, candidates_n, ok}`; `BotSession::$decisions` (`list<array>`); Report-Felder `decisions`, `utility_summary` (`null` ohne Entscheidungen; sonst `decisions`, `legacy_share` (K18, %), `mandatory_share`, `utility_share`, `median_decisions_per_sol` (K20)).

- [ ] **Step 1: Failing tests schreiben**

`tests/Unit/Playtest/DecisionLogTest.php`:

```php
<?php

namespace Tests\Unit\Playtest;

use PHPUnit\Framework\TestCase;
use Tests\Feature\Playtest\Utility\Candidate;
use Tests\Feature\Playtest\Utility\DecisionLog;
use Tests\Feature\Playtest\Utility\UtilitySelector;

class DecisionLogTest extends TestCase
{
    private static function c(string $name, ?float $net, bool $mandatory = false): Candidate
    {
        return new Candidate(name: $name, key: "k:{$name}", band: 1, net: $net, apCost: 2, do: fn () => ['ok' => true],
            terms: $net === null ? [] : ['gain' => $net + 1.23456, 'cost_rg' => -1.23456], mandatory: $mandatory);
    }

    public function test_utility_entry_carries_score_net_and_rounded_terms(): void
    {
        $d = UtilitySelector::select([self::c('a', 4.0), self::c('b', 2.0)], []);

        $entry = DecisionLog::entry(3, $d, true);

        $this->assertSame(['sol' => 3, 'source' => 'utility', 'name' => 'a', 'key' => 'k:a', 'band' => 1, 'score' => 2000, 'net' => 4.0],
            array_intersect_key($entry, array_flip(['sol', 'source', 'name', 'key', 'band', 'score', 'net'])));
        $this->assertSame(['gain' => 5.235, 'cost_rg' => -1.235], $entry['terms']);
        $this->assertSame(['b'], array_column($entry['alternatives'], 'name'));
        $this->assertSame(2, $entry['candidates_n']);
        $this->assertTrue($entry['ok']);
    }

    public function test_legacy_and_mandatory_sources(): void
    {
        $this->assertSame('legacy', DecisionLog::entry(0, UtilitySelector::select([self::c('a', null)], []), false)['source']);
        $this->assertNull(DecisionLog::entry(0, UtilitySelector::select([self::c('a', null)], []), false)['score']);
        $this->assertSame('mandatory', DecisionLog::entry(0, UtilitySelector::select([self::c('feed', null, true)], []), true)['source']);
    }
}
```

`tests/Feature/Playtest/RunReportUtilitySummaryTest.php`:

```php
<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Spec 2.10/2.11: the report carries the decision log and the K18/K20 summary. */
class RunReportUtilitySummaryTest extends TestCase
{
    use RefreshDatabase;

    private static function d(int $sol, string $source, bool $ok = true): array
    {
        return ['sol' => $sol, 'source' => $source, 'name' => 'x', 'key' => 'x', 'band' => 1, 'score' => null, 'net' => null,
            'terms' => [], 'alternatives' => [], 'candidates_n' => 1, 'ok' => $ok];
    }

    public function test_legacy_run_has_no_summary(): void
    {
        $bot = BotSession::boot($this, 7);

        $data = (new RunReport(7))->build($bot);

        $this->assertSame([], $data['decisions']);
        $this->assertNull($data['utility_summary']);
    }

    public function test_summary_counts_sources_and_median_decisions_per_sol(): void
    {
        $bot = BotSession::boot($this, 7);
        $bot->sol = 3;
        $bot->decisions = [self::d(0, 'legacy'), self::d(0, 'utility'), self::d(1, 'mandatory'), self::d(1, 'legacy', false)];

        $summary = (new RunReport(7, engine: 'utility'))->build($bot)['utility_summary'];

        $this->assertSame(4, $summary['decisions']);
        $this->assertSame(50.0, $summary['legacy_share']);
        $this->assertSame(25.0, $summary['mandatory_share']);
        $this->assertSame(25.0, $summary['utility_share']);
        // ok decisions per Sol 0..2: [2, 1, 0] -> median 1
        $this->assertSame(1.0, $summary['median_decisions_per_sol']);
    }
}
```

- [ ] **Step 2: Tests rot bestätigen**

Run: `bin/phpunit tests/Unit/Playtest/DecisionLogTest.php tests/Feature/Playtest/RunReportUtilitySummaryTest.php`
Expected: ERROR — `Class "...DecisionLog" not found`, `Undefined property ...$decisions`.

- [ ] **Step 3: `DecisionLog` anlegen**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

/** Spec 2.10: one compact, explainable log entry per executed decision. */
final class DecisionLog
{
    public static function entry(int $sol, Decision $d, bool $ok): array
    {
        $c = $d->chosen;

        return [
            'sol' => $sol,
            'source' => $c->mandatory ? 'mandatory' : ($c->isScored() ? 'utility' : 'legacy'),
            'name' => $c->name,
            'key' => $c->key,
            'band' => $c->band,
            'score' => $c->isScored() ? $c->milliScore() : null,
            'net' => $c->net === null ? null : round($c->net, 3),
            'terms' => array_map(fn (float $v): float => round($v, 3), $c->terms),
            'alternatives' => $d->alternatives,
            'candidates_n' => $d->candidatesN,
            'ok' => $ok,
        ];
    }
}
```

- [ ] **Step 4: `BotSession` und `Collector` erweitern**

In `BotSession.php` nach dem Feld `blockedThisSol` einfügen:

```php

    /** @var list<array> decision log of the utility engines (DecisionLog::entry()), empty for legacy */
    public array $decisions = [];
```

In `Collector::do()` nach `$res = ($d->chosen->do)($b);` einfügen:

```php
        $b->decisions[] = DecisionLog::entry($b->sol, $d, (bool) $res['ok']);
```

- [ ] **Step 5: `RunReport` erweitern**

In `build()` nach `'log' => $bot->log,` (Zeile 543) einfügen:

```php
            'decisions' => $bot->decisions,
            'utility_summary' => self::utilitySummary($bot),
```

Neue Methode nach `median()` (Zeile 447):

```php
    /**
     * Spec 2.11: K18 (share of legacy decisions, %) and K20 (median successful
     * decisions per Sol over Sols 0..sol-1). null for a legacy run (no decisions).
     */
    private static function utilitySummary(BotSession $bot): ?array
    {
        if ($bot->decisions === []) {
            return null;
        }

        $n = count($bot->decisions);
        $bySource = array_count_values(array_column($bot->decisions, 'source'));
        $perSol = array_fill(0, max(1, $bot->sol), 0);
        foreach ($bot->decisions as $d) {
            if ($d['ok']) {
                $perSol[$d['sol']] = ($perSol[$d['sol']] ?? 0) + 1;
            }
        }

        return [
            'decisions' => $n,
            'legacy_share' => round(($bySource['legacy'] ?? 0) / $n * 100, 1),
            'mandatory_share' => round(($bySource['mandatory'] ?? 0) / $n * 100, 1),
            'utility_share' => round(($bySource['utility'] ?? 0) / $n * 100, 1),
            'median_decisions_per_sol' => self::median(array_values($perSol)),
        ];
    }
```

- [ ] **Step 6: Tests grün**

Run: `bin/phpunit tests/Unit/Playtest/DecisionLogTest.php tests/Feature/Playtest/RunReportUtilitySummaryTest.php tests/Feature/Playtest/Utility/CollectorAdapterTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/Playtest/Utility/DecisionLog.php tests/Feature/Playtest/Utility/Collector.php tests/Feature/Playtest/BotSession.php tests/Feature/Playtest/RunReport.php tests/Unit/Playtest/DecisionLogTest.php tests/Feature/Playtest/RunReportUtilitySummaryTest.php
git commit -m "feat(playtest): Entscheidungslog und utility_summary (K18, K20) im Report (Utility S1)"
```

---

## Task 8 (S1): Kennzahlen K18/K19/K20/K23, `game:playtest-compare --engine`

**Files:**
- Modify: `app/Support/OpeningComparison.php` (Docblock Zeilen 35–81, `metrics()` 182–291, KPI-Liste 349, `checks()` 411–527)
- Modify: `app/Console/Commands/PlaytestCompare.php:23-44`, `:56`, `:74-78`, `:96`
- Test: `tests/Unit/OpeningComparisonUtilityKpiTest.php` (neu), `tests/Feature/Console/PlaytestCompareTest.php` (erweitern)

**Interfaces:**
- Consumes: Report-Felder `utility_summary`, `sols[].buildings`, `sols[].cc_level`.
- Produces: `metrics()` liefert zusätzlich `k18` (?float), `k19_lv2` (?int), `k19_lv3` (?int), `k19_lv1_sols` (?int), `k20` (?float), `k23` (?int); Checks `K18`, `K19`, `K20`, `K23` mit Status `info`.

- [ ] **Step 1: Failing tests schreiben**

`tests/Unit/OpeningComparisonUtilityKpiTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Support\OpeningComparison;
use PHPUnit\Framework\TestCase;

/** Spec 2.11 + P0: K18-K20 and K23 from synthetic RunReport::build() arrays. */
class OpeningComparisonUtilityKpiTest extends TestCase
{
    /**
     * @param  array<int, int>  $pathLevel  sol => level of the opening's path building (absent = not placed)
     * @param  array<int, int>  $cc  sol => cc_level (default 2)
     */
    private function report(string $opening, int $seed, array $pathLevel, array $cc = [], ?array $summary = null): array
    {
        $id = ['labor' => 31, 'hangar' => 44, 'cantina' => 52][$opening];
        $sols = [];
        for ($s = 1; $s <= 15; $s++) {
            $sols[] = [
                'sol' => $s, 'trust' => 2, 'cc_level' => $cc[$s] ?? 2, 'ap_unspent' => 2, 'ap' => ['inflow' => 16],
                'regolith_sources' => ['harvester' => 16, 'mission' => 0, 'trade' => 0, 'find' => 0, 'find_flight' => 0, 'event' => 0],
                'buildings' => isset($pathLevel[$s]) ? ["{$id}:1" => ['level' => $pathLevel[$s], 'ap_spend' => 0]] : [],
                'researches' => [],
            ];
        }
        $report = ['seed' => $seed, 'profile' => 'default', 'opening' => $opening, 'phase2_start_sol' => 16,
            'outcome' => ['status' => 'active', 'sols' => 16], 'truncated_at_sol' => 16, 'log' => [], 'sols' => $sols];
        if ($summary !== null) {
            $report['utility_summary'] = $summary;
        }

        return $report;
    }

    public function test_k19_reads_the_sols_of_the_own_path_building_levels(): void
    {
        $levels = [];
        foreach (range(4, 15) as $s) {
            $levels[$s] = $s >= 14 ? 3 : ($s >= 9 ? 2 : 1);
        }

        $m = OpeningComparison::metrics($this->report('hangar', 1, $levels));

        $this->assertSame([9, 14, 5], [$m['k19_lv2'], $m['k19_lv3'], $m['k19_lv1_sols']]);
    }

    public function test_k19_is_null_when_a_level_is_never_reached(): void
    {
        $m = OpeningComparison::metrics($this->report('cantina', 1, array_fill(4, 12, 1)));

        $this->assertSame([null, null, null], [$m['k19_lv2'], $m['k19_lv3'], $m['k19_lv1_sols']]);
    }

    public function test_k23_is_the_first_sol_with_cc_level_4(): void
    {
        $this->assertSame(12, OpeningComparison::metrics($this->report('labor', 1, [], [12 => 4, 13 => 4]))['k23']);
        $this->assertNull(OpeningComparison::metrics($this->report('labor', 1, []))['k23']);
    }

    public function test_k18_and_k20_come_from_the_utility_summary(): void
    {
        $m = OpeningComparison::metrics($this->report('labor', 1, [], [], ['legacy_share' => 87.5, 'median_decisions_per_sol' => 6.0]));

        $this->assertSame([87.5, 6.0], [$m['k18'], $m['k20']]);
    }

    public function test_legacy_report_without_utility_fields_yields_null_kpis(): void
    {
        $result = OpeningComparison::fromReports([$this->report('labor', 1, []), $this->report('hangar', 1, [])], ['labor', 'hangar'])->compare();

        $this->assertNull($result['metrics']['labor'][1]['k18']);
        $this->assertSame(1, $result['kpis']['k18']['labor']['missing']);
        $this->assertSame('info', $result['checks']['K18']['status']);
        $this->assertSame('info', $result['checks']['K19']['status']);
        $this->assertSame('info', $result['checks']['K23']['status']);
    }
}
```

An `tests/Feature/Console/PlaytestCompareTest.php` (vor der letzten `}`) anhängen:

```php
    public function test_engine_option_reads_only_that_engines_reports(): void
    {
        foreach (['labor' => 16, 'hangar' => 17] as $opening => $p2) {
            $this->writeReport('default', $opening, 1, '20261012_120000', 40);
            $this->writeReport('default.utility', $opening, 1, '20261012_120000', $p2);
        }

        Artisan::call('game:playtest-compare', ['--dir' => $this->dir, '--openings' => 'labor,hangar', '--engine' => 'utility']);

        $this->assertMatchesRegularExpression('/K3\s*\|\s*ok\s*\|\s*labor median 16; hangar median 17/', Artisan::output());
    }

    public function test_invalid_engine_aborts(): void
    {
        $this->artisan('game:playtest-compare', ['--dir' => $this->dir, '--engine' => 'magic'])
            ->expectsOutputToContain('Unknown engine: magic')
            ->assertExitCode(1);
    }

    public function test_prints_k19_and_k23_rows(): void
    {
        $this->writeBatch();

        Artisan::call('game:playtest-compare', ['--dir' => $this->dir]);
        $out = Artisan::output();

        $this->assertStringContainsString('K19 Pfad-Lv2 Sol', $out);
        $this->assertStringContainsString('K23 CC-Lv4 Sol', $out);
        $this->assertMatchesRegularExpression('/K19\s*\|\s*info\s*\|/', $out);
    }
```

- [ ] **Step 2: Tests rot bestätigen**

Run: `bin/phpunit tests/Unit/OpeningComparisonUtilityKpiTest.php tests/Feature/Console/PlaytestCompareTest.php`
Expected: FAIL — `Undefined array key "k19_lv2"`, `The "--engine" option does not exist.`

- [ ] **Step 3: `OpeningComparison::metrics()` erweitern**

Im Docblock nach dem K17-Absatz (nach Zeile 77) einfügen:

```php
 *  - K18 (informational, utility engines): utility_summary.legacy_share (% of
 *    decisions taken by a legacy source); null for legacy reports.
 *  - K19 (informational): first snapshot Sol with the opening's path building at
 *    level >= 2 (k19_lv2) / >= 3 (k19_lv3); k19_lv1_sols = k19_lv2 - Lv1 Sol.
 *    Target (T30 §14): Lv2 <= Sol 9 (warn > 12), Lv3 <= Sol 30. null if never.
 *  - K20 (informational): utility_summary.median_decisions_per_sol; null for legacy.
 *  - K23 (informational): first snapshot Sol with cc_level >= 4; target <= 25, warn > 30.
```

In `metrics()` vor `$p2 = $report['phase2_start_sol'] ?? null;` (Zeile 266) einfügen:

```php
        $pathId = $path !== null ? self::PATH_BUILDINGS[$path] : null;
        $k19lv2 = $pathId !== null ? self::levelSol($pathId, $sols, 2) : null;
        $summary = $report['utility_summary'] ?? null;
```

Im Rückgabe-Array nach `'ship_repair_25' => …,` (Zeile 285) einfügen:

```php
            'k18' => isset($summary['legacy_share']) ? (float) $summary['legacy_share'] : null,
            'k19_lv2' => $k19lv2,
            'k19_lv3' => $pathId !== null ? self::levelSol($pathId, $sols, 3) : null,
            'k19_lv1_sols' => $k19lv2 !== null && $lv1Sol !== null ? $k19lv2 - $lv1Sol : null,
            'k20' => isset($summary['median_decisions_per_sol']) ? (float) $summary['median_decisions_per_sol'] : null,
            'k23' => self::ccLevelSol($sols, 4),
```

Die `@return`-Annotation von `metrics()` um `k18: ?float, k19_lv2: ?int, k19_lv3: ?int, k19_lv1_sols: ?int, k20: ?float, k23: ?int` ergänzen. Neue Helfer nach `solsWith()` (Zeile 297):

```php
    /** First snapshot Sol in which $buildingId has at least $level, or null. */
    private static function levelSol(int $buildingId, array $sols, int $level): ?int
    {
        foreach ($sols as $snapshot) {
            foreach ($snapshot['buildings'] ?? [] as $key => $building) {
                if ((int) explode(':', (string) $key)[0] === $buildingId && (int) $building['level'] >= $level) {
                    return (int) $snapshot['sol'];
                }
            }
        }

        return null;
    }

    /** First snapshot Sol with cc_level >= $level, or null. */
    private static function ccLevelSol(array $sols, int $level): ?int
    {
        foreach ($sols as $snapshot) {
            if ((int) ($snapshot['cc_level'] ?? 0) >= $level) {
                return (int) $snapshot['sol'];
            }
        }

        return null;
    }
```

- [ ] **Step 4: KPI-Liste und Checks erweitern**

Zeile 349: an die Liste `'k18', 'k19_lv2', 'k19_lv3', 'k19_lv1_sols', 'k20', 'k23'` anhängen.
Zeile 415: an die `array_fill_keys`-Liste `'K18', 'K19', 'K20', 'K23'` anhängen.
Im Rückgabe-Array von `checks()` nach `'REP' => …,` (Zeile 526) einfügen:

```php
            'K18' => $info('Legacy share of decisions %, median', $kpis['k18'], 'informational; <= 5 after S8'),
            'K19' => [
                'status' => 'info',
                'detail' => 'Own path building Lv2 / Lv3 Sol, median: '.implode('; ', array_map(
                    fn ($o) => "{$o} ".self::fmt($kpis['k19_lv2'][$o]['median']).' / '.self::fmt($kpis['k19_lv3'][$o]['median']),
                    array_keys($kpis['k19_lv2']),
                )).' (target Lv2 <= 9, warn > 12; Lv3 <= 30)',
            ],
            'K20' => $info('Decisions per Sol, median', $kpis['k20'], 'informational'),
            'K23' => $info('Sol of CC Lv4, median', $kpis['k23'], 'target <= 25, warn > 30'),
```

- [ ] **Step 5: `PlaytestCompare` erweitern**

Signatur (Zeilen 23–28) nach `--profile` ergänzen: `{--engine=legacy : Engine of the reports (see BotProfile::ENGINES)}`.
`KPI_LABELS` (Zeilen 33–44) ergänzen:

```php
        'k18' => 'K18 Legacy-Anteil %',
        'k19_lv2' => 'K19 Pfad-Lv2 Sol',
        'k19_lv3' => 'K19 Pfad-Lv3 Sol',
        'k19_lv1_sols' => 'K19 Sole auf Lv1',
        'k20' => 'K20 Entscheidungen/Sol',
        'k23' => 'K23 CC-Lv4 Sol',
```

Nach der Openings-Prüfung (Zeile 54) einfügen:

```php
        $engine = (string) $this->option('engine');
        if (! in_array($engine, BotProfile::ENGINES, true)) {
            $this->error('Unknown engine: '.$engine.'. Allowed: '.implode(', ', BotProfile::ENGINES));

            return self::FAILURE;
        }
```

Nach Zeile 68 (`$profile = …`) einfügen: `$prefix = RunReport::filePrefix($profile, $engine);` und `use Tests\Feature\Playtest\RunReport;` importieren. In Zeile 74 `"{$dir}/{$profile}-{$opening}-*-*.json"` → `"{$dir}/{$prefix}-{$opening}-*-*.json"`, in Zeile 78 `preg_quote("{$profile}-{$opening}-", '/')` → `preg_quote("{$prefix}-{$opening}-", '/')`. Zeile 96: `"Profile: {$profile}"` → `"Profile: {$profile} — engine: {$engine}"`.

- [ ] **Step 6: Tests grün**

Run: `bin/phpunit tests/Unit/OpeningComparisonUtilityKpiTest.php tests/Unit/OpeningComparisonTest.php tests/Feature/Console/PlaytestCompareTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Support/OpeningComparison.php app/Console/Commands/PlaytestCompare.php tests/Unit/OpeningComparisonUtilityKpiTest.php tests/Feature/Console/PlaytestCompareTest.php
git commit -m "feat(playtest): Kennzahlen K18/K19/K20/K23 und playtest-compare --engine (Utility S1)"
```

---

## Task 9: Zwischenstand A (S0 + S1) — verifizieren, PR

**Files:**
- Modify: `CHANGELOG.md` (Tagesblock `## YYYY-MM-DD` oben; einen vorhandenen Block desselben Tages ergänzen, keinen zweiten anlegen)

- [ ] **Step 1: Schnelle Suite**

Run: `php artisan test --parallel --testsuite=laravel-feature,laravel-unit`
Expected: PASS (0 Fehler).

- [ ] **Step 2: Komplette Playtest-Suite (eigener Verifikationsschritt)**

Run (im Hintergrund, Monitor-Poll statt Warten im Vordergrund):

```bash
nohup bin/phpunit --testsuite playtest > storage/logs/playtest-suite-A.log 2>&1 &
```

Poll: `tail -n 5 storage/logs/playtest-suite-A.log` alle paar Minuten, bis `OK` oder `FAILURES!` erscheint (ca. 24 min).
Expected: `OK`. Bei Fehlern: superpowers:systematic-debugging, nicht weiter.

- [ ] **Step 3: CHANGELOG-Eintrag**

```markdown
- Feat: PlaytestBot Utility-Modell S0+S1 — Selektor mit Legacy-Adapter (Golden-Test: gleiche Aktionsfolge wie Legacy), `game:playtest --engine=legacy|utility`, Entscheidungslog im Report, Kennzahlen K18/K19/K20/K23 in `game:playtest-compare` (Spec `docs/superpowers/specs/2026-10-12-playtestbot-utility-modell.md`). Legacy-Verhalten unverändert.
```

- [ ] **Step 4: Commit, Push, PR**

```bash
git add CHANGELOG.md
git commit -m "docs: CHANGELOG PlaytestBot Utility S0+S1"
git push origin feat/bot-utility-modell
gh pr create --base master --head feat/bot-utility-modell --title "PlaytestBot Utility-Modell S0+S1: Adapter, Engine-Schalter, Golden-Test" --body "Spec: docs/superpowers/specs/2026-10-12-playtestbot-utility-modell.md, Plan: docs/superpowers/plans/2026-10-12-playtestbot-utility-modell.md (Tasks 1-9). Legacy-Verhalten unverändert (Golden-Test). Playtest-Suite lokal grün."
```

PR-Body um die Attribution der ausführenden Session ergänzen. Nicht mergen — der Owner merged (Pre-Merge-Checkliste in CLAUDE.md).

- [ ] **Step 5: Branch für S2**

```bash
git checkout -b feat/bot-utility-s2
```

---
## Task 10 (S2): Gewichte und reine Rechenbausteine

**Files:**
- Create: `tests/Feature/Playtest/Utility/weights.php`, `tests/Feature/Playtest/Utility/Valuation.php`
- Modify: `tests/Feature/Playtest/BotProfile.php` (neue Methode `weights()`)
- Test: `tests/Unit/Playtest/ValuationTest.php`, `tests/Unit/Playtest/BotProfileWeightsTest.php` (neu)

**Interfaces:**
- Produces: `BotProfile::weights(): array` (Schlüssel siehe `weights.php`; abgeleitet: `w_objective`, `price.credits`, `credit_reserve_factor`, `opening_bias` = 0 bei `auto`); `Valuation::discountSum(float $gamma, int $h): float`, `stream(float $perSol, int $latency, float $gamma, int $h): float`, `horizon(int $sol, int $tickLimit, int $max): int`, `scarcity(float $demand, float $stock, float $lo, float $hi): float`, `lambdaEma(float $prev, float $marginal, float $alpha): float`, `urgency(int $sol, int $fromSol, float $step): float`.

- [ ] **Step 1: Failing tests schreiben**

`tests/Unit/Playtest/ValuationTest.php`:

```php
<?php

namespace Tests\Unit\Playtest;

use PHPUnit\Framework\TestCase;
use Tests\Feature\Playtest\Utility\Valuation;

/** Spec 2.1/2.2/2.4: discount sum, streams, horizon, scarcity, lambda EMA, urgency. */
class ValuationTest extends TestCase
{
    public function test_discount_sum_matches_the_spec_example(): void
    {
        $this->assertEqualsWithDelta(16.558, Valuation::discountSum(0.95, 40), 0.01);
        $this->assertSame(0.0, Valuation::discountSum(0.95, 0));
    }

    public function test_stream_discounts_latency_and_shortens_the_horizon(): void
    {
        $this->assertEqualsWithDelta(44.12, Valuation::stream(3.0, 2, 0.95, 40), 0.01);
        $this->assertSame(0.0, Valuation::stream(3.0, 5, 0.95, 5));
    }

    public function test_horizon_is_capped_and_never_negative(): void
    {
        $this->assertSame(40, Valuation::horizon(10, 100, 40));
        $this->assertSame(10, Valuation::horizon(90, 100, 40));
        $this->assertSame(0, Valuation::horizon(105, 100, 40));
    }

    public function test_scarcity_is_demand_over_stock_clamped(): void
    {
        $this->assertSame(3.0, Valuation::scarcity(30, 10, 0.5, 4.0));
        $this->assertSame(4.0, Valuation::scarcity(100, 0, 0.5, 4.0));
        $this->assertSame(0.5, Valuation::scarcity(1, 100, 0.5, 4.0));
    }

    public function test_lambda_ema_and_urgency(): void
    {
        $this->assertEqualsWithDelta(0.95, Valuation::lambdaEma(0.5, 2.0, 0.3), 1e-9);
        $this->assertSame(1.0, Valuation::urgency(15, 15, 0.2));
        $this->assertEqualsWithDelta(1.6, Valuation::urgency(18, 15, 0.2), 1e-9);
    }
}
```

`tests/Unit/Playtest/BotProfileWeightsTest.php`:

```php
<?php

namespace Tests\Unit\Playtest;

use PHPUnit\Framework\TestCase;
use Tests\Feature\Playtest\BotProfile;

/** Spec 2.9: base weights plus what the profile dials and the opening derive. */
class BotProfileWeightsTest extends TestCase
{
    public function test_default_profile_uses_the_base_weights(): void
    {
        $w = BotProfile::named('default')->weights();

        $this->assertSame(0.95, $w['gamma']);
        $this->assertSame(40, $w['horizon_max']);
        $this->assertSame(0.04, $w['price']['credits']);
        $this->assertSame(0.0, $w['w_objective']);
        $this->assertEqualsWithDelta(1.1, $w['credit_reserve_factor'], 1e-9);
        $this->assertSame(0.0, $w['opening_bias'], 'auto opening has no bias');
    }

    public function test_a_named_opening_keeps_the_bias(): void
    {
        $this->assertSame(30.0, BotProfile::named('default')->withOpening('hangar')->weights()['opening_bias']);
    }

    public function test_thrifty_doubles_the_credit_price_and_raises_the_reserve(): void
    {
        $w = BotProfile::named('thrifty')->weights();

        $this->assertSame(0.08, $w['price']['credits']);
        $this->assertEqualsWithDelta(1.5, $w['credit_reserve_factor'], 1e-9);
    }

    public function test_focus_and_eager_pull_towards_objectives(): void
    {
        $this->assertSame(150.0, BotProfile::named('focus')->weights()['w_objective']);
        $eager = BotProfile::named('eager')->weights();
        $this->assertSame(150.0, $eager['w_objective']);
        $this->assertSame(0.04, $eager['price']['credits']);
    }
}
```

- [ ] **Step 2: Tests rot bestätigen**

Run: `bin/phpunit tests/Unit/Playtest/ValuationTest.php tests/Unit/Playtest/BotProfileWeightsTest.php`
Expected: ERROR — `Class "...Valuation" not found`, `Call to undefined method ...BotProfile::weights()`.

- [ ] **Step 3: `weights.php` anlegen**

```php
<?php

/**
 * Utility weights of the playtest bot (spec 2.9 + P0). Test harness, not game
 * values — therefore not in config/. Every value is an assumption to be
 * calibrated by measurement (plan Task 22). Profile-specific values are derived
 * from the BotProfile dials in BotProfile::weights(); `profiles` holds explicit
 * overrides (array_replace_recursive) and is empty in v1.
 */
return [
    'base' => [
        'gamma' => 0.95,
        'horizon_max' => 40,
        'lambda_ap_start' => 0.5,
        'lambda_ap_ema' => 0.3,
        'price' => ['credits' => 0.04, 'organics' => 1.0, 'compounds' => 3.0, 'trust' => 2.0],
        'scarcity_clamp' => [0.5, 4.0],
        'trust_danger_margin' => 10,
        'trust_danger_factor' => 4.0,
        'w_phase1' => 40.0,
        'phase1_urgency_from_sol' => 15,
        'phase1_urgency_step' => 0.2,
        // Owner 2026-10-12: the bot must want path building levels 2 and 3 (and CC 4/5).
        'w_tier' => 30.0,
        // Structural steps (CC level, path building placement) count cost-neutral in v1.
        'structural_offset' => 1.0,
        'objective_base' => 150.0,
        'opening_bias' => 30.0,
        'opening_bias_until_sol' => 15,
        'info_zone_tile' => 8.0,
        'research_floor' => 1.0,
        'build_ap_per_sol' => 10,
        'nav_ap_per_sol' => 4,
        'income_floor_rg' => 8,
        'goal_max_wait_sols' => 5,
        'min_net' => 0.01,
        'risk_aversion' => 0.0,
    ],
    'profiles' => [
        'default' => [],
        'thrifty' => [],
        'focus' => [],
        'eager' => [],
    ],
];
```

- [ ] **Step 4: `Valuation` anlegen**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

/** Pure building blocks of the utility model (spec 2.1-2.4). */
final class Valuation
{
    /** D(H) = Σ_{t=1..H} γ^t = γ(1 − γ^H)/(1 − γ); 0 for H <= 0. */
    public static function discountSum(float $gamma, int $h): float
    {
        if ($h <= 0) {
            return 0.0;
        }

        return $gamma * (1 - $gamma ** $h) / (1 - $gamma);
    }

    /** A yield of $perSol per Sol starting after $latency Sols: x · γ^L · D(H − L). */
    public static function stream(float $perSol, int $latency, float $gamma, int $h): float
    {
        return $perSol * $gamma ** $latency * self::discountSum($gamma, $h - $latency);
    }

    public static function horizon(int $sol, int $tickLimit, int $max): int
    {
        return max(0, min($tickLimit - $sol, $max));
    }

    public static function scarcity(float $demand, float $stock, float $lo, float $hi): float
    {
        return max($lo, min($hi, $demand / max(1.0, $stock)));
    }

    public static function lambdaEma(float $prev, float $marginal, float $alpha): float
    {
        return (1 - $alpha) * $prev + $alpha * $marginal;
    }

    /** 1 up to $fromSol, then +$step per Sol (spec 2.2 Phasenziel). */
    public static function urgency(int $sol, int $fromSol, float $step): float
    {
        return $sol <= $fromSol ? 1.0 : 1.0 + $step * ($sol - $fromSol);
    }
}
```

- [ ] **Step 5: `BotProfile::weights()` ergänzen**

In `tests/Feature/Playtest/BotProfile.php` nach `withEngine()` einfügen:

```php
    /**
     * Utility weights (spec 2.9): base set from Utility/weights.php, explicit
     * profile overrides, then what the dials and the opening derive.
     *
     * @return array<string, mixed>
     */
    public function weights(): array
    {
        $file = require __DIR__.'/Utility/weights.php';
        $w = array_replace_recursive($file['base'], $file['profiles'][$this->name] ?? []);

        $w['w_objective'] = $this->objectiveFocus * (float) $w['objective_base'];
        $w['price']['credits'] = (float) $w['price']['credits'] * (1.0 + $this->savingsAggressiveness);
        $w['credit_reserve_factor'] = 1.1 + 0.4 * $this->savingsAggressiveness;
        if ($this->opening === 'auto') {
            $w['opening_bias'] = 0.0;
        }

        return $w;
    }
```

- [ ] **Step 6: Tests grün**

Run: `bin/phpunit tests/Unit/Playtest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/Playtest/Utility/weights.php tests/Feature/Playtest/Utility/Valuation.php tests/Feature/Playtest/BotProfile.php tests/Unit/Playtest/ValuationTest.php tests/Unit/Playtest/BotProfileWeightsTest.php
git commit -m "test(playtest): Utility-Gewichte und Rechenbausteine (Utility S2)"
```

---

## Task 11 (S2): `BotContext::capture()` und gemeinsame Fixtures

**Files:**
- Modify: `tests/Feature/Playtest/Utility/BotContext.php` (vollständig ersetzen)
- Modify: `tests/Feature/Playtest/BotSession.php` (Felder `lambdaAp`, `lambdaBySol`)
- Modify: `tests/Feature/Playtest/BotStrategy.php:730` (`runPhase` → `public`), `:2304` (`hasActiveObjective` → `public`)
- Create: `tests/Feature/Playtest/Utility/UtilityFixtures.php`
- Test: `tests/Feature/Playtest/Utility/BotContextTest.php` (neu)

**Interfaces:**
- Consumes: `BotProfile::weights()`, `Valuation` (Task 10).
- Produces: `BotContext::capture(BotSession $b, BotProfile $profile): self` mit Feldern `sol, weights, phase, tickLimit, opening, openingBuildingId (?int), regolith, credits, organics, trust, apAvailable, apInflow, incomeRg (float), lambdaAp (float), advisors, nonCcLevel2, objectives (array<task_key, array{current:int, target:int}>), prices (array<string,float>)`; Methoden `horizon(): int`, `stream(float $perSol, int $latency): float`, `price(string $name): float`, `phase1Urgency(): float`. `BotSession::$lambdaAp` (`?float`, null = Startwert), `BotSession::$lambdaBySol` (`array<int,float>`). Trait `UtilityFixtures` mit `placeAt`, `setCc`, `setResource`, `setCredits`, `setSupply`, `setPhase`, `giveAp`, `makeFind`, `setKnowledge`, `logSolNext`.

- [ ] **Step 1: Fixture-Trait anlegen (Test-Infrastruktur, kein Verhalten)**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use App\Services\AdvisorService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotSession;

/** Shared state fixtures for the utility tests (pattern of BotStrategyCcReserveTest / BotStrategyFindFlightTest). */
trait UtilityFixtures
{
    private function placeAt(BotSession $bot, int $buildingId, int $level, int $apSpend = 0, int $instanceId = 1): void
    {
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)->delete();
        $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('is_colony_zone', 1)
            ->where(fn ($q) => $q->where('q', '!=', 0)->orWhere('r', '!=', 0))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('colony_buildings as cb')
                ->where('cb.colony_id', $bot->colonyId)
                ->whereColumn('cb.tile_x', 'colony_tiles.q')
                ->whereColumn('cb.tile_y', 'colony_tiles.r'))
            ->orderBy('ring')->orderBy('q')->orderBy('r')
            ->first();
        $this->assertNotNull($tile, 'fixture needs a free colony-zone tile');

        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId, 'building_id' => $buildingId, 'instance_id' => $instanceId,
            'level' => $level, 'status_points' => 20, 'ap_spend' => $apSpend, 'tile_x' => $tile->q, 'tile_y' => $tile->r,
        ]);
    }

    private function setCc(BotSession $bot, int $level, int $apSpend = 0): void
    {
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::CommandCenter->value)->update(['level' => $level, 'ap_spend' => $apSpend]);
    }

    private function setResource(BotSession $bot, int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(['colony_id' => $bot->colonyId, 'resource_id' => $resourceId], ['amount' => $amount]);
    }

    private function setCredits(BotSession $bot, int $credits): void
    {
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['credits' => $credits]);
    }

    private function setSupply(BotSession $bot, int $cap): void
    {
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['supply' => $cap]);
    }

    private function setPhase(BotSession $bot, int $phase): void
    {
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => $phase]);
    }

    private function giveAp(BotSession $bot, int $ap): void
    {
        $service = app(AdvisorService::class);
        DB::table('locked_actionpoints')->where('scope_type', 'colony')->where('scope_id', $bot->colonyId)->delete();
        $service->lockActionPoints($bot->colonyId, $service->getTotalActionPoints($bot->colonyId) - $ap);
    }

    private function makeFind(BotSession $bot, string $type, int $ring = 2): object
    {
        $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->whereNull('event_type')
            ->where('is_colony_zone', 0)->where('ring', $ring)->orderBy('q')->orderBy('r')->first();
        DB::table('colony_tiles')->where('id', $tile->id)->update(['event_type' => $type, 'is_explored' => 1,
            'is_deep_scanned' => 1, 'salvage_ap_spent' => 0, 'salvage_tick' => null]);

        return DB::table('colony_tiles')->where('id', $tile->id)->first();
    }

    private function setKnowledge(BotSession $bot, int $researchId, int $level, int $apSpend = 0): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'research_id' => $researchId],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => $apSpend],
        );
    }

    /** A fake ok sol_next log entry with the given Regolith gain (BotContext income). */
    private function logSolNext(BotSession $bot, int $gain): void
    {
        $bot->log[] = ['sol' => $bot->sol, 'rule' => 'sol_next', 'url' => '/sol/next', 'status' => 200, 'ok' => true, 'error' => null,
            'ap_before' => 0, 'ap_after' => 0, 'regolith_before' => 100, 'regolith_after' => 100 + $gain,
            'organics_before' => 0, 'organics_after' => 0];
    }
}
```

- [ ] **Step 2: Failing test schreiben**

`tests/Feature/Playtest/Utility/BotContextTest.php`:

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use App\Services\ResourcesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\TestCase;

/** Spec 3.1: one consistent snapshot of state, prices and weights per decision. */
class BotContextTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    public function test_capture_reads_colony_state_and_profile_weights(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->setResource($bot, 3, 77);

        $ctx = BotContext::capture($bot, BotProfile::named('default')->withOpening('hangar'));

        $this->assertSame([0, 1, 77, 40, 'hangar', 44], [$ctx->sol, $ctx->phase, $ctx->regolith, $ctx->horizon(), $ctx->opening, $ctx->openingBuildingId]);
        $this->assertSame(0.5, $ctx->lambdaAp, 'start value until the session tracks lambda');
        $this->assertSame(8.0, $ctx->incomeRg, 'income floor without any sol_next yet');
        $this->assertSame(0.95, $ctx->weights['gamma']);
        $this->assertSame(1.0, $ctx->phase1Urgency());
    }

    public function test_auto_opening_has_no_opening_building(): void
    {
        $bot = BotSession::boot($this, 1);

        $this->assertNull(BotContext::capture($bot, BotProfile::named('default'))->openingBuildingId);
    }

    public function test_income_is_the_mean_regolith_gain_of_the_last_three_sols(): void
    {
        $bot = BotSession::boot($this, 1);
        foreach ([100, 20, 30, 40] as $gain) {
            $this->logSolNext($bot, $gain);
        }

        $this->assertSame(30.0, BotContext::capture($bot, BotProfile::named('default'))->incomeRg);
    }

    public function test_organics_price_follows_the_scarcity_formula(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->setResource($bot, 5, 0);
        $profile = BotProfile::named('default');
        $w = $profile->weights();
        $foodNeed = app(ResourcesService::class)->foodNeed($bot->colonyId);

        $empty = BotContext::capture($bot, $profile)->price('organics');
        $this->setResource($bot, 5, 10000);
        $full = BotContext::capture($bot, $profile)->price('organics');

        $this->assertEqualsWithDelta($w['price']['organics'] * Valuation::scarcity($foodNeed * 3, 0, 0.5, 4.0), $empty, 1e-9);
        $this->assertGreaterThanOrEqual($full, $empty);
    }

    public function test_lambda_comes_from_the_session_once_tracked(): void
    {
        $bot = BotSession::boot($this, 1);
        $bot->lambdaAp = 1.7;

        $this->assertSame(1.7, BotContext::capture($bot, BotProfile::named('default'))->lambdaAp);
    }
}
```

- [ ] **Step 3: Test rot bestätigen**

Run: `bin/phpunit tests/Feature/Playtest/Utility/BotContextTest.php`
Expected: ERROR — `Call to undefined method ...BotContext::capture()`.

- [ ] **Step 4: `BotSession`-Felder und Sichtbarkeit**

In `BotSession.php` nach `decisions` einfügen:

```php

    /** λ_AP (spec 2.4): EMA of the marginal AP density, null until the first Sol was closed. */
    public ?float $lambdaAp = null;

    /** @var array<int, float> λ_AP after each closed Sol (report K21) */
    public array $lambdaBySol = [];
```

`BotStrategy.php` Zeile 730: `private static function runPhase` → `public static function runPhase`; Zeile 2304: `private static function hasActiveObjective` → `public static function hasActiveObjective`.

- [ ] **Step 5: `BotContext` ersetzen**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use App\Services\AdvisorService;
use App\Services\ResourcesService;
use App\Services\TrustService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;

/** State, prices and weights shared by all sources for one decision (spec 3.1). */
final class BotContext
{
    /**
     * @param  array<string, mixed>  $weights
     * @param  array<string, array{current:int, target:int}>  $objectives  open Phase-2 objectives
     * @param  array<string, float>  $prices  Rg-equivalents per unit
     */
    public function __construct(
        public readonly int $sol,
        public readonly array $weights = [],
        public readonly int $phase = 1,
        public readonly int $tickLimit = 100,
        public readonly string $opening = 'auto',
        public readonly ?int $openingBuildingId = null,
        public readonly int $regolith = 0,
        public readonly int $credits = 0,
        public readonly int $organics = 0,
        public readonly int $trust = 0,
        public readonly int $apAvailable = 0,
        public readonly int $apInflow = 0,
        public readonly float $incomeRg = 0.0,
        public readonly float $lambdaAp = 0.5,
        public readonly int $advisors = 0,
        public readonly int $nonCcLevel2 = 0,
        public readonly array $objectives = [],
        public readonly array $prices = [],
    ) {}

    public static function capture(BotSession $b, BotProfile $profile): self
    {
        $w = $profile->weights();
        $colonyId = $b->colonyId;
        $advisorService = app(AdvisorService::class);
        $credits = BotStrategy::credits($b);
        $organics = BotStrategy::organics($b);
        $trust = app(TrustService::class)->getTrust($colonyId);
        [$lo, $hi] = $w['scarcity_clamp'];

        $objectives = DB::table('run_objectives')->where('run_id', $b->runId)->whereNull('completed_at')
            ->orderBy('task_key')->get(['task_key', 'current_value', 'target_value'])
            ->mapWithKeys(fn ($o) => [$o->task_key => ['current' => (int) $o->current_value, 'target' => (int) $o->target_value]])
            ->all();

        $upkeepTable = config('game.advisor.upkeep', []);
        $upkeep = DB::table('advisors')->where('colony_id', $colonyId)->orderBy('id')->pluck('rank')
            ->sum(fn ($rank) => (int) ($upkeepTable[(int) $rank] ?? 0));
        $creditDemand = $upkeep * 10 + (isset($objectives['task_credit_reserve'])
            ? (int) round((int) config('game.run.tasks.task_credit_reserve.threshold', 4000) * $w['credit_reserve_factor'])
            : 0);
        $trustDanger = $trust - (int) config('game.run.trust_fail_threshold', -20) <= (int) $w['trust_danger_margin'];

        $prices = [
            'credits' => (float) $w['price']['credits'] * Valuation::scarcity($creditDemand, $credits, $lo, $hi),
            'organics' => (float) $w['price']['organics'] * Valuation::scarcity(app(ResourcesService::class)->foodNeed($colonyId) * 3, $organics, $lo, $hi),
            'compounds' => (float) $w['price']['compounds'],
            'trust' => (float) $w['price']['trust'] * ($trustDanger ? (float) $w['trust_danger_factor'] : 1.0),
        ];

        return new self(
            sol: $b->sol,
            weights: $w,
            phase: BotStrategy::runPhase($b),
            tickLimit: (int) config('game.run.tick_limit', 100),
            opening: $profile->opening,
            openingBuildingId: $profile->opening === 'auto' ? null : BotStrategy::pathBuildingOrder($profile->opening)[0],
            regolith: BotStrategy::regolith($b),
            credits: $credits,
            organics: $organics,
            trust: $trust,
            apAvailable: $advisorService->getAvailableActionPoints($colonyId),
            apInflow: (int) $advisorService->getApBreakdown($colonyId)['total'],
            incomeRg: self::income($b, (float) $w['income_floor_rg']),
            lambdaAp: $b->lambdaAp ?? (float) $w['lambda_ap_start'],
            advisors: DB::table('advisors')->where('colony_id', $colonyId)->count(),
            nonCcLevel2: DB::table('colony_buildings')->where('colony_id', $colonyId)
                ->where('building_id', '!=', BuildingId::CommandCenter->value)->where('level', '>=', 2)->count(),
            objectives: $objectives,
            prices: $prices,
        );
    }

    /** Mean Regolith gain of the last three ok sol_next entries, at least $floor (spec 2.6). */
    private static function income(BotSession $b, float $floor): float
    {
        $gains = [];
        foreach (array_reverse($b->log) as $entry) {
            if ($entry['rule'] === 'sol_next' && $entry['ok']) {
                $gains[] = max(0, $entry['regolith_after'] - $entry['regolith_before']);
                if (count($gains) === 3) {
                    break;
                }
            }
        }

        return max($floor, $gains === [] ? 0.0 : array_sum($gains) / count($gains));
    }

    public function horizon(): int
    {
        return Valuation::horizon($this->sol, $this->tickLimit, (int) $this->weights['horizon_max']);
    }

    public function stream(float $perSol, int $latency): float
    {
        return Valuation::stream($perSol, $latency, (float) $this->weights['gamma'], $this->horizon());
    }

    public function price(string $name): float
    {
        return $this->prices[$name] ?? 0.0;
    }

    public function phase1Urgency(): float
    {
        return Valuation::urgency($this->sol, (int) $this->weights['phase1_urgency_from_sol'], (float) $this->weights['phase1_urgency_step']);
    }
}
```

- [ ] **Step 6: Tests grün (inkl. Golden, weil `BotStrategy` angefasst)**

Run: `bin/phpunit tests/Feature/Playtest/Utility/BotContextTest.php tests/Unit/Playtest tests/Feature/Playtest/Utility/AdapterGoldenTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/Playtest/Utility/BotContext.php tests/Feature/Playtest/Utility/UtilityFixtures.php tests/Feature/Playtest/Utility/BotContextTest.php tests/Feature/Playtest/BotSession.php tests/Feature/Playtest/BotStrategy.php
git commit -m "test(playtest): BotContext::capture mit Preisen, Einkommen, λ (Utility S2)"
```

---
## Task 12 (S2): Bewertungsterme (`Estimate`, `Valuers`)

**Files:**
- Create: `tests/Feature/Playtest/Utility/Estimate.php`, `tests/Feature/Playtest/Utility/Valuers.php`
- Modify: `tests/Feature/Playtest/BotStrategy.php` — Sichtbarkeit `private` → `public` bei `agrardomPlaced` (Zeile 881), `supplyGated` (1278), `levelupRegolith` (1323), `effectiveApForLevelup` (1332)
- Test: `tests/Feature/Playtest/Utility/ValuersTest.php` (neu)

**Interfaces:**
- Consumes: `BotContext` (Task 11).
- Produces:
  - `Estimate(array $terms, int $apCost, int $regolithCost)`; `net(): float`; `withTerm(string $name, float $value): self`.
  - `Valuers::PATH_IDS = [31, 44, 52]`; `upgrade(BotSession, BotContext, object $row): Estimate` (Terme `cost_rg`, `phase1`, `tier`, `advisor_slot`, `opening`, `trust`, `unlock`, `food`, `objective`); `cc(BotSession, BotContext, object $cc): Estimate` (`cost_rg`, `structural`, `phase1`, `tier`, `advisor_slot`, `path_slot`, `knowledge_cap`); `research(BotSession, BotContext, int $researchId, int $level, int $apSpend): Estimate` (`floor`, `effect`, `trust`); `pathPlacement(BotSession, BotContext, int $buildingId): Estimate`; `advisorSlotValue(BotContext): float`; `upgradeRows(BotSession): list<object>`; `buildingRow(BotSession, int $id, int $instance): ?object`; `ccRow(BotSession): ?object`.
  - Zeilenform (`upgradeRows`/`buildingRow`): `building_id, instance_id, level, ap_spend, tile_x, supply_cost, ap_for_levelup`; `ccRow` zusätzlich `max_level`.

- [ ] **Step 1: Failing test schreiben**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\TestCase;

/**
 * Spec 2.3 + P0: relative expectations only — no fixed score numbers, so weight
 * tuning never breaks these tests.
 */
class ValuersTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    private function ctx(BotSession $bot, string $opening = 'auto'): BotContext
    {
        return BotContext::capture($bot, BotProfile::named('default')->withOpening($opening));
    }

    public function test_hangar_level_2_is_worth_more_with_a_waiting_large_find(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->placeAt($bot, BuildingId::Hangar->value, 1);
        $without = Valuers::upgrade($bot, $this->ctx($bot), Valuers::buildingRow($bot, 44, 1))->net();

        $this->makeFind($bot, 'find_large');
        $with = Valuers::upgrade($bot, $this->ctx($bot), Valuers::buildingRow($bot, 44, 1));

        $this->assertGreaterThan($without, $with->net());
        $this->assertArrayHasKey('unlock', $with->terms);
    }

    public function test_geology_beats_construction_per_ap_once_the_lab_stands(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->placeAt($bot, BuildingId::Sciencelab->value, 2);
        $ctx = $this->ctx($bot);

        $geology = Valuers::research($bot, $ctx, 92, 0, 0);
        $construction = Valuers::research($bot, $ctx, 90, 0, 0);

        $this->assertGreaterThan($construction->net() / $construction->apCost, $geology->net() / $geology->apCost);
        $this->assertSame(0, $geology->regolithCost);
    }

    public function test_every_knowledge_is_worth_its_ap(): void
    {
        $bot = BotSession::boot($this, 1);
        $ctx = $this->ctx($bot);

        foreach (range(90, 96) as $id) {
            $this->assertGreaterThan(0.0, Valuers::research($bot, $ctx, $id, 0, 0)->net(), "knowledge {$id}");
        }
    }

    public function test_housing_level_up_in_phase_2_without_a_need_is_not_worth_its_regolith(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->setPhase($bot, 2);
        $this->placeAt($bot, BuildingId::Housing->value, 1);

        $this->assertLessThan(0.0, Valuers::upgrade($bot, $this->ctx($bot), Valuers::buildingRow($bot, 28, 1))->net());
    }

    public function test_own_path_building_level_2_beats_housing_level_2_in_a_hangar_opening(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->placeAt($bot, BuildingId::Hangar->value, 1);
        $this->placeAt($bot, BuildingId::Housing->value, 1);
        $ctx = $this->ctx($bot, 'hangar');

        $hangar = Valuers::upgrade($bot, $ctx, Valuers::buildingRow($bot, 44, 1));
        $housing = Valuers::upgrade($bot, $ctx, Valuers::buildingRow($bot, 28, 1));

        $this->assertGreaterThan($housing->net(), $hangar->net());
        $this->assertArrayHasKey('opening', $hangar->terms);
    }

    public function test_path_building_level_3_stays_positive_in_phase_2(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->setPhase($bot, 2);
        $this->placeAt($bot, BuildingId::Hangar->value, 2);

        $this->assertGreaterThan(0.0, Valuers::upgrade($bot, $this->ctx($bot), Valuers::buildingRow($bot, 44, 1))->net());
    }

    public function test_cc_steps_are_positive_in_phase_1_and_towards_level_4(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->setCc($bot, 2);
        $this->assertGreaterThan(0.0, Valuers::cc($bot, $this->ctx($bot), Valuers::ccRow($bot))->net());

        $this->setPhase($bot, 2);
        $this->setCc($bot, 3);
        $cc4 = Valuers::cc($bot, $this->ctx($bot), Valuers::ccRow($bot));
        $this->assertGreaterThan(0.0, $cc4->net());
        $this->assertSame(120, $cc4->regolithCost);
    }

    public function test_path_placement_is_cost_neutral_and_positive(): void
    {
        $bot = BotSession::boot($this, 1);
        $e = Valuers::pathPlacement($bot, $this->ctx($bot, 'hangar'), 44);

        $this->assertGreaterThan(100, $e->regolithCost);
        $this->assertGreaterThan(0.0, $e->net());
        $this->assertSame(-$e->terms['cost_rg'], $e->terms['structural']);
    }
}
```

- [ ] **Step 2: Test rot bestätigen**

Run: `bin/phpunit tests/Feature/Playtest/Utility/ValuersTest.php`
Expected: ERROR — `Class "...Valuers" not found`.

- [ ] **Step 3: Sichtbarkeit in `BotStrategy` ändern**

`private static function agrardomPlaced` (881), `supplyGated` (1278), `levelupRegolith` (1323), `effectiveApForLevelup` (1332) → jeweils `public static function`. Kein weiterer Eingriff.

- [ ] **Step 4: `Estimate` anlegen**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

/** Utility estimate of one candidate: named terms in Rg-equivalents, their sum is Net(c). */
final readonly class Estimate
{
    /** @param array<string, float> $terms */
    public function __construct(
        public array $terms,
        public int $apCost,
        public int $regolithCost,
    ) {}

    public function net(): float
    {
        return (float) array_sum($this->terms);
    }

    public function withTerm(string $name, float $value): self
    {
        return new self([...$this->terms, $name => $value], $this->apCost, $this->regolithCost);
    }
}
```

- [ ] **Step 5: `Valuers` anlegen**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use App\Console\Commands\GameTick;
use App\Enums\BuildingId;
use App\Services\BuildingCostService;
use App\Services\ColonyTileService;
use App\Services\ResourcesService;
use App\Services\Techtree\ResearchService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;

/**
 * Utility estimates for the candidate classes of step S3 (spec 2.3 + P0), shared
 * by the shadow mode (S2) and the utility sources (S3). Only player-visible
 * state: finds count once deep-scanned (spec 2.7). Every weight is an assumption
 * (Utility/weights.php).
 */
final class Valuers
{
    public const PATH_IDS = [31, 44, 52];

    private const BIO_FACILITY = 41;

    private const RES_ORGANICS = 5;

    private const CONSTRUCTION = 90;

    private const CARTOGRAPHY = 91;

    private const GEOLOGY = 92;

    private const AGRONOMY = 93;

    public static function upgrade(BotSession $b, BotContext $ctx, object $row): Estimate
    {
        $w = $ctx->weights;
        $id = (int) $row->building_id;
        $target = (int) $row->level + 1;
        $regolith = BotStrategy::levelupRegolith($row);
        $ap = max(1, BotStrategy::effectiveApForLevelup($b, (int) ($row->ap_for_levelup ?? 10)) - (int) $row->ap_spend);
        $isPath = in_array($id, self::PATH_IDS, true);

        $terms = ['cost_rg' => -1.0 * $regolith];
        if ($ctx->phase === 1 && (($target === 2 && $ctx->nonCcLevel2 < 2) || ($target === 1 && $isPath && $ctx->advisors < 3))) {
            $terms['phase1'] = (float) $w['w_phase1'] * $ctx->phase1Urgency();
        }
        if ($isPath && $target >= 2) {
            $terms['tier'] = (float) $w['w_tier'];
        }
        if ($isPath && $target === 1) {
            $terms['advisor_slot'] = self::advisorSlotValue($ctx);
        }
        if ($id === $ctx->openingBuildingId && $ctx->sol <= (int) $w['opening_bias_until_sol']) {
            $terms['opening'] = (float) $w['opening_bias'];
        }
        $trust = (int) (collect(config('buildings'))->firstWhere('id', $id)['trust_per_lv'] ?? 0);
        if ($trust > 0) {
            $terms['trust'] = $trust * $ctx->price('trust');
        }
        $unlock = self::unlock($b, $ctx, $id, $target);
        if ($unlock !== 0.0) {
            $terms['unlock'] = $unlock;
        }
        if ($id === self::BIO_FACILITY) {
            $curve = config('game.production_curve.'.self::BIO_FACILITY.'.'.self::RES_ORGANICS, []);
            $food = min((int) ($curve[$target] ?? 0), self::foodDeficit($b));
            if ($food > 0) {
                $terms['food'] = $ctx->stream($food * $ctx->price('organics'), 1);
            }
        }
        $objective = $ctx->objectives['task_engineering_output'] ?? null;
        if ($objective !== null && (float) $w['w_objective'] > 0.0) {
            $terms['objective'] = (float) $w['w_objective'] / max(1, $objective['target']);
        }

        return new Estimate($terms, $ap, $regolith);
    }

    public static function cc(BotSession $b, BotContext $ctx, object $cc): Estimate
    {
        $w = $ctx->weights;
        $level = (int) $cc->level;
        $target = $level + 1;
        $regolith = BotStrategy::levelupRegolith($cc);
        $ap = max(1, BotStrategy::effectiveApForLevelup($b, (int) ($cc->ap_for_levelup ?? 10)) - (int) $cc->ap_spend);

        $terms = ['cost_rg' => -1.0 * $regolith, 'structural' => $regolith * (float) $w['structural_offset']];
        if ($ctx->phase === 1 && $target <= 3) {
            $terms['phase1'] = (float) $w['w_phase1'] * $ctx->phase1Urgency();
        }
        if ($target >= 4) {
            $terms['tier'] = (float) $w['w_tier'];
        }
        $maxSlots = (int) config('game.advisor.max_slots', 4);
        if ($target <= $maxSlots && $ctx->advisors >= min($level, $maxSlots)) {
            $terms['advisor_slot'] = self::advisorSlotValue($ctx);
        }
        $placedPath = DB::table('colony_buildings')->where('colony_id', $b->colonyId)
            ->whereIn('building_id', self::PATH_IDS)->whereNotNull('tile_x')->count();
        if ($placedPath < count(self::PATH_IDS) && $placedPath >= $level - 1) {
            $terms['path_slot'] = (float) $w['w_tier'];
        }
        $knowledgeLevel = array_search($target, config('game.knowledge_cc_level_cap', []), true);
        if ($knowledgeLevel !== false) {
            $sum = 0.0;
            $rows = DB::table('colony_researches')->where('colony_id', $b->colonyId)
                ->whereBetween('research_id', [90, 96])->where('level', (int) $knowledgeLevel - 1)
                ->orderBy('research_id')->get(['research_id', 'level', 'ap_spend']);
            foreach ($rows as $row) {
                $sum += self::research($b, $ctx, (int) $row->research_id, (int) $row->level, (int) $row->ap_spend)->net();
            }
            if ($sum > 0.0) {
                $terms['knowledge_cap'] = $sum;
            }
        }

        return new Estimate($terms, $ap, $regolith);
    }

    public static function research(BotSession $b, BotContext $ctx, int $researchId, int $level, int $apSpend): Estimate
    {
        $w = $ctx->weights;
        $target = $level + 1;
        $cfg = collect(config('knowledge'))->first(fn (array $k): bool => (int) $k['id'] === $researchId) ?? [];
        $fallback = (int) (DB::table('researches')->where('id', $researchId)->value('ap_for_levelup') ?? 0);
        $ap = max(1, app(ResearchService::class)->knowledgeLevelupCost($b->colonyId, $researchId, $fallback) - $apSpend);
        $latency = (int) ceil($ap / max(1, $ctx->apInflow));

        $effect = match ($researchId) {
            self::GEOLOGY => $ctx->stream((float) (config('game.geology_harvester_bonus_per_level')[$target] ?? 0), $latency),
            self::CONSTRUCTION => $ctx->stream(($cfg['ap_cost_reduction_per_lv'][$target] ?? 0) / 100 * (float) $w['build_ap_per_sol'] * $ctx->lambdaAp, $latency),
            self::CARTOGRAPHY => $ctx->stream(($cfg['nav_ap_reduction_per_lv'][$target] ?? 0) / 100 * (float) $w['nav_ap_per_sol'] * $ctx->lambdaAp, $latency),
            self::AGRONOMY => $ctx->stream(min((int) (config('game.agronomy_agrardom_bonus_per_level')[$target] ?? 0), self::foodDeficit($b)) * $ctx->price('organics'), $latency),
            default => 0.0,
        };

        $terms = ['floor' => (float) $w['research_floor']];
        if ($effect > 0.0) {
            $terms['effect'] = $effect;
        }
        $trust = (int) ($cfg['trust_per_lv'] ?? 0);
        if ($trust > 0) {
            $terms['trust'] = $trust * $ctx->price('trust');
        }

        return new Estimate($terms, $ap, 0);
    }

    public static function pathPlacement(BotSession $b, BotContext $ctx, int $buildingId): Estimate
    {
        $w = $ctx->weights;
        $regolith = (int) (app(BuildingCostService::class)->placementCost($buildingId)[3] ?? 0);
        $terms = [
            'cost_rg' => -1.0 * $regolith,
            'structural' => $regolith * (float) $w['structural_offset'],
            'tier' => (float) $w['w_tier'],
            'advisor_slot' => self::advisorSlotValue($ctx),
        ];
        if ($ctx->phase === 1 && $ctx->advisors < 3) {
            $terms['phase1'] = (float) $w['w_phase1'] * $ctx->phase1Urgency();
        }
        if ($buildingId === $ctx->openingBuildingId && $ctx->sol <= (int) $w['opening_bias_until_sol']) {
            $terms['opening'] = (float) $w['opening_bias'];
        }

        return new Estimate($terms, 1, $regolith);
    }

    /** One more advisor: Rank-1 AP stream valued at λ_AP minus its upkeep (spec 2.3). */
    public static function advisorSlotValue(BotContext $ctx): float
    {
        $ap = (int) (config('game.advisor.ap_per_rank')[1] ?? 2);
        $upkeep = (int) (config('game.advisor.upkeep')[1] ?? 10);

        return $ctx->stream($ap * $ctx->lambdaAp - $upkeep * $ctx->price('credits'), 1);
    }

    /** @return list<object> placed non-CC, non-Harvester buildings below max_level */
    public static function upgradeRows(BotSession $b): array
    {
        return self::rowQuery($b)
            ->whereNotIn('cb.building_id', [BuildingId::CommandCenter->value, BuildingId::Harvester->value])
            ->whereNotNull('cb.tile_x')
            ->where(fn ($q) => $q->whereNull('bld.max_level')->orWhereColumn('cb.level', '<', 'bld.max_level'))
            ->orderBy('cb.building_id')->orderBy('cb.instance_id')
            ->get()->all();
    }

    public static function buildingRow(BotSession $b, int $buildingId, int $instanceId): ?object
    {
        return self::rowQuery($b)->where('cb.building_id', $buildingId)->where('cb.instance_id', $instanceId)->first();
    }

    public static function ccRow(BotSession $b): ?object
    {
        return self::rowQuery($b)->where('cb.building_id', BuildingId::CommandCenter->value)->addSelect('bld.max_level')->first();
    }

    private static function rowQuery(BotSession $b): \Illuminate\Database\Query\Builder
    {
        return DB::table('colony_buildings as cb')
            ->join('buildings as bld', 'bld.id', '=', 'cb.building_id')
            ->where('cb.colony_id', $b->colonyId)
            ->select('cb.building_id', 'cb.instance_id', 'cb.level', 'cb.ap_spend', 'cb.tile_x', 'bld.supply_cost', 'bld.ap_for_levelup');
    }

    private static function unlock(BotSession $b, BotContext $ctx, int $id, int $target): float
    {
        if ($id === BuildingId::Hangar->value) {
            return self::waitingFindValue($b, $ctx, $target);
        }
        if ($id === BuildingId::Sciencelab->value && $target === 2) {
            $geology = (int) (DB::table('colony_researches')->where('colony_id', $b->colonyId)->where('research_id', self::GEOLOGY)->value('level') ?? 0);
            if ($geology === 0) {
                $cfg = collect(config('knowledge'))->first(fn (array $k): bool => (int) $k['id'] === self::GEOLOGY) ?? [];
                $latency = (int) ceil((int) ($cfg['levelup_costs'][1] ?? 20) / max(1, $ctx->apInflow));

                return $ctx->stream((float) (config('game.geology_harvester_bonus_per_level')[1] ?? 0), $latency);
            }
        }

        return 0.0;
    }

    /**
     * Scanned finds waiting for exactly this hangar level (findAction needs_level /
     * ship_inactive), worth rg × yield_factor; a large one also needs a freighter.
     */
    private static function waitingFindValue(BotSession $b, BotContext $ctx, int $target): float
    {
        $service = app(ColonyTileService::class);
        $types = config('game.finds.types');
        $value = 0.0;
        $needsFreighter = false;
        $tiles = DB::table('colony_tiles')->where('colony_id', $b->colonyId)->where('is_deep_scanned', 1)
            ->whereIn('event_type', ColonyTileService::FIND_TYPES)->orderBy('ring')->orderBy('q')->orderBy('r')->get();
        foreach ($tiles as $tile) {
            $action = $service->findAction($b->colonyId, $tile);
            if (! in_array($action['reason'], ['needs_level', 'ship_inactive'], true) || (int) ($action['needs_level'] ?? 0) > $target) {
                continue;
            }
            $value += (int) $types[$tile->event_type]['rg'] * (float) config('game.finds.flight.yield_factor');
            $needsFreighter = $needsFreighter || $tile->event_type === 'find_large';
        }
        $hasFreighter = DB::table('colony_ships')->where('colony_id', $b->colonyId)
            ->where('ship_id', (int) config('ships.freighter.id'))->exists();
        if ($needsFreighter && ! $hasFreighter) {
            $value -= (int) config('ships.freighter.nexus_cost', 0) * $ctx->price('credits');
        }

        return $value;
    }

    /** Organika the colony's need exceeds the current Agrardom yield by (feed_colony stays mandatory). */
    private static function foodDeficit(BotSession $b): int
    {
        $level = (int) (DB::table('colony_buildings')->where('colony_id', $b->colonyId)->where('building_id', self::BIO_FACILITY)
            ->whereNotNull('tile_x')->orderBy('instance_id')->value('level') ?? 0);
        $curve = config('game.production_curve.'.self::BIO_FACILITY.'.'.self::RES_ORGANICS, []);

        return max(0, app(ResourcesService::class)->foodNeed($b->colonyId) - GameTick::cumulativeCurveYield($curve, $level));
    }
}
```

- [ ] **Step 6: Tests grün (inkl. Golden, weil `BotStrategy` angefasst)**

Run: `bin/phpunit tests/Feature/Playtest/Utility/ValuersTest.php tests/Feature/Playtest/Utility/AdapterGoldenTest.php`
Expected: PASS. Schlägt ein Relativtest fehl, zuerst die Terme ausgeben (`dump($e->terms)`) und den Term mit der Spec/P0 abgleichen — keinen Test lockern.

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/Playtest/Utility/Estimate.php tests/Feature/Playtest/Utility/Valuers.php tests/Feature/Playtest/Utility/ValuersTest.php tests/Feature/Playtest/BotStrategy.php
git commit -m "test(playtest): Bewertungsterme für Ausbau, CC, Forschung, Pfadplatzierung (Utility S2)"
```

---

## Task 13 (S2): Schattenmodus und K22

**Files:**
- Create: `tests/Feature/Playtest/Utility/LegacyValuation.php`
- Modify: `tests/Feature/Playtest/Utility/Candidate.php` (`withEstimate()`), `Decision.php` (`shadow`, `withShadow()`), `UtilitySelector.php` (`shadowPick()`), `Collector.php` (Modus `shadow`, Gewichte), `UtilityStrategy.php` (`shadow()`), `DecisionLog.php` (`shadow`)
- Modify: `tests/Feature/Playtest/BotProfile.php` (`ENGINES` + `shadow`), `tests/Feature/Playtest/BotEngine.php`
- Modify: `tests/Feature/Playtest/RunReport.php` (`utilitySummary()` → `shadow_divergence`), `app/Support/OpeningComparison.php` (`k22`), `app/Console/Commands/PlaytestCompare.php` (Label)
- Test: `tests/Unit/Playtest/UtilitySelectorTest.php` (erweitern), `tests/Feature/Playtest/Utility/ShadowModeTest.php` (neu), `tests/Feature/Playtest/Utility/AdapterGoldenTest.php` (erweitern), `tests/Feature/Playtest/RunReportUtilitySummaryTest.php` (erweitern), `tests/Unit/OpeningComparisonUtilityKpiTest.php` (erweitern)

**Interfaces:**
- Consumes: `Valuers`, `BotContext::capture` (Tasks 11, 12).
- Produces: `Candidate::withEstimate(Estimate): Candidate`; `Decision::$shadow` (`?Candidate`), `withShadow(?Candidate): Decision`; `UtilitySelector::shadowPick(array $candidates, array $blocked, float $minNet, int $minBand = -1): ?Candidate`; `LegacyValuation::estimate(Candidate, BotSession, BotContext): ?Estimate`; `UtilityStrategy::shadow(BotProfile): array`; Log-Feld `shadow` (`?array{name,key,score}`); Summary-Feld `shadow_divergence` (K22, % oder null); `metrics()['k22']`; `BotProfile::ENGINES = ['legacy', 'utility', 'shadow']`; `Collector::MODES = ['adapter', 'utility', 'shadow']`.

- [ ] **Step 1: Failing tests schreiben**

An `tests/Unit/Playtest/UtilitySelectorTest.php` anhängen:

```php
    public function test_shadow_pick_prefers_a_mandatory_candidate(): void
    {
        $pick = UtilitySelector::shadowPick([self::c('x', 5, 99.0), self::c('feed', 9, null, 1, '', true)], [], 0.01);

        $this->assertSame('feed', $pick->name);
    }

    public function test_shadow_pick_takes_the_best_score_across_bands(): void
    {
        $pick = UtilitySelector::shadowPick([self::c('low_band', 1, 1.0), self::c('high_band', 9, 5.0), self::c('legacy', 0)], [], 0.01);

        $this->assertSame('high_band', $pick->name);
    }

    public function test_shadow_pick_is_null_without_scored_candidates(): void
    {
        $this->assertNull(UtilitySelector::shadowPick([self::c('legacy', 0)], [], 0.01));
    }

    public function test_with_estimate_scores_a_legacy_candidate(): void
    {
        $scored = self::c('legacy', 3)->withEstimate(new \Tests\Feature\Playtest\Utility\Estimate(['a' => 6.0], 3, 25));

        $this->assertSame([6.0, 3, 25, 3, 2000], [$scored->net, $scored->apCost, $scored->regolithCost, $scored->band, $scored->milliScore()]);
    }
```

`tests/Feature/Playtest/Utility/ShadowModeTest.php`:

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\TestCase;

/** Spec S2: the shadow mode scores the legacy candidates but never changes the decision. */
class ShadowModeTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    public function test_shadow_decision_keeps_the_legacy_choice_and_carries_the_utility_pick(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->setCc($bot, 2);
        $rules = [
            ['name' => 'explore_tile', 'when' => fn () => (object) ['q' => 1, 'r' => 0], 'do' => fn () => ['ok' => true]],
            ['name' => 'invest_cc', 'when' => fn () => true, 'do' => fn () => ['ok' => true]],
        ];
        $collector = new Collector(UtilityStrategy::legacySourcesFrom($rules), 'shadow', BotProfile::named('default'));

        $decision = $collector->when($bot);

        $this->assertSame('explore_tile', $decision->chosen->name);
        $this->assertSame('invest_cc', $decision->shadow?->name);
        $this->assertTrue($decision->shadow->isScored());
    }

    public function test_legacy_valuation_scores_only_the_migrating_classes(): void
    {
        $bot = BotSession::boot($this, 1);
        $ctx = BotContext::capture($bot, BotProfile::named('default'));
        $cc = new Candidate(name: 'invest_cc', key: 'invest_cc', band: 6, net: null, apCost: 0, do: fn () => ['ok' => true], payload: true);
        $explore = new Candidate(name: 'explore_tile', key: 'explore_tile', band: 15, net: null, apCost: 0, do: fn () => ['ok' => true]);

        $this->assertNotNull(LegacyValuation::estimate($cc, $bot, $ctx));
        $this->assertNull(LegacyValuation::estimate($explore, $bot, $ctx));
    }
}
```

An `AdapterGoldenTest` anhängen:

```php
    public function test_shadow_plays_the_legacy_action_sequence_and_scores_decisions(): void
    {
        $profile = BotProfile::named('default')->withOpening('hangar');

        $legacy = $this->play(7, BotStrategy::default($profile));
        $bot = BotSession::boot($this, 7);
        $this->playSolsUntil($bot, UtilityStrategy::shadow($profile), self::stopAtSol(self::UNTIL_SOL));
        $shadow = array_map(fn (array $e): array => [$e['sol'], $e['rule'], self::normalizeUrl($e['url']), $e['ok'], $e['error']], $bot->log);

        $this->assertSame($legacy, $shadow, self::firstDifference($legacy, $shadow));
        $this->assertNotEmpty(array_filter($bot->decisions, fn (array $d): bool => $d['shadow'] !== null), 'shadow must score at least one decision');
    }
```

An `RunReportUtilitySummaryTest` anhängen:

```php
    public function test_shadow_divergence_is_the_share_of_scored_decisions_the_utility_would_change(): void
    {
        $bot = BotSession::boot($this, 7);
        $bot->sol = 1;
        $same = self::d(0, 'legacy') + ['shadow' => ['name' => 'x', 'key' => 'x', 'score' => 100]];
        $other = self::d(0, 'legacy') + ['shadow' => ['name' => 'y', 'key' => 'y', 'score' => 300]];
        $none = self::d(0, 'legacy') + ['shadow' => null];
        $bot->decisions = [$same, $other, $other, $none];

        $this->assertEqualsWithDelta(66.7, (new RunReport(7, engine: 'shadow'))->build($bot)['utility_summary']['shadow_divergence'], 0.05);
    }
```

An `OpeningComparisonUtilityKpiTest` anhängen:

```php
    public function test_k22_comes_from_the_shadow_divergence(): void
    {
        $this->assertSame(12.5, OpeningComparison::metrics($this->report('labor', 1, [], [], ['shadow_divergence' => 12.5]))['k22']);
        $this->assertNull(OpeningComparison::metrics($this->report('labor', 1, []))['k22']);
    }
```

- [ ] **Step 2: Tests rot bestätigen**

Run: `bin/phpunit tests/Unit/Playtest/UtilitySelectorTest.php tests/Feature/Playtest/Utility/ShadowModeTest.php tests/Feature/Playtest/RunReportUtilitySummaryTest.php tests/Unit/OpeningComparisonUtilityKpiTest.php`
Expected: ERROR/FAIL — `Call to undefined method ...UtilitySelector::shadowPick()`, `Unknown collector mode: shadow`, `Undefined array key "k22"`.

- [ ] **Step 3: Wertobjekte und Selektor erweitern**

`Candidate.php`, nach `milliScore()`:

```php
    /** The same candidate, scored by $e (shadow mode). */
    public function withEstimate(Estimate $e): self
    {
        return new self($this->name, $this->key, $this->band, $e->net(), $e->apCost, $this->do, $e->terms, $this->mandatory, $this->payload, $e->regolithCost);
    }
```

`Decision.php`: Konstruktor um `public ?Candidate $shadow = null,` (letzter Parameter) erweitern und ergänzen:

```php
    public function withShadow(?Candidate $shadow): self
    {
        return new self($this->chosen, $this->candidatesN, $this->alternatives, $this->all, $shadow);
    }
```

`UtilitySelector.php`, nach `selectFromSources()`:

```php
    /**
     * What a full utility engine would pick (spec S2 shadow): a mandatory candidate
     * if any (by band), else the best milli-score across all bands; null when no
     * candidate is scored.
     *
     * @param  list<Candidate>  $candidates
     * @param  list<string>  $blocked
     */
    public static function shadowPick(array $candidates, array $blocked, float $minNet, int $minBand = -1): ?Candidate
    {
        $eligible = array_values(array_filter($candidates, fn (Candidate $c): bool => $c->band >= $minBand && self::eligible($c, $blocked, $minNet)));

        $mandatory = array_values(array_filter($eligible, fn (Candidate $c): bool => $c->mandatory));
        if ($mandatory !== []) {
            usort($mandatory, [self::class, 'compare']);

            return $mandatory[0];
        }

        $scored = array_values(array_filter($eligible, fn (Candidate $c): bool => $c->isScored()));
        if ($scored === []) {
            return null;
        }
        usort($scored, fn (Candidate $x, Candidate $y): int => [-$x->milliScore(), $x->name, $x->key] <=> [-$y->milliScore(), $y->name, $y->key]);

        return $scored[0];
    }
```

- [ ] **Step 4: `LegacyValuation` anlegen**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotSession;

/** Shadow mode (S2): utility estimate for the legacy candidates of the S3 classes; null = not scored. */
final class LegacyValuation
{
    public static function estimate(Candidate $c, BotSession $b, BotContext $ctx): ?Estimate
    {
        if (in_array($c->name, ['invest_production', 'focus_engineering_levelup'], true)) {
            $row = Valuers::buildingRow($b, (int) $c->payload->building_id, (int) $c->payload->instance_id);

            return $row === null ? null : Valuers::upgrade($b, $ctx, $row);
        }
        if ($c->name === 'invest_cc') {
            $cc = Valuers::ccRow($b);

            return $cc === null ? null : Valuers::cc($b, $ctx, $cc);
        }
        if ($c->name === 'research_knowledge') {
            $row = DB::table('colony_researches')->where('colony_id', $b->colonyId)->where('research_id', (int) $c->payload)->first(['level', 'ap_spend']);

            return Valuers::research($b, $ctx, (int) $c->payload, (int) ($row->level ?? 0), (int) ($row->ap_spend ?? 0));
        }
        if ($c->name === 'place_building' && in_array((int) $c->payload[0]['building_id'], Valuers::PATH_IDS, true)) {
            return Valuers::pathPlacement($b, $ctx, (int) $c->payload[0]['building_id']);
        }

        return null;
    }
}
```

- [ ] **Step 5: `Collector` um Modus `shadow` erweitern**

`MODES` auf `['adapter', 'utility', 'shadow']` setzen. Feld ergänzen: `private array $weights = [];`. Im Konstruktor nach der Modus-Prüfung: `$this->weights = $profile->weights();`. `when()` ersetzen:

```php
    public function when(BotSession $b): ?Decision
    {
        if ($b->sol !== $this->sol) {
            $this->sol = $b->sol;
            $this->cursorBand = -1;
        }

        $shadow = $this->mode === 'shadow';
        $ctx = $shadow ? BotContext::capture($b, $this->profile) : new BotContext(sol: $b->sol);
        $minNet = (float) $this->weights['min_net'];
        $decision = UtilitySelector::selectFromSources($this->sources, $b, $ctx, $b->blockedThisSol, $this->cursorBand, $minNet, fullSearch: $shadow);

        return $decision !== null && $shadow ? $this->withShadow($decision, $b, $ctx) : $decision;
    }

    /** Scores every collected legacy candidate it can and records what a utility engine would pick. */
    private function withShadow(Decision $decision, BotSession $b, BotContext $ctx): Decision
    {
        $scored = array_map(function (Candidate $c) use ($b, $ctx): Candidate {
            if ($c->isScored() || $c->mandatory) {
                return $c;
            }
            $estimate = LegacyValuation::estimate($c, $b, $ctx);

            return $estimate === null ? $c : $c->withEstimate($estimate);
        }, $decision->all);

        return $decision->withShadow(UtilitySelector::shadowPick($scored, $b->blockedThisSol, (float) $this->weights['min_net'], $this->cursorBand));
    }
```

- [ ] **Step 6: Engine `shadow` freischalten**

`UtilityStrategy.php` ergänzen:

```php
    /** Engine "shadow" (S2): legacy decides, the utility pick is only logged (K22). */
    public static function shadow(BotProfile $profile): array
    {
        return self::fromRules(BotStrategy::default($profile), 'shadow', $profile);
    }
```

`BotProfile::ENGINES` → `['legacy', 'utility', 'shadow']`; `BotEngine::rules()` um `'shadow' => UtilityStrategy::shadow($profile),` ergänzen.
`DecisionLog::entry()` vor `'ok' => $ok,` einfügen:

```php
            'shadow' => $d->shadow === null ? null : ['name' => $d->shadow->name, 'key' => $d->shadow->key, 'score' => $d->shadow->milliScore()],
```

- [ ] **Step 7: Report und Vergleich (K22)**

In `RunReport::utilitySummary()` vor `return [` einfügen:

```php
        $shadowed = array_values(array_filter($bot->decisions, fn (array $d): bool => ($d['shadow'] ?? null) !== null));
        $diverging = array_filter($shadowed, fn (array $d): bool => $d['shadow']['name'] !== $d['name'] || $d['shadow']['key'] !== $d['key']);
```

und im Rückgabe-Array ergänzen:

```php
            'shadow_divergence' => $shadowed === [] ? null : round(count($diverging) / count($shadowed) * 100, 1),
```

`OpeningComparison::metrics()` Rückgabe: `'k22' => isset($summary['shadow_divergence']) ? (float) $summary['shadow_divergence'] : null,`; Docblock: ` *  - K22 (informational, shadow engine): utility_summary.shadow_divergence (% of scored decisions a utility engine would change).`; KPI-Liste (Zeile 349) `'k22'` anhängen; `array_fill_keys`-Liste `'K22'`; Checks: `'K22' => $info('Shadow divergence %, median', $kpis['k22'], 'informational, S2'),`. `PlaytestCompare::KPI_LABELS`: `'k22' => 'K22 Schatten-Abweichung %',`.

- [ ] **Step 8: Tests grün**

Run: `bin/phpunit tests/Unit/Playtest tests/Unit/OpeningComparisonUtilityKpiTest.php tests/Feature/Playtest/Utility tests/Feature/Playtest/RunReportUtilitySummaryTest.php tests/Feature/Console/PlaytestCompareTest.php tests/Feature/Console/PlaytestCommandEngineTest.php`
Expected: PASS (inkl. neuem Golden-Test für `shadow`).

- [ ] **Step 9: Commit**

```bash
git add tests/Feature/Playtest/Utility/LegacyValuation.php tests/Feature/Playtest/Utility/Candidate.php tests/Feature/Playtest/Utility/Decision.php tests/Feature/Playtest/Utility/UtilitySelector.php tests/Feature/Playtest/Utility/Collector.php tests/Feature/Playtest/Utility/UtilityStrategy.php tests/Feature/Playtest/Utility/DecisionLog.php tests/Feature/Playtest/BotProfile.php tests/Feature/Playtest/BotEngine.php tests/Feature/Playtest/RunReport.php app/Support/OpeningComparison.php app/Console/Commands/PlaytestCompare.php tests/Unit/Playtest/UtilitySelectorTest.php tests/Feature/Playtest/Utility/ShadowModeTest.php tests/Feature/Playtest/Utility/AdapterGoldenTest.php tests/Feature/Playtest/RunReportUtilitySummaryTest.php tests/Unit/OpeningComparisonUtilityKpiTest.php
git commit -m "feat(playtest): Schattenmodus --engine=shadow mit Abweichungsquote K22 (Utility S2)"
```

---
## Task 14 (S2): λ_AP-Verlauf und K21

**Files:**
- Modify: `tests/Feature/Playtest/Utility/Collector.php` (Sol-Abschluss, Grenzdichte, `apProbe`)
- Modify: `tests/Feature/Playtest/RunReport.php` (`utilitySummary()` → `lambda_ap`), `app/Support/OpeningComparison.php` (`k21_4_12`, `k21_13_25`), `app/Console/Commands/PlaytestCompare.php` (Labels)
- Test: `tests/Feature/Playtest/Utility/LambdaTrackingTest.php` (neu), `tests/Unit/OpeningComparisonUtilityKpiTest.php` (erweitern)

**Interfaces:**
- Consumes: `Valuation::lambdaEma`, `BotSession::$lambdaAp/$lambdaBySol` (Tasks 10, 11), `Collector`-Konstruktorparameter `?\Closure $apProbe` (seit Task 4 vorhanden, `fn (BotSession): int`).
- Produces: In den Modi `utility` und `shadow` schließt der Sammler beim ersten `when()` eines neuen Sols den alten Sol ab: Grenzdichte = Score der letzten erfolgreich ausgeführten bewerteten Entscheidung (im Schatten: Schätzwert des ausgeführten Legacy-Kandidaten), 0 wenn der Sol mit freien AP und ohne Kandidat endete, sonst keine Aktualisierung. Summary-Feld `lambda_ap` (`array<sol, float>`); `metrics()['k21_4_12']`, `['k21_13_25']` (Mittel, `?float`).

- [ ] **Step 1: Failing tests schreiben**

`tests/Feature/Playtest/Utility/LambdaTrackingTest.php`:

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\PlaysSolLoop;
use Tests\TestCase;

/** Spec 2.4: λ_AP = EMA of the marginal density per Sol; a Sol ending with idle AP counts as 0. */
class LambdaTrackingTest extends TestCase
{
    use PlaysSolLoop;
    use RefreshDatabase;

    public function test_lambda_follows_the_last_executed_score_and_idle_sols_pull_it_down(): void
    {
        $bot = BotSession::boot($this, 1);
        $source = new class implements CandidateSource
        {
            /** @var array<int, bool> Sols in which the candidate already fired */
            public array $fired = [];

            public function name(): string
            {
                return 'work';
            }

            public function band(): int
            {
                return 0;
            }

            public function candidates(BotSession $b, BotContext $ctx): array
            {
                if (isset($this->fired[$b->sol])) {
                    return [];
                }

                return [new Candidate(name: 'work', key: 'work', band: 0, net: 3.0, apCost: 1, do: function (BotSession $s): array {
                    $this->fired[$s->sol] = true;
                    $s->log[] = ['sol' => $s->sol, 'rule' => 'work', 'url' => '/w/'.$s->sol, 'status' => 200, 'ok' => true, 'error' => null,
                        'ap_before' => 5, 'ap_after' => 4, 'regolith_before' => 0, 'regolith_after' => 0, 'organics_before' => 0, 'organics_after' => 0];

                    return ['ok' => true];
                })];
            }
        };
        $idleAp = 0;
        $collector = new Collector([$source], 'utility', BotProfile::named('default'), function () use (&$idleAp): int {
            return $idleAp;
        });
        // The fake source ignores the context; only the AP probe decides "idle".
        $rules = [$collector->rule()];

        $this->playOneSol($bot, $rules);   // Sol 0: score 3.0, no AP left
        $bot->sol = 1;
        $idleAp = 5;
        $this->playOneSol($bot, $rules);   // Sol 1: score 3.0, but ends with idle AP -> 0
        $bot->sol = 2;
        $this->playOneSol($bot, $rules);   // closes Sol 1

        $this->assertEqualsWithDelta(1.25, $bot->lambdaBySol[0], 1e-6);   // 0.7 * 0.5 + 0.3 * 3.0
        $this->assertEqualsWithDelta(0.875, $bot->lambdaBySol[1], 1e-6);  // 0.7 * 1.25 + 0.3 * 0
        $this->assertEqualsWithDelta(0.875, $bot->lambdaAp, 1e-6);
    }

    public function test_adapter_mode_never_tracks_lambda(): void
    {
        $bot = BotSession::boot($this, 1);
        $rules = UtilityStrategy::fromRules([['name' => 'noop', 'when' => fn () => null, 'do' => fn () => ['ok' => true]]], 'adapter', BotProfile::named('default'));

        $this->playOneSol($bot, $rules);
        $bot->sol = 1;
        $this->playOneSol($bot, $rules);

        $this->assertSame([], $bot->lambdaBySol);
        $this->assertNull($bot->lambdaAp);
    }
}
```

An `OpeningComparisonUtilityKpiTest` anhängen:

```php
    public function test_k21_is_the_mean_lambda_per_window(): void
    {
        $lambda = [];
        foreach (range(1, 15) as $s) {
            $lambda[$s] = $s <= 12 ? 1.0 : 2.0;
        }
        $lambda[4] = 3.0;

        $m = OpeningComparison::metrics($this->report('labor', 1, [], [], ['lambda_ap' => $lambda]));

        // Sols 4-12: (3 + 8 * 1) / 9; Sols 13-25 present: 13, 14, 15 -> 2.0
        $this->assertEqualsWithDelta(11 / 9, $m['k21_4_12'], 1e-9);
        $this->assertEqualsWithDelta(2.0, $m['k21_13_25'], 1e-9);
    }
```

- [ ] **Step 2: Tests rot bestätigen**

Run: `bin/phpunit tests/Feature/Playtest/Utility/LambdaTrackingTest.php tests/Unit/OpeningComparisonUtilityKpiTest.php`
Expected: FAIL — `Undefined array key 0` (λ wird nicht geführt), `Undefined array key "k21_4_12"`.

- [ ] **Step 3: `Collector` erweitern**

Felder ergänzen:

```php
    private ?float $pendingMarginal = null;

    private ?float $lastMarginal = null;

    private bool $endedIdle = false;
```

In `when()` den Sol-Wechsel-Block ersetzen durch:

```php
        if ($b->sol !== $this->sol) {
            if ($this->mode !== 'adapter' && $this->sol >= 0) {
                $this->closeSol($b);
            }
            $this->sol = $b->sol;
            $this->cursorBand = -1;
            $this->lastMarginal = null;
            $this->endedIdle = false;
        }
```

In `when()` nach dem Aufruf von `selectFromSources()` (vor dem `return`) einfügen:

```php
        if ($decision === null) {
            if ($this->mode !== 'adapter') {
                $this->endedIdle = $this->availableAp($b) > 0;
            }

            return null;
        }
        $this->pendingMarginal = $decision->chosen->isScored() ? $decision->chosen->milliScore() / 1000 : null;
```

In `withShadow()` nach dem Aufbau von `$scored` einfügen:

```php
        foreach ($scored as $c) {
            if ($c->key === $decision->chosen->key && $c->band === $decision->chosen->band && $c->isScored()) {
                $this->pendingMarginal = $c->milliScore() / 1000;
            }
        }
```

In `do()` nach `$this->cursorBand = …;` einfügen:

```php
        if ($res['ok'] && $this->pendingMarginal !== null) {
            $this->lastMarginal = $this->pendingMarginal;
        }
        $this->endedIdle = false;
```

Neue Methoden:

```php
    /** Spec 2.4: λ_AP ← EMA(marginal density of the closed Sol); idle AP at the end count as 0. */
    private function closeSol(BotSession $b): void
    {
        $marginal = $this->endedIdle ? 0.0 : $this->lastMarginal;
        if ($marginal === null) {
            return;
        }
        $previous = $b->lambdaAp ?? (float) $this->weights['lambda_ap_start'];
        $b->lambdaAp = Valuation::lambdaEma($previous, $marginal, (float) $this->weights['lambda_ap_ema']);
        $b->lambdaBySol[$this->sol] = round($b->lambdaAp, 4);
    }

    private function availableAp(BotSession $b): int
    {
        return $this->apProbe !== null
            ? ($this->apProbe)($b)
            : app(\App\Services\AdvisorService::class)->getAvailableActionPoints($b->colonyId);
    }
```

- [ ] **Step 4: Report und Vergleich (K21)**

`RunReport::utilitySummary()` Rückgabe ergänzen: `'lambda_ap' => $bot->lambdaBySol,`.
`OpeningComparison::metrics()`: vor dem `return` einfügen:

```php
        $lambda = $summary['lambda_ap'] ?? [];
        $lambdaMean = function (int $from, int $to) use ($lambda): ?float {
            $values = array_values(array_filter($lambda, fn ($v, $sol) => (int) $sol >= $from && (int) $sol <= $to, ARRAY_FILTER_USE_BOTH));

            return $values === [] ? null : array_sum($values) / count($values);
        };
```

Rückgabe ergänzen: `'k21_4_12' => $lambdaMean(4, 12), 'k21_13_25' => $lambdaMean(13, 25),`. Docblock: ` *  - K21 (informational, utility/shadow): mean λ_AP (utility_summary.lambda_ap) over Sols 4-12 / 13-25.` KPI-Liste: `'k21_4_12', 'k21_13_25'` anhängen; `array_fill_keys`-Liste `'K21'`; Check: `'K21' => $info('lambda_AP Sol 4-12, median of run means', $kpis['k21_4_12'], 'informational'),`. `PlaytestCompare::KPI_LABELS`: `'k21_4_12' => 'K21 λ_AP Sol 4-12', 'k21_13_25' => 'K21 λ_AP Sol 13-25',`.

- [ ] **Step 5: Tests grün**

Run: `bin/phpunit tests/Feature/Playtest/Utility tests/Unit/OpeningComparisonUtilityKpiTest.php tests/Feature/Console/PlaytestCompareTest.php`
Expected: PASS (inkl. Golden-Tests).

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Playtest/Utility/Collector.php tests/Feature/Playtest/RunReport.php app/Support/OpeningComparison.php app/Console/Commands/PlaytestCompare.php tests/Feature/Playtest/Utility/LambdaTrackingTest.php tests/Unit/OpeningComparisonUtilityKpiTest.php
git commit -m "feat(playtest): λ_AP-Verlauf je Sol und Kennzahl K21 (Utility S2)"
```

---

## Task 15: Zwischenstand B (S2) — verifizieren, Schatten-Stichprobe, PR

**Files:**
- Modify: `CHANGELOG.md`, `docs/superpowers/specs/2026-10-12-playtestbot-utility-modell.md` (Abschnitt „10. Messung S2“, nur wenn gemessen)

- [ ] **Step 1: Schnelle Suite**

Run: `php artisan test --parallel --testsuite=laravel-feature,laravel-unit`
Expected: PASS.

- [ ] **Step 2: Komplette Playtest-Suite**

```bash
nohup bin/phpunit --testsuite playtest > storage/logs/playtest-suite-B.log 2>&1 &
```

Poll mit `tail -n 5 storage/logs/playtest-suite-B.log`, bis `OK`/`FAILURES!`. Expected: `OK`.

- [ ] **Step 3 (optional, Owner entscheidet): Schatten-Stichprobe M-S2**

Owner fragen: „Schatten-Stichprobe laufen lassen? 8 Läufe (labor+hangar × Seeds 1–4, ca. 12 min), 12 Läufe (3 Eröffnungen × Seeds 1–4, ca. 15 min) oder 24 (Seeds 1–8, ca. 25 min); vorher keine rechenintensiven Programme starten.“ Bei Zustimmung (Beispiel 12):

```bash
nohup php artisan game:playtest --engine=shadow --openings=labor,hangar,cantina --seeds=1,2,3,4 --until-sol=16 > storage/logs/playtest/batch-s2-shadow.log 2>&1 &
```

Danach (Startzeit in UTC als `--since`):

```bash
php artisan game:playtest-compare --engine=shadow --openings=labor,hangar,cantina --seeds=1,2,3,4 --since="<Startzeit UTC>"
```

Für Sol 4–15 die Top-3 je Entscheidung stichprobenartig lesen:

```bash
php -r '$r = json_decode(file_get_contents($argv[1]), true); foreach ($r["decisions"] as $d) { if ($d["sol"] >= 4 && $d["sol"] <= 15 && $d["shadow"] !== null && $d["shadow"]["key"] !== $d["key"]) echo $d["sol"], " legacy=", $d["name"], " utility=", $d["shadow"]["key"], "\n"; }' storage/logs/playtest/default.shadow-hangar-1-<zeit>.json
```

K22 und auffällige Abweichungen als Tabelle in Spec-Abschnitt „10. Messung S2“ eintragen (Median K22 je Eröffnung, drei typische Abweichungen mit Sol). Ist K22 > 60 %: dem Owner vor S3 melden (Bewertung weicht stark von Legacy ab — gewollt, aber prüfen, ob ein Term dominiert).

- [ ] **Step 4: CHANGELOG**

```markdown
- Feat: PlaytestBot Utility-Modell S2 — Bewertungsmodell (Preise, Diskont, Phasen-/Stufen-/Freischaltterme, Gewichte in `tests/Feature/Playtest/Utility/weights.php`), Schattenmodus `--engine=shadow` (K22) und λ_AP-Verlauf (K21). Legacy-Verhalten unverändert.
```

- [ ] **Step 5: Commit, Push, PR**

```bash
git add CHANGELOG.md
git add docs/superpowers/specs/2026-10-12-playtestbot-utility-modell.md   # nur wenn in Step 3 geändert
git commit -m "docs: CHANGELOG PlaytestBot Utility S2"
git push origin feat/bot-utility-s2
gh pr create --base master --head feat/bot-utility-s2 --title "PlaytestBot Utility-Modell S2: Bewertung, Schattenmodus, λ_AP" --body "Plan Tasks 10-15. Setzt PR S0+S1 voraus (bei offenem Vorgänger-PR erst nach dessen Merge rebasen). Legacy unverändert (Golden-Tests inkl. shadow). Playtest-Suite lokal grün."
```

PR-Body um die Attribution der ausführenden Session ergänzen. Nicht mergen.

- [ ] **Step 6: Branch für S3**

```bash
git checkout -b feat/bot-utility-s3
```

---
## Task 16 (S3): Sparziel (`HeuristicGoalProvider`) und Opportunitätskosten

**Files:**
- Create: `tests/Feature/Playtest/Utility/SavingsGoal.php`, `tests/Feature/Playtest/Utility/HeuristicGoalProvider.php`
- Modify: `tests/Feature/Playtest/Utility/BotContext.php` (Feld `goal`, `capture(..., bool $withGoal = false)`, `opportunityCost()`, `regolithHold()`)
- Test: `tests/Feature/Playtest/Utility/HeuristicGoalProviderTest.php` (neu)

**Interfaces:**
- Consumes: `Valuers::cc/pathPlacement/upgrade/ccRow/upgradeRows`, `BotStrategy::agrardomPlaced/levelupRegolith/supplyGated/ccLevel/pathBuildingOrder`.
- Produces: `SavingsGoal(string $kind /* cc|path|upgrade */, string $key, int $regolithCost, float $net)`, `ratio(): float`; `HeuristicGoalProvider::goal(BotSession, BotContext): ?SavingsGoal`; `BotContext::$goal` (`?SavingsGoal`, öffentlich schreibbar), `BotContext::capture(BotSession, BotProfile, bool $withGoal = false)`, `opportunityCost(int $regolithCost, string $key): float`, `regolithHold(): int`. Schlüsselformat (verbindlich für Tasks 17/18): CC `cc:25:{zielstufe}`, Ausbau `invest:{building_id}:{instance_id}`, Platzierung `place:{building_id}`.

- [ ] **Step 1: Failing test schreiben**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\TestCase;

/** Spec 2.6 + T30 §14.4: one savings goal replaces the binary Regolith buffers. */
class HeuristicGoalProviderTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    private function phase2WithCc3(int $regolith): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->setPhase($bot, 2);
        $this->setCc($bot, 3);
        $this->placeAt($bot, 41, 1);
        $this->placeAt($bot, BuildingId::Sciencelab->value, 2);
        $this->placeAt($bot, BuildingId::Hangar->value, 2);
        $this->setResource($bot, 3, $regolith);
        $this->setSupply($bot, 1000);

        return $bot;
    }

    private function ctx(BotSession $bot, string $opening = 'auto'): BotContext
    {
        return BotContext::capture($bot, BotProfile::named('default')->withOpening($opening), withGoal: true);
    }

    public function test_an_unaffordable_cc_level_becomes_the_goal_in_phase_2(): void
    {
        $bot = $this->phase2WithCc3(50);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }

        $ctx = $this->ctx($bot);

        $this->assertSame(['cc', 'cc:25:4', 120], [$ctx->goal?->kind, $ctx->goal?->key, $ctx->goal?->regolithCost]);
        $this->assertSame(120, $ctx->regolithHold());
    }

    public function test_a_goal_out_of_reach_within_five_sols_is_dropped(): void
    {
        $bot = $this->phase2WithCc3(0);   // no sol_next yet: income floor 8 -> CC Lv4 (120 Rg) takes 15 Sols

        $ctx = $this->ctx($bot);

        $this->assertNotSame('cc:25:4', $ctx->goal?->key, 'CC Lv4 is out of reach');
        if ($ctx->goal !== null) {
            $this->assertLessThanOrEqual(5.0, $ctx->goal->regolithCost / 8, 'any remaining goal must be reachable within 5 Sols');
            $this->assertSame($ctx->goal->regolithCost, $ctx->regolithHold());
        }
    }

    public function test_without_the_flag_no_goal_is_computed(): void
    {
        $bot = $this->phase2WithCc3(50);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }

        $this->assertNull(BotContext::capture($bot, BotProfile::named('default'))->goal);
    }

    public function test_opportunity_cost_spares_the_goal_and_regolith_free_steps(): void
    {
        $bot = $this->phase2WithCc3(50);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }
        $ctx = $this->ctx($bot);

        $this->assertSame(0.0, $ctx->opportunityCost(120, 'cc:25:4'));
        $this->assertSame(0.0, $ctx->opportunityCost(0, 'invest:44:1'));
        $this->assertGreaterThan(0.0, $ctx->opportunityCost(25, 'invest:44:1'));
        $this->assertGreaterThan($ctx->opportunityCost(25, 'invest:44:1'), $ctx->opportunityCost(50, 'invest:31:1'));
    }

    public function test_a_pending_path_building_is_a_goal_without_a_hold(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->setCc($bot, 2);
        $this->placeAt($bot, 41, 1);
        foreach ([31, 44, 52] as $pathId) {
            \Illuminate\Support\Facades\DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', $pathId)->delete();
        }
        $this->setResource($bot, 3, 60);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }

        $ctx = $this->ctx($bot, 'hangar');

        $this->assertSame(['path', 'place:44'], [$ctx->goal?->kind, $ctx->goal?->key]);
        $this->assertSame(0, $ctx->regolithHold(), 'legacy place_building keeps its own buffer for path buildings');
    }
}
```

- [ ] **Step 2: Test rot bestätigen**

Run: `bin/phpunit tests/Feature/Playtest/Utility/HeuristicGoalProviderTest.php`
Expected: ERROR — `Unknown named parameter $withGoal`.

- [ ] **Step 3: `SavingsGoal` anlegen**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

/** Spec 2.6: the one Regolith target the bot is saving for. */
final readonly class SavingsGoal
{
    public function __construct(
        public string $kind,
        public string $key,
        public int $regolithCost,
        public float $net,
    ) {}

    public function ratio(): float
    {
        return $this->net / max(1, $this->regolithCost);
    }
}
```

- [ ] **Step 4: `HeuristicGoalProvider` anlegen**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use App\Services\ResourcesService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;

/**
 * Spec 2.6 (v1 heuristic; Option B replaces only this class): among the steps
 * that fail on Regolith alone and are worth doing (net > min_net), the goal is
 * the one with the best net per Regolith — dropped when the missing Regolith
 * takes longer than goal_max_wait_sols at the current income (T30 §14.4).
 */
final class HeuristicGoalProvider
{
    public static function goal(BotSession $b, BotContext $ctx): ?SavingsGoal
    {
        $options = [];

        $cc = Valuers::ccRow($b);
        if ($cc !== null && ($cc->max_level === null || (int) $cc->level < (int) $cc->max_level)
            && ((int) $cc->level !== 1 || BotStrategy::agrardomPlaced($b))
            && ! ($ctx->phase === 1 && (int) $cc->level >= 3)) {
            $rg = BotStrategy::levelupRegolith($cc);
            if ($rg > $ctx->regolith) {
                $options[] = new SavingsGoal('cc', 'cc:25:'.((int) $cc->level + 1), $rg, Valuers::cc($b, $ctx, $cc)->net());
            }
        }

        $path = self::nextPathBuilding($b, $ctx);
        if ($path !== null) {
            $e = Valuers::pathPlacement($b, $ctx, $path);
            if ($e->regolithCost > $ctx->regolith) {
                $options[] = new SavingsGoal('path', 'place:'.$path, $e->regolithCost, $e->net());
            }
        }

        $freeSupply = app(ResourcesService::class)->getFreeSupply($b->colonyId);
        foreach (Valuers::upgradeRows($b) as $row) {
            $rg = BotStrategy::levelupRegolith($row);
            $supplyBlocked = ! ResourcesService::reservesFirstLevel($row) && BotStrategy::supplyGated($row) && $freeSupply < (int) $row->supply_cost;
            if ($rg > $ctx->regolith && ! $supplyBlocked) {
                $options[] = new SavingsGoal('upgrade', 'invest:'.$row->building_id.':'.$row->instance_id, $rg, Valuers::upgrade($b, $ctx, $row)->net());
            }
        }

        $minNet = (float) $ctx->weights['min_net'];
        $maxWait = (float) $ctx->weights['goal_max_wait_sols'];
        $options = array_values(array_filter($options, fn (SavingsGoal $g): bool => $g->net > $minNet
            && ($g->regolithCost - $ctx->regolith) / max(1.0, $ctx->incomeRg) <= $maxWait));
        usort($options, fn (SavingsGoal $x, SavingsGoal $y): int => [-(int) round($x->ratio() * 1000), $x->key] <=> [-(int) round($y->ratio() * 1000), $y->key]);

        return $options[0] ?? null;
    }

    /** Next unplaced path building of the opening's order, if the CC allows one more (placed < CC level − 1) and the Agrardom stands. */
    private static function nextPathBuilding(BotSession $b, BotContext $ctx): ?int
    {
        if (! BotStrategy::agrardomPlaced($b)) {
            return null;
        }
        $placed = DB::table('colony_buildings')->where('colony_id', $b->colonyId)->whereIn('building_id', Valuers::PATH_IDS)
            ->whereNotNull('tile_x')->orderBy('building_id')->pluck('building_id')->map(fn ($id): int => (int) $id)->all();
        if (count($placed) >= BotStrategy::ccLevel($b) - 1) {
            return null;
        }
        foreach (BotStrategy::pathBuildingOrder($ctx->opening) as $id) {
            if (! in_array($id, $placed, true)) {
                return $id;
            }
        }

        return null;
    }
}
```

- [ ] **Step 5: `BotContext` erweitern**

Feld nach dem Konstruktor ergänzen:

```php
    /** Savings goal of this decision (S3, engine utility only). */
    public ?SavingsGoal $goal = null;
```

Signatur `capture(BotSession $b, BotProfile $profile): self` → `capture(BotSession $b, BotProfile $profile, bool $withGoal = false): self`; `return new self(...);` ersetzen durch `$ctx = new self(...);` und danach:

```php
        if ($withGoal) {
            $ctx->goal = HeuristicGoalProvider::goal($b, $ctx);
        }

        return $ctx;
```

Neue Methoden:

```php
    /** Spec 2.6: delaying the goal by rg / income Sols costs Net(G) · (1 − γ^Δt); 0 for the goal itself and Regolith-free steps. */
    public function opportunityCost(int $regolithCost, string $key): float
    {
        if ($this->goal === null || $regolithCost <= 0 || $key === $this->goal->key) {
            return 0.0;
        }
        $delay = $regolithCost / max(1.0, $this->incomeRg);

        return $this->goal->net * (1 - (float) $this->weights['gamma'] ** $delay);
    }

    /**
     * Regolith the legacy spenders must leave in stock (BotStrategy::regolithReserve()).
     * A path building goal holds nothing: legacy place_building keeps its own buffer.
     */
    public function regolithHold(): int
    {
        return $this->goal !== null && in_array($this->goal->kind, ['cc', 'upgrade'], true) ? $this->goal->regolithCost : 0;
    }
```

- [ ] **Step 6: Tests grün**

Run: `bin/phpunit tests/Feature/Playtest/Utility/HeuristicGoalProviderTest.php tests/Feature/Playtest/Utility/BotContextTest.php`
Expected: PASS. Fällt `test_an_unaffordable_cc_level_becomes_the_goal_in_phase_2` auf ein anderes Ziel, die Optionen mit `dump()` ausgeben: Ein Ausbau-Ziel mit höherem Net/Rg ist laut Spec zulässig — dann stimmt die Fixture nicht (Ausbauten müssen bezahlbar sein, Rg 50 ≥ 25), nicht der Provider.

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/Playtest/Utility/SavingsGoal.php tests/Feature/Playtest/Utility/HeuristicGoalProvider.php tests/Feature/Playtest/Utility/BotContext.php tests/Feature/Playtest/Utility/HeuristicGoalProviderTest.php
git commit -m "test(playtest): Sparziel und Opportunitätskosten statt Regolith-Puffer (Utility S3, U2/U4)"
```

---

## Task 17 (S3): `UpgradeSource` — Ausbau ohne Lv2-Deckel (U1, U2, U3)

**Files:**
- Create: `tests/Feature/Playtest/Utility/Sources/UpgradeSource.php`
- Test: `tests/Feature/Playtest/Utility/UpgradeSourceTest.php` (neu)

**Interfaces:**
- Consumes: `Valuers::upgrade/upgradeRows`, `BotContext::opportunityCost`, `BotStrategy::levelupRegolith/supplyGated`.
- Produces: `Sources\UpgradeSource(int $band)` — `name()` = `invest_production`; Kandidaten `invest_production` mit Schlüssel `invest:{id}:{instance}` für jedes platzierte Nicht-CC/Nicht-Harvester-Gebäude unter `max_level`, bezahlbar (Rg-Fälligkeit ≤ Bestand, AP ≥ 1) und nicht am Supply blockiert; Terme wie `Valuers::upgrade` plus `opportunity` (negativ) und `enable` (nur die niedrigste Wohnhabitat-Stufe: bestes Netto eines nur am Supply scheiternden Ausbaus). `do` = derselbe Request wie Legacy-`invest_production`.

- [ ] **Step 1: Failing test schreiben**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\Utility\Sources\UpgradeSource;
use Tests\TestCase;

/** T30 §14 U1/U3, spec P6: relative expectations, no fixed scores. */
class UpgradeSourceTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    private function pick(BotSession $bot, string $opening = 'auto'): ?Candidate
    {
        $ctx = BotContext::capture($bot, BotProfile::named('default')->withOpening($opening), withGoal: true);

        return UtilitySelector::select((new UpgradeSource(17))->candidates($bot, $ctx), [])?->chosen;
    }

    /** @return list<string> */
    private function keys(BotSession $bot): array
    {
        $ctx = BotContext::capture($bot, BotProfile::named('default'), withGoal: true);

        return array_map(fn (Candidate $c) => $c->key, (new UpgradeSource(17))->candidates($bot, $ctx));
    }

    private function boot(int $regolith): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->setResource($bot, 3, $regolith);
        $this->setSupply($bot, 1000);
        $this->giveAp($bot, 15);

        return $bot;
    }

    public function test_own_path_building_goes_to_level_2_before_housing_and_agrardom(): void
    {
        $bot = $this->boot(60);
        $this->placeAt($bot, BuildingId::Hangar->value, 1);
        $this->placeAt($bot, BuildingId::Housing->value, 1);
        $this->placeAt($bot, 41, 1);
        $this->makeFind($bot, 'find_large');

        $this->assertSame('invest:44:1', $this->pick($bot, 'hangar')?->key);
    }

    public function test_path_buildings_level_beyond_2_in_phase_2(): void
    {
        $bot = $this->boot(100);
        $this->setPhase($bot, 2);
        $this->placeAt($bot, BuildingId::Hangar->value, 2);

        $this->assertContains('invest:44:1', $this->keys($bot));
        $this->assertGreaterThan(0.0, $this->pick($bot)?->net);
    }

    public function test_a_supply_blocked_target_gives_housing_its_enabling_value(): void
    {
        $bot = $this->boot(100);
        $this->placeAt($bot, BuildingId::Sciencelab->value, 1);
        $this->placeAt($bot, BuildingId::Housing->value, 1);
        $this->setSupply($bot, 0);

        $chosen = $this->pick($bot, 'labor');

        $this->assertSame('invest:28:1', $chosen?->key);
        $this->assertGreaterThan(0.0, $chosen->terms['enable'] ?? 0.0);
        $this->assertNotContains('invest:31:1', $this->keys($bot));
    }

    public function test_a_running_cycle_beats_an_otherwise_equal_new_one(): void
    {
        $bot = $this->boot(100);
        $this->setPhase($bot, 2);
        $this->placeAt($bot, BuildingId::Hangar->value, 1, apSpend: 5);
        $this->placeAt($bot, BuildingId::Sciencelab->value, 1);
        $this->setKnowledge($bot, 92, 1);   // geology known: no lab unlock term

        $this->assertSame('invest:44:1', $this->pick($bot)?->key);
    }

    public function test_unaffordable_steps_are_left_to_the_savings_goal(): void
    {
        $bot = $this->boot(10);
        $this->placeAt($bot, BuildingId::Hangar->value, 1);

        $this->assertNotContains('invest:44:1', $this->keys($bot), 'Hangar Lv2 needs 25 Rg');
    }

    public function test_a_non_goal_step_pays_opportunity_cost_while_a_goal_is_open(): void
    {
        $bot = $this->boot(50);
        $this->setPhase($bot, 2);
        $this->setCc($bot, 3);
        $this->placeAt($bot, 41, 1);
        $this->placeAt($bot, BuildingId::Hangar->value, 2);
        foreach ([26, 26, 26] as $gain) {
            $this->logSolNext($bot, $gain);
        }

        $ctx = BotContext::capture($bot, BotProfile::named('default'), withGoal: true);
        $hangar = collect((new UpgradeSource(17))->candidates($bot, $ctx))->firstWhere('key', 'invest:44:1');

        $this->assertNotNull($ctx->goal, 'fixture must open a savings goal');
        $this->assertNotNull($hangar);
        $this->assertLessThan(0.0, $hangar->terms['opportunity'] ?? 0.0);
    }
}
```

- [ ] **Step 2: Test rot bestätigen**

Run: `bin/phpunit tests/Feature/Playtest/Utility/UpgradeSourceTest.php`
Expected: ERROR — `Class "...Sources\UpgradeSource" not found`.

- [ ] **Step 3: `UpgradeSource` anlegen**

```php
<?php

namespace Tests\Feature\Playtest\Utility\Sources;

use App\Enums\BuildingId;
use App\Services\ResourcesService;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\Utility\BotContext;
use Tests\Feature\Playtest\Utility\Candidate;
use Tests\Feature\Playtest\Utility\CandidateSource;
use Tests\Feature\Playtest\Utility\Valuers;

/**
 * S3 (T30 §14 U1-U3): every level-up of a placed building up to max_level — no
 * Lv2 cap, no building-id tie-break, no path buffer. Regolith-blocked steps are
 * the goal provider's job; supply-blocked steps lend their value to housing.
 * Replaces invest_production and focus_engineering_levelup in the utility engine.
 */
final class UpgradeSource implements CandidateSource
{
    public function __construct(private readonly int $band) {}

    public function name(): string
    {
        return 'invest_production';
    }

    public function band(): int
    {
        return $this->band;
    }

    public function candidates(BotSession $b, BotContext $ctx): array
    {
        if ($ctx->apAvailable < 1) {
            return [];
        }

        $freeSupply = app(ResourcesService::class)->getFreeSupply($b->colonyId);
        $open = [];
        $blockedBest = 0.0;
        foreach (Valuers::upgradeRows($b) as $row) {
            $regolith = BotStrategy::levelupRegolith($row);
            if ($regolith > $ctx->regolith) {
                continue;
            }
            $key = 'invest:'.$row->building_id.':'.$row->instance_id;
            $estimate = Valuers::upgrade($b, $ctx, $row);
            $opportunity = $ctx->opportunityCost($regolith, $key);
            if ($opportunity > 0.0) {
                $estimate = $estimate->withTerm('opportunity', -$opportunity);
            }
            if (! ResourcesService::reservesFirstLevel($row) && BotStrategy::supplyGated($row) && $freeSupply < (int) $row->supply_cost) {
                $blockedBest = max($blockedBest, $estimate->net());

                continue;
            }
            $open[] = [$row, $key, $estimate];
        }

        // The lowest housing level gets the enabling value (spec 2.3 Wohnhabitat).
        usort($open, fn (array $x, array $y): int => [(int) $x[0]->level, $x[1]] <=> [(int) $y[0]->level, $y[1]]);
        $enabled = false;
        $candidates = [];
        foreach ($open as [$row, $key, $estimate]) {
            if (! $enabled && $blockedBest > 0.0 && (int) $row->building_id === BuildingId::Housing->value) {
                $estimate = $estimate->withTerm('enable', $blockedBest);
                $enabled = true;
            }
            $buildingId = (int) $row->building_id;
            $instanceId = (int) $row->instance_id;
            $candidates[] = new Candidate(
                name: 'invest_production',
                key: $key,
                band: $this->band,
                net: $estimate->net(),
                apCost: $estimate->apCost,
                do: fn (BotSession $s): array => $s->act('invest_production', 'POST', '/colony/building/invest', [
                    'building_id' => $buildingId,
                    'instance_id' => $instanceId,
                ]),
                terms: $estimate->terms,
                regolithCost: $estimate->regolithCost,
            );
        }

        return $candidates;
    }
}
```

- [ ] **Step 4: Tests grün**

Run: `bin/phpunit tests/Feature/Playtest/Utility/UpgradeSourceTest.php`
Expected: PASS. Schlägt `test_a_running_cycle_beats_an_otherwise_equal_new_one` fehl, die Terme beider Kandidaten ausgeben: die Bar-/Labor-Terme dürfen den Hangar nur über Werte übertreffen, die der Test nicht neutralisiert hat — dann die Fixture präzisieren (nicht die Gewichte).

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Playtest/Utility/Sources/UpgradeSource.php tests/Feature/Playtest/Utility/UpgradeSourceTest.php
git commit -m "test(playtest): UpgradeSource — Ausbau bis max_level nach Nutzen (Utility S3, U1/U3)"
```

---
## Task 18 (S3): `CcSource` — CC-Ausbau nur, wenn bezahlbar (U4)

**Files:**
- Create: `tests/Feature/Playtest/Utility/Sources/CcSource.php`
- Test: `tests/Feature/Playtest/Utility/CcSourceTest.php` (neu)

**Interfaces:**
- Consumes: `Valuers::cc/ccRow`, `BotContext::opportunityCost`, `BotStrategy::agrardomPlaced/levelupRegolith`.
- Produces: `Sources\CcSource(int $band)` — `name()` = `invest_cc`; höchstens ein Kandidat `invest_cc`, Schlüssel `cc:25:{zielstufe}`; Filter: AP ≥ 1, Stufe < `max_level`, C16 (Stufe 1 → 2 erst mit Agrardom), Phase 1 höchstens bis Stufe 3 (Legacy-Pacing-Deckel bleibt in v1, Annahme), Rg-Fälligkeit ≤ Bestand. `do` = derselbe Request wie Legacy-`invest_cc`.

- [ ] **Step 1: Failing test schreiben**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\Utility\Sources\CcSource;
use Tests\TestCase;

/** T30 §14 U4: an unaffordable CC level is never attempted (no resource_limit loop) — it becomes the goal. */
class CcSourceTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    /** @return list<Candidate> */
    private function candidates(BotSession $bot): array
    {
        return (new CcSource(6))->candidates($bot, BotContext::capture($bot, BotProfile::named('default'), withGoal: true));
    }

    private function boot(int $cc, int $regolith, int $phase = 1, bool $agrardom = true): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->setCc($bot, $cc);
        $this->setPhase($bot, $phase);
        $this->setResource($bot, 3, $regolith);
        $this->giveAp($bot, 15);
        if ($agrardom) {
            $this->placeAt($bot, 41, 1);
        }

        return $bot;
    }

    public function test_phase_1_cc_level_3_is_a_positive_candidate(): void
    {
        $candidates = $this->candidates($this->boot(2, 200));

        $this->assertSame(['cc:25:3'], array_map(fn (Candidate $c) => $c->key, $candidates));
        $this->assertGreaterThan(0.0, $candidates[0]->net);
        $this->assertSame('invest_cc', $candidates[0]->name);
    }

    public function test_cc_level_2_waits_for_the_agrardom(): void
    {
        $this->assertSame([], $this->candidates($this->boot(1, 500, agrardom: false)));
    }

    public function test_phase_1_stops_at_level_3(): void
    {
        $this->assertSame([], $this->candidates($this->boot(3, 500)));
    }

    public function test_unaffordable_level_4_gives_no_candidate(): void
    {
        $this->assertSame([], $this->candidates($this->boot(3, 50, phase: 2)));
    }

    public function test_affordable_level_4_in_phase_2_is_a_candidate(): void
    {
        $candidates = $this->candidates($this->boot(3, 200, phase: 2));

        $this->assertSame(['cc:25:4'], array_map(fn (Candidate $c) => $c->key, $candidates));
        $this->assertGreaterThan(0.0, $candidates[0]->net);
    }
}
```

- [ ] **Step 2: Test rot bestätigen**

Run: `bin/phpunit tests/Feature/Playtest/Utility/CcSourceTest.php`
Expected: ERROR — `Class "...Sources\CcSource" not found`.

- [ ] **Step 3: `CcSource` anlegen**

```php
<?php

namespace Tests\Feature\Playtest\Utility\Sources;

use App\Enums\BuildingId;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\Utility\BotContext;
use Tests\Feature\Playtest\Utility\Candidate;
use Tests\Feature\Playtest\Utility\CandidateSource;
use Tests\Feature\Playtest\Utility\Valuers;

/** S3 (T30 §14 U4): the next CC level, offered only when affordable; otherwise HeuristicGoalProvider saves for it. */
final class CcSource implements CandidateSource
{
    public function __construct(private readonly int $band) {}

    public function name(): string
    {
        return 'invest_cc';
    }

    public function band(): int
    {
        return $this->band;
    }

    public function candidates(BotSession $b, BotContext $ctx): array
    {
        $cc = Valuers::ccRow($b);
        if ($cc === null || $ctx->apAvailable < 1) {
            return [];
        }
        $level = (int) $cc->level;
        if (($cc->max_level !== null && $level >= (int) $cc->max_level)
            || ($level === 1 && ! BotStrategy::agrardomPlaced($b))
            || ($ctx->phase === 1 && $level >= 3)) {
            return [];
        }
        $regolith = BotStrategy::levelupRegolith($cc);
        if ($regolith > $ctx->regolith) {
            return [];
        }

        $key = 'cc:25:'.($level + 1);
        $estimate = Valuers::cc($b, $ctx, $cc);
        $opportunity = $ctx->opportunityCost($regolith, $key);
        if ($opportunity > 0.0) {
            $estimate = $estimate->withTerm('opportunity', -$opportunity);
        }

        return [new Candidate(
            name: 'invest_cc',
            key: $key,
            band: $this->band,
            net: $estimate->net(),
            apCost: $estimate->apCost,
            do: fn (BotSession $s): array => $s->act('invest_cc', 'POST', '/colony/building/invest', [
                'building_id' => BuildingId::CommandCenter->value,
            ]),
            terms: $estimate->terms,
            regolithCost: $estimate->regolithCost,
        )];
    }
}
```

- [ ] **Step 4: Tests grün**

Run: `bin/phpunit tests/Feature/Playtest/Utility/CcSourceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Playtest/Utility/Sources/CcSource.php tests/Feature/Playtest/Utility/CcSourceTest.php
git commit -m "test(playtest): CcSource — CC-Ausbau nur bezahlbar, sonst Sparziel (Utility S3, U4)"
```

---

## Task 19 (S3): `ResearchSource` — Forschung nach Effekt je AP (U6)

**Files:**
- Modify: `tests/Feature/Playtest/BotStrategy.php:1444` (`researchLevelupReady` → `public`), `:1457-1514` (Refactoring `researchOptions()` → neue öffentliche Methode `researchableKnowledge()`)
- Create: `tests/Feature/Playtest/Utility/Sources/ResearchSource.php`
- Test: `tests/Feature/Playtest/Utility/ResearchSourceTest.php` (neu)

**Interfaces:**
- Consumes: `Valuers::research`.
- Produces: `BotStrategy::researchableKnowledge(BotSession): array{eligible: list<object{id:int, level:int, ap_spend:int}>, cc_blocked: bool}` (Reihenfolge nach ID, dieselben Filter wie bisher: Laborstufe, < 5, CC-Gate); `Sources\ResearchSource(int $band)` — `name()` = `research_knowledge`; ein Kandidat je erforschbarer Kenntnis, Schlüssel `research:{id}`; `do` wie Legacy (`levelup`, wenn bereit, sonst `add` 1 AP).

- [ ] **Step 1: Failing test schreiben**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use App\Services\Techtree\ResearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\Utility\Sources\ResearchSource;
use Tests\TestCase;

/** T30 §14 U6: research by effect per AP, not by lowest id. */
class ResearchSourceTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    /** @return list<Candidate> */
    private function candidates(BotSession $bot): array
    {
        return (new ResearchSource(19))->candidates($bot, BotContext::capture($bot, BotProfile::named('default'), withGoal: true));
    }

    private function bootWithLab(int $labLevel): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->placeAt($bot, BuildingId::Sciencelab->value, $labLevel);
        $this->giveAp($bot, 15);

        return $bot;
    }

    public function test_geology_is_researched_before_construction(): void
    {
        $bot = $this->bootWithLab(2);
        $keys = array_map(fn (Candidate $c) => $c->key, $this->candidates($bot));
        $this->assertContains('research:92', $keys, 'fixture: geology must be researchable at lab Lv2');
        $this->assertContains('research:90', $keys);

        $this->assertSame('research:92', UtilitySelector::select($this->candidates($bot), [])->chosen->key);
    }

    public function test_cc_gated_levels_are_not_offered(): void
    {
        $bot = $this->bootWithLab(5);
        $this->setCc($bot, 3);
        foreach (range(90, 96) as $id) {
            $this->setKnowledge($bot, $id, $id === 90 ? 2 : 3);
        }

        $this->assertSame(['research:90'], array_map(fn (Candidate $c) => $c->key, $this->candidates($bot)));
    }

    public function test_do_levels_up_when_ready_and_adds_otherwise(): void
    {
        $bot = $this->bootWithLab(2);
        $cost = app(ResearchService::class)->knowledgeLevelupCost($bot->colonyId, 92, (int) DB::table('researches')->where('id', 92)->value('ap_for_levelup'));
        $this->setKnowledge($bot, 92, 0, $cost);
        $geology = collect($this->candidates($bot))->firstWhere('key', 'research:92');

        $this->assertTrue(($geology->do)($bot)['ok']);
        $this->assertSame(1, (int) DB::table('colony_researches')->where('colony_id', $bot->colonyId)->where('research_id', 92)->value('level'));
    }

    public function test_research_options_keep_the_legacy_candidate(): void
    {
        $bot = $this->bootWithLab(2);

        $this->assertSame(90, \Tests\Feature\Playtest\BotStrategy::researchableKnowledge($bot)['eligible'][0]->id);
    }
}
```

- [ ] **Step 2: Test rot bestätigen**

Run: `bin/phpunit tests/Feature/Playtest/Utility/ResearchSourceTest.php`
Expected: ERROR — `Class "...Sources\ResearchSource" not found`.

- [ ] **Step 3: `researchOptions()` refaktorieren (ohne Verhaltensänderung)**

`BotStrategy.php` Zeile 1444 `private static function researchLevelupReady` → `public static function researchLevelupReady`.
Die Methode `researchOptions()` samt Docblock (Zeilen 1457–1514) ersetzen durch:

```php
    /**
     * Knowledge research could take right now, lowest id first: Sciencelab level
     * met, below level 5, and not held back by the CC gate
     * (game.knowledge_cc_level_cap). cc_blocked = at least one knowledge is held
     * back only by that gate. Shared with the utility ResearchSource (S3).
     *
     * A37 fix (kept): filtering by the SAME cap the server enforces lets the bot
     * move on to the next eligible knowledge instead of retrying a CC-gated one
     * forever (found 2026-09-13).
     *
     * @return array{eligible: list<object{id:int, level:int, ap_spend:int}>, cc_blocked: bool}
     */
    public static function researchableKnowledge(BotSession $b): array
    {
        $sciencelabLevel = (int) (DB::table('colony_buildings')
            ->where('colony_id', $b->colonyId)
            ->where('building_id', BuildingId::Sciencelab->value)
            ->value('level') ?? 0);
        $ccLevel = (int) (DB::table('colony_buildings')
            ->where('colony_id', $b->colonyId)
            ->where('building_id', BuildingId::CommandCenter->value)
            ->value('level') ?? 0);
        $ccCaps = config('game.knowledge_cc_level_cap', []);

        $rows = DB::table('researches as r')
            ->leftJoin('colony_researches as cr', function ($join) use ($b) {
                $join->on('cr.research_id', '=', 'r.id')->where('cr.colony_id', $b->colonyId);
            })
            ->where('r.purpose', 'knowledge')
            ->whereBetween('r.id', [90, 96])
            ->where(fn ($q) => $q->whereNull('r.required_building_level')->orWhereRaw('? >= r.required_building_level', [$sciencelabLevel]))
            ->where(fn ($q) => $q->whereNull('cr.level')->orWhere('cr.level', '<', 5))
            ->orderBy('r.id')
            ->get(['r.id', 'cr.level', 'cr.ap_spend']);

        $eligible = [];
        $ccBlocked = false;
        foreach ($rows as $row) {
            $level = (int) ($row->level ?? 0);
            $requiredCc = $ccCaps[$level + 1] ?? null;
            if ($requiredCc === null || $ccLevel >= $requiredCc) {
                $eligible[] = (object) ['id' => (int) $row->id, 'level' => $level, 'ap_spend' => (int) ($row->ap_spend ?? 0)];
            } else {
                $ccBlocked = true;
            }
        }

        return ['eligible' => $eligible, 'cc_blocked' => $ccBlocked];
    }

    /**
     * The knowledge research could take right now and whether any knowledge is held
     * back only by the CC gate. cc_blocked is only read when candidate is null
     * (ccResearchReserve()), where "any" equals the former "before the first eligible".
     *
     * @return array{candidate: int|null, cc_blocked: bool}
     */
    private static function researchOptions(BotSession $b): array
    {
        $knowledge = self::researchableKnowledge($b);

        return ['candidate' => $knowledge['eligible'][0]->id ?? null, 'cc_blocked' => $knowledge['cc_blocked']];
    }
```

- [ ] **Step 4: `ResearchSource` anlegen**

```php
<?php

namespace Tests\Feature\Playtest\Utility\Sources;

use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\Utility\BotContext;
use Tests\Feature\Playtest\Utility\Candidate;
use Tests\Feature\Playtest\Utility\CandidateSource;
use Tests\Feature\Playtest\Utility\Valuers;

/** S3 (T30 §14 U6): every researchable knowledge, valued by its next level's effect per AP. */
final class ResearchSource implements CandidateSource
{
    public function __construct(private readonly int $band) {}

    public function name(): string
    {
        return 'research_knowledge';
    }

    public function band(): int
    {
        return $this->band;
    }

    public function candidates(BotSession $b, BotContext $ctx): array
    {
        if ($ctx->apAvailable < 1) {
            return [];
        }

        $candidates = [];
        foreach (BotStrategy::researchableKnowledge($b)['eligible'] as $knowledge) {
            $id = $knowledge->id;
            $estimate = Valuers::research($b, $ctx, $id, $knowledge->level, $knowledge->ap_spend);
            $candidates[] = new Candidate(
                name: 'research_knowledge',
                key: 'research:'.$id,
                band: $this->band,
                net: $estimate->net(),
                apCost: $estimate->apCost,
                // Same request as the legacy rule: levelup only once it can succeed.
                do: fn (BotSession $s): array => BotStrategy::researchLevelupReady($s, $id)
                    ? $s->act('research_knowledge', 'POST', "/techtree/research/{$id}/order", ['order' => 'levelup'])
                    : $s->act('research_knowledge', 'POST', "/techtree/research/{$id}/order", ['order' => 'add', 'ap' => 1]),
                terms: $estimate->terms,
            );
        }

        return $candidates;
    }
}
```

- [ ] **Step 5: Tests grün — inkl. Legacy-Forschungstests und Golden**

Run: `bin/phpunit tests/Feature/Playtest/Utility/ResearchSourceTest.php tests/Feature/Playtest/BotStrategyResearchLevelupTest.php tests/Feature/Playtest/BotStrategyResearchBufferTest.php tests/Feature/Playtest/BotStrategyCcReserveTest.php tests/Feature/Playtest/Utility/AdapterGoldenTest.php`
Expected: PASS. Ist `test_cc_gated_levels_are_not_offered` rot, weil eine Kenntnis eine höhere Laborstufe verlangt: Laborstufe in der Fixture ist schon 5 — dann die `required_building_level` der Kenntnis prüfen und die Erwartung **nicht** lockern, sondern den Befund melden.

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Playtest/BotStrategy.php tests/Feature/Playtest/Utility/Sources/ResearchSource.php tests/Feature/Playtest/Utility/ResearchSourceTest.php
git commit -m "test(playtest): ResearchSource — Forschung nach Effekt je AP, Geologie vor Bauwesen (Utility S3, U6)"
```

---

## Task 20 (S3): `FreighterSource` — Frachter für wartenden großen Fund (U5)

**Files:**
- Modify: `tests/Feature/Playtest/BotStrategy.php:43` (`private const HIRE_ORDER` → `public const`), `:1900` (`hangarLevel` → `public`), `:2281` (`hasFreeBayFor` → `public`)
- Create: `tests/Feature/Playtest/Utility/Sources/FreighterSource.php`
- Test: `tests/Feature/Playtest/Utility/FreighterSourceTest.php` (neu)

**Interfaces:**
- Produces: `Sources\FreighterSource(int $band)` — `name()` = `request_ship`; Kandidat `request_ship`, Schlüssel `ship:freighter`, nur wenn: weniger als 3 Berater (ab 3 greift Legacy-`request_ship`), Hangar ≥ Stufe 2, Bucht für Klasse 2 frei, kein Frachter vorhanden, mindestens ein gescannter Fund mit `findAction`-Grund `needs_freighter`, Credits − Frachterpreis ≥ Preis der nächsten Einstellung (`HIRE_ORDER`). Netto = Σ wartende Funde (rg × yield_factor) − Frachterpreis × Credits-Preis.

- [ ] **Step 1: Failing test schreiben**

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use App\Services\HangarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\Utility\Sources\FreighterSource;
use Tests\TestCase;

/** T30 §14 U5: buy the freighter for a waiting large find even with fewer than 3 advisors. */
class FreighterSourceTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    private function bootHangar2(int $credits): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->placeAt($bot, BuildingId::Hangar->value, 2);
        app(HangarService::class)->ensureGiftDrone($bot->colonyId);
        $this->setCredits($bot, $credits);
        $this->giveAp($bot, 10);

        return $bot;
    }

    /** @return list<Candidate> */
    private function candidates(BotSession $bot): array
    {
        return (new FreighterSource(25))->candidates($bot, BotContext::capture($bot, BotProfile::named('default'), withGoal: true));
    }

    public function test_a_waiting_large_find_makes_the_freighter_a_candidate(): void
    {
        $bot = $this->bootHangar2(5000);
        $this->makeFind($bot, 'find_large');

        $candidates = $this->candidates($bot);

        $this->assertSame(['ship:freighter'], array_map(fn (Candidate $c) => $c->key, $candidates));
        $this->assertSame('request_ship', $candidates[0]->name);
        $this->assertGreaterThan(0.0, $candidates[0]->net);
    }

    public function test_no_waiting_find_no_freighter(): void
    {
        $this->assertSame([], $this->candidates($this->bootHangar2(5000)));
    }

    public function test_the_next_hire_must_stay_affordable(): void
    {
        $bot = $this->bootHangar2((int) config('ships.freighter.nexus_cost'));
        $this->makeFind($bot, 'find_large');

        $this->assertSame([], $this->candidates($bot));
    }
}
```

- [ ] **Step 2: Test rot bestätigen**

Run: `bin/phpunit tests/Feature/Playtest/Utility/FreighterSourceTest.php`
Expected: ERROR — `Class "...Sources\FreighterSource" not found`.

- [ ] **Step 3: Sichtbarkeit und Quelle**

`BotStrategy.php`: Zeile 43 `private const HIRE_ORDER` → `public const HIRE_ORDER`; Zeile 1900 `private static function hangarLevel` → `public static function hangarLevel`; Zeile 2281 `private static function hasFreeBayFor` → `public static function hasFreeBayFor`.

`tests/Feature/Playtest/Utility/Sources/FreighterSource.php`:

```php
<?php

namespace Tests\Feature\Playtest\Utility\Sources;

use App\Services\ColonyTileService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\Utility\BotContext;
use Tests\Feature\Playtest\Utility\Candidate;
use Tests\Feature\Playtest\Utility\CandidateSource;

/**
 * S3 (T30 §14 U5 / §14.4 Frachter-Ausnahme): the freighter for a scanned large
 * find waiting on needs_freighter, before the third advisor. From 3 advisors on
 * the legacy request_ship rule (same band) keeps buying ships.
 */
final class FreighterSource implements CandidateSource
{
    public function __construct(private readonly int $band) {}

    public function name(): string
    {
        return 'request_ship';
    }

    public function band(): int
    {
        return $this->band;
    }

    public function candidates(BotSession $b, BotContext $ctx): array
    {
        $freighterId = (int) config('ships.freighter.id');
        $cost = (int) config('ships.freighter.nexus_cost', 0);
        if ($ctx->advisors >= 3 || BotStrategy::hangarLevel($b) < 2 || ! BotStrategy::hasFreeBayFor($b, 2)
            || DB::table('colony_ships')->where('colony_id', $b->colonyId)->where('ship_id', $freighterId)->exists()
            || $ctx->credits - $cost < self::nextHireCost($b)) {
            return [];
        }

        $service = app(ColonyTileService::class);
        $types = config('game.finds.types');
        $value = 0.0;
        $tiles = DB::table('colony_tiles')->where('colony_id', $b->colonyId)->where('is_deep_scanned', 1)
            ->whereIn('event_type', ColonyTileService::FIND_TYPES)->orderBy('ring')->orderBy('q')->orderBy('r')->get();
        foreach ($tiles as $tile) {
            if ($service->findAction($b->colonyId, $tile)['reason'] === 'needs_freighter') {
                $value += (int) $types[$tile->event_type]['rg'] * (float) config('game.finds.flight.yield_factor');
            }
        }
        if ($value <= 0.0) {
            return [];
        }

        $terms = ['finds' => $value, 'cost_credits' => -$cost * $ctx->price('credits')];

        return [new Candidate(
            name: 'request_ship',
            key: 'ship:freighter',
            band: $this->band,
            net: (float) array_sum($terms),
            apCost: 1,
            do: fn (BotSession $s): array => $s->act('request_ship', 'POST', '/colony/hangar/request', ['ship_id' => $freighterId]),
            terms: $terms,
        )];
    }

    /** Credits of the next not yet hired advisor in BotStrategy::HIRE_ORDER, 0 if none is left. */
    private static function nextHireCost(BotSession $b): int
    {
        $hired = DB::table('advisors')->where('colony_id', $b->colonyId)->orderBy('id')->pluck('personell_id')->map(fn ($id): int => (int) $id)->all();
        foreach (BotStrategy::HIRE_ORDER as $personellId) {
            if (! in_array($personellId, $hired, true)) {
                return (int) (collect(config('advisors'))->firstWhere('id', $personellId)['credits'] ?? 0);
            }
        }

        return 0;
    }
}
```

- [ ] **Step 4: Tests grün (inkl. Golden, weil `BotStrategy` angefasst)**

Run: `bin/phpunit tests/Feature/Playtest/Utility/FreighterSourceTest.php tests/Feature/Playtest/Utility/AdapterGoldenTest.php`
Expected: PASS. Ist der Fund in der Fixture nicht `needs_freighter` (z. B. weil die Geschenk-Drohne eine Bucht belegt und `hasFreeBayFor(2)` scheitert), `findAction()` und die Buchtbelegung ausgeben und die Fixture korrigieren.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Playtest/BotStrategy.php tests/Feature/Playtest/Utility/Sources/FreighterSource.php tests/Feature/Playtest/Utility/FreighterSourceTest.php
git commit -m "test(playtest): FreighterSource — Frachter für wartenden großen Fund vor dem 3. Berater (Utility S3, U5)"
```

---
## Task 21 (S3): Engine `utility` verdrahten, Regolith-Hold, Smoke-Test

**Files:**
- Modify: `tests/Feature/Playtest/Utility/UtilityStrategy.php` (`rules()`, neue Methode `sources()`)
- Modify: `tests/Feature/Playtest/Utility/Collector.php` (`when()`: Kontext mit Ziel und Hold im Modus `utility`)
- Modify: `tests/Feature/Playtest/BotSession.php` (Feld `regolithHold`), `tests/Feature/Playtest/BotStrategy.php:1553-1556` (`regolithReserve()`)
- Test: `tests/Unit/Playtest/UtilityStrategyCompositionTest.php`, `tests/Feature/Playtest/Utility/RegolithHoldTest.php`, `tests/Feature/Playtest/Utility/UtilityEngineSmokeTest.php` (alle neu)

**Interfaces:**
- Consumes: Tasks 16–20.
- Produces: `UtilityStrategy::sources(BotProfile): list<CandidateSource>` — Legacy-Quellen mit Ersatz am selben Band: `invest_cc` → `CcSource`, `invest_production` → `UpgradeSource`, `research_knowledge` → `ResearchSource`, `focus_engineering_levelup` → entfällt (der Ausbau trägt den Siegziel-Term), `request_ship` → `FreighterSource` + `LegacyRuleSource`. `BotSession::$regolithHold` (int, Legacy immer 0); `BotStrategy::regolithReserve()` = max(CC-Reserve, Autarkie-Reserve, `regolithHold`).

- [ ] **Step 1: Failing tests schreiben**

`tests/Unit/Playtest/UtilityStrategyCompositionTest.php`:

```php
<?php

namespace Tests\Unit\Playtest;

use PHPUnit\Framework\TestCase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\Utility\LegacyRuleSource;
use Tests\Feature\Playtest\Utility\Sources\CcSource;
use Tests\Feature\Playtest\Utility\Sources\FreighterSource;
use Tests\Feature\Playtest\Utility\Sources\ResearchSource;
use Tests\Feature\Playtest\Utility\Sources\UpgradeSource;
use Tests\Feature\Playtest\Utility\UtilityStrategy;

class UtilityStrategyCompositionTest extends TestCase
{
    /** @return array<string, int> rule name => legacy index */
    private static function bands(BotProfile $profile): array
    {
        return array_flip(array_column(BotStrategy::default($profile), 'name'));
    }

    public function test_migrated_sources_take_the_band_of_the_rule_they_replace(): void
    {
        $profile = BotProfile::named('default');
        $bands = self::bands($profile);
        $byClass = [];
        foreach (UtilityStrategy::sources($profile) as $source) {
            $byClass[$source::class][] = $source->band();
        }

        $this->assertSame([$bands['invest_cc']], $byClass[CcSource::class]);
        $this->assertSame([$bands['invest_production']], $byClass[UpgradeSource::class]);
        $this->assertSame([$bands['research_knowledge']], $byClass[ResearchSource::class]);
        $this->assertSame([$bands['request_ship']], $byClass[FreighterSource::class]);
    }

    public function test_all_other_rules_stay_legacy_sources(): void
    {
        $profile = BotProfile::named('focus');
        $legacy = array_values(array_map(fn (LegacyRuleSource $s) => $s->name(), array_filter(UtilityStrategy::sources($profile), fn ($s) => $s instanceof LegacyRuleSource)));
        $expected = array_values(array_diff(array_column(BotStrategy::default($profile), 'name'), ['invest_cc', 'invest_production', 'research_knowledge', 'focus_engineering_levelup']));

        $this->assertSame($expected, $legacy);
    }
}
```

`tests/Feature/Playtest/Utility/RegolithHoldTest.php`:

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\TestCase;

/** T30 §14 U4: while the utility engine saves for a goal, legacy Regolith spenders only use the surplus. */
class RegolithHoldTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    private function placeCandidate(BotSession $bot): mixed
    {
        return collect(BotStrategy::default(BotProfile::named('default')))->firstWhere('name', 'place_building')['when']($bot);
    }

    private function bootRich(): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->setCc($bot, 2);
        $this->placeAt($bot, 41, 1);
        $this->setResource($bot, 3, 200);
        $this->setCredits($bot, 5000);
        $this->setSupply($bot, 1000);
        $this->giveAp($bot, 15);

        return $bot;
    }

    public function test_without_a_hold_legacy_placement_is_unchanged(): void
    {
        $this->assertNotNull($this->placeCandidate($this->bootRich()));
    }

    public function test_a_hold_keeps_legacy_placements_above_the_saved_regolith(): void
    {
        $bot = $this->bootRich();
        $bot->regolithHold = 150;

        $candidate = $this->placeCandidate($bot);

        if ($candidate !== null) {
            $placementRegolith = (int) (app(\App\Services\BuildingCostService::class)->placementCost((int) $candidate[0]['building_id'])[3] ?? 0);
            $this->assertGreaterThanOrEqual(150, 200 - $placementRegolith, 'placement must not dip into the hold: '.json_encode($candidate[0]));
        }
        $this->assertTrue(true);
    }
}
```

`tests/Feature/Playtest/Utility/UtilityEngineSmokeTest.php`:

```php
<?php

namespace Tests\Feature\Playtest\Utility;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotEngine;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\PlaysSolLoop;
use Tests\TestCase;

/** Spec 4 invariants for the utility engine — no balance assertions (the bot is an instrument). */
class UtilityEngineSmokeTest extends TestCase
{
    use PlaysSolLoop;
    use RefreshDatabase;

    public function test_utility_engine_plays_twelve_sols_without_rejection_loops(): void
    {
        $profile = BotProfile::named('default')->withOpening('hangar')->withEngine('utility');
        $bot = BotSession::boot($this, 4242);

        $this->playSolsUntil($bot, BotEngine::rules($profile), self::stopAtSol(12));

        $this->assertTrue($bot->isActive(), 'run must still be active at Sol 12: '.$bot->failReason());
        $migrated = ['invest_cc', 'invest_production', 'research_knowledge'];
        $rejected = array_values(array_filter($bot->log, fn (array $e): bool => in_array($e['rule'], $migrated, true) && ! $e['ok']));
        $this->assertLessThanOrEqual(2, count($rejected), 'utility sources must only offer what the server accepts: '.json_encode($rejected));
        $this->assertNotContains('agrardom_required', array_column($bot->log, 'error'));
        $this->assertNotEmpty(array_filter($bot->decisions, fn (array $d): bool => $d['source'] === 'utility' && $d['ok']), 'migrated sources must decide');
    }
}
```

- [ ] **Step 2: Tests rot bestätigen**

Run: `bin/phpunit tests/Unit/Playtest/UtilityStrategyCompositionTest.php tests/Feature/Playtest/Utility/RegolithHoldTest.php tests/Feature/Playtest/Utility/UtilityEngineSmokeTest.php`
Expected: ERROR/FAIL — `Call to undefined method ...UtilityStrategy::sources()`, `Undefined property ...$regolithHold`, Smoke-Test ohne `utility`-Entscheidungen.

- [ ] **Step 3: `BotSession` und `BotStrategy::regolithReserve()`**

`BotSession.php` nach `lambdaBySol` einfügen:

```php

    /**
     * Regolith the utility engine is saving for (BotContext::regolithHold(), S3).
     * Legacy spenders keep it via BotStrategy::regolithReserve(); always 0 in the
     * legacy, adapter and shadow engines.
     */
    public int $regolithHold = 0;
```

`BotStrategy.php` Zeilen 1549–1556 (`regolithReserve()` samt Docblock) ersetzen durch:

```php
    /**
     * Regolith all non-repair spending must leave in stock: the largest of the CC
     * research reserve (T17), the self-sufficiency reserve and the utility
     * engine's savings goal (BotSession::$regolithHold, 0 for the legacy engine).
     */
    private static function regolithReserve(BotSession $b): int
    {
        return max(self::ccResearchReserve($b), self::selfSufficiencyReserve($b), $b->regolithHold);
    }
```

- [ ] **Step 4: `UtilityStrategy::sources()` und `rules()`**

Imports ergänzen (`Sources\CcSource`, `Sources\FreighterSource`, `Sources\ResearchSource`, `Sources\UpgradeSource`). `rules()` ersetzen und `sources()` ergänzen:

```php
    /** Engine "utility" (S3): legacy rules with the migrated sources in their bands. */
    public static function rules(BotProfile $profile): array
    {
        return [(new Collector(self::sources($profile), 'utility', $profile))->rule()];
    }

    /** @return list<CandidateSource> */
    public static function sources(BotProfile $profile): array
    {
        $sources = [];
        foreach (array_values(BotStrategy::default($profile)) as $band => $rule) {
            $replacement = match ($rule['name']) {
                'invest_cc' => [new CcSource($band)],
                'invest_production' => [new UpgradeSource($band)],
                'research_knowledge' => [new ResearchSource($band)],
                'focus_engineering_levelup' => [],
                'request_ship' => [new FreighterSource($band), new LegacyRuleSource($rule, $band)],
                default => [new LegacyRuleSource($rule, $band, in_array($rule['name'], self::MANDATORY, true))],
            };
            array_push($sources, ...$replacement);
        }

        return $sources;
    }
```

- [ ] **Step 5: `Collector::when()` — Kontext mit Ziel und Hold im Modus `utility`**

In `when()` die Kontext-Zeile ersetzen:

```php
        $shadow = $this->mode === 'shadow';
        $ctx = match ($this->mode) {
            'adapter' => new BotContext(sol: $b->sol),
            'utility' => BotContext::capture($b, $this->profile, withGoal: true),
            'shadow' => BotContext::capture($b, $this->profile),
        };
        if ($this->mode === 'utility') {
            $b->regolithHold = $ctx->regolithHold();
        }
```

- [ ] **Step 6: Tests grün**

Run: `bin/phpunit tests/Unit/Playtest tests/Feature/Playtest/Utility tests/Feature/Playtest/BotStrategyCcReserveTest.php tests/Feature/Playtest/BotStrategySelfSufficiencyReserveTest.php`
Expected: PASS — inklusive aller Golden-Tests (`adapterOnly` und `shadow` setzen keinen Hold). Fällt der Smoke-Test, zuerst die abgelehnten Einträge lesen: eine Ablehnung `supply_limit`/`resource_limit` einer migrierten Quelle heißt, ein Machbarkeitsfilter fehlt in der Quelle (Bot-Bug, nicht Gewicht) — superpowers:systematic-debugging.

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/Playtest/Utility/UtilityStrategy.php tests/Feature/Playtest/Utility/Collector.php tests/Feature/Playtest/BotSession.php tests/Feature/Playtest/BotStrategy.php tests/Unit/Playtest/UtilityStrategyCompositionTest.php tests/Feature/Playtest/Utility/RegolithHoldTest.php tests/Feature/Playtest/Utility/UtilityEngineSmokeTest.php
git commit -m "feat(playtest): Engine utility mit Ausbau-, CC-, Forschungs- und Frachterquelle, Sparziel-Hold (Utility S3)"
```

---

## Task 22: Messung M-S3, Kalibrierung des Phasenziel-Terms, Zwischenstand C

**Files:**
- Modify (nur bei Kalibrierung): `tests/Feature/Playtest/Utility/weights.php`
- Modify: `docs/superpowers/specs/2026-10-12-playtestbot-utility-modell.md` (Abschnitt „11. Messung S3“), `CHANGELOG.md`

- [ ] **Step 1: Suiten grün vor der Messung**

Run: `php artisan test --parallel --testsuite=laravel-feature,laravel-unit`, dann komplette Playtest-Suite:

```bash
nohup bin/phpunit --testsuite playtest > storage/logs/playtest-suite-C.log 2>&1 &
```

Poll mit `tail -n 5 storage/logs/playtest-suite-C.log`. Expected: beide `OK`.

- [ ] **Step 2: Laufzahl vom Owner wählen lassen**

Frage an den Owner: „Messung S3: je Engine (legacy + utility, gleiche Seeds, `--until-sol=25`) 8 Läufe (labor+hangar × Seeds 1–4, ca. 12 min je Engine), 12 Läufe (3 Eröffnungen × Seeds 1–4, ca. 15 min) oder 24 Läufe (Seeds 1–8, ca. 25 min)? Vorher bitte keine rechenintensiven Programme starten.“ Legacy-Referenz darf wiederverwendet werden, wenn `git log 2ebedf32..HEAD -- app config database` leer ist und Reports vom 2026-10-12 17:38 (Seeds 1–4) vorliegen; sonst mitlaufen lassen.

- [ ] **Step 3: Batches starten (Beispiel 12 Läufe)**

Startzeit in UTC notieren (`date -u '+%Y-%m-%d %H:%M:%S'`), dann nacheinander:

```bash
nohup php artisan game:playtest --engine=legacy --openings=labor,hangar,cantina --seeds=1,2,3,4 --until-sol=25 > storage/logs/playtest/batch-s3-legacy.log 2>&1 &
```

nach Ende (Log endet mit der Tabelle):

```bash
nohup php artisan game:playtest --engine=utility --openings=labor,hangar,cantina --seeds=1,2,3,4 --until-sol=25 > storage/logs/playtest/batch-s3-utility.log 2>&1 &
```

Nicht minutenlang pollen: einmal prüfen, Stand merken, später auswerten. Läufe mit „timed out“ in der Tabelle sind ausgefallen, nicht „nicht enthalten“ (Process-Pool-Timeout 400 s).

- [ ] **Step 4: Auswerten**

```bash
php artisan game:playtest-compare --engine=legacy --openings=labor,hangar,cantina --seeds=1,2,3,4 --since="<Startzeit UTC>"
php artisan game:playtest-compare --engine=utility --openings=labor,hangar,cantina --seeds=1,2,3,4 --since="<Startzeit UTC>"
```

Tabelle für die Spec: je Eröffnung und Engine K3, K4, K5, K16 `needs_level`, K19 Lv2/Lv3, K23 (meist „-“ bis Sol 25), K18, K20, K21, Ablehnungen (`rejections` aus den Reports), Laufzeit je Lauf (Zeitstempel der Reports bzw. Batch-Dauer / Läufe).

- [ ] **Step 5: Kalibrierung nach Entscheidungstabelle (max. 2 Runden)**

| Befund (utility, Median je Eröffnung) | Maßnahme | Danach |
|---|---|---|
| K3 > Legacy-K3 + 1 bei einer Eröffnung, oder Phase 2 in mehr als 1 Lauf nicht erreicht | `w_phase1` 40 → 60 (2. Runde 80) | Step 3–4 nur mit `--engine=utility` wiederholen |
| K3 < 15 bei allen Eröffnungen | `w_phase1` 40 → 30 | wiederholen |
| K3 im Korridor, aber K19 eigenes Lv2 > Sol 12 bei Hangar oder Cantina | `w_tier` 30 → 45 | wiederholen |
| Ablehnungen der migrierten Regeln > Legacy | kein Gewicht ändern: Machbarkeitsfilter der Quelle reparieren (Bot-Bug, TDD) | wiederholen |
| Laufzeit > 1,3 × Legacy | kein Gewicht ändern: `BotContext::capture()`/`HeuristicGoalProvider` profilieren | Owner informieren |
| alles im Rahmen | keine Änderung | weiter mit Step 6 |

Nach zwei Runden ohne Erfolg: stoppen und dem Owner Messung + Hypothese vorlegen (Memory: Bot-Bug vs. Balance-Wand unterscheiden, erst instrumentieren). Jede Gewichtsänderung einzeln committen:

```bash
git add tests/Feature/Playtest/Utility/weights.php
git commit -m "tune(playtest): w_phase1 40 -> 60 nach Messung S3 Runde 1"
```

- [ ] **Step 6: Ergebnis in die Spec, CHANGELOG**

Spec-Abschnitt „11. Messung S3“ anlegen: Datum, Laufzahl, Seeds, die Tabelle aus Step 4, die Kalibrierungsrunden mit Begründung und die finalen Gewichte. CHANGELOG (Tagesblock ergänzen):

```markdown
- Feat: PlaytestBot Utility-Modell S3 — `--engine=utility` entscheidet Ausbau (bis `max_level`), CC-Ausbau mit Sparziel, Forschung nach Effekt je AP und Frachter für wartende große Funde (T30 §14 U1–U6); Messung und Kalibrierung in Spec §11.
```

- [ ] **Step 7: Commit, Push, PR**

```bash
git add docs/superpowers/specs/2026-10-12-playtestbot-utility-modell.md CHANGELOG.md
git commit -m "docs: Messung S3 und CHANGELOG PlaytestBot Utility S3"
git push origin feat/bot-utility-s3
gh pr create --base master --head feat/bot-utility-s3 --title "PlaytestBot Utility-Modell S3: Ausbau, Sparziel/CC, Forschung, Frachter" --body "Plan Tasks 16-22. Setzt PR S2 voraus. Messung siehe Spec §11. Legacy unverändert (Golden-Tests). Playtest-Suite lokal grün."
```

PR-Body um die Attribution der ausführenden Session ergänzen. Nicht mergen. Danach S4–S9 neu planen (Folgeplan unten), ausgehend von der S3-Messung.

---

## Folgeplan S4–S9 (nicht ausführbar — nach der S3-Messung neu planen)

### S4 Platzierung
- **Ziel:** `place_building` und `focus_trust_building` als Utility-Quelle: Pfadgebäude (feste Reihenfolge des zweiten Pfadgebäudes bleibt bis Owner-Freigabe als Pflicht-Filter), Wohnhabitat-Platzierung als Ermöglichungswert, Vertrauensgebäude (Werkstoffe × Preis, Vertrauen), Agrardom-Wiederholung nur mit Nahrungsdefizit. Sparziel-Kette (Wohnhabitat, das das Ziel vom Supply-Block befreit, ohne Opportunitätskosten).
- **Dateien:** `Utility/Sources/PlacementSource.php`, `HeuristicGoalProvider.php` (Kette), `UtilityStrategy::sources()`.
- **Messung:** 24 Läufe bis Sol 25 (K3, K19, K17, Zonen-Blockade, Ablehnungen `tile_occupied`).

### S5 Puffer-Abbau und Wartung
- **Ziel:** `repair_maintenance` als Quelle (vermiedener Stufenverlust × Stufenwert); im Utility-Pfad entfallen `cheapestPendingPathBuildingCost()`, `ccResearchReserve()`, `maintenanceMaySpendRegolith()`; `selfSufficiencyReserve()` wird Siegziel-Term; `bonusKnowledgeId()` nach Forschungswert.
- **Dateien:** `Utility/Sources/RepairSource.php`, `Valuers.php`, `BotStrategy.php` (nur Sichtbarkeit).
- **Messung:** 24 Läufe bis Sol 25 + K23 in 8 vollen Läufen.

### S6 AP-Füller
- **Ziel:** Erkunden, Tiefenscan, Selbstbergung, Flug-Bündel (`repair_ship_for_flight` + `dispatch_find_flight`), Erkundungs-/Credit-/Werkstoff-/Bergungsmissionen, Fokus-Expedition als Quellen (B5); K20b (Anteil Sole mit verworfenen positiven Kandidaten).
- **Dateien:** `Utility/Sources/NavSource.php`, `MissionSource.php`, `DecisionLog.php`, `OpeningComparison.php`.
- **Messung:** 24 Läufe bis Sol 25 (K4, K5, K11, K12, K16).

### S7 Personal und Credits
- **Ziel:** Berater einstellen/befördern, Schiffskauf vollständig, Händler, Konzernangebot, Harvester 2, Cantina-Aktionen; Credit-Bremse wird Knappheitspreis; statischer Profilzustand (`$holdsSelfSufficiencyReserve`, `$creditSavings`) entfällt im Utility-Pfad (B7).
- **Dateien:** `Utility/Sources/StaffSource.php`, `TradeSource.php`, `CantinaSource.php`.
- **Messung:** 24 volle Läufe (K7 nur beobachten, kein Tuning-Ziel).

### S8 Aufräumen
- **Ziel:** eine Score-Skala statt Bänder; Pflicht-Schicht als Regeln vor dem Sammler; K18 ≤ 5 %; Legacy-Tests, die bewusst geändertes Verhalten festschreiben (z. B. Lv2-Deckel), umschreiben oder löschen; Invarianten-Tests mit Datenprovider `engine ∈ {legacy, utility}`.
- **Dateien:** `UtilitySelector.php`, `Collector.php`, `UtilityStrategy.php`, `tests/Feature/Playtest/BotStrategy*Test.php`.
- **Messung:** Golden-Test entfällt bzw. wird zum Invarianten-Test; Playtest-Suite grün.

### S9 Neue Baseline und Abnahme
- **Ziel:** beide Engines, gleiche Seeds, volle Läufe; Abnahme nach Spec 5.3 (K3, K4, K5, K19, K16, K18, Ablehnungen, Laufzeit, K7 nur beobachten); danach Entscheidung über das Ende der Legacy-Engine.
- **Dateien:** Spec (Abschnitt Baseline), `CHANGELOG.md`, ggf. `ROADMAP.md` (über project-manager).
- **Messung:** 24 + 24 volle Läufe, dazu `focus` × `auto` × 8.
