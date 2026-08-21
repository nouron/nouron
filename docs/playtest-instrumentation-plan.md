# Playtest-Instrumentierung — Umsetzungsplan (GDD A.5)

**Status:** Planung (Owner-Entscheidung erforderlich)  
**Erstellt:** 2026-08-21 (project-manager)

## Ausgangslage & Konflikt

**GDD A.5 fordert:** Alle 11 Metriken müssen "vor dem ersten Lauf einzubauen" sein.

**Gegenwärtig vorhanden:** 6 von 11 Metriken in RunReport-Snapshots (Regolith/Organika/Credits/AP/Trust/CC-Level).

**Dieser Plan schlägt vor:** 
- **Alle 11 Metriken vor erstem Lauf, 0 DB-Tabellen neu**
  - 9 Metriken: Live-erfasst pro Sol (B1 Phase)
  - 2 Metriken: Post-Lauf-Aggregation aus Live-Daten (B2 Phase)

---

## Gap-Analyse: 11 Metriken

| # | Metrik | Status | Lösung |
|---|---|---|---|
| 1 | **AP-Bilanz** (Zufluss/Reparatur/Projekte/Handlungen/ungenutzt) | Nur `ap_unspent` | Snapshot: AP-Breakdown pro Sol (B1a) |
| 2 | **Sole bis Fertigstellung** je Projekt | ❌ | Nachberechnung: Level + ap_spend Diffing (B2a) |
| 3 | **Median gleichzeitiger Baustellen** | ❌ | Aus Projekt-Diffs ableitbar (B2a) |
| 4 | **Sol der letzten Projekt-Fertigstellung** | ❌ | Aus Projekt-Ende ableitbar (B2a) |
| 5 | **Instandhaltungsanteil am Pool** | ❌ | AP-Breakdown: `repair_spent / total` (B1a) |
| 6 | **Regolith-Bilanz** (Quelle-Breakdown) | Nur total | Snapshot: Quelle-Attribution pro Sol (B1c) |
| 7 | **Supply-Auslastung** (used/cap + Sole über Cap) | ❌ | Snapshot: Supply-Berechnung (B1d) |
| 8 | **Sole mit 0 AP je Domäne** | ❌ | Nachberechnung: Filter über Snapshots (B2c) |
| 9 | **Regolith-Durchsatz je Pfad** (Frachter/Geologie/Cantina) | ❌ | Nachberechnung: Quelle-Aggregation nach Pfad (B2b) |
| 10 | **Harvester-Umzüge** pro Run | ❌ | Snapshot: Positionen + Action-Log-Filter (B1e) |
| 11 | **Organika-Bilanz** (Quelle-Breakdown) | Nur total | Snapshot: wie Regolith (B1c) |

---

## Implementierungs-Roadmap

### Phase B1: Extended Snapshots (Datenerfassung)
**Duration:** 2–3 Sessions | **Owner:** `game-developer`, `qa-tester`

Alle Tasks sind im Test-Harness (RunReport::snapshot()), keine Game-Logic-Änderungen.

#### B1a: AP-Breakdown pro Sol
- Extend `RunReport::snapshot()`: Action-Log diese Sol
- Aggregiere AP pro Typ: `inflow` (Berater), `repair_spent`, `project_spent` (Bau+Research), `action_spent` (Erkunden), `unspent`
- **Test (TDD):** Summen stimmen, alle Kategorien vorhanden

#### B1b: Building-Level + AP-Spend Snapshot
- Query `colony_buildings`, speichere pro Instanz: `{level, ap_spend}`
- Format: `'buildings' => {'25:1' => {level: 3, ap_spend: 22}, ...}`
- Kritisch für Metriken 2–4: `ap_spend` trackt wann Projekt startet, `level` wann es endet
- **Test (TDD):** Keine Instanz fehlt, ap_spend ≥ 0

#### B1c: Regolith- & Organika-Quellen
- Klassifiziere jede Action im Log nach Quelle (harvester, mission, trade, event)
- Speichere: `'regolith_sources' => ['harvester' => 8, 'mission' => 2, ...]`
- **Test (TDD):** Quellen-Summen = Bestandsdelta (Anfang–Ende Sol)

#### B1d: Supply-Snapshot
- Berechne `supply_used` (sum aller Gebäude-Kosten), `supply_cap` (GDD §6 Formel)
- Speichere: `'supply' => {used, cap, utilization}`
- **Test (TDD):** utilization ∈ [0, 1], Cap ≥ Used

#### B1e: Harvester-Move-Tracking
- Query Harvester-Positionen (tile_x, tile_y) beide Instanzen
- Speichere: `'harvester_positions' => {'1' => [5, 3], ...}`
- **Test (TDD):** Position-Änderung ↔ `relocate_harvester` Action

### Phase B2: Post-Lauf-Aggregation
**Duration:** 2 Sessions | **Owner:** `game-developer`, `qa-tester`

#### B2a: Projekt-Metriken (Metriken 2–4)
- Leite aus B1b-Snapshots Projekt-Start/End ab.
- **Start** = erster Sol mit `ap_spend > 0`
- **End** = Sol mit `level`-Inkremente
- Aggregiere: `project_durations`, `last_completion_sol`, `median_concurrent_projects`
- **Test (TDD):** Multi-Sol-Accumulation korrekt

#### B2b: Regolith-Pfad-Attribution (Metrik 9)
- Klassifiziere nach Pfad: A (Frachter/mission), B (Geologie/harvester), C (Cantina/trade)
- Aggregiere über Run
- **Test (TDD):** Summe ≈ (final − start + consumed)

#### B2c: Sole mit 0 AP (Metrik 8)
- Filter Snapshots: zähle wo `ap_unspent <= 0`
- Speichere: count + Liste der Sole-Nummern
- **Test (TDD):** count ≤ total sols

### Phase C: Dashboard-Erweiterung
**Duration:** 3–4 Sessions | **Owner:** `ui-specialist`

7 neue Charts:
1. **AP Breakdown (Stacked Area)** — Metriken 1 & 5
2. **Regolith Sources (Stacked Area)** — Metriken 6 & 9
3. **Organika Sources (Stacked Area)** — Metrik 11
4. **Supply Utilization (Line + Zone)** — Metrik 7 (70%-Zielmarke)
5. **Ongoing Projects (Stacked Bar)** — Metrik 3
6. **Project Timeline (Gantt)** — Metriken 2 & 4
7. **Harvester Moves (Scatter + Line)** — Metrik 10

### Phase D: Summary-Tabelle
**Duration:** 1 Session | **Owner:** `ui-specialist`

Extend Tabelle um: `AP Ungenutzt %`, `Rego Top-Quelle`, `Supply Max %`, `Projekte Count`, `Harvester Moves`, `Last Project Sol`

---

## Kritischer Pfad

```
B1a (AP) → B1b (Buildings) → B2a (Projekte) → C (Dashboard)
         ↘ (parallel: B1c, B1d, B1e) ↗
```

---

## MVP-Kriterium (vor `game:playtest`)

**Blockierend:**
- ✅ B1a–e: Alle Snapshots
- ✅ B2a–c: Projekt-Metriken, Pfad-Attribution, 0-AP-Filter
- ✅ C: Min. 3 Charts (AP, Regolith, Supply)
- ✅ TDD-Tests: Grün

**Iterativ später:**
- C: Restliche 4 Charts
- D: Summary-Tabelle

---

## Aufwand-Schätzung

| Phase | Aufwand | TDD |
|---|---|---|
| B1 (Snapshots) | 8h | 5h |
| B2 (Aggregation) | 3.5h | 2h |
| C (Dashboard) | 6h | — |
| D (Summary) | 1h | — |
| **Total** | **~22h** | **~7h** |

---

## Validierungs-Checkliste (vor Playtest)

- [ ] RunReport::snapshot() enthält B1a–e Felder
- [ ] PlaytestBotTest grün
- [ ] Dashboard lädt & rendert Min. 3 Charts
- [ ] RunReport::build() aggregiert B2a–c
- [ ] Report-JSON enthält alle 11 Metriken

---

## Offene Owner-Entscheidungen

1. **Pfad-Definition (Metrik 9):** Mechanismen (Frachter/Geologie/Cantina) oder Kenntnisse?
2. **0-AP nach Domäne (Metrik 8):** Für Zukunft vorbereiten?
3. **Organika-Consumption:** Dynamisch per Aktion oder nur End-of-Sol?

---

**Relevant:** `tests/Feature/Playtest/RunReport.php`, `tools/playtest-dashboard.php`, `config/knowledge.php`
