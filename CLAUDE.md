# CLAUDE.md — Nouron Projektkontext

## Projekt-Überblick

**Nouron** Sci-Fi-Strategiespiel, entwickelt 2008–2014, seit 2026 wieder aktiv.
- GitHub: https://github.com/nouron/nouron
- Techstack: PHP/Laravel, SQLite, Blade-Templates, Alpine.js + PicoCSS (neu, Phase 3b+), SVG für Spielfelder
- Frontend-Migration: jQuery vollständig entfernt (Mai 2026). Legacy-Screens noch Bootstrap 5 (schrittweise auf Alpine.js + PicoCSS — kein Mix in neuen Screens)
- Status: Laravel-Migration abgeschlossen, Design-Sprints DS-1–DS-4 abgeschlossen, Phase 3 (UI) aktiv

## Aktueller Stand (Stand: Mai 2026)

**Spielkonzept:** Singleplayer Roguelike Mini-4X (FTL/Catan-Stil). Kleine, ressourcenarme Kolonie am Leben erhalten. Kein Imperiumsaufbau, keine Rassen, keine organisierten Kriege. Runs haben konkretes Ziel + klares Ende.

**Abgeschlossen:** ZF2 → Laminas → Laravel Migration, Techtree-Redesign, Tick-System, AP-System, Berater-System, Flottenoperationen, Decay-System, Moralsystem, Supply-System, INNN-Nachrichten, Hex-Grid Kolonieansicht, Systemkarte, Reisender Händler, jQuery-Migration (vollständig), Berater-Screen (Alpine.js + PicoCSS), Onboarding-System (Triggers + Hints-Bar), Run-System, Lobby/Runs-Übersicht, Debug-Statusleiste (Admin), Fleet Command Overlay (Systemkarte).

**Laufend (Phase 3):** UI-Migration Bootstrap 5 → Alpine.js + PicoCSS. Ausstehend: GDD-Cleanup (Balance-TODOs nach Playtest), Onboarding-Wizard (Triggers + Hints implementiert, kein dedizierter New-Player-Flow), Kommandanten-Zuweisung UI (Fleet), Ressourcen-DB-Cleanup (ENrg/LNrg/ANrg noch in DB, per Whitelist gefiltert).

## Wichtige Korrekturen

- **Datenbank ist SQLite** (NICHT MySQL)
  - `data/db/nouron.db` — Entwicklungsdatenbank
  - `data/db/test.db` — Testdatenbank (befüllt via `data/sql/testdata.sqlite.sql`)
- `Routen.txt` und `code/nouron_(pre_zend)/` veraltet — nur GitHub-Repo relevant
- Vollständige Referenztabellen (Ressourcen, Gebäude, Schiffe, DB-Schema) → `docs/game-reference.md`

## Architektur (Laravel)

```
app/
  Http/Controllers/   -- Route Handler (Techtree, Colony, Fleet, INNN, ...)
  Services/           -- Game Logic (TickService, MoralService, AdvisorService, ...)
  Models/             -- Eloquent Models
  Console/Commands/   -- game:tick, game:sync-techs
config/
  game.php            -- Spielparameter (tick, supply, combat, advisors, onboarding, ...)
  buildings.php       -- Gebäude-Stammdaten (decay_rate, max_level, supply_cost, ...)
  advisors.php        -- Berater-Stammdaten (ap_type, credits, rank_thresholds, ...)
database/migrations/  -- Schema-Migrationen
data/sql/
  testdata.sqlite.sql -- Testdaten (INSERT + UPDATE, wird von TestSeeder ausgeführt)
resources/views/      -- Blade-Templates
public/js|css/        -- techtree-view.js, advisors.js, techtree-view.css, ...
```

Schichtung: `Controller → Service → Eloquent Model → SQLite`

## Technische Hinweise

- `config/game.php` und `config/buildings.php` sind **canonical source of truth** für alle Spielwerte — GDD folgt Config, nicht umgekehrt
- Neue Screens: Alpine.js + PicoCSS — kein jQuery, kein Bootstrap
- Legacy-Screens: noch Bootstrap 5 — werden schrittweise auf Alpine.js + PicoCSS migriert (jQuery vollständig entfernt)
- `TestSeeder` führt `data/sql/testdata.sqlite.sql` aus (regex-filtered: nur INSERT/UPDATE Statements)
- Techtree-Koordinaten phase-lokal (Zeile/Spalte innerhalb Phase), nicht global
- Moral-Events: Keys `encounter_won`, `encounter_lost`, `colony_threatened` (nicht `combat_*`)

## Grafik-Assets

Verbindliches Format für alle Spiel-Grafiken (Icons, Portraits, Tiles, Schiffe, Gebäude, Ressourcen):

- **Format:** WebP, transparenter Hintergrund
- **Auflösung:** 2× Zielgröße (Grafiker liefert doppelte Pixelzahl — HiDPI-ready)
- **Kein SVG** für Illustrations-Assets — SVG nur für UI-Struktur (Hex-Grid, strukturelle Icons)
- **CSS:** Container in `em`/`rem`, nie fixe `px`. Bilder: `width: 100%; height: 100%; object-fit: contain;`
- **Ablage:** `public/img/icons/`, `public/img/buildings/`, `public/img/ships/`, `public/img/advisors/`, `public/img/tiles/`

Richtwert-Größen (Zielgröße → Datei):
`24×24 px` Ressourcen-Icons → 48×48 px | `32×32 px` Gebäude-Icon (Sidebar) → 64×64 px | `48×48 px` Gebäude/Schiff (Tile) → 96×96 px | `128×128 px` Berater-Portrait → 256×256 px

Hex-Tile-Texturen: als `<image>` innerhalb SVG-`<clipPath>` eingebunden (siehe ADR 0001). Zielgröße abhängig von SIZE-Konstante in `colony-hexgrid.js`.

Vollständige Entscheidung: `docs/adr/0001-graphics-asset-format.md`

## Sprachregeln

| Bereich | Sprache |
|---|---|
| PHP-Code, JS, CSS, Kommentare im Code | **Englisch** |
| Konfigurationskeys, DB-Spaltennamen | **Englisch** |
| `lang/de/*.php` Werte (User-facing Strings) | **Deutsch** |
| GDD, ROADMAP, CHANGELOG, ADRs | **Deutsch** |
| Blade-Templates (sichtbare Texte) | immer via `__('key')`, nie hardcoded |

## Subagenten (`.claude/agents/`)

**Proaktiv** einsetzen — nicht erst auf Nachfrage:

- `game-designer` — Mechanics definieren, GDD aktualisieren (vor jeder neuen Mechanik)
- `game-developer` — Game Logic, Services, Tick-Verarbeitung
- `backend-coder` — Controller, Routes, API-Endpoints, Middleware
- `ui-specialist` — Blade, Alpine.js + PicoCSS (neu), Bootstrap 5 (Legacy, kein jQuery mehr)
- `db-migration-agent` — Schema, Migrations, SQLite, testdata.sqlite.sql
- `qa-tester` — Tests schreiben (nach jeder Implementierung automatisch)
- `content-writer` — lang/de/*.php Texte, Lore, Tooltips (bei neuen Entitäten automatisch)
- `project-manager` — ROADMAP, CHANGELOG, ADRs, Feature-Breakdown

## Workflow-Hinweise

- Entwicklungsumgebung: Ubuntu unter WSL2 (Windows 11)
- Owner: Mario (tech.mario@outlook.de)

### Git-Workflow (verbindlich)

**Nie direkt auf `master` committen oder pushen.**

1. `git checkout -b feat/<name>`
2. Commits auf Branch
3. `git push origin feat/<name>`
4. PR auf GitHub erstellen

Bei GitHub-Warnung *"Changes must be made through a pull request"*: Push abbrechen, Branch anlegen, PR erstellen.

## Changelog-Pflege

Ende jeder Session mit Code-Arbeit: Eintrag in `CHANGELOG.md` ergänzen.

```
## YYYY-MM-DD

- Kurze Beschreibung (1–3 Sätze pro Thema, auf Deutsch, prägnant)
```