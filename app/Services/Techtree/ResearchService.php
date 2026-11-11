<?php

namespace App\Services\Techtree;

use App\Enums\BuildingId;
use App\Services\AdvisorService;
use App\Services\DailyApCapService;
use App\Services\ProjectBonusService;
use App\Services\ResourcesService;
use App\Services\TickService;
use Illuminate\Support\Facades\DB;

/**
 * ResearchService — manages colony researches (legacy) and Kenntnisse (IDs 90–96).
 *
 * Kenntnisse use level-based AP costs from config/knowledge.php → levelup_costs.
 * Legacy researches use the flat ap_for_levelup value from the DB.
 */
class ResearchService extends AbstractTechnologyService
{
    /** True while investBonus() levels a knowledge up: waives the lab prerequisite. */
    private bool $silentResearch = false;

    public function __construct(
        TickService $tickService,
        ResourcesService $resourcesService,
        private readonly ProjectBonusService $projectBonusService,
        ?AdvisorService $advisorService = null,
    ) {
        parent::__construct($tickService, $resourcesService, $advisorService);
    }

    protected function levelupTrustEvent(): ?string
    {
        return 'research_level_up';
    }

    protected function masterTable(): string
    {
        return 'researches';
    }

    protected function colonyTable(): string
    {
        return 'colony_researches';
    }

    protected function costsTable(): ?string
    {
        return null;
    }

    protected function entityIdKey(): string
    {
        return 'research_id';
    }

    public static function idFor(string $key): int
    {
        return (int) config("researches.{$key}.id");
    }

    /**
     * Invest research points into a research (add AP, repair, or remove damage).
     */
    public function invest(int $colonyId, int $entityId, string $action = 'add', int $points = 1): bool
    {
        if (! $this->isCappedKnowledge($entityId, $action)) {
            return $this->_invest($colonyId, $entityId, $action, $points);
        }

        // Book what actually landed in ap_spend (invest clamps at the level threshold).
        return DB::transaction(function () use ($colonyId, $entityId, $points): bool {
            $before = (int) ($this->getColonyEntity($colonyId, $entityId)->ap_spend ?? 0);
            if (! $this->_invest($colonyId, $entityId, 'add', $points)) {
                return false;
            }
            $after = (int) ($this->getColonyEntity($colonyId, $entityId)->ap_spend ?? 0);
            app(DailyApCapService::class)->record($colonyId, self::capKey($entityId), $after - $before);

            return true;
        });
    }

    public static function capKey(int $entityId): string
    {
        return 'knowledge:'.$entityId;
    }

    /** Per-Sol AP cap per knowledge (game.knowledge_ap_cap_per_sol.base), null = rule off. */
    public function apCap(): ?int
    {
        $base = config('game.knowledge_ap_cap_per_sol.base');

        return $base === null ? null : (int) $base;
    }

    /** @return array{cap: int, spent: int, remaining: int}|null */
    public function apCapState(int $colonyId, int $entityId): ?array
    {
        return app(DailyApCapService::class)->state($colonyId, self::capKey($entityId), $this->apCap());
    }

    public function investBlocker(int $colonyId, int $entityId, string $action = 'add', int $points = 1, bool $bypassPoolCheck = false, ?int $instanceId = null): ?string
    {
        if (! $bypassPoolCheck && $this->isCappedKnowledge($entityId, $action)
            && app(DailyApCapService::class)->wouldExceed($colonyId, self::capKey($entityId), $this->apCap(), abs($points))) {
            return 'knowledge_ap_cap';
        }

        return parent::investBlocker($colonyId, $entityId, $action, $points, $bypassPoolCheck, $instanceId);
    }

    private function isCappedKnowledge(int $entityId, string $action): bool
    {
        return $action === 'add'
            && DB::table($this->masterTable())->where('id', $entityId)->value('purpose') === 'knowledge';
    }

    /**
     * Inject earmarked bonus AP into a knowledge ("stille Forschung", spec
     * 2026-11-11-tomas-rat-rueckmeldung): bypasses the shared-colony-pool gate and the
     * per-Sol lab cap, and levels the knowledge up on its own whenever the threshold is
     * reached — with or without a sciencelab.
     *
     * This is the official entry point for "bonus AP that never came out of the
     * player's shared AP pool" — Tomas' advice (BarService::talkToBartender()),
     * Sarka's concern, Deva's and Lenn's knowledge boost. Do not route these through
     * invest(): that locks pool AP the colony never had drawn.
     *
     * A knowledge that is not selectable (bonusTargetBlocker()) refuses the bonus and
     * stays untouched. AP beyond a threshold carry into the next level; if that level
     * is blocked, the rest is forfeited and the result names amount and reason.
     */
    public function investBonus(int $colonyId, int $entityId, int $points = 1): KnowledgeBonusResult
    {
        $key = self::knowledgeKey($entityId);
        $levelBefore = $this->knowledgeLevel($colonyId, $entityId);

        if (($blocker = $this->bonusTargetBlocker($colonyId, $entityId)) !== null) {
            return KnowledgeBonusResult::refused($entityId, $key, $levelBefore, $blocker);
        }

        return DB::transaction(function () use ($colonyId, $entityId, $key, $levelBefore, $points): KnowledgeBonusResult {
            $entity = DB::table($this->masterTable())->find($entityId);
            $remaining = max(0, $points);
            $invested = 0;
            $reason = null;

            while ($remaining > 0) {
                $level = $this->knowledgeLevel($colonyId, $entityId);
                $apSpend = (int) ($this->getColonyEntity($colonyId, $entityId)->ap_spend ?? 0);
                $threshold = $this->resolveApForLevelup($colonyId, $entityId, $entity);
                $need = max(0, $threshold - $apSpend);

                if ($need > $remaining) {
                    $this->writeProgress($colonyId, $entityId, $level, $apSpend + $remaining);
                    $invested += $remaining;
                    $remaining = 0;
                    break;
                }

                $this->writeProgress($colonyId, $entityId, $level, $apSpend + $need);
                $invested += $need;
                $remaining -= $need;

                if (! $this->silentLevelup($colonyId, $entityId)) {
                    $reason = $this->withSilentResearch(fn () => $this->levelupBlocker($colonyId, $entityId)) ?? 'order_failed';
                    break;
                }

                if ($remaining > 0 && ($reason = $this->bonusTargetBlocker($colonyId, $entityId)) !== null) {
                    break;
                }
            }

            return new KnowledgeBonusResult(
                accepted: true,
                knowledgeId: $entityId,
                knowledgeKey: $key,
                apInvested: $invested,
                levelBefore: $levelBefore,
                levelAfter: $this->knowledgeLevel($colonyId, $entityId),
                apForfeited: $reason !== null ? $remaining : 0,
                blockReason: $reason,
            );
        });
    }

    /**
     * Why earmarked bonus AP may not flow into this knowledge right now, or null when it
     * is selectable: every gate except the AP threshold itself (spec sentence 12). The
     * sciencelab prerequisite does not count — silent research needs no lab — but a
     * second techtree prerequisite (e.g. the hangar for cartography) does.
     *
     * @return string|null one of: entity_not_found, max_level, knowledge_cc_gate,
     *                     requires_building, insufficient_resources, insufficient_supply
     */
    public function bonusTargetBlocker(int $colonyId, int $entityId): ?string
    {
        $entity = DB::table($this->masterTable())->find($entityId);
        if (! $entity || ($entity->purpose ?? '') !== 'knowledge') {
            return 'entity_not_found';
        }

        $level = $this->knowledgeLevel($colonyId, $entityId);

        return match (true) {
            $level >= self::maxKnowledgeLevel($entityId) => 'max_level',
            $this->ccGateBlocks($colonyId, $level + 1) => 'knowledge_cc_gate',
            ! $this->secondPrerequisiteMet($colonyId, $entity) => 'requires_building',
            ! $this->checkRequiredResourcesByEntityId($colonyId, $entityId) => 'insufficient_resources',
            ! $this->checkRequiredSupplyByEntityId($colonyId, $entityId) => 'insufficient_supply',
            default => null,
        };
    }

    /**
     * Rough progress word index shown without a lab (spec A8): 1–3 by the bands in
     * game.knowledge_rough_progress_bands, null at no progress.
     */
    public function roughProgressBand(int $apSpend, int $threshold): ?int
    {
        if ($apSpend <= 0 || $threshold <= 0) {
            return null;
        }

        $fraction = $apSpend / $threshold;
        $band = 1;
        foreach (config('game.knowledge_rough_progress_bands', []) as $upperBound) {
            if ($fraction < (float) $upperBound) {
                return $band;
            }
            $band++;
        }

        return $band;
    }

    /** Highest knowledge level = highest target level in config/knowledge.php → levelup_costs. */
    public static function maxKnowledgeLevel(int $entityId): int
    {
        $costs = collect(config('knowledge'))->firstWhere('id', $entityId)['levelup_costs'] ?? [];

        return $costs === [] ? 0 : (int) max(array_keys($costs));
    }

    /** config/knowledge.php key of a knowledge id, null if none. */
    public static function knowledgeKey(int $entityId): ?string
    {
        foreach (config('knowledge', []) as $key => $def) {
            if (is_array($def) && (int) ($def['id'] ?? 0) === $entityId) {
                return (string) $key;
            }
        }

        return null;
    }

    /** The lab prerequisite is waived while a bonus levels a knowledge up (silent research). */
    public function checkRequiredBuildingsByEntityId(int $colonyId, int $entityId): bool
    {
        if (! $this->silentResearch) {
            return parent::checkRequiredBuildingsByEntityId($colonyId, $entityId);
        }

        $entity = DB::table($this->masterTable())->find($entityId);

        return ! is_object($entity) || $this->secondPrerequisiteMet($colonyId, $entity);
    }

    private function silentLevelup(int $colonyId, int $entityId): bool
    {
        return $this->withSilentResearch(fn () => $this->levelup($colonyId, $entityId));
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function withSilentResearch(callable $callback): mixed
    {
        $this->silentResearch = true;
        try {
            return $callback();
        } finally {
            $this->silentResearch = false;
        }
    }

    private function writeProgress(int $colonyId, int $entityId, int $level, int $apSpend): void
    {
        DB::table($this->colonyTable())->updateOrInsert(
            ['colony_id' => $colonyId, $this->entityIdKey() => $entityId],
            ['level' => $level, 'ap_spend' => $apSpend],
        );
    }

    private function knowledgeLevel(int $colonyId, int $entityId): int
    {
        return (int) ($this->getColonyEntity($colonyId, $entityId)->level ?? 0);
    }

    /** Second techtree prerequisite (required_building2_*), the one silent research keeps. */
    private function secondPrerequisiteMet(int $colonyId, object $entity): bool
    {
        if (empty($entity->required_building2_id)) {
            return true;
        }

        $level = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', (int) $entity->required_building2_id)
            ->max('level');

        return $level !== null && (int) $level >= (int) ($entity->required_building2_level ?? 1);
    }

    /** Whether the CommandCenter level is too low for a knowledge to reach $targetLevel. */
    private function ccGateBlocks(int $colonyId, int $targetLevel): bool
    {
        $caps = config('game.knowledge_cc_level_cap', []);
        if (! isset($caps[$targetLevel])) {
            return false;
        }

        $ccLevel = (int) (DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::CommandCenter->value)
            ->value('level') ?? 0);

        return $ccLevel < $caps[$targetLevel];
    }

    /**
     * Adds the knowledge CC-level gate (config/game.php → knowledge_cc_level_cap) on top
     * of the shared requirement checks: a colony must have its CommandCenter at the
     * required level before a Kenntnis may advance to the matching level.
     *
     * Checked first so the player is told about the CC, not about a downstream gate.
     * levelup() needs no override — the base class refuses whenever this returns a code.
     */
    public function levelupBlocker(int $colonyId, int $entityId, ?int $instanceId = null): ?string
    {
        $entity = DB::table($this->masterTable())->find($entityId);

        if ($entity && ($entity->purpose ?? '') === 'knowledge'
            && $this->ccGateBlocks($colonyId, $this->knowledgeLevel($colonyId, $entityId) + 1)) {
            return 'knowledge_cc_gate';
        }

        return parent::levelupBlocker($colonyId, $entityId, $instanceId);
    }

    /**
     * For Kenntnisse (purpose='knowledge'), AP cost varies per target level (config/knowledge.php).
     * Legacy researches use the flat DB value.
     */
    protected function resolveApForLevelup(int $colonyId, int $entityId, object $entity): int
    {
        if ($entity->purpose !== 'knowledge') {
            return (int) $entity->ap_for_levelup;
        }

        return $this->knowledgeLevelupCost($colonyId, $entityId, (int) $entity->ap_for_levelup);
    }

    /**
     * Public entry point for UI data-gathering (TechtreeColonyService/Controller):
     * the AP cost for a knowledge's NEXT level, read from config/knowledge.php's
     * per-level `levelup_costs` — never from the static `researches.ap_for_levelup`
     * DB column, which only ever holds the Lv0→1 seed value and is never kept in
     * sync as levels progress (playtest finding 2026-07-14: the techtree UI capped
     * every knowledge's progress bar at that stale value, so investment silently
     * stalled once the real, higher per-level cost was reached).
     */
    public function knowledgeLevelupCost(int $colonyId, int $entityId, int $fallback = 0): int
    {
        $currentLevel = (int) (DB::table($this->colonyTable())
            ->where('colony_id', $colonyId)
            ->where($this->entityIdKey(), $entityId)
            ->value('level') ?? 0);

        $targetLevel = $currentLevel + 1;
        $costs = collect(config('knowledge'))->firstWhere('id', $entityId)['levelup_costs'] ?? [];

        $rawCost = (int) ($costs[$targetLevel] ?? $fallback);

        return $this->projectBonusService->effectiveKnowledgeApForLevelup($colonyId, $rawCost);
    }
}
