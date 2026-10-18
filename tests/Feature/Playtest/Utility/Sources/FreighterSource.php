<?php

namespace Tests\Feature\Playtest\Utility\Sources;

use App\Services\ColonyTileService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\Utility\BotContext;
use Tests\Feature\Playtest\Utility\Candidate;
use Tests\Feature\Playtest\Utility\CandidateSource;

/**
 * S3 (T30 §14 U5 / §14.4 Frachter-Ausnahme): the freighter for a scanned large
 * find waiting on needs_freighter, before the third advisor. From 3 advisors on
 * the legacy request_ship rule (same band) keeps buying ships.
 */
final class FreighterSource implements CandidateSource
{
    public function __construct(private readonly int $band) {}

    /**
     * Own name (review S3 M3): a failed freighter request blocks only this source
     * for the Sol, not the legacy request_ship rule. The action is still logged
     * as request_ship (report categories).
     */
    public function name(): string
    {
        return 'request_freighter';
    }

    public function band(): int
    {
        return $this->band;
    }

    public function candidates(BotSession $b, BotContext $ctx): array
    {
        $freighterId = (int) config('ships.freighter.id');
        $cost = (int) config('ships.freighter.nexus_cost', 0);
        if ($ctx->advisors >= 3 || BotStrategy::hangarLevel($b) < 2 || ! BotStrategy::hasFreeBayFor($b, 2)
            || DB::table('colony_ships')->where('colony_id', $b->colonyId)->where('ship_id', $freighterId)->exists()
            || $ctx->credits - $cost < self::nextHireCost($b)
            || self::creditReserveGuardBlocks($ctx)) {
            return [];
        }

        $service = app(ColonyTileService::class);
        $types = config('game.finds.types');
        $value = 0.0;
        $tiles = DB::table('colony_tiles')->where('colony_id', $b->colonyId)->where('is_deep_scanned', 1)
            ->whereIn('event_type', ColonyTileService::FIND_TYPES)->orderBy('ring')->orderBy('q')->orderBy('r')->get();
        foreach ($tiles as $tile) {
            if ($service->findAction($b->colonyId, $tile)['reason'] === 'needs_freighter') {
                $value += (int) $types[$tile->event_type]['rg'];
            }
        }
        if ($value <= 0.0) {
            return [];
        }

        $terms = ['finds' => $value, 'cost_credits' => -$cost * $ctx->price('credits')];

        return [new Candidate(
            name: 'request_freighter',
            key: 'ship:freighter',
            band: $this->band,
            net: (float) array_sum($terms),
            apCost: 1,
            do: fn (BotSession $s): array => $s->act('request_ship', 'POST', '/colony/hangar/request', ['ship_id' => $freighterId]),
            terms: $terms,
        )];
    }

    /** Same guard as legacy request_ship (BotStrategy::creditReserveGuardBlocks()): below the reserve buffer, buy nothing (review S3 M3). */
    private static function creditReserveGuardBlocks(BotContext $ctx): bool
    {
        if (! isset($ctx->objectives['task_credit_reserve'])) {
            return false;
        }

        return $ctx->credits < (int) round((int) config('game.run.tasks.task_credit_reserve.threshold', 4000) * (float) $ctx->weights['credit_reserve_factor']);
    }

    /** Credits of the next not yet hired advisor in BotStrategy::HIRE_ORDER, 0 if none is left. */
    private static function nextHireCost(BotSession $b): int
    {
        $hired = DB::table('advisors')->where('colony_id', $b->colonyId)->orderBy('id')->pluck('personell_id')->map(fn ($id): int => (int) $id)->all();
        foreach (BotStrategy::HIRE_ORDER as $personellId) {
            if (! in_array($personellId, $hired, true)) {
                return (int) (collect(config('advisors'))->firstWhere('id', $personellId)['credits'] ?? 0);
            }
        }

        return 0;
    }
}
