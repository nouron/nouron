<?php

return [
    'nav_label' => 'Command Center',
    'title' => 'Command Center — Nouron',

    // Phase objectives (Widget 1)
    'widget_phase_title' => 'Phase Objectives',

    // Colonist Stipend (Widget 2) — reuses colony.stipend_* strings (button/dialog
    // text was already defined for the old FAB/dialog, unchanged here).

    // Run progress (Widget 3)
    'widget_run_title' => 'Run Progress',
    'widget_run_sol' => 'Sol :current of :limit',
    'widget_run_remaining' => ':count Sols remaining',
    'widget_run_nexus_debt' => 'Nexus Debt',

    // Maintenance backlog (Widget 4)
    'widget_maintenance_title' => 'Maintenance Backlog',
    'widget_maintenance_none' => 'No critically damaged buildings.',
    'widget_maintenance_count' => ':count of :total buildings critically damaged',
    'widget_maintenance_status' => 'Status :sp / :max',

    // Net Sol balance (Widget 5)
    'widget_balance_title' => 'Net Sol Balance',
    'widget_balance_none' => 'No data yet — end your first Sol.',
    'widget_balance_intro' => 'Change over the last Sol:',

    // Advisor overview (Widget 6)
    'widget_advisors_title' => 'Advisors',
    'widget_advisors_none' => 'No advisors hired yet.',
    'widget_advisors_count' => ':count advisors active',
    'widget_advisors_link' => 'Go to Advisors',

    // Trust events (Widget 7)
    'widget_trust_events_title' => 'Trust Events',
    'widget_trust_events_none' => 'No events yet.',
    'widget_trust_events_sol' => 'Sol :sol',
];
