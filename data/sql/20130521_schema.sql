SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `glx_colonies` (
  `id` int(10) unsigned NOT NULL,
  `name` char(20) NOT NULL DEFAULT 'Colony',
  `system_object_id` int(10) unsigned NOT NULL,
  `spot` tinyint(1) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `since_tick` int(11) unsigned NOT NULL,
  `is_primary` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'is primary colony for this user',
  PRIMARY KEY (`id`),
  KEY `nPlanetary` (`system_object_id`),
  KEY `nUser` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `glx_fleetorders` (
  `tick` int(10) unsigned NOT NULL,
  `fleet_id` int(10) unsigned NOT NULL,
  `order` set('move','trade','hold','attack','defend','convoy','join','divide') NOT NULL DEFAULT 'move',
  `coordinates` char(50) NOT NULL COMMENT 'json format',
  `data` text COMMENT 'json format',
  `was_processed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `has_notified` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tick`,`fleet_id`),
  KEY `fleet_id` (`fleet_id`),
  KEY `tick` (`tick`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `glx_fleetresources` (
  `fleet_id` int(10) unsigned NOT NULL,
  `resource_id` tinyint(2) unsigned NOT NULL,
  `amount` int(10) unsigned NOT NULL,
  PRIMARY KEY (`fleet_id`,`resource_id`),
  KEY `resource_id` (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `glx_fleets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fleet` char(30) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `artefact` tinyint(3) unsigned DEFAULT NULL,
  `x` int(5) unsigned NOT NULL,
  `y` int(5) unsigned NOT NULL,
  `spot` int(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=20 ;

CREATE TABLE IF NOT EXISTS `glx_fleettechnologies` (
  `fleet_id` int(10) unsigned NOT NULL,
  `tech_id` int(10) unsigned NOT NULL,
  `count` int(10) unsigned NOT NULL,
  `is_cargo` tinyint(1) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `nFlee2` (`fleet_id`,`tech_id`,`is_cargo`),
  KEY `tech_id` (`tech_id`),
  KEY `fleet_id` (`fleet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `glx_systems` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `x` int(5) unsigned NOT NULL,
  `y` int(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type_id` int(10) unsigned NOT NULL,
  `background_image_url` varchar(255) NOT NULL,
  `sight` tinyint(1) unsigned NOT NULL DEFAULT '9',
  `density` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `radiation` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `nType` (`type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=19 ;

CREATE TABLE IF NOT EXISTS `glx_system_objects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `x` int(5) unsigned NOT NULL,
  `y` int(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type_id` int(10) unsigned NOT NULL,
  `sight` tinyint(1) unsigned NOT NULL DEFAULT '9',
  `density` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `radiation` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `nType` (`type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=19 ;

CREATE TABLE IF NOT EXISTS `glx_system_object_types` (
  `id` int(10) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `glx_system_types` (
  `id` int(10) unsigned NOT NULL,
  `class` varchar(255) NOT NULL,
  `size` tinyint(1) NOT NULL DEFAULT '5',
  `icon_url` varchar(50) DEFAULT NULL,
  `image_url` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `innn_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(10) unsigned NOT NULL,
  `tick` int(10) unsigned NOT NULL,
  `event` varchar(255) NOT NULL,
  `area` set('colony','planetary','stellar','galaxy','alliance','faction','race') NOT NULL,
  `parameters` text NOT NULL COMMENT 'serialized array with parameters for sprintf - function',
  PRIMARY KEY (`id`),
  KEY `user` (`user`,`tick`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=20 ;

CREATE TABLE IF NOT EXISTS `innn_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(10) unsigned NOT NULL,
  `attitude` char(50) NOT NULL,
  `recipient_id` int(10) unsigned NOT NULL,
  `tick` int(10) unsigned NOT NULL,
  `type` tinyint(2) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `isRead` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `isArchived` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `isDeleted` int(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `recipient_id` (`recipient_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=23 ;

CREATE TABLE IF NOT EXISTS `innn_message_types` (
  `id` tinyint(2) NOT NULL,
  `type` varchar(50) NOT NULL,
  `relationship_effect` tinyint(2) NOT NULL DEFAULT '0' COMMENT 'effect on the relationship from sender to recipient',
  `points` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT 'points for this message',
  PRIMARY KEY (`id`),
  UNIQUE KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `innn_news` (
  `id` int(10) unsigned NOT NULL,
  `tick` int(10) unsigned NOT NULL,
  `icon` varchar(50) NOT NULL,
  `topic` enum('economy','politics','diplomacy','culture','sports','misc') NOT NULL,
  `headline` varchar(255) NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `rbac_permission` (
  `perm_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `perm_name` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`perm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `rbac_role` (
  `role_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_role_id` int(11) unsigned DEFAULT NULL,
  `role_name` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`role_id`),
  KEY `parent_role_id` (`parent_role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `rbac_role_permission` (
  `role_id` int(11) unsigned NOT NULL,
  `perm_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`perm_id`),
  KEY `perm_id` (`perm_id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `res_colony_resources` (
  `resource_id` tinyint(2) unsigned NOT NULL,
  `colony_id` int(10) unsigned NOT NULL,
  `amount` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`resource_id`,`colony_id`),
  KEY `resource_id` (`resource_id`),
  KEY `user_id` (`colony_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `res_resources` (
  `id` tinyint(2) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `abbreviation` varchar(5) NOT NULL,
  `trigger` set('Event','Constant','Level') NOT NULL DEFAULT 'Level',
  `is_tradeable` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `start_amount` int(11) NOT NULL DEFAULT '0',
  `icon` varchar(99) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13 ;

CREATE TABLE IF NOT EXISTS `res_user_resources` (
  `user_id` int(10) unsigned NOT NULL,
  `credits` bigint(20) NOT NULL,
  `supply` int(10) unsigned NOT NULL COMMENT 'available supply NOT used Supply',
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='resources in user context (there is another table for colony';

CREATE TABLE IF NOT EXISTS `tech_costs` (
  `tech_id` int(10) unsigned NOT NULL,
  `resource_id` int(10) unsigned NOT NULL,
  `amount` int(10) unsigned NOT NULL,
  PRIMARY KEY (`tech_id`,`resource_id`),
  KEY `resource_id` (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tech_orders` (
  `tick` int(10) unsigned NOT NULL,
  `colony_id` int(10) unsigned NOT NULL,
  `tech_id` int(3) unsigned NOT NULL,
  `order` set('add','sub','renew') NOT NULL DEFAULT 'add',
  `ap_ordered` int(2) unsigned NOT NULL DEFAULT '0',
  `is_final_step` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `was_progressed` tinyint(1) NOT NULL DEFAULT '0',
  `has_notified` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tick`,`colony_id`,`tech_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tech_possessions` (
  `colony_id` int(11) unsigned NOT NULL,
  `tech_id` int(3) unsigned NOT NULL,
  `display_name` varchar(255) DEFAULT NULL COMMENT 'a user defined, but not used yet name',
  `level` int(2) unsigned NOT NULL DEFAULT '0',
  `ap_spend` int(2) unsigned NOT NULL DEFAULT '0' COMMENT 'already spended build ap for this tech',
  `slot` int(2) unsigned DEFAULT NULL COMMENT 'position where a building stands, but not used yet!',
  PRIMARY KEY (`colony_id`,`tech_id`),
  KEY `nTechnology` (`tech_id`),
  KEY `nUser` (`colony_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tech_requirements` (
  `required_tech_id` int(3) unsigned NOT NULL,
  `tech_id` int(3) unsigned NOT NULL DEFAULT '0',
  `required_tech_level` int(2) unsigned NOT NULL,
  PRIMARY KEY (`required_tech_id`,`tech_id`),
  KEY `required_tech_id` (`required_tech_id`),
  KEY `tech_id` (`tech_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tech_technologies` (
  `id` int(3) unsigned NOT NULL,
  `type` set('building','research','advisor','ship') NOT NULL DEFAULT 'building',
  `purpose` set('civil','industry','economy','politics','military') NOT NULL DEFAULT 'civil',
  `name` varchar(255) NOT NULL,
  `prime_colony_only` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1 = nur auf Primärkol. baubar, 0 = auf allen Kol.',
  `row` int(2) unsigned NOT NULL,
  `column` int(1) unsigned NOT NULL,
  `max_level` int(2) unsigned DEFAULT NULL,
  `ap_for_levelup` int(2) unsigned NOT NULL DEFAULT '1' COMMENT 'ticks needed to build or research this tech',
  `decay` int(2) unsigned DEFAULT NULL COMMENT 'decay per tick',
  `tradeable` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `moving_speed` int(5) unsigned DEFAULT NULL COMMENT 'moving speed per tick (primarily for ships)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `row` (`row`,`column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `trade_res` (
  `colony_id` int(10) unsigned NOT NULL,
  `direction` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `resource_id` tinyint(2) unsigned NOT NULL,
  `amount` bigint(20) unsigned NOT NULL DEFAULT '0',
  `price` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `restriction` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`colony_id`,`resource_id`),
  KEY `colony_id` (`colony_id`),
  KEY `resource_id` (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `trade_techs` (
  `colony_id` int(10) unsigned NOT NULL,
  `direction` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0 = buy; 1 = sell',
  `tech_id` int(10) unsigned NOT NULL,
  `amount` bigint(20) unsigned NOT NULL DEFAULT '0',
  `price` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT 'in Credits',
  `restriction` int(10) unsigned DEFAULT NULL COMMENT '0 = none; 1= own Race; 2 = own Faction; 3 = own Alliance',
  PRIMARY KEY (`colony_id`,`tech_id`),
  KEY `colony_id` (`colony_id`),
  KEY `tech_id` (`tech_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int(10) unsigned NOT NULL,
  `username` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `password` varchar(128) NOT NULL,
  `email` varchar(255) NOT NULL,
  `state` smallint(6) DEFAULT NULL,
  `race_id` tinyint(2) unsigned DEFAULT NULL,
  `faction_id` smallint(3) unsigned DEFAULT NULL,
  `description` text,
  `note` tinytext,
  `disabled` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `activated` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `activation_key` varchar(32) NOT NULL,
  `first_time_login` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `registration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `theme` varchar(50) NOT NULL DEFAULT 'darkred',
  `tooltips_enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `nRace` (`race_id`),
  KEY `nFaction` (`faction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `v_glx_colonies` (
`id` int(10) unsigned
,`name` char(20)
,`system_object_id` int(10) unsigned
,`spot` tinyint(1) unsigned
,`user_id` int(10) unsigned
,`since_tick` int(11) unsigned
,`is_primary` tinyint(1) unsigned
,`system_object_name` varchar(255)
,`x` int(5) unsigned
,`y` int(5) unsigned
,`type_id` int(10) unsigned
,`sight` tinyint(1) unsigned
,`density` tinyint(1) unsigned
,`radiation` tinyint(1) unsigned
);CREATE TABLE IF NOT EXISTS `v_glx_fleettechnologies` (
`fleet_id` int(10) unsigned
,`tech_id` int(10) unsigned
,`count` int(10) unsigned
,`is_cargo` tinyint(1) unsigned
,`name` varchar(255)
,`type` set('building','research','advisor','ship')
,`purpose` set('civil','industry','economy','politics','military')
);CREATE TABLE IF NOT EXISTS `v_glx_systems` (
`id` int(10) unsigned
,`x` int(5) unsigned
,`y` int(5) unsigned
,`name` varchar(255)
,`type_id` int(10) unsigned
,`background_image_url` varchar(255)
,`sight` tinyint(1) unsigned
,`density` tinyint(1) unsigned
,`radiation` tinyint(1) unsigned
,`class` varchar(255)
,`size` tinyint(1)
,`icon_url` varchar(50)
,`image_url` varchar(50)
);CREATE TABLE IF NOT EXISTS `v_glx_system_objects` (
`id` int(10) unsigned
,`x` int(5) unsigned
,`y` int(5) unsigned
,`name` varchar(255)
,`type_id` int(10) unsigned
,`sight` tinyint(1) unsigned
,`density` tinyint(1) unsigned
,`radiation` tinyint(1) unsigned
,`type` varchar(255)
,`image_url` varchar(255)
);CREATE TABLE IF NOT EXISTS `v_innn_messages` (
`id` int(10) unsigned
,`sender_id` int(10) unsigned
,`attitude` char(50)
,`recipient_id` int(10) unsigned
,`tick` int(10) unsigned
,`type` tinyint(2)
,`subject` varchar(255)
,`text` text
,`isRead` tinyint(1) unsigned
,`isArchived` tinyint(1) unsigned
,`isDeleted` int(1) unsigned
,`sender` varchar(255)
,`recipient` varchar(255)
);DROP TABLE IF EXISTS `v_glx_colonies`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_glx_colonies` AS select `c`.`id` AS `id`,`c`.`name` AS `name`,`c`.`system_object_id` AS `system_object_id`,`c`.`spot` AS `spot`,`c`.`user_id` AS `user_id`,`c`.`since_tick` AS `since_tick`,`c`.`is_primary` AS `is_primary`,`o`.`name` AS `system_object_name`,`o`.`x` AS `x`,`o`.`y` AS `y`,`o`.`type_id` AS `type_id`,`o`.`sight` AS `sight`,`o`.`density` AS `density`,`o`.`radiation` AS `radiation` from (`glx_colonies` `c` join `glx_system_objects` `o`) where (`c`.`system_object_id` = `o`.`id`);
DROP TABLE IF EXISTS `v_glx_fleettechnologies`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_glx_fleettechnologies` AS select `glx_fleettechnologies`.`fleet_id` AS `fleet_id`,`glx_fleettechnologies`.`tech_id` AS `tech_id`,`glx_fleettechnologies`.`count` AS `count`,`glx_fleettechnologies`.`is_cargo` AS `is_cargo`,`tech_technologies`.`name` AS `name`,`tech_technologies`.`type` AS `type`,`tech_technologies`.`purpose` AS `purpose` from (`glx_fleettechnologies` join `tech_technologies`);
DROP TABLE IF EXISTS `v_glx_systems`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_glx_systems` AS select `s`.`id` AS `id`,`s`.`x` AS `x`,`s`.`y` AS `y`,`s`.`name` AS `name`,`s`.`type_id` AS `type_id`,`s`.`background_image_url` AS `background_image_url`,`s`.`sight` AS `sight`,`s`.`density` AS `density`,`s`.`radiation` AS `radiation`,`t`.`class` AS `class`,`t`.`size` AS `size`,`t`.`icon_url` AS `icon_url`,`t`.`image_url` AS `image_url` from (`glx_systems` `s` join `glx_system_types` `t`) where (`s`.`type_id` = `t`.`id`);
DROP TABLE IF EXISTS `v_glx_system_objects`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_glx_system_objects` AS select `o`.`id` AS `id`,`o`.`x` AS `x`,`o`.`y` AS `y`,`o`.`name` AS `name`,`o`.`type_id` AS `type_id`,`o`.`sight` AS `sight`,`o`.`density` AS `density`,`o`.`radiation` AS `radiation`,`t`.`type` AS `type`,`t`.`image_url` AS `image_url` from (`glx_system_objects` `o` join `glx_system_object_types` `t`) where (`o`.`type_id` = `t`.`id`);
DROP TABLE IF EXISTS `v_innn_messages`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_innn_messages` AS select `m`.`id` AS `id`,`m`.`sender_id` AS `sender_id`,`m`.`attitude` AS `attitude`,`m`.`recipient_id` AS `recipient_id`,`m`.`tick` AS `tick`,`m`.`type` AS `type`,`m`.`subject` AS `subject`,`m`.`text` AS `text`,`m`.`isRead` AS `isRead`,`m`.`isArchived` AS `isArchived`,`m`.`isDeleted` AS `isDeleted`,`sender`.`username` AS `sender`,`recipient`.`username` AS `recipient` from ((`innn_messages` `m` join `user` `sender`) join `user` `recipient`) where ((`sender`.`user_id` = `m`.`sender_id`) and (`recipient`.`user_id` = `m`.`recipient_id`));


ALTER TABLE `glx_colonies`
  ADD CONSTRAINT `glx_colonies_ibfk_1` FOREIGN KEY (`system_object_id`) REFERENCES `glx_system_objects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `glx_systems`
  ADD CONSTRAINT `glx_systems_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `glx_system_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `glx_system_objects`
  ADD CONSTRAINT `glx_system_objects_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `glx_system_object_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `innn_events`
  ADD CONSTRAINT `innn_events_ibfk_1` FOREIGN KEY (`user`) REFERENCES `usr_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `rbac_role`
  ADD CONSTRAINT `rbac_role_ibfk_1` FOREIGN KEY (`parent_role_id`) REFERENCES `rbac_role` (`role_id`);

ALTER TABLE `rbac_role_permission`
  ADD CONSTRAINT `rbac_role_permission_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `rbac_role` (`role_id`),
  ADD CONSTRAINT `rbac_role_permission_ibfk_2` FOREIGN KEY (`perm_id`) REFERENCES `rbac_permission` (`perm_id`);

ALTER TABLE `res_colony_resources`
  ADD CONSTRAINT `res_colony_resources_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `res_resources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `res_colony_resources_ibfk_2` FOREIGN KEY (`colony_id`) REFERENCES `glx_colonies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `res_user_resources`
  ADD CONSTRAINT `res_user_resources_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `tech_costs`
  ADD CONSTRAINT `tech_costs_ibfk_1` FOREIGN KEY (`tech_id`) REFERENCES `tech_technologies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tech_costs_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `tech_technologies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `tech_possessions`
  ADD CONSTRAINT `tech_possessions_ibfk_1` FOREIGN KEY (`colony_id`) REFERENCES `glx_colonies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tech_possessions_ibfk_2` FOREIGN KEY (`tech_id`) REFERENCES `tech_technologies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `tech_requirements`
  ADD CONSTRAINT `tech_requirements_ibfk_1` FOREIGN KEY (`required_tech_id`) REFERENCES `tech_technologies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tech_requirements_ibfk_2` FOREIGN KEY (`tech_id`) REFERENCES `tech_technologies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `trade_res`
  ADD CONSTRAINT `trade_res_ibfk_5` FOREIGN KEY (`colony_id`) REFERENCES `glx_colonies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `trade_res_ibfk_6` FOREIGN KEY (`resource_id`) REFERENCES `res_resources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `trade_techs`
  ADD CONSTRAINT `trade_techs_ibfk_1` FOREIGN KEY (`colony_id`) REFERENCES `glx_colonies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `trade_techs_ibfk_2` FOREIGN KEY (`tech_id`) REFERENCES `tech_technologies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
SET FOREIGN_KEY_CHECKS=1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
