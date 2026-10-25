<?php

return [
    // Resource names (used in tooltips)
    'res_credits' => 'Credits',
    'res_supply' => 'Kolonisten',
    'res_regolith' => 'Regolith',
    'res_werkstoffe' => 'Werkstoffe',
    'res_organika' => 'Organika',
    'res_trust' => 'Vertrauen',

    // Chip popup descriptions
    'popup_sol_title' => 'Sol',
    'popup_sol_desc' => 'Jeder Sol ist ein Tag in der Kolonie — eine Chance mehr, bevor Verfall und Knappheit das letzte Wort haben. Entscheide, dann löse aus.',

    'popup_cr_title' => 'Credits',
    'popup_cr_desc' => 'Der einzige Faden zur Außenwelt — Credits bezahlen Berater-Gehälter, Baukosten und Nexus-Raten. Wer den Bestand unterschätzt, steht schnell mit leeren Händen da.',

    'popup_nx_title' => 'Nexus-Schuld',
    'popup_nx_desc' => 'Was der Nexus geliehen hat, will er zurück. Überschreitest du das Schuldenlimit, entzieht er die Konzession — ohne Gnade, ohne Vorlaufzeit.',

    'popup_sup_title' => 'Kolonisten',
    'popup_sup_desc' => 'Die Kolonisten der Siedlung: so viele, wie Kommandozentrale und Wohnhabitate unterbringen — fest, ohne Zuzug. Davon beschäftigt sind die Kolonisten, die einen Arbeitsplatz in Gebäuden oder Forschung besetzen; der Rest ist frei und deckt den Bedarf neuer Gebäude. Reichen die freien Kolonisten nicht, ruhen Neubau und Ausbau, bis Wohnhabitat oder Kommandozentrale wachsen. Geht Wohnraum verloren, sinken die Kolonisten sofort. Jeder Kolonist braucht Platz, aber nur Beschäftigte arbeiten.',
    'popup_sup_source_cc' => 'Kommandozentrale',
    'popup_sup_source_housing' => 'Wohnhabitat',
    'popup_sup_source_knowledge' => 'Kenntnisse',
    'popup_sup_used_buildings' => 'Arbeitsplätze: Gebäude',
    'popup_sup_used_researches' => 'Arbeitsplätze: Forschung',
    'popup_sup_used_advisors' => 'Berater',
    'popup_sup_free' => 'Frei',
    // TODO content-writer (T43-G8)
    'popup_sup_colonists' => 'Kolonisten',
    'popup_sup_employed' => 'davon beschäftigt',
    'popup_sup_unfilled' => 'Unbesetzte Arbeitsplätze',
    'popup_sup_staffing' => 'Rohstoffproduktion',
    'popup_sup_staffing_value' => ':pct %',

    'popup_ap_title' => 'Aktionspunkte',
    'popup_ap_desc' => 'Der gemeinsame Pool deiner Kolonie — Bau, Kenntnisse, Erkundung und Handel teilen sich dieselbe Kapazität.',

    'popup_ap_base' => 'Basis-AP',
    'popup_ap_advisor' => 'durch Berater',
    'popup_ap_trust_multiplier' => 'Vertrauen-Multiplikator',
    'popup_ap_plague_multiplier' => 'Seuchenausbruch',

    // Per-Sol balance rows in the Regolith/Organika/Werkstoffe popups
    'popup_flow_net' => 'Bilanz pro Sol',
    'popup_flow_harvester' => 'Harvester',
    'popup_flow_geology' => 'Geologie',
    'popup_flow_agrardom' => 'Agrardom',
    'popup_flow_agronomy' => 'Agronomie',
    'popup_flow_colonists' => 'Kolonisten (Verpflegung)',
    'popup_flow_subsidy' => 'Nexus-Subvention',
    'popup_flow_relay' => 'Relaisvergütung (Uplink)',
    'popup_flow_upkeep' => 'Berater-Unterhalt',
    'popup_flow_none' => 'Keine laufende Produktion — nur Import und Handel.',

    'popup_rg_title' => 'Regolith',
    'popup_rg_desc' => 'Das Gestein unter den Füßen. Der Harvester bricht es aus dem Boden — die Kolonie verbaut es. Fast alles, was hier gebaut wird, beginnt mit Regolith.',

    'popup_w_title' => 'Werkstoffe',
    'popup_w_desc' => 'Veredelte Legierungen, technische Komponenten — vor Ort nicht herzustellen. Jede Einheit muss importiert werden, und das schlägt sich im Preis nieder.',

    'popup_o_title' => 'Organika',
    'popup_o_desc' => 'Was der Agrardom unter seiner Kuppel anbaut. Organika versorgt die Kolonisten, hält die Crew am Leben und ist die biologische Grundlage für Verpflegung und Forschung.',

    // Abkürzungs-basierte Keys (Chip nutzt die resources.abbreviation: Co = Werkstoffe, Or = Organika).
    'popup_co_title' => 'Werkstoffe',
    'popup_co_desc' => 'Raffinierte Metalle, Legierungen, Präzisionskomponenten — lokal nicht herstellbar, nur über Handel oder Nexus-Import beschaffbar. Nötig für High-Tech-Gebäude, und jede Einheit hat ihren Preis.',

    'popup_or_title' => 'Organika',
    'popup_or_desc' => 'Alles, was der Agrardom unter seinem Dach anbaut: Nahrung, Medizin, Biodünger. Ohne ausreichend Organika hungern die Kolonisten — und das Vertrauen bricht schnell ein.',

    'popup_trust_title' => 'Vertrauen',
    'popup_trust_desc' => 'Was die Kolonisten noch hält. Sinkt dieser Wert zu weit, arbeitet niemand mehr sein Bestes — und irgendwann hört niemand mehr hin.',
    'popup_trust_hunger' => 'Hunger',
];
