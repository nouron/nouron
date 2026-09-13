<?php

/**
 * Cantina character roster. `game_role` mirrors the "Game Role" field on each
 * character's sheet (docs/characters/*.md) and drives which mechanic a figure
 * is eligible for (A36, GDD §12 Kanal 1):
 *   - permanent: always-present fixture, no encounter (Tomas, spot_0)
 *   - dedicated: has its own bespoke mechanic (Orin/CorporateContactService,
 *     Zara/wager, Voss/auction — see BarService)
 *   - bar_trade: shares the generic anonymous-guest barter offer, with a
 *     personalized flavor line (lang/de/colony.php → bar_trade_flavor_*)
 *   - story_hook: flavor-only encounter, no resource/Credits effect at all
 *     (Leitplanke §12: not every figure gets an economic tie-in)
 *   - information: role reserved, no mechanic yet (Deva/Lenn — deferred,
 *     A36 follow-up, not part of this iteration)
 */
return [
    'bartender' => ['name' => 'Tomas',  'role' => 'Bartender', 'game_role' => 'permanent'],
    'smuggler' => ['name' => 'Dax',    'role' => 'Smuggler', 'game_role' => 'bar_trade'],
    'information_broker' => ['name' => 'Vesper', 'role' => 'Information Broker', 'game_role' => 'bar_trade'],
    'mechanic' => ['name' => 'Sarka',  'role' => 'Exhausted Mechanic', 'game_role' => 'bar_trade'],
    'corporate_rep' => ['name' => 'Orin',   'role' => 'Corporate Representative', 'game_role' => 'dedicated'],
    'founder' => ['name' => 'Aldra',  'role' => 'Former Colony Founder', 'game_role' => 'story_hook'],
    'gambler' => ['name' => 'Zara',   'role' => 'Professional Gambler', 'game_role' => 'dedicated'],
    'ai_researcher' => ['name' => 'Lenn',   'role' => 'Unlicensed AI Researcher', 'game_role' => 'information'],
    'veteran' => ['name' => 'Deva',   'role' => 'Military Veteran', 'game_role' => 'information'],
    'stranger' => ['name' => null,     'role' => 'Mysterious Figure', 'game_role' => 'story_hook'],
    'doctor' => ['name' => 'Maret',  'role' => 'Colony Doctor', 'game_role' => 'bar_trade'],
    'preacher' => ['name' => 'Sorel',  'role' => 'Frontier Preacher', 'game_role' => 'story_hook'],
    'prospector' => ['name' => 'Fen',    'role' => 'Veteran Prospector', 'game_role' => 'bar_trade'],
    'mercenary' => ['name' => 'Juno',   'role' => 'Retired Mercenary', 'game_role' => 'bar_trade'],
    'scrap_dealer' => ['name' => 'Voss',   'role' => 'Scrap Dealer', 'game_role' => 'dedicated'],
];
