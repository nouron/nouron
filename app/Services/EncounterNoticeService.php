<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Surfaces GDD §9 danger events (Sturm/Instabilität/Seuche) prominently in the
 * UI, above the normal onboarding hint — Owner-Playtest-Fund (2026-08-31):
 * these previously only appeared in the Protokoll log, easy to miss for a
 * whole Sol if the player doesn't check it.
 *
 * Deliberately stateless: a notice is "active" exactly while its colony_log
 * entry's tick equals the colony's current tick. No dismiss button, no extra
 * DB column — it clears itself the moment game:tick advances to the next Sol
 * (Owner decision 2026-08-31), the same way the Protokoll's own Sol grouping
 * already works.
 */
class EncounterNoticeService
{
    private const EVENT_KEYS = [
        'encounter.storm_warning',
        'encounter.storm_abgewehrt',
        'encounter.storm_beschaedigt',
        'encounter.storm_kritisch',
        'encounter.instability_triggered',
        'encounter.plague_triggered',
    ];

    /**
     * @return list<array{event: string, text: string}>
     */
    public function activeNotices(int $colonyId, int $currentTick): array
    {
        return DB::table('colony_log')
            ->where('area', 'encounter')
            ->where('tick', $currentTick)
            ->whereIn('event', self::EVENT_KEYS)
            ->orderBy('id')
            ->get()
            ->filter(fn ($row) => $this->matchesColony($row, $colonyId))
            ->map(fn ($row) => [
                'event' => $row->event,
                'text' => $this->textFor($row->event, json_decode($row->parameters, true) ?? []),
            ])
            ->values()
            ->all();
    }

    private function matchesColony(object $row, int $colonyId): bool
    {
        $params = json_decode($row->parameters, true);

        return is_array($params) && (int) ($params['colony_id'] ?? -1) === $colonyId;
    }

    private function textFor(string $event, array $params): string
    {
        return match ($event) {
            'encounter.storm_warning' => __('colony.encounter_notice_storm_warning', ['building' => $this->buildingLabel($params)]),
            'encounter.storm_abgewehrt' => __('colony.encounter_notice_storm_abgewehrt', ['building' => $this->buildingLabel($params)]),
            'encounter.storm_beschaedigt' => __('colony.encounter_notice_storm_beschaedigt', ['building' => $this->buildingLabel($params)]),
            'encounter.storm_kritisch' => __('colony.encounter_notice_storm_kritisch', ['building' => $this->buildingLabel($params)]),
            'encounter.instability_triggered' => __('comm_log.desc.instability_triggered', [
                'sols' => (int) config('game.encounter.instability.outage_sols', 3),
            ]),
            'encounter.plague_triggered' => __('comm_log.desc.plague_triggered'),
            default => '',
        };
    }

    private function buildingLabel(array $params): string
    {
        $buildingId = $params['building_id'] ?? null;
        if ($buildingId === null) {
            return '?';
        }

        $nameKey = DB::table('buildings')->where('id', $buildingId)->value('name');
        if (! $nameKey) {
            return '?';
        }

        $translated = __('techtree.'.$nameKey);

        return $translated !== 'techtree.'.$nameKey ? $translated : $nameKey;
    }
}
