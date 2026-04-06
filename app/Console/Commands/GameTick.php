<?php

namespace App\Console\Commands;

use App\Models\Colony;
use App\Models\ColonyBuilding;
use App\Models\ColonyResearch;
use App\Models\ColonyResource;
use App\Models\Fleet;
use App\Models\FleetOrder;
use App\Models\FleetResource;
use App\Models\FleetShip;
use App\Models\UserResource;
use App\Services\EventService;
use App\Services\MoralService;
use App\Services\ResourcesService;
use App\Services\TickService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * GameTick — processes one game tick.
 *
 * Run manually:   php artisan game:tick
 * Run for tick N: php artisan game:tick --tick=16300
 *
 * Steps per tick:
 *  1. Fleet move orders   — update fleet position to ordered coordinates
 *  2. Fleet trade orders  — transfer resources between fleet and colony
 *  3. Fleet combat orders — resolve combat between attacking and defending fleets
 *  4. Building decay      — decrement status_points (per-type decay_rate); level-down at ≤ 0
 *  5. Ship decay          — decrement fleet_ships.status_points; combat fleets × 2; remove at ≤ 0
 *  6. Research decay      — decrement colony_researches.status_points; level-down at ≤ 0
 *  7. Supply cap          — SET user_resources.supply = CC_flat + housing_level × 8 (cap model)
 *  8. Resource generation — produce colony resources per industry building level (moral multiplier applied)
 *  8b. Moral calculation  — recalculate colony moral and store in colony_resources (resource_id=12)
 *  9. Advisor ticks       — increment active_ticks, check rank promotions
 */
class GameTick extends Command
{
    protected $signature   = 'game:tick {--tick= : Override the tick number (default: current tick)}';
    protected $description = 'Process one game tick (fleet orders, decay, supply, resources)';

    /** Fleet IDs that were involved in combat this tick — used for 2× ship decay. */
    private array $combatFleetIds = [];

    public function __construct(
        private readonly TickService      $tickService,
        private readonly EventService     $eventService,
        private readonly MoralService     $moralService,
        private readonly ResourcesService $resourcesService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($override = $this->option('tick')) {
            $this->tickService->setTickCount((int) $override);
        }

        $tick = $this->tickService->getTickCount();
        $this->info("Processing tick {$tick}…");

        DB::transaction(function () use ($tick) {
            $n = $this->processMoveOrders($tick);
            $this->line("  Fleet move orders:        {$n}");

            $n = $this->processTradeOrders($tick);
            $this->line("  Fleet trade orders:       {$n}");

            $n = $this->processCombatOrders($tick);
            $this->line("  Fleet combat orders:      {$n}");

            $n = $this->processBuildingDecay($tick);
            $this->line("  Buildings levelled down:  {$n}");

            $n = $this->processShipDecay($tick);
            $this->line("  Ship entries destroyed:   {$n}");

            $n = $this->processResearchDecay($tick);
            $this->line("  Researches levelled down: {$n}");

            $n = $this->calculateSupply();
            $this->line("  Users supply updated:     {$n}");

            $n = $this->generateResources($tick);
            $this->line("  Colonies with resources:  {$n}");

            $n = $this->calculateMoral($tick);
            $this->line("  Colonies moral updated:   {$n}");

            $n = $this->incrementAdvisorTicks();
            $this->line("  Advisors ticked:          {$n}");
        });

        $this->info("Tick {$tick} done.");
        return self::SUCCESS;
    }

    // ── 1. Fleet: move orders ────────────────────────────────────────────────

    private function processMoveOrders(int $tick): int
    {
        $orders = FleetOrder::where('tick', $tick)
            ->where('order', 'move')
            ->where('was_processed', 0)
            ->get();

        foreach ($orders as $order) {
            $coords = json_decode($order->coordinates, true);
            if (!is_array($coords) || count($coords) < 3) {
                $this->warn("  Fleet {$order->fleet_id}: invalid move coordinates, skipping.");
                continue;
            }

            Fleet::where('id', $order->fleet_id)->update([
                'x'    => $coords[0],
                'y'    => $coords[1],
                'spot' => $coords[2],
            ]);

            $order->update(['was_processed' => 1]);

            $fleet = Fleet::find($order->fleet_id);
            if ($fleet) {
                $this->eventService->createEvent([
                    'user'       => $fleet->user_id,
                    'tick'       => $tick,
                    'event'      => 'galaxy.fleet_arrived',
                    'area'       => 'galaxy',
                    'parameters' => serialize(['fleet_id' => $order->fleet_id, 'coords' => $coords]),
                ]);
            }
        }

        return $orders->count();
    }

    // ── 2. Fleet: trade orders ───────────────────────────────────────────────

    private function processTradeOrders(int $tick): int
    {
        $orders = FleetOrder::where('tick', $tick)
            ->where('order', 'trade')
            ->where('was_processed', 0)
            ->get();

        $processed = 0;

        foreach ($orders as $order) {
            $data = json_decode($order->data, true);
            if (!is_array($data)) {
                $this->warn("  Fleet {$order->fleet_id}: invalid trade data, skipping.");
                continue;
            }

            $colonyId   = (int) ($data['colony_id'] ?? 0);
            $resourceId = (int) ($data['resource_id'] ?? 0);
            $amount     = (int) ($data['amount'] ?? 0);
            $direction  = (int) ($data['direction'] ?? 0); // 0=buy(colony→fleet), 1=sell(fleet→colony)

            if (!$colonyId || !$resourceId || !$amount) {
                $this->warn("  Fleet {$order->fleet_id}: incomplete trade data (colony_id={$colonyId}), skipping.");
                continue;
            }

            $this->transferResource($colonyId, $order->fleet_id, $resourceId,
                $direction === 0 ? $amount : -$amount);

            $order->update(['was_processed' => 1]);

            $fleet = Fleet::find($order->fleet_id);
            if ($fleet) {
                $this->eventService->createEvent([
                    'user'       => $fleet->user_id,
                    'tick'       => $tick,
                    'event'      => 'galaxy.trade',
                    'area'       => 'galaxy',
                    'parameters' => serialize(['colony_id' => $colonyId]),
                ]);
            }

            $processed++;
        }

        return $processed;
    }

    /**
     * Transfer $amount of $resourceId from colony to fleet (negative = fleet to colony).
     */
    private function transferResource(int $colonyId, int $fleetId, int $resourceId, int $amount): void
    {
        // Clamp amount to what the source actually has to prevent creating resources from nothing.
        if ($amount > 0) {
            // Colony → Fleet: clamp to colony stock
            $colonyStock = (int) ColonyResource::where('colony_id', $colonyId)
                ->where('resource_id', $resourceId)
                ->value('amount');
            $amount = min($amount, $colonyStock);
        } elseif ($amount < 0) {
            // Fleet → Colony: clamp to fleet stock
            $fleetStock = (int) FleetResource::where('fleet_id', $fleetId)
                ->where('resource_id', $resourceId)
                ->value('amount');
            $amount = max($amount, -$fleetStock);
        }

        if ($amount === 0) {
            return;
        }

        ColonyResource::where('colony_id', $colonyId)
            ->where('resource_id', $resourceId)
            ->update(['amount' => DB::raw("MAX(0, amount - {$amount})")]);

        // FleetResource has a composite PK (fleet_id, resource_id) — Eloquent::increment()
        // would generate WHERE id = null and silently do nothing. Use a raw UPDATE + INSERT
        // pair instead.
        $updated = DB::table('fleet_resources')
            ->where('fleet_id', $fleetId)
            ->where('resource_id', $resourceId)
            ->update(['amount' => DB::raw("amount + {$amount}")]);

        if ($updated === 0) {
            // Row did not exist yet (new resource on this fleet)
            DB::table('fleet_resources')->insert([
                'fleet_id'    => $fleetId,
                'resource_id' => $resourceId,
                'amount'      => $amount,
            ]);
        }
    }

    // ── 3. Fleet: combat orders ──────────────────────────────────────────────

    private function processCombatOrders(int $tick): int
    {
        $orders = FleetOrder::where('tick', $tick)
            ->where('order', 'attack')
            ->where('was_processed', 0)
            ->get();

        $shipPower = config('game.combat.ship_power', []);
        $processed = 0;

        foreach ($orders as $order) {
            $coords = json_decode($order->coordinates, true);
            if (!is_array($coords) || count($coords) < 3) {
                $this->warn("  Fleet {$order->fleet_id}: invalid attack coordinates, skipping.");
                continue;
            }

            $attacker = Fleet::find($order->fleet_id);
            if (!$attacker) {
                continue;
            }

            Fleet::where('id', $attacker->id)->update([
                'x' => $coords[0], 'y' => $coords[1], 'spot' => $coords[2],
            ]);

            $defenders = Fleet::where('x', $coords[0])
                ->where('y', $coords[1])
                ->where('spot', $coords[2])
                ->where('user_id', '!=', $attacker->user_id)
                ->where('id', '!=', $attacker->id)
                ->get();

            if ($defenders->isEmpty()) {
                $order->update(['was_processed' => 1]);
                $this->eventService->createEvent([
                    'user'       => $attacker->user_id,
                    'tick'       => $tick,
                    'event'      => 'galaxy.fleet_arrived',
                    'area'       => 'galaxy',
                    'parameters' => serialize(['fleet_id' => $attacker->id, 'coords' => $coords]),
                ]);
                $processed++;
                continue;
            }

            $attackerPower    = $this->calcFleetPower($attacker->id, $shipPower);
            $defenderFleetIds = $defenders->pluck('id')->all();
            $defenderPower    = array_sum(array_map(
                fn($id) => $this->calcFleetPower($id, $shipPower),
                $defenderFleetIds
            ));

            $totalPower = $attackerPower + $defenderPower;
            if ($totalPower > 0) {
                $this->applyShipLosses($attacker->id, $defenderPower / $totalPower, $shipPower);
                foreach ($defenderFleetIds as $defId) {
                    $this->applyShipLosses($defId, $attackerPower / $totalPower, $shipPower);
                }

                // Record all combat participants for the ship decay step
                $this->combatFleetIds[] = $attacker->id;
                foreach ($defenderFleetIds as $defId) {
                    $this->combatFleetIds[] = $defId;
                }
            }

            $order->update(['was_processed' => 1]);

            // Notify attacker
            $this->eventService->createEvent([
                'user'       => $attacker->user_id,
                'tick'       => $tick,
                'event'      => 'galaxy.combat',
                'area'       => 'galaxy',
                'parameters' => serialize([
                    'attacker_id' => $attacker->user_id,
                    'defender_id' => $defenders->first()->user_id,
                    'colony_id'   => 0,
                ]),
            ]);

            $attackerColonyId = $this->getColonyIdByUserId($attacker->user_id);
            if ($attackerColonyId) {
                $this->moralService->fireEvent(
                    $attackerColonyId,
                    $attackerPower >= $defenderPower ? 'combat_won' : 'combat_lost',
                    $tick
                );
            }

            // Notify each unique defender user
            foreach ($defenders->unique('user_id') as $defFleet) {
                $this->eventService->createEvent([
                    'user'       => $defFleet->user_id,
                    'tick'       => $tick,
                    'event'      => 'galaxy.combat',
                    'area'       => 'galaxy',
                    'parameters' => serialize([
                        'attacker_id' => $attacker->user_id,
                        'defender_id' => $defFleet->user_id,
                        'colony_id'   => 0,
                    ]),
                ]);

                $defenderColonyId = $this->getColonyIdByUserId($defFleet->user_id);
                if ($defenderColonyId) {
                    $this->moralService->fireEvent($defenderColonyId, 'colony_attacked', $tick);
                    $this->moralService->fireEvent(
                        $defenderColonyId,
                        $defenderPower >= $attackerPower ? 'combat_won' : 'combat_lost',
                        $tick
                    );
                }
            }

            $processed++;
        }

        return $processed;
    }

    private function getColonyIdByUserId(int $userId): ?int
    {
        return DB::table('glx_colonies')
            ->where('user_id', $userId)
            ->value('id');
    }

    private function calcFleetPower(int $fleetId, array $shipPower): int
    {
        return FleetShip::where('fleet_id', $fleetId)
            ->get()
            ->sum(fn($s) => $s->count * ($shipPower[$s->ship_id] ?? 0));
    }

    private function applyShipLosses(int $fleetId, float $lossRatio, array $shipPower): void
    {
        $ships = FleetShip::where('fleet_id', $fleetId)->get();
        foreach ($ships as $ship) {
            if (($shipPower[$ship->ship_id] ?? 0) === 0) {
                continue; // non-combat ships not destroyed in battle
            }
            $remaining = max(0, $ship->count - (int) ceil($ship->count * $lossRatio));
            if ($remaining <= 0) {
                $ship->delete();
            } else {
                $ship->update(['count' => $remaining]);
            }
        }
    }

    // ── 4. Building decay ────────────────────────────────────────────────────

    private function processBuildingDecay(int $tick): int
    {
        $fallbackRate  = (float) config('game.decay.rate', 1);
        $overcapFactor = (float) config('game.decay.overcap_factor', 2.0);
        $decayRates    = DB::table('buildings')->pluck('decay_rate', 'id');
        $maxSPMap      = DB::table('buildings')->pluck('max_status_points', 'id');
        $levelled      = 0;

        // Build the over-cap set once before iterating — O(colonies), not O(buildings).
        $overCapColonies = $this->resourcesService->getOverCapColonyIds();

        $buildings = ColonyBuilding::where('level', '>', 0)->get();

        foreach ($buildings as $cb) {
            $rate         = (float) ($decayRates[$cb->building_id] ?? $fallbackRate);
            $overCapMult  = in_array($cb->colony_id, $overCapColonies) ? $overcapFactor : 1.0;
            $newStatus    = (float) $cb->status_points - ($rate * $overCapMult);
            $where     = ['colony_id' => $cb->colony_id, 'building_id' => $cb->building_id];

            if ($newStatus <= 0) {
                $maxSP    = (int) ($maxSPMap[$cb->building_id] ?? 20);
                $newLevel = max(0, $cb->level - 1);

                DB::table('colony_buildings')->where($where)->update([
                    'level'         => $newLevel,
                    'status_points' => $maxSP,
                ]);

                $colony = Colony::find($cb->colony_id);
                $this->eventService->createEvent([
                    'user'       => $colony?->user_id ?? 0,
                    'tick'       => $tick,
                    'event'      => 'techtree.level_down',
                    'area'       => 'techtree',
                    'parameters' => serialize([
                        'colony_id' => $cb->colony_id,
                        'tech_id'   => $cb->building_id,
                    ]),
                ]);
                $levelled++;
            } else {
                DB::table('colony_buildings')->where($where)
                    ->update(['status_points' => $newStatus]);
            }
        }

        return $levelled;
    }

    // ── 5. Ship decay ────────────────────────────────────────────────────────

    private function processShipDecay(int $tick): int
    {
        $fallbackRate  = (float) config('game.decay.rate', 1);
        $combatFactor  = (float) config('game.decay.combat_factor', 2);
        $decayRates    = DB::table('ships')->pluck('decay_rate', 'id');
        $maxSPMap      = DB::table('ships')->pluck('max_status_points', 'id');
        $destroyed     = 0;

        FleetShip::orderBy('fleet_id')->orderBy('ship_id')->chunk(200, function ($fleetShips) use ($fallbackRate, $combatFactor, $decayRates, $maxSPMap, &$destroyed, $tick) {
        foreach ($fleetShips as $fs) {
            $rate = (float) ($decayRates[$fs->ship_id] ?? $fallbackRate);

            if (in_array($fs->fleet_id, $this->combatFleetIds)) {
                $rate *= $combatFactor;
            }

            $newStatus = (float) $fs->status_points - $rate;
            $where     = ['fleet_id' => $fs->fleet_id, 'ship_id' => $fs->ship_id, 'is_cargo' => $fs->is_cargo];

            if ($newStatus <= 0) {
                // Ship fully decayed — remove from fleet
                $fleet = Fleet::find($fs->fleet_id);
                $this->eventService->createEvent([
                    'user'       => $fleet?->user_id ?? 0,
                    'tick'       => $tick,
                    'event'      => 'techtree.level_down',
                    'area'       => 'techtree',
                    'parameters' => serialize([
                        'colony_id' => 0,
                        'tech_id'   => $fs->ship_id,
                    ]),
                ]);
                DB::table('fleet_ships')->where($where)->delete();
                $destroyed++;
            } else {
                DB::table('fleet_ships')->where($where)->update(['status_points' => $newStatus]);
            }
        }
        }); // end chunkById

        return $destroyed;
    }

    // ── 6. Research decay ────────────────────────────────────────────────────

    private function processResearchDecay(int $tick): int
    {
        $fallbackRate  = (float) config('game.decay.rate', 1);
        $overcapFactor = (float) config('game.decay.overcap_factor', 2.0);
        $decayRates    = DB::table('researches')->pluck('decay_rate', 'id');
        $maxSPMap      = DB::table('researches')->pluck('max_status_points', 'id');
        $levelled      = 0;

        // Build the over-cap set once before iterating — O(colonies), not O(researches).
        $overCapColonies = $this->resourcesService->getOverCapColonyIds();

        $researches = ColonyResearch::where('level', '>', 0)->get();

        foreach ($researches as $cr) {
            $rate        = (float) ($decayRates[$cr->research_id] ?? $fallbackRate);
            $overCapMult = in_array($cr->colony_id, $overCapColonies) ? $overcapFactor : 1.0;
            $newStatus   = (float) $cr->status_points - ($rate * $overCapMult);
            $where     = ['colony_id' => $cr->colony_id, 'research_id' => $cr->research_id];

            if ($newStatus <= 0) {
                $maxSP    = (int) ($maxSPMap[$cr->research_id] ?? 20);
                $newLevel = max(0, $cr->level - 1);

                DB::table('colony_researches')->where($where)->update([
                    'level'         => $newLevel,
                    'status_points' => $maxSP,
                ]);

                $colony = Colony::find($cr->colony_id);
                $this->eventService->createEvent([
                    'user'       => $colony?->user_id ?? 0,
                    'tick'       => $tick,
                    'event'      => 'techtree.level_down',
                    'area'       => 'techtree',
                    'parameters' => serialize([
                        'colony_id' => $cr->colony_id,
                        'tech_id'   => $cr->research_id,
                    ]),
                ]);
                $levelled++;
            } else {
                DB::table('colony_researches')->where($where)
                    ->update(['status_points' => $newStatus]);
            }
        }

        return $levelled;
    }

    // ── 7. Supply cap ────────────────────────────────────────────────────────

    /**
     * Recalculates and sets the supply cap for each user.
     *
     * Cap model (GDD §6):
     *   cap = CC_flat (15) + housing_level × 8,  max 200
     *
     * CommandCenter must be level > 0. Without CC → cap = 0.
     * The result is SET (not incremented) in user_resources.supply.
     */
    private function calculateSupply(): int
    {
        $capCC      = (int) config('buildings.commandCenter.supply_cap', 15);
        $capHousing = (int) config('buildings.housingComplex.supply_cap', 8);
        $capMax     = (int) config('game.supply.cap_max', 200);

        $userIds = Colony::whereNotNull('user_id')->distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            $colony = Colony::where('user_id', $userId)->first();
            if (!$colony) {
                continue;
            }

            $ccLevel = (int) DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', 25)
                ->value('level');

            if ($ccLevel <= 0) {
                UserResource::where('user_id', $userId)->update(['supply' => 0]);
                continue;
            }

            $housingLevel = (int) DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', 28)
                ->value('level');

            $cap = min($capCC + ($housingLevel * $capHousing), $capMax);

            UserResource::where('user_id', $userId)->update(['supply' => $cap]);
        }

        return $userIds->count();
    }

    // ── 8. Resource generation ───────────────────────────────────────────────

    private function generateResources(int $tick): int
    {
        $productionConfig = config('game.production', []);
        if (empty($productionConfig)) {
            $this->warn('  No production rates configured in config/game.php → skipping.');
            return 0;
        }

        $colonies = Colony::all();

        foreach ($colonies as $colony) {
            // Apply moral production multiplier based on the colony's CURRENT moral
            // (stored from the previous tick's moral calculation — no circular dependency).
            $moral      = $this->moralService->getMoral($colony->id);
            $multiplier = $this->moralService->getProductionMultiplier($moral);

            foreach ($productionConfig as $buildingId => $outputs) {
                $building = DB::table('colony_buildings')
                    ->where('colony_id', $colony->id)
                    ->where('building_id', $buildingId)
                    ->first();

                if (!$building || $building->level <= 0) {
                    continue;
                }

                foreach ($outputs as $resourceId => $ratePerLevel) {
                    $yield = (int) round($building->level * $ratePerLevel * $multiplier);
                    ColonyResource::where('colony_id', $colony->id)
                        ->where('resource_id', $resourceId)
                        ->update(['amount' => DB::raw("amount + {$yield}")]);
                }
            }
        }

        return $colonies->count();
    }

    // ── 8b. Moral calculation ─────────────────────────────────────────────────

    private function calculateMoral(int $tick): int
    {
        $colonies = Colony::all();

        foreach ($colonies as $colony) {
            $this->moralService->calculateAndStore($colony, $tick);
        }

        return $colonies->count();
    }

    // ── 9. Advisor ticks ─────────────────────────────────────────────────────

    private function incrementAdvisorTicks(): int
    {
        $updated = DB::table('advisors')
            ->whereNull('unavailable_until_tick')
            ->where(function ($q) {
                $q->whereNotNull('colony_id')
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('fleet_id')->where('is_commander', 1);
                  });
            })
            ->increment('active_ticks');

        $thresholds = config('game.advisor.rank_thresholds', [1 => 10, 2 => 20]);
        foreach ($thresholds as $fromRank => $ticks) {
            DB::table('advisors')
                ->where('rank', $fromRank)
                ->where('active_ticks', '>=', $ticks)
                ->update(['rank' => $fromRank + 1]);
        }

        return $updated;
    }
}
