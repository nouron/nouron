<?php

return [

    // ── Tile actions ──────────────────────────────────────────────────────────

    'explore' => 'Explore',
    'deep_scan' => 'Probe',
    'invest_ap' => 'Invest AP',

    // ── Building actions ──────────────────────────────────────────────────────

    'build' => 'Build',
    'cancel' => 'Cancel',
    'select_tile_hint' => 'Click a tile to place',

    // ── Sidebar labels ────────────────────────────────────────────────────────

    'tile_info' => 'Tile Info',
    'click_tile_hint' => 'Click a hex tile to view details.',
    'building_section' => 'Building',
    'construction_site' => 'Construction Site',
    'max_level' => 'Max. Level',
    'condition' => 'Condition',
    'ap_invested' => 'AP invested',
    'resource_regolith' => 'Regolith',

    // ── Status chips ──────────────────────────────────────────────────────────

    'chip_locked' => 'Locked',
    'chip_unexplored' => 'Unexplored',
    'chip_explored' => 'Explored',
    'chip_scanned' => 'Probed',
    'chip_signal' => 'Signal',

    // ── Build mode ────────────────────────────────────────────────────────────

    'build_mode_title' => 'Build Structure',
    'build_mode_hint' => 'Select a building, then click a terrain tile.',
    'no_buildings' => 'No buildings available.',

    // ── Error messages ────────────────────────────────────────────────────────

    'error_tile_not_found' => 'Tile not found.',
    'error_ring_locked' => 'Ring not unlocked.',
    'error_already_explored' => 'Tile already explored.',
    'error_not_explored' => 'Tile must be explored first.',
    'error_no_signal' => 'No signal on this tile.',
    'error_already_scanned' => 'Tile already probed.',
    'error_no_nav_ap' => 'Not enough navigation AP.',
    'error_no_nav_ap_2' => 'Not enough navigation AP (2 required).',
    'error_tile_not_buildable' => 'Only buildable terrain tiles allowed.',
    'error_tile_outside_colony' => 'This tile is outside the colony zone.',
    'error_harvester_needs_regolith' => 'Harvester can only be placed on regolith tiles.',
    'harvester_move' => 'Relocate',
    'harvester_move_mode_hint' => 'Select an explored regolith tile outside the colony zone — 1 construction AP per hex distance.',
    'harvester_move_no_targets' => 'No free explored regolith tile available — explore new tiles first (Nav AP).',
    'error_harvester_in_transit' => 'The harvester is still in transit — relocation possible after arrival.',
    'harvester_in_transit' => 'In transit — arrives next Sol.',
    'error_tile_occupied' => 'Tile already occupied.',
    'error_no_construction_ap' => 'Not enough construction AP.',
    'error_building_not_found' => 'Building not found.',
    'error_insufficient_resources' => 'Not enough resources to build.',
    'error_repair_no_regolith' => 'No Regolith for repair — repair the Harvester or mine Regolith.',

    // ── Build cost chips ──────────────────────────────────────────────────────

    'cost_regolith' => ':amount Rg',
    'cost_compounds' => ':amount Wk',
    'cost_label' => 'Cost',

    // ── Nexus import (compounds for credits, Uplink Station Lv1) ──────────────

    'nexus_import_title' => 'Nexus Import',
    'nexus_import_hint' => 'Buy compounds directly from the Nexus — always available, fixed price.',
    'nexus_import_amount' => 'Amount (compounds)',
    'nexus_import_price_each' => ':price Cr/unit',
    'nexus_import_total' => 'Total: :total Cr',
    'nexus_import_confirm' => 'Import',
    'nexus_import_success' => 'Imported :amount compounds (:cost Cr).',
    'nexus_import_uplink_required' => 'Uplink Station Lv1 required — it unlocks active Nexus requests.',
    'nexus_import_no_credits' => 'Not enough credits for this import.',
    'nexus_import_error' => 'Import failed.',

];
