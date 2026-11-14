<?php

/**
 * Nexus-Datenbank — UI strings for the concept glossary screen.
 *
 * Localization: lang/de/nexusdb.php
 */
return [

    // Page header
    'page_title' => 'Nexus-Datenbank',
    'page_subtitle' => 'Referenz für Spielmechaniken und Begriffe.',

    // Concept cards
    'tab_concepts' => 'Konzepte',
    'tab_buildings' => 'Gebäude',
    'tab_knowledge' => 'Kenntnisse',
    'tab_ships' => 'Schiffe',
    'tab_advisors' => 'Berater',
    'asset_nexus_cost' => 'Nexus-Preis',
    'asset_delivery' => 'Lieferzeit',
    'asset_delivery_value' => ':count Sol',
    'building_requires' => 'Voraussetzung',
    'building_requires_none' => 'keine',
    'building_requires_cc' => 'Kommandozentrale Lv:level',
    'building_requires_building' => ':name Lv:level',
    'building_cost_place' => 'Platzieren',
    'building_cost_free' => 'kostenlos',
    'building_colonists' => 'Kolonisten',
    'building_max_level' => 'Höchststufe',
    'building_levels' => 'Ausbaustufen',
    'building_col_level' => 'Stufe',
    'building_col_ap' => 'AP',
    'building_col_regolith' => 'Regolith',
    'building_focus_note' => 'Dies ist eines von drei Fokus-Gebäuden. Du hast die Wahl worauf der Fokus zuerst liegt: Kenntnisse, Beziehungen oder Missionen.',

    // Advisor ranks and knowledge effects (Nexus-DB tabs)
    'advisor_ranks' => 'Ränge',
    'advisor_col_rank' => 'Rang',
    'advisor_col_cost' => 'Einmalig (Cr)',
    'advisor_col_upkeep' => 'Unterhalt (Cr/Sol)',
    'advisor_col_bonus' => 'Wirkung',
    'advisor_cost_hire' => 'Einstellung',
    'advisor_cost_promotion' => 'Beförderung',
    'advisor_focus' => 'Gehört zum Fokus-Gebäude',
    'bonus_ap' => ':n AP pro Sol',
    'bonus_ap_cap' => 'AP-Deckel: :n AP je Gebäude und Sol',
    'bonus_ap_cap_lab' => 'Labor-Deckel: :n AP je Kenntnis und Sol',
    'bonus_ap_cap_training' => 'Training-Deckel: :n AP je Training und Sol',
    'bonus_mission_chance' => '+:n % Erfolgschance bei Missionen',
    'bonus_ship_discount' => 'Schiffsverhandlung: :n Cr Rabatt je investiertem AP',
    'bonus_merchant_forecast' => 'Händlerbesuch :n Sol im Voraus angekündigt',
    'effect_trust' => ':n Vertrauen',
    'knowledge_col_effect' => 'Wirkung',
    'knowledge_effect_none' => '—',

    'concept_supply_title' => 'Kolonisten-Kapazität',
    'concept_supply_body' => 'Die Kolonisten-Kapazität bezeichnet, wie viele Menschen deine Siedlung tragen kann — abstrahiert aus Unterkunft, Strom, Wasser und Infrastruktur. Jedes Gebäude und jede Forschung bindet pro Ausbaustufe eine feste Zahl davon; ein größeres Labor braucht mehr Leute als ein kleines. Ist die Kapazität erreicht, kann nichts Neues in Betrieb genommen werden; bestehende Einheiten bleiben aktiv. Wird sie überschritten, verfällt alles schneller. Mehr Kapazität bringen höherstufige Kommandozentralen, zusätzliche Wohnhabitate und Kenntnisse.',

    'concept_trust_title' => 'Vertrauen',
    'concept_trust_body' => 'Vertrauen bezeichnet den Zustimmungsindex der Kolonistenbevölkerung gegenüber der Kolonieleitung, skaliert von 0 bis 100. Zwischenfälle, Versorgungsausfälle und Sicherheitsmängel senken den Wert; stabile Betriebsbedingungen und erfüllte Versorgungserwartungen erhöhen ihn. Unterschreiten kritischer Schwellwerte reduziert die kolonieweite Produktionseffizienz und wird in der Konzessionsbewertung des Direktors negativ vermerkt.',

    'concept_sol_title' => 'Sol',
    'concept_sol_body' => 'Sol bezeichnet die Standardzeiteinheit eines Konzessionsbetriebs — definiert als vollständiger Planetentag am Standort der Kolonie. Pro Sol wird ein vollständiger Berechnungszyklus ausgeführt: Ressourcenproduktion, Verschleißberechnung, anstehende Lieferungen, Ereignisverarbeitung. Alle zeitbezogenen Parameter in der Koloniebuchhaltung sind in Solen ausgedrückt.',

    'concept_ap_title' => 'Aktionspunkte',
    'concept_ap_body' => 'Aktionspunkte werden durch zugewiesenes Beraterpersonal pro Sol bereitgestellt und speisen einen einzigen koloniegebundenen Pool. Aus diesem Pool werden Ausbau-, Forschungs-, Erkundungs- und Handelsmaßnahmen gleichermaßen finanziert. Nicht verbrauchte AP verfallen am Sol-Ende — eine Übertragung auf folgende Sole ist nicht vorgesehen. Ohne ausreichende AP-Verfügbarkeit können Ausbau-, Forschungs- und Instandhaltungsmaßnahmen nicht durchgeführt werden.',

    'concept_decay_title' => 'Verfall',
    'concept_decay_body' => 'Alle Koloniegebäude unterliegen einem kontinuierlichen Statusverlust durch regulären Betriebsverschleiß. Der Statuswert eines Gebäudes sinkt pro Sol automatisch um einen festen Betrag. Erreicht der Statuswert null, verliert das Gebäude eine Betriebsstufe. Regelmäßiger AP-Einsatz durch Baumeister-Personal ist erforderlich, um den Statusverlust zu kompensieren.',

    'concept_repair_title' => 'Reparatur',
    'concept_repair_body' => 'Reparaturmaßnahmen erhöhen den Statuswert eines Gebäudes durch gezielten AP- und Ressourceneinsatz. Jeder Reparaturvorgang erhöht den Statuswert in Richtung des Maximalwerts. Reparaturen an Koloniegebäuden (ausgenommen Kommandozentrale und Harvester) erfordern zusätzlich Regolith. Ohne regelmäßige Instandhaltung sinkt der Status weiter bis zur Betriebsunfähigkeit.',

    'concept_nexus_title' => 'Nexus',
    'concept_nexus_body' => 'Der Nexus ist die interstellare Verwaltungsbehörde mit Konzessionshoheit über alle anerkannten Kolonien. Infrastruktur- und Schiffsanforderungen werden gegen Credits oder Nexus-Kredit abgewickelt. Der Direktor ist verpflichtet, alle Kreditverbindlichkeiten innerhalb der Konzessionslaufzeit zu begleichen. Bei Überschreitung des Kreditlimits oder Nichterfüllung der Konzessionsziele behält sich der Nexus den Entzug der Betriebsgenehmigung vor.',

    'concept_colonists_title' => 'Kolonisten',
    'concept_colonists_body' => 'Kolonisten bezeichnen die Arbeitskräfte und Bewohner einer Konzessionskolonie. Der aggregierte Zustimmungsindex (Vertrauen) ist die primäre soziale Kenngröße; Einzelpersonen werden im System nicht erfasst. Sinkende Zustimmung reduziert die Arbeitsbereitschaft und damit die Produktionsleistung kolonieweit. Kritische Vertrauenswerte lösen Leistungsabzüge in der Konzessionsbewertung aus.',

    'concept_focus_title' => 'Fokus-Gebäude',
    'concept_focus_body' => 'Labor, Hangar und Cantina sind die drei Fokus-Gebäude. Jedes steht für einen eigenen Bereich: das Labor für Kenntnisse, der Hangar für Missionen und Schiffe, die Cantina für Beziehungen — Begegnungen, Handel und Anliegen. Ein Fokus-Gebäude gehört zum Grundaufbau der ersten Phase; du wählst, welches du zuerst baust, und das prägt den Run. Das zweite und dritte kannst du erst bauen, wenn die Kommandozentrale hoch genug ausgebaut ist. Jedem Fokus-Gebäude ist ein Berater zugeordnet: Analytiker zum Labor, Raumfahrer zum Hangar, Konsul zur Cantina. Sein Berater-Slot lässt sich erst besetzen, wenn das Gebäude steht. Der Baumeister gehört zu keinem Fokus-Gebäude — er ist der feste erste Berater und kümmert sich um den Ausbau.',

];
