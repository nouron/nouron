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
    - 13.6 [AP-Zahlensatz](#136-ap-zahlensatz)
    - 13.7 [Regolith-Zahlensatz, hergeleitet](#137-regolith-zahlensatz-hergeleitet)
14. [Vertrauenssystem](#14-vertrauenssystem)
15. [Run-Struktur (Roguelike-Modus)](#15-run-struktur-roguelike-modus)
16. [Onboarding](gdd/onboarding.md) → eigene Datei
17. [Progressive Discovery System](gdd/progressive-discovery.md) → eigene Datei
18. [Run-Ende & Fail-State](#18-run-ende--fail-state)
- [Balance- und TODO-Index](gdd-balance-checklist.md) · [Implementierungsstand-Audit](audit-implementierungsstand-2026-09-06.md)

> **Ausgelagerte Kapitel** (2026-08-02, `docs/gdd/`): §8 + §8a (gestrichen, [Archiv](gdd/archiv-flotten-systemkarte.md)), §11 [Techtree](gdd/techtree.md), §16 [Onboarding](gdd/onboarding.md), §17 [Progressive Discovery](gdd/progressive-discovery.md). Kriterium: Kapitel, die man beim Nachdenken über Spielregeln nicht mitliest — nicht mehr geltende Mechanik, Entitätslisten und UX-/Content-Spezifikationen. Die Regelkapitel §1–7, §8b, §9–10, §12–15 und §18 bleiben zusammen. Ebenfalls in `docs/gdd/`: [`entity-chips.md`](gdd/entity-chips.md).

---

## 1. Spielkonzept

Nouron ist ein rundenbasiertes Weltraum-Strategiespiel für Einzelspieler im Browser. Der Spieler übernimmt die Rolle eines Kolonie-Direktors mit einem klaren Auftrag: eine kleine, ressourcenarme Kolonie auf Vordermann zu bringen — entweder eine frisch gestartete Siedlung oder eine heruntergekommene Anlage, die sich selbst überlassen wurde.

Die Kolonie bleibt im gesamten Spielverlauf überschaubar. Es geht nicht darum, ein galaktisches Imperium aufzubauen, sondern darum, eine kleine Gemeinschaft unter schwierigen Bedingungen am Leben zu erhalten und gedeihen zu lassen.

Das Spiel ist in **Runs** strukturiert: Jeder Run hat ein konkretes Ziel, einen variablen Verlauf und ein klares Ende — Erfolg oder Scheitern. Nouron enthält **Roguelike-Elemente**: variable Aufgaben je Run, zufällige Ereignisse und echte Konsequenzen für Fehlentscheidungen. Runs können wiederholt werden; jeder Run fühlt sich anders an.

Das Spiel läuft auf Basis eines Sol-Zyklus: alle Spielzustandsänderungen werden einmal pro Sol berechnet. Im Solo-Modus löst der Spieler Sole manuell aus; im Multiplayer-Modus feuert der Sol wenn alle Spieler bereit sind — oder nach Ablauf des Timeouts. (Intern: "Tick" — die technische Bezeichnung für den Berechnungszyklus.)

**Technischer Stack:** PHP/Laravel Backend, SQLite, Blade-Templates. Frontend: Alpine.js + PicoCSS, SVG für Spielfelder (Hex-Grid), Vanilla fetch() für Server-Calls. jQuery/Bootstrap-Migration vollständig abgeschlossen.

---

## 1.1 Designprinzipien

### Aufbau vor Konflikt

Nouron erzählt die Geschichte einer kleinen Kolonie, die ums Überleben kämpft — nicht die Geschichte eines aufstrebenden Militärstaats. Es gibt keine Armee, keine Flottenschlachten, keine Eskalationsziele. Schiffe dienen Erkundung (Drohne) und Logistik (Frachter); die Korvette schützt die Kolonieumgebung, sucht aber keine Konfrontation.

Gefahren sind klein, lokal und richten sich gegen die Kolonie selbst statt gegen Flotten: Stürme, geologische Instabilität, Seuchenausbrüche (§9). Sie wirken direkt auf den Gebäudezustand — es gibt keinen Gegner-Stärkewert, keinen Kampf, nur einen Zustand vorher und einen danach.

### Vorsorge statt Verbot

> Es gibt keine eigene „defensive" AP-Kategorie (kein Flottensystem, §8). Das Prinzip lautet daher: **Vorsorge kostet AP, das sonst in Wachstum fließen würde** — nicht als Strafe, sondern als Konkurrenz um denselben Pool.

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

Die Timestamp-Formel (`floor((timestamp - offset) / 86400)`) und `TickService::calculateTickFromTimestamp()` bleiben im Code, werden im Solo-Modus aber nicht verwendet. Sie dienen als Basis für spätere Multiplayer-Timeout-Berechnung.

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

> **Wozu diese Ordnung dient:** Beobachtet ein Playtest das Gegenteil (Organika im Überschuss, Regolith knapp), ist das ein Symptom der Produktionsraten, nicht ein Argument für andere Preise. Wo Beobachtung und diese Ordnung auseinandergehen, ist die Produktionsseite zu korrigieren, nicht die Ordnung.

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

Typische Werkstoffe-Events (immer mit Wahlmöglichkeit, nie kostenlos):
- **Strandetes Frachtschiff** — Bergung kostet Navigation-AP, gibt Werkstoffe
- **Händlerkonvoi in der Nähe** — befristetes Kaufangebot, günstiger als Nexus-Importpreis
- **Trümmerfeld im System** — Flotte entsenden, Werkstoffe heimholen

> **Entscheidung: Werkstoffe bleiben als Ressource.** Eine Streichung (Credits übernehmen die Rolle) wurde geprüft und nicht umgesetzt — Werkstoffe tragen die dritte Achse des Tauschdreiecks (§4a), die Lv1-Funktion der Uplink-Station (§4), den qualitativen Rang-3-Vorteil des Konsuls (§12), zwei Missionsbelohnungen (§8b) und eine Run-Aufgabe (§15). Eine Streichung wäre ein eigenes Vorhaben mit Ersatz für jede dieser Rollen.

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

13 Gebäude (`config/buildings.php`; Max-Level je Instanz, Wohnhabitat zusätzlich max. 6 Instanzen, Harvester max. 2):

| ID | Config-Key | Name (DE) | Name (EN) | Max-Level | Voraussetzung |
|----|------------|-----------|-----------|-----------|---------------|
| 25 | commandCenter | Kommandozentrale | Command Center | 5 | — |
| 28 | housingComplex | Wohnhabitat | Residential Habitat | 3 (× 6 Instanzen) | CC Lv1 |
| 27 | harvester | Harvester | Harvester | 1 (× 2 Instanzen) | CC Lv1 |
| 41 | bioFacility | Agrardom | Agrarian Dome | 3 | CC Lv1 + Harvester Lv1 (**Pflichtgebäude vor CC Lv2**, siehe unten) |
| 31 | sciencelab | Analytik-Labor | Analytics Lab | 5 | CC Lv2, Teil der **Pfadwahl** (siehe unten) |
| 46 | infirmary | Krankenstation | Medical Station | 3 | CC Lv2 |
| 52 | bar | Cantina | Cantina | 3 | CC Lv2, Teil der **Pfadwahl** (siehe unten) |
| 44 | hangar | Hangar | Hangar | 3 (Instanzen ungedeckelt) | **CC Lv2**, Teil der **Pfadwahl** (siehe unten) |
| 32 | temple | Religiöse Stätte | Sacred Site | 1 | CC Lv4 |
| 50 | monument | Kolonialdenkmal | Colonial Monument | 1 | CC Lv5 |
| 53 | securityHub | Sicherheits-Hub | Security Hub | 3 | CC Lv3 |
| 54 | uplinkStation | Uplink-Station | Uplink Station | 3 | CC Lv2 |
| 55 | tradingPost | Handelsposten | Trading Post | 3 | CC Lv4 |

> **Agrardom ist Pflichtgebäude vor CC Lv2.** Der Agrardom gehört nicht zur Pfadwahl-Gruppe. Begründung: Sol 1/2 garantieren nur Bau- und Erkundungs-AP-Verwendung, keinen Ressourcenfluss. Ohne Agrardom bliebe Organika auf 0, bis der Spieler ihn irgendwann wählt — in der Zwischenzeit frisst die Verpflegungsmechanik (§4a „Organika") den nicht vorhandenen Vorrat, und der eskalierende Trust-Malus (`TrustService::hungerPenalty`) greift vor der ersten bewussten Wirtschaftsentscheidung. Deshalb ist er das erste Bauprojekt der Kolonie (`hint_agrardome`, §16.2) und ein hartes Gate: `placeBuilding()` verlangt den Agrardom vor jedem Pfadgebäude. Eine zusätzliche Prüfung am CC-Levelup selbst ist beschrieben, im Code aber nicht vorhanden (Owner-Frage C16, ROADMAP).
>
> **Pfadwahl ab Sol 3 (CC Lv2 → Lv4):** Sciencelab, Hangar und Cantina sind alle ab CC Lv2 baubar , aber **nur eines der drei kann bei CC Lv2 gebaut werden** — die anderen beiden schalten erst bei CC Lv3 bzw. CC Lv4 frei (gestaffelt nach Bau-Reihenfolge, nicht nach Gebäudetyp). Was die drei Pfade inhaltlich sind, steht in **§4b „Die drei Pfade"**; die Slot- und Gate-Mechanik in §13 „Slot-System".
>
> **Sicherheits-Hub (CC Lv3) — optionaler Resilienz-Baustein:** Der Sicherheits-Hub ist **nicht Teil der Pfadwahl-Gruppe** (kein Bau-Gate-Zähler), sondern ein separates Infrastrukturgebäude das ab CC Lv3 gebaut werden kann. CC Lv3 hat **kein Pflichtgebäude** als Voraussetzung (kein Äquivalent zum Agrardom-Gate bei CC Lv2): 90 Regolith + AP-Kosten sind das natürliche Gate.
>
> **Harvester (Sondergebäude):** Der Harvester unterscheidet sich von allen anderen Gebäuden: Er steht nicht in der Kolonie-Zone, sondern auf einem Ressourcen-Tile in der Exploration Zone. Er produziert passiv je nach Tile-Typ (Regolith oder andere Mineralien). Er kann verlegt werden (Kosten: 1 Construction-AP **pro Hex Distanz**, keine Ressourcenabzüge; Transit-Zeit: **1 Sol flat**, unabhängig von der Distanz — der Harvester produziert im Transit-Sol nicht). Es gibt genau einen Harvester pro Kolonie. Technisch ist er ein Gebäude mit einer `tile_x/tile_y`-Position statt eines Kolonie-Slots.

> **Designentscheidung Harvester-Transit (2026-06-28):** Eine distanzabhängige Transit-Zeit (z. B. 1 Sol pro 2 Hex) wurde geprüft und **verworfen**. Die AP-Kosten skalieren bereits mit der Distanz (1 AP/Hex) und erzeugen damit das gewünschte Planungs-Druckgefühl. Eine zusätzliche Sol-Staffel wäre eine doppelte Strafe für lange Verlegungen und würde im Transit Reparaturen blockieren (Reparatur kostet Regolith — kein Regolith-Zufluss ohne Harvester), was eine unkontrollierte Decay-Spirale riskiert. Der 1-Sol-Stopp ist ausreichend: 1 Sol ohne Regolith-Produktion (= 8 Rg Opportunitätsverlust) bei gleichzeitig bis zu 5 AP-Kosten bei einer Fünf-Hex-Verlegung. Falls der Playtest zeigt, dass Harvester-Verlegungen zu oft ohne Nachdenken passieren, ist der bessere Hebel die AP-Rate (1 AP/Hex erhöhen), nicht die Sol-Downtime.
>
> **Playtest-Beobachtung:** Mit `max_level = 1` (§13.5) wiegt der Transit-Sol relativ schwerer, weil das Grundeinkommen die einzige passive Regolith-Quelle bleibt. Der Verlegungsanreiz sinkt zugleich, da Tile-Ergiebigkeit nicht mehr über Level multipliziert wird — beides im Playtest zu beobachten.

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

Der Sicherheits-Hub ist ein auf 1 Instanz begrenztes Infrastrukturgebäude (CC Lv3, max. Lv3). Er ist kein Pfadwahl-Kandidat und unterliegt keinem Pfadwahl-Bau-Gate. Er öffnet **keinen Berater-Slot** — er trägt sich vollständig über seine drei unabhängigen Effekte:

**Passiv — Vertrauen-Bonus:**
Ein Bonus pro Level (kumulativ). Thematisch: "Die Bevölkerung fühlt sich durch Schutzinfrastruktur sicherer." Bewusst niedriger als andere Wohlfahrts-Gebäude — Sicherheitsinfrastruktur ist utilitaristisch, kein Luxus-Bonus. Exakte Werte: `config/buildings.php`.

**Passiv — Event-Dämpfung:**
Wenn der Hub aktiv ist, werden negative Vertrauensverluste aus Zwischenfällen reduziert (prozentual; siehe `config/buildings.php`). Gilt für die Events `building_level_down`, `encounter_lost` und `colony_threatened`. Thematisch: "Der Hub sorgt nicht dafür, dass Vorfälle ausbleiben — er verhindert, dass sie eskalieren."

**Passiv — Level-Down-Recycling:**
Wenn ein Gebäude durch Decay ein Level verliert, gibt die Kolonie automatisch einen kleinen Ressourcenanteil zurück (handelbare Ressourcen: Regolith, Werkstoffe, Organika). Der Anteil liegt bewusst deutlich unter dem Reparaturwert, damit kein Anreiz entsteht, Verfall absichtlich zu provozieren. Exakte Prozentsätze: `config/buildings.php`.

> **TODO Balance:** Alle drei Effekte (trust-Bonus, Event-Dämpfungs-%, Recycling-%) nach erstem Playtest kalibrieren (siehe `config/buildings.php`). Compounds-Anforderung ist akzeptiert: Hub ist kein Progression-Gate (CC Lv3 hat kein Pflichtgebäude), sondern ein optionaler Resilienz-Baustein. In runs mit schlechtem Trade-Zugang kann der Hub später kommen — das verzögert nichts Zwingendes.

> Ein Passiveffekt auf Flottenorders (Verteidigung billiger) existiert nicht — Flotten sind gestrichen (§8). Design-Kandidat, falls §8 reaktiviert wird.

---

### Uplink-Station (uplinkStation) — Mechanik

Die Uplink-Station ist das einzige Kommunikationsgebäude der Kolonie — 1 Instanz, Lv1–3. **Ohne Uplink-Station Lv1 sind aktive Nexus-Anfragen gesperrt** (Werkstoff-Direktimport, Handelsschiff anfordern, Verwaltungsanfragen). Eingehende Nexus-Funk-Nachrichten des Nexus (Milestones, Warnungen) kommen immer an — diese sind nicht abhängig vom Gebäude.

| Level | CC-Voraussetzung | Freischaltet / Effekt |
|-------|-----------------|----------------------|
| 1 | CC Lv2 | Aktive Nexus-Anfragen: **Werkstoff-Direktimport** (gegen Credits, immer verfügbar, fester Preis — siehe §3), Handelsschiff anfordern, Verwaltung |
| 2 | CC Lv3 | Tiefenscan kostet weniger AP (`ColonyTileService`); *geplant:* Reisender Händler erscheint häufiger (ROADMAP A11) |
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

**Zusätzlich — Kenntnis-Preisbonus:** Die `trade`-Kenntnis liefert unabhängig vom Handelsposten einen eigenen Preis-Bonus auf allen drei Handelskanälen (Cantina-Angebote, Reisender Händler, Nexus/Corporate Contact). Beide Quellen stacken additiv, ohne Konkurrenz oder Ausschluss — dasselbe Muster wie beim Bau-AP-Rabatt-Pool, den `construction` und `trade` gemeinsam speisen (§13.3). Exakte Werte: `config/knowledge.php` → `trade.trade_price_bonus_per_lv`.

> **TODO Balance:** Baukosten und Decay nach erstem Playtest festlegen (siehe `config/buildings.php`).

---

### Status-Punkte

Jedes Koloniegebäude hat ein `status_points`-Feld. Das Maximum (`max_status_points`) ist in der `buildings`-Tabelle hinterlegt. Status-Punkte sinken pro Sol durch Verfall (siehe Abschnitt 7).

**Leveled vs. Instanced Buildings:**

- **Leveled** — ein Objekt auf einem Tile, wird stufenweise ausgebaut (z.B. CC Lv1→5, Agrardom). Ein Klick auf das Tile → "Ausbauen". (Der Harvester ist **nicht** leveled — `max_level = 1`, §13.5.)
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

> Kernregel: **Die CC erschließt nur Gelände — sie siedelt nicht ins Unbekannte.** Erschließen ≠ Erkunden. Der CC-Ausbau erkundet keine Tiles automatisch — sonst wären die beiden Achsen für den Spieler ununterscheidbar.

> **Blocker unter Fog — Lücken-Deduktion (Playtest-Review 2026-07-11):** Unaufgedeckte Tiles können `terrain_impassable` sein — der Spieler riskiert beim Erkunden also Nav-AP für ein nutzloses Tile. Das ist ring-abhängig unterschiedlich bewertet und bewusst so entschieden:
> - **Ring 2 enthält keine Blocker mehr** (`ColonyTileService::resolveTileType()`, Gewicht auf Hazard/Empty umverteilt). Grund: Ring 2 hat kein Regolith — eine Lücke in der "bald bebaubar"-Anzeige hätte dort *deterministisch* einen Blocker verraten. Das Aufdecken wäre beweisbar verschwendete AP (Falle ohne Entscheidung), das Nicht-Aufdecken trivial. Beides ist keine interessante Wahl.
> - **Ring 3+ behält Blocker selten.** Dort ist eine Lücke in der Anzeige mehrdeutig: meist Regolith (der Jackpot fürs Harvester-Verlegen), manchmal Fels. Das Aufdecken einer Lücke ist damit eine echte Wette mit positivem Erwartungswert — Information durch Abwesenheit als Feature, nicht als Bug. Keine AP-Erstattung beim Blocker-Fund (würde blindes Aufdecken belohnen und die Ring-Kosten-Drossel entwerten), keine Silhouetten unter Fog (würde die Ring-3-Ambivalenz zerstören).

> **Offener Designpunkt (2026-06, nicht umgesetzt):** Idee, den Erkundungsradius über die aktuelle Ring-3-Grenze hinaus zu erweitern, um zusätzliche Nav-AP-Sinks für spätere Sols zu schaffen (die Ring-Staffelung allein bremst, erschöpft sich aber irgendwann). Offene Sorge: ein größeres/dichteres Hex-Grid wird auf Mobile schwer navigierbar (Pan/Zoom-Aufwand steigt mit der Tile-Zahl). Vorzugsweise die Tile-Zahl von der Nav-AP-Sink-Zahl entkoppeln statt das Grid zu vergrößern — z.B. Signale/Points-of-Interest in größerer Entfernung ohne zusätzliches Hex-Rendering, oder eine Scan/Survey-Order auf Distanz statt physischer neuer Hexes. Nicht implementiert — nur als Richtung für ein späteres Balance-/Pacing-Update vermerkt.

### Visuelle Zone-Abgrenzung

Die Kolonie-Zone-Grenze ist auf kleinen Karten kein sauberer Ring, sondern ergibt sich aus dem `is_colony_zone`-Flag pro Tile. Das Frontend rendert Colony-Zone-Tiles mit einem warmen Basis-Tint (Farbschema: Weiß/Anthrazit/Rot-Palette), Exploration-Zone-Tiles mit einem kühleren, dunkleren Tint. Der Spieler erkennt die Grenze durch Farbe, nicht durch Position. Regolith-Tiles und impassable Tiles innerhalb der inneren Ringe sind immer Exploration Zone — sie wirken als visuelle "Lücken" in der Colony Zone, was die unterschiedliche Funktion deutlich kommuniziert.

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
| **Regolith** | 1 Harvester-Instanz, Grundeinkommen pro Sol (Standard-Baseline, §4c) | `geology` senkt Erschöpfung | Mission-Missionen liefern variable Mengen je Umlauf | **kein dedizierter Wachstumshebel** — opportunistischer Credits→Regolith-Kauf als Sicherheitsnetz (§12), siehe „Pfad-C-Hebel" unten |
| **Organika** | Agrardom | `agronomy` erhöht die Produktion mit jedem Level (glockenförmig) | Missions-Missionen liefern Organika je Umlauf | Ankauf über Bar-Angebote |
| **Credits** | Relaisvergütung, Ratssubvention | ⚠️ Hebel offen | Botenflug / Konvoi-Begleitung | Handelsvertrag + Organika-Verkauf |
| **Vertrauen** | Gebäude-Boni, Ereignisse | `health` + Krankenstation | `mission_aid_transport` (+2) | Cantina `trust_per_lv` + Handelserfolge |

**Pfad A Credits-Hebel:** Offen (s. Tabelle). Kandidat: Kenntnis-Effekt, der Kosten senkt — passt besser zum Pfad-Charakter als eine zusätzliche Einnahmequelle.

> **Prüfregel für künftige Mechaniken:** Wird eine neue Ressource, Kosten- oder Bedarfsachse eingeführt, ist zu prüfen, ob alle drei Pfade sie bedienen können. Ist das nicht der Fall, ist entweder die Mechanik anzupassen oder den unterversorgten Pfaden ein Hebel zu geben — **nicht** die Ungleichheit hinzunehmen.

### Pfad-C-Hebel: Credits statt Regolith

**Pfad C trägt keinen Regolith-Hebel.** Ein Organika→Regolith-Tausch als Pfad-C-Hebel ist ausgeschlossen — er würde das knappere gegen das häufigere Gut tauschen (Knappheitsordnung §3). Die Regolith-Lücke der Zielkolonie schließen Pfad A (`geology`) und Pfad B (`mission_supply_run`) gemeinsam (§13.7). Der Engpass, gegen den Pfad C als „Pfad der Flexibilität" antritt, ist Credits — und die Kolonie produziert strukturelle Organika-Überschüsse (§4a), die über diesen Hebel monetarisiert werden.

**Mechanik: Organika-Verkauf als Angebotstyp des Reisenden Händlers.** Neben Kauf (Credits→Ressource) und Tausch (Ressource↔Ressource) gibt es den **Verkauf** (Organika→Credits), eng gefasst nur für Organika — Regolith- und Werkstoff-Verkauf bleiben außen vor, damit kein Umweg zur Regolith-Beschaffung entsteht.

- **Preis:** deutlich unter dem Kaufpreis der Gegenrichtung, damit Arbitrage unattraktiv bleibt (`config/game.php`).
- **Reserve-Untergrenze:** Verkaufslose nur, solange der Bestand über einer Mindestreserve liegt (Vielfaches des Sol-Bedarfs `food_need`) — schützt die Hunger-Spirale (§3/§4a) vor Leerverkauf.
- **Zugang:** Corvan (Kanal 3, §12) bringt die Verkaufslose bei jedem Besuch mit. Bar-gated (Cantina Lv1+), aber nicht Konsul-exklusiv — der Konsul-Rang skaliert Preise und Häufigkeit. Das ist die Pfad-C-Prämie: wer früh in den Konsul investiert, profitiert schneller und stärker, während alle Pfade Zugang haben.

**Zielgröße:** offen. Eine belastbare Credits-Zielgröße je Konsul-Rang erfordert eine Credits-Bilanz über den Run (`docs/gdd-balance-checklist.md` A.4). Bis dahin ist die Losanzahl pro Besuch ein Playtest-Kandidat; der Hebel, falls mehr gebraucht wird, sind mehr Lose pro Besuch, nicht ein kürzeres Intervall.

> **Designhinweis:** Der Organika-Verkauf schafft indirekt einen Umweg Organika → Credits → Harvester-Zweitinstanz (Orin, §4c). Das ist ein Überschuss-Ventil on top einer bereits geschlossenen Regolith-Lücke, kein tragender Baustein — Zielmetrik nach Playtest: der Anteil des Regolith-Zuflusses über diesen Umweg bleibt eng.

**Offen:** Pfad A hat keine eigene Credits-Quelle (Tabelle oben); Owner-Entscheidung: Sciencelab- und Hangar-Pfad sollen ein eigenes Credits-Einkommen bekommen, unabhängig von der Cantina — Mechanismus noch nicht spezifiziert (ROADMAP „Offene Pfad-Paritäts-Fragen").

### Der Sicherheits-Hub ist kein vierter Pfad

Der Sicherheits-Hub (CC Lv3) ist ein **optionaler Resilienz-Baustein** ohne Berater-Kopplung und ohne Pfadwahl-Gate (Stratege zurückgestellt, §13). Er steht außerhalb dieser Systematik.

---
## 4c. Instanzen oder Level — die Wachstumsachse je Gebäude

Ein Gebäude kann auf zwei Arten wachsen, und die Wahl ist eine Designentscheidung, keine technische.

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
| **Wohnhabitat** | Instanz (+ Level 1–3 je Instanz) | 6 | Mehrere Habitate; das Level je Instanz trägt den Supply-Cap-Beitrag (§6). |
| **Agrardom** | **Instanz** (Ziel) | offen | Mehrere Kuppeln; Nahrungsproduktion skaliert natürlich mit der Anzahl. **Owner-Frage F1:** Config führt den Agrardom als Level-Gebäude (max Lv3, nicht instanziert) — Umstellung oder Zielkorrektur offen. |
| **Hangar** | **Instanz + Level** | Instanzen offen, Lv3 | Der einzige Fall, der beide Achsen braucht — siehe unten. |
| **Analytik-Labor** | Level | Lv3+ | Lv1-3 **sind** die Kenntnis-Stufen (`cartography` Lv1, `geology`/`trade` Lv2, `defense` Lv3) — ohne sie bricht die Staffelung weg. Lv4/5 haben zusätzlich einen eigenen Effekt (Kenntnis-Kosten-Rabatt, §13.3), keine reinen Gate-Stufen mehr. |
| **Uplink-Station** | Level | Lv3 | §4 nennt sie „das einzige Kommunikationsgebäude der Kolonie". Eine zweite Funkanlage verdoppelt keine Reichweite. |
| **Sicherheits-Hub** | Level | Lv3 | Eine pro Kolonie. |
| **Handelsposten** | Level | Lv3 | Eine pro Kolonie. |
| **Cantina** | Level | Lv3 | Zwei Kneipen in einer Kleinkolonie wirken falsch; eine bessere Kneipe nicht. |
| **Krankenstation** | Level | Lv3 | Besser ausgestattet, nicht doppelt vorhanden. |
| **Religiöse Stätte** | — | **1 Instanz, Lv1** | Weder Instanzen noch Level. Sie ist ein Bekenntnis, kein Ausbauprojekt. |
| **Kolonialdenkmal** | — | **1 Instanz, Lv1** | Dito. Ein Denkmal, fertig oder nicht. |

### Harvester: wenige Instanzen, dafür beweglich

**Ein Harvester ist die Standard-Baseline eines Runs.** Ein Run ist mit einer Instanz regulär abschließbar; die zweite Instanz ist ein optionaler Bonus, kein Bestandteil der Zielkolonie. Höchstens 2 Instanzen bleiben als technische Obergrenze (`max_instances`). Die Standard-Zielgröße ist der 1-Instanz-Zyklusdurchschnitt (§13.7); Regolith kommt zusätzlich über Missionen, Events und Handel — der Harvester ist der Sockel, nicht die Skalierung.

Der Harvester ist das einzige **bewegliche** Gebäude des Spiels (§4 „Harvester-Transit"), und diese Eigenschaft wird im Spielverlauf tatsächlich gebraucht: **Ein Harvester wird pro Run mehrfach umgesetzt.** Dafür sorgt die Erschöpfung der Vorkommen.

**Erschöpfung der Vorkommen.** Ein Regolith-Tile trägt einen Harvester eine begrenzte Zeit, dann sinkt der Ertrag:

- `colony_tiles.resource_max` / `resource_amount` — Startvorkommen und Restvorkommen je Tile
- drei Ergiebigkeitsstufen `regolith_rich` / `regolith_normal` / `regolith_poor` mit unterschiedlichem Frischwert und Vorkommen (`config/game.php → harvester`)
- die Verlege-Vorschau mit Ertragsvergleich und die distanzabhängigen Verlegekosten (`config/game.php`)

Damit entsteht die gewollte Schleife: fördern → Ertrag sinkt → Umzug lohnt → ein Sol Produktion und einige AP kosten → neues Tile. **Erkundung bekommt einen konkreten wirtschaftlichen Zweck**, weil man wissen muss, wo das nächste ergiebige Tile liegt, *bevor* der Umzug erzwungen ist.

#### Erschöpfungskurve und Umzugstakt

```
Ertrag = Frischwert × (0,5 + 0,5 × Restvorkommen / resource_max)
```

Ein Tile beginnt beim vollen Frischwert und fällt bis zum Ausschöpfen auf die **Hälfte** — nie auf null, damit ein vergessener Harvester nicht schlagartig stillsteht. Bei erschöpftem Vorkommen: Produktion 0, der Umzug ist erzwungen. Ein `poor`-Tile erreicht den Boden früher als ein `normal`-Tile — derselbe Mechanismus, keine Sonderregel.

| Fall | Harvester |
|---|---|
| **Standard, ganzer Run** | **1 Instanz** (Baseline) |
| Optionaler Bonus | 2 Instanzen, nur wenn über Weg A oder B verfügbar |

**Der eigentliche Regler ist die Umzugsgebühr, nicht die Kurve.** Verlegekosten sind pro Hex gesetzt (`config/game.php`) und machen einen Umzug samt Zielkundschaftung zu einer spürbaren Investition. Spielziel: mehrfache Umzüge pro Run, aber nicht als Routine — die Verlegung ist eine wiederkehrende Entscheidung, kein automatisches Refresh.

**Sichtbarkeit für den Spieler.** Die Schleife funktioniert nur, wenn der Spieler *vor* dem Erschöpfen ein Ziel kennt. Dafür startet die Kolonie mit einem vorerkundeten Ausweich-Vorkommen (Ring 3, „Nexus-Scout"-Fund), ein Onboarding-Hint warnt, sobald das Restvorkommen des aktiven Tiles unter die Schwelle `game.harvester.low_regolith_warning_pct` fällt, und erkundete, nicht erschöpfte Ausweich-Tiles sind auf der Hex-Karte markiert. Gegen Ertragssorgen („zu wenig Regolith bei Sol N") wird nicht an `fresh_yield`/`resource_max` gedreht, solange nicht mehrere Messpunkte über verschiedene Tile-Typen und Pfadwahlen vorliegen — das Umzugs-Timing ist der Hebel.

> **Hinweis zur Geologie-Kenntnis:** `geology` (Harvester-Bonus, §13.7) ist nicht CC-gegatet — Gate ist Analytik-Labor Lv2 + Harvester Lv1, beides ab CC Lv2 erreichbar. Kolonien, die zuerst Hangar oder Cantina bauen, laufen entsprechend länger ohne Kenntnis-Boost auf dem Harvester; das ist Teil der Pfadwahl, kein Fehler.

### Harvester-Zweitinstanz: Bezugsquelle

Die zweite Instanz ist kein garantierter Bestandteil, aber keine unmögliche Varianz. **Zwei unabhängige Quellen, beide selten und unsicher**, damit sie von Run zu Run unterschiedlich erreichbar sind und keinen planbaren Sockel-Zuwachs bedeuten. Es gibt keinen garantierten Weg — für die **erste** Instanz gilt die Bootstrap-Ausnahme (kein Catch-22), für die zweite ausdrücklich nicht.

#### Weg A: Orin (`corporate_rep`) — eigene Kontaktperson, nicht der Reisende Händler

Orin (`docs/characters/corporate_rep.md`, `config('characters')` Key `corporate_rep`) vermittelt Extraktionsrechte und Ausrüstung im Namen ungenannter Interessen mit vermuteter, nie bestätigter Nexus-Nähe — ein Grauzonen-Deal, keine offizielle Institution. Genau deshalb ist er nicht planbar. Corvan (Kanal 3) kommt für diese Rolle nicht in Frage: sein Charakterblatt legt ihn als unabhängig fest („answers to no company, no Nexus contract").

- **Technik:** eigener Spawn-Check `CorporateContactService` mit eigenem Config-Namensraum (`config/game.php → corporate_contact`), unabhängig von `MerchantService` (Corvans Inventar) und der `BarService`-Gästerotation. Orins Angebot ist zustandslos: eine Funktion aus Kolonie und Tick, keine Visits-Tabelle.
- **Zwei-Ebenen-Varianz:** Ebene 1 — erscheint Orin überhaupt (seltener als Corvan, passend zu „rare" in seinem Charakterblatt); Ebene 2 — bringt er beim Erscheinen das Harvester-Angebot mit (eigene, niedrigere Chance). Zusammen soll deutlich weniger als die Hälfte der Runs ein kaufbares Angebot sehen. Der Hebel, falls das zu selten wirkt, ist die Ebene-2-Chance, nicht das Erscheinungsintervall (Charakter-Kanon).
- **Gate:** CC Lv3 (`game.harvester.second_instance_cc_level`) — die Betriebsfähigkeit einer zweiten Schwermaschine hängt an der Koloniereife. Das Angebot wird nur gewürfelt, wenn `instance_count < max_instances` **und** das Gate erreicht ist — kein Angebot, das nicht kaufbar wäre.
- **Preis:** Credits im Bereich eines mehrere-Sole-Sparprozesses — „Bonus mit Opportunitätskosten" (`config/game.php`).
- **Platzierung:** Kauf → sofort platzierbar auf einem erkundeten Regolith-Tile, eigener Flow getrennt von `harvester_move`.

#### Weg B: Bergungsmission auf einer Ruinen-Kachel

`mission_harvester_salvage` (`config/missions.php`): Ziel ist eine durch Erkundung + Tiefenscan aufgedeckte `ruin_tile`, Schiff Frachter oder Korvette, `repeatable: false` — eine havarierte Förderanlage einer früheren Expedition, die die Kolonie nicht neu bauen, aber reaktivieren kann. Ein Zufallsfund ohne Spielerhandlung (Event-Tile) ist bewusst nicht vorgesehen.

- **Belohnung:** ein Freischalt-Flag für die zweite Instanz (`HarvesterEntitlementService`), kein Ressourcenwert.
- **Zustand bei Ankunft:** beschädigt (Status-Points im Bereich der Dispatch-Sperrschwelle). Reparatur kostet ausschließlich AP — der Harvester ist für jede Instanz von Regolith-Reparaturkosten ausgenommen (§4, Bootstrap-Regel).
- **Gate:** kein eigenes CC-Gate in der Mission; die Sichtbarkeit von Ruinen-Tiles liegt typischerweise erst nach vergleichbar viel Erkundung wie CC Lv3. Feuert das im Playtest zu früh, ist ein explizites `requires.building_level` das Sicherheitsnetz.
- **Offen — Spawnrate von `ruin_tile` pro Run:** Weg B kostet nur AP, Weg A Credits. Die Ruinen-Häufigkeit ist damit die **einzige** Bremse, die Weg B von einer dominanten Route unterscheidet, und muss vor einer Balance-Aussage verifiziert werden (Erzeugungslogik für `event_ruin`-Tiles prüfen).

**Warum zwei Wege, keine dritte Route:** `geology` liefert bereits einen Produktionsbonus auf bestehende Instanzen (§13.7) — ein zusätzlicher kenntnisgebundener *Erwerbs*pfad würde die Pfad-A-Identität verwischen. Zwei unabhängige, seltene Quellen (kommerziell über Orin, physisch über Ruinen) decken die gewünschte Varianz ab.

**Playtest-Monitoring:** Umzugsfrequenz pro Run (mehrere, aber keine Routine), Anteil der Sole mit Ertragserschöpfung (Ziel: niedrig), Anteil der Runs mit tatsächlich gekaufter/geborgener Zweitinstanz.

### Hangar: der einzige Fall mit beiden Achsen

Der Techtree gatet Schiffe über **Hangar-Level** — Drohne Lv1, Frachter Lv2, Korvette Lv3, dazu `defense` ab Hangar Lv2. Zugleich ist der Hangar instanziert. Beide Achsen haben getrennte Bedeutung:

| Achse | bedeutet | Deckel |
|---|---|---|
| **Instanzen** | Schiffsplätze — wie viele Schiffe die Kolonie halten kann | offen, supply-begrenzt |
| **Level** | Schiffsklasse — Lv1 Drohne, Lv2 Frachter, Lv3 Korvette | Lv3 |

Beides ist intuitiv: Eine Halle fasst ein Schiff, eine größere Halle ein größeres. Die primäre Wachstumsachse bleibt die Instanz (Grundsatz oben), das Level ist ein dreistufiges Freischalt-Gate.

### Datenmodell: `max_level` und `max_instances`

`buildings` führt beide Deckel als getrennte, nullable Felder (`NULL` = unbegrenzt): `max_level` ist der Level-Deckel je Instanz, `max_instances` der Instanz-Deckel instanzierter Gebäude. Gepflegt in `config/buildings.php`, in die DB gesynct via `game:sync-config`; `ColonyController::placeBuilding()` prüft `max_instances`, die Techtree-Gates `max_level`. Decay wird je Instanz verarbeitet (`processBuildingDecay()` filtert nach `instance_id`).

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

> **Warum eine Glockenkurve mit Deckel statt einer linearen Rate:** Ein linearer oder exponentieller Anstieg widerspricht der Frontier-Logik (jedes Level soll spürbar, aber nicht grenzenlos lohnend sein), und ein unbegrenzter Ausbau mit abflachendem Ertrag wäre bei flachen Levelup-Kosten nie eine echte Entscheidung — der Grenzertrag sänke monoton, ohne dass je ein Stopp erzwungen wird. Ein harter Deckel erzeugt echten Bedarf („wohin als Nächstes investieren?") — Wachstum darüber hinaus kommt über Kenntnisse, Missionen und Handel (Amplifikator-Prinzip, §18).
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

### Supply im Sol (GameTick Schritt 5 / §2 Phase 3)

`user_resources.supply` speichert den **aktuellen Supply-Cap**. Er wird in `GameTick.php`-Schritt 5 (entspricht der groben Phase 3 „Supply & Ressourcen" in §2) jedes Sols neu berechnet und gesetzt — so spiegelt der Wert immer den aktuellen Gebäudestand wider (z. B. nach einem Level-Down des Wohnkomplexes durch Decay).

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
| Leveled Building (allgemein) | Leveled | Level − 1; status_points reset auf max_status_points; Protokoll-Ereignis |
| Wohnhabitat | Instanced | **Instanz zerstört** (kein Level zum Abziehen); Supply-Cap sinkt; Protokoll-Ereignis |
| Hangar | Instanced | **Instanz zerstört**; zugewiesenes Schiff wird **unbrauchbar** (nicht zerstört); Protokoll-Ereignis |
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
- Protokoll-Ereignis: „[Name] benötigt eine Auszeit — Kolonie-Kapazität vorübergehend reduziert."

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

**Lieferzeiten Nexus-Anfrage:** je Schiffstyp gestaffelt — Drohne am schnellsten, Korvette am langsamsten (`config/game.php → hangar`).

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

> **Status: Implementiert** — `config/missions.php`, `HangarService::dispatchShip()`/`getMissionCatalogFor()`, `GameTick::processHangarMissions()`. Noch nicht im Katalog: `mission_perimeter_patrol` (Voraussetzung §9 ist erfüllt, Aufnahme offen — ROADMAP A18). `mission_ruin_expedition` zahlt Credits; der Almanach-Unlock folgt mit §17.

Außenmissionen sind der einzige aktive Einsatz von Schiffen (§7 Schiffs-Verschleiß). Jede Mission ist ein ziviler Auftrag — Erkundung, Logistik, Bergung, Schutzdienst. Es gibt keine Gegner und keinen Kampf (§9-Designlinie): Das Risiko einer Mission ist ausschließlich physisch — Verschleiß pro Sol unterwegs und der automatische Abbruch bei 0 SP.

#### Kostenmodell

Beim Dispatch fallen einmalig an (beide Kosten gaten den Start, AP-Chip-Konvention: alle Kosten vorab als Chips am Button):

- **Navigation-AP:** `sol_distance × 2` (`config/missions.php → nav_ap_per_sol`; bereits in `config/game.php → food.mission_nav_ap_per_sol` auf 2 gesetzt, zieht bei Implementierung um)
- **Organika:** `sol_distance × 3` als Proviant & Betriebsstoffe (`organika_per_sol`; gilt einheitlich auch für die unbemannte Drohne — eine Ausnahme würde Drohnen-Missionen zum kostenlosen Optimalpfad machen)
- Einzelne Missionen haben Zusatzkosten (z.B. Hilfsgüter-Fracht), im Katalog vermerkt.

`sol_distance` ist die **einfache Strecke**; die Gesamtdauer beträgt `2 × sol_distance` Sole (Hin- und Rückweg — deckungsgleich mit der Verschleiß-Prognose aus §7).

> **⚠️ Zu kalibrieren (ROADMAP Stufe 6):** Die Navigation-AP-Staffel ist gegen den gemeinsamen Pool (§13.1) zu setzen. Absicht: lange Expeditionen sind über Opportunitätskosten an die Raumfahrer-Progression gekoppelt, ohne hartes Gate — Distanz 1–2 ohne Raumfahrer machbar, Distanz 4–5 nur mit spürbarem Verzicht auf Bau- und Kenntnisprojekte.

#### Schwierigkeit & Erfolgschance

> **Status: Implementiert (2026-09-03)** — Werte in `config/game.php` → `missions.difficulty` und `config/missions.php` → `difficulties` pro Katalogeintrag.

Eine Außenmission hat keinen garantierten Ausgang mehr, sondern eine Erfolgschance. Beim Dispatch wählt der Spieler zwischen zwei der drei Schwierigkeitsstufen — leicht, normal, schwer — die für die jeweilige Mission im Katalog hinterlegt sind. Eine höhere Stufe senkt die Basis-Erfolgschance, hebt aber im Erfolgsfall die Belohnung an: der Spieler tauscht Sicherheit gegen Ertrag, statt dass die Mission selbst über den Ertrag entscheidet.

Zur Basis-Chance der gewählten Stufe addieren sich zwei Bonusquellen: der Rang des Raumfahrers (§12, koloniedeckt, unabhängig von der konkreten Mission) und — nur falls die Mission ein Kenntnis-Gate hat — jedes Kenntnis-Level oberhalb des Gate-Mindestlevels. Ungegatete Missionen profitieren nur vom Raumfahrer-Bonus. Die resultierende Gesamtchance ist nach oben gedeckelt, ein risikoloser Erfolg bleibt also auch bei hohem Rang und hoher Kenntnis ausgeschlossen.

Scheitert eine Mission, entfällt die Belohnung vollständig — das Schiff kehrt ohne Ertrag zurück. Auf der höchsten Schwierigkeitsstufe (schwer) kostet ein Fehlschlag das Schiff zusätzlich Verschleiß (Status-Points, §7): Dort ist das Risiko nicht nur entgangener Ertrag, sondern eine echte Materialkonsequenz. Konkrete Werte (Basis-Chancen, Reward-Multiplikatoren, Bonushöhen, Chance-Cap, Hard-Fail-Verschleiß sowie die Stufen-Zuordnung je Mission): siehe `docs/game-reference.md#hangar-missionen-schwierigkeit`.

#### Katalog

| Key | Name | Schiff | Gate / Verfügbar |
|---|---|---|---|
| `mission_courier_run` | Botenflug | Drohne | sofort |
| `mission_recon_flight` | Erkundungsflug | Drohne | sofort |
| `mission_deep_survey` | Signalvermessung | Drohne | bekanntes Signal-Tile |
| `mission_prospecting_flight` | Prospektionsflug | Drohne | Geologie Lv1 |
| `mission_data_sweep` | Datensammelflug | Drohne | Kartografie Lv1 |
| `mission_supply_run` | Versorgungsfahrt | Frachter | Frachter vorhanden |
| `mission_trade_convoy` | Handelsfahrt | Frachter | Handel Lv1 |
| `mission_aid_transport` | Hilfsgütertransport | Frachter | sofort (ungegatet — zweite Frachter-Mission ohne Kenntnis-Gate, deckt die Vertrauens-Lücke von Pfad B) |
| `mission_salvage_sweep` | Trümmerbergung | Frachter o. Korvette | Bautechnik Lv1 |
| `mission_escort_convoy` | Konvoi-Begleitung | Korvette | Korvette vorhanden |
| `mission_perimeter_patrol` | Umkreis-Patrouille | Korvette | Verteidigung Lv1 — **geplant, noch nicht in `config/missions.php`** |
| `mission_ruin_expedition` | Ruinen-Expedition | Frachter o. Korvette | tiefengescanntes Ruinen-Tile |
| `mission_harvester_salvage` | Harvester-Bergung | Frachter o. Korvette | tiefengescanntes Ruinen-Tile, einmalig (§4c Weg B) |
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

> ⚠️ BALANCE CONCERN: Der Milderungs-Effekt der Umkreis-Patrouille überschneidet sich mit dem Almanach-Bonus `encounter_prep` (§17). Regel: Milderungseffekte stapeln nicht — es gilt maximal eine Ausgangsstufe Milderung pro Gefahr, der stärkste Effekt wird verbraucht.

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

Begegnungen finden ausschließlich auf der Kolonieoberfläche statt (Hex-Grid, §4a); Flotten und Systemkarte sind gestrichen (§8). Es gibt kein Kampfsystem, keine Stärkewerte, keine Schiffe.

**Implementiert.** `EncounterService` löst alle drei Gefahrentypen pro Sol auf, Ausgangsstufen basieren auf dem SP-Zustand (siehe unten). Sturm nutzt eine 1-Sol-Vorwarnung über `colony_log` und wirkt koloniweit (`GameTick::rollStorm()`/`resolveStormWarning()`); Geologische Instabilität und Seuchenausbruch treffen sofort ohne Vorwarnung. Ein Cooldown zwischen Ereignissen (`game.encounter.cooldown_sols`) puffert gegen Spiral-Risiko. In Phase 1 rampt die Trigger-Chance von 0 auf volle Stärke (`game.encounter.phase1_ramp_sols`), damit die Sol-30-Deadline erreichbar bleibt — früh ist die Welt schwächer, aber nicht harmlos. Lore: Der Startbestand kommt aus einem automatisierten Frontier-Depot; frisch gelandete Kolonien sind verwundbarer.

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

| Gefahr | Wirkbereich | Trigger | Konsequenz | Häufigkeit (Richtwert) | Abschwächung |
|---|---|---|---|---|---|
| **Sturm** | **Koloniweit** — trifft alle Gebäude der Colony Zone gleichzeitig (Harvester ausgenommen, siehe unten) | Zufällig; Basis-Chance/Sol steigt mit Run-Schwierigkeit | SP-Verlust je Gebäude nach individueller Ausgangsstufe (Tabelle oben); ein gemeinsamer Trust-Ausgang für die ganze Kolonie (siehe „Wirkbereich"-Abschnitt) | variabel nach Phase (häufiger mit mehr Gebäuden) | Hohe SP durch regelmäßige Reparatur — bei koloniweitem Wirkbereich zählt jetzt der Zustand *aller* Gebäude, nicht nur eines |
| **Geologische Instabilität** | **Einzelgebäude, fest** — trifft immer den Harvester; kein Zufallsziel unter mehreren Gebäuden, da mechanisch an das Harvester-Tile gekoppelt | Chance steigt mit Solen seit letzter Relocation, sinkt mit Kenntnis Geologie | Produktionsausfall des Harvesters für eine Weile (statt zusätzlichem Trust-Malus — kein doppelter Bestrafungseffekt) | seltener als Stürme | Kenntnis Geologie senkt Chance; Relocation setzt Zähler zurück |
| **Seuchenausbruch** | **Kein Gebäude-Wirkbereich** — wirkt direkt auf Kolonie-Systeme (Supply-Cap/AP-Generierung), nicht auf die SP eines oder mehrerer Gebäude; die Einzelgebäude/koloniweit-Unterscheidung greift hier nicht | Emergent statt rein zufällig: nur möglich bei echter Vernachlässigung (Hunger-Spirale oder sehr niedriges Vertrauen), dann Zufallschance/Sol | Supply-Cap oder AP-Generierung temporär reduziert + `colony_threatened` | nur im Vernachlässigungsfall — bei gesunder Kolonie 0% Grundrisiko | Krankenstation (infirmary) und Kenntnis `health` senken Chance/Schwere |

### Wirkbereich (Scope): Einzelgebäude vs. koloniweit

**Owner-Entscheidung (2026-09-03):** Kolonistengefahren fallen in genau eine von zwei Wirkbereich-Kategorien.

- **Einzelgebäude-Wirkbereich** — ein Ereignis trifft ein einzelnes, bestimmbares Gebäude. Das Ziel kann zufällig unter mehreren geeigneten Gebäuden gewählt sein (Status quo vor dieser Entscheidung, sofern für einen zukünftigen Gefahrentyp gebraucht) oder — wie bei Geologischer Instabilität — mechanisch fest an ein bestimmtes Gebäude gekoppelt sein. In beiden Fällen bleibt der Schaden lokal: ein Gebäude, ein Ausgang, ein Trust-Event.
- **Koloniweiter Wirkbereich** — ein Ereignis trifft gleichzeitig **alle** Gebäude der Colony Zone. Sturm ist der erste und bislang einzige Gefahrentyp dieser Kategorie: Ein Sturm zieht nicht über ein Gebäude, sondern über die ganze Siedlung. Das passt zum Bild — Wetter kennt keine Gebäudegrenzen, ein geologischer Riss oder eine Krankheit dagegen schon (oder ist, im Fall der Seuche, ohnehin kein gebäudebezogenes Phänomen, siehe Tabelle).

**Schadensberechnung bei koloniweitem Wirkbereich:** Jedes betroffene Gebäude bekommt weiterhin seine **eigene** Ausgangsstufe nach seinem eigenen SP-Zustand (Tabelle oben, unverändert) — ein gut gewartetes Gebäude übersteht den Sturm unbeschadet, ein vernachlässigtes daneben nimmt trotzdem Schaden. Es gibt keinen einzigen Wurf für die ganze Kolonie, sondern so viele Berechnungen wie betroffene Gebäude. Der Harvester bleibt von Sturm ausgenommen (analog zur bestehenden Sonderrolle bei Seuche: er hat mit Geologischer Instabilität bereits eine eigene, gebäudespezifische Gefahr — kein doppelter Bestrafungseffekt auf dasselbe Gebäude durch zwei verschiedene Gefahrentypen).

**Trust-Aggregation:** Trust reagiert **nicht** proportional zur Zahl betroffener Gebäude — sonst würde ein koloniweites Ereignis bei einer größer ausgebauten Kolonie automatisch einen Vielfachen Trust-Schaden gegenüber derselben Kolonie früh im Run auslösen, ohne dass der Spieler dafür etwas anders gemacht hätte. Ein koloniweites Ereignis löst **einen einzigen** Trust-Event für den gesamten Vorfall aus, bestimmt durch die **schlechteste** unter den betroffenen Ausgangsstufen (ein einziges kritisch getroffenes Gebäude reicht, damit der ganze Sturm als `colony_threatened` gilt, selbst wenn alle anderen Gebäude ihn abgewehrt haben). Die SP-Verluste selbst bleiben dagegen additiv über alle betroffenen Gebäude — der strukturelle Schaden (Reparaturbedarf, ggf. mehrere gleichzeitige Level-Downs) skaliert bewusst mit der Koloniegröße, nur der Trust-Preis nicht doppelt obendrauf.

**Vorwarnung/Protokollierung bei koloniweitem Wirkbereich:** Die Vorwarnung referenziert bei Sturm kein einzelnes Gebäude mehr (`entity-chip auf das betroffene Gebäude` gilt nur noch für Einzelgebäude-Ereignisse) — sie kündigt die ganze Kolonie als Ziel an. Die Ausgangsmeldung braucht eine **Sammel-Zusammenfassung statt N Einzelmeldungen**: eine Zeile pro Sturm-Ereignis, die die Verteilung der Ausgangsstufen über die betroffenen Gebäude nennt (z. B. „X abgewehrt, Y beschädigt, Z kritisch"), nicht eine Meldung pro Gebäude. Das gilt sowohl für den `colony_log`-Eintrag als auch — sobald die unten verlinkte Umsetzungslücke geschlossen ist — für die Aufbereitung in `SolReportService::eventsGroup()`: dort muss ein koloniweites Sturm-Ereignis als **eine** aggregierte Zeile in der Gruppe „Begegnungen" erscheinen, nicht als eine Zeile je betroffenem Gebäude.

> **Trigger-Kalibrierung bei koloniweitem Wirkbereich:** Die Sturm-Trigger-Chance skaliert nicht mit der Gebäudeanzahl (`base_chance`/`chance_cap`, kein `chance_per_building`) — sonst wäre die Koloniegröße doppelt eingepreist (Häufigkeit **und** Schadenssumme). Rechenweg im Kommentar zu `game.encounter.storm.*`. **Offen:** `damaged_sp_loss_pct`/`critical_threshold_pct` sind einzelziel-kalibriert; die Regolith-Startreserve (§13.7 „Phase-1-Pacing") ist gegen koloniweite Stürme mit mehreren gleichzeitigen Kritisch-Treffern noch nicht neu gerechnet — nach dem nächsten PlaytestBot-Batch prüfen.

**Additives Risiko-Modell — `health`-Kenntnis und Krankenstation:** Die `health`-Kenntnis trägt wie die Krankenstation additiv zur Seuchenausbruch-Risikoreduktion bei — beide Werte werden gemeinsam gegen denselben Wirkungsdeckel (`plague_risk_reduction_cap`) summiert, kein Konkurrenz-/Stack-Ausschluss (Präzedenzfall: additive Kombination mehrerer unabhängiger Quellen auf denselben Effekt ist Projektstandard, siehe `construction`+`trade` auf dem Bau-AP-Rabatt-Pool, §13.3). Exakte Werte: `config/game.php` → `health_plague_risk_reduction_per_lv`.

### Vorwarnung & Protokollierung

Ein Ereignis kündigt sich 1 Sol vorher als `colony_log`-Eintrag an (Kategorie „Gefahr", entity-chip auf das betroffene Gebäude/den Harvester verweisend) — analog zum bestehenden Tiefenscan-Vorwarn-Muster, aber ohne AP-Kosten. Das gibt aufmerksamen Spielern ein Zeitfenster für eine Reparatur, bestraft aber niemanden, der die Warnung übersieht.

**Berechnungszeitpunkt und Sichtbarkeit sind zwei getrennte Dinge — beide müssen stimmen.** Der Ausgang ist bereits deterministisch (Gebäudezustand entscheidet, siehe Ausgangsstufen-Tabelle oben) und wird beim Sol-Wechsel berechnet, auf Basis des Zustands am Ende des vorgewarnten Sols — also inklusive jeder Reparatur, die der Spieler in der Zwischenzeit noch vorgenommen hat. Damit ist "Vorwarnung Sol N, Berechnung beim Übergang Sol N→N+1" bereits das richtige Modell, keine Verzögerung ohne Wirkung. Die *Sichtbarkeit* muss diese Kausalität aber tragen, sonst wirkt ein längst berechnetes Ergebnis wie unmotivierter Flavourtext: Der Ausgang gehört sichtbar in den Sol-Report des Solwechsels, der ihn ausgelöst hat (Gruppe „Ereignisse", analog zu anderen dort bereits aufbereiteten Vorkommnissen), nicht nur beiläufig ins Kolonieprotokoll ohne erkennbaren Bezug zur vorherigen Vorwarnung. Die Ausgangsmeldung selbst soll zudem den Grund benennen (z.B. den Gebäudezustand zum Ereigniszeitpunkt), nicht nur das Ergebnis ("abgewehrt") — sonst liest es sich, als sei nichts berechnet worden, obwohl es das war.

**Onboarding:** Beim ersten Kolonistengefahr-Ereignis eines Runs erscheint ein einmaliger Hint ("Gebäude mit niedrigem Zustand sind anfälliger für Zwischenfälle — regelmäßige Reparatur zahlt sich doppelt aus"), analog zu bestehenden One-Shot-Hints. Kein neuer Hint-Mechanismus nötig, nur ein neuer Trigger-Key im bestehenden System.

### Offene Punkte

- Exakte Basis-Chancen/Sol und SP-Verlust-Prozentsätze sind Richtwerte — Kalibrierung nach erstem Playtest.
- Ob Seuchenausbruch als eigenständiges Ereignis oder als Eskalationsstufe des bestehenden Hunger-Malus (§4a Organika) implementiert wird, ist eine Umsetzungsentscheidung für game-developer — design-seitig gleichwertig.
- **Sol-Report:** Sturm-Ausgänge erscheinen als eine aggregierte Zeile im Sol-Report (`SolReportService`, `encounter.storm_resolved`). **Offen:** Geologische Instabilität und Seuchenausbruch stehen nur im Kolonieprotokoll, nicht im Sol-Report — Fix: beide Event-Keys in `SolReportService::eventsGroup()` aufnehmen, inkl. Zustandsbegründung in der Detailzeile (ROADMAP T7).

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

### Effekte wirken direkt aus dem Kenntnis-Level

Kenntnis-Effekte werden **automatisch** wirksam, sobald die Kenntnis das nötige Level erreicht hat — ohne dass sie einem Berater zugewiesen werden muss. Es gibt keine Berater-Zuweisung, keine Zuweisungs-UI und keine Slot-Beschränkung für Kenntnis-Effekte.

Bereits implementierte Effekte (`config/knowledge.php`):

- `geology` erhöht den Harvester-Ertrag je Level (`game.geology_harvester_bonus_per_level`, §13.7) und senkt das Risiko Geologischer Instabilität (§9).
- `agronomy` erhöht die Agrardom-Produktion je Level (`game.agronomy_agrardom_bonus_per_level`).
- `health` senkt additiv zur Krankenstation das Seuchenausbruch-Risiko (§9).
- `defense` senkt das Sturm-Risiko (§9).
- `trade` gibt einen Preisbonus auf allen drei Handelskanälen (`trade_price_bonus_per_lv`, §4).
- `construction`, `trade` senken additiv die AP-Kosten von Gebäude-Levelups (§13.3) — glockenförmig über die Level gestaffelt (`ap_cost_reduction_per_lv`). `cartography` senkt stattdessen eigenständig die Navigation-AP-Kosten von Tile-Erkundung und Hangar-Missions-Reisekosten (siehe §13.3).
- `trade` erhöht zusätzlich die Zahl gleichzeitig aktiver Cantina-Angebote (§12), siehe `bar_offer_boost_per_lv`.
- `agronomy`, `health`, `defense` wirken auf das Vertrauen (§14), siehe `trust_per_lv`.
- Analytik-Labor Lv4/5 senkt die AP-Kosten von Kenntnis-Levelups (§13.3) — kein Kenntnis-, sondern ein Gebäudeeffekt, hier der Vollständigkeit halber.

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

Die Bar ist ab CC Lv2 verfügbar. Sie ist der Ort des Handels — verkörpert durch den Reisenden Händler **Corvan Ashe** (`docs/characters/merchant.md`, Kanal 3/`MerchantService`) — und erst in zweiter Hinsicht der Ort für Events und Missionen (übrige Cantina-Charaktere aus `config/characters.php`).

**Zwei Angebotsquellen, klar getrennt:**

1. **Corvan — der gesamte Credits-Handel.** Corvan erscheint regelmäßig (Intervall `config/game.php → merchant`) und bringt bei jedem Besuch zwei Ebenen mit:
   - **Alltagsgeschäft (bei jedem Besuch):** Commodity-Handel gegen Credits — Kauf (Credits→Ressource, mit Losgröße an die Zahlungsfähigkeit der Kolonie gebunden) und Verkauf (Organika→Credits, mehrere Lose je Besuch, §4b „Pfad-C-Hebel"). Ein unerschwingliches Kaufangebot entfällt einfach, es gibt keinen Barter-Fallback.
   - **Kuratiertes Sonderinventar (Sub-Chance je Besuch):** AP-Pakete, Schiffe, Information, Einmal-Items — die Kategorie-Tabelle unter Kanal 3.
2. **Anonyme Gästerotation — nur Tauschhandel.** Pro Sol erscheinen 0–2 Gäste (Dax, Voss, …), jeder mit einem Ressource↔Ressource-Angebot, das 2–4 Sole gültig ist (abhängig vom Bar-Level). Kein Kauf, kein Verkauf gegen Credits ohne Corvan.

**Konsul-Rang-Skalierung:** Ohne Konsul erscheint Corvans Alltagsgeschäft seltener, aber nicht nie; mit Konsul häufiger und zu besseren Konditionen (`trader_discount`) — der Konsul pflegt die Kontakte, die Corvan öfter vorbeikommen lassen. Die genaue Losanzahl pro Besuch ist ein Playtest-Kandidat, bis eine Credits-Bilanz über den Run eine Zielgröße liefert (§4b).

**Orin ist nicht Teil dieses Kanals.** Orin (`corporate_rep`) ist ausschließlich an den Harvester-Zweitinstanz-Deal gebunden (§4c Weg A), mit eigenem Spawn-Check außerhalb von `BarService`/`MerchantService`.

**Cantina-Verhandlung (Risiko-Handel)** steht für beide Angebotsquellen zur Verfügung (unten).

Der Spieler entscheidet pro Angebot: annehmen oder ablehnen. **Annehmen kostet AP** aus dem gemeinsamen Pool (§13.1) — der Handel konkurriert damit direkt mit Bau und Kenntnissen um dieselbe Kapazität. Exakte Kosten: siehe `config/game.php`.

**Handelsvertrag (garantierte Einnahmequelle):** Kauf- und Tauschangebote erzeugen kein Credits-Einkommen — sie kosten Credits oder sind ressourcenneutral. Das strukturelle Handelseinkommen der Kolonie ist deshalb der Handelsvertrag: kein Bar-Angebot (kein Karten-Slot, keine Annahme, keine AP-Kosten), sondern eine **passive Cr/Sol-Einnahme**, strukturell identisch zur Relaisvergütung (§3). Sie fließt automatisch pro Tick, solange ein Konsul der Kolonie zugewiesen ist **und** die Cantina mindestens Lv1 steht; der Konsul vermittelt laufende Handelsverträge im Hintergrund, die Kolonie liefert dafür keine Ressourcen. Config: `game.credits.consul_contract_income_per_rank`, verarbeitet in `GameTick` im selben Schritt wie `nexus_subsidy`/`relay_bonus_per_uplink_level`. Werte nach Konsul-Rang:

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

**Ablauf — zwei Schritte:** Verhandeln führt das Geschäft nicht sofort aus, sondern verbessert bei Erfolg nur die Konditionen des Angebots — der Spieler sieht das Ergebnis und bestätigt danach explizit mit **Annehmen**.

1. Verfügbarkeits- und Ressourcen-Check wie bei Annehmen (Give-Seite muss gedeckt sein — sonst Fehler `bar_offer_insufficient_resources`, kein Würfeln auf ein Geschäft, das ohnehin nicht zustande kommen könnte). Ein bereits verhandeltes Angebot kann nicht erneut verhandelt werden.
2. AP-Kosten werden abgebucht (`ap_cost_negotiate`, höher als `ap_cost_accept`) — unabhängig vom Ausgang.
3. Einmaliger Erfolgs-Wurf, Konsul-Rang-abhängig (`negotiate_success_chance`).
   - **Erfolg:** Die Konditionen des Angebots (`give_amount`/`get_amount`) werden dauerhaft auf die verbesserten Werte aktualisiert (`negotiate_bonus`, gleiche Formel-Achse wie `trader_discount`, s.u.) und das Angebot als verhandelt markiert. Der Handel selbst führt sich **noch nicht** aus — der Verhandeln-Button wird gesperrt, der Annehmen-Button bleibt aktiv und zeigt jetzt 0 AP (die Kosten wurden bereits mit der Verhandlung bezahlt). Erst ein Klick auf Annehmen überträgt die Ressourcen.
   - **Fehlschlag:** Kein Handel. Das Angebot ist **sofort und vollständig verloren** (gelöscht/verfallen) — kein zweiter Versuch, auch kein nachträgliches "Annehmen" zu den alten Konditionen. Die verlorene Chance ist die eigentliche Konsequenz, nicht die AP.
4. **Kein Trust-Malus.** `trade_blocked` (§13/§14) bleibt für einen anderen Fall reserviert (blockierter Handel, nicht gescheiterte Verhandlung) — eine fehlgeschlagene Verhandlung soll bestraft, aber nicht zusätzlich über Vertrauen abgestraft werden, sonst wird der Button nie benutzt.

**Warum die Chance den Preis macht, nicht die AP:** Bei `ap_cost_accept = 1` und max. 2–6 gleichzeitigen Angeboten kann ein Konsul-Halter praktisch jedes Angebot verhandeln, egal wie hoch `ap_cost_negotiate` gesetzt wird — AP war hier nie ein wirksamer Deckel. Der eigentliche Preis ist der komplette Verlust des Angebots bei Fehlschlag.

> **Zu prüfen im Handels-Balancing:** Mit dem gemeinsamen Pool (§13.1) konkurrieren Handelsgeschäfte direkt mit Bau und Kenntnissen — AP ist damit erstmals ein echter Deckel für Vielhandel. Ob `ap_cost_negotiate` dadurch schon von selbst wirkt oder weiterhin die Verlust-Mechanik tragen muss, ist offen.

Die Erfolgschance und der Bonus-Betrag steigen mit Konsul-Rang. Der Zusatz-Bonus wirkt auf dieselbe Achse wie `trader_discount` bei der Angebots-Generierung, aber additiv obendrauf auf das **konkrete, bereits generierte** Angebot (nicht auf einen neuen Wurf). Kein zweites Formel-System — nur eine zweite Anwendung derselben Formel.

Exakte Erfolgschancen und Bonussätze: siehe `config/game.php → bar` (`negotiate_success_chance` / `negotiate_bonus`).

> ⚠️ BALANCE CONCERN: Kalibration gegen das Risiko/Reward-Gleichgewicht. Zu hohe Erfolgschance oder zu großer Bonus macht Verhandeln zur dominanten Strategie ohne echtes Risiko. Nach erstem Playtest kalibrieren (siehe `config/game.php → bar` für Schwellenwertbeispiele).

---

### Kanal 2: Nexus-Handelsschiffe (Fallback, teuer, garantiert)

> **Status: nicht implementiert, Owner-Frage F5 (ROADMAP):** Der Werkstoff-Direktimport über die Uplink-Station (§3, §4) deckt die Sicherheitsnetz-Funktion bereits ab. Entscheidung offen, ob dieser Kanal gestrichen oder als Direktimport umdefiniert wird.

Nexus schickt auf Anfrage offizielle Handelsschiffe. Immer verfügbar — auch ohne Händler-Berater, auch ohne Bar. Das Sicherheitsnetz gegen Progression-Locks.

Lieferzeit und Preisaufschlag hängen vom Konsul-Rang ab — ohne Berater sind beide nachteilig. Höhere Ränge senken beide Parameter (schnellere Lieferung, bessere Konditionen). Exakte Werte: siehe `config/game.php`.

**Anfrage-Mechanik:** Der Spieler sendet eine Anfrage über den Nexus-Funk (Nachricht an "Nexus Command"). Nexus antwortet nach 1–3 Solen (abhängig vom Konsul-Rang) mit einem Protokoll-Ereignis, das die Lieferung bestätigt und die Ressourcen direkt zur Kolonie transferiert. Kein eigenes Fleet-Objekt — das Nexus-Schiff erscheint nicht auf der Karte.

**Ablauf:**
1. Spieler öffnet den Nexus-Funk → "Nexus-Handelsschiff anfordern" → wählt Ressource + Menge
2. Credits-Betrag wird sofort eingefroren (reserviert)
3. Nach Lieferzeit: Protokoll-Ereignis "Nexus-Lieferung eingetroffen", Ressourcen gutgeschrieben, Credits abgebucht
4. Kann nur 1 offene Anfrage gleichzeitig haben

---

### Kanal 3: Reisender Händler (selten, hochwertig)

Implementiert über `MerchantService` + `config/game.php → merchant`; Spawn-Check in `GameTick` (Schritt 14).

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

### 13.1 Ein gemeinsamer AP-Pool

**Es gibt genau einen AP-Pool** — eine Kolonie-Kapazität, in die alle Berater einzahlen, statt getrennter, nicht mischbarer AP-Typen je Domäne.

**Begründung:** Getrennte Pools erzeugen keine Entscheidung. Wenn Forschungs-AP nur für Forschung taugt, gibt es nichts abzuwägen — der Spieler gibt sie aus, weil sie sonst verfallen. Ungenutzte Pools verfallen still (dokumentiert für `economy` und `strategy` in §16), während der begehrte Pool leerläuft. Mit einem gemeinsamen Pool wird jede Ausgabe zu einer echten Allokationsentscheidung: **jeder Punkt in ein Gebäude ist ein Punkt, der nicht in eine Kenntnis, eine Mission oder ein Handelsgeschäft geht.**

**Domänen bleiben als Begriff erhalten** — sie beschreiben, *wofür* AP ausgegeben werden, nicht, *woher* sie kommen:

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

> **Geplant (ROADMAP A4): `decay.overcap_factor` 2.0 → 1.5.** Bei Überschreitung des Supply-Caps verdoppelt sich die Instandhaltung — bei ~7 AP/Sol Basislast springt der Anteil von 32 % auf 64 % des Pools. **Das** ist der „ab Sol 50 steht der Spieler still, ohne die Ursache zu erkennen"-Fall; er entsteht nicht aus dem Verfall, sondern aus diesem Multiplikator. Zusätzlich muss Over-Cap ein **sichtbarer Zustand** sein (Dashboard + Protokoll-Meldung), nicht ein stiller Faktor, und es muss einen Gegenzug geben — zu prüfen ist, ob freiwilliger Abriss über die UI erreichbar ist (§13 „AP-Verbrauch" nennt „Reparatur/Abbau").

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

**Bonusquellen:**

| Quelle | Status | Wo |
|---|---|---|
| Kenntnis-Level nach Domäne (Bau-Projekt-Rabatt-Pool) | implementiert | `ProjectBonusService::buildingApDiscountPercent()`, `config/knowledge.php → *.ap_cost_reduction_per_lv` |
| Analytik-Labor Lv4/5 — Domänen-Effizienzbonus „Wissen" | implementiert | `config/buildings.php → sciencelab.knowledge_ap_cost_reduction_per_lv` |
| Berater-Rang | **geplant** (ROADMAP Stufe 3) | vorgesehen: `config/game.php → project_cost_bonus` |
| Koloniereife (CC-Level) | **geplant** (ROADMAP Stufe 3) | dito |

**Bau-Projekt-Rabatt-Pool:** Bau ← `construction`, Wirtschaft ← `trade`. Beide Kurven sind glockenförmig über die fünf Level (Peak Lv2–4) und wirken additiv auf **alle** Gebäude-Levelups inklusive Kommandozentrale — nicht nach Projekttyp getrennt, weil nur Bau-Projekte existieren. `cartography` gehört nicht zu diesem Pool: die Kenntnis senkt eigenständig die Navigation-AP-Kosten von Tile-Erkundung (`ColonyTileService::exploreTile()`) und Hangar-Missions-Reisekosten (`HangarService::dispatchShip()`), `config('knowledge.cartography.nav_ap_reduction_per_lv')`.

**Analytik-Labor Lv4/5:** Gibt dem Laborausbau über die reinen Kenntnis-Gates (Lv1–3) hinaus einen eigenen Effekt — senkt die AP-Kosten für Kenntnis-Levelups, additiv und unabhängig vom Gebäude-Rabatt-Pool. Rührt an nichts, was pro Run gezogen wird (§10) — reine Effizienzsteigerung auf bereits freigeschaltete Kenntnisse.

Ein **Mindest-Kostenanteil** (`project_min_cost_factor`) verhindert, dass Projekte auf null fallen — das ist eine Leitplanke für spätere Bonusquellen (Events, Missionsbelohnungen, Run-Modifier), keine aktive Regel zum Start. Wichtig, das so zu lesen, damit später niemand gegen einen Deckel kalibriert, der gar nicht wirkt.

**Boni gelten nur für Projekte, nicht für Handlungen.** Dadurch wächst der Handlungsanteil am Pool über den Run relativ an — das späte Spiel verschiebt sich von selbst Richtung Ausführung. Das ist beabsichtigt und trägt den Kipppunkt aus 13.2 mit.

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

**Es gibt kein AP-Gleichgewicht, ab dem die Instandhaltung den gesamten Zufluss bindet.** `GameTick::processBuildingDecay()` zieht `decay_rate` je Gebäude-Instanz ab — **unabhängig vom Level**. Weil der Gebäudekatalog endlich ist (13 Typen, dazu die Instanzen von Wohnhabitat, Hangar und Harvester), hat die Instandhaltung in AP eine harte Obergrenze, die deutlich unter einem mit Beratern ausgebauten Pool liegt. Was stattdessen gilt: **eine wachsende, sichtbare Last.** Der Instandhaltungsanteil des Pools wächst über den Run — moderat früh, spürbar im Endausbau (Zielwert bei der Zielkolonie siehe G2 in §13.7). Das ist Gegenwind, kein Stillstand, und passt zu „kein Leerlauf, aktives Spielen wird belohnt" (§1.1) besser als ein Gleichgewicht, das den Spieler einfriert.

**Die eigentliche Wachstumsgrenze ist Regolith.** Reparatur kostet Regolith je SP, Level-Ups kosten Regolith, Errichtungen kosten Regolith. Dagegen steht der Harvester mit einem Grundeinkommen plus Missionen, Events und Handel. Diese Bilanz — nicht der AP-Pool — entscheidet, wie groß eine Kolonie werden kann; sie gehört ins Dashboard (§13.4).

#### Harvester: kein Level-Up, höchstens zwei Instanzen (Owner-Entscheidung)

Der Harvester hat **kein Level-Up** (`max_level = 1`). Er liefert je Standort ein Grundeinkommen an Regolith, das mit der Erschöpfung des Tiles sinkt (§4c); Wachstum kommt aus Kenntnissen, Missionen, Handel und — als nicht garantierter Bonus — aus einer zweiten Instanz (Deckel 2, Bezugswege §4c). Er ist zugleich das einzige bewegliche Gebäude und wird pro Run mehrfach umgesetzt. Damit ist Regolith kein passives Einkommen, sondern **aktives Spiel** (§1.1).

#### Regolith-Beschaffung: alle drei Pfade müssen die Grundbedürfnisse decken

**Verbindliche Anforderung — §4b „Paritäts-Anforderung".** Die Pfade dürfen sich im *Wie* unterscheiden, nicht im *Ob*. Der Harvester ist der gemeinsame Sockel, den jede Kolonie unabhängig von der Pfadwahl hat; was einen Pfad ausmacht, ist der Hebel obendrauf:

| | Quelle | Kostenprofil |
|---|---|---|
| **Sockel (alle Pfade)** | Harvester, 1 Instanz (§4c) | keine (passiv), Umzüge kosten AP |
| **A — Analytik** | Kenntnis `geology` erhöht die Harvester-Ausbeute je Level (`game.geology_harvester_bonus_per_level`, kumulativ) | einmalig hoch (AP bis zum Ziellevel), danach null laufende Kosten |
| **B — Hangar** | Frachter auf `mission_supply_run` (Regolith je Umlauf, `config/missions.php`) | laufend: Navigation-AP, Organika-Proviant, Verschleiß |
| **C — Cantina** | **kein Regolith-Hebel** — Pfad C liefert Credits (§4b „Pfad-C-Hebel") | — |

Die Profile sind bewusst gegensätzlich: **Analytik** verbessert den Sockel selbst — teuer im Aufbau, danach dauerhaft geschenkt, keine Logistik. **Hangar** legt einen zweiten Strom daneben — billig im Einstieg, aber jeden Sol Aufwand. **Cantina** kauft zu und wandelt Überschuss in Credits — maximal flexibel, an Credits und Angebotslage gebunden. Ob A und B die Regolith-Lücke der Zielkolonie tatsächlich schließen, rechnet §13.7 nach.

**Cantina-Angebote:** Die Losgröße von Corvans Kaufangeboten ist an die Zahlungsfähigkeit der Kolonie gebunden (höchstens ein Anteil des Bestands), damit Angebote nicht regelmäßig an „Not enough resources" scheitern. *Offen:* Tauschrichtung der anonymen Gäste nach Bestand wählen statt würfeln (Give = größter Überschuss, Get = knappste Ressource) — der Zufall bliebe in Preisvarianz, Gästezahl und Gültigkeitsdauer erhalten (ROADMAP „Offene Pfad-Paritäts-Fragen").

> **Nachrüstoption, falls das späte Spiel im Playtest schlaff wirkt:** Reparaturkosten mit dem Level skalieren — `AP je SP = 1 + floor((level−1)/3)`. Die Instandhaltung skaliert dann mit der **Tiefe** und koppelt sich an den Supply-Cap (§6). Die Alternative `decay_rate × level` ist thematisch schwächer (warum verfällt ein größeres Gebäude schneller?) und verdoppelt zusätzlich den Regolith-Abfluss.

---

### 13.6 AP-Zahlensatz

Die AP-Struktur ist Owner-Entscheidung: Grundwert des gemeinsamen Pools (`config/game.php → ap.base`), Berater-Beitrag je Rang (`advisor.ap_per_rank`), progressive Projektkostenkurve `f(L)` mit `f(1) = 0.5` und additive Bonus-Kurve (§13.3). Der Grundwert ist gegen die Instandhaltungslast breiter Kolonien (§4c „im Zweifel Instanz") kalibriert, damit alle drei Pfade ohne strukturelle Enge machbar bleiben — auch Pfad B mit seinen laufenden Kosten.

**Die tragenden Elemente:** Basis-AP-Wert, Gebäude-Basis-Kosten-Klassen (gestaffelt nach Gebäude-Rolle), eine progressive Kostenkurve pro Level, Berater-AP-Beitrag pro Rang (progressiv) und Kostenboni durch Kenntnisse/Domänen.

**Stellschrauben, wenn sich das System im Playtest als unausgewogen erweist** — in dieser Reihenfolge: Basis-Wert, dann Gebäude-Klassen, dann Kurvensteilheit, dann Berater-Beiträge. Alle sind einzeln in `config/` kalibrierbar, ohne den Regeltext zu ändern.

#### Ziel-Endzustand (guter Run, Sol ~75–80)

Ein typischer erfolgreicher Run erreicht: die Mehrheit der Gebäudetypen (nicht alle), moderate Gebäude-Level, volle Berater-Slots (hauptsächlich Rang 2), einige Kenntnisse auf mittlerem Level, ungefähr zwei Drittel der Bauplätze belegt. Ungenutzte Optionen sind Absicht: Der Spieler soll sehen, was offenblieb — kein Erreichen einer perfekten Optimalität, sondern ein befriedigender Zustand mit sichtbarem „hätte ich auch noch tun können".

#### AP-Grundwert und Berater-Beitrag

Der gemeinsame Pool hat einen Basiswert und wächst mit Berater-Rängen; der Beitrag steigt mit dem Rang, erlaubt aber kein exponentielles Wachstum — eine bewusst flache Kurve. Die AP-Rate wird durch drei Faktoren gestaffelt: **Berater-Anzahl und -Rang** (progressiv), **Instandhaltungslast** (wächst über den Run), **Projektkosten** (initial niedrig, später höher). Das Pool-Wachstum über einen Run ist moderat (Faktor ~2–3 vom Anfang zum Ende), kombiniert mit Kostenreduktionen durch Boni. Der Vertrauens-Multiplikator (`trust.ap_multiplier`, §14) kommt obendrauf.

#### Projektkosten je Gebäudelevel

```
ap_cost(building, L) = round(base_ap[building] × f(L))
f(1) = 0.5
f(L≥2) = 1 + 0.4 × (L−2)
```

Das Errichten (Level 1) kostet bewusst weniger als Level-Ups — Anreiz für breite Kolonien früh (weniger AP pro neues Gebäude) und tiefe Spezialisierung später. Gebäude sind nach Rolle in Kostenklassen gruppiert (Produktion, Klein, Mittel, Groß, Kommandozentrale als Sonderfall), jede mit eigenem Basis-AP. Produktionsgebäude sind am billigsten — ihre Glockenkurve (`game.production_curve`) setzt bereits einen Deckel.

> **Implementierungsstand:** Die Kurve `f(L)` ist **geplant** (ROADMAP Phase 3o, Stufe 3). Aktuell gilt je Gebäude ein flacher `ap_for_levelup`-Wert für jedes Level; die Kostenklassen sind in `config/buildings.php` angelegt.

**Early-Game-Tempo:** Breite Kolonien früh (billige erste Level) über tiefe Spezialisierung, kombiniert mit den Supply-Cap-Grenzen aus §6 — zusammen das Breite/Tiefe-Dreieck ohne optimalen Pfad. Befristete AP-Boni oder Vorbau in der Startkolonie sind keine Alternative: erstere wirken dort, wo ohnehin wenig Instandhaltung nötig ist, letzterer zerstört die Lernmomente aus §16.

**Kenntnisse** haben eigene, steigende Kosten je Level (`config/knowledge.php`) — sie skalieren parallel zum Pool-Wachstum.

#### Handlungs-AP

Sofort-Handlungen (Handel, Erkundung, Dispatch) sind gegen den gemeinsamen Pool kalibriert: deutlich billiger als Projekte, aber nicht kostenlos — sie konkurrieren um denselben Pool. Lange Missionen kosten einen nennenswerten Anteil des Pools; Ring-Erkundungen skalieren mit der Entfernung, damit die Karte nicht zu schnell aufgedeckt wird. Werte: `config/game.php` (Handel, Erkundung), `config/missions.php` (Navigation-AP pro Sol). *Geplant (Stufe 3):* Handels-AP (`bar.ap_cost_accept`/`ap_cost_negotiate`) an den gemeinsamen Pool nachziehen.

> **Die Regel „Gelegenheiten sind durch Verfügbarkeit begrenzt" (13.2) ist ohne neue Mechanik erfüllt.** Missionen sind durch Schiffszahl und Rundlaufzeit begrenzt, Bar-Angebote durch `guest_count` und `level_max_concurrent`. Zusammen binden sie einen kleinen Teil des Pools — genau der beabsichtigte Deckel.

#### Balancing-Targets und -Unsicherheiten

Der AP-Haushalt ist gegen drei Ziele kalibriert: (1) Die Zielkolonie ist ohne Glück erreichbar; (2) alle drei Pfade haben tragfähig unterschiedliche Kostenverhältnisse; (3) die Sol-1–4-Rampe erzeugt sichtbare Fertigstellungen pro Sol, nicht nur volllaufende Fortschrittsbalken. Zu prüfen im Playtest: Fühlt sich die Instandhaltungslast wie beabsichtigt an (Druck ohne Deadlock)? Sind alle drei Pfade äquivalent tragfähig? Die Credits-Ökonomie ist gegen den Berater-Unterhalt hergeleitet (§18.4), aber nicht gegen eine vollständige Credits-Bilanz über den Run (`docs/gdd-balance-checklist.md` A.4). Befunde führen zu Config-Anpassungen, nicht zu Regeltext-Änderungen.

---

### Slot-System: CC-Level als Gate, Pfadwahl ab Slot 2

Berater-Slots öffnen nicht allein über CC-Level, sondern analog zu den Pfad-Gebäuden: durch den Bau eines spezifischen Gebäudes. Slot 1 ist **fest** an den Baumeister gebunden (siehe §16.2 "Designentscheidung zu Rang 1"). Slots 2–4 sind **generisch**: Welcher Beratertyp einen dieser drei Slots belegt, hängt davon ab, welches der drei Pfad-Gebäude der Spieler zuerst/zweit/dritt baut — nicht von einer fest verdrahteten CC-Level→Typ-Zuordnung.

| Gate | Slot | Bindung |
|------|------|---------|
| CC Lv1 | Slot 1 | **fix:** Baumeister |
| CC Lv2 + 1. Pfad-Gebäude (sciencelab/hangar/bar) | Slot 2 | **generisch:** Analytiker/Raumfahrer/Konsul |
| CC Lv3 + 2. Pfad-Gebäude | Slot 3 | **generisch:** Analytiker/Raumfahrer/Konsul |
| CC Lv4 + 3. Pfad-Gebäude | Slot 4 | **generisch:** Analytiker/Raumfahrer/Konsul |

> Es gibt **vier** Berater-Slots. Der Sicherheits-Hub ist kein Slot-Gate (Stratege zurückgestellt, siehe „Die vier Berater-Typen").

**Die drei Pfade** (siehe §4 "Pfadwahl ab Sol 3"):

| Pfad | Gebäude | Beratertyp | Domäne | CC-Gate | Slot |
|------|---------|-----------|--------|---------|------|
| A | Analytik-Labor (sciencelab) | Analytiker | research | CC Lv2 (Pfadwahl) | 2–4 (generisch) |
| B | Hangar | Raumfahrer | navigation | CC Lv2 (Pfadwahl) | 2–4 (generisch) |
| C | Cantina (bar) | Konsul | economy | CC Lv2 (Pfadwahl) | 2–4 (generisch) |

**Gate-Logik (Bau, nicht nur Berater):** Alle drei Pfad-Gebäude sind ab CC Lv2 grundsätzlich baubar — aber gleichzeitig gilt ein zusätzliches Bau-Gate: `Anzahl bereits gebauter Pfad-Gebäude < CC-Level − 1`. Bei CC Lv2 darf also nur **eines** der drei gebaut werden; das zweite schaltet erst bei CC Lv3 frei, das dritte erst bei CC Lv4. Es gibt **keine permanente Ausschließung** — wer bei CC2 die Cantina wählt, bekommt Sciencelab und Hangar bei CC3 bzw. CC4 trotzdem, nur später. Die "Wahl" bei Sol 3 bestimmt **Reihenfolge und Zeitvorsprung**, nicht endgültigen Zugang. Das hält die Entscheidung gewichtig (wer zuerst baut, bekommt den zugehörigen Berater-Slot 1–2 CC-Level früher als bei den anderen beiden Pfaden), vermeidet aber einen harten Lockout, der bei einer frühen Sol-3-Entscheidung zu hart wäre (Nouron-Prinzip: keine bestrafenden Permanent-Konsequenzen für frühe Entscheidungen, siehe §1.1).

**Reihenfolge-Auflösung:** Der Slot, den ein Pfad-Gebäude belegt, ergibt sich aus der **Baureihenfolge** dieses Gebäudes relativ zu den anderen beiden — nicht aus dem Gebäudetyp selbst. Werden (im seltenen Fall ausreichender Ressourcen-Reserven) zwei Pfad-Gebäude im selben Sol fertiggestellt, entscheidet ein fixer, nicht spielerseitig beeinflussbarer Tie-Break in der Reihenfolge **Sciencelab → Hangar → Cantina** (aufsteigend nach `building_id`: 31 < 44 < 52). Dieser Tie-Break ist ein reines Implementierungsdetail ohne Spielerrelevanz außerhalb des Edge-Case.

> **Kostenbalancing der Pfad-Gebäude:** Die drei Pfad-Gebäude sind in Regolith und Supply gleich bepreist (`config/buildings.php`, §13.7 G4). Die Pfade unterscheiden sich über ihren Hebel-Mechanismus (§4b) und ihre AP-Kostenklasse, nicht über den Baupreis. Schiffe kosten kein Supply (§6).

---

### Datenmodell: `advisors`-Tabelle

Jeder Berater ist ein eigener Datensatz. Die Tabelle hat folgendes Schema:

```
advisors
├── id                      ← eindeutige ID des Beraters
├── user_id                 ← Eigentümer (immer gesetzt)
├── personell_type          ← 'construction' | 'research' | 'navigation' | 'economy'
├── colony_id               ← nullable: aktiv auf dieser Kolonie
├── rank                    ← 1 = Junior | 2 = Senior | 3 = Experte
├── active_ticks            ← kumulierter Zähler für Rang-Aufstieg
└── unavailable_until_tick  ← Erholungsphase nach Burnout (NULL = verfügbar)
```

> Berater sind colony-scoped — sie verlassen die Kolonie nicht. Ein Flottenkommandanten-Modell (`fleet_id`, `is_commander`) ist nicht vorgesehen.

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

> **Stratege zurückgestellt:** Ein fünfter Beratertyp (`strategy`) ist **nicht im Spiel**. Ob er später als eigener Pfad oder als Modifikator der drei anderen Pfade kommt, ist offen (`docs/gdd-balance-checklist.md` A.4).
>
> **Was das konkret heißt:** Berater-Slot 5 entfällt; es gibt maximal **vier** gleichzeitig zugewiesene Berater. Der **Sicherheits-Hub bleibt als Gebäude bestehen** — er behält seine drei eigenständigen Effekte (Vertrauens-Bonus, Event-Dämpfung, Recycling, §4), verliert aber seine Funktion als Slot-Gate. Die vom Strategen getragenen Informationsleistungen (Gefahren-Vorwarnung mit Prognose, Ziel-Erreichbarkeits-Prognose) wandern in das Kommandozentrale-Dashboard (13.4), wo sie ohnehin besser aufgehoben sind.
>

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

Missions-Auflösung läuft in **Tick-Schritt 12** (Advisor Ticks), nach AP-Berechnung und Burnout-Prüfung. Reihenfolge innerhalb dieses Schritts: erst AP-Update, dann Burnout-Check, dann Missions-Auflösung.

---

### Rang-System

Jeder Berater hat einen von drei Rängen. Der Rang bestimmt, wie stark der Berater den gemeinsamen AP-Pool erhöht (§13.1) und wie hoch sein laufender Upkeep in Credits ist — beide Werte wachsen mit dem Rang, additiv auf den gemeinsamen Pool angerechnet, unabhängig von der Domäne des Beraters.

Exakte Werte (AP-Bonus je Rang, Upkeep, Rang-Aufstiegs-Schwellen in aktiven Ticks, Beförderungskosten): siehe `config/game.php → advisor` und `docs/game-reference.md#5-berater-advisors-hire-kosten--ap-beiträge`.

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

Der Grundwert des gemeinsamen Pools steht in `config/game.php → ap.base` (Owner-Entscheidung, §13.6). Vertrauens- und Seuchen-Multiplikatoren (§9, §14) wirken zusätzlich multiplikativ auf den fertigen Grundwert+Bonus-Betrag, siehe `AdvisorService::getApBreakdown`. Exakter Wert: `docs/game-reference.md#9-action-points-ap`.

### AP-Verbrauch

**Projekte (mehrere Sole, siehe 13.2):**

1. **Bauen/Ausbauen:** AP werden beim Investieren gesperrt (`invest('add')`), bis die Projektkosten erreicht sind.
2. **Kenntnisse:** identisch — bereits implementiert über `AbstractTechnologyService::_invest`.

**Handlungen (sofort, siehe 13.2):**

3. **Reparatur/Abbau:** AP in Höhe der veränderten `status_points`.
4. **Erkundung/Dispatch:** Tile-Erkundung ring-gestaffelt (1/2/3 AP, §4a), Außenmissions-Dispatch `sol_distance × 2` AP (§8b).
5. **Handelsgeschäfte:** AP je angenommenem oder verhandeltem Angebot (§12).

### Implementierung

- `app/Services/AdvisorService.php` — AP-Berechnung (`getApBreakdown`), Sperrung
- `app/Services/Techtree/AbstractTechnologyService.php` — AP-Verbrauch beim Investieren
- `app/Services/ColonyTileService.php`, `app/Services/HangarService.php`, `app/Services/BarService.php` — Handlungs-AP für Erkundung, Dispatch und Handel
- Tabelle `locked_actionpoints`: `(tick, scope_type, scope_id, personell_type, spend_ap)` — `personell_type` ist seit der Zusammenlegung nur noch Auswertungsmerkmal („wofür wurde investiert"), keine Pool-Trennung.

### Berater-Burnout (Auswirkung auf AP)

Wenn ein Berater einen Burnout erleidet (Wahrscheinlichkeitsmechanik — Details in §7), fällt sein AP-Beitrag für die Dauer der Erholung auf null zurück. Der gemeinsame Pool sinkt um genau diesen Beitrag; zusätzlich entfällt sein Effizienzbonus in seiner Domäne (13.3).

**Beispiel:** Ein Senior-Analytiker (rank=2) trägt normalerweise 20 AP/Sol zum Pool bei. Bei Burnout fehlen diese 20 AP/Sol — die Kolonie arbeitet insgesamt langsamer, aber kein Bereich fällt vollständig aus. Das ist die gewünschte Abschwächung gegenüber dem alten Modell, in dem ein Burnout eine ganze Domäne auf den Grundwert zurückwarf.

**Dauer:** Abhängig vom Rang (Junior 15, Senior 10, Experte 5 Sole — Richtwerte, noch nicht in Config abgebildet; siehe „Implementierungsstand" in §7).

**Sichtbarkeit:** Die Berater-Übersicht zeigt einen "Pause"-Zustand mit Countdown bis zur Rückkehr. Protokoll-Ereignis informiert beim Einsetzen.

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
- Kritische Warnungen (z.B. Gebäude-Decay unter Schwellwert) feuern **immer** im Kolonieprotokoll — auch ohne Baumeister. Berater liefern Vorwarnzeit und Kontext, nicht die letzte Warnung selbst.
- Pro Berater: maximal 2–3 zusätzliche Informationspunkte. Optionale Details auf Tooltip-Ebene, nicht im Hauptscreen.
- Discovery-Moment beim ersten Einstellen eines Beraters: Onboarding-Hint zeigt was neu sichtbar wird.

**QoL-Infos nach Beratertyp:**

| Berater | Screen | Primär-Information | Sekundär-Information |
|---------|--------|--------------------|----------------------|
| Baumeister | Colony-View | Decay-Prognose pro Gebäude ("in ~4 Solen Level-Down") | Kritische Gebäude hervorgehoben (SP < 30% Max) |
| Analytiker | Techtree | "Sole bis Level X beim aktuellen AP-Fluss in diese Kenntnis" | Priorisierungshinweis für offene Run-Aufgaben |
| Konsul | Cantina | Händler-Einschätzung "guter / durchschnittlich / schlechter Deal" (kontextuell, nicht binär) | Restlaufzeit-Countdown für Angebote prominent statt versteckt |
| Raumfahrer | Hangar | Aufgebrochene Missionszeit ("X Sole Hinweg + Rückkehr Sol Z") | Verschleiß-Prognose pro geplantem Dispatch (§7) |

> Die Ziel-Erreichbarkeits-Prognose („Aufgabe X: ✓ in ~12 Solen; Aufgabe Y: ✗ — 400 Cr fehlen") und die Ausgangs-Prognose bei Gefahren-Vorwarnung (§9) sind keiner Berater-Informationsebene zugeordnet — sie gehören ins Kommandozentrale-Dashboard (§13.4).

> **⚠️ Balance — Konsul:** Händler-Einschätzung darf nicht binär sein ("kaufen / nicht kaufen"), sonst entwertet sie die Handelsentscheidung. Kontextuell: "günstig für Werkstoffe — du hast davon aber bereits 200" ist besser als "guter Deal".

> **⚠️ Balance — Erster Cantina-Besuch:** Konsul ist erst ab CC Lv2 verfügbar, der erste Händler erscheint früher. Der erste Cantina-Besuch muss immer ein objektiv gutes Angebot zeigen — unabhängig vom Konsul-Status. Sonst entsteht Früh-Spiel-Frustration bei Spielern ohne Konsul.

**Implementierung:** Phase 4 — setzt stabiles Berater-System und abgeschlossene Screen-Redesigns voraus. Keine neuen Datenpunkte nötig (alle Quellen in Config und DB bereits vorhanden), reine UI-Logik. Discovery-Moments integrieren sich in bestehenden Onboarding-Hint-Stack (§16).

---

---
### 13.7 Regolith-Zahlensatz, hergeleitet

Von der Designabsicht her hergeleitet statt aus Bestandswerten fortgeschrieben. Der Satz ist Owner-Entscheidung; die Werte stehen in `config/buildings.php` und `config/game.php`, Lookup in `docs/game-reference.md`. Dieses Kapitel hält die Herleitung fest, damit Playtest-Befunde gegen die richtige Stellschraube laufen.

#### Das Spielgefühl — zuerst, ohne Zahlen

Jede Zahl unten ist auf eine dieser Aussagen zurückführbar. Wo das nicht gelingt, ist sie willkürlich und gehört ersetzt.

| | Aussage |
|---|---|
| **G1** | **Regolith ist nie bequem und nie tödlich.** Der Bestand schwingt um eine niedrige zweistellige Zahl. Ein wachsender Haufen heißt, die Kolonie ist fertig; eine Null heißt, sie stirbt. Beides beendet die Spannung. Im Sockel-only-Spätspiel kriecht der Bestand statt zu schwingen — G1 setzt einen aktiven Pfad-Hebel voraus, wie G2 und G6. |
| **G2** | **Instandhaltung ist Routine, nicht Krise.** Gemessen am Gesamteinkommen (Sockel + aktiver Pfad-Hebel): Sie bindet ~15 % früh und ~40 % bei der Zielkolonie. Unter 10 % ist Verfall Dekoration und die USP fällt weg; über 60 % ist er eine Strafe fürs Bauen. |
| **G3** | **Vernachlässigung kostet ein Level, nicht den Run.** Ein Level-Down ist in 5–8 Solen aufgeholt, ohne Kaskadenrisiko. |
| **G4** | **Errichten ist eine Entscheidung, Level-Up ein Schritt.** Die Errichtung eines Pfadgebäudes kostet 5–8 Sole Sparen, ein Level-Up 1–2. Gilt für die drei Pfadgebäude Sciencelab/Hangar/Bar (einheitlich bepreist). Der Agrardom ist als Pflicht-Ramp-Gate vor CC Lv2 ausdrücklich ausgenommen — kein Pfadgebäude, steht nicht zur Abwägung; reine Infrastrukturgebäude (Wohnhabitat, Krankenstation, Sicherheits-Hub, Tempel, Monument) folgen anderen Kriterien. |
| **G5** | **Der Spieler soll 2–4 Mal pro Run an Regolith scheitern** — nicht dauernd (Grind), nicht einmal (Gate). Playtest-Trigger: mehr als 4 blockierte Sole pro Run. G5 dient zugleich als Toleranzbudget für die Restlücke eines einzelnen Hebels (Punkt 5 unten). |
| **G6** | **Der Sockel trägt das Überleben, der Pfad-Hebel das Wachstum.** Der Sockel allein trägt eine spielbare, aber unterdimensionierte Kolonie (~57 % der Zielgröße) — genug, um nicht zu scheitern, zu wenig für die Zielkolonie. Der gewählte Pfad-Hebel schließt die Lücke auf annähernd 100 %, sobald er aktiv genutzt wird. Ein Run ohne jede Pfad-Aktivität ist im Design nicht vorgesehen (die Pfadwahl ist ab CC Lv2 verpflichtend, §4b). |
| **G7** | **Der Spieler muss im Kopf rechnen können.** „Ich mache 20 pro Sol, das kostet 95, das sind fünf Sole." (Illustrativer Platzhalter für runde Kopfrechenzahlen — die Referenzwerte sind Frischwert und Zyklusmittel des Harvesters, siehe unten; 95 ist der einheitliche Preis der drei Pfadgebäude.) |

**G4 ist die wichtigste Aussage**, weil sie Regolith und AP entkoppelt: Das AP-Modell macht es genau umgekehrt (`f(1) = 0.5` — Errichten AP-billig, Level-Up AP-teuer). **Breite kostet Regolith, Tiefe kostet AP.** Die beiden Währungen greifen an gegenüberliegenden Enden an.

**G7 bestimmt die absolute Skala** — das ist der eigentliche Grund gegen einen niedrigen Sockel, nicht eine Deckungslücke. Bei einem sehr kleinen Sockel liegen alle Baupreise so eng, dass der Unterschied zwischen zwei Gebäuden im Reparaturrauschen verschwindet; beim gewählten Sockel gibt es unterscheidbare Preisklassen mit sauberem Abstand.

#### Der Satz

| Wert | Festlegung | folgt aus |
|---|---|---|
| Harvester-Ertrag | Frischwert je Tile-Stufe (`rich`/`normal`/`poor`), fallend mit der Erschöpfung (§4c), `max_level = 1` | G7 |
| Reparatur | 1 Rg je SP (zusätzlich 1 AP je SP) | G2 + „eine Zahl, zwei Währungen" |
| `decay_rate` | vier Klassen 0,40 / 0,60 / 0,80 / 1,20 | G2, G3 |
| Errichtung (Lv0→1) | 70 Agrardom (Ramp-Gate-Ausnahme) / 95 alle drei Pfadgebäude | G4 (5–8 Sole) |
| Level-Up | flach 25, jedes Level, jedes Nicht-CC-Gebäude (auch der Sprung 0→1 nach der Errichtung) | G4 (1–2 Sole) |
| CC-Ausbau | Ziel-Level × 30 | zentraler Progressionshebel |
| Weitere Instanzen (Wohnhabitat, Hangar, Agrardom) | voller `build_cost`, linear | §4c: Instanzen sind eine Designachse, keine Level |
| Harvester-Zweitinstanz | kein Regolith — Weg A Credits, Weg B AP (§4c) | Bootstrap-Sonderrolle |
| Startbestand | 370 (Herleitung „Phase-1-Pacing" unten) | Phase-1-Ziel Sol 15–20 + Sturm-Reserve |
| `mission_supply_run.sol_distance` | 1 | Hebel-Zielgröße, kurzer Entscheidungstakt |
| `geology`-Effekt | +3/3/2/2/2 je Level, kumuliert max 12 Rg/Sol | ~60 % eines Harvesters (Merkregel für einen reifen Hebel) |
| `knowledge.levelup_costs` | steigend je Level, Amortisation ~7 Sole | Kopplung an `ap.base` |
| `knowledge.credits` | 0 | Credits-Lücke von Pfad A (§4b) |
| Hebel-Zielgröße | 14,1 Rg/Sol reif, ~9,5 im Run-Mittel | Bilanz unten, Punkt 4 |

Die **Preisrelation** aus der Knappheitsordnung (§3) ist tragend, auch wenn die konkreten `bar.base_prices` Feintuning sind: Steht der Überschuss teurer als der Mangel, funktioniert der Cantina-Hebel nicht. Regolith < Organika < Werkstoffe; der Abstand Organika→Werkstoffe muss „deutlich knapper" abbilden (`config/game.php`).

**Zur Reparatur — eine Zahl, zwei Währungen.** Reparatur kostet 1 AP und 1 Regolith je SP. Damit gilt:

```
Instandhaltung [Rg/Sol]  =  Instandhaltung [AP/Sol]  =  Σ decay_rate
```

Das Dashboard (§13.4) braucht keine zwei Zeilen und §13.5 keine zwei Tabellen. Soll Reparatur „teurer wirken", ist `decay_rate` der Knopf, weil er beide Seiten gleichzeitig bewegt.

**Zu `decay_rate` — aus einer Spielaussage abgeleitet.** `decay_rate = max_status_points / Sole_bis_Level_Down`; die Sole sind eine Designaussage: *wie teuer ist es, dieses Gebäude zu vergessen?*

| Klasse | Sole bis Level-Down | Rate | Gebäude |
|---|---|---|---|
| Robust | 50 | 0,40 | Kommandozentrale, Wohnhabitat, Kolonialdenkmal |
| Standard | 33 | 0,60 | Agrardom, Uplink-Station, Hangar, Handelsposten, Sicherheits-Hub |
| Beansprucht | 25 | 0,80 | Harvester, Analytik-Labor, Cantina, Krankenstation |
| Fragil | 17 | 1,20 | Religiöse Stätte |

Bei den robusten Gebäuden ist ein Level-Down überproportional teuer (Supply-Cap bricht weg) — er muss langsam kommen, sonst verletzt er G3. Die Religiöse Stätte ist bewusst der teuerste Unterhalt im Spiel: Sie zahlt in Vertrauen, nicht in Funktion; wer sie hält, entscheidet sich aktiv dafür. Die Raten sind gegen eine Zielkolonie mit vielen Instanzen kalibriert (§4c „im Zweifel Instanz"): Σ `decay_rate` bei Vollausbau liegt bei ~41 % des AP-Pools und — gemessen an Sockel + aktivem Pfad-Hebel — bei ~41 % des Regolith-Einkommens; das trifft G2s Zielwert. Ein vernachlässigtes Gebäude verliert innerhalb eines Runs ein Level (17–50 Sole), der Verfall bleibt als Systemprinzip spürbar.

#### Bilanz über den Run (1-Instanz-Baseline)

Rechnung über 80 Sole — die Fensterbreite entspricht der Phase-2-Sol-80-Konvention (§18.4).

**1. Zielkolonie-Bedarf.** Errichtungen, Level-Ups und Reibung (Reparaturen, Fehlplatzierungen, Verlegungen) über den Gebäude- und Level-Katalog der Zielkolonie; die Harvester-Zweitinstanz zählt nicht zum Bedarf, weil sie kein Bestandteil der Standard-Zielkolonie ist.

```
Zielkolonie-Bedarf ≈ 835 (Errichtungen) + 720 (Level-Ups) + 240 (Reibung, ~15 %) ≈ 1.795 Rg
```

**2. Sockel-Einnahmen** aus der Erschöpfungskurve, Standardfall 1 Instanz auf `regolith_normal` inklusive Transit-Sole: Zyklusmittel **~12,9 Rg/Sol**, konstant über den Run.

```
Sockel-Einnahmen = 12,9 Rg/Sol × 80 Sole ≈ 1.032 Rg
```

**3. Sockel-Anteil.**

```
Sockel-Anteil = 1.032 / 1.795 ≈ 57,5 %
```

Das ist G6: Der Sockel allein trägt eine spielbare, aber unterdimensionierte Kolonie.

**4. Hebel-Lücke — als Fläche gerechnet, nicht als verdoppelter Mittelwert.** Ein Pfad-Hebel greift nicht ab Sol 1 (Kenntnisstufen bei A, ein Schiff bei B): er greift ab ~Sol 12, rampt linear bis ~Sol 40 und ist danach voll wirksam.

```
Lücke            = 1.795 − 1.032 = 763 Rg über 80 Sole  (≈ 9,5 Rg/Sol im Run-Mittel)
Sol-Äquivalente  = 28 × 0,5 (Rampe) + 40 (voll) = 54 effektive Sole
reife Hebelhöhe  = 763 / 54 ≈ 14,1 Rg/Sol
```

Merkregel, aus der Gegenrichtung: **ein reifer Pfad-Hebel ist etwa 60 % eines Harvesters** — spürbar, aber kein Ersatz für den Sockel.

**5. Deckung.**

| Pfad | Hebel | reifer Wert |
|---|---|---|
| A — Analytik | `geology`, kumuliert max 12 | 12 Rg/Sol |
| B — Hangar | `mission_supply_run`, ~6,25 Rg/Sol je Frachter | 6,25 Rg/Sol (1 Schiff), skaliert mit der Flotte |
| C — Cantina | kein Regolith-Hebel (§4b) | 0 |

```
A + B (1 Frachter) = 18,25 Rg/Sol  ≥  14,1 Rg/Sol benötigt
```

Gemeinsam übersteigen A und B die benötigte Hebelhöhe um ~29 % — wenn beide im relevanten Zeitfenster aktiv sind, was am CC-Lv3/4-Timing der Pfadwahl hängt. Auch ein einzelner Hebel reicht nahe an die Zielgröße: Pfad A allein ≈ 85 % der Hebelhöhe (~94 % des Bedarfs — Restlücke innerhalb der G5-Toleranz); Pfad B mit einem Frachter ≈ 44 % (~76 % des Bedarfs), durch weitere Frachter aufstockbar — die „aktiv arbeiten, breit versorgen"-Identität aus §4b. Ein Run, der primär Pfad C verfolgt und A/B spät bekommt, läuft in der Zwischenzeit nahe am reinen Sockel — knapper, aber nicht tödlich (G1). Die Zahlen von `geology` und `mission_supply_run` tragen die Lücke; sie werden nicht angehoben.

**6. Guard-Rails gegen diese Baseline geprüft.**

- **G3 hält.** Aus der Klassentabelle folgt für alle Gebäude derselbe `max_status_points`-Wert (20); ein volles Level-Down-Aufholen kostet 20 Rg + 20 AP. Netto nach Instandhaltung: ~1,4 Sole mit aktivem Hebel, ~7,4 Sole im Sockel-only-Spätspiel — beides im 5–8-Sole-Korridor, letzteres am oberen Rand.
- **G4 hält für die Pfadgebäude.** 95 Rg liegen bei Frischwert (~5,3 Sole) und Zyklusmittel (~7,4 Sole) im Korridor. Der Schnittbereich, in dem ein Preis bei beiden Referenzwerten zugleich in [5, 8] Solen liegt, ist eng (≈ 90–103 Rg) — drei gestaffelte Preisklassen hätten Abstände unter der Rauschgrenze eines Level-Downs (20 Rg) erzeugt. Deshalb sind alle drei Pfadgebäude gleich bepreist; Gleichpreisigkeit ist die konsequente Umsetzung der Paritäts-Anforderung (§4b), kein Kompromiss. Der Agrardom (70) liegt am Frischwert unter dem Korridor — absichtlich für die erste, zeitkritische Instanz (Nahrungssicherheit vor CC Lv2); spätere Instanzen zahlen denselben Preis gegen ein Einkommen näher am Zyklusmittel, dort liegt er im Korridor.
- **G2 hält** mit Bezugsgröße Sockel + aktiver Pfad-Hebel (Vollausbau ~41 %). Gegen den Sockel allein läge die Last bei ~79 % — deshalb ist der aktive Hebel die Bezugsgröße, nicht eine erneute `decay_rate`-Senkung oder eine kleinere Zielkolonie.
- **G1, G5** sind qualitative Pacing-Aussagen ohne eigene Herleitung; G5 ist über den Playtest-Trigger („mehr als 4 blockierte Sole") falsifizierbar.

**Bekannte Unschärfe:** Die 835 Rg Errichtungen enthalten Hangar-Instanzen zu einem höheren Preis als dem heutigen; mit 95 Rg sinkt der Bedarf je Hangar-Instanz leicht (Sockel-Anteil steigt, Hebelhöhe sinkt). Die Richtung ist begünstigend, die Größenordnung unverändert — bei der nächsten Zielkolonie-Neurechnung mitziehen.

#### Phase-1-Pacing: Startbestand

**Ziel (Owner):** Phase 1 (`checkPhase1Completion()`: CC Lv3 + 2 Nicht-CC-Gebäude ≥ Lv2 + 3 Berater) im Regelfall Sol 15–20, spätestens Sol 25; harte Grenze ist Fail State 4 (Sol 30, §18.2). Credits sind für die drei Ersteinstellungen nicht bindend (Startkapital reicht), AP ist auf dem Referenzpfad nicht bindend — der Engpass ist Regolith gegen die Ratengrenze des Harvesters.

**Bedarfskette Phase 1, günstigster Pfad** (Kommandozentrale, Harvester und Wohnhabitat existieren als Sol-1-Bootstrap auf Lv1; Agrardom und Pfadgebäude werden neu errichtet und starten auf Level 0):

| Posten | Rg |
|---|---|
| CC Lv1→Lv2→Lv3 (Ziel-Level × 30) | 150 |
| Agrardom: Errichtung 70 + Lv0→1 (25) + Lv1→2 (25) | 120 |
| Wohnhabitat: nur noch Lv1→2 | 25 |
| Pfadgebäude 1: Errichtung 95 + Lv0→1 (25) — öffnet Slot 2 | 120 |
| Pfadgebäude 2: Errichtung 95 + Lv0→1 (25) — öffnet Slot 3 | 120 |
| **Summe** | **535** |

Beide Pfadgebäude sind notwendig, nicht optional: Slot 2 öffnet mit dem ersten, Slot 3 (= dritter Berater) mit dem zweiten (§13 „Slot-System"). Der Harvester produziert ab Sol 2 nahe dem Frischwert (~17 Rg/Sol auf `regolith_normal`, solange das Vorkommen nicht knapp wird); Reparatur wird als konservative Marge angesetzt, obwohl frisch errichtete Gebäude im Fenster die Reparaturschwelle kaum erreichen:

```
Verfügbar(N) = Startbestand + 17 × (N − 1) − 2,94 × N
```

| Startbestand | Floor N (Verfügbar = 535) | Poor-Tile-Worst-Case |
|---|---|---|
| 300 | ≈ Sol 18 | ≈ Sol 23 |
| 340 | ≈ Sol 15 | ≈ Sol 20 |
| **370** (gesetzt) | ≈ Sol 13 ohne Sturm | ≈ Sol 18 |
| 400 | ≈ Sol 11 — Überkorrektur, kollidiert mit G4/G5 |

**Startbestand 370.** 340 trifft den Floor an der unteren Kante des Zielkorridors, sodass die reale Ausführungsfriktion (Reihenfolgezwang, Erkundung/Verlegung) nach oben in den Korridor streut statt ihn zu verlassen. Der Aufschlag auf 370 ist eine Reserve gegen Begegnungen (§9): Stürme treffen auch in Phase 1 (die Phase-1-Rampe dämpft die Chance, setzt sie nie auf 0), und ein Kritisch-Treffer kostet in der Größenordnung eines Pfadgebäudes — die Reserve deckt etwa 40 % eines typischen Treffers, bewusst kein Vollschutz. *Offen:* Die Reserve ist gegen „ein Kritisch-Treffer, ein Gebäude" gerechnet; seit Sturm koloniweit wirkt (§9), kann ein ausgelöster Sturm bei vernachlässigter Kolonie mehrere Kritisch-Treffer bedeuten — nach dem nächsten PlaytestBot-Batch verifizieren, nicht vorab blind nachschärfen.

**Poor-Tile-Start** (Frischwert und `resource_max` niedriger, die häufigste Einzelklasse): Das Vorkommen ist nach ~14 produktiven Solen erschöpft, eine frühe Zwangsverlegung ist eingebaut — gewollte Variabilität (G5), keine zu behebende Lücke. Mit Verlegung auf ein `normal`-Tile (1 Transit-Sol) schließt Phase 1 im Worst Case um Sol 18–20.

**Bewusst nicht angefasst:** `resource_max['regolith_normal']` (bindet im Zielfenster nicht — kumulierte Extraktion beim Floor-Sol liegt deutlich unter der Mengengrenze; eine Anhebung kostete nur Umzugstakt, §4c); `fresh_yield` (mehr Rate = schnellerer Vorlauf auf dieselbe Mengenwand, kürzere Standzeit); Pfadgebäude-Preise (pro Gebäude gegen G4 kalibriert, das Phase-1-Problem ist kumulativ); CC-Ausbaukosten (kein Engpass); Hire-Credits (nicht bindend, und eine Senkung würde den späteren Rang-2/3-Unterhaltsdruck abschwächen).

**Empirisch:** PlaytestBot über mehrere Seeds erreicht `phase2_start_sol` 20–22 — innerhalb Sol 25, an der Grenze zum Sol-20-Exzellenzziel. Ein Bot-Befund oberhalb des Korridors ist nur dann ein Gegenbeweis gegen den Startbestand, wenn der Bot nach dem Errichten eines Pfadgebäudes dessen Lv0→1-Sprung tatsächlich zuerst fertigstellt (wie ein menschlicher Spieler) — Bot-Ausführungsdefekte sind kein Balance-Hebel.

**Nebenbefunde:**
- Der Supply-Cap zwingt in Phase 1 weder eine zweite Wohnhabitat-Instanz noch ein Vorziehen des Wohnhabitat-Levelups: der Sol-1-Cap (CC + Wohnhabitat Lv1) deckt Harvester, Agrardom Lv2 und zwei Pfadgebäude auf Lv1 exakt.
- Die Pfadgebäude sind in Regolith gleich, in AP nicht: Analytik-Labor und Hangar sind AP-Klasse „Groß", Cantina „Mittel". Da AP in Phase 1 nicht bindet, ändert das nichts am Pacing, ist aber eine Inkonsistenz gegen die Paritäts-Anforderung (§4b) — bei Gelegenheit prüfen.
- Das im Config-Kommentar beschriebene „harte" Agrardom-Gate für den CC-Lv2-Ausbau (§4) ist im Code nicht vorhanden — `placeBuilding()` erzwingt den Agrardom nur vor den Pfadgebäuden, der CC-Levelup prüft ihn nicht. Owner-Frage in ROADMAP (C16).

#### Agrardom-Kurve: das obere Ende

Der Organika-Verbrauch skaliert über `food_need = intdiv(usedSupply, 4)` mit der **Ausbautiefe** der Kolonie — ein Rennen zwischen Agrardom-Level und Koloniewachstum, dazu Missionsproviant und Event-Kosten. Das ist genau der Mechanismus, den die Knappheitsordnung verlangt: Wer in die Tiefe baut, ohne den Agrardom nachzuziehen, gerät in den Mangel. *Zu prüfen ist nur das obere Ende:* Ab Agrardom Lv3 (max. Ausbaustufe) übersteigt die Produktion den Bedarf der Zielkolonie; ob die Kurve dort flacher auslaufen sollte oder Missionen und Events genug Zusatzlast erzeugen, gehört in dieselbe Herleitung wie der Regolith-Satz (`docs/game-reference.md#ressourcenverbrauch`).

#### Wenn sich die Zahlen als falsch erweisen — welche Stellschraube gilt

| Beobachtung im Playtest | Stellschraube | **nicht** |
|---|---|---|
| Regolith staut sich an (Bestand steigt monoton) | Baukosten anheben | Sockel senken — trifft die Instandhaltung mit und riskiert die Verfallsspirale |
| Regolith klemmt bei 0, Reparatur konkurriert dauernd mit Bauen | Sockel anheben | Reparaturkosten senken — sonst verschwindet der Verfall als Mechanik |
| Verfall wirkt wie Dekoration, folgenlos ignorierbar | `decay_rate` anheben (bewegt beide Währungen zugleich) | Reparaturkosten anheben — das entkoppelt Regolith und AP wieder |
| Instandhaltung fühlt sich spät schlaff an | levelskalierte Reparatur (`1 + floor((level−1)/3)` AP je SP, §13.5) | `decay_rate` global anheben — trifft das Early Game am härtesten |
| Ein Pfad hängt sichtbar zurück | den betreffenden **Hebel** anheben | Sockel oder Baukosten — die sind pfadneutral |
| Mehr als 4 Sole pro Run an Regolith blockiert (G5) | Startbestand, dann Errichtungspreise | die Hebel — sie greifen zu spät für die frühe Klemme |
| Phase 1 dauert typisch > Sol 25 | Startbestand (nur wenn die Bedarfskette gegen den Sol-1-Bootstrap-Zustand nachgerechnet ist), sonst Ausführungsfriktion suchen | `resource_max` — bindet im Zielfenster nicht |

#### Wo dieser Satz unsicher ist

- **Die 60-%-Regel für die Hebel-Reife ist eine Setzung.** Sie fällt aus zwei Richtungen auf dieselbe Zahl, ist aber die erste, die im Playtest zu prüfen wäre. Metrik: Anteil des Regolith-Zuflusses aus dem Hebel je Pfad, Zielband 30–40 %.
- **Die Reibungspauschale von 15 % ist geraten.** Sie deckt Level-Down-Wiederaufbau, Harvester-Verlegungen und Fehlkäufe und ist direkt aus dem Bot-Report ablesbar.
- **Die Supply-Achse ist bewusst nicht mitbewegt.** Die `supply_cost`-Werte sind gegen eine Wirtschaft kalibriert, in der Regolith knapper war. Wird Bauen leichter, wird Supply relativ zum bindenderen Limiter — was §6 entspricht, aber die Zielkolonie gegen den erreichbaren Cap gegenzuprüfen verlangt. **Das ist der nächste unconstrained durchzurechnende Zahlensatz** (ROADMAP Stufe 1d).

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

Der Wert wird in **Tick-Schritt 9** (nach Ressourcenproduktion und Verpflegung) berechnet, da Vertrauen die Produktionswerte desselben Sols noch nicht beeinflusst — es wirkt ab dem nächsten Sol.

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

**Rationale:** Agronomie und Gesundheit verbessern spürbar das koloniale Wohlbefinden. Verteidigung als Kenntnis schafft Sicherheit und stärkt dadurch das Vertrauen der Kolonisten — "Sicherheit schafft Vertrauen". Eine gut ausgebaute, zivile Sicherheitsvorsorge (Sturm-Risiko-Reduktion, der andere `defense`-Effekt) wird durchgängig positiv gerahmt, ohne Trade-off-Zwang. (Präzedenzfall: `geology` hat ebenfalls zwei Vorteile ohne Vertrauensmalus.)

### Einflussfaktoren: Relaisvergütung

Die Relaisvergütung (§3) ist eine reine Nexus-Einnahme **ohne automatischen Vertrauenseffekt** — sie fließt von Nexus an die Kolonie, nicht umgekehrt, und stellt für sich genommen keine Belastung der Kolonisten dar. Einen passiven Abzugs-/Steuermechanismus mit Vertrauensmalus gibt es nicht.

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

Die Kolonie verbraucht jeden Sol Organika zur Versorgung (§3, Tick-Schritt 7). Zwei Vertrauenswirkungen:

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

Vertrauen wird in **Tick-Schritt 9** nach Ressourcenproduktion und Verpflegung berechnet (Nummerierung siehe `GameTick.php`):

| Schritt | Beschreibung |
|---------|-------------|
| 6 | Resource Generation — Rohstoffproduktion (mit altem Vertrauen-Multiplikator) |
| 7–8 | Verpflegung, Begegnungen |
| **9** | **Trust Calculation** — Vertrauen neu berechnen, `colony_resources` (res_id=12) aktualisieren |
| 12 | Advisor Ticks |

Die Reihenfolge ist bewusst: Die Produktion von Sol N verwendet den Vertrauenswert von Sol N-1. Der neue Vertrauenswert gilt erst ab Sol N+1. Das verhindert zirkuläre Abhängigkeiten.

### Implementierung (Stand)

Vollständig implementiert, kein offener TODO mehr:

1. `config/game.php` — `trust`-Block produktiv (alle Werte, siehe oben).
2. `app/Services/TrustService.php` — berechnet den Vertrauenswert je Kolonie.
3. Tick-Integration in Schritt 9 (siehe unten) — schreibt `colony_resources` (res_id=12).
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

> ⚠️ BALANCE CONCERN: Aufgaben-Sets sollten mindestens 2 verschiedene Kategorien abdecken, damit ein Run nicht ausschließlich Wirtschaftsaufgaben zieht (`task_trade_volume` + `task_credit_reserve` sind beide Wirtschaft). Die Kombo-Blacklist ist implementiert (`RunProgressService::TASK_CATEGORIES`, max. 1 Wirtschafts-Aufgabe pro Ziehung).

---

### "2 von 3"-Mechanik

**Bewertung: gut.** Die Mechanik gibt dem Spieler echte Wahlfreiheit, ohne den Run zu trivial zu machen. Eine verfehlte Aufgabe beendet den Run nicht — das reduziert Frustration und fuhrt zu mehr strategischen Entscheidungen ("Welche zwei lohnen sich fur meine aktuelle Ausgangslage?").

**Milestones gegen zu fruhen Fokus-Verlust:**
- Phase-2-Sol 30: Mindestens 1 Aufgabe muss zu > 50 % erfüllt sein. Sonst: Nexus-Warnung im Nexus-Funk.
- Phase-2-Sol 50: Wenn noch keine Aufgabe vollständig erfüllt, zweite Nexus-Warnung.

Alle Nexus-Kontrollpunkte zählen in **Phase-2-Sol** (Sole seit Phasenübergang), nicht in Gesamt-Sol — Tabelle in §18.4.

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

Kommunikationskanal: ausschließlich der Nexus-Funk. Nexus sendet keine Dialogfenster, keine Popups — nur Protokoll-Ereignisse mit Absender "Nexus Command".

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

Sanktionen erscheinen nie ohne vorherige Nexus-Funk-Warnung.

#### Gnadenfrist

Der Countdown zum Missionsende ist sichtbar, sobald die letzten 20 Sole des Tick-Limits beginnen (§18.2 Fail State 3). Nexus tritt jetzt aktiver in Erscheinung (Phase-2-Sol, §18.4):

- **Phase-2-Sol 65:** Wenn noch keine Aufgabe vollständig erfüllt ist → Sanktion (1 Berater 1 Sol abgezogen). *Geplant, noch nicht implementiert:* zusätzlich Verkürzung des effektiven Endes („Nexus Command hat die Frist vorgezogen").
- **Phase-2-Sol 80:** Countdown-Meldung.
- *Geplant, noch nicht implementiert:* letzte Warnung 10 Sole vor dem Ende, falls immer noch 0 Aufgaben erfüllt.
- **Tick-Limit:** Run endet — Fail State 3.

Wer bei der Sanktionsprüfung bereits 1 Aufgabe erfüllt hat, erhält eine neutrale Statusmeldung ohne Sanktion.

> **TODO (Implementierung):** Nexus-Trigger-Tabelle definieren — welche Metrik, welcher Schwellwert, welche Reaktion, welche Phase. Muss vor der Implementierung als Config-Tabelle in `config/game.php → run.nexus_triggers` abgelegt werden.

> **TODO (Design):** Nexus-Boni in Phase 1 oder erst ab Phase 2? Phase-2-only wäre einfacher und vermeidet, neue Spieler zu bevormunden.

> **TODO (UI):** Nexus-Absender-Icon im Nexus-Funk (niedrige Priorität, vor Frontend-Phase klären).

---

### Fail States

Genau vier Fail States — kanonische Definition, Warnstufen und Auslösung in **§18.2**:

1. **Vertrauenskollaps** — Vertrauen fällt unter die Schwelle `run.trust_fail_threshold` (instant, kein Streak). „Die Kolonisten haben das Vertrauen verloren. Der Direktor wurde abgesetzt."
2. **Nexus-Schuldengrenze** — `nexus_debt` überschreitet `run.nexus_debt_fail_threshold`. „Nexus hat die Konzession entzogen. Der Direktor wurde zurückgerufen."
3. **Fristablauf ohne Sieg** — `run.tick_limit` erreicht mit weniger als 2 erfüllten Aufgaben. „Fristablauf. Die Konzession wurde nicht verlängert."
4. **Phase-1-Fristbruch** — Phase 1 bei `run.phase1_deadline_sol` nicht abgeschlossen.

**Nexus-Schulden-Mechanik:**
- Schulden akkumulieren durch: Startkapital (Vorschuss, initialer `nexus_debt`) + Nexus-Deals (Schiffskauf auf Nexus-Kredit, §8b)
- Keine Zinsen
- Rückzahlung: nur manuell — *geplant, noch nicht implementiert* (ROADMAP A8)
- Schuldenlimit: fester Wert (`config/game.php`), als Balken im UI kommuniziert („Nexus-Kredit: X / Cap"), Farbwechsel bei moderaten und hohen Schwellen
- Lose Kopplung mit Vertrauen: kein automatischer Zusammenhang, der Spieler managt beide Achsen aktiv. Ein Schiffskauf auf Kredit löst einen einmaligen kleinen Trust-Malus aus (`nexus_credit`-Event).

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

1. **Nach Registrierung:** `OnboardingService::setupNewPlayer()` erstellt Colony, Startressourcen und Gebäude — setzt aber `started_at = null`. Der Run hat `status = 'active'`, ist aber noch nicht gestartet.
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

- Tabellen: `runs` (Phase, `current_tick`, Status, `fail_reason`, `nexus_debt`, `phase2_start_tick`, Score) und `run_objectives` (aktive Aufgaben des Runs)
- `config/game.php → run` — Tick-Limit, Tick-Dauer, Spieleranzahl, PbM-Modus, Score-Formel-Gewichte; Nexus-Kontrollpunkte sind in `RunProgressService` gesetzt (Verdrahtung über `run.nexus_milestones` offen, ROADMAP A7)
- Run-Struktur läuft als Schritt 15 nach der Tick-Transaktion (`GameTick.php`): Phase-1-Check, Objective-Fortschritt, Nexus-Interventionen, Sieg-/Fail-Prüfung
- Nexus-Interventionen erzeugen Nexus-Funk-Nachrichten mit `sender = 'nexus'`
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

Begründung gegen eine Streak-Mechanikverzögerung: Trust unter −20 bedeutet aktive Feindseligkeit der Kolonisten, keinen vorübergehenden Stimmungseinbruch mehr. Eine Streak-Wartezeit würde die Aussagekraft des Trust-Werts verwässern und den Spieler in einem faktisch verlorenen Zustand weiterspielen lassen.

**Warnstufen (Nexus-Funk/Protokoll + UI):**

| Schwellwert | Maßnahme |
|-------------|---------|
| Trust < 0 | Protokoll-Ereignis (Kolonist, Absender): "Die Stimmung in der Kolonie ist angespannt." — einmalig pro Run |
| Trust < −10 | *geplant:* Roter Farbwechsel am Trust-Ressource-Chip in der Ressourcenleiste |
| Trust < −18 | *geplant:* Nexus-Funk-Warnung: "Direktor, die Lage ist kritisch. Sofortige Maßnahmen erforderlich." |
| Trust < −20 | Fail State — Run endet sofort |

> ⚠️ BALANCE CONCERN: Die −20-Schwelle ist bewusst tief gesetzt. Ein Hunger-Streak von vier Solen (kumulierter Malus nach `TrustService::hungerPenalty`: −2 − 3 − 4 − 5 = −14 kumuliert nach Streak 4) plus ein Level-Down-Event (−3) würde die Schwelle knapp nicht erreichen — das ist gewollt: Vernachlässigung soll spürbar bestrafen, aber erholbar bleiben. Nach erstem Playtest kalibrieren ob −20 zu tief (Spieler scheitern selten) oder zu flach (Spieler scheitern überraschend schnell) ist.

**Narrativer Ausgang:** "Die Kolonisten haben das Vertrauen verloren. Der Direktor wurde abgesetzt."

---

#### Fail State 2 — Nexus-Schuldengrenze

**Bedingung:** `nexus_debt > config('game.run.nexus_debt_fail_threshold')`

**Auslösung:** Instant bei Überschreitung. Geprüft sowohl in `checkFailStates()` als auch direkt in `checkNexusInterventions()` (Phase-2-Sol 55).

**Warnstufen (UI-Schuldenbalken):**

| Schuldenstand | Maßnahme |
|---------------|---------|
| > 80 % des Limits | Schuldenbalken wechselt auf Gelb |
| > 95 % des Limits | Schuldenbalken wechselt auf Rot; *geplant:* Nexus-Meldung „Kreditlimit fast erreicht." |
| > 100 % | Fail State — Run endet sofort |

> **Implementierungsstand:** Akkumulation (Startkapital als initiale Schuld, Nexus-Kredit-Schiffskauf) und Fail-State-Prüfung sind implementiert. Offen: manuelle Rückzahlung und die 95 %-Warnmeldung (ROADMAP A8).

**Narrativer Ausgang:** "Nexus hat die Konzession entzogen. Der Direktor wurde zurückgerufen."

---

#### Fail State 3 — Fristablauf ohne Sieg

**Bedingung:** `current_tick >= config('game.run.tick_limit')` (100) UND weniger als 2 Objectives abgeschlossen

**Auslösung:** In `checkFailStates()` nach jedem Tick. Das Sieg-Gate (§18.1) wird vor den Fail States geprüft — wer die zweite Objective genau auf Sol 100 abschließt, gewinnt noch.

**Countdown-Warnstufen:**

| Sol | Maßnahme |
|-----|---------|
| tick_limit − 20 (Sol 80) | Countdown-Anzeige erscheint im UI ("Noch 20 Sole bis Missionsende"); Nexus-Funk-Nachricht von Nexus |
| tick_limit − 10 (Sol 90) | *geplant:* letzte Nexus-Funk-Warnung, wenn 0 Objectives abgeschlossen |
| tick_limit (Sol 100) | Fail State — Run endet |

**Narrativer Ausgang:** "Fristablauf. Die Konzession wurde nicht verlängert."

---

#### Fail State 4 — Phase-1-Fristbruch

**Bedingung:** `run.phase === 1 && current_tick >= config('game.run.phase1_deadline_sol')` → Standardwert **Sol 30**

**Auslösung:** Instant in dem Tick, in dem die Deadline erreicht wird, sofern Phase 1 noch nicht abgeschlossen ist (`RunProgressService::checkPhase1Completion()`).

Owner-Vorgabe: Phase 1 im Normalfall Sol 15–20, spätestens Sol 30. Der bindende Engpass ist der Regolith-Startbestand — nicht der Harvester-Ertrag, nicht `resource_max`, nicht die Berater-Hire-Credits; Herleitung in §13.7 „Phase-1-Pacing". Empirisch erreicht der PlaytestBot `phase2_start_sol` 20–22.

**Warnstufen (Nexus-Funk):**

| Sol | Maßnahme |
|-----|---------|
| Sol 22 (`config('game.run.phase1_warning_sol')`) | Nexus-Funk-Warnung von Nexus, sofern Phase 1 noch nicht abgeschlossen — einmalig pro Run |
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

**Run-Länge: 100 Sole** (`run.tick_limit`, Owner-Entscheidung).

**Typischer Run-Korridor (Richtwert):**

| Phase | Sols | Anmerkung |
|-------|------|-----------|
| Phase 1 — Stabilisierung | 15–25 | CC Lv3 + 2 weitere Gebäude ≥ Lv2 (Code-Bedingung; Wortlaut „Produktionsgebäude" ist Owner-Frage F7) + 3 Berater — Ziel Sol 15–20, hart Sol 30 |
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

**Objective-Ziele — Kalibrierungsregeln (Werte in `RunProgressService::TASK_TARGETS` und `config/game.php → run`):**

- `task_expedition_coverage` steht am mathematischen Maximum erreichbarer Colony-Zone-Tiles (Summe `colony_zone_expansion` + CC-Tile) und wird nicht erhöht; ein Regressionstest bindet den Zielwert an `colony_zone_expansion`. Die letzte Zone-Kachel schaltet bei CC Lv4 frei, damit das Objective vor dem typischen Run-Ende erreichbar bleibt.
- Streak-Objectives (`task_self_sufficiency`, `task_credit_reserve`, `task_colony_prosperity`) sind so gesetzt, dass sie nicht nebenbei durch normales Spielen erfüllt werden — ein Objective, das der Bot in der Mehrheit der Läufe vor Sol 60 „mitnimmt", ist zu leicht. Zielkorridor für „completed": Sol 80–85.
- `task_colony_prosperity` (Vertrauen über Schwelle) wird nicht am Zielwert kalibriert, solange die Trust-Ökonomie selbst nicht kalibriert ist (Trust bewegt sich im Bot nur um den Neutralbereich) — eigene Untersuchung.
- `task_credit_reserve` liest die Schwelle aus `run.task_credit_reserve_threshold`.

**Credits-Ökonomie — Break-even-Regel:** Der Berater-Unterhalt (`advisor.upkeep`, steigend mit dem Rang) muss im **worst case ohne Cantina** (keine Handelsvertrag- und Corvan-Einnahmen) spätestens mit ausgebauter Uplink-Station tragbar sein — sonst ist die Cantina eine verdeckte Pflicht statt eine gleichrangige Pfadwahl. Einnahmen ohne Cantina sind `nexus_subsidy` (flat, bedingungslos) und die Relaisvergütung (`relay_bonus_per_uplink_level`); der Handelsvertrag (`consul_contract_income_per_rank`) bleibt ein Bonus des Cantina-Pfads. Herleitung mit vier Beratern (Werte `config/game.php`):

| Rang | Upkeep (4 Berater) | Einkommen, Uplink Lv0 | Einkommen, Uplink Lv2 | Einkommen, Uplink Lv3 (max.) |
|------|---------------------|------------------------|-------------------------|---------------------------------|
| 2 | 100 Cr/Sol | 50 Cr/Sol (−50) | 140 Cr/Sol (+40) | 185 Cr/Sol (+85) |
| 3 | 140 Cr/Sol | 50 Cr/Sol (−90) | 140 Cr/Sol (0) | 185 Cr/Sol (+45) |

Ein Rang-2-Defizit bei niedrigem Uplink-Ausbau ist aus dem Phase-1-Reststand absorbierbar; Rang 3 erreicht mit Uplink Lv2+ eine neutrale bis positive Marge statt eines permanenten Bodens. Mit Cantina + Konsul liegt der Überschuss deutlich höher — der Konsul ist ein spürbarer, aber optionaler Vorteil. Beförderungskosten (`promotion_costs`) sind so gesetzt, dass mehrere gleichzeitige Rang-3-Aufstiege keinen Einmal-Schock erzeugen, der zusammen mit dem Upkeep-Sprung die Kasse leert.

**Offene Design-Frage (Owner-Entscheidung):** Sciencelab- und Hangar-Pfad sollen ein **eigenes** Credits-Einkommen bekommen, unabhängig davon, ob und wann die Cantina gebaut wird. Die Zahlenhebel oben nivellieren nur; sie ersetzen keinen fehlenden Kanal. Mechanismus nicht spezifiziert — ROADMAP „Offene Pfad-Paritäts-Fragen".

---

### 18.6 Offene Implementierungsaufgaben (game-developer / db-migration-agent)


| Aufgabe | Verantwortung | Priorität |
|---------|--------------|-----------|
| Trust-Warnstufen (< −10 roter Chip, < −18 Nexus-Warnung; < 0 existiert als `onboarding_trust`) | game-developer | Mittel |
| Manuelle Schulden-Rückzahlung (Nexus-UI) + 95 %-Warnmeldung | game-developer / ui-specialist | Mittel |
| Sol-90-Letzte-Warnung + Fristverkürzung auf Sol 95 (§15 Gnadenfrist); toter Config-Block `run.nexus_milestones` verdrahten oder entfernen | game-developer | Mittel |

Vollständige Liste: `docs/audit-implementierungsstand-2026-09-06.md` (A6–A8).

---

## Siehe auch

- **[GDD Balance & TODO Index](gdd-balance-checklist.md)** — Blockierende Tasks, Folgearbeiten, Playtest-Kalibrierung, offene Designfragen, Instrumentierung
- **[Implementierungsstand-Audit 2026-09-06](audit-implementierungsstand-2026-09-06.md)** — Abweichungen zwischen GDD/ROADMAP und implementiertem Code/Config (ersetzt `gdd-config-audit.md`)

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
| AP-Struktur inkl. `ap.base` | §13.6 |
| Regolith-Zahlensatz: Harvester-Frischwert je Tile-Stufe, Reparatur 1 Rg/SP, Errichtung 70 (bioFacility, Ausnahme) / 95 (alle drei Pfadgebäude), Level-Up 25, `decay_rate`-Klassen 0,40/0,60/0,80/1,20, Startbestand | §13.7 |
| `max_instances` als eigenes Feld neben `max_level` | §4c |

Alles andere ist verhandelbar.

> **Diese Regel gilt auch für Subagenten.** Wer mit Balance-Aufgaben beauftragt wird, bekommt sie explizit mitgegeben — sonst entstehen Vorschläge, die vorhandene Zahlen als Randbedingung behandeln und Workarounds darum herum bauen, statt den Satz neu zu rechnen.
