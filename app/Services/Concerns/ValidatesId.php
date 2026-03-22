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
    protected function validateId(mixed $id): void
    {
        if (!is_numeric($id) || (int) $id <= 0) {
            throw new InvalidArgumentException(
                sprintf('Invalid ID: "%s". Must be a positive integer.', $id)
            );
        }
    }
}
