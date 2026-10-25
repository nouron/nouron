<?php

return [

    // ── Tile actions ──────────────────────────────────────────────────────────

    'explore' => 'Explore',
    'deep_scan' => 'Probe',
    'invest_ap' => 'Invest AP',
    'tile_colonists' => 'Colonists employed',
    'levelup_cost_label' => 'Cost:',
    'levelup_cost_suffix' => 'due with the first AP',
    'levelup_cost_already_paid' => 'already paid',
    'build_cost_erect' => 'build',
    'build_cost_first_level' => 'level 1',
    'first_level_prepaid' => 'Level 1 was paid on placement — completing it only costs AP.',
    'repair' => 'Repair',
    'repair_full_tooltip' => 'Condition is already at 100 %, nothing to repair.',
    'ap_per_tile' => ':n AP/tile',
    // Condition display above the Repair button; "Condition" is the consistent term.
    'condition_label' => 'Condition',

    // ── Repair errors ─────────────────────────────────────────────────────────

    'error_repair_under_construction' => 'Building is still under construction and cannot be repaired.',
    'error_repair_full' => 'Building is already fully intact.',

    // ── Building actions ──────────────────────────────────────────────────────

    'build' => 'Build',
    'cancel' => 'Cancel',
    'select_tile_hint' => 'Click a tile to place',

    // ── Sidebar labels ────────────────────────────────────────────────────────

    'tile_info' => 'Tile Info',
    'terrain_details' => 'Terrain & Location',

    // ── Hex-Grid legend ───────────────────────────────────────────────────────
    'legend_title' => 'Legend',
    'legend_buildable' => 'Buildable tile',
    'legend_soon_buildable' => 'Soon buildable (with CC upgrade)',
    'legend_zone_fog' => 'Buildable, not yet explored — building reveals it',
    'legend_explore_fog' => 'Exploration target — reveal with AP',
    'legend_regolith' => 'Regolith deposit',
    'legend_cc' => 'Command Center',
    'legend_hazard' => 'Hazard zone',
    'legend_impassable' => 'Impassable',
    'legend_event' => 'Discovered event',

    // ── Sidebar: Terrain / zone info ──────────────────────────────────────────
    'zone_buildable' => 'Colony zone — buildable',
    'zone_soon' => 'Soon buildable (next Command Center upgrade)',
    'zone_outside' => 'Outside the colony zone',
    'zone_unexplored' => 'Unexplored — explore (AP)',
    'terrain_label' => 'Terrain',
    'event_label' => 'Phenomenon',
    'coords_label' => 'Coordinates',
    'hint_regolith_target' => 'Target for Harvester relocation.',
    'hint_hazard' => 'Hazard zone — building is risky, elevated decay.',
    'hint_impassable' => 'Impassable — nothing can be built here.',
    'click_tile_hint' => 'Click a hex tile to view details.',
    'phase1_progress_title' => 'Phase 1 Objectives',
    'phase2_progress_title' => 'Nexus Directives',
    'building_section' => 'Building',
    'construction_site' => 'Construction Site',
    'under_construction' => 'Under Construction',
    'resource_regolith' => 'Regolith',
    'harvester_regolith_remaining_label' => 'Regolith deposit (tile):',
    'harvester_sols_remaining_label' => 'Until depleted:',

    // ── Status chips ──────────────────────────────────────────────────────────

    'chip_locked' => 'Locked',
    'chip_unexplored' => 'Unexplored',
    'chip_explored' => 'Explored',
    'chip_scanned' => 'Probed',
    'chip_signal' => 'Signal',

    // ── Build mode ────────────────────────────────────────────────────────────

    'build_mode_title' => 'Build Structure',
    'build_mode_hint' => 'Select a building, then click a tile on the map.',
    'building_info_label' => 'Show building info',
    'no_buildings' => 'No buildings available.',
    'inprogress_label' => 'Under Construction',
    'inprogress_hint' => 'Click the tile on the map to invest AP.',
    'levelup_built' => 'Construction complete:',

    // ── Event discovery popup ─────────────────────────────────────────────────

    'discovery_title' => 'Signal Decoded',
    'discovery_dismiss' => 'Understood',

    // ── Onboarding hints (Phase 3e) ───────────────────────────────────────────

    'hint_suggestion_label' => 'Suggestion',
    'hint_not_mandatory' => 'Suggestion, not mandatory — other build orders are possible.',
    'onboarding_hint_1' => 'No Engineer on board — AP is running at minimum. Fix this in the Advisor screen before another Sol slips by.',
    'onboarding_hint_repair' => 'A building is showing serious wear — repair it before decay gets more expensive. Tap the building, then choose "Repair" (1 AP + 2 Regolith per point).',
    'onboarding_hint_repair_urgent' => 'Warning: a building is close to losing a level. Repair now — before the next Sol makes the decision for you.',
    'onboarding_hint_2' => 'The Harvester is running empty laps — it is still inside the colony zone. Relocate it to an explored Regolith tile outside the zone so it actually produces.',
    'onboarding_hint_harvester_low_regolith' => 'The Harvester\'s Regolith deposit is running low — a fallback deposit has already been explored, relocate now.',
    'onboarding_hint_3' => 'The Agrarian Dome is up — now upgrade the Command Center to Level 2. That expands the colony zone, unlocks the second advisor slot and makes the Science Lab, Hangar and Cantina buildable.',
    'onboarding_hint_advisor_slot2' => 'Advisor slot 2 is open and the building it requires is up — hire the matching advisor in the Advisor screen.',
    'onboarding_hint_advisor_slot2_analytik' => 'The Analytics Lab is finished — now you can hire an Analyst!',
    'onboarding_hint_advisor_slot2_hangar' => 'Your ship is ready — now you can hire a Pilot!',
    'onboarding_hint_advisor_slot2_cantina' => 'The Cantina is finished — now you can hire a Consul!',
    'onboarding_hint_4' => 'No knowledge at Level 1 yet — assign AP to a knowledge field in the tech tree. Results build up Sol by Sol.',
    'onboarding_hint_5' => 'Colony mood is turning — stabilise Trust: build or repair a civil structure before it drops further.',
    'onboarding_hint_build_priority' => 'Cantina, Analytics Lab and Hangar are up for choice — but not all at once: all three also need Command Center Lv2, and resources won\'t stretch to all of them anyway. Start thinking now about which one comes first.',
    'onboarding_hint_6' => 'No Cantina built — traders and guests won\'t dock. Trade offers and one-time items simply pass the colony by.',
    'onboarding_hint_agrardome' => 'The colony\'s first build project: the Agrarian Dome. Without it CC Level 2 stays locked and Organics stay at zero — place it today and sink the remaining AP into it. Invested AP carries over across Sols, even if one Sol isn\'t enough.',
    'onboarding_hint_analytik' => 'No Analytics Lab — AP has nowhere to land. The colony stays scientifically stuck.',
    'onboarding_hint_hangar_path' => 'Build the Hangar first, then hire a Pilot — only then do missions and AP for exploration become properly available.',
    'onboarding_hint_invest_site' => 'Don\'t let AP go to waste — invest it in a running construction project. Each takes only a limited amount of AP per Sol; whatever is invested stays, and completion gets closer.',
    'onboarding_hint_explore' => 'Use AP for exploration (:ring2 AP per tile in ring 2, :ring3 AP in ring 3) — Regolith deposits and unknown signals wait beyond the zone.',
    'onboarding_hint_encounter' => 'Buildings in poor condition are more vulnerable to incidents — regular repairs pay off twice.',
    'onboarding_end_sol' => 'End the Sol — all sensible actions taken. The next Sol brings fresh action points and new developments.',
    'onboarding_hint_spend_ap_construction' => 'AP remaining — invest it in a building or pre-fund CC upgrade. AP expires at Sol end.',
    'onboarding_hint_spend_ap_research' => 'AP remaining — assign it to a knowledge field in the tech tree before the Sol ends.',
    'onboarding_hint_spend_ap_navigation' => 'AP remaining — there is still unexplored terrain out there. One tile costs 1 AP.',
    'onboarding_hint_spend_ap_economy' => 'AP remaining — check the Cantina to see if a guest has an offer ready.',
    'onboarding_hint_spend_ap_hangar' => 'There is not enough Regolith for new construction right now — the Hangar has find flights, missions and ship maintenance waiting.',
    'onboarding_hint_spend_ap_blocked' => 'Nothing sensible to build right now: Regolith is short or the AP cap of the buildings is reached for today. End the Sol — the Regolith keeps coming.',
    'nav_techtree_locked' => 'Analytics Lab required.',
    'nav_cantina_locked' => 'Cantina not built — Residential Habitat lv1 required.',

    // ── First-visit popups (Techtree / Nexus-DB / Cantina / Hangar) ──────────

    'first_visit_dismiss' => 'Understood',
    'first_visit_techtree_title' => 'Tech Tree',
    'first_visit_techtree_text' => 'Here you assign AP to a knowledge field. Knowledge grows Sol by Sol — whatever has been invested is retained, even if a Sol isn\'t enough to finish.',
    'first_visit_cantina_title' => 'Cantina',
    'first_visit_cantina_text' => 'The Cantina is the only place where colonists can forget, just for a moment, how thin the air is outside. Strangers from transit bring news, goods — and sometimes offers you really shouldn\'t miss. Drop by rarely and you\'ll notice the deal is already gone.',
    'first_visit_hangar_title' => 'Hangar',
    'first_visit_hangar_text' => 'Ships are stationed here and dispatched on missions. Each deployment costs Organics as crew provisions and takes several Sols — recall is possible at any time.',
    'first_visit_nexusdb_title' => 'Nexus DB',
    'first_visit_nexusdb_text' => 'Technical reference database, published by the Nexus — entries on buildings, knowledge fields, ships, and game mechanics. No action required.',

    // ── Onboarding — Nexus Briefing (INNN, event_type = 'onboarding.nexus_briefing') ──

    'onboarding_nexus_briefing_title' => 'Concession activated — Colony :colony',
    'onboarding_nexus_briefing_body' => 'Command Center and Harvester operational. Starting capital: 3,000 Cr — Nexus loan, repayment pending. Regolith reserve: 200 Rg. Colony viability under continuous assessment. Subsidies active until further notice.',

    // ── Onboarding — Inline trigger explanations ──────────────────────────────

    // Trigger 2 — Supply cap full (UI banner, 1 sentence)
    'onboarding_trigger_supply_full' => 'No free colonists left — no further buildings or research can be brought online or upgraded. Upgrade the Residential Habitat or the Command Center to create housing.',

    // Trigger 4 — AP limit (tooltip)
    'onboarding_trigger_ap_limit' => 'No more AP available this Sol.',

    // Trigger 5 — Harvester relocation (tooltip)
    'onboarding_trigger_harvester_move' => 'Relocation costs 1 AP per tile distance — only worth it if the target yields noticeably more. AP is scarce.',

    // ── Error messages ────────────────────────────────────────────────────────

    'error_path_building_gate' => 'Another path building requires Command Center Lv:level.',
    'error_building_ap_cap' => 'The construction crew can only put :cap AP into this building per Sol. It continues tomorrow — or at another building.',
    'ap_cap_chip' => 'today :spent/:cap',
    'ap_cap_tooltip' => 'This building takes no more work per Sol. An Engineer raises the limit with every rank.',
    'error_building_cc_gate' => 'This level needs Command Center Lv:level — upgrade the Command Center first.',
    'build_mode_hint_tile' => 'Choose a building for this tile.',
    'built_popup_sciencelab_title' => 'Analytics Lab complete',
    'built_popup_sciencelab_text' => 'The Techtree is open: assign AP to Knowledge so it matures Sol by Sol. You can also hire an Analyst in the Advisors screen.',
    'built_popup_hangar_title' => 'Hangar complete',
    'built_popup_hangar_text' => 'Your first drone is ready. Send it on exploration and salvage flights to bring in finds — each flight costs some Organics. Freighters unlock at hangar level 2.',
    'built_popup_bar_title' => 'Cantina complete',
    'built_popup_bar_text' => 'Visitors now drop by: encounters, trade offers and a Consul you can hire. Check in regularly.',
    'built_popup_uplink_title' => 'Uplink Station complete',
    'built_popup_uplink_text' => 'The station earns relay compensation in Credits every Sol and unlocks the Nexus import — Compounds, Regolith and Organics for Credits.',
    'build_locked_cc' => 'Needs Command Center Lv:level',
    'path_building_locked' => 'Another path building from CC Lv:level',
    'error_agrardom_required' => 'Build the Agrarian Dome first — mandatory prerequisite for all path buildings.',
    'error_tile_not_found' => 'Tile not found.',
    'error_ring_locked' => 'Ring not unlocked.',
    'error_already_explored' => 'Tile already explored.',
    'error_not_explored' => 'Tile must be explored first.',
    'error_no_signal' => 'No signal on this tile.',
    'error_already_scanned' => 'Tile already probed.',
    'error_no_nav_ap' => 'Not enough AP.',
    'error_no_nav_ap_2' => 'Not enough AP (:ap required).',
    'error_tile_not_buildable' => 'Only buildable terrain tiles allowed.',
    'error_not_scanned' => 'The tile must be scanned first.',
    'error_no_find' => 'There is nothing to salvage here.',
    'error_find_false' => 'False alarm — there is nothing here to salvage.',
    'error_invalid_ap' => 'Invalid AP amount for salvaging.',
    'error_salvage_by_flight' => 'With a hangar, finds are recovered by salvage flight.',
    'error_find_in_flight' => 'A ship is on its way to this find.',
    'error_tile_has_find' => 'This tile is blocked by a signal – scan and salvage it first.',
    'error_tile_outside_colony' => 'This tile is outside the colony zone.',
    'error_harvester_needs_regolith' => 'Harvester can only be placed on Regolith tiles.',
    'error_harvester_second_instance_cc_gate' => 'A second Harvester requires Command Center level 3.',
    'error_harvester_second_instance_locked' => 'A second Harvester needs a source — an offer from Orin in the cantina or a salvaged unit.',
    'harvester_move' => 'Relocate',
    'harvester_place_second' => 'Place Harvester',
    'harvester_move_mode_hint' => 'Click an explored Regolith tile outside the colony zone — shows a preview arrow with AP cost. Hold to relocate.',
    'harvester_move_no_targets' => 'No free explored Regolith tile available — explore new tiles first (AP).',
    'harvester_move_invalid_target' => 'Not a valid target — the Harvester needs a free, explored Regolith tile (highlighted in light blue).',
    'regolith_fallback_tile_hint' => 'Explored Regolith deposit — fallback target for Harvester relocation.',
    'network_error' => 'Network error — please try again.',
    'build_link_unavailable' => 'You cannot build this right now — check prerequisites and the instance limit in the techtree.',
    'error_harvester_in_transit' => 'The Harvester is still in transit — relocation possible after arrival.',
    'harvester_in_transit' => 'In transit — arrives next Sol.',
    'error_tile_occupied' => 'Tile already occupied.',
    'error_no_construction_ap' => 'Not enough AP.',
    'error_building_not_found' => 'Building not found.',
    'error_instance_not_found' => 'This building is not (or no longer) part of the colony.',
    'error_min_level' => 'This building cannot be levelled down any further. The Command Center always stays at level 1 or above.',
    'error_not_placed' => 'The building is not on the map — there is nothing to demolish.',
    'error_max_level_reached' => 'Maximum level already reached.',
    // Demolish button + confirmation dialog in the tile panel (A43, GDD techtree §11.5)
    'leveldown' => 'Demolish',
    'leveldown_cancel_construction' => 'Cancel construction',
    'leveldown_title' => 'Demolish one level',
    'leveldown_loading' => 'Calculating consequences …',
    'leveldown_new_level' => 'New level: :to (was :from)',
    'leveldown_ap_forfeited' => 'AP already invested are lost: :ap AP',
    'leveldown_freed_workplaces' => 'Colonists freed: :count — available for other buildings right away',
    'leveldown_capacity_loss' => 'Housing from the next Sol: :count places fewer',
    'leveldown_homeless' => 'Without shelter from the next Sol: :after colonists (without the teardown: :now). While housing is short, trust drops; once the deadline passes, they leave.',
    'leveldown_tile_released' => 'Level 0: the building is taken down and the tile cleared. Rebuilding means a new construction at full cost.',
    'leveldown_construction_cancelled' => 'The construction site is dissolved and the tile cleared. The construction costs are lost.',
    'leveldown_no_refund' => 'No refund: resources and AP put into it are lost. This cannot be undone.',
    'leveldown_confirm' => 'Demolish',
    'leveldown_confirm_cancel_construction' => 'Cancel construction',
    'leveldown_done' => 'Demolition complete.',
    'leveldown_cancelled_done' => 'Construction cancelled.',
    'error_supply_limit' => 'No free colonists for the next upgrade level — create housing first: upgrade the Residential Habitat or the Command Center.',
    'error_no_homeless_colonists' => 'Every colonist has a roof over their head — nobody has to leave.',
    'error_insufficient_resources' => 'Not enough resources to build.',
    'error_repair_no_regolith' => 'No Regolith for repair — repair the Harvester or mine Regolith.',

    // ── Build cost chips ──────────────────────────────────────────────────────

    'cost_regolith' => ':amount Rg',
    'cost_compounds' => ':amount Wk',
    'cost_label' => 'Cost',

    // ── Nexus import (Compounds for Credits, Uplink Station Lv1) ──────────────

    'nexus_import_title' => 'Nexus Import',
    'nexus_import_hint' => 'Buy Compounds directly from the Nexus — always available, fixed price.',
    'nexus_import_amount' => 'Amount (Compounds)',
    'nexus_import_price_each' => ':price Cr/unit',
    'nexus_import_price_sources' => 'Price advantage: :sources',
    'nexus_import_total' => 'Total: :total Cr',
    'nexus_import_confirm' => 'Import',
    'nexus_import_success' => 'Imported :amount Compounds (:cost Cr).',
    'nexus_import_uplink_required' => 'Uplink Station Lv1 required — it unlocks active Nexus requests.',
    'nexus_import_no_credits' => 'Not enough Credits for this import.',
    'nexus_import_error' => 'Import failed.',

    // Delayed Uplink direct import for Regolith/Organics (F5/A28, 2026-09-11) —
    // payment immediate, delivery after N Sols (depends on Uplink Station level).
    'nexus_import_delayed_title' => 'Nexus Import (delayed)',
    'nexus_import_delayed_hint' => 'Order Regolith or Organics from the Nexus — payment immediate, delivery after several Sols.',
    'nexus_import_delayed_resource' => 'Resource',
    'nexus_import_delayed_amount' => 'Amount',
    'nexus_import_delayed_confirm' => 'Order',
    'nexus_import_delayed_success' => ':amount units ordered — arriving in :ticks Sols (:cost Cr).',
    'nexus_import_delayed_error' => 'Order failed.',

    // ── Colonist Stipend (GDD §14) ───────────────────────────────────────────

    'stipend_button' => 'Colonist Stipend',
    'stipend_dialog_title' => 'Colonist Stipend',
    'stipend_dialog_hint' => 'Pay out Credits directly to the colonists — boosts Trust starting next Sol.',
    'stipend_tier_small' => 'Small',
    'stipend_tier_medium' => 'Medium',
    'stipend_tier_large' => 'Large',
    'stipend_success' => 'Stipend paid (:cost Cr) — takes effect next Sol.',
    'stipend_error' => 'Stipend failed.',
    'stipend_already_used' => 'A stipend has already been paid out this Sol.',
    'stipend_no_credits' => 'Not enough Credits for this stipend.',

    // ── Generic UI actions ────────────────────────────────────────────────────

    'close' => 'Close',

    // ── Travelling Merchant ───────────────────────────────────────────────────

    'merchant_in_system' => 'Merchant in system',
    'merchant_title' => 'Travelling Merchant',
    'merchant_until_sol' => 'Stays until Sol',
    'merchant_buy' => 'Buy',
    'merchant_sold' => 'Sold',
    'merchant_buy_success' => 'Purchase successful.',
    'merchant_buy_error' => 'Purchase failed.',

    // ── Market report (Consul, A13, GDD §12) ───────────────────────────────────
    'merchant_forecast_title' => 'Market Report',
    'merchant_forecast_tomorrow' => 'Corvan arrives tomorrow.',
    'merchant_forecast_in_sols' => 'Corvan arrives in :sols Sols.',
    'merchant_forecast_inventory' => 'Announced: :categories',
    'merchant_category_ap_package' => 'AP package',
    'merchant_category_information' => 'Information',
    'merchant_category_one_time' => 'One-time item',

    // ── Orin (corporate_rep) — Harvester second-instance Weg A (GDD §4c) ───────
    'corporate_contact_banner_hint' => 'An offer is waiting.',
    'corporate_contact_dialog_intro' => 'Orin has a Harvester module for sale — origin unclear, price due on the spot.',
    'corporate_contact_price_label' => 'Price',
    'error_corporate_contact_offer_unavailable' => 'This offer is no longer available.',
    'error_insufficient_credits' => 'Not enough Credits for this offer.',

    // ── Bar / Cantina ─────────────────────────────────────────────────────────

    'bar_title' => 'Cantina',
    'bar_no_building' => 'The Cantina has not been built yet.',
    'bar_no_offers' => 'No guests at the moment. Come back next Sol.',
    'bar_offer_heading' => 'Offers',
    'bar_offer_accept' => 'Accept',
    'bar_offer_give' => 'You give',
    'bar_offer_get' => 'You receive',
    'bar_offer_expires' => 'Expires in Sol',
    'bar_offer_not_found' => 'Offer not found.',
    'bar_offer_already_accepted' => 'Offer already accepted.',
    'bar_offer_expired' => 'Offer has expired.',
    'bar_offer_insufficient_resources' => 'Not enough resources.',
    'bar_offer_insufficient_ap' => 'Not enough AP.',
    'bar_offer_negotiate' => 'Negotiate',
    'bar_offer_no_consul' => 'No Consul available.',
    'bar_offer_already_negotiated' => 'This offer has already been negotiated.',
    'bar_offer_negotiate_failed' => ':name withdrew the offer — no deal, you keep :resource, :ap AP spent.',
    'bar_offer_not_negotiable' => 'This offer is a fixed price and cannot be negotiated.',
    'trade_source_consul' => 'Consul',
    'trade_source_trading_post' => 'Trading Post',
    'trade_source_trade_knowledge' => 'Trade knowledge',

    // ── Trade advantage in the offer dialog (A13 P2b, GDD §12 "What the offer dialog shows") ──
    'trade_line_consul' => 'Consul (rank :rank)',
    'trade_line_trading_post' => 'Trading Post (tier :tier)',
    'trade_line_trade_knowledge' => 'Trade knowledge (Lv :level)',
    'trade_short_consul' => 'Consul rank :rank',
    'trade_short_trading_post' => 'Trading Post',
    'trade_short_trade_knowledge' => 'Trade Lv:level',
    'trade_hint_no_consul' => 'No Consul available — no negotiating. A rank :rank Consul would bring +:percent %.',
    'trade_hint_trading_post_bar' => 'A Trading Post (from CC :cc) would bring +:percent %.',
    'trade_hint_trading_post_tier' => 'A Trading Post from tier :tier would bring −:percent %.',
    'trade_advantage_header' => 'Trade advantage :total (:sources)',
    'trade_price_advantage_line' => 'Price advantage :total (:sources)',
    'bar_offer_base_label' => 'Base offer (market value)',
    'bar_offer_base_label_credits' => 'Base offer',
    'bar_offer_base_credits' => ':amount :resource for :price Cr',
    'bar_offer_advantage_label' => 'Your trade advantage',
    'bar_offer_negotiation_label' => 'Negotiation bonus',
    'bar_offer_receive_label' => 'You receive',
    'bar_offer_plus_vs_base' => ':plus over base',
    'bar_offer_price_stays' => 'The price stays at :price Cr, the advantage raises the amount.',
    'bar_offer_accept_safe' => 'safe: :amount :resource (:plus)',
    'bar_offer_negotiate_win' => ':chance %: :amount :resource (:plus)',
    'bar_offer_negotiate_lose' => ':chance %: no deal, you keep :resource, the offer lapses, :ap AP spent.',
    'bar_offer_chance_breakdown' => 'Chance :total % = Consul rank :rank: :base % + Trade knowledge +:bonus %',
    'bar_offer_chance_base_only' => 'Chance :total % (Consul rank :rank)',
    'bar_offer_chance_total_only' => 'Chance :total %',
    'bar_offer_negotiation_sum' => 'Negotiation bonus: +:percent % (:base base + :advantage advantage + :bonus bonus = :total :resource)',
    'bar_offer_negotiated_label' => 'Negotiated:',
    'bar_offer_fixed_price_note' => 'Corvan buys :amount :resource for :credits Cr. Fixed price: trade advantage and negotiating do not apply to sale Credits.',
    'merchant_price_base_title' => 'Base price :price Cr',
    'bar_offer_reserve_floor' => 'The sale would drop the colony below its food reserve.',

    // ── Cantina encounter pool (GDD §12 channel 1, A35) ───────────────────────
    'bar_encounter_not_found' => 'Encounter not found.',
    'bar_encounter_already_accepted' => 'Encounter already accepted.',
    'bar_encounter_expired' => 'The encounter is over.',
    'bar_encounter_insufficient_resources' => 'Not enough resources for the stake.',
    'bar_encounter_insufficient_ap' => 'Not enough AP.',
    'bar_encounter_wager_heading' => 'Wager with Zara',
    'bar_encounter_wager_body' => 'Zara sizes up your stake. "All or nothing — what do you say?"',
    'bar_encounter_wager_won' => 'You won! Zara slides the Credits over, reluctantly.',
    'bar_encounter_wager_lost' => 'Lost. Zara grins and pockets the stake.',
    'bar_encounter_auction_heading' => 'Surplus buyout with Voss',
    'bar_encounter_auction_body' => 'Voss rummages through your surplus. "I\'ll take that off your hands — fair price, no haggling."',
    'bar_encounter_contract_heading' => 'Short-term contract',
    'bar_encounter_contract_body' => 'A passing buyer offers a limited deal — Credits for a few Sols, then he is gone.',
    'bar_encounter_accept' => 'Accept',

    // ── Cantina bartender Tomas (A40) ─────────────────────────────────────────
    // "Talk to Tomas" — free interaction, no AP cost check. The 4 tier variants
    // mirror config('game.bartender.ap_bonus_tiers') (0/5/15/30 interactions ->
    // 0/1/2/3 AP) and grow warmer the better Tomas knows the Director.
    'bartender_talk_button' => 'Talk to Tomas',
    'bartender_dialog_intro' => 'Tomas listens while polishing a glass — no word wasted with him, but every one counts.',
    'bartender_dialog_tier_0' => '"Tell me what\'s weighing on you." He nods curtly and listens — nothing more, he doesn\'t know you well enough yet.',
    'bartender_dialog_tier_1' => 'Tomas pauses. "I see you around." Without a word he slides a thought your way that actually helps.',
    'bartender_dialog_tier_2' => 'He sets the glass down and sits for a moment. "You\'re no stranger here anymore." What he passes on weighs more than usual.',
    'bartender_dialog_tier_3' => 'Tomas smiles briefly — rare enough that you notice. "You belong here." He gives you more than he has given most people.',

    // ── Character concerns (A41) ──────────────────────────────────────────────
    // One approach line + one success line per character; a failure line only
    // where config('game.bar.concern.success_chance.<slug>') < 1.0 (prospector
    // 0.50, founder 0.70, preacher 0.65, stranger 0.35 — the other 5
    // characters succeed guaranteed, see config/game.php).
    'bar_concern_not_found' => 'Concern not found.',
    'bar_concern_already_resolved' => 'This concern has already been dealt with.',
    'bar_concern_expired' => 'The matter is settled — the person has moved on.',
    'bar_concern_insufficient_resources' => 'Not enough resources for the stake.',
    'bar_concern_insufficient_ap' => 'Not enough AP.',
    'bar_concern_heading' => 'Concern',
    'bar_concern_resolve' => 'Help',

    'bar_concern_smuggler_intro' => 'Dax leans in close, eyes on the door. "I need a story nobody questions for a few days — cover, nothing more."',
    'bar_concern_smuggler_success' => 'Dax gives a curt nod. Days later a ship sits in the hangar, unannounced — his way of saying thanks.',

    'bar_concern_information_broker_intro' => 'Vesper lays her hands on the table. "I know of a weak spot in your next construction plan — the information has its price, but it\'s worth it."',
    'bar_concern_information_broker_success' => 'Vesper slides a data fragment over to you — the colony\'s next construction investment will come cheaper than it otherwise would.',

    'bar_concern_mechanic_intro' => 'Sarka wipes her hands on a rag. "I can\'t get through the maintenance list on my own anymore. A few hands that think along would do."',
    'bar_concern_mechanic_success' => 'It goes faster with two than Sarka expected. She growls a "good enough" — from her, almost a compliment — and passes on what she learned along the way.',

    'bar_concern_doctor_intro' => 'Maret rubs her eyes. "I\'m short on spare parts for the medical gear — nothing dramatic, but I can\'t go on without them."',
    'bar_concern_doctor_success' => 'With the parts you found, she can improvise. Maret thanks you curtly and gives you some of what\'s left in the infirmary in return.',

    'bar_concern_prospector_intro' => 'Fen weighs a sample in her hand. "I\'ve got a lead on a deposit — unconfirmed, maybe nothing. Whoever dares can follow it."',
    'bar_concern_prospector_success' => 'The lead was real. Fen grins, satisfied, as you come back with full hands — even if she acts as if she never doubted.',
    'bar_concern_prospector_failure' => 'The deposit turns out dead. Fen shrugs. "Happens. Next time."',

    'bar_concern_mercenary_intro' => 'Juno studies you for a long moment before she speaks. "Your security has gaps. I\'ll tell you where — for a fair return."',
    'bar_concern_mercenary_success' => 'Juno\'s assessment is as precise as expected. The agreed return changes hands without a word.',

    'bar_concern_founder_intro' => 'Aldra rummages in an old bag. "I still have blueprints from the first years — maybe some of it is still good today."',
    'bar_concern_founder_success' => 'The old plans turn out to be usable — much has barely changed since the first years. Aldra nods, satisfied, as if she had known.',
    'bar_concern_founder_failure' => 'The plans are too outdated to fit anymore. Aldra sighs. "Some things stay in the past."',

    'bar_concern_preacher_intro' => 'Sorel folds her hands. "A quarrel is brewing in the colony that nobody speaks of openly. Someone has to mediate before it grows."',
    'bar_concern_preacher_success' => 'The mediation works — not perfectly, but enough. Sorel nods, satisfied. "That was worth it."',
    'bar_concern_preacher_failure' => 'The quarrel cannot be settled — if anything, it gets worse. Sorel presses her lips together. "Some wounds need more than a conversation."',

    'bar_concern_stranger_intro' => 'The stranger slides an envelope across to you without looking up. No name, no explanation — just an expectation hanging in the room.',
    'bar_concern_stranger_success' => 'Whatever was expected, it is done. The stranger takes a sip from her glass and says nothing about it — only the payment lies ready.',
    'bar_concern_stranger_failure' => 'Something goes wrong — what exactly, remains unclear. The stranger vanishes without leaving an explanation. The stake is lost.',

    // ── Deva & Lenn — tactical information (A42) ──────────────────────────────
    // All 8 outcomes (4 per character) already final.
    'bar_information_not_found' => 'Conversation not found.',
    'bar_information_already_resolved' => 'The conversation has already ended.',
    'bar_information_expired' => 'The opportunity has passed.',
    'bar_information_heading' => 'Tactical information',
    'bar_information_resolve' => 'Ask further',
    'bar_information_veteran_drill_buffer' => 'Deva shows you a move that, when it counts, costs time — the other side\'s.',
    'bar_information_veteran_knowledge_boost' => 'Deva thinks for a moment and passes on a targeted tip.',
    'bar_information_veteran_narrative_1' => 'Deva mentions an old deployment in passing — nothing that helps you here.',
    'bar_information_veteran_narrative_2' => 'Deva gives you a curt nod and stays silent for the rest of the conversation.',
    'bar_information_ai_researcher_nav_discount' => 'Lenn types something into a datapad and hands it to you — the next expedition is prepared.',
    'bar_information_ai_researcher_knowledge_boost' => 'Lenn mutters something about mapping algorithms and pushes the notes across to you.',
    'bar_information_ai_researcher_narrative_1' => 'Lenn gets lost in a theory you can\'t quite follow.',
    'bar_information_ai_researcher_narrative_2' => 'Lenn seems distracted and quickly changes the subject.',

    // ── Character codex (A42) ─────────────────────────────────────────────────
    // 5 lore snippets for each of the 15 config('characters') figures (key
    // pattern codex_entry_<slug>_<1-5>, entry_number from CharacterCodexService).
    // They deepen/extend the character sheets (docs/characters/*.md), they do
    // not copy them. Kept short — a collector's reference, not short stories.
    'codex_heading' => 'Character Codex',

    'codex_entry_bartender_1' => 'Before the colony, Tomas served at a supply station in the Outer Reach — what drove him away from there he has never said.',
    'codex_entry_bartender_2' => 'The three bars and the circle on his forearm mark a station that officially no longer exists.',
    'codex_entry_bartender_3' => 'Tomas keeps no register of names — and needs none. He remembers every face, every order, every grudge.',
    'codex_entry_bartender_4' => 'Ask him where he comes from and he changes the subject with a fresh round.',
    'codex_entry_bartender_5' => 'Some say Tomas took over the Cantina because it was the last place where nobody asked him his name.',

    'codex_entry_smuggler_1' => 'Dax knows depots that appear in no Nexus register — and patrols that look away exactly when he needs them to.',
    'codex_entry_smuggler_2' => 'He never sits with his back to the door and never leaves the Cantina by the main entrance — both out of habit, not chance.',
    'codex_entry_smuggler_3' => 'Twenty years in the trade have left him no loyalties, only prices.',
    'codex_entry_smuggler_4' => 'What he carries he rarely asks — as long as the payment is right.',
    'codex_entry_smuggler_5' => 'There are rumours of a cargo he once failed to deliver. He denies it ever existed.',

    'codex_entry_information_broker_1' => 'Vesper names no origin — any answer would only be one more piece of information she could sell.',
    'codex_entry_information_broker_2' => 'The small data plug behind her ear is more than decoration — some claim she records every conversation she has.',
    'codex_entry_information_broker_3' => 'Her contacts reportedly reach into structures close to the Nexus — she has never proven it, nor denied it.',
    'codex_entry_information_broker_4' => 'She always turns up exactly when she knows something that is about to become relevant — that is no coincidence.',
    'codex_entry_information_broker_5' => 'A conversation with Vesper always feels like a trade, even if you never notice what you gave away yourself.',

    'codex_entry_mechanic_1' => 'Sarka keeps three quarters of the colony\'s distribution systems running single-handed — officially, her sleep is not among them.',
    'codex_entry_mechanic_2' => 'She cannot enter a room without checking hinges and appraising valves — the Cantina is no exception.',
    'codex_entry_mechanic_3' => 'Nobody could ever pay for her overtime — she stopped counting it long ago.',
    'codex_entry_mechanic_4' => 'Her assessments are unvarnished and therefore reliable — Sarka never says more than necessary, but never less.',
    'codex_entry_mechanic_5' => 'Ask her when she last had a day off and all you get is a tired laugh.',

    'codex_entry_corporate_rep_1' => 'Nobody has ever found out who Orin actually works for — ask him and he names a different company every time.',
    'codex_entry_corporate_rep_2' => 'He turns up when supply contracts, mining rights or regulatory questions need settling — never without reason.',
    'codex_entry_corporate_rep_3' => 'His datapad never leaves him — what is stored on it, he shows to no one.',
    'codex_entry_corporate_rep_4' => 'He is polite in a way that allows no closeness — friendliness as a tool, not as an offer.',
    'codex_entry_corporate_rep_5' => 'What he really wants rarely matches what he says — everyone in the Cantina knows that by now.',

    'codex_entry_founder_1' => 'Aldra was there when the first structural modules were set — long before the Nexus had a say.',
    'codex_entry_founder_2' => 'She still calls every place in the colony by its original name — names nobody else remembers.',
    'codex_entry_founder_3' => 'Her plans for the colony envisioned a settlement that sustains itself — not a supply station for corporate interests. Nothing came of it.',
    'codex_entry_founder_4' => 'She stayed because she belongs nowhere else — not because she agrees with what became of her colony.',
    'codex_entry_founder_5' => 'She watches the Director with a mix of hope and scepticism — as if she had worn that look many times before.',

    'codex_entry_gambler_1' => 'Zara bets on everything — the lifespan of a building, the next delivery date, the odds of rain tomorrow morning.',
    'codex_entry_gambler_2' => 'She doesn\'t cheat — at least not badly — and has a reputation for always settling her debts.',
    'codex_entry_gambler_3' => 'For years she has had no fixed quarters and apparently no need to afford any.',
    'codex_entry_gambler_4' => 'Where her starting capital came from nobody remembers anymore — least of all she, or so she claims.',
    'codex_entry_gambler_5' => 'The smooth metal ring on her finger is forever being turned and taken off — nobody has ever asked her why.',

    'codex_entry_ai_researcher_1' => 'Lenn used to work for a research facility close to the Nexus — until the approved parameters were left too far behind.',
    'codex_entry_ai_researcher_2' => 'What Lenn is developing stays unsaid — only that it needs computing power the colony barely monitors.',
    'codex_entry_ai_researcher_3' => 'The earpiece with the little LED is almost never off — whether a program runs there or something else, remains open.',
    'codex_entry_ai_researcher_4' => 'Sometimes Lenn holds half a conversation with someone who isn\'t there — nobody asks why anymore.',
    'codex_entry_ai_researcher_5' => 'Now and then components are needed that appear in no regular catalogue — where else they come from, nobody knows.',

    'codex_entry_veteran_1' => 'Deva served thirty years, last as a company commander on security missions at the edge of uncharted sectors.',
    'codex_entry_veteran_2' => 'She is not in the colony for military reasons — she was looking for someone, never found them, and stayed.',
    'codex_entry_veteran_3' => 'Security protocols and tactical assessments she shares only when she deems the occasion worthy.',
    'codex_entry_veteran_4' => 'She never explains the old scar from temple to chin — ask, and you get no answer.',
    'codex_entry_veteran_5' => 'She never drinks more than two glasses — and watches everyone who drinks more than she does.',

    'codex_entry_stranger_1' => 'Nobody knows when this person first sat in the Cantina — at some point she was simply there.',
    'codex_entry_stranger_2' => 'She barely touches her glass but sits over it for hours, as if time meant something different to her.',
    'codex_entry_stranger_3' => 'Tomas serves her without asking — whatever the reason, only he knows it.',
    'codex_entry_stranger_4' => 'Her questions seem harmless in the moment — only hours later do you notice how strange they really were.',
    'codex_entry_stranger_5' => 'Where she comes from, who she travels with, why she is here — nobody has ever gotten an answer.',

    'codex_entry_doctor_1' => 'Maret\'s shift officially ends at 22:00 — in practice, it never does.',
    'codex_entry_doctor_2' => 'She is the colony\'s only trained physician — a fact that gives her no peace.',
    'codex_entry_doctor_3' => 'In the Cantina she seeks quiet, but mostly finds patients who know where she sits.',
    'codex_entry_doctor_4' => 'She never promises more than she can deliver — even if that means promising little.',
    'codex_entry_doctor_5' => 'She has no strength left for grand gestures — but for the one person in front of her she still finds something.',

    'codex_entry_preacher_1' => 'Sorel quotes no scripture — she argues as if explaining something self-evident that others simply haven\'t seen yet.',
    'codex_entry_preacher_2' => 'She holds a worldview, not a religion — and never lets that distinction go unmentioned.',
    'codex_entry_preacher_3' => 'Her influence reaches into questions of how resources are distributed and how the colony treats strangers.',
    'codex_entry_preacher_4' => 'She changes the hand-printed cloth around her neck regularly — as if each one had a meaning of its own.',
    'codex_entry_preacher_5' => 'Whether she is right about what she preaches is a question nobody in the Cantina dares to ask aloud.',

    'codex_entry_prospector_1' => 'Fen has roamed between colonies and outposts for three decades — always looking for deposits that official survey teams overlooked.',
    'codex_entry_prospector_2' => 'She cannot help appraising the material value of everything around her — even things nobody wants to sell.',
    'codex_entry_prospector_3' => 'She claims she keeps coming back for Tomas\' beer — that the colony makes a good base she admits only reluctantly.',
    'codex_entry_prospector_4' => 'She delivers information like merchandise — sober, no sales pitch, no exaggeration.',
    'codex_entry_prospector_5' => 'Some of her leads turn out dead — she takes that with the same calm as a hit.',

    'codex_entry_mercenary_1' => 'Juno has worked for various clients — none of them with a name that appears in a public registry.',
    'codex_entry_mercenary_2' => 'Two years ago she stopped taking active contracts. Why, she does not say.',
    'codex_entry_mercenary_3' => 'She came to the colony because she knew nobody here — originally, that was the only reason.',
    'codex_entry_mercenary_4' => 'She never speaks about specific deployments — only about principles, costs and consequences.',
    'codex_entry_mercenary_5' => 'The way she reads a room says more about her than any gear she no longer carries.',

    'codex_entry_scrap_dealer_1' => 'Voss buys, collects and sells whatever others throw away or no longer need — his stock is chaos, his inventory lives only in his head.',
    'codex_entry_scrap_dealer_2' => 'He always opens price negotiations with a wildly inflated number — and then makes a fair offer anyway.',
    'codex_entry_scrap_dealer_3' => 'He occasionally deals in things whose origin he would rather not describe more closely.',
    'codex_entry_scrap_dealer_4' => 'Silence strikes him as a waste of time — his flow of talk practically never dries up.',
    'codex_entry_scrap_dealer_5' => 'Despite his reputation as a poor negotiator, nobody has ever been cheated by him.',

    // ── Cantina character assignment (A36) ────────────────────────────────────
    // bar_trade — personalised line in the guest trade-offer dialog, shown in
    // addition to the generic give/receive grid, no new mechanism.
    'bar_trade_flavor_prospector' => 'Fen weighs a chunk of Regolith in her hand as if already appraising it.',
    'bar_trade_flavor_doctor' => 'Maret wearily pushes a small bundle of Organics across to you — "Take it before it goes bad."',
    'bar_trade_flavor_mercenary' => 'Juno studies you for a moment, then silently lays something useful on the table.',
    'bar_trade_flavor_smuggler' => 'Dax shifts closer, back to the wall, and mutters something about cargo nobody will miss.',
    'bar_trade_flavor_information_broker' => 'Vesper smiles thinly, as if she had known you would ask — and names her price.',
    'bar_trade_flavor_mechanic' => 'Sarka raps a spare part to test it before sliding it over: "Still works. Mostly."',

    // story_hook — pure flavour moment, no resource/Credit effect.
    'story_encounter_preacher' => 'Sorel speaks calmly of what the colony owes its settlers, smoothing her cloth, lost in thought.',
    'story_encounter_founder' => 'Aldra points to a corner of the Cantina and calls it by its old name, one nobody else remembers.',
    'story_encounter_stranger' => 'The stranger asks an offhand question that only strikes you as odd later, then turns back to her untouched glass.',
    'story_encounter_close' => 'Close',

    // ── Sol trigger (navbar button) ───────────────────────────────────────────

    'next_sol_button' => 'End Sol',

    // ── Nexus debt display ────────────────────────────────────────────────────

    'nexus_debt_label' => 'Nexus Credit',
    'nexus_debt_format' => ':amount / 12,000 Cr',

    // ── Hangar screen ─────────────────────────────────────────────────────────

    'hangar_title' => 'Hangar',
    'hangar_slot_count' => 'Bays',
    'hangar_apron' => 'Apron',
    'hangar_bay' => 'Bay :n',
    'hangar_bay_locked' => 'from level :level',
    'hangar_apron_reserved' => 'Reserved for the gift drone',
    'hangar_error_bay_reserved' => 'The apron is reserved for the gift drone.',
    'hangar_error_bay_closed' => 'This bay is still closed — upgrade the hangar first.',
    'error_building_already_placed' => 'This building already stands — there is only one of it.',
    'hangar_empty' => 'Empty',
    'hangar_build_ship' => 'Build ship',
    'hangar_dispatch' => 'Dispatch',
    'hangar_dispatch_no_nav_ap' => 'Not enough AP for this mission.',
    'hangar_dispatch_no_organika' => 'Not enough Organics for this mission\'s crew provisions.',
    'hangar_ship_inactive' => 'Inactive: this ship needs a level :level hangar. Upgrade the hangar again to start missions.',
    'hangar_request_level_too_low' => 'This ship type requires a higher hangar upgrade.',
    'hangar_request_min_level' => 'Requires hangar level :level',
    'hangar_recall' => 'Recall',
    'hangar_repair' => 'Repair',
    'hangar_destination' => 'Destination',
    'hangar_sol_distance' => 'Sol distance',
    'hangar_in_transit' => 'In transit',
    'hangar_in_construction' => 'Under construction',
    'hangar_pilot_ready' => 'Pilot ready',
    'hangar_status' => 'Status',
    'hangar_already_commissioned' => 'Already active',
    'hangar_none_built' => 'No Hangar built. Build a Hangar in the colony to station ships.',
    'nav_hangar' => 'Hangar',
    'nav_hangar_locked' => 'No Hangar built.',

    // ── Hangar — Nexus request (ship acquisition redesign) ───────────────────

    'hangar_nexus_request' => 'Request from Nexus',
    'hangar_nexus_request_title' => 'Request ship',
    'hangar_nexus_request_submit' => 'Request',
    'hangar_ship_type' => 'Ship type',
    'hangar_payment_method' => 'Payment',
    'hangar_standard_purchase' => 'Standard purchase',
    'hangar_nexus_credit' => 'Nexus credit',
    'hangar_nexus_credit_hint' => '0 Cr now — Nexus debt increases',
    'hangar_consul_ap_title' => 'Consul Negotiation',
    'hangar_consul_required' => 'An available Consul is required for a negotiation.',
    'hangar_consul_ap_label' => 'Invest AP (saves :amount Cr)',
    'hangar_consul_ap_hint' => ':ap AP → saves :saved Cr',
    'hangar_delivery_pending' => 'Delivery pending',
    'hangar_arrival' => 'Arrival: Sol :tick',
    'hangar_pending_section' => 'Unassigned',
    'hangar_request_ordered' => 'Delivery ordered – see “Unassigned”.',
    'hangar_pending_expires' => 'Expires: Sol :tick',
    'hangar_assign_hangar' => 'Assign hangar',
    'hangar_assign_select' => 'Select bay…',
    'hangar_ship_drone' => 'Drone',
    'hangar_ship_freighter' => 'Freighter',
    'hangar_ship_corvette' => 'Corvette',

    // ── Sol Report (transition screen) ───────────────────────────────────────

    // Group headings
    'sol_report_group_decay' => 'The colony ages',
    'sol_report_group_events' => 'Events',
    'sol_report_group_production' => 'Production & Supplies',
    'sol_report_group_colony' => 'Colony & Personnel',
    'sol_report_group_run' => 'The Run',

    // Decay
    'sol_report_level_to' => 'dropped to level :level',
    'sol_report_level_lost' => 'Level lost',
    'sol_report_ship_destroyed' => 'destroyed by decay',
    'sol_report_wear_label' => 'Installations',
    'sol_report_wear_detail' => 'holding — minor wear',

    // Events
    'sol_report_event_merchant' => 'Travelling Merchant in system',
    'sol_report_passive_credits' => 'Nexus Subsidy & Relay Fee',
    'sol_report_subsidy' => 'Nexus subsidy',
    'sol_report_relay' => 'Relay fee (Uplink Station)',
    'sol_report_upkeep' => 'Advisor upkeep',
    'sol_report_advisor_hired' => 'Advisor hired',
    'sol_report_advisor_hired_detail' => ':name — -:cost Cr',
    'sol_report_stipend' => 'Colonist Stipend',
    'sol_report_stipend_detail' => '-:cost Cr — +:trust Trust',
    'sol_report_event_storm' => 'Storm over the colony',
    'sol_report_storm_detail' => 'The maintenance condition of the installations decided it: :abgewehrt withstood, :beschaedigt damaged, :kritisch critical.',
    'sol_report_event_instability' => 'Geological instability',
    'sol_report_instability_detail' => 'After :sols Sols without a change of location: Harvester yield disrupted.',
    'sol_report_event_plague' => 'Outbreak of disease',
    'sol_report_plague_detail_hunger' => 'Food shortage (:streak Sols without supplies) triggered the outbreak — action points temporarily reduced.',
    'sol_report_plague_detail_trust' => 'Critical Trust (:trust) triggered the outbreak — action points temporarily reduced.',
    'sol_report_event_colonists_left' => 'Colonists departed',
    'sol_report_colonists_left_detail' => ':count colonists without shelter have packed up and left the settlement. Their workplaces stay empty until new housing brings them back.',
    'sol_report_event_colonists_dismissed' => 'Colonists sent away',
    'sol_report_colonists_dismissed_detail' => 'On the director\'s orders, :count colonists without shelter have left the settlement. New housing will bring them back.',
    'sol_report_event_colonists_returned' => 'Colonists returned',
    'sol_report_colonists_returned_detail' => ':count colonists have moved into the new housing — their workplaces are staffed again.',

    // Production
    'sol_report_no_production' => 'The extraction plants are idle — no Regolith, no progress. Check industrial buildings.',
    'sol_report_supply_used' => ':cap colonists, :used of them employed',
    'sol_report_food' => 'Provisions',
    'sol_report_food_ok' => ':amount Organics distributed to the kitchens — the colony is fed',
    'sol_report_overcap' => 'Shelter',
    'sol_report_overcap_homeless' => ':homeless colonists are sleeping without shelter — Trust −:malus. Without new housing they will leave in :sols Sol.',
    'sol_report_staffing' => 'Personnel',
    'sol_report_understaffed' => ':departed workplaces unfilled — raw-material production at :pct %. Construction and upgrades stay locked until new housing brings the departed back.',
    'sol_report_food_shortage' => 'Supplies exhausted — colonists are going hungry. Trust is falling. Build or repair the Agrarian Dome.',

    // Colony & Personnel
    'sol_report_advisor' => 'Advisor',
    'sol_report_advisor_promoted' => 'promoted to rank :rank',

    // Run
    'sol_report_phase_reached' => 'Phase :phase reached — new options available',
    'sol_report_objectives' => 'Objectives met: :done / :total',
    'sol_report_sol_counter' => 'Sol :sol of :limit',

    // Finale (run end)
    'sol_report_finale_win_title' => 'Mission accomplished',
    'sol_report_finale_win_body' => 'The colony stands. Against decay, scarcity, and everything the planet threw at it — it stands.',
    'sol_report_finale_lose_title' => 'Mission failed',
    'sol_report_finale_lose_body' => 'This colony did not survive. What remains will be noted and handed over — and someday, those who have learned enough will start again.',

    // UI controls
    'sol_report_title' => 'Sol :sol complete',
    'sol_report_continue' => 'Continue to Sol :sol',

    // Screen 2 — Phase progress
    'sol_report_screen2_title' => 'Phase Progress',
    'sol_report_phase1_title' => 'Phase 1 — Foundation',
    'sol_report_phase2_title' => 'Phase 2 — Nexus Directives',
    'sol_report_phase1_cc_level' => 'Command Center at Level 2',
    'sol_report_phase1_harvester' => 'Harvester built',
    'sol_report_phase1_housing' => 'Housing complex built',
    'sol_report_phase1_agrardom' => 'Agrardom built',
    'sol_report_phase1_uplink' => 'Uplink station built',
    'sol_report_phase1_path_building' => 'One path building built (Sciencelab, Hangar or Cantina)',
    'sol_report_phase1_advisors' => '2 active advisors',
    'sol_report_phase2_objective_hidden' => 'Directive not yet known — unlocked during play',
    'sol_report_phase2_objective_done' => 'Fulfilled',
    // TODO content-writer: Phasenübergang-Erfolgsbildschirm
    'sol_report_phase_success_title' => 'Phase 1 complete',
    'sol_report_phase_success_body' => 'The foundation stands. The Phase 2 Nexus directives await.',
    'sol_report_phase_success_achieved' => 'Achieved',
    'sol_report_next_screen' => 'Continue',

    // Screen 3 — SOL N starts
    'sol_report_screen3_starts' => 'starts',
    'sol_report_screen3_begin' => 'Continue mission',

    'sol_report_finale_win_cta' => 'Complete run',
    'sol_report_finale_lose_cta' => 'End run',

    // Encounter hazard banner (GDD §9) — a separate, more urgent line above the
    // normal hint suggestion, owner playtest finding 2026-08-31: storm warnings
    // etc. were previously only visible in the log, easy to miss.
    //
    // Since 2026-09-03 the storm has colony-wide scope (hits all zone buildings
    // instead of a single target) — the warning therefore names no building,
    // but points to maintenance condition as the deciding factor.
    'encounter_notice_storm_warning' => 'Storm warning: a storm is gathering over the colony. Well-kept installations will hold, neglected ones will not.',
    'encounter_notice_storm_resolved' => 'Storm over: the maintenance condition of the installations decided it — :abgewehrt withstood, :beschaedigt damaged, :kritisch critical.',

    // Old: single-building variants, no longer reachable since the colony-wide
    // storm scope (the matching colony_log events are no longer written, see
    // GameTick::resolveStormWarning()). Kept in case cleanup of the dead
    // EncounterNoticeService::EVENT_KEYS entries comes up.
    'encounter_notice_storm_abgewehrt' => 'Storm at :building successfully withstood — no damage.',
    'encounter_notice_storm_beschaedigt' => 'Storm damage at :building.',
    'encounter_notice_storm_kritisch' => 'Critical storm damage at :building — level dropped.',

    // ── UI: Tomas dialog, concern/information dialog chrome, codex nav (A40/A41/A42) ──
    'user_nav_codex' => 'Character Codex',
    'bartender_knowledge_label' => 'Which knowledge field does the tip go into?',
    'bartender_error_cooldown' => 'Tomas has already given you advice for today — try again next Sol.',
    'bartender_error_generic' => 'The conversation did not happen.',
    'bar_concern_knowledge_label' => 'Which knowledge field should the help go into?',
    'bar_information_knowledge_label' => 'Which knowledge field should benefit?',
    'codex_page_intro' => 'Every figure in the Cantina leaves traces — the more often you meet, the more you learn about them. Unlocked entries stay with you beyond the end of a run.',
    'codex_locked_entry' => '???',
    'codex_no_entries_yet' => 'No entries unlocked yet.',

    // ── Legacy EN-only keys (kept for template compatibility) ─────────────────

    'condition' => 'Condition',
    'ap_invested' => 'AP invested',

    // T25 error contract (hangar/merchant codes)
    'hangar_error_ship_not_orderable' => 'The Nexus does not deliver this ship type.',
    'hangar_error_invalid_consul_ap' => 'Invalid AP amount for the negotiation.',
    'hangar_error_insufficient_ap' => 'Not enough AP: :requested needed, :available available.',
    'hangar_error_nexus_credit_cc_level' => 'Nexus credit requires Command Center level :level (currently :current).',
    'hangar_error_insufficient_credits' => 'Not enough Credits: :need needed, :have available.',
    'hangar_error_ship_not_pending' => 'This ship is not waiting for a hangar bay.',
    'hangar_error_hangar_occupied' => 'This hangar already holds a ship.',
    'hangar_error_unknown_mission' => 'Unknown mission.',
    'hangar_error_no_ship_in_hangar' => 'There is no ship in this hangar.',
    'hangar_error_ship_not_docked' => 'The ship is not ready for launch.',
    'hangar_error_no_active_mission' => 'No mission is running for this hangar.',
    'hangar_error_no_docked_ship' => 'No ship is docked in this hangar.',
    'hangar_error_ship_full_status' => 'The ship is already fully repaired.',
    'hangar_error_action_failed' => 'The action could not be carried out.',
    'merchant_error_item_not_found' => 'This offer does not exist.',
    'merchant_error_item_sold' => 'This offer is already sold.',
    'merchant_error_visit_over' => 'The merchant has already left.',

    // ── T30 find card ──────────────────────────────────────────────────────────
    'find_label' => 'Find',
    'find_value' => ':rg Rg',
    'find_salvage' => 'Salvage',
    'find_false_notice' => 'False alarm – no yield',
    'find_reason_unscanned' => 'Unknown signal. Scan it first.',
    'find_reason_false' => 'False alarm. The tile is free.',
    'find_reason_self' => 'Without a hangar the colony salvages on foot.',
    'find_reason_in_flight' => 'In flight. Back on Sol :sol.',
    'find_reason_flight' => 'Find :total Rg · :ap AP · :organika Or · back Sol :sol',
    'find_reason_ship_busy' => 'Waiting: :ship back on Sol :sol.',
    'find_reason_needs_level' => 'Locked: needs a freighter – hangar level :level.',
    'find_reason_needs_freighter' => 'Locked: needs a freighter – order one from the Nexus.',
    'find_reason_ship_inactive' => 'Locked: :ship inactive – needs hangar level :level.',
    'find_reason_no_ship' => 'Waiting: no ship that can carry the find.',
    'find_salvaged' => ':rg Rg salvaged',
    'find_header' => 'Finds: :signals signals, :scanned scanned, :open Rg open',
    'find_help' => 'There are :total signals in this run; some are false alarms. A deep scan shows whether and how much there is to salvage. Without a hangar the colony salvages itself: one click, a few AP, the Regolith is in the stores at once. Once the hangar stands, ships fetch the finds: hardly any AP, but flight time and Organics. Large finds are too heavy for the drone and wait for a freighter.',
    'find_flight' => 'Salvage flight',
    'hangar_build_notice' => 'Afterwards only ships fetch the finds: a flight costs 1 AP and some Organics, the Regolith arrives after the flight time. Large finds need hangar level 2 and a freighter.',
    'onboarding_hint_find_flight' => 'From now on ships fetch the finds: hardly any AP, but flight time and Organics. Large finds need a freighter.',
    'find_small' => 'Small find: scattered debris',
    'find_medium' => 'Medium find: broken-open wreck',
    'find_large' => 'Large find: freighter wreck',
    'find_false' => 'False alarm',
];
