<?php

namespace App\Http\Controllers\CommLog;

use App\Http\Controllers\BaseController;
use App\Models\ColonyLog;
use App\Services\EventService;
use App\Services\TickService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CommLogController extends BaseController
{
    private const NEXUS_EVENT_KEYS = [
        'run.nexus_warning_sol30',
        'run.nexus_warning_sol50',
        'run.nexus_sanction_sol65',
        'run.nexus_countdown_sol80',
        'run.run_completed',
        'run.run_failed_trust',
        'run.run_failed_nexus_debt',
        'run.run_failed_time',
        'run.phase1_complete',
    ];

    private const EXCLUDED_FROM_LOG = [
        'run.sol_advanced',
    ];

    private ?array $buildingNameMap  = null;
    private ?array $shipNameMap      = null;
    private ?array $researchNameMap  = null;

    public function __construct(
        TickService $tick,
        private readonly EventService $eventService,
    ) {
        parent::__construct($tick);
    }

    public function log(): View
    {
        $userId    = Auth::id();
        $nexusKeys = self::NEXUS_EVENT_KEYS;
        $excluded  = self::EXCLUDED_FROM_LOG;

        $entries = ColonyLog::where('user', $userId)
            ->where('area', '!=', 'nexus')
            ->whereNotIn('event', $nexusKeys)
            ->whereNotIn('event', $excluded)
            ->where('event', 'not like', 'onboarding%')
            ->orderByDesc('tick')
            ->orderByDesc('id')
            ->get()
            ->map(fn($e) => $this->decorate($e));

        $unreadCount = $this->eventService->countUnreadNexus($userId);

        return view('comm_log.index', [
            'tab'         => 'log',
            'entries'     => $entries,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function nexus(): View
    {
        $userId    = Auth::id();
        $nexusKeys = self::NEXUS_EVENT_KEYS;

        $entries = ColonyLog::where('user', $userId)
            ->where(function ($q) use ($nexusKeys) {
                $q->where('area', 'nexus')
                  ->orWhereIn('event', $nexusKeys);
            })
            ->orderByDesc('tick')
            ->orderByDesc('id')
            ->get()
            ->map(fn($e) => $this->decorate($e));

        $this->eventService->markNexusRead($userId);

        return view('comm_log.index', [
            'tab'         => 'nexus',
            'entries'     => $entries,
            'unreadCount' => 0,
        ]);
    }

    private function decorate(ColonyLog $entry): array
    {
        $params = $this->parseParams($entry->parameters);

        return [
            'id'          => $entry->id,
            'sol'         => $entry->tick,
            'event'       => $entry->event,
            'area'        => $entry->area,
            'params'      => $params,
            'description' => $this->buildDescription($entry->event, $params),
        ];
    }

    private function parseParams(?string $raw): array
    {
        if (empty($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Legacy PHP-serialized format from pre-migration data
        $unserialized = @unserialize($raw);
        if (is_array($unserialized)) {
            return $unserialized;
        }

        return [];
    }

    private function buildDescription(string $event, array $params): string
    {
        return match ($event) {
            'colony.building_placed'   => $this->descBuildingPlaced($params),
            'colony.building_invested' => $this->descBuildingInvested($params),
            'colony.tile_explored'     => __('comm_log.desc.tile_explored'),
            'colony.tile_deep_scanned' => __('comm_log.desc.tile_deep_scanned'),
            'colony.renamed'           => __('comm_log.desc.colony_renamed'),
            'techtree.level_up_finished' => $this->descLevelUp($params),
            'techtree.level_down'        => $this->descLevelDown($params),
            'techtree.advisor_hired'     => $this->descAdvisorHired($params),
            'trade.bar_accepted'         => __('comm_log.desc.bar_accepted'),
            'trade.merchant_purchase'    => __('comm_log.desc.merchant_purchase'),
            'merchant.visit'             => __('comm_log.desc.merchant_visit'),
            'galaxy.fleet_arrived'       => __('comm_log.desc.fleet_arrived'),
            'galaxy.trade'               => __('comm_log.desc.galaxy_trade'),
            'galaxy.encounter'           => __('comm_log.desc.encounter'),
            default                      => '',
        };
    }

    private function descBuildingPlaced(array $params): string
    {
        $name = $this->resolveBuildingName($params['building_id'] ?? null, $params['building_name'] ?? null);
        return __('comm_log.desc.building_placed', ['name' => $name]);
    }

    private function descBuildingInvested(array $params): string
    {
        $name     = $this->resolveBuildingName($params['building_id'] ?? null, $params['building_name'] ?? null);
        $ap       = (int) ($params['ap_spend'] ?? 1);
        $apNeeded = (int) ($params['ap_for_levelup'] ?? 0);
        $levelUp  = (bool) ($params['level_up'] ?? false);
        $newLevel = (int) ($params['new_level'] ?? 0);

        if ($levelUp) {
            return __('comm_log.desc.building_leveled_up', ['ap' => $ap, 'name' => $name, 'level' => $newLevel]);
        }

        return __('comm_log.desc.building_invested', [
            'ap'    => $ap,
            'name'  => $name,
            'done'  => $apNeeded > 0 ? ($apNeeded - $ap) : '?',
            'total' => $apNeeded ?: '?',
        ]);
    }

    private function descLevelUp(array $params): string
    {
        $name = $this->resolveEntityName(
            $params['entity_name'] ?? null,
            $params['entity_type'] ?? 'building',
            $params['tech_id'] ?? null,
        );
        return __('comm_log.desc.level_up', ['name' => $name]);
    }

    private function descLevelDown(array $params): string
    {
        $entityType = $params['entity_type'] ?? 'building';
        $newLevel   = isset($params['new_level']) ? (int) $params['new_level'] : null;
        $name       = $this->resolveEntityName(
            $params['entity_name'] ?? null,
            $entityType,
            $params['tech_id'] ?? null,
        );

        if ($entityType === 'ship') {
            return __('comm_log.desc.level_down_ship', ['name' => $name]);
        }

        if ($newLevel !== null) {
            return __('comm_log.desc.level_down_level', ['name' => $name, 'level' => $newLevel]);
        }

        return __('comm_log.desc.level_down', ['name' => $name]);
    }

    private function descAdvisorHired(array $params): string
    {
        $type        = $params['advisor_type'] ?? '';
        $typeName    = $type ? __('advisors.' . $type, [], 'de') : $type;
        return __('comm_log.desc.advisor_hired', ['type' => $typeName]);
    }

    private function resolveBuildingName(?int $buildingId, ?string $nameKey): string
    {
        if ($nameKey) {
            $translated = __('techtree.' . $nameKey);
            if ($translated !== 'techtree.' . $nameKey) {
                return $translated;
            }
        }

        if ($buildingId !== null) {
            $map = $this->getBuildingNameMap();
            $key = $map[$buildingId] ?? null;
            if ($key) {
                $translated = __('techtree.' . $key);
                if ($translated !== 'techtree.' . $key) {
                    return $translated;
                }
                return $key;
            }
        }

        return (string) ($buildingId ?? '?');
    }

    private function resolveEntityName(?string $entityName, string $entityType, mixed $techId): string
    {
        if ($entityName) {
            $translated = __('techtree.' . $entityName);
            if ($translated !== 'techtree.' . $entityName) {
                return $translated;
            }
            return $entityName;
        }

        if ($techId !== null) {
            $id  = (int) $techId;
            $map = match ($entityType) {
                'ship'     => $this->getShipNameMap(),
                'research' => $this->getResearchNameMap(),
                default    => $this->getBuildingNameMap(),
            };
            $key = $map[$id] ?? null;
            if ($key) {
                $translated = __('techtree.' . $key);
                if ($translated !== 'techtree.' . $key) {
                    return $translated;
                }
                return $key;
            }

            // Unknown entity_type — try all three tables
            foreach ([$this->getBuildingNameMap(), $this->getShipNameMap(), $this->getResearchNameMap()] as $fallbackMap) {
                $key = $fallbackMap[$id] ?? null;
                if ($key) {
                    $translated = __('techtree.' . $key);
                    return $translated !== 'techtree.' . $key ? $translated : $key;
                }
            }

            return (string) $id;
        }

        return '?';
    }

    private function getBuildingNameMap(): array
    {
        if ($this->buildingNameMap === null) {
            $this->buildingNameMap = DB::table('buildings')->pluck('name', 'id')->all();
        }

        return $this->buildingNameMap;
    }

    private function getShipNameMap(): array
    {
        if ($this->shipNameMap === null) {
            $this->shipNameMap = DB::table('ships')->pluck('name', 'id')->all();
        }

        return $this->shipNameMap;
    }

    private function getResearchNameMap(): array
    {
        if ($this->researchNameMap === null) {
            $this->researchNameMap = DB::table('researches')->pluck('name', 'id')->all();
        }

        return $this->researchNameMap;
    }
}
