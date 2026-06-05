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
Gebäude und Kenntnisse verfallen ohne aktive Pflege. Wer seine Kolonie vernachlässigt, verliert sie langsam — nicht durch Gegner, sondern durch Entropie. Der Verfall zwingt zur Priorisierung und macht jeden Sol zu einer echten Ressourcenentscheidung.

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
| 1. Fleet | Flottenbewegung, Ressourcentransfer, Zwischenfälle |
| 2. Decay | Gebäude-, Schiffs- und Kenntnisverfall (SP-Abzug; Level-Down bei SP ≤ 0) |
| 3. Supply & Ressourcen | Supply-Cap neu berechnen (§6), dann Rohstoffproduktion (Vertrauens-Multiplikator angewendet) |
| 4. Vertrauen | Vertrauenswert neu berechnen, `colony_resources` aktualisieren (§14) |
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

| Ressource | Gebäude (Rohbau) | Schiffe | High-Tech-Gebäude | Reparatur |
|-----------|-----------------|---------|-------------------|-----------|
| Regolith | Ja (außer CC + Harvester) | Nein | Nein | Nein |
| Werkstoffe | Nein | Ja | Ja | Ja |
| Organika | Nein | Ja | Ja | Nein |
| Credits | Ja (immer — Grundkosten) | Ja | Ja | Ja |

**Ausnahme CC + Harvester:** CommandCenter und Harvester kosten beim Bau nur Credits — sie sind der Einstiegspunkt der Kolonie und dürfen keinen Ressourcen-Catch-22 erzeugen (Regolith braucht Harvester, Harvester braucht Regolith).

> **Designprinzip:** Regolith = Rohbau, Werkstoffe = High-Tech/Schiffe, Organika = biologische Schicht (Schiffe + High-Tech). Credits sind immer beteiligt — als Grundkosten und einziger universeller Tauschstoff.

### Werkstoffe: Singleplayer-Sicherheitsnetz

Im Singleplayer gibt es keinen Spieler-zu-Spieler-Handel. Werkstoffe sind daher über **KI-Händler** (stationäre NPC-Fraktionen) immer kaufbar — teurer als Spielerhandel, aber garantiert verfügbar. Events liefern Werkstoffe als Bonus, nie als einzige Quelle.

Typische Werkstoffe-Events (immer mit Wahlmöglichkeit, nie kostenlos):
- **Strandetes Frachtschiff** — Bergung kostet Navigation-AP, gibt Werkstoffe
- **Händlerkonvoi in der Nähe** — befristetes Kaufangebot (2 Sole), günstiger als KI-Marktpreis
- **Trümmerfeld im System** — Flotte entsenden, Werkstoffe heimholen

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
| 41 | bioFacility | Agrardom | Agrarian Dome | — | CC Lv1 + Harvester Lv1 |
| 30 | depot | Lagerhalle | Warehouse | — | CC Lv2 |
| 31 | sciencelab | Analytik-Labor | Analytics Lab | — | CC Lv2 |
| 46 | infirmary | Krankenstation | Medical Station | — | CC Lv2 |
| 52 | bar | Cantina | Cantina | — | CC Lv2 |
| 44 | hangar | Hangar | Hangar | — | CC Lv3 |
| 32 | temple | Religiöse Stätte | Sacred Site | — | CC Lv4 |
| 50 | monument | Kolonialdenkmal | Colonial Monument | — | CC Lv5 |
| 53 | securityHub | Sicherheits-Hub | Security Hub | 3 | CC Lv2 |
| 54 | uplinkStation | Uplink-Station | Uplink Station | 3 | CC Lv2 |
| 55 | tradingPost | Handelsposten | Trading Post | 3 | CC Lv4 |

> **Harvester (Sondergebäude):** Der Harvester unterscheidet sich von allen anderen Gebäuden: Er steht nicht in der Kolonie-Zone, sondern auf einem Ressourcen-Tile in der Exploration Zone. Er produziert passiv je nach Tile-Typ (Regolith oder andere Mineralien). Er kann verlegt werden (Aktion: 1 Construction-AP, keine Downtime). Es gibt genau einen Harvester pro Kolonie. Technisch ist er ein Gebäude mit einer `tile_x/tile_y`-Position statt eines Kolonie-Slots.

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

### Lagerhalle (depot) — Mechanik

Die Lagerhalle erhöht die maximale Lagerkapazität aller drei Kolonieressourcen (Regolith, Werkstoffe, Organika). Ohne Lagerhalle gilt ein Basis-Cap; jedes Level der Lagerhalle erhöht diesen Cap.

```
resource_cap = base_cap + (depot_level × cap_per_level)
```

Überschreitet die Produktion den Cap in einem Sol, gehen die überschüssigen Einheiten verloren. Das erzeugt eine echte Entscheidung: Wer stark produziert (Harvester + Agrardom auf hohem Level) muss früher in Lagerkapazität investieren, sonst verpufft die Produktion.

| Parameter | Richtwert | Quelle |
|-----------|-----------|--------|
| `base_cap` | 500 je Ressource | nach Playtest kalibrieren |
| `cap_per_level` | +200 je Depot-Level | nach Playtest kalibrieren |

> **TODO Balance:** Konkrete Zahlen (`base_cap`, `cap_per_level`) nach erstem Playtest festlegen und in `config/buildings.php → depot` ergänzen. Das Ressourcen-Cap-System muss bei Implementierung im ProductionService berücksichtigt werden.

---

### Sicherheits-Hub (securityHub) — Mechanik

Der Sicherheits-Hub ist ein auf 1 Instanz begrenztes Infrastrukturgebäude (CC Lv2). Er bietet zwei unabhängige Effekte:

**Passiv — günstigere Verteidigung:**
Die `defend`-Order kostet 1 Nav-AP statt 2. Verteidigung wird attraktiver ohne den Angriff zu verbilligen (GDD §1.1 Prinzip: Militär kostet mehr als zivil bleibt gewahrt — Angriff kostet weiterhin 3).

**Passiv — Level-Down-Recycling:**
Wenn ein Gebäude durch Decay ein Level verliert, gibt die Kolonie automatisch einen kleinen Ressourcenanteil zurück (handelbare Ressourcen: Regolith, Werkstoffe, Organika). Konkrete Prozentzahl nach Playtest festlegen; die Rückgabe muss deutlich unter dem Reparaturwert liegen damit kein Anreiz entsteht, Verfall absichtlich zu provozieren.

> **TODO Balance:** Recycling-Prozentsatz und genaue Baukosten nach erstem Playtest kalibrieren. Vorläufig: 200 Rg + 200 Co, Supply 8, Decay 0.67.

---

### Uplink-Station (uplinkStation) — Mechanik

Die Uplink-Station ist das einzige Kommunikationsgebäude der Kolonie — 1 Instanz, Lv1–3. **Ohne Uplink-Station Lv1 sind aktive Nexus-Anfragen gesperrt** (Handelsschiff anfordern, Verwaltungsanfragen). Eingehende INNN-Nachrichten des Nexus (Milestones, Warnungen) kommen immer an — diese sind nicht abhängig vom Gebäude.

| Level | CC-Voraussetzung | Freischaltet / Effekt |
|-------|-----------------|----------------------|
| 1 | CC Lv2 | Aktive Nexus-Anfragen (Handelsschiff, Verwaltung) |
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

### Sichtbarkeit

- **Kolonie-Zone:** alle Tiles sofort sichtbar
- **Exploration Zone:** Fog of War — Tiles werden einzeln per Navigation-AP aufgedeckt

### Visuelle Zone-Abgrenzung

Die Kolonie-Zone-Grenze ist auf kleinen Karten nicht mehr ein sauberer Ring, sondern ergibt sich aus dem `is_colony_zone`-Flag pro Tile. Das Frontend rendert Colony-Zone-Tiles mit einem warmen Basis-Tint (Farbschema: Weiß/Anthrazit/Rot-Palette), Exploration-Zone-Tiles mit einem kühleren, dunkleren Tint. Der Spieler erkennt die Grenze durch Farbe, nicht durch Position. Regolith-Tiles und impassable Tiles innerhalb der inneren Ringe sind immer Exploration Zone — sie wirken als visuelle "Lücken" in der Colony Zone, was die unterschiedliche Funktion deutlich kommuniziert.

### Tile-Typen und Schwierigkeit

Tile-Typen (z.B. "Reicher Erzknoten", "Armes Vorkommen", "Organik-freies Terrain") beeinflussen die Ressourcenproduktion. Die Schwierigkeit eines Runs steuert die Tile-Qualität: schwieriger Run = schlechtere Vorkommen, keine reichen Erznodes in Ring 1.

### Organika

Organika entsteht nicht auf Tiles (biologische Materialien kommen auf Planeten nicht natürlich vor). Stattdessen produziert der **Agrardom** (Gebäude innerhalb der Kolonie-Zone) Organika passiv pro Sol.

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

Supply ist **kein fliessender Pool**, sondern ein **Kapazitätsdeckel** (Cap-Modell). Gebäude und Kenntnisse erhöhen den Cap. Schiffe und Gebäude (außer CC und Wohnkomplex) belegen Supply dauerhaft. Berater belegen **kein** Supply — sie kosten Credits. Es gibt keine Sol-basierte Supply-Generierung.

```
supply_cap    = CC-Level × 10 + Anzahl-Wohnkomplexe × 8 + Σ(Kenntnisse-Cap-Bonus)
laufende_last = Σ(Schiffe × Supply-Kosten) + Σ(Gebäude-Kosten)
freies_supply = supply_cap − laufende_last
```

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

### Supply-Kosten der Schiffstypen

Korvetten sind bewusst teurer als Frachter — Schutz kostet mehr als Transport (siehe §1.1). Drohnen kosten kein Supply — sie sind unbemannt. Die Flottengröße wird organisch durch den Supply-Cap begrenzt; es gibt keinen harten Schiffscount-Cap.

| Schiff | ship_id | Supply (Unterhalt) | Bemerkung |
|--------|---------|-------------------|-----------|
| drone | 85 | **0** | unbemannt, kein Hangar nötig |
| corvette | 37 | **14** | benötigt Hangar |
| freighter | 47 | **6** | benötigt Hangar |

**Beispielrechnung:** 2 Korvetten + 2 Frachter = 28 + 12 = 40 Supply — bereits mehr als die Hälfte eines typischen Mid-Game-Caps.

**Schiffe haben keinen passiven Decay.** Wartungsdruck entsteht durch aktiven Einsatz (Schiffs-Verschleiß — siehe §7) und durch Hangar-Decay. `fleet_ships.status_points` sinkt durch Flottenorders, nicht durch Zeitablauf.

> **TODO (Design, Phase 4+):** Sonderfall "Schiffe ohne Hangar" — durch Events, Handelsdeals oder andere Mechaniken könnte der Spieler Schiffe erwerben, die normalerweise nicht im Hangar baubar sind (z.B. erbeutete Fraktionsschiffe, Belohnungsschiffe aus Events). Diese wären per Run einzigartig und ein Roguelike-Element das jeden Durchlauf anders macht. Mechanik (Hangar-Pflicht? Supply-Kosten?) und Balance noch offen — für spätere Phase detailliert ausarbeiten.

### Supply-Kosten Gebäude

**Berater:** kein Supply-Verbrauch — Kosten laufen über Credits (siehe §13).

**CommandCenter und Wohnhabitat:** kein Supply-Verbrauch (sie definieren den Cap).

**Gebäude** (individuelle Supply-Kosten aus Technologie-Tabelle):

| Gebäude | Supply |
|---------|--------|
| Harvester, Agrardom | 2 |
| Kolonialdenkmal | 2 |
| Lagerhalle | 3 |
| Cantina, Religiöse Stätte | 4 (je) |
| Uplink-Station, Handelsposten | 6 (je) |
| Analytik-Labor, Sicherheits-Hub | 8 (je) |
| Krankenstation | 10 |
| Hangar | 12 (je Instanz) |

> Supply-Kosten sind **sol-rate-unabhängig** — sie beschreiben eine permanente Kapazitäts-Belegung, keine Fluss-Größe.

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
| Schiffe | Verschleiß (`wear_per_order` aus config/ships.php) | Aktiver Einsatz (Orders) | Repair-Order (Construction-AP + Credits) |
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
| Schiffs-Verschleiß | Zustand aktiv genutzter Schiffe | pro Order | Repair-Order (Construction-AP + Credits) |
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
| Lagerhalle (depot), Krankenstation (infirmary), Hangar | 30 | 0.67 |
| Wohnhabitat (housingComplex) | 45 | 0.44 |
| Kommandozentrale (max Lv5), Kolonialdenkmal | 60 | 0.33 |


> **Sol-Skalierung:** Bei 24 Solen/Tag entspricht "133 Sole" ~5,5 Echtzeit-Tagen. Bei 1 Sol/Tag sind es 133 Tage. Die Sol-Anzahl bleibt gleich — nur die Echtzeit-Dauer ändert sich. Das ist die gewünschte Eigenschaft des Sol-basierten Systems (intern: tick-basiert).

> Konkrete Werte per Migration in die Stammdaten-Tabelle (`buildings.decay_rate`). Kenntnisse und Schiffe haben kein Decay-System; `researches.decay_rate` und `ships.decay_rate` entfallen.

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

Wenn `status_points ≤ 0`, wird das Schiff **deaktiviert** (nicht zerstört). Es bleibt in der Datenbank, ist aber nicht einsatzbereit. Reaktivierung erfordert eine Reparatur-Order (verbraucht Construction-AP, kostet Credits).

| Schiffstyp | wear_per_order (Richtwert) | Begründung |
|------------|---------------------------|------------|
| drone | 0.05 | Unbemannte Drohne — minimalster Verschleiß |
| korvette | 0.20 | Militärisches Manövrieren — höherer Verschleiß |
| frachter | 0.10 | Routinebetrieb — moderater Verschleiß |

Konkrete Werte stehen in `config/ships.php` je Schiffstyp. Nach erstem Playtest kalibrierbar.

**Kein passiver Decay:** Ein Schiff, das im Hangar liegt und keine Orders erhält, verliert keine `status_points`. Das unterscheidet Schiffs-Verschleiß fundamental von Gebäude-Decay — nur Aktivität kostet.

**Reparatur:** Repair-Order auf die Flotte. Kosten: Construction-AP + Credits (aus `config/ships.php → repair_cost_per_point`). Reparatur gibt `status_points` zurück bis auf `max_status_points`.

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

Der Hangar-Screen ist die Verwaltungsansicht aller Schiffe einer Kolonie. Er wird aktiv sobald mindestens ein Hangar (building_id 44, CC Lv3) gebaut wurde.

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

Mehrere Schiffe desselben Typs sind erlaubt. Die natürliche Begrenzung ergibt sich aus dem Koloniebauplatz — ein Spieler kann realistisch 2–3 Hangare errichten bevor der Platz für wichtigere Gebäude fehlt. Kein Hard-Cap nötig.

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
| `depot` | Depot | CC Lv 2 | supply-limitiert |
| `bar` | Bar / Cantina | CC Lv 2 + Wohnhabitat Lv 1 | supply-limitiert |
| `infirmary` | Krankenstation | CC Lv 2 | supply-limitiert |
| `hangar` | Hangar | CC Lv 3 | supply-limitiert |
| `securityHub` | Sicherheits-Hub | CC Lv 2 | max. 1 Instanz |
| `uplinkStation` | Uplink-Station | CC Lv 2 | max. Lv 3 |
| `temple` | Religiöse Stätte | CC Lv 4 | supply-limitiert |
| `tradingPost` | Handelsposten | CC Lv 4 | max. 1 Instanz |
| `monument` | Kolonialdenkmal | CC Lv 5 | supply-limitiert |

Die 14 Gebäude decken alle Spielsäulen ab: Infrastruktur (CC, Depot, Wohnhabitat), Produktion (Harvester, Bio-Anlage), Wissenschaft (Analytik-Labor), Flotte (Hangar), Kommunikation (Uplink-Station), Sicherheit (Sicherheits-Hub), Handel (Handelsposten), Wohlfahrt (Bar, Krankenstation, Religiöse Stätte, Denkmal).

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

Berater erscheinen im Techtree in Spalte 0. Ihre Gates spiegeln die Einführungsreihenfolge im Run wider. CC-Level öffnet Berater-Slots (CC Lv1 = 1 Slot, ..., CC Lv5 = 5 Slots).

| Key (intern) | Name (DE) | AP-Typ | Hire-Voraussetzung |
|---|---|---|---|
| `engineer` | Baumeister | construction | CC Lv 1 |
| `scientist` | Analytiker | research | CC Lv 2 |
| `pilot` | Raumfahrer | navigation | Hangar Lv 1 |
| `trader` | Konsul | economy | Bar Lv 1 |
| `strategist` | Stratege | strategy | Hangar Lv 2 |

---

### 11.2 Abhängigkeitsregeln

Das Abhängigkeitssystem folgt vier Regeln:

**Regel 1 — CC als Tier-Gate**
Die Kommandozentrale hat 5 Level und schaltet je Level eine Gebäude-Tier frei. Kein Gebäude höherer Tier ist baubar, solange das CC-Level nicht erreicht ist. Die Tiers:

| CC-Level | Freischaltet |
|---|---|
| 1 | Wohnhabitat, Harvester |
| 2 | Analytik-Labor, Depot, Krankenstation, Cantina, Sicherheits-Hub, Uplink-Station (Lv1) |
| 3 | Hangar; Uplink-Station Lv2 freischaltbar |
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
| Stratege (Berater) | Hangar Lv 2 |

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
| 2 | 2 | depot | building | 1 | 1 |
| 2 | 2 | sciencelab | building | 1 | 2 |
| 2 | 2 | infirmary | building | 1 | 3 |
| 2 | 2 | bar | building | 2 | 1 |
| 2 | 2 | scientist | personell | 2 | 3 |
| 2 | 2 | trader | personell | 3 | 3 |
| 2 | 2 | knowledge_construction | research | 4 | 3 |
| 2 | 2 | knowledge_agronomy | research | 5 | 3 |
| 2 | 2 | knowledge_health | research | 6 | 1 |
| 2 | 2 | knowledge_trade | research | 6 | 3 |
| 3 | 3 | hangar | building | 1 | 2 |
| 3 | 3 | strategist | personell | 1 | 3 |
| 3 | 3 | drone | ship | 2 | 2 |
| 3 | 3 | pilot | personell | 2 | 3 |
| 3 | 3 | knowledge_geology | research | 3 | 1 |
| 3 | 3 | freighter | ship | 3 | 2 |
| 3 | 3 | knowledge_cartography | research | 3 | 3 |
| 3 | 3 | corvette | ship | 4 | 2 |
| 3 | 3 | knowledge_defense | research | 4 | 3 |
| 4 | 4 | temple | building | 1 | 2 |
| 5 | 5 | monument | building | 1 | 2 |

> Die `row`/`col`-Werte sind kanonisch — sie werden 1:1 in die DB-Tabellen geschrieben. Das Grid-CSS liest sie als `grid-row: row + 1; grid-column: col + 1`.

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

Die Bar ist ab CC Lv2 verfügbar. Pro Sol erscheinen 0–2 Gäste — Händler, Schmuggler, Gelegenheitsverkäufer. Jeder Gast hat ein konkretes Angebot das **1–2 Sole gültig** ist. Danach ist der Gast weg.

**Angebotstypen:**
- Ressource gegen Credits (z.B. 50 Werkstoffe für 800 Cr)
- Ressource gegen Ressource (z.B. 30 Organika gegen 20 Regolith)

Der Spieler sieht 0–2 Angebote und entscheidet: annehmen oder ablehnen. Keine unbegrenzte Auswahl — echte Entscheidung unter Zeitdruck.

**Spieler-zu-Spieler-Handel:** Wenn ein Spieler ein Angebot in der Bar einstellt, erscheint es für andere Spieler ebenfalls als "Gast". Ob ein Gast ein NPC oder ein echter Spieler ist, bleibt unsichtbar — atmosphärisch stimmig, technisch einfach.

**Händler-Berater (advisor_trader):**
- Rang 1: Basis-Angebote (0–1 Gäste/Sol, Marktpreise)
- Rang 2: mehr Angebote (0–2 Gäste/Sol), leicht bessere Preise
- Rang 3: regelmäßige Angebote (1–2 Gäste/Sol), deutlich bessere Preise

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

### Slot-System: CC-Level als Gate

Die Kommandozentrale bestimmt, wie viele Berater-Slots die Kolonie koordinieren kann. Die Slots werden in der Reihenfolge ihrer Nützlichkeit freigeschaltet:

| CC-Level | Freigeschalteter Slot | Beratertyp |
|----------|-----------------------|-----------|
| 1 | Slot 1 | Baumeister |
| 2 | Slot 2 | Analytiker |
| 3 | Slot 3 | Raumfahrer |
| 4 | Slot 4 | Konsul |
| 5+ | Slot 5 | Stratege |

Wer alle 5 Berater will, braucht mindestens CC Lv5. Das verknüpft Berater-Ausbau organisch mit dem Koloniefortschritt. Pro Typ und pro Kolonie kann immer nur genau **ein** Berater den Slot belegen — ein zweiter Ingenieur auf derselben Kolonie ist nicht möglich.

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
| Stratege | Sicherheitsanalyse | 3–4 | Nächster zufälliger NPC-Encounter ist vorab bekannt (Stärkewert + Typ sichtbar vor dem Auslösen) |
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
| 37 | korvette | -1 |
| 47 | frachter | +1 |

**Rationale:** Die Korvette signalisiert Wachsamkeit und Anspannung (-1/Schiff). Der Frachter steht für Versorgung und Normalität (+1/Schiff). Drohnen sind neutral — unbemannte Geräte erzeugen keine emotionale Reaktion bei den Bewohnern.

**Skalierungsproblem:** Da Schiffszahlen potenziell groß werden können, wird der Gesamtbeitrag aller Schiffe auf `±30` gecapped, bevor er in die Vertrauen-Summe eingeht:

```
ship_vertrauen = clamp(Σ(ship_amount × vertrauen_per_ship), -30, +30)
```

> ⚠️ BALANCE CONCERN: Der Cap von ±30 für Schiffe muss nach dem ersten Playtest evaluiert werden. Eine Kolonie mit 30 Korvetten wäre bei -30 bereits am Cap — das könnte für defensiv-orientierte Spieler zu früh einsetzen. Alternativ: Cap auf -20 für eine moderatere Wirkung.

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

**Begegnungen / Zwischenfälle:**

| Event-Key | Beschreibung | Vertrauenseffekt |
|-----------|-------------|------------------|
| `encounter_won` | Zwischenfall erfolgreich abgewehrt | +2 |
| `encounter_lost` | Eigene Schiffe bei Zwischenfall beschädigt | -5 |
| `colony_threatened` | Kolonie direkt bedroht (unabhängig vom Ausgang) | -4 |

**Diplomatie:**

| Event-Key | Beschreibung | Vertrauenseffekt |
|-----------|-------------|------------------|
| `treaty_signed` | Diplomatischer Vertrag abgeschlossen | +3 |

**Rationale für neue Events:**
- `colony_threatened` (-4) ist von `encounter_lost` (-5) getrennt, weil eine Bedrohung die Kolonisten auch dann verunsichert, wenn sie abgewehrt wurde. Beide Effekte können in einem Sol summieren (Bedrohung + Verlust = -9).
- `trade_blocked` (-3) macht Handelsblockaden spürbar — nicht nur wirtschaftlich, sondern auch in der Stimmung der Siedlung.

> ⚠️ BALANCE CONCERN: Ein gleichzeitiger `colony_threatened` + `encounter_lost` in einem Sol summiert sich zu -9. Das kann eine neutrale Kolonie (0) spürbar in Richtung "Unruhig" (-21) drücken. Das ist designtechnisch akzeptabel — Bedrohungen hinterlassen Spuren — aber der Spieler braucht klares UI-Feedback welche Events ausgelöst wurden.

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

11 Aufgabentypen (Pool). Pro Run werden 3 gezogen — mehr Varianz reduziert Wiederholungsgefühl. Alle Aufgaben können ohne Militär erfüllt werden (Kampf bleibt optional). Jede Aufgabe passt zu vorhandenen Spielmechaniken.

| # | Aufgabe | Kernmechanik | Spielstil |
|---|---------|-------------|-----------|
| 1 | **Handelsnetz** | X Handelsrouten aktiv + Gesamtvolumen Y Credits/Sol uber Z Sole aufrecht halten | Wirtschaft |
| 2 | **Forschungsvorsprung** | Mindestens 3 Forschungen auf Level 5+ bringen | Forschung/Aufbau |
| 3 | **Kolonieblute** | Vertrauen > 70 fur 10 aufeinanderfolgende Sole | Diplomatie/Zivilaufbau |
| 4 | **Selbstversorgung** | Beide Grundressourcen (Werkstoffe, Organika) positiv produzieren ohne Import + Supply > 0, fur 15 Sole | Wirtschaft/Aufbau |
| 5 | **Expeditionsstatus** | Alle Tiles der Exploration Zone vollständig aufgedeckt (gesamter äußerer Bereich, nicht nur Ring 1–2) | Exploration/Navigation |
| 6 | **Bewährungsprobe** | Mindestens 3 Encounters erfolgreich abgewehrt (`encounter_won`) mit eigener Flotte | Navigation/Konflikt |
| 7 | **Handelspartner** | Mindestens X Transaktionen mit dem Reisenden Händler abgeschlossen + Credits-Saldo danach stets positiv | Wirtschaft |
| 8 | **Ingenieursleistung** | Gesamt-SP-Kapazität aller Gebäude (Summe `max_status_points` aller colony_buildings) uber Schwelle Y | Aufbau/Optimierung |
| 9 | **Kreditimperium** | Credits-Bestand X Sole uber Schwelle Y halten (kein einmaliger Peak, sondern anhaltender Wohlstand) | Wirtschaft |
| 10 | **Expertenstab** | Alle 5 Berater-Slots besetzt + mindestens 2 Berater auf Rang Senior oder höher | Aufbau/Personal |
| 11 | **Effizienzsprung** | AP-Nutzungsrate >= 90% fur 5 aufeinanderfolgende Sole (verbrauchte AP / produzierte AP) | Optimierung/Hardcore |

> ⚠️ BALANCE CONCERN: Aufgaben 1, 7, 9 (alle Wirtschaft) dürfen nicht alle drei gleichzeitig gezogen werden. Aufgaben-Sets müssen mindestens 2 verschiedene Spielstilkategorien abdecken — eine Kombo-Blacklist ist vor der Implementierung zu definieren.

> ⚠️ BALANCE CONCERN: Aufgabe 6 (Bewährungsprobe) ist teilweise RNG-abhängig — Encounters müssen auftreten. Sicherstellen dass Encounter-Frequenz im Solo-Run hoch genug ist, oder einen alternativen Erfüllungsweg (aktiv `attack`-Order) ermöglichen.

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

| Rang | Bedingung | Hinweistext | Ziel-Link |
|------|-----------|-------------|-----------|
| 1 | Kein Wohnhabitat vorhanden (Supply-Cap = 10) | "Kein Wohnhabitat gebaut — Supply-Cap bleibt bei 10." | Colony-Screen, Bauen-Aktion |
| 2 | Kein Ingenieur-Berater aktiv (0 Construction-AP-Bonus) | "Noch kein Ingenieur eingestellt — Construction-AP bleibt bei Grundwert." | Berater-Screen |
| 3 | Harvester steht auf keinem Regolith-Tile | "Harvester produziert nichts — auf Regolith-Tile verlegen." | Colony-Screen, Harvester-Tile |
| 4 | Kein Wissen freigeschaltet nach Sol 10 | "Noch keine Kenntnis erforscht — Analytik-Labor baut AP auf." | Techtree-Screen, Kenntnisse |
| 5 | Vertrauen unter -20 für >= 3 Sole | "Vertrauen sinkt — Zivilgebäude bauen oder reparieren." | Techtree-Screen, Gebäude |

**Deaktivierung:** Das Hint-System kann in den Einstellungen dauerhaft abgeschaltet werden (`onboarding_hints = false` in User-Preferences). Default: aktiviert. Schließen (`[×]`) eines Hinweises deaktiviert nur diesen spezifischen Hinweistyp bis zum Ende des Runs.

> **Designentscheidung:** Das System prüft Zustände, keine Sequenzen. Es gibt keine "abgehakten Tutorial-Schritte" — nur eine kontinuierliche Zustandsauswertung. Das ist wartungsarm und funktioniert ohne State-Maschine.

> **Designentscheidung:** Nur ein Hinweis gleichzeitig, nie eine Liste. Eine Liste erzeugt denselben Paralyseeffekt wie keine Hinweise. Der Spieler braucht eine klare Richtung, keine Aufgabenübersicht.

> ⚠️ BALANCE CONCERN: Rang 4 (Kenntnis nach Sol 10) setzt voraus, dass das Analytik-Labor (CC Lv2) bis dahin baubar ist. Bei CC-Ausbau-Tempo sollte geprüft werden ob Sol 10 realistisch ist oder ob der Schwellwert auf Sol 15–20 angepasst werden muss.

---

### § 16.3 — Visuelles Hervorheben: "Pulse"-Indikator

**Mechanik:** Wenn eine Techtree-Kachel oder ein Tile auf der Koloniekarte den ersten empfohlenen nächsten Schritt darstellt, erhält sie einen **Pulse-Indikator** — eine dezente, langsam pulsierende SVG-Umrandung (CSS animation `ring-pulse`, 2s Periode, ein Atemzug-Rhythmus, nicht aufdringlich).

**Trigger:** Der Pulse-Indikator wird ausschließlich durch denselben Zustandscheck wie das Hint-System gesteuert. Er zeigt auf genau die Kachel oder den Tile, auf den der aktive Hinweis verweist. Kein Pulse ohne zugehörigen Hint.

**Konkrete Darstellung (Phase 3e):**

| Hint-Rang | Pulsierendes Element |
|-----------|----------------------|
| 1 (kein Wohnhabitat) | Wohnhabitat-Kachel im Techtree, und freie Terrain-Tiles auf der Koloniekarte |
| 2 (kein Ingenieur) | Ingenieur-Slot im Berater-Screen |
| 3 (Harvester falsch) | Harvester-Tile auf der Koloniekarte |
| 4 (kein Wissen) | Analytik-Labor-Kachel im Techtree |
| 5 (Vertrauen < -20) | Erste verfügbare positive Vertrauensgebäude-Kachel |

**Deaktivierung:** Zusammen mit dem Hint-System (gleiche Einstellung).

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

Der Startzustand (CC Lv1, Harvester Lv1, 3.000 Cr, 200 Rg) erzwingt einen natürlichen Pfad, wenn der Spieler dem Hint-System folgt. Der Pfad ist nicht zwingend — aber er ist der offensichtlich sinnvolle:

**Aktion 1 — Wohnhabitat bauen (Colony-Screen)**

- Warum: Supply-Cap von 10 ist ohne Wohnhabitat zu niedrig für Berater + Schiffe
- Kosten: Credits + Regolith (CC Lv1 verfügbar, kein Construction-AP nötig wenn Dev-Mode off)
- Ergebnis: Supply-Cap springt auf 18. Visuell: Kolonie-Zone-Tile ist jetzt bebaut.
- Feedback-Loop klar: Ressourcenleiste aktualisiert sich sofort (Alpine.js live-update)

**Aktion 2 — Ingenieur-Berater einstellen (Berater-Screen)**

- Warum: +6 Construction-AP/Sol durch Junior-Ingenieur verdoppelt den Grundwert
- Kosten: 50 Cr (Junior — erster Berater ist bewusst günstig)
- Ergebnis: Construction-AP-Anzeige springt von 6 auf 12. Berater-Card zeigt "Junior Ingenieur — aktiv"
- Feedback-Loop klar: AP-Chips auf allen Screens aktualisieren sich sofort

**Aktion 3 — CC ausbauen (Techtree-Screen → CC-Kachel)**

- Warum: CC Lv2 schaltet Wissenschaftler-Slot frei; 2 weitere Kolonie-Zone-Tiles
- Kosten: Construction-AP (erster Sol mit Ingenieur macht das spürbar) + Credits
- Ergebnis: Neue Tiles leuchten auf der Karte auf. Wissenschaftler-Slot in Berater-UI erscheint.
- Feedback-Loop klar: Koloniekarte aktualisiert sich live (Ring-Expansion § 4a)

**Aktion 4 — Exploration: erstes Tile erkunden (Colony-Screen)**

- Warum: Regolith-Vorräte sind sichtbar nach Exploration; Event-Tiles können etwas enthalten
- Kosten: 1 Navigation-AP (Grundwert von 6 — kein Raumfahrer nötig)
- Ergebnis: Ein Exploration-Zone-Tile wird aufgedeckt. Typ sichtbar (Regolith/Terrain/Event).
- Feedback-Loop klar: Tile-Animation beim Aufdecken (Fog of War lüftet)

**Aktion 5 — Zweiten Berater einstellen oder erste Kenntnis erforschen**

- Warum: Ab hier verzweigen sich sinnvolle Strategien — Aufbau, Exploration, Handel
- An diesem Punkt ist das Onboarding erledigt: Der Spieler hat die Kernsysteme berührt

**Kein erzwungener Sequenz-Abschluss.** Der Spieler kann jederzeit von diesem Pfad abweichen. Die Hints verschwinden, wenn die jeweilige Bedingung nicht mehr zutrifft.

> ⚠️ BALANCE CONCERN: Aktion 2 (Ingenieur-Berater, 50 Cr) muss nach dem ersten Playtest daraufhin geprüft werden, ob 50 Cr nach dem Wohnhabitat-Bau noch sicher verfügbar sind. Wenn der Wohnhabitat-Bau mehr als ~2.800 Cr kostet, ist der Junior-Ingenieur knapp. Einstellungskosten sind in `config/game.php → advisors` konfigurierbar.

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
| Harvester-Verlagerung | Erster Klick auf "Verlegen"-Aktion | Tooltip: "Harvester verlegen kostet 1 Construction-AP und ist sofort wirksam — ohne Downtime." |

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

**Konfiguration:** `config/game.php → onboarding`:

```php
'onboarding' => [
    'hint_supply_cap_threshold'    => 10,   // Hint Rang 1: Supply-Cap <= dieser Wert
    'hint_no_engineer_ticks'       => 0,    // Hint Rang 2: Sole ohne Ingenieur (0 = sofort)
    'hint_no_knowledge_after_tick' => 10,   // Hint Rang 4: Warnung nach diesem Sol
    'hint_trust_threshold'         => -20,  // Hint Rang 5: Vertrauen unter diesem Wert
    'hint_trust_min_ticks'         => 3,    // Hint Rang 5: mindestens N Sole ununterbrochen
],
```

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
