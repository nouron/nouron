INSERT INTO "resources" VALUES(1,'res_credits','Cr','Event',0,3000,'resicon-credits');
INSERT INTO "resources" VALUES(2,'res_supply','Sup','Event',0,200,'resicon-supply');
INSERT INTO "resources" VALUES(3,'res_regolith','Rg','Level',1,200,'resicon-regolith');
INSERT INTO "resources" VALUES(4,'res_werkstoffe','Co','Level',1,0,'resicon-iron');
INSERT INTO "resources" VALUES(5,'res_organika','Or','Level',1,0,'resicon-silicates');
INSERT INTO "resources" VALUES(12,'res_trust','Tr','Event',0,0,'resicon-moral');
INSERT INTO "user" (user_id,username,display_name,role,password,email,state,faction_id,description,note,disabled,activated,activation_key,first_time_login,last_activity,registration,theme,tooltips_enabled,remember_token) VALUES(0,'Homer','','player','$2y$10$tqJJsdnuAuhVcqtdqeby3.ytOSc2AupZs6LjST3GjiKytKBsuxp8m','homer@nouron.de',NULL,7,'dies ist eine Testbeschreibung dies ist eine Testbeschreibung dies ist eine Testbeschreibung dies ist eine Testbeschreibung dies ist eine Testbeschreibung dies ist eine Testbeschreibung dies ist eine Testbeschreibung dies ist eine Testbeschreibung !"<22>$%&/()=?`*''_:>><<|<7F><80>{[]]\\~\r\n','',0,0,'adsfsdfsf',0,'2012-03-05 10:08:37','0000-00-00 00:00:00','darkred',1,NULL);
INSERT INTO "user" (user_id,username,display_name,role,password,email,state,faction_id,description,note,disabled,activated,activation_key,first_time_login,last_activity,registration,theme,tooltips_enabled,remember_token) VALUES(1,'Marge','','player','$2y$10$tqJJsdnuAuhVcqtdqeby3.ytOSc2AupZs6LjST3GjiKytKBsuxp8m','marge@nouron.de',NULL,6,'','',0,0,'gaqx2hwrf4env5i3',1,'2011-09-18 09:49:10','2009-12-23 14:00:00','darkred',1,NULL);
INSERT INTO "user" (user_id,username,display_name,role,password,email,state,faction_id,description,note,disabled,activated,activation_key,first_time_login,last_activity,registration,theme,tooltips_enabled,remember_token) VALUES(2,'Lisa','','player','$2y$10$tqJJsdnuAuhVcqtdqeby3.ytOSc2AupZs6LjST3GjiKytKBsuxp8m','lisa@nouron.de',NULL,1,'','',0,0,'abcdefg',1,'2011-09-18 13:39:26','0000-00-00 00:00:00','darkred',1,NULL);
INSERT INTO "user" (user_id,username,display_name,role,password,email,state,faction_id,description,note,disabled,activated,activation_key,first_time_login,last_activity,registration,theme,tooltips_enabled,remember_token) VALUES(3,'Bart','','admin','$2y$10$9MytO4OOq3Z4MWvNcT1UreUqsTSw6IYuWCQ3bTdkmqAwa5vUJr8wG','bart@nouron.de',NULL,4,'','',0,1,'',1,'2013-01-05 19:25:03','0000-00-00 00:00:00','darkgreen',1,NULL);
INSERT INTO "user" (user_id,username,display_name,role,password,email,state,faction_id,description,note,disabled,activated,activation_key,first_time_login,last_activity,registration,theme,tooltips_enabled,remember_token) VALUES(4,'Maggy','','player','$2y$10$tqJJsdnuAuhVcqtdqeby3.ytOSc2AupZs6LjST3GjiKytKBsuxp8m','maggy@nouron.de',NULL,2,'','',0,1,'abcdefg',0,'2012-12-27 11:46:30','0000-00-00 00:00:00','0',1,NULL);
INSERT INTO "user" (user_id,username,display_name,role,password,email,state,faction_id,description,note,disabled,activated,activation_key,first_time_login,last_activity,registration,theme,tooltips_enabled,remember_token) VALUES(5,'Moe','','player','$2y$10$tqJJsdnuAuhVcqtdqeby3.ytOSc2AupZs6LjST3GjiKytKBsuxp8m','moe@nouron.de',NULL,5,'','',0,1,'abcdefg',0,'2012-12-27 11:46:30','0000-00-00 00:00:00','0',1,NULL);
INSERT INTO "user" (user_id,username,display_name,role,password,email,state,faction_id,description,note,disabled,activated,activation_key,first_time_login,last_activity,registration,theme,tooltips_enabled,remember_token) VALUES(18,'Lenny','','player','$2y$10$tqJJsdnuAuhVcqtdqeby3.ytOSc2AupZs6LjST3GjiKytKBsuxp8m','lenny@nouron.de',NULL,0,'','',0,0,'',1,'2012-12-27 11:46:30','0000-00-00 00:00:00','darkred',1,NULL);
INSERT INTO "user" (user_id,username,display_name,role,password,email,state,faction_id,description,note,disabled,activated,activation_key,first_time_login,last_activity,registration,theme,tooltips_enabled,remember_token) VALUES(19,'Carl','','player','$2y$10$tqJJsdnuAuhVcqtdqeby3.ytOSc2AupZs6LjST3GjiKytKBsuxp8m','carl@nouron.de',NULL,1,'','',0,0,'',1,'2012-12-27 11:46:30','0000-00-00 00:00:00','darkred',1,NULL);
-- glx_colonies: id, name, user_id, since_tick, is_primary, hunger_streak
INSERT INTO "glx_colonies" (id,name,user_id,since_tick,is_primary,hunger_streak) VALUES(1,'Springfield',3,20582,1,0);
INSERT INTO "glx_colonies" (id,name,user_id,since_tick,is_primary,hunger_streak) VALUES(2,'Shelbyville',0,20585,1,0);
INSERT INTO "buildings" (id,purpose,name,required_building_id,required_building_level,prime_colony_only,"row","column",max_level,ap_for_levelup,max_status_points,decay_rate,supply_cost,is_instanced,is_active) VALUES(25,'civil','building_commandCenter',41,1,0,0,2,5,10,20,0.33,0,0,1);
INSERT INTO "buildings" (id,purpose,name,required_building_id,required_building_level,prime_colony_only,"row","column",max_level,ap_for_levelup,max_status_points,decay_rate,supply_cost,is_instanced,is_active) VALUES(27,'industry','building_harvester',25,1,0,1,2,1,10,20,0.95,2,1,1);
INSERT INTO "buildings" (id,purpose,name,required_building_id,required_building_level,prime_colony_only,"row","column",max_level,ap_for_levelup,max_status_points,decay_rate,supply_cost,is_instanced,is_active) VALUES(28,'civil','building_housingComplex',25,1,1,1,1,6,10,20,0.44,0,1,1);
INSERT INTO "buildings" (id,purpose,name,required_building_id,required_building_level,prime_colony_only,"row","column",max_level,ap_for_levelup,max_status_points,decay_rate,supply_cost,is_instanced,is_active) VALUES(31,'civil','building_sciencelab',25,2,0,2,2,NULL,10,20,0.95,8,0,1);
INSERT INTO "buildings" (id,purpose,name,required_building_id,required_building_level,prime_colony_only,"row","column",max_level,ap_for_levelup,max_status_points,decay_rate,supply_cost,is_instanced,is_active) VALUES(32,'civil','building_temple',25,4,0,4,2,NULL,10,20,2.0,4,0,1);
INSERT INTO "buildings" (id,purpose,name,required_building_id,required_building_level,prime_colony_only,"row","column",max_level,ap_for_levelup,max_status_points,decay_rate,supply_cost,is_instanced,is_active) VALUES(41,'industry','building_bioFacility',27,1,0,2,1,NULL,10,20,0.95,2,0,1);
INSERT INTO "buildings" (id,purpose,name,required_building_id,required_building_level,prime_colony_only,"row","column",max_level,ap_for_levelup,max_status_points,decay_rate,supply_cost,is_instanced,is_active) VALUES(44,'civil','building_hangar',25,2,0,3,3,NULL,10,20,0.67,6,1,1);
INSERT INTO "buildings" (id,purpose,name,required_building_id,required_building_level,prime_colony_only,"row","column",max_level,ap_for_levelup,max_status_points,decay_rate,supply_cost,is_instanced,is_active) VALUES(46,'civil','building_infirmary',25,3,0,3,2,NULL,10,20,2.0,10,0,1);
INSERT INTO "buildings" (id,purpose,name,required_building_id,required_building_level,prime_colony_only,"row","column",max_level,ap_for_levelup,max_status_points,decay_rate,supply_cost,is_instanced,is_active) VALUES(50,'civil','building_monument',25,5,0,6,2,NULL,20,20,0.33,2,0,1);
INSERT INTO "buildings" (id,purpose,name,required_building_id,required_building_level,prime_colony_only,"row","column",max_level,ap_for_levelup,max_status_points,decay_rate,supply_cost,is_instanced,is_active) VALUES(52,'civil','building_bar',28,1,0,2,3,NULL,10,20,1.0,4,0,1);
INSERT INTO "buildings" (id,purpose,name,required_building_id,required_building_level,prime_colony_only,"row","column",max_level,ap_for_levelup,max_status_points,decay_rate,supply_cost,is_instanced,is_active) VALUES(53,'civil','building_securityHub',25,2,0,4,1,3,10,20,0.67,8,0,1);
INSERT INTO "buildings" (id,purpose,name,required_building_id,required_building_level,prime_colony_only,"row","column",max_level,ap_for_levelup,max_status_points,decay_rate,supply_cost,is_instanced,is_active) VALUES(54,'civil','building_uplinkStation',25,2,0,4,2,3,10,20,0.67,6,0,1);
INSERT INTO "buildings" (id,purpose,name,required_building_id,required_building_level,prime_colony_only,"row","column",max_level,ap_for_levelup,max_status_points,decay_rate,supply_cost,is_instanced,is_active) VALUES(55,'civil','building_tradingPost',25,4,0,1,3,3,10,20,0.67,6,0,1);
-- Credits (1) + Supply (2): legacy base costs (not consumed by the hex build flow).
INSERT INTO "building_costs" VALUES(25,1,100);
INSERT INTO "building_costs" VALUES(25,2,15);
INSERT INTO "building_costs" VALUES(27,2,10);
INSERT INTO "building_costs" VALUES(28,1,100);
INSERT INTO "building_costs" VALUES(31,1,50);
INSERT INTO "building_costs" VALUES(31,2,5);
INSERT INTO "building_costs" VALUES(32,1,100);
INSERT INTO "building_costs" VALUES(32,2,10);
INSERT INTO "building_costs" VALUES(41,1,50);
INSERT INTO "building_costs" VALUES(41,2,10);
INSERT INTO "building_costs" VALUES(44,1,100);
INSERT INTO "building_costs" VALUES(44,2,10);
INSERT INTO "building_costs" VALUES(46,1,100);
INSERT INTO "building_costs" VALUES(46,2,10);
INSERT INTO "building_costs" VALUES(50,1,100);
INSERT INTO "building_costs" VALUES(50,2,10);
INSERT INTO "building_costs" VALUES(52,1,100);
INSERT INTO "building_costs" VALUES(52,2,10);
INSERT INTO "building_costs" VALUES(53,1,100);
INSERT INTO "building_costs" VALUES(54,1,100);
INSERT INTO "building_costs" VALUES(55,1,400);
-- Regolith (3) + Werkstoffe (4): construction cost (canonical: config/buildings.php build_cost).
-- CC (25) + Harvester (27) carry none (bootstrap). Organika is never a build cost.
INSERT INTO "building_costs" VALUES(28,3,40);
INSERT INTO "building_costs" VALUES(41,3,40);
INSERT INTO "building_costs" VALUES(52,3,50);
INSERT INTO "building_costs" VALUES(31,3,60);
INSERT INTO "building_costs" VALUES(31,4,20);
INSERT INTO "building_costs" VALUES(32,3,50);
INSERT INTO "building_costs" VALUES(32,4,15);
INSERT INTO "building_costs" VALUES(46,3,60);
INSERT INTO "building_costs" VALUES(46,4,25);
INSERT INTO "building_costs" VALUES(50,3,60);
INSERT INTO "building_costs" VALUES(50,4,25);
INSERT INTO "building_costs" VALUES(44,3,80);
INSERT INTO "building_costs" VALUES(44,4,25);
INSERT INTO "building_costs" VALUES(53,3,80);
INSERT INTO "building_costs" VALUES(53,4,25);
INSERT INTO "building_costs" VALUES(54,3,80);
INSERT INTO "building_costs" VALUES(55,3,100);
INSERT INTO "building_costs" VALUES(55,4,25);
INSERT INTO "researches" (id,purpose,name,required_building_id,required_building_level,required_building2_id,required_building2_level,"row","column",ap_for_levelup,max_status_points,decay_rate,supply_cost,is_active) VALUES(90,'knowledge','knowledge_construction',31,1,NULL,NULL,3,5,3,20,0,0,1);
INSERT INTO "researches" (id,purpose,name,required_building_id,required_building_level,required_building2_id,required_building2_level,"row","column",ap_for_levelup,max_status_points,decay_rate,supply_cost,is_active) VALUES(91,'knowledge','knowledge_cartography',31,1,44,1,7,4,3,20,0,0,1);
INSERT INTO "researches" (id,purpose,name,required_building_id,required_building_level,required_building2_id,required_building2_level,"row","column",ap_for_levelup,max_status_points,decay_rate,supply_cost,is_active) VALUES(92,'knowledge','knowledge_geology',31,2,27,1,5,4,3,20,0,0,1);
INSERT INTO "researches" (id,purpose,name,required_building_id,required_building_level,required_building2_id,required_building2_level,"row","column",ap_for_levelup,max_status_points,decay_rate,supply_cost,is_active) VALUES(93,'knowledge','knowledge_agronomy',31,1,41,1,3,4,3,20,0,0,1);
INSERT INTO "researches" (id,purpose,name,required_building_id,required_building_level,required_building2_id,required_building2_level,"row","column",ap_for_levelup,max_status_points,decay_rate,supply_cost,is_active) VALUES(94,'knowledge','knowledge_health',31,1,46,1,4,4,3,20,0,0,1);
INSERT INTO "researches" (id,purpose,name,required_building_id,required_building_level,required_building2_id,required_building2_level,"row","column",ap_for_levelup,max_status_points,decay_rate,supply_cost,is_active) VALUES(95,'knowledge','knowledge_trade',31,1,52,1,6,4,3,20,0,0,1);
INSERT INTO "researches" (id,purpose,name,required_building_id,required_building_level,required_building2_id,required_building2_level,"row","column",ap_for_levelup,max_status_points,decay_rate,supply_cost,is_active) VALUES(96,'knowledge','knowledge_defense',31,3,44,2,8,4,3,20,0,0,1);
INSERT INTO "researches" (id,purpose,name,required_building_id,required_building_level,required_building2_id,required_building2_level,"row","column",ap_for_levelup,max_status_points,decay_rate,supply_cost,is_active) VALUES(9901,'civil','test_decay_placeholder',NULL,NULL,NULL,NULL,99,99,1,20,0.13,NULL,0);
INSERT INTO "ships" VALUES(29,'military','techs_frigate1',NULL,NULL,NULL,NULL,0,10,5,15,10,500,NULL,NULL,0,0);
INSERT INTO "ships" VALUES(37,'military','ship_corvette',44,3,NULL,NULL,0,6,5,15,10,500,NULL,NULL,1,0);
INSERT INTO "ships" VALUES(47,'economy','ship_freighter',44,2,NULL,NULL,0,5,5,15,10,500,NULL,NULL,1,0);
INSERT INTO "ships" VALUES(49,'military','techs_battlecruiser1',NULL,NULL,NULL,NULL,0,12,5,15,10,500,NULL,NULL,0,0);
INSERT INTO "ships" VALUES(83,'economy','techs_mediumTransporter',NULL,NULL,NULL,NULL,0,9,4,15,10,500,NULL,NULL,0,0);
INSERT INTO "ships" VALUES(84,'economy','techs_largeTransporter',NULL,NULL,NULL,NULL,0,11,4,15,10,500,NULL,NULL,0,0);
INSERT INTO "ships" VALUES(85,'military','ship_drone',44,1,NULL,NULL,0,4,5,3,10,5,NULL,NULL,1,0);
-- Ships cost Credits only (owner rule: Schiffe nur Credits — no resource cost).
INSERT INTO "ship_costs" VALUES(85,1,500);
INSERT INTO "ship_costs" VALUES(37,1,5000);
INSERT INTO "ship_costs" VALUES(47,1,2000);
INSERT INTO "personell" VALUES(35,'industry','techs_engineer',25,1,2,0,10,1,0);
INSERT INTO "personell" VALUES(36,'civil','techs_scientist',31,1,3,0,10,1,0);
INSERT INTO "personell" VALUES(89,'military','techs_pilot',44,1,5,0,10,1,0);
INSERT INTO "personell" VALUES(92,'economy','techs_trader',52,1,4,0,10,1,0);
INSERT INTO "personell" VALUES(93,'military','techs_strategist',25,3,6,0,10,1,0);
INSERT INTO "personell_costs" VALUES(35,1,2500);
INSERT INTO "personell_costs" VALUES(36,1,5000);
INSERT INTO "personell_costs" VALUES(89,1,5000);
INSERT INTO "personell_costs" VALUES(92,1,2000);
INSERT INTO "personell_costs" VALUES(93,1,7500);
INSERT INTO "personell_costs" VALUES(35,2,2);
INSERT INTO "personell_costs" VALUES(36,2,2);
INSERT INTO "personell_costs" VALUES(89,2,2);
INSERT INTO "personell_costs" VALUES(92,2,2);
INSERT INTO "personell_costs" VALUES(93,2,2);
INSERT INTO "colony_buildings" (colony_id,building_id,level,status_points,ap_spend) VALUES(1,25,3,20,0);
INSERT INTO "colony_buildings" (colony_id,building_id,level,status_points,ap_spend) VALUES(1,27,1,20,0);
INSERT INTO "colony_buildings" (colony_id,building_id,level,status_points,ap_spend) VALUES(1,28,2,20,2);
INSERT INTO "colony_buildings" (colony_id,building_id,level,status_points,ap_spend) VALUES(1,31,1,10,0);
-- Infirmary (46) for colony 1: used as a generic "uncapped, upgradable building" stand-in
-- by BuildingServiceTest/ColonyZoneDecoupleTest/BuildResourceSinkTest (ex-depot, removed 2026-06-22).
INSERT INTO "colony_buildings" (colony_id,building_id,level,status_points,ap_spend) VALUES(1,46,3,10,10);
INSERT INTO "colony_buildings" (colony_id,building_id,level,status_points,ap_spend) VALUES(1,52,0,0,0);
INSERT INTO "colony_buildings" (colony_id,building_id,level,status_points,ap_spend) VALUES(2,25,5,10,1);
INSERT INTO "colony_buildings" (colony_id,building_id,level,status_points,ap_spend) VALUES(2,27,1,10,1);
INSERT INTO "colony_buildings" (colony_id,building_id,level,status_points,ap_spend) VALUES(2,28,4,10,1);
INSERT INTO "colony_buildings" (colony_id,building_id,level,status_points,ap_spend) VALUES(2,31,3,10,1);
INSERT INTO "colony_buildings" (colony_id,building_id,level,status_points,ap_spend) VALUES(2,32,1,10,1);
INSERT INTO "colony_buildings" (colony_id,building_id,level,status_points,ap_spend) VALUES(2,41,1,10,1);
INSERT INTO "colony_buildings" (colony_id,building_id,level,status_points,ap_spend) VALUES(2,44,2,10,1);
INSERT INTO "colony_buildings" (colony_id,building_id,level,status_points,ap_spend) VALUES(2,46,1,10,1);
INSERT INTO "colony_buildings" (colony_id,building_id,level,status_points,ap_spend) VALUES(2,50,1,10,1);
INSERT INTO "colony_buildings" (colony_id,building_id,level,status_points,ap_spend) VALUES(2,52,1,10,1);
INSERT INTO "colony_resources" VALUES(3,1,250);
INSERT INTO "colony_resources" VALUES(4,1,50);
INSERT INTO "colony_resources" VALUES(5,1,50);
INSERT INTO "colony_resources" VALUES(12,1,0);
-- colony_buildings: two hangar bays (building_id=44) for colony 1 (Springfield)
INSERT INTO "colony_buildings" (colony_id,building_id,instance_id,level,status_points,ap_spend) VALUES(1,44,1,1,20,0);
INSERT INTO "colony_buildings" (colony_id,building_id,instance_id,level,status_points,ap_spend) VALUES(1,44,2,1,20,0);

INSERT INTO "colony_ships" (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(1,29,0,10,1,NULL,'docked',NULL,NULL);
INSERT INTO "colony_ships" (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(1,37,9,10,1,NULL,'docked',NULL,NULL);
INSERT INTO "colony_ships" (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(1,47,3,10,0,NULL,'docked',NULL,NULL);
INSERT INTO "colony_ships" (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(1,49,12,10,1,NULL,'docked',NULL,NULL);
INSERT INTO "colony_ships" (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(1,83,17,10,1,NULL,'docked',NULL,NULL);
INSERT INTO "colony_ships" (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(1,84,16,10,1,NULL,'docked',NULL,NULL);
INSERT INTO "colony_ships" (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(1,85,5,3,0,NULL,'docked',NULL,NULL);
INSERT INTO "colony_ships" (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(2,29,19,10,1,NULL,'docked',NULL,NULL);
INSERT INTO "colony_ships" (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(2,37,19,10,1,NULL,'docked',NULL,NULL);
INSERT INTO "colony_ships" (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(2,47,19,10,1,NULL,'docked',NULL,NULL);
INSERT INTO "colony_ships" (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(2,49,19,10,1,NULL,'docked',NULL,NULL);
INSERT INTO "colony_ships" (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(2,83,19,10,1,NULL,'docked',NULL,NULL);
INSERT INTO "colony_ships" (colony_id,ship_id,level,status_points,ap_spend,hangar_instance_id,ship_state,deliver_at_tick,pending_until_tick) VALUES(2,84,19,10,1,NULL,'docked',NULL,NULL);

-- Assign hangar bays: corvette (ship_id=37) → hangar 1, freighter (ship_id=47) → hangar 2 on colony 1
-- Drone (ship_id=85) dispatched from hangar 1, so ship_state=dispatched
UPDATE "colony_ships" SET hangar_instance_id=1, ship_state='dispatched' WHERE colony_id=1 AND ship_id=85;
UPDATE "colony_ships" SET hangar_instance_id=1, ship_state='docked'     WHERE colony_id=1 AND ship_id=37;
UPDATE "colony_ships" SET hangar_instance_id=2, ship_state='docked'     WHERE colony_id=1 AND ship_id=47;

-- Hangar missions for colony 1:
-- Mission 1: drone dispatched from hangar 1, currently active
-- Mission 2: freighter recalled from hangar 2, completed
INSERT INTO "colony_hangar_missions" (colony_id,instance_id,ship_id,destination,sol_distance,dispatch_tick,recall_tick,state,created_at) VALUES(1,1,85,'Asteroid Belt Proxima',4,1,NULL,'active','2026-06-03 00:00:00');
INSERT INTO "colony_hangar_missions" (colony_id,instance_id,ship_id,destination,sol_distance,dispatch_tick,recall_tick,state,created_at) VALUES(1,2,47,'Raani Trading Post',8,1,3,'recalled','2026-06-03 00:00:00');
INSERT INTO "colony_personell" VALUES(1,35,9,10);
INSERT INTO "colony_personell" VALUES(1,36,2,10);
INSERT INTO "colony_personell" VALUES(1,89,0,10);
INSERT INTO "colony_personell" VALUES(1,92,17,10);
INSERT INTO "colony_personell" VALUES(2,35,19,10);
INSERT INTO "colony_personell" VALUES(2,36,19,10);
INSERT INTO "colony_personell" VALUES(2,89,19,10);
INSERT INTO "colony_personell" VALUES(2,92,19,10);
INSERT INTO "colony_researches" VALUES(1,9901,1,20,0);
INSERT INTO "colony_researches" VALUES(1,96,0,10,0);
INSERT INTO "advisors" (user_id,colony_id,personell_id,rank,active_ticks) VALUES(3,1,35,1,0);
INSERT INTO "colony_log" VALUES(16,3,15405,'techtree.level_up_finished','techtree','{"entity_type":"knowledge","entity_name":"knowledge_construction","new_level":1,"tech_id":90}',NULL,1);
INSERT INTO "colony_log" VALUES(26,3,1,'colony.building_placed','colony','{"building_id":25,"building_name":"building_commandCenter","colony_id":1}',NULL,1);
INSERT INTO "colony_log" VALUES(27,3,1,'colony.building_placed','colony','{"building_id":27,"building_name":"building_harvester","colony_id":1}',NULL,1);
INSERT INTO "colony_log" VALUES(28,3,2,'colony.building_invested','colony','{"building_id":25,"building_name":"building_commandCenter","ap_spend":1,"ap_for_levelup":5,"level_up":false,"new_level":1}',NULL,1);
INSERT INTO "colony_log" VALUES(29,3,2,'colony.building_invested','colony','{"building_id":25,"building_name":"building_commandCenter","ap_spend":1,"ap_for_levelup":5,"level_up":false,"new_level":1}',NULL,1);
INSERT INTO "colony_log" VALUES(30,3,2,'colony.building_invested','colony','{"building_id":28,"building_name":"building_housingComplex","ap_spend":1,"ap_for_levelup":3,"level_up":false,"new_level":1}',NULL,1);
INSERT INTO "colony_log" VALUES(31,3,2,'colony.tile_explored','colony','{"colony_id":1,"q":1,"r":-1}',NULL,1);
INSERT INTO "colony_log" VALUES(32,3,3,'colony.building_invested','colony','{"building_id":28,"building_name":"building_housingComplex","ap_spend":1,"ap_for_levelup":3,"level_up":true,"new_level":2}',NULL,1);
INSERT INTO "colony_log" VALUES(33,3,3,'techtree.advisor_hired','techtree','{"advisor_type":"scientist","colony_id":1,"credits_cost":400}',NULL,1);
INSERT INTO "colony_log" VALUES(34,3,3,'merchant.visit','merchant','{"colony_id":1}',NULL,1);
INSERT INTO "colony_log" VALUES(35,3,4,'techtree.level_down','techtree','{"entity_type":"building","entity_name":"building_harvester","new_level":0,"tech_id":27}',NULL,1);
INSERT INTO "colony_log" VALUES(36,3,4,'techtree.level_down','techtree','{"entity_type":"knowledge","entity_name":"knowledge_cartography","new_level":1,"tech_id":91}',NULL,1);
INSERT INTO "colony_log" VALUES(37,3,4,'trade.bar_accepted','trade','{"colony_id":1,"give_resource_id":3,"give_amount":80,"get_resource_id":1,"get_amount":200}',NULL,1);
INSERT INTO "colony_log" VALUES(38,3,5,'trade.merchant_purchase','trade','{"colony_id":1,"item_type":"ap_package","cost_credits":100}',NULL,1);
INSERT INTO "colony_log" VALUES(39,3,5,'colony.tile_deep_scanned','colony','{"colony_id":1,"q":2,"r":0}',NULL,1);
INSERT INTO "trade_resources" VALUES(2,0,3,11,11,0);
INSERT INTO "trade_resources" VALUES(2,1,5,123,32,0);
INSERT INTO "trade_resources" VALUES(2,0,4,45,45,0);
INSERT INTO "trade_resources" VALUES(1,0,5,4,3,0);
INSERT INTO "trade_resources" VALUES(1,0,3,100,50,0);

INSERT INTO "user_resources" VALUES(3,2700,18);

INSERT OR REPLACE INTO "user_preferences" VALUES(1,0,1,NULL,NULL,NULL,NULL,0);
INSERT OR REPLACE INTO "user_preferences" VALUES(2,1,1,NULL,NULL,NULL,NULL,0);

-- Phase-based techtree grid positions (migration 2026_05_10_000001 — layout v2)
-- Phase 1 (CC Lv1): housingComplex, harvester, bioFacility, engineer
UPDATE "buildings"  SET phase=1, "row"=1, "column"=1 WHERE id=28; -- housingComplex
UPDATE "buildings"  SET phase=1, "row"=1, "column"=2 WHERE id=27; -- harvester
UPDATE "buildings"  SET phase=1, "row"=2, "column"=2 WHERE id=41; -- bioFacility
UPDATE "personell"  SET phase=1, "row"=2, "column"=3 WHERE id=35; -- engineer
-- Phase 2 (CC Lv2): depot, sciencelab, infirmary, bar, scientist, trader,
--                   knowledge_construction, knowledge_agronomy, knowledge_health, knowledge_trade
UPDATE "buildings"  SET phase=2, "row"=1, "column"=1 WHERE id=30; -- depot
UPDATE "buildings"  SET phase=2, "row"=1, "column"=2 WHERE id=31; -- sciencelab
UPDATE "buildings"  SET phase=2, "row"=1, "column"=3 WHERE id=46; -- infirmary
UPDATE "buildings"  SET phase=2, "row"=2, "column"=1 WHERE id=52; -- bar
UPDATE "personell"  SET phase=2, "row"=2, "column"=3 WHERE id=36; -- scientist
UPDATE "personell"  SET phase=2, "row"=3, "column"=3 WHERE id=92; -- trader
UPDATE "researches" SET phase=2, "row"=4, "column"=3 WHERE id=90; -- knowledge_construction
UPDATE "researches" SET phase=2, "row"=5, "column"=3 WHERE id=93; -- knowledge_agronomy
UPDATE "researches" SET phase=2, "row"=6, "column"=1 WHERE id=94; -- knowledge_health
UPDATE "researches" SET phase=2, "row"=6, "column"=3 WHERE id=95; -- knowledge_trade
-- Phase 3 (CC Lv3): hangar, strategist, drone, pilot, knowledge_geology,
--                   freighter, knowledge_cartography, corvette, knowledge_defense
UPDATE "buildings"  SET phase=3, "row"=1, "column"=2 WHERE id=44; -- hangar
UPDATE "personell"  SET phase=3, "row"=1, "column"=3 WHERE id=93; -- strategist
UPDATE "ships"      SET phase=3, "row"=2, "column"=2 WHERE id=85; -- drone
UPDATE "personell"  SET phase=3, "row"=2, "column"=3 WHERE id=89; -- pilot
UPDATE "researches" SET phase=3, "row"=3, "column"=1 WHERE id=92; -- knowledge_geology
UPDATE "ships"      SET phase=3, "row"=3, "column"=2 WHERE id=47; -- freighter
UPDATE "researches" SET phase=3, "row"=3, "column"=3 WHERE id=91; -- knowledge_cartography
UPDATE "ships"      SET phase=3, "row"=4, "column"=2 WHERE id=37; -- corvette
UPDATE "researches" SET phase=3, "row"=4, "column"=3 WHERE id=96; -- knowledge_defense
-- Phase 4 (CC Lv4)
UPDATE "buildings"  SET phase=4, "row"=1, "column"=2 WHERE id=32; -- temple
-- Phase 5 (CC Lv5)
UPDATE "buildings"  SET phase=5, "row"=1, "column"=2 WHERE id=50; -- monument
INSERT OR REPLACE INTO "user_preferences" VALUES(3,3,1,NULL,NULL,NULL,NULL,0);

-- Bar offers (migration 2026_05_14_000003)
-- colony_id=1 (Springfield), expires_tick=9999999 (far future, always valid in tests)
-- Offer 1: pay 800 Credits, receive 50 Compounds (Werkstoffe)
INSERT INTO "bar_offers" (colony_id,give_resource_id,give_amount,get_resource_id,get_amount,expires_tick,is_accepted,created_at,updated_at) VALUES(1,1,800,4,50,9999999,0,'2026-05-14 00:00:00','2026-05-14 00:00:00');
-- Offer 2: pay 20 Regolith, receive 30 Organics (Organika)
INSERT INTO "bar_offers" (colony_id,give_resource_id,give_amount,get_resource_id,get_amount,expires_tick,is_accepted,created_at,updated_at) VALUES(1,3,20,5,30,9999999,0,'2026-05-14 00:00:00','2026-05-14 00:00:00');

-- Active run for Bart (user_id=3) on Springfield (colony_id=1).
INSERT OR REPLACE INTO "runs" (id,user_id,colony_id,current_tick,status,started_at,ended_at,settings,phase,fail_reason,nexus_debt,phase2_start_tick,created_at,updated_at) VALUES(1,3,1,5,'active','2026-05-23 00:00:00',NULL,'{"tick_limit":100,"bypass":{"ap_checks":false,"resource_costs":false,"supply_checks":false},"supply_cap_max":200,"max_players":1}',1,NULL,3000,NULL,'2026-05-23 00:00:00','2026-05-23 00:00:00');
-- Active run for Homer (user_id=0) on Shelbyville (colony_id=2).
INSERT OR REPLACE INTO "runs" (id,user_id,colony_id,current_tick,status,started_at,ended_at,settings,phase,fail_reason,nexus_debt,phase2_start_tick,created_at,updated_at) VALUES(2,0,2,20,'active','2026-05-23 00:00:00',NULL,'{"tick_limit":100,"bypass":{"ap_checks":false,"resource_costs":false,"supply_checks":false},"supply_cap_max":200,"max_players":1}',1,NULL,3000,NULL,'2026-05-23 00:00:00','2026-05-23 00:00:00');
