<?php

return [
    // ── Buildings (building_* keys, GDD §4) ──────────────────────────────────
    'building_commandCenter' => 'Command Center',
    'building_housingComplex' => 'Residential Habitat',
    'building_harvester' => 'Harvester',
    'building_bioFacility' => 'Agrarian Dome',
    'building_sciencelab' => 'Analytics Lab',
    'building_temple' => 'Sacred Site',
    'building_hangar' => 'Hangar',
    'building_infirmary' => 'Medical Station',
    'building_monument' => 'Colonial Monument',
    'building_bar' => 'Cantina',
    'building_securityHub' => 'Response Center',
    'building_uplinkStation' => 'Uplink Station',
    'building_tradingPost' => 'Trading Post',

    // ── Tier nicknames (tier_* keys, design spec 2026-08-23) ─────────────────
    'tier_hangar_1' => 'Launch Module',
    'tier_hangar_2' => 'Freight Ramp',
    'tier_hangar_3' => 'Mooring Berth',
    'tier_securityHub_3' => 'Salvage Crew',
    'tier_infirmary_3' => 'Fully Equipped',
    'tier_bioFacility_3' => 'Emergency Reserve',
    'tier_uplinkStation_1' => 'Open Line',
    'tier_tradingPost_1' => 'Familiar Face',
    'tier_tradingPost_2' => 'Regular Customer',
    'tier_tradingPost_3' => 'Personal Contact',

    // ── Building descriptions (desc_techs_* keys) ─────────────────────────────
    'desc_techs_commandCenter' => 'Everything comes together here: reports, plans, decisions. The Command Center is not a glamorous building, but it is the colony\'s nervous system. Its level decides how far the colony can grow.',
    'desc_techs_housingComplex' => 'A residential habitat is more than a roof overhead. It says: this is no temporary camp. Every level makes room for more colonists.',
    'desc_techs_harvester' => 'Day and night it breaks Regolith out of the ground, somewhere beyond the colony zone. Without it, every building plan stays paper.',
    'desc_techs_bioFacility' => 'Under the dome grows what the colony needs to survive. The Agrarian Dome supplies Organics, and without it the colony cannot grow any further.',
    'desc_techs_sciencelab' => 'This is where the ones who ask questions sit. The lab turns measurements into insight and insight into progress. Without it, knowledge stays locked.',
    'desc_techs_infirmary' => 'Not every accident happens on a building site. The Medical Station tends injured colonists and steadies Trust in bad times. You hope never to need it.',
    'desc_techs_bar' => 'In the Cantina, colonists can forget for a moment how thin the air is outside. People passing through bring news and goods, and sometimes an offer worth listening to.',
    'desc_techs_hangar' => 'Ships need a place to land, refuel and be serviced. The Hangar is the starting point of every mission and the colony\'s eyes on the outside.',
    'desc_techs_temple' => 'Some things cannot be paid for in Regolith and Credits. The Sacred Site gives colonists room for ritual, company and a moment of stillness.',
    'desc_techs_monument' => 'Sooner or later you ask what all this is for. The Colonial Monument answers: visible, lasting, for everyone. It roots the colony\'s history in the planet\'s soil.',
    'desc_techs_securityHub' => 'No Response Center makes the colony invulnerable. But it keeps incidents from escalating and dampens negative events. The colonists know: someone is watching.',
    'desc_techs_uplinkStation' => 'Without an Uplink Station the colony is silent to the Nexus, and the Nexus only acts on request. It unlocks direct Compound import and keeps the only line to the outside open.',
    'desc_techs_tradingPost' => 'A well-kept Trading Post gets around among travellers: business is done here. The Travelling Merchant offers better terms.',

    // ── Knowledge ────────────────────────────────────────────────────────────
    'knowledge_construction' => 'Construction',
    'knowledge_cartography' => 'Cartography',
    'knowledge_geology' => 'Geology',
    'knowledge_agronomy' => 'Agronomy',
    'knowledge_health' => 'Medicine & Wellbeing',
    'knowledge_trade' => 'Trade & Logistics',
    'knowledge_defense' => 'Defence',

    // ── Knowledge descriptions ────────────────────────────────────────────────
    'desc_techs_construction' => 'What the colony builds lasts longer when people know how. Construction improves building processes and cuts the ongoing effort of maintenance.',
    'desc_techs_cartography' => 'The planet is vast, the maps are patchy. Cartography extends the reach of exploration and shows more clearly what lies out there.',
    'desc_techs_geology' => 'Under the dust lies what the colony needs. You just have to know where to dig. Geology improves extraction rates and helps find deposits sooner.',
    'desc_techs_agronomy' => 'The Agrarian Dome yields more when knowledge guides the work. Agronomy raises Organics output and secures the food supply for the long run.',
    'desc_techs_health' => 'Healthy colonists work better. Medicine & Wellbeing speeds up recovery, strengthens Trust and makes the workforce more resilient in a crisis.',
    'desc_techs_trade' => 'Those who know the market trade better. Trade & Logistics improves terms with the Travelling Merchant and makes Cantina deals more rewarding.',
    'desc_techs_defense' => 'Patrolling costs AP, but with good protective know-how every outing pays off more. Defence softens the impact of incidents and makes the colony more resilient.',

    // ── Ships ─────────────────────────────────────────────────────────────────
    'ship_drone' => 'Drone',
    'ship_corvette' => 'Corvette',
    'ship_freighter' => 'Freighter',

    // ── Ship descriptions ─────────────────────────────────────────────────────
    'desc_techs_drone' => 'Small, autonomous, tireless: the Drone flies without a crew. It scouts, radios data home and returns. For anything more, it falls short.',
    'desc_techs_corvette' => 'The Corvette is the only ship that can show force. But a patrolling Corvette doesn\'t trade, and a trading one doesn\'t patrol. That trade-off is the Director\'s to make.',
    'desc_techs_freighter' => 'Not a glamorous ship, but without a Freighter the colony is on its own. It brings and fetches what the colony cannot obtain alone.',

    // ── Personnel (advisor type keys) ─────────────────────────────────────────
    'techs_engineer' => 'Engineer',
    'techs_scientist' => 'Analyst',
    'techs_pilot' => 'Pilot',
    'techs_trader' => 'Consul',
    'techs_strategist' => 'Strategist',

    // ── Types ─────────────────────────────────────────────────────────────────
    'types_building' => 'Building',
    'types_buildings' => 'Buildings',
    'types_building_up' => 'build',
    'types_building_down' => 'demolish',
    'types_research' => 'Knowledge',
    'types_researchs' => 'Knowledge',
    'types_research_up' => 'research',
    'types_research_down' => 'destroy knowledge',
    'types_ship' => 'Ship',
    'types_ships' => 'Ships',
    'types_ship_up' => 'build ship',
    'types_ship_down' => 'scrap ship',
    'types_personell' => 'Advisor',
    'types_personells' => 'Advisors',
    'types_personell_up' => 'hire advisor',
    'types_personell_down' => 'dismiss advisor',

    // ── Purposes ──────────────────────────────────────────────────────────────
    'purposes_civil' => 'Civil',
    'purposes_industry' => 'Industry',
    'purposes_economy' => 'Economy',
    'purposes_politics' => 'Politics',
    'purposes_military' => 'Military',

    // ── Status chips (techtree overview) ─────────────────────────────────────
    'status_built' => 'Built',
    'status_available' => 'Available',
    'status_locked' => 'Locked',

    // ── Detail dialog ─────────────────────────────────────────────────────────
    'detail_type' => 'Type',
    'detail_status' => 'Status',
    'detail_level' => 'Level',
    'detail_required' => 'Requires',
    'detail_unlocks_next_level' => 'Unlocked from next level',
    // Split of detail_unlocks_next_level into "current" vs "next" (techtree
    // sidebar only) — detail_unlocks_next_level above stays untouched, it is
    // still used by the colony sidebar (hexview.blade.php).
    'detail_effects_current_level' => 'Effects of the current level',
    'detail_effects_next_level' => 'Effects of the next level',
    'detail_close' => 'Close',
    // Svelte frontend (ADR 0006): type labels and toasts, formerly hardcoded in techtree-view.js
    'type_building' => 'Building',
    'type_research' => 'Research',
    'type_ship' => 'Ship',
    'type_personell' => 'Advisor',
    'toast_level_reached' => ':name: level :level reached',
    'toast_invest_failed' => 'Investment failed.',

    // ── UI ────────────────────────────────────────────────────────────────────
    'tradeable' => 'tradeable',
    'decay' => 'Decay time',
    'max_level' => 'Max level',
    'moving_speed' => 'Speed',
    'costs and requirements' => 'Costs and requirements',

    // ── AP hints (when no AP available) ──────────────────────────────────────
    'hint_no_research_ap' => 'No AP left.',
    'hint_no_construction_ap' => 'No AP left.',

    // ── Advisor chip in techtree ──────────────────────────────────────────────
    'advisor_hired' => 'Hired',
    'advisor_available' => 'Available',
    'advisor_locked' => 'Locked',
    'advisor_placed' => 'Placed',
    'advisor_not_placed' => 'Not built',
    'detail_advisor_status' => 'Status',
    'detail_advisor_ap' => 'AP type',
    'detail_advisor_cost' => 'Hiring cost',
    'detail_advisor_link' => 'Manage in Advisor screen',

    // ── Instanced buildings + ships ───────────────────────────────────────────
    'detail_instances' => 'Instances',
    // Instance list in the techtree detail panel (A43, GDD techtree §11.4)
    'detail_instance_list' => 'On the map',
    'detail_instance_level' => 'Level :level / :max',
    'detail_instance_level_uncapped' => 'Level :level',
    'detail_instance_construction' => 'Construction site',
    'detail_instance_condition' => 'Condition :sp / :max',
    'detail_instance_tile_link' => 'Go to tile',
    'detail_count' => 'Count',
    'detail_ap_invest' => 'Invest AP',
    'detail_colony_link' => 'Build on colony',
    'detail_harvester_acquire_hint' => 'Harvesters are not built from the build menu: another one is only available through Orin\'s offer or a salvage mission. Then place it on an explored regolith tile.',

    // ── Error messages (techtree.error_<code>, see TechtreeController::orderFailed)
    // Apply to buildings, knowledge and ships alike — deliberately worded without
    // an entity type.
    'error_requires_building' => 'A required building is missing or its level is too low.',
    'error_requires_research' => 'The colony lacks the required knowledge.',
    'error_insufficient_resources' => 'Not enough resources for this upgrade.',
    'error_insufficient_ap_invested' => 'Not enough AP invested yet to complete the level.',
    'error_insufficient_supply' => 'Too few free colonists — expand a Residential Habitat first.',
    'error_max_level' => 'Maximum level already reached.',
    'error_knowledge_cc_gate' => 'Command Center level too low for this knowledge level — upgrade it first.',
    'error_building_ap_cap' => 'The construction crew cannot do more at this building today. It continues tomorrow — or at another building.',
    'error_knowledge_ap_cap' => 'No more than :cap AP can go into this knowledge today. It continues tomorrow — or with another knowledge.',
    'knowledge_ap_cap_tooltip' => 'This knowledge takes only a limited amount of AP per Sol. It continues tomorrow — or with another knowledge.',
    'knowledge_ap_cap_today' => 'Today :spent/:cap AP already put into this knowledge',
    'error_building_cc_gate' => 'Command Center level too low for this building level — a building level needs at least the same Command Center level.',
    'error_insufficient_ap' => 'Not enough AP available for this investment.',
    'error_use_colony_view' => 'Building, upgrading, repairing and demolishing all happen in the tile panel of the colony view.',
    'error_not_placed' => 'The building is not on the map yet — build it in the colony view first.',
    'error_min_level' => 'This building cannot be levelled down any further. The Command Center always stays at level 1 or above.',

    // Technical rejections — normally never seen by the player.
    'error_entity_not_found' => 'Unknown entity.',
    'error_invalid_mode' => 'Invalid action.',
    'error_unknown_type' => 'Unknown techtree type.',
    'error_lab_required' => 'Research needs an Analytics Lab.',
    'error_unknown_order' => 'Unknown order.',
    'error_order_failed' => 'The order could not be carried out.',

    // ── Canvas techtree (2026-10-18) ─────────────────────────────────────────
    'canvas_label' => 'Techtree: Command Center on top, every prerequisite above the buildings, knowledge, ships and advisors that depend on it.',
    'legend_label' => 'Legend',
    'legend_building' => 'Buildings',
    'legend_research' => 'Knowledge',
    'legend_ship' => 'Ships',
    'legend_advisor' => 'Advisors',
    'band_start' => 'Start',
    'band_cc' => 'CC Lv :level',
    'zoom_in' => 'Zoom in',
    'zoom_out' => 'Zoom out',
    'view_start' => 'Start',
    'view_overview' => 'Overview',
    'column_n' => 'Column :n of :total',
    'columns_label' => 'Columns',
    // Lab preview (no Analytics Lab yet): neutral notice, knowledge nodes show title only
    'preview_to_lab' => 'To the lab',
    'preview_lab_needed' => 'Lab required',
    'preview_reason_label' => 'Requirement',
    'preview_reason_lab' => 'Analytics Lab',
    'preview_reason_lab_needs' => 'The Analytics Lab itself still needs: :list',
    // Lab preview: bonus progress from the cantina (silent research).
    'preview_bonus_label' => 'Silent research',
    'preview_bonus_source' => 'from talks in the cantina',
];
