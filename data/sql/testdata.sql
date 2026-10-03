INSERT INTO user (user_id,username,display_name,role,password,email,activated,activation_key,registration,remember_token) VALUES(6,'Homer','','player','$2y$10$tqJJsdnuAuhVcqtdqeby3.ytOSc2AupZs6LjST3GjiKytKBsuxp8m','homer@nouron.de',0,'adsfsdfsf','2000-01-01 00:00:00',NULL);
INSERT INTO user (user_id,username,display_name,role,password,email,activated,activation_key,registration,remember_token) VALUES(1,'Marge','','player','$2y$10$tqJJsdnuAuhVcqtdqeby3.ytOSc2AupZs6LjST3GjiKytKBsuxp8m','marge@nouron.de',0,'gaqx2hwrf4env5i3','2009-12-23 14:00:00',NULL);
INSERT INTO user (user_id,username,display_name,role,password,email,activated,activation_key,registration,remember_token) VALUES(2,'Lisa','','player','$2y$10$tqJJsdnuAuhVcqtdqeby3.ytOSc2AupZs6LjST3GjiKytKBsuxp8m','lisa@nouron.de',0,'abcdefg','2000-01-01 00:00:00',NULL);
INSERT INTO user (user_id,username,display_name,role,password,email,activated,activation_key,registration,remember_token) VALUES(3,'Bart','','admin','$2y$10$9MytO4OOq3Z4MWvNcT1UreUqsTSw6IYuWCQ3bTdkmqAwa5vUJr8wG','bart@nouron.de',1,'','2000-01-01 00:00:00',NULL);
INSERT INTO user (user_id,username,display_name,role,password,email,activated,activation_key,registration,remember_token) VALUES(4,'Maggy','','player','$2y$10$tqJJsdnuAuhVcqtdqeby3.ytOSc2AupZs6LjST3GjiKytKBsuxp8m','maggy@nouron.de',1,'abcdefg','2000-01-01 00:00:00',NULL);
INSERT INTO user (user_id,username,display_name,role,password,email,activated,activation_key,registration,remember_token) VALUES(5,'Moe','','player','$2y$10$tqJJsdnuAuhVcqtdqeby3.ytOSc2AupZs6LjST3GjiKytKBsuxp8m','moe@nouron.de',1,'abcdefg','2000-01-01 00:00:00',NULL);
INSERT INTO user (user_id,username,display_name,role,password,email,activated,activation_key,registration,remember_token) VALUES(18,'Lenny','','player','$2y$10$tqJJsdnuAuhVcqtdqeby3.ytOSc2AupZs6LjST3GjiKytKBsuxp8m','lenny@nouron.de',0,'','2000-01-01 00:00:00',NULL);
INSERT INTO user (user_id,username,display_name,role,password,email,activated,activation_key,registration,remember_token) VALUES(19,'Carl','','player','$2y$10$tqJJsdnuAuhVcqtdqeby3.ytOSc2AupZs6LjST3GjiKytKBsuxp8m','carl@nouron.de',0,'','2000-01-01 00:00:00',NULL);
-- glx_colonies: id, name, user_id, is_primary, hunger_streak
-- Only player colonies exist (colony 2 "Shelbyville", removed 2026-09-23).
INSERT INTO glx_colonies (id,name,user_id,is_primary,hunger_streak) VALUES(1,'Springfield',3,1,0);
-- Credits (1) + Supply (2): legacy base costs (not consumed by the hex build flow).
-- Regolith (3) + Werkstoffe (4): construction cost (canonical: config/buildings.php build_cost).
-- CC (25) + Harvester (27) carry none (bootstrap). Organika is never a build cost.
INSERT INTO researches (id,purpose,name,required_building_id,required_building_level,required_building2_id,required_building2_level,`row`,`column`,ap_for_levelup,max_status_points,decay_rate,supply_cost,is_active) VALUES(9901,'civil','test_decay_placeholder',NULL,NULL,NULL,NULL,99,99,1,20,0.13,NULL,0);
-- Ships cost Credits only (owner rule: Schiffe nur Credits — no resource cost).
INSERT INTO colony_buildings (colony_id,building_id,level,status_points,ap_spend) VALUES(1,25,3,20,0);
INSERT INTO colony_buildings (colony_id,building_id,level,status_points,ap_spend) VALUES(1,27,1,20,0);
INSERT INTO colony_buildings (colony_id,building_id,level,status_points,ap_spend) VALUES(1,28,2,20,2);
-- Two more housingComplex instances for colony 1 (ROADMAP T8, 2026-09-22): the single
-- level-2 instance above only funds a 26 supply cap (10 CC flat + 16), far below what
-- colony 1's other buildings actually use (62, see infirmary/sciencelab/hangar below) —
-- building out housing further is the normal in-game way a player raises the cap, and
-- is the only lever besides CC (CC's contribution is flat, not ×level, regardless of CC
-- level — see ResourcesService::getSupplyBreakdown()). Total housing level sum = 2+3+2 = 7
-- -> cap = 10 + 7*8 = 66, matching user_resources.supply below. Kept as separate instance
-- rows (not a level bump on the existing row) so every "housing level=2" fixture comment
-- elsewhere (a single-instance reading) stays literally true.
INSERT INTO colony_buildings (colony_id,building_id,instance_id,level,status_points,ap_spend) VALUES(1,28,4,3,20,0);
INSERT INTO colony_buildings (colony_id,building_id,instance_id,level,status_points,ap_spend) VALUES(1,28,5,2,20,0);
INSERT INTO colony_buildings (colony_id,building_id,level,status_points,ap_spend,placed_at_tick) VALUES(1,31,1,10,0,0);
-- Infirmary (46) for colony 1: used as a generic "uncapped, upgradable building" stand-in
-- by BuildingServiceTest/ColonyZoneDecoupleTest/BuildResourceSinkTest (ex-depot, removed 2026-06-22).
INSERT INTO colony_buildings (colony_id,building_id,level,status_points,ap_spend) VALUES(1,46,3,10,10);
INSERT INTO colony_buildings (colony_id,building_id,level,status_points,ap_spend) VALUES(1,52,0,0,0);
INSERT INTO colony_resources (resource_id,colony_id,amount) VALUES(3,1,250);
INSERT INTO colony_resources (resource_id,colony_id,amount) VALUES(4,1,50);
INSERT INTO colony_resources (resource_id,colony_id,amount) VALUES(5,1,50);
INSERT INTO colony_resources (resource_id,colony_id,amount) VALUES(12,1,0);
-- colony_buildings: two hangar bays (building_id=44) for colony 1 (Springfield).
-- Hangar 1 sits on Lv3 so every ship docked there is active: corvette needs Lv3,
-- freighter Lv2, drone Lv1 (HangarService::SHIP_ID_TO_REQUIRED_HANGAR_LEVEL, T14/T20).
-- Hangar 2 stays Lv1 (only a history row in colony_hangar_missions references it).
INSERT INTO colony_buildings (colony_id,building_id,instance_id,level,status_points,ap_spend,placed_at_tick) VALUES(1,44,1,3,20,0,1);
INSERT INTO colony_buildings (colony_id,building_id,instance_id,level,status_points,ap_spend,placed_at_tick) VALUES(1,44,2,1,20,0,1);

INSERT INTO colony_ships (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(1,29,0,10,1,NULL,'docked',NULL,NULL);
INSERT INTO colony_ships (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(1,37,9,10,1,NULL,'docked',NULL,NULL);
INSERT INTO colony_ships (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(1,47,3,10,0,NULL,'docked',NULL,NULL);
INSERT INTO colony_ships (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(1,49,12,10,1,NULL,'docked',NULL,NULL);
INSERT INTO colony_ships (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(1,83,17,10,1,NULL,'docked',NULL,NULL);
INSERT INTO colony_ships (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(1,84,16,10,1,NULL,'docked',NULL,NULL);
INSERT INTO colony_ships (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(1,85,5,3,0,NULL,'docked',NULL,NULL);

-- Assign hangar bays: corvette (ship_id=37) + freighter (ship_id=47) → hangar 1 (Lv3) on colony 1.
-- Both used to sit in Lv1 hangars and were therefore inactive since T14.
-- Drone (ship_id=85) dispatched from hangar 1, so ship_state=dispatched
UPDATE colony_ships SET hangar_instance_id=1, ship_state='dispatched' WHERE colony_id=1 AND ship_id=85;
UPDATE colony_ships SET hangar_instance_id=1, ship_state='docked'     WHERE colony_id=1 AND ship_id=37;
UPDATE colony_ships SET hangar_instance_id=1, ship_state='docked'     WHERE colony_id=1 AND ship_id=47;

-- Hangar missions for colony 1:
-- Mission 1: drone dispatched from hangar 1, currently active
-- Mission 2: freighter recalled from hangar 2, completed (history row; the freighter now docks in hangar 1)
INSERT INTO colony_hangar_missions (colony_id,instance_id,ship_id,destination,sol_distance,dispatch_tick,recall_tick,state,created_at) VALUES(1,1,85,'mission_recon_flight',1,1,NULL,'active','2026-06-03 00:00:00');
INSERT INTO colony_hangar_missions (colony_id,instance_id,ship_id,destination,sol_distance,dispatch_tick,recall_tick,state,created_at) VALUES(1,2,47,'mission_supply_run',2,1,3,'recalled','2026-06-03 00:00:00');
INSERT INTO colony_researches (colony_id,research_id,level,status_points,ap_spend) VALUES(1,9901,1,20,0);
INSERT INTO colony_researches (colony_id,research_id,level,status_points,ap_spend) VALUES(1,96,0,10,0);
INSERT INTO advisors (user_id,colony_id,personell_id,`rank`,active_ticks) VALUES(3,1,35,1,0);
INSERT INTO colony_log (id,user,tick,event,area,parameters,created_at,is_read) VALUES(16,3,15405,'techtree.level_up_finished','techtree','{"entity_type":"knowledge","entity_name":"knowledge_construction","new_level":1,"tech_id":90}',NULL,1);
INSERT INTO colony_log (id,user,tick,event,area,parameters,created_at,is_read) VALUES(26,3,1,'colony.building_placed','colony','{"building_id":25,"building_name":"building_commandCenter","colony_id":1}',NULL,1);
INSERT INTO colony_log (id,user,tick,event,area,parameters,created_at,is_read) VALUES(27,3,1,'colony.building_placed','colony','{"building_id":27,"building_name":"building_harvester","colony_id":1}',NULL,1);
INSERT INTO colony_log (id,user,tick,event,area,parameters,created_at,is_read) VALUES(28,3,2,'colony.building_invested','colony','{"building_id":25,"building_name":"building_commandCenter","ap_spend":1,"ap_for_levelup":5,"level_up":false,"new_level":1}',NULL,1);
INSERT INTO colony_log (id,user,tick,event,area,parameters,created_at,is_read) VALUES(29,3,2,'colony.building_invested','colony','{"building_id":25,"building_name":"building_commandCenter","ap_spend":1,"ap_for_levelup":5,"level_up":false,"new_level":1}',NULL,1);
INSERT INTO colony_log (id,user,tick,event,area,parameters,created_at,is_read) VALUES(30,3,2,'colony.building_invested','colony','{"building_id":28,"building_name":"building_housingComplex","ap_spend":1,"ap_for_levelup":3,"level_up":false,"new_level":1}',NULL,1);
INSERT INTO colony_log (id,user,tick,event,area,parameters,created_at,is_read) VALUES(31,3,2,'colony.tile_explored','colony','{"colony_id":1,"q":1,"r":-1}',NULL,1);
INSERT INTO colony_log (id,user,tick,event,area,parameters,created_at,is_read) VALUES(32,3,3,'colony.building_invested','colony','{"building_id":28,"building_name":"building_housingComplex","ap_spend":1,"ap_for_levelup":3,"level_up":true,"new_level":2}',NULL,1);
INSERT INTO colony_log (id,user,tick,event,area,parameters,created_at,is_read) VALUES(33,3,3,'techtree.advisor_hired','techtree','{"advisor_type":"scientist","colony_id":1,"credits_cost":400}',NULL,1);
INSERT INTO colony_log (id,user,tick,event,area,parameters,created_at,is_read) VALUES(34,3,3,'merchant.visit','merchant','{"colony_id":1}',NULL,1);
INSERT INTO colony_log (id,user,tick,event,area,parameters,created_at,is_read) VALUES(35,3,4,'techtree.level_down','techtree','{"entity_type":"building","entity_name":"building_harvester","new_level":0,"tech_id":27}',NULL,1);
INSERT INTO colony_log (id,user,tick,event,area,parameters,created_at,is_read) VALUES(36,3,4,'techtree.level_down','techtree','{"entity_type":"knowledge","entity_name":"knowledge_cartography","new_level":1,"tech_id":91}',NULL,1);
INSERT INTO colony_log (id,user,tick,event,area,parameters,created_at,is_read) VALUES(37,3,4,'trade.bar_accepted','trade','{"colony_id":1,"give_resource_id":3,"give_amount":80,"get_resource_id":1,"get_amount":200}',NULL,1);
INSERT INTO colony_log (id,user,tick,event,area,parameters,created_at,is_read) VALUES(38,3,5,'trade.merchant_purchase','trade','{"colony_id":1,"item_type":"ap_package","cost_credits":100}',NULL,1);
INSERT INTO colony_log (id,user,tick,event,area,parameters,created_at,is_read) VALUES(39,3,5,'colony.tile_deep_scanned','colony','{"colony_id":1,"q":2,"r":0}',NULL,1);

-- supply=66 (ROADMAP T8, 2026-09-22): matches the GameTick-computed cap for colony 1's
-- CC lvl3 (flat 10) + housing sum=7 (56) = 66 — was 18, which was far below the colony's
-- own building usage (62 since T20) and unreachable in normal play (build/levelup is supply-gated).
-- Used supply for colony 1 = 62 (harvester 2 + sciencelab 6 + infirmary 30 + hangar Lv3 18
-- + hangar Lv1 6; supply_cost × level, values from config/buildings.php), leaving a 4-point buffer. See data/sql notes above the colony_buildings housing rows.
INSERT INTO user_resources (user_id,credits,supply) VALUES(3,2700,66);

INSERT INTO user_preferences (id,user_id,onboarding_hints,created_at,updated_at,dismissed_hints,fired_triggers,sol_report_skip) VALUES(1,6,1,NULL,NULL,NULL,NULL,0);
INSERT INTO user_preferences (id,user_id,onboarding_hints,created_at,updated_at,dismissed_hints,fired_triggers,sol_report_skip) VALUES(2,1,1,NULL,NULL,NULL,NULL,0);

-- Phase-based techtree grid positions (migration 2026_05_10_000001 — layout v2)
-- Phase 1 (CC Lv1): housingComplex, harvester, bioFacility, engineer
-- Phase 2 (CC Lv2): depot, sciencelab, infirmary, bar, scientist, trader,
--                   knowledge_construction, knowledge_agronomy, knowledge_health, knowledge_trade,
--                   knowledge_geology
-- Owner-Playtest-Fund 2026-09-05: geology showed in the Phase 3 (CC Lv3) column
-- even though its real gate (sciencelab Lv2 + harvester Lv1) has no CC-Lv3
-- requirement — moved next to sciencelab in phase 2, its actual prerequisite.
-- Phase 3 (CC Lv3): hangar, strategist, drone, pilot,
--                   freighter, knowledge_cartography, corvette, knowledge_defense
-- Phase 4 (CC Lv4)
-- Phase 5 (CC Lv5)
INSERT INTO user_preferences (id,user_id,onboarding_hints,created_at,updated_at,dismissed_hints,fired_triggers,sol_report_skip) VALUES(3,3,1,NULL,NULL,NULL,NULL,0);

-- Bar offers (migration 2026_05_14_000003)
-- colony_id=1 (Springfield), expires_tick=9999999 (far future, always valid in tests)
-- Offer 1: pay 800 Credits, receive 50 Compounds (Werkstoffe)
INSERT INTO bar_offers (colony_id,give_resource_id,give_amount,get_resource_id,get_amount,expires_tick,is_accepted,created_at,updated_at) VALUES(1,1,800,4,50,9999999,0,'2026-05-14 00:00:00','2026-05-14 00:00:00');
-- Offer 2: pay 20 Regolith, receive 30 Organics (Organika)
INSERT INTO bar_offers (colony_id,give_resource_id,give_amount,get_resource_id,get_amount,expires_tick,is_accepted,created_at,updated_at) VALUES(1,3,20,5,30,9999999,0,'2026-05-14 00:00:00','2026-05-14 00:00:00');

-- Active run for Bart (user_id=3) on Springfield (colony_id=1).
-- Exactly ONE active run exists in the fixture, deliberately: the game is singleplayer,
-- and `game:tick` without --run refuses to guess between several active runs.
-- Homer's active run on Shelbyville was removed here (2026-07-17) and Shelbyville
-- (colony 2, playerless multiplayer-era leftover) itself on 2026-09-23 (A14): the game
-- only knows player colonies. Tests that need a foreign colony create one themselves
-- (with its own user) instead of relying on the fixture.
INSERT INTO runs (id,user_id,colony_id,current_tick,status,started_at,ended_at,settings,phase,fail_reason,nexus_debt,phase2_start_tick,created_at,updated_at) VALUES(1,3,1,5,'active','2026-05-23 00:00:00',NULL,'{"tick_limit":100,"bypass":{"ap_checks":false,"resource_costs":false,"supply_checks":false},"supply_cap_max":200,"max_players":1}',1,NULL,3000,NULL,'2026-05-23 00:00:00','2026-05-23 00:00:00');
