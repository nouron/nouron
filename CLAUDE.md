# CLAUDE.md — Nouron Projektkontext

## Projekt-Überblick

**Nouron** ist ein Sci-Fi-Strategiespiel, entwickelt 2008–2014, seit 2026 wieder aktiv aufgenommen.
- GitHub: https://github.com/nouron/nouron
- Techstack: PHP/Laravel, SQLite, Blade-Templates, Alpine.js + PicoCSS (neu, Phase 3b+), SVG für Spielfelder
- Frontend-Migration: bestehende Screens nutzen noch jQuery + Bootstrap 5 (werden schrittweise auf Alpine.js + PicoCSS migriert — kein Mix in neuen Screens)
- Status: Laravel-Migration abgeschlossen, Design-Sprints DS-1–DS-4 abgeschlossen, Phase 3b (UI) als nächstes

## Aktueller Stand (Stand: Mai 2026)

Laravel-Migration abgeschlossen. Spielkonzept auf **Singleplayer Roguelike Mini-4X** umgestellt (FTL/Catan-Stil). Design-Sprints DS-1–DS-4 abgeschlossen. Phase 3 (UI-Redesign + neue Screens) aktiv.

**Abgeschlossen:** ZF2 → Laminas → Laravel Migration, Techtree-Redesign (Phasen-Layout), Tick-System, AP-System, Berater-System, Flottenoperationen, Decay-System, Moralsystem, Supply-System, INNN-Nachrichten, Hex-Grid Kolonieansicht, Systemkarte, Reisender Händler.

**Laufend (Phase 3):** UI-Migration von jQuery/Bootstrap auf Alpine.js + PicoCSS. Berater-Screen (Karussell), GDD-Cleanup, Onboarding.

**Spielkonzept:** Kleine, ressourcenarme Kolonie am Leben erhalten. Kein Imperiumsaufbau, keine Rassen, keine organisierten Kriege. Gefahren sind klein und lokal (ein Schiff begegnet dem Unbekannten, ein Ereignis trifft die Siedlung). Runs haben ein konkretes Ziel und ein klares Ende.

## Wichtige Korrekturen / Klarstellungen

- **Datenbank ist SQLite** (NICHT MySQL). Zwei separate DB-Dateien:
  - `data/db/nouron.db` — Entwicklungsdatenbank (läuft mit dem Dev-Server)
  - `data/db/test.db` — Testdatenbank (wird von den Unittests verwendet, per `data/sql/testdata.sqlite.sql` befüllt)
  - Für die Produktivphase ist ein Umstieg auf eine robustere DB (z.B. MySQL/PostgreSQL) geplant, bis dahin bleibt es bei SQLite
- Die Website ist eine **GitHub Page** im Repo (keine extra Sicherung nötig)
- `Routen.txt` ist vermutlich out-of-date
- `code/nouron_(pre_zend)/` ist stark veraltet und nicht mehr verwendbar
- **Nur der aktuelle Stand im GitHub Repository ist relevant**

## Datenbank-Schema (SQLite, 35 Tabellen)

### Kernstruktur

```
Galaxie:    glx_systems → glx_system_objects → glx_colonies
Spieler:    user → user_resources (Credits, Supply auf User-Ebene)
Kolonie:    colony_buildings, colony_resources, colony_researches, colony_ships, colony_personell
Flotten:    fleets → fleet_ships, fleet_resources, fleet_personell, fleet_researches
Befehle:    fleet_orders (tick-basiert, serialisierte PHP-Daten)
Handel:     trade_resources, trade_researches
Nachrichten: innn_messages, innn_events, innn_news, innn_message_types
Stammdaten: buildings, researches, ships, personell, resources + jeweilige _costs-Tabellen
```

### Ressourcen (6 aktiv)

| ID | Key | Name (DE) | Ebene | Handelbar | Startwert |
|----|-----|-----------|-------|-----------|-----------|
| 1 | credits | Credits | User | Nein | 3000 |
| 2 | supply | Versorgung | User | Nein | 10 (CC Lv1) |
| 3 | regolith | Regolith | Kolonie | Ja | 200 |
| 4 | compounds | Werkstoffe | Kolonie | Ja | 0 |
| 5 | organics | Organika | Kolonie | Ja | 0 |
| 12 | trust | Vertrauen | Kolonie | Nein | 0 |

### Gebäude (11 aktiv, CC-Level als Gate)

| Key | Name (DE) | CC-Lv | Phase |
|-----|-----------|-------|-------|
| `commandCenter` | Kommandozentrale | — | — |
| `housingComplex` | Wohnhabitat | 1 | 1 |
| `harvester` | Harvester | 1 | 1 |
| `bioFacility` | Agrardom | 1 + Harvester | 1 |
| `sciencelab` | Analytik-Labor | 2 | 2 |
| `depot` | Lagerhalle | 2 | 2 |
| `infirmary` | Krankenstation | 2 | 2 |
| `bar` | Cantina | 2 | 2 |
| `hangar` | Hangar | 3 | 3 |
| `temple` | Religiöse Stätte | 4 | 4 |
| `monument` | Kolonialdenkmal | 5 | 5 |

### Kenntnisse (7, alle via Analytik-Labor)

`construction`, `agronomy`, `health`, `cartography`, `geology`, `trade`, `defense`

### Schiffe (3 Typen)

| Key | Name | Supply | Stärkewert | Hangar nötig |
|-----|------|--------|-----------|--------------|
| `drone` | Sonde | 0 | 0 | Nein |
| `corvette` | Korvette | 14 | 3 | Ja |
| `freighter` | Frachter | 6 | 0 | Ja |

### Personal / Berater (5 Typen)

| Key | Name (DE) | AP-Typ |
|-----|-----------|--------|
| `engineer` | Baumeister | `construction` |
| `scientist` | Analytiker | `research` |
| `pilot` | Raumfahrer | `navigation` |
| `trader` | Konsul | `economy` |
| `strategist` | Stratege | `strategy` |

### Spielmechaniken

- **Tick-basiert**: Solo = manuell ausgelöst; Multiplayer = alle bestätigen oder Timeout (24–48h)
- **AP-System**: 5 unabhängige Pools (construction/research/navigation/economy/strategy), je 6 AP/Tick Grundwert
- **Gebäude-Verfall**: `status_points` in `colony_buildings`; Level-Down bei SP ≤ 0
- **Supply-Cap**: CC-Level + Wohnhabitate + Kenntnisse bestimmen max. Supply; Schiffe/Gebäude verbrauchen Supply
- **Encounters**: `attack`-Orders lösen Zwischenfälle aus; Korvette (Stärke 3) vs. NPC-Schiffe
- **Koordinatensystem**: 12×12-Grid pro System; Stern bei (6,6)

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
database/
  migrations/         -- Schema-Migrationen
  seeders/            -- TestSeeder (befüllt test.db aus testdata.sqlite.sql)
data/sql/
  testdata.sqlite.sql -- Testdaten (INSERT + UPDATE, wird von TestSeeder ausgeführt)
resources/views/      -- Blade-Templates
public/
  js/                 -- techtree-view.js, advisors.js, ...
  css/                -- techtree-view.css, advisors.css, ...
```

Schichtung: `Controller → Service → Eloquent Model → SQLite`

## Technische Hinweise

- `config/game.php` und `config/buildings.php` sind **canonical source of truth** für alle Spielwerte — GDD folgt der Config, nicht umgekehrt
- Neue Screens: Alpine.js + PicoCSS — kein jQuery, kein Bootstrap
- Legacy-Screens: noch jQuery + Bootstrap 5 — werden schrittweise migriert, kein Mix in neuen Screens
- `TestSeeder` führt `data/sql/testdata.sqlite.sql` aus (regex-filtered: nur INSERT/UPDATE Statements)
- Techtree-Koordinaten sind phase-lokal (Zeile/Spalte innerhalb einer Phase), nicht global
- Moral-Events: Keys `encounter_won`, `encounter_lost`, `colony_threatened` (nicht `combat_*`)

## Vorhandene Dokumentation (im NOURON-Ordner)

- `Nouron_Projektdokumentation_2026.pdf` — Vollständige Projektdoku (8 Abschnitte)
- `Nouron_Richtungsentscheidung.pdf` — Vergleich Classic vs. Light mit Scoring-Matrix
- `OneNote/` — 26 exportierte PDFs mit Design-Notizen aus 2012–2020
  - Wichtigste: "Supply und Aktionspunkte", "Politiksystem", "Nouron Light (V2)", "Abgrenzungen"
- `nouronzf2_dev_data.sql` — SQL-Dump (historisch, SQLite ist aktuell)
- `sql/` — 15+ historische SQL-Dumps (2008–2009)

## Testdaten in der DB

Die DB enthält Simpsons-Testcharaktere: Homer (ID 0), Marge (1), Bart (3), etc.
2 Kolonien: "Springfield" (User Bart, Planet 1) und "Shelbyville" (kein User, Planet 1)
10 Flotten, 45 Fleet Orders, bcrypt-gehashte Passwörter.

## Sprachregeln

| Bereich | Sprache |
|---|---|
| PHP-Code, JS, CSS, Kommentare im Code | **Englisch** |
| Konfigurationskeys, DB-Spaltennamen | **Englisch** |
| `lang/de/*.php` Werte (User-facing Strings) | **Deutsch** |
| GDD, ROADMAP, CHANGELOG, ADRs | **Deutsch** |
| Blade-Templates (sichtbare Texte) | immer via `__('key')`, nie hardcoded |

## Subagenten (`.claude/agents/`)

Spezialisten für einzelne Aufgabenbereiche. Werden **proaktiv** eingesetzt — nicht erst auf Nachfrage:

- `game-designer` — Mechanics definieren, GDD aktualisieren (vor jeder neuen Mechanik)
- `game-developer` — Game Logic, Services, Tick-Verarbeitung
- `backend-coder` — Controller, Routes, API-Endpoints, Middleware
- `ui-specialist` — Blade, Alpine.js + PicoCSS (neu), Bootstrap/jQuery (Legacy)
- `db-migration-agent` — Schema, Migrations, SQLite, testdata.sqlite.sql
- `qa-tester` — Tests schreiben (nach jeder Implementierung automatisch)
- `content-writer` — lang/de/*.php Texte, Lore, Tooltips (bei neuen Entitäten automatisch)
- `project-manager` — ROADMAP, CHANGELOG, ADRs, Feature-Breakdown

Abgrenzung: Jeder Agent schreibt nur in seinem Bereich. Details in `.claude/agents/README.md`.

## Workflow-Hinweise

- Entwicklungsumgebung: Ubuntu unter WSL2 (Windows 11)
- Claude Code wird für die Weiterentwicklung verwendet
- Der Owner heißt Mario (tech.mario@outlook.de)

### Git-Workflow (verbindlich)

**Nie direkt auf `master` committen oder pushen** — auch nicht nach einem Merge wenn man bereits auf `master` ist.

Ablauf für jede Änderung:
1. `git checkout -b feat/<name>` — neuen Branch anlegen
2. Commits auf dem Branch
3. `git push origin feat/<name>`
4. PR auf GitHub erstellen

Wenn GitHub beim Push warnt *"Changes must be made through a pull request"*: Push sofort abbrechen, Branch anlegen, PR erstellen. Branch-Protection-Regeln niemals bypassen.

## Changelog-Pflege

Am Ende jeder Session, in der Code-Arbeit stattgefunden hat, wird ein Eintrag in `CHANGELOG.md` im Projekt-Root ergänzt. Format:

```
## YYYY-MM-DD

- Kurze Beschreibung der erledigten Aufgaben (1–3 Sätze pro Thema)
- ...
```

Der Changelog soll als Grundlage für Retrospektiven und Blog Posts dienen. Einträge werden auf Deutsch verfasst, prägnant und inhaltlich (was wurde gemacht, warum).
