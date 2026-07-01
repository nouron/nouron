# Nouron — Roadmap

Stand: 2026-07-01

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

---

## Laufend — Phase 3 (UI-Migration + Feature-Finish)

### Entity-Chip-Rollout — weitere Views

- [ ] Integration in Berater-Screen, Kolonie-Screen und weitere Views (Segment-Array + Komponente existieren bereits, siehe Phase 3k)

### Offene GDD / Design-TODOs

- [ ] GDD §2 vs §6: Supply-Cap Tick-Schritt — Widerspruch, §2-Tabelle nennt Schritt 3, §6 + Code (`GameTick.php`) nennen Schritt 7. Eine Stelle korrigieren.
- [ ] GDD §13: Burnout-Config-Block — `config/game.php → advisors.burnout` existiert nicht. Ergänzen oder GDD-Referenz entfernen.
- [ ] GDD §14/Koloniebeiträge: Platzhalter-Begriff "Steuern" passt nicht zum Kleinkolonie-Konzept (kein Imperium). Begriff + prozentuales Malus-System neu denken, bevor implementiert wird. Alternativen: "Koloniebeiträge", "Abgaben", "Nexus-Quote".
- [ ] GDD §9 "Begegnungen & Gefahren" + Merchant-Item-Kategorie `encounter_prep`: referenzieren noch Fleet-basierte Encounters (Flotte/Systemkarte seit 2026-06-20 gestrichen). Eigener GDD-Pass zum Bereinigen, nicht blockierend.

---

## Geplant — Phase 4 (Post-Playtest)

- GDD-Cleanup: Balance-TODOs nach erstem Playtest einarbeiten
- Tile-abhängige Harvester-Produktionsrate (aktuell feste Rate ×10/Level)
- Berater-Traits (Draft — siehe Memory)
- Berater Außendienst-Mechanik für weitere Typen (nach Playtest evaluieren)
- Begegnungen & Gefahren (GDD §9) — konkrete Events + Encounter-Screens (siehe auch GDD-Bereinigung oben)
- Forschung / Techtree-Screen: Kenntnisse-Freischalt-Flow
- Play-by-Mail-Multiplayer (3–4 Spieler, variable Tick-Zeiten) — optionale spätere Iteration. Architektur in ADR 0003 festgelegt (siehe oben); Rest (Games/TurnOrders/Resolution-Engine/KI) zurückgestellt bis aktiv angegangen.

---

## Verworfene Features

- Kolonisierung / Colony-Ships (kein zweites Colony-Objekt)
- Rassen (kein Rassen-System)
- Battlecruiser (Schiffstypen reduziert auf Drohne/Frachter/Korvette)
- Tradecenter + Forschungshandel (entfernt)
- Player-Messaging / Galaxy-News / Inbox-Outbox (mit Phase 3j entfernt)
- Fleet-Commander als separater Berater-Typ (entfernt)
