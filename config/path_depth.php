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
        'mission_family' => [1 => 6, 2 => 9, 3 => 12, 4 => 16, 5 => 20],
        'character' => [1 => 4, 2 => 6, 3 => 8, 4 => 10, 5 => 12],
    ],

    // Minimum building level required to reach level N.
    'building_gate' => [1 => 1, 2 => 1, 3 => 2, 4 => 2, 5 => 3],

    // Building (BuildingId value) whose level caps each kind: Hangar = 44, Bar (cantina) = 52.
    'buildings' => [
        'mission_family' => 44,
        'character' => 52,
    ],

    // Training AP limit per mission family and Sol (T43-G2, owner 2026-10-25: "limited
    // AP per Sol" also holds in the hangar). PLACEHOLDER: base 4 is an ASSUMPTION copied
    // from game.knowledge_ap_cap_per_sol.base. null switches the rule off. Counter key
    // "training:<family>" in App\Services\DailyApCapService. The hangar has no earmarked
    // bonus AP (investBonus is lab-only), so no exemption is needed.
    'training_ap_cap_per_sol' => [
        'base' => 4,
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

    // Family key => mission catalog keys. (find_salvage, salvage_sweep and
    // harvester_salvage -> salvage is a plan assumption.)
    'families' => [
        'exploration' => ['mission_recon_flight', 'mission_data_sweep', 'mission_long_range_expedition'],
        'salvage' => ['mission_find_salvage', 'mission_salvage_sweep', 'mission_ruin_expedition', 'mission_harvester_salvage'],
        'supply' => ['mission_supply_run', 'mission_aid_transport'],
        'trade' => ['mission_courier_run', 'mission_trade_convoy'],
        'protection' => ['mission_escort_convoy', 'mission_perimeter_patrol'],
    ],

    // ── Cantina conversations (T43-G4). ALL VALUES ARE PLACEHOLDERS pending measurement.
    // Conversation option key => AP spent (progress is proportional to AP; no bulk discount,
    // no extra yield). Display names come from a pool in lang pathdepth.conversation_names.<key>.
    'conversation_options' => ['short' => 1, 'normal' => 2, 'long' => 4],

    // AP one character accepts per Sol (shared counter DailyApCapService, key
    // "conversation:<character>"). null switches the rule off. Earmarked bonus AP is exempt.
    'conversation_ap_cap_per_sol' => ['base' => 6],
];
