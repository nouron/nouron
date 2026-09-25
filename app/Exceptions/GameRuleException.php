<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A game rule refused the action. Carries a stable machine code next to the
 * translated player text, so controllers can answer with the AJAX error
 * contract (docs/frontend-conventions.md §2): `error` = code, `message` = text.
 * Extends RuntimeException so existing catch blocks keep handling it.
 */
class GameRuleException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
