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
    'error_conversation_option_locked' => 'This conversation needs cantina level :level.',

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
    // Hint in the character dialog for characters not met yet.
    'ui_relationship_not_met_hint' => 'You have not exchanged a word yet. After your first conversation you can nurture the relationship here.',
    // Relationship level names 0–5 (owner 2026-11-11).
    'relationship_level_0' => 'stranger',
    'relationship_level_1' => 'newly met',
    'relationship_level_2' => 'acquainted',
    'relationship_level_3' => 'familiar',
    'relationship_level_4' => 'friends',
    'relationship_level_5' => 'like family',

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

    // ── Cantina: line of a character for a locked conversation option (owner 2026-11-13).
    // TODO content-writer: drafts — wording may vary freely, pools may grow.
    'option_locked_lines' => [
        'default' => [
            'You can hardly hear yourself think in here. Another time, when there is more room.',
            'There is no room for a proper talk here. Expand the cantina.',
            'Not here, not this cramped. Maybe once the cantina is bigger.',
        ],
        'bartender' => [
            'I have no room for more here — expand the cantina.',
            'Barely any elbow room behind this counter. More space, more time for you.',
            'Long evenings? I need a bigger cantina for that first.',
        ],
    ],
];
