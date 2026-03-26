<?php

namespace App\Console\Commands;

use App\Models\Colony;
use App\Models\ColonyBuilding;
use App\Models\ColonyResource;
use App\Models\Fleet;
use App\Models\FleetOrder;
use App\Models\FleetResource;
use App\Models\FleetShip;
use App\Models\UserResource;
use App\Services\EventService;
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
 *  4. Building decay      — decrement status_points; level-down at 0
 *  5. Supply generation   — add supply to users based on commandCenter + housingComplex levels
 *  6. Resource generation — produce colony resources per industry building level
 */
class GameTick extends Command
{
    protected $signature   = 'game:tick {--tick= : Override the tick number (default: current tick)}';
    protected $description = 'Process one game tick (fleet orders, decay, supply, resources)';

    public function __construct(
        private readonly TickService  $tickService,
        private readonly EventService $eventService,
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
            $this->line("  Fleet move orders:   {$n}");

            $n = $this->processTradeOrders($tick);
            $this->line("  Fleet trade orders:  {$n}");

            $n = $this->processCombatOrders($tick);
            $this->line("  Fleet combat orders: {$n}");

            $n = $this->processDecay();
            $this->line("  Buildings decayed:   {$n}");

            $n = $this->calculateSupply();
            $this->line("  Users supply updated: {$n}");

            $n = $this->generateResources();
            $this->line("  Colonies with resources generated: {$n}");
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

            // direction 0 = Kauf: colony sends resources to fleet
            // direction 1 = Verkauf: fleet sends resources to colony
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
        ColonyResource::where('colony_id', $colonyId)
            ->where('resource_id', $resourceId)
            ->update(['amount' => DB::raw("MAX(0, amount - {$amount})")]);

        $fleetRes = FleetResource::firstOrCreate(
            ['fleet_id' => $fleetId, 'resource_id' => $resourceId],
            ['amount'   => 0],
        );
        $fleetRes->increment('amount', $amount);
        if ($fleetRes->amount < 0) {
            $fleetRes->update(['amount' => 0]);
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

            // Move attacker to target position first
            $attacker = Fleet::find($order->fleet_id);
            if (!$attacker) {
                continue;
            }

            Fleet::where('id', $attacker->id)->update([
                'x' => $coords[0], 'y' => $coords[1], 'spot' => $coords[2],
            ]);

            // Find defending fleets at same coordinates, belonging to other users
            $defenders = Fleet::where('x', $coords[0])
                ->where('y', $coords[1])
                ->where('spot', $coords[2])
                ->where('user_id', '!=', $attacker->user_id)
                ->where('id', '!=', $attacker->id)
                ->get();

            if ($defenders->isEmpty()) {
                // No defenders — attacker arrives unchallenged
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

            // Calculate combat powers
            $attackerPower = $this->calcFleetPower($attacker->id, $shipPower);

            // Sum all defending fleets into one power value
            $defenderPower = 0;
            $defenderFleetIds = $defenders->pluck('id')->all();
            foreach ($defenderFleetIds as $defId) {
                $defenderPower += $this->calcFleetPower($defId, $shipPower);
            }

            $totalPower = $attackerPower + $defenderPower;
            if ($totalPower === 0) {
                // No combat-capable ships on either side
                $order->update(['was_processed' => 1]);
                $processed++;
                continue;
            }

            // Loss ratios: each side loses ships proportional to the opponent's share of total power
            $attackerLossRatio = $defenderPower / $totalPower;
            $defenderLossRatio = $attackerPower / $totalPower;

            $this->applyShipLosses($attacker->id, $attackerLossRatio, $shipPower);
            foreach ($defenderFleetIds as $defId) {
                $this->applyShipLosses($defId, $defenderLossRatio, $shipPower);
            }

            $order->update(['was_processed' => 1]);

            // Notify both sides
            $defenderUserId = $defenders->first()->user_id;
            $this->eventService->createEvent([
                'user'       => $attacker->user_id,
                'tick'       => $tick,
                'event'      => 'galaxy.combat',
                'area'       => 'galaxy',
                'parameters' => serialize([
                    'attacker_id' => $attacker->user_id,
                    'defender_id' => $defenderUserId,
                    'colony_id'   => 0,
                ]),
            ]);
            $this->eventService->createEvent([
                'user'       => $defenderUserId,
                'tick'       => $tick,
                'event'      => 'galaxy.combat',
                'area'       => 'galaxy',
                'parameters' => serialize([
                    'attacker_id' => $attacker->user_id,
                    'defender_id' => $defenderUserId,
                    'colony_id'   => 0,
                ]),
            ]);

            $processed++;
        }

        return $processed;
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
            $power = $shipPower[$ship->ship_id] ?? 0;
            if ($power === 0) {
                continue; // non-combat ships not destroyed in battle
            }
            $losses = (int) ceil($ship->count * $lossRatio);
            $remaining = max(0, $ship->count - $losses);
            if ($remaining <= 0) {
                $ship->delete();
            } else {
                $ship->update(['count' => $remaining]);
            }
        }
    }

    // ── 4. Building decay ────────────────────────────────────────────────────

    private function processDecay(): int
    {
        $rate    = (int) config('game.decay.rate', 1);
        $decayed = 0;

        $buildings = ColonyBuilding::where('level', '>', 0)->get();

        foreach ($buildings as $cb) {
            $newStatus = $cb->status_points - $rate;
            $where = ['colony_id' => $cb->colony_id, 'building_id' => $cb->building_id];

            if ($newStatus <= 0) {
                // Level-down: building loses one level, status_points reset to max
                $maxStatus = DB::table('buildings')
                    ->where('id', $cb->building_id)
                    ->value('max_status_points') ?? 10;

                $newLevel = max(0, $cb->level - 1);
                DB::table('colony_buildings')->where($where)->update([
                    'level'         => $newLevel,
                    'status_points' => $maxStatus,
                ]);

                $colony = Colony::find($cb->colony_id);
                $this->eventService->createEvent([
                    'user'       => $colony?->user_id ?? 0,
                    'tick'       => $this->tickService->getTickCount(),
                    'event'      => 'techtree.level_down',
                    'area'       => 'techtree',
                    'parameters' => serialize([
                        'colony_id' => $cb->colony_id,
                        'tech_id'   => $cb->building_id,
                    ]),
                ]);
                $decayed++;
            } else {
                DB::table('colony_buildings')->where($where)
                    ->update(['status_points' => $newStatus]);
            }
        }

        return $decayed;
    }

    // ── 5. Supply generation ─────────────────────────────────────────────────

    private function calculateSupply(): int
    {
        $ccRate      = (int) config('game.supply.commandcenter_rate', 5);
        $housingRate = (int) config('game.supply.housingcomplex_rate', 10);

        // Get all users that have at least one colony
        $userIds = Colony::whereNotNull('user_id')->distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            $colonyIds = Colony::where('user_id', $userId)->pluck('id');

            $ccTotal = DB::table('colony_buildings')
                ->whereIn('colony_id', $colonyIds)
                ->where('building_id', 25) // commandCenter
                ->sum('level');

            $housingTotal = DB::table('colony_buildings')
                ->whereIn('colony_id', $colonyIds)
                ->where('building_id', 28) // housingComplex
                ->sum('level');

            $supplyGain = ($ccTotal * $ccRate) + ($housingTotal * $housingRate);

            UserResource::where('user_id', $userId)
                ->increment('supply', $supplyGain);
        }

        return $userIds->count();
    }

    // ── 6. Resource generation ───────────────────────────────────────────────

    private function generateResources(): int
    {
        $productionConfig = config('game.production', []);
        if (empty($productionConfig)) {
            $this->warn('  No production rates configured in config/game.php → skipping.');
            return 0;
        }

        $colonies = Colony::all();

        foreach ($colonies as $colony) {
            foreach ($productionConfig as $buildingId => $outputs) {
                $building = DB::table('colony_buildings')
                    ->where('colony_id', $colony->id)
                    ->where('building_id', $buildingId)
                    ->first();

                if (!$building || $building->level <= 0) {
                    continue;
                }

                foreach ($outputs as $resourceId => $ratePerLevel) {
                    $yield = $building->level * $ratePerLevel;
                    ColonyResource::where('colony_id', $colony->id)
                        ->where('resource_id', $resourceId)
                        ->update(['amount' => DB::raw("amount + {$yield}")]);
                }
            }
        }

        return $colonies->count();
    }
}
