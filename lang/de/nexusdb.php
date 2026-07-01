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
    'concept_supply_title' => 'Versorgung',
    'concept_supply_body' => 'Versorgung bezeichnet die kolonieweite Betriebskapazität, abstrahiert aus Strom-, Wasser- und Personalinfrastruktur. Jede in Betrieb genommene Einheit belegt einen festen Kapazitätsanteil. Wird das Kapazitätslimit erreicht, ist keine weitere Inbetriebnahme möglich; bestehende Einheiten bleiben unverändert aktiv. Kapazitätserweiterung erfolgt durch höherstufige Kommandozentralen oder zusätzliche Wohnhabitate.',

    'concept_trust_title' => 'Vertrauen',
    'concept_trust_body' => 'Vertrauen bezeichnet den Zustimmungsindex der Kolonistenbevölkerung gegenüber der Kolonieleitung, skaliert von 0 bis 100. Zwischenfälle, Versorgungsausfälle und Sicherheitsmängel senken den Wert; stabile Betriebsbedingungen und erfüllte Versorgungserwartungen erhöhen ihn. Unterschreiten kritischer Schwellwerte reduziert die kolonieweite Produktionseffizienz und wird in der Konzessionsbewertung des Direktors negativ vermerkt.',

    'concept_sol_title' => 'Sol',
    'concept_sol_body' => 'Sol bezeichnet die Standardzeiteinheit eines Konzessionsbetriebs — definiert als vollständiger Planetentag am Standort der Kolonie. Pro Sol wird ein vollständiger Berechnungszyklus ausgeführt: Ressourcenproduktion, Verschleißberechnung, anstehende Lieferungen, Ereignisverarbeitung. Alle zeitbezogenen Parameter in der Koloniebuchhaltung sind in Solen ausgedrückt.',

    'concept_ap_title' => 'Aktionspunkte',
    'concept_ap_body' => 'Aktionspunkte werden durch zugewiesenes Beraterpersonal pro Sol bereitgestellt und sind dem jeweiligen Aufgabenbereich zugeordnet. Unterschiedliche Beratertypen liefern unterschiedliche AP-Typen. Nicht verbrauchte AP verfallen am Sol-Ende — eine Übertragung auf folgende Sole ist nicht vorgesehen. Ohne ausreichende AP-Verfügbarkeit können Ausbau-, Forschungs- und Instandhaltungsmaßnahmen nicht durchgeführt werden.',

    'concept_decay_title' => 'Verfall',
    'concept_decay_body' => 'Alle Koloniegebäude unterliegen einem kontinuierlichen Statusverlust durch regulären Betriebsverschleiß. Der Statuswert eines Gebäudes sinkt pro Sol automatisch um einen festen Betrag. Erreicht der Statuswert null, verliert das Gebäude eine Betriebsstufe. Regelmäßiger Bau-AP-Einsatz durch Baumeister-Personal ist erforderlich, um den Statusverlust zu kompensieren.',

    'concept_repair_title' => 'Reparatur',
    'concept_repair_body' => 'Reparaturmaßnahmen erhöhen den Statuswert eines Gebäudes durch gezielten AP- und Ressourceneinsatz. Jeder Reparaturvorgang erhöht den Statuswert in Richtung des Maximalwerts. Reparaturen an Koloniegebäuden (ausgenommen Kommandozentrale und Harvester) erfordern zusätzlich Regolith. Ohne regelmäßige Instandhaltung sinkt der Status weiter bis zur Betriebsunfähigkeit.',

    'concept_nexus_title' => 'Nexus',
    'concept_nexus_body' => 'Der Nexus ist die interstellare Verwaltungsbehörde mit Konzessionshoheit über alle anerkannten Kolonien. Infrastruktur- und Schiffsanforderungen werden gegen Credits oder Nexus-Kredit abgewickelt. Der Direktor ist verpflichtet, alle Kreditverbindlichkeiten innerhalb der Konzessionslaufzeit zu begleichen. Bei Überschreitung des Kreditlimits oder Nichterfüllung der Konzessionsziele behält sich der Nexus den Entzug der Betriebsgenehmigung vor.',

    'concept_colonists_title' => 'Kolonisten',
    'concept_colonists_body' => 'Kolonisten bezeichnen die Arbeitskräfte und Bewohner einer Konzessionskolonie. Der aggregierte Zustimmungsindex (Vertrauen) ist die primäre soziale Kenngröße; Einzelpersonen werden im System nicht erfasst. Sinkende Zustimmung reduziert die Arbeitsbereitschaft und damit die Produktionsleistung kolonieweit. Kritische Vertrauenswerte lösen Leistungsabzüge in der Konzessionsbewertung aus.',

];
