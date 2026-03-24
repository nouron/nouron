<?php

namespace App\Services\Techtree;

/**
 * ResearchService — manages colony researches.
 *
 * Researches require research AP (scientists) to be invested before a
 * levelup can be triggered.
 *
 * Migrated from Techtree\Service\ResearchService (Laminas).
 */
class ResearchService extends AbstractTechnologyService
{
    protected function masterTable(): string  { return 'researches'; }
    protected function colonyTable(): string  { return 'colony_researches'; }
    protected function costsTable(): string   { return 'research_costs'; }
    protected function entityIdKey(): string  { return 'research_id'; }

    /**
     * Invest research points into a research (add AP, repair, or remove damage).
     */
    public function invest(int $colonyId, int $entityId, string $action = 'add', int $points = 1): bool
    {
        return $this->_invest('research_points', $colonyId, $entityId, $action, $points);
    }
}
