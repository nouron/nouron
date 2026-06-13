<?php

namespace App\Services;

use App\Models\ColonyLog;
use App\Services\Concerns\ValidatesId;
use Illuminate\Support\Collection;

class EventService
{
    use ValidatesId;

    private const NEXUS_EVENT_KEYS = [
        'run.nexus_warning_sol30',
        'run.nexus_warning_sol50',
        'run.nexus_sanction_sol65',
        'run.nexus_countdown_sol80',
        'run.run_completed',
        'run.run_failed_trust',
        'run.run_failed_nexus_debt',
        'run.run_failed_time',
        'run.phase1_complete',
    ];

    public function getEvent(mixed $id): ColonyLog|false
    {
        $this->validateId($id);

        return ColonyLog::find((int) $id) ?? false;
    }

    public function getEvents(mixed $userId): Collection
    {
        $this->validateId($userId);

        return ColonyLog::where('user', (int) $userId)->get();
    }

    public function createNexusBriefing(int $userId, int $tick, int $colonyId): void
    {
        $alreadyFired = ColonyLog::where('user', $userId)
            ->where('event', 'onboarding.nexus_briefing')
            ->exists();

        if ($alreadyFired) {
            return;
        }

        $this->createEvent([
            'user' => $userId,
            'tick' => $tick,
            'event' => 'onboarding.nexus_briefing',
            'area' => 'nexus',
            'parameters' => json_encode(['colony_id' => $colonyId]),
        ]);
    }

    public function createEvent(array $data): int
    {
        $area = $data['area'] ?? '';
        $event = $data['event'];

        $isNexus = $area === 'nexus' || in_array($event, self::NEXUS_EVENT_KEYS, true);

        $entry = ColonyLog::create([
            'user' => (int) $data['user'],
            'tick' => (int) ($data['tick'] ?? 0),
            'event' => $event,
            'area' => $area,
            'parameters' => $data['parameters'] ?? '',
            'is_read' => ! $isNexus,
            'created_at' => now(),
        ]);

        return $entry->id;
    }

    public function countUnreadNexus(int $userId): int
    {
        return ColonyLog::where('user', $userId)
            ->where('is_read', false)
            ->count();
    }

    public function markNexusRead(int $userId): void
    {
        ColonyLog::where('user', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public static function isNexusEvent(string $area, string $event): bool
    {
        return $area === 'nexus' || in_array($event, self::NEXUS_EVENT_KEYS, true);
    }
}
