<?php

namespace App\Services;

use App\Support\SeededRandom;

/**
 * Places the fixed start pool of hidden finds (T30 Spec §11.3) on the outer-ring map.
 *
 * Own generator ($rngSeed ^ SALT) so the existing map stream (randomizeOuterRingRows) and
 * every paired playtest baseline stay identical — only event_type is added.
 */
class FindPoolService
{
    public const SALT = 0x46494E44;

    /** Largest first: the large find gets the ring-3 preference. */
    private const SIZE_ORDER = ['find_large', 'find_medium', 'find_small', 'find_false'];

    /**
     * @param  list<array<string,mixed>>  $rows  rows from ColonyTileService::randomizeOuterRingRows()
     * @return list<array<string,mixed>> same rows, chosen ones carry event_type find_*
     */
    public function assign(array $rows, int $rngSeed): array
    {
        $types = [];
        foreach (self::SIZE_ORDER as $type) {
            for ($i = 0, $n = (int) (config('game.finds.start_pool')[$type] ?? 0); $i < $n; $i++) {
                $types[] = $type;
            }
        }

        $candidates = [];
        foreach ($rows as $i => $row) {
            $rows[$i]['event_type'] = null;
            if ($row['tile_type'] === 'terrain_empty' && in_array($row['ring'], [2, 3], true)) {
                $candidates[] = $i;
            }
        }

        // XOR instead of + : runs.rng_seed can be up to PHP_INT_MAX, + would overflow to float.
        $rng = SeededRandom::generator($rngSeed ^ self::SALT);
        for ($i = count($candidates) - 1; $i > 0; $i--) {
            $j = $rng->getInt(0, $i);
            [$candidates[$i], $candidates[$j]] = [$candidates[$j], $candidates[$i]];
        }

        // The first (largest) find prefers ring 3: lift the first seeded ring-3 candidate to the front.
        foreach ($candidates as $pos => $rowIndex) {
            if ($rows[$rowIndex]['ring'] === 3) {
                array_splice($candidates, $pos, 1);
                array_unshift($candidates, $rowIndex);
                break;
            }
        }

        foreach (array_slice($candidates, 0, count($types)) as $k => $rowIndex) {
            $rows[$rowIndex]['event_type'] = $types[$k];
        }

        return $rows;
    }
}
