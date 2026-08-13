# Phase-1-Sol-30-Deadline Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a fourth Run-Fail-State — Phase 1 must be completed by Sol 30 (`config('game.run.phase1_deadline_sol')`) or the run ends as `failed`/`phase1_deadline`, with an escalating Nexus warning at Sol 22 (`config('game.run.phase1_warning_sol')`) and a distinct "Nexus pulls the plug" tone on the fail screens.

**Architecture:** Follows the exact pattern already used for the three existing fail states in `RunProgressService` (`checkFailStates()` for the instant-fail check, `checkNexusInterventions()`-style escalating-warning method for the Sol-22 heads-up) and the existing `fail_reason`-branching in `SolReportService::finale()` / `resources/views/run/result.blade.php` for the distinct end-screen tone.

**Tech Stack:** Laravel 12, SQLite (test: in-memory via `RefreshDatabase`), PHPUnit (`bin/phpunit` / `php artisan test`), Blade.

## Global Constraints

- PHP/config/lang keys: English. `lang/de/*` values: German (Sprachregeln, CLAUDE.md).
- TDD verbindlich: red test before green code for every behavioral change (`RunProgressService`, `SolReportService` changes). Config/lang/GDD/view-only changes are the documented TDD exception (no independent logic to test) — covered indirectly by the tests in Tasks 3/4/6.
- `config/game.php` and `config/buildings.php` are canonical source of truth — GDD follows config, not the other way around; update GDD to match the config keys chosen here, don't invent different names in GDD.
- Design spec (approved 2026-08-12): `docs/superpowers/specs/2026-08-12-phase1-sol30-deadline-design.md`.

---

### Task 1: Config keys

**Files:**
- Modify: `config/game.php:384-388` (the `'run' =>` block)

**Interfaces:**
- Produces: `config('game.run.phase1_deadline_sol')` (int, default 30), `config('game.run.phase1_warning_sol')` (int, default 22) — consumed by Task 3 and Task 4.

- [ ] **Step 1: Add the two new keys**

In `config/game.php`, inside the `'run' => [` block, directly after the existing `'nexus_debt_fail_threshold' => 12000,` line, add:

```php
        'phase1_deadline_sol' => 30,    // hard fail if Phase 1 isn't complete by this Sol (checkFailStates)
        'phase1_warning_sol' => 22,     // escalating Nexus warning if Phase 1 still incomplete by this Sol
```

- [ ] **Step 2: Verify config loads**

Run: `php artisan tinker --execute="echo config('game.run.phase1_deadline_sol').' / '.config('game.run.phase1_warning_sol');"`
Expected output: `30 / 22`

- [ ] **Step 3: Commit**

```bash
git add config/game.php
git commit -m "config: add phase1_deadline_sol / phase1_warning_sol"
```

---

### Task 2: `checkFailStates()` — Phase-1-deadline fail check

**Files:**
- Modify: `app/Services/RunProgressService.php` (method `checkFailStates()`, currently lines 566-587)
- Test: `tests/Feature/RunProgressServiceTest.php` (new tests near the existing `checkFailStates` block, currently lines 531-565)

**Interfaces:**
- Consumes: `config('game.run.phase1_deadline_sol')` from Task 1.
- Produces: `checkFailStates(Run $run): ?string` now also returns `'phase1_deadline'` — consumed by `GameTick.php`'s existing unconditional call (no wiring change needed there, see Task 2 note below) and by Task 5/6 (`fail_reason` branching).

**Note:** `checkFailStates()` is already called unconditionally at the end of every tick in `app/Console/Commands/GameTick.php:198` regardless of phase — no call-site change is needed for this task, the new check is picked up automatically.

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/RunProgressServiceTest.php`, directly after `test_check_fail_states_returns_null_when_no_fail_conditions` (ends around line 565, before the `// ── endRun ──` section header):

```php
    public function test_check_fail_states_returns_phase1_deadline_when_phase1_incomplete_at_deadline_sol(): void
    {
        $deadlineSol = (int) config('game.run.phase1_deadline_sol', 30);
        $run = $this->makeRun(['current_tick' => $deadlineSol, 'phase' => 1]);

        $this->setTrust(50); // safe, doesn't trigger trust_collapse first

        $result = $this->service->checkFailStates($run);

        $this->assertEquals('phase1_deadline', $result, 'Must return phase1_deadline when still in Phase 1 at the deadline Sol');
    }

    public function test_check_fail_states_does_not_return_phase1_deadline_before_deadline_sol(): void
    {
        $deadlineSol = (int) config('game.run.phase1_deadline_sol', 30);
        $run = $this->makeRun(['current_tick' => $deadlineSol - 1, 'phase' => 1]);

        $this->setTrust(50);

        $result = $this->service->checkFailStates($run);

        $this->assertNull($result, 'Must not fail one Sol before the deadline');
    }

    public function test_check_fail_states_does_not_return_phase1_deadline_when_already_phase2(): void
    {
        $deadlineSol = (int) config('game.run.phase1_deadline_sol', 30);
        $run = $this->makeRun(['current_tick' => $deadlineSol + 5, 'phase' => 2]);

        $this->setTrust(50);

        $result = $this->service->checkFailStates($run);

        $this->assertNull($result, 'Must not apply the Phase-1 deadline once Phase 2 has started');
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=test_check_fail_states_returns_phase1_deadline_when_phase1_incomplete_at_deadline_sol tests/Feature/RunProgressServiceTest.php`
Expected: FAIL (`checkFailStates` returns `null`, not `'phase1_deadline'` — the check doesn't exist yet).

- [ ] **Step 3: Implement the check**

In `app/Services/RunProgressService.php`, inside `checkFailStates()` (around line 566), add the new check. Insert it **before** the existing `time_limit` check (it fires far earlier in practice since `phase1_deadline_sol` < `tick_limit`, and conceptually it's a more specific diagnosis of the same "ran out of time" situation):

```php
        if ($run->phase === 1 && $run->current_tick >= (int) config('game.run.phase1_deadline_sol', 30)) {
            return 'phase1_deadline';
        }

        if ($run->current_tick >= $run->getTickLimit()) {
            return 'time_limit';
        }
```

(The `time_limit` line above is the existing code, shown for placement context — don't duplicate it.)

Also update the method's docblock (directly above `public function checkFailStates`) to list the new fail state, matching the existing list style:

```php
     *  trust_collapse   — trust value < trust_fail_threshold (instant fail).
     *  nexus_debt       — nexus_debt > nexus_debt_fail_threshold (checked here as secondary path).
     *  phase1_deadline  — still in Phase 1 at current_tick >= phase1_deadline_sol (instant fail).
     *  time_limit       — current_tick >= tick_limit.
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=RunProgressServiceTest tests/Feature/RunProgressServiceTest.php`
Expected: PASS, all tests in the file green (including the pre-existing `trust_collapse`/`time_limit`/`null` tests — confirms no regression).

- [ ] **Step 5: Commit**

```bash
git add app/Services/RunProgressService.php tests/Feature/RunProgressServiceTest.php
git commit -m "feat: add phase1_deadline fail state to checkFailStates()"
```

---

### Task 3: Escalating Sol-22 Nexus warning

**Files:**
- Modify: `app/Services/RunProgressService.php` (new methods, place directly after `checkNexusInterventions()` and its private helpers — after `maybeFireSol80Countdown()`, before the `// ── Run end ──` section header)
- Test: `tests/Feature/RunProgressServiceTest.php` (new tests near the existing `checkNexusInterventions` block)

**Interfaces:**
- Consumes: `config('game.run.phase1_warning_sol')` from Task 1; `$this->eventAlreadyFired()` and `$this->createEvent()` (existing private methods on the same class, already used by `maybeFireSol30Warning()` etc.).
- Produces: `checkPhase1DeadlineWarnings(Run $run): void` — consumed by Task 5 (wired into `GameTick.php`). Fires a `colony_log` row with `event = 'run.nexus_phase1_warning'`.

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/RunProgressServiceTest.php`, directly after the last `checkNexusInterventions` test (search for `test_nexus_sol80_countdown` or the last test before the `// ── endRun ──` section — add right before that section header):

```php
    // ── checkPhase1DeadlineWarnings ─────────────────────────────────────────

    public function test_phase1_deadline_warning_fires_at_warning_sol_when_still_phase1(): void
    {
        $warningSol = (int) config('game.run.phase1_warning_sol', 22);
        $run = $this->makeRun(['current_tick' => $warningSol, 'phase' => 1, 'started_at' => now()->subHour()]);

        $this->service->checkPhase1DeadlineWarnings($run);

        $fired = DB::table('colony_log')
            ->where('user', $this->userId)
            ->where('event', 'run.nexus_phase1_warning')
            ->exists();

        $this->assertTrue($fired, 'nexus_phase1_warning must fire once current_tick reaches phase1_warning_sol while still in Phase 1');
    }

    public function test_phase1_deadline_warning_does_not_fire_before_warning_sol(): void
    {
        $warningSol = (int) config('game.run.phase1_warning_sol', 22);
        $run = $this->makeRun(['current_tick' => $warningSol - 1, 'phase' => 1, 'started_at' => now()->subHour()]);

        $this->service->checkPhase1DeadlineWarnings($run);

        $fired = DB::table('colony_log')
            ->where('user', $this->userId)
            ->where('event', 'run.nexus_phase1_warning')
            ->exists();

        $this->assertFalse($fired, 'nexus_phase1_warning must not fire before phase1_warning_sol');
    }

    public function test_phase1_deadline_warning_does_not_fire_once_phase2(): void
    {
        $warningSol = (int) config('game.run.phase1_warning_sol', 22);
        $run = $this->makeRun(['current_tick' => $warningSol + 5, 'phase' => 2, 'started_at' => now()->subHour()]);

        $this->service->checkPhase1DeadlineWarnings($run);

        $fired = DB::table('colony_log')
            ->where('user', $this->userId)
            ->where('event', 'run.nexus_phase1_warning')
            ->exists();

        $this->assertFalse($fired, 'nexus_phase1_warning must not fire once Phase 2 has started');
    }

    public function test_phase1_deadline_warning_fires_only_once(): void
    {
        $warningSol = (int) config('game.run.phase1_warning_sol', 22);
        $run = $this->makeRun(['current_tick' => $warningSol, 'phase' => 1, 'started_at' => now()->subHour()]);

        $this->service->checkPhase1DeadlineWarnings($run);
        $this->service->checkPhase1DeadlineWarnings($run);

        $count = DB::table('colony_log')
            ->where('user', $this->userId)
            ->where('event', 'run.nexus_phase1_warning')
            ->count();

        $this->assertEquals(1, $count, 'nexus_phase1_warning must fire at most once per run');
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=test_phase1_deadline_warning tests/Feature/RunProgressServiceTest.php`
Expected: FAIL with "Call to undefined method ...::checkPhase1DeadlineWarnings()".

- [ ] **Step 3: Implement**

In `app/Services/RunProgressService.php`, add directly after `maybeFireSol80Countdown()` (the last private helper in the `// ── Nexus interventions ──` section), before the `// ── Run end ──` section header:

```php
    // ── Phase-1 deadline warning ──────────────────────────────────────────────

    /**
     * Escalating Nexus warning if Phase 1 is still not complete by
     * config('game.run.phase1_warning_sol') — heads-up before the hard
     * config('game.run.phase1_deadline_sol') fail state in checkFailStates().
     *
     * Called once per tick, only while the run is in Phase 1 (see GameTick.php).
     * Fires at most once per run (guarded by colony_log lookup, same pattern
     * as maybeFireSol30Warning() etc.).
     */
    public function checkPhase1DeadlineWarnings(Run $run): void
    {
        if ($run->phase !== 1) {
            return;
        }

        $warningSol = (int) config('game.run.phase1_warning_sol', 22);
        if ($run->current_tick < $warningSol) {
            return;
        }

        $eventKey = 'run.nexus_phase1_warning';
        if ($this->eventAlreadyFired($run, $eventKey)) {
            return;
        }

        $this->createEvent($run->user_id, $run->current_tick, $eventKey, 'run', [
            'run_id' => $run->id,
            'colony_id' => $run->colony_id,
        ]);
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=RunProgressServiceTest tests/Feature/RunProgressServiceTest.php`
Expected: PASS, full file green.

- [ ] **Step 5: Commit**

```bash
git add app/Services/RunProgressService.php tests/Feature/RunProgressServiceTest.php
git commit -m "feat: add checkPhase1DeadlineWarnings() escalating Nexus warning"
```

---

### Task 4: Wire the warning check into the tick loop

**Files:**
- Modify: `app/Console/Commands/GameTick.php:163-168` (the `if ($run->phase === 1)` block)
- Test: none new — covered by the Feature-level playtest bot test already in the suite (`tests/Feature/Playtest/PlaytestBotTest.php`), which exercises the full tick loop end-to-end. Unit coverage for the method itself is Task 3's job.

**Interfaces:**
- Consumes: `RunProgressService::checkPhase1DeadlineWarnings(Run $run): void` from Task 3.

- [ ] **Step 1: Add the call**

In `app/Console/Commands/GameTick.php`, inside the existing `if ($run->phase === 1) {` block (currently lines 163-168):

```php
        if ($run->phase === 1) {
            if ($runProgressService->checkPhase1Completion($run)) {
                $runProgressService->transitionToPhase2($run);
                $run->refresh();
                $this->line('  Phase 1 completed — transitioning to Phase 2.');
            } else {
                $runProgressService->checkPhase1DeadlineWarnings($run);
            }
        }
```

Note the `else`: no point checking the warning in the same tick Phase 1 just completed — `checkPhase1DeadlineWarnings()` would no-op anyway (`$run->phase` is now 2 after `transitionToPhase2()`), but the `else` makes the intent explicit and skips a redundant DB round-trip.

- [ ] **Step 2: Verify with the existing playtest bot test**

Run: `php artisan test --filter=test_bot_plays_a_full_run_and_produces_a_report tests/Feature/Playtest/PlaytestBotTest.php`
Expected: PASS (no crash from the new call; the bot run completes/fails same as before — this test doesn't assert Phase-1 timing, just that a report is produced).

- [ ] **Step 3: Commit**

```bash
git add app/Console/Commands/GameTick.php
git commit -m "feat: wire checkPhase1DeadlineWarnings() into the Phase-1 tick branch"
```

---

### Task 5: Lang keys (INNN event + fail screens, DE + EN)

**Files:**
- Modify: `lang/de/run.php`, `lang/en/run.php`
- Modify: `lang/de/comm_log.php`, `lang/en/comm_log.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: `__('run.run_failed_phase1_deadline')`, `__('run.result_fail_phase1_deadline')` — consumed by Task 6. `comm_log.events.run.nexus_phase1_warning` / `comm_log.events.run.run_failed_phase1_deadline` — consumed by the existing (unmodified) INNN log rendering, same mechanism as `nexus_warning_sol30`/`run_failed_nexus_debt`.

No test for this task — pure content, exercised indirectly by Task 6's tests (`__('run.run_failed_phase1_deadline')` must resolve to a non-null string for those assertions to pass).

- [ ] **Step 1: `lang/de/run.php`** — add directly after `'run_failed_time' => ...` (line 8):

```php
    'run_failed_phase1_deadline' => 'Fristbruch — Phase 1 wurde nicht rechtzeitig abgeschlossen. Nexus zieht die Konzession mit sofortiger Wirkung.',
```

And directly after `'result_fail_time' => ...` (currently line 26, in the "Result screen" section):

```php
    'result_fail_phase1_deadline' => 'Die Stabilisierungsphase wurde nicht rechtzeitig abgeschlossen. Nexus hat die Reißleine gezogen.',
```

- [ ] **Step 2: `lang/en/run.php`** — mirror, directly after `'run_failed_time' => ...` (line 7):

```php
    'run_failed_phase1_deadline' => 'Deadline breach — Phase 1 was not completed in time. The Nexus terminates the concession with immediate effect.',
```

And directly after `'result_fail_time' => ...` (line 26):

```php
    'result_fail_phase1_deadline' => 'The stabilization phase was not completed in time. The Nexus pulled the plug.',
```

- [ ] **Step 3: `lang/de/comm_log.php`** — add two entries inside the `'run' => [` block (currently lines 55-97), directly after the `'run_failed_time'` entry:

```php
            'nexus_phase1_warning' => [
                'title' => 'Nexus-Warnung — Phase 1',
                'body' => 'Nexus-Protokoll §4.2: Die Stabilisierungsphase ist noch nicht abgeschlossen. Beschleunigen Sie den Fortschritt — bei Fristbruch wird die Konzession beendet.',
                'badge' => 'Warnung',
            ],
            'run_failed_phase1_deadline' => [
                'title' => 'Mission gescheitert — Fristbruch Phase 1',
                'body' => 'Nexus-Protokoll §4.3: Die Stabilisierungsphase wurde nicht innerhalb der Frist abgeschlossen. Die Konzession wird zwangsbeendet.',
                'badge' => 'Gescheitert',
            ],
```

- [ ] **Step 4: `lang/en/comm_log.php`** — mirror, same position in the `'run' => [` block:

```php
            'nexus_phase1_warning' => [
                'title' => 'Nexus Warning — Phase 1',
                'body' => 'Nexus Protocol §4.2: The stabilization phase is not yet complete. Accelerate progress — a deadline breach terminates the concession.',
                'badge' => 'Warning',
            ],
            'run_failed_phase1_deadline' => [
                'title' => 'Mission failed — Phase 1 deadline breach',
                'body' => 'Nexus Protocol §4.3: The stabilization phase was not completed within the deadline. The concession is forcibly terminated.',
                'badge' => 'Failed',
            ],
```

- [ ] **Step 5: Verify all four files parse**

Run: `php -l lang/de/run.php && php -l lang/en/run.php && php -l lang/de/comm_log.php && php -l lang/en/comm_log.php`
Expected: `No syntax errors detected` × 4.

- [ ] **Step 6: Commit**

```bash
git add lang/de/run.php lang/en/run.php lang/de/comm_log.php lang/en/comm_log.php
git commit -m "content: add phase1_deadline lang keys (DE+EN, placeholder copy)"
```

(Placeholder in the commit message is deliberate — matches the project's existing pattern of shipping functional-but-not-final copy with a content-writer polish flagged as a follow-up, same as e.g. the `mission_harvester_salvage` dialogue texts.)

---

### Task 6: Distinct fail-screen tone (`SolReportService` + result view)

**Files:**
- Modify: `app/Services/SolReportService.php` (method `finale()`, currently lines 501-524, the `match ($run->fail_reason)` block)
- Modify: `resources/views/run/result.blade.php:24-30` (the `@elseif($run->fail_reason === "trust_collapse")` block)
- Test: `tests/Feature/SolReportTest.php` (new test near `test_finale_on_failed_run_uses_trust_collapse_body`, currently lines 346-360)

**Interfaces:**
- Consumes: `__('run.run_failed_phase1_deadline')`, `__('run.result_fail_phase1_deadline')` from Task 5.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/SolReportTest.php`, directly after `test_finale_on_failed_run_uses_trust_collapse_body` (ends around line 360):

```php
    public function test_finale_on_failed_run_uses_phase1_deadline_body(): void
    {
        $run = $this->setRunTick(30);
        $before = $this->snapshot($run);
        $run->update(['status' => 'failed', 'fail_reason' => 'phase1_deadline']);
        $run->refresh();

        $report = $this->service()->buildReport($run, $before, false);

        $this->assertNotNull($report['finale']);
        $this->assertSame('lose', $report['finale']['outcome']);
        $this->assertSame(__('run.run_failed_phase1_deadline'), $report['finale']['body']);
        $this->assertNotNull($report['result_url']);
        $this->assertTrue($report['force_show']);
        $this->assertNull($this->groupByKey($report, 'run'));
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_finale_on_failed_run_uses_phase1_deadline_body tests/Feature/SolReportTest.php`
Expected: FAIL — `finale.body` resolves to `__('colony.sol_report_finale_lose_body')` (the `default` match arm), not `__('run.run_failed_phase1_deadline')`.

- [ ] **Step 3: Implement — `SolReportService::finale()`**

In `app/Services/SolReportService.php`, inside the `match ($run->fail_reason)` block (currently lines 513-516), add a new arm directly after `'nexus_debt' => 'run.run_failed_nexus_debt',`:

```php
                'trust_collapse' => 'run.run_failed_trust',
                'time_limit' => 'run.run_failed_time',
                'nexus_debt' => 'run.run_failed_nexus_debt',
                'phase1_deadline' => 'run.run_failed_phase1_deadline',
                default => 'colony.sol_report_finale_lose_body',
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=test_finale_on_failed_run_uses_phase1_deadline_body tests/Feature/SolReportTest.php`
Expected: PASS.

- [ ] **Step 5: Update the result view**

In `resources/views/run/result.blade.php`, the status-body block (currently lines 21-29) branches on `trust_collapse` vs. a generic time-based fallback. Add a `phase1_deadline` branch directly after the `trust_collapse` one:

```blade
                <p class="run-result__status-body">
                    @if ($run->status === "completed")
                        {{ __("run.run_completed") }}
                    @elseif($run->fail_reason === "trust_collapse")
                        {{ __("run.result_fail_trust") }}
                    @elseif($run->fail_reason === "phase1_deadline")
                        {{ __("run.result_fail_phase1_deadline") }}
                    @else
                        {{ __("run.result_fail_time") }}
                    @endif
                </p>
```

This is a Blade view with no dedicated feature test in the current suite (the pre-existing `trust_collapse` branch has none either) — visually verify per Step 6 instead of adding one, consistent with how `trust_collapse` itself is covered.

- [ ] **Step 6: Manual visual check**

Run the dev server (`php artisan serve` or the project's existing local run process) and, in an existing dev/test colony, trigger the result screen with a `phase1_deadline` fail reason — quickest path: use `php artisan tinker` to set an active run's `fail_reason`/`status` directly, then visit `/run/{id}/result`. Confirm the page shows the new "Nexus hat die Reißleine gezogen" body text instead of the generic time-out text.

- [ ] **Step 7: Run the full test suite**

Run: `php artisan test`
Expected: PASS, no regressions (baseline before this plan: 970 passed / 0 skipped, per PR #244's merge — confirm the same count modulo the new tests added in Tasks 2/3/6, and modulo the pre-existing `PlaytestBotTest` non-determinism skip which may or may not trigger on this particular run).

- [ ] **Step 8: Commit**

```bash
git add app/Services/SolReportService.php resources/views/run/result.blade.php tests/Feature/SolReportTest.php
git commit -m "feat: distinct phase1_deadline tone on Sol-report finale + result screen"
```

---

### Task 7: GDD update

**Files:**
- Modify: `docs/GDD.md` (§18.2, currently starting line 3438; the "Drei Fail States" intro sentence and the fail-state list)

**Interfaces:**
- Consumes: nothing (documentation only, no code path — TDD-exempt per CLAUDE.md).

- [ ] **Step 1: Update the §18.2 intro**

In `docs/GDD.md`, find the line (around 3438):

```
### 18.2 Fail States

Drei Fail States. Alle werden am Ende der Tick-Phase 5 geprüft, nach dem Objective-Update (damit ein Sieg auf demselben Tick immer Vorrang vor einem gleichzeitigen Fail State hat). Kanonische Implementierung: `RunProgressService::checkFailStates()`.
```

Replace `Drei Fail States.` with `Vier Fail States.` and add one sentence noting the new one is Phase-1-specific:

```
### 18.2 Fail States

Vier Fail States. Alle werden am Ende der Tick-Phase 5 geprüft, nach dem Objective-Update (damit ein Sieg auf demselben Tick immer Vorrang vor einem gleichzeitigen Fail State hat). Kanonische Implementierung: `RunProgressService::checkFailStates()`. Der vierte (Phase-1-Fristbruch) kann nur in Phase 1 auftreten — sobald Phase 2 erreicht ist, greifen ausschließlich die anderen drei.
```

- [ ] **Step 2: Add the new Fail-State subsection**

Directly after the existing `#### Fail State 3 — Fristablauf ohne Sieg` subsection (find its end — it runs until the next `---` horizontal rule or the next `####` heading), add a new subsection:

```markdown
---

#### Fail State 4 — Phase-1-Fristbruch

**Bedingung:** `run.phase === 1 && current_tick >= config('game.run.phase1_deadline_sol')` → Standardwert **Sol 30**

**Auslösung:** Instant in dem Tick, in dem die Deadline erreicht wird, sofern Phase 1 noch nicht abgeschlossen ist (`RunProgressService::checkPhase1Completion()`).

Owner-Vorgabe 2026-08-12: Phase 1 soll im Normalfall Sol 15-20 abgeschlossen sein, spätestens Sol 30. Datenbasis: PlaytestBot-Auswertung (PR #244, mehrere Seeds/Reruns) zeigte Phase 1 aktuell frühestens Sol 55-65 abgeschlossen — deutlich außerhalb des Zielkorridors. Dieser Fail State macht die Deadline spielmechanisch verbindlich; die zugehörige Rebalancierung (Harvester-Ertrag u.a.), die Sol 15-20 überhaupt erreichbar macht, ist ein separater, größerer Auftrag (Anhang A.4, "Phase-1-Pacing auf Sol-15-20-Ziel neu herleiten").

**Warnstufen (INNN):**

| Sol | Maßnahme |
|-----|---------|
| Sol 22 (`config('game.run.phase1_warning_sol')`) | INNN-Warnung von Nexus, sofern Phase 1 noch nicht abgeschlossen — einmalig pro Run |
| Sol 30 | Fail State — Run endet sofort |

Vollständiges Design: `docs/superpowers/specs/2026-08-12-phase1-sol30-deadline-design.md`.

**Narrativer Ausgang:** "Die Stabilisierungsphase wurde nicht rechtzeitig abgeschlossen. Nexus zieht die Konzession mit sofortiger Wirkung."
```

- [ ] **Step 3: Verify no broken cross-references**

Run: `grep -n "Drei Fail States\|Vier Fail States" docs/GDD.md`
Expected: only the updated line found, no leftover "Drei Fail States" elsewhere referring to this section.

- [ ] **Step 4: Commit**

```bash
git add docs/GDD.md
git commit -m "GDD: document Fail State 4 (Phase-1-Fristbruch, Sol 30)"
```

---

### Task 8: CHANGELOG + full verification

**Files:**
- Modify: `CHANGELOG.md`

- [ ] **Step 1: Run the full suite one more time**

Run: `php artisan test`
Expected: all green, no regressions.

- [ ] **Step 2: Add the CHANGELOG entry**

At the top of `CHANGELOG.md`, under today's date section (create a new `## 2026-08-12` section if the most recent one is a different date; if today's section already exists from earlier work this session, add a new paragraph to it):

```markdown
**Phase-1-Sol-30-Deadline implementiert** (`app/Services/RunProgressService.php`, `app/Console/Commands/GameTick.php`). Neuer, vierter Fail-State: Phase 1 muss bis Sol 30 abgeschlossen sein (`config('game.run.phase1_deadline_sol')`), sonst endet der Run sofort als `failed`/`phase1_deadline` — analog zu den bestehenden drei Fail-States (Trust-Kollaps, Nexus-Schulden, Zeitlimit). Eskalierende Nexus-Warnung ab Sol 22 (`config('game.run.phase1_warning_sol')`), falls Phase 1 noch offen ist. Eigener, wahrnehmbar anderer Fail-Screen-Ton ("Nexus zieht die Reißleine") in `SolReportService::finale()` und `run/result.blade.php`. Datenbasis: PlaytestBot-Auswertung (PR #244) zeigte Phase 1 aktuell erst bei Sol 55-65 abgeschlossen, weit außerhalb des Owner-Zielkorridors (normal Sol 15-20). Die eigentliche Rebalancierung, die den Zielkorridor erreichbar macht, ist NICHT Teil dieser Änderung — separater `game-designer`-Auftrag (GDD Anhang A.4). Spec: `docs/superpowers/specs/2026-08-12-phase1-sol30-deadline-design.md`.
```

- [ ] **Step 3: Commit**

```bash
git add CHANGELOG.md
git commit -m "docs: CHANGELOG entry for Phase-1-Sol-30-Deadline"
```

---

## Post-plan follow-ups (explicitly out of scope here, tracked elsewhere)

- Rebalancing to make Sol 15-20 Phase-1 completion realistically achievable (separate `game-designer` task, GDD Anhang A.4).
- Content-writer polish pass on the placeholder copy added in Task 5.
- Optional UI countdown ("N Sole bis Fristablauf") — not specified, raise separately with `ui-specialist` if wanted.
