<?php

return [
    // ── Fehlermeldungen (pathdepth.error_<code>, siehe PathDepthService::error)
    'error_unknown_entity' => 'Diese Familie oder Figur gibt es nicht.',
    'error_not_met' => 'Du hast diese Figur in diesem Lauf noch nicht getroffen.',
    'error_max_level' => 'Die höchste Stufe ist bereits erreicht.',
    'error_building_level_cap' => 'Die nächste Stufe braucht eine höhere Gebäudestufe.',
    'error_insufficient_ap' => 'Nicht genug AP verfügbar.',

    // ── Hangar: Training
    'ui_training_title' => 'Training',
    'ui_experience_label' => 'Missionserfahrung',
    'ui_level' => 'Stufe :level/:max',
    'ui_invest_progress' => ':spent/:needed AP',
    'ui_invest' => 'Investieren', // TODO content-writer: confirm wording
    'ui_level_max' => 'Höchste Stufe',
    'ui_locked_building' => 'Gesperrt: :building braucht Stufe :level.',

    // ── Cantina: Beziehung pflegen
    'ui_relationship_title' => 'Beziehung pflegen',
    'ui_not_met' => 'Noch nicht getroffen',
    'ui_relationship_run_hint' => 'Vertrautheit gilt nur für diesen Lauf. Was du über eine Figur erfährst, bleibt dagegen insgesamt im Kodex erhalten.',
    'ui_relationship_met_only' => 'Hier erscheinen nur Figuren, die du in diesem Lauf getroffen hast.',

    // ── Missionsfamilien
    'family_exploration' => 'Erkundung',
    'family_salvage' => 'Bergung',
    'family_supply' => 'Versorgung',
    'family_trade' => 'Handel',
    'family_protection' => 'Schutz',
];
