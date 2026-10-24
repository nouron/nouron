# Pfad-Vertiefung per AP-Investition (T41) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Hangar-Missionsfamilien und Cantina-Figuren bekommen fünf Stufen, die der Spieler wie im Labor mit AP aufsteigt (Version 1: Hangar-Stufe erhöht den Ertrag der Familie, Cantina-Stufe zeigt nur Balken).

**Architecture:** Eine neue Tabelle `colony_path_levels` hält Stufe und eingezahlte AP je (Kolonie, Art, Schlüssel). Ein `PathDepthService` kapselt Deckel, Kosten und Einzahlung (Vorbild `ResearchService::invest()`), ein Controller liefert den Endpunkt im Hangar-/Bar-JSON-Vertrag. Der Hangar-Ertrag liest die Stufe beim Auszahlen (`GameTick::payMissionRewards`).

**Tech Stack:** Laravel, MySQL 8, PHPUnit (`nouron_test`), Blade, Alpine.js, PicoCSS.

**Spec:** `docs/superpowers/specs/2026-10-23-pfad-vertiefung.md` (§4a1 Ausbau gegen Ausführung, O1 entschieden, Variante C), GDD §4b.

## Global Constraints

- Nur **ein** AP-Pool. Keine AP-Arten einführen oder neu benennen (`AdvisorService::lockActionPoints(int $colonyId, int $ap, ?int $personellId=null): bool`).
- Stufenaufstieg kostet **nur AP**; Ausführung (Mission/Gespräch/Handel) behält ihre Kosten und ändert sich nicht.
- Stufen gelten **pro Lauf**: Reset in `OnboardingService::resetColonyToSol1()` ($colonyScoped, volle PK im Delete) **und** `ResetPlayer`.
- Deckel durch Gebäudestufe (F5): Stufe 1–2 ab Gebäudestufe 1, 3–4 ab 2, 5 ab 3; Hangar = `BuildingId::Hangar` (44), Cantina = `BuildingId::Bar` (52).
- Cantina-Investition erst **nach dem ersten Treffen** der Figur im Lauf (O1); Tomas (`bartender`) gilt von Beginn an als getroffen.
- Alle Zahlen sind **vorläufig** (Platzhalter, Owner 2026-10-24), nur in Config, kein Wert im Code. Kein Bot-Batch.
- Stufe 0 = Verhalten wie heute (Multiplikator 1.0): bestehende Tests bleiben unverändert grün.
- Texte nur über `__()`; Kürzel (AP, Cr, Rg, Or) nie übersetzen. JSON-Vertrag Hangar/Bar: Erfolg `{ok:true,...,apAvailable}`, Fehler 422 `{ok:false,error,message}` (`docs/frontend-conventions.md` §2).
- TDD: Test zuerst, rot bestätigen, dann Code. Schnelle Suite: `bin/phpunit --testsuite=laravel-feature,laravel-unit`; Pint/Prettier-Hook beachten (Blade zweimal Prettier).
- Dev-DB `nouron` nie anfassen; Tests laufen auf `nouron_test`.

## Review Focus

- Einzahlung mit zu wenig AP ändert nichts (kein Teilstand, keine Sperre).
- Einzahlung über dem Gebäude-Deckel oder auf Stufe 5 wird abgelehnt, ohne AP zu verbrauchen.
- Cantina: Figur nie getroffen, unbekannter Slug, unbekannte Familie, Fremdkolonie → Fehlercode, keine Schreibung.
- Neuer Lauf (`resetColonyToSol1`, `ResetPlayer`) löscht Stufen; Tomas bleibt investierbar.
- Ertrag: Stufe 0 gleich heute; nur skalierbare Belohnungsarten wachsen; Label im Dialog = ausgezahlter Wert.

## File Structure

| Datei | Verantwortung |
|---|---|
| `config/path_depth.php` (neu) | Stufenzahl, Kosten je Stufe je Art, Gebäude-Deckel, Ertrag je Stufe, Familien-Zuordnung |
| `database/migrations/2026_10_24_120000_create_colony_path_levels.php` (neu) | Tabelle `colony_path_levels` |
| `app/Services/PathDepthService.php` (neu) | Stufe lesen, Blocker prüfen, einzahlen, Ertragsfaktor, „getroffen“ |
| `app/Http/Controllers/Colony/PathDepthController.php` (neu) | `invest`-Endpunkt |
| `lang/{de,en}/pathdepth.php` (neu) | Fehler- und UI-Texte |
| `app/Console/Commands/GameTick.php`, `app/Services/HangarService.php`, `app/Support/MissionReward.php` | Ertragsfaktor beim Auszahlen und im Dialog-Label |
| `app/Services/BarService.php`, `CorporateContactService.php`, `ColonyController.php` | `markMet` neben `recordProgress` |
| `resources/views/colony/hangar.blade.php`, `public/js/hangar.js`, `resources/views/colony/bar.blade.php` | Training-Panel, Gesprächslevel-Panel |

## Design-Entscheidungen (Platzhalter, vom Plan gesetzt)

- Tabelle `colony_path_levels(colony_id int, kind varchar(16), entity_key varchar(40), level tinyint default 0, ap_spend int default 0)`, PK `(colony_id, kind, entity_key)`. `kind` ∈ `mission_family|character`. Zeile mit `level=0, ap_spend=0` bei `character` bedeutet „getroffen“. Variante A später: zusätzliche Spalte, keine Modelländerung.
- Familien (F6, Schlüssel): `exploration` (recon_flight, data_sweep, long_range_expedition), `salvage` (find_salvage, salvage_sweep, ruin_expedition, harvester_salvage), `supply` (supply_run, aid_transport), `trade` (courier_run, trade_convoy), `protection` (escort_convoy, perimeter_patrol). Mission-Keys mit Präfix `mission_`. Zuordnung der drei im Spec-Text nicht genannten Missionen (find_salvage, salvage_sweep, harvester_salvage → `salvage`) ist Annahme des Plans.
- Kosten (Platzhalter, progressiv, niedriger als Labor): `mission_family` 1..5 = 6,9,12,16,20; `character` = 4,6,8,10,12. Ertrag: `yield_per_level` = 0.05 (Faktor `1 + 0.05 × Stufe` auf skalierbare Belohnungsarten).

---

### Task 1: Datenmodell und Config (P1)

**Files:**
- Create: `config/path_depth.php`, `database/migrations/2026_10_24_120000_create_colony_path_levels.php`
- Modify: `app/Services/OnboardingService.php` (`$colonyScoped` um `colony_path_levels`, Key-Spec `['colony_id','kind','entity_key']`), `app/Console/Commands/ResetPlayer.php` (Delete-Schleife), `tests/Feature/Database/BaselineSchemaTest.php` (`assertCount(37` → 38), `tests/Feature/Onboarding/OnboardingResetIsolationTest.php` (`COLONY_TABLES`)
- Test: `tests/Feature/PathDepth/PathDepthSchemaTest.php`

**Interfaces:**
- Produces: Tabelle `colony_path_levels`; `config('path_depth')` mit Schlüsseln `max_level` (5), `levelup_ap.{mission_family|character}` (Array 1..5), `building_gate` (`[1=>1,2=>1,3=>2,4=>2,5=>3]`), `buildings` (`['mission_family'=>44,'character'=>52]`), `yield_per_level` (0.05), `families` (Familienschlüssel → Liste Missions-Keys).

- [ ] **Step 1: Failing tests** – `PathDepthSchemaTest`: `test_table_exists_with_expected_columns` (Spalten `colony_id,kind,entity_key,level,ap_spend`), `test_primary_key_is_colony_kind_key`, `test_every_catalog_mission_belongs_to_exactly_one_family` (alle `array_keys(config('missions.catalog'))` genau einmal in `config('path_depth.families')`), `test_levelup_costs_are_progressive` (je Art streng steigend, 5 Einträge). Reset: in `OnboardingResetIsolationTest` eine Zeile in `colony_path_levels` anlegen und prüfen, dass `resetColonyToSol1` sie löscht und fremde Kolonie nicht.
- [ ] **Step 2: Rot bestätigen** (`bin/phpunit --filter PathDepth`, `--filter OnboardingResetIsolation`).
- [ ] **Step 3: Migration** im Baseline-Stil (`$table->integer('colony_id')`, explizite Foreign auf `glx_colonies`, `down()` droppt). Config schreiben (Werte siehe Design-Entscheidungen). Reset-Listen ergänzen, FK-abhängige Reihenfolge in `ResetPlayer` beachten (vor `glx_colonies`).
- [ ] **Step 4: Grün** (genannte Tests plus `BaselineSchemaTest`).
- [ ] **Step 5: Commit** `feat(t41): Datenmodell colony_path_levels und Config path_depth`.

### Task 2: PathDepthService (P2)

**Files:**
- Create: `app/Services/PathDepthService.php`
- Test: `tests/Feature/PathDepth/PathDepthServiceTest.php`

**Interfaces:**
- Consumes: Task 1 (Tabelle, Config), `AdvisorService::lockActionPoints`, `AdvisorService::getAvailableActionPoints(int): int`, `game.bypass.ap_checks`, `App\Exceptions\GameRuleException` (wie `HangarService`; Konstruktor/Code-Konvention dort übernehmen).
- Produces (alle in `PathDepthService`):
  - `state(int $colonyId, string $kind, string $key): array` → `['level'=>int,'ap_spend'=>int,'ap_for_levelup'=>int,'level_cap'=>int,'met'=>bool]` (`ap_for_levelup` = 0 bei Stufe 5; `met` immer true bei `mission_family`)
  - `levelCap(int $colonyId, string $kind): int` (aus Gebäudestufe und `building_gate`; 0 ohne Gebäude)
  - `invest(int $colonyId, string $kind, string $key, int $points = 1): array` (gibt `state()` plus `leveled_up: bool`; wirft `GameRuleException` mit Codes `unknown_entity|not_met|max_level|building_level_cap|insufficient_ap`)
  - `markMet(int $colonyId, string $characterSlug): void` (`insertOrIgnore` Zeile `level 0`; idempotent)
  - `yieldFactor(int $colonyId, string $missionKey): float` (`1 + yield_per_level × Stufe der Familie`; unbekannte Mission → 1.0)
  - `familyOf(string $missionKey): ?string`

- [ ] **Step 1: Failing tests** (jeweils Name = Aussage): `test_invest_adds_ap_spend_and_locks_ap`; `test_invest_levels_up_when_threshold_reached_and_resets_ap_spend`; `test_invest_caps_points_to_remaining_cost`; `test_invest_rejected_when_not_enough_ap_leaves_state_unchanged` (Config `game.bypass.ap_checks=false`); `test_invest_rejected_above_building_cap_without_spending_ap` (Hangar Stufe 1 → Familie max Stufe 2); `test_invest_rejected_at_level_5`; `test_character_not_met_is_rejected` (`not_met`); `test_bartender_is_investable_without_meeting`; `test_mark_met_is_idempotent`; `test_unknown_family_and_unknown_character_rejected`; `test_yield_factor_is_one_at_level_zero_and_grows_per_level`; `test_family_of_maps_every_catalog_mission`. Aufbau wie `KnowledgeServiceTest` (TestSeeder, Berater-Zeile für AP, `TickService` mocken).
- [ ] **Step 2: Rot bestätigen.**
- [ ] **Step 3: Implementieren.** Einzahlung in einer DB-Transaktion; erst Blocker prüfen, dann `lockActionPoints`, dann Zeile `updateOrInsert`. Gebäudestufe über `max('level')` auf `colony_buildings` (Instanzen!). Kein neuer Wert im Code, alles aus `config('path_depth')`.
- [ ] **Step 4: Grün.**
- [ ] **Step 5: Commit** `feat(t41): PathDepthService (Deckel, Einzahlung, Ertragsfaktor)`.

### Task 3: Endpunkt und Texte (P2)

**Files:**
- Create: `app/Http/Controllers/Colony/PathDepthController.php`, `lang/de/pathdepth.php`, `lang/en/pathdepth.php`
- Modify: `routes/web.php` (Gruppe `colony`, nach den Hangar-Routen)
- Test: `tests/Feature/PathDepth/PathDepthControllerTest.php`

**Interfaces:**
- Consumes: Task 2 `invest()`/`state()`.
- Produces: Route `POST /colony/path-depth/{kind}/{key}/invest`, Name `colony.pathdepth.invest`, Body `{ap: int (optional, default 1)}`. Erfolg `{ok:true, level, ap_spend, ap_for_levelup, leveled_up, level_cap, apAvailable}`. Fehler 422 `{ok:false, error:<code>, message:__('pathdepth.error_<code>')}`. Lang-Keys `error_unknown_entity|not_met|max_level|building_level_cap|insufficient_ap` und `ui_*` (Titel „Training“, „Beziehung pflegen“, Stufenanzeige), Namen der fünf Familien (`family_exploration` …). Label „Missionserfahrung“ ist Vorschlag: eigener Key, leicht änderbar.

- [ ] **Step 1: Failing tests** (Vorbild `HangarControllerTest`/`TechtreeControllerTest`): `test_invest_family_returns_ok_json_with_ap_available`; `test_invest_requires_auth`; `test_foreign_colony_cannot_be_targeted` (Kolonie immer aus `ColonyService::getPrimeColony(Auth::id())`); je Fehlercode ein 422-Test inkl. Übersetzung vorhanden (de und en).
- [ ] **Step 2: Rot.** [ ] **Step 3: Controller dünn halten (nur Auflösen, `GameRuleException` → 422).** [ ] **Step 4: Grün.**
- [ ] **Step 5: Commit** `feat(t41): Investitions-Endpunkt und Texte`.

### Task 4: Hangar-Ertrag nach Familienstufe (P3)

**Files:**
- Modify: `app/Console/Commands/GameTick.php` (`payMissionRewards`, ~l.516: Faktor auf `$scalableTypes` zusätzlich zu `$rewardMultiplier`), `app/Services/HangarService.php` (`getMissionCatalogFor`, ~l.890: `reward_label` mit Faktor), `app/Support/MissionReward.php` (`label()` nimmt optionalen Faktor)
- Test: `tests/Feature/Hangar/MissionFamilyYieldTest.php`

**Interfaces:**
- Consumes: Task 2 `yieldFactor(int,string): float`.
- Produces: keine neuen Signaturen außer `MissionReward::label(array $reward, float $factor = 1.0): string`.

- [ ] **Step 1: Failing tests:** `test_payout_at_level_zero_equals_current_behavior` (Credits-Mission, unverändert); `test_payout_scales_credits_with_family_level` (Stufe 2 → `round(180 × 1.10)`); `test_non_scalable_rewards_not_scaled` (Bergungsfund/`reveal_tiles` laut `$scalableTypes`-Regel prüfen); `test_dialog_reward_label_matches_paid_amount`; `test_level_read_at_resolution_not_dispatch` (Stufe nach Entsendung erhöht → gilt beim Auszahlen; dokumentierte Annahme).
- [ ] **Step 2: Rot.** [ ] **Step 3: Minimal einhängen** am `$rewardMultiplier`; Rundung wie bestehender Code. [ ] **Step 4: Grün, danach `--testsuite=laravel-feature --filter Hangar` komplett.**
- [ ] **Step 5: Commit** `feat(t41): Hangar-Ertrag nach Familienstufe`.

### Task 5: Cantina „getroffen“ (P4)

**Files:**
- Modify: Aufrufstellen von `CharacterCodexService::recordProgress` (`BarService` l.284, 721, 751, 943, 1255; `CorporateContactService` l.97; `ColonyController` l.872): daneben `PathDepthService::markMet($colonyId, $slug)`; dort wo nur `userId` vorliegt, Kolonie über `ColonyService::getPrimeColony($userId)`.
- Test: `tests/Feature/PathDepth/CharacterMeetingTest.php`

**Interfaces:** Consumes Task 2 `markMet`, `state()['met']`.

- [ ] **Step 1: Failing tests:** je Auslöser-Art ein Test: nach Barter-Annahme / Anliegen / Informationsbegegnung / Unternehmensvertreter markiert der Slug die Figur als getroffen; Kodex-Verhalten unverändert (bestehende Kodex-Tests grün); zweites Treffen erzeugt keine zweite Zeile.
- [ ] **Step 2: Rot.** [ ] **Step 3: Einhängen, keine Logikänderung am Kodex (F3).** [ ] **Step 4: Grün.**
- [ ] **Step 5: Commit** `feat(t41): Cantina-Figuren als getroffen markieren`.

### Task 6: Hangar-UI „Training“ (P5)

**Files:**
- Modify: `app/Http/Controllers/Colony/HangarController.php::index` (Familienzustände in `window.__hangarData`), `resources/views/colony/hangar.blade.php` (Panel nach `hangar-pending-section`, ~l.410, Partial `partials.ap-upgrade-button` wiederverwenden), `public/js/hangar.js` (Investieren-POST via `_post`, `syncHangarResources`)
- Test: `tests/Feature/Hangar/HangarTrainingViewTest.php`

**Interfaces:** Consumes Task 3 Route/Vertrag. Row-Vertrag des Partials: `ap_spend`, `ap_for_levelup`, optional `ap_cap`.

- [ ] **Step 1: Failing tests** (Server-Markup): Panel zeigt fünf Familien mit Stufe und Kosten-Chip; gesperrte Familie (Deckel) nennt Grund; alle Texte aus `pathdepth.php`.
- [ ] **Step 2: Rot.** [ ] **Step 3: Umsetzen** (AP-Kosten-Chip-Konvention, Resourcebar-Sync nach Einzahlung, Rahmung „Training“). [ ] **Step 4: Grün; Blade zweimal Prettier.**
- [ ] **Step 5: Browser-Prüfung** mit `tools/uicheck/uicheck` (Wegwerf-DB). [ ] **Step 6: Commit** `feat(t41): Hangar-Training-Panel`.

### Task 7: Cantina-UI „Beziehung pflegen“ (P5)

**Files:** `app/Http/Controllers/Colony/BarController.php::index` (getroffene Figuren mit Zustand), `resources/views/colony/bar.blade.php` (Panel, Alpine in `barPage`), Test `tests/Feature/Bar/BarRelationshipViewTest.php`.

- [ ] **Step 1: Failing tests:** nur getroffene Figuren (plus Tomas) sichtbar investierbar, andere nicht aufgelistet oder gesperrt mit Hinweis; Stufe und Kosten-Chip; Kodex-Anzeige unverändert, Beschriftung trennt „in diesem Lauf“ von „insgesamt“ (R3).
- [ ] **Step 2–4:** rot, umsetzen, grün (Prettier doppelt). [ ] **Step 5:** uicheck. [ ] **Step 6: Commit** `feat(t41): Cantina-Beziehungspanel`.

### Task 8: Dokumentation und Abschluss (P6)

**Files:** `GLOSSAR.md`, `docs/game-reference.md` (§6 Training-/Gesprächslevel-Tabellen, §8 Familien und Ertragsfaktor, §19 Tabelle), `CHANGELOG.md` (Block `2026-10-24`, eine Zeile), `ROADMAP.md` (T41: P1–P6 abhaken, offene Platzhalter und O2/R2/Bot-Messung nennen), Spec P-Tabelle Status.

- [ ] **Step 1:** Doku nachziehen, Werte aus `config/path_depth.php` kopieren, als vorläufig markieren.
- [ ] **Step 2:** Volle Suite `bin/phpunit` grün; `vendor/bin/pint --test`.
- [ ] **Step 3: Commit** `docs(t41): Glossar, game-reference, ROADMAP, CHANGELOG`.

---

## Bewusst nicht Teil dieses Plans

- Cantina-Stufenwirkung (P7/O4), Variante A, Berater-Rang-Gate (R5).
- Proviantwachstum je Stufe (R2 Punkt 3): erst nach Ertragsmessung.
- Bot-Regel „trainieren“ und jeder Bot-Batch (nur nach Rückfrage mit Dauerschätzung).
- Code-Rest der AP-Arten (T42).

## Self-Review

Spec-Abdeckung: Ausbau gegen Ausführung (Task 2/3), F2 Ertrag (4), F5 Deckel (2), F6 Familien (1, 4), O1 (2, 5), Reset pro Lauf (1), UI mit AP-Chip (6, 7), Docs (8). Typen: `invest`, `state`, `yieldFactor`, `markMet` in Task 2 definiert und in 3–7 gleich benannt. Offen als Annahmen markiert: Platzhalterkosten, Zuordnung dreier Missionen, Stufe-bei-Auszahlung.
