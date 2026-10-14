# Regolith-Wirtschaft — Vollinventar (Grundlage für die Neukalibrierung „grüner Rasen")

**Stand:** 2026-10-14 · Branch `feat/bot-utility-s3` (Commit dd9a4389) · reine Recherche, keine Code-/Config-Änderung.
**Zweck:** Der Owner will die Regolith-Wirtschaft (Rg) von null neu kalibrieren. Zielbild: **AP bremst im Normalfall, Rg ist nur gelegentlich Engpass** (1–2 Großanschaffungen pro Phase erzwingen kurzes Sparen). Dieses Dokument listet jede Quelle, jede Senke, die AP-Gegenseite, die Historie und die Kopplungen, jeweils mit Config-Key bzw. Datei:Zeile und aktuellem Wert.

**Konventionen:** Status **impl.** = im Code wirksam · **geplant** = entschieden, nicht umgesetzt · **Idee** = nicht entschieden · **tot** = im Code vorhanden, aber im echten Spiel unerreichbar. Eigene Deutungen und Handrechnungen sind als **Annahme** markiert. Zeilenangaben beziehen sich auf den oben genannten Commit.

---

## 0. Kurzbild

| Größe | Wert heute | Quelle |
|---|---|---|
| Startbestand | 300 Rg | `config/game.php:1033` `onboarding.start_regolith` |
| Harvester-Ertrag | 18 / 26 / 34 Rg/Sol (Ertragsstufe y1/y2/y3), konstant bis Tile leer | `config/game.php:141-145` |
| Tile-Vorkommen | 240 / 450 / 660 Rg (Mächtigkeit d1/d2/d3) | `config/game.php:152-156` |
| Levelup (Nicht-CC, Nicht-Harvester) | flat 25 Rg | `config/game.php:294` |
| Neubau | Errichtung (40–100 Rg) + 25 Rg für 0→1, eine Zahlung beim Platzieren | `config/buildings.php` `build_cost`, `BuildingCostService.php:73-87` |
| CC-Ausbau | Zielstufe × 30 Rg (Lv2 60 … Lv5 150, Σ 420) | `config/buildings.php:52`, `BuildingCostService.php:45-48` |
| Reparatur Gebäude | 1 Rg + 1 AP je SP (CC und Harvester nur AP) | `config/game.php:301`, `ColonyController.php:916-947` |
| Einkommen gemessen | ca. 17 Rg/Sol, > 90 % Harvester (Owner-Angabe, Bot-Läufe) | Auftrag; RunReport `regolith_sources` |
| Rg-Kosten-Rabatte | **keine** — jeder Rabatt im Spiel wirkt nur auf AP | §2.6 unten |
| Lagergrenze Rg | **keine** | grep `storage_cap`/`max_amount`: kein Treffer |

---

## 1. Quellen von Regolith

### 1.1 Startbestand

| Element | Wert | Key / Ort | Status |
|---|---|---|---|
| Start-Regolith | 300 | `config/game.php:1033` `onboarding.start_regolith`; gesetzt in `app/Services/OnboardingService.php:207-208` | impl. |
| Startgebäude | CC Lv1, Harvester Lv1, Wohnhabitat Lv1 (16/20 SP); Harvester steht auf (1,0) in der Kolonie-Zone und **fördert 0, bis er verlegt wird** | `OnboardingService`, T30-Spec §1.1 | impl. |
| Start-Credits / Nexus-Vorschuss | 3000 / 3000 (Credits sind über Nexus-Import usw. in Rg umwandelbar, siehe 1.6) | T30-Spec §1.1 | impl. |
| Dev-Szenarien | `ResetPlayer` setzt hartcodiert 150 / 60 / 210 / 500 Rg | `app/Console/Commands/ResetPlayer.php:510,550,586,650` | impl. (ROADMAP: „nachziehen") |

### 1.2 Harvester (Sockel)

| Element | Wert | Key / Ort | Status |
|---|---|---|---|
| Ertrag je Tile | y1 = 18, y2 = 26, y3 = 34 Rg/Sol, unabhängig von d | `game.harvester.fresh_yield` (`config/game.php:141-145`) | impl. |
| Formel | Ertrag = Frischwert + Geologie-Bonus, solange Restvorkommen > 0, sonst 0 (harter Cutoff); gutgeschrieben wird `round(base × Vertrauensmultiplikator × Personalanteil)`, und genau dieser Betrag wird vom Tile abgezogen | `GameTick::harvesterYield()` `app/Console/Commands/GameTick.php:934-949`, `generateHarvesterYield()` `:1031-1117` | impl. |
| Vorkommen je Tile | d1 = 240, d2 = 450, d3 = 660 Rg (`y3_d3` gibt es bewusst nicht) | `game.harvester.resource_max` (`config/game.php:152-156`); `ColonyTileService::pickRegolithCombo()` `app/Services/ColonyTileService.php:674` | impl. |
| Standzeit (Handrechnung, ohne Geologie/Vertrauen) | y1: 13 / 25 / 37 Sole · y2: 9 / 17 / 25 Sole · y3: 7 / 13 Sole | `resource_max / fresh_yield` | **Annahme** (Rechnung) |
| Kartenbudget | Ring 2 hat nie Regolith. Ring 3: 9 Frontier-Felder, davon 2 garantiert aufgedeckte Rg-Tiles als Gegensatzpaar (H2), die übrigen 7 mit ca. 50 % Rg-Chance | `ColonyTileService.php:760-812`, `RING3_FRONTIER_COUNT = 9` (`:34`), T30-Spec §11.2 | impl. |
| Kartenbudget Größenordnung | ca. 5–6 Rg-Tiles × Ø 424 Rg ≈ 2.100–2.500 Rg je Karte | Mittel der 8 Kombinationen = 423,75 | **Annahme** (Rechnung) |
| Geologie-Bonus | +3/+3/+2/+2/+2 Rg/Sol kumulativ (max +12), **einmal pro Kolonie**, auch vom Vertrauensmultiplikator erfasst und vom Tile abgezogen | `game.geology_harvester_bonus_per_level` (`config/game.php:219`), `GameTick.php:941, 1053-1104` | impl. |
| Geologie-Gate | Analytik-Labor Lv2 + Harvester; Kosten 20/28/36/44/52 AP | `database/seeders/data/researches.php`, `config/knowledge.php:77` | impl. |
| Vertrauens-Multiplikator | ×0,70 / 0,85 / 1,00 / 1,10 / 1,20 (Vertrauen −100…−61 / −60…−21 / −20…20 / 21…60 / 61…100) | `game.trust.production_multiplier` (`config/game.php:746-752`) | impl. |
| Personalanteil | anwesend ÷ Arbeitsplätze, < 1 nach Abwanderung durch Überkapazität | `ResourcesService::staffingShare()`, `game.overcap` (`config/game.php:812-818`) | impl. |
| Verlegung | 2 AP je Hex (mind. 1), im Verlege-Sol kein Ertrag (`pending_until_tick` = aktueller Tick) | `game.harvester.relocate_ap_per_hex` (`config/game.php:159`), `ColonyController.php:509-513, 611` | impl. |
| Instabilität | Ausfall 3 Sole ohne Ertrag; Chance 0,15 % je Sol seit letzter Verlegung, Deckel 5 %; Geologie und Deva-Puffer senken sie | `game.encounter.instability` (`config/game.php:402-406`), `geology_instability_risk_reduction_per_lv` (`:229`) | impl. |
| Warn-Hint | bei Restvorkommen < 30 % | `game.harvester.low_regolith_warning_pct` (`config/game.php:182`) | impl. |
| Zweite Instanz | Deckel 2 Instanzen, ab CC Lv3, **kein Rg-Preis**. Weg A: Orin verkauft sie für 400–800 Cr (Erscheinen alle 15–25 Sole, 30 % Angebotschance, 1 Sol Fenster). Weg B: `mission_harvester_salvage` (Ankunft mit 25 % SP) | `config/buildings.php:107`, `game.harvester.second_instance_cc_level` (`config/game.php:168`), `game.corporate_contact` (`:198-211`), `salvage_arrival_sp_pct` (`:175`) | Weg A impl.; **Weg B tot** (siehe §6) |
| Altkurve | `production_curve[27]` 8/10/12/… | `config/game.php:97` | inert (wird übersprungen, `GameTick.php:996-998`) |

### 1.3 Hangar-Missionen

Kosten aller Missionen: Nav-AP = `sol_distance × 2` (`config/missions.php:30`), Organika = `sol_distance × 3` (`:31`; mit Kenntnis-Gate −1 je Stufe über dem Gate, mindestens 1, `:32-33`), Dauer = `2 × sol_distance`. Erfolgschance nach Schwierigkeit 85 / 70 / 60 % (leicht/normal/schwer), Ertragsfaktor ×0,7 / 1,0 / 1,4, +5 % je Raumfahrer-Rang, +3 % je Kenntnisstufe über dem Gate, Deckel 95 % (`config/game.php:1104-1112`). Der Faktor skaliert auch Regolith (`GameTick.php:450, 532`). Verschleiß nur im Flug: Drohne 1,5, Frachter 1,0, Korvette 0,75 SP je Sol (`config/ships.php:28, 50, 39`); Reparatur 1 AP = +2 SP, ohne Rg (`config/game.php:342-346`). Startsperre: Rückkehr-Garantie (`HangarBays::flightReadiness()`).

| Mission | Schiff | Dauer | Kosten | Rg-Ertrag | Status |
|---|---|---|---|---|---|
| `mission_find_salvage` (Bergungsflug) | Drohne (klein/mittel), Frachter (alle) | Ring 2: 2 Sole, Ring 3: 4 Sole | 2/4 Nav-AP (Kartografie-Rabatt), 3/6 Or | Fund-Rg × 1,5: klein 6, mittel 15, groß 27; **kein Wurf** | impl. (`config/missions.php:63-72`, `FindFlightService.php:42-47`, `game.finds.flight` `config/game.php:66-69`) |
| `mission_long_range_expedition` | Drohne | 10 Sole | 10 Nav-AP, 15 Or; Gate Kartografie 3 | Loot-Tabelle: **30–45 Rg** oder 700–1100 Cr oder 8–12 Wk (eins davon, geseedet) | impl. (`config/missions.php:82-96`) |
| `mission_supply_run` | Frachter | 2 Sole | 2 AP, 3 Or | **0 Rg** (nur 10 Or, seit T30 §13 E8) | impl. (`:99-105`) |
| `mission_prospecting_flight` | — | — | — | früher 20–30 Rg | **gestrichen** 2026-10-12 (Test `FindFlightConfigTest:37`) |
| `mission_deep_survey` | — | — | — | — | **gestrichen** 2026-10-12 |
| `mission_harvester_salvage` | Frachter/Korvette | 8 Sole | 8 AP, 12 Or | zweite Harvester-Instanz (indirekt Rg) | **tot** (keine `event_ruin`-Tiles im Generator) |
| `mission_courier_run`, `mission_recon_flight`, `mission_data_sweep`, `mission_trade_convoy`, `mission_aid_transport`, `mission_salvage_sweep`, `mission_ruin_expedition`, `mission_escort_convoy`, `mission_perimeter_patrol` | div. | — | — | **kein Rg** (Credits, Wk, Or, Info, Forschungs-AP) | impl. bzw. `ruin_expedition` tot |

Schiffskosten (nur Credits): Drohne 300, Frachter 500, Korvette 800 (`config/ships.php:26, 48, 37`). Geschenk-Drohne beim ersten Hangar Lv1 auf dem Vorfeld (Bucht 0). Frachter braucht Hangar Lv2.

### 1.4 Funde / Pool v1

| Element | Wert | Key / Ort | Status |
|---|---|---|---|
| Start-Pool (fest je Run) | 1 groß, 1 mittel, 3 klein, 2 Fehlalarme = **40 Rg** brutto | `game.finds.start_pool` (`config/game.php:51`), `types` (`:52-57`) | impl. |
| Tiefenscan | 2 AP, ab Uplink Lv2 1 AP | `game.finds.scan_ap`, `scan_ap_uplink` (`:58-59`) | impl. |
| Selbstbergung (ohne Hangar) | klein 4 Rg / 12 AP, mittel 10 / 16, groß 18 / 22; höchstens 4 AP je Kachel und Sol, höchstens 2 offene Projekte; Rg erst bei Abschluss | `game.finds.types`, `salvage_cap_per_sol` (`:60`), `max_open_projects` (`:61`), `ColonyTileService::salvageFind()` (`app/Services/ColonyTileService.php:135-225`) | impl. |
| Bergungsflug (mit Hangar ≥ 1) | ersetzt die Selbstbergung (strenge Ablösung), Faktor 1,5 → Pool 60 Rg; große Funde erst mit Hangar Lv2 + Frachter | `game.finds.flight` (`:66-69`), `ColonyTileService::findAction()` | impl. |
| **Neu entschieden 2026-10-13** | Selbstbergung als Sofort-Aktion 3/4/6 AP, ohne Sol-Deckel und ohne Projektlimit; `yield_factor` 1,5 → 1,0; Flug soll deutlich weniger AP kosten als die Selbstbergung | `docs/superpowers/specs/2026-10-13-bergung-ohne-sol-deckel.md` | **geplant** (Config steht noch auf 12/16/22, 4 AP/Sol, 1,5) |
| Nachschub-Signale | ab Sol 8 20 %/Sol ein neues Signal, höchstens 5, nach Sol 30 keine; E ≈ +20 Rg bis Sol 25 | T30-Spec §11.3, §11.9 Schritt 6 | **geplant** (nach Messung) |
| Cantina-Gerüchte, Gerücht-Gutschein (+25 % auf einen Flug), Zusatzsignal v2 (höchstens 3 je Run, je 30 Rg brutto) | — | T30-Spec §11.4, §11.6, §11.7 | **geplant** (Schritte 4/5/7) |
| Landungsschrott, Erkundungs-Fundquote, Starthilfe-Paket (15 Rg je Pfadgebäude), Nexus-Vorschuss auf Rg, „Schürfen" (AP→Rg) | — | T30-Spec §10.5 B1–B6 | **Idee** (nicht gesetzt) |

### 1.5 Cantina

| Kanal | Rg-Wirkung | Kosten / Takt | Key / Ort | Status |
|---|---|---|---|---|
| Gästerotation: Rg→Credits-Verkauf (T10) | **Senke** Rg 10–30, Erlös ≈ 25 Cr/Rg ± 10 %; 12 % je Gast-Slot | 2 AP Annahme; Gäste je Sol nach Konsul-Rang `[0,1]`…`[1,2]`, gleichzeitig 2/3/4 Angebote (Cantina Lv1–3), Gültigkeit 2/3/3 Sole | `game.bar.regolith_sell_offer_chance_pct` (`config/game.php:536`), `base_prices[3]` = 25 (`:525`), `BarService.php:100-117, 1483-1494` | impl. |
| Gästerotation: Tausch Rg↔Wk/Or | Quelle oder Senke, 10–30 Einheiten der Abgabe, Gegenwert über `base_prices` 25 / 110 / 50 (Rg/Wk/Or) | 2 AP; 88 % der Gast-Slots | `BarService::buildBarterOffer()` (`:1497-1511`), `TRADEABLE = [3,4,5]` (`:29`) | impl. |
| Corvan Kaufangebot (Credits→Rg) | **Quelle** 10–50 Rg (Los an ≤ 35 % des Credits-Bestands gebunden), Preis ≈ 25 Cr/Rg ± 10 % | erster Besuch Sol 15–20, dann alle 5–8 Sole, je 2 Sole; ab Konsul-Rang 3 zu 50 % Werkstoffe statt zufälliger Ware | `BarService::buildCorvanBuyOffer()` (`:1374-1403`), `game.merchant` (`config/game.php:962-1026`) | impl. |
| Corvan Verkaufslose | nur Organika (`sell_resource_id` = 5) | — | `config/game.php:1005` | impl. (Rg bewusst ausgeschlossen, Owner 2026-09-28) |
| Handelsvorteil | Konsul 10/20/30 %, Handelsposten, Kenntnis `trade` +2/3/3/2/2 %; wirkt auf die Erhalt-Seite (auch Rg) | stille Deckel 60 % Cantina / 60 % Händler / 25 % Nexus | `config/game.php:527-534`, `config/knowledge.php:114`, `TradeAdvantageService` | impl. (Wirkung auf Rg-Verkaufsangebote: **Annahme**, nicht einzeln geprüft) |
| Verhandeln | +20 % auf die Erhalt-Seite bei 60/65/70 % (+Kenntnis `trade`), sonst Angebot weg | 2 AP | `config/game.php:549-551` | impl. |
| Begegnung „Wette" (Zara) | **Senke** Einsatz 15/20/25 Rg (Cantina Lv1–3), 45 % Gewinn → 80/110/140 Cr | 2 AP; Begegnungschance 20/28/36 %/Sol | `game.bar.encounter.wager` (`config/game.php:568-574`), `BarService.php:590-594` | impl. |
| Anliegen „Fen" (prospector) | **Quelle** 20–30 Rg bei 50 % Erfolg | 2 AP; Anliegenchance 10/14/18 %/Sol, Gewicht 2/20 | `game.bar.concern.prospector` (`config/game.php:696`), `BarService.php:1032-1041` | impl. |
| Anliegen Vesper / Aldra | Ausbau-Gutschein −25 % / −15 % (Aldra verfällt nach 10 Sol) | **nur AP**, kein Rg | `config/game.php:682, 703`, `ProjectBonusService.php:67-108` | impl. |
| Anliegen Sarka, Tomas, Deva, Lenn | Bonus-AP in Kenntnisse (5 / 0–3 je Gespräch / 15 / 15); Deva wahlweise in Geologie → **indirekte Rg-Quelle** | 0–4 AP; Labor nötig | `config/game.php:689, 733, 622-623, 633-634` | impl. |
| Anliegen Maret | Werkstoffe 15–17 (Cantina Lv1–3) | — | `:693` | impl. (kein Rg) |
| Auktion, Kontrakt, Juno, Stranger | Credits; Stranger setzt Werkstoffe ein | — | `:576-592, 699, 713` | impl. (kein Rg) |
| Kontor (Credits→Rg nach Beziehungsstufe) | — | — | T30-Spec §10.3 C1 | **verworfen** (Owner 2026-10-09: kein planbarer Rg aus der Cantina) |
| Inerte Werte | Stufen 4/5 in `spawn_chance_per_level`, `stake_amount_per_level`, `level_offer_duration` usw. | Cantina hat `max_level` 3 (`config/buildings.php:300`) | `config/game.php:539-540, 563, 570` | inert |

### 1.6 Handel über den Nexus

| Kanal | Rg-Wirkung | Kosten | Key / Ort | Status |
|---|---|---|---|---|
| Uplink-Direktimport | **Quelle, unbegrenzt**: 1–9999 Rg je Bestellung zu 35 Cr/Rg (Handelsvorteil Nexus bis −25 %), Lieferung nach 5/4/3 Solen (Uplink Lv1/2/3), **keine AP** | Uplink Lv1 (80 + 25 Rg, CC Lv2) | `game.economy.delayed_import_price` (`config/game.php:263`), `delayed_import_delivery_ticks` (`:273`), `ColonyController.php:1124-1160`, `NexusImportService.php:96` | impl. |
| Werkstoff-Import | kein Rg (Wk 165 Cr) | — | `config/game.php:254` | impl. |

**Folge (Annahme):** Der Uplink-Import ist eine Preisobergrenze für Regolith (≈ 26–35 Cr/Rg). Wer Credits hat, kann jeden Rg-Engpass ab Uplink Lv1 nach 3–5 Solen auflösen. Für das Zielbild „Rg nur gelegentlich Engpass" ist dieser Kanal der stärkste Hebel außerhalb des Harvesters.

### 1.7 Zufallsereignisse, Siegziele, Nexus

| Element | Rg-Wirkung | Ort | Status |
|---|---|---|---|
| Sturm, Instabilität, Seuche | keine direkte Rg-Gutschrift; Sturm erzeugt Reparaturbedarf (Senke), Instabilität Ertragsausfall | `GameTick` (§2.5 unten) | impl. |
| Leitstelle Stufe 3 „Bergungsdienst" | **Quelle**: bei jedem Stufenverlust (Verfall oder Sturm) 10 % der Errichtungskosten zurück, mindestens 1 (z. B. Pfadgebäude 9 Rg) | `config/buildings.php:360-361`, `GameTick.php:757-762` (liest Tabelle `building_costs`) | impl. |
| Siegziele | keine Rg-Belohnung. `task_self_sufficiency` verlangt **Rg > 150 und Or > 300 für 35 Sole am Stück** (Halte-Senke) | `config/game.php:897` | impl. |
| Nexus-Boni (Betriebsmittelzulage usw.) | GDD §15 nennt Credits und AP, kein Rg | `docs/GDD.md:2749-2754` | geplant (nicht implementiert, **Annahme**) |
| Score | kein Rg-Term | `config/game.php:936-941` | impl. |

### 1.8 Berater-Rang und -Level

| Element | Rg-Wirkung | Ort | Status |
|---|---|---|---|
| Rang 1–3 | nur AP (+2/+3/+4) und Credits (Unterhalt 10/25/35, Beförderung 150/250) | `config/game.php:426-454` | impl. |
| Berater-Rang als Projekt-Kostenbonus | senkt **AP** von Projekten (`project_cost_bonus`), kein Rg | GDD §13.3 (`docs/GDD.md:1815-1821`), ROADMAP D3 | geplant |
| Berater-Außenmissionen (Baumeister „Nexus-Notfall-Wartung": ein Gebäude auf volle SP) | indirekt: spart Reparatur-Rg | GDD §13 (`docs/GDD.md:2022-2030`) | **nicht implementiert** (kein Code gefunden) |
| Rg-Bonus aus Berater-Rang | — | — | **existiert nicht**, auch nicht geplant |

### 1.9 Kenntnis-Effekte mit Rg-Bezug

| Kenntnis | Effekt | Rg-Wirkung | Key | Status |
|---|---|---|---|---|
| geology | +3/+3/+2/+2/+2 Rg/Sol am Harvester | **Quelle** | `config/game.php:219` | impl. |
| geology | Instabilitäts-Risiko −3/−5/−5/−4/−3 % | schützt Ertrag | `config/game.php:229` | impl. |
| construction | Bau-AP −2/−4/−4/−3/−2 % (Σ 15 %) | **nur AP**, kein Rg | `config/knowledge.php:45` | impl. |
| cartography | Nav-AP −4/−8/−8/−6/−4 % (Σ 30 %), auch auf Bergungsflug | indirekt (billigere Flüge) | `config/knowledge.php:68` | impl. |
| trade | Handelsvorteil +2/3/3/2/2 %, +2 Angebotsslots, Verhandlungschance | indirekt (Rg-Tausch/-Kauf/-Verkauf) | `config/knowledge.php:111-120` | impl. |
| defense / health / agronomy | Sturmrisiko, Seuchenrisiko, Organika | indirekt (weniger Sturm-Reparatur) | `config/game.php:224, 234, 242` | impl. |
| Sciencelab Lv2–5 | Kenntnis-AP −6/7/8/9 % (Σ 30 %) | nur AP | `config/buildings.php:187` | impl. |
| Geologie räumt Geröll-Tiles frei | Bauplatz, ggf. verschüttete Vorkommen | ROADMAP „Blockierte Tiles freiräumen", A44 Paket 2 | **Idee** |
| Geologie erhöht Fund-/Prospektionsertrag | — | T30-Spec §11.4: ausdrücklich **nicht** (kein Stapeln) | verworfen |
| Option G: Geologie-Kurve 2/2/2/2/2 | — | T30-Spec §11.5 | **Idee** (nur nach Messung) |
| Feldproben (Bergungsflug zahlt 2 AP in eine Kenntnis) | indirekt | T30-Spec §11.6 | **geplant** (Schritt 5) |

---

## 2. Senken von Regolith

### 2.1 Neubau (Errichtung + 25 Rg für Stufe 0→1, beim Platzieren)

Quelle: `config/buildings.php` `build_cost` → per `game:sync-config` in die Tabelle `building_costs` (Rg-/Wk-Zeilen). `BuildingCostService::placementCost()` (`app/Services/BuildingCostService.php:73-87`) addiert `levelup_regolith_flat`. Ausbau 0→1 danach kostet nur AP. Abzug in `ColonyController::placeBuilding()` (`ColonyController.php:529-549`), Ablehnung `resource_limit`.

| Gebäude | Errichtung | + Stufe 1 | Summe Platzieren | Werkstoffe | CC-Gate | max. Stufe / Instanzen | Ort |
|---|---|---|---|---|---|---|---|
| Wohnhabitat | 40 | 25 | 65 | — | 1 | 3 / 6 | `buildings.php:64` |
| Agrardom | 70 | 25 | 95 | — | (Harvester) | 3 / 1 | `:122` |
| Analytik-Labor | 95 | 25 | 120 | — | 2 | 5 / 1 | `:152` |
| Hangar | 95 | 25 | 120 | — | 2 | 3 / 1 | `:218` |
| Cantina | 95 | 25 | 120 | — | 2 | 3 / 1 | `:289` |
| Krankenstation | 60 | 25 | 85 | 25 | 3 (Seed) | 3 / 1 | `:243` |
| Leitstelle | 80 | 25 | 105 | 25 | 2 (Seed) | 3 / 1 | `:349` |
| Uplink-Station | 80 | 25 | 105 | — | 2 | 3 / 1 | `:375` |
| Religiöse Stätte | 50 | 25 | 75 | 15 | 4 | 1 / 1 | `:319` |
| Handelsposten | 100 | 25 | 125 | 25 | 4 | 3 / 1 | `:395` |
| Kolonialdenkmal | 60 | 25 | 85 | 25 | 5 | 1 / 1 | `:305` |
| CC, Harvester | — | — | 0 | — | — | — | Bootstrap-Ausnahme |

CC-Gates nach Seeder (`database/seeders/data/buildings.php:4-16`); `game-reference.md` §2 nennt teils abweichende Gates (Krankenstation „Lv2", Leitstelle „Lv3") — siehe §6. Die Tabelle `building_costs` im Seeder (`database/seeders/data/building_costs.php`) enthält zusätzlich Credits- (ID 1) und Supply-Zeilen (ID 2), die **nicht** abgebucht werden.

### 2.2 Levelup und CC-Ausbau

| Element | Wert | Ort | Hinweis |
|---|---|---|---|
| Levelup Nicht-CC | flat 25 Rg je Stufe, beim ersten AP-Klick eines Zyklus | `game.build.levelup_regolith_flat` (`config/game.php:294`), `ColonyController.php:745-776` | unabhängig von Gebäude und Stufe |
| CC-Ausbau | Zielstufe × 30: Lv2 60, Lv3 90, Lv4 120, Lv5 150 (Σ 420) | `cc_upgrade_regolith_per_level` (`config/buildings.php:52`), `BuildingCostService.php:45-48` | Fallback 30 im Code |
| Harvester | 0 (kein Levelup, `max_level` 1) | `BuildingCostService.php:41-43` | — |
| Stufenverlust durch Verfall/Sturm | Neuaufbau kostet wieder 25 Rg + AP | `GameTick::applyLevelDown()` | indirekte Senke |

**Vollausbau-Bedarf (Handrechnung, Annahme):** alle Gebäude je eine Instanz auf Maximalstufe, Wohnhabitat 1 Instanz ≈ 1.955 Rg; mit 6 Wohnhabitat-Instanzen auf Stufe 3 ≈ 2.530 Rg (ohne Reparatur und Stufenverluste). Phase-1-Kette laut GDD §13.7: **535 Rg** (CC 150, Agrardom 120, Wohnhabitat 25, zwei Pfadgebäude je 120). GDD-Zielkolonie (veraltet): 1.795 Rg über 80 Sole.

### 2.3 Reparatur

| Element | Wert | Ort | Status |
|---|---|---|---|
| Gebäude | 1 Rg + 1 AP je SP; CC und Harvester nur AP | `game.repair.regolith_per_click` (`config/game.php:301`), `ColonyController.php:916-947` | impl. |
| Verfall (SP je Sol) | Robust 0,40 (CC, Wohnhabitat, Denkmal) · Standard 0,60 (Agrardom, Hangar, Leitstelle, Uplink, Handelsposten) · Beansprucht 0,80 (Harvester, Labor, Cantina, Krankenstation) · Fragil 1,20 (Religiöse Stätte) | `config/buildings.php` `decay_rate` | impl. |
| Instandhaltung Rg/Sol (Handrechnung, Annahme) | Phase 1 (Wohnhabitat, Agrardom, 2 Pfadgebäude) ≈ 2,6 Rg/Sol; Vollausbau 1 Wohnhabitat ≈ 7,4, mit 6 Instanzen ≈ 9,4 Rg/Sol | Σ `decay_rate` ohne CC/Harvester | — |
| Start-SP | 16/20 (80 %), Anzeige erst unter 70 % | `game.repair.display_threshold` (`config/game.php:310`) | impl. |
| Schiffe | nur AP (1 AP = +2 SP), kein Rg | `game.ship_repair` (`config/game.php:342-346`) | impl. |
| Händler-Reparaturkit | +30 SP für 400 Cr (spart ca. 30 Rg + 30 AP) | `game.merchant.items.repair_kit` (`config/game.php:996`) | impl. |
| Levelskalierte Reparatur `1 + floor((level−1)/3)` AP je SP | — | GDD §13.5 Nachrüstoption | **Idee** |
| Wartungsplan (Leitstelle: ausgewählte Gebäude verfallen 1 Sol nicht) | spart Reparatur-Rg | Handoff `2026-09-27-a44-t9-handoff.md:23` | **Idee/geplant** (nicht begonnen) |

### 2.4 Cantina, Handel und sonstige Rg-Ausgaben

| Senke | Wert | Ort |
|---|---|---|
| Rg→Credits-Verkauf (Gäste) | 10–30 Rg je Angebot | §1.5 |
| Tausch Rg→Wk/Or | 10–30 Rg je Angebot | §1.5 |
| Wette | 15/20/25 Rg Einsatz, 55 % Verlust | §1.5 |
| Siegziel Selbstversorgung | 150 Rg 35 Sole halten | §1.7 |
| Missionen | **keine** Mission kostet Rg (Kosten: Nav-AP, Organika) | `config/missions.php` |
| Schiffe, Berater, Kenntnisse | **kein** Rg (Credits bzw. AP; `knowledge.*.credits` = 0) | `config/ships.php`, `config/advisors.php`, `config/knowledge.php` |
| Harvester-Zweitinstanz | kein Rg (Credits oder Mission) | §1.2 |

### 2.5 Ereignisse mit Rg-Folgekosten

| Ereignis | Wirkung | Werte | Ort |
|---|---|---|---|
| Sturm | trifft alle Kolonie-Zonen-Gebäude (ohne Harvester): ≥ 66 % SP abgewehrt, sonst −20 % max-SP (4 SP = 4 Rg Reparatur), < 33 % kritisch mit Stufenverlust (25 Rg Neuaufbau) | Chance 1,2 %/Sol, Deckel 2 %, Phase-1-Rampe über 15 Sole; `defense` senkt | `game.encounter.storm` (`config/game.php:385-401, 414-416`), `GameTick.php:1415-1436` |
| Instabilität | 3 Sole kein Harvester-Ertrag (y2: ca. 78 Rg) | 0,15 % je Sol seit Verlegung, Deckel 5 % | `config/game.php:402-406` |
| Seuche | −20 % AP für 5 Sole (indirekt) | nur bei Hunger-Streak ≥ 3 oder Vertrauen < −20 | `config/game.php:407-411` |
| Überkapazität | Personalanteil < 1 senkt Harvester-Ertrag | — | `config/game.php:812-818` |

### 2.6 Rabatte auf Rg-Kosten

**Es gibt keinen.** Alle Rabattquellen wirken ausschließlich auf AP: `construction` (Bau-AP), Vesper-/Aldra-Gutschein (Bau-AP, `ProjectBonusService::buildingApDiscountPercent()`), Sciencelab (Kenntnis-AP), `cartography` (Nav-AP), Konsul/Handelsposten/`trade` (Preise beim Handel). Untergrenze `project_min_cost_factor` = 0,5 (`config/game.php:107`) gilt nur für AP. Geplant ist ebenfalls nur AP (Berater-Rang, Koloniereife, GDD §13.3; ROADMAP D3 `f(L)`-Kurve).

---

## 3. AP-Seite (Gegengewicht)

### 3.1 AP-Einkommen

| Element | Wert | Ort |
|---|---|---|
| Grundwert | 12 AP/Sol, ein gemeinsamer Pool | `game.ap.base` (`config/game.php:318`) |
| Berater | +2 / +3 / +4 je Rang 1/2/3, sofort ab Einstellung; höchstens 4 Berater (Slots = CC-Stufe, max 4) | `game.advisor.ap_per_rank` (`:427`), `max_slots` (`:437`), `Advisor::getApPerTick()` (`app/Models/Advisor.php:51-56`) |
| Rang-Schwellen | Rang 2 ab 15 aktiven Solen, Rang 3 ab 45; Beförderung manuell, 150 / 250 Cr | `config/game.php:426, 434` |
| Formel | `round((12 + Σ Berater) × Vertrauens-AP-Faktor × Seuchenfaktor)` | `AdvisorService::getApBreakdown()` (`app/Services/AdvisorService.php:61-90`) |
| Vertrauens-AP-Faktor | 0,80 / 0,90 / 1,00 / 1,05 / 1,10 | `game.trust.ap_multiplier` (`config/game.php:754-760`) |
| Seuche | −20 % für 5 Sole | `:410` |
| Spanne | Sol 1: 12 → 14 (Baumeister) · Phase 1 Ende: 18 (3 × Rang 1) · voller Stab: 12 + 4 × 4 = 28, × Vertrauen bis ca. 31 | Rechnung |
| Zweckgebundene Bonus-AP (nur Kenntnisse) | Tomas 0/1/2/3 je Gespräch (ab 0/5/15/30 Gesprächen), Sarka 5, Deva/Lenn je 15, `mission_data_sweep` 8 | `config/game.php:733, 689, 622, 633`, `config/missions.php:78` |
| Händler-AP-Pakete | 20 AP für 800 Cr (frei), 15 AP für 500 Cr (Kenntnis) | `game.merchant.items` (`config/game.php:989-990`) |
| Gemessen (Bot) | Zufluss Sol 4–15 ca. 14–16 AP/Sol; ungenutzt Sol 4–12 (K4) Baseline 88–95, nach Pool v1 22/25 (Hangar/Cantina), nach Schritt 3 Hangar 63 | T30-Spec §9.1, §9.2; CHANGELOG 2026-10-10; Utility-Spec §1.3 |

### 3.2 AP-Kosten

| Aktion | AP | Ort |
|---|---|---|
| Platzieren | 1 | `ColonyController.php:509-514` |
| Gebäude-Stufe | 10 (Denkmal 20), je Klick 1 AP, Rabatt `construction` + Gutschein | `database/seeders/data/buildings.php` `ap_for_levelup`, `ProjectBonusService::effectiveApForLevelup()` |
| Kenntnis-Stufe | 20 / 28 / 36 / 44 / 52 (Σ 180 je Kenntnis, 7 Kenntnisse = 1.260); Stufe 4/5 brauchen CC 4/5; Labor-Rabatt bis −30 % | `config/knowledge.php`, `game.knowledge_cc_level_cap` (`config/game.php:826-829`) |
| Reparatur Gebäude | 1 je SP (+1 Rg) | `config/game.php:301` |
| Reparatur Schiff | 1 je 2 SP | `config/game.php:342-346` |
| Harvester verlegen | 2 je Hex | `config/game.php:159` |
| Erkunden | Ring 1/2/3: 1/2/3 Nav-AP (Kartografie-Rabatt) | `game.colony.explore_cost_per_ring` (`config/game.php:45`) |
| Tiefenscan | 2 (Uplink Lv2: 1) | `config/game.php:58-59` |
| Selbstbergung | 12 / 16 / 22 (höchstens 4 je Kachel und Sol) — geplant 3 / 4 / 6 sofort | `config/game.php:53-60`; Spec 2026-10-13 |
| Mission / Bergungsflug | 2 Nav-AP je `sol_distance` | `config/missions.php:30` |
| Cantina-Angebot annehmen / verhandeln | 2 / 2 | `config/game.php:538, 549` |
| Cantina-Begegnung | 2 | `:565` |
| Charakter-Anliegen | 2–4 | `:664-667` |
| Kolonisten wegschicken | 8 | `:817` |
| Uplink-Import, Berater, Schiffe, Händler | 0 AP | — |

### 3.3 Sol-Deckel

| Deckel | Wert | Ort | Status |
|---|---|---|---|
| Selbstbergung je Kachel | 4 AP/Sol, 2 offene Projekte | `game.finds.salvage_cap_per_sol`, `max_open_projects` | impl.; Abschaffung **geplant** (2026-10-13) |
| Gebäudeausbau / Forschung | **kein Sol-Deckel** im Spiel (nur der AP-Pool begrenzt) | `ColonyController.php:767` | impl. |
| `build_ap_per_sol` = 10, `nav_ap_per_sol` = 4 | **nur Bot-Gewicht** (Annahme des Utility-Modells), keine Spielregel | `tests/Feature/Playtest/Utility/weights.php:32-33` | Test-Harness |
| Sockel/Deckel pro Sol für Bau und Forschung | — | Owner-Idee in Spec 2026-10-13 | **Idee** |

### 3.4 Pacing-Ziele und Phasengrenzen

| Ziel | Wert | Ort |
|---|---|---|
| Phase-1-Bedingung | CC Lv3 + 2 Nicht-CC-Gebäude ≥ Lv2 + 3 Berater | `RunProgressService::checkPhase1Completion()` |
| Phase-1-Ende | Ziel Sol 15–20; Warnung Sol 22; harter Fail Sol 30 | `config/game.php:843-844`; Owner-Vorgabe |
| Phase-1-Ende gemessen | Median 16–18 (T9 2026-10-02: 17,5; T30 Schritt 1: 16,5–17) | CHANGELOG, T30-Spec §11.9 |
| Sieg | Sol 70–85 nur bei sehr gutem Run mit Glück, realistisch 85–95; `tick_limit` 100 | `config/game.php:834`, Handoff A44/T9 |
| Nexus-Kontrollpunkte | Phase-2-Sol 30 / 50 / 65: Fortschritt [50] / [75, 50] / [100, 75] % | `config/game.php:925-929` |
| Kalibrierregel Siegziele | ohne gezieltes Spiel frühestens Sol ~90, gezielt Sol 70–85 | `config/game.php:865-867` |
| Bot-Siegquote | ca. 50 % gewollt, Menschen 70–80 %; > 65 % heißt „Knappheit verloren" | Memory, T30-Spec §9.5 |

### 3.5 Bot-Kennzahlen (T30, `app/Support/OpeningComparison.php`)

| ID | Definition | Ziel | Rg-Bezug |
|---|---|---|---|
| K1 | Sole von Pfadgebäude Lv1 bis zur ersten Pfadaktion | ≤ 1 | — |
| K2 | Sole bis zum ersten Phase-1-relevanten Ertrag (Rg, Supply, AP, Vertrauen) | ≤ 3 | ja |
| K3 | `phase2_start_sol` Median | 15–20, Abstand ≤ 2 | ja (Rg-Kette) |
| K4 | Σ ungenutzte AP Sol 4–12 | Abstand ≤ 20 | indirekt (Rg-Warteblock) |
| K5 | Leerlauf-Sole Sol 4–15 | ≤ 2 | indirekt |
| K6 | Rg zu Beginn Sol 10 | Spanne ≤ 25 | **direkt** |
| K7 | Siegquote / Sieg-Sol | Abstand ≤ 10 Punkte / ≤ 3 Sole | — |
| K8–K10 | Pfad-Rg Sol 10–25, Abstand, Anteil am Gesamt-Rg | 20–25 %, Warnung > 35 % | **direkt** (Spec §10.6; nicht implementiert, **Annahme**) |
| K11 | Pool-Rg Sol 10 / 25 | ca. 20 / 50 | **direkt** |
| K12 | Anteil Pool-Rg per Schiff | Hangar ≥ 85 % | **direkt** |
| K16 / K17 | Wartesole gescannter Funde je Grund / Zonen-Blockade | Bug-Detektor | ja |
| K18–K23 | Utility-Engine (Legacy-Anteil, Stufen-Sole, Entscheidungen/Sol, λ_AP, Schattenabweichung, CC Lv4-Sol) | informativ | K23 indirekt (CC-Rg) |

---

## 4. Historie und Fallen

### 4.1 Kalibrierungen (chronologisch)

| Datum | Änderung | Grund | Quelle |
|---|---|---|---|
| 2026-07-20 | Harvester als Glockenkurve 8/10/12/… je Level (Levelup 10 AP + 10 Rg) | Grundproduktion zu knapp | CHANGELOG:635-640 |
| 2026-08-02/03 | §13.7 Zahlensatz: Levelup flat 25, CC Zielstufe × 30 (vorher 20), Reparatur 2 → 1 Rg/SP, `decay_rate`-Klassen, Pfadgebäude 95 (Hangar 120), Agrardom 70; Harvester `max_level` 1; Knappheitsordnung Rg < Or < Wk; `bar.base_prices[3]` 30 → 25 | Herleitung aus Spielgefühl-Aussagen G1–G7 | GDD §13.7, CHANGELOG:430, 470-476 |
| 2026-08-03 | Erschöpfung mit Rampe; `resource_max` 500/300/160, Frischwerte 24/18/12 | Harvester soll mehrfach umziehen | CHANGELOG:419-440 |
| 2026-08-05 | Zweitinstanz ohne Rg (Orin / Bergungsmission) | Sockel = 1 Instanz | CHANGELOG:397-403 |
| 2026-08-11 | Hangar 120 → 95 (alle Pfadgebäude gleich) | G4-Korridor 5–8 Sole | `config/buildings.php:214-218` |
| 2026-08-12/13 | Startbestand 200 → 300 → 340 → 370 | Phase 1 zu langsam; Sturm-Reserve | CHANGELOG:352, 373 |
| 2026-08-17 | Ertrag +25–30 % (24/18/12 → 30/23/15) | Bot: Rg bricht in Sol 7–10 auf 0–30 ein | CHANGELOG:346, `config/game.php:122-126` |
| 2026-09-09 | Konstante Rate statt Rampe (A24) | Owner F4, Lesbarkeit | ROADMAP A24 |
| 2026-09-11 | Uplink-Direktimport Rg 35 Cr | Fortschritts-Sperren lösen | ROADMAP A28 |
| 2026-09-25 | T9: Platzieren bucht Errichtung + Stufe 1 (vorher kostete 0→1 nie Rg) | Owner-Regel „Rg immer beim Platzieren voll" | CHANGELOG:90 |
| 2026-09-27 | A44 H1/H2: zwei Achsen y/d, 8 Kombinationen, zwei bekannte Start-Tiles | Kartenvariation | ROADMAP A44 |
| 2026-09-28 | T10: `resource_max` × 1,5 (160/300/440 → 240/450/660); Rg→Cr-Verkauf 12 % in der Gästerotation | Kartenbudget erschöpft ca. Sol 44 (T18); fehlender Verkaufskanal | `docs/superpowers/plans/2026-09-27-t10-regolith-credits-rekalibrierung-spec.md` |
| 2026-10-02 | T9 Schritt 5: Startbestand 370 → 300 | Phase-1-Ende Median 12,5 → 17,5 | CHANGELOG:41 |
| 2026-10-09 | T30 Schritt 1: Ertrag 16/23/30 → 18/26/34 (`resource_max` unverändert, Tiles ca. 12 % früher leer) | Pfad-Parität, Sockel statt Zufall | CHANGELOG:19 |
| 2026-10-09 | Pool v1 (40 Rg Start-Pool, Selbstbergung) | AP-Leerlauf im Rg-Warteblock | CHANGELOG:20 |
| 2026-10-12 | T30 Schritt 3: Bergungsflug ×1,5; Prospektion (20–30 Rg) und Versorgungsfahrt-Rg (25 Rg) gestrichen | kein planbarer Hangar-Rg-Strom (Owner F2) | CHANGELOG:10 |
| 2026-10-13 | Entscheid: Selbstbergung 3/4/6 AP sofort, Faktor 1,0 | Bergung „falsch umgesetzt" | Spec 2026-10-13 (nicht umgesetzt) |

### 4.2 Warum die Werte so sind (Kernbegründungen)

- **Flat 25 Levelup / Errichtung 95:** G4 „Errichten ist eine Entscheidung (5–8 Sole Sparen), Levelup ein Schritt (1–2 Sole)"; „Breite kostet Regolith, Tiefe kostet AP" (`docs/GDD.md:2245`).
- **Reparatur 1 Rg = 1 AP je SP:** „eine Zahl, zwei Währungen": Instandhaltung [Rg/Sol] = Instandhaltung [AP/Sol] = Σ `decay_rate` (`docs/GDD.md:2272-2278`).
- **CC × 30:** „zentraler Progressionshebel", teuerster Einzelposten (`config/buildings.php:49-51`).
- **Startbestand 300:** empirisch am Bot-Median auf Phase-1-Ende Sol 15–20 kalibriert (`docs/GDD.md:2345-2383`).
- **§13.7-Bilanz ist veraltet:** rechnet mit dem Rampen-Zyklusmittel 12,9 Rg/Sol; Neuherleitung offen (ROADMAP A27, `docs/GDD.md:2228`).

### 4.3 Tests und Bot-Regeln, die an Rg-Werten hängen

| Datei | Bindung |
|---|---|
| `tests/Unit/HarvesterFreshYieldConfigTest.php` | pinnt `fresh_yield` und `resource_max` (240/450/660) |
| `tests/Unit/FindsConfigTest.php` | pinnt Pool (7 Signale, 40 Rg, groß 18/22, Scan 2/1, Deckel 4, 2 Projekte) |
| `tests/Unit/FindFlightConfigTest.php` | pinnt `yield_factor` 1,5, Tragkraft, keine Prospektion, Versorgungsfahrt nur Or |
| `tests/Feature/Colony/BuildingCostServiceTest.php`, `PlacementPrepaysFirstLevelTest.php` | Levelup/CC/Platzieren |
| `tests/Feature/GameTick/HarvesterDepletionTest.php`, `GameTickResourceGenerationTest.php`, `StaffingProductionTest.php`, `tests/Unit/ColonyTileServiceSolsRemainingTest.php` | Ertrag, Erschöpfung, Multiplikatoren |
| `tests/Feature/Colony/NexusDelayedImportTest.php`, `CommandCenterTest.php` | Importpreis 35 |
| `tests/Feature/Bar/*`, `tests/Unit/BarRngDistributionTest.php` | Wette, Fen, Verkaufsquote 12 % |
| `tests/Feature/Auth/OnboardingTest.php:47` | Startbestand aus Config |
| `tests/Feature/RunObjectivesTest.php`, `RunProgressServiceTest.php` | `regolith_min` 150 |
| `tests/Unit/Techtree/KnowledgeEffectDescriptionServiceTest.php` | Geologie-Anzeige |
| Bot (Legacy) `tests/Feature/Playtest/BotStrategy.php` | Puffer: Pfadgebäude-Kosten (`cheapestPendingPathBuildingCost`), CC-Forschungsreserve (`ccResearchReserve` `:1535`), Selbstversorgungs-Reserve `regolith_min × 1,1 + 1` (`:1575-1582`), Wartungsreparatur nur über Reserve (`:794-805`) |
| Bot (Utility) `tests/Feature/Playtest/Utility/` | Rg ist Numéraire (1 Rg = 1 Einheit), Credits 0,04 Rg/Cr (≈ 25 Cr/Rg), `income_floor_rg` 8, Sparziele (`SavingsGoal`, `RegolithHoldTest`) |

---

## 5. Kopplungsgraph

### 5.1 Abhängigkeiten

| Wert | hängt ab von / wirkt auf | Art |
|---|---|---|
| `fresh_yield` | ↔ `resource_max` (Standzeit = max ÷ Ertrag; höherer Ertrag = früher leer, mehr Umzüge); ↔ Startbestand (Phase-1-Tempo); ↔ Kartenbudget (Gesamt-Rg je Run); ↔ Sieg-Pacing | stark |
| `resource_max` × Anzahl Rg-Tiles | Kartenbudget → T18 (Karte leer um Sol 44 vor T10); unbeeinflusst vom Ertrag | stark |
| Geologie-Bonus | erhöht Rate und leert das Tile mit; Gate Labor Lv2 → Pfad A | mittel |
| Vertrauen | Produktionsmultiplikator 0,7–1,2 → Rg-Ertrag; Vertrauen hängt an Gebäuden, Kenntnissen, Ereignissen, Siegziel `task_colony_prosperity` | mittel (bis ±20 %) |
| Personalanteil | Überkapazität → Wohnhabitat → Rg-Kosten 65 je Instanz | schwach–mittel |
| Startbestand | Phase-1-Ende; jedes Rg weniger verschiebt um 1 ÷ Ertrag Sole | stark |
| Levelup flat 25 | Phase-1-Kette (2 × Lv2), `task_engineering_output` (Ziel 35 Stufen), Neuaufbau nach Stufenverlust, Anteil Platzieren (+25) | stark |
| CC × 30 | Phase-1 (CC 3), Kenntnis Lv4/5 (CC 4/5, `task_research_lead`), Zonenkacheln (`colony_zone_expansion`, `task_expedition_coverage`), Berater-Slots | stark |
| Errichtung 95 | Pfad-Parität (G4), Slot 2/3, Phase 1 | stark |
| Reparatur 1 Rg/SP | Σ `decay_rate` × Anzahl Gebäude; Sturm-Schaden | mittel, wächst mit Ausbau |
| Uplink-Import 35 Cr | Credits-Wirtschaft (Missionen 180–1100 Cr, Nexus 50 + 45/Uplink-Stufe) → Rg-Preisdeckel | stark ab Uplink Lv1 |
| Rg-Verkauf 25 Cr | Arbitrage-Abstand zum Import (35) und zu Corvan-Kauf (≈ 25) — Kauf bei Corvan und Verkauf an Gäste liegen beide bei ≈ 25 ± 10 % (**Annahme:** knapper Abstand) | mittel |
| Pool / Bergung | AP-Leerlauf (K4/K5), Hangar-Pfadidentität, `yield_factor` | klein in Rg (40–60), groß in AP |
| `task_self_sufficiency` | Rg 150 halten → Bot-Reserve → bremst Ausgaben in Phase 2 | mittel |
| AP-Pool | Rg wird zum Engpass genau dann, wenn AP übrig ist (T30 §9.2: Phase 1 ca. 41 Bau-AP gegen ca. 190 AP Zufluss) | Kern des Zielbilds |

### 5.2 Messgrößen, die die Wirtschaft sichtbar machen

| Messgröße | Inhalt | Ort |
|---|---|---|
| `regolith_sources` je Sol | `harvester`, `mission`, `trade`, `find`, `find_flight`, `event` | `tests/Feature/Playtest/RunReport.php:121-155` |
| `regolith_path_attribution` | Pfad A = Harvester-Topf (inkl. Geologie), B = Mission + Flug, C = Handel | `RunReport.php:541-546` |
| `regolith` je Sol, `regolith_after` im Log | Bestand | `RunReport.php:340`, `OpeningComparison` K6 |
| `rejections` | Zähler je Fehlercode, u. a. `resource_limit` (Platzieren/Ausbau), `repair_no_regolith`, `bar_offer_insufficient_resources` | `RunReport.php:503-509, 632` |
| `ap.inflow`, `ap_unspent`, AP nach Kategorie | AP-Seite | RunReport A30/B1a |
| Sol-Bericht im Spiel | Netto-Rg-Änderung je Sol | `app/Services/SolReportService.php:105-126` |
| Spieler-UI | „≈ N Sole bis Erschöpfung", Warn-Hint < 30 %, Ausweich-Tiles | A25/A26, `OnboardingHintService:514` |
| Dashboard | Charts Rg-Quellen, Summary „Rego Top-Quelle" | `tools/playtest-dashboard.php` |

---

## 6. Lücken und Unklarheiten

| # | Befund | Folge |
|---|---|---|
| L1 | **`regolith_sources.harvester` ist ein Resttopf:** Der Topf ist das Sol-Delta abzüglich Missions- und Flug-Rg. Er enthält deshalb auch Uplink-Lieferungen, Leitstellen-Recycling und den Geologie-Bonus. Fen-Anliegen (Regel `resolve_bar_concern`), Wette-Verluste und Händlerkäufe tauchen in keinem Topf auf; `event` ist immer 0 (`RunReport.php:110-120`). | Die Angabe „> 90 % Harvester" ist vermutlich leicht überhöht (**Annahme**). Für die Neukalibrierung eigene Töpfe `import`, `recycle`, `concern`, `geology` nötig. |
| L2 | **Keine Rg-Rabatte, keine Rg-Boni aus Berater-Rang.** Der Auftrag fragt danach; im Code und in den Plänen gibt es nur AP-Rabatte. | Gestaltungsraum für die Neukalibrierung ist offen. |
| L3 | **`event_ruin` wird nie erzeugt** (nur `ColonySeedDemo`): `mission_harvester_salvage` (Weg B Zweitinstanz) und `mission_ruin_expedition` sind tot. | Zweitinstanz nur über Orin (400–800 Cr, seltenes 1-Sol-Fenster). |
| L4 | **Uplink-Import ohne Mengen- und AP-Grenze** (`max:9999`). | Rg-Knappheit ist ab Uplink Lv1 in Credits auflösbar; in keiner Bilanz (GDD §13.7) berücksichtigt. |
| L5 | **Bergung-Entscheid 2026-10-13 nicht umgesetzt:** Config steht auf 12/16/22 AP, Deckel 4 AP/Sol, `yield_factor` 1,5; Tests pinnen diese Werte. | Pool-Zahlen in dieser Inventur sind Übergangsstand. |
| L6 | **GDD §13.7 veraltet** (Rampen-Zyklusmittel 12,9 Rg/Sol, Hebeltabelle mit Versorgungsfahrt 18,25 Rg/Sol, Zielkolonie 1.795 Rg); A27-Neuherleitung offen. GDD §13.5-Tabelle Pfad C nennt nur Corvan, nicht Uplink-Import und Gäste-Verkauf. | Keine belastbare Soll-Bilanz vorhanden. |
| L7 | **Veraltete Kommentare und Fallbacks:** `ColonyController.php:913-916` sagt „2 Regolith per click" mit Fallback 2 (Config 1); `BarService.php:101, 1376` Fallback-Preise 30/60; `ColonyTileService.php:507` Fallback 160; `config/buildings.php:120-121` „Startbestand 200", `:208` „Rg 90"; T10-Spec nennt Basispreis 30 (heute 25). | Risiko bei fehlender Config, sonst kosmetisch. |
| L8 | **CC-Gates im `game-reference.md` §2** (Krankenstation Lv2, Leitstelle Lv3) weichen vom Seeder ab (Krankenstation `required_building_level` 3, Leitstelle 2). | Welche Quelle gilt, nicht geprüft (**Annahme:** Seeder gilt). |
| L9 | **Handelsvorteil auf Rg-Verkaufsangebote der Gäste:** Laut A13-Regel gilt er für alle Angebote ohne `visit_id`; nicht einzeln verifiziert. | Verkaufspreis kann bis +60 % steigen und damit den Import (35) überholen (**Annahme**, Arbitrage-Risiko). |
| L10 | **Kartenbudget nicht als Config-Größe vorhanden:** Rg-Chance Ring 3 (ca. 50 %) steckt in `resolveTileType()`, nicht in Config; Budget 2.100–2.500 Rg ist eine Handrechnung. | Für „grüner Rasen" sollte das Budget explizit werden. |
| L11 | **K8–K10** (Pfad-Rg-Anteile) aus T30 §10.6 sind in `OpeningComparison` nicht implementiert (nur K11/K12). | **Annahme** (Docblock nennt sie nicht). |
| L12 | **Berater-Außenmissionen** (GDD §13, z. B. Baumeister-Notfallwartung) und Nexus-Boni (GDD §15) sind beschrieben, aber nicht implementiert. | Mögliche spätere Rg-Wirkung (Reparaturersparnis). |
| L13 | **Geplante, noch offene Rg-Hebel:** Nachschub-Signale, Cantina-Gerüchte und -Gutschein, Feldproben, Geröll-Räumen per Geologie, 5 Planetentypen („rocky = Regolith-reich"), Wartungsplan, levelskalierte Reparatur, `f(L)`-AP-Kurve. | Alle mit Zahlen nur als Vorschlag; Status siehe §1.4, §1.9, §2.3. |
| L14 | **Inerte Config-Reste:** `production_curve[27]`, Cantina-Werte für Stufe 4/5, Credits-/Supply-Zeilen in `building_costs`. | Beim Neuaufsetzen entfernen oder dokumentieren. |
| L15 | Ob die Long-Range-Expedition (30–45 Rg, 1 von 3 Loot-Einträgen) im Bot je Rg liefert, ist nicht gemessen. | Kleine, späte Quelle. |
