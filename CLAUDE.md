# CLAUDE.md — Nouron Projektkontext

## Projekt-Überblick

**Nouron** Sci-Fi-Strategiespiel, entwickelt 2008–2014, seit 2026 wieder aktiv.
- GitHub: https://github.com/nouron/nouron
- Techstack: PHP/Laravel, SQLite, Blade-Templates, Alpine.js + PicoCSS, SVG für Spielfelder
- Frontend-Migration: jQuery vollständig entfernt (Mai 2026). Bootstrap-5-Migration ebenfalls abgeschlossen — einzige bekannte Ausnahme: `resources/views/techtree/technology.blade.php` (Bootstrap-Modal-Klassen + `data-bs-*`, noch nicht auf Alpine.js + PicoCSS migriert). Kein Bootstrap-CSS/JS mehr eingebunden (nur `bootstrap-icons`-Font, unabhängig vom Framework).
- Status: Laravel-Migration abgeschlossen, Design-Sprints DS-1–DS-4 abgeschlossen, Phase 3 (UI) abgeschlossen (Mai 2026, siehe ROADMAP.md)

## Aktueller Stand (Stand: 2026-08-22)

**Spielkonzept:** Singleplayer Roguelike Mini-4X (FTL/Catan-Stil). Kleine, ressourcenarme Kolonie am Leben erhalten. Kein Imperiumsaufbau, keine Rassen, keine organisierten Kriege. Runs haben konkretes Ziel + klares Ende.

**Abgeschlossen:** ZF2 → Laminas → Laravel Migration, Techtree-Redesign, Tick-System, AP-System, Berater-System, Flottenoperationen, Decay-System, Trust-System (Vertrauen), Supply-System, INNN-Nachrichten, Hex-Grid Kolonieansicht, Systemkarte, Reisender Händler, jQuery-Migration (vollständig), Bootstrap-5-Migration (bis auf eine Ausnahme, s.o.), Berater-Screen (Alpine.js + PicoCSS), Onboarding-System (Triggers + Hints-Bar), Run-System, Lobby/Runs-Übersicht, Debug-Statusleiste (Admin), Fleet Command Overlay (Systemkarte), Kommandanten-Zuweisung (Fleet, PR #139), Ressourcen-DB-Cleanup (ENrg/LNrg/ANrg entfernt).

**Laufend:** GDD-Cleanup (Balance-TODOs nach Playtest, siehe `docs/gdd-config-audit.md`), Onboarding-Wizard (Triggers + Hints implementiert, kein dedizierter New-Player-Flow), `techtree/technology.blade.php` auf Alpine.js + PicoCSS nachziehen (letzter Bootstrap-Rest). Cantina-Redesign abgeschlossen (Bar-Hintergrund `cantina-interior.webp` + NPC-Charaktere via `config('characters')` + Hotspot-Portraits).

## Wichtige Korrekturen

- **Datenbank ist SQLite** (NICHT MySQL)
  - `data/db/nouron.db` — Entwicklungsdatenbank
  - `data/db/test.db` — Testdatenbank (befüllt via `data/sql/testdata.sqlite.sql`)
- `Routen.txt` und `code/nouron_(pre_zend)/` veraltet — nur GitHub-Repo relevant
- Vollständige Referenztabellen (Ressourcen, Gebäude, Schiffe, DB-Schema) → `docs/game-reference.md`
- Design System (Farben, Typo, Spacing, Komponenten — verbindlich) → `docs/design-system/` (`readme.md` als Einstieg)
- Frontend Engineering Conventions (AJAX-Contracts, Screen-Kompositionsregeln, Breakpoints — verbindlich) → `docs/frontend-conventions.md`. `docs/design-guide.md` ist entfernt (2026-08-01), Inhalt auf beide obigen Dateien aufgeteilt.

## Architektur (Laravel)

```
app/
  Http/Controllers/   -- Route Handler (Techtree, Colony, Fleet, INNN, ...)
  Services/           -- Game Logic (TickService, TrustService, AdvisorService, ...)
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
  partials/           -- sol-button.blade.php, res-popup.blade.php (wiederverwendbar)
public/js|css/        -- techtree-view.js, advisors.js, techtree-view.css, resources.css, ...
```

Schichtung: `Controller → Service → Eloquent Model → SQLite`

## Technische Hinweise

- `config/game.php` und `config/buildings.php` sind **canonical source of truth** für alle Spielwerte — GDD folgt Config, nicht umgekehrt
- Neue Screens: Alpine.js + PicoCSS — kein jQuery, kein Bootstrap
- Legacy-Screens: Bootstrap-Migration abgeschlossen bis auf `techtree/technology.blade.php` (letzter Rest, siehe oben) — jQuery vollständig entfernt
- `TestSeeder` führt `data/sql/testdata.sqlite.sql` aus (regex-filtered: nur INSERT/UPDATE Statements)
- Techtree-Koordinaten phase-lokal (Zeile/Spalte innerhalb Phase), nicht global
- Trust-Events (`game.trust.*`): Keys `encounter_won`, `encounter_lost`, `colony_threatened` (nicht `combat_*`)
- `moral` in Code, Config und DB ist vollständig zu `trust` umbenannt; deutscher UI-Label ist `Vertrauen` (via `__('resources.res_trust')`)

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

## GDD Dokumentation (ADR 0004: Zahlen-Scope)

**Zwei-Schichten-Modell seit 2026-08-21:**

| Schicht | Inhalt | Wartung |
|---|---|---|
| **GDD Prosa** (§1–18) | Mechanik, Design-Intent, Formeln, Beispiel-Herleitungen. **Keine konkreten Zahlenwerte.** Formulierungen: "kosten Credits" statt "kosten 30 Cr"; "deutlich höher" statt "Wert = 150". Exception: narrative Pacing (Sol 30, Sol 80) gehört ins GDD. | Live-Editing, keine Zahlen-Nachzüge nötig |
| **Config** (`config/*.php`) | **Canonical source of truth** für alle Spielwerte. Agenten lesen Config direkt bei konkreten Aufgaben. | Direct Code Change |
| **game-reference.md** | Lookup-Tabellen aller aktuellen Config-Werte. Wird manuell nach größeren Balance-Passes refreshed. GDD verweist via Fußnote: "siehe `docs/game-reference.md#gebäude-kosten`". | Manuell, nach Balance-Pass |

**Konsequenz:** Zahlen-Drift sinkt, GDD bleibt lesbares Designdokument, Agenten arbeiten gegen Config statt Dokumentation.

## Subagenten (`.claude/agents/`)

**Proaktiv** einsetzen — nicht erst auf Nachfrage:

- `game-designer` — Mechanics definieren, GDD aktualisieren (vor jeder neuen Mechanik)
- `game-developer` — Game Logic, Services, Tick-Verarbeitung
- `backend-coder` — Controller, Routes, API-Endpoints, Middleware
- `ui-specialist` — Blade, Alpine.js + PicoCSS (kein jQuery/Bootstrap mehr, bis auf einen letzten Rest — s.o.)
- `db-migration-agent` — Schema, Migrations, SQLite, testdata.sqlite.sql
- `qa-tester` — Tests schreiben: VOR der Implementierung (TDD-Pflicht, siehe unten) + danach für Security-/Adversarial-/Regressionstests
- `content-writer` — lang/de/*.php Texte, Lore, Tooltips (bei neuen Entitäten automatisch)
- `project-manager` — ROADMAP, CHANGELOG, ADRs, Feature-Breakdown

## Test-Driven Development (TDD) — verbindlich

Für jeden neuen Code mit Verhalten (Services, Controller-Logik, Game-Mechaniken, Migrations mit Datenlogik/Backfills) gilt Red-Green-Refactor, keine Ausnahme auf Zuruf:

1. **Red** — Erst einen fehlschlagenden Test schreiben, der das gewünschte Verhalten beschreibt. Test laufen lassen, Fehlschlag bestätigen (sonst weiß niemand, ob der Test überhaupt etwas prüft).
2. **Green** — Minimale Implementierung, bis der Test grün ist. Kein Code, der nicht durch einen Test motiviert ist.
3. **Refactor** — Aufräumen, Tests bleiben grün.

**Reihenfolge ist verbindlich:** kein Produktionscode schreiben, bevor der zugehörige Test existiert und rot war. `game-developer`, `backend-coder` und `db-migration-agent` schreiben den Test selbst zuerst, wenn sie allein an einer Aufgabe sitzen — nicht auf `qa-tester` warten. `qa-tester` wird zusätzlich eingesetzt für Security-/Adversarial-Fälle und Regressionsabdeckung, die über den TDD-Test der Kernlogik hinausgehen.

**Ausnahmen** (keine Umgehung, sondern echte Nicht-Anwendbarkeit): reine Config-/Doku-/Lang-Änderungen ohne Codepfad, Migrations ohne Logik (Spalte hinzufügen, kein Backfill/keine Berechnung), vom Owner explizit als Wegwerf-Spike/Prototyp markierter Code. Im Zweifel: Test schreiben.

Bei reaktivierten/bestehenden Dateien mit Coverage-Lücke (siehe `bin/phpunit --coverage-html`) gilt dieselbe Reihenfolge für jede Änderung daran: nicht "code first, Test irgendwann nachziehen".

## Workflow-Hinweise

- Entwicklungsumgebung: Ubuntu unter WSL2 (Windows 11)
- Owner: Mario (tech.mario@outlook.de)

### Pre-commit Lint-Hook

`.githooks/pre-commit` lintet gestagte Dateien vor jedem Commit: PHP → Laravel Pint (Auto-Fix), JS/CSS → Prettier (Auto-Fix), Blade → Prettier `--check` (blockt nur, kein Auto-Write). Nicht behebbare Fehler brechen den Commit ab.

**Aktivierung pro Clone** (nicht via Git geklont — lokale Config):
```
npm install
git config core.hooksPath .githooks
```
Configs: `pint.json` (laravel preset, `database/migrations` exkludiert), `.prettierrc.json`, `.prettierignore`. Blade nicht auto-formatieren — bewusst per `npx prettier --write <datei>` wenn der Hook eine Blade-Datei blockt.

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

### Vor jedem Merge (Pflicht-Checkliste)

Vor `mcp__github__merge_pull_request` immer prüfen:

1. **CHANGELOG** — Eintrag für heute (`## YYYY-MM-DD`) vorhanden?
2. **PR-Beschreibung** — spiegelt alle Commits seit dem letzten Merge wider?

Der Pre-Merge-Hook (`.claude/hooks/pre-merge-check.sh`) blockiert automatisch wenn CHANGELOG fehlt. PR-Beschreibung muss manuell geprüft/aktualisiert werden.
