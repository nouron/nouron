<?php

namespace App\Services\Techtree;

use Illuminate\Support\Facades\DB;

/**
 * ResearchService — manages colony researches (legacy) and Kenntnisse (IDs 90–96).
 *
 * Kenntnisse use level-based AP costs from config/knowledge.php → levelup_costs.
 * Legacy researches use the flat ap_for_levelup value from the DB.
 */
class ResearchService extends AbstractTechnologyService
{
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
        return $this->_invest('research_points', $colonyId, $entityId, $action, $points);
    }

    /**
     * Level up a research entity.
     *
     * For Kenntnisse (purpose='knowledge'), enforces the CC-level gate from
     * config/game.php → knowledge_cc_level_cap before delegating to the base class.
     * A colony must have its CommandCenter (building ID 25) at the required level
     * before a Kenntnis can advance to the corresponding level.
     */
    public function levelup(int $colonyId, int $entityId): bool
    {
        $entity = DB::table($this->masterTable())->find($entityId);

        if ($entity && ($entity->purpose ?? '') === 'knowledge') {
            $colonyEntity = $this->getColonyEntity($colonyId, $entityId);
            $currentLevel = $colonyEntity ? (int) $colonyEntity->level : 0;
            $targetLevel = $currentLevel + 1;

            $caps = config('game.knowledge_cc_level_cap', []);
            if (isset($caps[$targetLevel])) {
                $ccLevel = (int) (DB::table('colony_buildings')
                    ->where('colony_id', $colonyId)
                    ->where('building_id', 25)
                    ->value('level') ?? 0);

                if ($ccLevel < $caps[$targetLevel]) {
                    return false;
                }
            }
        }

        return parent::levelup($colonyId, $entityId);
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

        $currentLevel = (int) (DB::table($this->colonyTable())
            ->where('colony_id', $colonyId)
            ->where($this->entityIdKey(), $entityId)
            ->value('level') ?? 0);

        $targetLevel = $currentLevel + 1;
        $costs = collect(config('knowledge'))->firstWhere('id', $entityId)['levelup_costs'] ?? [];

        return (int) ($costs[$targetLevel] ?? $entity->ap_for_levelup);
    }
}
