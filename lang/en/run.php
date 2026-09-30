<?php

return [
    'phase1_complete' => 'Phase 1 complete — the stabilisation phase is reached. Nexus activates the next set of directives.',
    'run_completed' => 'Mission accomplished — the expedition is concluded. The colony endures, despite everything.',
    'run_failed_trust' => 'Recalled — the colonists\' trust has crumbled. Command is relieved.',
    'run_failed_time' => 'Time expired — the concession period has lapsed. The Nexus withdraws the Director.',
    'run_failed_phase1_deadline' => 'Deadline breach — Phase 1 was not completed in time. The Nexus terminates the concession with immediate effect.',
    'task_senior_advisors' => 'Expert Staff: :target advisors at rank :min_rank or higher',
    'task_credit_reserve' => 'Credit Reserve: hold at least :threshold Credits for :target Sols in a row',
    'task_colony_prosperity' => 'Colony Prosperity: keep Trust above :threshold for :target Sols in a row',
    'task_research_lead' => 'Research Lead: :target knowledge fields at level :min_level or higher',
    'task_self_sufficiency' => 'Self-Sufficiency: keep Regolith above :regolith_min, Organics above :organics_min for :target Sols in a row',
    'task_expedition_coverage' => 'Expeditions: :target successful outside missions at difficulty ":min_difficulty" or higher',
    'task_engineering_output' => 'Engineering Output: combined upgrade levels of all buildings at least :target',
    'task_trade_volume' => 'Trade Partner: :target purchases from the Travelling Merchant',
    'nexus_warning_sol30' => 'Nexus Command: Checkpoint 1 of 3 missed — no concession objective at half its target. Next review in 20 Sols.',
    'nexus_warning_sol50' => 'Nexus Command: Checkpoint 2 of 3 missed — requirement (one objective at three quarters, a second at half) not met. Final review in 15 Sols.',
    'nexus_sanction_sol65' => 'Nexus Command: Checkpoint 3 of 3 missed — requirement (one objective fulfilled, a second at three quarters) not met. Sanction: one advisor is withdrawn for one Sol.',
    'nexus_countdown_sol80' => 'Nexus Command: 20 Sols until concession expiry. No further extension provided.',
    'run_failed_nexus_debt' => 'Insolvency — debt limit exceeded. Nexus revokes the concession with immediate effect.',

    // Result screen
    'result_title_completed' => 'Mission accomplished',
    'result_title_failed' => 'Mission failed',
    'result_fail_trust' => 'The colonists have lost faith in their leadership. Trust has fallen too far.',
    'result_fail_time' => 'The concession period has lapsed without the objective being reached.',
    'result_fail_phase1_deadline' => 'The stabilization phase was not completed in time. The Nexus pulled the plug.',
    'result_score_label' => 'Score',
    'result_ticks_label' => 'Reached in Sol :current of :limit',
    'result_objective_fulfilled' => 'Fulfilled',
    'result_objective_open' => 'Not fulfilled',
    'result_btn_new_run' => 'Start new mission',
    'result_btn_colony' => 'View colony',
    'result_btn_colony_disabled' => 'View colony (success only)',
    'new_run_preparing' => 'New run being prepared — feature coming in Phase 4.',

    // New-run flow (Feature 2)
    'new_run_started' => 'New mission prepared — the colony is waiting. Ready when you are.',
    'new_run_active_exists' => 'You still have an active run. Complete or close it first.',
    'new_run_no_colony' => 'No colony found for your account. Please contact support.',
];
