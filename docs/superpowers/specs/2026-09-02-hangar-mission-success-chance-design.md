# Hangar-Missionen: Erfolgschance + Schwierigkeitsgrad

Datum: 2026-09-02
Status: Design freigegeben, bereit für Implementierungsplan

## Ausgangslage

Hangar-Missionen (GDD §8b, `config/missions.php`) laufen aktuell deterministisch durch: Dispatch kostet Nav-AP + Organika, bei Rückkehr (`GameTick::processHangarMissions`) wird die Belohnung immer ausgezahlt. Der einzige bestehende "Fehlschlag"-Pfad ist der SP-Abort (Schiff durch Abnutzung während der Mission bei SP ≤ 0 — kein Reward, Schiff kommt beschädigt heim).

Owner-Wunsch: echte Erfolgschance mit wählbarem Schwierigkeitsgrad, die früh im Run vor allem über Glück, später über Berater-/Kenntnis-Fortschritt schaffbar wird — organisches Roguelike-Risiko/Reward-Gefühl statt Garantie.

## Entscheidungen (aus Brainstorming, alle vom Owner bestätigt)

1. **Fehlschlag-Konsequenz**: kein Reward. Bei `leicht`/`normal` nur die normale Abnutzung (wie im Erfolgsfall). Bei `schwer` zusätzlich erhöhte Abnutzung (Extra-SP-Abzug obendrauf) — kein Schiffsverlust, keine Teilbelohnung.
2. **Schwierigkeitswahl**: pro Dispatch, nicht pro Mission-Typ fix. Jede Katalog-Mission bietet genau **2 von 3** Stufen (leicht/normal/schwer) zur Auswahl — welche 2, ist pro Mission im Katalog konfiguriert.
3. **Reward-Skalierung**: fester globaler Multiplikator pro Stufe (kein Pro-Mission-Handkuratieren).
4. **Dispatch-Kosten**: unverändert von Schwierigkeit — Nav-AP/Organika hängen nur von `sol_distance` ab, wie heute.
5. **Bonus-Quellen auf Erfolgschance**: Pilot-Rang (generisch, alle Hangar-Missionen) **+** die missionsspezifische Kenntnis, falls die Mission ein `requires.knowledge`-Gate hat (analog zum bestehenden Organika-Scaling-Muster: Bonus pro Level *über* dem Gate).
6. **Zeitliche Progression**: kein eigener Sol-/Zeit-Parameter. Basis-Chancen sind über die ganze Run-Laufzeit konstant; der "später leichter"-Effekt entsteht rein daraus, dass Pilot-Rang und Kenntnis-Level im Run natürlich steigen.
7. **RNG**: kein neuer Mechanismus — reuse `runs.rng_seed` (ADR 0003), gleiches Muster wie die bestehenden Reward-Rolls (`rng_seed + mission_id`), mit eigener Ableitung damit der Erfolgs-Roll nicht mit dem Loot-Roll kollidiert.

## Config-Schema

### `config/missions.php` — pro Katalog-Eintrag

Neuer Key `difficulties`, Liste von genau 2 der 3 Stufen:

```php
'mission_ruin_expedition' => [
    // ... bestehende Felder unverändert ...
    'difficulties' => ['normal', 'schwer'],
],
```

Missionen ohne explizites `requires.knowledge`-Gate bekommen trotzdem `difficulties` — der Kenntnis-Bonus fällt für sie einfach weg (nur Pilot-Rang-Bonus greift).

### `config/game.php` — neuer Block `missions.difficulty`

```php
'missions' => [
    // ... bestehende hangar/missions-Keys ...
    'difficulty' => [
        'base_chance' => ['leicht' => 0.85, 'normal' => 0.70, 'schwer' => 0.60],
        'reward_multiplier' => ['leicht' => 0.7, 'normal' => 1.0, 'schwer' => 1.4],
        'pilot_rank_bonus_pct' => 0.05,       // pro Pilot-Rang (1–3), additiv auf die Chance
        'knowledge_bonus_pct_per_level' => 0.03, // pro Kenntnis-Level über dem Mission-Gate
        'chance_cap' => 0.95,                 // harte Obergrenze nach allen Boni
        'hard_fail_extra_wear' => 1.0,        // zusätzlicher SP-Abzug bei Fehlschlag auf 'schwer' (on top von wear_per_sol)
    ],
],
```

Zahlen sind Platzhalter (ADR 0004 — Balance-Kalibrierung nach Playtest), Struktur ist der Punkt.

## Datenmodell

Migration: `colony_hangar_missions` bekommt Spalte `difficulty` (string, z.B. `'leicht'|'normal'|'schwer'`), gesetzt bei Dispatch, gelesen bei Resolution. Keine Backfill-Logik nötig (nur neue Missionen betroffen) — reine Additiv-Migration.

## Ablauf

### Dispatch (`HangarController` + `HangarService::dispatchShip`)

- Preview-Endpoint (bestehende `previewDispatch`-artige Logik, siehe `HangarService` ~Zeile 607) liefert pro verfügbarer Stufe: Ist-Chance (inkl. Boni), Reward-Preview (× Multiplikator), unveränderte Kosten.
- `dispatchShip()` bekommt neuen Parameter `string $difficulty`, validiert gegen `mission['difficulties']`, schreibt ihn in die neue Spalte.
- Neuer Helper `successChanceFor(int $colonyId, array $mission, string $difficulty): float`:
  - Basis aus `game.missions.difficulty.base_chance[$difficulty]`
  - `+ pilotRank(colonyId) * pilot_rank_bonus_pct`
  - `+ (falls mission['requires']['knowledge'] gesetzt) max(0, knowledgeLevel - requiredLevel) * knowledge_bonus_pct_per_level`
  - `min(chance_cap, ...)`
  - Pilot-Rang-Lookup: `Advisor::where('colony_id', $colonyId)->where('personell_id', config('advisors.pilot.id'))->value('rank')`, 0 falls kein Pilot angeheuert (kein Bonus, keine Blockade — Hangar-Missionen laufen auch ohne Pilot, wie heute).

### Resolution (`GameTick::processHangarMissions`)

Nach dem bestehenden SP-Abort-Check (unverändert) und vor dem Reward-Payout, sobald `tick >= returnTick`:

1. Deterministischer Erfolgs-Roll: `mt_srand($rngSeed + missionId + 1)` (eigene Ableitung, +1 versetzt zum Reward-Roll) → `mt_rand() / mt_getrandmax() <= successChance`.
2. **Erfolg**: bestehender `payMissionRewards`-Pfad, `reward`-Werte vor Auszahlung × `reward_multiplier[$difficulty]` skaliert (int-Werte runden, Ranges/loot_table entsprechend skalieren).
3. **Fehlschlag**: kein Reward-Payout, neuer Event `hangar.mission_failed` (Analogie zu `hangar.mission_aborted`). Bei `difficulty === 'schwer'`: zusätzlicher SP-Abzug `hard_fail_extra_wear` auf das Schiff (zusätzlich zur bereits im selben Tick abgezogenen normalen `wear_per_sol` — Ship darf dabei nicht unter 0 SP fallen, floor bei 0 wie beim bestehenden Abort-Pfad).
4. Mission-State in beiden Fällen `'completed'` (nicht `'aborted'` — Fehlschlag ist ein regulärer Missionsausgang, kein Abbruch durch Verschleiß).

### UI

- Dispatch-Dialog (Alpine, Hangar-Screen): Auswahl zwischen den 2 verfügbaren Stufen als Radio/Toggle, zeigt Chance-% und skalierte Reward-Preview live (nutzt Preview-Endpoint).
- Mission-Ergebnis-Anzeige (Event-Feed / INNN o.ä.): Fehlschlag-Fall braucht eigenen Text (`lang/de/missions.php` bzw. Event-Template), unterscheidbar von Erfolg und vom bestehenden Abort-Text.

## Lokalisierung / GDD

- `lang/de/missions.php`: neue Strings für Schwierigkeitsstufen-Labels, Fehlschlag-Meldung.
- GDD §8b: Prosa-Ergänzung zu Erfolgschance/Schwierigkeit + Bonus-Quellen (Pilot-Rang, Kenntnis) — keine konkreten Zahlen im GDD-Text (ADR 0004), Verweis auf `game-reference.md` nach erstem Balance-Pass.
- `docs/game-reference.md`: neue Tabelle für `missions.difficulty`-Werte, sobald kalibriert.

## Offene Nicht-Fragen (bewusst nicht Teil dieser Spec)

- Konkrete Zahlenwerte (Config-Platzhalter oben) — Balance-Pass nach Playtest, wie bei allen anderen Systemen (ADR 0004).
- Welche 2 von 3 Stufen jede der ~13 Katalog-Missionen bekommt — Game-Design-Entscheidung pro Mission, im Implementierungsplan/durch `game-designer` zu treffen, nicht hier strukturell relevant.
