# Nexus-DB + Codex zusammenführen — Entwurf (Stand 2026-11-08)

Status: **Entwurf, nicht umgesetzt.** Annahmen sind als solche gekennzeichnet. Owner-Entscheidung steht aus.

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
- optional einmaliger Wissensbonus beim ersten Lesen (nur wenn der Owner das weiterhin will)

**Übernahme in die Verschmelzung (Annahmen, Empfehlung):**

1. **Ein gemeinsames Freischaltmodell** für alle Artikel: pro Artikel eine Kategorie + optional ein Trigger. Der Codex-Fortschritt wird ein Trigger-Typ (`codex_progress:{figur}:{n}`), die Figuren-Einträge bleiben nacheinander freischaltbar.
2. **Wichtige Artikel nie gesperrt (Owner-Vorgabe):** alle Grundmechaniken, die der Spieler zum Überleben und Verstehen braucht (Kolonisten, AP, Sol, Gebäude-Grundlagen, Fokus, Ressourcen, Vertrauen), sind Kategorie „immer verfügbar“. Die Liste legen Owner und `game-designer` vor der Umsetzung fest; Regel: im Zweifel offen.
3. **Gesperrt werden nur Vertiefung und Lore**: Figuren-Hintergründe, spätere Phasen-Inhalte, Spoiler zu Begegnungen/Objectives.
4. **Persistenz:** Figuren-Fortschritt bleibt nutzerbezogen (wie heute); run-bezogene Freischaltungen (z. B. `building_built`) brauchen eine Entscheidung: pro Run oder nutzerweit? (Empfehlung: nutzerweit, damit Wiederholer nicht neu sperren müssen; Onboarding-Hinweis trotzdem pro Run.)
5. **Bestehende Daten:** `character_codex_entries` bleibt unverändert; eine neue Tabelle für sonstige Freischaltungen (Arbeitsname `article_unlocks`) kommt erst mit dem ersten gesperrten Nicht-Figuren-Artikel.

Folge für Alternativen: Option A („Tab Figuren“) allein reicht dann nicht; sie ist der **erste Schritt**, das gemeinsame Modell der zweite. Option C setzt es voraus.

## Offene Fragen an den Owner

1. A, B oder C?
2. Soll der Tab „Figuren“ ungelesene neue Einträge markieren (Punkt am Tab)? (Annahme: ja, später.)
3. Heißt das Ziel in der Navigation weiter „Nexus-DB“ oder „Datenarchiv“? (GLOSSAR: „Nexus-DB“ ≠ Nexus-Schulden; Verwechslungsgefahr bleibt bei „Nexus-DB“.)
5. Welche Artikel zählen zu „immer verfügbar“? (Vorschlag der Grundmechanik-Liste oben; Freigabe nötig.)
6. Wissensbonus beim ersten Lesen (GDD §17.3) mit übernehmen oder streichen?
4. Gehören die Kenntnis-Einträge der Kenntnis-Ziehung (geplant, siehe `2026-11-08-kenntnis-ziehung-entwurf.md`) später als eigener Tab oder in „Kenntnisse“?

## Nicht Teil dieses Entwurfs

Inhalte der Codex-Einträge, neue Figuren, Spoiler-Regeln für Nexus-DB-Spalten.
