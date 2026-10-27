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
6. [Kolonisten & Wohnraum (Cap-Modell)](#6-kolonisten--wohnraum-cap-modell)
7. [Verfall & Entropie](#7-verfall--entropie)
8. [Hangar & Schiffe](#8-hangar--schiffe) (Abgrenzung: keine Flotten, keine Systemkarte)
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

> **Ausgelagerte Kapitel** (`docs/gdd/`): §11 [Techtree](gdd/techtree.md), §16 [Onboarding](gdd/onboarding.md), §17 [Progressive Discovery](gdd/progressive-discovery.md). Kriterium: Kapitel, die man beim Nachdenken über Spielregeln nicht mitliest — nicht mehr geltende Mechanik, Entitätslisten und UX-/Content-Spezifikationen. Die Regelkapitel §1–7, §8b, §9–10, §12–15 und §18 bleiben zusammen. Ebenfalls in `docs/gdd/`: [`entity-chips.md`](gdd/entity-chips.md).

---

## 1. Spielkonzept

Nouron ist ein rundenbasiertes Weltraum-Strategiespiel für Einzelspieler im Browser. Der Spieler übernimmt die Rolle eines Kolonie-Direktors mit einem klaren Auftrag: eine kleine, ressourcenarme Kolonie auf Vordermann zu bringen — entweder eine frisch gestartete Siedlung oder eine heruntergekommene Anlage, die sich selbst überlassen wurde.

Die Kolonie bleibt im gesamten Spielverlauf überschaubar. Es geht nicht darum, ein galaktisches Imperium aufzubauen, sondern darum, eine kleine Gemeinschaft unter schwierigen Bedingungen am Leben zu erhalten und gedeihen zu lassen.

Das Spiel ist in **Runs** strukturiert: Jeder Run hat ein konkretes Ziel, einen variablen Verlauf und ein klares Ende — Erfolg oder Scheitern. Nouron enthält **Roguelike-Elemente**: variable Aufgaben je Run, zufällige Ereignisse und echte Konsequenzen für Fehlentscheidungen. Runs können wiederholt werden; jeder Run fühlt sich anders an.

Das Spiel läuft auf Basis eines Sol-Zyklus: alle Spielzustandsänderungen werden einmal pro Sol berechnet, und der Spieler löst den nächsten Sol selbst aus. Nouron ist ein reines Einzelspieler-Spiel — es gibt keinen Mehrspieler-Modus. (Intern: "Tick" — die technische Bezeichnung für den Berechnungszyklus.)

**Technischer Stack:** PHP/Laravel Backend, MySQL, Blade-Templates. Frontend: Alpine.js + PicoCSS, SVG für Spielfelder (Hex-Grid), Vanilla fetch() für Server-Calls. jQuery/Bootstrap-Migration vollständig abgeschlossen.

---

## 1.1 Designprinzipien

### Aufbau vor Konflikt

Nouron erzählt die Geschichte einer kleinen Kolonie, die ums Überleben kämpft — nicht die Geschichte eines aufstrebenden Militärstaats. Es gibt keine Armee, keine Flotten, keine Flottenbefehle, keine Kämpfe, keine Eskalationsziele. Die Schiffe des Hangars (Drohne, Frachter, Korvette) sind zivile Werkzeuge für Außenmissionen: Erkundung, Logistik, Bergung und Schutzdienst, ohne Gegner (§8b).

Gefahren sind klein, lokal und richten sich gegen die Kolonie selbst statt gegen Gegner: Stürme, geologische Instabilität, Seuchenausbrüche (§9). Sie wirken direkt auf den Gebäudezustand — es gibt keinen Gegner-Stärkewert, keinen Kampf, nur einen Zustand vorher und einen danach.

### Vorsorge statt Verbot

> Es gibt keine eigene „defensive" AP-Kategorie — alle AP stammen aus einem gemeinsamen Pool (§13.1). Das Prinzip lautet daher: **Vorsorge kostet AP, das sonst in Wachstum fließen würde** — nicht als Strafe, sondern als Konkurrenz um denselben Pool.

**Navigation-AP** (Domäne des Raumfahrers): fließt entweder in ring-gestaffelte Tile-Erkundung (Kosten steigen mit dem Ring, `colony.explore_cost_per_ring`) oder in den Dispatch von Hangar-Schiffen auf Außenmissionen (AP und Organika-Proviant je Sol Distanz, `config/missions.php`). Wer eine Mission entsendet, deckt in diesem Sol weniger neues Terrain auf — eine echte, rein zivile Opportunitätskostenentscheidung.

**Bau-AP** (Domäne des Baumeisters): fließt entweder in Gebäudeausbau (Wachstum) oder in Reparatur beschädigter Gebäude (Vorsorge gegen die Kolonistengefahren aus §9). Ein gut gewartetes Gebäude übersteht ein Ereignis fast unbeschadet, ein vernachlässigtes nimmt Schaden — Reparatur kostet nicht mehr AP pro Punkt als Ausbau, sie konkurriert nur mit ihm um denselben Pool.

### Geltungsbereich: spielweites Prinzip

Jede neue AP-Mechanik wird geprüft: Konkurriert Vorsorge (Reparatur, Wartung, Absicherung) sichtbar mit Wachstum um denselben AP-Pool? Bleibt eine echte Entscheidung ohne Optimalpfad? Es gibt keine strukturelle "Verteidigung kostet mehr als Zivil"-Regel; ein solcher Aufpreis bräuchte eine eigenständige Mechanik — etwa eine künftige Korvetten-Neutralisierung von `terrain_hazard`-Tiles (§4a, Konzept vorhanden, nicht implementiert).

---

## 1.2 Alleinstellungsmerkmale (USPs)

Nouron teilt sich das Genre "Browser-Strategiespiel" mit Dutzenden von Titeln. Was Nouron von ihnen unterscheidet, ist kein einzelnes Feature, sondern ein kohärentes Designprinzip: das Spiel ist für Spieler gebaut, die lieber nachdenken als klicken — und die Konsequenzen ihres Handelns über Tage spüren wollen.

### Die sechs Merkmale

**1. Verfall als durchgängiges Systemprinzip**
Gebäude verfallen ohne aktive Pflege, Schiffe verschleißen im Einsatz. Wer seine Kolonie vernachlässigt, verliert sie langsam — nicht durch Gegner, sondern durch Entropie. Der Verfall zwingt zur Priorisierung und macht jeden Sol zu einer echten Ressourcenentscheidung. Kenntnisse verfallen nicht — einmal erarbeitetes Wissen bleibt permanent.

**2. Sol-basiertes Spieltempo (1 Sol = 1 Tag)**
Keine Echtzeit-Hektik. Entscheidungen werden einmal täglich getroffen und einmal täglich ausgeführt. Das Spiel passt sich dem Spieler an, nicht umgekehrt.

**3. Nur eine Kolonie — Tiefe statt Breite**
Kein Ausbreiten über eine halbe Galaxie, kein Micromanagement von zehn Außenposten. Eine Kolonie, ein Direktor — alle Entscheidungen betreffen denselben Ort und dieselbe Gemeinschaft.

> **Außenposten:** Außenposten (nicht Kolonien) sind als Phase-4-Konzept vorgesehen — kein Kolonisierungssystem. Design noch nicht definiert. Der Spieler betreibt im gesamten Spiel genau eine Kolonie; Außenposten wären ressourcenextrahierende Außenstellen ohne eigene Verwaltungsebene.

**4. Roguelike-Elemente im Strategieformat**
Jeder Run hat variable Aufgaben, zufällige Ereignisse und echte Konsequenzen. Das Scheitern ist möglich und lehrreich. Kein Run ist identisch — aber die Kolonie bleibt immer dieselbe Art von Ort.

**5. Eine kleine Welt**
Eine Hex-Karte mit drei Ringen, wenige Felder, wenige Funde. Jede Begegnung und jedes Ereignis hat Gewicht. Die Knappheit des Raums ist Teil des Designs.

**6. Vorsorge als Opportunitätskosten-Entscheidung**
Reparatur und Wartung konkurrieren mit Wachstum um denselben AP-Pool. Wer alles in Ausbau steckt, steht beim nächsten Sturm schlechter da; wer nur flickt, wächst nicht. (Ausführlich in §1.1.)

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

### Sol auslösen

Der Spieler steuert den Sol selbst. Nach dem Setzen aller Befehle löst er den nächsten Sol manuell aus ("Sol beenden"-Button) — der Sol feuert sofort. Es gibt kein Warten und keine Echtzeit-Begrenzung. "1 Sol" entspricht einem Spielzug, nicht einer Kalenderdauer.

### Sol-Nummer (Sequenz-Counter)

Die Sol-Nummer ist ein einfacher **Integer-Counter pro Run**, gespeichert in `runs.current_tick`. Sie beginnt bei 0 und wird bei jedem Sol-Trigger atomar um 1 erhöht. Es gibt keinen Bezug zum Unix-Timestamp.

```
runs.current_tick += 1   -- atomar in DB-Transaktion
```

Dies hat zwei Konsequenzen:

- **Kein Doppellauf möglich:** Der Increment ist der Guard. Ein zweiter Player-Trigger erhöht `current_tick` auf den nächsten Wert und würde eine neue Berechnung auslösen — CSRF-Schutz und UI-Deaktivierung des Buttons nach Auslösung verhindern das auf Anwendungsebene.
- **Tick-Referenzen:** Tick-gebundene Datensätze (z.B. `colony_hangar_missions.dispatch_tick`, `colony_buildings.pending_until_tick`) referenzieren den Counter als einfachen Integer.

Die Timestamp-Formel (`floor((timestamp - offset) / 86400)`) und `TickService::calculateTickFromTimestamp()` bleiben als zeitbasierter Fallback im Code, werden im Spielbetrieb aber nicht verwendet.

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
| 3. Ressourcen & Wohnraum | Rohstoffproduktion (Vertrauens-Multiplikator und Besetzungsanteil angewendet); nach den Begegnungen Wohnraum neu berechnen; Kolonisten, Besetzungsanteil und Gate folgen sofort daraus (§6) |
| 3a. Verpflegung | Kolonie verbraucht Organika für ihre Kolonisten (Zielregel „1 Organika versorgt 1 Kolonisten“, Umsetzungsstand siehe §4a „Organika“); Vorrat reicht → `well_fed`, sonst Hunger-Streak + eskalierender Vertrauens-Malus (§3, §14) |
| 4. Vertrauen | Vertrauenswert neu berechnen (inkl. Hunger-Malus), `colony_resources` aktualisieren (§14) |
| 5. Credits, Beratung & Events | Credits-Einnahmen (Nexus-Subvention, Relaisvergütung), Berater-Unterhalt, Advisor-Ticks, Bar-Angebote, Händler-Spawn, Run-Checks (Phasen, Objectives, Fail State) |

> **Phase ≠ Schritt:** Die Nummerierung 1–5 (3a) in dieser Tabelle ist eine grobe, spielerorientierte Gruppierung — kein 1:1-Bezug zu den feingranularen Schritt-Nummern in `GameTick.php` (die z.B. bei der Wohnraum-Berechnung „Schritt 8" heißen, §6). Die genaue Schritt-Reihenfolge innerhalb jeder Phase ist in `app/Console/Commands/GameTick.php` (Docblock) kanonisch festgehalten — dort steht auch die maßgebliche Nummer, falls ein anderer GDD-Abschnitt einen konkreten Schritt referenziert.

---

## 3. Ressourcen

6 Ressourcentypen (Stand Phase 3):

| ID | Name (DE) | Name (EN) | Kürzel | Handelbar |
|----|-----------|-----------|--------|-----------|
| 1  | Credits | Credits | Cr | Nein |
| 2  | Kolonisten | Colonists | KOL | Nein |
| 3  | Regolith | Regolith | Rg | Ja |
| 4  | Werkstoffe | Compounds | Co | Ja |
| 5  | Organika | Organics | Or | Ja |
| 12 | Vertrauen | Trust | V | Nein |

Startwerte pro Lauf: siehe `config/game.php`; siehe auch `docs/game-reference.md#ressourcen-startwerte`.

Alle Ressourcen gehören der einen Kolonie des Spielers; das Spiel unterscheidet nicht zwischen „Spieler-" und „Kolonie-Ressourcen". Kolonisten tragen intern den Code-Schlüssel `supply` (siehe §6 und `GLOSSAR.md`).

### Knappheitsordnung

**Verbindlich für jede Balance-Arbeit.** Die drei handelbaren Kolonieressourcen stehen in einer festen Knappheitsreihenfolge. Preise, Produktionsraten und Verbrauchsmengen müssen sie abbilden — sie ist die Vorgabe, nicht das Ergebnis.

| Rang | Ressource | Rolle | Soll-Gefühl |
|---|---|---|---|
| 1 — am verfügbarsten | **Regolith** | Standard-Baustoff | **Soll verfügbar sein.** Bauen darf nicht am Rohstoff scheitern, sondern an AP, Bauplatz und freien Kolonisten. Knappheit entsteht als Ausnahme, nicht als Dauerzustand. |
| 2 | **Organika** | Verpflegung, Vertrauen | **Seltener als Regolith.** Der Vorrat trägt sich bei ordentlicher Führung, **kann aber bei Missmanagement knapp werden** — dann greift die Hunger→Vertrauen-Spirale (§4a). |
| 3 — am knappsten | **Werkstoffe** | High-Tech-Akzent | **Anfangs sehr begrenzt**, im Spielverlauf zunehmend verfügbar und dadurch belohnend — **bleibt aber dauerhaft knapper als Organika.** |

Daraus folgt zwingend `Preis(Regolith) < Preis(Organika) < Preis(Werkstoffe)` und, für die Produktionsseite, dass Regolith reichlicher zufließen muss als Organika verbraucht wird.

> **Wozu diese Ordnung dient:** Beobachtet ein Playtest das Gegenteil (Organika im Überschuss, Regolith knapp), ist das ein Symptom der Produktionsraten, nicht ein Argument für andere Preise. Wo Beobachtung und diese Ordnung auseinandergehen, ist die Produktionsseite zu korrigieren, nicht die Ordnung.

### Ressourcen-Semantik

- **Regolith** — Lokaler Rohstoff: Mondgestein, Silikate, Mineralstaub. Wird vor Ort vom Harvester abgebaut. Primäre Verwendung: Rohbaukosten für Gebäude (außer CC und Harvester). Der Startwert ist eine moderate Reserve — narrative Begründung: vor Ankunft des Spielers wurden durch automatisierte Maschinen bereits Ressourcen bereitgestellt (Frontier-Depot).
- **Werkstoffe** — Veredelte Industriegüter: raffinierte Metalle, Legierungen, technische Komponenten. Nicht lokal produzierbar. Quellen: Nexus-Direktimport (Uplink-Station, Preis in Credits), Cantina-Angebote, Missionen. Verwendung: High-Tech-Gebäude (als Akzent neben Regolith).
- **Organika** — Biologische Ressource: Nahrung, Medizin, Biodünger, organische Verbindungen. Entscheidend für Bevölkerung und Vertrauen. Produktionsgebäude: Agrardom (bioFacility). Wird durch eigene Produktion oder Handel beschafft.
- **Kolonisten** — die Menschen der Siedlung. Kein Rohstoff im klassischen Sinne: Ihre Zahl ergibt sich aus dem Wohnraum von Kommandozentrale und Wohnhabitaten (Cap-Modell, siehe §6) und begrenzt, wie viele Gebäude und Ausbaustufen die Kolonie betreiben kann.
- **Vertrauen** — Systemmechanik, kein handelbarer Rohstoff (siehe §14).

### Ressourcen-Verwendungsdomänen

| Ressource | Gebäude früh (Rohbau) | Gebäude spät (High-Tech) | Schiffe | Reparatur |
|-----------|----------------------|--------------------------|---------|-----------|
| Regolith | Ja (außer CC + Harvester) | Ja (außer CC + Harvester) | Nein | Ja (außer CC + Harvester); pro Reparatur-Schritt siehe config/game.php |
| Werkstoffe | Nein | Ja (Akzent, nicht Hauptkosten) | Nein | Nein |
| Organika | Nein | Nein | Nein | Nein |
| Credits | Nein | Nein | **Ja — nur Credits** (Nexus-Anfrage) | Nein |
| Kolonisten (Wohnraum) | Gate (freie Kolonisten ≥ Arbeitsplatzbedarf) | Gate | — | — |

**Ausnahme CC + Harvester:** CommandCenter und Harvester kosten beim Bau kein Regolith — sie sind der Einstiegspunkt der Kolonie und dürfen keinen Ressourcen-Catch-22 erzeugen (Regolith braucht Harvester, Harvester braucht Regolith). Beide sind auch von der Reparatur-Regolith-Kostenpflicht ausgenommen (AP-only) — das hält die Regolith-Quelle selbst immer reparierbar und verhindert eine Decay-Deadlock-Spirale.

**Kolonisten sind kein Stockpile, sondern ein Cap:** Der „Kolonistenbedarf" eines Gebäudes ist sein laufender Arbeitsplatzbedarf (`supply_cost`, §6). Beim Bau wird nichts abgezogen — geprüft wird nur, ob genug freie Kolonisten da sind.

> **Designprinzip:** Regolith = lokaler Rohbau (alle Gebäude außer CC/Harvester + laufende Reparatur — der Dauer-Sink, der bis Run-Ende relevant bleibt). Werkstoffe = knapper, importierter High-Tech-Akzent (nicht produzierbar, nur Credits-Import). Organika = biologische Schicht (Versorgung/Verpflegung + Handel — **nicht** Bau/Schiffe; Sinks siehe §3 Organika). Kolonisten = physisches Kapazitäts-Gate. Credits = Tausch-, Personal- und Schiffsmittel.

### Werkstoffe: Sicherheitsnetz

Werkstoffe können **nicht lokal produziert** werden — die Kolonie ist zu klein zum Veredeln. Es gibt drei Bezugswege, die bewusst eine Hierarchie bilden:

1. **Nexus-Direktimport (Sicherheitsnetz, garantiert):** Über die **Uplink-Station Lv1** (eine der aktiven Nexus-Anfragen, siehe §4) kann jederzeit eine beliebige Menge Werkstoffe gegen Credits gekauft werden — deterministisch, **sofort verfügbar**, aber zu einem **festen, spürbar höheren Preis** als der Cantina-Spotpreis (siehe `docs/game-reference.md#werkstoff-preise`). Dies ist das Anti-Lock-Netz: ohne diesen garantierten Weg wäre jede Werkstoff-Baukostenanforderung potenziell hart blockierbar. Für alle anderen Ressourcen bietet dieselbe Nexus-Anfrage eine verzögerte Variante (Bezahlung sofort, Lieferung nach einigen Solen, §4) — Werkstoffe bleiben bei der Sofort-Lieferung, weil sie das einzige echte Progression-Lock-Risiko tragen.
2. **Cantina (opportunistisch, günstiger):** Zufällige, zeitgebundene Kaufangebote zum niedrigeren Marktpreis (Kanal 1, §12). Belohnung fürs aufmerksame Spielen, aber **nie garantiert** — daher nie die einzige Quelle.
3. **Missionen (Bonus):** Hangar-Missionen wie Trümmerbergung und Fernexpedition (§8b) liefern Werkstoffe als Beute — mit Kosten und Risiko, nie kostenlos und nie als einzige Quelle.

> **Werkstoffe sind eine eigene Ressource.** Sie tragen die dritte Achse des Tauschdreiecks (§4a), die Lv1-Funktion der Uplink-Station (§4), den qualitativen Rang-3-Vorteil des Konsuls (§12), zwei Missionsbelohnungen (§8b) und eine Run-Aufgabe (§15); Credits können keine dieser Rollen übernehmen.

> **Designprinzip Knappheit:** Werkstoffe sind das „Salz", Regolith das „Mehl". Späte/High-Tech-Gebäude verlangen Werkstoffe nur als **Akzent**, nie als Hauptkosten — denn jeder Werkstoff ist eine harte Credits-Ausgabe über den Import (siehe `config/game.php` für exakte Kosten). Die Knappheit erzwingt eine Credits-Allokations-Entscheidung (Werkstoff-Import vs. Schiffsanfragen vs. Berater), bleibt aber durch den garantierten Nexus-Import planbar statt zum Glücksspiel zu werden.

### Credits-Einnahmen

Credits werden durch vier Quellen erworben:

| Quelle | Beschreibung |
|--------|-------------|
| Nexus-Subvention | Nexus zahlt jeder Kolonie mit Kommandozentrale pro Sol einen festen Betrag — bedingungslos, der Boden des Einkommens |
| Relaisvergütung | Nexus zahlt pro Sol eine Vergütung für die Relais-/Sensor-Infrastruktur der Uplink-Station — abhängig vom Uplink-Station-Level |
| Missionen und Cantina-Begegnungen | Credits-Belohnungen von Hangar-Missionen (§8b) und die Zufallsbegegnungen der Cantina (Wette, Auktion, Kontrakt; §12) |
| Handel | Verkauf von Organika an den Reisenden Händler (§12) |

Ausgaben: Berater-Anstellung, -Beförderung und -Unterhalt (§13), Schiffsanfragen bei Nexus (§8b), Werkstoff-Import (Uplink-Station), Händlerangebote, Kolonisten-Zulage (§14).

### Abgrenzung: Ressourcen, die es nicht gibt

- Wasser und Energie sind keine eigenen Ressourcen; es gibt keine rassenspezifischen Energieressourcen, da das Spiel keine Rassen kennt.

---

## 4. Kolonien & Gebäude

### Gebäude (Phase 3 — vollständige Liste)

13 Gebäude (`config/buildings.php`; Max-Level je Instanz, Wohnhabitat zusätzlich max. 6 Instanzen, alle übrigen genau eine Instanz):

| ID | Config-Key | Name (DE) | Name (EN) | Max-Level | Voraussetzung |
|----|------------|-----------|-----------|-----------|---------------|
| 25 | commandCenter | Kommandozentrale | Command Center | 5 | — |
| 28 | housingComplex | Wohnhabitat | Residential Habitat | 3 (× 6 Instanzen) | CC Lv1 |
| 27 | harvester | Harvester | Harvester | 1 (1 Instanz) | CC Lv1 |
| 41 | bioFacility | Agrardom | Agrarian Dome | 3 | CC Lv1 + Harvester Lv1 (**Pflichtgebäude vor CC Lv2**, siehe unten) |
| 31 | sciencelab | Analytik-Labor | Analytics Lab | 5 | CC Lv2, Teil der **Pfadwahl** (siehe unten) |
| 46 | infirmary | Krankenstation | Medical Station | 3 | CC Lv3 |
| 52 | bar | Cantina | Cantina | 3 | CC Lv2, Teil der **Pfadwahl** (siehe unten) |
| 44 | hangar | Hangar | Hangar | 3 (eine Halle) | **CC Lv2**, Teil der **Pfadwahl** (siehe unten) |
| 32 | temple | Religiöse Stätte | Sacred Site | 1 | CC Lv4 |
| 50 | monument | Kolonialdenkmal | Colonial Monument | 1 | CC Lv5 |
| 53 | securityHub | Leitstelle | Response Center | 3 | CC Lv2 |
| 54 | uplinkStation | Uplink-Station | Uplink Station | 3 | CC Lv2 |
| 55 | tradingPost | Handelsposten | Trading Post | 3 | CC Lv4 |

> **Agrardom ist Pflichtgebäude vor CC Lv2.** Der Agrardom gehört nicht zur Pfadwahl-Gruppe. Begründung: Sol 1/2 garantieren nur Bau- und Erkundungs-AP-Verwendung, keinen Ressourcenfluss. Ohne Agrardom bliebe Organika auf 0, bis der Spieler ihn irgendwann wählt — in der Zwischenzeit frisst die Verpflegungsmechanik (§4a „Organika") den nicht vorhandenen Vorrat, und der eskalierende Trust-Malus (`TrustService::hungerPenalty`) greift vor der ersten bewussten Wirtschaftsentscheidung. Deshalb ist er das erste Bauprojekt der Kolonie (`hint_agrardome`, §16.2) und ein hartes Gate: `placeBuilding()` verlangt den Agrardom vor jedem Pfadgebäude. Zusätzlich blockt der CC-Sprung von Stufe 1 auf 2 ohne Agrardom (`agrardom_required`).
>
> **Pfadwahl ab CC Lv2 (CC Lv2 → Lv4):** Das erste Pfadgebäude gehört in Phase 1, das zweite und dritte (CC Lv3 bzw. Lv4) in Phase 2. Der Zeitpunkt ergibt sich aus dem CC-Takt (AP-Kosten je CC-Stufe, §4 „Level-Up"), nicht aus einer festen Sol-Zahl. Sciencelab, Hangar und Cantina sind alle ab CC Lv2 baubar , aber **nur eines der drei kann bei CC Lv2 gebaut werden** — die anderen beiden schalten erst bei CC Lv3 bzw. CC Lv4 frei (gestaffelt nach Bau-Reihenfolge, nicht nach Gebäudetyp). Was die drei Pfade inhaltlich sind, steht in **§4b „Die drei Pfade"**; die Slot- und Gate-Mechanik in §13 „Slot-System".
>
> **Leitstelle (CC Lv2) — optionaler Resilienz-Baustein:** Die Leitstelle ist **nicht Teil der Pfadwahl-Gruppe** (kein Bau-Gate-Zähler), sondern ein separates Infrastrukturgebäude, das ab CC Lv2 gebaut werden kann. Sie hat **kein Pflichtgebäude** als Voraussetzung (kein Äquivalent zum Agrardom-Gate): Regolith, Werkstoffe und AP-Kosten sind das natürliche Gate. Ihre drei Stufen entsprechen den Stufen-Gates der Kommandozentrale (Stufe n braucht CC Lv n, §13 „Slot-System"), die volle Wirkung (Recycling) gibt es also erst ab CC Lv3.
>
> **Harvester (Sondergebäude):** Der Harvester unterscheidet sich von allen anderen Gebäuden: Er steht nicht in der Kolonie-Zone, sondern auf einem Ressourcen-Tile in der Exploration Zone. Er produziert passiv je nach Tile-Typ (Regolith oder andere Mineralien). Er kann verlegt werden (Kosten: AP **pro Hex Distanz**, `game.harvester.relocate_ap_per_hex`, keine Ressourcenabzüge; Transit-Zeit: **1 Sol flat**, unabhängig von der Distanz — der Harvester produziert im Transit-Sol nicht). Es gibt genau einen Harvester pro Kolonie. Technisch ist er ein Gebäude mit einer `tile_x/tile_y`-Position statt eines Kolonie-Slots.

> **Harvester-Transit:** Es gibt keine distanzabhängige Transit-Zeit. Die AP-Kosten skalieren bereits mit der Distanz und erzeugen damit das gewünschte Planungs-Druckgefühl. Eine zusätzliche Sol-Staffel wäre eine doppelte Strafe für lange Verlegungen und würde im Transit Reparaturen blockieren (Reparatur kostet Regolith — kein Regolith-Zufluss ohne Harvester), was eine unkontrollierte Decay-Spirale riskiert. Der 1-Sol-Stopp ist ausreichend: ein Sol ohne Regolith-Produktion plus die AP-Kosten der Strecke. Falls der Playtest zeigt, dass Harvester-Verlegungen zu oft ohne Nachdenken passieren, ist der bessere Hebel die AP-Rate je Hex, nicht die Sol-Downtime.
>
> **Playtest-Beobachtung:** Mit `max_level = 1` (§13.5) wiegt der Transit-Sol relativ schwerer, weil das Grundeinkommen die einzige passive Regolith-Quelle bleibt. Der Verlegungsanreiz sinkt zugleich, da Tile-Ergiebigkeit nicht mehr über Level multipliziert wird — beides im Playtest zu beobachten.

> **Verlege-Vorschau mit Ertragsvergleich :** Der Vorschaupfeil zeigt neben den AP-Kosten auch den Ertragsvergleich aktuelles vs. Ziel-Tile (z. B. „6 AP · 18→26 Rg"). Grund: Der Onboarding-Hint lehrt die Mechanik korrekt, gab dem Spieler aber keine Entscheidungsgrundlage — er jagte ergiebigeren Tiles hinterher und verbrannte AP. Statt einer paternalistischen Warnung informiert die Vorschau die Abwägung (Catan-Designlinie: Entscheidungen ohne Optimalpfad). Ergänzend nennt der First-Click-Tooltip die Opportunitätskosten („lohnt nur, wenn das Ziel spürbar ergiebiger ist").

### Baumenü: gesperrte Gebäude

Das Baumenü zeigt auch Gebäude, die der Spieler noch nicht platzieren darf, und nennt den Grund: Pfadgebäude über dem Bau-Gate (§13 „Slot-System") und Gebäude, die eine höhere Kommandozentrale-Stufe verlangen. Der Server lehnt ein solches Platzieren weiterhin ab; die Sperre im Menü verhindert nur den Klick auf etwas, das ohnehin abgelehnt würde. Ein Klick auf ein freies Feld der Karte öffnet das Baumenü mit diesem Feld als Bauplatz.

### Bauregeln: Zone-Trennung

**Kernregel:** Ressource-Tiles und Terrain-Tiles sind strikt getrennt — kein Gebäude darf auf einem falschen Tile-Typ platziert werden.

| Tile-Typ | Harvester | Andere Gebäude |
|----------|-----------|----------------|
| `terrain_empty`, `terrain_hazard` | ✗ nicht erlaubt | ✓ erlaubt |
| `regolith_*` (alle Ertrags-/Mächtigkeits-Kombinationen) | ✓ erlaubt | ✗ nicht erlaubt |
| `terrain_impassable` | ✗ | ✗ |

- Der Harvester darf **ausschließlich** auf Ressource-Tiles (`regolith_*`) platziert werden. Terrain-Tiles sind für ihn keine gültige Platzierung.
- Alle anderen Koloniegebäude dürfen **nicht** auf Ressource-Tiles gebaut werden. Nur Terrain-Tiles sind für reguläre Gebäude gültig.
- Diese Regel gilt auch beim Verlegung des Harvesters (neues Ziel muss ein `regolith_*`-Tile sein).

**Begründung:** Regolith-Tiles sind Abbaugebiete — ihre Fläche ist durch den Harvester belegt oder für zukünftigen Abbau reserviert. Würde man dort reguläre Gebäude bauen, würde das Vorkommen dauerhaft verschlossen. Umgekehrt wäre ein Harvester auf Terrain-Tiles sinnlos (keine Rohstoffe).

### Baukosten & Level-Up-Kosten

Der Hex-Bau-Flow zieht Ressourcen ab (canonical source: `config/buildings.php → build_cost` / `regolith_per_levelup`, in die `building_costs`-Tabelle gesynct via `game:sync-config`). Drei getrennte Kosten-Achsen:

**1. Errichten (Tile leer → Level 1, Einmal-Abzug):** Beim Platzieren zahlt der Spieler die Errichtungskosten **und** das Level-Up-Regolith für Stufe 1 in einer Zahlung; die Stufe 0→1 kostet danach nur noch AP.
- **Regolith** für alle Gebäude außer CC + Harvester (Kosten gestaffelt nach Gebäudetyp und Verfügbarkeitspfad; siehe `config/buildings.php → build_cost`).
- **Werkstoffe** nur für späte/High-Tech-Gebäude **ab CC Lv3+**, als knapper Akzent, nicht als Hauptkosten (jeder Werkstoff ist eine harte Credits-Ausgabe über den Import, §3). Uplink-Station Lv1, Analytik-Labor und Hangar sind **werkstofffrei** (Uplink-Station ist das Import-Gate, würde eine Zirkelschluss-Regel erzeugen; die Pfad-Gebäude erhalten dieselbe Behandlung zur Pfad-Parität). Siehe `docs/game-reference.md#gebäude-baukosten` für exakte Werte.
- **Kolonisten-Gate:** Bau nur möglich, wenn genug freie Kolonisten für die Arbeitsplätze des Gebäudes da sind (`supply_cost`, §6). Kein Abzug — reine Belegungsprüfung.

**2. Level-Up (jedes Level, flach — keine Eskalation):**
- **Regolith:** Ein fester, flacher Betrag je Level, gleich für alle Gebäude außer CC und Harvester (`game.build.levelup_regolith_flat`, `docs/game-reference.md`). Bewusst keine pro-Level-Steigerung. Abzug erst beim **Abschluss** des Level-Ups (`ap_spend ≥ ap_for_levelup`), nicht pro AP-Klick → AP-Invest bleibt reibungsarm.
- **CC-Upgrade (Sonderfall):** skaliert mit dem Ziel-Level, das CC ist der zentrale Progressionshebel. Er bleibt eine bewusste Regolith-Investition und kostet **zusätzlich steigende AP je Stufe** (siehe `config/buildings.php`, `docs/game-reference.md`) — AP ist die Währung des Tempos. Zusammen mit dem Stufen-Gate (§13 „Slot-System") entscheidet die CC, wie schnell die Kolonie in die Tiefe wachsen darf.
- Harvester: **kein Level-Up** (§13.5). Er liefert ein festes Regolith-Grundeinkommen je Standort; Wachstum kommt aus Kenntnissen (`geology`), Funden, Missionen und Handel.

**3. Reparatur (laufender Dauer-Sink):**
- **Regolith + Construction-AP pro Reparatur-Schritt** (siehe `config/game.php`). Decay läuft bis Run-Ende → Reparatur hält Regolith über den gesamten Run relevant (Errichtungs-/Level-Up-Kosten allein versiegen nach Vollausbau).
- **Hartes Gate:** kein Regolith → Reparatur-Button gesperrt, Tooltip verweist auf Harvester-Reparatur. Kein Negativ-Saldo, kein Schuldensystem.
- **CC + Harvester ausgenommen** (AP-only) → die Regolith-Quelle bleibt immer reparierbar, die Decay-Spirale ist ein erholbarer Rückschlag, kein Hard-Deadlock.

**4. Rückbau (Spieleraktion, kostenlos):**
- Senkt die Stufe einer Instanz um eins, ohne Kosten, ohne Voraussetzungen, ohne Rückerstattung. Die CC bleibt mindestens auf Stufe 1. Ein Rückbau auf Stufe 0 räumt das Tile, und derselbe Rückbau bricht eine platzierte Baustelle ab. Investierte AP eines laufenden Ausbaus verfallen. Der Rückbau löst kein Vertrauens-Ereignis aus, es gilt Bestandsschutz für bereits Bestehendes, und der Spieler bestätigt ihn in einem Modal-Dialog, der die Folgen nennt. Vollständige Regeln: §11.5 ([`docs/gdd/techtree.md`](gdd/techtree.md)).

Alle vier Aktionen finden ausschließlich im Tile-Panel der Kolonieansicht statt, nicht im Techtree (§11).

> **Designziel:** Regolith ist das „Mehl" (reichlich, lokal, Dauer-Sink über Bau + Reparatur), Werkstoffe das „Salz" (knapp, importiert, nur als Akzent). Schiffe kosten ausschließlich Credits.

> **Kein Lagerlimit:** Es gibt kein Resource-Cap-System (Lagerlimit für Regolith/Werkstoffe/Organika) und kein Depot-Gebäude. Begründung: Das eigentliche Spielproblem ist Ressourcenknappheit, nicht -überschuss; ein Lagerlimit hätte aktive Produktion bestraft statt belohnt — Widerspruch zum Roguelike-Designprinzip "kein Leerlauf, aktives Spielen wird belohnt". Bei Bedarf (z. B. neue Run-Modifier, die Überschuss als Mechanik nutzen) kann Depot + Cap-System später erneut eingeführt werden.

---

### Leitstelle (securityHub) — Mechanik

Die Leitstelle ist ein auf 1 Instanz begrenztes Infrastrukturgebäude (CC Lv2, max. Lv3). Sie ist kein Pfadwahl-Kandidat und unterliegt keinem Pfadwahl-Bau-Gate. Sie öffnet **keinen Berater-Slot** — sie trägt sich vollständig über ihre drei unabhängigen Effekte:

**Passiv — Vertrauen-Bonus:**
Ein Bonus pro Level (kumulativ). Thematisch: "Die Bevölkerung fühlt sich durch Schutzinfrastruktur sicherer." Bewusst niedriger als andere Wohlfahrts-Gebäude — Sicherheitsinfrastruktur ist utilitaristisch, kein Luxus-Bonus. Exakte Werte: `config/buildings.php`.

**Passiv — Event-Dämpfung:**
Wenn der Hub aktiv ist, werden negative Vertrauensverluste aus Zwischenfällen reduziert (prozentual; siehe `config/buildings.php`). Gilt für die Events `encounter_lost` und `colony_threatened`. Thematisch: "Der Hub sorgt nicht dafür, dass Vorfälle ausbleiben — er verhindert, dass sie eskalieren."

**Passiv — Level-Down-Recycling:**
Wenn ein Gebäude durch Decay ein Level verliert, gibt die Kolonie automatisch einen kleinen Anteil der Errichtungskosten zurück (Regolith, Werkstoffe). Das Recycling gibt es erst ab Leitstellen-Stufe 3. Der Anteil liegt bewusst deutlich unter dem Reparaturwert, damit kein Anreiz entsteht, Verfall absichtlich zu provozieren. Exakte Prozentsätze: `config/buildings.php`.

> **TODO Balance:** Alle drei Effekte (trust-Bonus, Event-Dämpfungs-%, Recycling-%) nach erstem Playtest kalibrieren (siehe `config/buildings.php`). Compounds-Anforderung ist akzeptiert: Hub ist kein Progression-Gate (CC Lv3 hat kein Pflichtgebäude), sondern ein optionaler Resilienz-Baustein. In runs mit schlechtem Trade-Zugang kann der Hub später kommen — das verzögert nichts Zwingendes.

---

### Uplink-Station (uplinkStation) — Mechanik

Die Uplink-Station ist das einzige Kommunikationsgebäude der Kolonie — 1 Instanz, Lv1–3. **Ohne Uplink-Station Lv1 sind aktive Nexus-Anfragen gesperrt** (Direktimport). Eingehende Nexus-Funk-Nachrichten des Nexus (Milestones, Warnungen) kommen immer an — diese sind nicht abhängig vom Gebäude.

| Level | CC-Voraussetzung | Freischaltet / Effekt |
|-------|-----------------|----------------------|
| 1 | CC Lv2 | Aktive Nexus-Anfragen: **Direktimport** (Werkstoffe gegen Credits, fester Preis, sofort verfügbar, siehe §3; Regolith und Organika gegen Credits, fester Preis, verzögerte Lieferung, siehe unten) |
| 2 | CC Lv3 | Tiefenscan kostet weniger AP (`ColonyTileService`); Direktimport-Lieferzeit für Nicht-Werkstoffe sinkt |
| 3 | CC Lv5 | Direktimport-Lieferzeit für Nicht-Werkstoffe sinkt weiter |

**Direktimport für Nicht-Werkstoff-Ressourcen :** Anfrage und Bezahlung passieren sofort wie beim Werkstoff-Direktimport, die Lieferung selbst braucht jedoch mehrere Sole (`game.economy.delayed_import_delivery_ticks`, sinkend mit dem Uplink-Station-Level). Der **einzige** Hebel zur Verkürzung ist das Uplink-Station-Level selbst — bewusst kein zusätzlicher Hebel über Konsul-Rang, Kenntnisse oder situative Boni, um den Direktimport als reine Infrastrukturfrage zu halten. Das betrifft die *Lieferzeit*; der *Preis* des Direktimports ist ein eigener Kanal des Handelspostens (Stufe III, siehe „Handelsposten — Mechanik" unten). Dieser Weg ist das Sicherheitsnetz für nicht lokal beschaffbare Ressourcenmengen (§12).

**Baukosten Lv1:** Ausschließlich Regolith + Credits — keine Werkstoffe, um einen Zirkelschluss zu vermeiden (Werkstoffe über Nexus anfordern setzt das Gebäude voraus).

> **Offene Ideen (nicht umgesetzt):** häufigerer Besuch des Reisenden Händlers ab Lv2; Run-Abschluss-Aktion „Kolonialbericht" mit Meta-Bonus für den nächsten Run (Lv3). Baukosten und Effekt-Parameter: siehe `config/buildings.php`.

---

### Handelsposten (tradingPost) — Mechanik

Der Handelsposten ist ein auf 1 Instanz begrenztes Wirtschaftsgebäude (CC Lv4, konkurriert mit Religiöser Stätte um dasselbe Tile-Budget). Er verbessert Handelskonditionen über mehrere Kanäle hinweg:

Der Handelsposten ist **Infrastruktur**: Seine Wirkung hängt nicht davon ab, ob ein Konsul zugewiesen ist. Einen „Konsul-Effizienz"-Effekt (AP-Kostenrabatt auf Handelsgeschäfte) gibt es nicht: Handelsgeschäfte sind Handlungen, und Boni senken nur Projektkosten (§13.3). Der Konsul verbessert stattdessen die *Qualität* seiner Handlungen (§12).

**Passiv — Kanal-Rabatt:**
Jede Ausbaustufe schaltet einen zusätzlichen Handelskanal frei, kumulativ: Stufe I (Bekannter Gast) die Cantina-Angebote, Stufe II (Fester Kunde) zusätzlich Corvans Sonderinventar, Stufe III (Persönlicher Kontakt) zusätzlich die Nexus-Kanäle. Der Rabatt ist kein eigener Stack, sondern eine sichtbare Quelle im **Handelsvorteil** des jeweiligen Kanals (§12): Er addiert sich dort mit dem Konsul-Rang (nur Cantina-Kanal) und der `trade`-Kenntnis. Auch bei verhandelten Angeboten gilt er — Verhandeln ist ein zusätzlicher Aufschlag oben drauf, keine Konkurrenz zum Handelsposten (Begründung §12).

**Stufe III (A13):** Stufe III senkt den **Preis** des Nexus-Direktimports (Werkstoffe sowie die verzögerten Importe von Regolith und Organika). Ausdrücklich **nicht** die Lieferzeit: sie bleibt eine reine Uplink-Station-Frage (siehe Direktimport-Abschnitt oben). Der Nexus-Kanal hat bewusst nur diese zwei Quellen (Handelsposten, `trade`) und keinen Konsul-Anteil; damit bleibt sein Vorteil klein genug, dass der Nexus auch im besten Fall der teurere Fallback über dem lokalen Preisniveau bleibt. Hier wirkt der Vorteil als Preisnachlass in genau der angezeigten Höhe.

Exakter Rabattsatz: `config/buildings.php` → `merchant_price_bonus`.

**Zusätzlich — Kenntnis-Preisbonus:** Die `trade`-Kenntnis liefert unabhängig vom Handelsposten einen eigenen Preis-Bonus auf den Handelskanälen (Cantina-Angebote, Corvans Sonderinventar, Nexus-Kanäle). Beide Quellen stacken additiv im Handelsvorteil (§12), ohne Ausschluss. Exakte Werte: `config/knowledge.php` → `trade.trade_price_bonus_per_lv`.

> **TODO Balance:** Baukosten und Decay nach erstem Playtest festlegen (siehe `config/buildings.php`).

---

### Status-Punkte

Jedes Koloniegebäude hat ein `status_points`-Feld. Das Maximum (`max_status_points`) ist in der `buildings`-Tabelle hinterlegt. Status-Punkte sinken pro Sol durch Verfall (siehe Abschnitt 7).

**Leveled vs. Instanced Buildings:**

- **Leveled** — ein Objekt auf einem Tile, wird stufenweise ausgebaut (z.B. CC Lv1→5, Agrardom). Ein Klick auf das Tile → "Ausbauen". (Der Harvester ist **nicht** leveled — `max_level = 1`, §13.5.)
- **Instanced** — jede Einheit ist ein eigenes Objekt auf einem eigenen Tile (z.B. Wohnhabitat max. 6 Einheiten). Jede Instanz kann separat auf Lv1–3 ausgebaut werden und hat eigene Status-Points.

Das Config-Flag `is_instanced` in `config/buildings.php` steuert das Verhalten. In der DB haben Instanced Buildings eine `instance_id` als Teil des zusammengesetzten PK (`colony_id + building_id + instance_id`).

Das UI-Verb ist immer identisch: **"Tile ausbauen"** — ob Leveled oder Instanced darunterliegt, ist ein Implementierungsdetail das der Spieler nicht sieht. "Neues Wohnhabitat bauen" bedeutet: neues Tile mit Instanz Lv1 belegen. "Wohnhabitat ausbauen" bedeutet: bestehende Instanz von Lv1 auf Lv2 heben.

---

## 4a. Kolonieoberfläche

### Darstellung: Hex-Grid

Die Kolonieoberfläche wird als 2D top-down Hex-Grid dargestellt. Die Karte hat immer **3 Ringe** (rings 0–3, gesamt 37 Tiles). Planetentyp und Run-Schwierigkeit beeinflussen die Tile-Qualität (Häufigkeit reicher Vorkommen, Hazard-Dichte), nicht die Kartengröße.

### Zwei Zonen

**Kolonie-Zone** — ein Set von Terrain-Tiles rund um das CC. Hier werden Gebäude gebaut (ausschließlich auf `terrain_empty`/`terrain_hazard`-Tiles). CC-Level-Upgrades fügen der Kolonie-Zone weitere Terrain-Tiles hinzu.

**Exploration Zone** — alle Tiles, die nicht zur Kolonie-Zone gehören. Hier liegen Ressourcenquellen (Regolith-Tiles), Gefahren und Event-Spots. Der Harvester steht hier auf einem Regolith-Tile. Jedes Tile muss einzeln per Navigation-AP erkundet werden (Erkundungsaktion des Raumfahrer-Beitrags am Pool; auch der Erkundungsflug der Drohne deckt Tiles auf).

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
- **Erkunden** (Navigation-AP): Erkundungsaktion oder Erkundungsflug lüften den Nebel und finden Ressourcen/Signale. Erkunden ist die einzige Quelle von Tile-Wissen.

Die AP-Kosten pro erkundetem Tile steigen mit dem Ring (`config/game.php → colony.explore_cost_per_ring`), gestaffelt von innen nach außen. Die Staffelung verlangsamt das vollständige Aufdecken der Karte bewusst — eine Pauschalrate würde die Karte zu schnell enthüllen und den Spannungswert des Fog of War zunichtemachen. Ring 1 ist beim Run-Start bereits automatisch erkundet; der Kostensatz greift praktisch nur für nachträglich erschlossene Tiles.

Daraus folgt:
- **Kolonie-Zone-Tiles** sind baubar, aber ggf. noch im Fog (`is_colony_zone=1, is_explored=0`). **Bauen auf einem solchen Tile deckt es auf** ("siedeln → sehen"). Der Spieler kann optional vorher per Navigation-AP erkunden, um vor dem Bauen zu sehen, was dort liegt (z.B. Gefahrenzone).
- **Exploration-Zone-Tiles** bleiben Fog of War — einzeln per Navigation-AP aufgedeckt (Ring-gestaffelte Kosten s.o.). Hier liegt der Erkundungs-Lohn (Regolith fürs Harvester-Verlegen, Signale/Funde in Ring 2 und 3).

**Signale, Tiefenscan, Bergung:** Zu Run-Beginn liegt auf einigen Außenring-Feldern ein verstecktes Signal. Ein Tiefenscan kostet wenig AP und zeigt, was dahintersteckt — ein Fehlalarm, ein kleiner, mittlerer oder großer Fund. Solange ein echter Fund nicht geborgen ist, bleibt sein Feld unbebaubar; ein Fehlalarm gibt das Feld nach dem Scan frei. Gehoben wird ein Fund auf einem von zwei Wegen, die nacheinander gelten statt nebeneinander. Ohne Hangar birgt die Kolonie selbst: eine Sofort-Aktion mit wenigen AP je nach Fundgröße, das Regolith ist im selben Moment im Lager (kein Tageslimit, kein Projekt). Sobald ein Hangar steht, gibt es für Funde nur noch den Bergungsflug (§8b): Ein Schiff holt den gescannten Fund ohne Würfelwurf ab, kostet nur einen einzigen AP und etwas Organika als Proviant, verschleißt nicht und bringt dasselbe Regolith wie die Selbstbergung — der Preis ist die Flugzeit, nach der das Regolith erst ankommt, und das belegte Schiff. Große Funde trägt nur der Frachter; mit Hangar-Stufe 1 bleiben sie bewusst gesperrt, bis Stufe 2 und ein Frachter da sind. Die Fundkarte zeigt genau eine Aktion mit einer Begründungszeile. Der Vorrat ist ein fester, für alle Eröffnungen identischer Pool — er begünstigt weder Labor, Hangar noch Cantina und bleibt in dieser Fassung ohne Nachschub. Zahlen: `docs/game-reference.md#17a-fundpool-t30-pool-v1-gamefinds`.

> Kernregel: **Die CC erschließt nur Gelände — sie siedelt nicht ins Unbekannte.** Erschließen ≠ Erkunden. Der CC-Ausbau erkundet keine Tiles automatisch — sonst wären die beiden Achsen für den Spieler ununterscheidbar.

> **Blocker unter Fog — Lücken-Deduktion :** Unaufgedeckte Tiles können `terrain_impassable` sein — der Spieler riskiert beim Erkunden also AP für ein nutzloses Tile. Das ist ring-abhängig unterschiedlich bewertet und bewusst so entschieden:
> - **Ring 2 enthält keine Blocker mehr** (`ColonyTileService::resolveTileType()`, Gewicht auf Hazard/Empty umverteilt). Grund: Ring 2 hat kein Regolith — eine Lücke in der "bald bebaubar"-Anzeige hätte dort *deterministisch* einen Blocker verraten. Das Aufdecken wäre beweisbar verschwendete AP (Falle ohne Entscheidung), das Nicht-Aufdecken trivial. Beides ist keine interessante Wahl.
> - **Ring 3+ behält Blocker selten.** Dort ist eine Lücke in der Anzeige mehrdeutig: meist Regolith (der Jackpot fürs Harvester-Verlegen), manchmal Fels. Das Aufdecken einer Lücke ist damit eine echte Wette mit positivem Erwartungswert — Information durch Abwesenheit als Feature, nicht als Bug. Keine AP-Erstattung beim Blocker-Fund (würde blindes Aufdecken belohnen und die Ring-Kosten-Drossel entwerten), keine Silhouetten unter Fog (würde die Ring-3-Ambivalenz zerstören).

> **Offener Designpunkt (nicht umgesetzt):** Idee, den Erkundungsradius über die aktuelle Ring-3-Grenze hinaus zu erweitern, um zusätzliche AP-Sinks für spätere Sols zu schaffen (die Ring-Staffelung allein bremst, erschöpft sich aber irgendwann). Offene Sorge: ein größeres/dichteres Hex-Grid wird auf Mobile schwer navigierbar (Pan/Zoom-Aufwand steigt mit der Tile-Zahl). Vorzugsweise die Tile-Zahl von der AP-Sink-Zahl entkoppeln statt das Grid zu vergrößern — z.B. Signale/Points-of-Interest in größerer Entfernung ohne zusätzliches Hex-Rendering, oder eine Scan/Survey-Order auf Distanz statt physischer neuer Hexes. Nicht implementiert — nur als Richtung für ein späteres Balance-/Pacing-Update vermerkt.

### Visuelle Zone-Abgrenzung

Die Kolonie-Zone-Grenze ist auf kleinen Karten kein sauberer Ring, sondern ergibt sich aus dem `is_colony_zone`-Flag pro Tile. Das Frontend rendert Colony-Zone-Tiles mit einem warmen Basis-Tint (Farbschema: Weiß/Anthrazit/Rot-Palette), Exploration-Zone-Tiles mit einem kühleren, dunkleren Tint. Der Spieler erkennt die Grenze durch Farbe, nicht durch Position. Regolith-Tiles und impassable Tiles innerhalb der inneren Ringe sind immer Exploration Zone — sie wirken als visuelle "Lücken" in der Colony Zone, was die unterschiedliche Funktion deutlich kommuniziert.

### Tile-Typen und Schwierigkeit

Tile-Typen (z.B. "Reicher Erzknoten", "Armes Vorkommen", "Organik-freies Terrain") beeinflussen die Ressourcenproduktion. Die Schwierigkeit eines Runs steuert die Tile-Qualität: schwieriger Run = schlechtere Vorkommen, keine reichen Erznodes in Ring 1.

### Organika

Organika entsteht nicht auf Tiles (biologische Materialien kommen auf Planeten nicht natürlich vor). Stattdessen produziert der **Agrardom** (Gebäude innerhalb der Kolonie-Zone) Organika passiv pro Sol.

Organika wird **nicht** in Bau- oder Schiffskosten verwendet (§3 Verwendungsmatrix). Ihre Sinks (implementiert):

1. **Verpflegung (laufend, eskalierend):** Zielregel (Owner-Entscheidung): **1 Organika versorgt 1 Kolonisten pro Sol**, und verpflegt werden **alle Kolonisten**, nicht nur die beschäftigten. *Umsetzungsstand:* Der Code rechnet noch mit einem Teiler und der Zahl der beschäftigten Kolonisten (`game.food.supply_per_eater`, `ResourcesService::foodNeed()`); die Angleichung samt Produktions-Abstimmung steht aus (ROADMAP T36, T43-G7). Tick-Reihenfolge: Produktion → Verpflegung → Vertrauen (Schritt 3a). Deckt der Vorrat den Bedarf → Bonus-Vertrauen, Hunger-Streak zurückgesetzt. Reicht der Vorrat nicht → verfügbarer Rest wird verbraucht, Hunger-Streak wächst, und ein **eskalierender** Trust-Malus greift (`TrustService::hungerPenalty`) — kein weicher Einmal-Tick, sondern eine Spirale: weniger Vertrauen → Produktionseinbruch → noch weniger Organika. Sättigung setzt den Streak (und damit den Malus) sofort zurück. Macht den Agrardom zum Pflichtgebäude.
2. **Missions-Proviant (einmalig):** Hangar-Dispatch (`HangarService::dispatchShip`) kostet beim Start Organika je Sol Distanz (Crew-Verpflegung) **und** AP je Sol Distanz; bei Mangel an beidem wird die Entsendung blockiert. (Config `config/missions.php → organika_per_sol` / `nav_ap_per_sol`.)
3. **Handel:** Organika ist in der Cantina gegen Credits verkaufbar (`bar.base_prices`).

Drei handelbare Kolonieressourcen (Regolith, Werkstoffe, Organika) erhalten bewusst das Catan-Tauschdreieck — mit nur zwei kollabiert die Handelstiefe.

### Gefahren und Ereignisse

Events können sich auf der Kolonieoberfläche abspielen (z.B. Meteoriteneinschlag auf Tile X, Statusverschlechterung durch Sturm). Gebäude werden nicht zerstört — ihr Status-Punkte-Wert sinkt, Reparatur wird nötig.

### Hex-Grid Koordinatensystem

**Koordinatenmodell:** Axial-Koordinaten (q, r). Jedes Tile wird durch ein Zahlenpaar (q, r) eindeutig identifiziert. Das CC-Tile steht bei (0, 0). Ringzugehörigkeit: `ring = max(|q|, |r|, |q+r|)`.

**Orientierung:** Pointy-top (Spitze zeigt nach oben).

### Tile-Typ-Katalog

Tile-Typen definieren die **Mechanik** eines Tiles — nicht sein Aussehen. Die visuelle Darstellung hängt vom Planetentyp ab (Theme-Schicht, unabhängig vom Tile-Typ). Definitionen in `config/tile_types.php`.

**Terrain-Tiles:**

| Typ-Key | Beschreibung |
|---------|-------------|
| `terrain_empty` | Begehbar, leer, bebaubar |
| `terrain_hazard` | Gefahrenzone — bebaubar, derzeit ohne eigene Mechanik (Konzept „Neutralisierung durch die Korvette" ist nicht umgesetzt; im inneren Ring gibt es keine) |
| `terrain_impassable` | Nicht begehbar, nicht bebaubar (Klippen, Abgründe, Lavaströme — je nach Planetentyp) |

> **UI-Render-States (kein DB-Typ):** `terrain_fog` und `terrain_locked` sind keine `tile_type`-Werte in der DB — sie sind visuelle Zustände die das Hex-Grid aus `is_explored` + `is_colony_zone` ableitet. `terrain_fog` = unerkundetes Kolonie-Zone-Tile; `terrain_locked` = unerkundetes Exploration-Zone-Tile. Beschreibung in `docs/lore/tiles.md`.

**Ressource-Tiles (für Harvester):**

Zwei unabhängige Achsen, je drei Stufen: Ertrag (Rg pro Sol, `y1`-`y3`) und Mächtigkeit (Gesamtvorkommen, `d1`-`d3`). Typ-Key-Muster: `regolith_y{Ertragsstufe}_d{Mächtigkeitsstufe}`. Die höchste Kombination auf beiden Achsen gleichzeitig (höchster Ertrag UND größte Mächtigkeit) existiert bewusst nicht — es gibt kein "perfektes" Vorkommen. Konkrete Werte je Kombination: siehe `docs/game-reference.md`.

**Signale und Funde** (durch Tiefenscan enthüllt — vorher nur als generisches Signal sichtbar):

| Typ-Key (`event_type`) | Beschreibung |
|---------|-------------|
| `find_small` | Kleiner Fund |
| `find_medium` | Mittlerer Fund |
| `find_large` | Großer Fund |
| `find_false` | Fehlalarm |

Zu Run-Beginn wird ein fester Pool von Signalen auf Außenring-Felder verteilt (`game.finds.start_pool`); was jeder Typ bringt, steht in `game.finds.types`. Das Signal bleibt bis zum Tiefenscan verborgen (§4a „Signale, Tiefenscan, Bergung").

Die weiteren `event_*`-Typen in `config/tile_types.php` (Wrack, Ruine, Bunker, Sonde, Kristall, Auslass, Höhle, Depot, Signal, Anomalie) werden von der Kartengenerierung nicht gesetzt. Die Missionen, die ein Ruinen-Tile als Ziel brauchen, sind im Normalbetrieb daher nicht erreichbar (ROADMAP T31).

### Planetentypen

Planetentypen (Gestein, Wüste, Eis, Ozean, Vulkan) mit eigener Tile-Qualität und eigenen Ereignis-Pools sind ein Entwurf und **nicht umgesetzt**. Die Kolonie liegt auf einem generischen Planeten; die Variation zwischen Runs entsteht aus der zufälligen Kartengenerierung (Startposition, Regolith-Vorkommen, Hazard- und Blocker-Dichte nach Ring, Fundverteilung).

### colony_tiles — Datenbankschema

Jedes Tile der Kolonieoberfläche wird als Zeile in `colony_tiles` gespeichert:

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | PK | |
| `colony_id` | FK → glx_colonies | |
| `q` | integer | Axial-Koordinate |
| `r` | integer | Axial-Koordinate |
| `ring` | integer | 0 = CC-Tile, 1–3 = Ring-Nummer (Karte hat max. 3 Ringe) |
| `tile_type` | string | Primärer Typ, z.B. `regolith_y2_d2` — sichtbar nach normalem Scan |
| `event_type` | string nullable | Signal-Overlay (`find_*`), NULL = kein Signal — sichtbar erst nach Tiefenscan |
| `is_colony_zone` | boolean | Tile gehört zur Kolonie-Zone (CC-Level-Expansion hat es freigeschaltet). Regolith- und impassable-Tiles sind immer false. |
| `is_explored` | boolean | Normaler Scan (AP) abgeschlossen |
| `is_deep_scanned` | boolean | Tiefenscan abgeschlossen — enthüllt `event_type` |
| `resource_amount` | integer nullable | Verbleibende Ressourcenmenge |
| `resource_max` | integer nullable | Startwert (Basis für Erschöpfungs-Counter im UI) |

---

## 4b. Die drei Pfade

Ab CC Lv2 stehen dem Spieler drei Gebäude offen — **Analytik-Labor**, **Hangar** und **Cantina** —, von denen zunächst nur eines gebaut werden kann. Diese Wahl ist die erste strategische Weichenstellung eines Runs und prägt seinen weiteren Verlauf.

Die Mechanik der Pfadwahl (Bau-Gates, Berater-Slots, Reihenfolge-Auflösung) steht in §13 „Slot-System"; die Kostenbalance in §6 „Pfadwahl-Kostenbalancing". Dieser Abschnitt beschreibt, **was die Pfade sind** — als Designentitäten, nicht als Gate-Logik.

### Was ein Pfad ist — und was er nicht ist

Ein Pfad ist **kein Ausschluss, sondern eine Sequenzierung.** Wer bei CC Lv2 die Cantina baut, bekommt Analytik-Labor und Hangar bei CC Lv3 bzw. Lv4 trotzdem — nur später. Die Wahl bestimmt **Reihenfolge und Zeitvorsprung**, nicht endgültigen Zugang. Das folgt dem Prinzip aus §1.1: keine bestrafenden Permanent-Konsequenzen für frühe Entscheidungen.

Was ein Pfad liefert, ist deshalb ein **Vorsprung von 1–2 CC-Leveln** in seinem Bereich — plus den zugehörigen Berater-Slot, der entsprechend zeitiger besetzbar ist.

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

**Pfad A — Analytik-Labor.** Der Pfad der dauerhaften Verbesserung. Kenntnisse sind die einzige Progression im Spiel ohne Verfall (§10): einmal erreicht, bleiben sie. Ihre Wirkung liegt in den Kenntnis-Effekten (§10); Kenntnisse schaffen keinen Wohnraum, mehr Ausbautiefe kommt allein aus Wohnraum (§6). Wer diesen Pfad zuerst geht, spielt auf Zinseszins: früh teuer, spät mühelos.

**Pfad B — Hangar.** Der Pfad der aktiven Versorgung. Schiffe erschließen den Missionskatalog (§8b) und damit die breiteste Ressourcenbasis im Spiel — Regolith (Bergungsflüge), Organika, Credits, Werkstoffe und Tile-Aufklärung kommen alle über Missionen. Der Preis ist dauerhafter Aufwand: jede Mission kostet AP und Proviant, Schiffe verschleißen und wollen repariert werden. Wer diesen Pfad zuerst geht, hat früh viele Hebel, muss sie aber jeden Sol bedienen.

**Pfad C — Cantina.** Der Pfad der Flexibilität. Die Cantina selbst gibt Vertrauen (`trust_per_lv`), und der Handel erlaubt, jeden konkreten Engpass gegen Credits zu lösen — statt ihn produzieren zu müssen. Wer diesen Pfad zuerst geht, ist gegen Überraschungen am besten aufgestellt, hängt dafür aber an der Credits-Decke und an der Angebotslage.

> ⚠️ **Credits-Hebel von Pfad C:** Es gibt keinen garantierten Dauer-Einkommens-„Handelsvertrag" für Konsul und Cantina; er wäre ohne weiteres Zutun stärker als die Hebel von Analytiker und Raumfahrer und verletzte die Paritäts-Anforderung unten. Die Paritäts-Anforderung gilt für **Pfad C**, nicht für den Konsul selbst — der Berater hat eigenständigen Wert (`trader_discount`, Werkstoffe-Bias, §13). Der Credits-Hebel hängt deshalb bewusst an der **Cantina als Gebäude/Pfad-Identität**, nicht am Konsul-Rang: der **Cantina-Begegnungspool** (§12, Kanal 1). Details dort.

### Vertiefung: dieselbe Struktur in allen drei Pfadgebäuden

> **Status: umgesetzt (T41), alle Werte Platzhalter** (`config/path_depth.php`, nicht gemessen). Umsetzungsplan und offene Fragen: `docs/superpowers/specs/2026-10-23-pfad-vertiefung.md`.

Jedes Pfadgebäude bietet mehrere **Dinge**, und jedes Ding lässt sich in fünf Stufen vertiefen. Im Labor ist das längst so: sieben Kenntnisse, jede in Stufen erforschbar (§10). Cantina und Hangar bekommen dieselbe Bauart:

| | **Labor** | **Cantina** | **Hangar** |
|---|---|---|---|
| Das Ding | Kenntnis | Figur | Missionstyp |
| Fortschritts-Begriff | Kenntnis-Level | **Vertrautheit** | **Missionserfahrung** (Label-Vorschlag) |
| Wie man aufsteigt | AP investieren (planbar) | AP investieren (planbar) | AP investieren (planbar) |
| Was eine Stufe bringt | Kenntnis-Effekt (§10) | Fortschritt am Vertrautheitsbalken; mehr Zugang zur Figur (Dialog, Kodex, später Anliegen mit besserer Belohnung) ist Ausbau-Idee, in der ersten Version ohne Zusatzeffekt | höherer Ertrag der skalierbaren Belohnungen der Missionsfamilie (linear je Stufe) |

**Das ist ein Strukturmodell, kein Bildschirm.** Der Spieler sieht nirgends ein gemeinsames Raster. Jede Oberfläche zeigt den Fortschritt in ihrer eigenen Form: das Labor im Techtree, die Cantina als Balken an der Figur, der Hangar als Balken am Missionstyp. Nur die Bauart ist gleich, damit der Spieler das Prinzip einmal lernt.

**Warum das gut ist.**
- *Parität (§4b):* Jedes Pfadgebäude bekommt eine eigene, dauerhafte Fortschrittsspur und damit eine eigene Antwort auf „Wohin investiere ich?“. Das bedient die Idee gleichwertiger Eröffnungen, ohne Labor-Forschung in Hangar oder Cantina zu kopieren.
- *Breite gegen Tiefe* bleibt die Entscheidung überall: wenige Dinge tief oder viele flach.
- *Verständlichkeit:* angezeigte Stufe = wirkende Stufe, die nächste Schwelle ist sichtbar.

**Aufstieg überall per Investition.** Alle drei Pfadgebäude steigen durch AP-Investition, nicht durch Nutzung. Das gibt den Pfaden dieselbe, einmal erlernbare Regel und dem Hangar eine Senke für sonst ungenutzte AP. Version 1 kennt keinen Fortschritt aus der Ausführung: Der Hangar ist ein Training (Simulator, Übungsflüge ohne Einsatz), die Cantina Beziehungspflege (Zeit mit der Figur verbringen, erst nach dem ersten Treffen der Figur im Lauf). Das Labor funktioniert schon so.

**Ausbau gegen Ausführung.** Der *Ausbau* (Stufenaufstieg) kostet nur AP aus dem einen gemeinsamen Pool, keine Rohstoffe, analog zur Kenntnis im Labor. Die *Ausführung* (Hangar-Mission: AP, Organika, Verschleiß; Cantina-Gespräch oder Handel: AP, beim Handel Rohstoffe) behält ihre Kosten. Die Stufe macht die Ausführung besser (z. B. Ertrag), nicht billiger oder anders bezahlt. Beide ziehen am selben AP-Pool und konkurrieren darum; das ist die Senke für Leerlauf-AP (Hangar-First). Kostenverlauf: Spec, Abschnitt 4b.

**Begriffe.** „Vertrauen“ bleibt allein die Kolonie-Stimmung (§14). Die Bindung an eine Figur heißt **Vertrautheit**, die Erfahrung mit einer Missionsfamilie **Missionserfahrung** (Vorschlag; Alternative „Training“) — sonst würde ein vierter „Level“ oder ein zweites „Vertrauen“ den Spieler verwirren. Der Balken an der Figur ist ein Vertrautheitsbalken.

**Cantina.** Alle Figuren (`config('characters')`) behalten ihre Vielfalt und können eine Vertrautheit aufbauen. Die Vertrautheit wächst durch AP-Investition in die Figur. Die Investition ist als **Gespräch** gerahmt: Je Figur gibt es mehrere Gesprächsoptionen mit unterschiedlich hohem AP-Einsatz; der Spieler entscheidet, wie viel Bemühung er in das Gespräch steckt. Das Gespräch liefert keinen zusätzlichen Ertrag, nur den Fortschritt am Balken (reine AP-Investition, Variante C); der Fortschritt ist proportional zu den eingesetzten AP, ohne Mengenrabatt (Annahme, damit die angezeigte Zahl die wirkende ist). Das Sol-Limit gilt wie beim Bauen, im Labor und im Hangar-Training auch hier als AP-Deckel je Figur und Sol (Annahme; Höhe und etwaiger Rang-Bonus nach Messung). Der Kodex zählt weiterhin getrennt die Begegnungen über alle Läufe (§12, Kodex-Trigger je `game_role`). Der Barkeeper ist die Figur ohne Zufallsspawn und bildet seine vier Bonus-Stufen schon heute aus wiederholtem Austausch — er ist der Prototyp dieses Prinzips. Was jede einzelne Figur auf höheren Stufen mechanisch bringt, wird separat entworfen und gehört nicht in die erste Version; die erste Version macht den Fortschritt nur sichtbar.

**Hangar.** Der Missionskatalog (§8b) wird zu fünf **Missionstypen** (Familien: Erkundung, Bergung, Versorgung, Handel und Kurier, Schutz) gebündelt, damit nicht jede Einzelmission eine eigene Fortschrittsspur braucht. Jede Stufe einer Familie erhöht den Ertrag ihrer skalierbaren Belohnungen (Credits, Organika und ähnliche Mengen) um einen kleinen, gleichbleibenden Anteil; die Entscheidung liegt in Breite gegen Tiefe über die Familien. Der Missionsdialog nennt Familie und Stufe und zeigt den Ertrag samt Familienbonus. Die Ertragssteigerung bleibt klein (Credits-Inflation, §4b). Die Missionserfahrung einer Familie wächst durch AP-Investition, nicht durch geflogene Missionen.
**Freischaltung nach Hangar-Stufe.** Eine Familie ist erst trainierbar, sobald der Hangar die Schiffsklasse erreicht hat, die ihre Missionen typischerweise fliegt (Leitschiff): Drohne, Frachter, Korvette. Die Zuordnung Familie → Hangar-Stufe steht in der Config. Das Training braucht kein Schiff und bindet keines — die Erfahrung gehört der Kolonie, nicht dem Schiff.

**Sol-Limit.** Wie der Ausbau von Gebäuden hat auch das Training eine Obergrenze je Familie und Sol (Grundwert; ein Rang-Bonus des Raumfahrers ist nicht vorgesehen). So lässt sich keine Familie in einem einzigen Sol hochziehen; die Wahl zwischen Breite und Tiefe bleibt eine Frage von mehreren Sol. Die Höhe wird erst nach Messung festgelegt, damit die Senke für ungenutzte AP im Hangar-First-Start erhalten bleibt.

**Gleiches Prinzip im Labor.** Auch das Labor nimmt je Kenntnis und Sol nur begrenzt AP auf (Deckel nach dem Muster des Gebäude-Deckels; zweckgebundene Bonus-AP wie die des Barkeepers sind ausgenommen). Damit gilt in allen drei Pfaden dieselbe Regel: AP lassen sich nicht an einem Sol in eine einzige Sache kippen. Alle drei Deckel nutzen denselben Sol-Zähler (`DailyApCapService`); die Höhe wird nach Messung festgelegt.

**Grenzen (Absicht).**
- **Kein zweiter Prozentbonus-Stapel.** Raumfahrer-Rang und Kenntnis-Level wirken schon auf Erfolgschance und Proviant (§8b). Die Familienstufe wirkt deshalb nur auf den Ertrag und bleibt klein; weitere Stufenwirkung soll bevorzugt *Neues* freischalten (Varianten, Dialoge, Zugang).
- **Planbar statt Zufall.** Der Fortschritt hängt an der Investition des Spielers, nicht an Glücks-Begegnungen. Ob eine Figur zuvor erschienen sein muss, ist offen (Spec).
- **Ein Lauf, eine Spur.** Die Stufen gelten pro Lauf. Dauerhaft bleibt nur, was schon dauerhaft ist (Kodex).
- **Gebäudestufe als Deckel.** Cantina und Hangar haben weniger Ausbaustufen als die Vertiefung hat Stufen; die Gebäudestufe begrenzt, wie hoch eine Figur oder Familie steigen kann (`path_depth.building_gate`).

**Dies ist die zentrale Designregel der Pfadwahl.** Die Pfade dürfen sich im *Wie* unterscheiden, nicht im *Ob*. Eine Kolonie braucht unabhängig vom gewählten Pfad Regolith, Credits, Organika und Vertrauen. Hat ein Pfad auf eines dieser Grundbedürfnisse keine Antwort, wird der Pfad, der sie hat, faktisch zur Pflicht — und die Pfadwahl zur Scheinentscheidung.

Der Harvester (Regolith) und der Agrardom (Organika) sind der **gemeinsame Sockel**, den jede Kolonie hat. Was ein Pfad beisteuert, ist der Hebel darauf:

| Grundbedürfnis | Sockel (alle) | Pfad A — Analytik | Pfad B — Hangar | Pfad C — Cantina |
|---|---|---|---|---|
| **Regolith** | 1 Harvester, Grundeinkommen pro Sol (§4c) | `geology` erhöht den Harvester-Ertrag | Bergungsflüge holen Regolith aus gescannten Funden | **kein dedizierter Wachstumshebel** — opportunistischer Credits→Regolith-Kauf als Sicherheitsnetz (§12), siehe „Pfad-C-Hebel" unten |
| **Organika** | Agrardom | `agronomy` erhöht die Produktion mit jedem Level (glockenförmig) | Versorgungsfahrten liefern Organika | Ankauf über Bar-Angebote |
| **Credits** | Relaisvergütung, Ratssubvention | ⚠️ Hebel offen | Botenflug / Konvoi-Begleitung | Cantina-Begegnungspool (§12) — Zufallsbegegnungen mit Credits-Ausgang, Bar-Level-skaliert statt Konsul-skaliert |
| **Vertrauen** | Gebäude-Boni, Ereignisse | `health` + Krankenstation | `mission_aid_transport` (Vertrauens-Ereignis) | Cantina `trust_per_lv` + Handelserfolge |

**Pfad A Credits-Hebel:** weiterhin offen (s. Tabelle). Kandidat: Kenntnis-Effekt, der Kosten senkt — passt besser zum Pfad-Charakter als eine zusätzliche Einnahmequelle (ROADMAP „Offene Pfad-Paritäts-Fragen"). **Pfad C Credits-Hebel:** geschlossen über den Cantina-Begegnungspool (§12) — bewusst nicht als verlässliche Dauereinnahme, sondern als unregelmäßige, teils charaktergebundene Zufallsbegegnung, damit der Pfad-Charakter „Flexibilität statt Verlässlichkeit" erhalten bleibt und keine bedingungslose, garantierte, dauerhafte Einnahme entsteht.

> **Prüfregel für künftige Mechaniken:** Wird eine neue Ressource, Kosten- oder Bedarfsachse eingeführt, ist zu prüfen, ob alle drei Pfade sie bedienen können. Ist das nicht der Fall, ist entweder die Mechanik anzupassen oder den unterversorgten Pfaden ein Hebel zu geben — **nicht** die Ungleichheit hinzunehmen.

### Pfad-C-Hebel: Credits statt Regolith

**Pfad C hat keinen dedizierten Regolith-Hebel.** Ein Organika→Regolith-Tausch als Pfad-C-Hebel ist ausgeschlossen — er würde das knappere gegen das häufigere Gut tauschen (Knappheitsordnung §3). Regolith-Zuflüsse neben dem Harvester kommen strukturell über Pfad A (`geology`) und Pfad B (Bergungsflüge); die Neuherleitung steht in §13.7 aus. Der Engpass, gegen den Pfad C als „Pfad der Flexibilität" antritt, ist Credits — und die Kolonie produziert strukturelle Organika-Überschüsse (§4a), die über diesen Hebel monetarisiert werden. Opportunistisch bleibt dem Spieler dennoch ein Nebenweg offen: Der Reisende Händler (Corvan) führt im Alltagsgeschäft auch Credits→Regolith-Kaufangebote, sodass eine Cantina-lastige Kolonie ihre Credits-Überschüsse bei Bedarf in Regolith umwandeln kann. Das ist kein struktureller Ersatz für A/B (Angebot ist unzuverlässig, an Losgröße und Kaufkraft gebunden) und ändert nichts an der Designabsicht — Pfad C bleibt ohne eigenen, planbaren Regolith-Wachstumshebel —, macht aber die Formulierung „trägt keinen Regolith-Hebel" zu absolut.

**Mechanik: Organika-Verkauf als Angebotstyp des Reisenden Händlers.** Neben Kauf (Credits→Ressource) und Tausch (Ressource↔Ressource) gibt es den **Verkauf** (Organika→Credits), eng gefasst nur für Organika — Regolith- und Werkstoff-Verkauf bleiben außen vor, damit kein Umweg zur Regolith-Beschaffung entsteht.

- **Preis:** deutlich unter dem Kaufpreis der Gegenrichtung, damit Arbitrage unattraktiv bleibt (`config/game.php`).
- **Reserve-Untergrenze:** Verkaufslose nur, solange der Bestand über einer Mindestreserve liegt (Vielfaches des Sol-Bedarfs `food_need`) — schützt die Hunger-Spirale (§3/§4a) vor Leerverkauf.
- **Zugang:** Corvan (Kanal 2, §12) bringt die Verkaufslose bei jedem Besuch mit. Bar-gated (Cantina Lv1+), aber nicht Konsul-exklusiv — der Konsul verbessert die Konditionen der Kaufangebote und kündigt Corvans Besuche vorab an (Marktbericht, §12); weder Besuchsrhythmus noch Verkaufserlöse skaliert er. Das ist die Pfad-C-Prämie: wer früh in den Konsul investiert, plant besser und kauft günstiger, während alle Pfade Zugang haben, ohne dass der Konsul zur passiven Einnahmequelle wird.

**Zielgröße:** offen. Eine belastbare Credits-Zielgröße je Konsul-Rang erfordert eine Credits-Bilanz über den Run (`docs/gdd-balance-checklist.md` A.4). Bis dahin ist die Losanzahl pro Besuch ein Playtest-Kandidat; der Hebel, falls mehr gebraucht wird, sind mehr Lose pro Besuch, nicht ein kürzeres Intervall.

**Offen:** Pfad A hat keine eigene Credits-Quelle (Tabelle oben); Owner-Entscheidung: Sciencelab- und Hangar-Pfad sollen ein eigenes Credits-Einkommen bekommen, unabhängig von der Cantina — Mechanismus noch nicht spezifiziert (ROADMAP „Offene Pfad-Paritäts-Fragen").

### Die Leitstelle ist kein vierter Pfad

Die Leitstelle (CC Lv2) ist ein **optionaler Resilienz-Baustein** ohne Berater-Kopplung und ohne Pfadwahl-Gate (Stratege zurückgestellt, §13). Sie steht außerhalb dieser Systematik.

---
## 4c. Instanzen oder Level — die Wachstumsachse je Gebäude

Ein Gebäude kann auf zwei Arten wachsen, und die Wahl ist eine Designentscheidung, keine technische.

### Die beiden Achsen

| | **Instanz** — mehr davon | **Level** — besser davon |
|---|---|---|
| kostet | ein weiteres Tile | kein Tile |
| Kolonisten | voller Arbeitsplatzbedarf (`supply_cost`) je Instanz | `supply_cost × Level` |
| Instandhaltung | eigene `decay_rate`-Zeile je Instanz | eine Zeile, unabhängig vom Level |
| Sichtbarkeit | **die Kolonie wächst sichtbar** auf dem Hex-Grid | eine Zahl steigt |
| Entscheidung | *wohin* — Platzierung, Nachbarschaft, Tile-Typ | *wie weit* — nur die Höhe |
| Kostenverlauf | linear | steigend (`f(L)`, §13.6) |

Instanzen bedienen damit die **Breiten-Achse** (Bauplatz + Instandhaltung), Level die **Tiefen-Achse** (Wohnraum) — siehe §6 „Die drei Begrenzungsachsen".

> **Grundsatz: Im Zweifel Instanz.** Instanzen sind auf dem Hex-Grid sichtbar, erzeugen eine Platzierungsentscheidung und binden das Wachstum an die 15 Koloniefelder — also an das „kleine Kolonie"-Prinzip aus §1. Level sind unsichtbar und erzeugen keine räumliche Entscheidung. Ein Level-Up muss sich rechtfertigen; eine Instanz nicht.

### Der Test

**Ergibt „zwei davon" in Fiktion und Mechanik einen Sinn?**

- **Ja** → Instanz. Die Kolonie hat mehrere Wohnhabitate, mehrere Kuppeln, mehrere Hallen.
- **Nein, weil das Gebäude die Kolonie als Ganzes repräsentiert** → Level. Es gibt eine Kommandozentrale, eine Funkanlage, einen Handelsposten.

Ein Level-Up ist zusätzlich gerechtfertigt, wenn die Stufe **etwas Bestimmtes freischaltet** statt nur eine Zahl zu erhöhen — beim Analytik-Labor sind die Level die Kenntnis-Stufen, beim Hangar die Schiffsklassen.

### Zuordnung

| Gebäude | Achse | Deckel | Begründung |
|---|---|---|---|
| **Kommandozentrale** | Level | Lv5 | Eine pro Kolonie, per Definition. Die Level tragen die Progressionsgates des gesamten Spiels. |
| **Harvester** | — | **1 Instanz, Lv1** | Ein beweglicher Abbaurig. Wachstum kommt nicht aus mehr Rigs, sondern aus Kenntnissen, Funden und Missionen — siehe unten. |
| **Wohnhabitat** | Instanz (+ Level 1–3 je Instanz) | 6 | Mehrere Habitate; das Level je Instanz trägt den Wohnraum-Beitrag (§6). |
| **Agrardom** | Level | Lv3 | Bleibt Level-Gebäude (Owner-Entscheidung F1). Er hat mit Level und `agronomy`-Kenntnis bereits zwei Wachstumshebel — eine dritte Achse (Instanz) wäre Redundanz ohne neue Entscheidung, und eine zusätzliche Instanz würde ein knappes Koloniefeld binden, das mit einzigartigen Gebäuden konkurriert. |
| **Hangar** | Level (Klasse und Bucht) | Lv3 | Eine einzige Halle; die Stufe ist zugleich Schiffsklasse und Buchtenzahl — siehe unten. |
| **Analytik-Labor** | Level | Lv5 | Lv1-3 **sind** die Kenntnis-Stufen (`cartography` Lv1, `geology`/`trade` Lv2, `defense` Lv3) — ohne sie bricht die Staffelung weg. Lv4/5 haben zusätzlich einen eigenen Effekt (Kenntnis-Kosten-Rabatt, §13.3), keine reinen Gate-Stufen mehr. |
| **Uplink-Station** | Level | Lv3 | §4 nennt sie „das einzige Kommunikationsgebäude der Kolonie". Eine zweite Funkanlage verdoppelt keine Reichweite. |
| **Leitstelle** | Level | Lv3 | Eine pro Kolonie. |
| **Handelsposten** | Level | Lv3 | Eine pro Kolonie. |
| **Cantina** | Level | Lv3 | Zwei Kneipen in einer Kleinkolonie wirken falsch; eine bessere Kneipe nicht. |
| **Krankenstation** | Level | Lv3 | Besser ausgestattet, nicht doppelt vorhanden. |
| **Religiöse Stätte** | — | **1 Instanz, Lv1** | Weder Instanzen noch Level. Sie ist ein Bekenntnis, kein Ausbauprojekt. |
| **Kolonialdenkmal** | — | **1 Instanz, Lv1** | Dito. Ein Denkmal, fertig oder nicht. |

### Harvester: ein Rig, dafür beweglich

**Es gibt genau einen Harvester pro Kolonie.** Er ist der Sockel der Regolith-Versorgung, nicht die Skalierung: Zusätzliches Regolith kommt über Kenntnisse (`geology`), Funde, Missionen und Handel. Die Zielgröße ist der Zyklusdurchschnitt einer Instanz (§13.7).

Der Harvester ist das einzige **bewegliche** Gebäude des Spiels (§4 „Harvester-Transit"), und diese Eigenschaft wird im Spielverlauf tatsächlich gebraucht: **Ein Harvester wird pro Run mehrfach umgesetzt.** Dafür sorgt die Erschöpfung der Vorkommen.

**Erschöpfung der Vorkommen.** Ein Regolith-Tile trägt einen Harvester eine begrenzte Zeit, dann sinkt der Ertrag:

- `colony_tiles.resource_max` / `resource_amount` — Startvorkommen und Restvorkommen je Tile
- zwei unabhängige Achsen — Ertrag (Frischwert je Sol) und Mächtigkeit (Gesamtvorkommen), je drei Stufen kombiniert (`config/game.php → harvester`, siehe „Ressource-Tiles" oben) — ein Tile kann also hohen Ertrag bei geringer Mächtigkeit haben oder umgekehrt, nie beides maximal zugleich
- die Verlege-Vorschau mit Ertragsvergleich und die distanzabhängigen Verlegekosten (`config/game.php`)

Damit entsteht die gewollte Schleife: fördern → Ertrag sinkt → Umzug lohnt → ein Sol Produktion und einige AP kosten → neues Tile. **Erkundung bekommt einen konkreten wirtschaftlichen Zweck**, weil man wissen muss, wo das nächste ergiebige Tile liegt, *bevor* der Umzug erzwungen ist.

#### Erschöpfungskurve und Umzugstakt

```
Ertrag = Frischwert, solange Restvorkommen > 0, sonst 0
```

Ein Tile fördert **konstant zum Frischwert**, bis das Vorkommen aufgebraucht ist — kein Abschwächen zur Mitte hin. Bei erschöpftem Vorkommen: harter Cutoff auf Produktion 0, der Umzug ist erzwungen. Ein Tile mit hohem Ertrag, aber geringer Mächtigkeit erreicht den Boden eher als eines mit niedrigerem Ertrag, aber großer Mächtigkeit — derselbe Mechanismus, keine Sonderregel.

**Konstante Förderrate:** Der Ertrag fällt nicht mit sinkendem Restvorkommen ab; eine konstante Rate ist für den Spieler berechenbar. Als Vorwarnung zeigt das Tile-Panel einen serverseitig berechneten „≈N Sole bis Erschöpfung"-Countdown (serverseitig, weil Geologie-Bonus und Vertrauens-Multiplikator nur dort zuverlässig bekannt sind). Herleitung: `docs/superpowers/specs/2026-08-10-harvester-constant-yield-design.md`.

**Der eigentliche Regler ist die Umzugsgebühr, nicht die Kurve.** Verlegekosten sind pro Hex gesetzt (`config/game.php`) und machen einen Umzug samt Zielkundschaftung zu einer spürbaren Investition. Spielziel: mehrfache Umzüge pro Run, aber nicht als Routine — die Verlegung ist eine wiederkehrende Entscheidung, kein automatisches Refresh.

**Sichtbarkeit für den Spieler.** Die Schleife funktioniert nur, wenn der Spieler *vor* dem Erschöpfen ein Ziel kennt. Dafür startet die Kolonie mit zwei vorerkundeten Ausweich-Vorkommen (Ring 3, „Nexus-Scout"-Fund) — ein garantiertes Gegensatzpaar aus hohem Ertrag/geringer Mächtigkeit und niedrigem Ertrag/großer Mächtigkeit, damit die erste Verlege-Entscheidung ein echter Tradeoff ist, nicht nur ein einzelnes bekanntes Ziel. Ein Onboarding-Hint warnt zusätzlich, sobald das Restvorkommen des aktiven Tiles unter die Schwelle `game.harvester.low_regolith_warning_pct` fällt, und erkundete, nicht erschöpfte Ausweich-Tiles sind auf der Hex-Karte markiert. Gegen Ertragssorgen („zu wenig Regolith bei Sol N") wird nicht an `fresh_yield`/`resource_max` gedreht, solange nicht mehrere Messpunkte über verschiedene Tile-Typen und Pfadwahlen vorliegen — das Umzugs-Timing ist der Hebel.

> **Hinweis zur Geologie-Kenntnis:** `geology` (Harvester-Bonus, §13.7) ist nicht CC-gegatet — Gate ist Analytik-Labor Lv2 + Harvester Lv1, beides ab CC Lv2 erreichbar. Kolonien, die zuerst Hangar oder Cantina bauen, laufen entsprechend länger ohne Kenntnis-Boost auf dem Harvester; das ist Teil der Pfadwahl, kein Fehler.

**Playtest-Monitoring:** Umzugsfrequenz pro Run (mehrere, aber keine Routine) und Anteil der Sole mit Ertragserschöpfung (Ziel: niedrig).

### Hangar: eine Halle, Stufe = Klasse und Bucht

Der Hangar ist ein einzigartiges Gebäude (genau eine Halle, keine weiteren Instanzen). Seine Stufe trägt zwei Bedeutungen zugleich:

| Wirkung der Stufe | bedeutet | Deckel |
|---|---|---|
| **Schiffsklasse** | Lv1 Drohne, Lv2 Frachter, Lv3 Korvette (dazu `defense` ab Lv2) | Lv3 |
| **Buchten** | Stufe n öffnet n Buchten; ein Schiff steht in genau einer Bucht | Lv3 |

Dazu kommt das **Vorfeld**: Es existiert ab Stufe 1, gehört nur der Geschenk-Drohne und zählt nicht als Bucht. Ein Schiff ist aktiv, solange die Halle mindestens seine Klassenstufe und seine Buchtnummer erreicht; sinkt sie darunter, wird es inaktiv (§7). Eine Halle fasst so ein Schiff pro Stufe, eine größere Halle auch größere Schiffe — ohne zweite Gebäudeachse, die das knappe Koloniefeld belegen würde. Zahlen und Kosten: `docs/game-reference.md#7b-hangar-b1-t30-schritt-3`.

### Datenmodell: `max_level` und `max_instances`

`buildings` führt beide Deckel als getrennte, nullable Felder (`NULL` = unbegrenzt): `max_level` ist der Level-Deckel je Instanz, `max_instances` der Instanz-Deckel instanzierter Gebäude. Gepflegt in `config/buildings.php`, in die DB gesynct via `game:sync-config`; `ColonyController::placeBuilding()` prüft `max_instances`, die Techtree-Gates `max_level`. Decay wird je Instanz verarbeitet (`processBuildingDecay()` filtert nach `instance_id`).

---

## 5. Ressourcenproduktion

### Mechanik

Einmal pro Sol produzieren die beiden Produktionsgebäude Rohstoffe: der **Harvester** Regolith, der **Agrardom** Organika. Auf beide wirken der Vertrauens-Multiplikator (§14) und der Besetzungsanteil. Der Besetzungsanteil ist 1, solange alle Arbeitsplätze besetzt sind; nach einem Wohnraumverlust sinkt er anteilig zu den unbesetzten Arbeitsplätzen (§6, „Wohnraumverlust — Konsequenzen").

**Harvester:** Der Ertrag hängt vom Tile ab, nicht vom Level (§4c): konstanter Frischwert, solange das Vorkommen reicht, dazu der Bonus der Kenntnis `geology` (§10). Der Harvester hat kein Level-Up (`max_level = 1`).

**Agrardom:** Die produzierte Menge ist die **kumulierte Glockenkurve** bis zum aktuellen Level (nicht Level × Flat-Rate), dazu der Bonus der Kenntnis `agronomy`:

```
produzierte Menge = Σ curve[1..aktuelles Level] × vertrauens_multiplikator × besetzungsanteil
```

### Produktionsgebäude

| Gebäude | building_id | Ressource | resource_id | max_level |
|---------|-------------|-----------|-------------|-----------|
| Harvester | 27 | Regolith | 3 | 1 (tile-basiert, §4c) |
| Agrardom | 41 | Organika | 5 | 3 |

> **Warum beim Agrardom eine Glockenkurve mit Deckel statt einer linearen Rate:** Ein linearer oder exponentieller Anstieg widerspricht der Frontier-Logik (jedes Level soll spürbar, aber nicht grenzenlos lohnend sein), und ein unbegrenzter Ausbau mit abflachendem Ertrag wäre bei flachen Levelup-Kosten nie eine echte Entscheidung — der Grenzertrag sänke monoton, ohne dass je ein Stopp erzwungen wird. Ein harter Deckel erzeugt echten Bedarf („wohin als Nächstes investieren?"). Der Agrardom peakt früh (Lv2–3): Organika/Nahrungssicherheit muss schnell stehen, bevor die Hunger→Trust-Spirale greift; die Kurve bleibt danach bewusst flacher, damit die Hunger-Mechanik (einzige „weiche" Verlustspirale des Spiels) nicht entwertet wird. Kein Level liefert 0 Zusatzertrag — Ausbau bleibt bis zur Maximalstufe lohnend. Wachstum darüber hinaus kommt über Kenntnisse, Missionen und Handel (Amplifikator-Prinzip, §18).

> **UI-Anforderung:** Der Grenzertrag des nächsten Agrardom-Levels muss vor dem Levelup sichtbar sein (analog AP-Cost-Chip-Convention) — Spieler soll entscheiden können, ob sich ein Ausbau noch lohnt, bevor er investiert. Ort dafür ist das Tile-Panel der Kolonieansicht, denn dort wird ausgebaut (§11.5). **TODO Implementierung:** wird aktuell nicht angezeigt.

> **Designentscheidung:** Der Harvester produziert Regolith (lokaler Rohstoff), nicht Werkstoffe. Werkstoffe sind veredelte Industriegüter, die nicht vor Ort herstellbar sind — sie kommen ausschließlich über Import, Handel und Missionen (§3).

### Konfiguration

- `config/game.php → production_curve[41]` — Agrardom-Kurve (der Eintrag für den Harvester, 27, ist inert und wird nicht gelesen).
- `config/game.php → harvester` — Frischwert je Tile-Typ, Vorkommen, Verlegekosten; `geology_harvester_bonus_per_level`, `agronomy_agrardom_bonus_per_level` — Kenntnis-Boni.
- Kumulierte Gesamtwerte je Level: `docs/game-reference.md#ressourcenproduktion`.

Ein neues Produktionsgebäude braucht einen Eintrag in `production_curve` und ein `max_level` in `config/buildings.php`, sonst läuft die Kurve unbegrenzt am letzten definierten Wert weiter (Deckel via `GameTick::cumulativeCurveYield()`).

---

## 6. Kolonisten & Wohnraum (Cap-Modell)

### Modell

Die Kolonisten der Siedlung sind **kein fließender Pool**, sondern eine **Kapazität** (Cap-Modell; Code-Schlüssel `supply`). Sie ergibt sich ausschließlich aus Wohnraum: Kommandozentrale und Wohnhabitate. Kenntnisse schaffen **keinen** Wohnraum — Kolonisten brauchen Betten, und Wissen schafft keine. Gebäude (außer CC und Wohnhabitat) binden Kolonisten dauerhaft als Arbeitsplätze. Berater binden **keine** Kolonisten — sie kosten Credits. **Schiffe binden keine Kolonisten** — ihre Zahl wird durch Hangar-Buchten begrenzt (siehe unten). Es gibt keine Zuwachsrate pro Sol.

```
wohnraum         = min(CC-Grundbeitrag + Σ(Wohnhabitat-Stufen) × Wert-je-Stufe, Hard-Cap)      (= Kolonisten; Code: supply_cap)
laufende_last    = Σ(Gebäude-Level × Arbeitsplatzbedarf) + Σ(Arbeitsplatzbedarf platzierter Gebäude auf Stufe 0)   (Code: supply_cost)
freie_kolonisten = wohnraum − laufende_last
```

`laufende_last` ist die Zahl der **Arbeitsplätze** der Kolonie; jeder Arbeitsplatz bindet einen Kolonisten. Der zweite Summand ist die **Reserve**: Ein platziertes, noch nicht fertiggestelltes Gebäude belegt bereits die Arbeitsplätze seiner ersten Ausbaustufe (siehe „Supply als Bau-Gate" unten).

Exakte Faktoren und ein Rechenbeispiel: `docs/game-reference.md` Abschnitt 10 (Wohnraum-Formel) / `config/game.php → supply`.

### Die drei Begrenzungsachsen

Die Koloniegröße wird von drei unabhängigen Achsen begrenzt. Jede hat eine eigene Währung, in der bezahlt wird — das ist der Catan-Zuschnitt aus §1.2: kein Optimalpfad, jede Strategie hat ihren eigenen Preis.

| Achse | begrenzt | wird bezahlt mit |
|---|---|---|
| **Breite** — Anzahl Gebäudetypen | Bauplatz (begrenzte Tile-Zahl) | Instandhaltung: Σ `decay_rate` in AP und Regolith pro Schadenpunkt, jeden Sol |
| **Tiefe** — Summe der Gebäudelevel | **Wohnraum (Kolonisten)** | — (reines Cap, kein laufender Abfluss) |
| **Tempo** | AP-Rate (§13.2) | die 100-Sol-Uhr (§18.4) |

Daraus folgt die Strategie-Abwägung:

| Strategie | wird billig | wird teuer |
|---|---|---|
| **Breit** (viele Gebäude auf niedrigem Level) | AP (Errichten kostet nur die halben Levelup-Kosten, §13.3), Kolonisten (nur 1 Level je Gebäude) | Bauplatz, Instandhaltung (Decay zählt pro Gebäudetyp, level-unabhängig) |
| **Tief** (wenige Gebäude hoch) | Bauplatz, Instandhaltung | AP (Kostenkurve wächst mit dem Level), Kolonisten (Level × Arbeitsplatzbedarf) |

> **Warum die Spreizung des Arbeitsplatzbedarfs (`supply_cost`) trägt:** Die Nennwerte sind bewusst gespreizt, aber weil sie mit dem Level multipliziert werden, ist die effektive Spreizung weit größer. Produktionsgebäude brauchen wenige Arbeitsplätze, Dienstleistungsgebäude viele. Ein hoch ausgebautes Analytik-Labor kann so viele Kolonisten binden wie mehrere niedriger ausgebaute Produktionsgebäude zusammen. Das ist die eigentliche Kompositionsentscheidung des Supply-Systems — Exakte Werte: `config/buildings.php` (`supply_cost`) / `docs/game-reference.md`.

> **Warum es Wohnraum als Cap gibt:** Der Wohnraum trägt neben Bauplatz, AP-Rate und Verfall eine eigene Rolle: Er ist die einzige Begrenzung der **Tiefe**. Zusätzlich hängen mehrere Mechaniken daran — die Verpflegung (Bedarf hängt an den Kolonisten, §4a; an ihnen hängt die Hunger→Vertrauen-Spirale), das **Wohnhabitat** (sonst ohne Funktion — ohne Wohnraum ein leeres Gebäude) und der CC-Ausbau. Kenntnisse speisen den Wohnraum nicht (siehe „Wohnraum-Quellen").

> **Offene Prüfung:** Ob der Wohnraum neben dem Stufen-Gate (Gebäude-Stufe ≤ CC-Stufe) noch eine eigene Rolle trägt, wird nach einer Neumessung geprüft. Bis dahin gilt er unverändert.

> **Schiffe binden keine Kolonisten.** Schiffe sind räumlich getrennt von der Kolonie, thematisch eigenversorgt und durch Hangar-Buchten begrenzt. Der Schiffsausbau wird gebremst durch Credits (Nexus-Kosten), Lieferzeit und die AP-Kosten der Missionen.

> **Darstellung:** Im Spiel heißt diese Größe durchgehend **Kolonisten** (Kürzel KOL) — der Code-Schlüssel `supply` ist nur ein interner Name. Die Anzeige lautet „N Kolonisten, davon M beschäftigt"; das Popup nennt zusätzlich die freien Kolonisten. Der Chip in der Ressourcenleiste färbt sich, sobald die Arbeitsplätze die Kolonisten übersteigen. Kostenanzeige am Gebäude, Hinweise und Nexus-DB sprechen ebenfalls von Kolonisten.
>
> **Warum Kolonisten:** Bei einer abstrakten Zahl versteht kein Spieler, warum ein Labor auf Lv3 dreimal so teuer ist wie auf Lv1. Mit Kolonisten ist es selbsterklärend: *ein größeres Labor braucht mehr Leute.*

Ein Gebäude kann nur platziert oder um eine weitere Ausbaustufe erweitert werden, wenn die freien Kolonisten den Arbeitsplatzbedarf decken (Details und Ausnahmen: „Kolonisten als Bau-Gate" unten).

### Wohnraum-Quellen

| Quelle | Wohnraum-Beitrag |
|--------|-------------------|
| CommandCenter | fester Grundbeitrag, sobald die Kommandozentrale steht — unabhängig von ihrer Ausbaustufe |
| Wohnhabitat | wächst mit jeder Ausbaustufe jeder Instanz (Stufe × Wert, summiert), begrenzte Instanzzahl (ergibt Tile-Limit) |

**Kolonisten-Modell.** Wohnraum und Kolonisten sind **dieselbe Größe**: Jedes Wohnhabitat — Neubau wie Ausbau — macht beim Abschluss sofort weitere Kolonisten verfügbar, die Kommandozentrale stellt einen festen Grundstock. Es gibt keinen Zuzug, kein Wachstum und keinen zufälligen Schwund; die Kolonisten ergeben sich vorhersagbar aus den Wohnhabitaten. Davon sind **beschäftigt** die Kolonisten, die Arbeitsplätze der Gebäude besetzen (Arbeitsplätze: Summe aus Stufe und Platzbedarf, einschließlich der Reserve platzierter Gebäude); der Rest sind **freie Kolonisten**, verfügbar für neue Gebäude und Ausbauten. Das Bau-Gate verlangt genug freie Kolonisten für die Arbeitsplätze eines Gebäudes. Reichen die Kolonisten für bestehende Arbeitsplätze nicht (Ausnahmen vom Bau-Gate, Verlust von Wohnraum), bleiben Arbeitsplätze unbesetzt und die Produktion sinkt anteilig. Anzeige: „N Kolonisten, davon M beschäftigt“ (Kopfleiste, Sol-Bericht, Kommandozentrale); „Wohnraum“ ist dabei nur der Name der Gebäudewirkung, keine zweite Zahl.

**Kenntnisse sind keine Cap-Quelle**. Wohnhabitate sind damit die einzige Wachstumsquelle des Caps; Wohnbau ist eine echte AP- und Regolith-Ausgabe, die der Spieler neben Produktion und Pfadgebäuden einplanen muss. Das passt zu „AP bremst".

Der Beitrag der Kommandozentrale ist flach: Er hängt nur davon ab, dass sie steht, nicht von ihrer Ausbaustufe. Ein Stufenverlust der Kommandozentrale senkt den Wohnraum daher nicht; Wohnraumverlust entsteht nur über Wohnhabitate.

**Startsituation:** CC Lv1 liefert einen Basis-Cap, ohne Wohnhabitate. Erster Tutorial-Schritt: Wohnhabitat bauen → Cap erhöht sich. Genaue Werte: `config/game.php` / `docs/game-reference.md` Abschnitt 10.
**Hard-Cap:** ein absolutes Kolonisten-Maximum, siehe `config/game.php → supply.cap_max`.

> **Tile-Budget:** Ein fester Anteil der Kolonie-Tiles ist für Nicht-CC-Gebäude reserviert, der Rest für Wohnhabitate — bei voller Belegung ist kein weiteres Wohnhabitat mehr möglich, ohne ein anderes Gebäude zu opfern. Exakte Tile-Zahlen: `config/game.php → colony_zone_expansion`. Bewusste Designentscheidung für Knappheit.

> **Designabsicht:** CC (flach) und Wohnhabitate sind die einzigen Cap-Quellen. Wer tiefer ausbauen will, muss zuerst Wohnraum investieren. Ob der Hard-Cap ohne Kenntnis-Anteil überhaupt noch erreichbar ist, klärt die Neumessung (Annahme: eher nein, er wirkt dann nur noch als Sicherheitsgrenze).

### Schiffe und Kolonisten

**Schiffe binden keine Kolonisten.** Die Schiffsanzahl wird durch folgende Limiter gebremst:

| Limiter | Mechanik |
|---------|---------|
| Hangar-Buchten | Eine Halle; max. Schiffe = Buchten (= Stufe) plus das Vorfeld für die Geschenk-Drohne |
| Credits | Nexus-Anfragen für Schiffe kosten Credits pro Schiff (Kosten und Typ in `config/ships.php`) |
| Lieferzeit | Schiffe werden nicht sofort geliefert — die längsten Typen brauchen mehrere Sole Lieferzeit |
| Navigation-AP | Außenmissions-Dispatch kostet AP je Sol Distanz (§8b) — mehr parallele Missionen = mehr AP-Verbrauch |

> **TODO Balance (Playtest):** Prüfen ob Korvetten-Stacking ohne Kolonisten-Limiter auftritt. Falls ja: Credits/Lieferzeit-Werte verschärfen, nicht Arbeitsplatzbedarf für Schiffe einführen.

**Schiffe haben keinen passiven Decay.** Wartungsdruck entsteht durch aktiven Einsatz (Schiffs-Verschleiß — siehe §7). `colony_ships.status_points` sinkt durch Außenmissionen, nicht durch Zeitablauf.

> **TODO (Design, Phase 4+):** Sonderfall "Schiffe ohne Hangar" — durch Events, Handelsdeals oder andere Mechaniken könnte der Spieler Schiffe erwerben, die normalerweise nicht im Hangar baubar sind (z.B. erbeutete Fraktionsschiffe, Belohnungsschiffe aus Events). Diese wären per Run einzigartig und ein Roguelike-Element das jeden Durchlauf anders macht. Mechanik (Hangar-Pflicht? Supply-Kosten?) und Balance noch offen — für spätere Phase detailliert ausarbeiten.

### Arbeitsplatzbedarf der Gebäude

**Berater:** binden keine Kolonisten — Kosten laufen über Credits (siehe §13).

**CommandCenter und Wohnhabitat:** kein Arbeitsplatzbedarf (sie definieren den Wohnraum).

**Gebäude** (individueller Arbeitsplatzbedarf):

Jedes Gebäude hat einen individuellen Arbeitsplatzbedarf (`supply_cost`; gering für Produktionsgebäude, höher für Infrastruktur und High-Tech). Exakte Werte: `config/buildings.php`.

> **Pfadwahl-Kostenbalancing:** Die drei Pfad-Gebäude (Analytik-Labor / Hangar / Cantina) sind bewusst unterschiedlich kalibriert um echte Abwägungen zu erzeugen. Jedes hat eine Schwachachse (Ressource oder Kolonisten) und eine Stärkeachse (wo es effizient ist). Die Kosten drücken diese Unterschiede aus — wer Supply-begrenzt ist, hat den einfacheren Weg über einen Pfad; wer Regolith-begrenzt ist, einen anderen. Das Ziel: Kein Pfad ist dominant, alle sind gleich tragfähig bei unterschiedlichen Startbedingungen.
> 
> Aktuelle Kosten: `config/buildings.php` (für die drei Pfad-Gebäude).

> Der Arbeitsplatzbedarf ist **sol-rate-unabhängig** — sie beschreiben eine permanente Kapazitäts-Belegung, keine Fluss-Größe.

> **Kolonisten als Bau-Gate („genug freie Kolonisten“):** Im Spiel heißt die Sperre „zu wenige freie Kolonisten“; frei sind Kolonisten (Wohnraum) abzüglich der Arbeitsplätze. Ein Gebäude kann nur **platziert** oder um eine **weitere Ausbaustufe** erweitert werden, wenn die freien Kolonisten (`Wohnraum − belegt`) seinen Arbeitsplatzbedarf decken. Es wird **nichts abgezogen** — Kolonisten sind ein Cap, kein Lager. Das ist die Kolonisten-Achse aus der Verwendungsmatrix (§3): Gebäude kosten Regolith (Abzug) **und** Kolonisten (Belegung + Gate).
>
> **Reserve beim Platzieren:** Die Prüfung für die erste Ausbaustufe findet beim **Platzieren** statt, nicht bei der Fertigstellung. Ab dem Platzieren belegt das Gebäude auf Stufe 0 bereits die Arbeitsplätze seiner ersten Stufe. Die Fertigstellung (Stufe 0 → 1) wird deshalb nie gegen Supply geprüft — was beim Platzieren gepasst hat, kann nicht nachträglich an Supply scheitern; jede weitere Stufe (n → n+1) wird normal geprüft. Die Kolonisten für diese Reserve gelten als **anwesend**: Sie sind schon eingezogen, um den Bau zu übernehmen, brauchen also Wohnraum und essen mit (§4a). Für den Spieler heißt das: Platzieren ist die eigentliche Personalentscheidung, nicht erst der letzte Bauklick. Ein Gebäude, das nur auf dem Plan steht (ohne Platz auf der Karte), reserviert nichts.
>
> **Ausnahmen:** Kommandozentrale und Wohnhabitat binden keine Kolonisten und sind nie gesperrt — sie schaffen den Wohnraum, auch nach einem Wohnraumverlust. Der **erste Harvester** ist als Starthilfe ohne Prüfung platzierbar, damit die Regolith-Quelle immer erreichbar bleibt; er reserviert seine Arbeitsplätze trotzdem. Weil das Gate damit jeden anderen Zuwachs der Last abdeckt, können Arbeitsplätze — vom Sonderfall des ungeprüften ersten Harvesters abgesehen — nur noch durch den Verlust von Wohnraum unbesetzt werden (siehe „Wohnraumverlust — Konsequenzen" unten).

### Kenntnisse und Wohnraum

Kenntnisse **binden keine Kolonisten** und **erhöhen den Wohnraum nicht** (siehe „Wohnraum-Quellen"). *Abweichung im Code:* `ResourcesService::calculateSupplyCap()` addiert noch einen Kenntnis-Anteil (`game.supply.knowledge_cap_per_level`); er ist zu entfernen (ROADMAP T43-H4). Sie haben **keinen Decay** — einmal erforschtes Wissen bleibt permanent. Ihre Wirkung liegt in den übrigen Kenntnis-Effekten (§10).

> ⚠️ BALANCE CONCERN: Ohne Cap-Bonus sind Kenntnisse für sich schwächer attraktiv, besonders solche ohne eigenen mechanischen Effekt. Gestaltungsrichtung: (1) Kenntnisse senken NICHT die Arbeitsplatz-Last von Gebäudetypen (zu stark); (2) Kenntnisse verstärken ihren Fachbereich stärker (z. B. mehr Ertrag, bessere Missions-/Ereignis-Ausgänge) — **ok**; (3) Kenntnisse schalten Gebäude-Ausbaustufen oder Optionen frei (Gate statt Bonus) — **ok**. Konkrete Ausgestaltung nach der Neumessung.

### Entropie-Übersicht

Die drei Entropie-Vektoren wirken unterschiedlich (Details in §7):

| Entität | Mechanismus | Auslöser | Gegenmaßnahme |
|---------|-------------|----------|---------------|
| Gebäude | Passiver Decay (`decay_rate` SP/Sol) | Zeitablauf | Repair-AP investieren |
| Schiffe | Verschleiß (`wear_per_sol` aus config/ships.php) | Aktiver Einsatz (Außenmissionen) | Reparatur (nur AP, pro Klick) |
| Kenntnisse | **kein Decay** — permanentes Wissen | — | — |

### Konfiguration

`config/game.php → supply`:

```php
'supply' => [
    // cap_commandcenter, cap_housingcomplex, cap_max: siehe docs/game-reference.md
    // Abschnitt 10 (Wohnraum-Formel)
    // Design: Kenntnisse speisen den Wohnraum nicht (Code-Rest: knowledge_cap_per_level, T43-H4)
    // Berater binden keine Kolonisten — Upkeep läuft über Credits (config/game.php → advisor)
    // Schiffe binden keine Kolonisten: siehe „Schiffe und Kolonisten" oben
],
```

### Kolonisten im Sol (GameTick Schritt 8 / §2 Phase 3)

Der aktuelle Wohnraum wird in `GameTick.php`-Schritt 8 (entspricht der groben Phase 3 „Ressourcen & Wohnraum" in §2) jedes Sols neu berechnet und gespeichert (intern `supply`) — nach allen Quellen eines Stufenverlusts (Verfall, Begegnungen), damit der Wert immer den aktuellen Gebäudestand widerspiegelt. Zusätzlich gilt Wohnraum **sofort** bei Abschluss eines Wohnhabitats (Neubau wie Ausbau), nicht erst beim Sol-Wechsel.

Die freien Kolonisten (für die Bau-Prüfung) ergeben sich live: `Wohnraum − Arbeitsplätze` (Stufen × `supply_cost` plus Reserve der platzierten Stufe-0-Gebäude, `ResourcesService::buildingWorkplaces()`).

### Abgrenzung der Unterhalts-Mechanismen

| Mechanismus | Was er begrenzt | Zeithorizont | Gegenmaßnahme |
|-------------|----------------|--------------|---------------|
| Wohnraum | **Summe der Gebäudelevel** (Ausbautiefe) | permanent | CC ausbauen, Wohnhabitate bauen |
| Bauplatz | Anzahl Gebäude (begrenzte Tile-Zahl) | permanent | — (harte Grenze) |
| AP | Arbeitsleistung pro Sol | täglich | mehr/bessere Berater, Kostenboni (§13.3) |
| Gebäude-Decay | Stand von Gebäuden; skaliert mit der **Anzahl Gebäudetypen**, nicht mit deren Level | täglich | Reparatur (AP + Regolith pro SP, siehe §4) |
| Schiffs-Verschleiß | Zustand aktiv genutzter Schiffe | pro Sol auf Außenmission | Reparatur (1 AP/Klick) |

Die Mechanismen sind bewusst unabhängig voneinander — mit einer Ausnahme, die **keine** ist: Decay und Bauplatz greifen beide an der Breite an (siehe „Die drei Begrenzungsachsen" oben). Das ist gewollt: Breite kostet einmalig Bauplatz und dauerhaft Instandhaltung, Tiefe kostet einmalig AP und dauerhaft nichts, dafür permanent Wohnraum.

### Wohnraumverlust — Konsequenzen (A14)

Kolonisten und Wohnraum sind dieselbe Größe (§6 „Wohnraum-Quellen“). Geht Wohnraum verloren, sinken die Kolonisten im selben Moment; es gibt keine Frist, keine Abwanderung und keine Aktion zum Wegschicken.

**Wie Wohnraumverlust entsteht.** Weil Platzieren **und** jeder weitere Ausbau freie Kolonisten voraussetzen (siehe „Kolonisten als Bau-Gate" oben), kann der Spieler die Kolonie nicht selbst über ihren Wohnraum hinaus bauen. Arbeitsplätze ohne Kolonisten entstehen nur noch, wenn **Wohnraum verloren geht**: Ein Wohnhabitat verliert durch Verfall (§7), ein Sturm-Ereignis (§9) oder einen Rückbau durch den Direktor (§11.5) eine Ausbaustufe, und die Zahl der Kolonisten sinkt **sofort** um den Wohnraum dieser Stufe. (Die Kommandozentrale zählt im aktuellen Code flach, ihr Stufenverlust senkt den Wohnraum nicht — siehe Balance-Hinweis bei „Supply-Cap-Quellen".) Die Gebäude bleiben stehen; ein Teil ihrer Arbeitsplätze ist jetzt **unbesetzt**. Das ist immer die Folge eines Schadens oder eines bewussten Rückbaus, nie ein Baufehler: Die Frage an den Spieler lautet nicht „Warum hast du zu viel gebaut?", sondern „Wie reagierst du auf den Verlust?". Beim Rückbau nennt der Bestätigungsdialog die Folgen vorher (§11.5).

**Folgen — sofort und ohne Frist.** Gebäude verlieren dabei **keine** Ausbaustufe; es fehlt nur das Personal. Das wirkt auf drei Arten:

- **Rohstoffproduktion sinkt anteilig.** Die Erträge der Produktionsgebäude werden mit dem Besetzungsanteil (beschäftigte Kolonisten / Arbeitsplätze) multipliziert — gleichmäßig über alle Produktionsgebäude, ohne Gebäudewahl. Andere Gebäudewirkungen (Labor, Hangar, Cantina, Krankenstation und so weiter) bleiben unberührt.
- **Neubau und Ausbau sind gesperrt, bis genug freie Kolonisten da sind.** Das Bau-Gate heißt „genug freie Kolonisten“ (freie Kolonisten = Wohnraum minus Arbeitsplätze); solange Arbeitsplätze unbesetzt sind, gibt es keine. Ausgenommen sind die Wohnraum-Gebäude (Kommandozentrale, Wohnhabitat) — sie binden keine Kolonisten und sind der Weg heraus. Reparatur ist nie gesperrt. Der Sperrgrund ist im Baumenü sichtbar (§4).
- **Erholung ist deterministisch.** Wird Wohnraum zurückgeholt (Wohnhabitat wieder ausbauen, ein weiteres errichten), steigen die Kolonisten sofort, die Arbeitsplätze sind wieder besetzt, die Produktion steigt im selben Maß und das Gate öffnet sich. Es gibt keinen Rückkehrpreis und keinen Zufall; der Preis war der Wiederaufbau des Wohnraums.

```
kolonisten      = wohnraum
beschäftigt     = min(kolonisten, laufende_last)
unbesetzt       = max(0, laufende_last − kolonisten)
besetzungsanteil = beschäftigt / laufende_last        (1, solange nichts unbesetzt ist)
```

`laufende_last` enthält hier wie überall die Reserve platzierter Stufe-0-Gebäude (§6, „Kolonisten als Bau-Gate").

**Abgang von Arbeitsplätzen.** Verliert ein Gebäude eine Ausbaustufe (Verfall, Sturm, Rückbau), verschwinden die Arbeitsplätze dieser Stufe mit; unbesetzte Arbeitsplätze werden dadurch automatisch weniger, ohne eigenen Zustand und ohne Sonderbuchung.

**Vertrauen.** Wohnraumverlust löst keinen eigenen Vertrauens-Malus aus (kein Streak, keine Eskalation); der Stufenverlust hat kein eigenes Ereignis (§14). Der Druck kommt über die geminderte Produktion und die gesperrten Bauaktionen. Ob ein einmaliges Ereignis den Verlust markieren soll, wird nach dem Playtest geprüft.

**Warnung vor dem Rückbau.** Der Bestätigungsdialog nennt vorab, wie viele Kolonisten wegfallen, wie viele Arbeitsplätze danach unbesetzt wären, dass die Produktion sinkt und dass Neubau und Ausbau bis zur Erholung gesperrt sind (§11.5). Verfall und Sturm sind über Zustandspunkte und Meldungen vorhersehbar; die Kolonieansicht zeigt unbesetzte Arbeitsplätze mit Ursache.

> ⚠️ BALANCE CONCERN: Ohne Frist fehlt die Abfederung. Fällt ein Wohnhabitat durch Verfall, wirkt der Schaden im selben Sol auf Produktion und Gate; verfallen mehrere Wohnhabitate hintereinander, droht eine Kaskade (Produktion fällt, Reparatur-Regolith wird knapper, weitere Stufen fallen). Im ersten Playtest prüfen, ob das real vorkommt; Hebel wären der Verfall der Wohnhabitate oder ihre Reparaturpriorität, nicht eine neue Frist.

**Warum keine Frist.** Mit „Kolonisten gleich Wohnraum“ gibt es keine Kolonisten über dem Wohnraum. Eine Frist bräuchte einen gespeicherten Zusatzzustand und eine Anzeige „Obdachlose“, die dem Kolonisten-Begriff widerspricht. Die schlichte Regel „angezeigte Zahl gleich wirkende Zahl“ geht vor Abfederung. Das Wort „Arbeiter“ wird im Spiel nicht eingeführt.

**Kein beschleunigter Verfall bei Wohnraumverlust.** Beschleunigter Verfall würde genau die Kaskade befeuern, die den Verlust ausgelöst hat: Wohnraum verloren → alles verfällt schneller → noch mehr Wohnraum verloren. Der Druck kommt stattdessen gezielt über Unterbesetzung (Produktion) und das Gate — beides heilt, sobald der Wohnraum zurück ist.

**Konfiguration.** Die Regel hat keinen eigenen Config-Block und keine eigenen Vertrauens-Ereignisse; sie folgt allein aus Wohnraum und Besetzung. Aktuelle Werte: `docs/game-reference.md` Abschnitt 10a.

Weitere Festlegung: Der Besetzungsanteil wirkt **nur auf die Rohstoffproduktion** der Produktionsgebäude, nicht auf AP, Credits, Vertrauensbeiträge oder Wohnraum-Beiträge.

---

## 7. Verfall & Entropie

Entropie ist ein übergreifendes Designprinzip: Ohne aktive Pflege degradiert die Kolonie schrittweise. Die zwei Entropie-Vektoren sind **Gebäude-Decay** und **Schiffs-Verschleiß** (Berater-Burnout ist zurückgestellt, siehe unten). Kenntnisse verfallen nicht — einmal erarbeitetes Wissen bleibt permanent (kein SP-System auf Kenntnissen).

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

**Konsequenz bei SP ≤ 0 — einheitlich, keine Zerstörung (Owner-Entscheidung F2):** Ein Gebäude oder eine Instanz wird durch Decay **nie gelöscht** und fällt nie unter Level 1 — ein einmal errichtetes Gebäude verschwindet nie vollständig durch Verfall. Erreichen die `status_points` 0, levelt das Exemplar sofort um 1 herunter (Untergrenze Level 1), die `status_points` werden dabei sofort auf `max_status_points` zurückgesetzt, ein Protokoll-Ereignis wird geschrieben. Das gilt gleichermaßen für Level-Gebäude und für Instanzen (Wohnhabitat, Hangar).
*(Kenntnis — kein Decay; Kenntnisse haben kein SP-System, siehe §10)*

> **Effekte skalieren mit dem aktuellen Level, kein separater Effekt-Schalter:** Es gibt keine Hysterese (Effekt fällt bei SP 0 komplett aus und kehrt erst ab einem größeren Reparaturanteil zurück). Weil ein Exemplar bei SP 0 sofort auf die niedrigere, aber wieder volle Stufe herunterklappt (siehe oben), hängt es nie über mehrere Sole in einem funktionslosen Zwischenzustand; sein Effekt (z. B. der Wohnraum-Beitrag des Wohnhabitats) skaliert einfach automatisch mit dem neuen, niedrigeren Level. Die Konsequenz bleibt real: das verlorene Level zurückzuholen kostet normale Level-Up-Kosten (AP + Ressourcen), kein billiger Reparatur-Klick. Grund: Eine Hysterese erzeugte ein eigenes Kaskaden-/Softlock-Risiko erzeugt (kaputtes Wohnhabitat → gesamter Wohnraum-Beitrag sofort weg statt nur einer Stufe → große Zahl unbesetzter Arbeitsplätze mit allen Folgen aus §6), das der bestehende Level-Down-Mechanismus samt Level-1-Untergrenze nicht hat.
>
> **Ausnahme Hangar-Schiffe:** Schiffe bleiben ein bewusster binärer Sonderfall — ein Schiff ist entweder einsatzbereit oder inaktiv (abhängig von der Stufe seines Hangars, siehe „Hangar unter Schiffsstufe" unten), es gibt keinen Teil-Zustand.
>
> ⚠️ BALANCE CONCERN: Ein sinkender Wohnraum-Beitrag durch Level-Down braucht eine Dämpfung gegen eine Verfalls-Kaskade (Vertrauen sinkt → mehr Verfall → noch weniger Vertrauen). Diese Dämpfung ist noch ungeklärt und muss vor einer tieferen Verzahnung beider Systeme geklärt werden.

> **Manuelle Reparatur:** kostet Construction-AP und Regolith pro Schritt. Hartes Gate — ohne Regolith ist der Reparatur-Button gesperrt. CC und Harvester sind regolithfrei reparierbar (AP-only, Bootstrap-Schutz). Vollständige Kosten-Regeln siehe §4 „Baukosten & Level-Up-Kosten" und `config/game.php`.

> **Keine Notreparatur:** Es gibt keine automatische Reparatur kritischer Strukturen. Wer CC oder Wohnhabitat verfallen lässt, verliert Stufen (siehe oben); die Gegenmaßnahme ist die normale Reparatur.

> **Hangar unter Schiffsstufe:** Die Hangar-Stufe ist die Schiffsklasse (§4c). Sinkt die Halle unter die Klassenstufe oder die Buchtnummer eines Schiffs, wird das Schiff **inaktiv**. Das gilt für Stufenverlust durch Verfall ebenso wie für einen Rückbau (§11.5). Inaktiv heißt:
> - Das Schiff kann keine neue Mission starten. Es wird nicht zerstört und bleibt in seiner Bucht.
> - Eine laufende Mission läuft unverändert weiter und endet regulär, mit Rückkehr, Ergebnis und Verschleiß. Das Schiff ist danach inaktiv, solange die Halle noch zu niedrig ist.
> - Erreicht der Hangar wieder die Stufe des Schiffs, ist das Schiff automatisch wieder einsatzbereit. Der Spieler muss dafür nichts tun.

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

Die Verfallsraten stehen je Gebäude in `config/buildings.php` (`decay_rate`, `max_status_points`), der Schiffs-Verschleiß je Schiffstyp in `config/ships.php` (`wear_per_sol`), die Reparaturkosten in `config/game.php → repair` und `ship_repair`.

### Designabsicht

Decay erzwingt regelmäßige AP-Investitionen in Wartung. Eine vernachlässigte Kolonie verliert schrittweise Infrastruktur. Die Kombination aus kleiner decay_rate und fraktionaler Akkumulation bedeutet: nichts bricht sofort — aber vernachlässigte Entitäten degradieren stetig.

> **Unbesetzte Arbeitsplätze beschleunigen den Verfall nicht.** Verfall läuft unabhängig davon, ob Arbeitsplätze unbesetzt sind. Grund: Unbesetzte Arbeitsplätze entstehen nur durch den Verlust von Wohnraum — also meist durch Verfall selbst. Ein Multiplikator hätte daraus eine Kaskade gemacht (Wohnhabitat verfällt → Arbeitsplätze unbesetzt → alles verfällt schneller, auch die übrigen Wohnhabitate). Die Folgen tragen allein Unterbesetzung und Gate (§6, „Wohnraumverlust — Konsequenzen"); beide heilen, sobald der Wohnraum zurück ist, und verstärken den Verfall nicht.

---

### Schiffs-Verschleiß

> **Status: Implementiert.** `GameTick::processHangarMissions()` zieht `wear_per_sol` pro Tick von jedem dispatchten Schiff ab.

Schiffe verfallen **nicht durch Zeitablauf**, sondern durch aktiven Einsatz. Der einzige aktive Einsatz ist die **Außenmission** (Hangar-Dispatch, §8b): Für jeden Sol, den ein Schiff im Zustand `dispatched` verbringt, verliert es Verschleißpunkte.

```
Pro Tick, je Schiff mit ship_state = 'dispatched':
colony_ships.status_points -= wear_per_sol (je Schiffstyp, config/ships.php)
```

Jeder Schiffstyp hat eine unterschiedliche Verschleiß-Rate pro Sol im Einsatz. Leichte Typen (Drohne) verschleißen schneller; schwere oder gepanzerte Typen (Korvette) sind robuster. Exakte `wear_per_sol`-Werte: `config/ships.php`.

**Recall als Schonungs-Entscheidung:** Da Verschleiß pro Sol unterwegs anfällt, spart ein vorzeitiger Rückruf reale SP — Missionsertrag gegen Schiffszustand abwägen. Beim Dispatch selbst fällt kein Verschleiß an (dort wirken bereits Navigation-AP und Organika als Kosten).

**Rückkehr-Garantie:** Ein Schiff startet nur, wenn es nach der Verschleiß-Prognose (`wear_per_sol × sol_distance × 2`, Hin- und Rückweg) mit Status-Points über null zurückkommt. Das gilt für alle Missionen außer dem Bergungsflug (der nicht verschleißt) Kein Schiff startet, das unterwegs ausfiele. Zu abgenutzte Schiffe müssen erst repariert werden; der Dispatch-Dialog zeigt die Prognose als Chip und sagt, wie viel Reparatur noch fehlt. Eine einzige Funktion rechnet das für Dispatch, Zielliste, Fundkarte und Bot.

**SP ≤ 0 unterwegs:** Die Mission wird automatisch abgebrochen (`state = aborted`), das Schiff kehrt flugunfähig zurück (`docked`, 0 SP), ein etwaiger Missionsertrag entfällt. Eintrag im Kolonieprotokoll (`colony_log`) und im Sol-Report. Schiffe werden **nie zerstört** — ein Totalverlust, der nur über Nexus-Ersatzkauf heilbar wäre, wäre ein Fail-Spiral-Risiko.

**Kein passiver Decay:** Ein gedocktes Schiff verliert keine SP. Das unterscheidet Schiffs-Verschleiß fundamental von Gebäude-Decay — nur Aktivität kostet.

> ⚠️ BALANCE CONCERN: `wear_per_sol`-Richtwerte sind ungetestet. Zielgröße: eine 3-Sol-Mission kostet 2–3 Construction-AP Reparatur (Drohne). Fühlt sich Verschleiß im Playtest wie Rauschen an → Werte ×1,5; frisst er den Construction-Pool → Drohne auf 1,0 senken.

**Reparatur:** Fixkosten pro Klick — AP gegen Statuswiederherstellung, gedeckelt auf die maximalen Status-Points. Die Reparatur kostet nur AP, weder Regolith noch Credits, und gilt je Schiff, auch auf dem Vorfeld. Gleiche Interaktion wie Gebäude-Reparatur, damit sich „Reparieren" spielweit konsistent anfühlt; der AP-Verbrauch wird vorab als Chip am Button angezeigt. Werte: `config/game.php → ship_repair`, Tabelle in `docs/game-reference.md`. Eine passive Reparatur im Dock gibt es bewusst nicht; sie käme erst, wenn die Messung zeigt, dass Schiffe sonst dauerhaft warten.

> **Designabsicht:** Schiffe, die viel fliegen, brauchen Wartung. Das erzeugt eine natürliche Kosten-Nutzen-Entscheidung: Intensive Missionsnutzung ist teuer in AP, die sonst in Gebäude fließen könnten.

---

### Berater-Burnout — zurückgestellt

> **Kein Berater-Burnout.** Der Entwurf (Wahrscheinlichkeitsformel, Erholungsphase, AP-Ausfall) liegt im [Archiv](gdd/archiv-berater-aussendienst-burnout.md). Ein Berater kann derzeit nur durch die **Nexus-Sanktion** zeitweise gesperrt sein (§15 „Sanktionen“); das Feld `unavailable_until_tick` dient allein diesem Zweck.

---

## 8. Hangar & Schiffe

> **Abgrenzung:** Es gibt keine Flotten, keine Flottenbefehle, keine Galaxie- oder Systemkarte und keine Kämpfe. Schiffe existieren ausschließlich über den **Hangar** (§8b) und fliegen **Außenmissionen** (Dispatch). Der **Reisende Händler** ist davon unabhängig und in §12 Handel, Kanal 2 beschrieben.

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
| **Konsul-Verhandlung** | Credits (reduziert) + Verhandlungs-AP | Konsul investiert AP explizit → niedrigerer Preis; der Nachlass je AP wächst mit dem Konsul-Rang (A13) |
| **Geschenk-Drohne** | kostenlos, einmalig | Beim ersten Hangar-Bau steht eine Drohne auf dem Vorfeld, ohne Credits und ohne Schuld (nur einmal pro Lauf) |
| **Händler** | Credits (Sonderinventar, sobald der Item-Pool Schiffe enthält) | Schiff direkt `docked` oder `pending` |

> **Hinweis Namenskollision:** Die "Konsul-Verhandlung" hier ist **risikofrei** — mehr AP kauft einen garantiert niedrigeren Preis, kein Fehlschlag möglich. Nicht zu verwechseln mit der **"Cantina-Verhandlung (Risiko-Handel)"** in §12 Kanal 1 — dort kann die Verhandlung scheitern und das Angebot geht komplett verloren. Zwei unterschiedliche Mechaniken, bewusst unterschiedlich benannt.

**Lieferzeiten Nexus-Anfrage:** je Schiffstyp gestaffelt — Drohne am schnellsten, Korvette am langsamsten (`config/game.php → hangar`).

**Nexus-Kredit** erst ab CC Lv2 verfügbar. Nutzung erzeugt kleinen Trust-Abzug ("Die Kolonisten machen sich Sorgen über wachsende Schulden").

> **Idee (später konzipieren):** Preis/Qualitäts-Tradeoff beim Nexus-Kauf — Nexus verkauft nicht unbedingt das beste Material. Wahl zwischen "teurer kaufen → guter Status (volle SP)" und "günstiger kaufen → reparaturbedürftig (niedrige Start-SP)". Verzahnt den Credits-Sink mit dem Reparatur-Sink (§7) und der Dispatch-Sperre (billiges Schiff unter Schwellenwert kann nicht sofort auf lange Mission). Noch nicht designt.

### Schiffs-Besitz-Modell

Die Halle bietet **Buchten**: Stufe n öffnet n Buchten, in jeder steht höchstens ein Schiff, und nur Schiffe in einer Bucht können entsandt werden. Das **Vorfeld** ist eine zusätzliche Fläche, die nur die Geschenk-Drohne nutzt. Darüber hinaus können Schiffe **ohne Bucht** existieren (`bay = NULL`, `ship_state = 'pending'`):

- Entsteht durch Händler-Kauf oder Nexus-Lieferung, wenn keine freie Bucht da ist
- Sichtbar im Hangar-Screen als separater Bereich „Nicht zugewiesen“ mit Decay-Countdown
- Verfällt automatisch nach N Solen (TickService), wenn nicht einer Bucht zugewiesen
- **Decay-Zeit:** nach Playtest kalibrieren

Mehrere Schiffe desselben Typs sind erlaubt. Die natürliche Begrenzung ergibt sich aus der Zahl der Buchten (Hallenstufe) und den Credits für Nexus-Anfragen. Kein weiterer Hard-Cap nötig.

### Karten-States (Carousel)

| State | Beschreibung | Aktion |
|-------|-------------|--------|
| Leer | Slot verfügbar | Nexus-Anfrage starten |
| Lieferung (`building`) | Schiff unterwegs von Nexus | Wartet N Sole |
| Angedockt (`docked`) | Schiff einsatzbereit | Entsenden / Reparieren |
| Unterwegs (`dispatched`) | Schiff auf aktiver Mission | Zurückrufen / Missionslog |

Nicht zugewiesene Schiffe (`pending`) erscheinen als separate Karten am Ende des Carousels mit sichtbarem Decay-Timer.

### Außenmissionen — Missionskatalog

> **Status: Implementiert** — `config/missions.php`, `HangarService::dispatchShip()`/`getMissionCatalogFor()`, `GameTick::processHangarMissions()`. `mission_ruin_expedition` zahlt Credits; der Almanach-Unlock folgt mit §17.

Außenmissionen sind der einzige aktive Einsatz von Schiffen (§7 Schiffs-Verschleiß). Jede Mission ist ein ziviler Auftrag — Erkundung, Logistik, Bergung, Schutzdienst. Es gibt keine Gegner und keinen Kampf (§9-Designlinie): Das Risiko einer Mission ist ausschließlich physisch — Verschleiß pro Sol unterwegs und der automatische Abbruch bei 0 SP.

#### Kostenmodell

Beim Dispatch fallen einmalig an (beide Kosten gaten den Start, AP-Chip-Konvention: alle Kosten vorab als Chips am Button):

- **Navigation-AP:** `sol_distance` × Faktor (`config/missions.php → nav_ap_per_sol`)
- **Organika:** `sol_distance` × Faktor als Proviant & Betriebsstoffe (`organika_per_sol`; gilt einheitlich auch für die unbemannte Drohne — eine Ausnahme würde Drohnen-Missionen zum kostenlosen Optimalpfad machen)
- Einzelne Missionen haben Zusatzkosten (z.B. Hilfsgüter-Fracht), im Katalog vermerkt.

`sol_distance` ist die **einfache Strecke**; die Gesamtdauer beträgt `2 × sol_distance` Sole (Hin- und Rückweg — deckungsgleich mit der Verschleiß-Prognose aus §7).

> **⚠️ Zu kalibrieren:** Die Navigation-AP-Staffel ist gegen den gemeinsamen Pool (§13.1) zu setzen. Absicht: lange Expeditionen sind über Opportunitätskosten an die Raumfahrer-Progression gekoppelt, ohne hartes Gate — Distanz 1–2 ohne Raumfahrer machbar, Distanz 4–5 nur mit spürbarem Verzicht auf Bau- und Kenntnisprojekte.

#### Schwierigkeit & Erfolgschance

> **Status: Implementiert** — Werte in `config/game.php` → `missions.difficulty` und `config/missions.php` → `difficulties` pro Katalogeintrag.

Eine Außenmission hat keinen garantierten Ausgang mehr, sondern eine Erfolgschance. Beim Dispatch wählt der Spieler zwischen zwei der drei Schwierigkeitsstufen — leicht, normal, schwer — die für die jeweilige Mission im Katalog hinterlegt sind. Eine höhere Stufe senkt die Basis-Erfolgschance, hebt aber im Erfolgsfall die Belohnung an: der Spieler tauscht Sicherheit gegen Ertrag, statt dass die Mission selbst über den Ertrag entscheidet.

Zur Basis-Chance der gewählten Stufe addieren sich zwei Bonusquellen: der Rang des Raumfahrers (§12, koloniedeckt, unabhängig von der konkreten Mission) und — nur falls die Mission ein Kenntnis-Gate hat — jedes Kenntnis-Level oberhalb des Gate-Mindestlevels. Ungegatete Missionen profitieren nur vom Raumfahrer-Bonus. Die resultierende Gesamtchance ist nach oben gedeckelt, ein risikoloser Erfolg bleibt also auch bei hohem Rang und hoher Kenntnis ausgeschlossen.

Scheitert eine Mission, entfällt die Belohnung vollständig — das Schiff kehrt ohne Ertrag zurück. Auf der höchsten Schwierigkeitsstufe (schwer) kostet ein Fehlschlag das Schiff zusätzlich Verschleiß (Status-Points, §7): Dort ist das Risiko nicht nur entgangener Ertrag, sondern eine echte Materialkonsequenz. Konkrete Werte (Basis-Chancen, Reward-Multiplikatoren, Bonushöhen, Chance-Cap, Hard-Fail-Verschleiß sowie die Stufen-Zuordnung je Mission): siehe `docs/game-reference.md#hangar-missionen-schwierigkeit`.

#### Katalog

| Key | Name | Schiff | Gate / Verfügbar |
|---|---|---|---|
| `mission_courier_run` | Botenflug | Drohne | sofort |
| `mission_recon_flight` | Erkundungsflug | Drohne | sofort |
| `mission_find_salvage` | Bergungsflug | Drohne o. Frachter | gescannter Fund; Träger je Fundgröße (große Funde nur Frachter) |
| `mission_data_sweep` | Datensammelflug | Drohne | Kartografie Lv1 |
| `mission_supply_run` | Versorgungsfahrt | Frachter | Frachter vorhanden |
| `mission_trade_convoy` | Handelsfahrt | Frachter | Handel Lv1 |
| `mission_aid_transport` | Hilfsgütertransport | Frachter | sofort (ungegatet — zweite Frachter-Mission ohne Kenntnis-Gate, deckt die Vertrauens-Lücke von Pfad B) |
| `mission_salvage_sweep` | Trümmerbergung | Frachter o. Korvette | Bautechnik Lv1 |
| `mission_escort_convoy` | Konvoi-Begleitung | Korvette | Korvette vorhanden |
| `mission_perimeter_patrol` | Umkreis-Patrouille | Korvette | Verteidigung Lv1 |
| `mission_ruin_expedition` | Ruinen-Expedition | Frachter o. Korvette | tiefengescanntes Ruinen-Tile (im Normalbetrieb nicht erreichbar, §4a) |
| `mission_long_range_expedition` | Fernexpedition | Drohne | Kartografie Lv3 |

Exakte Kosten (Navigation-AP, Organika-Proviant, Zusatzmaterialien), Distanzen, Belohnungen und Effekte pro Mission: siehe `config/missions.php`.

**Schiffsrollen:** Drohne = Information (Tiles, Scans, Daten), Frachter = Güter, Korvette = Schutzdienste und Bergung. Nicht jede Mission steht jedem Schiff offen — das gibt der Akquise-Entscheidung (§8b Akquise-Pfade) strategisches Gewicht.

**Gate-Schema — nur zwei Typen:** Eine Mission ist entweder an **eine Kenntnis mit Mindestlevel** gebunden oder an ein **Ziel-Tile** (Fund-/Ruinen-Tile — die Mission braucht physisch ein Ziel). Keine CC-Level- und keine Gebäude-Gates: Gebäude-Gates wären redundant (jede Kenntnis setzt das Analytik-Labor ohnehin voraus), CC-Gates wären eine dritte, schwer kommunizierbare Bedingungsart. Ungegatete Missionen und die Fund-Bergung stehen **immer** zur Verfügung — jedes Schiff hat ab dem ersten Sol sinnvolle Einsätze.

**Pfadwahl-Interaktion (Hangar-first):** Wer den Hangar-Pfad vor dem Analytik-Labor wählt, hat noch keine Kenntnisforschung — die kenntnis-gebundenen Missionen erscheinen ausgegraut mit Bedingung. Das ist Absicht: Hangar-first heißt realistisch Drohne zuerst, und die Drohne ist mit 3 sofort verfügbaren Missionen am besten versorgt; der Frachter hat mit der Versorgungsfahrt eine wiederholbare, Organika-positive Kernmission. Die ausgegrauten Missionen sind die sichtbare Zugkraft, das Labor als zweites Pfadgebäude nachzuziehen — Pfade sind Sequenzierung, kein Ausschluss. ⚠️ Playtest-Beobachtungspunkt: Fühlt sich eine früh gekaufte Korvette vor dem Labor-Bau unterbeschäftigt an, ist der Hebel eine zweite ungegatete Korvetten-Mission (z.B. Umkreis-Patrouille auf „sofort" senken), nicht die Streichung der Gates.

> **Idee (später konzipieren):** Bar-Begegnungen (Cantina-NPCs) können Missions-Varianten mit verbesserten Boni oder veränderten Parametern anbieten — als Alternative für den Pfad Hangar-first → Cantina-second (vor dem Labor). Ziel: verschiedene Spielweisen gleichwertig halten (Roguelike-Varianz). Noch nicht designt.

**Kenntnis-Skalierung — Erfahrung senkt den Proviantbedarf:** Eine einzige, spielweite Regel:

```
Organika-Kosten der Mission = Basis − (Level über Gate − 1), Minimum 1 pro Sol
```

Navigation-AP skalieren nie (die AP-Staffel ist das Raumfahrer-Progressionsgate, §13). Missionen ohne Kenntnis-Gate skalieren nicht. Begründung: Kostensenkung statt Belohnungserhöhung funktioniert für alle Belohnungstypen einheitlich (auch Tiles, Scans, Almanach — nicht bezifferbar), lässt Balance-Deckel unberührt, und erhält den Organika-Sink. Exakte Skalierungswerte und Basis-Kosten: `config/missions.php`.

**Wiederholbarkeit:** Missionen sind wiederholbar; Ausnahme: Ruinen-Expedition ist einmalig pro enthülltem Ruinen-Tile. Die natürliche Drossel für alles andere ist die Kostentrias AP + Organika + Verschleiß (Reparatur: nur AP, §7).

#### Resolution

- **Rückkehr:** `return_tick = dispatch_tick + 2 × sol_distance`. Die Auflösung läuft im Tick im selben Schritt wie der Schiffs-Verschleiß (§7), **nach** dessen Anwendung — der SP-0-Abbruch (`state = aborted`, kein Ertrag) hat Vorrang. Bei Rückkehr: `state = completed`, Schiff `docked`, Belohnung wird gutgeschrieben, Eintrag im Kolonieprotokoll (`colony_log`) und im Sol-Report.
- **Recall:** Keine anteilige Belohnung, keine Rückerstattung — auch nicht bei sofortigem Abbruch im selben Sol wie der Dispatch (AP und Organika werden beim Dispatch instant fällig, unabhängig von der tatsächlich zurückgelegten Zeit). Keine Mindestwartezeit vor dem Recall — der Spieler kann jederzeit zurückrufen, verliert dabei aber immer die vollen Dispatch-Kosten. Der Wert des Rückrufs ist gesparter Verschleiß (§7 „Schonungs-Entscheidung") — anteilige Erträge würden systematisches Halbstrecken-Abbrechen zum Optimalpfad machen.
- **Ausgang:** Eine Mission gelingt oder scheitert nach der Erfolgschance der gewählten Schwierigkeit (s. o.); es gibt keinen Teilerfolg. Daneben liegt die Risiko-Achse in Verschleiß und Abbruch. Zufall in der Belohnungshöhe beschränkt sich auf die Fernexpedition, deterministisch aus dem Run-`rng_seed`. Der Bergungsflug hat weder Wurf noch Streuung und verschleißt das Schiff nicht (Funde liegen im Nahbereich); nur die Tragkraft des Schiffs entscheidet vorab, ob er möglich ist. Die Versorgungsfahrt liefert nur Organika, kein Regolith mehr.

> **Kein zustandsbasierter Missionsausgang** (Rückkehr-SP bestimmt Ertragsstufe, analog §9). Da Verschleiß deterministisch ist, wäre der Ausgang beim Dispatch bereits bekannt — kein Risiko, sondern eine Doppelbestrafung langer Missionen (die Fernexpedition kehrt selbst mit vollen Start-SP bei 25% zurück und würde immer „fehlschlagen") plus Vollreparatur-Zwang vor jedem Start. Ein reiner Würfel-Fail wiederum verletzt „Opportunitätskosten statt Strafe" (§1.1): Schiffs-Missionen kosten harte Ressourcen im Voraus — ein Fehlschlag vernichtet Bezahltes. Fehlt im Playtest Spannung, ist der Hebel die Spanne der Fund-Missionen (Loot-Tabellen verbreitern), nicht ein Fehlschlag-Layer.

#### Missionslog

Jede Mission wird in `colony_hangar_missions` gespeichert (`destination` trägt den `mission_key`, Sol-Distanz aus dem Katalog, Zustand `active/completed/recalled/aborted`). Im Hangar-Screen einsehbar; abgeschlossene Missionen zeigen die erhaltene Belohnung.

> ⚠️ BALANCE CONCERN: Trümmerbergung und Fernexpedition sind neben Import und Cantina eine dritte Werkstoff-Quelle. Richtwert-Deckel: max. ~1 Werkstoff pro Missions-Sol Durchsatz je Schiff — der Nexus-Import (§3) muss die schnellere, die Mission die günstigere Option bleiben. Nach Playtest kalibrieren.

> ⚠️ BALANCE CONCERN: Botenflug/Konvoi-Begleitung sind wiederholbare Credit-Quellen. Mit mehreren Drohnen können diese skalieren — gegen Relaisvergütung und Berater-Upkeep (§13) prüfen; notfalls Prämien senken statt Cooldowns einführen.

> **Hinweis Umkreis-Patrouille:** Die Patrouille liefert Credits und ein Vertrauens-Ereignis (`encounter_won`). Ein Milderungs-Effekt gegen Gefahren (Almanach-Bonus `encounter_prep`, §17) ist nicht umgesetzt; käme er, gilt: Milderungseffekte stapeln nicht, maximal eine Ausgangsstufe Milderung pro Gefahr.

> ⚠️ BALANCE CONCERN: Erkundungsflug darf die Ring-Erkundung nicht obsolet machen. Er ist als effizientere, aber langsamere Alternative für äußere Ringe gedacht. Wirkt er im Playtest dominant → auf weniger Tiles senken oder Distanz erhöhen.

### UI-Buttons

| Button | Zustand | Funktion |
|--------|---------|---------|
| Nexus anfragen | Leer | Schiffstyp wählen, Akquise-Pfad wählen |
| Entsenden | `docked` | Mission aus Katalog wählen (gefiltert nach Schiffstyp; Chips: AP, Organika, Dauer, Verschleiß-Prognose, Belohnung; nicht erfüllte Gates ausgegraut mit Bedingungshinweis) |
| Zurückrufen | `dispatched` | Schiff zurückrufen |
| Reparieren | `docked`, SP < max | Repair-Order (Construction-AP) |
| Bucht zuweisen | `pending` | Schiff einer freien Bucht zuordnen |

### Technischer Stack

Alpine.js + PicoCSS. Carousel-Logik in `public/js/carousel.js`, Styles in `public/css/carousel.css`.

---

## 9. Begegnungen & Gefahren

Begegnungen finden ausschließlich auf der Kolonieoberfläche statt (Hex-Grid, §4a); Flotten und Systemkarte gibt es nicht (§8). Es gibt kein Kampfsystem und keine Stärkewerte; Schiffe sind nie Gegner oder Verteidiger.

**Implementiert.** `EncounterService` löst alle drei Gefahrentypen pro Sol auf, Ausgangsstufen basieren auf dem SP-Zustand (siehe unten). Sturm nutzt eine 1-Sol-Vorwarnung über `colony_log` und wirkt koloniweit (`GameTick::rollStorm()`/`resolveStormWarning()`); Geologische Instabilität und Seuchenausbruch treffen sofort ohne Vorwarnung. Ein Cooldown zwischen Ereignissen (`game.encounter.cooldown_sols`) puffert gegen Spiral-Risiko. In Phase 1 rampt die Trigger-Chance von 0 auf volle Stärke (`game.encounter.phase1_ramp_sols`), damit die Phase-1-Frist (Sol 20) erreichbar bleibt — *Annahme:* Die Rampenlänge ist gegen die Frist (Sol 20) zu prüfen — früh ist die Welt schwächer, aber nicht harmlos. Lore: Der Startbestand kommt aus einem automatisierten Frontier-Depot; frisch gelandete Kolonien sind verwundbarer.

Die Kolonie ist keine Festung, sondern eine verwundbare Ansiedlung auf einer kaum erschlossenen Welt. Gefahren haben keinen Marschbefehl und keine Absicht — sie sind lokale Zwischenfälle: Wetter, Geologie, Erschöpfung der Kolonisten. Es gibt keine Konfrontation im militärischen Sinn, nur einen Zustand vorher und einen Zustand danach.

### Grundprinzip: Zustand statt Konfrontation

Statt gegen eine gegnerische Stärke gewürfelt wird, wirkt jede Kolonistengefahr direkt auf den bestehenden Zustand der Kolonie — auf `status_points` betroffener Gebäude (§7) und auf Vertrauen (§14). Es gibt keinen Gegner-Stärkewert; es gibt nur die Frage, wie gut die Kolonie vorbereitet war. Ein Gebäude mit vollen SP übersteht ein Ereignis fast unbeschadet, ein vernachlässigtes Gebäude nimmt deutlichen Schaden. Wartungs-AP wird damit indirekt zur Gefahrenabwehr, ohne dass der Spieler im Moment des Ereignisses aktiv reagieren muss — das belohnt sowohl vorausschauendes aktives Spiel als auch entspanntes passives Spiel mit solider Grundwartung.

**Ausgangsstufen** (SP-Anteil des betroffenen Gebäudes zum Ereigniszeitpunkt):

| SP-Zustand | Ausgang | Trust-Event | Effekt |
|---|---|---|---|
| Hoch | Abgewehrt | `encounter_won` | kein/minimaler SP-Verlust |
| Mittel | Beschädigt | `encounter_lost` | SP-Verlust |
| Niedrig | Kritisch | `colony_threatened` | SP-Verlust + ggf. sofortiger Level-Down bzw. Instanzverlust (§7-Regeln) |

Damit werden zugleich die in §14 markierten Trust-Events mit Anwendungsfällen unterlegt. Die Leitstelle dämpft die Malus-Ausgänge (`encounter_lost`, `colony_threatened`; bestehende Regel, §14).

### Gefahrentypen

| Gefahr | Wirkbereich | Trigger | Konsequenz | Häufigkeit (Richtwert) | Abschwächung |
|---|---|---|---|---|---|
| **Sturm** | **Koloniweit** — trifft alle Gebäude der Colony Zone gleichzeitig (Harvester ausgenommen, siehe unten) | Zufällig; Basis-Chance/Sol steigt mit Run-Schwierigkeit | SP-Verlust je Gebäude nach individueller Ausgangsstufe (Tabelle oben); ein gemeinsamer Trust-Ausgang für die ganze Kolonie (siehe „Wirkbereich"-Abschnitt) | variabel nach Phase (häufiger mit mehr Gebäuden) | Hohe SP durch regelmäßige Reparatur — bei koloniweitem Wirkbereich zählt jetzt der Zustand *aller* Gebäude, nicht nur eines |
| **Geologische Instabilität** | **Einzelgebäude, fest** — trifft immer den Harvester; kein Zufallsziel unter mehreren Gebäuden, da mechanisch an das Harvester-Tile gekoppelt | Chance steigt mit Solen seit letzter Relocation, sinkt mit Kenntnis Geologie | Produktionsausfall des Harvesters für eine Weile (statt zusätzlichem Trust-Malus — kein doppelter Bestrafungseffekt) | seltener als Stürme | Kenntnis Geologie senkt Chance; Relocation setzt Zähler zurück |
| **Seuchenausbruch** | **Kein Gebäude-Wirkbereich** — wirkt direkt auf Kolonie-Systeme (Wohnraum/AP-Generierung), nicht auf die SP eines oder mehrerer Gebäude; die Einzelgebäude/koloniweit-Unterscheidung greift hier nicht | Emergent statt rein zufällig: nur möglich bei echter Vernachlässigung (Hunger-Spirale oder sehr niedriges Vertrauen), dann Zufallschance/Sol | Supply-Cap oder AP-Generierung temporär reduziert + `colony_threatened` | nur im Vernachlässigungsfall — bei gesunder Kolonie 0% Grundrisiko | Krankenstation (infirmary) und Kenntnis `health` senken Chance/Schwere |

### Wirkbereich (Scope): Einzelgebäude vs. koloniweit

**Kategorien:** Kolonistengefahren fallen in genau eine von zwei Wirkbereich-Kategorien.

- **Einzelgebäude-Wirkbereich** — ein Ereignis trifft ein einzelnes, bestimmbares Gebäude. Das Ziel kann zufällig unter mehreren geeigneten Gebäuden gewählt sein (Status quo vor dieser Entscheidung, sofern für einen zukünftigen Gefahrentyp gebraucht) oder — wie bei Geologischer Instabilität — mechanisch fest an ein bestimmtes Gebäude gekoppelt sein. In beiden Fällen bleibt der Schaden lokal: ein Gebäude, ein Ausgang, ein Trust-Event.
- **Koloniweiter Wirkbereich** — ein Ereignis trifft gleichzeitig **alle** Gebäude der Colony Zone. Sturm ist der erste und bislang einzige Gefahrentyp dieser Kategorie: Ein Sturm zieht nicht über ein Gebäude, sondern über die ganze Siedlung. Das passt zum Bild — Wetter kennt keine Gebäudegrenzen, ein geologischer Riss oder eine Krankheit dagegen schon (oder ist, im Fall der Seuche, ohnehin kein gebäudebezogenes Phänomen, siehe Tabelle).

**Schadensberechnung bei koloniweitem Wirkbereich:** Jedes betroffene Gebäude bekommt weiterhin seine **eigene** Ausgangsstufe nach seinem eigenen SP-Zustand (Tabelle oben, unverändert) — ein gut gewartetes Gebäude übersteht den Sturm unbeschadet, ein vernachlässigtes daneben nimmt trotzdem Schaden. Es gibt keinen einzigen Wurf für die ganze Kolonie, sondern so viele Berechnungen wie betroffene Gebäude. Der Harvester bleibt von Sturm ausgenommen (analog zur bestehenden Sonderrolle bei Seuche: er hat mit Geologischer Instabilität bereits eine eigene, gebäudespezifische Gefahr — kein doppelter Bestrafungseffekt auf dasselbe Gebäude durch zwei verschiedene Gefahrentypen).

**Trust-Aggregation:** Trust reagiert **nicht** proportional zur Zahl betroffener Gebäude — sonst würde ein koloniweites Ereignis bei einer größer ausgebauten Kolonie automatisch einen Vielfachen Trust-Schaden gegenüber derselben Kolonie früh im Run auslösen, ohne dass der Spieler dafür etwas anders gemacht hätte. Ein koloniweites Ereignis löst **einen einzigen** Trust-Event für den gesamten Vorfall aus, bestimmt durch die **schlechteste** unter den betroffenen Ausgangsstufen (ein einziges kritisch getroffenes Gebäude reicht, damit der ganze Sturm als `colony_threatened` gilt, selbst wenn alle anderen Gebäude ihn abgewehrt haben). Die SP-Verluste selbst bleiben dagegen additiv über alle betroffenen Gebäude — der strukturelle Schaden (Reparaturbedarf, ggf. mehrere gleichzeitige Level-Downs) skaliert bewusst mit der Koloniegröße, nur der Trust-Preis nicht doppelt obendrauf.

**Vorwarnung/Protokollierung bei koloniweitem Wirkbereich:** Die Vorwarnung referenziert bei Sturm kein einzelnes Gebäude mehr (`entity-chip auf das betroffene Gebäude` gilt nur noch für Einzelgebäude-Ereignisse) — sie kündigt die ganze Kolonie als Ziel an. Die Ausgangsmeldung braucht eine **Sammel-Zusammenfassung statt N Einzelmeldungen**: eine Zeile pro Sturm-Ereignis, die die Verteilung der Ausgangsstufen über die betroffenen Gebäude nennt (z. B. „X abgewehrt, Y beschädigt, Z kritisch"), nicht eine Meldung pro Gebäude. Das gilt sowohl für den `colony_log`-Eintrag als auch — sobald die unten verlinkte Umsetzungslücke geschlossen ist — für die Aufbereitung in `SolReportService::eventsGroup()`: dort muss ein koloniweites Sturm-Ereignis als **eine** aggregierte Zeile in der Gruppe „Begegnungen" erscheinen, nicht als eine Zeile je betroffenem Gebäude.

> **Trigger-Kalibrierung bei koloniweitem Wirkbereich:** Die Sturm-Trigger-Chance skaliert nicht mit der Gebäudeanzahl (`base_chance`/`chance_cap`, kein `chance_per_building`) — sonst wäre die Koloniegröße doppelt eingepreist (Häufigkeit **und** Schadenssumme). Rechenweg im Kommentar zu `game.encounter.storm.*`. **Offen:** `damaged_sp_loss_pct`/`critical_threshold_pct` sind einzelziel-kalibriert; die Regolith-Startreserve (§13.7 „Phase-1-Pacing") ist gegen koloniweite Stürme mit mehreren gleichzeitigen Kritisch-Treffern noch nicht neu gerechnet — nach dem nächsten PlaytestBot-Batch prüfen.

**Additives Risiko-Modell — `health`-Kenntnis und Krankenstation:** Die `health`-Kenntnis trägt wie die Krankenstation additiv zur Seuchenausbruch-Risikoreduktion bei — beide Werte werden gemeinsam gegen denselben Wirkungsdeckel (`plague_risk_reduction_cap`) summiert, kein Konkurrenz-/Stack-Ausschluss (Präzedenzfall: additive Kombination mehrerer unabhängiger Quellen auf denselben Effekt ist Projektstandard, siehe `construction`+`trade` auf dem Rabatt-Pool für AP-Kosten, §13.3). Exakte Werte: `config/game.php` → `health_plague_risk_reduction_per_lv`.

### Vorwarnung & Protokollierung

Ein Ereignis kündigt sich 1 Sol vorher als `colony_log`-Eintrag an (Kategorie „Gefahr", entity-chip auf das betroffene Gebäude/den Harvester verweisend) — analog zum bestehenden Tiefenscan-Vorwarn-Muster, aber ohne AP-Kosten. Das gibt aufmerksamen Spielern ein Zeitfenster für eine Reparatur, bestraft aber niemanden, der die Warnung übersieht.

**Berechnungszeitpunkt und Sichtbarkeit sind zwei getrennte Dinge — beide müssen stimmen.** Der Ausgang ist bereits deterministisch (Gebäudezustand entscheidet, siehe Ausgangsstufen-Tabelle oben) und wird beim Sol-Wechsel berechnet, auf Basis des Zustands am Ende des vorgewarnten Sols — also inklusive jeder Reparatur, die der Spieler in der Zwischenzeit noch vorgenommen hat. Damit ist "Vorwarnung Sol N, Berechnung beim Übergang Sol N→N+1" bereits das richtige Modell, keine Verzögerung ohne Wirkung. Die *Sichtbarkeit* muss diese Kausalität aber tragen, sonst wirkt ein längst berechnetes Ergebnis wie unmotivierter Flavourtext: Der Ausgang gehört sichtbar in den Sol-Report des Solwechsels, der ihn ausgelöst hat (Gruppe „Ereignisse", analog zu anderen dort bereits aufbereiteten Vorkommnissen), nicht nur beiläufig ins Kolonieprotokoll ohne erkennbaren Bezug zur vorherigen Vorwarnung. Die Ausgangsmeldung selbst soll zudem den Grund benennen (z.B. den Gebäudezustand zum Ereigniszeitpunkt), nicht nur das Ergebnis ("abgewehrt") — sonst liest es sich, als sei nichts berechnet worden, obwohl es das war.

**Onboarding:** Beim ersten Kolonistengefahr-Ereignis eines Runs erscheint ein einmaliger Hint ("Gebäude mit niedrigem Zustand sind anfälliger für Zwischenfälle — regelmäßige Reparatur zahlt sich doppelt aus"), analog zu bestehenden One-Shot-Hints. Kein neuer Hint-Mechanismus nötig, nur ein neuer Trigger-Key im bestehenden System.

### Offene Punkte

- Exakte Basis-Chancen/Sol und SP-Verlust-Prozentsätze sind Richtwerte — Kalibrierung nach erstem Playtest.
- Ob Seuchenausbruch als eigenständiges Ereignis oder als Eskalationsstufe des bestehenden Hunger-Malus (§4a Organika) implementiert wird, ist eine Umsetzungsentscheidung für game-developer — design-seitig gleichwertig.
- **Sol-Report:** Sturm-Ausgänge erscheinen als eine aggregierte Zeile im Sol-Report (`SolReportService`, `encounter.storm_resolved`); Geologische Instabilität und Seuchenausbruch erscheinen dort ebenfalls (`encounter.instability_triggered`, `encounter.plague_triggered`).

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

**Alle 7 Kenntnisse sind in jedem Run verfügbar** — endgültiger Zustand, keine offene Design-Frage. Eine roguelike-typische Teilmengen-Auswahl pro Run wurde geprüft und verworfen: Bei nur 7 Kenntnissen wäre das Risiko zu hoch, dass ein Run ohne eine essentielle Kenntnis auskommen müsste. Die Roguelike-Varianz des Spiels trägt sich über andere Systeme (Startbedingungen, Encounter-Würfe, Bar-Angebote, Berater-Erfolgschancen), nicht über die Kenntnisverfügbarkeit.

### Level-Modell ohne Decay

Kenntnisse verwenden das **Level-Modell (Lv1–5)** — identisch zu Gebäuden, aber **ohne Decay**. Einmal erforschtes Wissen bleibt permanent. Es gibt keinen SP-Verfall auf Kenntnissen — das wäre thematisch unlogisch (Wissen verfällt nicht). Die natürliche Begrenzung erfolgt über AP-Knappheit und Rundenstruktur.

Jedes Level wird durch Investition von AP aus dem gemeinsamen Pool erarbeitet; der Analytiker-Rang wirkt über den Pool. AP-Kosten steigen mit dem Level (steigende Glockenform). Das Labor nimmt je Kenntnis und Sol nur begrenzt AP auf (§4b), und Stufe 4 und 5 setzen eine Kommandozentrale gleicher Stufe voraus (`game.knowledge_cc_level_cap`). Die strategische Entscheidung: Breite (viele Kenntnisse auf Lv2–3) vs. Tiefe (wenige Kenntnisse auf Lv4–5).

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
- `construction` senkt additiv die AP-Kosten von Gebäude-Levelups (§13.3) — glockenförmig über die Level gestaffelt (`ap_cost_reduction_per_lv`). `cartography` senkt stattdessen eigenständig die Navigation-AP-Kosten von Tile-Erkundung und Hangar-Missions-Reisekosten (siehe §13.3). `trade` senkt **keine** Bau-Kosten (Handelsgeschick hat thematisch nichts mit dem Ausbau von Gebäuden zu tun) — ihre Wirkung liegt ausschließlich auf Handelskonditionen und Cantina.
- `trade` erhöht zusätzlich die Zahl gleichzeitig aktiver Cantina-Angebote (§12), siehe `bar_offer_boost_per_lv`.
- `trade` erhöht außerdem die Erfolgschance der Cantina-Verhandlung (§12, A13, Kalibrierung offen) — die im Beschreibungstext der Kenntnis versprochene „Verhandlungsführung". Wirkt nur, wenn ein Konsul verhandelt.
- `agronomy`, `health`, `defense` wirken auf das Vertrauen (§14), siehe `trust_per_lv`.
- Analytik-Labor Lv4/5 senkt die AP-Kosten von Kenntnis-Levelups (§13.3) — kein Kenntnis-, sondern ein Gebäudeeffekt, hier der Vollständigkeit halber.

Nicht jede Kenntnis trägt zwingend einen mechanischen Effekt dieser Art — einen einheitlichen Beitrag zum Wohnraum gibt es nicht (§6). Welche Kenntnis welchen Effekt trägt und in welcher Höhe, ist ausschließlich in `config/knowledge.php` gepflegt; Lookup-Tabelle: `docs/game-reference.md#kenntnisse-7-levelup-kosten-effekte`.

> **TODO Design:** Weitere Kenntnis-Effekte (insbesondere für Kenntnisse ohne eigenen mechanischen Effekt bislang) sind offen für spätere Balancing-Passes — nach Playtest, wenn klar ist, welche Lücken am meisten drücken.

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

### Kein Wohnraum-Bonus

Kenntnisse haben keinen Wohnraum-Bonus: Kolonisten brauchen Wohnraum, Wissen schafft keine Betten (§6). Einen einheitlichen Primäreffekt gibt es nicht; Ideen dazu stehen als Balance-Hinweis in §6 „Kenntnisse und Wohnraum".

Bestimmte Kenntnisse beeinflussen auch das Vertrauen der Kolonie (agronomy, health, defense) — Details siehe §14.

---

## 11. Techtree

> **Ausgelagert:** Dieses Kapitel steht in [`docs/gdd/techtree.md`](gdd/techtree.md) — Rolle des Techtrees als Freischalt-Übersicht und einziger Ort für Forschung, Entitäten-Übersicht (11.1), Abhängigkeitsregeln (11.2), Grid-Layout der Techtree-Ansicht (11.3), Detailpanel mit schreibgeschützter Instanzliste (11.4) sowie Gebäude-Aktionen im Tile-Panel der Kolonieansicht inkl. Rückbau (11.5).

---

## 12. Handel (Trade)

### Designprinzip (Phase 3 Redesign)

Handel ist **optional aber lohnend** — der Spieler kann alles auch ohne Handel aufbauen, aber Handel beschleunigt und verbilligt. Kein Zwang, kein Progression-Lock.

Der Handelsort ist die **Bar/Cantina**; dazu kommt der Nexus-Direktimport an der Uplink-Station (§3, §4). Alle Handelsaktivitäten in der Cantina — Kauf, Verkauf, NPC-Angebote — laufen über dieselbe Mechanik. Es gibt keinen separaten Marktplatz und keinen Handel mit anderen Spielern.

---

### Kanal 1: Bar/Cantina (primär, früh, informell)

Die Bar ist ab CC Lv2 verfügbar. Sie ist der Ort des Handels — verkörpert durch den Reisenden Händler **Corvan Ashe** (`docs/characters/merchant.md`, Kanal 2/`MerchantService`) — und erst in zweiter Hinsicht der Ort für Events und Missionen (übrige Cantina-Charaktere aus `config/characters.php`).

**Zwei Angebotsquellen, klar getrennt:**

1. **Corvan — der strukturierte Credits-Handel.** Corvan erscheint regelmäßig (Intervall `config/game.php → merchant`) und bringt bei jedem Besuch zwei Ebenen mit:
   - **Alltagsgeschäft (bei jedem Besuch):** Commodity-Handel gegen Credits — Kauf (Credits→Ressource, mit Losgröße an die Zahlungsfähigkeit der Kolonie gebunden) und Verkauf (Organika→Credits, mehrere Lose je Besuch, §4b „Pfad-C-Hebel"). Ein unerschwingliches Kaufangebot entfällt einfach, es gibt keinen Barter-Fallback.
   - **Kuratiertes Sonderinventar (Sub-Chance je Besuch):** AP-Pakete, Schiffe, Information, Einmal-Items — die Kategorie-Tabelle unter Kanal 2.
2. **Anonyme Gästerotation — überwiegend Tauschhandel.** Pro Sol erscheinen 0–2 Gäste (Dax, Voss, …), jeder mit einem Ressource↔Ressource-Angebot, das 2–4 Sole gültig ist (abhängig vom Bar-Level). Eine seltene Ausnahme (T10, „Eher sollte man Regolith verkaufen um Credits zu bekommen, statt andersrum"): gelegentlich bietet ein Gast statt eines Tauschs einen Regolith-Verkauf gegen Credits an — deutlich seltener als die normale Tauschrotation und unabhängig von Corvan.

**Konsul-Rang-Skalierung:** Corvans Besuchsrhythmus ist vom Konsul unabhängig — ein häufigerer Corvan wäre ein passiver Einkommens-Hebel und würde eine bedingungslose, garantierte Passiveinnahme schaffen (A22). Der Konsul wirkt stattdessen auf die *Qualität* der Geschäfte (bessere Kaufkonditionen über den Handelskonditionen-Pool, s.u.) und auf den *Planungsvorsprung* (Marktbericht, s.u.). Die genaue Losanzahl pro Besuch ist ein Playtest-Kandidat, bis eine Credits-Bilanz über den Run eine Zielgröße liefert (§4b).

**Marktbericht (Konsul, A13):** Der Konsul kündigt Corvans nächsten Besuch vorab an — je höher der Rang, desto weiter im Voraus. Auf dem höchsten Rang zeigt der Bericht zusätzlich, welche Sonderinventar-Kategorien (AP-Paket, Information, Einmal-Item — Schiffe erscheinen dort erst, sobald der Item-Pool welche enthält) Corvan mitbringt, nicht aber die konkreten Lose des Alltagsgeschäfts. Der Spieler kann damit Credits für ein absehbar teures Sonderangebot zurückhalten oder Organika für die Verkaufslose aufsparen. Es ist reine Planungsinformation: kein Einkommen, keine Wirkung auf Häufigkeit oder Preise, und ohne zugewiesenen, verfügbaren Konsul (nicht gesperrt) entfällt die Ankündigung — Corvan kommt trotzdem, nur ohne Vorwarnung. Der Marktbericht ist die spielwirksame Ausprägung des Konsul-Motivs „pflegt die Kontakte"; er ergänzt die kontextuelle Händler-Einschätzung aus §13 („Berater als Informationsebene").

**Orin ist nicht Teil dieses Kanals.** Orin (`corporate_rep`) ist eine Cantina-Figur mit eigener Rolle außerhalb von `BarService`/`MerchantService`. Sein ursprünglicher Auftrag (Vermittlung einer zweiten Harvester-Instanz) entfällt, weil es keinen zweiten Harvester gibt (§4c); seine künftige Rolle ist offen (ROADMAP T31).

**Cantina-Verhandlung (Risiko-Handel)** steht für beide Angebotsquellen zur Verfügung (unten).

Der Spieler entscheidet pro Angebot: annehmen oder ablehnen. **Annehmen kostet AP** aus dem gemeinsamen Pool (§13.1) — der Handel konkurriert damit direkt mit Bau und Kenntnissen um dieselbe Kapazität. Exakte Kosten: siehe `config/game.php`.

> ⚠️ **Kein Handelsvertrag:** Eine passive Cr/Sol-Einnahme allein durch Konsul und Cantina gibt es nicht — bedingungslos und ohne weiteres Zutun wäre sie stärker als die Hebel von Analytiker (Kenntnis-Boni) und Raumfahrer (Missions-Erträge) und verletzte die Paritäts-Anforderung (§4b). Corvans Alltagsgeschäft (Kauf/Verkauf) bleibt an die Cantina Lv1+ gekoppelt und erzeugt weiterhin kein passives Einkommen — Kauf- und Tauschangebote kosten Credits oder sind ressourcenneutral. Der Ersatz für den Pfad-C-Credits-Hebel ist der **Cantina-Begegnungspool** direkt unten — bewusst keine Dauereinnahme, sondern eine Zufallsbegegnung.

**Der Cantina-Begegnungspool:** Dritte Angebotsquelle neben Corvan und der anonymen Gästerotation, konzeptionell näher an den Begegnungen aus §9 (Sturm/Instabilität/Seuche) als an einem Handelsangebot — der Unterschied ist bewusst: Handel ist planbar, eine Begegnung ist es nicht. Zweck: den in §4b beschriebenen Pfad-C-Credits-Hebel bedienen, ohne eine Passiveinnahme zu schaffen, die bedingungslos, garantiert, dauerhaft und an den Konsul-Rang gekoppelt wäre.

Der Pool ist als **künftiger Container für die gesamte Cantina-Begegnungsvielfalt** gedacht (Credits sind nur die erste ausgearbeitete Kategorie), gegliedert in zwei Ebenen:

- **Generische Begegnungen** — kein Charakterbezug, rein systemisch erzählt. Höhere Wiederholungsrate erlaubt, da neutral.
- **Charaktergebundene Begegnungen** — jede Cantina-Figur (`config('characters')`) kann eigene, zu ihrer etablierten Rolle passende Begegnungen mitbringen. Niedrigere Wiederholungsrate je Figur (sonst nutzt sich die Persönlichkeit ab), Vielfalt entsteht durch die Rotation der Figuren selbst.

**Für den Credits-Hebel sind drei Ausgänge vorgesehen, gezogen aus einem gemeinsamen Ereignis-Slot (nicht drei unabhängige Spawn-Checks — sonst Häufungs-/Spam-Risiko mit Corvan- und Gästerotations-Terminen):**

- **Wette** — Einsatz (Ressourcen) gegen Credits-Auszahlung, chance-basiert, Totalverlust des Einsatzes bei Fehlschlag. Gebunden an **Zara** (Professional Gambler) — narrative Passung ohne Erklärungsbedarf.
- **Auktion / Ausschuss-Ankauf** — Einmalzahlung, Ressourcenüberschuss gegen Credits, sofort abgeschlossen. Gebunden an **Voss** (Scrap Dealer) — deckt sich mit seiner etablierten Rolle.
- **Kurzzeit-Kontrakt** — Credits/Sol für ein festes, kurzes Fenster, danach endet er automatisch wieder. Bewusst **ohne Charakterbindung** (generisch: „die Kolonie hat kurzfristig einen Abnehmer gefunden") — das ist der Ausgang, der einer dauerhaften Passiveinnahme am nächsten kommt, und eine Personenbindung würde ihn zur verlässlichen, an eine feste Quelle gekoppelten Einnahme machen. Skaliert über Bar-Level, nicht über Konsul-Rang.

> ⚠️ **BALANCE CONCERN:** Alle drei Ausgänge müssen über Bar-Level skalieren, nicht über Konsul-Rang — der Konsul bleibt bestenfalls leichter Preis-/Chancen-Modifikator, niemals Voraussetzung. Skaliert der Hebel stattdessen mit Konsul-Rang, entstünde eine Einnahme, die an den Konsul-Rang gekoppelt ist und damit eine bedingungslose, garantierte Passiveinnahme (nur eine Ebene tiefer: Cantina statt Konsul). Der Gesamt-Erwartungswert aus Pool + Organika-Verkauf (§4b) ist gegen die noch offenen Pfad-A/B-Hebel zu prüfen, sobald diese spezifiziert sind — aktuell keine Zielgröße, da beide Vergleichswerte selbst offen sind.

> **Leitplanke gegen Scope-Drift bei künftigen charaktergebundenen Begegnungen:** Nicht jede Cantina-Figur soll automatisch „ihre eigene Ressource" bekommen. §4b legt bewusst fest, dass Pfad C keinen verlässlichen Regolith-Wachstumshebel hat (Knappheitsordnung §3) — eine künftige Figur mit Regolith-Bezug (z.B. Fen, Veteran Prospector) darf diese Grenze nicht aufweichen; ihre Begegnungen müssen selten/opportunistisch bleiben, kein zweiter planbarer Regolith-Kanal neben Pfad A/B werden.

**Scope dieser Iteration:** Nur das mechanische Grundgerüst des Credits-Ausgangs (drei Ausprägungen, Charakterbindung, Frequenz-/Skalierungsregeln) ist hiermit festgelegt. Die vollständige Zuordnung aller übrigen Cantina-Figuren zu eigenen Begegnungen (inkl. Lore/Dialog, weitere Ressourcen-/Story-Kategorien, ggf. Missions-Trigger analog `project_bar_mission_trigger_idea`) ist ein eigenständiger Folge-Task, sinnvollerweise mit `content-writer`, sobald das Grundgerüst implementiert ist — kein Blocker für den Credits-Hebel.

**Charakter-Anliegen bei zufälligen Begegnungen:** Der Begegnungspool oben deckt den Credits-Ausgang ab (Wette/Auktion/Kontrakt). Als Erweiterung — nicht Ersatz — läuft ein zweiter, unabhängiger Begegnungstyp: Der Spieler trifft in der Cantina zufällig eine der etablierten Figuren, die ein konkretes Anliegen oder eine Bitte mitbringt. Der Spieler kann helfen — das kostet AP aus dem gemeinsamen Pool (§13.1), genau wie jede andere Cantina-Aktion — und erhält im Erfolgsfall eine Belohnung. Der Reiz liegt in der Bandbreite der Belohnung: Sie variiert bewusst breit und ist nicht auf eine Ressourcenachse festgelegt — denkbar sind Regolith, Organika oder Werkstoffe, Credits, ein AP-Bonus (nach demselben Prinzip wie der Barkeeper-Bonus, s.u.), ein Rabatt auf Baukosten oder ein zusätzliches, käufliches Schiff.

Das Anliegen läuft **nicht** im selben Ereignis-Slot wie der Credits-Begegnungspool oben, sondern als eigener, zweiter Roll direkt danach — mit niedrigerer Eintrittswahrscheinlichkeit als jener. Pro Sol feuert höchstens eine der beiden Cantina-Sonderbegegnungen: Ist der Credits-Begegnungspool bereits gezogen, entfällt der Anliegen-Roll für diesen Sol ganz (kein Stapeln, kein zweites Sonderereignis am selben Tag).

Anders als bei der Zuordnung der Credits-Ausgänge oben gilt hier bewusst **kein** Ausschluss für `story_hook`-Figuren: Aldra, Sorel und die namenlose Gestalt bringen ebenfalls ein Anliegen mit echter, mechanischer Belohnung mit (Baukosten-Rabatt bzw. Vertrauens-Ausschlag, ein Credits-Einsatz bei der namenlosen Gestalt) — ein bewusster Bruch der ursprünglich für diese Figuren geltenden Leitplanke „story_hook = nie mechanisch". Die Leitplanke galt für den Credits-Begegnungspool und bleibt dort gültig (§12, Ausgänge oben bleiben Zara/Voss/generisch vorbehalten); für den Anliegen-Slot ist sie explizit aufgehoben — jede Cantina-Figur, unabhängig von ihrem `game_role`, kann ein Anliegen mit Ressourcenwirkung tragen.

**Deva & Lenn — taktische Information, wiederholbar mit Varianz:** Die beiden Figuren mit `game_role: information` (`docs/characters/veteran.md`, `docs/characters/ai_researcher.md`) bringen — anders als der Credits-Ausgang oben — keine Ressourcen-Belohnung, sondern einen taktischen Vorteil im laufenden Run mit: Deva (Militärveteranin) auf Gefahrenabwehr, Lenn (KI-Forscher) auf Erkundung. Beide sind laut Charakterbogen `occasional` platziert, also für wiederholte Begegnungen über den Run hinweg gedacht — die Begegnung ist kein Einmal-Ereignis, sondern eine wiederkehrende, bei der Dialog und Ausgang bei jedem Antreffen variieren sollen, statt immer denselben Effekt abzuspulen.

Deva und Lenn bilden einen **dritten, eigenständigen Begegnungskanal** mit eigenem Spawn-Roll — sie laufen **nicht** im „höchstens eine Cantina-Sonderbegegnung pro Sol"-Gate des Credits-Begegnungspools und des Anliegen-Slots (oben) mit. Der Informations-Kanal kann also am selben Sol feuern wie eine der beiden anderen Cantina-Sonderbegegnungen — bewusst, weil er keine Ressourcen bewegt und daher kein Stapel-/Spam-Risiko für die Wirtschaft der Kolonie birgt.

Dafür bekommt jede der beiden Figuren einen kleinen **Ausgangs-Pool** (vier Einträge), aus dem bei jeder Begegnung einer gezogen wird — mit eigener Dialogzeile je Eintrag, unabhängig von der mechanischen Wirkung gepflegt (Dialogvarianten sind reine Content-Arbeit und beliebig erweiterbar, ohne die Balance zu berühren):

- **Zwei mechanische Ausgänge, je einmal pro Run nutzbar.** Bei Deva: ein einmaliger „Drill"-Reaktionspuffer, der die Konsequenz der nächsten eintretenden Instabilität oder Seuche (§9) abmildert, sowie ein einmaliger Kenntnis-Fortschrittsschub auf Geologie oder Gesundheit. Bei Lenn: ein einmaliger Navigation-Rabatt auf die nächste Erkundung sowie ein einmaliger Kenntnis-Fortschrittsschub auf Kartografie. Ist ein mechanischer Ausgang in diesem Run bereits gezogen (und beim Puffer: bereits verbraucht — nicht nur gebankt), fällt er aus dem Pool der jeweiligen Figur heraus und kann in diesem Run nicht erneut gezogen werden.
- **Zwei erzählerische Ausgänge, unbegrenzt wiederholbar.** Kein Ressourcen- oder AP-Effekt — Deva teilt eine Lageeinschätzung oder Feldgeschichte, Lenn eine Theorie-Randnotiz oder Bitte um seltene Bauteile. Diese beiden Einträge bleiben nach dem Verbrauch der mechanischen Ausgänge als Rest-Pool bestehen, sodass jede weitere Begegnung mit Deva oder Lenn im späteren Run weiterhin Sinn ergibt (Dialog statt Belohnung), aber wirkungslos für die Balance ist.

**Warum kein "einmal pro Run"-Deckel auf die ganze Begegnung mehr, sondern einer pro Ausgangs-Typ:** Ein einziger Verbrauchsdeckel auf die gesamte Figur würde entweder Wiederholbarkeit verhindern (Owner-Wunsch verletzt) oder — würde er stattdessen ganz aufgehoben — den Kenntnis-Fortschrittsschub beliebig oft wiederholbar machen und damit zu einem unkontrollierten AP-Kanal neben dem Analytiker werden lassen. Der Deckel pro Ausgangs-Typ löst beides: Die riskanten/wertvollen Ausgänge (Progress, Puffer) bleiben durch den Run-Deckel begrenzt, die Wiederholbarkeit selbst wird durch die erzählerischen Ausgänge bedient, die strukturell ungefährlich sind, weil sie keine Spielwerte verschieben.

**Auswahl bei der Begegnung:** Solange bei der jeweiligen Figur noch mindestens ein mechanischer Ausgang offen ist, wird aus allen noch verfügbaren Einträgen gezogen (mechanisch und erzählerisch gemischt, nicht garantiert mechanisch beim ersten Antreffen — das hielte die Überraschung). Sind beide mechanischen Ausgänge einer Figur bereits verbraucht, zieht jede weitere Begegnung nur noch aus den beiden erzählerischen Einträgen. Innerhalb eines Ausgangs-Typs rotieren die Dialogvarianten, statt bei jedem Ziehen zufällig zu wiederholen — vermeidet den unmittelbaren Eindruck von Wiederholung.

> **Scope dieser Konzeptrunde:** Nur die Pool-Struktur (vier Ausgänge, Run-Deckel pro Typ, Auswahlregel) ist hier festgelegt. Exakte Formelwerte (Puffer-Stärke, Fortschrittsschub-Höhe, Navigation-Rabatt-Höhe) folgen `config/game.php`/`config/knowledge.php` bei der Implementierung, nicht dem GDD (ADR 0004). Die konkreten Dialogvarianten je Ausgang sind Content-Arbeit (`content-writer`, `lang/de/*.php`).

**Charakter-Kodex — Meta-Ebene über den ganzen Cantina-Cast:** Zusätzlich zu den spielwirksamen Begegnungsausgängen oben bekommt **jede** der fünfzehn Cantina-Figuren (`config('characters')`, alle `game_role`-Typen) eine kleine Reihe rein erzählerischer Lore-Einträge — Hintergrundgeschichte, Motive, Andeutungen jenseits dessen, was der Charakterbogen ohnehin zeigt. Bewusst **ohne jede Spielmechanik-Wirkung**: kein Ressourcen-, AP-, Kenntnis- oder Preis-Effekt, nur Text. Der Kodex ist außerdem **run-übergreifend persistent** (analog zum `user_preferences`-Muster) — einmal freigeschaltete Einträge bleiben über das Ende eines Runs und den Start eines neuen hinweg erhalten, weil er den Spieler-Account und nicht die einzelne Kolonie beschreibt.

Damit der Kodex keine eigene, parallele Trigger-Mechanik pro Figur braucht, schaltet er sich **über die ohnehin je `game_role` bestehende Interaktion** frei — dieselbe Begegnung, die die Figur mechanisch definiert, zählt zugleich als Kodex-Trigger, gestaffelt: das n-te qualifizierende Zusammentreffen mit einer Figur schaltet ihren n-ten Kodex-Eintrag frei (bis der Pool der Figur erschöpft ist), unabhängig vom Run, in dem es passiert:

- **information (Deva, Lenn):** Trigger ist jede Begegnung aus dem Ausgangs-Pool oben — mechanisch oder erzählerisch, beide zählen für den Kodex-Fortschritt.
- **bar_trade (Dax, Vesper, Sarka, Maret, Fen, Juno):** Trigger ist ein abgeschlossener Tausch mit der personalisierten Flavor-Zeile der jeweiligen Figur (`bar_trade_flavor_*`) — die Mechanik existiert bereits, der Kodex hängt sich nur zusätzlich daran.
- **dedicated (Orin, Zara, Voss):** Trigger ist eine abgeschlossene Interaktion mit der jeweils eigenen Mechanik (Wette, Auktion; Orins Rolle ist offen) — bereits vorhanden, kein neuer Auslöser nötig.
- **story_hook (Aldra, Sorel, die namenlose Gestalt):** Trigger ist eine abgeschlossene Interaktion aus dem Anliegen-Slot (s.o.) — der Begegnungspool selbst bleibt für diese drei Figuren flavor-only, aber seit dem Anliegen-Slot haben auch sie eine mechanische Interaktion mit echter Ressourcenwirkung, die als Kodex-Trigger dient.
- **permanent (Tomas):** Kein Zufalls-Spawn, den man "antreffen" könnte, also kein Begegnungs-Trigger verfügbar — sein erster Kodex-Eintrag schaltet sich stattdessen am Baumeilenstein frei (Cantina erreicht Ausbaustufe I), spätere Einträge an höheren Cantina-Ausbaustufen, passend zu seiner Rolle als feste Einrichtung statt Zufallsfigur.

Der Kodex ist damit reiner Zusatzwert für Spieler, die an der Welt und ihren Figuren interessiert sind, ohne die Balance der jeweiligen `game_role`-Mechanik zu berühren oder zu verkomplizieren — und ein zweiter, unabhängiger Hebel für Dialogvarianz neben der Ausgangs-Pool-Rotation bei Deva/Lenn.

> Die Kodex-Einträge selbst (Anzahl je Figur, Texte) sind Content-Arbeit (`content-writer`, `lang/de/*.php`); die Persistenz läuft run-übergreifend über den Spieler-Account, nicht über die einzelne Kolonie.

**Barkeeper (Dauer-Charakter):** Anders als Corvan und die übrigen Cantina-Figuren hat der Barkeeper keinen Zufallsspawn und kein Verfügbarkeitsfenster — er ist immer in der Cantina anwesend, sobald sie gebaut ist. Statt über Angebote wirkt er über wiederholten Austausch mit dem Spieler: Der Spieler wählt eine laufende Kenntnis-Investition aus, und Tomas injiziert dieser Investition direkt einen zusätzlichen AP-Betrag — ähnlich einem stillen Mitspieler, der ergänzend zum Analytiker in dieselbe Kenntnis einzahlt, ohne selbst eine Kenntnis freizuschalten oder den gemeinsamen AP-Pool zu belasten. Die Interaktion ist auf einmal pro Sol begrenzt (Cooldown). Der Bonus-Betrag wächst mit der Häufigkeit, mit der der Spieler über den gesamten Run hinweg mit Tomas interagiert hat — gestaffelt über vier Stufen, bis zu einem harten Deckel, ab dem weitere Interaktionen keinen zusätzlichen Bonus mehr bringen (aber weiterhin den vollen Bonus der höchsten Stufe liefern). Damit ist der Barkeeper kein weiterer Handelskanal, sondern ein eigenständiger, dauerhaft verfügbarer AP-Hebel für den Kenntnis-Pfad, der sich durch wiederholte Nutzung im Lauf des Runs verstärkt. Als späterer UI-Ausblick: Investierte AP pro Kenntnis sollen im Frontend sichtbar aufschlüsseln, welcher Anteil vom Barkeeper stammt (eigenes Symbol/Badge neben der Balken-/Zahlenanzeige), damit sein Beitrag vom Analytiker-Beitrag unterscheidbar bleibt.

**Bar-Level-Progression:**

Höhere Bar-Level erhöhen die Angebots-Gültigkeit (Dauer) und die maximale Anzahl gleichzeitig aktiver Angebote. Details: siehe `config/buildings.php`.

Zusätzlich zum Bar-Level selbst erhöht die Kenntnis **Handel** (`trade`) ab einem bestimmten Level die Zahl gleichzeitig aktiver Angebots-Slots weiter — der Effekt wirkt direkt aus dem Kenntnis-Level, ohne dass ein Berater zugewiesen sein muss (§10). Details: `config/knowledge.php → trade.bar_offer_boost_per_lv`, `docs/game-reference.md#kenntnisse-7-levelup-kosten-effekte`.

**Konsul (advisor_trader) — Rang-Effekte:**

Der Konsul trägt zum gemeinsamen AP-Pool bei (Beitrag steigt mit Rang), verbessert die Gäste-Häufigkeit und Preiskonditionen, schaltet die Cantina-Verhandlung frei (Chance und Aufschlag steigen mit Rang) und erhöht die Wahrscheinlichkeit von Werkstoffen in Credits→Ressource-Angeboten bei höheren Rängen. Exakte Werte pro Rang: siehe `config/advisors.php` und `config/game.php → bar`.

**Kein AP-Rabatt — die Domäne „Wirtschaft" besteht aus Handlungen.** Der Domänen-Beitrag des Konsuls (§13.1) ist bewusst nicht als Prozentrabatt auf AP-Kosten von Handelsgeschäften angelegt: Boni senken nur Projektkosten (§13.3), und Handlungs-AP sind so klein, dass ein Prozentrabatt an der Rundung scheitert oder — über feste Abzüge — nur verdecktes „mehr AP" wäre. Der Konsul-Beitrag liegt auf der *Qualität* der Handlungen: bessere Konditionen, Verhandlungschance, Planungsvorsprung (Marktbericht), günstigere Schiffsverhandlung.

**Rangskalierte Schiffsverhandlung (A13):** Die Konsul-Verhandlung beim Schiffskauf (§8b) tauscht investierte AP gegen einen Credits-Nachlass. Der Nachlass je AP wächst mit dem Konsul-Rang; ohne zugewiesenen, verfügbaren Konsul gibt es keine Verhandlung. Die AP-Menge bleibt begrenzt, der Kauf bleibt risikofrei und wirkt nur bei aktiver Nutzung — kein passiver Effekt.

> **Idee (nicht beschlossen): „Wunschangebot".** Der Konsul „aktiviert seine Kontakte": Der Spieler nennt eine gewünschte Ressource, und für den nächsten Sol erscheint ein Gästeangebot, das sie als Get-Seite trägt — gegen AP und mit Rang-abhängigem Cooldown. Würde die Tauschrichtung nach Bedarf steuerbar machen (§13.5 „Tauschrichtung nach Bestand"). Offen: Werkstoffe als Wunsch erst auf höchstem Rang, um die Knappheit (§3) zu schützen. Nicht im Umfang von A13.

**Werkstoffe-Bias bei höheren Rängen:** Der Experten-Konsul hat Marktbeziehungen — bei Credits→Ressource-Angeboten erscheinen seltene Ressourcen häufiger. Das gibt dem höheren Rang einen konkreten wirtschaftlichen Vorteil in der knappsten Ressource des Spiels (§3 Werkstoffe nicht lokal produzierbar).

**Handelsvorteil (A13):**

Leitgedanke ist **Verständlichkeit**: Der Spieler muss im Angebotsdialog nachvollziehen können, *warum* ein Angebot so aussieht, und jede angezeigte Zahl muss sich im Kopf nachrechnen lassen. Alle *passiven* Konditionsquellen laufen deshalb je Handelskanal in **einem einzigen, sichtbaren Prozentwert** zusammen, dem Handelsvorteil: der Konsul-Rang (nur Cantina-Kanal), der Kanal-Rabatt des Handelspostens (§4) und der Preisbonus der `trade`-Kenntnis. Heute stacken diese Quellen teils multiplikativ und an verschiedenen Stellen (Rang schon bei der Angebots-Erzeugung eingebacken, Rest beim Annehmen), sodass keine Anzeige die Herkunft einer Zahl erklären könnte. Künftig gilt:

- **Addiert, nie multipliziert, nichts eingebacken.** Angebote werden mit ihren Basiskonditionen (fairer Tausch nach Marktwert) erzeugt; der Handelsvorteil wird erst beim Anzeigen und Abschluss angewandt. Nur so kann der Dialog Basis und Quellen einzeln zeigen.
- **Bei Ware: mehr Menge.** Bei Cantina-Angeboten (Tausch, Corvans Kaufangebote) gibt der Spieler dasselbe wie im Basisangebot und bekommt um den Handelsvorteil mehr Ware. Kein Preisnachlass, keine Umrechnung — „+X %" heißt genau „X % mehr Ware".
- **Bei Credits-Käufen zu Festpreis: Preisnachlass.** Corvans Sonderinventar und der Nexus-Direktimport werden um genau den angezeigten Prozentsatz günstiger. Diese Kanäle haben keine Mengenachse.
- **Nexus ohne Konsul.** Der Nexus-Kanal hat nur die Quellen Handelsposten und `trade` (F5: keine Konsul-Hebel am Direktimport).
- **Kein Deckel als Spielregel.** Die Quellen sind so bemessen, dass die Summe in jedem Kanal von selbst in einem Rahmen bleibt, der den Nexus als teureren Fallback erhält. Ein Deckel existiert höchstens als stille Leitplanke in der Config für künftige Quellen und wird dem Spieler nie erklärt — sonst müsste der Dialog „Deckel erreicht" und eine von der Summe abweichende Zahl begründen, für einen Fall, der mit den heutigen Quellen kaum eintritt.
- **Verkaufslose sind Festpreis.** Credits-Erlöse (Corvans Organika-Lose, §4b) erhalten weder Handelsvorteil noch Verhandlung. Sonst würden passive Infrastruktur und Konsul zu einer verlässlichen Credits-Einnahme, und ein Kauf-und-Rückverkauf-Kreislauf entstünde — genau das, was A22 ausgeschlossen hat. Merkregel für den Spieler: *Der Handelsvorteil verbessert, was du an Ware bekommst — Verkaufs-Credits sind fix.*
- **Verhandeln ist ein weiterer Aufschlag oben drauf,** nach denselben Anzeigeregeln (eigene Zeile, „+X %" heißt „X % mehr Ware"). Der Handelsposten gilt auch bei verhandelten Angeboten.

**Was der Angebotsdialog zeigt:** (1) das Basisangebot; (2) den Handelsvorteil mit jeder Quelle in einer eigenen Zeile — nur Quellen mit Wirkung, dazu ein dezenter Hinweis, was ein fehlender Konsul oder Handelsposten brächte; (3) das Ergebnis als konkrete Menge samt „Plus gegenüber Basis"; (4) bei Verhandeln *beide* Ausgänge in Zahlen — Erfolgschance samt ihren Quellen, Menge bei Erfolg, und ein klarer Satz, was bei Misserfolg passiert (kein Handel, die Give-Ressourcen bleiben beim Spieler, das Angebot verfällt, die AP sind verbraucht); (5) die AP-Kosten an beiden Buttons; (6) bei Verkaufslosen den Hinweis auf den Festpreis. Zusätzlich fasst die Kopfzeile der Cantina den aktuellen Handelsvorteil mit seinen Quellen zusammen. Bar- und Händlerlisten zeigen den tatsächlich fälligen Betrag, nicht mehr die unrabattierten Basiswerte.

**Cantina-Verhandlung (Risiko-Handel):**

Zusätzlich zu **Annehmen** (feste Konditionen, garantiert, `ap_cost_accept`) gibt es pro Bar-Angebot einen zweiten Button **Verhandeln** — sichtbar, sobald der Kolonie ein Konsul zugewiesen **und** verfügbar ist (nicht gesperrt, `unavailable_until_tick` ist `null` — dieselbe Prüfung wie bei der Angebots-Generierung, siehe `BarService::generateOffersForColony`). Jeder Rang genügt, auch Rang 1 (Junior) — ob sich der Klick lohnt, hängt an der Rechnung unten.

> **Nicht zu verwechseln** mit der "Konsul-Verhandlung" beim Schiffskauf (§8b, Hangar-Screen): dort ist der niedrigere Preis garantiert, hier nicht. Diese Mechanik heißt bewusst anders.

**Ablauf — zwei Schritte:** Verhandeln führt das Geschäft nicht sofort aus, sondern verbessert bei Erfolg nur die Konditionen des Angebots — der Spieler sieht das Ergebnis und bestätigt danach explizit mit **Annehmen**.

1. Verfügbarkeits- und Ressourcen-Check wie bei Annehmen (Give-Seite muss gedeckt sein — sonst Fehler `bar_offer_insufficient_resources`, kein Würfeln auf ein Geschäft, das ohnehin nicht zustande kommen könnte). Ein bereits verhandeltes Angebot kann nicht erneut verhandelt werden.
2. AP-Kosten werden abgebucht (`ap_cost_negotiate`) — **genauso hoch wie Annehmen**, unabhängig vom Ausgang. Verhandeln kostet also nichts extra: Die Wahl ist reine Risiko/Ertrag-Abwägung („sicher oder riskant"), kein AP-Rechnen.
3. Einmaliger Erfolgs-Wurf, Konsul-Rang-abhängig (`negotiate_success_chance`), zuzüglich des Chance-Bonus der `trade`-Kenntnis (§10) — auch der beste Konsul garantiert nichts. Der Dialog zeigt die Chance samt ihren Quellen.
   - **Erfolg:** Das Angebot wird als verhandelt markiert; seine Konditionen sind jetzt Basisangebot plus Handelsvorteil plus Verhandlungs-Aufschlag (`negotiate_bonus`, s.o.) — als eigene Zeile im Dialog, nicht in die Mengen des Angebots eingefroren. Der Handel selbst führt sich **noch nicht** aus — der Verhandeln-Button wird gesperrt, der Annehmen-Button bleibt aktiv und zeigt jetzt 0 AP (die Kosten wurden bereits mit der Verhandlung bezahlt). Erst ein Klick auf Annehmen überträgt die Ressourcen.
   - **Fehlschlag:** Kein Handel — die Give-Ressourcen bleiben beim Spieler. Das Angebot ist **sofort und vollständig verloren** (gelöscht/verfallen) — kein zweiter Versuch, auch kein nachträgliches "Annehmen" zu den alten Konditionen. Die AP sind verbraucht. Verloren geht damit der Handelsvorteil *und* die Ware, die man haben wollte.
   - **Nicht verhandelbar:** Verkaufslose mit Credits-Erlös (Festpreis, s.o.).
4. **Kein Trust-Malus.** Es gibt auch kein Handels-Ereignis, das Handel sperrt — eine fehlgeschlagene Verhandlung soll bestraft, aber nicht zusätzlich über Vertrauen abgestraft werden, sonst wird der Button nie benutzt.

**Wann sich Verhandeln lohnt — und wann nicht (Designziel A13):** Verhandeln soll eine echte Entscheidung sein, kein Dauer-Klick und kein toter Button. Weil beide Wege gleich viele AP kosten, gibt es nichts zu verrechnen; verglichen werden nur die Ausgänge: sicher den Handelsvorteil, oder mit einer Chance den größeren Vorteil und sonst gar nichts. Ob das im Schnitt besser ist, hängt an drei Dingen:

- **Der passive Handelsvorteil.** Je mehr davon vorhanden ist (Konsul-Rang, Handelsposten, `trade`), desto weniger bringt der riskante Aufschlag, und desto mehr kostet der Fehlschlag. Verhandeln ist die *aktive Alternative* zur passiven Infrastruktur: wer wenig davon hat, verhandelt gern; wer viel hat, nimmt lieber sicher an.
- **Chance und Aufschlag.** Chance steigt mit dem Konsul-Rang und der `trade`-Kenntnis; ein hoher Rang macht das Verhandeln sicherer, aber wegen des größeren passiven Vorteils nicht automatisch lohnender.
- **Bedarf des Spielers.** Ein Fehlschlag kostet auch die Ware, die man haben wollte. Wer die Ressource dringend braucht, nimmt sicher an; wer nur eine günstige Gelegenheit nutzen will, riskiert es. Dieser Bedarf ist die eigentliche situative Größe und liegt beim Spieler, nicht in der Config. Die Angebotsgröße entscheidet nicht darüber, *ob* Verhandeln besser ist, nur wie groß die Schwankung ausfällt.

Der Erwartungsvorsprung soll bewusst klein bleiben — in vielen Lagen ist der Unterschied nur wenige Prozent —, sodass die Wahl vor allem eine Frage der Risikoneigung ist. Fällt Verhandeln im Playtest dennoch zu dominant oder zu selten aus, ist die sanfteste Stellschraube die Erfolgschance; ein AP-Aufpreis für Verhandeln käme erst danach in Frage, weil er AP-Rechnerei in den Dialog trägt.

Exakte Chancen, Aufschläge und AP-Kosten: siehe `config/game.php → bar` (`negotiate_success_chance` / `negotiate_bonus` / `ap_cost_*`), `trade`-Chance-Bonus: `config/knowledge.php`.

> ⚠️ BALANCE CONCERN: Verhandeln muss je Konstellation mal besser, mal schlechter als Annehmen sein — auch für den höchsten Konsul-Rang. Zu hohe Chance oder zu großer Aufschlag macht es dominant, zu geringe macht es zum toten Button; beides ist über Rang, Handelsvorteil und Bedarf gegeneinander abzugleichen. Nach dem nächsten Bot-Batch kalibrieren (mindestens 6–10 Seeds) — der Bot kennt keinen Bedarf und keine Risikoneigung, sein Verhandlungsverhalten ist nur ein grober Indikator.

> **Offen (nicht entschieden):** Ob der Totalverlust des Angebots bei Fehlschlag beibehalten oder abgeschwächt werden soll (etwa Angebot bleibt, aber mit Einbuße). Default-Annahme: beibehalten. Bei hohem passivem Handelsvorteil bleibt Verhandeln wegen dieser Regel eher die Ausnahme.

---

> **Kein Nexus-Handelsschiff-Kanal.** Die Sicherheitsnetz-Funktion — garantierte Lieferung gegen Credits, auch für Ressourcen, die nicht sofort verfügbar sind — übernimmt die erweiterte Direktimport-Variante der Uplink-Station (§3, §4).

### Kanal 2: Reisender Händler (selten, hochwertig)

Implementiert über `MerchantService` + `config/game.php → merchant`; Spawn-Check in `GameTick` (Schritt 14).

Ein reisender Händler erscheint gelegentlich bei der Kolonie für eine begrenzte Anzahl Sole. Er bietet seltene Waren an — keine Standardressourcen, sondern Shortcuts und Chancen die im normalen Spielverlauf nicht erreichbar sind.

**Erscheinungsfrequenz:** Erscheint gelegentlich nach einer Startup-Phase (Kolonie soll sich erst etablieren). Regelmäßige Besuche danach, aber unregelmäßig genug um Roguelike-Druck zu erzeugen (kein garantiertes Angebot). Der Rhythmus ist vom Konsul unabhängig; der Konsul kündigt die Besuche nur an (Marktbericht, Kanal 1). Details: `config/game.php → merchant`.

**Inventar:** 3–4 Items pro Besuch (Mobile-optimiert, kein Scrollen nötig).

**Preisstruktur:** Alles in Credits. Kein Tauschhandel mit Corvan.

**Schwierigkeitsskalierung:** Höhere Preise auf schwierigeren Runs — nicht schlechteres Sortiment (das wäre frustrierend).

**Item-Kategorien:**

| Kategorie | Beschreibung | Seltenheit |
|-----------|-------------|-----------|
| **AP-Paket (flexibel)** | Sofortiger AP-Schub eines Typs (z.B. +20 Construction-AP) — Spieler wählt beim Kauf wofür er sie ausgibt. Teurer als gezieltes Paket | gelegentlich |
| **AP-Paket (gezielt)** | AP-Schub für ein konkretes Gebäude oder eine Kenntnis — günstiger, aber Ziel ist fixiert | gelegentlich |
| **Schiff** | Gebrauchtes Schiff mit Eigenname — ersetzt ein bestehendes Schiff (Hangar bleibt konstant). Phase 4+: besondere Eigenschaften denkbar | selten |
| **Information** | Alle noch unerkundeten Tiles der Exploration Zone sofort aufgedeckt (`colony_tiles.is_explored`) | selten |
| **Einmal-Item** | Reparatur-Kit, Vertrauens-Schub, Credits-Notfallkredit | häufig |

---

### Handelbare Ressourcen

| Ressource | Handelbar | Typische Richtung |
|-----------|-----------|-------------------|
| Regolith (Rg) | Ja | Verkauf (Überschuss) |
| Organika (Or) | Ja | Kauf/Verkauf je nach Spezialisierung |
| Werkstoffe (Co) | Ja | Kauf (nicht produzierbar) |
| Credits (Cr) | Nein | Zahlungsmittel |
| Kolonisten (KOL) | Nein | Systemwert |
| Vertrauen (V) | Nein | Systemwert |

---

### Kenntnisse-Handel

Kenntnisse sind personengebundenes Wissen — nicht handelbar und nicht übertragbar.

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
| Bau | Gebäude errichten und ausbauen, reparieren | Baumeister |
| Wissen | Kenntnisse vorantreiben | Analytiker |
| Navigation | Tile-Erkundung, Außenmissions-Dispatch | Raumfahrer |
| Wirtschaft | Handelsangebote, Marktgeschäfte | Konsul |

**Berater erhöhen den gemeinsamen Pool** und tragen zusätzlich einen **Beitrag in ihrer Domäne** — wo die Domäne aus Projekten besteht, als Kostenrabatt (siehe 13.3), wo sie aus Handlungen besteht, als Qualität der Handlungen. Der Konsul ist der zweite Fall: Konditionen, Verhandlungschance und Planungsvorsprung statt AP-Rabatt (§12). Berater bleiben damit klar unterscheidbar, ohne den Pool zu zersplittern.

**Keine Bodengarantie**. Es wird **kein** Mindestanteil je Domäne reserviert; die Allokation ist vollständig frei.

**Begründung:** AP sind ein **Fluss, kein Bestand** — Locks verfallen zum nächsten Sol, der Pool erneuert sich täglich vollständig. Eine Fehlallokation wirkt damit per Konstruktion maximal einen Sol. Um sich dauerhaft auszusperren, bräuchte es eine permanente Bindung, die es nicht gibt. Die vier plausibelsten Deadlock-Kandidaten wurden geprüft und alle entschärft:

| Kandidat | Deadlock? | Warum nicht |
|---|---|---|
| Kein Regolith → Reparatur gesperrt → Harvester verfällt → noch weniger Regolith | Nein | CC und Harvester sind regolithfrei reparierbar (AP-only, Bootstrap-Ausnahme §4). Die Regolith-Quelle bleibt immer erreichbar. |
| AP in ein Gebäude investiert, dessen Regolith fehlt | Nein | Regolith wird erst beim Abschluss abgezogen (§4). Investierte AP bleiben auf `ap_spend` liegen und werden gültig, sobald Regolith da ist — kein Verlust. |
| Credits auf 0, kein Berater bezahlbar | Nein | Upkeep wird auf ≥ 0 geklemmt, der Verlust läuft über `nexus_debt`. Langsames Ausbluten über viele Sole, kein Lock. |
| Über dem Wohnraum → beschleunigter Decay → Leveldown-Spirale | Nein | Unbesetzte Arbeitsplätze beschleunigen den Verfall nicht (§7), Wohnraumverlust kostet keine weiteren Ausbaustufen, nur Besetzung (§6). Wohnraum-Gebäude sind vom Bau-Gate ausgenommen — der Weg heraus bleibt offen. Siehe unten. |

Hinzu kommt: die **Einstiegskosten jeder Domäne sind winzig** (1 AP erkunden, 1 AP in eine Baustelle, 2 AP ein Angebot annehmen). Es gibt keine Domäne, in die man nicht mit einem einzigen Restpunkt zurückfindet.

Eine Untergrenze würde genau den Allokationsschmerz entfernen, der der Zweck des gemeinsamen Pools ist — und ein Problem lösen, das die Fluss-Natur des Pools bereits ausschließt.

> **Die reale Gefahr ist die fehlende Obergrenze, nicht die fehlende Untergrenze.** Ein Spieler, der jeden Sol den ganzen Pool in Reparaturen kippt, verliert den Run langsam, ohne es zu merken. Dagegen hilft keine Bodengarantie — nur die Instandhaltungsanzeige im Dashboard (13.4). Sie ist der Ersatz für die Bodengarantie und darf deshalb nicht als Komfort-Feature wegpriorisiert werden.

> **Wohnraumverlust belastet den AP-Pool nicht.** Die Instandhaltung aller Gebäude steigt dadurch nicht (§7). Wohnraumverlust wirkt ausschließlich über einen **sichtbaren, sofortigen Zustand**: unbesetzte Arbeitsplätze mit anteilig weniger Rohstoffproduktion und gesperrtem Neubau, siehe §6 „Wohnraumverlust — Konsequenzen". Der Gegenzug ist Wohnraum zurückholen. Keine dieser Folgen bindet dauerhaft AP, der Instandhaltungsanteil im Dashboard (13.4) bleibt davon unberührt.

---

### 13.2 Ratenmodell: Handlungen und Projekte

AP werden auf **zwei verschiedene Arten** ausgegeben. Beide ziehen aus demselben Pool.

**Handlungen — sofort, einmalig.** Missionen, Events, Tile-Erkundung, Handelsgeschäfte. Kosten AP im Moment der Auslösung, das Ergebnis tritt sofort (oder nach fester Laufzeit) ein.

**Projekte — investiert über mehrere Sole.** Gebäude und Kenntnisse haben Gesamtkosten in AP, die der Spieler über mehrere Sole hinweg einzahlt. Ein Gebäude ist fertig, wenn die Summe der investierten AP die Projektkosten erreicht. Die Mechanik gilt für Gebäude und Kenntnisse gleichermaßen (`AbstractTechnologyService`).

Damit ist AP nicht primär ein Tagesbudget, sondern eine **Arbeitsrate**. Knappheit entsteht nicht daraus, dass die Punkte ausgehen, sondern daraus, dass ein Run **auf 100 Sole begrenzt** ist (§18.4): Ein Gebäude, das 5 Sole Bauzeit bindet, kostet 5 % des Runs.

**Parallelbau ist erlaubt und ausdrücklich gewollt.** Der Spieler darf beliebig viele Projekte gleichzeitig laufen lassen und pro Sol frei entscheiden, welche er füttert. Die Rate verteilt sich dann entsprechend — mehr Baustellen heißt nicht mehr Durchsatz, sondern längere Einzellaufzeiten (der Pool bleibt die Gesamtgrenze; wie viel ein einzelnes Ziel pro Sol aufnimmt, begrenzt zusätzlich der AP-Deckel, siehe unten). Das ist eine **bewusste Planungsentscheidung** des Spielers, kein Fehler: Er kann Fertigstellungen absichtlich auf denselben Sol legen. (Ob es dafür einen konkreten Spielvorteil geben soll — z. B. gebündelte Vertrauens- oder Milestone-Effekte — ist offen und soll sich aus Playtests ergeben.)

**AP-Deckel pro Ziel und Sol (aktiv).** Der Pool bleibt ein einziger gemeinsamer Pool (13.1). Zusätzlich kann jedes **Ziel** eines Projekts — eine einzelne Gebäude-Instanz oder eine einzelne Kenntnis — pro Sol nur eine begrenzte Menge AP aufnehmen. Die Obergrenze setzt sich zusammen aus einem **Grundwert** plus dem **Beitrag des passenden Beraters je Rang**: für Gebäude der Baumeister; der Deckel je Kenntnis im Labor kennt bisher nur den Grundwert. Der Rang-Beitrag entspricht dem Beitrag, den der Berater ohnehin zum Pool leistet; er wird nicht separat erfunden. Für Raumfahrer und Konsul ist derselbe Mechanismus vorbereitet, aber ausgeschaltet: Ihre Handlungen sind bereits durch Verfügbarkeit begrenzt (siehe unten) und brauchen keine zweite Bremse.

Der Deckel erfüllt zwei Aufgaben. Erstens macht er es unmöglich, ein Gebäude oder mehrere Ausbaustufen in einem einzigen Sol durchzuziehen, selbst bei vollem Pool — Ausbau braucht Sole, und das gibt dem Aufbau Rhythmus. Zweitens ist er die **Zeit-Bremse für Phase 1**, die das Tempo drosselt, ohne am Regolith-Einkommen zu rühren: Eine Bremse über höhere Regolith-Kosten oder späteres Einkommen würde den Harvester-Anlauf verzögern und damit genau die Spieler treffen, die ohnehin knapp sind (§13.7 „Phase-1-Pacing"). Der Deckel **wächst nicht mit der Zielstufe** (ausdrücklich nicht): Teurere Stufen brauchen dadurch mehr Sole bei gleichem Deckel. Belohnungs-AP (Daten-Sweep, Cantina-Bonus, Gutscheine) liegen außerhalb des Deckels; sie sind Geschenke und sollen nicht gebremst werden. **Reparatur ist vom Deckel ausgenommen**: Sie erzeugt keinen Fortschritt, sondern stellt nur den Zustand wieder her, ist das Sicherheitsventil gegen den Verfall und wird bereits durch Regolith und Einsatzpunkte begrenzt; ein Notfall soll nie am Tageslimit scheitern. Kenntnisse dürfen die Deckel künftig beeinflussen; welche und wie stark, ist offen und wird nach Messung entschieden. Die Oberfläche zeigt den Deckel im bestehenden, segmentierten AP-Balken des Ausbau-Buttons, ohne zusätzlichen Erklärtext. Werte: siehe `docs/game-reference.md`.

**Gelegenheiten sind durch Verfügbarkeit begrenzt, Projekte durch AP.** Das ist die tragende Regel des Hybrids. Sofortige Handlungen sind für Spieler unter Unsicherheit systematisch attraktiver als aufgeschobene Investitionen — wenn Missionen und Events unbegrenzt AP aufnehmen könnten, würde der Spieler den Aufbau aushungern, ohne es zu merken. Deshalb gilt: Es gibt pro Sol nur eine begrenzte Zahl verfügbarer Missionen und Events. AP-Überschuss hat dann keinen anderen Abfluss als Bauen und Forschen — ohne dass eine bevormundende Regel („max. X % für Missionen") nötig wird.

**Der Late-Game-Kipppunkt ist gewollt.** Gegen Run-Ende lohnt sich kein langfristiges Projekt mehr: AP in ein 5-Sol-Gebäude bei Sol 92 ist verlorene Kapazität. Die letzten ~15 Sole verschieben sich dadurch von Aufbau auf Ausführung. Das ist ein designter Phasenwechsel und gibt dem Run-Ende Charakter — Voraussetzung ist, dass der Spieler ihn kommen sieht (siehe 13.4).

---

### 13.3 Boni: additiv, nie multiplikativ

Boni senken die **AP-Kosten von Projekten** und verkürzen damit die Bauzeit in Solen. Das ist die primäre Progressionsachse des Systems: Ein Gebäude, das früh 5 Sole bindet, ist im Mid- und Late-Game in 2 Solen fertig. Beschleunigung wird als Zeitgewinn spürbar, nicht als größere Zahl in einem Balken.

**Alle Kostenreduktionen wirken additiv.** Berater-Rang, Kenntnis-Level und Koloniereife addieren ihre Prozentwerte, bevor sie einmal auf die Projektkosten angewandt werden. Multiplikative Verkettung ist ausgeschlossen: Sie würde im Late-Game überschießen und Projekte praktisch sofort abschließen, was den Kipppunkt aus 13.2 zerstört.

**Bonusquellen:**

| Quelle | Status | Wo |
|---|---|---|
| Kenntnis `construction` (Bau-Projekt-Rabatt-Pool) | implementiert (`trade` gehört nicht dazu) | `ProjectBonusService::buildingApDiscountPercent()`, `config/knowledge.php → construction.ap_cost_reduction_per_lv` |
| Cantina-Gutschein (Charakter-Anliegen, §12) | implementiert | `ProjectBonusService::activeBuildingDiscountVoucherPercent()` |
| Analytik-Labor Lv4/5 — Domänen-Effizienzbonus „Wissen" | implementiert | `config/buildings.php → sciencelab.knowledge_ap_cost_reduction_per_lv` |
| Berater-Rang | **geplant** (ROADMAP Stufe 3) | vorgesehen: `config/game.php → project_cost_bonus` |
| Koloniereife (CC-Level) | **geplant** (ROADMAP Stufe 3) | dito |

**Bau-Projekt-Rabatt-Pool:** Bau ← `construction`. Die Kurve ist glockenförmig über die fünf Level (Peak Lv2–4) und wirkt additiv auf **alle** Gebäude-Levelups inklusive Kommandozentrale — nicht nach Projekttyp getrennt, weil nur Bau-Projekte existieren. Weitere additive Quellen im Pool sind Cantina-Gutscheine aus Charakter-Anliegen (§12). `cartography` gehört nicht zu diesem Pool: die Kenntnis senkt eigenständig die Navigation-AP-Kosten von Tile-Erkundung (`ColonyTileService::exploreTile()`) und Hangar-Missions-Reisekosten (`HangarService::dispatchShip()`), `config('knowledge.cartography.nav_ap_reduction_per_lv')`. `trade` gehört ebenfalls nicht dazu : Handelsgeschick beschleunigt keinen Gebäudeausbau, die Kenntnis wirkt auf Handelskonditionen (§12). Der Pool ist dadurch schlank; die primäre Progressionsachse „Bau-Beschleunigung als Zeitgewinn" ruht künftig stärker auf den noch geplanten Quellen Berater-Rang und Koloniereife (Tabelle oben, Stufe 3).

**Analytik-Labor Lv4/5:** Gibt dem Laborausbau über die reinen Kenntnis-Gates (Lv1–3) hinaus einen eigenen Effekt — senkt die AP-Kosten für Kenntnis-Levelups, additiv und unabhängig vom Gebäude-Rabatt-Pool. Rührt an nichts, was pro Run gezogen wird (§10) — reine Effizienzsteigerung auf bereits freigeschaltete Kenntnisse.

Ein **Mindest-Kostenanteil** (`project_min_cost_factor`) verhindert, dass Projekte auf null fallen — das ist eine Leitplanke für spätere Bonusquellen (Events, Missionsbelohnungen, Run-Modifier), keine aktive Regel zum Start. Mit einer einzigen Kenntnis im Pool plus höchstens einem Gutschein bleibt die Summe deutlich unter dem Deckel; auch hier gilt: nicht gegen einen Deckel kalibrieren, der gar nicht wirkt.

**Boni gelten nur für Projekte, nicht für Handlungen.** Dadurch wächst der Handlungsanteil am Pool über den Run relativ an — das späte Spiel verschiebt sich von selbst Richtung Ausführung. Das ist beabsichtigt und trägt den Kipppunkt aus 13.2 mit. Es ist zugleich der Grund, warum der Konsul keinen AP-Rabatt auf Handelsgeschäfte bekommt (§12): Sein Beitrag liegt auf der Qualität der Handlungen, nicht auf ihren Kosten. (Die Navigation-Rabatte der `cartography` sind eine bestehende Ausnahme; ihr Rundungsverhalten bei kleinen Basiskosten ist offen und nicht Teil dieser Regel.)

---

### 13.4 Kommandozentrale: Dashboard und Prognosen

Das Ratenmodell ist nur spielbar, wenn der Spieler seine Rate und ihre Verwendung jederzeit sieht. Der Kommandozentrale-Screen ist deshalb als **Dashboard** angelegt. *Umsetzungsstand:* Es zeigt Phasenziele, Run-Fortschritt, Wartungsstand, Netto-Sol-Bilanz, Berater, Kolonisten und Vertrauens-Ereignisse; **offen** sind Restzeit je Baustelle, Restertrag bis Run-Ende und die Konzessions-Prognose. Es ist keine Komfortfunktion, sondern tragende Voraussetzung: Mehr-Sol-Projekte machen nur Spaß, wenn sie planbar sind.

Mindestumfang:

| Anzeige | Zweck |
|---|---|
| AP-Zufluss pro Sol und wohin er aktuell fließt | Grundlage jeder Allokationsentscheidung |
| Restzeit je Baustelle („noch 3 Sole bei aktueller Rate") | Planbarkeit von Projekten, Timing von Fertigstellungen |
| Instandhaltungsanteil („Reparatur bindet [Anteil] deiner Kapazität") | Macht die wachsende Last aus 13.5 sichtbar, bevor sie drückt |
| **Restertrag bis Run-Ende** je Projekt („Agrardom Lv5: noch 3 Sole, dann 8 Sole × 7 Organika") | Trägt den Late-Game-Kipppunkt (13.2) — siehe unten |
| Regolith-Bilanz (Produktion − Reparatur − Levelups) | Die eigentliche Wachstumsgrenze (13.5) |
| **Unterbesetzungs-Hinweis**, solange Arbeitsplätze unbesetzt sind (Anzahl, Ursache, Produktionsabschlag, Sperre von Neubau und Ausbau) | Macht die Folgen des Wohnraumverlusts sichtbar und planbar (§6) |
| Konzessions-Prognose („bei aktuellem Kurs in 12 Solen unterschritten") | Macht den Fail-State aus §18.2 vorhersehbar statt überraschend |
| Fortschritt der Run-Aufgaben | Verbindet Tagesentscheidung mit Run-Ziel (§15) |
| **Rest-AP-Hinweis**: welcher AP-Pool noch Reserve hat und wofür er nutzbar ist | Der Hinweis nennt nur Verwendungen, die gerade möglich sind: kein „in ein Gebäude investieren“, wenn Regolith fehlt oder der Tages-Deckel erreicht ist; stattdessen der ehrliche Grund oder der Verweis auf den Hangar (§16.2) |

**Zum Restertrag — er trägt den Kipppunkt, nicht die Kosten.** Der Late-Game-Kipppunkt aus 13.2 entsteht nicht dadurch, dass Projekte spät teurer werden, sondern dadurch, dass sich ihr Ertrag nicht mehr amortisiert: Ein hochstufiges Produktionsgebäude, das spät fertig wird, liefert nur noch kleine Restertrag-Mengen — das ist der Grund, es sein zu lassen. Wenn das Dashboard neben der verbleibenden Bauzeit auch den geschätzten Restertrag zeigt, entsteht der Phasenwechsel **ohne jede Zahlenänderung**. Ohne die Anzeige müsste man ihn über Kosten erzwingen, was das Fortschrittsgefühl aus 13.3 beschädigen würde.

**Der Instandhaltungsanteil macht eine Bodengarantie überflüssig.** Die eigentliche Selbst-Blockade-Gefahr im Ratenmodell ist nicht, dass ein Spieler eine Domäne aushungert (13.1), sondern dass er jeden Sol den ganzen Pool in Reparaturen kippt und den Run langsam verliert, ohne es zu merken. Dagegen hilft keine Untergrenze — nur Sichtbarkeit. Diese Anzeige ist deshalb kein Komfort-Feature und darf nicht wegpriorisiert werden.

**Zur Konzessions-Prognose:** Run-Ziel (Expertenstab aufbauen) und Fail-State (Konzessionsentzug) ziehen in dieselbe Richtung — Berater kosten Credits und Unterhalt, und genau das kann die Konzessionsbedingungen reißen. Das ist als **Push-your-luck** gewollt: Der Spieler soll abwägen, ob er noch einen Berater einstellt oder Puffer hält. Diese Spannung funktioniert aber nur mit sichtbarer Prognose — ohne sie wird der Spieler für Zielverfolgung bestraft, ohne es zu merken.

---

### 13.5 Instandhaltungslast und die Regolith-Grenze

**Es gibt kein AP-Gleichgewicht, ab dem die Instandhaltung den gesamten Zufluss bindet.** `GameTick::processBuildingDecay()` zieht `decay_rate` je Gebäude-Instanz ab — **unabhängig vom Level**. Weil der Gebäudekatalog endlich ist (13 Typen, dazu die Instanzen des Wohnhabitats), hat die Instandhaltung in AP eine harte Obergrenze, die deutlich unter einem mit Beratern ausgebauten Pool liegt. Was stattdessen gilt: **eine wachsende, sichtbare Last.** Der Instandhaltungsanteil des Pools wächst über den Run — moderat früh, spürbar im Endausbau (Zielwert bei der Zielkolonie siehe G2 in §13.7). Das ist Gegenwind, kein Stillstand, und passt zu „kein Leerlauf, aktives Spielen wird belohnt" (§1.1) besser als ein Gleichgewicht, das den Spieler einfriert.

**Die eigentliche Wachstumsgrenze ist Regolith.** Reparatur kostet Regolith je SP, Level-Ups kosten Regolith, Errichtungen kosten Regolith. Dagegen steht der Harvester mit einem Grundeinkommen plus Missionen, Events und Handel. Diese Bilanz — nicht der AP-Pool — entscheidet, wie groß eine Kolonie werden kann; sie gehört ins Dashboard (§13.4).

#### Harvester: kein Level-Up, eine Instanz (Owner-Entscheidung)

Der Harvester hat **kein Level-Up** (`max_level = 1`) und genau eine Instanz. Er liefert je Standort ein Grundeinkommen an Regolith, das mit der Erschöpfung des Tiles endet (§4c); Wachstum kommt aus Kenntnissen, Funden, Missionen und Handel. Er ist zugleich das einzige bewegliche Gebäude und wird pro Run mehrfach umgesetzt. Damit ist Regolith kein passives Einkommen, sondern **aktives Spiel** (§1.1).

#### Regolith-Beschaffung: alle drei Pfade müssen die Grundbedürfnisse decken

**Verbindliche Anforderung — §4b „Paritäts-Anforderung".** Die Pfade dürfen sich im *Wie* unterscheiden, nicht im *Ob*. Der Harvester ist der gemeinsame Sockel, den jede Kolonie unabhängig von der Pfadwahl hat; was einen Pfad ausmacht, ist der Hebel obendrauf:

| | Quelle | Kostenprofil |
|---|---|---|
| **Sockel (alle Pfade)** | Harvester, 1 Instanz (§4c) | keine (passiv), Umzüge kosten AP |
| **A — Analytik** | Kenntnis `geology` erhöht die Harvester-Ausbeute je Level (`game.geology_harvester_bonus_per_level`, kumulativ) | einmalig hoch (AP bis zum Ziellevel), danach null laufende Kosten |
| **B — Hangar** | Bergungsflüge zu den gescannten Funden des Fundpools (§4a, §8b); die Versorgungsfahrt liefert nur Organika | laufend: Navigation-AP, Organika-Proviant, Verschleiß, Reparatur-AP |
| **C — Cantina** | kein dedizierter Hebel — Pfad C liefert Credits (§4b „Pfad-C-Hebel"); opportunistischer Credits→Regolith-Kauf via Corvan möglich | — |

Die Profile sind bewusst gegensätzlich: **Analytik** verbessert den Sockel selbst — teuer im Aufbau, danach dauerhaft geschenkt, keine Logistik. **Hangar** legt einen zweiten Strom daneben — billig im Einstieg, aber jeden Sol Aufwand. **Cantina** kauft zu und wandelt Überschuss in Credits — maximal flexibel, an Credits und Angebotslage gebunden. Ob A und B die Regolith-Lücke der Zielkolonie tatsächlich schließen, rechnet §13.7 nach.

**Cantina-Angebote:** Die Losgröße von Corvans Kaufangeboten ist an die Zahlungsfähigkeit der Kolonie gebunden (höchstens ein Anteil des Bestands), damit Angebote nicht regelmäßig an „Not enough resources" scheitern. *Offen:* Tauschrichtung der anonymen Gäste nach Bestand wählen statt würfeln (Give = größter Überschuss, Get = knappste Ressource) — der Zufall bliebe in Preisvarianz, Gästezahl und Gültigkeitsdauer erhalten (ROADMAP „Offene Pfad-Paritäts-Fragen").

> **Nachrüstoption, falls das späte Spiel im Playtest schlaff wirkt:** Reparaturkosten mit dem Level skalieren — `AP je SP = 1 + floor((level−1)/3)`. Die Instandhaltung skaliert dann mit der **Tiefe** und koppelt sich an den Wohnraum (§6). Die Alternative `decay_rate × level` ist thematisch schwächer (warum verfällt ein größeres Gebäude schneller?) und verdoppelt zusätzlich den Regolith-Abfluss.

---

### 13.6 AP-Zahlensatz

Die AP-Struktur ist Owner-Entscheidung: Grundwert des gemeinsamen Pools (`config/game.php → ap.base`), Berater-Beitrag je Rang (`advisor.ap_per_rank`), progressive Projektkostenkurve `f(L)` mit `f(1) = 0.5` und additive Bonus-Kurve (§13.3). Der Grundwert ist gegen die Instandhaltungslast breiter Kolonien (§4c „im Zweifel Instanz") kalibriert, damit alle drei Pfade ohne strukturelle Enge machbar bleiben — auch Pfad B mit seinen laufenden Kosten.

**Die tragenden Elemente:** Basis-AP-Wert, Gebäude-Basis-Kosten-Klassen (gestaffelt nach Gebäude-Rolle), eine progressive Kostenkurve pro Level, Berater-AP-Beitrag pro Rang (progressiv) und Kostenboni durch Kenntnisse/Domänen.

**Stellschrauben, wenn sich das System im Playtest als unausgewogen erweist** — in dieser Reihenfolge: Basis-Wert, dann Gebäude-Klassen, dann Kurvensteilheit, dann Berater-Beiträge. Alle sind einzeln in `config/` kalibrierbar, ohne den Regeltext zu ändern.

#### Ziel-Endzustand (solider Run zum Sieg-Zeitpunkt, Sol ~85–95)

Der Sieg-Korridor ist in §18.4 festgelegt: Sol 70–85 nur bei einem sehr guten Run mit Glück, realistisch Sol 85–95. Ein typischer erfolgreicher Run erreicht zum Sieg-Zeitpunkt: die Mehrheit der Gebäudetypen (nicht alle), moderate Gebäude-Level und volle Berater-Slots, überwiegend auf mittlerem Rang (den höchsten Rang für alle erreicht nur, wer gezielt auf „Expertenstab" spielt), einige Kenntnisse auf mittlerem Level, ungefähr zwei Drittel der Bauplätze belegt. Ungenutzte Optionen sind Absicht: Der Spieler soll sehen, was offenblieb — kein Erreichen einer perfekten Optimalität, sondern ein befriedigender Zustand mit sichtbarem „hätte ich auch noch tun können".

#### AP-Grundwert und Berater-Beitrag

Der gemeinsame Pool hat einen Basiswert und wächst mit Berater-Rängen; der Beitrag steigt mit dem Rang, erlaubt aber kein exponentielles Wachstum — eine bewusst flache Kurve. Die AP-Rate wird durch drei Faktoren gestaffelt: **Berater-Anzahl und -Rang** (progressiv), **Instandhaltungslast** (wächst über den Run), **Projektkosten** (initial niedrig, später höher). Das Pool-Wachstum über einen Run ist moderat (Faktor ~2–3 vom Anfang zum Ende), kombiniert mit Kostenreduktionen durch Boni. Der Vertrauens-Multiplikator (`trust.ap_multiplier`, §14) kommt obendrauf.

#### Projektkosten je Gebäudelevel

```
ap_cost(building, L) = round(base_ap[building] × f(L))
f(1) = 0.5
f(L≥2) = 1 + 0.4 × (L−2)
```

Das Errichten (Level 1) kostet bewusst weniger als Level-Ups — Anreiz für breite Kolonien früh (weniger AP pro neues Gebäude) und tiefe Spezialisierung später. Gebäude sind nach Rolle in Kostenklassen gruppiert (Produktion, Klein, Mittel, Groß, Kommandozentrale als Sonderfall), jede mit eigenem Basis-AP. Produktionsgebäude sind am billigsten — ihre Glockenkurve (`game.production_curve`) setzt bereits einen Deckel.

> **Implementierungsstand:** Die Kurve `f(L)` ist **geplant** (ROADMAP Phase 4, D3). Aktuell gilt je Gebäude ein flacher `ap_for_levelup`-Wert für jedes Level; nur die Kommandozentrale hat eine steigende AP-Kurve (`cc_upgrade_ap_by_level`). Die Kostenklassen sind in `config/buildings.php` angelegt.

**Early-Game-Tempo:** Breite Kolonien früh (billige erste Level) über tiefe Spezialisierung, kombiniert mit den Wohnraum-Grenzen aus §6 — zusammen das Breite/Tiefe-Dreieck ohne optimalen Pfad. Befristete AP-Boni oder Vorbau in der Startkolonie sind keine Alternative: erstere wirken dort, wo ohnehin wenig Instandhaltung nötig ist, letzterer zerstört die Lernmomente aus §16.

**Kenntnisse** haben eigene, steigende Kosten je Level (`config/knowledge.php`) — sie skalieren parallel zum Pool-Wachstum.

#### Handlungs-AP

Sofort-Handlungen (Handel, Erkundung, Dispatch) sind gegen den gemeinsamen Pool kalibriert: deutlich billiger als Projekte, aber nicht kostenlos — sie konkurrieren um denselben Pool. Lange Missionen kosten einen nennenswerten Anteil des Pools; Ring-Erkundungen skalieren mit der Entfernung, damit die Karte nicht zu schnell aufgedeckt wird. Werte: `config/game.php` (Handel, Erkundung), `config/missions.php` (Navigation-AP pro Sol). Handels-AP (`bar.ap_cost_accept`/`ap_cost_negotiate`) sind gegen den gemeinsamen Pool gesetzt.

> **Die Regel „Gelegenheiten sind durch Verfügbarkeit begrenzt" (13.2) ist ohne neue Mechanik erfüllt.** Missionen sind durch Schiffszahl und Rundlaufzeit begrenzt, Bar-Angebote durch `guest_count` und `level_max_concurrent`. Zusammen binden sie einen kleinen Teil des Pools — genau der beabsichtigte Deckel.

#### Balancing-Targets und -Unsicherheiten

Der AP-Haushalt ist gegen drei Ziele kalibriert: (1) Die Zielkolonie ist ohne Glück erreichbar; (2) alle drei Pfade haben tragfähig unterschiedliche Kostenverhältnisse; (3) die frühen Sole zeigen sichtbaren Fortschritt (Baustellen laufen voll, erste Fertigstellungen nach wenigen Solen); durch den AP-Deckel (13.2) wird kein Gebäude und keine Ausbaustufe an einem einzigen Sol fertig. Zu prüfen im Playtest: Fühlt sich die Instandhaltungslast wie beabsichtigt an (Druck ohne Deadlock)? Sind alle drei Pfade äquivalent tragfähig? Die Credits-Ökonomie ist gegen den Berater-Unterhalt hergeleitet (§18.4), aber nicht gegen eine vollständige Credits-Bilanz über den Run (`docs/gdd-balance-checklist.md` A.4). Befunde führen zu Config-Anpassungen, nicht zu Regeltext-Änderungen.

---

### Slot-System: CC-Level als Gate, Pfadwahl ab Slot 2

Berater-Slots öffnen nicht allein über CC-Level, sondern analog zu den Pfad-Gebäuden: durch den Bau eines spezifischen Gebäudes. Slot 1 ist **fest** an den Baumeister gebunden (siehe §16.2 "Designentscheidung zu Rang 1"). Slots 2–4 sind **generisch**: Welcher Beratertyp einen dieser drei Slots belegt, hängt davon ab, welches der drei Pfad-Gebäude der Spieler zuerst/zweit/dritt baut — nicht von einer fest verdrahteten CC-Level→Typ-Zuordnung.

| Gate | Slot | Bindung |
|------|------|---------|
| CC Lv1 | Slot 1 | **fix:** Baumeister |
| CC Lv2 + 1. Pfad-Gebäude (sciencelab/hangar/bar) | Slot 2 | **generisch:** Analytiker/Raumfahrer/Konsul |
| CC Lv3 + 2. Pfad-Gebäude | Slot 3 | **generisch:** Analytiker/Raumfahrer/Konsul |
| CC Lv4 + 3. Pfad-Gebäude | Slot 4 | **generisch:** Analytiker/Raumfahrer/Konsul |

> **Phasen-Zuordnung:** Slot 1 und Slot 2 gehören zum Grundaufbau (Phase 1: Baumeister plus Berater des ersten Pfades). Slot 3 und Slot 4 öffnen erst mit CC Lv3 bzw. Lv4 und gehören damit zu Phase 2. Die Berater-Slot-Tabelle selbst bleibt unverändert; nur die Phase-1-Abschlussbedingung verlangt zwei statt drei Berater.

> Es gibt **vier** Berater-Slots. Die Leitstelle ist kein Slot-Gate (Stratege zurückgestellt, siehe „Die vier Berater-Typen").

**Die drei Pfade** (siehe §4 "Pfadwahl ab CC Lv2"):

| Pfad | Gebäude | Beratertyp | Domäne | CC-Gate | Slot |
|------|---------|-----------|--------|---------|------|
| A | Analytik-Labor (sciencelab) | Analytiker | research | CC Lv2 (Pfadwahl) | 2–4 (generisch) |
| B | Hangar | Raumfahrer | navigation | CC Lv2 (Pfadwahl) | 2–4 (generisch) |
| C | Cantina (bar) | Konsul | economy | CC Lv2 (Pfadwahl) | 2–4 (generisch) |

**Gate-Logik (Bau, nicht nur Berater):** Alle drei Pfad-Gebäude sind ab CC Lv2 grundsätzlich baubar — aber gleichzeitig gilt ein zusätzliches Bau-Gate: `Anzahl bereits gebauter Pfad-Gebäude < CC-Level − 1`. Bei CC Lv2 darf also nur **eines** der drei gebaut werden; das zweite schaltet erst bei CC Lv3 frei, das dritte erst bei CC Lv4. Das Gate ist eine Server-Regel (`PathBuildingGateService`); das Baumenü zeigt gesperrte Pfad-Gebäude mit Begründung. Es gibt **keine permanente Ausschließung** — wer bei CC2 die Cantina wählt, bekommt Sciencelab und Hangar bei CC3 bzw. CC4 trotzdem, nur später. Die frühe Wahl des ersten Pfadgebäudes (Phase 1) bestimmt **Reihenfolge und Zeitvorsprung**, nicht endgültigen Zugang. Das hält die Entscheidung gewichtig (wer zuerst baut, bekommt den zugehörigen Berater-Slot 1–2 CC-Level eher als bei den anderen beiden Pfaden), vermeidet aber einen harten Lockout, der bei einer so frühen Entscheidung zu hart wäre (Nouron-Prinzip: keine bestrafenden Permanent-Konsequenzen für frühe Entscheidungen, siehe §1.1).

**Stufen-Gate (alle Nicht-CC-Gebäude):** Eine Ausbaustufe eines Gebäudes braucht eine Kommandozentrale mindestens gleicher Stufe — ein Lv3-Gebäude setzt CC Lv3 voraus. Die Kommandozentrale ist damit der Taktgeber der Kolonie-Entwicklung; wer sie nicht ausbaut, bleibt in der Breite stecken statt in der Tiefe zu eilen. Der CC-Ausbau selbst kostet steigend AP (§4 „Level-Up"), er bremst also über die knappe Handlungsressource. In Phase 1 greift das Gate kaum (alles bleibt auf Lv1, CC auf Lv2); es wirkt ab Phase 2, wo CC Lv3 den Auftakt bildet. Server-Regel (`BuildingService::ccLevelTooLow()`), Fehlercode `building_cc_gate`.

**Reihenfolge-Auflösung:** Der Slot, den ein Pfad-Gebäude belegt, ergibt sich aus der **Baureihenfolge** dieses Gebäudes relativ zu den anderen beiden — nicht aus dem Gebäudetyp selbst. Werden (im seltenen Fall ausreichender Ressourcen-Reserven) zwei Pfad-Gebäude im selben Sol fertiggestellt, entscheidet ein fixer, nicht spielerseitig beeinflussbarer Tie-Break in der Reihenfolge **Sciencelab → Hangar → Cantina** (aufsteigend nach `building_id`: 31 < 44 < 52). Dieser Tie-Break ist ein reines Implementierungsdetail ohne Spielerrelevanz außerhalb des Edge-Case.

> **Kostenbalancing der Pfad-Gebäude:** Die drei Pfad-Gebäude sind in Regolith und Arbeitsplatzbedarf gleich bepreist (`config/buildings.php`, §13.7 G4). Die Pfade unterscheiden sich über ihren Hebel-Mechanismus (§4b) und ihre AP-Kostenklasse, nicht über den Baupreis. Schiffe kosten kein Supply (§6).

---

### Datenmodell: `advisors`-Tabelle

Jeder Berater ist ein eigener Datensatz. Die Tabelle hat folgendes Schema:

```
advisors
├── id                      ← eindeutige ID des Beraters
├── user_id                 ← Eigentümer (immer gesetzt)
├── personell_id            ← Beratertyp (FK auf `personell`: Baumeister, Analytiker, Raumfahrer, Konsul)
├── colony_id               ← nullable: aktiv auf dieser Kolonie
├── rank                    ← 1 = Junior | 2 = Senior | 3 = Experte
├── active_ticks            ← kumulierter Zähler für Rang-Aufstieg
└── unavailable_until_tick  ← Sperre durch Nexus-Sanktion (NULL = verfügbar)
```

> Berater sind colony-scoped — sie verlassen die Kolonie nicht.

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
> **Was das konkret heißt:** Es gibt maximal **vier** gleichzeitig zugewiesene Berater. Die **Leitstelle** wirkt über ihre drei eigenständigen Effekte (Vertrauens-Bonus, Event-Dämpfung, Recycling, §4) und ist kein Slot-Gate. Informationsleistungen (Gefahren-Vorwarnung mit Prognose, Ziel-Erreichbarkeits-Prognose) liefert das Kommandozentrale-Dashboard (13.4).
>

Der Raumfahrer trägt zum gemeinsamen AP-Pool bei — diese AP decken die Tile-Erkundung (ring-gestaffelt 1/2/3 AP, §4a) und den Dispatch von Hangar-Schiffen auf Außenmissionen (`sol_distance × 2` AP, §8b). Er verlässt die Kolonie nicht. Berater-Außendienst ist zurückgestellt (siehe unten).

### Berater-Außendienst — zurückgestellt

> **Keine Berater-Außenmissionen:** Berater können nicht auf Außenmission entsandt werden. Der Entwurf (Missionen je Beratertyp, drei Ausgänge, Schema, Tick-Integration) liegt im [Archiv](gdd/archiv-berater-aussendienst-burnout.md). Nicht zu verwechseln mit den **Hangar-Außenmissionen** der Schiffe (§8b), die bleiben.

---

### Rang-System

Jeder Berater hat einen von drei Rängen. Der Rang bestimmt, wie stark der Berater den gemeinsamen AP-Pool erhöht (§13.1) und wie hoch sein laufender Upkeep in Credits ist — beide Werte wachsen mit dem Rang, additiv auf den gemeinsamen Pool angerechnet, unabhängig von der Domäne des Beraters.

Exakte Werte (AP-Bonus je Rang, Upkeep, Rang-Aufstiegs-Schwellen in aktiven Ticks, Beförderungskosten): siehe `config/game.php → advisor` und `docs/game-reference.md#5-berater-advisors-hire-kosten--ap-beiträge`.

**Einstellungskosten (Rang 1) — typ-spezifisch:** Baumeister ist der günstigste Einstieg (Kernanforderung Tag 1); Analytiker, Raumfahrer und Konsul sind höher gestaffelt, gekoppelt an ihre spätere Verfügbarkeit (Analytiker erst ab CC Lv2, Raumfahrer voller Nutzen erst mit Hangar, Konsul mittlere Priorität). Exakte Beträge: `config/advisors.php`, `docs/game-reference.md`.

**Beförderung** kostet beim Erreichen von Rang 2 bzw. Rang 3 zusätzlich zum laufenden Upkeep einen einmaligen Credits-Betrag (`config/game.php → advisor.promotion_costs`).

**Beförderung ist eine aktive Entscheidung, kein Automatismus (Owner-Entscheidung F3).** Sobald ein Berater den `active_ticks`-Schwellwert für den nächsten Rang erreicht hat, steht die Beförderung bereit — der Spieler löst sie selbst aus, wann er will, und kann sie beliebig lange hinauszögern, um den höheren Upkeep und die Einmalkosten erst zu tragen, wenn die Kolonie es sich leisten kann. Das folgt demselben Grundsatz wie an anderer Stelle im AP-/Berater-System: aktive Entscheidungen statt automatischer Fortschritt.

- **Upkeep** wird jeden Sol von den Colony-Credits abgezogen, solange der Berater `colony_id` gesetzt hat (Berater ist aktiv zugewiesen).
- **Rang-Aufstieg:** Bereitschaft wird nach ausreichend kumulierten `active_ticks` erreicht (`config/game.php → advisor.rank_thresholds`); der Vollzug bleibt manuell, siehe oben.
- Alle Werte stehen in `config/game.php → advisor` (Einstellungskosten, AP-Bonus, Upkeep, Rang-Thresholds, Beförderungskosten).

> **UI-Anforderung:** Die Berater-Verwaltung zeigt für jeden aktiven Berater: Rang, AP-Beitrag/Sol, laufender Upkeep (Cr/Sol) und `active_ticks` zum nächsten Rang-Aufstieg. Diese vier Werte müssen auf einen Blick lesbar sein.

---

### Kosten: Credits — keine Kolonisten

Berater kosten ausschließlich **Credits** — sowohl bei der Einstellung (einmalig) als auch im laufenden Upkeep (pro Sol). Sie binden keine Kolonisten.

Die Kolonisten (Wohnraum) bleiben der physische Kapazitätsdeckel für Gebäude. Personalkosten laufen über Credits. Das trennt zwei konzeptuell verschiedene Größen sauber:

- **Kolonisten** = physische Infrastrukturkapazität (Gebäude)
- **Credits** = ökonomische Liquidität (Personal, Handel, Investitionen)

**Schiffsanzahl:** Die maximale Schiffsanzahl der Kolonie ist durch die Hangar-Buchten begrenzt (Stufe = Buchten plus Vorfeld, siehe §6). Kein Kommandanten-Pflichtmodell: Schiffe benötigen keinen zugewiesenen Raumfahrer.

---

### Raumfahrer: Colony-Scope

Der Raumfahrer ist ein colony-scoped AP-Produzent für den `navigation`-Pool. Er bleibt der Kolonie zugewiesen und verlässt sie nicht.

- **Colony-zugewiesen:** Generiert Navigation-AP auf der Kolonie (Grundlage für Tile-Erkundung und Außenmissions-Dispatch).
- **AP-Verbrauch:** Navigation-AP, die der Raumfahrer generiert, werden verbraucht wenn der Spieler Tiles erkundet oder Schiffe entsendet — der Raumfahrer selbst "geht" dabei nicht mit.

---

### Verfügbare AP

```
availableAP = Grundwert + Σ AP_bonus(rank) über alle zugewiesenen Berater − lockedAP(tick)
```

Ein einziger Pool je Kolonie (13.1). `AP_bonus(rank)` ist der Beitrag jedes aktuell zugewiesenen Beraters, unabhängig von seiner Domäne — vier Berater erhöhen denselben Pool viermal. AP-Locks verfallen automatisch zum nächsten Sol; der Pool wird täglich vollständig erneuert.

Der Grundwert des gemeinsamen Pools steht in `config/game.php → ap.base` (Owner-Entscheidung, §13.6). Vertrauens- und Seuchen-Multiplikatoren (§9, §14) wirken zusätzlich multiplikativ auf den fertigen Grundwert+Bonus-Betrag, siehe `AdvisorService::getApBreakdown`. Exakter Wert: `docs/game-reference.md#9-action-points-ap`.

### AP-Verbrauch

**Projekte (mehrere Sole, siehe 13.2):**

1. **Bauen/Ausbauen:** AP werden beim Investieren gesperrt (`invest('add')`), bis die Projektkosten erreicht sind. Je Gebäude-Instanz nimmt ein Sol höchstens so viele AP auf, wie der AP-Deckel erlaubt (Grundwert plus Baumeister-Beitrag je Rang, 13.2); Belohnungs-AP zählen nicht dagegen.
2. **Kenntnisse:** identisch — bereits implementiert über `AbstractTechnologyService::_invest`. Der Deckel für Kenntnisse (Analytiker) ist vorgesehen, aber noch nicht aktiv; erst messen, dann einschalten.

**Handlungen (sofort, siehe 13.2):**

3. **Reparatur/Abbau:** AP in Höhe der veränderten `status_points`.
4. **Erkundung/Dispatch:** Tile-Erkundung ring-gestaffelt (1/2/3 AP, §4a), Außenmissions-Dispatch `sol_distance × 2` AP (§8b).
5. **Handelsgeschäfte:** AP je angenommenem oder verhandeltem Angebot (§12).

### Implementierung

- `app/Services/AdvisorService.php` — AP-Berechnung (`getApBreakdown`), Sperrung
- `app/Services/Techtree/AbstractTechnologyService.php` — AP-Verbrauch beim Investieren
- `app/Services/ColonyTileService.php`, `app/Services/HangarService.php`, `app/Services/BarService.php` — Handlungs-AP für Erkundung, Dispatch und Handel
- Tabelle `locked_actionpoints`: `(tick, scope_type, scope_id, personell_type, spend_ap)` — `personell_type` ist nur Auswertungsmerkmal („wofür wurde investiert"), keine Pool-Trennung.

### Test-Schalter

Zum Testen einzelner Systeme lassen sich Prüfungen per `.env` abschalten: `GAME_BYPASS_AP`, `GAME_BYPASS_RESOURCES` und `GAME_BYPASS_SUPPLY` (alle standardmäßig aus; `config/game.php → bypass`). Der alte Sammelschalter `GAME_DEV_MODE` ist veraltet. In Produktion sind die Schalter gesperrt.

---

### Berater als Informationsebene

Jeder Berater erweitert nicht nur den AP-Pool seines Typs — er erweitert auch den **Informationsraum** des Spielers in seinem zugehörigen Screen. Ohne Berater ist der Screen voll funktionstüchtig; mit Berater erscheinen zusätzliche Metriken, Prognosen und Hinweise.

Dieses Konzept — "Fog of Information" — ist analog zum Fog of War in der Exploration, aber auf Spieler-Entscheidungsqualität bezogen. Wer einen Berater verliert (Entlassung, Nexus-Sanktion), spielt dasselbe Spiel mit weniger Kontext. Das erzeugt spürbare Konsequenz ohne harten Progress-Block.

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
| Konsul | Cantina | Händler-Einschätzung "guter / durchschnittlich / schlechter Deal" (kontextuell, nicht binär) | Restlaufzeit-Countdown für Angebote prominent statt versteckt; Marktbericht zu Corvans nächstem Besuch (§12) |
| Raumfahrer | Hangar | Aufgebrochene Missionszeit ("X Sole Hinweg + Rückkehr Sol Z") | Verschleiß-Prognose pro geplantem Dispatch (§7) |

> Die Ziel-Erreichbarkeits-Prognose („Aufgabe X: ✓ in ~12 Solen; Aufgabe Y: ✗ — 400 Cr fehlen") und die Ausgangs-Prognose bei Gefahren-Vorwarnung (§9) sind keiner Berater-Informationsebene zugeordnet — sie gehören ins Kommandozentrale-Dashboard (§13.4).

> **⚠️ Balance — Konsul:** Händler-Einschätzung darf nicht binär sein ("kaufen / nicht kaufen"), sonst entwertet sie die Handelsentscheidung. Kontextuell: "günstig für Werkstoffe — du hast davon aber bereits 200" ist besser als "guter Deal".

> **⚠️ Balance — Erster Cantina-Besuch:** Konsul ist erst ab CC Lv2 verfügbar, der erste Händler erscheint zeitiger. Der erste Cantina-Besuch muss immer ein objektiv gutes Angebot zeigen — unabhängig vom Konsul-Status. Sonst entsteht Früh-Spiel-Frustration bei Spielern ohne Konsul.

**Implementierung:** Phase 4 — setzt stabiles Berater-System und abgeschlossene Screen-Redesigns voraus. Keine neuen Datenpunkte nötig (alle Quellen in Config und DB bereits vorhanden), reine UI-Logik. Discovery-Moments integrieren sich in bestehenden Onboarding-Hint-Stack (§16).

---

---
### 13.7 Regolith-Zahlensatz, hergeleitet

Von der Designabsicht her hergeleitet statt aus Bestandswerten fortgeschrieben. Der Satz ist Owner-Entscheidung; die Werte stehen in `config/buildings.php` und `config/game.php`, Lookup in `docs/game-reference.md`. Dieses Kapitel hält die Herleitung fest, damit Playtest-Befunde gegen die richtige Stellschraube laufen.

> **Status:** Die Herleitung rechnet noch mit einem Pfad-B-Dauerhebel (Frachter-Versorgungsfahrten), einem zweiten Harvester als Option und einem Rampen-Zyklusmittel. Nichts davon gilt mehr: Pfad B liefert Regolith nur über den einmaligen Fundpool (Bergungsflüge), es gibt genau einen Harvester, und er fördert konstant. Die Neuherleitung läuft als Regolith-Budget-Modell (ROADMAP T31, `docs/superpowers/specs/2026-10-14-regolith-wirtschaft-budget-modell.md`); die Zahlen unten sind bis dahin Rechenbeispiele, keine Sollwerte.
>
> ⚠️ **Zu überprüfen:** Der Harvester fördert konstant zum Frischwert bis zur Erschöpfung (§4c). Das hebt den effektiven Sockel-Durchschnitt gegenüber dem hier gerechneten Zyklusmittel spürbar an — die gesamte Bilanz unten (Sockel-Anteil, Hebel-Zielgröße, G2/G6-Prozentsätze) rechnet noch mit einem Rampen-Zyklusmittel. Die Neuherleitung folgt nach dem nächsten Playtest-Batch als eigenes Vorhaben. Bau-Preise, `decay_rate`-Klassen und Guard-Rails (G2/G4/G6/G7) bleiben bis dahin unverändert, gelten aber als zu überprüfen.

#### Das Spielgefühl — zuerst, ohne Zahlen

Jede Zahl unten ist auf eine dieser Aussagen zurückführbar. Wo das nicht gelingt, ist sie willkürlich und gehört ersetzt.

| | Aussage |
|---|---|
| **G1** | **Regolith ist nie bequem und nie tödlich.** Der Bestand schwingt um eine niedrige zweistellige Zahl. Ein wachsender Haufen heißt, die Kolonie ist fertig; eine Null heißt, sie stirbt. Beides beendet die Spannung. Im Sockel-only-Spätspiel kriecht der Bestand statt zu schwingen — G1 setzt einen aktiven Pfad-Hebel voraus, wie G2 und G6. |
| **G2** | **Instandhaltung ist Routine, nicht Krise.** Gemessen am Gesamteinkommen (Sockel + aktiver Pfad-Hebel): Sie bindet ~15 % früh und ~40 % bei der Zielkolonie. Unter 10 % ist Verfall Dekoration und die USP fällt weg; über 60 % ist er eine Strafe fürs Bauen. |
| **G3** | **Vernachlässigung kostet ein Level, nicht den Run.** Ein Level-Down ist in 5–8 Solen aufgeholt, ohne Kaskadenrisiko. |
| **G4** | **Errichten ist eine Entscheidung, Level-Up ein Schritt.** Die Errichtung eines Pfadgebäudes kostet 5–8 Sole Sparen, ein Level-Up 1–2. Gilt für die drei Pfadgebäude Sciencelab/Hangar/Bar (einheitlich bepreist). Der Agrardom ist als Pflicht-Ramp-Gate vor CC Lv2 ausdrücklich ausgenommen — kein Pfadgebäude, steht nicht zur Abwägung; reine Infrastrukturgebäude (Wohnhabitat, Krankenstation, Leitstelle, Tempel, Monument) folgen anderen Kriterien. |
| **G5** | **Der Spieler soll 2–4 Mal pro Run an Regolith scheitern** — nicht dauernd (Grind), nicht einmal (Gate). Playtest-Trigger: mehr als 4 blockierte Sole pro Run. G5 dient zugleich als Toleranzbudget für die Restlücke eines einzelnen Hebels (Punkt 5 unten). |
| **G6** | **Der Sockel trägt das Überleben, der Pfad-Hebel das Wachstum.** Der Sockel allein trägt eine spielbare, aber unterdimensionierte Kolonie (~57 % der Zielgröße) — genug, um nicht zu scheitern, zu wenig für die Zielkolonie. Der gewählte Pfad-Hebel schließt die Lücke auf annähernd 100 %, sobald er aktiv genutzt wird. Ein Run ohne jede Pfad-Aktivität ist im Design nicht vorgesehen (die Pfadwahl ist ab CC Lv2 verpflichtend, §4b). |
| **G7** | **Der Spieler muss im Kopf rechnen können.** „Ich mache 20 pro Sol, das kostet 95, das sind fünf Sole." (Illustrativer Platzhalter für runde Kopfrechenzahlen — die Referenzwerte sind Frischwert und Zyklusmittel des Harvesters, siehe unten; 95 ist der einheitliche Preis der drei Pfadgebäude.) |

**G4 ist die wichtigste Aussage**, weil sie Regolith und AP entkoppelt: Das AP-Modell macht es genau umgekehrt (`f(1) = 0.5` — Errichten AP-billig, Level-Up AP-teuer). **Breite kostet Regolith, Tiefe kostet AP.** Die beiden Währungen greifen an gegenüberliegenden Enden an.

**G7 bestimmt die absolute Skala** — das ist der eigentliche Grund gegen einen niedrigen Sockel, nicht eine Deckungslücke. Bei einem sehr kleinen Sockel liegen alle Baupreise so eng, dass der Unterschied zwischen zwei Gebäuden im Reparaturrauschen verschwindet; beim gewählten Sockel gibt es unterscheidbare Preisklassen mit sauberem Abstand.

#### Der Satz

| Wert | Festlegung | folgt aus |
|---|---|---|
| Harvester-Ertrag | Frischwert je Tile-Kombination aus Ertrags- und Mächtigkeits-Stufe (§4c), konstant bis zur Erschöpfung, dann harter Cutoff, `max_level = 1` | G7 |
| Reparatur | 1 Rg je SP (zusätzlich 1 AP je SP) | G2 + „eine Zahl, zwei Währungen" |
| `decay_rate` | vier Klassen 0,40 / 0,60 / 0,80 / 1,20 | G2, G3 |
| Errichtung (Lv0→1) | 70 Agrardom (Ramp-Gate-Ausnahme) / 95 alle drei Pfadgebäude | G4 (5–8 Sole) |
| Level-Up | flach 25, jedes Level, jedes Nicht-CC-Gebäude (auch der Sprung 0→1 nach der Errichtung) | G4 (1–2 Sole) |
| CC-Ausbau | Ziel-Level × Faktor in Regolith, zusätzlich steigende AP je Ziel-Level | zentraler Progressionshebel, Taktgeber über das Stufen-Gate |
| Weitere Instanzen (Wohnhabitat) | voller `build_cost`, linear | §4c: Instanzen sind eine Designachse, keine Level |
| Startbestand | `game.onboarding.start_regolith` (Herleitung „Phase-1-Pacing" unten) | Phase-1-Ziel Sol 12–13 (Minimum), 14–16 (mit Extras) |
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
| Standard | 33 | 0,60 | Agrardom, Uplink-Station, Hangar, Handelsposten, Leitstelle |
| Beansprucht | 25 | 0,80 | Harvester, Analytik-Labor, Cantina, Krankenstation |
| Fragil | 17 | 1,20 | Religiöse Stätte |

Bei den robusten Gebäuden ist ein Level-Down überproportional teuer (Wohnraum bricht weg) — er muss langsam kommen, sonst verletzt er G3. Die Religiöse Stätte ist bewusst der teuerste Unterhalt im Spiel: Sie zahlt in Vertrauen, nicht in Funktion; wer sie hält, entscheidet sich aktiv dafür. Die Raten sind gegen eine Zielkolonie mit vielen Instanzen kalibriert (§4c „im Zweifel Instanz"): Σ `decay_rate` bei Vollausbau liegt bei ~41 % des AP-Pools und — gemessen an Sockel + aktivem Pfad-Hebel — bei ~41 % des Regolith-Einkommens; das trifft G2s Zielwert. Ein vernachlässigtes Gebäude verliert innerhalb eines Runs ein Level (17–50 Sole), der Verfall bleibt als Systemprinzip spürbar.

#### Bilanz über den Run (1-Instanz-Baseline)

Rechnung über 80 Sole. Die Fensterbreite entspricht grob der Strecke vom typischen Phase-1-Ende bis zur Frist bzw. zum realistischen Sieg-Korridor (§18.4).

**1. Zielkolonie-Bedarf.** Errichtungen, Level-Ups und Reibung (Reparaturen, Fehlplatzierungen, Verlegungen) über den Gebäude- und Level-Katalog der Zielkolonie.

```
Zielkolonie-Bedarf ≈ 835 (Errichtungen) + 720 (Level-Ups) + 240 (Reibung, ~15 %) ≈ 1.795 Rg
```

**2. Sockel-Einnahmen** aus der Erschöpfungskurve, Standardfall 1 Instanz auf einem mittleren Ertrags-/Mächtigkeits-Tile inklusive Transit-Sole: Zyklusmittel **~12,9 Rg/Sol**, konstant über den Run. *(Zu überprüfen — Rampen-Zyklusmittel, siehe Hinweis am Kapitelanfang. Mit konstanter Förderrate und der Ertrag/Mächtigkeit-Entkopplung liegt der Sockel-Durchschnitt anders; Neuherleitung ist Teil der offenen T9-Kalibrierung.)*

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
| B — Hangar | Bergungsflüge zum Fundpool (T30 Schritt 3) | einmaliger Pool, kein Dauerertrag (siehe `docs/game-reference.md`) |
| C — Cantina | kein dedizierter Hebel (§4b), opportunistischer Kauf via Corvan | 0 (nicht planbar/strukturell) |

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

**Ziel:** Phase 1 ist der **Grundaufbau** (`checkPhase1Completion()`: CC Lv2, Agrardom, Harvester, Wohnhabitat, Uplink-Station und das erste Pfadgebäude stehen, jeweils mindestens Lv1, dazu zwei Berater) und endet für den Minimum-Spieler (nur die Pflichtgebäude) im Median um Sol 12–13, für Menschen mit Extras um Sol 14–16; als harte Grenze gilt Fail State 4 (Sol 20, §18.2). Am Phase-1-Ende bleibt ein Regolith-Puffer für ein bis zwei Extra-Gebäude oder -Stufen, damit es nicht nur eine richtige Bauabfolge gibt. Die Zeit kommt aus dem AP-Deckel (13.2), nicht aus höheren Regolith-Kosten. Wer schon in Phase 1 CC Lv3 oder Pfadgebäude-Tiefe erreicht, hatte zu viele Ressourcen übrig — Lv3 in Phase 1 ist ausdrücklich nicht gewollt. CC Lv3 ist der Auftakt von Phase 2; Pfadgebäude Lv2/Lv3, das zweite Pfadgebäude und der dritte Berater gehören ebenfalls dorthin. Credits sind für die zwei Ersteinstellungen nicht bindend (Startkapital reicht).

**Taktgeber CC (Regolith plus steigende AP):** Die Kommandozentrale kostet weiterhin Regolith (Ziel-Level × Faktor) und **zusätzlich steigende AP je Ausbaustufe** (`config/buildings.php`, Werte in `docs/game-reference.md`). Zusammen mit dem Stufen-Gate (§13 „Slot-System": Gebäude-Stufe N braucht CC-Stufe ≥ N) ist die CC damit der eine sichtbare Engpass der Kolonie-Entwicklung: Wer Tiefe will, muss zuerst Regolith und AP für die CC aufbringen.

**Bedarfskette Phase 1, günstigster Pfad** (Kommandozentrale, Harvester und Wohnhabitat existieren als Sol-1-Bootstrap auf Lv1; Agrardom und Pfadgebäude werden neu errichtet und starten auf Level 0):

| Posten | Rg |
|---|---|
| CC Lv1→Lv2 (Ziel-Level × Faktor; zusätzlich AP, hier nicht gezählt) | 60 |
| Agrardom: Errichtung 70 + Lv0→1 (25) | 95 |
| Wohnhabitat, Harvester | 0 (Bootstrap, Lv1 reicht) |
| Pfadgebäude 1: Errichtung 95 + Lv0→1 (25) — öffnet Slot 2 (= zweiter Berater) | 120 |
| **Summe** | **275** |

Die alte Kette (CC Lv3, Agrardom Lv2, Wohnhabitat Lv2, zwei Pfadgebäude; Summe 535) ist damit auf rund die Hälfte geschrumpft. Das zweite Pfadgebäude und Slot 3 (= dritter Berater) sind kein Phase-1-Bedarf mehr (§13 „Slot-System"). Der Harvester produziert ab Sol 2 nahe dem Frischwert (~17 Rg/Sol auf einem mittleren Ertrags-Tile, solange das Vorkommen nicht knapp wird); Reparatur wird als konservative Marge angesetzt, obwohl frisch errichtete Gebäude im Fenster die Reparaturschwelle kaum erreichen:

```
Verfügbar(N) = Startbestand + Ertrag × (N − 1) − Reparatur-Marge × N
```

Phase 1 endet, sobald Verfügbar die Bedarfskette deckt und die Ausführung (Reihenfolgezwang, Erkundung, AP-Takt) nachgezogen hat. Jedes Regolith weniger im Startbestand verschiebt das Ende um den Kehrwert des Harvester-Ertrags nach hinten — der Startbestand ist deshalb der direkteste Pacing-Hebel.

> ⚠️ BALANCE CONCERN (Messstand in `docs/superpowers/specs/2026-10-16-phase1-pacing-entscheidungen.md`): Mit der kleineren Abschlussbedingung deckt der Startbestand die Bedarfskette fast allein; Phase 1 endete ohne Gegenmaßnahme deutlich vor dem Zielfenster. Phase 1 ist in allen Messungen regolithgebunden, und jede Zeit, die über Regolith (Kosten oder späteres Einkommen) erzeugt wird, verzögert den Harvester-Anlauf. Gewählt ist deshalb der **AP-Deckel pro Ziel und Sol** (13.2) als Zeit-Bremse; Startbestand und Regolith-Kosten bleiben Feinabstimmung. Nicht gewählt: Startbestand senken (Regolith statt Zeit bindet, der Spieler startet knapp), ein zusätzliches Pflichtgebäude (verengt die Eröffnungsfreiheit der drei Pfade), ein Reparatur-Sink (wirkt wie Strafe) und die Bindung der Berater-Einstellung an eine AP-Aufgabe (bremst die Handlungsfähigkeit, die Phase 1 aufbauen soll). Offen bleibt, ob das Fenster nur über Bot-Verhalten erreichbar ist (Erkundung gehört zum AP-Verbrauch des Bots, ist für Gebäude-Platzierung aber nicht nötig).

**Herleitung:** Der Ertrag hängt vom Ertrags-Tier des gewählten Start-Vorkommens ab, die Standzeit vom Mächtigkeits-Tier, und der Spieler wählt bei Sol 1 zwischen zwei bekannten Vorkommen mit Zielkonflikt (§4c). Der Startbestand wird deshalb nicht aus einem Rechenwert gesetzt, sondern **empirisch am Median** kalibriert: PlaytestBot über einen Seed-Satz, Messgröße `phase2_start_sol` (Phase-1-Ende). Der Bot nimmt dabei jeweils das Vorkommen, das seine Regel wählt — damit streut die Messung automatisch über die neuen Kartenvarianten.

**Kalibrierung:** Der Startbestand ist so gewählt, dass das Phase-1-Ende im Zielkorridor liegt; die Streuung zwischen guter und schlechter Karte bleibt als gewollte Variabilität (G5) stehen. Die Messung gegen die Grundaufbau-Bedingung steht aus.

**Keine Sturm-Reserve:** Der Startbestand enthält keinen Aufschlag als Reserve gegen einen Kritisch-Treffer in Phase 1. Stürme treffen weiterhin (die Rampe dämpft die Chance, §9), aber die Kalibrierung zielt auf den Korridor-Mittelwert statt auf eine Pufferlage an dessen unterer Kante; ein Treffer verschiebt das Phase-1-Ende innerhalb des Korridors nach hinten, bleibt aber vor der Fail-State-Grenze (Sol 20). *(Mit der auf Sol 20 vorgezogenen Frist ist der Puffer klein: ein Sturm-Treffer plus schwaches Start-Vorkommen kann die Frist erreichen — ⚠️ BALANCE CONCERN, per Bot-Batch prüfen.)*

**Schwaches Start-Vorkommen** (niedriges Ertrags- und Mächtigkeits-Tier): Das Vorkommen erschöpft sich vor Phase-1-Ende, eine frühe Zwangsverlegung ist eingebaut — gewollte Variabilität (G5), keine zu behebende Lücke; der Startbestand wird nicht auf diesen Fall ausgelegt.

**Bewusst nicht angefasst:** `resource_max` der mittleren Tile-Kombinationen (bindet im Zielfenster nicht — kumulierte Extraktion beim Floor-Sol liegt deutlich unter der Mengengrenze; eine Anhebung kostete nur Umzugstakt, §4c); `fresh_yield` (mehr Rate = schnellerer Vorlauf auf dieselbe Mengenwand, kürzere Standzeit); Pfadgebäude-Preise (pro Gebäude gegen G4 kalibriert, das Phase-1-Problem ist kumulativ); CC-Ausbaukosten in Regolith (bleiben, zusätzlich kommen steigende AP, siehe „Taktgeber CC"); Hire-Credits (nicht bindend, und eine Senkung würde den späteren Rang-2/3-Unterhaltsdruck abschwächen).

**Empirisch:** Ein Bot-Befund außerhalb des Korridors ist nur dann ein Gegenbeweis gegen den Startbestand, wenn der Bot nach dem Errichten eines Pfadgebäudes dessen Lv0→1-Sprung tatsächlich zuerst fertigstellt (wie ein menschlicher Spieler) — Bot-Ausführungsdefekte sind kein Balance-Hebel.

**Nebenbefunde:**
- Der Wohnraum zwingt in Phase 1 keine zweite Wohnhabitat-Instanz und kein Wohnhabitat-Levelup mehr: Der Sol-1-Cap (CC + Wohnhabitat Lv1) deckt Harvester, Agrardom Lv1 und ein Pfadgebäude auf Lv1 mit Puffer. Das zweite Pfadgebäude in Phase 2 setzt das Wohnhabitat-Levelup voraus — Prüfauftrag. Die folgende Überlegung gilt weiter für diesen Phase-2-Übergang: Das gilt für jede Pfad-Kombination, aber nur solange alle drei Pfadgebäude denselben Arbeitsplatzbedarf tragen (Pfad-Parität, §4b). Kostet ein Pfadgebäude mehr als die anderen, muss das Wohnhabitat-Levelup (das ohnehin zur Phase-1-Kette gehört) vor dem zweiten Pfadgebäude kommen. Die Kombination bestimmt dann die Reihenfolge. Zwei Zeitpunkte zählen, weil ein platziertes Gebäude seine Stufe-1-Arbeitsplätze schon auf Level 0 reserviert und seine Regolith-Errichtungskosten beim Platzieren vollständig bezahlt werden: Freie Kolonisten und Regolith müssen beim **Platzieren** reichen, nicht erst beim Lv0→1-Sprung. Jedes zusätzliche Gebäude in Phase 1 setzt das Wohnhabitat-Levelup ebenfalls voraus. *(Kenntnisse liefern keinen Cap, §6: ab Phase 2 kommt jedes weitere Cap-Wachstum allein aus Wohnbau — dessen AP- und Regolith-Kosten sind dort fest einzuplanen, Prüfauftrag in der Neumessung.)*
- Die Pfadgebäude sind in Regolith gleich, in AP nicht: Analytik-Labor und Hangar sind AP-Klasse „Groß", Cantina „Mittel". Da der AP-Deckel (13.2) je Gebäude gilt und der Pool in Phase 1 nicht der Engpass ist, ändert das kaum etwas am Pacing, ist aber eine Inkonsistenz gegen die Paritäts-Anforderung (§4b) — bei Gelegenheit prüfen.
- Das „harte" Agrardom-Gate für den CC-Lv2-Ausbau (§4) ist im Code umgesetzt (`investBuilding()`, `agrardom_required`).

#### Agrardom-Kurve: das obere Ende

Der Organika-Verbrauch skaliert über `food_need = intdiv(anwesende Kolonisten, supply_per_eater)` mit der **Ausbautiefe** der Kolonie — ein Rennen zwischen Agrardom-Level und Koloniewachstum, dazu Missionsproviant und Event-Kosten. Das ist genau der Mechanismus, den die Knappheitsordnung verlangt: Wer in die Tiefe baut, ohne den Agrardom nachzuziehen, gerät in den Mangel. *Zu prüfen ist nur das obere Ende:* Ab Agrardom Lv3 (max. Ausbaustufe) übersteigt die Produktion den Bedarf der Zielkolonie; ob die Kurve dort flacher auslaufen sollte oder Missionen und Events genug Zusatzlast erzeugen, gehört in dieselbe Herleitung wie der Regolith-Satz (`docs/game-reference.md#ressourcenverbrauch`).

#### Wenn sich die Zahlen als falsch erweisen — welche Stellschraube gilt

| Beobachtung im Playtest | Stellschraube | **nicht** |
|---|---|---|
| Regolith staut sich an (Bestand steigt monoton) | Baukosten anheben | Sockel senken — trifft die Instandhaltung mit und riskiert die Verfallsspirale |
| Regolith klemmt bei 0, Reparatur konkurriert dauernd mit Bauen | Sockel anheben | Reparaturkosten senken — sonst verschwindet der Verfall als Mechanik |
| Verfall wirkt wie Dekoration, folgenlos ignorierbar | `decay_rate` anheben (bewegt beide Währungen zugleich) | Reparaturkosten anheben — das entkoppelt Regolith und AP wieder |
| Instandhaltung fühlt sich spät schlaff an | levelskalierte Reparatur (`1 + floor((level−1)/3)` AP je SP, §13.5) | `decay_rate` global anheben — trifft das Early Game am härtesten |
| Ein Pfad hängt sichtbar zurück | den betreffenden **Hebel** anheben | Sockel oder Baukosten — die sind pfadneutral |
| Mehr als 4 Sole pro Run an Regolith blockiert (G5) | Startbestand, dann Errichtungspreise | die Hebel — sie greifen zu spät für die frühe Klemme |
| Phase 1 dauert typisch > Sol 17 | Startbestand (nur wenn die Bedarfskette gegen den Sol-1-Bootstrap-Zustand nachgerechnet ist), sonst Ausführungsfriktion suchen | `resource_max` — bindet im Zielfenster nicht |
| Phase 1 endet typisch vor Sol 12 (Minimum-Spieler) | AP-Deckel (Grundwert, 13.2), danach Startbestand; Regolith-Kosten nur als letzter Hebel | Phase-1-Bedingung wieder auf Lv3 anheben — Lv3 gehört bewusst in Phase 2 |

#### Wo dieser Satz unsicher ist

- **Die 60-%-Regel für die Hebel-Reife ist eine Setzung.** Sie fällt aus zwei Richtungen auf dieselbe Zahl, ist aber die erste, die im Playtest zu prüfen wäre. Metrik: Anteil des Regolith-Zuflusses aus dem Hebel je Pfad, Zielband 30–40 %.
- **Die Reibungspauschale von 15 % ist geraten.** Sie deckt Level-Down-Wiederaufbau, Harvester-Verlegungen und Fehlkäufe und ist direkt aus dem Bot-Report ablesbar.
- **Die Kolonisten-Achse ist bewusst nicht mitbewegt.** Die `supply_cost`-Werte sind gegen eine Wirtschaft kalibriert, in der Regolith knapper war. Wird Bauen leichter, wird der Wohnraum relativ zum bindenderen Limiter — was §6 entspricht, aber die Zielkolonie gegen den erreichbaren Cap gegenzuprüfen verlangt. **Das ist der nächste unconstrained durchzurechnende Zahlensatz** (ROADMAP Stufe 1d).

---

## 14. Vertrauenssystem

### Design-Absicht

Vertrauen ist das "weiche" Feedback-System der Kolonie. Es reagiert auf die Entscheidungen des Spielers — welche Gebäude gebaut werden, wie die Kolonie versorgt wird, welche Kenntnisse erarbeitet werden — und verstärkt oder schwächt die Kolonieleistung mit spürbaren, aber nicht spielentscheidenden Effekten.

Vertrauen ist kein zweites Ressourcenproblem, das der Spieler managen muss. Es ist ein stiller Bewertungsparameter: Wer eine ausgewogene, zivil-orientierte Kolonie aufbaut, wird belohnt. Wer Versorgung und Zivilinfrastruktur vernachlässigt, spürt das in einer moderaten Malus-Spirale.

### Wertebereich

Vertrauen bewegt sich auf einer symmetrischen Skala um einen Neutralwert, mit dem jede Kolonie startet. Beide Enden der Skala sind harte Grenzen — keine weitere Verschlechterung bzw. Verbesserung darüber hinaus. Exakte Grenzwerte: siehe `docs/game-reference.md` Abschnitt 14 (Trust-Bänder & Multiplikatoren) / `config/game.php → trust`.

**Bedeutungsbereiche:**

| Bezeichnung | Anzeige (UI-Hinweis) |
|-------------|----------------------|
| Hohes Vertrauen | "Euphorisch" |
| Positive Stimmung | "Zufrieden" |
| Neutral | "Stabil" |
| Unzufriedenheit | "Unruhig" |
| Krise | "Aufruhr" |

Die fünf Bänder sind von sehr negativ bis sehr positiv geordnet, symmetrisch um den Neutralwert — deckungsgleich mit den Bändern der Produktions- und AP-Multiplikatoren weiter unten. Exakte Schwellwerte ebenfalls in `docs/game-reference.md` Abschnitt 14.

### Berechnung (Sol-basiert)

Vertrauen wird einmal pro Sol **neu berechnet** — nicht akkumuliert. Das Vertrauen eines Sols ergibt sich aus der Summe aller aktiven Faktoren:

```
vertrauen = clamp(Σ(Gebäudeeffekte) + Σ(Forschungseffekte) + clamp(Σ(Schiffseffekte), Schiffs-Untergrenze, Schiffs-Obergrenze) + ereigniseffekte, Vertrauens-Untergrenze, Vertrauens-Obergrenze)
```

Exakte Grenzwerte: `docs/game-reference.md` Abschnitt 14 / `config/game.php → trust`.

`colony_resources.amount` (resource_id=12) wird nach der Berechnung auf den neuen Wert gesetzt.

Der Wert wird in **Tick-Schritt 9** (nach Ressourcenproduktion und Verpflegung) berechnet, da Vertrauen die Produktionswerte desselben Sols noch nicht beeinflusst — es wirkt ab dem nächsten Sol.

> **Implementierungsnotiz:** Die Sol-Reihenfolge bedeutet, dass ein Spieler erst nach 2 Solen die volle Wirkung einer vertrauensverändernden Aktion sieht. Das ist akzeptables Design (kein Exploit durch Last-Minute-Bauweise).

### Einflussfaktoren: Gebäude

Jedes gebaute Exemplar eines Vertrauensgebäudes trägt mit einem fixen Wert pro Level bei. Nur Gebäude mit `status_points > 0` zählen (verfallene Gebäude tragen nicht bei).

**Positive Vertrauensgebäude:**

| Gebäude-ID | Bezeichner | Vertrauensbeitrag/Level |
|------------|------------|--------------------------|
| 32 | temple (Religiöse Stätte) | positiv |
| 46 | infirmary (Krankenstation) | positiv, am stärksten |
| 50 | monument (Kolonialdenkmal) | positiv |
| 52 | bar (Cantina) | positiv |

Exakte Werte pro Level: `config/buildings.php → trust_per_lv` / `docs/game-reference.md`.

**Negative Vertrauensgebäude:**

*(keine in Phase 3 — alle verbleibenden Gebäude sind neutral oder positiv)*

**Rationale:** Die Cantina wurde als sozialer Treffpunkt konzipiert — ein wichtiger Ort für das Gemeinschaftsgefühl einer kleinen Kolonie. Militärischer Druck wirkt über Schiffe und Kenntnisse, nicht über Gebäude.

> ⚠️ BALANCE CONCERN: Wenn ein Spieler alle positiven Gebäude maximal ausbaut (temple + infirmary + monument + bar auf Höchstlevel), ist das theoretische Maximum allein durch Gebäude sehr hoch. Der Clamp am oberen Ende der Skala verhindert Überlauf, aber der Vertrauen-Cap sollte beim ersten Playtest evaluiert werden ob er zu schnell erreichbar ist.

### Einflussfaktoren: Schiffe

Schiffe tragen zum Vertrauen bei, solange sie einer Kolonie zugewiesen sind (d.h. `colony_ships.amount > 0`). Der Effekt gilt **pro Schiff**, nicht pro Level. Ein Frachter steht für Handel und Versorgung; Drohne und Korvette sind neutral.

| Schiff-ID | Bezeichner | Vertrauensbeitrag/Schiff |
|-----------|------------|---------------------------|
| 85 | drone | neutral |
| 37 | korvette | neutral |
| 47 | frachter | positiv |

Exakte Werte: `config/ships.php → trust_per_unit` / `docs/game-reference.md`.

**Rationale:** Der Frachter steht für Handel und Versorgung — die Kolonisten sehen ihn als Zeichen normaler Aktivität. Die Korvette ist neutral: Kolonisten begrüßen ein Mindestmaß an Schutz, empfinden ein einzelnes Schiff aber nicht als Bedrohung. Drohnen sind unbemannte Geräte ohne emotionale Wirkung.

**Skalierungsproblem:** Da Schiffszahlen potenziell groß werden können, wird der Gesamtbeitrag aller Schiffe auf einen Höchstwert gecapped, bevor er in die Vertrauen-Summe eingeht:

```
ship_vertrauen = clamp(Σ(ship_amount × vertrauen_per_ship), 0, Schiffs-Obergrenze)
```

Exakter Deckel: `config/game.php → trust.ships_cap` / `docs/game-reference.md` Abschnitt 14.

### Einflussfaktoren: Forschungen

Forschungen tragen mit einem Pauschalwert pro Level bei (unabhängig von status_points, da Forschungslevel persistenter sind).

| Kenntnis-Key | Bezeichner | Vertrauensbeitrag/Level |
|--------------|------------|--------------------------|
| agronomy | Agronomie & Kultivierung | positiv |
| health | Gesundheit & Wohlbefinden | positiv, am stärksten |
| defense | Verteidigung & Überlebenstaktik | positiv |

Alle anderen Kenntnisse (construction, cartography, geology, trade) haben keinen direkten Vertrauenseffekt — sie sind neutrale Werkzeuge. Exakte Werte: `config/knowledge.php → trust_per_lv`.

**Rationale:** Agronomie und Gesundheit verbessern spürbar das koloniale Wohlbefinden. Verteidigung als Kenntnis schafft Sicherheit und stärkt dadurch das Vertrauen der Kolonisten — "Sicherheit schafft Vertrauen". Eine gut ausgebaute, zivile Sicherheitsvorsorge (Sturm-Risiko-Reduktion, der andere `defense`-Effekt) wird durchgängig positiv gerahmt, ohne Trade-off-Zwang. (Präzedenzfall: `geology` hat ebenfalls zwei Vorteile ohne Vertrauensmalus.)

### Einflussfaktoren: Relaisvergütung

Die Relaisvergütung (§3) ist eine reine Nexus-Einnahme **ohne automatischen Vertrauenseffekt** — sie fließt von Nexus an die Kolonie, nicht umgekehrt, und stellt für sich genommen keine Belastung der Kolonisten dar. Einen passiven Abzugs-/Steuermechanismus mit Vertrauensmalus gibt es nicht.

Was sich ändert: Der Spieler kann eingenommene Credits — ob aus Relaisvergütung, Handel oder Reserven — jetzt **aktiv** in Vertrauen zurückverwandeln. Das ist kein passiver Nebeneffekt der Relaisvergütung selbst, sondern eine eigene, bewusst gewählte Aktion — siehe **Kolonisten-Zulage** im nächsten Abschnitt.

### Einflussfaktoren: Kolonisten-Zulage (Spieleraktion)

Aktive Aktion des Direktors: Ein Teil der Kolonie-Credits kann jederzeit direkt an die Kolonisten ausgeschüttet werden — eine spürbare, bewusst gewählte Ausgabe, die zeigt, dass es der Siedlung wirtschaftlich gut genug geht, um sie unmittelbar zu beteiligen. Anders als Vertrauensgebäude oder Kenntnisse (permanente Dauerboni) ist die Zulage ein **reaktiver Hebel**: kein Dauerzustand, sondern eine situative Entscheidung — z. B. um vor einem kritischen Sol (Nexus-Meilenstein §15, drohende Vertrauens-Fail-Schwelle §15) Vertrauen zu stabilisieren, auf Kosten von Credits, die sonst in Ausbau, Berater-Upkeep oder Handel geflossen wären.

**Staffelung:** drei Stufen (Klein, Mittel, Groß), jeweils mit eigenem Event-Key (`stipend_small`, `stipend_medium`, `stipend_large`) — Kosten und Vertrauens-Bonus steigen mit der Stufe. Exakte Beträge: `docs/game-reference.md` Abschnitt 12–13 / `config/game.php → trust.events` und `stipend.tiers`.

Die Wirkung folgt der Standard-Event-Logik (siehe "Einflussfaktoren: Ereignisse" unten): sie wirkt im ersten Sol voll und klingt danach je nach Nachwirkungsprofil aus (Standard: nur dieser eine Sol).

**Nur eine Zulage pro Sol.** Die drei Stufen sind unterschiedliche Event-Keys (nicht Varianten desselben Keys) — das bestehende Dedup in `TrustService::eventContribution` fasst nur *gleiche* Keys zusammen und summiert *unterschiedliche* Keys auf. Ohne zusätzliche Sperre könnten z. B. "Klein" und "Groß" im selben Sol zu einem kombinierten Vertrauens-Bonus addiert werden. Das ist **nicht gewollt** und muss bei der Implementierung als eigene Regel ergänzt werden: pro Kolonie und Sol ist höchstens eine Zulagen-Stufe auslösbar (Fire-Time-Guard im Service, nicht im bestehenden Event-Dedup). Dieser Punkt ist ein expliziter Implementierungs-Hinweis, kein bereits vorhandenes Verhalten.

**Kein Cooldown über mehrere Sole hinweg.** Die Staffelung ist bewusst **degressiv** (die Credits-Kosten pro Vertrauenspunkt steigen mit der Stufe) — je größer die Ausschüttung, desto ineffizienter pro Credit. Das macht tägliches Wiederholen unattraktiv, ohne eine künstliche Sperre zu benötigen: Wer regelmäßig die kleine Stufe zieht, zahlt einen laufenden Credits-Betrag für einen wiederkehrenden Vertrauens-Bonus — spürbar gegenüber der Relaisvergütung und dem Berater-Upkeep, aber nicht kostenlos. Zum Vergleich: der seltene Händler-Artikel "Vertrauensschub" (§12, `trust_boost`) liefert einmalig einen großen Vertrauens-Bonus für einen Credits-Betrag — die Kolonisten-Zulage ist bewusst *weniger* effizient pro Credit, da sie jederzeit verfügbar ist und die übrigen Vertrauensfaktoren (Gebäude, Kenntnisse, Verpflegung) nicht verdrängen soll. Exakte Werte für den Vergleich: `docs/game-reference.md` Abschnitt 11–13.

**Rationale:** Die Zulage gibt dem Spieler einen direkten, jederzeit verfügbaren Hebel auf Vertrauen — aber zu einem Preis, der die Entscheidung "Vertrauen jetzt sichern" gegen "Credits in Ausbau/Handel investieren" tatsächlich schwer macht. Die degressive Staffelung verhindert, dass die große Stufe zur Standardwahl wird; die Einmal-pro-Sol-Regel verhindert Kombination innerhalb eines Sols. Zusammen ersetzt das einen Cooldown, ohne die Reaktionsfreiheit des Spielers einzuschränken.

> ⚠️ BALANCE CONCERN: Ohne harten Mehr-Sol-Cooldown ist die Kolonisten-Zulage im Lategame (hohe Credits-Reserven) potenziell ein "Vertrauen auf Knopfdruck"-Ventil, das die Vertrauens-Fail-Schwelle (§18.2) entschärft. Nach dem ersten Playtest prüfen, ob ein Soft-Cap (z. B. max. 1 Zulagen-Event pro N Sole) nötig wird, falls Spieler die Mechanik nutzen, um Krisen risikofrei auszusitzen statt echte Ursachen (Hunger, Decay, Militarisierung) zu beheben.

### Einflussfaktoren: Verpflegung (Organika)

Die Kolonie verbraucht jeden Sol Organika zur Versorgung (§3, Tick-Schritt 6). Zwei Vertrauenswirkungen:

- **Gesättigt** → `well_fed`-Event (Standard-Event-Logik, 1 Sol).
- **Hunger** (Vorrat deckt den Bedarf nicht) → **eskalierender** Malus, abhängig von `glx_colonies.hunger_streak` (aufeinanderfolgende Hunger-Sole):

```
Vertrauens-Malus = −min(Basis-Malus + (streak−1), Malus-Deckel)
```

Anders als gewöhnliche Events eskaliert dieser Malus, solange der Hunger anhält, und verfällt erst beim Sättigen (Streak → 0). Er wird in `TrustService::calculateTrust` als eigener Summand addiert, nicht über die Event-Tabelle (die nicht stackt).

Die Hunger-Spirale wird eskalierend bestraft — ein anhaltender Hunger führt zu einem progressiv stärkeren Vertrauens-Malus (kein Cap beim Eintreten, aber ein Deckel nach einigen Solen). Dies erzeugt einen echten Druck, die Organika-Produktion auf mindestens dem ersten Ausbaulevel zu halten. Der Feedback-Loop: leerer Agrardom → Vertrauensverfall → Produktions-/AP-Malus → noch weniger Organika möglich. Der Agrardom wird damit zum Pflichtgebäude. Exakte Malus-Werte: `config/game.php → food` (`hunger_base_malus`, `hunger_step`, `hunger_cap`) / `docs/game-reference.md` Abschnitt 15.

### Einflussfaktoren: Ereignisse (Events)

Events können Vertrauen temporär verändern. Die Wirkung beginnt im ersten Sol voll und **klingt über mehrere Sole aus** (Nachwirkung, Spec `docs/superpowers/specs/2026-10-23-vertrauens-nachwirkung.md`). Wie lange und wie stark, legt ein Profil je Ereignis-Typ fest: der Anteil des Ereigniswertes pro Sol seit Auslösung. Ereignisse ohne Profil (derzeit alle) wirken nur einen Sol; die Nachwirkung wird erst nach einer Messung scharf geschaltet. Event-Vertrauenswerte werden nicht in `colony_resources` gespeichert, sondern bei der Sol-Berechnung aus den Ereigniszeilen neu berechnet.

**Regeln der Nachwirkung:**
- Gleicher Typ im selben Auslöse-Sol zählt nur einmal (der stärkste). Gleicher Typ aus verschiedenen Solen **addiert sich**, solange sich die Nachwirkungen überlappen.
- Die Spitze bleibt erhalten, die Nachwirkung kommt zusätzlich; die Mehrwirkung wird gemessen.
- Jede Ereigniszeile wird einzeln gerundet (von der Null weg), damit angezeigte Zeilen und Summe übereinstimmen.
- Ein optionaler Deckel begrenzt den Gesamt-Ereignisanteil nach oben und unten (Sicherheitsnetz gegen Rückschlag-Ketten); seine Höhe wird gemessen.
- Der Leitstellen-Abschlag wirkt auf den Ereigniswert vor dem Profil, also auf die ganze Nachwirkung.
- `well_fed` bleibt beim Ein-Sol-Profil (täglich wiederholt, sonst ein versteckter Dauerbonus). Das Stipend wirkt im ersten Sol voll; die Nachwirkung kommt obendrauf, die Preis-Leistung wird nach der Messung geprüft.
- Die Kommandozentrale zeigt die wirkenden Ereigniszeilen mit Verlauf („danach +1, +1“) und der Summe.

Datenmodell: Ereignisse stehen in `trust_events` (Kolonie, Sol, Typ). Beim Speichern meldet das Spiel sie zusätzlich als Laravel-Event, damit Beobachter (z. B. Achievements) sofort reagieren können (ADR 0007); die Berechnung bleibt rundenbasiert.

**Geplante Event-Kategorien:**

Events sind nach Kategorie gruppiert: Bauwesen/Forschung (Gebäude-Stufe oder Kenntnis abgeschlossen; ein Stufenverlust durch Verfall oder Rückbau hat bewusst kein eigenes Vertrauens-Event, seine Folgen laufen über das System: sinkende Produktion, wegfallender Stufenbeitrag von Vertrauensgebäuden, ggf. unbesetzte Arbeitsplätze, §6/§7), Handel und Diplomatie (reserviert, derzeit ohne Auslöser: `treaty_signed`), Begegnungen (Zwischenfälle gelöst oder eskaliert), Spieleraktionen (freiwillige Kolonisten-Zulagen). 

Standard: Ein Ereignis wirkt einen Sol; mit einem Nachwirkungsprofil (s. o.) klingt es länger aus. Mehrere Ereignisse desselben Typs im selben Auslöse-Sol zählen nur einmal — es gilt der stärkste Wert.

Die konkreten Vertrauenseffekte pro Event-Typ (Malus für Fehlschläge und Zwischenfälle, Bonus für Erfolg oder Zuwendung) stehen in `config/game.php → trust.events.*` — exakte Werte nach erstem Playtest kalibrieren.

> **TODO:** Exakte Vertrauenswerte für Begegnungs-Events nach §9-Ausarbeitung kalibrieren. Event-Keys sind in `TrustService` als `game.trust.events.*` angelegt (CLAUDE.md Korrekturen-Sektion); Werte nach erstem Playtest festsetzen. Die **Leitstelle** dämpft `encounter_lost` und `colony_threatened` um einen Prozentsatz, wenn aktiv — das macht ihre genauen Werte doppelt relevant. Exakter Dämpfungswert: `config/buildings.php` (securityHub).

**Rationale für neue Events:**
- Handelsbeschränkungen gibt es nicht, ein Ereignis `trade_blocked` existiert nicht. `treaty_signed` ist angelegt, wird aber derzeit von keiner Spielregel ausgelöst (Verträge kommen frühestens mit den Cantina-Vereinbarungen, ROADMAP Ideenpool).
- `building_level_up` und `research_level_up` werden ausgelöst, sobald eine Gebäude- bzw. Kenntnis-Stufe fertig wird (Wirkung ab dem nächsten Sol, wie jedes Ereignis).

> ⚠️ BALANCE CONCERN: Event-Vertrauenseffekte für Bauwesen sind einmalig (pro Level-Up). Ein Spieler der täglich Gebäude baut, erhält täglich einen kleinen, aber stetigen Bonus, der aktives Spielen belohnt. Ob das ausreicht als Motivation oder ob der Effekt erhöht werden sollte, ist nach erstem Playtest zu evaluieren.

### Effekte des Vertrauens auf die Kolonie

Vertrauen beeinflusst drei Spielparameter. Alle Effekte werden als **Multiplikatoren** auf die Basiswerte angewendet, nicht als additive Boni. Das verhindert, dass Vertrauen zu einer dominanten Wachstumsstrategie wird.

#### Ressourcenproduktion

Vertrauen wirkt als Multiplikator auf die Rohstoffproduktion (Harvester, Agrardom). Hohes Vertrauen gibt einen moderaten Produktionsbonus; niedriges Vertrauen reduziert den Output. Das skaliert die Produktion ohne sie zu blockieren — der Multiplikator bleibt immer > 0.

#### AP-Multiplikator

Vertrauen wirkt auch auf die effektiven Aktionspunkte pro Sol — ein schwächerer Effekt als bei der Produktion. AP ist die knappste Ressource, daher soll Vertrauen sie nicht zu stark verstärken (kein Dominanz-Stacking).

> ⚠️ BALANCE CONCERN: Ein starker AP-Malus bei negativem Vertrauen macht Krisensituationen selbstverstärkend (weniger AP → weniger Reparaturen → mehr Decay → mehr Vertrauens-Malus). Diese Spirale ist designtechnisch vertretbar (Entropie als Spielprinzip), aber es muss einen Ausweg geben. Der Ausweg ist der Bau von Vertrauensgebäuden (positive Events) und Kolonisten-Zulagen (Spieler-Aktion), beides funktioniert trotz AP-Malus (AP wird nicht negativ).

Exakte Multiplikator-Werte pro Vertrauensbereich: `config/game.php → trust.production_multiplier` / `trust.ap_multiplier`.

#### Wohnraum

Vertrauen beeinflusst den Wohnraum **nicht**. Das Kolonisten-System ist ein separater Constraint (Wohnhabitate, CC) und soll nicht durch ein weiteres System kompliziert werden. Beide Systeme bleiben orthogonal.

### Schema-Bedarf

**Kein neues Schema erforderlich.** `colony_resources.amount` (resource_id=12) speichert den aktuellen Vertrauenswert als Integer innerhalb der Skala aus "Wertebereich" oben. Das ist ausreichend — Vertrauen ist ein Zustand, keine akkumulierte Menge.

**Die Konfiguration** steht produktiv in `config/game.php` unter dem Schlüssel `trust` (Umbenennung von `moral`→`trust` abgeschlossen). Die vollständigen Werte (buildings, researches, ships, ships_cap, production_multiplier, ap_multiplier, events) sind dort implementiert — `config/game.php` ist die einzige Quelle der Wahrheit für alle Zahlenwerte. Dieses Dokument beschreibt die Semantik; die konkreten Zahlen stehen in der Konfigurationsdatei.

### Sol-Integration

Vertrauen wird in **Tick-Schritt 9** nach Ressourcenproduktion und Verpflegung berechnet (Nummerierung siehe `GameTick.php`):

| Schritt | Beschreibung |
|---------|-------------|
| 5 | Resource Generation — Rohstoffproduktion (mit altem Vertrauen-Multiplikator) |
| 6–7 | Verpflegung, Begegnungen |
| 8 | Wohnraum neu berechnen |
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

Diese Erweiterungen erfordern kein Schema-Refactoring, da der Grundwert (feste Skala, siehe "Wertebereich" oben) in `colony_resources` stabil bleibt.

---

## 15. Run-Struktur (Roguelike-Modus)

### Konzept

Jede Partie von Nouron ist eine abgeschlossene **Expeditionsmission**. Es gibt kein Endlosspiel — ein Run hat einen definierten Anfang, ein Ziel und ein Ende. Das Roguelike-Prinzip: Nach jedem Run (Sieg oder Niederlage) startet der Spieler von vorne. Highscore entsteht durch Effizienz (wie schnell wurden die Aufgaben erfullt) und Restressourcen.

---

### Phasenstruktur

**Empfehlung: 2 Phasen** — mehr Phasen wurden bei diesem Scope zu viel Struktur erzeugen und das FTL-artige Momentum bremsen.

#### Phase 1 — "Kolonie stabilisieren" (Pflicht)

Dauer: Ziel Sol 12–13 für den Minimum-Spieler, Sol 14–16 für Menschen mit Extras; Fail State 4 bei Sol 20. Ein Regolith-Puffer für ein bis zwei Extra-Gebäude oder -Stufen bleibt am Ende erhalten. Die Zeit entsteht durch den AP-Deckel pro Ziel und Sol (§13.2), der den Ausbau ohne Eingriff ins Regolith-Einkommen streckt. Kann nicht übersprungen werden. Phase 1 ist der **Grundaufbau**: eine lebensfähige, selbsttragende Kolonie mit Versorgung, Regolith-Einkommen, Wohnraum und dem ersten Pfad — nicht mehr. Tiefe (CC Lv3, Pfadgebäude-Stufen, zweiter Pfad, dritter Berater) ist Stoff von Phase 2.

**Startzustand (jeder Run):**
- CommandCenter Level 1 — bereits gebaut, betriebsbereit
- Harvester Level 1 — bereits gebaut, produziert sofort Regolith
- Moderate Startressourcen (Credits für erste Ankäufe, Regolith für Baustoff). Weitere Rohstoffe starten bei Null oder niedrig.
- Der Spieler kann direkt mit dem Bau von Zusatzgebäuden beginnen.

**Abschlussbedingungen (BEIDE müssen erfüllt sein):**

Phase 1 endet, wenn: (1) der Grundaufbau steht — Kommandozentrale auf Lv2, dazu Agrardom, Harvester, Wohnhabitat, Uplink-Station und das erste Pfadgebäude, jeweils mindestens Lv1 — und (2) Personal vorhanden ist (mindestens zwei Berater aktiv: der Baumeister und der Berater des ersten Pfades). Die beiden Bedingungen sind eindeutig messbar und werden im Spieler-Interface angezeigt.

Die zwei Bedingungen decken die Kernsysteme ab: Aufbau (Gebäude) und Handlungsfähigkeit (AP). Sie sind eindeutig messbar und fur Neuspieler verstandlich.

Phase 1 endet automatisch, sobald beide Bedingungen gleichzeitig erfüllt sind. Der Übergang wird mit einem eigenen **Erfolgsbildschirm** gefeiert (der Grundaufbau steht, kurz zusammengefasst, was erreicht wurde), dazu kommt die Nexus-Nachricht; danach beginnt Phase 2.

> **TODO (Design):** Optionale dritte Bedingung für Phase 1 — könnte pro Run variieren (Roguelike-Element). Beispiele: "erster Handel abgeschlossen", "eine Kenntnis auf Lv2", "erste Mission entsandt". Das würde jeden Run-Einstieg leicht unterschiedlich anfühlen lassen. Bei Implementierung hier ergänzen.

#### Phase 2 — "Expeditionsmission"

Startet direkt nach Phase 1. Auftakt ist der Ausbau der Kommandozentrale auf Lv3: Er öffnet (über das Stufen-Gate) Pfadgebäude-Stufen 2 und 3, das zweite Pfadgebäude und damit Berater-Slot 3 (§13 „Slot-System"). Dem Spieler werden 3 Aufgaben aus dem Aufgabenpool zugewiesen (zufällig oder aus vordefinierten Sets). **2 von 3 müssen bis zu einem Run-spezifischen Sol erfullt werden.**

**Runlänge:** Die Frist beträgt 100 Sole (`run.tick_limit`). Ein Sieg um Sol 70–85 gelingt nur mit einem sehr guten Run und etwas Glück; realistisch fällt der Sieg zwischen Sol 85 und 95 (Owner-Vorgabe, Korridor-Tabelle in §18.4). Das ist lang genug für strategische Tiefe und kurz genug für Wiederholbarkeit. Dieser Korridor ist auch die Referenzgröße für alle AP- und Ressourcen-Balancingwerte.

**Konfiguration:** Jeder Run ist über `config/game.php → run` konfigurierbar: Frist (Gesamtsole), Aufgabenpool und -parameter, Kontrollpunkte der Nexus-Leiter, Countdown und Score-Gewichte.

---

### Aufgabenpool

8 Aufgabentypen bilden den Pool. Pro Run werden 3 zufällig gezogen, das reduziert das Wiederholungsgefühl. Alle Aufgaben sind zivil erfüllbar (es gibt keinen Kampf, keine Flotten und keine Systemkarte, §8). Jede Aufgabe passt zu vorhandenen Spielmechaniken.

**Kalibrierregel:** Alle Aufgaben sind gleich schwer, gemessen an einer gemeinsamen Regel:
- Wer **nicht** auf eine Aufgabe hinspielt, erreicht sie frühestens um Sol ~90 oder gar nicht.
- Wer **gezielt** auf sie hinspielt, erreicht sie um Sol 70–85.

Es gibt keinen Mindest-Sol, ab dem eine Aufgabe erst zählt. Die Varianz zwischen Runs entsteht über zwei Dinge: wie gut die gezogenen Aufgaben zum gewählten Pfad (§4b) passen, und wie die Karte ausfällt. Eine Aufgabe, die der PlaytestBot ohne gezielte Strategie deutlich vor Sol 90 „mitnimmt", ist zu leicht. Eine, die er auch gezielt nicht bis Sol 85 schafft, ist zu schwer.

| Aufgabe (`task_key`) | Kategorie | Kernmechanik |
|---|---|---|
| Handelsnetz (`task_trade_volume`) | Wirtschaft | Abgeschlossene Transaktionen mit dem Reisenden Händler im laufenden Run über einer Schwelle |
| Forschungsvorsprung (`task_research_lead`) | Forschung/Aufbau | Mindestens einige Kenntnisse auf Höchstlevel gebracht |
| Kolonieblüte (`task_colony_prosperity`) | Diplomatie/Zivilaufbau | Vertrauen über einer Schwelle für mehrere aufeinanderfolgende Sole |
| Selbstversorgung (`task_self_sufficiency`) | Überleben | Regolith- **und** Organika-Vorrat gleichzeitig über ihren jeweiligen Mindestschwellen. Beide Bedingungen müssen gleichzeitig für mehrere aufeinanderfolgende Sole gelten, jeder einzelne Ausfall setzt die Serie zurück |
| Expeditionsstatus (`task_expedition_coverage`) | Exploration/Navigation | Erfolgreich abgeschlossene Außenmissionen (§8b, Bergungsflüge eingeschlossen) ab Schwierigkeit „normal" über einer Schwelle. Leichte Missionen und Fehlschläge zählen nicht |
| Ingenieursleistung (`task_engineering_output`) | Forschung/Aufbau | Summe der Ausbaustufen aller Gebäude der Kolonie über einer Schwelle. Jede Instanz zählt mit ihrer Ausbaustufe, so zählen Breite (mehr Instanzen) und Tiefe (höhere Stufen) gleich |
| Kreditreserve (`task_credit_reserve`) | Wirtschaft | Credits-Bestand über einer Schwelle für mehrere aufeinanderfolgende Sole: anhaltender Wohlstand statt eines einmaligen Peaks |
| Expertenstab (`task_senior_advisors`) | Personal | Eine volle Berater-Mannschaft, deren Mitglieder alle den höchsten Rang erreicht haben |

**Quelle der Wahrheit:** Alle Aufgaben-Parameter (Kategorie, Typ Zähler/Serie, Zielwert, Schwellen, Serienlänge) liegen in `config/game.php → run.tasks` und werden über `RunTaskCatalog` gelesen. Die Aufgaben-Beschriftung im UI wird aus denselben Werten gebaut, die angezeigte Zahl ist also die gemessene. Die aktuellen Werte stehen in `docs/game-reference.md#18-run-struktur` und sind vorläufig bis zur Kalibrierung. Die Kalibrier-Historie steht in §18.4.

> **Implementierungsstand (A45, umgesetzt):** Alle Aufgaben-Parameter kommen aus `run.tasks`, nicht aus Konstanten in `RunProgressService`. `task_engineering_output` misst die Summe der Ausbaustufen, `task_expedition_coverage` erfolgreiche Missionen ab der Mindestschwierigkeit, und `task_senior_advisors` zählt Berater ab dem Mindestrang.

> **Folgeschritt:** `task_credit_reserve` bleibt als Serie im Pool. Damit sie erreichbar wird, werden die Credit-Belohnungen von Außenmissionen und Ereignissen angehoben: Die Kreditreserve soll über aktives Spielen (Missionen, Ereignisse) entstehen, nicht über reines Sparen. „Nexus-Vorschuss tilgen" wird ein **zusätzliches** Ziel im Pool, sobald es eine Tilgungsmechanik für `nexus_debt` gibt (siehe Nexus-Schulden-Mechanik unten).

> ⚠️ BALANCE CONCERN: Aufgaben-Sets sollten mindestens 2 verschiedene Kategorien abdecken, damit ein Run nicht ausschließlich Wirtschaftsaufgaben zieht (`task_trade_volume` + `task_credit_reserve` sind beide Wirtschaft). Die Kombo-Blacklist ist implementiert (höchstens 1 Wirtschafts-Aufgabe pro Ziehung; Kategorie je Aufgabe in `run.tasks`).

---

### "2 von 3"-Mechanik

**Bewertung: gut.** Die Mechanik gibt dem Spieler echte Wahlfreiheit, ohne den Run zu trivial zu machen. Eine verfehlte Aufgabe beendet den Run nicht — das reduziert Frustration und fuhrt zu mehr strategischen Entscheidungen ("Welche zwei lohnen sich fur meine aktuelle Ausgangslage?").

**Kontrollpunkte gegen zu frühen Fokus-Verlust:** Nexus prüft in Phase 2 mehrfach, ob die Kolonie auf mindestens eine Aufgabe hinarbeitet. Geprüft wird **Fortschritt, nicht Erfüllung**. Der Grund: Laut Kalibrierregel erreicht auch ein gezielt spielender Direktor seine erste Aufgabe erst um Sol 70–85. Ein Kontrollpunkt, der zu dieser Zeit schon eine erfüllte Aufgabe verlangt, würde genau das beabsichtigte Tempo bestrafen.

Die Kontrollpunkte bilden eine **steigende Leiter**. Jeder Punkt verlangt mehr als der vorige, passend zum Tempo, in dem ein gezielt spielender Direktor seine zwei Ziel-Aufgaben voranbringt:

- **Phase-2-Sol 30:** Mindestens eine Aufgabe hat mindestens die Hälfte erreicht. Sonst folgt die erste Nexus-Warnung.
- **Phase-2-Sol 50:** Eine Aufgabe hat mindestens drei Viertel erreicht und eine zweite mindestens die Hälfte. Sonst folgt die zweite Nexus-Warnung.
- **Phase-2-Sol 65:** Eine Aufgabe ist erfüllt und eine zweite hat mindestens drei Viertel erreicht. Sonst folgt eine Sanktion (siehe „Gnadenfrist" unten).

**Lesart der Schwellen:** „Die Hälfte" und „drei Viertel" sind Mindestwerte. Wer genau auf der Schwelle steht, hat sie erreicht, es wird nicht gerundet. **Jede Anforderung muss eine andere Aufgabe erfüllen:** Eine einzelne Aufgabe auf 80 % deckt an Phase-2-Sol 50 nur die Drei-Viertel-Anforderung, nicht zusätzlich die Hälfte-Anforderung. Eine erfüllte Aufgabe zählt immer als volle 100 %.

Geprüft wird **genau am jeweiligen Phase-2-Sol**, einmal pro Run. Ein verpasster Kontrollpunkt wird nicht nachgeholt, und ein später aufgeholter Rückstand hebt eine bereits ausgesprochene Warnung nicht auf. Wer nach einer Warnung aufholt, besteht dafür den nächsten Kontrollpunkt. Die Folgen eskalieren: Warnung, Warnung, Sanktion.

**Bei Serien-Aufgaben** (Kreditreserve, Selbstversorgung, Kolonieblüte) zählt als Fortschritt die längste Serie im Run bis jetzt, nicht die gerade laufende. Ein einzelner schlechter Sol kurz vor dem Kontrollpunkt löscht also nicht den Nachweis, dass die Kolonie auf das Ziel hinarbeitet. Die Schwellen je Kontrollpunkt stehen in `run.nexus_checkpoints` (aktuelle Werte in `docs/game-reference.md#18-run-struktur`).

Alle Nexus-Kontrollpunkte zählen in **Phase-2-Sol** (Sole seit Phasenübergang), nicht in Gesamt-Sol. Die Tabelle steht in §18.4.

Die Warnungen sind weich (kein Fail, nur Feedback) und erzeugen Dringlichkeit ohne Frustration. **Nexus ist der Absender:** Die Nachrichten kommen nicht anonym vom System, sondern von der übergeordneten Instanz, die den Spieler ausgesandt hat.

> **Implementierungsstand (A45, umgesetzt):** Die Leiter wird aus `run.nexus_checkpoints` gelesen und prüft Fortschritt statt Erfüllung. Der beste Serienstand je Aufgabe wird gespeichert (`run_objectives.best_streak_value`) und zählt als Fortschritt.

> ⚠️ BALANCE CONCERN: Die Leiter ist an einem Phase-1-Ende um Sol 12–16 ausgerichtet; die folgende Rechnung ist daran neu zu prüfen. Phase-2-Sol 65 entspricht dann Gesamt-Sol 80–85, also dem oberen Ende des Einzel-Korridors „gezielt Sol 70–85". Ein solider Run, der realistisch erst um Sol 85–95 siegt (§18.4), liegt am Sanktionspunkt eventuell noch knapp unter „eine Aufgabe erfüllt". Der laufende Kalibrier-Batch muss zeigen, wie oft ein gezielt spielender Bot die Sanktion trotzdem auslöst. Trifft sie solide Runs regelmäßig, bestraft sie das beabsichtigte Tempo. Stellschraube ist dann die Anforderung an Phase-2-Sol 65, nicht die Aufgaben-Zielwerte.

---

### Spieler-Rolle: Der Direktor

Der Spieler trägt den Titel **Direktor** (oder Direktorin). So nennen ihn die Kolonisten — es ist die informelle, täglich gebrauchte Anrede.

Nexus-intern heißt die Position **Konzessionär**: jemand der eine Betriebslizenz von einer übergeordneten Instanz auf Zeit erhalten hat, vertraglich gebunden ist und selbst das Risiko trägt — weder einfacher Angestellter noch unabhängiger Eigentümer.

**Nexus** ist kein Staat und keine Armee — es ist ein interstellares Entwicklungskonsortium, das Kolonisierungsrechte vergibt, Startkapital vorschießt und am Ende Rechenschaft erwartet. Der Spieler hat eine Konzession unterzeichnet: Aufbau und Betrieb einer Siedlung auf einem zugewiesenen Planeten, für eine definierte Laufzeit, gegen Vorauszahlung in Credits. Was in der Konzession nicht steht: wie rau die Bedingungen vor Ort sind, was die Kolonisten wirklich brauchen, und wie wenig Nexus bereit ist zu helfen wenn es brennt.

**Das Startkapital ist wörtlich ein Vorschuss, kein Geschenk (Owner-Entscheidung F3):** Die Kolonie startet mit nutzbaren Credits — und in exakt gleicher Höhe gleichzeitig mit `nexus_debt`. Das ist ein bewusster Härtefaktor, kein Rechentrick: Er erzeugt von Sol 1 an denselben Druck, den das Frontier-Survival-Ton verspricht, und macht die Konzessions-Entzug-Drohung (Fail State 2, §18.2) von Anfang an glaubwürdig statt erst spät im Run relevant zu werden.

Der Direktor steht zwischen zwei Loyalitäten: den Kolonisten (Vertrauen) und Nexus (Schulden). Wer zu sehr für Nexus optimiert, verliert das Vertrauen der Siedler. Wer Nexus ignoriert, wird zurückgerufen. Das ist kein Widerspruch — das ist der Job.

---

### Nexus als Hintergrund-Akteur

Nexus ist nicht nur der narrative Rahmen des Runs — es ist ein aktiver, aber stiller Spielakteur. Es überwacht die Kolonie und interveniert an definierten Schwellwerten. **Alle Nexus-Interventionen sind einmalige Effekte — kein permanenter State-Flip.**

Kommunikationskanal: ausschließlich der Nexus-Funk. Nexus sendet keine Dialogfenster, keine Popups — nur Protokoll-Ereignisse mit Absender "Nexus Command".

#### Boni (wenn der Spieler ahead-of-curve liegt) — geplant, nicht umgesetzt

Nexus würde Kolonien belohnen, die ihre Milestone-Ziele übertreffen:
- Credits-Transfer ("Nexus genehmigt Betriebsmittelzulage")
- Temporärer AP-Boost eines Berater-Typs für 3 Sole
- Aufgaben-Variante wird leicht entspannt (moderater Zielwert-Abschlag, siehe `config/game.php`)

#### Sanktionen (wenn der Spieler hinter Plan liegt)

Nexus erhöht den Druck auf Kolonien, die Milestones verfehlen:
- Berater kurz abgezogen ("vorübergehend für administrative Zwecke einberufen") — temporärer AP-Kapazitätsverlust (umgesetzt)
- *Geplant, nicht umgesetzt:* kleine Credits-Gebühr ("Overhead für Missionsaufsicht")
- *Geplant, nicht umgesetzt:* Gnadenfrist-Verkürzung (siehe unten)

Sanktionen erscheinen nie ohne vorherige Nexus-Funk-Warnung.

#### Gnadenfrist

Der Countdown zum Missionsende ist sichtbar, sobald die letzten Sole vor dem Tick-Limit beginnen (`run.countdown_sols_before_limit`, §18.2 Fail State 3). Nexus tritt jetzt aktiver in Erscheinung (Phase-2-Sol, §18.4):

- **Phase-2-Sol 65:** Wenn die letzte Stufe der Kontrollpunkt-Leiter verfehlt ist (eine Aufgabe erfüllt, eine zweite mindestens drei Viertel, siehe „2 von 3"-Mechanik oben), folgt eine Sanktion: Ein zufälliger Berater wird kurzzeitig abgezogen. *Geplant, noch nicht implementiert:* zusätzlich eine Verkürzung des effektiven Endes („Nexus Command hat die Frist vorgezogen").
- **Gesamt-Sol `tick_limit` − `countdown_sols_before_limit`:** Countdown-Meldung (§18.2 Fail State 3), unabhängig vom Phase-2-Sol.
- *Geplant, noch nicht implementiert:* letzte Warnung 10 Sole vor dem Ende, falls immer noch keine Aufgabe erfüllt ist.
- **Tick-Limit:** Run endet mit Fail State 3.

Wer die Leiter-Stufe bei der Sanktionsprüfung erreicht, bleibt ohne Sanktion.

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
- Schulden akkumulieren durch: Startkapital (Vorschuss, initialer `nexus_debt`), Nexus-Deals (Schiffskauf auf Nexus-Kredit, §8b) und — Owner-Entscheidung F3 — jedes Berater-Upkeep-Defizit, das die Credits nicht mehr deckt können: Credits werden dabei auf 0 geklemmt statt negativ zu werden, das Defizit fließt stattdessen in `nexus_debt`. `nexus_debt` ist damit das alleinige Schulden-Ledger der Kolonie; Credits selbst bleiben immer nutzbares, nicht-negatives Kapital (§13.1).
- Keine Zinsen
- **Tilgung: gibt es derzeit nicht.** `nexus_debt` kann im laufenden Run nur wachsen. Positives Sol-Einkommen fließt vollständig in die Credits und senkt die Schuld nicht. Auch einen manuellen Rückzahlungsweg gibt es nicht. Überschüsse tilgen die Schuld nicht automatisch. Eine Tilgungsmechanik ist ein künftiger Schritt. Sie ist Voraussetzung dafür, `task_credit_reserve` in „Nexus-Vorschuss tilgen" umzubauen (Aufgabenpool oben). Ob die Tilgung automatisch oder als Spieleraktion kommt, ist offen.
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

Bei einem verlorenen Run ist der Score 0.

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
- `config/game.php → run`: Tick-Limit, Score-Formel-Gewichte, `run.tasks` (Aufgaben-Parameter, gelesen über `RunTaskCatalog`), `run.nexus_checkpoints` (Kontrollpunkt-Leiter) und `run.countdown_sols_before_limit` (Countdown).
- `run_objectives.best_streak_value`: bester Serienstand je Serien-Aufgabe im Run, zählt für die Kontrollpunkte als Fortschritt
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

Das Runziel ist von Anfang an Phase 2 kommunizierbar: "Schließen Sie 2 der folgenden 3 Aufgaben ab." Die Objectives werden beim Phase-2-Übergang gezogen. Das erste gezogene Ziel ist von Anfang an sichtbar; die übrigen bleiben verdeckt, bis ihr Fortschritt über null liegt (gestaffelte Enthüllung via §17.1 ab Phase 4). Der Übergang selbst zeigt im Sol-Bericht einen eigenen Erfolgsbildschirm („Phase 1 abgeschlossen“, mit den erreichten Grundaufbau-Punkten), danach den Phase-2-Dialog. Die Wahlfreiheit über welche zwei Objectives erfüllt werden, ist das zentrale Roguelike-Entscheidungsmoment eines Runs.

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

**Frühzeitiger Sieg belohnt Effizienz:** Die Score-Formel enthält `(tick_limit − done_tick) × 10` — ein Sieg bei Sol 75 ergibt mehr Punkte als derselbe Sieg bei Sol 95. Das schafft permanenten Anreiz für schnelles Spielen, ohne Erkundung und Aufbau zu bestrafen.

**Sieg ist nur in Phase 2 möglich:** `endRun('completed')` wird nur aufgerufen wenn `run.phase == 2`. In Phase 1 gibt es ausschließlich Fail States (Trust, Schulden, Zeit — letzterer praktisch nie, da Phase 1 deutlich kürzer als `tick_limit` dauern sollte — sowie Phase-1-Fristbruch, Sol 20, Fail State 4 unten, der einzige der vier Fail States, der ausschließlich in Phase 1 auslösen kann).

---

### 18.2 Fail States

Vier Fail States. Alle werden am Ende der Tick-Phase 5 geprüft, nach dem Objective-Update (damit ein Sieg auf demselben Tick immer Vorrang vor einem gleichzeitigen Fail State hat). Kanonische Implementierung: `RunProgressService::checkFailStates()`. Der vierte (Phase-1-Fristbruch) kann nur in Phase 1 auftreten — sobald Phase 2 erreicht ist, greifen ausschließlich die anderen drei.

#### Fail State 1 — Vertrauenskollaps

**Bedingung:** `trust < config('game.run.trust_fail_threshold')` — ein tief negativer Schwellwert, siehe `config/game.php → run.trust_fail_threshold`.

**Auslösung:** Instant in demselben Tick, in dem der Vertrauenswert unter die Fail-Schwelle fällt. Kein Streak erforderlich.

Begründung gegen eine Streak-Mechanikverzögerung: Trust unter der Fail-Schwelle bedeutet aktive Feindseligkeit der Kolonisten, keinen vorübergehenden Stimmungseinbruch mehr. Eine Streak-Wartezeit würde die Aussagekraft des Trust-Werts verwässern und den Spieler in einem faktisch verlorenen Zustand weiterspielen lassen.

**Warnstufen (Nexus-Funk/Protokoll + UI):**

| Schwellwert | Maßnahme |
|-------------|---------|
| Trust unter dem Neutralwert | Protokoll-Ereignis (Kolonist, Absender): "Die Stimmung in der Kolonie ist angespannt." — einmalig pro Run |
| Trust deutlich negativ | Roter Farbwechsel am Trust-Ressource-Chip in der Ressourcenleiste (dazwischen zeigt der Chip gelb als Vorstufe) |
| Trust nahe der Fail-Schwelle | Nexus-Funk-Warnung, einmalig pro Run: "Direktor, die Lage ist kritisch. Sofortige Maßnahmen erforderlich." (entfällt, wenn im selben Tick bereits der Fail State greift) |
| Trust unter der Fail-Schwelle | Fail State — Run endet sofort |

Exakte Schwellwerte: `config/game.php → run.trust_fail_threshold`, `run.trust_warning.chip_red`, `run.trust_warning.nexus_warning`.

> ⚠️ BALANCE CONCERN: Die Fail-Schwelle ist bewusst tief gesetzt. Ein mehrtägiger Hunger-Streak plus ein zusätzliches Level-Down-Event würde die Schwelle knapp nicht erreichen — das ist gewollt: Vernachlässigung soll spürbar bestrafen, aber erholbar bleiben. Nach erstem Playtest kalibrieren ob die Schwelle zu tief (Spieler scheitern selten) oder zu flach (Spieler scheitern überraschend schnell) ist.

**Narrativer Ausgang:** "Die Kolonisten haben das Vertrauen verloren. Der Direktor wurde abgesetzt."

---

#### Fail State 2 — Nexus-Schuldengrenze

**Bedingung:** `nexus_debt > config('game.run.nexus_debt_fail_threshold')`

**Auslösung:** Instant bei Überschreitung. Geprüft sowohl in `checkFailStates()` als auch direkt in `checkNexusInterventions()` (Phase-2-Sol 55).

**Warnstufen (UI-Schuldenbalken):**

| Schuldenstand | Maßnahme |
|---------------|---------|
| hoher Anteil des Limits erreicht | Schuldenbalken wechselt auf Gelb |
| kurz vor dem Limit | Schuldenbalken wechselt auf Rot; *geplant:* Nexus-Meldung „Kreditlimit fast erreicht." |
| Limit überschritten | Fail State — Run endet sofort |

Exakte Schwellwerte für die UI-Warnstufen: Implementierung in `RunProgressService`, relativ zu `config('game.run.nexus_debt_fail_threshold')`.

> **Implementierungsstand:** Implementiert sind die Akkumulation (Startkapital als initiale Schuld, Nexus-Kredit-Schiffskauf, Berater-Upkeep-Defizit; Owner-Entscheidung F3, §15 Nexus-Schulden-Mechanik) und die Fail-State-Prüfung. **Eine Tilgung gibt es nicht:** Die Schuld sinkt im Run nie, positives Einkommen ändert daran nichts. Der Schuldenstand ist damit eine reine Einbahnstraße zum Limit. Offen sind die Tilgungsmechanik (künftiger Schritt, §15) und die Warnmeldung kurz vor dem Limit.

**Narrativer Ausgang:** "Nexus hat die Konzession entzogen. Der Direktor wurde zurückgerufen."

---

#### Fail State 3 — Fristablauf ohne Sieg

**Bedingung:** `current_tick >= config('game.run.tick_limit')` UND weniger als 2 Objectives abgeschlossen

**Auslösung:** In `checkFailStates()` nach jedem Tick. Das Sieg-Gate (§18.1) wird vor den Fail States geprüft — wer die zweite Objective genau auf Sol 100 abschließt, gewinnt noch.

**Countdown-Warnstufen:**

| Sol | Maßnahme |
|-----|---------|
| tick_limit − `countdown_sols_before_limit` (Sol 80) | Countdown-Anzeige erscheint im UI ("Noch 20 Sole bis Missionsende"); Nexus-Funk-Nachricht von Nexus |
| tick_limit − 10 (Sol 90) | *geplant:* letzte Nexus-Funk-Warnung, wenn 0 Objectives abgeschlossen |
| tick_limit (Sol 100) | Fail State — Run endet |

**Narrativer Ausgang:** "Fristablauf. Die Konzession wurde nicht verlängert."

---

#### Fail State 4 — Phase-1-Fristbruch

**Bedingung:** `run.phase === 1 && current_tick >= config('game.run.phase1_deadline_sol')` → Standardwert **Sol 20**
**Auslösung:** Instant in dem Tick, in dem die Deadline erreicht wird, sofern Phase 1 noch nicht abgeschlossen ist (`RunProgressService::checkPhase1Completion()`).

Owner-Vorgabe: Phase 1 (Grundaufbau: CC Lv2, Agrardom, Harvester, Wohnhabitat, Uplink-Station und erstes Pfadgebäude jeweils mindestens Lv1, zwei Berater) im Normalfall Sol 12–13 (Minimum-Spieler) bzw. Sol 14–16 (mit Extras), spätestens Sol 20 (Frist). Die Frist liegt damit einige Sole hinter dem Zielfenster, der Puffer für Rückschläge bleibt klein. Bindende Engpässe sind der Regolith-Startbestand und der AP-Deckel pro Ziel und Sol (§13.2); Herleitung in §13.7 „Phase-1-Pacing".
**Warnstufen (Nexus-Funk):**

| Sol | Maßnahme |
|-----|---------|
| Warn-Sol (`config('game.run.phase1_warning_sol')`, *Annahme:* rund drei Sole vor der Frist, d. h. um Sol 17 — der alte Wert Sol 22 läge hinter der neuen Frist und ist anzupassen) | Nexus-Funk-Warnung von Nexus, sofern Phase 1 noch nicht abgeschlossen — einmalig pro Run |
| Sol 20 | Fail State — Run endet sofort |

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
| Sieg 2/3, schnell (im Korridor „sehr guter Run", §18.4) | "Zwei Direktiven erfüllt. Konzession bestätigt. Effizienzrating: überdurchschnittlich." |
| Sieg 2/3, regulär (später als dieser Korridor) | "Zwei Direktiven erfüllt. Konzession bestätigt. Leistungsrating: ausreichend. Weitere Bewertung folgt." |
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
| Phase 1 — Grundaufbau | 12–20 | Grundaufbau: CC Lv2, Agrardom, Harvester, Wohnhabitat, Uplink-Station und erstes Pfadgebäude jeweils ≥ Lv1, zwei Berater — Ziel Sol 12–13 (Minimum) bzw. 14–16 (mit Extras), hart Sol 20 (CC Lv3 ist Auftakt von Phase 2) |
| Phase 2 früh — Einrichten | ~15–20 | Pfad-Gebäude ausbauen, Berater einstellen, die zwei Ziel-Aufgaben wählen |
| Phase 2 mitte — Aufgaben | ~30–40 | Kernarbeit an den zwei Ziel-Aufgaben |
| Phase 2 spät — Abschluss | ~10–15 | Zweite Aufgabe abschließen, dritte optional |

**Sieg-Korridor (Owner-Vorgabe, verbindlich für alle Pacing-Aussagen im GDD):**

| Run-Qualität | Sieg um | Anmerkung |
|---|---|---|
| Sehr guter Run mit Glück (Pfad passt zu den gezogenen Aufgaben, günstige Karte) | Sol 70–85 | Die Ausnahme, nicht der Normalfall |
| Realistischer, solider Run | Sol 85–95 | Der Referenzfall für Balancing |
| Frist | Sol 100 (`run.tick_limit`) | Danach Fail State 3 |

**Wie Sieg-Korridor und Kalibrierregel zusammenpassen:** Die Kalibrierregel (§15 Aufgabenpool: gezielt Sol 70–85) misst jede Aufgabe einzeln, so als würde nur auf sie gespielt. Ein Sieg braucht aber zwei Aufgaben, die um denselben AP-Pool und dieselben Ressourcen konkurrieren. Nur wenn beide gezogenen Aufgaben vom gewählten Pfad gleichzeitig bedient werden und die Karte mitspielt, fallen beide in den Einzel-Korridor. Im Normalfall bremst die zweite Aufgabe die erste, und der Sieg rutscht in den realistischen Korridor.

Die Frist lässt bewusst wenig Puffer über dem realistischen Korridor. Ein holpriger Start oder eine unpassende Aufgaben-Ziehung soll spürbar knapp werden, aber nicht aussichtslos.

**Pacing-Kontrollpunkte (Nexus-Interventionen in Phase-2-Sol):**

`checkNexusInterventions()` arbeitet in **Phase-2-Sol** (nicht in Gesamt-Sol und nicht mit absoluten Tick-Nummern). Die Kontrollpunkte bilden eine **steigende Leiter** und prüfen **Fortschritt statt Erfüllung** (Lesart der Schwellen, „je Anforderung eine andere Aufgabe" und Serien-Regel in §15 „2 von 3"-Mechanik). Jeder Kontrollpunkt wird genau an seinem Phase-2-Sol geprüft. Bei einem Phase-1-Abschluss um Gesamt-Sol 15 ergibt sich:

| Phase-2-Sol | Gesamt-Sol (bei Phase-1-Ende Sol 15) | Anforderung (Mindestwerte, je Anforderung eine andere Aufgabe) | Bei Verfehlen |
|-------------|--------------------------------------|------------------------------|---------------|
| 30 | ~45 | eine Aufgabe mindestens zur Hälfte | erste Nexus-Warnung |
| 50 | ~65 | eine Aufgabe mindestens zu drei Vierteln, eine zweite mindestens zur Hälfte | zweite Warnung |
| 55 (und danach) | ~70 | Prüfung der Schuldengrenze (Fail State 2), kein Leiter-Schritt | Fail |
| 65 | ~80 | eine Aufgabe erfüllt, eine zweite mindestens zu drei Vierteln | Berater-Sanktion |

Die Countdown-Meldung gehört nicht in diese Leiter. Sie hängt allein an der Gesamt-Frist und erscheint bei `tick_limit` − `countdown_sols_before_limit` (§18.2 Fail State 3), unabhängig davon, wann Phase 2 begonnen hat.

**Anpassungsrichtlinien nach Playtest:** Das `tick_limit` bleibt bei 100 (Owner-Entscheidung). Stellschrauben sind die Aufgaben-Parameter und die Phase-1-Bedingungen, nicht die Frist.

| Beobachtung | Maßnahme |
|-------------|---------|
| Phase 1 endet typisch vor Sol 12 | Pacing-Hebel aus §13.7 „Phase-1-Pacing" (AP-Deckel, dann Startbestand); die Bedingung nicht wieder um Lv3 verschärfen |
| Phase 1 dauert typisch länger als Sol 20 | Phase-1-Abschlussbedingungen lockern |
| Gezielt gespielte Aufgabe typisch erst nach Sol 85 | Zielwert dieser Aufgabe in `run.tasks` senken |
| Aufgabe wird ohne gezieltes Spiel vor Sol ~90 erreicht | Zielwert dieser Aufgabe in `run.tasks` erhöhen |

**Aufgaben-Ziele: Kalibrierregeln (Werte in `config/game.php → run.tasks`):**

- **Grundregel für jede Aufgabe (§15 Aufgabenpool):** Ohne gezieltes Spiel frühestens um Sol ~90 oder gar nicht, mit gezieltem Spiel um Sol 70–85. Gemessen wird mit dem PlaytestBot: einmal mit einer Strategie, die gezielt auf die Aufgabe spielt, einmal mit einer, die sie ignoriert. Beide Messungen brauchen eine ausreichend große Seed-Stichprobe.
- **`task_expedition_coverage`** misst erfolgreiche Außenmissionen ab Schwierigkeit „normal". Eine Messung „alle Tiles der Kolonie-Zone erkundet“ taugt nicht: Sie hängt vor allem am CC-Ausbau (die letzte Zonen-Kachel wird erst mit einer späten CC-Stufe frei), nicht an Erkundungsentscheidungen, und ihr Zielwert läge am mathematischen Maximum. Leichte Missionen zählen nicht, sonst wird die Aufgabe über risikoarmes Routine-Dispatching nebenbei erfüllt.
- **`task_engineering_output`** misst die Summe der Ausbaustufen aller Gebäude. Eine Messung über Status-Punkte wäre vom Verfall und von Reparaturen abhängig, für Spieler schwer nachvollziehbar und würde von Sol zu Sol schwanken.
- **`task_senior_advisors`** verlangt eine volle Mannschaft auf höchstem Rang. Das setzt den letzten Berater-Slot (CC-Ausbau) und ausreichend Dienstzeit aller Berater voraus. Wer Berater spät einstellt oder austauscht, schafft es nicht nebenbei.
- **Serien-Aufgaben** (`task_self_sufficiency`, `task_credit_reserve`, `task_colony_prosperity`) sind so gesetzt, dass normales Spielen sie nicht nebenbei erfüllt. Für die Nexus-Kontrollpunkte zählt bei ihnen die beste Serie bis jetzt.
- **Vorläufig bis zum laufenden Kalibrier-Batch (Annahme, nicht final kalibriert):** die Zielwerte aller Aufgaben außer den Owner-Vorgaben für `task_senior_advisors` und `task_credit_reserve`. Für die beiden neuen Messungen (`task_expedition_coverage`, `task_engineering_output`) gibt es noch gar keine Bot-Daten. Für `task_colony_prosperity` kommt hinzu, dass die Trust-Ökonomie selbst noch nicht kalibriert ist.
- **`task_credit_reserve`** bleibt vorerst eine Serie (Schwelle und Serienlänge in `run.tasks`). Der Folgeschritt „Nexus-Vorschuss tilgen" wartet auf die Tilgungsmechanik (§15 Nexus-Schulden-Mechanik).

**Credits-Ökonomie — Break-even-Regel:** Der Berater-Unterhalt (`advisor.upkeep`, steigend mit dem Rang) muss spätestens mit ausgebauter Uplink-Station tragbar sein, unabhängig davon, ob die Cantina gebaut wurde — die Cantina liefert kein dediziertes Dauereinkommen (§12 Kanal 1). Strukturelles Einkommen ist damit für alle drei Pfade gleich: `nexus_subsidy` (flat, bedingungslos) und die Relaisvergütung (`relay_bonus_per_uplink_level`). Herleitung mit vier Beratern (Werte `config/game.php`):

| Rang | Upkeep (4 Berater) | Einkommen, Uplink Lv0 | Einkommen, Uplink Lv2 | Einkommen, Uplink Lv3 (max.) |
|------|---------------------|------------------------|-------------------------|---------------------------------|
| 2 | 100 Cr/Sol | 50 Cr/Sol (−50) | 140 Cr/Sol (+40) | 185 Cr/Sol (+85) |
| 3 | 140 Cr/Sol | 50 Cr/Sol (−90) | 140 Cr/Sol (0) | 185 Cr/Sol (+45) |

Ein Rang-2-Defizit bei niedrigem Uplink-Ausbau ist aus dem Phase-1-Reststand absorbierbar; Rang 3 erreicht mit Uplink Lv2+ eine neutrale bis positive Marge statt eines permanenten Bodens. Beförderungskosten (`promotion_costs`) sind so gesetzt, dass mehrere gleichzeitige Rang-3-Aufstiege keinen Einmal-Schock erzeugen, der zusammen mit dem Upkeep-Sprung die Kasse leert.

**Offene Design-Frage (Owner-Entscheidung):** Alle drei Pfade — Analytik-Labor, Hangar **und** Cantina — brauchen weiterhin einen eigenen Credits-Wachstumshebel jenseits der pfadneutralen Grundeinnahmen oben (§4b markiert alle drei als ⚠️ offen bzw. nur opportunistisch gedeckt). Mechanismus nicht spezifiziert — ROADMAP „Offene Pfad-Paritäts-Fragen".

---

### 18.6 Offene Implementierungsaufgaben (game-developer / db-migration-agent)

| Aufgabe | Verantwortung | Priorität |
|---------|--------------|-----------|
| Warnmeldung kurz vor der Schuldengrenze; Tilgungsmechanik für `nexus_debt` (automatisch oder als Aktion: Design offen, Voraussetzung für „Nexus-Vorschuss tilgen", §15) | game-developer / ui-specialist | Mittel |
| Sol-90-Letzte-Warnung + Fristverkürzung (§15 Gnadenfrist) | game-developer | Mittel |
| A45-Kalibrierung: Zielwerte in `run.tasks` und Leiter-Stufen gegen die Kalibrierregel prüfen (Bot-Batch läuft) | game-developer | Hoch |

Vollständige Liste: `docs/audit-implementierungsstand-2026-09-06.md` (A6–A8).

---

## 19. Nexus-DB (Enzyklopädie)

Die Nexus-DB ist die In-Game-Nachschlageseite des Spiels (Nav-Eintrag „Nexus-DB", Route `/nexus-db`). Sie ist erreichbar, sobald ein Run läuft, und erklärt in kurzen, aufklappbaren Einträgen die Spielbegriffe und ihr Zusammenspiel: Kolonisten-Kapazität, Vertrauen, Sol, Aktionspunkte, Verfall, Reparatur, Nexus und Kolonisten.

**Prinzipien:**

- Reine Erklärseite: Sie kennt keinen Spielstand (kein Lagerbestand, kein Forschungsfortschritt) und ist weder Tutorial noch Tooltip-Ersatz. Sie soll informierte Entscheidungen ermöglichen, ohne dass der Spieler das GDD oder externe Dokumente lesen muss.
- Die Texte erklären Begriffe und Zusammenhänge. Konkrete Spielwerte stehen in `config/*.php` (canonical source of truth), nicht in den Seitentexten; die Seite darf deshalb nicht als Wertetabelle gelesen werden.
- Ein Erstbesuch-Popup erklärt der Seite beim ersten Öffnen ihren Zweck (First-Visit-Mechanik, §16).

**Abgrenzung:** „Nexus-DB" (Enzyklopädie) und „Nexus" (die Fraktion hinter Subvention, Kredit und Schulden) sind verschiedene Dinge, siehe `GLOSSAR.md`.

**Offen:** Ob die Almanach-Artikel aus §17.3 künftig in der Nexus-DB erscheinen, ist nicht entschieden.

---

## Siehe auch

- **[GDD Balance & TODO Index](gdd-balance-checklist.md)** — Blockierende Tasks, Folgearbeiten, Playtest-Kalibrierung, offene Designfragen, Instrumentierung
- **[Implementierungsstand-Audit 2026-09-06](audit-implementierungsstand-2026-09-06.md)** — Abweichungen zwischen GDD/ROADMAP und implementiertem Code/Config
- **[GDD-Audit 2026-10-27](gdd-audit-2026-10-27.md)** — Abgleich des GDD mit dem Code (Supply→Kolonisten, kein Multiplayer, keine Flotten, ein Harvester) samt offenen Code-Resten
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
| Sieg-Korridor: Sol 70–85 nur mit sehr gutem Run und Glück, realistisch Sol 85–95 | §18.4 |
| Aufgaben-Kalibrierregel (ohne gezieltes Spiel frühestens Sol ~90, gezielt Sol 70–85; kein Mindest-Sol; „2 von 3" bei zufälliger Ziehung) | §15 |
| Expertenstab = 4 Berater auf Rang 3 | §15, `run.tasks` |
| Ein gemeinsamer AP-Pool | §13.1 |
| Vier Beratertypen (Stratege zurückgestellt) | §13 |
| Werkstoffe bleiben als Ressource | §3 |
| Knappheitsordnung Regolith < Organika < Werkstoffe | §3 |
| AP-Struktur inkl. `ap.base` | §13.6 |
| Regolith-Zahlensatz: Harvester-Frischwert je Tile-Stufe, Reparatur 1 Rg/SP, Errichtung 70 (bioFacility, Ausnahme) / 95 (alle drei Pfadgebäude), Level-Up 25, `decay_rate`-Klassen 0,40/0,60/0,80/1,20, Startbestand | §13.7 |
| `max_instances` als eigenes Feld neben `max_level` | §4c |

Alles andere ist verhandelbar.

> **Diese Regel gilt auch für Subagenten.** Wer mit Balance-Aufgaben beauftragt wird, bekommt sie explizit mitgegeben — sonst entstehen Vorschläge, die vorhandene Zahlen als Randbedingung behandeln und Workarounds darum herum bauen, statt den Satz neu zu rechnen.
