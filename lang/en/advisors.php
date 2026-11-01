<?php

return [

    'engineer' => 'Engineer',
    'engineer_plural' => 'Engineers',
    'engineer_desc' => 'He knows every bolt in this installation. Without an Engineer, construction stalls — with one, buildings go up and stay in shape.',

    'scientist' => 'Analyst',
    'scientist_plural' => 'Analysts',
    'scientist_desc' => 'She thinks in models, measurements, and questions nobody has asked yet. The Analyst drives the research lab forward — without her, knowledge stays at yesterday\'s level.',

    'pilot' => 'Pilot',
    'pilot_plural' => 'Pilots',
    'pilot_desc' => 'He knows this system better than most people know the map. Without a Pilot, exploration is limited to a few tiles — with one, the view reaches far beyond the colony zone.',

    'trader' => 'Consul',
    'trader_plural' => 'Consuls',
    'trader_desc' => 'She negotiates as if the next freighter is never coming back. The Consul opens trade channels and saves Credits where others spend them.',

    'strategist' => 'Strategist',
    'strategist_plural' => 'Strategists',
    'strategist_desc' => 'Quiet, precise, rarely heard — until something goes wrong. The Strategist coordinates protective measures and keeps incidents small before they escalate.',

    // AP label (unified pool — no AP types since 2026-08-02)
    'ap_label' => 'Action Points',

    // Flash / JSON response messages
    'hired' => 'Advisor hired.',
    'fired' => 'Advisor dismissed.',
    'promoted' => 'Advisor promoted.',
    'error_duplicate' => 'An advisor of this type is already active on this colony.',
    'error_slot_full' => 'No free advisor slot. Upgrade the Command Center level.',
    'error_insufficient_credits' => 'Not enough Credits to hire this advisor.',
    'error_dismissed_this_tick' => 'This advisor was just dismissed and cannot be rehired until the next Sol.',
    'error_generic' => 'Advisor could not be hired.',
    'error_path_building_missing' => 'The associated path building must be built and placed first.',
    'error_promotion_not_eligible' => 'This advisor has not yet gained the experience needed for a promotion.',
    'error_promotion_max_rank' => 'This advisor has already reached the highest rank.',
    'error_promotion_insufficient_credits' => 'Not enough Credits for this promotion.',
    'desc_path_open' => 'No path building built — construct the Analytics Lab, Hangar, or Cantina first to unlock this slot.',

    // Path choice descriptions shown in the advisor carousel for path_open slots
    'path_label_scientist' => 'Analyst path',
    'path_label_pilot' => 'Pilot path',
    'path_label_trader' => 'Consul path',
    'path_choice_scientist' => 'Build the Analytics Lab, then hire an Analyst — opens the tech tree and grants AP. The first foundation for scientific progress.',
    'path_choice_pilot' => 'Build the Hangar, then hire a Pilot — opens exploration missions and AP for exploration. The first step outside the colony zone.',
    'path_choice_trader' => 'Build the Cantina, then hire a Consul — opens trade offers and AP. The fastest route to Compounds and extra income.',
    'path_unlock_scientist' => 'Tech tree + knowledge research + AP',
    'path_unlock_pilot' => 'Missions + hangar events + AP',
    'path_unlock_trader' => 'Trade + cantina events + AP',

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

    // Hire-time warnings: path building not yet built
    'warning_no_sciencelab' => 'You don\'t have an Analytics Lab yet — knowledge research will stay locked until you build one.',
    'warning_no_hangar' => 'You don\'t have a Hangar yet — missions will stay locked until you build one.',
    'error_not_found' => 'Advisor not found.',
    // Svelte advisors screen (ADR 0006) — were hardcoded in advisors/index.blade.php + advisors.js
    'ui_title' => 'Advisors',
    'ui_slots' => 'Slots',
    'ui_prereq' => 'Requirement',
    'ui_vacant' => 'Vacant',
    'ui_path_pending' => 'Path pending',
    'ui_locked' => 'Locked',
    'ui_ticks' => 'Ticks',
    'ui_promotion' => 'Promotion',
    'ui_max' => 'Max',
    'ui_inactive_until' => 'Inactive until T:tick',
    'ui_active' => 'Active',
    'ui_promote' => 'Promote',
    'ui_path_guidance' => 'Pick a building above → build it → the advisor slot unlocks.',
    'ui_hire_cost' => 'Hiring cost',
    'ui_junior_ap' => 'AP/tick (junior)',
    'ui_cc_required' => 'CC level :level required',
    'ui_effects' => 'Effects',
    'ui_prev' => 'Previous advisor',
    'ui_next' => 'Next advisor',
    'ui_error_hire' => 'Hiring failed.',
    'ui_error_fire' => 'Dismissal failed.',
    'ui_error_promote' => 'Promotion failed.',
    'effects_engineer' => 'AP · Construction · Repair',
    'effects_scientist' => 'AP · Techtree · Knowledge research',
    'effects_pilot' => 'AP · Missions · Hangar events',
    'effects_trader' => 'AP · Trade · Cantina events',
];
