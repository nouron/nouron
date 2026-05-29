# Nouron — Spielreferenz

Vollständige Referenztabellen für Entwicklung. Wird von `CLAUDE.md` referenziert, aber nicht automatisch geladen.
Bei konkreten Coding-Aufgaben zu Ressourcen, Gebäuden, Schiffen, etc. diese Datei lesen.

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

## Ressourcen (6 aktiv)

| ID | Key | Name (DE) | Ebene | Handelbar | Startwert |
|----|-----|-----------|-------|-----------|-----------|
| 1 | credits | Credits | User | Nein | 3000 |
| 2 | supply | Versorgung | User | Nein | 10 (CC Lv1) |
| 3 | regolith | Regolith | Kolonie | Ja | 200 |
| 4 | compounds | Werkstoffe | Kolonie | Ja | 0 |
| 5 | organics | Organika | Kolonie | Ja | 0 |
| 12 | trust | Vertrauen | Kolonie | Nein | 0 |

## Gebäude (11 aktiv, CC-Level als Gate)

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

## Kenntnisse (7, alle via Analytik-Labor)

`construction`, `agronomy`, `health`, `cartography`, `geology`, `trade`, `defense`

## Schiffe (3 Typen)

| Key | Name | Supply | Stärkewert | Hangar nötig |
|-----|------|--------|-----------|--------------|
| `drone` | Sonde | 0 | 0 | Nein |
| `corvette` | Korvette | 14 | 3 | Ja |
| `freighter` | Frachter | 6 | 0 | Ja |

## Personal / Berater (5 Typen)

| Key | Name (DE) | AP-Typ |
|-----|-----------|--------|
| `engineer` | Baumeister | `construction` |
| `scientist` | Analytiker | `research` |
| `pilot` | Raumfahrer | `navigation` |
| `trader` | Konsul | `economy` |
| `strategist` | Stratege | `strategy` |

## Spielmechaniken

- **Tick-basiert**: Solo = manuell ausgelöst; Multiplayer = alle bestätigen oder Timeout (24–48h)
- **AP-System**: 5 unabhängige Pools (construction/research/navigation/economy/strategy), je 6 AP/Tick Grundwert
- **Gebäude-Verfall**: `status_points` in `colony_buildings`; Level-Down bei SP ≤ 0
- **Supply-Cap**: CC-Level + Wohnhabitate + Kenntnisse bestimmen max. Supply; Schiffe/Gebäude verbrauchen Supply
- **Encounters**: `attack`-Orders lösen Zwischenfälle aus; Korvette (Stärke 3) vs. NPC-Schiffe
- **Koordinatensystem**: 12×12-Grid pro System; Stern bei (6,6)

## Progressive Discovery System (GDD §17)

### Almanach-Artikel-Kategorien

| Kategorie-Key | Beschreibung |
|---------------|-------------|
| `mechanics` | Spielmechaniken (AP, Supply, Decay, Sol-Zyklus) |
| `buildings` | Gebäude-Beschreibungen und Effekte |
| `knowledge` | Kenntnisse und Forschungseffekte |
| `lore` | Hintergrundgeschichte, Planet, Nexus |
| `encounters` | Begegnungen, NPC-Typen, Taktiken |

### Almanach Freischalt-Trigger-Typen

| Trigger-Key | Parameter | Beschreibung |
|-------------|-----------|-------------|
| `always` | — | Immer verfügbar |
| `sol_reached:{n}` | n = Sol-Nummer | Run hat Sol n erreicht |
| `building_built:{key}` | key = Gebäude-Key | Gebäude erstmals gebaut |
| `objective_revealed:{key}` | key = Task-Key | Objective wurde enthüllt |
| `encounter_event:{type}` | type = Event-Typ | Bestimmter Encounter aufgetreten |
| `advisor_dialog:{key}` | key = Dialog-Key | Berater-Dialog abgeschlossen |

### Advisor Dialog Status-Werte

| Status | Bedeutung |
|--------|-----------|
| `pending` | Dialog-Trigger erfüllt, noch nicht im INNN-Feed |
| `offered` | INNN-Eintrag erzeugt, Spieler hat noch nicht geantwortet |
| `accepted` | Spieler hat angenommen, AP verbraucht, Bonus gutgeschrieben |
| `declined` | Spieler hat explizit abgelehnt (`is_skippable = true`, Spieler wählt "Ablehnen") |
| `expired` | Dialog automatisch aufgelöst — Postpone-Maximum (2×) oder `dialog_expire_after_ticks` überschritten |

### Neue Tabellen (Phase 4)

| Tabelle | Zweck |
|---------|-------|
| `almanac_articles` | Stammdaten aller Almanach-Artikel (key, category, unlock_trigger, bonus_type, bonus_value) |
| `run_almanac_unlocks` | Welche Artikel sind in diesem Run freigeschaltet (`run_id`, `article_key`, `unlocked_at_tick`) |
| `advisor_dialogs` | Laufende und abgeschlossene Berater-Dialoge pro Run (`run_id`, `advisor_type`, `dialog_key`, `status`, ...) |

### Neue Felder auf bestehenden Tabellen (Phase 4)

| Tabelle | Spalte | Typ | Zweck |
|---------|--------|-----|-------|
| `run_objectives` | `revealed_at_tick` | integer nullable | Sol der Objective-Enthüllung |
| `run_objectives` | `reveal_trigger` | string nullable | Wie wurde enthüllt (`advisor_dialog`, `sol_threshold`, `run_event`) |
| `runs` | `almanac_read_bonuses` | JSON | Liste von article_keys mit bereits gutgeschriebenen Boni |

---

## Testdaten in der DB

Simpsons-Testcharaktere: Homer (ID 0), Marge (1), Bart (3), etc.
2 Kolonien: "Springfield" (User Bart, Planet 1) und "Shelbyville" (kein User, Planet 1)
10 Flotten, 45 Fleet Orders, bcrypt-gehashte Passwörter.

## Vorhandene Offline-Dokumentation (NOURON-Ordner)

- `Nouron_Projektdokumentation_2026.pdf` — Vollständige Projektdoku (8 Abschnitte)
- `Nouron_Richtungsentscheidung.pdf` — Vergleich Classic vs. Light mit Scoring-Matrix
- `OneNote/` — 26 exportierte PDFs mit Design-Notizen aus 2012–2020
- `nouronzf2_dev_data.sql` — SQL-Dump (historisch)
- `sql/` — 15+ historische SQL-Dumps (2008–2009)
