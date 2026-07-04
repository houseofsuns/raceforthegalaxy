
CREATE TABLE IF NOT EXISTS `card` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'unique',
  `card_type` varchar(16) NOT NULL COMMENT 'index into card_types array',
  `card_type_arg` int(11) NOT NULL COMMENT 'card cost/price',
  -- card_location: deck|discard|hand|tableau|hiddentableau|good|goal_first|goal_second|obj_first|pd{player_id}|pdis{player_id}
  `card_location` varchar(16) NOT NULL,
  -- hand/tableau/hiddentableau: owning player_id
  -- good: host world's card_id
  `card_location_arg` int(11) NOT NULL,
  -- card_location='tableau' (consume-power worlds/devs):
  --   0=inactive, >0=times used, -1=exhausted, -2=taken over
  -- card_location='tableau', card_type=253 (Terraforming Colony):
  --   0=unused, 1=discard-card power used, 2=goods power used, -1=both used
  -- card_location='tableau', card_type=220 (Oort Cloud):
  --   chosen kind ID (not reset between rounds)
  -- card_location='good':
  --   good type (1=Novelty, 2=Rare elements, 3=Genes, 4=Alien technology)
  `card_status` smallint(5) NOT NULL COMMENT 'consume ability status or good type' DEFAULT '0',
  -- track cards coming into play to award benefits
  `card_played_round` smallint(3) NOT NULL DEFAULT '-1',
  `card_played_phase` smallint(3) NOT NULL DEFAULT '-1',
  `card_played_subphase` smallint(2) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=115 ;



CREATE TABLE IF NOT EXISTS `phase` (
  `phase_id` int(10) unsigned NOT NULL COMMENT '1-5=phase number, 7=search',
  `phase_player` int(10) unsigned NOT NULL COMMENT 'choosing player',
  -- explore:  0=+1+1, 1=+5+0, >=100=Orb bonus (exact value encodes Orb row)
  -- develop:  0=normal, 10=prestige (-1 dev cost)
  -- settle:   0=normal, 10=prestige (+1 card)
  -- consume:  0=trade ($), 1=x2 VP
  -- produce:  0=windfall, 1=used, 3=repair
  -- prestige modifier: +10 added to base bonus value
  -- two-player game: if a phase is selected twice the bonus is encoded as (sum + 1)
  --                  the prestige modifier is +20 if the second invocation is modified
  `phase_bonus` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'selected bonus / phase card',
  KEY `phase_player` (`phase_player`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `player` ADD `player_just_played` INT UNSIGNED NULL DEFAULT NULL COMMENT 'a card_id';
ALTER TABLE `player` ADD `player_previously_played` INT UNSIGNED NULL DEFAULT NULL COMMENT 'card_id played in the immediately preceding phase';
ALTER TABLE `player` ADD `player_vp` INT UNSIGNED NOT NULL DEFAULT '0' COMMENT 'VP chips';
ALTER TABLE `player` ADD `player_prestige` INT UNSIGNED NOT NULL DEFAULT '0' COMMENT 'prestige tokens';
ALTER TABLE `player` ADD `player_search` INT UNSIGNED NOT NULL DEFAULT '0' COMMENT '1=player chose Search';
ALTER TABLE `player` ADD `player_milforce` INT NOT NULL DEFAULT '0' COMMENT 'permanent military force total';
ALTER TABLE `player` ADD `player_tmp_milforce` SMALLINT UNSIGNED NOT NULL DEFAULT '0' COMMENT 'temporary military for current settle',
ADD `player_consumed_types` VARCHAR( 128 ) NULL DEFAULT NULL COMMENT 'track current active consume ability';
ALTER TABLE `player` ADD  `player_takeover_target` INT UNSIGNED NULL DEFAULT NULL COMMENT 'a card_id';
ALTER TABLE `player` ADD  `player_startworld` INT NULL DEFAULT NULL COMMENT 'number of start-world';
ALTER TABLE `player` ADD  `player_defense_award` INT UNSIGNED NOT NULL DEFAULT '0' COMMENT 'number of awards';

CREATE TABLE `notification` (
`notification_reference` INT UNSIGNED NOT NULL ,
`notification_contents` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`notification_reference`)
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE IF NOT EXISTS `player_production` (
  `pp_player_id` int(10) unsigned NOT NULL COMMENT 'player who produced the good',
  -- 1=Novelty, 2=Rare elements, 3=Genes, 4=Alien technology (matches card_status for card_location="good")
  `pp_good_id` smallint(6) NOT NULL COMMENT 'good type produced',
  `pp_card_id` int(10) unsigned NOT NULL COMMENT 'card_id of the world that produced it',
  KEY `pp_player_id` (`pp_player_id`,`pp_good_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tableau_order` (
  -- used for tableau display order
  `card_id` int(10) unsigned NOT NULL COMMENT 'card_ids in insertion order'
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

ALTER TABLE  `player` ADD  `player_orb_priority` SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'Orb turn order (lower=higher priority)';
ALTER TABLE  `player` ADD  `player_tmp_gene_force` SMALLINT UNSIGNED NOT NULL DEFAULT  '0' COMMENT 'abused for search and bunker';



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

ALTER TABLE  `player` ADD  `player_tmp_xenoforce` SMALLINT UNSIGNED NOT NULL DEFAULT  '0' COMMENT 'temporary military against Xenos',
ADD  `player_xeno_milforce` SMALLINT NOT NULL DEFAULT  '0' COMMENT 'permanent military against Xenos',
ADD  `player_xeno_milforce_tiebreak` SMALLINT NOT NULL DEFAULT  '0' COMMENT 'tiebreaker for Xeno track ranking',
ADD  `player_effort` INT NOT NULL DEFAULT  '0' COMMENT 'war effort points contributed',
ADD  `player_xeno_victory` TINYINT UNSIGNED NOT NULL DEFAULT  '0' COMMENT '1=player has achieved it';

ALTER TABLE  `card` ADD  `card_damaged` INT UNSIGNED NOT NULL DEFAULT  '0' COMMENT 'damage tokens on this world; 0=undamaged';
