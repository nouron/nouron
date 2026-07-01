<?php

return [

    'engineer' => 'Engineer',
    'engineer_plural' => 'Engineers',
    'engineer_desc' => 'He knows every bolt in this installation. Without an Engineer, construction stalls — with one, buildings go up and stay in shape.',

    'scientist' => 'Scientist',
    'scientist_plural' => 'Scientists',
    'scientist_desc' => 'She thinks in models, measurements, and questions nobody has asked yet. The Scientist drives the research lab forward — without her, knowledge stays at yesterday\'s level.',

    'pilot' => 'Pilot',
    'pilot_plural' => 'Pilots',
    'pilot_desc' => 'He knows this system better than most people know the map. Without a Pilot, exploration is limited to a few tiles — with one, the view reaches far beyond the colony zone.',

    'trader' => 'Consul',
    'trader_plural' => 'Consuls',
    'trader_desc' => 'She negotiates as if the next freighter is never coming back. The Consul opens trade channels and saves Credits where others spend them.',

    'strategist' => 'Strategist',
    'strategist_plural' => 'Strategists',
    'strategist_desc' => 'Quiet, precise, rarely heard — until something goes wrong. The Strategist coordinates protective measures and keeps incidents small before they escalate.',

    // AP type labels
    'ap_construction' => 'Construction AP',
    'ap_research' => 'Research AP',
    'ap_economy' => 'Economy AP',
    'ap_strategy' => 'Strategy AP',
    'ap_navigation' => 'Navigation AP',

    // Flash / JSON response messages
    'hired' => 'Advisor hired.',
    'fired' => 'Advisor dismissed.',
    'error_duplicate' => 'An advisor of this type is already active on this colony.',
    'error_slot_full' => 'No free advisor slot. Upgrade the Command Center level.',
    'error_insufficient_credits' => 'Not enough Credits to hire this advisor.',
    'error_dismissed_this_tick' => 'This advisor was just dismissed and cannot be rehired until the next Sol.',
    'error_generic' => 'Advisor could not be hired.',
    'error_path_building_missing' => 'The associated path building must be built and placed first.',
    'desc_path_open' => 'No path building built — construct the Analytics Lab, Hangar, or Cantina first to unlock this slot.',

    // Path choice descriptions shown in the advisor carousel for path_open slots
    'path_label_scientist' => 'Scientist path',
    'path_label_pilot' => 'Pilot path',
    'path_label_trader' => 'Consul path',
    'path_choice_scientist' => 'Build the Analytics Lab, then hire a Scientist — opens the tech tree and grants Research AP. The first foundation for scientific progress.',
    'path_choice_pilot' => 'Build the Hangar, then hire a Pilot — opens exploration missions and Navigation AP. The first step outside the colony zone.',
    'path_choice_trader' => 'Build the Cantina, then hire a Consul — opens trade offers and Economy AP. The fastest route to Compounds and extra income.',
    'path_unlock_scientist' => 'Tech tree + knowledge research + knowledge AP',
    'path_unlock_pilot' => 'Missions + hangar events + Navigation AP',
    'path_unlock_trader' => 'Trade + cantina events + Economy AP',

    // Hire/fire confirmation dialogs
    'dialog_hire_title' => 'Hire advisor',
    'dialog_fire_title' => 'Dismiss advisor',
    'dialog_rank_junior' => 'Junior',
    'dialog_ap_label' => 'per Sol',
    'dialog_cost_once' => 'One-time cost',
    'dialog_upkeep' => 'Upkeep',
    'dialog_per_sol' => 'Cr / Sol',
    'dialog_fire_confirm' => 'really dismiss? Accumulated experience (rank) will be lost.',
    'dialog_hire' => 'Hire',
    'dialog_fire' => 'Dismiss',
    'dialog_cancel' => 'Cancel',

    // Hire-time warnings: AP-type has no consuming building yet
    'warning_no_sciencelab' => 'You don\'t have an Analytics Lab yet — Research AP will go unused for now.',
    'warning_no_hangar' => 'You don\'t have a Hangar yet — Navigation AP will only be used for exploration for now.',

];
