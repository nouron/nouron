<?php

return [

    // ── Tile actions ──────────────────────────────────────────────────────────

    'explore' => 'Explore',
    'deep_scan' => 'Probe',
    'invest_ap' => 'Invest AP',
    'levelup_cost_label' => 'Cost:',
    'levelup_cost_suffix' => 'at construction start',
    'repair' => 'Repair',
    'ap_per_tile' => '1 AP/tile',

    // ── Repair errors ─────────────────────────────────────────────────────────

    'error_repair_under_construction' => 'Building is still under construction and cannot be repaired.',
    'error_repair_full' => 'Building is already fully intact.',

    // ── Building actions ──────────────────────────────────────────────────────

    'build' => 'Build',
    'cancel' => 'Cancel',
    'select_tile_hint' => 'Click a tile to place',

    // ── Sidebar labels ────────────────────────────────────────────────────────

    'tile_info' => 'Tile Info',
    'terrain_details' => 'Terrain & Location',

    // ── Hex-Grid legend ───────────────────────────────────────────────────────
    'legend_title' => 'Legend',
    'legend_buildable' => 'Buildable tile',
    'legend_soon_buildable' => 'Soon buildable (with CC upgrade)',
    'legend_zone_fog' => 'Buildable, not yet explored — building reveals it',
    'legend_explore_fog' => 'Exploration target — reveal with Navigation AP',
    'legend_regolith' => 'Regolith deposit',
    'legend_cc' => 'Command Center',
    'legend_hazard' => 'Hazard zone',
    'legend_impassable' => 'Impassable',
    'legend_event' => 'Discovered event',

    // ── Sidebar: Terrain / zone info ──────────────────────────────────────────
    'zone_buildable' => 'Colony zone — buildable',
    'zone_soon' => 'Soon buildable (next Command Center upgrade)',
    'zone_outside' => 'Outside the colony zone',
    'zone_unexplored' => 'Unexplored — explore (Nav AP)',
    'terrain_label' => 'Terrain',
    'event_label' => 'Phenomenon',
    'coords_label' => 'Coordinates',
    'hint_regolith_target' => 'Target for Harvester relocation.',
    'hint_hazard' => 'Hazard zone — building is risky, elevated decay.',
    'hint_impassable' => 'Impassable — nothing can be built here.',
    'click_tile_hint' => 'Click a hex tile to view details.',
    'phase1_progress_title' => 'Phase 1 Objectives',
    'phase2_progress_title' => 'Nexus Directives',
    'building_section' => 'Building',
    'construction_site' => 'Construction Site',
    'under_construction' => 'Under Construction',
    'resource_regolith' => 'Regolith',

    // ── Status chips ──────────────────────────────────────────────────────────

    'chip_locked' => 'Locked',
    'chip_unexplored' => 'Unexplored',
    'chip_explored' => 'Explored',
    'chip_scanned' => 'Probed',
    'chip_signal' => 'Signal',

    // ── Build mode ────────────────────────────────────────────────────────────

    'build_mode_title' => 'Build Structure',
    'build_mode_hint' => 'Select a building, then click a terrain tile.',
    'building_info_label' => 'Show building info',
    'no_buildings' => 'No buildings available.',
    'inprogress_label' => 'Under Construction',
    'inprogress_hint' => 'Click the tile on the map to invest AP.',
    'levelup_built' => 'Construction complete:',

    // ── Event discovery popup ─────────────────────────────────────────────────

    'discovery_title' => 'Signal Decoded',
    'discovery_dismiss' => 'Understood',

    // ── Onboarding hints (Phase 3e) ───────────────────────────────────────────

    'hint_suggestion_label' => 'Suggestion',
    'hint_not_mandatory' => 'Suggestion, not mandatory — other build orders are possible.',
    'onboarding_hint_1' => 'No Engineer on board — Construction AP is running at minimum. Fix this in the Advisor screen before another Sol slips by.',
    'onboarding_hint_repair' => 'The installation is already showing early wear — better to repair now than face a crisis later. Tap a building, then choose "Repair" (1 Construction AP).',
    'onboarding_hint_repair_urgent' => 'Warning: a building is close to losing a level. Repair now — before the next Sol makes the decision for you.',
    'onboarding_hint_2' => 'The Harvester is running empty laps — it is still inside the colony zone. Relocate it to an explored Regolith tile outside the zone so it actually produces.',
    'onboarding_hint_3' => 'Upgrade the Command Center to Level 2 — that expands the colony zone and opens the path choice. The Agrarian Dome must be built first.',
    'onboarding_hint_3_agrardome_first' => 'Build the Agrarian Dome first — mandatory prerequisite for CC Level 2. Then choose your path: Analytics Lab, Hangar, or Cantina.',
    'onboarding_hint_advisor_slot2' => 'Advisor slot 2 is open — go to the Advisor screen, pick a path building and build it: Analytics Lab, Hangar, or Cantina.',
    'onboarding_hint_4' => 'No knowledge at Level 1 yet — assign Research AP to a field in the tech tree. Results build up Sol by Sol.',
    'onboarding_hint_5' => 'Colony mood is turning — stabilise Trust: build or repair a civil structure before it drops further.',
    'onboarding_hint_build_priority' => 'Resources won\'t stretch to everything at once — start with one path. The rest follows when the colony is on steadier footing.',
    'onboarding_hint_6' => 'No Cantina built — merchants and visitors won\'t stop. Trade offers and one-time items simply pass the colony by.',
    'onboarding_hint_agrardome' => 'Build the Agrarian Dome — without it CC Level 2 stays locked and Organics stay at zero. No Agrarian Dome, no provisions, no progress.',
    'onboarding_hint_analytik' => 'No Analytics Lab — Research AP has nowhere to go. The colony stays scientifically stuck.',
    'onboarding_hint_hangar_path' => 'Hangar path: build the Hangar first, then hire a Pilot. Only then do missions and Navigation AP become properly available.',
    'onboarding_hint_cc_invest' => 'Don\'t let Construction AP go to waste — invest it in the Command Center. Whatever is invested stays; Level 2 gets closer.',
    'onboarding_hint_explore' => 'Use Navigation AP for exploration (1 AP per tile) — Regolith deposits and unknown signals wait beyond the zone.',
    'onboarding_end_sol' => 'End the Sol — all sensible actions taken. The next Sol brings fresh action points and new developments.',
    'onboarding_hint_spend_ap_construction' => 'Construction AP remaining — invest it in a building or pre-fund CC upgrade. AP expires at Sol end.',
    'onboarding_hint_spend_ap_research' => 'Research AP remaining — assign it to a knowledge field in the tech tree before the Sol ends.',
    'onboarding_hint_spend_ap_navigation' => 'Navigation AP remaining — there is still unexplored terrain out there. One tile costs 1 AP.',
    'onboarding_hint_spend_ap_economy' => 'Economy AP remaining — check the Cantina to see if a guest has an offer ready.',
    'nav_techtree_locked' => 'Analytics Lab required.',
    'nav_cantina_locked' => 'Cantina not built — Residential Habitat lv1 required.',

    // ── First-visit popups (Techtree / Nexus-DB / Cantina / Hangar) ──────────

    'first_visit_dismiss' => 'Understood',
    'first_visit_techtree_title' => 'Tech Tree',
    'first_visit_techtree_text' => 'Here you assign Research AP to a knowledge field. Knowledge grows Sol by Sol — whatever has been invested is retained, even if a Sol isn\'t enough to finish.',
    'first_visit_cantina_title' => 'Cantina',
    'first_visit_cantina_text' => 'The Cantina is the only place where colonists can forget, just for a moment, how thin the air is outside. Strangers from transit bring news, goods — and sometimes offers you really shouldn\'t miss. Drop by rarely and you\'ll notice the deal is already gone.',
    'first_visit_hangar_title' => 'Hangar',
    'first_visit_hangar_text' => 'Ships are stationed here and dispatched on missions. Each deployment costs Organics as crew provisions and takes several Sols — recall is possible at any time.',
    'first_visit_nexusdb_title' => 'Nexus DB',
    'first_visit_nexusdb_text' => 'Technical reference database, published by the Nexus — entries on buildings, knowledge fields, ships, and game mechanics. No action required.',

    // ── Onboarding — Nexus Briefing (INNN, event_type = 'onboarding.nexus_briefing') ──

    'onboarding_nexus_briefing_title' => 'Concession activated — Colony :colony',
    'onboarding_nexus_briefing_body' => 'Command Center and Harvester operational. Starting capital: 3,000 Cr — Nexus loan, repayment pending. Regolith reserve: 200 Rg. Colony viability under continuous assessment. Subsidies active until further notice.',

    // ── Onboarding — Inline trigger explanations ──────────────────────────────

    // Trigger 2 — Supply cap full (UI banner, 1 sentence)
    'onboarding_trigger_supply_full' => 'Supply capacity exhausted — no further buildings or ships can be assigned. Upgrade the Residential Habitat or remove consumers.',

    // Trigger 4 — AP limit (tooltip)
    'onboarding_trigger_ap_limit' => 'No more Construction AP available this Sol.',

    // Trigger 5 — Harvester relocation (tooltip)
    'onboarding_trigger_harvester_move' => 'Relocation costs 1 Construction AP per tile distance — the Harvester will produce on the new tile afterwards.',

    // ── Error messages ────────────────────────────────────────────────────────

    'error_path_gate_locked' => 'Path locked — upgrade the Command Center to the next level first.',
    'error_agrardom_required' => 'Build the Agrarian Dome first — mandatory prerequisite for all path buildings.',
    'error_tile_not_found' => 'Tile not found.',
    'error_ring_locked' => 'Ring not unlocked.',
    'error_already_explored' => 'Tile already explored.',
    'error_not_explored' => 'Tile must be explored first.',
    'error_no_signal' => 'No signal on this tile.',
    'error_already_scanned' => 'Tile already probed.',
    'error_no_nav_ap' => 'Not enough Navigation AP.',
    'error_no_nav_ap_2' => 'Not enough Navigation AP (2 required).',
    'error_tile_not_buildable' => 'Only buildable terrain tiles allowed.',
    'error_tile_outside_colony' => 'This tile is outside the colony zone.',
    'error_harvester_needs_regolith' => 'Harvester can only be placed on Regolith tiles.',
    'harvester_move' => 'Relocate',
    'harvester_move_mode_hint' => 'Click an explored Regolith tile outside the colony zone — shows a preview arrow with AP cost. Hold to relocate.',
    'harvester_move_no_targets' => 'No free explored Regolith tile available — explore new tiles first (Nav AP).',
    'harvester_move_invalid_target' => 'Not a valid target — the Harvester needs a free, explored Regolith tile (highlighted in light blue).',
    'network_error' => 'Network error — please try again.',
    'error_harvester_in_transit' => 'The Harvester is still in transit — relocation possible after arrival.',
    'harvester_in_transit' => 'In transit — arrives next Sol.',
    'error_tile_occupied' => 'Tile already occupied.',
    'error_no_construction_ap' => 'Not enough Construction AP.',
    'error_building_not_found' => 'Building not found.',
    'error_max_level_reached' => 'Maximum level already reached.',
    'error_insufficient_resources' => 'Not enough resources to build.',
    'error_repair_no_regolith' => 'No Regolith for repair — repair the Harvester or mine Regolith.',

    // ── Build cost chips ──────────────────────────────────────────────────────

    'cost_regolith' => ':amount Rg',
    'cost_compounds' => ':amount Wk',
    'cost_label' => 'Cost',

    // ── Nexus import (Compounds for Credits, Uplink Station Lv1) ──────────────

    'nexus_import_title' => 'Nexus Import',
    'nexus_import_hint' => 'Buy Compounds directly from the Nexus — always available, fixed price.',
    'nexus_import_amount' => 'Amount (Compounds)',
    'nexus_import_price_each' => ':price Cr/unit',
    'nexus_import_total' => 'Total: :total Cr',
    'nexus_import_confirm' => 'Import',
    'nexus_import_success' => 'Imported :amount Compounds (:cost Cr).',
    'nexus_import_uplink_required' => 'Uplink Station Lv1 required — it unlocks active Nexus requests.',
    'nexus_import_no_credits' => 'Not enough Credits for this import.',
    'nexus_import_error' => 'Import failed.',

    // ── Generic UI actions ────────────────────────────────────────────────────

    'close' => 'Close',

    // ── Travelling Merchant ───────────────────────────────────────────────────

    'merchant_in_system' => 'Merchant in system',
    'merchant_title' => 'Travelling Merchant',
    'merchant_until_sol' => 'Stays until Sol',
    'merchant_buy' => 'Buy',
    'merchant_sold' => 'Sold',
    'merchant_buy_success' => 'Purchase successful.',
    'merchant_buy_error' => 'Purchase failed.',

    // ── Bar / Cantina ─────────────────────────────────────────────────────────

    'bar_title' => 'Cantina',
    'bar_no_building' => 'The Cantina has not been built yet.',
    'bar_no_offers' => 'No guests at the moment. Come back next Sol.',
    'bar_offer_heading' => 'Offers',
    'bar_offer_accept' => 'Accept',
    'bar_offer_give' => 'You give',
    'bar_offer_get' => 'You receive',
    'bar_offer_expires' => 'Expires in Sol',
    'bar_offer_not_found' => 'Offer not found.',
    'bar_offer_already_accepted' => 'Offer already accepted.',
    'bar_offer_expired' => 'Offer has expired.',
    'bar_offer_insufficient_resources' => 'Not enough resources.',
    'bar_offer_insufficient_ap' => 'Not enough Economy AP.',

    // ── Sol trigger (navbar button) ───────────────────────────────────────────

    'next_sol_button' => 'End Sol',

    // ── Nexus debt display ────────────────────────────────────────────────────

    'nexus_debt_label' => 'Nexus Credit',
    'nexus_debt_format' => ':amount / 12,000 Cr',

    // ── Hangar screen ─────────────────────────────────────────────────────────

    'hangar_title' => 'Hangar',
    'hangar_slot_count' => 'Slots',
    'hangar_empty' => 'Empty',
    'hangar_build_ship' => 'Build ship',
    'hangar_dispatch' => 'Dispatch',
    'hangar_dispatch_no_nav_ap' => 'Not enough Navigation AP for this mission.',
    'hangar_dispatch_no_organika' => 'Not enough Organics for this mission\'s crew provisions.',
    'hangar_recall' => 'Recall',
    'hangar_repair' => 'Repair',
    'hangar_destination' => 'Destination',
    'hangar_sol_distance' => 'Sol distance',
    'hangar_in_transit' => 'In transit',
    'hangar_in_construction' => 'Under construction',
    'hangar_pilot_ready' => 'Pilot ready',
    'hangar_status' => 'Status',
    'hangar_already_commissioned' => 'Already active',
    'hangar_none_built' => 'No Hangar built. Build a Hangar in the colony to station ships.',
    'nav_hangar' => 'Hangar',
    'nav_hangar_locked' => 'No Hangar built.',

    // ── Hangar — Nexus request (ship acquisition redesign) ───────────────────

    'hangar_nexus_request' => 'Request from Nexus',
    'hangar_nexus_request_title' => 'Request ship',
    'hangar_nexus_request_submit' => 'Request',
    'hangar_ship_type' => 'Ship type',
    'hangar_payment_method' => 'Payment',
    'hangar_standard_purchase' => 'Standard purchase',
    'hangar_nexus_credit' => 'Nexus credit',
    'hangar_nexus_credit_hint' => '0 Cr now — Nexus debt increases',
    'hangar_consul_ap_title' => 'Consul Negotiation',
    'hangar_consul_ap_label' => 'Invest AP (saves :amount Cr)',
    'hangar_consul_ap_hint' => ':ap AP → saves :saved Cr',
    'hangar_delivery_pending' => 'Delivery pending',
    'hangar_arrival' => 'Arrival: Sol :tick',
    'hangar_pending_section' => 'Unassigned',
    'hangar_pending_expires' => 'Expires: Sol :tick',
    'hangar_assign_hangar' => 'Assign hangar',
    'hangar_assign_select' => 'Select bay…',
    'hangar_ship_drone' => 'Drone',
    'hangar_ship_freighter' => 'Freighter',
    'hangar_ship_corvette' => 'Corvette',

    // ── Sol Report (transition screen) ───────────────────────────────────────

    // Group headings
    'sol_report_group_decay' => 'The colony ages',
    'sol_report_group_events' => 'Events',
    'sol_report_group_production' => 'Production & Supplies',
    'sol_report_group_colony' => 'Colony & Personnel',
    'sol_report_group_run' => 'The Run',

    // Decay
    'sol_report_level_to' => 'dropped to level :level',
    'sol_report_level_lost' => 'Level lost',
    'sol_report_ship_destroyed' => 'destroyed by decay',
    'sol_report_wear_label' => 'Installations',
    'sol_report_wear_detail' => 'holding — minor wear',

    // Events
    'sol_report_event_merchant' => 'Travelling Merchant in system',

    // Production
    'sol_report_no_production' => 'The extraction plants are idle — no Regolith, no progress. Check industrial buildings.',
    'sol_report_food' => 'Provisions',
    'sol_report_food_ok' => ':amount Organics distributed to the kitchens — the colony is fed',
    'sol_report_food_shortage' => 'Supplies exhausted — colonists are going hungry. Trust is falling. Build or repair the Agrarian Dome.',

    // Colony & Personnel
    'sol_report_advisor' => 'Advisor',
    'sol_report_advisor_promoted' => 'promoted to rank :rank',

    // Run
    'sol_report_phase_reached' => 'Phase :phase reached — new options available',
    'sol_report_objectives' => 'Objectives met: :done / :total',
    'sol_report_sol_counter' => 'Sol :sol of :limit',

    // Finale (run end)
    'sol_report_finale_win_title' => 'Mission accomplished',
    'sol_report_finale_win_body' => 'The colony stands. Against decay, scarcity, and everything the planet threw at it — it stands.',
    'sol_report_finale_lose_title' => 'Mission failed',
    'sol_report_finale_lose_body' => 'This colony did not survive. What remains will be noted and handed over — and someday, those who have learned enough will start again.',

    // UI controls
    'sol_report_title' => 'Sol :sol complete',
    'sol_report_continue' => 'Continue to Sol :sol',

    // Screen 2 — Phase progress
    'sol_report_screen2_title' => 'Phase Progress',
    'sol_report_phase1_title' => 'Phase 1 — Stabilisation',
    'sol_report_phase2_title' => 'Phase 2 — Nexus Directives',
    'sol_report_phase1_cc' => 'Command Center at Level 3',
    'sol_report_phase1_buildings' => '2 buildings at Level 2',
    'sol_report_phase1_advisors' => '3 active advisors',
    'sol_report_phase2_objective_hidden' => 'Directive not yet known — unlocked during play',
    'sol_report_phase2_objective_done' => 'Fulfilled',
    'sol_report_next_screen' => 'Continue',

    // Screen 3 — SOL N starts
    'sol_report_screen3_starts' => 'starts',
    'sol_report_screen3_begin' => 'Continue mission',

    'sol_report_skip_hint' => 'Tap to skip',
    'sol_report_skip_setting' => 'Skip Sol report automatically in future',
    'sol_report_finale_win_cta' => 'Complete run',
    'sol_report_finale_lose_cta' => 'End run',

    // ── Legacy EN-only keys (kept for template compatibility) ─────────────────

    'condition' => 'Condition',
    'ap_invested' => 'AP invested',

];
