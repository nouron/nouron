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
    'building_securityHub' => 'Security Hub',
    'building_uplinkStation' => 'Uplink Station',
    'building_tradingPost' => 'Trading Post',

    // ── Building descriptions (desc_techs_* keys) ─────────────────────────────
    'desc_techs_commandCenter' => 'Everything converges here — reports, plans, decisions. The Command Center is not a glamorous building; it is the nervous system of the colony. Its level determines how far the colony can grow.',
    'desc_techs_housingComplex' => 'A residential habitat is more than shelter — it is the promise that this is not a temporary camp. Every new unit increases the supply capacity of the entire installation.',
    'desc_techs_harvester' => 'Day and night it breaks Regolith from the ground — somewhere beyond the colony zone, tireless, without pause. Without the Harvester, every construction plan is just paper.',
    'desc_techs_bioFacility' => 'Beneath the dome grows what the colony needs to survive. The Agrarian Dome produces Organics — and is a mandatory prerequisite for the colony to grow further at all.',
    'desc_techs_sciencelab' => 'This is where those who ask sit. The Analytics Lab translates measurement data into insights — and insights into progress. Without it, the tech tree stays closed.',
    'desc_techs_infirmary' => 'Not every accident happens during construction. The Medical Station tends to injured colonists and stabilises Trust in difficult phases — the place everyone hopes never to truly need.',
    'desc_techs_bar' => 'The Cantina is the only place where colonists can forget, just for a moment, how thin the air is outside. Strangers from transit bring news, goods — and sometimes offers worth hearing.',
    'desc_techs_hangar' => 'Ships need a place to land, refuel, and be maintained. The Hangar is the starting point of every mission and gives the colony eyes to the outside.',
    'desc_techs_temple' => 'Some things cannot be paid for in Regolith and Credits. The Sacred Site provides space for what keeps colonists sane — ritual, community, a moment of stillness.',
    'desc_techs_monument' => 'At some point you ask yourself: what is all this for? The Colonial Monument provides an answer — visible, lasting, for everyone. It anchors the history of this colony in the soil of the planet.',
    'desc_techs_securityHub' => 'No Security Hub makes the colony invulnerable — but it ensures that incidents don\'t escalate. Its operation dampens negative events and gives colonists the feeling that someone is watching.',
    'desc_techs_uplinkStation' => 'Without an Uplink Station the colony is silent to the Nexus — and the Nexus only acts on request. It unlocks direct Compound import and keeps the only communication channel open.',
    'desc_techs_tradingPost' => 'A well-maintained Trading Post signals to all travellers that this colony does business. The Consul works more efficiently, the Travelling Merchant trades on better terms.',

    // ── Knowledge ────────────────────────────────────────────────────────────
    'knowledge_construction' => 'Construction',
    'knowledge_cartography' => 'Cartography',
    'knowledge_geology' => 'Geology',
    'knowledge_agronomy' => 'Agronomy',
    'knowledge_health' => 'Medicine & Wellbeing',
    'knowledge_trade' => 'Trade & Logistics',
    'knowledge_defense' => 'Defence',

    // ── Knowledge descriptions ────────────────────────────────────────────────
    'desc_techs_construction' => 'Everything the colony builds holds better when the right people know how. Construction improves building processes and reduces the ongoing effort for maintenance.',
    'desc_techs_cartography' => 'The planet is vast, the maps are incomplete. Cartography extends the reach of exploration — and gives the colony a clearer view of what lies out there.',
    'desc_techs_geology' => 'Beneath the dust lies everything the colony needs — if you know where to dig. Geology improves extraction rates and helps identify resource deposits earlier.',
    'desc_techs_agronomy' => 'The Agrarian Dome yields more when the work is guided by knowledge. Agronomy increases Organics output and stabilises the supply situation long-term.',
    'desc_techs_health' => 'Healthy colonists are productive colonists. Medicine & Wellbeing improves recovery rates, strengthens Trust, and makes the workforce more resilient against crisis periods.',
    'desc_techs_trade' => 'Those who know the market trade better. Trade & Logistics improves terms with the Travelling Merchant and makes Cantina deals more rewarding.',
    'desc_techs_defense' => 'Patrolling costs AP — but good protective knowledge makes every deployment more efficient. Defence reduces the impact of incidents and strengthens colony resilience.',

    // ── Ships ─────────────────────────────────────────────────────────────────
    'ship_drone' => 'Drone',
    'ship_corvette' => 'Corvette',
    'ship_freighter' => 'Freighter',

    // ── Ship descriptions ─────────────────────────────────────────────────────
    'desc_techs_drone' => 'Small, autonomous, tireless — the Drone needs no hangar and no pilot. It scouts, transmits data, and returns. For anything beyond that, it falls short.',
    'desc_techs_corvette' => 'The Corvette is the only ship that can show force — but a patrolling Corvette doesn\'t trade, and a trading Corvette doesn\'t patrol. That trade-off is the Director\'s to make.',
    'desc_techs_freighter' => 'No glamorous ship — but without a Freighter the colony is left to its own devices. It transports, delivers, and collects what the colony could not obtain alone.',

    // ── Personnel (advisor type keys) ─────────────────────────────────────────
    'techs_engineer' => 'Engineer',
    'techs_scientist' => 'Scientist',
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
    'detail_close' => 'Close',

    // ── UI ────────────────────────────────────────────────────────────────────
    'tradeable' => 'tradeable',
    'decay' => 'Decay time',
    'max_level' => 'Max level',
    'moving_speed' => 'Speed',
    'costs and requirements' => 'Costs and requirements',

    // ── AP hints (when no AP available) ──────────────────────────────────────
    'hint_no_research_ap' => 'No Research AP available — hire a Scientist advisor.',
    'hint_no_construction_ap' => 'No Construction AP available — hire an Engineer advisor.',

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
    'detail_count' => 'Count',
    'detail_ap_invest' => 'Invest AP',
    'detail_colony_link' => 'Build on colony',
];
