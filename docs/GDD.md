# Nouron — Game Design Document (GDD)

**Projekt:** Nouron — A Free Space Opera Browsergame
**Status:** aktiv (Stand: 2026)
**Verantwortlich:** Mario Gehnke

---

## Inhaltsverzeichnis

1. [Spielkonzept](#1-spielkonzept)
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

---

## 1. Spielkonzept

Nouron ist ein rundenbasiertes Weltraum-Strategiespiel im Browser. Spieler bauen Kolonien auf, erforschen Technologien, bauen Flotten und interagieren mit anderen Spielern durch Handel und Kampf. Das Spiel läuft servergesteuert auf Basis eines Tick-Systems: alle Spielzustandsänderungen werden einmal pro Tag global berechnet.

---

## 2. Tick-System

### Grundprinzip

Ein **Tick** entspricht einem Spieltag. Alle periodischen Spielmechaniken (Ressourcenproduktion, Gebäudeverfall, Flottenorders) werden einmal pro Tick ausgeführt.

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
| 1 | Fleet Move Orders — Flotten bewegen sich |
| 2 | Fleet Trade Orders — Ressourcentransfer Flotte ↔ Kolonie |
| 3 | Fleet Combat Orders — Kampfauflösung |
| 4 | Building Decay — Gebäude verlieren Status-Punkte |
| 5 | Supply Generation — Supply für alle User berechnen |
| 6 | Resource Generation — Rohstoffproduktion pro Kolonie |

---

## 3. Ressourcen

9 Ressourcentypen, nicht-konsekutive IDs:

| ID | Name | Kürzel | Ebene | Handelbar | Startwert |
|----|------|--------|-------|-----------|-----------|
| 1  | Credits | Cr | User | Nein | 3000 |
| 2  | Supply | Sup | User | Nein | 200 |
| 3  | Wasser | W | Kolonie | Ja | 500 |
| 4  | Ferum | E | Kolonie | Ja | 500 |
| 5  | Silikate | S | Kolonie | Ja | 500 |
| 6  | Ena (elektr. Energie) | ENrg | Kolonie | Ja | 100 |
| 8  | Lho (leichte Energie) | LNrg | Kolonie | Ja | 100 |
| 10 | Aku (Akkuenergie) | ANrg | Kolonie | Ja | 100 |
| 12 | Moral | M | Kolonie | Nein | 0 |

**Credits** und **Supply** werden auf User-Ebene (`user_resources`) geführt, alle anderen auf Kolonieebene (`colony_resources`).

---

## 4. Kolonien & Gebäude

### Gebäude (Auswahl)

| ID | Bezeichner | Max-Level | Voraussetzung |
|----|-----------|-----------|---------------|
| 25 | CommandCenter | 10 | — |
| 27 | Erzmine (oremine) | — | CC Lv1 |
| 28 | Wohnkomplex (housingComplex) | 200 | CC Lv3 |
| 31 | Forschungslabor (sciencelab) | — | CC Lv4 |
| 41 | Silikatmine (silicatemine) | — | CC Lv1 |
| 42 | Wasserextraktor (waterextractor) | — | CC Lv1 |
| 43 | Handelszentrum (tradecenter) | — | CC Lv5 |
| 44 | Zivile Werft (civilianSpaceyard) | — | — |
| 68 | Militärwerft (militarySpaceyard) | — | Zivile Werft Lv5 |
| 70 | Bank | — | — |

Vollständige Liste: CLAUDE.md, Abschnitt "Gebäude".

### Status-Punkte

Jedes Koloniegebäude hat ein `status_points`-Feld. Das Maximum (`max_status_points`) ist in der `buildings`-Tabelle hinterlegt. Status-Punkte sinken pro Tick durch Verfall (siehe Abschnitt 7).

---

## 5. Ressourcenproduktion

### Mechanik

Einmal pro Tick produziert jedes aktive Produktionsgebäude in jeder Kolonie Rohstoffe. Die produzierte Menge ergibt sich aus:

```
produzierte Menge = Gebäude-Level × Rate
```

### Produktionsgebäude (aktuell konfiguriert)

| Gebäude | building_id | Ressource | resource_id | Rate pro Level |
|---------|-------------|-----------|-------------|----------------|
| Erzmine | 27 | Ferum | 4 | 10 |
| Silikatmine | 41 | Silikate | 5 | 10 |
| Wasserextraktor | 42 | Wasser | 3 | 10 |

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

## 6. Supply-Generierung

### Mechanik

Supply ist eine User-Level-Ressource und repräsentiert die Versorgungskapazität des Imperiums. Sie steigt einmal pro Tick basierend auf den gebauten Infrastrukturgebäuden über alle Kolonien des Spielers.

```
Supply-Zuwachs = Σ(CC-Level × CC-Rate) + Σ(Wohnkomplex-Level × Wohnkomplex-Rate)
```

(Summe über alle Kolonien des Users)

### Konfigurierte Raten

| Gebäude | building_id | Rate pro Level |
|---------|-------------|----------------|
| CommandCenter | 25 | 5 Supply/Tick |
| Wohnkomplex | 28 | 10 Supply/Tick |

### Konfiguration

`config/game.php → supply`:

```php
'supply' => [
    'commandcenter_rate'  => 5,
    'housingcomplex_rate' => 10,
],
```

---

## 7. Gebäude-Verfall (Decay)

### Mechanik

Gebäude verfallen ohne aktive Pflege. Einmal pro Tick verliert jedes aktive Koloniegebäude (Level > 0) einen festgelegten Betrag an `status_points`.

Erreichen die `status_points` den Wert 0 oder darunter:
1. Das Gebäude verliert **ein Level** (`level -= 1`, Minimum: 0)
2. Die `status_points` werden auf `max_status_points` zurückgesetzt (Wert aus `buildings`-Tabelle)
3. Ein INNN-Ereignis `techtree.level_down` wird für den Koloniebesitzer erzeugt

### Designabsicht

Decay erzwingt, dass Spieler regelmäßig Aktionspunkte in Reparaturen investieren, um ihren Gebäudestand zu erhalten. Inaktive Spieler verlieren schrittweise ihre Infrastruktur.

### Konfiguration

`config/game.php → decay`:

```php
'decay' => [
    'rate' => 1,   // status_points-Verlust pro Tick
],
```

---

## 8. Flotten & Flottenorders

### Flottenorders

Flottenbewegungen und -aktionen werden als Orders in der `fleet_orders`-Tabelle gespeichert. Jede Order ist einem Tick zugewiesen und wird beim zugehörigen Tick genau einmal verarbeitet (`was_processed = 1` nach Ausführung).

### Move-Order

Bewegt eine Flotte zu Zielkoordinaten `[x, y, spot]`.

- Nach Ausführung wird die Position der Flotte (`fleets.x`, `fleets.y`, `fleets.spot`) aktualisiert
- INNN-Ereignis `galaxy.fleet_arrived` wird für den Flottenbesitzer erzeugt

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
| Kolonisationsschiff | 88 | 0 |

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
        88 => 0,   // colonyShip
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

*Vollständige Mechanik noch nicht dokumentiert — wird in Phase 2 ergänzt.*

---

*Dokument erstellt: 2026-03-26. Weitere Abschnitte werden im Verlauf von Phase 2 ergänzt.*
