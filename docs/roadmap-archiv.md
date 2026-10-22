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

## Aus der ROADMAP verschoben (2026-10-22)

Erledigte Punkte (`- [x]`) samt Unterpunkten, unverändert aus `ROADMAP.md` übernommen; Abschnittsüberschrift der Herkunft in Klammern. IDs bleiben gültig.


### Herkunft: P0-1 — Run-Ziele & Spielbogen (Spielspaß-Blocker)

- [x] **A45b Kreditreserve-Zielwert kalibrieren** ✅ 2026-10-01 (Fund 2026-09-27) — `task_credit_reserve` (4000 Cr/10 Sole) ist mit den bisherigen Bot-Profilen unerreichbar (max. 1590–2490 Cr gemessen). Braucht ein Bot-Profil mit gezielter Spar-Strategie (Beförderungen zurückstellen, Bar-Verkäufe/Handel priorisieren), danach den Wert neu batchen. Owner: erst Profil bauen, dann Wert setzen. — Klein/Mittel — **Ergebnis:** Belohnungen ×2, Ereignisse ×2, alle Bot-Profile sparen bei aktivem Ziel (Puffer 1,1–1,5 × Schwelle), Schwelle **6000 für 20 Sole** (Owner). Batch 2026-10-01: 8/8 erfüllt an Sol 68–72.

### Herkunft: P0-2 — Hosting & Betrieb (existiert noch nicht)

- [x] **R3 Produktions-Konfiguration** ✅ 2026-10-01 — `.env.production.example` (Werte + Deploy-Hinweise, für Laravel Cloud als Liste der zu setzenden Variablen); Dev-Kommandos `db:reset`, `game:reset-player`, `colony:seed-demo`, `game:playtest` und `DatabaseSeeder` verweigern `APP_ENV=production` (`RefusesInProduction`); fehlende Migrationen für `sessions`, `cache`, `cache_locks` angelegt (Database-Driver für Sessions/Cache inkl. R11-Lock jetzt nutzbar). — Klein
- [x] **R5b MySQL-Portierung** ✅ 2026-10-05 (Folge aus ADR 0005) — Ein Dialekt überall: Prod, CI und lokale Entwicklung laufen auf MySQL 8, Suite und CI gegen MySQL, SQLite ist kein Dialekt mehr. Ergebnis: eine Baseline-Migration statt 132 Migrationen (Äquivalenz per unabhängigem Schemavergleich belegt), idempotenter `ReferenceDataSeeder` (Golden-Dump-Gleichheit), Fixtures nur noch Spielerseite, Legacy-Tabellen/-Spalten entfernt, `GREATEST` in `HangarService`; PlaytestBot in gemeinsamer DB `nouron_playtest` (ein User pro Lauf, Guard gegen `nouron`/`nouron_test*`), Spiel-RNG nur aus `runs.rng_seed` (Seeds reproduzierbar), Deadlock-Retry im Onboarding (echter Mehrspieler-Fund). Gemessen: schnelle Suite seriell ca. 9,5–11,6 min (SQLite ca. 1,5), `--parallel --processes=4` ca. 3,5 min, Playtest-Suite ca. 20 min (SQLite ca. 5), Bot-Lauf 8–14 min, 8 parallele Bots ca. 17–18 min. Doku: `docs/dev-setup-mysql.md`, `docs/deployment-db.md`, ADR 0005 (Abschnitt „Umsetzung R5b“). Folgepunkt: R4 gemäß `docs/deployment-db.md`. — Groß
- [x] **R5 Produktions-Datenbank** ✅ 2026-10-02 — entschieden in **ADR 0005**: Laravel Cloud + Laravel MySQL (Postgres und VPS+SQLite verworfen), Region EU-Frankfurt, bis 24 h Datenverlust akzeptabel. Folgearbeit: R5b. — Mittel
- [x] **R8 CI** ✅ 2026-10-01 — `.github/workflows/ci.yml`: auf jedem PR und Push nach `master` Pint (`--test`) sowie PHPUnit getrennt für Standard-Suiten (feature + unit) und Playtest-Suite. Erster echter Lauf auf GitHub steht noch aus (lokal geprüft: Suite-Auswahl, `.env.example`-Werte). — Klein

### Herkunft: P0-3 — Zugang

- [x] **R9 Einladungs-Code bei Registrierung** ✅ 2026-09-30 — Owner-Wahl: ein gemeinsamer Code. `BETA_INVITE_CODE` (→ `config('auth.invite_code')`); gesetzt = Pflichtfeld im Registrierungsformular, Vergleich per `hash_equals` (Leerzeichen getrimmt), leer = Registrierung offen (Dev/Tests). `/register` wie Login gedrosselt (5/min) gegen Code-Raten. Tests: `RegistrationInviteCodeTest`. — Klein
- [x] **R10 Passwort-Reset** ✅ 2026-09-30 — Owner-Wahl: Admin-Kommando jetzt, Mail-Reset später (R20). `php artisan user:reset-password <username|email>` setzt ein zufälliges 16-Zeichen-Passwort und gibt es einmal aus. Tests: `UserResetPasswordTest`. Owner-Account in Produktion mit `role = admin` anlegen → als Schritt in R4 (Deployment). — Klein

### Herkunft: P0-4 — Bugs mit direkter Spielerwirkung

- [x] **T21 `HangarController::index` prüft `building_id = 1` als Kommandozentrale** ✅ 2026-09-30 (Fund T14, 2026-09-25) — richtig ist 25 (`BuildingId::CommandCenter`); Nexus-Kredit dadurch vermutlich nie verfügbar. Echter Bug. — Klein — Fix: `BuildingId::CommandCenter`, Tests für beide Richtungen.
- [x] **T22 `requestShip` weist geliefertes Schiff auch einem zu niedrigen Hangar zu** ✅ 2026-09-30 (Fund T14) — Schiff wäre sofort inaktiv; passenden Slot wählen oder ablehnen. — Klein — Fix: Slot-Suche in `HangarService::requestShip` berücksichtigt `requiredHangarLevel`; ist nur ein zu niedriger Hangar frei, geht das Schiff in `pending`. Nebenbefund mitbehoben: Nexus-Kredit-Schuld wurde auf den ersten beliebigen aktiven Run gebucht statt auf den des anfragenden Spielers.
- [x] **R11 `/sol/next` gegen Doppelausführung sichern** ✅ 2026-09-30 — `SolController::next` erhöht `current_tick` vor dem Tick ohne Sperre/Transaktion; Doppelklick oder zwei Tabs können zwei Sole auf einmal auslösen. Lock pro Run (z. B. `Cache::lock`) + Frontend-Button während des Requests deaktivieren, TDD. — Klein — Fix: `Cache::lock` pro Run (409 `sol_advance_in_progress`), Button während des Requests gesperrt, 409 lädt die Seite neu.
- [x] **R12 Ownership-Check `/resources/colony/{id}`** ✅ 2026-09-30 — `JsonController::getColonyResources` prüft nicht, ob die Kolonie dem eingeloggten User gehört (fremde Ressourcen lesbar). Prüfen/403, TDD; bei der Gelegenheit alle Routen mit ID-Parametern auf dasselbe Muster durchsehen (qa-tester). — Klein — Fix: 403 bei fremder Kolonie. Weitere Routen mit ID-Parametern noch nicht systematisch durchgesehen → siehe R19.
- [x] **R18 GameTick pro Run statt global (Mehrspieler-Blocker)** ✅ 2026-09-30 (Fund 2026-09-30) — `game:tick --run=X` (ausgelöst durch `/sol/next`) wählt nur den Sol aus dem Run, fast alle Schritte laufen aber über **alle** Kolonien/User (`Colony::all()`, `Advisor::whereNotNull('colony_id')`, Decay, Ressourcen, Nahrung, Supply, Trust, Credits, Berater-Unterhalt, Bar, Händler, Hangar-Lieferungen/-Missionen, Nexus-Importe). Folge: Jeder Sol-Klick eines Spielers simuliert alle anderen Kolonien mit. Zusätzlich bucht `deductAdvisorUpkeep()` Unterdeckung auf den ersten beliebigen aktiven Run. Alle Schritte auf Kolonie/User des Runs einschränken, Mehrspieler-Regressionstest (zwei aktive Runs, nur einer tickt). Größter technischer Beta-Blocker. — Groß — Umgesetzt: alle Schritte (Lieferungen, Nexus-Importe, Missionen, Verfall, Ressourcen, Nahrung, Encounters, Überkapazität, Supply, Trust, Credits, Unterhalt, Berater-Ticks, Bar, Händler) auf `runs.colony_id` eingeschränkt, Unterdeckung beim Unterhalt geht auf den eigenen Run; Regressionstest `GameTickRunIsolationTest` (zwei aktive Runs, nur einer tickt).
- [x] **R19 Mehrspieler-Isolation prüfen** ✅ 2026-09-30 (nach R18, qa-tester) — alle Routen mit ID-Parametern und alle `runs`/`colonies`-Abfragen ohne User-Filter systematisch auf Fremdzugriff/Fremdwirkung prüfen (Muster: T22-Nebenbefund, R12, R18). — Mittel — Ergebnis: alle 16 Routen mit ID-Parametern prüfen den Eigentümer (Service-Lookups mit `colony_id`/`user_id`, `abort(403)` bei Run/Lobby); kein Controller nimmt `colony_id`/`user_id` aus dem Request; keine ungefilterten Spieler-Abfragen außerhalb der Dev-Kommandos. Neue Regressionstests `ForeignColonyActionTest` für die 5 bisher ungetesteten Pfade (Cantina-Anliegen, Informations-Begegnung, Händler-Kauf/-Besuch, Hangar-Zuweisung), per Mutation geprüft. `ColonyService::getColonies()` (Altkonzept, ungenutzt) samt Test entfernt. `TickService::calculationIsRunning()` (ebenfalls Altcode) samt Config-Key und Debug-Anzeige entfernt.

### Herkunft: P1 — Spielerlebnis & Feedback

- [x] **R15 Feedback-Kanal im Spiel** ✅ 2026-10-01 — Button „Feedback“ unten rechts in beiden Layouts (mobil nur Icon), `sol-modal`-Dialog mit Kategorie (Fehler/Balance/Idee/Sonstiges) und Freitext; `POST /feedback` (5/min) speichert in Tabelle `feedback` mit serverseitigem Kontext (User, aktiver Run, Sol, Seitenpfad, User-Agent). Admin-Übersicht unter `/admin/feedback` (nur `role = admin`). Kein Mailversand (folgt ggf. mit R20). Tests: `FeedbackTest`, Sichtprüfung per Playwright. — Mittel
- [x] **T25 Restliche Endpoints mit Klartext im `error`-Feld** ✅ 2026-10-01 (Fund T11) — `AdvisorController` ~Z. 392 (`'Not found.'`), `MerchantService::buyItem` (hartkodiertes Deutsch), `HangarController` recall/repair/request (Exception-Text). Auf Code + `message` umstellen. — Klein — Umgesetzt: `HangarService` wirft nur noch `GameRuleException` (Code + übersetzte Meldung, 22 Codes), `HangarController` antwortet einheitlich `{error: code, message}` und loggt unerwartete Ausnahmen nur noch (generischer Code `hangar_action_failed`, kein Exception-Text nach außen); `MerchantService::buyItem` und `AdvisorController::fire` ebenso. Bar-Händlerkauf und `advisors.js` zeigen `message`. Tests: `ErrorResponseContractTest`.

### Herkunft: Owner-Fragen (F1–F8, alle beantwortet — Kontext für laufende Punkte)

- [x] **F1 Agrardom: Level oder Instanz?** ✅ 2026-09-07 (C9) — Entschieden: bleibt Level, Config unverändert (max_level=3), keine Instanz-Umstellung. Einziger Task war die GDD-Korrektur (game-designer, parallel in Bearbeitung).
- [x] **F2 Instanz-Zerstörung bei Decay** ✅ 2026-09-07 (C2) — Entschieden: Instanzen werden nie gelöscht (bestätigter Ist-Zustand, kein Codeänderung nötig). NEU beschlossen: Reparatur-Hysterese ersetzt binäres `status_points > 0`-Modell — Effekte/Boni fallen bei 0 Status Points aus und kehren erst ab ~50 % Reparatur zurück. Hangar-Schiffe bleiben bewusster binärer Sonderfall (nicht anfassen). Umsetzungs-Tasks siehe **A19/A20**.
- [x] **F3 Upkeep-Defizit → Nexus-Schulden?** ✅ 2026-09-07 (C1) — Entschieden: `nexus_debt` bleibt bestehen, Upkeep-Defizit fließt künftig hinein statt zu verpuffen. Konsul-„Handelsvertrag" entfällt ersatzlos. Berater-Beförderung wird von automatisch auf manuell (spielerausgelöst) umgestellt. Umsetzungs-Tasks siehe **A21/A22/A23** (drei zusammenhängende, aber separat umsetzbare Tasks — keiner ist Voraussetzung für einen anderen, aber A21 sollte zuerst gehen, da A8 daran hängt).
- [x] **F4 Harvester Konstant-Yield-Spec** ✅ 2026-09-08 (A16) — Entschieden: Spec wird umgesetzt (konstante Rate statt Rampen-Formel), inkl. §13.7-Neuherleitung. GDD wird parallel nachgezogen (§4c Formel, §13.7 als veraltet markiert). Umsetzungs-Tasks siehe **A24/A25/A26/A27**.
- [x] **F5 Kanal 2 Nexus-Handelsschiffe** ✅ 2026-09-08 (A12) — Entschieden: Kanal 2 wird gestrichen, Funktion wandert in einen Uplink-Direktimport für Nicht-Werkstoff-Ressourcen (Lieferzeit 3-5 Sol, reduzierbar über Uplink-Station-Level als einzigen Hebel). Werkstoff-Sofortkauf bleibt unverändert. Umsetzungs-Tasks siehe **A28/A29**.
- [x] **F6 Roguelike-Kenntnis-Teilmenge pro Run** ✅ 2026-09-08 (A10) — Entschieden: wird komplett gestrichen, auch nicht abgeschwächt (bei nur 7 Kenntnissen zu hohes Risiko einer essentiellen Kenntnis-Lücke pro Run). GDD wird bereinigt (§10-Abschnitt entfernt, §8b-Begründung angepasst/gestrichen). Kein neuer Umsetzungs-Task — reine Streichung.
- [x] **F7 Phase-1-Bedingung** ✅ 2026-09-08 „2 Produktionsgebäude" vs. „2 Nicht-CC-Gebäude" (C4) — Entschieden: Code (`RunProgressService::checkPhase1Completion()`, zählt jedes Nicht-CC-Gebäude ≥ Lv2) ist korrekt und bleibt unverändert; irreführender Docblock-Kommentar bereits gefixt. Kein neuer Umsetzungs-Task.
- [x] **F8 Playtest-Instrumentierung** ✅ 2026-09-08 freigegeben (A17) — `docs/playtest-instrumentation-plan.md` ist vollständig überarbeitet (alle 3 Unterentscheidungen entschieden). Umsetzungs-Tasks siehe **A30/A31/A32/A33**.

### Herkunft: A — Design vorhanden, Implementierung fehlt (TDD-Pflicht)

- [x] **A4** ✅ 2026-09-06 — `decay.overcap_factor` 2.0 → 1.5 (§13.1) — Klein
- [x] **A5** ✅ 2026-09-06 — `bar.ap_cost_accept` 1 → 2, `ap_cost_negotiate` 3 → 4 (Stufe 3) — Klein
- [x] **A6 Trust-Warnstufen** ✅ 2026-09-20 (§18.2) — Trust-Chip stuft sich jetzt ab: neutral, gelb unter 0 (Vorstufe), rot unter −10; Nexus-Funk-Warnung `run.nexus_trust_critical` unter −18, einmal pro Run und nur solange nicht im selben Tick der Fail State (−20) greift (`RunProgressService::checkTrustWarnings()`, Schwellen in `config/game.php → run.trust_warning`). Nebenbefund: die Liste der Nexus-Event-Schlüssel steht dreifach im Code (`RunProgressService::createEvent`, `EventService`, `CommLogController`) — ein neues Event muss überall eingetragen werden, sonst erscheint es nie im Nexus-Funk (Test `test_nexus_includes_trust_critical_warning_from_run_area` sichert das). TDD, echte Browser-Verifikation.
- [x] **A8** ✅ 2026-09-07 — Nexus-Schulden-Rückzahlungs-Sondermechanik (§18.2) erledigt sich strukturell durch F3-Entscheidung (`nexus_debt` bleibt als laufender Fehlbetrags-Topf, keine separate Rückzahlungsmechanik nötig) — obsolet, kein eigener Task mehr
- [x] **A10** ✅ 2026-09-08 — Kenntnis-Teilmenge pro Run (§10) erledigt sich strukturell durch F6-Entscheidung (Streichung, kein Umsetzungsauftrag) — obsolet, kein eigener Task mehr
- [x] **A12** ✅ 2026-09-08 — Kanal 2 Nexus-Handelsschiffe erledigt sich strukturell durch F5-Entscheidung (Streichung zugunsten Uplink-Direktimport) — obsolet, kein eigener Task mehr, siehe A28/A29
- [x] **A13 Handel/Konsul-Komplex** ✅ 2026-09-22 (§4, §10, §12, §13.3; Ausgangspunkt: Handelsposten „Konsul-Effizienz") — wuchs 2026-09-20 nach zwei game-designer-Reviews zu einem Komplex aus. **Owner-Entscheidungen:** „Konsul-Effizienz" wird gestrichen; Kenntnis `trade` senkt KEINE Bau-AP mehr (thematisch falsch); der Konsul bekommt zusätzlich zu AP aktive Bonus-Effekte (Schiffsverhandlung, Marktbericht; „Wunschangebot" nur Ideenpool); Verhandeln soll sich „mal lohnen, mal nicht"; Handelsposten Stufe III wirkt auf den Nexus-Direktimport-Preis; **Verständlichkeit für den Spieler hat hohe Priorität** (Angebotsdialog zeigt Basis, Vorteil je Quelle, Chance und Ergebnis); Annehmen und Verhandeln kosten beide 2 AP (Owner-Idee, Designer bestätigt und neu kalibriert). Konzept im GDD §12 „Handelsvorteil" — Implementierung P1–P8 abgeschlossen.
  - [x] **P1** `trade` aus dem Bau-AP-Pool entfernt (Pool nur noch `construction`, max. 15 %) ✅ 2026-09-20
  - [x] **P5** rangskalierte Konsul-Schiffsverhandlung (`game.hangar.consul_ap_discount` 50/60/70 Cr/AP; Anzeige- und Ausführungspfad aus einer Quelle; Verhandlung ohne Konsul wird jetzt abgelehnt statt still rabattiert) ✅ 2026-09-20
  - [x] **P6** Marktbericht (Konsul kündigt Corvans Besuch 1/2/3 Sol vorher an, Rang 3 mit Sonderinventar-Kategorien; `MerchantService::getForecast()`, Hinweis im Cantina-Screen) ✅ 2026-09-20
  - [x] **P2a** Dienst „Handelsvorteil" (`TradeAdvantageService`): Quellen (Konsul-Rang, Handelsposten, `trade`) additiv, Basiskonditionen statt eingebackenem `trader_discount`, Festpreis-Verkaufslose, Anzeige == Ausführung (`BarService::effectiveTerms`); Migration löscht offene Alt-Angebote ✅ 2026-09-20
  - [x] **P3** Verhandeln neu: 2/2 AP, Chance 60/65/70 % (+ `trade`-Chance-Bonus Σ8 pp, Deckel 95 %), Aufschlag konstant +20 % additiv, `is_negotiated`-Flag statt eingefrorener Mengen, Handelsposten wirkt auch bei verhandelten Angeboten, Kalibrier-Test „nicht immer dominant" ✅ 2026-09-21
  - [x] **P2b** Angebotsdialog mit Quellen-Aufschlüsselung, Chance-Aufschlüsselung, Hinweise für fehlende Quellen, Cantina-Kopfzeile, Sonderinventar-/Orin-Preiszeilen, Toast-Fix (Presenter `TradeAdvantagePresenter`/`BarOfferDialogPresenter`) ✅ 2026-09-21
  - [x] **P4** Nexus-Kanal / Handelsposten Stufe III (Direktimport-Preis, `NexusImportService`, Handelsvorteil-Kanal `nexus` statt `corporate_contact`, Vorschau-Pfad über `quote()`) ✅ 2026-09-22
  - [x] **P7** Doku-Nachzug ✅ 2026-09-22 — `docs/GDD.md` (§4, §8b, §12, §13.4): alle „Konzeptstand A13"/„noch nicht im Code"-Vermerke entfernt bzw. auf Ist-Zustand umformuliert; `docs/game-reference.md` neuer Abschnitt „7a. Handel & Konsul (A13)" (Handelsvorteil-Kanäle, Konsul-Rang-Rabatt, Cantina-Verhandlung, Schiffsverhandlung, Marktbericht) + `trade`-Kenntnis-Zeile und Trading-Post-Zeile korrigiert.
  - [x] **P8** Bot-Batch (8 Seeds, `default`-Profil) ✅ 2026-09-22 — 7/8 Runs abgeschlossen (2/3 Ziele, Score 2516–2760), 1/8 `fail_reason=time_limit`; keine Fehler/Exceptions im neuen Handels-/Verhandeln-Code. Kein Regressionsverdacht gegenüber der Erfolgsrate vor A13.
  - Offen/nicht entschieden: `trade` als reine Pfad-C-Kenntnis akzeptieren; Totalverlust-Regel bei Verhandeln (Default: beibehalten); cartography-Rundungsgrenzfall (Lv1–3 bei Basiskosten 1–3 wirkungslos); Vermerk „Schiff" fehlt im Sonderinventar-Pool (GDD nennt es als Kategorie).
- [x] **A14 Überkapazität** ✅ 2026-09-24 (§6/§7) — Modell „Unterbesetzung" (Owner-Entscheidung 2026-09-24, ersetzt die zuerst gebaute Stufenverlust-Variante): Obdachlose Kolonisten → eskalierender Vertrauens-Malus ab Sol 1, nach der Frist wandern alle Obdachlosen ab (`glx_colonies.overcap_departed`, `colonists_left`), danach senkt der Personalanteil Regolith-/Organika-Produktion und Nahrungsbedarf, Bauen/Ausbauen gesperrt bis Wohnraum frei; neuer Wohnraum holt Kolonisten automatisch zurück. „Wegschicken" als AP-Aktion im Kolonisten-Popup (`colonists_dismissed`). `decay.overcap_factor` gestrichen. Supply-Gate jetzt auch beim Ausbau und für den zweiten Harvester; platzierte Stufe-0-Gebäude reservieren ihre ersten Arbeitsplätze. CC-Kapazitätsbeitrag bleibt flach (Owner), tote Config-Keys `supply.cap_*` entfernt. Nebenbei: Techtree-`leveldown`/`levelup` instanzgenau (`instance_id`), Rückbau kostenlos ohne Levelup-Voraussetzungen, Rückbau auf Stufe 0 gibt das Tile frei, Ausbau ohne Tile gesperrt (`not_placed`). PlaytestBot-Fix (Sol-0-Tick im Test-Harness, Agrardom vor CC, Supply-Gate): 7/8 Runs abgeschlossen statt 0/8.
- [x] **A15 Kolonisten-Framing** ✅ 2026-09-20 (§6) — Chip zeigt „KOL belegt / Kapazität" statt „SUP frei / Kapazität" (Owner-Entscheidung), rot bei Überschreitung; Popup mit „Frei"-Zeile und korrigiertem Text (Schiffe kosten seit 2026-06-08 kein Supply mehr), Gebäudekosten „KOL", Onboarding-/Fehlertexte, Lobby und Nexus-DB auf Kolonisten umgestellt. `task_self_sufficiency` bewusst als „Kolonisten-Kapazität > 0" formuliert (prüft die Kapazität, nicht den freien Rest).
- [x] **A16** ✅ 2026-09-08 — Konstant-Yield-Spec entschieden (F4: umsetzen) — Umsetzungs-Tasks siehe A24/A25/A26/A27
- [x] **A17** ✅ 2026-09-08 — Playtest-Instrumentierung 11 Metriken in `RunReport` freigegeben (F8) — Umsetzungs-Tasks siehe A30/A31/A32/A33
- [x] **A18** `mission_perimeter_patrol` in `config/missions.php` aufgenommen ✅ 2026-09-22 (§8b) — Korvette, Voraussetzung Verteidigung Lv1, Reward `trust_event: encounter_won` (Platzhalter, `encounter_prep`/Almanach §17 existiert noch nicht — Kommentar verweist auf den Swap, sobald §17 kommt)
- [x] **A19/A20** ✅ 2026-09-08 — Reparatur-Hysterese-Idee (50 %-Schwellenwert) verworfen zugunsten einer einfacheren Lösung: Gebäude-Level-Down bei SP=0 existiert bereits (`GameTick::applyLevelDown()`, SP-Reset auf Max bei jedem Level-Down) und skaliert Effekte automatisch über das aktuelle Level — kein neuer Suspendier-/Rückkehr-Mechanismus nötig, damit auch kein Kaskaden-Risiko (A20 entfällt ersatzlos). Einzige Ergänzung: Level darf nie unter 1 fallen (`max(0, …)` → `max(1, …)`), ein platziertes Gebäude verschwindet nie durch Verfall. Umgesetzt (TDD: `GameTickDecayTest::test_building_level_floors_at_one_and_never_reaches_zero_via_decay`), GDD §7 entsprechend korrigiert.
- [x] **A21** ✅ 2026-09-09 — Nexus-Schulden: Upkeep-Defizit einspeisen. `GameTick::deductAdvisorUpkeep()` aggregiert Upkeep jetzt pro User, klemmt Credits weiterhin auf ≥ 0, addiert den Fehlbetrag zu `nexus_debt` (aktiver Run) statt ihn verpuffen zu lassen. TDD: `GameTickCreditsTest::test_advisor_upkeep_shortfall_flows_into_nexus_debt` + `test_advisor_upkeep_no_shortfall_when_credits_suffice`. `nexus_debt_fail_threshold` (12000) bewusst unverändert gelassen — Neukalibrierung ist eine Balance-Frage, siehe **A34**.
- [x] **A34 Nexus-Schulden-Threshold neu kalibrieren (Playtest)** (nach A21) ✅ 2026-09-13 — **Ergebnis: kein Änderungsbedarf, 12000 bleibt.** `RunReport` erfasst jetzt `nexus_debt` pro Sol (vorher gar nicht getrackt). Erster 6-Seed-Batch zeigte `nexus_debt` eingefroren bei 3000 — Ursache: PlaytestBot befördert Berater nie (bekannte Lücke seit A23), Rang-1-Upkeep ist zu günstig, um das A21-Defizit je auszulösen. Bot um `promote_advisor`-Regel ergänzt (`BotStrategy.php`), danach 8-Seed-Batch wiederholt: `nexus_debt` bewegte sich (max. 4265 von 8 Seeds), blieb aber weit unter 12000 — auch analytisch bestätigt (Worst-Case 4 Berater Rang 3 = 140 Cr/Tick Upkeep vs. max. 185 Cr/Tick passives Einkommen bei Uplink Lv3, strukturell gedeckt). Zwei Nebenbefunde für Folge-Tasks: (1) keiner der 8 Runs hat gewonnen (alle `time_limit`, 0-1/3 Ziele) — eigenständiges, größeres Balance-Problem; (2) Nicht-Determinismus entdeckt (identischer Seed, unterschiedliches Ergebnis solo vs. parallel) — Ursache noch offen, eigener Folge-Task.
- [x] **A22** ✅ 2026-09-09 — Konsul-„Handelsvertrag" entfernt. `config/game.php → credits.consul_contract_income_per_rank` gelöscht, `GameTick::generatePassiveCredits()` + `GameTickDryRun` Berechnung entfernt, `docs/game-reference.md` Konsul-Rang-Tabelle korrigiert. TDD: bestehende Contract-Income-Tests ersetzt durch `GameTickCreditsTest::test_no_konsul_contract_income_regardless_of_cantina_and_rank` + `GameTickDryRunTest::test_cantina_konsul_never_shows_contract_income`. Volle Suite grün (1147 Tests).
- [x] **A23** ✅ 2026-09-09 — Berater-Beförderung automatisch → manuell umgestellt. `GameTick::incrementAdvisorTicks()` promotet nicht mehr automatisch (nur noch `active_ticks`-Increment, kein Cap). Neu: `AdvisorService::promote()` (Ownership-/Threshold-/Credits-/Max-Rank-Prüfung), `POST /advisors/{id}/promote`-Endpoint, "Befördern"-Button im Berater-Screen (erscheint sobald Threshold erreicht). TDD: `AdvisorPromotionManualTest` (6 Tests), `AdvisorControllerTest`-Ergänzung (3 Tests), `GameTickAdvisorTest`/`AdvisorServiceTest` auf Kein-Auto-Promote-Regression umgeschrieben, alte `AdvisorPromotionCostTest` (testete nur entfernte Automatik) gelöscht. Volle Suite grün (1148 Tests).
- [x] **A24** ✅ 2026-09-09 — `GameTick::harvesterYield()` auf konstante Rate umgestellt (Rampen-Formel entfernt, laut Spec `docs/superpowers/specs/2026-08-10-harvester-constant-yield-design.md`). TDD: `HarvesterDepletionTest` umgeschrieben (rot → grün), Config-/Test-Kommentare korrigiert. GDD §4c/§13.7 bereits vorab aktualisiert (F4-Doku-Runde). Volle Suite grün (1148 Tests).
- [x] **A25** ✅ 2026-09-09 — `sols_remaining`-Berechnung + Serialisierung. Neue `ColonyTileService::solsRemaining()` (pure, `ceil(resource_amount / effektive Rate)`, null bei erschöpft/Rate 0), in `ColonyController::hexview()` pro aktivem Harvester-Tile mitgeliefert (Geologie-Level + Trust-Multiplikator einmal pro Colony berechnet, nicht pro Tile). TDD: `ColonyTileServiceSolsRemainingTest` (7 Tests) + `ColonyViewTest`-Ergänzung (2 Tests). UI-Anzeige folgt in A26. Volle Suite grün (1157 Tests).
- [x] **A26** ✅ 2026-09-11 — UI „≈N Sole bis Erschöpfung" im Tile-Panel (`hexview.blade.php`), direkt unter der Regolith-Vorkommen-Zeile. Warnfarbe (`--warn`-Modifier) bei ≤3 Sole verbleibend. Neuer Lang-Key `colony.harvester_sols_remaining_label`. TDD: Markup-Smoke-Test in `ColonyViewTest` (Alpine-Bindung, tatsächlicher Wert ist clientseitig — kein Browser-Test in dieser Umgebung möglich, nur serverseitige Markup-/Daten-Verifikation). Volle Suite grün (1158 Tests).
- [x] **A28** ✅ 2026-09-11 — Uplink-Direktimport für Regolith/Organika (neue Tabelle `nexus_imports`, Anfrage+Bezahlung sofort via `ColonyController::nexusImportResource()`, Lieferung via `GameTick::processNexusImportDeliveries()`). Preise (35/65 Cr, ~1,3-1,4× Spotpreis statt Werkstoff-1,5×, da die Wartezeit selbst schon der Umgehungs-Preis ist) und Lieferzeit-Staffel (Lv1=5/Lv2=4/Lv3=3 Sole) mit game-designer abgestimmt. UI-Widget im Command-Center-Screen. TDD: `NexusDelayedImportTest` (7 Tests) + `CommandCenterTest`-Ergänzung. Echte Browser-Verifikation (Playwright). Volle Suite grün (1166 Tests).
- [x] **A29** ✅ 2026-09-11 — Keine INNN-Restverweise mehr im GDD (bereits durch die F5-Doku-Runde erledigt, verifiziert per grep).
- [x] **A30** ✅ 2026-09-12 — Playtest-Instrumentierung Phase B1 (Extended Snapshots) komplett. `RunReport::snapshot()`/`BotSession::act()` (`tests/Feature/Playtest/`) um 5 Teilaufgaben erweitert: **B1a** AP-Bilanz nach Kategorie (repair/project/action, aus regelname-klassifizierten `ap_before`/`ap_after`-Deltas je Aktion, da AP seit Pool-Konsolidierung ungetaggt ist); **B1b** Building-Level+AP-Spend pro Instanz; **B1c** Regolith-/Organika-Quellen (harvester/agrardom/mission/trade/event, Mission-Rewards aus echten `hangar.mission_completed`-Colony-Log-Events statt geraten, Organika-Verbrauch in `hunger_consumed`/`mission_dispatch_consumed` aufgeschlüsselt via `ResourcesService::foodNeed()`); **B1d** Supply-Snapshot (wiederverwendet `ResourcesService::getSupplyBreakdown()`, keine zweite Formel); **B1e** Harvester-Positions-Tracking. B1b/B1d/B1e parallel per Subagenten in Worktrees erarbeitet, Diffs von Hand zusammengeführt (alle drei trafen dieselbe Snapshot-Stelle). TDD durchgängig, reines Test-Harness (kein Game-Code). Volle Playtest-Suite (22 Tests, echter Bot-Lauf) + Kern-Suite (1160 Tests) grün.
- [x] **A31** ✅ 2026-09-12 — Playtest-Instrumentierung Phase B2 (Post-Lauf-Aggregation) komplett. `RunReport::build()` (`tests/Feature/Playtest/RunReport.php`) um 3 Teilaufgaben erweitert: **B2a** Projekt-Metriken (`project_durations`, `last_completion_sol`, `median_concurrent_projects`) rein aus den B1b-Building-Snapshots abgeleitet, keine neuen DB-Reads; **B2b** Regolith-Pfad-Attribution (Metrik 9, Summe der B1c-Quellen-Buckets über den ganzen Run je Pfad A/B/C — bekannte Einschränkung: Pfad-A-Anteil im 'harvester'-Bucket noch mit Basis-Ertrag vermischt, Trennung bräuchte ein zusätzliches Pro-Sol-Geologie-Level-Snapshot, außerhalb des Scopes); **B2c** Sole-mit-0-AP-Filter (nur Gesamtzahl, keine Domänen-Aufschlüsselung — moot seit Pool-Konsolidierung, F8/2). B2b/B2c parallel per Subagenten in Worktrees erarbeitet, Diffs von Hand zusammengeführt. TDD durchgängig, reines Test-Harness. Volle Playtest-Suite (29 Tests, echter Bot-Lauf) + Kern-Suite (1160 Tests) grün.
- [x] **A32** ✅ 2026-09-12 — Playtest-Instrumentierung Phase C, MVP-Charts (3 von 7 laut Plan, Rest bleibt iterativ). `tools/playtest-dashboard.php`: neuer "Detail-Charts"-Bereich mit AP-Bilanz (Stacked Area: Reparatur/Projekt/Handlung/Ungenutzt), Regolith-Quellen (Stacked Area: Harvester/Mission/Handel/Ereignis) und Supply-Auslastung (Line + 70%-Zielmarke) für den im Log-Panel gewählten Einzel-Lauf — Stacked-Charts überlagern über mehrere Läufe nicht sinnvoll. Kein PHPUnit (reines Chart.js-Dev-Tool ohne Testsuite), echte Browser-Verifikation via Playwright mit frischem `game:playtest`-Report. Verbleibende 4 Charts (Organika-Quellen, Ongoing Projects, Project Timeline/Gantt, Harvester Moves) laut Plan explizit "iterativ später" — kein neuer ROADMAP-Task nötig, bei Bedarf ergänzen.
- [x] **A33** ✅ 2026-09-12 — Playtest-Instrumentierung Phase D (Summary-Tabellen-Erweiterung) komplett. `tools/playtest-dashboard.php` Summary-Tabelle um 6 Spalten erweitert: AP Ungenutzt %, Rego Top-Quelle, Supply Max %, Projekte-Count, Harvester-Umzüge, Letztes Projekt (Sol) — alle aus den A30/A31-Snapshot-Daten abgeleitet, kein neuer Report-Export nötig außer `project_metrics`-Durchreichung. Damit ist die gesamte Playtest-Instrumentierung (A30-A33) abgeschlossen. Echte Browser-Verifikation via Playwright.
- [x] **A35 Cantina-Begegnungspool: mechanisches Grundgerüst** (nach A22, §12) ✅ 2026-09-12 — Ersatz-Hebel für den Pfad-C-Credits-Hebel (Owner-Entscheidung 2026-09-09, GDD §4b/§12) implementiert: ein gemeinsamer Ereignis-Slot (`bar_encounters`), einzelner Roll (`BarService::generateEncounterForColony()`, skaliert über `game.bar.encounter.spawn_chance_per_level`, NICHT Konsul-Rang), drei Ausgänge — Wette (Zara, Einsatz gegen Chance-Auszahlung, Totalverlust bei Fehlschlag), Auktion (Voss, garantierte Einmalzahlung), Kurzzeit-Kontrakt (generisch, Credits/Sol über `GameTick::processBarEncounters()` bis `ends_tick`). TDD (`BarEncounterTest`, `GameTickBarEncounterTest`, `BarEncounterControllerTest`), UI im Cantina-Screen (Hotspot + Dialog, echte Browser-Verifikation via Playwright). Werte sind ein erster Entwurf, keine kalibrierte Balance (analog A34).
- [x] **A36 Cantina-Begegnungspool: volle Charakter-Zuordnung** (nach A35, content-writer) ✅ 2026-09-13 — 9 der 11 verbleibenden Figuren zugeordnet (Deva/veteran, Lenn/ai_researcher bewusst zurückgestellt, Owner-Entscheidung, `game_role: information` reserviert für Folge-Task). `config/characters.php` bekam ein `game_role`-Feld je Figur (bar_trade/story_hook/dedicated/permanent/information). 6 `bar_trade`-Figuren (Fen, Maret, Juno, Dax, Vesper, Sarka) bekamen eine personalisierte Dialogzeile im bestehenden Gäste-Tauschangebot (kein neuer Mechanismus). 3 `story_hook`-Figuren (Sorel, Aldra, Stranger) bekamen eine eigene, rein narrative Begegnung ohne jede Ressourcen-/Credits-Wirkung (`BarService::pickStoryEncounter()`, deterministisch pro Sol) — Leitplanke damit eingehalten: keine dieser Figuren erzeugt ein neues Ressourcen-Event. Nebeneffekt-Fix: story_hook-Figuren erscheinen nicht mehr fälschlich als Flavor eines Tauschangebots (fallen auf anonymen Gast zurück). TDD (`BarStoryEncounterTest`), echte Browser-Verifikation via Playwright.
- [x] **A37 PlaytestBot gewinnt keinen einzigen Run** (Nebenbefund A34, 2026-09-13) ✅ 2026-09-16 — 8-Seed-Batch (`default`-Profil, inkl. Berater-Beförderung): alle 8 Runs `fail_reason=time_limit`, 0-1/3 Ziele erreicht. Eigenständiges, größeres Balance-Problem — braucht eigene Analyse (welches Ziel scheitert warum, AP-/Ressourcen-Engpass oder Bot-Strategie-Schwäche), nicht Teil von A34/A35/A36. — Groß
  - **Teilbefund + Bot-Fix (2026-09-13):** `task_research_lead` (3 Kenntnisse auf Lv5, `RunProgressService::updateResearchLead()`) war im Bot strukturell unerreichbar. `RunReport` erfasst jetzt Kenntnis-Level pro Sol (vorher gar nicht getrackt, `RunReportResearchSnapshotTest`). Ein voller 99-Sol-Lauf (Seed 1001) zeigte zunächst: nur 1 von 7 Kenntnissen je angefasst (`knowledge_construction`, Lv4 erreicht), die anderen 6 blieben bei Lv0, trotz 63% täglich verfallener AP (2044 Zufluss, nur 755 verbraucht — AP ist NICHT die knappe Ressource). **Root Cause gefunden:** `BotStrategy::researchCandidate()` wählte immer die niedrigste noch offene Kenntnis-ID, ohne den `knowledge_cc_level_cap`-Gate (CC Lv4/5 nötig für Kenntnis-Lv4/5) zu berücksichtigen — sobald die gewählte Kenntnis an diesem Gate hängenblieb, scheiterte der `levelup`-Versuch jeden Sol erneut mit `knowledge_cc_gate`, der `add`-Fallback greift aber nur bei `insufficient_ap_invested`, also blockierte die Regel für den Rest des Laufs komplett, ohne je zur nächsten Kenntnis zu wechseln. Fix: `researchCandidate()` überspringt jetzt CC-gegatete Kenntnisse und wählt die nächste eligible. `Playtest.php`-Timeout 240→400 (der reparierte Bot handelt jetzt deutlich mehr AP erfolgreich ab, braucht dadurch mehr Laufzeit pro Seed).
  - **8-Seed-Batch nach Fix:** 1 von 8 Runs gewinnt jetzt (Seed 1008, 2/3 Ziele, Score 2391) — vorher 0 von 8. 5 von 8 erreichen 1/3 Ziele (vorher meist 0/3). `task_research_lead` bleibt der neue Hauptblocker bei den übrigen 7 (alle Kenntnisse hängen gemeinsam an CC Lv5, Kommandozentrale kommt über Lv4 nicht hinaus) — kein Bot-Bug mehr, sondern eine klare Folgefrage (eigene Analyse nötig, ob CC-Lv5 seedabhängig unerreichbar ist oder die AP-/Regolith-Kosten für CC-Ausbau selbst ein Problem sind).
  - **Nach A39 (Sciencelab-Rabatt-Fix, s.u.):** 3 von 8 Runs gewinnen jetzt. `task_research_lead` kein Blocker mehr (erfüllt sich in 2 von 3 gewonnenen Runs). Verbleibende Blocker in den übrigen 5: `task_trade_volume` und `task_colony_prosperity` nie erfüllt — eigene Analyse nötig, gleiche Systematik wie A37/A39 (instrumentieren, Bot-Bug vs. Balance-Wand unterscheiden, mit game-designer kalibrieren).
  - **`task_trade_volume`-Fix (2026-09-14):** Root Cause identisch zu A34 (promote_advisor) — der Bot hatte **keine einzige Regel**, die `/merchant/buy/{itemId}` je aufruft (Corvans kuratierter Katalog, `merchant_items`/`merchant_visits`, unabhängig von `bar_offers`). Neue `buy_merchant_item`-Regel (`BotStrategy.php`, hohe Priorität direkt nach `promote_advisor` — Merchant-Besuche sind zeitlich begrenzt, verpasstes Fenster ist unwiederbringlich). Smoke-Test Seed 1001: `task_trade_volume` sofort 6/5 erfüllt (vorher 0/5 in diesem Seed). 8-Seed-Batch: **4 von 8 Runs gewinnen jetzt** (vorher 3/8).
  - **`task_colony_prosperity`-Befund (2026-09-14, offen):** Trust > 70, 10 Sole Streak — max. beobachtete Trust in den 3 verbleibenden Blockern lag bei nur 17-20 (Formel: `GameTick::calculateTrust()`, jeden Tick neu berechnet, kein Akkumulieren). Game-designer-Analyse: theoretisches Maximum mit den alten Werten lag real bei ~48-58, NICHT bei den grob geschätzten ~70 — das Ziel war praktisch unerreichbar, unabhängig von Bot-Kompetenz. Owner-Entscheidung: Threshold (70) und Streak (10 Sole) NICHT senken, stattdessen Trust-Quellen selbst verstärken (game-designer-kalibriert): Krankenstation 3→5/Lv, Cantina/Denkmal/Tempel 2→3/Lv, Security-Hub 1→2/Lv, Agronomy 1→2/Lv, Defense 1→2/Lv, Health 2→3/Lv, Frachter-Schiff `trust_per_unit` 1→2, `well_fed`-Event 1→2. TDD: `TrustServiceTest` (7 hardcodierte Erwartungswerte aktualisiert), `docs/game-reference.md` nachgezogen. Zusätzlich `BotStrategy::placeCandidate()` um eine Prioritätsstufe für die 4 trust-tragenden Gebäude ergänzt (Infirmary/Monument/Tempel/SecurityHub — wurden in KEINEM bisherigen Playtest-Report je gebaut). **Ergebnis:** max. Trust stieg spürbar (17-20 → 33-48) allein durch die Zahlen-Erhöhung, aber die 4 Trust-Gebäude werden trotz höherer Priorität weiterhin NIE gebaut — sie scheitern am Ressourcen-Check (alle vier brauchen Werkstoffe im `build_cost`, die knappste Ressource im Spiel). `task_colony_prosperity` bleibt offen.
  - **Werkstoff-Engpass-Befund (2026-09-14):** Antwort auf die Owner-Frage "nicht genug Quellen oder Ertrag zu gering?" — **weder noch, der Bot nutzt schlicht keine der vorhandenen Quellen.** Zwei dedizierte Werkstoff-Missionen existieren (`mission_salvage_sweep`, `mission_long_range_expedition`, config/missions.php) plus der garantierte Uplink-Sofortimport (`nexusImportCompounds`, GDD §3 "Anti-Lock-Netz") — der Bot hatte für KEINE davon eine Regel (dritter Zero-Rule-Bug derselben Familie wie A34/`task_trade_volume`). Neue `dispatch_compounds_mission`-Regel (`mission_salvage_sweep`, braucht `construction` Lv1 + Frachter/Korvette). Beim Testen zusätzlich entdeckt: `shipToRequest()` kaufte bei nicht leistbarem Frachter (500 Cr) IMMER stattdessen eine weitere Drohne nach — 3 Test-Seeds endeten mit 5-7 Drohnen und NULL Frachtern/Korvetten trotz erreicher 3-Berater-Schwelle ab Sol 20. Frachter/Korvette sind aber die einzigen für Compounds-Missionen zulässigen Schiffe. Fix: Drohnen-Kauf auf 2 gedeckelt, danach wird auf den Frachter gespart statt weiter Drohnen zu horten. Neue `RunReportShipSnapshotTest`/Schiffs-Tracking in `RunReport` (Schiffsbestand war vorher gar nicht erfasst). **Ergebnis:** Frachter werden jetzt tatsächlich erworben (2/3 Seeds), `dispatch_compounds_mission` feuert erfolgreich (6× bzw. 1× in den beiden Seeds mit Frachter) — Seed ohne Frachter blieb unerklärt (eigene Prüfung nötig). Trust-Gebäude werden trotz erfolgreicher Compounds-Beschaffung weiterhin NIE gebaut.
  - **Compounds-Bestand-Tracking + finale Auflösung (2026-09-15):** `RunReport` erfasst jetzt Compounds-Bestand pro Sol (`RunReportCompoundsSnapshotTest`) sowie Schiffs-**Zustand** pro Sol, nicht nur -Anzahl (`ship_states`, `RunReportShipSnapshotTest`) — nötig, um docked/pending/dispatched zu unterscheiden. Damit zwei weitere, tiefere Root Causes gefunden und gefixt:
    1. **Pending-Schiff-Verfall:** `shipToRequest()` kaufte den Frachter (500 Cr) OHNE freien Hangar-Slot zu prüfen (nur die Bootstrap-Drohne hatte diesen Check) — in einem Seed verfielen 3 gekaufte Frachter komplett im `pending`-Zustand, bevor je eine 2. Hangar-Instanz einen Slot freigab (1500 Cr verschwendet, Compounds-Mission dauerhaft ohne Schiff). Fix: Frachter-Kauf jetzt ebenfalls an `hasFreeHangarSlot()` gebunden.
    2. **Zonen-Kapazitäts-Konflikt (finale Root Cause):** Hangar (bewusst instanzunbegrenzt, "1 Hangar = 1 Schiffslot") und Wohnhabitat (max 6) sind beide `priority=1`/`3` in `placeCandidate()` und damit die einzigen bezahlbaren Optionen früh im Spiel (Trust-Gebäude brauchen Werkstoffe, die erst Sol 70-90 verfügbar werden) — sie füllten alle ~16 Zonen-Tiles lange bevor Trust-Gebäude je konkurrieren konnten. Eine reine Prioritäts-Anpassung half nicht (Trust-Gebäude waren schlicht noch nicht bezahlbar, als die Tiles vergeben wurden). Owner bestätigt: Design-Absicht sind 2-3 Hangar-Instanzen, nicht unbegrenzt. Fix: Hangar/Wohnhabitat werden ab 2 Instanzen **hart aus der Kandidatenliste ausgeschlossen** (nicht nur niedriger priorisiert) — reserviert Zonen-Tiles für andere Gebäudetypen statt sie "prioritätsschwach, aber mangels Alternative doch" zu vergeben.
  - **Ergebnis (3-Seed-Stichprobe):** **2 von 3 vorher komplett blockierten Seeds gewinnen jetzt** (vorher 0 von 3). Ein Seed baute erstmals alle 4 Trust-Gebäude und erreichte **Trust = 70 exakt** (Schwelle ist `> 70`, damit hauchdünn verfehlt — kein neuer Bot-Bug mehr, sondern eine Zahlen-Feinjustierung für ein andermal). Phase 2 startet zudem früher (Sol 9 statt 13) — die Zonen-Kapazität war ein spürbarer allgemeiner Bremsklotz, nicht nur für `task_colony_prosperity`.
  - **Abschluss (2026-09-16):** game-designer-Einschätzung zum Trust=70-Miss eingeholt (Owner-Einwand: "Bots spielen nicht optimal, soll schwer bleiben"). Bewertung: `task_colony_prosperity` verlangt einen Streak von 10 Ticks über Trust>70 (`RunProgressService::updateColonyProsperity`) — strukturell die härteste der 6 Task-Kategorien, by design; 3-Seed-Stichprobe ohnehin zu klein für eine weitere Zahlenkorrektur (siehe `feedback_playtest_seed_sample_size`); von "0/3 unmöglich" zu "an der Kante" ist genau die erwartete Wirkung des Fixes. Owner-Entscheidung: so belassen, **A37 abgeschlossen**. Keine weitere Änderung an `trust_per_lv`/`trust_per_unit`.
- [x] **A39 Sciencelab-Kenntnis-Rabatt verstärken** (Nebenbefund A37) ✅ 2026-09-13 — `sciencelab.knowledge_ap_cost_reduction_per_lv` von `[4=>3, 5=>2]` (Σ5%, erst ab Lv4) auf game-designer-kalibrierte `[2=>6, 3=>7, 4=>8, 5=>9]` (Σ6/13/21/30% bei Lv2-5) umgestellt. TDD: `ResearchServiceKnowledgeDiscountTest` um Lv1/Lv2-Fälle erweitert, `ProjectBonusServiceTest` + `HangarMissionResolutionTest` (hardcodierte alte Kurve) korrigiert, `docs/game-reference.md` nachgezogen. **Verifiziert per 8-Seed-Batch: 3 von 8 Runs gewinnen jetzt (vorher 1 von 8 nach dem A37-Bot-Fix allein, 0 von 8 ursprünglich)** — `task_research_lead` (3 Kenntnisse auf Lv5) erfüllt sich in 2 der 3 gewonnenen Runs. Klarer, messbarer Balance-Erfolg.
- [x] **A38 PlaytestBot-Nichtdeterminismus** (Nebenbefund A34) ✅ 2026-09-13 — Root Cause: `ColonyTileService::randomTileType()`/`randomizeOuterRingRows()` würfelten die komplette Sol-1-Startkarte (Terrain, Regolith-Reichtum/-Position, Hazards) mit ungeseedetem `random_int()`/`shuffle()`/`array_rand()` — bewusst so designed (Roguelike-Variation), aber dadurch reproduzierte `game:playtest --seeds=X` nichts außer der Objective-Ziehung. `rng_seed` wurde zudem erst NACH der Kartengenerierung gesetzt (`OnboardingService::seedSol1State()`), sodass selbst der spätere `BotSession`-Hack (`rng_seed` per DB-Update überschreiben) wirkungslos war. Fix: ein `$rngSeed` treibt jetzt beides — Karte (seeded LCG-Hash, gleiches Muster wie `BarService::pseudoRand()`) und `runs.rng_seed` —, optional durchgereicht via `resetColonyToSol1(..., rngSeed:)`; echte Spieler bekommen weiterhin einen frischen Zufalls-Seed, keine Verhaltensänderung für sie. TDD: `ColonyTileServiceSeededGenerationTest`. Verifiziert: zwei unabhängige Solo-Läufe von `--seeds=1001` liefern jetzt byte-identische JSON-Reports (Actions, Sols, Log, Outcome).
- [x] **A40 Cantina-Barkeeper** (GDD §12 Kanal 1 „Bar/Cantina") ✅ 2026-09-17 — Tomas injiziert bei Interaktion ("Mit Tomas reden", 0 AP-Kosten, Cooldown 1×/Sol) direkt Bonus-AP in eine vom Spieler gewählte laufende Kenntnis-Investition, über `ResearchService::investBonus()` (neuer, pool-gate-freier Injektionsweg, siehe A41/A42 unten für Wiederverwendung). Bonus wächst mit kumulativer Interaktionshäufigkeit (0/+1/+2/+3 AP an den Schwellen 0/5/15/30, harter Deckel). TDD, volle Suite grün.
- [x] **A41 Cantina-Charakter-Anliegen bei zufälligen Begegnungen** (GDD §12 Kanal 1) ✅ 2026-09-17 — 9 Figuren (6 bar_trade + Owner-Entscheidung: auch die 3 story_hook-Figuren Aldra/Sorel/Stranger, bewusster Bruch der bisherigen "story_hook = nie mechanisch"-Leitplanke) bekommen je ein Anliegen mit variabler Belohnung (Zusatzschiff, Baukosten-Rabatt, AP-Bonus via `investBonus()`, Werkstoffe, Regolith, Credits, Trust — neuer Event-Key `story_concern_resolved`/`story_concern_failed`). Läuft als zweiter, unabhängiger, selteneren Roll NACH dem bestehenden Credits-Begegnungspool (max. 1 Cantina-Sonderbegegnung/Sol über beide Pools). TDD, volle Suite grün.
- [x] **A42 Deva/Lenn (information-Rolle) + Charakter-Kodex** (GDD §12 Kanal 1, A36-Folge-Task) ✅ 2026-09-17 — zwei Teile:
  - **Deva & Lenn taktische Information:** dritter, unabhängiger Begegnungskanal (eigener Spawn-Roll, läuft NICHT im "max 1/Sol"-Gate der anderen beiden Pools). Vier-Ausgänge-Pool je Figur, Deckel pro Ausgangs-Typ (nicht pro Figur) — Deva/Lenn mechanische Ausgänge je 1×/Run, 2 narrative Ausgänge je Figur unbegrenzt wiederholbar.
  - **Charakter-Kodex:** rein narrative Lore-Einträge für alle **15** Cantina-Figuren (frühere "14"-Zählung übersah durchgängig Voss/scrap_dealer), 5 pro Figur, run-übergreifend user-persistent (neue Tabelle `character_codex_entries`), eigener Screen `/user/codex`. Freischaltung reused bestehende `game_role`-Interaktionen als Trigger.
  - TDD durchgängig, echte Browser-Verifikation via Playwright. Nebenbefund: ein bereits bestehender Quote-Kollisions-Bug im A36-Angebots-Annehmen-Button (`bar.blade.php`) wurde dabei gefunden und gefixt (brach die UI für jedes Angebot mit zugeordnetem Charakter).
- [x] **A43 Techtree = Übersicht + Forschung** ✅ 2026-09-24 (Variante D, `docs/gdd/techtree.md` §11.4/§11.5) — Techtree-Order nimmt nur noch Forschung an (`use_colony_view` für Gebäude/Schiffe). Rückbau/Bauabbruch im Tile-Panel (`POST /colony/building/leveldown`, kostenlos, instanzgenau, Stufe 0 räumt das Tile) mit Bestätigungs-Modal auf Basis einer Vorschau (`GET …/leveldown-preview`, echte Rechnung in zurückgerollter Transaktion). Owner-Entscheidungen: kein Vertrauens-Event, Bestandsschutz, Bauabbruch erlaubt. Deep-Link `/colony/view?building=&instance=`, Techtree-Instanzliste mit „Zum Tile", Instanzanzeige `/ max_instances` statt `/ max_level`. Nebenbei: doppeltes Escaping in `entity-chip`-Links, `tileHeading`-JS-Fehler, `securityHub_desc` korrigiert. Reste erledigt 2026-09-24 (Branch `chore/a43-reste`): Kolonieprotokoll-Chips verlinken aufs Tile (Events schreiben `instance_id`), Harvester bekommt im Techtree einen Bezugs-Hinweis statt des toten `?build=`-Links, `?build=` für nicht baubare Gebäude zeigt einen Toast; `securityHub_desc` neu.

### Herkunft: C — Doku widerspricht Code (Entscheidung, dann GDD oder Code anpassen)

- [x] **C1** ✅ 2026-09-07 — §13.1 „Upkeep-Verlust läuft über `nexus_debt`" — entschieden: GDD-Text bestätigt sich als Zielzustand, Umsetzung fehlt noch (Code-Gap, siehe A21)
- [x] **C2** ✅ 2026-09-07 — §7 Instanz-Zerstörung vs. Level-0-Ruine — entschieden: nie löschen (Ist-Zustand bestätigt), zusätzlich neue Reparatur-Hysterese für Effekt-Rückkehr ab ~50 % beschlossen (siehe A19/A20); GDD §7 bereits parallel aktualisiert
- [x] **C3** ✅ 2026-09-06 (GDD-Konsolidierung) — §4 Uplink Lv2 „Tiefenscan 1 Sol weniger" → Code: Scan-AP 2 → 1; Text angleichen
- [x] **C4** ✅ 2026-09-08 — §15/§18.4 Phase-1-Bedingung — entschieden (F7): Code (`RunProgressService::checkPhase1Completion()`) ist korrekt und bleibt unverändert, irreführender Docblock-Kommentar bereits gefixt
- [x] **C5** ✅ 2026-09-06 (GDD-Konsolidierung) — §15 Fail States / Gnadenfrist auf Phase-2-Sol-Basis und Instant-Trust-Fail umschreiben (§18.5-TODO seit 06-28) — zusammen mit A7
- [x] **C6** ✅ 2026-09-22 — geprüft: GDD §6 (Zeile 727, „das Level je Instanz trägt den Supply-Cap-Beitrag") war bereits korrekt, nur der Kommentar in `config/buildings.php` bei `housingComplex.supply_cap` war veraltet (unterschlug die Level-Multiplikation, nannte fälschlich einen flachen Max-Wert von 48 statt der tatsächlichen 144 bei vollem Ausbau) — Kommentar korrigiert, kein Code-/GDD-Änderungsbedarf
- [x] **C7** ✅ 2026-09-06 (GDD-Konsolidierung) — §8b Missionstabelle: `mission_aid_transport` ungegatet, `mission_harvester_salvage` ergänzen, Lieferzeiten 1/2/3
- [x] **C8** ✅ 2026-09-06 (GDD-Konsolidierung) — §4 Gebäudetabelle: Max-Level-Spalte (alle 13 gedeckelt), Wohnhabitat Lv3 × 6 Instanzen, „11 aktive + 3 im Design" → 13 implementiert
- [x] **C9** ✅ 2026-09-07 — §4c Zuordnungstabelle — entschieden: Agrardom bleibt Level (F1), Config/GDD stimmen nach GDD-Korrektur überein
- [x] **C10** ✅ 2026-09-06 (GDD-Konsolidierung) — §10 Liste implementierter Kenntnis-Effekte vervollständigen (geology, agronomy-Organika, health-Seuche, defense-Sturm, trade-Preisbonus, Analytik Lv4/5)
- [x] **C11** ✅ 2026-09-06 (GDD-Konsolidierung) — §13 „Implementierung": `PersonellService` → `AdvisorService`, `FleetService` entfernen
- [x] **C12** ✅ 2026-09-06 (GDD-Konsolidierung) — Tick-Schrittnummern in §8b/§13/§14 auf die 1–15-Nummerierung von `GameTick.php` (Stufe 6)
- [x] **C13** ✅ 2026-09-06 (GDD-Konsolidierung) — Terminologie-Pass „INNN" → Kolonieprotokoll/Nexus-Funk (29 Stellen im GDD)
- [x] **C14** ✅ 2026-09-23 — Zahlen aus GDD §6/§14/§18.2-Prosa entfernt (ADR 0004), durch qualitative Formulierungen + Fußnoten auf `config/*.php` ersetzt. Dabei aufgefallen: mehrere entfernte Zahlen waren bereits Drift gegenüber der Config (Trust-Werte aus dem A37-Rebalance vom 2026-09-14 nie ins GDD nachgezogen) — genau das Muster, das ADR 0004 verhindern soll. Nebenbefund: `game-reference.md` fehlen komplett Tabellen für Trust-per-Entity-Beiträge, Fail-State-Schwellwerte und Supply-Cap-Quellen — eigener Folge-Task wert.
- [x] **C16** ✅ 2026-09-23 — Owner-Entscheidung: Agrardom-Gate wird implementiert (kein Sackgassen-Risiko, aber unnötiger Fallstrick für Neueinsteiger). `investBuilding()` blockt jetzt den CC-Sprung von Lv1 auf Lv2 ohne platzierten Agrardom (`agrardom_required`, geprüft vor jedem AP-/Ressourcen-Verbrauch), spätere CC-Stufen bleiben ungegatet. Gemeinsamer `agrardomPlaced()`-Helper ersetzt die bisher doppelte Inline-Query in `placeBuilding()`. `config/buildings.php`-Kommentar korrigiert (verwies auf einen nicht existenten `ColonyService`-Ort).
- [x] **C15** ✅ 2026-09-06 — `docs/gdd/techtree.md`: Max-Level-Spalte, `strategist`-Zeile 166, Bio-Anlage-Gate

### Herkunft: T — sonstige Funde (außerhalb des ursprünglichen Audit-Katalogs)

- [x] **T7 Encounter-Reste** ✅ 2026-09-22: Geologische Instabilität + Seuchenausbruch erscheinen jetzt als Detailzeile mit Zustandsbegründung in `SolReportService::eventsGroup()` (analog zur bestehenden Sturm-Behandlung, B12) — `GameTick::rollInstability()`/`rollPlague()` loggen dafür zusätzliche Parameter (Sole seit Standortwechsel bzw. Hunger-Streak/Vertrauenswert)
- [x] **T8 Realistische Test-/Dev-Fixture (Supply)** ✅ 2026-09-22 (Fund 2026-09-20) — `data/sql/testdata.sqlite.sql`: 2 weitere Wohnhabitat-Instanzen für Bart (Levels 2+3+2, Σ7), Kapazität dadurch von 18 (fest) auf 66 (rechnerisch aus CC+Wohnhabitat hergeleitet), Supply jetzt +14 statt −34. Kein Gebäude-Level wurde reduziert, um die vielen darauf hart-codierten Tests nicht zu kaskadieren — stattdessen der Hebel genutzt, den auch der Spieler hätte (Wohnhabitat ausbauen). `ResetPlayer.php`-Szenarien geprüft: alle bereits positiv, nur ein veralteter Docblock-Kommentar (near-deadline "≈58" statt tatsächlich 95) korrigiert. Volle Suite grün (1566 Tests), 6 Testdateien an die neue Kapazität angepasst. Bekannter Nebenbefund (nicht behoben, eigenes Ticket wert): `testdata.sqlite.sql` listet `sciencelab.supply_cost=8`, `config/buildings.php` sagt 6 (Drift seit 2026-08-11).
- [x] **T10 Credits-Wand Phase 2 / Harvester-Stillstand im Bot** ✅ 2026-09-28 (Fund 2026-09-24) — Root Cause: es gab keinen Regolith→Credits-Kanal, nur Credits→Regolith (Corvans Kauf-Alltagsgeschäft) — ein Teufelskreis bei Credits-Mangel. Owner-Entscheidung 2026-09-28: neuer, seltener Regolith→Credits-Verkaufsangebot-Typ in der anonymen Gästerotation (`BarService::buildRegolithSellOffer()`, `game.bar.regolith_sell_offer_chance_pct` = 12%, NICHT über Corvan). Zusätzlich `harvester.resource_max` ×1,5 (160/300/440 → 240/450/660, Baustoff-/Kartenbudget-Fix, orthogonal zum Sell-Kanal). Siehe `docs/superpowers/plans/2026-09-27-t10-regolith-credits-rekalibrierung-spec.md` (inkl. finaler Owner-Entscheidung). Bot-Verifikations-Batch (8–12 Seeds) steht noch aus. — Mittel
- [x] **T11 `accept_bar_offer` liefert Klartext statt Machine-Code** ✅ 2026-09-25 — alle 22 Fehlerpfade in `BarService` liefern Code + `message`; Bar-JS zeigt `message`.
- [x] **T13 Vertrauens-Event `building_level_down`** ✅ 2026-09-25 — Owner: gestrichen; Key aus Config/Lang/Tests/GDD entfernt.
- [x] **T14 Hangar-Regel aus GDD §7 fehlt im Code** ✅ 2026-09-25 — Owner: umsetzen; Schiff inaktiv (berechnet, nicht gespeichert), `dispatchShip` → `ship_inactive`, Hangar-Screen zeigt Grund, laufende Missionen laufen weiter, Bot überspringt inaktive Schiffe.
- [x] **T15 Security-Hub-Recycling greift ab Stufe 1 statt ab Stufe 3** ✅ 2026-09-25 — `securityHub.recycle_min_level` = 3.
- [x] **T17 Bot spart nicht für CC5** ✅ 2026-09-25 — `ccResearchReserve()`; Seed 5: CC5 Sol 83→70, Run-Ende 89→76.
- [x] **T19 GDD §13.7 Sol-1-Supply-Satz** ✅ 2026-09-25 — Config ist in sich stimmig (alle Pfadgebäude `supply_cost` 6); die 20 kamen aus Daten-Drift (T20). GDD-Satz präzisiert (Pfad-Parität, Reserve beim Platzieren).
- [x] **T20 Daten-Drift testdata ↔ Config** ✅ 2026-09-25 — Ursache größer: `MasterDataSeeder` überschrieb Gebäude/Schiffe mit uralten Werten (Verfall 2–9× zu niedrig, Supply/Baukosten/max_level abweichend). Fix: `TestSeeder` ruft am Ende `game:sync-config` (Config gewinnt immer), Fixture + `MasterDataSeeder` bereinigt, Paritätstest `MasterDataConfigParityTest`, Hangar 1 der Fixture auf Stufe 3. **Offen:** Dev-DB `nouron.db` hat dieselbe Drift → `php artisan game:sync-config` (erst `--dry-run`) nach Owner-OK. Alle bisherigen Bot-Balance-Daten sind mit zu niedrigen Verfallsraten entstanden.
- [x] **T16 Name Security-Hub** ✅ 2026-09-24 — Owner-Wahl: „Leitstelle" (EN „Response Center"), Ausbaustufe 3 „Bergungsdienst" (EN „Salvage Crew"). Lang DE/EN, GDD, `docs/gdd/techtree.md`, `game-reference.md` angepasst; interner Key `securityHub` bleibt.

### Herkunft: Zurückgestellte A/T-Punkte

- [x] **T26 Run-Seed nahe `PHP_INT_MAX` → TypeError in GameTick** (Fund RNG-Fix 2026-09-25) — `$rngSeed + mission_id + 1` bzw. `$rngSeed + colony*7919 + tick*104729` wird bei Seeds nahe `PHP_INT_MAX` zum Float. Wahrscheinlichkeit ~1e-9/Run; behoben 2026-10-05: `GameTick` reduziert `rng_seed` per `RunSeed::reduce`. — Klein
- [x] **T28 Hangar-First: Raumfahrer-Hint zu früh** ✅ 2026-10-05 (Fund R13-Handtest, Owner) — der Hint „Raumfahrer anheuern“ erschien direkt nach dem Hangar-Bau, obwohl der Pilot erst mit einem angekommenen Schiff nützt. Jetzt zählt der Hangar erst als Slot-freischaltend, sobald ein Schiff `docked`/`dispatched` ist.
- [x] **T29 Missionskarte: Ertrag nicht sichtbar/veraltet** ✅ 2026-10-05 (Fund R13-Handtest, Owner) — Ertrag stand nur klein neben dem Namen, die hartkodierten Lang-Texte zeigten veraltete Credits (vor der ×2-Anhebung). Label wird jetzt aus `config/missions.php` abgeleitet (`App\Support\MissionReward`, Test: jede konfigurierte Zahl erscheint) und steht als eigene Zeile „Ertrag“ getrennt von den Kosten-Chips.

### Herkunft: D1 — klein, danach — größtenteils abgeschlossen

- [x] `mission_supply_run.sol_distance` 2 → 1 (2026-08-04)
- [x] `mission_aid_transport` ungegatet — zweite Frachter-Mission ohne Kenntnis-Gate, schließt zugleich die Vertrauens-Lücke von Pfad B (2026-08-04)
- [x] Cantina-Losgröße an Zahlungsfähigkeit gebunden (höchstens ~35 % des Bestands, 2026-08-04)
- [x] Harvester-Zweitinstanz-Bezugsquelle entworfen und freigegeben (2026-08-05, §4c): Sockel-Baseline auf 1 Harvester-Instanz umgestellt (2. Instanz = optionaler Bonus, bewusst gegen Planbarkeit); Weg A = Orin (`corporate_rep`) verkauft Extraktionsrechte für 400–800 Cr; Weg B = Bergungsmission `mission_harvester_salvage` auf `ruin_tile`, kostenlos aber beschädigt ankommend — siehe `docs/roadmap-archiv.md` Meilenstein 08-05/06
- [x] **Harvester-Erschöpfung** (§4c) — ✅ umgesetzt (`GameTick` Depletion-Kurve auf `colony_tiles.resource_amount`, Sidebar-Anzeige 09-04, Warn-Hint + Ausweichziel 09-06). Ursprüngliches Ziel: Ertrag eines Regolith-Tiles soll über die Zeit sinken, damit der Harvester pro Run mehrfach umgesetzt werden muss. Schema-Grundlage existiert (`colony_tiles.resource_max`), ebenso die drei Ergiebigkeitsstufen und die Verlege-Vorschau. Zielbild: ein Tile trägt ~15–25 Sole. Rate gehört in die Regolith-Herleitung (§13.7)

### Herkunft: D2 — Supply-Achse (nächste Design-Runde, offen)

- [x] ~~Level-Deckel für Cantina und Krankenstation~~ — ✅ 2026-08-26 (Ausbaustufen-System, beide Lv3)
- [x] ~~Die übrigen `max_level = NULL`-Gebäude~~ — ✅ 2026-08-26: alle 13 Gebäude gedeckelt

### Herkunft: D3 — Ratenmodell vervollständigen (§13.2–13.3, §13.6) — offen

- [x] Bonus-System, Domänen-Kenntnis-Teil (§13.3) — additive, glockenförmige Bau-AP-Kostenreduktion aus `construction`/`cartography`/`trade` (Σ15% je Kenntnis bei Lv5), wirkt auf alle Gebäude-Levelups; `app/Services/ProjectBonusService.php` (PR #253, 2026-08-15)
- [x] ~~Handlungs-AP nachziehen: `bar.ap_cost_accept` 1→2, `ap_cost_negotiate` 3→4~~ — ✅ erledigt sich durch A5 (2026-09-06) und A13/P3 (2026-09-21, Verhandeln auf 2/2 AP)
- [x] ~~`decay.overcap_factor` 2.0 → 1.5~~ — ✅ erledigt sich durch A4 (2026-09-06)

### Herkunft: D5 — Instrumentierung, Playtest, Kalibrierung (laufend)

- [x] Determinismus-Bug `ColonyTileService::randomizeOuterRingRows()` **behoben** (2026-08-11, keine ungeseedete Tile-Erzeugung mehr — 980 Tests grün, 0 Skips, Stand 2026-08-13); vollständig verifiziert 2026-09-13 (siehe **A38**)
- [x] Die neun Metriken aus GDD Anhang A.5 in `RunReport` aufnehmen — ✅ erledigt sich durch A30/A31/A32/A33 (2026-09-12)

### Herkunft: D6 — Nachzieharbeiten — offen

- [x] `config/game.php → merchant.items.information.label` — behoben (2026-08-18, PR #270), beschreibt jetzt den echten Effekt statt der gestrichenen Systemkarte
- [x] Tick-Schritt-Nummerierung in `GameTick.php` (Docblock) — behoben (2026-08-18, PR #270), 1-15 lückenlos neu durchnummeriert. GDD-Referenzen (§8b, §13, §14, §15) auf die alten Schrittnummern sind noch offen, eigener Pass

### Herkunft: D7 — Offene Pfad-Paritäts-Fragen (Kenntnisse/Hangar/Cantina)

- [x] Kenntnisse-Sekundäreffekt-Matrix, größter Teil — die Aussage „keine Ressourcen-/AP-Boni" ist überholt: 6 von 7 Kenntnissen haben inzwischen einen hartverdrahteten Primäreffekt (Bau-AP-Rabatt, Organika-/Regolith-Produktion, Cantina-Slots, Sturm-/Instabilitäts-Risiko — siehe archivierte Stufe 1 + Branch `design/encounters-and-defense`, 2026-08-16). Offen bleibt nur die feinere Kosten-Differenzierung je Kenntnis/Level (siehe GDD Anhang A.4 „Kenntnisse-Boni komplett ausarbeiten") — `config/knowledge.php → levelup_costs`/`credits` sind weiterhin für alle 7 Kenntnisse identisch

### Herkunft: Entwicklungswerkzeuge (Dev Tools)

- [x] **Dev Panel** (`tools/dev-panel.php`) — Kombiniertes Browser-Tool mit Tab-Navigation: **Resources** (Credits/Supply/Regolith/Werkstoffe/Organika/Vertrauen setzen) + **Techtree** (Drag-and-Drop-Editor für Techtree-Positionen). Löst `tools/techtree-editor.php` und `tools/resource-editor.php` ab. Start: `php -S localhost:8081 tools/dev-panel.php`
- [x] **Debug-Statusleiste** — Fixed Bottom-Bar, nur für `role=admin` sichtbar. Kompakte Zeile: Run-ID, Sol/Tick-Limit, Bypass-Flags (farbkodiert), App-Env. „Config ▾"-Toggle öffnet Detailpanel mit Run-, Tick-, Supply-, Credits-, Fleet-AP- und Moral-Event-Werten aus `config/game.php`. Alpine.js x-show, kein Bootstrap.
- [x] **Tick-Simulator** (`game:tick-dry-run`) — Simuliert einen Tick und zeigt Credits-, Ressourcen- und Decay-Diff ohne DB-Schreibzugriff. `--colony=ID` filtert auf eine Kolonie. Ideal für Balancing-Checks.
- [x] **Playtest Dashboard** (`tools/playtest-dashboard.php`, 2026-08-16) — Browser-Viewer für `storage/logs/playtest/*.json`-Reports (von `game:playtest`/`PlaytestBotTest` erzeugt, keine neue Datenerhebung). Sidebar mit allen Läufen, Mehrfachauswahl, Chart.js-Liniendiagramme (Regolith/Credits/AP/Vertrauen/CC-Level über Sol) + Summary-Tabelle (Outcome, Objectives, Rejections). Teilt sich `tools/assets/dev-panel.css` mit Dev Panel (eigene `PLAYTEST DASHBOARD`-Sektion). Start: `php -S localhost:8082 tools/playtest-dashboard.php`

### Nachträglich als erledigt erkannt (2026-10-22)

- [x] ✅ (geprüft 2026-10-22: `race_id` kommt im Repo nirgends mehr vor, Schema ist die Baseline-Migration) **DB-Cleanup: `race_id` entfernen** — Rassen wurden konzeptuell abgekündigt (GDD §3, zusammen mit ENrg/LNrg/ANrg). `race_id` auf `users`-Tabelle ist historisches Schema ohne Auswertung → Migration zum Entfernen der Spalte.

### Gestrichene und umgeschriebene Ideen (Owner-Entscheidung 2026-10-22)

Aus dem ROADMAP-Ideenpool genommen, weil sie auf entfernte Funktionen verweisen. Wortlaut unverändert, Begründung darunter.

- [ ] **NPC-Vereinbarungen** — `innn_message_types.relationship_effect` für Nexus-Beziehungsstufen auswerten; `treaty_signed`-Moral-Event für Handels-/Schutzabkommen mit NPC-Fraktionen (Händler, Schmuggler) aktivieren; kein Krieg/Allianz-System (inkompatibel mit Singleplayer-Roguelike-Konzept, GDD §1.1). `war_declared` als Moral-Event-Key deprecaten.
  - *Stattdessen:* ROADMAP-Ideenpool „Cantina-Vereinbarungen“ (umgeschrieben).
- [ ] **Gruppen/Gilden** — Datenmodell für Gruppen (kein Schema vorhanden); Grundlage für `restriction = 1` im Handelssystem; bewusst einfach gehalten: gründen, beitreten, verlassen
  - *Grund:* Mehrspieler-Handel/Gruppen sind Singleplayer-Scope-fremd (kein Schema, kein Bedarf).
- [ ] **Moral-Erweiterung** — Bevölkerungszufriedenheit als eigener Wert, Revolutionsrisiko, fraktionsspezifische Moralmodifikatoren (GDD §13)
  - *Stattdessen:* ROADMAP-Ideenpool „Vertrauens-Erweiterung“ (umbenannt, Inhalt bereinigt).
- [ ] **Handelsbeschränkungen vollständig durchsetzen** — `restriction`-Feld Werte 1/2/3 korrekt auswerten (aktuell ignoriert)
  - *Grund:* Das alte Handelssystem mit `restriction`-Feld wurde durch Cantina/Nexus-Fallback ersetzt.
- [ ] **Aktionslog** — kein persistentes, spieler-aktions-bezogenes Audit-Log (Gebäude gebaut, Handel abgeschlossen etc.); ursprünglich als „Bekannte Lücke" Ende Phase 2 identifiziert (`docs/roadmap-archiv.md`), seither nicht aktiv verfolgt. Das Kolonieprotokoll (`/comm-log`, Phase 3j) deckt einen Teil ab (chronologisches Ereignis-Log), aber kein dediziertes Aktions-Audit. Bei der Roadmap-Restrukturierung 2026-09-22 hierher übernommen, damit der Punkt nicht in Vergessenheit gerät.
  - *Grund:* INNN-Messaging entfernt; das Journal läuft über `colony_log` (Kolonieprotokoll). Die Idee lebt in T38 (Event-System für Achievements) weiter.
- [ ] **Außenposten** — `home_colony_id` pro Flotte (GDD §12); ob Außenposten kommen, hängt davon ab ob das Einzelkolonie-Konzept als zu einschränkend empfunden wird; minimal halten (kein vollständiges Kolonie-System)
  - *Grund:* Flotten wurden am 2026-06-20 entfernt (`home_colony_id` pro Flotte existiert nicht mehr); eine Kolonie pro Spieler bleibt Grundprinzip.
