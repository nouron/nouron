<?php

namespace App\Services;

/**
 * HarvesterEntitlementService — single source of truth for "has the player earned the
 * second Harvester instance, and via which path" (GDD §4c "Harvester-Zweitinstanz:
 * Bezugsquelle", freigegeben 2026-08-05).
 *
 * The old CC-Lv3 + 100 Regolith path is gone: instance 2 is no longer a deterministic
 * buy, it requires an opportunistic entitlement earned via one of two independent,
 * not-guaranteed paths — Weg A (Orin/CorporateContactService, purchase for 400-800 Cr)
 * or Weg B (mission_harvester_salvage reward). Neither is a resource type, so both are
 * stored as one-shot per-user flags via the existing OnboardingTriggerService rather
 * than a dedicated table (single-colony-per-user scope, see project_singleplayer_scope).
 */
class HarvesterEntitlementService
{
    public const TRIGGER_PURCHASE = 'harvester_second_instance_unlocked_purchase';

    public const TRIGGER_SALVAGE = 'harvester_second_instance_unlocked_salvage';

    public function __construct(
        private readonly OnboardingTriggerService $onboardingTriggerService,
    ) {}

    /** Whether the player has earned the second Harvester instance via any path. */
    public function hasEntitlement(int $userId): bool
    {
        return $this->onboardingTriggerService->hasFired($userId, self::TRIGGER_PURCHASE)
            || $this->onboardingTriggerService->hasFired($userId, self::TRIGGER_SALVAGE);
    }

    /** Grants the entitlement via Weg A (Orin's purchase). Idempotent. */
    public function grantPurchase(int $userId): void
    {
        $this->onboardingTriggerService->markFired($userId, self::TRIGGER_PURCHASE);
    }

    /** Grants the entitlement via Weg B (mission_harvester_salvage reward). Idempotent. */
    public function grantSalvage(int $userId): void
    {
        $this->onboardingTriggerService->markFired($userId, self::TRIGGER_SALVAGE);
    }

    /**
     * True when the earned entitlement includes the salvage path — the second
     * instance must then arrive damaged (config('game.harvester.salvage_arrival_sp_pct'))
     * instead of at full health.
     */
    public function isSalvageSourced(int $userId): bool
    {
        return $this->onboardingTriggerService->hasFired($userId, self::TRIGGER_SALVAGE);
    }

    /**
     * True when the earned entitlement includes the purchase path (Weg A, Orin).
     * Used to give purchase precedence over salvage when both are somehow present
     * (both earned before either is consumed by placement) — a paid-for instance
     * must never be downgraded by an also-earned salvage entitlement.
     */
    public function hasPurchaseEntitlement(int $userId): bool
    {
        return $this->onboardingTriggerService->hasFired($userId, self::TRIGGER_PURCHASE);
    }
}
