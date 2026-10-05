<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Base value for every seeded game roll: the run's rng_seed (runs.rng_seed),
 * combined by the caller with the Sol/tick and a fixed per-domain salt only.
 *
 * Never put colony, run, user or other auto-increment row ids into a roll seed
 * (R5b, 2026-10-04): in a shared database (production, parallel playtest bots)
 * those ids depend on which player started first, so the same rng_seed would
 * play out differently. Where a roll needs to tell several rows of one colony
 * apart, use an id-free, stable column of the row (creation Sol, slot) — or
 * rowOrdinal() for tables whose rows are never deleted during a run.
 */
final class RunSeed
{
    /**
     * Seeds are reduced to 2^47 so the callers' additive salts (domain salt +
     * tick * multiplier) can never overflow PHP_INT_MAX into a float; small seeds
     * (the playtest tool's --seeds) are unchanged.
     */
    private const MODULUS = 0x7FFFFFFFFFFF;

    /** rng_seed of the colony's active run (else its latest run), 0 without any run. */
    public static function forColony(int $colonyId): int
    {
        $seed = DB::table('runs')->where('colony_id', $colonyId)->where('status', 'active')
            ->orderByDesc('id')->value('rng_seed')
            ?? DB::table('runs')->where('colony_id', $colonyId)->orderByDesc('id')->value('rng_seed');

        return self::reduce((int) ($seed ?? 0));
    }

    /** A run's rng_seed, reduced like forColony() (for callers that already hold the run). */
    public static function reduce(int $rngSeed): int
    {
        return $rngSeed % self::MODULUS;
    }

    /**
     * 1-based position of $rowId among the colony's rows of $table (by id) — a
     * per-colony stand-in for the global id. Only stable for tables whose rows are
     * never deleted during a run (colony_hangar_missions); where rows expire or get
     * deleted, seed from an id-free column of the row instead (e.g. bar_offers.roll_key,
     * bar_concerns.created_tick).
     */
    public static function rowOrdinal(string $table, int $colonyId, int $rowId): int
    {
        return DB::table($table)->where('colony_id', $colonyId)->where('id', '<=', $rowId)->count();
    }
}
