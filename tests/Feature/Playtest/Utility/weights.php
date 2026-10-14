<?php

/**
 * Utility weights of the playtest bot (spec 2.9 + P0). Test harness, not game
 * values — therefore not in config/. Every value is an assumption to be
 * calibrated by measurement (plan Task 22). Profile-specific values are derived
 * from the BotProfile dials in BotProfile::weights(); `profiles` holds explicit
 * overrides (array_replace_recursive) and is empty in v1.
 */
return [
    'base' => [
        'gamma' => 0.95,
        'horizon_max' => 40,
        'lambda_ap_start' => 0.5,
        'lambda_ap_ema' => 0.3,
        // Review S3 I5: cap of the marginal density fed into λ (best regular density, geology Lv1 ≈ 2.5, spec 2.5).
        'lambda_ap_max' => 3.0,
        'price' => ['credits' => 0.04, 'organics' => 1.0, 'compounds' => 3.0, 'trust' => 2.0],
        'scarcity_clamp' => [0.5, 4.0],
        'trust_danger_margin' => 10,
        'trust_danger_factor' => 4.0,
        'w_phase1' => 40.0,
        'phase1_urgency_from_sol' => 15,
        'phase1_urgency_step' => 0.2,
        // Owner 2026-10-12: the bot must want path building levels 2 and 3 (and CC 4/5).
        'w_tier' => 30.0,
        // Structural steps (CC level, path building placement) count cost-neutral in v1.
        'structural_offset' => 1.0,
        'objective_base' => 150.0,
        'opening_bias' => 30.0,
        'opening_bias_until_sol' => 15,
        'info_zone_tile' => 8.0,
        'research_floor' => 1.0,
        'build_ap_per_sol' => 10,
        'nav_ap_per_sol' => 4,
        'income_floor_rg' => 8,
        'goal_max_wait_sols' => 5,
        'min_net' => 0.01,
        'risk_aversion' => 0.0,
    ],
    'profiles' => [
        'default' => [],
        'thrifty' => [],
        'focus' => [],
        'eager' => [],
    ],
];
