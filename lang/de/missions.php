<?php

return [
    // ── Mission catalog (config/missions.php) ────────────────────────────────

    'mission_courier_run_name' => 'Botenflug',
    'mission_courier_run_desc' => 'Die Drohne trägt Datenpakete und Post zum nächsten Relais — kleine Fracht, aber da draußen zahlt jemand gut dafür, verbunden zu bleiben.',
    'mission_courier_run_reward' => '60 Credits',

    'mission_recon_flight_name' => 'Erkundungsflug',
    'mission_recon_flight_desc' => 'Die Drohne zieht ihre Bahnen über unkartiertem Gelände und funkt zurück, was hinter dem Horizont liegt.',
    'mission_recon_flight_reward' => '2 Sektoren kartiert',

    'mission_deep_survey_name' => 'Signalvermessung',
    'mission_deep_survey_desc' => 'Irgendetwas da draußen sendet — die Drohne kreist über der Quelle, bis klar ist, was es wirklich ist.',
    'mission_deep_survey_reward' => 'Tiefenscan des Signals',

    'mission_prospecting_flight_name' => 'Prospektionsflug',
    'mission_prospecting_flight_desc' => 'Die Drohne tastet die Geröllfelder nach abbauwürdigem Regolith ab und kehrt mit vollen Probencontainern heim.',
    'mission_prospecting_flight_reward' => '20–30 Regolith',

    'mission_data_sweep_name' => 'Datensammelflug',
    'mission_data_sweep_desc' => 'Auf weiter Schleife sammelt die Drohne Messdaten, die den Analytikern daheim Wochen an Feldarbeit ersparen.',
    'mission_data_sweep_reward' => '+8 Forschungs-AP',

    'mission_long_range_expedition_name' => 'Fernexpedition',
    'mission_long_range_expedition_desc' => 'Fünf Sole hin, fünf zurück — niemand weiß, was die Drohne am Rand ihrer Reichweite findet, aber leer kommt sie selten heim.',
    'mission_long_range_expedition_reward' => 'Zufallsfund',

    'mission_supply_run_name' => 'Versorgungsfahrt',
    'mission_supply_run_desc' => 'Der Frachter klappert die verstreuten Depots und Außenstationen ab und bringt heim, was die Kolonie am nötigsten braucht.',
    'mission_supply_run_reward' => '25 Regolith + 10 Organika',

    'mission_trade_convoy_name' => 'Handelsfahrt',
    'mission_trade_convoy_desc' => 'Beladen mit allem, was sich entbehren lässt, fährt der Frachter die Handelsroute — und die Kolonisten sehen gern, wenn er schwer zurückkehrt.',
    'mission_trade_convoy_reward' => '180 Credits + Vertrauen',

    'mission_aid_transport_name' => 'Hilfsgütertransport',
    'mission_aid_transport_desc' => 'Irgendwo geht es einer Station schlechter als uns — der Frachter bringt Organika hinaus, und die Kolonie weiß wieder, wofür sie arbeitet.',
    'mission_aid_transport_reward' => '60 Credits + Vertrauen',

    'mission_salvage_sweep_name' => 'Trümmerbergung',
    'mission_salvage_sweep_desc' => 'In den Wrackfeldern liegt, was die Kolonie selbst nicht herstellen kann — die Bergungscrew schneidet heraus, was noch taugt.',
    'mission_salvage_sweep_reward' => '6–10 Werkstoffe',

    'mission_ruin_expedition_name' => 'Ruinen-Expedition',
    'mission_ruin_expedition_desc' => 'Wer die Ruine hinterlassen hat, ist lange fort — aber was zwischen ihren Wänden liegt, ist eine Expedition wert.',
    'mission_ruin_expedition_reward' => '150 Credits',

    // TODO(content-writer): Platzhalter, GDD §4c "Harvester-Zweitinstanz: Bezugsquelle"
    // Weg B (freigegeben 2026-08-05) — narrative Rahmung folgt (havarierte Förderanlage
    // einer früheren Expedition, siehe GDD-Abschnitt für Details).
    'mission_harvester_salvage_name' => 'Bergungsauftrag: Förderanlage',
    'mission_harvester_salvage_desc' => 'In der Ruine liegt eine ausgeschlachtete Förderanlage — beschädigt, aber vielleicht noch zu retten.',
    'mission_harvester_salvage_reward' => 'Geborgener Harvester (beschädigt)',

    'mission_escort_convoy_name' => 'Konvoi-Begleitung',
    'mission_escort_convoy_desc' => 'Die Korvette begleitet einen fremden Konvoi durch unwegsames Gelände — meist reicht schon ihre Silhouette am Himmel, damit nichts passiert.',
    'mission_escort_convoy_reward' => '200 Credits',

    // ── Dispatch dialog ──────────────────────────────────────────────────────

    'dialog_title' => 'Mission wählen',
    'dialog_no_missions' => 'Für diesen Schiffstyp liegt gerade kein Auftrag vor.',
    'gate_knowledge_hint' => 'Erfordert :name Stufe :level',
    'gate_target_hint' => 'Kein gültiges Ziel verfügbar',
    'gate_nav_ap_hint' => 'Nicht genug Navigations-AP (:available/:required verfügbar)',
    'gate_organika_hint' => 'Nicht genug Organika (:available/:required verfügbar)',
    'select_target' => 'Ziel wählen',
    'chip_duration' => 'Dauer: :sols Sole',
    'chip_wear' => 'Verschleiß',
    'return_label' => 'Rückkehr: Sol :sol',
    'start_button' => 'Mission starten',

    // ── Dispatch errors ──────────────────────────────────────────────────────

    'error_wrong_ship_type' => 'Dieses Schiff ist für diese Mission nicht geeignet.',
    'error_sp_too_low' => 'Das Schiff ist zu verschlissen für einen Einsatz — erst reparieren.',
    'error_knowledge_gate' => 'Der Kolonie fehlt die nötige Kenntnis für diese Mission.',
    'error_invalid_target' => 'Kein gültiges Ziel für diese Mission gewählt.',
    'error_target_consumed' => 'Diese Ruine wurde bereits geborgen.',
    'error_harvester_instance_full' => 'Die Kolonie hat bereits die maximale Anzahl Harvester — eine Bergung würde nichts bringen.',

    // ── Sol report ───────────────────────────────────────────────────────────

    'sol_report_completed' => 'Mission abgeschlossen',
    'sol_report_aborted' => 'Mission abgebrochen — Schiff kehrte flugunfähig zurück',
];
