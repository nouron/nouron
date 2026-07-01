<?php

return [

    // ── Order type labels ─────────────────────────────────────────────────────

    'order_move' => 'Move',
    'order_hold' => 'Hold',
    'order_trade' => 'Trade',
    'order_convoy' => 'Convoy (Escort)',
    'order_defend' => 'Defend',
    'order_join' => 'Join',
    'order_attack' => 'Engage',
    'order_devide' => 'Split',

    // ── Order group labels ────────────────────────────────────────────────────

    'order_group_movement' => 'Movement',
    'order_group_cooperation' => 'Cooperation',
    'order_group_combat' => 'Combat',

    // ── Order field labels ────────────────────────────────────────────────────

    'field_dest_x' => 'Destination X',
    'field_dest_y' => 'Destination Y',
    'field_colony_id' => 'Colony ID',
    'field_resource_id' => 'Resource ID',
    'field_amount' => 'Amount',
    'field_direction' => 'Direction',
    'field_target_fleet' => 'Target Fleet ID',

    'direction_buy' => 'Buy (Colony → Fleet)',
    'direction_sell' => 'Sell (Fleet → Colony)',

    // ── Order descriptions (shown below form) ─────────────────────────────────

    'desc_move' => 'Fleet moves to the specified coordinates.',
    'desc_hold' => 'Fleet holds its current position for one Sol.',
    'desc_trade' => 'Fleet loads or unloads resources at a colony.',
    'desc_convoy' => 'Fleet escorts the target fleet to its destination.',
    'desc_defend' => 'Fleet moves to the target fleet\'s position to defend it.',
    'desc_join' => 'Fleet joins the target fleet and merges with it.',
    'desc_attack' => 'Fleet engages the target fleet.',

    // ── Commander assignment ──────────────────────────────────────────────────

    'commander_assigned' => 'Commander assigned to fleet.',
    'commander_removed' => 'Commander relieved from fleet.',
    'commander_no_navigator' => 'No navigator available on the colony.',
    'commander_navigator_unavailable' => 'Navigator is currently unavailable.',
    'commander_already_assigned' => 'This fleet already has a commander.',

    // ── General UI ────────────────────────────────────────────────────────────

    'order_form_title' => 'Issue order',
    'order_submit' => 'Issue order',
    'pending_orders' => 'Pending orders',
    'no_pending_orders' => 'No pending orders.',
    'fleet_index_title' => 'Fleets',
    'fleet_config_title' => 'Fleet Configuration',
    'fleet_create' => 'New fleet',
    'fleet_name' => 'Fleet name',
    'fleet_delete_confirm' => 'Really disband this fleet?',
    'confirm_delete' => 'Really delete this fleet?',
    'no_fleets' => 'No fleets available.',

];
