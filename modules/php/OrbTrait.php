<?php
 /**
  * OrbTrait.php
  *
  * Alien Artifacts expansion: Orb exploration board, exploration teams,
  * Orb card placement, and artefact scoring.
  *
  */

use Bga\GameFramework\UserException;
use Bga\GameFramework\SystemException;

trait OrbTrait
{
    function orb_to_categ($orb_type_id)
    {
        if ($orb_type_id == 41 || $orb_type_id == 42) {
            return 'sas';
        } elseif ($orb_type_id == 43 || $orb_type_id == 44) {
            return 'sas2';
        } else {
            if ($orb_type_id < 10) {
                return 'b';
            } elseif ($orb_type_id >=15 && $orb_type_id <= 20) {
                return 'b';
            } else {
                return 'a';
            }
        }
    }
    ///////////////////////////////////////////////////////////
    ///////////// ORB play (Alien Artifacts) //////////////////

    function orbdraw()
    {
        self::checkAction('orbDraw');
        $player_id = self::getActivePlayerId();

        if (self::getGameStateValue('orbactionnbr') == 0) {
            throw new UserException(self::_("You can only do 2 of the 3 Orb actions so you cannot do the Draw action"));
        }

        $card = $this->orbcards->pickCard('deck', $player_id);

        if ($card !== null) {
            $this->notifyPlayer($player_id, 'pickOrbCards', '', array(
                'cards' => array($card)
               ));

            $deck = self::getCollectionFromDB("SELECT card_type_arg, COUNT(*) FROM orbcard WHERE card_location='deck' GROUP BY card_type_arg", true);

            $this->notifyAllPlayers('drawOrb', clienttranslate('${player_name} draws an Orb card'), array(
                'player_name' => self::getActivePlayerName(),
                'player_id' => $player_id,
                'orbcard_type' =>  $this->orb_to_categ($card['type']),
                'deck' => $deck
               ));
        } else {
            throw new UserException(self::_("There is no remaining orb card : you should pass instead."));
        }
        $this->gamestate->nextState('orbDraw');
    }

    function orbpass()
    {
        self::checkAction('orbPass');
        $player_id = self::getActivePlayerId();
        $this->movePlayerOrbTopPriority($player_id);
    }

    function movePlayerOrbTopPriority($player_id, $bPass = true)
    {
        // Move this player at priority 1
        $current_priority = self::getUniqueValueFromDB("SELECT player_orb_priority FROM player WHERE player_id='$player_id'");

        // All players with an higher priority than the current one : +1
        self::DbQuery("UPDATE player SET player_orb_priority=player_orb_priority+1 WHERE player_orb_priority<$current_priority");

        // This player : priority 1
        self::DbQuery("UPDATE player SET player_orb_priority='1' WHERE player_id='$player_id'");

        // Notify
        $player_to_priority = self::getCollectionFromDB("SELECT player_id, player_orb_priority FROM player", true);

        $this->notifyAllPlayers('changeOrbPriority', '', array('priority' => $player_to_priority));

        if ($bPass) {
            $this->gamestate->nextState('orbPass');
        }
    }

    function orbskip()
    {
        self::checkAction('orbSkip');
        // Skip this phase
        $state = $this->gamestate->getCurrentMainState()->name;
        if ($state == 'orbActionMove') {
            if (self::getGameStateValue('orbteamhasmoved')) {
                throw new SystemException("You cannot skip the action if you have moved a team");
            }
            $player_id = self::getActivePlayerId();
            if (!$this->orbcards->countCardInLocation('hand', $player_id)) {
                $this->notifyAllPlayers('simpleNote', clienttranslate('${player_name} has no orb card, skipping the May Play Orb Card action'), [
                    'player_name' => self::getActivePlayerName()]);
                $this->notifyPlayer($player_id, 'showMessage', '', [
                    'msg' => clienttranslate('You have no orb card, skipping the May Play Orb Card action')
                ]);
                $this->gamestate->nextState('orbDraw');
                return;
            }
        }

        $this->gamestate->nextState('orbSkip');
    }

    function orbendmoveaction()
    {
        self::checkAction('orbEndMoveAction');
        if (self::getGameStateValue('orbteamhasmoved')) {
            self::incGameStateValue('orbactionnbr', -1);
            self::setGameStateValue('orbteamhasmoved', 0);
        }
        $player_id = self::getActivePlayerId();
        if ($this->orbcards->countCardInLocation('hand', $player_id)) {
            $this->gamestate->nextState('orbPlay');
        } else {
            $this->notifyAllPlayers('simpleNote', clienttranslate('${player_name} has no orb card, skipping the May Play Orb Card action'), [
                'player_name' => self::getActivePlayerName()]);
            $this->notifyPlayer($player_id, 'showMessage', '', [
                'msg' => clienttranslate('You have no orb card, skipping the May Play Orb Card action')
            ]);
            $this->gamestate->nextState('orbDraw');
        }
    }

    function orbstop()
    {
        self::checkAction('orbStop');
        if ($this->hasAvailableTeam())
        {
            $this->gamestate->nextState('unselect');
        } else {
            $this->orbendmoveaction();
        }
    }

    // Does the player has any team available to move?
    function hasAvailableTeam()
    {
        $player_id = self::getActivePlayerId();
        $team_move = self::getUniqueValueFromDB("SELECT SUM(team_move) FROM orbteam WHERE team_player='$player_id' AND team_cannotmove!=1");
        if (is_null($team_move)) {
            // All teams have already found an artifact
            return False;
        }
        // Check if player is holding any artifact with a move bonus
        $move_bonus = $this->artefacts->getCardsOfTypeInLocation(5, null, 'hand', $player_id);
        // Does the player have movement points left or an artifact?
        return $team_move || $move_bonus;
    }

    function moveTeamSelect($team_id)
    {
        self::checkAction('orbMoveSelect');

        $player_id = self::getActivePlayerId();

        // Get team
        $team = self::getObjectFromDB("SELECT team_x x, team_y y, team_player, team_cannotmove FROM orbteam WHERE team_id='$team_id'");

        if ($team == null) {
            throw new SystemException("this team does not exists");
        }

        if ($team['team_player'] != self::getActivePlayerId()) {
            throw new SystemException("this team does not belong to you");
        }

        if ($team['team_cannotmove'] == 1) {
            throw new UserException(self::_("This team discovered an artifact this turn and cannot move again."));
        }

        self::setGameStateValue('orbteam', $team_id);

        $this->gamestate->nextState('orbMove');
    }

    function initTeamMove($player_id)
    {
        $team_move = $this->getPlayerSurveyTeamMoves($player_id);
        self::DbQuery("UPDATE orbteam SET team_move=$team_move, team_crossbarrier=0, team_crosswall=0 WHERE team_player=$player_id");
    }

    function getTeamMove($team_id)
    {
        return self::getUniqueValueFromDB("SELECT team_move FROM orbteam WHERE team_id=$team_id");
    }

    function addTeamMove($team_id, $n)
    {
        self::DbQuery("UPDATE orbteam SET team_move=team_move + $n WHERE team_id=$team_id");
    }

    function setTeamMove($team_id, $n)
    {
        self::DbQuery("UPDATE orbteam SET team_move=$n WHERE team_id=$team_id");
    }

    function getTeamCrossBarrier($team_id)
    {
        return self::getUniqueValueFromDB("SELECT team_crossbarrier FROM orbteam WHERE team_id=$team_id");
    }

    function incTeamCrossBarrier($team_id)
    {
        self::DbQuery("UPDATE orbteam SET team_crossbarrier=team_crossbarrier+1 WHERE team_id=$team_id");
    }

    function setTeamCrossBarrier($team_id, $n)
    {
        self::DbQuery("UPDATE orbteam SET team_crossbarrier=$n WHERE team_id=$team_id");
    }

    function getTeamCrossWall($team_id)
    {
        return self::getUniqueValueFromDB("SELECT team_crosswall FROM orbteam WHERE team_id=$team_id");
    }

    function incTeamCrossWall($team_id)
    {
        self::DbQuery("UPDATE orbteam SET team_crosswall=team_crosswall+1 WHERE team_id=$team_id");
    }

    function setTeamCrossWall($team_id, $n)
    {
        self::DbQuery("UPDATE orbteam SET team_crosswall=$n WHERE team_id=$team_id");
    }

    // Used to compare paths in Orb game and sort them by length
    function cmp_path($a, $b)
    {
        return $b['move_points'] - $a['move_points'];
    }

    function moveTeam($x, $y)
    {
        self::checkAction('orbMoveDest');

        $player_id = self::getActivePlayerId();

        $moves = $this->getPossibleMoves();

        $team_id = self::getGameStateValue('orbteam');

        // Get team
        $team = self::getObjectFromDB("SELECT team_x x, team_y y, team_player FROM orbteam WHERE team_id='$team_id'");

        if ($team == null) {
            throw new SystemException("this team does not exists");
        }

        if ($team['team_player'] != self::getActivePlayerId()) {
            throw new SystemException("this team does not belong to you");
        }

        if ($x == $team['x'] && $y == $team['y']) {
            $this->gamestate->nextState('unselect');
        } else {
            if (! isset($moves[ $x.'_'.$y ])) {
                throw new UserException(self::_("This is imposible to move here"));
            }

            // Pick the shortest path
            $paths =  $moves[ $x.'_'.$y ]['paths'];
            usort($paths, 'self::cmp_path');
            $move = $paths[0];

            // Move team there
            $sql = "UPDATE orbteam SET team_x='$x', team_y='$y' WHERE team_id='$team_id'";
            self::DbQuery($sql);

            // Consume move points and bonuses
            $this->setTeamMove($team_id, $move['move_points']);
            $this->setTeamCrossBarrier($team_id, $move['pass_through_barrier']);
            $this->setTeamCrossWall($team_id, $move['pass_through_wall']);

            self::setGameStateValue('orbteamhasmoved', 1);
            $path = $move['path'];

            $this->notifyAllPlayers('simpleNote', clienttranslate('${player_name} moves a team'), array(
                'player_name' => self::getActivePlayerName()
           ));

            foreach ($path as $step) {
                $this->notifyAllPlayers('moveTeam', '', array(
                    'player_id' => $player_id,
                    'team_id' => $team_id,
                    'x' => $step[0],
                    'y' => $step[1]
               ));
            }

            // Check if there is an artefact in the destination square
            $artefact_square = self::getObjectFromDB("SELECT content, content_id FROM orb WHERE x='$x' AND y='$y'");
            $artefact_id = $artefact_square['content_id'];
            if ($artefact_id > 0) {
                // Some artefact has been found !!
                $artefact = $this->artefacts->getCard($artefact_id);
                $this->artefacts->moveCard($artefact_id, 'hand', $player_id);

                $this->notifyPlayer($player_id, 'pickArtefact', '', array(
                    'card' => $artefact,
                    'x' => $x,
                    'y' => $y
               ));

                $this->notifyAllPlayers('destroyArtefact', clienttranslate('${player_name} picks and artefact'), array(
                    'player_name' => self::getActivePlayerName(),
                    'artefact_id' => $artefact_id,
                    'artefact_type' => $this->artefact_types[$artefact['type']]['level'],
                    'player_id' => $player_id
               ));

                self::DbQuery("UPDATE orb SET content_id='0' WHERE x='$x' AND y='$y'");
                self::DbQuery("UPDATE orbteam SET team_cannotmove='1' WHERE team_id='$team_id'");

                if ($artefact_square['content'] == '!') {
                    // Breeding tube : must place another Orb card now
                    $card = $this->orbcards->pickCard('deck', $player_id);

                    if ($card !== null) {
                        $this->notifyPlayer($player_id, 'pickOrbCards', '', array(
                            'cards' => array($card)
                           ));

                        $deck = self::getCollectionFromDB("SELECT card_type_arg, COUNT(*) FROM orbcard WHERE card_location='deck' GROUP BY card_type_arg", true);

                        $this->notifyAllPlayers('drawOrb', clienttranslate('Breeding tube: ${player_name} draws an Orb card and must place it immediately.'), array(
                            'player_name' => self::getActivePlayerName(),
                            'player_id' => $player_id,
                            'orbcard_type' =>  $this->orb_to_categ($card['type']),
                            'deck' => $deck
                           ));

                        // Mark this card as the orb card you MUST play
                        self::DbQuery("UPDATE player SET player_previously_played='".$card['id']."' WHERE player_id='$player_id'");
                        $this->gamestate->nextState('breedingTube');

                        return ;
                    } else {
                        $this->notifyAllPlayers('simpleNote', clienttranslate("Breeding tube cannot apply because Orb card deck is empty"), array());
                    }
                }
            }

            if ($move['move_points'] > 0 && !$artefact_id) {
                $this->gamestate->nextState('continue');
            } else {
                if ($this->hasAvailableTeam()) {
                    $this->gamestate->nextState('unselect');
                } else {
                    $this->orbendmoveaction();
                }
            }
        }
    }

    function orbBackToSas($team_id, $x, $y)
    {
        self::checkAction('orbBackToSas');

        $player_id = self::getActivePlayerId();

        // Get team
        $team = self::getObjectFromDB("SELECT team_x x, team_y y, team_player FROM orbteam WHERE team_id='$team_id'");

        if ($team == null) {
            throw new SystemException("this team does not exists");
        }

        if ($team['team_player'] != self::getActivePlayerId()) {
            throw new SystemException("this team does not belong to you");
        }

        $target = self::getUniqueValueFromDB("SELECT content FROM orb WHERE x='$x' AND y='$y'");

        if ($target == null) {
            throw new SystemException("Invalid destination");
        }
        if ($target != 'D' && $target != 'd') {
            throw new UserException(self::_("You must choose a Sas (blue) square"));
        }

        $sql = "UPDATE orbteam SET team_x='$x', team_y='$y' WHERE team_id='$team_id'";
        self::DbQuery($sql);

        $this->notifyAllPlayers('moveTeam', '', array(
            'player_id' => $player_id,
            'team_id' => $team_id,
            'x' => $x,
            'y' => $y
       ));

        // If all Teams have been moved on Sas space, don't wait for the player to click Done
        $all_teams_on_sas = True;
        $contents = self::getObjectListFromDB("
            SELECT content FROM orb
            JOIN orbteam ON team_x=x and team_y=y
            where team_player=$player_id", true);

        foreach ($contents as $content) {
            if ($content != 'D' && $content !='d') {
                $all_teams_on_sas = False;
            }
        }
        if ($all_teams_on_sas) {
            $this->gamestate->nextState('orbSkip');
        }
    }

    function getPossibleMoves()
    {
        $player_id = self::getActivePlayerId();
        $team_id = self::getGameStateValue('orbteam');

        // Get team
        $team = self::getObjectFromDB("SELECT team_x x, team_y y, team_player FROM orbteam WHERE team_id='$team_id'");

        if ($team == null) {
            throw new SystemException("this team does not exists");
        }

        if ($team['team_player'] != self::getActivePlayerId()) {
            throw new SystemException("this team does not belong to you");
        }

        // Military = permanent + temporary + against alien = Alien Robot Sentry
        $military = $this->getMilitaryForceForWorld($player_id, $this->card_types[ 63 ], false);
        $military = $military['force'];

        $pass_through_barrier = $this->getTeamCrossBarrier($team_id);
        $pass_through_wall = $this->getTeamCrossWall($team_id);


        $orb = $this->getOrb();
        $moves = $this->getMovingPossibilities($team['x'], $team['y'], $orb, $this->getTeamMove($team_id), $military, $pass_through_barrier, $pass_through_wall, array(), array());

        return $moves;
    }

    // Return possible moves ordered by destination
    function getMovingPossibilities($x, $y, $map, $move_points, $military, $pass_through_barrier, $pass_through_wall, $current_path, $result)
    {
        $current_square = $map[ $x ][ $y ];

        $opposite = array(
            'n' => 's',
            's' => 'n',
            'w' => 'e',
            'e' => 'w'
       );

        if ($move_points == 0) {
            return $result ;
        }

        $targets = array(
            array('x' => $x, 'y' => $y+1, 'dir' => 's'),
            array('x' => $x+1, 'y' => $y, 'dir' => 'e'),
            array('x' => $x, 'y' => $y-1, 'dir' => 'n'),
            array('x' => $x-1, 'y' => $y, 'dir' => 'w')
       );

        if ($current_square['content'] == 'D') {
            // Main sas => can go to 6 destination insted of one
            $targets = array(
                array('x' => -1, 'y' => 0, 'dir' => 'n'),
                array('x' => 0, 'y' => 0, 'dir' => 'n'),
                array('x' => 1, 'y' => 1, 'dir' => 'e'),
                array('x' => -1, 'y' => 2, 'dir' => 's'),
                array('x' => 0, 'y' => 2, 'dir' => 's'),
                array('x' => -2, 'y' => 1, 'dir' => 'w')
           );
        }

        if ($current_square['content'] == 'T') {
            // Find all teleporters, and add them to possible targets
            foreach ($map as $mapx => $column) {
                foreach ($column as $mapy => $mapsq) {
                    if ($mapsq['content'] == 'T' && ($mapx != $x || $mapy != $y)) {
                        $targets[] = array('x' => $mapx, 'y' => $mapy, 'dir' => 't');
                    }
                }
            }
        }

        foreach ($targets as $target) {
            $target_x = $target['x'];
            $target_y = $target['y'];
            $direction = $target['dir'];

            $bCanMoveHere = false;
            $bMustStopHere = false;
            $bBarrier = false;
            $bWall =false;

            if (isset($map[ $target_x ]) && isset($map[ $target_x ][ $target_y ])) {
                // The target square is within the map
                $target_square = $map[ $target_x ][ $target_y ];

                // Check walls
                if ($direction != 't') {
                    $wall = $current_square[$direction];
                    $opposite_wall = $target_square[ $opposite[ $direction ] ];

                    // Take the most restrictive of the 2
                    $constraint = 0;
                    if ($wall == 'X' || $opposite_wall == 'X') {
                        $constraint = 'X';
                    } else {
                        $constraint = (int)max($wall, $opposite_wall);
                    }

                    if ($constraint === 0) {
                        $bCanMoveHere = true;
                    } elseif ($constraint == 'X') {
                        // Can we cross walls?
                        if ($pass_through_wall) {
                            $bCanMoveHere = true;
                            $bWall = true;
                        }
                    } else {
                        // Depends on military or use a pass through bonus
                        if ($military >= $constraint) {
                            $bCanMoveHere = true;
                        } elseif ($pass_through_barrier) {
                            $bCanMoveHere = true;
                            $bBarrier = true;
                        } elseif ($pass_through_wall) {
                            $bCanMoveHere = true;
                            $bWall = true;
                        }
                    }
                } else {
                    $bCanMoveHere = true;   // Teleporter case
                }

                if ($target_square['content_id'] > 0) {
                    // There is an artefact here!
                    $bMustStopHere = true;
                }
            }

            // Possible destination => build result
            if ($bCanMoveHere) {
                $target_id = $target_x.'_'.$target_y;
                $possible_move = $current_path;
                $possible_move[] = array($target_x,$target_y);
                $path_cost = array(
                    'move_points' => $move_points - 1,
                    'pass_through_barrier' => $pass_through_barrier - $bBarrier,
                    'pass_through_wall' => $pass_through_wall - $bWall,
                    'path' => $possible_move
                );

                $bGoodPath = True;
                if (!isset($result[ $target_id ])) {
                    // First path to here
                    $result[$target_id] = array(
                        'dest' => array('x' => $target_x, 'y' => $target_y),
                        'paths' => array($path_cost)
                   );
                } else {
                    // We compare this path to all the ones we already found
                    foreach($result[$target_id]['paths'] as $existing_path) {
                        if ($existing_path['move_points'] >= $path_cost['move_points']
                            && $existing_path['pass_through_barrier'] >= $path_cost['pass_through_barrier']
                            && $existing_path['pass_through_wall'] >= $path_cost['pass_through_wall']) {
                                // We already have a better path to here
                                $bGoodPath = False;
                                break;
                        }
                    }

                    // This path is better on at least one resource compared to each known paths
                    if ($bGoodPath) {
                        $result[$target_id]['paths'][] = $path_cost;
                    }
                }

                // recursion
                if (!$bMustStopHere && $bGoodPath) {
                    $result = $this->getMovingPossibilities(
                        $target_x,
                        $target_y,
                        $map,
                        $move_points - 1,
                        $military,
                        $pass_through_barrier - $bBarrier,
                        $pass_through_wall - $bWall,
                        $possible_move,
                        $result
                    );
                }
            }
        }

        return $result;
    }

    // Handle the error when placing Orb card. Throw an exception if trying to confirm an invalid placement,
    // otherwise
    function orbCardPlacementError($msg, $args, $bConfirmed = true)
    {
        $args['error'] = $msg;
        if ($bConfirmed) {
            throw new UserException(self::_($msg));
        } else {
            $this->notifyPlayer(self::getCurrentPlayerId(), 'putCardOnOrb', '', $args);
        }
    }

    function getCardUnderCurrentTeam()
    {
        $team_id = self::getGameStateValue('orbteam');
        $team = self::getObjectFromDB("SELECT team_x x, team_y y FROM orbteam WHERE team_id='$team_id'");

        // Get the card who is under this position
        $currentTeamCard = null;
        $orbcards = self::getObjectListFromDB(
            "SELECT card_id as id,
                    card_x as min_x,
                    card_y as min_y,
                    card_ori,
                    card_location_arg as 'order'
            FROM orbcard
            WHERE card_location='orb'
            ORDER BY card_location_arg ASC");
        foreach ($orbcards as $i=> $orbcard) {
            $orbcard['max_x'] = $orbcard['min_x'] + ($orbcard['card_ori']%2 == 0 ? 1 : 2);
            $orbcards[$i]['max_x'] = $orbcard['max_x'];
            $orbcard['max_y'] = $orbcard['min_y'] + ($orbcard['card_ori']%2 == 0 ? 2 : 1);
            $orbcards[$i]['max_y'] = $orbcard['max_y'];

            if ($team['x']>=$orbcard['min_x'] && $team['x']<=$orbcard['max_x'] && $team['y']>=$orbcard['min_y'] && $team['y']<=$orbcard['max_y']) {
                $currentTeamCard = $orbcard;
            }
        }

        if ($currentTeamCard === null) {
            throw new SystemException("Could not find the orb card at team place ".$team['x'].','.$team['y']);
        }

        // We've found the topmost card on which the team is standing. Record visible squares.
        $currentTeamCard['visible_squares'] = [];
        for ($x = $currentTeamCard['min_x']; $x <= $currentTeamCard['max_x']; ++$x) {
            for ($y = $currentTeamCard['min_y']; $y <= $currentTeamCard['max_y']; ++$y) {
                $currentTeamCard['visible_squares'][] = ['x' => $x, 'y' => $y];
            }
        }

        // Now, we look for other cards that might be overlapping parts of it
        foreach ($orbcards as $orbcard) {
            if ($orbcard['order'] <= $currentTeamCard['order']) {
                continue;
            }

            foreach ($currentTeamCard['visible_squares'] as $i => $sq) {
                if ($sq['x']>=$orbcard['min_x'] && $sq['x']<=$orbcard['max_x'] && $sq['y']>=$orbcard['min_y'] && $sq['y']<=$orbcard['max_y']) {
                    // This square is overlapped
                    unset($currentTeamCard['visible_squares'][$i]);
                }
            }
        }

        return $currentTeamCard;
    }

    function placeOrbCard($id, $x, $y, $ori, $bCheck = false, $bConfirmed = true)
    {
        if ($bCheck) {
            self::checkAction('orbPlay');
        }

        $state = $this->gamestate->getCurrentMainState()->name;
        $card = $this->orbcards->getCard($id);
        $args = [ 'card_id' => $id, 'x' => $x, 'y' => $y, 'ori' => $ori ];


        if ($card == null) {
            throw new SystemException("This orb card does not exists");
        }

        if ($bCheck) {
            if ($card['location'] != 'hand' && $card['location_arg'] != self::getActivePlayerId()) {
                throw new SystemException("This orb card is not in your hand");
            }
        }

        $teams = $this->getTeamsByLocation();
        $player_id = self::getActivePlayerId();

        $type = $this->orb_cards_types[ $card['type'] ];

        if ($state == 'additionalSas') {
            // Must check if this is a Sas card
            if ($card['type'] != 43 && $card['type'] != 44) {
                throw new UserException(self::_("You must place the new airlock card which is in your hand"));
            }
        }
        if ($state == 'breedingTube') {
            $lastest = self::getUniqueValueFromDB("SELECT player_previously_played FROM player WHERE player_id='$player_id'");

            if ($lastest != $id) {
                throw new UserException(self::_("You must choose the very latest orb card you just draw"));
            }

            $currentTeamCard = $this->getCardUnderCurrentTeam();
        }

        $new_squares_to_add = array(); // New squares that will be added to the orb, with their x_y, content and walls

        $new_squares_to_add[] = array('x' => $x, 'y'=>$y, 'content' => $type['content'][1][0], 'walls' => array(
            'n' => $type['verwalls'][0][1],
            'e' => $type['horwalls'][1][0],
            's' => $type['verwalls'][1][1],
            'w'=>  $type['horwalls'][2][0]));
        $new_squares_to_add[] = array('x' => $x+1, 'y'=>$y, 'content' => $type['content'][1-1][0], 'walls' => array(
            'n' => $type['verwalls'][0][1-1],
            'e' => $type['horwalls'][1-1][0],
            's' => $type['verwalls'][1][1-1],
            'w'=>  $type['horwalls'][2-1][0]));
        $new_squares_to_add[] = array('x' => $x, 'y'=>$y+1, 'content' => $type['content'][1][0+1], 'walls' => array(
            'n' => $type['verwalls'][0+1][1],
            'e' => $type['horwalls'][1][0+1],
            's' => $type['verwalls'][1+1][1],
            'w'=>  $type['horwalls'][2][0+1]));
        $new_squares_to_add[] = array('x' => $x+1, 'y'=>$y+1, 'content' => $type['content'][1-1][0+1], 'walls' => array(
            'n' => $type['verwalls'][0+1][1-1],
            'e' => $type['horwalls'][1-1][0+1],
            's' => $type['verwalls'][1+1][1-1],
            'w'=>  $type['horwalls'][2-1][0+1]));
        $new_squares_to_add[] = array('x' => $x, 'y'=>$y+2, 'content' => $type['content'][1][0+2], 'walls' => array(
            'n' => $type['verwalls'][0+2][1],
            'e' => $type['horwalls'][1][0+2],
            's' => $type['verwalls'][1+2][1],
            'w'=>  $type['horwalls'][2][0+2]));
        $new_squares_to_add[] = array('x' => $x+1, 'y'=>$y+2, 'content' => $type['content'][1-1][0+2], 'walls' => array(
            'n' => $type['verwalls'][0+2][1-1],
            'e' => $type['horwalls'][1-1][0+2],
            's' => $type['verwalls'][1+2][1-1],
            'w'=>  $type['horwalls'][2-1][0+2]));

        for ($i=0; $i<$ori; $i++) {
            // Rotate everything +90°

            foreach ($new_squares_to_add as $square_id => $sq) {
                $base_x = ($i%2 == 0) ? 2 : 1;

                $new_squares_to_add[$square_id] = array(
                    'x' => $x + ($base_x-($sq['y']-$y)),
                    'y' => $y + ($sq['x'] - $x),
                    'content' => $sq['content'],
                    'walls' => array(
                        'n' => $sq['walls']['w'],
                        'e' => $sq['walls']['n'],
                        's' => $sq['walls']['e'],
                        'w' => $sq['walls']['s']
                   )
               );
            }
        }

        if ($state == 'breedingTube') {
            foreach ($new_squares_to_add as $sq) {
                foreach ($currentTeamCard['visible_squares'] as $vsq) {
                    if ($sq['x'] >= $vsq['x']-1 && $sq['x'] <= $vsq['x']+1 && $sq['y'] >= $vsq['y']-1 && $sq['y'] <= $vsq['y']+1) {
                        $this->orbCardPlacementError(
                            clienttranslate("While placing this orb card, you cannot override any part (even the borders) of the Orb card where the team which discover the artifact is located"),
                            $args, $bConfirmed);
                        return;
                    }
                }
            }
        }

        // Now, we must add these new squares to existings
        $orb = $this->getOrb();

        $directions = array(
            'n' => array('x' => 0, 'y' => -1),
            'e' => array('x' => 1, 'y' => 0),
            's' => array('x' => 0, 'y' => 1),
            'w' => array('x' => -1, 'y' => 0)
       );

        if ($bCheck) {
            $bAtLeastOneNonExistingSquare = false;
            $bBorderCheck = false;
            $bLinkedToOrb = false;

            $new_squares_defined = array();
            foreach ($new_squares_to_add as $square) {
                $new_squares_defined[ $square['x'].'_'.$square['y'] ] = true;
            }

            foreach ($new_squares_to_add as $square) {
                // Are we ABOVE an existing part of the orb?
                $bAboveExistingOrb = false;
                if (isset($orb[ $square['x'] ]) && isset($orb[ $square['x'] ][ $square['y'] ])) {
                    $bAboveExistingOrb = true;
                    $bLinkedToOrb = true;

                    // Check if we are not over something we should not
                    if ($square['x'] >= -2 && $square['x'] <=1 && $square['y']>=0 && $square['y']<=2) {
                        $this->orbCardPlacementError(
                            clienttranslate("You cannot overwrite an Airlock place"), $args, $bConfirmed);
                        return;
                    }

                    // Cannot overwrite team or artefact
                    if ($orb[ $square['x'] ][ $square['y'] ]['content_id'] > 0) {
                        $this->orbCardPlacementError(
                            clienttranslate("You cannot overwrite an artefact"), $args, $bConfirmed);
                        return;
                    }

                    if ($orb[ $square['x'] ][ $square['y'] ]['content'] == '.' || $orb[ $square['x'] ][ $square['y'] ]['content'] == 'D' || $orb[ $square['x'] ][ $square['y'] ]['content'] == 'd') {
                        $this->orbCardPlacementError(
                            clienttranslate("You cannot overwrite an Airlock place"), $args, $bConfirmed);
                        return;
                    }


                    if (isset($teams[ $square['x'] ]) && isset( $teams[ $square['x'] ][ $square['y'] ])) {
                        $this->orbCardPlacementError(clienttranslate("You cannot overwrite a team"), $args, $bConfirmed);
                        return;
                    }
                } else {
                    $bAtLeastOneNonExistingSquare = true;
                }


                // In which direction is the card border?
                foreach ($directions as $dir => $delta) {
                    $neighbour_x = $square['x'] + $delta['x'];
                    $neighbour_y = $square['y'] + $delta['y'];

                    $bExistingNeighbour = (isset($orb[ $neighbour_x ]) && isset($orb[ $neighbour_x ][ $neighbour_y ]));

                    if ($bExistingNeighbour) {
                        $bLinkedToOrb = true;
                    }

                    if (! isset($new_squares_defined[ $neighbour_x.'_'.$neighbour_y ])) {
                        // This is a new card border !
                        // Is this also an existing orb border?

                        $existing_border = null;
                        if ($bAboveExistingOrb && ! $bExistingNeighbour) {
                            $existing_border = $orb[ $square['x'] ][ $square['y'] ][ $dir ];
                        } elseif (! $bAboveExistingOrb && $bExistingNeighbour) {
                            $existing_border = $orb[ $neighbour_x ][ $neighbour_y ][ $this->oppdir($dir) ];
                        }

                        if ($existing_border !== null) {
                            $card_border = $square['walls'][ $dir ];

                            if ($existing_border === 0) {
                               // Can be replaced by anything!
                            } elseif ($existing_border == 'X') {
                                if ($card_border != 'X') {
                                    $this->orbCardPlacementError(
                                        clienttranslate("You cannot replace a wall on the existing Orb border by a less restrictive barrier on the border of this new Orb card."),
                                        $args, $bConfirmed);
                                    return;
                                }
                            } else {
                                // Barrier with number
                                if ($card_border == 'X') {
                                    // okay, replacing a barrier by a wall
                                } else {
                                    if ($card_border < $existing_border) {
                                        $this->orbCardPlacementError(
                                            clienttranslate("You cannot replace a barrier on the existing Orb border by a less restrictive barrier on the border of this new Orb card."),
                                            $args, $bConfirmed);
                                        return;
                                    }
                                }
                            }
                        }
                    }
                }
            }


            if (! $bLinkedToOrb) {
                $this->orbCardPlacementError(
                    clienttranslate("The card must be linked to existing Orb."), $args, $bConfirmed);
                return;
            }

            if (! $bAtLeastOneNonExistingSquare) {
                $this->orbCardPlacementError(
                    clienttranslate("At least one of the new square must not cover any part of the existing Orb."),
                    $args, $bConfirmed);
                return;
            }
        }

        if ($bConfirmed) {
            // Decrement orb action count
            if ($state != 'breedingTube') {
                self::incGameStateValue('orbactionnbr', -1);
            }

            // Place this card on the Orb
            $max_orb = self::getUniqueValueFromDB("SELECT COALESCE(MAX(card_location_arg), 0) FROM orbcard WHERE card_location='orb' ");

            // At first, we must nullify all existing "opposite walls" because the new card will overwrite them
            $wall_to_nullify = array();
            if ($i%2 == 0) {
                //  New card is vertical
                $wall_to_nullify = array(
                    array('x' => $x, 'y'=>$y-1, 'w' => 's'),
                    array('x' => $x+1, 'y'=>$y-1, 'w' => 's'),

                    array('x' => $x+2, 'y'=>$y, 'w' => 'w'),
                    array('x' => $x+2, 'y'=>$y+1, 'w' => 'w'),
                    array('x' => $x+2, 'y'=>$y+2, 'w' => 'w'),

                    array('x' => $x+1, 'y'=>$y+3, 'w' => 'n'),
                    array('x' => $x, 'y'=>$y+3, 'w' => 'n'),

                    array('x' => $x-1, 'y'=>$y+2, 'w' => 'e'),
                    array('x' => $x-1, 'y'=>$y+1, 'w' => 'e'),
                    array('x' => $x-1, 'y'=>$y, 'w' => 'e'),
            );
            } else {
                // New card is horizontal
                $wall_to_nullify = array(
                    array('x' => $x, 'y'=>$y-1, 'w' => 's'),
                    array('x' => $x+1, 'y'=>$y-1, 'w' => 's'),
                    array('x' => $x+2, 'y'=>$y-1, 'w' => 's'),

                    array('x' => $x+3, 'y'=>$y, 'w' => 'w'),
                    array('x' => $x+3, 'y'=>$y+1, 'w' => 'w'),

                    array('x' => $x+2, 'y'=>$y+2, 'w' => 'n'),
                    array('x' => $x+1, 'y'=>$y+2, 'w' => 'n'),
                    array('x' => $x, 'y'=>$y+2, 'w' => 'n'),

                    array('x' => $x-1, 'y'=>$y+1, 'w' => 'e'),
                    array('x' => $x-1, 'y'=>$y, 'w' => 'e'),
            );
            }
            foreach ($wall_to_nullify as $nullwall) {
                $sql = "UPDATE orb SET wall_".$nullwall['w']."='0' WHERE x='".$nullwall['x']."' AND y='".$nullwall['y']."' ";
                self::DbQuery($sql);
            }

            $new_artefacts = array();
            $sql = "REPLACE INTO orb (x,y,content,wall_n,wall_s,wall_e,wall_w,content_id) VALUES ";
            $sql_values = array();
            $new_orb_squares = array();
            $airlock_location = null;
            foreach ($new_squares_to_add as $square) {
                $content_id = 0;
                if ($square['content'] == 'A' || $square['content'] == '!') {
                    // "A" type artefact
                    $art = $this->artefacts->pickCardForLocation('A', 'orb');
                    $content_id = $art['id'];
                    $new_artefacts[] = array('id' => $art['id'], 'type_arg' => 0, 'x' => $square['x'], 'y' => $square['y'], 'content' => $square['content']);
                } elseif ($square['content'] == 'B') {
                    // "B" type artefact
                    $art = $this->artefacts->pickCardForLocation('B', 'orb');
                    $content_id = $art['id'];
                    $new_artefacts[] = array('id' => $art['id'], 'type_arg' => 1, 'x' => $square['x'], 'y' => $square['y'], 'content' => $square['content']);
                }

                if ($square['content'] == 'd') {
                    $airlock_location = array('x' => $square['x'], 'y' => $square['y']);
                }

                $sql_values[] = "('".$square['x']."','".$square['y']."','".$square['content']."','".$square['walls']['n']."','".$square['walls']['s']."','".$square['walls']['e']."','".$square['walls']['w']."','$content_id')";
            }
            $sql .= implode(',', $sql_values);
            self::DbQuery($sql);

            self::DbQuery("UPDATE orbcard SET card_location='orb', card_location_arg='".($max_orb+1)."', card_x='$x', card_y='$y', card_ori='$ori' WHERE card_id='$id'");


            if ($bCheck) {
                $this->notifyAllPlayers("updateOrb", clienttranslate('${player_name} plays an Orb card'), array(
                    'player_name' => self::getActivePlayerName(),
                    'newsquares' => $new_squares_to_add,
                    'orbcard' => $card,
                    'orbcard_type' =>  $this->orb_to_categ($card['type']),
                    'player_id' => self::getActivePlayerId(),
                    'x' => $x,
                    'y' => $y,
                    'ori' => $ori,
                    'artefacts' => $new_artefacts
            ));
            }
        } else {
            $this->notifyPlayer($player_id, 'putCardOnOrb', '',
                 [
                     'card_id' => $id,
                     'x' => $x,
                     'y' => $y,
                     'ori' => $ori,
                 ]);
            return;
        }

        if ($state == 'additionalSas') {
            // Must add a new team at airlock location
            $sql = "INSERT INTO orbteam (team_x,team_y,team_player) VALUES ('".$airlock_location['x']."','".$airlock_location['y']."','$player_id')";
            self::DbQuery($sql);
            $team_id = self::DbGetLastId();

            $this->notifyAllPlayers("orbteam", clienttranslate('${player_name} has a new Survey team'), array(
                'player_name' => self::getActivePlayerName(),
                'team_id' => $team_id,
                'x' => $airlock_location['x'],
                'y' => $airlock_location['y'],
                'player' => $player_id,
                'player_id' => $player_id
           ));
        }

        if ($state == 'breedingTube') {
            if ($this->hasAvailableTeam()) {
                $this->gamestate->nextState('moveOtherTeam');
            } else {
                $this->orbendmoveaction();
            }
        } elseif ($bCheck) {
            if (self::getGameStateValue('orbactionnbr') > 0) {
                $this->gamestate->nextState('orbDraw');
            } else {
                $this->gamestate->nextState('orbNextPlayer');
            }
        }
    }

    function getTeams()
    {
        return self::getCollectionFromDB("SELECT team_id, team_x x, team_y y, team_player player FROM orbteam");
    }

    function getTeamsByLocation()
    {
        return self::getDoubleKeyCollectionFromDB("SELECT team_x x, team_y y, team_id, team_player player FROM orbteam");
    }

    function placeTeam($x, $y)
    {
        self::checkAction('orbTeamplace');

        $teams = $this->getTeamsByLocation();
        $players = self::loadPlayersBasicInfos();

        $teams_nbr = self::getUniqueValueFromDB("SELECT COUNT(team_id) FROM orbteam ");

        $player_id = self::getActivePlayerId();

        if ($teams_nbr == 4) {
            // Specific case : must play on 0,1
            $x=0;
            $y=1;
        } else {
            if (isset($teams[$x]) && isset($teams[$x][$y])) {
                throw new UserException(self::_("There is already a team there"));
            }


            // Acceptable spaces : on corners
            if (($x==1 && $y == 0)
             || ($x==1 && $y == 2)
             || ($x==-2 && $y == 0)
             || ($x==-2 && $y == 2)
            ) {
            } else {
                throw new UserException(self::_("You must choose a corner of the sas"));
            }
        }

        // Okay, place a team there
        $sql = "INSERT INTO orbteam (team_x,team_y,team_player) VALUES ('$x','$y','$player_id')";
        self::DbQuery($sql);
        $team_id = self::DbGetLastId();

        $this->notifyAllPlayers("orbteam", clienttranslate('${player_name} chooses a starting place for his/her team'), array(
            'player_name' => self::getActivePlayerName(),
            'team_id' => $team_id,
            'x' => $x,
            'y' => $y,
            'player' => $player_id,
            'player_id' => $player_id
       ));

        $this->gamestate->nextState('orbTeamplace');
    }

    function getOrbPlayNextPlayer($bReverse = false)
    {
        $result = array();
        $prev_player = null;

        if ($bReverse) {
            $player_to_priority = self::getCollectionFromDB("SELECT player_id, player_orb_priority FROM player ORDER BY player_orb_priority DESC");
        } else {
            $player_to_priority = self::getCollectionFromDB("SELECT player_id, player_orb_priority FROM player ORDER BY player_orb_priority ASC");
        }

        foreach ($player_to_priority as $player_id => $priority) {
            if ($prev_player != null) {
                $result[ $prev_player ] = $player_id;
            }

            $prev_player = $player_id;
        }

        $result[ $prev_player ] = null;

        return $result;
    }

    function stOrbNextPlayer()
    {
        $players = self::loadPlayersBasicInfos();
        $players_nb = count($players);

        $player_id = self::getActivePlayerId();
        self::DbQuery("UPDATE player SET player_just_played='1' WHERE player_id='$player_id'");
        $player_has_played = self::getCollectionFromDB("SELECT player_id,player_just_played FROM player", true);

        $orbNextPlayer = $this->getOrbPlayNextPlayer();

        $next = self::getActivePlayerId();
        // Active next player
        do {
            $next = $orbNextPlayer[ $next ];
        } while ($next !== null && $player_has_played[ $next ] == 1);

        if ($next === null) {
            self::setGameStateValue('orbactionnbr', 0);
            $this->gamestate->nextState('end');
        } else {
            $this->gamestate->changeActivePlayer($next);
            self::giveExtraTime(self::getActivePlayerId());

            $player_phases = $this->getPhaseChoice(1);
            if (isset($player_phases[ $next ]) && $player_phases[ $next ] >=100) {
                self::setGameStateValue('orbactionnbr', 3);
            } else {
                self::setGameStateValue('orbactionnbr', 2);
            }
            $this->initTeamMove($next);
            self::setGameStateValue('orbteamhasmoved', 0);

            $this->gamestate->nextState('next');
        }
    }


    function stInitialOrbNextPlayer()
    {
        $players = self::loadPlayersBasicInfos();
        $players_nb = count($players);
        $orbNextPlayer = $this->getOrbPlayNextPlayer(true);

        $teams_nbr = self::getUniqueValueFromDB("SELECT COUNT(team_id) FROM orbteam ");

        if ($teams_nbr < $players_nb) {
            // Still some teams to place
            $next = $orbNextPlayer[ self::getActivePlayerId() ];

            $this->gamestate->changeActivePlayer($next);

            $this->gamestate->nextState('next');
        } else {
            $this->gamestate->nextState('end');
        }
    }

    function oppdir($dir)
    {
        if ($dir == 'n') {
            return 's';
        } elseif ($dir == 's') {
            return 'n';
        } elseif ($dir == 'e') {
            return 'w';
        } elseif ($dir == 'w') {
            return 'e';
        }
    }

    // Get Orb
    function getOrb()
    {
        $orb = self::getDoubleKeyCollectionFromDB("SELECT x, y, content, content_id, wall_n n, wall_w w, wall_s s, wall_e e FROM orb ");

        return $orb;
    }

    function placeSasCards()
    {
        $cards = $this->orbcards->getCardsInLocation('sas');
        foreach ($cards as $card) {
            if ($card['type'] == 41) {
                $this->placeOrbCard($card['id'], 0, 0, 0, false);
            } elseif ($card['type'] == 42) {
                $this->placeOrbCard($card['id'], -2, 0, 0, false);
            }
        }
    }


    function useArtefact($artifact_id, $reason, $player_id = null)
    {
        if ($player_id === null) {
            $player_id = self::getCurrentPlayerId();
            $player_name = self::getCurrentPlayerName();
        } else {
            $players = self::loadPlayersBasicInfos();
            $player_name = $players[ $player_id ]['player_name'];
        }

        $team_id = self::getGameStateValue('orbteam');
        $art = $this->artefacts->getCard($artifact_id);
        if ($art == null) {
            throw new SystemException("Invalid artifact");
        }
        if ($art['location'] != 'hand') {
            throw new SystemException("Invalid artifact");
        }
        if ($art['location_arg'] != $player_id) {
            throw new SystemException("Invalid artifact");
        }

        $state = $this->gamestate->getCurrentMainState()->name;
        $bConsumeArt = false;
        $bDefered = $state == 'settle';
        $log = '';
        $logarg = array(
                            "player_id" => $player_id,
                            "player_name" => $player_name,
                            "artifact_id" => $artifact_id,
                            "artifact_type" => $art['type']
                       );

        if ($reason == 'crossbarrier') {
            self::checkAction('useCrossBarier');

            if ($art['type'] != 6 && $art['type'] != 8 && $art['type'] != 11 && $art['type'] != 12) {
                throw new SystemException("Invalid artifact");
            }

            $bConsumeArt = true;
            $log = clienttranslate('${player_name} uses an artifact to cross one barrier');

            $this->incTeamCrossBarrier($team_id);
            $this->gamestate->nextState('useCrossBarier');
        }
        if ($reason == 'crosswall') {
            self::checkAction('useCrossBarier');

            if ($art['type'] != 3) {
                throw new SystemException("Invalid artifact");
            }

            $bConsumeArt = true;
            $log = clienttranslate('${player_name} uses an artifact to cross one wall or barrier');


            $this->incTeamCrossWall($team_id);
            $this->gamestate->nextState('useCrossBarier');
        }
        if ($reason == 'movebonus') {
            self::checkAction('useCrossBarier');

            if ($art['type'] != 5) {
                throw new SystemException("Invalid artifact");
            }

            $bConsumeArt = true;
            $log = clienttranslate('${player_name} uses an artifact to move 4 more squares');

            $this->addTeamMove(self::getGameStateValue('orbteam'), 4);
            $this->gamestate->nextState('useCrossBarier');
        }
        if ($reason == 'militaryboost') {
            self::checkAction('militaryboost');

            if ($art['type'] != 6 && $art['type'] != 8 && $art['type'] != 11 && $art['type'] != 12 && $art['type'] != 4) {
                throw new SystemException("Invalid artifact");
            }

            $boost = 1;
            if ($art['type'] == 6 || $art['type'] == 11 || $art['type'] == 4) {
                $boost = 2;
            }
            if ($art['type'] == 12) {
                $boost = 3;
            }


            $bConsumeArt = true;
            $log = clienttranslate('${player_name} uses an artifact to get +${boost} military');
            $logarg['boost'] = $boost;

            if ($art['type'] == 4) {
                $sql = "UPDATE player SET player_tmp_gene_force=player_tmp_gene_force+$boost WHERE player_id='$player_id' ";
            } else {
                $sql = "UPDATE player SET player_tmp_milforce=player_tmp_milforce+$boost WHERE player_id='$player_id' ";
            }
            self::DbQuery($sql);

            if ($art['type'] == 4) {
                $this->notifyPlayer($player_id, 'updateTmpMilforce', '',
                                    array(
                                        'tmp' => self::getUniqueValueFromDB("SELECT player_tmp_milforce FROM player WHERE player_id='$player_id'"),
                                        'player' => $player_id
                                   ));
            }
        }
        if ($reason == 'sell') {
            self::checkAction('sell');

            if ($art['type'] != 2 && $art['type'] != 9) {
                throw new SystemException("Invalid artifact");
            }

            $bConsumeArt = true;
            $log = clienttranslate('${player_name} uses an artifact as an Alien resource');
            $price = 5;
            $good_type = 4;
            $price += $this->getSellBonusForGoodType($player_id, 4, 0);


            // Give cards to player
            $this->drawCardForPlayer($player_id, $price);
            $this->notifyAllPlayers('drawCards_log', clienttranslate('${player_name} sells a ${good_name} for ${card_nbr} card(s)'),
                                        array(
                                            "i18n" => array("good_name"),
                                            "player_name" => $player_name,
                                            "player_id" => $player_id,
                                            "card_nbr" => $price,
                                            "good_name" => $this->good_types_untr[ $good_type ]
                                       ));


            $this->gamestate->setPlayerNonMultiactive($player_id, "sellcleared");
        }
        if ($reason == 'consume') {
            self::checkAction('consume');

            if ($art['type'] != 2 && $art['type'] != 9) {
                throw new SystemException("Invalid artifact");
            }

            $bConsumeArt = true;
            $log = clienttranslate('${player_name} uses an artifact as an Alien resource');
        }
        if ($reason == 'goodForMilitary') {
            self::checkAction('militaryboost');

            if ($art['type'] != 2 && $art['type'] != 9) {
                throw new SystemException("Invalid artifact");
            }

            $bConsumeArt = true;
            $log = clienttranslate('${player_name} uses an artifact as an Alien resource');
        }
        if ($reason == 'scoring') {
            $log = '';
            $bConsumeArt = true;
        }


        if ($bConsumeArt) {
            $this->artefacts->moveCard($artifact_id, 'tableau', $player_id);
            if ($bDefered) {
                $this->defered_notifyAllPlayers($this->notif_defered_id, 'consumeArtifact', $log, $logarg);
            } else {
                $this->notifyAllPlayers('consumeArtifact', $log, $logarg);
            }

            $artpoints = $this->artefact_types[ $art['type'] ]['vp'];

            if ($artpoints > 0) {
                $pscore = $this->updatePlayerScore($player_id, $artpoints, false);
                self::incStat($artpoints, 'artefact_points', $player_id);

                $this->notifyAllPlayers('updateScore', clienttranslate('${player_name} scores ${score_delta} with artifact(s).'),
                                                array(
                                                    "player_name" => $player_name,
                                                    "player_id" => $player_id,
                                                    "score" => $pscore['score'],
                                                    "vp" => $pscore['vp'],
                                                    "score_delta" => $artpoints,
                                                    "vp_delta" => 0     // Note: "consumption" vp
                                               ));
            }
        }
    }
    function scoreRemainingArtefacts()
    {
        // Consume all remaining artifacts to score their points
        $expansion = self::getGameStateValue('expansion');
        if ($expansion != 5) {
            return ;
        }


        $remaining = $this->artefacts->getCardsInLocation('hand');

        foreach ($remaining as $artefact) {
            $this->useArtefact($artefact['id'], 'scoring', $artefact['location_arg']);
        }

        // Then, score "feeding stations" (artefacts 4 and 14)
        $all_artefacts = $this->artefacts->getCardsInLocation('tableau');
        $player_pt_per_station = array();
        foreach ($all_artefacts as $artefact) {
            if ($artefact['type'] == 4 || $artefact['type'] == 14) {
                if (! isset($player_pt_per_station[ $artefact['location_arg'] ])) {
                    $player_pt_per_station[ $artefact['location_arg'] ] = 0;
                }
                $player_pt_per_station[ $artefact['location_arg'] ]++;
            }
        }

        $visible_stations = self::getUniqueValueFromDB("SELECT COUNT(content) FROM orb WHERE content='S'");

        $players = self::loadPlayersBasicInfos();
        foreach ($player_pt_per_station as $player_id => $multiplier) {
            $points = $multiplier * $visible_stations;
            $pscore = $this->updatePlayerScore($player_id, $points, false);
            self::incStat($points, 'artefact_points', $player_id);

            $this->notifyAllPlayers('updateScore', clienttranslate('Visible feeding station : ${player_name} scores ${points_nbr} VP with ${nbr} artefacts x ${stations} visible stations.'),
                                            array(
                                                "player_id" => $player_id,
                                                "score_delta" => $points,
                                                "vp_delta" => 0,
                                                "score" => $pscore['score'],
                                                "vp" => $pscore['vp'],
                                                "player_name" => $players[$player_id]['player_name'] ,
                                                "points_nbr" => $points,
                                                "nbr" => $multiplier,
                                                'stations' => $visible_stations
                                           ) );
        }
    }
}
