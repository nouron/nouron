# T30 — Drei gleichwertige Eröffnungen (Labor-First / Hangar-First / Cantina-First)

**Stand:** 2026-10-05 · **Autor:** game-designer (Analyse + Vorschlag, keine Code-/Config-Änderung) · **ROADMAP:** T30

**Owner-Entscheidung (verbindlich):** Labor-First, Hangar-First und Cantina-First sind gleichwertige Eröffnungen. Keine davon soll der schlechtere Weg sein.
**Owner-Idee, noch nicht entschieden:** Geschenk-Drohne beim Hangar-Abschluss, eventuell ohne Hangar-Slot.
**Owner-Vorgabe 2026-10-05:** Der PlaytestBot bekommt je Eröffnung ein eigenes Profil (Abschnitt 5).

Alle anderen Deutungen in diesem Dokument sind als **Annahme** oder **Empfehlung** gekennzeichnet.

---


## Owner-Entscheidungen (2026-10-05, nach Lesen dieser Spec — verbindlich, ersetzen entgegenstehende Empfehlungen unten)

1. **Geschenk-Drohne ohne Slot:** Die Drohne wird mit dem ersten fertigen Hangar geschenkt und **belegt keinen Hangar-Slot** (nicht „Slot + Ausmustern" wie empfohlen). Damit entfallen die Aktion „Schiff ausmustern" und der Hint „Drohne bestellen". Zu klären in der Umsetzung: wo die slotfreie Drohne „wohnt" (Datenmodell), Verhalten bei Verlust/Beschädigung, Auswirkung auf Frachter/Korvette (zweites Schiff früher → messen).
2. **Prospektionsflug früher:** keine Geologie-Voraussetzung mehr; **Geologie erhöht den Ertrag zusätzlich** (statt ihn freizuschalten). Ertragsanzeige weiter aus der Config (T29).
3. **Neue Idee (zusätzlich, noch ohne Spec):** Beim Aufdecken der Karte sollen kleine, einsammelbare **Regolith-Quellen** auftauchen — das erhöht das verfügbare Regolith für alle drei Pfade gleich. Muss separat konzipiert werden (Menge/Häufigkeit, Einsammeln per AP?, Zusammenspiel mit Zonen-Tiles und Harvester, Karten-Regolith-Budget laut Generator-Invarianten).
4. **Pilot:** T28 bleibt (Raumfahrer-Hint erst mit angekommenem Schiff); der Hint soll später zusätzlich den AP-Vorteil nennen.
5. **K1–K7** gelten als Abnahmemaßstab für T30; im Bot eine **feste Reihenfolge für das zweite Pfadgebäude**.
6. Die „Kleinen Fixes" aus den Nebenbefunden sind separat umgesetzt (PR „Onboarding-Hints").
7. Umsetzungsreihenfolge sonst wie empfohlen: Bot-Eröffnungsprofile + Baseline-Messung zuerst, dann Hints/Transparenz, Prospektionsflug, Geschenk-Drohne, danach zweite Messung.


## 0. Kernbefund in drei Sätzen

1. **Phase-1-Tempo ist in allen drei Eröffnungen fast gleich.** Die Regolith-Kette ist identisch: alle drei Pfadgebäude kosten 95 + 25 Rg, und jede Kolonie braucht zwei Pfadgebäude, CC Lv3 und zwei Lv2-Ausbauten. Deshalb liegt das Phase-1-Ende rechnerisch bei allen innerhalb von etwa 1 Sol.
2. **Ungleich ist, was der Spieler im Regolith-Warteblock (etwa Sol 6–11) mit seinen AP anfangen kann.** Labor-First hat einen unbegrenzten, Regolith-freien AP-Abnehmer mit Dauerwirkung (Kenntnisse). Hangar-First und Cantina-First haben nur kleine bzw. zufällige Abnehmer und weichen auf das endliche Tile-Aufdecken aus.
3. **Hangar-First zahlt seinen frühen Ertrag in der falschen Währung aus.** Er bringt Credits und Karteninfos, Phase 1 ist aber nur durch Regolith begrenzt. Dazu kommt eine verdeckte Falle: Die naheliegende erste Drohne blockiert den einzigen Slot, den der Frachter (der Regolith-Weg des Pfads) bräuchte.

---

## 1. Ist-Vergleich Sol 1–10

### 1.1 Grundwerte (aus Code/Config geprüft)

| Größe | Wert | Quelle |
|---|---|---|
| Start-Regolith | 300 | `game.onboarding.start_regolith` |
| Start-Credits / Nexus-Vorschuss | 3000 / 3000 | `OnboardingService::seedResources()`, `seedSol1State()` |
| Startgebäude | CC, Harvester, Wohnhabitat je Lv1, 16/20 SP; Harvester auf leerem Feld (1,0), **fördert 0** bis zur Verlegung | `OnboardingService` |
| Harvester-Ertrag | 16 / 23 / 30 Rg/Sol (Ertragsstufe y1/y2/y3) | `game.harvester.fresh_yield` |
| Verlegung | 2 AP je Hex | `game.harvester.relocate_ap_per_hex` |
| AP | 12 Basis + 2 je Berater Rang 1, **sofort ab Einstellung** (gemeinsamer Pool) | `game.ap.base`, `advisor.ap_per_rank`, `AdvisorService::getApBreakdown()` |
| Bau-AP je Stufe | 10 für alle Gebäude (Rabatt nur über `construction`) | `database/seeders/data/buildings.php` `ap_for_levelup` |
| Platzieren | 1 AP + Errichtung + 25 Rg (Stufe 0→1 vorausbezahlt) | `ColonyController::placeBuilding()`, T9 |
| Agrardom | 70 + 25 Rg | `buildings.bioFacility.build_cost` |
| Pfadgebäude (Labor/Hangar/Cantina) | je 95 + 25 Rg, Supply 6 | `buildings.*.build_cost` |
| Lv-Up (Nicht-CC) | 25 Rg + 10 AP | `game.build.levelup_regolith_flat` |
| CC-Ausbau | Zielstufe × 30 Rg (Lv2 = 60, Lv3 = 90) | `cc_upgrade_regolith_per_level` |
| Berater | Baumeister 300, Analytiker 400, Raumfahrer 500, Konsul 350 Cr; Unterhalt 10 Cr/Sol | `config/advisors.php`, `advisor.upkeep` |
| Credits-Zufluss | +50 Cr/Sol Nexus-Zuschuss | `credits.nexus_subsidy` |
| Phase-1-Ende | CC Lv3 + 2 Nicht-CC-Gebäude ≥ Lv2 (beliebige, auch Pfadgebäude) + 3 Berater | `RunProgressService::checkPhase1Completion()` |
| Kenntnis-Gates | construction/agronomy: Labor Lv1; **geology: Labor Lv2** + Harvester; cartography: Labor Lv1 + Hangar Lv1; Kosten Lv1 = 20 AP | `seeders/data/researches.php`, `config/knowledge.php` |
| Schiffe | Drohne 300 Cr (Hangar Lv1), **Frachter 500 Cr (Hangar Lv2)**, Korvette 800 Cr (Lv3) | `config/ships.php`, `HangarService::SHIP_ID_TO_REQUIRED_HANGAR_LEVEL` |
| Lieferung | Kauf in Sol N → `docked` im Tick-Lauf von Sol N → **nutzbar ab Sol N+1** (Drohne). Nexus-Kredit setzt `deliver_at_tick` = aktueller Tick, liefert also **ebenfalls erst ab Sol N+1**. | `HangarService::requestShip()`, `SolController::next()`, `GameTick::processHangarDeliveries()` |
| Missionsdauer | 2 × `sol_distance`; Botenflug/Erkundungsflug: 2 Sole, 2 AP, 3 Or | `config/missions.php` |
| Botenflug | 180 Cr (leicht ×0,7 bei 85 %, normal ×1,0 bei 70 %) | `missions.catalog`, `game.missions.difficulty` |
| Prospektionsflug | 20–30 Rg, 4 Sole, 4 AP, 6 Or, **Gate geology Lv1** (also Labor Lv2) | `missions.catalog.mission_prospecting_flight` |
| Versorgungsfahrt (Frachter) | 25 Rg + 10 Or, 2 Sole | `missions.catalog.mission_supply_run` |
| Erkunden | Ring 1/2/3 = 1/2/3 AP je Feld | `game.colony.explore_cost_per_ring` |

**Korrekturen am Faktenstand des Auftrags:** (a) Die Prospektionsflug-Hürde ist praktisch **Labor Lv2 + 20 AP Forschung**, nicht nur „Geologie 1“. (b) Der Frachter braucht **Hangar Lv2**. In `docs/game-reference.md` §7 steht fälschlich Lv1. (c) Der Raumfahrer bringt auch ohne Schiff sofort +2 AP in den gemeinsamen Pool. Er ist also nicht nutzlos, nur sein Missionsbonus ist es.

### 1.2 Annahmen für die Zeittafeln

- **A1:** Harvester-Tile mit Ertragsstufe y2 (23 Rg/Sol) als Referenz. Die Empfindlichkeit für y1/y3 steht in 1.4.
- **A2:** Verlegung auf Sol 1 über 3 Hexe = 6 AP (Ring-3-Ziel von (1,0) aus: 2–4 Hexe).
- **A3:** Ein Gebäude steigt auf, sobald die AP vollständig investiert sind; das Regolith wird beim Aufstieg abgebucht.
- **A4:** Optimales menschliches Spiel ohne Fehlplatzierung. Reparaturen werden ab Sol 5 mit etwa 3 AP + 2 Rg pro Sol angesetzt (Σ `decay_rate` ≈ 3; CC und Harvester zahlen kein Rg).
- **A5:** Gästeangebote erscheinen bei Cantina Lv1 mit etwa 0,5 pro Sol (`guest_count` [0,1], gleichverteilt angenommen).

### 1.3 Gemeinsame Rampe Sol 1–3 (alle Eröffnungen identisch)

| Sol | Aktionen | AP (verfügbar → genutzt) | Rg nach Aktionen → nach Tick | Credits Ende Sol |
|---|---|---|---|---|
| 1 | Baumeister (−300 Cr), Harvester verlegen (6), Agrardom platzieren (1, −95 Rg), 7 AP in Agrardom | 14 → 14 | 205 → 228 | 2740 |
| 2 | Agrardom fertig (3), CC Lv2 (10, −60), Pfadgebäude platzieren (1, −120) | 14 → 14 | 48 → 71 | 2780 |
| 3 | Pfadgebäude Lv1 (10), Pfad-Berater einstellen (+2 AP) | 16 → 10, **6 frei** | 71 → 94 | ≈ 2400 (abhängig vom Berater) |

Danach ist die restliche Phase-1-Kette für alle gleich: zwei Lv2-Ausbauten (50), CC Lv3 (90), zweites Pfadgebäude (120) = **260 Rg**. Von 71 Rg aus fehlen 189 Rg, also etwa **8 Sole Ertrag**.

### 1.4 Regolith-Verlauf (alle Eröffnungen, ohne Pfad-Hebel)

| Sol | Regolith-Aktion | Rg nach Aktion | Wartesol? |
|---|---|---|---|
| 4 | Lv2 #1 (25) | 69 | nein |
| 5 | Lv2 #2 (25), meist Wohnhabitat (Supply für Pfadgebäude 2) | 67 | nein |
| 6 | — (CC Lv3 braucht 90) | 90 | **ja** |
| 7 | CC Lv3 (90) | 23 | nein |
| 8–11 | — (Pfadgebäude 2 braucht 120) | 46 → 115 | **ja, 4 Sole** |
| 12 | Pfadgebäude 2 + Lv1 + 3. Berater → Phase-1-Ende | ≈ 0 | — |

Mit Reparatur-Abzug ergibt sich etwa Sol 13. Mit y1 (16 Rg/Sol) etwa Sol 16, mit y3 (30 Rg/Sol) etwa Sol 11. Der Bot-Median liegt bei Sol 17–18 (Messung 2026-10-02). Das passt zu etwas weniger effizientem Spiel.
**Konsequenz:** In jeder Eröffnung gibt es **etwa 5 Wartesole**, in denen kein Regolith-Bau möglich ist. Unterschiedlich ist nur, ob die AP dort einen Abnehmer finden.

### 1.5 AP-Budget im Warteblock Sol 4–12

- Zufluss: 9 Sole × 16 AP = **144 AP**.
- Pflichtbau: 2 × Lv2 (20) + CC Lv3 (10) + Pfadgebäude 2 (11) = 41 AP. Dazu Reparatur ≈ 25 AP.
- Daraus folgen **etwa 78 frei verfügbare AP**. In den Wartesolen sind das rund 13 AP pro Sol.
- Tile-Aufdecken ist ein gemeinsamer, aber **endlicher** Abnehmer: Ring 2 hat etwa 12 Felder × 2 AP, Ring 3 etwa 16 verdeckte Felder × 3 AP, zusammen ≈ 70 AP einmalig (**Annahme**: Kartenzuschnitt Ring 0–3). Der Nutzen in Phase 1 ist reine Information.

### 1.6 Zeittafeln je Eröffnung (ab Sol 3)

**A — Labor-First**

| Sol | Pfad-Aktion | AP-Pfad | Rg/Cr-Wirkung | Neue Optionen | Ertrag |
|---|---|---|---|---|---|
| 3 | Labor Lv1, Analytiker (400) | 6 AP in Kenntnis | −400 Cr | construction, agronomy | — |
| 4 | Labor Lv2 als Lv2 #1 | 10 Bau + 6 Forschung | −25 Rg | **geology** | construction Lv1 (+3 Supply, +2 Vertrauen) |
| 5–6 | geology Lv1 | 20 AP | — | Prospektion (für später) | **+3 Rg/Sol ab Sol 6** |
| 7–12 | agronomy, construction Lv2 … | alle freien AP | +1 Or/Sol, −2 % Bau-AP | — | jede Stufe +Supply/+Vertrauen |

Leerlauf ≈ 0. Der erste Ertrag kommt nach 1–2 Solen. Bis Sol 12 bringt geology etwa +18 Rg, also rund 1 Sol früher in Phase 2. **Alle Erträge zahlen auf Phase-1-Engpässe ein.**

**B — Hangar-First (heutiger Normalablauf, wie im Owner-Handtest)**

| Sol | Pfad-Aktion | AP-Pfad | Rg/Cr-Wirkung | Neue Optionen | Ertrag |
|---|---|---|---|---|---|
| 3 | Hangar Lv1, Drohne bestellen (300); Raumfahrer-Hint erst nach Ankunft (T28) | 6 AP → Erkunden | −300 Cr | — | — |
| 4 | Drohne `docked`, Raumfahrer (500), Erkundungs- oder Botenflug | 2 AP | −500 Cr | Missionen ohne Gate | — |
| 5 | — (Drohne unterwegs) | Erkunden | — | — | — |
| 6 | Drohne zurück, nächster Flug | 2 AP | +126–180 Cr oder 2 Felder | — | **erster Ertrag: Credits** |
| 7–12 | ein Flug alle 2 Sole; Prospektion gesperrt (Labor fehlt); Frachter braucht Hangar Lv2 **und einen freien Slot**, den die Drohne belegt | ≈ 1 AP/Sol | ≈ +55–63 Cr/Sol (Erwartungswert) | — | Credits sind in Phase 1 nicht knapp |

Leerlauf: Nach dem Aufdecken (etwa ab Sol 8–9) bleiben **8–12 AP pro Wartesol ohne Ziel**. Der erste Ertrag kommt nach 3 Solen und auch dann nur in Credits.
**Verdeckte Optimallinie (Annahme, nicht gemessen):** Keine Drohne kaufen, Hangar Lv2 als Lv2 #1 bauen (zählt fürs Phase-1-Ziel, kostet also kein Extra-Rg), Frachter in Sol 4 bestellen, ab Sol 6 Versorgungsfahrten fliegen. Das bringt etwa +7–9 Rg/Sol ab Sol 8 und rund 1,5 Sole früher Phase 2. Kein Spieler findet diese Linie: Kein Hint führt dorthin, und die Drohne ist der naheliegende Erstkauf.

**C — Cantina-First**

| Sol | Pfad-Aktion | AP-Pfad | Rg/Cr-Wirkung | Neue Optionen | Ertrag |
|---|---|---|---|---|---|
| 3 | Cantina Lv1, Konsul (350) | 6 AP → Erkunden | −350 Cr, +3 Vertrauen passiv | Angebote ab nächstem Tick | — |
| 4–12 | Gastangebote (≈ 0,5/Sol, 2 AP), Begegnung 20 %/Sol (2 AP; Wette kostet 15 Rg!), Anliegen 10 %/Sol, Deva/Lenn 12 %/Sol | ≈ 2–3 AP/Sol | Credits/Tauschwaren; Regolith nur selten (Fen-Anliegen, Tausch) | Tomas | erster Ertrag Sol 4–5, zufällig |

Leerlauf: ähnlich wie Hangar-First, etwa 6–10 AP pro Wartesol nach dem Aufdecken. Allerdings „passiert öfter etwas“.
**Annahme (zu prüfen):** Tomas' Bonus-AP und die Kenntnis-Boni von Deva und Lenn fließen über `ResearchService::investBonus()` in Kenntnisse. Alle Kenntnisse verlangen das Labor (Gebäude 31). Ohne Labor wären diese Cantina-Hebel in der Eröffnung also wirkungslos.

### 1.7 Stand nach Sol 3 / 5 / 10 (Referenz y2, Schätzwerte)

| Kennzahl | Labor-First | Hangar-First (Drohne) | Hangar-First (Frachter-Linie) | Cantina-First |
|---|---|---|---|---|
| Rg nach Sol 3 / 5 | 94 / 67 | 94 / 67 | 94 / 67 | 94 / 67 (−15 bei Wette) |
| Rg zu Beginn von Sol 10 | ≈ 104 (+geology) | ≈ 92 | ≈ 110 (+1 Fahrt) | ≈ 92 |
| Credits zu Beginn von Sol 10 | ≈ 2.400 | ≈ 2.100 (+Botenflüge) | ≈ 1.900 | ≈ 2.450 (+Ereignisse) |
| Leerlauf-AP Σ Sol 4–10 (nach Aufdecken) | ≈ 0 | ≈ 25–35 | ≈ 15–25 | ≈ 15–30 |
| Erste pfadspezifische Aktion nach Fertigstellung | 0 Sol | 1 Sol (Lieferung) | 1 Sol (Ausbau) | 1 Sol (zufällig) |
| Erster Ertrag nach Fertigstellung | 1–2 Sole | 3 Sole | 5 Sole | 1–3 Sole (zufällig) |
| Ertragswährung bindend für Phase 1? | ja (Rg, Supply) | nein (Cr, Info) | ja (Rg) | meist nein |
| Phase-1-Ende (Schätzung) | Sol ≈ 12 | Sol ≈ 13 | Sol ≈ 11–12 | Sol ≈ 13 |

---

## 2. Asymmetrien und Ursachen

| # | Ursache | Labor | Hangar | Cantina |
|---|---|---|---|---|
| U1 | **Regolith-freier AP-Abnehmer mit Dauerwert** im Warteblock | unbegrenzt | 1 AP/Sol (1 Schiff) | ≈ 2 AP/Sol, zufällig |
| U2 | **Ertragswährung** passt zum Phase-1-Engpass (Regolith) | ja (geology) | nein; Prospektion hängt am Labor | kaum |
| U3 | **Latenz bis zum ersten Ertrag** | 1–2 Sole | 3 Sole (Lieferung + Hin- und Rückflug) | 1–3 Sole |
| U4 | **Vorleistung in Credits** vor dem ersten Nutzen | 400 | 300 Schiff + 500 Pilot | 350 |
| U5 | **Falle**: Erstkauf blockiert späteren Kern-Nutzen | — | Drohne belegt den einzigen Slot und verhindert den Frachter | (Wette kostet Regolith) |
| U6 | **Hint-Führung** | Hint „AP können nirgendwo landen“ deutet auf das Labor als AP-Ziel | kein Hint „Schiff bestellen“; Pfad-Hint nennt den Raumfahrer vor dem Schiff | Hint „spend AP economy“ zeigt auf die Cantina, auch wenn kein Gast da ist |

**Warum sich Hangar-First leer anfühlt:** Alle sechs Ursachen treffen ihn zugleich. Bei Cantina-First wirken U1–U3 ebenfalls, nur abgeschwächt und durch Zufallsereignisse überdeckt. Ein Cantina-First-Handtest steht noch aus. **Annahme:** Er zeigt denselben Leerlauf in milderer Form.
**Labor-First ist kein Maßstab, der abgesenkt werden soll.** Seine bekannte Schwäche liegt später (Credits-Pfad-Lücke, ROADMAP „Offene Pfad-Paritäts-Fragen“). Für Gleichwertigkeit werden B und C angehoben.

### 2.1 Vorgeschlagene Gleichwertigkeits-Kriterien (Empfehlung)

Gemessen wird gepaart über identische Seeds; Referenz ist Labor-First.

| ID | Kriterium | Ziel |
|---|---|---|
| K1 | Sole von Pfadgebäude Lv1 bis zur ersten pfadspezifischen Aktion | ≤ 1 in allen Eröffnungen |
| K2 | Sole bis zum ersten Ertrag in einer **Phase-1-relevanten** Größe (Rg, Supply, AP, Vertrauen) | ≤ 3 |
| K3 | `phase2_start_sol`, Median | Abstand zwischen den Eröffnungen ≤ 2 Sole, alle im Korridor 15–20 |
| K4 | Leerlauf-AP Σ Sol 4–12 (unverbrauchte AP plus reine Erkundungs-AP nach Kartenende) | Median-Abstand ≤ 15 % des AP-Zuflusses (≈ 20 AP) |
| K5 | „Leere Sole“ in Sol 4–15 (≥ 50 % AP ungenutzt **und** keine Pfad- oder Bauaktion) | ≤ 2 je Eröffnung |
| K6 | Regolith-Saldo zu Beginn von Sol 10 | Abstand ≤ 25 Rg (≈ 1 Harvester-Sol) |
| K7 | Siegquote und Sieg-Sol (Bot) | Abstand ≤ 10 Prozentpunkte bzw. Median ≤ 3 Sole (Bot-Ziel ≈ 50 % bleibt unverändert) |

---

## 3. Lösungsoptionen

Aufwand: K = Klein, M = Mittel, G = Groß.

| Option | Inhalt | Löst | Vorteile | Nachteile/Risiken | Aufwand |
|---|---|---|---|---|---|
| **A** Geschenk-Drohne, belegt Slot | Hangar Lv1 erstmals fertig → Drohne sofort `docked` (`grantFreeShip()` mit sofortiger Lieferung) | U3, U4 teilweise | Erster Flug noch im Fertigstellungs-Sol; entspricht der Owner-Idee | Ertrag bleibt in Credits (U2 offen); **verschärft U5**, weil die Drohne zwangsweise im einzigen Slot steht; die 300 Cr Ersparnis ist in Phase 1 wertlos | K |
| **B** Geschenk-Drohne ohne Slot | Wie A, aber außerhalb der Slot-Zählung | U3, U4, U5 | Frachter-Linie bleibt offen; früh zwei Schiffe → „aktiv arbeiten“ | Bricht die klare Regel „1 Hangar = 1 Schiff“ (Verständlichkeit); Dispatch, Recall, Reparatur und Routen hängen an `hangar_instance_id`; Wechselwirkung mit Dax-Drohne, Bot-Drohnen-Deckel und `task_expedition_coverage` (zweites Schiff beschleunigt stark, siehe game-reference §18) | M–G |
| **A+** A plus „Schiff ausmustern“ | Gedocktes Schiff kann aufgegeben werden, der Slot wird frei (ohne Erstattung) | U3, U4, U5 | Die Falle wird zur bewussten Entscheidung („Drohne behalten oder Platz für den Frachter“); Regel bleibt einfach | Neue Aktion samt UI; Bot braucht eine Regel | K–M |
| **C1** Prospektionsflug ohne Geologie-Gate | `requires` leer | **U2** | Drohne liefert ab Sol 4 Regolith: 25 Rg × 70 % / 4 Sole ≈ 4,4 Rg/Sol, vergleichbar mit geology Lv1 (+3, passiv); reine Config; passt zu §4b („Missionen liefern variable Mengen“) | Ohne Gate entfallen Kenntnis-Bonus und Organika-Skalierung (`HangarService::successChanceFor/organikaCostFor` wirken nur mit Gate). Labor- und Cantina-Spieler profitieren später auch (unkritisch) | K |
| **C2** Billigerer oder gestaffelter Hangar | z. B. 70 statt 95 | — | — | Bricht die Preisgleichheit G4/§4b und verschiebt nur den Phase-1-Takt; Leerlauf bleibt. **Nicht empfohlen** | K |
| **D1** Hint-Kette Hangar | Neuer Hint „Hangar bereit → Drohne bestellen (kommt nächsten Sol)“; Pfad-Hint ohne Raumfahrer-Vorrang; Navigations-Hint nennt die echten Ring-Kosten statt „1 AP“; im Hangar sichtbar „Frachter: Hangar Stufe 2 + freier Platz“ | U6, U5 (sichtbar) | Billig; erfüllt die Transparenz-Priorität des Owners | Löst kein Spielgewicht | K |
| **D2** T28 überdenken | Raumfahrer bringt sofort +2 AP; den Slot-Hint erst nach Schiffsankunft zu zeigen kostet 2 AP pro Sol | U4 | — | Owner hat T28 gerade so entschieden | K |
| **D3** Lieferzeit | `nexus_delivery_ticks` 1 → 0 bewirkt **nichts**, weil die Lieferung frühestens im nächsten Tick-Lauf erfolgt. Sofortlieferung braucht Code (`docked` beim Kauf). Nebenbefund: Nexus-Kredit ist entgegen der Code-Absicht nicht schneller | U3 | — | Für die Drohne durch A ohnehin erledigt | K |
| **E1** Erstkontakt-Garantie je Pfad | Jedes Pfadgebäude hat im Fertigstellungs-Sol sofort eine Handlung: Labor (heute schon), Hangar (A/C1), Cantina (garantierter erster Gast bzw. erstes Ereignis am Fertigstellungs-Sol) | K1 für alle | Symmetrische Regel, leicht zu erklären | Cantina-Teil braucht Code in `BarService` | K–M |
| **E2** Cantina-Kenntnis-Hebel ohne Labor | Falls die Annahme in 1.6 C stimmt: Tomas, Deva und Lenn bieten ohne Labor eine Alternative (z. B. Credits oder Bau-AP) | U1 (C) | Schließt eine stille Lücke bei Cantina-First | Erst verifizieren | K–M |
| **E3** Universeller AP-Abnehmer | z. B. „Handabbau“: X AP → Y Rg mit schlechtem Kurs | U1 für alle | Beseitigt Leerlauf pfadneutral | Ebnet Pfadidentität ein, könnte dominant werden und den Harvester entwerten. **Nur als Rückfallebene nach Messung** | M |

**Wechselwirkungen:**
- **Bot:** kauft heute selbst eine Drohne (`shipToRequest`). Bei A/B überspringt `hasAnyShip()` den Kauf automatisch. Bei B muss `hasFreeHangarSlot()` die Geschenk-Drohne ignorieren.
- **Credits-Pfad-Lücke:** C1 verschiebt den frühen Drohnen-Einsatz von Credits zu Regolith. Die Credits-Einnahme über Botenflüge bleibt für Phase 2.
- **Phase-1-Ziel Sol 15–20:** C1 und A+ beschleunigen Hangar-First um bis zu etwa 1–1,5 Sole. Das liegt im Rahmen von K3. Wird Hangar-First schneller als Labor-First, ist das kein Problem, solange K3 hält. `start_regolith` nicht nachziehen, bevor gemessen ist.

---

## 4. Empfehlung: Reihenfolge der Maßnahmen

| Schritt | Maßnahme | Betroffen (nur nennen) | Tests (TDD: rot zuerst) | Batch? |
|---|---|---|---|---|
| 0 | **Bot-Eröffnungsprofile + Baseline-Messung** mit heutigen Werten (Abschnitt 5) | `tests/Feature/Playtest/*`, `app/Console/Commands/Playtest.php` | siehe 5(d) | ja, Baseline |
| 1 | **D1 Hint-Kette + Transparenz** (Schiff-bestellen-Hint, Ring-Kosten im Navigations-Hint, Frachter-Voraussetzung sichtbar). Klärung D2 mit Owner | `OnboardingHintService`, `lang/de/colony.php` (content-writer), Hangar-View | `OnboardingHintServiceTest`: Hangar Lv1 + kein Schiff → neuer Hint aktiv; mit Schiff in `building` → inaktiv; Navigations-Text-Key enthält den Kostenwert des günstigsten Nebelrings | nein |
| 2 | **C1 Prospektionsflug ungegatet** | `config/missions.php` (`mission_prospecting_flight.requires`), GDD §8b-Prosa, `docs/game-reference.md` §8 | `HangarService`-Dispatch ohne geology erlaubt; `organikaCostFor` = 6; Missionskatalog listet die Mission bei Hangar Lv1; Bot-`regolithMissionCandidate` greift ohne geology | ja (mit 3) |
| 3 | **A+ Geschenk-Drohne (Slot belegt) + „Schiff ausmustern“** | `HangarService` (`grantFreeShip` mit Sofortlieferung, neue `decommissionShip`), Auslösestelle beim ersten Hangar-Lv1 (Building-Levelup-Pfad), Hangar-UI, Sol-Bericht | Hangar 0→1 erste Instanz → genau 1 Drohne `docked`; zweite Hangar-Instanz → keine Drohne; Reset/neuer Run → wieder genau eine; Ausmustern nur `docked`, Slot danach frei, Frachter belegbar; Dax-Anliegen unverändert | ja (mit 2) |
| 4 | Nur falls K1/K4/K5 für Cantina-First verfehlt: **E1-Cantina** (erster Gast garantiert) und **E2** (nach Verifikation) | `BarService`, `game.bar.*` | je Mechanik ein roter Test vorab | ja |
| 5 | Nur falls danach noch Leerlauf in allen Nicht-Labor-Eröffnungen: **E3** gesondert designen | — | — | ja |

**Bewusst nicht empfohlen:** C2 (Preisgleichheit), Änderungen an `start_regolith` vor der Messung, B ohne vorherigen Vergleich mit A+.
**Nachzug bei jeder Config-Änderung:** `docs/game-reference.md`. Dort außerdem die Drift beheben: §1 Regolith-Startwert 200 statt 300, §7 Frachter-Gate „Lv1“ statt Lv2.

---

## 5. Messplan (Owner-Vorgabe: eigene Eröffnungsprofile)

### (a) Modellierung im Bot — Empfehlung: orthogonale Dimension `opening`

- **Empfehlung:** Neues Feld `BotProfile::$opening` mit den Werten `auto | labor | hangar | cantina`. Default ist `auto`; das entspricht dem heutigen Verhalten. Die Eröffnung wird **nicht** als vierter Satz von Preset-Namen modelliert.
- **Begründung:**
  1. Die Eröffnung ist eine Reihenfolgeentscheidung für Sol 1–~15. Sparneigung und Zielfokus wirken vor allem in Phase 2. Beide Achsen sind also fachlich unabhängig.
  2. Presets würden sich multiplizieren: 4 Profile × 4 Eröffnungen = 16 Namen.
  3. Die Profil-Spec (2026-08-14) sieht neue Dimensionen ausdrücklich so vor. Bestehende Batches bleiben über `auto` vergleichbar.
  4. Abweichung von „stetige Float-Regler“ ist hier gerechtfertigt, weil die Wahl diskret ist.
- **Aufrufbar:** `game:playtest --profiles=default --openings=labor,hangar,cantina --seeds=…`. Die Kombination ist Profil × Eröffnung × Seed. Übergabe per Env `PLAYTEST_OPENING`, gelesen in `PlaytestBotTest`.
- **Pflicht:** Der Report-Dateiname wird `{profile}-{opening}-{seed}-*.json`, und das JSON bekommt ein Feld `opening`. Sonst kollidiert `Playtest::latestReportFor()` zwischen Eröffnungen mit gleichem Seed. Die Zusammenfassungstabelle bekommt eine Spalte „Eröffnung“.
- **Befund:** Heute spielt der Bot faktisch immer Labor-First. `placeCandidate()` gibt allen drei Pfadgebäuden Priorität 1, und das stabile `usort` behält die Menüreihenfolge bei, in der Labor (id 31) vor Hangar und Cantina steht (**Annahme**: Menü nach id sortiert). Das zweite Pfadgebäude ist dann der Hangar.

### (b) Regeln je Eröffnung

| Regel | `labor` | `hangar` | `cantina` |
|---|---|---|---|
| Sol 1–2 | unverändert (Baumeister, Verlegung, Agrardom, CC Lv2) | gleich | gleich |
| Erstes Pfadgebäude (`placeCandidate`: Prio 1 nur für dieses, solange kein Pfadgebäude steht) | 31 | 44 | 52 |
| Zweites/drittes Pfadgebäude (feste Reihenfolge, **Empfehlung**, damit nur die Eröffnung variiert) | 44, dann 52 | 31, dann 52 | 31, dann 44 |
| Berater | `HIRE_ORDER` folgt dem gebauten Pfadgebäude (funktioniert bereits über das Gebäude-Gate) | Raumfahrer sofort nach Hangar Lv1, **nicht** erst nach Schiffsankunft (Bot ignoriert Hints, nimmt die +2 AP mit) | Konsul sofort |
| Erste Lv2-Ausbauten | Labor Lv2 zuerst (geology) | Hangar Lv2 nur in der Variante `hangar_freighter` (optional, siehe unten), sonst wie heute | wie heute |
| Schiffe in Phase 1 | erst mit Hangar als 2. Pfad (heute) | Drohne am Fertigstellungs-Sol (heute schon so); **neu:** Phase-1-Flüge — Prospektion (falls freigegeben, C1) vor Erkundungsflug vor Botenflug | wie Labor |
| Geschenk-Drohne (falls A/A+/B) | keine Sonderregel: `hasAnyShip()` überspringt den Kauf | dto.; bei B muss `hasFreeHangarSlot()` die Geschenk-Drohne ausklammern; bei A+ Ausmustern nur in `hangar_freighter` | — |
| Cantina-Ereignisse | ab Cantina (heute) | ab Cantina | von Sol 4 an (heute schon aktiv; prüfen, dass der Credit-Reserve-Guard in Phase 1 nicht blockiert) |

Optionale vierte Eröffnung `hangar_freighter`: keine Drohne, Hangar Lv2, Frachter, Versorgungsfahrten. Sie misst einmalig, wie viel die verdeckte Optimallinie wert ist, also wie teuer die Falle U5 ist. Kosten: 8 Läufe zusätzlich.

### (c) Vergleich

- **Design:** gepaart, also dieselben Seeds für alle Eröffnungen. Die Karte ist seit PR #333 seed-deterministisch. Ein Profil (`default`) isoliert den Effekt der Eröffnung; `focus` folgt erst nach einer Entscheidung.
- **Seeds:** **8**. Das ist die Untergrenze der Regel „6–10, nie 3“. Ergibt 3 × 8 = **24 Läufe ≈ 3 Batches à 8 parallel ≈ 51 min**. Mit `hangar_freighter` 32 Läufe ≈ 68 min. Zwei Runden (Baseline + nach Schritt 2/3) ≈ 1:45–2:15 h. Der Owner wählt vor jeder Runde 8/16/24 Läufe; meine Empfehlung ist 24.
- **Kennzahlen pro Lauf:** K1–K7 aus 2.1. Aus vorhandenen Snapshots lesbar: `phase2_start_sol`, `regolith` je Sol, `ap_unspent`, `regolith_sources`, `credits`, `ship_states`, Ausgang/Score. **Neu im RunReport:** `opening`, `first_path_action_sol`, `first_phase1_relevant_yield_sol`, AP je Kategorie getrennt in „Erkunden“ und „Pfad“ (Forschung / Dispatch+Schiffskauf / Cantina-Aktionen) sowie die Zahl der leeren Sole nach der K5-Definition.
- **Auswertung:** Pro Seed das Delta zur Labor-Eröffnung, dann Median und Spannweite. Entscheidung nur über K-Schwellen, nicht über Einzelläufe. Bot-Siegquote ≈ 50 % ist gewollt und kein Tuning-Ziel.

### (d) Aufwand Bot-Umbau: **Mittel (≈ 1–1,5 Entwicklertage)**

| Teil | Aufwand | TDD-Hinweis (Test zuerst, rot bestätigen) |
|---|---|---|
| `BotProfile::$opening` + `named()`-unabhängiger Parser, Env, `PlaytestBotTest` | K | Unit-Test: Default `auto`; ungültiger Wert wirft Exception |
| `game:playtest --openings`, Kombination, Tabellenspalte, Report-Dateiname | K | Command-Test: 2 Eröffnungen × 1 Seed → 2 Prozesse mit korrekten Env-Werten; Dateinamen kollidieren nicht |
| `placeCandidate`-Priorität und Folge-Reihenfolge | K | Neuer `BotStrategyOpeningTest`: Kolonie bei CC Lv2 mit genug Rg → `hangar` platziert 44, `cantina` 52, `auto` 31 (Regressionsschutz) |
| Phase-1-Missionsregel für `hangar` | K–M | Test: gedockte Drohne in Phase 1 + C1 → Prospektion; ohne C1 → Erkundungsflug |
| RunReport-Felder (erste Pfad-Aktion, Kategorien, leere Sole) | K–M | `RunReport`-Test mit synthetischen Aktionen je Sol |
| Optional `hangar_freighter` | K | Test: keine Drohne gekauft, Hangar Lv2 vor Frachterkauf |

---

## 6. Offene Owner-Fragen (mit Empfehlung)

1. **Geschenk-Drohne: A, A+ oder B?** Empfehlung: **A+** (Slot belegt, dafür „Schiff ausmustern“). Sie behebt Wartezeit und Falle mit einer einfachen, sichtbaren Regel. B nur, wenn die Baseline zeigt, dass Hangar-First trotz C1 + A+ unter K4/K5 bleibt.
2. **Prospektionsflug ohne Geologie-Gate (C1)?** Empfehlung: **ja**. Das ist der kleinste Hebel, der die Ertragswährung von Hangar-First auf Regolith umstellt. Die Paritätstabelle in §4b („Missionen liefern Regolith“) wird damit schon in der Eröffnung wahr.
3. **T28 (Raumfahrer-Hint erst nach Schiffsankunft) beibehalten?** Der Raumfahrer gibt sofort +2 AP. Empfehlung: Den Hint „Schiff bestellen“ voranstellen und den Raumfahrer direkt danach anbieten, ohne auf die Ankunft zu warten. Mit der Geschenk-Drohne erledigt sich die Frage.
4. **Gleichwertigkeits-Kriterien K1–K7 und Toleranzen** als Abnahmemaßstab für T30 übernehmen? Empfehlung: ja. Kern sind K3, K4 und K5, nicht die Siegquote allein.
5. **Bot: zweites Pfadgebäude in fester Reihenfolge** (siehe 5b) statt frei? Empfehlung: ja für die Messung. Eine freie Zweitwahl ist eine eigene spätere Dimension.
6. **Universeller AP-Abnehmer (E3)** nur als Rückfallebene nach Messung? Empfehlung: ja, zurückstellen, weil er die Pfadidentität einebnet.

---

## 7. Nebenbefunde (zu prüfen, nicht Teil von T30)

- `OnboardingHintService::checkHint3()` (CC-Lv2-Hint) verlangt ein Pfadgebäude ≥ Lv1 bei CC < 2. Pfadgebäude sind aber erst ab CC Lv2 im Baumenü und steigen ohne CC Lv2 nicht auf. **Annahme:** Der Hint feuert nie. Sein Kommentar beschreibt eine veraltete Rampe.
- Nexus-Kredit-Schiffe sind entgegen dem Code-Kommentar nicht schneller als der Normalkauf (siehe 1.1).
- `onboarding_hint_spend_ap_navigation` nennt „1 AP“ pro Feld, Ring 2 und 3 kosten aber 2 bzw. 3 AP. Das verstößt gegen „angezeigte Zahl = wirkende Zahl“.
- Die Missionskarte zeigt den Basis-Ertrag. Prüfen, ob der Multiplikator der Schwierigkeitsstufe (×0,7/×1,4) beim Ertrag sichtbar ist (gleiche Transparenzregel, Nachgang zu T29).
- `docs/game-reference.md`: §1 nennt 200 Rg Start (richtig: 300), §7 nennt für den Frachter Hangar Lv1 (richtig: Lv2).

---

## 9. Baseline-Ergebnis und AP-Konsumenten (2026-10-06)

**Autor:** game-designer (Analyse + Vorschlag, keine Code-/Config-Änderung). Alle Zahlen in diesem Abschnitt sind entweder **Messwerte** (Baseline 2026-10-06, 24 Läufe, Profil `default`, bis Sol 25) oder **Vorschlagswerte** (ausdrücklich so gekennzeichnet, nicht verbindlich). Deutungen sind als **Annahme** markiert. Der GDD-Hauptteil bleibt zahlenfrei; Mechanik-Prosa dieses Abschnitts wandert nach Owner-Entscheidung ins GDD §8/§9 (Erkundung, Tiefenscan), Zahlen nach `config/game.php` und `docs/game-reference.md`.

**Owner-Präzisierung (verbindlich):** Gemeint ist nicht „Aufdecken teurer machen“, sondern **neue Erkundungs-Mechaniken mit eigener Entscheidung und Belohnung** (mehrstufig, z. B. Tiefenscan, Fund, ausgraben), die über das Tile-Aufdecken hinausgehen und die frühere Idee der einsammelbaren Regolith-Quellen aufnehmen.

### 9.1 Baseline-Messwerte (Median je Eröffnung Labor / Hangar / Cantina)

| Kennzahl | Messwert | Ziel | Urteil |
|---|---|---|---|
| K1 erster Pfadnutzen | 7 / 0 / 1 | ≤ 1 | Labor verfehlt (siehe 9.2, Bot-Artefakt) |
| K2 erster Ertrag | 8 / nie bis Sol 25 / 1 | ≤ 3 | Labor und Hangar verfehlt |
| K3 Phase-2-Start | 18 / 18 / 18,5 | 15–20, Abstand ≤ 2 | ok |
| K4 Σ ungenutzte AP Sol 4–12 | 95 / 89 / 88 | Abstand ≤ 20 | Abstand ok, absolut sehr hoch |
| K5 Leerlauf-Sols Sol 4–15 | 7 / 4 / 3,5 | ≤ 2 | verfehlt |
| K6 Regolith Sol 10 | 63 / 63 / 63 | Spanne ≤ 25 | ok |

Lesart: Das Tempo ist gleich (K3, K6), aber **in allen drei Eröffnungen ist die AP-Nutzung in Sol 5–13 schlecht**. Die Eröffnungen unterscheiden sich kaum, weil das Problem nicht pfadspezifisch ist.

### 9.2 Diagnose: Wofür geht der AP-Zufluss in Sol 4–15 drauf?

Gelesen aus den Berichten (Stichprobe je Eröffnung Seed 1; die Mediane aus 9.1 bestätigen das Muster über alle 8 Seeds).

**Beispiel Labor, Seed 1 (Messwert):** Zufluss Sol 4–15 = 191 AP. Davon Bau/Projekte 43 (Sol 7: 10, Sol 14: 14, Sol 15: 19), Erkunden 23 (nur Sol 4–5), Reparatur ca. 11, **ungenutzt 114 (rund 60 %)**. Ab Sol 5 liegt der Tagesrest bei 13–16 AP von 14–16 Zufluss, also nahezu 100 % ungenutzt, bis Sol 13. Sol 14 bis 25 sind dagegen voll ausgelastet (Rest 0).
**Hangar, Seed 1:** gleiches Bild, Restmenge 13–15 AP/Sol in Sol 6–13. Die einzigen pfadspezifischen Ausgaben sind drei Flüge zu je 2 AP (Sol 8, 10, 12).
**Cantina, Seed 1:** Sol 6–14 **keine einzige Aktion**, Rest 14–17 AP/Sol. Es kam in diesem Lauf kein Cantina-Ereignis zur Ausführung (Stichprobe, nicht belegt für alle Seeds).

**Wohin der Zufluss wirklich fließt (Messwerte aus dem Log, Labor Seed 1):**
- Sol 0–1: fast alles in CC-Ausbau und Platzieren (Rg-Kette), Sol 2: Verlegung des Harvesters.
- **Sol 2–5: Erkunden.** Danach wird kein `explore_tile` mehr ausgeführt (geprüft bis Sol 17). Das Aufdecken der Karte ist also **nach etwa 45 AP und Sol 5 vollständig abgeschlossen** und trägt in Sol 6–13 nichts mehr bei. Erkundung ist ein endlicher Sink, der sehr früh leerläuft.
- Sol 6–13: außer ca. 1 AP Reparatur pro Sol passiert nichts (Labor), bzw. 2 AP Flug alle 2 Sole (Hangar).

**Wo staut es sich — drei Ursachen:**

1. **Regolith ist der Engpass, AP nicht.** Die Phase-1-Kette (CC-Ausbau, Lv2-Ausbauten, zweites Pfadgebäude) kostet Regolith, nicht AP (ca. 41 AP Bauaufwand gegen ca. 190 AP Zufluss). Bauen und Investieren sind **regolith-limitiert**: Ein Ausbau-Zyklus verbraucht beim Start Regolith, und der Bot (und ein sparsamer Spieler) wartet auf den Harvester. AP-Kosten für Bauen sind in den Warteblöcken daher kein Konsument.
2. **Es gibt in Phase 1 kaum regolith-freie, unbegrenzte AP-Abnehmer.** Das ist nur die Forschung (nur Labor, setzt Analytiker + Labor voraus), danach Missionen (1–2 AP je Flug, schiffsgebunden, 2–4 Sole Laufzeit), Reparatur (ca. 1 AP/Sol) und zufällige Cantina-Aktionen (ca. 2 AP, selten). Alle anderen Konsumenten sind endlich, selten oder gedeckelt.
3. **Bot-Regel erzeugt einen Teil des Labor-Leerlaufs (Befund, Annahme zur Gewichtung).** `BotStrategy::researchCandidate()` hält Forschung zurück, solange weniger als 3 Berater angeworben sind und das Regolith unter den Kosten des nächsten Pfadgebäudes liegt (ca. 120). Forschung kostet aber **kein Regolith** (anders als Regolith-Lv-Ups, wo der Puffer sinnvoll ist; für diese ist er seit T9 schon auf Regolith-pflichtige Schritte begrenzt). Genau das erklärt den Messwert: Labor Lv1 steht ab Sol 7 mit Analytiker, die erste Forschung beginnt aber erst in Sol 14, als der dritte Berater da ist. Der Labor-Wert K1 = 7 und ein Großteil des Labor-K5 sind damit sehr wahrscheinlich **Bot-Artefakte**, kein Designbefund. Ein menschlicher Spieler würde in Sol 8–13 forschen. **Empfehlung: Diese Regel vor der zweiten Messung korrigieren (Forschung nicht puffern) und die Labor-Baseline mit 8 Läufen wiederholen**, sonst wird die Referenz „Labor“ zu schlecht gemessen und die Lücke zu Hangar/Cantina unterschätzt.

**Ist Erkundung der richtige Hebel?** Nur teilweise. Das **Aufdecken** selbst ist zu klein (ca. 45 AP, endet in Sol 5). Der richtige Hebel ist ein **neuer, regolith-freier AP-Sink mit Regolith-Ertrag**, der an die Erkundung anknüpft, weil Erkundung die einzige Mechanik ist, die für alle drei Pfade ohne Gebäude- und Schiffsvoraussetzung sofort verfügbar ist. Er löst Ursache 1 und 2 zugleich: Er wandelt überschüssige AP in das knappe Regolith um und gibt der Warte-Phase etwas zu tun. Er löst **nicht** K1/K2 der Pfade (siehe 9.4).

### 9.3 Bewertung der Owner-Idee

**Vorteile**
- Wirkt auf den echten Engpass (Regolith) statt auf Beschäftigung allein; entschärft die Wartesole in Sol 6–13.
- Pfadneutral: Der Sink braucht weder Labor noch Hangar noch Cantina. Alle drei Eröffnungen erreichen ihn gleich früh und gleich billig, das stärkt K3/K6-Gleichheit.
- Passt zur Kern-Fantasie (kleine, lokale Funde um die Kolonie, kein Imperium) und zu „Knappheit statt Überfluss“: AP reichen dann nicht für alles (Forschung gegen Bergung gegen Reparatur).
- Nutzt bereits vorhandene Infrastruktur: `is_deep_scanned`, `event_type`, `has_signal`, Tiefenscan-Aktion, Missionsbelohnung `deep_scan`, GDD §5 (Event-Tiles).

**Risiken und Gegenmaßnahmen**
- **Pflicht-Standardlinie:** Der Sink wird von jedem Spieler und vom Bot genommen, weil AP sonst leer bleiben. Das ist unkritisch, solange er (a) im Gesamtbudget klein bleibt, (b) echte Konkurrenz zu Forschung/Reparatur hat und (c) pro Fund eine kleine Entscheidung enthält. Für Labor-Spieler ist es eine echte Abwägung (Kenntnis oder Bergung), für Hangar/Cantina fast ohne Opportunitätskosten. Das ist **gewollt** (sie haben den Leerlauf), verengt aber den Unterschied.
- **Regolith-Inflation:** Das Gesamtbudget muss am Karten-Regolith-Budget der Generator-Invarianten hängen und klein sein. **Vorschlagswert:** Fundbudget je Karte ca. 40 Rg, also etwa 2 Harvester-Sole und rund 15 % der Phase-1-Kette (260 Rg). Phase 2 rutscht damit um etwa 1–2 Sole nach vorn (18 → 16–17, bleibt im Korridor 15–20 laut K3). Mehr als das würde die Rg-Knappheit, die Kern-Fantasie, aufweichen.
- **Rendite zu hoch:** Der Sink darf nie effizienter sein als die Missionen (Prospektionsflug ca. 4 Rg/AP, aber schiffsgebunden) und nie als der Harvester. **Vorschlagswert:** ca. 0,5–0,7 Rg pro AP einschließlich Scan.
- **Kartenende:** Wenn alles aufgedeckt und geborgen ist, ist AP wieder frei (heute schon ab Sol 5 beim Aufdecken). Gegenmaßnahme: Bergung als **Projekt mit AP-Deckel pro Sol** (wie der Gebäudeausbau), damit sich die Ausgaben über Sol 5–13 verteilen statt in zwei Sols zu verpuffen. Ab ca. Sol 14 ist AP ohnehin knapp (Rest 0 in Sol 14–25, Messwert), das Ende des Sinks ist dort also kein Problem.
- **Eröffnungs-Gleichwertigkeit:** Der neutrale Sink behebt K4/K5 für alle, **nicht K1/K2** (pfadspezifischer Erstnutzen). Wer Hangar-First und Cantina-First gleichwertig machen will, braucht die Spec-Maßnahmen aus Abschnitt 3/4 zusätzlich (Prospektionsflug ohne Gate, Geschenk-Drohne, Cantina-Erstkontakt).
- **Verständlichkeit:** Jede Zahl (Fundmenge, Bergungsaufwand, Deckel pro Sol) steht vor der Entscheidung in der Fundkarte und gilt genau so. Zufall nur bei der Frage, **was** ein Signal ist (wird durch den Scan sicher), nie bei der Menge.

### 9.4 Mechanik-Varianten

Gemeinsame Randbedingungen aller Varianten: **keine Gebäude-, Kenntnis- oder CC-Voraussetzung** (sonst bricht die Eröffnungs-Gleichwertigkeit), Platzierung deterministisch über `RunSeed::forColony()` bei der Kartengenerierung (`ColonyTileService`, gleiche Stelle wie die Zonen- und Rohstoff-Tiles; Funde zählen **nicht** auf das Budget der Harvester-Tiles, die Invariante „genau zwei aufgedeckte Rg-Tiles“ bleibt unberührt), Daten in den vorhandenen Spalten `event_type`/`is_deep_scanned`. **Annahme (zu prüfen):** Der Generator setzt heute `event_type` nie (alle Tiles `null`, nur `ColonySeedDemo` und Missionsbelohnungen belegen es); der Tiefenscan ist in echten Läufen also ein Pfad ohne Inhalt.

#### Variante A (empfohlen): „Signal, Tiefenscan, Bergungsprojekt“

Ablauf:
1. **Aufdecken (bestehend).** Auf aufgedeckten Feldern der Ringe 2 und 3 erscheint bei einem Teil der Tiles ein **Signal** (`has_signal` existiert bereits). Ringe 1 und Zone-Tiles tragen keine Signale. Das gibt dem Aufdecken von Ring 2/3 einen Zweck, ohne die Kosten zu ändern.
2. **Tiefenscan (bestehend, Basiskosten wie heute).** Der Scan zeigt die Art des Fundes und alle Zahlen: Regolith-Fund klein/mittel/groß oder **Fehlalarm** (kein Ertrag). Kosten werden vor dem Scan angezeigt. Bestehende Rabatte (CC Lv3, Uplink) bleiben gültig und werden in der Anzeige eingerechnet.
3. **Bergungsprojekt.** Nach dem Scan kann der Spieler AP in den Fund einzahlen wie in einen Gebäudeausbau. Es gibt einen **AP-Deckel pro Sol** je Projekt und höchstens **zwei parallele Projekte**. Bei vollem Fortschritt wird der angezeigte Regolith-Betrag gutgeschrieben, das Tile wird `terrain_empty` (damit gleich bebaubar bzw. für Harvester-Verlegung ungeeignet, kein Rohstoff-Tile).
4. **Ende.** Fund erschöpft, Signale verbraucht; es gibt keinen Nachschub. Fehlalarme sind das Risiko der Entscheidung („Scan lohnt nur, wenn ich ein Fund erwarte“).

Entscheidungen für den Spieler: Welche Signale scanne ich zuerst (näher = billiger zu entdecken, weiter = womöglich größer, Ring 3 kostet beim Aufdecken mehr)? Bergen oder forschen (Labor)? Teilbergen und AP für Reparatur frei halten?

**Vorschlagswerte (Annahme, nach Messung nachziehen):**

| Größe | Vorschlag |
|---|---|
| Signale je Karte (Ring 2–3) | 6, davon 4 Rg-Funde und 2 Fehlalarme |
| Tiefenscan | 2 AP (wie heute), Rabatt bleibt |
| Fund klein / mittel / groß | 6 Rg für 10 AP / 12 Rg für 16 AP / 18 Rg für 22 AP |
| Verteilung | 2 × klein, 1 × mittel, 1 × groß, ca. 42 Rg gesamt |
| Deckel je Projekt | 4 AP pro Sol, höchstens 2 Projekte parallel (also bis 8 AP/Sol) |
| Gesamtaufwand | ca. 58 AP Bergung + 12 AP Scans = ca. 70 AP, Rendite ca. 0,6 Rg/AP |

**Erwartete Wirkung** (Annahme): K4 sinkt von 88–95 auf etwa 35–45 (ca. 55–60 % der Messlücke werden absorbiert). K5: In Sol 5–12 sinkt der Rest von ca. 15 auf ca. 7 AP/Sol, also unter der 50-%-Schwelle; vermutlich ≤ 2 Leerlauf-Sols, die Marge ist knapp (Sol 13–15 prüfen). K6: Regolith zu Beginn von Sol 10 steigt um ca. 15–25 Rg (63 → ca. 80–90, innerhalb der Spanne 33–137, Abstand zwischen den Eröffnungen bleibt klein, da pfadneutral). K1/K2: unverändert (pfadspezifisch). K3: Phase-2-Start 1–2 Sole früher, bleibt im Korridor.

**Eröffnungs-Gleichwertigkeit:** Nutzbar für alle ab Sol 3 (ab Sol 2 aufgedeckte Ringe), keine Voraussetzung. Optionale **spätere** Pfad-Hebel, bewusst nicht in v1: Geologie erhöht den Fund-Ertrag (Labor, analog zur Prospektions-Entscheidung), Missionsbelohnung `deep_scan` deckt ein Signal ohne AP auf (Hangar, vorhandene Belohnung), ein Informationsgast der Cantina verrät den Fund-Typ vor dem Scan. Das würde jedem Pfad einen eigenen, kleinen Vorteil am selben Sink geben, ist aber bis zur Messung zurückzustellen (Regel „vereinfachen statt stapeln“).

#### Variante B: „Fundstelle beim Aufdecken“ (einstufig)

Beim Aufdecken bestimmter Ring-2/3-Felder wird sofort eine sichtbare Regolith-Quelle angezeigt, die für einen festen AP-Betrag einmalig eingesammelt wird. Entspricht der früheren Owner-Idee in der einfachsten Form. **Vorschlagswerte:** 4 Quellen, je 4 AP für 4–8 Rg (ca. 16 AP, ca. 24 Rg). Vorteile: kein neuer UI-Zustand außer Marker und Button, sofort verständlich, kaum Risiko. Nachteile: keine Entscheidung (alles wird immer genommen), absorbiert nur ca. 16 AP (K4 −15 bis −20), also zu klein, um K4/K5 zu lösen. **Eignet sich als Teilumfang von A** (A mit ausgelassenem Scan-Schritt), falls der Aufwand von A zu hoch ist.

#### Variante C: „Fundstelle am Harvester-Feld erschließen“ (Ertragssteigerung)

AP-Projekt auf dem Harvester-Tile, das die Ertragsstufe um eine Stufe anhebt. Wirkung dauerhaft, also überproportional (eine Stufe y1 → y2 sind +7 Rg/Sol; schon bei 40 AP über 10 Sole ca. +70 Rg). **Nicht empfohlen:** verschiebt die Kern-Ökonomie dauerhaft, wirkt in Phase 2 weiter, wird zur Pflicht-Standardlinie, schwer zu balancieren und kollidiert mit der Verlegungs-Entscheidung (Sol 1–2).

#### Vergleichsoption V: „nur Erkundungskosten erhöhen“

Ring-Kosten verdoppeln. Absorbiert höchstens ca. 45 weitere AP, und zwar nur bis die Karte aufgedeckt ist (heute Sol 5, dann etwa Sol 7), ohne jede Belohnung. Spieler werden bestraft, die Kosten stehen in Sol 2–3 in Konkurrenz zur Harvester-Verlegung (also zum ersten Regolith). Löst K5 ab Sol 8 nicht. **Nicht empfohlen.**

### 9.5 Empfehlung

**Variante A, v1 pfadneutral (ohne Pfad-Hebel), mit AP-Deckel pro Sol und kleinem Fundbudget (ca. 40 Rg).** Vorher als eigenen kleinen Schritt die **Bot-Forschungsregel** korrigieren und die Labor-Baseline neu messen. Begründung: Variante A ist die einzige, die den Engpass (Regolith) trifft, Entscheidungen enthält (Scan-Risiko, Bergen gegen Forschen) und die Sol-Verteilung glättet; B ist zu klein, C verändert die Kern-Ökonomie, V belohnt nichts. A nutzt vorhandene Struktur (`event_type`, `is_deep_scanned`, Tiefenscan-Aktion), braucht aber neue Fundtypen, Bergungsprojekt-Zustand je Tile und UI. Aufwand **Mittel bis Groß** (Annahme).

**Reihenfolge (Vorschlag):**
1. Bot-Regel Forschung ohne Rg-Puffer (TDD: `BotStrategyResearch…` rot zuerst: <3 Berater, Rg < Pfadkosten → Forschung wird trotzdem angeboten) + 8 Labor-Läufe neu messen. Erwartung: Labor K1 7 → ≤ 1, K5 Labor 7 → ≤ 3, K4 Labor sinkt deutlich. Damit ist die Baseline fair.
2. Spec A (Fundtypen, Zufall aus `RunSeed`, UI) mit dem Owner finalisieren; `game-developer`-Auftrag nach TDD.
3. Zweite Messung: 24 Läufe (3 × 8 Seeds, gleiche Seeds wie die Baseline, ca. 51 min). Zusätzlich pro Lauf neu erfassen: AP je Kategorie „Scan“, „Bergung“, Rg aus Funden (neue Zeile `regolith_sources.find`).

**Messplan (Zielwerte als Vorschlag):**

| Kennzahl | Soll nach Variante A (Median) | Warnschwelle |
|---|---|---|
| K4 Σ ungenutzte AP Sol 4–12 | ≤ 45 je Eröffnung, Abstand ≤ 20 | > 60 oder Abstand > 20 |
| K5 Leerlauf-Sols Sol 4–15 | ≤ 2 je Eröffnung | ≥ 4 |
| K6 Regolith Sol 10 | Spanne 33–137, Abstand ≤ 25 | > 137 |
| K3 Phase-2-Start | 16–18, Abstand ≤ 2 | < 15 (zu schnell) |
| Fund-Rg Sol 4–15 | 30–42 (≈ Budget) | > 45 |
| Anteil Fund-AP am AP-Zufluss | ≤ 15 % Gesamtlauf | > 25 % (wird Standardlinie) |
| Siegquote Bot | bleibt um ~50 %, Abstand zur Baseline ≤ 10 Punkte | Bot-Siegquote > 65 % (Rg-Knappheit verloren) |

**Bot-Aufwand (Annahme):** drei Regeln (`scan_signal`, `invest_find`, Priorität gegenüber Forschung: Bergung nachrangig zu Forschung, damit der Bot die Entscheidung nicht verzerrt). TDD-Pflicht gilt, Tests zuerst.

### 9.6 Offene Owner-Fragen (mit Empfehlung)

1. **Variante:** A (Signal, Tiefenscan, Bergungsprojekt) umsetzen? **Empfehlung: ja**; B nur als Notlösung, C und V verwerfen.
2. **Fundbudget:** ca. 40 Rg je Karte (rund 2 Harvester-Sole, ca. 15 % der Phase-1-Kette, Rendite ca. 0,6 Rg/AP)? **Empfehlung: klein starten**, nach der zweiten Messung gegen K3/K6 und die Siegquote nachziehen. Alternativ größer (ca. 60 Rg), dann zieht Phase 2 bis Sol ≈ 15–16 vor.
3. **Pfad-Hebel am Sink** (Geologie erhöht Ertrag, Drohnen-Mission deckt Signal ohne AP auf, Cantina-Hinweis nennt Fund-Typ) in v1 oder erst nach Messung? **Empfehlung: erst nach Messung**, v1 pfadneutral. Grund: Gleichwertigkeit und Regelanzahl (vereinfachen statt stapeln); die Hebel können später jedem Pfad einen eigenen Vorteil geben.
4. **Bot-Forschungsregel zuerst korrigieren** (Forschung ohne Rg-Puffer) und Labor-Baseline neu messen, bevor Variante A entwickelt wird? **Empfehlung: ja**, billig (klein), verhindert, dass der Sink für ein Problem gebaut wird, das zum Teil ein Bot-Artefakt ist. Zusatzfrage dabei: Soll der Cantina-Lauf (Sol 6–14 ohne Cantina-Aktion in Seed 1) separat auf Bot- oder Spawn-Ursachen geprüft werden? Empfehlung: ja, aus den Berichten der Seeds 2–8 Stichproben prüfen.
