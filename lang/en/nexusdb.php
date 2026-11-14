<?php

/**
 * Nexus Database — UI strings for the concept glossary screen.
 *
 * Localization: lang/en/nexusdb.php
 */
return [

    // Page header
    'page_title' => 'Nexus Database',
    'page_subtitle' => 'Reference for game mechanics and terminology.',

    // Concept cards
    'tab_concepts' => 'Concepts',
    'tab_buildings' => 'Buildings',
    'tab_knowledge' => 'Knowledge',
    'tab_ships' => 'Ships',
    'tab_advisors' => 'Advisors',
    'asset_nexus_cost' => 'Nexus price',
    'asset_delivery' => 'Delivery time',
    'asset_delivery_value' => ':count Sol',
    'building_requires' => 'Requirement',
    'building_requires_none' => 'none',
    'building_requires_cc' => 'Command Center Lv:level',
    'building_requires_building' => ':name Lv:level',
    'building_cost_place' => 'Placing',
    'building_cost_free' => 'free',
    'building_colonists' => 'Colonists',
    'building_max_level' => 'Max level',
    'building_levels' => 'Upgrade levels',
    'building_col_level' => 'Level',
    'building_col_ap' => 'AP',
    'building_col_regolith' => 'Regolith',
    'building_focus_note' => 'This is one of three focus buildings. You choose where the focus lies first: knowledge, relations or missions.',

    // Advisor ranks and knowledge effects (Nexus-DB tabs)
    'advisor_ranks' => 'Ranks',
    'advisor_col_rank' => 'Rank',
    'advisor_col_cost' => 'One-time (Cr)',
    'advisor_col_upkeep' => 'Upkeep (Cr/Sol)',
    'advisor_col_bonus' => 'Effect',
    'advisor_cost_hire' => 'Hiring',
    'advisor_cost_promotion' => 'Promotion',
    'advisor_focus' => 'Belongs to focus building',
    'bonus_ap' => ':n AP per Sol',
    'bonus_ap_cap' => 'AP cap: :n AP per building and Sol',
    'bonus_ap_cap_lab' => 'Lab cap: :n AP per knowledge and Sol',
    'bonus_ap_cap_training' => 'Training cap: :n AP per training and Sol',
    'bonus_mission_chance' => '+:n % mission success chance',
    'bonus_ship_discount' => 'Ship negotiation: :n Cr discount per invested AP',
    'bonus_merchant_forecast' => 'Merchant visit announced :n Sol ahead',
    'effect_trust' => ':n trust',
    'knowledge_col_effect' => 'Effect',
    'knowledge_effect_none' => '—',

    'concept_supply_title' => 'Supply',
    'concept_supply_body' => 'Supply denotes the colony-wide operational capacity, abstracted from power, water, and personnel infrastructure. Each commissioned unit occupies a fixed share of that capacity. Once the capacity limit is reached, no further unit can be commissioned; existing units remain unaffected. Capacity expansion is achieved through higher-level Command Centers or additional Residential Habitats.',

    'concept_trust_title' => 'Trust',
    'concept_trust_body' => 'Trust denotes the approval index of the colonists toward colony management, scaled from 0 to 100. The index is recalculated each Sol: starting from a base value of 50, the contributions of buildings, knowledge, ships and the Sol\'s events are added and food shortage is deducted. The index is divided into five bands that modify the colony\'s Action Point pool per Sol: Unrest (0–14) −2 AP, Uneasy (15–39) −1 AP, Stable (40–59) no effect, Content (60–79) +1 AP, Euphoric (80–100) +2 AP. Should the index fall below 5, the concession is revoked. The exact index value and individual contributions are only reported once an Analytics Lab is in operation; without a lab, only the band is visible.',

    'concept_sol_title' => 'Sol',
    'concept_sol_body' => 'Sol denotes the standard time unit of a concession operation — defined as one complete planetary day at the colony\'s location. Each Sol executes a full calculation cycle: resource production, wear calculation, pending deliveries, event processing. All time-related parameters in the colony ledger are expressed in Sols.',

    'concept_ap_title' => 'Action Points',
    'concept_ap_body' => 'Action Points are provided per Sol by assigned advisor personnel and feed a single colony-bound pool. Construction, research, exploration and trade measures are all funded from this pool alike. Unused AP expire at Sol end — carry-over to subsequent Sols is not provided. Without sufficient AP availability, construction, research, and maintenance measures cannot be performed.',

    'concept_decay_title' => 'Decay',
    'concept_decay_body' => 'All colony buildings are subject to continuous status loss through regular operational wear. A building\'s status value decreases automatically each Sol by a fixed amount. When the status value reaches zero, the building loses one operational level. Regular Construction AP input from Engineer personnel is required to compensate for status loss.',

    'concept_repair_title' => 'Repair',
    'concept_repair_body' => 'Repair measures increase a building\'s status value through targeted AP and resource expenditure. Each repair operation raises the status value toward its maximum. Repairs to colony buildings (excluding Command Center and Harvester) additionally require Regolith. Without regular maintenance, status continues to decline until the building becomes inoperable.',

    'concept_nexus_title' => 'Nexus',
    'concept_nexus_body' => 'The Nexus is the interstellar administrative authority holding concession sovereignty over all recognised colonies. Infrastructure and ship requests are processed against Credits or Nexus credit. The Director is obligated to settle all credit liabilities within the concession period. In the event of credit limit breach or failure to meet concession objectives, the Nexus reserves the right to revoke the operating licence.',

    'concept_colonists_title' => 'Colonists',
    'concept_colonists_body' => 'Colonists denote the workforce and inhabitants of a concession colony. The aggregated approval index (Trust) is the primary social metric; individuals are not tracked in the system. Approval determines workforce readiness and thereby the number of Action Points available per Sol. Should Trust fall below the minimum, the concession is terminated.',

    'concept_focus_title' => 'Focus buildings',
    'concept_focus_body' => 'Lab, Hangar and Cantina are the three focus buildings. Each stands for its own focus: the Lab for knowledge, the Hangar for missions and ships, the Cantina for relations — encounters, trade and requests. One focus building is part of the basic setup of the first phase; you choose which one to build first, and that shapes the run. You can build the second and third only once the Command Center is upgraded far enough. Each focus building has an advisor assigned: Analyst to the Lab, Pilot to the Hangar, Consul to the Cantina. Their advisor slot can only be filled once the building stands. The Engineer belongs to no focus building — he is the fixed first advisor and takes care of construction.',

];
