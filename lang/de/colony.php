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
    // Owner-Fund 2026-09-05: sichtbare Zustandsanzeige über dem Reparieren-Button.
    // Wert "Zustand" bereits an anderer Stelle exakt für dasselbe Konzept etabliert
    // (onboarding_hint_encounter, run.task_engineering_output, comm_log.building_repaired)
    // — hier direkt wiederverwendet statt TODO-Platzhalter, content-writer bitte
    // gegenprüfen falls ein anderer Begriff gewünscht ist.
    'condition_label' => 'Zustand',

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
    'legend_explore_fog' => 'Erkundungsziel — mit AP aufdecken',
    'legend_regolith' => 'Regolith-Vorkommen',
    'legend_cc' => 'Kommandozentrale',
    'legend_hazard' => 'Gefahrenzone',
    'legend_impassable' => 'Unpassierbar',
    'legend_event' => 'Entdecktes Ereignis',

    // ── Sidebar: Terrain-/Zonen-Info ──────────────────────────────────────────
    'zone_buildable' => 'Koloniezone — bebaubar',
    'zone_soon' => 'Bald bebaubar (nächster Kommandozentralen-Ausbau)',
    'zone_outside' => 'Außerhalb der Koloniezone',
    'zone_unexplored' => 'Unerforscht — erkunden (AP)',
    'terrain_label' => 'Terrain',
    'event_label' => 'Phänomen',
    'coords_label' => 'Koordinaten',
    'hint_regolith_target' => 'Ziel für Harvester-Verlegung.',
    'hint_hazard' => 'Gefahrenzone — Bauen riskant, erhöhter Verfall.',
    'hint_impassable' => 'Unpassierbar — hier lässt sich nichts errichten.',
    'click_tile_hint' => 'Hex-Tile anklicken um Details anzuzeigen.',
    'phase1_progress_title' => 'Phase-1-Ziele',
    'phase2_progress_title' => 'Nexus-Direktiven',
    'building_section' => 'Gebäude',
    'construction_site' => 'Baustelle',
    'under_construction' => 'Im Bau',
    'resource_regolith' => 'Regolith',
    'harvester_regolith_remaining_label' => 'Regolith-Vorkommen (Feld):',
    'harvester_sols_remaining_label' => 'Bis Erschöpfung:',

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
    'onboarding_hint_1' => 'Kein Baumeister an Bord — AP läuft auf Minimum. Im Berater-Screen nachbessern, bevor der nächste Sol verpufft.',
    'onboarding_hint_repair' => 'Ein Gebäude zeigt deutlichen Verschleiß — reparieren, bevor der Verfall teurer wird. Gebäude antippen, dann „Reparieren" wählen (1 AP + 2 Regolith pro Punkt).',
    'onboarding_hint_repair_urgent' => 'Warnung: ein Gebäude steht kurz vor dem Stufenverlust. Jetzt reparieren — bevor der nächste Sol die Entscheidung abnimmt.',
    'onboarding_hint_2' => 'Der Harvester dreht Leerrunden — er steht noch in der Koloniezone. Auf ein erkundetes Regolith-Tile außerhalb verlegen, damit er wirklich fördert.',
    'onboarding_hint_harvester_low_regolith' => 'Das Regolith-Vorkommen des Harvesters wird knapp — ein Ausweich-Vorkommen ist bereits erkundet, jetzt verlegen.',
    'onboarding_hint_3' => 'Agrardom und zweites Gebäude stehen — jetzt die Kommandozentrale auf Level 2 ausbauen. Das erweitert die Koloniezone und schaltet den zweiten Beraterslot frei.',
    'onboarding_hint_advisor_slot2' => 'Berater-Slot 2 ist offen und das dafür nötige Gebäude steht — im Berater-Screen den passenden Berater einstellen.',
    'onboarding_hint_advisor_slot2_analytik' => 'Das Analytik-Labor wurde fertiggestellt — jetzt kannst du einen Analytiker anheuern!',
    'onboarding_hint_advisor_slot2_hangar' => 'Der Hangar wurde fertiggestellt — jetzt kannst du einen Raumfahrer anheuern!',
    'onboarding_hint_advisor_slot2_cantina' => 'Die Cantina wurde fertiggestellt — jetzt kannst du einen Konsul anheuern!',
    'onboarding_hint_4' => 'Noch keine Kenntnis auf Level 1 — im Techtree AP einer Kenntnis zuweisen. Ergebnisse kommen Sol für Sol.',
    'onboarding_hint_5' => 'Die Stimmung in der Kolonie kippt — Vertrauen stabilisieren: Zivilgebäude bauen oder reparieren, bevor es weiter fällt.',
    'onboarding_hint_build_priority' => 'Cantina, Analytik-Labor und Hangar stehen zur Wahl — aber nicht gleichzeitig: alle drei brauchen zusätzlich Kommandozentrale Lv2, und die Ressourcen reichen ohnehin nicht für alle auf einmal. Schon jetzt überlegen, welches zuerst drankommt.',
    'onboarding_hint_6' => 'Keine Cantina gebaut — Händler und Gäste legen nicht an. Tauschangebote und Einmal-Items passieren die Kolonie einfach.',
    'onboarding_hint_agrardome' => 'Erstes Bauprojekt der Kolonie: der Agrardom. Ohne ihn bleibt CC Level 2 gesperrt und Organika gleich null — heute platzieren, restliche AP hineinstecken. Investierte AP bleiben über Sol-Grenzen erhalten, auch wenn ein Sol nicht reicht.',
    'onboarding_hint_analytik' => 'Kein Analytik-Labor — AP können nirgendwo landen. Die Kolonie bleibt wissenschaftlich auf der Stelle.',
    'onboarding_hint_hangar_path' => 'Erst den Hangar bauen, dann einen Raumfahrer einstellen — erst dann stehen Missionen und AP für Erkundung richtig zur Verfügung.',
    'onboarding_hint_invest_site' => 'AP nicht verfallen lassen — in die laufende Baustelle investieren. Was eingezahlt ist, bleibt; der Abschluss rückt damit näher.',
    'onboarding_hint_explore' => 'AP für Erkundung einsetzen (1 AP pro Feld) — jenseits der Zone warten Regolith-Vorkommen und unbekannte Signale.',
    'onboarding_hint_encounter' => 'Gebäude mit niedrigem Zustand sind anfälliger für Zwischenfälle — regelmäßige Reparatur zahlt sich doppelt aus.',
    'onboarding_end_sol' => 'Sol beenden — alle sinnvollen Aktionen getätigt. Nächster Sol bringt frische Aktionspunkte und neue Entwicklungen.',
    'onboarding_hint_spend_ap_construction' => 'Es sind noch AP übrig — in ein Gebäude investieren oder CC-Ausbau vorfinanzieren. AP verfallen am Sol-Ende.',
    'onboarding_hint_spend_ap_research' => 'Es sind noch AP übrig — im Techtree einer Kenntnis zuweisen, bevor der Sol endet.',
    'onboarding_hint_spend_ap_navigation' => 'Es sind noch AP übrig — da draußen liegt noch unentdecktes Gelände. Ein Feld kostet 1 AP.',
    'onboarding_hint_spend_ap_economy' => 'Es sind noch AP übrig — in der Cantina nachsehen, ob ein Gast ein Angebot bereit hat.',
    'nav_techtree_locked' => 'Analytiklabor erforderlich.',
    'nav_cantina_locked' => 'Cantina nicht gebaut — Wohnhabitat lv1 erforderlich.',

    // ── First-visit popups (Techtree/Nexus-DB/Cantina/Hangar) ──────────────────

    'first_visit_dismiss' => 'Verstanden',
    'first_visit_techtree_title' => 'Techtree',
    'first_visit_techtree_text' => 'Hier weist du AP einer Kenntnis zu. Kenntnisse wachsen Sol für Sol — was einmal eingezahlt ist, bleibt erhalten, auch wenn ein Sol nicht reicht.',
    'first_visit_cantina_title' => 'Cantina',
    'first_visit_cantina_text' => 'Die Cantina ist der einzige Ort, wo Kolonisten für einen Moment vergessen dürfen, wie dünn die Luft draußen ist. Fremde aus dem Transit bringen Nachrichten, Waren — und manchmal Angebote, die man besser nicht verpasst. Wer selten vorbeischaut, merkt es erst, wenn das Angebot schon weg ist.',
    'first_visit_hangar_title' => 'Hangar',
    'first_visit_hangar_text' => 'Schiffe werden hier stationiert und auf Missionen geschickt. Jede Entsendung kostet Organika als Crew-Verpflegung und dauert mehrere Sols — Rückruf jederzeit möglich.',
    'first_visit_nexusdb_title' => 'Nexus-DB',
    'first_visit_nexusdb_text' => 'Technische Referenzdatenbank, herausgegeben vom Nexus — Einträge zu Gebäuden, Kenntnissen, Schiffen und Spielmechaniken. Keine Aktion erforderlich.',

    // ── Onboarding — Nexus-Briefing (INNN, event_type = 'onboarding.nexus_briefing') ──

    'onboarding_nexus_briefing_title' => 'Konzession aktiviert — Kolonie :colony',
    'onboarding_nexus_briefing_body' => 'Kommandozentrale und Harvester betriebsbereit. Startkapital: 3.000 Cr — Nexus-Darlehen, Rückzahlung ausstehend. Regolith-Reserve: 200 Rg. Lebensfähigkeit der Kolonie wird laufend bewertet. Subventionen bis auf Weiteres aktiv.',

    // ── Onboarding — Inline-Trigger-Erklärungen ───────────────────────────────

    // Trigger 2 — Supply-Cap voll (UI-Banner, 1 Satz)
    'onboarding_trigger_supply_full' => 'Keine freien Kolonisten mehr — weitere Gebäude und Forschungen können nicht in Betrieb genommen werden. Wohnhabitat ausbauen oder Verbraucher abbauen.',

    // Trigger 4 — AP-Limit (Tooltip)
    'onboarding_trigger_ap_limit' => 'Keine AP mehr in diesem Sol verfügbar.',

    // Trigger 5 — Harvester-Verlagerung (Tooltip)
    'onboarding_trigger_harvester_move' => 'Verlegen kostet 1 AP pro Tile-Distanz — lohnt nur, wenn das Ziel spürbar ergiebiger ist. AP sind knapp.',

    // ── Error messages ────────────────────────────────────────────────────────

    'error_path_gate_locked' => 'Pfad gesperrt — Kommandozentrale zuerst auf das nächste Level ausbauen.',
    'error_agrardom_required' => 'Agrardom zuerst bauen — Pflichtvoraussetzung für alle Pfad-Gebäude.',
    'error_tile_not_found' => 'Tile nicht gefunden.',
    'error_ring_locked' => 'Ring nicht freigeschaltet.',
    'error_already_explored' => 'Tile bereits erkundet.',
    'error_not_explored' => 'Tile muss zuerst erkundet werden.',
    'error_no_signal' => 'Kein Signal auf diesem Tile.',
    'error_already_scanned' => 'Tile bereits sondiert.',
    'error_no_nav_ap' => 'Nicht genug AP.',
    'error_no_nav_ap_2' => 'Nicht genug AP (2 benötigt).',
    'error_tile_not_buildable' => 'Nur bebaubare Terrain-Tiles erlaubt.',
    'error_tile_outside_colony' => 'Dieses Tile liegt außerhalb der Koloniezone.',
    'error_harvester_needs_regolith' => 'Harvester kann nur auf Regolith-Tiles platziert werden.',
    'error_harvester_second_instance_cc_gate' => 'Zweiter Harvester erst ab Kommandozentrale Level 3 möglich.',
    'error_harvester_second_instance_locked' => 'Für einen zweiten Harvester braucht es einen Bezugsweg — ein Angebot von Orin in der Cantina oder eine geborgene Anlage.',
    'harvester_move' => 'Verlegen',
    'harvester_place_second' => 'Harvester platzieren',
    'harvester_move_mode_hint' => 'Erkundetes Regolith-Tile außerhalb der Koloniezone anklicken — zeigt Vorschaupfeil mit AP-Kosten. Gedrückt halten zum Verlegen.',
    'harvester_move_no_targets' => 'Kein freies erkundetes Regolith-Tile verfügbar — erst neue Tiles erkunden (AP).',
    'harvester_move_invalid_target' => 'Kein gültiges Ziel — der Harvester braucht ein freies, erkundetes Regolith-Tile (hellblau markiert).',
    'regolith_fallback_tile_hint' => 'Erkundetes Regolith-Vorkommen — Ausweichziel für Harvester-Verlegung.',
    'network_error' => 'Netzwerkfehler — bitte erneut versuchen.',
    'error_harvester_in_transit' => 'Der Harvester ist noch unterwegs — Verlegen erst nach Ankunft möglich.',
    'harvester_in_transit' => 'Unterwegs — Ankunft nächsten Sol.',
    'error_tile_occupied' => 'Tile bereits belegt.',
    'error_no_construction_ap' => 'Nicht genug AP.',
    'error_building_not_found' => 'Gebäude nicht gefunden.',
    'error_max_level_reached' => 'Maximales Level bereits erreicht.',
    'error_supply_limit' => 'Keine freien Kolonisten für die nächste Ausbaustufe — erst Wohnraum schaffen: Wohnhabitat oder Kommandozentrale ausbauen.',
    'error_no_homeless_colonists' => 'Alle Kolonisten haben ein Dach über dem Kopf — niemand muss gehen.',
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
    'nexus_import_price_sources' => 'Preisvorteil: :sources',
    'nexus_import_total' => 'Gesamt: :total Cr',
    'nexus_import_confirm' => 'Importieren',
    'nexus_import_success' => ':amount Werkstoffe importiert (:cost Cr).',
    'nexus_import_uplink_required' => 'Uplink-Station Lv1 erforderlich — sie schaltet aktive Nexus-Anfragen frei.',
    'nexus_import_no_credits' => 'Nicht genug Credits für diesen Import.',
    'nexus_import_error' => 'Import fehlgeschlagen.',

    // Delayed Uplink-Direktimport for Regolith/Organika (F5/A28, 2026-09-11) —
    // payment immediate, delivery after N Sole (Uplink-Station-Level-abhängig).
    'nexus_import_delayed_title' => 'Nexus-Import (verzögert)',
    'nexus_import_delayed_hint' => 'Regolith oder Organika vom Nexus bestellen — Bezahlung sofort, Lieferung nach mehreren Solen.',
    'nexus_import_delayed_resource' => 'Ressource',
    'nexus_import_delayed_amount' => 'Menge',
    'nexus_import_delayed_confirm' => 'Bestellen',
    'nexus_import_delayed_success' => ':amount Einheiten bestellt — Ankunft in :ticks Solen (:cost Cr).',
    'nexus_import_delayed_error' => 'Bestellung fehlgeschlagen.',

    // ── Kolonisten-Zulage (GDD §14) ──────────────────────────────────────────

    'stipend_button' => 'Kolonisten-Zulage',
    'stipend_dialog_title' => 'Kolonisten-Zulage',
    'stipend_dialog_hint' => 'Credits direkt an die Kolonisten ausschütten — wirkt ab dem nächsten Sol auf das Vertrauen.',
    'stipend_tier_small' => 'Klein',
    'stipend_tier_medium' => 'Mittel',
    'stipend_tier_large' => 'Groß',
    'stipend_success' => 'Zulage gebucht (:cost Cr) — wirkt ab dem nächsten Sol.',
    'stipend_error' => 'Zulage fehlgeschlagen.',
    'stipend_already_used' => 'Für diesen Sol wurde bereits eine Zulage ausgeschüttet.',
    'stipend_no_credits' => 'Nicht genug Credits für diese Zulage.',

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

    // ── Marktbericht (Konsul, A13, GDD §12) ────────────────────────────────────
    'merchant_forecast_title' => 'Marktbericht',
    'merchant_forecast_tomorrow' => 'Corvan trifft morgen ein.',
    'merchant_forecast_in_sols' => 'Corvan trifft in :sols Solen ein.',
    'merchant_forecast_inventory' => 'Angekündigt: :categories',
    'merchant_category_ap_package' => 'AP-Paket',
    'merchant_category_information' => 'Information',
    'merchant_category_one_time' => 'Einmal-Item',

    // ── Orin (corporate_rep) — Harvester-Zweitinstanz Weg A (GDD §4c,
    // freigegeben 2026-08-05) ───────────────────────────────────────────────
    'corporate_contact_banner_hint' => 'Ein Angebot wartet.',
    'corporate_contact_dialog_intro' => 'Orin hat ein Harvester-Modul im Angebot — Herkunft ungeklärt, Preis fällig sofort.',
    'corporate_contact_price_label' => 'Preis',
    'error_corporate_contact_offer_unavailable' => 'Das Angebot ist nicht mehr verfügbar.',
    'error_insufficient_credits' => 'Nicht genug Credits für dieses Angebot.',

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
    'bar_offer_insufficient_ap' => 'Nicht genügend AP.',
    'bar_offer_negotiate' => 'Verhandeln',
    'bar_offer_no_consul' => 'Kein verfügbarer Konsul zugewiesen.',
    'bar_offer_already_negotiated' => 'Angebot wurde bereits verhandelt.',
    'bar_offer_negotiate_failed' => ':name hat das Angebot zurückgezogen — kein Handel, du behältst :resource, :ap AP verbraucht.',
    'bar_offer_not_negotiable' => 'Dieses Angebot ist ein Festpreis und nicht verhandelbar.',
    'trade_source_consul' => 'Konsul',
    'trade_source_trading_post' => 'Handelsposten',
    'trade_source_trade_knowledge' => 'Kenntnis Handel',

    // ── Handelsvorteil im Angebotsdialog (A13 P2b, GDD §12 "Was der Angebotsdialog zeigt") ──
    'trade_line_consul' => 'Konsul (Rang :rank)',
    'trade_line_trading_post' => 'Handelsposten (Stufe :tier)',
    'trade_line_trade_knowledge' => 'Kenntnis Handel (Lv :level)',
    'trade_short_consul' => 'Konsul Rang :rank',
    'trade_short_trading_post' => 'Handelsposten',
    'trade_short_trade_knowledge' => 'Handel Lv:level',
    'trade_hint_no_consul' => 'Kein Konsul verfügbar — kein Verhandeln möglich. Ein :rank-Konsul brächte +:percent %.',
    'trade_hint_trading_post_bar' => 'Handelsposten (ab CC :cc) würde +:percent % bringen.',
    'trade_hint_trading_post_tier' => 'Handelsposten ab Stufe :tier würde −:percent % bringen.',
    'trade_advantage_header' => 'Handelsvorteil :total (:sources)',
    'trade_price_advantage_line' => 'Preisvorteil :total (:sources)',
    'bar_offer_base_label' => 'Basisangebot (Marktwert)',
    'bar_offer_base_label_credits' => 'Basisangebot',
    'bar_offer_base_credits' => ':amount :resource für :price Cr',
    'bar_offer_advantage_label' => 'Dein Handelsvorteil',
    'bar_offer_negotiation_label' => 'Verhandlungs-Aufschlag',
    'bar_offer_receive_label' => 'Du erhältst',
    'bar_offer_plus_vs_base' => ':plus gegenüber Basis',
    'bar_offer_price_stays' => 'Der Preis bleibt bei :price Cr, der Vorteil erhöht die Menge.',
    'bar_offer_accept_safe' => 'sicher: :amount :resource (:plus)',
    'bar_offer_negotiate_win' => ':chance %: :amount :resource (:plus)',
    'bar_offer_negotiate_lose' => ':chance %: kein Handel, du behältst :resource, das Angebot verfällt, :ap AP verbraucht.',
    'bar_offer_chance_breakdown' => 'Chance :total % = Konsul Rang :rank: :base % + Kenntnis Handel +:bonus %',
    'bar_offer_chance_base_only' => 'Chance :total % (Konsul Rang :rank)',
    'bar_offer_chance_total_only' => 'Chance :total %',
    'bar_offer_negotiation_sum' => 'Verhandlungs-Aufschlag: +:percent % (:base Basis + :advantage Vorteil + :bonus Aufschlag = :total :resource)',
    'bar_offer_negotiated_label' => 'Verhandelt:',
    'bar_offer_fixed_price_note' => 'Corvan kauft :amount :resource für :credits Cr. Festpreis: Handelsvorteil und Verhandeln gelten nicht für Verkaufs-Credits.',
    'merchant_price_base_title' => 'Grundpreis :price Cr',
    'bar_offer_reserve_floor' => 'Der Verkauf würde die Nahrungsreserve der Kolonie unterschreiten.',

    // ── Cantina-Begegnungspool (GDD §12 Kanal 1, A35) ─────────────────────────
    'bar_encounter_not_found' => 'Begegnung nicht gefunden.',
    'bar_encounter_already_accepted' => 'Begegnung bereits angenommen.',
    'bar_encounter_expired' => 'Die Begegnung ist vorüber.',
    'bar_encounter_insufficient_resources' => 'Nicht genügend Ressourcen für den Einsatz.',
    'bar_encounter_insufficient_ap' => 'Nicht genügend AP.',
    'bar_encounter_wager_heading' => 'Wette mit Zara',
    'bar_encounter_wager_body' => 'Zara mustert deinen Einsatz. „Alles oder nichts — was meinst du?"',
    'bar_encounter_wager_won' => 'Gewonnen! Zara schiebt dir die Credits widerwillig rüber.',
    'bar_encounter_wager_lost' => 'Verloren. Zara grinst und streicht den Einsatz ein.',
    'bar_encounter_auction_heading' => 'Ausschuss-Ankauf bei Voss',
    'bar_encounter_auction_body' => 'Voss durchwühlt deinen Überschuss. „Das nehm ich dir ab — fairer Preis, kein Feilschen."',
    'bar_encounter_contract_heading' => 'Kurzzeit-Kontrakt',
    'bar_encounter_contract_body' => 'Ein durchreisender Abnehmer bietet ein befristetes Geschäft — Credits für ein paar Sole, dann ist er weitergezogen.',
    'bar_encounter_accept' => 'Annehmen',

    // ── Cantina-Barkeeper Tomas (A40) ────────────────────────────────────────
    // "Mit Tomas reden" — kostenlose Interaktion, kein AP-Kosten-Check. Die 4
    // Tier-Varianten spiegeln config('game.bartender.ap_bonus_tiers') (0/5/15/30
    // Interaktionen -> 0/1/2/3 AP) und werden wärmer, je vertrauter Tomas den
    // Direktor kennt.
    'bartender_talk_button' => 'Mit Tomas reden',
    'bartender_dialog_intro' => 'Tomas hört zu, während er ein Glas poliert — bei ihm ist kein Wort verschwendet, aber jedes zählt.',
    'bartender_dialog_tier_0' => '„Erzähl mir, was dich umtreibt." Er nickt knapp und hört zu — mehr nicht, noch kennt er dich nicht gut genug.',
    'bartender_dialog_tier_1' => 'Tomas hält kurz inne. „Dich seh ich öfter." Er schiebt dir wortlos einen Gedanken zu, der weiterhilft.',
    'bartender_dialog_tier_2' => 'Er stellt das Glas ab und setzt sich für einen Moment hin. „Du bist kein Fremder mehr hier." Was er dir mitgibt, wiegt mehr als sonst.',
    'bartender_dialog_tier_3' => 'Tomas lächelt kurz — selten genug, dass es auffällt. „Du gehörst hierher." Er gibt dir mehr, als er den meisten je gegeben hat.',

    // ── Charakter-Anliegen (A41) ────────────────────────────────────────────────
    // Je Figur eine Ansprache-Zeile + Erfolgs-Zeile; eine Fehlschlag-Zeile nur
    // dort, wo config('game.bar.concern.success_chance.<slug>') < 1.0 ist
    // (prospector 0.50, founder 0.70, preacher 0.65, stranger 0.35 — die
    // übrigen 5 Figuren gelingen garantiert, siehe config/game.php).
    'bar_concern_not_found' => 'Anliegen nicht gefunden.',
    'bar_concern_already_resolved' => 'Anliegen wurde bereits erledigt.',
    'bar_concern_expired' => 'Das Anliegen ist erledigt — die Person ist weitergezogen.',
    'bar_concern_insufficient_resources' => 'Nicht genügend Ressourcen für den Einsatz.',
    'bar_concern_insufficient_ap' => 'Nicht genügend AP.',
    'bar_concern_heading' => 'Anliegen',
    'bar_concern_resolve' => 'Helfen',

    'bar_concern_smuggler_intro' => 'Dax rutscht dichter heran, den Blick auf die Tür gerichtet. „Ich brauch für ein paar Tage eine Geschichte, die niemand hinterfragt — Deckung, nichts weiter."',
    'bar_concern_smuggler_success' => 'Dax nickt knapp. Tage später steht unangemeldet ein Schiff im Hangar — seine Art, sich erkenntlich zu zeigen.',

    'bar_concern_information_broker_intro' => 'Vesper legt die Hände auf den Tisch. „Ich weiß von einer Schwachstelle in eurer nächsten Bauplanung — die Information hat ihren Preis, aber sie ist es wert."',
    'bar_concern_information_broker_success' => 'Vesper schiebt dir ein Datenfragment zu — die nächste Bauinvestition der Kolonie wird günstiger ausfallen, als sie es sonst täte.',

    'bar_concern_mechanic_intro' => 'Sarka wischt sich die Hände an einem Lappen ab. „Ich komm allein nicht mehr durch die Wartungsliste. Ein paar Hände, die mitdenken, würden reichen."',
    'bar_concern_mechanic_success' => 'Zu zweit geht es schneller, als Sarka erwartet hat. Sie knurrt ein „passt schon" — von ihr fast ein Kompliment — und gibt dir weiter, was sie dabei gelernt hat.',

    'bar_concern_doctor_intro' => 'Maret reibt sich die Augen. „Mir fehlen Ersatzteile für die Medizintechnik — nichts Dramatisches, aber ohne komm ich nicht weiter."',
    'bar_concern_doctor_success' => 'Mit den beschafften Teilen lässt sich improvisieren. Maret bedankt sich knapp und gibt dir im Gegenzug etwas ab von dem, was in der Krankenstation übrig ist.',

    'bar_concern_prospector_intro' => 'Fen wiegt eine Probe in der Hand. „Ich hab eine Spur auf ein Vorkommen — unbestätigt, vielleicht nichts. Wer sich traut, kann sie verfolgen."',
    'bar_concern_prospector_success' => 'Die Spur war echt. Fen grinst zufrieden, als du mit vollen Händen zurückkommst — auch wenn sie tut, als hätte sie nie gezweifelt.',
    'bar_concern_prospector_failure' => 'Das Vorkommen erweist sich als taub. Fen zuckt mit den Schultern. „Passiert. Nächstes Mal wieder."',

    'bar_concern_mercenary_intro' => 'Juno mustert dich lange, bevor sie spricht. „Eure Sicherheitslage hat Lücken. Ich sag dir, wo — für eine angemessene Gegenleistung."',
    'bar_concern_mercenary_success' => 'Junos Einschätzung ist präzise wie erwartet. Die vereinbarte Gegenleistung wechselt wortlos den Besitzer.',

    'bar_concern_founder_intro' => 'Aldra kramt in einer alten Tasche. „Ich hab noch Baupläne aus den ersten Jahren — vielleicht taugt was davon noch heute."',
    'bar_concern_founder_success' => 'Die alten Pläne sind tatsächlich brauchbar — vieles hat sich seit den ersten Jahren kaum verändert. Aldra nickt zufrieden, als hätte sie es gewusst.',
    'bar_concern_founder_failure' => 'Die Pläne sind zu veraltet, um noch zu passen. Aldra seufzt. „Manches bleibt eben in der Vergangenheit."',

    'bar_concern_preacher_intro' => 'Sorel faltet die Hände. „In der Kolonie gärt ein Streit, den niemand offen ausspricht. Jemand muss vermitteln, bevor er größer wird."',
    'bar_concern_preacher_success' => 'Die Vermittlung gelingt — nicht perfekt, aber genug. Sorel nickt zufrieden. „Das war es wert."',
    'bar_concern_preacher_failure' => 'Der Streit lässt sich nicht schlichten, im Gegenteil. Sorel presst die Lippen zusammen. „Manche Wunden brauchen mehr als ein Gespräch."',

    'bar_concern_stranger_intro' => 'Die Fremde schiebt dir einen Umschlag zu, ohne aufzuschauen. Kein Name, keine Erklärung — nur eine Erwartung, die im Raum steht.',
    'bar_concern_stranger_success' => 'Was auch immer erwartet wurde, ist erledigt. Die Fremde nimmt einen Schluck aus ihrem Glas und sagt kein Wort dazu — nur die Bezahlung liegt bereit.',
    'bar_concern_stranger_failure' => 'Etwas läuft schief — was genau, bleibt unklar. Die Fremde verschwindet, ohne eine Erklärung zu hinterlassen. Der Einsatz ist verloren.',

    // ── Deva & Lenn — taktische Information (A42) ──────────────────────────────
    // Alle 8 Ausgänge (4 pro Figur) bereits final formuliert.
    'bar_information_not_found' => 'Gespräch nicht gefunden.',
    'bar_information_already_resolved' => 'Das Gespräch ist bereits beendet.',
    'bar_information_expired' => 'Die Gelegenheit ist vorüber.',
    'bar_information_heading' => 'Taktische Information',
    'bar_information_resolve' => 'Nachfragen',
    'bar_information_veteran_drill_buffer' => 'Deva zeigt dir einen Handgriff, der im Ernstfall Zeit kostet — dem Gegner.',
    'bar_information_veteran_knowledge_boost' => 'Deva denkt kurz nach und gibt dir einen gezielten Hinweis weiter.',
    'bar_information_veteran_narrative_1' => 'Deva erzählt beiläufig von einem alten Einsatz — nichts, was dir hier weiterhilft.',
    'bar_information_veteran_narrative_2' => 'Deva nickt dir knapp zu und schweigt den Rest des Gesprächs.',
    'bar_information_ai_researcher_nav_discount' => 'Lenn tippt etwas in ein Datenpad und reicht es dir — die nächste Erkundung ist vorbereitet.',
    'bar_information_ai_researcher_knowledge_boost' => 'Lenn murmelt etwas über Kartierungsalgorithmen und schiebt dir ihre Notizen zu.',
    'bar_information_ai_researcher_narrative_1' => 'Lenn verliert sich in einer Theorie, der du nicht ganz folgen kannst.',
    'bar_information_ai_researcher_narrative_2' => 'Lenn wirkt abgelenkt und wechselt schnell das Thema.',

    // ── Charakter-Kodex (A42) ───────────────────────────────────────────────────
    // 5 Lore-Snippets je der 15 config('characters')-Figuren (Key-Muster
    // codex_entry_<slug>_<1-5>, entry_number aus CharacterCodexService).
    // Vertiefen/ergänzen die Charakterbögen (docs/characters/*.md), schreiben
    // sie nicht ab. Kurz gehalten — Sammel-Nachschlagewerk, keine Kurzgeschichten.
    'codex_heading' => 'Charakter-Kodex',

    'codex_entry_bartender_1' => 'Vor der Kolonie diente Tomas auf einer Versorgungsstation im Outer Reach — was ihn von dort wegtrieb, hat er nie erzählt.',
    'codex_entry_bartender_2' => 'Die drei Balken und der Kreis auf seinem Unterarm sind das Kennzeichen einer Station, die es offiziell nicht mehr gibt.',
    'codex_entry_bartender_3' => 'Tomas führt kein Namensregister — und braucht keines. Er merkt sich jedes Gesicht, jede Bestellung, jeden Groll.',
    'codex_entry_bartender_4' => 'Fragt man ihn nach seiner Herkunft, wechselt er das Thema mit einer neuen Runde.',
    'codex_entry_bartender_5' => 'Manche sagen, Tomas habe die Cantina übernommen, weil sie der letzte Ort war, an dem ihn niemand nach seinem Namen fragte.',

    'codex_entry_smuggler_1' => 'Dax kennt Depots, die in keinem Nexus-Register auftauchen — und Patrouillen, die genau dann wegsehen, wenn er es braucht.',
    'codex_entry_smuggler_2' => 'Er sitzt nie mit dem Rücken zur Tür und verlässt die Cantina nie durch den Haupteingang — beides aus Gewohnheit, nicht aus Zufall.',
    'codex_entry_smuggler_3' => 'Zwanzig Jahre im Geschäft haben ihm keine Loyalitäten hinterlassen, nur Preise.',
    'codex_entry_smuggler_4' => 'Was er transportiert, fragt er selten — solange die Bezahlung stimmt.',
    'codex_entry_smuggler_5' => 'Es gibt Gerüchte über eine Fracht, die er einmal nicht ausgeliefert hat. Er bestreitet, dass es sie je gab.',

    'codex_entry_information_broker_1' => 'Vesper nennt keine Herkunft — jede Antwort darauf wäre ohnehin nur eine weitere Information, die sie verkaufen könnte.',
    'codex_entry_information_broker_2' => 'Der kleine Datenstecker hinter ihrem Ohr ist mehr als Zierrat — manche behaupten, sie zeichnet jedes Gespräch auf, das sie führt.',
    'codex_entry_information_broker_3' => 'Ihre Kontakte reichen angeblich bis in Nexus-nahe Strukturen — bewiesen hat sie das nie, bestritten auch nicht.',
    'codex_entry_information_broker_4' => 'Sie taucht immer genau dann auf, wenn sie etwas weiß, das gerade relevant wird — Zufall ist das nicht.',
    'codex_entry_information_broker_5' => 'Ein Gespräch mit Vesper fühlt sich immer wie ein Tausch an, auch wenn man nie merkt, was man selbst preisgegeben hat.',

    'codex_entry_mechanic_1' => 'Sarka hält drei Viertel der Verteilersysteme der Kolonie eigenhändig am Laufen — offiziell zählt dazu nicht ihr Schlaf.',
    'codex_entry_mechanic_2' => 'Sie kann keinen Raum betreten, ohne Scharniere zu prüfen und Ventile zu bewerten — auch die Cantina bleibt davon nicht verschont.',
    'codex_entry_mechanic_3' => 'Ihre Überstunden würde niemand je bezahlen können — sie hat aufgehört, sie zu zählen.',
    'codex_entry_mechanic_4' => 'Ihre Einschätzungen sind ungeschönt und deshalb verlässlich — Sarka sagt nie mehr, als nötig ist, aber nie weniger.',
    'codex_entry_mechanic_5' => 'Wer sie nach ihrem letzten freien Tag fragt, bekommt nur ein müdes Lachen als Antwort.',

    'codex_entry_corporate_rep_1' => 'Niemand hat je herausgefunden, für wen Orin tatsächlich arbeitet — fragt man ihn, nennt er jedes Mal eine andere Firma.',
    'codex_entry_corporate_rep_2' => 'Er taucht auf, wenn Lieferverträge, Abbaurechte oder regulatorische Fragen zu klären sind — nie ohne Grund.',
    'codex_entry_corporate_rep_3' => 'Sein Datenpad verlässt ihn nie — was darauf gespeichert ist, zeigt er niemandem.',
    'codex_entry_corporate_rep_4' => 'Er ist höflich auf eine Art, die keine Nähe zulässt — Freundlichkeit als Werkzeug, nicht als Angebot.',
    'codex_entry_corporate_rep_5' => 'Was er wirklich will, deckt sich selten mit dem, was er sagt — das weiß inzwischen jeder in der Cantina.',

    'codex_entry_founder_1' => 'Aldra war da, als die ersten Strukturmodule gesetzt wurden — lange bevor Nexus mitzureden hatte.',
    'codex_entry_founder_2' => 'Sie nennt jeden Ort in der Kolonie noch bei seinem ursprünglichen Namen — Namen, die sonst niemand mehr kennt.',
    'codex_entry_founder_3' => 'Ihre Pläne für die Kolonie sahen eine Siedlung vor, die sich selbst trägt — keine Versorgungsstation für Konzerninteressen. Daraus wurde nichts.',
    'codex_entry_founder_4' => 'Sie ist geblieben, weil sie nirgendwo sonst hingehört — nicht, weil sie mit dem einverstanden ist, was aus ihrer Kolonie wurde.',
    'codex_entry_founder_5' => 'Den Direktor beobachtet sie mit einer Mischung aus Hoffnung und Skepsis — als hätte sie diesen Blick schon oft gehabt.',

    'codex_entry_gambler_1' => 'Zara wettet auf alles — die Lebensdauer eines Gebäudes, das nächste Lieferdatum, die Regenwahrscheinlichkeit am nächsten Morgen.',
    'codex_entry_gambler_2' => 'Sie betrügt nicht — zumindest nicht schlecht — und hat den Ruf, ihre Schulden immer zu begleichen.',
    'codex_entry_gambler_3' => 'Seit Jahren hat sie kein festes Quartier und offenbar keine Notwendigkeit, sich eines zu leisten.',
    'codex_entry_gambler_4' => 'Woher ihr Startkapital stammte, weiß niemand mehr — sie selbst am wenigsten, oder sie sagt es nur so.',
    'codex_entry_gambler_5' => 'Der glatte Metallring an ihrem Finger wandert ständig zwischen Drehen und Abnehmen — niemand hat sie je gefragt, warum.',

    'codex_entry_ai_researcher_1' => 'Lenn arbeitete früher für eine Nexus-nahe Forschungseinrichtung — bis er die genehmigten Parameter zu weit hinter sich ließ.',
    'codex_entry_ai_researcher_2' => 'Was er entwickelt, verrät er nicht — nur, dass es Rechenleistung braucht, die die Kolonie kaum überwacht.',
    'codex_entry_ai_researcher_3' => 'Sein Ohrstecker mit der kleinen LED ist fast nie aus — ob dort ein Programm läuft oder etwas anderes, bleibt offen.',
    'codex_entry_ai_researcher_4' => 'Manchmal führt er halbe Gespräche mit jemandem, der nicht da ist — niemand fragt mehr nach, warum.',
    'codex_entry_ai_researcher_5' => 'Gelegentlich braucht er Bauteile, die in keinem regulären Katalog stehen — woher er die sonst bekommt, weiß niemand.',

    'codex_entry_veteran_1' => 'Deva diente dreißig Jahre, zuletzt als Kompaniekommandantin bei Sicherungsmissionen an der Grenze unkartierter Sektoren.',
    'codex_entry_veteran_2' => 'Sie ist nicht aus militärischen Gründen in der Kolonie — sie suchte jemanden, fand die Person nie und blieb.',
    'codex_entry_veteran_3' => 'Sicherheitsprotokolle und taktische Einschätzungen teilt sie nur, wenn sie den Anlass für würdig hält.',
    'codex_entry_veteran_4' => 'Die alte Narbe von der Schläfe bis zum Kinn erklärt sie nie — wer danach fragt, bekommt keine Antwort.',
    'codex_entry_veteran_5' => 'Sie trinkt nie mehr als zwei Gläser — und beobachtet jeden, der mehr trinkt als sie.',

    'codex_entry_stranger_1' => 'Niemand weiß, wann diese Person zum ersten Mal in der Cantina saß — sie war irgendwann einfach da.',
    'codex_entry_stranger_2' => 'Sie berührt ihr Glas kaum, sitzt aber stundenlang daran, als wäre die Zeit für sie etwas anderes.',
    'codex_entry_stranger_3' => 'Tomas bedient sie, ohne zu fragen — was auch immer der Grund dafür ist, kennt nur er.',
    'codex_entry_stranger_4' => 'Ihre Fragen wirken im Moment harmlos — erst Stunden später merkt man, wie seltsam sie eigentlich waren.',
    'codex_entry_stranger_5' => 'Woher sie kommt, mit wem sie reist, warum sie hier ist — niemand hat je eine Antwort bekommen.',

    'codex_entry_doctor_1' => 'Marets Dienst endet offiziell um 22 Uhr — praktisch endet er nie.',
    'codex_entry_doctor_2' => 'Sie ist die einzige ausgebildete Ärztin der Kolonie — eine Tatsache, die ihr keine Ruhe lässt.',
    'codex_entry_doctor_3' => 'In der Cantina sucht sie Stille, findet aber meist Patienten, die wissen, wo sie sitzt.',
    'codex_entry_doctor_4' => 'Sie verspricht nie mehr, als sie halten kann — auch wenn das bedeutet, wenig zu versprechen.',
    'codex_entry_doctor_5' => 'Für große Gesten hat sie keine Kraft mehr übrig — für den Einzelnen vor ihr findet sie trotzdem noch etwas.',

    'codex_entry_preacher_1' => 'Sorel zitiert keine Schriften — sie argumentiert, als erkläre sie etwas Selbstverständliches, das andere nur noch nicht gesehen haben.',
    'codex_entry_preacher_2' => 'Sie vertritt eine Weltanschauung, keine Religion — und lässt diesen Unterschied nie unerwähnt.',
    'codex_entry_preacher_3' => 'Ihr Einfluss reicht in Fragen, wie Ressourcen verteilt werden und wie die Kolonie mit Fremden umgeht.',
    'codex_entry_preacher_4' => 'Das handbedruckte Tuch um ihren Hals wechselt sie regelmäßig — als hätte jedes eine eigene Bedeutung.',
    'codex_entry_preacher_5' => 'Ob sie recht hat mit dem, was sie predigt, ist eine Frage, die in der Cantina niemand laut zu stellen wagt.',

    'codex_entry_prospector_1' => 'Fen zieht seit drei Jahrzehnten zwischen Kolonien und Außenposten umher — immer auf der Suche nach Vorkommen, die offizielle Vermessungsteams übersehen haben.',
    'codex_entry_prospector_2' => 'Sie kann nicht anders, als den materiellen Wert von allem um sich herum abzuschätzen — auch von Dingen, die niemand verkaufen will.',
    'codex_entry_prospector_3' => 'Sie behauptet, wegen Tomas\' Bier zurückzukommen — dass die Kolonie ein guter Stützpunkt ist, gibt sie nur zögernd zu.',
    'codex_entry_prospector_4' => 'Informationen liefert sie wie Ware — nüchtern, ohne Verkaufsgespräch, ohne Übertreibung.',
    'codex_entry_prospector_5' => 'Manche ihrer Spuren erweisen sich als taub — sie nimmt das mit derselben Gelassenheit wie einen Treffer.',

    'codex_entry_mercenary_1' => 'Juno hat für diverse Auftraggeber gearbeitet — keiner davon mit einem Namen, der in einem öffentlichen Verzeichnis auftaucht.',
    'codex_entry_mercenary_2' => 'Vor zwei Jahren hat sie aufgehört, aktive Aufträge anzunehmen. Warum, sagt sie nicht.',
    'codex_entry_mercenary_3' => 'Sie kam zur Kolonie, weil sie hier niemanden kannte — das war ursprünglich der einzige Grund.',
    'codex_entry_mercenary_4' => 'Über konkrete Einsätze spricht sie nie — nur über Prinzipien, Kosten und Konsequenzen.',
    'codex_entry_mercenary_5' => 'Wie sie einen Raum liest, sagt mehr über sie als jede Ausrüstung, die sie nicht mehr trägt.',

    'codex_entry_scrap_dealer_1' => 'Voss kauft, sammelt und verkauft, was andere wegwerfen oder nicht mehr brauchen — sein Lagerbestand ist Chaos, sein Inventar lebt nur in seinem Kopf.',
    'codex_entry_scrap_dealer_2' => 'Er beginnt Preisverhandlungen immer mit einer wild überzogenen Zahl — und macht danach trotzdem ein faires Angebot.',
    'codex_entry_scrap_dealer_3' => 'Er handelt gelegentlich mit Dingen, deren Herkunft er lieber nicht genauer beschreibt.',
    'codex_entry_scrap_dealer_4' => 'Stille empfindet er als Zeitverschwendung — sein Redefluss versiegt praktisch nie.',
    'codex_entry_scrap_dealer_5' => 'Trotz seines Rufs als schlechter Verhandler ist noch niemand von ihm betrogen worden.',

    // ── Cantina-Charakter-Zuordnung (A36) ──────────────────────────────────────
    // bar_trade — personalisierte Zeile im Gäste-Tauschangebot-Dialog, zeigt sich
    // zusätzlich zum generischen Geben/Bekommen-Raster, kein neuer Mechanismus.
    'bar_trade_flavor_prospector' => 'Fen wiegt einen Brocken Regolith in der Hand, als würde sie ihn bereits taxieren.',
    'bar_trade_flavor_doctor' => 'Maret schiebt dir müde ein kleines Bündel Organika rüber — „Nimm, bevor\'s schlecht wird."',
    'bar_trade_flavor_mercenary' => 'Juno mustert dich einen Moment, dann legt sie wortlos etwas Brauchbares auf den Tisch.',
    'bar_trade_flavor_smuggler' => 'Dax rutscht näher, den Rücken zur Wand, und murmelt etwas von Fracht, die niemand vermissen wird.',
    'bar_trade_flavor_information_broker' => 'Vesper lächelt knapp, als hätte sie schon gewusst, dass du fragen würdest — und nennt ihren Preis.',
    'bar_trade_flavor_mechanic' => 'Sarka klopft prüfend gegen ein Ersatzteil, bevor sie es dir zuschiebt: „Läuft noch. Meistens."',

    // story_hook — reiner Flavor-Moment, keine Ressourcen-/Credits-Wirkung.
    'story_encounter_preacher' => 'Sorel spricht ruhig davon, was die Kolonie ihren Siedlern schuldet, während sie in Gedanken versunken ihr Tuch glättet.',
    'story_encounter_founder' => 'Aldra deutet auf einen Winkel der Cantina und nennt ihn beim alten Namen, den niemand sonst mehr kennt.',
    'story_encounter_stranger' => 'Die Fremde stellt eine beiläufige Frage, die dir erst später merkwürdig vorkommt, und wendet sich wieder ihrem unberührten Glas zu.',
    'story_encounter_close' => 'Schließen',

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
    'hangar_dispatch_no_nav_ap' => 'Nicht genug AP für diese Mission.',
    'hangar_dispatch_no_organika' => 'Nicht genug Organika für die Crew-Verpflegung dieser Mission.',
    'hangar_request_level_too_low' => 'Dieser Schiffstyp erfordert einen höheren Hangar-Ausbau.',
    'hangar_request_min_level' => 'Erfordert Hangar-Ausbaustufe :level',
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
    'hangar_consul_required' => 'Für eine Verhandlung wird ein verfügbarer Konsul benötigt.',
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
    'sol_report_passive_credits' => 'Nexus-Subvention & Relaisvergütung',
    'sol_report_advisor_hired' => 'Berater eingestellt',
    'sol_report_advisor_hired_detail' => ':name — -:cost Cr',
    'sol_report_stipend' => 'Kolonisten-Zulage',
    'sol_report_stipend_detail' => '-:cost Cr — +:trust Vertrauen',
    'sol_report_event_storm' => 'Sturm über der Kolonie',
    'sol_report_storm_detail' => 'Der Wartungszustand der Anlagen hat entschieden: :abgewehrt abgewehrt, :beschaedigt beschädigt, :kritisch kritisch.',
    'sol_report_event_instability' => 'Geologische Instabilität',
    'sol_report_instability_detail' => 'Nach :sols Solen ohne Standortwechsel: Harvester-Ertrag gestört.',
    'sol_report_event_plague' => 'Seuchenausbruch',
    'sol_report_plague_detail_hunger' => 'Nahrungsknappheit (:streak Sole ohne Vorräte) hat den Ausbruch ausgelöst — Aktionspunkte vorübergehend reduziert.',
    'sol_report_plague_detail_trust' => 'Kritisches Vertrauen (:trust) hat den Ausbruch ausgelöst — Aktionspunkte vorübergehend reduziert.',
    'sol_report_event_colonists_left' => 'Kolonisten abgewandert',
    'sol_report_colonists_left_detail' => ':count Kolonisten ohne Unterkunft haben ihre Sachen gepackt und die Siedlung verlassen. Ihre Arbeitsplätze bleiben leer, bis neuer Wohnraum sie zurückholt.',
    'sol_report_event_colonists_dismissed' => 'Kolonisten weggeschickt',
    'sol_report_colonists_dismissed_detail' => 'Auf Anweisung des Direktors haben :count Kolonisten ohne Unterkunft die Siedlung verlassen. Neuer Wohnraum holt sie zurück.',
    'sol_report_event_colonists_returned' => 'Kolonisten zurückgekehrt',
    'sol_report_colonists_returned_detail' => ':count Kolonisten sind in den neuen Wohnraum eingezogen — ihre Arbeitsplätze sind wieder besetzt.',

    // Produktion
    'sol_report_no_production' => 'Die Förderanlagen stehen still — kein Regolith, kein Fortschritt. Industriegebäude prüfen.',
    'sol_report_food' => 'Verpflegung',
    'sol_report_food_ok' => ':amount Organika in die Küchen geflossen — die Kolonie ist versorgt',
    'sol_report_overcap' => 'Unterkunft',
    'sol_report_overcap_homeless' => ':homeless Kolonisten schlafen ohne Unterkunft — Vertrauen −:malus. Ohne neuen Wohnraum wandern sie in :sols Sol ab.',
    'sol_report_staffing' => 'Personal',
    'sol_report_understaffed' => ':departed Arbeitsplätze unbesetzt — Rohstoffproduktion bei :pct %. Neubau und Ausbau bleiben gesperrt, bis neuer Wohnraum die Abgewanderten zurückholt.',
    'sol_report_food_shortage' => 'Vorräte erschöpft — die Kolonisten hungern. Vertrauen sinkt. Agrardom bauen oder reparieren.',

    // Kolonie & Personal
    'sol_report_advisor' => 'Berater',
    'sol_report_advisor_promoted' => 'befördert zu Rang :rank',

    // Run
    'sol_report_phase_reached' => 'Phase :phase erreicht — neue Möglichkeiten verfügbar',
    'sol_report_objectives' => 'Ziele erfüllt: :done / :total',
    'sol_report_sol_counter' => 'Sol :sol von :limit',

    // Finale (Run-Ende)
    'sol_report_finale_win_title' => 'Mission erfüllt',
    'sol_report_finale_win_body' => 'Die Kolonie steht. Gegen Verfall, Knappheit und alles was der Planet ihr entgegengeworfen hat — sie steht.',
    'sol_report_finale_lose_title' => 'Mission gescheitert',
    'sol_report_finale_lose_body' => 'Diese Kolonie hat nicht überlebt. Was bleibt, wird vermerkt und übergeben — und irgendwann beginnt von vorne, wer genug gelernt hat.',

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

    'sol_report_finale_win_cta' => 'Run abschließen',
    'sol_report_finale_lose_cta' => 'Run beenden',

    // Encounter-Gefahrenbanner (GDD §9) — eigene, dringlichere Zeile über dem
    // normalen Hint-Vorschlag, Owner-Playtest-Fund 2026-08-31: Sturmwarnung etc.
    // waren zuvor nur im Protokoll sichtbar, leicht zu übersehen.
    //
    // Sturm ist seit 2026-09-03 koloniweiter Scope (trifft alle Zone-Gebäude
    // statt eines einzelnen Ziels) — die Warnung nennt daher kein Gebäude mehr,
    // sondern verweist auf den Wartungszustand als entscheidenden Faktor.
    'encounter_notice_storm_warning' => 'Sturmwarnung: Ein Sturm zieht über die Kolonie auf. Gepflegte Anlagen werden ihm standhalten, vernachlässigte nicht.',
    'encounter_notice_storm_resolved' => 'Sturm vorüber: Der Wartungszustand der Anlagen hat entschieden — :abgewehrt abgewehrt, :beschaedigt beschädigt, :kritisch kritisch.',

    // Alt: einzelgebäude-bezogene Varianten, seit dem koloniweiten Sturm-Scope
    // nicht mehr erreichbar (die zugehörigen colony_log-Events werden nicht
    // mehr geschrieben, siehe GameTick::resolveStormWarning()). Belassen falls
    // Cleanup der toten EncounterNoticeService::EVENT_KEYS-Einträge ansteht.
    'encounter_notice_storm_abgewehrt' => 'Sturm bei :building erfolgreich abgewehrt — kein Schaden.',
    'encounter_notice_storm_beschaedigt' => 'Sturmschaden an :building.',
    'encounter_notice_storm_kritisch' => 'Kritischer Sturmschaden an :building — Level gesunken.',

    // ── UI: Tomas-Dialog, Anliegen-/Informations-Dialog-Chrome, Kodex-Nav (A40/A41/A42) ──
    'user_nav_codex' => 'Charakter-Kodex',
    'bartender_knowledge_label' => 'In welche Kenntnis fließt der Hinweis?',
    'bartender_error_cooldown' => 'Tomas hat dir für heute schon einen Rat mitgegeben — versuch es nächstes Sol wieder.',
    'bartender_error_generic' => 'Das Gespräch kam nicht zustande.',
    'bar_concern_knowledge_label' => 'In welche Kenntnis soll die Hilfe fließen?',
    'bar_information_knowledge_label' => 'Welche Kenntnis soll profitieren?',
    'codex_page_intro' => 'Jede Figur der Cantina hinterlässt Spuren — je öfter ihr euch begegnet, desto mehr erfährst du über sie. Freigeschaltete Einträge bleiben dir über das Ende eines Runs hinaus erhalten.',
    'codex_locked_entry' => '???',
    'codex_no_entries_yet' => 'Noch keine Einträge freigeschaltet.',

];
