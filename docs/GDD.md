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
   - 4b. [Die drei Pfade](#4b-die-drei-pfade)
   - 4c. [Instanzen oder Level — die Wachstumsachse je Gebäude](#4c-instanzen-oder-level--die-wachstumsachse-je-gebäude)
5. [Ressourcenproduktion](#5-ressourcenproduktion)
6. [Supply-System (Cap-Modell)](#6-supply-system-cap-modell)
7. [Verfall & Entropie](#7-verfall--entropie)
8. [Flotten & Flottenorders](gdd/archiv-flotten-systemkarte.md) → gestrichen, Archiv
   - 8a. [Systemansicht](gdd/archiv-flotten-systemkarte.md) → gestrichen, Archiv
   - 8b. [Hangar-Screen](#8b-hangar-screen)
9. [Begegnungen & Gefahren](#9-begegnungen--gefahren)
10. [Kenntnisse (ehem. Forschung)](#10-kenntnisse-ehem-forschung)
11. [Techtree](gdd/techtree.md) → eigene Datei
12. [Handel (Trade)](#12-handel-trade)
13. [Berater & Aktionspunkte (AP-System)](#13-berater--aktionspunkte-ap-system)
    - 13.1 [Ein gemeinsamer AP-Pool](#131-ein-gemeinsamer-ap-pool-entscheidung-2026-08-02)
    - 13.2 [Ratenmodell: Handlungen und Projekte](#132-ratenmodell-handlungen-und-projekte)
    - 13.3 [Boni: additiv, nie multiplikativ](#133-boni-additiv-nie-multiplikativ)
    - 13.4 [Kommandozentrale: Dashboard und Prognosen](#134-kommandozentrale-dashboard-und-prognosen)
    - 13.5 [Instandhaltungslast und die Regolith-Grenze](#135-instandhaltungslast-und-die-regolith-grenze)
    - 13.6 [Zahlenvorschlag, erste Fassung (überholt)](#136-zahlenvorschlag-erste-fassung-überholt--siehe-137)
    - 13.7 [Regolith-Zahlensatz, hergeleitet](#137-regolith-zahlensatz-hergeleitet-stand-2026-08-02--vorschlag)
14. [Moralsystem](#14-moralsystem)
15. [Run-Struktur (Roguelike-Modus)](#15-run-struktur-roguelike-modus)
16. [Onboarding](gdd/onboarding.md) → eigene Datei
17. [Progressive Discovery System](gdd/progressive-discovery.md) → eigene Datei
18. [Run-Ende & Fail-State](#18-run-ende--fail-state)
- [Anhang A — Balance- und TODO-Index](#anhang-a--balance--und-todo-index)

> **Ausgelagerte Kapitel** (2026-08-02, `docs/gdd/`): §8 + §8a (gestrichen, [Archiv](gdd/archiv-flotten-systemkarte.md)), §11 [Techtree](gdd/techtree.md), §16 [Onboarding](gdd/onboarding.md), §17 [Progressive Discovery](gdd/progressive-discovery.md). Kriterium: Kapitel, die man beim Nachdenken über Spielregeln nicht mitliest — nicht mehr geltende Mechanik, Entitätslisten und UX-/Content-Spezifikationen. Die Regelkapitel §1–7, §8b, §9–10, §12–15 und §18 bleiben zusammen. Ebenfalls in `docs/gdd/`: [`entity-chips.md`](gdd/entity-chips.md).

---

## Arbeitsstand 2026-08-02 (AP-Konsolidierung)

> **Nichts davon ist festgeschrieben.** Dies ist der Diskussionsstand einer laufenden Design-Runde, nicht ein Beschluss. **Kein Punkt ist implementiert** — weder in `config/`, noch in der Datenbank, noch im Code. Alles bleibt anpassbar, auch die Richtungsentscheidungen unten. Der Zweck dieses Abschnitts ist, den Stand nachvollziehbar zu halten, damit die nächste Runde nicht bei null anfängt.

Richtungsentscheidungen aus der Konsolidierungs-Session:

| # | Richtung | Wo |
|---|---|---|
| 1 | **Ein gemeinsamer AP-Pool** statt fünf getrennter, nicht mischbarer AP-Typen | §13.1 |
| 2 | **Ratenmodell**: AP fließen sowohl in sofortige Handlungen als auch in Projekte über mehrere Sole; Parallelbau erlaubt, Boni additiv | §13.2, §13.3 |
| 3 | **Stratege zurückgestellt** — vier Beratertypen, Slot 5 entfällt | §13 |
| 4 | **Harvester ohne Level-Up** (`max_level = 1`) — liefert ein Regolith-Grundeinkommen, Wachstum kommt aus Kenntnissen, Missionen und Handel | §13.5 |

Dazu drei Korrekturen an Stellen, die schlicht falsch waren:

| Was | Ort |
|---|---|
| Supply-Formel: `Σ(Level × supply_cost)`, nicht `Σ(Gebäude-Kosten)` — Supply begrenzt **Tiefe**, nicht Anzahl | §6 |
| §13.5 „Verfallsgrenze" beschrieb ein AP-Gleichgewicht, das bei den aktuellen Werten nicht eintreten kann | §13.5 |
| „Supply-Cap begrenzt Anzahl Schiffe + Gebäude" — Schiffe kosten seit 2026-06-08 kein Supply | §6 |

Ein konkreter Zahlenvorschlag für Grundwert, Projektkosten und Bonus-Kurve liegt in **§13.6** — ausdrücklich als Vorschlag, ohne Entscheidung.

> **Zurückgestellt: Werkstoffe streichen.** Die Streichung der Werkstoffe (Credits übernehmen die Rolle) war Teil derselben Session und wurde **bewusst nicht umgesetzt**. Die Prüfung der Auswirkungen ergab, dass die Ressource tiefer im Spiel verankert ist als angenommen: Sie trägt die dritte Achse des Tauschdreiecks (§4a), die tragende Lv1-Funktion der Uplink-Station (§4), den einzigen qualitativen Rang-3-Vorteil des Konsuls (§12), zwei Missionsbelohnungen (§8b) und eine Run-Aufgabe (§15). Werkstoffe bleiben vorerst unverändert im Spiel; eine Streichung wäre ein eigenes Vorhaben mit Ersatz für jede dieser fünf Rollen.

### Offene Punkte aus dieser Session

Diese Fragen sind mit den obigen Entscheidungen **nicht** beantwortet und brauchen eine eigene Runde:

| Thema | Frage | Wo |
|---|---|---|
| **Balancing Ratenmodell** | AP-Zufluss pro Runphase, Projektkosten je Gebäudelevel, Bonus-Kurve, Lage des Verfall-Gleichgewichts | §13.5 |
| **Grundwert AP** | Wie hoch ist der Grundwert des gemeinsamen Pools? Nicht die Summe der alten 5 × 6 | §13 „Verfügbare AP" |
| **Early-Game-Tempo** | Erstes Gebäudelevel deutlich günstiger als Folgelevel? | §13.5 |
| **Braucht es Versorgung noch?** | Mit Bauplatz, AP-Rate und Verfall existieren drei Begrenzungen — trägt Supply noch eine eigene Rolle, oder wird es zum reinen Früh-Gate? | §6 |
| **Werkstoffe** | Bleiben vorerst. Falls die Streichung später erneut geprüft wird: Ersatz für Tauschdreieck, Uplink Lv1, Konsul Rang 3, zwei Missionen, Run-Aufgabe 4 | §3 |
| **Bodengarantie** | Mindestanteil je Domäne, oder freie Allokation ohne Untergrenze? | §13.1 |
| **Stratege** | Später neu bewerten und designen — als eigener Pfad oder als Modifikator der anderen? | §13 |

> **GDD-Aufräumen ist erledigt** (2026-08-02): TOC-Anker korrigiert, gestrichene Kapitel §8/§8a ins Archiv ausgelagert, AP-Reste nachgezogen, alle Balance- und TODO-Marker in [Anhang A](#anhang-a--balance--und-todo-index) indexiert. Die dort gelisteten Punkte bleiben offen — sie sind jetzt nur auffindbar.

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

Nouron erzählt die Geschichte einer kleinen Kolonie, die ums Überleben kämpft — nicht die Geschichte eines aufstrebenden Militärstaats. Es gibt keine Armee, keine Flottenschlachten, keine Eskalationsziele. Schiffe dienen Erkundung (Drohne) und Logistik (Frachter); die Korvette schützt die Kolonieumgebung, sucht aber keine Konfrontation.

Gefahren sind klein, lokal und richten sich gegen die Kolonie selbst statt gegen Flotten: Stürme, geologische Instabilität, Seuchenausbrüche (§9). Sie wirken direkt auf den Gebäudezustand — es gibt keinen Gegner-Stärkewert, keinen Kampf, nur einen Zustand vorher und einen danach.

### Vorsorge statt Verbot

> Umformulierung (Juli 2026): Dieses Prinzip lautete ursprünglich "Verteidigung kostet strukturell mehr AP als zivile Aktionen", illustriert an Flottenorders (`attack`/`defend` teurer als `move`/`trade`). Mit der Streichung von Galaxie und Flottensystem (2026-06-20) gibt es keine eigene "defensive" AP-Kategorie mehr im Code. Das Prinzip gilt daher abgeschwächt: **Vorsorge kostet AP, das sonst in Wachstum fließen würde** — nicht als Strafe, sondern als Konkurrenz um denselben Pool.

**Navigation-AP** (Raumfahrer): fließt entweder in ring-gestaffelte Tile-Erkundung (1/2/3 AP je Ring, `colony.explore_cost_per_ring`) oder in den Dispatch von Hangar-Schiffen auf Außenmissionen (`sol_distance × 2` AP zzgl. `sol_distance × 3` Organika). Wer eine Mission entsendet, deckt in diesem Sol weniger neues Terrain auf — eine echte, rein zivile Opportunitätskostenentscheidung.

**Construction-AP** (Baumeister): fließt entweder in Gebäudeausbau (Wachstum) oder in Reparatur beschädigter Gebäude (Vorsorge gegen die Kolonistengefahren aus §9). Ein gut gewartetes Gebäude übersteht ein Ereignis fast unbeschadet, ein vernachlässigtes nimmt Schaden — Reparatur kostet nicht mehr AP pro Punkt als Ausbau, sie konkurriert nur mit ihm um denselben Pool.

### Geltungsbereich: spielweites Prinzip

Jede neue AP-Mechanik wird geprüft: Konkurriert Vorsorge (Reparatur, Wartung, Absicherung) sichtbar mit Wachstum um denselben AP-Pool? Bleibt eine echte Entscheidung ohne Optimalpfad? Eine strukturelle "Verteidigung kostet mehr als Zivil"-Regel existiert im aktuellen Code nicht mehr und sollte nicht ohne eine neue, eigenständige Mechanik wiederbelebt werden — etwa eine künftige Korvetten-Neutralisierung von `terrain_hazard`-Tiles (§4a, Konzept vorhanden, nicht implementiert).

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

Ein **Sol** ist die atomare Zeiteinheit des Spiels. Alle periodischen Spielmechaniken (Ressourcenproduktion, Verfall, Hangar-Lieferungen) werden einmal pro Sol ausgeführt.

**Alle Spielwerte sind in Solen ausgedrückt** — nicht in Echtzeit-Stunden oder -Tagen. Damit skalieren alle Spielmechaniken automatisch, unabhängig davon wie lang ein Sol in Echtzeit dauert.

### Solo vs. Multiplayer

Das Sol-System funktioniert in beiden Modi identisch — was sich unterscheidet, ist wer den Sol auslöst:

**Solo-Modus (primär):** Der Spieler steuert den Sol selbst. Nach dem Setzen aller Befehle löst er den nächsten Sol manuell aus ("Nächsten Sol starten"-Button) — der Sol feuert sofort. Es gibt kein Warten und keine Echtzeit-Begrenzung. "1 Sol" entspricht einem Spielzug, nicht einer Kalenderdauer.

**Multiplayer-Modus (spätere Phase):** Alle Spieler einer Instanz teilen denselben Sol-Rhythmus. Der Sol feuert, sobald alle Spieler ihren Turn bestätigt haben — oder nach Ablauf des konfigurierten Timeouts, damit kein Mitspieler die Instanz dauerhaft blockieren kann. Technische Architektur (Turn-Resolution-Engine, Konfliktauflösung bei exklusiven Zielen, Event-System): siehe `docs/adr/0003-simultan-turn-resolution-multiplayer.md`.

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
- **Tick-Referenzen:** Tick-gebundene Datensätze (z.B. `colony_hangar_missions.dispatch_tick`, `colony_buildings.pending_until_tick`) referenzieren den Counter als einfachen Integer.
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

> **Phase ≠ Schritt:** Die Nummerierung 1–5 (3a) in dieser Tabelle ist eine grobe, spielerorientierte Gruppierung — kein 1:1-Bezug zu den feingranularen Schritt-Nummern in `GameTick.php` (die z.B. bei Supply-Cap „Schritt 7" heißen, §6). Die genaue Schritt-Reihenfolge innerhalb jeder Phase ist in `app/Console/Commands/GameTick.php` (Docblock) kanonisch festgehalten — dort steht auch die maßgebliche Nummer, falls ein anderer GDD-Abschnitt einen konkreten Schritt referenziert.

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

### Knappheitsordnung (Owner-Entscheidung 2026-08-02)

**Verbindlich für jede Balance-Arbeit.** Die drei handelbaren Kolonieressourcen stehen in einer festen Knappheitsreihenfolge. Preise, Produktionsraten und Verbrauchsmengen müssen sie abbilden — sie ist die Vorgabe, nicht das Ergebnis.

| Rang | Ressource | Rolle | Soll-Gefühl |
|---|---|---|---|
| 1 — am verfügbarsten | **Regolith** | Standard-Baustoff | **Soll verfügbar sein.** Bauen darf nicht am Rohstoff scheitern, sondern an AP, Bauplatz und Supply. Knappheit entsteht als Ausnahme, nicht als Dauerzustand. |
| 2 | **Organika** | Verpflegung, Vertrauen | **Seltener als Regolith.** Der Vorrat trägt sich bei ordentlicher Führung, **kann aber bei Missmanagement knapp werden** — dann greift die Hunger→Vertrauen-Spirale (§4a). |
| 3 — am knappsten | **Werkstoffe** | High-Tech-Akzent | **Anfangs sehr begrenzt**, im Spielverlauf zunehmend verfügbar und dadurch belohnend — **bleibt aber dauerhaft knapper als Organika.** |

Daraus folgt zwingend `Preis(Regolith) < Preis(Organika) < Preis(Werkstoffe)` und, für die Produktionsseite, dass Regolith reichlicher zufließen muss als Organika verbraucht wird.

> **Wozu diese Ordnung dient:** Sie wurde festgeschrieben, nachdem ein Balance-Vorschlag die Preise von Regolith und Organika vertauschen wollte — mit dem Argument, die Kolonie überproduziere Organika und leide an Regolith-Mangel. Das war eine zutreffende Beobachtung am **Ist-Zustand**, aber der Ist-Zustand ist das Symptom: Die Produktionsraten passen nicht zur Absicht, nicht die Preise. Wo Beobachtung und diese Ordnung auseinandergehen, ist die Produktionsseite zu korrigieren, nicht die Ordnung.

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
| Relaisvergütung | Nexus zahlt pro Sol eine Vergütung für die Relais-/Sensor-Infrastruktur der Uplink-Station — abhängig vom Uplink-Station-Level |
| Galaktischer Rat | Staatliche Subventionen für aktive Kolonien pro Sol (Arbeitstitel: Name noch offen) |
| Handelsvertrag (Konsul) | Garantierte Bar-Einnahme, sobald ein Konsul zugewiesen ist und die Cantina Lv1+ steht — ≈10/25/45 Cr/Sol je Konsul-Rang (§12, §13) |
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
| 53 | securityHub | Sicherheits-Hub | Security Hub | 3 | CC Lv3 |
| 54 | uplinkStation | Uplink-Station | Uplink Station | 3 | CC Lv2 |
| 55 | tradingPost | Handelsposten | Trading Post | 3 | CC Lv4 |

> **Designentscheidung (2026-06-24) — Agrardom wird Pflichtgebäude vor CC Lv2.** Agrardom war bisher Teil der "Sol-3-Wahlfreiheit" (Cantina/Agrardom/Analytik, alle CC Lv2). Das widersprach der strikten Sol-1/2-Linearität (§16.5): Sol 1/2 garantieren bislang nur Bau- und Erkundungs-AP-Verwendung, keine Ressourcenfluss-Garantie. Ohne Agrardom bliebe Organika auf 0, bis der Spieler — möglicherweise erst Sole nach CC Lv2 — den Agrardom-Pfad wählt; in der Zwischenzeit frisst die Verpflegungsmechanik (§4a "Organika") den nicht vorhandenen Vorrat und der eskalierende Trust-Malus (`TrustService::hungerPenalty`) greift potenziell schon vor der ersten bewussten Wirtschaftsentscheidung. Agrardom wird daher aus der Wahlgruppe herausgelöst und zum **Pflicht-Gate für den CC-Lv2-Ausbau**: Der CC-Levelup-Endpoint prüft zusätzlich zu den AP-Kosten, ob Agrardom ≥ Lv1 gebaut ist. Das ändert nichts an der bisherigen Hint-Logik (`hint_agrardome` lief ohnehin unabhängig von der Wahlgruppe, siehe §16.2 "Agrardom ist unabhängig") — es macht aus einer starken Empfehlung ein hartes Gate.
>
> **Pfadwahl ab Sol 3 (CC Lv2 → Lv4):** Sciencelab, Hangar und Cantina sind alle ab CC Lv2 baubar (Hangar-Gate von CC Lv3 auf CC Lv2 gesenkt), aber **nur eines der drei kann bei CC Lv2 gebaut werden** — die anderen beiden schalten erst bei CC Lv3 bzw. CC Lv4 frei (gestaffelt nach Bau-Reihenfolge, nicht nach Gebäudetyp). Was die drei Pfade inhaltlich sind, steht in **§4b „Die drei Pfade"**; die Slot- und Gate-Mechanik in §13 „Slot-System".
>
> **Sicherheits-Hub (CC Lv3) — optionaler Resilienz-Baustein:** Der Sicherheits-Hub ist **nicht Teil der Pfadwahl-Gruppe** (kein Bau-Gate-Zähler), sondern ein separates Infrastrukturgebäude das ab CC Lv3 gebaut werden kann. CC Lv3 hat **kein Pflichtgebäude** als Voraussetzung (kein Äquivalent zum Agrardom-Gate bei CC Lv2): 90 Regolith + AP-Kosten sind das natürliche Gate.
>
> **Geändert 2026-08-02:** Der Hub war bis dahin zusätzlich das Gate für den Strategen-Slot (Slot 5). Mit der Zurückstellung des Strategen (§13 „Die vier Berater-Typen") entfällt diese Funktion. Der Hub behält seine drei eigenständigen Effekte und ist damit ein rein optionaler Resilienz-Baustein ohne Berater-Kopplung.

> **Harvester (Sondergebäude):** Der Harvester unterscheidet sich von allen anderen Gebäuden: Er steht nicht in der Kolonie-Zone, sondern auf einem Ressourcen-Tile in der Exploration Zone. Er produziert passiv je nach Tile-Typ (Regolith oder andere Mineralien). Er kann verlegt werden (Kosten: 1 Construction-AP **pro Hex Distanz**, keine Ressourcenabzüge; Transit-Zeit: **1 Sol flat**, unabhängig von der Distanz — der Harvester produziert im Transit-Sol nicht). Es gibt genau einen Harvester pro Kolonie. Technisch ist er ein Gebäude mit einer `tile_x/tile_y`-Position statt eines Kolonie-Slots.

> **Designentscheidung Harvester-Transit (2026-06-28):** Eine distanzabhängige Transit-Zeit (z. B. 1 Sol pro 2 Hex) wurde geprüft und **verworfen**. Die AP-Kosten skalieren bereits mit der Distanz (1 AP/Hex) und erzeugen damit das gewünschte Planungs-Druckgefühl. Eine zusätzliche Sol-Staffel wäre eine doppelte Strafe für lange Verlegungen und würde im Transit Reparaturen blockieren (Reparatur kostet Regolith — kein Regolith-Zufluss ohne Harvester), was eine unkontrollierte Decay-Spirale riskiert. Der 1-Sol-Stopp ist ausreichend: 1 Sol ohne Regolith-Produktion (= 8 Rg Opportunitätsverlust) bei gleichzeitig bis zu 5 AP-Kosten bei einer Fünf-Hex-Verlegung. Falls der Playtest zeigt, dass Harvester-Verlegungen zu oft ohne Nachdenken passieren, ist der bessere Hebel die AP-Rate (1 AP/Hex erhöhen), nicht die Sol-Downtime.
>
> **Nachtrag 2026-08-02:** Mit `max_level = 1` (§13.5) wiegt der Transit-Sol relativ schwerer, weil das Grundeinkommen die einzige passive Regolith-Quelle bleibt. Der Verlegungsanreiz sinkt zugleich, da Tile-Ergiebigkeit nicht mehr über Level multipliziert wird — beides im Playtest zu beobachten.

> **Verlege-Vorschau mit Ertragsvergleich (Playtest-Review 2026-07-11):** Der Vorschaupfeil zeigt neben den AP-Kosten auch den Ertragsvergleich aktuelles vs. Ziel-Tile (z. B. „3 AP · 10→15 Rg"). Grund: Der Onboarding-Hint lehrt die Mechanik korrekt, gab dem Spieler aber keine Entscheidungsgrundlage — er jagte ergiebigeren Tiles hinterher und verbrannte Bau-AP. Statt einer paternalistischen Warnung informiert die Vorschau die Abwägung (Catan-Designlinie: Entscheidungen ohne Optimalpfad). Ergänzend nennt der First-Click-Tooltip die Opportunitätskosten („lohnt nur, wenn das Ziel spürbar ergiebiger ist").

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
- Harvester: **kein Level-Up** (Entscheidung 2026-08-02, §13.5). Er liefert ein festes Regolith-Grundeinkommen je Standort; Wachstum kommt aus einer zweiten Instanz (max. 2, §4c), aus Missionen, Events und Handel.

**3. Reparatur (laufender Dauer-Sink):**
- **2 Regolith pro Klick** (+1 SP), zusätzlich zu 1 Construction-AP. Decay läuft bis Run-Ende → Reparatur hält Regolith über den gesamten Run relevant (Errichtungs-/Level-Up-Kosten allein versiegen nach Vollausbau).
- **Hartes Gate:** kein Regolith → Reparatur-Button gesperrt, Tooltip verweist auf Harvester-Reparatur. Kein Negativ-Saldo, kein Schuldensystem.
- **CC + Harvester ausgenommen** (AP-only) → die Regolith-Quelle bleibt immer reparierbar, die Decay-Spirale ist ein erholbarer Rückschlag, kein Hard-Deadlock.

> **Designziel:** Regolith ist das „Mehl" (reichlich, lokal, Dauer-Sink über Bau + Reparatur), Werkstoffe das „Salz" (knapp, importiert, nur als Akzent). Schiffe kosten ausschließlich Credits.

> **Entschieden (2026-06-22):** Ein Resource-Cap-System (Lagerlimit für Regolith/Werkstoffe/Organika) wurde geprüft und **verworfen** — siehe Owner-Entscheidung unter §16 Befund 1. Das Depot-Gebäude (`building_id=30`), das diese Mechanik getragen hätte, ist ersatzlos aus dem Spiel entfernt (Migration `2026_06_22_000001_remove_depot_building.php`). Begründung: Das eigentliche Spielproblem ist Ressourcenknappheit, nicht -überschuss; ein Lagerlimit hätte aktive Produktion bestraft statt belohnt — Widerspruch zum Roguelike-Designprinzip "kein Leerlauf, aktives Spielen wird belohnt". Bei Bedarf (z. B. neue Run-Modifier, die Überschuss als Mechanik nutzen) kann Depot + Cap-System später erneut eingeführt werden.

---

### Sicherheits-Hub (securityHub) — Mechanik

Der Sicherheits-Hub ist ein auf 1 Instanz begrenztes Infrastrukturgebäude (CC Lv3, max. Lv3). Er ist kein Pfadwahl-Kandidat und unterliegt keinem Pfadwahl-Bau-Gate. Seit der Zurückstellung des Strategen (2026-08-02) öffnet er **keinen Berater-Slot mehr** — er trägt sich vollständig über seine drei unabhängigen Effekte:

**Passiv — Vertrauen-Bonus:**
`trust_per_lv = 1` pro Level (Lv1: +1, Lv2: +2, Lv3: +3 Vertrauen kumuliert). Thematisch: "Die Bevölkerung fühlt sich durch Schutzinfrastruktur sicherer." Bewusst niedriger als Bar (+2/Level) und Monument (+2/Level) — Sicherheitsinfrastruktur ist utilitaristisch, kein Wohlfahrtsgut.

**Passiv — Event-Dämpfung:**
Wenn der Hub aktiv ist, werden negative Vertrauensverluste aus Zwischenfällen um **25 %** reduziert (aufgerundet: -3 → -2, -5 → -4). Gilt für die Events `building_level_down`, `encounter_lost` und `colony_threatened`. Implementierungsort: `TrustService` vor dem Anwenden negativer Event-Werte. Thematisch: "Der Hub sorgt nicht dafür, dass Vorfälle ausbleiben — er verhindert, dass sie eskalieren."

**Passiv — Level-Down-Recycling:**
Wenn ein Gebäude durch Decay ein Level verliert, gibt die Kolonie automatisch einen kleinen Ressourcenanteil zurück (handelbare Ressourcen: Regolith, Werkstoffe, Organika). `recycle_pct = 0.10` — 10 % der Baukosten des Gebäudes werden zurückgegeben. Der Wert liegt bewusst deutlich unter dem Reparaturwert, damit kein Anreiz entsteht, Verfall absichtlich zu provozieren.

> **TODO Balance:** Alle drei Effekte (trust-Bonus, Event-Dämpfungs-%, Recycling-%) nach erstem Playtest kalibrieren. Baukosten vorläufig: 80 Rg + 25 Compounds, Supply 8, Decay 0.67. Compounds-Anforderung ist akzeptiert: Hub ist kein Progression-Gate (CC Lv3 hat kein Pflichtgebäude), sondern ein optionaler Resilienz-Baustein. In runs mit schlechtem Trade-Zugang kann der Hub später kommen — das verzögert nichts Zwingendes.

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
Trade-Orders kosten 1 AP weniger (Minimum 0). Nur relevant wenn ein Konsul aktiv ist — dies ist ein Beispiel für einen Domänen-Effizienzbonus (§13.3).

**Passiv — Händlerkonditionen:**
Der Reisende Händler bietet bei Anwesenheit eines Handelspostens bessere Preiskonditionen (+10–15 % Handelswert). Konkreter Wert nach Playtest kalibrieren.

> **TODO Balance:** Genaue Baukosten, Decay und Supply nach erstem Playtest festlegen. Vorläufig: 400 Cr + 200 Rg, Supply 6, Decay 0.67. Handelswert-Bonus muss mit dem Konsul-Rang-System abgestimmt werden (kein Stack-Effekt wenn Konsul Experte + Handelsposten).

---

### Status-Punkte

Jedes Koloniegebäude hat ein `status_points`-Feld. Das Maximum (`max_status_points`) ist in der `buildings`-Tabelle hinterlegt. Status-Punkte sinken pro Sol durch Verfall (siehe Abschnitt 7).

**Leveled vs. Instanced Buildings:**

- **Leveled** — ein Objekt auf einem Tile, wird stufenweise ausgebaut (z.B. CC Lv1→5, Agrardom). Ein Klick auf das Tile → "Ausbauen". (Der Harvester ist seit 2026-08-02 **nicht** mehr leveled — `max_level = 1`, §13.5.)
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

> **Blocker unter Fog — Lücken-Deduktion (Playtest-Review 2026-07-11):** Unaufgedeckte Tiles können `terrain_impassable` sein — der Spieler riskiert beim Erkunden also Nav-AP für ein nutzloses Tile. Das ist ring-abhängig unterschiedlich bewertet und bewusst so entschieden:
> - **Ring 2 enthält keine Blocker mehr** (`ColonyTileService::resolveTileType()`, Gewicht auf Hazard/Empty umverteilt). Grund: Ring 2 hat kein Regolith — eine Lücke in der "bald bebaubar"-Anzeige hätte dort *deterministisch* einen Blocker verraten. Das Aufdecken wäre beweisbar verschwendete AP (Falle ohne Entscheidung), das Nicht-Aufdecken trivial. Beides ist keine interessante Wahl.
> - **Ring 3+ behält Blocker (5 %).** Dort ist eine Lücke in der Anzeige mehrdeutig: meist Regolith (45 % — der Jackpot fürs Harvester-Verlegen), manchmal Fels. Das Aufdecken einer Lücke ist damit eine echte Wette mit positivem Erwartungswert — Information durch Abwesenheit als Feature, nicht als Bug. Keine AP-Erstattung beim Blocker-Fund (würde blindes Aufdecken belohnen und die Ring-Kosten-Drossel entwerten), keine Silhouetten unter Fog (würde die Ring-3-Ambivalenz zerstören).

> **Offener Designpunkt (2026-06, nicht umgesetzt):** Idee, den Erkundungsradius über die aktuelle Ring-3-Grenze hinaus zu erweitern, um zusätzliche Nav-AP-Sinks für spätere Sols zu schaffen (die Ring-Staffelung allein bremst, erschöpft sich aber irgendwann). Offene Sorge: ein größeres/dichteres Hex-Grid wird auf Mobile schwer navigierbar (Pan/Zoom-Aufwand steigt mit der Tile-Zahl). Vorzugsweise die Tile-Zahl von der Nav-AP-Sink-Zahl entkoppeln statt das Grid zu vergrößern — z.B. Signale/Points-of-Interest in größerer Entfernung ohne zusätzliches Hex-Rendering, oder eine Scan/Survey-Order auf Distanz statt physischer neuer Hexes. Nicht implementiert — nur als Richtung für ein späteres Balance-/Pacing-Update vermerkt.

### Visuelle Zone-Abgrenzung

Die Kolonie-Zone-Grenze ist auf kleinen Karten nicht mehr ein sauberer Ring, sondern ergibt sich aus dem `is_colony_zone`-Flag pro Tile. Das Frontend rendert Colony-Zone-Tiles mit einem warmen Basis-Tint (Farbschema: Weiß/Anthrazit/Rot-Palette), Exploration-Zone-Tiles mit einem kühleren, dunkleren Tint. Der Spieler erkennt die Grenze durch Farbe, nicht durch Position. Regolith-Tiles und impassable Tiles innerhalb der inneren Ringe sind immer Exploration Zone — sie wirken als visuelle "Lücken" in der Colony Zone, was die unterschiedliche Funktion deutlich kommuniziert.

### Tile-Typen und Schwierigkeit

Tile-Typen (z.B. "Reicher Erzknoten", "Armes Vorkommen", "Organik-freies Terrain") beeinflussen die Ressourcenproduktion. Die Schwierigkeit eines Runs steuert die Tile-Qualität: schwieriger Run = schlechtere Vorkommen, keine reichen Erznodes in Ring 1.

### Organika

Organika entsteht nicht auf Tiles (biologische Materialien kommen auf Planeten nicht natürlich vor). Stattdessen produziert der **Agrardom** (Gebäude innerhalb der Kolonie-Zone) Organika passiv pro Sol.

Organika wird **nicht** in Bau- oder Schiffskosten verwendet (§3 Verwendungsmatrix). Ihre Sinks (implementiert):

1. **Verpflegung (laufend, eskalierend):** Die Kolonie verbraucht pro Sol Organika proportional zur belegten Supply (`floor(belegte_Supply / 4)`, Config `game.food.supply_per_eater`). Tick-Reihenfolge: Produktion → Verpflegung → Vertrauen (Schritt 3a). Deckt der Vorrat den Bedarf → `well_fed` (+1 Trust, `game.trust.events.well_fed`), Hunger-Streak zurückgesetzt. Reicht der Vorrat nicht → verfügbarer Rest wird verbraucht, `glx_colonies.hunger_streak` wächst, und der **eskalierende** Trust-Malus `−min(2 + (streak−1), 8)` greift (`TrustService::hungerPenalty`) — kein weicher Einmal-Tick, sondern eine Spirale: weniger Vertrauen → Produktionseinbruch → noch weniger Organika. Sättigung setzt den Streak (und damit den Malus) sofort zurück. Macht den Agrardom zum Pflichtgebäude. `floor(used/4)=0` bei sehr kleiner Frühkolonie → kein Verbrauch, kein Bonus.
2. **Missions-Proviant (einmalig):** Hangar-Dispatch (`HangarService::dispatchShip`) kostet beim Start `sol_distance × 3` Organika (Crew-Verpflegung) **und** `sol_distance × 2` Navigations-AP; bei Mangel an beidem wird die Entsendung blockiert. (Config `game.food.mission_organika_per_sol` / `mission_nav_ap_per_sol`.)
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

## 4b. Die drei Pfade

Ab CC Lv2 stehen dem Spieler drei Gebäude offen — **Analytik-Labor**, **Hangar** und **Cantina** —, von denen zunächst nur eines gebaut werden kann. Diese Wahl ist die erste strategische Weichenstellung eines Runs und prägt seinen weiteren Verlauf.

Die Mechanik der Pfadwahl (Bau-Gates, Berater-Slots, Reihenfolge-Auflösung) steht in §13 „Slot-System"; die Kostenbalance in §6 „Pfadwahl-Kostenbalancing". Dieser Abschnitt beschreibt, **was die Pfade sind** — als Designentitäten, nicht als Gate-Logik.

### Was ein Pfad ist — und was er nicht ist

Ein Pfad ist **kein Ausschluss, sondern eine Sequenzierung.** Wer bei CC Lv2 die Cantina baut, bekommt Analytik-Labor und Hangar bei CC Lv3 bzw. Lv4 trotzdem — nur später. Die Wahl bestimmt **Reihenfolge und Zeitvorsprung**, nicht endgültigen Zugang. Das folgt dem Prinzip aus §1.1: keine bestrafenden Permanent-Konsequenzen für frühe Entscheidungen.

Was ein Pfad liefert, ist deshalb ein **Vorsprung von 1–2 CC-Leveln** in seinem Bereich — plus den zugehörigen Berater-Slot, der entsprechend früher besetzbar ist.

### Die drei Identitäten

| | **A — Analytik-Labor** | **B — Hangar** | **C — Cantina** |
|---|---|---|---|
| Berater | Analytiker | Raumfahrer | Konsul |
| Domäne (§13.1) | Wissen | Navigation | Wirtschaft |
| Kernversprechen | *Einmal investieren, dauerhaft profitieren* | *Aktiv arbeiten, breit versorgen* | *Flexibel zukaufen, stabil bleiben* |
| Schaltet frei | alle sieben Kenntnisse (§10) | Drohne, Frachter, Korvette + Außenmissionen (§8b) | Handelsangebote, Verhandlung (§12) |
| Wirkt über | permanente Boni ohne laufende Kosten | wiederholbare Missionen mit laufendem Aufwand | Credits-Umwandlung nach Bedarf |
| Bezahlt mit | AP im Voraus | AP + Organika + Verschleiß, laufend | Credits, laufend |
| Risiko | langsamer Start, Ertrag kommt spät | Logistikaufwand jeden Sol, Schiffe verschleißen | Angebotslage ist nicht vollständig planbar |

**Pfad A — Analytik-Labor.** Der Pfad der dauerhaften Verbesserung. Kenntnisse sind die einzige Progression im Spiel ohne Verfall (§10): einmal erreicht, bleiben sie. Ihr Primäreffekt ist der Supply-Cap-Bonus (§6), also mehr Ausbautiefe. Der Analytiker liefert zusätzlich den stärksten Außenmissions-Bonus des Spiels (eine Kenntnis steigt gratis um ein Level, §13). Wer diesen Pfad zuerst geht, spielt auf Zinseszins: früh teuer, spät mühelos.

**Pfad B — Hangar.** Der Pfad der aktiven Versorgung. Schiffe erschließen den Missionskatalog (§8b) und damit die breiteste Ressourcenbasis im Spiel — Regolith, Organika, Credits, Almanach-Funde und Tile-Aufklärung kommen alle über Missionen. Der Preis ist dauerhafter Aufwand: jede Mission kostet AP und Proviant, Schiffe verschleißen und wollen repariert werden. Wer diesen Pfad zuerst geht, hat früh viele Hebel, muss sie aber jeden Sol bedienen.

**Pfad C — Cantina.** Der Pfad der Flexibilität. Der Konsul bringt mit dem Handelsvertrag eine garantierte Sol-Einnahme (§12), die Cantina selbst gibt Vertrauen (`trust_per_lv`), und der Handel erlaubt, jeden konkreten Engpass gegen Credits zu lösen — statt ihn produzieren zu müssen. Wer diesen Pfad zuerst geht, ist gegen Überraschungen am besten aufgestellt, hängt dafür aber an der Credits-Decke und an der Angebotslage.

### Paritäts-Anforderung: jeder Pfad muss die Grundbedürfnisse decken

**Dies ist die zentrale Designregel der Pfadwahl.** Die Pfade dürfen sich im *Wie* unterscheiden, nicht im *Ob*. Eine Kolonie braucht unabhängig vom gewählten Pfad Regolith, Credits, Organika und Vertrauen. Hat ein Pfad auf eines dieser Grundbedürfnisse keine Antwort, wird der Pfad, der sie hat, faktisch zur Pflicht — und die Pfadwahl zur Scheinentscheidung.

Der Harvester (Regolith) und der Agrardom (Organika) sind der **gemeinsame Sockel**, den jede Kolonie hat. Was ein Pfad beisteuert, ist der Hebel darauf:

| Grundbedürfnis | Sockel (alle) | Pfad A — Analytik | Pfad B — Hangar | Pfad C — Cantina |
|---|---|---|---|---|
| **Regolith** | Harvester, 8/Sol | `geology` steigert die Ausbeute — ⚠️ **fehlt komplett** | `mission_supply_run`, 6,25/Sol je Frachter | Credits→Regolith-Ankauf — ⚠️ **nur Verkaufsrichtung garantiert** |
| **Organika** | Agrardom | `agronomy` — ⚠️ Effekt zu prüfen | `mission_supply_run`, 10 je Umlauf | Ankauf über Bar-Angebote |
| **Credits** | Relaisvergütung, Ratssubvention | ⚠️ **keine eigene Quelle** | Botenflug / Konvoi-Begleitung, 180–200 je Mission | Handelsvertrag 10/25/45 Cr/Sol + Handelsgewinne |
| **Vertrauen** | Gebäude-Boni, Ereignisse | `health` + Krankenstation | `mission_aid_transport` (+2) | Cantina `trust_per_lv` + Handelserfolge |

> **⚠️ Zwei offene Lücken (2026-08-02).** Pfad A hat aktuell **weder** einen Regolith-Hebel **noch** eine Credits-Quelle. Der Regolith-Hebel ist mit dem Harvester-Umbau (§13.5) blockierend geworden — ohne ihn wird Pfad B zur Pflicht, sobald der Harvester nicht mehr levelbar ist. Die Credits-Lücke ist älter und weniger akut (Relaisvergütung und Ratssubvention laufen für alle), sollte aber mitgedacht werden: Ein Analytik-Run finanziert seine Berater heute schlechter als die anderen beiden.
>
> Kandidaten für Pfad A: `geology` → Regolith-Produktionsbonus (Vorschlag +1,5/Level, §13.5); für Credits ein Kenntnis-Effekt, der Kosten senkt statt Einnahmen zu schaffen — das passt besser zum Pfadcharakter „einmal investieren, dauerhaft profitieren" als eine weitere Einnahmequelle.

> **Prüfregel für künftige Mechaniken:** Wird eine neue Ressource, Kosten- oder Bedarfsachse eingeführt, ist zu prüfen, ob alle drei Pfade sie bedienen können. Ist das nicht der Fall, ist entweder die Mechanik anzupassen oder den unterversorgten Pfaden ein Hebel zu geben — **nicht** die Ungleichheit hinzunehmen.

### Der Sicherheits-Hub ist kein vierter Pfad

Der Sicherheits-Hub (CC Lv3) war bis 2026-08-02 als „Pfad D" mit dem Strategen-Slot gekoppelt. Mit der Zurückstellung des Strategen (§13) ist er ein **optionaler Resilienz-Baustein** ohne Berater-Kopplung und ohne Pfadwahl-Gate. Er steht außerhalb dieser Systematik.

---
## 4c. Instanzen oder Level — die Wachstumsachse je Gebäude

Ein Gebäude kann auf zwei Arten wachsen, und die Wahl ist eine Designentscheidung, keine technische. Sie war bisher nirgends begründet, weshalb die Zuordnung im Katalog uneinheitlich ist.

### Die beiden Achsen

| | **Instanz** — mehr davon | **Level** — besser davon |
|---|---|---|
| kostet | ein weiteres Tile | kein Tile |
| Supply | volle `supply_cost` je Instanz | `supply_cost × Level` |
| Instandhaltung | eigene `decay_rate`-Zeile je Instanz | eine Zeile, unabhängig vom Level |
| Sichtbarkeit | **die Kolonie wächst sichtbar** auf dem Hex-Grid | eine Zahl steigt |
| Entscheidung | *wohin* — Platzierung, Nachbarschaft, Tile-Typ | *wie weit* — nur die Höhe |
| Kostenverlauf | linear | steigend (`f(L)`, §13.6) |

Instanzen bedienen damit die **Breiten-Achse** (Bauplatz + Instandhaltung), Level die **Tiefen-Achse** (Supply-Cap) — siehe §6 „Die drei Begrenzungsachsen".

> **Grundsatz (Owner, 2026-08-02): Im Zweifel Instanz.** Instanzen sind auf dem Hex-Grid sichtbar, erzeugen eine Platzierungsentscheidung und binden das Wachstum an die 15 Koloniefelder — also an das „kleine Kolonie"-Prinzip aus §1. Level sind unsichtbar und erzeugen keine räumliche Entscheidung. Ein Level-Up muss sich rechtfertigen; eine Instanz nicht.

### Der Test

**Ergibt „zwei davon" in Fiktion und Mechanik einen Sinn?**

- **Ja** → Instanz. Die Kolonie hat mehrere Wohnhabitate, mehrere Kuppeln, mehrere Hallen.
- **Nein, weil das Gebäude die Kolonie als Ganzes repräsentiert** → Level. Es gibt eine Kommandozentrale, eine Funkanlage, einen Handelsposten.

Ein Level-Up ist zusätzlich gerechtfertigt, wenn die Stufe **etwas Bestimmtes freischaltet** statt nur eine Zahl zu erhöhen — beim Analytik-Labor sind die Level die Kenntnis-Stufen, beim Hangar die Schiffsklassen.

### Zuordnung

| Gebäude | Achse | Deckel | Begründung |
|---|---|---|---|
| **Kommandozentrale** | Level | Lv5 | Eine pro Kolonie, per Definition. Die Level tragen die Progressionsgates des gesamten Spiels. |
| **Harvester** | **Instanz** | **2** | Mehrere Abbaurigs auf mehreren Regolith-Tiles. Bewusst knapp gedeckelt — siehe unten. |
| **Wohnhabitat** | Instanz | 6 | Unverändert. |
| **Agrardom** | **Instanz** | offen | Mehrere Kuppeln; Nahrungsproduktion skaliert natürlich mit der Anzahl. Umstellung von Level auf Instanz. |
| **Hangar** | **Instanz + Level** | Instanzen offen, Lv3 | Der einzige Fall, der beide Achsen braucht — siehe unten. |
| **Analytik-Labor** | Level | Lv3+ | Die Level **sind** die Kenntnis-Stufen (`cartography` Lv1, `geology`/`trade` Lv2, `defense` Lv3). Ohne sie bricht die Staffelung weg. |
| **Uplink-Station** | Level | Lv3 | §4 nennt sie „das einzige Kommunikationsgebäude der Kolonie". Eine zweite Funkanlage verdoppelt keine Reichweite. |
| **Sicherheits-Hub** | Level | Lv3 | Eine pro Kolonie. |
| **Handelsposten** | Level | Lv3 | Eine pro Kolonie. |
| **Cantina** | Level | offen | Zwei Kneipen in einer Kleinkolonie wirken falsch; eine bessere Kneipe nicht. |
| **Krankenstation** | Level | offen | Besser ausgestattet, nicht doppelt vorhanden. |
| **Religiöse Stätte** | — | **1 Instanz, Lv1** | Weder Instanzen noch Level. Sie ist ein Bekenntnis, kein Ausbauprojekt. |
| **Kolonialdenkmal** | — | **1 Instanz, Lv1** | Dito. Ein Denkmal, fertig oder nicht. |

### Harvester: wenige Instanzen, dafür beweglich

**Deckel: 2 Instanzen.** Die ersten ~20–30 Sole muss **einer** reichen; danach kommt höchstens einer dazu. Regolith kommt zusätzlich über Missionen, Events und Handel (§3, §13.7) — der Harvester ist der Sockel, nicht die Skalierung.

Der Harvester ist das einzige **bewegliche** Gebäude des Spiels (§4 „Harvester-Transit"), und diese Eigenschaft soll im Spielverlauf tatsächlich genutzt werden: **Ein Harvester wird pro Run mehrfach umgesetzt.** Dafür braucht es einen Grund, der zwingt statt nur einlädt.

**Erschöpfung der Vorkommen.** Ein Regolith-Tile trägt einen Harvester eine begrenzte Zeit, dann sinkt der Ertrag. Die Grundlagen dafür sind bereits angelegt:

- `colony_tiles.resource_max` — im Schema beschrieben als „Startwert (Basis für Erschöpfungs-Counter im UI)"
- drei Ergiebigkeitsstufen `regolith_rich` / `regolith_normal` / `regolith_poor` mit unterschiedlichem Vorkommen
- die Verlege-Vorschau mit Ertragsvergleich („3 AP · 10→15 Rg", Playtest-Review 2026-07-11)
- Verlegekosten: 1 AP je Hex Distanz, 1 Sol Transit ohne Produktion

Damit entsteht die gewollte Schleife: fördern → Ertrag sinkt → Umzug lohnt → ein Sol Produktion und einige AP kosten → neues Tile. **Und Erkundung bekommt einen konkreten wirtschaftlichen Zweck**, weil man wissen muss, wo das nächste ergiebige Tile liegt, *bevor* der Umzug erzwungen ist.

> **⚠️ Offen:** Erschöpfungsrate und Ertragskurve je Tile-Stufe. Zielbild: ein Tile trägt ~15–25 Sole, sodass es über einen Run zu mehreren Umzügen kommt, ohne dass Umziehen zur Daueraufgabe wird. Gehört in dieselbe Herleitung wie der Regolith-Zahlensatz (§13.7) — die Harvester-Grundproduktion dort setzt einen *frischen* Standort voraus.

> **Später, noch nicht durchdacht:** Zusätzliche **Expeditionskarten** neben der Koloniekarte wurden angedacht. Sie würden dem Erschöpfungs-Kreislauf mehr Raum geben, sind aber nicht ausgearbeitet und stehen nicht auf der Roadmap.

### Hangar: der einzige Fall mit beiden Achsen

Der Techtree gatet Schiffe bereits über **Hangar-Level** — Drohne Lv1, Frachter Lv2, Korvette Lv3, dazu `defense` ab Hangar Lv2. Die Config macht den Hangar aber instanziert, wo `max_level` die Instanzzahl bedeutet. Nach dieser Lesart hieße „Hangar Lv2" schlicht „zwei Hangars", was mechanisch funktioniert, aber thematisch nichts erklärt: Warum erlaubt eine zweite identische Halle den Bau eines Frachters?

**Auflösung — beide Achsen, mit getrennter Bedeutung:**

| Achse | bedeutet | Deckel |
|---|---|---|
| **Instanzen** | Schiffsplätze — wie viele Schiffe die Kolonie halten kann | offen, supply-begrenzt |
| **Level** | Schiffsklasse — Lv1 Drohne, Lv2 Frachter, Lv3 Korvette | Lv3 |

Beides ist intuitiv: Eine Halle fasst ein Schiff, eine größere Halle ein größeres. Die primäre Wachstumsachse bleibt damit die Instanz (Grundsatz oben), das Level ist ein kleines, dreistufiges Freischalt-Gate.

### Technische Voraussetzung: `max_level` ist überladen

`max_level` bedeutet heute **zweierlei**: bei instanzierten Gebäuden die maximale Instanzzahl (Config-Kommentar beim Wohnhabitat: „max 6 instances"), bei allen übrigen das maximale Level. Ein Gebäude kann deshalb aktuell **nicht beides** haben — was den Hangar-Widerspruch überhaupt erst erzeugt.

**Aufzuteilen in `max_instances` und `max_level`.** Beide nullable; `NULL` heißt jeweils unbegrenzt. Betroffen: `buildings`-Tabelle, `config/buildings.php`, `data/sql/testdata.sqlite.sql`, `SyncConfig`, `ColonyController::placeBuilding`, Techtree-Gates.

> **⚠️ Vorher zu prüfen: der Instanz-Decay-Verdacht.** `GameTick::processBuildingDecay()` schreibt mit `['colony_id', 'building_id']` ohne Instanz-Unterscheidung. Verfallen instanzierte Gebäude dadurch superlinear, wird **jede** Umstellung auf Instanzen sofort bestraft — und dieser Abschnitt stellt zwei Gebäude um. Verifizieren, bevor umgestellt wird, nicht danach (ROADMAP Phase 3o, Stufe 1c).

---

## 5. Ressourcenproduktion

### Mechanik

Einmal pro Sol produziert jedes aktive Produktionsgebäude in jeder Kolonie Rohstoffe. Die produzierte Menge ist die **kumulierte Glockenkurve** bis zum aktuellen Level (nicht Level × Flat-Rate, siehe Balance-Anpassung 2026-07-20 unten):

```
produzierte Menge = Σ curve[1..aktuelles Level]
```

### Produktionsgebäude (Phase 3)

| Gebäude | building_id | Ressource | resource_id | max_level |
|---------|-------------|-----------|-------------|-----------|
| Harvester | 27 | Regolith | 3 | 8 |
| Agrardom | 41 | Organika | 5 | 8 |

> **Balance-Anpassung (2026-07-20, GDD §18 Credit-Ökonomie-Ticket):** Die ursprüngliche flache Rate (`×10/level`, unbegrenzte Level) wurde durch eine **Glockenkurve mit festem Deckel (max_level=8)** ersetzt. Grund: Owner-Feedback im Playtest — Grundproduktion war zu knapp, aber ein einfacher linearer/exponentieller Anstieg widerspricht der Frontier-Logik (jedes Level soll spürbar, aber nicht grenzenlos lohnend sein) und ein unbegrenzter Ausbau mit abflachendem Ertrag wäre bei den (level-unabhängig) flachen Levelup-Kosten (10 AP + 10 Regolith, unabhängig vom Zielevel) nie eine echte Entscheidung geworden — der Grenzertrag wäre monoton gesunken, ohne dass je ein Stopp erzwungen wird. Ein harter Deckel erzeugt stattdessen echten Bedarf ("wohin als Nächstes investieren?") — Wachstum über Lv8 hinaus kommt nur noch über Kenntnisse/Missionen/Handel (Amplifikator-Prinzip, siehe §18).
>
> Harvester peakt breit in der Mitte (Lv3-4) — Regolith wird über den ganzen Run in Schüben gebraucht (CC-Upgrades, Pfadgebäude, Reparatur). Agrardom peakt früh (Lv2-3) — Organika/Nahrungssicherheit muss schnell stehen, bevor die Hunger→Trust-Spirale greift; die Kurve bleibt danach bewusst flacher als beim Harvester, damit die Hunger-Mechanik (einzige "weiche" Verlustspirale des Spiels) nicht entwertet wird. Kein Level liefert 0 Zusatzertrag — Ausbau bleibt bis Lv8 immer lohnend, nur graduell weniger.

> **UI-Anforderung:** Der Grenzertrag des nächsten Levels muss vor dem Levelup sichtbar sein (analog AP-Cost-Chip-Convention) — Spieler soll entscheiden können, ob sich z.B. Lv6→Lv7 noch lohnt, bevor er investiert. **TODO Implementierung:** Techtree-UI (`technology.blade.php`) zeigt das aktuell noch nicht an.

> **Designentscheidung (unverändert):** Der Harvester produziert Regolith (lokaler Rohstoff), nicht Werkstoffe. Werkstoffe sind veredelte Industriegüter die nicht vor Ort herstellbar sind — sie kommen ausschließlich über Handel, KI-Händler und Events (§3).

> **Harvester-Produktion (Phase 4+):** Geplant ist eine zusätzliche tile-abhängige Rate mit Tile-Boni (z.B. "Reicher Erzknoten" = +50%) und gradueller Erschöpfung, obendrauf auf die Glockenkurve — nach weiterem Playtest evaluieren.

### Konfiguration

`config/game.php → production_curve`:

```php
'production_curve' => [
    27 => [3 => [1=>8, 2=>10, 3=>12, 4=>12, 5=>10, 6=>8, 7=>6, 8=>4]],   // harvester   → Regolith
    41 => [5 => [1=>8, 2=>12, 3=>12, 4=>9,  5=>7,  6=>5, 7=>3, 8=>2]],   // bioFacility → Organika
],
```

Kumulierte Gesamtwerte bei max_level=8: Harvester **70** Regolith/Sol, Agrardom **58** Organika/Sol.

Neue Produktionsgebäude können ohne Code-Änderung ausschließlich durch Erweiterung dieser Config hinzugefügt werden — dabei jeweils `max_level` in `config/buildings.php` setzen, sonst läuft die Kurve unbegrenzt am letzten definierten Wert weiter (Deckel via `GameTick::cumulativeCurveYield()`).

---

## 6. Supply-System (Cap-Modell)

### Modell

Supply ist **kein fliessender Pool**, sondern ein **Kapazitätsdeckel** (Cap-Modell). Kenntnisse erhöhen den Cap. Gebäude (außer CC und Wohnkomplex) belegen Supply dauerhaft. Berater belegen **kein** Supply — sie kosten Credits. **Schiffe belegen kein Supply** — die Flottensize wird durch Hangar-Slots und Tiles begrenzt (siehe unten). Es gibt keine Sol-basierte Supply-Generierung.

```
supply_cap    = CC-Level × 10 + Anzahl-Wohnkomplexe × 8 + Σ(Kenntnisse-Cap-Bonus)
laufende_last = Σ(Gebäude-Level × supply_cost)
freies_supply = supply_cap − laufende_last
```

> **Korrigiert 2026-08-02:** Die Formel lautete zuvor `laufende_last = Σ(Gebäude-Kosten)` und las sich als Pro-Gebäude-Wert. **Der Code multipliziert mit dem Level** — `SUM(cb.level * COALESCE(b.supply_cost, 0))` in `ResourcesService::getSupplyBreakdown()`, `GameTick.php` und `ValidateColony.php`. Der Abschnitt „Supply im Sol" weiter unten hatte es bereits korrekt. Die Unterscheidung ist nicht kosmetisch: **Supply begrenzt Ausbautiefe, nicht Gebäudeanzahl** — davon hängt ab, welche Rolle Supply im Gesamtmodell trägt (siehe „Die drei Begrenzungsachsen").

### Die drei Begrenzungsachsen

Die Koloniegröße wird von drei unabhängigen Achsen begrenzt. Jede hat eine eigene Währung, in der bezahlt wird — das ist der Catan-Zuschnitt aus §1.2: kein Optimalpfad, jede Strategie hat ihren eigenen Preis.

| Achse | begrenzt | wird bezahlt mit |
|---|---|---|
| **Breite** — Anzahl Gebäudetypen | Bauplatz (15 Tiles) | Instandhaltung: Σ `decay_rate` in AP **und** 2 Rg je SP, jeden Sol |
| **Tiefe** — Summe der Gebäudelevel | **Supply-Cap** | — (reines Cap, kein laufender Abfluss) |
| **Tempo** | AP-Rate (§13.2) | die 100-Sol-Uhr (§18.4) |

Daraus folgt die Strategie-Abwägung:

| Strategie | wird billig | wird teuer |
|---|---|---|
| **Breit** (viele Gebäude auf niedrigem Level) | AP (Errichten kostet nur die halben Levelup-Kosten, §13.3), Supply (nur 1 Level je Gebäude) | Bauplatz, Instandhaltung (Decay zählt pro Gebäudetyp, level-unabhängig) |
| **Tief** (wenige Gebäude hoch) | Bauplatz, Instandhaltung | AP (Kostenkurve wächst mit dem Level), Supply-Cap (Level × `supply_cost`) |

> **Warum die Spreizung der `supply_cost`-Werte trägt:** Die Nennwerte liegen bei 2–10, aber weil sie mit dem Level multipliziert werden, ist die effektive Spreizung weit größer. Produktionsgebäude sind supply-billig (2/Level), Dienstleistungsgebäude teuer (8–10/Level). „Analytik-Labor Lv3" (24) bindet so viel Cap wie „Harvester Lv8 + Agrardom Lv4" (16 + 8). Das ist die eigentliche Kompositionsentscheidung des Supply-Systems — sie war nur durch die zweideutige Formel oben nicht sichtbar.

> **Geprüft und verworfen (2026-08-02): Supply streichen.** Nach der AP-Zusammenlegung stand die Frage im Raum, ob Supply neben Bauplatz, AP-Rate und Verfall noch eine eigene Rolle trägt. Die Prüfung ergab: ja, und zwar die einzige, die die **Tiefe** begrenzt. Zusätzlich hängen vier weitere Mechaniken daran — die Verpflegung (`food_need = intdiv(usedSupply, 4)`, §4a; Supply ist der Bevölkerungsskalar, an dem die Hunger→Vertrauen-Spirale hängt), das **Wohnhabitat** (`supply_cap 8`, sonst keinerlei Funktion — ohne Supply ein leeres Gebäude), der **Supply-Cap-Bonus als Primäreffekt aller sieben Kenntnisse** (§10), und der CC-Ausbau. Supply bleibt unverändert.

> **Design-Entscheidung (2026-06-08):** Schiffe wurden aus der Supply-Last entfernt. Begründung: Schiffe sind räumlich getrennt von der Kolonie (externe Flotte), thematisch eigenversorgt, und bereits durch Hangar-Slots + Tile-Budget begrenzt. Supply als zweiter Limiter war redundant und thematisch inkonsistent. Flottenausbau wird weiterhin gebremst durch: Credits (Nexus-Kosten), Lieferzeit, und Navigator-AP.

> **Kolonisten-Framing — vorgezogen (2026-08-02, war „Phase 4+"):** Supply wird als **Kolonisten** dargestellt — „47 Kolonisten im Einsatz / 60 verfügbar" statt „Supply 47/60". Mechanik bleibt identisch (Cap-Modell), nur die UI-Sprache wird konkreter. Implementierungsaufwand: minimal (nur Labels + Tooltips).
>
> **Warum jetzt statt später:** Die Level-Multiplikation ist ohne Framing nicht intuitiv — bei einer abstrakten Zahl „Supply" versteht kein Spieler, warum ein Labor auf Lv3 dreimal so teuer ist wie auf Lv1. Mit Kolonisten ist es selbsterklärend: *ein größeres Labor braucht mehr Leute.* Da die Formel mit dem Ratenmodell ohnehin klargestellt wird, gehört das Framing in denselben Schritt.

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
| Navigation-AP | Außenmissions-Dispatch kostet Raumfahrer-AP (`sol_distance × 2`) — mehr parallele Missionen = mehr AP-Verbrauch |

> **TODO Balance (Playtest):** Prüfen ob Korvetten-Stacking ohne Supply-Limiter auftritt. Falls ja: Credits/Lieferzeit-Werte verschärfen, nicht Supply-Kosten wieder einführen.

**Schiffe haben keinen passiven Decay.** Wartungsdruck entsteht durch aktiven Einsatz (Schiffs-Verschleiß — siehe §7). `colony_ships.status_points` sinkt durch Außenmissionen, nicht durch Zeitablauf.

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
| Schiffe | Verschleiß (`wear_per_sol` aus config/ships.php) | Aktiver Einsatz (Außenmissionen) | Reparatur (1 Construction-AP/Klick) |
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

### Supply im Sol (GameTick Schritt 7 / §2 Phase 3)

`user_resources.supply` speichert den **aktuellen Supply-Cap**. Er wird in `GameTick.php`-Schritt 7 (entspricht der groben Phase 3 „Supply & Ressourcen" in §2) jedes Sols neu berechnet und gesetzt — so spiegelt der Wert immer den aktuellen Gebäudestand wider (z. B. nach einem Level-Down des Wohnkomplexes durch Decay).

Das freie Supply (für Enforcement-Checks) ergibt sich live: `cap − Σ(entity_level × supply_cost)`.

### Abgrenzung der Unterhalts-Mechanismen

| Mechanismus | Was er begrenzt | Zeithorizont | Gegenmaßnahme |
|-------------|----------------|--------------|---------------|
| Supply-Cap | **Summe der Gebäudelevel** (Ausbautiefe) | permanent | CC ausbauen, Wohnhabitate bauen, Kenntnisse erforschen |
| Bauplatz | Anzahl Gebäude (15 Tiles) | permanent | — (harte Grenze) |
| AP | Arbeitsleistung pro Sol | täglich | mehr/bessere Berater, Kostenboni (§13.3) |
| Gebäude-Decay | Stand von Gebäuden; skaliert mit der **Anzahl Gebäudetypen**, nicht mit deren Level | täglich | Reparatur (1 AP + 2 Regolith je SP) |
| Schiffs-Verschleiß | Zustand aktiv genutzter Schiffe | pro Sol auf Außenmission | Reparatur (1 AP/Klick) |
| Berater-Burnout | AP-Kapazität bei Überbelastung | probabilistisch | Erholungsphase abwarten |

> **Korrigiert 2026-08-02:** Die Zeile für den Supply-Cap lautete „Anzahl Schiffe + Gebäude" — beides falsch. Schiffe kosten seit dem 2026-06-08 kein Supply, und begrenzt wird die Summe der Level, nicht die Anzahl. Auch der Schlusssatz „Diese drei Mechanismen" passte nicht zur fünfzeiligen Tabelle.

Die Mechanismen sind bewusst unabhängig voneinander — mit einer Ausnahme, die **keine** ist: Decay und Bauplatz greifen beide an der Breite an (siehe „Die drei Begrenzungsachsen" oben). Das ist gewollt: Breite kostet einmalig Bauplatz und dauerhaft Instandhaltung, Tiefe kostet einmalig AP und dauerhaft nichts, dafür permanent Supply-Cap.

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

> **Schiffe haben keinen passiven Decay.** Schiffs-Verschleiß entsteht durch aktiven Einsatz (Außenmissionen), nicht durch Zeitablauf — siehe §7 "Schiffs-Verschleiß".

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

> Konkrete Werte per Migration in die Stammdaten-Tabelle (`buildings.decay_rate`). **Kenntnisse haben kein Decay-System** — `researches.decay_rate` ist für alle `knowledge_*`-Einträge 0 und wird im Tick-Loop übersprungen (GDD §10). **Schiffe haben keinen Zeit-Decay** — ihr Verschleiß läuft über Außenmissionen (siehe "Schiffs-Verschleiß" unten).

**Minimum:** Jede Entität hat mindestens **5 max_status_points**.

> ⚠️ **Gnadenfrist** (kein Decay für neue Schiffe/Gebäude für X Sole): vorerst nicht implementiert. Kann in einer späteren Phase evaluiert werden.

### Schema (implementiert)

Die folgenden Spalten sind im Schema vorhanden und werden vom Decay-System genutzt:

- `buildings`: Spalten `max_status_points INTEGER` und `decay_rate REAL` — Werte aus `config/buildings.php`; Sync via `php artisan game:sync-techs`
- `colony_buildings.status_points REAL` — aktueller Zustandswert des Gebäudes
- `colony_ships.status_points REAL` — Verschleißzustand des Schiffes (sinkt pro Sol auf Außenmission, nicht durch Zeit)

### Konfiguration

`config/game.php → decay`:

```php
'decay' => [
    // Schiffs-Verschleiß: wear_per_sol steht in config/ships.php je Schiffstyp
],
```

### Designabsicht

Decay erzwingt regelmäßige AP-Investitionen in Wartung. Inaktive Spieler verlieren schrittweise Infrastruktur und Flotte. Die Kombination aus kleiner decay_rate und fraktionaler Akkumulation bedeutet: nichts bricht sofort — aber vernachlässigte Entitäten degradieren stetig.

---

### Schiffs-Verschleiß

> **Status: Implementiert (2026-07-05).** `GameTick::processHangarMissions()` zieht `wear_per_sol` pro Tick von jedem dispatchten Schiff ab.

Schiffe verfallen **nicht durch Zeitablauf**, sondern durch aktiven Einsatz. Der einzige aktive Einsatz ist die **Außenmission** (Hangar-Dispatch, §8b): Für jeden Sol, den ein Schiff im Zustand `dispatched` verbringt, verliert es Verschleißpunkte.

```
Pro Tick, je Schiff mit ship_state = 'dispatched':
colony_ships.status_points -= wear_per_sol (je Schiffstyp, config/ships.php)
```

| Schiffstyp | wear_per_sol | Begründung |
|---|---|---|
| Drohne | 1,5 | Leichtbau, unbemannt — fragil im Dauereinsatz |
| Frachter | 1,0 | Robuster Routinebetrieb |
| Korvette | 0,75 | Gepanzert, auf lange Patrouillen ausgelegt |

**Recall als Schonungs-Entscheidung:** Da Verschleiß pro Sol unterwegs anfällt, spart ein vorzeitiger Rückruf reale SP — Missionsertrag gegen Schiffszustand abwägen. Beim Dispatch selbst fällt kein Verschleiß an (dort wirken bereits Navigation-AP und Organika als Kosten).

**Dispatch-Sperre:** Schiffe unter **25 % SP (5 von 20)** können nicht entsandt werden — erst reparieren. Der Dispatch-Dialog zeigt die Verschleiß-Prognose (`wear_per_sol × sol_distance × 2`, Hin- und Rückweg) als Chip und warnt, wenn die Mission das Schiff unter die Sperr-Schwelle brächte.

**SP ≤ 0 unterwegs:** Die Mission wird automatisch abgebrochen (`state = aborted`), das Schiff kehrt flugunfähig zurück (`docked`, 0 SP), ein etwaiger Missionsertrag entfällt. Eintrag im Kolonieprotokoll (`colony_log`) und im Sol-Report. Schiffe werden **nie zerstört** — ein Totalverlust, der nur über Nexus-Ersatzkauf heilbar wäre, wäre ein Fail-Spiral-Risiko.

**Kein passiver Decay:** Ein gedocktes Schiff verliert keine SP. Das unterscheidet Schiffs-Verschleiß fundamental von Gebäude-Decay — nur Aktivität kostet.

**Reparatur:** Wie Gebäude — **1 Construction-AP → +2 SP** (`REPAIR_SP_PER_AP`), gedeckelt auf 20 (`SHIP_MAX_STATUS`), AP-Chip am Button. Bereits implementiert (`HangarService::repairShip`).

> ⚠️ BALANCE CONCERN: `wear_per_sol`-Richtwerte sind ungetestet. Zielgröße: eine 3-Sol-Mission kostet 2–3 Construction-AP Reparatur (Drohne). Fühlt sich Verschleiß im Playtest wie Rauschen an → Werte ×1,5; frisst er den Construction-Pool → Drohne auf 1,0 senken.

**Reparatur:** Fixkosten pro Klick — **1 Construction-AP → +2 `status_points`** (`REPAIR_SP_PER_AP`), gedeckelt auf `max_status_points` (20). Gleiche Interaktion wie Gebäude-Reparatur (1 Klick = 1 AP), damit sich „Reparieren" spielweit konsistent anfühlt; der AP-Verbrauch wird vorab als Chip am Button angezeigt. Kein spielergewählter AP-Betrag mehr.

> **Offen:** Zusätzliche Credit-Kosten pro Reparatur (`config/ships.php → repair_cost_per_point`) sind im Design vorgesehen, aber noch nicht implementiert — eigener Balance-Task.

> **Designabsicht:** Schiffe, die viel fliegen, brauchen Wartung. Das erzeugt eine natürliche Kosten-Nutzen-Entscheidung: Intensive Missionsnutzung ist teuer in Construction-AP, die sonst in Gebäude fließen könnten.

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
- Der gemeinsame AP-Pool (§13.1) sinkt für die Dauer um den AP-Beitrag dieses Beraters; sein Domänen-Effizienzbonus (§13.3) entfällt ebenfalls
- INNN-Ereignis: „[Name] benötigt eine Auszeit — Kolonie-Kapazität vorübergehend reduziert."

**Rang-Erholungszeiten:**

| Rang | recovery_ticks (Richtwert) |
|------|---------------------------|
| Junior | 15 |
| Senior | 10 |
| Experte | 5 |

Erfahrenere Berater erholen sich schneller — und haben schon durch den `rank_dampener` eine geringere Burnout-Chance.

**`active_ticks`-Reset:** Nach dem Burnout startet der Zähler bei 0. Das bedeutet: Ein Berater der gerade erholt hat, ist für eine Weile sicher. Burnout-Risiko baut sich langsam wieder auf. Kein "ständiger Burnout" ist möglich.

> **Designabsicht:** Burnout ist ein seltenes, aber echtes Risiko, das den Spieler dazu bringt, einen Backup-Plan für den Ausfall eines Beraters zu haben. Experten sind robuster, aber teurer — das macht Rang-Aufstieg strategisch wertvoller als nur "mehr AP pro Sol".

> **Implementierungsstand:** Die Burnout-Wahrscheinlichkeits-Formel ist noch nicht implementiert. `unavailable_until_tick` existiert in der DB und wird gecheckt; die probabilistische Prüfung folgt nach dem ersten Playtest (Phase 4+). Ein `config/game.php → advisors.burnout`-Block existiert bewusst noch nicht — die Richtwerte oben (`base_chance`, `growth_factor`, `threshold`, `rank_dampener`, `recovery_ticks`) sind das Design für die spätere Config, kein fehlender Verweis.

---

## 8. Flotten & Flottenorders · 8a. Systemansicht

> ⛔ **Gestrichen (2026-06-20, „bis auf weiteres") — ausgelagert nach [`docs/gdd/archiv-flotten-systemkarte.md`](gdd/archiv-flotten-systemkarte.md).**
>
> Galaxie- und Systemkarte samt Flottenbewegung und -kampf sind aus dem Spiel entfernt. Beide Kapitel beschrieben keinen aktuellen Spielstand mehr und standen nur noch als Phase-4+-Referenz im Regelteil; sie stehen jetzt vollständig im Archiv.
>
> **Was stattdessen gilt:** Schiffe existieren ausschließlich über den **Hangar** (§8b) inklusive Außenmissionen (Dispatch). Der **Reisende Händler** ist davon unabhängig aktiv und in §12 Handel, Kanal 3 beschrieben.

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

> **Hinweis Namenskollision:** Die "Konsul-Verhandlung" hier ist **risikofrei** — mehr AP kauft einen garantiert niedrigeren Preis, kein Fehlschlag möglich. Nicht zu verwechseln mit der **"Cantina-Verhandlung (Risiko-Handel)"** in §12 Kanal 1 — dort kann die Verhandlung scheitern und das Angebot geht komplett verloren. Zwei unterschiedliche Mechaniken, bewusst unterschiedlich benannt.

**Lieferzeiten Nexus-Anfrage** (Richtwerte — nach erstem Playtest kalibrieren):

| Schiffstyp | Lieferzeit |
|------------|-----------|
| Drohne | 1–2 Sole |
| Frachter | 3 Sole |
| Korvette | 5 Sole |

**Nexus-Kredit** erst ab CC Lv2 verfügbar. Nutzung erzeugt kleinen Trust-Abzug ("Die Kolonisten machen sich Sorgen über wachsende Schulden").

> **Idee (festgehalten 2026-07-04, später konzipieren):** Preis/Qualitäts-Tradeoff beim Nexus-Kauf — Nexus verkauft nicht unbedingt das beste Material. Wahl zwischen "teurer kaufen → guter Status (volle SP)" und "günstiger kaufen → reparaturbedürftig (niedrige Start-SP)". Verzahnt den Credits-Sink mit dem Reparatur-Sink (§7) und der Dispatch-Sperre (<25% SP: billiges Schiff kann nicht sofort auf lange Mission). Noch nicht designt.

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

### Außenmissionen — Missionskatalog

> **Status: Implementiert (2026-07-05)** — `config/missions.php`, `HangarService::dispatchShip()`/`getMissionCatalogFor()`, `GameTick::processHangarMissions()`. `mission_perimeter_patrol` bleibt zurückgestellt bis §9 (Kolonistengefahren) implementiert ist; `mission_ruin_expedition` zahlt vorerst nur die 150-Cr-Belohnung, der Almanach-Unlock folgt mit §17.

Außenmissionen sind der einzige aktive Einsatz von Schiffen (§7 Schiffs-Verschleiß). Jede Mission ist ein ziviler Auftrag — Erkundung, Logistik, Bergung, Schutzdienst. Es gibt keine Gegner und keinen Kampf (§9-Designlinie): Das Risiko einer Mission ist ausschließlich physisch — Verschleiß pro Sol unterwegs und der automatische Abbruch bei 0 SP.

#### Kostenmodell

Beim Dispatch fallen einmalig an (beide Kosten gaten den Start, AP-Chip-Konvention: alle Kosten vorab als Chips am Button):

- **Navigation-AP:** `sol_distance × 2` (`config/missions.php → nav_ap_per_sol`; bereits in `config/game.php → food.mission_nav_ap_per_sol` auf 2 gesetzt, zieht bei Implementierung um)
- **Organika:** `sol_distance × 3` als Proviant & Betriebsstoffe (`organika_per_sol`; gilt einheitlich auch für die unbemannte Drohne — eine Ausnahme würde Drohnen-Missionen zum kostenlosen Optimalpfad machen)
- Einzelne Missionen haben Zusatzkosten (z.B. Hilfsgüter-Fracht), im Katalog vermerkt.

`sol_distance` ist die **einfache Strecke**; die Gesamtdauer beträgt `2 × sol_distance` Sole (Hin- und Rückweg — deckungsgleich mit der Verschleiß-Prognose aus §7). Die Kostenstaffel war ursprünglich gegen den separaten Navigation-AP-Pool kalibriert: Distanz 1–2 (2–4 AP) ohne Raumfahrer machbar, Distanz 3 (6 AP) kostete den kompletten Grundpool, Distanz 4–5 (8–10 AP) setzte einen Raumfahrer voraus.

> **⚠️ Neu zu kalibrieren (2026-08-02):** Mit dem gemeinsamen AP-Pool (§13.1) gibt es keinen eigenen Navigations-Grundpool mehr, gegen den diese Staffel gemessen werden könnte. Die Absicht bleibt gültig — lange Expeditionen sollen über Opportunitätskosten an die Raumfahrer-Progression gekoppelt sein, ohne hartes Gate — aber die konkreten Werte müssen gegen den neuen Grundwert und die Projektkosten neu gesetzt werden (§13.5).

#### Katalog

| Key | Name | Schiff | Dist | Kosten (AP / Or) | Belohnung bei Rückkehr | Verfügbar ab |
|---|---|---|---|---|---|---|
| `mission_courier_run` | Botenflug | Drohne | 1 | 2 / 3 | 60 Cr | sofort |
| `mission_recon_flight` | Erkundungsflug | Drohne | 1 | 2 / 3 | 2 unerkundete Tiles der Exploration Zone aufgedeckt | sofort |
| `mission_deep_survey` | Signalvermessung | Drohne | 2 | 4 / 6 | Tiefenscan eines Signal-Tiles abgeschlossen (`event_type` enthüllt, §4a) | bekanntes Signal-Tile |
| `mission_prospecting_flight` | Prospektionsflug | Drohne | 2 | 4 / 6 | 20–30 Regolith (variabel) | Geologie Lv1 |
| `mission_data_sweep` | Datensammelflug | Drohne | 3 | 6 / 9 | +8 AP Fortschritt auf eine gewählte Kenntnis (§10) — als Projekt-Investition, ohne den AP-Pool zu belasten | Kartografie Lv1 |
| `mission_supply_run` | Versorgungsfahrt | Frachter | 2 | 4 / 6 | 25 Regolith + 10 Organika | Frachter vorhanden |
| `mission_trade_convoy` | Handelsfahrt | Frachter | 3 | 6 / 9 | 180 Cr + Trust-Event `trade_success` (+2, §14) | Handel Lv1 |
| `mission_aid_transport` | Hilfsgütertransport | Frachter | 2 | 4 / 6 + **10 Or Fracht** | Trust-Event `encounter_won` (+2) + 60 Cr Nexus-Prämie | Gesundheit Lv1 |
| `mission_salvage_sweep` | Trümmerbergung | Frachter o. Korvette | 4 | 8 / 12 | 6–10 Werkstoffe (variabel) | Bautechnik Lv1 |
| `mission_escort_convoy` | Konvoi-Begleitung | Korvette | 3 | 6 / 9 | 200 Cr (Nexus-Schutzprämie) | Korvette vorhanden |
| `mission_perimeter_patrol` | Umkreis-Patrouille | Korvette | 3 | 6 / 9 | Nächste Kolonistengefahr (§9) wird eine Ausgangsstufe milder bewertet; verfällt nach 10 Solen | Verteidigung Lv1 |
| `mission_ruin_expedition` | Ruinen-Expedition | Frachter o. Korvette | 4 | 8 / 12 | Almanach-Artikel freigeschaltet (§17, inkl. Lesebonus) + 150 Cr | tiefengescanntes Ruinen-Event-Tile; einmalig pro Tile |
| `mission_long_range_expedition` | Fernexpedition | Drohne | 5 | 10 / 15 | Zufallsfund: 250–400 Cr oder 8–12 Werkstoffe oder 30–45 Regolith | Kartografie Lv3 |

**Schiffsrollen:** Drohne = Information (Tiles, Scans, Daten), Frachter = Güter, Korvette = Schutzdienste und Bergung. Nicht jede Mission steht jedem Schiff offen — das gibt der Akquise-Entscheidung (§8b Akquise-Pfade) strategisches Gewicht.

**Gate-Schema — nur zwei Typen:** Eine Mission ist entweder an **eine Kenntnis mit Mindestlevel** gebunden oder an ein **Ziel-Tile** (Signal-/Ruinen-Tile — die Mission braucht physisch ein Ziel). Keine CC-Level- und keine Gebäude-Gates: Gebäude-Gates wären redundant (jede Kenntnis setzt das Analytik-Labor ohnehin voraus), CC-Gates wären eine dritte, schwer kommunizierbare Bedingungsart. 4 ungegatete Missionen + 2 Ziel-Missionen sind **immer** verfügbar — jedes Schiff hat ab dem ersten Sol sinnvolle Einsätze.

**Pfadwahl-Interaktion (Hangar-first):** Wer den Hangar-Pfad vor dem Analytik-Labor wählt, hat noch keine Kenntnisforschung — die kenntnis-gebundenen Missionen erscheinen ausgegraut mit Bedingung. Das ist Absicht (geprüft 2026-07-04): Hangar-first heißt realistisch Drohne zuerst, und die Drohne ist mit 3 sofort verfügbaren Missionen am besten versorgt; der Frachter hat mit der Versorgungsfahrt eine wiederholbare, Organika-positive Kernmission. Die ausgegrauten Missionen sind die sichtbare Zugkraft, das Labor als zweites Pfadgebäude nachzuziehen — Pfade sind Sequenzierung, kein Ausschluss. ⚠️ Playtest-Beobachtungspunkt: Fühlt sich eine früh gekaufte Korvette vor dem Labor-Bau unterbeschäftigt an, ist der Hebel eine zweite ungegatete Korvetten-Mission (z.B. Umkreis-Patrouille auf „sofort" senken), nicht die Streichung der Gates.

> **Idee (festgehalten 2026-07-04, später konzipieren):** Bar-Begegnungen (Cantina-NPCs) können Missions-Varianten mit verbesserten Boni oder veränderten Parametern anbieten — als Alternative für den Pfad Hangar-first → Cantina-second (vor dem Labor). Ziel: verschiedene Spielweisen gleichwertig halten (Roguelike-Varianz). Noch nicht designt.

**Roguelike-Varianz gratis:** Da pro Run nur eine Teilmenge der Kenntnisse verfügbar ist (§10), fehlen in manchen Runs 2–3 der kenntnis-gebundenen Missionen (Prospektion, Datensammelflug, Handelsfahrt, Hilfsgütertransport, Trümmerbergung, Patrouille, Fernexpedition) — jede Missionsökonomie spielt sich pro Run anders, ohne Zusatzsystem. 6 der 7 Kenntnisse gaten je 1–2 Missionen; Agronomie bleibt frei als Reserve für spätere Missionstypen.

**Kenntnis-Skalierung — Erfahrung senkt den Proviantbedarf:** Eine einzige, spielweite Regel: **Jedes Level der Gate-Kenntnis über dem Mindest-Gate senkt die Organika-Kosten der Mission um 1 pro Distanz-Sol — Minimum 1/Sol.** Beispiel Trümmerbergung (Bautechnik Lv1, Distanz 4): Lv1 = 12 Or, Lv2 = 8 Or, Lv3+ = 4 Or. Navigation-AP skalieren nie (die AP-Staffel ist das Raumfahrer-Progressionsgate, §13). Missionen ohne Kenntnis-Gate skalieren nicht. Begründung: Kostensenkung statt Belohnungserhöhung funktioniert für alle Belohnungstypen einheitlich (auch Tiles, Scans, Almanach — nicht bezifferbar), lässt alle ⚠️-Deckel unberührt (die Credit-Missionen Botenflug/Konvoi sind ungegatet und skalieren gar nicht; der Werkstoff-Durchsatz bleibt zeitgedeckelt, da Distanz/Dauer unverändert), und das Minimum 1/Sol erhält den Organika-Sink. (Config: `organika_scaling_per_level => 1`, `organika_floor_per_sol => 1` in `config/missions.php`.)

**Wiederholbarkeit:** Missionen sind wiederholbar; Ausnahmen: Signalvermessung verbraucht das Signal-Tile, Ruinen-Expedition ist einmalig pro enthülltem Ruinen-Tile. Die natürliche Drossel für alles andere ist die Kostentrias Nav-AP + Organika + Verschleiß (Reparatur: Construction-AP + Regolith, §7).

#### Resolution

- **Rückkehr:** `return_tick = dispatch_tick + 2 × sol_distance`. Die Auflösung läuft im Tick im selben Schritt wie der Schiffs-Verschleiß (§7), **nach** dessen Anwendung — der SP-0-Abbruch (`state = aborted`, kein Ertrag) hat Vorrang. Bei Rückkehr: `state = completed`, Schiff `docked`, Belohnung wird gutgeschrieben, Eintrag im Kolonieprotokoll (`colony_log`) und im Sol-Report.
- **Recall:** Keine anteilige Belohnung, keine Rückerstattung — auch nicht bei sofortigem Abbruch im selben Sol wie der Dispatch (Nav-AP und Organika werden beim Dispatch instant fällig, unabhängig von der tatsächlich zurückgelegten Zeit). Keine Mindestwartezeit vor dem Recall — der Spieler kann jederzeit zurückrufen, verliert dabei aber immer die vollen Dispatch-Kosten. Der Wert des Rückrufs ist gesparter Verschleiß (§7 „Schonungs-Entscheidung") — anteilige Erträge würden systematisches Halbstrecken-Abbrechen zum Optimalpfad machen.
- **Kein Ausgangs-Roll:** Anders als Berater-Außenmissionen (§13) gibt es kein Erfolg/Teilerfolg/Misserfolg-Würfeln — Schiffe haben kein Rang-Analogon, und die Risiko-Achse existiert bereits über Verschleiß + Abbruch. Zufall beschränkt sich auf die Belohnungshöhe der Fund-Missionen (Prospektion, Bergung, Fernexpedition), deterministisch aus dem Run-`rng_seed` (ADR 0003).

> **Geprüft und verworfen (2026-07-04):** Ein zustandsbasierter Missionsausgang (Rückkehr-SP bestimmt Ertragsstufe, analog §9). Da Verschleiß deterministisch ist, wäre der Ausgang beim Dispatch bereits bekannt — kein Risiko, sondern eine Doppelbestrafung langer Missionen (die Fernexpedition kehrt selbst mit vollen Start-SP bei 25% zurück und würde immer „fehlschlagen") plus Vollreparatur-Zwang vor jedem Start. Ein reiner Würfel-Fail wiederum verletzt „Opportunitätskosten statt Strafe" (§1.1): Schiffs-Missionen kosten harte Ressourcen im Voraus — ein Fehlschlag vernichtet Bezahltes. Fehlt im Playtest Spannung, ist der Hebel die Spanne der Fund-Missionen (Loot-Tabellen verbreitern), nicht ein Fehlschlag-Layer.

#### Missionslog

Jede Mission wird in `colony_hangar_missions` gespeichert (`destination` trägt den `mission_key`, Sol-Distanz aus dem Katalog, Zustand `active/completed/recalled/aborted`). Im Hangar-Screen einsehbar; abgeschlossene Missionen zeigen die erhaltene Belohnung.

> ⚠️ BALANCE CONCERN: Trümmerbergung und Fernexpedition sind neben Import und Cantina eine dritte Werkstoff-Quelle. Richtwert-Deckel: max. ~1 Werkstoff pro Missions-Sol Durchsatz je Schiff — der Nexus-Import (§3) muss die schnellere, die Mission die günstigere Option bleiben. Nach Playtest kalibrieren.

> ⚠️ BALANCE CONCERN: Botenflug/Konvoi-Begleitung sind wiederholbare Credit-Quellen. Mit mehreren Drohnen skaliert das (~30 Cr/Sol je Drohne vor Reparaturkosten) — gegen Relaisvergütung und Berater-Upkeep (§13) prüfen; notfalls Prämien senken statt Cooldowns einführen.

> ⚠️ BALANCE CONCERN: Der Milderungs-Effekt der Umkreis-Patrouille überschneidet sich mit dem Almanach-Bonus `encounter_prep` (§17). (Die frühere dritte Überschneidung, die Strategen-Sicherheitsanalyse, entfällt mit der Zurückstellung des Strategen, §13.) Regel: Milderungseffekte stapeln nicht — es gilt maximal eine Ausgangsstufe Milderung pro Gefahr, der stärkste Effekt wird verbraucht.

> ⚠️ BALANCE CONCERN: Erkundungsflug (2 Tiles für 2 AP + 3 Or + 2 Sole + Verschleiß) darf die Ring-Erkundung (1/2/3 AP, sofort) nicht obsolet machen. Er ist als AP-effiziente, aber langsame Alternative für äußere Ringe gedacht. Wirkt er im Playtest dominant → auf 1 Tile senken oder Distanz auf 2 erhöhen.

### UI-Buttons

| Button | Zustand | Funktion |
|--------|---------|---------|
| Nexus anfragen | Leer | Schiffstyp wählen, Akquise-Pfad wählen |
| Entsenden | `docked` | Mission aus Katalog wählen (gefiltert nach Schiffstyp; Chips: Nav-AP, Organika, Dauer, Verschleiß-Prognose, Belohnung; nicht erfüllte Gates ausgegraut mit Bedingungshinweis) |
| Zurückrufen | `dispatched` | Schiff zurückrufen |
| Reparieren | `docked`, SP < max | Repair-Order (Construction-AP) |
| Hangar zuweisen | `pending` | Schiff einem freien Hangar-Slot zuordnen |

### Technischer Stack

Alpine.js + PicoCSS. Carousel-Logik in `public/js/carousel.js`, Styles in `public/css/carousel.css`.

---

## 9. Begegnungen & Gefahren

> **Design fertig, Implementierung ausstehend.** Dieser Abschnitt ersetzt die frühere flottenbasierte Fassung (Konfrontations-Ablauf, Stärkewerte-Vergleich, `config('game.combat')`) vollständig — diese Mechanik existiert seit der Streichung von Galaxie/Systemkarte (2026-06-20) nicht mehr im Code. Begegnungen finden ausschließlich auf der Kolonieoberfläche statt (Hex-Grid, §4a). Es gibt kein Kampfsystem, keine Stärkewerte, keine Schiffe in dieser Mechanik.

Die Kolonie ist keine Festung, sondern eine verwundbare Ansiedlung auf einer kaum erschlossenen Welt. Gefahren haben keinen Marschbefehl und keine Absicht — sie sind lokale Zwischenfälle: Wetter, Geologie, Erschöpfung der Kolonisten. Es gibt keine Konfrontation im militärischen Sinn, nur einen Zustand vorher und einen Zustand danach.

### Grundprinzip: Zustand statt Konfrontation

Statt gegen eine gegnerische Stärke gewürfelt wird, wirkt jede Kolonistengefahr direkt auf den bestehenden Zustand der Kolonie — auf `status_points` betroffener Gebäude (§7) und auf Vertrauen (§14). Es gibt keinen Gegner-Stärkewert; es gibt nur die Frage, wie gut die Kolonie vorbereitet war. Ein Gebäude mit vollen SP übersteht ein Ereignis fast unbeschadet, ein vernachlässigtes Gebäude nimmt deutlichen Schaden. Wartungs-AP wird damit indirekt zur Gefahrenabwehr, ohne dass der Spieler im Moment des Ereignisses aktiv reagieren muss — das belohnt sowohl vorausschauendes aktives Spiel als auch entspanntes passives Spiel mit solider Grundwartung.

**Ausgangsstufen** (SP-Anteil des betroffenen Gebäudes zum Ereigniszeitpunkt):

| SP-Zustand | Ausgang | Trust-Event | Effekt |
|---|---|---|---|
| ≥ 66% | Abgewehrt | `encounter_won` (+2) | kein/minimaler SP-Verlust |
| 33–65% | Beschädigt | `encounter_lost` (-4) | SP-Verlust (Richtwert: 20% von `max_status_points`) |
| < 33% | Kritisch | `colony_threatened` (-5) | SP-Verlust + ggf. sofortiger Level-Down bzw. Instanzverlust (§7-Regeln) |

Damit werden zugleich die in §14 als TODO markierten `game.trust.events.*`-Werte final mit Anwendungsfällen unterlegt statt Platzhaltern. Der Sicherheits-Hub dämpft alle drei Ausgänge weiterhin um 25 % (bestehende Regel, §14).

### Gefahrentypen

| Gefahr | Trigger | Konsequenz | Häufigkeit (Richtwert) | Abschwächung |
|---|---|---|---|---|
| **Sturm** | Zufällig; Basis-Chance/Sol steigt mit Run-Schwierigkeit; trifft 1 (selten 2) zufällige Gebäude der Colony Zone | SP-Verlust nach Ausgangsstufe (Tabelle oben) | ~1× alle 15–20 Sole früh, ~1× alle 10–12 Sole ab Phase 3 (mehr Gebäude = mehr Angriffsfläche) | Hohe SP durch regelmäßige Reparatur |
| **Geologische Instabilität** | Gekoppelt an das Harvester-Tile; Chance steigt mit Solen seit letzter Relocation, sinkt mit Kenntnis Geologie | Produktionsausfall des Harvesters für N Sole (statt zusätzlichem Trust-Malus — kein doppelter Bestrafungseffekt) | seltener, ~alle 20–30 Sole | Kenntnis Geologie senkt Chance; Relocation setzt Zähler zurück |
| **Seuchenausbruch** | Emergent statt rein zufällig: nur möglich bei `hunger_streak ≥ 3` oder Vertrauen < -20, dann Zufallschance/Sol | Supply-Cap oder AP-Generierung temporär reduziert + `colony_threatened` | nur im Vernachlässigungsfall — bei gesunder Kolonie 0% Grundrisiko | Krankenstation (infirmary) senkt Chance/Schwere je Level |

### Vorwarnung & Protokollierung

Ein Ereignis kündigt sich 1 Sol vorher als `colony_log`-Eintrag an (Kategorie „Gefahr", entity-chip auf das betroffene Gebäude/den Harvester verweisend) — analog zum bestehenden Tiefenscan-Vorwarn-Muster, aber ohne AP-Kosten. Das gibt aufmerksamen Spielern ein Zeitfenster für eine Reparatur, bestraft aber niemanden, der die Warnung übersieht. Der Ausgang wird beim Sol-Wechsel als zweiter `colony_log`-Eintrag protokolliert (Abgewehrt/Beschädigt/Kritisch).

**Onboarding:** Beim ersten Kolonistengefahr-Ereignis eines Runs erscheint ein einmaliger Hint ("Gebäude mit niedrigem Zustand sind anfälliger für Zwischenfälle — regelmäßige Reparatur zahlt sich doppelt aus"), analog zu bestehenden One-Shot-Hints. Kein neuer Hint-Mechanismus nötig, nur ein neuer Trigger-Key im bestehenden System.

### Offene Punkte

- Exakte Basis-Chancen/Sol und SP-Verlust-Prozentsätze sind Richtwerte — Kalibrierung nach erstem Playtest.
- Ob Seuchenausbruch als eigenständiges Ereignis oder als Eskalationsstufe des bestehenden Hunger-Malus (§4a Organika) implementiert wird, ist eine Umsetzungsentscheidung für game-developer — design-seitig gleichwertig.

> ⚠️ BALANCE CONCERN: Sturm und Seuchenausbruch können beide `colony_threatened` (-5) auslösen. In einer bereits schlechten Phase (niedriges Trust, viele beschädigte Gebäude) könnte ein Spieler mehrere -5-Treffer kurz hintereinander kassieren — Spiral-Risiko analog zum Hunger-Malus. Nach Playtest prüfen, ob ein kurzer Cooldown zwischen Kolonistengefahren-Ereignissen nötig ist.

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

> **Kosten-Kurve (überarbeitet, Playtest-Review 2026-07-14):** `levelup_costs = [1 => 12, 2 => 20, 3 => 30, 4 => 40, 5 => 50]` (vorher 5/10/18/28/40, Gesamtsumme 101 → jetzt 152 AP). Owner-Befund: Lv1 bei 5 AP war innerhalb eines einzigen Sols erledigt — das widerspricht dem Designziel, dass Kenntnisforschung von Anfang an mehrere Sole beansprucht. Richtwert: Junior-Analytiker (10 AP/Sol, Basis 6 + Rang-1-Bonus 4) braucht jetzt ~2 Sole für Lv1, ~11–13 Sole für Lv5 (inklusive eigener Rangaufstiege bei 10/20 aktiven Ticks). Kurve ist identisch für alle 7 Kenntnisse — keine Differenzierung nötig, da sie sich bereits über `trust_per_lv` und Sekundäreffekte unterscheiden.
> ⚠️ Diese Kurve ist an `game.ap.base`/`advisor.ap_per_rank` gekoppelt — bei Änderung dort erneut gegen die AP/Sol-Rate prüfen, nicht isoliert betrachten.
> **Zusätzlicher Bugfix (2026-07-14):** Die Techtree-UI zeigte bis dahin für jede Kenntnis konstant 3 AP an (Fortschrittsleiste + Ausbau-Button) — ein stiller Off-Sync zwischen dem statischen `researches.ap_for_levelup`-DB-Feld (nur beim initialen Migrations-Seed gesetzt, nie synchronisiert) und den tatsächlichen, gestaffelten `levelup_costs` in dieser Config. Das Serverbackend (`ResearchService::resolveApForLevelup`) verlangte schon immer den korrekten, höheren Wert — nur die UI-Anzeige und die Klick-Grenze der Leiste hingen am veralteten Wert, sodass eine Investition über 3 AP hinaus optisch möglich schien, aber lautlos nichts bewirkte. `TechtreeController` liest den Kenntnis-Kostenwert jetzt dynamisch aus derselben Quelle wie das Backend.

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
| defense | advisor_pilot | −1 Navigation-AP-Kosten für Schutzmissionen (z.B. Umkreis-Patrouille) |
| trade | advisor_trader | +15% Handelsgewinn |
| cartography | advisor_pilot | +1 zusätzlich aufgedecktes Tile pro Erkundung |

> **TODO Design:** Vollständige 7×5-Matrix (alle Kenntnisse × alle Berater) ausarbeiten — nach erstem Playtest, wenn klar ist welche Kombinationen strategisch interessant sind.
>
> **Korrektur (Juli 2026):** Die ursprünglichen Platzhalterwerte für `defense`/`advisor_pilot` ("AP-Kosten für Angriff") und `cartography`/`advisor_pilot` ("Bewegungsreichweite") referenzierten die 2026-06-20 gestrichene Flotten-/Systemkarten-Mechanik (Angriffsorder, Flottenbewegung). Durch zivile Äquivalente ersetzt (Schutzmissions-AP, Tile-Erkundung) — weiterhin nur Platzhalter, keine finalen Werte.

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

> **Ausgelagert:** Dieses Kapitel steht in [`docs/gdd/techtree.md`](gdd/techtree.md) — Entitäten-Übersicht (11.1), Abhängigkeitsregeln (11.2) und Grid-Layout der Techtree-Ansicht (11.3).

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

Der Spieler entscheidet pro Angebot: annehmen oder ablehnen. **Annehmen kostet 1 AP** aus dem gemeinsamen Pool (§13.1) — der Handel konkurriert damit direkt mit Bau und Kenntnissen um dieselbe Kapazität.

**Handelsvertrag (neue, garantierte Einnahmequelle, 2026-07-19):** Beide obigen Angebotstypen erzeugen kein Credits-Einkommen für den Spieler — sie kosten Credits (Kauf) oder sind ressourcenneutral (Tausch). Das war die Kernursache dafür, dass die Kolonie strukturell kein Credits-Einkommen aus Handel ziehen konnte (Playtest-Bot-Befund, PR #218; siehe §18 `task_credit_reserve`). Fix: kein Bar-Angebot im bisherigen Sinn (kein Karten-Slot, keine Annahme, kein AP-Kosten), sondern eine **passive Cr/Sol-Einnahme** — strukturell identisch zur Relaisvergütung (§3): sie fließt automatisch pro Tick, solange ein Konsul der Kolonie zugewiesen ist **und** die Cantina mind. Lv1 gebaut ist. Thematisch vermittelt der Konsul laufende Handelsverträge im Hintergrund; die Kolonie liefert dafür keine Ressourcen. Config-Key-Vorschlag: `game.credits.consul_contract_income_per_rank`, verarbeitet in `GameTick` im selben Schritt wie `nexus_subsidy`/`relay_bonus_per_uplink_level`. Werte nach Konsul-Rang:

| Konsul-Rang | Handelsvertrag-Einkommen |
|-------------|--------------------------|
| Kein Konsul | 0 Cr/Sol |
| 1 — Junior | 10 Cr/Sol |
| 2 — Senior | 25 Cr/Sol |
| 3 — Experte | 45 Cr/Sol |

Ohne zugewiesenen Konsul entfällt diese Einnahme vollständig — **beabsichtigt**, keine versteckte Falle: die Konsul-Entscheidung bekommt dadurch einen echten wirtschaftlichen Gegenwert, den Analytiker und Raumfahrer nicht bieten. Ein Spieler ohne Konsul kompensiert über Uplink-Station-Ausbau oder trägt ein spürbares, aber wegen des hohen Credits-Fail-Schwellenwerts (> 12.000 Cr Schulden, §15) nicht sofort tödliches Defizit.

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
| AP-Beitrag/Sol | — | 10 | 13 | 18 |
| Gäste/Sol | 0–1 | 0–1 | 0–2 | 1–2 |
| Preisrabatt | 0 % | 10 % | 20 % | 30 % |
| Werkstoffe-Bias | ~33 % | ~33 % | ~33 % | 50 % |

> **Geändert 2026-08-02:** Die erste Zeile hieß früher „Economy-AP/Sol" mit Basiswert 6 — seit der AP-Zusammenlegung (§13.1) zahlt der Konsul in den gemeinsamen Pool ein, es gibt keinen separaten Economy-Basiswert mehr.

**Werkstoffe-Bias bei Rang 3:** Der Experten-Konsul hat Marktbeziehungen — bei Credits→Ressource-Angeboten erscheinen Werkstoffe mit 50 % Wahrscheinlichkeit (statt gleichverteilt ~33 %). Das gibt dem Experten-Konsul einen konkreten wirtschaftlichen Vorteil in der knappsten Ressource des Spiels (§3 Werkstoffe nicht lokal produzierbar).

**Cantina-Verhandlung (Risiko-Handel):**

Zusätzlich zu **Annehmen** (feste Konditionen, garantiert, 1 AP) gibt es pro Bar-Angebot einen zweiten Button **Verhandeln** — sichtbar, sobald der Kolonie ein Konsul zugewiesen **und** verfügbar ist (nicht auf Außenmission, `unavailable_until_tick` ist `null` — dieselbe Prüfung wie bei der Angebots-Generierung, siehe `BarService::generateOffersForColony`). Jeder Rang genügt, auch Rang 1 (Junior) — analog zum bestehenden Muster, dass der Junior-Konsul sofort sichtbaren Wert bringt (`trader_discount[1] = 0.10`).

> **Nicht zu verwechseln** mit der "Konsul-Verhandlung" beim Schiffskauf (§8b, Hangar-Screen): dort ist der niedrigere Preis garantiert, hier nicht. Diese Mechanik heißt bewusst anders.

**Ablauf — zwei Schritte (Owner-Entscheidung 2026-07-31, revidiert gegenüber der ursprünglichen Ein-Schritt-Fassung):** Verhandeln führt das Geschäft nicht sofort aus, sondern verbessert bei Erfolg nur die Konditionen des Angebots — der Spieler sieht das Ergebnis und bestätigt danach explizit mit **Annehmen**.

1. Verfügbarkeits- und Ressourcen-Check wie bei Annehmen (Give-Seite muss gedeckt sein — sonst Fehler `bar_offer_insufficient_resources`, kein Würfeln auf ein Geschäft, das ohnehin nicht zustande kommen könnte). Ein bereits verhandeltes Angebot kann nicht erneut verhandelt werden.
2. AP-Kosten werden abgebucht (`ap_cost_negotiate`, höher als `ap_cost_accept`) — unabhängig vom Ausgang.
3. Einmaliger Erfolgs-Wurf, Konsul-Rang-abhängig (`negotiate_success_chance`).
   - **Erfolg:** Die Konditionen des Angebots (`give_amount`/`get_amount`) werden dauerhaft auf die verbesserten Werte aktualisiert (`negotiate_bonus`, gleiche Formel-Achse wie `trader_discount`, s.u.) und das Angebot als verhandelt markiert. Der Handel selbst führt sich **noch nicht** aus — der Verhandeln-Button wird gesperrt, der Annehmen-Button bleibt aktiv und zeigt jetzt 0 AP (die Kosten wurden bereits mit der Verhandlung bezahlt). Erst ein Klick auf Annehmen überträgt die Ressourcen.
   - **Fehlschlag:** Kein Handel. Das Angebot ist **sofort und vollständig verloren** (gelöscht/verfallen) — kein zweiter Versuch, auch kein nachträgliches "Annehmen" zu den alten Konditionen. Die verlorene Chance ist die eigentliche Konsequenz, nicht die AP.
4. **Kein Trust-Malus.** `trade_blocked` (§13/§14) bleibt für einen anderen Fall reserviert (blockierter Handel, nicht gescheiterte Verhandlung) — eine fehlgeschlagene Verhandlung soll bestraft, aber nicht zusätzlich über Vertrauen abgestraft werden, sonst wird der Button nie benutzt.

**Warum die Chance den Preis macht, nicht die AP:** Bei `ap_cost_accept = 1` und max. 2–6 gleichzeitigen Angeboten kann ein Konsul-Halter praktisch jedes Angebot verhandeln, egal wie hoch `ap_cost_negotiate` gesetzt wird — AP war hier nie ein wirksamer Deckel. Der eigentliche Preis ist der komplette Verlust des Angebots bei Fehlschlag.

> **Neu zu prüfen nach der AP-Zusammenlegung (2026-08-02):** Das Argument stützte sich darauf, dass Economy-AP ein eigener Pool mit 6–18 AP/Sol war, der ohnehin nichts anderes zu tun hatte. Mit dem gemeinsamen Pool (§13.1) konkurrieren Handelsgeschäfte direkt mit Bau und Kenntnissen — AP wird damit erstmals zu einem echten Deckel für Vielhandel. Ob `ap_cost_negotiate` dadurch schon von selbst wirkt oder weiterhin die Verlust-Mechanik tragen muss, ist im Handels-Balancing zu prüfen.

| | Rang 1 (Junior) | Rang 2 (Senior) | Rang 3 (Experte) |
|---|---|---|---|
| Erfolgschance | 55 % | 70 % | 85 % |
| Zusatz-Bonus (`negotiate_bonus`) | 10 % | 15 % | 20 % |

Der Zusatz-Bonus wirkt auf dieselbe Achse wie `trader_discount` bei der Angebots-Generierung, aber additiv obendrauf auf das **konkrete, bereits generierte** Angebot (nicht auf einen neuen Wurf): Credits→Ressource-Preis × `(1 − negotiate_bonus)`, Tausch-`get_amount` × `(1 + negotiate_bonus)`. Kein zweites Formel-System — nur eine zweite Anwendung derselben Formel auf ein bestehendes statt ein neu generiertes Angebot.

**Config-Vorschlag** (noch nicht in `config/game.php`, analog zum bestehenden `bar`-Block):

```php
'bar' => [
    // ...bestehende Keys...
    'ap_cost_negotiate' => 3,  // vs. ap_cost_accept=1 — Verhandeln ist teurer, aber AP ist nicht der eigentliche Deckel (s. Fließtext)
    'negotiate_success_chance' => [0 => 0.0, 1 => 0.55, 2 => 0.70, 3 => 0.85],
    'negotiate_bonus' => [0 => 0.0, 1 => 0.10, 2 => 0.15, 3 => 0.20],
],
```

> ⚠️ BALANCE CONCERN: Erwartungswert-Rechnung Rang 2 (70 % Erfolg, 15 % Bonus), Beispiel Credits→Werkstoffe-Angebot bei 2.000 Cr: Erfolg zahlt 1.700 Cr (−15 %), Fehlschlag verliert das Angebot komplett. EV lohnt sich klar bei Angeboten, die man ohnehin eher verwerfen würde ("nice to have, aber teuer") — bei Angeboten für knappe, dringend benötigte Werkstoffe (§3, nicht lokal produzierbar) ist der Verlust des einzigen verfügbaren Angebots teurer als die 15 % Ersparnis wert sind. Genau diese Abwägung ist die Design-Absicht. Kippt, wenn `negotiate_bonus` über ~25 % oder `negotiate_success_chance` über ~90 % gesetzt wird — dann wird Verhandeln zur dominanten Strategie ohne echtes Risiko und Annehmen zum toten Button. Nach erstem Playtest kalibrieren.

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

### Kanal 3: Reisender Händler (selten, hochwertig)

> **Umgezogen aus §8a (Juli 2026):** Diese Beschreibung stand zuvor unter dem "GESTRICHEN"-Banner der (entfernten) Systemansicht — obwohl der Reisender Händler unabhängig davon eine aktive, implementierte Mechanik ist. Implementiert über `MerchantService` + `config/game.php → merchant`; Spawn-Check läuft in `GameTick.php` Schritt 11.

Ein reisender Händler erscheint gelegentlich bei der Kolonie für eine begrenzte Anzahl Sole. Er bietet seltene Waren an — keine Standardressourcen, sondern Shortcuts und Chancen die im normalen Spielverlauf nicht erreichbar sind.

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
| **Information** | Alle noch unerkundeten Tiles der Exploration Zone sofort aufgedeckt (`colony_tiles.is_explored`) | selten |
| **Einmal-Item** | Reparatur-Kit, Vertrauens-Schub, Credits-Notfallkredit | häufig |
| **Exotics** | Platzhalter Phase 4+ | sehr selten |

> **Config-Nacharbeit (nicht GDD — für game-developer/backend-coder):** `config/game.php → merchant.items.information.label` heißt noch **"Systemkarte vollständig"** — ein rein kosmetischer Restverweis auf die 2026-06-20 gestrichene Systemkarte. Geprüft: `MerchantService::applyItemEffect()` setzt bereits korrekt `colony_tiles.is_explored = true` für die Kolonie (Exploration Zone) — die Wirkung ist **nicht** kaputt, nur das Label ist veraltet. Label an die obige Formulierung anpassen (kein Balance-Risiko, reiner Text-Fix).

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

Aktionspunkte (AP) sind die zentrale Handlungswährung in Nouron. Sie begrenzen, wie viel Arbeit die Kolonie pro Sol leisten kann — in Gebäude, Kenntnisse, Erkundung/Missionen und Handel.

Berater sind **individuelle Entitäten** — kein Mengenzähler. Jeder Berater hat einen eigenen Datensatz mit Rang, Aktivitätszähler und Verfügbarkeitsstatus. Der Spieler rekrutiert, benennt und entwickelt konkrete Individuen, keine abstrakten "Personal"-Stapel.

---

### 13.1 Ein gemeinsamer AP-Pool (Entscheidung 2026-08-02)

**Es gibt genau einen AP-Pool.** Die früheren fünf getrennten, nicht mischbaren AP-Typen (`construction`, `research`, `navigation`, `economy`, `strategy`) sind zu einer einzigen Kolonie-Kapazität zusammengelegt.

**Begründung:** Fünf getrennte Pools erzeugen keine Entscheidung. Wenn Forschungs-AP nur für Forschung taugt, gibt es nichts abzuwägen — der Spieler gibt sie aus, weil sie sonst verfallen. Ungenutzte Pools verfallen still (dokumentiert für `economy` und `strategy` in §16), während der begehrte Pool leerläuft. Mit einem gemeinsamen Pool wird jede Ausgabe zu einer echten Allokationsentscheidung: **jeder Punkt in ein Gebäude ist ein Punkt, der nicht in eine Kenntnis, eine Mission oder ein Handelsgeschäft geht.**

**Domänen bleiben als Begriff erhalten** — sie beschreiben, *wofür* AP ausgegeben werden, nicht mehr, *woher* sie kommen:

| Domäne | Verwendung | Zugehöriger Berater |
|--------|-----------|---------------------|
| Bau | Gebäude errichten und ausbauen, reparieren, Schiffsbau | Baumeister |
| Wissen | Kenntnisse vorantreiben | Analytiker |
| Navigation | Tile-Erkundung, Außenmissions-Dispatch | Raumfahrer |
| Wirtschaft | Handelsangebote, Marktgeschäfte | Konsul |

**Berater erhöhen den gemeinsamen Pool** und geben zusätzlich einen **Effizienzbonus in ihrer Domäne** (siehe 13.3) — sie bleiben damit klar unterscheidbar, ohne den Pool zu zersplittern.

**Keine Bodengarantie** (entschieden 2026-08-02). Es wird **kein** Mindestanteil je Domäne reserviert; die Allokation ist vollständig frei.

**Begründung:** AP sind ein **Fluss, kein Bestand** — Locks verfallen zum nächsten Sol, der Pool erneuert sich täglich vollständig. Eine Fehlallokation wirkt damit per Konstruktion maximal einen Sol. Um sich dauerhaft auszusperren, bräuchte es eine permanente Bindung, die es nicht gibt. Die vier plausibelsten Deadlock-Kandidaten wurden geprüft und alle entschärft:

| Kandidat | Deadlock? | Warum nicht |
|---|---|---|
| Kein Regolith → Reparatur gesperrt → Harvester verfällt → noch weniger Regolith | Nein | CC und Harvester sind regolithfrei reparierbar (AP-only, Bootstrap-Ausnahme §4). Die Regolith-Quelle bleibt immer erreichbar. |
| AP in ein Gebäude investiert, dessen Regolith fehlt | Nein | Regolith wird erst beim Abschluss abgezogen (§4). Investierte AP bleiben auf `ap_spend` liegen und werden gültig, sobald Regolith da ist — kein Verlust. |
| Credits auf 0, kein Berater bezahlbar | Nein | Upkeep wird auf ≥ 0 geklemmt, der Verlust läuft über `nexus_debt`. Langsames Ausbluten über viele Sole, kein Lock. |
| Über Supply-Cap → doppelter Decay → Leveldown-Spirale | Fast | Der schmalste Grat im System — siehe unten. |

Hinzu kommt: die **Einstiegskosten jeder Domäne sind winzig** (1 AP erkunden, 1 AP in eine Baustelle, 2 AP ein Angebot annehmen). Es gibt keine Domäne, in die man nicht mit einem einzigen Restpunkt zurückfindet.

Eine Untergrenze würde genau den Allokationsschmerz entfernen, der der Zweck der Zusammenlegung ist — und ein Problem lösen, das die Fluss-Natur des Pools bereits ausschließt.

> **Die reale Gefahr ist die fehlende Obergrenze, nicht die fehlende Untergrenze.** Ein Spieler, der jeden Sol den ganzen Pool in Reparaturen kippt, verliert den Run langsam, ohne es zu merken. Dagegen hilft keine Bodengarantie — nur die Instandhaltungsanzeige im Dashboard (13.4). Sie ist der Ersatz für die Bodengarantie und darf deshalb nicht als Komfort-Feature wegpriorisiert werden.

> **⚠️ Zu ändern: `decay.overcap_factor` von 2.0 auf 1.5.** Bei Überschreitung des Supply-Caps verdoppelt sich aktuell die Instandhaltung — bei ~7 AP/Sol Basislast springt der Anteil damit von 32 % auf 64 % des Pools. **Das** ist der in 13.5 ursprünglich befürchtete „ab Sol 50 steht der Spieler still, ohne die Ursache zu erkennen"-Fall; er entsteht nicht aus dem Verfall, sondern aus diesem Multiplikator. Zusätzlich muss Over-Cap ein **sichtbarer Zustand** sein (Dashboard + INNN-Meldung), nicht ein stiller Faktor, und es muss einen Gegenzug geben — zu prüfen ist, ob freiwilliger Abriss über die UI erreichbar ist (§13 „AP-Verbrauch" nennt „Reparatur/Abbau").

---

### 13.2 Ratenmodell: Handlungen und Projekte

AP werden auf **zwei verschiedene Arten** ausgegeben. Beide ziehen aus demselben Pool.

**Handlungen — sofort, einmalig.** Missionen, Events, Tile-Erkundung, Handelsgeschäfte. Kosten AP im Moment der Auslösung, das Ergebnis tritt sofort (oder nach fester Laufzeit) ein.

**Projekte — investiert über mehrere Sole.** Gebäude und Kenntnisse haben Gesamtkosten in AP, die der Spieler über mehrere Sole hinweg einzahlt. Ein Gebäude ist fertig, wenn die Summe der investierten AP die Projektkosten erreicht. Die Mechanik existiert bereits für Kenntnisse (`AbstractTechnologyService::_invest`) und wird auf Gebäude ausgeweitet.

Damit ist AP nicht primär ein Tagesbudget, sondern eine **Arbeitsrate**. Knappheit entsteht nicht daraus, dass die Punkte ausgehen, sondern daraus, dass ein Run **auf 100 Sole begrenzt** ist (§18.4): Ein Gebäude, das 5 Sole Bauzeit bindet, kostet 5 % des Runs.

**Parallelbau ist erlaubt und ausdrücklich gewollt.** Der Spieler darf beliebig viele Projekte gleichzeitig laufen lassen und pro Sol frei entscheiden, welche er füttert. Die Rate verteilt sich dann entsprechend — mehr Baustellen heißt nicht mehr Durchsatz, sondern längere Einzellaufzeiten. Das ist eine **bewusste Planungsentscheidung** des Spielers, kein Fehler: Er kann Fertigstellungen absichtlich auf denselben Sol legen. (Ob es dafür einen konkreten Spielvorteil geben soll — z. B. gebündelte Vertrauens- oder Milestone-Effekte — ist offen und soll sich aus Playtests ergeben.)

**Gelegenheiten sind durch Verfügbarkeit begrenzt, Projekte durch AP.** Das ist die tragende Regel des Hybrids. Sofortige Handlungen sind für Spieler unter Unsicherheit systematisch attraktiver als aufgeschobene Investitionen — wenn Missionen und Events unbegrenzt AP aufnehmen könnten, würde der Spieler den Aufbau aushungern, ohne es zu merken. Deshalb gilt: Es gibt pro Sol nur eine begrenzte Zahl verfügbarer Missionen und Events. AP-Überschuss hat dann keinen anderen Abfluss als Bauen und Forschen — ohne dass eine bevormundende Regel („max. X % für Missionen") nötig wird.

**Der Late-Game-Kipppunkt ist gewollt.** Gegen Run-Ende lohnt sich kein langfristiges Projekt mehr: AP in ein 5-Sol-Gebäude bei Sol 92 ist verlorene Kapazität. Die letzten ~15 Sole verschieben sich dadurch von Aufbau auf Ausführung. Das ist ein designter Phasenwechsel und gibt dem Run-Ende Charakter — Voraussetzung ist, dass der Spieler ihn kommen sieht (siehe 13.4).

---

### 13.3 Boni: additiv, nie multiplikativ

Boni senken die **AP-Kosten von Projekten** und verkürzen damit die Bauzeit in Solen. Das ist die primäre Progressionsachse des Systems: Ein Gebäude, das früh 5 Sole bindet, ist im Mid- und Late-Game in 2 Solen fertig. Beschleunigung wird als Zeitgewinn spürbar, nicht als größere Zahl in einem Balken.

**Alle Kostenreduktionen wirken additiv.** Berater-Rang, Kenntnis-Level und Koloniereife addieren ihre Prozentwerte, bevor sie einmal auf die Projektkosten angewandt werden. Multiplikative Verkettung ist ausgeschlossen: Sie würde im Late-Game überschießen und Projekte praktisch sofort abschließen, was den Kipppunkt aus 13.2 zerstört.

**Bonusquellen (Vorschlag, siehe 13.6):**

| Quelle | Wert | Maximum |
|---|---|---|
| Domänen-Berater | Rang 1 +5 %, Rang 2 +10 %, Rang 3 +15 % | 15 % |
| Domänen-Kenntnis | +3 % je Level | 15 % |
| Koloniereife | +3 % je CC-Level über 1 | 12 % |
| **Summe** | | **42 %** |

Domänen-Kenntnis-Zuordnung: Bau ← `construction`, Navigation ← `cartography`, Wirtschaft ← `trade`. Für die Domäne **Wissen** gibt es keine passende Kenntnis — dort stattdessen **Analytik-Labor-Level +3 %** (max 15 %). Asymmetrisch in der Art, symmetrisch im Wert, und es gibt dem Laborausbau endlich einen eigenen mechanischen Effekt (heute hat er außer dem Kenntnis-Gate keinen).

Ein **Mindest-Kostenanteil** (`project_min_cost_factor = 0.5`) verhindert, dass Projekte auf null fallen. Bei 42 % Maximalbonus greift er **nie** — das ist Absicht: Er ist eine Leitplanke für spätere Bonusquellen (Events, Missionsbelohnungen, Run-Modifier), keine aktive Regel zum Start. Wichtig, das so zu lesen, damit später niemand gegen einen Deckel kalibriert, der gar nicht wirkt.

**Boni gelten nur für Projekte, nicht für Handlungen.** Dadurch wächst der Handlungsanteil am Pool über den Run relativ an — das späte Spiel verschiebt sich von selbst Richtung Ausführung. Das ist beabsichtigt und trägt den Kipppunkt aus 13.2 mit.

---

### 13.4 Kommandozentrale: Dashboard und Prognosen

Das Ratenmodell ist nur spielbar, wenn der Spieler seine Rate und ihre Verwendung jederzeit sieht. Der Kommandozentrale-Screen wird deshalb zum **Dashboard** ausgebaut. Es ist keine Komfortfunktion, sondern tragende Voraussetzung: Mehr-Sol-Projekte machen nur Spaß, wenn sie planbar sind.

Mindestumfang:

| Anzeige | Zweck |
|---|---|
| AP-Zufluss pro Sol und wohin er aktuell fließt | Grundlage jeder Allokationsentscheidung |
| Restzeit je Baustelle („noch 3 Sole bei aktueller Rate") | Planbarkeit von Projekten, Timing von Fertigstellungen |
| Instandhaltungsanteil („Reparatur bindet 33 % deiner Kapazität") | Macht die wachsende Last aus 13.5 sichtbar, bevor sie drückt |
| **Restertrag bis Run-Ende** je Projekt („Agrardom Lv5: noch 3 Sole, dann 8 Sole × 7 Organika") | Trägt den Late-Game-Kipppunkt (13.2) — siehe unten |
| Regolith-Bilanz (Produktion − Reparatur − Levelups) | Die eigentliche Wachstumsgrenze (13.5) |
| **Over-Cap-Warnung**, wenn die Supply-Last den Cap übersteigt | Ersetzt die stille Verdopplung der Instandhaltung durch einen sichtbaren Zustand (§7) |
| Konzessions-Prognose („bei aktuellem Kurs in 12 Solen unterschritten") | Macht den Fail-State aus §18.2 vorhersehbar statt überraschend |
| Fortschritt der Run-Aufgaben | Verbindet Tagesentscheidung mit Run-Ziel (§15) |

**Zum Restertrag — er trägt den Kipppunkt, nicht die Kosten.** Der Late-Game-Kipppunkt aus 13.2 entsteht nicht dadurch, dass Projekte spät teurer werden, sondern dadurch, dass sich ihr Ertrag nicht mehr amortisiert: Ein Agrardom Lv5, der an Sol 92 fertig wird, liefert noch 8 × 7 = 56 Organika — das ist der Grund, es sein zu lassen. Wenn das Dashboard neben „noch 3 Sole" auch den Restertrag zeigt, entsteht der Phasenwechsel **ohne jede Zahlenänderung**. Ohne die Anzeige müsste man ihn über Kosten erzwingen, was das Fortschrittsgefühl aus 13.3 beschädigen würde.

**Der Instandhaltungsanteil ersetzt die Bodengarantie.** Die eigentliche Selbst-Blockade-Gefahr im Ratenmodell ist nicht, dass ein Spieler eine Domäne aushungert (13.1), sondern dass er jeden Sol den ganzen Pool in Reparaturen kippt und den Run langsam verliert, ohne es zu merken. Dagegen hilft keine Untergrenze — nur Sichtbarkeit. Diese Anzeige ist deshalb kein Komfort-Feature und darf nicht wegpriorisiert werden.

**Zur Konzessions-Prognose:** Run-Ziel (Expertenstab aufbauen) und Fail-State (Konzessionsentzug) ziehen in dieselbe Richtung — Berater kosten Credits und Unterhalt, und genau das kann die Konzessionsbedingungen reißen. Das ist als **Push-your-luck** gewollt: Der Spieler soll abwägen, ob er noch einen Berater einstellt oder Puffer hält. Diese Spannung funktioniert aber nur mit sichtbarer Prognose — ohne sie wird der Spieler für Zielverfolgung bestraft, ohne es zu merken.

---

### 13.5 Instandhaltungslast und die Regolith-Grenze

> **Korrigiert 2026-08-02.** Dieser Abschnitt hieß „Verfallsgrenze als natürliche Koloniegröße" und beschrieb ein AP-Gleichgewicht, ab dem die Instandhaltung den gesamten Zufluss bindet und nichts Neues mehr fertig wird. **Das kann bei den aktuellen Werten nicht eintreten** — es war keine Kalibrierungsfrage, sondern strukturell unerreichbar. Die Grenze existiert trotzdem, nur in einer anderen Währung.

**Warum das AP-Gleichgewicht nicht existiert.** `GameTick::processBuildingDecay()` iteriert über `colony_buildings`-Zeilen und zieht `decay_rate` ab — **unabhängig vom Level**. Ein Harvester Lv8 verfällt exakt so schnell wie ein Harvester Lv1. Weil der Gebäudekatalog 13 Einträge hat, hat die Instandhaltung damit eine harte Obergrenze:

| Ausbaustand | Gebäudetypen | Σ `decay_rate` | AP/Sol | Regolith/Sol |
|---|---|---|---|---|
| Sol 1 (CC + Harvester) | 2 | 1,28 | 1,3 | 2,6 |
| + Agrardom, Wohnhabitat | 4 | 2,67 | 2,7 | 5,3 |
| + 1. Pfadgebäude, Uplink | 6 | 4,34 | 4,3 | 8,7 |
| + 2. Pfadgebäude, Hangar | 8 | 5,96 | 6,0 | 11,9 |
| + Krankenstation, Sicherheits-Hub | 10 | 7,30 | 7,3 | 14,6 |
| **alle 13 Typen** | 13 | **10,30** | **10,3** | **20,6** |

Solange der AP-Pool über ~11 AP/Sol liegt, kann die Instandhaltung den Zufluss nie vollständig binden.

**Was stattdessen gilt: eine wachsende, sichtbare Last.** Der Instandhaltungsanteil steigt über den Run von ~20 % auf ~33 %, bei Vollausbau auf ~43 % des Pools. Das ist spürbarer Gegenwind, kein Stillstand — und es passt besser zum Designprinzip „kein Leerlauf, aktives Spielen wird belohnt" (§1.1) als ein echtes Gleichgewicht, das den Spieler ab Sol 50 einfriert.

**Die eigentliche Wachstumsgrenze ist Regolith.** 20,6 Rg/Sol Reparaturbedarf bei Vollausbau, dazu der Regolith der Level-Ups. Dagegen steht der Harvester mit einem festen Grundeinkommen plus Missionen, Events und Handel. Diese Bilanz — nicht der AP-Pool — entscheidet, wie groß eine Kolonie werden kann. Sie gehört deshalb ins Dashboard (13.4).

#### Harvester: kein Level-Up, höchstens zwei Instanzen (Owner-Entscheidung 2026-08-02)

Der Harvester hat **kein Level-Up**. Er liefert je Standort ein **Grundeinkommen** an Regolith; Wachstum kommt aus einer zweiten Instanz — frühestens nach ~20–30 Solen, Deckel 2 — sowie aus Missionen, Events und Handel. Er ist zugleich das einzige bewegliche Gebäude und soll pro Run **mehrfach umgesetzt** werden, getrieben von der Erschöpfung der Vorkommen. Vollständige Begründung und die Abgrenzung Instanz/Level für alle Gebäude: **§4c**.

Damit wird Regolith von passivem Einkommen zu **aktivem Spiel** — was der Designlinie „kein Leerlauf, aktives Spielen wird belohnt" (§1.1) entspricht, aber die Wirtschaft grundlegend umstellt.

**Die Rechnung:** `game.production_curve` ist additiv pro Level (`27 => [3 => [1=>8, 2=>10, 3=>12, …]]`) — der Harvester produzierte auf Lv5 also 52 Rg/Sol, auf Lv1 sind es **8 Rg/Sol**. Gegen den Reparaturbedarf oben:

| Gebäudetypen | Reparatur Rg/Sol | Bilanz nur mit Harvester Lv1 |
|---|---|---|
| 4 (Sol ~3) | 5,3 | +2,7 |
| 6 (Sol ~10) | 8,7 | **−0,7** |
| 8 | 11,9 | −3,9 |
| 13 (Vollausbau) | 20,6 | −12,6 |

Ab dem sechsten Gebäudetyp — etwa beim zweiten Pfadgebäude — reicht das Grundeinkommen nicht mehr für die Instandhaltung, Level-Ups noch gar nicht eingerechnet. **Die Beschaffung über andere Kanäle ist damit keine Option, sondern Pflicht.**

> ⚠️ **`max_level = 1` ist noch nicht in der Config.** `config/buildings.php` hat weiterhin `max_level => 8` mit einem Kommentar, der die Glockenkurve als Begründung nennt (Entscheidung 2026-07-20). Die Änderung ist mit dem Umbau der Regolith-Kanäle unten zusammen umzusetzen — einzeln würde sie die Wirtschaft brechen.

#### Regolith-Beschaffung: alle drei Pfade müssen gleichwertig liefern

**Verbindliche Anforderung — siehe §4b „Paritäts-Anforderung".** Die Pfade dürfen sich im *Wie* unterscheiden, nicht im *Ob*. Wenn Regolith zur aktiv zu beschaffenden Ressource wird, muss jeder der drei Pfade einen eigenen Hebel mit vergleichbarem Ertrag haben — sonst wird der Pfad, der ihn hat, faktisch zur Pflicht und die Pfadwahl zur Scheinentscheidung.

**Der Harvester ist der gemeinsame Sockel, nicht der Kanal eines Pfades.** Seine 8 Rg/Sol hat jede Kolonie, unabhängig von der Pfadwahl. Was einen Pfad ausmacht, ist der **Hebel obendrauf** — und davon braucht jeder der drei einen mit vergleichbarem Ertrag.

Zielgröße je Hebel: **~6 Rg/Sol** bei vergleichbarem Einsatz. Sie sollen sich nicht in der Menge unterscheiden, sondern im **Kostenprofil** — das ist der Unterschied zwischen drei Wegen und drei Klonen.

| | Quelle | Ertrag | Kostenprofil | Status |
|---|---|---|---|---|
| **Sockel (alle Pfade)** | Harvester Lv1 | 8 Rg/Sol | keine (passiv) | existiert |
| **A — Analytik** | Kenntnis `geology` **steigert die Harvester-Ausbeute** | Vorschlag **+1,5 Rg/Sol je Level** → Lv4 = +6 | einmalig hoch (102 AP bis Lv4), danach **null laufende Kosten** | **fehlt komplett** |
| **B — Hangar** | Frachter dauerhaft auf `mission_supply_run` (25 Rg / 4 Sole Umlauf) | **4,25 Rg/Sol netto** (6,25 brutto − 2,0 Reparatur-Regolith) | laufend: ~2 AP/Sol + 1,5 Organika/Sol | existiert, aber praktisch schwer erreichbar |
| **C — Cantina** | garantierter Credits→Regolith-Ankauf | ~6 Rg/Sol bei regelmäßigem Kauf | laufend Credits (Basispreis 30 Cr/Einheit) + 2 AP je Angebot | halb vorhanden |

Die drei Profile sind bewusst gegensätzlich und ergeben drei verschiedene Spielgefühle:

- **Analytik** verbessert den Sockel selbst — teuer im Aufbau, danach dauerhaft geschenkt, keine Logistik. Wer diesen Pfad geht, baut einmal auf und hat Ruhe.
- **Hangar** legt einen zweiten Strom daneben — billig im Einstieg, aber jeden Sol Aufwand (AP, Organika, Verschleiß). Wer diesen Pfad geht, arbeitet dauerhaft dafür.
- **Cantina** kauft zu — maximal flexibel, aber an Credits und Angebotslage gebunden.

Über 60 Sole gerechnet liegen Analytik und Hangar bei rund 100 AP Gesamteinsatz: der Analytiker zahlt vorne, der Raumfahrer verteilt.

**Die Lücke liegt beim Analytik-Pfad.** `config/knowledge.php` enthält **keinen einzigen Produktionsbonus**. `geology` hat `trust_per_lv => 0` und außer den Levelup-Kosten keinerlei Effekt; der Supply-Cap-Bonus ist der einzige implementierte Kenntniseffekt überhaupt. `geology` ist der thematisch richtige Träger (Gate: Analytik-Labor Lv2 + Harvester Lv1) und braucht diesen Effekt ohnehin — bisher ist die Kenntnis mechanisch leer.

**Zum Cantina-Pfad — die Diagnose war umgekehrt.** Eine frühere Fassung dieses Abschnitts behauptete, die Ankaufsrichtung sei nicht garantiert. Gegen `BarService::buildOffer()` geprüft gilt das Gegenteil: **Die Credits→Ressource-Kaufrichtung existiert und ist mit 60 % der Angebote der Regelfall. Die Verkaufsrichtung existiert überhaupt nicht** — es gibt keinen Angebotstyp, bei dem der Spieler eine Ressource gibt und Credits bekommt. (Der Code-Kommentar in Zeile 305 sagt das Gegenteil des Codes darunter.)

Das eigentliche Problem sind die **Losgrößen**: `rand(1,5) × 10` Einheiten ergibt einen Erwartungswert von **~1.400 Cr pro Angebot** — gegen ein Netto-Einkommen von +5 Cr/Sol nach Berater-Upkeep (§18.4). Das „Not enough resources." ist kein Bug in der Bestandsprüfung, sondern eine Fehlkalibrierung um eine Größenordnung.

**Der tragfähige Hebel für Pfad C ist deshalb nicht der Credits-Kauf, sondern der Tausch.** Der Tauschtyp (40 % der Angebote) bepreist wertäquivalent — Organika → Regolith liefert bei 10–30 Or rund 17–50 Rg. Das ist genau der Pfadcharakter „Überschuss in Mangel wandeln", und es umgeht die kaputte Credits-Ökonomie vollständig. Give- und Get-Ressource werden heute allerdings gleichverteilt gewürfelt, sodass Or→Rg nur etwa 6,7 % der Angebote trifft — bei 0–2 Gästen pro Sol also eines alle 10–15 Sole.

Vorschlag: **Losgröße an die Zahlungsfähigkeit binden** (höchstens ~35 % des Bestands) **und die Tauschrichtung nach Bestand wählen statt zu würfeln** — Give = Ressource mit dem größten Überschuss, Get = die knappste. Der Zufall bleibt in Preisvarianz, Gästezahl und Gültigkeitsdauer erhalten; er verlagert sich von „welches Angebot?" auf „wie günstig, und kommt heute jemand?". Das ist die planbarere und damit bessere Unsicherheit.

> **⚠️ Offen — Zahlen und Umsetzung.** Die +1,5 Rg/Sol je `geology`-Level sind ein erster Ansatz, kalibriert auf Parität mit dem Frachter-Kanal. Zu prüfen ist, ob der Analytik-Pfad damit insgesamt zu stark wird — er trägt zusätzlich den Supply-Cap-Bonus **und** den Domänen-Effizienzbonus (13.3), leistet also dreifach. Falls ja: auf +1,2/Level senken statt einen der anderen Effekte zu beschneiden.

> **⚠️ Der Sockel ist zu niedrig — die Hebel sind nicht das Problem (Befund 2026-08-02).** Eine Gegenrechnung von der Bedarfsseite ergibt für die Zielkolonie aus §13.6 über 80 Sole rund **1.454 Rg Bedarf** (530 Errichtungen + 284 Level-Ups + 640 Reparatur) gegen **840 verfügbar** (200 Start + 8/Sol). Lücke ≈ 614 Rg. Schlimmer noch: Der Bedarf ist ungleich verteilt, die **Spitze liegt bei 15–18 Rg/Sol in den Solen 21–60** — während der zweite Pfad erst bei CC Lv3 (~Sol 30) und der dritte bei CC Lv4 (~Sol 50) dazukommt, also *nach* der Spitze. Mit einem Hebel ist die Zielkolonie bei Sockel 8 nicht baubar.
>
> Damit ist die Guard-Rail aus der Owner-Entscheidung vom 2026-07-20 verletzt: *„Grundproduktion muss für sich allein knapp, aber machbar sein, bevor irgendein Pfad-Bonus draufkommt."* Bei 8 Rg/Sol ist sie nicht machbar — die Kolonie ist ab sechs Gebäudetypen allein durch Reparatur negativ, bevor ein einziges Level-Up bezahlt ist. Die Zeile „−0,7" in der Tabelle oben ist kein Spannungsbogen, sondern ein Fehler.
>
> **Der Sockel wurde deshalb neu hergeleitet, nicht nachjustiert** — Ergebnis in **§13.7**. Kurzfassung: Der maßgebliche Grund gegen 8 ist nicht die Deckungslücke, sondern die **Auflösung** (G7) — bei 8 Rg/Sol gibt es nur zwei unterscheidbare Baupreisklassen. Vorschlag ist ein Sockel von 20 bei gleichzeitig halbierten Reparaturkosten (1 statt 2 Rg/SP) und neu abgeleiteten `decay_rate`-Werten. Die Tabellen in diesem Abschnitt rechnen noch mit den alten Werten und gelten nur, solange §13.7 nicht übernommen ist.

> **Nachrüstoption, falls das späte Spiel im Playtest schlaff wirkt:** Reparaturkosten mit dem Level skalieren — `AP je SP = 1 + floor((level−1)/3)`. Die Instandhaltung skaliert dann mit der **Tiefe** und koppelt sich elegant an den Supply-Cap (§6); bei der Zielkolonie ergäbe das ~11 statt 7,3 AP/Sol, also rund 50 % des Pools. Das ist der saubere Hebel. Die Alternative `decay_rate × level` ist thematisch schwächer (warum verfällt ein größeres Gebäude schneller?) und verdoppelt zusätzlich den Regolith-Abfluss.

---

### 13.6 Zahlenvorschlag, erste Fassung (überholt — siehe 13.7)

> **Überholt durch §13.7 (2026-08-02).** Diese Fassung ist gegen die bestehenden Config-Werte gerechnet und behandelt sie als Randbedingung — genau der Fehler, den „Zum Umgang mit den Zahlen" beschreibt. Sie bleibt stehen, weil der Vergleich mit §13.7 zeigt, was der Methodenwechsel bewirkt: Die AP-Struktur (Grundwert, Berater-Beitrag, `f(L)`-Kurve, Bonus-Kurve) hat sich bestätigt, die Regolith-Zahlen und die Hebel-Zielgröße nicht — letztere lag um Faktor 2 daneben.
>
> **Weiterhin gültig aus diesem Abschnitt:** Ziel-Endzustand, AP-Grundwert 10, Berater-Beitrag 2/3/4, `f(L)`-Kostenkurve mit `f(1) = 0.5`, Bonus-Kurve, Handlungs-AP. **Ersetzt:** alles Regolith-Bezogene und die Budgetprobe.

> **Status:** Erarbeitet gegen Code und Configs, nicht gegen die GDD-Richtwerte. **Noch keine Owner-Entscheidung.** Vor der Übernahme in `config/game.php` sind die in Anhang B gelisteten Drifts zu klären — insbesondere die tatsächlichen `ap_for_levelup`-Werte in der laufenden Datenbank.

#### Ziel-Endzustand (guter Run, Sol ~75–80)

| | |
|---|---|
| Gebäudetypen | 9–10 von 13 |
| Summe der Gebäudelevel | ~30 |
| CC | Lv4 (Lv5 = Streckziel, `max_level` ist 5) |
| Harvester | Lv1 (fest — siehe unten) |
| Agrardom | Lv4 |
| Wohnhabitate | 3 Instanzen |
| Pfadgebäude | eines Lv3, eines Lv2, eines Lv1 |
| Uplink-Station | Lv2 |
| Berater | 4, überwiegend Rang 2, einer Rang 3 |
| Kenntnisse | 4 auf Lv3 |
| Tiles belegt | 10–11 von 15 |

Vier ungenutzte Tiles und ein Rest-Cap sind Absicht: Der Spieler soll am Run-Ende sehen, was er hätte tun können.

#### AP-Grundwert und Berater-Beitrag

```php
'ap'      => ['base' => 10, 'project_min_cost_factor' => 0.5],
'advisor' => ['ap_per_rank' => [1 => 2, 2 => 3, 3 => 4]],   // war [4, 7, 12]
```

Der Berater-Beitrag muss **drastisch** sinken. Bei den alten Werten wäre ein Pool von 10 + 4 × 12 = 58 AP/Sol möglich — das Sechsfache des Startwerts. Die Zielvorgabe „ein Gebäude, das früh 5 Sole bindet, ist spät in 2 Solen fertig" ist ein Faktor 2,5; der zerfällt in Pool-Wachstum × Kostenreduktion. Bei 35 % Reduktion bleibt für das Pool-Wachstum höchstens Faktor ~1,8.

| Sol | Berater | Pool | Instandhaltung | Handlungen | **frei für Projekte** |
|---|---|---|---|---|---|
| 1–5 | 1 × R1 | 12 | 1,3–2,7 | ~2 | 7–9 |
| 6–20 | 2 × R1 | 14 | 2,7–4,3 | ~3 | 7–8 |
| 21–35 | 3, meist R1/R2 | 16 | 4,3–5,3 | ~4 | 7 |
| 36–60 | 4, meist R2 | 19–21 | 6,0 | ~5 | 8–10 |
| 61–85 | 4, R2 + 1 R3 | 22 | 7,3 | ~6 | 9 |
| 86–100 | 4, R2/R3 | 23–24 | 7,3 | ~6 | 10 |

Pool-Wachstum 12 → 22 = Faktor 1,8; mit 35 % Kostenreduktion ergibt das **2,8× Beschleunigung**. Der Vertrauens-Multiplikator (`trust.ap_multiplier`, ±10 %) kommt obendrauf: bei hohem Vertrauen 13 → 24.

> ⚠️ **Der Grundwert 10 ist die empfindlichste Einzelzahl des Modells.** Er ist bewusst so gewählt, dass er die bereits playgetestete Sol-1–4-Rampe reproduziert (Validierung unten) — nicht aus einer Formel abgeleitet. Die defensivere Alternative wäre 8 (schärferer Early-Game-Druck, Instandhaltung erreicht 40 % statt 33 %), sie bricht aber die Rampe. Empfehlung: 10, dann messen statt diskutieren.

#### Projektkosten je Gebäudelevel

```
ap_cost(building, L) = round(base_ap[building] × f(L))
f(1) = 0.5          (Errichten kostet die Hälfte eines Level-Ups)
f(L≥2) = 1 + 0.4 × (L − 2)
→ f = [0.5, 1.0, 1.4, 1.8, 2.2, 2.6, 3.0, 3.4]
```

| Stufe | `base_ap` | Gebäude | Kosten Lv1…Lv5 |
|---|---|---|---|
| Produktion | 10 | Harvester, Agrardom | 5 / 10 / 14 / 18 / 22 |
| Klein | 12 | Wohnhabitat, Kolonialdenkmal, Religiöse Stätte | 6 / 12 / 17 / 22 / 26 |
| Mittel | 16 | Cantina, Uplink-Station, Handelsposten | 8 / 16 / 22 / 29 / 35 |
| CC | 18 | Kommandozentrale | — / 18 / 25 / 32 / 40 |
| Groß | 22 | Analytik-Labor, Hangar, Krankenstation, Sicherheits-Hub | 11 / 22 / 31 / 40 / 48 |

Bei instanzierten Gebäuden (Wohnhabitat, Hangar) ist „Level" die Instanznummer: die 2. Wohnhabitat-Instanz kostet 12, die 3. kostet 17.

Produktionsgebäude sind bewusst am billigsten — ihre Glockenkurve (`game.production_curve`) deckelt den Wert bereits, ein zweiter Deckel über die AP-Kosten wäre doppelt.

**`f(1) = 0.5` ist zugleich die Antwort auf das Early-Game-Tempo.** Nicht als Sonderregel, sondern als erster Punkt derselben Kurve. Zusammen mit den Achsen aus §6 entsteht daraus das Breite/Tiefe-Dreieck ohne Optimalpfad. Die Alternativen wurden verworfen: Ein befristeter AP-Bonus früh wirkt dort, wo die Instandhaltungslast ohnehin am niedrigsten ist (22 %), und erzeugt beim Auslaufen eine Klippe. Mehr Vorbau in der Startkolonie würde den Agrardom-Lernmoment wegnehmen, der im Playtest-Review vom 2026-07-14 gerade erst als richtiger Einstieg bestätigt wurde — ein vorgebautes Wohnhabitat wäre sogar aktiv schädlich, weil es den ersten Supply-Cap-Sprung vorwegnimmt, an dem das System eingeführt wird.

**Kenntnisse** behalten ihre `levelup_costs` `[12, 20, 30, 40, 50]` aus `config/knowledge.php` unverändert — sie passen gegen 12–22 AP/Sol weiterhin (Lv1 in ~2 Solen, Lv5 in ~12 Solen bei Teil-Allokation).

#### Handlungs-AP nachziehen

Die Sofort-Handlungen waren gegen 6–10 AP-Einzelpools kalibriert und werden gegen 12–22 AP relativ zu billig:

| Wert | heute | Vorschlag | Begründung |
|---|---|---|---|
| `bar.ap_cost_accept` | 1 | **2** | 1 AP von 22 ist Rauschen |
| `bar.ap_cost_negotiate` | 3 | **4** | Abstand zum Annehmen erhalten |
| `missions.nav_ap_per_sol` | 2 | unverändert | Fernexpedition = 10 AP = 45 % des Pools, bleibt eine echte Entscheidung |
| `colony.explore_cost_per_ring` | 1/2/3 | unverändert | 19 Zonen-Tiles ≈ 33 AP ≈ 1,5 volle Sole |

> **Die Regel „Gelegenheiten sind durch Verfügbarkeit begrenzt" (13.2) ist bereits erfüllt — ohne neue Mechanik.** Missionen sind durch Schiffszahl und Rundlaufzeit begrenzt (2 Schiffe × Ø 5 Sole Umlauf × 5 AP ≈ 2 AP/Sol), Bar-Angebote durch `guest_count` und `level_max_concurrent` (≈ 4 AP/Sol). Zusammen ~6 von ~22 AP/Sol = 27 %. Das ist genau der beabsichtigte Deckel — es braucht keine zusätzliche Regel.

#### Budgetprobe

Verfügbare Projekt-AP: ~640 bis Sol 80, ~840 bis Sol 100.

| Projekt | AP nominal |
|---|---|
| CC Lv1→Lv4 | 75 |
| Agrardom Lv0→Lv4 | 47 |
| Wohnhabitat ×3 | 35 |
| Pfadgebäude 1 auf Lv3 | 46 |
| Pfadgebäude 2 auf Lv2 | 33 |
| Pfadgebäude 3 auf Lv1 | 11 |
| Uplink-Station Lv2 | 24 |
| Krankenstation Lv1 | 11 |
| Harvester Lv1 | 5 |
| **Gebäude Σ** | **287** |
| 4 Kenntnisse auf Lv3 | 248 |
| **Σ nominal** | **535** |
| **Σ nach Ø 20 % Bonus** | **~430** |

**430 von 640 = 67 % Auslastung.** Die verbleibenden ~210 AP sind der Spielraum für Vertiefung (CC Lv5, weitere Kenntnisse, weitere Wohnhabitate) — genau die „Phase 2 spät — Optimierung" aus §18.4. Das Budget geht mit rund einem Drittel Luft für Fehler und Umwege auf.

#### Validierung an der playgetesteten Sol-1–4-Rampe

Mit Pool 12, Agrardom Lv1 = 5 AP, Pfadgebäude Lv1 = 8 AP, CC Lv2 = 18 AP:

| Sol | Ausgaben | Rest |
|---|---|---|
| 1 | Harvester-Move 3 + Agrardom platzieren 1 + investieren 5 → **Agrardom Lv1 fertig** | 3 → Ring-2-Tile erkunden |
| 2 | Pfadgebäude platzieren 1 + investieren 8 → **Pfadgebäude Lv1 fertig** | 3 → erkunden |
| 3 | CC-Invest 10 | 0 |
| 4 | CC-Invest 8 → **CC Lv2 + Berater 2** | 2 → erkunden |

Gleicher Endpunkt wie heute (CC Lv2 an Sol 4), aber **jeder Sol schließt ein sichtbares Projekt ab statt nur Balken zu füllen** — und Erkundung läuft nicht mehr in einem getrennten Pool nebenher, sondern konkurriert echt. Die Regolith-Rechnung der Rampe bleibt unverändert gültig.

#### Wo der Vorschlag unsicher ist

- **Ob 33 % Instandhaltungsanteil sich nach Druck anfühlt.** Kernunsicherheit, ohne Playtest nicht beantwortbar. Falls nein: die levelskalierte Reparatur aus 13.5 nachrüsten, **nicht** die `decay_rate` global anheben — die hängen auch am Regolith-Abfluss.
- **Der Handlungsanteil (~5–6 AP/Sol) ist eine Schätzung.** Er hängt am Schiffsbestand und damit an der Credits-Ökonomie, die laut §18.4 noch instabil ist. Kommt der Spieler nie über ein Schiff hinaus, sinkt der Anteil auf ~3 und das Projektbudget steigt um ~15 %.
- **`f(1) = 0.5` könnte zu weit gehen.** Bei Produktionsgebäuden ist Lv1 = 5 AP, der Agrardom wäre an Sol 1 mit halbem Pool fertig. Der Gegenwert steht in Regolith (40 von 200), es ist also keine Gratisleistung — aber es ist die Stelle, an der am ehesten auf 0,6 nachzujustieren wäre.

---

### Slot-System: CC-Level als Gate, Pfadwahl ab Slot 2

Berater-Slots öffnen nicht mehr ausschließlich über CC-Level, sondern analog zu den Pfad-Gebäuden: durch den Bau eines spezifischen Gebäudes. Slot 1 ist **fest** an den Baumeister gebunden (siehe §16.2 "Designentscheidung zu Rang 1"). Slots 2–4 sind seit der Pfadwahl-Überarbeitung (2026-06-24) **generisch**: Welcher Beratertyp einen dieser drei Slots belegt, hängt davon ab, welches der drei Pfad-Gebäude der Spieler zuerst/zweit/dritt baut — nicht von einer fest verdrahteten CC-Level→Typ-Zuordnung.

| Gate | Slot | Bindung |
|------|------|---------|
| CC Lv1 | Slot 1 | **fix:** Baumeister |
| CC Lv2 + 1. Pfad-Gebäude (sciencelab/hangar/bar) | Slot 2 | **generisch:** Analytiker/Raumfahrer/Konsul |
| CC Lv3 + 2. Pfad-Gebäude | Slot 3 | **generisch:** Analytiker/Raumfahrer/Konsul |
| CC Lv4 + 3. Pfad-Gebäude | Slot 4 | **generisch:** Analytiker/Raumfahrer/Konsul |

> **Slot 5 entfällt (2026-08-02):** Der frühere Slot 5 (fix Stratege, Gate CC Lv3 + Sicherheits-Hub Lv1) ist mit der Zurückstellung des Strategen weggefallen. Es gibt **vier** Berater-Slots. Der Sicherheits-Hub bleibt als Gebäude erhalten, ist aber kein Slot-Gate mehr — Details siehe „Die vier Berater-Typen" weiter unten.

**Die drei Pfade** (siehe §4 "Pfadwahl ab Sol 3"):

| Pfad | Gebäude | Beratertyp | Domäne | CC-Gate | Slot |
|------|---------|-----------|--------|---------|------|
| A | Analytik-Labor (sciencelab) | Analytiker | research | CC Lv2 (Pfadwahl) | 2–4 (generisch) |
| B | Hangar | Raumfahrer | navigation | CC Lv2 (Pfadwahl) | 2–4 (generisch) |
| C | Cantina (bar) | Konsul | economy | CC Lv2 (Pfadwahl) | 2–4 (generisch) |

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
├── personell_type          ← 'construction' | 'research' | 'navigation' | 'economy'
│                             ('strategy' entfällt — Stratege zurückgestellt, 2026-08-02)
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

### Die vier Berater-Typen

| Beratertyp | Domäne (intern) | Thematische Rolle |
|------------|----------------|------------------|
| Baumeister | `construction` | Infrastruktur, Gebäude, Schiffsbau |
| Analytiker | `research` | Kenntnisse, Wissensarbeit |
| Raumfahrer | `navigation` | Tile-Erkundung, Außenmissions-Dispatch |
| Konsul | `economy` | Wirtschaftsbeziehungen, Markt |

> **Stratege zurückgestellt (Entscheidung 2026-08-02):** Der fünfte Beratertyp (`strategy`) ist **vorerst aus dem Spiel genommen**. Er war nie zu Ende designt — die Entwürfe schwankten zwischen „zusätzlicher später Pfad" und „modifiziert die drei anderen Pfade", ohne dass eine Richtung entschieden wurde. Statt ihn halbfertig mitzuschleppen, entfällt er zunächst vollständig und wird später neu bewertet und designt.
>
> **Was das konkret heißt:** Berater-Slot 5 entfällt; es gibt maximal **vier** gleichzeitig zugewiesene Berater. Der **Sicherheits-Hub bleibt als Gebäude bestehen** — er behält seine drei eigenständigen Effekte (Vertrauens-Bonus, Event-Dämpfung, Recycling, §4), verliert aber seine Funktion als Slot-Gate. Die vom Strategen getragenen Informationsleistungen (Gefahren-Vorwarnung mit Prognose, Ziel-Erreichbarkeits-Prognose) wandern in das Kommandozentrale-Dashboard (13.4), wo sie ohnehin besser aufgehoben sind.
>
> Betroffene Stellen, die bei der Umsetzung nachzuziehen sind: §4 (Sicherheits-Hub als Strategen-Pfad), §8b/§9 (Milderungs-Stacking mit Strategen-Sicherheitsanalyse), §11.1/§11.2 (Techtree-Entitäten und Abhängigkeiten), §13 (Slot-Tabelle, Außenmissionen, Rekrutierungskosten), §17.2 (`strategist_threat_assessment`-Dialog), `advisors.personell_type`-Enum, `config/advisors.php`.

Der Raumfahrer trägt zum gemeinsamen AP-Pool bei — diese AP decken die Tile-Erkundung (ring-gestaffelt 1/2/3 AP, §4a) und den Dispatch von Hangar-Schiffen auf Außenmissionen (`sol_distance × 2` AP, §8b). Er verlässt die Kolonie nicht. Eine eventuelle Außendienst-Mechanik für den Raumfahrer selbst ist für Phase 4+ zurückgestellt und noch nicht definiert (siehe auch "Außenmissionen" weiter unten).

### Außenmissionen (Berater-Außendienst)

> **Phase 4** — Vollständig ausgearbeitet, Implementierung ab Phase 4 geplant.

Drei Beratertypen (Baumeister, Analytiker, Konsul) können für eine begrenzte Anzahl Sole auf eine **Außenmission** entsendet werden — mit denselben Opportunitätskosten (AP fehlen während der Abwesenheit) und einem Bonus bei Rückkehr. Der Raumfahrer erscheint nicht in der Missions-Auswahl — eine spezifische Außendienst-Mechanik für ihn wird nach Playtest evaluiert (Phase 4+, noch kein konkreter Pfad definiert).

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
| Analytiker | Datenaustausch mit Forschungsstation | 4–6 | Spieler wählt eine Kenntnis — diese steigt sofort um 1 Level (ohne AP-Kosten, CC-Gates bleiben aktiv) |
| Konsul | Handelsreise | 3–4 | Exklusives Bar-Angebot bei Rückkehr (2 Sole gültig, erscheint als zusätzlicher Slot neben normalen Bar-Angeboten) |
| Raumfahrer | — | — | Kein Berater-Außenmissions-Pfad — sein "Außendienst" läuft indirekt über den Schiffs-Dispatch (§8b); eine eigene Mechanik wird nach Playtest evaluiert |

> **⚠️ Balance:** Der Analytiker-Bonus (Kenntnis +1 Level kostenlos) ist der stärkste Effekt. CC-Gates bleiben aktiv — ein Kenntnislevel das CC Lv5 voraussetzt, kann durch eine Außenmission nicht übersprungen werden. Dennoch muss nach Playtest geprüft werden, ob ein Free-Level-Upgrade bei Lv4→Lv5 zu mächtig ist. Ggf. Einschränkung: Bonus gilt nur für Lv1→Lv2 oder Lv2→Lv3.

> **Entfallen (2026-08-02):** Die Strategen-Außenmission „Sicherheitsanalyse" (detaillierte Gefahren-Prognose) ist mit der Zurückstellung des Strategen weggefallen. Die Gefahren-Vorwarnung mit prognostiziertem Ausgang wandert als Dauerfunktion in das Kommandozentrale-Dashboard (§13.4) — sie war als seltener, an einen Berater gekoppelter Einmaleffekt ohnehin schwer planbar.

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
| 2 | Senior | +7 | 13 | 30 |
| 3 | Experte | +12 | 18 | 80 |

*(Gesamt-AP = 6 Grundwert + AP-Bonus)*

> **Balance-Entscheidung (2026-07-19):** Upkeep-Kurve gegenüber der ursprünglichen Kalibrierung (10/50/160) abgeflacht — die alte Kurve ließ die Credits-Ökonomie strukturell kollabieren, sobald mehrere Berater gleichzeitig Rang 2 erreichten (Playtest-Bot-Befund, PR #218). Begleitend wurden die Beförderungs-Schwellen (`rank_thresholds`) von `[1=>10, 2=>20]` auf **`[1=>15, 2=>45]`** aktive Ticks gestreckt — mehr Zeit, um Uplink-Station und Cantina vor dem teureren Upkeep hochzuziehen. Volle Herleitung inkl. Break-even-Rechnung: siehe §18 Balancing-Richtlinien (`task_credit_reserve`).

**Einstellungskosten (Rang 1) — typ-spezifisch:**

| Beratertyp | Kosten (Cr) | Begründung |
|------------|-------------|-----------|
| Baumeister | 300 | Kernanforderung Tag 1 — günstigster Einstieg |
| Analytiker | 400 | Mittlere Priorität — erst bei CC Lv2 verfügbar |
| Raumfahrer | 500 | Erkundungs-/Missions-fokussiert — voller Nutzen erst mit Hangar |
| Konsul | 350 | Handelssupport — mittlere Priorität |

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

**Schiffsanzahl:** Die maximale Schiffsanzahl pro Spieler ist durch Hangar-Slots begrenzt (jede Hangar-Instanz belegt ein Tile, siehe §6). Kein Kommandanten-Pflichtmodell: Schiffe benötigen keinen zugewiesenen Raumfahrer.

---

### Raumfahrer: Colony-Scope

Der Raumfahrer ist ein colony-scoped AP-Produzent für den `navigation`-Pool. Er bleibt der Kolonie zugewiesen und verlässt sie nicht.

- **Colony-zugewiesen:** Generiert Navigation-AP auf der Kolonie (Grundlage für Tile-Erkundung und Außenmissions-Dispatch).
- **AP-Verbrauch:** Navigation-AP, die der Raumfahrer generiert, werden verbraucht wenn der Spieler Tiles erkundet oder Schiffe entsendet — der Raumfahrer selbst "geht" dabei nicht mit.
- **Burnout:** Bei Burnout ist der Raumfahrer für N Sole nicht verfügbar (`unavailable_until_tick` gesetzt), der Navigation-AP-Pool fällt auf den Grundwert.

---

### Verfügbare AP

```
availableAP = Grundwert + Σ AP_bonus(rank) über alle zugewiesenen Berater − lockedAP(tick)
```

Ein einziger Pool je Kolonie (13.1). `AP_bonus(rank)` ist der Beitrag jedes aktuell zugewiesenen Beraters, unabhängig von seiner Domäne — vier Berater erhöhen denselben Pool viermal. AP-Locks verfallen automatisch zum nächsten Sol; der Pool wird täglich vollständig erneuert.

> **⚠️ Offen — Grundwert:** Der frühere Grundwert war 6 AP/Sol **pro Typ** (5 × 6 = 30 AP/Sol nominell, praktisch unbrauchbar wegen der Nicht-Mischbarkeit). Der neue Grundwert des gemeinsamen Pools ist noch nicht festgelegt und muss zusammen mit den Projektkosten kalibriert werden (13.5). Er darf nicht die Summe der alten Werte sein — sonst wächst die effektive Handlungsfähigkeit sprunghaft.

### AP-Verbrauch

**Projekte (mehrere Sole, siehe 13.2):**

1. **Bauen/Ausbauen:** AP werden beim Investieren gesperrt (`invest('add')`), bis die Projektkosten erreicht sind.
2. **Kenntnisse:** identisch — bereits implementiert über `AbstractTechnologyService::_invest`.

**Handlungen (sofort, siehe 13.2):**

3. **Reparatur/Abbau:** AP in Höhe der veränderten `status_points`.
4. **Erkundung/Dispatch:** Tile-Erkundung ring-gestaffelt (1/2/3 AP, §4a), Außenmissions-Dispatch `sol_distance × 2` AP (§8b).
5. **Handelsgeschäfte:** AP je angenommenem oder verhandeltem Angebot (§12).

### Implementierung

- `app/Services/Techtree/PersonellService.php` — AP-Berechnung, Sperrung
- `app/Services/Techtree/AbstractTechnologyService.php` — AP-Verbrauch beim Investieren
- `app/Services/FleetService.php` — Navigation-AP-Check bei Order-Erstellung
- Tabelle `locked_actionpoints`: `(tick, scope_type, scope_id, personell_type, spend_ap)` — die Spalte `personell_type` verliert mit der Zusammenlegung ihre Funktion als Pool-Trennung; sie kann als reines Auswertungs-/Anzeigemerkmal („wofür wurde investiert") erhalten bleiben oder entfallen. Zu entscheiden bei der Implementierung.

### Berater-Burnout (Auswirkung auf AP)

Wenn ein Berater einen Burnout erleidet (Wahrscheinlichkeitsmechanik — Details in §7), fällt sein AP-Beitrag für die Dauer der Erholung auf null zurück. Der gemeinsame Pool sinkt um genau diesen Beitrag; zusätzlich entfällt sein Effizienzbonus in seiner Domäne (13.3).

**Beispiel:** Ein Senior-Analytiker (rank=2) trägt normalerweise 20 AP/Sol zum Pool bei. Bei Burnout fehlen diese 20 AP/Sol — die Kolonie arbeitet insgesamt langsamer, aber kein Bereich fällt vollständig aus. Das ist die gewünschte Abschwächung gegenüber dem alten Modell, in dem ein Burnout eine ganze Domäne auf den Grundwert zurückwarf.

**Dauer:** Abhängig vom Rang (Junior 15, Senior 10, Experte 5 Sole — Richtwerte, noch nicht in Config abgebildet; siehe „Implementierungsstand" in §7).

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
| Analytiker | Techtree | "Sole bis Level X beim aktuellen AP-Fluss in diese Kenntnis" | Priorisierungshinweis für offene Run-Aufgaben |
| Konsul | Cantina | Händler-Einschätzung "guter / durchschnittlich / schlechter Deal" (kontextuell, nicht binär) | Restlaufzeit-Countdown für Angebote prominent statt versteckt |
| Raumfahrer | Hangar | Aufgebrochene Missionszeit ("X Sole Hinweg + Rückkehr Sol Z") | Verschleiß-Prognose pro geplantem Dispatch (§7) |

> **Ehemals Stratege:** Die Ziel-Erreichbarkeits-Prognose („Aufgabe X: ✓ in ~12 Solen; Aufgabe Y: ✗ — 400 Cr fehlen") und die Ausgangs-Prognose bei Gefahren-Vorwarnung (§9) waren als Strategen-Informationsebene geplant. Beide werden mit der Zurückstellung des Strategen zu **beraterunabhängigen Dauerfunktionen** des Kommandozentrale-Dashboards (§13.4) — sie sind für das Ratenmodell zu wichtig, um an einen optionalen Berater gekoppelt zu bleiben.

> **⚠️ Balance — Konsul:** Händler-Einschätzung darf nicht binär sein ("kaufen / nicht kaufen"), sonst entwertet sie die Handelsentscheidung. Kontextuell: "günstig für Werkstoffe — du hast davon aber bereits 200" ist besser als "guter Deal".

> **⚠️ Balance — Erster Cantina-Besuch:** Konsul ist erst ab CC Lv2 verfügbar, der erste Händler erscheint früher. Der erste Cantina-Besuch muss immer ein objektiv gutes Angebot zeigen — unabhängig vom Konsul-Status. Sonst entsteht Früh-Spiel-Frustration bei Spielern ohne Konsul.

**Implementierung:** Phase 4 — setzt stabiles Berater-System und abgeschlossene Screen-Redesigns voraus. Keine neuen Datenpunkte nötig (alle Quellen in Config und DB bereits vorhanden), reine UI-Logik. Discovery-Moments integrieren sich in bestehenden Onboarding-Hint-Stack (§16).

---

---
### 13.7 Regolith-Zahlensatz, hergeleitet (Stand 2026-08-02 — Vorschlag)

> **Status:** Von der Designabsicht her hergeleitet statt aus den Bestandswerten fortgeschrieben. **Noch keine Owner-Freigabe** außer den ausdrücklich markierten Punkten. Ersetzt die Regolith-Anteile von §13.6.

#### Das Spielgefühl — zuerst, ohne Zahlen

Jede Zahl unten ist auf eine dieser Aussagen zurückführbar. Wo das nicht gelingt, ist sie willkürlich und gehört ersetzt.

| | Aussage |
|---|---|
| **G1** | **Regolith ist nie bequem und nie tödlich.** Der Bestand schwingt um eine niedrige zweistellige Zahl. Ein wachsender Haufen heißt, die Kolonie ist fertig; eine Null heißt, sie stirbt. Beides beendet die Spannung. |
| **G2** | **Instandhaltung ist Routine, nicht Krise.** Sie bindet ~15 % des Einkommens früh und ~40 % bei der Zielkolonie. Unter 10 % ist Verfall Dekoration und die USP fällt weg; über 60 % ist er eine Strafe fürs Bauen. |
| **G3** | **Vernachlässigung kostet ein Level, nicht den Run.** Ein Level-Down ist in 5–8 Solen aufgeholt, ohne Kaskadenrisiko. |
| **G4** | **Errichten ist eine Entscheidung, Level-Up ein Schritt.** Eine Errichtung kostet 5–8 Sole Sparen, ein Level-Up 1–2. |
| **G5** | **Der Spieler soll 2–4 Mal pro Run an Regolith scheitern** — nicht dauernd (Grind), nicht einmal (Gate). |
| **G6** | **Der Sockel trägt das Überleben, der Pfad-Hebel das Wachstum.** Ohne genutzten Hebel erreicht die Kolonie ~70 % der Zielgröße: kleiner, aber vollständig spielbar. |
| **G7** | **Der Spieler muss im Kopf rechnen können.** „Ich mache 20 pro Sol, das kostet 95, das sind fünf Sole." |

**G4 ist die wichtigste Aussage**, weil sie Regolith und AP entkoppelt: Das AP-Modell macht es genau umgekehrt (`f(1) = 0.5` — Errichten AP-billig, Level-Up AP-teuer). **Breite kostet Regolith, Tiefe kostet AP.** Damit sind die beiden Währungen nicht mehr redundant, sondern greifen an gegenüberliegenden Enden an.

**G7 bestimmt die absolute Skala** — und das ist der eigentliche Grund gegen einen niedrigen Sockel, nicht eine Deckungslücke. Bei 8 Rg/Sol liegen die Baupreise zwischen 15 und 55: zwei unterscheidbare Klassen, und der Unterschied zwischen einem 25er und einem 30er Gebäude verschwindet im Reparaturrauschen. Bei 20 sind es vier bis fünf Klassen mit sauberem Abstand.

#### Der Satz

| Wert | heute | Vorschlag | folgt aus |
|---|---|---|---|
| `production_curve[27][3]` | `[8,10,12,12,10,8,6,4]` | **`[1 => 20]`**, einziger Wert | G7, `max_level = 1` |
| `repair.regolith_per_click` | 2 | **1** | G2 + Vereinfachung, s. u. |
| `decay_rate` | 0,33–2,0 (geerbt) | **4 Klassen: 0,5 / 0,8 / 1,0 / 1,5** | G2, G3 |
| Errichtung (Lv0→1) | 40–100 | **70 / 95 / 120** | G4 (5–8 Sole) |
| Level-Up | 25 % der Errichtung | **flach 25** | G4 (1–2 Sole) |
| CC-Ausbau | Ziel-Level × 20 | **× 30** | zentraler Progressionshebel |
| Instanz 2 und folgende | voller `build_cost` | **Level-Up-Preis (25)** | Datenmodell; löst den Hangar-Bootstrap-Zirkel |
| Startbestand | 200 | **200** (zufällig gleich) | Rampenprobe |
| `mission_supply_run.sol_distance` | 2 | **1** | Hebel-Zielgröße, kürzerer Entscheidungstakt |
| `geology`-Effekt | keiner | **+3/3/2/2/2 → kumuliert max 12** | 60 % des Sockels |
| `knowledge.levelup_costs` | 12/20/30/40/50 | **20/28/36/44/52** | Amortisation ~7 Sole |
| `knowledge.credits` | 100 | **0** | Credits-Lücke von Pfad A (§4b) |
| Hebel-Zielgröße | ~6 Rg/Sol | **12 Rg/Sol reif, ~6 im Run-Mittel** | Rampe + 60-%-Regel |

**Zur Reparatur — eine Zahl, zwei Währungen.** Reparatur kostet bereits 1 AP je SP. Bei ebenfalls 1 Regolith je SP gilt:

```
Instandhaltung [Rg/Sol]  =  Instandhaltung [AP/Sol]  =  Σ decay_rate
```

Das Dashboard (13.4) braucht dann keine zwei Zeilen und 13.5 keine zwei Tabellen. Die heutige 1 : 2-Kopplung existiert nur, damit Reparatur „teuer wirkt" — dafür ist `decay_rate` der bessere Knopf, weil er beide Seiten gleichzeitig bewegt.

**Zu `decay_rate` — aus einer Spielaussage abgeleitet.** Regel bleibt `decay_rate = max_status_points / Sole_bis_Level_Down`. Neu ist, dass die Sole eine Designaussage sind: *wie teuer ist es, dieses Gebäude zu vergessen?*

| Klasse | Sole bis Level-Down | Rate | Gebäude |
|---|---|---|---|
| Robust | 40 | **0,50** | Kommandozentrale, Wohnhabitat, Kolonialdenkmal |
| Standard | 25 | **0,80** | Agrardom, Uplink-Station, Hangar, Handelsposten, Sicherheits-Hub |
| Beansprucht | 20 | **1,00** | Harvester, Analytik-Labor, Cantina, Krankenstation |
| Fragil | 13 | **1,50** | Religiöse Stätte |

Bei den drei robusten ist ein Level-Down überproportional teuer (Supply-Cap bricht weg, Instanz verschwindet) — er muss langsam kommen, sonst verletzt er G3. Die Religiöse Stätte ist bewusst der teuerste Unterhalt im Spiel: Sie zahlt in Vertrauen, nicht in Funktion; wer sie hält, entscheidet sich aktiv dafür.

Instandhaltung gegen G2:

| Ausbaustand | Typen | Σ `decay_rate` = Rg/Sol = AP/Sol | Anteil am Sockel | Ziel |
|---|---|---|---|---|
| Start (CC, Harvester, Wohnhabitat) | 3 | 2,00 | 10 % | — |
| + Agrardom | 4 | 2,80 | **14 %** | ~15 % ✓ |
| + Pfad 1, Uplink | 6 | 4,60 | 23 % | — |
| + Pfad 2 | 7 | 5,60 | **28 %** | ~30 % ✓ |
| + Pfad 3, Krankenstation, Denkmal | 10 | 7,90 | **40 %** | ~40 % ✓ |
| alle 13 | 13 | 11,00 | 55 % | ≤ 60 % ✓ |

Die AP-Seite fällt dabei von selbst richtig: 7,9 AP/Sol gegen den Pool von 22 sind 36 % — der in 13.5 formulierte Zielkorridor. Kein Zufall, sondern die Folge davon, dass beide Kosten an derselben Zahl hängen.

#### Proben

**Sol-1–4-Rampe** (Instandhaltung Sol 1: 2,0; ab Agrardom 2,8):

| Sol | Ausgabe | netto | Bestand Ende |
|---|---|---|---|
| Start | — | — | 200 |
| 1 | Agrardom errichten 70 | +17,2 | 147 |
| 2 | Pfadgebäude errichten 95 (Cantina) | +16,2 | 68 |
| 3 | — (CC-Invest läuft) | +16,2 | 84 |
| 4 | CC Lv2 = 60 → Berater 2 | +16,2 | **40** |
| 5 | Startschaden reparieren (12 SP) | +16,2 | 44 |

Gleicher Endpunkt wie heute (CC Lv2 an Sol 4), aber mit einem Einkommen, das den Puffer trägt statt ihn zu verzehren. Der in 13.5 als offen markierte Engpass „Sole 8–20" verschwindet. Beim Hangar-Pfad (120 statt 95) endet Sol 4 bei ~15 Rg — knapper, aber nicht negativ; die teuerste Pfadwahl wird damit zur echten Entscheidung statt zur kosmetischen.

**Bilanz über den Run.** Zielkolonie ≈ 1.820 Rg Bedarf (835 Errichtungen + 720 Level-Ups + 25 Zweitinstanz + ~240 Reibung). Sockel-Einnahmen bis Sol 80 ≈ 1.363 Rg. **Der Sockel trägt 75 % der Zielkolonie** — G6 verlangt ~70 %. ✓ Die Lücke von ~460 Rg ≈ 6 Rg/Sol im Mittel ist das, was der Pfad-Hebel schließt.

#### Warum die Hebel-Zielgröße vorher um Faktor 2 danebenlag

Die alte Zahl „~6 Rg/Sol je Hebel" war der **Mittelwert über den Run**, angewandt als **Reife-Wert**. Ein Hebel läuft aber nicht ab Sol 1: Pfad A braucht Kenntnisstufen, Pfad B ein Schiff, Pfad C Angebote. Realistisch greift er ab Sol ~12 und ist ab ~40 voll — die reife Höhe muss deshalb beim Doppelten liegen.

Gegenprobe aus anderer Richtung, zugleich die Merkregel: **ein reifer Pfad-Hebel ist etwa 60 % eines Harvesters.** Spürbar, aber kein Ersatz für den Sockel. Beide Herleitungen landen bei 12.

---

#### Korrektur durch die Knappheitsordnung (Owner, 2026-08-02)

Der Vorschlag enthielt ursprünglich eine Preisänderung `bar.base_prices` auf Rg 40 / Or 30 / Wk 120, begründet damit, die Preise stünden „andersherum als die Knappheit" — die Kolonie überproduziere Organika und leide an Regolith-Mangel.

**Das ist zurückgewiesen.** Die Beobachtung stimmt für den Ist-Zustand, aber die Knappheitsordnung aus §3 ist die Vorgabe: `Regolith < Organika < Werkstoffe`. Die heutigen Preise (Rg 30 / Or 50 / Wk 60) haben die **richtige Reihenfolge**; der Vorschlag hätte sie vertauscht.

**Was bleibt:** Der Abstand zwischen Organika und Werkstoffen ist zu klein für „deutlich knapper als Organika". Eine Anpassung, die die Ordnung respektiert:

| | heute | Vorschlag |
|---|---|---|
| Regolith | 30 | **25** — wird mit Sockel 20 reichlicher, der Preis folgt |
| Organika | 50 | **50** (unverändert) |
| Werkstoffe | 60 | **110** — „anfangs sehr begrenzt", der Abstand muss die Knappheit zeigen |
| `compound_import_price` | 90 | **165** — hält das Verhältnis ~1,5 : 1 zum Spotpreis (§3) |

**Zwei Folgen, die noch offen sind:**

> **⚠️ Der Pfad-C-Hebel muss neu gedacht werden.** Der vorgeschlagene Organika→Regolith-Tausch setzte voraus, dass Organika der Überschuss ist. Nach der Knappheitsordnung ist es umgekehrt — man würde das knappere Gut gegen das häufigere tauschen. Der Credits→Regolith-Ankauf ist bei 25 Cr/Rg zwar billiger als zuvor gerechnet (12 Rg/Sol ≈ 300 Cr/Sol), trägt aber immer noch keine Ökonomie. **Möglich ist auch, dass Pfad C gar keinen großen Regolith-Hebel braucht:** Wenn Regolith laut §3 „verfügbar sein soll", ist es nicht der Engpass, gegen den die Pfade sich beweisen müssen — dann liegt Pfad C's Beitrag woanders (Flexibilität, Credits, Vertrauen) und die Paritätsfrage stellt sich für Regolith gar nicht in dieser Schärfe.

> **⚠️ Agrardom-Kurve: das obere Ende prüfen, nicht die Mechanik.** Der Organika-Verbrauch skaliert über `food_need = intdiv(usedSupply, 4)` mit der **Ausbautiefe** der Kolonie — es ist also ein Rennen zwischen Agrardom-Level und Koloniewachstum, dazu der einmalige Missionsproviant (`sol_distance × 3`) und Event-Kosten. Das ist genau der Mechanismus, den die Knappheitsordnung verlangt, und er funktioniert:
>
> | verbrauchter Supply | Bedarf/Sol | nötiges Agrardom-Level (kumuliert 8/20/32/41/48) |
> |---|---|---|
> | 40 | 10 | Lv2 |
> | 80 | 20 | Lv2 knapp, Lv3 sicher |
> | 100 | 25 | Lv3 |
> | 126 (Cap der Zielkolonie) | 31 | Lv3 grenzwertig, Lv4 komfortabel |
>
> Wer die Kolonie in die Tiefe baut, ohne den Agrardom nachzuziehen, gerät in den Mangel — so gewollt. **Zu prüfen ist deshalb nur das obere Ende:** Lv4/Lv5 liefern 41/48 gegen einen Bedarf, der bei der Zielkolonie nicht über ~31 steigt. Ab Lv4 ist das Rennen entschieden und Organika hört auf, eine Sorge zu sein. Ob die Kurve dort flacher auslaufen sollte — oder ob Missionen und Events genug Zusatzlast erzeugen, um die Marge dünn zu halten —, gehört in dieselbe Herleitung wie der Regolith-Satz.

#### Auslieferung: alles in einem Zug

Der Satz ist ein zusammenhängendes System. **Sockel 20 ohne die neuen Baukosten ergibt eine triviale Wirtschaft, die neuen Baukosten ohne den Sockel eine unspielbare.** Alles oben gehört in einen PR — zusammen mit `harvester.max_level` 8 → 1 in `config/buildings.php` (sonst setzt der nächste `game:sync-config`-Lauf die Owner-Entscheidung still zurück, Anhang B).

#### Wo dieser Satz unsicher ist

- **Die 60-%-Regel für die Hebel-Reife (12 von 20) ist eine Setzung.** Sie fällt aus zwei unabhängigen Richtungen auf dieselbe Zahl, ist aber die erste, die im Playtest zu prüfen wäre. Metrik: Anteil des Regolith-Zuflusses aus dem Hebel je Pfad, Zielband 30–40 %.
- **Die Reibungspauschale von 15 % (240 Rg) ist geraten.** Sie deckt Level-Down-Wiederaufbau, Harvester-Verlegungen und Fehlkäufe und ist direkt aus dem Bot-Report ablesbar.
- **Die Supply-Achse ist bewusst nicht mitbewegt.** Die `supply_cost`-Werte sind gegen eine Wirtschaft kalibriert, in der Regolith knapper war. Wird Bauen leichter, wird Supply relativ zum bindenderen Limiter — was §6 entspricht, aber die Zielkolonie gegen den erreichbaren Cap gegenzuprüfen verlangt. **Das ist der nächste unconstrained durchzurechnende Zahlensatz.**
- **`max_level = NULL` bei sieben Gebäuden** (Sciencelab, Temple, Agrardom, Hangar, Krankenstation, Monument, Cantina) ist unangetastet. Ein unbegrenztes Hochleveln widerspricht dem „kleine Kolonie"-Prinzip; gehört zur Supply-Runde.

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
vertrauen = clamp(Σ(Gebäudeeffekte) + Σ(Forschungseffekte) + clamp(Σ(Schiffseffekte), -30, +30) + ereigniseffekte, -100, +100)
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

### Einflussfaktoren: Relaisvergütung

Die Relaisvergütung (§3) ist eine reine Nexus-Einnahme **ohne automatischen Vertrauenseffekt** — sie fließt von Nexus an die Kolonie, nicht umgekehrt, und stellt für sich genommen keine Belastung der Kolonisten dar. Ein gesonderter passiver Abzugs-/Steuermechanismus mit Vertrauensmalus wurde ursprünglich erwogen (das frühere "Steuern"-Konzept), ist aber hinfällig und wird nicht weiterverfolgt — der Platzhalter-Begriff "Steuern" ist damit erledigt: nicht umbenannt, sondern die Mechanik dahinter gestrichen.

Was sich ändert: Der Spieler kann eingenommene Credits — ob aus Relaisvergütung, Handel oder Reserven — jetzt **aktiv** in Vertrauen zurückverwandeln. Das ist kein passiver Nebeneffekt der Relaisvergütung selbst, sondern eine eigene, bewusst gewählte Aktion — siehe **Kolonisten-Zulage** im nächsten Abschnitt.

### Einflussfaktoren: Kolonisten-Zulage (Spieleraktion)

Aktive Aktion des Direktors: Ein Teil der Kolonie-Credits kann jederzeit direkt an die Kolonisten ausgeschüttet werden — eine spürbare, bewusst gewählte Ausgabe, die zeigt, dass es der Siedlung wirtschaftlich gut genug geht, um sie unmittelbar zu beteiligen. Anders als Vertrauensgebäude oder Kenntnisse (permanente Dauerboni) ist die Zulage ein **reaktiver Hebel**: kein Dauerzustand, sondern eine situative Entscheidung — z. B. um vor einem kritischen Sol (Nexus-Meilenstein §15, drohende Vertrauens-Fail-Schwelle §15) Vertrauen zu stabilisieren, auf Kosten von Credits, die sonst in Ausbau, Berater-Upkeep oder Handel geflossen wären.

**Staffelung:**

| Stufe | Kosten | Vertrauens-Bonus | Event-Key | Credits/Punkt |
|-------|--------|-------------------|-----------|----------------|
| Klein | 100 Credits | +2 Vertrauen | `stipend_small` | 50 |
| Mittel | 300 Credits | +3 Vertrauen | `stipend_medium` | 100 |
| Groß | 600 Credits | +4 Vertrauen | `stipend_large` | 150 |

Die Wirkung folgt der Standard-Event-Logik (siehe "Einflussfaktoren: Ereignisse" unten): genau **1 Sol**, danach verworfen.

**Nur eine Zulage pro Sol.** Die drei Stufen sind unterschiedliche Event-Keys (nicht Varianten desselben Keys) — das bestehende Dedup in `TrustService::eventContribution` fasst nur *gleiche* Keys zusammen und summiert *unterschiedliche* Keys auf. Ohne zusätzliche Sperre könnten "Klein" + "Groß" im selben Sol also zu +6 Vertrauen kombiniert werden. Das ist **nicht gewollt** und muss bei der Implementierung als eigene Regel ergänzt werden: pro Kolonie und Sol ist höchstens eine Zulagen-Stufe auslösbar (Fire-Time-Guard im Service, nicht im bestehenden Event-Dedup). Dieser Punkt ist ein expliziter Implementierungs-Hinweis, kein bereits vorhandenes Verhalten.

**Kein Cooldown über mehrere Sole hinweg.** Die Staffelung ist bewusst **degressiv** (Credits pro Vertrauenspunkt steigen von 50 auf 150) — je größer die Ausschüttung, desto ineffizienter pro Credit. Das macht tägliches Wiederholen unattraktiv, ohne eine künstliche Sperre zu benötigen: Wer jeden Sol die kleine Stufe zieht, zahlt 100 Credits/Sol für einen wiederkehrenden +2-Bonus — spürbar gegenüber der Relaisvergütung (20–60 Cr/Sol, abhängig vom Uplink-Station-Level) und dem Berater-Upkeep (50 Cr/Sol, Rang 2), aber nicht kostenlos. Zum Vergleich: der seltene Händler-Artikel "Vertrauensschub" (§12, `trust_boost`) liefert einmalig +15 Vertrauen für 600 Credits (40 Cr/Punkt) — die Kolonisten-Zulage ist bewusst *weniger* effizient pro Credit, da sie jederzeit verfügbar ist und die übrigen Vertrauensfaktoren (Gebäude, Kenntnisse, Verpflegung) nicht verdrängen soll.

**Rationale:** Die Zulage gibt dem Spieler einen direkten, jederzeit verfügbaren Hebel auf Vertrauen — aber zu einem Preis, der die Entscheidung "Vertrauen jetzt sichern" gegen "Credits in Ausbau/Handel investieren" tatsächlich schwer macht. Die degressive Staffelung verhindert, dass die große Stufe zur Standardwahl wird; die Einmal-pro-Sol-Regel verhindert Kombination innerhalb eines Sols. Zusammen ersetzt das einen Cooldown, ohne die Reaktionsfreiheit des Spielers einzuschränken.

> ⚠️ BALANCE CONCERN: Ohne harten Mehr-Sol-Cooldown ist die Kolonisten-Zulage im Lategame (hohe Credits-Reserven) potenziell ein "Vertrauen auf Knopfdruck"-Ventil, das die -20-Fail-Schwelle (§15) entschärft. Nach dem ersten Playtest prüfen, ob ein Soft-Cap (z. B. max. 1 Zulagen-Event pro N Sole) nötig wird, falls Spieler die Mechanik nutzen, um Krisen risikofrei auszusitzen statt echte Ursachen (Hunger, Decay, Militarisierung) zu beheben.

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

**Spieleraktionen:**

| Event-Key | Beschreibung | Vertrauenseffekt |
|-----------|-------------|------------------|
| `stipend_small` | Kolonisten-Zulage, Stufe Klein (100 Cr) | +2 |
| `stipend_medium` | Kolonisten-Zulage, Stufe Mittel (300 Cr) | +3 |
| `stipend_large` | Kolonisten-Zulage, Stufe Groß (600 Cr) | +4 |

Details, Kosten-Rationale und die Einmal-pro-Sol-Regel siehe "Einflussfaktoren: Kolonisten-Zulage (Spieleraktion)" weiter oben.

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

> **Ausgelagert:** Dieses Kapitel steht in [`docs/gdd/onboarding.md`](gdd/onboarding.md) — Designprinzipien, Cold-Start-Problem, Nexus-Briefing (16.1), Hint-System (16.2), Pulse-Indikator (16.3), Techtree-Kaltstart (16.4), die ersten 3–5 Aktionen (16.5), Inline-Erklärungen (16.6) und Abgrenzung (16.7).

---

## 17. Progressive Discovery System

> **Ausgelagert:** Dieses Kapitel steht in [`docs/gdd/progressive-discovery.md`](gdd/progressive-discovery.md) — Objective Discovery (17.1), Advisor Dialogs (17.2), Almanach (17.3) und Implementierungshinweise (17.4).

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

> **Entschieden (2026-07-19):** `task_credit_reserve: 10` (10 aufeinanderfolgende Sole mit Credits > 5.000) war mit der alten Ökonomie strukturell unerreichbar — Playtest-Bot-Befund PR #218 bestätigt: Credits fielen auf 0 und blieben dort geklemmt, der dritte Berater wurde nie leistbar, Phase 1 nie abgeschlossen. Fix über drei Hebel (Details siehe §13 "Rang-System" und §12 "Kanal 1: Bar/Cantina"):
>
> 1. **Upkeep-Kurve abgeflacht:** `advisor.upkeep` von `[1=>10, 2=>50, 3=>160]` auf **`[1=>10, 2=>30, 3=>80]`**. Rang-2-Sprung war 5×, jetzt 3×; Rang-3-Sprung war 3,2×, jetzt 2,67×. Weiterhin eine echte Eskalation (teure Berater bleiben teuer), aber kein Klippensturz.
> 2. **Beförderungs-Schwellen gestreckt:** `advisor.rank_thresholds` von `[1=>10, 2=>20]` auf **`[1=>15, 2=>45]`** aktive Ticks. Gibt dem Spieler bis Rang 2 mehr als doppelt so lange Zeit, Uplink-Station und Cantina hochzuziehen, bevor der teurere Upkeep greift.
> 3. **Neue passive Einnahmequelle "Handelsvertrag":** Kein Bar-Angebot (kein Slot, keine Annahme, kein AP-Kosten), sondern eine passive Cr/Sol-Einnahme — strukturell identisch zur Relaisvergütung: sie fließt automatisch pro Tick, solange ein Konsul der Kolonie zugewiesen ist **und** die Cantina mind. Lv1 gebaut ist. Feste Werte nach Konsul-Rang: **Rang 1 = 10 Cr/Sol, Rang 2 = 25 Cr/Sol, Rang 3 = 45 Cr/Sol** (Config-Key-Vorschlag: `game.credits.consul_contract_income_per_rank`, verarbeitet in `GameTick` im selben Schritt wie `nexus_subsidy`/`relay_bonus_per_uplink_level`). Ohne Konsul: 0 — bewusst, siehe unten.
>
> **Break-even-Rechnung (Zielgröße, kein Autopilot-Sieg):** 3 Berater gleichzeitig auf Rang 2 kosten 3 × 30 = 90 Cr/Sol Upkeep.
> - Mit Uplink-Station Lv2 (Relaisvergütung 40 Cr/Sol) + Nexus-Subvention (30 Cr/Sol, unverändert) + Handelsvertrag Rang 2 (25 Cr/Sol) = 95 Cr/Sol → **+5 Cr/Sol Überschuss**. Ein Spieler, der Uplink-Station ausbaut und einen Konsul hält, trägt drei Rang-2-Berater knapp, aber stabil.
> - Ohne Uplink-Station (nur Subvention 30 + Handelsvertrag 25 = 55 Cr/Sol) → **-35 Cr/Sol Defizit.** Spürbarer Druck, Uplink-Station zu bauen — kein Soft-Lock, da der Credits-Fail-Schwellenwert erst bei > 12.000 Cr Schuldenstand liegt (§15): ein Defizit dieser Größe ist ein langsames Ausbluten über viele Sole, keine sofortige Niederlage.
> - Ohne Konsul zugewiesen (z. B. Spieler wählt Analytiker + Raumfahrer als die zwei freien Slots) entfällt der Handelsvertrag komplett: Subvention 30 + Uplink Lv2 40 = 70 vs. 90 Upkeep → -20 Cr/Sol. Das ist **beabsichtigt**: die Konsul-Entscheidung hat einen echten wirtschaftlichen Preis, kein versteckter Kollaps — der Spieler kompensiert über Uplink-Ausbau, langsameres Rang-Aufsteigen (weniger aktive Nutzung) oder gelegentliche manuelle Bar-Trades.
>
> Bewusst **nicht** geändert: `nexus_subsidy` bleibt bei 30 Cr/Sol (kein zusätzlicher passiver Puffer — sonst nähert sich die Ökonomie einem Autopilot-Sieg an) und `promotion_costs` bleiben bei `[2=>150, 3=>400]` (das einmalige Beförderungs-Gate war nie das Problem, siehe ursprüngliche Diagnose).
>
> **Playtest-Bot-Ergebnis nach diesem Fix (2026-07-20):** Phase 2 wird jetzt erreicht (Sol 49 mit PR #219 allein, Sol 18 nach der zusätzlichen Grundproduktions-Anpassung unten) — vorher nie. Aber: die Ökonomie kollabiert danach weiterhin, sobald 2-3 Berater gleichzeitig auf Rang 2/3 stehen (Credits crashen auf 0 und bleiben dort bis Run-Ende). Grundproduktion (Harvester/Agrardom) war zu knapp, um überhaupt ausreichend Baupuffer/Handelsware für Uplink-Station + Cantina + Konsul gleichzeitig aufzubauen, bevor der Upkeep zuschlägt — daraus folgt die Glockenkurven-Anpassung oben im Produktions-Abschnitt (§3). Bar/Cantina-Nutzbarkeit (`"Not enough resources."`-Ablehnungen, 47-77× pro Lauf) und die Post-Phase-1-Erholung bei 3 gleichzeitig hohen Rängen bleiben weiterhin offen — **eigenes Ticket, Brainstorming läuft** (Kenntnisse-Boni, Hangar-Missionsnutzbarkeit, Handel-Redesign als Amplifikatoren, siehe Owner-Diskussion 2026-07-20).
>
> **Nebenfund (2026-07-20, eigenes Ticket, NICHT Teil dieser Balance-Änderung):** `PlaytestBotTest::test_same_seed_draws_identical_objectives` deckte einen echten Determinismus-Bug auf: `ColonyTileService::randomizeOuterRingRows()` nutzt PHP-Ambient-Zufall (`random_int`/`shuffle`/`array_rand`), nicht den Run-`rng_seed` — und läuft in `OnboardingService::resetColonyToSol1()` VOR dem expliziten Setzen von `rng_seed` in Tests. Zwei Runs mit identischem Seed erhalten dadurch unterschiedliche Tile-Layouts, was zu unterschiedlichen Spielverläufen kaskadiert (empirisch bestätigt: ein Bot-Lauf erreichte Phase 2, der andere mit demselben Seed nicht). Bricht die Reproduzierbarkeits-Garantie für "gleicher Seed → gleicher Run" — relevant über Tests hinaus, sobald Replay/Determinismus je gebraucht wird. Test bewusst als "skipped" markiert (nicht rot), bis Tile-Randomisierung über `rng_seed` läuft.

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

---

## Anhang A — Balance- und TODO-Index

Sammelübersicht aller offenen Balance- und Designfragen im GDD, damit nach einem Playtest an einer Stelle steht, was zu prüfen ist. Angelegt 2026-08-02. Die maßgebliche Formulierung steht jeweils am genannten Ort — dies ist ein Verzeichnis, keine zweite Quelle.

### A.1 Blockierend — vor der Implementierung des Ratenmodells zu klären

| Thema | Stand | Ort |
|---|---|---|
| **`max_level` in `max_instances` + `max_level` aufteilen** — ohne die Trennung kann der Hangar seine beiden Achsen nicht haben | **offen, blockierend** | §4c |
| **Instanz-Decay-Verdacht verifizieren** — `processBuildingDecay()` schreibt ohne Instanz-Unterscheidung. Bestraft sonst jede Umstellung auf Instanzen sofort | **offen, blockierend, vor der Umstellung** | §4c |
| **Regolith-Zahlensatz freigeben** (Sockel, Reparatur, `decay_rate`, Bau- und Level-Up-Kosten) — in einem Zug auszuliefern, Teile einzeln brechen die Wirtschaft | Vorschlag liegt vor, Owner-Entscheidung offen | §13.7 |
| **Harvester-Erschöpfungsrate** — die Grundproduktion in §13.7 unterstellt einen frischen Standort; mit Erschöpfung ist das ein Start-, kein Dauerwert | **offen, gehört in dieselbe Rechnung** | §4c, §13.7 |
| Regolith-Parität der drei Pfade | **entschärft** — löst sich weitgehend auf, wenn Wachstum über Harvester-Instanzen läuft | §4b, §4c |
| Harvester ohne Level-Up | entschieden; Umsetzung nur gemeinsam mit dem Zahlensatz | §13.5, §4c |
| Tatsächliche `ap_for_levelup`-Werte in der laufenden DB | ✅ verifiziert 2026-08-02: überall 10, nur Monument 20 | Anhang B |
| AP-Grundwert, Projektkosten, Bonus-Kurve | Vorschlag liegt vor, Owner-Entscheidung offen | §13.6 |
| Erstes Gebäudelevel günstiger (Early-Game-Tempo) | vorläufig: `f(1) = 0.5` | §13.6 |
| Bodengarantie je Domäne | vorläufig: keine | §13.1 |
| Braucht Versorgung noch eine eigene Rolle? | vorläufig: ja, bleibt unverändert | §6 |
| Lage des Verfall-Gleichgewichts | geklärt — existiert bei den aktuellen Werten nicht, §13.5 umgeschrieben | §13.5 |
| `decay.overcap_factor` 2.0 → 1.5 + sichtbarer Zustand | Vorschlag liegt vor | §13.1 |

### A.2 Folgearbeiten aus der AP-Zusammenlegung

Stellen, die noch von getrennten AP-Pools ausgehen und nachzuziehen sind.

| Thema | Ort |
|---|---|
| Außenmissions-AP-Staffel (2–10 AP) war gegen den Navigations-Grundpool kalibriert | §8b |
| Cantina: AP als Deckel für Vielhandel — wirkt jetzt erstmals wirklich | §12 |
| Onboarding-Hinweistexte sprechen von „Bau-AP verfällt" o. ä. | `gdd/onboarding.md` §16.2 |
| Sol-1–4-Budget-Rechnung rechnet mit getrennten Pools | `gdd/onboarding.md` §16.5 |
| `locked_actionpoints.personell_type` — Pool-Trennung oder nur Auswertungsmerkmal? | §13 „Implementierung" |
| AP-Malus bei Aufruhr (−20 %) trifft jetzt die gesamte Kolonie statt einer Domäne | §14 |

### A.3 Nach dem ersten Playtest zu kalibrieren

| Thema | Ort |
|---|---|
| Sicherheits-Hub: Vertrauens-Bonus, Event-Dämpfung, Recycling-Anteil | §4 |
| Uplink-Station: Tiefenscan-Basiskosten, Händler-Erscheinungsrate | §4 |
| Handelsposten: Baukosten, Decay, Supply, Handelswert-Bonus | §4 |
| Korvetten-Stacking ohne Supply-Limiter | §6 |
| Schiffs-Verschleiß `wear_per_sol` | §7 |
| Missionen: Werkstoff-Durchsatz, Credit-Missionen mit mehreren Drohnen, Erkundungsflug vs. Ring-Erkundung, Milderungs-Stacking | §8b |
| Kolonistengefahren: Sturm und Seuchenausbruch lösen beide `colony_threatened` aus | §9 |
| Cantina-Verhandlung: `negotiate_bonus`, `negotiate_success_chance` | §12 |
| Berater-Außenmissionen: Analytiker-Bonus (stärkster Effekt), Rang-1-Misserfolgsrate | §13 |
| Konsul: Händler-Einschätzung nicht binär, erster Cantina-Besuch ohne Konsul | §13 |
| Vertrauen: theoretisches Maximum bei Voll-Ausbau, Kolonisten-Zulage ohne Cooldown, Bauwesen-Events wiederholbar | §14 |
| Run-Aufgabenpool: Wirtschafts-Cluster (1, 7, 9), Kollision Aufgabe 11 mit 2/8 | §15 |
| Highscore-Formel: Gewichtung | §15 |
| Fail-State: −20-Trust-Schwelle, `nexus_debt`-Mechanik | §18 |
| `task_expedition_coverage: 19` als schwierigster Task-Target-Wert | §18 |
| Run-Ende: „Kolonie ansehen" setzt voraus, dass Koloniedaten erhalten bleiben | §18 |

### A.4 Offene Designfragen (kein Playtest nötig, Entscheidung steht aus)

**Nächste zusammenhängende Design-Runde: die Supply-Achse.** Die `supply_cost`-Werte sind gegen eine Wirtschaft kalibriert, in der Regolith knapper war als nach §13.7. Wird Bauen leichter, wird Supply relativ zum bindenderen Limiter — was §6 entspricht, aber verlangt, die Zielkolonie gegen den erreichbaren Cap gegenzuprüfen. Zu dieser Runde gehören die drei folgenden Deckel-Fragen: sie bestimmen gemeinsam, wie tief eine Kolonie überhaupt wachsen kann.

| Thema | Ort |
|---|---|
| **Supply-Achse unconstrained neu herleiten** — `supply_cost` je Gebäude, Cap-Quellen, Zielkolonie gegen erreichbaren Cap | §6, §13.7 |
| **Level-Deckel für Cantina und Krankenstation** — beide heute `NULL` (unbegrenzt), was dem „kleine Kolonie"-Prinzip widerspricht | §4c, §1 |
| **Instanz-Deckel für den Agrardom** — mit der Umstellung auf Instanzen offen; hängt am Organika-Rennen und am Tile-Budget | §4c, §3 |
| **`max_level = NULL` bei sieben Gebäuden** (Sciencelab, Temple, Agrardom, Hangar, Krankenstation, Monument, Cantina) — die `f(L)`-Kostenkurve läuft dort ohne natürlichen Endpunkt weiter | §4c, §13.6 |
| Stratege — neu bewerten und designen (eigener Pfad oder Modifikator?) | §13 |
| Cantina: verlässlicher Credits→Regolith-Kanal (heute nur Verkaufsrichtung garantiert) | §13.5, §12 |
| **Pfad-C-Regolith-Hebel neu denken** — der Organika→Regolith-Tausch fällt mit der Knappheitsordnung weg; offen, ob Pfad C überhaupt einen großen Regolith-Hebel braucht | §13.7, §4b |
| `geology` als Träger des Regolith-Produktionsbonus — Höhe und Balance gegen den Analytik-Pfad insgesamt; **möglicherweise überflüssig**, wenn Regolith-Wachstum über Harvester-Instanzen läuft | §13.5, §4c |
| Wird Pfad B (Hangar) durch den Regolith-Bedarf faktisch zur Pflicht? | §4b, §13.5 |
| Pfad A (Analytik) hat keine eigene Credits-Quelle — Kostensenkung statt Einnahme? | §4b |
| `agronomy`-Kenntnis: hat sie einen Organika-Effekt oder nur den Supply-Cap-Bonus? | §4b, §10 |
| Optionale dritte Bedingung für Run-Phase 1 (Roguelike-Variabilität) | §15 |
| Nexus-Boni in Phase 1 oder erst ab Phase 2? | §15 |
| Schiffe ohne Hangar (Events, Handelsdeals) — Phase 4+ | §6 |
| Kolonisten-Ausbildung — Design-Konzept, Phase 4+ | §10 |
| Exotics als vierter handelbarer Rohstoff — Phase 4+ | §3 |
| AP-Delegation zwischen Kolonien — Phase 4+ | §12 |

### A.5 Playtest-Instrumentierung (vor dem ersten Lauf einzubauen)

Ohne diese Messwerte ist keine der Zahlen aus §13.6 nach dem ersten Lauf begründet korrigierbar — man hätte nur Bauchgefühl.

| # | Metrik | Zielkorridor / Auslöser |
|---|---|---|
| 1 | **AP-Bilanz je Sol**, fünf Zahlen: Zufluss / Reparatur / Projekte / Handlungen / **ungenutzt** | ungenutzt > 15 % über mehrere Sole = Grundwert zu hoch |
| 2 | **Sole bis Fertigstellung** je Projekt, mit Start-Sol | erstes Drittel 4–6 Sole, letztes Drittel 1–2 |
| 3 | **Median gleichzeitiger Baustellen** | > 4 = Spieler streut, weil Einzelprojekte zu lang wirken |
| 4 | **Sol der letzten Projekt-Fertigstellung** (Kipppunkt-Test) | Ziel 85–92; über 95 = Kipppunkt fehlt |
| 5 | **Instandhaltungsanteil am Pool** je Sol | Ziel 20 % → 33 %; bleibt er unter 25 %, levelskalierte Reparatur nachrüsten (§13.5) |
| 6 | **Regolith-Bilanz**: Produktion / Reparatur / Level-Ups je Sol, aufgeschlüsselt nach Quelle (Harvester / Missionen / Handel / Events) | die eigentliche Wachstumsgrenze; besonders Sole 8–20 beobachten |
| 7 | **Supply-Auslastung** (`used/cap`) je Sol + Anzahl Sole über Cap | bleibt die Auslastung dauerhaft unter 70 %, ist Supply doch nicht bindend — dann wäre die Streichungsfrage aus §6 neu zu stellen |
| 8 | **Sole mit 0 AP je Domäne** | > 60 % in einer Domäne → prüfen, ob ein Hint nötig ist (keine Bodengarantie, §13.1) |
| 9 | **Regolith-Durchsatz je Pfad** (Frachter / `geology` / Cantina) | die drei Kanäle sollen im Playtest tatsächlich vergleichbar liefern (§13.5) |
| 10 | **Harvester-Umzüge pro Run** und Sole ohne Produktion durch Transit | Zielbild §4c: mehrere Umzüge, aber Umziehen darf keine Daueraufgabe werden |
| 11 | **Organika-Bilanz je Sol** (Produktion / Verpflegung / Missionsproviant / Events) und Anzahl Sole im Mangel | prüft, ob das Agrardom-Rennen aus §3 tatsächlich kippen kann |

Metrik 7 ist der explizite Falsifikationstest für die Entscheidung, Supply zu behalten. Metrik 9 für die Pfad-Parität.

---

## Anhang B — Drifts zwischen GDD, Config und Code

Gefunden bei der Durchsicht am 2026-08-02, alle unabhängig von den Designfragen und **alle noch offen**. Wo GDD und Code auseinanderlaufen, gilt laut CLAUDE.md der Code bzw. die Config als kanonisch — das GDD ist nachzuziehen, nicht umgekehrt.

| Ort | GDD/Doku sagt | Config/Code sagt |
|---|---|---|
| §4 „Level-Up" | CC-Upgrade = Ziel-Level × **30** Rg (Lv2 = 60) | `cc_upgrade_regolith_per_level = 20` (Lv2 = 40) |
| §7 „Fraktionaler Decay" | Dezimalwert 0,05–0,3 SP/Sol | tatsächlich 0,33–2,0 |
| §6 Config-Block | listet `supply.ship_cost` (Korvette 14, Frachter 6) | Key existiert in `config/game.php` nicht; die §6-Prosa sagt korrekt „Schiffe kosten kein Supply" |
| §13 „Rang-System" | Gesamt-AP = 6 + Bonus (10/13/18) | mit dem gemeinsamen Pool obsolet (§13.1) |
| `config/knowledge.php` (Kommentar) | „base 6 + Rang-1-Bonus 4", „Rang 2 bei 10 aktiven Ticks" | `rank_thresholds = [1 => 15, 2 => 45]`; Grundwert ändert sich mit §13.6 |
| `config/advisors.php` | `strategist` (id 93) + Slot-5-Kommentar | Stratege zurückgestellt (§13) |
| `data/sql/testdata.sqlite.sql` | Hangar supply 6, Cantina 4, Krankenstation decay 2.0 | `config/buildings.php`: 4 / 6 / 0.67 — Testfixture ist auf dem Stand **vor** dem Pfadwahl-Rebalancing 2026-06-28 |

### ⚠️ Akut: `harvester.max_level` — DB und Config widersprechen sich

**Die laufende Datenbank hat `max_level = 1`, `config/buildings.php` hat 8.** `game:sync-config` schreibt `max_level` aus der Config in die DB (`SyncConfig.php` Z. 130) — **ein Sync-Lauf setzt den Harvester still auf 8 zurück.** Die Config ist vor jedem weiteren Sync anzugleichen. `data/sql/testdata.sqlite.sql` hat ebenfalls bereits `max_level = 1`, ist hier also mit der DB konsistent.

**Nebenfolge:** Die Glockenkurve aus PR #220 ist für den Harvester wirkungslos. `game.production_curve[27]` definiert Level 1–8, aber bei `max_level = 1` greift nur der erste Eintrag — **8 Rg/Sol, dauerhaft**. Der Config-Kommentar („Growth beyond Lv8 comes only from Kenntnisse/Missionen/Handel") beschreibt einen Zustand, den es in der DB nicht gibt. Beim Agrardom (`max_level = NULL`, unbegrenzt) wirkt die Kurve dagegen voll.

**Sieben Gebäude haben `max_level = NULL`** (unbegrenzt): Sciencelab, Temple, Agrardom, Hangar, Krankenstation, Monument, Cantina. Für sie läuft die `f(L)`-Kostenkurve aus §13.6 ohne natürlichen Endpunkt weiter — bei Lv10 wäre `f` = 4,2, bei Lv15 = 6,2. Ob das gewollt ist oder ob diese Gebäude Deckel brauchen, ist offen.

### ✅ Erledigt: `ap_for_levelup` verifiziert (2026-08-02)

Owner hat die laufende DB geprüft. Ergebnis: **`ap_for_levelup` ist überall 10**, einzige Ausnahme Monument (50) mit 20. Die Migration `2026_04_17_000003_calibrate_building_ap_costs.php` (CC 10 / die meisten 20 / Hangar 30) ist **nicht aktiv** — entweder zurückgerollt oder später überschrieben.

Damit ist die Onboarding-Budgetrechnung (`gdd/onboarding.md` §16.5) korrekt und die Kalibrierung in §13.6 steht auf der Basis, die sie unterstellt hat.

> **Aber: eine flache 10 über alle Gebäude ist kein Balancing, sondern ein Default.** Dass der Wert die playgetestete Rampe reproduziert, macht ihn nicht richtig — es macht ihn nur konsistent mit dem, was bisher gespielt wurde. Er gehört zu den Platzhaltern (siehe „Zum Umgang mit den Zahlen" unten) und ist bei der Herleitung der Projektkosten frei wählbar.

> **Bei Umsetzung mitzuziehen:** `app/Console/Commands/ResetPlayer.php` — alle fünf Szenarien (`pre-phase2`, `phase2`, `near-fail-trust`, `near-deadline`, `objectives-done`) haben hartcodierte `supply`- und `regolith`-Werte samt Herleitungskommentaren, die an `ap_for_levelup` und den Supply-Formeln hängen.

---

## Zum Umgang mit den Zahlen in diesem Dokument

**Die meisten Zahlenwerte in Config, Datenbank und GDD sind Platzhalter.** Sie sind entstanden, weil irgendein Wert dastehen musste, nicht weil sie hergeleitet wurden. Das gilt für Baukosten, `decay_rate`, `supply_cost`, `ap_for_levelup`, Missionserträge, `bar.base_prices`, Verschleißraten und Kenntniskosten gleichermaßen.

**Konsequenz für jede Balance-Arbeit:** Ein bestehender Wert ist kein Argument. Wenn eine Rechnung nicht aufgeht, ist die erste Frage nicht „wie baue ich einen Ausgleich?", sondern „stimmen die zugrundeliegenden Werte überhaupt?". Der Zahlensatz ist zusammenhängend von der Designabsicht her herzuleiten — wenn dabei herauskommt, dass die Reparatur die Hälfte kosten und der Harvester das Doppelte liefern muss, ist das ein legitimes Ergebnis, kein Sonderfall.

**Geschützt sind nur ausdrücklich als Owner-Entscheidung markierte Werte.** Aktuell:

| Wert | Ort |
|---|---|
| Harvester ohne Level-Up (`max_level = 1`) | §13.5 |
| CC `max_level = 5` | §4 |
| Run-Länge 100 Sole | §18.4 |
| Ein gemeinsamer AP-Pool | §13.1 |
| Vier Beratertypen (Stratege zurückgestellt) | §13 |
| Werkstoffe bleiben als Ressource | §3 |

Alles andere ist verhandelbar. Insbesondere gilt das für den Zahlenvorschlag in §13.6 — er ist gegen die heutigen Werte gerechnet und teilt damit deren Unsicherheit.

> **Diese Regel gilt auch für Subagenten.** Wer mit Balance-Aufgaben beauftragt wird, bekommt sie explizit mitgegeben — sonst entstehen Vorschläge, die vorhandene Zahlen als Randbedingung behandeln und Workarounds darum herum bauen, statt den Satz neu zu rechnen.
