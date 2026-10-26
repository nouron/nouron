<?php

return [
    // ── Error messages (pathdepth.error_<code>, see PathDepthService::error)
    'error_unknown_entity' => 'No such family or character.',
    'error_not_met' => 'You have not met this character in this run yet.',
    'error_max_level' => 'The highest level has already been reached.',
    'error_building_level_cap' => 'The next level needs a higher building level.',
    'error_training_ap_cap' => 'No more than :cap AP can go into this training today. It continues tomorrow — or with another family.',
    'error_training_locked' => 'This training is still locked. The hangar needs a higher level.',
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
    'ui_training_ap_today' => 'Today :spent/:cap AP already put into this training',
    'ui_training_ap_cap_tooltip' => 'This training takes only a limited amount of AP per Sol. It continues tomorrow — or with another family.',
    'ui_locked_building' => 'Locked: :building needs level :level.',
    'ui_training_effect' => 'Each level: +:pct% yield on quantity rewards',
    'ui_training_effect_now' => 'Now +:now% · next level +:next%',
    'ui_training_effect_max' => 'Now +:now% (highest level)',
    'ui_training_missions' => 'Applies to: :list',
    'ui_training_unlock' => 'From hangar level :level',

    // ── Cantina: nurture relationship
    'ui_relationship_title' => 'Nurture relationship',
    'ui_not_met' => 'Not met yet',
    'ui_relationship_run_hint' => 'Familiarity grows only within this run. What you learn about a character stays in the codex.',
    'ui_relationship_met_only' => 'Only characters you have already spoken to in this run are listed.',

    // ── Cantina: conversation options (T43-G4)
    'ui_conversation_cap_tooltip' => 'This character only has so much time for conversation per Sol. Tomorrow is another day — or talk to someone else.',
    'ui_conversation_today' => 'Today :spent/:cap AP',
    // One name per option size is picked per Sol from these pools (any pool size works).
    'conversation_names' => [
        'short' => ['Say a quick hello', 'Just nod', 'Exchange a word', 'Raise a glass', 'Pass by the table', 'Ask how it is going'],
        'normal' => ['Talk over a drink', 'Listen', 'Sit together', 'Share a table', 'Trade stories', 'Hear them out'],
        'long' => ['Take the evening', 'Dive deep', 'Stay until closing time', 'Talk through the night', 'Leave the shift behind', 'Pour another round'],
    ],

    // ── Mission families
    'family_exploration' => 'Exploration',
    'family_salvage' => 'Salvage',
    'family_supply' => 'Supply',
    'family_trade' => 'Trade',
    'family_protection' => 'Protection',
];
