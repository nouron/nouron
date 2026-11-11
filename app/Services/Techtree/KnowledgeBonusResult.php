<?php

namespace App\Services\Techtree;

/**
 * Outcome of ResearchService::investBonus() ("stille Forschung").
 *
 * accepted=false: nothing changed, $blockReason says why the knowledge is not selectable.
 * accepted=true:  $apInvested landed (progress and/or level-ups); a non-null $blockReason
 *                 then names why $apForfeited AP could not carry into the next level.
 */
final readonly class KnowledgeBonusResult
{
    public function __construct(
        public bool $accepted,
        public int $knowledgeId,
        public ?string $knowledgeKey,
        public int $apInvested,
        public int $levelBefore,
        public int $levelAfter,
        public int $apForfeited,
        public ?string $blockReason,
    ) {}

    public static function refused(int $knowledgeId, ?string $knowledgeKey, int $level, string $reason): self
    {
        return new self(false, $knowledgeId, $knowledgeKey, 0, $level, $level, 0, $reason);
    }

    public function leveledUp(): bool
    {
        return $this->levelAfter > $this->levelBefore;
    }

    /**
     * @return array{accepted: bool, knowledge_id: int, knowledge_key: string|null, ap_invested: int,
     *     level_before: int, level: int, leveled_up: bool, ap_forfeited: int, block_reason: string|null}
     */
    public function toArray(): array
    {
        return [
            'accepted' => $this->accepted,
            'knowledge_id' => $this->knowledgeId,
            'knowledge_key' => $this->knowledgeKey,
            'ap_invested' => $this->apInvested,
            'level_before' => $this->levelBefore,
            'level' => $this->levelAfter,
            'leveled_up' => $this->leveledUp(),
            'ap_forfeited' => $this->apForfeited,
            'block_reason' => $this->blockReason,
        ];
    }
}
