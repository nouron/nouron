<?php

return [
    'nav_label' => 'Kommandozentrale',
    'title' => 'Kommandozentrale — Nouron',

    // Phasenziele (Widget 1)
    'widget_phase_title' => 'Phasenziele',

    // Kolonisten-Zulage (Widget 2) — reuses colony.stipend_* strings (button/dialog
    // text was already defined for the old FAB/dialog, unchanged here).

    // Run-Fortschritt (Widget 3)
    'widget_run_title' => 'Run-Fortschritt',
    'widget_run_sol' => 'Sol :current von :limit',
    'widget_run_remaining' => ':count Sole verbleibend',
    'widget_run_nexus_debt' => 'Nexus-Schuld',

    // Wartungsstau (Widget 4)
    'widget_maintenance_title' => 'Wartungsstau',
    'widget_maintenance_none' => 'Keine kritisch beschädigten Gebäude.',
    'widget_maintenance_count' => ':count von :total Gebäuden kritisch beschädigt',
    'widget_maintenance_status' => 'Status :sp / :max',

    // Netto-Sol-Bilanz (Widget 5)
    'widget_balance_title' => 'Netto-Sol-Bilanz',
    'widget_balance_none' => 'Noch keine Daten — beende deinen ersten Sol.',
    'widget_balance_intro' => 'Veränderung im letzten Sol:',

    // Berater-Kurzübersicht (Widget 6)
    'widget_advisors_title' => 'Berater',
    'widget_advisors_none' => 'Noch keine Berater angestellt.',
    'widget_advisors_count' => ':count Berater aktiv',
    'widget_advisors_link' => 'Zum Berater-Screen',

    // Vertrauens-Ereignisse (Widget 7)
    'widget_trust_events_title' => 'Vertrauens-Ereignisse',
    'widget_trust_events_none' => 'Noch keine Ereignisse.',
    'widget_trust_events_sol' => 'Sol :sol',
];
