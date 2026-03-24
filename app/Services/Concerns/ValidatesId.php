<?php

namespace App\Services\Concerns;

use InvalidArgumentException;

/**
 * Provides ID validation used across all game services.
 * Migrated from Core\Service\AbstractService::_validateId().
 */
trait ValidatesId
{
    /**
     * Throw if $id is not a positive integer.
     *
     * @throws InvalidArgumentException
     */
    /**
     * Throw if $id is not a non-negative integer (allows 0 for legacy user IDs like Homer).
     *
     * @throws InvalidArgumentException
     */
    protected function validateId(mixed $id): void
    {
        if (!is_numeric($id) || (int) $id < 0) {
            throw new InvalidArgumentException(
                sprintf('Invalid ID: "%s". Must be a non-negative integer.', $id)
            );
        }
    }
}
