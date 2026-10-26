<?php

return [
    // ── Fehlermeldungen (pathdepth.error_<code>, siehe PathDepthService::error)
    'error_unknown_entity' => 'Diese Familie oder Figur gibt es nicht.',
    'error_not_met' => 'Du hast diese Figur in diesem Lauf noch nicht getroffen.',
    'error_max_level' => 'Die höchste Stufe ist bereits erreicht.',
    'error_building_level_cap' => 'Die nächste Stufe braucht eine höhere Gebäudestufe.',
    'error_insufficient_ap' => 'Nicht genug AP verfügbar.',

    'error_conversation_ap_cap' => 'Mehr als :cap AP nimmt diese Figur heute nicht an. Morgen geht das Gespräch weiter — oder sprich mit jemand anderem.',
    'error_invalid_option' => 'Diese Gesprächsgröße gibt es nicht.',

    // ── Hangar: Training
    'ui_training_title' => 'Training',
    'ui_experience_label' => 'Missionserfahrung',
    'ui_level' => 'Stufe :level/:max',
    'ui_invest_progress' => ':spent/:needed AP',
    'ui_train' => 'Trainieren',
    'ui_get_to_know' => 'Kennenlernen',
    'ui_level_max' => 'Höchste Stufe',
    'ui_locked_building' => 'Gesperrt: :building braucht Stufe :level.',

    // ── Cantina: Beziehung pflegen
    'ui_relationship_title' => 'Beziehung pflegen',
    'ui_not_met' => 'Noch nicht getroffen',
    'ui_relationship_run_hint' => 'Vertrautheit wächst nur in diesem Lauf. Was du über eine Figur erfährst, bleibt im Kodex stehen.',
    'ui_relationship_met_only' => 'Gelistet sind nur Figuren, mit denen du in diesem Lauf schon gesprochen hast.',

    // ── Cantina: conversation options (T43-G4)
    'ui_conversation_cap_tooltip' => 'Diese Figur nimmt pro Sol nur begrenzt Zeit für Gespräche. Morgen geht es weiter — oder bei jemand anderem.',
    'ui_conversation_today' => 'Heute :spent/:cap AP',
    // TODO content-writer: name pools per option size (placeholder proposals); one name is picked per Sol.
    'conversation_names' => [
        'short' => ['Kurz grüßen', 'Nur nicken', 'Ein Wort wechseln'],
        'normal' => ['Bei einem Getränk reden', 'Zuhören', 'Zusammen sitzen'],
        'long' => ['Den Abend nehmen', 'Tief eintauchen', 'Bis zur Sperrstunde bleiben'],
    ],

    // ── Missionsfamilien
    'family_exploration' => 'Erkundung',
    'family_salvage' => 'Bergung',
    'family_supply' => 'Versorgung',
    'family_trade' => 'Handel',
    'family_protection' => 'Schutz',
];
