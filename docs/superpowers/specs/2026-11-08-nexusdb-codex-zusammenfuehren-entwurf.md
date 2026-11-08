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

## Offene Fragen an den Owner

1. A, B oder C?
2. Soll der Tab „Figuren“ ungelesene neue Einträge markieren (Punkt am Tab)? (Annahme: ja, später.)
3. Heißt das Ziel in der Navigation weiter „Nexus-DB“ oder „Datenarchiv“? (GLOSSAR: „Nexus-DB“ ≠ Nexus-Schulden; Verwechslungsgefahr bleibt bei „Nexus-DB“.)
4. Gehören die Kenntnis-Einträge der Kenntnis-Ziehung (geplant, siehe `2026-11-08-kenntnis-ziehung-entwurf.md`) später als eigener Tab oder in „Kenntnisse“?

## Nicht Teil dieses Entwurfs

Inhalte der Codex-Einträge, neue Figuren, Spoiler-Regeln für Nexus-DB-Spalten.
