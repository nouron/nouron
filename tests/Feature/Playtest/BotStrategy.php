<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\Techtree\PersonellService;
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

    // Engineer -> Scientist -> Trader: matches the sciencelab/bar path
    // buildings a fresh colony can reach first (hangar/pilot needs its own
    // building placed first too, but engineer+scientist+trader alone
    // satisfy Phase 1's "3 advisors" condition).
    private const HIRE_ORDER = [35, 36, 92];

    /**
     * @return array<int, array{name:string, when:callable(BotSession):bool, do:callable(BotSession):array}>
     */
    public static function default(): array
    {
        return [
            [
                'name' => 'repair_critical',
                'when' => fn (BotSession $b) => self::repairCandidate($b) !== null
                    && self::regolith($b) >= config('game.repair.regolith_per_click', 2),
                'do' => function (BotSession $b) {
                    $row = self::repairCandidate($b);

                    return $b->act('repair_critical', 'POST', '/colony/building/repair', [
                        'building_id' => $row->building_id,
                        'instance_id' => $row->instance_id,
                    ]);
                },
            ],
            [
                'name' => 'hire_advisor',
                'when' => fn (BotSession $b) => self::nextHireCandidate($b) !== null,
                'do' => function (BotSession $b) {
                    $personellId = self::nextHireCandidate($b);

                    return $b->act('hire_advisor', 'POST', '/advisors/hire', [
                        'personell_id' => $personellId,
                    ]);
                },
            ],
            [
                'name' => 'invest_cc',
                'when' => fn (BotSession $b) => self::ccLevel($b) < 3
                    && self::constructionAp($b) >= 1,
                'do' => fn (BotSession $b) => $b->act('invest_cc', 'POST', '/colony/building/invest', [
                    'building_id' => BuildingId::CommandCenter->value,
                ]),
            ],
            [
                'name' => 'explore_tile',
                'when' => fn (BotSession $b) => self::exploreCandidate($b) !== null,
                'do' => function (BotSession $b) {
                    $tile = self::exploreCandidate($b);

                    return $b->act('explore_tile', 'POST', '/colony/tile/explore', [
                        'q' => $tile->q,
                        'r' => $tile->r,
                    ]);
                },
            ],
            [
                'name' => 'place_building',
                'when' => fn (BotSession $b) => self::placeCandidate($b) !== null,
                'do' => function (BotSession $b) {
                    [$building, $tile] = self::placeCandidate($b);

                    return $b->act('place_building', 'POST', '/colony/building/place', [
                        'building_id' => $building['building_id'],
                        'q' => $tile->q,
                        'r' => $tile->r,
                    ]);
                },
            ],
            [
                'name' => 'invest_production',
                'when' => fn (BotSession $b) => self::productionInvestCandidate($b) !== null
                    && self::constructionAp($b) >= 1,
                'do' => function (BotSession $b) {
                    $row = self::productionInvestCandidate($b);

                    return $b->act('invest_production', 'POST', '/colony/building/invest', [
                        'building_id' => $row->building_id,
                        'instance_id' => $row->instance_id,
                    ]);
                },
            ],
        ];
    }

    private static function personellService(): PersonellService
    {
        return app(PersonellService::class);
    }

    private static function regolith(BotSession $b): int
    {
        return (int) (DB::table('colony_resources')
            ->where('colony_id', $b->colonyId)
            ->where('resource_id', self::RES_REGOLITH)
            ->value('amount') ?? 0);
    }

    private static function ccLevel(BotSession $b): int
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
        $credits = (int) (DB::table('user_resources')->where('user_id', $b->userId)->value('credits') ?? 0);

        foreach (self::HIRE_ORDER as $personellId) {
            if (in_array($personellId, $hired, true)) {
                continue;
            }

            $cfg = collect(config('advisors'))->firstWhere('id', $personellId);
            $pathBuildingId = match ($personellId) {
                36 => 31, // scientist -> sciencelab
                92 => 52, // trader -> bar
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

        $supplyUsed = (int) (DB::table('colony_buildings')->where('colony_id', $b->colonyId)->sum('supply_cost'));
        $supplyCap = (int) (DB::table('user_resources')->where('user_id', $b->userId)->value('supply') ?? 0);

        foreach ($buildings as $building) {
            $regolithCost = (int) ($building['build_cost'][self::RES_REGOLITH] ?? 0);
            if (self::regolith($b) < $regolithCost) {
                continue;
            }
            if ($supplyUsed + (int) $building['supply_cost'] > $supplyCap) {
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
}
