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
    private ?array $resourceNameMap  = null;

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
            'id'       => $entry->id,
            'sol'      => $entry->tick,
            'event'    => $entry->event,
            'area'     => $entry->area,
            'params'   => $params,
            'segments' => $this->buildDescription($entry->event, $params),
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

    /**
     * Returns a segment array representing a rich description for the given event.
     * Each segment is either ['type'=>'text','value'=>'...'] or an entity segment.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildDescription(string $event, array $params): array
    {
        return match ($event) {
            'colony.building_placed'     => $this->descBuildingPlaced($params),
            'colony.building_invested'   => $this->descBuildingInvested($params),
            'colony.building_repaired'   => $this->descBuildingRepaired($params),
            'colony.tile_explored'       => [$this->seg(__('comm_log.desc.tile_explored'))],
            'colony.tile_deep_scanned'   => $this->descTileDeepScanned($params),
            'colony.renamed'             => [$this->seg(__('comm_log.desc.colony_renamed'))],
            'techtree.level_up_finished' => $this->descLevelUp($params),
            'techtree.level_down'        => $this->descLevelDown($params),
            'techtree.advisor_hired'     => $this->descAdvisorHired($params),
            'trade.bar_accepted'         => $this->descBarAccepted($params),
            'trade.merchant_purchase'    => [$this->seg(__('comm_log.desc.merchant_purchase'))],
            'merchant.visit'             => [$this->seg(__('comm_log.desc.merchant_visit'))],
            'galaxy.fleet_arrived'       => [$this->seg(__('comm_log.desc.fleet_arrived'))],
            'galaxy.trade'               => $this->descGalaxyTrade($params),
            'galaxy.encounter'           => [$this->seg(__('comm_log.desc.encounter'))],
            default                      => [],
        };
    }

    /** Text segment shorthand. */
    private function seg(string $text): array
    {
        return ['type' => 'text', 'value' => $text];
    }

    /**
     * Entity segment for a typed game entity.
     *
     * @param array<string, mixed> $tooltip
     * @return array<string, mixed>
     */
    private function entitySeg(string $type, string $key, string $label, array $tooltip = []): array
    {
        return [
            'type'    => $type,
            'key'     => $key,
            'label'   => $label,
            'tooltip' => $tooltip,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function descBuildingPlaced(array $params): array
    {
        $key  = $params['building_name'] ?? null;
        $id   = $params['building_id'] ?? null;
        $intId = $id !== null ? (int) $id : null;
        $name = $this->resolveBuildingName($intId, $key);
        $bKey = $key ?? ($intId !== null ? ($this->getBuildingNameMap()[$intId] ?? (string) $intId) : '?');

        // lang: comm_log.desc.building_placed = ':name platziert.'
        return [
            $this->entitySeg('building', (string) $bKey, $name, [
                'level' => null,
                'link'  => '/nexus-db',
            ]),
            $this->seg(' platziert.'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function descBuildingInvested(array $params): array
    {
        $id    = $params['building_id'] ?? null;
        $key   = $params['building_name'] ?? null;
        $intId = $id !== null ? (int) $id : null;
        $name  = $this->resolveBuildingName($intId, $key);
        $bKey  = $key ?? ($intId !== null ? ($this->getBuildingNameMap()[$intId] ?? (string) $intId) : '?');
        $ap       = (int) ($params['ap_spend'] ?? 1);
        $apNeeded = (int) ($params['ap_for_levelup'] ?? 0);
        $levelUp  = (bool) ($params['level_up'] ?? false);
        $newLevel = (int) ($params['new_level'] ?? 0);

        if ($levelUp) {
            return [
                $this->seg($ap . ' AP in '),
                $this->entitySeg('building', (string) $bKey, $name, [
                    'level' => $newLevel,
                    'link'  => '/nexus-db',
                ]),
                $this->seg(' investiert. Bau abgeschlossen — Level ' . $newLevel . ' erreicht.'),
            ];
        }

        $done  = $apNeeded > 0 ? ($apNeeded - $ap) : '?';
        $total = $apNeeded ?: '?';

        return [
            $this->seg($ap . ' AP in '),
            $this->entitySeg('building', (string) $bKey, $name, [
                'level' => null,
                'link'  => '/nexus-db',
            ]),
            $this->seg(' investiert (' . $done . ' / ' . $total . ' AP).'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function descBuildingRepaired(array $params): array
    {
        $id    = $params['building_id'] ?? null;
        $key   = $params['building_name'] ?? null;
        $intId = $id !== null ? (int) $id : null;
        $name  = $this->resolveBuildingName($intId, $key);
        $bKey  = $key ?? ($intId !== null ? ($this->getBuildingNameMap()[$intId] ?? (string) $intId) : '?');
        $sp    = (int) ($params['status_points'] ?? 0);
        $maxSp = (int) ($params['max_status_points'] ?? 0);

        // lang: comm_log.desc.building_repaired = ':name repariert (:current / :max Zustand).'
        return [
            $this->entitySeg('building', (string) $bKey, $name, [
                'level' => null,
                'link'  => '/nexus-db',
            ]),
            $this->seg(' repariert (' . $sp . ' / ' . ($maxSp ?: '?') . ' Zustand).'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function descLevelUp(array $params): array
    {
        $entityName = $params['entity_name'] ?? null;
        $entityType = $params['entity_type'] ?? null;
        $techId     = $params['tech_id'] ?? null;

        if (!$entityType && $entityName) {
            $entityType = str_starts_with($entityName, 'knowledge_') ? 'knowledge'
                : (str_starts_with($entityName, 'building_') ? 'building' : 'research');
        }

        $resolvedType = $entityType ?? 'building';
        $name         = $this->resolveEntityName($entityName, $resolvedType, $techId);
        $level        = isset($params['new_level']) ? (int) $params['new_level'] : null;

        $chipType = match ($resolvedType) {
            'knowledge' => 'knowledge',
            'ship'      => 'ship',
            'research'  => 'research',
            default     => 'building',
        };

        $link = match ($chipType) {
            'knowledge', 'research' => '/nexus-db',
            'ship'                  => '/nexus-db',
            default                 => '/nexus-db',
        };

        $entityKey = $entityName ?? (string) $techId;

        if ($chipType === 'knowledge') {
            $prefix = 'Kenntnis ';
            $suffix = $level !== null ? ' auf Level ' . $level . ' gestiegen.' : ' erworben.';
        } else {
            $prefix = 'Forschung ';
            $suffix = $level !== null ? ' auf Level ' . $level . ' gestiegen.' : ' abgeschlossen.';
        }

        return [
            $this->seg($prefix),
            $this->entitySeg($chipType, (string) $entityKey, $name, [
                'level' => $level,
                'link'  => $link,
            ]),
            $this->seg($suffix),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function descTileDeepScanned(array $params): array
    {
        $q = $params['q'] ?? null;
        $r = $params['r'] ?? null;

        if ($q !== null && $r !== null) {
            return [$this->seg(__('comm_log.desc.tile_deep_scanned_coords', ['q' => $q, 'r' => $r]))];
        }

        return [$this->seg(__('comm_log.desc.tile_deep_scanned'))];
    }

    /** @return array<int, array<string, mixed>> */
    private function descBarAccepted(array $params): array
    {
        $giveResId  = $params['give_resource_id'] ?? null;
        $giveAmount = (int) ($params['give_amount'] ?? 0);
        $getResId   = $params['get_resource_id'] ?? null;
        $getAmount  = (int) ($params['get_amount'] ?? 0);

        if ($giveResId !== null && $getResId !== null) {
            $giveKey   = $this->resolveResourceKey((int) $giveResId);
            $giveLabel = $this->resolveResourceName((int) $giveResId);
            $getKey    = $this->resolveResourceKey((int) $getResId);
            $getLabel  = $this->resolveResourceName((int) $getResId);

            return [
                $this->seg($giveAmount . ' '),
                $this->entitySeg('resource', $giveKey, $giveLabel, []),
                $this->seg(' gegen ' . $getAmount . ' '),
                $this->entitySeg('resource', $getKey, $getLabel, []),
                $this->seg(' getauscht.'),
            ];
        }

        return [$this->seg(__('comm_log.desc.bar_accepted'))];
    }

    /** @return array<int, array<string, mixed>> */
    private function descGalaxyTrade(array $params): array
    {
        $credits = (int) ($params['credits_earned'] ?? 0);

        if ($credits > 0) {
            return [$this->seg(__('comm_log.desc.galaxy_trade_credits', ['credits' => $credits]))];
        }

        return [$this->seg(__('comm_log.desc.galaxy_trade'))];
    }

    /** @return array<int, array<string, mixed>> */
    private function descLevelDown(array $params): array
    {
        $entityType = $params['entity_type'] ?? 'building';
        $newLevel   = isset($params['new_level']) ? (int) $params['new_level'] : null;
        $entityName = $params['entity_name'] ?? null;
        $techId     = $params['tech_id'] ?? null;
        $name       = $this->resolveEntityName($entityName, $entityType, $techId);

        $entityKey = $entityName ?? (string) $techId;

        if ($entityType === 'ship') {
            return [
                $this->seg('Schiff '),
                $this->entitySeg('ship', (string) $entityKey, $name, [
                    'level' => null,
                    'link'  => '/nexus-db',
                ]),
                $this->seg(' durch Verfall zerstört.'),
            ];
        }

        $chipType = ($entityType === 'knowledge') ? 'knowledge' : 'building';
        $link     = '/nexus-db';
        $suffix   = $newLevel !== null
            ? ' mangels Wartung auf ' . $newLevel . ' gesunken.'
            : ' mangels Wartung gesunken.';

        return [
            $this->seg('Level für '),
            $this->entitySeg($chipType, (string) $entityKey, $name, [
                'level' => $newLevel,
                'link'  => $link,
            ]),
            $this->seg($suffix),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function descAdvisorHired(array $params): array
    {
        $type     = $params['advisor_type'] ?? '';
        $typeName = $type ? __('techtree.' . $type) : $type;
        if (!$typeName || $typeName === 'techtree.' . $type) {
            $typeName = $type ? __('advisors.' . $type) : $type;
        }
        $cost = (int) ($params['credits_cost'] ?? 0);

        if ($cost > 0) {
            return [
                $this->entitySeg('advisor', $type, (string) $typeName, [
                    'link' => '/nexus-db',
                ]),
                $this->seg(' eingestellt. Kosten: ' . $cost . ' CR.'),
            ];
        }

        return [
            $this->entitySeg('advisor', $type, (string) $typeName, [
                'link' => '/nexus-db',
            ]),
            $this->seg(' eingestellt.'),
        ];
    }

    /**
     * Resolves the internal resource key (e.g. "res_regolith") for a resource ID.
     * Falls back to a stringified ID if the DB row or lang key cannot be found.
     */
    private function resolveResourceKey(int $resId): string
    {
        if ($this->resourceNameMap === null) {
            $this->resourceNameMap = DB::table('resources')->pluck('name', 'id')->all();
        }
        return $this->resourceNameMap[$resId] ?? 'res_' . $resId;
    }

    private function resolveResourceName(int $resId): string
    {
        $key = $this->resolveResourceKey($resId);
        if ($key) {
            $translated = __('resources.' . $key);
            if ($translated !== 'resources.' . $key) {
                return $translated;
            }
        }
        return (string) $resId;
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
