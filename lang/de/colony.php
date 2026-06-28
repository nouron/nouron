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
    'onboarding_hint_1' => 'Noch kein Baumeister eingestellt — Bau-AP bleibt beim Grundwert.',
    'onboarding_hint_repair' => 'Deine Startgebäude sind beschädigt — tippe ein Gebäude an und nutze „Reparieren" (1 Bau-AP), um die Substanz zu sichern.',
    'onboarding_hint_repair_urgent' => 'Ein Gebäude steht kurz vor dem Stufenverlust — jetzt mit dem Reparieren-Button (Bau-AP) sichern.',
    'onboarding_hint_2' => 'Harvester steht noch in der Kolonie-Zone — auf das erkundete Regolith-Tile außerhalb verlegen.',
    'onboarding_hint_3' => 'Kommandozentrale auf Level 2 ausbauen — schaltet Berater-Pfadwahl und neue Kolonie-Tiles frei.',
    'onboarding_hint_3_agrardome_first' => 'Agrardom zuerst bauen — Pflichtvoraussetzung für den CC-Ausbau auf Level 2. Danach Pfad wählen: Analytiklabor, Hangar oder Cantina.',
    'onboarding_hint_advisor_slot2' => 'Berater-Slot 2 freigeschaltet — im Berater-Screen einen Pfad wählen und das passende Gebäude bauen (Analytiklabor, Hangar oder Cantina).',
    'onboarding_hint_4' => 'Noch keine Kenntnis erforscht — im Techtree eine Kenntnis auf Level 1 bringen.',
    'onboarding_hint_5' => 'Vertrauen sinkt — Zivilgebäude bauen oder reparieren.',
    'onboarding_hint_build_priority' => 'Agrardom, Cantina und Analytik-Labor sind grundsätzlich baubar — die Ressourcen reichen aber in diesem Sol nicht für alle gleichzeitig. Mit einem davon anfangen, der Rest folgt in späteren Sols.',
    'onboarding_hint_6' => 'Cantina noch nicht gebaut — hier erscheinen Händler und NPC-Gäste mit Tauschangeboten und Einmal-Items.',
    'onboarding_hint_agrardome' => 'Agrardom bauen — er ist Voraussetzung für den CC-Ausbau auf Level 2 und liefert Organika für Versorgung und Missionen.',
    'onboarding_hint_analytik' => 'Analytik-Labor noch nicht gebaut — ohne dieses Labor bleibt die Kolonie blind für Materialanalyse und Tiefenscans.',
    'onboarding_hint_hangar_path' => 'Hangar-Pfad: Hangar bauen und Raumfahrer einstellen — ermöglicht Missionen, Navigations-AP und Schiffsoperationen.',
    'onboarding_hint_cc_invest' => 'Restliche Bau-AP nicht verfallen lassen — tippe die Kommandozentrale an und investiere in den Ausbau. Was du jetzt einzahlst, bleibt erhalten; so steht Level 2 schon im nächsten Sol.',
    'onboarding_hint_explore' => 'Navigations-AP einsetzen und nach draußen erkunden (1 Nav-AP pro Feld) — jenseits der Kolonie liegen Regolith-Vorkommen für den Harvester und unbekannte Signale. Das nahe Gelände erschließt die Kommandozentrale ohnehin selbst.',
    'onboarding_end_sol' => 'Sol beenden — AP für diesen Sol aufgebraucht oder nicht mehr sinnvoll einsetzbar. Nächster Sol bringt frische Aktionspunkte.',
    'onboarding_hint_spend_ap_construction' => 'Noch Bau-AP übrig — ein bestehendes Gebäude weiter ausbauen, statt den Rest verfallen zu lassen.',
    'onboarding_hint_spend_ap_research' => 'Noch Forschungs-AP übrig — im Techtree eine Kenntnis weiter erforschen.',
    'onboarding_hint_spend_ap_navigation' => 'Noch Navigations-AP übrig — weiter erkunden, jenseits der Kolonie liegt noch unentdecktes Gelände.',
    'onboarding_hint_spend_ap_economy' => 'Noch Wirtschafts-AP übrig — in der Cantina nach Angeboten schauen.',
    'nav_techtree_locked' => 'Analytiklabor erforderlich.',
    'nav_cantina_locked' => 'Cantina nicht gebaut — Wohnhabitat lv1 erforderlich.',

    // ── First-visit popups (Techtree/Nexus-DB/Cantina/Hangar) ──────────────────

    'first_visit_dismiss' => 'Verstanden',
    'first_visit_techtree_title' => 'Techtree',
    'first_visit_techtree_text' => 'Hier weist du Forschungs-AP einer Kenntnis zu. Jede Kenntnis braucht mehrere Sols, um ein Level zu erreichen — bereits eingezahlte AP bleiben erhalten, auch wenn ein Sol nicht reicht.',
    'first_visit_cantina_title' => 'Cantina',
    'first_visit_cantina_text' => 'Hier tauschen NPC-Gäste Ressourcen gegen Credits oder Einmal-Items, und der Reisende Händler macht gelegentlich Station. Angebote laufen nach einigen Sols ab — regelmäßig vorbeischauen lohnt sich.',
    'first_visit_hangar_title' => 'Hangar',
    'first_visit_hangar_text' => 'Hier baust und entsendest du Schiffe für Missionen. Entsendungen kosten Organika als Proviant und dauern mehrere Sols — Schiffe können zwischendurch zurückgerufen werden.',
    'first_visit_nexusdb_title' => 'Nexus-DB',
    'first_visit_nexusdb_text' => 'Das Almanach der Kolonie — Nachschlagewerk zu Gebäuden, Kenntnissen, Schiffen und Lore. Rein informativ, keine Aktion erforderlich.',

    // ── Onboarding — Nexus-Briefing (INNN, event_type = 'onboarding.nexus_briefing') ──

    'onboarding_nexus_briefing_title' => 'Konzession aktiviert — Kolonie :colony',
    'onboarding_nexus_briefing_body' => 'Kommandozentrale und Harvester sind operationsbereit. Startkapital: 3.000 Cr — Nexus-Vorschuss, kein Geschenk. Regolith-Vorrat: 200 Rg. Die Kolonie gilt als lebensfähig, wenn Wohnraum und Personal vorhanden sind. Subventionen laufen vorerst weiter.',

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
    'sol_report_skip_hint' => 'Tippen zum Überspringen',
    'sol_report_skip_setting' => 'Sol-Report künftig automatisch überspringen',
    'sol_report_finale_win_cta' => 'Run abschließen',
    'sol_report_finale_lose_cta' => 'Run beenden',

];
