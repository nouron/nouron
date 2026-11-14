<?php

namespace App\Services\Techtree;

use App\Enums\BuildingId;
use Illuminate\Support\Facades\DB;

/**
 * JSON payloads around earmarked knowledge bonus AP ("stille Forschung", spec
 * 2026-11-11-tomas-rat-rueckmeldung): the result line after a bonus and the knowledge
 * choice list shared by Tomas, Sarka and Deva. With a sciencelab the progress is exact;
 * without one only a rough word (game.knowledge_rough_progress_bands) — the reached
 * level and its effect are always exact.
 */
class KnowledgeBonusPresenter
{
    public function __construct(
        private readonly ResearchService $researchService,
        private readonly KnowledgeEffectDescriptionService $effectDescriptions,
    ) {}

    /**
     * @param  array<string, mixed>  $result  KnowledgeBonusResult::toArray() shape
     * @return array<string, mixed>
     */
    public function present(int $colonyId, array $result): array
    {
        $knowledgeId = (int) ($result['knowledge_id'] ?? 0);
        $key = $result['knowledge_key'] ?? ResearchService::knowledgeKey($knowledgeId);
        $hasLab = $this->hasSciencelab($colonyId);
        $progress = $this->progress($colonyId, $knowledgeId);
        $blockReason = $result['block_reason'] ?? null;

        return $result + [
            'knowledge_name' => $key !== null ? __("knowledge.{$key}") : null,
            'has_lab' => $hasLab,
            'ap_spend' => $hasLab ? $progress['ap_spend'] : null,
            'ap_for_levelup' => $hasLab ? $progress['ap_for_levelup'] : null,
            'rough_progress' => $progress['rough_progress'],
            'rough_progress_label' => $progress['rough_progress_label'],
            'effects' => ! empty($result['leveled_up']) && $key !== null
                ? $this->effectDescriptions->effectsAtLevel($key, (int) ($result['level'] ?? 0))
                : [],
            'block_label' => $blockReason !== null ? $this->blockLabel($blockReason) : null,
        ];
    }

    /**
     * Every knowledge as a choice for a bonus: selectable or "wartet auf <Grund>".
     *
     * @return list<array{id: int, key: string, name: string, selectable: bool, blocked_reason: string|null, blocked_label: string|null}>
     */
    public function options(int $colonyId): array
    {
        $options = [];
        foreach (config('knowledge', []) as $key => $def) {
            if (! is_array($def) || ! isset($def['id'])) {
                continue;
            }
            $blocker = $this->researchService->bonusTargetBlocker($colonyId, (int) $def['id']);
            $options[] = [
                'id' => (int) $def['id'],
                'key' => (string) $key,
                'name' => __("knowledge.{$key}"),
                'selectable' => $blocker === null,
                'blocked_reason' => $blocker,
                'blocked_label' => $blocker !== null ? $this->blockLabel($blocker) : null,
            ];
        }

        return $options;
    }

    /**
     * Current progress of a knowledge: exact numbers plus the rough word index.
     *
     * @return array{ap_spend: int, ap_for_levelup: int, rough_progress: int|null, rough_progress_label: string|null}
     */
    public function progress(int $colonyId, int $knowledgeId): array
    {
        $apSpend = (int) ($this->researchService->getColonyEntity($colonyId, $knowledgeId)->ap_spend ?? 0);
        $threshold = $this->researchService->knowledgeLevelupCost($colonyId, $knowledgeId);
        $band = $this->researchService->roughProgressBand($apSpend, $threshold);

        return [
            'ap_spend' => $apSpend,
            'ap_for_levelup' => $threshold,
            'rough_progress' => $band,
            'rough_progress_label' => $band !== null ? __("knowledge.rough_progress_{$band}") : null,
        ];
    }

    /**
     * Exact knowledge state for the admin debug bar: lets the owner check silent research
     * (level-ups without a lab) that players only see as a rough word. Admin-only caller.
     *
     * @return list<array{id: int, key: string, name: string, level: int, ap_spend: int, ap_for_levelup: int, max_level: int, blocker: string|null, blocker_label: string|null}>
     */
    public function debugRows(int $colonyId): array
    {
        $rows = [];
        foreach (config('knowledge', []) as $key => $def) {
            if (! is_array($def) || ! isset($def['id'])) {
                continue;
            }
            $id = (int) $def['id'];
            $entity = $this->researchService->getColonyEntity($colonyId, $id);
            $blocker = $this->researchService->bonusTargetBlocker($colonyId, $id);
            $rows[] = [
                'id' => $id,
                'key' => (string) $key,
                'name' => __("knowledge.{$key}"),
                'level' => (int) ($entity->level ?? 0),
                'ap_spend' => (int) ($entity->ap_spend ?? 0),
                'ap_for_levelup' => $this->researchService->knowledgeLevelupCost($colonyId, $id),
                'max_level' => ResearchService::maxKnowledgeLevel($id),
                'blocker' => $blocker,
                'blocker_label' => $blocker !== null ? $this->blockLabel($blocker) : null,
            ];
        }

        return $rows;
    }

    public function hasSciencelab(int $colonyId): bool
    {
        return DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::Sciencelab->value)
            ->where('level', '>', 0)
            ->exists();
    }

    private function blockLabel(string $reason): string
    {
        return __("knowledge.bonus_wait_{$reason}");
    }
}
