<?php

return [
    'page_title' => 'Colony Log',
    'tab_log' => 'Log',
    'tab_nexus' => 'Nexus Comms',
    'empty_log' => 'No events logged yet.',
    'empty_nexus' => 'No Nexus messages received.',
    'sol_label' => 'Sol :sol',

    // Event labels — nested to match dot-notation traversal (comm_log.events.colony.building_placed)
    'events' => [
        'colony' => [
            'building_placed' => 'Building placed',
            'building_invested' => 'Building upgraded',
            'building_repaired' => 'Building repaired',
            'renamed' => 'Colony renamed',
            'tile_explored' => 'Sector explored',
            'tile_deep_scanned' => 'Deep scan performed',
        ],
        'merchant' => [
            'visit' => 'Travelling Merchant announced',
        ],
        'techtree' => [
            'level_up_finished' => 'Research completed',
            'level_down' => 'Research setback (decay)',
            'advisor_hired' => 'Advisor hired',
        ],
        'trade' => [
            'bar_accepted' => 'Cantina offer accepted',
            'merchant_purchase' => 'Purchased from merchant',
        ],
        'galaxy' => [
            'fleet_arrived' => 'Fleet arrived',
            'trade' => 'Trade route concluded',
            'encounter' => 'Encounter in space',
        ],
        'encounter_won' => 'Encounter resolved',
        'encounter_lost' => 'Encounter lost',
    ],

    // Nexus messages — nested (comm_log.nexus_events.onboarding.nexus_briefing.title)
    'nexus_events' => [
        'onboarding' => [
            'nexus_briefing' => [
                'title' => 'Nexus First Contact',
                'body' => 'Connection to Nexus Central established. Your concession is registered. We are monitoring your colony. Ensure that you meet the agreed mission objectives.',
                'badge' => 'First Contact',
            ],
        ],
        'run' => [
            'phase1_complete' => [
                'title' => 'Phase 1 complete',
                'body' => 'Nexus confirms: your colony has successfully completed Phase 1. Phase 2 begins. Further requirements have been transmitted.',
                'badge' => 'Phase complete',
            ],
            'nexus_warning_sol30' => [
                'title' => 'Nexus Warning',
                'body' => 'Nexus Protocol §12.4: your colony is showing insufficient progress. We demand demonstrable objective completion by Sol 50. Sanctions will follow otherwise.',
                'badge' => 'Warning',
            ],
            'nexus_warning_sol50' => [
                'title' => 'Nexus Warning (critical)',
                'body' => 'Final notice: no objectives reached by Sol 50. Sanctions take effect from Sol 65. This is your last opportunity to correct course.',
                'badge' => 'Critical Warning',
            ],
            'nexus_sanction_sol65' => [
                'title' => 'Nexus Sanction imposed',
                'body' => 'Pursuant to concession contract §7: one advisor has been temporarily suspended. Debt balance under review. Fulfil outstanding objectives to avoid further measures.',
                'badge' => 'Sanction',
            ],
            'nexus_countdown_sol80' => [
                'title' => 'Mission Countdown',
                'body' => 'Sol 80 reached. Remaining time for your mission is approaching its end. Nexus expects a final mission report.',
                'badge' => 'Countdown',
            ],
            'run_completed' => [
                'title' => 'Mission successfully completed',
                'body' => 'Nexus confirms: all mission objectives have been met. Your concession is assessed positively. Final protocol transmitted.',
                'badge' => 'Success',
            ],
            'run_failed_trust' => [
                'title' => 'Mission failed — Trust loss',
                'body' => 'Nexus Protocol §3.1: colonist trust has critically fallen below the minimum threshold. The concession is terminated.',
                'badge' => 'Failed',
            ],
            'run_failed_nexus_debt' => [
                'title' => 'Mission failed — Debt Protocol',
                'body' => 'Nexus Protocol §15.2: Nexus debt has exceeded the concession limit. The mission is forcibly terminated.',
                'badge' => 'Failed',
            ],
            'run_failed_time' => [
                'title' => 'Mission failed — Time limit',
                'body' => 'The Sol limit was reached without completing the required objectives. Nexus terminates the concession.',
                'badge' => 'Failed',
            ],
        ],
    ],

    // Rich descriptions for Log entries (with :param placeholders)
    'desc' => [
        'building_placed' => ':name placed.',
        'building_invested' => ':ap AP invested in :name (:done / :total AP).',
        'building_repaired' => ':name repaired (:current / :max condition).',
        'building_leveled_up' => ':ap AP invested in :name. Construction complete — Level :level reached.',
        'level_up' => 'Research :name completed.',
        'level_up_level' => 'Research :name advanced to Level :level.',
        'level_up_knowledge' => 'Knowledge :name acquired.',
        'level_up_knowledge_level' => 'Knowledge :name advanced to Level :level.',
        'level_down' => 'Level for :name dropped due to lack of maintenance.',
        'level_down_level' => 'Level for :name dropped to :level due to lack of maintenance.',
        'level_down_ship' => 'Ship :name destroyed by decay.',
        'advisor_hired' => '":type" hired.',
        'advisor_hired_cost' => '":type" hired. Cost: :credits CR.',
        'bar_accepted' => 'Cantina offer accepted.',
        'bar_accepted_trade' => 'Traded :give_amount :give for :get_amount :get.',
        'merchant_purchase' => 'Purchased from the Travelling Merchant.',
        'merchant_visit' => 'Travelling Merchant announced nearby.',
        'fleet_arrived' => 'Fleet arrived at destination.',
        'galaxy_trade' => 'Trade route concluded.',
        'galaxy_trade_credits' => 'Trade route concluded (+:credits CR).',
        'encounter' => 'Encounter with unknown vessel.',
        'tile_explored' => 'New sector explored.',
        'tile_deep_scanned' => 'Deep scan of a sector performed.',
        'tile_deep_scanned_coords' => 'Deep scan of sector (:q/:r) performed.',
        'colony_renamed' => 'Colony renamed.',
    ],

    // Area icons (Bootstrap Icons class) — flat, keys are simple strings (no dots)
    'area_icons' => [
        'colony' => 'bi-hexagon',
        'techtree' => 'bi-diagram-3',
        'trade' => 'bi-shop',
        'galaxy' => 'bi-stars',
        'run' => 'bi-flag',
        'nexus' => 'bi-broadcast-pin',
        'merchant' => 'bi-bag',
        'default' => 'bi-journal-text',
    ],
];
