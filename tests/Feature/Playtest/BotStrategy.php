<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\AdvisorService;
use App\Services\ResourcesService;
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
                'when' => fn (BotSession $b) => self::availableAp($b) >= 1
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
                'when' => fn (BotSession $b) => self::ccLevel($b) < 3 && self::availableAp($b) >= 1,
                'do' => fn (BotSession $b) => $b->act('invest_cc', 'POST', '/colony/building/invest', [
                    'building_id' => BuildingId::CommandCenter->value,
                ]),
            ],
            [
                'name' => 'buy_corporate_harvester_offer',
                // Weg A for the Harvester 2nd instance (GDD §4c, CorporateContactService).
                // Rare, timed offer (Orin/corporate_rep) — checked early/high-priority since
                // it can expire and doesn't cost AP, only Credits.
                'when' => fn (BotSession $b) => self::corporateOfferCandidate($b),
                'do' => fn (BotSession $b, array $offer) => $b->act('buy_corporate_harvester_offer', 'POST', '/colony/corporate-contact/buy-harvester'),
            ],
            [
                'name' => 'place_harvester_instance2',
                // Once either Weg A (Orin) or Weg B (mission_harvester_salvage) has granted
                // the entitlement (HarvesterEntitlementService), place the 2nd instance —
                // free (no build_cost), so this should fire the very next tick after earning it.
                'when' => fn (BotSession $b) => self::harvesterInstance2Candidate($b),
                'do' => fn (BotSession $b, object $tile) => $b->act('place_harvester_instance2', 'POST', '/colony/building/place', [
                    'building_id' => BuildingId::Harvester->value,
                    'instance_id' => 2,
                    'q' => $tile->q,
                    'r' => $tile->r,
                ]),
            ],
            [
                'name' => 'deep_scan_signal_tile',
                // Explored tiles with an event_type (signal) must be deep-scanned before
                // they resolve into anything usable — including the event_ruin tiles Weg B
                // (mission_harvester_salvage) targets. The bot never did this before, so
                // ruin/event content was structurally unreachable regardless of missions.
                'when' => fn (BotSession $b) => self::deepScanCandidate($b),
                'do' => fn (BotSession $b, object $tile) => $b->act('deep_scan_signal_tile', 'POST', '/colony/tile/deep-scan', [
                    'q' => $tile->q,
                    'r' => $tile->r,
                ]),
            ],
            [
                'name' => 'dispatch_salvage_mission',
                // Weg B for the Harvester 2nd instance. Needs a docked freighter/corvette
                // (mission_recon_flight's drone doesn't qualify) and a deep-scanned
                // event_ruin tile — both are real prerequisites, not bot workarounds; if
                // this rarely fires, that's a real finding about ship/ruin availability,
                // not a bug in the rule.
                'when' => fn (BotSession $b) => self::salvageDispatchCandidate($b),
                'do' => fn (BotSession $b, array $candidate) => $b->act('dispatch_salvage_mission', 'POST', "/colony/hangar/{$candidate['ship']->hangar_instance_id}/dispatch", [
                    'mission_key' => 'mission_harvester_salvage',
                    'target' => ['q' => $candidate['tile']->q, 'r' => $candidate['tile']->r],
                ]),
            ],
            [
                'name' => 'relocate_harvester',
                'when' => fn (BotSession $b) => self::harvesterRelocateCandidate($b),
                'do' => fn (BotSession $b, object $tile) => $b->act('relocate_harvester', 'POST', '/colony/building/place', [
                    'building_id' => BuildingId::Harvester->value,
                    'instance_id' => 1,
                    'q' => $tile->q,
                    'r' => $tile->r,
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
                'when' => fn (BotSession $b) => self::availableAp($b) >= 1 ? self::productionInvestCandidate($b) : null,
                'do' => fn (BotSession $b, object $row) => $b->act('invest_production', 'POST', '/colony/building/invest', [
                    'building_id' => $row->building_id,
                    'instance_id' => $row->instance_id,
                ]),
            ],
            [
                'name' => 'research_knowledge',
                'when' => fn (BotSession $b) => self::availableAp($b) >= 1 ? self::researchCandidate($b) : null,
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
                'when' => fn (BotSession $b) => self::availableAp($b) >= (int) config('game.bar.ap_cost_accept', 1)
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

    private static function advisorService(): AdvisorService
    {
        return app(AdvisorService::class);
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

    /**
     * Available AP in the shared colony pool (GDD §13.1 — single pool, no
     * per-domain split anymore).
     */
    private static function availableAp(BotSession $b): int
    {
        return self::advisorService()->getAvailableActionPoints($b->colonyId);
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
        // No ring cap: harvesterRelocateCandidate() only ever offers already-explored
        // regolith tiles, so once ring<=2 regolith is exhausted the bot must be able
        // to explore further out (config('game.colony.explore_cost_per_ring') prices
        // ring 3 at 3 AP — a real, affordable game mechanic) or it deadlocks forever
        // with idle AP (root cause of seed=4242 runs stalling flat at Sol 20-95, see
        // storage/logs/playtest/4242-20260811_175942.json).
        $tile = DB::table('colony_tiles')
            ->where('colony_id', $b->colonyId)
            ->where('is_explored', 0)
            ->orderBy('ring')
            ->first();

        if ($tile === null) {
            return null;
        }

        $cost = (int) (config('game.colony.explore_cost_per_ring')[$tile->ring] ?? config('game.colony.explore_cost_default', 1));
        if (self::availableAp($b) < $cost) {
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

            // Sort priority (ascending key = higher priority):
            //   key[0] = 0 for bioFacility (must be first — ramp gate),
            //             1 for path buildings (sciencelab/hangar/bar — unlock advisor slots),
            //             2 for everything else
            //   key[1] = existing instance count (prefer new building types)
            $pathIds = [31, 44, 52];       // sciencelab, hangar, bar
            $bioFacilityId = 41;

            $placedCounts = DB::table('colony_buildings')
                ->where('colony_id', $b->colonyId)
                ->whereNotNull('tile_x')
                ->selectRaw('building_id, COUNT(*) as cnt')
                ->groupBy('building_id')
                ->pluck('cnt', 'building_id');

            usort($buildings, function ($a, $c) use ($placedCounts, $pathIds, $bioFacilityId) {
                $priority = function (array $building) use ($pathIds, $bioFacilityId): int {
                    $id = (int) $building['building_id'];
                    if ($id === $bioFacilityId) {
                        return 0;
                    }
                    if (in_array($id, $pathIds, true)) {
                        return 1;
                    }

                    return 2;
                };

                $pa = $priority($a);
                $pc = $priority($c);
                if ($pa !== $pc) {
                    return $pa <=> $pc;
                }

                return ($placedCounts[$a['building_id']] ?? 0) <=> ($placedCounts[$c['building_id']] ?? 0);
            });

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
        // Hold back Regolith if a path building is still needed — but only
        // enough for the cheapest unplaced one, not a flat 100.
        $activeAdvisors = DB::table('advisors')->where('colony_id', $b->colonyId)->count();
        if ($activeAdvisors < 3) {
            $needed = self::cheapestPendingPathBuildingCost($b);
            if ($needed !== null && self::regolith($b) < $needed) {
                return null;
            }
        }

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

    /**
     * Lowest-id Knowledge entity (90-96) not yet at max level whose required Sciencelab
     * (building_id=31) level is already met — cheap stand-in for the full
     * requirement graph the real ResearchService checks; a rejection here just
     * blocks the rule for the Sol, same as any other 422.
     */
    private static function researchCandidate(BotSession $b): ?int
    {
        // Same Rg-buffer logic as productionInvestCandidate.
        $activeAdvisors = DB::table('advisors')->where('colony_id', $b->colonyId)->count();
        if ($activeAdvisors < 3) {
            $needed = self::cheapestPendingPathBuildingCost($b);
            if ($needed !== null && self::regolith($b) < $needed) {
                return null;
            }
        }

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

        if (self::availableAp($b) < $navApCost) {
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

    /**
     * Weg A: Orin's (corporate_rep) current harvester offer, if any and affordable.
     * Server re-derives/validates the offer on purchase — this only reads it for the
     * `when` check, the price itself isn't trusted client-side.
     */
    private static function corporateOfferCandidate(BotSession $b): ?array
    {
        $result = $b->peek('/colony/corporate-contact/offer');
        $offer = $result['body']['offer'] ?? null;
        if ($offer === null) {
            return null;
        }

        return self::credits($b) >= (int) $offer['price'] ? $offer : null;
    }

    /**
     * Whether the player has earned the 2nd Harvester instance (Weg A or Weg B,
     * HarvesterEntitlementService — stored as a fired trigger, not a resource) and
     * hasn't placed it yet. Mirrors HarvesterEntitlementService::hasEntitlement()
     * directly against user_preferences, same DB-read pattern as the rest of this
     * class (no HTTP round-trip needed just to check a flag).
     */
    private static function harvesterInstance2Candidate(BotSession $b): ?object
    {
        $alreadyPlaced = DB::table('colony_buildings')
            ->where('colony_id', $b->colonyId)
            ->where('building_id', BuildingId::Harvester->value)
            ->where('instance_id', 2)
            ->whereNotNull('tile_x')
            ->exists();
        if ($alreadyPlaced) {
            return null;
        }

        $fired = json_decode(
            DB::table('user_preferences')->where('user_id', $b->userId)->value('fired_triggers') ?? '[]',
            true
        ) ?? [];
        $entitled = in_array('harvester_second_instance_unlocked_purchase', $fired, true)
            || in_array('harvester_second_instance_unlocked_salvage', $fired, true);
        if (! $entitled) {
            return null;
        }

        // Weg B (salvage) carries no CC-Lv3 gate of its own (HangarService::dispatchShip
        // only checks instance count + entitlement) — unlike Weg A, where
        // CorporateContactService::gatesSatisfied already requires CC-Lv3 before the
        // offer appears. Server still enforces it here (ColonyController::placeBuilding),
        // so without this check a salvage-earned entitlement before CC-3 would retry
        // and fail every Sol (qa-tester review PR #244).
        if (self::ccLevel($b) < 3) {
            return null;
        }

        return DB::table('colony_tiles as ct')
            ->where('ct.colony_id', $b->colonyId)
            ->where('ct.is_explored', 1)
            ->where('ct.tile_type', 'like', 'regolith_%')
            ->where('ct.resource_amount', '>', 0)
            ->whereNotExists(function ($query) use ($b) {
                $query->select(DB::raw(1))
                    ->from('colony_buildings as cb')
                    ->where('cb.colony_id', $b->colonyId)
                    ->whereColumn('cb.tile_x', 'ct.q')
                    ->whereColumn('cb.tile_y', 'ct.r');
            })
            ->orderByDesc('ct.resource_amount')
            ->first();
    }

    /**
     * Any explored tile carrying an unresolved signal (event_type set, not yet
     * deep-scanned) — the prerequisite step for event_ruin tiles (Weg B target) and
     * any other event content. Uplink-Station Lv2+ halves the AP cost (mirrors
     * ColonyTileService::deepScanTile); bot conservatively assumes the un-halved
     * cost when unsure to avoid overspending its Nav-AP.
     */
    private static function deepScanCandidate(BotSession $b): ?object
    {
        $uplinkLv = (int) (DB::table('colony_buildings')
            ->where('colony_id', $b->colonyId)
            ->where('building_id', (int) config('buildings.uplinkStation.id', 54))
            ->value('level') ?? 0);
        $apCost = $uplinkLv >= 2 ? 1 : 2;

        if (self::availableAp($b) < $apCost) {
            return null;
        }

        return DB::table('colony_tiles')
            ->where('colony_id', $b->colonyId)
            ->where('is_explored', 1)
            ->whereNotNull('event_type')
            ->where('is_deep_scanned', 0)
            ->orderBy('ring')
            ->first();
    }

    /**
     * Weg B: a docked freighter/corvette (mission_harvester_salvage's ship gate —
     * the drone doesn't qualify) plus an unclaimed deep-scanned event_ruin tile.
     */
    private static function salvageDispatchCandidate(BotSession $b): ?array
    {
        $navApCost = 4 * (int) config('missions.nav_ap_per_sol', 2); // sol_distance=4
        if (self::availableAp($b) < $navApCost) {
            return null;
        }

        // Same min-SP floor as dispatchCandidate() (qa-tester review PR #244: this rule
        // was missing it — a worn docked ship would otherwise match every Sol and fail
        // every Sol, masking real ship/ruin-availability rarity behind a self-inflicted
        // rejection loop).
        $shipMaxStatus = 20; // HangarService::SHIP_MAX_STATUS — not exposed, mirrored here
        $minSp = $shipMaxStatus * (float) config('missions.dispatch_min_sp_pct', 0.25);

        $eligibleShipIds = [(int) config('ships.freighter.id'), (int) config('ships.corvette.id')];
        $ship = DB::table('colony_ships')
            ->where('colony_id', $b->colonyId)
            ->where('ship_state', 'docked')
            ->whereIn('ship_id', $eligibleShipIds)
            ->where('status_points', '>=', $minSp)
            ->first();
        if ($ship === null) {
            return null;
        }

        $claimedTargets = DB::table('colony_hangar_missions')
            ->where('colony_id', $b->colonyId)
            ->where('destination', 'mission_harvester_salvage')
            ->whereIn('state', ['active', 'completed'])
            ->pluck('target');

        $tile = DB::table('colony_tiles')
            ->where('colony_id', $b->colonyId)
            ->where('is_deep_scanned', 1)
            ->where('event_type', 'event_ruin')
            ->orderBy('ring')
            ->get(['q', 'r'])
            ->first(fn ($t) => ! $claimedTargets->contains(json_encode(['q' => (int) $t->q, 'r' => (int) $t->r])));

        if ($tile === null) {
            return null;
        }

        return ['ship' => $ship, 'tile' => $tile];
    }

    private static function harvesterRelocateCandidate(BotSession $b): ?object
    {
        $harvester = DB::table('colony_buildings')
            ->where('colony_id', $b->colonyId)
            ->where('building_id', BuildingId::Harvester->value)
            ->where('instance_id', 1)
            ->first();

        if (! $harvester) {
            return null;
        }

        // Check if currently on a valid regolith tile with substantial resources remaining (>50)
        if ($harvester->tile_x !== null && $harvester->tile_y !== null) {
            $currentTile = DB::table('colony_tiles')
                ->where('colony_id', $b->colonyId)
                ->where('q', $harvester->tile_x)
                ->where('r', $harvester->tile_y)
                ->first();

            if ($currentTile && str_starts_with($currentTile->tile_type, 'regolith_') && ($currentTile->resource_amount ?? 0) > 50) {
                return null;
            }
        }

        // Find an explored, unbuilt regolith tile with available resources (qa-tester
        // review PR #244: same missing-occupied-check bug as harvesterInstance2Candidate
        // had — orderByDesc('resource_amount') often picked an already-built tile,
        // e.g. the harvester's own current one, wasting several Sols of tile_occupied
        // rejections before landing on a free one by chance).
        $targetTile = DB::table('colony_tiles as ct')
            ->where('ct.colony_id', $b->colonyId)
            ->where('ct.is_explored', 1)
            ->where('ct.tile_type', 'like', 'regolith_%')
            ->where('ct.resource_amount', '>', 0)
            ->whereNotExists(function ($query) use ($b) {
                $query->select(DB::raw(1))
                    ->from('colony_buildings as cb')
                    ->where('cb.colony_id', $b->colonyId)
                    ->whereColumn('cb.tile_x', 'ct.q')
                    ->whereColumn('cb.tile_y', 'ct.r');
            })
            ->orderByDesc('ct.resource_amount')
            ->first();

        if (! $targetTile) {
            return null;
        }

        $qx = $harvester->tile_x ?? 0;
        $qy = $harvester->tile_y ?? 0;
        $dx = $targetTile->q - $qx;
        $dy = $targetTile->r - $qy;
        $distance = (int) ((abs($dx) + abs($dy) + abs($dx + $dy)) / 2);
        $apCost = max(1, (int) ceil($distance * (int) config('game.harvester.relocate_ap_per_hex', 2)));

        if (self::availableAp($b) < $apCost) {
            return null;
        }

        return $targetTile;
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

    /**
     * Return the Regolith cost of the cheapest path building not yet placed in
     * the colony (sciencelab/hangar/bar all 95 Rg — from config('buildings')).
     * Returns null when all three path buildings are already placed (no saving needed).
     */
    private static function cheapestPendingPathBuildingCost(BotSession $b): ?int
    {
        // IDs mirror AdvisorController::PATH_BUILDINGS
        $pathBuildingIds = [31, 44, 52];

        $placed = DB::table('colony_buildings')
            ->where('colony_id', $b->colonyId)
            ->whereIn('building_id', $pathBuildingIds)
            ->whereNotNull('tile_x')
            ->pluck('building_id')
            ->all();

        $unplaced = array_diff($pathBuildingIds, $placed);
        if (empty($unplaced)) {
            return null; // All path buildings placed — no Rg buffer needed
        }

        $buildings = config('buildings');
        $costs = [];
        foreach ($buildings as $buildingData) {
            if (in_array($buildingData['id'] ?? null, $unplaced, true)) {
                // build_cost is keyed by resource_id; 3 = Regolith
                $costs[] = (int) ($buildingData['build_cost'][self::RES_REGOLITH] ?? 0);
            }
        }

        return empty($costs) ? null : min($costs);
    }
}
