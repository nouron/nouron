# PlaytestBot Playstyle-Profile + `game:playtest`-Command — Design

Status: entworfen, Owner-Freigabe ausstehend
Datum: 2026-08-14

## Kontext

Heutige Phase-2-Pacing-Untersuchung fand: `task_credit_reserve` (Credits ≥ 3.000 für 10 aufeinanderfolgende Sole) wird vom Bot nie erreicht — nicht weil die Ökonomie kollabiert (das ist heute separat behoben, PR #248), sondern weil der Bot jeden Credits-Überschuss sofort für andere Regeln ausgibt (`accept_bar_offer`, `request_ship`), statt gezielt zu sparen, wenn genau dieses Objective gezogen wurde.

Owner-Wunsch, über den konkreten Fix hinaus: der Bot soll grundsätzlich mit einstellbarem, generellem Spielverhalten (sparsam/risikobereit/…) laufen können, damit sich unterschiedliche Spielstile gegeneinander vergleichen lassen — nicht nur ein Bugfix für ein Objective, sondern ein wiederverwendbarer Mechanismus, an den künftige Dimensionen (Risikoneigung etc.) angehängt werden können.

Zusätzlicher Klärungspunkt: CLAUDE.md referenziert bereits `PlaytestBot`/`game:playtest` als primäres Balance-Werkzeug, aber `game:playtest` existiert nicht als Artisan-Command — nur der zugrunde liegende PHPUnit-Test (`tests/Feature/Playtest/PlaytestBotTest.php`), den heute mehrfach manuell per Seed-Schleife wiederholt wurde. Dieses Spec baut den Command nach und schließt damit die Doku-Realitäts-Lücke.

**Wichtige Klarstellung (Owner-Rückfrage, geklärt):** `BotSession` fährt bereits echte HTTP-Requests durch echte Routes/Controller/Middleware (`$test->json()`), kein Mock — nur im selben PHP-Prozess statt über einen echten TCP-Socket. Der `game:playtest`-Command geht NICHT auf einen laufenden Server über echte Sockets (das würde nur Transport-Realismus hinzufügen, keine Spiellogik-Realismus, bei deutlich mehr Risiko/Komplexität) — er orchestriert stattdessen den bestehenden, bewährten PHPUnit-Testpfad von außen.

## Design

### 1. `BotProfile` — typisiertes Playstyle-Value-Object

Neue Datei `tests/Feature/Playtest/BotProfile.php`:

```php
<?php

namespace Tests\Feature\Playtest;

/**
 * Tunable bot playstyle dials. Continuous float knobs (not a fixed enum) so
 * future dimensions (risk tolerance, exploration eagerness, ...) slot in
 * without restructuring — named presets are just convenience constructors
 * over the same typed shape. Add a new property + a case in named() when a
 * new dimension is needed; existing profiles keep their old default (0.0)
 * for it automatically, so nothing else needs to change.
 */
final class BotProfile
{
    public function __construct(
        public readonly string $name = 'default',
        // 0.0 = today's behaviour (spend whenever affordable, no reserve
        // awareness). 1.0 = maximum thrift: hold back discretionary spends
        // once task_credit_reserve is drawn and not yet complete.
        public readonly float $savingsAggressiveness = 0.0,
    ) {}

    public static function named(string $name): self
    {
        return match ($name) {
            'default' => new self('default'),
            'thrifty' => new self('thrifty', savingsAggressiveness: 1.0),
            default => throw new \InvalidArgumentException("Unknown bot profile: {$name}"),
        };
    }
}
```

### 2. `BotStrategy` — Profil einweben

`BotStrategy::default(): array` wird zu `BotStrategy::default(BotProfile $profile = new BotProfile()): array` — Default-Parameter, damit jeder bestehende Aufrufer (`PlaytestBotTest`, `PlaytestBotPhase1Test`) ohne Änderung weiterläuft und exakt das heutige Verhalten bekommt (`savingsAggressiveness=0.0` deaktiviert den neuen Gate vollständig).

Neuer privater Helper:

```php
/**
 * True when a drawn, still-incomplete Phase-2 objective of this task_key
 * exists for the run. Objectives only exist once Phase 2 has started
 * (RunProgressService::transitionToPhase2() → drawObjectives()) — returns
 * false during Phase 1 without a extra phase check needed, since the query
 * itself finds no rows yet.
 */
private static function hasActiveObjective(BotSession $b, string $taskKey): bool
{
    return DB::table('run_objectives')
        ->where('run_id', $b->runId)
        ->where('task_key', $taskKey)
        ->whereNull('completed_at')
        ->exists();
}

/**
 * True when accept_bar_offer/request_ship should hold back this Sol because
 * task_credit_reserve is an active goal and spending now would jeopardize
 * reaching/holding the threshold. Scales the safety buffer with
 * savingsAggressiveness (0.0 → gate never blocks, matches today; 1.0 → 1.5×
 * threshold buffer).
 */
private static function creditReserveGuardBlocks(BotSession $b, BotProfile $profile): bool
{
    if ($profile->savingsAggressiveness <= 0.0) {
        return false;
    }
    if (! self::hasActiveObjective($b, 'task_credit_reserve')) {
        return false;
    }

    $threshold = (int) config('game.run.task_credit_reserve_threshold', 3000);
    $buffer = (int) round($threshold * (1 + 0.5 * $profile->savingsAggressiveness));

    return self::credits($b) < $buffer;
}
```

`accept_bar_offer`'s und `request_ship`'s `when`-Closures bekommen je eine zusätzliche Bedingung: `&& ! self::creditReserveGuardBlocks($b, $profile)`. Der `$profile`-Wert wird beim Aufbau der Regelliste in `default($profile)` per `use ($profile)` in die Closures eingefangen — kein Parameter-Durchreichen durch die Regel-Engine nötig (`PlaysSolLoop` bleibt unverändert).

`hire_advisor` bleibt bewusst unangetastet — Kernprogression (Phase-1-Voraussetzung + `task_senior_advisors`), kein rein diskretionärer Spend.

### 3. `PlaytestBotTest` — Seed/Profil optional aus Env

```php
public function test_bot_plays_a_full_run_and_produces_a_report(): void
{
    $seed = (int) (getenv('PLAYTEST_SEED') ?: 4242);
    $profile = BotProfile::named(getenv('PLAYTEST_PROFILE') ?: 'default');
    $bot = BotSession::boot($this, $seed);
    $rules = BotStrategy::default($profile);
    $report = new RunReport($seed, $profile->name);
    // ... Rest unverändert
}
```

Normaler Aufruf (`php artisan test`, IDE-Runner, CI) bleibt exakt wie heute: `getenv()` liefert `false` → Fallback `4242`/`'default'`, identisches Verhalten zu vor diesem Spec. Nur der neue Command setzt die Env-Variablen gezielt.

### 4. `RunReport` — Profilname im Dateinamen

`__construct(private readonly int $seed)` → `__construct(private readonly int $seed, private readonly string $profile = 'default')`. `write()`s Pfad wird zu:

```php
$path = "{$dir}/{$this->profile}-{$this->seed}-".now()->format('Ymd_His').'.json';
```

Rückwärtskompatibel (Default-Parameter) — jeder unveränderte Aufrufer bekommt weiterhin `default-{seed}-{timestamp}.json` statt bisher `{seed}-{timestamp}.json`. Das ändert bestehende Dateinamen im Log-Verzeichnis geringfügig (Präfix `default-` kommt neu dazu) — kein Konsument liest diese Dateinamen programmatisch außer dem neuen Command (geprüft: `grep -rn "storage/logs/playtest"` zeigt nur `RunReport::write()` selbst und Lesezugriffe durch mich manuell/Debug-Skripte, keine Produktionscode-Abhängigkeit).

### 5. Neuer Command `game:playtest`

Neue Datei `app/Console/Commands/Playtest.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Playtest — orchestrates the PlaytestBot (tests/Feature/Playtest/) across
 * multiple seeds and/or playstyle profiles, and prints a comparison table.
 *
 * Run:   php artisan game:playtest --profiles=default,thrifty --seeds=4242,1337,9001
 * Single combo (equivalent to running the PHPUnit test directly):
 *        php artisan game:playtest --seeds=4242
 *
 * Shells out to PHPUnit per (profile × seed) combination — the bot itself
 * still drives real HTTP requests through PlaytestBotTest/BotSession exactly
 * as it does today; this command only automates what was previously a
 * manual sed-loop plus manual JSON inspection. See
 * docs/superpowers/specs/2026-08-14-bot-playstyle-profiles-design.md.
 */
class Playtest extends Command
{
    protected $signature = 'game:playtest
        {--profiles=default : Comma-separated BotProfile names}
        {--seeds=4242 : Comma-separated integer seeds}';

    protected $description = 'Run the PlaytestBot across seeds/profiles and print a comparison table';

    public function handle(): int
    {
        $profiles = array_filter(array_map('trim', explode(',', (string) $this->option('profiles'))));
        $seeds = array_filter(array_map('trim', explode(',', (string) $this->option('seeds'))));

        $rows = [];

        foreach ($profiles as $profile) {
            foreach ($seeds as $seed) {
                $this->line("Running profile={$profile} seed={$seed}...");

                $result = Process::env([
                    'PLAYTEST_PROFILE' => $profile,
                    'PLAYTEST_SEED' => $seed,
                ])->timeout(120)->run([
                    'php', 'bin/phpunit',
                    '--filter', 'test_bot_plays_a_full_run_and_produces_a_report',
                    'tests/Feature/Playtest/PlaytestBotTest.php',
                ]);

                if (! $result->successful()) {
                    $this->error("profile={$profile} seed={$seed} failed to run:");
                    $this->line($result->errorOutput());

                    continue;
                }

                $report = $this->latestReportFor($profile, $seed);
                if ($report === null) {
                    $this->error("profile={$profile} seed={$seed}: no report file found after run");

                    continue;
                }

                $rows[] = $this->summarize($profile, $seed, $report);
            }
        }

        $this->table(
            ['Profile', 'Seed', 'Status', 'Fail Reason', 'Phase2 Sol', 'Objectives Done', 'Score'],
            $rows
        );

        return self::SUCCESS;
    }

    private function latestReportFor(string $profile, string $seed): ?array
    {
        $pattern = storage_path("logs/playtest/{$profile}-{$seed}-*.json");
        $matches = glob($pattern) ?: [];
        if ($matches === []) {
            return null;
        }

        sort($matches);
        $latest = end($matches);

        return json_decode(file_get_contents($latest), true);
    }

    private function summarize(string $profile, string $seed, array $report): array
    {
        $completed = collect($report['objectives'] ?? [])
            ->filter(fn ($o) => $o['completed_at'] !== null)
            ->count();
        $total = count($report['objectives'] ?? []);

        return [
            $profile,
            $seed,
            $report['outcome']['status'] ?? '?',
            $report['outcome']['fail_reason'] ?? '-',
            $report['phase2_start_sol'] ?? '-',
            "{$completed}/{$total}",
            $report['outcome']['score'] ?? 0,
        ];
    }
}
```

`Process::env()->run([...])` ist Laravel 12s `Illuminate\Support\Facades\Process`-Fassade (bereits Framework-Bestandteil, keine neue Abhängigkeit) — läuft `bin/phpunit` als echten Subprozess, genau wie ein manueller Terminal-Aufruf.

### Testing

- TDD für `hasActiveObjective()`/`creditReserveGuardBlocks()`: da `BotStrategy`-Methoden bisher ausschließlich über volle PlaytestBot-Läufe verifiziert werden (kein separates Unit-Test-File für private Helper, etabliertes Muster in dieser Datei), gilt hier dasselbe Vorgehen — Verifikation über einen gezielten PlaytestBot-Lauf mit `PLAYTEST_PROFILE=thrifty`, der `task_credit_reserve`-Fortschritt vor/nach dem Fix vergleicht, statt eines isolierten PHPUnit-Tests für die private Methode.
- `Playtest`-Command: manueller Lauf `php artisan game:playtest --profiles=default,thrifty --seeds=4242,1337,9001` gegen alle 6 Kombinationen, Vergleichstabelle sichtprüfen.
- Regressionscheck: `php artisan test` (volle Suite) muss weiterhin grün bleiben — `PlaytestBotTest`/`PlaytestBotPhase1Test` ohne gesetzte Env-Variablen müssen sich identisch zum Stand vor diesem Spec verhalten.

## Ausdrücklich nicht Teil dieses Specs

- Weitere Playstyle-Dimensionen (Risikoneigung, Explorationstempo) — `BotProfile` ist so gebaut, dass sie später ergänzbar sind, aber nur `savingsAggressiveness` wird jetzt implementiert.
- Umbau von `BotSession` auf echte Über-Socket-HTTP-Requests (Ansatz B aus der Diskussion) — bewusst verworfen, siehe Kontext-Abschnitt.
- Persistente Speicherung/History von Vergleichsläufen über einzelne Command-Aufrufe hinaus (z. B. eine DB-Tabelle für Playtest-Historie) — nicht angefragt, reiner Terminal-Output reicht für den aktuellen Zweck.
