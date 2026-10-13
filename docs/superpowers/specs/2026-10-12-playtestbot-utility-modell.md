# PlaytestBot: Bewertungs-Bot (Utility-Modell, Option A)

**Stand:** 2026-10-12 · **Autor:** game-designer (Spezifikation, kein Produktionscode) · **Branch:** `feat/bot-utility-modell`

**Owner-Entscheid (2026-10-12, verbindlich):** Die Bots sollen intelligenter agieren und selbst entscheiden statt starren Regeln zu folgen. Daraus folgt Option A: Jede heutige Regel wird zum Kandidaten-Erzeuger. Sie liefert Kandidaten **mit geschätztem Nutzen** statt Ja/Nein. Ein zentraler Selektor wählt den besten Kandidaten, bis AP und Regolith verbraucht sind. Option B (Planer: Ziele → Abhängigkeiten) kommt eventuell später als Aufsatz.

**Randbedingungen (Owner):** deterministisch und reproduzierbar (gepaarte Seeds, `RunSeed`). Bot-Profile bleiben als Gewichtssätze erhalten. Eröffnungsprofile (`--openings`) bleiben als Gewichtungs-Bias. Die Bot-Siegquote ist **kein** Tuning-Ziel. Alle Läufe bleiben über `game:playtest`, `RunReport` und `OpeningComparison` auswertbar. Die Playtest-Suite soll grün bleiben. Migration schrittweise.

Alle Zahlen stammen aus Code und Config (gelesen am 2026-10-12). Handrechnungen und eigene Deutungen sind als **Annahme** markiert.

> **Quellenhinweis:** Der Auftrag nennt „Spec T30 §14, Ursachen U1–U6, warum Pfadgebäude 9–12 Sole auf Stufe 1 bleiben“. §14 liegt laut Memory auf Branch `feat/t30-schritt4-spec` und ist auf diesem Branch nicht vorhanden. Die Ursachen U1–U6 in §2 derselben Spec sind **spielseitig** (Abnehmer, Währung, Latenz). Abschnitt 1.3 leitet deshalb die **botseitigen** Ursachen B1–B7 direkt aus dem Code ab. Der Abgleich mit §14 ist Task P0 (Abschnitt 7).

---

## 1. Ist-Analyse

### 1.1 Wie der Bot heute entscheidet

| Baustein | Ort | Verhalten |
|---|---|---|
| Regelliste | `BotStrategy::default()` | 28 Regeln (+3 Fokus-Regeln bei `objectiveFocus > 0`, eingefügt vor `relocate_harvester`). Jede Regel hat `when(BotSession): Kandidat\|null` und `do(BotSession, Kandidat): Antwort`. |
| Auswahl | `PlaysSolLoop::playOneSol()` | Die Regeln werden von oben nach unten geprüft. **Die erste passende Regel feuert**, danach beginnt die Prüfung wieder bei Regel 1. Die Priorität steckt also nur in der Listenposition. |
| Fehlschlag | `$blockedThisSol` | Eine abgelehnte Regel ist für den Rest des Sols gesperrt (nach Regelname). |
| Wächter | `MAX_ACTIONS_PER_SOL = 200`, `NO_OP_REPEAT_LIMIT = 3` | Abbruch bei Endlosschleife bzw. bei dreimal gleichem Erfolg ohne Änderung an AP, Regolith oder Organika. |
| Sol-Ende | — | Passt keine Regel mehr, endet der Sol. Rest-AP verfallen (K4). |
| Puffer | `cheapestPendingPathBuildingCost()` (solange < 3 Berater), `ccResearchReserve()` (T17, Phase 2), `selfSufficiencyReserve()` (nur Fokus-Profile) | Binäre Sperren für Regolith-Ausgaben. Kein Abwägen. |
| Credit-Bremse | `spendableCredits()`, `creditReserveGuardBlocks()` | Puffer = Schwelle × (1,1 + 0,4 × `savingsAggressiveness`), solange `task_credit_reserve` offen ist. |
| Eröffnung | `pathBuildingOrder()`, `orderPlacementCandidates()` | Feste Reihenfolge der Pfadgebäude je Eröffnung. Bis das eigene Pfadgebäude steht, sind die anderen keine Kandidaten. |
| Sortierung in Regeln | z. B. `costToLevel2()`, `researchOptions()` | Lexikografisch nach Kosten bzw. nach ID. Kein Nutzenbegriff. |
| Profil-Zustand | `static $holdsSelfSufficiencyReserve`, `static $creditSavings` | Wird in `default()` gesetzt. Versteckte Kopplung: Wer `default()` mit einem anderen Profil aufruft, ändert das Verhalten aller Helfer. |

### 1.2 Regel-Inventar und Migrationsklasse

**Klassen:** **P** = Pflicht (bleibt Regel, Hard-Constraint) · **K** = reiner Kandidaten-Erzeuger (leicht migrierbar: `when` liefert schon den Kandidaten, es fehlt nur der Nutzen) · **Z** = Kandidat plus Zustandslogik (Puffer, Deckel, Tiers), die in Nutzen- oder Kostenterme überführt werden muss.

| # | Regel (= Log-Name) | Klasse | Zustandslogik heute → künftig |
|---|---|---|---|
| 1 | `repair_critical` | P | < 30 % SP. Bleibt Regel. |
| 2 | `hire_advisor` | K | `HIRE_ORDER` fest, Pfadgebäude-Gate → Nutzen je Berater |
| 3 | `promote_advisor` | K | billigste zuerst → Nutzen (AP-Strom) |
| 4 | `buy_merchant_item` | K | billigste zuerst, Zeitfenster → Ablauf als Dringlichkeitsterm |
| 5 | `place_agrardom` | P | C16-Gate für CC Lv2. Bleibt Regel. |
| 6 | `feed_colony` | P | Nahrung. Bleibt Regel. |
| 7 | `invest_cc` | Z | Phasen-Deckel (Lv3 in Phase 1, Lv5 in Phase 2) → Freischalt- und Phasenterm |
| 8 | `buy_corporate_harvester_offer` | K | zeitkritisch |
| 9 | `place_harvester_instance2` | K | kostenlos, praktisch immer positiv |
| 10 | `dispatch_salvage_mission` | K | — |
| 11 | `repair_ship_for_flight` | Z | nur sinnvoll als Vorstufe eines Flugs → **Bündel** (3.4) |
| 12 | `dispatch_find_flight` | K + P-Filter | Rückkehr-Garantie und Nahrungsreserve bleiben harte Filter |
| 13 | `dispatch_credit_mission` | Z | Werkstoff-Knappheitsfilter → Knappheitspreis |
| 14 | `dispatch_compounds_mission` | K | — |
| 15–17 | `focus_expedition_mission`, `focus_engineering_levelup`, `focus_trust_building` | K | nur mit Siegziel → Siegziel-Term mit Gewicht `objectiveFocus` |
| 18 | `relocate_harvester` | Z | Schwelle „> 50 Rg Restvorrat“ → Ertragsdifferenz |
| 19 | `explore_tile` | K | Füller |
| 20 | `place_building` | Z | Rg-Puffer, CC-Reserve, Eröffnungs-Ausschluss, Instanz-Deckel, Tiers 0–4 |
| 21 | `invest_production` | Z | Deckel Lv < 2, Puffer, Supply→Wohnhabitat-Umweg, `costToLevel2` |
| 22 | `repair_maintenance` | Z | < 60 % SP, `maintenanceMaySpendRegolith()` |
| 23 | `research_knowledge` | K | niedrigste ID zuerst, CC-Gate-Filter |
| 24 | `dispatch_mission` (Erkundungsflug) | K | — |
| 25–28 | `accept_bar_encounter`, `resolve_bar_concern`, `resolve_information_encounter`, `accept_bar_offer` | K | Credit-Bremse → Credits-Knappheitspreis |
| 29 | `request_ship` | Z | Drohnen-Deckel 2, ≥ 3 Berater, freie Bucht, Credit-Bremse |
| 30 | `deep_scan_signal_tile` | K | Füller |
| 31 | `invest_find` | K | Füller, eine Einzahlung je Kachel und Sol |

Bilanz: 3 × P, 20 × K (inkl. Fokus und `dispatch_find_flight` mit Pflicht-Filter), 8 × Z.

### 1.3 Wo das starre Muster die Befunde erzeugt (botseitig, aus dem Code)

| # | Ursache im Bot | Code-Stelle | Wirkung | Bezug zu T30 §2 |
|---|---|---|---|---|
| B1 | **Ausbau-Deckel Lv2.** `invest_production` baut nur bis Lv2. Darüber baut nur `focus_engineering_levelup`, und das nur in Fokus-Profilen mit offenem Siegziel. | `productionInvestCandidate()` `level < 2` | Im `default`-Profil erreicht kein Pfadgebäude Stufe 3. Hangar Stufe 3 (Korvette, Bucht 3) und Cantina Stufe 3 kommen nie. | U5 |
| B2 | **Tie-Break nach `building_id`.** Alle Lv1→Lv2-Schritte kosten gleich (25 Rg + 10 AP). Die stabile Sortierung fällt auf die Vorsortierung `level DESC, building_id` zurück. Wohnhabitat (28) < Labor (31) < Agrardom (41) < Hangar (44) < Cantina (52). | `productionInvestCandidate()` | **Annahme (zu messen):** Die zwei Phase-1-Ausbauten gehen bei Hangar-First und Cantina-First an Wohnhabitat und Agrardom, nicht an das eigene Pfadgebäude. Das Pfadgebäude bleibt auf Stufe 1. | U5 |
| B3 | **Kein Freischaltwert.** Der Bot weiß nicht, dass Hangar Stufe 2 Frachter, große Funde und Bucht 2 öffnet, Labor Stufe 2 Geologie, Cantina Stufe 2 mehr Angebote. | überall | Gesperrte Funde warten (K16 `needs_level`/`needs_freighter`), ohne dass der Bot den Ausbau als Lösung erkennt. | U2, U5 |
| B4 | **Puffer statt Abwägung.** Drei binäre Regolith-Sperren. Ein Lv2-Ausbau wartet, bis Rg ≥ 120 (nächstes Pfadgebäude), und verliert dann gegen `place_building`, das weiter oben steht. | `cheapestPendingPathBuildingCost()`, `ccResearchReserve()`, `selfSufficiencyReserve()` | Ausbau-Zyklen starten spät. Regolith liegt teilweise still. | — |
| B5 | **Position statt Wert.** Die erste passende Regel feuert, nicht die wertvollste. Erkunden und Missionen stehen vor Bau und Forschung. Füller (Scan, Bergung) stehen ganz hinten. | Regelreihenfolge | Erkunden frisst AP, die ein Ausbau-Zyklus bräuchte. Im Regolith-Warteblock fehlt außerhalb des Labors ein Abnehmer. Gemessen: Hangar-K4 63 (Schritt 3). | U1 |
| B6 | **Forschung nach ID.** Construction (90) vor Geologie (92). Gleiches gilt für `bonusKnowledgeId()`. | `researchOptions()` | Der Regolith-Hebel Geologie kommt spät. | U2 |
| B7 | **Statischer Profil-Zustand.** | `static $holdsSelfSufficiencyReserve`, `$creditSavings` | Schwer testbar. Fehleranfällig, sobald zwei Profile in einem Prozess laufen. | — |

**Folgerung:** B1–B6 sind genau die Stellen, an denen ein Nutzenmodell mehr leistet als eine weitere Regel. B7 entfällt, sobald die Gewichte im Kontext-Objekt stehen.

---

## 2. Nutzenmodell

### 2.1 Einheit und Grundformel

**Einheit:** Regolith-Äquivalent (**Rg-Ä**). 1 Rg = 1 Rg-Ä. Regolith ist der Phase-1-Engpass (GDD, T30 §0), andere Größen werden darauf umgerechnet.

| Symbol | Bedeutung |
|---|---|
| `U(c)` | Bruttonutzen des Kandidaten c in Rg-Ä (Summe der Terme aus 2.3) |
| `p_r` | Preis der Ressource r in Rg-Ä je Einheit (2.4) |
| `Opp(c)` | Opportunitätskosten gegenüber dem aktiven Sparziel (2.6) |
| `Net(c)` | `U(c) − Σ_r p_r · kosten_r(c) − Opp(c)` |
| `score(c)` | `Net(c) / max(1, ap(c))`, also Nutzen je AP |
| `D(H)` | Diskontsumme `Σ_{t=1..H} γ^t = γ(1 − γ^H)/(1 − γ)` |
| `H` | Horizont `min(tick_limit − sol, horizon_max)` |
| Strom | Ein Dauerertrag von x je Sol ab Latenz L ist `x · γ^L · D(H − L)` wert. |

**Warum Nutzen je AP:** AP verfallen am Sol-Ende. Innerhalb eines Sols ist AP die bindende, verderbliche Ressource. Regolith und Credits sind Bestände, die über Preise und Sparziele bewertet werden. AP bekommen deshalb **keinen** Preis im Zähler, sondern stehen im Nenner.

**Zulässigkeit:** Ein Kandidat ist wählbar, wenn er bezahlbar ist (AP, Ressourcen, Supply), nicht in `blockedThisSol` steht, die Pflicht-Filter besteht und `Net(c) > min_net` gilt. Ein Kandidat mit negativem Netto bleibt liegen. Leerlauf ist dann die bessere Wahl. Das ist gewollt und steht im Log.

### 2.2 Zukunftswert

| Term | Formel | Zweck |
|---|---|---|
| Diskont | `γ` je Sol | Frühe Erträge zählen mehr. Gegen Run-Ende schrumpft H. Der Bot investiert dann von selbst weniger, wie ein Mensch im Endspiel. |
| Phasenziel | `w_phase1 · dringlichkeit(sol)` je erfüllter Teilbedingung (CC Lv3, zweiter Ausbau ≥ Lv2, dritter Berater) | Phase-1-Tempo (Ziel Sol 15–20). Dringlichkeit = 1 bis `phase1_urgency_from_sol`, danach +20 % je Sol. |
| Siegziel | `w_objective · Δfortschritt / zielwert` je offenem Siegziel | ersetzt die Fokus-Regeln. `w_objective` = `objectiveFocus` × Basiswert. |
| Freischaltung | Wert der **besten Kandidaten, die erst durch c möglich werden** (Optionswert), gedeckelt auf einen Kandidaten je Klasse | z. B. Hangar Stufe 2: Summe der heute wegen `needs_level`/`needs_freighter` wartenden Funde × `yield_factor`, abzüglich Frachter-Credits. Labor Stufe 2: Geologie-Strom. Wird aus dem Zustand berechnet, nicht als Tabelle geschätzt. |
| Laufender Zyklus | Bereits bezahltes Regolith fällt aus den Kosten | Ein begonnener Ausbau hat von selbst eine höhere Dichte. Die heutige Sonderregel „laufender Zyklus zuerst“ entfällt. |

### 2.3 Bewertungsterme je Kandidatenklasse

| Klasse | Regeln (Log-Name bleibt) | Nutzen `U` | Kosten |
|---|---|---|---|
| Pfadgebäude platzieren | `place_building` (Pfad) | Berater-Slot (AP-Strom des Beraters, 2.5) + Freischaltung Stufe 1 (Pfadaktionen, Kenntnisse) + Phasenziel-Anteil + Eröffnungs-Bias | Rg (Platzierung inkl. 0→1), 1 AP, Supply |
| Ausbau Gebäude | `invest_production`, `focus_engineering_levelup` | Δ Produktion als Strom + Freischaltung der Zielstufe + Phasenziel (nur für die zwei Phase-1-Ausbauten) + Siegziel `task_engineering_output`. **Kein Lv2-Deckel mehr.** | Rg beim ersten Klick, AP des Zyklus (Dichte = Netto / Rest-AP) |
| CC-Ausbau | `invest_cc` | Phasenziel (bis Lv3) + Freischaltung: zweites Pfadgebäude (Regel „CC-Stufe − 1 ≥ Anzahl Pfadgebäude“), Kenntnis-Stufe 4/5 (`knowledge_cc_level_cap`), Zonenkacheln (`task_expedition_coverage`) | CC-Stufe × `cc_upgrade_regolith_per_level`, AP |
| Wohnhabitat | `invest_production` (Housing), `place_building` (Housing) | **Ermöglichungswert:** Anteil am Netto der besten Kandidaten, die nur am Supply scheitern. Sonst nahe 0. | Rg, AP |
| Harvester verlegen | `relocate_harvester` | (Ertrag Zielfeld − Ertrag heute) als Strom, begrenzt durch den Restvorrat | AP je Hex |
| Forschung | `research_knowledge` | Effekt der nächsten Stufe als Strom: Geologie Rg (`geology_harvester_bonus_per_level`), Construction Bau-AP-Rabatt × erwartete Bau-AP im Horizont × λ_AP, Kartografie Nav-AP-Rabatt, Agronomie Organika + Vertrauen, Health/Defense Vertrauen, Trade Angebote. Dazu Supply-Bonus je Stufe als Ermöglichungswert. Dichte = Netto / AP der Stufe. | AP |
| Berater einstellen/befördern | `hire_advisor`, `promote_advisor` | AP-Strom (`ap_per_rank`) × λ_AP + Phasenziel (3 Berater) + Pfadaktion-Freischaltung | Credits, Unterhalt als Strom (`advisor.upkeep`) |
| Erkunden | `explore_tile` | Informationswert: erwarteter Fundwert je Kachel (nur aus spielersichtbarer Info, 2.7) + Zonenkachel-Wert, wenn freie Bauplätze knapp sind + Siegziel `task_expedition_coverage` | Nav-AP je Ring (`explore_cost_per_ring`, Kartografie-Rabatt) |
| Tiefenscan | `deep_scan_signal_tile` | Erwartungswert des Signals (Fundtyp-Verteilung) × pfadabhängiger Bergungsweg (Flug-Faktor oder Selbstbergung) − künftige Bergungskosten | `scan_ap` |
| Bergung | `invest_find`, `dispatch_find_flight` (+ `repair_ship_for_flight` als Bündel) | Selbstbergung: `rg` des Fundtyps; Flug: `rg × yield_factor` | AP bzw. Nav-AP + Organika (+ Reparatur-AP) |
| Missionen | `dispatch_mission`, `dispatch_credit_mission`, `dispatch_compounds_mission`, `dispatch_salvage_mission`, `focus_expedition_mission` | Erfolgschance × Belohnung (Credits × p_Cr, Werkstoffe × p_Wk) + Siegziel | Nav-AP, Organika, Schiff-Belegung (Opportunität: bester alternativer Flug dieses Schiffs) |
| Schiffskauf | `request_ship` | Wert der Flüge, die das Schiff im Horizont ermöglicht (Optionswert, wie Freischaltung) | Credits |
| Cantina | `accept_bar_offer`, `accept_bar_encounter`, `resolve_bar_concern`, `resolve_information_encounter` | Erhalt − Abgabe in Rg-Ä, Erwartungswert bei Zufall, Vertrauensänderung × p_Trust, Bonus-AP-Kenntnis wie Forschung | AP, Abgabe |
| Händler, Konzernangebot, Harvester 2 | `buy_merchant_item`, `buy_corporate_harvester_offer`, `place_harvester_instance2` | Gegenstandswert bzw. Ertragsstrom des zweiten Harvesters; Ablauf-Dringlichkeit | Credits |
| Wartungsreparatur | `repair_maintenance` | erwarteter vermiedener Stufenverlust: P(Stufenverlust im Horizont) × Wert der Stufe | AP, Rg je Klick |

### 2.4 Preise und Knappheit

`p_r = basis_r · knappheit_r`, `knappheit_r = clamp(bedarf_r / max(1, bestand_r), 0,5, 4)`.

| Ressource | `basis` (Default, **Annahme**) | Bedarf (für Knappheit) | Begründung |
|---|---|---|---|
| Regolith | 1 (Definition) | — | Einheit. Knappheit läuft über Sparziele (2.6), nicht über den Preis. |
| Credits | 0,04 | Unterhalt × 10 Sole + offene Kaufziele; bei offenem `task_credit_reserve` + Reserve-Puffer | In Phase 1 sind Credits laut T30 §1.7 nicht knapp. Grob 25 Cr ≈ 1 Rg. Kalibrierung in Schritt S7. |
| Organika | 1,0 | `foodNeed` × 3 Sole | Hunger kostet Vertrauen. Pflicht `feed_colony` bleibt zusätzlich. |
| Werkstoffe | 3,0 | Werkstoffkosten offener Vertrauensgebäude | knappste Ressource (GDD §3, Knappheitsordnung) |
| Vertrauen | 2,0 je Punkt | Abstand zu `trust_fail_threshold` (Faktor 4 in Fail-Nähe) | Produktionsmultiplikator und Fail-Grenze |
| Supply | kein Preis | — | Ermöglichungswert beim Wohnhabitat (2.3). Kandidaten über dem Cap sind unzulässig. |
| AP | im Nenner | — | verderblich (2.1) |

**λ_AP** (Wert einer AP für Ströme wie „+2 AP je Sol“): gleitender Mittelwert (EMA, Faktor 0,3) der **Grenzdichte**, also des `score` der letzten ausgeführten Aktion je Sol. Ein Sol mit ungenutzten AP zählt als 0. Startwert 0,5. **Annahme:** So wird der AP-Wert aus dem eigenen Spielverlauf gemessen statt geraten. Im AP-Überschuss (Warteblock) sinkt er, in AP-knappen Phasen steigt er.

### 2.5 Ströme: Handrechnungen (**Annahme**, γ = 0,95, H = 40 → D ≈ 16,6)

| Kandidat | Rechnung | Netto | Dichte (Rg-Ä/AP) |
|---|---|---|---|
| Geologie Stufe 1 (+3 Rg/Sol) | 3 × 16,6 | ≈ 50 | 50 / 20 AP ≈ **2,5** |
| Bergungsflug kleiner Fund | 4 × 1,5 = 6 Rg − 3 Or × 1,0 | ≈ 3 | 3 / 2 Nav-AP ≈ **1,5** |
| Selbstbergung groß / mittel / klein | 18 / 10 / 4 Rg | 18 / 10 / 4 | 18/22 ≈ **0,8** · 10/16 ≈ **0,6** · 4/12 ≈ **0,3** |
| Analytiker einstellen (Phase 1) | 2 AP × λ 0,5 × 16,6 ≈ 17; − 400 Cr × 0,04 = 16; − Unterhalt 10 × 0,04 × 16,6 ≈ 7; + Phasenziel 40 | ≈ 34 | 0 AP → Nenner 1 → **34** |
| Analytiker ohne Phasenziel | wie oben ohne + 40 | ≈ −6 | unzulässig |

**Lesart:** Ohne den Phasenziel-Term würde der Bot in Phase 1 keinen Berater einstellen. Der Term ist also tragend und muss in S3 kalibriert werden. Geologie schlägt jede Selbstbergung um den Faktor 3. Das passt zum Befund, dass Labor-First im Warteblock keinen Leerlauf hat (T30 §1.6).

### 2.6 Sparziele als Opportunitätskosten (ersetzt die drei Puffer)

1. **Ziel bestimmen:** Unter allen Kandidaten, die **nur** an Regolith scheitern und `Net > 0` haben, ist das Sparziel G der mit dem höchsten `Net/Rg`. Kandidaten aus G's Kette (z. B. ein Wohnhabitat, das G vom Supply-Block befreit) gehören dazu.
2. **Kosten für alle anderen:** Ein Kandidat c, der x Rg ausgibt, verzögert G um `Δt = x / einkommen_rg` Sole (Einkommen = Mittel der letzten 3 Sole, mindestens Harvester-Ertrag). Dann gilt `Opp(c) = Net(G) · (1 − γ^Δt)`.
3. **Ausnahmen:** `Opp = 0` für G selbst, für G's Kette, für Kandidaten ohne Rg-Kosten und für Pflicht-Regeln.

Damit verschwinden `cheapestPendingPathBuildingCost()`, `ccResearchReserve()` und `maintenanceMaySpendRegolith()` als Sonderlogik. Der Fall T17 (CC-Stufe sperrt Forschung) entsteht von selbst: Der CC-Ausbau wird G, weil seine Freischaltung (Kenntnis-Stufe 4/5) den höchsten Wert je Rg hat. `selfSufficiencyReserve()` wird zum Siegziel-Term (Regolith-Bestand über `regolith_min` hat Wert, solange das Ziel offen ist).

### 2.7 Risiko, Unsicherheit, Informationsregel

| Punkt | Regel v1 |
|---|---|
| Zufall | `U = Erfolgschance × U_erfolg` (Missionen, Wetten, Verhandlung). Erfolgschance aus denselben Service-Methoden wie die UI (`HangarService::successChanceFor()` u. a.). |
| Risikoscheu | Gewicht `risk_aversion` (Default 0) für `− ρ · Streuung`. In v1 nur vorbereitet. |
| Information | **Annahme (Owner-Frage 2):** Der Bot nutzt nur, was der Spieler sieht. Fundtyp erst nach Scan. Davor Erwartungswert aus der Pool-Zusammensetzung (`finds.start_pool`), soweit die UI sie zeigt. |

### 2.8 Pflicht-Schicht (bleibt Regel, läuft vor dem Selektor)

| Pflicht | Regel | Grund |
|---|---|---|
| Notreparatur | `repair_critical` (< 30 % SP) | Stufenverlust droht |
| Agrardom-Gate | `place_agrardom` | C16: CC Lv2 erst nach Agrardom |
| Nahrung | `feed_colony` | Hunger-Streak und Vertrauensmalus |
| Rückkehr-Garantie | Filter in Flug-/Missionskandidaten (`HangarBays::flightReadiness`) | Server-Guard, kein Abwägen |
| Nahrungsreserve beim Flug | Filter `organics − kosten ≥ foodNeed` | wie heute |
| Eröffnung | Ausschluss fremder Pfadgebäude, bis das eigene steht | Vergleichbarkeit K1–K7, Owner-Entscheid 2026-10-05 Nr. 5 (Owner-Frage 1) |

### 2.9 Gewichte als Profil-Config

**Ort (Vorschlag):** `tests/Feature/Playtest/Utility/weights.php`. Die Datei liefert einen Basissatz und Overrides je Profil. Sie ist Test-Harness, kein Spielwert, und gehört deshalb nicht nach `config/`. `BotProfile::weights()` mischt Basis, Profil und Eröffnung. Die bestehenden Regler bleiben und wirken über Gewichte.

| Schlüssel | Default | Begründung (**Annahme**, Kalibrierung in der Messung) |
|---|---|---|
| `gamma` | 0,95 | Halbwertszeit ≈ 13,5 Sole, etwa eine Phase-1-Länge |
| `horizon_max` | 40 | verhindert, dass ferne Ströme alles überstrahlen |
| `lambda_ap_start` / `lambda_ap_ema` | 0,5 / 0,3 | Startwert, danach gemessen (2.4) |
| `price.credits` / `organics` / `compounds` / `trust` | 0,04 / 1,0 / 3,0 / 2,0 | 2.4 |
| `scarcity_clamp` | [0,5; 4] | begrenzt Ausreißer |
| `w_phase1` | 40 | ≈ 1,5 Harvester-Sole (y2: 26 Rg). Schlägt Füller, aber nie Pflicht. |
| `phase1_urgency_from_sol` | 15 | Untergrenze des Phase-1-Korridors 15–20 |
| `w_objective` | 0 (`default`, `thrifty`) / 150 (`focus`, `eager`) | = `objectiveFocus` × 150 |
| `credit_reserve_factor` | 1,1 + 0,4 × `savingsAggressiveness` | übernimmt `creditReserveBuffer()` als Bedarf im Credits-Knappheitspreis |
| `opening_bias` | 30 bis Sol 15 | auf Platzierung **und** Ausbau des eigenen Pfadgebäudes sowie seine Pfadaktionen. Bei `auto` 0. |
| `info_zone_tile` | 8 | Wert einer neuen Zonenkachel, wenn weniger als 2 freie Bauplätze da sind |
| `min_net` | 0,01 | trennt „lohnt“ von „lohnt nicht“ |
| `risk_aversion` | 0 | v1 aus |

| Profil | Overrides |
|---|---|
| `default` | keine |
| `thrifty` | `savingsAggressiveness` 1 → Credits-Bedarf höher, `price.credits` × 2 |
| `focus` | wie `thrifty` + `w_objective` 150 |
| `eager` | `w_objective` 150, Credits wie `default` |

### 2.10 Determinismus und Erklärbarkeit

| Punkt | Regel |
|---|---|
| Kein Zufall im Bot | keine RNG-Aufrufe im Bot. Alle SQL-Abfragen mit vollständigem `ORDER BY` (Lehre R5b). |
| Ganzzahlige Scores | `score` und `Net` auf Milli-Rg-Ä runden (`(int) round(x * 1000)`), bevor verglichen wird |
| Tie-Break | 1. `score` absteigend, 2. Gruppenrang (Pflicht vor Rest, danach alter Regelindex), 3. Name, 4. Kandidatenschlüssel (z. B. `invest:44:1`, `explore:2:-1`) |
| Log je Entscheidung | `{sol, quelle: pflicht\|utility\|legacy, gewählt: {name, key, score, net, terme}, alternativen: Top-3 mit score, kandidaten_n}` in `BotSession::$decisions` |
| Report | `RunReport::build()` bekommt `decisions` (kompakt) und `utility_summary` (2.11). Die Log-Namen der Aktionen bleiben gleich. `AP_CATEGORY_MAP`, `PATH_RULES` und `BUILD_RULES` funktionieren ohne Änderung weiter. |

### 2.11 Neue Kennzahlen (informativ)

| ID | Kennzahl | Quelle |
|---|---|---|
| K18 | Anteil Legacy-Entscheidungen (Fallback über Adapter) je Lauf | `decisions[].quelle` |
| K19 | Stufen-Sole je Pfadgebäude: Sole auf Stufe 1, Sol von Stufe 2, Sol von Stufe 3 | `sols[].buildings` |
| K20 | Entscheidungen je Sol (Median) und Anteil Sole mit mindestens einem verworfenen Kandidaten mit `Net > 0` (= AP-Mangel statt Leerlauf) | `decisions` |
| K21 | Grenzdichte λ_AP im Verlauf (Sol 4–12, 13–25) | `decisions` |
| K22 | Abweichungsquote im Schattenmodus: Anteil Entscheidungen, bei denen eine bandtreue Utility (wie S3: Band zuerst, Score nur innerhalb eines Bands) anders gewählt hätte als Legacy. Bandübergreifende Wahl nur informativ (`shadow_free_divergence`) | nur Schritt S2 |

---

## 3. Architektur

### 3.1 Bausteine

| Baustein | Art | Inhalt |
|---|---|---|
| `Candidate` | Value Object (readonly) | `name` (= Log-Name), `key` (stabil), `group`, `apCost`, `costs` (`[resource_id => menge]`), `utility`, `terms` (Aufschlüsselung), `do` (Closure), `bundleOf` (optional) |
| `CandidateSource` | Interface | `name(): string`, `upperBound(BotContext): ?float`, `candidates(BotSession, BotContext): list<Candidate>` |
| `BotContext` | je Entscheidung | Sol, Phase, Bestände, Preise, λ_AP, Sparziel, Gewichte, Pflicht-Filter. Gecacht über `BotSession::remember()` (Log-Zähler als Invalidierung). |
| `UtilitySelector` | rein, ohne DB | `select(list<Candidate>, BotContext, blocked): ?Decision` mit Zulässigkeit, Score, Tie-Break, Top-3 |
| `GoalProvider` | Interface | `goals(BotContext): list<SavingsGoal>`. v1: `HeuristicGoalProvider` (2.6). Hier dockt später Option B an. |
| `LegacyRuleSource` | Adapter | eine Quelle je alter Regel (Schritt S0) |
| `UtilityStrategy::rules(BotProfile)` | Fabrik | liefert eine **Regelliste im alten Format**: Pflicht-Regeln + eine Sammelregel `utility` |

Schnittstelle (Skizze, Englisch wie im Code):

```php
interface CandidateSource {
    public function name(): string;
    /** Highest score this source can reach now, or null if unknown (no pruning). */
    public function upperBound(BotContext $ctx): ?float;
    /** @return list<Candidate> */
    public function candidates(BotSession $b, BotContext $ctx): array;
}
```

### 3.2 Selektor: gierig nach Dichte, mit Neuberechnung

- Je Aktion: alle Quellen fragen, zulässige Kandidaten bewerten, den mit dem höchsten `score` ausführen, danach den Zustand neu lesen. **Kein Rucksack je Sol in v1.** Gründe: Der Zustand ändert sich nach jeder Aktion (Kosten, Freischaltungen). Die Kandidaten sind klein gegenüber dem AP-Budget. Mehrschritt-Fälle lösen Bündel und Sparziele.
- **Frühabbruch (Laufzeit):** Quellen werden absteigend nach `upperBound` gefragt. Ist der beste gefundene Score größer als die höchste verbleibende Schranke, endet die Suche. Für den Legacy-Adapter ist die Schranke der Prioritätsrang. Dann werden **genau dieselben `when`-Aufrufe wie heute** ausgeführt, inklusive `peek()`. Das ist für den Golden-Test wichtig.

### 3.3 Verhältnis zu `PlaysSolLoop`

- `PlaysSolLoop` bleibt. Die Utility-Strategie liefert eine Regelliste im alten Format: `[Pflicht-Regeln …, utility]`. `when` der Sammelregel gibt die `Decision` zurück, `do` führt `chosen->do` aus.
- **Einzige Änderung im Loop:** Beim Fehlschlag wird der **Name des letzten neuen Log-Eintrags** gesperrt, nicht der Regelname. Sonst würde ein abgelehnter Kandidat die ganze Sammelregel für den Sol sperren. Der Loop legt die Sperrliste zusätzlich als `$bot->blockedThisSol` ab, damit der Selektor sie filtern kann. Für die alte Regelliste ist beides identisch, weil jede Regel genau unter ihrem eigenen Namen loggt.
- `MAX_ACTIONS_PER_SOL` und `NO_OP_REPEAT_LIMIT` bleiben unverändert.

### 3.4 Mehrschritt-Abhängigkeiten

| Mechanismus | Wann | Beispiel |
|---|---|---|
| Bündel | Vorstufe ohne eigenen Wert, Folgeschritt im selben Sol möglich | `repair_ship_for_flight` + `dispatch_find_flight`: ein Kandidat mit Summe der AP. `do` führt nur die Reparatur aus, die nächste Entscheidung bewertet neu. |
| Laufende Zyklen | mehrere Sole AP in einen Ausbau | bezahltes Rg fällt aus den Kosten (2.2) |
| Sparziel | Regolith über mehrere Sole ansparen | 2.6 |
| Freischaltung | Schritt öffnet neue Kandidaten | Optionswert aus dem Zustand (2.2) |

### 3.5 Platz für Option B (Planer)

Der Planer ersetzt später nur den `GoalProvider`. Er liefert Ziele mit Abhängigkeiten (z. B. „Hangar Stufe 2 → Frachter → großer Fund“) als Sparziele und Gewichts-Multiplikatoren. Selektor, Quellen und Report bleiben gleich.

---

## 4. Migrationsplan

Jeder Schritt ist einzeln messbar. Bis zur Abnahme (S9) bleiben beide Engines wählbar: `BotProfile::$engine` (`legacy` | `utility`), CLI `game:playtest --engine=` (Owner-Frage 3).

| Schritt | Inhalt | Messung | Aufwand |
|---|---|---|---|
| **S0** | Selektor, `Candidate`, Loop-Änderung (3.3), `LegacyRuleSource`: jede alte Regel als Quelle, `utility` = (N − Regelindex) × 10⁶ | **Golden-Test:** gleiche Seeds, gleiche Aktionsfolge wie Legacy | M |
| **S1** | Entscheidungslog, Report-Felder, K18–K21 in `OpeningComparison` (informativ), `--engine` in `game:playtest` und `game:playtest-compare` | Golden-Test bleibt grün | K–M |
| **S2** | `BotContext`, Preise, Diskont, Freischalt- und Phasenterme, `weights.php`. **Schattenmodus:** Utility bewertet die Legacy-Kandidaten mit, entscheidet aber nicht. | K22 Abweichungsquote, Stichprobe der Top-3 je Sol 4–15 lesen | M |
| **S3** | Bau und Ausbau: `place_building`, `invest_production`, `focus_engineering_levelup`, Wohnhabitat-Ermöglichungswert; Lv2-Deckel und ID-Tie-Break entfallen (B1, B2, B3) | 24 Läufe bis Sol 25 + K19 | G |
| **S4** | Sparziel und CC: `HeuristicGoalProvider`, `invest_cc`, Wartungsreparatur; die drei Puffer entfallen im Utility-Pfad (B4) | 24 Läufe bis Sol 25 | M |
| **S5** | Forschung: `research_knowledge`, `bonusKnowledgeId` (B6) | 24 Läufe bis Sol 25 | K–M |
| **S6** | AP-Füller: Erkunden, Scan, Selbstbergung, Flug-Bündel, Erkundungs-/Credit-/Werkstoff-/Bergungsmissionen, Fokus-Expedition (B5) | 24 Läufe bis Sol 25 (K4/K5) | M–G |
| **S7** | Personal und Credits: Berater, Beförderung, Schiffskauf, Händler, Konzernangebot, Harvester 2, Cantina; Credit-Bremse wird Knappheitspreis (B7 entfällt) | 24 volle Läufe (K7) | M |
| **S8** | Aufräumen: Legacy-Anteil (K18) nahe 0, nur Pflicht-Schicht bleibt Regel | — | K |
| **S9** | Neue Baseline: beide Engines, gleiche Seeds, volle Läufe; Abnahme (5.3) | 5 | K |

**Reihenfolge nach Hebel:** S3 → S4 → S5 greifen die Ursachen der Stufe-1-Stagnation und die Puffer an. S6 adressiert den AP-Leerlauf. S7 ist für Phase 1 am wenigsten kritisch.

**Teststrategie (TDD, Rot zuerst):**

| Ebene | Tests |
|---|---|
| Selektor (Unit, rein) | Eigenschaften: wählt das Maximum; Ergebnis unabhängig von der Reihenfolge der Eingabe; stabiler Tie-Break; nie unbezahlbar, nie gesperrt, nie `Net ≤ min_net`; positive Skalierung aller Nutzen ändert die Wahl nicht; leere Liste → null; Frühabbruch liefert dasselbe Ergebnis wie die volle Suche. |
| Golden (Feature, Playtest-Suite) | Legacy und Adapter auf frischen Bots mit gleichem Seed: Folge `(sol, name, url, ok)` identisch (IDs in URLs normalisiert). Seeds 4242 und 7 × Eröffnungen `labor`, `hangar`, bis Sol 12. Beide Läufe im selben Test, keine Fixture-Datei, die driften kann. |
| Quellen (Feature) | je migrierter Klasse Zustand-Fixture im Muster der vorhandenen `BotStrategy*Test` und **relative** Erwartungen. Beispiel: „Hangar Stufe 2 mit wartendem großem Fund liegt vor Wohnhabitat Stufe 2.“ Keine festen Score-Zahlen, damit Gewichts-Tuning keine Tests bricht. |
| Invarianten (beide Engines) | Datenprovider `engine ∈ {legacy, utility}`: kein Hunger-Streak über X, Agrardom vor CC Lv2, keine `insufficient_*`-Ablehnungsschleifen, K16 `no_ship_ready` ≤ 3. Diese Tests werden aus bestehenden Tests herausgelöst. |
| Bestand | `BotStrategy::default()` bleibt bis S8 unverändert. Die rund 173 Playtest-Tests laufen weiter gegen Legacy. Tests, die bewusst geändertes Verhalten festschreiben (z. B. Lv2-Deckel), werden erst in S8 umgeschrieben oder gelöscht. |

---

## 5. Messplan

### 5.1 Grundsatz

Die Werte des Utility-Bots sind mit der alten Baseline **nicht direkt vergleichbar**. Deshalb laufen in S9 beide Engines auf denselben Seeds. Bewertet werden die gepaarten Differenzen.

### 5.2 Läufe

| Messung | Läufe | Länge |
|---|---|---|
| Zwischenschritte S3–S6 | `default` × `labor\|hangar\|cantina` × 8 Seeds = 24 (Seeds wie Schritt-3-Messung, ergänzt auf 8) | `--until-sol=25` |
| S7, S9 | dieselben 24, plus gleiche 24 mit `--engine=legacy` in S9 | volle Länge (K7) |
| Fokus-Profile | `focus` × `auto` × 8 nur in S9 | volle Länge |

Die Dauer wird vor jedem Batch geschätzt, der Owner wählt 8/16/24 Läufe.

### 5.3 Abnahmekriterien (Vorschlag)

| Kennzahl | Kriterium |
|---|---|
| K3 | je Eröffnung Median im Korridor 15–20 und nicht später als Legacy + 1 Sol |
| K4 | je Eröffnung ≤ Legacy; Hangar − Labor ≤ 20 AP |
| K5 | ≤ 2 je Eröffnung |
| K19 | eigenes Pfadgebäude Lv2 ≤ Sol 9 (Median, Warnung > 12) bei allen Eröffnungen; Lv3 ≤ Sol 30 |
| K23 | CC Lv4 ≤ Sol 25 (Median, Warnung > 30) |
| K16 | `no_ship_ready` ≤ 3 Sole je Lauf |
| K18 | ≤ 5 % nach S8 (nur Pflicht-Schicht) |
| Ablehnungen | je Lauf ≤ Legacy |
| Laufzeit | ≤ 1,3 × Legacy je Lauf, kein Lauf über dem Prozess-Timeout (400 s) |
| K7 | **nur beobachten.** Kein Tuning-Ziel. Ein Abfall um mehr als 15 Punkte gegenüber Legacy deutet auf einen Bot-Fehler. Ein Anstieg ist eine Owner-Frage (4). |

---

## 6. Risiken und Abgrenzung

| # | Risiko | Gegenmaßnahme |
|---|---|---|
| R1 | **Tuning-Aufwand:** Viele Gewichte, gegenseitige Abhängigkeiten | Schattenmodus (S2) vor dem Umschalten; je Schritt nur eine Klasse; relative statt absolute Tests; Gewichte zentral in einer Datei |
| R2 | **Instabilität:** knappe Scores kippen hin und her | ganzzahlige Scores, fester Tie-Break; K20 zeigt den Abstand zwischen Platz 1 und 2 |
| R3 | **Reward-Hacking:** AP-Verschwendung für Kleinstnutzen, Pendeln zwischen zwei Kandidaten, Bergung leerer Funde | `min_net`; nur spielersichtbare Info; No-Op-Wächter bleibt; Erklär-Log macht es sichtbar |
| R4 | **Laufzeit:** alle Quellen je Aktion, `peek()`-Aufrufe | Frühabbruch über `upperBound`, `remember()`-Cache, `BotContext` einmal je Aktion; Laufzeit-Kriterium 5.3 |
| R5 | **Interpretierbarkeit:** „warum hat der Bot das getan?“ | Top-3 mit Termen im Report; Kennzahlen K18–K22 |
| R6 | **Phasenterm dominiert** oder fehlt (2.5: Berater sonst negativ) | in S3 als erstes kalibrieren; Abweichungsquote K22 |
| R7 | **Golden-Test ist nicht deterministisch** (z. B. GET mit Seiteneffekt wie das Konzernangebot) | Frühabbruch erhält die Aufrufreihenfolge; schlägt der Test fehl, zuerst `peek`-Seiteneffekte prüfen |
| R8 | **Doppelte Pflege** Legacy und Utility | befristet bis S9; neue Spielmechaniken in der Zeit nur als Quelle + Legacy-Regel |

**Nicht in Version 1:** Planer (Option B), Lernen oder automatisches Gewichts-Tuning, Vorausschau oder Simulation mehrerer Sole, Rucksack-Optimierung je Sol, zufällige Entscheidungen, Risikoscheu > 0, Pflicht-Regeln als Nutzen.

---

## 7. Plan-Tasks

| # | Task | Dateien (Hauptstellen) | TDD-Ansatz (Rot zuerst) | Aufwand |
|---|---|---|---|---|
| P0 | Abgleich mit T30 §14 (Branch `feat/t30-schritt4-spec`): U1–U6 dort gegen B1–B7 hier, K19-Zielwert übernehmen | diese Spec | Doku | K |
| P1 | Loop sperrt den Namen des letzten Log-Eintrags, `$bot->blockedThisSol` | `PlaysSolLoop.php`, `BotSession.php` | Test: Sammelregel mit zwei Kandidaten; der erste wird abgelehnt, der zweite feuert im selben Sol; alte Regeln unverändert | K |
| P2 | `Candidate`, `Decision`, `UtilitySelector` | `tests/Feature/Playtest/Utility/*`, `tests/Unit/Playtest/UtilitySelectorTest.php` | Eigenschaftstests aus Abschnitt 4 | K–M |
| P3 | `LegacyRuleSource`, `UtilityStrategy::rules()`, `BotProfile::$engine`, `--engine` | `Utility/*`, `BotProfile.php`, `PlaytestBotTest.php`, `app/Console/Commands/Playtest.php` | Golden-Test (Abschnitt 4); `PlaytestCommandOpeningsTest` um `PLAYTEST_ENGINE` erweitern | M |
| P4 | Entscheidungslog, `RunReport` (`decisions`, `utility_summary`, `engine`), K18–K21, `playtest-compare --engine` | `BotSession.php`, `RunReport.php`, `app/Support/OpeningComparison.php`, `PlaytestCompare.php` | `RunReport*Test`, `OpeningComparisonTest`: Felder vorhanden, alte Reports ohne `engine` = `legacy` | M |
| P5 | `BotContext`, Preise, Diskont, λ_AP, Freischalt- und Phasenterme, `weights.php`, Schattenmodus, K22 | `Utility/*`, `BotProfile.php` | Unit: Diskontsumme, Knappheits-Clamp, λ-EMA; Feature: Freischaltwert Hangar Stufe 2 steigt mit wartendem großem Fund | M |
| P6 | Quelle Bau/Ausbau (S3) | `Utility/Sources/BuildSource.php` | Relativtests: eigenes Pfadgebäude Stufe 2 vor Wohnhabitat bei Bias; Ausbau über Lv2 möglich; Supply-Block hebt Wohnhabitat; laufender Zyklus vor neuem | G |
| P7 | Sparziel und CC (S4) | `Utility/HeuristicGoalProvider.php`, `Sources/CcSource.php`, `Sources/RepairSource.php` | Opp = 0 für das Ziel; billiger Ausbau, der das Ziel um > 1 Sol verzögert, fällt unter das Ziel; T17-Fall ergibt CC als Ziel | M |
| P8 | Quelle Forschung (S5) | `Sources/ResearchSource.php` | Geologie vor Construction, wenn der Harvester steht; CC-Gate-Filter; `levelup` nur, wenn bereit | K–M |
| P9 | Quelle Navigation (S6) | `Sources/NavSource.php` | Flug-Bündel; Scan vor Flug; kleine Selbstbergung unter Erkunden bei gleichem AP-Rest; Rückkehr-Garantie als Filter | M–G |
| P10 | Quelle Personal und Wirtschaft (S7) | `Sources/StaffSource.php`, `Sources/TradeSource.php` | Berater in Phase 1 positiv nur mit Phasenterm; Credit-Reserve über Preis; kein Schiffskauf ohne freie Bucht | M |
| P11 | Aufräumen und Baseline (S8/S9), Spec um Messergebnis ergänzen | Legacy-Tests umschreiben | Invarianten-Tests für beide Engines | K–M |

Reihenfolge: P0 → P1 → P2 → P3 → P4 → P5 → P6 → P7 → P8 → P9 → P10 → P11. Nach P6 bis P10 jeweils Messung (5.2).

---

## 8. Offene Owner-Fragen (max. 4, mit Vorschlag)

1. **Feste Reihenfolge des zweiten Pfadgebäudes** (Owner 2026-10-05 Nr. 5) im Utility-Bot behalten? **Vorschlag:** ja in v1 (Vergleichbarkeit K1–K7). Danach als Profil-Schalter freigeben, damit der Bot selbst wählt.
2. **Nur spielersichtbare Information** für den Bot (Fundtyp erst nach Scan)? **Vorschlag:** ja. Der Bot misst dann das Spiel, das Menschen spielen.
3. **Zwei Engines parallel** (`--engine=legacy|utility`) bis zur Abnahme, mit befristeter Doppelpflege? **Vorschlag:** ja. Ohne Parallelbetrieb gibt es keine gepaarte Baseline.
4. **Siegquote:** Ein klügerer Bot gewinnt vermutlich öfter als ~50 %. Bleibt das unkommentiert, oder wird `default` bewusst „mittelmäßig“ gehalten? **Vorschlag:** nicht schwächen. Siegquote nur beobachten und die Spielbalance gegen menschliche Playtests (Ziel 70–80 %) bewerten.

## Rulings 2026-10-12 (Controller, nach Designer-Vorschlägen; Owner hat Option A mit „umsetzen" freigegeben und kann widerrufen)

1. v1 behält die feste Reihenfolge fürs zweite Pfadgebäude (Vergleichbarkeit); später Profil-Schalter.
2. Der Bot nutzt nur spielersichtbare Information (Fundtyp erst nach dem Scan).
3. Beide Engines laufen bis zur Abnahme parallel (`--engine=legacy|utility`, befristet doppelte Pflege) — nötig für gepaarte Baseline.
4. Ein klügerer Bot darf öfter gewinnen; `default` wird nicht bewusst geschwächt, nur beobachtet (Bot-Quote ist kein Tuning-Ziel).
5. Abgleich mit T30-Spec §14 (Branch `feat/t30-schritt4-spec`, U1–U6 vs. B1–B7) als Task P0.

## 9. Abgleich mit T30-Spec §14 (P0)

| T30 §14 | Utility-Spec | Umsetzung |
|---|---|---|
| U1 Deckel Stufe 2 | B1 | S3 `UpgradeSource`: alle Stufen bis `max_level`, Stufenterm `w_tier` (Owner-Vorgabe 2026-10-12 „Stufe 2 und 3 bauen wollen“) |
| U2 Pfad-Puffer | B4 | S3 Sparziel (`HeuristicGoalProvider`) + Opportunitätskosten statt Puffer im Ausbau; Legacy-`place_building` behält seinen Puffer bis S4 |
| U3 Sortierung nur aufs Phase-1-Ziel | B2, B3 | S3 Bewertung: Phasen-, Stufen-, Freischalt-, Eröffnungsterm |
| U4 CC-Ausbau kurzsichtig | B4 | S3 `CcSource` bietet nur Bezahlbares an; unbezahlbarer CC-Ausbau wird Sparziel, Legacy-Regolith-Ausgaben halten es über `BotSession::$regolithHold` |
| U5 Frachter erst ab 3 Beratern | – (neu) | S3 `FreighterSource`: Frachter, wenn ein gescannter großer Fund auf `needs_freighter` wartet |
| U6 Forschung nach ID | B6 | S3 `ResearchSource`: Effekt je AP, Geologie vor Bauwesen |
| – | B5, B7 | Folgeplan S6/S7 |

**Kennzahlen:** T30 §14 K18 (Stufen-Sol je Pfadgebäude) heißt hier **K19**; T30 §14 K19 (Sol CC Lv4) heißt hier **K23** (neu). Zielwerte aus §14 übernommen: K19 eigenes Pfadgebäude Lv2 ≤ Sol 9 (Warnung > Sol 12), Lv3 ≤ Sol 30; K23 ≤ Sol 25 (Warnung > Sol 30). K20 enthält in v1 nur „Entscheidungen je Sol (Median)“; der Anteil verworfener positiver Kandidaten folgt mit S6.

**Phase-2-Sparziel (§14.4):** Das Utility-Sparziel wählt nach Net/Rg statt nach fester Liste. Die Gewichte (`w_tier`, `structural_offset`) sind so gesetzt, dass CC Lv4 in Phase 2 in der Regel vor Pfadgebäude Lv3 liegt (Annahme, Messung S3). Die 5-Sol-Erreichbarkeit aus §14.4 gilt (`goal_max_wait_sols`). `pathTierPolicy`/`hangarLv2Trigger` (§14.4) werden nicht als Schalter umgesetzt, sondern über Gewichte. Verlade-AP (§14.5) ist eine Spielregel und nicht Teil dieses Vorhabens.

**Präzisierungen für die Umsetzung (Plan 2026-10-12):**
- Statt `(N − Regelindex) × 10⁶` und `upperBound()` ordnet ein **Band** (= alter Regelindex) die Kandidaten; innerhalb eines Bands entscheidet der Score. Bis S8 bleiben auch die Pflicht-Regeln als Legacy-Quellen in ihren Bändern im Sammler (sonst wäre die Golden-Gleichheit unmöglich).
- Der Loop sperrt `res['block']` ?? Name des letzten neuen Log-Eintrags ?? Regelname und fragt eine Regel erneut, wenn nicht ihr eigener Name gesperrt wurde.
- Strukturschritte (CC-Stufe, Pfadgebäude platzieren) sind in v1 kostenneutral bewertet: ihr Rg-Preis wird als gleich hoher Strukturwert gegengerechnet (`structural_offset` = 1,0, Annahme).
- Neue Gewichte: `w_tier` 30, `structural_offset` 1,0, `objective_base` 150, `research_floor` 1,0, `build_ap_per_sol` 10, `nav_ap_per_sol` 4, `income_floor_rg` 8, `goal_max_wait_sols` 5, `trust_danger_margin` 10, `trust_danger_factor` 4. Profil-Gewichte werden aus den Reglern abgeleitet (`w_objective` = `objectiveFocus` × 150, `price.credits` × (1 + `savingsAggressiveness`)).
- Engine `shadow` ist dritter Wert von `--engine`. Reports anderer Engines heißen `{profil}.{engine}-{eröffnung}-{seed}-{zeit}.json`.
- Plan-S3 bündelt Spec-S3 (nur Ausbau), Spec-S4 (Sparziel/CC) und Spec-S5 (Forschung) sowie die Frachter-Ausnahme aus Spec-S7.
