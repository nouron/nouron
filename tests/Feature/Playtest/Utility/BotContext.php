<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use App\Services\AdvisorService;
use App\Services\ResourcesService;
use App\Services\TrustService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;

/** State, prices and weights shared by all sources for one decision (spec 3.1). */
final class BotContext
{
    /**
     * @param  array<string, mixed>  $weights
     * @param  array<string, array{current:int, target:int}>  $objectives  open Phase-2 objectives
     * @param  array<string, float>  $prices  Rg-equivalents per unit
     */
    public function __construct(
        public readonly int $sol,
        public readonly array $weights = [],
        public readonly int $phase = 1,
        public readonly int $tickLimit = 100,
        public readonly string $opening = 'auto',
        public readonly ?int $openingBuildingId = null,
        public readonly int $regolith = 0,
        public readonly int $credits = 0,
        public readonly int $organics = 0,
        public readonly int $trust = 0,
        public readonly int $apAvailable = 0,
        public readonly int $apInflow = 0,
        public readonly float $incomeRg = 0.0,
        public readonly float $lambdaAp = 0.5,
        public readonly int $advisors = 0,
        public readonly int $nonCcLevel2 = 0,
        public readonly array $objectives = [],
        public readonly array $prices = [],
    ) {}

    public static function capture(BotSession $b, BotProfile $profile): self
    {
        $w = $profile->weights();
        $colonyId = $b->colonyId;
        $advisorService = app(AdvisorService::class);
        $credits = BotStrategy::credits($b);
        $organics = BotStrategy::organics($b);
        $trust = app(TrustService::class)->getTrust($colonyId);
        [$lo, $hi] = $w['scarcity_clamp'];

        $objectives = DB::table('run_objectives')->where('run_id', $b->runId)->whereNull('completed_at')
            ->orderBy('task_key')->get(['task_key', 'current_value', 'target_value'])
            ->mapWithKeys(fn ($o) => [$o->task_key => ['current' => (int) $o->current_value, 'target' => (int) $o->target_value]])
            ->all();

        $upkeepTable = config('game.advisor.upkeep', []);
        $upkeep = DB::table('advisors')->where('colony_id', $colonyId)->orderBy('id')->pluck('rank')
            ->sum(fn ($rank) => (int) ($upkeepTable[(int) $rank] ?? 0));
        $creditDemand = $upkeep * 10 + (isset($objectives['task_credit_reserve'])
            ? (int) round((int) config('game.run.tasks.task_credit_reserve.threshold', 4000) * $w['credit_reserve_factor'])
            : 0);
        $trustDanger = $trust - (int) config('game.run.trust_fail_threshold', -20) <= (int) $w['trust_danger_margin'];

        $prices = [
            'credits' => (float) $w['price']['credits'] * Valuation::scarcity($creditDemand, $credits, $lo, $hi),
            'organics' => (float) $w['price']['organics'] * Valuation::scarcity(app(ResourcesService::class)->foodNeed($colonyId) * 3, $organics, $lo, $hi),
            'compounds' => (float) $w['price']['compounds'],
            'trust' => (float) $w['price']['trust'] * ($trustDanger ? (float) $w['trust_danger_factor'] : 1.0),
        ];

        return new self(
            sol: $b->sol,
            weights: $w,
            phase: BotStrategy::runPhase($b),
            tickLimit: (int) config('game.run.tick_limit', 100),
            opening: $profile->opening,
            openingBuildingId: $profile->opening === 'auto' ? null : BotStrategy::pathBuildingOrder($profile->opening)[0],
            regolith: BotStrategy::regolith($b),
            credits: $credits,
            organics: $organics,
            trust: $trust,
            apAvailable: $advisorService->getAvailableActionPoints($colonyId),
            apInflow: (int) $advisorService->getApBreakdown($colonyId)['total'],
            incomeRg: self::income($b, (float) $w['income_floor_rg']),
            lambdaAp: $b->lambdaAp ?? (float) $w['lambda_ap_start'],
            advisors: DB::table('advisors')->where('colony_id', $colonyId)->count(),
            nonCcLevel2: DB::table('colony_buildings')->where('colony_id', $colonyId)
                ->where('building_id', '!=', BuildingId::CommandCenter->value)->where('level', '>=', 2)->count(),
            objectives: $objectives,
            prices: $prices,
        );
    }

    /** Mean Regolith gain of the last three ok sol_next entries, at least $floor (spec 2.6). */
    private static function income(BotSession $b, float $floor): float
    {
        $gains = [];
        foreach (array_reverse($b->log) as $entry) {
            if ($entry['rule'] === 'sol_next' && $entry['ok']) {
                $gains[] = max(0, $entry['regolith_after'] - $entry['regolith_before']);
                if (count($gains) === 3) {
                    break;
                }
            }
        }

        return max($floor, $gains === [] ? 0.0 : array_sum($gains) / count($gains));
    }

    public function horizon(): int
    {
        return Valuation::horizon($this->sol, $this->tickLimit, (int) $this->weights['horizon_max']);
    }

    public function stream(float $perSol, int $latency): float
    {
        return Valuation::stream($perSol, $latency, (float) $this->weights['gamma'], $this->horizon());
    }

    public function price(string $name): float
    {
        return $this->prices[$name] ?? 0.0;
    }

    public function phase1Urgency(): float
    {
        return Valuation::urgency($this->sol, (int) $this->weights['phase1_urgency_from_sol'], (float) $this->weights['phase1_urgency_step']);
    }
}
