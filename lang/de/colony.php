<?php

return [

    // ── Tile actions ──────────────────────────────────────────────────────────

    'explore' => 'Erkunden',
    'deep_scan' => 'Sondieren',
    'invest_ap' => 'Ausbauen',
    'levelup_cost_label' => 'Kosten:',
    'levelup_cost_suffix' => 'bei Baubeginn',
    'repair' => 'Reparieren',
    'ap_per_tile' => '1 AP/Feld',

    // ── Repair errors ─────────────────────────────────────────────────────────

    'error_repair_under_construction' => 'Gebäude ist noch im Bau und kann nicht repariert werden.',
    'error_repair_full' => 'Gebäude ist bereits voll instand.',

    // ── Building actions ──────────────────────────────────────────────────────

    'build' => 'Bauen',
    'cancel' => 'Abbrechen',
    'select_tile_hint' => 'Tile anklicken zum Platzieren',

    // ── Sidebar labels ────────────────────────────────────────────────────────

    'tile_info' => 'Tile-Info',
    'terrain_details' => 'Terrain & Standort',

    // ── Hex-Grid Legende ──────────────────────────────────────────────────────
    'legend_title' => 'Legende',
    'legend_buildable' => 'Baubares Feld',
    'legend_soon_buildable' => 'Bald bebaubar (mit CC-Ausbau)',
    'legend_zone_fog' => 'Baubar, noch unerkundet — Bauen deckt auf',
    'legend_explore_fog' => 'Erkundungsziel — mit Navigations-AP aufdecken',
    'legend_regolith' => 'Regolith-Vorkommen',
    'legend_cc' => 'Kommandozentrale',
    'legend_hazard' => 'Gefahrenzone',
    'legend_impassable' => 'Unpassierbar',
    'legend_event' => 'Entdecktes Ereignis',

    // ── Sidebar: Terrain-/Zonen-Info ──────────────────────────────────────────
    'zone_buildable' => 'Koloniezone — bebaubar',
    'zone_soon' => 'Bald bebaubar (nächster Kommandozentralen-Ausbau)',
    'zone_outside' => 'Außerhalb der Koloniezone',
    'zone_unexplored' => 'Unerforscht — erkunden (Nav-AP)',
    'terrain_label' => 'Terrain',
    'event_label' => 'Phänomen',
    'coords_label' => 'Koordinaten',
    'hint_regolith_target' => 'Ziel für Harvester-Verlegung.',
    'hint_hazard' => 'Gefahrenzone — Bauen riskant, erhöhter Verfall.',
    'hint_impassable' => 'Unpassierbar — hier lässt sich nichts errichten.',
    'click_tile_hint' => 'Hex-Tile anklicken um Details anzuzeigen.',
    'building_section' => 'Gebäude',
    'construction_site' => 'Baustelle',
    'under_construction' => 'Im Bau',
    'resource_regolith' => 'Regolith',

    // ── Status chips ──────────────────────────────────────────────────────────

    'chip_locked' => 'Gesperrt',
    'chip_unexplored' => 'Unerforscht',
    'chip_explored' => 'Erkundet',
    'chip_scanned' => 'Sondiert',
    'chip_signal' => 'Signal',

    // ── Build mode ────────────────────────────────────────────────────────────

    'build_mode_title' => 'Gebäude bauen',
    'build_mode_hint' => 'Gebäude wählen, dann Terrain-Tile anklicken.',
    'building_info_label' => 'Gebäude-Info anzeigen',
    'no_buildings' => 'Keine Gebäude verfügbar.',
    'inprogress_label' => 'Im Bau',
    'inprogress_hint' => 'Tile auf der Karte anklicken um AP zu investieren.',
    'levelup_built' => 'Bau abgeschlossen:',

    // ── Event discovery popup ─────────────────────────────────────────────────

    'discovery_title' => 'Signal entschlüsselt',
    'discovery_dismiss' => 'Verstanden',

    // ── Onboarding hints (Phase 3e) ───────────────────────────────────────────

    'hint_suggestion_label' => 'Vorschlag',
    'hint_not_mandatory' => 'Vorschlag, kein Zwang — andere Baureihenfolgen sind möglich.',
    'onboarding_hint_1' => 'Kein Baumeister eingestellt — Bau-AP läuft auf Mindestniveau. Im Berater-Screen ändern.',
    'onboarding_hint_repair' => 'Die Anlage weist schon Schäden auf — Gebäude antippen, „Reparieren" wählen (1 Bau-AP). Substanz sichern, bevor der Verfall zunimmt.',
    'onboarding_hint_repair_urgent' => 'Warnung: ein Gebäude steht kurz vor dem Stufenverlust — jetzt reparieren (Bau-AP), bevor die Substanz kippt.',
    'onboarding_hint_2' => 'Harvester läuft leer — noch in der Koloniezone. Auf ein erkundetes Regolith-Tile außerhalb verlegen, damit er fördert.',
    'onboarding_hint_3' => 'Kommandozentrale auf Level 2 ausbauen — erweitert die Koloniezone und schaltet die Berater-Pfadwahl frei.',
    'onboarding_hint_3_agrardome_first' => 'Erst Agrardom bauen — Pflichtvoraussetzung für CC Level 2. Danach Pfad wählen: Analytiklabor, Hangar oder Cantina.',
    'onboarding_hint_advisor_slot2' => 'Berater-Slot 2 frei — im Berater-Screen Pfad wählen, dann passendes Gebäude bauen: Analytiklabor, Hangar oder Cantina.',
    'onboarding_hint_4' => 'Keine Kenntnis auf Level 1 — im Techtree Forschungs-AP einer Kenntnis zuweisen. Erste Ergebnisse kommen über mehrere Sols.',
    'onboarding_hint_5' => 'Die Kolonisten werden unruhig — Vertrauen stabilisieren: Zivilgebäude bauen oder reparieren.',
    'onboarding_hint_build_priority' => 'Ressourcen reichen nicht für alle Pfadgebäude auf einmal — mit einem anfangen, der Rest folgt in späteren Sols.',
    'onboarding_hint_6' => 'Keine Cantina — Händler und Gäste legen nicht an. Tauschangebote und Einmal-Items gehen an der Kolonie vorbei.',
    'onboarding_hint_agrardome' => 'Agrardom bauen — Pflichtvoraussetzung für CC Level 2 und Quelle für Organika. Ohne ihn kommt die Kolonie nicht weiter.',
    'onboarding_hint_analytik' => 'Analytik-Labor fehlt — Forschungs-AP können nicht eingesetzt werden. Die Kolonie bleibt wissenschaftlich blind.',
    'onboarding_hint_hangar_path' => 'Hangar-Pfad: Hangar bauen und Raumfahrer einstellen. Erst dann stehen Missionen und Navigations-AP zur Verfügung.',
    'onboarding_hint_cc_invest' => 'Bau-AP nicht verfallen lassen — in die Kommandozentrale investieren. Einzahlungen bleiben erhalten; Level 2 rückt damit näher.',
    'onboarding_hint_explore' => 'Navigations-AP für Erkundung nutzen (1 AP pro Feld) — jenseits der Zone liegen Regolith-Vorkommen und unbekannte Signale.',
    'onboarding_end_sol' => 'Sol beenden — AP für diesen Sol aufgebraucht oder nicht mehr sinnvoll einsetzbar. Nächster Sol bringt frische Aktionspunkte.',
    'onboarding_hint_spend_ap_construction' => 'Noch Bau-AP übrig — in ein Gebäude investieren oder CC-Ausbau vorfinanzieren. AP verfallen am Sol-Ende.',
    'onboarding_hint_spend_ap_research' => 'Forschungs-AP übrig — im Techtree einer Kenntnis zuweisen, bevor der Sol endet.',
    'onboarding_hint_spend_ap_navigation' => 'Navigations-AP übrig — da draußen liegt noch unentdecktes Gelände. Ein Feld kostet 1 AP.',
    'onboarding_hint_spend_ap_economy' => 'Wirtschafts-AP übrig — in der Cantina nachsehen, ob Gäste Angebote bereithalten.',
    'nav_techtree_locked' => 'Analytiklabor erforderlich.',
    'nav_cantina_locked' => 'Cantina nicht gebaut — Wohnhabitat lv1 erforderlich.',

    // ── First-visit popups (Techtree/Nexus-DB/Cantina/Hangar) ──────────────────

    'first_visit_dismiss' => 'Verstanden',
    'first_visit_techtree_title' => 'Techtree',
    'first_visit_techtree_text' => 'Hier weist du Forschungs-AP einer Kenntnis zu. Kenntnisse reifen über mehrere Sols — was einmal eingezahlt ist, bleibt erhalten, auch wenn ein Sol nicht reicht.',
    'first_visit_cantina_title' => 'Cantina',
    'first_visit_cantina_text' => 'Gäste aus dem Transit machen hier kurz Halt — tauschen Ressourcen, Credits und Einmal-Items. Der Reisende Händler erscheint gelegentlich. Angebote laufen ab; wer selten vorbeischaut, verpasst sie.',
    'first_visit_hangar_title' => 'Hangar',
    'first_visit_hangar_text' => 'Schiffe werden hier stationiert und auf Missionen geschickt. Jede Entsendung kostet Organika als Verpflegung und dauert mehrere Sols. Rückruf jederzeit möglich.',
    'first_visit_nexusdb_title' => 'Nexus-DB',
    'first_visit_nexusdb_text' => 'Nexus-Datenbank, Konzessions-Ausgabe — Einträge zu Gebäuden, Kenntnissen, Schiffen und Kolonie-Lore. Keine Aktion erforderlich.',

    // ── Onboarding — Nexus-Briefing (INNN, event_type = 'onboarding.nexus_briefing') ──

    'onboarding_nexus_briefing_title' => 'Konzession aktiviert — Kolonie :colony',
    'onboarding_nexus_briefing_body' => 'Kommandozentrale und Harvester betriebsbereit. Startkapital: 3.000 Cr — Nexus-Darlehen, Rückzahlung ausstehend. Regolith-Reserve: 200 Rg. Lebensfähigkeit der Kolonie wird laufend bewertet. Subventionen bis auf Weiteres aktiv.',

    // ── Onboarding — Inline-Trigger-Erklärungen ───────────────────────────────

    // Trigger 2 — Supply-Cap voll (UI-Banner, 1 Satz)
    'onboarding_trigger_supply_full' => 'Versorgungskapazität erschöpft — weitere Gebäude oder Schiffe können nicht zugewiesen werden. Wohnhabitat ausbauen oder Verbraucher abbauen.',

    // Trigger 4 — AP-Limit (Tooltip)
    'onboarding_trigger_ap_limit' => 'Keine Bau-AP mehr in diesem Sol verfügbar.',

    // Trigger 5 — Harvester-Verlagerung (Tooltip)
    'onboarding_trigger_harvester_move' => 'Verlegen kostet 1 Bau-AP pro Tile-Distanz — der Harvester produziert danach auf dem neuen Tile.',

    // ── Error messages ────────────────────────────────────────────────────────

    'error_path_gate_locked' => 'Pfad gesperrt — Kommandozentrale zuerst auf das nächste Level ausbauen.',
    'error_agrardom_required' => 'Agrardom zuerst bauen — Pflichtvoraussetzung für alle Pfad-Gebäude.',
    'error_tile_not_found' => 'Tile nicht gefunden.',
    'error_ring_locked' => 'Ring nicht freigeschaltet.',
    'error_already_explored' => 'Tile bereits erkundet.',
    'error_not_explored' => 'Tile muss zuerst erkundet werden.',
    'error_no_signal' => 'Kein Signal auf diesem Tile.',
    'error_already_scanned' => 'Tile bereits sondiert.',
    'error_no_nav_ap' => 'Nicht genug Navigations-AP.',
    'error_no_nav_ap_2' => 'Nicht genug Navigations-AP (2 benötigt).',
    'error_tile_not_buildable' => 'Nur bebaubare Terrain-Tiles erlaubt.',
    'error_tile_outside_colony' => 'Dieses Tile liegt außerhalb der Koloniezone.',
    'error_harvester_needs_regolith' => 'Harvester kann nur auf Regolith-Tiles platziert werden.',
    'harvester_move' => 'Verlegen',
    'harvester_move_mode_hint' => 'Erkundetes Regolith-Tile außerhalb der Koloniezone anklicken — zeigt Vorschaupfeil mit AP-Kosten. Gedrückt halten zum Verlegen.',
    'harvester_move_no_targets' => 'Kein freies erkundetes Regolith-Tile verfügbar — erst neue Tiles erkunden (Nav-AP).',
    'harvester_move_invalid_target' => 'Kein gültiges Ziel — der Harvester braucht ein freies, erkundetes Regolith-Tile (hellblau markiert).',
    'network_error' => 'Netzwerkfehler — bitte erneut versuchen.',
    'error_harvester_in_transit' => 'Der Harvester ist noch unterwegs — Verlegen erst nach Ankunft möglich.',
    'harvester_in_transit' => 'Unterwegs — Ankunft nächsten Sol.',
    'error_tile_occupied' => 'Tile bereits belegt.',
    'error_no_construction_ap' => 'Nicht genug Bau-AP.',
    'error_building_not_found' => 'Gebäude nicht gefunden.',
    'error_max_level_reached' => 'Maximales Level bereits erreicht.',
    'error_insufficient_resources' => 'Nicht genug Ressourcen für den Bau.',
    'error_repair_no_regolith' => 'Kein Regolith für die Reparatur — Harvester reparieren oder Regolith abbauen.',

    // ── Build cost chips ──────────────────────────────────────────────────────

    'cost_regolith' => ':amount Rg',
    'cost_compounds' => ':amount Wk',
    'cost_label' => 'Kosten',

    // ── Nexus-Import (Werkstoffe gegen Credits, Uplink-Station Lv1) ────────────

    'nexus_import_title' => 'Nexus-Import',
    'nexus_import_hint' => 'Werkstoffe direkt vom Nexus kaufen — immer verfügbar, fester Preis.',
    'nexus_import_amount' => 'Menge (Werkstoffe)',
    'nexus_import_price_each' => ':price Cr/Einheit',
    'nexus_import_total' => 'Gesamt: :total Cr',
    'nexus_import_confirm' => 'Importieren',
    'nexus_import_success' => ':amount Werkstoffe importiert (:cost Cr).',
    'nexus_import_uplink_required' => 'Uplink-Station Lv1 erforderlich — sie schaltet aktive Nexus-Anfragen frei.',
    'nexus_import_no_credits' => 'Nicht genug Credits für diesen Import.',
    'nexus_import_error' => 'Import fehlgeschlagen.',

    // ── Generic UI actions ───────────────────────────────────────────────────

    'close' => 'Schließen',

    // ── Traveling Merchant (Reisender Händler) ────────────────────────────────

    'merchant_in_system' => 'Händler im System',
    'merchant_title' => 'Reisender Händler',
    'merchant_until_sol' => 'Bleibt bis Sol',
    'merchant_buy' => 'Kaufen',
    'merchant_sold' => 'Verkauft',
    'merchant_buy_success' => 'Kauf erfolgreich.',
    'merchant_buy_error' => 'Kauf fehlgeschlagen.',

    // ── Bar/Cantina ───────────────────────────────────────────────────────────

    'bar_title' => 'Cantina',
    'bar_no_building' => 'Die Cantina ist noch nicht gebaut.',
    'bar_no_offers' => 'Keine Gäste im Moment. Komm nächsten Sol wieder.',
    'bar_offer_heading' => 'Angebote',
    'bar_offer_accept' => 'Annehmen',
    'bar_offer_give' => 'Du gibst',
    'bar_offer_get' => 'Du bekommst',
    'bar_offer_expires' => 'Läuft ab in Sol',
    'bar_offer_not_found' => 'Angebot nicht gefunden.',
    'bar_offer_already_accepted' => 'Angebot bereits angenommen.',
    'bar_offer_expired' => 'Angebot ist abgelaufen.',
    'bar_offer_insufficient_resources' => 'Nicht genügend Ressourcen.',
    'bar_offer_insufficient_ap' => 'Nicht genügend Wirtschafts-AP.',

    // ── Sol trigger (navbar button) ───────────────────────────────────────────

    'next_sol_button' => 'Sol beenden',

    // ── Nexus-Schulden-Anzeige (Feature 3) ───────────────────────────────────

    'nexus_debt_label' => 'Nexus-Kredit',
    'nexus_debt_format' => ':amount / 12.000 Cr',

    // ── Hangar screen ─────────────────────────────────────────────────────────

    'hangar_title' => 'Hangar',
    'hangar_slot_count' => 'Slots',
    'hangar_empty' => 'Leer',
    'hangar_build_ship' => 'Schiff bauen',
    'hangar_dispatch' => 'Entsenden',
    'hangar_dispatch_no_nav_ap' => 'Nicht genug Navigations-AP für diese Mission.',
    'hangar_dispatch_no_organika' => 'Nicht genug Organika für die Crew-Verpflegung dieser Mission.',
    'hangar_recall' => 'Zurückrufen',
    'hangar_repair' => 'Reparieren',
    'hangar_destination' => 'Ziel',
    'hangar_sol_distance' => 'Sol-Distanz',
    'hangar_in_transit' => 'Unterwegs',
    'hangar_in_construction' => 'Im Bau',
    'hangar_pilot_ready' => 'Raumfahrer bereit',
    'hangar_status' => 'Status',
    'hangar_already_commissioned' => 'Bereits aktiv',
    'hangar_none_built' => 'Kein Hangar gebaut. Baue einen Hangar in der Kolonie, um Schiffe zu stationieren.',
    'nav_hangar' => 'Hangar',
    'nav_hangar_locked' => 'Kein Hangar gebaut.',

    // ── Hangar — Nexus-Anfrage (ship acquisition redesign) ───────────────────

    'hangar_nexus_request' => 'Nexus anfragen',
    'hangar_nexus_request_title' => 'Schiff anfordern',
    'hangar_nexus_request_submit' => 'Anfordern',
    'hangar_ship_type' => 'Schiffstyp',
    'hangar_payment_method' => 'Bezahlung',
    'hangar_standard_purchase' => 'Standardkauf',
    'hangar_nexus_credit' => 'Nexus-Kredit',
    'hangar_nexus_credit_hint' => '0 Cr jetzt — Nexus-Schulden steigen',
    'hangar_consul_ap_title' => 'Konsul-Verhandlung',
    'hangar_consul_ap_label' => 'AP investieren (spart :amount Cr)',
    'hangar_consul_ap_hint' => ':ap AP → spart :saved Cr',
    'hangar_delivery_pending' => 'Lieferung ausstehend',
    'hangar_arrival' => 'Ankunft: Sol :tick',
    'hangar_pending_section' => 'Nicht zugewiesen',
    'hangar_pending_expires' => 'Verfällt: Sol :tick',
    'hangar_assign_hangar' => 'Hangar zuweisen',
    'hangar_assign_select' => 'Bay auswählen…',
    'hangar_ship_drone' => 'Drohne',
    'hangar_ship_freighter' => 'Frachter',
    'hangar_ship_corvette' => 'Korvette',

    // ── Sol-Report (Übergangsscreen) ──────────────────────────────────────────

    // Gruppentitel
    'sol_report_group_decay' => 'Die Kolonie altert',
    'sol_report_group_events' => 'Ereignisse',
    'sol_report_group_production' => 'Produktion & Vorräte',
    'sol_report_group_colony' => 'Kolonie & Personal',
    'sol_report_group_run' => 'Der Run',

    // Verfall
    'sol_report_level_to' => 'auf Stufe :level gefallen',
    'sol_report_level_lost' => 'Stufe verloren',
    'sol_report_ship_destroyed' => 'durch Verfall zerstört',
    'sol_report_wear_label' => 'Anlagen',
    'sol_report_wear_detail' => 'halten — leichter Verschleiß',

    // Ereignisse
    'sol_report_event_merchant' => 'Reisender Händler im System',

    // Produktion
    'sol_report_no_production' => 'Keine Produktion — Industriegebäude stillgelegt oder beschädigt.',
    'sol_report_food' => 'Verpflegung',
    'sol_report_food_ok' => ':amount Organika verbraucht — Kolonie versorgt',
    'sol_report_food_shortage' => 'Vorräte erschöpft — Vertrauen sinkt (Agrardom bauen/reparieren)',

    // Kolonie & Personal
    'sol_report_advisor' => 'Berater',
    'sol_report_advisor_promoted' => 'befördert zu Rang :rank',

    // Run
    'sol_report_phase_reached' => 'Phase :phase erreicht — neue Möglichkeiten verfügbar',
    'sol_report_objectives' => 'Ziele erfüllt: :done / :total',
    'sol_report_sol_counter' => 'Sol :sol von :limit',

    // Finale (Run-Ende)
    'sol_report_finale_win_title' => 'Mission erfüllt',
    'sol_report_finale_win_body' => 'Die Kolonie hat ihr Ziel erreicht und besteht weiter — gegen die Entropie behauptet.',
    'sol_report_finale_lose_title' => 'Mission gescheitert',
    'sol_report_finale_lose_body' => 'Die Kolonie konnte sich nicht halten. Was bleibt, wird verzeichnet und übergeben.',

    // UI-Steuertexte
    'sol_report_title' => 'Sol :sol abgeschlossen',
    'sol_report_continue' => 'Weiter zu Sol :sol',

    // Screen 2 — Phase progress
    'sol_report_screen2_title' => 'Fortschritt der Phase',
    'sol_report_phase1_title' => 'Phase 1 — Stabilisierung',
    'sol_report_phase2_title' => 'Phase 2 — Nexus-Direktiven',
    'sol_report_phase1_cc' => 'Kontrollzentrum auf Stufe 3',
    'sol_report_phase1_buildings' => '2 Gebäude auf Stufe 2',
    'sol_report_phase1_advisors' => '3 aktive Berater',
    'sol_report_phase2_objective_hidden' => 'Direktive noch unbekannt — im Spielverlauf erspielt',
    'sol_report_phase2_objective_done' => 'Erfüllt',
    'sol_report_next_screen' => 'Weiter',

    // Screen 3 — SOL N startet
    'sol_report_screen3_starts' => 'startet',
    'sol_report_screen3_begin' => 'Mission fortsetzen',

    'sol_report_skip_hint' => 'Tippen zum Überspringen',
    'sol_report_skip_setting' => 'Sol-Report künftig automatisch überspringen',
    'sol_report_finale_win_cta' => 'Run abschließen',
    'sol_report_finale_lose_cta' => 'Run beenden',

];
