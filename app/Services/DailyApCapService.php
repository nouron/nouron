<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Shared per-Sol AP cap mechanism (T43-G1): "only so many AP per thing and Sol".
 * A cap key names the thing ("knowledge:<id>", "training:<family>",
 * "conversation:<character>"); the counter lives in colony_daily_ap and belongs to
 * the Sol stored beside it, so it resets by itself when the Sol changes.
 *
 * The cap height is passed in by the caller (each rule has its own config); a null cap
 * means the rule is off. The building cap (BuildingApCapService) keeps its own columns
 * on colony_buildings and could migrate onto this service later.
 */
class DailyApCapService
{
    public function __construct(private readonly TickService $tickService) {}

    public function spent(int $colonyId, string $key): int
    {
        $row = DB::table('colony_daily_ap')
            ->where(['colony_id' => $colonyId, 'cap_key' => $key])
            ->first(['tick', 'spent']);

        if ($row === null || (int) $row->tick !== $this->tickService->getTickCount()) {
            return 0;
        }

        return (int) $row->spent;
    }

    /** @return array{cap: int, spent: int, remaining: int}|null null when the rule is off */
    public function state(int $colonyId, string $key, ?int $cap): ?array
    {
        if ($cap === null) {
            return null;
        }
        $spent = $this->spent($colonyId, $key);

        return ['cap' => $cap, 'spent' => $spent, 'remaining' => max(0, $cap - $spent)];
    }

    public function wouldExceed(int $colonyId, string $key, ?int $cap, int $ap = 1): bool
    {
        $state = $this->state($colonyId, $key, $cap);

        return $state !== null && $state['remaining'] < $ap;
    }

    /** Book $ap spent under the key this Sol. */
    public function record(int $colonyId, string $key, int $ap): void
    {
        if ($ap <= 0) {
            return;
        }

        DB::table('colony_daily_ap')->updateOrInsert(
            ['colony_id' => $colonyId, 'cap_key' => $key],
            ['tick' => $this->tickService->getTickCount(), 'spent' => $this->spent($colonyId, $key) + $ap],
        );
    }
}
