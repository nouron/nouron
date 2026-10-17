# Claude Code Subagents — Nouron Browsergame Team

Subagenten-Definitionen für Nouron-Projekt.

## Sprachregeln (gilt für alle Agenten)

| Bereich | Sprache |
|---|---|
| PHP-Code, JS, CSS, Kommentare im Code | **Englisch** |
| Konfigurationskeys, DB-Spaltennamen | **Englisch** |
| `lang/de/*.php` Werte (User-facing Strings) | **Deutsch** |
| GDD, ROADMAP, CHANGELOG, ADRs | **Deutsch** |
| Blade-Templates (User-facing Text) | immer via `__('key')`, nie hardcoded |

## Agenten-Übersicht

| Agent | Zuständig für | DARF NICHT |
|---|---|---|
| `game-designer` | GDD, Mechanics Design, Balancing | Produktionscode schreiben |
| `game-developer` | Game Logic, Services, Tick-System | GDD schreiben, Frontend bauen |
| `backend-coder` | PHP/Laravel, Controller, API-Endpoints | GDD/ROADMAP bearbeiten, Frontend bauen |
| `ui-specialist` | Alpine.js/PicoCSS (neu), Bootstrap/jQuery (Legacy), Blade | PHP-Logik schreiben, lang-Werte setzen |
| `db-migration-agent` | Schema, Migrations, MySQL, Seeders | Game Logic, lang-Dateien ändern |
| `qa-tester` | Tests, Security, Regression, Cheat-Detection | Produktionscode ändern |
| `project-manager` | Roadmap, ADRs, Feature-Breakdown, CHANGELOG | Code schreiben, lang-Dateien ändern |
| `content-writer` | lang/de/*.php Texte, Lore, Tooltips, INNN | Code schreiben, Blade/JS ändern |

## Typischer Feature-Workflow

```
1. game-designer      → Mechanic definieren, GDD aktualisieren
2. db-migration-agent → Schema-Änderungen + Migration
3. game-developer     → Game Logic / Service implementieren
4. backend-coder      → Controller + API Endpoint
5. ui-specialist      → Frontend View + AJAX (Alpine.js + PicoCSS für neue Screens)
6. qa-tester          → Unit + Integration Tests (proaktiv nach jedem Schritt)
7. content-writer     → lang/de/*.php Texte, Tooltips, Lore (proaktiv bei neuen Entitäten)
8. project-manager    → CHANGELOG-Eintrag, ROADMAP aktualisieren (auf Anfrage)
```

## Frontend-Stack

| Screen-Typ | Stack |
|---|---|
| Neue Screens (Phase 3b+) | Alpine.js 3 + PicoCSS 2 + SVG |
| Legacy-Screens (pre-3b) | jQuery 3 + Bootstrap 5 (wird schrittweise migriert) |

**Nie mischen**: Kein jQuery auf neuen Screens, kein Alpine auf Legacy-Screens.

## Docs-Struktur

```
docs/
├── GDD.md              ← Game Design Document (game-designer)
├── adr/                ← Architecture Decision Records (project-manager)
ROADMAP.md              ← Projekt-Roadmap (project-manager)
CHANGELOG.md            ← Release-Log pro Session (project-manager)
```
## Briefing-Vorlage für Agenten-Aufträge

Jeder Auftrag an einen Agenten enthält diese Blöcke (kurz, aber vollständig; sie fehlen sonst erfahrungsgemäß):

1. **Ziel und Kontext:** eine Zeile Ziel; gemessene Fakten mit Quelle (Datei/Report), nie nur gerechnete Zahlen.
2. **Grenzen:** welche Dateien erlaubt sind (z. B. nur `tests/Feature/Playtest/**` und `tools/`); kein Commit/Push; Dev-DB `nouron` und Port 8000 tabu, wenn der Owner spielt; nichts ändern, was der Browser ausliefert, solange der Owner spielt.
3. **Datenbanken:** PHPUnit nur mit `TEST_TOKEN=9 LARAVEL_PARALLEL_TESTING=1 php bin/phpunit …` (Wegwerf-DB `nouron_test_test_9`); Browser-Checks mit `tools/uicheck/uicheck` (Token 8, Port 8012); Bot-Batches schreiben nach `nouron_playtest`; nur ein Batch gleichzeitig; `--concurrency` bewusst wählen (12, wenn der Owner parallel arbeitet).
4. **TDD:** Test zuerst, Rot bestätigen, dann Code (`CLAUDE.md`); bei UI-Aufträgen den Test **vor** der Implementierung schreiben.
5. **Messregeln:** vorab Hypothese mit erwarteten Zahlen; eine Änderung pro Messung; Berichtszahlen als *gemessen* (Quelle) oder *gerechnet* kennzeichnen; eigene Deutungen als *Annahme* (siehe Skill `nouron-balance-pass`). Batches mit Zeitfenster in einen Ordner sortieren und je Zelle die Report-Anzahl prüfen.
6. **Berichtsformat:** Höchstzeilen nennen, Fakten zuerst, Datei:Zeile für Fundstellen, Überraschungen als eigener Punkt, am Ende Owner-Fragen mit empfohlener Antwort.
7. **Begriffe:** `GLOSSAR.md` zuerst lesen; im Bericht den **Spielbegriff** verwenden (Labor, Baustelle, AP-Deckel), den Code-Schlüssel nur einmal in Klammern.
8. **Agenten ohne Shell** (game-designer, content-writer, project-manager) bekommen Auswertungen als fertige Tabelle im Auftrag; sie aggregieren nicht selbst.
