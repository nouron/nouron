PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS buildings (
  id INTEGER  NOT NULL,
  purpose TEXT NOT NULL,
  name TEXT UNIQUE NOT NULL,
  required_building_id INTEGER  DEFAULT NULL REFERENCES buildings(id),
  required_building_level INTEGER  DEFAULT NULL,
  prime_colony_only INTEGER  NOT NULL DEFAULT '0',
  row INTEGER  NOT NULL,
  column INTEGER  NOT NULL,
  max_level INTEGER  DEFAULT NULL,
  ap_for_levelup INTEGER  NOT NULL DEFAULT '1',
  max_status_points INTEGER  DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT row_column UNIQUE (row,column)
);

CREATE TABLE IF NOT EXISTS building_costs (
  building_id  INTEGER(3)  NOT NULL REFERENCES buildings(id),
  resource_id INTEGER  NOT NULL REFERENCES resources(id),
  amount INTEGER  NOT NULL,
  CONSTRAINT building_resource UNIQUE (building_id,resource_id)
);

CREATE TABLE IF NOT EXISTS colony_buildings (
  colony_id INTEGER  NOT NULL REFERENCES glx_colonies(id),
  building_id INTEGER  NOT NULL REFERENCES glx_colonies(id), 
  level INTEGER  NOT NULL DEFAULT '0',
  status_points INTEGER  NOT NULL DEFAULT '10',
  ap_spend INTEGER  NOT NULL DEFAULT '0',
  CONSTRAINT colony_building UNIQUE (colony_id,building_id)
);

CREATE TABLE IF NOT EXISTS colony_personell (
  colony_id INTEGER  NOT NULL REFERENCES glx_colonies(id),
  personell_id INTEGER  NOT NULL REFERENCES personell(id),
  level INTEGER  NOT NULL DEFAULT '0',
  status_points INTEGER  NOT NULL DEFAULT '10',
  PRIMARY KEY (colony_id,personell_id)
);

CREATE TABLE IF NOT EXISTS colony_researches (
  colony_id INTEGER  NOT NULL REFERENCES glx_colonies(id),
  research_id INTEGER  NOT NULL REFERENCES researches(id),
  level INTEGER  NOT NULL DEFAULT '0',
  status_points INTEGER  NOT NULL DEFAULT '10',
  ap_spend INTEGER  NOT NULL DEFAULT '0',
  PRIMARY KEY (colony_id,research_id)
);

CREATE TABLE IF NOT EXISTS colony_resources (
  resource_id INTEGER  NOT NULL REFERENCES resources(id),
  colony_id INTEGER  NOT NULL REFERENCES glx_colonies(id),
  amount INTEGER  NOT NULL DEFAULT '0',
  PRIMARY KEY (resource_id,colony_id)
);

CREATE TABLE IF NOT EXISTS colony_ships (
  colony_id INTEGER  NOT NULL REFERENCES glx_colonies(id),
  ship_id INTEGER  NOT NULL REFERENCES ships(id),
  level INTEGER  NOT NULL DEFAULT '0',
  status_points INTEGER  NOT NULL DEFAULT '10',
  ap_spend INTEGER  NOT NULL DEFAULT '0',
  PRIMARY KEY (colony_id,ship_id)
);

CREATE TABLE IF NOT EXISTS fleets (
  id INTEGER  NOT NULL,
  fleet TEXT NOT NULL,
  user_id INTEGER  NOT NULL REFERENCES user(user_id),
  artefact INTEGER  DEFAULT NULL,
  x INTEGER  NOT NULL,
  y INTEGER  NOT NULL,
  spot INTEGER  NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS fleet_orders (
  tick INTEGER  NOT NULL,
  fleet_id INTEGER  NOT NULL REFERENCES fleets(id),
  'order' TEXT NOT NULL,
  coordinates TEXT NOT NULL,
  data TEXT,
  was_processed INTEGER  NOT NULL DEFAULT '0',
  has_notified INTEGER  NOT NULL DEFAULT '0',
  PRIMARY KEY (tick,fleet_id)
);

CREATE TABLE IF NOT EXISTS fleet_personell (
  fleet_id INTEGER  NOT NULL REFERENCES fleets(id),
  personell_id INTEGER  NOT NULL REFERENCES personell(id),
  count INTEGER  NOT NULL,
  is_cargo INTEGER  NOT NULL DEFAULT '0',
  CONSTRAINT nFlee2 UNIQUE (fleet_id, personell_id, is_cargo)
);

CREATE TABLE IF NOT EXISTS fleet_researches (
  fleet_id INTEGER  NOT NULL REFERENCES fleets(id),
  research_id INTEGER  NOT NULL REFERENCES researches(id),
  count INTEGER  NOT NULL,
  is_cargo INTEGER  NOT NULL DEFAULT '0',
  CONSTRAINT nFlee2 UNIQUE (fleet_id,research_id,is_cargo)
);

CREATE TABLE IF NOT EXISTS fleet_resources (
  fleet_id INTEGER  NOT NULL REFERENCES fleets(id),
  resource_id INTEGER  NOT NULL REFERENCES resources(id),
  amount INTEGER  NOT NULL,
  PRIMARY KEY (fleet_id, resource_id)
);

CREATE TABLE IF NOT EXISTS fleet_ships (
  fleet_id INTEGER  NOT NULL REFERENCES fleets(id),
  ship_id INTEGER  NOT NULL REFERENCES ships(id),
  count INTEGER  NOT NULL,
  is_cargo INTEGER  NOT NULL DEFAULT '0',
  CONSTRAINT nFlee2 UNIQUE (fleet_id, ship_id, is_cargo)
);

CREATE TABLE IF NOT EXISTS glx_colonies (
  id INTEGER  NOT NULL,
  name TEXT NOT NULL DEFAULT 'Colony',
  system_object_id INTEGER  NOT NULL REFERENCES glx_system_objects(id),
  spot INTEGER  NOT NULL,
  user_id INTEGER  DEFAULT NULL REFERENCES user(user_id),
  since_tick INTEGER  NOT NULL,
  is_primary INTEGER  NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS glx_systems (
  id INTEGER  NOT NULL,
  x INTEGER  NOT NULL,
  y INTEGER  NOT NULL,
  name TEXT NOT NULL,
  type_id INTEGER  NOT NULL REFERENCES glx_system_types(id),
  background_image_url TEXT NOT NULL,
  sight INTEGER  NOT NULL DEFAULT '9',
  density INTEGER  NOT NULL DEFAULT '0',
  radiation INTEGER  NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS glx_system_objects (
  id INTEGER  NOT NULL,
  x INTEGER  NOT NULL,
  y INTEGER  NOT NULL,
  name TEXT NOT NULL,
  type_id INTEGER  NOT NULL REFERENCES glx_system_object_types(id),
  sight INTEGER  NOT NULL DEFAULT '9',
  density INTEGER  NOT NULL DEFAULT '0',
  radiation INTEGER  NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS glx_system_object_types (
  id INTEGER  NOT NULL,
  type TEXT NOT NULL,
  image_url TEXT NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS glx_system_types (
  id INTEGER  NOT NULL,
  class TEXT NOT NULL,
  size INTEGER NOT NULL DEFAULT '5',
  icon_url TEXT DEFAULT NULL,
  image_url TEXT DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS innn_events (
  id INTEGER  NOT NULL,
  user INTEGER  NOT NULL,
  tick INTEGER  NOT NULL,
  event TEXT NOT NULL,
  area TEXT NOT NULL,
  parameters text NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS innn_messages (
  id INTEGER  NOT NULL,
  sender_id INTEGER  NOT NULL REFERENCES user(user_id),
  attitude TEXT NOT NULL,
  recipient_id INTEGER  NOT NULL REFERENCES user(user_id),
  tick INTEGER  NOT NULL,
  type INTEGER NOT NULL,
  subject TEXT NOT NULL,
  'text' TEXT NOT NULL,
  isRead INTEGER  NOT NULL DEFAULT '0',
  isArchived INTEGER  NOT NULL DEFAULT '0',
  isDeleted INTEGER  NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS innn_message_types (
  id INTEGER NOT NULL,
  type TEXT UNIQUE NOT NULL,
  relationship_effect INTEGER NOT NULL DEFAULT '0',
  points INTEGER  NOT NULL DEFAULT '1',
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS innn_news (
  id INTEGER  NOT NULL,
  tick INTEGER  NOT NULL,
  icon TEXT NOT NULL,
  topic TEXT CHECK( topic IN ('economy','politics','diplomacy','culture','sports','misc') ) NOT NULL,
  headline TEXT NOT NULL,
  text text NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS locked_actionpoints (
  tick INTEGER  NOT NULL,
  colony_id INTEGER  NOT NULL REFERENCES glx_colonies(id),
  personell_id INTEGER  NOT NULL REFERENCES personell(id),
  spend_ap INTEGER  NOT NULL DEFAULT '0',
  PRIMARY KEY (tick,colony_id,personell_id)
);

CREATE TABLE IF NOT EXISTS personell (
  id INTEGER  NOT NULL,
  purpose TEXT NOT NULL,
  name TEXT UNIQUE NOT NULL,
  required_building_id INTEGER  DEFAULT NULL REFERENCES buildings(id),
  required_building_level INTEGER  DEFAULT NULL,
  row INTEGER  NOT NULL,
  column INTEGER  NOT NULL,
  max_status_points INTEGER  DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT row UNIQUE (row,column)
);

CREATE TABLE IF NOT EXISTS personell_costs (
  personell_id INTEGER  NOT NULL REFERENCES personell(id),
  resource_id INTEGER  NOT NULL REFERENCES resources(id),
  amount INTEGER  NOT NULL
);

CREATE TABLE IF NOT EXISTS researches (
  id INTEGER  NOT NULL,
  purpose TEXT NOT NULL,
  name TEXT UNIQUE NOT NULL,
  required_building_id INTEGER  DEFAULT NULL REFERENCES buildings(id),
  required_building_level INTEGER  DEFAULT NULL,
  row INTEGER  NOT NULL,
  column INTEGER  NOT NULL,
  ap_for_levelup INTEGER  NOT NULL DEFAULT '1',
  max_status_points INTEGER  DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT row UNIQUE (row,column)
);

CREATE TABLE IF NOT EXISTS research_costs (
  research_id INTEGER  NOT NULL REFERENCES researches(id),
  resource_id INTEGER  NOT NULL REFERENCES resources(id),
  amount INTEGER  NOT NULL,
  PRIMARY KEY (research_id,resource_id)
);

CREATE TABLE IF NOT EXISTS resources (
  id INTEGER  NOT NULL,
  name TEXT NOT NULL,
  abbreviation TEXT NOT NULL,
  trigger TEXT NOT NULL,
  is_tradeable INTEGER  NOT NULL DEFAULT '1',
  start_amount INTEGER NOT NULL DEFAULT '0',
  icon TEXT NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS ships (
  id INTEGER  NOT NULL,
  purpose TEXT NOT NULL,
  name TEXT UNIQUE NOT NULL,
  required_building_id INTEGER  DEFAULT NULL REFERENCES buildings(id),
  required_building_level INTEGER  DEFAULT NULL,
  required_research_id INTEGER  DEFAULT NULL REFERENCES researches(id),
  required_research_level INTEGER  DEFAULT NULL,
  prime_colony_only INTEGER  NOT NULL DEFAULT '0',
  row INTEGER  NOT NULL,
  column INTEGER  NOT NULL,
  ap_for_levelup INTEGER  NOT NULL DEFAULT '1',
  max_status_points INTEGER  DEFAULT NULL,
  moving_speed INTEGER  DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT row UNIQUE(row,column)
);

CREATE TABLE IF NOT EXISTS ship_costs (
  ship_id INTEGER  NOT NULL REFERENCES ships(id),
  resource_id INTEGER  NOT NULL REFERENCES resources(id),
  amount INTEGER  NOT NULL,
  PRIMARY KEY (ship_id,resource_id)
);

CREATE TABLE IF NOT EXISTS trade_researches (
  colony_id INTEGER  NOT NULL REFERENCES glx_colonies(id),
  direction INTEGER  NOT NULL DEFAULT '0',
  research_id INTEGER  NOT NULL REFERENCES researches(id),
  amount bigINTEGER  NOT NULL DEFAULT '0',
  price INTEGER  NOT NULL DEFAULT '0',
  restriction INTEGER  DEFAULT NULL,
  PRIMARY KEY (colony_id,research_id)
);

CREATE TABLE IF NOT EXISTS trade_resources (
  colony_id INTEGER  NOT NULL REFERENCES glx_colonies(id),
  direction INTEGER  NOT NULL DEFAULT '0',
  resource_id INTEGER  NOT NULL REFERENCES resources(id),
  amount bigINTEGER  NOT NULL DEFAULT '0',
  price INTEGER  NOT NULL DEFAULT '0',
  restriction INTEGER  NOT NULL DEFAULT '0'
);

CREATE TABLE IF NOT EXISTS user (
  user_id INTEGER  NOT NULL REFERENCES user(user_id),
  username TEXT UNIQUE NOT NULL,
  display_name TEXT NOT NULL,
  role TEXT NOT NULL,
  password TEXT NOT NULL,
  email TEXT UNIQUE NOT NULL,
  state smallINTEGER DEFAULT NULL,
  race_id INTEGER  DEFAULT NULL,
  faction_id smallINTEGER  DEFAULT NULL,
  description text,
  note tinytext,
  disabled INTEGER  NOT NULL DEFAULT '0',
  activated INTEGER  NOT NULL DEFAULT '0',
  activation_key TEXT NOT NULL,
  first_time_login INTEGER  NOT NULL DEFAULT '1',
  last_activity timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  registration timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  theme TEXT NOT NULL DEFAULT 'darkred',
  tooltips_enabled INTEGER  NOT NULL DEFAULT '1',
  PRIMARY KEY (user_id)
);

CREATE TABLE IF NOT EXISTS user_resources (
  user_id INTEGER  UNIQUE NOT NULL,
  credits INTEGER NOT NULL,
  supply INTEGER  NOT NULL
);

DROP VIEW IF EXISTS v_glx_colonies;
CREATE VIEW v_glx_colonies AS select c.id AS id,c.name AS name,c.system_object_id AS system_object_id,c.spot AS spot,c.user_id AS user_id,c.since_tick AS since_tick,c.is_primary AS is_primary,o.name AS system_object_name,o.x AS x,o.y AS y,o.type_id AS type_id,o.sight AS sight,o.density AS density,o.radiation AS radiation from (glx_colonies c join glx_system_objects o) where (c.system_object_id = o.id);
DROP VIEW IF EXISTS v_glx_systems;
CREATE VIEW v_glx_systems AS select s.id AS id,s.x AS x,s.y AS y,s.name AS name,s.type_id AS type_id,s.background_image_url AS background_image_url,s.sight AS sight,s.density AS density,s.radiation AS radiation,t.class AS class,t.size AS size,t.icon_url AS icon_url,t.image_url AS image_url from (glx_systems s join glx_system_types t) where (s.type_id = t.id);
DROP VIEW IF EXISTS v_glx_system_objects;
CREATE VIEW v_glx_system_objects AS select o.id AS id,o.x AS x,o.y AS y,o.name AS name,o.type_id AS type_id,o.sight AS sight,o.density AS density,o.radiation AS radiation,t.type AS type,t.image_url AS image_url from (glx_system_objects o join glx_system_object_types t) where (o.type_id = t.id);
DROP VIEW IF EXISTS v_innn_messages;
CREATE VIEW v_innn_messages AS select m.id AS id,m.sender_id AS sender_id,m.attitude AS attitude,m.recipient_id AS recipient_id,m.tick AS tick,m.type AS type,m.subject AS subject,m.text AS text,m.isRead AS isRead,m.isArchived AS isArchived,m.isDeleted AS isDeleted,sender.username AS sender,recipient.username AS recipient from ((innn_messages m join user sender) join user recipient) where ((sender.user_id = m.sender_id) and (recipient.user_id = m.recipient_id));
DROP VIEW IF EXISTS v_trade_researches;
CREATE VIEW v_trade_researches AS select tr.colony_id AS colony_id,tr.direction AS direction,tr.research_id AS research_id,tr.amount AS amount,tr.price AS price,tr.restriction AS restriction,col.name AS colony,u.username AS username,u.user_id AS user_id,u.race_id AS race_id,u.faction_id AS faction_id from ((trade_researches tr join glx_colonies col) join user u) where ((tr.colony_id = col.id) and (col.user_id = u.user_id));
DROP VIEW IF EXISTS v_trade_resources;
CREATE VIEW v_trade_resources AS select tr.colony_id AS colony_id,tr.direction AS direction,tr.resource_id AS resource_id,tr.amount AS amount,tr.price AS price,tr.restriction AS restriction,col.name AS colony,u.username AS username,u.user_id AS user_id,u.race_id AS race_id,u.faction_id AS faction_id from ((trade_resources tr join glx_colonies col) join user u) where ((tr.colony_id = col.id) and (col.user_id = u.user_id));
