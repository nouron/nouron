<?php

namespace App\Services;

use App\Console\Commands\GameTick;
use App\Enums\BuildingId;
use Illuminate\Support\Facades\DB;

/**
 * Read-only per-Sol balance of the colony resources Regolith, Organika and Werkstoffe,
 * for the resource-bar popups. Mirrors exactly what game:tick books next
 * (GameTick::generateResources() + processFoodConsumption()): trust production
 * multiplier x staffing share, the Harvester per tile (geology Kenntnis once per colony),
 * the Agrardom curve plus the agronomy Kenntnis, and the Organika the colonists eat.
 * Parity with the tick is pinned by ResourceFlowServiceTest.
 *
 * Werkstoffe have no recurring source in the tick (they only arrive through imports/
 * trade), so their per-Sol net is always 0.
 */
class ResourceFlowService
{
    public function __construct(
        private readonly TrustService $trustService,
        private readonly ResourcesService $resourcesService,
        private readonly TickService $tickService,
    ) {}

    /**
     * @return array{
     *   regolith: array{sources: array{harvester: int, geology: int}, net: int},
     *   organika: array{sources: array{agrardom: int, agronomy: int}, consumption: int, net: int},
     *   werkstoffe: array{net: int}
     * }
     */
    public function perSol(int $colonyId): array
    {
        $tick = $this->tickService->getTickCount();
        $multiplier = $this->trustService->getProductionMultiplier($this->trustService->getTrust($colonyId))
            * $this->resourcesService->staffingShare($colonyId);

        [$harvester, $geology] = $this->harvesterYield($colonyId, $tick, $multiplier);

        $agrardom = $this->agrardomYield($colonyId, $tick, $multiplier);
        $agronomy = $this->agronomyBonus($colonyId, $multiplier);

        // Food is eaten after production: the Sol's harvest is on hand (GameTick step 8a).
        $stock = (int) DB::table('colony_resources')
            ->where('colony_id', $colonyId)->where('resource_id', 5)->value('amount');
        $consumption = min($stock + $agrardom + $agronomy, $this->resourcesService->foodNeed($colonyId));

        return [
            'regolith' => [
                'sources' => ['harvester' => $harvester, 'geology' => $geology],
                'net' => $harvester + $geology,
            ],
            'organika' => [
                'sources' => ['agrardom' => $agrardom, 'agronomy' => $agronomy],
                'consumption' => $consumption,
                'net' => $agrardom + $agronomy - $consumption,
            ],
            'werkstoffe' => ['net' => 0],
            'credits' => $this->creditsFlow($colonyId),
        ];
    }

    /**
     * Credits per Sol, mirroring GameTick::generatePassiveCredits() (Nexus subsidy +
     * Uplink relay, only with a running CC) and deductAdvisorUpkeep(). The upkeep
     * clamp at an empty purse is ignored here — the shortfall goes to the Nexus debt.
     *
     * @return array{sources: array{subsidy: int, relay: int}, upkeep: int, net: int}
     */
    public function creditsFlow(int $colonyId): array
    {
        $level = fn (BuildingId $b) => (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)->where('building_id', $b->value)->value('level');

        $ccRunning = $level(BuildingId::CommandCenter) > 0;
        $subsidy = $ccRunning ? (int) config('game.credits.nexus_subsidy', 30) : 0;
        $relay = $ccRunning ? $level(BuildingId::UplinkStation) * (int) config('game.credits.relay_bonus_per_uplink_level', 20) : 0;

        $perRank = config('game.advisor.upkeep', [1 => 10, 2 => 30, 3 => 80]);
        $upkeep = (int) DB::table('advisors')->where('colony_id', $colonyId)->pluck('rank')
            ->sum(fn ($rank) => (int) ($perRank[$rank] ?? 10));

        return [
            'sources' => ['subsidy' => $subsidy, 'relay' => $relay],
            'upkeep' => $upkeep,
            'net' => $subsidy + $relay - $upkeep,
        ];
    }

    /**
     * Harvester yield per Sol, split like the Regolith ledger: own yield vs the geology share.
     *
     * @return array{0: int, 1: int}
     */
    private function harvesterYield(int $colonyId, int $tick, float $multiplier): array
    {
        $resourceMaxCfg = config('game.harvester.resource_max', []);
        $geologyLevel = (int) DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', (int) config('knowledge.geology.id', 92))
            ->value('level');
        $geologyApplied = false;
        $own = 0;
        $geology = 0;

        $instances = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::Harvester->value)
            ->where('level', '>', 0)
            ->get();

        foreach ($instances as $instance) {
            if ($instance->tile_x === null || $instance->tile_y === null) {
                continue;
            }
            if ($instance->pending_until_tick !== null && (int) $instance->pending_until_tick >= $tick) {
                continue;
            }
            if (($instance->instability_outage_until_tick ?? null) !== null && (int) $instance->instability_outage_until_tick >= $tick) {
                continue;
            }

            $tile = DB::table('colony_tiles')
                ->where('colony_id', $colonyId)->where('q', $instance->tile_x)->where('r', $instance->tile_y)->first();
            if (! $tile) {
                continue;
            }

            $configMax = (int) ($resourceMaxCfg[$tile->tile_type] ?? 0);
            if ($configMax <= 0) {
                continue;
            }

            $remaining = min((int) ($tile->resource_amount ?? $configMax), $configMax);
            $base = GameTick::harvesterYield($tile->tile_type, $remaining, $configMax, $geologyApplied ? 0 : $geologyLevel);
            if ($base <= 0) {
                continue;
            }
            $geologyApplied = true;

            $credited = (int) round($base * $multiplier);
            $baseOnly = (int) round(GameTick::harvesterYield($tile->tile_type, $remaining, $configMax, 0) * $multiplier);
            $own += $baseOnly;
            $geology += $credited - $baseOnly;
        }

        return [$own, $geology];
    }

    /** Agrardom Organika output from game.production_curve (not while the building is in transit). */
    private function agrardomYield(int $colonyId, int $tick, float $multiplier): int
    {
        $curve = config('game.production_curve.41.5', []);
        $building = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)->where('building_id', 41)->first();
        if (! $building || $building->level <= 0) {
            return 0;
        }
        if ($building->pending_until_tick !== null && (int) $building->pending_until_tick >= $tick) {
            return 0;
        }

        return (int) round(GameTick::cumulativeCurveYield($curve, (int) $building->level) * $multiplier);
    }

    /** Agronomy Kenntnis bonus on the Agrardom output (only with a built Agrardom). */
    private function agronomyBonus(int $colonyId, float $multiplier): int
    {
        $level = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)->where('building_id', 41)->value('level');
        if ($level <= 0) {
            return 0;
        }

        $agronomyLevel = (int) DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', (int) config('knowledge.agronomy.id', 93))
            ->value('level');
        $bonus = GameTick::cumulativeCurveYield(config('game.agronomy_agrardom_bonus_per_level', []), $agronomyLevel);

        return (int) round($bonus * $multiplier);
    }
}
