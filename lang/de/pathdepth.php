<?php

return [
    // ── Fehlermeldungen (pathdepth.error_<code>, siehe PathDepthService::error)
    'error_unknown_entity' => 'Diese Familie oder Figur gibt es nicht.',
    'error_not_met' => 'Du hast diese Figur in diesem Lauf noch nicht getroffen.',
    'error_max_level' => 'Die höchste Stufe ist bereits erreicht.',
    'error_building_level_cap' => 'Die nächste Stufe braucht eine höhere Gebäudestufe.',
    'error_insufficient_ap' => 'Nicht genug AP verfügbar.',
    'error_training_ap_cap' => 'Mehr als :cap AP lassen sich heute nicht in dieses Training stecken. Morgen geht es weiter — oder bei einer anderen Familie.',
    'error_training_locked' => 'Dieses Training ist noch gesperrt. Der Hangar braucht eine höhere Stufe.',

    'error_conversation_ap_cap' => 'Mehr als :cap AP nimmt diese Figur heute nicht an. Morgen geht das Gespräch weiter — oder sprich mit jemand anderem.',
    'error_invalid_option' => 'Diese Gesprächsgröße gibt es nicht.',
    'error_conversation_option_locked' => 'Für ein so langes Gespräch fehlt der Raum. Die Cantina braucht dafür Stufe :level.',
    'error_conversation_done_today' => 'Mit dieser Figur hast du heute schon gesprochen. Morgen wieder.',

    // ── Hangar: Training
    'ui_training_title' => 'Training',
    'ui_experience_label' => 'Missionserfahrung',
    'ui_level' => 'Stufe :level/:max',
    'ui_invest_progress' => ':spent/:needed AP',
    'ui_train' => 'Trainieren',
    'ui_get_to_know' => 'Kennenlernen',
    'ui_level_max' => 'Höchste Stufe',
    'ui_training_ap_today' => 'Heute schon :spent/:cap AP in dieses Training',
    'ui_training_ap_cap_tooltip' => 'Dieses Training nimmt pro Sol nur begrenzt AP auf. Morgen geht es weiter — oder bei einer anderen Familie.',
    'ui_locked_building' => 'Gesperrt: :building braucht Stufe :level.',
    'ui_training_effect' => 'Je Stufe: +:pct % Ertrag bei Mengen-Belohnungen',
    'ui_training_effect_now' => 'Jetzt +:now % · nächste Stufe +:next %',
    'ui_training_effect_max' => 'Jetzt +:now % (Höchststufe)',
    'ui_training_missions' => 'Gilt für: :list',
    'ui_training_unlock' => 'Ab Hangar-Stufe :level',

    // ── Cantina: Beziehung pflegen
    'ui_relationship_title' => 'Beziehung pflegen',
    'ui_not_met' => 'Noch nicht getroffen',
    'ui_relationship_run_hint' => 'Vertrautheit wächst nur in diesem Lauf. Was du über eine Figur erfährst, bleibt im Kodex stehen.',
    'ui_relationship_met_only' => 'Gelistet sind nur Figuren, mit denen du in diesem Lauf schon gesprochen hast.',
    // Hinweis im Figuren-Popup für noch nicht getroffene Figuren.
    'ui_relationship_not_met_hint' => 'Ihr habt noch kein Wort gewechselt. Nach dem ersten Gespräch kannst du hier die Beziehung pflegen.',
    // Relationship level names 0–5 (owner 2026-11-11).
    'relationship_level_0' => 'fremd',
    'relationship_level_1' => 'neu kennengelernt',
    'relationship_level_2' => 'bekannt',
    'relationship_level_3' => 'vertraut',
    'relationship_level_4' => 'befreundet',
    'relationship_level_5' => 'brüderlich',

    // ── Cantina: conversation options (T43-G4)
    'ui_conversation_cap_tooltip' => 'Diese Figur nimmt pro Sol nur begrenzt Zeit für Gespräche. Morgen geht es weiter — oder bei jemand anderem.',
    'ui_conversation_today' => 'Heute :spent/:cap AP',
    // Variante C (2026-11-13): ein Gespräch je Figur und Sol; Überschuss über die nächste
    // Stufe verfällt mit Warnung.
    'conversation_done_today' => 'Für heute habt ihr geredet. Morgen wieder.',
    'conversation_wasted_chip' => ':ap AP · :n verfallen',
    'conversation_wasted_hint' => 'Nur noch :n AP bis zur nächsten Stufe – darüber verfällt der Rest.',
    'conversation_wasted_result' => ':n AP sind verfallen.',
    // Tomas' Rat: Häufigkeit nach Beziehungsstufe (game.bartender.advice_every_sols_by_relationship_level).
    'bartender_advice_every' => 'Rat alle :n Sol',
    'bartender_advice_daily' => 'Rat jeden Sol',
    'bartender_advice_next' => 'Nächster Rat in :n Sol',
    'bartender_advice_today' => 'Rat heute verfügbar',
    // Pro Sol wird je Gesprächslänge ein Name aus dem Pool gewählt (Poolgröße beliebig).
    // short = 1 AP, long = 2 AP (ab Cantina-Stufe 3).
    'conversation_names' => [
        'short' => ['Kurz grüßen', 'Nur nicken', 'Ein Wort wechseln', 'Das Glas heben', 'Am Tisch vorbeischauen', 'Fragen, wie es läuft'],
        'long' => ['Den Abend nehmen', 'Bei einem Getränk reden', 'Bis zur Sperrstunde bleiben', 'Den Tisch teilen', 'Die Schicht hinter sich lassen', 'Noch eine Runde bestellen'],
    ],

    // ── Cantina: line of a character for a conversation option the cantina level does not
    // allow yet (owner 2026-11-13). Pool per character slug, fallback "default"; one entry is
    // picked per Sol. :level = required cantina level (optional in the text).
    'option_locked_lines' => [
        'default' => [
            'Hier drin hört man ja sein eigenes Wort nicht. Ein andermal, wenn die Cantina mehr Platz hat.',
            'Für ein richtiges Gespräch fehlt hier der Raum. Bau die Cantina aus, dann setz ich mich zu dir.',
            'Nicht hier, nicht so eng. Vielleicht, wenn die Cantina größer ist.',
        ],
        'bartender' => [
            'Für mehr fehlt mir hier der Platz — bau die Cantina aus.',
            'Ich hab hinter dem Tresen kaum Ellbogenfreiheit. Mehr Raum, mehr Zeit für dich.',
            'Lange Abende? Dafür brauch ich erst eine größere Cantina.',
        ],
        'smuggler' => [
            'Zu viele Ohren auf zu wenig Raum. Wenn die Cantina wächst, reden wir richtig.',
            '…nicht hier. Hier sitzt jeder jedem im Nacken. Gib mir eine Ecke, die keiner einsehen kann.',
            'Was ich erzähle, bleibt nicht lange geheim, wenn der Nachbartisch mithört. Bau an.',
        ],
        'information_broker' => [
            'Was ich zu sagen habe, braucht eine ruhige Ecke. Die gibt es hier noch nicht.',
            'Ein vollständiger Bericht verlangt einen Tisch ohne Zuhörer. Sobald die Cantina das bietet, gern.',
            'Ich bespreche Einzelheiten nicht zwischen Tür und Tresen. Erweitere die Cantina, dann nehme ich mir die Zeit.',
        ],
        'mechanic' => [
            'Ich steh hier zwischen Tür und Tresen. Gib mir einen Tisch, dann reden wir.',
            'Lange Gespräche? Hier? Da kann ich auch im Lüftungsschacht — egal. Bau erst aus.',
            'Zu eng. Zu laut. Wenn die Cantina mal größer ist, erklär ich dir alles. Vielleicht.',
        ],
        'corporate_rep' => [
            'Ein Gespräch dieser Länge verlangt angemessene Räumlichkeiten.',
            'Ich schätze unsere Zusammenarbeit sehr. Für vertiefte Gespräche wäre allerdings eine erweiterte Cantina wünschenswert.',
            'Gute Kooperation braucht den richtigen Rahmen. Den bietet dieser Raum noch nicht ganz.',
        ],
        'founder' => [
            'Meine Geschichte ist lang. Dafür braucht es mehr als diesen Winkel.',
            'Damals saßen wir zu fünft auf Kisten und haben trotzdem geredet. Aber für das, was ich dir erzählen will, bau die Cantina aus.',
            'Früher war hier nur ein Tresen. Es ist schon mehr geworden. Noch ein Stück, dann setz ich mich zu dir.',
        ],
        'gambler' => [
            'Am vollen Tisch spielt keiner lange. Bau an, dann bleib ich sitzen.',
            'Mein großes Spiel braucht eine Bühne, Direktor, kein Stehplätzchen am Tresen! Mach die Cantina größer.',
            'Für eine kurze Runde reicht es. Für die lange Nacht? Da muss schon mehr Platz her.',
        ],
        'ai_researcher' => [
            'Zu laut, zu eng. Mein Kopf braucht mehr Raum als das hier.',
            'Ich wollte dir von den — nein, warte, nicht hier, hier kann ich nicht denken. Wenn die Cantina größer ist.',
            'Lange Gedanken brauchen einen ruhigen Tisch. Den hat diese Cantina noch nicht. Noch nicht.',
        ],
        'veteran' => [
            'Kein Rücken frei, keine Wand im Blick. So lange bleib ich hier nicht.',
            'Kurz, ja. Lang, nein. Zu eng hier. Bau aus.',
            'Zu viele Leute, zu wenig Abstand. Größere Cantina, dann bleib ich.',
        ],
        'stranger' => [
            '…nicht hier. Nicht so.',
            '…mehr Raum. Dann.',
            'Dieser Ort ist… noch zu klein. Für das, was zu sagen wäre.',
        ],
        'doctor' => [
            'Ich hab kaum Platz zum Sitzen. Für ein langes Gespräch brauchen wir mehr Raum.',
            'Ich würde dir gern länger zuhören. Aber hier, im Gedränge, hört keiner richtig zu. Wenn die Cantina wächst.',
            'Ein gutes Gespräch braucht Ruhe, wie eine Wunde. Die findet man hier noch nicht.',
        ],
        'preacher' => [
            'Auch das Wort braucht einen Ort, an dem es atmen kann.',
            'Jede Gemeinschaft wächst mit ihren Räumen. Wenn diese Cantina wächst, wachsen auch unsere Gespräche.',
            'Was zu sagen ist, verträgt kein Gedränge. Gib ihm einen größeren Raum.',
        ],
        'prospector' => [
            'Mehr Platz, mehr Geschichten. So eng erzähl ich nur die kurzen.',
            'Die guten Fundstellen erklärt man nicht im Stehen. Bau die Cantina aus, dann breite ich die Karte aus.',
            'Hier passt nicht mal meine Tasche unter den Tisch. Für längere Gespräche muss angebaut werden.',
        ],
        'mercenary' => [
            'Kurz geht. Für alles andere ist mir das hier zu eng.',
            'Ich rede nicht lange, wo jeder mithört. Größere Cantina, dann reden wir.',
            'Gib mir einen Tisch mit Abstand. Den hat diese Cantina noch nicht.',
        ],
        'scrap_dealer' => [
            'Hier stapelt sich ja alles. Mach Platz, dann reden wir länger.',
            'Ich hätte dir so viel zu erzählen — über Ventile, über Dichtungen, über alles! Aber hier krieg ich ja nicht mal die Ellbogen auseinander. Bau aus!',
            'Ein langes Gespräch braucht Platz für die Musterstücke, Direktor. Mehr Cantina, mehr Voss!',
        ],
    ],

    // ── Missionsfamilien
    'family_exploration' => 'Erkundung',
    'family_salvage' => 'Bergung',
    'family_supply' => 'Versorgung',
    'family_trade' => 'Handel',
    'family_protection' => 'Schutz',
];
