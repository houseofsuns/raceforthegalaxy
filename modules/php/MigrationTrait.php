<?php
 /**
  * MigrationTrait.php
  *
  * DB migration functionality. Most of this is only of historic interest.
  *
  */

trait MigrationTrait
{
    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

    function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
        if ($from_version <= 1512261437) {
            self::DbQuery("ALTER TABLE `player` ADD  `player_previously_played` INT UNSIGNED NULL DEFAULT NULL");
            self::DbQuery("ALTER TABLE `zz_savepoint_player` ADD  `player_previously_played` INT UNSIGNED NULL DEFAULT NULL");
            self::DbQuery("ALTER TABLE `zz_replay1_player` ADD  `player_previously_played` INT UNSIGNED NULL DEFAULT NULL");
            self::DbQuery("ALTER TABLE `zz_replay2_player` ADD  `player_previously_played` INT UNSIGNED NULL DEFAULT NULL");
            self::DbQuery("ALTER TABLE `zz_replay3_player` ADD  `player_previously_played` INT UNSIGNED NULL DEFAULT NULL");
        }
        if ($from_version <= 1605301547) {
            self::DbQuery("ALTER TABLE `player` ADD  `player_takeover_target` INT UNSIGNED NULL DEFAULT NULL");
            self::DbQuery("ALTER TABLE `zz_savepoint_player` ADD  `player_takeover_target` INT UNSIGNED NULL DEFAULT NULL");
            self::DbQuery("ALTER TABLE `zz_replay1_player` ADD  `player_takeover_target` INT UNSIGNED NULL DEFAULT NULL");
            self::DbQuery("ALTER TABLE `zz_replay2_player` ADD  `player_takeover_target` INT UNSIGNED NULL DEFAULT NULL");
            self::DbQuery("ALTER TABLE `zz_replay3_player` ADD  `player_takeover_target` INT UNSIGNED NULL DEFAULT NULL");

            self::DbQuery("ALTER TABLE `player` ADD  `player_startworld` INT UNSIGNED NULL DEFAULT NULL");
            self::DbQuery("ALTER TABLE `zz_savepoint_player` ADD  `player_startworld` INT UNSIGNED NULL DEFAULT NULL");
            self::DbQuery("ALTER TABLE `zz_replay1_player` ADD  `player_startworld` INT UNSIGNED NULL DEFAULT NULL");
            self::DbQuery("ALTER TABLE `zz_replay2_player` ADD  `player_startworld` INT UNSIGNED NULL DEFAULT NULL");
            self::DbQuery("ALTER TABLE `zz_replay3_player` ADD  `player_startworld` INT UNSIGNED NULL DEFAULT NULL");
        }
        if ($from_version <= 1608262326) {
            self::DbQuery("ALTER TABLE `player` ADD `player_prestige` INT UNSIGNED NOT NULL DEFAULT '0'");
            self::DbQuery("ALTER TABLE `zz_savepoint_player` ADD `player_prestige` INT UNSIGNED NOT NULL DEFAULT '0'");
            self::DbQuery("ALTER TABLE `zz_replay1_player` ADD `player_prestige` INT UNSIGNED NOT NULL DEFAULT '0'");
            self::DbQuery("ALTER TABLE `zz_replay2_player` ADD `player_prestige` INT UNSIGNED NOT NULL DEFAULT '0'");
            self::DbQuery("ALTER TABLE `zz_replay3_player` ADD `player_prestige` INT UNSIGNED NOT NULL DEFAULT '0'");

            self::DbQuery("ALTER TABLE `player` ADD `player_search` INT UNSIGNED NOT NULL DEFAULT '0'");
            self::DbQuery("ALTER TABLE `zz_savepoint_player` ADD `player_search` INT UNSIGNED NOT NULL DEFAULT '0'");
            self::DbQuery("ALTER TABLE `zz_replay1_player` ADD `player_search` INT UNSIGNED NOT NULL DEFAULT '0'");
            self::DbQuery("ALTER TABLE `zz_replay2_player` ADD `player_search` INT UNSIGNED NOT NULL DEFAULT '0'");
            self::DbQuery("ALTER TABLE `zz_replay3_player` ADD `player_search` INT UNSIGNED NOT NULL DEFAULT '0'");
        }
        if ($from_version <= 1612091824) {
            self::DbQuery("CREATE TABLE IF NOT EXISTS `orbcard` (
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
");
            self::DbQuery("CREATE TABLE IF NOT EXISTS `zz_savepoint_orbcard` (
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
");
            self::DbQuery("CREATE TABLE IF NOT EXISTS `zz_replay1_orbcard` (
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
");
            self::DbQuery("CREATE TABLE IF NOT EXISTS `zz_replay2_orbcard` (
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
");
            self::DbQuery("CREATE TABLE IF NOT EXISTS `zz_replay3_orbcard` (
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
");


            self::DbQuery("CREATE TABLE IF NOT EXISTS `orb` (
  `x` mediumint(9) NOT NULL,
  `y` mediumint(9) NOT NULL,
  `content` char(1) NOT NULL,
  `wall_n` char(1) NOT NULL,
  `wall_w` char(1) NOT NULL,
  `wall_e` char(1) NOT NULL,
  `wall_s` char(1) NOT NULL,
  `content_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`x`,`y`) COMMENT 'y'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
            self::DbQuery("CREATE TABLE IF NOT EXISTS `zz_savepoint_orb` (
  `x` mediumint(9) NOT NULL,
  `y` mediumint(9) NOT NULL,
  `content` char(1) NOT NULL,
  `wall_n` char(1) NOT NULL,
  `wall_w` char(1) NOT NULL,
  `wall_e` char(1) NOT NULL,
  `wall_s` char(1) NOT NULL,
  `content_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`x`,`y`) COMMENT 'y'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
            self::DbQuery("CREATE TABLE IF NOT EXISTS `zz_replay1_orb` (
  `x` mediumint(9) NOT NULL,
  `y` mediumint(9) NOT NULL,
  `content` char(1) NOT NULL,
  `wall_n` char(1) NOT NULL,
  `wall_w` char(1) NOT NULL,
  `wall_e` char(1) NOT NULL,
  `wall_s` char(1) NOT NULL,
  `content_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`x`,`y`) COMMENT 'y'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
            self::DbQuery("CREATE TABLE IF NOT EXISTS `zz_replay2_orb` (
  `x` mediumint(9) NOT NULL,
  `y` mediumint(9) NOT NULL,
  `content` char(1) NOT NULL,
  `wall_n` char(1) NOT NULL,
  `wall_w` char(1) NOT NULL,
  `wall_e` char(1) NOT NULL,
  `wall_s` char(1) NOT NULL,
  `content_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`x`,`y`) COMMENT 'y'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
            self::DbQuery("CREATE TABLE IF NOT EXISTS `zz_replay3_orb` (
  `x` mediumint(9) NOT NULL,
  `y` mediumint(9) NOT NULL,
  `content` char(1) NOT NULL,
  `wall_n` char(1) NOT NULL,
  `wall_w` char(1) NOT NULL,
  `wall_e` char(1) NOT NULL,
  `wall_s` char(1) NOT NULL,
  `content_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`x`,`y`) COMMENT 'y'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

            self::DbQuery("CREATE TABLE IF NOT EXISTS `orbteam` (
  `team_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team_player` int(10) unsigned NOT NULL,
  `team_x` mediumint(9) NOT NULL,
  `team_y` mediumint(9) NOT NULL,
  `team_cannotmove` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`team_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
");
            self::DbQuery("CREATE TABLE IF NOT EXISTS `zz_savepoint_orbteam` (
  `team_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team_player` int(10) unsigned NOT NULL,
  `team_x` mediumint(9) NOT NULL,
  `team_y` mediumint(9) NOT NULL,
  `team_cannotmove` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`team_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
");
            self::DbQuery("CREATE TABLE IF NOT EXISTS `zz_replay1_orbteam` (
  `team_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team_player` int(10) unsigned NOT NULL,
  `team_x` mediumint(9) NOT NULL,
  `team_y` mediumint(9) NOT NULL,
  `team_cannotmove` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`team_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
");
            self::DbQuery("CREATE TABLE IF NOT EXISTS `zz_replay2_orbteam` (
  `team_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team_player` int(10) unsigned NOT NULL,
  `team_x` mediumint(9) NOT NULL,
  `team_y` mediumint(9) NOT NULL,
  `team_cannotmove` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`team_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
");
            self::DbQuery("CREATE TABLE IF NOT EXISTS `zz_replay3_orbteam` (
  `team_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team_player` int(10) unsigned NOT NULL,
  `team_x` mediumint(9) NOT NULL,
  `team_y` mediumint(9) NOT NULL,
  `team_cannotmove` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`team_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
");

            self::DbQuery("ALTER TABLE  `player` ADD  `player_orb_priority` SMALLINT UNSIGNED NULL DEFAULT NULL ;");
            self::DbQuery("ALTER TABLE  `zz_savepoint_player` ADD  `player_orb_priority` SMALLINT UNSIGNED NULL DEFAULT NULL ;");
            self::DbQuery("ALTER TABLE  `zz_replay1_player` ADD  `player_orb_priority` SMALLINT UNSIGNED NULL DEFAULT NULL ;");
            self::DbQuery("ALTER TABLE  `zz_replay2_player` ADD  `player_orb_priority` SMALLINT UNSIGNED NULL DEFAULT NULL ;");
            self::DbQuery("ALTER TABLE  `zz_replay3_player` ADD  `player_orb_priority` SMALLINT UNSIGNED NULL DEFAULT NULL ;");

            self::DbQuery("ALTER TABLE  `player` ADD  `player_tmp_gene_force` SMALLINT UNSIGNED NOT NULL DEFAULT  '0';");
            self::DbQuery("ALTER TABLE  `zz_savepoint_player` ADD  `player_tmp_gene_force` SMALLINT UNSIGNED NOT NULL DEFAULT  '0';");
            self::DbQuery("ALTER TABLE  `zz_replay1_player` ADD  `player_tmp_gene_force` SMALLINT UNSIGNED NOT NULL DEFAULT  '0';");
            self::DbQuery("ALTER TABLE  `zz_replay2_player` ADD  `player_tmp_gene_force` SMALLINT UNSIGNED NOT NULL DEFAULT  '0';");
            self::DbQuery("ALTER TABLE  `zz_replay3_player` ADD  `player_tmp_gene_force` SMALLINT UNSIGNED NOT NULL DEFAULT  '0';");

            self::DbQuery("CREATE TABLE IF NOT EXISTS `artefact` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
");
            self::DbQuery("CREATE TABLE IF NOT EXISTS `zz_savepoint_artefact` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
");
            self::DbQuery("CREATE TABLE IF NOT EXISTS `zz_replay1_artefact` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
");
            self::DbQuery("CREATE TABLE IF NOT EXISTS `zz_replay2_artefact` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
");
            self::DbQuery("CREATE TABLE IF NOT EXISTS `zz_replay3_artefact` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
");
        }
        if ($from_version <= 1704251716) {
            self::applyDbChangeToAllDB("CREATE TABLE IF NOT EXISTS `DBPREFIX_invasion` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
");
            self::applyDbChangeToAllDB("ALTER TABLE `DBPREFIX_player` ADD  `player_tmp_xenoforce` SMALLINT UNSIGNED NOT NULL DEFAULT  '0',
ADD  `player_xeno_milforce` SMALLINT NOT NULL DEFAULT  '0',
ADD  `player_xeno_milforce_tiebreak` SMALLINT NOT NULL DEFAULT  '0',
ADD  `player_effort` INT NOT NULL DEFAULT  '0',
ADD  `player_xeno_victory` TINYINT UNSIGNED NOT NULL DEFAULT  '0';");

            self::applyDbChangeToAllDB("ALTER TABLE `DBPREFIX_card` ADD  `card_damaged` INT UNSIGNED NOT NULL DEFAULT  '0';");
        }
        if ($from_version <= 1705121100) {
            self::DbQuery("CREATE TABLE IF NOT EXISTS `tableau_order` (
  `card_id` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
        }
        if ($from_version <= 1706011355) {
            self::applyDbChangeToAllDB("CREATE TABLE IF NOT EXISTS `DBPREFIX_tableau_order` (
  `card_id` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
        }
        if ($from_version <= 1706051613) {
            self::applyDbChangeToAllDB("ALTER TABLE DBPREFIX_player_production ADD pp_card_id INT UNSIGNED NOT NULL DEFAULT 0;");
        }

        if ($from_version <= 2012142346) {
            try {
                self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_orbteam ADD team_move INT UNSIGNED NOT NULL DEFAULT 0,
    ADD `team_crossbarrier` tinyint(4) NOT NULL DEFAULT 0,
    ADD `team_crosswall` tinyint(4) NOT NULL DEFAULT 0;");
                $state = $this->gamestate->getCurrentMainState()->toArray();
                if ($state['name'] == 'orbActionMove') {
                    $player_id = self::getActivePlayerId();
                    $team_move = $this->getPlayerSurveyTeamMoves($player_id);
                    self::DbQuery("UPDATE orbteam SET team_move=$team_move WHERE team_player=$player_id");
                }
                $team_id = self::getGameStateValue('orbteam');
                if ($team_id) {
                    $team_move = self::getGameStateValue('orbteamhasmoved');
                    self::DbQuery("UPDATE orbteam SET team_move=$team_move WHERE team_id=$team_id");
                }
            } catch (Exception $e) {
                return;
            }
        }

        if ($from_version <= 2101142241) {
            try {
                self::applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_player ADD player_defense_award INT UNSIGNED NOT NULL DEFAULT '0'");
                $expansion = self::getGameStateValue('expansion');
                if ($expansion == 2 || $expansion == 3 ||$expansion == 4) {
                    self::initStat('player', 'goal_first_points', 0);
                    self::initStat('player', 'goal_most_points', 0);
                    self::setGameStateValue('goals', 1);
                }
                if ($expansion == 4) {
                    self::initStat('player', 'prestige_points', 0);
                }
                if ($expansion == 5) {
                    self::initStat('player', 'artefact_points', 0);
                }
                if ($expansion == 7) {
                    self::initStat('player', 'defense_award_points', 0);
                    self::initStat('player', 'greatest_admiral_points', 0);
                    self::initStat('player', 'greatest_contributor_points', 0);
                }
            } catch (Exception $e) {
                return;
            }
        }

        if ($from_version <= 2101211219) {
            self::incGameStateValue('draft', -1);
        }

        if ($from_version <= 2101242249 && self::getGameStateValue('draft') == -1 ) {
            self::setGameStateValue('draft', 0);
        }

        if ($from_version <= 2101242309) {
            $state = $this->gamestate->getCurrentMainState()->name;
            $expansion = self::getGameStateValue('expansion');
            if ($state == 'explore' && $expansion != 7 && $expansion != 8) {
                $cards = $this->cards->getCardsInLocation('explored');
                if (count($cards) == 0) {
                    $this->stExplore();
                }
            }
        }

        if ($from_version <= 2101250815) {
            $state = $this->gamestate->getCurrentMainState()->name;
            $expansion = self::getGameStateValue('expansion');
            if ($state == 'explore' && ($expansion == 7 || $expansion == 8)) {
                $this->stExplore();
            }
        }

        if ($from_version <= 2101250820) {
            if (self::getGameStateValue('draft') != null) {
                self::incGameStateValue('draft', 1);
            }
        }

        if ($from_version <= 2101250845) {
            if (self::getGameStateValue('draft') == 2) {
                $state = $this->gamestate->getCurrentMainState()->name;
                if ($state == 'draft') {
                    $this->cards->moveAllCardsInLocation('hand', 'deck');
                    $this->cards->moveAllCardsInLocation('drafted', 'deck');
                    $this->cards->moveAllCardsInLocation('nextchoice', 'deck');
                    $this->cards->shuffle('deck');

                    $players = self::loadPlayersBasicInfos();
                    foreach ($players as $player_id => $player) {
                        $cards = $this->cards->pickCards(6, 'deck', $player_id);
                    }
                    self::setGameStateValue('draft', 1);
                    $this->gamestate->nextState('initialDiscardHomeWorld');
                }
            }
        }

        if ($from_version <= 2101251341) {
            self::DbQuery('UPDATE global SET global_value=5 WHERE global_id=1 AND global_value=9');
        }

        if ($from_version <= 2101251544) {
            $state = $this->gamestate->getCurrentMainState()->name;
            if ($state == 'phaseChoiceCrystal') {
                $current_player = self::getUniqueValueFromDB('SELECT global_value FROM global WHERE global_id=2');
                if ($current_player == 0) {
                    self::DbQuery('UPDATE global SET global_value=11 WHERE global_id=1');
                    $this->stPhaseChoiceSignal();
                }
            }
        }

        if ($from_version <= 2102101022) {
            $categ_to_type = [
                'a' => 0,
                'b' => 1,
                'sas' => 2,
                'sas2' => 3
            ];
            foreach ($this->orb_cards_types as $orb_type_id => $orb_type) {
                $categ = $this->orb_to_categ($orb_type_id);
                $type_arg = $categ_to_type[$categ];
                self::DbQuery("UPDATE orbcard SET card_type_arg=$type_arg WHERE card_type=$orb_type_id");
            }
            self::DbQuery("UPDATE orbcard SET card_location='deck' WHERE card_location='a'");
        }

        if ($from_version <= 2512181316) {
            $sql = "ALTER TABLE `DBPREFIX_card`
ADD `card_played_round` smallint(3) NOT NULL DEFAULT '-1',
ADD `card_played_phase` smallint(3) NOT NULL DEFAULT '-1',
ADD `card_played_subphase` smallint(2) NOT NULL DEFAULT '-1';";
            self::applyDbUpgradeToAllDB($sql);
        }

        if ($from_version <= 2601310938) {
            $state = $this->gamestate->getCurrentMainState()->name;
            if ($state == 'settlediscard') {
                $active_players = $this->gamestate->getActivePlayerList();
                $card_in_hands = $this->cards->countCardsByLocationArgs('hand');
                foreach($active_players as $player_id) {
                    $card_in_hand = isset($card_in_hands[ $player_id ]) ? $card_in_hands[ $player_id ] : 0;
                    if ($card_in_hand == 0) {
                        $this->gamestate->setPlayerNonMultiactive($player_id, "done");
                    }
                }
            }
        }

        if ($from_version <= 2607071751) {
            $sql = "ALTER TABLE `DBPREFIX_player` ADD `player_boost_snapshot` TEXT NULL DEFAULT NULL;";
            self::applyDbUpgradeToAllDB($sql);
        }

        if ($from_version <= 2607071751) {
            // player_tmp_gene_force was abused to also store prestige special action card usage,
            // Xeno invasion repair charges and bunker power usage; split those out
            // into their own columns.
            $sql = "ALTER TABLE `DBPREFIX_player`
ADD `player_bonus_action_card_used` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
ADD `player_repair_charges` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
ADD `player_bunker_used` SMALLINT UNSIGNED NOT NULL DEFAULT '0';";
            self::applyDbUpgradeToAllDB($sql);
            $sql = "UPDATE `DBPREFIX_player`
SET player_bonus_action_card_used=player_tmp_gene_force,
player_repair_charges=player_tmp_gene_force,
player_bunker_used=player_tmp_gene_force;";
            self::applyDbUpgradeToAllDB($sql);
        }
    }
}
