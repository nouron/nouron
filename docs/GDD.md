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
5. [Ressourcenproduktion](#5-ressourcenproduktion)
6. [Supply-Generierung](#6-supply-generierung)
7. [Gebäude-Verfall (Decay)](#7-gebäude-verfall-decay)
8. [Flotten & Flottenorders](#8-flotten--flottenorders)
9. [Kampfsystem (Combat)](#9-kampfsystem-combat)
10. [Forschung](#10-forschung)
11. [Handel (Trade)](#11-handel-trade)
12. [Berater & Aktionspunkte (AP-System)](#12-berater--aktionspunkte-ap-system)
13. [Moralsystem](#13-moralsystem)
14. [Run-Struktur (Roguelike-Modus)](#14-run-struktur-roguelike-modus)

---

## 1. Spielkonzept

Nouron ist ein rundenbasiertes Weltraum-Strategiespiel im Browser. Spieler bauen Kolonien auf, erforschen Technologien, bauen Flotten und interagieren mit anderen Spielern durch Handel und Kampf. Das Spiel läuft servergesteuert auf Basis eines Tick-Systems: alle Spielzustandsänderungen werden einmal pro Tag global berechnet.

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

Nouron teilt sich das Genre "Browser-Strategiespiel" mit Dutzenden von Titeln. Was Nouron von ihnen unterscheidet, ist kein einzelnes Feature, sondern ein kohärentes Designprinzip: das Spiel ist für Spieler gebaut, die lieber nachdenken als klicken — und die Konsequenzen ihres Handelns über Tage und Wochen beobachten wollen.

### Die sechs Merkmale

**1. Verfall als durchgängiges Systemprinzip**
Gebäude, Schiffe und Forschungen verfallen ohne aktive Pflege. Wer sein Imperium vernachlässigt, verliert es langsam — nicht durch Gegner, sondern durch Entropie. Der Verfall zwingt zur Priorisierung und macht jeden Tick zu einer echten Ressourcenentscheidung.

**2. Tick-basiertes Spieltempo (1 Tick = 1 Tag)**
Keine Echtzeit-Hektik. Entscheidungen werden einmal täglich getroffen und einmal täglich ausgeführt. Das Spiel passt sich dem Spieler an, nicht umgekehrt.

**3. Nur eine Kolonie pro Spieler**
Kein Ausbreiten über eine halbe Galaxie, kein Micromanagement von zehn Außenposten. Ein Spieler, eine Kolonie — Tiefe statt Breite.

**4. Kleine, überschaubare Galaxie**
Wenige Systeme, wenige Planeten. Jede Begegnung mit einem anderen Spieler hat Gewicht. Anonymität gibt es nicht.

**5. Diplomatie und Politik als Kernmechanik**
Während andere Spiele Diplomatie als Beiwerk behandeln, ist sie in Nouron ein gleichwertiger Spielpfad neben Handel und Forschung. Bündnisse, Verträge und politisches Kapital sind keine Optionen für Pazifisten — sie sind eine eigene Form von Macht.

**6. Militär als Opportunitätskosten-Entscheidung**
Krieg ist möglich, aber er kostet. Militärische Aktionen verbrauchen strukturell mehr AP als zivile. Wer ständig angreift, wächst langsamer als jemand, der baut, forscht und handelt. (Ausführlich in §1.1.)

### Der Zusammenhang

Diese Merkmale sind kein Zufall. Sie folgen demselben Grundgedanken: Nouron belohnt Spieler, die ihren Fokus bewusst setzen, langfristig planen und mit anderen interagieren — nicht durch Überrumpelung, sondern durch Überzeugung. Das Vorbild ist das klassische 4X-Genre (Master of Orion), übersetzt in ein Browserformat mit minimalem Zeitaufwand pro Tag.

> Ein Spieler der acht Stunden täglich spielen will, hat keinen Vorteil gegenüber einem Spieler, der täglich fünf Minuten investiert — aber seine Entscheidungen sorgfältig trifft.

### Vorbilder

Die zentralen Inspirationsquellen sind klassische Strategiespiele aus der DOS-Ära:

- **Reunion** (1994) — stärkster Einfluss auf das Einzelkolonie-Konzept: Ein Spieler, ein Heimatplanet, maximale Tiefe statt Breite.
- **Imperium Galactica II** (2000) — Vorbild für das Zusammenspiel von Kolonieverwaltung, Forschung und Diplomatie.
- **Master of Orion** (1993) — Vorbild für das 4X-Grundgerüst, Fraktionen und die Kommandopunkte-Mechanik.

Nouron ist kein Klon dieser Spiele, sondern eine Neuinterpretation ihrer Kernideen im Browserformat — mit modernem Spieltempo und dem Fokus auf eine einzige Kolonie wie in Reunion.

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
| 7 | Supply Cap — `user_resources.supply` wird auf Cap gesetzt (`CC_flat + housing × 8`) |
| 8 | Resource Generation — Rohstoffproduktion pro Kolonie und Produktionsgebäude |
| 8b | Moral Calculation — Moral neu berechnen, `colony_resources` (res_id=12) aktualisieren (siehe §13) |
| 9 | Advisor Ticks — `active_ticks` erhöhen, Rang-Aufstieg prüfen |

---

## 3. Ressourcen

5 Ressourcentypen (Stand Phase 3):

| ID | Name (DE) | Name (EN) | Kürzel | Ebene | Handelbar | Startwert |
|----|-----------|-----------|--------|-------|-----------|-----------|
| 1  | Credits | Credits | Cr | User | Nein | 3000 |
| 2  | Versorgung | Supply | Sup | User | Nein | 200 |
| 4  | Werkstoffe | Compounds | Co | Kolonie | Ja | 500 |
| 5  | Organika | Organics | Or | Kolonie | Ja | 500 |
| 12 | Moral | Moral | M | Kolonie | Nein | 0 |

**Credits** und **Supply** werden auf User-Ebene (`user_resources`) geführt, alle anderen auf Kolonieebene (`colony_resources`).

### Ressourcen-Semantik

- **Werkstoffe** — Industrielle Sammelressource: Metalle, Legierungen, Keramik, Polymere. Alles was gebaut, geschmiedet und verarbeitet wird. Produktionsgebäude: Industriemine.
- **Organika** — Biologische Ressource: Nahrung, Medizin, Biodünger, organische Verbindungen. Entscheidend für Bevölkerungswachstum und Moral. Produktionsgebäude: Agrardom.
- **Versorgung** — Versorgungskapazität (Nahrung + Energie + Wasser, kombiniert abstrahiert). Kein Rohstoff im klassischen Sinne — definiert die maximale Größe der Kolonie (Cap-Modell, siehe §6).
- **Moral** — Systemmechanik, kein handelbarer Rohstoff (siehe §13).

### Zukünftiger Rohstoff (Phase 4+): Exotics

Ein dritter handelbarer Rohstoff ist für spätere Phasen reserviert: **Exotics** (Arbeitstitel) — seltene Materialien die auf der Heimatkolonie nicht abgebaut werden können. Quellen: Exploration anderer Systeme via Flotte, oder Handel mit anderen Spielern/Fraktionen. Gibt der interstellaren Bewegung einen konkreten wirtschaftlichen Zweck.

### Abgekündigte Ressourcen (werden in Phase 3 entfernt)

- Wasser (ID 3) — wird durch Versorgung (Supply) abstrahiert; kein eigenständiges Rohstoff-Modell nötig.
- ENrg (ID 6), LNrg (ID 8), ANrg (ID 10) — rassenspezifische Energieressourcen aus dem alten Konzept. Rassen wurden abgekündigt; Supply übernimmt die Energieversorgungsrolle konzeptionell.

---

## 4. Kolonien & Gebäude

### Gebäude (Phase 3 — vollständige Liste)

13 Gebäude, reduziert auf das Mini-4X-Kernsortiment:

| ID | Config-Key | Name (DE) | Name (EN) | Max-Level | Voraussetzung |
|----|------------|-----------|-----------|-----------|---------------|
| 25 | commandCenter | Kommandozentrale | Command Center | 10 | — |
| 28 | housingComplex | Wohnhabitat | Residential Habitat | 200 | CC Lv3 |
| 27 | industrieMine | Industriemine | Industrial Mine | — | CC Lv1 |
| 41 | bioFacility | Agrardom | Agrarian Dome | — | CC Lv1 |
| 30 | depot | Lagerhalle | Warehouse | — | CC Lv1 |
| 31 | sciencelab | Analytik-Labor | Analytics Lab | — | CC Lv4 |
| 43 | tradecenter | Handelsposten | Trading Post | — | CC Lv5 |
| 44 | civilianSpaceyard | Raumwerft | Spaceyard | — | — |
| 68 | militarySpaceyard | Kampfwerft | Combat Yard | — | Raumwerft Lv5 |
| 46 | hospital | Krankenstation | Medical Station | — | CC Lv2 |
| 52 | bar | Cantina | Cantina | — | CC Lv1 |
| 50 | denkmal | Kolonialdenkmal | Colonial Monument | — | — |
| 32 | temple | Religiöse Stätte | Sacred Site | — | — |

### Status-Punkte

Jedes Koloniegebäude hat ein `status_points`-Feld. Das Maximum (`max_status_points`) ist in der `buildings`-Tabelle hinterlegt. Status-Punkte sinken pro Tick durch Verfall (siehe Abschnitt 7).

---

## 5. Ressourcenproduktion

### Mechanik

Einmal pro Tick produziert jedes aktive Produktionsgebäude in jeder Kolonie Rohstoffe. Die produzierte Menge ergibt sich aus:

```
produzierte Menge = Gebäude-Level × Rate
```

### Produktionsgebäude (Phase 3)

| Gebäude | building_id | Ressource | resource_id | Rate pro Level |
|---------|-------------|-----------|-------------|----------------|
| Industriemine | 27 | Werkstoffe | 4 | 10 |
| Agrardom | 41 | Organika | 5 | 10 |

### Konfiguration

`config/game.php → production`:

```php
'production' => [
    27 => [4 => 10],   // oremine        → ferum      × 10/level
    41 => [5 => 10],   // silicatemine   → silicates  × 10/level
    42 => [3 => 10],   // waterextractor → water      × 10/level
],
```

Neue Produktionsgebäude können ohne Code-Änderung ausschließlich durch Erweiterung dieser Config hinzugefügt werden.

---

## 6. Supply-System (Cap-Modell)

### Modell

Supply ist **kein fliessender Pool**, sondern ein **Kapazitätsdeckel** (Cap-Modell). Gebäude definieren ein Maximum. Schiffe, Berater, Gebäude (außer CC und Wohnkomplex) und Forschungen belegen Supply dauerhaft. Es gibt keine Tick-basierte Supply-Generierung.

```
supply_cap    = 15 (CC, pauschal) + Anzahl-Wohnkomplexe × 8
laufende_last = Σ(Schiffe × Supply-Kosten) + Σ(Berater × 2) + Σ(Gebäude-Kosten) + Σ(Forschungs-Kosten)
freies_supply = supply_cap − laufende_last
```

Eine neue Einheit kann nur gebaut / angestellt werden wenn `freies_supply >= Kosten der neuen Einheit`.

### Supply-Cap-Quellen

| Gebäude | building_id | Supply-Cap-Beitrag |
|---------|-------------|-------------------|
| CommandCenter | 25 | **15 Supply-Cap** (pauschal, nicht pro Level) |
| Wohnkomplex | 28 | **8 Supply-Cap** pro Einheit (Level irrelevant) |

**Startsituation:** CC = 15, 1 Wohnkomplex = 8 → Supply-Cap = **23**.
**Hard-Cap:** 200 Supply.

> **Designabsicht:** Das CC gibt einen Pauschalwert — Supply-Wachstum läuft fast vollständig über Wohnkomplexe. Wer mehr Schiffe oder Berater will, muss Wohnkomplexe bauen (Opportunitätskosten gegenüber anderen Gebäuden).

### Supply-Kosten der Schiffstypen

Militärische Schiffe sind bewusst deutlich teurer als Transporter (Kernprinzip: Militär kostet mehr, siehe §1.1).

| Schiff | ship_id | Supply (Unterhalt) |
|--------|---------|-------------------|
| fighter1 | 37 | **8** |
| frigate1 | 29 | **14** |
| battlecruiser1 | 49 | **25** |
| smallTransporter | 47 | 2 |
| mediumTransporter | 83 | 4 |
| largeTransporter | 84 | 7 |
| Scout/Sonde (geplant) | — | 1 |

> ⚠️ **Phase-3-Frage:** Ob Spieler als Verwalter einer kleinen Kolonie überhaupt Battlecruiser unterhalten können sollen (Supply-Cap 25 = mehr als ein frischer Spieler hat), wird bei der Phase-3-Konzeption entschieden.

### Supply-Kosten Berater, Gebäude, Forschungen

**Berater:** 2 Supply je aktivem Berater (unabhängig von Rang).

**CommandCenter und Wohnkomplex:** kein Supply-Verbrauch (sie definieren den Cap).

**Gebäude** (individuelle Supply-Kosten aus Technologie-Tabelle):

| Gebäude | Supply |
|---------|--------|
| Industriemine, Agrardom | 2 |
| Kolonialdenkmal | 2 |
| Lagerhalle | 3 |
| Cantina, Religiöse Stätte | 4 (je) |
| Handelsposten | 7 |
| Analytik-Labor | 8 |
| Krankenstation | 10 |
| Raumwerft | 20 |
| Kampfwerft | 30 |
| civilian shipyard | 20 |
| secretops | 26 |
| military shipyard | 30 |

**Forschungen** (individuelle Supply-Kosten):

| Forschung | Supply |
|-----------|--------|
| biology, chemistry, diplomacy, economics, languages, mathematics, medical_science, physics, politics | 5 |
| military | 8 |

> Supply-Kosten sind **tick-rate-unabhängig** — sie beschreiben eine permanente Kapazitäts-Belegung, keine Fluss-Größe. Bei 1 Tick/Tag oder 24 Ticks/Tag ändert sich der belegte Cap-Anteil pro Einheit nicht.

### Decay für Schiffe und Forschungen

Schiffe und Forschungen haben — analog zu Gebäuden — `status_points` die über Zeit abnehmen.

| Entität | Decay-Rate | Besonderheit |
|---------|-----------|--------------|
| Gebäude | 1 SP/Tick | bereits implementiert |
| Schiffe | moderat (TBD) | Gnadenfrist X Ticks nach Bau; im Kampf schneller |
| Forschungen | sehr langsam (TBD) | kein Verlust durch Inaktivität, nur Verfall |

> Konkrete Werte (SP/Tick, Gnadenfrist) werden bei Implementierung in `config/game.php → decay` festgelegt.

### Konfiguration

`config/game.php → supply`:

```php
'supply' => [
    'cap_commandcenter'  => 15,   // building_id 25 — pauschal, nicht pro Level
    'cap_housingcomplex' => 8,    // building_id 28 — pro Einheit
    'cap_max'            => 200,  // absolutes Hard-Cap
    'cost_advisor'       => 2,    // Supply pro aktivem Berater
    'ship_cost' => [
        37 => 8,   // fighter1
        29 => 14,  // frigate1
        49 => 25,  // battlecruiser1
        47 => 2,   // smallTransporter
        83 => 4,   // mediumTransporter
        84 => 7,   // largeTransporter
    ],
],
```

### Supply im Tick (Schritt 7)

`user_resources.supply` speichert den **aktuellen Supply-Cap**. Er wird in Schritt 7 jedes Ticks neu berechnet und gesetzt — so spiegelt der Wert immer den aktuellen Gebäudestand wider (z. B. nach einem Level-Down des Wohnkomplexes durch Decay).

Das freie Supply (für Enforcement-Checks) ergibt sich live: `cap − Σ(entity_level × supply_cost)`.

### Abgrenzung der Unterhalts-Mechanismen

| Mechanismus | Was er begrenzt | Zeithorizont | Gegenmaßnahme |
|-------------|----------------|--------------|---------------|
| Supply-Cap | Anzahl Schiffe + Berater + Gebäude + Forschungen | permanent | mehr Wohnkomplexe bauen |
| AP | Aktionen pro Tag | täglich | mehr/bessere Berater |
| Decay | Stand von Gebäuden, Schiffen, Forschungen | täglich | Reparatur-AP investieren |

Diese drei Mechanismen sind bewusst unabhängig voneinander.

---

## 7. Verfall (Decay) — Gebäude, Schiffe, Forschungen

### Mechanik

Gebäude, Schiffe und Forschungen verfallen ohne aktive Pflege. Jedes Exemplar hat individuelle Werte für `max_status_points` und `decay_rate` (SP/Tick), die in den Stammdaten-Tabellen (`buildings`, `ships`, `researches`) gespeichert sind.

**Fraktionaler Decay:** Die `decay_rate` ist ein Dezimalwert (0.05–0.3 SP/Tick). Pro Tick wird dieser Wert von den `status_points` des Exemplars abgezogen. Ein ganzer SP geht erst verloren, wenn sich genug Verlust akkumuliert hat.

```
Beispiel: max_status_points=5, decay_rate=0.3
  Nach Tick 1: status_points = 4.70
  Nach Tick 2: status_points = 4.40
  Nach Tick 3: status_points = 4.10
  Nach Tick 4: status_points = 3.80  ← erster ganzer SP verloren
```

**Konsequenzen (wenn floor(SP) sinkt):**

| Entität | Konsequenz |
|---------|-----------|
| Gebäude | Level − 1; status_points reset auf max_status_points; INNN-Ereignis |
| Schiff | Einheit aus fleet_ships entfernt; INNN-Ereignis |
| Forschung | Level − 1; INNN-Ereignis |

### Kampf-Beschleunigung

In einem Tick, in dem ein Schiff an Kampfhandlungen beteiligt ist, gilt **Faktor 2** auf die decay_rate:

```
decay_in_kampftick = decay_rate × 2
```

Diese Regel ist bewusst einfach gehalten — leicht zu erklären, leicht zu merken.

### Richtwerte (abgeleitet aus Technologie-Tabelle)

Die Technologie-Tabelle enthält für jede Entität einen "Ticks until lost"-Wert (ohne Wartung). Daraus leitet sich die `decay_rate` ab, wenn `max_status_points` standardisiert wird:

```
decay_rate = max_status_points / ticks_until_lost
```

Mit `max_status_points = 20` als Standard ergeben sich z.B.:

| Entität | Ticks until lost | decay_rate (bei SP=20) |
|---------|-----------------|------------------------|
| bar | 100 | 0.20 |
| hospital | 100 | 0.20 |
| sciencelab | 120 | 0.17 |
| ore mine | 120 | 0.17 |
| museum | 200 | 0.10 |
| civilian shipyard | 166 | 0.12 |
| military shipyard | 250 | 0.08 |
| fighter | 133 | 0.15 |
| frigate | 125 | 0.16 |
| battlecruiser | 200 | 0.10 |
| transporter (small) | 400 | 0.05 |
| research (most) | 160 | 0.13 |

Alle Werte liegen im definierten Bereich 0.05–0.3 ✓.

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
    'rate'          => 1,    // Fallback-Rate (aktuell noch genutzt für Gebäude)
    'combat_factor' => 2,    // Schiffs-Decay im Kampftick × 2
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

**Einschränkungen Phase 2:**
- Ausschließlich innerhalb eines Sternensystems (gleiche `system_id`)
- Interstellare Bewegung (zwischen Systemen) erfordert eine noch zu definierende Wurmloch-/Sternentor-Mechanik und ist für Phase 3 vorgesehen

**Datenspeicherung:**
- Koordinaten in `fleet_orders.coordinates` werden als JSON gespeichert (`json_encode`)
- Zusatzdaten für Trade/Attack in `fleet_orders.data` ebenfalls als JSON

Nach Ausführung wird die Position der Flotte (`fleets.x`, `fleets.y`, `fleets.spot`) aktualisiert.
INNN-Ereignis `galaxy.fleet_arrived` wird für den Flottenbesitzer erzeugt.

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
| Fighter 1 | 37 | 1 |
| Fregatte 1 | 29 | 3 |
| Schlachtkreuzer 1 | 49 | 10 |
| Kleiner Transporter | 47 | 0 |
| Mittlerer Transporter | 83 | 0 |
| Großer Transporter | 84 | 0 |

Schiffe mit Kampfwert 0 sind **nicht-kampffähig** und werden im Gefecht nicht zerstört.

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
        37 => 1,   // fighter1
        29 => 3,   // frigate1
        49 => 10,  // battlecruiser1
        47 => 0,   // smallTransporter
        83 => 0,   // mediumTransporter
        84 => 0,   // largeTransporter
    ],
],
```

Neue Schiffstypen und deren Kampfwerte werden ausschließlich in dieser Config konfiguriert.

---

## 10. Forschung

10 Forschungsgebiete: biology, languages, mathematics, medicalScience, physics, chemistry, economicScience, diplomacy, politicalScience, military.

Forschung wird über Aktionspunkte (AP) vorangetrieben. Details zum AP-System sind im Techtree-Modul implementiert (`module/Techtree/`, in Laravel: `app/Services/`).

*Spielmechanik noch nicht vollständig dokumentiert — wird in Phase 2 ergänzt.*

---

## 11. Handel (Trade)

Ressourcen und Forschungen können über Handelsrouten zwischen Spielern transferiert werden. Angebote werden in `trade_resources` / `trade_researches` gespeichert.

| direction | Bedeutung |
|-----------|-----------|
| 0 | Kaufangebot (Spieler will kaufen) |
| 1 | Verkaufsangebot (Spieler will verkaufen) |

Handelsrouten werden über Flottenorders (`order = 'trade'`) abgewickelt.

### Restriktion (`restriction`)

Das `restriction`-Feld steuert, welche Spieler ein Angebot annehmen dürfen:

| Wert | Bedeutung |
|------|-----------|
| 0 | Keine Einschränkung — alle Spieler |
| 1 | Nur Mitglieder derselben Gruppe (Gilde/Team, Zusammenschluss innerhalb einer Fraktion) |
| 2 | Nur Mitglieder derselben Fraktion |
| 3 | Nur Mitglieder derselben Rasse |

> **Phase-3-Vorbehalt:** Rassen (`race_id`) werden in Phase 3 überarbeitet. Wert 3 bleibt im Datenmodell erhalten, wird aber erst nach der Überarbeitung vollständig durchgesetzt.
> Gruppen/Gilden sind noch nicht im Datenmodell vorhanden — Wert 1 kann bis zur Implementierung des Gruppenmoduls wie Wert 0 behandelt werden (keine Einschränkung).

### Forschungshandel

Forschungen können grundsätzlich gehandelt werden (`trade_researches`-Tabelle und Gateway-Methoden sind vorhanden), die genaue Mechanik wird in **Phase 3a** definiert und implementiert. Im aktuellen Acceptance-Flow wird Forschungshandel nicht unterstützt.

**Offene Designoptionen (vor Phase 3a zu entscheiden — ADR erforderlich):**

| Option | Beschreibung |
|--------|-------------|
| Level-Transfer | Käufer erhält +1 Level in der Forschung, Verkäufer verliert -1 Level |
| Wissenstransfer | Käufer erhält +1 Level, Verkäufer behält seinen Stand |
| Lizenz-Modell | Käufer erhält befristeten Bonus-Effekt, kein permanenter Level-Gewinn |
| AP-Delegation | Wissenschaftler-AP werden an fremde Kolonie "verliehen" — Forscher arbeitet dort für X Ticks |

> **Designidee (2026-04-06):** Statt Forschungen direkt zu handeln, könnten Spieler die AP ihrer Wissenschaftler an andere Kolonien "verleihen" — der Forscher arbeitet dann für eine bestimmte Anzahl Ticks auf der Fremdkolonie. Dieser Ansatz ist thematisch stimmiger (Wissen ist personengebunden) und passt gut zum AP-System. Würde das Berater-System und den Forschungshandel elegant verbinden. Zu evaluieren bei der Phase-3a-Konzeption.

---

---

## 12. Berater & Aktionspunkte (AP-System)

### Grundkonzept

Aktionspunkte (AP) sind die zentrale Handlungswährung in Nouron. Sie begrenzen, wie viel ein Spieler pro Tick in Gebäude, Forschung, Flotten und Handel investieren kann.

**5 AP-Typen — nicht mischbar:**

| AP-Typ | Berater | Verwendung |
|--------|---------|-----------|
| Konstruktion | Baumeister | Gebäude ausbauen, reparieren, Schiffsbau |
| Analyse | Analytiker | Forschungen vorantreiben, Wissenstransfer |
| Navigation | Raumfahrer | Flottenbewegung, Fleet-Trade-Orders |
| Strategie | Stratege | Kampforders, Verteidigung |
| Handel | Konsul | Handelsangebote, Fraktionskontakte, Handelsabkommen |

**Grundwert:** Jeder AP-Typ hat einen Grundwert von **6 AP/Tick** — auch ohne Berater. Ein frischer Spieler ist nie vollständig blockiert.

**Berater** erhöhen den Grundwert ihres AP-Typs. Max. **5 Berater gleichzeitig**, einer pro Typ (Slot-System).

---

### Datenmodell: `advisors`-Tabelle

```
advisors
├── id                      ← eindeutige ID des Beraters
├── user_id                 ← Eigentümer (immer gesetzt)
├── personell_id            ← FK → personell (Typ)
├── colony_id               ← nullable: aktiv auf dieser Kolonie
├── fleet_id                ← nullable: auf dieser Flotte
├── is_commander            ← boolean: führt die Flotte (nur Kommandant-Typ)
├── rank                    ← 1/2/3 (Junior/Senior/Experte)
├── active_ticks            ← für Rang-Aufstieg gezählt
└── unavailable_until_tick  ← Erholung nach Kampfverlust (NULL = verfügbar)

CHECK: colony_id IS NULL OR fleet_id IS NULL
```

**Mögliche Zustände eines Beraters:**

| colony_id | fleet_id | is_commander | Bedeutung | Gilt für |
|-----------|----------|--------------|-----------|----------|
| gesetzt | NULL | false | Aktiv auf Kolonie, generiert AP | Alle Typen |
| NULL | gesetzt | **true** | Führt Flotte, generiert Nav-AP | **Nur Kommandant** |
| NULL | gesetzt | false | Passagier auf Flotte (Transport) | Alle Typen |
| NULL | NULL | false | Arbeitslos — handelbar, re-assignierbar | Alle Typen |

**Validierungsregel:** `is_commander = true` ist nur erlaubt wenn `personell.can_command_fleet = true`. Das Flag `can_command_fleet` steht in der `personell`-Mastertabelle und ist nur für den Kommandant-Typ gesetzt.

**Entlassung** löscht keinen Berater — `colony_id` und `fleet_id` werden auf NULL gesetzt. Der Berater bleibt arbeitslos in der Tabelle und kann re-aktiviert oder an andere Spieler gehandelt werden.

---

### Die fünf Berater-Typen

| Berater | AP-Typ | Thematische Rolle |
|---------|--------|------------------|
| Baumeister | Konstruktion | Infrastruktur, Gebäude, Schiffsbau |
| Analytiker | Analyse | Forschung, Technologie, Wissenstransfer |
| Raumfahrer | Navigation | Flottenführung, Bewegung, Fleet-Trade |
| Stratege | Strategie | Kampf, Verteidigung, taktische Planung |
| Konsul | Handel | Wirtschaftsbeziehungen, Fraktionskontakte, Markt |

---

### Level-System

Jeder Berater hat ein Level (1–5). Level 4 ist der rationale Sweet Spot; Level 5 ist Prestige mit kaum höherem Effizienzgewinn.

| Level | AP-Bonus | Gesamt-AP/Tick | Upkeep (Cr/Tick) | Steuern (Cr/Tick) | Netto |
|-------|----------|---------------|-----------------|-------------------|-------|
| 1 | +6 | 12 | 10 | ~3 | ~7 |
| 2 | +10 | 16 | 25 | ~6 | ~19 |
| 3 | +14 | 20 | 50 | ~10 | ~40 |
| 4 | +18 | 24 | 90 | ~18 | ~72 ← Sweet Spot |
| 5 | +20 | 26 | 160 | ~22 | ~138 ← Prestige |

- **Upkeep** wird jeden Tick von den Credits abgezogen.
- **Steuern** zahlt jeder Berater zurück (Credits-Einnahme für den Spieler) — Netto-Kosten sind immer günstiger als der Brutto-Upkeep.
- **Level-Aufstieg:** automatisch nach ausreichend aktiven Ticks (konfigurierbar). Optional per Credits beschleunigbar.
- Alle Werte sind in `config/game.php → advisors` konfiguriert und werden nach erstem Playtest kalibriert.

> **UI-Anforderung:** Berater-Verwaltung zeigt immer Brutto-Upkeep, Steuereinnahmen und Netto-Kosten als drei separate Zeilen — sonst wirkt Level 5 rein unattraktiv.

---

### Upkeep: Credits statt Supply

Berater kosten **Credits pro Tick** (laufender Upkeep), nicht Supply. Supply bleibt der physische Kapazitätsdeckel für Gebäude und Schiffe — Personalkosten laufen über Credits. Das trennt zwei konzeptuell verschiedene Ressourcen sauber.

Supply wird durch **Koloniezentrum** und **Wohnkomplex** generiert (Cap-Modell). Gebäude und Schiffe verbrauchen Supply; Berater nicht.

**Flottenanzahl:** Die maximale Flottenanzahl wird durch eine Konfigurationsobergrenze oder das Raumfahrer-Slot-System begrenzt (Design-Entscheidung Phase 3 — aktuell: max Flotten = Anzahl aktiver Raumfahrer).

---

### Kommandant: Kolonie vs. Flotte

Der Kommandant ist der einzige Beratertyp, der seinen Koloniebezug verlieren kann.

**Modell (Option A — Phase 2):** Nur die *Flotte* braucht einen Kommandanten. Einzelne Schiffe benötigen keine eigenen Piloten. Begründung: Das Supply-Budget reicht bei Phase-2-Werten nicht für piloten-pro-Schiff-Modelle; die Opportunitätskostenstruktur (Kommandant bei Kolonie vs. Flotte) liefert ausreichend strategische Tiefe ohne Micro-Management.

- **Kolonie-zugewiesen:** Gibt der Kolonie Navigation-AP (für das Erteilen neuer Flottenorders).
- **Flotten-zugewiesen:** Gibt der Flotte direkt Navigation-AP; Koloniebezug aufgehoben.
- **Rückkehr:** Beim Löschen einer Flotte wird der Kommandant automatisch wieder der Kolonie zugewiesen.
- **Flottenverlust im Kampf:** Der Kommandant ist für 2–3 Ticks nicht verfügbar (erholt sich), geht aber nicht dauerhaft verloren.

*Phase 3:* Benannte Kommandanten mit individuellen Fähigkeiten (+Kampfbonus, -AP-Kosten) sind als Erweiterung von Option A vorgesehen, ohne das Supply-Budget zu belasten.

---

### Verfügbare AP

```
availableAP(type) = 6 (Grundwert) + Σ(AP/Tick je Berater dieses Typs) − lockedAP(tick, type)
```

AP-Locks verfallen automatisch zum nächsten Tick — jeder Pool wird täglich vollständig erneuert. Die fünf Typen sind vollständig unabhängig voneinander.

### AP-Verbrauch

1. **Bauen/Forschen/Handel:** AP werden beim Investieren gesperrt (`invest('add')`).
2. **Reparatur/Abbau:** AP werden in Höhe der veränderten `status_points` gesperrt.
3. **Flottenorder:** AP-Kosten abhängig von Order-Typ (siehe §1.1 und §8).

### Implementierung

- `app/Services/Techtree/PersonellService.php` — AP-Berechnung, Sperrung
- `app/Services/Techtree/AbstractTechnologyService.php` — AP-Verbrauch beim Investieren
- `app/Services/FleetService.php` — Navigation-AP-Check bei Order-Erstellung
- Tabelle `locked_actionpoints`: `(tick, scope_type, scope_id, personell_id, spend_ap)`

### Dev-Mode

Im Dev-Mode (`GAME_DEV_MODE=true` in `.env`, Standard) werden Ressourcen- und AP-Kosten übersprungen. Das AP-System selbst bleibt aktiv für Tests.

---

---

## 13. Moralsystem

### Design-Absicht

Moral ist das "weiche" Feedback-System der Kolonie. Sie reagiert auf die Entscheidungen des Spielers — welche Gebäude gebaut werden, wie militaristisch die Spielweise ist, welche Forschungen betrieben werden — und verstärkt oder schwächt die Kolonieleistung mit spürbaren, aber nicht spielentscheidenden Effekten.

Moral ist kein zweites Ressourcenproblem, das der Spieler managen muss. Sie ist ein stiller Bewertungsparameter: Wer eine ausgewogene, zivil-orientierte Kolonie aufbaut, wird belohnt. Wer ausschließlich auf Militär setzt und Zivilinfrastruktur vernachlässigt, spürt das in einer moderaten Malus-Spirale.

### Wertebereich

```
Moral: -100 bis +100
Neutralwert: 0
Startwert: 0
```

**Bedeutungsbereiche:**

| Bereich | Bezeichnung | Anzeige (UI-Hinweis) |
|---------|-------------|----------------------|
| +61 bis +100 | Hochmoral | "Euphorisch" |
| +21 bis +60 | Positive Stimmung | "Zufrieden" |
| -20 bis +20 | Neutral | "Stabil" |
| -21 bis -60 | Unzufriedenheit | "Unruhig" |
| -61 bis -100 | Krise | "Aufruhr" |

Der Wert -100 ist ein harter Boden (keine weitere Verschlechterung). Ebenso +100 als Deckel.

### Berechnung (Tick-basiert)

Moral wird einmal pro Tick **neu berechnet** — nicht akkumuliert. Die Moral eines Ticks ergibt sich aus der Summe aller aktiven Faktoren:

```
moral = clamp(Σ(Gebäudeeffekte) + Σ(Forschungseffekte) + clamp(Σ(Schiffseffekte), -30, +30) + steuerfaktor + ereigniseffekte, -100, +100)
```

`colony_resources.amount` (resource_id=12) wird nach der Berechnung auf den neuen Wert gesetzt.

Der Wert wird in **Tick-Schritt 8** (nach Ressourcenproduktion) berechnet, da Moral die Produktionswerte desselben Ticks noch nicht beeinflusst — sie wirkt ab dem Folgetick.

> **Implementierungsnotiz:** Die Tick-Reihenfolge bedeutet, dass ein Spieler erst nach 2 Ticks die volle Wirkung einer moralverändernden Aktion sieht. Das ist akzeptables Design (kein Exploit durch Last-Minute-Bauweise).

### Einflussfaktoren: Gebäude

Jedes gebaute Exemplar eines Moralgebäudes trägt mit einem fixen Wert pro Level bei. Nur Gebäude mit `status_points > 0` zählen (verfallene Gebäude tragen nicht bei).

**Positive Moralgebäude:**

| Gebäude-ID | Bezeichner | Moral/Level |
|------------|------------|-------------|
| 32 | temple | +2 |
| 45 | parc | +2 |
| 46 | hospital | +3 |
| 48 | public_security | +1 |
| 50 | denkmal | +2 |
| 51 | university | +2 |
| 53 | stadium | +3 |
| 56 | museum | +2 |
| 65 | recyclingStation | +1 |

**Negative Moralgebäude:**

| Gebäude-ID | Bezeichner | Moral/Level |
|------------|------------|-------------|
| 52 | bar | -1 |
| 54 | casino | -2 |
| 55 | prison | -3 |
| 64 | wastedisposal | -1 |
| 66 | secretOps | -2 |
| 68 | militarySpaceyard | -1 |

**Rationale:** Kasino und Gefängnis degradieren aktiv die gesellschaftliche Stimmung. Das Gefängnis signalisiert soziale Kontrolle und Repression (-3/Level ist bewusst stark, um Prison-Spam zu bestrafen). Bar und Casino haben negative Effekte, aber nur moderat — sie sind ein bewusster Trade-off (Credits vs. Moral). Die Militärwerft hat einen kleinen Malus als Untermauerung des Kernprinzips "Militarismus hat Kosten".

> ⚠️ BALANCE CONCERN: Wenn ein Spieler alle positiven Gebäude maximal ausbaut (temple Lv10 + hospital Lv10 + stadium Lv10 ...), ist das theoretische Maximum allein durch Gebäude sehr hoch. Der clamp bei +100 verhindert Überlauf, aber der Moral-Cap sollte getestet werden ob er zu schnell erreichbar ist ohne negative Gebäude.

### Einflussfaktoren: Schiffe

Schiffe tragen zur Moral bei, solange sie einer Kolonie zugewiesen sind (d.h. `colony_ships.amount > 0`). Der Effekt gilt **pro Schiff**, nicht pro Level. Militärschiffe signalisieren der Bevölkerung Kriegsbereitschaft und erzeugen Unruhe; Transporter stehen für Handel und Wohlstand.

**Militärische Schiffe (negative Moral):**

| Schiff-ID | Bezeichner | Moral/Schiff |
|-----------|------------|--------------|
| 37 | fighter1 | -1 |
| 29 | frigate1 | -2 |
| 49 | battlecruiser1 | -4 |

**Zivile/Transport-Schiffe (positive Moral):**

| Schiff-ID | Bezeichner | Moral/Schiff |
|-----------|------------|--------------|
| 47 | smallTransporter | +1 |
| 83 | mediumTransporter | +1 |
| 84 | largeTransporter | +2 |

**Rationale:** Militärschiffe verstärken das Prinzip "Militarismus hat Kosten" — wer eine starke Kriegsflotte in der Kolonie stationiert, zahlt mit Moralverlust. Der Malus ist absichtlich progressiv (Battlecruiser -4 > Frigate -2 > Fighter -1), um Massenansammlungen schwerer Schiffe spürbar zu bestrafen, ohne kleine Verteidigungsflotten zu ruinieren. Transportschiffe belohnen eine handelsorientierte Spielweise mit kleinen Moralgewinnen (+1/+2 pro Schiff).

**Skalierungsproblem:** Da Schiffszahlen potenziell groß werden können, wird der Gesamtbeitrag aller Schiffe auf `±30` gecapped, bevor er in die Moral-Summe eingeht:

```
ship_moral = clamp(Σ(ship_amount × moral_per_ship), -30, +30)
```

> ⚠️ BALANCE CONCERN: Der Cap von ±30 für Schiffe muss nach dem ersten Playtest evaluiert werden. Eine Kolonie mit 30 Fightern wäre bei -30 bereits am Cap — das könnte für frühe militärische Spieler zu früh einsetzen. Alternativ: Cap auf -20 für eine moderatere Wirkung.

### Einflussfaktoren: Forschungen

Forschungen tragen mit einem Pauschalwert pro Level bei (unabhängig von status_points, da Forschungslevel persistenter sind).

| Forschungs-ID | Bezeichner | Moral/Level |
|---------------|------------|-------------|
| 33 | biology | +1 |
| 72 | medicalScience | +2 |
| 79 | diplomacy | +1 |
| 80 | politicalScience | +1 |
| 81 | military | -2 |
| 34 | languages | +1 |

Alle anderen Forschungen (mathematics, physics, chemistry, economicScience) haben keinen direkten Moraleffekt — sie sind neutrale Werkzeuge.

**Zur military-Forschung:** Der Wert wurde auf -2/Level angehoben (von -1). Da military-Forschung auf bis zu Level 10 steigen kann und Schiffe bereits einen separaten Malus erzeugen, soll die Forschung selbst ein deutlicheres Signal setzen. Ein vollständig militärisch ausgelegter Spieler (military Lv10 + schwere Schiffsflotte) sieht dadurch einen merklichen kombinierten Malus.

> ⚠️ BALANCE CONCERN: military-Forschung auf -2/Level bedeutet bis zu -20 allein durch die Forschung. Zusammen mit Schiffs-Malus und militarySpaceyard kann ein Hardcore-Militärspieler tief in den negativen Moralbereich geraten. Das ist gewollt, aber der Spieler braucht ausreichend Kompensationsmöglichkeiten durch Zivilgebäude.

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

> ⚠️ BALANCE CONCERN: Steuern als Moralsenke bedeuten, dass hohe Einnahmen immer mit Moralverlust erkauft werden. Das ist gewollt, aber der konkrete Faktor muss mit dem Produktionssystem zusammen kalibriert werden.

### Einflussfaktoren: Ereignisse (Events)

Events können Moral temporär verändern. Die Wirkung hält genau **1 Tick** an (danach wirken nur noch Dauereffekte). Event-Moralwerte werden nicht in `colony_resources` gespeichert, sondern bei der Tick-Berechnung addiert und am Ende des Ticks verworfen.

Datenmodell: `innn_events` kann über das `data`-Feld bereits Moral-Deltas tragen. Kein Schemabedarf.

**Geplante Event-Trigger und Moraleffekte:**

Events sind nach Kategorie gruppiert. Alle Effekte wirken exakt 1 Tick (werden nach der Moral-Berechnung verworfen). Mehrere Events desselben Typs im selben Tick summieren sich **nicht** — es gilt der stärkste Wert der Kategorie.

**Bauwesen / Forschung:**

| Event-Key | Beschreibung | Moraleffekt |
|-----------|-------------|-------------|
| `building_level_up` | Gebäude fertiggestellt (Level-Up) | +1 |
| `building_level_down` | Gebäude verfallen (Level-Down durch Decay) | -3 |
| `research_level_up` | Forschung abgeschlossen (Level-Up) | +2 |

**Handel:**

| Event-Key | Beschreibung | Moraleffekt |
|-----------|-------------|-------------|
| `trade_success` | Handelsmission erfolgreich abgeschlossen | +2 |
| `trade_blocked` | Handelsmission durch feindliche Flotte blockiert | -3 |

**Kampf / Militär:**

| Event-Key | Beschreibung | Moraleffekt |
|-----------|-------------|-------------|
| `combat_won` | Kampf gewonnen (feindliche Flotte vernichtet) | +2 |
| `combat_lost` | Kampf verloren (eigene Einheiten zerstört) | -5 |
| `colony_attacked` | Eigene Kolonie wurde angegriffen (unabhängig vom Ausgang) | -4 |
| `war_declared` | Kriegserklärung empfangen | -8 |

**Diplomatie:**

| Event-Key | Beschreibung | Moraleffekt |
|-----------|-------------|-------------|
| `treaty_signed` | Diplomatischer Vertrag abgeschlossen | +3 |

**Rationale für neue Events:**
- `colony_attacked` (-4) ist von `combat_lost` (-5) getrennt, weil ein Angriff die Bevölkerung auch dann verunsichert, wenn die Verteidigung erfolgreich war. Beide Effekte können in einem Tick summieren (Angriff + Verlust = -9).
- `trade_blocked` (-3) macht Handelsblockaden als Kriegsstrategie spürbar — nicht nur wirtschaftlich, sondern auch moralisch.

> ⚠️ BALANCE CONCERN: Ein gleichzeitiger `colony_attacked` + `combat_lost` + `war_declared` in einem Tick summiert sich zu -17. Das kann eine neutrale Kolonie (0) direkt in den "Unruhig"-Bereich (-21) kippen. Das ist designtechnisch akzeptabel (Kriege sind moralische Katastrophen), aber der Spieler braucht klares UI-Feedback welche Events ausgelöst wurden.

> ⚠️ BALANCE CONCERN: Event-Moraleffekte für Bauwesen sind einmalig (+1 pro Level-Up). Ein Spieler der täglich Gebäude baut, erhält täglich +1 — das ist ein kleiner, aber stetiger Bonus der aktives Spielen belohnt. Ob das ausreicht als Motivation oder ob der Effekt auf +2 erhöht werden sollte, ist nach erstem Playtest zu evaluieren.

### Effekte der Moral auf die Kolonie

Moral beeinflusst drei Spielparameter. Alle Effekte werden als **Multiplikatoren** auf die Basiswerte angewendet, nicht als additive Boni. Das verhindert, dass Moral zu einer dominanten Wachstumsstrategie wird.

#### Ressourcenproduktion

```
produzierte_menge_effektiv = produzierte_menge × production_multiplier(moral)
```

| Moralbereich | Multiplikator |
|--------------|---------------|
| +61 bis +100 | 1.20 (+20%) |
| +21 bis +60 | 1.10 (+10%) |
| -20 bis +20 | 1.00 (neutral) |
| -21 bis -60 | 0.85 (-15%) |
| -61 bis -100 | 0.70 (-30%) |

Angewendet auf alle Produktionsgebäude (oremine, silicatemine, waterextractor und zukünftige).

#### AP-Multiplikator

```
AP_effektiv = AP_basis × ap_multiplier(moral)
```

| Moralbereich | Multiplikator |
|--------------|---------------|
| +61 bis +100 | 1.10 (+10%) |
| +21 bis +60 | 1.05 (+5%) |
| -20 bis +20 | 1.00 (neutral) |
| -21 bis -60 | 0.90 (-10%) |
| -61 bis -100 | 0.80 (-20%) |

Der AP-Bonus bei hoher Moral ist bewusst kleiner als der Produktionsbonus — AP ist die knappste Ressource und soll nicht durch Moral-Stacking zu stark erhöht werden.

> ⚠️ BALANCE CONCERN: Ein AP-Malus von -20% bei Aufruhr macht Krisensituationen selbstverstärkend (weniger AP → weniger Reparaturen → mehr Decay → mehr Moral-Malus). Diese Spirale ist designtechnisch vertretbar (Entropie als Spielprinzip), aber es muss einen Ausweg geben. Der Ausweg ist der Bau von Moralgebäuden, der trotz AP-Malus möglich bleibt (die Malus-Grenze liegt bei 0.80, nicht bei 0).

#### Supply-Cap

Moral beeinflusst den Supply-Cap **nicht**. Das Supply-System ist ein separater Constraint (Wohnkomplexe, CC) und soll nicht durch ein weiteres System kompliziert werden. Beide Systeme bleiben orthogonal.

### Schema-Bedarf

**Kein neues Schema erforderlich.** `colony_resources.amount` (resource_id=12) speichert den aktuellen Moralwert als Integer im Bereich -100 bis +100. Das ist ausreichend — Moral ist ein Zustand, keine akkumulierte Menge.

**Benötigt wird ausschließlich eine Konfiguration** in `config/game.php` unter dem Schlüssel `moral`. Die vollständigen Werte (buildings, researches, ships, ships_cap, production_multiplier, ap_multiplier, events) sind dort implementiert — `config/game.php` ist die einzige Quelle der Wahrheit für alle Zahlenwerte. Dieses Dokument beschreibt die Semantik; die konkreten Zahlen stehen in der Konfigurationsdatei.

### Tick-Integration

Moral wird als neuer **Tick-Schritt 8b** nach der Ressourcenproduktion berechnet:

| Schritt | Beschreibung |
|---------|-------------|
| 8 | Resource Generation — Rohstoffproduktion (mit altem Moral-Multiplikator) |
| **8b** | **Moral Calculation** — Moral neu berechnen, `colony_resources` (res_id=12) aktualisieren |
| 9 | Advisor Ticks |

Die Reihenfolge ist bewusst: Die Produktion von Tick N verwendet den Moral-Wert von Tick N-1. Der neue Moralwert gilt erst ab Tick N+1. Das verhindert zirkuläre Abhängigkeiten.

### Implementierungsschritte

1. `config/game.php` — `moral`-Block hinzufügen (alle Werte aus obiger Tabelle)
2. `app/Services/MoralService.php` — Service mit Methode `calculate(int $colonyId): int`
3. `app/Services/ResourceService.php` (oder TickService) — `MoralService::calculate()` in Schritt 8b aufrufen und `colony_resources` (res_id=12) schreiben
4. `app/Services/Techtree/PersonellService.php` — AP-Berechnung um `moral_multiplier` erweitern
5. Produktionslogik (`config/game.php → production`) — Moral-Multiplikator anwenden
6. UI: Moral-Anzeige in der Ressourcenleiste (existiert als resource_id=12 bereits)

### Abgrenzung zu Phase 3

Das beschriebene System ist eine bewusst einfache Phase-2-Mechanik. In Phase 3 (Neukonzeption / Solo-Highscore) kann Moral weiterentwickelt werden zu:
- Bevölkerungszufriedenheit mit eigenem Bevölkerungswert
- Revolutionsrisiko bei anhaltender Krise
- Fraktions-spezifische Moralmodifikatoren

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

**Abschlussbedingungen (ALLE drei mussen erfullt sein):**

| Bedingung | Konkret |
|-----------|---------|
| Infrastruktur | CommandCenter Level 3 + mindestens 2 Produktionsgebäude auf Level >= 2 |
| Versorgung | Supply > 0 fur 3 aufeinanderfolgende Ticks (kein Engpass) |
| Personal | Mindestens 3 aktive Berater (beliebiger Typ) |

Die drei Bedingungen decken alle Kernsysteme ab: Aufbau (Gebäude), Ressource (Supply) und Handlungsfähigkeit (AP). Sie sind eindeutig messbar und fur Neuspieler verstandlich.

Phase 1 endet automatisch, sobald alle drei Bedingungen gleichzeitig erfullt sind. Der Spieler erhält eine Benachrichtigung und Phase 2 beginnt.

#### Phase 2 — "Expeditionsmission"

Startet direkt nach Phase 1. Dem Spieler werden 3 Aufgaben aus dem Aufgabenpool zugewiesen (zufällig oder aus vordefinierten Sets). **2 von 3 mussen bis Tick X erfullt werden.**

**Runlänge gesamt:** 60–100 Ticks (konfigurierbar). Bei 1 Tag/Tick entspricht das 2–3 Monaten.

---

### Aufgabenpool

10 Aufgabentypen. Alle Aufgaben sind ohne Militär erfullt werden (Kampf bleibt optional oder einer von mehreren Wegen). Jede Aufgabe passt zu einer der vorhandenen Spielmechaniken.

| # | Aufgabe | Kernmechanik | Spielstil |
|---|---------|-------------|-----------|
| 1 | **Handelsnetz** | X Handelsrouten aktiv + Gesamtvolumen Y Credits/Tick uber Z Ticks aufrecht halten | Wirtschaft |
| 2 | **Forschungsvorsprung** | Mindestens 3 Forschungen auf Level 5+ bringen | Forschung/Aufbau |
| 3 | **Kolonieblute** | Moral > 70 fur 10 aufeinanderfolgende Ticks | Diplomatie/Zivilaufbau |
| 4 | **Selbstversorgung** | Alle 3 Grundressourcen (Water, Ferum, Silicates) positiv produzieren ohne Import + Supply > 0, fur 15 Ticks | Wirtschaft/Aufbau |
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
- Tick 30: Mindestens 1 Aufgabe muss zu > 50% erfullt sein. Sonst: Ereignis-Warnung im INNN-Feed ("Die Expedition gerät ins Stocken").
- Tick 50: Wenn noch keine Aufgabe vollständig erfullt, zweite Warnung mit Tick-Countdown.

Diese Milestones sind weich (kein Fail, nur Feedback) und erzeugen Dringlichkeitsgefuhl ohne Frustration.

---

### Fail States

Genau 2 Fail States. Kein Fail durch Militarverlust (passt zur Designphilosophie §1.1).

**Fail State 1 — Versorgungskollaps:**
Supply faellt auf 0 und bleibt 3 aufeinanderfolgende Ticks bei 0.
- Begründung: Supply ist der Lebensnerv der Kolonie. 3 Ticks Gnadenfrist gibt dem Spieler Zeit zu reagieren; wer nicht reagiert, hat die Kontrolle verloren.
- Vorwarnung: INNN-Ereignis bei Supply = 0 (Tick 1 des Engpasses). Roter UI-Indikator ab Tick 2. Tick 3 = Run-Ende mit Meldung "Kolonie aufgegeben".

**Fail State 2 — Zeitablauf:**
Das Tick-Limit des Runs wird erreicht ohne dass 2 von 3 Aufgaben erfullt wurden.
- Begründung: Sauberes, vorhersehbares Ende. Verhindert Endlos-Sessions ohne Ziel.
- Tick-Limit: 100 Ticks (konfigurierbar in `config/game.php → run.tick_limit`).
- Countdown im UI sichtbar ab Tick 80 ("Noch 20 Ticks bis Missionsende").

---

### Highscore-Berechnung (Entwurf)

```
score = (aufgaben_erfullt × 1000) + (tick_limit - erfullt_in_tick) × 10 + (credits_rest / 10) + (moral_at_end × 5)
```

Komponenten:
- Aufgabenanzahl (2 oder 3) als Hauptfaktor
- Geschwindigkeit (fruheres Erfullen = mehr Punkte)
- Wohlstand (verbleibende Credits)
- Koloniequalität (Moral am Ende)

> ⚠️ BALANCE CONCERN: Highscore-Formel ist ein erster Entwurf. Gewichtung muss nach ersten Playtests kalibriert werden. Ziel: 3-von-3-Sieg sollte deutlich mehr Punkte ergeben als 2-von-3, aber ein schneller 2-von-3-Sieg kann einen langsamen 3-von-3-Sieg ubertrumpfen.

---

### Implementierungshinweise

- Neue Tabellen benotigt: `run_objectives` (aktive Aufgaben des aktuellen Runs), `run_state` (Phase, Tick-Start, Tick-Limit, Fail-State-Tracking)
- `config/game.php → run` — Tick-Limit, Aufgaben-Pool-Konfiguration, Score-Formel-Gewichte
- Aufgaben-Fortschritt wird bei jedem Tick-Schritt gepruft (nach Schritt 9 "Advisor Ticks")
- Phase-1-Check nach Tick-Schritt 4 (Gebäude-Decay) sinnvoll, da Gebäude-Level dann aktuell ist

---

*Dokument erstellt: 2026-03-26. Weitere Abschnitte werden im Verlauf von Phase 2 ergänzt.*
