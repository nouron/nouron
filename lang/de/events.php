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

    // Fallback (shown when no specific template matches)
    'unknown'                     => 'Ereignis: :event',
];
