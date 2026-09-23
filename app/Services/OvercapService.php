<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * OvercapService — over-capacity consequences (GDD §6 "Überkapazität —
 * Konsequenzen", A14).
 *
 * A colony is over capacity while its used supply exceeds its cap
 * (ResourcesService::getFreeSupply() < 0). Stage 1 (this class): track the
 * consecutive over-cap Sols in glx_colonies.overcap_streak, log the two
 * transitions (entering over-capacity, end of the grace period) to the colony
 * log, and expose a display status for the resource bar. The trust penalty
 * itself is a summand in TrustService::overcapPenalty().
 */
class OvercapService
{
    public function __construct(
        private readonly ResourcesService $resourcesService,
        private readonly EventService $eventService,
        private readonly TrustService $trustService,
    ) {}

    /**
     * GameTick step: advance the streak of every over-cap colony by 1 and reset
     * every other colony to 0. Must run after the supply cap was recalculated for
     * this Sol (and after every step that can level buildings down) and before
     * the trust calculation.
     *
     * @return int Number of colonies over capacity this Sol
     */
    public function advanceStreaks(int $tick): int
    {
        $grace = (int) config('game.overcap.grace_sols', 5);
        $colonies = DB::table('glx_colonies')->get(['id', 'user_id', 'overcap_streak']);
        $overCount = 0;

        foreach ($colonies as $colony) {
            $colonyId = (int) $colony->id;
            $deficit = -$this->resourcesService->getFreeSupply($colonyId);

            if ($deficit <= 0) {
                if ((int) $colony->overcap_streak !== 0) {
                    DB::table('glx_colonies')->where('id', $colonyId)->update(['overcap_streak' => 0]);
                }

                continue;
            }

            $overCount++;
            $streak = (int) $colony->overcap_streak + 1;
            DB::table('glx_colonies')->where('id', $colonyId)->update(['overcap_streak' => $streak]);

            $userId = (int) ($colony->user_id ?? 0);
            if ($userId <= 0) {
                continue;
            }

            if ($streak === 1) {
                $this->log($userId, $tick, 'colony.overcap_started', [
                    'colony_id' => $colonyId,
                    'deficit' => $deficit,
                    'grace_sols' => $grace,
                ]);
            } elseif ($streak === $grace + 1) {
                $this->log($userId, $tick, 'colony.overcap_trust_malus', [
                    'colony_id' => $colonyId,
                    'deficit' => $deficit,
                    'malus' => -$this->trustService->overcapPenaltyForStreak($streak),
                ]);
            }
        }

        return $overCount;
    }

    /**
     * Display status for the resource bar.
     *
     * - over:              colony is over capacity right now (live free supply < 0)
     * - deficit:           colonists above capacity (0 when within cap)
     * - streak:            consecutive over-cap Sols as of the last tick
     * - grace_sols_left:   Sol changes until the trust penalty starts (0 once it applies)
     * - trust_penalty:     current over-capacity trust summand (non-positive, from the streak)
     * - hunger_penalty:    current hunger trust summand (non-positive)
     *
     * @return array{over: bool, deficit: int, streak: int, grace_sols_left: int, trust_penalty: int, hunger_penalty: int}
     */
    public function status(int $colonyId): array
    {
        $grace = (int) config('game.overcap.grace_sols', 5);
        $deficit = max(0, -$this->resourcesService->getFreeSupply($colonyId));
        $streak = (int) DB::table('glx_colonies')->where('id', $colonyId)->value('overcap_streak');
        $penalty = $this->trustService->overcapPenaltyForStreak($streak);

        return [
            'over' => $deficit > 0,
            'deficit' => $deficit,
            'streak' => $streak,
            'grace_sols_left' => $penalty < 0 ? 0 : max(1, $grace + 1 - $streak),
            'trust_penalty' => $penalty,
            'hunger_penalty' => $this->trustService->hungerPenalty($colonyId),
        ];
    }

    /** @param array<string, int> $params */
    private function log(int $userId, int $tick, string $event, array $params): void
    {
        $this->eventService->createEvent([
            'user' => $userId,
            'tick' => $tick,
            'event' => $event,
            'area' => 'colony',
            'parameters' => json_encode($params),
        ]);
    }
}
