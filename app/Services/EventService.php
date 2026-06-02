<?php

namespace App\Services;

use App\Models\InnnEvent;
use App\Services\Concerns\ValidatesId;
use Illuminate\Support\Collection;

/**
 * EventService — Laravel port of INNN\Service\EventService.
 *
 * Events are game-generated notifications associated with a user and a game
 * tick. They are created by game services (e.g. tick processing) and read by
 * the INNN module to display the events tab.
 */
class EventService
{
    use ValidatesId;

    /**
     * Fetch a single event by primary key.
     *
     * @throws \InvalidArgumentException for non-numeric or negative $id
     */
    public function getEvent(mixed $id): InnnEvent|false
    {
        $this->validateId($id);
        return InnnEvent::find((int) $id) ?? false;
    }

    /**
     * Fetch all events for a given user ID.
     *
     * @throws \InvalidArgumentException for invalid $userId
     */
    public function getEvents(mixed $userId): Collection
    {
        $this->validateId($userId);
        return InnnEvent::where('user', (int) $userId)->get();
    }

    /**
     * Fire the one-time Nexus Briefing event for a newly registered player.
     *
     * Guard ensures the event is never duplicated — safe to call multiple times.
     */
    public function createNexusBriefing(int $userId, int $tick, int $colonyId): void
    {
        $alreadyFired = InnnEvent::where('user', $userId)
            ->where('event', 'onboarding.nexus_briefing')
            ->exists();

        if ($alreadyFired) {
            return;
        }

        $this->createEvent([
            'user'       => $userId,
            'tick'       => $tick,
            'event'      => 'onboarding.nexus_briefing',
            'area'       => 'nexus',
            'parameters' => json_encode(['colony_id' => $colonyId]),
        ]);
    }

    /**
     * Insert a new event and return its new ID.
     *
     * Expected keys in $data: user, tick, event, area, parameters.
     *
     * @return int the new event ID
     */
    public function createEvent(array $data): int
    {
        $event = InnnEvent::create([
            'user'       => (int) $data['user'],
            'tick'       => (int) ($data['tick'] ?? 0),
            'event'      => $data['event'],
            'area'       => $data['area'] ?? '',
            'parameters' => $data['parameters'] ?? '',
        ]);

        return $event->id;
    }
}
