<?php

return [
    // Resource names (used in tooltips)
    'res_credits' => 'Credits',
    'res_supply' => 'Colonists',
    'res_regolith' => 'Regolith',
    'res_werkstoffe' => 'Compounds',
    'res_organika' => 'Organics',
    'res_trust' => 'Trust',

    // Chip popup descriptions
    'popup_sol_title' => 'Sol',
    'popup_sol_desc' => 'Every Sol is another day in the colony — one more chance before decay and scarcity have the last word. Decide, then trigger.',

    'popup_cr_title' => 'Credits',
    'popup_cr_desc' => 'The only thread to the outside world — Credits pay advisor salaries, construction costs, and Nexus rates. Underestimate your balance and you\'ll find yourself empty-handed fast.',

    'popup_nx_title' => 'Nexus Debt',
    'popup_nx_desc' => 'What the Nexus has lent, it wants back. Exceed the debt limit and it revokes the concession — without mercy, without notice.',

    'popup_sup_title' => 'Colonists',
    'popup_sup_desc' => 'Colonists at work / capacity of the settlement. Every building and every research ties up people per level — a building from the moment it is placed. Once the capacity is exhausted, construction and upgrades rest until the Residential Habitat or Command Center grows. If housing is lost, colonists are left without shelter: Trust falls, and after a short grace period they move away.',
    'popup_sup_source_cc' => 'Command Center',
    'popup_sup_source_housing' => 'Residential Habitat',
    'popup_sup_source_knowledge' => 'Knowledge',
    'popup_sup_used_buildings' => 'Buildings',
    'popup_sup_used_researches' => 'Research',
    'popup_sup_used_advisors' => 'Advisors',
    'popup_sup_free' => 'Free',
    'popup_sup_overcap_homeless' => 'Without shelter',
    'popup_sup_overcap_trust' => 'Trust',
    'popup_sup_overcap_departure' => 'Departure',
    'popup_sup_overcap_departure_value' => 'in :sols Sol',
    'popup_sup_dismiss' => 'Send away',
    'popup_sup_dismissed' => 'Sent away',
    'popup_sup_understaffed' => 'Unfilled workplaces',
    'popup_sup_staffing' => 'Raw-material production',
    'popup_sup_staffing_value' => ':pct %',
    'popup_sup_dismiss_failed' => 'Sending away failed.',

    'popup_ap_title' => 'Action Points',
    'popup_ap_desc' => 'Your colony\'s shared pool — construction, research, exploration, and trade all draw from the same capacity.',

    'popup_ap_base' => 'Base AP',
    'popup_ap_advisor' => 'from advisors',
    'popup_ap_trust_multiplier' => 'Trust multiplier',

    'popup_rg_title' => 'Regolith',
    'popup_rg_desc' => 'The rock beneath your feet. The Harvester breaks it out of the ground — the colony builds with it. Almost everything constructed here starts with Regolith.',

    'popup_w_title' => 'Compounds',
    'popup_w_desc' => 'Refined alloys, technical components — not manufacturable on-site. Every unit must be imported, and that shows in the price.',

    'popup_o_title' => 'Organics',
    'popup_o_desc' => 'What the Agrarian Dome grows beneath its dome. Organics feed the colonists, keep the crew alive, and form the biological basis for provisions and research.',

    // Abbreviation-based keys (chip uses resources.abbreviation: Co = Compounds, Or = Organics)
    'popup_co_title' => 'Compounds',
    'popup_co_desc' => 'Refined metals, alloys, precision components — not producible locally, only obtainable via trade or Nexus import. Required for high-tech structures, and every unit has its price.',

    'popup_or_title' => 'Organics',
    'popup_or_desc' => 'Everything the Agrarian Dome grows under its roof: food, medicine, bio-fertiliser. Without enough Organics, colonists go hungry — and Trust breaks down fast.',

    'popup_trust_title' => 'Trust',
    'popup_trust_desc' => 'What still holds the colonists together. Let this fall too far and nobody gives their best any more — and eventually nobody listens at all.',
    'popup_trust_overcap' => 'Housing shortage',
    'popup_trust_hunger' => 'Hunger',
];
