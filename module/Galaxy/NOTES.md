# Galaxy-Modul — Business-Logik

## Was ist die Galaxie?

Die Galaxie ist der übergeordnete Spielraum. Sie besteht aus einer großen 2D-Karte mit
Sternsystemen, die über Koordinaten (x, y) verteilt sind. Typische Koordinaten liegen
im Bereich von einigen Tausend (z.B. 6800, 3000). Innerhalb eines Sternsystems gibt es
Systemobjekte (Planeten, Monde, Asteroiden, Nebel, usw.) auf denen Kolonien errichtet
werden können.

## Was kann der Spieler hier tun?

### Galaxiekarte erkunden
- Der Spieler sieht eine interaktive Karte aller Sternsysteme.
- Per Klick auf ein System öffnet sich die Systemansicht.
- In der Systemansicht sind alle Objekte (Planeten etc.), Kolonien und Flotten
  sichtbar, die sich gerade dort aufhalten.

### Flottenweg planen
- Der Spieler kann ein Ziel auf der Karte auswählen.
- Der Server berechnet den kürzesten Weg vom aktuellen Standort zum Ziel
  (Bresenham-Algorithmus, Schritt für Schritt pro Spieltick).
- Die Reisezeit ergibt sich aus der Manhattan-Distanz: jeder Schritt dauert
  einen Tick.

### Flottenbefehle erteilen
- Über die Galaxieansicht können Flotten Marschbefehle bekommen.
- Unterstützte Befehlstypen: **Bewegen, Handel, Halten, Konvoi, Verteidigen,
  Angreifen, Zusammenschließen, Aufteilen**.
- Befehle werden als Tick-Queue gespeichert. Ein Befehl mit 10 Schritten belegt
  10 Ticks.
- An den finalen Befehl können Zusatzdaten angehängt werden (z.B. Handelsparameter).

## Spielregeln

- **Sichtbarkeit**: Alle Flotten an einem Ort sind sichtbar — eigene und fremde.
  Fremde Flotten können nur betrachtet, nicht befehligt werden.
- **Koordinatensystem**: 3-dimensional: x, y und ein Orbit-Slot (0–9). Bis zu
  10 Flotten können gleichzeitig denselben Ort belegen.
- **Reichweite**: Die Detailansicht zeigt Objekte im Umkreis von max. 50 Einheiten.
- **Reisegeschwindigkeit**: Aktuell 1 Feld pro Tick (hardcoded, später konfigurierbar).

## Datenstruktur (vereinfacht)

```
Galaxie
└── Sternsysteme (glx_systems)       — x, y, Name
    └── Systemobjekte (glx_system_objects)  — x, y, Typ (Planet, Mond, Asteroid, …)
        └── Kolonien (glx_colonies)         — Spot-Nummer, Besitzer, Name
```
