<?php

return [
    // ── Mission catalog (config/missions.php) ────────────────────────────────

    'mission_courier_run_name' => 'Courier Run',
    'mission_courier_run_desc' => 'The drone carries data packets and mail to the nearest relay — small cargo, but out here someone pays well to stay connected.',
    'mission_courier_run_reward' => '60 Credits',

    'mission_recon_flight_name' => 'Recon Flight',
    'mission_recon_flight_desc' => 'The drone sweeps across uncharted terrain, radioing back what lies beyond the horizon.',
    'mission_recon_flight_reward' => '2 sectors charted',

    'mission_deep_survey_name' => 'Signal Survey',
    'mission_deep_survey_desc' => 'Something out there is transmitting — the drone circles the source until it knows what it really is.',
    'mission_deep_survey_reward' => 'Deep scan of the signal',

    'mission_prospecting_flight_name' => 'Prospecting Flight',
    'mission_prospecting_flight_desc' => 'The drone probes the rubble fields for workable regolith and comes home with its sample containers full.',
    'mission_prospecting_flight_reward' => '20–30 Regolith',

    'mission_data_sweep_name' => 'Data Sweep',
    'mission_data_sweep_desc' => 'On one long, wide loop the drone gathers field readings that save the analysts back home weeks of legwork.',
    'mission_data_sweep_reward' => '+8 Research AP',

    'mission_long_range_expedition_name' => 'Long-Range Expedition',
    'mission_long_range_expedition_desc' => 'Five sols out, five back — nobody knows what the drone will find at the edge of its range, but it rarely comes home empty.',
    'mission_long_range_expedition_reward' => 'Random find',

    'mission_supply_run_name' => 'Supply Run',
    'mission_supply_run_desc' => 'The freighter makes the rounds of scattered depots and outposts, hauling back whatever the colony needs most.',
    'mission_supply_run_reward' => '25 Regolith + 10 Organics',

    'mission_trade_convoy_name' => 'Trade Convoy',
    'mission_trade_convoy_desc' => 'Loaded with everything the colony can spare, the freighter runs the trade route — and everyone likes to see it come back heavy.',
    'mission_trade_convoy_reward' => '180 Credits + Trust',

    'mission_aid_transport_name' => 'Aid Transport',
    'mission_aid_transport_desc' => 'Somewhere a station is worse off than we are — the freighter carries organics out, and the colony remembers what it works for.',
    'mission_aid_transport_reward' => '60 Credits + Trust',

    'mission_salvage_sweep_name' => 'Salvage Sweep',
    'mission_salvage_sweep_desc' => 'The wreck fields hold what the colony cannot make itself — the salvage crew cuts loose whatever is still good.',
    'mission_salvage_sweep_reward' => '6–10 Compounds',

    'mission_ruin_expedition_name' => 'Ruin Expedition',
    'mission_ruin_expedition_desc' => 'Whoever left the ruin is long gone — but what lies between its walls is worth an expedition.',
    'mission_ruin_expedition_reward' => '150 Credits',

    'mission_escort_convoy_name' => 'Convoy Escort',
    'mission_escort_convoy_desc' => 'The corvette shadows a convoy through rough country — most days nothing happens, and that is exactly the point.',
    'mission_escort_convoy_reward' => '200 Credits',

    // ── Dispatch dialog ──────────────────────────────────────────────────────

    'dialog_title' => 'Choose a mission',
    'dialog_no_missions' => 'No assignment available for this ship type right now.',
    'gate_knowledge_hint' => 'Requires :name level :level',
    'gate_target_hint' => 'No valid target available',
    'select_target' => 'Select target',
    'chip_duration' => ':sols Sols',
    'chip_wear' => 'Wear',
    'chip_organika' => 'Organics',
    'return_label' => 'Return: Sol :sol',
    'start_button' => 'Start mission',

    // ── Dispatch errors ──────────────────────────────────────────────────────

    'error_wrong_ship_type' => 'This ship is not suited for this mission.',
    'error_sp_too_low' => 'This ship is too worn for a mission — repair it first.',
    'error_knowledge_gate' => 'The colony lacks the knowledge required for this mission.',
    'error_invalid_target' => 'No valid target selected for this mission.',
    'error_target_consumed' => 'This ruin has already been salvaged.',

    // ── Sol report ───────────────────────────────────────────────────────────

    'sol_report_completed' => 'Mission completed',
    'sol_report_aborted' => 'Mission aborted — ship returned unable to fly',
];
