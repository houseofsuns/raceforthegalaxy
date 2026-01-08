
CREATE TABLE IF NOT EXISTS `card` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  `card_status` smallint(5) NOT NULL COMMENT '0=nothing,>0=in use,-1=used (or good type for goods)',
  `card_played_round` smallint(3) NOT NULL DEFAULT '-1' COMMENT 'TODO:use',
  `card_played_phase` smallint(3) NOT NULL DEFAULT '-1' COMMENT 'TODO:use',
  `card_played_subphase` smallint(2) NOT NULL DEFAULT '-1' COMMENT 'TODO:use',
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=115 ;



CREATE TABLE IF NOT EXISTS `phase` (
  `phase_id` int(10) unsigned NOT NULL,
  `phase_player` int(10) unsigned NOT NULL,
  `phase_bonus` tinyint(3) unsigned NOT NULL DEFAULT '0',
  KEY `phase_player` (`phase_player`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `player` ADD `player_just_played` INT UNSIGNED NULL DEFAULT NULL COMMENT 'card just played by this player';
ALTER TABLE `player` ADD  `player_previously_played` INT UNSIGNED NULL DEFAULT NULL ;
ALTER TABLE `player` ADD `player_vp` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `player_prestige` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `player_search` INT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `player_milforce` INT NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `player_tmp_milforce` SMALLINT UNSIGNED NOT NULL ,
ADD `player_consumed_types` VARCHAR( 128 ) NULL DEFAULT NULL ;
ALTER TABLE `player` ADD  `player_takeover_target` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `player` ADD  `player_startworld` INT NULL DEFAULT NULL;
ALTER TABLE `player` ADD  `player_defense_award` INT UNSIGNED NOT NULL DEFAULT '0';

CREATE TABLE `notification` (
`notification_reference` INT UNSIGNED NOT NULL ,
`notification_contents` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`notification_reference`)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE IF NOT EXISTS `player_production` (
  `pp_player_id` int(10) unsigned NOT NULL,
  `pp_good_id` smallint(6) NOT NULL,
  `pp_card_id` int(10) unsigned NOT NULL,
  KEY `pp_player_id` (`pp_player_id`,`pp_good_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tableau_order` (
  `card_id` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Alien artifact
--


CREATE TABLE IF NOT EXISTS `orbcard` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  `card_x` mediumint(9) DEFAULT NULL,
  `card_y` mediumint(9) DEFAULT NULL,
  `card_ori` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `orb` (
  `x` mediumint(9) NOT NULL,
  `y` mediumint(9) NOT NULL,
  `content` char(1) NOT NULL,
  `wall_n` char(1) NOT NULL,
  `wall_w` char(1) NOT NULL,
  `wall_e` char(1) NOT NULL,
  `wall_s` char(1) NOT NULL,
  `content_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`x`,`y`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `orbteam` (
  `team_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team_player` int(10) unsigned NOT NULL,
  `team_x` mediumint(9) NOT NULL,
  `team_y` mediumint(9) NOT NULL,
  `team_move` tinyint(4) NOT NULL DEFAULT '0',
  `team_crossbarrier` tinyint(4) NOT NULL DEFAULT '0',
  `team_crosswall` tinyint(4) NOT NULL DEFAULT '0',
  `team_cannotmove` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`team_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

ALTER TABLE  `player` ADD  `player_orb_priority` SMALLINT UNSIGNED NULL DEFAULT NULL ;
ALTER TABLE  `player` ADD  `player_tmp_gene_force` SMALLINT UNSIGNED NOT NULL DEFAULT  '0';



CREATE TABLE IF NOT EXISTS `artefact` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--- Xeno invasion

CREATE TABLE IF NOT EXISTS `invasion` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE  `player` ADD  `player_tmp_xenoforce` SMALLINT UNSIGNED NOT NULL DEFAULT  '0',
ADD  `player_xeno_milforce` SMALLINT NOT NULL DEFAULT  '0',
ADD  `player_xeno_milforce_tiebreak` SMALLINT NOT NULL DEFAULT  '0',
ADD  `player_effort` INT NOT NULL DEFAULT  '0',
ADD  `player_xeno_victory` TINYINT UNSIGNED NOT NULL DEFAULT  '0';

ALTER TABLE  `card` ADD  `card_damaged` INT UNSIGNED NOT NULL DEFAULT  '0';

