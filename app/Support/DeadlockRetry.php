<?php

namespace App\Support;

use Closure;
use Illuminate\Database\DetectsConcurrencyErrors;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * DB::transaction() with a retry on MySQL deadlocks (SQLSTATE 40001/1213) that
 * logs each retry — Laravel's own `attempts` parameter retries silently (R5b:
 * concurrent registrations / new runs deadlocked with 8 parallel bots). Only
 * SQLSTATE and driver error code are logged, never the message (SQL + bindings).
 *
 * Like Laravel, it only retries a top-level transaction: inside an outer
 * transaction the server has already rolled the whole thing back, so the
 * error is rethrown to the outer level.
 */
final class DeadlockRetry
{
    use DetectsConcurrencyErrors;

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function transaction(Closure $callback, int $attempts): mixed
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction($callback);
            } catch (Throwable $e) {
                if ($attempt >= $attempts || DB::transactionLevel() > 0 || ! (new self)->causedByConcurrencyError($e)) {
                    throw $e;
                }

                // Error codes only: a QueryException message carries the SQL with its
                // bindings (e-mail addresses, password hashes).
                $info = $e instanceof QueryException ? $e->errorInfo : null;
                Log::warning('db deadlock retry', [
                    'attempt' => $attempt,
                    'sqlstate' => (string) ($info[0] ?? $e->getCode()),
                    'driver_code' => isset($info[1]) ? (int) $info[1] : null,
                ]);
            }
        }
    }
}
