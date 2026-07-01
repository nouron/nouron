<?php

/**
 * Nexus Database — UI strings for the concept glossary screen.
 *
 * Localization: lang/en/nexusdb.php
 */
return [

    // Page header
    'page_title' => 'Nexus Database',
    'page_subtitle' => 'Reference for game mechanics and terminology.',

    // Concept cards
    'concept_supply_title' => 'Supply',
    'concept_supply_body' => 'Supply denotes the colony-wide operational capacity, abstracted from power, water, and personnel infrastructure. Each commissioned unit occupies a fixed share of that capacity. Once the capacity limit is reached, no further unit can be commissioned; existing units remain unaffected. Capacity expansion is achieved through higher-level Command Centers or additional Residential Habitats.',

    'concept_trust_title' => 'Trust',
    'concept_trust_body' => 'Trust denotes the approval index of the colonist population toward colony management, scaled from 0 to 100. Incidents, supply failures, and security deficiencies lower the value; stable operating conditions and met supply expectations raise it. Falling below critical thresholds reduces colony-wide production efficiency and is recorded as a negative mark in the Director\'s concession assessment.',

    'concept_sol_title' => 'Sol',
    'concept_sol_body' => 'Sol denotes the standard time unit of a concession operation — defined as one complete planetary day at the colony\'s location. Each Sol executes a full calculation cycle: resource production, wear calculation, pending deliveries, event processing. All time-related parameters in the colony ledger are expressed in Sols.',

    'concept_ap_title' => 'Action Points',
    'concept_ap_body' => 'Action Points are provided per Sol by assigned advisor personnel and are allocated to the respective functional domain. Different advisor types deliver different AP types. Unused AP expire at Sol end — carry-over to subsequent Sols is not provided. Without sufficient AP availability, construction, research, and maintenance measures cannot be performed.',

    'concept_decay_title' => 'Decay',
    'concept_decay_body' => 'All colony buildings are subject to continuous status loss through regular operational wear. A building\'s status value decreases automatically each Sol by a fixed amount. When the status value reaches zero, the building loses one operational level. Regular Construction AP input from Engineer personnel is required to compensate for status loss.',

    'concept_repair_title' => 'Repair',
    'concept_repair_body' => 'Repair measures increase a building\'s status value through targeted AP and resource expenditure. Each repair operation raises the status value toward its maximum. Repairs to colony buildings (excluding Command Center and Harvester) additionally require Regolith. Without regular maintenance, status continues to decline until the building becomes inoperable.',

    'concept_nexus_title' => 'Nexus',
    'concept_nexus_body' => 'The Nexus is the interstellar administrative authority holding concession sovereignty over all recognised colonies. Infrastructure and ship requests are processed against Credits or Nexus credit. The Director is obligated to settle all credit liabilities within the concession period. In the event of credit limit breach or failure to meet concession objectives, the Nexus reserves the right to revoke the operating licence.',

    'concept_colonists_title' => 'Colonists',
    'concept_colonists_body' => 'Colonists denote the workforce and inhabitants of a concession colony. The aggregated approval index (Trust) is the primary social metric; individuals are not tracked in the system. Declining approval reduces workforce readiness and thereby colony-wide production output. Critical Trust values trigger performance deductions in the concession assessment.',

];
