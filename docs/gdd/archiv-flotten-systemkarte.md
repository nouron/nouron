# Archiv: Flotten & Systemansicht (gestrichen 2026-06-20)

> ⛔ **Beschreibt keinen aktuellen Spielstand.** Galaxie- und Systemkarte samt Flottenbewegung und -kampf wurden am 2026-06-20 „bis auf weiteres" aus dem Spiel entfernt (Backend, Tabellen `fleets`/`fleet_*`/`glx_system*`, Services).
>
> Diese beiden Kapitel (§8 Flotten & Flottenorders, §8a Systemansicht) wurden am 2026-08-02 aus [`docs/GDD.md`](../GDD.md) hierher ausgelagert, damit die Regelkapitel nur noch geltende Mechanik beschreiben. Sie bleiben als Referenz für eine mögliche Wiedereinführung in Phase 4+ erhalten.
>
> **Weiterhin aktiv und im GDD:** Schiffe existieren ausschließlich über den Hangar (§8b) inklusive Außenmissionen. Der Reisende Händler ist eine unabhängige, implementierte Mechanik und steht in §12 Handel, Kanal 3.

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

> **Reisender Händler umgezogen (Juli 2026):** Die Beschreibung stand hier fälschlich unter dem "GESTRICHEN"-Banner dieses Abschnitts, obwohl der Reisender Händler eine aktive, unabhängig von der Systemkarte weiterhin implementierte Mechanik ist (`MerchantService`, `GameTick.php` Schritt 11, `config/game.php → merchant`). Vollständige Beschreibung jetzt in §12 Handel, Kanal 3.

### Multiplayer

> ⛔ **Veraltet (2026-07-01).** Der bisherige Ansatz (Interaktion über Flottenbewegung auf der Systemkarte) ist mit der Streichung der Systemkarte (siehe Banner oben) hinfällig. Derzeit ist **keine Multiplayer-Interaktionsmechanik geplant** — der Turn-Resolution-Layer aus ADR 0003 (`docs/adr/0003-simultan-turn-resolution-multiplayer.md`) ist davon unabhängig und unterstützt Multiplayer auch ohne gemeinsamen Interaktionsraum (z.B. mehrere Spieler, jeweils eigene isolierte Kolonie, gemeinsamer Sol-Rhythmus). Bei Bedarf neu evaluieren, sobald Multiplayer aktiv angegangen wird.

---

