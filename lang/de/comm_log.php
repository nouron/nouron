<?php

return [
    'page_title'  => 'Kolonieprotokoll',
    'tab_log'     => 'Protokoll',
    'tab_nexus'   => 'Nexus-Funk',
    'empty_log'   => 'Noch keine Ereignisse protokolliert.',
    'empty_nexus' => 'Keine Nexus-Nachrichten empfangen.',
    'sol_label'   => 'Sol :sol',

    // Event labels — nested to match dot-notation traversal (comm_log.events.colony.building_placed)
    'events' => [
        'colony' => [
            'building_placed'   => 'Gebäude platziert',
            'building_invested' => 'Gebäude ausgebaut',
            'building_repaired' => 'Gebäude repariert',
            'renamed'           => 'Kolonie umbenannt',
            'tile_explored'     => 'Sektor erkundet',
            'tile_deep_scanned' => 'Tiefen-Scan durchgeführt',
        ],
        'merchant' => [
            'visit' => 'Reisender Händler angekündigt',
        ],
        'techtree' => [
            'level_up_finished' => 'Forschung abgeschlossen',
            'level_down'        => 'Forschungsrückschritt (Verfall)',
            'advisor_hired'     => 'Berater eingestellt',
        ],
        'trade' => [
            'bar_accepted'       => 'Cantina-Angebot angenommen',
            'merchant_purchase'  => 'Beim Händler eingekauft',
        ],
        'galaxy' => [
            'fleet_arrived' => 'Flotte angekommen',
            'trade'         => 'Handelsroute abgeschlossen',
            'encounter'     => 'Begegnung im All',
        ],
        'encounter_won'  => 'Begegnung gewonnen',
        'encounter_lost' => 'Begegnung verloren',
    ],

    // Nexus messages — nested (comm_log.nexus_events.onboarding.nexus_briefing.title)
    'nexus_events' => [
        'onboarding' => [
            'nexus_briefing' => [
                'title' => 'Nexus-Erstkontakt',
                'body'  => 'Verbindung zur Nexus-Zentrale hergestellt. Ihre Konzession ist registriert. Wir überwachen Ihre Kolonie. Stellen Sie sicher, dass Sie die vereinbarten Missionsziele erreichen.',
                'badge' => 'Erstkontakt',
            ],
        ],
        'run' => [
            'phase1_complete' => [
                'title' => 'Phase 1 abgeschlossen',
                'body'  => 'Nexus bestätigt: Ihre Kolonie hat Phase 1 erfolgreich abgeschlossen. Phase 2 beginnt. Weitere Anforderungen wurden übermittelt.',
                'badge' => 'Phase abgeschlossen',
            ],
            'nexus_warning_sol30' => [
                'title' => 'Nexus-Warnung',
                'body'  => 'Nexus-Protokoll §12.4: Ihre Kolonie zeigt unzureichende Fortschritte. Wir fordern nachweisbare Zielerfüllung bis Sol 50. Andernfalls folgen Sanktionen.',
                'badge' => 'Warnung',
            ],
            'nexus_warning_sol50' => [
                'title' => 'Nexus-Warnung (kritisch)',
                'body'  => 'Letzte Mahnung: Keine Ziele bis Sol 50 erreicht. Sanktionen treten ab Sol 65 in Kraft. Dies ist Ihre letzte Gelegenheit zur Kurskorrektur.',
                'badge' => 'Kritische Warnung',
            ],
            'nexus_sanction_sol65' => [
                'title' => 'Nexus-Sanktion verhängt',
                'body'  => 'Gemäß Konzessionsvertrag §7: Ein Berater wurde temporär gesperrt. Schuldensaldo wird überprüft. Erfüllen Sie die ausstehenden Ziele, um weitere Maßnahmen zu vermeiden.',
                'badge' => 'Sanktion',
            ],
            'nexus_countdown_sol80' => [
                'title' => 'Mission-Countdown',
                'body'  => 'Sol 80 erreicht. Die verbleibende Zeit für Ihre Mission nähert sich dem Ende. Nexus erwartet einen abschließenden Missionsbericht.',
                'badge' => 'Countdown',
            ],
            'run_completed' => [
                'title' => 'Mission erfolgreich abgeschlossen',
                'body'  => 'Nexus bestätigt: Alle Missionsziele wurden erfüllt. Ihre Konzession wird positiv bewertet. Abschlussprotokoll wurde übermittelt.',
                'badge' => 'Erfolg',
            ],
            'run_failed_trust' => [
                'title' => 'Mission gescheitert — Vertrauensverlust',
                'body'  => 'Nexus-Protokoll §3.1: Das Vertrauen der Kolonisten ist kritisch unter den Mindestwert gefallen. Die Konzession wird beendet.',
                'badge' => 'Gescheitert',
            ],
            'run_failed_nexus_debt' => [
                'title' => 'Mission gescheitert — Schulden-Protokoll',
                'body'  => 'Nexus-Protokoll §15.2: Nexus-Schulden haben die Konzessionsgrenze überschritten. Die Mission wird zwangsbeendet.',
                'badge' => 'Gescheitert',
            ],
            'run_failed_time' => [
                'title' => 'Mission gescheitert — Zeitlimit',
                'body'  => 'Das Sol-Limit wurde erreicht ohne die erforderlichen Ziele abzuschließen. Nexus beendet die Konzession.',
                'badge' => 'Gescheitert',
            ],
        ],
    ],

    // Rich descriptions for Protokoll entries (with :param placeholders)
    'desc' => [
        'building_placed'           => ':name platziert.',
        'building_invested'         => ':ap AP in :name investiert (:done / :total AP).',
        'building_repaired'         => ':name repariert (:current / :max Zustand).',
        'building_leveled_up'       => ':ap AP in :name investiert. Bau abgeschlossen — Level :level erreicht.',
        'level_up'                  => 'Forschung :name abgeschlossen.',
        'level_up_level'            => 'Forschung :name auf Level :level gestiegen.',
        'level_up_knowledge'        => 'Kenntnis :name erworben.',
        'level_up_knowledge_level'  => 'Kenntnis :name auf Level :level gestiegen.',
        'level_down'                => 'Level für :name mangels Wartung gesunken.',
        'level_down_level'          => 'Level für :name mangels Wartung auf :level gesunken.',
        'level_down_ship'           => 'Schiff :name durch Verfall zerstört.',
        'advisor_hired'             => '":type" eingestellt.',
        'advisor_hired_cost'        => '":type" eingestellt. Kosten: :credits CR.',
        'bar_accepted'              => 'Cantina-Angebot angenommen.',
        'bar_accepted_trade'        => ':give_amount :give gegen :get_amount :get getauscht.',
        'merchant_purchase'         => 'Beim Reisenden Händler eingekauft.',
        'merchant_visit'            => 'Reisender Händler in der Nähe angekündigt.',
        'fleet_arrived'             => 'Flotte am Ziel angekommen.',
        'galaxy_trade'              => 'Handelsroute abgeschlossen.',
        'galaxy_trade_credits'      => 'Handelsroute abgeschlossen (+:credits CR).',
        'encounter'                 => 'Begegnung mit fremder Flotte.',
        'tile_explored'             => 'Neuen Sektor erkundet.',
        'tile_deep_scanned'         => 'Tiefen-Scan eines Sektors durchgeführt.',
        'tile_deep_scanned_coords'  => 'Tiefen-Scan von Sektor (:q/:r) durchgeführt.',
        'colony_renamed'            => 'Kolonie umbenannt.',
    ],

    // Area icons (Bootstrap Icons class) — flat, keys are simple strings (no dots)
    'area_icons' => [
        'colony'   => 'bi-hexagon',
        'techtree' => 'bi-diagram-3',
        'trade'    => 'bi-shop',
        'galaxy'   => 'bi-stars',
        'run'      => 'bi-flag',
        'nexus'    => 'bi-broadcast-pin',
        'merchant' => 'bi-bag',
        'default'  => 'bi-journal-text',
    ],
];
