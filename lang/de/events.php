<?php

return [
    // Techtree events
    'techtree_level_up_finished'  => 'Gebäude :tech auf Kolonie :colony wurde ausgebaut.',
    'techtree_level_down'         => 'Struktur :tech auf Kolonie :colony ist durch Verfall um eine Stufe gesunken.',
    'techtree_research_finished'  => 'Forschung :research auf Kolonie :colony abgeschlossen.',

    // Galaxy / Fleet events
    'galaxy_fleet_arrived'        => 'Flotte :fleet ist bei Koordinaten :coords angekommen.',
    'galaxy_combat'               => 'Kampf auf Kolonie :colony: :attacker greift :defender an.',
    'galaxy_trade'                => 'Handelsgeschäft auf Kolonie :colony abgeschlossen.',

    // Onboarding events (Phase 3e)
    'onboarding_nexus_briefing'   => 'Kolonie :colony — Statusbericht Nexus-Stützpunkt. Commandcenter und Harvester operationsbereit. Erste Priorität: Wohnhabitat errichten. Ohne Personal bleiben Aktionspunkte kritisch begrenzt.',

    // TODO: trigger when a building first drops a status level in the player's session
    'onboarding_decay'            => 'Struktur :tech auf Kolonie :colony zeigt Verfallsschäden — Reparatur-AP einplanen.',

    // TODO: trigger when colony moral first drops below threshold
    'onboarding_trust'            => 'Vertrauen der Kolonisten auf :colony gesunken — Ursache prüfen.',

    // Merchant events
    'merchant_visit'              => 'Reisender Händler in der Cantina auf Kolonie :colony — Angebot ist zeitlich begrenzt.',

    // Player action log
    'colony_tile_explored'        => 'Sektor auf Kolonie :colony aufgedeckt.',
    'colony_tile_deep_scanned'    => 'Tiefenscan auf Kolonie :colony abgeschlossen — Befund protokolliert.',
    'colony_building_placed'      => 'Struktur :tech auf Kolonie :colony errichtet.',
    'trade_bar_accepted'          => 'Bar-Angebot auf Kolonie :colony angenommen.',
    'trade_merchant_purchase'     => 'Händlerware :item_id auf Kolonie :colony erworben.',
    'techtree_advisor_hired'      => 'Berater :advisor_type auf Kolonie :colony in Dienst gestellt.',
    'run_sol_advanced'            => 'Sol :sol auf Kolonie :colony abgeschlossen.',

    // Fallback (shown when no specific template matches)
    'unknown'                     => 'Ereignis: :event',
];
