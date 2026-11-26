<?php

return [
    // Existing keys — kept as-is
    'colony_ready' => 'Kolonie bereit zum Start',
    'start_button' => 'Run starten',
    'run_not_started' => 'Dein Run ist noch nicht gestartet — klicke „Run starten", um loszulegen.',

    // New keys for the rewritten lobby view
    'page_title' => 'Mission Control',
    'page_subtitle' => 'Übersicht deiner laufenden und abgeschlossenen Runs.',
    'active_runs' => 'Aktive Runs',
    'pending_runs' => 'Bereit zum Start',
    'continue_button' => 'Run fortsetzen',
    'new_run_button' => 'Neuen Run starten',
    'no_runs' => 'Keine Runs vorhanden.',
    'sol_progress' => 'Sol :current / :limit',
    'started_at' => 'Gestartet',
    'status_completed' => 'Abgeschlossen',
    'status_failed' => 'Gescheitert',
    'bypass_warning' => 'Bypass aktiv',
    'tick_limit' => 'Sol-Limit',
    'supply_cap' => 'Kolonisten-Kapazität',
    'bypass_active' => 'Bypass',
    'run_number' => 'Run #:id',
    'colony_unnamed' => '(Unbenannt)',

    // Highscore table (Feature 1)
    'highscore_title' => 'Vergangene Runs',
    'highscore_col_mission' => 'Run',
    'highscore_col_status' => 'Status',
    'highscore_col_sol' => 'Sol erreicht',
    'highscore_col_tasks' => 'Aufgaben',
    'highscore_col_score' => 'Score',
    'highscore_no_runs' => 'Noch keine abgeschlossenen Runs.',
    'highscore_ended' => 'Beendet: :date',

    // New run button (Feature 2)

    // Abandon run
    'abandon_button' => 'Run abbrechen',
    'abandon_confirm' => 'Run wirklich abbrechen? Er gilt dann als gescheitert und kann nicht fortgesetzt werden.',
    'abandon_success' => 'Run abgebrochen.',
    'abandon_not_active' => 'Dieser Run ist nicht mehr aktiv.',
    'nav_runs' => 'Run-Übersicht',

    // Colony rename (moved here from the removed colony/index screen)
    'rename_label' => 'Kolonienname',
    'rename_button' => 'Umbenennen',
    // Svelte lobby (ADR 0006): was a hardcoded flash text in ColonyController::rename()
    'rename_success' => 'Kolonienname wurde aktualisiert.',
];
