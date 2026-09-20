<?php

namespace App\Services\Techtree;

use App\Enums\BuildingId;
use App\Services\AdvisorService;
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
    public function __construct(
        TickService $tickService,
        ResourcesService $resourcesService,
        private readonly ProjectBonusService $projectBonusService,
        ?AdvisorService $advisorService = null,
    ) {
        parent::__construct($tickService, $resourcesService, $advisorService);
    }

    protected function masterTable(): string
    {
        return 'researches';
    }

    protected function colonyTable(): string
    {
        return 'colony_researches';
    }

    protected function costsTable(): string
    {
        return 'research_costs';
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
        return $this->_invest($colonyId, $entityId, $action, $points);
    }

    /**
     * Inject earmarked bonus AP directly into a knowledge's ap_spend, bypassing the
     * shared-colony-pool availability gate that invest() normally runs.
     *
     * This is the official entry point for "bonus AP that never came out of the
     * player's shared AP pool" — e.g. Cantina-Barkeeper Tomas' interaction-tier
     * bonus (A40, BarService::talkToBartender()), a character concern's one-off
     * knowledge boost (A41, Sarka), or a character's knowledge-progress-boost trait
     * (A42, Deva/Lenn). Such sources are zweckgebunden: they are earned outside the
     * pool and must never fail or evaporate just because the pool happens to be at
     * or near 0 when the player triggers them. Do not route these through invest()
     * with a manual pre-check instead — that still leaves the pool-locking side
     * effect in place, which would incorrectly debit AP the colony never had drawn.
     *
     * Only the 'add' semantics apply here (ap_spend toward the next levelup) — this
     * mirrors invest($action = 'add') but skips both the pool-availability gate and
     * the pool-locking side effect, since the AP was never in the pool to begin with.
     */
    public function investBonus(int $colonyId, int $entityId, int $points = 1): bool
    {
        return $this->_invest($colonyId, $entityId, 'add', $points, bypassPoolCheck: true);
    }

    /**
     * Adds the knowledge CC-level gate (config/game.php → knowledge_cc_level_cap) on top
     * of the shared requirement checks: a colony must have its CommandCenter at the
     * required level before a Kenntnis may advance to the matching level.
     *
     * Checked first so the player is told about the CC, not about a downstream gate.
     * levelup() needs no override — the base class refuses whenever this returns a code.
     */
    public function levelupBlocker(int $colonyId, int $entityId): ?string
    {
        $entity = DB::table($this->masterTable())->find($entityId);

        if ($entity && ($entity->purpose ?? '') === 'knowledge') {
            $colonyEntity = $this->getColonyEntity($colonyId, $entityId);
            $targetLevel = ($colonyEntity ? (int) $colonyEntity->level : 0) + 1;

            $caps = config('game.knowledge_cc_level_cap', []);
            if (isset($caps[$targetLevel])) {
                $ccLevel = (int) (DB::table('colony_buildings')
                    ->where('colony_id', $colonyId)
                    ->where('building_id', BuildingId::CommandCenter->value)
                    ->value('level') ?? 0);

                if ($ccLevel < $caps[$targetLevel]) {
                    return 'knowledge_cc_gate';
                }
            }
        }

        return parent::levelupBlocker($colonyId, $entityId);
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
