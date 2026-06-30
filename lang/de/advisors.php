<?php

return [

    'engineer' => 'Baumeister',
    'engineer_plural' => 'Baumeister',
    'engineer_desc' => 'Er kennt jeden Bolzen in dieser Anlage. Ohne Baumeister steht der Ausbau still — mit ihm kommen Gebäude hoch und bleiben in Schuss.',

    'scientist' => 'Analytiker',
    'scientist_plural' => 'Analytiker',
    'scientist_desc' => 'Sie denkt in Modellen, Messwerten und Fragen, die noch niemand gestellt hat. Der Analytiker treibt das Forschungslabor voran — ohne sie bleiben die Kenntnisse auf dem Stand von gestern.',

    'pilot' => 'Raumfahrer',
    'pilot_plural' => 'Raumfahrer',
    'pilot_desc' => 'Er kennt das System besser als die meisten die Karte kennen. Ohne Raumfahrer bleibt die Erkundung auf ein paar Felder beschränkt — mit ihm reicht der Blick über die Koloniezone hinaus.',

    'trader' => 'Konsul',
    'trader_plural' => 'Konsuln',
    'trader_desc' => 'Sie verhandelt, als käme der nächste Frachter nie wieder. Der Konsul öffnet Handelskanäle und spart Credits, wo andere Credits ausgeben.',

    'strategist' => 'Stratege',
    'strategist_plural' => 'Strategen',
    'strategist_desc' => 'Ruhig, präzise, selten zu hören — bis etwas schiefläuft. Der Stratege koordiniert Schutzmaßnahmen und hält Zwischenfälle klein, bevor sie eskalieren.',

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
    'desc_path_open' => 'Kein Pfadgebäude gebaut — erst Analytiklabor, Hangar oder Cantina errichten, um diesen Slot freizuschalten.',

    // Path choice descriptions shown in the advisor carousel for path_open slots
    'path_label_scientist' => 'Analytiker-Pfad',
    'path_label_pilot' => 'Raumfahrer-Pfad',
    'path_label_trader' => 'Konsul-Pfad',
    'path_choice_scientist' => 'Analytiklabor bauen, dann Analytiker einstellen — öffnet den Techtree und gibt Forschungs-AP. Die erste Grundlage für wissenschaftlichen Fortschritt.',
    'path_choice_pilot' => 'Hangar bauen, dann Raumfahrer einstellen — öffnet Erkundungsmissionen und Navigations-AP. Der erste Schritt raus aus der Koloniezone.',
    'path_choice_trader' => 'Cantina bauen, dann Konsul einstellen — öffnet Handelsangebote und Wirtschafts-AP. Der schnellste Weg zu Werkstoffen und Extraeinnahmen.',
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
