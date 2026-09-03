# Nouron — Spielreferenz: Zahlen-Lookup (Stand 2026-08-21)

Vollständige Referenztabellen für Coding-Aufgaben. Wird von ADR 0004 und CLAUDE.md referenziert.
Diese Datei ist ein **Hand-Maintained Snapshot** — keine automatische Generierung. Bei Änderungen in `config/*.php` aktualisieren.

---

## 1. Ressourcen (6 aktiv)

| ID | Key | Name (DE) | Ebene | Handelbar | Startwert |
|---|---|---|---|---|---|
| 1 | `credits` | Credits | User | Nein | 3000 |
| 2 | `supply` | Versorgung | User | Nein | 10 |
| 3 | `regolith` | Regolith | Kolonie | **Ja** | 200 |
| 4 | `compounds` | Werkstoffe | Kolonie | **Ja** | 0 |
| 5 | `organics` | Organika | Kolonie | **Ja** | 0 |
| 12 | `trust` | Vertrauen | Kolonie | Nein | 0 |

---

## 2. Gebäude: Build-Kosten (Initialausbildung)

| Gebäude | CC-Gate | Build-Kosten (Rg / Wk) | Supply-Kosten | Max Level | Max Instanzen |
|---|---|---|---|---|---|
| **Commandenter (CC)** | — | — | 0 | 5 | 1 |
| **Wohnhabitat** | Lv1 | 40 Rg | 0 | 6 | 6 |
| **Harvester** | Lv1 | — | 2 | 1 | 2 |
| **Agrardom** | 1 + Harv | 70 Rg | 2 | 8 | 1 |
| **Analytik-Labor** | Lv2 | 95 Rg | 6 | ∞ | 1 |
| **Lagerhalle** | Lv2 | ? | ? | ∞ | 1 |
| **Krankenstation** | Lv2 | 60 Rg / 25 Wk | 10 | ∞ | 1 |
| **Cantina** | Lv2 | 95 Rg | 6 | ∞ | 1 |
| **Hangar** | Lv2 | 95 Rg | 6 | 3 | ∞ |
| **Religiöse Stätte** | Lv4 | 50 Rg / 15 Wk | 4 | 1 | 1 |
| **Kolonialdenkmal** | Lv5 | 60 Rg / 25 Wk | 2 | 1 | 1 |
| **Security Hub** | Lv3 | 80 Rg / 25 Wk | 8 | 3 | 1 |
| **Uplink Station** | Lv2 | 80 Rg | 6 | 3 | 1 |
| **Trading Post** | Lv4 | 100 Rg / 25 Wk | 6 | 3 | 1 |

> **Rg** = Regolith, **Wk** = Werkstoffe (compounds)
> Nicht eingetragen: Lagerhalle (Depot, config fehlt explizit)

---

## 3. Gebäude: Levelup & Repair

| Aspekt | Wert |
|---|---|
| **Levelup-Kosten (alle außer CC)** | 25 Rg (flat) |
| **CC-Levelup-Kosten** | Ziel-Level × 30 Rg |
| **Reparatur pro Punkt (SP)** | 1 Rg / Klick |
| **CC-Lv2 Regolith Total** | 60 Rg (von 200 Startwert) |

---

## 4. Gebäude: Decay-Raten & Status-Points

Decay-Rate = **Status Points pro Tick** verloren (multipliciert ggü. Supply-Overcap ×2).

| Gebäude | Klasse | Decay-Rate | Max SP | Tage bis Level-Down |
|---|---|---|---|---|
| Commandzentrale | Robust | 0.40 | 20 | ~50 Sol |
| Wohnhabitat | Robust | 0.40 | 20 | ~50 Sol |
| Harvester | Beansprucht | 0.80 | 20 | ~25 Sol |
| Agrardom | Standard | 0.60 | 20 | ~33 Sol |
| Analytik-Labor | Beansprucht | 0.80 | 20 | ~25 Sol |
| Cantina | Beansprucht | 0.80 | 20 | ~25 Sol |
| Hangar | Standard | 0.60 | 20 | ~33 Sol |
| Krankenstation | Beansprucht | 0.80 | 20 | ~25 Sol |
| Religiöse Stätte | Fragil | 1.20 | 20 | ~17 Sol |
| Kolonialdenkmal | Robust | 0.40 | 20 | ~50 Sol |
| Security Hub | Standard | 0.60 | 20 | ~33 Sol |
| Uplink Station | Standard | 0.60 | 20 | ~33 Sol |
| Trading Post | Standard | 0.60 | 20 | ~33 Sol |

---

## 5. Berater (Advisors): Hire-Kosten & AP-Beiträge

### Hire-Kosten (Rang 1)
| Typ | Key | Hire-Kosten | AP-Pool |
|---|---|---|---|
| Baumeister | `engineer` | 300 Cr | construction |
| Analytiker | `scientist` | 400 Cr | research |
| Raumfahrer | `pilot` | 500 Cr | navigation |
| Konsul | `trader` | 350 Cr | economy |

### Rank-Progression
| Rang | Active-Ticks Threshold | AP/Tick Bonus | Upkeep Cr/Tick | Promotion Cost |
|---|---|---|---|---|
| Rang 1 | — | +2 AP | 10 Cr | — |
| Rang 2 | 15 Ticks | +3 AP | 25 Cr | 150 Cr |
| Rang 3 | 45 Ticks | +4 AP | 35 Cr | 250 Cr |

> **Upkeep**: Cr/Tick deducted after passive income in GameTick (GDD §12)
> **Promotion**: One-time cost when advisor reaches that rank

---

## 6. Kenntnisse (7): Levelup-Kosten & Effekte

Alle levelup via Analytik-Labor. Keine Credits-Kosten (=0). Alle Kurven glockenförmig für Supply-Cap-Bonus.

| Kenntnis | Levelup-AP (Lv1→5) | Σ AP | Trust/Lv | Effekt |
|---|---|---|---|---|
| **construction** | 20/28/36/44/52 | 180 AP | 0 | Bau-AP −2/−4/−4/−3/−2 (Σ−15%) |
| **cartography** | 20/28/36/44/52 | 180 AP | 0 | Navigation-AP (Tile-Erkundung, Hangar-Missions-Reisekosten) −4/−8/−8/−6/−4% (Σ−30%) |
| **geology** | 20/28/36/44/52 | 180 AP | 0 | Harvester +3/+3/+2/+2/+2 Rg/Sol; Instabilität −3/−5/−5/−4/−3% |
| **agronomy** | 20/28/36/44/52 | 180 AP | +1 | Agrardom +1/+2/+2/+1/+1 Or/Sol |
| **health** | 20/28/36/44/52 | 180 AP | +2 | Seuchenausbruch-Risiko −3/−5/−5/−4/−3% |
| **trade** | 20/28/36/44/52 | 180 AP | 0 | Bau-AP −2/−4/−4/−3/−2 (Σ−15%); Bar-Slots +0/+1/+1/+0/+0; Handelspreis-Bonus +2/+3/+3/+2/+2% (Σ12%) |
| **defense** | 20/28/36/44/52 | 180 AP | +1 | Sturm-Risiko −3/−5/−5/−4/−3% |

> **CC-Level Gate**: Lv4 & Lv5 knowledge erfordern CC Lv4 bzw. Lv5
> **Supply-Cap Bonus**: alle Kenntnisse +3/+5/+5/+4/+3 = 20 max pro Kenntnis

---

## 7. Schiffe

| Schiff | Supply-Kosten | Max SP | Decay-Rate | Hangar-Gate | Stärkewert |
|---|---|---|---|---|---|
| **Drohne** | 0 | ? | ? | Nein | 0 |
| **Frachter** | 0 | ? | ? | Ja (Hangar Lv1) | 0 |
| **Korvette** | 0 | ? | ? | Ja (Hangar Lv3) | 3 |

> **Supply-Kosten**: alle Schiffe 0 — Schiffe verbrauchen kein Supply (Design-Entscheidung 2026-06-08, `config/ships.php` → `supply_cost`)

> **Hangar-Level = Schiffsklasse**: Lv1 = Drohne, Lv2 = Frachter, Lv3 = Korvette
> Instanzen sind separate Achse (supply-limitiert, unbegrenzt Slots theoretisch)

---

## 8. Missionen: Belohnungen

### Drohne
| Mission | Sol-Distanz | Organika-Kosten | **Belohnung** |
|---|---|---|---|
| `mission_courier_run` | 1 | 3 Or | 90 Cr |
| `mission_recon_flight` | 1 | 3 Or | 2 Tiles reveal |
| `mission_deep_survey` | 2 | 6 Or | 1 Deep Scan |
| `mission_prospecting_flight` (Geo Lv1+) | 2 | 6 Or | 20–30 Rg |
| `mission_data_sweep` (Cart Lv1+) | 3 | 9 Or | 8 Research AP |
| `mission_long_range_expedition` (Cart Lv3+) | 5 | 15 Or | 350–550 Cr / 8–12 Wk / 30–45 Rg (1 pick) |

### Frachter
| Mission | Sol-Distanz | Organika-Kosten | **Belohnung** |
|---|---|---|---|
| `mission_supply_run` | 1 | 3 Or | 25 Rg / 10 Or |
| `mission_trade_convoy` (Trade Lv1+) | 3 | 9 Or | 260 Cr + Trade Success (+2 Trust) |
| `mission_aid_transport` | 2 | 6 Or + 10 Or extra | 90 Cr + Encounter Won (+2 Trust) |

### Frachter / Korvette
| Mission | Sol-Distanz | Organika-Kosten | **Belohnung** |
|---|---|---|---|
| `mission_salvage_sweep` (Constr Lv1+) | 4 | 12 Or | 6–10 Wk |
| `mission_ruin_expedition` | 4 | 12 Or | 220 Cr (1x pro Ruin) |
| `mission_harvester_salvage` | 4 | 12 Or | Harvester Instanz #2 (1x pro Ruin) |

### Korvette
| Mission | Sol-Distanz | Organika-Kosten | **Belohnung** |
|---|---|---|---|
| `mission_escort_convoy` | 3 | 9 Or | 280 Cr |

> **Organika-Kosten** (Provisions): base = sol_distance × 3 Oder, mit Knowledge-Scaling −1 pro Level (Floor 1)
> **Dispatch Anforderung**: Schiff ≥25% Max-SP

---

## 9. Action Points (AP)

### Base & Advisor-Bonus
| Quelle | AP/Tick |
|---|---|
| **Base (Colony)** | 12 AP (1 shared pool) |
| **Advisor Rang 1** | +2 AP |
| **Advisor Rang 2** | +3 AP |
| **Advisor Rang 3** | +4 AP |

**Maximum**: 12 + (4 advisors × 4 AP/Rank3) = 28 AP/Tick theoretisch.

### AP-Kosten (Beispiele)
| Aktion | AP-Typ | Kosten |
|---|---|---|
| Gebäude-Levelup | construction | *Individuell; Rabatt via construction/trade* |
| Kenntnis-Levelup | research | *20–52 je Ziel-Level* |
| Feld erkunden | navigation | 1–3 (ringabhängig: Ring 1=1, Ring 2=2, Ring 3=3); Rabatt via cartography |
| Handel annehmen | economy | 1 |
| Handel verhandeln | economy | 3 (+ risk) |

---

## 10. Supply-Cap-Formel

```
Cap = min(
  CC-Level × 10  
  + Wohnhabitate × (8 × ihre_Level)  
  + Σ(Knowledge-Lv × Bonus per Lv)  
  + 200  /* hard max */
  , 200  /* absolute cap */
)
```

**Knowledge Supply-Cap Bonus** (pro Kenntnis): 3/5/5/4/3 = 20 max.

**Beispiel Phase 1 (CC Lv1, 1× Housing Lv1, keine Kenntnisse)**:
- CC: 1 × 10 = 10
- Housing: 1 × 8 = 8
- Total: 18 Supply Cap

---

## 11. Credits: Passive Income

Alle Werte Cr/Tick, angewendet nach Ressourcen-Generierung in GameTick Schritt 8b.

| Quelle | Bedingung | Wert |
|---|---|---|
| **Nexus-Subsidy** | CC > 0 | 50 Cr/Tick |
| **Relay-Bonus** | Uplink-Station je Level | 45 Cr/Tick × Uplink-Lv |
| **Konsul-Handelsvertrag** | Trader Rang 1–3 + Bar Lv1+ | 10/25/45 Cr/Tick je Rang |

---

## 12. Credits: Kosten

| Aktion | Betrag |
|---|---|
| Advisor Hire (Rang 1) | je Typ: 300–500 Cr |
| Advisor Rank 1 → 2 Promotion | 150 Cr (einmalig) |
| Advisor Rank 2 → 3 Promotion | 250 Cr (einmalig) |
| Advisor Upkeep (Rang 1–3) | 10/25/35 Cr/Tick (laufend) |

---

## 13. Trust-Events (einmalig, Σ stacking strongest-only)

| Event | Trust-Delta | Trigger |
|---|---|---|
| Gebäude Level-Up | +1 | Jeder Level-Up |
| Gebäude Level-Down | −3 | Decay, SP ≤ 0 |
| Kenntnis Level-Up | +2 | Jeder Level-Up |
| Handel erfolgreich | +2 | Bar-Angebot akzeptiert |
| Handel blockiert | −3 | Bar-Angebot scheitert / abgelehnt |
| Vertrag unterzeichnet | +3 | (zukünftig, nicht implementiert) |
| Organika "gut versorgt" | +1 | Diese Sol: Vorrat ≥ Bedarf |
| Hunger-Strafe | −2 bis −8 | Hunger-Streak, linear bis Cap |
| Encounter gewonnen | +2 | Mission erfolgreich (z.B. `encounter_won`) |
| Encounter verloren | −4 | Beschädigte Begegnung (33–65% SP) |
| Kolonie bedroht | −5 | Kritische Begegnung (<33% SP) |
| Kolonisten-Zulage (klein) | +2 | Spieler zahlt 100 Cr |
| Kolonisten-Zulage (mittel) | +3 | Spieler zahlt 300 Cr |
| Kolonisten-Zulage (groß) | +4 | Spieler zahlt 600 Cr |
| Nexus-Kredit | −5 | Schiff gekauft on Debt |

**Stacking-Regel**: Nur der stärkste Event pro Tick zählt (kein Addieren mehrerer same-key events).

---

## 14. Trust-Bänder & Multiplikatoren

Gesamttrust = clamp(Σ buildings + Σ researches + clamp(Σ ships, −30, +30) + events, −100, +100)

### Production Multiplier (Ressourcen/Sol)
| Trust-Band | Factor |
|---|---|
| 61–100 | ×1.20 |
| 21–60 | ×1.10 |
| −20 bis +20 | ×1.00 |
| −60 bis −21 | ×0.85 |
| −100 bis −61 | ×0.70 |

### AP Multiplier
| Trust-Band | Factor |
|---|---|
| 61–100 | ×1.10 |
| 21–60 | ×1.05 |
| −20 bis +20 | ×1.00 |
| −60 bis −21 | ×0.90 |
| −100 bis −61 | ×0.80 |

---

## 15. Food (Organika) System

| Aspekt | Wert |
|---|---|
| **Verbrauch pro Tick** | floor(Used Supply / 4) Organika |
| **"Gut versorgt" Bonus** | +1 Trust (wenn Bestand ≥ Bedarf) |
| **Hunger-Strafe Base** | −2 Trust auf erste hungrige Sol |
| **Hunger-Strafe Step** | +1 pro weitere Sol in Streak |
| **Hunger-Strafe Cap** | −8 Trust (max) |

---

## 16. Begegnungen (Encounters) — Trigger-Chancen

Alle Chancen Richtwerte (erste Kalibrierung), zu tunen nach PlaytestBot.

### Phase-1 Ramp
Trigger-Chancen ramp linear von 0 bis volle Stärke über erste 15 Sol.

| Begegnung | Typ | Base-Chance | Per-Building | Cap |
|---|---|---|---|---|
| **Sturm** (Weather) | 1.2% | — (entfernt, s. u.) | 2% |
| **Geologische Instabilität** | 0.15% × Sol seit Harv-Umzug | — | 5% |
| **Seuchenausbruch** (Plague) | 5% (nur wenn Hunger ≥3 oder Trust<−20) | — | — |

> **Sturm-Re-Kalibrierung 2026-09-03:** Seit dem Wechsel auf koloniweiten Wirkbereich (GDD §9) trifft ein ausgelöster Sturm ALLE Colony-Zone-Gebäude gleichzeitig statt eines einzelnen — die Gesamtschwere skaliert dadurch bereits von selbst mit der Gebäudeanzahl. Ein zusätzlicher `chance_per_building`-Term hätte dasselbe Kolonie-Größen-Signal doppelt eingepreist (Trigger-Häufigkeit UND Schadenssumme). `chance_per_building` wurde daher entfernt und `base_chance` gesenkt, damit die erwartete Schadenssumme/Sol für eine typische Mid-/Lategame-Kolonie (5-12 Gebäude) grob auf dem alten, gedeckelten Einzelziel-Niveau bleibt statt mit der Gebäudeanzahl zu explodieren. Platzhalter-Größenordnung, Nachjustierung nach Playtest — siehe `config/game.php` (`game.encounter.storm.*`) für den Rechenweg-Kommentar.

> **Cooldown**: 3 Sol nach jeder resolved Begegnung, keine neue WARNING

### Begegnungs-Ergebnisse (SP-Prozent)
| Tier | SP-Range | Event | Trust-Delta |
|---|---|---|---|
| Abgewehrt | ≥66% | Erfolgreich | +2 (encounter_won) |
| Beschädigt | 33–65% | Getroffen | −4 (encounter_lost) |
| Kritisch | <33% | Kritisch | −5 (colony_threatened) |

---

## 17. Harvester-System

### Depletion (GDD §4c)
```
Ertrag = FreshYield × (0.5 + 0.5 × Restvorkommen / ResourceMax)
```

**Fresh Yields** (Regolith pro Sole):
| Tile-Typ | Fresh Yield | Resource Max |
|---|---|---|
| regolith_rich | 30 Rg | 500 Rg |
| regolith_normal | 23 Rg | 300 Rg |
| regolith_poor | 15 Rg | 160 Rg |

**Relocation**: 1–2 AP pro Hex (GDD §4c: 2 AP/Hex)

**Second Harvester Instance**:
- Gate: CC Lv3 (ownership entitlement)
- Sources:
  - Weg A: Orin (corporate_contact, 400–800 Cr, 30% chance alle 15–25 Sol)
  - Weg B: Mission salvage (1× pro Ruin tile, damaged at 25% SP)

---

## 18. Run-Struktur

| Aspekt | Wert |
|---|---|
| **Tick Limit** | 100 Ticks (default) |
| **Tick Length** | 24 hours (1 Sol = 1 Tick) |
| **Phase 1 Deadline** | Sol 30 (hard fail) |
| **Phase 1 Warning** | Sol 22 (Nexus escalates) |
| **Trust Fail Threshold** | Trust < −20 (instant fail) |
| **Nexus Debt Cap** | 12000 Cr (instant fail) |

### Objectives (GDD §15)
Player muss 8 Task Pools durchsuchen; jede Task ein streamed Ziel.

**Task Credit Reserve**: 4000 Cr (14 Sols halten)

**Nexus Milestones**:
- Sol 30: ≥1 Task >50% done oder WARNING
- Sol 50: ≥1 Task complete oder WARNING
- Sol 85: ≥1 Task complete oder Advisor Penalty + Deadline→95
- Sol 90: Last warning

### Score Formula
```
Score = (Tasks_Done × 1000)
       + (Tick_Limit - Done_At_Tick) × 10
       + Remaining_Credits ÷ 10
       + End_Trust × 5
```

---

## 19. Datenbank-Schema Übersicht (SQLite)

### Zentrale Tabellen
```
Spieler:       user, user_resources
Kolonie:       colonies, colony_buildings, colony_resources, colony_researches, colony_ships, colony_personell
Flotten:       fleets, fleet_ships, fleet_personell, fleet_orders
Stammdaten:    buildings, researches, ships, personell, resources
Nachrichten:   innn_messages, innn_events, innn_news
```

### Inventar der Kolonie-Tile System
```
colony_tiles   — Hex-Tile-Daten (Koordinaten, Terraintyp, Ressourcen, Gebäude, Erkundet)
```

---

## 20. Gebäude-Trust & Special Effects

| Gebäude | Trust/Lv | Spezial-Effekt |
|---|---|---|
| Agrardom | 0 | Organisierte Organika-Produktion |
| Cantina | +2/Lv | Verhandlungs-Risiko; +Konsul-Slot |
| Analytik-Labor | 0 | Kenntnis-Forschung; +Analytiker-Slot; Lv4/5: Kenntnis-Levelup-AP −3/−2% (Σ5%) |
| Hangar | 0 | Schiff-Ausbau; +Raumfahrer-Slot |
| Krankenstation | +3/Lv | Seuchenausbruch-Risiko −8% pro Lv (Cap 50%, gemeinsam mit health-Kenntnis) |
| Religiöse Stätte | +2/Lv | Besinnlichkeit (Lv1 only) |
| Kolonialdenkmal | +2/Lv | Stolz (Lv1 only) |
| Security Hub | +1/Lv | Trust-Event-Mitigation −25%; Recycling 10% build-cost |
| Uplink Station | 0 | Deep-Scan −1 AP (Lv2+); Merchant frequency ↑ |
| Trading Post | 0 | Merchant +12% trade value |

---

## 21. Companion-Referenzen

- **GDD** (`docs/GDD.md`) — vollständige Spielmechaniken-Beschreibung
- **Design System** (`docs/design-system/readme.md`) — Farben, Typo, Komponenten
- **Frontend Conventions** (`docs/frontend-conventions.md`) — AJAX, Screens, Breakpoints
- **Character Sheets** (`docs/characters/`) — NPC-Biographien (EN)
- **Testdata** (`data/sql/testdata.sqlite.sql`) — DB-Fixtures für Tests

---

**Hinweis**: Für spekulativ noch nicht im Config verankerte Werte (z.B. Depot-Supply, Schiffs-Decay, etc.) siehe die jeweilige `config/*.php` Datei direkt oder frage in Task-Spec nach Clarification.
