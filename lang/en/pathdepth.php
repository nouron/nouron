<?php

return [
    // ── Error messages (pathdepth.error_<code>, see PathDepthService::error)
    'error_unknown_entity' => 'No such family or character.',
    'error_not_met' => 'You have not met this character in this run yet.',
    'error_max_level' => 'The highest level has already been reached.',
    'error_building_level_cap' => 'The next level needs a higher building level.',
    'error_insufficient_ap' => 'Not enough AP available.',

    'error_conversation_ap_cap' => 'This character will not take more than :cap AP today. The conversation continues tomorrow — or talk to someone else.',
    'error_invalid_option' => 'No such conversation size.',

    // ── Hangar: training
    'ui_training_title' => 'Training',
    'ui_experience_label' => 'Mission experience',
    'ui_level' => 'Level :level/:max',
    'ui_invest_progress' => ':spent/:needed AP',
    'ui_train' => 'Train',
    'ui_get_to_know' => 'Get to know',
    'ui_level_max' => 'Highest level',
    'ui_locked_building' => 'Locked: :building needs level :level.',

    // ── Cantina: nurture relationship
    'ui_relationship_title' => 'Nurture relationship',
    'ui_not_met' => 'Not met yet',
    'ui_relationship_run_hint' => 'Familiarity grows only within this run. What you learn about a character stays in the codex.',
    'ui_relationship_met_only' => 'Only characters you have already spoken to in this run are listed.',

    // ── Cantina: conversation options (T43-G4)
    'ui_conversation_cap_tooltip' => 'This character only has so much time for conversation per Sol. Tomorrow is another day — or talk to someone else.',
    'ui_conversation_today' => 'Today :spent/:cap AP',
    // TODO content-writer: name pools per option size (placeholder proposals); one name is picked per Sol.
    'conversation_names' => [
        'short' => ['Say a quick hello', 'Just nod', 'Exchange a word'],
        'normal' => ['Talk over a drink', 'Listen', 'Sit together'],
        'long' => ['Take the evening', 'Dive deep', 'Stay until closing time'],
    ],

    // ── Mission families
    'family_exploration' => 'Exploration',
    'family_salvage' => 'Salvage',
    'family_supply' => 'Supply',
    'family_trade' => 'Trade',
    'family_protection' => 'Protection',
];
