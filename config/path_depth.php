<?php

/**
 * Path depth (T41): hangar mission families and cantina characters have levels that
 * the player raises with AP. ALL VALUES ARE PLACEHOLDERS (owner 2026-10-24) pending
 * balance measurement — change them only here.
 */
return [
    // Highest level of a family or character.
    'max_level' => 5,

    // AP needed to reach level N (index 1..5), per kind.
    'levelup_ap' => [
        // AP scale 2026-11-13 §11.2 (T-2) and §11.4 (S1): the first conversation makes
        // a character known, every further level needs 3 AP.
        'mission_family' => [1 => 3, 2 => 5, 3 => 6, 4 => 8, 5 => 10],
        'character' => [1 => 1, 2 => 3, 3 => 3, 4 => 3, 5 => 3],
    ],

    // Minimum hangar level required to reach mission family level N. Mission families only:
    // cantina characters have no level gate (owner 2026-11-13), their conversation options
    // are staged by cantina level instead (conversation_option_cantina_level below).
    'building_gate' => [1 => 1, 2 => 1, 3 => 2, 4 => 2, 5 => 3],

    // Building (BuildingId value) per kind: Hangar = 44 caps family levels (building_gate),
    // Bar (cantina) = 52 unlocks the conversation options.
    'buildings' => [
        'mission_family' => 44,
        'character' => 52,
    ],

    // Training AP limit per mission family and Sol (T43-G2, owner 2026-10-25: "limited
    // AP per Sol" also holds in the hangar). Cap = base + the pilot's specialist bonus
    // (game.advisor.cap_bonus_per_rank, AP scale 2026-11-13 §11.1). null switches the rule
    // off. Counter key "training:<family>" in App\Services\DailyApCapService. The hangar has
    // no earmarked bonus AP (investBonus is lab-only), so no exemption is needed.
    'training_ap_cap_per_sol' => [
        'base' => 1,
    ],

    // Hangar level a mission family needs before it can be trained (T43-G3; designer
    // assumption confirmed by the owner 2026-10-25). Placeholder pending balance.
    'family_unlock_hangar_level' => [
        'exploration' => 1,
        'salvage' => 1,
        'supply' => 2,
        'trade' => 2,
        'protection' => 3,
    ],

    // Payout factor = 1 + yield_per_level * family level (scalable reward types only).
    'yield_per_level' => 0.05,

    // Family key => mission catalog keys. (find_salvage and salvage_sweep
    // -> salvage is a plan assumption.)
    'families' => [
        'exploration' => ['mission_recon_flight', 'mission_data_sweep', 'mission_long_range_expedition'],
        'salvage' => ['mission_find_salvage', 'mission_salvage_sweep', 'mission_ruin_expedition'],
        'supply' => ['mission_supply_run', 'mission_aid_transport'],
        'trade' => ['mission_courier_run', 'mission_trade_convoy'],
        'protection' => ['mission_escort_convoy', 'mission_perimeter_patrol'],
    ],

    // ── Cantina conversations (T43-G4, variant C 2026-11-13): ONE conversation per character
    // and Sol, the player picks its length. Option key => AP spent; an option always costs
    // its full price, AP beyond the next relationship level are lost (no carry-over).
    // Display names come from a pool in lang pathdepth.conversation_names.<key>.
    'conversation_options' => ['short' => 1, 'long' => 2],

    // Cantina level each conversation option needs (owner decision 2026-11-13; AP scale
    // §11.4: the long conversation from cantina level 3).
    'conversation_option_cantina_level' => ['short' => 1, 'long' => 3],

    // AP one character accepts per Sol (counter DailyApCapService, key
    // "conversation:<character>"; owner 2026-11-13: at most 2). An option above it is not
    // selectable. null switches the limit off (the one-conversation rule stays).
    'conversation_ap_cap_per_sol' => ['base' => 2],
];
