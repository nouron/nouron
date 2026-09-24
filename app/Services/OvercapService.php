<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * OvercapService — over-capacity consequences (GDD §6 "Überkapazität —
 * Konsequenzen", A14, model "Unterbesetzung").
 *
 * Colonists are homeless while more of them are present than the cap houses
 * (ResourcesService::colonistStatus()). Per Sol with homeless colonists the
 * streak in glx_colonies.overcap_streak grows (trust penalty from the first Sol,
 * TrustService::overcapPenalty()). Once the streak has reached
 * overcap.departure_after_sols, all homeless colonists leave at the next Sol:
 * glx_colonies.overcap_departed grows by their number (unfilled workplaces —
 * lower staffing share, lower food need, build gate stays closed), the streak
 * ends, trust event colonists_left fires once. Buildings keep their levels.
 * Departed colonists return automatically as soon as housing is free again;
 * lost workplaces take unfilled ones with them (settleLostWorkplaces()).
 *
 * dismiss() is the player shortcut ("Wegschicken"): same result, immediately,
 * with the milder trust event colonists_dismissed.
 */
class OvercapService
{
    public function __construct(
        private readonly ResourcesService $resourcesService,
        private readonly EventService $eventService,
        private readonly TrustService $trustService,
    ) {}

    /**
     * GameTick step before the supply cap is recalculated: workplaces lost this Sol
     * (a building or research lost a level) take unfilled workplaces with them —
     * persist the smaller departed count silently. Nobody came back, so this is not
     * a return; the stored cap is still last Sol's, so any drop of the live departed
     * count here can only come from lost workplaces. advanceStreaks() then only
     * sees drops caused by a larger cap — real returns.
     *
     * @return int Number of colonies whose departed count shrank
     */
    public function settleLostWorkplaces(): int
    {
        $colonies = DB::table('glx_colonies')->where('overcap_departed', '>', 0)->get(['id', 'overcap_departed']);
        $settled = 0;

        foreach ($colonies as $colony) {
            $departed = $this->resourcesService->colonistStatus((int) $colony->id)['departed'];
            if ($departed < (int) $colony->overcap_departed) {
                DB::table('glx_colonies')->where('id', $colony->id)->update(['overcap_departed' => $departed]);
                $settled++;
            }
        }

        return $settled;
    }

    /**
     * GameTick step: persist returns, then advance the streak of every colony
     * with homeless colonists by 1 (or, once the deadline is reached, let them
     * depart) and reset every other colony to 0.
     *
     * Must run after the supply cap was recalculated for this Sol (and after every
     * step that can level buildings down) and before the trust calculation, so a
     * departure ends the streak penalty in the same Sol and colonists_left (fired
     * for $tick) counts in this Sol's trust.
     *
     * @return int Number of colonies with homeless colonists after this step
     */
    public function advanceStreaks(int $tick): int
    {
        $deadline = max(1, (int) config('game.overcap.departure_after_sols', 3));
        $colonies = DB::table('glx_colonies')->get(['id', 'user_id', 'overcap_streak', 'overcap_departed']);
        $overCount = 0;

        foreach ($colonies as $colony) {
            $colonyId = (int) $colony->id;
            $userId = (int) ($colony->user_id ?? 0);
            $storedStreak = (int) $colony->overcap_streak;
            $storedDeparted = (int) $colony->overcap_departed;
            $status = $this->resourcesService->colonistStatus($colonyId);

            // Return: colonistStatus() already clamps the departed count to the
            // free room — persist it and log how many came back. Workplaces lost
            // this Sol were already settled silently (settleLostWorkplaces()), so
            // what is left here is housing that became free.
            $departed = $status['departed'];
            if ($departed < $storedDeparted && $userId > 0) {
                $this->log($userId, $tick, 'colony.colonists_returned', [
                    'colony_id' => $colonyId,
                    'count' => $storedDeparted - $departed,
                ]);
            }

            $homeless = $status['homeless'];
            $streak = 0;

            if ($homeless > 0 && $storedStreak >= $deadline) {
                $departed += $homeless;
                $this->trustService->fireEvent($colonyId, 'colonists_left', $tick);
                if ($userId > 0) {
                    $this->log($userId, $tick, 'colony.colonists_left', [
                        'colony_id' => $colonyId,
                        'count' => $homeless,
                        'departed' => $departed,
                    ]);
                }
            } elseif ($homeless > 0) {
                $streak = $storedStreak + 1;
                $overCount++;
                if ($streak === 1 && $userId > 0) {
                    $this->log($userId, $tick, 'colony.overcap_started', [
                        'colony_id' => $colonyId,
                        'homeless' => $homeless,
                        'sols' => $deadline,
                    ]);
                }
            }

            if ($streak !== $storedStreak || $departed !== $storedDeparted) {
                DB::table('glx_colonies')->where('id', $colonyId)
                    ->update(['overcap_streak' => $streak, 'overcap_departed' => $departed]);
            }
        }

        return $overCount;
    }

    /**
     * "Wegschicken" (GDD §6): all currently homeless colonists leave right away —
     * same state as a departure, but the streak ends now and the milder trust
     * event colonists_dismissed fires (for $trustTick, the Sol it takes effect).
     * Charges no AP itself — the caller locks overcap.dismiss_ap_cost.
     *
     * @return int Number of dismissed colonists (0 = nobody was homeless, nothing changed)
     */
    public function dismiss(int $colonyId, int $userId, int $trustTick): int
    {
        $status = $this->resourcesService->colonistStatus($colonyId);
        $homeless = $status['homeless'];
        if ($homeless <= 0) {
            return 0;
        }

        $departed = $status['departed'] + $homeless;
        DB::table('glx_colonies')->where('id', $colonyId)
            ->update(['overcap_streak' => 0, 'overcap_departed' => $departed]);

        $this->trustService->fireEvent($colonyId, 'colonists_dismissed', $trustTick);
        if ($userId > 0) {
            $this->log($userId, $trustTick, 'colony.colonists_dismissed', [
                'colony_id' => $colonyId,
                'count' => $homeless,
                'departed' => $departed,
            ]);
        }

        return $homeless;
    }

    /**
     * Display status for the resource bar, Sol report and dismiss response.
     *
     * - over:                 colonists are homeless right now
     * - homeless:             homeless colonists (0 when everyone is housed)
     * - streak:               consecutive Sols with homeless colonists as of the last tick
     * - sols_until_departure: Sol changes until the homeless leave (≥ 1; only meaningful while over)
     * - trust_penalty:        current over-capacity trust summand (non-positive, from the streak)
     * - hunger_penalty:       current hunger trust summand (non-positive)
     * - departed:             colonists who left = unfilled workplaces
     * - staffing_pct:         staffing share in percent (100 while nobody departed)
     * - dismiss_ap_cost:      AP cost of "Wegschicken"
     *
     * @return array{over: bool, homeless: int, streak: int, sols_until_departure: int, trust_penalty: int, hunger_penalty: int, departed: int, staffing_pct: int, dismiss_ap_cost: int}
     */
    public function status(int $colonyId): array
    {
        $deadline = max(1, (int) config('game.overcap.departure_after_sols', 3));
        $colonists = $this->resourcesService->colonistStatus($colonyId);
        $homeless = $colonists['homeless'];
        $streak = (int) DB::table('glx_colonies')->where('id', $colonyId)->value('overcap_streak');

        return [
            'over' => $homeless > 0,
            'homeless' => $homeless,
            'streak' => $streak,
            'sols_until_departure' => max(1, $deadline + 1 - $streak),
            'trust_penalty' => $this->trustService->overcapPenaltyForStreak($streak),
            'hunger_penalty' => $this->trustService->hungerPenalty($colonyId),
            'departed' => $colonists['departed'],
            'staffing_pct' => (int) round($colonists['staffing'] * 100),
            'dismiss_ap_cost' => (int) config('game.overcap.dismiss_ap_cost', 8),
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
