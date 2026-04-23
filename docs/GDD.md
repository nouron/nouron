# Nouron — Game Design Document (GDD)

**Projekt:** Nouron — A Free Space Opera Browsergame
**Status:** aktiv (Stand: 2026)
**Verantwortlich:** Mario Gehnke

---

## Inhaltsverzeichnis

1. [Spielkonzept](#1-spielkonzept)
   - 1.1 [Designprinzipien](#11-designprinzipien)
   - 1.2 [Alleinstellungsmerkmale (USPs)](#12-alleinstellungsmerkmale-usps)
2. [Tick-System](#2-tick-system)
3. [Ressourcen](#3-ressourcen)
4. [Kolonien & Gebäude](#4-kolonien--gebäude)
   - 4a. [Kolonieoberfläche](#4a-kolonieoberfläche)
5. [Ressourcenproduktion](#5-ressourcenproduktion)
6. [Supply-Generierung](#6-supply-generierung)
7. [Gebäude-Verfall (Decay)](#7-gebäude-verfall-decay)
8. [Flotten & Flottenorders](#8-flotten--flottenorders)
   - 8a. [Systemansicht](#8a-systemansicht)
9. [Kampfsystem (Combat)](#9-kampfsystem-combat)
10. [Forschung](#10-forschung)
11. [Handel (Trade)](#11-handel-trade)
12. [Berater & Aktionspunkte (AP-System)](#12-berater--aktionspunkte-ap-system)
13. [Moralsystem](#13-moralsystem)
14. [Run-Struktur (Roguelike-Modus)](#14-run-struktur-roguelike-modus)

---

## 1. Spielkonzept

Nouron ist ein rundenbasiertes Weltraum-Strategiespiel für Einzelspieler im Browser. Der Spieler übernimmt die Rolle eines Koloniekommandanten mit einem klaren Auftrag: eine kleine, ressourcenarme Kolonie auf Vordermann zu bringen — entweder eine frisch gestartete Siedlung oder eine heruntergekommene Anlage, die sich selbst überlassen wurde.

Die Kolonie bleibt im gesamten Spielverlauf überschaubar. Es geht nicht darum, ein galaktisches Imperium aufzubauen, sondern darum, eine kleine Gemeinschaft unter schwierigen Bedingungen am Leben zu erhalten und gedeihen zu lassen.

Das Spiel ist in **Runs** strukturiert: Jeder Run hat ein konkretes Ziel, einen variablen Verlauf und ein klares Ende — Erfolg oder Scheitern. Nouron enthält **Roguelike-Elemente**: variable Aufgaben je Run, zufällige Ereignisse und echte Konsequenzen für Fehlentscheidungen. Runs können wiederholt werden; jeder Run fühlt sich anders an.

Das Spiel läuft servergesteuert auf Basis eines Tick-Systems: alle Spielzustandsänderungen werden einmal pro Tag global berechnet.

---

## 1.1 Designprinzipien

### Militarismus ist teuer — bewusst

**Kernprinzip: Militarische Aktionen kosten immer mehr AP als nicht-militarische.**

Dies ist kein technisches Detail, sondern eine fundamentale Designentscheidung. Nouron ist kein Kriegsspiel. Die Kernphantasie ist der Aufbau eines florierenden Imperiums durch Handel, Infrastruktur und Forschung. Militarische Macht ist ein Mittel zur Absicherung, nicht das Ziel.

### Warum dieses Prinzip

Ohne Kostenasymmetrie dominiert in Strategie-Browserspielen erfahrungsgemass eine einzelne optimale Strategie: maximale Militarisierung, da Angriff die billigste Form der Ressourcengewinnung ist. Das zerstoert den Spielspass fur alle, die eine andere Spielweise bevorzugen.

Indem militarische Aktionen strukturell teurer sind, wird Militarismus nicht verboten, aber er hat einen realen Opportunitatskostenfaktor. Ein Spieler, der standig angreift, hat weniger AP fur Aufbau, Forschung und Handel — und wachst dadurch langsamer als ein Spieler, der sich auf friedliche Entwicklung konzentriert.

### Umsetzung im AP-System

Navigation-AP werden durch **Piloten** generiert und decken alle Flottenorders ab — zivile wie militarische. Die Differenzierung erfolgt ausschliesslich uber die AP-Kosten je Order-Typ:

| Order-Typ | Navigation-AP-Kosten |
|-----------|----------------------|
| move (Bewegung) | 1 |
| hold (Halten) | 1 |
| trade (Handel) | 1 |
| join (Anschließen) | 1 |
| convoy (Eskorte) | 1 |
| defend (Verteidigen) | 2 |
| attack (Angriff) | 3 |

Ein Pilot, der 15 AP pro Tick generiert, kann also entweder:
- 15 Bewegungs- oder Handels-Orders erteilen, oder
- 5 Angriffs-Orders

Die zivile Variante erzeugt dreimal so viele Aktionen wie die militarische bei gleicher AP-Basis.

### Geltungsbereich: spielweites Prinzip

Diese Asymmetrie gilt nicht nur fur Flottenorders. Sie muss in allen zukunftigen Mechaniken, die AP-Kosten oder andere Kosten haben, konsequent angewendet werden:

- **Diplomatie / Vertrage**: Angriffs- oder Sanktionsvertrage kosten mehr als Handels- oder Beistandsvertrage.
- **Politiksystem**: Kriegserklarungen und Embargos verbrauchen mehr politische AP als Allianzen oder Handelsabkommen.
- **Spionage / Geheimdienstoperationen**: Sabotageaktionen kosten mehr als Informationssammlung.
- **Neue Schiffstypen**: Kampfschiffe sind teurer in Bau-AP als zivile Schiffe vergleichbarer Grosse.

> Jede neue Spielmechanik muss beim Design gepruft werden: Ist die militarische Variante teurer als die zivile? Wenn nicht, ist die Mechanik nicht balanciert im Sinne der Nouron-Vision.

### Abgrenzung

Das Prinzip bedeutet nicht, dass Militarismus unmoglich oder unrentabel ist. Ein hochspezialisierter Militarspieler, der alle Piloten auf maximales Level bringt, kann trotzdem erhebliche Kampfkapazitat aufbauen. Die Kostenadditionalitat stellt sicher, dass diese Spezialisierung eine echte Wahl ist — mit echten Opportunitatskosten — und nicht die einzig sinnvolle Strategie.

---

## 1.2 Alleinstellungsmerkmale (USPs)

Nouron teilt sich das Genre "Browser-Strategiespiel" mit Dutzenden von Titeln. Was Nouron von ihnen unterscheidet, ist kein einzelnes Feature, sondern ein kohärentes Designprinzip: das Spiel ist für Spieler gebaut, die lieber nachdenken als klicken — und die Konsequenzen ihres Handelns über Tage spüren wollen.

### Die sechs Merkmale

**1. Verfall als durchgängiges Systemprinzip**
Gebäude und Kenntnisse verfallen ohne aktive Pflege. Wer seine Kolonie vernachlässigt, verliert sie langsam — nicht durch Gegner, sondern durch Entropie. Der Verfall zwingt zur Priorisierung und macht jeden Tick zu einer echten Ressourcenentscheidung.

**2. Tick-basiertes Spieltempo (1 Tick = 1 Tag)**
Keine Echtzeit-Hektik. Entscheidungen werden einmal täglich getroffen und einmal täglich ausgeführt. Das Spiel passt sich dem Spieler an, nicht umgekehrt.

**3. Nur eine Kolonie — Tiefe statt Breite**
Kein Ausbreiten über eine halbe Galaxie, kein Micromanagement von zehn Außenposten. Eine Kolonie, ein Kommandant — alle Entscheidungen betreffen denselben Ort und dieselbe Gemeinschaft.

**4. Roguelike-Elemente im Strategieformat**
Jeder Run hat variable Aufgaben, zufällige Ereignisse und echte Konsequenzen. Das Scheitern ist möglich und lehrreich. Kein Run ist identisch — aber die Kolonie bleibt immer dieselbe Art von Ort.

**5. Kleine, handverlesene Galaxie**
Wenige Systeme, wenige Objekte. Jede Begegnung mit einer anderen Fraktion oder einem Ereignis hat Gewicht. Die Knappheit des Raums ist Teil des Designs.

**6. Militär als Opportunitätskosten-Entscheidung**
Verteidigung ist möglich und sinnvoll — aber Angriff kostet strukturell mehr AP als zivile Aktionen. Wer ständig kämpft, baut und wächst langsamer. (Ausführlich in §1.1.)

### Der Zusammenhang

Diese Merkmale folgen demselben Grundgedanken: Nouron belohnt Spieler, die ihren Fokus bewusst setzen und mit begrenzten Mitteln das Beste herausholen. Das Vorbild ist nicht das klassische Imperium-4X, sondern das Frontier-Szenario — eine kleine Kolonie, ein begrenzter Horizont, echte Entscheidungen.

> Ein Spieler der acht Stunden täglich spielen will, hat keinen Vorteil gegenüber einem Spieler, der täglich fünf Minuten investiert — aber seine Entscheidungen sorgfältig trifft.

### Vorbilder

- **Reunion** (1994) — stärkster Einfluss: Ein Spieler, ein Heimatplanet, maximale Tiefe statt Breite. Die Cantina, der Alltag der Kolonie, das Gefühl von Ort.
- **FTL: Faster Than Light** (2012) — Vorbild für die Run-Struktur: variables Ziel, zufällige Ereignisse, echtes Scheitern als Teil des Spiels.
- **Catan** (1995) — Vorbild für das Ressourcenmanagement mit Knappheit: jede Ressource hat Gewicht, Tausch ist eine Kernmechanik.
- **Master of Orion** (1993) — Vorbild für das Mini-4X-Grundgerüst und die Kommandopunkte-Mechanik.

---

## 2. Tick-System

### Grundprinzip

Ein **Tick** ist die atomare Zeiteinheit des Spiels. Alle periodischen Spielmechaniken (Ressourcenproduktion, Verfall, Flottenorders) werden einmal pro Tick ausgeführt.

**Alle Spielwerte sind in Ticks ausgedrückt** — nicht in Echtzeit-Stunden oder -Tagen. Die Echtzeit-Dauer eines Ticks ist konfigurierbar (`config/game.php → tick.length`, aktuell 24 Stunden). Damit skalieren alle Spielmechaniken automatisch:

| tick.length | 1 Tick entspricht | 100 Ticks ≈ |
|-------------|------------------|-------------|
| 24 h (Standard) | 1 Tag | 3,3 Monate |
| 1 h | 1 Stunde | 4 Tage |

> **Designentscheidung offen:** 1 Tick/Tag (aktuell) oder 24 Ticks/Tag werden evaluiert. Die Wahl ändert nur `tick.length` — alle Balancing-Werte bleiben in Ticks und skalieren automatisch.

### Zeitberechnung

Die Tick-Nummer ergibt sich aus:

```
tick = floor((unix_timestamp - 4h) / 86400)
```

Dies entspricht der Anzahl vergangener Tage seit der Unix-Epoch, verschoben um 4 Stunden (damit der Tagesübergang um Mitternacht nicht mit dem Berechnungsfenster kollidiert).

### Berechnungsfenster

Der Tick wird täglich automatisch zwischen **03:00 und 04:00 Uhr Serverzeit** berechnet (konfigurierbar in `config/game.php → tick.calculation`).

### Manueller Aufruf

```bash
php artisan game:tick           # berechnet den aktuellen Tick
php artisan game:tick --tick=N  # erzwingt Tick-Nummer N (z. B. für Tests)
```

### Implementierung

- Artisan-Command: `app/Console/Commands/GameTick.php`
- Tick-Berechnung: `app/Services/TickService.php`
- Konfiguration: `config/game.php → tick`
- Alle Schritte eines Ticks laufen in einer einzigen DB-Transaktion (atomar)

### Reihenfolge der Tick-Schritte

| Schritt | Beschreibung |
|---------|-------------|
| 1 | Fleet Move Orders — Flotten bewegen sich zu Zielkoordinaten |
| 2 | Fleet Trade Orders — Ressourcentransfer Flotte ↔ Kolonie |
| 3 | Fleet Combat Orders — Kampfauflösung, Verluste werden berechnet |
| 4 | Building Decay — Gebäude verlieren `decay_rate` SP; Level-Down bei SP ≤ 0 |
| 5 | Ship Decay — Schiffe verlieren SP (×2 im Kampftick); Eintrag gelöscht bei SP ≤ 0 |
| 6 | Research Decay — Forschungen verlieren SP; Level-Down bei SP ≤ 0 |
| 7 | Supply Cap — `user_resources.supply` neu berechnen und setzen (Formel: siehe §6) |
| 8 | Resource Generation — Rohstoffproduktion pro Kolonie und Produktionsgebäude |
| 8b | Vertrauen Calculation — Vertrauen neu berechnen, `colony_resources` (res_id=12) aktualisieren (siehe §13) |
| 9 | Advisor Ticks — `active_ticks` erhöhen, Rang-Aufstieg prüfen |

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
- **Vertrauen** — Systemmechanik, kein handelbarer Rohstoff (siehe §13).

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
- **Händlerkonvoi in der Nähe** — befristetes Kaufangebot (2 Ticks), günstiger als KI-Marktpreis
- **Trümmerfeld im System** — Flotte entsenden, Werkstoffe heimholen

### Credits-Einnahmen

Credits werden durch vier Quellen erworben:

| Quelle | Beschreibung |
|--------|-------------|
| Kolonistensteuern | Automatische Abgaben pro Tick — abhängig von der Koloniegröße (Wohnhabitat-Anzahl) |
| Galaktischer Rat | Staatliche Subventionen für aktive Kolonien pro Tick (Arbeitstitel: Name noch offen) |
| Handel | Einnahmen aus Handelsrouten beim Verkauf von Regolith / Organika / Werkstoffen |
| Events | Einmalige Gutschriften durch zufällige Ereignisse |

Ausgaben: Berater-Upkeep (§12), Gebäudebaukosten, Schiffsbaukosten, Werkstoffe-Import (KI-Händler).

### Zukünftiger Rohstoff (Phase 4+): Exotics

Ein vierter handelbarer Rohstoff ist für spätere Phasen reserviert: **Exotics** (Arbeitstitel) — seltene Materialien die auf der Heimatkolonie nicht abgebaut werden können. Quellen: Exploration anderer Systeme via Flotte, oder Handel mit anderen Spielern/Fraktionen. Gibt der interstellaren Bewegung einen konkreten wirtschaftlichen Zweck.

### Abgekündigte Ressourcen (werden in Phase 3 entfernt)

- Wasser (ID 3) — wird durch Versorgung (Supply) abstrahiert; kein eigenständiges Rohstoff-Modell nötig.
- ENrg (ID 6), LNrg (ID 8), ANrg (ID 10) — rassenspezifische Energieressourcen aus dem alten Konzept. Rassen wurden abgekündigt; Supply übernimmt die Energieversorgungsrolle konzeptionell.

---

## 4. Kolonien & Gebäude

### Gebäude (Phase 3 — vollständige Liste)

12 Gebäude, reduziert auf das Mini-4X-Kernsortiment:

| ID | Config-Key | Name (DE) | Name (EN) | Max-Level | Voraussetzung |
|----|------------|-----------|-----------|-----------|---------------|
| 25 | commandCenter | Kommandozentrale | Command Center | 5 | — |
| 28 | housingComplex | Wohnhabitat | Residential Habitat | 6 | CC Lv1 |
| 27 | harvester | Harvester | Harvester | 1 | CC Lv1 |
| 41 | bioFacility | Agrardom | Agrarian Dome | — | CC Lv1 |
| 30 | depot | Lagerhalle | Warehouse | — | CC Lv1 |
| 31 | sciencelab | Analytik-Labor | Analytics Lab | — | CC Lv4 |
| 43 | tradecenter | Handelsposten | Trading Post | — | CC Lv5 |
| 44 | hangar | Hangar | Hangar | — | CC Lv2 |
| 46 | hospital | Krankenstation | Medical Station | — | CC Lv2 |
| 52 | bar | Cantina | Cantina | — | CC Lv1 |
| 50 | denkmal | Kolonialdenkmal | Colonial Monument | — | — |
| 32 | temple | Religiöse Stätte | Sacred Site | — | — |

> **Harvester (Sondergebäude):** Der Harvester unterscheidet sich von allen anderen Gebäuden: Er steht nicht in der Kolonie-Zone, sondern auf einem Ressourcen-Tile in der Exploration Zone. Er produziert passiv je nach Tile-Typ (Regolith oder andere Mineralien). Er kann verlegt werden (Aktion: 1 Construction-AP, keine Downtime). Es gibt genau einen Harvester pro Kolonie. Technisch ist er ein Gebäude mit einer `tile_x/tile_y`-Position statt eines Kolonie-Slots.

### Status-Punkte

Jedes Koloniegebäude hat ein `status_points`-Feld. Das Maximum (`max_status_points`) ist in der `buildings`-Tabelle hinterlegt. Status-Punkte sinken pro Tick durch Verfall (siehe Abschnitt 7).

**Leveled vs. Instanced Buildings:**

- **Leveled** — ein Objekt auf einem Tile, wird stufenweise ausgebaut (z.B. CC Lv1→5, Harvester, Agrardom). Ein Klick auf das Tile → "Ausbauen".
- **Instanced** — jede Einheit ist ein eigenes Objekt auf einem eigenen Tile (z.B. Wohnhabitat max. 6 Einheiten, Hangar). Jede Instanz kann separat auf Lv1–3 ausgebaut werden und hat eigene Status-Points.

Das Config-Flag `is_instanced` in `config/buildings.php` steuert das Verhalten. In der DB haben Instanced Buildings eine `instance_id` als Teil des zusammengesetzten PK (`colony_id + building_id + instance_id`).

Das UI-Verb ist immer identisch: **"Tile ausbauen"** — ob Leveled oder Instanced darunterliegt, ist ein Implementierungsdetail das der Spieler nicht sieht. "Neues Wohnhabitat bauen" bedeutet: neues Tile mit Instanz Lv1 belegen. "Wohnhabitat ausbauen" bedeutet: bestehende Instanz von Lv1 auf Lv2 heben.

---

## 4a. Kolonieoberfläche

### Darstellung: Hex-Grid

Die Kolonieoberfläche wird als 2D top-down Hex-Grid dargestellt. Die Planetengröße bestimmt die Anzahl verfügbarer Ringe und skaliert mit dem Schwierigkeitsgrad des Runs.

### Zwei Zonen

**Kolonie-Zone** — die inneren Ringe um das CC. Hier werden Gebäude gebaut. Neue Ringe werden durch CC-Level freigeschaltet.

**Exploration Zone** — die äußeren Tiles jenseits der Kolonie-Zone. Hier liegen Ressourcenquellen, Gefahren und Event-Spots. Jedes Tile muss einzeln per Navigation-AP erkundet werden (Korvette oder Sonde).

### CC-Level und Koloniewachstum

Die Kommandozentrale schaltet durch Level-Upgrades neue Hex-Ringe in der Kolonie-Zone frei. Tiles innerhalb eines freigeschalteten Rings werden einzeln durch Erkundungs-AP enthüllt — nicht alle auf einmal.

| Planetengröße | Kolonie-Ringe gesamt | Neuer Ring bei CC-Level |
|---|---|---|
| Klein | 2 | 1 / 3 |
| Mittel | 3 | 1 / 2 / 4 |
| Groß | 4 | 1 / 2 / 3 / 5 |

Ring 1 (6 Tiles direkt um das CC) ist immer sofort verfügbar. Der erste Ressourcen-Tile ist garantiert in Ring 1 (fixes Starttemplate, Typ variiert pro Run).

### Startposition

Die CC-Startposition ist pro Run zufällig. Das erzeugt unterschiedliche Ausgangssituationen und trägt zum Roguelike-Charakter bei.

### Sichtbarkeit

- **Kolonie-Zone:** alle Tiles sofort sichtbar
- **Exploration Zone:** Fog of War — Tiles werden einzeln per Navigation-AP aufgedeckt

### Tile-Typen und Schwierigkeit

Tile-Typen (z.B. "Reicher Erzknoten", "Armes Vorkommen", "Organik-freies Terrain") beeinflussen die Ressourcenproduktion. Die Schwierigkeit eines Runs steuert die Tile-Qualität: schwieriger Run = schlechtere Vorkommen, keine reichen Erznodes in Ring 1.

### Organika

Organika entsteht nicht auf Tiles (biologische Materialien kommen auf Planeten nicht natürlich vor). Stattdessen produziert der **Agrardom** (Gebäude innerhalb der Kolonie-Zone) Organika passiv pro Tick.

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
| `ring` | integer | 0 = CC-Tile, 1–4 = Ring-Nummer |
| `tile_type` | string | Primärer Typ, z.B. `regolith_rich` — sichtbar nach normalem Scan |
| `event_type` | string nullable | Event-Overlay, NULL = kein Event — sichtbar erst nach Tiefenscan |
| `is_ring_unlocked` | boolean | CC-Level hat diesen Ring freigeschaltet |
| `is_explored` | boolean | Normaler Scan (Nav-AP) abgeschlossen |
| `is_deep_scanned` | boolean | Tiefenscan abgeschlossen — enthüllt `event_type` |
| `resource_amount` | integer nullable | Verbleibende Ressourcenmenge |
| `resource_max` | integer nullable | Startwert (Basis für Erschöpfungs-Counter im UI) |

---

## 5. Ressourcenproduktion

### Mechanik

Einmal pro Tick produziert jedes aktive Produktionsgebäude in jeder Kolonie Rohstoffe. Die produzierte Menge ergibt sich aus:

```
produzierte Menge = Gebäude-Level × Rate
```

### Produktionsgebäude (Phase 3)

| Gebäude | building_id | Ressource | resource_id | Rate |
|---------|-------------|-----------|-------------|------|
| Harvester | 27 | Regolith | 3 | tile-abhängig |
| Agrardom | 41 | Organika | 5 | 10 pro Level |

> **Designentscheidung:** Der Harvester produziert Regolith (lokaler Rohstoff), nicht Werkstoffe. Werkstoffe sind veredelte Industriegüter die nicht vor Ort herstellbar sind — sie kommen ausschließlich über Handel, KI-Händler und Events (§3).

> **Harvester-Produktion:** Die produzierte Menge hängt vom Tile-Typ ab (z.B. "Reicher Erzknoten" = +50% Bonus). Quellen versiegen graduell — ein sichtbarer Erschöpfungs-Counter auf dem Tile zeigt den verbleibenden Vorrat.

### Konfiguration

`config/game.php → production`:

```php
'production' => [
    27 => [3 => 'tile'],   // harvester      → Regolith  tile-abhängig
    41 => [5 => 10],       // bioFacility    → Organika  × 10/level
],
```

Neue Produktionsgebäude können ohne Code-Änderung ausschließlich durch Erweiterung dieser Config hinzugefügt werden.

---

## 6. Supply-System (Cap-Modell)

### Modell

Supply ist **kein fliessender Pool**, sondern ein **Kapazitätsdeckel** (Cap-Modell). Gebäude und Kenntnisse erhöhen den Cap. Schiffe und Gebäude (außer CC und Wohnkomplex) belegen Supply dauerhaft. Berater belegen **kein** Supply — sie kosten Credits. Es gibt keine Tick-basierte Supply-Generierung.

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

> **Designabsicht:** CC-Ausbau und Wohnhabitate sind die primären Cap-Quellen. Kenntnisse liefern einen zusätzlichen Bonus, der den Cap in Richtung 200 schiebt — aber nie alleine reicht. Wer militärisch eskalieren will, muss zuerst zivile Infrastruktur investieren.

### Supply-Kosten der Schiffstypen

Korvetten sind bewusst teurer als Frachter (Kernprinzip: Militär kostet mehr, siehe §1.1). Sonden kosten kein Supply — sie sind unbemannt. Die Flottengröße wird organisch durch den Supply-Cap begrenzt; es gibt keinen harten Schiffscount-Cap.

| Schiff | ship_id | Supply (Unterhalt) | Bemerkung |
|--------|---------|-------------------|-----------|
| sonde | 85 | **0** | unbemannt, kein Hangar nötig |
| korvette | 37 | **14** | benötigt Hangar |
| frachter | 47 | **6** | benötigt Hangar |

**Beispielrechnung:** 2 Korvetten + 2 Frachter = 28 + 12 = 40 Supply — bereits mehr als die Hälfte eines typischen Mid-Game-Caps.

**Schiffe verfallen nicht.** Sie sind entweder intakt oder zerstört (Kampf, Umgebungsgefahren). Wartungsdruck entsteht durch den Hangar-Decay, nicht durch Ship-Status-Points.

> **TODO (Design, Phase 4+):** Sonderfall "Schiffe ohne Hangar" — durch Events, Handelsdeals oder andere Mechaniken könnte der Spieler Schiffe erwerben, die normalerweise nicht im Hangar baubar sind (z.B. erbeutete Fraktionsschiffe, Belohnungsschiffe aus Events). Diese wären per Run einzigartig und ein Roguelike-Element das jeden Durchlauf anders macht. Mechanik (Hangar-Pflicht? Supply-Kosten?) und Balance noch offen — für spätere Phase detailliert ausarbeiten.

### Supply-Kosten Gebäude

**Berater:** kein Supply-Verbrauch — Kosten laufen über Credits (siehe §12).

**CommandCenter und Wohnhabitat:** kein Supply-Verbrauch (sie definieren den Cap).

**Gebäude** (individuelle Supply-Kosten aus Technologie-Tabelle):

| Gebäude | Supply |
|---------|--------|
| Harvester, Agrardom | 2 |
| Kolonialdenkmal | 2 |
| Lagerhalle | 3 |
| Cantina, Religiöse Stätte | 4 (je) |
| Handelsposten | 7 |
| Analytik-Labor | 8 |
| Krankenstation | 10 |
| Hangar | 12 (je Instanz) |

> Supply-Kosten sind **tick-rate-unabhängig** — sie beschreiben eine permanente Kapazitäts-Belegung, keine Fluss-Größe.

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

### Decay für Schiffe und Forschungen

Schiffe und Forschungen haben — analog zu Gebäuden — `status_points` die über Zeit abnehmen.

| Entität | Decay-Rate | Besonderheit |
|---------|-----------|--------------|
| Gebäude | 1 SP/Tick | bereits implementiert; inkl. Hangar |
| Schiffe | — | kein Decay; Verlust nur durch Kampf oder Umgebungsgefahren |
| Forschungen | sehr langsam (TBD) | kein Verlust durch Inaktivität, nur Verfall |

> Konkrete Werte (SP/Tick, Gnadenfrist) werden bei Implementierung in `config/game.php → decay` festgelegt.

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
        85 => 0,   // sonde    — unbemannt
        37 => 14,  // korvette
        47 => 6,   // frachter
    ],
],
```

### Supply im Tick (Schritt 7)

`user_resources.supply` speichert den **aktuellen Supply-Cap**. Er wird in Schritt 7 jedes Ticks neu berechnet und gesetzt — so spiegelt der Wert immer den aktuellen Gebäudestand wider (z. B. nach einem Level-Down des Wohnkomplexes durch Decay).

Das freie Supply (für Enforcement-Checks) ergibt sich live: `cap − Σ(entity_level × supply_cost)`.

### Abgrenzung der Unterhalts-Mechanismen

| Mechanismus | Was er begrenzt | Zeithorizont | Gegenmaßnahme |
|-------------|----------------|--------------|---------------|
| Supply-Cap | Anzahl Schiffe + Gebäude | permanent | CC ausbauen, Wohnhabitate bauen, Kenntnisse erforschen |
| AP | Aktionen pro Tag | täglich | mehr/bessere Berater |
| Decay | Stand von Gebäuden und Forschungen | täglich | Reparatur-AP investieren |

Diese drei Mechanismen sind bewusst unabhängig voneinander.

---

## 7. Verfall (Decay) — Gebäude und Forschungen

### Mechanik

Gebäude und Forschungen verfallen ohne aktive Pflege. Schiffe verfallen nicht — sie gehen nur durch Kampf oder Umgebungsgefahren verloren (siehe §6). Jedes Exemplar hat individuelle Werte für `max_status_points` und `decay_rate` (SP/Tick), die in den Stammdaten-Tabellen (`buildings`, `researches`) gespeichert sind.

**Fraktionaler Decay:** Die `decay_rate` ist ein Dezimalwert (0.05–0.3 SP/Tick). Pro Tick wird dieser Wert von den `status_points` des Exemplars abgezogen. Ein ganzer SP geht erst verloren, wenn sich genug Verlust akkumuliert hat.

```
Beispiel: max_status_points=5, decay_rate=0.3
  Nach Tick 1: status_points = 4.70
  Nach Tick 2: status_points = 4.40
  Nach Tick 3: status_points = 4.10
  Nach Tick 4: status_points = 3.80  ← erster ganzer SP verloren
```

**Konsequenzen nach Building-Typ:**

| Entität | Typ | Konsequenz bei SP ≤ 0 |
|---------|-----|----------------------|
| Leveled Building (allgemein) | Leveled | Level − 1; status_points reset auf max_status_points; INNN-Ereignis |
| Wohnhabitat | Instanced | **Instanz zerstört** (kein Level zum Abziehen); Supply-Cap sinkt um 8; INNN-Ereignis |
| Hangar | Instanced | **Instanz zerstört**; zugewiesenes Schiff wird **unbrauchbar** (nicht zerstört); INNN-Ereignis |
| Kenntnis | Leveled | Level − 1; INNN-Ereignis |

> **Instanced vs. Leveled:** Leveled Buildings verlieren ein Level und regenerieren SP — sie geben mehrere Chancen. Instanced Buildings (Wohnhabitat, Hangar) haben kein Level: Decay auf 0 zerstört die Instanz sofort. Das macht sie gefährlicher zu vernachlässigen, erlaubt aber bewusst riskantes Spiel (Repair-AP sparen auf eigene Gefahr).

> **Notreparatur (CC und Wohnhabitat):** Wenn SP dieser kritischen Strukturen unter einen Schwellwert fällt, wird automatisch eine Notreparatur ausgelöst — kostet Credits statt AP. Verhindert unbeabsichtigten Verlust, nicht aber bewusste Vernachlässigung (Credits müssen vorhanden sein).

> **Hangar-Decay-Detail:** Ein Schiff im zerstörten Hangar bleibt in der Datenbank erhalten — es ist nur deaktiviert. Sobald ein neuer Hangar gebaut oder der alte repariert wird, ist das Schiff wieder einsatzbereit.

> **Schiffe verfallen nicht.** Schiffe werden durch Kampf oder Umgebungsgefahren zerstört, nicht durch Decay. Wartungsdruck entsteht indirekt durch den Hangar-Decay.

### Kampf-Beschleunigung (Hangar)

In einem Tick, in dem eine Flotte an Kampfhandlungen beteiligt war, verfällt der zugeordnete **Hangar** mit **Faktor 2** auf die decay_rate:

```
hangar_decay_in_kampftick = decay_rate × 2
```

**Designabsicht:** "Krieg verschleißt die Infrastruktur." Kampf erzeugt direkten Reparaturdruck auf den Hangar — wer offensiv agiert, muss anschließend AP in Wartung investieren. Die Regel ist bewusst einfach gehalten: ein Faktor, ein Gebäudetyp.

### Richtwerte (abgeleitet aus Technologie-Tabelle)

Die Technologie-Tabelle enthält für jede Entität einen "Ticks until lost"-Wert (ohne Wartung). Daraus leitet sich die `decay_rate` ab, wenn `max_status_points` standardisiert wird:

```
decay_rate = max_status_points / ticks_until_lost
```

Mit `max_status_points = 20` als Standard ergeben sich z.B.:

| Entität | Ticks until lost | decay_rate (bei SP=20) |
|---------|-----------------|------------------------|
| Cantina (bar) | 7 | 2.86 |
| Religiöse Stätte (temple) | 10 | 2.0 |
| Krankenstation (hospital) | 10 | 2.0 |
| Harvester, Agrardom | 21 | 0.95 |
| Analytik-Labor (sciencelab) | 21 | 0.95 |
| Lagerhalle (depot) | 30 | 0.67 |
| Handelsposten (tradecenter) | 30 | 0.67 |
| Hangar | 30 | 0.67 |
| Wohnhabitat (housingComplex) | 45 | 0.44 |
| Kommandozentrale (max Lv5), Kolonialdenkmal | 61 | 0.33 |
| Kenntnisse (most) | ~150 | ~0.13 |


> **Tick-Skalierung:** Bei 24 Ticks/Tag entspricht "133 Ticks" ~5,5 Echtzeit-Tagen. Bei 1 Tick/Tag sind es 133 Tage. Die Tick-Anzahl bleibt gleich — nur die Echtzeit-Dauer ändert sich. Das ist die gewünschte Eigenschaft des tick-basierten Systems.

> Konkrete Werte pro Typ per Migration in die Stammdaten-Tabellen (`buildings.decay_rate`, `ships.decay_rate`, `researches.decay_rate`).

**Minimum:** Jede Entität hat mindestens **5 max_status_points**.

> ⚠️ **Gnadenfrist** (kein Decay für neue Schiffe/Gebäude für X Ticks): vorerst nicht implementiert. Kann in einer späteren Phase evaluiert werden.

### Schema (implementiert)

Die folgenden Spalten sind im Schema vorhanden und werden vom Decay-System genutzt:

- `buildings`, `ships`, `researches`: Spalten `max_status_points INTEGER` und `decay_rate REAL` — Werte aus `config/buildings.php`, `config/ships.php`, `config/techs.php`; Sync via `php artisan game:sync-techs`
- `colony_buildings.status_points REAL` — aktueller Zustandswert des Gebäudes
- `fleet_ships.status_points REAL` — aktueller Zustandswert des Schiffes
- `colony_researches.status_points REAL` — aktueller Zustandswert der Forschung

### Konfiguration

`config/game.php → decay`:

```php
'decay' => [
    'combat_factor' => 2,    // Hangar-Decay im Kampftick × 2 (Schiffe verfallen nicht)
],
```

### Designabsicht

Decay erzwingt regelmäßige AP-Investitionen in Wartung. Inaktive Spieler verlieren schrittweise Infrastruktur und Flotte. Die Kombination aus kleiner decay_rate und fraktionaler Akkumulation bedeutet: nichts bricht sofort — aber vernachlässigte Entitäten degradieren stetig.

---

## 8. Flotten & Flottenorders

### Flottenorders

Flottenbewegungen und -aktionen werden als Orders in der `fleet_orders`-Tabelle gespeichert. Jede Order ist einem Tick zugewiesen und wird beim zugehörigen Tick genau einmal verarbeitet (`was_processed = 1` nach Ausführung).

### Navigation-AP-Kosten je Order-Typ

Jede Flottenorder verbraucht Navigation-AP, die durch Piloten generiert werden (siehe Abschnitt 12). Die AP-Kosten unterscheiden sich bewusst je nach Charakter der Aktion — militarische Orders sind teurer (siehe Abschnitt 1.1, Designprinzip "Militarismus ist teuer").

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
- Bewegung geschieht über mehrere Ticks — die Flotte teleportiert sich nicht sofort
- Geschwindigkeit = `moving_speed` des langsamsten Schiffs in der Flotte (Fallback: 1 Einheit/Tick)
- `FleetService::addOrder()` berechnet den Pfad via `GalaxyService::getPath()` und legt für jeden Tick auf dem Weg eine 'move'-Order an; nur die letzte Order trägt den eigentlichen Order-Typ
- Pro Tick des Weges werden Navigation-AP gesperrt (Gesamtkosten = Wegkosten + Order-Kosten)

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
| Tiefenscan | Mehrere Navigation-AP über mehrere Ticks | Verborgener Event-Spot enthüllt (Schiffswrack, Ruine, Versteck) |

### Fixe Objekte (immer vorhanden)

- Stern (1) — immer bei (6,6)
- Heimatplanet + Monde (je Spieler) — prozedural platziert
- Sprungtor (1, narratives Element — nicht nutzbar, kann bewacht werden) — prozedural platziert
- Nexus-Außenposten (1): Basishandel + Verwaltung der Nexus-Schulden — prozedural platziert

### Prozedurale Objekte (variabel pro Run)

Asteroiden, Schiffsfriedhöfe, Event-Tiles — zufällig generiert, tragen zum Roguelike-Charakter bei.

### NPC-Fraktionen

Vereinzelte NPC-Fraktionen sind im System präsent. Das System wirkt unbesiedelt und nach Frontier — Begegnungen sind selten aber bedeutsam.

### Reisender Händler

Ein reisender Händler erscheint gelegentlich im System für eine begrenzte Anzahl Ticks. Er bietet seltene Waren an — keine Standardressourcen, sondern Shortcuts und Chancen die im normalen Spielverlauf nicht erreichbar sind.

**Erscheinungsfrequenz:** Erstmals ab Tick 15–20 (Kolonie soll sich erst etablieren). Danach alle 10–15 Ticks zufällig. Ergibt ~6–7 Besuche pro 100-Tick-Run. Ist der Händler weg, ist er weg — Roguelike-Druck.

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

## 9. Kampfsystem (Combat)

### Ablauf

Eine Attack-Order löst folgende Schritte aus:

1. Der Angreifer bewegt sich zu den Zielkoordinaten
2. Alle fremden Flotten an diesen Koordinaten werden als Verteidiger identifiziert
3. Kampfstärken werden berechnet
4. Verluste werden anteilig verteilt
5. INNN-Ereignis `galaxy.combat` wird für beide Seiten erzeugt

### Kampfstärke

```
Kampfstärke einer Flotte = Σ(Schiffanzahl × Kampfwert des Schiffstyps)
```

### Kampfwerte der Schiffstypen

| Schiff | ship_id | Kampfwert |
|--------|---------|-----------|
| Sonde | 85 | 0 |
| Korvette | 37 | 1 |
| Frachter | 47 | 0 |

Schiffe mit Kampfwert 0 sind **nicht-kampffähig** und werden im Gefecht nicht zerstört. Sonden können jedoch durch Kampfhandlungen in ihrer Nähe verloren gehen.

### Verlustberechnung

```
Verlustquote Angreifer = Verteidiger-Stärke / Gesamtstärke
Verlustquote Verteidiger = Angreifer-Stärke / Gesamtstärke
Gesamtstärke = Angreifer-Stärke + Verteidiger-Stärke
```

Verluste je Schiffstyp:

```
Verluste = ceil(Anzahl × Verlustquote)
```

Sinkt eine Schiffsklasse auf 0 oder darunter, wird der Eintrag aus `fleet_ships` gelöscht.

Haben beide Seiten keine kampffähigen Schiffe (Gesamtstärke = 0), findet kein Kampf statt.

### Konfiguration

`config/game.php → combat.ship_power`:

```php
'combat' => [
    'ship_power' => [
        85 => 0,   // sonde
        37 => 1,   // korvette
        47 => 0,   // frachter
    ],
],
```

Neue Schiffstypen und deren Kampfwerte werden ausschließlich in dieser Config konfiguriert.

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
| health | advisor_scientist | +1 Analyse-AP/Tick |
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

Rang-Aufstieg schaltet bei Rang 2 den Slot frei; Rang 3 erhöht den Slot nicht weiter (dafür steigt der AP-Bonus — §12).

**Max. aktive Sekundäreffekte:** 5 (je ein Slot pro Berater, wenn alle auf Rang 2+). Bei 7 Kenntnissen und 5 Slots muss der Spieler 2 Kenntnisse ohne Sekundäreffekt lassen — das erzeugt echte Spezialisierungsentscheidungen.

> **Balancing-Notiz:** Slot-Anzahl und Kenntnisanzahl sind Ausgangswerte für den ersten Playtest. Nach Erfahrungen aus dem Betrieb können zusätzliche Kenntnisse und/oder ein zweiter Slot bei Rang 3 eingeführt werden.

### Roguelike-Variabilität

Pro Run ist nicht der vollständige Kenntnisbaum verfügbar — nur eine zufällige Teilmenge (z.B. 5 von 7). Das erzeugt unterschiedliche Spezialisierungspfade ohne das System komplexer zu machen, analog zum variablen Spielfeld bei Catan.

> **TODO Implementierung:** Run-Mechanik mit zufälliger Kenntnisauswahl — ausstehend für Phase 3 Run-Struktur (§14).

### Supply-Cap-Bonus (Primäreffekt, bleibt erhalten)

Jede freigeschaltete Kenntnis erhöht den Supply-Cap. Da es kein Levelsystem mehr gibt, wird der Bonus als Pauschalwert beim Freischalten gewährt:

> **TODO Design:** Pauschalen Supply-Cap-Bonus pro Kenntnis definieren (ersetzt die bisherige Level-Glockenformel). Richtwert: +8–12 pro Kenntnis, sodass alle 7 Kenntnisse zusammen ~60–80 Cap-Bonus ergeben.

Bestimmte Kenntnisse beeinflussen auch das Vertrauen der Kolonie (agronomy, health, defense) — Details siehe §13.

---

## 11. Handel (Trade)

### Designprinzip (Phase 3 Redesign)

Handel ist **optional aber lohnend** — der Spieler kann alles auch ohne Handel aufbauen, aber Handel beschleunigt und verbilligt. Kein Zwang, kein Progression-Lock.

Der einzige Handelsort ist die **Bar/Cantina**. Alle Handelsaktivitäten — Kauf, Verkauf, NPC-Angebote, Spieler-zu-Spieler — laufen über denselbe Mechanik. Es gibt keinen separaten Marktplatz und kein Tradecenter.

> **Designentscheidung:** Das Tradecenter (building_id 43, CC Lv5) wird gestrichen. Die Bar übernimmt alle Handelsfunktionen.

---

### Kanal 1: Bar/Cantina (primär, früh, informell)

Die Bar ist ab CC Lv1 verfügbar. Pro Tick erscheinen 0–2 Gäste — Händler, Schmuggler, Gelegenheitsverkäufer. Jeder Gast hat ein konkretes Angebot das **1–2 Ticks gültig** ist. Danach ist der Gast weg.

**Angebotstypen:**
- Ressource gegen Credits (z.B. 50 Werkstoffe für 800 Cr)
- Ressource gegen Ressource (z.B. 30 Organika gegen 20 Regolith)

Der Spieler sieht 0–2 Angebote und entscheidet: annehmen oder ablehnen. Keine unbegrenzte Auswahl — echte Entscheidung unter Zeitdruck.

**Spieler-zu-Spieler-Handel:** Wenn ein Spieler ein Angebot in der Bar einstellt, erscheint es für andere Spieler ebenfalls als "Gast". Ob ein Gast ein NPC oder ein echter Spieler ist, bleibt unsichtbar — atmosphärisch stimmig, technisch einfach.

**Händler-Berater (advisor_trader):**
- Rang 1: Basis-Angebote (0–1 Gäste/Tick, Marktpreise)
- Rang 2: mehr Angebote (0–2 Gäste/Tick), leicht bessere Preise
- Rang 3: regelmäßige Angebote (1–2 Gäste/Tick), deutlich bessere Preise

---

### Kanal 2: Nexus-Handelsschiffe (Fallback, teuer, garantiert)

Nexus schickt auf Anfrage offizielle Handelsschiffe. Immer verfügbar — auch ohne Händler-Berater, auch ohne Bar. Das Sicherheitsnetz gegen Progression-Locks.

| | Ohne Berater | Rang 1 | Rang 2 | Rang 3 |
|---|---|---|---|---|
| Lieferzeit | 3 Ticks | 3 Ticks | 2 Ticks | 1 Tick |
| Preis | +50% Aufschlag | +40% | +25% | +10% |

Anfrage läuft über das INNN-System (Nachricht an Nexus) oder eine eigene Anfrage-UI.

> **TODO Implementierung:** Nexus-Handelsschiff-Anfrage als Fleet-Order oder INNN-Mechanik definieren.

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

> **Offen (Phase 4+):** AP-Delegation — ein Spieler "verleiht" Analytiker-AP an eine andere Kolonie für X Ticks. Thematisch stimmiger als direkter Wissenstransfer. Für spätere Phase zurückgestellt.

---

## 12. Berater & Aktionspunkte (AP-System)

### Grundkonzept

Aktionspunkte (AP) sind die zentrale Handlungswährung in Nouron. Sie begrenzen, wie viel ein Spieler pro Tick in Gebäude, Forschung, Flotten und Handel investieren kann.

Berater sind **individuelle Entitäten** — kein Mengenzähler. Jeder Berater hat einen eigenen Datensatz mit Rang, Aktivitätszähler und Verfügbarkeitsstatus. Der Spieler rekrutiert, benennt und entwickelt konkrete Individuen, keine abstrakten "Personal"-Stapel.

**5 AP-Typen — nicht mischbar:**

| AP-Typ (intern) | Beraterbezeichnung | Verwendung |
|-----------------|-------------------|-----------|
| `industry` | Ingenieur | Gebäude ausbauen, reparieren, Schiffsbau |
| `science` | Wissenschaftler | Forschungen vorantreiben, Wissenstransfer |
| `navigation` | Pilot / Kommandant | Flottenbewegung, Fleet-Trade-Orders |
| `economy` | Händler | Handelsangebote, Fraktionskontakte, Handelsabkommen |
| `strategy` | Stratege | Kampforders, Verteidigung, taktische Planung |

**Grundwert:** Jeder AP-Typ hat einen Grundwert von **6 AP/Tick** — auch ohne Berater. Ein frischer Spieler ist nie vollständig blockiert.

**Berater** erhöhen den Grundwert ihres AP-Typs. Max. **1 Berater pro Typ pro Kolonie** (Slot-System) — also maximal 5 gleichzeitig.

---

### Slot-System: CC-Level als Gate

Die Kommandozentrale bestimmt, wie viele Berater-Slots die Kolonie koordinieren kann. Die Slots werden in der Reihenfolge ihrer Nützlichkeit freigeschaltet:

| CC-Level | Freigeschalteter Slot | Beratertyp |
|----------|-----------------------|-----------|
| 1 | Slot 1 | Ingenieur |
| 2 | Slot 2 | Wissenschaftler |
| 3 | Slot 3 | Pilot / Kommandant |
| 4 | Slot 4 | Händler |
| 5+ | Slot 5 | Stratege |

Wer alle 5 Berater will, braucht mindestens CC Lv5. Das verknüpft Berater-Ausbau organisch mit dem Koloniefortschritt. Pro Typ und pro Kolonie kann immer nur genau **ein** Berater den Slot belegen — ein zweiter Ingenieur auf derselben Kolonie ist nicht möglich.

---

### Datenmodell: `advisors`-Tabelle

Jeder Berater ist ein eigener Datensatz. Die Tabelle hat folgendes Schema:

```
advisors
├── id                      ← eindeutige ID des Beraters
├── user_id                 ← Eigentümer (immer gesetzt)
├── personell_type          ← 'industry' | 'science' | 'navigation' | 'economy' | 'strategy'
├── colony_id               ← nullable: aktiv auf dieser Kolonie
├── fleet_id                ← nullable: auf dieser Flotte
├── is_commander            ← boolean: führt die Flotte als Kommandant (nur navigation-Typ)
├── rank                    ← 1 = Junior | 2 = Senior | 3 = Experte
├── active_ticks            ← kumulierter Zähler für Rang-Aufstieg
└── unavailable_until_tick  ← Erholungsphase nach Kampfverlust (NULL = verfügbar)

CHECK: colony_id IS NULL OR fleet_id IS NULL
```

**Mögliche Zustände eines Beraters:**

| colony_id | fleet_id | is_commander | Bedeutung | Gilt für |
|-----------|----------|--------------|-----------|----------|
| gesetzt | NULL | false | Aktiv auf Kolonie, generiert AP | Alle Typen |
| NULL | gesetzt | **true** | Führt Flotte als Kommandant, generiert Navigation-AP | **Nur navigation-Typ** |
| NULL | gesetzt | false | Passagier auf Flotte (Transport oder Begleitung) | Alle Typen |
| NULL | NULL | false | Arbeitslos — re-assignierbar oder handelbar | Alle Typen |

**Validierungsregel:** `is_commander = true` ist nur erlaubt, wenn `personell_type = 'navigation'`. Das wird auf Service-Ebene erzwungen.

**Entlassung** löscht keinen Berater — `colony_id` und `fleet_id` werden auf NULL gesetzt. Der Berater bleibt als arbeitsloser Datensatz erhalten und kann erneut zugewiesen oder gehandelt werden. Rang und `active_ticks` bleiben erhalten.

---

### Die fünf Berater-Typen

| Beratertyp | AP-Pool (intern) | Thematische Rolle |
|------------|-----------------|------------------|
| Ingenieur | `industry` | Infrastruktur, Gebäude, Schiffsbau |
| Wissenschaftler | `science` | Forschung, Technologie, Wissenstransfer |
| Pilot / Kommandant | `navigation` | Flottenführung, Bewegung, Fleet-Trade; kann Flotten kommandieren |
| Händler | `economy` | Wirtschaftsbeziehungen, Fraktionskontakte, Markt |
| Stratege | `strategy` | Kampf, Verteidigung, taktische Befehle |

Der Typ "Pilot / Kommandant" ist eine Doppelrolle: Auf der Kolonie generiert er Navigation-AP für das Erteilen von Flottenorders. Wenn er einer Flotte zugewiesen wird (als Kommandant), verschiebt sich sein AP-Beitrag von der Kolonie zur Flotte. Dieser Transfer ist die einzige Situation, in der ein Beraterslot auf der Kolonie temporär leer wird, ohne dass eine Entlassung stattgefunden hat.

---

### Rang-System

Jeder Berater hat einen von drei Rängen. Der Rang bestimmt den AP-Bonus pro Tick und den laufenden Upkeep in Credits.

| Rang | Bezeichnung | AP-Bonus/Tick | Gesamt-AP/Tick | Einstellungskosten (Cr) | Upkeep (Cr/Tick) |
|------|-------------|---------------|---------------|------------------------|-------------------|
| 1 | Junior | +6 | 12 | 50 | 10 |
| 2 | Senior | +14 | 20 | 150 | 50 |
| 3 | Experte | +20 | 26 | 400 | 160 |

- **Einstellungskosten** sind einmalig beim Rekrutieren fällig (Credits).
- **Upkeep** wird jeden Tick von den Colony-Credits abgezogen, solange der Berater colony_id oder fleet_id hat (also nicht arbeitslos ist).
- **Rang-Aufstieg:** automatisch nach ausreichend kumulierten `active_ticks` (Schwellwerte konfigurierbar). Optional per Credits beschleunigbar.
- Alle Werte stehen in `config/game.php → advisors` und werden nach erstem Playtest kalibriert.

> ⚠️ BALANCE CONCERN: Die Einstellungskosten sind noch nicht gegen die Credits-Startmenge (3000 Cr) kalibriert. Testspiele nötig, um zu prüfen ob ein Junior-Ingenieur am Tag 1 erschwinglich ist, ohne das frühe Spiel auszuhöhlen.

> **UI-Anforderung:** Die Berater-Verwaltung zeigt für jeden aktiven Berater: Rang, AP-Beitrag/Tick, laufender Upkeep (Cr/Tick) und `active_ticks` zum nächsten Rang-Aufstieg. Diese vier Werte müssen auf einen Blick lesbar sein.

---

### Kosten: Credits — kein Supply

Berater kosten ausschliesslich **Credits** — sowohl bei der Einstellung (einmalig) als auch im laufenden Upkeep (pro Tick). Supply ist nicht betroffen.

Supply bleibt der physische Kapazitätsdeckel für Gebäude und Schiffe. Personalkosten laufen über Credits. Das trennt zwei konzeptuell verschiedene Ressourcen sauber:

- **Supply** = physische Infrastrukturkapazität (Gebäude, Schiffe)
- **Credits** = ökonomische Liquidität (Personal, Handel, Investitionen)

Supply wird durch Kommandozentrale und Wohnkomplex generiert (Cap-Modell). Berater verbrauchen kein Supply.

**Flottenanzahl:** Die maximale Flottenanzahl pro Spieler wird durch eine Konfigurationsobergrenze begrenzt (Designentscheidung Phase 3, noch offen). Aktuell: kein Piloten-pro-Flotte-Pflichtmodell.

---

### Kommandant: Kolonie vs. Flotte

Der Pilot / Kommandant ist der einzige Beratertyp, der seinen Koloniebezug aufgeben kann, um eine Flotte zu führen.

- **Kolonie-zugewiesen:** Generiert Navigation-AP auf der Kolonie (Grundlage für neue Flottenorders).
- **Flotte-zugewiesen (Kommandant):** Generiert Navigation-AP direkt auf der Flotte; Kolonie-Slot ist leer bis zur Rückkehr.
- **Rückkehr:** Beim Auflösen einer Flotte wird der Kommandant automatisch wieder der Kolonie zugewiesen (`colony_id` gesetzt, `fleet_id` = NULL, `is_commander` = false).
- **Flottenverlust im Kampf:** Der Kommandant ist für 2–3 Ticks nicht verfügbar (`unavailable_until_tick` gesetzt), geht aber nicht dauerhaft verloren.
- **Einzelne Schiffe** brauchen keine eigenen Piloten. Nur die Flotte als Ganzes braucht einen Kommandanten.

> **TODO — Kommandanten-Zuweisung (UI nicht implementiert):** Die UI zur Zuweisung eines Kommandanten zu einer Flotte existiert noch nicht. Aktuell kann ein Pilot-Berater nur auf Kolonieebene verwaltet werden. Flottenkommandanten müssen als eigener UI-Flow implementiert werden: Flottendetailansicht → Kommandant auswählen → Transfer bestätigen → Kolonie-Slot wird leer markiert. Dieser Flow ist für Phase 2 vorgesehen und blockiert die Vollständigkeit des Flottenkommando-Systems.

---

### Verfügbare AP

```
availableAP(type) = 6 (Grundwert) + AP_bonus(rank) − lockedAP(tick, type)
```

Wobei `AP_bonus(rank)` der Bonus-Wert des aktuell zugewiesenen Beraters dieses Typs ist (0 wenn kein Berater im Slot). AP-Locks verfallen automatisch zum nächsten Tick — jeder Pool wird täglich vollständig erneuert. Die fünf Typen sind vollständig unabhängig voneinander.

### AP-Verbrauch

1. **Bauen/Forschen/Handel:** AP werden beim Investieren gesperrt (`invest('add')`).
2. **Reparatur/Abbau:** AP werden in Höhe der veränderten `status_points` gesperrt.
3. **Flottenorder:** AP-Kosten abhängig von Order-Typ (siehe §1.1 und §8).

### Implementierung

- `app/Services/Techtree/PersonellService.php` — AP-Berechnung, Sperrung
- `app/Services/Techtree/AbstractTechnologyService.php` — AP-Verbrauch beim Investieren
- `app/Services/FleetService.php` — Navigation-AP-Check bei Order-Erstellung
- Tabelle `locked_actionpoints`: `(tick, scope_type, scope_id, personell_type, spend_ap)`

### Dev-Mode

Im Dev-Mode (`GAME_DEV_MODE=true` in `.env`, Standard) werden Ressourcen- und AP-Kosten übersprungen. Das AP-System selbst bleibt aktiv für Tests.

---

---

## 13. Moralsystem

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

### Berechnung (Tick-basiert)

Vertrauen wird einmal pro Tick **neu berechnet** — nicht akkumuliert. Das Vertrauen eines Ticks ergibt sich aus der Summe aller aktiven Faktoren:

```
vertrauen = clamp(Σ(Gebäudeeffekte) + Σ(Forschungseffekte) + clamp(Σ(Schiffseffekte), -30, +30) + steuerfaktor + ereigniseffekte, -100, +100)
```

`colony_resources.amount` (resource_id=12) wird nach der Berechnung auf den neuen Wert gesetzt.

Der Wert wird in **Tick-Schritt 8b** (nach Ressourcenproduktion) berechnet, da Vertrauen die Produktionswerte desselben Ticks noch nicht beeinflusst — es wirkt ab dem Folgetick.

> **Implementierungsnotiz:** Die Tick-Reihenfolge bedeutet, dass ein Spieler erst nach 2 Ticks die volle Wirkung einer vertrauensverändernden Aktion sieht. Das ist akzeptables Design (kein Exploit durch Last-Minute-Bauweise).

### Einflussfaktoren: Gebäude

Jedes gebaute Exemplar eines Vertrauensgebäudes trägt mit einem fixen Wert pro Level bei. Nur Gebäude mit `status_points > 0` zählen (verfallene Gebäude tragen nicht bei).

**Positive Vertrauensgebäude:**

| Gebäude-ID | Bezeichner | Vertrauen/Level |
|------------|------------|-----------------|
| 32 | temple (Religiöse Stätte) | +2 |
| 46 | hospital (Krankenstation) | +3 |
| 50 | denkmal (Kolonialdenkmal) | +2 |
| 52 | bar (Cantina) | +2 |

**Negative Vertrauensgebäude:**

*(keine in Phase 3 — alle verbleibenden Gebäude sind neutral oder positiv)*

**Rationale:** Die Cantina wurde als sozialer Treffpunkt konzipiert (+2) — ein wichtiger Ort für das Gemeinschaftsgefühl einer kleinen Kolonie. Militärischer Druck wirkt über Schiffe und Kenntnisse, nicht über Gebäude.

> ⚠️ BALANCE CONCERN: Wenn ein Spieler alle positiven Gebäude maximal ausbaut (temple + hospital + denkmal + bar je Lv10+), ist das theoretische Maximum allein durch Gebäude sehr hoch. Der clamp bei +100 verhindert Überlauf, aber der Vertrauen-Cap sollte beim ersten Playtest evaluiert werden ob er zu schnell erreichbar ist.

### Einflussfaktoren: Schiffe

Schiffe tragen zum Vertrauen bei, solange sie einer Kolonie zugewiesen sind (d.h. `colony_ships.amount > 0`). Der Effekt gilt **pro Schiff**, nicht pro Level. Militärschiffe signalisieren der Bevölkerung Kriegsbereitschaft und erzeugen Unruhe; Transporter stehen für Handel und Wohlstand.

| Schiff-ID | Bezeichner | Vertrauen/Schiff |
|-----------|------------|------------------|
| 85 | sonde | 0 |
| 37 | korvette | -1 |
| 47 | frachter | +1 |

**Rationale:** Die Korvette signalisiert Militärbereitschaft (-1/Schiff). Der Frachter steht für Handel und Wohlstand (+1/Schiff). Sonden sind neutral — unbemannte Geräte erzeugen keine emotionale Reaktion.

**Skalierungsproblem:** Da Schiffszahlen potenziell groß werden können, wird der Gesamtbeitrag aller Schiffe auf `±30` gecapped, bevor er in die Vertrauen-Summe eingeht:

```
ship_vertrauen = clamp(Σ(ship_amount × vertrauen_per_ship), -30, +30)
```

> ⚠️ BALANCE CONCERN: Der Cap von ±30 für Schiffe muss nach dem ersten Playtest evaluiert werden. Eine Kolonie mit 30 Fightern wäre bei -30 bereits am Cap — das könnte für frühe militärische Spieler zu früh einsetzen. Alternativ: Cap auf -20 für eine moderatere Wirkung.

### Einflussfaktoren: Forschungen

Forschungen tragen mit einem Pauschalwert pro Level bei (unabhängig von status_points, da Forschungslevel persistenter sind).

| Kenntnis-Key | Bezeichner | Vertrauen/Level |
|--------------|------------|-----------------|
| agronomy | Agronomie & Kultivierung | +1 |
| health | Gesundheit & Wohlbefinden | +2 |
| defense | Verteidigung & Überlebenstaktik | -1 |

Alle anderen Kenntnisse (construction, cartography, geology, trade) haben keinen direkten Vertrauenseffekt — sie sind neutrale Werkzeuge.

**Rationale:** Agronomie und Gesundheit verbessern spürbar das koloniale Wohlbefinden. Verteidigung als Kenntnis verbreitet ein Klima der Wachsamkeit, das die Stimmung leicht dämpft — analoges Signal zu den Korvetten.

### Einflussfaktoren: Steuern

Das Steuersystem ist in Phase 2 noch nicht implementiert. Der Platzhalter in der Formel ist `steuerfaktor = 0`.

Vorgesehene Mechanik (Phase 3):

```
steuerfaktor = -floor(steuersatz / 10)
```

Steuersatz 0–20%: Faktor 0 (kein Malus)
Steuersatz 30%: Faktor -3
Steuersatz 50%: Faktor -5
Steuersatz 100%: Faktor -10

> ⚠️ BALANCE CONCERN: Steuern als Vertrauenssenke bedeuten, dass hohe Einnahmen immer mit Vertrauensverlust erkauft werden. Das ist gewollt, aber der konkrete Faktor muss mit dem Produktionssystem zusammen kalibriert werden.

### Einflussfaktoren: Ereignisse (Events)

Events können Vertrauen temporär verändern. Die Wirkung hält genau **1 Tick** an (danach wirken nur noch Dauereffekte). Event-Vertrauenswerte werden nicht in `colony_resources` gespeichert, sondern bei der Tick-Berechnung addiert und am Ende des Ticks verworfen.

Datenmodell: `innn_events` kann über das `data`-Feld bereits Vertrauen-Deltas tragen. Kein Schemabedarf.

**Geplante Event-Trigger und Vertrauenseffekte:**

Events sind nach Kategorie gruppiert. Alle Effekte wirken exakt 1 Tick (werden nach der Vertrauen-Berechnung verworfen). Mehrere Events desselben Typs im selben Tick summieren sich **nicht** — es gilt der stärkste Wert der Kategorie.

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

**Kampf / Militär:**

| Event-Key | Beschreibung | Vertrauenseffekt |
|-----------|-------------|------------------|
| `combat_won` | Kampf gewonnen (feindliche Flotte vernichtet) | +2 |
| `combat_lost` | Kampf verloren (eigene Einheiten zerstört) | -5 |
| `colony_attacked` | Eigene Kolonie wurde angegriffen (unabhängig vom Ausgang) | -4 |
| `war_declared` | Kriegserklärung empfangen | -8 |

**Diplomatie:**

| Event-Key | Beschreibung | Vertrauenseffekt |
|-----------|-------------|------------------|
| `treaty_signed` | Diplomatischer Vertrag abgeschlossen | +3 |

**Rationale für neue Events:**
- `colony_attacked` (-4) ist von `combat_lost` (-5) getrennt, weil ein Angriff die Bevölkerung auch dann verunsichert, wenn die Verteidigung erfolgreich war. Beide Effekte können in einem Tick summieren (Angriff + Verlust = -9).
- `trade_blocked` (-3) macht Handelsblockaden als Kriegsstrategie spürbar — nicht nur wirtschaftlich, sondern auch moralisch.

> ⚠️ BALANCE CONCERN: Ein gleichzeitiger `colony_attacked` + `combat_lost` + `war_declared` in einem Tick summiert sich zu -17. Das kann eine neutrale Kolonie (0) direkt in den "Unruhig"-Bereich (-21) kippen. Das ist designtechnisch akzeptabel (Kriege sind moralische Katastrophen), aber der Spieler braucht klares UI-Feedback welche Events ausgelöst wurden.

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

**Benötigt wird ausschließlich eine Konfiguration** in `config/game.php` unter dem Schlüssel `vertrauen`. Die vollständigen Werte (buildings, researches, ships, ships_cap, production_multiplier, ap_multiplier, events) sind dort implementiert — `config/game.php` ist die einzige Quelle der Wahrheit für alle Zahlenwerte. Dieses Dokument beschreibt die Semantik; die konkreten Zahlen stehen in der Konfigurationsdatei.

### Tick-Integration

Vertrauen wird als neuer **Tick-Schritt 8b** nach der Ressourcenproduktion berechnet:

| Schritt | Beschreibung |
|---------|-------------|
| 8 | Resource Generation — Rohstoffproduktion (mit altem Vertrauen-Multiplikator) |
| **8b** | **Vertrauen Calculation** — Vertrauen neu berechnen, `colony_resources` (res_id=12) aktualisieren |
| 9 | Advisor Ticks |

Die Reihenfolge ist bewusst: Die Produktion von Tick N verwendet den Vertrauenswert von Tick N-1. Der neue Vertrauenswert gilt erst ab Tick N+1. Das verhindert zirkuläre Abhängigkeiten.

### Implementierungsschritte

1. `config/game.php` — `vertrauen`-Block hinzufügen (alle Werte aus obiger Tabelle)
2. `app/Services/VertrauenService.php` — Service mit Methode `calculate(int $colonyId): int`
3. `app/Services/ResourceService.php` (oder TickService) — `VertrauenService::calculate()` in Schritt 8b aufrufen und `colony_resources` (res_id=12) schreiben
4. `app/Services/Techtree/PersonellService.php` — AP-Berechnung um `vertrauen_multiplier` erweitern
5. Produktionslogik (`config/game.php → production`) — Vertrauen-Multiplikator anwenden
6. UI: Vertrauen-Anzeige in der Ressourcenleiste (existiert als resource_id=12 bereits)

### Abgrenzung zu Phase 3

Das beschriebene System ist eine bewusst einfache Phase-2-Mechanik. In Phase 3 (Neukonzeption / Solo-Highscore) kann Vertrauen weiterentwickelt werden zu:
- Bevölkerungszufriedenheit mit eigenem Bevölkerungswert
- Revolutionsrisiko bei anhaltender Krise
- Fraktions-spezifische Vertrauensmodifikatoren

Diese Erweiterungen erfordern kein Schema-Refactoring, da der Grundwert (-100 bis +100) in `colony_resources` stabil bleibt.

---

## 14. Run-Struktur (Roguelike-Modus)

### Konzept

Jede Partie von Nouron ist eine abgeschlossene **Expeditionsmission**. Es gibt kein Endlosspiel — ein Run hat einen definierten Anfang, ein Ziel und ein Ende. Das Roguelike-Prinzip: Nach jedem Run (Sieg oder Niederlage) startet der Spieler von vorne. Highscore entsteht durch Effizienz (wie schnell wurden die Aufgaben erfullt) und Restressourcen.

---

### Phasenstruktur

**Empfehlung: 2 Phasen** — mehr Phasen wurden bei diesem Scope zu viel Struktur erzeugen und das FTL-artige Momentum bremsen.

#### Phase 1 — "Kolonie stabilisieren" (Pflicht)

Dauer: ~10–20 Ticks. Kann nicht ubersprungen werden. Ziel ist eine lebensfähige, selbsttragende Kolonie.

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

Startet direkt nach Phase 1. Dem Spieler werden 3 Aufgaben aus dem Aufgabenpool zugewiesen (zufällig oder aus vordefinierten Sets). **2 von 3 mussen bis Tick X erfullt werden.**

**Runlänge gesamt:** 60–100 Ticks (konfigurierbar, Standard 100). Bei 1 Tag/Tick entspricht das 2–3 Monaten — das ist die Referenzgröße für alle AP- und Ressourcen-Balancingwerte.

**Tick-Konfiguration:** Jeder Run ist über `config/game.php → run` konfigurierbar:
- `tick_limit` — Gesamtticks des Runs (Standard 100)
- `tick_duration_hours` — Maximale Echtzeit pro Tick in Stunden (Standard 24 = 1 Tag)
- `max_players` — 1 (Singleplayer) oder 2–4 (Multiplayer)
- `playbymailmode` — bei `true`: Tick endet sobald alle Spieler ihre Aktionen eingereicht haben, spätestens nach `tick_duration_hours`

> **Designprinzip:** Die Max-Wartezeit (`tick_duration_hours`) ist Pflicht auch im Play-by-Mail-Modus — ohne sie blockiert ein inaktiver Spieler alle anderen. Singleplayer nutzt immer das Zeitmodell.

---

### Aufgabenpool

10 Aufgabentypen. Alle Aufgaben sind ohne Militär erfullt werden (Kampf bleibt optional oder einer von mehreren Wegen). Jede Aufgabe passt zu einer der vorhandenen Spielmechaniken.

| # | Aufgabe | Kernmechanik | Spielstil |
|---|---------|-------------|-----------|
| 1 | **Handelsnetz** | X Handelsrouten aktiv + Gesamtvolumen Y Credits/Tick uber Z Ticks aufrecht halten | Wirtschaft |
| 2 | **Forschungsvorsprung** | Mindestens 3 Forschungen auf Level 5+ bringen | Forschung/Aufbau |
| 3 | **Kolonieblute** | Vertrauen > 70 fur 10 aufeinanderfolgende Ticks | Diplomatie/Zivilaufbau |
| 4 | **Selbstversorgung** | Beide Grundressourcen (Werkstoffe, Organika) positiv produzieren ohne Import + Supply > 0, fur 15 Ticks | Wirtschaft/Aufbau |
| 5 | **Aufklärer** | 3 verschiedene, bisher unbekannte Systeme mit einer Flotte angesteuert | Exploration |
| 6 | **Kontaktnetz** | Gleichzeitig aktive Handelsrouten mit 3 verschiedenen KI-Fraktionen | Diplomatie |
| 7 | **Ingenieursleistung** | Gesamt-SP-Kapazität aller Gebäude (Summe `max_status_points` aller colony_buildings) uber Schwelle Y | Aufbau/Optimierung |
| 8 | **Kreditimperium** | Credits-Bestand X Ticks uber Schwelle Y halten (kein einmaliger Peak, sondern anhaltender Wohlstand) | Wirtschaft |
| 9 | **Wissenschaftsnetzwerk** | Wissenschaftler-AP an mindestens 2 verschiedene KI-Kolonien ausgeliehen (je >= 10 Ticks) | Diplomatie |
| 10 | **Effizienzsprung** | AP-Nutzungsrate >= 90% fur 5 aufeinanderfolgende Ticks (verbrauchte AP / produzierte AP) | Optimierung/Hardcore |

> ⚠️ BALANCE CONCERN: Aufgaben 1 + 8 (beides Wirtschaft) sind strukturell leicht zusammen losbar. Aufgaben-Sets mussen so gezogen werden, dass sie mindestens 2 verschiedene Spielstilkategorien abdecken. Eine Kombo-Blacklist ist vor der Implementierung zu definieren.

> ⚠️ BALANCE CONCERN: Aufgabe 10 (Effizienz) kollidiert strukturell mit gleichzeitigem massivem Bauen (Aufgaben 2, 7) — "AP-effizient" und "viel bauen" sind Gegensätze. Aufgabe 10 sollte nie zusammen mit Aufgabe 2 oder 7 gezogen werden.

---

### "2 von 3"-Mechanik

**Bewertung: gut.** Die Mechanik gibt dem Spieler echte Wahlfreiheit, ohne den Run zu trivial zu machen. Eine verfehlte Aufgabe beendet den Run nicht — das reduziert Frustration und fuhrt zu mehr strategischen Entscheidungen ("Welche zwei lohnen sich fur meine aktuelle Ausgangslage?").

**Milestones gegen zu fruhen Fokus-Verlust:**
- Tick 30: Mindestens 1 Aufgabe muss zu > 50% erfullt sein. Sonst: Nexus-Warnung im INNN-Feed ("Die Expedition gerät ins Stocken — Nexus Command erwartet Fortschritt").
- Tick 50: Wenn noch keine Aufgabe vollständig erfullt, zweite Nexus-Warnung mit Tick-Countdown.

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
- Temporärer AP-Boost eines Berater-Typs für 3 Ticks
- Aufgaben-Variante wird leicht entspannt (z.B. Zielwert um 10% gesenkt)

#### Sanktionen (wenn der Spieler hinter Plan liegt)

Nexus erhöht den Druck auf Kolonien, die Milestones verfehlen:
- Berater kurz abgezogen ("vorübergehend für administrative Zwecke einberufen") — 1 Tick AP-Drop
- Kleine Credits-Gebühr ("Overhead für Missionsaufsicht")
- Gnadenfrist-Verkürzung (siehe unten)

Sanktionen erscheinen nie ohne vorherige INNN-Warnung.

#### Gnadenfrist

Ab Tick 80 zeigt das UI den Countdown sichtbar ("Noch 20 Ticks bis Missionsende"). Nexus tritt jetzt aktiver in Erscheinung:

- **Tick 85:** Wenn noch keine Aufgabe vollständig erfüllt ist → Nexus verhängt eine Sanktion (1 Berater 1 Tick abgezogen) **und** verkürzt das effektive Ende auf Tick 95. Der Spieler sieht im INNN-Feed: "Nexus Command hat die Frist auf Tick 95 vorgezogen."
- **Tick 90:** Letzte Warnung falls immer noch 0 Aufgaben erfüllt.
- **Tick 95/100:** Run endet — Fail State 2.

Wer hingegen bei Tick 85 bereits 1 Aufgabe erfüllt hat, erhält eine neutrale Statusmeldung ("Nexus registriert Fortschritt — Mission läuft.") ohne Sanktion.

> **TODO (Implementierung):** Nexus-Trigger-Tabelle definieren — welche Metrik, welcher Schwellwert, welche Reaktion, welche Phase. Muss vor der Implementierung als Config-Tabelle in `config/game.php → run.nexus_triggers` abgelegt werden.

> **TODO (Design):** Nexus-Boni in Phase 1 oder erst ab Phase 2? Phase-2-only wäre einfacher und vermeidet, neue Spieler zu bevormunden.

> **TODO (UI):** Nexus-Absender-Icon im INNN-Feed (niedrige Priorität, vor Frontend-Phase klären).

---

### Fail States

Genau 3 Fail States.

**Fail State 1 — Vertrauen kollabiert:**
Das Vertrauen der Kolonisten in den Direktor bleibt für N aufeinanderfolgende Ticks unter einem kritischen Schwellenwert (z.B. < 10).
- Begründung: Die Kolonisten verlieren den Glauben an ihre Führung. Der Direktor wird abgesetzt und muss die Kolonie verlassen.
- Vorwarnung: INNN-Ereignis wenn Vertrauen unter 20 fällt. Roter UI-Indikator bei Vertrauen < 10. Countdown-Anzeige "Noch N Ticks bis Abberufung" wenn Zustand anhält.
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
Das Tick-Limit des Runs wird erreicht ohne dass 2 von 3 Aufgaben erfüllt wurden.
- Begründung: Sauberes, vorhersehbares Ende. Verhindert Endlos-Sessions ohne Ziel.
- Tick-Limit: 100 Ticks (konfigurierbar in `config/game.php → run.tick_limit`).
- Countdown im UI sichtbar ab Tick 80 ("Noch 20 Ticks bis Missionsende").

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

### Implementierungshinweise

- Neue Tabellen: `run_objectives` (aktive Aufgaben des aktuellen Runs), `run_state` (Phase, Tick-Start, Tick-Limit, Fail-State-Tracking)
- `config/game.php → run` — Tick-Limit, Tick-Dauer, Spieleranzahl, PbM-Modus, Nexus-Trigger-Tabelle, Score-Formel-Gewichte
- Aufgaben-Fortschritt wird bei jedem Tick-Schritt geprüft (nach Schritt 9 "Advisor Ticks")
- Phase-1-Check nach Tick-Schritt 4 (Gebäude-Decay) sinnvoll, da Gebäude-Level dann aktuell ist
- Nexus-Interventionen: GameTick prüft nach Aufgaben-Fortschritt die Nexus-Trigger-Tabelle und erzeugt ggf. INNN-Events mit `sender = 'nexus'`

---

*Dokument erstellt: 2026-03-26. Weitere Abschnitte werden im Verlauf von Phase 2 ergänzt.*
