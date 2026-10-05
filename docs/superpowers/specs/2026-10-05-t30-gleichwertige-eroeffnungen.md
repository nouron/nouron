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
