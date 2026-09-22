# Nouron — Roadmap-Archiv: Phase 2 & Phase 3

> Abgeschlossene Arbeit aus Phase 2 ("Spielablauf stabilisieren"), Phase 3 ("Das Spiel zeigen") und den bereits abgeschlossenen Stufen des Phase-3o-Stufenplans (Stufe 0, 1, 1c, 2). Für die Laminas→Laravel-Migration (Phase 1b) siehe `docs/roadmap-archiv-migration.md`. Für aktuell offene Arbeit siehe `ROADMAP.md`.

---

## Phase 2: Spielablauf stabilisieren
*(nach Abschluss Phase 1b)*

**Designklarstellungen:**
- Jeder Spieler hat genau **eine Kolonie** — kein Kolonisierungsfeature
- Kämpfe finden ausschließlich als **PvP-Schiffskämpfe** statt (Schiffe vs. Schiffe)
- Alle anderen Interaktionen (Gebäude, Forschung, Produktion, Handel) sind **PvE** (Player vs. Environment)
- Es gibt keine Angriffe auf Kolonien

*Hinweis: Diese Designklarstellungen stammen aus der Phase-2-Planung. PvP-Flottenkämpfe und Angriffe sind seit der Flotten-/Systemkarten-Streichung (2026-06-20) nicht mehr Teil des Spiels — siehe `docs/gdd/archiv-flotten-systemkarte.md`.*

---

### Prio 1: Kritische Bugs beheben

| Problem | Ort | Status |
|---|---|---|
| ~~`PersonellService::hire` — `$this->resourcesService` nicht deklariert → Fatal Error wenn `dev_mode=false`~~ | ~~`app/Services/Techtree/PersonellService.php`~~ | Behoben (PR #66) |

---

### Prio 2: Fehlende UI für vorhandene Services

Die folgenden Services sind implementiert, aber ohne UI — Spieler können diese Funktionen nicht nutzen:

- [x] **Advisor-Management-UI** — `/advisors` mit hire/fire, 4 Typ-Cards, AP-Summen, Supply-Kosten
- [x] **Colony-UI** — `/colony` mit Kolonie-Übersicht und Umbenennung (PATCH `/colony/name`)
- [x] **Forschungshandel-View** — `/trade/researches` implementiert; Ressourcenhandel `/trade/resources` ebenfalls überarbeitet (Chips, Restriktions-Badges, Farbcodierung)
- [x] **User-Profil / Einstellungen** — Passwort, Display Name und weitere Einstellungen implementiert

---

### Prio 3: Spielmechaniken vervollständigen

- [x] **`moving_speed` für Schiffe gesetzt** — `config/ships.php` enthält nun Werte (4/3/2/3/2/1); `FleetService::calcFleetSpeed()` war bereits korrekt implementiert
- [x] **`game:sync-techs` implementiert** — `app/Console/Commands/SyncTechs.php`; synct moving_speed, decay_rate, supply_cost, max_status_points aus config in ships/buildings-Tabellen; `--dry-run` Option vorhanden
- [x] **Laravel Scheduler eingerichtet** — `routes/console.php`: `Schedule::command('game:tick')->dailyAt('03:00')`
- [x] **Fleet-Orders im UI vervollständigt** — `hold`, `convoy`, `defend`, `join` sind im Validator, Controller und Blade-View mit Lokalisierung implementiert; AP-Kosten in `config/game.php` ergänzt
- [x] **Flotten auf Galaxiekarte** — `getMapData()` liefert Layer-3-Einträge für alle Flotten im Sichtbereich; eigene Flotten grün, fremde gelb; galaxy.js rendert bereits korrekt
- [x] **Galaxy-Koordinaten-Skalierung geprüft** — System-Radius 50 Einheiten; Speed 4 durchquert in ~12 Ticks, Speed 1 in ~50 Ticks — Unterschied ist für Spieler deutlich spürbar, keine Anpassung nötig

---

### Prio 4: Spielablauf testen & stabilisieren

> **Obsolet (bereinigt 2026-09-06):** `fleet_orders`, Handelsrouten, Flottenoperationen/PvP und Flash-Messenger existieren seit der Streichung von Galaxie/Systemkarte (2026-06-20) bzw. dem Comm-Log-Redesign (Phase 3j) nicht mehr. AP-System und Auth sind über die PHPUnit-Suite und den PlaytestBot (Phase 3n) abgedeckt.

- [x] ~~Tick-System und `fleet_orders`-Verarbeitung End-to-End testen~~ (entfallen)
- [x] AP-System vollständig testen (Vergabe, Verbrauch, Trust-Multiplikator) — `AdvisorServiceTest`, PlaytestBot
- [x] ~~Handelsrouten (Ressourcen + Forschungen)~~ (entfallen)
- [x] ~~Flottenoperationen (Bewegung, PvP-Schiffskampf)~~ (entfallen)
- [x] ~~Flash-Messenger in Formularen~~ (entfallen)
- [x] Login/Registrierung und Auth-System — `tests/Feature/Auth`

---

### Bekannte Lücken (kein Code vorhanden, Stand Ende Phase 2)

| System | Beschreibung |
|---|---|
| **Politiksystem / Diplomatie** | `innn_message_types.relationship_effect` ist im Schema vorhanden, wird aber nirgends ausgewertet. Allianz/Krieg/Frieden: keine Logik. Moral-Events `war_declared` und `treaty_signed` sind in `config/game.php` definiert, aber nie gefeuert. |
| **Aktionslog** | Kein persistentes Log über Spieleraktionen (Gebäude gebaut, Flotte bewegt, Handel abgeschlossen etc.) — weder im Backend noch im UI. |

*Einordnung: „Politiksystem/Diplomatie" ist als „NPC-Vereinbarungen" in `ROADMAP.md → Phase 4: Ideenpool` weiterhin aktiv als Idee getrackt (dort mit demselben `relationship_effect`/`treaty_signed`-Befund). „Aktionslog" ist an keiner anderen Stelle der Roadmap erfasst — wurde zur Vermeidung von Informationsverlust zusätzlich als eigener Punkt in `ROADMAP.md → Ideenpool` aufgenommen. Das Kolonieprotokoll (`/comm-log`, Phase 3j) deckt inzwischen einen Teil des ursprünglichen Bedarfs ab (chronologisches Aktions-/Ereignis-Log), aber kein rein spieler-aktions-bezogenes Audit-Log.*

---

## Phase 3: "Das Spiel zeigen" — Abgeschlossen (2026-05, ungefähr)
*(nach Phase 2)*

**Ziel:** Das Spiel ist für externe Spieler zugänglich, verständlich und rund.

Dieser Schnitt macht Sinn, weil Phase 2 die Mechaniken implementiert und stabilisiert, Phase 3 aber das Spiel für Menschen lesbar und spielbar macht, die keinen Entwicklerhintergrund haben. Ohne diesen Schritt ist kein sinnvoller Playtest mit echten Spielern möglich — und ohne Playtest-Feedback sind Phase-4-Entscheidungen (Diplomatie, Rassen, Gruppen) zu unsicher, um sie zu committen.

---

### Phase 3a: Design-Sprint — Abgeschlossen (2026-04, ungefähr)

Alle drei Design-Themen wurden entschieden und im GDD dokumentiert (PRs #78, #79, #80 gemergt).

- [x] **Kenntnisse-System redesignt** — Freischalt-Techtree (permanent, kein Decay); Dual-Effekt-Modell (Primär/Sekundär); Berater-Zuweisung mit Slots nach Rang; 7 Kenntnisse, Roguelike-Variabilität → PR #78
- [x] **Handel redesignt** — Bar als einziger Handelsort (0–2 Gäste/Tick); Nexus-Handelsschiffe als Fallback; Regolith als neue Ressource (lokal abbaubar); Werkstoffe nur via Handel/Events → PR #79
- [x] **Flottenbewegung redesignt** — interstellare Bewegung nicht implementiert; Flotten im eigenen System; Sprungtor als narratives Element → PR #80

---

### Phase 3a: Implementierung (Design-Sprint-Ergebnisse)

> **Stand PR #82 (2026-04, ungefähr):** Kern-Balancing und Ressourcensystem vollständig implementiert.

- [x] **Regolith als neue Ressource eingeführt** — resource_id 3, Startwert 200, Harvester produziert Regolith, OnboardingService angepasst (PR #81)
- [x] **Tradecenter entfernt** — config, MasterDataSeeder, Migration, Lang-Dateien, testdata; Trader + Wirtschafts-Forschung erfordern jetzt Bar (PR #81)
- [x] **Ressourcen umbenannt** — Ferum → Werkstoffe (Co), Silikate → Organika (Or); beide starten bei 0 (PR #82)
- [x] **Kenntnisse-System implementiert** — 7 Typen (IDs 90–96), kein Decay, steigende AP-Kosten per Level (5/10/18/28/40), Supply-Cap-Bonus; `ResearchService.resolveApForLevelup()` Hook (PR #82)
- [x] **Gebäude-Balancing kalibriert** — ap_for_levelup (CC=10, Standard=20, High-Tech=30), Regolith als Baukosten für alle Gebäude außer CC+Harvester (PR #82)
- [x] **Schiffssystem redesignt** — Sonde (85) in DB eingeführt; Korvette (37) + Frachter (47) umbenannt; Schiffskosten: Credits + Werkstoffe + Organika; deprecated ships costs entfernt (PR #82)
- [x] **Passive Credits + Berater-Upkeep** — GameTick: Nexus-Subvention 30 Cr/Tick + Kolonistensteuern 20 Cr/Tick pro Housing-Level; Upkeep 10/50/160 Cr je Rang (PR #82)
- [x] **Startzustand** — CC Lv1 + Harvester Lv1 vorgebaut; 3.000 Credits, 200 Regolith, 0 Werkstoffe/Organika (PR #82)
- [x] **Berater-Einstellungskosten kalibriert** — 50 Cr → 300–600 Cr je Typ; echter Day-1-Tradeoff (PR #82)
- [x] **Bar-Event-System** — 0–2 NPC-Gäste pro Tick, befristete Angebote (2 Ticks), Credits + Tausch; Konsul-Rang steuert Anzahl und Preise (PR #114)
- [x] **DB-Cleanup: überzählige Gebäude entfernt** — 25 → 11 aktive Gebäude; `building_*`-Keys eingeführt; Migration + Seed bereinigt (PR #92)
- [x] **Berater Rang 2/3 Beförderungskosten** — 150/400 Cr je Rang; Beförderung verschoben bei fehlenden Credits (PR #114)

---

### Phase 3b: Colony-View + Buildings-Cleanup — Abgeschlossen (2026-04, ungefähr, PR #92)

**Frontend-Stack:** Alpine.js + PicoCSS + SVG für neue Screens. Bestehende Screens (fleets, techtree, trade, innn) werden schrittweise migriert.

- [x] **Alpine.js + PicoCSS eingebunden** — Colony-Layout `layouts/colony.blade.php`; bestehende `app.blade.php` vorerst unangetastet
- [x] **DB-Migrationen** — `colony_tiles` (Hex-Grid, Rings, Fog-of-War), `instance_id` + `tile_x/y` auf `colony_buildings`, `planet_size/type` auf `glx_system_objects`
- [x] **Colony-View (Hex-Grid)** — SVG + Alpine.js, Axial-Koordinaten, Fog-of-War, Tile-Sidebar, Building-Badges, Signal-Indikator (PR #92)
- [x] **Demo-Seed** — `php artisan colony:seed-demo` befüllt Kolonie mit ~80%-Demo-State
- [x] **System-View (12×12-Grid)** — SVG + plain JS, Objekte und Flotten, Flottenbefehl-Overlay
- [x] **Vertrauensanzeige im UI** — Vertrauens-Chip in Colony Hexview (grün/grau/rot); Trust in globaler Ressourcenleiste auf allen Seiten
- [x] **Händler-Modal** — Alpine-gesteuert, nativer `<dialog>`, 3 Items (Reparatur-Kit, Vertrauensschub, Systemkarte); MerchantService + MerchantController + GameTick-Integration; DB: `merchant_visits` + `merchant_items`
- [x] **Globale Ressourcenleiste** — Sol-Chip + Credits + Supply + Trust persistent auf allen Gameplay-Seiten (`layouts/app` + `layouts/colony`); Sol run-lokal via `since_tick`; deprecated Ressourcen (ENrg/LNrg/ANrg) gefiltert
- [x] **Ingame-Almanach** — als NexusDB umgesetzt (`/nexus-db`, `NexusDbController`, `lang/de/nexusdb.php`, Almanach-Stimme des Drei-Stimmen-Systems). Erweiterungen (Freischalt-Artikel) siehe Phase 4 „Progressive Discovery"
- [x] **jQuery-Migration (Schritt 1)** — galaxy.js, nouron.js, innn.js auf Vanilla JS migriert; techtree.js + leader-line.min.js aus layouts.app entfernt (dead code); Inline-$(document).ready → DOMContentLoaded
- [x] **jQuery-Migration (Schritt 2)** — fleets.js und trade.js auf Vanilla JS/fetch migriert; jQuery, bootbox, growl aus layouts.app entfernt; jQuery vollständig aus dem Projekt entfernt

---

### Phase 3c: Kolonieaktionen — Abgeschlossen (2026-04, ungefähr, PR #93)

- [x] **Erkunden** — unbekannte Exploration-Zone-Tiles aufdecken (1 Nav-AP); kontextsensitiver Button in Sidebar
- [x] **Sondieren (Deep Scan)** — Signal-Tiles mit Event untersuchen (2 Nav-AP); pulsierender SVG-Indikator
- [x] **Bauen** — globaler Button im Canvas-Header; Gebäude-Auswahlliste; Terrain-Tile wählen (1 Construction-AP); AP investieren bis Level-Up
- [x] **AP-Chips** — Nav-AP und Bau-AP werden nach jeder Aktion live aktualisiert

---

### Phase 3d: Colony Zone Expansion — Abgeschlossen (2026-04, ungefähr, PR #94 + PR #95)

- [x] **Tile-Count Unlock** — CC Lv1–5 schaltet 4/2/3/3/3 = max. 15 individuelle Terrain-Tiles frei (statt ganzer Ringe); konfigurierbar via `config/game.php → colony_zone_expansion`
- [x] **`is_ring_unlocked` → `is_colony_zone`** — DB-Umbenennung; Semantik: Terrain-Tile in Koloniezone (bebaubar)
- [x] **3-Ring-Karte als Default** — 37 Tiles statt 61; Kartengröße run-konfigurierbar (vorbereitet)
- [x] **CC Level-Up live** — Grid aktualisiert sich sofort wenn CC aufsteigt
- [x] **Mehrfach-Instanzen** — Wohnhabitat (max 6×) und Hangar mehrfach platzierbar

---

### Phase 3e: Onboarding & New-Player Experience — Abgeschlossen (2026-05, ungefähr)

GDD-Referenz: § 15 (Designprinzipien, §15.1–§15.7)

**Kernprinzipien (GDD § 15):** Lernen durch Tun — kein Pflicht-Tutorial — erfahrene Spieler nicht bevormunden — minimaler Implementierungsaufwand.

#### Schritt 1 — Infrastruktur & Konfiguration

- [x] [db-migration-agent] `user_preferences`-Tabelle + `onboarding_hints`-Spalte (2 Migrationen)
- [x] [game-developer] `config/game.php → onboarding`-Block: 5 Schwellwerte (`hint_supply_cap_threshold`, `hint_no_engineer_ticks`, `hint_no_knowledge_after_tick`, `hint_trust_threshold`, `hint_trust_min_ticks`)
- [x] [backend-coder] `UserController::updateOnboardingHints()` + Route `PATCH /user/settings/onboarding` + Toggle in `settings.blade.php`

#### Schritt 2 — Nexus-Briefing (§ 15.1)

- [x] [content-writer] Finalen Nachrichtentext für das Nexus-Briefing formulieren — `lang/de/colony.php → onboarding_nexus_briefing_title/body` (karg, lakonisch, Frontier-Ton)
- [x] [game-developer] `EventService::createNexusBriefing()` mit idempotent guard; `OnboardingService::setupNewPlayer()` ruft `createNexusBriefing()` — Event beim Erzeugen eines neuen Runs automatisch angelegt
- [x] [qa-tester] 6 Tests in `NexusBriefingTest.php` grün

#### Schritt 3 — Hint-System (§ 15.2)

- [x] [game-developer] `OnboardingHintService`: 5 Rang-Regeln (Rang 1: kein Wohnhabitat; Rang 2: kein Ingenieur; Rang 3: Harvester auf falschem Tile; Rang 4: keine Kenntnis; Rang 5: Vertrauen < -20); gibt `null` zurück wenn `onboarding_hints = false`
- [x] [backend-coder] Dismiss-Endpunkt `POST /colony/hint/dismiss`; AJAX-Aktionen liefern `activeHint` in Response; kein separater Poll-Endpunkt nötig
- [x] [ui-specialist] Reaktive Hint-Bar in `hexview.blade.php` — Alpine `x-show`, kein Page-Reload; AJAX-Aktionen aktualisieren Hinweis live
- [x] [qa-tester] 17 Tests in `OnboardingHintServiceTest.php` grün

#### Schritt 4 — Pulse-Indikator (§ 15.3)

- [x] [ui-specialist] CSS-Animation `onboarding-ring-pulse` (blau-weiß, 2s) in `colony.css`
- [x] [ui-specialist] Pulse auf Rang-1-Tiles (bebaubare Colony-Zone) und Rang-3-Tiles (Harvester-Tile) im SVG-Grid implementiert
- [x] [ui-specialist] Pulse für Rang 2/4/5 (Techtree-Kacheln) — `data-hint-rank` auf Container, CSS `@keyframes techtree-card-pulse` auf `.tech-personell/.tech-research/.tech-building.status-available`

#### Schritt 5 — Techtree-Kaltstart: Kachel-Sortierung (§ 15.4)

- [x] [backend-coder] `TechtreeController` / Techtree-API: Gruppierungsflag je Kachel (`available` / `locked` / `built`) — implementiert
- [x] [ui-specialist] Techtree-View: drei visuelle Gruppen, gesperrte Kacheln gedimmt (Opacity 0.55) mit Lock-Icon + Voraussetzungs-Hinweis

#### Schritt 6 — Inline-Erklärungen: 5 INNN-Trigger (§ 15.6)

- [x] [game-developer] Trigger 1 (Decay): Erstes Gebäude unter 80% Status-Points → einmaliges `innn_event` mit `event_type = 'onboarding_decay'`, Absender System, erklärt Reparatur-AP (einmalig pro Run)
- [x] [game-developer] Trigger 2 (Supply-Cap voll): `freies_supply = 0` → `fired_triggers → supply_cap_full` in `user_preferences`
- [x] [game-developer] Trigger 3 (Vertrauen erstmals negativ): `vertrauen` wird negativ → einmaliges `innn_event` mit `event_type = 'onboarding_trust'`, Absender Kolonist
- [x] [backend-coder] Trigger 4 (AP-Limit): Button-Handler gibt `error: 'ap_limit'` zurück; Frontend zeigt Inline-Meldung (kein Modal)
- [x] [ui-specialist] Trigger 5 (Harvester-Verlagerung): Beim ersten Klick auf "Verlegen" erscheint einmaliger Tooltip via `harvester_move_shown`-Flag
- [x] [db-migration-agent] Flag-Mechanismus: `fired_triggers` JSON-Spalte in `user_preferences`; `OnboardingTriggerService` mit idempotenten `hasFired`/`markFired`
- [x] [content-writer] Finale Texte für alle 5 Inline-Erklärungen in `lang/de/colony.php`
- [x] [qa-tester] 43 Tests in `OnboardingTriggersTest.php` + `OnboardingTriggerServiceTest.php` — alle grün

#### Schritt 7 — Integration & Einstellungen

- [x] [ui-specialist] Einstellungs-Toggle in User-Settings-Screen: "Onboarding-Hinweise anzeigen" (An/Aus) — implementiert (Schritt 1)
- [x] [qa-tester] End-to-End: Neuer Run → Nexus-Briefing im INNN → Hint-Leiste zeigt Rang-1-Hinweis → Wohnhabitat bauen → Hint-Rang wechselt auf Rang 2 → Onboarding-Hints deaktivieren → null — `OnboardingE2ETest.php` (4 Tests, 15 Assertions)

---

### Phase 3g: Neue Gebäude — Abgeschlossen (2026-05, ungefähr, PRs #104 + #105 + #112)

Drei neue Gebäude entworfen (GDD §4 + §11) und vollständig implementiert (DB-Migration, Service-Effekte, Sprachschlüssel).

- [x] **Sicherheits-Hub** (`securityHub`, CC Lv2, max 1 Instanz) — Verteidigung-Order kostet nur 1 Nav-AP; gibt ~10% der Stufenkosten als Ressourcen zurück beim Decay-Level-Down. Provisorisch: supply_cost 8, decay 30d.
- [x] **Uplink-Station** (`uplinkStation`, CC Lv2/3/5, max 1 Instanz, 3 Level) — Lv1: Aktive Nexus-Anfragen freischalten; Lv2: Tiefenscan −1 Tick + Händler häufiger; Lv3: Run-Abschluss-Aktion. Lv1-Baukosten ohne Werkstoffe (kein Zirkelrisiko). Provisorisch: supply_cost 6, decay 30d.
- [x] **Handelsposten** (`tradingPost`, CC Lv4, max 1 Instanz) — Händler-Economy-AP −1; Händlerpreise +10–15%. Provisorisch: supply_cost 6, decay 30d.

---

### Phase 3f: Berater-Screen Redesign — Abgeschlossen (2026-05, ungefähr, Branch feat/phase3f-advisor-carousel)

Der Berater-Screen war der logische nächste Schritt nach dem Onboarding (Phase 3e), da der Onboarding-Hinweis Rang 2 direkt auf das Einstellen eines Beraters verweist. Der Screen wurde von Bootstrap/jQuery auf Alpine.js + PicoCSS migriert und als Karussell neugestaltet.

- [x] [backend-coder] `AdvisorController::buildSlots()` — 5-Slot-Array mit Zustands-Logik (active/unavailable/empty/locked), CC-Level-Gating, Rang-Fortschritt in Prozent
- [x] [backend-coder] JSON-Branching in `hire()` und `fire()` — AJAX-Clients erhalten strukturiertes JSON (`{ok, slots, slotInfo}`), HTML-Clients erhalten weiterhin Redirect
- [x] [ui-specialist] `public/css/advisors.css` — Portrait-Karten (2:3-Verhältnis), Rang-Badges, Fortschrittsbalken, Status-Chips, Karussell-Track mit CSS-Transition, Arrows + Dots (Mobile only)
- [x] [ui-specialist] `public/js/advisors.js` — Alpine-Komponente: Swipe-Gesten (Touch-Events), Karussell-Navigation, AJAX hire/fire, native `<dialog>`-Steuerung
- [x] [ui-specialist] `resources/views/advisors/index.blade.php` — Komplett auf `layouts.colony` (PicoCSS + Alpine) umgestellt; `x-for` für Karten, `x-if` für Zustände, `@push`-Stacks für CSS/JS
- [x] [qa-tester] 22 Feature-Tests in `AdvisorControllerTest.php` — Index, Hire/Fire (Redirect + JSON), 404-Sicherheit, Auth-Guard; alle grün

---

### Phase 3h: Techtree Phase-Layout — Abgeschlossen (2026-05, ungefähr)

Techtree-Ansicht komplett überarbeitet. Fünf Sektionen (Phase 1–5), eine pro CC-Level. 3-Spalten-Grid je Sektion; SVG-Bézier-Pfeile für Abhängigkeiten innerhalb einer Phase. Mobile: horizontales Karussell mit Wisch-Geste und Dot-Navigation.

- [x] DB-Migration 000003 — `phase`-Spalte auf allen 4 Master-Tabellen; partielle `(phase, row, column)` Unique-Indizes ersetzen alte `(row, column)` Indizes
- [x] `TechtreeController` — pageData-Struktur mit Phase-Gruppen; Liniengenerierung phase-lokal
- [x] `resources/views/techtree/index.blade.php` — Alpine.js + PicoCSS, Phasen-Sektionen, Karussell (Mobile)
- [x] `public/js/techtree-view.js` — Bézier-SVG-Linien mit Scroll-Offset-Kompensation; Kategorie-Toggles (visibility:hidden, kein Grid-Reflow)
- [x] TestSeeder erweitert um UPDATE-Support; 3 neue Controller-Tests

---

### Phase 3i: Run-System — Abgeschlossen (2026-05, ungefähr, PR #141)

Roguelike-Run-Struktur mit zwei Phasen, 8 trackbaren Objectives (ursprünglich 9, `task_combat_record` mit der Flotten-Streichung entfallen) und Nexus-Interventionssystem. Playtest-Voraussetzung für Phase-4-Entscheidungen.

#### Sprint A — Kern-Infrastruktur

- [x] DB: `runs`-Tabelle (`current_tick`, `status`, `phase`, `fail_reason`, `nexus_debt`, `phase2_start_tick`) + `run_objectives`-Tabelle (`task_key`, `target_value`, `current_value`, `streak_value`, `completed_at`)
- [x] `Run`- und `RunObjective`-Eloquent-Models
- [x] `RunProgressService`: Phase-1-Check (CC Lv3 + 2 Produktionsgebäude Lv2+ + 3 Berater), `drawObjectives()` mit Combo-Blacklist (max. 1 Economy-Task), 4 Objective-Typen (Phase 1 Sprint A)
- [x] GameTick-Integration: `updateObjectiveProgress`, `checkNexusInterventions`, `checkFailStates`, `endRun`, `calculateScore`
- [x] Fail-States: Vertrauen < −20, Zeitablauf (tick_limit), Nexus-Schulden > 12.000 Cr
- [x] Sieg-Bedingung: min. 2 von 3 Objectives erfüllt
- [x] Ergebnis-Screen (`/run/{id}/result`) mit Score, Fortschrittsbalken, Sieg/Niederlage-Feedback

#### Sprint B — Vollständige Objective-Suite + Nexus

- [x] 5 weitere Objective-Typen (damals 9 insgesamt, heute 8): `task_self_sufficiency`, `task_expedition_coverage`, `task_engineering_output`, `task_trade_volume`, ~~`task_combat_record`~~ (entfernt 2026-06)
- [x] Nexus-Interventionen: Sol-30/50-Warnung, Sol-65-Berater-Sperre, Sol-80-Countdown, Schulden-Fail-State
- [x] UI: Highscore-Tabelle Lobby, Nexus-Kredit-Badge Navbar (grau/gelb/rot)
- [x] Vollständiger `newRun()`-Reset (Gebäude, Tiles, Forschungen, Advisors, Credits)
- [x] Score-Formel: `(abgeschlossen × 1000) + ((tick_limit − sol) × 10) + (credits / 10) + (vertrauen × 5)`, min. 0
- [x] Task-Keys englischsprachig gemäß CLAUDE.md-Konvention
- [x] 613 Tests grün (57 neue in Sprint B)

---

### Bewusste Designentscheidungen (nicht umsetzen in Phase 3)

| Thema | Entscheidung | Begründung |
|---|---|---|
| **Interstellare Bewegung** | Nicht implementieren | Bei einer Kolonie im Fokus findet alles im eigenen System statt. Sprungtor existiert als narratives Element. Gäste von außerhalb kommen via Events/Bar. Phase 4+ nachrüstbar. |
| **Modulare Schiffe** | Nicht implementieren | Die Kolonie steht im Vordergrund. Die 3 Schiffstypen erzeugen bereits sinnvolle Kompositionsentscheidungen. Bei 1 Tick/Tag wäre der Feedback-Loop für Modul-Fehler zu langsam. |
| **Angriffe auf Kolonien** | Nicht implementieren | Nur PvP-Schiffskämpfe (Schiff vs. Schiff). Kolonien sind kein Angriffsziel. |
| **Kolonisierung** | Nicht implementieren | Jeder Spieler hat genau eine Kolonie. |
| **Rassen-System** | Abgekündigt | Konzeptuell aufgegeben (GDD §3) — zusammen mit ENrg/LNrg/ANrg. `race_id` wird per DB-Cleanup entfernt (Phase 4), keine rassenspezifischen Effekte geplant. |
| **Gruppen/Gilden** | Zurückstellen auf Phase 4 | Kein Datenmodell vorhanden. Soziale Mechaniken entfalten erst Wert wenn eine aktive Spielerbasis existiert. |
| **Klassische Diplomatie** | Abgekündigt | Krieg/Allianz/Fraktionszustände inkompatibel mit Singleplayer-Roguelike ohne organisierte Gegner (GDD §1.1). Ersetzt durch NPC-Vereinbarungen (Phase 4) und `treaty_signed`-Events. |
| **Außenposten** | Zurückstellen auf Phase 5 | Ob das Einzelkolonie-Konzept als zu einschränkend empfunden wird, lässt sich erst nach echtem Betrieb beurteilen. |
| **Benannte Chef-Berater** | Zurückstellen auf Phase 4 | Aktuelles Berater-Modell ist als Fundament ausgelegt (GDD §12); individuelle Charaktere erst nach abgeschlossener Balance-Kalibrierung (PlaytestBot) sinnvoll. |
| **Steuersystem** | Abgekündigt | `steuerfaktor` in der Vertrauensformel ist entfernt (nicht mehr Platzhalter) — ersetzt durch die implementierte Kolonisten-Zulage (2026-07-10, GDD §14). Kein offener Punkt für Phase 4. |
| **Battlecruiser** | Abgekündigt | Schiffstypen auf Drohne/Frachter/Korvette reduziert. |
| **Fleet-Commander als separater Berater-Typ** | Abgekündigt | Entfernt im Zuge des Berater-Redesigns (GDD §12); Kommandanten-Zuweisung existiert als Fleet-Feature unabhängig davon (PR #139). |

*Einordnung (Punkt 3 der Prüfung, 2026-09-22): Diese Tabelle beschreibt dauerhaften Spiel-Scope (was das Spiel bewusst NICHT ist/hat) — inhaltlich näher an lebender Design-Wahrheit als an einer zeitlich abgeschlossenen Roadmap-Aufgabe. Empfehlung für eine Folge-Session: relevante Zeilen in `docs/GDD.md` prüfen/einpflegen (am ehesten §1.1 Spielkonzept, §3 Ressourcen/Rassen, §12 Berater/Diplomatie) — die meisten Punkte sind laut Memory-Notizen (`project_game_direction`, `project_colony_design`, `project_singleplayer_scope`) bereits an anderer Stelle im GDD verankert, ein Abgleich steht aber aus. Diese Roadmap-Tabelle bleibt zusätzlich hier stehen, da sie auch dokumentiert, warum diese Themen in Phase 3 bewusst nicht umgesetzt wurden — das ist genuine Roadmap-Information, kein Duplikat.*

---

## Phase 3 Balance — Bot-Kalibrierung & nächste Schritte

> **Fokus:** Singleplayer only. Multiplayer folgt erst in einer späteren Phase. **Kein menschlicher Playtest geplant** (CLAUDE.md, Owner-Entscheidung) — `PlaytestBot`/`game:playtest` ist das primäre und einzige vorgesehene Werkzeug für Balance-Analysen.

### Balance-Ziel

Ein automatisierter Run (Bot, ausschließlich über echte HTTP-Routen) soll die Kolonie von Beginn an auf etwa 80% ausbauen können — d.h. alle wesentlichen Gebäude bauen, Berater einstellen, Ressourcen managen, Schiffe über Nexus anfragen, Run-Objectives verfolgen und einen Run zu einem (erfolgreichen oder gescheiterten) Ende bringen, ohne an strukturellen (nicht spielerischen) Engpässen zu scheitern.

### Balance-Checkliste (Bot-Kalibrierung statt menschlichem Playtest)

- [x] **Onboarding-Hints weitgehend abgedeckt** — Sol-1–4-Rampe neu geordnet (GDD §16.2/16.3/16.5), 67 Hint-Tests grün (2026-07-14). Ein Punkt bewusst offen: `hint_2` soll von der Sol-1-Spezialformulierung zu einem generellen "Regolith-Tile erschöpft, Harvester verlegen"-Alert werden (Owner-Entscheidung 2026-08-04). **Umgesetzt 2026-09-06** als eigener Hint `hint_harvester_low_regolith` (< 30 % Restvorkommen) + Ausweichziel-Markierung auf der Karte; offen nur noch, ob `hint_2` (Sol-1-Formulierung) damit entfällt.
- [x] **Neuer Run spielbar?** — verifiziert per PlaytestBot statt menschlichem Spieler: Bot spielt komplette Runs ausschließlich über die echten HTTP-Routen; `phase2_start_sol` liegt aktuell (Stand 2026-08-13) bei 20–22 über 3 Test-Seeds. Startwerte seither mehrfach nachjustiert (Regolith-Startbestand 200→300→340). Läuft aktuell noch an `time_limit` aus (zu wenig Sole für Phase 2 übrig) statt an der strukturellen Blockade, die vorher bestand — siehe `ROADMAP.md → Aktive Arbeit → Phase 4` für den Fortgang.
- [x] **Kritische Blocker?** — systematisch über Bot- und Owner-Playtests ausgeräumt (einheitlicher 422-Fehlercontract, automatischer Techtree-Levelup + Fehleranzeige, mehrere Hint-Sackgassen behoben). Kein bekannter offener Blocker.
- [x] **INNN/Nachrichten vereinfachen** — abgeschlossen, siehe Phase 3j

### Phase 3j: Kolonieprotokoll (INNN-Redesign) — Abgeschlossen

INNN-Nachrichtensystem vollständig ersetzt. Neuer Screen `/comm-log` mit zwei Tabs — "Protokoll" (chronologisches Aktions- + Ereignis-Log, mit `×N`-Kollaps bei Wiederholungen) und "Nexus-Funk" (story-generierte Nachrichten mit Ungelesen-Badge). Player-Messaging, Inbox/Outbox, Compose, Galaxy-News entfallen. Entity-Chips (Gebäude, Kenntnis, Schiff, Ressource, Berater) als farbige Pills mit Hover-Tooltip. `colony_log`-Tabelle ersetzt `innn_events`. 725 Tests grün.

### Weitere abgeschlossene Meilensteine (2026-07 bis 2026-08, ungefähr, chronologisch)

- **07-04/05 Hangar-Missionskatalog + Schiffs-Verschleiß** (GDD §8b/§7) — 12 Missionstypen, `wear_per_sol` je Schiffstyp, Missionsdialog statt Freitext-Dispatch (PR #210/#211).
- **Cantina-Redesign** — Bar-Hintergrund (`cantina-interior.webp`) + NPC-Charaktere via `config('characters')` + Hotspot-Portraits (vor dem 07-30/31-Verhandlungs-Redesign, das darauf aufsetzt).
- **content-writer-Tonalität + lang/en-Sync** — Drei-Stimmen-System (Kolonie/Nexus-Direktiven/NexusDB-Almanach), alle `lang/de/`-Beschreibungstexte neu geschrieben, `lang/en/` vollständig synchronisiert (12 neue Dateien); globales Sci-Fi-Dialog-System (`dialogs.css`, `sol-modal`).
- **07-10/11 Kolonisten-Zulage + Kommandozentrale-Screen** — neue Spieleraktion "Kolonisten-Zulage" (Credits → Vertrauen, 3 Stufen) ersetzt das nie implementierte Steuern-Konzept; eigener Kolonie-Dashboard-Screen (Run-Fortschritt, Wartungsstau, Berater-Kurzübersicht, Vertrauens-Ereignisse).
- **07-14 Onboarding-Rampe Sol 1–4 neu geordnet** — game-designer-Spezifikation mit Budget-Rechnung, 67 Hint-Tests, GDD §16.2/16.3/16.5 aktualisiert.
- **07-17/18 Playtest-Bot** — PHPUnit-basierter Bot unter `tests/Feature/Playtest/` spielt komplette Runs ausschließlich über echte HTTP-Routen (`BotSession`/`BotStrategy`/`RunReport`-JSON-Artefakt). Deckte dabei mehrere echte Bugs auf (Session-Hard-Default Kolonie 1, ungeseedete Ziel-Ziehung, nicht persistierter Score, 200er statt 422 bei Colony-Fehlern) und legte den strukturellen Credits-Ökonomie-Kollaps nach Phase 1 offen (PR #217/#218).
- **07-19/20 Credit-Ökonomie-Balance (2-Schritt-Ticket)** — Relaisvergütung Housing→Uplink-Station umgehängt, Advisor-Upkeep abgeflacht, Rang-Schwellen gestreckt, neue Handelsvertrag-Einkommensquelle (PR #219); Harvester/Agrardom-Grundproduktion von flacher Rate auf `production_curve`-Glockenkurve mit Deckel umgestellt (PR #220). Ergebnis: `phase2_start_sol` nie erreicht → 49 → 18 — vor der §13.7-Zahlensatz-Umstellung (unten) gemessen, mit dem heutigen Stand (20–22) nicht direkt vergleichbar.
- **07-21/24 Larastan (PHPStan Level 5) auf 0 Fehler** + PHPUnit-Coverage von 70,7% auf 89,9% gebracht.
- **07-30/31 Cantina-Verhandlung + Dialog-Redesign** — zweistufiger Verhandlungsablauf (Konsul-Rang-abhängige Erfolgschance), einheitliches Cantina-Dialog-Layout mit Charakter-Portrait.
- **08-01 Design System verbindlich verdrahtet** (`docs/design-system/`) + `docs/frontend-conventions.md` löst `docs/design-guide.md` ab.
- **08-02/03 GDD-Restrukturierung** — §4b "Die drei Pfade" (Paritäts-Anforderung), §4c "Instanzen oder Level" (Wachstumsachse je Gebäude), §13.1–13.7 (AP-Pool, Ratenmodell, Regolith-Zahlensatz), Anhang A (Balance-/TODO-Index) und Anhang B (Config/Code-Drifts) neu eingeführt.
- **08-05/06 Harvester-Zweitinstanz-Bezugswege + Corvan/Pfad-C** (§4c) — Sockel-Baseline auf 1 Harvester-Instanz umgestellt, zweite Instanz optional über Weg A (Orin/`corporate_rep`, Cantina-Kauf 400–800 Cr) oder Weg B (Bergungsmission `mission_harvester_salvage`); Corvan (Reisender Händler) übernimmt Alltagsgeschäft (Credits-Handel) als Pfad-C-Hebel, anonyme Bar-Gäste nur noch Tauschhandel.
- **08-10 AP-Pool-Konsolidierung** (Phase 3o Stufe 2, GDD §13.1) — die fünf getrennten AP-Domänen sind zu einem gemeinsamen Kolonie-Pool zusammengelegt; `strategist`-Beratertyp zurückgestellt, `advisor.max_slots` 5→4 (PR #240/#241).
- **08-12 Phase-1-Sol-30-Deadline** — vierter Fail-State (`RunProgressService`), eskalierende Nexus-Warnung ab Sol 22, eigener Fail-Screen-Ton.
- **08-15 Kenntnis-Effekte, erste Welle** (PR #253) — `construction`/`cartography`/`trade` erhalten additiven Bau-AP-Rabatt (`app/Services/ProjectBonusService.php`), `agronomy` erhält Organika-Produktionsbonus (Parität zu `geology`), `trade` zusätzlich Cantina-Angebotsslot-Bonus. GDD §13.3/§13.5-Nachträge.
- **08-16 GDD §9 „Begegnungen & Gefahren" implementiert** (Branch `design/encounters-and-defense`) — drei Gefahrentypen (Sturm, Geologische Instabilität, Seuchenausbruch) erstmals codiert (§9 war zuvor nur spezifiziert); neuer `app/Services/EncounterService.php`, Cooldown-Mechanismus gegen Ereignis-Spiralen, vollständige Kolonieprotokoll-Integration, Onboarding-Hint. `defense`-Kenntnis bekommt ihren ersten aktiven Effekt (Sturm-Risiko-Reduktion), `geology` bekommt einen zweiten Effekt (Instabilitäts-Risiko-Reduktion). GDD §9-Nachtrag.

---

## Phase 3o: AP-Ratenmodell & Regolith-Balance — abgeschlossene Stufen (0, 1, 1c, 2)

*(Diese Phase heißt in der aktiven Arbeit seit der Restrukturierung 2026-09-22 „Phase 4" — hier, für die bereits archivierten Stufen, historisch als „Phase 3o" belassen.)*

Design steht im GDD (§3, §4b, §4c, §6, §13.1–13.7, Anhang A/B). Die offenen Stufen (1b, 1d, 3, 4, 5, 6) sowie die "Offene Pfad-Paritäts-Fragen" sind als D-Punkte (D1–D7) in `ROADMAP.md → Aktive Arbeit → Phase 4` zusammengeführt — dort auch die Erklärung des ID-Schemas (vormals S1b/S1d/S3/S4/S5/S6/S7, bei der Restrukturierung 2026-09-22 auf eine flache Nummerierung umgestellt).

### Stufe 0 — Klären (Owner) — Abgeschlossen (2026-08-03)

- [x] `ap_for_levelup` in der laufenden DB verifiziert: überall 10, nur Monument 20 (Migration `2026_04_17_000003` mit 10/20/30 ist nicht aktiv)
- [x] AP-Struktur freigegeben (§13.6): `ap.base = 12` statt 10, Berater 2/3/4, `f(1) = 0.5`, Boni additiv max. 42 %
- [x] Regolith-Zahlensatz freigegeben (§13.7): Harvester-Frischwert 18, Reparatur 1 Rg/SP, `decay_rate` 0,40/0,60/0,80/1,20, Errichtung 70/95/120, Level-Up flach 25; Instanz-Preisregel zurückgezogen (Instanzen zahlen vollen Errichtungspreis)
- [x] Harvester-Erschöpfung freigegeben (§4c): Ertragskurve fällt auf 50 %, `resource_max` 500/300/160, Verlegekosten 2 AP/Hex, zweite Instanz an CC Lv3 + 100 Rg
- [x] `max_instances` als eigenes Feld beschlossen (§4c)
- [x] `bar.base_prices` nach der Knappheitsordnung (§3): Rg 25 / Or 50 / Wk 110, `compound_import_price` 165
- [x] `geology`-Bonus: +3/3/2/2/2, kumuliert max 12 (§13.7)
- [x] Pfad A/Credits: `knowledge.credits` von 100 auf 0 statt vierter Einnahmequelle (§13.7)

### Stufe 1 — Zahlensatz in einem Zug — Abgeschlossen (PR #235, 2026-08-04)

- [x] Kompletter Zahlensatz aus §13.7 (Produktion, Reparatur, `decay_rate`, Bau-/Level-Up-Kosten, CC-Ausbau)
- [x] `harvester.max_level` 8 → 1
- [x] Harvester-Zweitinstanz-Gate (CC Lv3 + 100 Rg pauschal, `ColonyController::placeBuilding`) — die generische "Level-Up-Preis für jede weitere Instanz"-Regel für Hangar/Wohnhabitat bleibt offen (Bootstrap-Zirkel, siehe Stufe 1b)
- [x] `geology`-Effekt als hartverdrahteter Hook (erster von ursprünglich max. zwei erlaubten hartverdrahteten Kenntniseffekten — die Guard-Rail ist durch Owner-Entscheidung vom 2026-08-15 überholt, siehe `docs/superpowers/specs/2026-08-15-knowledge-effects-and-encounters-design.md`: mittlerweile 6 von 7 Kenntnissen mit hartverdrahtetem Effekt — `construction`/`cartography`/`trade` Bau-AP-Rabatt, `agronomy` Organika-Bonus, `trade` zusätzlich Cantina-Slot-Bonus (PR #253, 2026-08-15), `geology` zusätzlich Instabilitäts-Risiko-Reduktion, `defense` erster eigener Effekt (Sturm-Risiko-Reduktion) (Branch `design/encounters-and-defense`, 2026-08-16); `health` bewusst ohne Zusatzeffekt)
- [x] `bar.base_prices` + `compound_import_price`, `knowledge.levelup_costs` + `credits` nachgezogen
- [x] Wachstumsachsen-Umstellung (§4c) — ✅ erledigt sich durch Owner-Frage F1 (2026-09-07, siehe `ROADMAP.md`): Agrardom bleibt Level, keine Instanz-Umstellung, Config unverändert (`max_level=3`). Religiöse Stätte/Kolonialdenkmal je 1 Instanz/Lv1 ✅ 2026-08-26 (`max_level = 1`); Hangar-Doppelachse ✅ (Level 1–3 = Schiffsklasse, serverseitiges Gate seit 2026-09-04; Instanzen ungedeckelt). *Nachträglich geschlossen bei der Roadmap-Restrukturierung (2026-09-22) — der ursprüngliche Punkt blieb unangehakt, obwohl die blockierende Owner-Frage F1 bereits am 2026-09-07 beantwortet wurde.*

### Stufe 1c — Schema und Messbarkeit — Abgeschlossen (PR #234)

- [x] `max_level` aufgeteilt in `max_instances` und `max_level`
- [x] `config/buildings.php`: `harvester.max_level` angeglichen
- [x] `BotStrategy` repariert (Raumfahrer in `HIRE_ORDER`, Schiffskauf nicht mehr auf eine Drohne gedeckelt)
- [x] Instanz-Decay-Bug verifiziert und gefixt (`processBuildingDecay()` filterte nicht nach `instance_id`)

*Ein Punkt aus dieser Stufe ("Post-Phase-1-Ökonomie / Verkaufsrichtung in der Cantina — eigenes Ticket, kein Blocker mehr für den Zahlensatz") war im Ursprungsdokument trotz „Abgeschlossen"-Status der Stufe unangehakt geblieben. Er ist inhaltlich derselbe offene Punkt wie „Bar/Cantina: Verkaufsrichtung als dritter Angebotstyp" — bei der Restrukturierung (2026-09-22) nach `ROADMAP.md → Aktive Arbeit → D7` verschoben, um den Widerspruch zwischen Status und Checkbox aufzulösen, ohne den Inhalt zu verlieren.*

### Stufe 2 — AP-Pool zusammenlegen (§13.1) — Abgeschlossen (PR #240/#241, 2026-08-10)

Kernumbau. `ap_spend` existierte bereits auf `colony_buildings`, `colony_research` und `colony_ships` — die Projekt-Investition über mehrere Sole funktionierte also schon, war nur typgebunden.

- [x] Ein gemeinsamer Pool, Berater aller Domänen zahlen ein, Locks verfallen zum Sol-Wechsel
- [x] `PersonellService` entkoppelt und zu `AdvisorService` umbenannt (nach `app/Services/` verschoben); vier Domänen-Getter entfallen, `$type`-Parameter entfernt
- [x] Callsites umgestellt: `AbstractTechnologyService`, `BarService`, `HangarService`, `ColonyTileService`, `OnboardingHintService`, Controller-Layer, `MerchantService::creditAp` (`FleetService` existiert nicht mehr)
- [x] `advisors.personell_type`-Enum ohne `strategy`; `strategist`-Beratertyp zurückgestellt, `advisor.max_slots` 5 → 4
- [x] `config/game.php`: `ap.base`, `advisor.ap_per_rank`; `config/advisors.php`: `strategist` entfernt
- [x] UI: AP-Chips, Ressourcenleiste, Berater-Screen auf einen Pool
