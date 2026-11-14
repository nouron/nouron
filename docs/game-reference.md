# Nouron — Spielreferenz: Zahlen-Lookup (Stand 2026-09-06; Hangar/Funde/Bergungsflug 2026-10-12; alle AP-Werte 2026-11-14 nach AP-Skala ÷3)

> **AP-Refresh 2026-11-14:** Alle AP-Werte in dieser Datei sind auf den Config-Stand von Branch `feat/ap-skala` gebracht (AP-Skala ÷3 mit Rang-Option B, Bau ÷2,5, Rabatte absolut; Specs `docs/superpowers/specs/2026-11-13-ap-skala.md` §11 und `2026-11-13-cantina-gespraechsoptionen-gefuehl.md`). Quelle ausschließlich `config/*.php` und `database/seeders/data/buildings.php`. Alle Werte gerechnet, **nicht gemessen** (Messung M1/M2 offen). Handels-Prozente (Abschnitt 7a, `trade`-Kenntnis) sind bewusst unverändert (P7 folgt).

Vollständige Referenztabellen für Coding-Aufgaben. Wird von ADR 0004 und CLAUDE.md referenziert.
Diese Datei ist ein **Hand-Maintained Snapshot** — keine automatische Generierung. Bei Änderungen in `config/*.php` aktualisieren.

---

## 1. Ressourcen (6 aktiv)

| ID | Key | Name (DE) | Ebene | Handelbar | Startwert |
|---|---|---|---|---|---|
| 1 | `credits` | Credits | User | Nein | 3000 |
| 2 | `supply` | Versorgung | User | Nein | 10 |
| 3 | `regolith` | Regolith | Kolonie | **Ja** | 300 (`game.onboarding.start_regolith`) |
| 4 | `compounds` | Werkstoffe | Kolonie | **Ja** | 0 |
| 5 | `organics` | Organika | Kolonie | **Ja** | 0 |
| 12 | `trust` | Vertrauen | Kolonie | Nein | 50 (Grundwert `game.trust.base`, Skala 0–100, Abschnitt 14) |

---

## 2. Gebäude: Build-Kosten (Initialausbildung)

| Gebäude | CC-Gate | Build-Kosten (Rg / Wk) | Supply-Kosten | Max Level | Max Instanzen |
|---|---|---|---|---|---|
| **Commandcenter (CC)** | — | — | 0 | 5 | 1 |
| **Wohnhabitat** | Lv1 | 40 Rg | 0 | 3 | 6 |
| **Harvester** | Lv1 | — | 2 | 1 | 1 |
| **Agrardom** | 1 + Harv | 70 Rg | 2 | 3 | 1 |
| **Analytik-Labor** | Lv2 | 95 Rg | 6 | 5 | 1 |
| **Krankenstation** | Lv3 | 60 Rg / 25 Wk | 10 | 3 | 1 |
| **Cantina** | Lv2 | 95 Rg | 6 | 3 | 1 |
| **Hangar** | Lv2 | 95 Rg | 6 | 3 | 1 (eine Halle, T30 Schritt 3; Stufe 1–3 flat je 25 Rg) |
| **Religiöse Stätte** | Lv4 | 50 Rg / 15 Wk | 4 | 1 | 1 |
| **Kolonialdenkmal** | Lv5 | 60 Rg / 25 Wk | 2 | 1 | 1 |
| **Leitstelle** | Lv2 | 80 Rg / 25 Wk | 8 | 3 | 1 |
| **Uplink Station** | Lv2 | 80 Rg | 6 | 3 | 1 |
| **Trading Post** | Lv4 | 100 Rg / 25 Wk | 6 | 3 | 1 |

> **Rg** = Regolith, **Wk** = Werkstoffe (compounds)
> **Pfad-Gebäude-Gate** (Analytik-Labor/Hangar/Cantina, Server-Regel seit 2026-10-14, `game.path_building_gate.cc_level_offset` = 1): ein neues Pfad-Gebäude nur solange `platzierte Pfad-Gebäude < CC-Level − 1` — CC Lv2: 1, Lv3: 2, Lv4: alle 3. Gesperrte Einträge bleiben im Baumenü sichtbar (mit Begründung); stehende Pfad-Gebäude bleiben bei CC-Rückbau unberührt.
> **Gebäude-Stufen-Gate** (Server-Regel seit 2026-10-15, `game.building_cc_level_offset` = 0): jede Ausbaustufe eines Nicht-CC-Gebäudes braucht eine Kommandozentrale mindestens gleicher Stufe (Lv2-Gebäude → CC Lv2, Lv3 → CC Lv3 …); Fehlercode `building_cc_gate`. Die CC selbst ist ausgenommen.
> **AP-Deckel pro Gebäude und Sol** (Server-Regel seit 2026-10-16, `game.build.ap_cap_per_sol.base` = 1; `null` schaltet aus): Deckel = `base` + Fach-Deckel des Baumeisters (`game.advisor.cap_bonus_per_rank`, Rang 1/2/3 = +1/+2/+2, also Bau-Deckel 2/3/3; ohne oder nicht verfügbarer Baumeister = nur `base`); gilt für Ausbau-AP inkl. CC, nicht für Reparatur oder Platzieren; Fehlercode `building_ap_cap`. Ausbaustufe (4 AP) liegt über dem höchsten Bau-Deckel (3), also nie in einem Sol fertig.

> **Labor-AP-Deckel pro Kenntnis und Sol** (T43-G1, `game.knowledge_ap_cap_per_sol.base` = 1; `null` schaltet aus): Deckel = `base` + Fach-Deckel des Analytikers (`game.advisor.cap_bonus_per_rank`, +1/+2/+2, also 2/3/3; ohne Analytiker 1); gilt für AP aus dem gemeinsamen Pool via `ResearchService::invest()`, nicht für zweckgebundene Bonus-AP (`investBonus`, z. B. Barkeeper); Fehlercode `knowledge_ap_cap`; Zähler in `colony_daily_ap` (`DailyApCapService`, wird von Training und Gespräch mitgenutzt), Reset mit dem Sol-Wechsel.
> **Stille Forschung** (seit 2026-11-11, Spec `2026-11-11-tomas-rat-rueckmeldung`): zweckgebundene Bonus-AP (`ResearchService::investBonus`: Tomas, Sarka, Deva, Lenn) lassen die Kenntnis beim Erreichen der Schwelle selbst aufsteigen, auch ohne Labor; Überschuss trägt in die nächste Stufe, ist die gesperrt, verfällt der Rest mit Grund. Die Labor-Voraussetzung entfällt dabei, die zweite Gebäude-Voraussetzung, das CC-Gate (`knowledge_cc_level_cap`) und die Höchststufe gelten. Auch Daten-Sweep-AP (`mission_data_sweep`, `research_ap`) folgen dieser Regel; kann die Zielkenntnis bei Rückkehr nichts aufnehmen, verfallen sie mit Protokoll-Eintrag. **Grobe Fortschrittsworte ohne Labor** (`game.knowledge_rough_progress_bands` = 0,34 / 0,67, **Vorschlag**): Anteil der Schwelle unter 0,34 = Wort 1, unter 0,67 = Wort 2, sonst Wort 3; bei 0 kein Wort.
> **Kenntnis-Höchststufe und Gebäude-Voraussetzungen** (seit 2026-11-11): Höchststufe einer Kenntnis = letzter Schlüssel ihrer `levelup_costs` in `config/knowledge.php` (derzeit 5); darüber kein Aufstieg und keine Investition (Fehlercode `max_level`). Labor-Aufstieg, Techtree-Anzeige und stille Forschung prüfen beide Gebäude-Voraussetzungen gleich (z. B. Kartografie: Labor + Hangar); stille Forschung lässt nur das Labor weg.
> Max Level für alle Nicht-CC-Gebäude seit dem Ausbaustufen-System (2026-08-26, `config/buildings.php`). Wohnhabitat: Level 3 **pro Instanz**, 6 Instanzen. Depot/Lagerhalle ist seit 2026-06-22 entfernt.

---

## 3. Gebäude: Levelup & Repair

| Aspekt | Wert |
|---|---|
| **Levelup-Kosten (alle außer CC)** | 25 Rg (flat, `game.build.levelup_regolith_flat`) |
| **Levelup-AP (alle außer CC)** | 4 AP je Ausbaustufe (Seeder `ap_for_levelup`, alle Stufen gleich); Kolonialdenkmal 8 AP |
| **CC-Levelup-AP** | Ziel-Lv2/3/4/5 = 8/16/24/32 AP (`buildings.commandCenter.cc_upgrade_ap_by_level`; Seeder-Fallback 4) |
| **Platzieren (Neubau)** | Errichtungskosten + 25 Rg für Stufe 1, in einer Zahlung beim Platzieren (T9, 2026-09-25); Ausbau 0→1 danach nur AP |
| **CC-Levelup-Kosten** | Ziel-Level × 30 Rg |
| **Reparatur je Klick (Gebäude)** | 1 AP → +3 SP (`game.repair.sp_per_click` = 3), dazu 1 Rg je wiederhergestelltem SP (`regolith_per_sp` = 1); fehlen weniger als 3 SP, nur diese (und deren Rg) — der AP bleibt. CC und Harvester: nur AP. Schiffe: siehe 7b |
| **Regolith-Startbestand** | 300 Rg (`game.onboarding.start_regolith`, T9-Kalibrierung 2026-10-02: Phase-1-Ende Median Sol ~17–18, vorher 370 → Sol ~12–13) |
| **CC-Lv2 Regolith Total** | 60 Rg |

---

## 4. Gebäude: Decay-Raten & Status-Points

Decay-Rate = **Status Points pro Tick** verloren. Wohnraumverlust beschleunigt den Verfall nicht (A14, 2026-09-24). Level-Down endet bei Stufe 1 (`GameTick::applyLevelDown()`), ein platziertes Gebäude verfällt nie auf Stufe 0.

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
| Leitstelle | Standard | 0.60 | 20 | ~33 Sol |
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
| Rang | Active-Ticks Threshold | Pool-AP/Tick (`ap_per_rank`) | Fach-Deckel (`cap_bonus_per_rank`) | Upkeep Cr/Tick | Promotion Cost |
|---|---|---|---|---|---|
| Rang 1 | — | +1 AP | +1 | 10 Cr | — |
| Rang 2 | 15 Ticks | +1 AP | +2 | 25 Cr | 150 Cr |
| Rang 3 | 45 Ticks | +2 AP | +2 | 35 Cr | 250 Cr |

> **Werte sind je Rang, nicht kumulativ zu addieren** (AP-Skala §11.1 Option B: Einstellung Pool +1 und Deckel +1, Rang 2 Deckel +1, Rang 3 Pool +1).
> **Fach-Deckel** (`game.advisor.cap_bonus_per_rank`, `AdvisorService::specialistCapBonus`): Baumeister → Bau-Deckel (`game.build.ap_cap_per_sol`), Analytiker → Labor-Deckel (`game.knowledge_ap_cap_per_sol`), Raumfahrer → Training-Deckel (`path_depth.training_ap_cap_per_sol`); Konsul ohne Deckel. Nicht verfügbarer Berater (Sanktion) = +0. Wirksamer Deckel = `base` (je 1) + Fach-Deckel = 1 / 2 / 3 / 3 (ohne / Rang 1 / 2 / 3).

> **Upkeep**: Cr/Tick deducted after passive income in GameTick (GDD §12)
> **Promotion**: One-time cost when advisor reaches that rank

---

## 6. Kenntnisse (7): Levelup-Kosten & Effekte

Alle levelup via Analytik-Labor. Keine Credits-Kosten (=0). Alle Kurven glockenförmig für Supply-Cap-Bonus.

| Kenntnis | Levelup-AP (Lv1→5) | Σ AP | Trust/Lv | Effekt |
|---|---|---|---|---|
| **construction** | 8/11/14/18/21 | 72 AP | 0 | Gebäude-Levelup-AP: Lv2 −1, Lv4 −1 (Σ −2 AP, `ap_cost_reduction_per_lv`), nur auf Levelups ≥ `game.min_discountable_cost_ap` (6), praktisch nur CC |
| **cartography** | 8/11/14/18/21 | 72 AP | 0 | ab Lv2: Missionen mit Entfernung ≥ 3 kosten −1 AP (`mission_ap_reduction`, Untergrenze `game.action_min_cost_ap` = 1); ab Lv4: erste Erkundung je Sol kostenlos (`free_explore_from_level`) |
| **geology** | 8/11/14/18/21 | 72 AP | 0 | Harvester +3/+3/+2/+2/+2 Rg/Sol; Instabilität −3/−5/−5/−4/−3% |
| **agronomy** | 8/11/14/18/21 | 72 AP | +1 | Agrardom +1/+2/+2/+1/+1 Or/Sol |
| **health** | 8/11/14/18/21 | 72 AP | +2 | Seuchenausbruch-Risiko −3/−5/−5/−4/−3% |
| **trade** | 8/11/14/18/21 | 72 AP | 0 | Bar-Slots +0/+1/+1/+0/+0; Handelsvorteil-Preisbonus (alle 3 Kanäle) +2/+3/+3/+2/+2% (Σ12%); Cantina-Verhandlung-Erfolgschance +1/+2/+2/+2/+1%-Punkte (Σ8%-Punkte, nur mit Konsul). Kein Bau-Rabatt auf AP mehr (A13, 2026-09-20 — vorher Σ15% additiv mit construction, gestrichen: Handel wirkt auf Preise/Angebote/Verhandlung, nicht auf Bauprojekte). |
| **defense** | 8/11/14/18/21 | 72 AP | +1 | Sturm-Risiko −3/−5/−5/−4/−3% |

> **Rabatt-Regel** (`game.php`, AP-Skala §5.1): alle AP-Rabatte absolut und additiv. `min_discountable_cost_ap` = 6 (dauerhafte Rabatte nur auf Kosten ≥ 6 AP), `project_min_cost_ap` = 2 (Untergrenze Projekte/Levelups nach Rabatt), `action_min_cost_ap` = 1 (Untergrenze Handlungen/Missionen). Einmal-Gutscheine (Vesper/Aldra) ignorieren `min_discountable_cost_ap`, respektieren aber die Untergrenze. Labor-Rabatt auf Kenntnisse: Abschnitt 20.
> **Sol je Kenntnis** (gerechnet aus Kosten ÷ Deckel): Lv1–5 bei Deckel 1 / 2 / 3 = Σ 72 / 37 / 25 Sol.

> **CC-Level Gate**: Lv4 & Lv5 knowledge erfordern CC Lv4 bzw. Lv5
> **Supply-Cap Bonus**: alle Kenntnisse +3/+5/+5/+4/+3 = 20 max pro Kenntnis

---

## 6b. Pfad-Vertiefung (T41, vorläufig)

Alle Werte Platzhalter (Owner 2026-10-24), nicht per Bot gemessen. Quelle: `config/path_depth.php`. Stufe pro Lauf, Tabelle `colony_path_levels`.

| Art | AP für Stufe 1/2/3/4/5 | Σ AP | Deckel-Gebäude |
|---|---|---|---|
| Missionsfamilie (Hangar „Training“) | 3/5/6/8/10 | 32 | Hangar |
| Figur (Cantina „Beziehung pflegen“) | 1/3/3/3/3 | 13 | — (Gesprächslängen nach Cantina-Stufe) |

Gerechnete Sol (Kosten ÷ Tagesmaximum, ohne Übertrag): Training Lv1–5 bei Deckel 1 / 2 / 3 = Σ 32 / 17 / 12 Sol. Figur bekannt (Stufe 2) / vertraut (3) / brüderlich (5) mit 1 AP je Sol = 4 / 7 / 13 Sol, mit 2 AP je Sol (Cantina 3) = 3 / 5 / 9 Sol.

- **Gebäude-Deckel** (`path_depth.building_gate`, nur Missionsfamilien): Stufe 1–2 ab Hangarstufe 1, Stufe 3–4 ab 2, Stufe 5 ab 3. Figuren haben seit 2026-11-13 keinen Gebäude-Deckel mehr.
- **Hangar-Ertrag:** Faktor 1 + 0,05 × Familienstufe auf skalierbare Belohnungstypen.
- **Familien:** exploration, salvage, supply, trade, protection (Zuordnung in `config/path_depth.php`).
- **Training-Sol-Limit** (T43-G2, `path_depth.training_ap_cap_per_sol.base` = 1 + Fach-Deckel des Raumfahrers +1/+2/+2, also 1 / 2 / 3 / 3; `null` schaltet aus): je Familie und Sol, Zählerschlüssel `training:<Familie>` in `colony_daily_ap` (`DailyApCapService`); Fehlercode `training_ap_cap`. Im Hangar gibt es keine zweckgebundenen Bonus-AP, daher keine Ausnahme.
- **Freischaltung nach Hangarstufe** (T43-G3, `path_depth.family_unlock_hangar_level`, **Platzhalter**, Designer-Annahme vom Owner bestätigt): exploration und salvage ab Stufe 1, supply und trade ab 2, protection ab 3; Fehlercode `training_locked`. Zusätzlich gilt weiter der Gebäude-Deckel je Familienstufe.
- **Cantina:** Investition, wenn die Figur im Lauf getroffen wurde oder heute in der Cantina sitzt (Tomas immer; seit 2026-11-13, `CantinaPresenceService`: Gast-Angebote auf den Hotspots, Wette/Auktion, Story-Begegnung, Anliegen, Informations-Begegnung). Das erste Gespräch legt die Beziehung an.
- **Tomas' Rat-Rhythmus** (AP-Skala 2026-11-13 §6, ersetzt `ap_bonus_by_relationship_level` und `interaction_cooldown_ticks`): jeder Rat = `game.bartender.ap_bonus` = 1 zweckgebundener Bonus-AP; die Beziehungsstufe bestimmt den Abstand (`advice_every_sols_by_relationship_level`): Stufe 1/2 alle 3 Sol, Stufe 3/4 alle 2 Sol, Stufe 5 jeden Sol, gezählt ab dem letzten Rat (`BarService::bartenderAdviceInSols`). Stufe 0 („fremd“): kein Rat (Fehlercode `relationship_too_low`). Der Rat selbst kostet keine Pool-AP.
- Nur AP aus dem gemeinsamen Pool; Ausführung von Mission/Gespräch kostet unverändert.
- **Cantina-Gespräche, Variante C** (Owner 2026-11-13, `path_depth.conversation_options`): **ein Gespräch je Figur und Sol**, Länge wählbar: kurz (`short`) = 1 AP, lang (`long`) = 2 AP. Voller Preis immer (keine Kürzung); Fortschritt proportional zu den AP; kein Übertrag: Rest über der nächsten Beziehungsstufe verfällt, die API meldet je Länge die verfallenden AP (Warnchip + Hinweiszeile vor dem Klick, Ergebnis- und Protokollzeile danach). Namen je Länge aus einem Pool (`lang/*/pathdepth.php` → `conversation_names`), pro Sol rotierend.
- **Gesprächslängen nach Cantina-Stufe** (`path_depth.conversation_option_cantina_level`): kurz ab Cantina-Stufe 1, lang ab 3. Fehlercode `conversation_option_locked` (mit `requires_cantina_level`); gesperrte Länge zeigt eine Figurenzeile aus `lang/*/pathdepth.php` → `option_locked_lines.<Figur>` (Fallback `default`), pro Sol rotierend.
- **Gesprächs-Deckel** (`path_depth.conversation_ap_cap_per_sol.base` = 2, Owner 2026-11-13 „höchstens 2“; `null` = Deckel aus, die Ein-Gespräch-Regel bleibt): AP je Figur und Sol, Zähler `conversation:<Figur>` in `colony_daily_ap`; Länge über dem Deckel nicht wählbar; Fehlercode `conversation_ap_cap`. Kein Fach-Deckel-Bonus.

---

## 7. Schiffe

| Schiff | Supply-Kosten | Max SP | Verschleiß (`wear_per_sol`) | Hangar-Gate | Trägt Funde (`find_carry`) | Stärkewert |
|---|---|---|---|---|---|---|
| **Drohne** | 0 | 20 | 1,5 SP/Sol | Ja (Hangar Lv1) | klein, mittel | 0 |
| **Frachter** | 0 | 20 | 1,0 SP/Sol | Ja (Hangar Lv2) | klein, mittel, groß | 0 |
| **Korvette** | 0 | 20 | 0,75 SP/Sol | Ja (Hangar Lv3) | keine | 3 |

> **Supply-Kosten**: alle Schiffe 0 — Schiffe verbrauchen kein Supply (Design-Entscheidung 2026-06-08, der Schlüssel `supply_cost` existiert für Schiffe nicht mehr)

> **Hangar-Level = Schiffsklasse und Buchten**: Lv1 = Drohne, Lv2 = Frachter, Lv3 = Korvette; Stufe n öffnet n Buchten (Details 7b). Max SP = `game.ship_repair.max_status_points`.

---

## 7b. Hangar B1 (T30 Schritt 3)

**Eine Halle** (`is_instanced` 0, nicht zweimal platzierbar). Stufe n = Schiffsklasse n **und** n Buchten (Bucht 1–3). Das **Vorfeld** (Bucht 0) existiert ab Stufe 1, gehört nur der Geschenk-Drohne und zählt nicht als Bucht. Ein Schiff ist inaktiv, wenn Hallenstufe < max(Klassenstufe, Bucht). Stufenkosten unverändert flat 25 Rg. `colony_ships.bay` (NULL = `pending`), `unique(colony_id, bay)`.

**Geschenk-Drohne:** Beim ersten Erreichen von Hangar Stufe 1 steht eine Drohne auf dem Vorfeld, ohne Credits und Schuld; einmal pro Lauf (`HangarService::ensureGiftDrone`, CommLog-Eintrag).

| Schiffs-Reparatur (`game.ship_repair`) | Wert |
|---|---|
| Deckel (`max_status_points`) | 20 SP |
| Pro Klick (`sp_per_click` / `ap_per_click`) | +6 SP für 1 AP |
| Kosten | nur AP (kein Rg, keine Credits), je Schiff über `bay` inkl. Vorfeld, keine passive Dock-Reparatur |

**Rückkehr-Garantie** (`HangarBays::flightReadiness`, ersetzt `dispatch_min_sp_pct` = 25 %): Start nur, wenn `SP − wear_per_sol × 2 × sol_distance > 0`. Fehlercode `ship_too_worn`. Beispiel Drohne Ring 3 (Distanz 2): Verschleiß 6 SP, Start ab 7 SP, Rückkehr mit 1 SP. Gilt für alle Missionen.

**Bergungsflug** (`mission_find_salvage`, `FindFlightService`): nur zu gescannten echten Funden, Schiff Drohne oder Frachter, kein Erfolgswurf (`roll` false), Schwierigkeit `normal` (zählt für `task_expedition_coverage`, nicht gegen `finds.max_open_projects`).

| Ring | Sol-Distanz (`sol_distance_by_ring`) | AP (pauschal, `finds.flight.ap_cost`) | Organika (1 je Sol, `finds.flight.organika_per_sol`) | Dauer (hin und zurück) |
|---|---|---|---|---|
| 2 | 1 | 1 | 1 | 2 Sol |
| 3 | 2 | 1 | 2 | 4 Sol |

Der Flug verschleißt das Schiff nicht (keine Rückkehr-Garantie nötig). Ertrag = Fundmenge (`finds.types.*.rg`, kein Faktor): klein 4, mittel 10, groß 18 Rg; die Zeilenstruktur (`base`, `total`) bleibt für spätere Zusätze. Tragkraft aus `ships.*.find_carry` (Tabelle oben). Kein Ertrag, wenn der Fund beim Eintreffen fehlt (`find_missing`) oder das Schiff den Fund nicht tragen kann; bei SP ≤ 0 unterwegs, Abbruch oder Rückruf bleibt der Fund bestehen und wieder Flugziel.

**Katalog-Änderungen:** `mission_find_salvage` neu. `mission_prospecting_flight` und `mission_deep_survey` (Signalvermessung) gestrichen, Zieltyp `signal_tile` und Belohnung `deep_scan` entfallen. `mission_supply_run` zahlt nur noch Organika (kein Regolith).

**`findAction` (`ColonyTileService`)** ist die einzige Quelle, welche Aktion für eine Fundkachel gilt (Fundkarte zeigt genau eine Aktion plus Begründung):

| Zustand | Aktion | Grund (`find_reason_*`) |
|---|---|---|
| nicht gescannt | scannen | `unscanned` |
| Fehlalarm | keine | `false` |
| Flug unterwegs | keine | `in_flight` |
| kein Hangar (Stufe 0) | Selbstbergung | `self` |
| Hangar, tragendes Schiff bereit | Flug | `flight` |
| Schiff unterwegs/in Lieferung | warten | `ship_busy` |
| Schiff gedockt, aber Rückkehr-Garantie verfehlt | warten (Reparatur-AP) | `ship_worn` |
| kein tragendes Schiff, nicht groß | warten | `no_ship` |
| tragendes Schiff nur in geschlossener Bucht | gesperrt | `ship_inactive` |
| Hallenstufe < `finds.flight.min_hangar_level` (klein 1, mittel 1, groß 2) | gesperrt | `needs_level` |
| großer Fund, Halle ≥ 2, kein Frachter | gesperrt | `needs_freighter` |

**Strenge Variante:** Mit Hangar Stufe ≥ 1 gibt es für Funde nur noch den Flug (die Bedingung wird aus der aktuellen Hallenstufe abgeleitet, nie gespeichert). Große Funde sind mit Stufe 1 gesperrt, bis Stufe 2 und ein Frachter da sind; auf einer Zonen-Kachel kostet das Bauplatz (K17).

**Kennzahlen** (`OpeningComparison`, informativ): K12 = Anteil Pool-Rg über Schiff (`regolith_sources.find_flight` / Pool gesamt); K16 = Wartesole je Grund (`needs_level` inkl. `ship_inactive`, `needs_freighter`, `ship_busy`, `no_ship_ready` = `ship_worn` + `no_ship`; `no_ship_ready` > 3 Sole = Bot-Bug); K17 = Sole, in denen ein gesperrter Fund (`needs_level`/`needs_freighter`) eine Zonen-Kachel belegt. Dazu Reparatur-AP Sol 4–12 und bis Sol 25 (Information neben K4).

---

## 7a. Handel & Konsul (A13, `TradeAdvantageService`)

**Handelsvorteil-Kanäle** (`TradeAdvantageService::CHANNEL_*`) — additive Quellen, nie multipliziert:

| Kanal | Quellen | Wirkung |
|---|---|---|
| `bar` (Cantina) | Konsul-Rang + Handelsposten (Stufe ≥1) + `trade`-Kenntnis | mehr Ware auf der Get-Seite |
| `merchant` (Corvans Sonderinventar) | Handelsposten (Stufe ≥2) + `trade`-Kenntnis (kein Konsul) | Preisnachlass |
| `nexus` (Direktimport-Preis) | Handelsposten (Stufe ≥3) + `trade`-Kenntnis (kein Konsul, F5) | Preisnachlass |

Stiller Deckel (nie im UI erklärt, `game.bar.trade_terms.silent_cap`): `bar` 60%, `merchant` 60%, `nexus` 25%.

**Konsul-Rang-Rabatt im `bar`-Kanal** (`game.bar.trader_discount`): Rang 0 → 0%, Rang 1 → 10%, Rang 2 → 20%, Rang 3 → 30%.

**Cantina-Verhandlung** (`BarService::negotiateOffer/negotiateChance`, `game.bar`):
- `ap_cost_negotiate` = 1 (gleich `ap_cost_accept` = 1; Cantina-Begegnung annehmen `bar.encounter.ap_cost_accept` = 1)
- `negotiate_success_chance` je Konsul-Rang: 0 → 0%, 1 → 60%, 2 → 65%, 3 → 70%; zzgl. `knowledge.trade.negotiate_chance_bonus_per_lv` (Σ8%-Punkte bei Lv5), gedeckelt bei `negotiate_chance_max` = 95%
- `negotiate_bonus` (additiver Aufschlag auf die Get-Seite bei Erfolg): konstant 20% ab Rang 1
- Verkaufslose mit Credits-Erlös (Corvans Organika-Lose) sind Festpreis: kein Handelsvorteil, keine Verhandlung (`trade_terms.fixed_price_offers`)

**Rangskalierte Schiffsverhandlung im Hangar** (`HangarService::consulApDiscountPerAp`, `game.hangar.consul_ap_discount`): Credits-Nachlass pro investiertem Verhandlungs-AP je Konsul-Rang — Rang 1 → 150 Cr/AP, Rang 2 → 180 Cr/AP, Rang 3 → 210 Cr/AP (×3 mit der AP-Skala, Credits-Nachlass je Verhandlung gleich). Risikofrei (kein Fehlschlag), 0 ohne verfügbaren Konsul.

**Marktbericht** (`MerchantService::getForecast`, `game.merchant`): Vorlauf für Corvans nächsten Besuch je Konsul-Rang (`forecast_sols`) — Rang 0 → keine Ankündigung, Rang 1 → 1 Sol, Rang 2 → 2 Sole, Rang 3 → 3 Sole. Ab `forecast_inventory_min_rank` = 3 zusätzlich die Sonderinventar-Kategorien des kommenden Besuchs (nicht die Alltagsgeschäft-Lose).

**Regolith→Credits-Verkauf (T10, 2026-09-28 Owner-Entscheidung):** die anonyme Gästerotation (nicht Corvan) generiert pro Gast-Slot mit `game.bar.regolith_sell_offer_chance_pct` = 12% statt eines normalen Tausch-Angebots ein seltenes Regolith→Credits-Verkaufsangebot (`BarService::buildRegolithSellOffer()`). Preis: `base_prices[Regolith]` (25) ± `price_variance` (±20%), analog `buildCorvanBuyOffer()`s Preisformel. Corvans strukturierte Sell-Lots (`game.merchant.commodity`) bleiben unverändert auf Organika beschränkt — dieser Kanal ist bewusst separat und läuft ausschließlich über die generische Gästerotation.

---

## 8. Missionen: Belohnungen

Credit-Belohnungen am 2026-09-30 verdoppelt (Owner, `task_credit_reserve`: Credits sollen über Missionen und Ereignisse entstehen).

### Drohne
| Mission | Sol-Distanz | Organika-Kosten | **Belohnung** |
|---|---|---|---|
| `mission_courier_run` | 1 | 3 Or | 180 Cr |
| `mission_recon_flight` | 1 | 3 Or | 2 Tiles reveal |
| `mission_data_sweep` (Cart Lv1+) | 3 | 9 Or | 3 Research AP |
| `mission_long_range_expedition` (Cart Lv3+) | 5 | 15 Or | 700–1100 Cr / 8–12 Wk / 30–45 Rg (1 pick) |

### Drohne / Frachter
| Mission | Sol-Distanz | Organika-Kosten | **Belohnung** |
|---|---|---|---|
| `mission_find_salvage` (gescannter Fund) | 1 (Ring 2) / 2 (Ring 3) | 1 / 2 Or (`finds.flight.organika_per_sol`) + 1 AP pauschal (`finds.flight.ap_cost`) | Fundmenge ohne Faktor (klein 4, mittel 10, groß 18; Frachter für groß), kein Wurf — siehe 7b |

### Frachter
| Mission | Sol-Distanz | Organika-Kosten | **Belohnung** |
|---|---|---|---|
| `mission_supply_run` | 1 | 3 Or | 10 Or (nur Organika, T30: kein Regolith mehr) |
| `mission_trade_convoy` (Trade Lv1+) | 3 | 9 Or | 520 Cr + Trade Success (+1 Vertrauen) |
| `mission_aid_transport` | 2 | 6 Or + 10 Or extra | 180 Cr + Encounter Won (+1 Vertrauen) |

### Frachter / Korvette
| Mission | Sol-Distanz | Organika-Kosten | **Belohnung** |
|---|---|---|---|
| `mission_salvage_sweep` (Constr Lv1+) | 4 | 12 Or | 6–10 Wk |
| `mission_ruin_expedition` | 4 | 12 Or | 440 Cr (1x pro Ruin) |

### Korvette
| Mission | Sol-Distanz | Organika-Kosten | **Belohnung** |
|---|---|---|---|
| `mission_escort_convoy` | 3 | 9 Or | 560 Cr |
| `mission_perimeter_patrol` (Def Lv1+) | 3 | 9 Or | 440 Cr + Encounter Won (+1 Vertrauen) |

> **Dispatch-AP** (`missions.ap_by_distance`): Entfernung 1/2/3/4/5 = 1/1/2/3/3 AP (jenseits der Tabelle der letzte Wert); `cartography` ab Lv2 −1 AP ab Entfernung 3 (Untergrenze 1), siehe Abschnitt 6.
> **Organika-Kosten** (Provisions): base = sol_distance × 3 Or, mit Knowledge-Scaling −1 pro Level (Floor 1)
> **Dispatch Anforderung**: Rückkehr-Garantie (`SP − Verschleiß × 2 × Distanz > 0`, Fehlercode `ship_too_worn`; ersetzt die 25-%-Sperre), siehe 7b

---

## Hangar-Missionen: Schwierigkeit

Werte aus `config/game.php` → `missions.difficulty` (Platzhalter-Größenordnung, Nachjustierung nach Playtest, siehe GDD §8b).

| Stufe | Basis-Erfolgschance | Reward-Multiplikator |
|---|---|---|
| easy | 85% | ×0.7 |
| normal | 70% | ×1.0 |
| hard | 60% | ×1.4 |

| Globaler Wert | Wert |
|---|---|
| Pilot-Rang-Bonus (pro Raumfahrer-Rang) | +5% |
| Kenntnis-Bonus (pro Level über Gate) | +3% |
| Chance-Cap | 95% |
| Hard-Fail-Extra-Wear (nur Stufe "hard") | 1.0 SP |

### Mission → Schwierigkeitsstufen

Aus `config/missions.php` → `catalog[*].difficulties`. Jede Mission bietet genau zwei der drei Stufen zur Wahl.

| Key | Name | Schiff | Verfügbare Stufen |
|---|---|---|---|
| `mission_courier_run` | Botenflug | Drohne | easy / normal |
| `mission_recon_flight` | Erkundungsflug | Drohne | easy / normal |
| `mission_data_sweep` | Datensammelflug | Drohne | normal / hard |
| `mission_long_range_expedition` | Fernexpedition | Drohne | normal / hard |
| `mission_find_salvage` | Bergungsflug | Drohne o. Frachter | normal (kein Wurf) |
| `mission_supply_run` | Versorgungsfahrt | Frachter | easy / normal |
| `mission_trade_convoy` | Handelsfahrt | Frachter | normal / hard |
| `mission_aid_transport` | Hilfsgütertransport | Frachter | easy / normal |
| `mission_salvage_sweep` | Trümmerbergung | Frachter o. Korvette | normal / hard |
| `mission_ruin_expedition` | Ruinen-Expedition | Frachter o. Korvette | normal / hard |
| `mission_escort_convoy` | Konvoi-Begleitung | Korvette | normal / hard |
| `mission_perimeter_patrol` | Umkreis-Patrouille | Korvette | normal / hard |

---

## 9. Action Points (AP)

### Base & Advisor-Bonus
| Quelle | AP/Tick |
|---|---|
| **Base (Colony)** | 4 AP (1 shared pool, `game.ap.base`) |
| **Advisor Rang 1** | +1 AP |
| **Advisor Rang 2** | +1 AP |
| **Advisor Rang 3** | +2 AP |
| **Vertrauens-AP** | −2 / −1 / 0 / +1 / +2 je Stufe (Abschnitt 14) |
| **Seuche** | −1 AP solange aktiv (`game.encounter.plague.ap_reduction`; 0 bei Devas Drill-Puffer) |

Pool = `max(0, base + Σ Berater + Vertrauens-AP + Seuchen-AP)` (`AdvisorService::getApBreakdown`, alle Summanden einzeln ausgewiesen). Sol 0 (Baumeister R1): 5; Phase-1-Ende (2 × R1): 6; **Maximum** 4 + 4 × 2 + 2 (Euphorisch) = 14 AP/Tick theoretisch.

### Deckel je Ziel und Sol
| Ziel | `base` | + Fach-Deckel | wirksam ohne / R1 / R2 / R3 |
|---|---|---|---|
| Gebäude-Instanz (`game.build.ap_cap_per_sol`) | 1 | Baumeister | 1 / 2 / 3 / 3 |
| Kenntnis (`game.knowledge_ap_cap_per_sol`) | 1 | Analytiker | 1 / 2 / 3 / 3 |
| Missionsfamilie-Training (`path_depth.training_ap_cap_per_sol`) | 1 | Raumfahrer | 1 / 2 / 3 / 3 |
| Figur-Gespräch (`path_depth.conversation_ap_cap_per_sol`) | 2 | — | 2 (ein Gespräch je Sol) |

### AP-Kosten
| Aktion | Kosten (AP, gemeinsamer Pool) | Config |
|---|---|---|
| Ausbaustufe (alle außer CC, Denkmal) | 4 | Seeder `ap_for_levelup` |
| Kolonialdenkmal | 8 | Seeder `ap_for_levelup` |
| CC Ziel-Lv2/3/4/5 | 8/16/24/32 (`construction` −1 ab Lv2, −2 ab Lv4) | `buildings.commandCenter.cc_upgrade_ap_by_level` |
| Kenntnis-Levelup Lv1–5 | 8/11/14/18/21 (Labor −1 ab Lv3, −2 ab Lv5) | `knowledge.*.levelup_costs` |
| Training Lv1–5 | 3/5/6/8/10 | `path_depth.levelup_ap.mission_family` |
| Beziehungsstufe 1–5 | 1/3/3/3/3 | `path_depth.levelup_ap.character` |
| Gespräch kurz / lang | 1 / 2 (lang ab Cantina 3) | `path_depth.conversation_options` |
| Feld erkunden Ring 1/2/3 | 1/1/2 (Default 1); ab `cartography` Lv4 erste Erkundung je Sol 0; Lenn-Gutschein 0 | `game.colony.explore_cost_per_ring` |
| Tiefenscan | 1; ab Uplink-Station Lv2 erster Scan je Sol 0 | `game.finds.scan_ap`, `free_scan_uplink_level` |
| Selbstbergung klein/mittel/groß | 1/2/2 | `game.finds.types.*.ap` |
| Bergungsflug | 1 | `game.finds.flight.ap_cost` |
| Mission (Entfernung 1–5) | 1/1/2/3/3 | `missions.ap_by_distance` |
| Harvester-Umzug | 1 je Hex | `game.harvester.relocate_ap_per_hex` |
| Reparatur Gebäude / Schiff | 1 je Klick → 3 / 6 SP | `game.repair.sp_per_click`, `game.ship_repair` |
| Handel annehmen / verhandeln | 1 / 1 | `game.bar.ap_cost_accept`, `ap_cost_negotiate` |
| Cantina-Begegnung annehmen | 1 | `game.bar.encounter.ap_cost_accept` |
| Anliegen | 1; Dax (Schmuggler) und namenlose Gestalt 2 | `game.bar.concern.ap_cost` |
| Tomas' Rat | 0 (gibt 1 Bonus-AP) | `game.bartender` |

### Bonus-AP (zweckgebunden, außerhalb der Deckel)
| Quelle | AP | Config |
|---|---|---|
| Tomas' Rat | 1 je Rat (Rhythmus siehe 6b) | `game.bartender.ap_bonus` |
| Sarka (Anliegen Mechanikerin) | 2 | `game.bar.concern.mechanic.ap_bonus` |
| Deva / Lenn (Kenntnis-Schub) | 5 | `game.bar.information_pool.*.knowledge_boost_ap` |
| Daten-Sweep | 3 | `missions.catalog.mission_data_sweep.reward.research_ap` |
| Händler AP-Paket flexibel / Kenntnis | 7 / 5 (800 / 500 Cr) | `game.merchant.items.*.ap_amount` |

### Rabatte und Gutscheine (absolut)
| Quelle | Wirkung | Config |
|---|---|---|
| Vesper (Anliegen) | −2 AP auf die nächste abgeschlossene Ausbaustufe, ohne Ablauf | `game.bar.concern.information_broker.discount_ap` |
| Aldra (Anliegen) | −1 AP, verfällt nach 10 Sol | `game.bar.concern.founder` |
| Untergrenzen | Projekte ≥ 2, Handlungen ≥ 1; dauerhafte Rabatte nur ab 6 AP Grundkosten | `game.project_min_cost_ap`, `action_min_cost_ap`, `min_discountable_cost_ap` |

> Gutschein-Wert liegt in der DB-Spalte `colony_building_discount_vouchers.discount_pct` (trägt seit der AP-Skala AP, Umbenennung offen); der Seuchen-Abzug in `glx_colonies.plague_ap_reduction_pct` (ebenso AP).

---

## 10. Supply-Cap-Formel

Stand Code (`GameTick::calculateSupply()`, Anzeige gespiegelt in `ResourcesService::getSupplyBreakdown()`):

```
Cap = min(
  CC-Beitrag (flach, sobald CC-Stufe ≥ 1)
  + Σ(Stufen aller Wohnhabitat-Instanzen) × Wohnhabitat-Wert
  , game.supply.cap_max
)
CC-Stufe 0 → Cap = 0
```

### Supply-Cap-Quellen

| Quelle | Rechnung | Config-Key | Wert |
|---|---|---|---|
| Kommandozentrale | flach, unabhängig von der Stufe (nur Stufe ≥ 1) | `buildings.commandCenter.supply_cap` | 10 |
| Wohnhabitat | Stufe × Wert, über alle Instanzen summiert (max. 6 × Stufe 3 × 8 = 144) | `buildings.housingComplex.supply_cap` | 8 je Stufe |
| Hard-Cap | Deckel über alles | `game.supply.cap_max` | 200 |

> **Achtung Drift:** `game.supply.cap_commandcenter` (10) und `game.supply.cap_housingcomplex` (8) werden vom Code nicht gelesen — maßgeblich sind die `buildings.*.supply_cap`-Keys. Die Kommentare an beiden Stellen sprechen beim CC von „pro Level", der Code rechnet flach (offene Owner-Frage, siehe GDD §6 „Supply-Cap-Quellen").

**Beispiel Phase 1 (CC Lv1, 1× Wohnhabitat Lv1, keine Kenntnisse)**:
- CC: 10 (flach)
- Wohnhabitat: 1 × 8 = 8
- Total: 18 Supply-Cap

### Belegung (Arbeitsplätze)

`ResourcesService::buildingWorkplaces()`:

```
Arbeitsplätze = Σ(Stufe × supply_cost)                             für Gebäude mit Stufe ≥ 1
              + Σ(supply_cost)                                     für platzierte Gebäude auf Stufe 0 (Reserve, tile_x gesetzt)
freies Supply = Cap − Arbeitsplätze − Σ(Forschungsstufe × supply_cost)
```

Bau-Gate: Platzieren und Ausbau n → n+1 (n ≥ 1) erfordern `freies Supply ≥ supply_cost`. Ausbau 0 → 1 wird nie geprüft (Reserve). Ausgenommen: CC, Wohnhabitat, erster Harvester (ungeprüft, reserviert aber). Bypass: `game.bypass.supply_checks`.

---

## 10a. Wohnraumverlust / Unterbesetzung (A14, Option A2)

Kein eigener Config-Block mehr (`game.overcap` und die Ereignisse `colonists_left` / `colonists_dismissed` entfielen mit T43-G9); Logik in `ResourcesService::colonistsOverview()` und `staffingShare()`. Vertrauens-Konfiguration (Profile, Ereignis-Deckel): Abschnitt 13/14.

```
Arbeitsplätze  = siehe Abschnitt 10 (inkl. Reserve)
Kolonisten     = gespeicherter Cap (Kommandozentrale + Wohnhabitate), gilt sofort bei Abschluss und bei Wohnraumverlust
beschäftigt    = min(Kolonisten, Arbeitsplätze)
frei           = Kolonisten − beschäftigt
unbesetzt      = max(0, Arbeitsplätze − Kolonisten)
Personalanteil = beschäftigt / Arbeitsplätze   (1,0 solange nichts unbesetzt ist; wirkt nur auf Regolith-/Organika-Produktion)
food_need      = floor(beschäftigt / game.food.supply_per_eater)   (T43-G7: supply_per_eater = 1 → food_need = beschäftigt)
```

Kein gespeicherter Zustand (die Spalten `glx_colonies.overcap_streak` / `overcap_departed` sind entfernt), keine Frist, keine Abwanderung, kein „Wegschicken". Wohnraumverlust (Verfall, Sturm, Rückbau) senkt die Kolonisten im selben Moment; das Bau-Gate (freies Supply, Abschnitt 10) bleibt zu, bis wieder genug freie Kolonisten da sind. Tick-Reihenfolge: Decay → Produktion → Verpflegung → Begegnungen → Supply-Cap → Vertrauen.

---

## 11. Credits: Passive Income

Alle Werte Cr/Tick, angewendet nach Ressourcen-Generierung in GameTick Schritt 8b.

| Quelle | Bedingung | Wert |
|---|---|---|
| **Nexus-Subsidy** | CC > 0 | 50 Cr/Tick |
| **Relay-Bonus** | Uplink-Station je Level | 45 Cr/Tick × Uplink-Lv |

Konsul-Handelsvertrag (bedingungsloses Dauereinkommen) entfernt (2026-09-09, F3/A22) — Pfad-Paritätsverletzung, kein Ersatz geplant.

---

## 12. Credits: Kosten

| Aktion | Betrag |
|---|---|
| Advisor Hire (Rang 1) | je Typ: 300–500 Cr |
| Advisor Rank 1 → 2 Promotion | 150 Cr (einmalig) |
| Advisor Rank 2 → 3 Promotion | 250 Cr (einmalig) |
| Advisor Upkeep (Rang 1–3) | 10/25/35 Cr/Tick (laufend) |

---

## 13. Vertrauens-Ereignisse (`game.trust.events`)

Stand T59 (2026-11-14, Skala 0–100, Werte gegenüber der alten Skala halbiert). Beträge wirken nach ihrem Profil (`game.trust.event_profile`, Standard `instant` = genau ein Sol).

| Ereignis (Key) | Betrag | Auslöser |
|---|---|---|
| `building_level_up` | +1 | jede abgeschlossene Ausbaustufe |
| `research_level_up` | +1 | jede abgeschlossene Kenntnisstufe |
| `trade_success` | +1 | Handel erfolgreich (auch Belohnung `mission_trade_convoy`) |
| `treaty_signed` | +2 | reserviert (Cantina-Vereinbarungen), feuert noch nirgends |
| `well_fed` | +1 | in diesem Sol Organika-Vorrat ≥ Bedarf |
| `encounter_won` | +1 | Begegnung abgewehrt / Schutz- oder Hilfsmission erfolgreich |
| `encounter_lost` | −2 | Begegnung beschädigt (33–65 % SP) |
| `colony_threatened` | −3 | Begegnung kritisch (< 33 % SP) |
| `nexus_credit` | −3 | Schiff auf Nexus-Kredit (`game.hangar.nexus_credit_trust_penalty` ebenfalls −3) |
| `stipend_small` / `_medium` / `_large` | +1 / +2 / +3 | Kolonisten-Zulage für 100 / 300 / 600 Cr (`game.stipend.tiers`) |
| `story_concern_resolved` | +2 | Cantina-Anliegen (Sorel/Prediger) gelöst |
| `story_concern_failed` | −1 | Cantina-Anliegen gescheitert |
| `merchant_trust_boost` | +8, klingt ab | Händler-Item „Vertrauensschub“ (600 Cr, `game.merchant.items.trust_boost`), Profil `merchant_boost` |

Gebäude-Rückbau und Handel-blockiert lösen kein Vertrauens-Ereignis aus (keine Keys in Config).

**Stapel-Regel:** gleicher Key im selben Sol zählt nur einmal (stärkster gewinnt); derselbe Key in verschiedenen Solen addiert sich, solange die Nachwirkungen überlappen.

| Config-Key | Wert | Bedeutung |
|---|---|---|
| `game.trust.event_profiles` | `instant` [100]; `fade5` [100, 80, 60, 40, 20]; `merchant_boost` [100, 75, 63, 38, 25] | Prozent des Ereignisbetrags je Sol seit Auslösung (T40); `merchant_boost` ergibt aus +8 die Folge 8, 6, 5, 3, 2 |
| `game.trust.event_profile` | `merchant_trust_boost` → `merchant_boost`; alle anderen `instant` | `fade5` ist vorbereitet, aber keinem Ereignis zugewiesen (Messschritt offen) |
| `game.trust.event_cap` | aus (`null`) | Deckel auf den Gesamt-Ereignisanteil (±), Höhe nach Messung |
| `buildings.securityHub.trust_event_mitigation` | 1 | Leitstelle (Stufe ≥ 1): jedes negative Ereignis 1 Punkt näher an 0 (−3 → −2, −1 → 0), vor dem Profil; Hunger ausgenommen |

---

## 14. Vertrauen: Skala, Quellen, Stufen (T59)

```
Vertrauen = clamp(base + Σ Gebäude + Σ Kenntnisse + clamp(Σ Schiffe, ±ships_cap) + Ereignisse + Hunger, min, max)
          = clamp(50 + … , 0, 100)
```

Kein angesparter Bestand: jeden Sol neu berechnet (`TrustService::breakdown()`), Ergebnis in `colony_resources` (resource_id 12). Start = Grundwert 50. Keine Produktionswirkung mehr (Faktor-Tabelle entfällt, T59 Variante P0); Vertrauen wirkt nur über die AP der Stufe.

### Stufen (`game.trust.bands`, untere Grenze inklusive)
| Stufe (Key) | Spanne | AP auf den Pool |
|---|---|---|
| Aufruhr (`riot`) | 0–14 | −2 |
| Unruhig (`restless`) | 15–39 | −1 |
| Stabil (`stable`) | 40–59 | 0 |
| Zufrieden (`content`) | 60–79 | +1 |
| Euphorisch (`euphoric`) | 80–100 | +2 |

### Statische Quellen (je Stufe bzw. Einheit)
| Quelle | Config | Betrag | Maximum |
|---|---|---|---|
| Krankenstation (max 3) | `buildings.infirmary.trust_per_lv` | +3 | +9 |
| Cantina (max 3) | `buildings.bar.trust_per_lv` | +2 | +6 |
| Religiöse Stätte (max 1) | `buildings.temple.trust_per_lv` | +2 | +2 |
| Kolonialdenkmal (max 1) | `buildings.monument.trust_per_lv` | +2 | +2 |
| Leitstelle (max 3) | `buildings.securityHub.trust_per_lv` | +1 | +3 |
| Agronomie (max 5) | `knowledge.agronomy.trust_per_lv` | +1 | +5 |
| Gesundheit (max 5) | `knowledge.health.trust_per_lv` | +2 | +10 |
| Verteidigung (max 5) | `knowledge.defense.trust_per_lv` | +1 | +5 |
| Frachter (je Einheit) | `ships.freighter.trust_per_unit` | +1 | Schiffsdeckel `game.trust.ships_cap` = ±15 |

Berater, übrige Gebäude, Kenntnisse und Schiffe: 0.

### Hunger (`game.food`)
Strafe = −min(`hunger_base_malus` + (Serie − 1) × `hunger_step`, `hunger_cap`) = −1, −2, −3, −4 (Deckel). Endet mit dem ersten satten Sol.

### Schwellen
| Schwelle | Wert | Config |
|---|---|---|
| Niederlage | Vertrauen < 5 | `game.run.trust_fail_threshold` |
| Nexus-Warnung (einmalig) | < 10 | `game.run.trust_warning.nexus_warning` |
| Onboarding-Hinweis | < 40 (Eintritt Unruhig), frühestens ab Tick 5 | `game.onboarding.hint_trust_threshold`, `hint_trust_min_ticks` |
| Seuchen-Zweig Vertrauen | < 15 (Aufruhr) | `game.encounter.plague.trust_below` |
| Kolonieblüte | Stufe ≥ Zufrieden, 12 Sole in Folge | `game.run.tasks.task_colony_prosperity` (`min_band` `content`) |
| Punktwertung | Vertrauen am Ende × 5 | `game.run.score_weights.trust_multiplier` |

Die Kopfzeilen-Farbe folgt der Stufe (`trust_chip_red` entfällt).

### Anzeige
Chip und Popup zeigen immer Stufenwort + AP-Wirkung und die Stufenleiste. Zahlen (Summe und Beträge der Quellen) nur mit Analytik-Labor Stufe ≥ 1 (`TrustService::numbersVisible()`); ohne Labor nur die Namen der gerade wirkenden Quellen (Grundwert immer).

> Erreichbarkeit (gerechnet, Spec §2.5, ungemessen): größte negative Summe aus bestehenden Quellen etwa −11, Aufruhr und Niederlage praktisch unerreichbar. Eskalierender Hunger als eigener, gemessener Schritt (ROADMAP T59a).

---

## 15. Food (Organika) System

| Aspekt | Wert |
|---|---|
| **Verbrauch pro Tick** | beschäftigte Kolonisten × 1 Organika (`game.food.supply_per_eater = 1`, T43-G7 2026-10-31; anwesend = Arbeitsplätze inkl. Reserve − abgewandert, siehe Abschnitt 10a) |
| **Start-Organika** | 10 (`OnboardingService`, T43-G7 2026-10-31) |
| **Agrardom-Kurve** | 16 / 20 / 30 Organika je Sol (Marginalwerte Lv1/2/3; kumulativ 16/36/66; `game.production_curve[41][5]`, T43-G7 2026-10-31) |
| **"Gut versorgt" Bonus** | +1 Vertrauen (Ereignis `well_fed`, wenn Bestand ≥ Bedarf) |
| **Hunger-Strafe Base** | −1 Vertrauen auf erstem hungrigen Sol (`hunger_base_malus`) |
| **Hunger-Strafe Step** | +1 pro weiterem Sol in Serie (`hunger_step`) |
| **Hunger-Strafe Cap** | −4 Vertrauen (`hunger_cap`) |

---

## 16. Begegnungen (Encounters) — Trigger-Chancen

Alle Chancen Richtwerte (erste Kalibrierung), zu tunen nach PlaytestBot.

### Phase-1 Ramp
Trigger-Chancen ramp linear von 0 bis volle Stärke über erste 15 Sol.

| Begegnung | Typ | Base-Chance | Per-Building | Cap |
|---|---|---|---|---|
| **Sturm** (Weather) | 1.2% | — (entfernt, s. u.) | 2% |
| **Geologische Instabilität** | 0.15% × Sol seit Harv-Umzug | — | 5% |
| **Seuchenausbruch** (Plague) | 5% (nur wenn Hungerserie ≥ 3 oder Vertrauen < 15) | — | — |

> **Sturm-Re-Kalibrierung 2026-09-03:** Seit dem Wechsel auf koloniweiten Wirkbereich (GDD §9) trifft ein ausgelöster Sturm ALLE Colony-Zone-Gebäude gleichzeitig statt eines einzelnen — die Gesamtschwere skaliert dadurch bereits von selbst mit der Gebäudeanzahl. Ein zusätzlicher `chance_per_building`-Term hätte dasselbe Kolonie-Größen-Signal doppelt eingepreist (Trigger-Häufigkeit UND Schadenssumme). `chance_per_building` wurde daher entfernt und `base_chance` gesenkt, damit die erwartete Schadenssumme/Sol für eine typische Mid-/Lategame-Kolonie (5-12 Gebäude) grob auf dem alten, gedeckelten Einzelziel-Niveau bleibt statt mit der Gebäudeanzahl zu explodieren. Platzhalter-Größenordnung, Nachjustierung nach Playtest — siehe `config/game.php` (`game.encounter.storm.*`) für den Rechenweg-Kommentar.

> **Cooldown**: 3 Sol nach jeder resolved Begegnung, keine neue WARNING

### Begegnungs-Ergebnisse (SP-Prozent)
| Tier | SP-Range | Event | Vertrauen |
|---|---|---|---|
| Abgewehrt | ≥66% | Erfolgreich | +1 (encounter_won) |
| Beschädigt | 33–65% | Getroffen | −2 (encounter_lost; mit Leitstelle −1) |
| Kritisch | <33% | Kritisch | −3 (colony_threatened; mit Leitstelle −2) |

---

## 17. Harvester-System

### Depletion (GDD §4c)
```
Ertrag = Frischwert, solange Restvorkommen > 0, sonst 0 (konstante Rate, harter Cutoff — A24/A25)
```

**A44/H1 (2026-09-27):** zwei entkoppelte Achsen — Ertragstier (`y1`-`y3`) und Mächtigkeitstier (`d1`-`d3`), kombiniert zu 8 `tile_type`-Werten. `y3_d3` (bestes Tier auf beiden Achsen) existiert bewusst nicht.

**T30 (2026-10-09):** Fresh Yield 16/23/30 → 18/26/34 (Pfad-Parität, Spec §11), Resource Max unverändert.

**Fresh Yields** (Regolith pro Sol) und **Resource Max** (Gesamtvorkommen). **T10 (2026-09-28):** Resource Max ×1,5 (160/300/440 → 240/450/660) — behebt das Baustoff-/Kartenbudget-Problem, siehe `docs/superpowers/plans/2026-09-27-t10-regolith-credits-rekalibrierung-spec.md`:
| Tile-Typ | Fresh Yield | Resource Max |
|---|---|---|
| regolith_y1_d1 | 18 Rg | 240 Rg |
| regolith_y1_d2 | 18 Rg | 450 Rg |
| regolith_y1_d3 | 18 Rg | 660 Rg |
| regolith_y2_d1 | 26 Rg | 240 Rg |
| regolith_y2_d2 | 26 Rg | 450 Rg |
| regolith_y2_d3 | 26 Rg | 660 Rg |
| regolith_y3_d1 | 34 Rg | 240 Rg |
| regolith_y3_d2 | 34 Rg | 450 Rg |

**H2 — Sol-1-Gegensatzpaar:** zwei der 9 Ring-3-Frontier-Koordinaten werden immer pre-explored und mit einem Kontrast-Paar belegt (eine Kombi strikt höheres Ertragstier UND strikt niedrigeres Mächtigkeitstier als die andere) — welches konkrete Paar variiert pro Run/Seed (`ColonyTileService::pickH2Pair()`).

**Relocation**: 1 AP pro Hex (`game.harvester.relocate_ap_per_hex`)

**Instanzen**: genau ein Harvester (`max_instances` 1); es gibt keine zweite Instanz.

---

## 17a. Fundpool (T30 Pool v1, `game.finds`)

Fester Start-Pool versteckter Signale auf den Außenring-Feldern (Ring 2/3, nur `terrain_empty`), pfadneutral: für alle Eröffnungen gleich, kein Nachschub in v1. Platzierung `FindPoolService` (eigener Seed-Strom, der größte Fund bevorzugt Ring 3).

| Fundtyp | Anzahl im Pool | Regolith | Selbstbergung (AP, sofort) |
|---|---|---|---|
| `find_false` (Fehlalarm) | 2 | 0 | 0 |
| `find_small` | 3 | 4 | 1 |
| `find_medium` | 1 | 10 | 2 |
| `find_large` | 1 | 18 | 2 |

Pool gesamt: 7 Signale, 40 Rg; der Ertrag ist bei Selbstbergung und Flug gleich (kein Faktor).

| Parameter | Wert | Config |
|---|---|---|
| Tiefenscan eines Signals | 1 AP; ab Uplink-Station Lv2 ist der erste Scan je Sol kostenlos | `finds.scan_ap`, `finds.free_scan_uplink_level` = 2 |
| Selbstbergung | eine Sofort-Aktion zu `finds.types.*.ap` (1/2/2 AP), kein Sol-Deckel, kein Projektlimit | `finds.types` |

| Bergungsflug (`finds.flight`, T30 Schritt 3) | Wert |
|---|---|
| Ertrag | Fundmenge (`types.*.rg`), kein Faktor |
| AP je Flug `ap_cost` | 1 (unabhängig von Größe und Ring) |
| Proviant `organika_per_sol` | 1 Organika je Sol Entfernung (Ring 2: 1, Ring 3: 2) |
| Verschleiß | keiner (Bergungsflüge verschleißen das Schiff nicht; Rückkehr-Garantie entfällt) |
| Mindest-Hallenstufe `min_hangar_level` | klein 1, mittel 1, groß 2 (muss zu `ships.*.find_carry` passen) |

Ablösungsregel (Details und Grundtabelle in 7b): ohne Hangar Selbstbergung für alle Größen, mit Hangar nur Bergungsflug (strenge Ablösung, keine Koexistenz). Ablauf Selbstbergung: Signal (Fehlalarm nicht erkennbar) → Tiefenscan (enthüllt den Fund, Fehlalarm wird bebaubar) → Bergung über `ColonyTileService::salvageFind` (Route `colony.tile.salvage`, feste AP je Fundgröße, Regolith sofort, Kachel frei). Signal-Kacheln sind bis zum Scan nicht bebaubar (`tile_has_find`); ein echter Fund bleibt unbebaubar, bis er vollständig geborgen ist, ein gescannter Fehlalarm wird bebaubar. Bot: Regeln `deep_scan_signal_tile`, `invest_find` (nur ohne Hangar) und `dispatch_find_flight`. Messung: `RunReport` `regolith_sources.find` und `regolith_sources.find_flight`, `game:playtest-compare` K11/K12/K16/K17 (informativ).

---

## 18. Run-Struktur

*Stand: 2026-09-26 (A45 im Code umgesetzt, Werte vorläufig bis zum Abschluss des Kalibrier-Batches).*

| Aspekt | Wert |
|---|---|
| **Tick Limit** | 100 Ticks (default) |
| **Tick Length** | 24 hours (1 Sol = 1 Tick) |
| **Phase 1 Deadline** | Sol 20 (hard fail, `game.run.phase1_deadline_sol`, Owner 2026-10-15) |
| **Phase 1 Warning** | Sol 17 (Nexus escalates, `game.run.phase1_warning_sol`) |
| **Phase-1-Ende** | Grundaufbau (`RunProgressService::checkPhase1Completion`, Owner 2026-10-15): CC Lv2; Harvester, Wohnhabitat, Agrardom, Uplink-Station und ein Pfadgebäude je mindestens Lv1; zwei Berater. Zielfenster Sol 12–15. CC Lv3 eröffnet Phase 2. |
| **CC-Ausbau AP** | je Ziel-Level 8/16/24/32 für Lv2–Lv5 (`buildings.commandCenter.cc_upgrade_ap_by_level`, AP-Skala 2026-11-13), zusätzlich zum Regolith (30 × Ziel-Level). Gerechnet bei Baumeister R1 (Deckel 2) 4/8/12/16 Sol, ab R2 (Deckel 3) 3/6/8/11 Sol. |
| **Trust Fail Threshold** | Vertrauen < 5 (instant fail, `game.run.trust_fail_threshold`; Nexus-Warnung < 10) |
| **Nexus Debt Cap** | 12000 Cr (instant fail) |
| **Countdown** | `countdown_sols_before_limit` 20 → Gesamt-Sol 80 |
| **Sieg-Korridor** (Owner) | Sol 70–85 nur sehr guter Run + Glück; realistisch Sol 85–95 |

### Objectives (GDD §15)
Der Pool umfasst 8 Aufgabentypen, pro Run werden 3 zufällig gezogen (höchstens 1 aus der Kategorie `economy`). Sieg bei 2 von 3 erfüllten Aufgaben.

**Kalibrierregel (Owner 2026-09-26):** Ohne gezieltes Spiel frühestens Sol ~90 oder gar nicht; gezielt Sol 70–85. Kein Mindest-Sol.

#### Ist-Stand (Code, `config/game.php → run.tasks`, Stand 2026-10-01)

**Kalibriert (A45/A45b/R1, Stand 2026-10-01).** Alle Parameter liegen in `run.tasks` und werden über `RunTaskCatalog` gelesen; alle Werte sind Owner-Entscheidungen auf Basis der Bot-Batches (Kalibrierregel: nebenbei frühestens ~Sol 90, gezielt Sol 70–85). Baseline-Batch 2026-09-30 (4 Profile × 8 Seeds) siehe ROADMAP R1.

| Task | `category` | `type` | Messung | Parameter | Status |
|---|---|---|---|---|---|
| `task_senior_advisors` | personal | counter | Berater der Kolonie mit Rang ≥ `min_rank` | **`min_rank` 3, `target` 4** | Owner-Entscheidung |
| `task_credit_reserve` | economy | streak | Credits ≥ `threshold` | **`threshold` 6000, `target` 20 Sole** | Owner-Entscheidung 2026-10-01 (vorher 4000/10: mit Belohnungen ×2 und Sparen schon an Sol 44–45 erfüllt) |
| `task_colony_prosperity` | diplomacy | streak | Vertrauensstufe ≥ `min_band` | **`min_band` content (Zufrieden, ≥ 60), `target` 12 Sole** | T59 Owner 2026-11-14 (vorher Vertrauen > 40 auf alter Skala, 8/8 @82–95); neu ungemessen |
| `task_research_lead` | research | counter | Kenntnisse auf Lv ≥ `min_level` | **`min_level` 5, `target` 7** | Owner 2026-10-01 bestätigt (gezielt ~Sol 68, nebenbei max. 6) |
| `task_self_sufficiency` | survival | streak | Regolith > `regolith_min` **und** Organika > `organics_min` | **`regolith_min` 150, `organics_min` 300, `target` 35 Sole** | Owner 2026-10-01 (vorher 45 Sole, erst um Sol 96 erreicht) |
| `task_expedition_coverage` | exploration | counter | erfolgreiche Außenmissionen mit Schwierigkeit ≥ `min_difficulty` (Reihenfolge: `missions.difficulty.order`) | **`min_difficulty` normal, `target` 10** | Owner 2026-10-01 bestätigt (gezielt @82–87) |
| `task_engineering_output` | research | counter | Summe der Ausbaustufen (`level`) aller `colony_buildings`, jede Instanz einzeln | **`target` 35** | Owner-Entscheidung 2026-09-30 (gezielt: 30 schon an Sol 48, 35 um Sol 70) |
| `task_trade_volume` | economy | counter | gekaufte Händler-Items im Run | **`target` 8** | Owner 2026-10-01 (vorher 7, nebenbei schon @79–86) |

Vergleiche: `>` bzw. `≥` wie in der Spalte „Messung" (entspricht `RunProgressService`). Bei `streak`-Tasks speichert `run_objectives.best_streak_value` die beste Serie im Run. Sie zählt für die Nexus-Kontrollpunkte als Fortschritt, erfüllt wird der Task aber erst mit einer Serie der Länge `target`.

Herleitung der Vorschläge für die neuen Messungen (Annahme, grob):
- `task_engineering_output` 35: Zum Phase-1-Ende liegt die Summe bei ungefähr 10–15. Nebenbei gespielt bleibt sie bei 22–26 (Baseline 2026-09-30). Gezielter Ausbau erreicht 30 um Sol 48, danach flacht die Kurve ab (Level-Deckel, Supply): 35 um Sol 70.
- `task_expedition_coverage` 10: Ab Hangar und erstem Schiff (frühe Phase 2) bleiben rund 50 Sole. Kurze Missionen dauern 2–4 Sole hin und zurück, die Grundchance bei „normal" liegt bei 70 %. Eine dauerhaft beschäftigte Drohne kommt damit auf etwa 10 Erfolge bis Sol ~75–80. Wer nur gelegentlich oder auf „leicht" schickt, bleibt deutlich darunter. Eine zweite Drohne beschleunigt stark. Das ist beim Bot-Batch zu prüfen.
- `task_senior_advisors`: Rang 3 braucht 45 Rangpunkte (`advisor.rank_thresholds`). Der vierte Slot kommt erst mit CC Lv4. Ein spät eingestellter oder ausgetauschter Berater verhindert das Ziel praktisch.

#### Nexus-Kontrollpunkte (Phase-2-Sol, nicht Gesamt-Sol)

Ist-Stand (Code, `config/game.php → run.nexus_checkpoints`, Stand 2026-09-26; **vorläufig, Kalibrier-Batch läuft**). Steigende Leiter (Owner 2026-09-26), Prüfung auf **Fortschritt statt Erfüllung**. `requirements` listet je Anforderung einen Mindest-Fortschritt in Prozent (`≥`, ganzzahlig, keine Rundung; 100 = erfüllt). Jede Anforderung muss durch eine **andere** Aufgabe erfüllt werden. Bei Serien-Aufgaben zählt die **beste bisherige Serie** (`best_streak_value`). Geprüft wird einmal, genau am jeweiligen Phase-2-Sol. `run.nexus_milestones` ist entfernt.

| Phase-2-Sol | `requirements` | Bedeutung | Folge bei Verfehlen (`event`) |
|---|---|---|---|
| 30 | `[50]` | ein Ziel ≥ 50 % | Warnung (`run.nexus_warning_sol30`) |
| 50 | `[75, 50]` | ein Ziel ≥ 75 %, ein zweites ≥ 50 % | 2. Warnung (`run.nexus_warning_sol50`) |
| 65 | `[100, 75]` | ein Ziel erfüllt, ein zweites ≥ 75 % | Sanktion (`run.nexus_sanction_sol65`): 1 zufälliger Berater `advisor_lock_sols` = 1 Sol gesperrt |

Weitere Nexus-Prüfungen außerhalb der Leiter:

| Auslöser | Prüfung | Folge |
|---|---|---|
| ab Phase-2-Sol 55 (jeder Sol) | `nexus_debt` > `nexus_debt_fail_threshold` | Fail (`nexus_debt`) |
| Gesamt-Sol ≥ `tick_limit` − `countdown_sols_before_limit` (= 100 − 20 = Sol 80) | unabhängig vom Phase-2-Sol, einmal pro Run | Countdown-Meldung |

Bei einem Phase-1-Ende um Sol 15–20 liegt Phase-2-Sol 65 bei Gesamt-Sol 80–85.
### Nexus-Schulden

`nexus_debt` startet mit dem Vorschuss (`OnboardingService`, 3000) und wächst durch Nexus-Kredit-Schiffskäufe und Upkeep-Defizite. **Eine Tilgung gibt es nicht:** Die Schuld sinkt im Run nie. Eine Tilgungsmechanik ist ein künftiger Schritt (GDD §15).

### Score Formula
```
Score = (Tasks_Done × 1000)
       + (Tick_Limit - Done_At_Tick) × 10
       + Remaining_Credits ÷ 10
       + End_Trust × 5
```

---

## 19. Datenbank-Schema Übersicht (MySQL)

### Zentrale Tabellen
```
Spieler:       user, user_resources
Kolonie:       colonies, colony_buildings, colony_resources, colony_researches, colony_ships, advisors
Flotten:       fleets, fleet_ships, fleet_personell, fleet_orders
Stammdaten:    buildings, researches, ships, personell, resources
Nachrichten:   innn_messages, innn_events, innn_news
```

### Inventar der Kolonie-Tile System
```
colony_path_levels — Stufen je Missionsfamilie/Figur pro Lauf (T41)
colony_tiles   — Hex-Tile-Daten (Koordinaten, Terraintyp, Ressourcen, Gebäude, Erkundet)
```

---

## 20. Gebäude-Vertrauen & Special Effects

| Gebäude | Vertrauen/Lv | Spezial-Effekt |
|---|---|---|
| Agrardom | 0 | Organisierte Organika-Produktion |
| Cantina | +2/Lv | Verhandlungs-Risiko; +Konsul-Slot |
| Analytik-Labor | 0 | Kenntnis-Forschung; +Analytiker-Slot; Kenntnis-Levelup-AP: ab Lv3 −1, ab Lv5 −2 (absolut, kumulativ, `knowledge_ap_cost_reduction_per_lv` [3 => 1, 5 => 1], Untergrenze 2) |
| Hangar | 0 | Schiff-Ausbau; +Raumfahrer-Slot |
| Krankenstation | +3/Lv | Seuchenausbruch-Risiko −8% pro Lv (Cap 50%, gemeinsam mit health-Kenntnis) |
| Religiöse Stätte | +2/Lv | Besinnlichkeit (Lv1 only) |
| Kolonialdenkmal | +2/Lv | Stolz (Lv1 only) |
| Leitstelle | +1/Lv | negative Vertrauens-Ereignisse 1 Punkt näher an 0 (`trust_event_mitigation`, absolut); Recycling 10% build-cost ab Ausbaustufe 3 (Bergungsdienst, `recycle_min_level`) |
| Uplink Station | 0 | ab Lv2 erster Tiefenscan je Sol kostenlos; Merchant frequency ↑ (nicht implementiert) |
| Trading Post | 0 | Handelsvorteil-Kanalrabatt 12% je Ausbaustufe: Lv1 Bar-Kanal, Lv2 + Merchant-Kanal, Lv3 + Nexus-Kanal (kumulativ, additiv mit Konsul-Rang/`trade`-Kenntnis im Handelsvorteil, A13) |

---

## 21. Companion-Referenzen

- **GDD** (`docs/GDD.md`) — vollständige Spielmechaniken-Beschreibung
- **Design System** (`docs/design-system/readme.md`) — Farben, Typo, Komponenten
- **Frontend Conventions** (`docs/frontend-conventions.md`) — AJAX, Screens, Breakpoints
- **Character Sheets** (`docs/characters/`) — NPC-Biographien (EN)
- **Testdata** (`data/sql/testdata.sql`) — DB-Fixtures für Tests

---

**Hinweis**: Für spekulativ noch nicht im Config verankerte Werte (z.B. Depot-Supply, Schiffs-Decay, etc.) siehe die jeweilige `config/*.php` Datei direkt oder frage in Task-Spec nach Clarification.
