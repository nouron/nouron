<?php

namespace App\Console\Concerns;

/**
 * Development commands that wipe or rewrite game state must never touch the
 * production database (R3, closed beta). Call at the top of handle():
 *
 *   if ($this->refusesInProduction()) {
 *       return self::FAILURE;
 *   }
 */
trait RefusesInProduction
{
    protected function refusesInProduction(): bool
    {
        if (! $this->laravel->isProduction()) {
            return false;
        }

        $this->error("{$this->getName()} is a development command and refuses to run in production.");

        return true;
    }
}
