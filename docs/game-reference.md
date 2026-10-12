# Nouron — Spielreferenz: Zahlen-Lookup (Stand 2026-09-06; Hangar/Funde/Bergungsflug 2026-10-12)

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
| 12 | `trust` | Vertrauen | Kolonie | Nein | 0 |

---

## 2. Gebäude: Build-Kosten (Initialausbildung)

| Gebäude | CC-Gate | Build-Kosten (Rg / Wk) | Supply-Kosten | Max Level | Max Instanzen |
|---|---|---|---|---|---|
| **Commandcenter (CC)** | — | — | 0 | 5 | 1 |
| **Wohnhabitat** | Lv1 | 40 Rg | 0 | 3 | 6 |
| **Harvester** | Lv1 | — | 2 | 1 | 2 |
| **Agrardom** | 1 + Harv | 70 Rg | 2 | 3 | 1 |
| **Analytik-Labor** | Lv2 | 95 Rg | 6 | 5 | 1 |
| **Krankenstation** | Lv2 | 60 Rg / 25 Wk | 10 | 3 | 1 |
| **Cantina** | Lv2 | 95 Rg | 6 | 3 | 1 |
| **Hangar** | Lv2 | 95 Rg | 6 | 3 | 1 (eine Halle, T30 Schritt 3; Stufe 1–3 flat je 25 Rg) |
| **Religiöse Stätte** | Lv4 | 50 Rg / 15 Wk | 4 | 1 | 1 |
| **Kolonialdenkmal** | Lv5 | 60 Rg / 25 Wk | 2 | 1 | 1 |
| **Leitstelle** | Lv3 | 80 Rg / 25 Wk | 8 | 3 | 1 |
| **Uplink Station** | Lv2 | 80 Rg | 6 | 3 | 1 |
| **Trading Post** | Lv4 | 100 Rg / 25 Wk | 6 | 3 | 1 |

> **Rg** = Regolith, **Wk** = Werkstoffe (compounds)
> Max Level für alle Nicht-CC-Gebäude seit dem Ausbaustufen-System (2026-08-26, `config/buildings.php`). Wohnhabitat: Level 3 **pro Instanz**, 6 Instanzen. Depot/Lagerhalle ist seit 2026-06-22 entfernt.

---

## 3. Gebäude: Levelup & Repair

| Aspekt | Wert |
|---|---|
| **Levelup-Kosten (alle außer CC)** | 25 Rg (flat, `game.build.levelup_regolith_flat`) |
| **Platzieren (Neubau)** | Errichtungskosten + 25 Rg für Stufe 1, in einer Zahlung beim Platzieren (T9, 2026-09-25); Ausbau 0→1 danach nur AP |
| **CC-Levelup-Kosten** | Ziel-Level × 30 Rg |
| **Reparatur pro Punkt (SP)** | 1 Rg / Klick (Gebäude; Schiffe: nur AP, siehe 7b) |
| **Regolith-Startbestand** | 300 Rg (`game.onboarding.start_regolith`, T9-Kalibrierung 2026-10-02: Phase-1-Ende Median Sol ~17–18, vorher 370 → Sol ~12–13) |
| **CC-Lv2 Regolith Total** | 60 Rg |

---

## 4. Gebäude: Decay-Raten & Status-Points

Decay-Rate = **Status Points pro Tick** verloren. Überkapazität beschleunigt den Verfall nicht (A14, 2026-09-24). Level-Down endet bei Stufe 1 (`GameTick::applyLevelDown()`), ein platziertes Gebäude verfällt nie auf Stufe 0.

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
| **agronomy** | 20/28/36/44/52 | 180 AP | +2 | Agrardom +1/+2/+2/+1/+1 Or/Sol |
| **health** | 20/28/36/44/52 | 180 AP | +3 | Seuchenausbruch-Risiko −3/−5/−5/−4/−3% |
| **trade** | 20/28/36/44/52 | 180 AP | 0 | Bar-Slots +0/+1/+1/+0/+0; Handelsvorteil-Preisbonus (alle 3 Kanäle) +2/+3/+3/+2/+2% (Σ12%); Cantina-Verhandlung-Erfolgschance +1/+2/+2/+2/+1%-Punkte (Σ8%-Punkte, nur mit Konsul). Kein Bau-AP-Rabatt mehr (A13, 2026-09-20 — vorher Σ15% additiv mit construction, gestrichen: Handel wirkt auf Preise/Angebote/Verhandlung, nicht auf Bauprojekte). |
| **defense** | 20/28/36/44/52 | 180 AP | +2 | Sturm-Risiko −3/−5/−5/−4/−3% |

> **CC-Level Gate**: Lv4 & Lv5 knowledge erfordern CC Lv4 bzw. Lv5
> **Supply-Cap Bonus**: alle Kenntnisse +3/+5/+5/+4/+3 = 20 max pro Kenntnis

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

**Eine Halle** (`is_instanced` 0, nicht zweimal platzierbar). Stufe n = Schiffsklasse n **und** n Ladebuchten (Ladebucht 1–3). Das **Vorfeld** (Bucht 0) existiert ab Stufe 1, gehört nur der Geschenk-Drohne und zählt nicht als Bucht. Ein Schiff ist inaktiv, wenn Hallenstufe < max(Klassenstufe, Bucht). Stufenkosten unverändert flat 25 Rg. `colony_ships.bay` (NULL = `pending`), `unique(colony_id, bay)`.

**Geschenk-Drohne:** Beim ersten Erreichen von Hangar Stufe 1 steht eine Drohne auf dem Vorfeld, ohne Credits und Schuld; einmal pro Lauf (`HangarService::ensureGiftDrone`, CommLog-Eintrag).

| Schiffs-Reparatur (`game.ship_repair`) | Wert |
|---|---|
| Deckel (`max_status_points`) | 20 SP |
| Pro Klick (`sp_per_click` / `ap_per_click`) | +2 SP für 1 AP |
| Kosten | nur AP (kein Rg, keine Credits), je Schiff über `bay` inkl. Vorfeld, keine passive Dock-Reparatur |

**Rückkehr-Garantie** (`HangarBays::flightReadiness`, ersetzt `dispatch_min_sp_pct` = 25 %): Start nur, wenn `SP − wear_per_sol × 2 × sol_distance > 0`. Fehlercode `ship_too_worn`. Beispiel Drohne Ring 3 (Distanz 2): Verschleiß 6 SP, Start ab 7 SP, Rückkehr mit 1 SP. Gilt für alle Missionen.

**Bergungsflug** (`mission_find_salvage`, `FindFlightService`): nur zu gescannten echten Funden, Schiff Drohne oder Frachter, kein Erfolgswurf (`roll` false), Schwierigkeit `normal` (zählt für `task_expedition_coverage`, nicht gegen `finds.max_open_projects`).

| Ring | Sol-Distanz (`sol_distance_by_ring`) | Nav-AP (2 je Sol) | Organika (3 je Sol) | Dauer (hin und zurück) |
|---|---|---|---|---|
| 2 | 1 | 2 | 3 | 2 Sol |
| 3 | 2 | 4 | 6 | 4 Sol |

Nav-AP wird durch Kartografie gesenkt (`ProjectBonusService`). Ertrag `round(rg × finds.flight.yield_factor)` mit Faktor 1,5, additive Zeilen `base` + `flight_bonus`: klein 4 → 6 Rg, mittel 10 → 15 Rg, groß 18 → 27 Rg. Tragkraft aus `ships.*.find_carry` (Tabelle oben). Kein Ertrag, wenn der Fund beim Eintreffen fehlt (`find_missing`) oder das Schiff den Fund nicht tragen kann; bei SP ≤ 0 unterwegs, Abbruch oder Rückruf bleibt der Fund bestehen und wieder Flugziel.

**Katalog-Änderungen:** `mission_find_salvage` neu. `mission_prospecting_flight` und `mission_deep_survey` (Signalvermessung) gestrichen, Zieltyp `signal_tile` und Belohnung `deep_scan` entfallen. `mission_supply_run` zahlt nur noch Organika (kein Regolith).

**`findAction` (`ColonyTileService`)** ist die einzige Quelle, welche Aktion für eine Fundkachel gilt (Fundkarte zeigt genau eine Aktion plus Begründung):

| Zustand | Aktion | Grund (`find_reason_*`) |
|---|---|---|
| nicht gescannt | scannen | `unscanned` |
| Fehlalarm | keine | `false` |
| Flug unterwegs | keine | `in_flight` |
| kein Hangar (Stufe 0) | Selbstbergung | `self` |
| Selbstbergung begonnen (`salvage_ap_spent` > 0) | Selbstbergung fortsetzen | `grandfathered` (Hangar steht) bzw. `self` |
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
| `nexus` (Direktimport-Preis + Orin) | Handelsposten (Stufe ≥3) + `trade`-Kenntnis (kein Konsul, F5) | Preisnachlass |

Stiller Deckel (nie im UI erklärt, `game.bar.trade_terms.silent_cap`): `bar` 60%, `merchant` 60%, `nexus` 25%.

**Konsul-Rang-Rabatt im `bar`-Kanal** (`game.bar.trader_discount`): Rang 0 → 0%, Rang 1 → 10%, Rang 2 → 20%, Rang 3 → 30%.

**Cantina-Verhandlung** (`BarService::negotiateOffer/negotiateChance`, `game.bar`):
- `ap_cost_negotiate` = 2 (gleich `ap_cost_accept`)
- `negotiate_success_chance` je Konsul-Rang: 0 → 0%, 1 → 60%, 2 → 65%, 3 → 70%; zzgl. `knowledge.trade.negotiate_chance_bonus_per_lv` (Σ8%-Punkte bei Lv5), gedeckelt bei `negotiate_chance_max` = 95%
- `negotiate_bonus` (additiver Aufschlag auf die Get-Seite bei Erfolg): konstant 20% ab Rang 1
- Verkaufslose mit Credits-Erlös (Corvans Organika-Lose) sind Festpreis: kein Handelsvorteil, keine Verhandlung (`trade_terms.fixed_price_offers`)

**Rangskalierte Schiffsverhandlung im Hangar** (`HangarService::consulApDiscountPerAp`, `game.hangar.consul_ap_discount`): Credits-Nachlass pro investiertem Verhandlungs-AP je Konsul-Rang — Rang 1 → 50 Cr/AP, Rang 2 → 60 Cr/AP, Rang 3 → 70 Cr/AP. Risikofrei (kein Fehlschlag), 0 ohne verfügbaren Konsul.

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
| `mission_data_sweep` (Cart Lv1+) | 3 | 9 Or | 8 Research AP |
| `mission_long_range_expedition` (Cart Lv3+) | 5 | 15 Or | 700–1100 Cr / 8–12 Wk / 30–45 Rg (1 pick) |

### Drohne / Frachter
| Mission | Sol-Distanz | Organika-Kosten | **Belohnung** |
|---|---|---|---|
| `mission_find_salvage` (gescannter Fund) | 1 (Ring 2) / 2 (Ring 3) | 3 / 6 Or (+ 2 / 4 Nav-AP) | Fund-Rg × 1,5 (klein 6, mittel 15, groß 27; Frachter für groß), kein Wurf |

### Frachter
| Mission | Sol-Distanz | Organika-Kosten | **Belohnung** |
|---|---|---|---|
| `mission_supply_run` | 1 | 3 Or | 10 Or (nur Organika, T30: kein Regolith mehr) |
| `mission_trade_convoy` (Trade Lv1+) | 3 | 9 Or | 520 Cr + Trade Success (+2 Trust) |
| `mission_aid_transport` | 2 | 6 Or + 10 Or extra | 180 Cr + Encounter Won (+2 Trust) |

### Frachter / Korvette
| Mission | Sol-Distanz | Organika-Kosten | **Belohnung** |
|---|---|---|---|
| `mission_salvage_sweep` (Constr Lv1+) | 4 | 12 Or | 6–10 Wk |
| `mission_ruin_expedition` | 4 | 12 Or | 440 Cr (1x pro Ruin) |
| `mission_harvester_salvage` | 4 | 12 Or | Harvester Instanz #2 (1x pro Ruin) |

### Korvette
| Mission | Sol-Distanz | Organika-Kosten | **Belohnung** |
|---|---|---|---|
| `mission_escort_convoy` | 3 | 9 Or | 560 Cr |
| `mission_perimeter_patrol` (Def Lv1+) | 3 | 9 Or | 440 Cr + Encounter Won (+2 Trust) |

> **Organika-Kosten** (Provisions): base = sol_distance × 3 Oder, mit Knowledge-Scaling −1 pro Level (Floor 1)
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
| `mission_harvester_salvage` | Bergungsauftrag: Förderanlage | Frachter o. Korvette | easy / normal |
| `mission_escort_convoy` | Konvoi-Begleitung | Korvette | normal / hard |
| `mission_perimeter_patrol` | Umkreis-Patrouille | Korvette | normal / hard |

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
| Gebäude-Levelup | construction | *Individuell; Rabatt via construction* |
| Kenntnis-Levelup | research | *20–52 je Ziel-Level* |
| Feld erkunden | navigation | 1–3 (ringabhängig: Ring 1=1, Ring 2=2, Ring 3=3); Rabatt via cartography |
| Handel annehmen | economy | 2 |
| Handel verhandeln | economy | 4 (+ risk) |

---

## 10. Supply-Cap-Formel

Stand Code (`GameTick::calculateSupply()`, Anzeige gespiegelt in `ResourcesService::getSupplyBreakdown()`):

```
Cap = min(
  CC-Beitrag (flach, sobald CC-Stufe ≥ 1)
  + Σ(Stufen aller Wohnhabitat-Instanzen) × Wohnhabitat-Wert
  + Σ je Kenntnis Σ(Bonus je erreichter Stufe)
  , game.supply.cap_max
)
CC-Stufe 0 → Cap = 0
```

### Supply-Cap-Quellen

| Quelle | Rechnung | Config-Key | Wert |
|---|---|---|---|
| Kommandozentrale | flach, unabhängig von der Stufe (nur Stufe ≥ 1) | `buildings.commandCenter.supply_cap` | 10 |
| Wohnhabitat | Stufe × Wert, über alle Instanzen summiert (max. 6 × Stufe 3 × 8 = 144) | `buildings.housingComplex.supply_cap` | 8 je Stufe |
| Kenntnisse | Bonus je erreichter Stufe, je Kenntnis kumuliert | `game.supply.knowledge_cap_per_level` | 3/5/5/4/3 = 20 je Kenntnis |
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

## 10a. Überkapazität / Unterbesetzung (A14)

`config/game.php → overcap` und `trust.events`, Logik in `OvercapService` / `ResourcesService::colonistStatus()`.

| Config-Key | Wert | Bedeutung |
|---|---|---|
| `game.overcap.departure_after_sols` | 3 | Sole mit Obdachlosen, bevor sie abwandern (Abwanderung im Sol danach) |
| `game.overcap.trust_base_malus` | 2 | Vertrauens-Malus am ersten Sol mit Obdachlosen |
| `game.overcap.trust_step` | 1 | +Malus je weiterem ununterbrochenen Sol |
| `game.overcap.trust_cap` | 4 | Deckel des Streak-Malus (Verlauf 2 → 3 → 4) |
| `game.overcap.dismiss_ap_cost` | 8 AP | Kosten „Wegschicken" |
| `game.trust.events.colonists_left` | −3 | einmalig je Abwanderung |
| `game.trust.events.colonists_dismissed` | −2 | einmalig je Wegschicken |

```
Arbeitsplätze = siehe Abschnitt 10 (inkl. Reserve)
abgewandert   = min(glx_colonies.overcap_departed, max(0, Arbeitsplätze − Cap))
anwesend      = Arbeitsplätze − abgewandert
obdachlos     = max(0, anwesend − Cap)
Personalanteil = anwesend / Arbeitsplätze   (1,0 solange niemand abgewandert ist; wirkt nur auf Regolith-/Organika-Produktion)
food_need     = floor(anwesend / game.food.supply_per_eater)
```

DB-Spalten: `glx_colonies.overcap_streak`, `glx_colonies.overcap_departed`. Tick-Reihenfolge: Decay → Produktion → Verpflegung → Begegnungen → `settleLostWorkplaces()` (Stufenverlust nimmt unbesetzte Arbeitsplätze still mit) → Supply-Cap → `advanceStreaks()` (Rückkehr loggen, Streak/Abwanderung) → Vertrauen.

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

## 13. Trust-Events (einmalig, Σ stacking strongest-only)

| Event | Trust-Delta | Trigger |
|---|---|---|
| Gebäude Level-Up | +1 | Jeder Level-Up |
| Gebäude Level-Down | −3 | Decay, SP ≤ 0 |
| Kenntnis Level-Up | +2 | Jeder Level-Up |
| Handel erfolgreich | +2 | Bar-Angebot akzeptiert |
| Handel blockiert | −3 | Bar-Angebot scheitert / abgelehnt |
| Vertrag unterzeichnet | +3 | (zukünftig, nicht implementiert) |
| Organika "gut versorgt" | +2 | Diese Sol: Vorrat ≥ Bedarf |
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
| **Verbrauch pro Tick** | floor(anwesende Kolonisten / 4) Organika (`game.food.supply_per_eater`; anwesend = Arbeitsplätze inkl. Reserve − abgewandert, siehe Abschnitt 10a) |
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

**Relocation**: 1–2 AP pro Hex (GDD §4c: 2 AP/Hex)

**Second Harvester Instance**:
- Gate: CC Lv3 (ownership entitlement)
- Sources:
  - Weg A: Orin (corporate_contact, 400–800 Cr, 30% chance alle 15–25 Sol)
  - Weg B: Mission salvage (1× pro Ruin tile, damaged at 25% SP)

---

## 17a. Fundpool (T30 Pool v1, `game.finds`)

Fester Start-Pool versteckter Signale auf den Außenring-Feldern (Ring 2/3, nur `terrain_empty`), pfadneutral: für alle Eröffnungen gleich, kein Nachschub in v1. Platzierung `FindPoolService` (eigener Seed-Strom, der größte Fund bevorzugt Ring 3).

| Fundtyp | Anzahl im Pool | Regolith (brutto) | Bergungs-AP |
|---|---|---|---|
| `find_false` (Fehlalarm) | 2 | 0 | 0 |
| `find_small` | 3 | 4 | 12 |
| `find_medium` | 1 | 10 | 16 |
| `find_large` | 1 | 18 | 22 |

Pool gesamt: 7 Signale, 40 Rg brutto. Mit Hangar und Flug: 60 Rg (Faktor 1,5).

| Parameter | Wert | Config |
|---|---|---|
| Tiefenscan eines Signals | 2 AP (1 AP mit Uplink-Station Lv2+) | `finds.scan_ap`, `finds.scan_ap_uplink` |
| Bergungs-Deckel | 4 AP je Sol und Projekt | `finds.salvage_cap_per_sol` |
| Offene Bergungsprojekte | max. 2 | `finds.max_open_projects` |

| Bergungsflug (`finds.flight`, T30 Schritt 3) | Wert |
|---|---|
| Ertragsfaktor `yield_factor` | 1,5 (Flug-Bonus = Rundung(rg × 1,5) − rg) |
| Mindest-Hallenstufe `min_hangar_level` | klein 1, mittel 1, groß 2 (muss zu `ships.*.find_carry` passen) |

Ablösungsregel (Details und Grundtabelle in 7b): ohne Hangar Selbstbergung für alle Größen, mit Hangar nur Bergungsflug, begonnene Selbstbergungen laufen zu Ende (Bestandsschutz, keine Erstattung). Ablauf Selbstbergung: Signal (Fehlalarm nicht erkennbar) → Tiefenscan (enthüllt den Fund, Fehlalarm wird bebaubar) → Bergung über `ColonyTileService::salvageFind` (Route `colony.tile.salvage`, AP-Investition bis zum Fund-Preis, Regolith bei Abschluss). Signal-Kacheln sind bis zum Scan nicht bebaubar (`tile_has_find`); ein echter Fund bleibt unbebaubar, bis er vollständig geborgen ist, ein gescannter Fehlalarm wird bebaubar. Bot: Regeln `deep_scan_signal_tile`, `invest_find` (nur ohne Hangar bzw. Bestandsschutz), `repair_ship_for_flight` und `dispatch_find_flight`. Messung: `RunReport` `regolith_sources.find` und `regolith_sources.find_flight`, `game:playtest-compare` K11/K12/K16/K17 (informativ).

---

## 18. Run-Struktur

*Stand: 2026-09-26 (A45 im Code umgesetzt, Werte vorläufig bis zum Abschluss des Kalibrier-Batches).*

| Aspekt | Wert |
|---|---|
| **Tick Limit** | 100 Ticks (default) |
| **Tick Length** | 24 hours (1 Sol = 1 Tick) |
| **Phase 1 Deadline** | Sol 30 (hard fail) |
| **Phase 1 Warning** | Sol 22 (Nexus escalates) |
| **Trust Fail Threshold** | Trust < −20 (instant fail) |
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
| `task_colony_prosperity` | diplomacy | streak | Vertrauen > `threshold` | **`threshold` 40, `target` 12 Sole** | Owner 2026-10-01 bestätigt (8/8 @82–95) |
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
colony_tiles   — Hex-Tile-Daten (Koordinaten, Terraintyp, Ressourcen, Gebäude, Erkundet)
```

---

## 20. Gebäude-Trust & Special Effects

| Gebäude | Trust/Lv | Spezial-Effekt |
|---|---|---|
| Agrardom | 0 | Organisierte Organika-Produktion |
| Cantina | +3/Lv | Verhandlungs-Risiko; +Konsul-Slot |
| Analytik-Labor | 0 | Kenntnis-Forschung; +Analytiker-Slot; Lv2-5: Kenntnis-Levelup-AP −6/−7/−8/−9% (Σ6/13/21/30%) |
| Hangar | 0 | Schiff-Ausbau; +Raumfahrer-Slot |
| Krankenstation | +5/Lv | Seuchenausbruch-Risiko −8% pro Lv (Cap 50%, gemeinsam mit health-Kenntnis) |
| Religiöse Stätte | +3/Lv | Besinnlichkeit (Lv1 only) |
| Kolonialdenkmal | +3/Lv | Stolz (Lv1 only) |
| Leitstelle | +2/Lv | Trust-Event-Mitigation −25%; Recycling 10% build-cost ab Ausbaustufe 3 (Bergungsdienst, `recycle_min_level`) |
| Uplink Station | 0 | Deep-Scan −1 AP (Lv2+); Merchant frequency ↑ |
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
