<?php

return [

    // ── Order type labels ─────────────────────────────────────────────────────

    'order_move' => 'Bewegen',
    'order_hold' => 'Halten',
    'order_trade' => 'Handeln',
    'order_convoy' => 'Konvoi (Eskorte)',
    'order_defend' => 'Verteidigen',
    'order_join' => 'Anschließen',
    'order_attack' => 'Angreifen',
    'order_devide' => 'Aufteilen',

    // ── Order group labels ────────────────────────────────────────────────────

    'order_group_movement' => 'Bewegung',
    'order_group_cooperation' => 'Kooperation',
    'order_group_combat' => 'Kampf',

    // ── Order field labels ────────────────────────────────────────────────────

    'field_dest_x' => 'Ziel X',
    'field_dest_y' => 'Ziel Y',
    'field_colony_id' => 'Kolonie-ID',
    'field_resource_id' => 'Ressource-ID',
    'field_amount' => 'Menge',
    'field_direction' => 'Richtung',
    'field_target_fleet' => 'Ziel-Flotten-ID',

    'direction_buy' => 'Kaufen (Kolonie → Flotte)',
    'direction_sell' => 'Verkaufen (Flotte → Kolonie)',

    // ── Order descriptions (shown below form) ─────────────────────────────────

    'desc_move' => 'Flotte bewegt sich zu den angegebenen Koordinaten.',
    'desc_hold' => 'Flotte hält ihre aktuelle Position für einen Sol.',
    'desc_trade' => 'Flotte lädt Ressourcen bei einer Kolonie auf oder ab.',
    'desc_convoy' => 'Flotte eskortiert die Ziel-Flotte zu deren Zielposition.',
    'desc_defend' => 'Flotte bewegt sich zur Position der Ziel-Flotte, um diese zu verteidigen.',
    'desc_join' => 'Flotte schließt sich der Ziel-Flotte an und fusioniert mit ihr.',
    'desc_attack' => 'Flotte greift die Ziel-Flotte an.',

    // ── Commander assignment ──────────────────────────────────────────────────

    'commander_assigned' => 'Kommandant der Flotte zugewiesen.',
    'commander_removed' => 'Kommandant von der Flotte abberufen.',
    'commander_no_navigator' => 'Kein Navigator auf der Kolonie verfügbar.',
    'commander_navigator_unavailable' => 'Navigator ist derzeit nicht verfügbar.',
    'commander_already_assigned' => 'Diese Flotte hat bereits einen Kommandanten.',

    // ── General UI ────────────────────────────────────────────────────────────

    'order_form_title' => 'Befehl erteilen',
    'order_submit' => 'Befehl erteilen',
    'pending_orders' => 'Ausstehende Befehle',
    'no_pending_orders' => 'Keine ausstehenden Befehle.',
    'fleet_index_title' => 'Flotten',
    'fleet_config_title' => 'Flotten-Konfiguration',
    'fleet_create' => 'Neue Flotte',
    'fleet_name' => 'Flottenname',
    'fleet_delete_confirm' => 'Flotte wirklich auflösen?',
    'confirm_delete' => 'Flotte wirklich löschen?',
    'no_fleets' => 'Keine Flotten vorhanden.',

];
