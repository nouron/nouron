# Nexus-DB + Codex zusammenführen — Entwurf (Stand 2026-11-08)

Status: **Entscheidungen getroffen, nicht umgesetzt.** Annahmen sind als solche gekennzeichnet.

> **Owner-Entscheidungen 2026-11-08:**
> 1. **Option C** (echte Verschmelzung): Nexus-DB und Codex werden ein Datenarchiv mit gemeinsamem Freischaltmodell; Figuren-Einträge erscheinen eingebettet bei den zugehörigen Artikeln. Option A (Tab „Figuren“) ist höchstens Zwischenschritt.
> 2. **Wissensbonus beim ersten Lesen (GDD §17.3) entfällt vorerst**, späterer Evaluationspunkt.
> 3. **Grundmechaniken sind immer offen** (nie gesperrt).
> 4. **Freischaltungen spielerweit** (bleiben über Runs erhalten, wie der Codex heute).
> 5. **Name „Datenarchiv“**; „Nexus-DB“ darf als Alias und als Name in der Lore weiterleben (GLOSSAR-Eintrag nötig).
> 6. **Redaktionskonzept** (welche Figuren-Einträge zu welchen Artikeln) wird separat besprochen.
> 7. **Liste „immer verfügbar“ freigegeben** (Regeln offen, Lore gesperrt; Tempel/Denkmal-Regelartikel ebenfalls offen).
> 8. **Redaktionskonzept (Owner 2026-11-09):** Figuren-Einträge als eigener Bereich mit Querverweisen, zweite Lore-Schicht bei Regelartikeln, Freischalt-Anlässe (max. 3 Stufen pro Artikel), Spoiler-Regel, zunächst nur `de` — alles wie vorgeschlagen. Texte macht `content-writer`; zunächst nur grobe Ideen/Platzhalter, Feinschliff später.
> 9. **Freischalt-Budget:** In einem einzelnen Run lässt sich nicht alles freischalten. Für die vollständige Freischaltung braucht es **4–5 komplette, erfolgreiche Runs**. Folge: Anlässe und Stufen müssen über Runs verteilt sein (pro Run erreichbar nur ein Teil), Fortschritt zählt nur bei erfolgreichem Run-Abschluss oder ist daran gekoppelt (Präzisiert unten: Runs zählen unabhängig vom Ausgang, Hades-Prinzip.). Messung/Plan: Anzahl Anlässe gesamt ÷ pro Run realistisch erreichbare ≈ 4–5.
> Weiter offen: Detailfragen zu 9 (siehe unten), kein Umsetzungsstart.

## Ist-Stand

| | Nexus-DB (`/nexus-db`, Spiel-Layout) | Codex (`/codex`, Infra-Layout) |
|---|---|---|
| Inhalt | Regelreferenz: Konzepte, Gebäude, Kenntnisse, Schiffe, Berater | Was der Spieler über jede Figur gelernt hat (Einträge pro Figur) |
| Wissensstand | immer vollständig, run-unabhängig | wächst im Lauf (gesperrte Einträge als Platzhalter) |
| Aufbau | 5 Tabs | Aufklapp-Liste je Figur, Zähler `x / max` |
| Navigation | Bottom-Nav → Datenarchiv ▲ | Bottom-Nav → Datenarchiv ▲ (Eintrag zeigt auf Infra-Seite, ohne Spiel-Navigation) |

Beide sind „Nachschlagen“, unterscheiden sich aber in der Frage: *Wie funktioniert X?* vs. *Was weiß ich über Y?*

## Vorschlag (Annahme, Empfehlung)

**Ein Ziel „Datenarchiv“ mit Tabs; der Codex wird sechster Tab „Figuren“.**

- Nexus-DB-Seite bekommt Tab `figures` mit dem heutigen Codex-Inhalt (gleiche API `/codex`, gleiche Texte).
- Route `/codex` bleibt als Weiterleitung auf `/nexus-db?tab=figures` (alte Links, Hinweise im Log).
- Bottom-Nav-Gruppe Datenarchiv: **Log, Datenarchiv** (statt Log, Nexus-DB, Codex) — ein Eintrag weniger.
- Der Codex liegt damit im Spiel-Layout (Kopfzeile, Leiste, Bottom-Nav) statt isoliert.
- Seitentitel „Nexus-Datenbank“ bleibt; Begriff „Codex“ lebt als Tab-/Bereichsname weiter.

Aufwand (grob): 1 kleiner PR, nur Frontend + Lang-Keys; kein Backend, keine Migration.

## Alternativen

- **B: getrennt lassen**, nur Codex ins Spiel-Layout holen. Kleinster Eingriff, Nav bleibt mit drei Einträgen.
- **C: echte Verschmelzung** (Figuren-Einträge erscheinen in den Berater-/Konzept-Artikeln). Inhaltlich reizvoll, aber braucht Redaktionskonzept (welche Einträge wohin) — nicht ohne Content-Planung.

## Freischaltsystem (Owner-Hinweis 2026-11-08, muss mit übernommen werden)

Die Nexus-DB kennt bisher **keine** Freischaltung: alle Artikel sind sofort sichtbar. Der Codex hat sie: Einträge pro Figur schalten sich nach Cantina-Gesprächen und Beziehungsaufbau nacheinander frei (`CharacterCodexService::recordProgress()`, `character_codex.entries_per_character`, Tabelle `character_codex_entries`; **nutzerbezogen, bleibt über Runs erhalten**; gesperrte Einträge erscheinen als Platzhalter mit Zähler `x / max`).

Das allgemeine Konzept steht schon im GDD: `docs/gdd/progressive-discovery.md` §17.3 „Almanach — Unlock & Wissensbonus“ (nie umgesetzt; die Nexus-DB ist de facto der Almanach):

- drei Kategorien: **immer verfügbar** / **fortschrittsabhängig** / **entdeckungsabhängig**
- Trigger-Typen (`sol_reached`, `building_built`, `objective_revealed`, `encounter_event`, `advisor_dialog`, `always`)
- gesperrt = grauer Eintrag mit „Wird nach … freigeschaltet“, Inhalt verborgen; Badge „Neu“ + Protokollmeldung bei Freischaltung
- einmaliger Wissensbonus beim ersten Lesen — **vorerst gestrichen (Owner), später neu evaluieren**

**Übernahme in die Verschmelzung (Annahmen, Empfehlung):**

1. **Ein gemeinsames Freischaltmodell** für alle Artikel: pro Artikel eine Kategorie + optional ein Trigger. Der Codex-Fortschritt wird ein Trigger-Typ (`codex_progress:{figur}:{n}`), die Figuren-Einträge bleiben nacheinander freischaltbar.
2. **Wichtige Artikel nie gesperrt (Owner-Vorgabe):** alle Grundmechaniken, die der Spieler zum Überleben und Verstehen braucht (Kolonisten, AP, Sol, Gebäude-Grundlagen, Fokus, Ressourcen, Vertrauen), sind Kategorie „immer verfügbar“. Die Liste legen Owner und `game-designer` vor der Umsetzung fest; Regel: im Zweifel offen.
3. **Gesperrt werden nur Vertiefung und Lore**: Figuren-Hintergründe, spätere Phasen-Inhalte, Spoiler zu Begegnungen/Objectives.
4. **Persistenz:** Figuren-Fortschritt bleibt nutzerbezogen (wie heute); run-bezogene Freischaltungen (z. B. `building_built`) brauchen eine Entscheidung: pro Run oder nutzerweit? (Empfehlung: nutzerweit, damit Wiederholer nicht neu sperren müssen; Onboarding-Hinweis trotzdem pro Run.)
5. **Bestehende Daten:** `character_codex_entries` bleibt unverändert; eine neue Tabelle für sonstige Freischaltungen (Arbeitsname `article_unlocks`) kommt erst mit dem ersten gesperrten Nicht-Figuren-Artikel.

Folge für Alternativen: Option A („Tab Figuren“) allein reicht dann nicht; sie ist der **erste Schritt**, das gemeinsame Modell der zweite. Option C setzt es voraus.

## Vorschlag: Liste „immer verfügbar“ (zur Freigabe)

Grundsatz: **Regel- und Zahlenwissen ist immer offen**, gesperrt wird nur Lore/Vertiefung. Grund: Der Techtree zeigt Gebäude, Kenntnisse und Berater ohnehin schon; ein gesperrter Regelartikel würde dem widersprechen.

| Bereich | Immer verfügbar | Fortschrittsabhängig (Vorschlag) |
|---|---|---|
| **Konzepte** (9) | Sol, Aktionspunkte, Kolonisten, Kolonisten-Kapazität, Vertrauen, Verfall, Reparatur, Fokus-Gebäude, Nexus (Schulden/Subvention) | — (Vertiefungen/Hintergrund später als eigene Artikel) |
| **Gebäude** (13) | Regelartikel aller Gebäude: Kosten, Voraussetzung, Ausbaustufen, Wirkung | Lore-Absatz je Gebäude; Hintergrund zu Tempel/Denkmal erst nach dem ersten Bau (`building_built`) |
| **Kenntnisse** (7) | Regelartikel: Wirkung, Stufen | Lore/Herkunft nach Erforschung |
| **Berater** (4 sichtbar: Baumeister, Wissenschaftler, Raumfahrer, Händler) | Regelartikel: Ränge, Kosten, Wirkung, Fokus-Zuordnung | Charakter-Hintergrund nach Einstellung/Rang |
| **Schiffe** (aktive) | Regelartikel: Kosten, Werte | Lore nach erstem Kauf |
| **Figuren** (Cantina) | — (Figuren selbst bleiben unbekannt, bis man sie trifft) | alle Einträge wie heute im Codex: Eintrag n nach n-tem Anlass; Eintrag 1 bei erster Begegnung |

Nicht aufgelistet, weil noch nicht vorhanden: Encounter-/Objective-Artikel (Spoilergefahr, `encounter_event` / `objective_revealed`) und Phasen-Artikel — die kommen später gesperrt dazu.

Offene Rückfrage an den Owner: Reichen Regelartikel immer offen und Lore gesperrt als Faustregel, oder sollen einzelne Spät-Gebäude (Tempel, Denkmal) auch als Regelartikel verborgen bleiben (Überraschungseffekt)? Empfehlung: offen lassen — der Techtree zeigt sie schon.

## Offene Fragen an den Owner

1. ~~A, B oder C?~~ entschieden: C.
2. Soll der Tab „Figuren“ ungelesene neue Einträge markieren (Punkt am Tab)? (Annahme: ja, später.)
3. ~~Name~~ entschieden: Datenarchiv (Nexus-DB als Alias/Lore-Name).
5. Welche Artikel zählen zu „immer verfügbar“? (Vorschlag der Grundmechanik-Liste oben; Freigabe nötig.)
6. ~~Wissensbonus~~ entschieden: entfällt vorerst.
4. Gehören die Kenntnis-Einträge der Kenntnis-Ziehung (geplant, siehe `2026-11-08-kenntnis-ziehung-entwurf.md`) später als eigener Tab oder in „Kenntnisse“?

## Nicht Teil dieses Entwurfs

Inhalte der Codex-Einträge, neue Figuren, Spoiler-Regeln für Nexus-DB-Spalten.

## Redaktionskonzept — Stand 2026-11-09

Angenommen/entschieden: siehe Kasten oben (Punkte 8–9). Offene Detailfragen zu Punkt 9:

**Entschieden 2026-11-09:**

1. **Freischaltungen aus verlorenen/abgebrochenen Runs zählen.**
2. **Hades-Prinzip, abgeschwächt:** Der Spieler scheitert anfangs oft und schaltet trotzdem laufend mehr Charakter-Informationen frei. Manche Einträge brauchen zusätzlich eine **Mindestzahl an (auch gescheiterten) Runs**. Kein Eintrag ist nur durch einen Sieg erreichbar. Neuer Trigger-Typ `runs_played:{n}` (spielerweit gezählt, Ausgang egal) neben den Ereignis-Triggern; Muster: Eintrag n = Ereignis-Anlass **und** `runs_played ≥ Schwelle(n)`.
3. **Kein Gesamtzähler** im Datenarchiv; nur Zähler pro Artikel/Figur (wie heute `x / max`).

*Annahme (Auflösung zu Punkt 9 im Kasten):* Das Budget „4–5 komplette Runs“ meint Runs, die zu Ende gespielt wurden (Sieg oder Niederlage), nicht nur Siege. Die Schwellen `Schwelle(n)` werden so gesetzt, dass alles frühestens nach 4–5 Runs offen ist; das wird mit `game-designer` festgelegt.
