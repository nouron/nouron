# ADR 0007: Spielereignisse als Laravel-Events (Achievements)
Datum: 2026-10-23
Status: Akzeptiert (Stufe 1 umgesetzt, Stufe 2 offen)

## Kontext

Das Spiel ist und bleibt rundenbasiert (auch bei einem späteren Mehrspieler-Modus, ADR 0003).
Trotzdem sollen Dinge wie Achievements **sofort bei Auslösung** reagieren und nicht erst in der
Rundenberechnung. Bisher gab es nur das Grundgerüst aus ADR 0003 (`RunStarted`, `RunEnded`,
`SolAdvanced`, keine Listener). Spielereignisse waren reine Datenbankzeilen: Journal-Einträge
(`EventService::createEvent`, Tabelle `colony_log`) und Vertrauens-Ereignisse
(`TrustService::fireEvent`, Tabelle `trust_events`).

## Entscheidung

**Stufe 1 (umgesetzt):** Beide Stellen dispatchen nach dem Speichern zusätzlich ein Laravel-Event:

- `JournalEventRecorded` (Log-ID, Nutzer, Sol, Ereignis-Schlüssel, Bereich, Parameter)
- `TrustEventFired` (Kolonie, Ereignis-Typ, Sol)

Die Zeilen werden unverändert geschrieben; das Event ist eine reine Zusatzmeldung. Unbekannte
Vertrauens-Ereignisse (werden ignoriert) lösen kein Event aus.

**Regel für Listener:** nur beobachten und speichern (z. B. Achievement vergeben). Listener
verändern keinen Spielzustand, den die Rundenberechnung liest. So bleibt `GameTick`
deterministisch und reproduzierbar (Seed-Läufe, Bot-Messungen).

**Stufe 2 (offen, bei Bedarf):** wichtige Spielmomente zusätzlich als eigene Domain-Events
(z. B. Gebäude fertig, Schiff verloren), sobald ein Listener mehr braucht als Schlüssel und
Parameter.

## Verworfen

- **Umbau des gesamten Spiels / von `GameTick` auf Event-getriebenen Code:** hoher Aufwand,
  gefährdet Determinismus, bringt für ein rundenbasiertes Spiel keinen Nutzen.
- **Achievements per Abfrage in der Rundenberechnung:** reagiert zu spät (erst am Sol-Ende).

## Folgen

- Achievements, Statistiken oder Benachrichtigungen werden als Listener ergänzt, ohne
  Services anzufassen.
- Listener laufen synchron im Request; sie müssen schnell bleiben (bei Bedarf in die Queue legen).
- Die Bot-Messung (`game:playtest`) löst die Events ebenfalls aus; ohne Listener ohne Wirkung.
