<?php

namespace Tests\Unit;

/**
 * HarvesterEntitlementService unit tests.
 *
 * GDD §4c "Harvester-Zweitinstanz: Bezugsquelle" (freigegeben 2026-08-05) — the second
 * Harvester instance is no longer a deterministic Regolith buy. It requires an
 * entitlement earned via Weg A (Orin/CorporateContactService, purchase) or Weg B
 * (mission_harvester_salvage reward). This service is the single source of truth for
 * "has the player earned it, and via which path" — no dedicated DB table, backed by
 * OnboardingTriggerService's existing per-user one-shot-flag store.
 *
 * Covered scenarios:
 *   - test_has_entitlement_is_false_by_default
 *   - test_grant_purchase_sets_entitlement
 *   - test_grant_salvage_sets_entitlement
 *   - test_is_salvage_sourced_is_false_by_default
 *   - test_is_salvage_sourced_is_true_after_grant_salvage
 *   - test_is_salvage_sourced_is_false_after_grant_purchase_only
 *   - test_grants_are_idempotent
 *   - test_has_purchase_entitlement_is_false_by_default
 *   - test_has_purchase_entitlement_is_true_after_grant_purchase
 *   - test_has_purchase_entitlement_is_false_after_grant_salvage_only
 */

use App\Services\HarvesterEntitlementService;
use App\Services\OnboardingTriggerService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HarvesterEntitlementServiceTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3; // Bart

    private HarvesterEntitlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = new HarvesterEntitlementService($this->app->make(OnboardingTriggerService::class));
    }

    public function test_has_entitlement_is_false_by_default(): void
    {
        $this->assertFalse($this->service->hasEntitlement(self::USER_ID));
    }

    public function test_grant_purchase_sets_entitlement(): void
    {
        $this->service->grantPurchase(self::USER_ID);

        $this->assertTrue($this->service->hasEntitlement(self::USER_ID));
    }

    public function test_grant_salvage_sets_entitlement(): void
    {
        $this->service->grantSalvage(self::USER_ID);

        $this->assertTrue($this->service->hasEntitlement(self::USER_ID));
    }

    public function test_is_salvage_sourced_is_false_by_default(): void
    {
        $this->assertFalse($this->service->isSalvageSourced(self::USER_ID));
    }

    public function test_is_salvage_sourced_is_true_after_grant_salvage(): void
    {
        $this->service->grantSalvage(self::USER_ID);

        $this->assertTrue($this->service->isSalvageSourced(self::USER_ID));
    }

    public function test_is_salvage_sourced_is_false_after_grant_purchase_only(): void
    {
        $this->service->grantPurchase(self::USER_ID);

        $this->assertFalse($this->service->isSalvageSourced(self::USER_ID));
    }

    public function test_grants_are_idempotent(): void
    {
        $this->service->grantPurchase(self::USER_ID);
        $this->service->grantPurchase(self::USER_ID);

        $this->assertTrue($this->service->hasEntitlement(self::USER_ID));
    }

    public function test_has_purchase_entitlement_is_false_by_default(): void
    {
        $this->assertFalse($this->service->hasPurchaseEntitlement(self::USER_ID));
    }

    public function test_has_purchase_entitlement_is_true_after_grant_purchase(): void
    {
        $this->service->grantPurchase(self::USER_ID);

        $this->assertTrue($this->service->hasPurchaseEntitlement(self::USER_ID));
    }

    public function test_has_purchase_entitlement_is_false_after_grant_salvage_only(): void
    {
        $this->service->grantSalvage(self::USER_ID);

        $this->assertFalse($this->service->hasPurchaseEntitlement(self::USER_ID));
    }
}
