<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\ResourcesService;
use App\Services\Techtree\PersonellService;
use App\Services\TickService;
use Illuminate\Support\Facades\DB;

/**
 * Ordered rule list — not an AI. Per Sol, rules are tried top to bottom;
 * the first one whose `when` matches fires via `do`, and the loop restarts
 * from rule 1. Rules 1-6 cover the Phase-1 core (checkPhase1Completion:
 * CC Lv3 + 2 production buildings Lv2 + 3 advisors).
 */
class BotStrategy
{
    private const RES_REGOLITH = 3;

    // Engineer -> Scientist -> Pilot -> Trader: matches the sciencelab/hangar/bar
    // path buildings a fresh colony can reach first. Pilot (id 89, gated on
    // Hangar being placed) sits between Scientist and Trader so the bot picks
    // up whichever path building is placed first without starving either hire.
    private const HIRE_ORDER = [35, 36, 89, 92];

    /**
     * @return array<int, array{name:string, when:callable(BotSession):mixed, do:callable(BotSession, mixed):array}>
     */
    public static function default(): array
    {
        return [
            [
                'name' => 'repair_critical',
                'when' => fn (BotSession $b) => self::constructionAp($b) >= 1
                    && self::regolith($b) >= config('game.repair.regolith_per_click', 2)
                    ? self::repairCandidate($b)
                    : null,
                'do' => fn (BotSession $b, object $row) => $b->act('repair_critical', 'POST', '/colony/building/repair', [
                    'building_id' => $row->building_id,
                    'instance_id' => $row->instance_id,
                ]),
            ],
            [
                'name' => 'hire_advisor',
                'when' => fn (BotSession $b) => self::nextHireCandidate($b),
                'do' => fn (BotSession $b, int $personellId) => $b->act('hire_advisor', 'POST', '/advisors/hire', [
                    'personell_id' => $personellId,
                ]),
            ],
            [
                'name' => 'invest_cc',
                'when' => fn (BotSession $b) => self::ccLevel($b) < 3 && self::constructionAp($b) >= 1,
                'do' => fn (BotSession $b) => $b->act('invest_cc', 'POST', '/colony/building/invest', [
                    'building_id' => BuildingId::CommandCenter->value,
                ]),
            ],
            [
                'name' => 'explore_tile',
                'when' => fn (BotSession $b) => self::exploreCandidate($b),
                'do' => fn (BotSession $b, object $tile) => $b->act('explore_tile', 'POST', '/colony/tile/explore', [
                    'q' => $tile->q,
                    'r' => $tile->r,
                ]),
            ],
            [
                'name' => 'place_building',
                'when' => fn (BotSession $b) => self::placeCandidate($b),
                'do' => function (BotSession $b, array $candidate) {
                    [$building, $tile] = $candidate;

                    return $b->act('place_building', 'POST', '/colony/building/place', [
                        'building_id' => $building['building_id'],
                        'q' => $tile->q,
                        'r' => $tile->r,
                    ]);
                },
            ],
            [
                'name' => 'invest_production',
                'when' => fn (BotSession $b) => self::constructionAp($b) >= 1 ? self::productionInvestCandidate($b) : null,
                'do' => fn (BotSession $b, object $row) => $b->act('invest_production', 'POST', '/colony/building/invest', [
                    'building_id' => $row->building_id,
                    'instance_id' => $row->instance_id,
                ]),
            ],
            [
                'name' => 'research_knowledge',
                'when' => fn (BotSession $b) => self::researchAp($b) >= 1 ? self::researchCandidate($b) : null,
                'do' => function (BotSession $b, int $researchId) {
                    // Try to close out a level first (accumulated ap_spend may already
                    // meet the threshold); investBlocker() doesn't cap 'add' on ap_spend,
                    // so levelup is the only way to find out a level is actually done.
                    $res = $b->act('research_knowledge', 'POST', "/techtree/research/{$researchId}/order", [
                        'order' => 'levelup',
                    ]);
                    if ($res['ok']) {
                        return $res;
                    }

                    // _invest() caps 'add' at ap_spend == ap_for_levelup and still
                    // returns ok:true once capped — falling back to 'add' for any
                    // OTHER reason (a hard gate like knowledge_cc_gate, a missing
                    // building, max_level) would loop forever with no progress.
                    // Only "not enough ap_spend yet" justifies another 'add'.
                    if ($res['error'] !== 'insufficient_ap_invested') {
                        return $res;
                    }

                    return $b->act('research_knowledge', 'POST', "/techtree/research/{$researchId}/order", [
                        'order' => 'add',
                        'ap' => 1,
                    ]);
                },
            ],
            [
                'name' => 'dispatch_mission',
                'when' => fn (BotSession $b) => self::dispatchCandidate($b),
                'do' => fn (BotSession $b, object $ship) => $b->act('dispatch_mission', 'POST', "/colony/hangar/{$ship->hangar_instance_id}/dispatch", [
                    'mission_key' => 'mission_recon_flight',
                ]),
            ],
            [
                'name' => 'accept_bar_offer',
                'when' => fn (BotSession $b) => self::personellService()->getAvailableActionPoints('economy', $b->colonyId) >= (int) config('game.bar.ap_cost_accept', 1)
                    ? self::barOfferCandidate($b)
                    : null,
                'do' => fn (BotSession $b, object $offer) => $b->act('accept_bar_offer', 'POST', "/colony/bar/accept/{$offer->id}"),
            ],
            [
                'name' => 'request_ship',
                // Bootstrap ship (drone, cheapest, matches mission_recon_flight — the
                // only ship type mission_recon_flight accepts, config('missions')) is
                // capped at one and requires a free hangar slot, so it always docks
                // and can actually fly recon. request_ship has no mission-driven
                // demand signal, and unbounded drone-buying (300cr each) would
                // starve hire_advisor's remaining hires of credits permanently, so
                // this stays gated until all 3 Phase-1 advisors are active.
                //
                // Once all 3 are active, shipToRequest() also allows buying further
                // ships (preferring the freighter, 500cr) WITHOUT requiring a free
                // hangar slot: HangarService::requestShip supports ship_state =
                // 'pending' when no slot is free (it decays after
                // pending_decay_ticks unless a hangar is later built to receive
                // it) — that's a real, intentional game mechanic, not a bot
                // workaround. Since the bot's placeCandidate() logic rarely builds
                // a second Hangar instance, requiring a free slot here would make
                // it structurally impossible to ever exercise Path B (trade) via a
                // freighter; see docs/handoff-ap-ratenmodell.md §4/§7.
                //
                // NOTE (2026-08-03 measurement run, seed=4242): even with this rule
                // reachable, no ship gets bought in that run — the Hangar isn't
                // placed until Sol 44, by which point Credits have already been at
                // 0 since Sol ~35 (advisor upkeep is net-negative against income,
                // config('game.advisor') "3 advisors at rank 2 cost 150 Cr/Tick
                // against ~30-70 Cr/Tick income"). That's a real Path-B finding
                // about the credit economy, not a bug in this rule — see report.
                'when' => fn (BotSession $b) => self::hangarLevel($b) >= 1
                    ? self::shipToRequest($b)
                    : null,
                'do' => fn (BotSession $b, int $shipId) => $b->act('request_ship', 'POST', '/colony/hangar/request', [
                    'ship_id' => $shipId,
                ]),
            ],
        ];
    }

    private static function personellService(): PersonellService
    {
        return app(PersonellService::class);
    }

    /** Shared with RunReport — the single source for this lookup. */
    public static function regolith(BotSession $b): int
    {
        return (int) (DB::table('colony_resources')
            ->where('colony_id', $b->colonyId)
            ->where('resource_id', self::RES_REGOLITH)
            ->value('amount') ?? 0);
    }

    /** Shared with RunReport — the single source for this lookup. */
    public static function ccLevel(BotSession $b): int
    {
        return (int) (DB::table('colony_buildings')
            ->where('colony_id', $b->colonyId)
            ->where('building_id', BuildingId::CommandCenter->value)
            ->value('level') ?? 0);
    }

    private static function constructionAp(BotSession $b): int
    {
        return self::personellService()->getAvailableActionPoints('construction', $b->colonyId);
    }

    private static function navigationAp(BotSession $b): int
    {
        return self::personellService()->getAvailableActionPoints('navigation', $b->colonyId);
    }

    private static function repairCandidate(BotSession $b): ?object
    {
        return DB::table('colony_buildings as cb')
            ->join('buildings as bld', 'bld.id', '=', 'cb.building_id')
            ->where('cb.colony_id', $b->colonyId)
            ->whereNotNull('cb.tile_x')
            ->where('cb.level', '>=', 1)
            ->whereColumn('cb.status_points', '<', DB::raw('bld.max_status_points * 0.3'))
            ->orderBy('cb.status_points')
            ->select('cb.building_id', 'cb.instance_id')
            ->first();
    }

    private static function nextHireCandidate(BotSession $b): ?int
    {
        $activeCount = DB::table('advisors')->where('colony_id', $b->colonyId)->count();
        if ($activeCount >= 3) {
            return null;
        }

        $hired = DB::table('advisors')->where('colony_id', $b->colonyId)->pluck('personell_id')->all();
        $credits = self::credits($b);

        foreach (self::HIRE_ORDER as $personellId) {
            if (in_array($personellId, $hired, true)) {
                continue;
            }

            $cfg = collect(config('advisors'))->firstWhere('id', $personellId);
            $pathBuildingId = match ($personellId) {
                36 => BuildingId::Sciencelab->value, // scientist -> sciencelab
                89 => BuildingId::Hangar->value, // pilot -> hangar
                92 => BuildingId::Bar->value, // trader -> bar
                default => null,
            };

            if ($pathBuildingId !== null) {
                $placed = DB::table('colony_buildings')
                    ->where('colony_id', $b->colonyId)
                    ->where('building_id', $pathBuildingId)
                    ->where('level', '>', 0)
                    ->whereNotNull('tile_x')
                    ->exists();
                if (! $placed) {
                    continue;
                }
            }

            if ($credits < (int) ($cfg['credits'] ?? PHP_INT_MAX)) {
                continue;
            }

            return $personellId;
        }

        return null;
    }

    private static function exploreCandidate(BotSession $b): ?object
    {
        $tile = DB::table('colony_tiles')
            ->where('colony_id', $b->colonyId)
            ->where('is_explored', 0)
            ->where('ring', '<=', 2)
            ->orderBy('ring')
            ->first();

        if ($tile === null) {
            return null;
        }

        $cost = (int) (config('game.colony.explore_cost_per_ring')[$tile->ring] ?? config('game.colony.explore_cost_default', 1));
        if (self::navigationAp($b) < $cost) {
            return null;
        }

        return $tile;
    }

    /**
     * @return array{0: array, 1: object}|null [building row from availableBuildings(), tile row]
     */
    private static function placeCandidate(BotSession $b): ?array
    {
        // Heaviest rule in the set (one HTTP round-trip + one ResourcesService::check()
        // per candidate building) — memoized per BotSession until the next real action,
        // since nothing here can change while earlier rules keep failing their `when`.
        return $b->remember('place_candidate', function () use ($b) {
            $available = $b->peek('/colony/buildings/available');
            $buildings = $available['body']['buildings'] ?? [];

            // Prefer a new building type over duplicating an already-placed instanced
            // one (Housing/Hangar) — otherwise the bot happily re-instances the first
            // building in id order forever and never reaches the path buildings
            // (sciencelab/hangar/bar) the third advisor slot depends on.
            $placedCounts = DB::table('colony_buildings')
                ->where('colony_id', $b->colonyId)
                ->whereNotNull('tile_x')
                ->selectRaw('building_id, COUNT(*) as cnt')
                ->groupBy('building_id')
                ->pluck('cnt', 'building_id');

            usort($buildings, fn ($a, $c) => ($placedCounts[$a['building_id']] ?? 0) <=> ($placedCounts[$c['building_id']] ?? 0));

            // Real cap/possession logic lives in ResourcesService — supply is CC level +
            // Housing count + knowledge bonus, not the flat user_resources.supply seed
            // value, and build_cost can list more than just Regolith (e.g. compounds).
            $resourcesService = app(ResourcesService::class);
            $freeSupply = $resourcesService->getFreeSupply($b->colonyId);

            foreach ($buildings as $building) {
                $costs = [];
                foreach ($building['build_cost'] as $resourceId => $amount) {
                    $costs[] = ['resource_id' => $resourceId, 'amount' => $amount];
                }
                if ($costs !== [] && ! $resourcesService->check($costs, $b->colonyId)) {
                    continue;
                }
                if ((int) $building['supply_cost'] > $freeSupply) {
                    continue;
                }

                $tile = DB::table('colony_tiles as ct')
                    ->where('ct.colony_id', $b->colonyId)
                    ->where('ct.is_colony_zone', 1)
                    ->whereNotExists(function ($query) use ($b) {
                        $query->select(DB::raw(1))
                            ->from('colony_buildings as cb')
                            ->where('cb.colony_id', $b->colonyId)
                            ->whereColumn('cb.tile_x', 'ct.q')
                            ->whereColumn('cb.tile_y', 'ct.r');
                    })
                    ->first();

                if ($tile === null) {
                    return null;
                }

                return [$building, $tile];
            }

            return null;
        });
    }

    private static function productionInvestCandidate(BotSession $b): ?object
    {
        return DB::table('colony_buildings as cb')
            ->join('buildings as bld', 'bld.id', '=', 'cb.building_id')
            ->where('cb.colony_id', $b->colonyId)
            ->where('cb.building_id', '!=', BuildingId::CommandCenter->value)
            ->where('cb.building_id', '!=', BuildingId::Harvester->value)
            ->whereNotNull('cb.tile_x')
            ->where('cb.level', '<', 2)
            ->where(fn ($q) => $q->whereNull('bld.max_level')->orWhereColumn('cb.level', '<', 'bld.max_level'))
            ->orderByDesc('cb.level')
            ->orderBy('cb.building_id')
            ->select('cb.building_id', 'cb.instance_id')
            ->first();
    }

    private static function researchAp(BotSession $b): int
    {
        return self::personellService()->getResearchPoints($b->colonyId);
    }

    /**
     * Lowest-id Knowledge entity (90-96) not yet at max level whose required Sciencelab
     * (building_id=31) level is already met — cheap stand-in for the full
     * requirement graph the real ResearchService checks; a rejection here just
     * blocks the rule for the Sol, same as any other 422.
     */
    private static function researchCandidate(BotSession $b): ?int
    {
        $sciencelabLevel = (int) (DB::table('colony_buildings')
            ->where('colony_id', $b->colonyId)
            ->where('building_id', BuildingId::Sciencelab->value)
            ->value('level') ?? 0);

        $row = DB::table('researches as r')
            ->leftJoin('colony_researches as cr', function ($join) use ($b) {
                $join->on('cr.research_id', '=', 'r.id')->where('cr.colony_id', $b->colonyId);
            })
            ->where('r.purpose', 'knowledge')
            ->whereBetween('r.id', [90, 96])
            ->where(fn ($q) => $q->whereNull('r.required_building_level')->orWhereRaw('? >= r.required_building_level', [$sciencelabLevel]))
            ->where(fn ($q) => $q->whereNull('cr.level')->orWhere('cr.level', '<', 5))
            ->orderBy('r.id')
            ->value('r.id');

        return $row !== null ? (int) $row : null;
    }

    private static function dispatchCandidate(BotSession $b): ?object
    {
        $shipMaxStatus = 20; // HangarService::SHIP_MAX_STATUS — not exposed, mirrored here
        $minSp = $shipMaxStatus * (float) config('missions.dispatch_min_sp_pct', 0.25);
        $navApCost = 1 * (int) config('missions.nav_ap_per_sol', 2); // mission_recon_flight: sol_distance = 1

        if (self::navigationAp($b) < $navApCost) {
            return null;
        }

        return DB::table('colony_ships')
            ->where('colony_id', $b->colonyId)
            ->where('ship_state', 'docked')
            ->where('status_points', '>=', $minSp)
            ->first();
    }

    private static function barOfferCandidate(BotSession $b): ?object
    {
        $barLevel = (int) (DB::table('colony_buildings')
            ->where('colony_id', $b->colonyId)
            ->where('building_id', BuildingId::Bar->value)
            ->value('level') ?? 0);

        if ($barLevel < 1) {
            return null;
        }

        $tick = app(TickService::class)->getTickCount();

        return DB::table('bar_offers')
            ->where('colony_id', $b->colonyId)
            ->where('expires_tick', '>', $tick)
            ->where('is_accepted', false)
            ->orderBy('id')
            ->first();
    }

    private static function hangarLevel(BotSession $b): int
    {
        return (int) (DB::table('colony_buildings')
            ->where('colony_id', $b->colonyId)
            ->where('building_id', BuildingId::Hangar->value)
            ->value('level') ?? 0);
    }

    /**
     * Which ship (if any) request_ship should buy next, or null if none is
     * affordable/allowed right now. First ship is always the cheapest drone,
     * bought only into a free hangar slot so it actually docks and can fly
     * mission_recon_flight. Further purchases only open up once all 3
     * Phase-1 advisors are hired, preferring the freighter (needed to ever
     * exercise the trade/Path-B code path) over another drone — bought
     * regardless of a free slot, since HangarService::requestShip supports
     * a 'pending' ship_state when the hangar is full (real game mechanic,
     * see request_ship rule comment above).
     */
    private static function shipToRequest(BotSession $b): ?int
    {
        $credits = self::credits($b);
        $droneCost = (int) config('ships.drone.nexus_cost', 0);

        if (! self::hasAnyShip($b)) {
            return self::hasFreeHangarSlot($b) && $credits >= $droneCost
                ? (int) config('ships.drone.id')
                : null;
        }

        $activeAdvisors = DB::table('advisors')->where('colony_id', $b->colonyId)->count();
        if ($activeAdvisors < 3) {
            return null;
        }

        $freighterCost = (int) config('ships.freighter.nexus_cost', 0);
        if ($credits >= $freighterCost) {
            return (int) config('ships.freighter.id');
        }

        return $credits >= $droneCost ? (int) config('ships.drone.id') : null;
    }

    private static function hasAnyShip(BotSession $b): bool
    {
        return DB::table('colony_ships')->where('colony_id', $b->colonyId)->exists();
    }

    private static function hasFreeHangarSlot(BotSession $b): bool
    {
        $hangarInstances = DB::table('colony_buildings')
            ->where('colony_id', $b->colonyId)
            ->where('building_id', BuildingId::Hangar->value)
            ->pluck('instance_id');

        $occupied = DB::table('colony_ships')
            ->where('colony_id', $b->colonyId)
            ->whereNotNull('hangar_instance_id')
            ->pluck('hangar_instance_id');

        return $hangarInstances->diff($occupied)->isNotEmpty();
    }

    /** Shared with RunReport — the single source for this lookup. */
    public static function credits(BotSession $b): int
    {
        return (int) (DB::table('user_resources')->where('user_id', $b->userId)->value('credits') ?? 0);
    }
}
