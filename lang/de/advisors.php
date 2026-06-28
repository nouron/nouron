<?php

return [

    'engineer' => 'Baumeister',
    'engineer_plural' => 'Baumeister',
    'engineer_desc' => 'Stellt Bau-AP bereit. Ermöglicht den Ausbau und die Instandhaltung von Gebäuden.',

    'scientist' => 'Analytiker',
    'scientist_plural' => 'Analytiker',
    'scientist_desc' => 'Stellt Forschungs-AP bereit. Treibt den Fortschritt in Wissenschaft und Technologie voran.',

    'pilot' => 'Raumfahrer',
    'pilot_plural' => 'Raumfahrer',
    'pilot_desc' => 'Stellt Navigations-AP bereit. Ermöglicht Flottenbewegungen und Erkundungsmissionen.',

    'trader' => 'Konsul',
    'trader_plural' => 'Konsuln',
    'trader_desc' => 'Stellt Wirtschafts-AP bereit. Verwaltet Handelsrouten und Marktoperationen.',

    'strategist' => 'Stratege',
    'strategist_plural' => 'Strategen',
    'strategist_desc' => 'Stellt Strategie-AP bereit. Zuständig für Verteidigung und Kampfoperationen.',

    // AP-Typ-Labels
    'ap_construction' => 'Bau-AP',
    'ap_research' => 'Forschungs-AP',
    'ap_economy' => 'Wirtschafts-AP',
    'ap_strategy' => 'Strategie-AP',
    'ap_navigation' => 'Navigations-AP',

    // Flash / JSON response messages
    'hired' => 'Berater eingestellt.',
    'fired' => 'Berater entlassen.',
    'error_duplicate' => 'Für diesen Beratertyp ist bereits ein Berater auf dieser Kolonie aktiv.',
    'error_slot_full' => 'Kein freier Berater-Slot. Erhöhe das CommandCenter-Level.',
    'error_insufficient_credits' => 'Nicht genug Credits, um diesen Berater einzustellen.',
    'error_dismissed_this_tick' => 'Dieser Berater wurde gerade erst entlassen und kann erst im nächsten Sol wieder eingestellt werden.',
    'error_generic' => 'Berater konnte nicht eingestellt werden.',
    'error_path_building_missing' => 'Das zugehörige Pfadgebäude muss zuerst gebaut und platziert werden.',
    'desc_path_open' => 'Noch kein Pfadgebäude gebaut — Sciencelab, Hangar oder Cantina errichten, um diesen Slot freizuschalten.',

    // Path choice descriptions shown in the advisor carousel for path_open slots
    'path_label_scientist' => 'Analytiker-Pfad',
    'path_label_pilot' => 'Raumfahrer-Pfad',
    'path_label_trader' => 'Konsul-Pfad',
    'path_choice_scientist' => 'Analytiklabor bauen → Analytiker einstellen. Schaltet Techtree und Kenntnisforschung frei; gibt Kenntnis-AP.',
    'path_choice_pilot' => 'Hangar bauen → Raumfahrer einstellen. Schaltet Missionen und Hangar-Events frei; gibt Navigations-AP.',
    'path_choice_trader' => 'Cantina bauen → Konsul einstellen. Schaltet Handel und Cantina-Events frei; gibt Handels-AP.',
    'path_unlock_scientist' => 'Techtree + Kenntnisforschung + Kenntnis-AP',
    'path_unlock_pilot' => 'Missionen + Hangar-Events + Navigations-AP',
    'path_unlock_trader' => 'Handel + Cantina-Events + Handels-AP',

    // Hire/fire confirmation dialogs
    'dialog_hire_title' => 'Berater einstellen',
    'dialog_fire_title' => 'Berater entlassen',
    'dialog_rank_junior' => 'Junior',
    'dialog_ap_label' => 'pro Sol',
    'dialog_cost_once' => 'Einmalige Kosten',
    'dialog_upkeep' => 'Unterhalt',
    'dialog_per_sol' => 'Cr / Sol',
    'dialog_fire_confirm' => 'wirklich entlassen? Bereits gesammelte Erfahrung (Rang) geht verloren.',
    'dialog_hire' => 'Einstellen',
    'dialog_fire' => 'Entlassen',
    'dialog_cancel' => 'Abbrechen',

    // Hire-time warnings: AP-type has no consuming building yet
    'warning_no_sciencelab' => 'Du hast noch kein Analytik-Labor — Forschungs-AP bleibt vorerst ungenutzt.',
    'warning_no_hangar' => 'Du hast noch keinen Hangar — Navigations-AP wird vorerst nur für Erkundung genutzt.',

];
