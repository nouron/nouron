<?php

namespace Tests\Unit;

use App\Services\EncounterService;
use Tests\TestCase;

class EncounterServiceTest extends TestCase
{
    public function test_high_sp_resolves_to_abgewehrt(): void
    {
        $service = new EncounterService;
        // 80% SP (≥66% threshold) — Abgewehrt, minimal/no loss.
        $result = $service->resolveOutcome(statusPoints: 16, maxStatusPoints: 20, securityHubActive: false);

        $this->assertSame('abgewehrt', $result['tier']);
        $this->assertSame('encounter_won', $result['trust_event']);
        $this->assertSame(16, $result['sp_after']);
        $this->assertFalse($result['forces_level_down']);
    }

    public function test_mid_sp_resolves_to_beschaedigt_with_20pct_loss(): void
    {
        $service = new EncounterService;
        // 50% SP (33-65% band) — Beschädigt, loses 20% of max (4 of 20).
        $result = $service->resolveOutcome(statusPoints: 10, maxStatusPoints: 20, securityHubActive: false);

        $this->assertSame('beschaedigt', $result['tier']);
        $this->assertSame('encounter_lost', $result['trust_event']);
        $this->assertSame(6, $result['sp_after']);
        $this->assertFalse($result['forces_level_down']);
    }

    public function test_low_sp_resolves_to_kritisch_and_forces_level_down(): void
    {
        $service = new EncounterService;
        // 20% SP (<33%) — Kritisch, forces a level-down.
        $result = $service->resolveOutcome(statusPoints: 4, maxStatusPoints: 20, securityHubActive: false);

        $this->assertSame('kritisch', $result['tier']);
        $this->assertSame('colony_threatened', $result['trust_event']);
        $this->assertTrue($result['forces_level_down']);
    }

    public function test_security_hub_dampens_beschaedigt_sp_loss_by_25_percent(): void
    {
        $service = new EncounterService;
        // Without hub: loses 4 (20% of 20). With hub: loses round(4 * 0.75) = 3.
        $result = $service->resolveOutcome(statusPoints: 10, maxStatusPoints: 20, securityHubActive: true);

        $this->assertSame(7, $result['sp_after']);
    }

    public function test_boundary_at_exactly_66_percent_is_abgewehrt(): void
    {
        $service = new EncounterService;
        $result = $service->resolveOutcome(statusPoints: 13, maxStatusPoints: 20, securityHubActive: false); // exactly 65%... below threshold

        // 13/20 = 0.65, below the 0.66 threshold → Beschädigt, not Abgewehrt.
        $this->assertSame('beschaedigt', $result['tier']);
    }

    public function test_boundary_at_exactly_33_percent_is_beschaedigt_not_kritisch(): void
    {
        $service = new EncounterService;
        $result = $service->resolveOutcome(statusPoints: 7, maxStatusPoints: 20, securityHubActive: false); // 7/20 = 0.35

        $this->assertSame('beschaedigt', $result['tier']);
    }
}
