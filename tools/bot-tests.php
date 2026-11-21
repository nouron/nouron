<?php

/**
 * Bot test runner (T58d, owner 2026-11-20): runs the playtest test suite on several processes
 * with a progress line per finished unit, can be stopped at any time (Ctrl+C) and resumed
 * without repeating what already passed, and starts the slowest units first (timings of the
 * last run). Heavy files (last run > --split seconds) are split into single tests.
 *
 *   php tools/bot-tests.php                 fresh run of tests/Feature/Playtest
 *   php tools/bot-tests.php --resume        continue an interrupted run (passed units are skipped)
 *   php tools/bot-tests.php --processes=16  number of parallel test processes (default: cores - 4)
 *   php tools/bot-tests.php --dir=tests/Feature/Bar   another test directory
 *
 * Every process gets its own throwaway database nouron_test_test_<20+slot> (never the dev DB);
 * the schema cache (tests/Concerns/TestSchemaCache) skips rebuilding it when migrations are
 * unchanged. State, timings and one log per unit: storage/logs/bot-tests/.
 */

use Tests\Concerns\TestRunPlan;

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';

$opts = getopt('', ['resume', 'processes:', 'dir:', 'split:']);
$dir = rtrim($opts['dir'] ?? 'tests/Feature/Playtest', '/');
$workers = max(1, (int) ($opts['processes'] ?? max(1, (int) shell_exec('nproc') - 4)));
$splitAbove = (float) ($opts['split'] ?? 60);
$stateDir = $root.'/storage/logs/bot-tests/'.preg_replace('/[^A-Za-z0-9_]/', '_', $dir);
@mkdir($stateDir.'/logs', 0777, true);
$timingsFile = $stateDir.'/timings.json';
$stateFile = $stateDir.'/state.json';
$timings = is_file($timingsFile) ? (array) json_decode((string) file_get_contents($timingsFile), true) : [];
$fingerprint = trim((string) shell_exec('cd '.escapeshellarg($root).' && git rev-parse HEAD && git diff HEAD | sha1sum'));

$results = [];
if (isset($opts['resume'])) {
    $state = is_file($stateFile) ? (array) json_decode((string) file_get_contents($stateFile), true) : [];
    $results = (array) ($state['results'] ?? []);
    if ($results === []) {
        echo "Nichts fortzusetzen, starte neu.\n";
    } elseif (($state['fingerprint'] ?? null) !== $fingerprint) {
        echo "Hinweis: Der Code hat sich seit dem unterbrochenen Lauf geändert; bestandene Tests werden trotzdem übersprungen.\n";
    }
}

// Units: one per test file; a file that took longer than --split seconds last time is split
// into its single tests (as `phpunit --list-tests` names them) so it does not decide the end alone.
$units = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$dir, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if (! str_ends_with($f->getFilename(), 'Test.php')) {
        continue;
    }
    $file = substr($f->getPathname(), strlen($root) + 1);
    // A split file's time is the sum of its single tests from the last run.
    $fileSeconds = $timings[$file] ?? array_sum(array_filter($timings, fn ($t, $u) => str_starts_with($u, $file.'::'), ARRAY_FILTER_USE_BOTH));
    if ($fileSeconds > $splitAbove) {
        $list = (string) shell_exec('cd '.escapeshellarg($root).' && php bin/phpunit --list-tests '.escapeshellarg($file).' 2>/dev/null');
        preg_match_all('/^ - \S+::(.+)$/m', $list, $m);
        foreach ($m[1] ?? [] as $test) {
            $units[] = $file.'::'.$test;
        }
        if (! empty($m[1])) {
            continue;
        }
    }
    $units[] = $file;
}
sort($units);
$plan = new TestRunPlan($units, $timings, $results);
$queue = $plan->pending();
$total = $plan->total();
$done = $plan->doneCount();
$failures = 0; // failed units run again on --resume, so only this run's failures count
$saveState = function () use (&$results, $stateFile, $fingerprint): void {
    file_put_contents($stateFile, json_encode(['fingerprint' => $fingerprint, 'results' => $results], JSON_PRETTY_PRINT));
};

echo sprintf("%d Einheiten in %s, %d schon erledigt, %d Prozesse. Abbrechen mit Strg+C, fortsetzen mit --resume.\n", $total, $dir, $done, $workers);

$stop = false;
if (function_exists('pcntl_async_signals')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGINT, function () use (&$stop) {
        $stop = true;
    });
    pcntl_signal(SIGTERM, function () use (&$stop) {
        $stop = true;
    });
}

$start = time();
$running = []; // slot => [proc, unit, started, logPath]
$freeSlots = range(1, $workers);
while (($queue !== [] && ! $stop) || $running !== []) {
    if ($stop) {
        // Ctrl+C: stop the running tests right away; they count as not run and start again on --resume.
        foreach ($running as [$proc]) {
            proc_terminate($proc, SIGTERM);
            proc_close($proc);
        }
        $running = [];
        break;
    }
    while (! $stop && $queue !== [] && $freeSlots !== []) {
        $unit = array_shift($queue);
        $slot = array_shift($freeSlots);
        [$file, $test] = array_pad(explode('::', $unit, 2), 2, null);
        $cmd = ['php', '-d', 'opcache.enable_cli=1', 'bin/phpunit', '--do-not-cache-result'];
        if ($test !== null) {
            // --list-tests names a data set as method"name", the filter matches method with data set "name".
            $name = preg_match('/^(\w+)"(.*)"$/', $test, $d) ? $d[1].' with data set "'.$d[2].'"' : $test;
            $cmd[] = '--filter';
            $cmd[] = '/::'.preg_quote($name, '/').'$/';
        }
        $cmd[] = $file;
        $log = $stateDir.'/logs/'.preg_replace('/[^A-Za-z0-9_.-]/', '_', $unit).'.log';
        $env = ['LARAVEL_PARALLEL_TESTING' => '1', 'TEST_TOKEN' => (string) (20 + $slot)] + getenv();
        $proc = proc_open($cmd, [0 => ['file', '/dev/null', 'r'], 1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipes, $root, $env);
        $running[$slot] = [$proc, $unit, microtime(true), $log];
    }

    foreach ($running as $slot => [$proc, $unit, $started, $log]) {
        $status = proc_get_status($proc);
        if ($status['running']) {
            continue;
        }
        proc_close($proc);
        unset($running[$slot]);
        $freeSlots[] = $slot;
        $seconds = microtime(true) - $started;
        // A filter that matches nothing exits 0 with "No tests executed!" — never count that as passed.
        $ok = $status['exitcode'] === 0 && ! str_contains((string) file_get_contents($log), 'No tests executed!');
        $results[$unit] = $ok ? 'passed' : 'failed';
        $timings[$unit] = round($seconds, 1);
        $done++;
        $failures += $ok ? 0 : 1;
        $saveState();
        $open = array_merge($queue, array_column($running, 1));
        echo TestRunPlan::progressLine($done, $total, $unit, $ok, $seconds, $failures, time() - $start,
            (int) round($plan->remainingSeconds($open, $workers))).($ok ? '' : "\n       Log: {$log}")."\n";
    }
    usleep(200000);
}

file_put_contents($timingsFile, json_encode($timings, JSON_PRETTY_PRINT));

$failed = array_keys(array_filter($results, fn ($s) => $s === 'failed'));
$left = $total - count(array_filter($results, fn ($s) => $s === 'passed')) - count($failed);
if ($stop) {
    echo sprintf("\nUnterbrochen nach %s. %d Einheiten offen. Fortsetzen: php tools/bot-tests.php --resume\n", gmdate('i:s', time() - $start), $left);
    exit(130);
}
echo sprintf("\nFertig in %s: %d bestanden, %d mit Fehlern.\n", gmdate('i:s', time() - $start), $total - count($failed), count($failed));
foreach ($failed as $unit) {
    echo "  FEHL {$unit}\n";
}

exit($failed === [] ? 0 : 1);
