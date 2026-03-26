<?php

namespace App\Console\Commands;

use App\Models\Colony;
use App\Models\ColonyResource;
use App\Models\Fleet;
use App\Models\FleetOrder;
use App\Models\FleetResource;
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
 * Steps:
 *  1. Fleet move orders  — update fleet position to ordered coordinates
 *  2. Fleet trade orders — transfer resources between fleet and colony
 *  3. Resource generation — produce resources on every colony (per building level)
 */
class GameTick extends Command
{
    protected $signature   = 'game:tick {--tick= : Override the tick number (default: current tick)}';
    protected $description = 'Process one game tick (fleet orders + resource generation)';

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
            $processed = $this->processMoveOrders($tick);
            $this->line("  Fleet move orders processed: {$processed}");

            $processed = $this->processTradeOrders($tick);
            $this->line("  Fleet trade orders processed: {$processed}");

            $generated = $this->generateResources();
            $this->line("  Colonies with resource generation: {$generated}");
        });

        $this->info("Tick {$tick} done.");
        return self::SUCCESS;
    }

    // ── Fleet: move orders ────────────────────────────────────────────────────

    private function processMoveOrders(int $tick): int
    {
        $orders = FleetOrder::where('tick', $tick)
            ->where('order', 'move')
            ->where('was_processed', 0)
            ->get();

        foreach ($orders as $order) {
            $coords = json_decode($order->coordinates, true);
            if (!is_array($coords) || count($coords) < 3) {
                $this->warn("  Fleet {$order->fleet_id}: invalid coordinates '{$order->coordinates}', skipping.");
                continue;
            }

            Fleet::where('id', $order->fleet_id)->update([
                'x'    => $coords[0],
                'y'    => $coords[1],
                'spot' => $coords[2],
            ]);

            $order->update(['was_processed' => 1]);

            $this->eventService->createEvent([
                'user'       => Fleet::find($order->fleet_id)?->user_id ?? 0,
                'tick'       => $tick,
                'event'      => 'galaxy.fleet_arrived',
                'area'       => 'galaxy',
                'parameters' => serialize([
                    'fleet_id' => $order->fleet_id,
                    'coords'   => $coords,
                ]),
            ]);
        }

        return $orders->count();
    }

    // ── Fleet: trade orders ───────────────────────────────────────────────────

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

            $colonyId   = (int) ($data['colony_id'] ?? $data['colony'] ?? 0);
            $resourceId = (int) ($data['resource_id'] ?? 0);
            $amount     = (int) ($data['amount'] ?? 0);
            $direction  = (int) ($data['direction'] ?? 0); // 0=buy(colony→fleet), 1=sell(fleet→colony)

            if (!$colonyId || !$resourceId || !$amount) {
                $this->warn("  Fleet {$order->fleet_id}: incomplete trade data, skipping.");
                continue;
            }

            // direction 0 = Kauf: colony gives resources to fleet
            // direction 1 = Verkauf: fleet gives resources to colony
            if ($direction === 0) {
                $this->transferResource($colonyId, $order->fleet_id, $resourceId, $amount);
            } else {
                $this->transferResource($colonyId, $order->fleet_id, $resourceId, -$amount);
            }

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
        // Subtract from colony (or add if amount is negative)
        ColonyResource::where('colony_id', $colonyId)
            ->where('resource_id', $resourceId)
            ->update(['amount' => DB::raw("MAX(0, amount - {$amount})")]);

        // Add to fleet (or subtract if amount is negative)
        $fleetRes = FleetResource::firstOrCreate(
            ['fleet_id' => $fleetId, 'resource_id' => $resourceId],
            ['amount' => 0],
        );
        $fleetRes->increment('amount', $amount);
        if ($fleetRes->amount < 0) {
            $fleetRes->update(['amount' => 0]);
        }
    }

    // ── Resource generation ───────────────────────────────────────────────────

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
