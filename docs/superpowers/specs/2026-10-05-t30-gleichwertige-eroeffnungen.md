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
| Harvester-Ertrag | 16 / 23 / 30 Rg/Sol vorher, jetzt 18 / 26 / 34 (Ertragsstufe y1/y2/y3) | `game.harvester.fresh_yield` |
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
**Cantina, Seed 1:** ab Sol 8 kaum noch Aktionen (nur Reparatur, Bar-Aktionen ohne AP-Kosten), Rest 13–17 AP/Sol. Ursachen laut Log: Regolith-Puffer für das zweite Pfadgebäude hält alle Regolith-Schritte zurück, dritter Berater erst Sol 16, Erkundung ab Sol 5 leer (Stichprobe Seed 1, nicht belegt für alle Seeds).

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

---

## 10. Pfad-Parität: Regolith-Einkommen, Querverbindungen, Überbrückung (2026-10-07)

**Autor:** game-designer (Analyse + Vorschlag, keine Code-/Config-Änderung). Zahlen sind **Messwerte** (Baseline 2026-10-06 bzw. Neumessung Labor), **Configwerte** (gelesen aus `config/*.php`) oder **Vorschlagswerte** (so gekennzeichnet). Eigene Deutungen sind als **Annahme** markiert. Prosa-Teile wandern nach Owner-Entscheidung ins GDD §4b / §8b / §12 (zahlenfrei), Zahlen nach Config und `docs/game-reference.md`.

**Owner-Zielbild (verbindlich):** Der Harvester liefert die Grundversorgung. Jeder der drei Pfade bietet zusätzlich eine **gleichwertige** Möglichkeit, das Regolith-Einkommen zu erhöhen: Labor = Geologie (dauerhaft), Hangar = spezielle Missionen, Cantina = Handel durch Gespräche und Beziehungen. Alle drei Pfade sollen sich gegenseitig stärken können; die Wartezeit bis zum ersten Pfadertrag soll überbrückt werden.

> **GDD-Konflikt, Owner-Freigabe nötig:** GDD §4b („Pfad-C-Hebel: Credits statt Regolith“, Leitplanke bei den Cantina-Begegnungen) schließt einen planbaren Regolith-Hebel für die Cantina bisher ausdrücklich aus. Das Zielbild hebt diese Regel auf. Beim Umsetzen müssen §4b, §12 und die Paritätstabelle in §13.7 umgeschrieben werden (Frage F1 unten).

### 10.1 Diagnose je Pfad

Annahmen (Stand vor Schritt 1): Referenz-Harvester y2 (23 Rg/Sol; jetzt 26). Geologie-Stufen als Schätzung: Lv1 Sol 6, Lv2 Sol 10, Lv3 Sol 15 (nur bei Vorrang der Geologie). Missionsertrag = Config-Ertrag × Schwierigkeitsfaktor (normal: ×1,0 bei 70 %; leicht: ×0,7 bei 85 %), Fehlschlag ohne Ertrag. Die Folgen eines Fehlschlags für das Schiff habe ich nicht geprüft.

| | Labor | Hangar | Cantina |
|---|---|---|---|
| Quelle heute | Geologie erhöht Harvester-Ertrag (`geology_harvester_bonus_per_level` 3, 3, 2, 2, 2 kumulativ) | (a) Prospektionsflug der Drohne, 20–30 Rg, 4 Sol Umlauf; (b) Versorgungsfahrt des Frachters, 25 Rg + 10 Or, 2 Sol Umlauf; (c) Expedition 30–45 Rg nur als Loot-Eintrag | Credits→Regolith-Kaufangebote von Corvan (opportunistisch), Fen-Anliegen 20–30 Rg (50 % Erfolg, selten), Tauschgäste mit zufälliger Ware |
| Art | dauerhaft, planbar | wiederholbar, aktiv, 70-%-Wurf | zufällig, einmalig je Angebot |
| Größe | +3 Rg/Sol ab Lv1, bis Sol 25 etwa 70–130 Rg (Obergrenze bei Geologie-Vorrang) | (a) ≈ 4,4 Rg/Sol; (b) ≈ 7,4–8,8 Rg/Sol brutto je Frachter (Config); beides zusammen ≈ 13 Rg/Sol | Erwartungswert Fen allein ≈ 0,15 Rg/Sol (0,10 Spawn × 2/17 Gewicht × 50 % × 25 Rg); Gesamtbild ≈ 0,2–2 Rg/Sol, nicht planbar (Annahme) |
| Voraussetzungen | Labor Lv2 (zählt für Phase 1), Analytiker, 20 AP | (a) **Geologie 1 = Labor-Kenntnis**; (b) Hangar **Lv2**, 500 Cr, freier Hangarplatz (Drohne blockiert ihn) | Cantina Lv1; Credits als Kaufkraft |
| Wartezeit bis zum ersten Ertrag | 1–2 Sol nach Lv2 (Messung: Neumessung Labor K2 = 1) | (a) nie ohne Labor; (b) Hangar Lv2 + Frachter + Lieferung + Flug ≈ 5 Sol nach Lv1; **Messung: nie in 8/8 Läufen** | K2 = 1 nur durch die Vertrauens-Heuristik des Bots, echtes Regolith kaum |

**Konstruktionsfehler:**
1. **Hangar:** Die einzige schnell wirkende Drohnen-Regolith-Quelle hängt an einer Labor-Kenntnis (ein Pfad braucht den anderen, die Paritätsregel „nicht im Ob“ ist verletzt). Die zweite Quelle (Frachter) ist **stark, aber versteckt**: Sie liegt hinter Hangar Lv2, 500 Cr und einem Platz, den der naheliegende Erstkauf (Drohne) belegt. Kein Hint führt dorthin, und der Bot spielt sie nicht (K2 nie). **Annahme:** Das Messergebnis „nie“ ist daher überwiegend Zugangs- und Bot-Problem, nicht Ertragsproblem.
2. **Cantina:** Handel kennt Regolith nur als Zufall. Es gibt keine Beziehung, die einen verlässlichen Zufluss erzeugt; das GDD verbietet ihn sogar. Das Vorhandene (Corvan-Kauf, Fen) ist nicht planbar und deshalb für K2 wertlos.
3. **Labor:** unproblematisch, aber der Ertrag ist ohne Gegenstück klein, solange nur Lv1 steht (+3 gegen ≈ 23 Rg Harvester, vorher; jetzt ≈ 26).
4. **Querbefund:** Die Ertragshöhen liegen heute weit auseinander (Labor ≈ 3–8, Hangar im Vollbetrieb ≈ 13, Cantina ≈ 1). Würde man nur Zugang und Bot reparieren, wäre **Hangar der Überflieger**.

> ⚠️ BALANCE CONCERN: Hangar-Vollbetrieb (Frachter + Drohne) liefert nach Config etwa das Doppelte des angestrebten Pfadertrags. Vor jeder Anhebung von Labor oder Cantina zuerst mit dem Profil `hangar_freighter` messen (10.6), dann entscheiden, ob Hangar gesenkt (Versorgungsfahrt 25 → ca. 18 Rg) oder die anderen angehoben werden.

### 10.2 Parität definieren

**Prinzip:** Gleiche Gesamthöhe, verschiedene **Art**. Gemessen wird Regolith aus Pfadquellen je Lauf (`regolith_sources`, neue Zeilen je Pfad), nicht die Eröffnung.

| Größe | Zielwert (Vorschlag) | Begründung |
|---|---|---|
| Pfadertrag im eingeschwungenen Zustand | ≈ 6–8 Rg/Sol | ≈ 25–35 % eines y2-Harvesters; spürbar, nie Ersatz für den Harvester (Knappheit bleibt Kern-Fantasie) |
| Kumuliert Sol 10–25 | ≈ 90–130 Rg je Pfad | Labor-Obergrenze ≈ 118 (siehe Annahmen) |
| Abstand der Pfade | ≤ 25 % des Mittelwerts, Obergrenze Sol 25 | analog K6, aber auf Pfadquellen bezogen |
| Erster Pfadertrag | ≤ 3 Sol nach Pfadgebäude Lv1 (K2) | bestehendes Kriterium |
| Anteil am Gesamt-Regolith bis Sol 25 | Pfad ≈ 20–25 % des Harvesters (Σ Harvester Sol 10–25 ≈ 345) | Warnschwelle > 35 % (Knappheit verloren) |

Bezug zum Budget: Die Phase-1-Kette kostet 260 Rg. Ein Pfad bringt bis Phase-1-Ende (≈ Sol 12–18) nur ≈ 20–60 Rg und verschiebt Phase 2 um etwa 1 Sol, das passt zu K3. **Annahme/Risiko:** Wer später **alle drei** Pfade baut (CC Lv4), stapelt drei Pfaderträge (≈ 20 Rg/Sol zusätzlich). Das ist nicht gemessen und ist der Hauptgrund, den Einzelertrag eher knapp zu halten (Regolith-Rechner im Memory-Ordner vor der Umsetzung gegen die Phase-2-Bilanz rechnen).

**Art der Pfade:**
- **Labor = Dauer-Rate** (einmal bezahlt, danach geschenkt, wächst mit Stufen).
- **Hangar = Arbeit gegen Zeit und Risiko** (aktive Umläufe, AP/Proviant, Wurf).
- **Cantina = Umwandlung und Beziehung** (Credits und Gespräche werden zu Regolith; Credits sind in Phase 1 reichlich vorhanden, ≈ 2400 zu Beginn Sol 10, und genau der Cantina-Stärke zugeordnet).

### 10.3 Mechanik-Varianten je Pfad

#### Hangar

**H1 (empfohlen, Basis): Frachterlinie sichtbar machen und freischalten.** Kein neues System. (1) Prospektionsflug ohne Geologie-Gate (bereits entschieden, Geologie erhöht zusätzlich), (2) Geschenk-Drohne ohne Platz (bereits entschieden) löst den Platzkonflikt, (3) der Hangar zeigt vor dem Kauf „Frachter: Hangar Stufe 2, Versorgungsfahrt ≈ X Rg je Umlauf“ (Zahl aus Config). Ablauf: Hangar Lv2 (zählt als Lv2-Ausbau für Phase 1), Frachter kaufen, Versorgungsfahrt starten, nach 2 Sol Ertrag. Kosten: 2 Nav-AP + 3 Or je Fahrt (Rückfluss +10 Or), 500 Cr einmalig. Entscheidung: Fahren oder AP anderweitig nutzen, leichte (sicherer, ×0,7) gegen normale Schwierigkeit. Transparenz: Ertrag, Chance und Kosten stehen vor dem Start. Bot: einfach (Profil `hangar_freighter`, 8 Läufe). Risiko: zu stark (siehe Balance Concern).

**H2: Bergungsflug (nur mit Variante A aus §9).** Ein Schiff übernimmt das Bergungsprojekt eines Funds, statt dass der Spieler Bergungs-AP einzahlt: Frachter/Drohne starten zum Fund, Umlauf nach Entfernung, Ertrag **+50 %** gegenüber Selbstbergung, dafür Zeit, Proviant und Nav-AP statt Bergungs-AP. Der Hangar wird damit zum Weg, **AP durch Zeit zu ersetzen**. Entscheidung: AP jetzt oder Schiffszeit. Zufall nur wie in A (Fund ist nach Scan sicher). Bot: mittel. Nur nach A sinnvoll.

**H3: Schrotthandel / Ausmustern.** Das geplante „Schiff ausmustern“ (A+) liefert einen Schrottwert in Regolith (Vorschlag: ca. 25–40 % der Credits-Kosten in Rg-Äquivalent, z. B. Drohne ≈ 6 Rg). Eher Notfall-Ventil als Ertragsquelle; hilft beim Platzkonflikt. Nicht wiederholbar, kein Pfadertrag. Nur als Beiwerk.

#### Cantina

**C1 (empfohlen): „Kontor“ — Dauerangebot Credits→Regolith, Menge nach Beziehungsstufe.** Die Cantina hat ab Lv1 ein festes Tagesangebot („der Händler am Tresen hat Regolith“), eine Lieferung je Sol zum festen, angezeigten Preis. Die Losgröße wächst mit der **Beziehung zu Tomas**: Die Stufen existieren bereits (`bartender.ap_bonus_tiers` an `interaction_count`, 0/5/15/30). Gespräche kosten keine AP (Config), die Beziehung wächst mit der Nutzung. Vorschlagswerte: Lot 4 / 6 / 8 Rg je Sol, Preis ca. 30 Cr je Rg (zwischen Basispreis 25 und Uplink-Import 35, bessere Kondition als Uplink wegen Sofortlieferung), Handelsvorteil (Konsul, Kenntnis `trade`) senkt den Preis wie bei allen Kaufangeboten (`TradeAdvantageService`, keine neue Regel). Kosten und Selbstbegrenzung: bei Vollnutzung 6 Rg/Sol ≈ 180 Cr/Sol gegen +50 Cr/Sol Zuschuss; ein Startbestand von rund 2400 Cr trägt das etwa 14 Sol, danach bremst die Credits-Knappheit von selbst (Cantina-Begegnungen als Credits-Quelle). Entscheidung: Wie viel Credits-Reserve opfere ich für Regolith (Berater, Schiffe konkurrieren). Zufall: keiner; Transparenz: Preis und Lot im Angebot, Beziehungsstufe sichtbar. Wirkt **sofort nach dem Bau** (K2 = 0–1). Bot: sehr einfach (Kaufregel mit Credit-Reserve). Risiken: (1) Arbitrage mit dem Regolith-Verkaufskanal (T10): Verkaufspreis muss mindestens 30 % unter dem Kaufpreis bleiben; (2) Credits-Collapse nach Phase 1 (bekannter Befund) nicht verschärfen: Reserve-Untergrenze wie bei Corvans Organika-Losen; (3) Stufe 3 zu stark, dann Lot senken.

**C2: „Gerücht“ — Gespräche decken Funde auf (nur mit Variante A).** Tomas' Gespräch liefert alle paar Sol (Vorschlag: bei jeder 4. Interaktion) ein „Gerücht“: ein zusätzliches, schon vorgescannter Fund (Typ und Menge sichtbar), der per Bergungs-AP oder Bergungsflug (H2) gehoben wird. Das passt zur Cantina-Identität „Information“ (Deva, Lenn, Vesper) und bringt Regolith nur durch Arbeit. Mittlere Größe: ≈ 3 Rg/Sol. Wirkt ab dem ersten Gespräch, füllt AP-Leerlauf (K4). Bot: mittel. Risiko: verdoppelt die Fundlogik, daher erst nach A.

**C3 (verworfen als Standard): Organika↔Regolith-Tauschring für Stammkunden.** Verbessert die Tauschrate je Beziehungsstufe. Verstößt gegen die Knappheitsordnung (GDD §3/§4b) und macht den Agrardom zur Regolith-Quelle. Nur nennen, nicht empfehlen.

#### Labor

**L0 (empfohlen): unverändert.** Der Ertrag ist dauerhaft, planbar und früh. Zwei kleine Ergänzungen: (1) Geologie-Stufen erhöhen zusätzlich den Ertrag der Prospektion und später der Funde (bereits entschieden bzw. optional in A), jeweils als einzeln ausgewiesene Zeile („Basis + Geologie“); (2) die Anzeige am Harvester nennt den Geologie-Anteil einzeln (Transparenz). **Keine Erhöhung der Stufe-1-Wirkung**, solange nicht gemessen ist, dass Labor im Mittel unter ≈ 6 Rg/Sol bleibt.

**L1 (nur falls gemessen nötig): Geologie Lv1 früher.** Erste Stufe etwas billiger (AP-Kosten Lv1 gesenkt) oder durch die Querverbindung Hangar→Labor (Feldproben, siehe 10.4) beschleunigt. Vorzug: die Querverbindung, da sie keine Zahl an der Kenntnis-Kurve ändert.

### 10.4 Querverbindungen

**Bestandsaufnahme (Annahme: aus Config gelesen, nicht im Code geprüft):** Vier der sechs Richtungen existieren bereits in Ansätzen, aber alle spät, gegatet oder versteckt.

| Richtung | Heute vorhanden | Kandidaten | Wirkung / Größe | Verständlichkeit | Gefahr Pflichtlinie |
|---|---|---|---|---|---|
| Labor→Hangar | Kenntnis `cartography` senkt Nav-AP für Erkunden und Dispatch (Σ 30 %) | (a) bestehendes ausbauen: Hint „Kartografie senkt Flugkosten“; (b) Geologie erhöht Prospektionsertrag (entschieden) | Rabatt bzw. Ertragsbonus | gut | niedrig |
| Labor→Cantina | Kenntnis `trade` erhöht Verhandlungschance und Handelsvorteil | (a) `trade` senkt den Kontor-Preis über den bestehenden Handelsvorteil (keine neue Regel); (b) Forschung schaltet Angebots-Optionen frei (Owner-Beispiel), neues Konstrukt | (a) ≈ −3 bis −5 % Preis je Stufe (Vorschlag) | (a) sehr gut | mittel: `trade` wird Pflicht für Cantina-Spieler |
| Hangar→Labor | `mission_data_sweep`: 8 Forschungs-AP, aber Gate cartography 1 (Labor+Hangar) und 3 Sol Flug | (a) **Feldproben:** jede erfolgreiche Prospektion oder Erkundungsflug zahlt 2 Forschungs-AP in Geologie (`investBonus`); (b) `data_sweep` ohne Gate | (a) ≈ 1 AP/Sol Äquivalent; beschleunigt den Geologie-Ertrag | (a) gut, thematisch klar | niedrig |
| Hangar→Cantina | keine | (a) Fahrtberichte: erfolgreiche Handelsfahrt oder Hilfstransport erhöht die Beziehung zu Tomas (+1 `interaction_count`) und damit Lot-Größe im Kontor; (b) Fracht verkauft über die Cantina (neues Konstrukt) | (a) Beziehungsstufe schneller | mittel: unsichtbare Verknüpfung, muss in der Fahrtmeldung stehen | niedrig |
| Cantina→Labor | Tomas, Deva, Lenn, Sarka injizieren Bonus-AP in Kenntnisse (`investBonus`); **setzt Labor und Analytiker voraus** | Bestehendes unverändert, keine neue Regel; ohne Labor sichtbar „braucht Labor“ | 1–3 AP je Gespräch (Config), 5–15 AP je Ereignis | gut | mittel (siehe Annahme zu Tomas) |
| Cantina→Hangar | keine (Dax liefert eine Drohne, einmalig) | (a) **Frachttipp:** Gespräch mit Tomas/Händler gibt einen Gutschein „nächste Fahrt +30 % Ertrag“ (wie Vesper/Aldra-Gutscheine, gleiches Muster); (b) Cantina nennt Wrack-/Signal-Fund (C2) | (a) ≈ +5–8 Rg je Gutschein | (a) gut, Gutscheinmuster bekannt | niedrig |

**Empfehlung (kleine Auswahl, je Pfad genau ein ausgehender Bonus, bildet einen Kreis):**
1. **Labor→Cantina:** `trade` wirkt auf den Kontor-Preis (nutzt bestehenden Handelsvorteil, null neue Regeln).
2. **Cantina→Hangar:** Frachttipp-Gutschein (bestehendes Gutscheinmuster).
3. **Hangar→Labor:** Feldproben (kleine Menge Geologie-AP je erfolgreichem Flug).

Dazu bleiben die bestehenden Wirkungen unverändert (Tomas→Labor, Cartography→Hangar), werden aber sichtbar gemacht. **Annahme:** Die Kreisform (A hilft B hilft C hilft A) erzeugt keine Pflichtlinie, weil jeder Bonus Opportunitätskosten hat (AP für Gespräche, Flüge, Forschung) und jede Verbindung an den Pfad gebunden ist, der sie auslöst. **Nicht empfohlen:** die übrigen Kandidaten (Vollmatrix), insbesondere Forschung, die Cantina-Optionen freischaltet (b bei Labor→Cantina): neuer Zustand, unklare Anzeige.

> ⚠️ BALANCE CONCERN: Die Querverbindungen bevorzugen Spieler, die alle drei Pfade bauen. Das ist gewollt (Pfad = Reihenfolge, §4b), verschiebt aber die Gewichtung hin zur Dreifach-Kolonie. Messen: Siegquote und Regolith/Sol der Dreifach-Läufe gegen Zweifach-Läufe.

### 10.5 Überbrückung bis zum ersten Pfadertrag

Bewertung: füllt K4/K5 · pfadneutral · Inflation · Bot · Aufwand.

| Idee | K4/K5 | pfadneutral | Regolith-Inflation | Bot | Aufwand |
|---|---|---|---|---|---|
| **A** Signal/Tiefenscan/Bergung (§9, Owner: später verfolgen) | ja, stark (≈ 70 AP) | ja | klein (≈ 40 Rg gesamt) | mittel | M–G |
| **B1 Starthilfe-Paket:** Beim Fertigstellen jedes Pfadgebäudes (nur die ersten beiden) liefert der Nexus ein Versorgungspaket (Vorschlag: 15 Rg, pfadspezifische Beschreibung: Gesteinsproben / Ersatzteil-Fracht / Händlergeschenk); optional ein Abholauftrag auf der Karte (6 AP) | K2 sofort erfüllt, K4 minimal | ja (gleiche Menge) | 30 Rg einmalig ≈ 1,3 Harvester-Sol | trivial | K |
| **B2 Nexus-Vorschuss auf Pfadertrag:** Bis zu 40 Rg sofort, Rückzahlung 50 Rg über 10 Sol (Abzug am Harvester-Ertrag), einmalig, sichtbar als eigene Zeile | K6/K3 (Phase 1 schneller), K4 nein | ja | netto 0 bis negativ (Zins), glättet nur | einfach | K–M |
| **B3 AP→Regolith „Schürfen“ (E3-Rückfallebene):** am Harvester 4 AP für 6 Rg, jede Wiederholung am gleichen Sol −1 Rg, höchstens 3 je Sol, Neustart am nächsten Sol | K4/K5 ja | ja | **hoch**, wenn Deckel zu locker (verdrängt Pfade) | trivial | K |
| **B4 Landungsschrott:** Rund um den CC liegen 2–3 Schrott-Felder (Ring 1), die einmalig für Rückbau-AP je 4–6 Rg liefern | K4 klein (≈ 15 AP) | ja | ≈ 15 Rg | einfach | M (Kartengenerator) |
| **B5 Pfad-Gespräch „Sondierung“ (Cantina) / „Probenflug“ (Hangar) / „Bodenprobe“ (Labor):** je Pfad eine AP-pflichtige Erstaktion (2–4 AP), die ein kleines Ergebnis liefert (Hint, Fundkarte, 5 Rg) | K1/K2 | **nein, pfadspezifisch** | klein | mittel | M |
| **B6 Erkundungs-Fundquote:** jedes aufgedeckte Ring-2/3-Feld hat eine feste (angezeigte) Chance auf einen kleinen Fund (+2–4 Rg beim Aufdecken) | K4 klein (belohnt vorhandene AP) | ja | ≈ 20–30 Rg | trivial | K–M |

**Einordnung:**
- **A** bleibt die tragende Lösung für K4/K5, aber nicht für K2; schneller Wert erst mit größerem Aufwand.
- **B1** ist der kleinste wirksame Schritt für K2 in **allen** Eröffnungen (Wartezeit bis zum Pfadertrag wird auf 0 gesetzt), verändert aber die AP-Nutzung kaum.
- **B6** ist die billigste Brücke zu A (gleiche Funddaten, ohne Scan und Projekt) und ersetzt die frühere Variante B aus §9.
- **B3** nur als Rückfallebene nach Messung. Es entwertet den Harvester und ebnet die Pfade ein, vor allem wenn der Deckel nicht eng bleibt.
- **B2** ist die überraschendste Ergänzung: Es überbrückt exakt das „Ertrag kommt später“-Problem und bleibt neutral über die Laufzeit (Spieler zahlt zurück). Risiko: Schulden-Anzeige und eine zusätzliche Zahl im UI; deshalb nachrangig.
- **B5** (pfadspezifische Erstaktion) ist die einzige Idee, die K1/K2 pfadgerecht löst, kostet aber drei Mechaniken. Nur ansehen, falls B1+C1+H1 nicht reichen.

### 10.6 Empfohlene Reihenfolge und Messplan

| Schritt | Maßnahme | Aufwand | Messung |
|---|---|---|---|
| 1 | **Bot-Profile:** `hangar_freighter` (Hangar Lv2 als Lv2 #1, Frachter, Versorgungsfahrten) und Cantina-Seeds 2–8 auf Bot-/Spawn-Ursachen prüfen; neue Report-Zeile Regolith aus Pfadquellen je Sol | K | 8 Läufe `hangar_freighter`: Wie hoch ist der Hangar-Pfadertrag, wenn der Zugang funktioniert? Entscheidet über Absenken Hangar vs Anheben Cantina |
| 2 | **B1 Starthilfe-Paket** (Config + kleine Gutschrift) | K | K2 aller Eröffnungen ≤ 3; K6 Spanne ≤ 25 |
| 3 | **H1:** Prospektion ungated, Geschenk-Drohne (bereits entschieden), Frachter-Hint und Sichtbarkeit | K–M | Hangar K2 ≤ 3, Hangar-Pfadertrag Sol 10–25 gegen 90–130 |
| 4 | **C1 Kontor** (Spec ausarbeiten, TDD) | M | Cantina K2 ≤ 3 mit echtem Regolith; Regolith aus Kontor Sol 4–25 ≈ 60–120; Credits-Saldo nicht < Reserve |
| 5 | Querverbindungen: Feldproben (Hangar→Labor), `trade`→Kontor-Preis (Labor→Cantina), Frachttipp (Cantina→Hangar) | K–M | Pfadertrag je Pfad nähert sich 6–8 Rg/Sol, keine Pfad-Pflichtlinie (Siegquote je Eröffnung ≤ 10 Punkte Abstand) |
| 6 | **Variante A** (§9) mit B6 als Vorstufe; danach C2/H2 optional | M–G | K4 ≤ 45, K5 ≤ 2 (siehe §9.5) |
| 7 | B3 / B2 nur bei Restproblemen | K | – |

**Batch-Plan:** pro Runde 24 Läufe (3 Eröffnungen × 8 Seeds, gleiche Seeds wie die Baseline); nach Schritt 1 zusätzlich 8 Läufe `hangar_freighter`; nach Schritt 4 eine volle Runde; nach Schritt 6 eine volle Runde (jeweils Dauer vorher dem Owner nennen, 8/16/24 wählen lassen). **Neue Kennzahlen:** K8 Regolith aus Pfadquellen Sol 10–25 je Eröffnung, K9 Abstand der Pfadquellen (Ziel ≤ 25 %), K10 Anteil Pfadquelle am Gesamt-Regolith bis Sol 25 (Ziel 20–25 %, Warnung > 35 %). Bestehende K1–K7 bleiben Abnahmemaßstab; Bot-Siegquote ≈ 50 % bleibt Soll und ist kein Tuning-Ziel, nur eine Warnschwelle (> 65 % heißt Knappheit verloren).

**Bot-Regeln dafür:** Kontor-Kaufregel (Kauf, wenn Credits über Reserve), Frachter-Fahrten priorisiert vor Erkundungsflügen, Feldproben automatisch (keine Regel), Frachttipp einlösen, später `scan_signal`/`invest_find`. Bot-Profil-Dials (Risiko, Handel) aus dem Ideenpool nur, falls K8 zwischen Profilen stark streut.

### 10.7 Offene Owner-Fragen (max. 5, mit Empfehlung)

1. **F1 Cantina-Regolith-Hebel erlauben?** Das GDD (§4b, §12 Leitplanke) verbietet ihn. **Empfehlung: ja, als Kontor (Credits→Regolith, Beziehungsstufen, Preis/Lot sichtbar)**; Organika→Regolith-Tausch bleibt verboten.
2. **F2 Zielhöhe je Pfad ≈ 6–8 Rg/Sol (≈ 90–130 Rg Sol 10–25)?** Empfehlung: ja, zuerst messen (Schritt 1); wenn Hangar im Vollbetrieb deutlich darüber liegt, Versorgungsfahrt senken statt Labor/Cantina anheben.
3. **F3 Starthilfe-Paket (≈ 15 Rg je Pfadgebäude, erste zwei) als Standard-Überbrückung vor Variante A?** Empfehlung: ja, kleinster Hebel, K2 sofort; Menge nach Messung.
4. **F4 Querverbindungen als Kreis mit je einem neuen Bonus** (`trade`→Kontor-Preis, Frachttipp, Feldproben)? Empfehlung: ja, die drei; Tomas→Labor und Cartography→Hangar bleiben, werden sichtbar gemacht. Weitere Verbindungen erst nach Messung.
5. **F5 Hangar-Frachter als Haupt-Regolith-Weg des Hangars bestätigen** (inkl. Hangar Lv2 als Lv2 #1 für Phase 1)? Empfehlung: ja, ergänzt um Hint und Frachter-Sichtbarkeit; bei zu hohem Ertrag Versorgungsfahrt senken, nicht den Zugang erschweren.

### 10.8 Risiken

- **Hangar überschießt** (≈ 13 Rg/Sol im Vollbetrieb): ohne vorherige Messung wird die Parität nach oben verschoben, nicht erreicht.
- **Cantina-Kontor belastet Credits:** Konkurrenz zu Beratern/Schiffen ist gewollt, darf aber den Credits-Collapse nach Phase 1 nicht verschärfen; Reserve-Untergrenze und Preisabstand zum Verkaufskanal sind Pflicht.
- **Stapelung bei Dreifach-Kolonie** (≈ +20 Rg/Sol): Einzelwerte klein halten, Phase-2-Bilanz im Regolith-Rechner prüfen.
- **Zu viele neue Zahlen** (Lot, Preis, Gutschein, Feldproben, Starthilfe, Funde): gegen die Verständlichkeitspriorität. Gegenmaßnahme: jede Zahl steht vor der Entscheidung und gilt genau so; neue Regeln pro Schritt höchstens eine.
- **Bot-Artefakte:** Hangar K2 „nie“ und Cantina-Heuristik sind teilweise Bot-bedingt; vor Design-Änderungen Profil und Regeln nachziehen (Schritt 1), sonst wird wieder ein Bot-Problem als Balance-Wand behandelt.
- **GDD-Pflege:** §4b-Paritätstabelle und die Cantina-Leitplanke müssen mit dem Owner-Entscheid umgeschrieben werden, sonst widerspricht das Dokument dem Spiel.

---

## 11. Variante D: Fundpool der Erkundung als gemeinsame Regolith-Quelle (2026-10-09)

**Autor:** game-designer (Analyse + Vorschlag, keine Code-/Config-Änderung). Zahlen sind **Configwerte** (gelesen), **Messwerte** (Baseline 2026-10-06 bzw. Neumessung Labor) oder **Vorschlagswerte** (so gekennzeichnet). Eigene Deutungen sind als **Annahme** markiert. Rechnungen ohne Simulation: im Entwurf stand kein Rechenwerkzeug zur Verfügung, Erwartungswerte und Streuungen sind von Hand bzw. per Normalnäherung hergeleitet (Formeln stehen dabei) und nach der ersten Messung nachzuziehen. Der GDD-Hauptteil bleibt zahlenfrei; Mechanik-Prosa wandert nach Owner-Entscheidung nach §4b/§5/§8b/§12, Zahlen nach Config und `docs/game-reference.md`.

**Owner-Ausgangslage (2026-10-09):** Harvester = konstante Quelle (gestärkt durch Geologie), Labor = Dauerrate; Hangar und Cantina = unregelmäßig. Zusätzlich „Funde durch Erkundung“ als Zufallsquelle, die alle drei Pfade stützt. Planbarer Rg-Ertrag aus Hangar und Cantina ist nicht gewollt (Kontor/Frachtlinie aus §10 daher nicht gesetzt). Querverbindungen erwünscht. Im Zweifel darf der Harvester-Grundertrag steigen.

> **Annahme:** „Harvester = 1 Rg je Sol“ aus der Skizze lese ich als „gleichmäßige Rate pro Sol“, nicht wörtlich 1 Rg (Config: vorher 16/23/30, jetzt 18/26/34 Rg/Sol je Ertragsstufe).

### 11.1 Kurzurteil

1. **Der Pool trägt, aber er ist klein.** Ein Pool, der die Knappheit nicht auflöst, hat ein Start-Budget von rund 40 Rg und wächst bis Sol 25 auf rund 60 Rg (Brutto, Vorschlag). Das sind ca. 6–7 % des gesamten Regolith-Zuflusses bis Sol 25. Er füllt K4/K5 (AP-Sink, Entscheidung), er ist **keine Rate von 6–8 Rg/Sol je Pfad** und kann es nicht sein, ohne die Knappheit aufzugeben.
2. **Die Rate-Parität aus §10.2 (6–8 Rg/Sol je Pfad) ist mit D nicht erreichbar.** D ersetzt sie durch **Wirkungs-Parität**: jeder Pfad verändert, wie viel der Spieler aus Sockel und Pool *effektiv* bekommt. Realistische Größen: Labor ca. 3–5, Hangar ca. 1,5–2 (plus gesparte AP), Cantina ca. 0–1 Rg/Sol-Äquivalent (11.7). Das ist ehrlich ungleich; die Lücke wird über den **Harvester-Grundertrag** (alle gleich) und einen Messpunkt für Cantina geschlossen, nicht über mehr Zufall.
3. **Information ist nur dann etwas wert, wenn AP knapp sind.** In Sol 4–12 liegen 88–95 AP brach (K4). Wer dort nichts zu entscheiden hat, profitiert nicht von Gerüchten. Der Cantina-Hebel wirkt deshalb erst, sobald der Pool mehr AP binden würde, als frei sind (Nachschub-Signale, Konkurrenz zur Forschung). Das ist die wichtigste Schwäche von D und der Grund, die Cantina-Wirkung in 11.4 zweistufig zu planen.
4. **Empfehlung:** D als Kern übernehmen, in dieser Reihenfolge: Harvester-Grundertrag, Pool v1 pfadneutral, Hangar-Bergungsflug, Cantina-Gerüchte, Querverbindungen, Nachschub. §10-Kontor und -Frachtlinie fallen weg (Owner-Vorgabe).

### 11.2 Verifizierte Fakten (gelesen, nicht erinnert)

| Befund | Quelle |
|---|---|
| Der Kartengenerator setzt `event_type` **nie** (`null` in `generateDefaultTiles()`; `randomizeOuterRingRows()` liefert gar kein Feld). `has_signal` wird in `transformTile()` abgeleitet (`event_type` gesetzt, aufgedeckt, nicht gescannt). Nur `ColonySeedDemo` setzt `event_ruin`. In echten Läufen gibt es also **keine** Signale: Tiefenscan (`deepScanTile`), `mission_deep_survey` und das Ziel `signal_tile` laufen ins Leere. | `ColonyTileService`, `ColonySeedDemo`, `HangarService` |
| **Folgebefund:** `ruin_tile` (`event_ruin`) entsteht ebenfalls nie. `mission_ruin_expedition` und `mission_harvester_salvage` (Weg B der Harvester-Zweitinstanz) sind in echten Läufen unerreichbar. | `config/missions.php`, `HangarService` |
| Tiefenscan kostet 2 Nav-AP, ab Uplink Lv2 1 AP, **hartcodiert** (nicht in Config). Kein Ertrag, setzt nur `is_deep_scanned`. | `ColonyTileService::deepScanTile()` |
| `mission_deep_survey` (Drohne, `sol_distance` 2) kostet 4 Nav-AP + 6 Or und 4 Sole für dasselbe, was der Spieler selbst für 2 AP kann. Sie ist heute strikt dominiert. | `config/missions.php` |
| Karte: Ring 2 = 12 Felder (10 % Gefahr, sonst `terrain_empty`); Ring 3 = 9 von 18 Koordinaten („Frontier“, 5 % unpassierbar, 10 % Gefahr, 50 % Regolith, 35 leer), davon 2 vorab aufgedeckt. Verdeckt sind also **12 + 7 = 19 Felder**, Aufdecken kostet 12×2 + 7×3 = **45 AP**. (Die Schätzung in 1.5 von ca. 70 AP war zu hoch.) | `randomizeOuterRingRows()`, `game.colony.explore_cost_per_ring`, `RING3_FRONTIER_COUNT` |
| Ein Seed-Strom je Karte (`SeededRandom::generator($seed)`) mit fester Ziehreihenfolge; `RunSeed::forColony()` liefert `runs.rng_seed` reduziert. | `ColonyTileService`, `RunSeed` |
| Harvester-Gesamtvorkommen je Kachel: 240/450/660 Rg (`resource_max`), Ertrag vorher 16/23/30, jetzt 18/26/34; Geologie +3/+3/+2/+2/+2 kumulativ. Eine höhere Rate verkürzt also die Laufzeit der Kachel, vergrößert aber **nicht** das Karten-Regolith. | `game.harvester`, `geology_harvester_bonus_per_level` |
| Versorgungsfahrt (Frachter, `sol_distance` 1, 2 Nav-AP, 3 Or, Umlauf 2 Sole) zahlt **25 Rg + 10 Or**, Prospektionsflug (Drohne, `sol_distance` 2, Gate Geologie 1) 20–30 Rg. Erfolgschance 70 % (normal), 85 % (leicht, ×0,7). Das sind bereits **planbare Hangar-Raten von ca. 4–9 Rg/Sol**. Sie stehen im Widerspruch zur Owner-Vorgabe „kein planbarer Hangar-Ertrag“ und sind in D die offene Hauptfrage (11.4, Owner-Frage F2). | `config/missions.php`, `game.missions.difficulty` |
| Bergungs-/Kapazitätsattribute für Schiffe gibt es nicht (`config/ships.php` kennt nur Kosten, Lieferzeit, Verschleiß). | `config/ships.php` |

### 11.3 Der Pool (Pfad-Aufgabe 1)

**Idee in einem Satz:** Auf der Karte liegen verdeckte Signale; Aufdecken zeigt, *dass* etwas da ist, Tiefenscan sagt *was*, und eine Bergung holt es gegen AP (oder Schiffszeit) ab. Gesamtmenge und Zusammensetzung sind pro Run **fest und aus dem Seed ableitbar**; Zufall steckt nur in Lage, Reihenfolge und Nachschub.

#### Pool-Aufbau (Vorschlagswerte)

| Größe | Vorschlag |
|---|---|
| Signale im Start-Pool | 7, nur auf `terrain_empty` in Ring 2 und Ring 3 (nie Ring 0/1, Gefahr, unpassierbar, Regolith-Kacheln) |
| Zusammensetzung (fest, gemischt) | 2 Fehlalarme, 3 klein, 1 mittel, 1 groß |
| Fund klein / mittel / groß | 4 Rg für 12 AP / 10 Rg für 16 AP / 18 Rg für 22 AP |
| Rendite Rg je Bergungs-AP | 0,33 / 0,63 / 0,82 (bewusst gespreizt, damit Auswahl eine Entscheidung ist) |
| Tiefenscan | 2 AP (1 mit Uplink Lv2, wie heute, aber in Config) |
| Lage | größere Funde bevorzugt in Ring 3 (Aufdecken 3 AP gegen 2 AP, Umlauf eines Schiffs länger); fehlt ein geeignetes Ring-3-Feld, Ring 2 (**Annahme**, Generator prüft) |
| Bergungsprojekt | wie Gebäudeausbau: Fortschritt in AP, **Deckel 4 AP/Sol je Projekt, höchstens 2 Projekte** (8 AP/Sol) |
| Fundfeld danach | wird `terrain_empty`, frei bebaubar (Bauen auf einem Signalfeld erst nach Scan, Detail für die Umsetzung) |
| Nachschub (Schritt 6, nicht v1) | ab Sol 8 mit 20 % je Sol ein neues Signal auf einem freien leeren Ring-2/3-Feld, Typ i. i. d. wie die Mischung oben (2/7 Fehlalarm, 3/7 klein, 1/7 mittel, 1/7 groß), höchstens 5 Stück, nach Sol 30 keine mehr |

#### Start-Pool in Zahlen

- Rg brutto: 3×4 + 10 + 18 = **40 Rg** (feste Summe, Streuung 0).
- AP: Scans 7×2 = 14, Bergung 3×12 + 16 + 22 = 74, zusammen **88 AP**, Rendite 0,45 Rg/AP gesamt, 0,6–0,8 für die guten Funde. Das entspricht fast genau dem gemessenen Leerlauf von Sol 4–12 (88–95 AP): K4 sinkt für Hangar/Cantina auf rund 5–25 (Annahme: der Spieler nimmt nicht alles, Deckel 8 AP/Sol streckt es auf neun Sole). Labor-First hat durch die Forschung weniger freie AP und nimmt vor allem die guten Funde; das ist die gewollte Konkurrenz „Forschen oder Bergen“.
- Frist: Aufdecken von Ring 2 ab Sol 2–3 möglich (2 AP je Feld), Pool damit für alle ab Sol 3–4 nutzbar, ohne Gebäude, Berater oder Kenntnis.

#### Erwartungswert und Streuung

Einzelnes Nachschub-Signal: X ∈ {0 (p = 2/7), 4 (3/7), 10 (1/7), 18 (1/7)}. E[X] = 5,71 Rg, E[X²] = 67,4, Var = 34,8, σ = 5,9 Rg.

| Größe | Erwartung | Streuung (σ) | Anmerkung |
|---|---|---|---|
| Start-Pool brutto | 40 Rg | 0 | feste Mischung, nur die Lage variiert |
| Nachschub Sol 8–25 (N ~ Bin(18; 0,2): E 3,6, σ 1,7) | 20,6 Rg | 14,8 Rg | Compound-Verteilung: Var = E[N]·Var(X) + Var(N)·E[X]² = 125 + 94 |
| Pool brutto bis Sol 25 | 60,6 Rg | 14,8 Rg | 90-%-Bereich grob 36–85 Rg (Normalnäherung, schief nach oben) |
| Pool-Rg am Start von Sol 10, nur Start-Pool, Selbstbergung | ca. 20 Rg (Labor ca. 12, Hangar/Cantina ca. 28) | ca. 6 Rg | Obergrenze: 6 Sole × 8 AP = 48 AP, abzüglich ca. 10 AP Scans |
| Rg am Start von Sol 10 gesamt | siehe unten | – | der Sockel (Harvester-Kachel) dominiert die Streuung |

**Bezug auf den Sockel (Annahme, Normalnäherung):** Die gemessene Spanne von K6 liegt bei 33–137 Rg (Median 63). Ich setze σ ≈ 26 (Spanne/4). Die Streuung kommt fast vollständig von der Harvester-Kachel (vorher 16/23/30 Rg/Sol: ±7 Rg/Sol ≈ ±55 Rg bis Sol 10) und vom Bot-Verhalten, **nicht vom Pool** (σ ≈ 6). Der Pool verschiebt den Mittelwert und lässt die Streuung praktisch unverändert (Quadratsumme: √(26² + 6²) = 26,7).

| Szenario (Rg zu Beginn Sol 10) | Mittel | Anteil Runs unter 50 Rg | Anteil unter 40 Rg | Anteil über 110 Rg |
|---|---|---|---|---|
| Baseline (Messung, Median 63) | 63 | 31 % | 19 % | 4 % |
| + Pool v1 (Selbstbergung, ø +20) | 83 | 11 % | 5 % | 18 % |
| + Pool v1 + Harvester 18/26/34 (+3 Rg/Sol × 9 Sole = +27) | 110 | 1,5 % | 0,5 % | 50 % |
| Nur Harvester 18/26/34 | 90 | 6 % | 2 % | 24 % |

(Normalnäherung μ, σ = 26; Messwert-Spanne bis 137 bestätigt Rechtsschiefe, Werte sind Größenordnungen.)

**Lesart:** Der Pool halbiert die Anzahl der „armen“ Runs (< 50 Rg zu Sol 10) von rund einem Drittel auf rund einen Zehntel, ohne dass ein einzelner Run durch Pool-Zufall arm oder reich wird. Pool + volle Harvester-Anhebung verschieben Sol 10 um rund zwei Sole nach vorn (Phase-2-Start wahrscheinlich 18 → 15,5–16, an der Untergrenze von K3, siehe 11.6). Beides zusammen ist deshalb die obere Variante, nicht die Startvariante.

#### Bilanz Sol 1–25 (Referenz: Harvester-Kachel y2, Verlegung Sol 1, Zahlen in Rg)

| Posten | Heute | Mit D (Vorschlag) |
|---|---|---|
| Start | 300 | 300 |
| Harvester Sol 2–25 (24 Sole) | 24 × 23 = 552 | 24 × 26 = 624 |
| Geologie Lv1 ab Sol 6 / Lv2 ab ca. Sol 13 (Labor) | 60 + 36 = 96 (nur Labor-Linie, Obergrenze) | unverändert |
| Pool, Selbstbergung (60 Rg brutto, ca. 85 % realisiert) | – | ca. 50 |
| Pool über Bergungsflug (Faktor 1,5 auf die Schiffsfunde, ca. 60 % der Funde) | – | ca. 50 + 15 = 65 |
| Bedarf Phase 1 (Platzierungen, Lv2, CC Lv3, 2. Pfadgebäude) | 535 | 535 |
| Reparatur ab Sol 5 (ca. 2 Rg/Sol) | ca. 40 | ca. 40 |
| Verbleibend für Phase 2 (ohne Geologie, ohne Pool) | 852 − 575 = ca. 280 | 924 − 575 = ca. 350 (+ Pool 50–65) |

Der Pool macht damit etwa 5–7 % der Gesamtquelle aus (Sockel mit D 924 Rg, Pool 50–65 Rg). Er löst die Knappheit nicht auf (Warnschwelle für alle Nicht-Sockel-Quellen zusammen: 35 %, siehe 10.2 K10).

#### Determinismus aus `runs.rng_seed` (Vorschlag)

- **Start-Pool:** wird beim Sol-1-Seeding in `OnboardingService::seedStartingTiles()` direkt nach `randomizeOuterRingRows($rngSeed)` erzeugt. **Wichtig:** ein **eigener** Generator `SeededRandom::generator($rngSeed + FIND_POOL_SALT)`, nicht der bestehende Strom. Sonst verschiebt sich jede bestehende Karte, und die gepaarten Baselines (gleiche Seeds) werden wertlos. Die bestehenden Tile-Typen bleiben seed-identisch; nur `event_type` kommt dazu.
- **Reihenfolge der Ziehungen** (fest): Kandidatenliste (leere Ring-2/3-Felder in Koordinatenreihenfolge) → seeded Mischung → die ersten sieben bekommen die Typen aus der festen Mischung, größere bevorzugt auf Ring-3-Kandidaten.
- **Nachschub:** je Sol ein Wurf aus `RunSeed::forColony()`, `tick` und eigenem Salt (gleiches Muster wie Sturm/Instabilität in `GameTick`), niemals aus Zeilen-IDs (R5b-Regel in `RunSeed`).
- **Datenhaltung:** vorhandene Spalten `event_type` (neue Werte `find_small|find_medium|find_large|find_false`) und `is_deep_scanned`; zusätzlich Bergungsfortschritt je Kachel (neue Spalte `salvage_ap_spent`, Migration, Annahme).
- **`event_ruin`** (Ruinen für Zweitinstanz und Ruinen-Expedition) sollte der Generator ebenfalls setzen (z. B. 1 Ruine ab CC Lv3 oder fest ab Karte); das ist ein eigener kleiner Fix und kein Teil des Pools (Nebenbefund 11.2).

#### Was der Spieler vorab sieht (Transparenz, „angezeigte Zahl = wirkende Zahl“)

- Nach dem Aufdecken: Signal-Marker auf dem Feld, im Tooltip „unbekannt, Scan 2 AP“ (Zahl aus Config, inklusive Uplink-Rabatt).
- Nach dem Scan: Typ, Menge, Bergungs-AP und Deckel pro Sol **vorab**; Fehlalarm als eigenes Ergebnis mit Hinweis „kein Ertrag“. Zufall ist damit nur die Frage „was steckt unter dem Signal“; sie löst sich mit dem Scan, nie bei der Menge.
- Kartenkopf: „Funde: x Signale, davon y gescannt, z Rg geborgen/offen“. Die Gesamtzahl 7 und die feste Mischung sind in der Hilfe nachlesbar (Budget-Invariante wie die zwei Rg-Kacheln), nur die **Verteilung auf Felder** ist verdeckt.
- Hangar/Cantina-Wirkungen erscheinen als **eigene additive Zeilen** („Fund 10 Rg, Bergungsflug ×1,5 = 15 Rg; Gerücht: Gutschein +25 %“), nie als versteckter Zuschlag.

### 11.4 Pfad-Eingriffe (Pfad-Aufgabe 2)

| | **Labor (Planbar)** | **Hangar (Bergekapazität)** | **Cantina (Information)** |
|---|---|---|---|
| Mechanik | `geology` hebt die Harvester-Rate wie heute. **Kein** Zuschlag auf Funde (sonst zweite Geologie-Regel). | **Bergungsflug:** ein gedocktes Schiff holt einen *gescannten* Fund ab, statt dass der Spieler Bergungs-AP einzahlt. | Tomas' Gespräch liefert **Gerüchte**: zum nächsten ungescannten Signal Fehlalarm ja/nein, später Größenklasse; auf der letzten Stufe Vorwarnung vor Nachschub. |
| Zahlen (Vorschlag) | unverändert (3/3/2/2/2) | Flug: Nav-AP und Proviant nach der Missionsformel (`sol_distance` = Ring − 1; Ring 2: 2 AP/3 Or/Umlauf 2 Sole; Ring 3: 4 AP/6 Or/Umlauf 4 Sole). Ertrag **Menge × 1,5**. Drohne trägt klein/mittel, Frachter auch groß. **Kein Wurf** (der Fund ist bekannt, Zufall sinkt), nur Verschleiß wie heute. | Stufen nach `bartender.interaction_count` (bestehende Schwellen 0/5/15/30): Stufe 0 nennt Fehlalarm ja/nein, Stufe 1 die Größenklasse, Stufe 2 die exakte Menge, Stufe 3 Vorwarnung 2 Sole vor Nachschub. Eine Auskunft je Gespräch, keine AP-Kosten (wie heute). |
| Wartezeit bis zur ersten Wirkung | 1–2 Sole nach Labor Lv2 (Geologie 20 AP) | Geschenk-Drohne (ohne Slot, Owner-Entscheidung 1) ist am Fertigstellungs-Sol da; Ring-2-Flug ab Sol 3–4, **Ertrag nach 2 Solen** (K1 ≈ 0–1, K2 ≈ 2) | erstes Gespräch am Tag nach Bau: Information ab Sol 4 (K1 ≤ 1). Rg-Wirkung erst, wenn der Spieler wegen der Auskunft AP spart oder einen Fund vorzieht. |
| Entscheidung | Forschen (Geologie) oder Bergen mit denselben AP | Schiffszeit gegen AP: Flug (Nav-AP, Proviant, Zeit, +50 %) oder Selbstbergung (AP, kein Proviant); Drohne gegen Frachter; welches Signal zuerst | Welchen Scan ich mir spare; ob ich das Gespräch führe (ein Gespräch je Sol, kostet keinen AP, aber Aufmerksamkeit); Fehlalarme umgehen |
| Anzeige | Harvester-Karte: „Basis 26 + Geologie 3 = 29“ (Zeile einzeln) | Fundkarte: Menge, „Flug +50 % = x Rg“, Kosten, Rückkehr-Sol | Fundkarte: Gerücht-Zeile („Tomas: Fehlalarm“), Stufe der Beziehung sichtbar |
| Bot | sehr einfach (heute) | mittel: Regel `dispatch_salvage` (größter gescannter Fund, Schiff nach Tragkraft) | einfach: Regel `ask_rumor` am Sol, Scan überspringt gemeldete Fehlalarme |
| Wirkung Sol 25 (Rg-Äquivalent über pfadneutralem Pool, Annahme) | Geologie ca. 60–100 (Lv1 ab Sol 6, Lv2 ab ca. Sol 10–13) | ca. +25–30 (50 % auf ca. 50–60 Rg, wenn alles per Schiff) plus ca. 40–60 gesparte AP (Annahme) | 0–5 Rg direkt (ca. 8–10 gesparte Scan-AP, 4–5 Rg-Äquivalent bei 0,5 Rg/AP); mit Gutschein/Zusatzsignal (11.7) mehr |

**Eröffnungs-Gleichwertigkeit (wer kann den Pool wann nutzen):**

- **Pool ohne Pfad ist nutzbar** (Selbstbergung ab Sol 3–4). Das ist gewollt: die Eröffnungen sollen nicht am Pfadgebäude hängen, sonst verlieren Hangar-/Cantina-First ihren Wert am Sink. Zugleich ist der Pool für **keinen** Pfad Pflicht, weil jeder Pfad nur *verändert*, wie viel und wie sicher.
- **Labor-First:** nutzt den Pool *und* Geologie; Konkurrenz um AP (Forschung gegen Bergung). Das ist die einzige Eröffnung mit echter AP-Knappheit in Sol 5–13.
- **Hangar-First:** profitiert am stärksten. Die Geschenk-Drohne (Owner-Entscheidung 1) bekommt sofort eine sinnvolle Aufgabe; der Bergungsflug ist die erste pfadspezifische Aktion (K1 ≈ 0–1) und bringt Rg nach 2 Solen (K2 ≈ 2). Die Falle „Drohne blockiert den Frachter-Slot“ entfällt, weil die Geschenk-Drohne keinen Slot belegt; der Frachter hebt die Tragkraft (große Funde) und die Parallelkapazität.
- **Cantina-First:** schwächste Rg-Wirkung. Information spart Scan-AP und Fehlalarme, aber in Sol 4–12 sind AP nicht knapp. **Einordnung:** Cantina-First bleibt gleich schnell (K3/K6), aber sie ist die Eröffnung, in der D allein **K2 nicht in Regolith** erfüllt. K2 bleibt über Vertrauen und Gäste wie heute (Baseline K2 = 1). Wenn die Messung zeigt, dass dies als Leerlauf wirkt, folgt Stufe 2 (11.7).
- **Nutzt der Pool ohne Pfad:** ja, aber ohne Pfad ist die Entscheidung dünner (kein Schiff, keine Auskunft). Das ist akzeptabel.

**Hangar-Missionen und Geschenk-Drohne, Einordnung:**

- **Prospektionsflug** (20–30 Rg, Drohne, Geologie-Gate bzw. nach Owner-Entscheidung 2 ohne Gate): in D ein **konkurrierender Rg-Strom ohne Pool-Bezug** und damit der Gegenentwurf zur Owner-Vorgabe. **Empfehlung:** Prospektionsflug entfällt als Direktertrag und geht im Bergungsflug auf; Geologie erhöht dann **nicht** Prospektion, sondern wirkt (wie heute) auf den Harvester (kein Stapeln). Das ersetzt Owner-Entscheidung 2, Owner-Freigabe nötig (F2).
- **Versorgungsfahrt** (25 Rg + 10 Or je 2 Sole, ca. 7–9 Rg/Sol je Frachter) ist ebenfalls ein planbarer Strom und größer als jeder Pool-Hebel. **Empfehlung:** Regolith aus der Versorgungsfahrt streichen, Organika/Werkstoffe-Anteil behalten (der Frachter bleibt eine Versorgungsmaschine, nur nicht für Regolith). Ersatz ist der Bergungsflug mit Frachter. Alternativ (schwächer): Regolith auf 10–12 senken. Messung `hangar_freighter` zeigt vorab, wie viel das ist.
- **Deep Survey** (heute strikt dominiert, 11.2): entweder streichen oder zum Bergungsflug *mit Scan im Flug* machen („Fernscan“, sichert dem Hangar-Spieler den Scan ohne AP; bei Ring 3 spart er 2–3 Aufdeck-AP). Empfehlung: in den Bergungsflug integrieren (ein Flug scannt und birgt, 1 Regel statt zwei).
- **Geschenk-Drohne:** bleibt sinnvoll und wird in D wichtiger, weil ein sofort nutzbares Schiff den ersten Bergungsflug am Fertigstellungs-Sol erlaubt. Datenmodell-Frage (wo wohnt die slotfreie Drohne) bleibt wie in Owner-Entscheidung 1 offen.

### 11.5 Harvester-Grundertrag (Pfad-Aufgabe 3)

**Frage:** Entlastet ein höherer Grundertrag die Pfadwirkungen, und wie viel?

**Rechnung (Referenz y2, Verlegung Sol 1):** Phase-1-Bedarf 535 + Reparatur 40 = 575 Rg. Zufluss ab Start 300 + 23·(n − 1) (Stand vor Schritt 1). Ideales Phase-1-Ende bei Sol 12 (23 Rg/Sol) bzw. Sol 11 (26 Rg/Sol). Der Bot braucht bei 23 Rg/Sol Sol 17–18; das heißt, der **effektive** Zufluss im Bot-Lauf liegt nur bei ca. 15–16 Rg/Sol (Verlegung, y1-Kachel, Reparatur, Fehlallokation; Annahme). Die Anhebung wirkt dort proportional (ca. +13 % → Sol 17–18 ≈ 15,5–16,5).

| Variante (Ertragsstufen y1/y2/y3) | Δ je Sol (y2) | Sol-10-Bestand (Δ) | Phase-1-Ende (ideal / Bot-Schätzung) | Kachel-Laufzeit y2_d2 (450) ohne / mit Geologie Lv1 |
|---|---|---|---|---|
| vorher 16/23/30 | – | – | 12 / 18 | 19,6 / 17,3 Sole |
| **18/26/34 (Empfehlung, +2/+3/+4)** | +3 | +27 | 11 / 16,5 | 17,3 / 15,5 Sole |
| 20/28/36 (obere Variante) | +5 | +45 | 10,5 / 15,5 | 16,1 / 14,5 Sole |

**Nebenwirkungen:**
- **Knappheit bleibt, weil Vorkommen nicht mitwachsen:** `resource_max` (240/450/660) bleibt unverändert, die Gesamt-Rg-Menge der Karte ändert sich nicht, nur das Tempo. Das ist ein Vorteil (Karten-Budget-Invariante, Kern-Fantasie) und ein Risiko: Kacheln sind rund 12 % früher leer, **Verlegungen rücken vor** (2 AP je Hex, Transit-Sole ohne Ertrag). Die Verlegungsentscheidung wird häufiger, nicht schwerer.
- **Baukosten-Kette:** CC Lv3 (90) und das zweite Pfadgebäude (120) waren die Wartepunkte (Sol 8–11); ein früheres Erreichen verkürzt K5-Leerlauf, ohne AP zu binden. Die 5 Wartesole aus 1.4 schrumpfen auf ca. 3–4.
- **Geologie relativ:** +3 gegen 23 sind 13 %, +3 gegen 26 sind 11,5 %, bei Lv5 (+12) von 52 % auf 46 %. Geologie bleibt spürbar, der Pfad Labor verliert relativ, **Hangar und Cantina gewinnen anteilig mehr** (der Sockel steigt für alle gleich).
- **Entlastung der Pfadwirkung:** +3 Rg/Sol Sockel entspricht in Summe (Sol 2–25: +72 Rg) etwa der gesamten Hangar-Pool-Wirkung (ca. +25–30) plus der Cantina-Wirkung (0–5) zusammen, also ein Mehrfaches. Das ist der Zweifelsfall der Owner-Vorgabe: **Der Sockel kann die Pfadlücke bei Cantina/Hangar *überdecken*, aber nicht schließen** (die Pfade bleiben ungleich in der Art und im Betrag; der Sockel gibt allen den gleichen Vorsprung).
- **Warnsignal:** Bot-Siegquote > 65 % hieße Knappheit verloren (Soll ca. 50 %, siehe Projektvorgabe). Pool + 18/26/34 zusammen verschieben Sol 10 um ca. +47 Rg; das ist die Obergrenze, darüber nicht gehen.
- **Option G (nur nach Messung):** Wenn Labor in der Messung mehr als doppelt so viel Pfad-Rg wie Hangar liefert, die Geologie-Kurve abflachen (z. B. 2/2/2/2/2 statt 3/3/2/2/2, Summe 10 statt 12) und diesen Rg-Anteil in den Sockel schieben (der Sockel-Anstieg ist ohnehin gesetzt). Das nähert die Pfade an, ohne Zufall.

**Empfehlung:** Harvester 18/26/34 als erster Schritt (eine Config-Zeile, reversibel), **vor** dem Pool, damit der Effekt isoliert messbar ist. 20/28/36 nur, wenn Pool und Pfadwirkungen allein nicht reichen.

### 11.6 Querverbindungen als Modifikatoren (Pfad-Aufgabe 4)

Alle sechs Richtungen, je ein Modifikator auf vorhandene Werte. Es entsteht kein zusätzliches Zufallsereignis.

| Richtung | Modifikator (Vorschlag) | Größe | Bestand? | Empfehlung |
|---|---|---|---|---|
| Labor → Hangar | `cartography` senkt Nav-AP von Aufdecken **und** Bergungsflug (bereits implementiert für `dispatchShip`) | Σ 30 % bei Lv5 | ja | sichtbar machen („Kartografie senkt Flugkosten“), nichts Neues |
| Labor → Cantina | `trade` senkt die Gesprächsschwelle der Gerüchtestufen um 1 Gespräch je 2 Stufen | bis −2 Gespräche | neu | **optional**; Alternative: bestehender Verhandlungs-/Preisbonus von `trade` bleibt die einzige Wirkung |
| Hangar → Labor | **Feldproben:** jeder abgeschlossene Bergungsflug zahlt 2 Forschungs-AP in die zuletzt begonnene Kenntnis (`investBonus`) | 2 AP je Flug (ca. 10 % einer Stufe Lv1) | neu | **empfohlen** (Owner-Beispiel „Hangar-Mission senkt die AP-Kosten einer Kenntnis“) |
| Hangar → Cantina | **Fahrtbericht:** jeder abgeschlossene Bergungsflug zählt als zusätzliches Tomas-Gespräch (+1 `interaction_count`) | +1 je Flug | neu | optional (schwach, unsichtbare Verknüpfung, muss in der Flugmeldung stehen) |
| Cantina → Labor | Tomas' Bonus-AP und Deva/Lenn-Boni in Kenntnisse (`investBonus`) | 1–3 AP je Gespräch (Config) | ja | unverändert; **das ist bereits das Owner-Beispiel „Cantina-Gespräch reduziert AP-Kosten einer Kenntnis“**; nur „braucht Labor“ sichtbar machen |
| Cantina → Hangar | **Gerücht-Gutschein:** ein Gespräch der Stufe 1+ vergibt einmal je 5 Gespräche „nächster Bergungsflug +25 % Menge“ (Gutscheinmuster wie Vesper/Aldra) | +25 % auf einen Flug | neu | **empfohlen** (Owner-Beispiel „Cantina-Gespräch erhöht Hangar-Ertrag“) |

**Kreis (A hilft B hilft C hilft A):** Cantina → Hangar (Gutschein) → Labor (Feldproben) → Cantina (`trade`-Schwelle) bildet einen geschlossenen Kreis. Empfehlung: die beiden Hangar-Verbindungen (neu) einführen, die Labor→Cantina-Verbindung erst nach Messung, die übrigen drei sind Bestand und werden nur sichtbar gemacht.

**Einordnung der Owner-Beispiele:**
- „Forschung verbessert Handelsoptionen“: `trade` ist bereits da (Verhandlungschance, Handelsvorteil). In D ist die einzige *neue* Wirkung die Schwelle der Gerüchtestufen (optional). Eine Forschung, die neue Handelsangebote *freischaltet*, bleibt nicht empfohlen (neuer Zustand, unklare Anzeige, siehe 10.4).
- „Cantina-Gespräch senkt AP einer Kenntnis“: vorhanden (Tomas/Deva/Lenn über `investBonus`).
- „Cantina-Gespräch erhöht Hangar-Ertrag“: neu als Gerücht-Gutschein.
- „Hangar-Mission senkt AP einer Kenntnis“: neu als Feldproben.

> ⚠️ BALANCE CONCERN: Die Querverbindungen bevorzugen die Dreifach-Kolonie (wie in 10.4). Messen: Siegquote und Pool-Rg der Dreifach-Läufe gegen Zweifach-Läufe.

### 11.7 Cantina-Lücke, zweistufig

Information allein bringt in Sol 4–12 fast nichts (AP sind brach), und Credits sind in Phase 1 reichlich. Das ist die Kehrseite des Owner-Wunsches „kein planbarer Rg-Ertrag aus der Cantina“. Plan:

1. **v1:** Information (11.4) plus Gutschein (11.6). Messen: K8–K10 aus 10.6 für Cantina, Siegquote und Sol-25-Pool-Rg.
2. **v2, nur falls Cantina-Wirkung unter ca. 50 % der Hangar-Wirkung bleibt:** Gerüchte erzeugen ein **zusätzliches Signal** (mittel, vorgescannt, kein Fehlalarm), seed-bestimmt im Zeitpunkt (Chance je Gespräch ab Stufe 2: 25 %, höchstens 3 je Run). Das ist unregelmäßig, nicht planbar, an Arbeit (Bergung) gebunden und im Maximum begrenzt (30 Rg brutto, 16 AP je Fund). Es dreht „Information senkt Zufall“ um in „Information erzeugt Zufall“; deshalb nur bei Bedarf und ausdrücklich als Gegenstück zum Hangar (Menge) gedacht.
3. **Nicht empfohlen:** Credits→Regolith-Bergungsteam (planbar durch die Hintertür) und Organika-Tausch (§3-Knappheitsordnung).

### 11.8 Vergleich D gegen §10 und die Chat-Alternativen (Pfad-Aufgabe 5)

| | **D** Fundpool, Pfade verändern ihn | **§10** planbare Raten (Kontor, Frachtlinie) | **A** nur Harvester + Erkundung, Labor kein Pfad | **B** Pfade senken Kosten statt Einkommen | **C** Pfade stützen Siegziele |
|---|---|---|---|---|---|
| Idee | gemeinsamer Pool + Sockel, Pfade ändern Menge, Sicherheit, Tempo | je Pfad eigene Rate 6–8 Rg/Sol | Regolith nur aus Sockel + Pool; Geologie und Hangar-Regolith entfallen | Pfade rabattieren Bau-/Flug-/Forschungskosten | Pfade liefern Fortschritt auf Siegbedingungen |
| Vorteile | Eine Quelle für alle (K4/K5), Knappheit bleibt, Eröffnungen starten gleich, Owner-Vorgaben (kein planbares Hangar/Cantina-Rg) erfüllt, Querverbindungen als Modifikatoren | stärkste K2-Wirkung, einfach messbar | maximal einfach, klarste Verständlichkeit | passt zur Knappheit („weniger brauchen“), kein Inflationsrisiko | stärkt Run-Ziele, entlastet Rg-Frage |
| Nachteile | Pfad-Rg ungleich (Labor 3–5, Hangar 1,5–2, Cantina 0–1 Rg/Sol-Äq.), Cantina-Info schwach solange AP brach sind, großer neuer Umfang (Generator, UI, Bergung) | verletzt die Owner-Vorgabe (planbar), Hangar überschießt (≈ 13 Rg/Sol), GDD §4b muss umgeschrieben werden | Labor verliert seine Pfad-Identität, Owner-Idee „Labor = Dauerrate“ entfällt | in Phase 1 sind AP/Credits nicht knapp, Rabatte wirken kaum (gleiche Schwäche wie Cantina-Info); keine Antwort auf K4/K5 | berührt die Rg-Frage nicht; Pfade ohne Ziel-Bezug bleiben leer |
| Balance-Rechenbarkeit | gut: feste Pool-Summe, nur Lage und Nachschub streuen (σ ≈ 15 Rg bis Sol 25), Sockel dominiert die Streuung | sehr gut (Raten) | sehr gut | mittel (Kostenpfade überlagern) | schlecht (Siegziele drift-anfällig) |
| Aufwand | groß (Generator-Seed, Tiefenscan-Config, Bergungsprojekt, Bergungsflug, Cantina-Auskunft) | mittel (Kontor, Hint, Config) | klein bis mittel (Streichen, Pool) | mittel (Rabatt-Wiring-Fallen, siehe Memory „Discount-Wiring“) | mittel bis groß |
| Bot-Modellierung | mittel (vier neue Regeln, gut testbar) | einfach | einfach | mittel | mittel |
| Risiko | Pool zu klein/groß, Cantina-Lücke, Prospektion/Versorgungsfahrt als Konkurrenzquelle | Hangar-Überschuss, Credits-Collapse, Owner lehnt es ab | Pfadverlust, Eröffnungs-Gleichwertigkeit bleibt offen | Rabatte wirken erst spät, Phase-1-Leerlauf bleibt | Dreifach-Bevorzugung, Siegziel-Drift |

**Empfehlung: D, begleitet von Harvester 18/26/34 (11.5).** Begründung: D ist die einzige Variante, die K4/K5 löst, die Owner-Vorgaben (kein planbarer Hangar/Cantina-Ertrag) einhält und Querverbindungen ohne neue Zufallsereignisse erlaubt. Die Schwäche der Cantina wird nicht verschwiegen, sondern gemessen und zweistufig gelöst (11.7). **B** (Kostensenkung) wird als Würze in die Querverbindungen aufgenommen (Gutschein, Feldproben), nicht als Haupthebel. **A** wird teilweise übernommen: Hangar-Regolith aus Versorgungsfahrt/Prospektion fällt weg (11.4), das Labor bleibt aber über Geologie die Dauerrate. **C** bleibt separat.

### 11.9 Umsetzungsreihenfolge, Messplan, Risiken, offene Fragen (Pfad-Aufgabe 6)

#### Umsetzungsreihenfolge (kleinste wirksame Schritte)

| Schritt | Inhalt | Aufwand | Messung (24 Läufe, gleiche Seeds) |
|---|---|---|---|
| 0 | Messungen aus 10.6 Schritt 1 (`hangar_freighter`, Cantina-Seeds 2–8) abwarten; die Versorgungsfahrt-Zahlen aus 11.4 hängen daran | K | vorhanden bzw. in Arbeit |
| 1 | **Harvester-Ertrag 16/23/30 → 18/26/34** (Config) — **umgesetzt 2026-10-09**; gemessen: K3 17/17/16,5, K6 79/79/71,5, K4/K5 unverändert | K | K3, K6, Siegquote: Phase-2-Start 18 → ca. 16,5; K6 +27 Rg |
| 2 | **umgesetzt (2026-10-09, Branch feat/t30-pool-v1; Messung offen)** — **Pool v1 pfadneutral** (= Variante A aus §9 mit festem Start-Budget): Signale im Generator (eigener Seed-Strom), Fundtypen, Tiefenscan-Kosten in Config, Bergungsprojekt mit Deckel, UI-Fundkarte, Bot-Regeln `deep_scan_signal_tile`/`invest_find` | M–G | K4 ≤ 45, K5 ≤ 2; K6 Abstand ≤ 25; Pool-Rg Sol 10 ≈ 20 |
| 3 | **Hangar-Bergungsflug** (inkl. Scan im Flug, Entscheidung zu Prospektion/Versorgungsfahrt, Geschenk-Drohne) | M | Hangar K1 ≤ 1, K2 ≤ 3; Anteil Pool-Rg über Schiff 40–70 % |
| 4 | **Cantina-Gerüchte** (Stufen nach `interaction_count`) | K–M | Cantina K4/K5 gegen Pool allein: Spar-AP durch Auskunft |
| 5 | **Querverbindungen:** Feldproben, Gutschein; danach optional `trade`-Schwelle, Fahrtbericht | K–M | Siegquote je Eröffnung ≤ 10 Punkte Abstand; keine Pflichtlinie |
| 6 | **Nachschub-Signale** (20 %/Sol ab Sol 8) | K–M | K4 (Sol 13+), Anteil Fund-AP am AP-Zufluss ≤ 15 % |
| 7 | nur bei Bedarf: Cantina v2 (Zusatzsignal), Option G (Geologie-Kurve), 20/28/36 | K | gezielt |

Reihenfolge-Begründung: 1 ist rückholbar und isoliert den Sockel-Effekt; 2 liefert den größten K4/K5-Hebel für alle Eröffnungen zugleich; 3 und 4 geben dem Pool pfadspezifische Wirkung; 6 erst nach Messung, weil Nachschub den Fund-AP-Anteil und die Streuung erhöht.

#### Messplan (Kennzahlen, Zielwerte als Vorschlag)

Bestehend: K1–K7 (2.1), K8–K10 (10.6). Neu für D:

| Kennzahl | Definition | Soll |
|---|---|---|
| K11 Pool-Rg realisiert | Rg aus `regolith_sources.find` bis Sol 10 / Sol 25 je Eröffnung | ca. 20 / ca. 50 (Labor niedriger, Hangar höher) |
| K12 Anteil Pool-Rg über Schiff | Rg aus Bergungsflug / Rg aus Pool gesamt | Hangar 50–80 %, Labor/Cantina ≤ 20 % |
| K13 Fund-AP-Anteil | Scan- + Bergungs-AP / AP-Zufluss gesamt | ≤ 15 % Gesamtlauf, ≤ 40 % in Sol 4–12 |
| K14 Fehlalarm-Scans | Scans ohne Ertrag je Lauf | Cantina deutlich niedriger als Labor/Hangar (zeigt Auskunftswirkung) |
| K15 Pfad-Rg-Äquivalent | Geologie-Rg + Schiffs-Aufschlag (Rg) + Spar-AP × 0,5 | Abstand Labor/Hangar ≤ Faktor 2; Cantina ≥ 50 % Hangar (sonst v2) |

Profile: `default` × `labor|hangar|cantina` × 8 Seeds (24 Läufe); zusätzlich 8 Läufe `hangar_freighter` vor Schritt 3, damit die Versorgungsfahrt-Entscheidung nicht blind fällt. Dauer vor jedem Batch dem Owner nennen (8/16/24 wählen lassen). Bot-Siegquote ca. 50 % bleibt Soll; > 65 % ist Warnschwelle (Knappheit verloren), ein einzelner Rückgang kein Tuning-Ziel.

#### Risiken

- **Pool zu klein:** Er bleibt dann ein Beschäftigungs-Sink ohne Rg-Relevanz. Gegenmittel: Start-Budget 40 → 55 Rg (Rendite bleibt, Anzahl Signale 7 → 9), nur nach Messung.
- **Pool zu groß oder Pool-Pflichtlinie:** Er wird für jeden Spieler Standardlinie und entwertet den Harvester. Warnschwelle K13 > 25 %.
- **Konkurrenzquellen im Hangar:** Wird Prospektionsflug/Versorgungsfahrt nicht entschärft, liefert der Hangar weiter 4–9 Rg/Sol planbar und der Pool ist nur Beiwerk (Zielkonflikt mit Owner-Vorgabe).
- **Cantina-Lücke:** Information ohne AP-Knappheit wirkt kaum (11.7). Messen, früh entscheiden, nicht zuwarten.
- **Fehl-Gewichtung Labor:** Geologie (3–5 Rg/Sol) bleibt die stärkste Einzelwirkung; Option G beobachten.
- **Verständlichkeit:** Neue Zahlen: Signal-Mischung, drei Fundgrößen, Deckel, Faktor 1,5, Gutschein 25 %, Gerüchtestufen. Gegenmaßnahme: eine Fundkarte, jede Zahl vor der Entscheidung sichtbar, die Querverbindungen höchstens zwei neue Zahlen.
- **Generatorfalle:** Würde der Pool im selben Seed-Strom erzeugt, ändert sich jede Karte der Baselines (gepaarte Vergleiche ungültig). Eigener Salt ist Pflicht (11.3).
- **Zeilen-ID-Falle (R5b):** Nachschub nur aus `rng_seed` + Sol + Salt, nie aus Zeilen-IDs.
- **`event_ruin`:** Wird die Ruine nicht ergänzt, bleibt Weg B der Harvester-Zweitinstanz tot (Nebenbefund 11.2, nicht Teil von D).

#### Offene Owner-Fragen (max. 5, mit Vorschlag)

1. **F1 Wirkungs-Parität statt Rate-Parität?** Das Ziel 6–8 Rg/Sol je Pfad (§10.2) wird mit D nicht erreicht; Vorschlag: durch „Pfad-Rg-Äquivalent bis Sol 25“ ersetzen (Labor ca. 60–100, Hangar ca. 30 plus gesparte AP, Cantina ≥ 50 % Hangar) und den Rest über den Sockel decken. **Empfehlung: ja.** *(Owner 2026-10-09: wie vorgeschlagen)*
2. **F2 Prospektionsflug und Versorgungsfahrt:** Prospektionsflug in der Bergung aufgehen lassen (ersetzt Owner-Entscheidung 2); Regolith aus der Versorgungsfahrt streichen (Organika/Werkstoffe bleiben). **Empfehlung: ja**, sonst bleibt der Hangar ein planbarer Rg-Strom. Fallback: Versorgungsfahrt auf 10–12 Rg. *(Owner 2026-10-09: wie vorgeschlagen)*
3. **F3 Harvester-Grundertrag 18/26/34?** Vorschlag: ja, vor dem Pool; 20/28/36 nur bei Bedarf. Kachel-Vorkommen bleiben (Karten-Rg-Budget unverändert). *(Umgesetzt und gemessen, siehe Schritt 1.)*
4. **F4 Nachschub-Signale** (20 %/Sol ab Sol 8, höchstens 5) in v1 oder erst nach Messung? **Empfehlung: erst nach Messung (Schritt 6)**; v1 ist der feste Start-Pool, weil dann die Streuung null ist und die Messung sauber bleibt. *(Owner 2026-10-09: wie vorgeschlagen)*
5. **F5 Cantina:** Information plus Gutschein (v1) und Zusatzsignal (v2) nur bei Bedarf, oder Zusatzsignal gleich? **Empfehlung: v1 zuerst, v2 nur wenn K15 für Cantina unter 50 % von Hangar fällt.** *(Owner 2026-10-09: wie vorgeschlagen)*

**Annahmen, die ich in diesem Abschnitt nicht belegen konnte:** (a) Normalnäherung der K6-Streuung (σ = 26), (b) 85 % realisierte Pool-Menge, (c) Anteil 60 % der Funde per Schiff, (d) Ring-3-Kandidaten reichen für die größeren Funde, (e) „1 Rg je Sol“ aus der Skizze meint eine gleichmäßige Rate.

---

## 12. Hangar: Kosten und Instanzen vs. Bays (2026-10-10)

**Autor:** game-designer (Analyse + Empfehlung, keine Code-/Config-/GDD-Änderung). Zahlen sind **Configwerte** (gelesen) oder **Vorschlagswerte** (so gekennzeichnet). Handrechnungen und eigene Deutungen sind als **Annahme** markiert.

**Owner-Frage (2026-10-10, sinngemäß):** Labor, Cantina und Hangar kosten auf Stufe 1 gleich viel, aber nur vom Hangar gibt es mehrere Instanzen (bis zu 3). Müsste eine Hangar-Instanz dann nicht ein Drittel kosten? Alternative: eine einzige Hangar-Instanz mit mehreren ausbaubaren Bays.

### 12.1 Faktenlage (gelesen, nicht erinnert)

| Befund | Wert | Quelle |
|---|---|---|
| Hangar-Instanzen | **unbegrenzt** (`max_instances` = null, `is_instanced` = 1), nur durch Supply und Zonen-Kacheln gebremst. „Bis zu 3“ steht nirgends im Code. | `config/buildings.php` hangar, `database/seeders/data/buildings.php` |
| Stufen je Instanz | `max_level` 3, Stufe = Schiffsklasse: Lv1 Drohne, Lv2 Frachter, Lv3 Korvette. **Jede Instanz hat ihre eigene Stufe.** | `HangarService::SHIP_ID_TO_REQUIRED_HANGAR_LEVEL` |
| Schiffsplätze | 1 Instanz = 1 Schiff. Gekaufte Schiffe kommen in die erste freie Instanz, deren Stufe die Klasse trägt (T22), sonst `pending` mit Verfall nach `pending_decay_ticks`. | `HangarService::requestShip()`, `grantFreeShip()` |
| Bestellbare Klasse | höchste Hangar-Stufe der Kolonie (`hangarMaxLevel`), betreiben kann ein Schiff aber nur eine Instanz mit passender Stufe | `HangarService::hangarMaxLevel()`, `isShipInactive()` |
| Kosten je Instanz | Platzieren 95 + 25 Rg (Stufe 0→1 vorausbezahlt, T9), 11 AP bis Lv1, jede weitere Stufe 25 Rg + 10 AP. **Jede Instanz zahlt den vollen Preis.** | `build_cost`, `game.build.levelup_regolith_flat`, `ap_for_levelup` |
| Supply | 6 je Stufe und Instanz (Lv0 reserviert 6) | `ResourcesService::buildingWorkplaces()` |
| Verfall | 0,60 SP/Sol **je Instanz**, Reparatur 1 Rg + 1 AP je SP | `decay_rate`, `game.repair` |
| Zonen-Kacheln | 6 / +3 / +3 / +3 / 0 je CC-Stufe, max. 15. Jede Hangar-Instanz belegt eine. Ungeborgene Fund-Kacheln (Pool v1) sind nicht bebaubar. | `game.colony_zone_expansion`, `ColonyController::placeBuilding()` (`tile_has_find`) |
| Schiffe | Drohne 300 Cr, Frachter 500 Cr, Korvette 800 Cr; Frachter +2 Vertrauen je Stück | `config/ships.php` |
| Raumfahrer | 500 Cr, Slot nur einmal (mehrere Hangar-Instanzen zählen als ein Pfadgebäude) | `config/advisors.php`, `AdvisorController` |
| Bot | baut höchstens **2** Hangar-Instanzen (harte Sperre `$instanceCap = [44 => 2, …]`), weil er sonst alle Zonen-Kacheln mit Hangars füllte (Befund A37: 6 Instanzen, null Vertrauensgebäude). Kommentar: „Owner: Absicht sind ca. 2–3 Hangar-Instanzen“. | `BotStrategy::orderPlacementCandidates()` |

**Abweichung Owner-Wahrnehmung ↔ Code:** „Bis zu 3 Instanzen“ ist eine **Owner-Absicht** (A37-Rest, 2026-09-15), kein Code-Stand. Im Code gilt: Instanzen unbegrenzt, Stufen bis 3. Die GDD-Tabelle in §4 („Hangar | 3 (Instanzen ungedeckelt)“) meint mit „3“ die Stufe. **Annahme:** Die „3“ der Owner-Frage mischt beides.

**Nebenbefund (Annahme, zu prüfen):** Die Regel „bei CC Lv2 nur eines der drei Pfadgebäude“ (Config-Kommentar, GDD §4) habe ich in `ColonyController::placeBuilding()`/`buildableBuildings()` nicht gefunden. Dort wird nur das Agrardom-Gate geprüft. Begrenzt wird nur die Zahl der Berater-Slots (CC-Stufe).

### 12.2 Kostenlogik

**Der eigentliche Fehler ist die doppelte Bezahlung, nicht der Preis der ersten Instanz.** Wer gleichzeitig Drohne, Frachter und Korvette halten will, braucht drei Instanzen **und** drei verschiedene Stufen. Er zahlt den Platz über die Instanz und die Klasse über die Stufe, und das je Schiff.

Gesamtkosten der Ausbaustufen (Configwerte, Summen von Hand gerechnet):

| Ausbau | Rg | AP (Bau) | Supply | Kacheln | Verfall SP/Sol (≈ Rg + AP/Sol Reparatur) | Schiffe | Credits (Gebäude + Personal + Schiffe) |
|---|---|---|---|---|---|---|---|
| Labor Lv3 / Lv5 | 170 / 220 | 31 / 51 | 18 / 30 | 1 | 0,80 | – | 400 |
| Cantina Lv3 | 170 | 31 | 18 | 1 | 0,80 | – | 350 |
| Hangar 1 Instanz Lv1 | 120 | 11 | 6 | 1 | 0,60 | 1 (Drohne) | 500 + 300 |
| Hangar 1 Instanz Lv2 | 145 | 21 | 12 | 1 | 0,60 | 1 (Frachter) | 500 + 500 |
| Hangar 2 Instanzen (Lv2 + Lv1) | 265 | 32 | 18 | 2 | 1,20 | 2 | 500 + 800 |
| Hangar 3 Instanzen (Lv3 + Lv2 + Lv1) | 435 | 63 | 36 | 3 | 1,80 | 3 | 500 + 1.600 |

**Lesart:**
- Mit **einer** Instanz ist der Hangar preisgleich zu Labor und Cantina (je 120 bis Lv1, 170 bis Lv3) und im Verfall sogar billiger. Hier gibt es kein Problem.
- Ab der **zweiten** Instanz kippt es. Ein zweites Schiff kostet 120 Rg, eine Kachel, 6 Supply und doppelten Verfall. Das ist so viel wie ein ganzes Pfadgebäude. Der Volle-Flotte-Hangar kostet das 2,5-Fache einer voll ausgebauten Cantina (435 gegen 170 Rg) und doppelt so viel Supply. Dazu kommen 1.600 Cr für die Schiffe.
- **Nutzen der weiteren Plätze (Annahme):** Jeder zusätzliche Platz bringt einen weiteren parallelen Flug. Er kostet aber eigene Nav-AP, Proviant und Verschleiß. Nach F2 (Owner 2026-10-09) entfallen Versorgungsfahrt-Regolith und Prospektionsflug als planbare Ströme. Der Hangar-Ertrag hängt dann an Pool-Funden (ca. 50–60 Rg je Run) und Credits-Missionen. Ein dritter Platz hat also wenig zu tun. Der Grenznutzen fällt, der Preis bleibt voll.
- **„Preis ÷ 3“ ist die falsche Antwort.** Die erste Instanz ist das Pfadgebäude: Sie schaltet den Raumfahrer-Slot, den Missionskatalog und die Nexus-Bestellung frei. Mit ca. 32 Rg Errichtung wäre Hangar-First rund 63 Rg billiger als Labor-/Cantina-First, also etwa 2,5 Harvester-Sole früher in Phase 2 (Annahme: 26 Rg/Sol, y2). Das verletzt G4 und K3/K6. Teuer ist nicht die erste Halle, sondern **jede weitere**. Dazu verbraucht jede weitere eine Zonen-Kachel. Die ist durch Pool v1 noch knapper geworden und mit Regolith gar nicht zu bezahlen.

### 12.3 Varianten

| | **A** Instanz-Preis ÷ 3 | **B1 (empfohlen)** Eine Halle, Stufe = Klasse + Bucht | **B2** Eine Halle, Buchten als eigene Ausbau-Achse | **C1** Instanzen bleiben, gestaffelt + Deckel 3 | **C2** Instanzen bleiben, Stufe koloniweit | **C3** Schiffe teurer, Gebäude bleibt |
|---|---|---|---|---|---|---|
| Regel | jede Instanz ca. 32 Rg | 1 Hangar, Lv n = Klasse n **und** n Buchten (Lv1: 1, Lv2: 2, Lv3: 3) | 1 Hangar, Lv1–3 = Klasse; Buchten 1→3 als eigenes Projekt (Vorschlag: je 25 Rg + 10 AP) | 1. Instanz 95, weitere z. B. 40 Rg (Vorschlag), `max_instances` 3 | Klasse = höchste Stufe aller Instanzen, Instanzen sind reine Plätze | Hangar unverändert, Schiffspreise hoch |
| Rg volle Flotte (Drohne + Frachter + Korvette) | ca. 245 (3 × 32 + 3 × 25 + 3 Stufen à 25) | **170** (= Cantina Lv3) | 120 + 50 + 50 = 220 (= Labor Lv5) | 170 + 90 + 65 = 325 | 170 + 2 × 65 = 300 (weitere Instanzen nur Lv1) | 435 |
| Kacheln / Supply | 3 / 36 | **1 / 18** | 1 / 18 (Vorschlag: Buchten ohne Supply) | 3 / 36 | 3 / 30 | 3 / 36 |
| Pfad-Parität Eröffnung | **verletzt** (Hangar-First 63+ Rg billiger) | gewahrt | gewahrt | gewahrt | gewahrt | gewahrt |
| Entscheidung für den Spieler | keine neue | Stufe aufsteigen = größere Klasse + mehr Platz (ein Schritt) | Klasse oder Platz zuerst | Halle wohin, welche Stufe in welcher Halle | Halle wohin | – |
| Transparenz („angezeigte Zahl = wirkende Zahl“) | wie heute (Stufe je Instanz, T22-Falle bleibt) | **sehr gut:** „Hangar Stufe 2, Buchten 2/2“ | gut, aber zwei Zahlen | mittel (Preis hängt von der Anzahl ab, Stufe je Instanz bleibt) | mittel | wie heute |
| Löst Kachelverbrauch / Bot-Sperre | nein, verschärft (billiger = mehr Hallen) | **ja** | ja | teilweise (Deckel 3) | teilweise | nein |
| Löst T22-Falle (Frachter findet keine Halle mit Lv2) | nein | **ja** (alle Buchten haben die Hallenstufe) | ja | nein | ja | nein |
| Ort für die Geschenk-Drohne (Owner: ohne Slot) | offen | natürlich: **Vorfeld** der Halle (fester Zusatzplatz nur für die Geschenk-Drohne) | Vorfeld | offen | offen | offen |
| Fiktion | Hallenfeld | **Landefeld mit Andockbuchten** einer Kleinkolonie, kein Werftgelände | dito | Hallenfeld | Hallenfeld | – |
| Aufwand | K (eine Config-Zahl) | **M–G** | G | K–M (instanzabhängige Kosten im Lese- und Vorschaupfad, Falle aus Memory „Discount-Wiring“) | M | K |
| Urteil | verwerfen | **empfehlen** | nur falls B1 zu starr | Übergangslösung, falls B1 verschoben wird | verwerfen (halbe Lösung, gleicher Migrationsaufwand wie B1) | verwerfen (trifft Credits, nicht Kacheln, verschärft den Credits-Collapse) |

**B1 im Detail (Vorschlag):**
- **Datenmodell:** Hangar wird `is_instanced` = 0 (wie Cantina). Bucht-Anzahl = Stufe, wird **abgeleitet, nicht gespeichert**. `colony_ships.hangar_instance_id` wird zur Buchtnummer 1..3 (Annahme: Spalte umdeuten oder `bay` ergänzen, Entscheidung beim `db-migration-agent`). Die Geschenk-Drohne bekommt Bucht 0 = Vorfeld (Owner-Entscheidung 1 „belegt keinen Slot“ bleibt erfüllt).
- **Verfall und Rückbau:** Sinkt die Stufe, entfällt die oberste Bucht. Ein Schiff dort wird **inaktiv**. Das ist die bestehende Regel „Hangar unter Schiffsstufe“ (GDD §7), keine neue. Mit dem Wiederaufbau ist es automatisch wieder aktiv.
- **Bestellung und Queue:** Ein Schiff geht in die erste freie Bucht. Klasse ≤ Hallenstufe gilt für alle Buchten gleich. `pending` ohne freie Bucht bleibt wie heute.
- **Reparatur:** Es gibt nur noch einen Verfallsträger. Das spart bei drei Schiffen 1,2 SP/Sol, also ca. 1,2 Rg + 1,2 AP/Sol gegenüber heute (Annahme, Handrechnung).
- **UI:** Der Hangar-Screen listet heute schon „Slots“ (`getHangarSlots()`). Künftig zeigt er Buchten 1..Stufe plus Vorfeld; gesperrte Buchten mit „ab Stufe n“. Die Hex-Karte zeigt eine Kachel.
- **Bot:** Die Sperre `instanceCap[44]` entfällt. Kaufregel „freie Bucht“ statt „freie Instanz“. Der Hangar-Aufstieg wird zur Platz-Regel.
- **Migration:** Bestehende Kolonien mit mehreren Hallen werden auf eine Halle mit der höchsten Stufe zusammengeführt. Schiffe über der Buchtenzahl gehen auf `pending` (Annahme; Alternative: Run-Neustart, Owner-Frage 3). Die übrigen Kacheln werden frei, ohne Erstattung. Mit anzufassen sind `data/sql/testdata.sql`, die Szenarien in `ResetPlayer`, `ColonySeedDemo` und `docs/game-reference.md`.
- **Wo B1 ein Prinzip berührt:** GDD §4c macht den Hangar ausdrücklich zum „einzigen Fall mit beiden Achsen“. B1 streicht diese Ausnahme. Danach gilt für alle Gebäude außer Wohnhabitat und Harvester: ein Gebäude, eine Achse. Das ist die Regel „vereinfachen statt stapeln“. §4c und §6 („max. Schiffe = Hangar-Instanzen“) müssen umgeschrieben werden.

### 12.4 Wechselwirkung mit T30 (qualitativ, Annahme)

| Kennzahl | A ÷ 3 | B1 | C1 |
|---|---|---|---|
| K1/K2 (erster Pfadnutzen/-ertrag) | unverändert, getragen von Geschenk-Drohne + Bergungsflug | unverändert. Die Geschenk-Drohne hat mit dem Vorfeld einen klaren Ort. Der Frachter für große Funde kommt mit Lv2 ohne zweite Halle. | unverändert |
| K3 (Phase-2-Start) | Hangar-First **früher** (63+ Rg) → Abstand > 2 Sole möglich | neutral (Lv1/Lv2 kosten wie heute, Lv2 zählt weiter fürs Phase-1-Ziel) | neutral |
| K4/K5 (Leerlauf-AP) | gering + | gering +: zweites Schiff früher (Lv2 statt 120 Rg + Kachel) → mehr Flüge = mehr Nav-AP verbraucht | gering + |
| K6 (Rg Sol 10) | Hangar-Spanne nach oben | neutral bis +, wenn der Spieler sonst eine 2. Halle gebaut hätte | gering + |
| K7 (Siegquote) | ungewiss | gering +: freie Kacheln und Supply für Vertrauensgebäude (task_colony_prosperity) | gering + |
| Pool v1 / Bergungsflug | – | passt: Fund-Kacheln drücken auf die Zonen, B1 spart 2 Kacheln; Bergungsflug skaliert mit Buchten statt Hallen | teilweise |

**Gleichwertigkeit:** B1 macht den Hangar in Kosten-Kurve, Kacheln und Supply **identisch zur Cantina** (170 Rg, 1 Kachel, 18 Supply bei Lv3). Damit ist es die einzige Variante, bei der die drei Pfadgebäude auch über die Eröffnung hinaus strukturell gleich gebaut sind. A verschiebt die Eröffnungs-Parität zugunsten des Hangars, C1 lindert die Lage nur.

### 12.5 Empfehlung, Messplan, Risiken

**Empfehlung: B1, umgesetzt zusammen mit T30 Schritt 3 (Bergungsflug + Geschenk-Drohne).** Beide greifen in die Platzlogik von `HangarService` und in den Hangar-Screen ein. Zusammen braucht es eine Migration und eine UI-Runde statt zwei, und die offene Frage „wo wohnt die slotfreie Drohne“ ist mit dem Vorfeld beantwortet. Bis dahin keine Kostenänderung am Hangar. A wird verworfen.

**Messplan (24 Läufe, 3 Eröffnungen × 8 gleiche Seeds, vorher Dauer nennen):**

| Kennzahl | Soll mit B1 | Warnschwelle |
|---|---|---|
| K1–K7 | wie §2.1/§11.9, Hangar-K3 nicht früher als Labor − 1 Sol | Hangar-First K3 > 2 Sole vor Labor |
| Schiffe je Lauf bei Sol 25 / 40 (neu) | Hangar-First 2 / 3, andere 1 / 2 | Hangar-First > 3 (Vorfeld + 3 Buchten ausgereizt vor Sol 25) |
| Zonen-Kacheln für Vertrauensgebäude bei Sol 40 (neu) | +1–2 gegenüber Baseline | – |
| K12 Anteil Pool-Rg über Schiff | Hangar 50–80 % | < 40 % (Buchten werden nicht genutzt) |
| Nav-AP-Anteil am AP-Zufluss Sol 4–25 (Hangar-First) | steigt gegenüber Baseline | > 40 % (Hangar verdrängt alles andere) |

**Risiken:**
- **Weniger Entscheidung:** Ein zweites Schiff verlangt Lv2. Wer zwei Drohnen will, muss trotzdem aufsteigen. Das ist gewollt (eine Achse), kostet aber eine Freiheit. Falls der Playtest das vermisst, auf B2 ausweichen.
- **Frachter-Vertrauen:** Bei 3 Buchten gibt es höchstens 3 Frachter × 2 Vertrauen. Heute ist das unbegrenzt (praktisch über Kacheln begrenzt). Unkritisch, aber in K7 beobachten.
- **Migration:** Bestehende Spielstände mit mehr als einer Halle. Das betrifft nur Dev-, Test- und Playtest-Daten, kein Live-Betrieb (Annahme).
- **Doku-Drift:** GDD §4c, §6, §8b und `docs/game-reference.md` beschreiben das Instanzmodell. Die Doku muss im selben PR nachgezogen werden.
- **Bot-Bug-Familie „unbegrenzt instanzierbares Gebäude frisst Kacheln“** (Memory): B1 beseitigt die Ursache für den Hangar. Das Wohnhabitat behält seine Sperre.

### 12.6 Offene Owner-Fragen (max. 4, mit Vorschlag)

1. **B1 als Zielbild?** Eine Halle; Stufe 1/2/3 = Schiffsklasse **und** 1/2/3 Buchten; Geschenk-Drohne auf dem Vorfeld ohne Bucht. **Vorschlag: ja.** A (÷ 3) verwerfen, weil die erste Halle das Pfadgebäude ist.
2. **Zeitpunkt:** B1 im selben Arbeitspaket wie T30 Schritt 3 (Bergungsflug + Geschenk-Drohne)? **Vorschlag: ja**, eine Migration und eine UI-Runde. Bis dahin Hangar-Kosten unverändert lassen.
3. **Bestehende Spielstände:** zusammenführen (höchste Stufe, überzählige Schiffe `pending`) oder den aktiven Run neu starten? **Vorschlag: zusammenführen** per Migration. Freie Kacheln werden ohne Erstattung frei.
4. **Ausbaukosten:** Bleiben Hangar-Stufen bei flat 25 Rg (dann exakt wie Cantina), obwohl Lv2/Lv3 jetzt Klasse **und** Platz bringen? **Vorschlag: flat lassen und messen.** Erst wenn Hangar-First K3 über 2 Sole vorn liegt, Lv2/Lv3 anheben.

### 12.x Owner-Entscheidungen (2026-10-10)

1. **B1 ist Zielbild:** eine Halle, jede Stufe bringt Schiffsklasse und eine weitere Bucht; „Preis ÷ 3“ verworfen.
2. **Zeitpunkt:** B1 zusammen mit T30 Schritt 3 (Bergungsflug, Geschenk-Drohne); Hangar-Preis bis dahin unverändert.
3. **Bestehende Spielstände:** keine Rücksicht nötig (Entwicklungsphase) — Neustart statt Zusammenführung, keine Datenmigration.
4. **Stufenkosten:** Hangar-Stufen bleiben bei flat 25 Rg; messen; Anheben nur, wenn Hangar-First mehr als 2 Sole früher in Phase 2 kommt.

