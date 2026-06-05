<?php

return [

    // ── Tile actions ──────────────────────────────────────────────────────────

    'explore'           => 'Erkunden',
    'deep_scan'         => 'Sondieren',
    'invest_ap'         => 'AP investieren',

    // ── Building actions ──────────────────────────────────────────────────────

    'build'             => 'Bauen',
    'cancel'            => 'Abbrechen',
    'select_tile_hint'  => 'Tile anklicken zum Platzieren',

    // ── Sidebar labels ────────────────────────────────────────────────────────

    'tile_info'         => 'Tile-Info',
    'click_tile_hint'   => 'Hex-Tile anklicken um Details anzuzeigen.',
    'building_section'  => 'Gebäude',
    'construction_site'    => 'Baustelle',
    'under_construction'   => 'Im Bau',
    'max_level'         => 'Max. Stufe',
    'condition'         => 'Zustand',
    'ap_invested'       => 'AP investiert',
    'resource_regolith' => 'Regolith',

    // ── Status chips ──────────────────────────────────────────────────────────

    'chip_locked'       => 'Gesperrt',
    'chip_unexplored'   => 'Unerforscht',
    'chip_explored'     => 'Erkundet',
    'chip_scanned'      => 'Sondiert',
    'chip_signal'       => 'Signal',

    // ── Build mode ────────────────────────────────────────────────────────────

    'build_mode_title'       => 'Gebäude bauen',
    'build_mode_hint'        => 'Gebäude wählen, dann Terrain-Tile anklicken.',
    'no_buildings'           => 'Keine Gebäude verfügbar.',
    'inprogress_label'       => 'Im Bau',
    'inprogress_hint'        => 'Tile auf der Karte anklicken um AP zu investieren.',
    'levelup_built'          => 'Bau abgeschlossen:',

    // ── Event discovery popup ─────────────────────────────────────────────────

    'discovery_title'   => 'Signal entschlüsselt',
    'discovery_dismiss' => 'Verstanden',

    // ── Onboarding hints (Phase 3e) ───────────────────────────────────────────

    'onboarding_hint_1' => 'Kein Wohnhabitat gebaut — Supply-Cap bleibt bei 10.',
    'onboarding_hint_2' => 'Noch kein Baumeister eingestellt — Bau-AP bleibt beim Grundwert.',
    'onboarding_hint_3' => 'Harvester produziert nichts — auf ein Regolith-Tile verlegen.',
    'onboarding_hint_4' => 'Noch keine Kenntnis erforscht — im Techtree eine Kenntnis auf Level 1 bringen.',
    'onboarding_hint_5' => 'Vertrauen sinkt — Zivilgebäude bauen oder reparieren.',
    'onboarding_hint_6' => 'Cantina noch nicht gebaut — hier erscheinen Händler und NPC-Gäste mit Tauschangeboten und Einmal-Items.',
    'nav_cantina_locked' => 'Cantina nicht gebaut — Wohnhabitat lv1 + Kommandozentrale lv2 erforderlich.',

    // ── Onboarding — Nexus-Briefing (INNN, event_type = 'onboarding.nexus_briefing') ──

    'onboarding_nexus_briefing_title' => 'Konzession aktiviert — Kolonie :colony',
    'onboarding_nexus_briefing_body'  => 'Kommandozentrale und Harvester sind operationsbereit. Startkapital: 3.000 Cr — Nexus-Vorschuss, kein Geschenk. Regolith-Vorrat: 200 Rg. Die Kolonie gilt als lebensfähig, wenn Wohnraum und Personal vorhanden sind. Subventionen laufen vorerst weiter.',

    // ── Onboarding — Inline-Trigger-Erklärungen ───────────────────────────────

    // Trigger 1 — Decay (INNN-Event, event_type = 'onboarding_decay')
    'onboarding_trigger_decay_title'    => 'Strukturverfall gemeldet',
    'onboarding_trigger_decay_body'     => 'Gebäude verlieren ohne Wartung schrittweise Stabilität. Sinken die Statuswerte auf null, fällt das Gebäude eine Stufe zurück. Bau-AP in den Erhalt investieren.',

    // Trigger 2 — Supply-Cap voll (UI-Banner, 1 Satz)
    'onboarding_trigger_supply_full'    => 'Versorgungskapazität erschöpft — weitere Gebäude oder Schiffe können nicht zugewiesen werden. Wohnhabitat ausbauen oder Verbraucher abbauen.',

    // Trigger 3 — Vertrauen negativ (INNN-Event, event_type = 'onboarding_trust')
    'onboarding_trigger_trust_title'    => 'Vertrauen im negativen Bereich',
    'onboarding_trigger_trust_body'     => 'Sinkendes Vertrauen drückt die Produktion der Kolonie. Ursache ist meist schlechte Moral — Zivilgebäude stabilisieren den Wert.',

    // Trigger 4 — AP-Limit (Tooltip)
    'onboarding_trigger_ap_limit'       => 'Keine Bau-AP mehr in diesem Sol verfügbar.',

    // Trigger 5 — Harvester-Verlagerung (Tooltip)
    'onboarding_trigger_harvester_move' => 'Verlegen kostet 1 Bau-AP — der Harvester produziert danach auf dem neuen Tile.',

    // ── Error messages ────────────────────────────────────────────────────────

    'error_tile_not_found'      => 'Tile nicht gefunden.',
    'error_ring_locked'         => 'Ring nicht freigeschaltet.',
    'error_already_explored'    => 'Tile bereits erkundet.',
    'error_not_explored'        => 'Tile muss zuerst erkundet werden.',
    'error_no_signal'           => 'Kein Signal auf diesem Tile.',
    'error_already_scanned'     => 'Tile bereits sondiert.',
    'error_no_nav_ap'           => 'Nicht genug Navigations-AP.',
    'error_no_nav_ap_2'         => 'Nicht genug Navigations-AP (2 benötigt).',
    'error_tile_not_buildable'  => 'Nur bebaubare Terrain-Tiles erlaubt.',
    'error_tile_outside_colony' => 'Dieses Tile liegt außerhalb der Koloniezone.',
    'error_tile_occupied'       => 'Tile bereits belegt.',
    'error_no_construction_ap'  => 'Nicht genug Bau-AP.',
    'error_building_not_found'  => 'Gebäude nicht gefunden.',
    'error_max_level_reached'   => 'Maximales Level bereits erreicht.',

    // ── Generic UI actions ───────────────────────────────────────────────────

    'close'             => 'Schließen',

    // ── Traveling Merchant (Reisender Händler) ────────────────────────────────

    'merchant_in_system'  => 'Händler im System',
    'merchant_title'      => 'Reisender Händler',
    'merchant_until_sol'  => 'Bleibt bis Sol',
    'merchant_buy'         => 'Kaufen',
    'merchant_sold'        => 'Verkauft',
    'merchant_buy_success' => 'Kauf erfolgreich.',
    'merchant_buy_error'   => 'Kauf fehlgeschlagen.',

    // ── Bar/Cantina ───────────────────────────────────────────────────────────

    'bar_title'                        => 'Cantina',
    'bar_no_building'                  => 'Die Cantina ist noch nicht gebaut.',
    'bar_no_offers'                    => 'Keine Gäste im Moment. Komm nächsten Sol wieder.',
    'bar_offer_heading'                => 'Angebote',
    'bar_offer_accept'                 => 'Annehmen',
    'bar_offer_give'                   => 'Du gibst',
    'bar_offer_get'                    => 'Du bekommst',
    'bar_offer_expires'                => 'Läuft ab in Sol',
    'bar_offer_not_found'              => 'Angebot nicht gefunden.',
    'bar_offer_already_accepted'       => 'Angebot bereits angenommen.',
    'bar_offer_expired'                => 'Angebot ist abgelaufen.',
    'bar_offer_insufficient_resources' => 'Nicht genügend Ressourcen.',

    // ── Sol trigger (navbar button) ───────────────────────────────────────────

    'next_sol_button' => 'Sol beenden',

    // ── Nexus-Schulden-Anzeige (Feature 3) ───────────────────────────────────

    'nexus_debt_label'  => 'Nexus-Kredit',
    'nexus_debt_format' => ':amount / 12.000 Cr',

    // ── Hangar screen ─────────────────────────────────────────────────────────

    'hangar_title'            => 'Hangar',
    'hangar_slot_count'       => 'Slots',
    'hangar_empty'            => 'Leer',
    'hangar_build_ship'       => 'Schiff bauen',
    'hangar_dispatch'         => 'Entsenden',
    'hangar_recall'           => 'Zurückrufen',
    'hangar_repair'           => 'Reparieren',
    'hangar_destination'      => 'Ziel',
    'hangar_sol_distance'     => 'Sol-Distanz',
    'hangar_in_transit'       => 'Unterwegs',
    'hangar_in_construction'  => 'Im Bau',
    'hangar_pilot_ready'      => 'Raumfahrer bereit',
    'hangar_status'           => 'Status',
    'hangar_already_commissioned' => 'Bereits aktiv',
    'hangar_none_built'       => 'Kein Hangar gebaut. Baue einen Hangar in der Kolonie, um Schiffe zu stationieren.',
    'nav_hangar'              => 'Hangar',
    'nav_hangar_locked'       => 'Kein Hangar gebaut.',

    // ── Hangar — Nexus-Anfrage (ship acquisition redesign) ───────────────────

    'hangar_nexus_request'         => 'Nexus anfragen',
    'hangar_nexus_request_title'   => 'Schiff anfordern',
    'hangar_nexus_request_submit'  => 'Anfordern',
    'hangar_ship_type'             => 'Schiffstyp',
    'hangar_payment_method'        => 'Bezahlung',
    'hangar_standard_purchase'     => 'Standardkauf',
    'hangar_nexus_credit'          => 'Nexus-Kredit',
    'hangar_nexus_credit_hint'     => '0 Cr jetzt — Nexus-Schulden steigen',
    'hangar_consul_ap_title'       => 'Konsul-Verhandlung',
    'hangar_consul_ap_label'       => 'AP investieren (spart :amount Cr)',
    'hangar_consul_ap_hint'        => ':ap AP → spart :saved Cr',
    'hangar_delivery_pending'      => 'Lieferung ausstehend',
    'hangar_arrival'               => 'Ankunft: Sol :tick',
    'hangar_pending_section'       => 'Nicht zugewiesen',
    'hangar_pending_expires'       => 'Verfällt: Sol :tick',
    'hangar_assign_hangar'         => 'Hangar zuweisen',
    'hangar_assign_select'         => 'Bay auswählen…',
    'hangar_ship_drone'            => 'Drohne',
    'hangar_ship_freighter'        => 'Frachter',
    'hangar_ship_corvette'         => 'Korvette',

];

