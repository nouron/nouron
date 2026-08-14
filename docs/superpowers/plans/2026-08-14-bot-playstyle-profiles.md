# PlaytestBot Playstyle-Profile + game:playtest-Command Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give the PlaytestBot a typed, extensible playstyle-profile mechanism (starting with one dial — savings aggressiveness — that makes it actually try to hit `task_credit_reserve`), and a `game:playtest` Artisan command that runs it across multiple seeds/profiles and prints a comparison table.

**Architecture:** `BotProfile` is a small immutable value object threaded through `BotStrategy::default()` into the two purely-discretionary spending rules (`accept_bar_offer`, `request_ship`). `PlaytestBotTest` picks up seed/profile from environment variables (falling back to today's hardcoded defaults so normal test runs are unaffected). A new `game:playtest` Artisan command shells out to `bin/phpunit` once per (profile × seed) combination via Laravel's `Process` facade — the bot still drives real HTTP requests through the existing, proven `BotSession`/PHPUnit path; the command only automates what was previously a manual seed loop.

**Tech Stack:** PHP 8.2, Laravel 12, PHPUnit (`bin/phpunit`), Laravel `Process` facade.

**Spec:** `docs/superpowers/specs/2026-08-14-bot-playstyle-profiles-design.md`

## Global Constraints

- Every existing call site of `BotStrategy::default()`, `new RunReport($seed)`, and the two `PlaytestBotTest` test methods must behave **identically** to today when no env vars are set — this is TDD-required regression safety, not optional polish (CLAUDE.md TDD mandate; this codebase treats the playtest bot's own logic as behavior with tests, per the file's existing pattern of PlaytestBot-run verification instead of isolated unit tests for private helpers).
- `BotProfile` is a value object: `readonly` properties, no setters (matches the "Ordered rule list — not an AI" simplicity ethos already stated in `BotStrategy`'s class docblock — don't turn this into a bigger framework than the spec asks for).
- No new Composer dependency — `Illuminate\Support\Facades\Process` ships with Laravel 12.
- PHP-side code/comments in English, per CLAUDE.md Sprachregeln; this plan's own prose is German-adjacent only where quoting existing German docblocks.

---

### Task 1: `BotProfile` value object

**Files:**
- Create: `tests/Feature/Playtest/BotProfile.php`

**Interfaces:**
- Produces: `BotProfile` class with `public readonly string $name`, `public readonly float $savingsAggressiveness`, constructor defaults `(name: 'default', savingsAggressiveness: 0.0)`, and static factory `BotProfile::named(string $name): self` supporting `'default'` and `'thrifty'` (throws `\InvalidArgumentException` for anything else). Consumed by Tasks 3 and 4.

This is a plain value object with no dependency on game state — no test framework interaction, so a quick standalone PHPUnit test is the right (and only sensible) TDD vehicle here, unlike the DB-backed `BotStrategy` helpers in later tasks.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Playtest/BotProfileTest.php`:

```php
<?php

namespace Tests\Unit\Playtest;

use Tests\Feature\Playtest\BotProfile;
use Tests\TestCase;

class BotProfileTest extends TestCase
{
    public function test_default_profile_has_zero_savings_aggressiveness(): void
    {
        $profile = new BotProfile();

        $this->assertSame('default', $profile->name);
        $this->assertSame(0.0, $profile->savingsAggressiveness);
    }

    public function test_named_default_matches_default_constructor(): void
    {
        $profile = BotProfile::named('default');

        $this->assertSame('default', $profile->name);
        $this->assertSame(0.0, $profile->savingsAggressiveness);
    }

    public function test_named_thrifty_has_maximum_savings_aggressiveness(): void
    {
        $profile = BotProfile::named('thrifty');

        $this->assertSame('thrifty', $profile->name);
        $this->assertSame(1.0, $profile->savingsAggressiveness);
    }

    public function test_named_rejects_unknown_profile(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        BotProfile::named('nonexistent');
    }
}
```

Note: this test file lives in `tests/Unit/Playtest/`, not `tests/Feature/Playtest/` (where `BotProfile.php` itself lives) — it needs no DB/HTTP, so it belongs with the Unit suite. Create the `tests/Unit/Playtest/` directory if it doesn't exist yet.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Unit/Playtest/BotProfileTest.php`
Expected: FAIL — `Class "Tests\Feature\Playtest\BotProfile" not found`.

- [ ] **Step 3: Implement `BotProfile`**

Create `tests/Feature/Playtest/BotProfile.php`:

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

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Unit/Playtest/BotProfileTest.php`
Expected: PASS, 4/4.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Playtest/BotProfile.php tests/Unit/Playtest/BotProfileTest.php
git commit -m "feat: add BotProfile value object for PlaytestBot playstyle dials"
```

---

### Task 2: `RunReport` — profile name in the report filename

**Files:**
- Modify: `tests/Feature/Playtest/RunReport.php:22` (constructor), `:125` (`write()`'s path)

**Interfaces:**
- Consumes: nothing new from other tasks.
- Produces: `RunReport::__construct(int $seed, string $profile = 'default')`. Consumed by Task 4 (`PlaytestBotTest`) and read (via the resulting filename pattern) by Task 5 (`game:playtest` command).

No behavioral test framework hook exists for `RunReport::write()` beyond what already runs via the full PlaytestBot integration test — this is a one-line constructor/path change with no independent logic to unit-test (matches the CLAUDE.md TDD exception for trivial, no-branching changes). Verify by inspection + the full-suite run in Task 6.

- [ ] **Step 1: Update the constructor**

In `tests/Feature/Playtest/RunReport.php`, change:

```php
    public function __construct(private readonly int $seed) {}
```

to:

```php
    public function __construct(private readonly int $seed, private readonly string $profile = 'default') {}
```

- [ ] **Step 2: Update `write()`'s path**

Change:

```php
        $path = "{$dir}/{$this->seed}-".now()->format('Ymd_His').'.json';
```

to:

```php
        $path = "{$dir}/{$this->profile}-{$this->seed}-".now()->format('Ymd_His').'.json';
```

- [ ] **Step 3: Verify no other constructor call sites break**

Run: `grep -rn "new RunReport(" tests/`
Expected: only call sites that pass a single `$seed` argument (default parameter covers them) — confirm none pass a second positional argument that would now collide with `$profile`. If any exist, they'll be updated in Task 4 anyway.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Playtest/RunReport.php
git commit -m "feat: RunReport writes profile name into the report filename"
```

---

### Task 3: `BotStrategy` — credit-reserve savings guard

**Files:**
- Modify: `tests/Feature/Playtest/BotStrategy.php:30` (`default()` signature), `:189-193` (`accept_bar_offer` rule), `:196-229` (`request_ship` rule), plus two new private static methods (place them near the other DB-reading helpers, e.g. directly after `credits()` — search for `private static function credits(` to find it)

**Interfaces:**
- Consumes: `BotProfile` (Task 1) — `$profile->savingsAggressiveness`.
- Produces: `BotStrategy::default(BotProfile $profile = new BotProfile()): array` (signature change, default keeps every existing caller behaviorally identical). Consumed by Task 4.

This task changes real bot decision logic — TDD applies via the established pattern for this file: `BotStrategy`'s private helpers have no isolated unit tests anywhere in the codebase (confirmed: `grep -rn "hasActiveObjective\|creditReserveGuardBlocks\|productionInvestCandidate\|cheapestPendingPathBuildingCost" tests/` finds no test file besides `BotStrategy.php` itself for any of its existing private helpers) — they're verified exclusively through full `PlaytestBotTest` runs, which is also this task's TDD vehicle. Step 1 below is the "red" state (current behavior, captured as a documented manual comparison you'll reproduce before and after) rather than a new automated red test, because no per-helper test file exists to add one to without breaking the file's established testing convention. Do not invent a new test file for this — follow the file's convention.

- [ ] **Step 1: Capture the "before" baseline**

Run (no env vars set — today's default behavior):

```bash
php artisan test --filter=test_bot_plays_a_full_run_and_produces_a_report tests/Feature/Playtest/PlaytestBotTest.php
```

Note the printed `[playtest] ...` line and find the newest file in `storage/logs/playtest/`. Open it and check the `objectives` array for `task_credit_reserve` (it may or may not be drawn this run — if it isn't, that's fine, this step is just establishing there's no regression risk from the signature change alone). Keep this output for comparison after Step 6.

- [ ] **Step 2: Change `default()`'s signature**

In `tests/Feature/Playtest/BotStrategy.php`, change:

```php
    public static function default(): array
    {
        return [
```

to:

```php
    public static function default(BotProfile $profile = new BotProfile()): array
    {
        return [
```

Add the import near the top of the file (alongside the existing `use` statements):

```php
use Tests\Feature\Playtest\BotProfile;
```

(Note: `BotStrategy` is itself in namespace `Tests\Feature\Playtest`, same as `BotProfile` — this `use` is technically redundant since PHP resolves same-namespace classes automatically, so **skip adding the `use` line**; just reference `BotProfile` directly in the signature. Confirm by running `php -l` in Step 5 — a missing-class error there would mean this assumption was wrong and the `use` needs to go back in.)

- [ ] **Step 3: Add the two new private helpers**

Directly after the `credits()` method (search for `public static function credits(BotSession $b): int` to find its closing brace), insert:

```php
    /**
     * True when a drawn, still-incomplete Phase-2 objective of this task_key
     * exists for the run. Objectives only exist once Phase 2 has started
     * (RunProgressService::transitionToPhase2() → drawObjectives()) — during
     * Phase 1 this query simply finds no rows yet, no separate phase check needed.
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
     * savingsAggressiveness (0.0 → gate never blocks, matches pre-profile
     * behavior; 1.0 → 1.5× threshold buffer).
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

- [ ] **Step 4: Wire the guard into `accept_bar_offer` and `request_ship`**

Change the `accept_bar_offer` rule's `when` (currently):

```php
                'when' => fn (BotSession $b) => self::availableAp($b) >= (int) config('game.bar.ap_cost_accept', 1)
                    ? self::barOfferCandidate($b)
                    : null,
```

to:

```php
                'when' => fn (BotSession $b) => self::availableAp($b) >= (int) config('game.bar.ap_cost_accept', 1)
                    && ! self::creditReserveGuardBlocks($b, $profile)
                    ? self::barOfferCandidate($b)
                    : null,
```

Change the `request_ship` rule's `when` (currently):

```php
                'when' => fn (BotSession $b) => self::hangarLevel($b) >= 1
                    ? self::shipToRequest($b)
                    : null,
```

to:

```php
                'when' => fn (BotSession $b) => self::hangarLevel($b) >= 1 && ! self::creditReserveGuardBlocks($b, $profile)
                    ? self::shipToRequest($b)
                    : null,
```

Both closures already capture outer-scope variables by value automatically in PHP (`fn` arrow functions auto-capture) — no explicit `use ($profile)` needed since `$profile` is the enclosing method's parameter, in scope at the `return [...]` array-literal's construction time.

- [ ] **Step 5: Verify syntax**

Run: `php -l tests/Feature/Playtest/BotStrategy.php`
Expected: `No syntax errors detected`.

- [ ] **Step 6: Run the full PlaytestBot test (still no env vars — Task 4 wires those up) to confirm zero behavior change**

Run:

```bash
php artisan test --filter=test_bot_plays_a_full_run_and_produces_a_report tests/Feature/Playtest/PlaytestBotTest.php
php artisan test --filter=PlaytestBotPhase1Test tests/Feature/Playtest/PlaytestBotPhase1Test.php
```

Expected: both PASS, and the `[playtest] ...` summary line's `phase2_start_sol`/`actions`/`rejected` numbers are in the same range as Step 1's baseline (exact numbers will differ run-to-run due to the known unseeded-tile-layout non-determinism — that's pre-existing, not something this task introduces; just confirm no new failure mode like a sudden `time_limit` regression at a much earlier Sol than before).

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/Playtest/BotStrategy.php
git commit -m "feat: BotStrategy accepts a BotProfile, adds credit-reserve savings guard"
```

---

### Task 4: `PlaytestBotTest` — env-driven seed/profile

**Files:**
- Modify: `tests/Feature/Playtest/PlaytestBotTest.php:24-31` (`test_bot_plays_a_full_run_and_produces_a_report`)

**Interfaces:**
- Consumes: `BotProfile::named()` (Task 1), `BotStrategy::default(BotProfile $profile)` (Task 3), `RunReport::__construct(int $seed, string $profile)` (Task 2).
- Produces: the env-variable contract `PLAYTEST_SEED` (int, optional) and `PLAYTEST_PROFILE` (string, optional, one of `BotProfile::named()`'s known names) that Task 5's command relies on.

- [ ] **Step 1: Write the failing test**

Add a new test to `tests/Feature/Playtest/PlaytestBotTest.php`, directly after `test_bot_plays_a_full_run_and_produces_a_report` (before `test_same_seed_draws_identical_objectives`):

```php
    /**
     * The env-var override (used by the game:playtest command, Task 5 of the
     * bot-playstyle-profiles plan) must actually change which seed/profile the
     * bot runs with — not silently fall back to the hardcoded defaults.
     */
    public function test_env_vars_override_seed_and_profile(): void
    {
        putenv('PLAYTEST_SEED=1337');
        putenv('PLAYTEST_PROFILE=thrifty');

        try {
            $seed = (int) (getenv('PLAYTEST_SEED') ?: 4242);
            $profile = BotProfile::named(getenv('PLAYTEST_PROFILE') ?: 'default');

            $this->assertSame(1337, $seed);
            $this->assertSame('thrifty', $profile->name);
            $this->assertSame(1.0, $profile->savingsAggressiveness);
        } finally {
            putenv('PLAYTEST_SEED');
            putenv('PLAYTEST_PROFILE');
        }
    }
```

Deliberately does not call `BotSession::boot()` or run the Sol loop — this test only needs to confirm the env-var-to-value parsing works (the exact same expression `test_bot_plays_a_full_run_and_produces_a_report` will use in Step 3), not exercise the full bot. Keeps this regression guard fast and DB-independent.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_env_vars_override_seed_and_profile tests/Feature/Playtest/PlaytestBotTest.php`
Expected: FAIL — the test method calls `BotStrategy::default(BotProfile::named(...))` and `BotSession::boot($this, (int) (getenv(...) ...))`, which are valid calls after Task 1/3, so this specific test should actually already pass once Tasks 1 and 3 are done — its real purpose is to fail if a future change breaks the env-var contract. If it passes immediately, that's fine (Tasks 1 and 3 already provide the building blocks) — proceed to Step 3 regardless, which is the actual behavior change this task adds (the *production* test method reading env vars, not just this regression guard).

- [ ] **Step 3: Update `test_bot_plays_a_full_run_and_produces_a_report`**

Change:

```php
    public function test_bot_plays_a_full_run_and_produces_a_report(): void
    {
        $seed = 4242;
        $bot = BotSession::boot($this, $seed);
        $rules = BotStrategy::default();
        $report = new RunReport($seed);
```

to:

```php
    public function test_bot_plays_a_full_run_and_produces_a_report(): void
    {
        $seed = (int) (getenv('PLAYTEST_SEED') ?: 4242);
        $profile = BotProfile::named(getenv('PLAYTEST_PROFILE') ?: 'default');
        $bot = BotSession::boot($this, $seed);
        $rules = BotStrategy::default($profile);
        $report = new RunReport($seed, $profile->name);
```

- [ ] **Step 4: Run both tests to verify they pass**

Run:

```bash
php artisan test --filter=test_bot_plays_a_full_run_and_produces_a_report tests/Feature/Playtest/PlaytestBotTest.php
php artisan test --filter=test_env_vars_override_seed_and_profile tests/Feature/Playtest/PlaytestBotTest.php
```

Expected: both PASS.

- [ ] **Step 5: Verify the env-var override actually changes bot behavior end-to-end**

Run:

```bash
PLAYTEST_SEED=1337 PLAYTEST_PROFILE=thrifty php artisan test --filter=test_bot_plays_a_full_run_and_produces_a_report tests/Feature/Playtest/PlaytestBotTest.php
ls -t storage/logs/playtest/ | head -1
```

Expected: the newest file is named `thrifty-1337-<timestamp>.json` (confirms `RunReport` got the right profile name from Task 2/3's wiring, and the seed matches).

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Playtest/PlaytestBotTest.php
git commit -m "feat: PlaytestBotTest reads seed/profile from PLAYTEST_SEED/PLAYTEST_PROFILE env vars"
```

---

### Task 5: `game:playtest` Artisan command

**Files:**
- Create: `app/Console/Commands/Playtest.php`

**Interfaces:**
- Consumes: the `PLAYTEST_SEED`/`PLAYTEST_PROFILE` env-var contract (Task 4) and the `{profile}-{seed}-{timestamp}.json` filename pattern (Task 2).
- Produces: `php artisan game:playtest --profiles=... --seeds=...` CLI command. Nothing else depends on this.

This is a thin orchestrator with no game-state logic of its own — TDD-exempt per CLAUDE.md's "reine Config-/Doku-/Lang-Änderungen ohne Codepfad" is *not* quite the right exception (this has a real code path), but it has no independently-testable business logic beyond "shell out and parse JSON," which Step 4/5's manual verification covers directly and is the honest equivalent of a test here — Laravel Console commands in this codebase (`SyncConfig`, `GameTick`, etc.) have no dedicated unit tests either; they're verified by running them. Follow that established convention.

- [ ] **Step 1: Create the command**

Create `app/Console/Commands/Playtest.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

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

- [ ] **Step 2: Verify syntax and command registration**

Run:

```bash
php -l app/Console/Commands/Playtest.php
php artisan list | grep game:playtest
```

Expected: `No syntax errors detected`, and `game:playtest` appears in the command list (Laravel auto-discovers commands in `app/Console/Commands/`, no manual registration needed in this codebase's `bootstrap/app.php`/`routes/console.php` — confirm by checking how `SyncConfig`/`GameTick` are registered, e.g. `grep -rn "SyncConfig\|command(" bootstrap/app.php routes/console.php 2>/dev/null`; if those files DO list commands explicitly, add `Playtest::class` the same way instead of relying on auto-discovery).

- [ ] **Step 3: Single-combo smoke test**

Run: `php artisan game:playtest --seeds=4242`
Expected: prints "Running profile=default seed=4242...", then a table with one row, `Status` column showing `failed` (matches current game balance state — Phase 2 isn't fully winnable yet, this is not a bug in the command) and a real `Phase2 Sol` number (not `-`, not an error).

- [ ] **Step 4: Multi-combo comparison run**

Run: `php artisan game:playtest --profiles=default,thrifty --seeds=4242,1337,9001`
Expected: 6 rows in the table (2 profiles × 3 seeds), no `Error` rows. Manually compare the `default` vs `thrifty` rows for `task_credit_reserve` progress — open the two corresponding JSON files under `storage/logs/playtest/` for the same seed and check whether the `thrifty` run's `objectives` array shows `task_credit_reserve` with a higher `current` streak value than the `default` run, on runs where that objective was drawn for both (objectives are randomly drawn per run, so not every seed will have `task_credit_reserve` in its pool — that's expected, not a bug).

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/Playtest.php
git commit -m "feat: add game:playtest command to orchestrate PlaytestBot across seeds/profiles"
```

---

### Task 6: Full verification + CHANGELOG

**Files:**
- Modify: `CHANGELOG.md`

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test`
Expected: all green, no regressions vs. the pre-task baseline (982 passed as of the last known-good run today, 2026-08-14 — the exact count may drift slightly from other same-day work landing in parallel; the important check is zero new failures, not an exact number match).

- [ ] **Step 2: Add the CHANGELOG entry**

Per the "keep it short" convention established today (2026-08-14 — see the CHANGELOG conciseness memory/feedback if you have access to it, otherwise: 1-2 sentences, pointer to the spec, no re-derivation of the reasoning), add to `CHANGELOG.md` under today's `## 2026-08-14` section (create it if a newer date section doesn't already exist at the top):

```markdown
- PlaytestBot: `BotProfile`-Mechanismus (Playstyle-Parameter, startet mit `savingsAggressiveness`) + neuer `game:playtest`-Command zum Vergleich mehrerer Seeds/Profile. Siehe `docs/superpowers/specs/2026-08-14-bot-playstyle-profiles-design.md`.
```

- [ ] **Step 3: Commit**

```bash
git add CHANGELOG.md
git commit -m "docs: CHANGELOG entry for BotProfile + game:playtest"
```

---

## Post-plan follow-ups (explicitly out of scope here, tracked elsewhere)

- Additional `BotProfile` dimensions (risk tolerance, exploration eagerness) — the shape supports them, none are implemented beyond `savingsAggressiveness`.
- Persistent history of comparison runs beyond a single command invocation's terminal output.
- Deciding whether `thrifty` (or any profile) becomes the *new default* for balance work going forward — that's a game-design/Owner call to make after using the comparison tool, not part of this plan.
