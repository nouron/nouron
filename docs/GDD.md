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
14. [Vertrauenssystem](#14-vertrauenssystem)
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

**Technischer Stack (Stand 2026-08-22):** PHP/Laravel Backend, SQLite, Blade-Templates. Frontend: Alpine.js + PicoCSS, SVG für Spielfelder (Hex-Grid, Systemkarte), Vanilla fetch() für Server-Calls. jQuery/Bootstrap-Migration vollständig abgeschlossen.

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

| ID | Name (DE) | Name (EN) | Kürzel | Ebene | Handelbar |
|----|-----------|-----------|--------|-------|-----------|
| 1  | Credits | Credits | Cr | User | Nein |
| 2  | Versorgung | Supply | Sup | User | Nein |
| 3  | Regolith | Regolith | Rg | Kolonie | Ja |
| 4  | Werkstoffe | Compounds | Co | Kolonie | Ja |
| 5  | Organika | Organics | Or | Kolonie | Ja |
| 12 | Vertrauen | Trust | V | Kolonie | Nein |

Startwerte pro Lauf: siehe `config/game.php`; siehe auch `docs/game-reference.md#ressourcen-startwerte`.

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

- **Regolith** — Lokaler Rohstoff: Mondgestein, Silikate, Mineralstaub. Wird vor Ort vom Harvester abgebaut. Primäre Verwendung: Rohbaukosten für Gebäude (außer CC und Harvester). Der Startwert ist eine moderate Reserve — narrative Begründung: vor Ankunft des Spielers wurden durch automatisierte Maschinen bereits Ressourcen bereitgestellt (Frontier-Depot).
- **Werkstoffe** — Veredelte Industriegüter: raffinierte Metalle, Legierungen, technische Komponenten. Nicht lokal produzierbar. Quellen: KI-Händler (immer verfügbar, Preis in Credits), Spieler-zu-Spieler-Handel, Events. Verwendung: Schiffbau, High-Tech-Gebäude, Reparaturen.
- **Organika** — Biologische Ressource: Nahrung, Medizin, Biodünger, organische Verbindungen. Entscheidend für Bevölkerung und Vertrauen. Produktionsgebäude: Agrardom (bioFacility). Wird durch eigene Produktion oder Handel beschafft.
- **Versorgung** — Versorgungskapazität (Nahrung + Energie + Wasser, kombiniert abstrahiert). Kein Rohstoff im klassischen Sinne — definiert die maximale Größe der Kolonie (Cap-Modell, siehe §6).
- **Vertrauen** — Systemmechanik, kein handelbarer Rohstoff (siehe §14).

### Ressourcen-Verwendungsdomänen

| Ressource | Gebäude früh (Rohbau) | Gebäude spät (High-Tech) | Schiffe | Reparatur |
|-----------|----------------------|--------------------------|---------|-----------|
| Regolith | Ja (außer CC + Harvester) | Ja (außer CC + Harvester) | Nein | Ja (außer CC + Harvester); pro Reparatur-Schritt siehe config/game.php |
| Werkstoffe | Nein | Ja (Akzent, nicht Hauptkosten) | Nein | Nein |
| Organika | Nein | Nein | Nein | Nein |
| Credits | Ja (immer — Grundkosten) | Ja (immer) | **Ja — nur Credits** | Nur Notreparatur (CC/Wohnhabitat) |
| Supply (Cap) | Gate (freie Cap ≥ supply_cost) | Gate | — | — |

**Ausnahme CC + Harvester:** CommandCenter und Harvester kosten beim Bau kein Regolith — sie sind der Einstiegspunkt der Kolonie und dürfen keinen Ressourcen-Catch-22 erzeugen (Regolith braucht Harvester, Harvester braucht Regolith). Beide sind auch von der Reparatur-Regolith-Kostenpflicht ausgenommen (AP-only) — das hält die Regolith-Quelle selbst immer reparierbar und verhindert eine Decay-Deadlock-Spirale.

**Supply ist kein Stockpile, sondern ein Cap:** „Supply-Kosten" eines Gebäudes = sein laufender `supply_cost`-Unterhalt (§6). Beim Bau wird nichts abgezogen — geprüft wird nur, ob die freie Cap den Bedarf deckt.

> **Designprinzip:** Regolith = lokaler Rohbau (alle Gebäude außer CC/Harvester + laufende Reparatur — der Dauer-Sink, der bis Run-Ende relevant bleibt). Werkstoffe = knapper, importierter High-Tech-Akzent (nicht produzierbar, nur Credits-Import). Organika = biologische Schicht (Versorgung/Verpflegung + Handel — **nicht** Bau/Schiffe; Sinks siehe §3 Organika). Supply = physisches Kapazitäts-Gate. Credits = universeller Tauschstoff + alleinige Schiffskosten.

### Werkstoffe: Singleplayer-Sicherheitsnetz

Im Singleplayer gibt es keinen Spieler-zu-Spieler-Handel. Werkstoffe können **nicht lokal produziert** werden — die Kolonie ist zu klein zum Veredeln. Es gibt drei Bezugswege, die bewusst eine Hierarchie bilden:

1. **Nexus-Direktimport (Sicherheitsnetz, garantiert):** Über die **Uplink-Station Lv1** (eine der aktiven Nexus-Anfragen, siehe §4) kann jederzeit eine beliebige Menge Werkstoffe gegen Credits gekauft werden — deterministisch, immer verfügbar, aber zu einem **festen, spürbar höheren Preis** als der Cantina-Spotpreis (siehe `docs/game-reference.md#werkstoff-preise`). Dies ist das Anti-Lock-Netz: ohne diesen garantierten Weg wäre jede Werkstoff-Baukostenanforderung potenziell hart blockierbar.
2. **Cantina (opportunistisch, günstiger):** Zufällige, zeitgebundene Kaufangebote zum niedrigeren Marktpreis (Kanal 1, §12). Belohnung fürs aufmerksame Spielen, aber **nie garantiert** — daher nie die einzige Quelle.
3. **Events (Bonus):** Liefern Werkstoffe als Bonus, immer mit Wahlmöglichkeit, nie kostenlos und nie als einzige Quelle.

> **Korrektur (2026-08-04):** Punkt 2 schrieb bisher „Cantina / Reisender Händler" als eine gemeinsame Quelle. Das sind zwei getrennte Systeme (§12 Kanal 1 „Bar/Cantina" vs. Kanal 3 „Reisender Händler") — die Vermischung hier ist vermutlich die Wurzel einer Owner-Rückfrage zum Pfad-C-Hebel-Vorschlag (§4b), ob eine neue Bar-Rolle „Reisender Händler" heißen soll. Kanal 3 verkauft ohnehin keine Standardressourcen wie Werkstoffe (§12, Kategorie-Tabelle) — für den Werkstoff-Bezugsweg hier ist ausschließlich Kanal 1 gemeint.

Typische Werkstoffe-Events (immer mit Wahlmöglichkeit, nie kostenlos):
- **Strandetes Frachtschiff** — Bergung kostet Navigation-AP, gibt Werkstoffe
- **Händlerkonvoi in der Nähe** — befristetes Kaufangebot, günstiger als Nexus-Importpreis
- **Trümmerfeld im System** — Flotte entsenden, Werkstoffe heimholen

> **Designprinzip Knappheit:** Werkstoffe sind das „Salz", Regolith das „Mehl". Späte/High-Tech-Gebäude verlangen Werkstoffe nur als **Akzent**, nie als Hauptkosten — denn jeder Werkstoff ist eine harte Credits-Ausgabe über den Import (siehe `config/game.php` für exakte Kosten). Die Knappheit erzwingt eine Credits-Allokations-Entscheidung (Werkstoff-Import vs. Schiffbau vs. Reparaturen), bleibt aber durch den garantierten Nexus-Import planbar statt zum Glücksspiel zu werden.

### Credits-Einnahmen

Credits werden durch vier Quellen erworben:

| Quelle | Beschreibung |
|--------|-------------|
| Relaisvergütung | Nexus zahlt pro Sol eine Vergütung für die Relais-/Sensor-Infrastruktur der Uplink-Station — abhängig vom Uplink-Station-Level |
| Galaktischer Rat | Staatliche Subventionen für aktive Kolonien pro Sol (Arbeitstitel: Name noch offen) |
| Handelsvertrag (Konsul) | Garantierte Bar-Einnahme, sobald ein Konsul zugewiesen ist und die Cantina Lv1+ steht — steigt mit Konsul-Rang (§12, §13) |
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
- **Regolith** für alle Gebäude außer CC + Harvester (Kosten gestaffelt nach Gebäudetyp und Verfügbarkeitspfad; siehe `config/buildings.php → build_cost`).
- **Werkstoffe** nur für späte/High-Tech-Gebäude **ab CC Lv3+**, als knapper Akzent, nicht als Hauptkosten (jeder Werkstoff ist eine harte Credits-Ausgabe über den Import, §3). Uplink-Station Lv1, Analytik-Labor und Hangar sind **werkstofffrei** (Uplink-Station ist das Import-Gate, würde eine Zirkelschluss-Regel erzeugen; die Pfad-Gebäude erhalten dieselbe Behandlung zur Pfad-Parität). Siehe `docs/game-reference.md#gebäude-baukosten` für exakte Werte.
- **Supply-Gate:** Bau nur möglich, wenn freie Supply-Cap ≥ `supply_cost` des Gebäudes (§6). Kein Abzug — reine Belegungsprüfung.

**2. Level-Up (jedes Level, flach — keine Eskalation):**
- **Regolith:** Ein fester Prozentsatz der Errichtungskosten pro Level (siehe `config/buildings.php`). Bewusst keine pro-Level-Steigerung. Abzug erst beim **Abschluss** des Level-Ups (`ap_spend ≥ ap_for_levelup`), nicht pro AP-Klick → AP-Invest bleibt reibungsarm.
- **CC-Upgrade (Sonderfall):** skaliert mit dem Ziel-Level, das CC ist der zentrale Progressionshebel und soll eine bewusste Regolith-Investition bleiben (siehe `config/buildings.php`).
- Harvester: **kein Level-Up** (Entscheidung 2026-08-02, §13.5). Er liefert ein festes Regolith-Grundeinkommen je Standort; Wachstum kommt aus einer zweiten Instanz (max. 2, §4c), aus Missionen, Events und Handel.

**3. Reparatur (laufender Dauer-Sink):**
- **Regolith + Construction-AP pro Reparatur-Schritt** (siehe `config/game.php`). Decay läuft bis Run-Ende → Reparatur hält Regolith über den gesamten Run relevant (Errichtungs-/Level-Up-Kosten allein versiegen nach Vollausbau).
- **Hartes Gate:** kein Regolith → Reparatur-Button gesperrt, Tooltip verweist auf Harvester-Reparatur. Kein Negativ-Saldo, kein Schuldensystem.
- **CC + Harvester ausgenommen** (AP-only) → die Regolith-Quelle bleibt immer reparierbar, die Decay-Spirale ist ein erholbarer Rückschlag, kein Hard-Deadlock.

> **Designziel:** Regolith ist das „Mehl" (reichlich, lokal, Dauer-Sink über Bau + Reparatur), Werkstoffe das „Salz" (knapp, importiert, nur als Akzent). Schiffe kosten ausschließlich Credits.

> **Entschieden (2026-06-22):** Ein Resource-Cap-System (Lagerlimit für Regolith/Werkstoffe/Organika) wurde geprüft und **verworfen** — siehe Owner-Entscheidung unter §16 Befund 1. Das Depot-Gebäude (`building_id=30`), das diese Mechanik getragen hätte, ist ersatzlos aus dem Spiel entfernt (Migration `2026_06_22_000001_remove_depot_building.php`). Begründung: Das eigentliche Spielproblem ist Ressourcenknappheit, nicht -überschuss; ein Lagerlimit hätte aktive Produktion bestraft statt belohnt — Widerspruch zum Roguelike-Designprinzip "kein Leerlauf, aktives Spielen wird belohnt". Bei Bedarf (z. B. neue Run-Modifier, die Überschuss als Mechanik nutzen) kann Depot + Cap-System später erneut eingeführt werden.

---

### Sicherheits-Hub (securityHub) — Mechanik

Der Sicherheits-Hub ist ein auf 1 Instanz begrenztes Infrastrukturgebäude (CC Lv3, max. Lv3). Er ist kein Pfadwahl-Kandidat und unterliegt keinem Pfadwahl-Bau-Gate. Seit der Zurückstellung des Strategen (2026-08-02) öffnet er **keinen Berater-Slot mehr** — er trägt sich vollständig über seine drei unabhängigen Effekte:

**Passiv — Vertrauen-Bonus:**
Ein Bonus pro Level (kumulativ). Thematisch: "Die Bevölkerung fühlt sich durch Schutzinfrastruktur sicherer." Bewusst niedriger als andere Wohlfahrts-Gebäude — Sicherheitsinfrastruktur ist utilitaristisch, kein Luxus-Bonus. Exakte Werte: `config/buildings.php`.

**Passiv — Event-Dämpfung:**
Wenn der Hub aktiv ist, werden negative Vertrauensverluste aus Zwischenfällen reduziert (prozentual; siehe `config/buildings.php`). Gilt für die Events `building_level_down`, `encounter_lost` und `colony_threatened`. Thematisch: "Der Hub sorgt nicht dafür, dass Vorfälle ausbleiben — er verhindert, dass sie eskalieren."

**Passiv — Level-Down-Recycling:**
Wenn ein Gebäude durch Decay ein Level verliert, gibt die Kolonie automatisch einen kleinen Ressourcenanteil zurück (handelbare Ressourcen: Regolith, Werkstoffe, Organika). Der Anteil liegt bewusst deutlich unter dem Reparaturwert, damit kein Anreiz entsteht, Verfall absichtlich zu provozieren. Exakte Prozentsätze: `config/buildings.php`.

> **TODO Balance:** Alle drei Effekte (trust-Bonus, Event-Dämpfungs-%, Recycling-%) nach erstem Playtest kalibrieren (siehe `config/buildings.php`). Compounds-Anforderung ist akzeptiert: Hub ist kein Progression-Gate (CC Lv3 hat kein Pflichtgebäude), sondern ein optionaler Resilienz-Baustein. In runs mit schlechtem Trade-Zugang kann der Hub später kommen — das verzögert nichts Zwingendes.

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

> **TODO Balance:** Genaue Tiefenscan-Basiskosten und Händler-Erscheinungsrate müssen vor Finalisierung der Lv2-Effekte festgelegt werden. Meta-Bonus für nächsten Run (Lv3) erst konkretisieren wenn Run-Abschluss-Mechanik vollständig ausgearbeitet ist (§15 N4). Baukosten und Effekt-Parameter: siehe `config/buildings.php`.

---

### Handelsposten (tradingPost) — Mechanik

Der Handelsposten ist ein auf 1 Instanz begrenztes Wirtschaftsgebäude (CC Lv4, konkurriert mit Religiöser Stätte um dasselbe Tile-Budget). Er verbessert Handelskonditionen über mehrere Kanäle hinweg:

<!-- TODO: Konsul-Effizienz-Absatz prüfen, evtl. Verwechslung mit trade-Kenntnis-Domäneneffizienz — separater Task -->
**Passiv — Konsul-Effizienz:**
Trade-Orders erhalten einen Bonus (AP-Kostenreduktion). Nur relevant wenn ein Konsul aktiv ist — dies ist ein Beispiel für einen Domänen-Effizienzbonus (§13.3). Exakte Werte: `config/buildings.php`.

**Passiv — Kanal-Rabatt (Design-Spec 2026-08-23):**
Jede Ausbaustufe schaltet einen zusätzlichen Handelskanal für einen Preisrabatt frei, kumulativ: Stufe I (Bekannter Gast) den Kanal Cantina-Zufallsangebote, Stufe II (Fester Kunde) zusätzlich den Reisenden Händler, Stufe III (Persönlicher Kontakt) zusätzlich Nexus/Corporate Contact (Orin). Beim Cantina-Kanal gilt: kein Stack-Effekt mit dem expliziten Konsul-Verhandlungsbonus (`negotiate_bonus`, ausgelöst über den "Verhandeln"-Button) — der Rabatt gilt nur für nicht verhandelte Angebote.
> **TODO Balance/Design:** Der passive, bereits bei der Angebots-Generierung eingerechnete Konsul-Rang-Rabatt (`trader_discount`, siehe `BarService::generateOffersForColony()`) ist von diesem Ausschluss NICHT erfasst und stackt aktuell multiplikativ mit dem Handelsposten-Rabatt (z.B. Rang-3-Konsul 30% + Handelsposten-Stufe-I 12% = kombiniert 38,4%). Ob das gewollt ist, wurde noch nicht bewertet — Whole-Branch-Review-Fund 2026-08-27, offene Design-Frage für den nächsten Balance-Pass.
Exakter Rabattsatz: `config/buildings.php` → `merchant_price_bonus`.

> **TODO Balance:** Baukosten und Decay nach erstem Playtest festlegen (siehe `config/buildings.php`).

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

Die Kommandozentrale schaltet durch Level-Upgrades zusätzliche **Terrain-Tiles** in der Kolonie-Zone frei — keine ganzen Ringe, sondern eine gestaffelte Anzahl individueller Tiles pro Level.

**Freischalt-Logik:** Tiles werden in Ringfolge (Ring 1 zuerst, dann Ring 2, dann Ring 3) und innerhalb eines Rings in fester Reihenfolge (Tile-ID-Reihenfolge) freigeschaltet. Regolith-Tiles (`regolith_*`) und unpassierbare Tiles (`terrain_impassable`) werden dabei übersprungen und zählen nicht — sie bleiben dauerhaft Exploration Zone.

Colony-Zone-Expansion (Tiles pro CC-Level): siehe `config/game.php → colony_zone_expansion`.

**Maximum:** Die Kolonie-Zone reicht bis zu einem festen Limit (siehe config oben). Bei vollständigem Ausbau aller anderen Gebäude bleiben je nach Konstellation noch Slots für Wohnhabitate — die Knappheit ist bewusst: Wohnhabitate konkurrieren mit Produktionsgebäuden um denselben Tile-Pool.

**Kein Spieler-Wahlrecht bei der Freischaltung.** Die Expansion ist deterministisch. Die Spielerentscheidung liegt darin, *welches Gebäude* auf *welchen* der freigeschalteten Tiles gesetzt wird — nicht welche Tiles freigeschaltet werden. Das hält die Interaktion auf Mobile einfach (kein tile-selection-Popup beim CC-Levelup).

Ring 1 (6 Tiles direkt um das CC) liefert die ersten 4–6 Colony-Zone-Tiles (sofern nicht alle regolith oder impassable). Der erste Ressourcen-Tile ist garantiert in Ring 1 (fixes Starttemplate, Typ variiert pro Run).

### Startposition

Die CC-Startposition ist pro Run zufällig. Das erzeugt unterschiedliche Ausgangssituationen und trägt zum Roguelike-Charakter bei.

### Sichtbarkeit — zwei getrennte Achsen

**Bebaubarkeit** (`is_colony_zone`) und **Sicht** (`is_explored`) sind entkoppelt — zwei unabhängige Achsen, die der Spieler über zwei verschiedene Verben erlebt:

- **Erschließen** (CC-Level): Die Kommandozentrale macht angrenzendes Gelände *bebaubar* (erweitert die Kolonie-Zone). Sie deckt das Tile **nicht** automatisch auf — ein neu erschlossenes Zone-Tile bleibt im Fog, bis es erkundet oder bebaut wird.
- **Erkunden** (Navigation-AP): Sonde/Raumfahrer lüften den Nebel und finden Ressourcen/Signale. Erkunden ist die einzige Quelle von Tile-Wissen.

Die Nav-AP-Kosten pro erkundetem Tile steigen mit dem Ring (`config/game.php → colony.explore_cost_per_ring`), gestaffelt von innen nach außen. Die Staffelung verlangsamt das vollständige Aufdecken der Karte bewusst — eine Pauschalrate würde die Karte zu schnell enthüllen und den Spannungswert des Fog of War zunichtemachen. Ring 1 ist beim Run-Start bereits automatisch erkundet; der Kostensatz greift praktisch nur für nachträglich erschlossene Tiles.

Daraus folgt:
- **Kolonie-Zone-Tiles** sind baubar, aber ggf. noch im Fog (`is_colony_zone=1, is_explored=0`). **Bauen auf einem solchen Tile deckt es auf** ("siedeln → sehen"). Der Spieler kann optional vorher per Navigation-AP erkunden, um vor dem Bauen zu sehen, was dort liegt (z.B. Gefahrenzone).
- **Exploration-Zone-Tiles** bleiben Fog of War — einzeln per Navigation-AP aufgedeckt (Ring-gestaffelte Kosten s.o.). Hier liegt der Erkundungs-Lohn (Regolith fürs Harvester-Verlegen, Signale/Funde ab Ring 3).

> Kernregel: **Die CC erschließt nur Gelände — sie siedelt nicht ins Unbekannte.** Erschließen ≠ Erkunden. Frühere Kopplung (CC-Ausbau erkundete Zone-Tiles automatisch) wurde 2026-06 entfernt, weil sie die beiden Achsen für den Spieler ununterscheidbar machte.

> **Blocker unter Fog — Lücken-Deduktion (Playtest-Review 2026-07-11):** Unaufgedeckte Tiles können `terrain_impassable` sein — der Spieler riskiert beim Erkunden also Nav-AP für ein nutzloses Tile. Das ist ring-abhängig unterschiedlich bewertet und bewusst so entschieden:
> - **Ring 2 enthält keine Blocker mehr** (`ColonyTileService::resolveTileType()`, Gewicht auf Hazard/Empty umverteilt). Grund: Ring 2 hat kein Regolith — eine Lücke in der "bald bebaubar"-Anzeige hätte dort *deterministisch* einen Blocker verraten. Das Aufdecken wäre beweisbar verschwendete AP (Falle ohne Entscheidung), das Nicht-Aufdecken trivial. Beides ist keine interessante Wahl.
> - **Ring 3+ behält Blocker selten.** Dort ist eine Lücke in der Anzeige mehrdeutig: meist Regolith (der Jackpot fürs Harvester-Verlegen), manchmal Fels. Das Aufdecken einer Lücke ist damit eine echte Wette mit positivem Erwartungswert — Information durch Abwesenheit als Feature, nicht als Bug. Keine AP-Erstattung beim Blocker-Fund (würde blindes Aufdecken belohnen und die Ring-Kosten-Drossel entwerten), keine Silhouetten unter Fog (würde die Ring-3-Ambivalenz zerstören).

> **Offener Designpunkt (2026-06, nicht umgesetzt):** Idee, den Erkundungsradius über die aktuelle Ring-3-Grenze hinaus zu erweitern, um zusätzliche Nav-AP-Sinks für spätere Sols zu schaffen (die Ring-Staffelung allein bremst, erschöpft sich aber irgendwann). Offene Sorge: ein größeres/dichteres Hex-Grid wird auf Mobile schwer navigierbar (Pan/Zoom-Aufwand steigt mit der Tile-Zahl). Vorzugsweise die Tile-Zahl von der Nav-AP-Sink-Zahl entkoppeln statt das Grid zu vergrößern — z.B. Signale/Points-of-Interest in größerer Entfernung ohne zusätzliches Hex-Rendering, oder eine Scan/Survey-Order auf Distanz statt physischer neuer Hexes. Nicht implementiert — nur als Richtung für ein späteres Balance-/Pacing-Update vermerkt.

### Visuelle Zone-Abgrenzung

Die Kolonie-Zone-Grenze ist auf kleinen Karten nicht mehr ein sauberer Ring, sondern ergibt sich aus dem `is_colony_zone`-Flag pro Tile. Das Frontend rendert Colony-Zone-Tiles mit einem warmen Basis-Tint (Farbschema: Weiß/Anthrazit/Rot-Palette), Exploration-Zone-Tiles mit einem kühleren, dunkleren Tint. Der Spieler erkennt die Grenze durch Farbe, nicht durch Position. Regolith-Tiles und impassable Tiles innerhalb der inneren Ringe sind immer Exploration Zone — sie wirken als visuelle "Lücken" in der Colony Zone, was die unterschiedliche Funktion deutlich kommuniziert.

### Tile-Typen und Schwierigkeit

Tile-Typen (z.B. "Reicher Erzknoten", "Armes Vorkommen", "Organik-freies Terrain") beeinflussen die Ressourcenproduktion. Die Schwierigkeit eines Runs steuert die Tile-Qualität: schwieriger Run = schlechtere Vorkommen, keine reichen Erznodes in Ring 1.

### Organika

Organika entsteht nicht auf Tiles (biologische Materialien kommen auf Planeten nicht natürlich vor). Stattdessen produziert der **Agrardom** (Gebäude innerhalb der Kolonie-Zone) Organika passiv pro Sol.

Organika wird **nicht** in Bau- oder Schiffskosten verwendet (§3 Verwendungsmatrix). Ihre Sinks (implementiert):

1. **Verpflegung (laufend, eskalierend):** Die Kolonie verbraucht pro Sol Organika proportional zur belegten Supply (siehe `config/game.php → food.supply_per_eater`). Tick-Reihenfolge: Produktion → Verpflegung → Vertrauen (Schritt 3a). Deckt der Vorrat den Bedarf → Bonus-Vertrauen, Hunger-Streak zurückgesetzt. Reicht der Vorrat nicht → verfügbarer Rest wird verbraucht, Hunger-Streak wächst, und ein **eskalierender** Trust-Malus greift (`TrustService::hungerPenalty`) — kein weicher Einmal-Tick, sondern eine Spirale: weniger Vertrauen → Produktionseinbruch → noch weniger Organika. Sättigung setzt den Streak (und damit den Malus) sofort zurück. Macht den Agrardom zum Pflichtgebäude. Bei sehr kleiner Frühkolonie entfällt der Verbrauch (rounding).
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
| **Regolith** | 1 Harvester-Instanz, festes Grundeinkommen pro Sol (Standard-Baseline seit Owner-Entscheidung 2026-08-05, §4c „Harvester-Zweitinstanz") | `geology` senkt Erschöpfung | Mission-Missionen liefern variable Mengen je Umlauf | **kein dedizierter Wachstumshebel** — opportunistischer Credits→Regolith-Kauf als Sicherheitsnetz (§12), siehe „Pfad-C-Hebel" unten |
| **Organika** | Agrardom | `agronomy` erhöht die Produktion mit jedem Level (glockenförmig) | Missions-Missionen liefern Organika je Umlauf | Ankauf über Bar-Angebote |
| **Credits** | Relaisvergütung, Ratssubvention | ⚠️ Hebel offen | Botenflug / Konvoi-Begleitung | Handelsvertrag + Organika-Verkauf |
| **Vertrauen** | Gebäude-Boni, Ereignisse | `health` + Krankenstation | `mission_aid_transport` (+2) | Cantina `trust_per_lv` + Handelserfolge |

**Pfad A Credits-Hebel:** Offen (s. Tabelle). Kandidat: Kenntnis-Effekt, der Kosten senkt — passt besser zum Pfad-Charakter als eine zusätzliche Einnahmequelle.

> **Prüfregel für künftige Mechaniken:** Wird eine neue Ressource, Kosten- oder Bedarfsachse eingeführt, ist zu prüfen, ob alle drei Pfade sie bedienen können. Ist das nicht der Fall, ist entweder die Mechanik anzupassen oder den unterversorgten Pfaden ein Hebel zu geben — **nicht** die Ungleichheit hinzunehmen.

### Pfad-C-Hebel: von Regolith zu Credits (Freigegeben 2026-08-05, konsolidiert 2026-08-06)

**Begründung und Zielrichtung.** Der ursprüngliche Pfad-C-Vorschlag war ein Organika→Regolith-Tausch, der der Knappheitsordnung (§3) widerspricht. Andererseits ist Pfad C nicht primär ein Regolith-Hebel nötig: Pfad A (`geology`) und Pfad B (`mission_supply_run`) schließen die Regolith-Lücke gemeinsam (siehe §13.7 „Neuherleitung gegen die 1-Instanz-Sockel-Baseline"). Der echte Engpass, gegen den Pfad C als „Pfad der Flexibilität" antreten soll, ist Credits — und die Kolonie produziert bereits strukturelle Organika-Überschüsse (siehe §4a), die bislang nicht monetarisierbar sind.

**Vorschlag: Organika-Verkauf als dritter Bar-Angebotstyp, Pfad-C-Hebel = Credits statt Regolith.**

Der Konsul/die Cantina bekommt einen dritten Angebotstyp neben Kauf (Credits→Ressource) und Tausch (Ressource↔Ressource): **Verkauf** (Organika→Credits), eng gefasst nur für Organika — Regolith- oder Werkstoff-Verkauf bleiben außen vor, um keinen unbeabsichtigten Umweg zur Regolith-Beschaffung zu schaffen.

**Mechanische Details:**

- **Preis:** deutlich unter dem Kaufpreis der Gegenrichtung. Spanne bewusst kalkuliert, um Arbitrage unattraktiv zu machen (siehe `config/game.php` für konkrete Werte).
- **Reserve-Untergrenze:** Verkaufsangebote nur, solange der Kolonie-Bestand über einer Mindestreserve liegt (Vorschlag: 2× Sol-Bedarf `food_need`). Schützt den Hunger-Spirale-Mechanismus (§3/§4a), verhindert zu aggressives Leerverkaufen.
- **Zugang:** Der Organika-Verkauf ist ein Angebotstyp des Reisenden Händlers (**Corvan**, Kanal 3, siehe §12). Er ist Bar-gated (braucht Cantina Lv1+), aber nicht Konsul-exklusiv — der Konsul-Rang skaliert über bessere Preise und erhöhte Angebotsfrequenz. Das ist die eigentliche Pfad-C-Prämie: wer früh in den Konsul investiert, profitiert schneller und stärker, während alle Pfade gleichzeitig Zugang haben.

**Zielgröße:** Offen. Eine belastbare Credits-Zielgröße erfordert eine vollständige Bilanz über den Run (siehe ausstehende Aufgabe in Anhang A.4) — dies ist kein Nebenprodukt der Regolith-Neuherleitung. Im Playtest misst man: erreichte Cr/Sol aus Organika-Verkauf je Konsul-Rang, gegen die später validierte Zielgröße.

> **Designhinweis:** Ein Organika-Verkauf schafft indirekt einen Umweg (Organika → Credits → Regolith-Kauf über Orin/Weg A, §4c). Nach der §13.7-Neuherleitung ist die Regolith-Lücke aber bereits durch Pfad A + B gedeckt — dieser Umweg käme on top einer geschlossenen Lücke, nicht zu ihrer Schließung. Zielmetriken nach Playtest: Anteil des Regolith-Zuflusses über diesen Credits-Umweg sollte eng bleiben, da dieser Kanal nur ein Überschuss-Ventil ist, kein tragender Baustein.

**Offene Implementierungsfragen (reine Code-/Config-Details, nicht Design-kritisch):** siehe Anhang A.4 sowie Folgepunkte für die Owner-Entscheidung unten.

**Offene Punkte für die Owner-Entscheidung:**
1. Zustimmung zur eng gefassten Verkaufsrichtung (nur Organika) als dritter Bar-Angebotstyp.
2. Preis-Spread und Reserve-Untergrenze aus den mechanischen Details oben sind Vorschlagswerte, keine hergeleiteten Zahlen — erste Playtest-Kandidaten, landen bei Umsetzung als neue Config-Werte in `config/game.php`.
3. Pfad A behält weiterhin keine eigene Credits-Quelle (offene Lücke in §4b-Tabelle) — dieser Vorschlag löst sie nicht, aber Bar-gating bedeutet pfadübergreifender Zugang.

Erst nach Freigabe: TDD-Umsetzung (`BarService`, dritter Offer-Typ, Reserve-Check) durch `game-developer`/`qa-tester`, Konfigwerte in `config/game.php`.

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
| **Analytik-Labor** | Level | Lv3+ | Lv1-3 **sind** die Kenntnis-Stufen (`cartography` Lv1, `geology`/`trade` Lv2, `defense` Lv3) — ohne sie bricht die Staffelung weg. Lv4/5 haben zusätzlich einen eigenen Effekt (Kenntnis-Kosten-Rabatt, §13.3), keine reinen Gate-Stufen mehr. |
| **Uplink-Station** | Level | Lv3 | §4 nennt sie „das einzige Kommunikationsgebäude der Kolonie". Eine zweite Funkanlage verdoppelt keine Reichweite. |
| **Sicherheits-Hub** | Level | Lv3 | Eine pro Kolonie. |
| **Handelsposten** | Level | Lv3 | Eine pro Kolonie. |
| **Cantina** | Level | offen | Zwei Kneipen in einer Kleinkolonie wirken falsch; eine bessere Kneipe nicht. |
| **Krankenstation** | Level | offen | Besser ausgestattet, nicht doppelt vorhanden. |
| **Religiöse Stätte** | — | **1 Instanz, Lv1** | Weder Instanzen noch Level. Sie ist ein Bekenntnis, kein Ausbauprojekt. |
| **Kolonialdenkmal** | — | **1 Instanz, Lv1** | Dito. Ein Denkmal, fertig oder nicht. |

### Harvester: wenige Instanzen, dafür beweglich

**Ein Harvester ist die Standard-Baseline eines Runs.** Früher war die Zielgröße einer vollständig ausgebauten Kolonie gegen eine Zwei-Instanzen-Rampe (Sol 1–30: eine Instanz, Sol 30–80: zwei Instanzen, Zielgröße ~21,8 Rg/Sol) konzipiert. Die Owner-Entscheidung (2026-08-05) korrigiert: Ein Harvester soll ausreichen, um einen Spieldurchlauf zu schaffen — die zweite Instanz ist optional ein Bonus, nicht garantierter Bestandteil. **Die neue Standard-Zielgröße ist der 1-Instanz-Zyklus-Durchschnitt** (siehe Sockelwert unten). Regolith kommt zusätzlich über Missionen, Events und Handel — der eine Harvester ist der Sockel, nicht die Skalierung.

**Kein Deckel-Zahlenwert mehr als Design-Ziel, sondern eine Obergrenze für den Sonderfall.** Höchstens 2 Instanzen bleibt als technische Obergrenze bestehen (mehr als eine zweite wäre ohnehin zu viel Skalierung für eine Kleinkolonie), aber die zweite Instanz ist jetzt explizit **kein Bestandteil der Standard-Zielkolonie** — ein Run, der nie eine zweite Instanz bekommt, soll trotzdem regulär abschließbar sein. Regolith kommt zusätzlich über Missionen, Events und Handel (§3, §13.7) — der eine Harvester ist der Sockel, nicht die Skalierung.

Der Harvester ist das einzige **bewegliche** Gebäude des Spiels (§4 „Harvester-Transit"), und diese Eigenschaft soll im Spielverlauf tatsächlich genutzt werden: **Ein Harvester wird pro Run mehrfach umgesetzt.** Dafür braucht es einen Grund, der zwingt statt nur einlädt.

**Erschöpfung der Vorkommen.** Ein Regolith-Tile trägt einen Harvester eine begrenzte Zeit, dann sinkt der Ertrag. Die Grundlagen dafür sind bereits angelegt:

- `colony_tiles.resource_max` — im Schema beschrieben als „Startwert (Basis für Erschöpfungs-Counter im UI)"
- drei Ergiebigkeitsstufen `regolith_rich` / `regolith_normal` / `regolith_poor` mit unterschiedlichem Vorkommen
- die Verlege-Vorschau mit Ertragsvergleich (siehe `config/game.php` für AP- und Regolith-Kosten)
- Verlegekosten skalieren nach Distanz und kolonialer Logistik (siehe `config/game.php`)

Damit entsteht die gewollte Schleife: fördern → Ertrag sinkt → Umzug lohnt → ein Sol Produktion und einige AP kosten → neues Tile. **Und Erkundung bekommt einen konkreten wirtschaftlichen Zweck**, weil man wissen muss, wo das nächste ergiebige Tile liegt, *bevor* der Umzug erzwungen ist.

#### Erschöpfungskurve und Umzugstakt

```
Ertrag = Frischwert × (0,5 + 0,5 × Restvorkommen / resource_max)
```

Ein Tile beginnt beim vollen Frischwert und fällt bis zum Ausschöpfen auf die **Hälfte** — nie auf null, damit ein vergessener Harvester nicht schlagartig stillsteht. Bei erschöpftem Vorkommen: Produktion 0, der Umzug ist erzwungen.

Die Frischwerte wurden 2026-08-18 nach PlaytestBot-Befunden erhöht (Early-Game-Regolith-Mangel). `resource_max` reduziert (sonst Standzeit zu lang, kaum Umzüge). **Sockel-Folgerechnungen in §13.7 wurden gegen die neue 1-Instanz-Baseline neu hergeleitet** — siehe dortige Neuherleitung.

| Phase | Harvester | Status |
|---|---|---|
| **Standard, ganzer Run** | **1 Instanz** | **Baseline** |
| Optionaler Bonus | **2 Instanzen** | **Nur wenn verfügbar** |

**Der eigentliche Regler ist die Umzugsgebühr, nicht die Kurve.** Verlegekosten sind pro Hex gesetzt (siehe `config/game.php`). Das macht einen typischen Umzug + Zielkundschaftung zu einer spürbaren Investition. Spielziel: **mehrfache Umzüge pro Run**, aber nicht als Routine — Erkundung bekommt dadurch echte strategische Bedeutung (wo ist das nächste ergiebige Tile?), und die Harvester-Verlegung wird zur wiederkehrenden Entscheidung, kein automatisches Refresh.

### Harvester-Zweitinstanz: Bezugsquelle

Die zweite Instanz ist kein garantierter Bestandteil (siehe Baseline oben), aber keine unmögliche Varianz. **Zwei unabhängige Quellen, beide selten und unsicher**, damit sie von Run zu Run unterschiedlich erreichbar sind und nicht planbaren Sockel-Zuwachs bedeuten.

**Kontrollfrage Temple/Monument:** kein vergleichbarer Gap. Beide sind laut `config/buildings.php` (Kommentar Zeile 240) bewusst `is_instanced = false` und laufen über den normalen Baumenü-Pfad wie jedes einfache Gebäude. Kein weiterer Nacharbeitsbedarf hier.

**Leitprinzip — angepasst, nicht mehr uneingeschränkt gültig.** Die ursprüngliche Fassung dieses Abschnitts leitete aus §3 ab, dass **ob** eine zweite Instanz erreichbar ist, nicht dem Zufall überlassen sein darf. Das gilt weiterhin für die **erste** Instanz (Bootstrap-Ausnahme, kein Catch-22) — für die **zweite** Instanz gilt es laut Owner-Entscheidung jetzt ausdrücklich **nicht mehr**: sie ist ein Bonus außerhalb der garantierten Zielkolonie, ihre Verfügbarkeit selbst darf variieren. Damit entfällt auch der Grund für die zweistufige „garantiert + opportunistisch"-Struktur nach dem Werkstoffe-Muster — es gibt keinen garantierten Weg mehr, nur noch opportunistische.

#### Weg A: Orin (`corporate_rep`) — eigene Kontaktperson, ausdrücklich NICHT der Reisende Händler

> **Owner-Korrektur 2026-08-05 (dritte Runde): Die Zusammenführung mit dem Reisenden Händler (Corvan Ashe, Kanal 3) ist VERWORFEN.** Erste Fassung dieses Wegs führte die Harvester-Beschaffung mit Corvan/`MerchantService` zusammen (Begründung: Owner-Klarstellung vom 2026-08-04, Cantina sei von Anfang an der Ort des Reisenden Händlers). Der Owner will stattdessen **eine eigene, dedizierte Figur** — sein Stichwort: „ein Agent, der Beziehungen zu Nexus haben könnte." **Gegenprobe, warum Corvan tatsächlich nicht passt:** `docs/characters/merchant.md` beschreibt ihn explizit als unabhängig — „answers to no company, no Nexus contract, and no colony charter." Eine Figur mit Nexus-Nähe bei ihm anzusiedeln widerspräche seinem eigenen, bereits festgelegten Charakterkonzept. Die Zusammenführung war also nicht nur unerwünscht, sondern auch in sich inkonsistent — gut, dass sie nicht umgesetzt wurde.

**Neue Zuordnung: Orin, `corporate_rep` (`docs/characters/corporate_rep.md`, `config('characters')` Key `corporate_rep`).** Sein bestehendes Charakterblatt passt inhaltlich bereits sehr genau, ohne Anpassung der Lore: „He appears when a supply contract needs negotiating, when extraction rights are being discussed, or when someone wants to know what Nexus actually intends" — Förder-/Extraktionsrechte und Nexus-Nähe sind bereits sein Kernthema, nicht neu erfunden für diesen Vorschlag. Sein Quirk („never admits who he works for — ask him and he names a different company than he did last time") trägt die narrative Rahmung direkt: **er vermittelt/verkauft im Namen ungenannter Interessen, die er nie offiziell als Nexus bestätigt** — kein offizieller Nexus-Kanal, sondern ein Grauzonen-Deal über eine Figur mit vermuteter, nie bestätigter Nexus-Nähe. Das passt sogar besser zur roguelike-Varianz als eine offizielle Institution: Ein anonymer Mittelsmann ist per Definition nicht planbar, eine Nexus-Behörde wäre es (genau das Problem der zweiten, verworfenen Runde dieses Abschnitts).

- **Technische Einordnung — weder `MerchantService` (Corvan, Kanal 3) noch die generische `BarService`-Gästerotation (Kanal 1, 0–2 anonyme Gäste/Sol).** Beide bestehenden Mechaniken passen strukturell nicht: `MerchantService`/`merchant.items` ist explizit Corvans Inventar, laut Owner tabu für diese Figur. Die generische Bar-Gästerotation ist auf häufigen, kleinteiligen Handel ausgelegt (60/40-Split Ressource↔Credits/Ressource↔Ressource, 2–4 Sole Gültigkeit) — ungeeignet für ein einzelnes, seltenes Großangebot mit einer benannten, wiedererkennbaren Figur. **Empfehlung: ein eigener, dritter Spawn-Check, strukturell nach dem `MerchantService`-Muster (periodische Erscheinungsprüfung + Angebot), aber mit eigenem Config-Namensraum und eigener Instanz — nicht Teil von `merchant.items`, nicht Teil des generischen `BarService`-Gästepools.** Arbeitstitel `config('game.corporate_contact')`, analog strukturiert zu `game.merchant`, aber unabhängig davon parametrisiert.
- **Bezug zum §12-Kanal-1-Vorschlag — erledigt, keine Doppelbelegung mehr offen (2026-08-05).** Die frühere Fassung dieses Punkts verwies auf den damals noch offenen „Gast-Archetypen"-Vorschlag (2026-08-04), der Orin unabhängig als Kandidaten für eine neue Kanal-1-Credits-Rolle genannt hatte. Dieser Archetyp-Vorschlag ist inzwischen vom Owner verworfen (§12 Kanal 1, Owner-Zitat dort) — die Kanal-1-Credits-Rolle ist jetzt „Corvans Netzwerk" (Corvan Ashe, Kanal 3), nicht Orin. Orin bleibt damit **ausschließlich** an den Harvester-Deal (Weg A hier) gebunden, keine Doppelbelegung, keine offene Koordinationsfrage mehr.
- **Zwei-Ebenen-Varianz, wie vom Owner gefordert:** Orins Charakterblatt nennt seine Cantina-Frequenz bereits als **„rare"** (Panels 1, 2) — das ist aber aktuell nur eine erzählerische Einstufung, keine im Code hinterlegte Zahl (kein Treffer für „Frequency"/„Panels" in `app/` — die `Game Role`/`Frequency`-Felder in `docs/characters/*.md` sind bislang Content-Taxonomie, nicht verdrahtete Mechanik). „Rare" allein deckt nur **Ebene 1** (erscheint Orin überhaupt). Für **Ebene 2** (bringt er, wenn er erscheint, den Harvester-Deal mit) braucht es eine eigene, niedrigere bedingte Wahrscheinlichkeit — passend zu seinem Hintergrund, der ihn meist mit anderen Themen zeigt (Versorgungsverträge, Regulierungsfragen) und nur manchmal mit einem konkreten Extraktionsrechte-/Ausrüstungsangebot. Playtest-Kandidat: Erscheinung ~alle 15–25 Sole (seltener als Corvans 10–15, passend zu „rare" vs. Corvans „occasional"), davon **~25–35 % mit dem Harvester-Angebot**, Rest mit anderer (noch nicht spezifizierter) Orin-Interaktion.
  > **Kombinierte Trefferquote — ausgerechnet, nicht nur die zwei Faktoren isoliert genannt.** CC Lv3 fällt laut §4c/§13.7 auf ~Sol 25–30; bis Sol 80 bleiben damit real ~50–55 Sole. Bei einem Intervall von 15–25 Solen sind das **~2–3 Orin-Erscheinungen nach dem Gate** pro Run. Bei 25–35 % Trefferchance pro Erscheinung liegt die Wahrscheinlichkeit, dass ein Run **überhaupt einmal** das Angebot sieht, überschlägig bei `1 − (1 − 0,30)^2,5` ≈ **55–60 %** in der Mitte der Spanne, am unteren Rand (25-Sol-Intervall, 25 % Trefferchance, spätes CC Lv3) eher **~40 %**. Und selbst dann muss die Kolonie in dem konkreten 2-Sol-Fenster 400–800 Cr flüssig haben. **Damit sieht spürbar weniger als die Hälfte der Runs ein tatsächlich kaufbares Angebot** — das ist vermutlich im Sinne von „nicht zwingend in einem Spielablauf", aber eine Zahl, die der Owner bewusst freigeben sollte, nicht implizit über zwei einzeln unauffällige Prozentsätze. Der Hebel, falls das zu selten wirkt, ist die Ebene-2-Chance (25–35 %), nicht das Erscheinungsintervall — letzteres ist Orins Charakterblatt-Kanon („rare"), Ersteres reine Spielbalance-Erfindung dieses Vorschlags.
- **Preiskalibrierung:** Der Preis liegt im Bereich eines mehrere-Sole-Sparprozesses — spürbar, aber machbar, „Bonus mit Opportunitätskosten" statt Unerreichbarkeit oder Trivialität. Exakter Preis: `config/buildings.php`.
- **CC-Lv3-Gate bleibt zusätzlich zum Erscheinen-Zufall.** Auch wenn Orin das Angebot früh macht, sollte die Kolonie es erst ab CC Lv3 kaufen/platzieren können — dieselbe fiktionale Begründung wie zuvor (Betriebsfähigkeit einer zweiten Schwermaschine hängt an der Koloniereife), nicht als Timing-Anker für eine garantierte Sockelgröße (die gibt es nach der Owner-Entscheidung oben nicht mehr).
- **Angebot darf nicht erscheinen, wenn es nicht kaufbar wäre.** Das Harvester-Angebot in Ebene 2 wird nur gewürfelt, wenn `instance_count < max_instances (2)` **und** CC Lv3 erreicht ist — sonst müsste die Implementierung raten, was bei „schon zwei Instanzen" oder „Gate nicht erreicht" mit einem angebotenen, aber nicht kaufbaren Deal passiert.
- **Platzierung wie zuvor:** Kauf → sofort platzierbar auf einem erkundeten Regolith-Tile, eigener Flow getrennt von `harvester_move`.
- **Content-Nacharbeit (nicht GDD — für `content-writer`):** `docs/characters/corporate_rep.md` führt aktuell `Game Role: information`. Für diesen Vorschlag braucht Orin zusätzlich eine Handelsfähigkeit — Empfehlung: `Game Role` um `bar_trade` erweitern (oder als kombinierte Rolle dokumentieren), da `information` allein keine Kauf-/Verkaufsinteraktion abdeckt (Template-Enum: `permanent | bar_trade | information | story_hook | event_only`). Bis zur Freigabe dieses Vorschlags bleibt das Charakterblatt unverändert — ich ändere es hier nicht selbst, das ist Content-Ownership.
- **Umsetzungsaufwand-Hinweis:** Neuer, eigenständiger Spawn-Check + eigenes kleines Angebots-Objekt (Item „harvester_module", Arbeitstitel) mit „platziere Gebäude" statt „wende Sofort-Effekt an" als Wirkung — nicht in `MerchantService` oder `BarService` eingehängt, sondern eigene, kleine Service-Ergänzung nach demselben Muster. Größerer Aufwand als eine reine Item-Config-Zeile (das war die ursprüngliche, jetzt verworfene Idee, es einfach `merchant.items` hinzuzufügen).

#### Weg B: Bergungsmission auf einer Ruinen-Kachel — bleibt bestehen, unverändert opportunistisch

**Warum nicht der alte `event_derelict_rig`-Vorschlag:** ein Event-Tile, das ohne Spielerhandlung „gefunden" wird, ist genau der Zufallsfund, den der Owner ablehnt. Der Ersatz braucht eine aktive Handlung mit Rahmung — das gilt unverändert, unabhängig von der heutigen Korrektur.

**Vorschlag: bestehende Ruinen-Mechanik erweitern statt ein neues System erfinden.** `config/missions.php` hat mit `mission_ruin_expedition` bereits das passende Muster: Ziel ist eine zuvor durch Erkundung + Tiefenscan aufgedeckte `ruin_tile` (`HangarService::ruin_tile`-Check), Versand kostet Navigation-AP + Organika, `repeatable: false` passt zu „einmal pro aufgedeckter Ruine" — ein Harvester-Fund ist kein wiederholbares Ereignis.

- **Neue Mission `mission_harvester_salvage`** (Arbeitstitel): `ships: ['freighter', 'corvette']`, `sol_distance` ~4 (wie `mission_ruin_expedition`), `requires: ['target' => 'ruin_tile']`, `repeatable: false`.
- **Narrative Rahmung:** eine havarierte/verlassene Förderanlage einer früheren Expedition — eine geborgene, ausgeschlachtete Maschine, die die Kolonie selbst nicht neu bauen, aber reparieren/reaktivieren kann. Dasselbe Muster wie die bestehende Ruinen-/Almanach-Lore (Ruinen implizieren frühere Präsenz im System), kein neues Lore-Element nötig, nur ein neuer Mission-Text (`content-writer`).
- **Belohnung ist ein Freischalt-Flag, kein Ressourcenwert** — neuer Reward-Typ (`'harvester_instance' => true` o. ä.), den das Missionssystem bisher nicht kennt (bisher nur `credits`, `regolith`, `compounds`, `research_ap`, `reveal_tiles`, `deep_scan`, `trust_event`, `loot_table`). **Umsetzungsaufwand-Hinweis:** derselbe Reward-Resolver-Mehraufwand wie bei Weg A, an anderer Stelle im Code (`HangarService`/`MissionService` statt der neuen Orin-Ergänzung).
- **CC-Lv3-Gate teilen, nicht verdoppeln.** Die Mission selbst braucht im `requires`-Block keine eigene CC-Prüfung, solange `ruin_tile`-Sichtbarkeit typischerweise erst nach vergleichbar viel Erkundung eintritt wie CC Lv3 selbst; feuert das im Playtest zu früh, ist ein explizites `requires.building_level` das Sicherheitsnetz.
- **⚠️ Unverifiziert: Spawnrate von `ruin_tile` pro Run.** Regolith-Tile-Verteilung nach Ergiebigkeit ist definiert in `ColonyTileService` (siehe unten), für `event_ruin`-Tiles ist aktuell keine Erzeugungslogik in der Codebase auffindbar (nur ein hartcodiertes Beispiel-Tile in `ColonySeedDemo`) — die „opportunistisch, selten"-Einstufung dieses Wegs ist unbelegt. Vor Umsetzung klären: sind Ruinen pro Run tatsächlich knapp?
- **Korrektur (2026-08-06, Code-Befund PR #237): Harvester-Reparatur ist grundsätzlich regolithfrei, für jede Instanz — die ursprüngliche Fassung dieses Punkts war falsch.** Die erste Fassung nahm den allgemeinen §7-Reparaturpfad (1 AP + 1 Rg je SP, §13.7) für die geborgene Instanz an. Bei der Umsetzung der Harvester-Zweitinstanz-UI (PR #237) stellte sich heraus: Der Harvester ist strukturell von Regolith-Reparaturkosten ausgenommen — er zahlt Reparatur ausschließlich in AP, unabhängig von der Instanznummer. Das ist keine neue, gesondert zu treffende Regel für Instanz 2, sondern dieselbe Bootstrap-Logik, die für den **Bau** von Instanz 1 gilt („Instanz 1 bleibt regolithfrei", weiter unten in diesem Abschnitt), vom Code konsequent auch auf die **Reparatur** angewendet — keine zwei getrennten Ausnahmen, eine einzige, konsistent durchgezogene.
  > **Konsequenz für Weg B:** Die geborgene Instanz (beschädigt ankommend, `status_points` ~25–30 % des Maximums — derselbe Schwellenwert wie `dispatch_min_sp_pct = 0.25` in `config/missions.php`) kostet zur Reparatur **0 Rg + ~14–15 AP** (bei angenommenen 20 max. SP, 1 AP je SP) — kein Regolith-Anteil. **Die frühere Aussage „Weg B ist günstiger als Weg A, aber nicht kostenlos" wird zurückgenommen:** Weg B ist noch günstiger als bisher angenommen — reine AP-Kosten gegen Weg As 400–800 Cr. Das ist okay, kein Balance-Fehler: Weg B ist ohnehin als die günstigere Route konzipiert, ihr Preis liegt in der Verfügbarkeit, nicht im Ressourcenaufwand. **Es verschärft aber die Bedeutung der `ruin_tile`-Spawnrate:** Diese war bisher als Randnotiz markiert (⚠️ unverifiziert, s. o.) — sie ist jetzt die **einzige** Bremse, die Weg B von einer strikt dominanten Route gegenüber Weg A unterscheidet. Ist sie zu hoch (Ruinen zu häufig auffindbar), verliert Orin/Weg A seinen Sinn als eigenständiger Bezugsweg; ist sie realistisch selten, bleibt die Zwei-Wege-Struktur intakt. Damit wird die Spawnrate zu einer **Vorbedingung** für die Balance dieses Abschnitts, nicht mehr nur zu einem offenen Detail.

**Warum zwei Wege, keine dritte parallele Route:** `geology` liefert bereits einen Produktionsbonus auf bestehende Instanzen (§13.7) — ein zusätzlicher kenntnisgebundener *Erwerbs*pfad würde die Pfad-A-Identität unnötig verwischen. Zwei unabhängige, seltene Quellen (kommerziell über Orin, physisch über Ruinen) decken die gewünschte Varianz ab, ohne dass beide in jedem Run gleichzeitig fehlen müssen.

**Weg A: Orin** (`corporate_rep`). Eigenständige Kontaktperson mit seltenen Auftreten und gelegentlichem Harvester-Angebot. Gate: CC Lv3. Preis: Credits (Bereich passend zu Opportunitätskosten). Chancen: niedrig genug, dass weniger als die Hälfte der Runs das Angebot tatsächlich verfügbar hat.

**Weg B: Bergungsmission.** Auf Ruinen-Tiles durchführbar, `repeatable: false`. Beschädigte Maschine kommt an, Reparatur kostet AP-only (kein Regolith). Ebenfalls selten und nicht garantiert. §13.7-Neuherleitung (2026-08-06) bestätigt die Balance gegen die 1-Instanz-Baseline — keine Zahlenwerte der Regolith-Bilanz ändern sich.

**Playtest-Monitoring:** Umzugsfrequenz pro Run (moderat, mehrere pro Laufzeit) und Anteil der Sole mit Ertragserschöpfung (Ziel: niedrig). Regolith-Tiles sind kein Engpass — der Bremser ist die Umzugsentscheidung, nicht die Verfügbarkeit von Standorten.

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

**Aufzuteilen in `max_instances` und `max_level`** (Owner-Entscheidung 2026-08-03). Beide nullable; `NULL` heißt jeweils unbegrenzt. Betroffen: `buildings`-Tabelle, `config/buildings.php`, `data/sql/testdata.sqlite.sql`, `SyncConfig`, `ColonyController::placeBuilding`, Techtree-Gates.

> **Es ist ein Blocker, keine Aufräumarbeit.** Für den Harvester kollidieren zwei beschlossene Aussagen in einem Feld: „kein Level-Up" (§13.5) und „Deckel 2 Instanzen" (dieser Abschnitt). Solange `max_level` bei instanzierten Gebäuden die Instanzzahl bedeutet, lässt sich nur eine der beiden abbilden. Dasselbe gilt für den Hangar, der beide Achsen braucht. **Die Aufteilung muss vor der Umsetzung von §13.7 und §4c stehen** — sie ist Schema-Arbeit für `db-migration-agent`, keine Balance-Frage.

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

> **UI-Anforderung:** Der Grenzertrag des nächsten Levels muss vor dem Levelup sichtbar sein (analog AP-Cost-Chip-Convention) — Spieler soll entscheiden können, ob sich z.B. Lv6→Lv7 noch lohnt, bevor er investiert. **TODO Implementierung:** Techtree-UI (`techtree/index.blade.php` + `techtree-view.js`) zeigt das aktuell noch nicht an.

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

Genaue kumulierte Gesamtwerte pro Gebäude und Level: siehe `docs/game-reference.md#ressourcenproduktion`.

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
| **Breite** — Anzahl Gebäudetypen | Bauplatz (15 Tiles) | Instandhaltung: Σ `decay_rate` in AP und Regolith pro Schadenpunkt, jeden Sol |
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
| CommandCenter | wächst mit jedem Level (max Lv5 erreicht ein Dach) |
| Wohnhabitat | wächst pro Einheit, max. 6 Instanzen (ergibt Tile-Limit) |
| Kenntnisse | **nicht-linear pro Level** (siehe unten) |

**Startsituation:** CC Lv1 liefert einen Basis-Cap, ohne Wohnhabitate. Erster Tutorial-Schritt: Wohnhabitat bauen → Cap erhöht sich. Genaue Werte: `config/game.php`.
**Hard-Cap:** 200 Supply.

> **Tile-Budget:** 10 Nicht-CC-Gebäude + 5 Wohnhabitat = 15 Tiles (voll). Wer das 6. Wohnhabitat will, muss ein anderes Gebäude opfern — bewusste Designentscheidung für Knappheit.

> **Designabsicht:** CC-Ausbau und Wohnhabitate sind die primären Cap-Quellen. Kenntnisse liefern einen zusätzlichen Bonus, der den Cap in Richtung 200 schiebt — aber nie alleine reicht. Wer militärisch eskalieren will, muss zuerst zivile Infrastruktur investieren.

### Schiffe und Supply

**Schiffe kosten kein Supply.** Die Flottensize wird durch folgende Limiter gebremst:

| Limiter | Mechanik |
|---------|---------|
| Hangar-Slots | Jede Hangar-Instanz belegt ein Tile; max. Schiffe = Hangar-Instanzen |
| Credits | Nexus-Anfragen für Schiffe kosten Credits pro Schiff (Kosten und Typ in `config/ships.php`) |
| Lieferzeit | Schiffe werden nicht sofort geliefert — die längsten Typen brauchen mehrere Sole Lieferzeit |
| Navigation-AP | Außenmissions-Dispatch kostet Raumfahrer-AP (`sol_distance × 2`) — mehr parallele Missionen = mehr AP-Verbrauch |

> **TODO Balance (Playtest):** Prüfen ob Korvetten-Stacking ohne Supply-Limiter auftritt. Falls ja: Credits/Lieferzeit-Werte verschärfen, nicht Supply-Kosten wieder einführen.

**Schiffe haben keinen passiven Decay.** Wartungsdruck entsteht durch aktiven Einsatz (Schiffs-Verschleiß — siehe §7). `colony_ships.status_points` sinkt durch Außenmissionen, nicht durch Zeitablauf.

> **TODO (Design, Phase 4+):** Sonderfall "Schiffe ohne Hangar" — durch Events, Handelsdeals oder andere Mechaniken könnte der Spieler Schiffe erwerben, die normalerweise nicht im Hangar baubar sind (z.B. erbeutete Fraktionsschiffe, Belohnungsschiffe aus Events). Diese wären per Run einzigartig und ein Roguelike-Element das jeden Durchlauf anders macht. Mechanik (Hangar-Pflicht? Supply-Kosten?) und Balance noch offen — für spätere Phase detailliert ausarbeiten.

### Supply-Kosten Gebäude

**Berater:** kein Supply-Verbrauch — Kosten laufen über Credits (siehe §13).

**CommandCenter und Wohnhabitat:** kein Supply-Verbrauch (sie definieren den Cap).

**Gebäude** (individuelle Supply-Kosten aus Technologie-Tabelle):

Jedes Gebäude hat einen individuellen Supply-Kosten-Wert (geringe für Produktionsgebäude, höhere für Infrastruktur und High-Tech). Exakte Werte: `config/buildings.php`.

> **Pfadwahl-Kostenbalancing:** Die drei Pfad-Gebäude (Analytik-Labor / Hangar / Cantina) sind bewusst unterschiedlich kalibriert um echte Abwägungen zu erzeugen. Jedes hat eine Schwachachse (Ressource oder Supply) und eine Stärkeachse (wo es effizient ist). Die Kosten drücken diese Unterschiede aus — wer Supply-begrenzt ist, hat den einfacheren Weg über einen Pfad; wer Regolith-begrenzt ist, einen anderen. Das Ziel: Kein Pfad ist dominant, alle sind gleich tragfähig bei unterschiedlichen Startbedingungen.
> 
> Aktuelle Kosten: `config/buildings.php` (für die drei Pfad-Gebäude).

> Supply-Kosten sind **sol-rate-unabhängig** — sie beschreiben eine permanente Kapazitäts-Belegung, keine Fluss-Größe.

> **Supply als Bau-Gate:** Ein Gebäude kann nur errichtet werden, wenn die freie Supply-Cap (`Cap − belegt`) den `supply_cost` des Neubaus deckt. Es wird **nichts abgezogen** — Supply ist ein Cap, kein Lager. Das ist die „Supply-Kosten"-Achse aus der Verwendungsmatrix (§3): Gebäude kosten Regolith (Abzug) **und** Supply (Cap-Belegung + Gate).

### Kenntnisse als Supply-Cap-Quelle

Kenntnisse **kosten kein Supply** — sie **erhöhen den Cap**. Jede der 7 Kenntnisse hat 5 Level; die Bonus-Progression ist nicht-linear (Glockenform: mittlere Level sind effizienter als Extremwerte). Kenntnisse haben **keinen Decay** — einmal erforschtes Wissen bleibt permanent.

Kenntnisse geben mit jedem Level einen Cap-Bonus, gestaffelt mit einer **glockenförmigen Kurve** (mittlere Level liefern den besten Wert pro AP, Extrem-Level sind ineffizienter). Alle Kenntnisse kombiniert können den Hard-Cap erreichen, aber kein einzelner Pfad reicht dafür — der Spieler muss breit recherchieren.

**Strategische Implikation:** Das System belohnt Breite (mehrere Kenntnisse auf mittlerem Level) über Tiefe (wenige Kenntnisse maxed). Exakte Progressionswerte: `config/knowledge.php`.

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
| Gebäude-Decay | Stand von Gebäuden; skaliert mit der **Anzahl Gebäudetypen**, nicht mit deren Level | täglich | Reparatur (AP + Regolith pro SP, siehe §4) |
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

**Fraktionaler Decay:** Die `decay_rate` ist ein Dezimalwert, gestaffelt in Klassen von "Robust" (langsamster Verfall) bis "Fragil" (schnellster Verfall) — siehe `config/buildings.php` bzw. die vollständige Tabelle in `docs/game-reference.md#4-gebäude-decay-raten--status-points`. Pro Sol wird dieser Wert von den `status_points` des Exemplars abgezogen. Ein ganzer SP geht erst verloren, wenn sich genug Verlust akkumuliert hat.

```
Beispiel (Formel-Illustration, nicht die aktuelle Klasse "Robust"):
max_status_points=5, decay_rate=0.5
  Nach Sol 1: status_points = 4.50
  Nach Sol 2: status_points = 4.00
  Nach Sol 3: status_points = 3.50
  Nach Sol 4: status_points = 3.00  ← zwei ganze SP verloren
```

**Konsequenzen nach Building-Typ:**

| Entität | Typ | Konsequenz bei SP ≤ 0 |
|---------|-----|----------------------|
| Leveled Building (allgemein) | Leveled | Level − 1; status_points reset auf max_status_points; INNN-Ereignis |
| Wohnhabitat | Instanced | **Instanz zerstört** (kein Level zum Abziehen); Supply-Cap sinkt; INNN-Ereignis |
| Hangar | Instanced | **Instanz zerstört**; zugewiesenes Schiff wird **unbrauchbar** (nicht zerstört); INNN-Ereignis |
*(Kenntnis — kein Decay; Kenntnisse haben kein SP-System, siehe §10)*

> **Instanced vs. Leveled:** Leveled Buildings verlieren ein Level und regenerieren SP — sie geben mehrere Chancen. Instanced Buildings (Wohnhabitat, Hangar) haben kein Level: Decay auf 0 zerstört die Instanz sofort. Das macht sie gefährlicher zu vernachlässigen, erlaubt aber bewusst riskantes Spiel (Repair-AP sparen auf eigene Gefahr).

> **Manuelle Reparatur:** kostet Construction-AP und Regolith pro Schritt. Hartes Gate — ohne Regolith ist der Reparatur-Button gesperrt. CC und Harvester sind regolithfrei reparierbar (AP-only, Bootstrap-Schutz). Vollständige Kosten-Regeln siehe §4 „Baukosten & Level-Up-Kosten" und `config/game.php`.

> **Notreparatur (CC und Wohnhabitat):** Wenn SP dieser kritischen Strukturen unter einen Schwellwert fällt, wird automatisch eine Notreparatur ausgelöst — kostet Credits statt AP. Verhindert unbeabsichtigten Verlust, nicht aber bewusste Vernachlässigung (Credits müssen vorhanden sein).

> **Hangar-Decay-Detail:** Ein Schiff im zerstörten Hangar bleibt in der Datenbank erhalten — es ist nur deaktiviert. Sobald ein neuer Hangar gebaut oder der alte repariert wird, ist das Schiff wieder einsatzbereit.

> **Schiffe haben keinen passiven Decay.** Schiffs-Verschleiß entsteht durch aktiven Einsatz (Außenmissionen), nicht durch Zeitablauf — siehe §7 "Schiffs-Verschleiß".

### Richtwerte (abgeleitet aus Technologie-Tabelle)

Die Technologie-Tabelle enthält für jede Entität einen "Sole bis Verlust"-Wert (ohne Wartung; intern: "ticks_until_lost"). Daraus leitet sich die `decay_rate` ab, wenn `max_status_points` standardisiert wird:

```
decay_rate = max_status_points / ticks_until_lost
```

Genaue `decay_rate`-Werte pro Gebäude und berechnete Verlustzeitspannen: siehe `docs/game-reference.md#verfall-und-decay-raten`.


> **Sol-Skalierung:** Die Sol-Anzahl ist zeitunabhängig — nur die Echtzeit-Dauer ändert sich je nach wie lang ein einzelner Sol in echten Stunden dauert. Das ist die gewünschte Eigenschaft des Sol-basierten Systems (intern: tick-basiert).

> Konkrete Werte per Migration in die Stammdaten-Tabelle (`buildings.decay_rate`). **Kenntnisse haben kein Decay-System** — `researches.decay_rate` ist für alle `knowledge_*`-Einträge 0 und wird im Tick-Loop übersprungen (GDD §10). **Schiffe haben keinen Zeit-Decay** — ihr Verschleiß läuft über Außenmissionen (siehe "Schiffs-Verschleiß" unten).

**Minimum:** Jede Entität hat einen Mindestwert für max_status_points; siehe `config/buildings.php`.

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

Jeder Schiffstyp hat eine unterschiedliche Verschleiß-Rate pro Sol im Einsatz. Leichte Typen (Drohne) verschleißen schneller; schwere oder gepanzerte Typen (Korvette) sind robuster. Exakte `wear_per_sol`-Werte: `config/ships.php`.

**Recall als Schonungs-Entscheidung:** Da Verschleiß pro Sol unterwegs anfällt, spart ein vorzeitiger Rückruf reale SP — Missionsertrag gegen Schiffszustand abwägen. Beim Dispatch selbst fällt kein Verschleiß an (dort wirken bereits Navigation-AP und Organika als Kosten).

**Dispatch-Sperre:** Schiffe unter kritischem Status-Points-Schwellenwert können nicht entsandt werden — erst reparieren. Der Dispatch-Dialog zeigt die erwartete Verschleiß-Prognose (`wear_per_sol × sol_distance × 2`, Hin- und Rückweg) als Chip und warnt, wenn die Mission das Schiff unter die Sperr-Schwelle brächte. Schwellenwert siehe `config/ships.php`.

**SP ≤ 0 unterwegs:** Die Mission wird automatisch abgebrochen (`state = aborted`), das Schiff kehrt flugunfähig zurück (`docked`, 0 SP), ein etwaiger Missionsertrag entfällt. Eintrag im Kolonieprotokoll (`colony_log`) und im Sol-Report. Schiffe werden **nie zerstört** — ein Totalverlust, der nur über Nexus-Ersatzkauf heilbar wäre, wäre ein Fail-Spiral-Risiko.

**Kein passiver Decay:** Ein gedocktes Schiff verliert keine SP. Das unterscheidet Schiffs-Verschleiß fundamental von Gebäude-Decay — nur Aktivität kostet.


> ⚠️ BALANCE CONCERN: `wear_per_sol`-Richtwerte sind ungetestet. Zielgröße: eine 3-Sol-Mission kostet 2–3 Construction-AP Reparatur (Drohne). Fühlt sich Verschleiß im Playtest wie Rauschen an → Werte ×1,5; frisst er den Construction-Pool → Drohne auf 1,0 senken.

**Reparatur:** Fixkosten pro Klick — Construction-AP gegen Statuswiederherstellung, gedeckelt auf `max_status_points`. Gleiche Interaktion wie Gebäude-Reparatur, damit sich „Reparieren" spielweit konsistent anfühlt; der AP-Verbrauch wird vorab als Chip am Button angezeigt. Exakte Kosten: siehe `config/ships.php`.

> **Offen:** Zusätzliche Credit-Kosten pro Reparatur (`config/ships.php → repair_cost_per_point`) sind im Design vorgesehen, aber noch nicht implementiert — eigener Balance-Task.

> **Designabsicht:** Schiffe, die viel fliegen, brauchen Wartung. Das erzeugt eine natürliche Kosten-Nutzen-Entscheidung: Intensive Missionsnutzung ist teuer in Construction-AP, die sonst in Gebäude fließen könnten.

---

### Berater-Burnout

Berater können nicht dauerhaft auf Hochtouren laufen. Nach langer Aktivität steigt die Wahrscheinlichkeit, dass ein Berater für eine begrenzte Zeit ausfällt — **Burnout**. Der Ausfall ist nicht garantiert, aber wahrscheinlicher, je länger der Berater ununterbrochen aktiv ist.

**Mechanik (probabilistisch):**

```
burnout_chance(tick) = base_chance × growth_factor^(active_ticks / threshold) × rank_dampener(rank)
```

Die Formel benutzt vier Parameter: eine Basis-Burnout-Chance (niedrig bei Spielstart), einen exponentiellen Steigerungs-Faktor (über Arbeitsdauer), einen Schwellwert (wann die Chancen signifikant ansteigen) und einen Rang-Dämpfer (erfahrenere Berater sind robuster).

Konkrete Parameterwerte: `config/game.php → advisors.burnout.*`.

**Was passiert bei Burnout:**
- `unavailable_until_tick = current_tick + recovery_ticks` (Länge abhängig von Rang: Junior länger, Experte kürzer)
- `active_ticks` wird **zurückgesetzt** (der Berater startet frisch nach der Erholung)
- Der gemeinsame AP-Pool (§13.1) sinkt für die Dauer um den AP-Beitrag dieses Beraters; sein Domänen-Effizienzbonus (§13.3) entfällt ebenfalls
- INNN-Ereignis: „[Name] benötigt eine Auszeit — Kolonie-Kapazität vorübergehend reduziert."

Erfahrenere Berater erholen sich schneller — und haben schon durch den Rang-Dämpfer eine geringere Burnout-Chance. Exakte Erholungszeiten pro Rang: `config/game.php → advisors.burnout.*`.

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

> **Idee (festgehalten 2026-07-04, später konzipieren):** Preis/Qualitäts-Tradeoff beim Nexus-Kauf — Nexus verkauft nicht unbedingt das beste Material. Wahl zwischen "teurer kaufen → guter Status (volle SP)" und "günstiger kaufen → reparaturbedürftig (niedrige Start-SP)". Verzahnt den Credits-Sink mit dem Reparatur-Sink (§7) und der Dispatch-Sperre (billiges Schiff unter Schwellenwert kann nicht sofort auf lange Mission). Noch nicht designt.

### Schiffs-Besitz-Modell

Hangare sind **operationale Slots** — nur ein Schiff pro Hangar-Instanz kann entsendet werden. Darüber hinaus können Schiffe **ohne Hangar-Zuweisung** existieren (`hangar_instance_id = NULL`, `ship_state = 'pending'`):

- Entsteht durch Wrackbergung, Händler-Kauf oder Nexus-Lieferung wenn kein freier Hangar-Slot vorhanden
- Sichtbar im Hangar-Screen als separater Bereich "Nicht zugewiesen" mit Decay-Countdown
- Verfällt automatisch nach N Solen (TickService) wenn nicht einem Hangar zugewiesen
- **Decay-Zeit:** nach Playtest kalibrieren

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

> **Korrektur (2026-08-18):** Credit-Belohnungen angehoben (game-designer-Review, PlaytestBot zeigte chronischen Credits-Mangel — die aktive Missionsschiene lag effektiv unter dem passiven Einkommenssockel aus `nexus_subsidy` + Relaisvergütung). Tabelle unten zeigt die aktuellen Werte aus `config/missions.php`.

| Key | Name | Schiff | Gate / Verfügbar |
|---|---|---|---|
| `mission_courier_run` | Botenflug | Drohne | sofort |
| `mission_recon_flight` | Erkundungsflug | Drohne | sofort |
| `mission_deep_survey` | Signalvermessung | Drohne | bekanntes Signal-Tile |
| `mission_prospecting_flight` | Prospektionsflug | Drohne | Geologie Lv1 |
| `mission_data_sweep` | Datensammelflug | Drohne | Kartografie Lv1 |
| `mission_supply_run` | Versorgungsfahrt | Frachter | Frachter vorhanden |
| `mission_trade_convoy` | Handelsfahrt | Frachter | Handel Lv1 |
| `mission_aid_transport` | Hilfsgütertransport | Frachter | Gesundheit Lv1 |
| `mission_salvage_sweep` | Trümmerbergung | Frachter o. Korvette | Bautechnik Lv1 |
| `mission_escort_convoy` | Konvoi-Begleitung | Korvette | Korvette vorhanden |
| `mission_perimeter_patrol` | Umkreis-Patrouille | Korvette | Verteidigung Lv1 |
| `mission_ruin_expedition` | Ruinen-Expedition | Frachter o. Korvette | tiefengescanntes Ruinen-Tile |
| `mission_long_range_expedition` | Fernexpedition | Drohne | Kartografie Lv3 |

Exakte Kosten (Navigation-AP, Organika-Proviant, Zusatzmaterialien), Distanzen, Belohnungen und Effekte pro Mission: siehe `config/missions.php`.

**Schiffsrollen:** Drohne = Information (Tiles, Scans, Daten), Frachter = Güter, Korvette = Schutzdienste und Bergung. Nicht jede Mission steht jedem Schiff offen — das gibt der Akquise-Entscheidung (§8b Akquise-Pfade) strategisches Gewicht.

**Gate-Schema — nur zwei Typen:** Eine Mission ist entweder an **eine Kenntnis mit Mindestlevel** gebunden oder an ein **Ziel-Tile** (Signal-/Ruinen-Tile — die Mission braucht physisch ein Ziel). Keine CC-Level- und keine Gebäude-Gates: Gebäude-Gates wären redundant (jede Kenntnis setzt das Analytik-Labor ohnehin voraus), CC-Gates wären eine dritte, schwer kommunizierbare Bedingungsart. 4 ungegatete Missionen + 2 Ziel-Missionen sind **immer** verfügbar — jedes Schiff hat ab dem ersten Sol sinnvolle Einsätze.

**Pfadwahl-Interaktion (Hangar-first):** Wer den Hangar-Pfad vor dem Analytik-Labor wählt, hat noch keine Kenntnisforschung — die kenntnis-gebundenen Missionen erscheinen ausgegraut mit Bedingung. Das ist Absicht (geprüft 2026-07-04): Hangar-first heißt realistisch Drohne zuerst, und die Drohne ist mit 3 sofort verfügbaren Missionen am besten versorgt; der Frachter hat mit der Versorgungsfahrt eine wiederholbare, Organika-positive Kernmission. Die ausgegrauten Missionen sind die sichtbare Zugkraft, das Labor als zweites Pfadgebäude nachzuziehen — Pfade sind Sequenzierung, kein Ausschluss. ⚠️ Playtest-Beobachtungspunkt: Fühlt sich eine früh gekaufte Korvette vor dem Labor-Bau unterbeschäftigt an, ist der Hebel eine zweite ungegatete Korvetten-Mission (z.B. Umkreis-Patrouille auf „sofort" senken), nicht die Streichung der Gates.

> **Idee (festgehalten 2026-07-04, später konzipieren):** Bar-Begegnungen (Cantina-NPCs) können Missions-Varianten mit verbesserten Boni oder veränderten Parametern anbieten — als Alternative für den Pfad Hangar-first → Cantina-second (vor dem Labor). Ziel: verschiedene Spielweisen gleichwertig halten (Roguelike-Varianz). Noch nicht designt.

**Roguelike-Varianz gratis:** Da pro Run nur eine Teilmenge der Kenntnisse verfügbar ist (§10), fehlen in manchen Runs 2–3 der kenntnis-gebundenen Missionen (Prospektion, Datensammelflug, Handelsfahrt, Hilfsgütertransport, Trümmerbergung, Patrouille, Fernexpedition) — jede Missionsökonomie spielt sich pro Run anders, ohne Zusatzsystem. 6 der 7 Kenntnisse gaten je 1–2 Missionen; Agronomie bleibt frei als Reserve für spätere Missionstypen.

**Kenntnis-Skalierung — Erfahrung senkt den Proviantbedarf:** Eine einzige, spielweite Regel:

```
Organika-Kosten der Mission = Basis − (Level über Gate − 1), Minimum 1 pro Sol
```

Navigation-AP skalieren nie (die AP-Staffel ist das Raumfahrer-Progressionsgate, §13). Missionen ohne Kenntnis-Gate skalieren nicht. Begründung: Kostensenkung statt Belohnungserhöhung funktioniert für alle Belohnungstypen einheitlich (auch Tiles, Scans, Almanach — nicht bezifferbar), lässt Balance-Deckel unberührt, und erhält den Organika-Sink. Exakte Skalierungswerte und Basis-Kosten: `config/missions.php`.

**Wiederholbarkeit:** Missionen sind wiederholbar; Ausnahmen: Signalvermessung verbraucht das Signal-Tile, Ruinen-Expedition ist einmalig pro enthülltem Ruinen-Tile. Die natürliche Drossel für alles andere ist die Kostentrias Nav-AP + Organika + Verschleiß (Reparatur: Construction-AP + Regolith, §7).

#### Resolution

- **Rückkehr:** `return_tick = dispatch_tick + 2 × sol_distance`. Die Auflösung läuft im Tick im selben Schritt wie der Schiffs-Verschleiß (§7), **nach** dessen Anwendung — der SP-0-Abbruch (`state = aborted`, kein Ertrag) hat Vorrang. Bei Rückkehr: `state = completed`, Schiff `docked`, Belohnung wird gutgeschrieben, Eintrag im Kolonieprotokoll (`colony_log`) und im Sol-Report.
- **Recall:** Keine anteilige Belohnung, keine Rückerstattung — auch nicht bei sofortigem Abbruch im selben Sol wie der Dispatch (Nav-AP und Organika werden beim Dispatch instant fällig, unabhängig von der tatsächlich zurückgelegten Zeit). Keine Mindestwartezeit vor dem Recall — der Spieler kann jederzeit zurückrufen, verliert dabei aber immer die vollen Dispatch-Kosten. Der Wert des Rückrufs ist gesparter Verschleiß (§7 „Schonungs-Entscheidung") — anteilige Erträge würden systematisches Halbstrecken-Abbrechen zum Optimalpfad machen.
- **Kein Ausgangs-Roll:** Anders als Berater-Außenmissionen (§13) gibt es kein Erfolg/Teilerfolg/Misserfolg-Würfeln — Schiffe haben kein Rang-Analogon, und die Risiko-Achse existiert bereits über Verschleiß + Abbruch. Zufall beschränkt sich auf die Belohnungshöhe der Fund-Missionen (Prospektion, Bergung, Fernexpedition), deterministisch aus dem Run-`rng_seed` (ADR 0003).

> **Geprüft und verworfen (2026-07-04):** Ein zustandsbasierter Missionsausgang (Rückkehr-SP bestimmt Ertragsstufe, analog §9). Da Verschleiß deterministisch ist, wäre der Ausgang beim Dispatch bereits bekannt — kein Risiko, sondern eine Doppelbestrafung langer Missionen (die Fernexpedition kehrt selbst mit vollen Start-SP bei 25% zurück und würde immer „fehlschlagen") plus Vollreparatur-Zwang vor jedem Start. Ein reiner Würfel-Fail wiederum verletzt „Opportunitätskosten statt Strafe" (§1.1): Schiffs-Missionen kosten harte Ressourcen im Voraus — ein Fehlschlag vernichtet Bezahltes. Fehlt im Playtest Spannung, ist der Hebel die Spanne der Fund-Missionen (Loot-Tabellen verbreitern), nicht ein Fehlschlag-Layer.

#### Missionslog

Jede Mission wird in `colony_hangar_missions` gespeichert (`destination` trägt den `mission_key`, Sol-Distanz aus dem Katalog, Zustand `active/completed/recalled/aborted`). Im Hangar-Screen einsehbar; abgeschlossene Missionen zeigen die erhaltene Belohnung.

> ⚠️ BALANCE CONCERN: Trümmerbergung und Fernexpedition sind neben Import und Cantina eine dritte Werkstoff-Quelle. Richtwert-Deckel: max. ~1 Werkstoff pro Missions-Sol Durchsatz je Schiff — der Nexus-Import (§3) muss die schnellere, die Mission die günstigere Option bleiben. Nach Playtest kalibrieren.

> ⚠️ BALANCE CONCERN: Botenflug/Konvoi-Begleitung sind wiederholbare Credit-Quellen. Mit mehreren Drohnen können diese skalieren — gegen Relaisvergütung und Berater-Upkeep (§13) prüfen; notfalls Prämien senken statt Cooldowns einführen.

> ⚠️ BALANCE CONCERN: Der Milderungs-Effekt der Umkreis-Patrouille überschneidet sich mit dem Almanach-Bonus `encounter_prep` (§17). (Die frühere dritte Überschneidung, die Strategen-Sicherheitsanalyse, entfällt mit der Zurückstellung des Strategen, §13.) Regel: Milderungseffekte stapeln nicht — es gilt maximal eine Ausgangsstufe Milderung pro Gefahr, der stärkste Effekt wird verbraucht.

> ⚠️ BALANCE CONCERN: Erkundungsflug darf die Ring-Erkundung nicht obsolet machen. Er ist als effizientere, aber langsamere Alternative für äußere Ringe gedacht. Wirkt er im Playtest dominant → auf weniger Tiles senken oder Distanz erhöhen.

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

Dieser Abschnitt ersetzt die frühere flottenbasierte Fassung vollständig — diese Mechanik existiert seit der Streichung von Galaxie/Systemkarte (2026-06-20) nicht mehr. Begegnungen finden ausschließlich auf der Kolonieoberfläche statt (Hex-Grid, §4a). Es gibt kein Kampfsystem, keine Stärkewerte, keine Schiffe.

**Implementiert 2026-08-16.** `EncounterService` löst alle drei Gefahrentypen pro Sol auf, Ausgangsstufen basieren auf SP-Zustand (siehe unten). Sturm nutzt 1-Sol-Vorwarn über `colony_log`. Geologische Instabilität und Seuchenausbruch sofort ohne Vorwarnung. Cooldown zwischen Ereignissen (`game.encounter.cooldown_sols`) puffert gegen Spiral-Risiko. PlaytestBot-Befunde bestätigten: Phase 1 brauchte Trigger-Chance-Ramping (0→voll über erste 15 Sole) um die Sol-30-Deadline erreichbar zu halten — früh ist schwächer, aber nicht abwesend. Lore: Startbestand kommt aus automatisiertem Frontier-Depot, die Welt ist gefährlich, aber frisch gelandete Kolonien sind verwundbarer.

Die Kolonie ist keine Festung, sondern eine verwundbare Ansiedlung auf einer kaum erschlossenen Welt. Gefahren haben keinen Marschbefehl und keine Absicht — sie sind lokale Zwischenfälle: Wetter, Geologie, Erschöpfung der Kolonisten. Es gibt keine Konfrontation im militärischen Sinn, nur einen Zustand vorher und einen Zustand danach.

### Grundprinzip: Zustand statt Konfrontation

Statt gegen eine gegnerische Stärke gewürfelt wird, wirkt jede Kolonistengefahr direkt auf den bestehenden Zustand der Kolonie — auf `status_points` betroffener Gebäude (§7) und auf Vertrauen (§14). Es gibt keinen Gegner-Stärkewert; es gibt nur die Frage, wie gut die Kolonie vorbereitet war. Ein Gebäude mit vollen SP übersteht ein Ereignis fast unbeschadet, ein vernachlässigtes Gebäude nimmt deutlichen Schaden. Wartungs-AP wird damit indirekt zur Gefahrenabwehr, ohne dass der Spieler im Moment des Ereignisses aktiv reagieren muss — das belohnt sowohl vorausschauendes aktives Spiel als auch entspanntes passives Spiel mit solider Grundwartung.

**Ausgangsstufen** (SP-Anteil des betroffenen Gebäudes zum Ereigniszeitpunkt):

| SP-Zustand | Ausgang | Trust-Event | Effekt |
|---|---|---|---|
| Hoch | Abgewehrt | `encounter_won` | kein/minimaler SP-Verlust |
| Mittel | Beschädigt | `encounter_lost` | SP-Verlust |
| Niedrig | Kritisch | `colony_threatened` | SP-Verlust + ggf. sofortiger Level-Down bzw. Instanzverlust (§7-Regeln) |

Damit werden zugleich die in §14 markierten Trust-Events mit Anwendungsfällen unterlegt. Der Sicherheits-Hub schwächt alle drei Ausgänge ab (bestehende Regel, §14).

### Gefahrentypen

| Gefahr | Trigger | Konsequenz | Häufigkeit (Richtwert) | Abschwächung |
|---|---|---|---|---|
| **Sturm** | Zufällig; Basis-Chance/Sol steigt mit Run-Schwierigkeit; trifft 1 (selten 2) zufällige Gebäude der Colony Zone | SP-Verlust nach Ausgangsstufe (Tabelle oben) | variabel nach Phase (häufiger mit mehr Gebäuden) | Hohe SP durch regelmäßige Reparatur |
| **Geologische Instabilität** | Gekoppelt an das Harvester-Tile; Chance steigt mit Solen seit letzter Relocation, sinkt mit Kenntnis Geologie | Produktionsausfall des Harvesters für eine Weile (statt zusätzlichem Trust-Malus — kein doppelter Bestrafungseffekt) | seltener als Stürme | Kenntnis Geologie senkt Chance; Relocation setzt Zähler zurück |
| **Seuchenausbruch** | Emergent statt rein zufällig: nur möglich bei echter Vernachlässigung (Hunger-Spirale oder sehr niedriges Vertrauen), dann Zufallschance/Sol | Supply-Cap oder AP-Generierung temporär reduziert + `colony_threatened` | nur im Vernachlässigungsfall — bei gesunder Kolonie 0% Grundrisiko | Krankenstation (infirmary) senkt Chance/Schwere |

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

> **Kosten-Kurve:** `levelup_costs` für Kenntnisse steigen mit jedem Level (progressive Kurve). Einzelne Level-Kosten sind in `config/knowledge.php` definiert. Das Design-Ziel: Kenntnisforschung soll mehrere Sole beanspruchen, auch früh im Run, um das System zu geben Breite vs. Tiefe Lebensfähigkeit auszulösen.
> **Wichtig:** Diese Kostenkurve ist an `game.ap.base`/`advisor.ap_per_rank` gekoppelt — bei Änderung dort erneut gegen die AP/Sol-Rate prüfen, nicht isoliert betrachten.
> **Zusätzlicher Bugfix (2026-07-14):** Die Techtree-UI zeigte bis dahin für jede Kenntnis konstant 3 AP an (Fortschrittsleiste + Ausbau-Button) — ein stiller Off-Sync zwischen dem statischen `researches.ap_for_levelup`-DB-Feld (nur beim initialen Migrations-Seed gesetzt, nie synchronisiert) und den tatsächlichen, gestaffelten `levelup_costs` in dieser Config. Das Serverbackend (`ResearchService::resolveApForLevelup`) verlangte schon immer den korrekten, höheren Wert — nur die UI-Anzeige und die Klick-Grenze der Leiste hingen am veralteten Wert, sodass eine Investition über 3 AP hinaus optisch möglich schien, aber lautlos nichts bewirkte. `TechtreeController` liest den Kenntnis-Kostenwert jetzt dynamisch aus derselben Quelle wie das Backend.

### Effekte wirken direkt aus dem Kenntnis-Level

Kenntnis-Effekte werden **automatisch** wirksam, sobald die Kenntnis das nötige Level erreicht hat — ohne dass sie einem Berater zugewiesen werden muss. Das frühere Modell (Primär-/Sekundäreffekt mit Berater-Zuweisungspflicht für den zweiten Effekt) ist entfallen; es gibt keine Zuweisungs-UI und keine Slot-Beschränkung mehr.

Bereits implementierte Effekte (`config/knowledge.php`):

- `construction`, `trade` senken additiv die AP-Kosten von Gebäude-Levelups (§13.3) — glockenförmig über die Level gestaffelt (`ap_cost_reduction_per_lv`). `cartography` senkt stattdessen eigenständig die Navigation-AP-Kosten von Tile-Erkundung und Hangar-Missions-Reisekosten (siehe §13.3).
- `trade` erhöht zusätzlich die Zahl gleichzeitig aktiver Cantina-Angebote (§12), siehe `bar_offer_boost_per_lv`.
- `agronomy`, `health`, `defense` wirken auf das Vertrauen (§14), siehe `trust_per_lv`.

Nicht jede Kenntnis trägt zwingend einen mechanischen Effekt dieser Art — alle Kenntnisse tragen zusätzlich einheitlich zum Supply-Cap-Wachstum bei (§7). Welche Kenntnis welchen Effekt trägt und in welcher Höhe, ist ausschließlich in `config/knowledge.php` gepflegt; Lookup-Tabelle: `docs/game-reference.md#kenntnisse-7-levelup-kosten-effekte`.

> **TODO Design:** Weitere Kenntnis-Effekte (insbesondere für Kenntnisse ohne eigenen mechanischen Effekt bislang) sind offen für spätere Balancing-Passes — nach Playtest, wenn klar ist, welche Lücken am meisten drücken.

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

Jede Kenntnis erhöht den Supply-Cap stufenweise mit jedem Level. Der Bonus ist nicht-linear — mittlere Level sind effizienter als Extremwerte (Glockenform). Details in §6 und `config/game.php → supply.knowledge_cap_per_level`.

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

> **⚠️ VERWORFEN (Owner, 2026-08-05) — vorheriger Archetyp-Vorschlag vom 2026-08-04.** Der vorherige Ansatz führte eine **neue** Gast-Rolle „Händler"/„Handelsgast" ein — einen zusätzlichen Archetyp unter mehreren gleichrangigen Cantina-Gästen (neben Schmuggler/Gelegenheitsverkäufer), der als einziger Credits-Handel anbietet. Owner-Begründung für die Ablehnung (wörtlich): „es war immer geplant, dass die Cantina der Ort für Handel ist (-> reisender Händler) und erst in zweiter Hinsicht für Events und Missionen (-> andere Charaktere)." Der Fehler im vorherigen Vorschlag: Er behandelte Handel als **eine von mehreren gleichwertigen Cantina-Aktivitäten** und erfand dafür eine neue, namenlose Figur — während die Cantina konzeptionell von Anfang an **primär** der Handelsort ist, verkörpert durch den bereits existierenden Reisenden Händler (**Corvan Ashe**, `docs/characters/merchant.md`, Kanal 3/`MerchantService`), und die übrigen Cantina-Charaktere (`config/characters.php`: Dax, Voss, Orin, Vesper, Zara, Tomas etc.) sekundäre, nicht-primär-handelsbezogene Rollen (Events, Missionen, Flavor) haben. Neuer Vorschlag unten löst dieselbe strukturelle Frage (Credits-Handel klar von reinem Tauschhandel trennen) mit der **bestehenden** Figur statt einer erfundenen.

#### Corvan wird die zentrale Handelsfigur der Cantina (Freigegeben 2026-08-05 — Direction 1)

**Ausgangsbefund, der die Neu-Zuordnung stützt:** Corvans eigenes Charakterblatt (`docs/characters/merchant.md`) trägt bereits `Game Role: bar_trade` und einen eigenen Abschnitt „Cantina Placement" (`Frequency: occasional`, `Panels: 0`) — er war also schon vor diesem Vorschlag konzeptionell der Cantina zugeordnet, nur technisch nie mit `BarService` verbunden (er ist nicht in `config/characters.php` gelistet, jenem Roster, aus dem die generischen Hotspot-Charaktere stammen). Zusätzlicher technischer Befund: `components/cantina-dialog.blade.php` ist bereits „shared by the offer dialog and the merchant dialog" — Bar-Angebote und Corvans Handelsdialog laufen UI-seitig schon durch dieselbe Komponente. Die Verschmelzung ist technisch näher an der Realität als zwei getrennte Systeme.

**Erste Fassung dieses Vorschlags hatte Owner-Richtung 1 („Corvan wird zum häufigeren, zentralen Cantina-Handelscharakter … `BarService`-Gästerotation entfällt für Credits-Handel ganz") vorschnell verworfen — Korrektur:** Begründung war Corvans Bio-Text („he isn't a fixture of the cantina… only docks when his route brings him through"). Das übersieht, dass `docs/characters/merchant.md` explizit `status: draft` trägt — kein festgeschriebener Kanon, sondern ein Content-Entwurf, den `content-writer` an eine mechanische Entscheidung anpassen kann (z. B.: er kommt inzwischen häufiger vorbei, weil die wachsende Kolonie zu seinen verlässlicheren Handelspartnern zählt — passt zu seinem „shrewd, ... unsentimental"-Charakter, ohne die Figur umzuschreiben). Ein Draft-Bio-Satz ist kein hinreichender Grund, die von Owner explizit vorgeschlagene Hauptrichtung zu verwerfen.

**Empfehlung: Zwei-Ebenen-Modell innerhalb einer erhöhten Corvan-Frequenz, statt zwei getrennter Systeme.**

1. **Corvans Erscheinungsintervall wird angehoben** — Vorschlag ~5–8 Sole (statt bisher 10–15) — und bei jedem Erscheinen laufen **zwei unabhängige Rollen**:
   - **Alltagsgeschäft (häufig bei jedem Erscheinen):** Standard-Commodity-Handel — Regolith/Organika/Werkstoffe gegen Credits, beide Richtungen (Kauf bestehend, Verkauf neu — der Organika-Verkauf-Vorschlag aus §4b). Strukturell das, was heute `BarService`s Credits↔Ressource-Angebotstyp leistet, jetzt aber an Corvans Erscheinen gebunden statt an anonyme Gäste. **Sizing — Zielzahl zurückgezogen (2026-08-06), Mechanismus bleibt.** Die ursprüngliche Rechnung kalibrierte gegen ~247 Cr/Sol (§4b) — diese Zahl ist zurückgezogen, weil sie aus der Regolith-Lücke statt aus einem Credits-Bedarf hergeleitet war (§13.7 „Neuherleitung", Punkt 5; §4b „Dimensionierung — korrigiert"). Ohne validierte Cr/Sol-Zielgröße lässt sich die genaue Losanzahl pro Besuch nicht mehr belastbar herleiten — das wartet auf die in Anhang A neu aufgenommene Credits-Bilanz-über-den-Run. **Der strukturelle Punkt bleibt unabhängig davon gültig:** Mit nur einem Verkaufslos à ~20 Einheiten pro Besuch (~0,15 Besuche/Sol × 20 Einheiten × 35 Cr) kommen ~105 Cr/Sol zusammen — der richtige Hebel, falls mehr gebraucht wird, ist **mehrere Verkaufslose pro Besuch**, nicht ein kürzeres Intervall (überstrapaziert Corvans „occasional"-Bio schon im Entwurfsstadium) oder ein einzelnes Riesenlos (sprengt die Reserve-Untergrenze). Exakte Losanzahl: Playtest-Kandidat, sobald die Credits-Bilanz eine Zielgröße liefert.
   - **Kuratiertes Sonderinventar (seltener, Sub-Chance bei einem Erscheinen):** AP-Pakete, Schiffe, Information, Einmal-Items, Exotics — die bisherige `MerchantService`-Kategorie-Tabelle, unverändert in Inhalt und Seltenheit innerhalb seiner Besuche.
2. **`BarService`s anonyme Gästerotation bleibt nur für Tauschhandel (Ressource↔Ressource)** — Dax (`smuggler`), Voss (`scrap_dealer`) und ähnliche passen inhaltlich bereits. **Für Credits-Handel entfällt sie ganz**, wörtlich wie vom Owner vorgeschlagen: kein Kauf, kein Verkauf gegen Credits ohne Corvan.
3. Technisch am ehesten als **eine gemeinsame Erscheinungs-/Angebots-Pipeline** umsetzbar (nicht zwei getrennte Spawn-Checks `MerchantService` + `BarService`-Credits-Zweig) — Detailarchitektur ist `game-developer`-Entscheidung, hier nur die Design-Anforderung: ein Corvan-Besuch, zwei Angebotsebenen.

**Fallback, falls der Owner die Bio-Rarität ausdrücklich erhalten will (Owner-Richtung 2, nicht mehr die primäre Empfehlung):** Kanal 3 bleibt exakt wie heute (~10–15 Sole, nur Sonderinventar), Kanal 1 bekommt eine reine Namens-/Flavor-Zuordnung „Corvans Netzwerk" für den bestehenden Credits-Angebotstyp — Geschäfte „in seinem Auftrag", ohne dass er selbst häufiger auftritt. Erfüllt den Kern der Owner-Anforderung (Credits-Handel hat eine erkennbare Identität, keine erfundene Figur), bleibt aber näher an der heutigen Systemtrennung. Nur wählen, wenn Direction 1 aus Umsetzungsgründen zurückgestellt wird.

**Bezug zu Orin — bewusst nicht dieselbe Rolle, in beiden Varianten.** Der Harvester-Zweitinstanz-Vorschlag (§4c, freigegeben 2026-08-05) bindet Orin (`corporate_rep`) bereits an eine eigene, unabhängige Handelsrolle (Verkauf eines Harvester-Moduls, 400–800 Cr, eigener Spawn-Check außerhalb von `BarService`/`MerchantService`). Orin bleibt ausschließlich an den Harvester-Deal gebunden, keine Doppelbelegung mit zwei unterschiedlichen Handelsbedeutungen.

**Cantina-Verhandlung (Risiko-Handel):** bleibt für beide Angebotstypen (Commodity/Corvan und Tausch/sekundäre Charaktere) nutzbar — keine zwingende Notwendigkeit, sie einzuschränken.

**Konsul-Rang-Skalierung, wie zuvor:** Ohne Konsul erscheint Corvans Alltagsgeschäft seltener, aber nicht nie; mit Konsul häufiger und zu besseren Konditionen (`trader_discount`) — thematisch: der Konsul pflegt die Kontakte, die Corvan öfter vorbeikommen lassen. Die untenstehende Dimensionierung (§4b) hatte dieses Prinzip zuvor gegen eine aus der Regolith-Lücke umgerechnete Cr/Sol-Zielgröße durchgerechnet — diese Zielgröße ist am 2026-08-06 zurückgezogen (§13.7 „Neuherleitung", Punkt 5; §4b „Dimensionierung — korrigiert"), weil Pfad C laut eigener Entscheidung keinen Regolith-Hebel trägt und die Regolith-Lücke inzwischen unabhängig von Pfad C durch A + B gedeckt ist. Der Mechanismus (mehrere Verkaufslose pro Corvan-Besuch statt kürzeres Intervall) bleibt gültig; die genaue Losanzahl wartet auf eine eigene Credits-Bilanz.

**Owner-Freigabe 2026-08-05, alle drei Punkte bestätigt:**
1. Direction 1: Corvan-Frequenz angehoben (~5–8 Sole), zwei Angebotsebenen bei jedem Erscheinen, `BarService`-Gästerotation verliert Credits-Handel vollständig an ihn.
2. Sekundäre Cantina-Charaktere (Dax, Voss, ggf. weitere) stehen ausschließlich für Tauschhandel, nie für Credits.
3. Corvans Bio (`status: draft`) wird an die höhere Frequenz angepasst (`content-writer`).

Erst nach Freigabe: TDD-Umsetzung durch `game-developer`/`backend-coder` (Zusammenführung der Spawn-/Angebotslogik, Architekturentscheidung dort), `content-writer` für Corvans Charakterblatt-Update.

Der Spieler entscheidet pro Angebot: annehmen oder ablehnen. **Annehmen kostet AP** aus dem gemeinsamen Pool (§13.1) — der Handel konkurriert damit direkt mit Bau und Kenntnissen um dieselbe Kapazität. Exakte Kosten: siehe `config/game.php`.

**Handelsvertrag (neue, garantierte Einnahmequelle, 2026-07-19):** Beide obigen Angebotstypen erzeugen kein Credits-Einkommen für den Spieler — sie kosten Credits (Kauf) oder sind ressourcenneutral (Tausch). Das war die Kernursache dafür, dass die Kolonie strukturell kein Credits-Einkommen aus Handel ziehen konnte (Playtest-Bot-Befund, PR #218; siehe §18 `task_credit_reserve`). Fix: kein Bar-Angebot im bisherigen Sinn (kein Karten-Slot, keine Annahme, kein AP-Kosten), sondern eine **passive Cr/Sol-Einnahme** — strukturell identisch zur Relaisvergütung (§3): sie fließt automatisch pro Tick, solange ein Konsul der Kolonie zugewiesen ist **und** die Cantina mind. Lv1 gebaut ist. Thematisch vermittelt der Konsul laufende Handelsverträge im Hintergrund; die Kolonie liefert dafür keine Ressourcen. Config-Key-Vorschlag: `game.credits.consul_contract_income_per_rank`, verarbeitet in `GameTick` im selben Schritt wie `nexus_subsidy`/`relay_bonus_per_uplink_level`. Werte nach Konsul-Rang:

| Konsul-Rang | Handelsvertrag-Einkommen |
|-------------|--------------------------|
| Kein Konsul | — |
| 1 — Junior | Niedrig |
| 2 — Senior | Mittel |
| 3 — Experte | Hoch |

Exakte Werte pro Rang: siehe `config/game.php → credits.consul_contract_income_per_rank`.

Ohne zugewiesenen Konsul entfällt diese Einnahme vollständig — **beabsichtigt**: die Konsul-Entscheidung erhält einen echten Gegenwert. Wichtig: Der Handelsvertrag wie auch Corvans Alltagsgeschäft sind beide an die gebaute Cantina (Bar Lv1+) gekoppelt. Für Läufe, die Sciencelab oder Hangar zuerst bauen (gleichwertige Pfadwahl, §13), entfällt damit der komplette Cantina-Einnahmepfad — das ist kein Edge-Case, sondern der Normalfall für jeden Lauf ohne frühe Cantina. Credits-Planung muss das berücksichtigen (Missionen, Nexus-Reserve, Berater-AP-Beitrag fallen schwächer aus).

**Bar-Level-Progression:**

Höhere Bar-Level erhöhen die Angebots-Gültigkeit (Dauer) und die maximale Anzahl gleichzeitig aktiver Angebote. Details: siehe `config/buildings.php`.

Zusätzlich zum Bar-Level selbst erhöht die Kenntnis **Handel** (`trade`) ab einem bestimmten Level die Zahl gleichzeitig aktiver Angebots-Slots weiter — der Effekt wirkt direkt aus dem Kenntnis-Level, ohne dass ein Berater zugewiesen sein muss (§10). Details: `config/knowledge.php → trade.bar_offer_boost_per_lv`, `docs/game-reference.md#kenntnisse-7-levelup-kosten-effekte`.

**Konsul (advisor_trader) — Rang-Effekte:**

Der Konsul trägt zum gemeinsamen AP-Pool bei (Beitrag steigt mit Rang), verbessert die Gäste-Häufigkeit und Preiskonditionen, und erhöht die Wahrscheinlichkeit von Werkstoffen in Credits↔Ressource-Angeboten bei höheren Rängen. Exakte Werte pro Rang: siehe `config/advisors.php`.

**Werkstoffe-Bias bei höheren Rängen:** Der Experten-Konsul hat Marktbeziehungen — bei Credits→Ressource-Angeboten erscheinen seltene Ressourcen häufiger. Das gibt dem höheren Rang einen konkreten wirtschaftlichen Vorteil in der knappsten Ressource des Spiels (§3 Werkstoffe nicht lokal produzierbar).

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

Die Erfolgschance und der Bonus-Betrag steigen mit Konsul-Rang. Der Zusatz-Bonus wirkt auf dieselbe Achse wie `trader_discount` bei der Angebots-Generierung, aber additiv obendrauf auf das **konkrete, bereits generierte** Angebot (nicht auf einen neuen Wurf). Kein zweites Formel-System — nur eine zweite Anwendung derselben Formel.

Exakte Erfolgschancen und Bonussätze: siehe `config/game.php → bar` (`negotiate_success_chance` / `negotiate_bonus`).

> ⚠️ BALANCE CONCERN: Kalibration gegen das Risiko/Reward-Gleichgewicht. Zu hohe Erfolgschance oder zu großer Bonus macht Verhandeln zur dominanten Strategie ohne echtes Risiko. Nach erstem Playtest kalibrieren (siehe `config/game.php → bar` für Schwellenwertbeispiele).

---

### Kanal 2: Nexus-Handelsschiffe (Fallback, teuer, garantiert)

Nexus schickt auf Anfrage offizielle Handelsschiffe. Immer verfügbar — auch ohne Händler-Berater, auch ohne Bar. Das Sicherheitsnetz gegen Progression-Locks.

Lieferzeit und Preisaufschlag hängen vom Konsul-Rang ab — ohne Berater sind beide nachteilig. Höhere Ränge senken beide Parameter (schnellere Lieferung, bessere Konditionen). Exakte Werte: siehe `config/game.php`.

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

**Erscheinungsfrequenz:** Erscheint gelegentlich nach einer Startup-Phase (Kolonie soll sich erst etablieren). Regelmäßige Besuche danach, aber unregelmäßig genug um Roguelike-Druck zu erzeugen (kein garantiertes Angebot). Details: `config/game.php → merchant`.

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

Mehrere unabhängige Quellen tragen zu gestaffelten Kostenreduktionen bei: Berater-Ränge, Kenntnis-Level nach Domäne, und Koloniereife (CC-Level). Exakte Boni und Maxima: siehe `config/game.php` → `project_cost_bonus`.

Domänen-Kenntnis-Zuordnung (Bau-Projekt-Rabatt-Pool, `ProjectBonusService::buildingApDiscountPercent()`): Bau ← `construction`, Wirtschaft ← `trade`. `cartography` ist seit 2026-08-27 kein Mitglied dieses Pools mehr — die Kenntnis senkt stattdessen eigenständig die Navigation-AP-Kosten von Tile-Erkundung (`ColonyTileService::exploreTile()`) und Hangar-Missions-Reisekosten (`HangarService::dispatchShip()`), beide über `config('knowledge.cartography.nav_ap_reduction_per_lv')`, ein separater Pool. Für die Domäne **Wissen** gibt es keine passende Kenntnis — ein früher Entwurf sah hier stattdessen Analytik-Labor-Level-Boni auf denselben Gebäude-Rabatt-Pool vor; das wurde durch eine eigenständige Mechanik ersetzt (siehe unten, „Analytik-Labor Lv4/5"): Analytik-Labor-Level senken stattdessen die AP-Kosten von **Kenntnis-Levelups** selbst, ein separater Pool, kein vierter Beitrag zu diesem hier beschriebenen Gebäude-Rabatt.

**Analytik-Labor Lv4/5 (Design-Spec 2026-08-23, umgesetzt 2026-08-27):** Gibt dem Laborausbau über die reinen Kenntnis-Gates (Lv1-3) hinaus einen eigenen mechanischen Effekt — senkt die AP-Kosten für Kenntnis-Levelups, additiv, unabhängig vom Gebäude-Rabatt-Pool oben. Rührt an nichts, was pro Run gezogen wird (§10 Roguelike-Variabilität bleibt unangetastet) — reine Effizienzsteigerung auf bereits freigeschaltete Kenntnisse. Exakte Werte: `config/buildings.php` → `sciencelab.knowledge_ap_cost_reduction_per_lv`.

Ein **Mindest-Kostenanteil** (`project_min_cost_factor`) verhindert, dass Projekte auf null fallen — das ist eine Leitplanke für spätere Bonusquellen (Events, Missionsbelohnungen, Run-Modifier), keine aktive Regel zum Start. Wichtig, das so zu lesen, damit später niemand gegen einen Deckel kalibriert, der gar nicht wirkt.

**Boni gelten nur für Projekte, nicht für Handlungen.** Dadurch wächst der Handlungsanteil am Pool über den Run relativ an — das späte Spiel verschiebt sich von selbst Richtung Ausführung. Das ist beabsichtigt und trägt den Kipppunkt aus 13.2 mit.

> **Nachtrag 2026-08-15 (Owner-Entscheidung, PlaytestBot-Befund):** Umgesetzt für
> `construction`/`trade` — `cartography` wurde am 2026-08-27 aus diesem Pool gelöst, siehe oben —
> glockenförmig statt linear (Σ15% je Kenntnis bei Lv5, Peak Lv2–4), wirkt additiv auf **alle**
> Gebäude-Levelups (inkl. CommandCenter), nicht nach Projekttyp getrennt, da im aktuellen Spiel nur
> Bau-Projekte existieren (Navigation/Wirtschaft haben keine passende Projekt-
> Kategorie). Berater-Rang- und Koloniereife-Bonusquellen aus der Tabelle oben sind
> weiterhin nicht implementiert. Siehe `app/Services/ProjectBonusService.php`,
> `docs/superpowers/specs/2026-08-15-knowledge-effects-and-encounters-design.md`.

---

### 13.4 Kommandozentrale: Dashboard und Prognosen

Das Ratenmodell ist nur spielbar, wenn der Spieler seine Rate und ihre Verwendung jederzeit sieht. Der Kommandozentrale-Screen wird deshalb zum **Dashboard** ausgebaut. Es ist keine Komfortfunktion, sondern tragende Voraussetzung: Mehr-Sol-Projekte machen nur Spaß, wenn sie planbar sind.

Mindestumfang:

| Anzeige | Zweck |
|---|---|
| AP-Zufluss pro Sol und wohin er aktuell fließt | Grundlage jeder Allokationsentscheidung |
| Restzeit je Baustelle („noch 3 Sole bei aktueller Rate") | Planbarkeit von Projekten, Timing von Fertigstellungen |
| Instandhaltungsanteil („Reparatur bindet [Anteil] deiner Kapazität") | Macht die wachsende Last aus 13.5 sichtbar, bevor sie drückt |
| **Restertrag bis Run-Ende** je Projekt („Agrardom Lv5: noch 3 Sole, dann 8 Sole × 7 Organika") | Trägt den Late-Game-Kipppunkt (13.2) — siehe unten |
| Regolith-Bilanz (Produktion − Reparatur − Levelups) | Die eigentliche Wachstumsgrenze (13.5) |
| **Over-Cap-Warnung**, wenn die Supply-Last den Cap übersteigt | Ersetzt die stille Verdopplung der Instandhaltung durch einen sichtbaren Zustand (§7) |
| Konzessions-Prognose („bei aktuellem Kurs in 12 Solen unterschritten") | Macht den Fail-State aus §18.2 vorhersehbar statt überraschend |
| Fortschritt der Run-Aufgaben | Verbindet Tagesentscheidung mit Run-Ziel (§15) |

**Zum Restertrag — er trägt den Kipppunkt, nicht die Kosten.** Der Late-Game-Kipppunkt aus 13.2 entsteht nicht dadurch, dass Projekte spät teurer werden, sondern dadurch, dass sich ihr Ertrag nicht mehr amortisiert: Ein hochstufiges Produktionsgebäude, das spät fertig wird, liefert nur noch kleine Restertrag-Mengen — das ist der Grund, es sein zu lassen. Wenn das Dashboard neben der verbleibenden Bauzeit auch den geschätzten Restertrag zeigt, entsteht der Phasenwechsel **ohne jede Zahlenänderung**. Ohne die Anzeige müsste man ihn über Kosten erzwingen, was das Fortschrittsgefühl aus 13.3 beschädigen würde.

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

**Was stattdessen gilt: eine wachsende, sichtbare Last.** Der Instandhaltungsanteil des AP-Pools wächst über den Run — moderat früh, spürbar im Endausbau. Das ist spürbarer Gegenwind, kein Stillstand — und es passt besser zum Designprinzip „kein Leerlauf, aktives Spielen wird belohnt" (§1.1) als ein echtes Gleichgewicht, das den Spieler einfriert. Genaue Werte siehe `config/game.php` (Instandhaltungs-Progression) und Herleitung in §13.7."

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

> **Nachtrag 2026-08-15:** `agronomy`-Organika-Parität zu `geology` umgesetzt
> (`config('game.agronomy_agrardom_bonus_per_level')`, Σ7 Or/Sol bei Lv5, glockenförmig
> — bewusst NICHT front-loaded wie `geology`, da neu ohne Kalibrierungshistorie). Der
> Cantina-Pfad-C-Fix (Losgrößen/Tauschrichtung) ist weiterhin offen; `trade`s neuer
> Kenntniseffekt (zusätzliche Angebotsslots, `BarService::tradeConcurrentSlotBonus()`)
> läuft parallel dazu, ohne ihn zu ersetzen.

> **⚠️ Offen — Zahlen und Umsetzung.** Die +1,5 Rg/Sol je `geology`-Level sind ein erster Ansatz, kalibriert auf Parität mit dem Frachter-Kanal. Zu prüfen ist, ob der Analytik-Pfad damit insgesamt zu stark wird — er trägt zusätzlich den Supply-Cap-Bonus **und** den Domänen-Effizienzbonus (13.3), leistet also dreifach. Falls ja: auf +1,2/Level senken statt einen der anderen Effekte zu beschneiden.

> **⚠️ Der Sockel ist zu niedrig — die Hebel sind nicht das Problem (Befund 2026-08-02).** Eine Gegenrechnung von der Bedarfsseite ergibt für die Zielkolonie aus §13.6 über 80 Sole rund **1.454 Rg Bedarf** (530 Errichtungen + 284 Level-Ups + 640 Reparatur) gegen **840 verfügbar** (200 Start + 8/Sol). Lücke ≈ 614 Rg. Schlimmer noch: Der Bedarf ist ungleich verteilt, die **Spitze liegt bei 15–18 Rg/Sol in den Solen 21–60** — während der zweite Pfad erst bei CC Lv3 (~Sol 30) und der dritte bei CC Lv4 (~Sol 50) dazukommt, also *nach* der Spitze. Mit einem Hebel ist die Zielkolonie bei Sockel 8 nicht baubar.
>
> Damit ist die Guard-Rail aus der Owner-Entscheidung vom 2026-07-20 verletzt: *„Grundproduktion muss für sich allein knapp, aber machbar sein, bevor irgendein Pfad-Bonus draufkommt."* Bei 8 Rg/Sol ist sie nicht machbar — die Kolonie ist ab sechs Gebäudetypen allein durch Reparatur negativ, bevor ein einziges Level-Up bezahlt ist. Die Zeile „−0,7" in der Tabelle oben ist kein Spannungsbogen, sondern ein Fehler.
>
> **Der Sockel wurde deshalb neu hergeleitet, nicht nachjustiert** — Ergebnis in **§13.7**. Kurzfassung: Der maßgebliche Grund gegen 8 ist nicht die Deckungslücke, sondern die **Auflösung** (G7) — bei 8 Rg/Sol gibt es nur zwei unterscheidbare Baupreisklassen. Freigegeben ist ein Harvester-Frischwert von 18 auf `regolith_normal` (Run-Mittel ~21,8 mit zwei Instanzen, §4c) bei gleichzeitig halbierten Reparaturkosten (1 statt 2 Rg/SP) und neu abgeleiteten `decay_rate`-Werten. **Die Tabellen in diesem Abschnitt rechnen noch mit den alten Werten** und sind nur als Herleitung des Befunds zu lesen, nicht als geltende Zahlen.

> **Nachrüstoption, falls das späte Spiel im Playtest schlaff wirkt:** Reparaturkosten mit dem Level skalieren — `AP je SP = 1 + floor((level−1)/3)`. Die Instandhaltung skaliert dann mit der **Tiefe** und koppelt sich elegant an den Supply-Cap (§6); bei der Zielkolonie ergäbe das ~11 statt 7,3 AP/Sol, also rund 50 % des Pools. Das ist der saubere Hebel. Die Alternative `decay_rate × level` ist thematisch schwächer (warum verfällt ein größeres Gebäude schneller?) und verdoppelt zusätzlich den Regolith-Abfluss.

---

### 13.6 Zahlenvorschlag, erste Fassung (überholt — siehe 13.7)

> **Überholt durch §13.7 (2026-08-02).** Diese Fassung ist gegen die bestehenden Config-Werte gerechnet und behandelt sie als Randbedingung — genau der Fehler, den „Zum Umgang mit den Zahlen" beschreibt. Sie bleibt stehen, weil der Vergleich mit §13.7 zeigt, was der Methodenwechsel bewirkt: Die AP-Struktur (Grundwert, Berater-Beitrag, `f(L)`-Kurve, Bonus-Kurve) hat sich bestätigt, die Regolith-Zahlen und die Hebel-Zielgröße nicht — letztere lag um Faktor 2 daneben.
>
> **Weiterhin gültig aus diesem Abschnitt:** Ziel-Endzustand, Berater-Beitrag 2/3/4, `f(L)`-Kostenkurve mit `f(1) = 0.5`, Bonus-Kurve, Handlungs-AP. **Ersetzt:** alles Regolith-Bezogene und die Budgetprobe. **Geändert:** der AP-Grundwert — siehe Freigabe unten.

> ## ✅ Freigegeben (Owner, 2026-08-03) — mit einer Anpassung
>
> Die AP-Struktur ist beschlossen mit einer Erhöhung des Basis-Wertes gegen gestiegene Instandhaltungslast (Wechsel zu breiter statt tiefer Kolonien — viele kleine Instanzen statt wenige große).
> 
> **Grund:** §4c („im Zweifel Instanz") erzeugt mehr sich verfallende Gebäude pro Kolonie. Dies erforderte eine Neuherleitung, um die Drei-Pfade-Parität zu bewahren — alle Pfade müssen ohne strukturelle Enge machbar bleiben, auch wenn einer (B) bewusst teurere laufende Kosten trägt. Der genaue Basis-Wert ist kalibriert gegen diese Anforderung — siehe `config/game.php → ap.base`.

**Die tragenden Elemente dieses Systems:** Basis-AP-Wert, Gebäude-Basis-Kosten-Klassen (gestaffelt nach Gebäude-Rolle), eine progressive Kostenkurve pro Level, Berater-AP-Beitrag pro Rang (progressiv), und Kostenboni durch Kenntnisse/Domänen.

**Wenn sich das System im Playtest als unausgewogen erweist:** Die Stellschrauben in Reihenfolge — Basis-Wert, dann Gebäude-Klassen, dann Kurvensteilheit, dann Berater-Beiträge. Alle sind einzeln in `config/` kalibrierbar, ohne den Regeltext zu ändern. See `docs/game-reference.md` for Playtest-Interpretation-Guide.

#### Ziel-Endzustand (guter Run, Sol ~75–80)

Ein typischer erfolgreicher Run bei dieser Pacing erreicht einen Endzustand mit: Mehrheit der Gebäude-Typen (aber nicht alle), moderate Gebäude-Level, volle Berater-Slots (hauptsächlich Rang 2), einige Kenntnisse auf mittleres Level, und ungefähr 2/3 der verfügbaren Bauplätze belegt.

Ungenutzte Ressourcen und Spielzüge sind Absicht: Der Spieler soll sehen, welche Optionen offenblieben — es ist kein Erreichen einer perfekten Optimalität, sondern ein befriedigender Zustand mit sichtbarem „hätte-ich-auch-noch-tun-können" Potenzial.

#### AP-Grundwert und Berater-Beitrag

Der Gemeinsame AP-Pool hat einen Basiswert (siehe `config/game.php → ap.base`) und wächst mit Berater-Rängen. Der Berater-Beitrag steigt mit dem Rang, erlaubt aber kein exponentielles Wachstum — die Progression folgt einer bewusst flachen Kurve.

Die AP-Rate wird durch drei Faktoren gestaffelt: **Berater-Anzahl und -Rang** (progressiv), **Instandhaltungslast** (wächst über den Run), **Projektkosten** (initial niedrig, später höher).

Das **Pool-Wachstum über einen 100-Sol-Run** ist moderat (Faktor ~2–3 vom Anfang zum Ende), kombiniert mit Kostenreduktionen durch Boni und Domänen-Effizienz. Der Vertrauens-Multiplikator (`trust.ap_multiplier`, siehe §14) kommt obendrauf.

Exakte Werte: siehe `config/game.php` (`ap.base`, `advisor.ap_per_rank`).

#### Projektkosten je Gebäudelevel

Projektkosten folgen einer Formel:

```
ap_cost(building, L) = round(base_ap[building] × f(L))
f(1) = 0.5
f(L≥2) = 1 + 0.4 × (L−2)
```

Das Errichten (Level 1) kostet bewusst weniger als Level-Ups — das erzeugt einen Anreiz für breitere Kolonien früh (weniger AP pro neues Gebäude) und tiefere Spezialisierung später (mehr AP pro Ausbau).

Gebäude werden nach Rolle in Kategorien gruppiert (Produktion, Klein, Mittel, Groß, und Kommandozentrale als Sonderfall), jede mit eigenem Basis-AP. Produktionsgebäude sind bewusst am billigsten — ihre Glockenkurve (`game.production_curve`) setzt bereits einen Ceiling; doppelte AP-Deckel wären redundant.

**Early-Game-Tempo:** Das Design bevorzugt breite Kolonien früh (billige erste Level) über tiefe Spezialisierung, kombiniert mit den Supply-Cap-Grenzen aus §6. Zusammen entsteht das Breite/Tiefe-Dreieck ohne optimalen Pfad. Alternativen (befristete AP-Boni, Vorbau in der Startkolonie) wurden verworfen — erstere wirken dort, wo ohnehin wenig Instandhaltung nötig ist (wenig Hebel), letztere würde Lernmomente in §16 zerstören.

**Kenntnisse** haben ihre eigenen Kosten (siehe `config/knowledge.php`), unabhängig von Gebäuden — sie skalieren parallel mit dem Pool-Wachstum.

Exakte `base_ap`-Werte und Kostenkurven: siehe `config/buildings.php`.

#### Handlungs-AP nachziehen

Sofort-Handlungen (Handel, Erkundung) werden gegen den gemeinsamen AP-Pool kalibriert. Sie kosten deutlich weniger als Projekte, sind aber nicht kostenlos — sie konkurrieren um denselben Pool und erfordern echte Abwägungen.

Lange Missionen (Fernexpeditionen) kosten ein nennenswerter Anteil des Pools, um eine echte Entscheidung zu erzeugen. Ring-Erkundungen skalieren mit Entfernung, um zu verhindern, dass die Karte zu schnell aufgedeckt wird.

Exakte Kosten: siehe `config/game.php` (Handelskosten, Erkundungs-Staffelung) und `config/missions.php` (Navigation-AP pro Sol).

> **Die Regel „Gelegenheiten sind durch Verfügbarkeit begrenzt" (13.2) ist bereits erfüllt — ohne neue Mechanik.** Missionen sind durch Schiffszahl und Rundlaufzeit begrenzt (2 Schiffe × Ø 5 Sole Umlauf × 5 AP ≈ 2 AP/Sol), Bar-Angebote durch `guest_count` und `level_max_concurrent` (≈ 4 AP/Sol). Zusammen ~6 von ~22 AP/Sol = 27 %. Das ist genau der beabsichtigte Deckel — es braucht keine zusätzliche Regel.

#### Balancing-Targets und -Unsicherheiten

Der AP-Haushalt ist gegen drei informale Ziele kalibriert: (1) Die Zielkolonie soll ohne Glück erreichbar sein; (2) Alle drei Pfade sollen tragfähig unterschiedliche Kostenverhältnisse haben; (3) Die Sol-1–4-Rampe soll sichtbare Fertigstellungen pro Sol erzeugen, nicht nur ein Füllen von Fortschrittsbalken. 

Diese Ziele wurden gegen die frühere, kleinere Spielwiese (4c vor §13.5 Entscheidung) validiert. Mit der aktuellen Instanz-Breite und neuen Harvester-Regeln muss die Balance nach erstem Playtest kalibriert werden — die Stellschrauben sind alle einzeln in `config/` einstellbar.

**Unsicherheitsquellen für den Playtest:**
- Ob die Instandhaltungslast sich so anfühlt, wie beabsichtigt (druck ohne deadlock)
- Ob alle drei Pfade tatsächlich äquivalent tragfähig sind
- Die Credits-Ökonomie (abhängig von Schiffs-Pool-Größe und Handelsfrequenz) ist noch nicht vollständig kalibriert

Alle diese Fragen sind im Playtest-Report zu dokumentieren und führen zu Config-Anpassungen, nicht zu GDD-Änderungen.

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

Erfolgs-, Teilerfolgs- und Misserfolgsraten gestaffelt nach Berater-Rang. Junior-Ränge haben merklich höheres Misserfolgsrisiko als erfahrene; Experten sind zuverlässig. Exakte Werte siehe `config/advisors.php`.

**Kein permanenter Verlust:** Bei Misserfolg kehrt der Berater unbeschadet zurück. Der einzige Schaden ist der Opportunitätsverlust — die AP haben während der Missionsdauer gefehlt. Ein Rang-Abzug oder permanenter Malus findet nicht statt.

> **⚠️ Balance:** Junior-Berater haben höheres Misserfolgsrisiko — das motiviert, wichtige Missionen mit erfahrenen Beratern zu starten, oder bewusst das Risiko einzugehen. Eine Junior-Mission bleibt attraktiv wenn die Opportunitätskosten niedrig sind (kurze Missionsdauer, AP-Pool nicht ausgelastet).

---

#### Constraints und Interaktionen

| Regel | Beschreibung |
|-------|-------------|
| **Burnout-Sperre** | Ein Berater mit gesetztem `unavailable_until_tick` (Burnout) kann keine Mission starten. |
| **Missions-Immunität** | Ein Berater auf Außenmission kann während dieser Zeit keinen Burnout erleiden. Der Burnout-Timer pausiert für die Missionsdauer. |
| **Concurrent-Limit** | Maximal 2 Berater gleichzeitig auf Mission (kolonieweites Limit). Ein dritter kann erst starten, wenn einer zurückgekehrt ist. |
| **Missionsdauer-Transparenz** | Das Missions-UI zeigt die verbleibenden Sole bis Rückkehr neben der aktuellen Sol-Nummer an. |
| **AP-Nutzungsrate** | Run-Aufgabe "Effizienzsprung" (hohe AP-Auslastung über mehrere Sole, §15) und Außenmissionen schließen sich nicht aus — der Spieler muss aktiv abwägen ob er einen AP-Produzenten für die Missionsdauer opfert. Schwellenwert siehe `config/game.php` → `objectives`. |
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

Jeder Berater hat einen von drei Rängen. Der Rang bestimmt, wie stark der Berater den gemeinsamen AP-Pool erhöht (§13.1) und wie hoch sein laufender Upkeep in Credits ist — beide Werte wachsen mit dem Rang, additiv auf den gemeinsamen Pool angerechnet, unabhängig von der Domäne des Beraters.

Exakte Werte (AP-Bonus je Rang, Upkeep, Rang-Aufstiegs-Schwellen in aktiven Ticks, Beförderungskosten): siehe `config/game.php → advisor` und `docs/game-reference.md#5-berater-advisors-hire-kosten--ap-beiträge`.

> **Balance-Historie:** Die Upkeep-Kurve wurde mehrfach abgeflacht (2026-07-19, 2026-08-14, 2026-08-18) — ursprünglich steile Rang-Sprünge ließen die Credits-Ökonomie strukturell kollabieren, sobald mehrere Berater gleichzeitig aufstiegen (Playtest-Bot-Befund, PR #218; volle Herleitung inkl. Break-even-Rechnung siehe §18.4 Balancing-Richtlinien, `task_credit_reserve`). Begleitend wurden die Rang-Aufstiegs-Schwellen gestreckt — mehr Zeit, um Uplink-Station und Cantina vor dem teureren Upkeep hochzuziehen.

**Einstellungskosten (Rang 1) — typ-spezifisch:** Baumeister ist der günstigste Einstieg (Kernanforderung Tag 1); Analytiker, Raumfahrer und Konsul sind höher gestaffelt, gekoppelt an ihre spätere Verfügbarkeit (Analytiker erst ab CC Lv2, Raumfahrer voller Nutzen erst mit Hangar, Konsul mittlere Priorität). Exakte Beträge: `config/advisors.php`, `docs/game-reference.md`.

**Beförderung** kostet beim Erreichen von Rang 2 bzw. Rang 3 zusätzlich zum laufenden Upkeep einen einmaligen Credits-Betrag (`config/game.php → advisor.promotion_costs`). Kann der Spieler die Beförderung nicht bezahlen, wird sie auf den nächsten Sol verschoben, bis genug Credits verfügbar sind.

- **Upkeep** wird jeden Sol von den Colony-Credits abgezogen, solange der Berater `colony_id` gesetzt hat (Berater ist aktiv zugewiesen).
- **Rang-Aufstieg:** automatisch nach ausreichend kumulierten `active_ticks` (`config/game.php → advisor.rank_thresholds`).
- Alle Werte stehen in `config/game.php → advisor` (Einstellungskosten, AP-Bonus, Upkeep, Rang-Thresholds, Beförderungskosten).

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

Der Grundwert des gemeinsamen Pools ist seit 2026-08-03 kalibriert und freigegeben (`config/game.php → ap.base`) — bewusst deutlich kleiner als die Summe der früheren fünf Einzel-Pools, damit die effektive Handlungsfähigkeit gegenüber dem Vor-Konsolidierungs-Modell nicht sprunghaft wächst. Vertrauens- und Seuchen-Multiplikatoren (§9, §14) wirken zusätzlich multiplikativ auf den fertigen Grundwert+Bonus-Betrag, siehe `AdvisorService::getApBreakdown`. Exakter Wert: `docs/game-reference.md#9-action-points-ap`.

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

> ## ✅ Freigegeben (Owner, 2026-08-03) — mit zwei Korrekturen
>
> Der Satz ist beschlossen. Zwei Punkte der ersten Fassung sind zurückgenommen, beide als Folge von §4c:
>
> 1. **`decay_rate` um ein Fünftel gesenkt** auf 0,40 / 0,60 / 0,80 / 1,20 — Begründung bei der Klassentabelle unten.
> 2. **Die Instanz-Preisregel ist zurückgezogen.** Die erste Fassung ließ die zweite und jede weitere Instanz den Level-Up-Preis zahlen; das war eine Reaktion auf den Hangar-Bootstrap-Zirkel unter dem alten, kleinen Sockel. Unter §4c sind Instanzen eine bewusste Designachse und echte Bauwerke — sie zahlen den **vollen Errichtungspreis**, linear. Der Zirkel löst sich von selbst: ~~120~~ **95** Rg (Preis korrigiert 2026-08-11, s. G4-Audit unten) für den zweiten Hangar sind bei ~20 Rg/Sol netto knapp fünf Sole (95/20 ≈ 4,75), kein Blocker mehr.
>
> **Die vier tragenden Zahlen:** Harvester-Frischwert (18 auf `regolith_normal`), Reparatur 1 Rg je SP, die vier `decay_rate`-Klassen, Errichtung ~~70/95/120~~ → **70 / 95 (alle drei Pfadgebäude, korrigiert 2026-08-11, s. G4-Audit unten)** gegen Level-Up flach 25. Alles Übrige — CC-Ausbau ×30, `bar.base_prices`, `compound_import_price`, `mission_supply_run`, `geology`-Kurve, Kenntniskosten, Startbestand — ist Feintuning.
>
> Eine Ausnahme mit Struktur-Charakter: Die **Preisrelation** aus der Knappheitsordnung (§3) ist tragend, auch wenn die konkreten Werte Feintuning sind. Steht der Überschuss teurer als der Mangel, funktioniert der Cantina-Hebel nicht.
>
> **Wenn sich die Zahlen als falsch erweisen — welche Stellschraube gilt:**
>
> | Beobachtung im Playtest | Stellschraube | **nicht** |
> |---|---|---|
> | Regolith staut sich an (Bestand steigt monoton) | Baukosten anheben | Sockel senken — trifft die Instandhaltung mit und riskiert die Verfallsspirale |
> | Regolith klemmt bei 0, Reparatur konkurriert dauernd mit Bauen | Sockel anheben | Reparaturkosten senken — sonst verschwindet der Verfall als Mechanik |
> | Verfall wirkt wie Dekoration, folgenlos ignorierbar | `decay_rate` anheben (bewegt beide Währungen zugleich) | Reparaturkosten anheben — das entkoppelt Regolith und AP wieder |
> | Instandhaltung fühlt sich spät schlaff an | levelskalierte Reparatur (`1 + floor((level−1)/3)` AP je SP) | `decay_rate` global anheben — trifft das Early Game am härtesten |
> | Ein Pfad hängt sichtbar zurück | den betreffenden **Hebel** anheben | Sockel oder Baukosten — die sind pfadneutral |
> | Mehr als 4 Sole pro Run an Regolith blockiert (G5) | Startbestand, dann Errichtungspreise | die Hebel — sie greifen zu spät für die frühe Klemme |

> **Nachtrag 2026-08-06 — Sockel-Neuherleitung gegen die 1-Harvester-Baseline, vollständig freigegeben.** Die obige Freigabe vom 2026-08-03 gilt weiterhin für die vier tragenden Zahlen (Harvester-Frischwert, allgemeine Reparatur 1 Rg/SP, `decay_rate`-Klassen, Errichtung ~~70/95/120~~ → **70 / 95 (alle drei Pfadgebäude, korrigiert 2026-08-11, s. G4-Audit unten)** vs. Level-Up 25). Die Owner-Entscheidung vom 2026-08-05 (§4c, „Ein Harvester ist die Baseline") machte den Sockel kleiner (12,9 statt der vormals angenommenen ~20–21,8 Rg/Sol) und riss dadurch zwei Rechnungen dieses Kapitels neu auf: die „Bilanz über den Run" (unten, neu hergeleitet) und die `decay_rate`-Anteilstabelle bei G2. **Beide sind am 2026-08-06 entschieden** (G6 umformuliert, G2-Metrik auf „Sockel + aktiver Pfad-Hebel" umgestellt — Details in der Neuherleitung unten und in G2/G6 der Guard-Rail-Tabelle) — kein Spielwert dieses Kapitels wurde dabei geändert, nur zwei Kennzahlen-Definitionen. Das Kapitel gilt jetzt als vollständig freigegeben.

Von der Designabsicht her hergeleitet statt aus den Bestandswerten fortgeschrieben. Ersetzt die Regolith-Anteile von §13.6.

#### Das Spielgefühl — zuerst, ohne Zahlen

Jede Zahl unten ist auf eine dieser Aussagen zurückführbar. Wo das nicht gelingt, ist sie willkürlich und gehört ersetzt.

| | Aussage |
|---|---|
| **G1** | **Regolith ist nie bequem und nie tödlich.** Der Bestand schwingt um eine niedrige zweistellige Zahl. Ein wachsender Haufen heißt, die Kolonie ist fertig; eine Null heißt, sie stirbt. Beides beendet die Spannung. |
| **G2** | **Instandhaltung ist Routine, nicht Krise.** Gemessen am Gesamteinkommen (Sockel + aktiver Pfad-Hebel, nicht Sockel allein — *umgestellt 2026-08-06, Owner-Entscheidung, kein Spielwert geändert*): Sie bindet ~15 % des Einkommens früh und ~40 % bei der Zielkolonie. Unter 10 % ist Verfall Dekoration und die USP fällt weg; über 60 % ist er eine Strafe fürs Bauen. |
| **G3** | **Vernachlässigung kostet ein Level, nicht den Run.** Ein Level-Down ist in 5–8 Solen aufgeholt, ohne Kaskadenrisiko. |
| **G4** | **Errichten ist eine Entscheidung, Level-Up ein Schritt.** Eine Errichtung [eines Pfadgebäudes] kostet 5–8 Sole Sparen, ein Level-Up 1–2. *(Präzisiert 2026-08-11, Owner-Entscheidung im G4-Audit: gilt für die drei Pfadgebäude Sciencelab/Hangar/Bar, jetzt einheitlich 95 Rg. Alle übrigen Errichtungen — bioFacility als Pflicht-Ramp-Gate vor CC Lv2 (dokumentierte Ausnahme, s. u.) ebenso wie reine Infrastrukturgebäude wie Wohnhabitat, Depot, Krankenstation, Sicherheits-Hub, Tempel, Monument — stehen außerhalb dieses Korridors; ihre Preise sind hier nicht geprüft und folgen anderen Kriterien, siehe Audit-Block unten.)* |
| **G5** | **Der Spieler soll 2–4 Mal pro Run an Regolith scheitern** — nicht dauernd (Grind), nicht einmal (Gate). |
| **G6** | **Der Sockel trägt das Überleben, der Pfad-Hebel das Wachstum.** Der Sockel allein trägt eine spielbare, aber unterdimensionierte Kolonie (~57 % der Zielgröße) — genug, um nicht zu scheitern, zu wenig, um die Zielkolonie zu erreichen. Der gewählte Pfad-Hebel schließt die Lücke auf annähernd 100 %, sobald er aktiv genutzt wird. Ein Run ohne jede Pfad-Aktivität ist im aktuellen Design nicht vorgesehen. *(Umformuliert 2026-08-06, Owner-Entscheidung — ersetzt „ohne genutzten Hebel ~70 %", das seit der 1-Instanz-Sockel-Baseline vom 2026-08-05 arithmetisch nicht mehr zutrifft; kein Spielwert geändert, siehe §13.7 „Neuherleitung gegen die 1-Instanz-Sockel-Baseline", Punkt 6.)* |
| **G7** | **Der Spieler muss im Kopf rechnen können.** „Ich mache 20 pro Sol, das kostet 95, das sind fünf Sole." |

**G4 ist die wichtigste Aussage**, weil sie Regolith und AP entkoppelt: Das AP-Modell macht es genau umgekehrt (`f(1) = 0.5` — Errichten AP-billig, Level-Up AP-teuer). **Breite kostet Regolith, Tiefe kostet AP.** Damit sind die beiden Währungen nicht mehr redundant, sondern greifen an gegenüberliegenden Enden an.

**G7 bestimmt die absolute Skala** — und das ist der eigentliche Grund gegen einen niedrigen Sockel, nicht eine Deckungslücke. Bei 8 Rg/Sol liegen die Baupreise zwischen 15 und 55: zwei unterscheidbare Klassen, und der Unterschied zwischen einem 25er und einem 30er Gebäude verschwindet im Reparaturrauschen. Bei 20 sind es vier bis fünf Klassen mit sauberem Abstand.

#### Der Satz

| Wert | heute | Vorschlag | folgt aus |
|---|---|---|---|
| Harvester-Ertrag | `[8,10,12,12,10,8,6,4]` kumuliert | **Frischwert 24 / 18 / 12** je Tile-Stufe, fallend mit der Erschöpfung (§4c) | G7, `max_level = 1` |
| `repair.regolith_per_click` | 2 | **1** | G2 + Vereinfachung, s. u. |
| `decay_rate` | 0,33–2,0 (geerbt) | **4 Klassen: 0,40 / 0,60 / 0,80 / 1,20** | G2, G3, korrigiert nach §4c |
| Errichtung (Lv0→1) | 40–100 | ~~70 / 95 / 120~~ → **70 (bioFacility, Ramp-Gate-Ausnahme) / 95 (alle drei Pfadgebäude: Sciencelab, Hangar, Bar)** (korrigiert 2026-08-11, Owner-Entscheidung Option 3 im G4-Audit, s. u. — Hangar 120→95 gesenkt, Sciencelab/Bar unverändert) | G4 (5–8 Sole) für die drei Pfadgebäude; bioFacility bewusste Ausnahme (s. u.) |
| Level-Up | 25 % der Errichtung | **flach 25** | G4 (1–2 Sole) |
| CC-Ausbau | Ziel-Level × 20 | **× 30** | zentraler Progressionshebel |
| Instanz 2 und folgende | voller `build_cost` | **unverändert voller `build_cost`** | §4c: Instanzen sind eine bewusste Designachse, keine Level |
| Startbestand | 200 | **200** (zufällig gleich) | Rampenprobe |
| `mission_supply_run.sol_distance` | 2 | **1** | Hebel-Zielgröße, kürzerer Entscheidungstakt |
| `geology`-Effekt | keiner | **+3/3/2/2/2 → kumuliert max 12** | 60 % des Sockels |
| `knowledge.levelup_costs` | 12/20/30/40/50 | **20/28/36/44/52** | Amortisation ~7 Sole |
| `knowledge.credits` | 100 | **0** | Credits-Lücke von Pfad A (§4b) |
| Hebel-Zielgröße | ~6 Rg/Sol | ~~12 Rg/Sol reif, ~6 im Run-Mittel~~ → **14,1 Rg/Sol reif, ~9,5 im Run-Mittel** (korrigiert 2026-08-06, §13.7 „Neuherleitung", Punkt 4 — gegen die 1-Instanz-Sockel-Baseline, nicht die alte 2-Instanzen-Bilanz) | Rampe + Sol-Äquivalente-Rechnung |

> **⚠️ Zeile „Instanz 2 und folgende" oben ist für den Harvester überholt — nicht Teil dieses freigegebenen Satzes.** Der 100-Rg-Regolith-Kostenanteil für die zweite Harvester-Instanz ist mit dem am 2026-08-05 freigegebenen Vorschlag hinfällig (§4c „Harvester-Zweitinstanz: Bezugsquelle"): Weg A (Orin, `corporate_rep`) kostet 400–800 Cr statt Regolith, Weg B (Bergungsmission `mission_harvester_salvage`) kostet keinen Regolith-Anteil, sondern ausschließlich AP (**korrigiert 2026-08-06**, siehe §4c „Weg B": der Harvester ist strukturell von Regolith-Reparaturkosten ausgenommen, für jede Instanz, nicht nur die erste — ~14–15 AP, 0 Rg für den beschädigt ankommenden Fund). Beide Wege sind zusätzlich nicht garantiert verfügbar (§4c). Die „voller `build_cost`"-Regel bleibt für alle anderen instanzierten Gebäude (Agrardom, Hangar, Wohnhabitat) unverändert gültig — nur der Harvester ist die Ausnahme, wegen der beschlossenen Bootstrap-Sonderrolle dieses einen Gebäudes. **Die 1-Instanz-Sockel-Baseline (§4c „Deckel"-Abschnitt) ist jetzt vollständig neu hergeleitet und freigegeben (2026-08-06)** — siehe unten, „Neuherleitung gegen die 1-Instanz-Sockel-Baseline".

**Zur Reparatur — eine Zahl, zwei Währungen.** Reparatur kostet bereits 1 AP je SP. Bei ebenfalls 1 Regolith je SP gilt:

```
Instandhaltung [Rg/Sol]  =  Instandhaltung [AP/Sol]  =  Σ decay_rate
```

Das Dashboard (13.4) braucht dann keine zwei Zeilen und 13.5 keine zwei Tabellen. Die heutige 1 : 2-Kopplung existiert nur, damit Reparatur „teuer wirkt" — dafür ist `decay_rate` der bessere Knopf, weil er beide Seiten gleichzeitig bewegt.

**Zu `decay_rate` — aus einer Spielaussage abgeleitet.** Regel bleibt `decay_rate = max_status_points / Sole_bis_Level_Down`. Neu ist, dass die Sole eine Designaussage sind: *wie teuer ist es, dieses Gebäude zu vergessen?*

| Klasse | Sole bis Level-Down | Rate | Gebäude |
|---|---|---|---|
| Robust | 50 | **0,40** | Kommandozentrale, Wohnhabitat, Kolonialdenkmal |
| Standard | 33 | **0,60** | Agrardom, Uplink-Station, Hangar, Handelsposten, Sicherheits-Hub |
| Beansprucht | 25 | **0,80** | Harvester, Analytik-Labor, Cantina, Krankenstation |
| Fragil | 17 | **1,20** | Religiöse Stätte |

> **Um ein Fünftel gesenkt gegenüber der ersten Fassung (2026-08-03).** Grund ist §4c: Mit Agrardom und Harvester als Instanzen hat die Zielkolonie **16 statt 10** verfallende Zeilen. Bei den ursprünglichen Raten (0,50/0,80/1,00/1,50) läge die Instandhaltung bei Σ 13,6 = **62 % des AP-Pools** — die Einfrier-Zone, die §13.5 ausdrücklich ablehnt. Mit den korrigierten Raten: Σ 9,0 = **41 % des Pools, 35 % des Regolith-Zuflusses**. Ein vernachlässigtes Gebäude verliert weiterhin innerhalb eines Runs ein Level (17–50 Sole), der Verfall bleibt als Systemprinzip spürbar.

Bei den drei robusten ist ein Level-Down überproportional teuer (Supply-Cap bricht weg, Instanz verschwindet) — er muss langsam kommen, sonst verletzt er G3. Die Religiöse Stätte ist bewusst der teuerste Unterhalt im Spiel: Sie zahlt in Vertrauen, nicht in Funktion; wer sie hält, entscheidet sich aktiv dafür.

Instandhaltung gegen G2 — **umgestellt 2026-08-06 (Owner-Entscheidung, Option B der §13.7-Neuherleitung): Bezugsgröße ist Sockel + aktiver Pfad-Hebel, nicht Sockel allein.** Grund: Seit der 1-Instanz-Sockel-Baseline (2026-08-05) trägt der reine Sockel nur noch 12,9 statt vormals ~20 Rg/Sol — dieselben Σ-`decay_rate`-Werte hätten gegen den kleineren Sockel allein die 60-%-Obergrenze gerissen (Vollausbau ~79 %, siehe §13.7 „Neuherleitung", Punkt 7). Die neue Bezugsgröße ab dem ersten Pfad-Gebäude ist Sockel + reifer Wert des aktiven Pfad-Hebels (hier: Pfad A/`geology`, 12 Rg/Sol, als repräsentativer Referenzwert — die Tabelle bildet nicht jeden der drei Pfade einzeln ab):

**Validierungstabelle:** Die Vollausbau-Last trifft G2s eigenen Zielwert bei Einkommen aus Sockel + aktivem Hebel. Tabelle siehe `config/buildings.php` (decay_rates) und Balance-Herleitung in PR-Review 2026-08-05/06. Die AP-Last skaliert konsistent mit der Regolith-Last; Umstellung betrifft nur die Regolith-Metrik.

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

Gleicher Endpunkt wie heute (CC Lv2 an Sol 4), aber mit einem Einkommen, das den Puffer trägt statt ihn zu verzehren. Der in 13.5 als offen markierte Engpass „Sole 8–20" verschwindet.

> **Korrektur 2026-08-11 (G4-Audit, Owner-Entscheidung Option 3):** Der Satz „Beim Hangar-Pfad (120 statt 95) endet Sol 4 bei ~15 Rg — knapper, aber nicht negativ; die teuerste Pfadwahl wird damit zur echten Entscheidung statt zur kosmetischen" ist überholt. Hangar kostet jetzt ebenfalls 95 (s. u.) — alle drei Pfadgebäude sind preislich identisch, die Rampe verläuft für jeden der drei Pfade gleich wie oben für Cantina gezeigt (Sol 2 endet bei 68 Rg, Sol 4 bei 40 Rg); die frühere ~15-Rg-Variante des 120er-Hangar-Pfads entfällt. Die Pfadwahl bleibt eine echte Entscheidung, aber sie unterscheidet sich jetzt ausschließlich über den Hebel-Mechanismus (§4b: `geology` vs. `mission_supply_run` vs. Cantina-Credits-Hebel) und die AP-/Gate-Struktur, nicht mehr über den Regolith-Preis. Das ist eine Vereinfachung, keine Verarmung: die drei Pfade waren nie primär über den Bau-Preis differenziert gedacht (§4b, „Paritäts-Anforderung" — Gleichwertigkeit ist ausdrückliches Designziel, nicht nur Kollateraleffekt).

**Bilanz über den Run — historisch (Stand 2026-08-04, überholt).** Zielkolonie ≈ 1.895 Rg Bedarf (835 Errichtungen + 720 Level-Ups + 100 Zweitinstanz + ~240 Reibung — die 100 Rg sind die korrigierte Zweitinstanz-Zahl aus dem „Stale Zahl"-Fund vom 2026-08-04, ursprünglich fälschlich mit 25 Rg angesetzt). Sockel-Einnahmen bis Sol 80 ≈ 1.363 Rg, unter der damals gültigen 2-Instanzen-Sockelannahme (Sol 1–30 bei 1, Sol 30–80 bei 2 Instanzen). Sockel-Anteil ≈ 72 %, Lücke ≈ 535 Rg ≈ 6,7 Rg/Sol im Mittel. **Überholt durch die Owner-Entscheidung 2026-08-05** (§4c „Ein Harvester ist die Baseline") — die zweite Instanz ist kein Bestandteil der Standard-Zielkolonie mehr. Vollständige Neuherleitung unten.

#### Warum die Hebel-Zielgröße vorher um Faktor 2 danebenlag

Die alte Zahl „~6 Rg/Sol je Hebel" war der **Mittelwert über den Run**, angewandt als **Reife-Wert**. Ein Hebel läuft aber nicht ab Sol 1: Pfad A braucht Kenntnisstufen, Pfad B ein Schiff. Realistisch greift er ab Sol ~12 und ist ab ~40 voll — die reife Höhe muss deshalb beim Doppelten liegen. (Gilt für die verbliebenen Regolith-Hebel A und B — Pfad C hat seit dem §4b-Vorschlag „von Regolith zu Credits" keinen eigenen Regolith-Hebel mehr; sein Credits-Hebel folgt derselben Verdopplungsregel, aber über die Konsul-Rang-Progression statt über die Sol-Zeitachse, siehe dort.)

Gegenprobe aus anderer Richtung, zugleich die Merkregel: **ein reifer Pfad-Hebel ist etwa 60 % eines Harvesters.** Spürbar, aber kein Ersatz für den Sockel. Beide Herleitungen landen bei 12.

---

#### Neuherleitung gegen die 1-Instanz-Sockel-Baseline (Freigegeben 2026-08-06)

Ersetzt die „Bilanz über den Run" oben vollständig. Löst die dort zuvor stehenden ⚠️-Marker auf, ebenso die Verweise in §4b („Pfad-C-Hebel: von Regolith zu Credits") und §4c (Punkt 5 der „Für die Owner-Entscheidung"-Liste, „Deckel"-Abschnitt).

**Run-Länge dieser Rechnung: 80 Sole**, dieselbe Fensterbreite wie die alte Bilanz — sie deckt sich mit der Phase-2-Sol-80-Konvention (§18.4/§15, Countdown-Anker bei typischem Phase-1-Ende Sol 20). Diese Annahme wird hier übernommen, nicht neu hergeleitet.

**1. Zielkolonie-Bedarf — bleibt stabil, minus die gestrichene Zweitinstanz-Zeile.**

Die 835 Rg (Errichtungen), 720 Rg (Level-Ups) und ~240 Rg (Reibung: Reparaturen, Fehlplatzierungen, Verlegungen) hängen am Gebäude- und Level-Katalog der Zielkolonie, nicht an der Harvester-Instanzzahl — sie bleiben unverändert. Was entfällt: die 100-Rg-„Zweitinstanz"-Zeile, weil die zweite Harvester-Instanz seit der Owner-Entscheidung 2026-08-05 kein Bestandteil der Standard-Zielkolonie mehr ist, sondern ein optionaler, nicht garantierter Bonus (§4c). Ein Run, der sie nie bekommt, muss sie auch nicht bezahlen — folgerichtig zählt sie nicht mehr zum Bedarf, den Sockel + Hebel decken müssen.

```
Zielkolonie-Bedarf = 835 (Errichtungen) + 720 (Level-Ups) + 240 (Reibung) = 1.795 Rg
```

**2. Sockel-Einnahmen — neu aus der freigegebenen Erschöpfungskurve gerechnet, nicht aus der alten 1.363-Rg-Zahl fortgeschrieben.**

§4c legt die Zyklusmittel-Zahl für den Standardfall (1 Instanz, `regolith_normal`, inkl. Transit-Sol) bereits fest: **12,9 Rg/Sol**, konstant über den gesamten Run — kein Sprung mehr auf eine zweite Instanz, die ist Bonusfall.

```
Sockel-Einnahmen = 12,9 Rg/Sol × 80 Sole = 1.032 Rg
```

*Zur alten 1.363-Rg-Zahl:* Sie lässt sich nicht sauber auf einen einzelnen, benennbaren Blendfaktor der 2-Instanzen-Annahme zurückführen — vermutlich ein Zwischenstand von vor der 2026-08-03-Freigabe der Erschöpfungskurve, mit anderen Annahmen gerechnet (z. B. unrabattierter Frischwert statt Zyklusmittel). Die neue Zahl hier ist unabhängig davon direkt aus der freigegebenen Kurve und der 08-05-Baseline hergeleitet; sie ersetzt die alte, statt an sie anzuknüpfen.

**3. Sockel-Anteil — bricht die G6-Vorgabe (~70 %), zentraler Befund dieser Neuherleitung.**

```
Sockel-Anteil = 1.032 / 1.795 ≈ 57,5 %
```

Das liegt spürbar unter den ~70 % aus G6 und unter den 72–75 %, die die alte, jetzt überholte Bilanz auswies. **Das ist kein Rechenfehler, sondern die unmittelbare, erwartbare Folge der Owner-Entscheidung**, den Sockel von ~21 auf 12,9 Rg/Sol zu senken, ohne die Zielkolonie im selben Schritt zu verkleinern.

**4. Hebel-Lücke — die Fläche direkt gerechnet, nicht der Mittelwert verdoppelt.**

Nominale Lücke:

```
1.795 − 1.032 = 763 Rg über 80 Sole ≈ 9,5 Rg/Sol im Run-Mittel
```

Wie oben begründet („Warum die Hebel-Zielgröße vorher um Faktor 2 danebenlag"), greift ein Pfad-Hebel nicht ab Sol 1 — er braucht Vorlauf (Kenntnisstufen bei A, ein Schiff bei B) und ist erst ab einem späteren Sol voll wirksam. Statt der bisherigen groben Faustregel „reife Höhe = Doppeltes des Mittels" hier die Fläche direkt: Der Hebel greift ab ~Sol 12, rampt linear bis ~Sol 40, ist danach voll wirksam bis Sol 80.

```
Sol-Äquivalente = (Sol 12–40: 28 Sole × 0,5 Rampe) + (Sol 40–80: 40 Sole × volle Wirkung)
                = 14 + 40 = 54 effektive Sole (von 80 nominell)

reife Hebelhöhe = 763 Rg / 54 Sole ≈ 14,1 Rg/Sol
```

**5. Deckung — Pfad A und Pfad B schließen die Lücke gemeinsam, mit Puffer; Pfad C bleibt bei null, wie in §4b bereits entschieden.**

Die beiden Regolith-Hebel aus der Paritätstabelle (§4b):

| Pfad | Hebel | reifer Wert |
|---|---|---|
| A — Analytik | `geology`, kumuliert max 12 | 12 Rg/Sol |
| B — Hangar | `mission_supply_run`, 6,25/Sol je Frachter | 6,25 Rg/Sol (1 Schiff), skaliert mit Flottengröße |
| C — Cantina | kein dedizierter Regolith-Hebel (Entscheidung §4b, unverändert) | 0 |

```
A + B gestapelt (1 Frachter) = 12 + 6,25 = 18,25 Rg/Sol ≥ 14,1 Rg/Sol benötigt
```

Gemeinsam übersteigen A und B die benötigten 14,1 Rg/Sol um ~29 % — die Lücke schließt sich rechnerisch komfortabel, **wenn beide Hebel im relevanten Zeitfenster aktiv sind.** Das ist der Punkt, an dem die Rechnung eine Prämisse braucht, die das GDD bisher nicht exakt beziffert: §4b beschreibt, dass jeder Run **alle drei** Pfad-Gebäude bekommt, nur gestaffelt über CC-Level (erster Pfad ab CC Lv2, die anderen bei CC Lv3/Lv4) — wann genau ein zweiter Pfad-Hebel im selben Run aktiv wird, hängt am CC-Lv3/4-Timing, das hier nicht neu hergeleitet wird.

**Konsequenz, konservativ betrachtet — auch ein einzelner, isoliert genutzter Hebel reicht nahe an die Zielgröße heran:**

- Nur Pfad A aktiv (kein Pfad B): 12 Rg/Sol reif ≈ **85 % der benötigten Hebelhöhe** (14,1 Rg/Sol reif; entspricht ~94 % des Zielkolonie-Bedarfs, §4b) — eine kleine Restlücke, innerhalb der Toleranz, die G5 ohnehin vorsieht („2–4 Mal pro Run an Regolith scheitern").
- Nur Pfad B mit 1 Frachter aktiv: 6,25 Rg/Sol reif ≈ **44 % der benötigten Hebelhöhe** (entspricht ~76 % des Zielkolonie-Bedarfs, §4b) — spürbar knapper, aber Pfad B lässt sich durch weitere Frachter aufstocken (2 Frachter ≈ 12,5, 3 ≈ 18,75) — genau die „aktiv arbeiten, breit versorgen"-Identität aus §4b.
- Ein Run, der primär Pfad C verfolgt und A/B erst spät bekommt, läuft in der Zwischenzeit näher am reinen Sockel (57,5 %) — spürbar knapper, aber nicht tödlich (Regolith „nie tödlich", G1) und deckungsgleich mit der bereits in §4c dokumentierten Owner-Absicht, dass ein starker Regolith-Hebel nicht garantiert früh in jedem Run vorkommen muss.

**Bestätigt (2026-08-06): keine Änderung an `geology`-Kurve oder `mission_supply_run`-Rate.** Die Zahlen tragen die Lücke, sobald mindestens ein Regolith-Hebel aktiv genutzt wird — genau das, was G6 jetzt auch so formuliert (Punkt 6, umgesetzt).

**6. G6 muss umformuliert werden — Owner-Entscheidung, keine stille Korrektur.**

G6 lautet aktuell: „Ohne genutzten Hebel erreicht die Kolonie ~70 % der Zielgröße." Das trifft arithmetisch nicht mehr zu (57,5 % ohne jeden Hebel, Punkt 3) — nicht weil die Rechnung falsch wäre, sondern weil die Prämisse „ohne genutzten Hebel" unter dem heutigen Pfad-Design nicht mehr der relevante Grenzfall ist: Die Pfadwahl ist verpflichtend (§4b, „ab CC Lv2 ... von denen zunächst nur eines gebaut werden kann"), jeder Run hat spätestens ab seinem ersten Pfad-Gebäude einen Hebel-Kandidaten. Ein Run ganz ohne jede Pfad-Aktivität ist im Design nicht vorgesehen. Vorschlag für die neue Formulierung:

> **G6 (Vorschlag):** *Der Sockel trägt eine spielbare, aber unterdimensionierte Kolonie allein (~57 % der Zielgröße) — genug, um nicht zu scheitern, zu wenig, um die Zielkolonie zu erreichen. Der gewählte Pfad-Hebel schließt die Lücke auf annähernd 100 %, sobald er aktiv genutzt wird. Ein Run ohne jede Pfad-Aktivität ist im aktuellen Design nicht vorgesehen — die alte Formulierung „ohne genutzten Hebel" beschrieb einen Grenzfall, der es nicht mehr ist.*

Drei Stellschrauben, falls der Owner die 57,5-%-Untergrenze zu niedrig findet — zur Wahl vorgelegt, nicht selbst entschieden:

| Option | Wirkung | Kosten |
|---|---|---|
| G6 wie oben umformulieren, Zahlen unverändert lassen | keine Balance-Änderung, nur Aussage korrigiert | keine |
| Zielkolonie verkleinern (weniger Errichtungen/Level-Ups in der Referenzrechnung) | hebt den Sockel-Anteil an, ohne den Sockel selbst zu ändern | reißt die freigegebenen §13.7-Kernzahlen (Errichtung 70/95/120, CC-Ausbau ×30) wieder auf — hohe Kollateralwirkung auf G4/G7 (diese Warnung hat sich am 2026-08-11 in begrenztem Umfang bewahrheitet: Hangar 120→95 im G4-Audit korrigiert, s. u. — betrifft nur einen der drei Werte, nicht CC-Ausbau) |
| Sockel erneut anheben (z. B. Frischwert/`resource_max` der Erschöpfungskurve) | direktes Gegenmittel | widerspricht der ausdrücklichen 08-05-Entscheidung, den Sockel klein zu halten |

**Empfehlung: erste Option.** Sie ändert keinen einzigen Spielwert, nur die Formulierung von G6 — konsistent mit der Owner-Absicht („ein Harvester soll reichen, ein zweiter ist Bonus"), ohne die am 08-03 freigegebenen Kernzahlen erneut anzufassen.

**7. Offener Konflikt, nicht Teil dieser Freigabe: die G2-Instandhaltungslast-Tabelle bricht unter der neuen Sockelgröße.**

Die Instandhaltungs-Kennzahl war unter der alten Sockel-Baseline gegen einen Referenzwert kalibriert, der unter der neuen Baseline zu höheren Anteilen führte — bis zur Verletzung von G2s eigenen Grenzen (Verfall als Dekoration vs. als Strafe). **Auflösung (2026-08-06, Owner-Entscheidung Option B):** Umstellung der Kennzahl auf Anteil am Gesamteinkommen (Sockel + aktiver Pfad-Hebel), nicht Sockel allein — ohne Spielwert-Änderung, nur Bezugsgrößen-Neuformulierung. Vollständige Validierung siehe Git-Audit-Dokumentation (2026-08-05/06, §13.7 Neuherleitung)."

**Das war eine echte Kollision zwischen der 08-05-Sockel-Entscheidung und der 08-03-`decay_rate`-Freigabe — inzwischen entschieden.**

> **Owner-Entscheidung 2026-08-06: Option B — G2-Metrik umgestellt auf „Anteil am Gesamteinkommen (Sockel + aktiver Pfad-Hebel)" statt „Anteil am Sockel allein".** Kein Spielwert geändert (`decay_rate` bleibt bei 0,40/0,60/0,80/1,20, Zielkolonie-Instanzzahl unverändert) — nur die Bezugsgröße der Kennzahl. Ergebnis, mit Pfad-A-Referenzwert (`geology`, reif 12 Rg/Sol) als aktivem Hebel: Vollausbau fällt von 85,3 % (bzw. 79,1 % nach Abzug der gestrichenen Zweitinstanz-Zeile) auf **41,0 %** — trifft G2s eigenen Zielwert „~40 % bei der Zielkolonie" fast exakt. Umgesetzt in der Tabelle „Instandhaltung gegen G2" oben. Die beiden verworfenen Alternativen (erneute `decay_rate`-Senkung, kleinere Zielkolonie) sind damit vom Tisch.

> *Fußnote (weiterhin gültig):* Die Σ-Werte 2,00/2,80/4,60/5,60/7,90/11,00 der ursprünglichen Tabelle ließen sich nicht vollständig aus den dokumentierten Einzel-`decay_rate`-Werten rekonstruieren, ohne die genaue Instanzzahl je Zeile zu kennen. Ein Teilstück ließ sich vorab beantworten: Die gestrichene Harvester-Zweitinstanz war eine der 13 Zeilen der Vollausbaustufe, Klasse „Beansprucht" = 0,80 — ohne sie sinkt Σ von 11,00 auf **10,20**, die aktuelle Basis für die obige 41,0-%-Rechnung.

**8. G4 und G7 — geprüft, halten früh, driften erst spät.**

G7 begründete die Sockelhöhe ursprünglich mit unterscheidbaren Preisklassen („bei 8 Rg/Sol liegen die Baupreise ... im Reparaturrauschen; bei 20 sind es vier bis fünf Klassen") und G4 mit „Errichtung = 5–8 Sole Sparen" — beide implizit gegen einen Sockel von ~20 Rg/Sol kalibriert. Bei 12,9 Rg/Sol dauert der 120er-Hangar-Bau rechnerisch 9,3 statt 6 Sole — außerhalb des G4-Korridors.

**Das ist kein dritter Konflikt neben Punkt 7, sondern entschärft sich durch dieselbe Unterscheidung, die §4c bereits trifft: 12,9 ist der Zyklusmittel-Wert über Erschöpfung und Transit, nicht das, was ein Spieler früh im Run tatsächlich sieht.** Ein frisch platzierter Harvester auf `regolith_normal` liefert **18 Rg/Sol** (Frischwert, kein Verfall) — die Sol-1–4-Rampe (oben, „Proben") rechnet korrekt mit diesem Wert, nicht mit 12,9, und bleibt deshalb unverändert gültig: ihr +16,2-Netto-Einkommen ist Frischwert-Einkommen, keine Zahl, die diese Neuherleitung berührt. G4/G7 halten also dort, wo sie im Spielgefühl wirken — in der frühen Errichtungsphase auf frischen Tiles. Sie driften erst im späten Run, wenn Tiles erschöpft sind und Verlegungen den Ertrag auf den Zyklusmittelwert drücken — dort sind längere Sparzeiten ohnehin beabsichtigt (Zielkolonie-Ausbau ist Spätspiel-Arbeit, kein Sol-1–4-Tempo). **Keine Handlungsempfehlung nötig; die Sol-1–4-Rampe-Tabelle bleibt unverändert.**

> **Überholt durch den Audit vom 2026-08-07 und die Owner-Entscheidung vom 2026-08-11.** Die obige Einschätzung „G4/G7 halten früh, driften erst spät" beruhte auf einer einzigen Stichprobe (nur der 120er-Hangar-Preis). Der Audit unten prüfte die volle Matrix (70/95/120 gegen beide Referenzwerte) und fand, dass auch der 70er-bioFacility-Preis am Frischwert unter die Untergrenze fällt (3,9 statt ≥5 Sole) — kein reines Spätspiel-Problem. Die Owner-Entscheidung vom 2026-08-11 (Option 3, s. u.) hat daraufhin den Hangar-Preis von 120 auf 95 gesenkt und die drei Pfadgebäude damit auf einen gemeinsamen Preis vereinheitlicht; bioFacility bleibt bei 70 als bewusst dokumentierte Ausnahme (kein Pfadgebäude, sondern Pflicht-Ramp-Gate vor CC Lv2, s. u.).

---

### Für die Owner-Entscheidung

1. **Erledigt (2026-08-06): G6-Umformulierung** (Punkt 6) — Option 1 gewählt („Sockel trägt ~57 % allein, Pfad-Hebel schließt auf ~100 %"), kein Spielwert geändert, nur die Aussage. Umgesetzt in der Guard-Rail-Tabelle oben.
2. **Erledigt (2026-08-06): `decay_rate`/G2-Kollision** (Punkt 7) — Option B gewählt (G2-Metrik auf „Sockel + aktiver Pfad-Hebel" umgestellt statt „Sockel allein"). Kein Spielwert geändert; Vollausbau-Anteil fällt dadurch von 79,1 % auf 41,0 %, trifft G2s eigenen ~40-%-Zielwert. Umgesetzt in der Tabelle „Instandhaltung gegen G2" oben. Passt zur selben Logik wie Entscheidung 1 (G6) — beide beruhen darauf, dass „ohne Hebel" im aktuellen Pfad-Design kein relevanter Regelfall mehr ist.
3. **Keine Änderung an `geology` (max 12) oder `mission_supply_run` (6,25/Sol je Frachter)** — beide reichen zusammen (18,25 Rg/Sol reif) für die benötigten 14,1 Rg/Sol; Zustimmung zur Empfehlung „Zahlen unverändert lassen" (Punkt 5).
4. **Zurückziehung der 247-Cr/Sol-Pfad-C-Zielgröße** (§4b „Dimensionierung — korrigiert") — Zustimmung, dass diese Zahl falsch hergeleitet war (aus der Regolith-Lücke statt aus dem Credits-Bedarf), und dass eine eigenständige Credits-Bilanz-über-den-Run als neues, offenes Vorhaben in Anhang A A.4 aufgenommen wird (noch nicht terminiert).
5. **Weg-B-Reparatur ist 0 Rg + ~14–15 AP** (§4c „Weg B", Code-Befund PR #237) — Kenntnisnahme, keine Entscheidung nötig. Konsequenz: die `ruin_tile`-Spawnrate (bisher ⚠️ unverifiziert) wird dadurch zur **Vorbedingung** für die Balance von Weg A vs. Weg B, nicht mehr nur zu einem offenen Detail — vor Umsetzung von `mission_harvester_salvage` klären.
6. **G4/G7 (Punkt 8)** — keine Entscheidung nötig, nur zur Kenntnisnahme: beide bleiben gültig, weil sie an Frischwert-Einkommen (frühes Spiel) hängen, nicht am Zyklusmittel (12,9 Rg/Sol, Spätspiel).

**Config-Keys, betroffen bei Freigabe:**

| Key | Änderung | Bedingung |
|---|---|---|
| `game.harvester.second_instance_regolith_cost` | verliert seine Bedeutung (war 100 Rg) — ersetzt durch Weg-A-Credits-Preis (400–800 Cr) bzw. Weg-B-Freischaltung | bereits mit §4c-Freigabe vom 08-05 fällig, unabhängig von dieser Neuherleitung |
| `game.harvester.second_instance_*` (weitere Felder) | Semantik ändert sich mit Weg A/B (Gate statt Direktkauf) | dito |
| `buildings.decay_rate` | **keine Änderung** — Option B gewählt (G2-Metrik umgestellt, nicht die Rate selbst) | entschieden, Owner-Entscheidung 2 oben |
| `game.bar.*` (Verkaufspreis Organika, Reserve-Untergrenze), `game.merchant.*` (Corvan-Frequenz/Losanzahl unter Direction 1) | **blockiert** — keine Zahl ableitbar, solange die Credits-Bilanz-über-den-Run offen ist | wartet auf Owner-Entscheidung 4 oben |
| `game.corporate_contact.*` | neu anzulegen (Orin, Weg A) | unverändert bereits aus §4c fällig |
| `geology_harvester_bonus_per_level`, `mission_supply_run.*` | **unverändert** — ausdrücklich bestätigt, keine Anpassung nötig | Owner-Entscheidung 3 oben |
| `buildings.php` → `build_cost` (Regolith-Anteil), Hangar (id 44) | **120 → 95** | entschieden + implementiert 2026-08-11, G4-Audit unten (PR #243) |

---

#### Audit: G1, G3, G4, G5, G7 gegen die 12,9-Rg/Sol-Baseline (2026-08-07)

**Status: Audit, Owner-Review ausstehend.** Kein Spielwert geändert. Diese Prüfung rechnet nicht neu her, was in der Neuherleitung oben (Punkte 1–8) bereits freigegeben ist — sie schließt die Lücke, dass dort nur G2/G6 (Punkte 6–7) und G4/G7 in einer einzigen Stichprobe (Punkt 8, nur die 120er-Errichtung) geprüft wurden. G1, G3 und G5 waren seit der 08-05-Baseline-Änderung noch nie explizit gegen die neuen Zahlen gerechnet worden. Geprüft gegen die beiden Referenz-Einkommen aus der Neuherleitung: Frischwert 18 Rg/Sol (`regolith_normal`, frisch platzierter Harvester) und Zyklusmittel 12,9 Rg/Sol (Sockel, Spätspiel), sowie 24,9 Rg/Sol (Sockel + reifer Pfad-A-Hebel, Referenzwert aus der G2-Tabelle).

**G3 — verifiziert, hält (a).**

Aus der bereits freigegebenen Klassentabelle (`decay_rate = max_status_points / Sole_bis_Level_Down`) folgt für alle vier Klassen derselbe `max_status_points`-Wert, ohne dass Zeile 1217 nötig ist: Robust 0,40 × 50 = 20, Standard 0,60 × 33 ≈ 20, Beansprucht 0,80 × 25 = 20, Fragil 1,20 × 17 ≈ 20. Ein vollständiges Level-Down-Aufholen kostet damit **20 Rg + 20 AP** (1 Rg + 1 AP je SP, s. o.).

| Szenario | Rg-Seite | AP-Seite (Pool ~22, Instandhaltung 7,9–10,2 AP/Sol, s. Punkt 7) |
|---|---|---|
| Sockel-only, Spätspiel (12,9 − 10,2 Instandhaltung = 2,7 Rg/Sol netto) | 20 / 2,7 ≈ **7,4 Sole** | 20 / (22 − 10,2) ≈ 1,7 Sole |
| Sockel + Pfad-Hebel aktiv (24,9 − 10,2 = 14,7 Rg/Sol netto) | 20 / 14,7 ≈ **1,4 Sole** | dito |

Beide Fälle liegen innerhalb des 5–8-Sole-Korridors — auch der pessimistische Sockel-only-Fall (7,4 von 5–8), wenn auch am oberen Rand. G3 war nicht Teil der 08-06-Prüfung, hält aber unter Nachrechnung. Keine Korrektur nötig.

> ✅ **G4 — entschieden (Owner, 2026-08-11): Option 3, Errichtungspreise neu justiert.**
>
> §13.7 Punkt 8 hatte nur eine Stichprobe geprüft (Errichtung 120 bei 12,9 Rg/Sol → 9,3 Sole, über der Obergrenze) und daraus geschlossen, G4 „hält früh, driftet erst spät". Das galt nicht für die volle Matrix:
>
> | Errichtung | bei Frischwert 18 Rg/Sol | bei Zyklusmittel 12,9 Rg/Sol |
> |---|---|---|
> | 70 | 3,9 Sole — unter der Untergrenze 5 | 5,4 ✓ |
> | 95 | 5,3 ✓ | 7,4 ✓ |
> | 120 | 6,7 ✓ | 9,3 Sole — über der Obergrenze 8 |
>
> Der mathematische Schnittbereich, in dem ein Preis bei **beiden** Referenzwerten gleichzeitig im 5–8-Sole-Korridor liegt, ist eng: `preis/18 ∈ [5,8]` verlangt `preis ∈ [90,144]`, `preis/12,9 ∈ [5,8]` verlangt `preis ∈ [64,5; 103,2]` — Schnittmenge `[90; 103]`. Drei spürbar unterscheidbare Preisklassen (G7) hineinzuzwingen hätte den Effekt, dass die Differenzen zwischen den Klassen (~5–13 Rg) unter die Rauschgrenze fallen, die G7 selbst benennt: ein vollständiges Level-Down-Aufholen kostet 20 Rg (G3), Preisklassen, die enger als das auseinanderliegen, verschwinden im Reparaturrauschen — dieselbe Formulierung, die G7 (Zeile 2550) für den alten 8-Rg/Sol-Fall verwendet.
>
> **Die eigentliche Auflösung kam nicht aus der Preis-Arithmetik, sondern aus einer Kategorien-Korrektur:** Die 70/95/120-Tabelle vermischte zwei unterschiedliche Gebäudekategorien. Nur Sciencelab (31), Hangar (44) und Bar (52) sind „die drei Pfadgebäude" im Sinn von §4 („Pfadwahl ab Sol 3", Zeile 366) und §4b — bioFacility/Agrardom (41) ist **kein** Pfadgebäude, sondern das Pflicht-Ramp-Gate vor CC Lv2 (Zeile 353: „**Pflichtgebäude vor CC Lv2**"), zuständig für die Nahrungssicherheits-Dringlichkeit (§3, Organika-Rennen), nicht für die Pfadwahl-Abwägung. G4 als „Preisklassen-Spreizung, die kopfrechenbar bleiben muss" (G7) war implizit nur gegen die drei tatsächlich gegeneinander abzuwägenden Pfadgebäude gedacht — bioFacility steht nicht zur Wahl, es ist obligatorisch und früh, sein Preis muss nicht gegen die anderen beiden differenzierbar sein.
>
> **Beschluss:**
> - **Hangar (44): 120 → 95.** Damit kosten alle drei Pfadgebäude identisch 95 Rg. Rechnung unverändert gültig für alle drei: 95/18 ≈ 5,3 Sole, 95/12,9 ≈ 7,4 Sole — beide klar innerhalb [5,8], kein Rand mehr. Die Preisgleichheit ist kein Verlust an Entscheidungstiefe: §4b („Paritäts-Anforderung", Zeile 713) verlangt ausdrücklich, dass die drei Pfade gleichwertig sind — Gleichpreisigkeit ist die konsequente Umsetzung dieser bereits bestehenden Vorgabe, nicht ein neuer Kompromiss. Die Pfade unterscheiden sich weiterhin über ihren Hebel-Mechanismus (`geology` vs. `mission_supply_run` vs. Cantina-Credits-Hebel, §4b) und ihre AP-/Gate-Struktur — nur nicht mehr über den Regolith-Bau-Preis.
> - **bioFacility (41): 70, unverändert — dokumentierte Ausnahme, nicht Teil des G4-Korridors, aber nur für die erste Instanz volle Absicht.** Der Preis bleibt außerhalb von [5,8] am Frischwert (3,9 Sole) und ist das für die **erste** Instanz absichtlich: bioFacility ist das erste Gebäude nach CC Lv1 + Harvester Lv1 (Pflichtgebäude vor CC Lv2, Zeile 353), muss unter Zeitdruck (Nahrungssicherheit) schnell erreichbar sein, und ist zu diesem Zeitpunkt keine Abwägungsentscheidung zwischen mehreren Optionen. bioFacility ist aber laut §4c/Zeile 800 eine **Instanz** wie Harvester — weitere Domes werden bei Bedarf mittel- und spätspielig nachgebaut, zum vollen `build_cost` (Zeile 2570: „voller Errichtungspreis, linear" gilt für alle instanzierten Gebäude außer Harvester). Für diese späteren Instanzen ist das relevante Einkommen ohnehin näher am Zyklusmittel als am Frischwert eines Sol-1-Harvesters — dort liegt 70/12,9 ≈ 5,4 Sole sauber im Korridor. Die Ausnahme gilt also präzise für den Zeitdruck-Sonderfall „erste Instanz, früh im Run", nicht für bioFacility als Preisklasse insgesamt.
>
> **Verworfen:** Option 1 (Korridor als reinen Referenzfall der mittleren Klasse umdeuten) und Option 2 (Korridor pauschal auf 4–10 Sole aufweiten) sind mit der Kategorien-Korrektur hinfällig — es gibt jetzt keine „Matrix aus drei Klassen" mehr, gegen die diese Optionen hätten abwägen müssen, sondern eine (die drei Pfadgebäude, jetzt bei 95) plus eine bewusst außerhalb stehende Ausnahme (bioFacility).
>
> **Ergänzung der Guard-Rail-Formulierung von G4 selbst** (Zeile 2543): *„Eine Errichtung [eines Pfadgebäudes] kostet 5–8 Sole Sparen, ein Level-Up 1–2. bioFacility/Agrardom ist als Pflicht-Ramp-Gate vor CC Lv2 von diesem Korridor ausdrücklich ausgenommen — es ist kein Pfadgebäude und steht nicht zur Abwägung."*

**G7 — durch die G4-Entscheidung entschärft; Formulierungskorrektur weiterhin empfohlen (b), keine Zahlenänderung.**

Das Beispiel „Ich mache 20 pro Sol, das kostet 95, das sind fünf Sole" verwendet weiterhin den literalen Wert 20 — weder Frischwert (18) noch Zyklusmittel (12,9) treffen ihn exakt, bleibt aber nach der G4-Entscheidung (2026-08-11) sogar treffender als zuvor: **95 ist jetzt der Preis aller drei Pfadgebäude**, nicht mehr nur der Cantina-Preis einer Drei-Klassen-Spreizung. Die Substanz von G7 (Preisklassen bleiben unterscheidbar) betrifft nach der Korrektur nur noch die Abgrenzung zwischen den Pfadgebäuden (95, alle drei identisch — keine Spreizung mehr nötig, da Preisgleichheit hier gewollt ist, s. G4-Audit oben) und der bioFacility-Ausnahme (70, kategorisch verschieden, kein Vergleich mit den Pfadgebäuden nötig). Das alte Rauschgrenzen-Argument (Zeile 2550, „der Unterschied zwischen einem 25er und einem 30er Gebäude verschwindet im Reparaturrauschen") betraf explizit den Fall enger, aber ungewollter Preisnähe zwischen tatsächlich zur Wahl stehenden Alternativen — bei 95 = 95 = 95 ist das kein Rauschen, sondern die beabsichtigte Aussage.

Kein Vorschlag, 20 durch 18 zu ersetzen: 95/18 ≈ 5,3 ist schlechteres Kopfrechnen als 95/20 = 4,75 ≈ 5 — und G7s Kernaussage ist gerade, dass Kopfrechnen einfach bleiben muss. Ein exakterer, aber unrunderer Wert würde die Illustration der behaupteten Eigenschaft selbst schwächen. Vorschlag stattdessen: das Beispiel als illustrativen Platzhalter kennzeichnen und die tatsächlichen Referenzwerte danebenstellen, damit niemand „20" künftig für eine reale, aktuelle Sockelzahl hält:

> **G7 (Vorschlag, nur Formulierung):** *Der Spieler muss im Kopf rechnen können. „Ich mache 20 pro Sol, das kostet 95, das sind fünf Sole." (Illustrativer Platzhalter für runde Kopfrechenzahlen, kein Sockel-Live-Wert — die tatsächlichen Referenzwerte sind Frischwert 18 Rg/Sol bzw. Zyklusmittel 12,9 Rg/Sol, siehe §13.7. 95 ist seit der G4-Entscheidung 2026-08-11 zudem der einheitliche Preis aller drei Pfadgebäude, nicht mehr nur einer von drei Klassen.)*

**G1 — hält (a), mit Abhängigkeitsvermerk.**

G1 beschreibt den Bestand, nicht die Einkommensrate, und „nie tödlich" ist in der Neuherleitung (Punkt 5, dritter Punkt) für den Sockel-only-Fall bereits ausdrücklich bestätigt. Ergänzung für die Dokumentation: Im Sockel-only-Spätspielfall (2,7 Rg/Sol netto nach Instandhaltung, s. G3-Rechnung oben) „schwingt" der Bestand nicht mehr um eine niedrige zweistellige Zahl, sondern kriecht nur noch langsam — G1 erbt damit dieselbe Hebel-aktiv-Prämisse, gegen die G2 und G6 bereits umformuliert wurden. Kein eigener Korrekturbedarf, nur derselbe Vorbehalt wie dort.

**G5 — unverändert (a), rein qualitative Pacing-Absicht ohne eigene Herleitung.**

Es gibt keine quantitative Herleitung für „2–4 Mal pro Run scheitern" im GDD, die die neue Baseline widerlegen oder bestätigen könnte — insofern nichts zu korrigieren. Zwei Anmerkungen zur Einordnung: Erstens ist G5 nicht folgenlos unquantifiziert — Zeile 2526 gibt ihr einen playtest-beobachtbaren Trigger („mehr als 4 Sole pro Run blockiert") und eine benannte Stellschraube (Startbestand, dann Errichtungspreise), ist also in der Praxis falsifizierbar. Zweitens **trägt** G5 in der bereits freigegebenen Neuherleitung bereits Gewicht als quantitatives Toleranzbudget: Punkt 5, erster Aufzählungspunkt, rechnet die verbleibende 15-%-Lücke bei reinem Pfad-A-Einsatz ausdrücklich „innerhalb der Toleranz, die G5 ohnehin vorsieht" gegen. Eine unquantifizierte Leitplanke trägt hier bereits eine bezifferte Schlussfolgerung — das ist kein Fehler, aber erwähnenswert, falls G5 später selbst beziffert werden soll.

**Für die Owner-Entscheidung (Audit):**

1. **G3** — keine Entscheidung nötig, Nachrechnung bestätigt den 5–8-Sole-Korridor (Randfall 7,4 Sole im Sockel-only-Spätspiel).
2. **G4** — entschieden UND implementiert (2026-08-11, Option 3, PR #243): Hangar 120→95 gesenkt, alle drei Pfadgebäude jetzt einheitlich 95 Rg; bioFacility bleibt bei 70 als dokumentierte Ausnahme (kein Pfadgebäude). Details und Begründung siehe Audit-Block oben. `config/buildings.php` + betroffener Test (`BuildResourceSinkTest`) angepasst, CHANGELOG-Eintrag vorhanden. **Weiterhin offen, bewusst nicht Teil dieser PR:** `app/Console/Commands/ResetPlayer.php` (Szenario-Kommentare referenzieren bereits vor diesem Fix einen veralteten 80-Rg-Wert, nicht 120 — eigenständige Altlast) und die 835-Rg-Zielkolonie-Bedarfskette (Punkt 1–4 der Neuherleitung oben, s. Folgepunkt unten). **Kollateraleffekt mitgezogen (Owner, 2026-08-11):** Hangar (`supply_cost=4`) war vor der Preisänderung durch den höheren Baupreis (120) teilweise gegen Sciencelab (`supply_cost=8`) und Bar (`supply_cost=6`) ausbalanciert — bei gleichem Baupreis (95) wäre Hangar sonst auf beiden Achsen der günstigste der drei Pfade gewesen. Provisorisch behoben, um weiter playtesten zu können: `supply_cost` aller drei Pfadgebäude auf **6** vereinheitlicht (Sciencelab 8→6, Hangar 4→6, Bar unverändert 6). Keine vollständige Neuherleitung — reine Testkonvenienz, bei Bedarf eigener, späterer Balance-Punkt. `php artisan game:sync-config` in der Dev-DB ausgeführt (dabei fiel auf: die Dev-DB war seit dem 08-03-Rebalancing insgesamt nicht gesynct — `decay_rate`/`build_cost` mehrerer weiterer Gebäude+Schiffe waren veraltet, mit synchronisiert, unabhängig von G4).
3. **G7** — Formulierungsvorschlag oben zur Freigabe, kein Spielwert betroffen.
4. **G1** — keine Entscheidung nötig, nur Kenntnisnahme des Hebel-aktiv-Vorbehalts (analog G2/G6).
5. **G5** — keine Entscheidung nötig, nur Kenntnisnahme, dass die Leitplanke bereits als Toleranzbudget in Punkt 5 verwendet wird, ohne selbst hergeleitet zu sein.

**Rückwirkungsprüfung der G4-Entscheidung (2026-08-11) auf G3/G6/G2:**

- **G3 — nicht betroffen.** Die Level-Down-Aufholrechnung (oben, „G3 — verifiziert, hält") hängt an `decay_rate × Sole_bis_Level_Down = max_status_points` (20 Rg + 20 AP je volles Level) — keine Errichtungspreis-Größe fließt dort ein. Einzige indirekte Berührung: Zeile 2528 nennt Errichtungspreise als Stellschraube für G5 (nicht G3), falls „mehr als 4 Sole pro Run an Regolith blockiert" beobachtet wird — die G5-Sensitivität verschiebt sich geringfügig (ein Pfadgebäude ist jetzt nie teurer als 95 statt bis zu 120), das schwächt eher ein mögliches G5-Risiko am oberen Rand, verschärft keins.
- **G6/G2 — 835-Rg-Zielkolonie-Bedarfskette leicht verschoben, nicht neu hergeleitet.** Die Neuherleitung oben (Punkt 1–4) rechnet mit „835 Rg (Errichtungen)" als Summe über den Gebäude-/Level-Katalog der Zielkolonie. Diese Summe enthält die Hangar-Errichtung(en) zum alten Preis (120); mit 95 sinkt sie um 25 Rg je Hangar-Instanz in der Zielkolonie. Die genaue Instanzzahl pro Preisklasse ist aus dem GDD nicht rekonstruierbar (dieselbe Lücke, die Zeile 2741 für die Σ-`decay_rate`-Tabelle bereits dokumentiert) — die Richtung ist eindeutig (Bedarf sinkt leicht, Sockel-Anteil 57,5 % steigt leicht, reife Hebelhöhe 14,1 Rg/Sol sinkt leicht), die neue Größenordnung nicht. **Offener Folgepunkt, nicht Teil dieser Freigabe:** 835/1.795/57,5 %/763/14,1 in der Neuherleitung sowie die Σ-`decay_rate`-Anteilstabelle bei G2 (falls sie Hangar-Errichtungskosten referenziert, was sie aktuell nicht direkt tut — sie rechnet über `decay_rate`, nicht über `build_cost`) bei Gelegenheit mit dem 95er-Hangar-Preis neu rechnen. Da die Verschiebung strukturell begünstigend ist (kleinerer Bedarf, größerer Sockel-Anteil), ist keine Dringlichkeit gegeben.
- **Sol-1–4-Rampe-Tabelle (oben, „Proben")** — bereits im Fließtext direkt nach der Tabelle korrigiert (s. o.): Hangar-Pfad läuft jetzt identisch zum dort gezeigten Cantina-Beispiel (95, Sol 4 bei 40 Rg statt vormals ~15 Rg beim 120er-Pfad).

---

#### Korrektur durch die Knappheitsordnung (Owner, 2026-08-02)

Der Vorschlag enthielt ursprünglich eine Preisänderung `bar.base_prices` auf Rg 40 / Or 30 / Wk 120, begründet damit, die Preise stünden „andersherum als die Knappheit" — die Kolonie überproduziere Organika und leide an Regolith-Mangel.

**Das ist zurückgewiesen.** Die Beobachtung stimmt für den Ist-Zustand, aber die Knappheitsordnung aus §3 ist die Vorgabe: `Regolith < Organika < Werkstoffe`. Die heutigen Preise (Rg 30 / Or 50 / Wk 60) haben die **richtige Reihenfolge**; der Vorschlag hätte sie vertauscht.

**Was bleibt:** Der Abstand zwischen Organika und Werkstoffen ist zu klein für „deutlich knapper als Organika". Eine Anpassung der Handelpreise, die die Knappheitsordnung (§3) respektiert. Genaue Preiswerte und die Nexus-Direktimport-Preise: siehe `docs/game-reference.md#handelpreise` und `config/game.php`.

**Zwei Folgen, die noch offen sind:**

> **⚠️ Der Pfad-C-Hebel muss neu gedacht werden — Vorschlag siehe §4b.** Der vorgeschlagene Organika→Regolith-Tausch setzte voraus, dass Organika der Überschuss ist. Nach der Knappheitsordnung ist es umgekehrt — man würde das knappere Gut gegen das häufigere tauschen. Der Credits→Regolith-Ankauf ist bei 25 Cr/Rg zwar billiger als zuvor gerechnet (12 Rg/Sol ≈ 300 Cr/Sol), trägt aber immer noch keine Ökonomie. **Pfad C braucht keinen großen Regolith-Hebel:** Wenn Regolith laut §3 „verfügbar sein soll", ist es nicht der Engpass, gegen den die Pfade sich beweisen müssen, und seit §4c läuft das Regolith-Wachstum ohnehin über die pfad-unabhängige Harvester-Zweitinstanz. Pfad C's Beitrag liegt stattdessen bei Credits — Design „Pfad-C-Hebel: von Regolith zu Credits" in **§4b**, freigegeben 2026-08-05 (mit Korrektur 2026-08-06, siehe dort).

> **⚠️ Agrardom-Kurve: das obere Ende prüfen, nicht die Mechanik.** Der Organika-Verbrauch skaliert über `food_need = intdiv(usedSupply, 4)` mit der **Ausbautiefe** der Kolonie — es ist also ein Rennen zwischen Agrardom-Level und Koloniewachstum, dazu der einmalige Missionsproviant und Event-Kosten. Das ist genau der Mechanismus, den die Knappheitsordnung verlangt. Genaue Beispielwerte und die Produktionskurven: siehe `docs/game-reference.md#ressourcenverbrauch` und `config/game.php`.
> | 126 (Cap der Zielkolonie) | 31 | Lv3 grenzwertig, Lv4 komfortabel |
>
> Wer die Kolonie in die Tiefe baut, ohne den Agrardom nachzuziehen, gerät in den Mangel — so gewollt. **Zu prüfen ist deshalb nur das obere Ende:** Lv4/Lv5 liefern 41/48 gegen einen Bedarf, der bei der Zielkolonie nicht über ~31 steigt. Ab Lv4 ist das Rennen entschieden und Organika hört auf, eine Sorge zu sein. Ob die Kurve dort flacher auslaufen sollte — oder ob Missionen und Events genug Zusatzlast erzeugen, um die Marge dünn zu halten —, gehört in dieselbe Herleitung wie der Regolith-Satz.

#### Auslieferung: alles in einem Zug

Der Satz ist ein zusammenhängendes System. **Der neue Sockel ohne die neuen Baukosten ergibt eine triviale Wirtschaft, die neuen Baukosten ohne den Sockel eine unspielbare.** Alles oben gehört in einen PR — zusammen mit `harvester.max_level` in `config/buildings.php` (sonst setzt der nächste `game:sync-config`-Lauf die Owner-Entscheidung still zurück, Anhang B) und der Erschöpfungskurve aus §4c, die den Sockel erst zu einem Durchschnittswert macht.

#### Wo dieser Satz unsicher ist

- **Die 60-%-Regel für die Hebel-Reife (12 von 20) ist eine Setzung.** Sie fällt aus zwei unabhängigen Richtungen auf dieselbe Zahl, ist aber die erste, die im Playtest zu prüfen wäre. Metrik: Anteil des Regolith-Zuflusses aus dem Hebel je Pfad, Zielband 30–40 %.
- **Die Reibungspauschale von 15 % (240 Rg) ist geraten.** Sie deckt Level-Down-Wiederaufbau, Harvester-Verlegungen und Fehlkäufe und ist direkt aus dem Bot-Report ablesbar.
- **Die Supply-Achse ist bewusst nicht mitbewegt.** Die `supply_cost`-Werte sind gegen eine Wirtschaft kalibriert, in der Regolith knapper war. Wird Bauen leichter, wird Supply relativ zum bindenderen Limiter — was §6 entspricht, aber die Zielkolonie gegen den erreichbaren Cap gegenzuprüfen verlangt. **Das ist der nächste unconstrained durchzurechnende Zahlensatz.**
- **`max_level = NULL` bei sieben Gebäuden** (Sciencelab, Temple, Agrardom, Hangar, Krankenstation, Monument, Cantina) ist unangetastet. Ein unbegrenztes Hochleveln widerspricht dem „kleine Kolonie"-Prinzip; gehört zur Supply-Runde.

---

#### Nachtrag 2026-08-12 — Phase-1-Pacing auf Sol-15-20 neu hergeleitet

> **Status: Überholt durch den Nachtrag 2026-08-13 unten (Bedarfskette war um 35 Rg unterzählt) — bleibt stehen, nicht überschrieben, siehe dortige Begründung.** Der hier vorgeschlagene Zielwert 300 ist durch 340 ersetzt, umgesetzt (`OnboardingService::seedResources()`).

**Ausgangsbefund (PlaytestBot, PR #244, mehrere Seeds/Reruns):** Phase 1 (`RunProgressService::checkPhase1Completion()` — CC Lv3 + mindestens 2 Nicht-CC-Gebäude auf Level ≥2 + 3 aktive Berater) wird aktuell frühestens Sol 55–65 abgeschlossen, nie früher. Beste bekannte Timeline (Seed 4242): CC Lv3 bereits Sol 1, aber vom zweiten Berater (Sol 1) zum dritten (Sol 55) vergehen 54 Sole — obwohl CC-Ausbau selbst kein Engpass ist. Owner-Ziel: Regelfall Sol 15–20, harte Obergrenze Sol 30 (eigener, paralleler Auftrag — `docs/superpowers/specs/2026-08-12-phase1-sol30-deadline-design.md`).

**Wichtige Vorklärung — welche Bedingung tatsächlich gilt.** `RunProgressService` prüft `building_id != CommandCenter` bei Level ≥2, nicht „Produktionsgebäude" — Wohnhabitat zählt also mit. Die Herleitung unten rechnet konsequent gegen das Code-Verhalten (Bedingung 2 lässt sich am günstigsten mit Agrardom Lv2 + Wohnhabitat Lv2 erfüllen, nicht zwingend mit einem der drei Pfadgebäude). **Diskrepanz zur GDD-Formulierung in §15 („mindestens 2 Produktionsgebäude") — bewusst offen gelassen für eine eigene Owner-Entscheidung:** entweder §15 auf den Code-Wortlaut angleichen, oder das Code-Verhalten auf „Produktionsgebäude" einschränken. **Würde Letzteres umgesetzt, bricht die gesamte Rechnung unten** — der Harvester hat `max_level = 1` (nie level-fähig), bioFacility wäre dann das einzige verbleibende Nicht-CC-Produktionsgebäude, das überhaupt Level 2 erreichen kann, und Bedingung 2 würde faktisch an ein zweites Pfadgebäude gekoppelt (mehr Regolith, nicht weniger). Diese Rechnung gilt also nur für die aktuelle, tatsächliche Code-Bedingung.

**Bedarfskette für die Phase-1-Teilmenge (nicht die volle Zielkolonie aus der Neuherleitung oben) — günstigster Pfad:**

| Posten | Rg |
|---|---|
| CC Lv1→Lv2→Lv3 (2×30 + 3×30) | 150 |
| bioFacility Errichtung + →Lv2 (70 + 25) | 95 |
| Wohnhabitat Errichtung + →Lv2 (40 + 25) | 65 |
| 2 Pfadgebäude Errichtung (95 + 95, Lv1 reicht — schaltet Slot 2 und Slot 3 frei) | 190 |
| **Errichtung/Level-Up-Summe** | **500** |
| Instandhaltung über ~18 Sole (Σ `decay_rate` ramp 1,20 → 3,60 je nach Bauzustand, Fläche gerechnet) | ~50 |
| **Gesamtbedarf** | **≈ 550** |

Die 2 Pfadgebäude sind notwendig, nicht optional: Slot 2 (2. Berater) öffnet mit dem 1., Slot 3 (3. Berater = Bedingung 3) mit dem 2. Pfadgebäude (§13 „Slot-System"). Credits sind dabei **nicht** der Engpass — 3 Anwerbungen (Baumeister 300, günstigste zwei der übrigen drei Typen z. B. Konsul 350 + Analytiker 400 = 1.050 Cr) sind aus dem Startkapital von 3.000 Cr trivial finanzierbar, unabhängig vom laufenden Berater-Unterhalt. **Das widerlegt die ursprüngliche Anhang-A.4-Vermutung** „Berater-Hire-Credits reichen nicht annähernd schnell genug nach" — der Unterhaltskollaps bei 2–3 Beratern auf Rang 2/3 (§18.4, 07-19/07-20) ist ein reales, aber späteres und separates Problem, nicht die Ursache der Sol-55-65-Verzögerung.

**Warum Regolith trotzdem der Flaschenhals ist — zwei getrennte Grenzen, die gegeneinander geprüft werden müssen.** Der Harvester hat sowohl eine **Raten**- als auch eine **Mengen**-Grenze:

1. **Ratengrenze:** Die Erschöpfungskurve (`Ertrag = Frischwert × (0,5 + 0,5 × Restvorkommen/resource_max)`) liefert nie mehr als den Frischwert (18 Rg/Sol auf `regolith_normal`) — auch bei vollem Vorkommen. Solange das Tile nicht knapp wird, extrahiert ein Harvester im Schnitt ~17 Rg/Sol (nahe Frischwert, da die Restvorkommen-Quote lange nahe 1 bleibt).
2. **Mengengrenze:** Kumulativ kann ein einzelnes Tile nie mehr als `resource_max` liefern — unabhängig von der Rate. `regolith_normal` liegt aktuell bei **300**.

Sol-1-Start-Harvester produziert 0 Rg bis zur ersten Verlegung (Owner-bestätigte Absicht, kein Bug) — die Bedarfsdeckung beginnt effektiv erst mit dem ersten produktiven Tile.

**Verfügbares Regolith gegen die reine Errichtungs-/Level-Up-Summe (500, ohne die Instandhaltung ein zweites Mal abzuziehen — die steckt bereits in der Nettobetrachtung):**

```
Verfügbar(N) = Startbestand + Σ(Harvester-Ertrag, Sol 2…N) − Σ(Reparatur, Sol 1…N)
             ≈ Startbestand + 17×(N−1) − 2,94×N      (Ø-Reparatur über das Fenster, s. Bedarfstabelle oben)
```

Bei heutigem Startbestand (200) und aufgelöst nach `Verfügbar(N) = 500`: `N ≈ 22,5` — knapp am oberen Rand des bisherigen „15–25"-Richtwerts aus §18.4, aber deutlich über dem neuen Sol-15-20-Ziel. Die beobachteten Sol 55–65 sind damit **nicht** allein durch diese Idealrechnung erklärt: Ein erheblicher Teil der zusätzlichen Verzögerung liegt an Ausführungsfriktion, die dieses Modell nicht abbildet (Reihenfolgezwang — bioFacility vor CC Lv2, zweites Pfadgebäude erst ab CC Lv3 baubar, s. „Gate-Logik" §13 „Slot-System" —, Erkundung/Verlegung, Bot-Suboptimalität, teils bereits behobene Bugs wie der Ring-Erkundungsdeadlock vom 2026-08-11). Das Modell liefert deshalb eine **untere Schranke** (Floor), keinen Erwartungswert — der reale Abschluss-Sol liegt aufgrund dieser zusätzlichen Reibung typischerweise darüber.

**Ein Hebel, kein zweiter — Startbestand, ohne Eingriff in `resource_max`.** Löst man dieselbe Gleichung nach dem nötigen Startbestand für `N = 15` auf: `Startbestand + 17×14 − 2,94×15 = 500 → Startbestand ≈ 306`. Gerundet:

| Hebel | heute | Vorschlag | Wirkung |
|---|---|---|---|
| Startbestand Regolith | 200 | **300** | verschiebt den Floor von ≈Sol 22,5 auf ≈**Sol 15,4** — an die untere Kante des Zielkorridors, mit Raum für die oben beschriebene reale Ausführungsfriktion, ohne dass der Regelfall dadurch schon über Sol 20 hinausgeschoben wird |

**`resource_max['regolith_normal']` bewusst NICHT angehoben — geprüft und verworfen, nicht übersehen.** Mit Startbestand 300 liegt die kumulierte Tile-Extraktion beim Floor-Sol (≈15,4) bei `17×14,4 ≈ 245 Rg` — deutlich unter der bestehenden 300er-Mengengrenze. Die Mengengrenze bindet in diesem Fenster also nicht, ein zweiter Hebel an dieser Stelle kostet Kollateralschaden ohne Nutzen: §4c legt die Umzugsgebühr bewusst so aus, dass ein Harvester **4–6 Mal pro Run** verlegt wird („Der eigentliche Regler ist die Umzugsgebühr, nicht die Kurve"); eine Standzeit-Verlängerung von ~22 auf ~27 Sole hätte das Richtung „seltener als gewollt" verschoben — und würde sich bei einer künftigen Konstant-Yield-Umstellung (Frischwert als harter Cutoff statt Rampe, §4c-Spec 2026-08-10) noch stärker auswirken (Standzeit dann `resource_max / Frischwert`, bei 400/18 ≈ 22 statt heute effektiv ~17 unter Rampe — der Umzugstakt würde spürbar seltener als die im Spec dokumentierten ~17 Sole bei unverändertem `resource_max`). Ein Hebel, der die Mengengrenze anhebt, ohne dass sie im Zielfenster überhaupt bindet, ist reiner Kollateralschaden gegen eine unabhängige Designentscheidung — deshalb hier verworfen.

**Einordnung des Ergebnisses.** Der Floor von ≈Sol 15,4 ist eine untere Schranke unter Idealbedingungen (kontinuierlicher Bau ohne Leerlauf, keine Fehlkäufe, keine Erkundungs-/Verlege-Verzögerung, kein Reihenfolgezwang-Verlust). Realistisch — mit dem oben beschriebenen Reihenfolgezwang und normaler Spielfriktion — liegt der Regelfall eher am oberen Rand des 15–20-Korridors oder knapp darüber, nicht exakt beim Floor-Wert. Genau dieses Verhältnis (Floor nahe der unteren Kante, damit Streuung nach oben in den Korridor fällt statt ihn sofort zu verlassen) ist beabsichtigt — ein Floor bei Sol 18–19 (wie eine frühere Fassung dieser Rechnung fälschlich auswies, s. u.) hätte keinen Spielraum für Streuung nach oben gelassen und den Regelfall strukturell über Sol 20 gedrückt.

> **Korrektur gegenüber einer ersten Fassung dieser Rechnung:** Eine frühere Version zählte die Instandhaltung sowohl in der Bedarfssumme (550 = 500 + ~50 Reparatur) als auch ein zweites Mal in der Verfügbarkeits-Formel (`Verfügbar = Start + Ertrag − Reparatur`) — Doppelzählung, die die Lücke um ~50 Rg zu groß und den Floor um ~7 Sole zu spät auswies (fälschlich ≈Sol 22 statt korrekt ≈Sol 15,4 bei Startbestand 300, bzw. ≈Sol 22,5 statt der irrtümlich behaupteten Sol 25–26 bei Startbestand 200). Diese Version rechnet konsistent nur mit einer Instandhaltungs-Erfassung (in der Verfügbarkeits-Formel, gegen die reine Errichtungs-/Level-Up-Summe 500). Auf dieser korrigierten Basis erwies sich der zunächst vorgeschlagene zweite Hebel (`resource_max`-Anhebung) als unnötig — s. u.

**`regolith_poor`/`regolith_rich` bewusst unverändert.** Ein Run, der auf einem `poor`-Tile startet (Frischwert 12, `resource_max` 160, ~25 % Häufigkeit — die häufigste Einzelklasse), erreicht dieselbe Rechnung nicht: Kumulative Extraktion ist dort bereits bei `12×(N−1) = 160` erschöpft, also nach ≈14 produktiven Solen (≈Sol 14–15 im Run) — eine frühe Zwangsverlegung ist eingebaut. **Das ist die gewollte Variabilität** (G5, „2–4 Mal pro Run an Regolith scheitern"), keine zu behebende Lücke. Gegenprobe, Worst-Case-Pfad (Start auf `poor`, Zwangsverlegung auf `regolith_normal` bei Sol 14, 1 Transit-Sol ohne Ertrag): `Verfügbar(14, poor) ≈ 300 + 12×13 − 2,0×14 ≈ 300 + 156 − 28 = 428` (niedrigere Ø-Reparatur hier bewusst angesetzt, da bei Sol 14 typischerweise noch nicht alle Gebäudetypen der Bedarfskette stehen). Rest-Bedarf `500 − 428 = 72` Rg, bei ~15 Rg/Sol netto auf dem neuen Tile (Frischwert 18 minus Reparatur, nach dem Transit-Sol) ≈ 5 weitere Sole → Abschluss ≈ Sol 14 + 1 (Transit) + 5 = **Sol 20**. Am oberen Rand des 15–20-Zielfensters, klar unter der Sol-30-Grenze — als bewusst akzeptierte, etwas langsamere Variante für den unglücklicheren Start, nicht als Ausreißer außerhalb des Zielkorridors. `resource_max['regolith_poor']` künstlich anzuheben würde diese Varianz gerade wegnehmen, die G1/G5 ausdrücklich wollen — deshalb hier **kein** Änderungsvorschlag.

**Bewusst nicht angefasst — und warum:**

- **`resource_max['regolith_normal']` unverändert (erwogen, dann verworfen — s. o.).** Die Mengengrenze bindet im Zielfenster nicht (245 von 300 Rg kumulierter Extraktion beim Floor-Sol) — eine Anhebung hätte nur Kollateralschaden gegen die §4c-Umzugstakt-Vorgabe (4–6 Verlegungen/Run) gekostet, ohne die Sol-15-20-Erreichbarkeit zu verbessern.
- **`fresh_yield` (Harvester-Frischwert) unverändert.** Solange das Tile nicht knapp ist (s. o.), liefert eine Rate-Erhöhung nur schnelleren Vorlauf auf dieselbe `resource_max`-Wand, nicht mehr Gesamtmenge — und würde zusätzlich die Standzeit verkürzen (mehr, nicht weniger, ungeplante Verlegungen), das Gegenteil dessen, was hier gebraucht wird.
- **Konstant-Yield-Umstellung (§4c-Spec vom 2026-08-10) ist NICHT Teil dieses Hebels.** Sie ist Owner-approved, aber **nicht implementiert** — `GameTick::harvesterYield()` läuft weiterhin mit der Rampenformel, die GDD-§4c-Formelzeile ist unverändert. Diese Neuherleitung rechnet bewusst gegen die **live laufende** Rampenformel, nicht gegen die noch nicht gebaute konstante Variante. Beide Formeln liefern in diesem Fenster ähnliche Werte (die Rampe liegt nahe am Frischwert, solange das Vorkommen nicht knapp wird — das ist hier über weite Strecken des Sol-1–20-Fensters der Fall) — der hier vorgeschlagene Startbestand-Wert ist gegen **beide** Formeln robust, kein Nachrechnen nötig, falls die Konstant-Yield-Umstellung später unabhängig davon landet. Da `resource_max` hier unverändert bleibt, ändert sich an der bestehenden Konstant-Yield-Standzeit-Rechnung aus dem Spec (300/18 ≈ 17 Sole) ebenfalls nichts.
- **Pfadgebäude-Baukosten (95 Rg) unverändert.** Der G4-Audit vom 2026-08-11 hat diesen Wert bereits gegen den 5–8-Sole-Korridor **pro Einzelgebäude** kalibriert; das hier gefundene Problem ist kumulativ (mehrere Bauprojekte konkurrieren um denselben frühen Regolith-Strom), keine Einzelpreis-Fehlkalibrierung. Eine weitere Senkung würde den G4-Korridor erneut aufreißen, ohne die eigentliche Ursache zu treffen.
- **CC-Ausbaukosten (`cc_upgrade_regolith_per_level`, ×30) unverändert.** Bestätigt „kein Engpass" durch den Ausgangsbefund selbst (Sol 1 bereits fertig in der besten bekannten Timeline).
- **`config/advisors.php` (Hire-Credits) unverändert.** Siehe oben — Credits sind für die Ersteinstellung nicht bindend; eine Senkung würde ein Problem lösen, das hier nicht vorliegt, und stattdessen unbeabsichtigt den späteren Rang-2/3-Unterhaltsdruck (§18.4) abschwächen, der bewusst kalibriert ist.
- **AP-Achse unverändert — Einschränkung: gilt nachweislich nur für den `regolith_normal`-Referenzpfad ohne Zwangsverlegung.** Nominal-Bedarf für CC Lv1→3 + bioFacility Lv1→2 + Wohnhabitat Lv1→2 + 2 Pfadgebäude Lv1 ≈ 43+5+6+8+11 ≈ 73 AP (§13.6-Kostentabelle) gegen einen Pool, der ab Sol 1 bei ~14 AP/Sol beginnt (Basis 12 + Baumeister Rang 1) und mit Slot 2/3 auf ~16–19 AP/Sol wächst — über 15 Sole kumuliert weit mehr als 73 AP verfügbar, auch nach Abzug der Instandhaltung. Für den `poor`-Zweig (s. o.) ist die Aussage **nicht** ungeprüft übertragbar: dort kommt Erkundungs-AP (ring-gestaffelt 1/2/3 AP, §13.6 „19 Zonen-Tiles ≈ 33 AP") für ein Verlege-Ziel sowie Verlege-AP (2 AP/Hex, §4c) hinzu, bevor die Zwangsverlegung überhaupt ausgeführt werden kann — genau das Fehlen eines erkundeten Ziels blockierte laut CHANGELOG 2026-08-11 einen Bot-Lauf real bis Sol 20. Diese zusätzliche AP-Last ist hier nicht quantifiziert; für den `poor`-Zweig gilt deshalb nur die schwächere Aussage „AP ist auf dem `regolith_normal`-Pfad nicht bindend", nicht pauschal für alle Startbedingungen.

**Nebenbefund — Asymmetrie zwischen den Pfadgebäuden bleibt bestehen, nicht Teil dieses Fixes.** Die G4-Preisgleichheit (95 Rg für alle drei) gilt nur für Regolith. In AP-Kosten sind Analytik-Labor und Hangar „Groß" (`base_ap=22`, Lv1 = 11 AP), Cantina ist „Mittel" (`base_ap=16`, Lv1 = 8 AP) — bei gleichem Regolith-Preis ist die Cantina AP-günstiger. Da AP hier nicht die bindende Achse ist (s. o.), ändert das nichts an der Sol-15-20-Erreichbarkeit, ist aber eine Inkonsistenz in der „Paritäts-Anforderung" (§4b), die bei Gelegenheit (nicht hier) zu prüfen ist.

**Erforderliche Folgearbeiten bei Umsetzung (nicht Teil dieses GDD-Nachtrags, für den Implementierungs-Task):**

1. Startbestand Regolith 200 → 300 — hartcodiert in `OnboardingService::setupNewPlayer()`, außerdem in `app/Console/Commands/ResetPlayer.php`-Szenarien und `data/sql/testdata.sqlite.sql` nachzuziehen (siehe game-designer-Rollenpflicht „Szenario-Pflege"). Kein weiterer Config-Key betroffen — `resource_max`/`fresh_yield`/Errichtungspreise/Berater-Kosten bleiben unverändert (s. o.).
2. §15 „Startzustand" (aktuell „200 Regolith") und §15-Bedingungstext („2 Produktionsgebäude" vs. Code-Verhalten, s. Vorklärung oben) auf Konsistenz prüfen — Owner-Entscheidung zur Bedingung-2-Formulierung ist ein eigener, offener Punkt.
3. TDD-Pflicht (CLAUDE.md): neue/angepasste Tests für `HarvesterSol1BootstrapTest` (falls dort der Startbestand referenziert wird), ggf. `PlaytestBotTest`-Erwartungen.
4. Nach Umsetzung: erneuter PlaytestBot-Lauf (mehrere Seeds) zur empirischen Bestätigung — diese Herleitung ist eine Floor-Rechnung, kein Simulationsersatz; der Sol-55-65-Ist-Wert enthielt nachweislich Ausführungsfriktion (Reihenfolgezwang, Erkundung/Verlegung, teils behobene Bugs), die diese Rechnung explizit nicht vollständig abbildet. Sollte der reale Regelfall trotz des Fixes spürbar über Sol 20 bleiben, ist die Erkundungs-/Verlege-AP-Last aus dem `poor`-Zweig (s. o.) der nächstplausible, hier noch nicht quantifizierte Kandidat — nicht `resource_max` erneut.
5. Falls die Konstant-Yield-Umstellung (§4c-Spec 2026-08-10) unabhängig davon umgesetzt wird: keine Rückwirkung auf diesen Nachtrag nötig, da `resource_max` hier unverändert bleibt (s. „Bewusst nicht angefasst").

---

#### Nachtrag 2026-08-13 — Zweite Iteration: Bedarfskette war um 100 Rg unterzählt, nicht der Floor „zu optimistisch"

> **Status: Umgesetzt + empirisch bestätigt (2026-08-13).** Löst die verschärfte Owner-Vorgabe vom 2026-08-13 (Phase 2 verlässlich unter Sol 25, bei sehr gutem Run unter Sol 20) und korrigiert den Nachtrag vom 2026-08-12, der stehen bleibt (nicht überschrieben) — die dortige Herleitungsmethode war richtig, die Bedarfskette darin war es nicht. Beide Bot-Fixes aus „Erforderliche Folgearbeiten" Punkt 2 sind umgesetzt. Erneuter PlaytestBot-Lauf (3 Seeds, Startbestand 340) bestätigt: `phase2_start_sol` = 20–22 durchgehend — trifft den vorhergesagten Floor (≈15,1) plus die erwartete Ausführungsfriktion, klar innerhalb Sol 25, an der Grenze zum Sol-20-Exzellenzziel.

**Ausgangsbefund (Startbestand 300, umgesetzt + PlaytestBot-Läufe am 2026-08-13, Seed 4242 repräsentativ):** Zwei echte PlaytestBot-Bugs wurden im Vorfeld gefunden und gefixt (bioFacility-Prioritäts-Loop, fehlender Rg-Puffer in `placeCandidate()`). Nach beiden Fixes: CC Lv3 weiterhin Sol 1 (kein Engpass), aber der **2. Berater** (= 1. Pfadgebäude nutzbar) kommt erst **Sol 23** — nicht ≈Sol 15,4 wie der Floor aus dem 08-12-Nachtrag vorhersagte. Regolith oszilliert die ganze Zeit unter ~90, obwohl die Formel ~14 Rg/Sol Netto-Zufluss ansetzt. AP ist durchgehend nicht der Engpass (`ap_unspent` 13–15). Phase 2 wird in keinem der 3 Testläufe erreicht.

**Ursache 1 (Hauptfehler, Bedarfskette): Errichtung bringt ein Gebäude auf Level 0, nicht Level 1 — der Sprung 0→1 kostet einen weiteren, vollen Level-Up.** Bestätigt im Code:

```php
// ColonyController::placeBuilding()
DB::table('colony_buildings')->insert([
    ...
    'level' => 0,
    'status_points' => $building->max_status_points ?? 20,
    'ap_spend' => 1,
    ...
]);
```

`level: 0` — ein frisch platziertes Gebäude ist **nicht** auf Level 1. Erst ein separater `investBuilding()`-Aufruf (AP-Invests bis `ap_spend >= ap_for_levelup`) hebt es auf Level 1, und **dieser Sprung kostet zusätzlich `LEVELUP_REGOLITH_FLAT = 25` Rg** — dieselbe Flatrate wie jeder andere Level-Up, dokumentiert im eigenen Code-Kommentar zum Hangar: *„Level-up Rg cost is the flat rate (25), same as every other non-CC building."* Das gilt für **jeden** Level-Sprung eines nicht-CC-Gebäudes, nicht nur für „echte" Ausbaustufen — 0→1 ist kein Sonderfall, der im `build_cost` schon inbegriffen wäre.

Die Bedarfskette vom 08-12-Nachtrag hat das für die zwei Pfadgebäude komplett übersehen (Zeile „Errichtung 95+95, Lv1 reicht" — implizit als „Errichtung = Lv1", ohne den separaten 25-Rg-Sprung) **und** für bioFacility den zweiten nötigen Sprung (0→1) vergessen (die Tabelle nannte „Errichtung + →Lv2 (70 + 25)" — das deckt nur 1→2, nicht 0→1).

**Korrektur an dieser Stelle — Wohnhabitat ist KEIN Bau-Posten, sondern Sol-1-Bootstrap.** `OnboardingService::seedStartingBuilding()` seedet CommandCenter, Harvester **und** HousingComplex bereits bei Kolonie-Erstellung auf `level: 1` (16/20 SP, „80 % beschädigt, aber level 1"). Das im 08-12-Nachtrag geführte Wohnhabitat-„Errichtung 40 + →Lv2 25"-Posten ist damit doppelt falsch: Die 40 Rg „Errichtung" fallen nie an (das Gebäude existiert schon), und der einzig reale Kostenpunkt ist der **eine** verbleibende Sprung Lv1→2 (25 Rg) — nicht Lv0→1 wie bei bioFacility/den Pfadgebäuden, die der Spieler tatsächlich neu baut. Nur CC (ebenfalls Sol-1-Bootstrap auf Lv1) und Harvester (kein Rg-Repair-Kostenfaktor) sind vorbestehend; bioFacility und alle drei Pfadgebäude müssen vom Spieler neu errichtet werden und starten dabei bei Level 0. Korrigierte Bedarfskette:

| Posten | Rg (08-12, fehlerhaft) | Rg (korrigiert) | Differenz |
|---|---|---|---|
| CC Lv1→Lv2→Lv3 (Sol-1-Bootstrap auf Lv1, eigene CC-Formel) | 150 | 150 | 0 |
| bioFacility (neu gebaut): Errichtung 70 + Lv0→1 (25) + Lv1→2 (25) | 95 | **120** | +25 |
| Wohnhabitat (Sol-1-Bootstrap auf Lv1, nur noch Lv1→2 fällig): 25 | 65 | **25** | −40 |
| Pfadgebäude 1 (neu gebaut): Errichtung 95 + Lv0→1 (25) | 95 | **120** | +25 |
| Pfadgebäude 2 (neu gebaut): Errichtung 95 + Lv0→1 (25) | 95 | **120** | +25 |
| **Summe** | **500** | **535** | **+35** |

CC ist unbetroffen — `levelupRegolithFor()` behandelt die CC-ID gesondert (`targetLevel × cc_upgrade_regolith_per_level`, keine Flatrate, kein 0-Level-Sonderfall in der bestehenden Formel), und der empirische Sol-0/1-Verlauf (300 → 240 → 150) bestätigt exakt 150 Rg für Lv1→3, keine Abweichung — konsistent mit CC als Sol-1-Bootstrap-Gebäude, nicht Neubau.

> **Zwischenschritt, verworfen — festgehalten, damit er sich nicht wiederholt.** Eine erste Fassung dieses Nachtrags rechnete Wohnhabitat fälschlich wie bioFacility/die Pfadgebäude als Neubau (Errichtung 40 + zwei Sprünge = 90 Rg) und kam auf eine Summe von 600 Rg statt 535 — das hätte den empfohlenen Startbestand unnötig auf ≈400 statt ≈300–350 getrieben. Der Fehler: die Prüfung, welche Gebäude tatsächlich vom Spieler gebaut werden müssen und welche bereits als Sol-1-Bootstrap existieren, wurde nicht gegen `OnboardingService::seedStartingBuilding()` verifiziert, bevor die Tabelle geschrieben wurde. Reviewer-Hinweis, der den Fehler aufgedeckt hat, bevor er in die Empfehlung einging — hier dokumentiert, um das gleiche Muster (Bedarfsposten ungeprüft aus der falschen Analogie übernehmen) beim nächsten Mal zu vermeiden.

**Ursache 2 (Nebenbefund, nicht Balance — Bot-Defekt, hier nur benannt, nicht behoben):** `BotStrategy::cheapestPendingPathBuildingCost()` bestimmt den zu reservierenden Betrag als `min(build_cost)` über alle noch **unplatzierten** (`tile_x IS NULL`) Pfadgebäude — das liefert **95**, solange irgendeines der drei noch nicht steht, unabhängig davon, ob ein bereits platziertes Pfadgebäude eigentlich nur noch 25 Rg für seinen Lv0→1-Sprung braucht. Der Puffer in `productionInvestCandidate()`/`researchCandidate()` gibt `null` zurück, solange `regolith < 95` — er reserviert also weiter für ein Gebäude, das der Bot als nächstes gar nicht bauen muss, und blockiert dabei genau den günstigen 25-Rg-Schritt, der tatsächlich den nächsten Beraterslot freischaltet. Die Rohdaten bestätigen dieses Muster (nicht das gegenteilige): Sol 4 (−148 Rg) ≈ bioFacility 70 + ein Pfadgebäude 95 (beide auf Lv0 platziert, Puffer erlaubte beides da Rg ≥95 war); Sol 4→14 wächst Regolith danach nur um ~4,9/Sol netto trotz ~17 Rg/Sol brutto — der 25-Rg-Levelup des bereits stehenden Pfadgebäudes feuert nicht, weil der Puffer weiter auf 95 wartet; Sol 15 (−83 Rg) ≈ ein weiteres 95-Rg-Placement (Puffer erlaubte es, sobald wieder ≥95 erreicht war). Berater 2 kommt dadurch erst Sol 23, weil das Pfadgebäude ~18 Sole lang auf Level 0 sitzen bleibt, obwohl nur 25 (nicht 95) Rg fehlen. **Zwei konkrete Fundstellen für den Dev-Follow-up:** (1) der Puffer muss die Kosten des **nächsten Beraterslots** reservieren, nicht die eines beliebigen unplatzierten Pfadgebäudes — für ein bereits platziertes, aber ungeleveltes Pfadgebäude sind das 25 Rg (Lv0→1), für ein noch nicht platziertes 95 + 25; reserviert wird das Minimum über die für die verbleibenden Berater-Slots noch nötigen Pfadgebäude (2 für 3 Berater), nicht über alle drei; (2) `productionInvestCandidate()` braucht zusätzlich eine Präferenz für ein platziertes-aber-ungelevletes Pfadgebäude vor anderen Kandidaten gleichen Levels. **Ohne diesen Fix validiert ein erneuter PlaytestBot-Lauf den unten vorgeschlagenen Zielwert nicht zuverlässig** — ein erneutes Scheitern des Bots am Korridor wäre dann kein Beleg gegen die neue Zahl, sondern Wiederholung desselben, hier schon benannten Ausführungsfehlers. Das ist ein Bot-Ausführungsdefekt (ein guter menschlicher Spieler würde nach dem Errichten eines Pfadgebäudes selbstverständlich zuerst dessen Lv1-Freischaltung fertigstellen, bevor er in Wohnhabitat oder eine weitere bioFacility investiert) — kein Balance-Hebel, wird hier deshalb nicht in die Zahlenempfehlung eingerechnet, aber explizit als Blocker für die empirische Nachprüfung benannt.

**Nebenbefund, geprüft und für unauffällig befunden — Supply-Cap zwingt keine zweite Wohnhabitat-Instanz und keine Reihenfolge-Zwangspause.** Autoritative Quelle ist `GameTick::calculateSupply()` (setzt `user_resources.supply` jeden Tick neu — `ResourcesService::getSupplyBreakdown()` liest `$cap` nur als bereits gesetzten Wert, ist nicht die Formel selbst): `cap = min(capCC + housingLevel × capHousing + knowledgeCap, capMax)`, mit `capCC = 10` (flat, sobald CC > Lv0 — nicht CC-level-skalierend, trotz des einzelnen `supply_cap`-Kommentars „cap per level" in `config/buildings.php`, das ist eine weitere kleine Doku/Code-Diskrepanz, hier nur benannt) und `capHousing = 8`. Da Wohnhabitat bereits ab Sol 1 auf Lv1 existiert (s. o.), liegt der Cap von Beginn an bei `10 + 8 = 18`, nicht erst bei 10. Verbrauch der vollen Zielkette (Harvester 2 + bioFacility Lv2 `2×2=4` + 2 Pfadgebäude auf Lv1 `2×6=12`) = 18 — passt exakt in den Sol-1-Cap, ganz ohne dass Wohnhabitat erst auf Lv2 gehoben werden müsste. Sobald Wohnhabitat auf Lv2 investiert wird (Cap 26), bleibt zusätzlicher Puffer von 8. Weder eine zweite Wohnhabitat-Instanz noch ein erzwungenes Vorziehen des Wohnhabitat-Levelups sind strukturell nötig.

**Nebenbefund, geprüft und für unauffällig befunden — die −2,94-Rg/Sol-Reparaturannahme ist im 15–20-Sol-Fenster konservativer als real nötig, bewusst so belassen.** `repairCandidate()` greift erst unter 30 % `max_status_points` (< 6 von 20). Frisch platzierte Gebäude starten auf vollen 20 SP (`ColonyController::placeBuilding()`, s.o.) — bei `decay_rate` 0,40–0,80/Sol dauert es 17,5–50 Sole, bis ein neu gebautes Gebäude die Reparaturschwelle überhaupt erreicht. Innerhalb eines 15–20-Sol-Floor-Fensters lösen frisch gebaute Gebäude realistisch **keine** Rg-kostende Reparatur aus; nur die Sol-1-Bootstrap-Gebäude (80 % SP, 16/20 — CC, Harvester, Wohnhabitat) kämen rechnerisch in die Nähe, erreichen die Schwelle bei `decay_rate` 0,40 (CC, Wohnhabitat) aber ebenfalls erst nach ≈25 Solen — außerhalb des Zielfensters. Der Term bleibt trotzdem im Modell (keine Neuherleitung nötig) — er ist eine bewusste, unveränderte Sicherheitsmarge, keine nachträglich „entdeckte" zweite Modellungenauigkeit.

**Nebenbefund, dokumentiert, nicht Teil dieses Hebels — der in `config/buildings.php` behauptete „harte" CC-Lv2-Gate für bioFacility ist im Code nicht auffindbar.** Der Kommentar bei `bioFacility` nennt sie „mandatory prerequisite for the CC Lv1→Lv2 upgrade"; `ColonyController::investBuilding()` (CC-Levelup-Pfad) enthält aber keine bioFacility-Prüfung — nur `OnboardingHintService` verweist weich darauf (`checkHintAgrardome`), und `placeBuilding()` erzwingt Agrardom nur als Voraussetzung für die **Pfadgebäude** (`error_agrardom_required`), nicht für CC-Invests. Der Bot bestätigt das empirisch: CC erreichte Lv3 bereits Sol 1, ohne dass bioFacility zu dem Zeitpunkt gebaut war. Diese Diskrepanz zwischen Config-Kommentar/GDD-Beschreibung und tatsächlichem Codeverhalten ist ein eigener, kleiner Dokumentations- bzw. Gate-Findungs-Punkt (nicht klar, ob der Kommentar oder der fehlende Code der Fehler ist) — hier nur benannt, nicht Teil dieses Nachtrags-Hebels, da CC ohnehin nachweislich kein Engpass ist (Sol 1 fertig).

**Korrigierter Hebel — derselbe Hebel wie am 2026-08-12 (Startbestand), neu aufgelöst gegen die korrigierte 535er-Summe:**

```
Verfügbar(N) = Startbestand + 17×(N−1) − 2,94×N = Startbestand − 17 + 14,06×N
```

| Startbestand | Floor N (Verfügbar(N) = 535) | Poor-Tile-Worst-Case (Formel s. u.) |
|---|---|---|
| 300 (aktuell umgesetzt) | ≈ 17,9 | ≈ Sol 23 |
| 320 | ≈ 16,5 | ≈ Sol 21 |
| **340** | **≈ 15,1** | **≈ Sol 20** |
| 400 (verworfen, s. u.) | ≈ 10,8 | ≈ Sol 16 |

**Empfehlung: Startbestand 300 → 340** (nicht 400 — dieser Wert stand in einer Zwischenfassung dieses Nachtrags, siehe Kasten oben, und beruhte auf der inzwischen korrigierten Wohnhabitat-Zeile). Bei 340 landet der Floor bei ≈Sol 15,1 (volle Kette, beide Pfadgebäude) — nahezu exakt der Wert, den der 08-12-Nachtrag als Zielposition beabsichtigt hatte (≈15,4), jetzt aber gegen die korrekt gerechnete 535er-Kette statt der fehlerhaften 500er. **400 wäre eine Überkorrektur:** Floor ≈10,8 würde die Kolonie strukturell zu schnell durch Phase 1 tragen und mit G5 („2–4 Mal pro Run an Regolith scheitern") sowie der G4-5–8-Sole-pro-Gebäude-Kalibrierung kollidieren — der gleiche Fehler in die andere Richtung, den die verschärfte Owner-Vorgabe vermeiden soll (Tempo-Ziel gegen Varianz-Ziel eingetauscht). 340 hält den Floor nah an der ursprünglich beabsichtigten Position, ohne den Korridor nach unten zu sprengen.

> **Nachtrag 2026-08-16:** 340 → 370. GDD §9-Begegnungen (Sturm) können seit
> ihrer Implementierung auch in Phase 1 landen (trotz der Phase-1-Ramp-
> Dämpfung, die die Chance nur senkt, nie auf 0 setzt) — ein Kritisch-Tier-
> Treffer kostet Ø ~77,5 Rg (Band 60-95), genug um die knappe Sol-30-Deadline
> zu reißen (empirisch beobachtet: PlaytestBot-Standardseed 4242 kippte von
> zuverlässigem Phase-1-Erfolg zu `phase1_deadline`-Fail, sobald Encounters
> aktiv waren). +30 Rg deckt ~40 % eines typischen Treffers, verschiebt den
> No-Storm-Floor auf ≈Sol 12,9 — bewusst kein Vollschutz, um nicht erneut in
> die oben verworfene 400er-Überkorrektur zu laufen. Verifiziert: Seed 4242
> schließt mit 370 wieder zuverlässig ab (in einem Testlauf sogar komplett,
> Score 2966, statt nur Phase 1 zu erreichen).

`resource_max['regolith_normal'] = 300` bleibt unverändert ausreichend: kumulierte Extraktion bei Floor-Sol 15,1 ≈ `17×14,1 ≈ 240 Rg`, weiterhin unter der Mengengrenze.

**Poor-Tile-Worst-Case, neu gerechnet gegen 340/535:** `Verfügbar(14, poor) = 340 + 12×13 − 2,0×14 = 340 + 156 − 28 = 468`. Rest-Bedarf `535 − 468 = 67` Rg, bei ≈15 Rg/Sol netto auf dem neuen Tile ≈5 weitere Sole → Abschluss ≈ Sol 14 + 1 (Transit) + 5 = **Sol 20** — deckt sich mit dem 08-12-Zielwert. Bei unverändertem Startbestand 300 läge der Worst-Case bei ≈Sol 23 (immer noch unter der harten Sol-25-Grenze, aber ohne Sicherheitsmarge) — ein weiteres Argument für den moderaten Sprung auf 340 statt „300 unverändert lassen".

**Zu Auftragspunkt 3 — 2-Pfadgebäude-Kopplung an Slot 3 bleibt mit dem <25-Ziel vereinbar, bei Startbestand 340.** Der Floor von ≈Sol 15,1 gilt für die **volle** Zielkette einschließlich beider Pfadgebäude auf Lv1 (nicht nur eines) — die 2-Pfadgebäude-Anforderung selbst ist also kein struktureller Blocker für <25 oder <20. **Erwogene, aber nicht empfohlene Alternative:** Slot 3 vom 2. Pfadgebäude entkoppeln (z. B. an einen AP- oder Kenntnis-Meilenstein statt an ein zweites 95-Rg-Gebäude). Verworfen, weil (a) mit Startbestand 340 kein Bedarf dafür besteht — die Rechnung geht ohne Eingriff ins Slot-System auf — und (b) eine Entkopplung tiefer in die Slot-System-Kopplungslogik eingreifen würde als ein reiner Config-Zahlenwert, mit Kollateralrisiko für die Pfadwahl-Parität (§13 „Pfadwahl ab Sol 3"), die bewusst alle drei Pfade gleich gewichtet.

**Einordnung — warum dieser Nachtrag anders benannt ist als „Floor war zu optimistisch".** Der Fehler im 08-12-Nachtrag war **kein** zu optimistisches Friktions-Assessment, sondern eine konkrete, nachrechenbare Lücke in der Bedarfstabelle: ein struktureller Schritt (Errichtung setzt Level 0, nicht Level 1; der 0→1-Sprung ist ein separater, kostenpflichtiger Level-Up) wurde für die Pfadgebäude komplett ausgelassen und für bioFacility zur Hälfte gezählt — während Wohnhabitat fälschlich überhaupt als Neubau statt als Sol-1-Bootstrap-Gebäude geführt wurde. Diese Unterscheidung ist wichtig für zukünftige Iterationen: „der Floor war zu optimistisch" lädt dazu ein, beim nächsten Mal wieder blind den Startbestand hochzusetzen, ohne die Kette nachzurechnen. „Die Kette hat einen Pflichtschritt pro Level-Sprung übersehen, und ein Posten wurde gegen die falsche Analogie berechnet" ist dagegen an der Codebasis nachprüfbar (s. o. und `OnboardingService::seedStartingBuilding()`) und wiederholt sich nicht von selbst — vorausgesetzt, jede künftige Bedarfstabelle wird wieder explizit gegen den Sol-1-Bootstrap-Zustand verifiziert, nicht nur gegen `build_cost`/`levelupRegolithFor()`.

**Erforderliche Folgearbeiten bei Umsetzung (nicht Teil dieses GDD-Nachtrags):**

1. Startbestand Regolith 300 → 340 — `OnboardingService::setupNewPlayer()` (genauer: `seedResources()`), `app/Console/Commands/ResetPlayer.php`-Szenarien, `data/sql/testdata.sqlite.sql` (Szenario-Pflege-Pflicht, s. Agent-Rollenbeschreibung).
2. **Vor jeder erneuten empirischen Bestätigung per PlaytestBot:** die zwei in „Ursache 2" benannten Fundstellen in `tests/Feature/Playtest/BotStrategy.php` fixen (`level >= 1` statt `tile_x`-Check im Rg-Puffer; Pfadgebäude-Präferenz im `productionInvestCandidate()`-Tie-Break). Ohne diesen Fix ist ein erneuter Sol-23+-Befund kein Gegenbeweis gegen Startbestand 340 — er wiederholt nur den bereits identifizierten Ausführungsfehler.
3. TDD-Pflicht (CLAUDE.md) für beide Punkte (1) und (2) getrennt beachten — (1) ist ein Konfigurationswert ohne eigenen Codepfad (Ausnahme von TDD zulässig, s. CLAUDE.md „Ausnahmen"), (2) ist Bot-Testlogik mit Verhalten und braucht einen vorab roten Test.
4. Nach beiden Fixes: erneuter PlaytestBot-Lauf (mehrere Seeds) zur empirischen Bestätigung des Sol-15-20-Korridors für die volle Phase-1-Kette (3 Berater, 2 Pfadgebäude, CC Lv3, bioFacility Lv2, Wohnhabitat Lv2).

---

## 14. Vertrauenssystem

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
| defense | Verteidigung & Überlebenstaktik | +1 |

Alle anderen Kenntnisse (construction, cartography, geology, trade) haben keinen direkten Vertrauenseffekt — sie sind neutrale Werkzeuge.

**Rationale:** Agronomie und Gesundheit verbessern spürbar das koloniale Wohlbefinden. **Korrektur (2026-08-27, Owner-Entscheidung):** Verteidigung gibt seit diesem Datum einen POSITIVEN Trust-Beitrag pro Level (`trust_per_lv=+1`, vorher `-1`) — "Sicherheit schafft Vertrauen" statt "Wachsamkeit dämpft Vertrauen". Die vorige Begründung (sichtbare Schutzinfrastruktur signalisiert Gefahr) wurde neu bewertet — eine gut ausgebaute, zivile Sicherheitsvorsorge (Sturm-Risiko-Reduktion, das andere `defense`-Effekt) wird jetzt durchgängig positiv gerahmt, ohne Trade-off-Zwang. (Präzedenzfall: `geology` hat ebenfalls zwei Vorteile ohne Vertrauensmalus, `trust_per_lv=0`.)

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
- **Hunger** (Vorrat deckt den Bedarf nicht) → **eskalierender** Malus, abhängig von `glx_colonies.hunger_streak` (aufeinanderfolgende Hunger-Sole):

```
Vertrauens-Malus = −min(2 + (streak−1), 8)
```

Anders als gewöhnliche Events eskaliert dieser Malus, solange der Hunger anhält, und verfällt erst beim Sättigen (Streak → 0). Er wird in `TrustService::calculateTrust` als eigener Summand addiert, nicht über die Event-Tabelle (die nicht stackt).

Die Hunger-Spirale wird eskalierend bestraft — ein anhaltender Hunger führt zu einem progressiv stärkeren Vertrauens-Malus (kein Cap beim Eintreten, aber ein Deckel nach einigen Solen). Dies erzeugt einen echten Druck, die Organika-Produktion auf Lv1+ zu bringen. Der Feedback-Loop: leerer Agrardom → Vertrauensverfall → Produktions-/AP-Malus → noch weniger Organika möglich. Der Agrardom wird damit zum Pflichtgebäude. Exakte Malus-Werte: `config/game.php → trust.events.*`.

### Einflussfaktoren: Ereignisse (Events)

Events können Vertrauen temporär verändern. Die Wirkung hält genau **1 Sol** an (danach wirken nur noch Dauereffekte). Event-Vertrauenswerte werden nicht in `colony_resources` gespeichert, sondern bei der Sol-Berechnung addiert und am Ende des Sols verworfen.

Datenmodell: `innn_events` kann über das `data`-Feld bereits Vertrauen-Deltas tragen. Kein Schemabedarf.

**Geplante Event-Kategorien:**

Events sind nach Kategorie gruppiert: Bauwesen/Forschung (Gebäude/Kenntnisse abgeschlossen oder verfallen), Handel (Handelsrouten erfolgreich oder blockiert), Diplomatie (Verträge), Begegnungen (Zwischenfälle gelöst oder eskaliert), Spieleraktionen (freiwillige Kolonisten-Zulagen). 

Alle Effekte wirken exakt 1 Sol (werden nach der Vertrauen-Berechnung verworfen). Mehrere Events desselben Typs im selben Sol summieren sich **nicht** — es gilt der stärkste Wert der Kategorie.

Die konkreten Vertrauenseffekte pro Event-Typ (Malus für Verfall oder Fehler, Bonus für Erfolg oder Zuwendung) stehen in `config/game.php → trust.events.*` — exakte Werte nach erstem Playtest kalibrieren.

> **TODO:** Exakte Vertrauenswerte für Begegnungs-Events nach §9-Ausarbeitung kalibrieren. Event-Keys sind in `TrustService` als `game.trust.events.*` angelegt (CLAUDE.md Korrekturen-Sektion); Werte nach erstem Playtest festsetzen. Der **Sicherheits-Hub** dämpft diese drei Events (+ `building_level_down`) um 25 % wenn aktiv — das macht ihre genauen Werte doppelt relevant.

**Rationale für neue Events:**
- `trade_blocked` (-3) macht Handelsblockaden spürbar — nicht nur wirtschaftlich, sondern auch in der Stimmung der Siedlung.

> ⚠️ BALANCE CONCERN: Event-Vertrauenseffekte für Bauwesen sind einmalig (+1 pro Level-Up). Ein Spieler der täglich Gebäude baut, erhält täglich +1 — das ist ein kleiner, aber stetiger Bonus der aktives Spielen belohnt. Ob das ausreicht als Motivation oder ob der Effekt auf +2 erhöht werden sollte, ist nach erstem Playtest zu evaluieren.

### Effekte des Vertrauens auf die Kolonie

Vertrauen beeinflusst drei Spielparameter. Alle Effekte werden als **Multiplikatoren** auf die Basiswerte angewendet, nicht als additive Boni. Das verhindert, dass Vertrauen zu einer dominanten Wachstumsstrategie wird.

#### Ressourcenproduktion

Vertrauen wirkt als Multiplikator auf die Rohstoffproduktion (Harvester, Agrardom). Hohes Vertrauen gibt einen moderaten Produktionsbonus; niedriges Vertrauen reduziert den Output. Das skaliert die Produktion ohne sie zu blockieren — der Multiplikator bleibt immer > 0.

#### AP-Multiplikator

Vertrauen wirkt auch auf die effektiven Aktionspunkte pro Sol — ein schwächerer Effekt als bei der Produktion. AP ist die knappste Ressource, daher soll Vertrauen sie nicht zu stark verstärken (kein Dominanz-Stacking).

> ⚠️ BALANCE CONCERN: Ein starker AP-Malus bei negativem Vertrauen macht Krisensituationen selbstverstärkend (weniger AP → weniger Reparaturen → mehr Decay → mehr Vertrauens-Malus). Diese Spirale ist designtechnisch vertretbar (Entropie als Spielprinzip), aber es muss einen Ausweg geben. Der Ausweg ist der Bau von Vertrauensgebäuden (positive Events) und Kolonisten-Zulagen (Spieler-Aktion), beides funktioniert trotz AP-Malus (AP wird nicht negativ).

Exakte Multiplikator-Werte pro Vertrauensbereich: `config/game.php → trust.production_multiplier` / `trust.ap_multiplier`.

#### Supply-Cap

Vertrauen beeinflusst den Supply-Cap **nicht**. Das Supply-System ist ein separater Constraint (Wohnkomplexe, CC) und soll nicht durch ein weiteres System kompliziert werden. Beide Systeme bleiben orthogonal.

### Schema-Bedarf

**Kein neues Schema erforderlich.** `colony_resources.amount` (resource_id=12) speichert den aktuellen Vertrauenswert als Integer im Bereich -100 bis +100. Das ist ausreichend — Vertrauen ist ein Zustand, keine akkumulierte Menge.

**Die Konfiguration** steht produktiv in `config/game.php` unter dem Schlüssel `trust` (Umbenennung von `moral`→`trust` abgeschlossen). Die vollständigen Werte (buildings, researches, ships, ships_cap, production_multiplier, ap_multiplier, events) sind dort implementiert — `config/game.php` ist die einzige Quelle der Wahrheit für alle Zahlenwerte. Dieses Dokument beschreibt die Semantik; die konkreten Zahlen stehen in der Konfigurationsdatei.

### Sol-Integration

Vertrauen wird als neuer **Tick-Schritt 6b** nach der Ressourcenproduktion berechnet:

| Schritt | Beschreibung |
|---------|-------------|
| 6 | Resource Generation — Rohstoffproduktion (mit altem Vertrauen-Multiplikator) |
| **6b** | **Vertrauen Calculation** — Vertrauen neu berechnen, `colony_resources` (res_id=12) aktualisieren |
| 7 | Advisor Ticks |

Die Reihenfolge ist bewusst: Die Produktion von Sol N verwendet den Vertrauenswert von Sol N-1. Der neue Vertrauenswert gilt erst ab Sol N+1. Das verhindert zirkuläre Abhängigkeiten.

### Implementierung (Stand)

Vollständig implementiert, kein offener TODO mehr:

1. `config/game.php` — `trust`-Block produktiv (alle Werte, siehe oben).
2. `app/Services/TrustService.php` — berechnet den Vertrauenswert je Kolonie.
3. Tick-Integration in Schritt 6b (siehe unten) — schreibt `colony_resources` (res_id=12).
4. `app/Services/AdvisorService.php` — AP-Berechnung berücksichtigt den Trust-AP-Multiplikator (`getApBreakdown`).
5. Produktionslogik — Trust-Produktionsmultiplikator wird angewandt.
6. UI: Vertrauen-Anzeige in der Ressourcenleiste (resource_id=12).

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

Dauer: typischerweise 10–20 Sole. Kann nicht übersprungen werden. Ziel ist eine lebensfähige, selbsttragende Kolonie.

**Startzustand (jeder Run):**
- CommandCenter Level 1 — bereits gebaut, betriebsbereit
- Harvester Level 1 — bereits gebaut, produziert sofort Regolith
- Moderate Startressourcen (Credits für erste Ankäufe, Regolith für Baustoff). Weitere Rohstoffe starten bei Null oder niedrig.
- Der Spieler kann direkt mit dem Bau von Zusatzgebäuden beginnen.

**Abschlussbedingungen (BEIDE müssen erfüllt sein):**

Phase 1 endet, wenn: (1) Infrastruktur ausreichend ist (mehrere Gebäude auf mehreren Leveln + CommandCenter aufgestuft) und (2) Personal vorhanden ist (mindestens 3 Berater aktiv). Die beiden Bedingungen sind eindeutig messbar und werden im Spieler-Interface angezeigt.

Die zwei Bedingungen decken die Kernsysteme ab: Aufbau (Gebäude) und Handlungsfähigkeit (AP). Sie sind eindeutig messbar und fur Neuspieler verstandlich.

Phase 1 endet automatisch, sobald beide Bedingungen gleichzeitig erfüllt sind. Der Spieler erhält eine Benachrichtigung und Phase 2 beginnt.

> **TODO (Design):** Optionale dritte Bedingung für Phase 1 — könnte pro Run variieren (Roguelike-Element). Beispiele: "erste Handelsroute etabliert", "eine Kenntnis auf Lv2", "erste Flotte entsandt". Das würde jeden Run-Einstieg leicht unterschiedlich anfühlen lassen. Bei Implementierung hier ergänzen.

#### Phase 2 — "Expeditionsmission"

Startet direkt nach Phase 1. Dem Spieler werden 3 Aufgaben aus dem Aufgabenpool zugewiesen (zufällig oder aus vordefinierten Sets). **2 von 3 müssen bis zu einem Run-spezifischen Sol erfullt werden.**

**Runlänge:** Das Spiel ist auf eine moderate Runlänge (typischerweise 60–100 Sole) kalibriert — lang genug für strategische Tiefe, kurz genug, um Wiederholbarkeit zu unterstützen. Das ist auch die Referenzgröße für alle AP- und Ressourcen-Balancingwerte.

**Konfiguration:** Jeder Run ist über `config/game.php → run` konfigurierbar: Gesamtsole, Echtzeit pro Sol (für Multiplayer), Player-Mode (Singleplayer oder Multiplayer), Play-by-Mail-Modus (Turn-basiert vs. Echtzeit-Synchronisation).

> **Designprinzip:** Die Max-Wartezeit (`tick_duration_hours`) ist Pflicht auch im Play-by-Mail-Modus — ohne sie blockiert ein inaktiver Spieler alle anderen. Singleplayer nutzt immer das Zeitmodell.

---

### Aufgabenpool

8 Aufgabentypen (Pool, `RunProgressService::TASK_CATEGORIES`/`TASK_TARGETS`). Pro Run werden 3 gezogen — Varianz reduziert Wiederholungsgefühl. Alle Aufgaben sind zivil erfüllbar (es gibt keinen Kampf mehr — Flotte/Systemkarte gestrichen, §8). Jede Aufgabe passt zu vorhandenen Spielmechaniken.

| Aufgabe (`task_key`) | Kategorie | Kernmechanik |
|---|---|---|
| Handelsnetz (`task_trade_volume`) | Wirtschaft | Abgeschlossene Transaktionen mit dem Reisenden Händler im laufenden Run über einer Schwelle |
| Forschungsvorsprung (`task_research_lead`) | Forschung/Aufbau | Mindestens einige Kenntnisse auf Höchstlevel gebracht |
| Kolonieblüte (`task_colony_prosperity`) | Diplomatie/Zivilaufbau | Vertrauen über einer Schwelle für mehrere aufeinanderfolgende Sole |
| Selbstversorgung (`task_self_sufficiency`) | Wirtschaft/Aufbau | Regolith- **und** Organika-Vorrat gleichzeitig über ihren jeweiligen Mindestschwellen **und** Supply > 0 — alle drei Bedingungen gleichzeitig, für mehrere aufeinanderfolgende Sole; jeder einzelne Ausfall setzt den Streak zurück |
| Expeditionsstatus (`task_expedition_coverage`) | Exploration/Navigation | Alle Tiles der Kolonie-Zone erkundet |
| Ingenieursleistung (`task_engineering_output`) | Aufbau/Optimierung | Gesamt-SP-Kapazität aller Gebäude (Summe `status_points` aller `colony_buildings`) über einer Schwelle |
| Kreditreserve (`task_credit_reserve`) | Wirtschaft | Credits-Bestand über einer Schwelle für mehrere aufeinanderfolgende Sole (kein einmaliger Peak, sondern anhaltender Wohlstand) |
| Expertenstab (`task_senior_advisors`) | Aufbau/Personal | Alle Berater-Slots besetzt + mindestens 2 Berater auf Rang Senior oder höher |

Exakte Schwellen, Streak-Längen und Herleitung: `docs/game-reference.md#18-run-struktur`, vollständige Balancing-Historie unten in §18.4.

> ⚠️ BALANCE CONCERN: Aufgaben-Sets sollten mindestens 2 verschiedene Kategorien abdecken, damit ein Run nicht ausschließlich Wirtschaftsaufgaben zieht (`task_trade_volume` + `task_credit_reserve` sind beide Wirtschaft). Eine Kombo-Blacklist bzw. -Regel für die Ziehung ist noch nicht implementiert.

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
- Aufgaben-Variante wird leicht entspannt (moderater Zielwert-Abschlag, siehe `config/game.php`)

#### Sanktionen (wenn der Spieler hinter Plan liegt)

Nexus erhöht den Druck auf Kolonien, die Milestones verfehlen:
- Berater kurz abgezogen ("vorübergehend für administrative Zwecke einberufen") — temporärer AP-Kapazitätsverlust
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

Genau 4 Fail States (vollständige, aktuelle Liste inkl. Fail State 4 „Phase-1-Fristbruch" in §18.2 — dieser Abschnitt ist älter und noch nicht vollständig nachgezogen).

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
- UI-Label: "Nexus-Kredit: X / [Cap] Cr" — Farbwechsel bei moderaten und hohen Schwellenwerten  
- Bei hohem Kreditzustand: einmalige INNN-Meldung von Nexus, die Vertrauen leicht senkt ("Die Kolonisten merken, dass etwas nicht stimmt"). Schwellenwerte siehe `config/game.php`
- Lose Kopplung mit Vertrauen: kein automatischer Zusammenhang. Der Spieler managt beide Achsen aktiv.

**Fail State 3 — Zeitablauf:**
Das Sol-Limit des Runs wird erreicht ohne dass 2 von 3 Aufgaben erfüllt wurden.
- Begründung: Sauberes, vorhersehbares Ende. Verhindert Endlos-Sessions ohne Ziel.
- Sol-Limit: definiert in `config/game.php` (run.tick_limit), narrative Framing mit Countdown sichtbar ab ~Sol 80.
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

**Sieg ist nur in Phase 2 möglich:** `endRun('completed')` wird nur aufgerufen wenn `run.phase == 2`. In Phase 1 gibt es ausschließlich Fail States (Trust, Schulden, Zeit — letzterer praktisch nie, da Phase 1 deutlich kürzer als `tick_limit` dauern sollte — sowie Phase-1-Fristbruch, Sol 30, Fail State 4 unten, der einzige der vier Fail States, der ausschließlich in Phase 1 auslösen kann).

---

### 18.2 Fail States

Vier Fail States. Alle werden am Ende der Tick-Phase 5 geprüft, nach dem Objective-Update (damit ein Sieg auf demselben Tick immer Vorrang vor einem gleichzeitigen Fail State hat). Kanonische Implementierung: `RunProgressService::checkFailStates()`. Der vierte (Phase-1-Fristbruch) kann nur in Phase 1 auftreten — sobald Phase 2 erreicht ist, greifen ausschließlich die anderen drei.

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

#### Fail State 4 — Phase-1-Fristbruch

**Bedingung:** `run.phase === 1 && current_tick >= config('game.run.phase1_deadline_sol')` → Standardwert **Sol 30**

**Auslösung:** Instant in dem Tick, in dem die Deadline erreicht wird, sofern Phase 1 noch nicht abgeschlossen ist (`RunProgressService::checkPhase1Completion()`).

Owner-Vorgabe 2026-08-12: Phase 1 soll im Normalfall Sol 15-20 abgeschlossen sein, spätestens Sol 30. Datenbasis: PlaytestBot-Auswertung (PR #244, mehrere Seeds/Reruns) zeigte Phase 1 aktuell frühestens Sol 55-65 abgeschlossen — deutlich außerhalb des Zielkorridors. Dieser Fail State macht die Deadline spielmechanisch verbindlich; die zugehörige Rebalancierung, die Sol 15-20 überhaupt erreichbar macht, ist bereits hergeleitet (§13.7 „Nachtrag 2026-08-12 — Phase-1-Pacing auf Sol-15-20 neu hergeleitet"): entgegen der ursprünglichen Vermutung ist **nicht** der Harvester-Ertrag der bindende Engpass — weder er noch die Tile-Mengengrenze (`resource_max`) noch Berater-Hire-Credits binden im Zielfenster. Der alleinige Hebel ist der Regolith-Startbestand — nach einer Korrektur der Bedarfskette (§13.7 „Nachtrag 2026-08-13", 35 Rg unterzählt) **200 → 340** statt der ursprünglich vorgeschlagenen 300. Umgesetzt und empirisch bestätigt: `phase2_start_sol` = 20–22 über 3 Testseeds (siehe Anhang A.4).

**Warnstufen (INNN):**

| Sol | Maßnahme |
|-----|---------|
| Sol 22 (`config('game.run.phase1_warning_sol')`) | INNN-Warnung von Nexus, sofern Phase 1 noch nicht abgeschlossen — einmalig pro Run |
| Sol 30 | Fail State — Run endet sofort |

Vollständiges Design: `docs/superpowers/specs/2026-08-12-phase1-sol30-deadline-design.md`.

**Narrativer Ausgang:** "Die Stabilisierungsphase wurde nicht rechtzeitig abgeschlossen. Nexus zieht die Konzession mit sofortiger Wirkung."

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

> ✅ BEHOBEN (2026-08-14): `task_expedition_coverage: 19` war **mathematisch unerreichbar**, nicht nur schwierig — die Colony-Zone wächst über `config('game.colony_zone_expansion')` (Summe 15 Terrain-Tiles über alle 5 CC-Level) plus das immer-Zone-und-vorerkundete CC-Ring-0-Tile, macht maximal **16** je erreichbare `is_colony_zone=1`-Tiles. PlaytestBot bestätigte den Deadlock empirisch: alle 3 Testseeds blieben identisch bei 13/19 stehen (Phase-2-Pacing-Untersuchung, 2026-08-14). `RunProgressService::TASK_TARGETS['task_expedition_coverage']` auf **16** korrigiert, Regressionstest ergänzt (`RunProgressServiceTest::test_task_expedition_coverage_target_does_not_exceed_max_reachable_colony_zone_tiles`), der jede künftige `colony_zone_expansion`-Änderung gegen diesen Zielwert prüft.
>
> **Nachtrag 2026-08-16:** Ziel 16 war zwar rechnerisch erreichbar, praktisch aber
> fast nie — die 16. Kachel hing exklusiv an CC Lv5, das typischerweise erst
> weit nach Sol 65 erreicht wird (CC-Lv4-Timing-Befund, siehe §13.5-Diskussion).
> `colony_zone_expansion` von `[6,3,3,2,1]` auf `[6,3,3,3,0]` umverteilt (Summe
> weiterhin 15) — die 15. Kachel (Ziel-Gesamt 16 mit CC-Ring-0-Tile) schaltet
> jetzt bereits bei CC Lv4 frei. `task_expedition_coverage`-Ziel bleibt bei 16.
> Gleicher Nachtrag korrigiert `task_self_sufficiency`: Streak-Ziel 15→8 Sole,
> Regolith-Schwelle `>50`→`>25` (PlaytestBot zeigte Regolith nur in 18/95 Solen
> über der alten Schwelle, Ø 31,4 — Regolith ist laufender Verbrauch, keine
> stabile Reserve). `lang/de+en/run.php` korrigiert dabei auch einen
> Text/Code-Mismatch: der Text nannte "Werkstoffe", der Code prüft schon immer
> Regolith (resource_id=3).

> **Nachtrag (2026-08-17) — drei Objectives nachträglich verschärft, `task_self_sufficiency` teilweise zurückgedreht.** Ein 20-Läufe-PlaytestBot-Batch (nach dem 08-16-Fix + dem Tick/Sol-Offset-Bugfix vom selben Tag) zeigte: Läufe gingen viel zu früh (Sol 39–54, Zielkorridor 80–85) als „completed" durch, weil die 2-von-3-Objectives-Siegbedingung (`GameTick.php`) leicht erreichbare Tasks kombinierte. Von 8 Task-Typen im Pool wurden nur 4 je erfüllt — `task_self_sufficiency` (8×), `task_expedition_coverage` (7×), `task_engineering_output` (3×), `task_credit_reserve` (2×) — die anderen vier nie (`task_senior_advisors` strukturell durch den damaligen Bot-Berater-Deckel blockiert, `task_colony_prosperity` vermutlich eigenes Trust-Kalibrierungsproblem, s.u.). Drei Korrekturen:
> - `task_self_sufficiency`: Streak **8→15** Sole, Organika-Schwelle `>50`→`>75` (Regolith-Schwelle bleibt bei `>25`). Reißt die 08-16-Lockerung teilweise zurück — die lief zu leicht nebenbei mit, ohne echte Anstrengung.
> - `task_credit_reserve`: Schwelle **3.000→4.000 Cr**, Streak **10→14** Sole. Die 3.000er-Schwelle war die 08-14-Notmaßnahme gegen den damaligen Collapse — jetzt (nach den 08-14/08-17/08-18-Fixes) ohne Repro-Risiko teilweise zurückkorrigierbar, bewusst nicht voll auf die alten 5.000 (Sicherheitsabstand).
> - `task_engineering_output` (Summe `status_points` über alle Gebäude): Ziel **200→320** (`RunProgressService::TASK_TARGETS`, nirgends sonst im GDD referenziert). ~29 Status-Points/Gebäude (bei ~11 aktiven Gebäuden) statt ~18 — erzwingt aktive Priorisierung statt beiläufigem Mitlaufen beim normalen Leveln.
> - `task_expedition_coverage` bewusst **nicht** erhöht — bereits am mathematischen Maximum (16, siehe 08-14-Nachtrag oben). Eine Erhöhung würde das Objective erneut unerreichbar machen.
> - `task_colony_prosperity` (Vertrauen > 70, 10 Sole Streak) bewusst **nicht** angefasst — Trust bewegte sich in allen 20 Läufen nur zwischen ca. −10 und +10, nie in Nähe von 70. Vermutlich eine Trust-Ökonomie-Fehlkalibrierung (Quellen zu schwach/Decay zu stark), kein Balance-Ziel-Problem — eigene, noch nicht begonnene Untersuchung.
>
> **Verifiziert (nach diesem Fix + dem 08-17-Bot-Berater-Deckel-Fix):** frischer 20-Läufe-Batch zeigte 3/20 „completed" bei Sol 84, 91, 65 — die ersten beiden treffen den Zielkorridor, die dritte ist noch etwas früh, aber deutlich näher als zuvor.

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

> **Nachtrag (2026-08-14) — Post-Phase-1-Kollaps identifiziert und behoben, Rang-3-Upkeep war die eigentliche Ursache:** Der Fix vom 2026-07-19/20 (oben) rechnete Break-even nur für **Rang 2** durch und stufte das verbleibende Kollaps-Risiko als „spürbar, aber wegen des hohen Schulden-Fail-Schwellenwerts nicht sofort tödlich" ein. Ein frischer PlaytestBot-Lauf (Seed 4242, repräsentativ für alle 3 Testseeds, Phase-2-Pacing-Untersuchung 2026-08-14) zeigt, dass das die Lage deutlich unterschätzt hat: Credits crashen ab Sol ~30–40 auf 0 und bleiben dort für die **restlichen 55 Sole (58 % des 95-Sol-Runs)** — kein einziger gemessener Sol in diesem Fenster zeigt auch nur kurzzeitige Erholung, obwohl der AP-Pool durchgehend 11–21 Punkte ungenutzt lässt (der Bot ist nicht AP-, sondern Credits-limitiert und tut ab diesem Punkt strukturell nichts Sinnvolles mehr). Die NICHT-`task_expedition_coverage`-Objectives (`task_credit_reserve`, `task_senior_advisors`, `task_colony_prosperity`, `task_trade_volume`, `task_research_lead`, `task_self_sufficiency`) lagen in allen 3 Läufen durchgehend bei 0 Fortschritt.
>
> **Root Cause 1 — Rang 3, nicht Rang 2, ist der eigentliche Klippensturz.** Der 07-19-Fix flachte den Rang-1→2-Sprung ab (10→30, 3×), ließ den Rang-2→3-Sprung aber fast unverändert scharf (30→80, **2,67×**). Sobald mehrere Berater über die (ebenfalls 07-19 gestreckten, aber weiterhin erreichbaren) `rank_thresholds` (45 kumulierte aktive Ticks) Rang 3 erreichen — realistisch ab Phase-2-Sol ~45–60 bei Ersteinstellung um Sol 10–15 —, springt der Unterhalt von 3 Beratern von 90 Cr/Sol (Rang 2) auf **240 Cr/Sol** (Rang 3). Kein im Spiel verfügbarer Hebel deckt das (siehe Root Cause 2), und es gibt **keinen Rückweg**: Berater degradieren nie, das Defizit ist damit strukturell permanent, nicht zyklisch — anders als ein Rang-2-Defizit, das noch durch spätere Handelsverträge abgefangen werden könnte.
>
> **Root Cause 2 — die 07-19-Rechnung prüfte nur den Konsul-Fall, nicht den Normalfall.** Sowohl der Handelsvertrag (§12 Kanal 1) als auch Corvans Alltagsgeschäft (`MerchantService::shouldSpawn()`) sind hart an eine gebaute Cantina (Bar Lv1+) gekoppelt — Corvans Verkaufskanal zusätzlich an den Konsul-Rang für Häufigkeit/Konditionen. Sciencelab, Hangar und Cantina sind aber drei **gleichrangige** Pfadwahl-Optionen (§13 „Slot-System"). Ein Spieler, der Sciencelab und Hangar vor der Cantina baut — der PlaytestBot tut genau das: seine `HIRE_ORDER` priorisiert Analytiker und Raumfahrer vor dem Konsul, und `placeCandidate()` baut neue Gebäudetypen vor Wiederholungen, sodass Sciencelab (id 31) und Hangar (id 44) typischerweise vor der Cantina (id 52) fertig sind — hat **weder** Handelsvertrag **noch** Corvan-Verkauf: nicht reduziert, sondern **exakt 0 Cr/Sol** aus beiden Kanälen, dauerhaft. Das ist nicht der Randfall, sondern laut Bot-Playtest der *default*-Fall bei „naheliegender" Pfadwahl (Forschung + Schiffe vor Handel). Für diesen Fall bleiben nur zwei passive Quellen: `nexus_subsidy` (30 Cr/Sol flat) und `relay_bonus_per_uplink_level` (20 Cr/Sol × Uplink-Level, max. Lv3 = 60 Cr/Sol) — zusammen maximal **90 Cr/Sol**. Das deckt gerade so den Rang-2-Upkeep (Breakeven im besten Fall), aber bei Weitem nicht den Rang-3-Upkeep (240 Cr/Sol, **−150 Cr/Sol Defizit selbst mit voll ausgebauter Uplink-Station**). Die 07-19-Rechnung hatte nur den Konsul-Fall bei Rang 2 durchgerechnet; der No-Konsul/Rang-3-Fall — der in der Praxis häufigere — blieb unentdeckt.
>
> **Fix (vier Hebel, gleiche Methodik wie 2026-07-19/20):**
> 1. **Rang-2→3-Sprung abgeflacht:** `advisor.upkeep` von `[1=>10, 2=>30, 3=>80]` auf **`[1=>10, 2=>25, 3=>50]`**. Rang-2-Sprung bleibt bei 2,5× (vorher 3×), Rang-3-Sprung sinkt von 2,67× auf **2,0×**. 3 Berater auf Rang 3 kosten jetzt 150 Cr/Sol (vorher 240).
> 2. **Nexus-Subvention angehoben:** `credits.nexus_subsidy` von 30 auf **50 Cr/Sol**. Bewusst flach und bedingungslos (kein Pfadwahl-Bezug) — sie ist die einzige Einnahmequelle ohne jede Gebäude- oder Beraterentscheidung als Voraussetzung und muss deshalb den absoluten Boden tragen, unabhängig davon, welchen der drei Pfade der Spieler wählt.
> 3. **Relaisvergütung verstärkt:** `credits.relay_bonus_per_uplink_level` von 20 auf **35 Cr/Sol pro Uplink-Level** (max. Lv3 = 105 Cr/Sol, vorher 60). Uplink-Station bleibt der bewusst aktiv zu bauende Hebel — einzelne Instanz, CC-Lv2-Gate, **kein** Konflikt mit der Sciencelab/Hangar/Cantina-Pfadwahl (kann parallel zu jedem der drei Pfade gebaut werden) — statt einer weiteren voraussetzungslosen Subvention.
> 4. **Beförderungskosten Rang 3 gesenkt:** `advisor.promotion_costs[3]` von 400 auf **250 Cr**. Bei nahezu gleichzeitigem Rang-3-Aufstieg mehrerer Berater (wie im gemessenen Lauf, Sol 45–60) addierten sich bis zu 3 × 400 = 1.200 Cr Einmalkosten genau in dem Moment, in dem der laufende Upkeep-Sprung ohnehin zuschlägt — dieser kombinierte Einmal-Schock ist im gemessenen Lauf plausibel (mit)ursächlich für den scharfen Sol-20→22-Einbruch (2.010 → 970 Cr, deutlich mehr als reiner Upkeep über 2 Sole erklärt). Neuer Maximal-Schock: 3 × 250 = 750 Cr.
>
> Bewusst **nicht** geändert: `consul_contract_income_per_rank` (10/25/45 Cr/Sol) bleibt unverändert — der Handelsvertrag soll ein **Bonus** für die Cantina-Pfadwahl bleiben, kein impliziter Pflicht-Überlebensmechanismus. Genau das war der Denkfehler der Vorversion: die 07-19-Rechnung behandelte ihn faktisch als Grundversorgung, obwohl er hinter einer von drei gleichrangigen Pfadwahl-Optionen liegt.
>
> **Neue Break-even-Rechnung — worst case: keine Cantina, kein Konsul, kein Corvan (der vom Bot tatsächlich gespielte, laut Root Cause 2 nicht seltene Fall):**
>
> | Rang | Upkeep (3 Berater) | Einkommen, Uplink Lv0 | Einkommen, Uplink Lv2 | Einkommen, Uplink Lv3 (max.) |
> |------|---------------------|------------------------|-------------------------|---------------------------------|
> | 2 | 75 Cr/Sol | 50 Cr/Sol (−25) | 120 Cr/Sol (+45) | 155 Cr/Sol (+80) |
> | 3 | 150 Cr/Sol | 50 Cr/Sol (−100) | 120 Cr/Sol (−30) | 155 Cr/Sol (**+5**) |
>
> Die Uplink-Station kostet insgesamt nur 130 Rg (80 Rg Bau + 2 × 25 Rg Level-Up), keine Werkstoffe, ein Instanz-Slot, CC-Lv2-Gate — realistisch bis Sol 40–50 baubar, deutlich vor dem Rang-3-Zeitpunkt (Sol ~45–60). Selbst im komplett Cantina-losen Fall bricht die Ökonomie damit nicht mehr dauerhaft zusammen: sie trägt ein vorübergehendes, aus dem Phase-1-Reststand (typ. 500–1.500 Cr) absorbierbares Defizit während der Rang-2-Phase und erreicht spätestens mit ausgebauter Uplink-Station eine stabile Nulllinie statt eines permanenten Bodens.
>
> **Mit Cantina + Konsul (Bonusfall, keine Voraussetzung mehr):** Rang 3, Uplink Lv3, Konsul Rang 3 (+45 Cr/Sol) → 200 Cr/Sol Einkommen gegen 150 Cr/Sol Upkeep = **+50 Cr/Sol Überschuss**, zzgl. Corvans Alltagsgeschäft on top. Der Konsul bleibt damit ein spürbarer, aber optionaler Vorteil statt einer verdeckten Überlebensbedingung — die Pfadwahl bleibt eine echte Entscheidung ohne Optimalpfad (GDD-Grundprinzip, Catan-Inspiration), nicht länger ein verstecktes Muss.
>
> **`task_credit_reserve`-Zielwert: Anpassung empfohlen.** Der aktuell hartcodierte Schwellenwert (`RunProgressService::updateCreditReserve()`, `credits >= 5000`, 10 Sole am Stück in Folge) war unter dem alten, permanenten Kollaps faktisch unerreichbar — wie `task_expedition_coverage` zuvor (oben, 2026-08-14), nicht nur schwierig, sondern strukturell blockiert. Auch mit dem obigen Fix bleibt 5.000 Cr ambitioniert: der beste realistische Dauerüberschuss liegt bei +50 bis +80 Cr/Sol (Bonusfall Konsul + Uplink Lv3) über ein Zeitfenster von grob 30–50 Sol (Rang-3-Reife bis Run-Ende) — das ergibt 1.500–4.000 Cr Akkumulation zzgl. Phase-1-Reststand. **Empfehlung: Schwellenwert von 5.000 auf 3.000 Cr senken**, Streak-Dauer (10 Sole) unverändert lassen. Macht das Objective in der Mehrheit der Konsul-Läufe und einem relevanten Anteil der Nicht-Konsul-Läufe erreichbar, bleibt aber ein echtes, nicht triviales Sparziel — kein Nebenprodukt der bloßen Grundversorgung. Playtest-Kandidat: sollte nach Umsetzung der vier obigen Config-Änderungen per PlaytestBot gegengerechnet werden, bevor der Wert endgültig fixiert wird. Technische Anmerkung: der Schwellenwert steht aktuell **nicht** in `config/game.php`, sondern als Literal in `RunProgressService::updateCreditReserve()` — die Umsetzung sollte ihn zugleich nach `config('game.run.task_credit_reserve_threshold')` (oder ähnlich) auslagern, analog zu den übrigen `TASK_TARGETS`.
>
> **Umsetzung ist separater, nachgelagerter Schritt** (game-developer/backend-coder, TDD-Pflicht) — dieser Nachtrag ist die Design-Entscheidung, kein Code wurde hier geändert. `ResetPlayer`-Testszenarien mit hartcodierten Credits-/Upkeep-Annahmen (Szenario-Pflege-Pflicht, siehe Agent-Konfiguration) müssen bei Umsetzung der `advisor.upkeep`/`promotion_costs`-Änderung mitgeprüft werden.
>
> **Umgesetzt + empirisch bestätigt (2026-08-14).** Alle vier Hebel implementiert (`config/game.php`), `task_credit_reserve`-Schwelle nach `config('game.run.task_credit_reserve_threshold')` ausgelagert (war Literal in `RunProgressService`). PlaytestBot-Nachlauf (Seed 4242) bestätigt: **der permanente 0-Kollaps ist behoben** — Credits pendeln ab Sol ~40 niedrig (36–291 Cr), statt dauerhaft bei exakt 0 zu kleben, deckt sich mit der hergeleiteten Near-Breakeven-Lage. `task_credit_reserve` (3000 Cr, 10 Sole Streak) wird davon unabhängig **nicht** erreicht — nicht weil die Ökonomie wieder kollabiert, sondern weil der Bot jeden Überschuss sofort für andere Regeln ausgibt (Schiffskauf, Bar-Angebote, Berater-Anwerbung) statt gezielt zu sparen. Das ist ein Bot-Spielstil-Defizit, kein Ökonomie-Fehler mehr — offener Folgepunkt, kein Blocker für diesen Nachtrag. `task_senior_advisors` (0/1) ebenfalls weiterhin unerreicht — braucht den 4. Beraterslot besetzt, eigener, noch nicht untersuchter Punkt.
>
> **Nachtrag (2026-08-17/18) — 4. Beraterslot gelöst, Kollaps kehrt strukturell zurück, zweiter Zahlen-Fix + offene Design-Frage.** Der 08-14-Fix rechnete durchgehend mit 3 Beratern — der PlaytestBot hatte einen hardcoded Deckel bei `activeCount >= 3` (`BotStrategy::nextHireCandidate()`), obwohl das Slot-System 4 erlaubt (`advisor.max_slots`). Nach Auflösung dieses Deckels (2026-08-17) zeigte ein frischer 10-Läufe-Batch: Credits kleben in 8/10 Läufen wieder fast die ganze Laufzeit nahe 0 (Ø Sol 41+: 57–116 Cr). Durchgerechnet (Sol 50–70, CC Lv3–4, 4 Berater): **Pfad B (Sciencelab/Hangar, keine Cantina)** — Einnahmen ~110–155 Cr/Sol (Nexus-Subvention + Relaisvergütung + episodische Missionen) gegen ~150–200 Cr/Sol Unterhalt bei 4 Beratern (bis Rang 3) → strukturell negativ. **Pfad A (Cantina + Konsul)** — zusätzlich Corvans Alltagsgeschäft ~180–320 Cr/Sol → überkompensiert deutlich (2/10 Läufe wachsen auf 1.800–2.800+ Cr). Root Cause bleibt also derselbe Mechanismus wie 08-14 (Cliff bei mehr gleichzeitigen Rang-2/3-Beratern), nur mit dem jetzt erreichbaren 4. Slot erneut verschärft.
>
> **Zweiter Zahlen-Fix (umgesetzt 2026-08-18, PR #270):** `advisor.upkeep[3]` **50 → 35** (4 × Rang 3 damit 140 statt 200 Cr/Sol) + `credits.relay_bonus_per_uplink_level` **35 → 45** (pfadneutral, stärkt gezielt Pfad B ohne den bereits überkompensierenden Cantina-Kanal weiter aufzublähen). Neue Break-even-Rechnung, worst case (4 Berater, keine Cantina, kein Konsul, kein Corvan):
>
> | Rang | Upkeep (4 Berater) | Einkommen, Uplink Lv0 | Einkommen, Uplink Lv2 | Einkommen, Uplink Lv3 (max.) |
> |------|---------------------|------------------------|-------------------------|---------------------------------|
> | 2 | 100 Cr/Sol | 50 Cr/Sol (−50) | 140 Cr/Sol (+40) | 185 Cr/Sol (+85) |
> | 3 | 140 Cr/Sol | 50 Cr/Sol (−90) | 140 Cr/Sol (0) | 185 Cr/Sol (+45) |
>
> Struktur identisch zum 08-14-Muster: Rang-2-Defizit bei niedrigem Uplink-Ausbau bleibt (absorbierbar aus Phase-1-Reststand), Rang-3 erreicht mit Uplink Lv2+ eine positive bis neutrale Marge statt eines permanenten Bodens — aber deutlich knapper als der Cantina-Pfad.
>
> **Offene Design-Frage, kein Zahlen-Fix (Owner-Entscheidung 2026-08-17): sowohl der Sciencelab- als auch der Hangar-Pfad sollen ein EIGENES Credits-Einkommen bekommen**, unabhängig davon ob/wann die Cantina gebaut wird (Randfall: gar nicht oder erst spät). Die zwei Zahlen-Fixes oben nivellieren die Bilanz nur teilweise — sie ersetzen keinen fehlenden dritten Kanal. Welcher Mechanismus (Sciencelab-Forschungsverkauf? Hangar-Bergungsertrag in Credits? etwas Drittes?) ist noch nicht spezifiziert — eigener Design-Schritt für eine kommende Session, siehe ROADMAP.md „Offene Pfad-Paritäts-Fragen".

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

## Siehe auch

- **[GDD Balance & TODO Index](gdd-balance-checklist.md)** — Blockierende Tasks, Folgearbeiten, Playtest-Kalibrierung, offene Designfragen, Instrumentierung
- **[GDD ↔ Config Audit](gdd-config-audit.md)** — Drifts zwischen Dokumentation und implementiertem Code/Config

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
| Knappheitsordnung Regolith < Organika < Werkstoffe | §3 |
| AP-Struktur inkl. `ap.base = 12` (freigegeben 2026-08-03) | §13.6 |
| Regolith-Zahlensatz: Harvester-Frischwert, Reparatur 1 Rg/SP, Errichtung ~~70/95/120~~ → 70 (bioFacility, Ausnahme) / 95 (alle drei Pfadgebäude, korrigiert 2026-08-11), Level-Up 25 (freigegeben 2026-08-03) — `decay_rate` 0,40/0,60/0,80/1,20 dagegen **vorläufig**, kollidiert unter der neuen 1-Instanz-Sockel-Baseline mit der G2-60-%-Obergrenze (§13.7 Punkt 7, Owner-Entscheidung ausstehend) | §13.7 |
| `max_instances` als eigenes Feld neben `max_level` | §4c |

Alles andere ist verhandelbar. Insbesondere gilt das für den Zahlenvorschlag in §13.6 — er ist gegen die heutigen Werte gerechnet und teilt damit deren Unsicherheit.

> **Diese Regel gilt auch für Subagenten.** Wer mit Balance-Aufgaben beauftragt wird, bekommt sie explizit mitgegeben — sonst entstehen Vorschläge, die vorhandene Zahlen als Randbedingung behandeln und Workarounds darum herum bauen, statt den Satz neu zu rechnen.
