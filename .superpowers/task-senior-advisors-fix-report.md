# Fix: task_senior_advisors Missionsziel unreichbar (max_slots 5→4)

## TDD-Evidenz

### RED (Test umgeschrieben auf 4 Advisors, alte Implementierung noch `>= 5`)

```
1) Tests\Feature\RunProgressServiceTest::test_task_senior_advisors_completes_when_4_advisors_with_2_senior
task_senior_advisors must be marked completed
Failed asserting that null is not null.

/home/mg/workspace/nouron/tests/Feature/RunProgressServiceTest.php:403

FAILURES!
Tests: 1, Assertions: 1, Failures: 1.
```

### GREEN (nach Fix `RunProgressService::updateSeniorAdvisors()`)

```
bin/phpunit --filter test_task_senior_advisors
OK (2 tests, 3 assertions)
```

### Volle Suite

```
bin/phpunit
Tests: 970, Assertions: 3462, Skipped: 2.
OK, but some tests were skipped!
```
(2 Skips sind vorbestehend/unabhängig vom Fix — Playtest-Bot-Env-Skips.)

### Pint

```
bin/pint --test app/Services/RunProgressService.php tests/Feature/RunProgressServiceTest.php lang/de/run.php lang/en/run.php
{"result":"pass"}
```

## Geänderte Dateien

- `app/Services/RunProgressService.php` — `updateSeniorAdvisors()`: `$totalAdvisors >= 5` → `$totalAdvisors >= config('game.advisor.max_slots')`.
- `tests/Feature/RunProgressServiceTest.php` — Testname + Testkörper `test_task_senior_advisors_completes_when_5_advisors_with_2_senior` → `..._4_advisors_with_2_senior` (4 Advisor-Fixtures statt 5), Doc-Kommentar oben angepasst.
- `lang/de/run.php:8` — `'Expertenstab: Alle 5 Berater-Slots besetzt, ...'` → `'Expertenstab: Alle Berater-Slots besetzt, ...'` (Zahl entfernt).
- `lang/en/run.php:8` — analog `'Expert Staff: all 5 advisor slots filled, ...'` → `'Expert Staff: all advisor slots filled, ...'`.
