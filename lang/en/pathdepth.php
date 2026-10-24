<?php

return [
    // ── Error messages (pathdepth.error_<code>, see PathDepthService::error)
    'error_unknown_entity' => 'No such family or character.',
    'error_not_met' => 'You have not met this character in this run yet.',
    'error_max_level' => 'The highest level has already been reached.',
    'error_building_level_cap' => 'The next level needs a higher building level.',
    'error_insufficient_ap' => 'Not enough AP available.',

    // ── Hangar: training
    'ui_training_title' => 'Training',
    'ui_experience_label' => 'Mission experience',
    'ui_level' => 'Level :level/:max',
    'ui_invest_progress' => ':spent/:needed AP',
    'ui_invest' => 'Invest', // TODO content-writer: confirm wording
    'ui_level_max' => 'Highest level',
    'ui_locked_building' => 'Locked: :building needs level :level.',

    // ── Cantina: nurture relationship
    'ui_relationship_title' => 'Nurture relationship',
    'ui_not_met' => 'Not met yet',
    'ui_relationship_run_hint' => 'Familiarity only counts for this run. What you learn about a character stays in the codex across all runs.',
    'ui_relationship_met_only' => 'Only characters you have met in this run appear here.',

    // ── Mission families
    'family_exploration' => 'Exploration',
    'family_salvage' => 'Salvage',
    'family_supply' => 'Supply',
    'family_trade' => 'Trade',
    'family_protection' => 'Protection',
];
