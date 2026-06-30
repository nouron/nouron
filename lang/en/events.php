<?php

return [
    // Techtree events
    'techtree_level_up_finished' => 'Building :tech on colony :colony has been upgraded.',
    'techtree_level_down' => 'Structure :tech on colony :colony has lost a level due to decay.',
    'techtree_research_finished' => 'Research :research on colony :colony completed.',

    // Galaxy / Fleet events
    'galaxy_fleet_arrived' => 'Fleet :fleet has arrived at coordinates :coords.',
    'galaxy_combat' => 'Incident at colony :colony: :attacker encounters :defender.',
    'galaxy_trade' => 'Trade concluded at colony :colony.',

    // Onboarding events (Phase 3e)
    'onboarding_nexus_briefing' => 'Colony :colony — status report Nexus outpost. Command Center and Harvester operational. First priority: build Residential Habitat. Without personnel, action points remain critically limited.',

    // TODO: trigger when a building first drops a status level in the player's session
    'onboarding_decay' => 'Structure :tech on colony :colony showing decay damage — schedule repair AP.',

    // TODO: trigger when colony trust first drops below threshold
    'onboarding_trust' => 'Colonist trust on :colony has dropped — investigate cause.',

    // Merchant events
    'merchant_visit' => 'Travelling Merchant in the Cantina on colony :colony — offer is time-limited.',

    // Player action log
    'colony_tile_explored' => 'Sector on colony :colony uncovered.',
    'colony_tile_deep_scanned' => 'Deep scan on colony :colony complete — findings logged.',
    'colony_building_placed' => 'Structure :tech on colony :colony erected.',
    'trade_bar_accepted' => 'Bar offer on colony :colony accepted.',
    'trade_merchant_purchase' => 'Merchant item :item_id on colony :colony acquired.',
    'techtree_advisor_hired' => 'Advisor :advisor_type on colony :colony commissioned.',
    'run_sol_advanced' => 'Sol :sol on colony :colony completed.',

    // Fallback (shown when no specific template matches)
    'unknown' => 'Event: :event',
];
