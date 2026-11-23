<?php

return [
    // Existing keys
    'colony_ready' => 'Colony ready to launch',
    'start_button' => 'Start run',
    'run_not_started' => 'Your run has not started yet — click "Start run" to begin.',

    // New keys for the rewritten lobby view
    'page_title' => 'Mission Control',
    'page_subtitle' => 'Overview of your active and completed runs.',
    'active_runs' => 'Active Runs',
    'pending_runs' => 'Ready to Start',
    'continue_button' => 'Continue run',
    'new_run_button' => 'Start new run',
    'no_runs' => 'No runs on record.',
    'sol_progress' => 'Sol :current / :limit',
    'started_at' => 'Started',
    'status_completed' => 'Completed',
    'status_failed' => 'Failed',
    'bypass_warning' => 'Bypass active',
    'tick_limit' => 'Sol limit',
    'supply_cap' => 'Supply cap',
    'bypass_active' => 'Bypass',
    'run_number' => 'Run #:id',
    'colony_unnamed' => '(Unnamed)',

    // Highscore table (Feature 1)
    'highscore_title' => 'Past Runs',
    'highscore_col_mission' => 'Run',
    'highscore_col_status' => 'Status',
    'highscore_col_sol' => 'Sol reached',
    'highscore_col_tasks' => 'Tasks',
    'highscore_col_score' => 'Score',
    'highscore_no_runs' => 'No completed runs yet.',
    'highscore_ended' => 'Ended: :date',

    // New run button (Feature 2)

    // Abandon run
    'abandon_button' => 'Abandon run',
    'abandon_confirm' => 'Really abandon this run? It will be marked as failed and cannot be continued.',
    'abandon_success' => 'Run abandoned.',
    'abandon_not_active' => 'This run is no longer active.',
    'nav_runs' => 'Run overview',

    // Colony rename (moved here from the removed colony/index screen)
    'rename_label' => 'Colony name',
    'rename_button' => 'Rename',
    // Svelte lobby (ADR 0006): was a hardcoded flash text in ColonyController::rename()
    'rename_success' => 'Colony name updated.',
];
