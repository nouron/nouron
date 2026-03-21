# Claude Code Subagents — Browsergame Team

Dieses Verzeichnis enthält die Subagenten-Definitionen für das Browsergame-Projekt.

## Installation

Kopiere den Ordner `.claude/agents/` in das **Root-Verzeichnis deines Projekts**:

```
dein-projekt/
├── .claude/
│   └── agents/
│       ├── game-developer.md
│       ├── backend-coder.md
│       ├── ui-specialist.md
│       ├── db-migration-agent.md
│       ├── qa-tester.md
│       ├── project-manager.md
│       └── game-designer.md
├── src/
├── public/
└── ...
```

Alternativ kannst du die Agents **global** installieren (für alle Projekte verfügbar):

```bash
mkdir -p ~/.claude/agents
cp .claude/agents/*.md ~/.claude/agents/
```

## Agenten-Übersicht

| Agent | Zuständig für | Invoke mit |
|---|---|---|
| `game-developer` | Game Mechanics, Game Loop, Server-side Logic | `@game-developer` |
| `backend-coder` | PHP/Laminas, Controller, API, Laravel-Migration | `@backend-coder` |
| `ui-specialist` | Bootstrap 5, jQuery, Templates, AJAX | `@ui-specialist` |
| `db-migration-agent` | Schema, Migrations, SQLite→Prod, Laminas→Eloquent | `@db-migration-agent` |
| `qa-tester` | Tests, Security, Regression, Cheat-Detection | `@qa-tester` |
| `project-manager` | Roadmap, ADRs, Feature-Breakdown, Migration-Plan | `@project-manager` |
| `game-designer` | GDD, Balancing, Mechanics Design, Fun Review | `@game-designer` |

## Typischer Feature-Workflow

```
1. @project-manager   → Feature in Tasks aufteilen, ADR falls nötig
2. @game-designer     → GDD aktualisieren, Mechanic definieren, Balance-Werte
3. @db-migration-agent → Schema-Änderungen + Migration schreiben
4. @game-developer    → Game Logic / Service implementieren
5. @backend-coder     → Controller + API Endpoint
6. @ui-specialist     → Frontend View + AJAX
7. @qa-tester         → Unit + Integration Tests schreiben
```

## Docs-Struktur (wird von Agenten erstellt)

```
docs/
├── GDD.md              ← Game Design Document (@game-designer)
├── adr/                ← Architecture Decision Records (@project-manager)
├── balancing/          ← Balance-Changelogs (@game-designer)
ROADMAP.md              ← Projekt-Roadmap (@project-manager)
CHANGELOG.md            ← Release-Log (@project-manager)
MIGRATION_LOG.md        ← DB-Migration-Log (@db-migration-agent)
```
