<?php

namespace App\Services\Techtree;

use App\Services\EventService;
use Illuminate\Support\Facades\DB;

/**
 * Journal entries for earmarked knowledge bonus AP ("stille Forschung"), shared by the
 * Cantina figures and the data sweep mission: knowledge.bonus_breakthrough on a
 * level-up, knowledge.bonus_progress otherwise, knowledge.bonus_lapsed when the target
 * refused the bonus and the AP were lost.
 */
class KnowledgeBonusJournal
{
    public const EVENT_PROGRESS = 'knowledge.bonus_progress';

    public const EVENT_BREAKTHROUGH = 'knowledge.bonus_breakthrough';

    public const EVENT_LAPSED = 'knowledge.bonus_lapsed';

    public function __construct(private readonly EventService $eventService) {}

    public function record(int $colonyId, int $tick, string $source, KnowledgeBonusResult $result): void
    {
        $event = match (true) {
            ! $result->accepted => $result->apForfeited > 0 ? self::EVENT_LAPSED : null,
            $result->leveledUp() => self::EVENT_BREAKTHROUGH,
            default => self::EVENT_PROGRESS,
        };
        if ($event === null) {
            return;
        }

        $this->eventService->createEvent([
            'user' => $this->colonyOwner($colonyId),
            'tick' => $tick,
            'event' => $event,
            'area' => 'knowledge',
            'parameters' => json_encode(['colony_id' => $colonyId, 'source' => $source] + $result->toArray()),
        ]);
    }

    /**
     * Parameters of the latest bonus entry from $source in this Sol, or null.
     *
     * @return array<string, mixed>|null
     */
    public function latest(int $colonyId, int $tick, string $source): ?array
    {
        $entries = DB::table('colony_log')
            ->where('user', $this->colonyOwner($colonyId))
            ->where('tick', $tick)
            ->whereIn('event', [self::EVENT_PROGRESS, self::EVENT_BREAKTHROUGH])
            ->orderByDesc('id')
            ->pluck('parameters');

        foreach ($entries as $raw) {
            $params = json_decode((string) $raw, true);
            if (is_array($params) && ($params['source'] ?? null) === $source && (int) ($params['colony_id'] ?? 0) === $colonyId) {
                return $params;
            }
        }

        return null;
    }

    private function colonyOwner(int $colonyId): int
    {
        return (int) DB::table('glx_colonies')->where('id', $colonyId)->value('user_id');
    }
}
