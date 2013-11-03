SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

INSERT INTO `buildings` (`id`, `purpose`, `name`, `required_building_id`, `required_building_level`, `prime_colony_only`, `row`, `column`, `max_level`, `ap_for_levelup`, `max_status_points`) VALUES
(25, 'civil', 'techs_commandCenter', NULL, NULL, 0, 0, 2, 10, 10, 20),
(27, 'industry', 'techs_oremine', 25, 1, 0, 1, 2, NULL, 10, 10),
(28, 'civil', 'techs_housingComplex', 25, 3, 1, 1, 1, 200, 10, 10),
(30, 'industry', 'techs_depot', 27, 3, 0, 2, 5, NULL, 10, 10),
(31, 'civil', 'techs_sciencelab', 25, 4, 0, 2, 2, NULL, 10, 10),
(32, 'civil', 'techs_temple', 28, 30, 0, 13, 0, NULL, 10, 10),
(41, 'industry', 'techs_silicatemine', 25, 1, 0, 1, 3, NULL, 10, 10),
(42, 'industry', 'techs_waterextractor', 25, 1, 0, 1, 4, NULL, 10, 10),
(43, 'economy', 'techs_tradecenter', 25, 5, 0, 6, 2, NULL, 10, 10),
(44, 'civil', 'techs_civilianSpaceyard', NULL, NULL, 0, 6, 3, NULL, 10, 10),
(45, 'civil', 'techs_parc', 28, 15, 0, 5, 0, NULL, 10, 10),
(46, 'civil', 'techs_hospital', 28, 15, 0, 4, 1, NULL, 10, 10),
(48, 'civil', 'techs_public_security', 28, 7, 0, 3, 0, NULL, 10, 10),
(50, 'civil', 'techs_denkmal', 28, 20, 0, 9, 0, NULL, 20, 10),
(51, 'civil', 'techs_university', 31, 4, 0, 6, 1, NULL, 10, 10),
(52, 'civil', 'techs_bar', NULL, NULL, 0, 4, 3, NULL, 10, 10),
(53, 'civil', 'techs_stadium', 28, 30, 0, 15, 0, NULL, 10, 10),
(54, 'civil', 'techs_casino', 28, 30, 0, 11, 0, NULL, 10, 10),
(55, 'civil', 'techs_prison', NULL, NULL, 0, 11, 2, NULL, 10, 10),
(56, 'civil', 'techs_museum', 28, 20, 0, 7, 0, NULL, 10, 10),
(64, 'civil', 'techs_wastedisposal', 27, 20, 0, 3, 4, NULL, 10, 10),
(65, 'civil', 'techs_recyclingStation', 64, 3, 0, 4, 4, NULL, 10, 10),
(66, 'military', 'techs_secretOps', NULL, NULL, 0, 11, 1, NULL, 10, 10),
(68, 'military', 'techs_militarySpaceyard', 44, 5, 0, 7, 5, NULL, 10, 10),
(70, 'economy', 'techs_bank', NULL, NULL, 0, 11, 3, NULL, 10, 10);

INSERT INTO `building_costs` (`building_id`, `resource_id`, `amount`) VALUES
(25, 1, 100),
(25, 2, 15),
(25, 3, 25),
(25, 4, 20),
(27, 2, 10),
(27, 3, 10),
(27, 4, 1),
(27, 5, 10),
(28, 1, 100),
(28, 3, 25),
(28, 4, 25),
(28, 5, 25),
(30, 2, 5),
(30, 3, 5),
(30, 4, 5),
(31, 1, 50),
(31, 2, 5),
(31, 3, 5),
(31, 4, 5),
(31, 5, 5),
(32, 1, 100),
(32, 2, 10),
(41, 1, 50),
(41, 2, 10),
(41, 3, 25),
(41, 4, 25),
(42, 1, 50),
(42, 2, 10),
(42, 4, 25),
(42, 5, 25),
(43, 1, 100),
(43, 2, 10),
(44, 1, 100),
(44, 2, 10),
(45, 1, 100),
(45, 2, 10),
(46, 1, 100),
(46, 2, 10),
(48, 1, 100),
(48, 2, 10),
(50, 1, 100),
(50, 2, 10),
(51, 1, 100),
(51, 2, 10),
(52, 1, 100),
(52, 2, 10),
(53, 1, 100),
(53, 2, 10),
(54, 1, 100),
(54, 2, 10),
(55, 1, 100),
(55, 2, 10),
(56, 1, 100),
(56, 2, 10),
(64, 1, 100),
(64, 2, 10),
(65, 1, 100),
(65, 2, 10),
(66, 1, 100),
(66, 2, 10),
(68, 1, 100),
(68, 2, 10),
(70, 1, 100);

INSERT INTO `colony_buildings` (`colony_id`, `building_id`, `level`, `status_points`, `ap_spend`) VALUES
(1, 25, 10, 16, 1),
(1, 27, 5, 11, 3),
(1, 28, 2, 10, 0),
(1, 30, 19, 10, 10),
(1, 31, 19, 11, 1),
(1, 32, 19, 10, 1),
(1, 41, 8, 10, 4),
(1, 42, 1, 7, 5),
(1, 44, 11, 1, 0),
(1, 46, 18, 9, 1),
(1, 50, 19, 9, 9),
(1, 52, 1, 10, 0),
(1, 65, 0, 11, 4),
(1, 68, 0, 10, 10),
(2, 25, 19, 10, 1),
(2, 27, 19, 10, 1),
(2, 28, 19, 10, 1),
(2, 30, 19, 10, 1),
(2, 31, 19, 10, 1),
(2, 32, 19, 10, 1),
(2, 41, 19, 10, 1),
(2, 42, 19, 10, 1),
(2, 43, 19, 10, 1),
(2, 44, 19, 10, 1),
(2, 45, 19, 10, 1),
(2, 46, 19, 10, 1),
(2, 50, 19, 10, 1),
(2, 52, 19, 10, 1),
(2, 53, 19, 10, 1),
(2, 54, 19, 10, 1),
(2, 55, 19, 10, 1),
(2, 56, 19, 10, 1),
(2, 64, 19, 10, 1),
(2, 65, 19, 10, 1),
(2, 66, 19, 10, 1),
(2, 68, 19, 10, 1),
(2, 70, 19, 10, 1);

INSERT INTO `colony_personell` (`colony_id`, `personell_id`, `level`, `status_points`) VALUES
(1, 35, 9, 10),
(1, 36, 2, 10),
(1, 89, 0, 10),
(1, 92, 17, 10),
(2, 35, 19, 10),
(2, 36, 19, 10),
(2, 89, 19, 10),
(2, 92, 19, 10);

INSERT INTO `colony_researches` (`colony_id`, `research_id`, `level`, `status_points`, `ap_spend`) VALUES
(1, 33, 8, 20, 27),
(1, 34, 9, 0, 1),
(1, 39, 19, 10, 0),
(1, 72, 17, 11, 1),
(1, 73, 0, 13, 7),
(1, 74, 17, 11, 1),
(1, 76, 17, 10, 1),
(1, 79, 17, 10, 1),
(1, 81, 16, 10, 1),
(2, 33, 19, 10, 1),
(2, 34, 19, 10, 1),
(2, 39, 19, 10, 1),
(2, 72, 19, 10, 1),
(2, 73, 19, 10, 1),
(2, 74, 19, 10, 1),
(2, 76, 19, 10, 1),
(2, 79, 19, 10, 1),
(2, 80, 19, 10, 1),
(2, 81, 19, 10, 1);

INSERT INTO `colony_resources` (`resource_id`, `colony_id`, `amount`) VALUES
(3, 1, 4205),
(4, 1, 19018),
(5, 1, 6345),
(6, 1, 9500),
(8, 1, 9500),
(10, 1, 9500),
(12, 1, 9500);

INSERT INTO `colony_ships` (`colony_id`, `ship_id`, `level`, `status_points`, `ap_spend`) VALUES
(1, 29, 0, 10, 1),
(1, 37, 12, 10, 1),
(1, 49, 13, 10, 1),
(1, 83, 17, 10, 1),
(1, 84, 16, 10, 1),
(1, 88, 16, 10, 1),
(2, 29, 19, 10, 1),
(2, 37, 19, 10, 1),
(2, 47, 19, 10, 1),
(2, 49, 19, 10, 1),
(2, 83, 19, 10, 1),
(2, 84, 19, 10, 1),
(2, 88, 19, 10, 1);

INSERT INTO `glx_colonies` (`id`, `name`, `system_object_id`, `spot`, `user_id`, `since_tick`, `is_primary`) VALUES
(1, 'Springfield', 1, 1, 3, 0, 1),
(2, 'Shelbyville', 1, 2, 0, 0, 1);

INSERT INTO `glx_fleetorders` (`tick`, `fleet_id`, `order`, `coordinates`, `data`, `was_processed`, `has_notified`) VALUES
(14988, 16, 'move', '[6800,3000,0]', NULL, 0, 0),
(14989, 16, 'trade', '[6808,3006,1]', '{"colony":1,"direction":1,"resource_id":3,"amount":9,"price":255,"restriction":0,"colony":"Brasows Kolonie","user_id":0,"resource":"res_water","icon":"res res_water","is_tradeable":1}', 0, 0),
(14989, 17, 'move', '[2000,2000,1]', NULL, 0, 0),
(14990, 17, 'move', '[2500,2105,0]', NULL, 0, 0),
(14991, 17, 'move', '[3000,2210,0]', NULL, 0, 0),
(14992, 17, 'move', '[3500,2316,0]', NULL, 0, 0),
(14993, 17, 'move', '[4000,2421,0]', NULL, 0, 0),
(14994, 17, 'move', '[4500,2526,0]', NULL, 0, 0),
(14995, 17, 'move', '[5000,2631,0]', NULL, 0, 0),
(14996, 17, 'move', '[5500,2737,0]', NULL, 0, 0),
(14997, 17, 'move', '[6000,2842,0]', NULL, 0, 0),
(14998, 17, 'move', '[6500,2947,0]', NULL, 0, 0),
(14999, 17, 'move', '[6828,3016,1]', NULL, 0, 0),
(15219, 14, 'move', '[5828,4016,1]', NULL, 0, 0),
(15220, 14, 'move', '[5928,3916,0]', NULL, 0, 0),
(15221, 14, 'move', '[6028,3816,0]', NULL, 0, 0),
(15222, 14, 'move', '[6128,3716,0]', NULL, 0, 0),
(15223, 14, 'move', '[6228,3616,0]', NULL, 0, 0),
(15224, 14, 'move', '[6328,3516,0]', NULL, 0, 0),
(15225, 14, 'move', '[6428,3416,0]', NULL, 0, 0),
(15226, 14, 'move', '[6528,3316,0]', NULL, 0, 0),
(15227, 14, 'move', '[6628,3216,0]', NULL, 0, 0),
(15228, 14, 'move', '[6728,3116,0]', NULL, 0, 0),
(15229, 14, 'move', '[6828,3016,2]', NULL, 0, 0),
(15404, 10, 'trade', '[6828,3016,1]', '{"colony_id":1,"direction":1,"resource_id":3,"amount":9,"price":255,"restriction":0,"colony":"Brasows Kolonie","user_id":0,"resource":"res_water","icon":"res res_water","is_tradeable":1}', 1, 1),
(15760, 10, 'move', '[6828,3016,0]', NULL, 0, 0),
(15761, 10, 'move', '[6828,3015,0]', NULL, 0, 0),
(15762, 10, 'move', '[6829,3014,0]', NULL, 0, 0),
(15763, 10, 'move', '[6829,3013,0]', NULL, 0, 0),
(15764, 10, 'move', '[6829,3012,0]', NULL, 0, 0),
(15765, 10, 'move', '[6830,3011,0]', NULL, 0, 0),
(15766, 10, 'move', '[6830,3010,0]', NULL, 0, 0),
(15767, 10, 'move', '[6831,3009,0]', NULL, 0, 0),
(15768, 10, 'move', '[6831,3008,0]', NULL, 0, 0),
(15769, 10, 'move', '[6831,3007,0]', NULL, 0, 0),
(15770, 10, 'move', '[6832,3006,0]', NULL, 0, 0),
(15771, 10, 'move', '[6832,3005,0]', NULL, 0, 0),
(15772, 10, 'move', '[6832,3004,0]', NULL, 0, 0),
(15773, 10, 'move', '[6833,3003,0]', NULL, 0, 0),
(15774, 10, 'move', '[6833,3002,0]', NULL, 0, 0),
(15775, 10, 'move', '[6834,3001,0]', NULL, 0, 0),
(15776, 10, 'move', '[6834,3000,0]', NULL, 0, 0),
(15777, 10, 'move', '[6834,2999,0]', NULL, 0, 0),
(15778, 10, 'move', '[6835,2998,0]', NULL, 0, 0),
(15779, 10, 'move', '[6835,2997,0]', 'a:0:{}', 0, 0);

INSERT INTO `glx_fleetresources` (`fleet_id`, `resource_id`, `amount`) VALUES
(8, 3, 0),
(8, 5, 0),
(8, 6, 0),
(8, 8, 790),
(10, 3, 10),
(11, 3, 10),
(11, 4, 1000),
(17, 3, 10),
(17, 4, 588),
(17, 5, 1);

INSERT INTO `glx_fleets` (`id`, `fleet`, `user_id`, `artefact`, `x`, `y`, `spot`) VALUES
(8, 'B. Flotte 1', 18, NULL, 6828, 3016, 0),
(9, 'B. Flotte 2', 18, NULL, 6818, 3006, 0),
(10, 'Test Flotte 1', 3, NULL, 6828, 3016, 0),
(11, 'Test Flotte 2', 3, NULL, 6827, 3014, 0),
(12, 'Test Flotte 3', 3, NULL, 56828, 316, 0),
(13, 'Test Flotte 4', 3, NULL, 628, 6916, 0),
(14, 'Test Flotte 5', 3, NULL, 5828, 4016, 0),
(15, 'test', 0, NULL, 6828, 3016, 0),
(16, 'Test Flotte 6', 3, NULL, 6828, 3016, 0),
(17, 'Test Flotte 7', 3, NULL, 6828, 3016, 0),
(18, 'erszfgh', 0, NULL, 0, 0, 0),
(19, 'erszfgh', 0, NULL, 0, 0, 0);

INSERT INTO `glx_systems` (`id`, `x`, `y`, `name`, `type_id`, `background_image_url`, `sight`, `density`, `radiation`) VALUES
(1, 6800, 3000, 'test', 1, 'starfields/starfield1.png', 9, 0, 0),
(2, 2400, 2400, 'Raani', 5, 'starfields/starfield_blue.jpg', 9, 0, 0),
(4, 6600, 4400, 'Adoran', 2, 'starfields/starfield_magenta.jpg', 9, 0, 0),
(5, 3400, 5800, 'Heely', 3, 'starfields/starfield_green.jpg', 9, 0, 0),
(6, 8000, 2400, 'Desgod', 4, 'starfields/starfield_yellow.jpg', 9, 0, 0),
(8, 6600, 800, 'Suyegar', 6, 'starfields/starfield_yellow.jpg', 9, 0, 0),
(11, 6200, 2600, 'Welfort', 3, 'starfields/starfield_yellow.jpg', 9, 0, 0),
(12, 1200, 4800, 'Dilur', 4, 'starfields/starfield_yellow.jpg', 9, 0, 0),
(14, 3000, 1800, 'Syird', 2, 'starfields/starfield_yellow.jpg', 9, 0, 0),
(15, 7400, 8000, 'Endar', 9, '', 9, 0, 0),
(16, 6800, 6800, 'Tesva', 1, 'starfields/starfield_yellow.jpg', 9, 0, 0),
(17, 9200, 8000, 'Blackhole 1', 13, '', 9, 0, 0),
(18, 5000, 5200, 'Nouron', 9, 'starfields/starfield_yellow.jpg', 9, 0, 0);

INSERT INTO `glx_system_objects` (`id`, `x`, `y`, `name`, `type_id`, `sight`, `density`, `radiation`) VALUES
(1, 6828, 3016, 'test', 1, 9, 0, 0),
(2, 6801, 2998, 'test2', 2, 9, 0, 0),
(3, 6572, 4361, 'test3', 3, 9, 0, 0),
(4, 6820, 3020, 'Asteroiden', 9, 9, 0, 0),
(5, 6800, 2950, 'test 4', 7, 9, 0, 0),
(10, 6805, 2966, 'test7', 5, 9, 0, 0),
(11, 6788, 3034, 'test8', 8, 9, 0, 0),
(12, 9190, 7790, 'Blackhole', 13, 0, 9, 0),
(13, 6820, 3021, 'Asteroiden', 9, 9, 0, 0),
(14, 6821, 3021, 'Asteroiden', 9, 9, 0, 0),
(15, 6821, 3022, 'Asteroiden', 9, 9, 0, 0),
(16, 6822, 3022, 'Asteroiden', 9, 9, 0, 0),
(17, 6822, 3023, 'Asteroiden', 9, 9, 0, 0),
(18, 6750, 2950, 'TESTTESTTEST', 2, 9, 0, 0);

INSERT INTO `glx_system_object_types` (`id`, `type`, `image_url`) VALUES
(1, 'planetary_type_jungle', 'planetaries/1.png'),
(2, 'planetary_type_ice', 'planetaries/2.png'),
(3, 'planetary_type_desert', 'planetaries/3.png'),
(4, 'planetary_type_earth', 'planetaries/4.png'),
(5, 'planetary_type_crater', 'planetaries/5.png'),
(6, 'planetary_type_vulcano', 'planetaries/7.png'),
(7, 'planetary_type_gasgiant', 'planetaries/11.png'),
(8, 'planetary_type_water', 'planetaries/8.png'),
(9, 'planetary_type_asteroidfield', 'galaxy/field_asteroids.png'),
(10, 'planetary_type_minefield', 'planetaries/10.png'),
(11, 'planetary_type_shipgraveyard', 'planetaries/p_grey.png'),
(12, 'planetary_type_rockgiant', 'planetaries/p_grey.png'),
(13, 'planetary_type_icegiant', 'planetaries/p_white.png');

INSERT INTO `glx_system_types` (`id`, `class`, `size`, `icon_url`, `image_url`) VALUES
(1, 'stellar_class_A', 8, 'stellars/s_white.png', 'stellars/s_white_quarter.png'),
(2, 'stellar_class_O', 12, 'stellars/s_blue.png', 'stellars/s_blue_quarter.png'),
(3, 'stellar_class_B', 11, 'stellars/s_cyan.png', 'stellars/s_cyan_quarter.png'),
(4, 'stellar_class_F', 9, 'stellars/s_green.png', 'stellars/s_green_quarter.png'),
(5, 'stellar_class_G', 8, 'stellars/s_yellow.png', 'stellars/s_yellow_quarter.png'),
(6, 'stellar_class_K', 12, 'stellars/s_orange.png', 'stellars/s_red_quarter.png'),
(7, 'stellar_class_M', 8, 'stellars/s_red.png', 'stellars/s_red_quarter.png'),
(8, 'stellar_double', 9, 'stellars/s_double.png', ''),
(9, 'stellar_neutron', 10, 'stellars/s_pulsar.png', ''),
(10, 'stellar_novae', 9, 'stellars/novae.png', ''),
(11, 'stellar_supernovae', 11, 'stellars/supernovae.png', ''),
(12, 'stellar_nebula', 11, 'stellars/nebula.png', ''),
(13, 'stellar_black_hole', 10, 'stellars/s_blackhole.png', '');

INSERT INTO `innn_events` (`id`, `user`, `tick`, `event`, `area`, `parameters`) VALUES
(16, 3, 15405, 'techtree.level_up_finished', '', 'a:2:{s:7:"colony_id";i:0;s:11:"tech_id";i:27;}'),
(19, 3, 15405, 'galaxy.trade', '', 'a:1:{s:7:"colony_id";i:1;}');

INSERT INTO `innn_messages` (`id`, `sender_id`, `attitude`, `recipient_id`, `tick`, `type`, `subject`, `text`, `isRead`, `isArchived`, `isDeleted`) VALUES
(22, 3, 'mood_factual', 0, 15836, 0, 'Nachricht von Bart an Homer', 'test test Testnachricht1 test test Testnachricht1 test test Testnachricht1 test test Testnachricht1 test test Testnachricht1 test test Testnachricht1 test test Testnachricht1 test test Testnachricht1 test test Testnachricht1 test test Testnachricht1 test test Testnachricht1', 0, 0, 0),
(23, 3, 'mood_friendly', 0, 15865, 0, '2134567', 'dfghjk', 0, 0, 0);

INSERT INTO `innn_message_types` (`id`, `type`, `relationship_effect`, `points`) VALUES
(0, 'subject_none', 0, 1),
(1, 'subject_information', 1, 1),
(2, 'subject_instruction', -1, 1),
(3, 'subject_threat', -15, 1),
(4, 'subject_praise', 3, 1),
(6, 'subject_gift', 8, 1),
(7, 'subject_trade_off', 5, 1),
(8, 'subject_trade', 5, 1),
(9, 'subject_comm_treaty', 5, 1),
(10, 'subject_pact', 39, 1),
(11, 'subject_peace_treaty', 25, 1),
(12, 'subject_declaration_of_war', -49, 1),
(13, 'subject_nap', 10, 1);

INSERT INTO `innn_news` (`id`, `tick`, `icon`, `topic`, `headline`, `text`) VALUES
(1, 12345, 'innn_black', 'economy', 'test_headline', 'test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text test_text ');

INSERT INTO `locked_actionpoints` (`tick`, `colony_id`, `personell_id`, `spend_ap`) VALUES
(15930, 1, 35, 22),
(15930, 1, 36, 3),
(15934, 1, 35, 20),
(15935, 1, 35, 1),
(15942, 1, 35, 26),
(15947, 1, 35, 8),
(15962, 1, 35, 3),
(15983, 1, 35, 5),
(15990, 1, 35, 3),
(15990, 1, 36, 5),
(15991, 1, 36, 1),
(15997, 1, 35, 37),
(15997, 1, 36, 0),
(15998, 1, 35, 1),
(16005, 1, 35, 9);

INSERT INTO `personell` (`id`, `purpose`, `name`, `required_building_id`, `required_building_level`, `row`, `column`, `max_status_points`) VALUES
(35, 'industry', 'techs_engineer', 25, 5, 1, 0, 10),
(36, 'civil', 'techs_scientist', 31, 1, 3, 1, 10),
(89, 'military', 'techs_pilot', 44, 1, 7, 3, 10),
(92, 'economy', 'techs_trader', 43, 1, 7, 2, 10);

INSERT INTO `personell_costs` (`personell_id`, `resource_id`, `amount`) VALUES
(35, 1, 2500),
(36, 1, 5000),
(89, 1, 5000),
(92, 1, 2000),
(35, 2, 2),
(36, 2, 2),
(89, 2, 2),
(92, 2, 2);

INSERT INTO `researches` (`id`, `purpose`, `name`, `required_building_id`, `required_building_level`, `row`, `column`, `ap_for_levelup`, `max_status_points`) VALUES
(33, 'civil', 'techs_biology', 31, 1, 3, 3, 25, 10),
(34, 'politics', 'techs_languages', 51, 1, 8, 1, 25, 10),
(39, 'civil', 'techs_mathematics', 31, 4, 5, 5, 25, 10),
(72, 'civil', 'techs_medicalScience', 46, 1, 5, 2, 25, 10),
(73, 'civil', 'techs_physics', 31, 3, 5, 3, 25, 10),
(74, 'civil', 'techs_chemistry', 31, 2, 3, 2, 25, 10),
(76, 'economy', 'techs_economicScience', 43, 3, 10, 3, 25, 10),
(79, 'politics', 'techs_diplomacy', NULL, NULL, 10, 1, 25, 10),
(80, 'politics', 'techs_politicalScience', NULL, NULL, 10, 2, 25, 10),
(81, 'military', 'techs_military', 68, 1, 9, 5, 25, 10);

INSERT INTO `research_costs` (`research_id`, `resource_id`, `amount`) VALUES
(33, 1, 5000),
(33, 2, 5000),
(33, 3, 5000),
(33, 4, 5000),
(33, 5, 5000),
(33, 6, 5000),
(33, 8, 5000),
(33, 10, 5000),
(33, 12, 5000),
(39, 1, 5000);

INSERT INTO `resources` (`id`, `name`, `abbreviation`, `trigger`, `is_tradeable`, `start_amount`, `icon`) VALUES
(1, 'res_credits', 'Cr', 'Event', 0, 3000, 'resicon-credits'),
(2, 'res_supply', 'Sup', 'Event', 0, 200, 'resicon-supply'),
(3, 'res_water', 'W', 'Level', 1, 500, 'resicon-water'),
(4, 'res_ferum', 'E', 'Level', 1, 500, 'resicon-iron'),
(5, 'res_silicates', 'S', 'Level', 1, 500, 'resicon-silicates'),
(6, 'res_ena', 'ENrg', 'Constant', 1, 100, 'resicon-ena'),
(8, 'res_lho', 'LNrg', 'Constant', 1, 100, 'resicon-lho'),
(10, 'res_aku', 'ANrg', 'Constant', 1, 100, 'resicon-aku'),
(12, 'res_moral', 'M', 'Event', 0, 0, 'resicon-moral');

INSERT INTO `ships` (`id`, `purpose`, `name`, `required_building_id`, `required_building_level`, `required_research_id`, `required_research_level`, `prime_colony_only`, `row`, `column`, `ap_for_levelup`, `max_status_points`, `moving_speed`, `cargo_personell`, `cargo_resources`, `cargo_researches`) VALUES
(29, 'military', 'techs_frigate1', NULL, NULL, NULL, NULL, 0, 10, 5, 15, 10, 500, 0, 0, 0),
(37, 'military', 'techs_fighter1', NULL, NULL, NULL, NULL, 0, 8, 5, 15, 10, 500, 0, 0, 0),
(47, 'economy', 'techs_smallTransporter', NULL, NULL, NULL, NULL, 0, 7, 4, 15, 10, 500, 0, 0, 0),
(49, 'military', 'techs_battlecruiser1', NULL, NULL, NULL, NULL, 0, 12, 5, 15, 10, 500, 0, 0, 0),
(83, 'economy', 'techs_mediumTransporter', NULL, NULL, NULL, NULL, 0, 9, 4, 15, 10, 500, 0, 0, 0),
(84, 'economy', 'techs_largeTransporter', NULL, NULL, NULL, NULL, 0, 11, 4, 15, 10, 500, 0, 0, 0),
(88, 'civil', 'techs_colonyShip', NULL, NULL, NULL, NULL, 0, 13, 4, 50, 10, 500, 0, 0, 0);

INSERT INTO `tech_requirements` (`required_tech_id`, `tech_id`, `required_tech_level`) VALUES
(25, 27, 1),
(25, 28, 3),
(25, 31, 4),
(25, 35, 5),
(25, 41, 1),
(25, 42, 1),
(25, 43, 5),
(27, 30, 3),
(27, 31, 5),
(27, 64, 20),
(28, 32, 30),
(28, 45, 15),
(28, 46, 15),
(28, 48, 7),
(28, 50, 20),
(28, 53, 30),
(28, 54, 30),
(28, 56, 20),
(31, 33, 1),
(31, 36, 1),
(31, 39, 4),
(31, 51, 4),
(31, 73, 3),
(31, 74, 2),
(33, 52, 1),
(33, 65, 5),
(33, 72, 3),
(34, 79, 5),
(34, 80, 2),
(39, 44, 1),
(39, 68, 5),
(41, 30, 3),
(41, 64, 25),
(42, 30, 3),
(43, 76, 3),
(43, 92, 1),
(44, 47, 1),
(44, 68, 5),
(44, 83, 5),
(44, 84, 20),
(44, 88, 10),
(44, 89, 1),
(46, 72, 1),
(51, 34, 1),
(64, 65, 3),
(68, 29, 5),
(68, 37, 1),
(68, 49, 15),
(68, 81, 1),
(73, 44, 1),
(73, 68, 5),
(74, 52, 1),
(74, 65, 5),
(74, 72, 3),
(76, 70, 1),
(79, 66, 5),
(80, 55, 3),
(81, 29, 5),
(81, 49, 10);

INSERT INTO `tech_technologies` (`id`, `type`, `purpose`, `name`, `prime_colony_only`, `row`, `column`, `max_level`, `ap_for_levelup`, `max_status_points`, `tradeable`, `moving_speed`) VALUES
(25, 'building', 'civil', 'techs_commandCenter', 0, 0, 2, 10, 10, 20, 0, NULL),
(27, 'building', 'industry', 'techs_oremine', 0, 1, 2, NULL, 10, 10, 0, NULL),
(28, 'building', 'civil', 'techs_housingComplex', 1, 1, 1, 200, 10, 10, 0, NULL),
(29, 'ship', 'military', 'techs_frigate1', 0, 10, 5, NULL, 15, 10, 1, 500),
(30, 'building', 'industry', 'techs_depot', 0, 2, 5, NULL, 10, 10, 0, NULL),
(31, 'building', 'civil', 'techs_sciencelab', 0, 2, 2, NULL, 10, 10, 0, NULL),
(32, 'building', 'civil', 'techs_temple', 0, 13, 0, NULL, 10, 10, 0, NULL),
(33, 'research', 'civil', 'techs_biology', 1, 3, 3, NULL, 25, 10, 1, NULL),
(34, 'research', 'politics', 'techs_languages', 1, 8, 1, NULL, 25, 10, 1, NULL),
(35, 'advisor', 'industry', 'techs_engineer', 1, 1, 0, NULL, 0, 10, 1, NULL),
(36, 'advisor', 'civil', 'techs_scientist', 1, 3, 1, NULL, 0, 10, 1, NULL),
(37, 'ship', 'military', 'techs_fighter1', 0, 8, 5, NULL, 15, 10, 1, 500),
(39, 'research', 'civil', 'techs_mathematics', 1, 5, 5, NULL, 25, 10, 1, NULL),
(41, 'building', 'industry', 'techs_silicatemine', 0, 1, 3, NULL, 10, 10, 0, NULL),
(42, 'building', 'industry', 'techs_waterextractor', 0, 1, 4, NULL, 10, 10, 0, NULL),
(43, 'building', 'economy', 'techs_tradecenter', 0, 6, 2, NULL, 10, 10, 0, NULL),
(44, 'building', 'civil', 'techs_civilianSpaceyard', 0, 6, 3, NULL, 10, 10, 0, NULL),
(45, 'building', 'civil', 'techs_parc', 0, 5, 0, NULL, 10, 10, 0, NULL),
(46, 'building', 'civil', 'techs_hospital', 0, 4, 1, NULL, 10, 10, 1, NULL),
(47, 'ship', 'economy', 'techs_smallTransporter', 0, 7, 4, NULL, 15, 10, 1, 500),
(48, 'building', 'civil', 'techs_public_security', 0, 3, 0, NULL, 10, 10, 0, NULL),
(49, 'ship', 'military', 'techs_battlecruiser1', 0, 12, 5, NULL, 15, 10, 1, 500),
(50, 'building', 'civil', 'techs_denkmal', 0, 9, 0, NULL, 20, 10, 1, NULL),
(51, 'building', 'civil', 'techs_university', 0, 6, 1, NULL, 10, 10, 0, NULL),
(52, 'building', 'civil', 'techs_bar', 0, 4, 3, NULL, 10, 10, 0, NULL),
(53, 'building', 'civil', 'techs_stadium', 0, 15, 0, NULL, 10, 10, 0, NULL),
(54, 'building', 'civil', 'techs_casino', 0, 11, 0, NULL, 10, 10, 0, NULL),
(55, 'building', 'civil', 'techs_prison', 0, 11, 2, NULL, 10, 10, 0, NULL),
(56, 'building', 'civil', 'techs_museum', 0, 7, 0, NULL, 10, 10, 0, NULL),
(64, 'building', 'civil', 'techs_wastedisposal', 0, 3, 4, NULL, 10, 10, 0, NULL),
(65, 'building', 'civil', 'techs_recyclingStation', 0, 4, 4, NULL, 10, 10, 0, NULL),
(66, 'building', 'military', 'techs_secretOps', 0, 11, 1, NULL, 10, 10, 0, NULL),
(68, 'building', 'military', 'techs_militarySpaceyard', 0, 7, 5, NULL, 10, 10, 0, NULL),
(70, 'building', 'economy', 'techs_bank', 0, 11, 3, NULL, 10, 10, 0, NULL),
(72, 'research', 'civil', 'techs_medicalScience', 1, 5, 2, NULL, 25, 10, 1, NULL),
(73, 'research', 'civil', 'techs_physics', 1, 5, 3, NULL, 25, 10, 0, NULL),
(74, 'research', 'civil', 'techs_chemistry', 1, 3, 2, NULL, 25, 10, 1, NULL),
(76, 'research', 'economy', 'techs_economicScience', 1, 10, 3, NULL, 25, 10, 1, NULL),
(79, 'research', 'politics', 'techs_diplomacy', 1, 10, 1, NULL, 25, 10, 1, NULL),
(80, 'research', 'politics', 'techs_politicalScience', 1, 10, 2, NULL, 25, 10, 0, NULL),
(81, 'research', 'military', 'techs_military', 1, 9, 5, NULL, 25, 10, 1, NULL),
(83, 'ship', 'economy', 'techs_mediumTransporter', 0, 9, 4, NULL, 15, 10, 1, 500),
(84, 'ship', 'economy', 'techs_largeTransporter', 0, 11, 4, NULL, 15, 10, 1, 500),
(88, 'ship', 'civil', 'techs_colonyShip', 0, 13, 4, NULL, 50, 10, 1, 500),
(89, 'advisor', 'military', 'techs_pilot', 1, 7, 3, NULL, 0, 10, 1, NULL),
(92, 'advisor', 'economy', 'techs_trader', 1, 7, 2, NULL, 0, 10, 1, NULL);

INSERT INTO `trade_researches` (`colony_id`, `direction`, `research_id`, `amount`, `price`, `restriction`) VALUES
(1, 1, 25, 123456, 11, NULL),
(1, 0, 35, 12345, 11, NULL),
(2, 0, 27, 1, 1, NULL),
(2, 0, 28, 1, 1, NULL),
(2, 0, 29, 1, 1, NULL),
(2, 0, 30, 1, 1, NULL),
(2, 0, 31, 1, 1, NULL),
(2, 0, 32, 1, 1, NULL),
(2, 0, 33, 1, 1, NULL),
(2, 0, 34, 1, 1, NULL);

INSERT INTO `trade_resources` (`colony_id`, `direction`, `resource_id`, `amount`, `price`, `restriction`) VALUES
(2, 0, 3, 11, 11, 0),
(2, 1, 5, 123, 32, 0),
(2, 0, 6, 45, 45, 0),
(1, 0, 8, 1, 2, 0),
(1, 0, 10, 4, 3, 0);

INSERT INTO `user` (`user_id`, `username`, `display_name`, `role`, `password`, `email`, `state`, `race_id`, `faction_id`, `description`, `note`, `disabled`, `activated`, `activation_key`, `first_time_login`, `last_activity`, `registration`, `theme`, `tooltips_enabled`) VALUES
(0, 'Homer', '', 'player', '$2y$14$nFApucgOokdP66.LfoskBOoyek5wTo3XrMwYvngTjMiIXw5YJ918e', 'homer@nouron.de', NULL, 1, 7, 'dies ist eine Testbeschreibung dies ist eine Testbeschreibung dies ist eine Testbeschreibung dies ist eine Testbeschreibung dies ist eine Testbeschreibung dies ist eine Testbeschreibung dies ist eine Testbeschreibung dies ist eine Testbeschreibung !"�$%&/()=?`*''_:>><<|��{[]]\\~\r\n', '', 0, 0, 'adsfsdfsf', 0, '2012-03-05 10:08:37', '0000-00-00 00:00:00', 'darkred', 1),
(1, 'Marge', '', 'player', '$2y$14$nFApucgOokdP66.LfoskBOoyek5wTo3XrMwYvngTjMiIXw5YJ918e', 'marge@nouron.de', NULL, 3, 6, '', '', 0, 0, 'gaqx2hwrf4env5i3', 1, '2011-09-18 09:49:10', '2009-12-23 14:00:00', 'darkred', 1),
(2, 'Lisa', '', 'player', '$2y$14$nFApucgOokdP66.LfoskBOoyek5wTo3XrMwYvngTjMiIXw5YJ918e', 'lisa@nouron.de', NULL, 1, 1, '', '', 0, 0, 'abcdefg', 1, '2011-09-18 13:39:26', '0000-00-00 00:00:00', 'darkred', 1),
(3, 'Bart', '', 'admin', '$2y$14$nFApucgOokdP66.LfoskBOoyek5wTo3XrMwYvngTjMiIXw5YJ918e', 'bart@nouron.de', NULL, 2, 4, '', '', 0, 0, '', 1, '2013-01-05 19:25:03', '0000-00-00 00:00:00', 'darkgreen', 1),
(4, 'Maggy', '', 'player', '$2y$14$nFApucgOokdP66.LfoskBOoyek5wTo3XrMwYvngTjMiIXw5YJ918e', 'maggy@nouron.de', NULL, 1, 2, '', '', 0, 1, 'abcdefg', 0, '2012-12-27 11:46:30', '0000-00-00 00:00:00', '0', 1),
(5, 'Moe', '', 'player', '$2y$14$nFApucgOokdP66.LfoskBOoyek5wTo3XrMwYvngTjMiIXw5YJ918e', 'moe@nouron.de', NULL, 3, 5, '', '', 0, 1, 'abcdefg', 0, '2012-12-27 11:46:30', '0000-00-00 00:00:00', '0', 1),
(18, 'Lenny', '', 'player', '$2y$14$nFApucgOokdP66.LfoskBOoyek5wTo3XrMwYvngTjMiIXw5YJ918e', 'lenny@nouron.de', NULL, 0, 0, '', '', 0, 0, '', 1, '2012-12-27 11:46:30', '0000-00-00 00:00:00', 'darkred', 1),
(19, 'Carl', '', 'player', '$2y$14$nFApucgOokdP66.LfoskBOoyek5wTo3XrMwYvngTjMiIXw5YJ918e', 'carl@nouron.de', NULL, 1, 1, '', '', 0, 0, '', 1, '2012-12-27 11:46:30', '0000-00-00 00:00:00', 'darkred', 1);

INSERT INTO `user_resources` (`user_id`, `credits`, `supply`) VALUES
(3, 49615, 1938);
SET FOREIGN_KEY_CHECKS=1;
