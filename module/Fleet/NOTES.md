# Fleet-Modul — Business-Logik

## Was ist eine Flotte?

Eine Flotte ist eine bewegliche Einheit, die einem Spieler gehört. Sie hat eine Position
(x, y, Orbit-Slot) und kann Schiffe, Personal, Forschungen und Ressourcen transportieren.
Flotten werden auf eigenen Kolonien erstellt und können dann durch die Galaxie bewegt werden.

## Was kann der Spieler hier tun?

### Flottenübersicht (`/fleets`)
- Zeigt alle eigenen Flotten mit ihrer aktuellen Position und ihrem Status.
- Fremde Flotten am gleichen Ort sind ebenfalls sichtbar (nur lesen).
- Neue Flotten können direkt auf der aktuellen Kolonie erstellt werden.
- Status einer Flotte: **angedockt** (an Kolonie) oder **Koordinaten** (unterwegs).

### Flottenkonfiguration (`/fleet/config/{id}`)
- Nur zugänglich wenn die Flotte an einer eigenen Kolonie angedockt ist.
- Der Spieler sieht drei Spalten: **Koloniebestand | Gegenstand | Flottenbestand**.
- Mengenauswahl: 1, 5, 10 oder eigene Eingabe.
- Zwei Transferrichtungen:
  - **→ Flotte**: Gegenstand von der Kolonie in die Flotte laden.
  - **← Kolonie**: Gegenstand aus der Flotte zurück zur Kolonie entladen.

## Was kann geladen werden?

| Kategorie    | Typen                                                   | Besonderheit                    |
|--------------|---------------------------------------------------------|---------------------------------|
| Schiffe      | Kämpfer, Fregatte, Schlachtkreuzer, Transporter (3 Größen), Kolonisierungsschiff | Als Besatzung oder Fracht       |
| Personal     | Ingenieure, Wissenschaftler, Piloten, Händler            | Als Crew oder Passagiere        |
| Forschungen  | Alle 10 Forschungsgebiete                               | Als Fracht                      |
| Ressourcen   | Wasser, Ferum, Silikate, ENA, LHO, AKU                  | Nur handelbare Ressourcen       |

**Nicht transferierbar**: Credits, Supply und Moral — diese sind kolonie- bzw.
spielergebunden und können nicht in Flotten geladen werden.

## Spielregeln

- **Andocken**: Transfer ist nur möglich wenn die Flotte exakt dieselben Koordinaten
  (x, y, Spot) wie die Kolonie hat. Eine Flotte auf dem Weg kann nicht beladen werden.
- **Eigentumscheck**: Befehle können nur an eigene Flotten erteilt werden.
- **Flottenbefehlsqueue**: Mehrere Befehle können in einer Queue hinterlegt werden.
  Neue Befehle ersetzen noch ausstehende zukünftige Befehle.
- **Orbit-Slots**: Bis zu 10 Flotten können gleichzeitig an einem Ort sein (Slots 0–9).

## Befehlstypen (geplant / teilweise implementiert)

| Befehl       | Bedeutung                                              |
|--------------|--------------------------------------------------------|
| Bewegen      | Flotte zu Zielkoordinaten schicken                     |
| Halten       | Flotte bleibt am aktuellen Ort                         |
| Handel       | Handelsauftrag an einem Handelsposten ausführen        |
| Konvoi       | Ressourcentransport zwischen zwei Punkten              |
| Verteidigen  | Ort verteidigen                                        |
| Angreifen    | Feindliche Flotte oder Kolonie angreifen               |
| Zusammen-    |                                                        |
| schließen    | Mit anderer eigener Flotte vereinen                    |
| Aufteilen    | Flotte in zwei Flotten teilen                          |

## Datenstruktur (vereinfacht)

```
Flotte (fleets)
├── Schiffe      (fleet_ships)      — Typ, Anzahl, als Crew oder Fracht
├── Personal     (fleet_personell)  — Typ, Anzahl, als Crew oder Passagiere
├── Forschungen  (fleet_researches) — Typ, Anzahl (Fracht)
├── Ressourcen   (fleet_resources)  — Typ, Menge
└── Befehle      (fleet_orders)     — Tick, Befehlstyp, Zielkoordinaten, Zusatzdaten
```
