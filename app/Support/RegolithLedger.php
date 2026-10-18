<?php

namespace App\Support;

/**
 * Regolith booking journal (budget spec E.1 M1): every Regolith change is
 * reported here with a source or sink tag — positive delta = inflow
 * (harvester_base, geology, import, ...), negative = outflow (build_place,
 * repair, ...).
 *
 * A pure observer for the PlaytestBot report: off by default (record() is a
 * no-op), switched on by BotSession::boot() and drained after each bot request.
 * It never reads or changes game state, RNG or control flow — the game behaves
 * identically with the recorder on or off.
 */
final class RegolithLedger
{
    /** Tag used when a booking site passes none — the bot invariant test fails on it. */
    public const UNTAGGED = 'untagged';

    public const RES_REGOLITH = 3;

    /** @var list<array{colony_id: int, tag: string, delta: int}>|null null = recorder off */
    private static ?array $entries = null;

    public static function start(): void
    {
        self::$entries = [];
    }

    public static function stop(): void
    {
        self::$entries = null;
    }

    public static function active(): bool
    {
        return self::$entries !== null;
    }

    public static function record(int $colonyId, ?string $tag, int $delta): void
    {
        if (self::$entries === null || $delta === 0) {
            return;
        }

        self::$entries[] = ['colony_id' => $colonyId, 'tag' => $tag ?? self::UNTAGGED, 'delta' => $delta];
    }

    /**
     * Removes and returns the colony's entries in booking order; entries of other
     * colonies are discarded (one bot per process, nothing else consumes them).
     *
     * @return list<array{tag: string, delta: int}>
     */
    public static function drain(int $colonyId): array
    {
        if (self::$entries === null) {
            return [];
        }

        $mine = [];
        foreach (self::$entries as $entry) {
            if ($entry['colony_id'] === $colonyId) {
                $mine[] = ['tag' => $entry['tag'], 'delta' => $entry['delta']];
            }
        }
        self::$entries = [];

        return $mine;
    }
}
