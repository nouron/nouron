# Nouron — Roadmap

Stand: 2026-07-21

Singleplayer Roguelike Mini-4X (FTL/Catan-Stil). Keine Rassen, keine Kolonisierung, ein Run hat ein konkretes Ziel + klares Ende.

---

## Abgeschlossen

### Phase 1 — ZF2 → Laminas + Bootstrap 5 Migration
Abgeschlossen. Codebase vollständig auf Laminas migriert.

### Phase 1b — Laminas → Laravel 12 Migration
Abgeschlossen. Läuft auf Laravel 12 + SQLite.

### Phase 2 — Kern-Mechaniken stabilisiert
Abgeschlossen: Tick-System, Supply-Cap, Decay, Trade-Routen, Flottenoperationen.

### Phase 3a — Supply-Cap-Rework + Colony-Zone-Fundament
Abgeschlossen.

### Phase 3b — Colony-UI (Alpine.js Hex-Grid, Fog of War, Tile-System)
Abgeschlossen.

### Phase 3c — Colony-Aktionen (Erkunden, Sondieren, Bauen)
Abgeschlossen.

### Phase 3d — Colony-Zonen-Erweiterung (Tile-Count-Unlock, 3-Ring-Karte, Instanced Buildings)
Abgeschlossen.

### Phase 3e — Onboarding / Neue-Spieler-Erfahrung
Abgeschlossen: Triggers + Hints-Bar implementiert. Kein dedizierter New-Player-Wizard.

### Phase 3f — Berater-Screen (Alpine.js + PicoCSS)
Abgeschlossen.

### Phase 3g — Run-System + Lobby/Runs-Übersicht
Abgeschlossen.

### Phase 3h — Hangar-Screen (Carousel-View, Nexus-Schiffsanfragen, Hangar-Missionslog)
Abgeschlossen.

### Phase 3i — jQuery-Entfernung (vollständig), NexusDB-Redesign, Cantina-Charakter-System
Abgeschlossen (Mai/Juni 2026).

### Phase 3j — Kolonieprotokoll (INNN-Redesign)
Abgeschlossen: INNN-System vollständig ersetzt durch `/comm-log` (Protokoll-Tab + Nexus-Funk-Tab). Rich Log Descriptions + `building_invested`-Event. DB: `colony_log`. 725 Tests grün.

### Phase 3k — Entity-Chip-System (ADR 0002)
Abgeschlossen: `<x-entity-chip>`-Komponente, `CommLogController::buildDescription()` liefert Segment-Array (kein Legacy-String, kein Migrationsbedarf), Comm-Log auf Chip-Rendering umgestellt, Chip-Stile je Typ (`resource`/`building`/`knowledge`/`ship`/`advisor`), Tooltip-Daten inline. Ausstehend: Integration in weitere Views (Berater-Screen, Kolonie-Screen) — spätere Iteration, siehe unten.

### Phase 3l — Cantina-Redesign
Abgeschlossen: Bar-Hintergrund (`cantina-interior.webp`) + NPC-Charaktere via `config('characters')` + Hotspot-Portraits.

### Phase 3m — content-writer-Tonalität + lang/en-Sync
Abgeschlossen (2026-07-01): Drei-Stimmen-System (Kolonie/Nexus-Direktiven/NexusDB-Almanach), alle `lang/de/`-Beschreibungstexte neu geschrieben, `lang/en/` vollständig synchronisiert (12 neue Dateien). Globales Sci-Fi-Dialog-System (`dialogs.css`, `sol-modal`).

### ADR 0003 — Multiplayer-Turn-Resolution (Architektur)
Abgeschlossen (2026-07-01): Architekturentscheidungen dokumentiert (`docs/adr/0003-simultan-turn-resolution-multiplayer.md`). Zwei Sofort-Maßnahmen gemergt: `runs.rng_seed`-Vorbereitung + Domain-Events (`RunStarted`/`SolAdvanced`/`RunEnded`). Rest (Games/TurnOrders/Resolution-Engine/KI) zurückgestellt bis Multiplayer aktiv angegangen wird — siehe Phase 4.

### Phase 3n — Playtest-Bot (automatisierte Balance-Messung)
Abgeschlossen (PR #217 Vorarbeiten + PR #218, 2026-07-17/18): PHPUnit-basierter Bot unter `tests/Feature/Playtest/` spielt komplette Runs ausschließlich über echte HTTP-Routen (`BotSession`, `BotStrategy`, `RunReport`-JSON-Artefakt unter `storage/logs/playtest/`). Eigene Suite `playtest` (aus `laravel-feature` ausgeschlossen). Deckte dabei mehrere echte Bugs auf (Session-Hard-Default Kolonie 1, ungeseedete Ziel-Ziehung, nicht persistierter Score, 200er statt 422 bei Colony-Fehlern u.a.) und legte den strukturellen Credits-Ökonomie-Kollaps nach Phase 1 offen — siehe folgende zwei Einträge.

### Credit-Ökonomie-Balance (2-Schritt-Ticket)
Abgeschlossen — Schritt 1, PR #219, gemergt (2026-07-19): Relaisvergütung Housing→Uplink-Station umgehängt, Advisor-Upkeep abgeflacht (`[10,50,160]`→`[10,30,80]` Cr/Tick), Rang-Schwellen gestreckt (`[10,20]`→`[15,45]` active_ticks), neue Handelsvertrag-Einkommensquelle (Konsul + Cantina). Playtest-Bot-Ergebnis: `phase2_start_sol` nie erreicht → Sol 49.

In Review — Schritt 2, PR #220, noch nicht gemergt (2026-07-20): Harvester/Agrardom-Grundproduktion von flacher Rate (`×10/Level`, unbegrenzte Level) auf Glockenkurve mit Deckel umgestellt (`config/game.php: production_curve`, `max_level=8` in `config/buildings.php`). Playtest-Bot-Ergebnis: `phase2_start_sol` 49 → 18. Ökonomie-Kollaps nach Phase 1 bleibt weiterhin offen — siehe „Laufend" unten.

---

## Laufend — Phase 3 (UI-Migration + Feature-Finish)

### Brainstorming: Kenntnisse/Hangar/Cantina-Pfad-Parität (mit Owner, 2026-07-20)

Design-Entscheidung getroffen, konkrete Zahlen noch offen: die drei Berater-Pfade behalten unterschiedliche, aber gleichwertige Wirkweisen statt eines neuen Subsystems — Analytiker/Kenntnisse = passiver Multiplikator, Pilot/Hangar = aktive Burst-Beschaffung (Missionen), Konsul/Cantina = aktive Konversion (Handel). Guard-Rail: Grundproduktion muss für sich allein "knapp, aber machbar" sein, bevor irgendein Pfad-Bonus draufkommt.

Nächste Schritte, alle noch offen:

- [ ] Bar/Cantina „Not enough resources." beheben — kein praktisch nutzbarer reiner Credits-Einkommenstyp
- [ ] Post-Phase-1-Ökonomie-Erholung (Kollaps bei mehreren Rang-2/3-Beratern gleichzeitig)
- [ ] Kenntnisse-Sekundäreffekt-Matrix ausfüllen (GDD §10, aktuell nur 6 von 35 Kombinationen als Platzhalter, keine Ressourcen-/AP-Boni)
- [ ] Hangar-Missionsnutzbarkeit (Bot/Spieler kommt praktisch nie zum Freighter-Kauf, ressourcengebende Missionen bleiben unerreicht)

### Entity-Chip-Rollout — weitere Views

- [x] Integration in Berater-Screen, Kolonie-Screen (2026-07-21). Kommandozentrale-Dashboard (Wartungsstau, Berater-Kurzübersicht) ergänzt.

### Offene GDD / Design-TODOs

Abgeschlossen (2026-07-10, game-designer): alle 4 Punkte geprüft und im GDD bereinigt.

- [x] GDD §2 vs §6: Supply-Cap Tick-Schritt. §2s "Phase 3" war eine grobe Gruppierung, kein Widerspruch zu §6/Code (Schritt 7) — beide bereits konsistent. §2 um eine Phase-≠-Schritt-Klarstellung ergänzt.
- [x] GDD §13: Burnout-Config-Block. Kein echter fehlender Verweis — GDD markiert die Formel bereits explizit als "noch nicht implementiert (Phase 4+)" und referenziert keinen nicht-existenten Config-Pfad. Klarstellung ergänzt, keine Config geschrieben (Feature bewusst aufgeschoben).
- [x] GDD §14/Koloniebeiträge: "Steuern"-Konzept ist im GDD bereits als **verworfen** (nicht umbenannt) dokumentiert, ersetzt durch die undramatische Relaisvergütung. Abweichung von der ursprünglichen Design-Notiz ("Begriff klären, nicht verwerfen") — Owner-Rückfrage empfohlen, siehe Bericht.
- [x] GDD §9/Merchant Fleet-Referenzen: §9 selbst war bereits sauber. `encounter_prep` ist keine Merchant-Item-Kategorie (die sind `ap_flex/ap_targeted/information/repair_kit/trust_boost`) — es ist ein Almanach-Bonustyp (§17) und referenziert bereits das neue §9-Kolonistengefahr-System, keine Flotten-Mechanik. Die Prämisse dieses ROADMAP-Punkts war insofern doppelt ungenau. Tatsächlicher Fund: Reisender Händler (aktive Mechanik) stand fälschlich unter dem "GESTRICHEN"-Banner der alten Systemansicht (§8a) — nach §12 Handel (neuer "Kanal 3") verschoben, Wortlaut von "im System" auf Exploration Zone korrigiert. Zusätzlich zwei tote Platzhalter-Werte (Angriffs-AP, Bewegungsreichweite) in der Kenntnisse-Beispieltabelle durch zivile Äquivalente ersetzt.

---

## Geplant — Phase 4 (Post-Playtest)

- GDD-Cleanup: Balance-TODOs nach erstem Playtest einarbeiten
- Tile-abhängige Harvester-Produktionsrate obendrauf auf die bestehende Glockenkurve (PR #220) — zusätzlicher Modifier, ersetzt sie nicht
- Berater-Traits (Draft — siehe Memory)
- Berater Außendienst-Mechanik für weitere Typen (nach Playtest evaluieren)
- Begegnungen & Gefahren (GDD §9) — konkrete Events + Encounter-Screens
- Forschung / Techtree-Screen: Kenntnisse-Freischalt-Flow
- Play-by-Mail-Multiplayer (3–4 Spieler, variable Tick-Zeiten) — optionale spätere Iteration. Architektur in ADR 0003 festgelegt (siehe oben); Rest (Games/TurnOrders/Resolution-Engine/KI) zurückgestellt bis aktiv angegangen.
- Admin-Tool zur visuellen Auswertung/Vergleich von Playtest-Bot-Läufen (Owner-Wunsch 2026-07-20) — JSON-Reports aktuell nur manuell mit jq/python einsehbar

### Neu gefunden bei GDD-Aufräumpass (2026-07-10, technisch, nicht blockierend)

- `config/game.php → merchant.items.information.label` heißt noch "Systemkarte vollständig" (Restverweis auf die 2026-06-20 gestrichene Systemkarte) — rein kosmetisch, geprüft: `MerchantService` setzt bereits korrekt `colony_tiles.is_explored`, die Wirkung ist nicht kaputt. Label-Text anpassen. Siehe GDD §12 Kanal 3.
- Tick-Schritt-Nummerierung in `GameTick.php` (Docblock) ist selbst lückenhaft/inkonsistent (springt 0→4→6, Food-Consumption-Schritt fehlt ganz) und mehrere GDD-Stellen (§8b, §13, §14, §15) referenzieren daraus veraltete Schrittnummern (z.B. "Tick-Schritt 7" für Advisor Ticks, tatsächlich Schritt 9). Docblock zuerst durch game-developer korrigieren/vervollständigen, danach GDD-Referenzen in einem eigenen Pass nachziehen.

### Determinismus-Bug (gefunden 2026-07-20)

- `ColonyTileService::randomizeOuterRingRows()` nutzt PHP-Ambient-Zufall (`random_int`/`shuffle`/`array_rand`) statt `rng_seed` und läuft in `OnboardingService::resetColonyToSol1()` **vor** dessen explizitem Setzen — "gleicher Seed → gleicher Run" gilt aktuell **nicht** für Tile-Layouts. Gefunden via `PlaytestBotTest::test_same_seed_draws_identical_objectives`, Test läuft aktuell mit `markTestSkipped` statt rot.

---

## Verworfene Features

- Kolonisierung / Colony-Ships (kein zweites Colony-Objekt)
- Rassen (kein Rassen-System)
- Battlecruiser (Schiffstypen reduziert auf Drohne/Frachter/Korvette)
- Tradecenter + Forschungshandel (entfernt)
- Player-Messaging / Galaxy-News / Inbox-Outbox (mit Phase 3j entfernt)
- Fleet-Commander als separater Berater-Typ (entfernt)
