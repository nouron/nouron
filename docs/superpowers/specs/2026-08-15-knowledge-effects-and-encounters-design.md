# Kenntnis-Effekte + Begegnungen & Gefahren — Design

**Status:** Entwurf, wartet auf Owner-Review.
**Kontext:** Ausgelöst durch PlaytestBot-Balance-Analyse (siehe CHANGELOG 2026-08-14/15): CC erreicht Level 4 typischerweise erst bei Sol 65–91 von ~95 Gesamtlaufzeit — der Flaschenhals ist Timing, nicht Einzelpreise. `docs/GDD.md` §13.5 fordert bereits Paritäts-Hebel für alle drei Ressourcen-Pfade (Analytik/Hangar/Cantina); dieser Review deckt zusätzlich auf, dass 6 von 7 Kenntnissen komplett wirkungslos sind (nur `geology` hat einen aktiven Effekt) und dass GDD §9 "Begegnungen & Gefahren" spezifiziert, aber nie implementiert wurde.

## Ziel

Jede der 7 Kenntnisse bekommt einen thematisch passenden, spielrelevanten Effekt. Damit werden zwei Dinge gleichzeitig erreicht: (1) die Analytik-Labor-Investition lohnt sich über alle Domänen hinweg, nicht nur für `geology`, und (2) mehr Regolith/AP wird früher im Run verfügbar, was CC-Progression beschleunigen soll — ohne den AP-Pool selbst großzügiger zu machen (der ist bewusst knapp, siehe Owner-Entscheidung).

## Bereits vorhandener Ist-Stand (Korrektur zum ersten oberflächlichen Review)

- **`geology` hat bereits einen aktiven, verdrahteten Regolith-Bonus.** `config/game.php:145` → `geology_harvester_bonus_per_level => [1=>3, 2=>3, 3=>2, 4=>2, 5=>2]` (kumulativ 3/6/8/10/12 Rg/Sol), angewendet in `GameTick::harvesterYield()`/`generateHarvesterYield()`, liest den echten Kenntnis-Level aus `colony_researches`. **Bleibt unverändert** — die Kurve ist front-loaded (größter Zuwachs früh), das ist hier bewusst richtig (siehe Kurvenform unten) und nicht Teil dieser Spec.
- **`config/knowledge.php` hat keinen Effekt-Mechanismus** — nur `levelup_costs`, `credits`, `trust_per_lv`, alle bis auf `trust_per_lv` identisch für alle 7 Kenntnisse.
- **`health` (+2 Trust/Lv), `agronomy` (+1 Trust/Lv), `defense` (−1 Trust/Lv)** sind die einzigen weiteren aktiven Effekte, alle über `trust_per_lv`.
- **GDD §9 "Begegnungen & Gefahren" ist spezifiziert, aber nicht implementiert.** Trust-Event-Keys `encounter_lost` und `colony_threatened` existieren nirgends im Code (nur `encounter_won` ist definiert). Kein Zufallsgenerator für Sturm/Geologische Instabilität/Seuche.
- **GDD §13.3 "Boni: additiv, nie multiplikativ"** beschreibt ein Domänen-Bonus-System (Berater-Rang + Kenntnis-Level + Koloniereife senken additiv die AP-Kosten von Bau-/Navigations-/Wirtschafts-Projekten), linear vorgeschlagen (+3 %/Level), **existiert im Code nicht**.

## Global Constraints

- Effekt-Kurven pro Kenntnis sind **glockenförmig** über 5 Level: kleiner Zuwachs bei Lv1 und Lv5, größter Zuwachs in der Mitte (Lv2–4). Ausnahme: `geology` (bereits bestehend, front-loaded, nicht anfassen).
- Alle Kostenreduktionen (Domänen-Bonus) bleiben **additiv, nie multiplikativ** (§13.3, unverändert gültig) und respektieren `project_min_cost_factor = 0.5` als Untergrenze, falls dieser Config-Wert existiert — sonst mit anlegen.
- Keine neue AP-Domänen-Trennung — der Bonus wirkt auf **Projektkosten** (Bau-/Navigations-/Wirtschafts-Projekte), nicht auf einen separaten AP-Pool. Der einheitliche Pool (§13.1) bleibt unangetastet.
- `health` bekommt **keinen** zweiten Effekt — bleibt reiner Trust-Pfad. Bewusste Design-Entscheidung, keine Lücke.
- Kein Kampfsystem, keine Stärkewerte (§9 explizit) — Begegnungen wirken direkt auf `status_points` und Trust, nicht auf eine Gegner-Stärke.
- PHP-Code/Kommentare Englisch, Config-Keys Englisch, `lang/de/*.php`-Werte Deutsch (CLAUDE.md-Standard).
- TDD-Pflicht für jeden neuen Effekt (Service-Test pro Effekt-Typ, der den Bonus bei einem bestimmten Level verifiziert).

---

## 1. Generische Effekt-Kurven-Struktur

Neues Config-Muster in `config/knowledge.php`, pro Kenntnis optional:

```php
'construction' => [
    // ... bestehende Felder unverändert ...
    'effect_type' => 'ap_cost_reduction',
    'domain' => 'construction',           // welche Projekt-Kategorie profitiert
    'effect_per_lv' => [1 => 2, 2 => 4, 3 => 4, 4 => 3, 5 => 2],   // % additiv, Σ15, glockenförmig
],
```

Folgt dem bereits etablierten Delta-Array-Muster (`supply.knowledge_cap_per_level` in `config/game.php`, `colony_zone_expansion`) — kein neuer Formalismus. Kumulierter Effekt bei Level L = Summe der Einträge 1..L (analog `GameTick::cumulativeCurveYield()`, wiederverwendbar).

`effect_type` ist ein geschlossenes Set: `ap_cost_reduction`, `agrardom_yield`, `bar_offer_boost`, `encounter_risk_reduction`, `none` (für `health`/`geology`, die ihren eigenen bestehenden Mechanismus behalten bzw. keinen neuen brauchen).

---

## 2. Domänen-AP-Kostenreduktion (`construction`, `cartography`, `trade`)

**Neuer zentraler Baustein**, da bisher nicht im Code: eine Stelle, die für ein gegebenes Projekt (Gebäude-Levelup ODER Kenntnis-Levelup) die Summe aus Berater-Rang-Bonus + passender Domänen-Kenntnis + Koloniereife (CC-Level) ermittelt und additiv auf `ap_for_levelup` anwendet, gedeckelt durch `project_min_cost_factor`.

**Domänen-Zuordnung** (§13.3, unverändert):
- Bau ← `construction` (wirkt auf Gebäude-Levelups, via `ColonyController::levelupRegolithFor`-Nachbarschaft — dort wird `ap_for_levelup` bereits gelesen)
- Navigation ← `cartography` (wirkt auf Erkundungs-/Tile-Aktionen mit AP-Kosten, z.B. `ColonyTileService`)
- Wirtschaft ← `trade` (wirkt auf handelsbezogene Projekte — aktuell gibt es keine "Wirtschafts-Projekte" mit `ap_for_levelup`; siehe Abschnitt 4 für `trade`s zweiten Effekt, der diese Lücke füllt)

**Kurve pro Kenntnis:** glockenförmig, Summe 15 % bei Lv5 (Owner-Vorgabe aus §13.3-Tabelle bleibt als Zielsumme gültig, nur die Verteilung ändert sich von linear auf Glocke). Beispiel `construction`: `[2, 4, 4, 3, 2]`.

**Owner-Notiz für später (nicht Teil dieser Implementierung):** Freischaltbare Spezialoptionen in Dialogen (z. B. bessere Handelskonditionen bei hohem `trade`) sind eine vorgemerkte Erweiterung für einen Folge-Pass, kein Teil dieser Spec.

---

## 3. `agronomy` — Agrardom-Organika-Bonus (Parität zu `geology`)

Spiegelt `geology_harvester_bonus_per_level` 1:1, eigene Ressource: neuer Config-Key `agronomy_agrardom_bonus_per_level` in `config/game.php`, gleiche Glockenform-Zielgröße (~6–7 Or/Sol kumuliert bei Lv4/5) statt front-loaded — **abweichend von `geology`**, weil dieser Effekt neu eingeführt wird und keine bestehende Kalibrierung/Playtest-Historie hat, die eine front-loaded Kurve rechtfertigen würde. Integration analog `generateHarvesterYield()`: `generateResources()`-Loop in `GameTick.php` liest `agronomy`-Level aus `colony_researches`, addiert den Bonus auf den bioFacility-Output (`production_curve[41]`).

`agronomy` behält zusätzlich `trust_per_lv = 1` unverändert (Nahrungssicherheit als vertrauensbildend, bestehende Design-Entscheidung).

---

## 4. `trade` — Cantina-Angebotsfrequenz/Losgröße

Zweiter Effekt neben dem Domänen-AP-Bonus (Abschnitt 2), um die thematische Nähe zu Cantina zu stiften, ohne mit `advisor_trader` (+15 % Handelsgewinn, Berater-Bonus) zu kollidieren. `trade` verbessert **nicht** den Gewinn pro Angebot, sondern **Verlässlichkeit**: erhöht `guestCount` (Angebotsfrequenz) oder `levelMaxConcurrent` (gleichzeitige Angebote) in `BarService::generateOffersForColony()`, analog zum bereits bestehenden `trader_discount`-Muster (`config("game.bar.trader_discount.{$traderRank}")`).

**Läuft parallel zum separat geplanten Cantina-Pfad-C-Fix** (§13.5: Losgröße an Zahlungsfähigkeit koppeln, Tauschrichtung nach Bestand statt Zufall) — beide Baustellen zusammen spezifizieren, um nicht zweimal an denselben Zahlen zu drehen. Der Pfad-C-Fix ändert *welche* Angebote generiert werden (Richtung/Größe); der `trade`-Kenntniseffekt ändert *wie oft* (Frequenz/Slots). Getrennte Config-Hebel, keine Formel-Überschneidung.

Kurve glockenförmig, Peak Lv2–3, Zielgröße grob +1 zusätzlicher Slot oder +20–30 % Frequenz bei Lv4.

---

## 5. GDD §9 "Begegnungen & Gefahren" — Implementierung

Bisher nur Design, kein Code. Wird jetzt gebaut, weil `defense`s einziger sinnvoller Effekt (Risikominderung bei Zwischenfällen) sonst an einem nicht existierenden System hinge.

**Kernmechanik** (aus §9, unverändert übernommen):
- Kein Kampfsystem, keine Gegner-Stärke — jedes Ereignis wirkt direkt auf `status_points` eines betroffenen Gebäudes + Trust.
- Ausgangsstufen nach SP-Anteil zum Ereigniszeitpunkt: ≥66 % Abgewehrt (`encounter_won`, +2 Trust, minimal SP-Verlust), 33–65 % Beschädigt (`encounter_lost`, −4 Trust, SP-Verlust ~20 % von `max_status_points`), <33 % Kritisch (`colony_threatened`, −5 Trust, SP-Verlust + ggf. Level-Down).
- Drei Gefahrentypen: **Sturm** (zufällig, 1 Gebäude der Colony Zone), **Geologische Instabilität** (gekoppelt an Harvester-Tile, Chance steigt mit Solen seit Relocation, **sinkt mit `geology`**), **Seuchenausbruch** (emergent, nur bei `hunger_streak ≥ 3` oder Trust < −20).
- `securityHub.event_mitigation_pct` (bereits in Config vorhanden, `0.25`) dämpft alle drei Ausgänge um 25 % — **muss jetzt tatsächlich angewendet werden** (aktuell nur Kommentar, keine Anwendung im Code).
- Vorwarnung 1 Sol vorher als `colony_log`-Eintrag, Ausgang beim Sol-Wechsel protokolliert.

**Neuer Bestandteil dieser Spec — `defense`s Rolle:**
`defense` senkt die **Trigger-Chance** von Sturm-Ereignissen (die einzige der drei Gefahrenarten ohne bereits zugewiesene Kenntnis-Abschwächung — Geologische Instabilität hat schon `geology`, Seuche hat `infirmary` als Gebäude-Hebel). Glockenkurve, Zielgröße ~15–20 % Risikoreduktion kumuliert bei Lv4, rechtfertigt den bestehenden Trust-Malus (`trust_per_lv = −1`) narrativ: "Wachsamkeit kostet Vertrauen der Kolonisten, schützt sie aber faktisch."

**Trust-Event-Werte** `encounter_lost` (−4) und `colony_threatened` (−5) müssen neu in `config('game.trust.events')` angelegt werden (bisher fehlen sie komplett, nur in Kommentaren referenziert).

**Kalibrierungs-Hinweis aus dem GDD (übernommen):** Basis-Chancen/Sol und SP-Verlust-Prozentsätze sind Richtwerte, Kalibrierung nach PlaytestBot-Läufen. Cooldown zwischen Ereignissen prüfen, falls Playtest eine Spiral-Häufung von `colony_threatened` zeigt (GDD-eigene Warnung).

---

## 6. `health` — keine Änderung

Bleibt bei `trust_per_lv = 2`, kein zusätzlicher Effekt. Begründung (aus dem Brainstorming): ein zweiter mechanischer Bonus würde die Domänentrennung zwischen den Kenntnissen verwässern (z. B. Decay-Reduktion würde in `construction`s Domäne eingreifen). Der reine Trust-Pfad ist bereits das stärkste Alleinstellungsmerkmal unter den 7 Kenntnissen.

---

## Offene Kalibrierungsfragen (nach erstem Playtest zu klären, nicht vor Implementierung)

- Exakte Glockenkurven-Werte pro Kenntnis (hier nur Beispiele/Zielsummen genannt) — endgültige Zahlen entstehen im Implementierungsplan, analog zum bestehenden Muster "Zahlenvorschlag, erste Fassung" (§13.6).
- `defense`-Risikoreduktion vs. `securityHub.event_mitigation_pct` — beide wirken auf denselben Sturm-Ausgang; Reihenfolge/Stapelung (additiv oder auf Chance vs. auf Schaden wirkend) im Implementierungsplan konkretisieren.
- Ob ein Cooldown zwischen Kolonistengefahren-Ereignissen nötig ist (GDD-eigene, offene Balance-Warnung aus §9).
