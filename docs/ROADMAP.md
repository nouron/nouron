# Nouron — Roadmap

Stand: 2026-06-07

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

---

## Laufend — Phase 3 (UI-Migration + Feature-Finish)

### Entity-Chip-System (UI-Verbesserung)

Ziel: Entity-Namen (Ressourcen, Gebäude, Kenntnisse, Schiffe, Berater) überall in der UI als wiederverwendbare Chips mit Hover-/Tap-Tooltip darstellen. Architektur-Entscheidung: ADR 0002 (Structured Segments, SSR).

- [ ] `<x-entity-chip>` Blade-Komponente erstellen (Alpine.js Popover-Interaktion, PicoCSS Stile)
- [ ] `CommLogController::buildDescription()` auf Segment-Array umbauen (statt fertiger String)
- [ ] Comm-Log-View auf Segment-basiertes Chip-Rendering umstellen
- [ ] Chip-Stile je Entity-Typ definieren: `resource`, `building`, `knowledge`, `ship`, `advisor`
- [ ] Tooltip-Datenversorgung pro Typ klären (statisch via Config/Lang vs. inline `data-*`-Attribute)
- [ ] Rückwärtskompatibilität bestehender `colony_log`-Einträge klären (Migration oder doppelter Render-Pfad)
- [ ] Integration in weitere Views (Berater-Screen, Kolonie-Screen, etc.) — spätere Iteration

### Offene GDD / Design-TODOs

- [ ] GDD §2 vs §6: Supply-Cap Tick-Schritt — welcher Schritt (5 oder 7) ist korrekt? `TickService` prüfen
- [ ] GDD §13: Burnout-Config-Block — `config/game.php → advisors.burnout` ergänzen oder GDD-Referenz entfernen
- [ ] GDD: Erklärung `moral` (technisch) vs. `Vertrauen` (UI) in CLAUDE.md + GDD §14 ergänzen

---

## Geplant — Phase 3k (nächste Iteration)

- Cantina-Redesign: Bar-Hintergrund + NPC-Charaktere (Hotspot-System bereits implementiert, visuelles Redesign ausstehend)
- GDD-Cleanup: Balance-TODOs nach erstem Playtest einarbeiten

---

## Geplant — Phase 4 (Post-Playtest)

- Tile-abhängige Harvester-Produktionsrate (aktuell feste Rate ×10/Level)
- Berater-Traits (Draft — siehe Memory)
- Berater Außendienst-Mechanik für weitere Typen (nach Playtest evaluieren)
- Begegnungen & Gefahren (GDD §9) — konkrete Events + Encounter-Screens
- Forschung / Techtree-Screen: Kenntnisse-Freischalt-Flow
- Play-by-Mail-Multiplayer (3–4 Spieler, variable Tick-Zeiten) — optionale spätere Iteration. Architektur festgelegt in ADR 0003 (`docs/adr/0003-simultan-turn-resolution-multiplayer.md`); zwei Sofort-Maßnahmen daraus bereits gemergt (2026-07-01): `runs.rng_seed`-Vorbereitung + Domain-Events (`RunStarted`/`SolAdvanced`/`RunEnded`). Rest (Games/TurnOrders/Resolution-Engine/KI) zurückgestellt bis aktiv angegangen.

---

## Verworfene Features

- Kolonisierung / Colony-Ships (kein zweites Colony-Objekt)
- Rassen (kein Rassen-System)
- Battlecruiser (Schiffstypen reduziert auf Drohne/Frachter/Korvette)
- Tradecenter + Forschungshandel (entfernt)
- Player-Messaging / Galaxy-News / Inbox-Outbox (mit Phase 3j entfernt)
- Fleet-Commander als separater Berater-Typ (entfernt)
