# Nouron — Game Design Document (GDD)

**Projekt:** Nouron — A Free Space Opera Browsergame
**Status:** aktiv (Stand: 2026)
**Verantwortlich:** Mario Gehnke

---

## Inhaltsverzeichnis

1. [Spielkonzept](#1-spielkonzept)
   - 1.1 [Designprinzipien](#11-designprinzipien)
   - 1.2 [Alleinstellungsmerkmale (USPs)](#12-alleinstellungsmerkmale-usps)
2. [Sol-Zyklus (Tick-System)](#2-sol-zyklus-tick-system)
3. [Ressourcen](#3-ressourcen)
4. [Kolonien & Gebäude](#4-kolonien--gebäude)
   - 4a. [Kolonieoberfläche](#4a-kolonieoberfläche)
5. [Ressourcenproduktion](#5-ressourcenproduktion)
6. [Supply-Generierung](#6-supply-generierung)
7. [Verfall & Entropie](#7-verfall--entropie)
8. [Flotten & Flottenorders](#8-flotten--flottenorders)
   - 8a. [Systemansicht](#8a-systemansicht)
   - 8b. [Hangar-Screen](#8b-hangar-screen)
9. [Begegnungen & Gefahren](#9-begegnungen--gefahren)
10. [Forschung](#10-forschung)
11. [Techtree](#11-techtree)
    - 11.1 [Entitäten-Übersicht](#111-entitäten-übersicht)
    - 11.2 [Abhängigkeitsregeln](#112-abhängigkeitsregeln)
    - 11.3 [Grid-Layout (Techtree-Ansicht)](#113-grid-layout-techtree-ansicht)
    - 11.4 [Legacy-Entitäten (entfernt)](#114-legacy-entitäten-entfernt)
12. [Handel (Trade)](#12-handel-trade)
13. [Berater & Aktionspunkte (AP-System)](#13-berater--aktionspunkte-ap-system)
14. [Moralsystem](#14-moralsystem)
15. [Run-Struktur (Roguelike-Modus)](#15-run-struktur-roguelike-modus)
16. [Onboarding](#16-onboarding)
17. [Progressive Discovery System](#17-progressive-discovery-system)
18. [Run-Ende & Fail-State](#18-run-ende--fail-state)

---

## 1. Spielkonzept

Nouron ist ein rundenbasiertes Weltraum-Strategiespiel für Einzelspieler im Browser. Der Spieler übernimmt die Rolle eines Kolonie-Direktors mit einem klaren Auftrag: eine kleine, ressourcenarme Kolonie auf Vordermann zu bringen — entweder eine frisch gestartete Siedlung oder eine heruntergekommene Anlage, die sich selbst überlassen wurde.

Die Kolonie bleibt im gesamten Spielverlauf überschaubar. Es geht nicht darum, ein galaktisches Imperium aufzubauen, sondern darum, eine kleine Gemeinschaft unter schwierigen Bedingungen am Leben zu erhalten und gedeihen zu lassen.

Das Spiel ist in **Runs** strukturiert: Jeder Run hat ein konkretes Ziel, einen variablen Verlauf und ein klares Ende — Erfolg oder Scheitern. Nouron enthält **Roguelike-Elemente**: variable Aufgaben je Run, zufällige Ereignisse und echte Konsequenzen für Fehlentscheidungen. Runs können wiederholt werden; jeder Run fühlt sich anders an.

Das Spiel läuft auf Basis eines Sol-Zyklus: alle Spielzustandsänderungen werden einmal pro Sol berechnet. Im Solo-Modus löst der Spieler Sole manuell aus; im Multiplayer-Modus feuert der Sol wenn alle Spieler bereit sind — oder nach Ablauf des Timeouts. (Intern: "Tick" — die technische Bezeichnung für den Berechnungszyklus.)

**Technischer Stack (Stand April 2026):** PHP/Laravel Backend, SQLite, Blade-Templates. Frontend: Alpine.js + PicoCSS (neue Screens ab Phase 3b), SVG für Spielfelder (Hex-Grid, Systemkarte), Vanilla fetch() für Server-Calls. Bestehende Screens werden schrittweise von jQuery/Bootstrap migriert.

---

## 1.1 Designprinzipien

### Aufbau vor Konflikt

Nouron erzählt die Geschichte einer kleinen Kolonie, die ums Überleben kämpft — nicht die Geschichte eines aufstrebenden Militärstaats. Die Kolonie hat keine Armee, keine Flottenstützpunkte, keine Kriegsziele. Sie hat eine Korvette, die ab und zu auf etwas Unbekanntes trifft, und einen Trupp Kolonisten, der manchmal in gefährliches Terrain gerät.

Gefahren sind klein und lokal: ein verwaistes Schiffswrack, das gelegentlich Piraten anzieht; eine Minenstation, in der etwas schief gelaufen ist; ein fremdes Schiff, das im System auftaucht und Signale sendet. Diese Begegnungen sind Ereignisse — keine Schlachten.

### Opportunitätskosten statt Verbot

Verteidigung und Schutz sind sinnvolle Optionen im Spiel. Sie kosten jedoch strukturell mehr AP als zivile Aktionen — nicht als Strafe, sondern als Konsequenz: eine Korvette, die patrouilliert, schleppt keine Güter. Ein Raumfahrer, der auf Bewachungsmission ist, erkundet kein neues Terrain.

Navigation-AP werden durch **Raumfahrer** generiert und decken alle Flottenorders ab. Die Differenzierung erfolgt über die AP-Kosten je Order-Typ:

| Order-Typ | Navigation-AP-Kosten |
|-----------|----------------------|
| move (Bewegung) | 1 |
| hold (Halten) | 1 |
| trade (Handel) | 1 |
| join (Anschließen) | 1 |
| convoy (Eskorte) | 1 |
| defend (Verteidigen) | 2 |
| attack (Angriff) | 3 |

Ein Raumfahrer, der 15 AP pro Sol generiert, kann also entweder 15 Handelsmissionen durchführen oder 5 Konfrontations-Orders — die zivile Variante erzeugt dreimal so viele Aktionen.

### Geltungsbereich: spielweites Prinzip

Diese Kostenstruktur gilt für alle Mechaniken mit AP-Kosten. Jede neue Mechanik muss beim Design geprüft werden: Ist die konfrontative Variante teurer als die zivile? Wenn nicht, ist sie nicht balanciert im Sinne der Nouron-Vision.

> Konkret: Neue Schiffstypen mit Kampfwert > 0 sind teurer in Bau-AP als zivile Schiffe vergleichbarer Größe. Defensiv-orientierte Orders kosten mehr als reine Bewegungs-Orders.

---

## 1.2 Alleinstellungsmerkmale (USPs)

Nouron teilt sich das Genre "Browser-Strategiespiel" mit Dutzenden von Titeln. Was Nouron von ihnen unterscheidet, ist kein einzelnes Feature, sondern ein kohärentes Designprinzip: das Spiel ist für Spieler gebaut, die lieber nachdenken als klicken — und die Konsequenzen ihres Handelns über Tage spüren wollen.

### Die sechs Merkmale

**1. Verfall als durchgängiges Systemprinzip**
Gebäude und Flotte verfallen ohne aktive Pflege. Wer seine Kolonie vernachlässigt, verliert sie langsam — nicht durch Gegner, sondern durch Entropie. Der Verfall zwingt zur Priorisierung und macht jeden Sol zu einer echten Ressourcenentscheidung. Kenntnisse verfallen nicht — einmal erarbeitetes Wissen bleibt permanent.

**2. Sol-basiertes Spieltempo (1 Sol = 1 Tag)**
Keine Echtzeit-Hektik. Entscheidungen werden einmal täglich getroffen und einmal täglich ausgeführt. Das Spiel passt sich dem Spieler an, nicht umgekehrt.

**3. Nur eine Kolonie — Tiefe statt Breite**
Kein Ausbreiten über eine halbe Galaxie, kein Micromanagement von zehn Außenposten. Eine Kolonie, ein Direktor — alle Entscheidungen betreffen denselben Ort und dieselbe Gemeinschaft.

> **Außenposten:** Außenposten (nicht Kolonien) sind als Phase-4-Konzept vorgesehen — kein Kolonisierungssystem. Design noch nicht definiert. Der Spieler betreibt im gesamten Spiel genau eine Kolonie; Außenposten wären ressourcenextrahierende Außenstellen ohne eigene Verwaltungsebene.

**4. Roguelike-Elemente im Strategieformat**
Jeder Run hat variable Aufgaben, zufällige Ereignisse und echte Konsequenzen. Das Scheitern ist möglich und lehrreich. Kein Run ist identisch — aber die Kolonie bleibt immer dieselbe Art von Ort.

**5. Kleine, handverlesene Galaxie**
Wenige Systeme, wenige Objekte. Jede Begegnung mit einer anderen Fraktion oder einem Ereignis hat Gewicht. Die Knappheit des Raums ist Teil des Designs.

**6. Schutz als Opportunitätskosten-Entscheidung**
Eine Korvette kann die Kolonie bewachen — aber eine Korvette, die patrouilliert, handelt nicht. Konfrontative Aktionen kosten strukturell mehr AP als zivile. Wer alle Schiffe auf Schutzpatrouille schickt, baut und forscht langsamer. (Ausführlich in §1.1.)

### Der Zusammenhang

Diese Merkmale folgen demselben Grundgedanken: Nouron belohnt Spieler, die ihren Fokus bewusst setzen und mit begrenzten Mitteln das Beste herausholen. Das Vorbild ist nicht das klassische Imperium-4X, sondern das Frontier-Szenario — eine kleine Kolonie, ein begrenzter Horizont, echte Entscheidungen.

> Ein Spieler der acht Stunden täglich spielen will, hat keinen Vorteil gegenüber einem Spieler, der täglich fünf Minuten investiert — aber seine Entscheidungen sorgfältig trifft.

### Vorbilder

- **Reunion** (1994) — stärkster Einfluss: Ein Spieler, ein Heimatplanet, maximale Tiefe statt Breite. Die Cantina, der Alltag der Kolonie, das Gefühl von Ort.
- **FTL: Faster Than Light** (2012) — Vorbild für die Run-Struktur: variables Ziel, zufällige Ereignisse, echtes Scheitern als Teil des Spiels.
- **Catan** (1995) — Vorbild für das Ressourcenmanagement mit Knappheit: jede Ressource hat Gewicht, Tausch ist eine Kernmechanik.
- **Master of Orion** (1993) — Vorbild für das Mini-4X-Grundgerüst und die Kommandopunkte-Mechanik.

---

## 2. Sol-Zyklus (Tick-System)

### Grundprinzip

**Aus Spielerperspektive:** Die Zeiteinheit in Nouron heißt **Sol** — ein Sonnentag auf dem kolonisierten Planeten (NASA-Terminologie, analog zu "The Martian"). Jeder Run zählt ab Sol 1. Entscheidungen werden pro Sol getroffen und ausgeführt. Ein Spieler hat "ab Sol 34" eine Kenntnis erforscht.

**Technisch:** Intern heißt diese Einheit **Tick**. `TickService`, `game:tick`, DB-Spalten und Config-Keys verwenden durchgehend den Begriff "tick". Sol = Tick — dieselbe Einheit, zwei Perspektiven.

Ein **Sol** ist die atomare Zeiteinheit des Spiels. Alle periodischen Spielmechaniken (Ressourcenproduktion, Verfall, Flottenorders) werden einmal pro Sol ausgeführt.

**Alle Spielwerte sind in Solen ausgedrückt** — nicht in Echtzeit-Stunden oder -Tagen. Damit skalieren alle Spielmechaniken automatisch, unabhängig davon wie lang ein Sol in Echtzeit dauert.

### Solo vs. Multiplayer

Das Sol-System funktioniert in beiden Modi identisch — was sich unterscheidet, ist wer den Sol auslöst:

**Solo-Modus (primär):** Der Spieler steuert den Sol selbst. Nach dem Setzen aller Befehle löst er den nächsten Sol manuell aus ("Nächsten Sol starten"-Button) — der Sol feuert sofort. Es gibt kein Warten und keine Echtzeit-Begrenzung. "1 Sol" entspricht einem Spielzug, nicht einer Kalenderdauer.

**Multiplayer-Modus (spätere Phase):** Alle Spieler einer Instanz teilen denselben Sol-Rhythmus. Der Sol feuert, sobald alle Spieler ihren Turn bestätigt haben — oder nach Ablauf des konfigurierten Timeouts, damit kein Mitspieler die Instanz dauerhaft blockieren kann.

| Timeout-Konfiguration | Einsatz |
|-----------------------|---------|
| 12 h | Schnell-Runden |
| 24 h (Standard) | Normales Multiplayer |
| 48 h | Casual / Play-by-Mail |

### Sol-Nummer (Sequenz-Counter)

Die Sol-Nummer ist ein einfacher **Integer-Counter pro Run**, gespeichert in `runs.current_tick`. Sie beginnt bei 0 und wird bei jedem Sol-Trigger atomar um 1 erhöht. Es gibt keinen Bezug zum Unix-Timestamp.

```
runs.current_tick += 1   -- atomar in DB-Transaktion
```

Dies hat drei Konsequenzen:

- **Kein Doppellauf möglich:** Der Increment ist der Guard. Ein zweiter Player-Trigger erhöht `current_tick` auf den nächsten Wert und würde eine neue Berechnung auslösen — CSRF-Schutz und UI-Deaktivierung des Buttons nach Auslösung verhindern das auf Anwendungsebene.
- **fleet_orders-Kompatibilität:** `fleet_orders.tick` bleibt eine einfache Integer-Referenz. Der Filter wechselt von `where('tick', $tickService->getTickCount())` auf `where('tick', $run->current_tick)`.
- **Multiplayer-Erweiterung:** Im Multiplayer löst der Server den Increment aus (alle bestätigt oder Timeout), nicht der Spieler. Keine Architektur-Änderung nötig.

Die bisherige Timestamp-Formel (`floor((timestamp - offset) / 86400)`) und `TickService::calculateTickFromTimestamp()` bleiben im Code, werden im Solo-Modus aber nicht verwendet. Sie dienen als Basis für spätere Multiplayer-Timeout-Berechnung.

### Berechnungsfenster (Multiplayer / Server-gesteuert)

Im Multiplayer-Modus wird der Sol serverseitig automatisch ausgelöst — entweder wenn alle Spieler bestätigt haben oder nach Ablauf des Timeouts. Das Berechnungsfenster ist in `config/game.php → tick.calculation` konfiguriert. Im Solo-Modus ist dieses Fenster ohne Bedeutung.

### Manueller Aufruf (Entwicklung/Tests)

```bash
php artisan game:tick           # berechnet den nächsten Tick für den aktiven Run
php artisan game:tick --tick=N  # erzwingt Tick-Nummer N (nur für Tests)
```

### Implementierung

- Artisan-Command: `app/Console/Commands/GameTick.php`
- Tick-Berechnung: `app/Services/TickService.php`
- Tick-Counter: `runs.current_tick` (DB-Spalte, Integer, pro Run)
- Konfiguration: `config/game.php → tick`
- Alle Schritte eines Ticks laufen in einer einzigen DB-Transaktion (atomar)

### Reihenfolge der Tick-Phasen

| Phase | Beschreibung |
|-------|-------------|
| 1. Hangar | Hangar-Lieferungen abwickeln (Schiff-Bau → docked; abgelaufene Anforderungen) |
| 2. Decay | Gebäude- und Kenntnisverfall (SP-Abzug; Level-Down bei SP ≤ 0) |
| 3. Supply & Ressourcen | Supply-Cap neu berechnen (§6), dann Rohstoffproduktion (Vertrauens-Multiplikator angewendet) |
| 3a. Verpflegung | Kolonie verbraucht Organika (`floor(belegte Supply / 4)`); Vorrat reicht → `well_fed`, sonst Hunger-Streak + eskalierender Vertrauens-Malus (§3, §14) |
| 4. Vertrauen | Vertrauenswert neu berechnen (inkl. Hunger-Malus), `colony_resources` aktualisieren (§14) |
| 5. Beratung & Events | Advisor-Ticks, Bar-Angebote, Händler-Spawn, Run-Checks (Phasen, Objectives, Fail State) |

> Die genaue Schritt-Reihenfolge innerhalb jeder Phase ist in `app/Console/Commands/GameTick.php` (Docblock) kanonisch festgehalten.

---

## 3. Ressourcen

6 Ressourcentypen (Stand Phase 3):

| ID | Name (DE) | Name (EN) | Kürzel | Ebene | Handelbar | Startwert |
|----|-----------|-----------|--------|-------|-----------|-----------|
| 1  | Credits | Credits | Cr | User | Nein | 3000 |
| 2  | Versorgung | Supply | Sup | User | Nein | 10 (CC Lv1, kein Wohnhabitat) |
| 3  | Regolith | Regolith | Rg | Kolonie | Ja | 200 |
| 4  | Werkstoffe | Compounds | Co | Kolonie | Ja | 0 |
| 5  | Organika | Organics | Or | Kolonie | Ja | 0 |
| 12 | Vertrauen | Trust | V | Kolonie | Nein | 0 |

**Credits** und **Supply** werden auf User-Ebene (`user_resources`) geführt, alle anderen auf Kolonieebene (`colony_resources`).

### Ressourcen-Semantik

- **Regolith** — Lokaler Rohstoff: Mondgestein, Silikate, Mineralstaub. Wird vor Ort vom Harvester abgebaut. Primäre Verwendung: Rohbaukosten für Gebäude (außer CC und Harvester). Startwert 200 Rg — narrative Begründung: vor Ankunft des Spielers wurden durch automatisierte Maschinen bereits Ressourcen bereitgestellt (Frontier-Depot).
- **Werkstoffe** — Veredelte Industriegüter: raffinierte Metalle, Legierungen, technische Komponenten. Nicht lokal produzierbar. Quellen: KI-Händler (immer verfügbar, Preis in Credits), Spieler-zu-Spieler-Handel, Events. Verwendung: Schiffbau, High-Tech-Gebäude, Reparaturen.
- **Organika** — Biologische Ressource: Nahrung, Medizin, Biodünger, organische Verbindungen. Entscheidend für Bevölkerung und Vertrauen. Produktionsgebäude: Agrardom (bioFacility). Startwert 0 — wird durch eigene Produktion oder Handel beschafft.
- **Versorgung** — Versorgungskapazität (Nahrung + Energie + Wasser, kombiniert abstrahiert). Kein Rohstoff im klassischen Sinne — definiert die maximale Größe der Kolonie (Cap-Modell, siehe §6).
- **Vertrauen** — Systemmechanik, kein handelbarer Rohstoff (siehe §14).

### Ressourcen-Verwendungsdomänen

| Ressource | Gebäude früh (Rohbau) | Gebäude spät (High-Tech) | Schiffe | Reparatur |
|-----------|----------------------|--------------------------|---------|-----------|
| Regolith | Ja (außer CC + Harvester) | Ja (außer CC + Harvester) | Nein | Ja (2/Klick, außer CC + Harvester) |
| Werkstoffe | Nein | Ja (Akzent 10–25/Stufe) | Nein | Nein |
| Organika | Nein | Nein | Nein | Nein |
| Credits | Ja (immer — Grundkosten) | Ja (immer) | **Ja — nur Credits** | Nur Notreparatur (CC/Wohnhabitat) |
| Supply (Cap) | Gate (freie Cap ≥ supply_cost) | Gate | — | — |

**Ausnahme CC + Harvester:** CommandCenter und Harvester kosten beim Bau kein Regolith — sie sind der Einstiegspunkt der Kolonie und dürfen keinen Ressourcen-Catch-22 erzeugen (Regolith braucht Harvester, Harvester braucht Regolith). Beide sind auch von der Reparatur-Regolith-Kostenpflicht ausgenommen (AP-only) — das hält die Regolith-Quelle selbst immer reparierbar und verhindert eine Decay-Deadlock-Spirale.

**Supply ist kein Stockpile, sondern ein Cap:** „Supply-Kosten" eines Gebäudes = sein laufender `supply_cost`-Unterhalt (§6). Beim Bau wird nichts abgezogen — geprüft wird nur, ob die freie Cap den Bedarf deckt.

> **Designprinzip:** Regolith = lokaler Rohbau (alle Gebäude außer CC/Harvester + laufende Reparatur — der Dauer-Sink, der bis Run-Ende relevant bleibt). Werkstoffe = knapper, importierter High-Tech-Akzent (nicht produzierbar, nur Credits-Import). Organika = biologische Schicht (Versorgung/Verpflegung + Handel — **nicht** Bau/Schiffe; Sinks siehe §3 Organika). Supply = physisches Kapazitäts-Gate. Credits = universeller Tauschstoff + alleinige Schiffskosten.

### Werkstoffe: Singleplayer-Sicherheitsnetz

Im Singleplayer gibt es keinen Spieler-zu-Spieler-Handel. Werkstoffe können **nicht lokal produziert** werden — die Kolonie ist zu klein zum Veredeln. Es gibt drei Bezugswege, die bewusst eine Hierarchie bilden:

1. **Nexus-Direktimport (Sicherheitsnetz, garantiert):** Über die **Uplink-Station Lv1** (eine der aktiven Nexus-Anfragen, siehe §4) kann jederzeit eine beliebige Menge Werkstoffe gegen Credits gekauft werden — deterministisch, immer verfügbar, aber zu einem **festen, spürbar höheren Preis** als der Cantina-Spotpreis (Richtwert: Nexus ~90 Cr/Einheit vs. Cantina-Basis ~60 Cr). Dies ist das Anti-Lock-Netz: ohne diesen garantierten Weg wäre jede Werkstoff-Baukostenanforderung potenziell hart blockierbar.
2. **Cantina / Reisender Händler (opportunistisch, günstiger):** Zufällige, zeitgebundene Kaufangebote zum niedrigeren Marktpreis. Belohnung fürs aufmerksame Spielen, aber **nie garantiert** — daher nie die einzige Quelle.
3. **Events (Bonus):** Liefern Werkstoffe als Bonus, immer mit Wahlmöglichkeit, nie kostenlos und nie als einzige Quelle.

Typische Werkstoffe-Events (immer mit Wahlmöglichkeit, nie kostenlos):
- **Strandetes Frachtschiff** — Bergung kostet Navigation-AP, gibt Werkstoffe
- **Händlerkonvoi in der Nähe** — befristetes Kaufangebot (2 Sole), günstiger als Nexus-Importpreis
- **Trümmerfeld im System** — Flotte entsenden, Werkstoffe heimholen

> **Designprinzip Knappheit:** Werkstoffe sind das „Salz", Regolith das „Mehl". Späte/High-Tech-Gebäude verlangen Werkstoffe nur als **Akzent** (Richtwert 10–25 Einheiten pro Stufe), nie als Hauptkosten — denn jeder Werkstoff ist eine harte Credits-Ausgabe über den Import. Die Knappheit erzwingt eine Credits-Allokations-Entscheidung (Werkstoff-Import vs. Schiffbau vs. Reparaturen), bleibt aber durch den garantierten Nexus-Import planbar statt zum Glücksspiel zu werden.

### Credits-Einnahmen

Credits werden durch vier Quellen erworben:

| Quelle | Beschreibung |
|--------|-------------|
| Koloniebeiträge (Arbeitstitel) | Automatische Abgaben pro Sol — abhängig von der Koloniegröße (Wohnhabitat-Anzahl). Begriff und Mechanik offen — Design-TODO in §14 |
| Galaktischer Rat | Staatliche Subventionen für aktive Kolonien pro Sol (Arbeitstitel: Name noch offen) |
| Handel | Einnahmen aus Handelsrouten beim Verkauf von Regolith / Organika / Werkstoffen |
| Events | Einmalige Gutschriften durch zufällige Ereignisse |

Ausgaben: Berater-Upkeep (§13), Gebäudebaukosten, Schiffsbaukosten, Werkstoffe-Import (KI-Händler).

### Zukünftiger Rohstoff (Phase 4+): Exotics

Ein vierter handelbarer Rohstoff ist für spätere Phasen reserviert: **Exotics** (Arbeitstitel) — seltene Materialien die auf der Heimatkolonie nicht abgebaut werden können. Quellen: Exploration anderer Systeme via Flotte, oder Handel mit anderen Spielern/Fraktionen. Gibt der interstellaren Bewegung einen konkreten wirtschaftlichen Zweck.

### Abgekündigte Ressourcen (konzeptionell entfernt, DB-Cleanup abgeschlossen Mai 2026)

- Wasser (ID 3) — wird durch Versorgung (Supply) abstrahiert; kein eigenständiges Rohstoff-Modell nötig.
- ENrg (ID 6), LNrg (ID 8), ANrg (ID 10) — rassenspezifische Energieressourcen aus dem alten Konzept. Rassen wurden abgekündigt; Supply übernimmt die Energieversorgungsrolle konzeptionell.

> Die IDs 3, 6, 8, 10 wurden per DB-Cleanup-Migration (Mai 2026) entfernt und werden vom Spiel nicht mehr genutzt.

---

## 4. Kolonien & Gebäude

### Gebäude (Phase 3 — vollständige Liste)

11 aktive Gebäude + 3 im Design (Stand Phase 3b):

| ID | Config-Key | Name (DE) | Name (EN) | Max-Level | Voraussetzung |
|----|------------|-----------|-----------|-----------|---------------|
| 25 | commandCenter | Kommandozentrale | Command Center | 5 | — |
| 28 | housingComplex | Wohnhabitat | Residential Habitat | 6 | CC Lv1 |
| 27 | harvester | Harvester | Harvester | — | CC Lv1 |
| 41 | bioFacility | Agrardom | Agrarian Dome | — | CC Lv1 + Harvester Lv1 (**Pflichtgebäude vor CC Lv2**, siehe unten) |
| 31 | sciencelab | Analytik-Labor | Analytics Lab | — | CC Lv2, Teil der **Pfadwahl** (siehe unten) |
| 46 | infirmary | Krankenstation | Medical Station | — | CC Lv2 |
| 52 | bar | Cantina | Cantina | — | CC Lv2, Teil der **Pfadwahl** (siehe unten) |
| 44 | hangar | Hangar | Hangar | — | **CC Lv2** (vormals CC Lv3), Teil der **Pfadwahl** (siehe unten) |
| 32 | temple | Religiöse Stätte | Sacred Site | — | CC Lv4 |
| 50 | monument | Kolonialdenkmal | Colonial Monument | — | CC Lv5 |
| 53 | securityHub | Sicherheits-Hub | Security Hub | 3 | CC Lv3 (Strategen-Pfad, siehe unten) |
| 54 | uplinkStation | Uplink-Station | Uplink Station | 3 | CC Lv2 |
| 55 | tradingPost | Handelsposten | Trading Post | 3 | CC Lv4 |

> **Designentscheidung (2026-06-24) — Agrardom wird Pflichtgebäude vor CC Lv2.** Agrardom war bisher Teil der "Sol-3-Wahlfreiheit" (Cantina/Agrardom/Analytik, alle CC Lv2). Das widersprach der strikten Sol-1/2-Linearität (§16.5): Sol 1/2 garantieren bislang nur Bau- und Erkundungs-AP-Verwendung, keine Ressourcenfluss-Garantie. Ohne Agrardom bliebe Organika auf 0, bis der Spieler — möglicherweise erst Sole nach CC Lv2 — den Agrardom-Pfad wählt; in der Zwischenzeit frisst die Verpflegungsmechanik (§4a "Organika") den nicht vorhandenen Vorrat und der eskalierende Trust-Malus (`TrustService::hungerPenalty`) greift potenziell schon vor der ersten bewussten Wirtschaftsentscheidung. Agrardom wird daher aus der Wahlgruppe herausgelöst und zum **Pflicht-Gate für den CC-Lv2-Ausbau**: Der CC-Levelup-Endpoint prüft zusätzlich zu den AP-Kosten, ob Agrardom ≥ Lv1 gebaut ist. Das ändert nichts an der bisherigen Hint-Logik (`hint_agrardome` lief ohnehin unabhängig von der Wahlgruppe, siehe §16.2 "Agrardom ist unabhängig") — es macht aus einer starken Empfehlung ein hartes Gate.
>
> **Pfadwahl ab Sol 3 (CC Lv2 → Lv4):** Sciencelab, Hangar und Cantina sind alle ab CC Lv2 baubar (Hangar-Gate von CC Lv3 auf CC Lv2 gesenkt), aber **nur eines der drei kann bei CC Lv2 gebaut werden** — die anderen beiden schalten erst bei CC Lv3 bzw. CC Lv4 frei (gestaffelt nach Bau-Reihenfolge, nicht nach Gebäudetyp). Siehe §13 "Pfadwahl & generische Berater-Slots" für das vollständige Modell.
>
> **Sicherheits-Hub (CC Lv3) — Strategen-Pfad:** Der Sicherheits-Hub ist analog zu den drei Pfad-Gebäuden das Gate für den Strategen-Slot (Slot 5). Er ist **nicht Teil der Pfadwahl-Gruppe** (kein Bau-Gate-Zähler), sondern ein separates viertes Infrastrukturgebäude das ab CC Lv3 gebaut werden kann. Beim Bau öffnet der Strategen-Slot — unabhängig davon, welche der drei Pfad-Gebäude der Spieler bereits gewählt hat. CC Lv3 hat **kein Pflichtgebäude** als Voraussetzung (kein Äquivalent zum Agrardom-Gate bei CC Lv2): 90 Regolith + AP-Kosten sind das natürliche Gate.

> **Harvester (Sondergebäude):** Der Harvester unterscheidet sich von allen anderen Gebäuden: Er steht nicht in der Kolonie-Zone, sondern auf einem Ressourcen-Tile in der Exploration Zone. Er produziert passiv je nach Tile-Typ (Regolith oder andere Mineralien). Er kann verlegt werden (Kosten: 1 Construction-AP **pro Hex Distanz**, keine Ressourcenabzüge; Transit-Zeit: **1 Sol flat**, unabhängig von der Distanz — der Harvester produziert im Transit-Sol nicht). Es gibt genau einen Harvester pro Kolonie. Technisch ist er ein Gebäude mit einer `tile_x/tile_y`-Position statt eines Kolonie-Slots.

> **Designentscheidung Harvester-Transit (2026-06-28):** Eine distanzabhängige Transit-Zeit (z. B. 1 Sol pro 2 Hex) wurde geprüft und **verworfen**. Die AP-Kosten skalieren bereits mit der Distanz (1 AP/Hex) und erzeugen damit das gewünschte Planungs-Druckgefühl. Eine zusätzliche Sol-Staffel wäre eine doppelte Strafe für lange Verlegungen und würde im Transit Reparaturen blockieren (Reparatur kostet Regolith — kein Regolith-Zufluss ohne Harvester), was eine unkontrollierte Decay-Spirale riskiert. Der 1-Sol-Stopp ist ausreichend: 1 Sol ohne Regolith-Produktion (= 10 Rg Opportunitätsverlust bei Lv1) bei gleichzeitig bis zu 5 AP-Kosten bei einer Fünf-Hex-Verlegung. Falls der Playtest zeigt, dass Harvester-Verlegungen zu oft ohne Nachdenken passieren, ist der bessere Hebel die AP-Rate (1 AP/Hex erhöhen), nicht die Sol-Downtime.

### Bauregeln: Zone-Trennung

**Kernregel:** Ressource-Tiles und Terrain-Tiles sind strikt getrennt — kein Gebäude darf auf einem falschen Tile-Typ platziert werden.

| Tile-Typ | Harvester | Andere Gebäude |
|----------|-----------|----------------|
| `terrain_empty`, `terrain_hazard` | ✗ nicht erlaubt | ✓ erlaubt |
| `regolith_*` (rich / normal / poor) | ✓ erlaubt | ✗ nicht erlaubt |
| `terrain_impassable` | ✗ | ✗ |

- Der Harvester darf **ausschließlich** auf Ressource-Tiles (`regolith_*`) platziert werden. Terrain-Tiles sind für ihn keine gültige Platzierung.
- Alle anderen Koloniegebäude dürfen **nicht** auf Ressource-Tiles gebaut werden. Nur Terrain-Tiles sind für reguläre Gebäude gültig.
- Diese Regel gilt auch beim Verlegung des Harvesters (neues Ziel muss ein `regolith_*`-Tile sein).

**Begründung:** Regolith-Tiles sind Abbaugebiete — ihre Fläche ist durch den Harvester belegt oder für zukünftigen Abbau reserviert. Würde man dort reguläre Gebäude bauen, würde das Vorkommen dauerhaft verschlossen. Umgekehrt wäre ein Harvester auf Terrain-Tiles sinnlos (keine Rohstoffe).

### Baukosten & Level-Up-Kosten

Der Hex-Bau-Flow zieht Ressourcen ab (canonical source: `config/buildings.php → build_cost` / `regolith_per_levelup`, in die `building_costs`-Tabelle gesynct via `game:sync-config`). Drei getrennte Kosten-Achsen:

**1. Errichten (Tile leer → Level 1, Einmal-Abzug):**
- **Regolith** für alle Gebäude außer CC + Harvester. Richtwerte: früh 40–50 (Wohnhabitat/Agrardom), mittel 60–75 (Cantina/Uplink-Station), spät 80–100 (Analytik-Labor/Hangar/Handelsposten…).
- **Werkstoffe** nur für späte/High-Tech-Gebäude **ab CC Lv3+**, als knapper Akzent **10–25 Einheiten** (nicht als Hauptkosten — jeder Werkstoff ist eine harte Credits-Ausgabe über den Import, §3). Uplink-Station Lv1 ist **werkstofffrei** (sie ist das Import-Gate → Zirkelschluss-Vermeidung). Analytik-Labor und Hangar (beide CC Lv2-Pfadgebäude) sind aus demselben Grund **werkstofffrei**: Die Uplink-Station (Wk-Import-Gate) ist ebenfalls erst ab CC Lv2 baubar — wer ein Pfad-Gebäude mit Wk-Anforderung bauen will, müsste erst Uplink-Station bauen (80 Rg + 6 Supply extra), was Pfad B gegenüber Pfad A/C strukturell benachteiligt. Entscheidung 2026-06-28: alle drei Pfad-Gebäude (CC Lv2) sind Wk-frei.
- **Supply-Gate:** Bau nur möglich, wenn freie Supply-Cap ≥ `supply_cost` des Gebäudes (§6). Kein Abzug — reine Belegungsprüfung.

**2. Level-Up (jedes Level, flach — keine Eskalation):**
- **Regolith = 25 % der Errichtungskosten, fest pro Level** (z. B. Wohnhabitat 10/Lvl, Cantina ~17/Lvl, Analytik-Labor 20/Lvl, Hangar ~22/Lvl). Bewusst keine pro-Level-Steigerung. Abzug erst beim **Abschluss** des Level-Ups (`ap_spend ≥ ap_for_levelup`), nicht pro AP-Klick → AP-Invest bleibt reibungsarm.
- **CC-Upgrade (Sonderfall):** skaliert mit `Ziel-Level × 30` Regolith (Lv2 = 60 … Lv5 = 150) — das CC ist der zentrale Progressionshebel und soll eine bewusste Regolith-Investition bleiben.
- Harvester: Level-Up regolithfrei (Bootstrap-Schutz).

**3. Reparatur (laufender Dauer-Sink):**
- **2 Regolith pro Klick** (+1 SP), zusätzlich zu 1 Construction-AP. Decay läuft bis Run-Ende → Reparatur hält Regolith über den gesamten Run relevant (Errichtungs-/Level-Up-Kosten allein versiegen nach Vollausbau).
- **Hartes Gate:** kein Regolith → Reparatur-Button gesperrt, Tooltip verweist auf Harvester-Reparatur. Kein Negativ-Saldo, kein Schuldensystem.
- **CC + Harvester ausgenommen** (AP-only) → die Regolith-Quelle bleibt immer reparierbar, die Decay-Spirale ist ein erholbarer Rückschlag, kein Hard-Deadlock.

> **Designziel:** Regolith ist das „Mehl" (reichlich, lokal, Dauer-Sink über Bau + Reparatur), Werkstoffe das „Salz" (knapp, importiert, nur als Akzent). Schiffe kosten ausschließlich Credits.

> **Entschieden (2026-06-22):** Ein Resource-Cap-System (Lagerlimit für Regolith/Werkstoffe/Organika) wurde geprüft und **verworfen** — siehe Owner-Entscheidung unter §16 Befund 1. Das Depot-Gebäude (`building_id=30`), das diese Mechanik getragen hätte, ist ersatzlos aus dem Spiel entfernt (Migration `2026_06_22_000001_remove_depot_building.php`). Begründung: Das eigentliche Spielproblem ist Ressourcenknappheit, nicht -überschuss; ein Lagerlimit hätte aktive Produktion bestraft statt belohnt — Widerspruch zum Roguelike-Designprinzip "kein Leerlauf, aktives Spielen wird belohnt". Bei Bedarf (z. B. neue Run-Modifier, die Überschuss als Mechanik nutzen) kann Depot + Cap-System später erneut eingeführt werden.

---

### Sicherheits-Hub (securityHub) — Mechanik

Der Sicherheits-Hub ist ein auf 1 Instanz begrenztes Infrastrukturgebäude (CC Lv3, max. Lv3). Er öffnet beim Bau den **Strategen-Slot** (Slot 5) — analog zu den drei Pfad-Gebäuden die die Slots 2–4 öffnen, aber außerhalb der Pfadwahl-Gruppe: der Hub ist kein Pfadwahl-Kandidat und unterliegt keinem Pfadwahl-Bau-Gate. Er bietet drei unabhängige Effekte:

**Passiv — Vertrauen-Bonus:**
`trust_per_lv = 1` pro Level (Lv1: +1, Lv2: +2, Lv3: +3 Vertrauen kumuliert). Thematisch: "Die Bevölkerung fühlt sich durch Schutzinfrastruktur sicherer." Bewusst niedriger als Bar (+2/Level) und Monument (+2/Level) — Sicherheitsinfrastruktur ist utilitaristisch, kein Wohlfahrtsgut.

**Passiv — Event-Dämpfung:**
Wenn der Hub aktiv ist, werden negative Vertrauensverluste aus Zwischenfällen um **25 %** reduziert (aufgerundet: -3 → -2, -5 → -4). Gilt für die Events `building_level_down`, `encounter_lost` und `colony_threatened`. Implementierungsort: `TrustService` vor dem Anwenden negativer Event-Werte. Thematisch: "Der Hub sorgt nicht dafür, dass Vorfälle ausbleiben — er verhindert, dass sie eskalieren."

**Passiv — Level-Down-Recycling:**
Wenn ein Gebäude durch Decay ein Level verliert, gibt die Kolonie automatisch einen kleinen Ressourcenanteil zurück (handelbare Ressourcen: Regolith, Werkstoffe, Organika). `recycle_pct = 0.10` — 10 % der Baukosten des Gebäudes werden zurückgegeben. Der Wert liegt bewusst deutlich unter dem Reparaturwert, damit kein Anreiz entsteht, Verfall absichtlich zu provozieren.

> **TODO Balance:** Alle drei Effekte (trust-Bonus, Event-Dämpfungs-%, Recycling-%) nach erstem Playtest kalibrieren. Baukosten vorläufig: 80 Rg + 25 Compounds, Supply 8, Decay 0.67. Compounds-Anforderung ist akzeptiert: Hub ist kein Progression-Gate (CC Lv3 hat kein Pflichtgebäude), sondern ein optionaler Resilienz-Baustein. In runs mit schlechtem Trade-Zugang kann der Hub später kommen — das verzögert den Strategen-Slot, blockiert ihn aber nicht dauerhaft.

> **Entfernt (2026-06):** Der frühere Passiveffekt "defend-Order kostet 1 Nav-AP statt 2" wurde mit dem Flotten-/Galaxie-Layer entfernt (§8 GESTRICHEN). Er bleibt als Design-Kandidat für eine spätere Wiedereinführung wenn §8 reaktiviert wird.

---

### Uplink-Station (uplinkStation) — Mechanik

Die Uplink-Station ist das einzige Kommunikationsgebäude der Kolonie — 1 Instanz, Lv1–3. **Ohne Uplink-Station Lv1 sind aktive Nexus-Anfragen gesperrt** (Werkstoff-Direktimport, Handelsschiff anfordern, Verwaltungsanfragen). Eingehende INNN-Nachrichten des Nexus (Milestones, Warnungen) kommen immer an — diese sind nicht abhängig vom Gebäude.

| Level | CC-Voraussetzung | Freischaltet / Effekt |
|-------|-----------------|----------------------|
| 1 | CC Lv2 | Aktive Nexus-Anfragen: **Werkstoff-Direktimport** (gegen Credits, immer verfügbar, fester Preis — siehe §3), Handelsschiff anfordern, Verwaltung |
| 2 | CC Lv3 | Tiefenscan dauert 1 Sol weniger; Reisender Händler erscheint häufiger |
| 3 | CC Lv5 | Run-Abschluss-Aktion: Kolonialbericht senden → Meta-Bonus für nächsten Run |

**Baukosten Lv1:** Ausschließlich Regolith + Credits — keine Werkstoffe, um einen Zirkelschluss zu vermeiden (Werkstoffe über Nexus anfordern setzt das Gebäude voraus).

> **TODO Balance:** Genaue Tiefenscan-Basiskosten und Händler-Erscheinungsrate müssen vor Finalisierung der Lv2-Effekte festgelegt werden. Meta-Bonus für nächsten Run (Lv3) erst konkretisieren wenn Run-Abschluss-Mechanik vollständig ausgearbeitet ist (§15 N4). Vorläufig: 300 Rg + Credits für Lv1, Lv2+ auch Werkstoffe; Supply 6, Decay 0.67.

---

### Handelsposten (tradingPost) — Mechanik

Der Handelsposten ist ein auf 1 Instanz begrenztes Wirtschaftsgebäude (CC Lv4, konkurriert mit Religiöser Stätte um dasselbe Tile-Budget). Er stärkt den Handels-AP-Effizienz und den Nexus-Handelskanal:

**Passiv — Konsul-Effizienz:**
Trade-Orders des Konsuls kosten 1 Economy-AP weniger (Minimum 0). Nur relevant wenn ein Konsul aktiv ist.

**Passiv — Händlerkonditionen:**
Der Reisende Händler bietet bei Anwesenheit eines Handelspostens bessere Preiskonditionen (+10–15 % Handelswert). Konkreter Wert nach Playtest kalibrieren.

> **TODO Balance:** Genaue Baukosten, Decay und Supply nach erstem Playtest festlegen. Vorläufig: 400 Cr + 200 Rg, Supply 6, Decay 0.67. Handelswert-Bonus muss mit dem Konsul-Rang-System abgestimmt werden (kein Stack-Effekt wenn Konsul Experte + Handelsposten).

---

### Status-Punkte

Jedes Koloniegebäude hat ein `status_points`-Feld. Das Maximum (`max_status_points`) ist in der `buildings`-Tabelle hinterlegt. Status-Punkte sinken pro Sol durch Verfall (siehe Abschnitt 7).

**Leveled vs. Instanced Buildings:**

- **Leveled** — ein Objekt auf einem Tile, wird stufenweise ausgebaut (z.B. CC Lv1→5, Harvester, Agrardom). Ein Klick auf das Tile → "Ausbauen".
- **Instanced** — jede Einheit ist ein eigenes Objekt auf einem eigenen Tile (z.B. Wohnhabitat max. 6 Einheiten, Hangar). Jede Instanz kann separat auf Lv1–3 ausgebaut werden und hat eigene Status-Points.

Das Config-Flag `is_instanced` in `config/buildings.php` steuert das Verhalten. In der DB haben Instanced Buildings eine `instance_id` als Teil des zusammengesetzten PK (`colony_id + building_id + instance_id`).

Das UI-Verb ist immer identisch: **"Tile ausbauen"** — ob Leveled oder Instanced darunterliegt, ist ein Implementierungsdetail das der Spieler nicht sieht. "Neues Wohnhabitat bauen" bedeutet: neues Tile mit Instanz Lv1 belegen. "Wohnhabitat ausbauen" bedeutet: bestehende Instanz von Lv1 auf Lv2 heben.

---

## 4a. Kolonieoberfläche

### Darstellung: Hex-Grid

Die Kolonieoberfläche wird als 2D top-down Hex-Grid dargestellt. Die Karte hat immer **3 Ringe** (rings 0–3, gesamt 37 Tiles). Planetentyp und Run-Schwierigkeit beeinflussen die Tile-Qualität (Häufigkeit reicher Vorkommen, Hazard-Dichte), nicht die Kartengröße.

### Zwei Zonen

**Kolonie-Zone** — ein Set von Terrain-Tiles rund um das CC. Hier werden Gebäude gebaut (ausschließlich auf `terrain_empty`/`terrain_hazard`-Tiles). CC-Level-Upgrades fügen der Kolonie-Zone weitere Terrain-Tiles hinzu.

**Exploration Zone** — alle Tiles, die nicht zur Kolonie-Zone gehören. Hier liegen Ressourcenquellen (Regolith-Tiles), Gefahren und Event-Spots. Der Harvester steht hier auf einem Regolith-Tile. Jedes Tile muss einzeln per Navigation-AP erkundet werden (Korvette oder Sonde).

> Zone-Trennung: Reguläre Gebäude nur auf Terrain-Tiles, Harvester nur auf Regolith-Tiles. Siehe §4 "Bauregeln: Zone-Trennung".

### CC-Level und Koloniewachstum

Die Kommandozentrale schaltet durch Level-Upgrades zusätzliche **Terrain-Tiles** in der Kolonie-Zone frei — keine ganzen Ringe, sondern eine feste Anzahl individueller Tiles.

**Freischalt-Logik:** Tiles werden in Ringfolge (Ring 1 zuerst, dann Ring 2, dann Ring 3) und innerhalb eines Rings in fester Reihenfolge (Tile-ID-Reihenfolge) freigeschaltet. Regolith-Tiles (`regolith_*`) und unpassierbare Tiles (`terrain_impassable`) werden dabei übersprungen und zählen nicht — sie bleiben dauerhaft Exploration Zone.

| CC-Level | Neu freigeschaltete Terrain-Tiles | Kolonie-Zone gesamt (kumulativ, ohne CC-Tile) |
|---|---|---|
| 1 | 6 | 6 |
| 2 | 3 | 9 |
| 3 | 3 | 12 |
| 4 | 2 | 14 |
| 5 | 1 | 15 |

**Maximum: 15 Terrain-Tiles** in der Kolonie-Zone (+ CC-Tile = 16 belegte Tiles). Bei vollständigem Ausbau aller anderen Gebäude bleiben je nach Konstellation noch Slots für Wohnhabitate — die Knappheit ist bewusst: Wohnhabitate konkurrieren mit Produktionsgebäuden um denselben Tile-Pool.

> Die konkreten Zahlen (6/3/3/2/1) liegen in `config/game.php → colony_zone_expansion`. Balancing-Anpassungen ohne Code-Änderungen möglich.

**Kein Spieler-Wahlrecht bei der Freischaltung.** Die Expansion ist deterministisch. Die Spielerentscheidung liegt darin, *welches Gebäude* auf *welchen* der freigeschalteten Tiles gesetzt wird — nicht welche Tiles freigeschaltet werden. Das hält die Interaktion auf Mobile einfach (kein tile-selection-Popup beim CC-Levelup).

Ring 1 (6 Tiles direkt um das CC) liefert die ersten 4–6 Colony-Zone-Tiles (sofern nicht alle regolith oder impassable). Der erste Ressourcen-Tile ist garantiert in Ring 1 (fixes Starttemplate, Typ variiert pro Run).

### Startposition

Die CC-Startposition ist pro Run zufällig. Das erzeugt unterschiedliche Ausgangssituationen und trägt zum Roguelike-Charakter bei.

### Sichtbarkeit — zwei getrennte Achsen

**Bebaubarkeit** (`is_colony_zone`) und **Sicht** (`is_explored`) sind entkoppelt — zwei unabhängige Achsen, die der Spieler über zwei verschiedene Verben erlebt:

- **Erschließen** (CC-Level): Die Kommandozentrale macht angrenzendes Gelände *bebaubar* (erweitert die Kolonie-Zone). Sie deckt das Tile **nicht** automatisch auf — ein neu erschlossenes Zone-Tile bleibt im Fog, bis es erkundet oder bebaut wird.
- **Erkunden** (Navigation-AP): Sonde/Raumfahrer lüften den Nebel und finden Ressourcen/Signale. Erkunden ist die einzige Quelle von Tile-Wissen.

Die Nav-AP-Kosten pro erkundetem Tile steigen mit dem Ring (`config/game.php → colony.explore_cost_per_ring`): Ring 1 kostet 1 Nav-AP, Ring 2 kostet 2, Ring 3 kostet 3 (Ring 1 ist ohnehin beim Run-Start bereits automatisch erkundet, der Kostensatz greift praktisch nur für nachträglich erschlossene Tiles). Die Staffelung verlangsamt das vollständige Aufdecken der Karte bewusst — bei pauschal 1 AP/Tile war die Karte bei 6 Nav-AP/Sol nach rund 5 Sols komplett enthüllt, was den Spannungswert des Fog of War zunichtemachte.

Daraus folgt:
- **Kolonie-Zone-Tiles** sind baubar, aber ggf. noch im Fog (`is_colony_zone=1, is_explored=0`). **Bauen auf einem solchen Tile deckt es auf** ("siedeln → sehen"). Der Spieler kann optional vorher per Navigation-AP erkunden, um vor dem Bauen zu sehen, was dort liegt (z.B. Gefahrenzone).
- **Exploration-Zone-Tiles** bleiben Fog of War — einzeln per Navigation-AP aufgedeckt (Ring-gestaffelte Kosten s.o.). Hier liegt der Erkundungs-Lohn (Regolith fürs Harvester-Verlegen, Signale/Funde ab Ring 3).

> Kernregel: **Die CC erschließt nur Gelände — sie siedelt nicht ins Unbekannte.** Erschließen ≠ Erkunden. Frühere Kopplung (CC-Ausbau erkundete Zone-Tiles automatisch) wurde 2026-06 entfernt, weil sie die beiden Achsen für den Spieler ununterscheidbar machte.

> **Offener Designpunkt (2026-06, nicht umgesetzt):** Idee, den Erkundungsradius über die aktuelle Ring-3-Grenze hinaus zu erweitern, um zusätzliche Nav-AP-Sinks für spätere Sols zu schaffen (die Ring-Staffelung allein bremst, erschöpft sich aber irgendwann). Offene Sorge: ein größeres/dichteres Hex-Grid wird auf Mobile schwer navigierbar (Pan/Zoom-Aufwand steigt mit der Tile-Zahl). Vorzugsweise die Tile-Zahl von der Nav-AP-Sink-Zahl entkoppeln statt das Grid zu vergrößern — z.B. Signale/Points-of-Interest in größerer Entfernung ohne zusätzliches Hex-Rendering, oder eine Scan/Survey-Order auf Distanz statt physischer neuer Hexes. Nicht implementiert — nur als Richtung für ein späteres Balance-/Pacing-Update vermerkt.

### Visuelle Zone-Abgrenzung

Die Kolonie-Zone-Grenze ist auf kleinen Karten nicht mehr ein sauberer Ring, sondern ergibt sich aus dem `is_colony_zone`-Flag pro Tile. Das Frontend rendert Colony-Zone-Tiles mit einem warmen Basis-Tint (Farbschema: Weiß/Anthrazit/Rot-Palette), Exploration-Zone-Tiles mit einem kühleren, dunkleren Tint. Der Spieler erkennt die Grenze durch Farbe, nicht durch Position. Regolith-Tiles und impassable Tiles innerhalb der inneren Ringe sind immer Exploration Zone — sie wirken als visuelle "Lücken" in der Colony Zone, was die unterschiedliche Funktion deutlich kommuniziert.

### Tile-Typen und Schwierigkeit

Tile-Typen (z.B. "Reicher Erzknoten", "Armes Vorkommen", "Organik-freies Terrain") beeinflussen die Ressourcenproduktion. Die Schwierigkeit eines Runs steuert die Tile-Qualität: schwieriger Run = schlechtere Vorkommen, keine reichen Erznodes in Ring 1.

### Organika

Organika entsteht nicht auf Tiles (biologische Materialien kommen auf Planeten nicht natürlich vor). Stattdessen produziert der **Agrardom** (Gebäude innerhalb der Kolonie-Zone) Organika passiv pro Sol.

Organika wird **nicht** in Bau- oder Schiffskosten verwendet (§3 Verwendungsmatrix). Ihre Sinks (implementiert):

1. **Verpflegung (laufend, eskalierend):** Die Kolonie verbraucht pro Sol Organika proportional zur belegten Supply (`floor(belegte_Supply / 4)`, Config `game.food.supply_per_eater`). Tick-Reihenfolge: Produktion → Verpflegung → Vertrauen (Schritt 3a). Deckt der Vorrat den Bedarf → `well_fed` (+1 Trust, `game.trust.events.well_fed`), Hunger-Streak zurückgesetzt. Reicht der Vorrat nicht → verfügbarer Rest wird verbraucht, `glx_colonies.hunger_streak` wächst, und der **eskalierende** Trust-Malus `−min(2 + (streak−1), 8)` greift (`TrustService::hungerPenalty`) — kein weicher Einmal-Tick, sondern eine Spirale: weniger Vertrauen → Produktionseinbruch → noch weniger Organika. Sättigung setzt den Streak (und damit den Malus) sofort zurück. Macht den Agrardom zum Pflichtgebäude. `floor(used/4)=0` bei sehr kleiner Frühkolonie → kein Verbrauch, kein Bonus.
2. **Missions-Proviant (einmalig):** Hangar-Dispatch (`HangarService::dispatchShip`) kostet beim Start `sol_distance × 3` Organika (Crew-Verpflegung) **und** `sol_distance × 1` Navigations-AP; bei Mangel an beidem wird die Entsendung blockiert. (Config `game.food.mission_organika_per_sol` / `mission_nav_ap_per_sol`.)
3. **Handel:** Organika ist in der Cantina gegen Credits verkaufbar (`bar.base_prices`).

Drei handelbare Kolonieressourcen (Regolith, Werkstoffe, Organika) erhalten bewusst das Catan-Tauschdreieck — mit nur zwei kollabiert die Handelstiefe.

### Gefahren und Ereignisse

Events können sich auf der Kolonieoberfläche abspielen (z.B. Meteoriteneinschlag auf Tile X, Statusverschlechterung durch Sturm). Gebäude werden nicht zerstört — ihr Status-Punkte-Wert sinkt, Reparatur wird nötig. Die Korvette kann Umgebungsgefahren in der Exploration Zone neutralisieren (kostet Navigation-AP).

### Hex-Grid Koordinatensystem

**Koordinatenmodell:** Axial-Koordinaten (q, r). Jedes Tile wird durch ein Zahlenpaar (q, r) eindeutig identifiziert. Das CC-Tile steht bei (0, 0). Ringzugehörigkeit: `ring = max(|q|, |r|, |q+r|)`.

**Orientierung:** Pointy-top (Spitze zeigt nach oben).

### Tile-Typ-Katalog

Tile-Typen definieren die **Mechanik** eines Tiles — nicht sein Aussehen. Die visuelle Darstellung hängt vom Planetentyp ab (Theme-Schicht, unabhängig vom Tile-Typ). Definitionen in `config/tile_types.php`.

**Terrain-Tiles:**

| Typ-Key | Beschreibung |
|---------|-------------|
| `terrain_empty` | Begehbar, leer, bebaubar |
| `terrain_hazard` | Gefahr — Korvette/Sonde nötig zur Neutralisierung. Wird danach zu `terrain_empty` |
| `terrain_impassable` | Nicht begehbar, nicht bebaubar (Klippen, Abgründe, Lavaströme — je nach Planetentyp) |

> **UI-Render-States (kein DB-Typ):** `terrain_fog` und `terrain_locked` sind keine `tile_type`-Werte in der DB — sie sind visuelle Zustände die das Hex-Grid aus `is_explored` + `is_colony_zone` ableitet. `terrain_fog` = unerkundetes Kolonie-Zone-Tile; `terrain_locked` = unerkundetes Exploration-Zone-Tile. Beschreibung in `docs/lore/tiles.md`.

**Ressource-Tiles (für Harvester):**

| Typ-Key | Ressource | Qualität |
|---------|-----------|----------|
| `regolith_rich` | Regolith | Reich |
| `regolith_normal` | Regolith | Normal |
| `regolith_poor` | Regolith | Arm |

**Event-Tiles** (werden durch Tiefenscan enthüllt — vorher nur als generisches Signal sichtbar):

| Typ-Key | Beschreibung |
|---------|-------------|
| `event_wreck` | Schiffswrack — Bergung möglich |
| `event_ruin` | Ruine (alien/alt) — Kenntnis/Loot |
| `event_bunker` | Vergrabener Bunker — Shelter/Ressourcen |
| `event_probe` | Alte Sonde / Forschungsstation — Tech-Fund |
| `event_crystal` | Kristallformation — seltene Materialien |
| `event_vent` | Thermaler Auslass / Geysir |
| `event_cave` | Höhleneingang — unbekannter Inhalt |
| `event_cache` | Verstecktes Depot — Ressourcen |
| `event_signal` | Schwaches Signal — Unklar/Mysterium |
| `event_anomaly` | Unerklärliche Anomalie — Risiko/Chance |

Ein Tile kann gleichzeitig einen Ressource-Typ und ein Event-Overlay haben (`event_type` nullable). Das Event bleibt bis zum Tiefenscan verborgen — der Ressourcentyp ist nach normalem Scan sichtbar.

### Planetentypen

Fünf Planetentypen, alle im ersten Release (stärkt den Roguelike-Charakter — jeder Run fühlt sich durch den Planetentyp anders an):

| Typ-Key | Name | Schwierigkeit | Charakter |
|---------|------|--------------|-----------|
| `rocky` | Gestein | Mittel | Felsiger Standardplanet, Regolith-reich |
| `desert` | Wüste | Mittel-Schwer | Heiß, staubig, ressourcenarm |
| `ice` | Eis | Mittel | Gefroren, konservierte Strukturen |
| `ocean` | Ozean | Mittel | Inseln/Küsten, hohes Hazard-Potential |
| `volcanic` | Vulkan | Schwer | Aktive Geologie, viele impassable Tiles |

**Event-Pools je Planetentyp:**

| Event | Gestein | Wüste | Eis | Ozean | Vulkan |
|-------|---------|-------|-----|-------|--------|
| `event_wreck` | ✓ | ✓ | ✓ | ✓ | |
| `event_ruin` | ✓ | ✓ | ✓ | ✓ | |
| `event_bunker` | ✓ | ✓ | ✓ | | |
| `event_probe` | ✓ | | ✓ | ✓ | ✓ |
| `event_crystal` | ✓ | | ✓ | | ✓ |
| `event_vent` | | | | | ✓ |
| `event_cave` | ✓ | ✓ | ✓ | | ✓ |
| `event_cache` | ✓ | ✓ | ✓ | ✓ | |
| `event_signal` | ✓ | ✓ | ✓ | ✓ | ✓ |
| `event_anomaly` | ✓ | ✓ | | | ✓ |

`terrain_hazard`-Dichte: gering (rocky/desert) → mittel (ice/ocean) → sehr hoch (volcanic).
`terrain_impassable`-Dichte: gering (rocky/desert) → mittel (ice/ocean) → hoch (volcanic).

Planetentyp und -größe werden in `glx_system_objects.planet_type` und `glx_system_objects.planet_size` gespeichert.

### colony_tiles — Datenbankschema

Jedes Tile der Kolonieoberfläche wird als Zeile in `colony_tiles` gespeichert:

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | PK | |
| `colony_id` | FK → glx_colonies | |
| `q` | integer | Axial-Koordinate |
| `r` | integer | Axial-Koordinate |
| `ring` | integer | 0 = CC-Tile, 1–3 = Ring-Nummer (Karte hat max. 3 Ringe) |
| `tile_type` | string | Primärer Typ, z.B. `regolith_rich` — sichtbar nach normalem Scan |
| `event_type` | string nullable | Event-Overlay, NULL = kein Event — sichtbar erst nach Tiefenscan |
| `is_colony_zone` | boolean | Tile gehört zur Kolonie-Zone (CC-Level-Expansion hat es freigeschaltet). Regolith- und impassable-Tiles sind immer false. |
| `is_explored` | boolean | Normaler Scan (Nav-AP) abgeschlossen |
| `is_deep_scanned` | boolean | Tiefenscan abgeschlossen — enthüllt `event_type` |
| `resource_amount` | integer nullable | Verbleibende Ressourcenmenge |
| `resource_max` | integer nullable | Startwert (Basis für Erschöpfungs-Counter im UI) |

---

## 5. Ressourcenproduktion

### Mechanik

Einmal pro Sol produziert jedes aktive Produktionsgebäude in jeder Kolonie Rohstoffe. Die produzierte Menge ergibt sich aus:

```
produzierte Menge = Gebäude-Level × Rate
```

### Produktionsgebäude (Phase 3)

| Gebäude | building_id | Ressource | resource_id | Rate |
|---------|-------------|-----------|-------------|------|
| Harvester | 27 | Regolith | 3 | 10 pro Level |
| Agrardom | 41 | Organika | 5 | 10 pro Level |

> **Designentscheidung:** Der Harvester produziert Regolith (lokaler Rohstoff), nicht Werkstoffe. Werkstoffe sind veredelte Industriegüter die nicht vor Ort herstellbar sind — sie kommen ausschließlich über Handel, KI-Händler und Events (§3).

> **Harvester-Produktion (Phase 4+):** Geplant ist eine tile-abhängige Rate mit Tile-Boni (z.B. "Reicher Erzknoten" = +50%) und gradueller Erschöpfung. Aktuell (Phase 3): feste Rate `×10/level` identisch zum Agrardom — nach erstem Playtest evaluieren ob Tile-Varianz den Aufwand rechtfertigt.

### Konfiguration

`config/game.php → production`:

```php
'production' => [
    27 => [3 => 10],   // harvester   → Regolith  × 10/level
    41 => [5 => 10],   // bioFacility → Organika  × 10/level
],
```

Neue Produktionsgebäude können ohne Code-Änderung ausschließlich durch Erweiterung dieser Config hinzugefügt werden.

---

## 6. Supply-System (Cap-Modell)

### Modell

Supply ist **kein fliessender Pool**, sondern ein **Kapazitätsdeckel** (Cap-Modell). Kenntnisse erhöhen den Cap. Gebäude (außer CC und Wohnkomplex) belegen Supply dauerhaft. Berater belegen **kein** Supply — sie kosten Credits. **Schiffe belegen kein Supply** — die Flottensize wird durch Hangar-Slots und Tiles begrenzt (siehe unten). Es gibt keine Sol-basierte Supply-Generierung.

```
supply_cap    = CC-Level × 10 + Anzahl-Wohnkomplexe × 8 + Σ(Kenntnisse-Cap-Bonus)
laufende_last = Σ(Gebäude-Kosten)
freies_supply = supply_cap − laufende_last
```

> **Design-Entscheidung (2026-06-08):** Schiffe wurden aus der Supply-Last entfernt. Begründung: Schiffe sind räumlich getrennt von der Kolonie (externe Flotte), thematisch eigenversorgt, und bereits durch Hangar-Slots + Tile-Budget begrenzt. Supply als zweiter Limiter war redundant und thematisch inkonsistent. Flottenausbau wird weiterhin gebremst durch: Credits (Nexus-Kosten), Lieferzeit, und Navigator-AP.

> **Design-Konzept (2026-06-08, nicht implementiert):** Supply könnte langfristig als **Kolonisten-Framing** dargestellt werden — "47 Kolonisten im Einsatz / 60 verfügbar" statt "Supply 47/60". Mechanik bleibt identisch (Cap-Modell), nur die UI-Sprache wird konkreter. Gebäude brauchen dann eine Anzahl Kolonisten zum Betrieb statt einer abstrakten Supply-Zahl. Implementierungsaufwand: minimal (nur Labels + Tooltips). Einzuplanen wenn Supply-Abstraktheit in Playtest als Verständnis-Problem auftaucht.

Eine neue Einheit kann nur gebaut / angestellt werden wenn `freies_supply >= Kosten der neuen Einheit`.

### Supply-Cap-Quellen

| Quelle | Supply-Cap-Beitrag |
|--------|-------------------|
| CommandCenter (25) | **10 pro Level** (max Lv5 → +50) |
| Wohnhabitat (28) | **8 pro Einheit** (max 6 Einheiten → +48) |
| Kenntnisse | **nicht-linear pro Level** (siehe unten) |

**Startsituation:** CC Lv1 = 10, 0 Wohnhabitate → Supply-Cap = **10**. Erster Tutorial-Schritt: Wohnhabitat bauen → Cap springt auf 18.
**Hard-Cap:** 200 Supply.

> **Tile-Budget:** 10 Nicht-CC-Gebäude + 5 Wohnhabitat = 15 Tiles (voll). Wer das 6. Wohnhabitat will, muss ein anderes Gebäude opfern — bewusste Designentscheidung für Knappheit.

> **Designabsicht:** CC-Ausbau und Wohnhabitate sind die primären Cap-Quellen. Kenntnisse liefern einen zusätzlichen Bonus, der den Cap in Richtung 200 schiebt — aber nie alleine reicht. Wer militärisch eskalieren will, muss zuerst zivile Infrastruktur investieren.

### Schiffe und Supply

**Schiffe kosten kein Supply.** Die Flottensize wird durch folgende Limiter gebremst:

| Limiter | Mechanik |
|---------|---------|
| Hangar-Slots | Jede Hangar-Instanz belegt ein Tile; max. Schiffe = Hangar-Instanzen |
| Credits | Nexus-Kosten pro Schiff (Drohne 300, Frachter 500, Korvette 800 Cr) |
| Lieferzeit | Korvette 5 Sole Lieferzeit — kein Sofort-Aufbau möglich |
| Navigator-AP | Flottenorders kosten Raumfahrer-AP — mehr Schiffe = mehr AP-Verbrauch |

> **TODO Balance (Playtest):** Prüfen ob Korvetten-Stacking ohne Supply-Limiter auftritt. Falls ja: Credits/Lieferzeit-Werte verschärfen, nicht Supply-Kosten wieder einführen.

**Schiffe haben keinen passiven Decay.** Wartungsdruck entsteht durch aktiven Einsatz (Schiffs-Verschleiß — siehe §7). `fleet_ships.status_points` sinkt durch Flottenorders, nicht durch Zeitablauf.

> **TODO (Design, Phase 4+):** Sonderfall "Schiffe ohne Hangar" — durch Events, Handelsdeals oder andere Mechaniken könnte der Spieler Schiffe erwerben, die normalerweise nicht im Hangar baubar sind (z.B. erbeutete Fraktionsschiffe, Belohnungsschiffe aus Events). Diese wären per Run einzigartig und ein Roguelike-Element das jeden Durchlauf anders macht. Mechanik (Hangar-Pflicht? Supply-Kosten?) und Balance noch offen — für spätere Phase detailliert ausarbeiten.

### Supply-Kosten Gebäude

**Berater:** kein Supply-Verbrauch — Kosten laufen über Credits (siehe §13).

**CommandCenter und Wohnhabitat:** kein Supply-Verbrauch (sie definieren den Cap).

**Gebäude** (individuelle Supply-Kosten aus Technologie-Tabelle):

| Gebäude | Supply |
|---------|--------|
| Harvester, Agrardom | 2 |
| Kolonialdenkmal | 2 |
| Hangar | 4 (je Instanz) |
| Religiöse Stätte | 4 |
| Cantina | 6 |
| Uplink-Station, Handelsposten | 6 (je) |
| Analytik-Labor, Sicherheits-Hub | 8 (je) |
| Krankenstation | 10 |

> **Pfadwahl-Kostenbalancing (2026-06-28):** Die drei Pfad-Gebäude (Analytik-Labor / Hangar / Cantina) hatten zuvor sehr unterschiedliche Kosten ohne echte Abwägung zwischen den Achsen. Nach erstem Playtest-Feedback (Sol 4/5, "Kosten sehr unterschiedlich") wurde neu ausbalanciert:
>
> | Pfad | Gebäude | Supply (vorher → neu) | Regolith (vorher → neu) | Werkstoffe (vorher → neu) | Charakter |
> |------|---------|----------------------|------------------------|--------------------------|-----------|
> | A | Analytik-Labor | 8 (unverändert) | 80 (unverändert) | 0 (unverändert) | Supply-schwer, Rg-mittel — zahlt in langfristiger Cap-Belegung |
> | B | Hangar | 6 → **4** | 80 → **90** | 25 Wk → **0** | Supply-günstig, Rg-schwer — braucht Regolith-Reserve |
> | C | Cantina | 4 → **6** | 50 → **70** | 0 (unverändert) | Ausgeglichen + Trust-Bonus — war ohne Gegengewicht zu günstig |
>
> Das Ziel: jedes Pfad-Gebäude hat eine Schwachachse (Supply oder Regolith) und eine Stärkeachse. Wer knapp an Supply ist, wählt Hangar; wer wenig Regolith hat, wählt Analytik-Labor; wer Stabilität und Trust braucht, wählt Cantina. Kein Pfad ist dominant.

> Supply-Kosten sind **sol-rate-unabhängig** — sie beschreiben eine permanente Kapazitäts-Belegung, keine Fluss-Größe.

> **Supply als Bau-Gate:** Ein Gebäude kann nur errichtet werden, wenn die freie Supply-Cap (`Cap − belegt`) den `supply_cost` des Neubaus deckt. Es wird **nichts abgezogen** — Supply ist ein Cap, kein Lager. Das ist die „Supply-Kosten"-Achse aus der Verwendungsmatrix (§3): Gebäude kosten Regolith (Abzug) **und** Supply (Cap-Belegung + Gate).

### Kenntnisse als Supply-Cap-Quelle

Kenntnisse **kosten kein Supply** — sie **erhöhen den Cap**. Jede der 7 Kenntnisse hat 5 Level; die Bonus-Progression ist nicht-linear (Glockenform: mittlere Level sind effizienter als Extremwerte). Kenntnisse haben **keinen Decay** — einmal erforschtes Wissen bleibt permanent.

| Level | Cap-Bonus (dieses Level) | Kumuliert |
|-------|--------------------------|-----------|
| 1 | +3 | 3 |
| 2 | +5 | 8 |
| 3 | +5 | 13 |
| 4 | +4 | 17 |
| 5 | +3 | **20** |

**Max aller 7 Kenntnisse:** 7 × 20 = **140 Cap-Bonus**. Zusammen mit CC max (50) und Wohnhabitaten ist der Hard-Cap von 200 erreichbar — aber nicht ohne signifikante Investition.

**Strategische Implikation:** Level 2–3 liefern den besten Cap-pro-AP-Wert. Alle 7 Kenntnisse auf Lv3 (7 × 13 = 91 Bonus) schlägt 3 Kenntnisse auf Lv5 (3 × 20 = 60 Bonus) — Breite lohnt sich mehr als Tiefe.

### Entropie-Übersicht

Die drei Entropie-Vektoren wirken unterschiedlich (Details in §7):

| Entität | Mechanismus | Auslöser | Gegenmaßnahme |
|---------|-------------|----------|---------------|
| Gebäude | Passiver Decay (`decay_rate` SP/Sol) | Zeitablauf | Repair-AP investieren |
| Schiffe | Verschleiß (`wear_per_order` aus config/ships.php) | Aktiver Einsatz (Orders) | Reparatur (1 Construction-AP/Klick) |
| Berater | Burnout-Wahrscheinlichkeit (steigt mit `active_ticks`) | Kumulierte Aktivität | Erholungsphase, Rang-Aufstieg dämpft Risiko |
| Kenntnisse | **kein Decay** — permanentes Wissen | — | — |

### Konfiguration

`config/game.php → supply`:

```php
'supply' => [
    'cap_commandcenter'  => 10,   // building_id 25 — pro Level (max Lv5 → 50)
    'cap_housingcomplex' => 8,    // building_id 28 — pro Einheit
    'cap_max'            => 200,  // absolutes Hard-Cap
    // Kenntnisse: Cap-Bonus nicht-linear pro Level (+3/+5/+5/+4/+3 = 20 max je Kenntnis)
    'knowledge_cap_per_level' => [1 => 3, 2 => 5, 3 => 5, 4 => 4, 5 => 3],
    // Berater kosten kein Supply — Upkeep läuft über Credits (config/game.php → advisors)
    'ship_cost' => [
        85 => 0,   // drone     — unbemannt
        37 => 14,  // corvette
        47 => 6,   // freighter
    ],
],
```

### Supply im Sol (Schritt 7)

`user_resources.supply` speichert den **aktuellen Supply-Cap**. Er wird in Schritt 7 jedes Sols neu berechnet und gesetzt — so spiegelt der Wert immer den aktuellen Gebäudestand wider (z. B. nach einem Level-Down des Wohnkomplexes durch Decay).

Das freie Supply (für Enforcement-Checks) ergibt sich live: `cap − Σ(entity_level × supply_cost)`.

### Abgrenzung der Unterhalts-Mechanismen

| Mechanismus | Was er begrenzt | Zeithorizont | Gegenmaßnahme |
|-------------|----------------|--------------|---------------|
| Supply-Cap | Anzahl Schiffe + Gebäude | permanent | CC ausbauen, Wohnhabitate bauen, Kenntnisse erforschen |
| AP | Aktionen pro Tag | täglich | mehr/bessere Berater |
| Gebäude-Decay | Stand von Gebäuden | täglich | Reparatur-AP investieren |
| Schiffs-Verschleiß | Zustand aktiv genutzter Schiffe | pro Order | Reparatur (1 Construction-AP/Klick) |
| Berater-Burnout | AP-Kapazität bei Überbelastung | probabilistisch | Erholungsphase abwarten |

Diese drei Mechanismen sind bewusst unabhängig voneinander.

---

## 7. Verfall & Entropie

Entropie ist ein übergreifendes Designprinzip: Ohne aktive Pflege degradiert die Kolonie schrittweise. Die drei Entropie-Vektoren sind **Gebäude-Decay**, **Schiffs-Verschleiß** und **Berater-Burnout**. Kenntnisse verfallen nicht — einmal erarbeitetes Wissen bleibt permanent (kein SP-System auf Kenntnissen).

### Gebäude-Decay

### Mechanik

Gebäude verfallen ohne aktive Pflege. Jedes Exemplar hat individuelle Werte für `max_status_points` und `decay_rate` (SP/Sol, intern SP/Tick), die in den Stammdaten-Tabellen (`buildings`) gespeichert sind.

**Fraktionaler Decay:** Die `decay_rate` ist ein Dezimalwert (0.05–0.3 SP/Sol). Pro Sol wird dieser Wert von den `status_points` des Exemplars abgezogen. Ein ganzer SP geht erst verloren, wenn sich genug Verlust akkumuliert hat.

```
Beispiel: max_status_points=5, decay_rate=0.3
  Nach Sol 1: status_points = 4.70
  Nach Sol 2: status_points = 4.40
  Nach Sol 3: status_points = 4.10
  Nach Sol 4: status_points = 3.80  ← erster ganzer SP verloren
```

**Konsequenzen nach Building-Typ:**

| Entität | Typ | Konsequenz bei SP ≤ 0 |
|---------|-----|----------------------|
| Leveled Building (allgemein) | Leveled | Level − 1; status_points reset auf max_status_points; INNN-Ereignis |
| Wohnhabitat | Instanced | **Instanz zerstört** (kein Level zum Abziehen); Supply-Cap sinkt um 8; INNN-Ereignis |
| Hangar | Instanced | **Instanz zerstört**; zugewiesenes Schiff wird **unbrauchbar** (nicht zerstört); INNN-Ereignis |
*(Kenntnis — kein Decay; Kenntnisse haben kein SP-System, siehe §10)*

> **Instanced vs. Leveled:** Leveled Buildings verlieren ein Level und regenerieren SP — sie geben mehrere Chancen. Instanced Buildings (Wohnhabitat, Hangar) haben kein Level: Decay auf 0 zerstört die Instanz sofort. Das macht sie gefährlicher zu vernachlässigen, erlaubt aber bewusst riskantes Spiel (Repair-AP sparen auf eigene Gefahr).

> **Manuelle Reparatur:** kostet 1 Construction-AP **+ 2 Regolith pro Klick** (+1 SP). Hartes Gate — ohne Regolith ist der Reparatur-Button gesperrt. CC und Harvester sind regolithfrei reparierbar (AP-only, Bootstrap-Schutz). Vollständige Kosten-Regeln siehe §4 „Baukosten & Level-Up-Kosten".

> **Notreparatur (CC und Wohnhabitat):** Wenn SP dieser kritischen Strukturen unter einen Schwellwert fällt, wird automatisch eine Notreparatur ausgelöst — kostet Credits statt AP. Verhindert unbeabsichtigten Verlust, nicht aber bewusste Vernachlässigung (Credits müssen vorhanden sein).

> **Hangar-Decay-Detail:** Ein Schiff im zerstörten Hangar bleibt in der Datenbank erhalten — es ist nur deaktiviert. Sobald ein neuer Hangar gebaut oder der alte repariert wird, ist das Schiff wieder einsatzbereit.

> **Schiffe haben keinen passiven Decay.** Schiffs-Verschleiß entsteht durch aktiven Einsatz (Flottenorders), nicht durch Zeitablauf — siehe §7 "Schiffs-Verschleiß".

### Richtwerte (abgeleitet aus Technologie-Tabelle)

Die Technologie-Tabelle enthält für jede Entität einen "Sole bis Verlust"-Wert (ohne Wartung; intern: "ticks_until_lost"). Daraus leitet sich die `decay_rate` ab, wenn `max_status_points` standardisiert wird:

```
decay_rate = max_status_points / ticks_until_lost
```

Mit `max_status_points = 20` als Standard ergeben sich z.B.:

| Entität | Sole bis Verlust (ticks_until_lost) | decay_rate (bei SP=20) |
|---------|-----------------|------------------------|
| Religiöse Stätte (temple) | 10 | 2.0 |
| Cantina (bar) | 20 | 1.0 |
| Harvester, Agrardom | 21 | 0.95 |
| Analytik-Labor (sciencelab) | 21 | 0.95 |
| Krankenstation (infirmary), Hangar | 30 | 0.67 |
| Wohnhabitat (housingComplex) | 45 | 0.44 |
| Kommandozentrale (max Lv5), Kolonialdenkmal | 60 | 0.33 |


> **Sol-Skalierung:** Bei 24 Solen/Tag entspricht "133 Sole" ~5,5 Echtzeit-Tagen. Bei 1 Sol/Tag sind es 133 Tage. Die Sol-Anzahl bleibt gleich — nur die Echtzeit-Dauer ändert sich. Das ist die gewünschte Eigenschaft des Sol-basierten Systems (intern: tick-basiert).

> Konkrete Werte per Migration in die Stammdaten-Tabelle (`buildings.decay_rate`). **Kenntnisse haben kein Decay-System** — `researches.decay_rate` ist für alle `knowledge_*`-Einträge 0 und wird im Tick-Loop übersprungen (GDD §10). **Schiffe haben Decay** — `ships.decay_rate` ist aktiv; Fleet-Schiffe im Kampf nehmen 2× Decay.

**Minimum:** Jede Entität hat mindestens **5 max_status_points**.

> ⚠️ **Gnadenfrist** (kein Decay für neue Schiffe/Gebäude für X Sole): vorerst nicht implementiert. Kann in einer späteren Phase evaluiert werden.

### Schema (implementiert)

Die folgenden Spalten sind im Schema vorhanden und werden vom Decay-System genutzt:

- `buildings`: Spalten `max_status_points INTEGER` und `decay_rate REAL` — Werte aus `config/buildings.php`; Sync via `php artisan game:sync-techs`
- `colony_buildings.status_points REAL` — aktueller Zustandswert des Gebäudes
- `fleet_ships.status_points REAL` — Verschleißzustand des Schiffes (wird durch Orders verbraucht, nicht durch Zeit)

### Konfiguration

`config/game.php → decay`:

```php
'decay' => [
    // Schiffs-Verschleiß: wear_per_order steht in config/ships.php je Schiffstyp
],
```

### Designabsicht

Decay erzwingt regelmäßige AP-Investitionen in Wartung. Inaktive Spieler verlieren schrittweise Infrastruktur und Flotte. Die Kombination aus kleiner decay_rate und fraktionaler Akkumulation bedeutet: nichts bricht sofort — aber vernachlässigte Entitäten degradieren stetig.

---

### Schiffs-Verschleiß

Schiffe verfallen **nicht durch Zeitablauf**, sondern durch aktiven Einsatz. Jede ausgeführte Flottenorder verbraucht Verschleißpunkte des beteiligten Schiffes.

**Mechanik:**

```
fleet_ships.status_points -= wear_per_order (je Schiffstyp aus config/ships.php)
```

Wenn `status_points ≤ 0`, wird das Schiff **deaktiviert** (nicht zerstört). Es bleibt in der Datenbank, ist aber nicht einsatzbereit. Reaktivierung erfordert Reparatur (Fixkosten: 1 Construction-AP pro Klick, siehe unten).

| Schiffstyp | wear_per_order (Richtwert) | Begründung |
|------------|---------------------------|------------|
| drone | 0.05 | Unbemannte Drohne — minimalster Verschleiß |
| korvette | 0.20 | Militärisches Manövrieren — höherer Verschleiß |
| frachter | 0.10 | Routinebetrieb — moderater Verschleiß |

Konkrete Werte stehen in `config/ships.php` je Schiffstyp. Nach erstem Playtest kalibrierbar.

**Kein passiver Decay:** Ein Schiff, das im Hangar liegt und keine Orders erhält, verliert keine `status_points`. Das unterscheidet Schiffs-Verschleiß fundamental von Gebäude-Decay — nur Aktivität kostet.

**Reparatur:** Fixkosten pro Klick — **1 Construction-AP → +2 `status_points`** (`REPAIR_SP_PER_AP`), gedeckelt auf `max_status_points` (20). Gleiche Interaktion wie Gebäude-Reparatur (1 Klick = 1 AP), damit sich „Reparieren" spielweit konsistent anfühlt; der AP-Verbrauch wird vorab als Chip am Button angezeigt. Kein spielergewählter AP-Betrag mehr.

> **Offen:** Zusätzliche Credit-Kosten pro Reparatur (`config/ships.php → repair_cost_per_point`) sind im Design vorgesehen, aber noch nicht implementiert — eigener Balance-Task.

> **Designabsicht:** Schiffe, die viel fliegen, brauchen Wartung. Das erzeugt eine natürliche Kosten-Nutzen-Entscheidung: Aggressive Flottennutzung ist teuer in Construction-AP, die sonst in Gebäude fließen könnten.

---

### Berater-Burnout

Berater können nicht dauerhaft auf Hochtouren laufen. Nach langer Aktivität steigt die Wahrscheinlichkeit, dass ein Berater für eine begrenzte Zeit ausfällt — **Burnout**. Der Ausfall ist nicht garantiert, aber wahrscheinlicher, je länger der Berater ununterbrochen aktiv ist.

**Mechanik (probabilistisch):**

```
burnout_chance(tick) = base_chance × growth_factor^(active_ticks / threshold) × rank_dampener(rank)
```

| Parameter | Wert (Richtwert) | Beschreibung |
|-----------|-----------------|--------------|
| `base_chance` | 0.01 (1%) | Grundwahrscheinlichkeit pro Sol bei Neubeginn |
| `growth_factor` | 1.5 | Multiplikator-Steigerung mit `active_ticks` |
| `threshold` | 50 Sole | Sole bis zur signifikanten Chancensteigerung |
| `rank_dampener(1)` | 1.00 | Junior — keine Dämpfung |
| `rank_dampener(2)` | 0.70 | Senior — 30% weniger Burnout-Anfälligkeit |
| `rank_dampener(3)` | 0.40 | Experte — robuster gegen Burnout |

**Beispiel:** Ein Junior-Berater (rank=1) mit 100 aktiven Solen hat ~`0.01 × 1.5^2 × 1.0 = 2.25%` Chance pro Sol auf Burnout. Ein Experte (rank=3) mit denselben 100 Solen kommt auf ~`0.9%`.

**Was passiert bei Burnout:**
- `unavailable_until_tick = current_tick + recovery_ticks` (Richtwert: 5–15 Sole, abhängig von Rang)
- `active_ticks` wird **zurückgesetzt** (der Berater startet frisch nach der Erholung)
- Der AP-Pool dieses Typs fällt für die Dauer auf den Grundwert (6 AP/Sol)
- INNN-Ereignis: „[Name] benötigt eine Auszeit — [AP-Typ]-Kapazität vorübergehend reduziert."

**Rang-Erholungszeiten:**

| Rang | recovery_ticks (Richtwert) |
|------|---------------------------|
| Junior | 15 |
| Senior | 10 |
| Experte | 5 |

Erfahrenere Berater erholen sich schneller — und haben schon durch den `rank_dampener` eine geringere Burnout-Chance.

**`active_ticks`-Reset:** Nach dem Burnout startet der Zähler bei 0. Das bedeutet: Ein Berater der gerade erholt hat, ist für eine Weile sicher. Burnout-Risiko baut sich langsam wieder auf. Kein "ständiger Burnout" ist möglich.

> **Designabsicht:** Burnout ist ein seltenes, aber echtes Risiko, das den Spieler dazu bringt, einen Backup-Plan für den Ausfall eines Beraters zu haben. Experten sind robuster, aber teurer — das macht Rang-Aufstieg strategisch wertvoller als nur "mehr AP pro Sol".

> **Implementierungsstand:** Die Burnout-Wahrscheinlichkeits-Formel ist noch nicht implementiert. `unavailable_until_tick` existiert in der DB und wird gecheckt; die probabilistische Prüfung folgt nach dem ersten Playtest (Phase 4+).

---

## 8. Flotten & Flottenorders

> ⛔ **GESTRICHEN (2026-06-20, „bis auf weiteres").** Galaxie- und Systemkarte samt Flottenbewegung/-kampf wurden entfernt (Backend, Tabellen `fleets`/`fleet_*`/`glx_system*`, Services). Schiffe existieren weiterhin ausschließlich über den **Hangar** (§8b) inkl. Außenmissionen (Dispatch). Der folgende Abschnitt bleibt als Referenz für eine mögliche spätere Wiedereinführung (Phase 4+) erhalten, beschreibt aber **keinen aktuellen Spielstand**.

### Flottenorders

Flottenbewegungen und -aktionen werden als Orders in der `fleet_orders`-Tabelle gespeichert. Jede Order ist einem Tick zugewiesen und wird beim zugehörigen Tick genau einmal verarbeitet (`was_processed = 1` nach Ausführung).

### Navigation-AP-Kosten je Order-Typ

Jede Flottenorder verbraucht Navigation-AP, die durch Raumfahrer generiert werden (siehe Abschnitt 13). Die AP-Kosten unterscheiden sich bewusst je nach Charakter der Aktion — konfrontative Orders sind teurer als zivile (siehe Abschnitt 1.1, Designprinzip "Aufbau vor Konflikt").

| Order-Typ | Navigation-AP-Kosten | Kategorie |
|-----------|----------------------|-----------|
| move | 1 | zivil |
| hold | 1 | zivil |
| trade | 1 | zivil |
| join | 1 | zivil |
| convoy | 1 | zivil |
| defend | 2 | semi-militarisch |
| attack | 3 | militarisch |

> Die Kostenwerte sind in `config/game.php → fleet.order_costs` konfiguriert. Neue Order-Typen muessen beim Anlegen immer einen Eintrag dort erhalten. Das Verhaltnisprinzip (militarisch >= zivil) darf dabei nicht verletzt werden.

### Move-Order

Bewegt eine Flotte zu Zielkoordinaten `[x, y, spot]` innerhalb eines Sternensystems.

**Bewegungs-Mechanik (Phase 2):**
- Bewegung geschieht über mehrere Sole — die Flotte teleportiert sich nicht sofort
- Geschwindigkeit = `moving_speed` des langsamsten Schiffs in der Flotte (Fallback: 1 Einheit/Sol)
- `FleetService::addOrder()` berechnet den Pfad via `GalaxyService::getPath()` und legt für jeden Sol auf dem Weg eine 'move'-Order an; nur die letzte Order trägt den eigentlichen Order-Typ
- Pro Sol des Weges werden Navigation-AP gesperrt (Gesamtkosten = Wegkosten + Order-Kosten)

**Einschränkungen (bewusste Designentscheidung):**
- Ausschließlich innerhalb eines Sternensystems (gleiche `system_id`)
- Interstellare Bewegung wird **nicht implementiert** — siehe unten

**Datenspeicherung:**
- Koordinaten in `fleet_orders.coordinates` werden als JSON gespeichert (`json_encode`)
- Zusatzdaten für Trade/Attack in `fleet_orders.data` ebenfalls als JSON

Nach Ausführung wird die Position der Flotte (`fleets.x`, `fleets.y`, `fleets.spot`) aktualisiert.
INNN-Ereignis `galaxy.fleet_arrived` wird für den Flottenbesitzer erzeugt.

### Interstellare Bewegung — bewusst nicht implementiert

Flotten operieren ausschließlich im eigenen Sternensystem. Interstellare Bewegung zwischen Systemen wird nicht implementiert.

**Begründung:** Bei einem Scope von einer Kolonie pro Spieler und wenigen Schiffen findet fast alles im eigenen System statt — Erkundung, Ressourcenbergung, Bewachung, PvP. Eine interstellare Bewegungsmechanik würde Komplexität hinzufügen ohne spielerischen Mehrwert für Phase 3.

**Das Sprungtor als narratives Element:** Im System ist ein Sprungtor sichtbar (Galaxiekarte), das theoretisch den Weg zu anderen Systemen öffnen könnte. Es wird nicht benutzt — aber es kann bewacht werden (`defend`-Order). Narrativ: Warum siedelt Nexus ausgerechnet in diesem System? Das Sprungtor deutet eine Antwort an ohne sie zu geben.

**"Gäste von außerhalb"** kommen via Events und Bar — Händler, Schmuggler, Boten aus anderen Systemen erscheinen ohne dass eine Bewegungsmechanik implementiert sein muss.

> **Phase 4+:** Wenn Multiplayer-PvP zwischen Systemen gewünscht wird, kann interstellare Bewegung dann als eigene Mechanik nachgerüstet werden. `GalaxyService::getPath()` unterstützt systemübergreifende Pfade bereits technisch.

### Trade-Order

Transferiert Ressourcen zwischen einer Kolonie und einer Flotte.

| direction | Bedeutung |
|-----------|-----------|
| 0 | Kauf: Kolonie gibt Ressource an Flotte |
| 1 | Verkauf: Flotte gibt Ressource an Kolonie |

- Koloniebestand kann nicht unter 0 sinken (Schutz via `MAX(0, amount - amount)`)
- Flottenbestand kann nicht unter 0 sinken
- INNN-Ereignis `galaxy.trade` wird für den Flottenbesitzer erzeugt

---

## 8a. Systemansicht

> ⛔ **GESTRICHEN (2026-06-20, „bis auf weiteres").** Die Systemkarte (12×12-Grid, Sprungtor, Flottenplatzierung) wurde entfernt. Die Kolonie hat keinen navigierbaren Systemraum mehr und keine Koordinaten. Abschnitt bleibt als Phase-4+-Referenz.

### Darstellung: 2D Top-Down Grid

Die Systemansicht zeigt das gesamte Sternensystem als 2D top-down Darstellung. Das zugrundeliegende Grid (12×12) ist im Normalmodus unsichtbar — es erscheint nur wenn ein Flottenbefehl erteilt wird (Zielauswahl). Planeten, Flotten und Objekte sind Icons im freien Raum.

### Koordinatensystem

Einheitliches **12×12-Grid** (grid_x: 0–11, grid_y: 0–11) für alle Objekte und Flotten auf der Systemkarte. Der Stern steht immer bei **(6,6)** — Mittelpunkt. Alle anderen Objekte werden beim Run-Start prozedural platziert und in `glx_system_objects.grid_x/grid_y` gespeichert. Flotten nutzen dasselbe Koordinatensystem (`fleets.grid_x`, `fleets.grid_y`). Das veraltete `spot`-Feld entfällt.

### Sichtbarkeit

Das gesamte System ist von Beginn an sichtbar — Nexus hat das System vor der Expedition vorab erkundet. Einige Tiles erfordern Detailerkundung.

### Erkundungsstufen

| Stufe | Kosten | Ergebnis |
|-------|--------|---------|
| Scan | 1 Navigation-AP, sofort | Tile aufgedeckt (leer / Ressource / normales Event) |
| Tiefenscan | Mehrere Navigation-AP über mehrere Sole | Verborgener Event-Spot enthüllt (Schiffswrack, Ruine, Versteck) |

### Fixe Objekte (immer vorhanden)

- Stern (1) — immer bei (6,6)
- Heimatplanet + Monde (je Spieler) — prozedural platziert
- Sprungtor (1, narratives Element — nicht nutzbar, kann bewacht werden) — prozedural platziert
- Nexus-Außenposten (1): Basishandel + Verwaltung der Nexus-Schulden — prozedural platziert

### Prozedurale Objekte (variabel pro Run)

Asteroiden, Schiffsfriedhöfe, Event-Tiles — zufällig generiert, tragen zum Roguelike-Charakter bei.

### NPC-Präsenzen

Das System wirkt unbesiedelt und nach Frontier — Begegnungen sind selten aber bedeutsam. Drei Klassen von NPC-Präsenzen:

| Klasse | Stärkewert | Auftreten | Auslöser |
|--------|-----------|-----------|---------|
| **Piratensonde** | 1 | häufig | Zufälliges Event-Tile in der Exploration Zone; erscheint wenn eine Flotte das Tile betritt |
| **Schmugglerfrachter** | 0 | gelegentlich | Bewegt sich durch das System; auslösbar mit `attack`-Order; flieht bei Konfrontation (kein Kampf, aber +Vertrauen für Abwehr) |
| **Schwerer Wächter** | 5 | selten | Bewacht ein hochwertiges Event-Tile (z.B. verlassenes Lager); erscheint nur bei Tiefenscan-Ergebnis mit `danger_high` |

**Encounter-Auslöser:** NPC-Begegnungen entstehen ausschließlich durch Flottenorders — passiv trifft keine Flotte auf NPCs. Ein NPC-Event-Tile wird bei Erkundung (Sonde/Korvette) aufgedeckt; der Spieler entscheidet dann bewusst ob er `attack` oder `defend` ordert oder das Tile ignoriert.

**Erscheinungsfrequenz pro Run:** 3–5 Piratensonden-Events, 1–3 Schmuggler, 0–1 schwere Wächter (prozedurale Verteilung bei Run-Generierung).

### Reisender Händler

Ein reisender Händler erscheint gelegentlich im System für eine begrenzte Anzahl Sole. Er bietet seltene Waren an — keine Standardressourcen, sondern Shortcuts und Chancen die im normalen Spielverlauf nicht erreichbar sind.

**Erscheinungsfrequenz:** Erstmals ab Sol 15–20 (Kolonie soll sich erst etablieren). Danach alle 10–15 Sole zufällig. Ergibt ~6–7 Besuche pro 100-Sol-Run. Ist der Händler weg, ist er weg — Roguelike-Druck.

**Inventar:** 3–4 Items pro Besuch (Mobile-optimiert, kein Scrollen nötig).

**Preisstruktur:** Alles in Credits. Kein Tauschhandel in Phase 3. Exotics/Tausch für Phase 4+ denkbar.

**Schwierigkeitsskalierung:** Höhere Preise auf schwierigeren Runs — nicht schlechteres Sortiment (das wäre frustrierend).

**Item-Kategorien:**

| Kategorie | Beschreibung | Seltenheit |
|-----------|-------------|-----------|
| **AP-Paket (flexibel)** | Sofortiger AP-Schub eines Typs (z.B. +20 Construction-AP) — Spieler wählt beim Kauf wofür er sie ausgibt. Teurer als gezieltes Paket | gelegentlich |
| **AP-Paket (gezielt)** | AP-Schub für ein konkretes Gebäude oder eine Kenntnis — günstiger, aber Ziel ist fixiert | gelegentlich |
| **Schiff** | Gebrauchtes Schiff mit Eigenname — ersetzt ein bestehendes Schiff (Hangar bleibt konstant). Phase 4+: besondere Eigenschaften denkbar | selten |
| **Information** | Alle versteckten Event-Spots im System sofort enthüllt | selten |
| **Einmal-Item** | Reparatur-Kit, Vertrauens-Schub, Credits-Notfallkredit | häufig |
| **Exotics** | Platzhalter Phase 4+ | sehr selten |

### Multiplayer

Im Mehrspielermodus hat jeder Spieler einen eigenen Planeten im selben System. Interaktion findet über Flottenbewegung auf der Systemkarte statt (Handel, Diplomatie). Die Systemkarte ist der gemeinsame Interaktionsraum.

---

## 8b. Hangar-Screen

Der Hangar-Screen ist die Verwaltungsansicht aller Schiffe einer Kolonie. Er wird aktiv sobald mindestens ein Hangar (building_id 44, CC Lv2) gebaut wurde.

### Schiffsakquise — Grundprinzip

Schiffe werden **nicht selbst gebaut**. Die Kolonie verfügt nicht über Werftkapazität — Schiffe kommen ausschließlich von Nexus oder durch externe Ereignisse. Der Hangar ist Anforderungsstelle und Operationsbasis, keine Produktionsstätte.

### Akquise-Pfade

| Pfad | Kosten | Ergebnis |
|------|--------|---------|
| **Nexus-Anfrage (Standard)** | Credits + Lieferzeit (N Sole) | Schiff landet nach N Solen auf `docked` |
| **Nexus-Kredit** | 0 Cr jetzt + Nexus-Schulden ↑ | Schiff sofort verfügbar; Schulden-Risiko (§15) |
| **Konsul-Verhandlung** | Credits (reduziert) + Verhandlungs-AP | Konsul investiert AP explizit → niedrigerer Preis |
| **Event / Händler** | situativ (Wrackbergung, Sonderdeal) | Schiff direkt `docked` oder `pending` |

**Lieferzeiten Nexus-Anfrage** (Richtwerte — nach erstem Playtest kalibrieren):

| Schiffstyp | Lieferzeit |
|------------|-----------|
| Drohne | 1–2 Sole |
| Frachter | 3 Sole |
| Korvette | 5 Sole |

**Nexus-Kredit** erst ab CC Lv2 verfügbar. Nutzung erzeugt kleinen Trust-Abzug ("Die Kolonisten machen sich Sorgen über wachsende Schulden").

### Schiffs-Besitz-Modell

Hangare sind **operationale Slots** — nur ein Schiff pro Hangar-Instanz kann entsendet werden. Darüber hinaus können Schiffe **ohne Hangar-Zuweisung** existieren (`hangar_instance_id = NULL`, `ship_state = 'pending'`):

- Entsteht durch Wrackbergung, Händler-Kauf oder Nexus-Lieferung wenn kein freier Hangar-Slot vorhanden
- Sichtbar im Hangar-Screen als separater Bereich "Nicht zugewiesen" mit Decay-Countdown
- Verfällt automatisch nach N Solen (TickService) wenn nicht einem Hangar zugewiesen
- **Decay-Zeit:** nach Playtest kalibrieren (Richtwert: 5 Sole)

Mehrere Schiffe desselben Typs sind erlaubt. Die natürliche Begrenzung ergibt sich aus drei Faktoren: Koloniebauplatz, Supply-Kosten des Hangars und Credits für Nexus-Anfragen. Kein Hard-Cap nötig.

### Karten-States (Carousel)

| State | Beschreibung | Aktion |
|-------|-------------|--------|
| Leer | Slot verfügbar | Nexus-Anfrage starten |
| Lieferung (`building`) | Schiff unterwegs von Nexus | Wartet N Sole |
| Angedockt (`docked`) | Schiff einsatzbereit | Entsenden / Reparieren |
| Unterwegs (`dispatched`) | Schiff auf aktiver Mission | Zurückrufen / Missionslog |

Nicht zugewiesene Schiffe (`pending`) erscheinen als separate Karten am Ende des Carousels mit sichtbarem Decay-Timer.

### Missionslog

Jede abgeschlossene Mission wird in `colony_hangar_missions` gespeichert (Zielkoordinaten, Sol-Distanz, Ergebnis). Im Screen einsehbar.

### UI-Buttons

| Button | Zustand | Funktion |
|--------|---------|---------|
| Nexus anfragen | Leer | Schiffstyp wählen, Akquise-Pfad wählen |
| Entsenden | `docked` | Flottenorder erteilen |
| Zurückrufen | `dispatched` | Schiff zurückrufen |
| Reparieren | `docked`, SP < max | Repair-Order (Construction-AP) |
| Hangar zuweisen | `pending` | Schiff einem freien Hangar-Slot zuordnen |

### Technischer Stack

Alpine.js + PicoCSS. Carousel-Logik in `public/js/carousel.js`, Styles in `public/css/carousel.css`.

---

## 9. Begegnungen & Gefahren

Die Kolonie existiert nicht im Vakuum. Im System gibt es vereinzelte Präsenzen — Piraten, fremde Sonden, verlassene Stationen — die gelegentlich zu Zwischenfällen führen. Diese Begegnungen sind keine Schlachten; sie sind Ereignisse mit Konsequenzen.

### Arten von Begegnungen

**Erkundungsbegegnungen (Drohne/Korvette):** Eine Drohne stößt auf etwas Unbekanntes — ein Schiffswrack, ein Signal, eine verlassene Station. Ergebnis: INNN-Ereignis, mögliche Ressource oder Gefahr.

**Zwischenfälle im System:** Ein fremdes Schiff kreuzt den Orbit. Eine Korvette kann es mit einer `defend`- oder `attack`-Order konfrontieren — oder ignorieren. Die Entscheidung hat Konsequenzen für Vertrauen und Supply.

**Kolonistengefahren:** Lokale Gefahren auf der Kolonieoberfläche (Sturm, Einsturz, Seuchenausbruch) — keine Schiffe beteiligt, kein AP-Verbrauch, sondern Event-getrieben.

### Konfrontations-Ablauf

Wenn eine `attack`-Order ausgelöst wird (z.B. gegen eine Piratensonde):

1. Die Korvette bewegt sich zu den Zielkoordinaten
2. Alle fremden Schiffe an diesen Koordinaten werden als Gegner identifiziert
3. Stärken werden verglichen
4. Verluste werden anteilig verteilt
5. INNN-Ereignis `galaxy.combat` wird erzeugt

### Stärkewerte der Schiffstypen

```
Stärke einer Flotte = Σ(Schiffanzahl × Stärkewert des Typs)
```

| Schiff | ship_id | Stärkewert |
|--------|---------|------------|
| Drohne | 85 | 0 |
| Korvette | 37 | 3 |
| Frachter | 47 | 0 |

Schiffe mit Stärkewert 0 sind **nicht-kampffähig** und werden im Zwischenfall nicht zerstört. Drohnen können jedoch durch nahe Konfrontationen verloren gehen.

> Der absolute Stärkewert der Korvette ist erst relevant wenn NPC-Schiffe eigene Stärkewerte erhalten (z.B. Piraten-Sonde = 1, schwerer Wächter = 5). Bis dahin bestimmt der Wert nur die Verlustquote gegen NPC-Begegnungen.

### Verlustberechnung

```
Verlustquote A = Stärke B / Gesamtstärke
Verlustquote B = Stärke A / Gesamtstärke
Gesamtstärke   = Stärke A + Stärke B
```

```
Verluste = ceil(Anzahl × Verlustquote)
```

Sinkt eine Schiffsklasse auf 0 oder darunter, wird der Eintrag aus `fleet_ships` gelöscht. Haben beide Seiten keine kampffähigen Schiffe (Gesamtstärke = 0), findet keine Konfrontation statt.

### Konfiguration

`config/game.php → combat.ship_power`:

```php
'combat' => [
    'ship_power' => [
        85 => 0,   // drone
        37 => 3,   // corvette
        47 => 0,   // freighter
    ],
],
```

Neue Schiffstypen und deren Stärkewerte werden ausschließlich in dieser Config konfiguriert.

---

## 10. Kenntnisse (ehem. Forschung)

7 Wissensgebiete — kein akademisches Studium, sondern praktisches Kolonialwissen, das durch Analyse-AP (Analytiker-Berater) erarbeitet wird:

| Key | Name (DE) | Name (EN) |
|-----|-----------|-----------|
| construction | Bautechnik & Materialverarbeitung | Construction & Materials Processing |
| cartography | Kartografie & Erkundung | Cartography & Exploration |
| geology | Geologie & Rohstoffgewinnung | Geology & Resource Extraction |
| agronomy | Agronomie & Kultivierung | Agronomy & Cultivation |
| health | Gesundheit & Wohlbefinden | Health & Wellbeing |
| trade | Handel & Logistik | Trade & Logistics |
| defense | Verteidigung & Überlebenstaktik | Defence & Survival Tactics |

### Level-Modell ohne Decay

Kenntnisse verwenden das **Level-Modell (Lv1–5)** — identisch zu Gebäuden, aber **ohne Decay**. Einmal erforschtes Wissen bleibt permanent. Es gibt keinen SP-Verfall auf Kenntnissen — das wäre thematisch unlogisch (Wissen verfällt nicht). Die natürliche Begrenzung erfolgt über AP-Knappheit und Rundenstruktur.

Jedes Level wird durch Investition von Analytiker-AP erarbeitet. AP-Kosten steigen mit dem Level (steigende Glockenform). Die strategische Entscheidung: Breite (viele Kenntnisse auf Lv2–3) vs. Tiefe (wenige Kenntnisse auf Lv4–5).

### Zwei Effekt-Ebenen

Jede Kenntnis hat:

- **Primäreffekt** — aktiv sobald freigeschaltet, unabhängig von Beratern (z.B. Supply-Cap-Bonus, Vertrauenseffekt)
- **Sekundäreffekt** — nur aktiv wenn die Kenntnis einem Berater zugewiesen ist; variiert je nach Berater-Typ

Beispiele für Sekundäreffekte (konkrete Werte folgen nach erstem Playtest):

| Kenntnis | Berater | Sekundäreffekt |
|----------|---------|----------------|
| geology | advisor_engineer | −10% Gebäudekosten |
| geology | advisor_trader | +10% Rohstoff-Verkaufspreis |
| health | advisor_scientist | +1 Analyse-AP/Sol |
| defense | advisor_pilot | −1 AP-Kosten für Angriff |
| trade | advisor_trader | +15% Handelsgewinn |
| cartography | advisor_pilot | +1 Bewegungsreichweite |

> **TODO Design:** Vollständige 7×5-Matrix (alle Kenntnisse × alle Berater) ausarbeiten — nach erstem Playtest, wenn klar ist welche Kombinationen strategisch interessant sind.

### Berater-Zuweisung

Freigeschaltete Kenntnisse können einem Berater zugewiesen werden (UI: Drag & Drop). Der Sekundäreffekt der Kenntnis wird durch den zugewiesenen Berater bestimmt.

**Slots je Berater nach Rang:**

| Rang | Kenntnis-Slots |
|------|----------------|
| 1 | 0 |
| 2 | 1 |
| 3 | 1 |

Rang-Aufstieg schaltet bei Rang 2 den Slot frei; Rang 3 erhöht den Slot nicht weiter (dafür steigt der AP-Bonus — §13).

**Max. aktive Sekundäreffekte:** 5 (je ein Slot pro Berater, wenn alle auf Rang 2+). Bei 7 Kenntnissen und 5 Slots muss der Spieler 2 Kenntnisse ohne Sekundäreffekt lassen — das erzeugt echte Spezialisierungsentscheidungen.

> **Balancing-Notiz:** Slot-Anzahl und Kenntnisanzahl sind Ausgangswerte für den ersten Playtest. Nach Erfahrungen aus dem Betrieb können zusätzliche Kenntnisse und/oder ein zweiter Slot bei Rang 3 eingeführt werden.

### Roguelike-Variabilität

Pro Run ist nicht der vollständige Kenntnisbaum verfügbar — nur eine zufällige Teilmenge (z.B. 5 von 7). Das erzeugt unterschiedliche Spezialisierungspfade ohne das System komplexer zu machen, analog zum variablen Spielfeld bei Catan.

> **TODO Implementierung:** Run-Mechanik mit zufälliger Kenntnisauswahl — ausstehend für Phase 3 Run-Struktur (§15).

### Kolonisten-Ausbildung (Design-Konzept, Phase 4+)

> **Status:** Design-Idee, nicht beschlossen, nicht implementiert. Einzuplanen nach erstem Playtest.

Statt Kenntnisse zu leveln (AP → Kenntnis Lv1→5) würden Kenntnisse durch **Ausbildung von Kolonisten** verbreitet:

- **Berater als Lehrer:** Ein Berater investiert AP → Kolonist erlernt eine Kenntnis. Kosten: Berater-AP + optional Credits.
- **Kolonisten als Träger:** Jeder Kolonist kann 2–3 Kenntnisse halten (Breite begrenzt durch Kolonistenanzahl, Tiefe durch Berater-AP).
- **AP-Generierung durch Kolonisten:** Je mehr Kolonisten eine Kenntnis haben, desto mehr AP generiert die Kolonie in dieser Disziplin. Kolonisten liefern 1 AP/Sol pro Kenntnis (Minions); Berater liefern mehr AP und aktivieren Sekundäreffekte (Bosse).

**Offene Fragen vor Implementierung:**
- Schleifenpotenzial: AP investieren → Kolonisten ausbilden → mehr AP. Hartes Cap notwendig.
- Wie viele Kenntnisse pro Kolonist? Gleichzeitig aktiv oder Umschulung nötig?
- Was passiert mit Kolonisten in Encounters / Events — können sie verloren gehen?
- Wie grenzt sich Berater-Rolle von Kolonisten-Rolle ab wenn beide AP liefern?
- Kolonisten-Zahl: automatisch durch Wohnhabitate oder aktiv anwerben (Credits/Nexus)?

**Verhältnis zum bestehenden Kenntnisse-System:** Würde Level-Modell (Lv1–5) ersetzen oder ergänzen. Erst nach Playtest-Feedback entscheiden ob der Umbau den Gewinn rechtfertigt.

### Supply-Cap-Bonus (Primäreffekt, bleibt erhalten)

Jede Kenntnis erhöht den Supply-Cap stufenweise mit jedem Level. Der Bonus ist nicht-linear — mittlere Level sind effizienter als Extremwerte (Glockenform). Details und Zahlenwerte in §6 und `config/game.php → supply.knowledge_cap_per_level`.

| Level | Cap-Bonus (dieses Level) | Kumuliert |
|-------|--------------------------|-----------|
| 1 | +3 | 3 |
| 2 | +5 | 8 |
| 3 | +5 | 13 |
| 4 | +4 | 17 |
| 5 | +3 | **20** |

Maximum aller 7 Kenntnisse auf Lv5: 7 × 20 = **140 Cap-Bonus**. In der Praxis lohnt sich Breite (viele Kenntnisse auf Lv2–3) mehr als Tiefe (wenige auf Lv5).

Bestimmte Kenntnisse beeinflussen auch das Vertrauen der Kolonie (agronomy, health, defense) — Details siehe §14.

---

## 11. Techtree

Der Techtree ist die Verwaltungsansicht aller ausbaubaren Entitäten einer Kolonie: Gebäude, Kenntnisse, Schiffe und Berater. Er ist kein linearer Forschungsbaum, sondern ein **überschaubares Abhängigkeitsgitter** — die Kommandozentrale (CC) ist das einzige globale Gate, das den Fortschritt reguliert.

Das Designziel: Ein Spieler soll in 30 Sekunden verstehen, was er bauen kann und warum etwas noch gesperrt ist. Kein Micromanagement, keine Forschungsketten die Monate dauern.

---

### 11.1 Entitäten-Übersicht

Die folgende Tabelle listet alle Entitäten im Techtree.

#### Gebäude

Grid-Koordinaten (phasen-lokal) siehe §11.3.

| Key (intern) | Name (DE) | Voraussetzung | Max-Level |
|---|---|---|---|
| `commandCenter` | Kommandozentrale | — | 5 |
| `housingComplex` | Wohnhabitat | CC Lv 1 | 6 Instanzen |
| `harvester` | Harvester | CC Lv 1 | supply-limitiert |
| `bioFacility` | Bio-Anlage | Harvester Lv 1 | supply-limitiert |
| `sciencelab` | Analytik-Labor | CC Lv 2 | supply-limitiert |
| `bar` | Bar / Cantina | CC Lv 2 + Wohnhabitat Lv 1 | supply-limitiert |
| `infirmary` | Krankenstation | CC Lv 2 | supply-limitiert |
| `hangar` | Hangar | CC Lv 2 (Pfadwahl) | supply-limitiert |
| `securityHub` | Sicherheits-Hub | CC Lv 3 (Strategen-Pfad) | max. Lv 3 |
| `uplinkStation` | Uplink-Station | CC Lv 2 | max. Lv 3 |
| `temple` | Religiöse Stätte | CC Lv 4 | supply-limitiert |
| `tradingPost` | Handelsposten | CC Lv 4 | max. 1 Instanz |
| `monument` | Kolonialdenkmal | CC Lv 5 | supply-limitiert |

Die 13 Gebäude decken alle Spielsäulen ab: Infrastruktur (CC, Wohnhabitat), Produktion (Harvester, Bio-Anlage), Wissenschaft (Analytik-Labor), Flotte (Hangar), Kommunikation (Uplink-Station), Sicherheit (Sicherheits-Hub), Handel (Handelsposten), Wohlfahrt (Bar, Krankenstation, Religiöse Stätte, Denkmal).

#### Kenntnisse

Die 7 Kenntnisse sind das einzige Forschungssystem. Alle setzen das Analytik-Labor voraus. Zusätzlich gelten funktionale Gebäude-Voraussetzungen je nach Kenntnis.

| Key (intern) | Name (DE) | Voraussetzung | Max-Level |
|---|---|---|---|
| `construction` | Bautechnik | Analytik-Labor Lv 1 | 5 |
| `agronomy` | Agronomie | Analytik-Labor Lv 1 + Bio-Anlage Lv 1 | 5 |
| `health` | Gesundheit | Analytik-Labor Lv 1 + Krankenstation Lv 1 | 5 |
| `cartography` | Kartografie | Analytik-Labor Lv 1 + Hangar Lv 1 | 5 |
| `geology` | Geologie | Analytik-Labor Lv 2 + Harvester Lv 1 | 5 |
| `trade` | Handel & Logistik | Analytik-Labor Lv 2 + Bar Lv 1 | 5 |
| `defense` | Verteidigung | Analytik-Labor Lv 3 + Hangar Lv 2 | 5 |

**Begründung:** Das Analytik-Labor als Gate für alle Kenntnisse stellt sicher, dass der Spieler zuerst eine Wissenschaftsbasis aufbaut, bevor er Spezialkenntnisse erschließt. Die zusätzlichen Gebäude-Voraussetzungen verknüpfen jede Kenntnis mit dem passenden Kolonieteil — Agronomie braucht eine Bio-Anlage, Kartografie einen Hangar, Verteidigung ein höheres Analytik-Labor und einen ausgebauten Hangar. Die Kenntnisse Lv4 und Lv5 sind zusätzlich durch das CC-Level gegattet (siehe §11.2 Regel 3).

> **Roguelike-Variabilität:** Pro Run steht nicht der vollständige Kenntnisbaum zur Verfügung — nur eine zufällig gezogene Teilmenge (z.B. 5 von 7). Details in §15 (Run-Struktur).

#### Schiffe

Drei semantisch klare Typen: Drohne erkundet, Frachter transportiert, Korvette kämpft. Kapazitätsskalierung läuft über Hangar-Slots (Anzahl Schiffe), nicht über verschiedene Schiffsgrößen.

| Key (intern) | Name (DE) | Voraussetzung |
|---|---|---|
| `drone` | Drohne | Hangar Lv 1 |
| `freighter` | Frachter | Hangar Lv 2 |
| `corvette` | Korvette | Hangar Lv 3 |

#### Berater (Personal)

Berater erscheinen im Techtree in Spalte 0. Ihre Gates spiegeln die Einführungsreihenfolge im Run wider. Berater-Slots öffnen über zwei Mechanismen: CC-Level (Slot 1) oder den Bau eines spezifischen Gebäudes (Slots 2–5). Max. 5 Slots (1 je Beratertyp).

| Key (intern) | Name (DE) | AP-Typ | Hire-Voraussetzung | Slot |
|---|---|---|---|---|
| `engineer` | Baumeister | construction | CC Lv 1 | 1 (fix) |
| `scientist` | Analytiker | research | Analytik-Labor Lv 1 | 2–4 (generisch) |
| `pilot` | Raumfahrer | navigation | Hangar Lv 1 | 2–4 (generisch) |
| `trader` | Konsul | economy | Bar Lv 1 | 2–4 (generisch) |
| `strategist` | Stratege | strategy | Sicherheits-Hub Lv 1 | 5 (fix) |

---

### 11.2 Abhängigkeitsregeln

Das Abhängigkeitssystem folgt vier Regeln:

**Regel 1 — CC als Tier-Gate**
Die Kommandozentrale hat 5 Level und schaltet je Level eine Gebäude-Tier frei. Kein Gebäude höherer Tier ist baubar, solange das CC-Level nicht erreicht ist. Die Tiers:

| CC-Level | Freischaltet |
|---|---|
| 1 | Wohnhabitat, Harvester |
| 2 | Analytik-Labor, Krankenstation, Cantina, Hangar (alle drei Pfadwahl-Gebäude ab Lv2 baubar, gestaffelt — siehe §13), Uplink-Station (Lv1) |
| 3 | Sicherheits-Hub (→ Strategen-Slot); Uplink-Station Lv2 freischaltbar |
| 4 | Religiöse Stätte, Handelsposten |
| 5 | Denkmal; Uplink-Station Lv3 freischaltbar |

**Regel 2 — Funktionale Abhängigkeiten**
Einige Entitäten setzen nicht nur CC-Level, sondern ein konkretes Gebäude voraus:

| Entität | Voraussetzung |
|---|---|
| `bioFacility` | Harvester Lv 1 |
| `bar` | Wohnhabitat Lv 1 |
| `construction` (Kenntnis) | Analytik-Labor Lv 1 |
| `agronomy` (Kenntnis) | Analytik-Labor Lv 1 + Bio-Anlage Lv 1 |
| `health` (Kenntnis) | Analytik-Labor Lv 1 + Krankenstation Lv 1 |
| `cartography` (Kenntnis) | Analytik-Labor Lv 1 + Hangar Lv 1 |
| `geology` (Kenntnis) | Analytik-Labor Lv 2 + Harvester Lv 1 |
| `trade` (Kenntnis) | Analytik-Labor Lv 2 + Bar Lv 1 |
| `defense` (Kenntnis) | Analytik-Labor Lv 3 + Hangar Lv 2 |
| Drohne | Hangar Lv 1 |
| Frachter | Hangar Lv 2 |
| Korvette | Hangar Lv 3 |
| Raumfahrer (Berater) | Hangar Lv 1 |
| Konsul (Berater) | Bar Lv 1 |
| Stratege (Berater) | Sicherheits-Hub Lv 1 |

**Regel 3 — CC-Level-Cap für Kenntnisse Lv4/5**
Kenntnisse können maximal auf das aktuelle CC-Level ausgebaut werden, sobald sie Lv4 oder Lv5 erreichen sollen. Lv1–3 sind immer erreichbar wenn die Gebäude-Voraussetzungen erfüllt sind. Lv4 erfordert zusätzlich CC Lv4, Lv5 erfordert CC Lv5.

| Kenntnis-Level | Zusätzliche Voraussetzung |
|---|---|
| 1–3 | Nur Gebäude-Voraussetzungen (Regel 2) |
| 4 | Gebäude-Voraussetzungen + CC Lv 4 |
| 5 | Gebäude-Voraussetzungen + CC Lv 5 |

**Regel 4 — Supply als weicher Gate**
Jedes Gebäude und jedes Schiff verbraucht Supply. Supply-Cap ist durch CC-Level und Wohnhabitate begrenzt. Der Spieler kann theoretisch alles bauen wollen, ist aber durch Supply gezwungen, Prioritäten zu setzen. Das ist kein harter Abhängigkeitsbaum, sondern Ressourcendruck. Details in §6 (Supply-Generierung).

> **Keine zyklischen Abhängigkeiten.** Jede Abhängigkeitskette endet beim CC. Ein Deadlock durch wechselseitige Abhängigkeiten ist konstruktiv ausgeschlossen.

---

### 11.3 Grid-Layout (Techtree-Ansicht)

Der Techtree ist in **5 Phasen** aufgeteilt, jede entspricht einem CC-Level-Meilenstein. Jede Phase ist ein **3-Spalten-Grid** (Koordinaten phasen-lokal, 1-indexiert). Pfeile verbinden Abhängigkeiten ausschließlich innerhalb einer Phase — das CC-Level-Gate kommuniziert der Phasen-Header.

**Pfeil-Quellen:**

- Gebäude, Schiffe, Berater: Pfeil von `required_building_id`
- Kenntnisse: Pfeil vom **sekundären Gebäude** (nicht vom Analytik-Labor). Ausnahme: `construction` hat kein sekundäres Gebäude — Pfeil vom Analytik-Labor. Bei phasen-übergreifenden Sekundär-Voraussetzungen wird auf das Analytik-Labor als Phasen-internen Anker zurückgegriffen.

**Vollständige Phasen-Grid-Koordinatentabelle** (row/col phasen-lokal, 1-indexiert):

| Phase | CC-Lv | Entität | Typ | Row | Col |
|-------|--------|---------|-----|-----|-----|
| 1 | 1 | housingComplex | building | 1 | 1 |
| 1 | 1 | harvester | building | 1 | 2 |
| 1 | 1 | bioFacility | building | 2 | 2 |
| 1 | 1 | engineer | personell | 2 | 3 |
| 2 | 2 | sciencelab | building | 1 | 2 |
| 2 | 2 | infirmary | building | 1 | 3 |
| 2 | 2 | bar | building | 2 | 1 |
| 2 | 2 | hangar | building | 2 | 2 |
| 2 | 2 | scientist | personell | 2 | 3 |
| 2 | 2 | trader | personell | 3 | 1 |
| 2 | 2 | pilot | personell | 3 | 2 |
| 2 | 2 | knowledge_construction | research | 4 | 3 |
| 2 | 2 | knowledge_agronomy | research | 5 | 3 |
| 2 | 2 | knowledge_health | research | 6 | 1 |
| 2 | 2 | knowledge_trade | research | 6 | 3 |
| 3 | 3 | securityHub | building | 1 | 1 |
| 3 | 3 | strategist | personell | 1 | 2 |
| 3 | 3 | drone | ship | 2 | 1 |
| 3 | 3 | freighter | ship | 2 | 2 |
| 3 | 3 | knowledge_geology | research | 3 | 1 |
| 3 | 3 | knowledge_cartography | research | 3 | 3 |
| 3 | 3 | corvette | ship | 4 | 2 |
| 3 | 3 | knowledge_defense | research | 4 | 3 |
| 4 | 4 | temple | building | 1 | 2 |
| 5 | 5 | monument | building | 1 | 2 |

> Die `row`/`col`-Werte sind kanonisch — sie werden 1:1 in die DB-Tabellen geschrieben. Das Grid-CSS liest sie als `grid-row: row + 1; grid-column: col + 1`.

> ⚠️ BALANCE CONCERN: Die Phase-2-Grid-Koordinaten für `hangar` (2,2), `pilot` (3,2), `trader` (3,1) sind vorläufige Werte nach der Umstrukturierung (Hangar von Phase 3 auf Phase 2, 2026-06-28). Phase 2 hat nun 11 Einträge statt 9 — visuelle Kollisionen und Pfeil-Überschneidungen müssen nach Implementierung im Techtree-Screen geprüft und ggf. korrigiert werden. Gleiches gilt für Phase 3 (securityHub/strategist neu, alte Positionen von hangar/pilot frei).

**Implementierungshinweise (Blade/JS):**

Die bisherigen 4 getrennten `<section>`-Blöcke mit je eigenem `<div class="tech-grid">` werden zu einem einzigen gemeinsamen `<div class="tech-grid">` zusammengeführt. Kategorie-Toggle-Buttons steuern `display: none` auf den einzelnen Tech-Cards (per CSS-Klasse oder `x-show` auf Card-Ebene), nicht auf Grid-Container-Ebene. Section-Titel (Gebäude / Kenntnisse / Schiffe / Berater) bleiben als positionierte Label-Elemente im Grid erhalten.

> ⚠️ BALANCE CONCERN: Die Kenntnisse `cartography` (row 7) und `defense` (row 8) liegen visuell weit unter ihrem sekundären Prereq Hangar (row 3). Das ist unvermeidbar bei 7 Kenntnissen in einer Spalte ohne Kollisionen. Falls die Pfeil-Länge als störend empfunden wird, kann `cartography` auf col 5 row 4 verschoben werden (neben drone, dem anderen Hangar-Lv1-Kind) — das würde die Kenntnisse-Spalte jedoch aufreißen und die visuelle Gruppierung schwächen.

---

## 12. Handel (Trade)

### Designprinzip (Phase 3 Redesign)

Handel ist **optional aber lohnend** — der Spieler kann alles auch ohne Handel aufbauen, aber Handel beschleunigt und verbilligt. Kein Zwang, kein Progression-Lock.

Der einzige Handelsort ist die **Bar/Cantina**. Alle Handelsaktivitäten — Kauf, Verkauf, NPC-Angebote, Spieler-zu-Spieler — laufen über dieselbe Mechanik. Es gibt keinen separaten Marktplatz.

---

### Kanal 1: Bar/Cantina (primär, früh, informell)

Die Bar ist ab CC Lv2 verfügbar. Pro Sol erscheinen 0–2 Gäste — Händler, Schmuggler, Gelegenheitsverkäufer. Jeder Gast hat ein konkretes Angebot das **2–4 Sole gültig** ist (abhängig vom Bar-Level). Danach ist der Gast weg.

**Angebotstypen:**
- Ressource gegen Credits (z.B. 50 Werkstoffe für 800 Cr) — 60 % aller Angebote
- Ressource gegen Ressource (z.B. 30 Organika gegen 20 Regolith) — 40 % aller Angebote

Der Spieler entscheidet pro Angebot: annehmen oder ablehnen. **Annehmen kostet 1 Economy-AP** — dieser Sink macht den Konsul-AP-Pool spielerisch relevant.

**Bar-Level-Progression:**

| Level | Angebots-Gültigkeit | Max. gleichzeitig aktive Angebote |
|-------|---------------------|-----------------------------------|
| Lv1 | 2 Sole | 2 |
| Lv2 | 3 Sole | 3 |
| Lv3 | 3 Sole | 4 |
| Lv4 | 3 Sole | 5 |
| Lv5 | 4 Sole | 6 |

**Konsul (advisor_trader) — Rang-Effekte:**

| | Kein Konsul | Rang 1 (Junior) | Rang 2 (Senior) | Rang 3 (Experte) |
|---|---|---|---|---|
| Economy-AP/Sol | 6 (Basis) | 10 | 13 | 18 |
| Gäste/Sol | 0–1 | 0–1 | 0–2 | 1–2 |
| Preisrabatt | 0 % | 10 % | 20 % | 30 % |
| Werkstoffe-Bias | ~33 % | ~33 % | ~33 % | 50 % |

**Werkstoffe-Bias bei Rang 3:** Der Experten-Konsul hat Marktbeziehungen — bei Credits→Ressource-Angeboten erscheinen Werkstoffe mit 50 % Wahrscheinlichkeit (statt gleichverteilt ~33 %). Das gibt dem Experten-Konsul einen konkreten wirtschaftlichen Vorteil in der knappsten Ressource des Spiels (§3 Werkstoffe nicht lokal produzierbar).

---

### Kanal 2: Nexus-Handelsschiffe (Fallback, teuer, garantiert)

Nexus schickt auf Anfrage offizielle Handelsschiffe. Immer verfügbar — auch ohne Händler-Berater, auch ohne Bar. Das Sicherheitsnetz gegen Progression-Locks.

| | Ohne Berater | Rang 1 | Rang 2 | Rang 3 |
|---|---|---|---|---|
| Lieferzeit | 3 Sole | 3 Sole | 2 Sole | 1 Sol |
| Preis | +50% Aufschlag | +40% | +25% | +10% |

**Anfrage-Mechanik:** Der Spieler sendet eine Anfrage über das INNN-System (Nachricht an "Nexus Command"). Nexus antwortet nach 1–3 Solen (abhängig vom Konsul-Rang) mit einem INNN-Ereignis, das die Lieferung bestätigt und die Ressourcen direkt zur Kolonie transferiert. Kein eigenes Fleet-Objekt — das Nexus-Schiff erscheint nicht auf der Karte.

**Ablauf:**
1. Spieler öffnet INNN → "Nexus-Handelsschiff anfordern" → wählt Ressource + Menge
2. Credits-Betrag wird sofort eingefroren (reserviert)
3. Nach Lieferzeit: INNN-Ereignis "Nexus-Lieferung eingetroffen", Ressourcen gutgeschrieben, Credits abgebucht
4. Kann nur 1 offene Anfrage gleichzeitig haben

---

### Handelbare Ressourcen

| Ressource | Handelbar | Typische Richtung |
|-----------|-----------|-------------------|
| Regolith (Rg) | Ja | Verkauf (Überschuss) |
| Organika (Or) | Ja | Kauf/Verkauf je nach Spezialisierung |
| Werkstoffe (Co) | Ja | Kauf (nicht produzierbar) |
| Credits (Cr) | Nein | Zahlungsmittel |
| Supply (Sup) | Nein | Systemwert |
| Vertrauen (V) | Nein | Systemwert |

---

### Kenntnisse-Handel

Kenntnisse sind personengebundenes Wissen — nicht transferierbar.

> **Offen (Phase 4+):** AP-Delegation — ein Spieler "verleiht" Analytiker-AP an eine andere Kolonie für X Sole. Thematisch stimmiger als direkter Wissenstransfer. Für spätere Phase zurückgestellt.

---

## 13. Berater & Aktionspunkte (AP-System)

### Grundkonzept

Aktionspunkte (AP) sind die zentrale Handlungswährung in Nouron. Sie begrenzen, wie viel ein Spieler pro Sol in Gebäude, Forschung, Flotten und Handel investieren kann.

Berater sind **individuelle Entitäten** — kein Mengenzähler. Jeder Berater hat einen eigenen Datensatz mit Rang, Aktivitätszähler und Verfügbarkeitsstatus. Der Spieler rekrutiert, benennt und entwickelt konkrete Individuen, keine abstrakten "Personal"-Stapel.

**5 AP-Typen — nicht mischbar:**

| AP-Typ (intern) | Beraterbezeichnung | Verwendung |
|-----------------|-------------------|-----------|
| `construction` | Baumeister | Gebäude ausbauen, reparieren, Schiffsbau |
| `research` | Analytiker | Kenntnisse vorantreiben, Wissensarbeit |
| `navigation` | Raumfahrer | Flottenbewegung, Fleet-Trade-Orders |
| `economy` | Konsul | Handelsangebote, Marktgeschäfte |
| `strategy` | Stratege | Schutzorders, Verteidigung, taktische Planung |

**Grundwert:** Jeder AP-Typ hat einen Grundwert von **6 AP/Sol** — auch ohne Berater. Ein frischer Spieler ist nie vollständig blockiert.

**Berater** erhöhen den Grundwert ihres AP-Typs. Max. **1 Berater pro Typ pro Kolonie** (Slot-System) — also maximal 5 gleichzeitig.

---

### Slot-System: CC-Level als Gate, Pfadwahl ab Slot 2

Berater-Slots öffnen nicht mehr ausschließlich über CC-Level, sondern analog zu den Pfad-Gebäuden: durch den Bau eines spezifischen Gebäudes. Slot 1 und Slot 5 sind **fest** an einen Beratertyp gebunden (Baumeister zuerst — siehe §16.2 "Designentscheidung zu Rang 1"; Stratege über den Sicherheits-Hub). Slots 2–4 sind seit der Pfadwahl-Überarbeitung (2026-06-24) **generisch**: Welcher Beratertyp einen dieser drei Slots belegt, hängt davon ab, welches der drei Pfad-Gebäude der Spieler zuerst/zweit/dritt baut — nicht von einer fest verdrahteten CC-Level→Typ-Zuordnung.

| Gate | Slot | Bindung |
|------|------|---------|
| CC Lv1 | Slot 1 | **fix:** Baumeister |
| CC Lv2 + 1. Pfad-Gebäude (sciencelab/hangar/bar) | Slot 2 | **generisch:** Analytiker/Raumfahrer/Konsul |
| CC Lv3 + 2. Pfad-Gebäude | Slot 3 | **generisch:** Analytiker/Raumfahrer/Konsul |
| CC Lv4 + 3. Pfad-Gebäude | Slot 4 | **generisch:** Analytiker/Raumfahrer/Konsul |
| CC Lv3 + Sicherheits-Hub Lv1 | Slot 5 | **fix:** Stratege |

> **Hinweis:** Slot 5 (Stratege, CC Lv3 + Hub) kann früher öffnen als Slot 4 (drittes Pfad-Gebäude, CC Lv4), wenn der Spieler bei CC Lv3 den Hub vor dem dritten Pfad-Gebäude baut. Die Slot-Nummerierung ist ein Label, keine strikt sequenzielle Pflicht-Reihenfolge.

**Die drei Pfade + Strategen-Pfad** (siehe §4 "Pfadwahl ab Sol 3" und §4 "Sicherheits-Hub"):

| Pfad | Gebäude | Beratertyp | AP-Pool | CC-Gate | Slot |
|------|---------|-----------|---------|---------|------|
| A | Analytik-Labor (sciencelab) | Analytiker | research | CC Lv2 (Pfadwahl) | 2–4 (generisch) |
| B | Hangar | Raumfahrer | navigation | CC Lv2 (Pfadwahl) | 2–4 (generisch) |
| C | Cantina (bar) | Konsul | economy | CC Lv2 (Pfadwahl) | 2–4 (generisch) |
| D | Sicherheits-Hub | Stratege | strategy | CC Lv3 (kein Pfadwahl-Gate) | 5 (fix) |

**Gate-Logik (Bau, nicht nur Berater):** Alle drei Pfad-Gebäude sind ab CC Lv2 grundsätzlich baubar — aber gleichzeitig gilt ein zusätzliches Bau-Gate: `Anzahl bereits gebauter Pfad-Gebäude < CC-Level − 1`. Bei CC Lv2 darf also nur **eines** der drei gebaut werden; das zweite schaltet erst bei CC Lv3 frei, das dritte erst bei CC Lv4. Es gibt **keine permanente Ausschließung** — wer bei CC2 die Cantina wählt, bekommt Sciencelab und Hangar bei CC3 bzw. CC4 trotzdem, nur später. Die "Wahl" bei Sol 3 bestimmt **Reihenfolge und Zeitvorsprung**, nicht endgültigen Zugang. Das hält die Entscheidung gewichtig (wer zuerst baut, bekommt den zugehörigen Berater-Slot 1–2 CC-Level früher als bei den anderen beiden Pfaden), vermeidet aber einen harten Lockout, der bei einer frühen Sol-3-Entscheidung zu hart wäre (Nouron-Prinzip: keine bestrafenden Permanent-Konsequenzen für frühe Entscheidungen, siehe §1.1).

**Reihenfolge-Auflösung:** Der Slot, den ein Pfad-Gebäude belegt, ergibt sich aus der **Baureihenfolge** dieses Gebäudes relativ zu den anderen beiden — nicht aus dem Gebäudetyp selbst. Werden (im seltenen Fall ausreichender Ressourcen-Reserven) zwei Pfad-Gebäude im selben Sol fertiggestellt, entscheidet ein fixer, nicht spielerseitig beeinflussbarer Tie-Break in der Reihenfolge **Sciencelab → Hangar → Cantina** (aufsteigend nach `building_id`: 31 < 44 < 52). Dieser Tie-Break ist ein reines Implementierungsdetail ohne Spielerrelevanz außerhalb des Edge-Case.

> **Kostenbalancing der Pfad-Gebäude (2026-06-28, gelöst):** Die Supply- und Regolith-Kosten der drei Pfad-Gebäude wurden nach erstem Playtest-Feedback neu ausbalanciert — vollständige Tabelle und Begründung in §6 "Pfadwahl-Kostenbalancing". Neue Werte: Analytik-Labor 80 Rg / 8 Supply (unverändert), Hangar 90 Rg / 4 Supply (vorher 80 Rg + 25 Wk / 6 Supply), Cantina 70 Rg / 6 Supply (vorher 50 Rg / 4 Supply). Schiffe kosten kein Supply (Design-Entscheidung 2026-06-08) — der frühere Einwand "Pfad B bindet mehr Supply durch Schiffe" entfällt damit vollständig.

---

### Datenmodell: `advisors`-Tabelle

Jeder Berater ist ein eigener Datensatz. Die Tabelle hat folgendes Schema:

```
advisors
├── id                      ← eindeutige ID des Beraters
├── user_id                 ← Eigentümer (immer gesetzt)
├── personell_type          ← 'construction' | 'research' | 'navigation' | 'economy' | 'strategy'
├── colony_id               ← nullable: aktiv auf dieser Kolonie
├── rank                    ← 1 = Junior | 2 = Senior | 3 = Experte
├── active_ticks            ← kumulierter Zähler für Rang-Aufstieg
└── unavailable_until_tick  ← Erholungsphase nach Burnout (NULL = verfügbar)
```

> **Verworfen (Option A):** Frühere Entwürfe sahen `fleet_id` und `is_commander`-Felder vor, um den Raumfahrer als Flottenkommandanten zu modellieren. Dieser Pfad wurde nicht weiterverfolgt. Berater sind colony-scoped — sie verlassen die Kolonie nicht. Die Flottenkommandanten-Mechanik ist für Phase 4+ zurückgestellt und noch nicht definiert.

**Mögliche Zustände eines Beraters:**

| colony_id | Bedeutung | Gilt für |
|-----------|-----------|----------|
| gesetzt | Aktiv auf Kolonie, generiert AP | Alle Typen |
| NULL | Arbeitslos — re-assignierbar oder handelbar | Alle Typen |

**Entlassung** löscht keinen Berater — `colony_id` wird auf NULL gesetzt. Der Berater bleibt als arbeitsloser Datensatz erhalten und kann erneut zugewiesen oder gehandelt werden. Rang und `active_ticks` bleiben erhalten.

---

### Die fünf Berater-Typen

| Beratertyp | AP-Pool (intern) | Thematische Rolle |
|------------|-----------------|------------------|
| Baumeister | `construction` | Infrastruktur, Gebäude, Schiffsbau |
| Analytiker | `research` | Kenntnisse, Wissensarbeit |
| Raumfahrer | `navigation` | Flottenorders erteilen, Bewegung, Fleet-Trade; colony-scoped AP-Produzent |
| Konsul | `economy` | Wirtschaftsbeziehungen, Markt |
| Stratege | `strategy` | Schutz, Verteidigung, taktische Befehle |

Der Raumfahrer generiert Navigation-AP auf der Kolonie — diese AP sind die Voraussetzung für das Erteilen von Flottenorders. Er ist kein Flottenkommandant und verlässt die Kolonie nicht. Eine eventuelle Außendienst-Mechanik für den Raumfahrer ist für Phase 4+ zurückgestellt und noch nicht definiert (siehe auch "Außenmissionen" weiter unten).

### Außenmissionen (Berater-Außendienst)

> **Phase 4** — Vollständig ausgearbeitet, Implementierung ab Phase 4 geplant.

Vier Beratertypen (Baumeister, Analytiker, Konsul, Stratege) können für eine begrenzte Anzahl Sole auf eine **Außenmission** entsendet werden — mit denselben Opportunitätskosten (AP fehlen während der Abwesenheit) und einem Bonus bei Rückkehr. Der Raumfahrer erscheint nicht in der Missions-Auswahl — eine spezifische Außendienst-Mechanik für ihn wird nach Playtest evaluiert (Phase 4+, noch kein konkreter Pfad definiert).

---

#### Grundprinzip

- Der Berater verlässt die Kolonie für die Missionsdauer vollständig.
- Während der Mission generiert er **keine AP** für seinen Pool.
- Bei Rückkehr erhält die Kolonie einen Bonus — abhängig vom Missionstyp und Rang.
- Der Spieler initiiert die Mission manuell; sie kann nicht vorzeitig abgebrochen werden.
- Maximal **2 Berater gleichzeitig auf Außenmission** (kolonieweites Limit).

---

#### Missionen nach Beratertyp

| Beratertyp | Missionsname | Dauer (Sole) | Bonus bei Erfolg |
|------------|--------------|--------------|-----------------|
| Baumeister | Nexus-Notfall-Wartung | 3–5 | Ein beliebiges Koloniegebäude erhält sofort volle `status_points` |
| Analytiker | Datenaustausch mit Forschungsstation | 4–6 | Spieler wählt eine Kenntnis — diese steigt sofort um 1 Level (ohne Research-AP-Kosten, CC-Gates bleiben aktiv) |
| Konsul | Handelsreise | 3–4 | Exklusives Bar-Angebot bei Rückkehr (2 Sole gültig, erscheint als zusätzlicher Slot neben normalen Bar-Angeboten) |
| Stratege | Sicherheitsanalyse | 3–4 | Nächster zufälliger NPC-Encounter ist vorab bekannt (Stärkewert + Typ sichtbar vor dem Auslösen). **Voraussetzung:** Sicherheits-Hub Lv 1 aktiv (thematisch: Hub stellt die Infrastruktur für den Einsatz). |
| Raumfahrer | — | — | Kein Außenmissions-Pfad — sein Außendienst ist der Flottenkommandanten-Pfad (§14) |

> **⚠️ Balance:** Der Analytiker-Bonus (Kenntnis +1 Level kostenlos) ist der stärkste Effekt. CC-Gates bleiben aktiv — ein Kenntnislevel das CC Lv5 voraussetzt, kann durch eine Außenmission nicht übersprungen werden. Dennoch muss nach Playtest geprüft werden, ob ein Free-Level-Upgrade bei Lv4→Lv5 zu mächtig ist. Ggf. Einschränkung: Bonus gilt nur für Lv1→Lv2 oder Lv2→Lv3.

> **⚠️ Balance:** Der Stratege-Bonus (vorab bekannter Encounter) verändert die Risikostruktur von Begegnungen fundamental. Er sollte nur für den unmittelbar nächsten Encounter gelten, nicht pauschal für mehrere Sole voraus. Verfällt nach dem nächsten Encounter oder nach 5 Solen (je nachdem was früher eintritt).

---

#### Risiko-Mechanik: Drei Ausgänge

Jede Außenmission hat drei mögliche Ausgänge. Der Rang des Beraters bestimmt die Wahrscheinlichkeitsverteilung.

| Ausgang | Beschreibung |
|---------|-------------|
| **Erfolg** | Voller Bonus bei Rückkehr |
| **Teilerfolg** | Halber Bonus (gerundet nach unten) |
| **Misserfolg** | Kein Bonus — AP haben dennoch für die Missionsdauer gefehlt |

**Wahrscheinlichkeiten nach Rang:**

| Rang | Erfolg | Teilerfolg | Misserfolg |
|------|--------|------------|------------|
| 1 — Junior | 60% | 25% | 15% |
| 2 — Senior | 75% | 20% | 5% |
| 3 — Experte | 90% | 10% | 0% |

**Kein permanenter Verlust:** Bei Misserfolg kehrt der Berater unbeschadet zurück. Der einzige Schaden ist der Opportunitätsverlust — die AP haben während der Missionsdauer gefehlt. Ein Rang-Abzug oder permanenter Malus findet nicht statt.

> **⚠️ Balance:** Bei Rang 1 besteht eine 15% Misserfolgswahrscheinlichkeit. Das ist der Anreiz, Missionen bevorzugt mit erfahrenen Beratern zu starten — oder das Risiko bewusst einzugehen. Eine Junior-Mission bleibt attraktiv wenn die Opportunitätskosten gering sind (kurze Missionsdauer, AP-Pool ohnehin nicht voll ausgelastet).

---

#### Constraints und Interaktionen

| Regel | Beschreibung |
|-------|-------------|
| **Burnout-Sperre** | Ein Berater mit gesetztem `unavailable_until_tick` (Burnout) kann keine Mission starten. |
| **Missions-Immunität** | Ein Berater auf Außenmission kann während dieser Zeit keinen Burnout erleiden. Der Burnout-Timer pausiert für die Missionsdauer. |
| **Concurrent-Limit** | Maximal 2 Berater gleichzeitig auf Mission (kolonieweites Limit). Ein dritter kann erst starten, wenn einer zurückgekehrt ist. |
| **Missionsdauer-Transparenz** | Das Missions-UI zeigt die verbleibenden Sole bis Rückkehr neben der aktuellen Sol-Nummer an. |
| **AP-Nutzungsrate** | Run-Aufgabe "Effizienzsprung" (AP-Nutzungsrate ≥ 90%, §15) und Außenmissionen schließen sich nicht aus — der Spieler muss aktiv abwägen ob er einen AP-Produzenten für die Missionsdauer opfert. |
| **Raumfahrer ausgenommen** | Der Raumfahrer erscheint in der Missions-Auswahl nicht — Außendienst-Mechanik für den Raumfahrer wird nach Playtest evaluiert (Phase 4+, noch kein konkreter Pfad definiert). |

---

#### Technische Implementierungshinweise

**Schema-Erweiterung (`advisors`-Tabelle):**

```
advisors
├── on_mission_until_tick  ← nullable int: gesetzt während Außenmission aktiv
└── mission_type           ← nullable string: z.B. 'nexus_maintenance', 'data_exchange', 'trade_trip', 'security_analysis'
```

`on_mission_until_tick` und `unavailable_until_tick` sind semantisch getrennt — ersteres ist freiwillige Abwesenheit, letzteres unfreiwillige Erholungsphase. Sie dürfen nicht gleichzeitig gesetzt sein (Constraint auf Service-Ebene).

**AP-Berechnung:**

`getTotalActionPoints()` (bzw. `PersonellService`) muss `on_mission_until_tick` analog zu `unavailable_until_tick` behandeln: Wenn `current_tick <= on_mission_until_tick`, liefert der Berater **0 AP-Bonus** (Grundwert bleibt aktiv).

**Bonus-Dispatch:**

Der Missions-Abschluss wird in `AdvisorMissionService` verarbeitet. Empfehlung: Strategy-Pattern oder typ-spezifische `resolve*Mission()`-Methoden je `mission_type`. Der Zufallsausgang (Erfolg/Teilerfolg/Misserfolg) wird im Tick-Schritt 7 gewürfelt, sobald `current_tick > on_mission_until_tick`.

**Tick-Integration:**

Missions-Auflösung läuft in **Tick-Schritt 7** (Advisor Ticks), nach AP-Berechnung und Burnout-Prüfung. Reihenfolge innerhalb Schritt 7: erst AP-Update, dann Burnout-Check, dann Missions-Auflösung.

---

### Rang-System

Jeder Berater hat einen von drei Rängen. Der Rang bestimmt den AP-Bonus pro Sol und den laufenden Upkeep in Credits.

| Rang | Bezeichnung | AP-Bonus/Sol | Gesamt-AP/Sol | Upkeep (Cr/Sol) |
|------|-------------|--------------|---------------|-----------------|
| 1 | Junior | +4 | 10 | 10 |
| 2 | Senior | +7 | 13 | 50 |
| 3 | Experte | +12 | 18 | 160 |

*(Gesamt-AP = 6 Grundwert + AP-Bonus)*

**Einstellungskosten (Rang 1) — typ-spezifisch:**

| Beratertyp | Kosten (Cr) | Begründung |
|------------|-------------|-----------|
| Baumeister | 300 | Kernanforderung Tag 1 — günstigster Einstieg |
| Analytiker | 400 | Mittlere Priorität — erst bei CC Lv2 verfügbar |
| Raumfahrer | 500 | Flotten-fokussiert — erst relevant wenn Hangar gebaut |
| Konsul | 350 | Handelssupport — mittlere Priorität |
| Stratege | 600 | Teuerster — typischerweise Late-Game |

- **Upkeep** wird jeden Sol von den Colony-Credits abgezogen, solange der Berater `colony_id` gesetzt hat (Berater ist aktiv zugewiesen).
- **Rang-Aufstieg:** automatisch nach ausreichend kumulierten `active_ticks` (`config/game.php → advisors.rank_thresholds`).
- Alle Werte stehen in `config/game.php → advisor` (Einstellungskosten, AP, Upkeep, Rang-Thresholds).

> **UI-Anforderung:** Die Berater-Verwaltung zeigt für jeden aktiven Berater: Rang, AP-Beitrag/Sol, laufender Upkeep (Cr/Sol) und `active_ticks` zum nächsten Rang-Aufstieg. Diese vier Werte müssen auf einen Blick lesbar sein.

---

### Kosten: Credits — kein Supply

Berater kosten ausschliesslich **Credits** — sowohl bei der Einstellung (einmalig) als auch im laufenden Upkeep (pro Sol). Supply ist nicht betroffen.

Supply bleibt der physische Kapazitätsdeckel für Gebäude und Schiffe. Personalkosten laufen über Credits. Das trennt zwei konzeptuell verschiedene Ressourcen sauber:

- **Supply** = physische Infrastrukturkapazität (Gebäude, Schiffe)
- **Credits** = ökonomische Liquidität (Personal, Handel, Investitionen)

Supply wird durch Kommandozentrale und Wohnkomplex generiert (Cap-Modell). Berater verbrauchen kein Supply.

**Flottenanzahl:** Die maximale Flottenanzahl pro Spieler wird durch eine Konfigurationsobergrenze begrenzt. Konkrete Obergrenze noch offen — konfigurierbar, Phase 4+. Kein Flottenkommandanten-Pflichtmodell: Flotten benötigen keinen zugewiesenen Raumfahrer.

---

### Raumfahrer: Colony-Scope

Der Raumfahrer ist ein colony-scoped AP-Produzent für den `navigation`-Pool. Er bleibt der Kolonie zugewiesen und verlässt sie nicht.

- **Colony-zugewiesen:** Generiert Navigation-AP auf der Kolonie (Grundlage für neue Flottenorders).
- **Flottenorders:** Navigation-AP, die der Raumfahrer generiert, werden verbraucht wenn der Spieler Flottenorders erteilt — der Raumfahrer selbst "geht" dabei nicht mit.
- **Burnout:** Bei Burnout ist der Raumfahrer für N Sole nicht verfügbar (`unavailable_until_tick` gesetzt), der Navigation-AP-Pool fällt auf den Grundwert.

> **Phase 4+ — Flottenkommandanten-Mechanik:** Eine Mechanik, bei der der Raumfahrer physisch einer Flotte zugewiesen wird (als Kommandant), ist für Phase 4+ zurückgestellt. Datenmodell (eventuelles `fleet_id`-Feld, `is_commander`-Flag) ist noch nicht definiert. Keine Implementierung vor Playtest-Auswertung.

---

### Verfügbare AP

```
availableAP(type) = 6 (Grundwert) + AP_bonus(rank) − lockedAP(tick, type)
```

Wobei `AP_bonus(rank)` der Bonus-Wert des aktuell zugewiesenen Beraters dieses Typs ist (0 wenn kein Berater im Slot). AP-Locks verfallen automatisch zum nächsten Sol — jeder Pool wird täglich vollständig erneuert. Die fünf Typen sind vollständig unabhängig voneinander.

### AP-Verbrauch

1. **Bauen/Forschen/Handel:** AP werden beim Investieren gesperrt (`invest('add')`).
2. **Reparatur/Abbau:** AP werden in Höhe der veränderten `status_points` gesperrt.
3. **Flottenorder:** AP-Kosten abhängig von Order-Typ (siehe §1.1 und §8).

### Implementierung

- `app/Services/Techtree/PersonellService.php` — AP-Berechnung, Sperrung
- `app/Services/Techtree/AbstractTechnologyService.php` — AP-Verbrauch beim Investieren
- `app/Services/FleetService.php` — Navigation-AP-Check bei Order-Erstellung
- Tabelle `locked_actionpoints`: `(tick, scope_type, scope_id, personell_type, spend_ap)`

### Berater-Burnout (Auswirkung auf AP)

Wenn ein Berater einen Burnout erleidet (Wahrscheinlichkeitsmechanik — Details in §7), fällt sein AP-Beitrag für die Dauer der Erholung auf null zurück. Der AP-Pool des betroffenen Typs sinkt auf den **Grundwert (6 AP/Sol)**.

**Beispiel:** Ein Senior-Analytiker (rank=2) liefert normalerweise 20 AP/Sol für `research`. Bei Burnout: nur noch 6 AP/Sol für `research`.

**Dauer:** Abhängig vom Rang (Junior 15, Senior 10, Experte 5 Sole — Richtwerte aus `config/game.php → advisors.burnout`).

**Sichtbarkeit:** Die Berater-Übersicht zeigt einen "Pause"-Zustand mit Countdown bis zur Rückkehr. INNN-Ereignis informiert beim Einsetzen.

**`active_ticks`-Reset:** Der Berater beginnt nach dem Burnout bei 0 aktiven Ticks — Burnout "entlastet" also auch zukünftig, weil die Wahrscheinlichkeit eines weiteren Burnouts wieder sinkt.

**Kein manueller Eingriff nötig:** Der Berater kehrt automatisch auf den Slot zurück wenn `current_tick > unavailable_until_tick`. Der Slot bleibt "reserviert" — ein anderer Berater kann nicht eingestellt werden während der Slot im Erholungs-Zustand ist.

### Dev-Mode

Im Dev-Mode (`GAME_DEV_MODE=true` in `.env`, Standard) werden Ressourcen- und AP-Kosten übersprungen. Das AP-System selbst bleibt aktiv für Tests.

---

### Berater als Informationsebene

Jeder Berater erweitert nicht nur den AP-Pool seines Typs — er erweitert auch den **Informationsraum** des Spielers in seinem zugehörigen Screen. Ohne Berater ist der Screen voll funktionstüchtig; mit Berater erscheinen zusätzliche Metriken, Prognosen und Hinweise.

Dieses Konzept — "Fog of Information" — ist analog zum Fog of War in der Exploration, aber auf Spieler-Entscheidungsqualität bezogen. Wer einen Berater verliert (Burnout, Abwesenheit, Außenmission), spielt dasselbe Spiel mit weniger Kontext. Das erzeugt spürbare Konsequenz ohne harten Progress-Block.

**Designprinzipien:**

- Informations-Verlust ≠ Feature-Verlust: alle Screens bleiben vollständig bedienbar
- Kritische Warnungen (z.B. Gebäude-Decay unter Schwellwert) feuern **immer** via INNN — auch ohne Baumeister. Berater liefern Vorwarnzeit und Kontext, nicht die letzte Warnung selbst.
- Pro Berater: maximal 2–3 zusätzliche Informationspunkte. Optionale Details auf Tooltip-Ebene, nicht im Hauptscreen.
- Discovery-Moment beim ersten Einstellen eines Beraters: Onboarding-Hint zeigt was neu sichtbar wird.

**QoL-Infos nach Beratertyp:**

| Berater | Screen | Primär-Information | Sekundär-Information |
|---------|--------|--------------------|----------------------|
| Baumeister | Colony-View | Decay-Prognose pro Gebäude ("in ~4 Solen Level-Down") | Kritische Gebäude hervorgehoben (SP < 30% Max) |
| Analytiker | Techtree | "Sole bis Level X bei aktuellem Forschungs-AP-Fluss" | Priorisierungshinweis für offene Run-Aufgaben |
| Konsul | Cantina | Händler-Einschätzung "guter / durchschnittlich / schlechter Deal" (kontextuell, nicht binär) | Restlaufzeit-Countdown für Angebote prominent statt versteckt |
| Raumfahrer | Systemkarte | Aufgebrochene Reisezeit ("X Sole Reise + Y Sole Order + Rückkehr Sol Z") | Verschleiß-Prognose pro geplante Order |
| Stratege | Run-Ziel-Panel | Ziel-Erreichbarkeits-Prognose ("Aufgabe X: ✓ in ~12 Solen; Aufgabe Y: ✗ — 400 Cr fehlen") | Stärkenabschätzung vor `attack`-Order |

> **⚠️ Balance — Konsul:** Händler-Einschätzung darf nicht binär sein ("kaufen / nicht kaufen"), sonst entwertet sie die Handelsentscheidung. Kontextuell: "günstig für Werkstoffe — du hast davon aber bereits 200" ist besser als "guter Deal".

> **⚠️ Balance — Erster Cantina-Besuch:** Konsul ist erst ab CC Lv2 verfügbar, der erste Händler erscheint früher. Der erste Cantina-Besuch muss immer ein objektiv gutes Angebot zeigen — unabhängig vom Konsul-Status. Sonst entsteht Früh-Spiel-Frustration bei Spielern ohne Konsul.

**Implementierung:** Phase 4 — setzt stabiles Berater-System und abgeschlossene Screen-Redesigns voraus. Keine neuen Datenpunkte nötig (alle Quellen in Config und DB bereits vorhanden), reine UI-Logik. Discovery-Moments integrieren sich in bestehenden Onboarding-Hint-Stack (§16).

---

---

## 14. Moralsystem

### Design-Absicht

Vertrauen ist das "weiche" Feedback-System der Kolonie. Es reagiert auf die Entscheidungen des Spielers — welche Gebäude gebaut werden, wie militaristisch die Spielweise ist, welche Forschungen betrieben werden — und verstärkt oder schwächt die Kolonieleistung mit spürbaren, aber nicht spielentscheidenden Effekten.

Vertrauen ist kein zweites Ressourcenproblem, das der Spieler managen muss. Es ist ein stiller Bewertungsparameter: Wer eine ausgewogene, zivil-orientierte Kolonie aufbaut, wird belohnt. Wer ausschließlich auf Militär setzt und Zivilinfrastruktur vernachlässigt, spürt das in einer moderaten Malus-Spirale.

### Wertebereich

```
Vertrauen: -100 bis +100
Neutralwert: 0
Startwert: 0
```

**Bedeutungsbereiche:**

| Bereich | Bezeichnung | Anzeige (UI-Hinweis) |
|---------|-------------|----------------------|
| +61 bis +100 | Hohes Vertrauen | "Euphorisch" |
| +21 bis +60 | Positive Stimmung | "Zufrieden" |
| -20 bis +20 | Neutral | "Stabil" |
| -21 bis -60 | Unzufriedenheit | "Unruhig" |
| -61 bis -100 | Krise | "Aufruhr" |

Der Wert -100 ist ein harter Boden (keine weitere Verschlechterung). Ebenso +100 als Deckel.

### Berechnung (Sol-basiert)

Vertrauen wird einmal pro Sol **neu berechnet** — nicht akkumuliert. Das Vertrauen eines Sols ergibt sich aus der Summe aller aktiven Faktoren:

```
vertrauen = clamp(Σ(Gebäudeeffekte) + Σ(Forschungseffekte) + clamp(Σ(Schiffseffekte), -30, +30) + steuerfaktor + ereigniseffekte, -100, +100)
```

`colony_resources.amount` (resource_id=12) wird nach der Berechnung auf den neuen Wert gesetzt.

Der Wert wird in **Tick-Schritt 6b** (nach Ressourcenproduktion) berechnet, da Vertrauen die Produktionswerte desselben Sols noch nicht beeinflusst — es wirkt ab dem nächsten Sol.

> **Implementierungsnotiz:** Die Sol-Reihenfolge bedeutet, dass ein Spieler erst nach 2 Solen die volle Wirkung einer vertrauensverändernden Aktion sieht. Das ist akzeptables Design (kein Exploit durch Last-Minute-Bauweise).

### Einflussfaktoren: Gebäude

Jedes gebaute Exemplar eines Vertrauensgebäudes trägt mit einem fixen Wert pro Level bei. Nur Gebäude mit `status_points > 0` zählen (verfallene Gebäude tragen nicht bei).

**Positive Vertrauensgebäude:**

| Gebäude-ID | Bezeichner | Vertrauen/Level |
|------------|------------|-----------------|
| 32 | temple (Religiöse Stätte) | +2 |
| 46 | infirmary (Krankenstation) | +3 |
| 50 | monument (Kolonialdenkmal) | +2 |
| 52 | bar (Cantina) | +2 |

**Negative Vertrauensgebäude:**

*(keine in Phase 3 — alle verbleibenden Gebäude sind neutral oder positiv)*

**Rationale:** Die Cantina wurde als sozialer Treffpunkt konzipiert (+2) — ein wichtiger Ort für das Gemeinschaftsgefühl einer kleinen Kolonie. Militärischer Druck wirkt über Schiffe und Kenntnisse, nicht über Gebäude.

> ⚠️ BALANCE CONCERN: Wenn ein Spieler alle positiven Gebäude maximal ausbaut (temple + infirmary + monument + bar je Lv10+), ist das theoretische Maximum allein durch Gebäude sehr hoch. Der clamp bei +100 verhindert Überlauf, aber der Vertrauen-Cap sollte beim ersten Playtest evaluiert werden ob er zu schnell erreichbar ist.

### Einflussfaktoren: Schiffe

Schiffe tragen zum Vertrauen bei, solange sie einer Kolonie zugewiesen sind (d.h. `colony_ships.amount > 0`). Der Effekt gilt **pro Schiff**, nicht pro Level. Eine Korvette signalisiert den Kolonisten Wachsamkeit und Anspannung; ein Frachter steht für Handel und Versorgung.

| Schiff-ID | Bezeichner | Vertrauen/Schiff |
|-----------|------------|------------------|
| 85 | drone | 0 |
| 37 | korvette | 0 |
| 47 | frachter | +1 |

**Rationale:** Der Frachter steht für Handel und Versorgung (+1/Schiff) — die Kolonisten sehen ihn als Zeichen normaler Aktivität. Die Korvette ist neutral: Kolonisten begrüßen ein Mindestmaß an Schutz, empfinden eine kleine Flotte aber nicht als Bedrohung. Drohnen sind unbemannte Geräte ohne emotionale Wirkung.

**Skalierungsproblem:** Da Schiffszahlen potenziell groß werden können, wird der Gesamtbeitrag aller Schiffe auf `+30` gecapped, bevor er in die Vertrauen-Summe eingeht:

```
ship_vertrauen = clamp(Σ(ship_amount × vertrauen_per_ship), 0, +30)
```

### Einflussfaktoren: Forschungen

Forschungen tragen mit einem Pauschalwert pro Level bei (unabhängig von status_points, da Forschungslevel persistenter sind).

| Kenntnis-Key | Bezeichner | Vertrauen/Level |
|--------------|------------|-----------------|
| agronomy | Agronomie & Kultivierung | +1 |
| health | Gesundheit & Wohlbefinden | +2 |
| defense | Verteidigung & Überlebenstaktik | -1 |

Alle anderen Kenntnisse (construction, cartography, geology, trade) haben keinen direkten Vertrauenseffekt — sie sind neutrale Werkzeuge.

**Rationale:** Agronomie und Gesundheit verbessern spürbar das koloniale Wohlbefinden. Verteidigung als Kenntnis verbreitet ein Klima der Wachsamkeit, das die Stimmung leicht dämpft — analoges Signal zu den Korvetten.

### Einflussfaktoren: Koloniebeiträge (Platzhalter)

> **TODO Design (Evaluation):** Der Begriff "Steuern" passt nicht zur kleinen, persönlichen Kolonie — kein Imperium, kein Gouverneur. Treffendere Begriffe wären z.B. "Koloniebeiträge", "Abgaben" oder "Nexus-Quote". Das zugrundeliegende System (prozentualer Abzug → Vertrauensmalus) muss ebenfalls neu durchdacht werden, bevor es implementiert wird.

Das System ist noch nicht implementiert. Der Platzhalter in der Formel ist `steuerfaktor = 0`.

### Einflussfaktoren: Verpflegung (Organika)

Die Kolonie verbraucht jeden Sol Organika zur Versorgung (§3, Tick-Schritt 3a). Zwei Vertrauenswirkungen:

- **Gesättigt** → `well_fed`-Event (+1, Standard-Event-Logik, 1 Sol).
- **Hunger** (Vorrat deckt den Bedarf nicht) → **eskalierender** Malus, abhängig von `glx_colonies.hunger_streak` (aufeinanderfolgende Hunger-Sole): `−min(2 + (streak−1), 8)`. Anders als gewöhnliche Events eskaliert dieser Faktor, solange der Hunger anhält, und verfällt erst beim Sättigen (Streak → 0). Er wird in `TrustService::calculateTrust` als eigener Summand addiert, nicht über die Event-Tabelle (die nicht stackt).

| Hunger-Streak | Vertrauens-Malus |
|---|---|
| 1 | −2 |
| 2 | −3 |
| 3 | −4 |
| 7+ | −8 (Cap) |

Wirkung: leerer Agrardom → Vertrauensverfall → Produktions-/AP-Malus → noch weniger Organika. Der Agrardom wird damit zum Pflichtgebäude.

### Einflussfaktoren: Ereignisse (Events)

Events können Vertrauen temporär verändern. Die Wirkung hält genau **1 Sol** an (danach wirken nur noch Dauereffekte). Event-Vertrauenswerte werden nicht in `colony_resources` gespeichert, sondern bei der Sol-Berechnung addiert und am Ende des Sols verworfen.

Datenmodell: `innn_events` kann über das `data`-Feld bereits Vertrauen-Deltas tragen. Kein Schemabedarf.

**Geplante Event-Trigger und Vertrauenseffekte:**

Events sind nach Kategorie gruppiert. Alle Effekte wirken exakt 1 Sol (werden nach der Vertrauen-Berechnung verworfen). Mehrere Events desselben Typs im selben Sol summieren sich **nicht** — es gilt der stärkste Wert der Kategorie.

**Bauwesen / Forschung:**

| Event-Key | Beschreibung | Vertrauenseffekt |
|-----------|-------------|------------------|
| `building_level_up` | Gebäude fertiggestellt (Level-Up) | +1 |
| `building_level_down` | Gebäude verfallen (Level-Down durch Decay) | -3 |
| `research_level_up` | Forschung abgeschlossen (Level-Up) | +2 |

**Handel:**

| Event-Key | Beschreibung | Vertrauenseffekt |
|-----------|-------------|------------------|
| `trade_success` | Handelsmission erfolgreich abgeschlossen | +2 |
| `trade_blocked` | Handelsmission durch feindliche Flotte blockiert | -3 |

**Diplomatie:**

| Event-Key | Beschreibung | Vertrauenseffekt |
|-----------|-------------|------------------|
| `treaty_signed` | Diplomatischer Vertrag abgeschlossen | +3 |

**Begegnungen & Zwischenfälle:**

| Event-Key | Beschreibung | Vertrauenseffekt |
|-----------|-------------|------------------|
| `encounter_won` | Zwischenfall erfolgreich gelöst/abgewehrt | +2 |
| `encounter_lost` | Zwischenfall eskaliert / Kolonie wurde beschädigt | -4 |
| `colony_threatened` | Kolonie akut bedroht (kritischer Zwischenfall) | -5 |

> **TODO:** Exakte Vertrauenswerte für Begegnungs-Events nach §9-Ausarbeitung kalibrieren. Event-Keys sind in `TrustService` als `game.trust.events.*` angelegt (CLAUDE.md Korrekturen-Sektion); Werte nach erstem Playtest festsetzen. Der **Sicherheits-Hub** dämpft diese drei Events (+ `building_level_down`) um 25 % wenn aktiv — das macht ihre genauen Werte doppelt relevant.

**Rationale für neue Events:**
- `trade_blocked` (-3) macht Handelsblockaden spürbar — nicht nur wirtschaftlich, sondern auch in der Stimmung der Siedlung.

> ⚠️ BALANCE CONCERN: Event-Vertrauenseffekte für Bauwesen sind einmalig (+1 pro Level-Up). Ein Spieler der täglich Gebäude baut, erhält täglich +1 — das ist ein kleiner, aber stetiger Bonus der aktives Spielen belohnt. Ob das ausreicht als Motivation oder ob der Effekt auf +2 erhöht werden sollte, ist nach erstem Playtest zu evaluieren.

### Effekte des Vertrauens auf die Kolonie

Vertrauen beeinflusst drei Spielparameter. Alle Effekte werden als **Multiplikatoren** auf die Basiswerte angewendet, nicht als additive Boni. Das verhindert, dass Vertrauen zu einer dominanten Wachstumsstrategie wird.

#### Ressourcenproduktion

```
produzierte_menge_effektiv = produzierte_menge × production_multiplier(vertrauen)
```

| Vertrauensbereich | Multiplikator |
|-------------------|---------------|
| +61 bis +100 | 1.20 (+20%) |
| +21 bis +60 | 1.10 (+10%) |
| -20 bis +20 | 1.00 (neutral) |
| -21 bis -60 | 0.85 (-15%) |
| -61 bis -100 | 0.70 (-30%) |

Angewendet auf alle Produktionsgebäude (Harvester, Agrardom und zukünftige).

#### AP-Multiplikator

```
AP_effektiv = AP_basis × ap_multiplier(vertrauen)
```

| Vertrauensbereich | Multiplikator |
|-------------------|---------------|
| +61 bis +100 | 1.10 (+10%) |
| +21 bis +60 | 1.05 (+5%) |
| -20 bis +20 | 1.00 (neutral) |
| -21 bis -60 | 0.90 (-10%) |
| -61 bis -100 | 0.80 (-20%) |

Der AP-Bonus bei hohem Vertrauen ist bewusst kleiner als der Produktionsbonus — AP ist die knappste Ressource und soll nicht durch Vertrauen-Stacking zu stark erhöht werden.

> ⚠️ BALANCE CONCERN: Ein AP-Malus von -20% bei Aufruhr macht Krisensituationen selbstverstärkend (weniger AP → weniger Reparaturen → mehr Decay → mehr Vertrauen-Malus). Diese Spirale ist designtechnisch vertretbar (Entropie als Spielprinzip), aber es muss einen Ausweg geben. Der Ausweg ist der Bau von Vertrauensgebäuden, der trotz AP-Malus möglich bleibt (die Malus-Grenze liegt bei 0.80, nicht bei 0).

#### Supply-Cap

Vertrauen beeinflusst den Supply-Cap **nicht**. Das Supply-System ist ein separater Constraint (Wohnkomplexe, CC) und soll nicht durch ein weiteres System kompliziert werden. Beide Systeme bleiben orthogonal.

### Schema-Bedarf

**Kein neues Schema erforderlich.** `colony_resources.amount` (resource_id=12) speichert den aktuellen Vertrauenswert als Integer im Bereich -100 bis +100. Das ist ausreichend — Vertrauen ist ein Zustand, keine akkumulierte Menge.

**Benötigt wird ausschließlich eine Konfiguration** in `config/game.php` unter dem Schlüssel `moral`. Die vollständigen Werte (buildings, researches, ships, ships_cap, production_multiplier, ap_multiplier, events) sind dort implementiert — `config/game.php` ist die einzige Quelle der Wahrheit für alle Zahlenwerte. Dieses Dokument beschreibt die Semantik; die konkreten Zahlen stehen in der Konfigurationsdatei.

### Sol-Integration

Vertrauen wird als neuer **Tick-Schritt 6b** nach der Ressourcenproduktion berechnet:

| Schritt | Beschreibung |
|---------|-------------|
| 6 | Resource Generation — Rohstoffproduktion (mit altem Vertrauen-Multiplikator) |
| **6b** | **Vertrauen Calculation** — Vertrauen neu berechnen, `colony_resources` (res_id=12) aktualisieren |
| 7 | Advisor Ticks |

Die Reihenfolge ist bewusst: Die Produktion von Sol N verwendet den Vertrauenswert von Sol N-1. Der neue Vertrauenswert gilt erst ab Sol N+1. Das verhindert zirkuläre Abhängigkeiten.

### Implementierungsschritte

1. `config/game.php` — `moral`-Block hinzufügen (alle Werte aus obiger Tabelle)
2. `app/Services/VertrauenService.php` — Service mit Methode `calculate(int $colonyId): int`
3. `app/Services/ResourceService.php` (oder TickService) — `VertrauenService::calculate()` in Schritt 6b aufrufen und `colony_resources` (res_id=12) schreiben
4. `app/Services/Techtree/PersonellService.php` — AP-Berechnung um `vertrauen_multiplier` erweitern
5. Produktionslogik (`config/game.php → production`) — Vertrauen-Multiplikator anwenden
6. UI: Vertrauen-Anzeige in der Ressourcenleiste (existiert als resource_id=12 bereits)

### Mögliche Erweiterungen (nach Playtest)

Das beschriebene System ist bewusst einfach gehalten. Nach einem ersten Playtest kann Vertrauen weiterentwickelt werden zu:
- Revolutionsrisiko bei anhaltender Krise (harter Fail-State-Auslöser)
- Ereignis-Kaskaden bei extremen Vertrauenswerten (z.B. Desertion, Sabotage)

Diese Erweiterungen erfordern kein Schema-Refactoring, da der Grundwert (-100 bis +100) in `colony_resources` stabil bleibt.

---

## 15. Run-Struktur (Roguelike-Modus)

### Konzept

Jede Partie von Nouron ist eine abgeschlossene **Expeditionsmission**. Es gibt kein Endlosspiel — ein Run hat einen definierten Anfang, ein Ziel und ein Ende. Das Roguelike-Prinzip: Nach jedem Run (Sieg oder Niederlage) startet der Spieler von vorne. Highscore entsteht durch Effizienz (wie schnell wurden die Aufgaben erfullt) und Restressourcen.

---

### Phasenstruktur

**Empfehlung: 2 Phasen** — mehr Phasen wurden bei diesem Scope zu viel Struktur erzeugen und das FTL-artige Momentum bremsen.

#### Phase 1 — "Kolonie stabilisieren" (Pflicht)

Dauer: ~10–20 Sole. Kann nicht ubersprungen werden. Ziel ist eine lebensfähige, selbsttragende Kolonie.

**Startzustand (jeder Run):**
- CommandCenter Level 1 — bereits gebaut, betriebsbereit
- Harvester Level 1 — bereits gebaut, produziert sofort Regolith
- Startressourcen: 3.000 Credits, 200 Regolith. Werkstoffe und Organika starten bei 0.
- Der Spieler kann direkt mit dem Bau von Wohnhabitaten beginnen.

**Abschlussbedingungen (BEIDE mussen erfullt sein):**

| Bedingung | Konkret |
|-----------|---------|
| Infrastruktur | CommandCenter Level 3 + mindestens 2 Produktionsgebäude auf Level >= 2 |
| Personal | Mindestens 3 aktive Berater (beliebiger Typ) |

Die zwei Bedingungen decken die Kernsysteme ab: Aufbau (Gebäude) und Handlungsfähigkeit (AP). Sie sind eindeutig messbar und fur Neuspieler verstandlich.

Phase 1 endet automatisch, sobald beide Bedingungen gleichzeitig erfüllt sind. Der Spieler erhält eine Benachrichtigung und Phase 2 beginnt.

> **TODO (Design):** Optionale dritte Bedingung für Phase 1 — könnte pro Run variieren (Roguelike-Element). Beispiele: "erste Handelsroute etabliert", "eine Kenntnis auf Lv2", "erste Flotte entsandt". Das würde jeden Run-Einstieg leicht unterschiedlich anfühlen lassen. Bei Implementierung hier ergänzen.

#### Phase 2 — "Expeditionsmission"

Startet direkt nach Phase 1. Dem Spieler werden 3 Aufgaben aus dem Aufgabenpool zugewiesen (zufällig oder aus vordefinierten Sets). **2 von 3 mussen bis Sol X erfullt werden.**

**Runlänge gesamt:** 60–100 Sole (konfigurierbar, Standard 100). Bei 1 Tag/Sol entspricht das 2–3 Monaten — das ist die Referenzgröße für alle AP- und Ressourcen-Balancingwerte.

**Sol-Konfiguration:** Jeder Run ist über `config/game.php → run` konfigurierbar:
- `tick_limit` — Gesamtsole des Runs (Standard 100)
- `tick_duration_hours` — Maximale Echtzeit pro Sol in Stunden (Standard 24 = 1 Tag)
- `max_players` — 1 (Singleplayer) oder 2–4 (Multiplayer)
- `playbymailmode` — bei `true`: Sol endet sobald alle Spieler ihre Aktionen eingereicht haben, spätestens nach `tick_duration_hours`

> **Designprinzip:** Die Max-Wartezeit (`tick_duration_hours`) ist Pflicht auch im Play-by-Mail-Modus — ohne sie blockiert ein inaktiver Spieler alle anderen. Singleplayer nutzt immer das Zeitmodell.

---

### Aufgabenpool

10 Aufgabentypen (Pool). Pro Run werden 3 gezogen — mehr Varianz reduziert Wiederholungsgefühl. Alle Aufgaben sind zivil erfüllbar (es gibt keinen Kampf mehr — Flotte/Systemkarte gestrichen, §8). Jede Aufgabe passt zu vorhandenen Spielmechaniken.

| # | Aufgabe | Kernmechanik | Spielstil |
|---|---------|-------------|-----------|
| 1 | **Handelsnetz** | X Handelsrouten aktiv + Gesamtvolumen Y Credits/Sol uber Z Sole aufrecht halten | Wirtschaft |
| 2 | **Forschungsvorsprung** | Mindestens 3 Forschungen auf Level 5+ bringen | Forschung/Aufbau |
| 3 | **Kolonieblute** | Vertrauen > 70 fur 10 aufeinanderfolgende Sole | Diplomatie/Zivilaufbau |
| 4 | **Selbstversorgung** | Organika positiv produzieren (Netto > 0) **und** einen Werkstoff-Vorrat ≥ X Einheiten bei durchgehend positivem Credits-Saldo halten — für 15 aufeinanderfolgende Sole. (Werkstoffe sind nicht produzierbar, §3 — getestet wird stabiles Import-Management, nicht Eigenproduktion.) | Wirtschaft/Aufbau |
| 5 | **Expeditionsstatus** | Alle Tiles der Exploration Zone vollständig aufgedeckt (gesamter äußerer Bereich, nicht nur Ring 1–2) | Exploration/Navigation |
| 7 | **Handelspartner** | Mindestens X Transaktionen mit dem Reisenden Händler abgeschlossen + Credits-Saldo danach stets positiv | Wirtschaft |
| 8 | **Ingenieursleistung** | Gesamt-SP-Kapazität aller Gebäude (Summe `max_status_points` aller colony_buildings) uber Schwelle Y | Aufbau/Optimierung |
| 9 | **Kreditimperium** | Credits-Bestand X Sole uber Schwelle Y halten (kein einmaliger Peak, sondern anhaltender Wohlstand) | Wirtschaft |
| 10 | **Expertenstab** | Alle 5 Berater-Slots besetzt + mindestens 2 Berater auf Rang Senior oder höher | Aufbau/Personal |
| 11 | **Effizienzsprung** | AP-Nutzungsrate >= 90% fur 5 aufeinanderfolgende Sole (verbrauchte AP / produzierte AP) | Optimierung/Hardcore |

> ⚠️ BALANCE CONCERN: Aufgaben 1, 7, 9 (alle Wirtschaft) dürfen nicht alle drei gleichzeitig gezogen werden. Aufgaben-Sets müssen mindestens 2 verschiedene Spielstilkategorien abdecken — eine Kombo-Blacklist ist vor der Implementierung zu definieren.

> ⚠️ BALANCE CONCERN: Aufgabe 11 (Effizienz) kollidiert strukturell mit massivem Bauen (Aufgaben 2, 8) — "AP-effizient" und "viel bauen" sind Gegensätze. Aufgabe 11 sollte nie zusammen mit Aufgabe 2 oder 8 gezogen werden.

---

### "2 von 3"-Mechanik

**Bewertung: gut.** Die Mechanik gibt dem Spieler echte Wahlfreiheit, ohne den Run zu trivial zu machen. Eine verfehlte Aufgabe beendet den Run nicht — das reduziert Frustration und fuhrt zu mehr strategischen Entscheidungen ("Welche zwei lohnen sich fur meine aktuelle Ausgangslage?").

**Milestones gegen zu fruhen Fokus-Verlust:**
- Sol 30: Mindestens 1 Aufgabe muss zu > 50% erfullt sein. Sonst: Nexus-Warnung im INNN-Feed ("Die Expedition gerät ins Stocken — Nexus Command erwartet Fortschritt").
- Sol 50: Wenn noch keine Aufgabe vollständig erfullt, zweite Nexus-Warnung mit Sol-Countdown.

Diese Milestones sind weich (kein Fail, nur Feedback) und erzeugen Dringlichkeitsgefuhl ohne Frustration. **Nexus ist der Absender** — die Nachrichten kommen nicht anonym vom System, sondern von der übergeordneten Instanz, die den Spieler ausgesandt hat.

---

### Spieler-Rolle: Der Direktor

Der Spieler trägt den Titel **Direktor** (oder Direktorin). So nennen ihn die Kolonisten — es ist die informelle, täglich gebrauchte Anrede.

Nexus-intern heißt die Position **Konzessionär**: jemand der eine Betriebslizenz von einer übergeordneten Instanz auf Zeit erhalten hat, vertraglich gebunden ist und selbst das Risiko trägt — weder einfacher Angestellter noch unabhängiger Eigentümer.

**Nexus** ist kein Staat und keine Armee — es ist ein interstellares Entwicklungskonsortium, das Kolonisierungsrechte vergibt, Startkapital vorschießt und am Ende Rechenschaft erwartet. Der Spieler hat eine Konzession unterzeichnet: Aufbau und Betrieb einer Siedlung auf einem zugewiesenen Planeten, für eine definierte Laufzeit, gegen Vorauszahlung in Credits. Was in der Konzession nicht steht: wie rau die Bedingungen vor Ort sind, was die Kolonisten wirklich brauchen, und wie wenig Nexus bereit ist zu helfen wenn es brennt.

Der Direktor steht zwischen zwei Loyalitäten: den Kolonisten (Vertrauen) und Nexus (Schulden). Wer zu sehr für Nexus optimiert, verliert das Vertrauen der Siedler. Wer Nexus ignoriert, wird zurückgerufen. Das ist kein Widerspruch — das ist der Job.

---

### Nexus als Hintergrund-Akteur

Nexus ist nicht nur der narrative Rahmen des Runs — es ist ein aktiver, aber stiller Spielakteur. Es überwacht die Kolonie und interveniert an definierten Schwellwerten. **Alle Nexus-Interventionen sind einmalige Effekte — kein permanenter State-Flip.**

Kommunikationskanal: ausschließlich der INNN-Feed. Nexus sendet keine Dialogfenster, keine Popups — nur INNN-Ereignisse mit Absender "Nexus Command".

#### Boni (wenn der Spieler ahead-of-curve liegt)

Nexus belohnt Kolonien, die ihre Milestone-Ziele übertreffen:
- Credits-Transfer ("Nexus genehmigt Betriebsmittelzulage")
- Temporärer AP-Boost eines Berater-Typs für 3 Sole
- Aufgaben-Variante wird leicht entspannt (z.B. Zielwert um 10% gesenkt)

#### Sanktionen (wenn der Spieler hinter Plan liegt)

Nexus erhöht den Druck auf Kolonien, die Milestones verfehlen:
- Berater kurz abgezogen ("vorübergehend für administrative Zwecke einberufen") — 1 Sol AP-Drop
- Kleine Credits-Gebühr ("Overhead für Missionsaufsicht")
- Gnadenfrist-Verkürzung (siehe unten)

Sanktionen erscheinen nie ohne vorherige INNN-Warnung.

#### Gnadenfrist

Ab Sol 80 zeigt das UI den Countdown sichtbar ("Noch 20 Sole bis Missionsende"). Nexus tritt jetzt aktiver in Erscheinung:

- **Sol 85:** Wenn noch keine Aufgabe vollständig erfüllt ist → Nexus verhängt eine Sanktion (1 Berater 1 Sol abgezogen) **und** verkürzt das effektive Ende auf Sol 95. Der Spieler sieht im INNN-Feed: "Nexus Command hat die Frist auf Sol 95 vorgezogen."
- **Sol 90:** Letzte Warnung falls immer noch 0 Aufgaben erfüllt.
- **Sol 95/100:** Run endet — Fail State 2.

Wer hingegen bei Sol 85 bereits 1 Aufgabe erfüllt hat, erhält eine neutrale Statusmeldung ("Nexus registriert Fortschritt — Mission läuft.") ohne Sanktion.

> **TODO (Implementierung):** Nexus-Trigger-Tabelle definieren — welche Metrik, welcher Schwellwert, welche Reaktion, welche Phase. Muss vor der Implementierung als Config-Tabelle in `config/game.php → run.nexus_triggers` abgelegt werden.

> **TODO (Design):** Nexus-Boni in Phase 1 oder erst ab Phase 2? Phase-2-only wäre einfacher und vermeidet, neue Spieler zu bevormunden.

> **TODO (UI):** Nexus-Absender-Icon im INNN-Feed (niedrige Priorität, vor Frontend-Phase klären).

---

### Fail States

Genau 3 Fail States.

**Fail State 1 — Vertrauen kollabiert:**
Das Vertrauen der Kolonisten in den Direktor bleibt für N aufeinanderfolgende Sole unter einem kritischen Schwellenwert (z.B. < 10).
- Begründung: Die Kolonisten verlieren den Glauben an ihre Führung. Der Direktor wird abgesetzt und muss die Kolonie verlassen.
- Vorwarnung: INNN-Ereignis wenn Vertrauen unter 20 fällt. Roter UI-Indikator bei Vertrauen < 10. Countdown-Anzeige "Noch N Sole bis Abberufung" wenn Zustand anhält.
- Run-Ende mit Meldung: "Die Kolonisten haben das Vertrauen verloren. Der Direktor wurde abgesetzt."

**Fail State 2 — Nexus-Schulden zu hoch:**
Die Schulden beim Nexus-Konsortium überschreiten das Schuldenlimit.
- Begründung: Nexus hat dem Direktor eine Konzession erteilt und Startkapital vorgeschossen. Unkontrollierte Schulden führen zur Rückberufung — der Direktor wird "gefeuert".
- Run-Ende mit Meldung: "Nexus hat die Konzession entzogen. Der Direktor wurde zurückgerufen."

**Nexus-Schulden-Mechanik:**
- Schulden akkumulieren durch: Startkapital (3.000 Cr Vorschuss) + weitere Nexus-Deals (zusätzliche Credits leihen gegen mehr Schulden)
- Keine Zinsen
- Rückzahlung: nur manuell (Spieler überweist aktiv über den Nexus-Außenposten)
- **Schuldenlimit: 12.000 Cr** (fester Wert, klar kommuniziert als Balken im UI)
- UI-Label: "Nexus-Kredit: X / 12.000 Cr" — Farbwechsel gelb bei 80%, rot bei 95%
- Bei >95%: einmalige INNN-Meldung von Nexus, die Vertrauen leicht senkt ("Die Kolonisten merken, dass etwas nicht stimmt")
- Lose Kopplung mit Vertrauen: kein automatischer Zusammenhang. Der Spieler managt beide Achsen aktiv.

**Fail State 3 — Zeitablauf:**
Das Sol-Limit des Runs wird erreicht ohne dass 2 von 3 Aufgaben erfüllt wurden.
- Begründung: Sauberes, vorhersehbares Ende. Verhindert Endlos-Sessions ohne Ziel.
- Sol-Limit: 100 Sole (konfigurierbar in `config/game.php → run.tick_limit`).
- Countdown im UI sichtbar ab Sol 80 ("Noch 20 Sole bis Missionsende").

---

### Highscore-Berechnung (Entwurf)

```
score = (aufgaben_erfullt × 1000) + (tick_limit - erfullt_in_tick) × 10 + (credits_rest / 10) + (vertrauen_at_end × 5)
```

Komponenten:
- Aufgabenanzahl (2 oder 3) als Hauptfaktor
- Geschwindigkeit (fruheres Erfullen = mehr Punkte)
- Wohlstand (verbleibende Credits)
- Koloniequalität (Vertrauen am Ende)

> ⚠️ BALANCE CONCERN: Highscore-Formel ist ein erster Entwurf. Gewichtung muss nach ersten Playtests kalibriert werden. Ziel: 3-von-3-Sieg sollte deutlich mehr Punkte ergeben als 2-von-3, aber ein schneller 2-von-3-Sieg kann einen langsamen 3-von-3-Sieg ubertrumpfen.

---

### Lobby-Screen (Run-Einstieg)

Jeder Run beginnt mit einem **Lobby-Screen**, der nach Login erscheint wenn kein laufender Run existiert oder ein neuer Run vorbereitet wurde.

#### Ablauf

1. **Nach Registrierung:** `OnboardingService::setupNewPlayer()` erstellt Colony, Startressourcen und Gebäude wie bisher — setzt aber `started_at = null`. Der Run hat `status = 'active'`, ist aber noch nicht gestartet.
2. **Nach Login:** Route `/lobby` ist der feste Einstieg. Controller-Logik:
   - Run `status = 'active'` UND `started_at != null` → direkter Redirect zur Colony-Ansicht.
   - Run `status = 'active'` UND `started_at = null` → Lobby-Screen anzeigen.
   - Kein aktiver Run (Run beendet, oder noch kein Run) → Lobby-Screen mit "Neuen Run starten"-Option.
3. **"Mission starten"-Button:** POST-Request setzt `started_at = now()`, Redirect zur Colony-Ansicht. Das ist der einzige Ort wo `started_at` geschrieben wird.

#### Was der Screen zeigt (Minimal-Version)

- Koloniename — editierbar vor dem ersten Klick auf "Mission starten", danach fix
- Nexus-Briefing — statischer Lore-Text als narrativer Einstieg: "Direktor, Ihre Konzession wurde aktiviert. Die Kolonie wartet auf Ihre Ankunft."
- "Mission starten"-Button

#### Erweiterung Phase 4+

- Liste vergangener Runs: Sol-Anzahl, erzielte Aufgaben, Highscore
- "Neuen Run starten"-Button wenn aktiver Run beendet ist (status = 'completed' oder 'failed')
- Zukünftig: Schwierigkeitsauswahl oder Run-Optionen (z.B. Kenntnisauswahl, Startbedingungen)

#### Designentscheid: Warum Option B (eigene Route), nicht Modal

Ein Modal bietet keinen Platz für die spätere Erweiterung (Highscores, Run-Liste). Die feste Route `/lobby` ist der kanonische Einstiegspunkt — sie bleibt auch nach Phase 3 stabil. Ein Modal wäre Sackgasse.

#### Technische Anmerkung zu `started_at = null`

`started_at = null` bei `status = 'active'` ist kein neuer Run-Status, sondern ein Zustand "vorbereitet, nicht gestartet". `scopeActive()` filtert nur auf `status`, nicht auf `started_at` — das ist korrekt, weil Colony und Ressourcen bereits existieren und z.B. für den Onboarding-Screen gebraucht werden. Kein anderer Game-Loop-Code (TickService, GameTick) verarbeitet einen Run ohne `started_at`.

---

### Implementierungshinweise

- Neue Tabellen: `run_objectives` (aktive Aufgaben des aktuellen Runs), `run_state` (Phase, Tick-Start, Tick-Limit, Fail-State-Tracking)
- `config/game.php → run` — Tick-Limit, Tick-Dauer, Spieleranzahl, PbM-Modus, Nexus-Trigger-Tabelle, Score-Formel-Gewichte
- Aufgaben-Fortschritt wird bei jedem Tick-Schritt geprüft (nach Schritt 7 "Advisor Ticks")
- Phase-1-Check nach Tick-Schritt 4 (Building Decay) sinnvoll, da Gebäude-Level dann aktuell ist
- Nexus-Interventionen: GameTick prüft nach Aufgaben-Fortschritt die Nexus-Trigger-Tabelle und erzeugt ggf. INNN-Events mit `sender = 'nexus'`
- Lobby-Route: `GET /lobby` (LobbyController@show) + `POST /lobby/start` (LobbyController@start). Auth-Middleware, kein Game-Loop-Zugriff vor `started_at != null`.

---

*Dokument erstellt: 2026-03-26. Weitere Abschnitte werden im Verlauf von Phase 2 ergänzt.*

---

## 16. Onboarding

### Designprinzipien für Onboarding

Vier Prinzipien leiten das gesamte Onboarding-Design und haben Vorrang vor allen konkreten Maßnahmen:

1. **Lernen durch Tun, nicht durch Lesen.** Erklärungen erscheinen genau dann, wenn sie relevant sind — nicht vorab und nicht als Pflichtlektüre.
2. **Kein separater Tutorial-Modus.** Onboarding passiert im echten Spielzustand. Tag 1 ist der echte Spielstart. Was im Onboarding gebaut wird, bleibt erhalten.
3. **Erfahrene Spieler werden nicht bevormundet.** Alle Hinweise sind schließbar, überspringbar oder deaktivierbar. Wer weiß was er tut, soll das sofort tun können.
4. **Minimaler Implementierungsaufwand.** Das System darf keine komplexe State-Maschine erfordern. Jede Maßnahme muss einzeln implementierbar und wartbar sein.

---

### Das Cold-Start-Problem

Ein neuer Spieler sieht nach dem ersten Login:

- Die **Koloniekarte** (Hex-Grid): CC Lv1 und Harvester sind gesetzt, der Rest der Kolonie-Zone ist leer.
- Den **Techtree-Screen**: 11 Gebäude-Kacheln, 7 Kenntnis-Kacheln, 3 Schiffstypen, 5 Berater-Typen — alle ohne Erklärung der Zusammenhänge.
- Die **Ressourcenleiste**: 3.000 Credits, 200 Regolith, 0 Werkstoffe, 0 Organika.

Das Problem: Der Spieler sieht viele Optionen, hat aber keinen Hinweis, welche davon den größten Effekt hat. Jede Option sieht gleichwertig aus. Das erzeugt Paralyse.

**Die Lösung ist nicht mehr Information — sie ist Fokussteuerung.** Eine einzige klar hervorgehobene "erste Aktion" beseitigt die Paralyse ohne den Spieler in eine Reihenfolge zu zwingen.

---

### § 16.1 — Der erste Bildschirm: Nexus-Briefing

**Mechanik:** Beim allerersten Login eines Runs erscheint eine **Nexus-Nachricht im INNN-Feed** (kein Popup, kein Modal). Die Nachricht ist bereits sichtbar ohne dass der Spieler aktiv etwas öffnen muss — sie ist der erste Eintrag im INNN-Feed, Absender "Nexus Command", Priorität "dringend".

**Inhalt der Nachricht (Ton: karg, professionell, Roguelike-Atmosphäre):**

> **Nexus Command — Einsatzüberblick**
>
> Direktor, Ihre Konzession ist aktiv. Folgendes liegt vor:
>
> — Kommandozentrale (Lv1): betriebsbereit
> — Harvester (Lv1): Regolith-Produktion läuft
> — Startkapital: 3.000 Cr. Regolith-Reserve: 200 Rg
>
> Erste Priorität: Kolonie lebensfähig machen. Dafür brauchen Sie Wohnraum.
> Zweite Priorität: Personal einstellen — Kolonien ohne Berater handeln langsam.
>
> Der Rest liegt bei Ihnen.

Diese Nachricht erfüllt vier Funktionen gleichzeitig:
- Erklärt den Startzustand narrativ (Frontier-Atmosphäre bleibt erhalten)
- Benennt die erste sinnvolle Aktion ("Wohnraum")
- Verankert den Berater-Mechanic als frühe Priorität
- Ist kein Popup — erfahrene Spieler überlesen sie ohne Unterbrechung

**Technisch:** Die Nachricht wird beim Erzeugen eines neuen Runs über `InnnService::createEvent()` mit `sender = 'nexus'` erzeugt. Kein neues Schema erforderlich.

---

### § 16.2 — "Nächste sinnvolle Aktion": das Hint-System

Das **Hint-System** zeigt zu jedem Zeitpunkt genau **einen** hervorgehobenen Hinweis an. Nie mehr als einen gleichzeitig. Der Hinweis verschwindet sobald die Aktion ausgeführt wurde.

**Darstellung:** Eine schmale, schließbare Hinweis-Leiste direkt unterhalb der Ressourcenleiste. Hintergrundfarbe: gedämpftes Gelb (Warnton, kein Alarm). Maximale Länge: eine Zeile Text + ein Aktions-Link.

Beispiel-Darstellung:
```
[!] Kein Wohnhabitat gebaut — Supply-Cap bleibt bei 10. → Jetzt bauen
                                                                          [×]
```

Der Aktions-Link führt direkt zum relevanten Screen oder zur entsprechenden Kachel — kein Suchen nötig.

**Priorisierung: Der jeweils dringendste Zustand gewinnt.** Die Hinweise sind nach Dringlichkeit geordnet; wenn mehrere Bedingungen gleichzeitig zutreffen, gewinnt der Eintrag mit dem höchsten Rang:

> ⚠️ BALANCE CONCERN / DOKU-DRIFT (2026-06-21): Die folgende Tabelle war seit längerem veraltet — die Implementierung (`app/Services/OnboardingHintService.php`) hat inzwischen **15 Ränge statt 8**. Tabelle unten korrigiert auf den tatsächlichen Stand. Zusätzlich wurden in `config/game.php → onboarding` mehrere Schwellen verschoben, ohne dass die Designentscheidungs-Texte darunter nachgezogen wurden (siehe Korrekturen je Eintrag).
>
> ⚠️ BALANCE CONCERN / IMPLEMENTIERUNGSAUFTRAG (2026-06-24): Mit der Pfadwahl-Überarbeitung (§4, §13) muss diese Tabelle nochmals angepasst werden — noch nicht in `OnboardingHintService.php` umgesetzt, siehe Implementierungs-Checkliste am Ende dieses Abschnitts. Konkret: (a) Rang 13 `hint_agrardome` ändert Charakter von "Empfehlung" zu "Pflicht-Gate-Hinweis" (Agrardom ist jetzt CC2-Voraussetzung, nicht mehr Wahlgruppen-Mitglied); (b) Rang 12/14 (`hint_6`/`hint_analytik`) brauchen ein drittes Pendant `hint_hangar_path`, alle drei jetzt als echte 3-Wege-Wahl statt 2-Wege; (c) `allChoiceBuildingsPlaced()` (aktuell Cantina+Agrardom+Analytik) muss auf die drei Pfad-Gebäude (Sciencelab/Hangar/Cantina) umgestellt werden, Agrardom raus.

| Rang | Key | Bedingung | Hinweistext (Kurzfassung) | Ziel-Link | Sol-Schwelle |
|------|-----|-----------|---------------------------|-----------|--------------|
| 1 | `hint_1` | Kein Baumeister-Berater aktiv | "Noch kein Baumeister eingestellt — Bau-AP bleibt beim Grundwert von 6." | `/advisors` | — (siehe Designentscheidung unten — bewusst ohne Schwelle/Alternative) |
| 2 | `hint_repair_urgent` | Gebäude (Level ≥ 1) auf/unter `hint_repair_urgent_sp` (3 von 20) — Leveldown-Gefahr | "Ein Gebäude steht kurz vor dem Stufenverlust — jetzt reparieren." | Colony-Screen | — |
| 3 | `hint_2` | Harvester steht auf `is_colony_zone=1`-Tile (Ring 1) | "Harvester steht noch in der Kolonie-Zone — verlegen." | Colony-Screen | — |
| 4 | `hint_repair` | Gebäude (Level ≥ 1) unter Maximal-Statuspunkten | "Deine Startgebäude sind beschädigt — Reparieren (1 Bau-AP)." | Colony-Screen | — |
| 5 | `hint_3` | CC Level < 2 — **Hinweis muss ab der Pfadwahl-Überarbeitung zusätzlich kommunizieren, dass Agrardom Pflicht-Voraussetzung für den CC2-Ausbau ist** (Gate, nicht nur Empfehlung) | "Kommandozentrale auf Level 2 ausbauen — Agrardom muss zuerst stehen." | Colony-Screen | Sol 2 (`hint_cc_upgrade_after_tick=1`) |
| 6 | `hint_advisor_slot2` | CC ≥ Lv2 und mindestens 1 freier Berater-Slot — **Slot-Inhalt ist jetzt generisch**, Hinweistext darf keinen festen Beratertyp mehr nennen | "Zweiter Berater-Slot frei — jetzt besetzen." | `/advisors` | — (sofort nach CC2) |
| 7 | `hint_cc_invest` | Sol 1, Sol-1-To-Dos erledigt, CC < Lv2, noch Bau-AP übrig | "Verbleibende Bau-AP in den CC-Ausbau stecken." | Colony-Screen | nur Sol 1 |
| 8 | `hint_explore` | Sol ≤ `hint_explore_until_tick` (0 → nur Sol 1), unentdeckte Tiles vorhanden, < 6 Ring-≥2-Tiles erkundet, günstigstes Tile bezahlbar | "Umgebung erkunden — Navigations-AP nutzen." | Colony-Screen | nur Sol 1 |
| 9 | `hint_4` | Keine Kenntnis auf Level > 0 | "Noch keine Kenntnis erforscht." | `/techtree` | **Sol 9** (`hint_no_knowledge_after_tick=8`, nicht Sol 8 wie zuvor dokumentiert — Tick 8 = Sol 9) |
| 10 | `hint_5` | Trust < -20 | "Vertrauen der Kolonie sinkt." | Colony-Screen | **Sol 6** (`hint_trust_min_ticks=5` → Tick 5 = Sol 6, nicht Sol 5) |
| 11 | `hint_build_priority` | ≥ 2 von (Sciencelab/Hangar/Cantina) gleichzeitig baubar (Voraussetzung erfüllt, Bau-Gate nicht verbraucht, nicht gebaut) — **Agrardom raus aus der Eligibility-Zählung (jetzt Pflichtgebäude, nicht Wahlgruppe), Hangar rein** | "Mehrere Gebäude bereit — eines auswählen." | Colony-Screen | — |
| 12 | `hint_6` | CC ≥ Lv2, Housing ≥ Lv1, Bau-Gate frei (< CC-Level−1 Pfad-Gebäude gebaut), keine Cantina, bezahlbar | "Cantina noch nicht gebaut." | `/colony/view?build=52` | **Sol 3** (`hint_no_cantina_after_tick=2`) — gleichrangig mit `hint_analytik` und `hint_hangar_path`, siehe §13 "Pfadwahl" |
| 13 | `hint_agrardome` | **Charakter geändert (2026-06-24): Pflicht-Gate-Hinweis statt Wahlempfehlung.** Harvester ≥ Lv1, kein Agrardom, bezahlbar, CC noch < Lv2 (Agrardom ist jetzt CC2-Voraussetzung) | "Agrardom bauen — Voraussetzung für CC-Ausbau auf Level 2." | `/colony/view?build=41` | **Sol 2** (`hint_no_agrardome_after_tick=1`) — unabhängig von der Pfadwahl Sciencelab/Hangar/Cantina, siehe unten |
| 14 | `hint_analytik` | CC ≥ Lv2, Bau-Gate frei, kein Analytik-Labor, bezahlbar | "Analytik-Labor noch nicht gebaut." | `/colony/view?build=31` | **Sol 3** (`hint_no_analytik_after_tick=2`) — gleichrangig mit `hint_6` und `hint_hangar_path` |
| 14b | `hint_hangar_path` **(neu, 2026-06-24)** | CC ≥ Lv2, Bau-Gate frei, kein Hangar, bezahlbar | "Hangar noch nicht gebaut." | `/colony/view?build=44` | **Sol 3**, identische Schwelle (`hint_no_hangar_after_tick=2`) — drittes gleichrangiges Mitglied der Pfadwahl |
| 15 | `hint_end_sol` | Fallback — greift nur wenn kein höherrangiger Hint aktiv ist | "Nichts mehr zu tun — Sol beenden." | Colony-Screen | jedes Sol (Universal-Floor) |

> **Bau-Gate-Hinweis (wichtig für Implementierung):** Sobald der Spieler eines der drei Pfad-Gebäude gebaut hat, dürfen `hint_6`/`hint_analytik`/`hint_hangar_path` für die jeweils noch nicht erreichte CC-Stufe nicht feuern — sie greifen erst wieder, wenn `Anzahl gebauter Pfad-Gebäude < CC-Level − 1` zutrifft (siehe §13). Beispiel: Bei CC Lv2 und bereits 1 gebautem Pfad-Gebäude zeigen die beiden übrigen Hints (für die anderen zwei Pfad-Gebäude) **nicht mehr** "jetzt bauen", weil das Bau-Gate sie blockiert — stattdessen sollte ein neuer, noch zu definierender Hint-Text "Bei CC Lv3 verfügbar" angezeigt werden (kein Aktions-Link, reine Information), damit der Spieler nicht auf ein Gebäude klickt, das der Server ablehnt.

**Neu seit letzter GDD-Fassung, bisher nicht dokumentiert:**
- `hint_advisor_slot2` (Rang 6): Direktes Feedback auf CC-Lv2-Ausbau — ohne diesen Hint bliebe die Hint-Leiste nach dem CC-Ausbau mehrere Sole leer, bevor `hint_4`/`hint_5`/`hint_6` ticken.
- `hint_cc_invest` (Rang 7) und `hint_explore` (Rang 8): Sol-1-spezifische Hints, die verbleibende Bau-AP bzw. Navigations-AP gezielt in CC-Vorinvestition bzw. Erkundung lenken, statt sie ungenutzt verfallen zu lassen (siehe Punkt "Kein Leerlauf" unten).
- `hint_build_priority` (Rang 11): Reine Strategie-Hinweisebene, kein Aktionslink zu einem einzelnen Gebäude — signalisiert nur, dass eine Wahl zwischen mehreren gleichwertig bereiten Gebäuden besteht.
- `hint_end_sol` (Rang 15): Universeller Fallback, der verhindert, dass die Hint-Leiste je leer bleibt, solange noch ungenutzte AP/Ressourcen vorhanden wären, die der Spieler stattdessen einfach für „Sol beenden" nutzen sollte.
- `canAffordBuildingPlacement()`-Gate auf Rang 12–14: Alle drei Bau-Hints (Cantina/Agrardom/Analytik) prüfen jetzt zusätzlich tatsächliche Bezahlbarkeit (Bau-AP, Regolith, Werkstoffe, Supply) bevor sie feuern — verhindert, dass der Hint auf ein Gebäude zeigt, das der Spieler in diesem Sol gar nicht bauen kann.

> **Designentscheidung zu Rang 2 (Reparieren dringend):** Eigener Hint getrennt vom Lehr-Hint `hint_repair` (Rang 4). `hint_repair` wird beim ersten Reparieren-Klick dauerhaft dismissed (Lehrmoment „du kannst reparieren"). `hint_repair_urgent` warnt dagegen **wiederkehrend** vor dem einzigen irreversiblen Verlust (Leveldown bei SP 0): er ist nicht dismissbar (kein Eintrag in `dismissed_hints`), selbst-clearend sobald alle Gebäude wieder über `hint_repair_urgent_sp` liegen, und feuert bei jedem erneuten Verfall. Höchste Repair-Priorität, nur hinter `hint_1` (Baumeister liefert die Bau-AP). Schwelle 3/20 (≈15%) gibt selbst beim schnellsten Verfall (Cantina, 2 SP/Tick) noch >1 Sol Reaktionszeit.

> **Designentscheidung zu Rang 3 (Harvester):** Der Harvester startet auf Ring-1-Tile (1,0) = `regolith_normal, is_colony_zone=1`. Das ist technisch ein Regolith-Tile, liegt aber in der Kolonie-Zone — die für Gebäude reserviert ist. Der Hint motiviert, ihn auf Ring 2 zu verlegen. Ring-2-Tile (2,0) ist `regolith_normal, is_explored=1` (Nexus-Scout hat es bei Ankunft vorab erkundet, Nexus-Briefing erklärt das). Nach dem Verlegen ist das Ring-1-Tile für Gebäude frei.

> **Designentscheidung zu Rang 4 (Reparieren — Lehr-Hint):** Alle drei Startgebäude starten auf `status_points=16/20` (80% — beschädigt aber funktionsfähig). Kein Tick-Gate; der Hint erscheint ab Sol 1. **Verschwindet beim ersten Reparieren-Klick** (dauerhaft dismissed): Reparieren ist ein Lehr-Hint — der Spieler soll lernen DASS er reparieren kann, nicht dass er alles sofort voll reparieren MUSS. Bewusst hinter dem Harvester-Hint (Rang 3): alle drei Gebäude voll zu reparieren kostet ~12 Bau-AP — mehr als ein Sol liefert (~10 mit Baumeister). Käme der Repair-Hint zuerst und bliebe kleben, säße der Spieler auf einem in Sol 1 nicht abschließbaren Hinweis fest. Das billige Harvester-Verlegen (~2 AP) geht voran. Der kritische Fall (Leveldown-Gefahr) wird vom separaten `hint_repair_urgent` (Rang 2) abgedeckt.

> **Designentscheidung zu Rang 5 (Sol-Schwelle):** CC-Ausbau kostet `ap_for_levelup = 10` Construction-AP. Mit 6 Basis-AP + 4 Baumeister-Junior = 10 AP/Sol ist CC Lv2 frühestens nach Sol 1 erreichbar. Die Schwelle Sol 2 verhindert, dass der Hint sofort nach dem Baumeister-Hire erscheint bevor der Spieler auch nur einen AP ausgegeben hat.
>
> **Verifikation (2026-06-21) — Werte bestätigt, Werte verschoben:** `config('game.advisor.ap_per_rank')[1] = 4` bestätigt den Junior-Bonus von +4 AP. `ap_for_levelup=10` für CC bestätigt (per DB-Migration `2026_04_17_000003_calibrate_building_ap_costs.php`, nicht in `config/buildings.php` selbst — dort gibt es kein `ap_for_levelup`-Feld, das Feld lebt in der `buildings`-Tabelle). Die Sol-Schwelle selbst hat sich jedoch verschoben: `config('game.onboarding.hint_cc_upgrade_after_tick') = 1`, das entspricht weiterhin Sol 2 (Tick 1 = Sol 2) — hier kein Drift. **Drift gefunden bei anderen Schwellen:** `hint_no_knowledge_after_tick=8` → Sol 9, nicht Sol 8 wie in der alten Tabelle (§16.2) stand; `hint_trust_min_ticks=5` → Sol 6, nicht Sol 5; die alte Cantina-Schwelle "Sol 8" existiert in der neuen Implementierung gar nicht mehr — `hint_no_cantina_after_tick=5` → Sol 6. Alle drei Korrekturen sind in der Tabelle in § 16.2 oben eingearbeitet.

> **Designentscheidung (2026-06-21) zu Rang 1 — "Baumeister zuerst" ist gewollt, kein offener Punkt:** Der vorherige Befund 2 (siehe unten) kritisierte, dass `hint_1` ohne Tick-Schwelle und ohne Alternative ausschließlich auf den Baumeister verweist und damit faktisch die einzige vom Hint-System unterstützte Eröffnung erzwingt. Der Owner hat dazu entschieden: **Das ist beabsichtigt, nicht zu ändern.** Baumeister/Bau-AP ist die strukturell einzige Ressource, die in Sol 1 *alles* andere freischaltet — Wohnraum, Harvester-Verlegung, CC-Ausbau, später jedes Gebäude. Ohne Bau-AP-Hebel bleibt der Spieler in Sol 1 handlungsarm, unabhängig davon, welchen strategischen Pfad er danach einschlägt. Ein Spieler, der bewusst zuerst Konsul oder Analytiker einstellen will, kann das weiterhin jederzeit tun — `hint_1` ist dismissable (pro Hint-Typ) und blockiert keine Aktion, er ist lediglich der dauerhaft höchstrangige *Hinweis*, kein Gate. Die in Befund 2 vorgeschlagenen Alternativen (`hint_no_scientist`/`hint_no_trader` auf Rang 1, dynamischer Rang-1-Text) werden **nicht** umgesetzt. Befund 2 bleibt unten als Analyse-Dokumentation stehen, ist aber mit dieser Designentscheidung als abgeschlossen zu lesen — kein offener Implementierungsauftrag mehr an `game-developer`/`backend-coder`.

> **Designentscheidung (2026-06-21, Agrardom-Teil überholt 2026-06-24 — siehe unten) — Sol-1/Sol-2-Fokus: ausschließlich Bau und Erkundung.** Die ersten beiden Sole sind bewusst auf zwei AP-Pools verengt: Construction (Bau-AP) und Navigation (Erkundungs-AP). Kein Hint darf vor Sol 3 (current_tick=2) in Richtung Cantina, Analytik-Labor oder Hangar drängen — diese Phase ist reine Aufbau- und Sichtbarkeits-Phase (Baumeister, Harvester-Verlegung, Agrardom als CC2-Pflichtgebäude, CC-Ausbau, Erkundung), nicht bereits eine Wirtschafts-, Forschungs- oder Flotten-Entscheidung. Geprüfter Stand der Tick-Schwellen (`config/game.php → onboarding`):
> - `hint_no_agrardome_after_tick = 1` → frühestmöglich Sol 2. Agrardom ist seit der Pfadwahl-Überarbeitung (2026-06-24) **kein Wahlgruppen-Mitglied mehr, sondern Pflicht-Voraussetzung für CC Lv2** (siehe §4, §13) — der Hint bleibt deshalb im Sol-1/2-Fenster, jetzt aber mit verschärftem Charakter: Er weist nicht mehr nur auf eine sinnvolle Option hin, sondern auf ein hartes Gate, das den CC-Ausbau blockiert solange es nicht erfüllt ist.
> - `hint_no_cantina_after_tick`, `hint_no_analytik_after_tick` und das neue `hint_no_hangar_after_tick` (alle = 2, Sol 3) → vor Sol 3 feuert keiner der drei Pfad-Hints. Der Sol-1/2-Fokus gilt jetzt für eine **dreigleisige** Wahlachse statt der vormaligen zweigleisigen.
> - `hint_4` (Kenntnis fehlt, Sol 9) und `hint_5` (Vertrauen kritisch, Sol 6) liegen ohnehin weit nach Sol 1/2 und tangieren den Fokus nicht.

> **Designentscheidung (2026-06-24, ersetzt die Fassung vom 2026-06-21) — Sol 3: echte, gleichwertige Wahl zwischen drei Pfaden (Sciencelab, Hangar, Cantina).** Ab Sol 3 (current_tick=2) stehen `hint_6` (Cantina), `hint_analytik` (Sciencelab) und `hint_hangar_path` (Hangar) auf derselben Tick-Schwelle (`hint_no_cantina_after_tick = hint_no_analytik_after_tick = hint_no_hangar_after_tick = 2`). Keiner der drei Hints hat einen strukturellen Vorlauf vor den anderen — alle drei werden, sobald ihre jeweiligen Prerequisites (CC≥Lv2 für alle drei; Cantina zusätzlich Housing≥Lv1) erfüllt **und** das Bau-Gate (§13: `Anzahl gebauter Pfad-Gebäude < CC-Level − 1`) noch nicht erschöpft ist, gleichzeitig "bereit". Sobald der Spieler eines der drei gebaut hat, verschwindet dessen Hint dauerhaft (Gebäude existiert) und die übrigen zwei bleiben aktiv, bis entweder gebaut oder das Bau-Gate sie für die aktuelle CC-Stufe sperrt (dann zeigt ein neuer, informativer Hinweistext "Bei CC Lv3/4 verfügbar" ohne Aktions-Link — siehe Implementierungs-Hinweis bei der Hint-Tabelle).
>
> **Standard-Empfehlung, keine Zwangsregel:** Wer Cantina zuerst priorisiert, sollte als nächsten Berater eher den **Konsul** anwerben. Wer Sciencelab zuerst priorisiert, eher den **Analytiker**. Wer Hangar zuerst priorisiert, eher den **Raumfahrer**. Diese Zuordnung ist die naheliegende Standardlinie und ergibt sich jetzt sogar **mechanisch** aus dem generischen Slot-System (§13): Der Slot, der durch das zuerst gebaute Pfad-Gebäude freigeschaltet wird, *ist* genau dieser passende Beratertyp — es gibt keine Möglichkeit mehr, "das falsche" Pendant in Slot 2 zu bekommen. Es bleibt weiterhin **explizit Raum für ausgefuchste Taktiken** (z. B. Pfad-Gebäude über Grund-AP abdecken, ohne den zugehörigen Slot sofort zu besetzen — der Slot bleibt einfach offen, bis der Spieler ihn füllen will). Das deckt sich mit §16.7 "Kein Pflicht-Reihenfolge".

> **Designentscheidung (2026-06-24, ersetzt "Agrardom ist unabhängig von der Sol-3-Wahlgruppe" vom 2026-06-21) — Agrardom ist Pflichtgebäude, kein Wahlgruppen-Mitglied mehr.** Frühere Fassung: Agrardom war von der Cantina-vs.-Analytik-Wahlgruppe entkoppelt, aber weiterhin optional. Neue Fassung: Agrardom ist jetzt **CC2-Bau-Voraussetzung** (siehe §4). Der `hint_agrardome`-Hint bleibt auf derselben Sol-2-Schwelle, ändert aber seine Funktion von "Empfehlung, weil keine vergleichbare strategische Verzweigung" zu "Pflicht-Warnung, weil CC-Ausbau sonst blockiert ist". `hint_build_priority` (Rang 11) bezieht Agrardom **nicht mehr** in die Eligibility-Zählung der Pfadwahl ein — die Wahlgruppe besteht jetzt ausschließlich aus den drei echten Pfaden Sciencelab/Hangar/Cantina.

**Deaktivierung:** Das Hint-System kann in den Einstellungen dauerhaft abgeschaltet werden (`onboarding_hints = false` in User-Preferences). Default: aktiviert. Schließen (`[×]`) eines Hinweises deaktiviert nur diesen spezifischen Hinweistyp bis zum Ende des Runs.

> **Designentscheidung:** Das System prüft Zustände, keine Sequenzen. Es gibt keine "abgehakten Tutorial-Schritte" — nur eine kontinuierliche Zustandsauswertung. Das ist wartungsarm und funktioniert ohne State-Maschine.

> **Designentscheidung:** Nur ein Hinweis gleichzeitig, nie eine Liste. Eine Liste erzeugt denselben Paralyseeffekt wie keine Hinweise. Der Spieler braucht eine klare Richtung, keine Aufgabenübersicht.

> ⚠️ BALANCE CONCERN: `hint_4` (Kenntnis-Hint, jetzt Rang 9) feuert ab Sol 9 (`hint_no_knowledge_after_tick=8`), während `hint_analytik` (Gebäude fehlt) bereits ab Sol 3 (`hint_no_analytik_after_tick=2`) feuert. Das Analytik-Labor hat damit in der Praxis schon 6 Sole Vorlauf, bevor `hint_4` überhaupt aktiv werden kann — der ursprüngliche Concern ("Kenntnis-Hint feuert vor dem Gebäude-Hint") ist mit der Sol-3-Anpassung von `hint_no_analytik_after_tick` hinfällig. Es bleibt aber sinnvoll, `hint_no_knowledge_after_tick` so zu belassen oder eher zu erhöhen als zu senken — er markiert keinen Eröffnungszwang, sondern eine späte Sicherheitswarnung für Spieler, die nach 9 Solen noch gar keine Kenntnis erforscht haben (unabhängig davon, ob sie den Cantina-, Analytik- oder Hangar-Pfad gewählt haben).

---

### § 16.3 — Visuelles Hervorheben: "Pulse"-Indikator

**Mechanik:** Wenn eine Techtree-Kachel oder ein Tile auf der Koloniekarte den ersten empfohlenen nächsten Schritt darstellt, erhält sie einen **Pulse-Indikator** — eine dezente, langsam pulsierende SVG-Umrandung (CSS animation `ring-pulse`, 2s Periode, ein Atemzug-Rhythmus, nicht aufdringlich).

**Trigger:** Der Pulse-Indikator wird ausschließlich durch denselben Zustandscheck wie das Hint-System gesteuert. Er zeigt auf genau die Kachel oder den Tile, auf den der aktive Hinweis verweist. Kein Pulse ohne zugehörigen Hint.

**Konkrete Darstellung (Phase 3e):**

| Hint-Rang | Pulsierendes Element |
|-----------|----------------------|
| 1 (kein Baumeister) | Baumeister-Slot im Berater-Screen |
| 3 (Harvester in Colony-Zone) | Harvester-Tile auf Koloniekarte + Ziel-Ring-2-Tile (2,0) |
| 5 (CC Level < 2) | CC-Tile auf Koloniekarte |
| 9 (kein Wissen) | Analytik-Labor-Kachel im Techtree (wenn noch nicht gebaut) oder erste verfügbare Kenntnis-Kachel |
| 10 (Vertrauen < -20) | Erste verfügbare positive Vertrauensgebäude-Kachel |
| 12 (keine Cantina) | Cantina-Kachel im Techtree |
| 14b (kein Hangar) **(neu, 2026-06-24)** | Hangar-Kachel im Techtree |

**Deaktivierung:** Zusammen mit dem Hint-System (gleiche Einstellung).

> ⚠️ BALANCE CONCERN / DOKU-DRIFT: Diese Tabelle deckt nur 7 der jetzt 16 vorgesehenen Hint-Ränge ab (siehe § 16.2). Für die seit Phase 3g neuen Hints (`hint_repair`, `hint_repair_urgent`, `hint_advisor_slot2`, `hint_cc_invest`, `hint_explore`, `hint_build_priority`, `hint_agrardome`, `hint_analytik`, `hint_hangar_path`, `hint_end_sol`) ist nicht spezifiziert, welches Element pulsieren soll bzw. ob sie überhaupt einen Pulse erhalten. Muss vor dem nächsten UI-Pass mit `ui-specialist` geklärt werden — insbesondere `hint_end_sol` (Rang 15) sollte vermutlich KEINEN Pulse auf eine Kachel legen, sondern (falls überhaupt visuell hervorgehoben) auf den „Sol beenden"-Button.

**Abgrenzung zu bestehenden Indikatoren:** Der Tiefenscan-Pulse auf der Koloniekarte (bestehend, § 4a) ist ein anderer Indikator-Typ (orangefarbene Blitz-Animation). Onboarding-Pulse ist blau-weißlich — visuell eindeutig unterscheidbar.

> ⚠️ BALANCE CONCERN: Wenn zu viele Elemente gleichzeitig pulsierten (eigener Scan-Indicator, Onboarding-Pulse, zukünftige Event-Marker), wird die Karte visuell unruhig. Die Regel "nie mehr als ein Onboarding-Pulse gleichzeitig" muss auch auf UI-Ebene durchgesetzt werden.

---

### § 16.4 — Techtree-Kaltstart: Zugangshürde reduzieren

Der Techtree-Screen hat 11 Gebäude-Kacheln, 7 Kenntnisse, 3 Schiffe, 5 Berater — alle auf einmal sichtbar. Das ist für neue Spieler ein Orientierungsproblem.

**Maßnahme: Zustandsbasierte Kachel-Sortierung.**

Kacheln werden in drei Gruppen dargestellt, visuell getrennt durch einen Zwischenstrich und eine kleine Gruppenbezeichnung:

| Gruppe | Inhalt | Darstellung |
|--------|--------|-------------|
| **Jetzt verfügbar** | Gebäude, die Voraussetzungen erfüllt haben und sofort gebaut werden können | Normal hell, oben |
| **Voraussetzung fehlt** | Gebäude, die noch gesperrt sind | Gedimmt (Opacity 0.6), Tooltip zeigt was fehlt |
| **Bereits vorhanden** | Gebaute Gebäude | Grüner Statusring, unten oder ausgeblendet |

**Kein separater "Anfänger-Modus"** — das ist die Standarddarstellung für alle Spieler. Erfahrene Spieler profitieren ebenfalls von einer schnellen "Was ist gerade baubar?"-Übersicht.

**Tooltip bei gesperrten Kacheln:** Ein einzeiliger Hinweis direkt auf der Kachel (kein separates Modal): z.B. "Benötigt: CC Lv4" oder "Benötigt: Hangar". Der Tooltip erscheint nur on-hover — nicht dauerhaft als Text auf der Kachel.

> **Designentscheidung:** Die Kacheln werden nicht dauerhaft verborgen oder ausgeblendet — der Spieler sieht immer den gesamten Techtree. "Jetzt verfügbar" herauszuheben ist weniger invasiv als Inhalte zu verstecken. Transparenz über das gesamte System ist ein Nouron-Merkmal.

---

### § 16.5 — Die ersten 3–5 Aktionen: natürlicher Pfad

Der Startzustand (CC Lv1 beschädigt, Harvester Lv1 auf Ring-1, Housing Lv1 beschädigt, 3.000 Cr, 200 Rg) erzwingt einen natürlichen Pfad, wenn der Spieler dem Hint-System folgt. Der Pfad ist nicht zwingend — aber er ist der offensichtlich sinnvolle:

> **Startzustand (implementiert 2026-06-11):** CC, Harvester und Wohnhabitat starten auf Level 1 mit `status_points=16/20` (80% Zustand) — funktionsfähig, aber sichtbar beschädigt. Repair-Mechanik (AP → `status_points`) ist noch nicht implementiert; natürlicher Verfall macht Reparatur nach ~5–10 Solen nötig. Harvester startet auf Ring-1-Tile (1,0) = `regolith_normal, is_colony_zone=1`. Ring-2-Tile (2,0) = `regolith_normal, is_explored=1` ist vorab erkundet (Nexus-Scout).

**Aktion 1 — Baumeister einstellen (Berater-Screen)**

- Warum: Baumeister (+4 Construction-AP/Sol Junior) erhöht Bau-Tempo ab Sol 1; hint_1 zeigt auf `/advisors`
- Kosten: 300 Cr (Junior-Baumeister)
- Ergebnis: Construction-AP springt von 6 auf 10. AP-Chips aktualisieren sich sofort.
- Feedback-Loop klar: Berater-Card erscheint, Construction-AP-Anzeige springt hoch

**Aktion 2 — Harvester auf Ring-2-Regolith verlegen (Colony-Screen)**

- Warum: Harvester steht in der Kolonie-Zone (Ring 1) — dieser Slot ist für Gebäude reserviert. Nexus-Scout hat Ring-2-Tile (2,0) vorab erkundet; Ziel ist sichtbar.
- Kosten: 1 Construction-AP (Distanz 1 Hex)
- Ergebnis: Tile (1,0) in Ring 1 wird frei für Gebäude; Harvester produziert weiterhin Regolith
- Feedback-Loop klar: Harvester-Sprite bewegt sich auf neues Tile

**Aktion 3 — CC auf Level 2 ausbauen (Colony-Screen, ab Sol 2)**

- Warum: CC Lv2 schaltet zweiten Berater-Slot frei + 6 neue Kolonie-Zone-Tiles
- Kosten: 10 Construction-AP (kumuliert; mit 10 AP/Sol in Sol 2 erreichbar)
- Ergebnis: Neue Ring-2-Tiles leuchten auf Koloniekarte auf. Zweiter Berater-Slot erscheint.
- Feedback-Loop klar: Koloniekarte aktualisiert sich live (Ring-Expansion §4a)

**Aktion 4 — Sol 1 Rest-AP: Erkunden + CC-Vorinvestition (`hint_explore`, `hint_cc_invest`)**

- Warum: Nach Aktion 1–2 sind von 10 Construction-AP (6 Basis + 4 Baumeister-Junior) nur ~2 verbraucht (Harvester-Verlegung). `hint_cc_invest` (Rang 7) lenkt die verbleibenden ~8 Bau-AP in den CC-Ausbau (Ziel: 10 AP kumuliert für Lv2). Parallel liegen 6 Navigation-AP komplett ungenutzt — `hint_explore` (Rang 8) lenkt sie in die Tile-Erkundung (1 AP für Ring-2-Tiles, 2 AP für Ring-3).
- Kosten: ~8 Bau-AP (CC-Vorinvestition) + bis zu 6 Nav-AP (Erkundung, ring-gestaffelt)
- Ergebnis: CC-Levelup-Fortschritt 8/10 AP am Ende Sol 1 (Restbedarf 2 AP fließt in Sol 2 automatisch zuerst); mehrere neue Tiles aufgedeckt
- **Wichtig:** Ohne diese beiden Hints lägen in Sol 1 ~8 Bau-AP und alle 6 Nav-AP brach — siehe Befund 1 unten.

**Aktion 5 — CC auf Level 2 ausbauen (Colony-Screen, ab Sol 2, `hint_3`)**

- Warum: CC Lv2 schaltet zweiten Berater-Slot frei + 6 neue Kolonie-Zone-Tiles. **Voraussetzung seit 2026-06-24: Agrardom muss bereits Lv1 stehen** (siehe §4, §13) — ohne Agrardom bleibt der CC-Levelup-Button gesperrt, unabhängig von vorhandenen Bau-AP.
- Kosten: 10 Construction-AP kumuliert (mit Vorinvestition aus Sol 1 bereits 8/10 — Sol 2 schließt mit 2 weiteren AP sofort ab) + Agrardom muss vorab gebaut sein (eigene Bau-AP-Kosten, separat von den 10 AP für den CC-Levelup)
- Ergebnis: Neue Ring-2-Tiles leuchten auf Koloniekarte auf. Zweiter Berater-Slot erscheint **leer und generisch** (kein fest zugeordneter Beratertyp mehr) — `hint_advisor_slot2` (Rang 6) feuert sofort, weist aber nicht mehr auf einen bestimmten Beratertyp hin.
- Feedback-Loop klar: Koloniekarte aktualisiert sich live (Ring-Expansion §4a)

**Aktion 6 — Agrardom bauen, dann zweiten Berater einstellen (`hint_agrardome`, `hint_advisor_slot2`)**

- Warum: Agrardom ist seit 2026-06-24 **Pflichtgebäude vor CC Lv2** (siehe §4, §13) — ohne Agrardom bleibt der CC2-Ausbau gesperrt. `hint_agrardome` feuert ab Sol 2 (`hint_no_agrardome_after_tick=1`), unabhängig von der späteren Pfadwahl.
- Nach CC Lv2 (Aktion 5) erscheint der zweite Berater-Slot **leer und generisch** — er ist nicht mehr an einen festen Beratertyp gebunden, sondern wird durch das zuerst gebaute Pfad-Gebäude bestimmt (Aktion 7). Der Spieler kann den Slot bereits jetzt mit irgendeinem Beratertyp besetzen, falls er das möchte — die Bindung entsteht erst beim Bau des jeweiligen Pfad-Gebäudes (siehe §13 "Reihenfolge-Auflösung").
- An diesem Punkt hat der Spieler die Kernsysteme berührt: Berater, Tile-Management, CC-Ausbau, Erkundung.

**Aktion 7 — Ab Sol 3: die Pfadwahl zwischen Sciencelab, Hangar und Cantina** (`hint_6`, `hint_analytik`, `hint_hangar_path`, ggf. `hint_build_priority`)

- Warum: Ab Sol 3 (current_tick=2) stehen Cantina (`hint_6`), Sciencelab (`hint_analytik`) und Hangar (`hint_hangar_path`) auf **derselben Tick-Schwelle** — das ist die erste bewusst gleichwertige strategische Wahl im natürlichen Pfad: Handel zuerst, Forschung zuerst oder Flotte/Exploration zuerst. Alle drei Gebäude sind ab CC Lv2 grundsätzlich baubar, aber das Bau-Gate (§13) erlaubt bei CC Lv2 nur eines der drei — die anderen beiden folgen bei CC Lv3 bzw. CC Lv4. Wer zuerst baut, bekommt **automatisch den passenden generischen Berater-Slot** (Sciencelab → Analytiker, Hangar → Raumfahrer, Cantina → Konsul) — keine separate Wahl mehr nötig, die Bau-Entscheidung *ist* die Berater-Entscheidung. Wenn zwei oder drei der drei Gebäude gleichzeitig bereit sind, weist `hint_build_priority` (Rang 11) auf die Wahl hin, ohne ein bestimmtes Gebäude vorzuschreiben.
- Sol 1/2 bleiben bewusst auf Bau (Construction-AP) und Erkundung (Navigation-AP) fokussiert — kein Hint drängt vor Sol 3 in Richtung Cantina, Sciencelab oder Hangar. Sol 3 ist damit der erste Punkt im natürlichen Pfad, an dem echte, vom Hint-System aktiv unterstützte Build-Order-Varianz beginnt — vorher (Aktion 1–6) ist die Reihenfolge praktisch linear vorgegeben, und das ist explizite Designentscheidung, nicht Versehen.
- **Kein permanenter Lockout:** Wer z. B. Cantina bei CC2 wählt, bekommt Sciencelab und Hangar bei CC3/CC4 trotzdem — nur später (siehe §13 "Gate-Logik"). Die Wahl bestimmt Reihenfolge und Zeitvorsprung, nicht endgültigen Zugang.

**Kein erzwungener Sequenz-Abschluss.** Der Spieler kann jederzeit von diesem Pfad abweichen. Die Hints verschwinden, wenn die jeweilige Bedingung nicht mehr zutrifft.

> ⚠️ BALANCE CONCERN: Baumeister-Kosten (300 Cr, Junior) müssen nach Playtest geprüft werden — 300 Cr von 3.000 Startguthaben ist 10%, sollte kein Problem sein. Einstellungskosten in `config/advisors.php` konfigurierbar.

> ⚠️ BALANCE CONCERN: Repair-Mechanik fehlt noch. Gebäude bei 80% sind 5–10 Sole lang funktionsfähig; sobald Verfall sie unter ~30% bringt, wirken sich Statusmalus-Effekte aus. Die Schwelle für "kritisch beschädigt" (aktuell: `80%`-Trigger in OnboardingTriggersService) soll nach erstem Playtest kalibriert werden.
>
> **Korrektur (2026-06-21):** Repair-Mechanik ist inzwischen implementiert (`hint_repair`, `hint_repair_urgent`, Reparieren-Button kostet 1 Bau-AP/Klick, `hint_repair_urgent_sp=3` von 20). Dieser Concern ist erledigt — verbleibt nur als Hinweis, dass die Schwelle `3/20` nach Playtest noch validiert werden sollte (siehe Designentscheidung zu Rang 2 in § 16.2).

---

#### Befund 1 — Leerlauf in den frühen Sols (AP- und Ressourcen-Sümpfe)

> ⚠️ BALANCE CONCERN (Analyse 2026-06-21): Auch mit `hint_cc_invest` und `hint_explore` bleiben mehrere AP-Pools in frühen Sols strukturell ungenutzt, weil dafür **kein Hint existiert**:
>
> - **Economy-AP (Konsul) und Strategy-AP (Stratege):** Es gibt bis heute keinen einzigen Hint, der auf eine Verwendung dieser beiden AP-Pools hinweist. Die Basis-6-AP/Sol verfallen ungenutzt, solange kein Konsul/Stratege eingestellt ist UND solange keiner der beiden eingestellt wird, weil kein Hint dazu motiviert (`hint_1` deckt nur den Baumeister ab; `hint_advisor_slot2` ist berater-typ-agnostisch und damit zwar eine Wahlmöglichkeit, aber keine Aufforderung speziell für Economy/Strategy). Solange Cantina (Handelsangebote) nicht gebaut ist, ist Economy-AP ohnehin praktisch wirkungslos — das ist ein struktureller Sumpf von Sol 1 bis frühestens Sol 6.
> - **Strategy-AP** hat im aktuellen Frühspiel **gar keine Verwendung** (keine Begegnungen/Eskorte-Befehle vor Hangar/Korvette, Phase 3). Der Pool läuft potenziell viele Sole leer, ohne dass das im GDD irgendwo benannt wird. Das ist kein Hint-Problem, sondern ein strukturelles Pacing-Problem: Strategy-AP sollte entweder früher nutzbar sein (z.B. für Erkundungs-Risikobewertung) oder es sollte explizit dokumentiert sein, dass dieser Pool bewusst erst ab Phase 3 (Hangar/Korvette) relevant wird, damit niemand fälschlich einen fehlenden Hint als Bug einstuft.
> - **Regolith-Überschuss:** Harvester Lv1 produziert kontinuierlich Regolith; bei Bauprojekten, die Bau-AP-limitiert sind (nicht Regolith-limitiert), kann sich Regolith schon vor Sol 5 anhäufen.
> - **Credits:** Nach Aktion 1 (Baumeister, 300 Cr) bleiben ~2.700 Cr liegen. Vor `hint_advisor_slot2` (frühestens Sol 2/3) gibt es keine weitere Credits-Senke außer ggf. weiteren Berater-Anwerbungen — das ist beabsichtigt (Credits sind die "freie" Ressource, kein Hint nötig), aber sollte explizit als Designentscheidung benannt werden statt implizit zu bleiben.
>
> **Update (2026-06-21) — Depot-Hint geprüft, nicht umsetzbar: blockiert durch fehlendes Resource-Cap-System.** Die ursprüngliche Empfehlung unten ging davon aus, dass Depot einen Lager-Cap für Regolith durchsetzt und überschüssiges Regolith am Cap "stillschweigend verfällt". Codeprüfung (`app/Services/ResourcesService.php`, `app/Console/Commands/GameTick.php`) zeigt: Es gibt aktuell **kein Resource-Storage-Cap-System** — `cap` im Code bezeichnet ausschließlich den *Supply*-Cap (Entity-Limit für Gebäude/Berater/Schiffe), nicht ein Lagerlimit für Regolith/Credits/Werkstoffe. Depot (`building_id=30`) hatte im Code **keine Funktion** — es war in `config/buildings.php` definiert, aber ohne jede Spielwirkung.
>
> **Erledigt (2026-06-22) — Depot ersatzlos entfernt, statt Cap-System nachzuziehen.** Pro/Contra-Evaluation (siehe § "Errichten" oben) ergab: Das eigentliche Spielproblem ist Ressourcenknappheit, nicht -überschuss; ein Lagerlimit-System hätte aktive Produktion bestraft statt belohnt und stand quer zum Roguelike-Designprinzip. Owner-Entscheidung: Depot-Gebäude (`building_id=30`) komplett aus dem Spiel gestrichen (`config/buildings.php`, `lang/de+en/buildings.php`, `lang/de+en/techtree.php`, `MasterDataSeeder`, `ColonySeedDemo`, Migration `2026_06_22_000001_remove_depot_building.php`). Damit ist der Regolith-Überschuss-Punkt unten kein offener Hint-Blocker mehr, sondern erledigt durch Entfernung der betroffenen Mechanik-Idee. Bei Bedarf kann Depot + Cap-System später erneut eingeführt werden.
>
> **Erledigt (2026-06-21):** Toter Config-Key `hint_no_engineer_ticks` aus `config/game.php → onboarding` entfernt (war in `OnboardingHintService::checkHint1()` nicht mehr referenziert). Code-Kommentar-Defaults in `OnboardingHintService.php` (die `config(..., $default)`-Fallbacks) auf die tatsächlich aktiven Config-Werte synchronisiert — betraf `hint_cc_upgrade_after_tick` (2→1), `hint_explore_until_tick` (2→0), `hint_no_knowledge_after_tick` (10→8), `hint_no_cantina_after_tick` (5→2), `hint_no_agrardome_after_tick` (6→1). Bestehende Test-Suite (`tests/Feature/Onboarding/OnboardingHintServiceTest.php`, 53 Tests) bestätigt grün — reine Fallback-Korrektur ohne Verhaltensänderung, da die Config-Werte ohnehin immer gesetzt sind.
>
> **Weiterhin offen:** Economy-/Strategy-AP-Leerlauf (siehe oben) — keine Code-Änderung, aber die GDD-Notiz selbst (dieser Block) macht das Pacing jetzt bewusst/dokumentiert, statt implizit zu bleiben.

#### Befund 2 — Erzwungene Berater-Reihenfolge (Status: entschieden, kein offener Punkt mehr)

> **Erledigt durch Designentscheidung (2026-06-21), siehe § 16.2 "Designentscheidung zu Rang 1".** Die folgende Analyse bleibt als historische Dokumentation stehen, ist aber **nicht mehr als offener Balance-Concern zu behandeln** — der Owner hat "Baumeister zuerst" als bewusste, dauerhafte Designentscheidung bestätigt. Die unten stehenden Empfehlungen 1 und 4 (Rang 1 generalisieren bzw. zu einer Wahlgruppe umbauen) werden **nicht** umgesetzt. Empfehlung 3 war bereits zutreffend (kein Code-Änderungsbedarf). Die ursprüngliche Beobachtung selbst — dass die Hint-Priorisierung de facto eine nahegelegte Standardreihenfolge erzeugt — bleibt sachlich richtig, ändert aber nichts an der Design-Entscheidung: Eine *nahegelegte* Reihenfolge ist ausdrücklich erlaubt, solange sie nicht erzwungen wird (Hints bleiben dismissable, blockieren keine Aktion). Die echte Wahlfreiheit setzt laut Design bewusst erst **ab Sol 3** ein (Cantina vs. Analytik-Labor, siehe § 16.2) — Sol 1/2 sind als linearer Bau-/Erkundungs-Einstieg beabsichtigt, nicht als Wahlphase.
>
> Ursprüngliche Analyse (2026-06-21, unverändert zur Nachvollziehbarkeit erhalten):
>
> Rang 1 (`hint_1`, kein Baumeister) hat **keine Tick-Schwelle und keine Alternative** — er ist der einzige Hint, der einen bestimmten Berater-Typ (Engineer) namentlich verlangt, und er steht permanent an der höchsten Priorität, bis ein Baumeister eingestellt wird. In Kombination mit `hint_cc_invest`/`hint_explore` (die beide implizit voraussetzen, dass bereits ein Baumeister aktiv ist, weil sonst kaum Bau-AP für CC-Vorinvestition übrig bleibt) wird **"Baumeister zuerst" faktisch zur einzigen vom Hint-System unterstützten Eröffnung**. Ein Spieler, der z.B. zuerst einen Konsul (Handel) oder Analytiker (Forschung) einstellen möchte, bekommt:
> - Weiterhin `hint_1` als höchstrangigen, nicht wegklickbaren-bis-erledigt Hinweis (dismissable nur pro Hint-Typ, aber er kommt zurück solange kein Baumeister da ist und wird sofort wieder zum dringendsten Hint, wenn andere abgearbeitet sind)
> - Kein gleichwertiges Pendant `hint_no_scientist`/`hint_no_trader` o.ä. auf Rang 1 — die anderen vier Berater-Typen haben überhaupt keinen "fehlt noch"-Hint
> - Das Gefühl, "falsch" zu spielen, weil die Hint-Leiste beharrlich auf den Baumeister verweist, auch wenn der gewählte Build (z.B. früher Handel über Cantina) strukturell ebenso valide ist
>
> Das widerspricht dem in § 16.7 festgehaltenen Prinzip "Kein Pflicht-Reihenfolge" — de facto entsteht durch die Hint-Priorisierung trotzdem eine nahegelegte Standardreihenfolge (Baumeister → Harvester verlegen → CC-Vorinvestition/Erkunden → CC Lv2 → 2. Berater → Cantina/Agrardom/Analytik), die zwar nicht erzwungen, aber stark begünstigt ist, weil sie als einzige durchgängig durch Hints unterstützt wird.
>
> Ursprüngliche Empfehlungen (1, 2, 4 zurückgezogen — siehe oben; 3 weiterhin gültig als Bestandsbeschreibung):
> 1. ~~`hint_1` so erweitern, dass er nicht zwingend "Baumeister" verlangt~~ — verworfen, Baumeister-zuerst bleibt Designentscheidung.
> 2. ~~Alternativ: explizit als Designentscheidung dokumentieren~~ — umgesetzt, siehe § 16.2.
> 3. `hint_cc_invest`/`hint_explore` setzen den Engineer-Pfad nicht voraus, prüfen nur verbleibende Bau-AP bzw. Nav-AP — weiterhin korrekt, kein Änderungsbedarf.
> 4. ~~Ränge 1–8 (Sol 1–2) zu einer Wahlgruppe umbauen~~ — verworfen. Sol 1/2 bleiben linear (Bau + Erkundung); die Wahlgruppe wird stattdessen ab Sol 3 (Cantina vs. Analytik) eingeführt, siehe § 16.2.

---

#### Pfadwahl-Überarbeitung (2026-06-24) — Implementierungs-Checkliste

> Diese Checkliste fasst zusammen, was konkret geändert werden muss, damit Code und Config der hier dokumentierten Pfadwahl (§4 "Pfadwahl ab Sol 3", §13 "Slot-System: CC-Level als Gate, Pfadwahl ab Slot 2") entsprechen. Kein offener Designpunkt mehr — die Entscheidung ist getroffen; das Folgende ist Implementierungsauftrag an `game-developer`/`backend-coder`/`db-migration-agent`.

**1. Config-Änderungen:**

| Datei | Änderung |
|-------|----------|
| `config/buildings.php` | `hangar.cc_level_required` (oder äquivalentes Gate-Feld, falls ein solches Feld eingeführt wird — aktuell ist das CC-Gate nicht in `buildings.php` selbst kodiert, siehe Punkt 2) — Hangar von "CC Lv3" auf "CC Lv2" senken. `bioFacility` braucht ein neues Flag, das es als CC2-Pflichtvoraussetzung markiert (z. B. `'cc2_prerequisite' => true`), falls der CC-Levelup-Check generisch über Config laufen soll statt hartcodiert. |
| `config/advisors.php` | Keine Strukturänderung nötig — `ap_type`/`credits` bleiben pro Typ unverändert. Ggf. Kommentar ergänzen, dass `scientist`/`pilot`/`trader` jetzt über die generischen Pfad-Slots 2–4 gebunden werden, nicht mehr über feste CC-Level. |
| `config/game.php → onboarding` | Neuer Key `hint_no_hangar_after_tick => 2` ergänzen (siehe §16.7-Codeblock oben). |

**2. PHP-Dateien:**

| Datei | Änderung |
|-------|----------|
| `app/Http/Controllers/Techtree/AdvisorController.php` | `SLOT_ORDER`-Konstante ersetzen durch `FIXED_SLOTS` (Position 1 → engineer, Position 5 → strategist) + `PATH_BUILDINGS`-Mapping (building_id → advisor key, siehe §13-Pseudocode). `buildSlots()` umbauen: Slots 2–4 nicht mehr statisch aus einem Array, sondern dynamisch aus der Bau-Reihenfolge der drei Pfad-Gebäude auf der Kolonie ermittelt (siehe Punkt 3, Migration). Neuer UI-Zustand für "Slot wartet auf Pfad-Gebäude-Bau" nötig (unterscheidet sich vom bisherigen `locked`-Zustand, der nur CC-Level prüft). |
| `app/Services/Techtree/PersonellService.php` | `hire()` braucht **keine** Strukturänderung — die Methode ist bereits typ-agnostisch (CC-Level bestimmt nur die Slot-*Anzahl*, nicht den Typ). Zu prüfen: ob `hire()` zusätzlich validieren muss, dass der angefragte `personell_id` tatsächlich zu einem der drei bereits gebauten Pfad-Gebäude (oder den fixen Slots 1/5) gehört — aktuell gibt es keine Typ-Bindung in der Hire-Logik selbst, das wäre eine neue Geschäftsregel: Slot 2 darf nicht mit einem Typ besetzt werden, dessen Pfad-Gebäude noch nicht gebaut ist. |
| `app/Services/ColonyService.php` (oder wo `placeBuilding()` lebt) | Neues Bau-Gate für die drei Pfad-Gebäude (Sciencelab/Hangar/Cantina, building_id 31/44/52): `Anzahl bereits platzierter Pfad-Gebäude < CC-Level − 1` muss vor der Platzierung geprüft werden. Fehlerfall (analog zu bestehenden Gate-Fehlern wie `slot_full`) z. B. `'path_gate_locked'`. |
| CC-Levelup-Endpoint (vermutlich in `ColonyService` oder `ColonyController`) | Neue Voraussetzung für CC Lv1→Lv2: Agrardom (`building_id=41`) muss ≥ Lv1 sein. Fehlerfall z. B. `'agrardome_required'`. |
| `app/Services/OnboardingHintService.php` | Siehe Punkt 4 unten — eigener Abschnitt, da umfangreich. |

**3. Migration (DB-Schema):**

`colony_buildings` hat aktuell **keine** Zeitstempel-/Reihenfolge-Spalte (siehe `0001_01_01_000014_create_colony_buildings_table.php`) — die Bau-Reihenfolge der drei Pfad-Gebäude kann nicht rekonstruiert werden, ohne eine neue Spalte einzuführen. Empfehlung: neue nullable Spalte `placed_at_tick` (Analogie zu `pending_until_tick`, siehe `2026_06_11_200000_add_pending_until_tick_to_colony_buildings.php`), gesetzt beim ersten `placeBuilding()`-Aufruf für dieses `colony_id + building_id`-Paar. Slot-Zuordnung in `buildSlots()` sortiert dann die drei Pfad-Gebäude nach `placed_at_tick ASC`, Tie-Break bei Gleichstand nach `building_id ASC` (siehe §13 "Reihenfolge-Auflösung").

> ⚠️ Diese Migration ist die einzige tatsächliche Schema-Änderung dieser gesamten Design-Überarbeitung — alles andere ist Config + Service-Logik.

**4. `OnboardingHintService.php` — detaillierter Rework-Bedarf:**

- `allChoiceBuildingsPlaced()` (aktuell: Cantina + Agrardom + Analytik) → umstellen auf Sciencelab + Hangar + Cantina (Agrardom raus, weil jetzt Pflichtgebäude, kein Wahlgruppen-Mitglied mehr).
- `checkHintBuildPriority()` → dieselbe Korrektur (Agrardom raus aus der `$eligible`-Zählung, Hangar rein).
- Neue Methode `checkHintHangarPath()` + `hangarPrereqsMet()` — Analogie zu `checkHintAnalytik()`/`analytikPrereqsMet()`, prüft CC ≥ Lv2 + Bau-Gate frei + nicht gebaut + bezahlbar.
- `cantinaPrereqsMet()`, `analytikPrereqsMet()`, neue `hangarPrereqsMet()` müssen alle zusätzlich das Bau-Gate prüfen (`Anzahl gebauter Pfad-Gebäude < CC-Level − 1`), nicht nur CC-Level — aktuell prüfen sie nur CC-Level, was nach der Überarbeitung falsch-positive Hints erzeugen würde (Hint zeigt auf ein Gebäude, das der Server wegen Bau-Gate ablehnt).
- `checkHintAgrardome()`/`agrardomePrereqsMet()` bleiben strukturell gleich (Harvester ≥ Lv1, Sol-Schwelle), aber der Hinweistext (lang/de) muss von Empfehlung zu Pflicht-Warnung geändert werden — Aufgabe für `content-writer`, nicht `game-developer`.
- Neuer Hint-Zustand für "Bau-Gate aktuell gesperrt, bei höherem CC-Level verfügbar" — kein Aktions-Link, reine Information (siehe Hinweis in der Hint-Tabelle oben). Muss definiert werden, ob dieser Zustand überhaupt einen eigenen Hint-Rang bekommt oder nur eine Tooltip-Information auf der gedimmten Techtree-Kachel ist (letzteres vermutlich konsistenter mit §16.4 "Zustandsbasierte Kachel-Sortierung" — kein neuer Hint-Rang nötig, nur ein Tooltip-Text "Verfügbar ab CC Lv3").
- `checkHint3()` (CC-Levelup-Hinweis, Rang 5) muss den Agrardom-Pflicht-Check einbauen: Wenn CC < Lv2 UND Agrardom < Lv1, darf der Hinweistext nicht "CC ausbauen" lauten (der Button ist ja gesperrt), sondern muss auf Agrardom verweisen — sonst zeigt das Hint-System auf eine Aktion, die der Server ablehnt.

**5. Lang-Dateien (`content-writer`):**

- `lang/de/colony.php` (oder wo `onboarding_hint_*`-Keys liegen): Neuer Key für `hint_hangar_path`, Anpassung `hint_agrardome`-Text (Pflicht statt Empfehlung), Anpassung `hint_advisor_slot2`-Text (kein fester Beratertyp mehr nennbar).
- `lang/de/advisors.php`: Ggf. Anpassung der Slot-Beschreibungstexte im Berater-Screen, falls dort bisher "Slot 2 — Analytiker" o. ä. fest beschriftet war (zu prüfen mit `ui-specialist`).

**6. Tests (`qa-tester`):**

- Bestehende `tests/Feature/Onboarding/OnboardingHintServiceTest.php` (53 Tests, Stand 2026-06-21) muss für alle Tests angepasst werden, die `bioFacility`/Cantina/Analytik als gleichrangige Wahlgruppe annehmen.
- Neue Tests: Bau-Gate-Durchsetzung (2. Pfad-Gebäude bei CC2 ablehnen, bei CC3 erlauben), Agrardom-als-CC2-Voraussetzung, generische Slot-Zuordnung (Slot 2 = Typ des zuerst gebauten Pfad-Gebäudes), Tie-Break bei Gleichstand.

---

### § 16.6 — Inline-Erklärungen statt Handbuch

Bestimmte Konzepte sind für neue Spieler nicht intuitiv. Statt ein Handbuch anzubieten, gibt es **kontextsensitive Inline-Erklärungen** — kurze Einzeiler die genau dann erscheinen, wenn das Konzept zum ersten Mal relevant wird.

**Trigger-Punkte (einmalig pro Run, nicht wiederholend):**

| Konzept | Trigger | Anzeigeform |
|---------|---------|-------------|
| Decay (Verfall) | Erstes Gebäude fällt auf < 80% Status-Points | INNN-Ereignis: "Ihre [Gebäudename] zeigt erste Verfallserscheinungen. Reparatur-AP verlangsamen den Prozess." |
| Supply-Cap erreicht | `freies_supply` sinkt auf 0 | Inline-Banner (gelb) im Ressourcen-Header: "Supply-Cap erreicht — kein neues Schiff oder Berater baubar." |
| Vertrauen sinkt erstmals unter 0 | `vertrauen` wird negativ | INNN-Ereignis (Absender: Kolonist): "Die Stimmung in der Kolonie ist angespannt." |
| Erstes AP-Limit | Spieler versucht Aktion aber AP = 0 | Tooltip am Button: "Keine [Typ]-AP mehr heute. Berater erhöhen den täglichen Vorrat." |
| Harvester-Verlagerung | Erster Klick auf "Verlegen"-Aktion | Tooltip: "Harvester verlegen kostet 1 Bau-AP pro Hex Distanz — er kommt nächsten Sol an und produziert unterwegs nichts." |

**Format:** INNN-Ereignisse für narrative Konzepte (Verfall, Vertrauen), Inline-Banner für kritische Systemgrenzen (Supply-Cap), Tooltips für Aktions-Mechaniken. Kein Modal, kein Overlay.

**Technisch:** Alle fünf Trigger sind einmalige `innn_events`-Einträge mit einem Flag `is_onboarding_hint = true` (oder einem separaten `event_type`-Präfix `onboarding_*`). Sie werden beim Erzeugen markiert und nach dem Lesen nicht mehr wiederholt.

> **Designentscheidung:** INNN ist der natürliche Kanal für alle narrativen Erklärungen — der Spieler lernt früh, dass INNN wichtige Informationen liefert. Onboarding-Hinweise über denselben Kanal zu liefern stärkt diese Gewohnheit statt eine neue UI-Schicht einzuführen.

---

### § 16.7 — Was Onboarding bewusst nicht leistet

Explizit ausgeschlossen — diese Maßnahmen verletzen die Designprinzipien und werden nicht implementiert:

| Ausgeschlossen | Begründung |
|----------------|-----------|
| Pflicht-Reihenfolge (Story-Modus, "Schritt 1 von 5") | Macht den Roguelike-Start zur Lehrveranstaltung; verhindert eigene Erkundung |
| Gesperrte Screens bis Tutorial fertig | Bevormundet erfahrene Spieler; zerstört die "echter Spielstart von Tag 1"-Eigenschaft |
| Erklärungsmodal beim Laden des Spiels | Pop-up-Spam; wird weggeklickt; Informationen zu früh, nicht kontextsensitiv |
| Animierter Cursor-Zeiger ("Klick hier!") | Infantilisiert; passt nicht zum Direktor-Ton von Nouron |
| Permanente Sidebar-Erklärung aller Konzepte | Platzverschwendung; nach dem ersten Tag nicht mehr sinnvoll |
| Separater Sandbox-/Tutorial-Run | Hoher Implementierungsaufwand; Spieler wollen spielen, nicht üben |

---

### Technische Anforderungen (Zusammenfassung)

| Maßnahme | Implementierungsaufwand | Abhängigkeiten |
|----------|------------------------|----------------|
| Nexus-Briefing (INNN-Nachricht beim Run-Start) | Klein — `InnnService::createEvent()` erweitern | Run-Erzeugung muss Hook haben |
| Hint-System (Zustandscheck + Leiste unter Ressourcen) | Mittel — Alpine.js Komponente, 5 Bedingungsregeln | Ressourcenleiste-Layout, User-Preferences |
| Pulse-Indikator (CSS-Animation auf Kacheln/Tiles) | Klein — CSS-Klasse `ring-pulse` + Condition-Flag im Blade | Hint-System (welches Element pulsiert) |
| Kachel-Sortierung im Techtree | Mittel — Techtree-Controller liefert Gruppierungsflag | Techtree-Screen-Refactoring |
| Inline-Erklärungen (5 Trigger-Punkte) | Klein pro Trigger — INNN-Event + Flag | Run-State (Trigger darf nur einmal feuern) |
| User-Preference `onboarding_hints` | Klein — User-Settings-Tabelle oder Cookie | User-Settings-Screen |

**Konfiguration:** `config/game.php → onboarding` (Stand 2026-06-21, vollständig — die vorherige Fassung dieser Liste war veraltet und nannte teils nicht mehr existierende Keys):

```php
'onboarding' => [
    'hint_repair_urgent_sp'           => 3,   // Rang 2: SP-Schwelle (von max. 20) für Leveldown-Warnung
    'hint_supply_cap_threshold'       => 10,  // (aktuell ungenutzt im Hint-Ranking selbst — Supply-Cap-Banner läuft über §16.6, nicht §16.2)
    'hint_no_engineer_ticks'          => 3,   // Rang 1 referenziert dies nicht direkt mehr (checkHint1 prüft nur Advisor-Existenz) — TODO: toter Config-Wert?
    'hint_no_knowledge_after_tick'    => 8,   // Rang 9 (hint_4): Sol 9
    'hint_trust_threshold'            => -20, // Rang 10 (hint_5)
    'hint_trust_min_ticks'            => 5,   // Rang 10 (hint_5): Sol 6
    'hint_no_cantina_after_tick'      => 2,   // Rang 12 (hint_6) — Sol 3, gleichrangig mit hint_no_analytik_after_tick + hint_no_hangar_after_tick (Pfadwahl, siehe §13)
    'hint_no_agrardome_after_tick'    => 1,   // Rang 13 (hint_agrardome) — Sol 2; jetzt Pflicht-Gate-Hinweis, nicht mehr Wahlgruppen-Mitglied (siehe §4, §13)
    'hint_no_analytik_after_tick'     => 2,   // Rang 14 (hint_analytik) — Sol 3, gleichrangig mit hint_no_cantina_after_tick + hint_no_hangar_after_tick
    'hint_no_hangar_after_tick'       => 2,   // Rang 14b (hint_hangar_path) — NEU (2026-06-24), Sol 3, gleichrangig mit den beiden obigen — drittes Pfadwahl-Mitglied
    'hint_cc_upgrade_after_tick'      => 1,   // Rang 5 (hint_3): Sol 2
    'hint_explore_until_tick'         => 0,   // Rang 8 (hint_explore): nur Sol 1
    'hint_explore_max_explored_tiles' => 6,   // Rang 8 (hint_explore): Throttle
],
```

> ⚠️ BALANCE CONCERN / IMPLEMENTIERUNGSAUFTRAG (2026-06-24): `hint_no_hangar_after_tick` ist ein neuer Config-Key, noch nicht in `config/game.php` angelegt — Teil der Implementierungs-Checkliste am Ende dieses Abschnitts.
>
> ⚠️ BALANCE CONCERN / DOKU-DRIFT (2026-06-21, teilweise behoben): Die tatsächlichen Werte in `config/game.php` weichen weiterhin von den Code-Defaults in `OnboardingHintService.php` ab (z.B. `hint_no_agrardome_after_tick`: Config=1, Code-Default=6; `hint_no_analytik_after_tick`: Config=2, Code-Default=8). Das ist beabsichtigt (Config gewinnt immer; siehe `canAffordBuildingPlacement()`, die ohnehin die reale Bezahlbarkeit prüft) — die Code-Kommentare mit den höheren Default-Werten sollten dennoch zur Vermeidung von Verwirrung beim nächsten Code-Review aktualisiert werden (Aufgabe für `game-developer`, nicht `game-designer`). **Update (2026-06-21):** `hint_no_cantina_after_tick` wurde von `0` auf `2` korrigiert (war zuvor Sol 1, also zwei Sole vor `hint_no_analytik_after_tick`) — beide stehen jetzt auf identisch `2` (Sol 3), wie es die Designentscheidung "Pfadwahl" (§ 16.2, §13) verlangt. Diese eine Diskrepanz war kein reines Doku-Drift-Problem, sondern eine tatsächliche Balance-Lücke (Cantina hatte einen unbeabsichtigten Sol-Vorsprung) und wurde behoben.
>
> `hint_no_engineer_ticks` scheint im aktuellen `OnboardingHintService::checkHint1()` gar nicht mehr gelesen zu werden (die Methode prüft nur, ob ein Advisor-Datensatz existiert, ohne Tick-Schwelle). Falls korrekt: toter Config-Eintrag, sollte entfernt oder die Doku-Kommentare im Config korrigiert werden — zu klären mit `game-developer`/`backend-coder`.

> **TODO (Implementierung):** User-Preferences-Tabelle benötigt Spalte `onboarding_hints BOOLEAN DEFAULT 1`. Alternativ: Session-Storage für den ersten Run, persistente DB-Einstellung ab zweitem Run.

> **TODO (Design):** Nexus-Briefing-Text ist bisher nur als Entwurf definiert. Finale Formulierung mit dem content-writer abstimmen (Ton: karg, lakonisch, Frontier-Atmosphäre — kein Tutorial-Handbuch-Ton).

> **TODO (Design):** Reihenfolge der ersten freigeschalteten Kenntnis-Slots im Roguelike-Zufallssystem (§ 10) beeinflusst Onboarding — Hint Rang 4 muss prüfen ob das Analytik-Labor überhaupt Teil des laufenden Runs ist. Falls nicht: Hint anpassen auf "erste verfügbare Kenntnis".

---

## 17. Progressive Discovery System

### Designprinzip

Nouron-Runs sind kurz und wiederholbar — aber kein Run soll sich identisch anfühlen. Das Progressive Discovery System ist das Bindeglied zwischen Roguelike-Variabilität (§15) und dem Spielerlebnis: **Der Spieler entdeckt das Spiel im Verlauf des Runs, nicht davor.**

Das Gegenprinzip wäre das klassische "Upfront-Reveal": Alle Objectives erscheinen sofort, alle Almanach-Artikel sind von Anfang an lesbar, alle Informationen liegen transparent auf dem Tisch. Das ist korrekt und spielerfreundlich — aber es beraubt den Run seiner Dynamik. Ein Run bei dem der Spieler von Sol 1 an den gesamten Weg kennt, fühlt sich wie eine Checkliste an, nicht wie eine Expedition.

**Das Ziel dieser Mechaniken ist deshalb nicht Informationsentzug, sondern zeitliche Dosierung**: Informationen kommen genau dann, wenn sie relevant und erfahrbar sind — nicht als Vorlesung, sondern als Teil des Spielgeschehens.

Die drei Mechaniken dieses Abschnitts sind eng verwandt:

- **§17.1 — Objective Discovery:** Phase-2-Objectives werden durch Berater-Dialoge über mehrere Sole enthüllt, nicht sofort beim Phasenübergang angezeigt.
- **§17.2 — Advisor Dialogs:** Berater führen strukturierte Multi-Sol-Dialoge. Sie können AP kosten und liefern dafür Informationen, Aufträge oder Bonuseffekte.
- **§17.3 — Almanach Unlock:** Bestimmte Almanach-Artikel sind anfangs gesperrt. Freischaltung durch Run-Fortschritt. Einmaliger Wissensbonus beim ersten Lesen.

Diese drei Mechaniken ziehen sich als **roter Faden** durch den gesamten Spielablauf. Sie sind unabhängig voneinander implementierbar, aber konzeptionell aufeinander aufgebaut: Berater-Dialoge (§17.2) liefern die narrative Schicht für Objective Discovery (§17.1), und Almanach-Einträge (§17.3) dokumentieren, was der Spieler durch Dialoge und Run-Fortschritt gelernt hat.

> Das System schreibt keine feste Spielerfahrung vor. Spieler, die Objectives ignorieren oder Almanach-Artikel nie öffnen, werden nicht bestraft. Discovery ist ein Angebot, keine Pflicht.

---

### 17.1 Objective Discovery — Berater enthüllen Phase-2-Aufgaben

#### Problem

Beim aktuellen Run-Design (§15) erscheinen alle drei Phase-2-Objectives sofort beim Übergang von Phase 1 zu Phase 2. Das ist klar und ehrlich — aber es tötet den Entdeckungsmoment. Der Spieler sieht sofort das vollständige Ziel-Set und plant rational. Die Transition von Phase 1 zu Phase 2 fühlt sich wie ein Menü-Wechsel an, nicht wie ein narrativer Wendepunkt.

#### Lösung: Gestaffelte Enthüllung

Phase-2-Objectives werden nicht sofort beim Phasenübergang angezeigt. Stattdessen enthüllen Berater die Objectives über die ersten **3–5 Sole** von Phase 2 durch individuelle Dialoge.

**Ablauf (Referenzbeispiel, 3 Objectives):**

| Sol nach Phase-2-Start | Ereignis |
|------------------------|---------|
| Sol +0 (Übergang) | INNN-Ereignis von Nexus: "Phase 1 abgeschlossen. Neue Direktive folgt." — keine Objectives sichtbar |
| Sol +1 | Berater-Dialog (z.B. Baumeister): Objective 1 wird enthüllt. AP-Kosten entstehen, wenn der Spieler den Dialog aktiv annimmt. |
| Sol +4–5 | Zweiter Berater (z.B. Analytiker) enthüllt Objective 2 |
| Sol +8–12 | Dritter Berater enthüllt Objective 3 oder Objective wird durch ein Run-Ereignis ausgelöst |
| Sol +15 | Sol-Threshold-Fallback: alle noch nicht enthüllten Objectives erscheinen automatisch |

Der Spieler kann bereits ab Sol +1 mit den Arbeiten beginnen — er muss das vollständige Objective-Set nicht kennen um sinnvoll zu handeln. Das erzeugt echte Spannung: "Was kommt als nächstes?"

#### Enthüllungs-Trigger

Objectives können durch drei verschiedene Trigger enthüllt werden:

| Trigger-Typ | Beschreibung | Beispiel |
|-------------|-------------|---------|
| `advisor_dialog` | Ein Berater-Dialog (§17.2) löst die Enthüllung aus | Baumeister berichtet nach erstem Sol von einem Nexus-Auftrag |
| `sol_threshold` | Fester Sol-Zeitpunkt nach Phase-2-Start | Objective erscheint spätestens ab Sol +15 (Fallback wenn kein Dialog ausgelöst wurde) |
| `run_event` | Ein bestimmtes Run-Ereignis löst das Objective aus | Korvette erkundet ein neues Tile → Objective "Expedition abschließen" wird sichtbar |

**Fallback-Regel:** Jedes Objective hat einen `reveal_by_sol`-Wert (Anzahl Sole nach Phase-2-Start). Wurde das Objective bis dahin nicht durch Dialog oder Event enthüllt, erscheint es automatisch — stilles INNN-Ereignis mit Absender "Nexus Command". Kein Objective bleibt für immer versteckt.

**UI-Darstellung:** Im Objectives-Screen gibt es einen dritten Zustand neben "in Bearbeitung" und "abgeschlossen": **"Unbekannt"** (Fragezeichen-Icon). Dieser Zustand zeigt, dass ein weiteres Objective existiert, aber noch nicht enthüllt wurde. So weiß der Spieler, dass er auf etwas wartet — ohne zu wissen was.

> ⚠️ BALANCE CONCERN: Der Fallback-Mechanismus ist wichtig. Wenn ein Spieler keinen Analytiker-Berater hat und das zweite Objective nur durch den Analytiker enthüllbar ist, muss der Sol-Threshold-Fallback greifen. Die Discovery-Mechanik darf keinen Progression-Lock erzeugen.

#### Roguelike-Variabilität

Pro Run sind nicht nur die Objectives selbst variabel (§15), sondern auch die Enthüllungs-Reihenfolge. Ein Run mit `task_research_lead` als Objective 1 kann dieses durch den Analytiker enthüllen — derselbe Run ohne Analytiker-Berater würde es durch Nexus (Sol-Threshold) enthüllen. Das erzeugt unterschiedliche narrative Erfahrungen ohne unterschiedlichen Spielinhalt zu erzwingen.

---

### 17.2 Advisor Dialogs — Multi-Sol-Dialoge

#### Konzept

Berater sind bisher passive AP-Produzenten mit Rang-Progression. Dialoge machen sie zu **aktiven Akteuren**: Sie sprechen den Direktoren gezielt an, bieten Informationen an, formulieren Bitten und können Aktionen anstoßen.

Ein Advisor Dialog ist ein strukturierter Informationsaustausch über **1–3 Sole**. Er erscheint als INNN-Ereignis mit Absender `[Berater-Typ]:[Berater-Name]`. Der Spieler kann den Dialog annehmen, verzögern oder ablehnen. Annehmen kann AP kosten.

#### Dialog-Struktur

Jeder Dialog hat folgende Felder:

| Feld | Beschreibung | Beispiel |
|------|-------------|---------|
| `dialog_key` | Eindeutiger Schlüssel | `engineer_phase2_objective_reveal` |
| `advisor_type` | Welcher Berater-Typ | `construction` |
| `trigger` | Was löst den Dialog aus | `phase2_start`, `run_event`, `sol_threshold` |
| `duration_ticks` | Wie viele Sole dauert der Dialog | 1–3 |
| `ap_cost_type` | AP-Typ der Entscheidungskosten | `construction`, oder `null` |
| `ap_cost_amount` | Wie viele AP kostet Annehmen | 3–8 |
| `reward_type` | Was liefert der Dialog | `objective_reveal`, `knowledge_hint`, `resource_bonus`, `none` |
| `is_skippable` | Kann der Spieler ablehnen | `true` / `false` |

**AP als Preis für Information:** Wenn ein Berater-Dialog AP kostet, sind das keine "Strafpunkte" — es ist der Preis für etwas Wertvolles. Ein Baumeister, der einen halben Sol damit verbringt, Nexus-Direktiven zu entziffern und dem Direktor zu erklären, steht in dieser Zeit nicht für Bauprojekte bereit. Das ist die saubere Nouron-Mechanik: **Opportunitätskosten statt Verbot** (§1.1).

> Designprinzip: Dialog-AP-Kosten sollten spürbar, aber nicht prohibitiv sein. Richtwert: 3–8 AP (entspricht 25–65% eines Junior-Berater-Tagespools). Ein Junior-Baumeister mit 10 AP/Sol kann sich den Dialog "leisten" — aber nicht an demselben Sol gleichzeitig ein Gebäude ausbauen. Das ist die Entscheidung.

#### Dialog-Verlauf

**Sol 1 — Ankündigung:**
INNN-Ereignis erscheint mit Absender-Name des Beraters. Kurze Eröffnung: "Direktor, ich habe etwas aufgegriffen das Ihre Aufmerksamkeit verdient. Haben Sie heute Zeit?"

Zwei Response-Optionen:
- "Jetzt anhören" — kostet sofort die definierten AP (oder 0 wenn `ap_cost_amount = null`)
- "Morgen" — Dialog verschiebt sich um 1 Sol (max. 2-mal verschiebbar, dann wird er automatisch aufgelöst)

**Sol 2 (oder selber Sol bei Sofortannahme) — Hauptdialog:**
Der eigentliche Inhalt erscheint: Objective-Enthüllung, Wissenshinweis, Ressourcen-Info oder narratives Lore-Fragment. Das INNN-Ereignis ist ausführlicher als ein Standard-Ereignis — max. 3 kurze Absätze.

**Sol 3 (optional) — Folgedialog oder Abschluss:**
Berater-Dialoge mit `duration_ticks = 3` haben einen Abschluss-Sol mit einer optionalen Reaktion des Spielers. Kein weiterer AP-Verbrauch.

#### Dialog-Typen (Phase 4 Katalog, Auswahl)

| Dialog-Key | Berater | Trigger | Kosten | Ergebnis |
|------------|---------|---------|--------|---------|
| `engineer_phase2_objective_reveal` | Baumeister | `phase2_start` + 1 Sol | 4 construction-AP | Objective 1 enthüllt |
| `scientist_anomaly_hint` | Analytiker | Anomalie-Tile erkundet | 5 research-AP | Event-Tile Tiefenscan-Kosten um 1 AP reduziert |
| `pilot_patrol_report` | Raumfahrer | Korvette hat 3+ Orders ausgeführt | 0 AP | Nächster NPC-Encounter-Stärkewert vorab bekannt |
| `trader_nexus_deal` | Konsul | Sol 20–30 (wenn `task_trade_volume` aktiv) | 6 economy-AP | Nexus-Handelsschiff erscheint 1 Sol früher als normal |
| `strategist_threat_assessment` | Stratege | Piratensonde auf der Karte | 0 AP | Genaue Position der Sonde auf Systemkarte enthüllt |

> ⚠️ BALANCE CONCERN: Dialoge mit 0 AP-Kosten dürfen keinen spielentscheidenden Vorteil bieten. "Genaue Position der Piratensonde" ist ein Komfort-Bonus, keine Entscheidungsverschiebung — das ist akzeptabel. Dialoge mit 5+ AP-Kosten müssen einen spürbaren Gegenwert liefern, sonst werden sie ignoriert.

#### Ablehnung und Verfall

Lehnt der Spieler einen Dialog explizit ab (`is_skippable = true`, Spieler wählt "Ablehnen"), wird er als `declined` abgeschlossen. Schiebt der Spieler ihn 2× auf ("Morgen") oder läuft `dialog_expire_after_ticks` ab, gilt er als `expired`. In beiden Fällen keine Konsequenz. Kein Dialog ist existenziell für den Run. Die Enthüllungen die über Dialoge transportiert werden, kommen im Zweifelsfall über den Sol-Threshold-Fallback (§17.1).

**Nicht verfügbare Berater:** Wenn der Berater-Typ des Dialogs gerade keinen aktiven Berater hat (Slot leer, Burnout, Außenmission), ist der Dialog nicht verfügbar. Er erscheint nicht im INNN-Feed. Der Sol-Threshold-Fallback greift stattdessen.

---

### 17.3 Almanach — Unlock & Wissensbonus

#### Konzept

Der Almanach (bisher als "Ingame-Nachschlagewerk" in der ROADMAP erwähnt) ist die In-Game-Dokumentation von Spielmechaniken, Gebäuden, Forschungen und Lore. Er ist ein wichtiges Onboarding-Werkzeug — aber er ist mehr als ein Handbuch: **Almanach-Artikel können einmalige Spielboni tragen**.

Das Lesen eines Artikels wird zu einer echten Spielentscheidung wenn es einen Bonus gibt. Statt "ich lese das irgendwann wenn ich Fragen habe" wird es zu: "Ich sollte jetzt den Geology-Artikel lesen bevor ich den Harvester verlagere — der Bonus hilft mir."

#### Freischaltsystem

Almanach-Artikel sind in drei Kategorien aufgeteilt:

| Kategorie | Freischalt-Bedingung | Beispiel-Artikel |
|-----------|---------------------|-----------------|
| **Immer verfügbar** | Kein Gate | Grundlegende Spielmechaniken (Supply, AP, Sol-Zyklus) |
| **Fortschrittsabhängig** | Sol-Zahl, Gebäude-Level oder Objective-Fortschritt | "Verfall & Entropie" freigeschaltet wenn erstes Gebäude < 80% SP |
| **Entdeckungsabhängig** | Explizites Ereignis (Berater-Dialog, Event-Tile) | "Piratensonden — Verhalten und Bekämpfung" freigeschaltet nach erstem Piratensonden-Encounter |

**Freischalt-Trigger-Typen:**

| Trigger-Key | Bedingung |
|-------------|-----------|
| `sol_reached:{n}` | Run hat Sol n erreicht |
| `building_built:{key}` | Gebäude wurde erstmals gebaut |
| `objective_revealed:{key}` | Objective wurde enthüllt (§17.1) |
| `encounter_event:{type}` | Bestimmter Encounter-Typ ist aufgetreten |
| `advisor_dialog:{key}` | Berater-Dialog wurde abgeschlossen |
| `always` | Immer verfügbar |

**UI-Darstellung:** Gesperrte Artikel werden im Almanach-Index als grauer Eintrag mit dem Hinweis "Wird nach [Bedingung] freigeschaltet" gelistet. Der Spieler sieht, dass es dort etwas gibt — aber nicht den Inhalt. Das erzeugt Neugier ohne Frustration.

Neu freigeschaltete Artikel werden mit einem Badge "Neu" markiert und erscheinen in einer kompakten INNN-Meldung: "Almanach — neuer Artikel freigeschaltet: [Titel]".

#### Wissensbonus beim Lesen

Wenn ein Spieler einen Artikel mit einem Wissensbonus öffnet und vollständig liest (Scroll-Threshold oder expliziter "Gelesen"-Button), erhält er einmalig pro Run einen kleinen Vorteil. Der Bonus ist thematisch mit dem Artikel-Inhalt verknüpft.

**Bonus-Typen:**

| Bonus-Typ | Beschreibung | Beispiel |
|-----------|-------------|---------|
| `ap_bonus` | Sofortiger einmaliger AP-Schub eines Typs | +6 construction-AP nach Lesen von "Bautechnik — fortgeschrittene Methoden" |
| `resource_bonus` | Sofortiger einmaliger Ressourcen-Zuschuss | +30 Regolith nach Lesen von "Geologie — Abbauoptimierung" |
| `knowledge_hint` | Erhöht AP-Effizienz für eine bestimmte Kenntnis für N Sole | -1 AP pro research-Level für "Agronomie" für 5 Sole |
| `encounter_prep` | Reduktion des Stärke-Anforderungswerts für den nächsten passenden Encounter | -1 Stärke-Cap für nächste Piratensonden-Begegnung |
| `none` | Kein Spielbonus — reines Lore oder Nachschlagewerk | Hintergrundgeschichte des Planeten |

**Einmalig pro Run:** Der Bonus wird nur beim ersten Lesen gutgeschrieben. Erneutes Öffnen des Artikels liefert keinen weiteren Bonus. Das Datum und der Erhalt des Bonus werden in `run_state` (oder einem neuen Feld `almanac_read_bonuses`) vermerkt.

**Lesbarkeits-Prinzip:** Artikel mit Bonus müssen kurz sein — max. 150–200 Wörter. Sie sind keine Romankapitel. Der Spieler soll nicht 10 Minuten lesen müssen um einen AP-Bonus zu bekommen. Das wäre kein Anreiz, sondern Arbeit.

> ⚠️ BALANCE CONCERN: AP-Boni über den Almanach dürfen die bestehende AP-Balance (§13) nicht aushebeln. Der Richtwert ist: ein Almanach-AP-Bonus entspricht dem Grundwert eines Tages (6 AP) oder dem Beitrag eines Junior-Beraters für 1 Sol (4 AP-Bonus). Höhere Boni sind nur für sehr späte oder sehr seltene Artikel akzeptabel. Die Boni summieren sich über einen Run: wenn alle freigeschalteten Artikel gelesen werden, sollte der Gesamteffekt spürbar, aber nicht spielverändernd sein.

> ⚠️ BALANCE CONCERN: Artikel mit `encounter_prep`-Bonus (Stärke-Reduktion) müssen sicherstellen, dass der Encounter dadurch nicht trivial wird. Richtwert: maximale Reduktion -1 Stärkepunkt, nicht mehr.

#### Almanach und Onboarding

Der Almanach ergänzt das Onboarding-System (§16) ohne es zu ersetzen. Das Onboarding zeigt dem Spieler was er jetzt tun soll; der Almanach erklärt warum Systeme so funktionieren wie sie funktionieren. Die Zielgruppen sind verschieden:

- **Neuer Spieler, erster Run:** Onboarding-Hints leiten, Almanach-Basics immer verfügbar
- **Spieler nach 3–5 Runs:** Onboarding deaktiviert, Almanach als Nachschlagewerk und Discovery-Tool

Almanach-Artikel die durch Berater-Dialoge (§17.2) freigeschaltet werden, erzeugen eine dritte Schicht: Der Dialog bringt den Spieler auf das Thema, der Artikel vertieft es. Das schafft einen kohärenten Informationsfluss ohne jeden Dialog zu einer Vorlesung zu machen.

---

### 17.4 Implementierungshinweise

Dieser Abschnitt beschreibt, was für Phase 4 konkret vorzubereiten ist. Es handelt sich um Design-Voraussetzungen, keine vollständige Implementierungsspezifikation.

#### Datenbank

**Neue Tabelle `advisor_dialogs`:**

```
advisor_dialogs
├── id                    ← PK
├── run_id                ← FK → runs
├── advisor_type          ← 'construction' | 'research' | 'navigation' | 'economy' | 'strategy'
├── dialog_key            ← Verweis auf Config-Definition, z.B. 'engineer_phase2_objective_reveal'
├── status                ← 'pending' | 'offered' | 'accepted' | 'declined' | 'expired'
├── offered_at_tick       ← Sol in dem der Dialog angeboten wurde
├── resolved_at_tick      ← Sol in dem der Dialog abgeschlossen oder verfallen ist
└── postponed_count       ← Anzahl "Morgen"-Antworten (max 2)
```

**Neue Spalten auf `run_objectives`:**

```
run_objectives
├── ...                   ← bestehende Felder (aus Phase 3i)
├── revealed_at_tick      ← nullable int: Sol in dem das Objective enthüllt wurde (null = noch nicht enthüllt)
└── reveal_trigger        ← nullable string: wie wurde es enthüllt ('advisor_dialog' | 'sol_threshold' | 'run_event')
```

**Neue Spalte auf `runs` oder erweitertes JSON-Feld:**

```
runs
├── ...                   ← bestehende Felder
└── almanac_read_bonuses  ← JSON: Liste von article_keys die gelesen + Bonus bereits gutgeschrieben wurden
```

**Neue Tabelle `almanac_articles`** (Stammdaten — wird per Migration/Seed befüllt, nicht pro Run):

```
almanac_articles
├── id                    ← PK
├── key                   ← eindeutiger Key, z.B. 'geology_extraction'
├── title                 ← Anzeigetitel (via lang/de)
├── category              ← 'mechanics' | 'buildings' | 'knowledge' | 'lore' | 'encounters'
├── unlock_trigger        ← JSON: Trigger-Definition, z.B. {"type": "building_built", "key": "harvester"}
├── bonus_type            ← nullable string: 'ap_bonus' | 'resource_bonus' | 'knowledge_hint' | 'encounter_prep' | null
├── bonus_value           ← nullable int/JSON: Wert des Bonus
└── bonus_ap_type         ← nullable string: AP-Typ wenn bonus_type = 'ap_bonus'
```

**Neue Tabelle `run_almanac_unlocks`** (welche Artikel sind in diesem Run freigeschaltet):

```
run_almanac_unlocks
├── id                    ← PK
├── run_id                ← FK → runs
├── article_key           ← FK → almanac_articles.key
└── unlocked_at_tick      ← Sol der Freischaltung
```

#### Config-Schlüssel

Neuer Block in `config/game.php → progressive_discovery`:

```php
'progressive_discovery' => [
    // Objective Discovery
    'objective_reveal_fallback_ticks' => 15,  // Sole nach Phase-2-Start bis Sol-Threshold-Fallback greift
    'objective_reveal_min_delay'      => 1,   // Minimale Sole zwischen zwei Objective-Enthüllungen

    // Advisor Dialogs
    'dialog_postpone_max'             => 2,   // Maximale Anzahl "Morgen"-Antworten
    'dialog_expire_after_ticks'       => 3,   // Dialog verfällt nach N Solen ohne Antwort

    // Almanach Bonus
    'almanac_bonus_ap_max_per_run'    => 30,  // Maximale kumulierte AP-Boni aus Almanach pro Run
    'almanac_bonus_resource_max_per_run' => 100, // Maximale kumulierte Ressourcen-Boni
],
```

Dialoge und Artikel-Definitionen kommen in eigene Config-Dateien:
- `config/advisor_dialogs.php` — alle Dialog-Definitionen (key, trigger, costs, reward)
- `config/almanac.php` — alle Artikel-Definitionen (key, category, unlock_trigger, bonus)

#### Tick-Integration

- **Advisor Dialogs:** Tick-Schritt 7 (Advisor Ticks) prüft für jeden aktiven Berater ob ein Dialog-Trigger erfüllt ist und legt ggf. eine neue `advisor_dialogs`-Zeile an. Bereits laufende Dialoge werden um `offered_at_tick + postponed_count` ausgewertet.
- **Objective Reveal:** Nach Tick-Schritt 7 prüft `RunProgressService` für jedes noch nicht enthüllte `run_objective` ob ein `reveal_trigger` ausgelöst wurde oder `sol_threshold` überschritten ist.
- **Almanach Unlock:** Nach Tick-Schritt 6 (Resource Generation) prüft ein neuer `AlmanachService::checkUnlocks()` für jeden definierten Trigger ob neue Artikel freigeschaltet werden.

#### Priorisierung für Phase 4

Die drei Teilmechaniken können unabhängig voneinander implementiert werden. Empfohlene Reihenfolge:

1. **Almanach-Grundstruktur** (Artikel-Tabelle, Freischalt-System, INNN-Benachrichtigung) — kleiner Aufwand, sichtbarer Effekt, keine Abhängigkeiten
2. **Objective Discovery via Sol-Threshold** (nur den Fallback implementieren, ohne Berater-Dialoge) — schafft sofort den Enthüllungseffekt mit minimalem Schema-Aufwand
3. **Advisor Dialogs** — aufwendiger, aber der narrativ reichhaltigste Teil. Setzt Almanach und Objective Discovery als Empfänger voraus.

Die vollständige Integration aller drei Teilmechaniken ist der Zielzustand. Jede Teilmechanik alleine bringt aber bereits Wert — es gibt keinen "alles oder nichts"-Implementierungspunkt.

---

## 18. Run-Ende & Fail-State

### Designprinzip

Jeder Run von Nouron hat ein klares, kommunizierbares Ende. Das Ende ist keine Überraschung — weder Sieg noch Niederlage trifft den Spieler unvorbereitet. Alle Konsequenzen haben Vorwarnstufen. Das Spielprinzip "Konsequenzen für Fehlentscheidungen" (§1) bedeutet nicht "unangekündigte Strafe", sondern "rechtzeitig spürbarer Druck".

**Kanonische Quelle:** `app/Services/RunProgressService.php` und `config/game.php → run`. Dieser GDD-Abschnitt dokumentiert die Design-Intention; Zahlen folgen der Config, nicht umgekehrt.

---

### 18.1 Siegbedingung

**Entscheidung: 2 von 3 Phase-2-Objectives abgeschlossen (Kombinations-Modell)**

Das Runziel ist von Anfang an Phase 2 kommunizierbar: "Schließen Sie 2 der folgenden 3 Aufgaben ab." Die Objectives erscheinen beim Phase-2-Übergang (mit gestaffelter Enthüllung via §17.1 ab Phase 4). Die Wahlfreiheit über welche zwei Objectives erfüllt werden, ist das zentrale Roguelike-Entscheidungsmoment eines Runs.

Warum kein Bau- oder Ressourcenmilestone (Optionen b/c) als Siegbedingung:
- Objectives variieren je Run → variabler Spielverlauf → Roguelike-Charakter
- "2 von 3" gibt echte Wahlfreiheit ohne Optimalpfad
- Die Bedingung ist von Beginn der Phase 2 an sichtbar — kein verstecktes Ziel

**Win-Trigger (implementierbar in `RunProgressService`):**

Nach jedem `updateObjectiveProgress()`-Aufruf im Tick-Zyklus (Phase 5, §2) wird geprüft:
```
completed = run.objectives().whereNotNull('completed_at').count()
if run.phase == 2 and completed >= 2:
    endRun(run, 'completed')
```

Der Run endet in demselben Tick, in dem die zweite Objective abgeschlossen wird. Alle drei Objectives vollständig zu erfüllen ist möglich und ergibt einen höheren Score (Faktor `task_completed × 1000` pro Objective, §15).

**Frühzeitiger Sieg belohnt Effizienz:** Die Score-Formel enthält `(tick_limit − done_tick) × 10` — ein Sieg bei Sol 60 ergibt mehr Punkte als derselbe Sieg bei Sol 90. Das schafft permanenten Anreiz für schnelles Spielen, ohne Erkundung und Aufbau zu bestrafen.

**Sieg ist nur in Phase 2 möglich:** `endRun('completed')` wird nur aufgerufen wenn `run.phase == 2`. In Phase 1 gibt es ausschließlich Fail States (Trust, Schulden, Zeit — letzterer praktisch nie, da Phase 1 deutlich kürzer als `tick_limit` dauern sollte).

---

### 18.2 Fail States

Drei Fail States. Alle werden am Ende der Tick-Phase 5 geprüft, nach dem Objective-Update (damit ein Sieg auf demselben Tick immer Vorrang vor einem gleichzeitigen Fail State hat). Kanonische Implementierung: `RunProgressService::checkFailStates()`.

#### Fail State 1 — Vertrauenskollaps

**Bedingung:** `trust < config('game.run.trust_fail_threshold')` → Standardwert **−20**

**Auslösung:** Instant in demselben Tick, in dem der Vertrauenswert unter −20 fällt. Kein Streak erforderlich.

Begründung gegen eine Streak-Mechanikverzögerung (wie in §15 ursprünglich skizziert): Trust unter −20 bedeutet aktive Feindseligkeit der Kolonisten, keinen vorübergehenden Stimmungseinbruch mehr. Eine Streak-Wartezeit würde die Aussagekraft des Trust-Werts verwässern und den Spieler in einem faktisch verlorenen Zustand weiterspielen lassen.

**Warnstufen (INNN + UI):**

| Schwellwert | Maßnahme |
|-------------|---------|
| Trust < 0 | INNN-Ereignis (Kolonist, Absender): "Die Stimmung in der Kolonie ist angespannt." — einmalig pro Run |
| Trust < −10 | Roter Farbwechsel am Trust-Ressource-Chip in der Ressourcenleiste |
| Trust < −18 | INNN-Warnung von Nexus: "Direktor, die Lage ist kritisch. Sofortige Maßnahmen erforderlich." |
| Trust < −20 | Fail State — Run endet sofort |

> ⚠️ BALANCE CONCERN: Die −20-Schwelle ist bewusst tief gesetzt. Ein Hunger-Streak von vier Solen (kumulierter Malus nach `TrustService::hungerPenalty`: −2 − 3 − 4 − 5 = −14 kumuliert nach Streak 4) plus ein Level-Down-Event (−3) würde die Schwelle knapp nicht erreichen — das ist gewollt: Vernachlässigung soll spürbar bestrafen, aber erholbar bleiben. Nach erstem Playtest kalibrieren ob −20 zu tief (Spieler scheitern selten) oder zu flach (Spieler scheitern überraschend schnell) ist.

**Narrativer Ausgang:** "Die Kolonisten haben das Vertrauen verloren. Der Direktor wurde abgesetzt."

---

#### Fail State 2 — Nexus-Schuldengrenze

**Bedingung:** `nexus_debt > 12.000` Cr

**Auslösung:** Instant bei Überschreitung. Geprüft sowohl in `checkFailStates()` als auch direkt in `checkNexusInterventions()` (Phase-2-Sol 55).

**Warnstufen (UI-Schuldenbalken):**

| Schuldenstand | Maßnahme |
|---------------|---------|
| > 9.600 Cr (80 %) | Schuldenbalken wechselt auf Gelb |
| > 11.400 Cr (95 %) | Schuldenbalken wechselt auf Rot; INNN-Meldung von Nexus: "Kreditlimit fast erreicht." |
| > 12.000 Cr | Fail State — Run endet sofort |

> ⚠️ BALANCE CONCERN (Implementierungshinweis, Stand 2026-06-28): `nexus_debt` als Mechanik ist in der Code-Logik referenziert (`$run->nexus_debt`), aber die Schulden-Akkumulation (Startkapital als Schuld, Nexus-Deals als Schuldenerhöhung, manuelle Rückzahlung) ist noch nicht vollständig implementiert. Das `nexus_debt`-Feld auf der `runs`-Tabelle muss per Migration angelegt werden bevor dieser Fail State produktiv greift. Die Schulden-Mechanik ist in §15 "Nexus-Schulden-Mechanik" skizziert.

**Narrativer Ausgang:** "Nexus hat die Konzession entzogen. Der Direktor wurde zurückgerufen."

---

#### Fail State 3 — Fristablauf ohne Sieg

**Bedingung:** `current_tick >= config('game.run.tick_limit')` (100) UND weniger als 2 Objectives abgeschlossen

**Auslösung:** In `checkFailStates()` nach jedem Tick. Das Sieg-Gate (§18.1) wird vor den Fail States geprüft — wer die zweite Objective genau auf Sol 100 abschließt, gewinnt noch.

**Countdown-Warnstufen:**

| Sol | Maßnahme |
|-----|---------|
| tick_limit − 20 (Sol 80) | Countdown-Anzeige erscheint im UI ("Noch 20 Sole bis Missionsende"); INNN-Nachricht von Nexus |
| tick_limit − 10 (Sol 90) | INNN-Letzte-Warnung wenn 0 Objectives abgeschlossen |
| tick_limit (Sol 100) | Fail State — Run endet |

**Narrativer Ausgang:** "Fristablauf. Die Konzession wurde nicht verlängert."

---

### 18.3 Run-Ende-Screen

Der Run-Ende-Screen ersetzt die Kolonie-Ansicht unmittelbar nach `endRun()`. Er ist kein Overlay, sondern ein eigener Screen. Der Sol-Report-Screen (§15, `SolReportService`) läuft vor dem End-Screen wenn das Ende durch einen Tick ausgelöst wird.

#### Aufbau

**Ergebnis-Header (oben, volle Breite):**

| Ergebnis | Überschrift | Ton |
|----------|-------------|-----|
| Sieg | MISSION ERFÜLLT | Warm, hell |
| Niederlage: Trust | KONZESSION WIDERRUFEN | Kühl, gedämpft |
| Niederlage: Schulden | KONZESSION EINGEZOGEN | Kühl, gedämpft |
| Niederlage: Zeit | MISSION ABGEBROCHEN | Neutral, dunkel |

**Nexus-Kommentar (direkt unter dem Header, 2–3 Sätze):**

| Ergebnis | Nexus-Kommentar (Entwurf — finale Formulierung via `content-writer`) |
|----------|----------------------------------------------------------------------|
| Sieg 3/3 | "Alle Direktiven erfüllt. Konzession verlängert. Ihre Akte wird dem Zentralbüro übermittelt." |
| Sieg 2/3, schnell (< 70 % des Zeitlimits verbraucht) | "Zwei Direktiven erfüllt. Konzession bestätigt. Effizienzrating: überdurchschnittlich." |
| Sieg 2/3, langsam (≥ 70 % des Zeitlimits verbraucht) | "Zwei Direktiven erfüllt. Konzession bestätigt. Leistungsrating: ausreichend. Weitere Bewertung folgt." |
| Niederlage: Trust | "Kolonie destabilisiert. Direktorsabsetzung registriert. Nachfolge wird organisiert." |
| Niederlage: Schulden | "Kreditlimit überschritten. Konzession eingezogen. Schulden sind ausstehend." |
| Niederlage: Zeit | "Frist abgelaufen. Kolonie übernommen. Keine weiteren Informationen verfügbar." |

> **Ton-Regel:** Nexus-Kommentare sind kurz, passiv, ohne Emotion. Nexus bewertet — es trauert nicht, gratuliert nicht. Kein "Schade, aber..." oder "Herzlichen Glückwunsch!". Die Kälte ist Teil des Lore.

**Zusammenfassung (darunter, scrollbar):**

- **Objectives-Status:** 3 Felder mit Symbol (✓ Abgeschlossen Sol X / ✗ Nicht erfüllt / ? Phase 2 nicht erreicht)
- **Score:** Große Zahl; darunter Aufschlüsselung: Tasks × 1.000 + Sol-Bonus + Credits-Bonus + Trust-Bonus (entspricht `calculateScore()`)
- **Kolonie-Statistiken:** Gespielte Sole · Trust am Ende · Credits am Ende · Gebaute Gebäude · Erforschte Kenntnisse
- **Buttons:** "Neuer Run starten" (primär) und "Kolonie ansehen" (sekundär, read-only — die letzte Kolonie bleibt bis zum nächsten Run-Start erhalten)

> ⚠️ BALANCE CONCERN: "Kolonie ansehen" nach Run-Ende setzt voraus, dass Koloniedaten beim Run-Ende nicht gelöscht werden. Technisch: `runs.status = 'completed'|'failed'` + `ended_at` setzen, Colony-Daten unberührt lassen. Erst beim Start eines neuen Runs (`POST /lobby/start`) wird die Colony zurückgesetzt. Falls historische Run-Daten archiviert werden sollen (Phase 4+), muss die db-migration-agent eine Archiv-Tabelle anlegen.

**Technische Verortung:** Route `GET /run/result` oder `/lobby` mit End-State-Branching in `LobbyController`. `endRun()` in `RunProgressService` setzt `status`, `fail_reason`, `ended_at` — der Controller liest diese Felder und wählt das korrekte Template.

---

### 18.4 Tick-Limit & Pacing

**Entscheidung: 100 Sols bleibt der Standard (Stand 2026-06-28)**

100 Sols ist für den aktuellen Spielstand richtig. Playtest erreicht Sol 4/5 problemlos — das ist Early Phase 1, kein Maßstab für das Gesamtpacing.

**Typischer Run-Korridor (Richtwert):**

| Phase | Sols | Anmerkung |
|-------|------|-----------|
| Phase 1 — Stabilisierung | 15–25 | CC Lv3 + 2 Produktionsgebäude Lv2 + 3 Berater |
| Phase 2 früh — Einrichten | 10–20 | Pfad-Gebäude ausbauen, Berater optimieren |
| Phase 2 mitte — Objectives | 20–35 | Kernarbeit an den zwei Ziel-Objectives |
| Phase 2 spät — Optimierung | 5–15 | Dritte Objective optional; Score verbessern |
| **Guter Gesamtrun** | **50–80 Sols** | |

Das Tick-Limit von 100 gibt 20–50 Sols Puffer für schlechtere Starts und langsamere Spieler.

**Pacing-Kontrollpunkte (Nexus-Interventionen in Phase-2-Sol):**

`checkNexusInterventions()` arbeitet in **Phase-2-Sol** (nicht Gesamt-Sol, nicht absolute Tick-Nummer). Bei einem Phase-1-Abschluss um Gesamt-Sol 20 ergibt sich:

| Phase-2-Sol | Gesamt-Sol (bei Phase-1-Ende Sol 20) | Bedeutung |
|-------------|--------------------------------------|-----------|
| 30 | ~50 | Mindestens 1 Objective > 50 % — sonst Nexus-Warnung |
| 50 | ~70 | Mindestens 1 Objective vollständig — sonst zweite Warnung |
| 65 | ~85 | Berater-Sanktion wenn 0 Objectives abgeschlossen |
| 80 | ~100 | Countdown-Meldung (= Gesamtticklimit bei normalem Phase-1-Tempo) |

Bei Phase-1-Ende Sol 20 fällt Phase-2-Sol 80 exakt auf Gesamt-Sol 100 — das ist kein Zufall, sondern die gewünschte Kalibrierung: der Countdown erscheint genau wenn das Limit erreicht wird.

**Anpassungsrichtlinien nach Playtest:**

| Beobachtung | Maßnahme |
|-------------|---------|
| Phase-1 endet typisch < Sol 15 | tick_limit auf 85–90 senken (mehr Druck in Phase 2) |
| Phase-1 dauert typisch > Sol 25 | Phase-1-Abschlussbedingungen lockern, nicht tick_limit erhöhen |
| Typischer Sieg > Sol 90 | `TASK_TARGETS`-Werte in `RunProgressService` senken (Objectives zu schwer) |
| Typischer Sieg < Sol 55 | `TASK_TARGETS`-Werte erhöhen oder tick_limit auf 80 senken |

> ⚠️ BALANCE CONCERN: `task_expedition_coverage: 19` (alle Colony-Zone-Tiles erkundet) ist der schwierigste Task-Target-Wert und braucht als erstes Playtest-Validierung. 19 Tiles bei ring-gestaffelten Kosten (1/2/3 Nav-AP/Ring) und einem Junior-Raumfahrer mit ~7 Nav-AP/Sol ergibt rechnerisch ~3–5 Sole reiner Erkundungsarbeit, was realistisch ist — aber stark von der Tile-Verteilung der Karte abhängt (impassable Tiles zählen nicht; auf vulkanischen Planeten könnten sehr viele Tiles aus der Zone fallen). Vor dem Finalisieren dieses Task-Targets den Colony-Zone-Expansion-Mechanismus (§4a) gegen typische Karten durchrechnen.

> ⚠️ BALANCE CONCERN: `task_credit_reserve: 10` bedeutet 10 aufeinanderfolgende Sole mit Credits > 5.000. Mit Nexus-Subvention 30 Cr/Sol + Housing-Steuer (20 Cr/Sol je Housing-Level, §3) und einem Wohnhabitat Lv2 = 70 Cr/Sol Einnahmen — Upkeep für 3 Berater Rang 1 kostet 3 × 10 = 30 Cr/Sol → Nettoeinnahmen ~40 Cr/Sol. Ab Startkapital 3.000 Cr dauert es ~50 Sole ohne Ausgaben um 5.000 Cr zu erreichen. Realistische Ausgaben (Gebäude, Reparaturen) machen den Task signifikant schwieriger. Nach Playtest kalibrieren.

---

### 18.5 GDD-Drifts (Stand 2026-06-28)

Bekannte Abweichungen zwischen GDD §15-Prosa und dem tatsächlichen Code/Config:

| Thema | GDD §15 (alt) | Code/Config (kanonisch) | Korrekt |
|-------|--------------|------------------------|---------|
| Trust Fail State — Bedingung | "N Sole unter Schwellenwert 10 (Streak)" | Instant bei trust < −20 (`trust_fail_threshold`) | Code/Config |
| Trust Fail State — Vorwarnung | "INNN bei < 20, roter Indikator bei < 10" | Schwellenwerte nicht implementiert — Design-Intent hier in §18.2 | §18.2 |
| Nexus-Milestone-Sol-Basis | "Sol 30/50/85/90" impliziert Gesamt-Sol | Phase-2-Sol in `checkNexusInterventions()` | Code |
| Sol-85-Sanktion (GDD §15) | "Sol 85 Gnadenfrist" | Phase-2-Sol 65 im Code | Code |

> **TODO:** §15 "Fail States" und "Gnadenfrist" in einer kommenden GDD-Revision auf Phase-2-Sol-Basis korrigieren und Trust-Fail-State von "Streak unter 10" auf "Instant unter −20" aktualisieren. Nicht jetzt — §18 ist die autoritative Definition, §15-Abweichungen sind dokumentiert.

---

### 18.6 Offene Implementierungsaufgaben (game-developer / db-migration-agent)

| Aufgabe | Verantwortung | Priorität |
|---------|--------------|-----------|
| `checkWinCondition()` in RunProgressService: nach `updateObjectiveProgress()`, wenn `completed >= 2 && phase == 2` → `endRun('completed')` | game-developer | Hoch |
| `nexus_debt`-Feld auf `runs`-Tabelle anlegen (Migration) | db-migration-agent | Mittel |
| Schulden-Akkumulation implementieren (Startkapital als initiale Schuld, Rückzahlung via Nexus-UI) | game-developer | Mittel |
| Trust-Warn-INNN-Events implementieren (< 0, < −10, < −18) | game-developer | Mittel |
| Run-Ende-Screen Blade-Template | ui-specialist | Mittel |
| Nexus-Kommentar-Texte | content-writer | Niedrig |
| `config('game.run.nexus_debt_limit')` als Config-Key anlegen (aktuell hardcodiert 12000 in RunProgressService) | game-developer | Niedrig |
