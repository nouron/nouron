<?php

namespace App\Services;

use App\Models\Colony;
use App\Models\Fleet;
use App\Models\FleetOrder;
use App\Models\FleetPersonell;
use App\Models\FleetResearch;
use App\Models\FleetResource;
use App\Models\FleetShip;
use App\Models\GlxSystem;
use App\Models\GlxSystemObject;
use App\Services\Concerns\ValidatesId;
use App\Services\Techtree\PersonellService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * FleetService — Laravel port of Fleet\Service\FleetService.
 */
class FleetService
{
    use ValidatesId;

    public function __construct(
        private readonly ColonyService      $colonyService,
        private readonly GalaxyService      $galaxyService,
        private readonly TickService        $tickService,
        private readonly ?PersonellService  $personellService = null,
    ) {}

    // ── Read ─────────────────────────────────────────────────────────────────

    public function getFleet(int $fleetId): Fleet|false
    {
        $this->validateId($fleetId);
        return Fleet::find($fleetId) ?? false;
    }

    // ── Write ────────────────────────────────────────────────────────────────

    public function saveFleet(Fleet $fleet): bool
    {
        return $fleet->save();
    }

    public function saveFleetOrder(FleetOrder $order): void
    {
        DB::table('fleet_orders')->updateOrInsert(
            ['tick' => $order->tick, 'fleet_id' => $order->fleet_id],
            array_diff_key($order->getAttributes(), array_flip(['tick', 'fleet_id']))
        );
    }

    // ── Orders ────────────────────────────────────────────────────────────────

    public function getFleetOrdersByFleetIds(array $fleetIds): Collection
    {
        return FleetOrder::whereIn('fleet_id', $fleetIds)->get();
    }

    /**
     * Add a fleet order (move, trade, hold, etc.).
     * Delegates path-finding to GalaxyService::getPath().
     *
     * @param  int|Fleet         $fleet
     * @param  string            $order
     * @param  int|array|Colony|Fleet $destination
     * @param  array|null        $additionalData
     */
    public function addOrder(int|Fleet $fleet, string $order, mixed $destination, ?array $additionalData = null): void
    {
        $validOrders = ['move', 'trade', 'hold', 'convoy', 'defend', 'attack', 'join', 'divide'];
        if (!in_array($order, $validOrders)) {
            throw new \InvalidArgumentException("Unknown command: {$order}");
        }

        if (is_numeric($fleet)) {
            $fleetId = (int) $fleet;
            $fleet   = $this->getFleet($fleetId);
        } else {
            $fleetId = $fleet->id;
        }

        // Resolve destination to [x, y, spot] array
        if (!(is_array($destination) && array_keys($destination) === [0, 1, 2])) {
            if (is_numeric($destination)) {
                $destination = (int) $destination;
                if (in_array(strtolower($order), ['move', 'trade', 'hold'])) {
                    $obj = $this->colonyService->getColony($destination);
                } else {
                    $obj = $this->getFleet($destination);
                }
                $destination = $obj->getCoords();
            } elseif ($destination instanceof Colony || $destination instanceof Fleet) {
                $destination = $destination->getCoords();
            }
        }

        $coords      = $fleet->getCoords();
        $speed       = $this->calcFleetSpeed($fleetId);
        $currentTick = $this->tickService->getTickCount();
        $path        = $this->galaxyService->getPath($coords, $destination, $speed, $currentTick + 1);

        // Navigation-AP check: each tick in the path costs (order_cost) AP.
        // The final tick carries the actual $order; all preceding ticks are 'move'.
        // Total AP cost = order_cost_for_move * (pathLength - 1) + order_cost_for_$order * 1.
        $pathLength     = count($path);
        $moveCost       = (int) config('game.fleet.order_costs.move', 1);
        $finalOrderCost = (int) config('game.fleet.order_costs.' . $order, $moveCost);
        $apCost         = $moveCost * max(0, $pathLength - 1) + $finalOrderCost;

        // AP check and lock must be atomic to prevent TOCTOU race conditions.
        DB::transaction(function () use ($fleetId, $path, $order, $additionalData, $apCost) {
            if (config('game.bypass.ap_checks')) {
                Log::debug("FleetService::addOrder() AP check bypassed. "
                    . "fleet={$fleetId} order={$order} apCost={$apCost}");
            } else {
                $availableAP = $this->personellService?->getAvailableActionPoints('navigation', $fleetId) ?? PHP_INT_MAX;
                if ($availableAP < $apCost) {
                    throw new \RuntimeException("Nicht genug Navigations-AP für diesen Befehl.");
                }
                $this->personellService?->lockActionPoints('navigation', $fleetId, $apCost);
            }

            $this->_storePathInDb($fleetId, $path, $order, $additionalData);
        });
    }

    protected function _storePathInDb(int $fleetId, array $path, string $order, ?array $additionalData): void
    {
        DB::transaction(function () use ($fleetId, $path, $order, $additionalData) {
            $currentTick = $this->tickService->getTickCount();

            DB::table('fleet_orders')
                ->where('fleet_id', $fleetId)
                ->where('tick', '>=', $currentTick)
                ->delete();

            $i   = 1;
            $cnt = count($path);
            foreach ($path as $tickNr => $tmpCoords) {
                $tmpOrder = ($i === $cnt) ? $order : 'move';
                $row = [
                    'fleet_id'    => $fleetId,
                    'tick'        => $tickNr,
                    'order'       => strtolower($tmpOrder),
                    'coordinates' => json_encode($tmpCoords),
                ];
                if ($i === $cnt && $additionalData !== null) {
                    $row['data'] = json_encode($additionalData);
                }
                DB::table('fleet_orders')->insert($row);
                $i++;
            }
        });
    }

    // ── Transfer ──────────────────────────────────────────────────────────────

    public function transferShip(int|Colony $colony, int|Fleet $fleet, int $shipId, int $amount): int
    {
        return $this->transferTechnology('ship', $colony, $fleet, $shipId, $amount);
    }

    public function transferResearch(int|Colony $colony, int|Fleet $fleet, int $researchId, int $amount): int
    {
        return $this->transferTechnology('research', $colony, $fleet, $researchId, $amount);
    }

    public function transferPersonell(int|Colony $colony, int|Fleet $fleet, int $personellId, int $amount): int
    {
        return $this->transferTechnology('personell', $colony, $fleet, $personellId, $amount);
    }

    public function transferTechnology(string $type, int|Colony $colony, int|Fleet $fleet, int $techId, int $amount): int
    {
        if (is_numeric($colony)) {
            $colony = $this->colonyService->getColony((int) $colony);
        }
        if (is_numeric($fleet)) {
            $fleet = $this->getFleet((int) $fleet);
        }

        $type = strtolower($type);
        switch ($type) {
            case 'ship':
                $colonyTable = 'colony_ships';
                $fleetTable  = 'fleet_ships';
                $typeKey     = 'ship_id';
                break;
            case 'research':
                $colonyTable = 'colony_researches';
                $fleetTable  = 'fleet_researches';
                $typeKey     = 'research_id';
                break;
            case 'personell':
                $colonyTable = 'colony_personell';
                $fleetTable  = 'fleet_personell';
                $typeKey     = 'personell_id';
                break;
            default:
                throw new \InvalidArgumentException("Invalid type for transferTechnology: {$type}");
        }

        $colonyCoords = $colony->getCoords();
        $fleetCoords  = $fleet->getCoords();

        if ($colonyCoords[0] !== $fleetCoords[0] || $colonyCoords[1] !== $fleetCoords[1]) {
            return 0;
        }

        $colonyRow = DB::table($colonyTable)
            ->where('colony_id', $colony->id)
            ->where($typeKey, $techId)
            ->first();

        $colonyLevel = $colonyRow ? $colonyRow->level : 0;

        $fleetRow = DB::table($fleetTable)
            ->where('fleet_id', $fleet->id)
            ->where($typeKey, $techId)
            ->where('is_cargo', 0)
            ->first();

        $fleetCount = $fleetRow ? $fleetRow->count : 0;

        // Clamp amount to available units
        if ($amount >= 0) {
            if ($amount > $colonyLevel) {
                $amount = $colonyLevel;
            }
        } else {
            if ($amount < -$fleetCount) {
                $amount = -$fleetCount;
            }
        }

        DB::transaction(function () use (
            $colonyTable, $fleetTable, $typeKey, $techId, $colony, $fleet,
            $colonyLevel, $fleetCount, $amount, $type
        ) {
            $newColonyLevel = $colonyLevel - $amount;
            $newFleetCount  = $fleetCount + $amount;

            // Update colony side
            DB::table($colonyTable)->updateOrInsert(
                ['colony_id' => $colony->id, $typeKey => $techId],
                ['level' => $newColonyLevel]
            );

            // Update fleet side
            DB::table($fleetTable)->updateOrInsert(
                ['fleet_id' => $fleet->id, $typeKey => $techId, 'is_cargo' => 0],
                ['count' => $newFleetCount]
            );
        });

        return abs($amount);
    }

    public function transferResource(int|Colony $colony, int|Fleet $fleet, int $resId, int $amount): int
    {
        if (is_numeric($fleet)) {
            $fleet = $this->getFleet((int) $fleet);
        }

        $colonyCoords = $colony->getCoords();
        $fleetCoords  = $fleet->getCoords();
        if ($colonyCoords[0] !== $fleetCoords[0] || $colonyCoords[1] !== $fleetCoords[1]) {
            return 0;
        }

        $colonyRes = DB::table('colony_resources')
            ->where('colony_id', $colony->id)
            ->where('resource_id', $resId)
            ->first();

        if (!$colonyRes) {
            return 0;
        }

        $fleetResRow = DB::table('fleet_resources')
            ->where('fleet_id', $fleet->id)
            ->where('resource_id', $resId)
            ->first();

        $colonyAmount = $colonyRes->amount;
        $fleetAmount  = $fleetResRow ? $fleetResRow->amount : 0;

        if ($amount >= 0) {
            if ($amount > $colonyAmount) {
                $amount = $colonyAmount;
            }
        } else {
            if ($fleetResRow && $amount < -$fleetAmount) {
                $amount = -$fleetAmount;
            }
        }

        DB::transaction(function () use ($colony, $fleet, $resId, $colonyAmount, $fleetAmount, $amount) {
            DB::table('colony_resources')->updateOrInsert(
                ['colony_id' => $colony->id, 'resource_id' => $resId],
                ['amount' => $colonyAmount - $amount]
            );
            DB::table('fleet_resources')->updateOrInsert(
                ['fleet_id' => $fleet->id, 'resource_id' => $resId],
                ['amount' => $fleetAmount + $amount]
            );
        });

        return abs($amount);
    }

    // ── Fleet tech getters ────────────────────────────────────────────────────

    public function getFleetShip(array $key, bool $forceResultEntity = false): FleetShip|false
    {
        $result = FleetShip::where($key)->first();
        if (!$result && $forceResultEntity) {
            $result = new FleetShip(['fleet_id' => $key['fleet_id'], 'ship_id' => $key['ship_id'], 'count' => 0, 'is_cargo' => 0]);
        }
        return $result ?? false;
    }

    public function getFleetResearch(array $key, bool $forceResultEntity = false): FleetResearch|false
    {
        $result = FleetResearch::where($key)->first();
        if (!$result && $forceResultEntity) {
            $result = new FleetResearch(['fleet_id' => $key['fleet_id'], 'research_id' => $key['research_id'], 'count' => 0, 'is_cargo' => 0]);
        }
        return $result ?? false;
    }

    public function getFleetShips(array $where): Collection
    {
        return FleetShip::where($where)->get();
    }

    public function getFleetShipsByFleetId(int $fleetId, ?bool $isCargo = null): Collection
    {
        $this->validateId($fleetId);
        $query = FleetShip::where('fleet_id', $fleetId);
        if ($isCargo !== null) {
            $query->where('is_cargo', (int) $isCargo);
        }
        return $query->get();
    }

    public function getFleetResearches(array $where): Collection
    {
        return FleetResearch::where($where)->get();
    }

    public function getFleetResearchesByFleetId(int $fleetId, ?bool $isCargo = null): Collection
    {
        $this->validateId($fleetId);
        $query = FleetResearch::where('fleet_id', $fleetId);
        if ($isCargo !== null) {
            $query->where('is_cargo', (int) $isCargo);
        }
        return $query->get();
    }

    public function getFleetPersonell(array $where): Collection
    {
        return FleetPersonell::where($where)->get();
    }

    public function getFleetPersonellByFleetId(int $fleetId, ?bool $isCargo = null): Collection
    {
        $this->validateId($fleetId);
        $query = FleetPersonell::where('fleet_id', $fleetId);
        if ($isCargo !== null) {
            $query->where('is_cargo', (int) $isCargo);
        }
        return $query->get();
    }

    public function getFleetResources(array $where): Collection
    {
        return FleetResource::where($where)->get();
    }

    public function getFleetResourcesByFleetId(int $fleetId): Collection
    {
        $this->validateId($fleetId);
        return $this->getFleetResources(['fleet_id' => $fleetId]);
    }

    public function getFleetResource(array $key, bool $forceResultEntity = false): FleetResource|false
    {
        $result = FleetResource::where($key)->first();
        if (!$result && $forceResultEntity) {
            $result = new FleetResource(['fleet_id' => $key['fleet_id'], 'resource_id' => $key['resource_id'], 'amount' => 0]);
        }
        return $result ?? false;
    }

    // ── Orders / Fleet queries ────────────────────────────────────────────────

    public function getOrders(?array $where = null, ?string $orderBy = null, ?int $limit = null, ?int $offset = null): Collection
    {
        $query = FleetOrder::query();
        if ($where) {
            $query->where($where);
        }
        $allowedOrderBy = ['tick', 'fleet_id', 'order', 'was_processed'];
        if ($orderBy && in_array($orderBy, $allowedOrderBy, true)) {
            $query->orderBy($orderBy);
        }
        if ($offset) {
            $query->skip($offset);
        }
        if ($limit) {
            $query->take($limit);
        }
        return $query->get();
    }

    public function getFleetsByUserId(int $userId): Collection
    {
        $this->validateId($userId);
        return Fleet::where('user_id', $userId)->get();
    }

    public function getFleetsByEntityId(string $entityType, int $id): Collection
    {
        $this->validateId($id);

        $entity = match (strtolower($entityType)) {
            'colony' => Colony::find($id),
            'object' => GlxSystemObject::find($id),
            'system' => GlxSystem::find($id),
            default  => null,
        };

        if (!$entity) {
            return collect();
        }

        return $this->getFleetsByCoords([$entity->x, $entity->y]);
    }

    public function getFleetsByCoords(array $coords): Collection
    {
        [$x, $y] = $coords;
        if (!is_numeric($x) || !is_numeric($y)) {
            throw new InvalidArgumentException('Invalid coordinates.');
        }
        return Fleet::where('x', $x)->where('y', $y)->get();
    }

    // ── Technologies ──────────────────────────────────────────────────────────

    public function getFleetTechnologies(int $fleetId): array
    {
        $this->validateId($fleetId);

        return [
            'research'  => $this->_gatherFleetTechnologyInformations($fleetId, 'research'),
            'ship'      => $this->_gatherFleetTechnologyInformations($fleetId, 'ship'),
            'personell' => $this->_gatherFleetTechnologyInformations($fleetId, 'personell'),
        ];
    }

    /**
     * Calculate the effective movement speed of a fleet.
     *
     * The fleet moves at the speed of its slowest ship (min moving_speed).
     * Falls back to 1 if the fleet has no ships or all ships have null moving_speed.
     */
    private function calcFleetSpeed(int $fleetId): int
    {
        $minSpeed = DB::table('fleet_ships')
            ->join('ships', 'ships.id', '=', 'fleet_ships.ship_id')
            ->where('fleet_ships.fleet_id', $fleetId)
            ->where('fleet_ships.is_cargo', 0)
            ->whereNotNull('ships.moving_speed')
            ->min('ships.moving_speed');

        return max(1, (int) $minSpeed);
    }

    private function _gatherFleetTechnologyInformations(int $fleetId, string $type): array
    {
        switch (strtolower($type)) {
            case 'research':
                $masterTable = 'researches';
                $fleetTable  = 'fleet_researches';
                $typeKey     = 'research_id';
                break;
            case 'ship':
                $masterTable = 'ships';
                $fleetTable  = 'fleet_ships';
                $typeKey     = 'ship_id';
                break;
            case 'personell':
                $masterTable = 'personell';
                $fleetTable  = 'fleet_personell';
                $typeKey     = 'personell_id';
                break;
            default:
                return [];
        }

        $entities = DB::table($masterTable)->get()->keyBy('id')
            ->map(fn($e) => (array) $e)
            ->toArray();

        $fleetEntities = DB::table($fleetTable)
            ->where('fleet_id', $fleetId)
            ->get()
            ->keyBy($typeKey)
            ->map(fn($e) => (array) $e)
            ->toArray();

        foreach ($entities as $id => $entity) {
            if (array_key_exists($id, $fleetEntities)) {
                $entities[$id] = array_merge($entity, $fleetEntities[$id]);
            } else {
                $entities[$id]['count']         = 0;
                $entities[$id]['status_points'] = 0;
                $entities[$id]['ap_spend']      = 0;
            }
        }

        return $entities;
    }
}
