---
name: nouron-balance-pass
description: Proaktiv einsetzen, auch ohne dass der Owner den Skill nennt, sobald es um Nouron-Balancing geht - Kosten/Einnahmen/AP-Werte ändern, Pacing-Ziele (Phase-1-Ende, Sol-Fenster), Bot-Messungen (game:playtest, Playtest-Bot-Verhalten), "zu teuer/zu schnell/zu langsam", Hebel für Gebäude-, Regolith- oder AP-Werte vorschlagen. Erzwingt Messen vor Rechnen, eine Änderung pro Messung und ein gepflegtes Entscheidungsdokument.
---

# Nouron Balance-Pass

Verfahren für jede Balancing- oder Pacing-Arbeit. Hintergrund: Am 2026-10-16 kreisten wir, weil Hebel (Kosten, AP, Start-Rg) diskutiert wurden, bevor die Größen zerlegt waren. Gerechnete Zahlen (445 Rg, 125 AP) stimmten nicht mit den Reports überein; der Bot (nicht die Balance) verursachte die Fehlschläge; neue Variablen (Erkundung = 38 % der Phase-1-AP) tauchten nach jeder Entscheidung auf.

## Vor allem anderen lesen

1. Das aktuelle Entscheidungsdokument (z. B. `docs/superpowers/specs/2026-10-16-phase1-pacing-entscheidungen.md`): Ziel, Entscheidungen mit Status/Auslöser, fixierte Größen, offene Variablen, Messhistorie.
2. `config/game.php`, `config/buildings.php` sind die Quelle für Werte (nicht GDD, nicht Memory).

## Ablauf

1. **Baseline messen, nicht rechnen.** Neuesten Batch auswerten: `php tools/phase1-budget.php --since=YYYYMMDD_HHMMSS` (UTC aus dem Report-Dateinamen). Liefert pro Eröffnung Phase-1-Ende, AP je Bot-Regel, Rg-Senken, Rg-Puffer. Gibt es keinen frischen Batch, zuerst einen fahren (siehe Messprotokoll).
2. **Bot-Fehler von Balance-Wand trennen.** Vor jedem Hebel prüfen: Baut der Bot Pflicht-Gebäude nicht (fehlende Regel), verbraucht er eine knappe Ressource für Nicht-Pflichtiges (myopische Regel) oder hortet er (unbegrenzte Instanz)? Erst wenn der Bot das Ziel nachweislich anpeilt, ist ein Ergebnis eine Balance-Aussage. Siehe Memory `feedback_zero_rule_gap_pattern` und `feedback_playtest_balance_investigation_method`.
3. **Gerechnet und gemessen trennen.** Jede Zahl im Gespräch trägt ein Etikett. Zahlen aus Agenten oder eigener Rechnung sind Annahmen, bis sie gegen Reports geprüft sind. Dem Owner keine Entscheidung auf Basis nur gerechneter Zahlen vorlegen.
4. **Hypothese vorher notieren.** Vor jeder Messung: welcher eine Hebel ändert sich, welche Zahlen erwarte ich. Eine Änderung pro Messung. Weicht das Ergebnis ab, zuerst mit der Tabelle erklären (welche Zeile hat sich bewegt?), dann erst neuer Hebel.
5. **Bot-Profil einfrieren.** Bot-Verhalten ist eine fixierte Größe. Änderungen am Bot (Regeln, Erkundung, Extras) sind eigene, benannte Schritte mit Test (TDD), nie ein Nebeneffekt einer Balance-Messung.
6. **Metriken, die täuschen.** "Rg-Bestand am Ende" ist als Puffer ungeeignet (Bot gibt Reste aus). Puffer = verfügbares Rg bis Phase-1-Ende minus Minimum. Siehe auch Seed-Regel: mind. 8 Seeds, nicht 3; Same-Seed-Vergleiche erst ab Fix vom 2026-09-13 zuverlässig.
7. **Entscheidungsdokument pflegen.** Neue Erkenntnis -> Zeile in "Messhistorie", geänderte Entscheidung -> Eintrag unter "Änderungen" mit Datum und Abhängigem. Entscheidungen des Owners dürfen sich ändern; jede hat Status (fest/vorläufig) und Auslöser. Eigene Deutungen als Annahme markieren, nie als Owner-Entscheidung.

## Messprotokoll

```
PLAYTEST_PROCESS_TIMEOUT=2700 nohup php artisan game:playtest --engine=utility \
  --openings=labor,hangar,cantina --seeds=1,2,3,4,5,6,7,8 --until-sol=25 --concurrency=24 \
  > $CLAUDE_JOB_DIR/tmp/mN.log 2>&1 &
date -u +"%Y%m%d_%H%M%S" > $CLAUDE_JOB_DIR/tmp/mN_since.txt   # VOR dem Start schreiben
php tools/phase1-budget.php --since=<Zeitstempel>
```

- 24 Läufe dauern mit `--concurrency=24` auf der 24-Kerne-Maschine ca. 4 min (nicht 1 h). Standard 10 erzeugt ungleiche Wellen.
- Währenddessen keine PHPUnit-Läufe, Migrationen oder Seeds (gleiche DB). Nie `migrate:fresh` gegen die Dev-DB.
- Hintergrundlauf mit `nohup ... &`, dann Monitor/Polling-Schleife; `run_in_background` kann fälschlich "low memory" melden.
- `--since` ist UTC aus dem Dateinamen (nicht lokale mtime, +2 h).
- Wert-Sweeps ohne Code: Config-Zeile per `sed` ändern, Batch fahren, danach `git checkout config/game.php`. Immer prüfen, dass die Config danach sauber ist.
- Kleine Bot-/UI-Fixes ohne Batch abschließen; Batches nur bei Balance-Änderungen.

## Nach Wertänderungen

`docs/game-reference.md` aktualisieren (Hook blockiert sonst), CHANGELOG-Zeile, ROADMAP-Status, GDD zahlenfrei (ADR 0004). Bei Kosten-/Rabatt-Wiring die Checkliste im `qa-tester`-Agent anwenden.
