# Phase-1-Sol-30-Deadline — Design

Status: entworfen, Owner-Freigabe ausstehend
Datum: 2026-08-12

## Kontext

Playtest-Bot-Auswertung heute (PR #244, mehrere Seeds/Reruns) zeigt: Phase 1 (CC Lv3 + 2 Produktionsgebäude Lv2 + 3 Berater, `RunProgressService::checkPhase1Completion()`) wird aktuell frühestens bei **Sol 55-65** abgeschlossen — nie früher. Beispiel-Timeline aus dem besten bisherigen Lauf (Seed 4242):

| Sol | CC-Level | Berater |
|---|---|---|
| 0 | 2 | 1 |
| 1 | 3 | 1 |
| 50 | 3 | 2 |
| 55 | 3 | 3 (→ Phase 2) |

CC-Ausbau ist kein Problem (Sol 1 erledigt). Der komplette Engpass liegt zwischen Sol 1 und Sol 50+: Pfadgebäude-Regolith + Berater-Hire-Credits reichen nicht annähernd schnell genug nach.

Owner-Vorgabe: Phase 1 soll **normal um Sol 15-20** abgeschlossen sein, **spätestens Sol 30** — danach hartes Game-Over. Dieses Spec deckt nur den Deadline-Mechanismus selbst ab. Die Rebalancierung, die Sol 15-20 überhaupt erreichbar macht (vermutlich primär Harvester-Ertrag, ggf. Pfadgebäude-/Hire-Kosten), ist ein separater, größerer Auftrag an `game-designer` — siehe GDD Anhang A.4.

## Bestehende Muster (wiederverwendet, nicht neu erfunden)

- **Fail-States**: `RunProgressService::checkFailStates()` — bereits `trust_collapse`, `nexus_debt`, `time_limit`. Neuer Eintrag `phase1_deadline` fügt sich als vierter Check ein, gleiches Rückgabe-/`endRun()`-Muster.
- **Eskalierende Nexus-Warnungen**: `RunProgressService::checkNexusInterventions()` — Phase-2-Checkpoints (Sol 30/50/55/65/80 relativ zu `phase2_start_tick`, via `getPhase2Sol()`), jeder Checkpoint feuert per `colony_log`-Lookup (`eventAlreadyFired()`) genau einmal. Neue Phase-1-Checkpoints spiegeln dieses Muster 1:1, nur an `current_tick` statt `getPhase2Sol()` gebunden (Phase 1 hat kein eigenes "Start"-Offset — Run-Start = Sol 0 = Phase-1-Start).

**Namenskollision vermeiden:** Der bestehende Phase-2-Checkpoint heißt bereits `run.nexus_warning_sol30` (Phase-2-relativer Sol 30). Neue Event-Keys müssen sich eindeutig auf Phase 1 beziehen, z. B. `run.nexus_phase1_briefing` / `run.nexus_phase1_warning`.

## Design

### 1. Config

```php
// config/game.php → 'run' Block
'phase1_deadline_sol' => 30,        // hartes Game-Over, wenn Phase 1 bis hier nicht abgeschlossen
'phase1_warning_sol' => 22,         // eskalierende Nexus-Warnung, falls Phase 1 noch nicht abgeschlossen
```

Zwei separate Config-Keys statt einer festen Prozent-Berechnung — beide direkt vom Owner justierbar, ohne Kopplung.

### 2. Fail-State-Check

`RunProgressService::checkFailStates()`, neuer Check vor `time_limit` (feuert in der Praxis immer zuerst, da `phase1_deadline_sol` < `tick_limit`):

```php
if ($run->phase === 1 && $run->current_tick >= (int) config('game.run.phase1_deadline_sol', 30)) {
    return 'phase1_deadline';
}
```

Bestehende Checks (`trust_collapse`, `nexus_debt`, `time_limit`) bleiben unverändert — falls die Kolonie z. B. schon vorher an Trust-Kollaps scheitert, greift der weiterhin zuerst (Reihenfolge in `checkFailStates()` ist bereits so: erster zutreffender Check gewinnt).

### 3. Nexus-Ankündigung + Eskalation

Neue Methode `checkPhase1DeadlineWarnings(Run $run)`, aufgerufen einmal pro Tick **nur während Phase 1** (Aufrufstelle: wo auch `checkNexusInterventions()` für Phase 2 aufgerufen wird — vermutlich `GameTick`, gegen Phase geprüft):

```php
public function checkPhase1DeadlineWarnings(Run $run): void
{
    if ($run->phase !== 1) {
        return;
    }

    // Briefing: einmalig bei Run-Start (Sol 0/1), Teil des bestehenden
    // Nexus-Erstbriefings — kein neuer Checkpoint-Mechanismus nötig, nur ein
    // zusätzlicher Satz im bestehenden Briefing-Text (content-writer).

    $warningSol = (int) config('game.run.phase1_warning_sol', 22);
    if ($run->current_tick >= $warningSol) {
        $this->maybeFirePhase1DeadlineWarning($run);
    }
}

private function maybeFirePhase1DeadlineWarning(Run $run): void
{
    $eventKey = 'run.nexus_phase1_warning';

    if ($this->eventAlreadyFired($run, $eventKey)) {
        return;
    }

    $this->createEvent($run->user_id, $run->current_tick, $eventKey, 'run', [
        'run_id' => $run->id,
        'colony_id' => $run->colony_id,
        'sols_remaining' => (int) config('game.run.phase1_deadline_sol', 30) - $run->current_tick,
    ]);
}
```

Kein `eventAlreadyFired`-Sol-Exakt-Check wie bei `maybeFireSol30Warning()` (dort `if ($sol !== 30) return;`) — hier reicht "ab Sol X, einmalig", da es nur einen Eskalationsschritt gibt (nicht mehrere Checkpoints wie in Phase 2). Bei Bedarf später erweiterbar (z. B. zweite Warnung ab Sol 27).

### 4. GDD

- §15 (Run-Ende/Fail-States): neuer Fail-State `phase1_deadline`, analog zur bestehenden `trust_collapse`/`nexus_debt`-Auflistung.
- §18 (Fail-State-Definitionen, falls dort eine Tabelle existiert wie bei Trust): `phase1_deadline_sol=30`/`phase1_warning_sol=22` eintragen.
- Querverweis auf den offenen Rebalancierungs-Punkt in Anhang A.4 (bereits vorhanden für Kenntnisse/Events — Phase-1-Pacing als eigene Zeile ergänzen, siehe unten).

### 5. Ausdrücklich nicht Teil dieses Specs

- Die eigentliche Rebalancierung (Harvester-Ertrag etc.), die Sol 15-20 erreichbar macht — separater `game-designer`-Auftrag.
- Content-Texte (Nexus-Briefing-Ergänzung, Warnungs-INNN-Text, Game-Over-Screen-Text) — `content-writer`, nach GDD-Freigabe.
- UI (Countdown-Anzeige o. ä.) — falls gewünscht, eigener `ui-specialist`-Folgeauftrag, hier nicht spezifiziert.

## Offene Fragen für die Owner-Freigabe

1. `phase1_warning_sol=22` als Default — passt die Zahl (grob 2/3 der Deadline), oder anderer Wert?
2. Soll `phase1_deadline` als Fail-Reason einen eigenen, wahrnehmbar anderen Game-Over-Screen-Ton bekommen (im Sinne von "Nexus zieht die Reißleine") als die bestehenden Fails, oder reicht generische Game-Over-Behandlung? (Reine Content-Frage, keine Logik-Frage — nur für Scope-Klarheit hier vermerkt.)
