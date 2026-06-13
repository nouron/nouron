<?php

return [
    // Existing keys — kept as-is
    'subtitle' => 'Deine Kolonie wartet auf dich.',
    'colony_ready' => 'Kolonie bereit zum Start',
    'start_button' => 'Mission starten',
    'no_run' => 'Kein aktiver Run gefunden. Bitte neu einloggen.',

    // New keys for the rewritten lobby view
    'page_title' => 'Mission Control',
    'page_subtitle' => 'Übersicht deiner laufenden und abgeschlossenen Missionen.',
    'active_runs' => 'Aktive Missionen',
    'pending_runs' => 'Bereit zum Start',
    'finished_runs' => 'Abgeschlossene Missionen',
    'continue_button' => 'Mission fortsetzen',
    'new_run_button' => 'Neue Mission starten',
    'coming_soon' => 'Noch nicht verfügbar',
    'no_runs' => 'Keine Missionen vorhanden.',
    'sol_progress' => 'Sol :current / :limit',
    'started_at' => 'Gestartet',
    'played_from_to' => 'Gespielt von :from bis :to',
    'status_completed' => 'Abgeschlossen',
    'status_failed' => 'Gescheitert',
    'bypass_warning' => 'Bypass aktiv',
    'settings_detail' => 'Missionsdetails',
    'tick_limit' => 'Sol-Limit',
    'supply_cap' => 'Supply-Cap',
    'bypass_active' => 'Bypass',
    'show_details' => 'Details anzeigen',
    'hide_details' => 'Details ausblenden',
    'run_number' => 'Mission #:id',
    'colony_unnamed' => '(Unbenannt)',
    'ended_at' => 'Beendet',
    'max_players' => 'Max. Spieler',

    // Highscore table (Feature 1)
    'highscore_title' => 'Vergangene Missionen',
    'highscore_col_mission' => 'Mission',
    'highscore_col_status' => 'Status',
    'highscore_col_sol' => 'Sol erreicht',
    'highscore_col_tasks' => 'Aufgaben',
    'highscore_col_score' => 'Score',
    'highscore_no_runs' => 'Noch keine abgeschlossenen Missionen.',
    'highscore_ended' => 'Beendet: :date',

    // New run button (Feature 2)
    'new_run_confirm' => 'Neuen Run starten — Kolonie wird zurückgesetzt. Fortfahren?',

    // Abandon run
    'abandon_button' => 'Mission abbrechen',
    'abandon_confirm' => 'Mission wirklich abbrechen? Der Run gilt dann als gescheitert und kann nicht fortgesetzt werden.',
    'abandon_success' => 'Mission abgebrochen.',
    'abandon_not_active' => 'Dieser Run ist nicht mehr aktiv.',
    'nav_runs' => 'Run-Übersicht',

    // Colony rename (moved here from the removed colony/index screen)
    'rename_label' => 'Kolonienname',
    'rename_button' => 'Umbenennen',
];
