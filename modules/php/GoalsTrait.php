<?php
 /**
  * GoalsTrait.php
  *
  * Goals expansion: goal tile progress tracking, winner determination,
  * and end-of-phase/endgame goal scoring.
  *
  */

use Bga\GameFramework\SystemException;

trait GoalsTrait
{
    function getGoalProgressTooltip($goal, $progress)
    {
        if (is_null($progress)) {
            return null;
        }

        $tooltip = '';
        arsort($progress);
        $players = self::loadPlayersBasicInfos();
        foreach ($progress as $player_id => $value) {
            $player = $players[$player_id];
            $style = 'color:#'.$player['player_color'];

            // Hackish way for text stroke when player color is white.
            // A bit ugly, but still better than white text on white background though
            if ($player['player_color'] == 'ffffff') {
                $style .= '; text-shadow: 1px 0 0 black, 0 1px 0 black, 0 -1px 0 black, -1px 0 0 black';
            }

            $tooltip .= '<span style="'.$style.'">'.$player['player_name'].'</span>: ';
            $tooltip .= $value . '<br>';
        }
        return $tooltip;
    }

    function getGoalProgress($goal)
    {
        $tableaux = $this->cards->getCardsInLocation('tableau');
        $players = self::loadPlayersBasicInfos();
        $progress = array();
        foreach ($players as $player_id => $player) {
            $progress[$player_id] = 0;
        }

        switch ($goal['name']) {
            // First goals

            case 'Innovation Leader': // 1 power in each phase
                $players_to_powers = array();
                foreach ($players as $player_id => $player) {
                    $players_to_powers[ $player_id ] = array(
                        1 => false, 2 => false, 3 => false, 4 => false, 5 => false, 's' => false
                   );
                }

                $bTakeOver = (self::getGameStateValue('takeover') == 1 || self::getGameStateValue('takeover') == 3);

                foreach ($tableaux as $card) {
                    $card_type = $this->card_types[ $card['type'] ];
                    foreach ($card_type['powers'] as $phase => $powers) {
                        // Already have it
                        if ($players_to_powers[ $card['location_arg'] ][ $phase ]) {
                            continue;
                        }

                        // If game is without takeover, then takeover powers don't count
                        if (!$bTakeOver && $phase == 3) {
                            foreach ($powers as $power) {
                                if ($this->isTakeoverPower($power)) {
                                    continue;
                                }

                                $players_to_powers[ $card['location_arg'] ][ $phase ] = true;
                                ++$progress[ $card['location_arg'] ];
                                break;
                            }
                        } else {
                            $players_to_powers[ $card['location_arg'] ][ $phase ] = true;
                            ++$progress[ $card['location_arg'] ];
                        }
                    }
                }
                break;

            case 'System diversity': // 1 world of each kind
                $players_to_colors = array();
                foreach ($players as $player_id => $player) {
                    $players_to_colors[ $player_id ] = array(
                        1 => false, 2 => false, 3 => false, 4 => false
                   );
                }

                foreach ($tableaux as $card) {
                    $card_type = $this->card_types[ $card['type'] ];
                    $world_type = $this->getCardColorFromType($card_type);

                    if ($world_type !== null && ! $players_to_colors[ $card['location_arg'] ][ $world_type ]) {
                        $players_to_colors[ $card['location_arg'] ][ $world_type ] = true;
                        ++$progress[ $card['location_arg'] ];
                    }
                }
                break;

            case 'Expansion Leader': // 8 cards in tableau
                $progress = $this->cards->countCardsByLocationArgs('tableau');
                break;

            case 'Overlord Discoveries': // 3 alien cards
                foreach ($tableaux as $card) {
                    $card_type = $this->card_types[ $card['type'] ];
                    if (in_array('alien', $card_type['category'])) {
                        ++$progress[ $card['location_arg'] ];
                    }
                }
                break;

            case 'Uplift Knowledge': // 3 uplift cards
                foreach ($tableaux as $card) {
                    $card_type = $this->card_types[ $card['type'] ];
                    if (in_array('uplift', $card_type['category'])) {
                        ++$progress[ $card['location_arg'] ];
                    }
                }
                break;

            case 'Galactic Standard of Living': // 5 VP chips
                $progress = self::getCollectionFromDB("SELECT player_id, player_vp FROM player", true);
                break;
            case 'Galactic Riches': // 4 goods
                $sql  = "SELECT world.card_location_arg player, COUNT(good.card_id) ";
                $sql .= "FROM card good ";
                $sql .= "JOIN card world ON world.card_id=good.card_location_arg ";
                $sql .= "WHERE good.card_location='good' ";
                $sql .= "GROUP BY player ";
                $progress = self::getCollectionFromDB($sql, true) + $progress;
                break;

            // Most goals

            case 'Greatest Infrastructure': // devs
                foreach ($tableaux as $card) {
                    $card_type = $this->card_types[ $card['type'] ];
                    if ($card_type['type'] == 'development') {
                        ++$progress[ $card['location_arg'] ];
                    }
                }
                break;

            case 'Propaganda Edge': // Rebel Military worlds
                foreach ($tableaux as $card) {
                    $card_type = $this->card_types[ $card['type'] ];
                    if ($card_type['type'] == 'world' && in_array('military', $card_type['category']) && in_array('rebel', $card_type['category'])) {
                        ++$progress[ $card['location_arg'] ];
                    }
                }
                break;

            case 'Production Leader': // Production Worlds
                foreach ($tableaux as $card) {
                    $card_type = $this->card_types[ $card['type'] ];
                    if ($this->isProductionWorld($card_type)) {
                        ++$progress[ $card['location_arg'] ];
                    }
                }
                break;

            case 'Greatest Military': // Military strength
                $progress = self::getCollectionFromDB("SELECT player_id, player_milforce FROM player WHERE 1", true);
                break;

            case 'Research Leader': // Powers in explore phase
                foreach ($tableaux as $card) {
                    $card_type = $this->card_types[ $card['type'] ];
                    if (isset($card_type['powers'][1])) {
                        ++$progress[ $card['location_arg'] ];
                    }
                }
                break;

            case 'Largest Industry': // Novelty and Rare Elements worlds
                foreach ($tableaux as $card) {
                    $card_type = $this->card_types[ $card['type'] ];
                    $world_type = $this->getCardColorFromType($card_type);
                    if ($world_type == 1 || $world_type == 2) {
                        ++$progress[ $card['location_arg'] ];
                    }
                }
                break;

            case 'Galactic Prestige': // Prestige chips
                $progress = self::getCollectionFromDB("SELECT player_id, player_prestige FROM player WHERE 1", true);
                break;

            case 'Prosperity Lead': // Powers in consume phase
                foreach ($tableaux as $card) {
                    $card_type = $this->card_types[ $card['type'] ];
                    if (isset($card_type['powers'][4])) {
                        ++$progress[ $card['location_arg'] ];
                    }
                }
                break;

            case 'Prestige leader':
                $progress = self::getCollectionFromDB("SELECT player_id,player_prestige FROM player", true);
                break;

            default:
                return null;
        }
        return $progress;
    }

    function getGoalWinners($goal)
    {
        $goal_type = $this->goal_types[ $goal['type'] ];
        $tableaux = $this->cards->getCardsInLocation('tableau');
        $players = self::loadPlayersBasicInfos();
        $progress = $this->getGoalProgress($goal_type);
        $winners = array();
        $limit = null;
        switch ($goal_type['name']) {
            case 'Prestige leader':
                $limit = 1;
                break;
            case 'Overlord Discoveries':
            case 'Uplift Knowledge':
            case 'Propaganda Edge':
            case 'Largest Industry':
            case 'Research Leader':
            case 'Galactic Prestige':
            case 'Prosperity Lead':
                $limit = 3;
                break;
            case 'System diversity':
            case 'Galactic Riches':
            case 'Greatest Infrastructure':
            case 'Production Leader':
                $limit = 4;
                break;
            case 'Galactic Standard of Living':
                $limit = 5;
                break;
            case 'Innovation Leader':
            case 'Greatest Military':
                $limit = 6;
                break;
            case 'Expansion Leader':
                $limit = 8;
                break;
            case 'Budget Surplus': // discard at the end of the turn
                foreach ($this->getEndRoundDiscardNumber() as $player_id => $nbr) {
                    if ($nbr > 0) {
                        $winners[] = $player_id;
                    }
                }
                break;
            case 'Galactic Status': // play a 6 dev
                foreach ($tableaux as $card) {
                    $card_type = $this->card_types[ $card['type'] ];
                    if ($card_type['type'] == 'development' && $card_type['cost'] == 6 && $card_type['name'] != 'Pan-Galactic Research') {
                        $winners[] = $card['location_arg'];
                    }
                }
                break;
            case 'Military Influence': // 3 imperium or 4 military
                $players_to_imp = array();
                $players_to_mil = array();
                foreach ($players as $player_id => $player) {
                    $players_to_imp[ $player_id ] = 0;
                    $players_to_mil[ $player_id ] = 0;
                }

                foreach ($tableaux as $card) {
                    $card_type = $this->card_types[ $card['type'] ];

                    if (in_array('imperium', $card_type['category'])) {
                        $players_to_imp[ $card['location_arg'] ] ++;
                    }

                    if (in_array('military', $card_type['category'])) {
                        $players_to_mil[ $card['location_arg'] ] ++;
                    }
                }

                foreach ($players as $player_id => $player) {
                    if ($players_to_imp[ $player_id ] >= 3 || $players_to_mil[ $player_id ] >= 4) {
                        $winners[] = $player_id;
                    }
                }
                break;
            case 'Peace/War Leader':
                // 2 worlds in tableau + negative military OR 2 military worlds in tableau + takeover power
                $player_to_worlds = array();
                $player_to_military_worlds = array();
                foreach ($players as $player_id => $player) {
                    $player_to_worlds[ $player_id ] = 0;
                    $player_to_military_worlds[ $player_id ] = 0;
                }
                $player_with_neg_mid = self::getObjectListFromDB("SELECT player_id FROM player WHERE player_milforce<0", true);
                $player_with_takeover = array();

                $bTakeOver = (self::getGameStateValue('takeover') == 1 || self::getGameStateValue('takeover') == 3);

                foreach ($tableaux as $card) {
                    $card_type = $this->card_types[ $card['type'] ];

                    if ($card_type['type'] == 'world') {
                        $player_to_worlds[ $card['location_arg'] ]++;
                        if (in_array('military', $card_type['category'])) {
                            $player_to_military_worlds[ $card['location_arg'] ]++;
                        }
                    }

                    // If there are no takeovers, this condition disappear
                    if ($bTakeOver && isset($card_type['powers'][3])) {
                        foreach ($card_type['powers'][3] as $power_type => $power) {
                            // Defense do not apply, see https://boardgamegeek.com/thread/515525/peacewar-leader-goal
                            if ($this->isTakeoverPower($power, false)) {
                                $player_with_takeover[] = $card['location_arg'];
                            }
                        }
                    }
                }

                foreach ($players as $player_id => $player) {
                    if ($player_to_worlds[ $player_id ] >= 2 && in_array($player_id, $player_with_neg_mid)
                        || $player_to_military_worlds[ $player_id ] >= 2 && in_array($player_id, $player_with_takeover)) {
                            $winners[] = $player_id;
                    }
                }
                break;
            case 'Galactic Standing': // 3 VP + 2 prestige
                $winners = self::getObjectListFromDb("SELECT player_id FROM player WHERE player_vp >= 3 AND player_prestige>=2", true);
                break;
            default:
                throw new SystemException("Unknow goal : ".$goal_type['name']);
        }
        if (! is_null($progress)) {
            if ($goal_type['type'] == 'first') {
                foreach ($progress as $player => $value) {
                    if ($value >= $limit) {
                        $winners[] = $player;
                    }
                }
            } else {
                $this_obj_winners = getKeysWithMaximum($progress);
                if (count($this_obj_winners) > 0) {
                    if ($progress[ reset($this_obj_winners) ] >= $limit) {
                        $winners = $this_obj_winners;
                    }
                }
            }
            $progress_tooltip = $this->getGoalProgressTooltip($goal_type, $progress);
            $args = array('goal' => $goal['id'], 'progress' => $progress_tooltip);
            if ($goal['location_arg'] != 0) {
                $args['player'] = $goal['location_arg'];
            }
            $this->notifyAllPlayers('goalProgress', "", $args);
        }
        return $winners;
    }

    // Check goals completion for the given phase
    // "endgame" special phase allows you to score 3 points for end games tie
    function checkGoals($phase)
    {
        $firsts = $this->cards->getCardsInLocation('obj_first', 0);
        $mosts = $this->cards->getCardsInLocation('obj_most');
        $players = self::loadPlayersBasicInfos();

        $winners = array();

        // "First" objectifs
        foreach ($firsts as $first) {
            $goal = $this->goal_types[ $first['type'] ];

            if (! in_array($phase, $goal['phases'])) {
                continue;
            }

            foreach ($this->getGoalWinners($first) as $winner) {
                $winners[] = array('player' => $winner, 'goal' =>$first['id'], 'goal_type_id' => $first['type'], 'goal_type' => $goal);
            }
        }

        // "First" goals to attribute (3 points per players)
        $goal_given = array();
        foreach ($winners as $winner) {
            // Give goal to player
            if (isset ($goal_given[$winner['goal']])) {
                // Goal has already been given, let's make a duplicate
                $sql = "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg, card_status) VALUES ";
                $sql.= "(".$winner['goal_type_id'].", 0, 'obj_first', ".$winner['player'].", 0)";
                self::DbQuery($sql);
            } else {
                $this->cards->moveCard($winner['goal'], 'obj_first', $winner['player']);
                $goal_given[$winner['goal']] = array();
            }
            $goal_given[$winner['goal']][] = $winner['player'];

            $points = $winner['goal_type']['points'];

            $pscore = $this->updatePlayerScore($winner['player'], $points, false);
            self::incStat($points, 'goal_first_points', $winner['player']);


            $this->notifyAllPlayers('updateScore', clienttranslate('${player_name} fulfills goal ${goal} (${description}) and scores ${points_nbr} points'),
                                            array(
                                                "i18n" => array("goal", "description"),
                                                "player_id" => $winner['player'],
                                                "score_delta" => $points,
                                                "vp_delta" => 0,
                                                "score" => $pscore['score'],
                                                "vp" => $pscore['vp'],
                                                "player_name" => $players[$winner['player']]['player_name'] ,
                                                "points_nbr" => $points,
                                                "goal" => $winner['goal_type']['name'],
                                                "description" => $winner['goal_type']['description']
                                           ) );
        }

        foreach ($goal_given as $goal_id => $winners) {
            $goal = $this->cards->getCard($goal_id);
            $goal_type = $this->goal_types[ $goal['type'] ];
            $this->notifyAllPlayers('fullfillGoal', '', array('goal' => $goal['id'], 'type' => $goal['type'], 'to' => $winners));
            $progress_tooltip = $this->getGoalProgressTooltip($goal_type, $this->getGoalProgress($goal_type));
            foreach ($winners as $winner) {
                $this->notifyAllPlayers('goalProgress', "", array('goal' => $goal['id'], 'player' => $winner, 'progress' => $progress_tooltip));
            }
        }

        // "Mosts" goals

        foreach ($mosts as $most) {
            $goal_type = $this->goal_types[ $most['type'] ];

            if (!in_array($phase, $goal_type['phases']) && $phase != 'endgame') {
                continue;
            }

            // Prestige isn't a goal, don't count ties for 3 points
            if ($goal_type['type'] == 'pr' && $phase == 'endgame') {
                continue;
            }

            $new_owners = $this->getGoalWinners($most);

            // Who has it now?
            $current_owner = null;
            if ($most['location_arg'] != 0) {
                $current_owner = $most['location_arg'];
            }

            $bCurrentOwnerMustLooseIt = false;
            $who_gets_it = null;

            if ($phase == 'endgame') {
                // Special case : end of game.
                // Attribute 3pts to tie players

                if (count($new_owners) > 0) {
                    if ($current_owner !== null &&  in_array($current_owner, $new_owners)) {
                        // some player has the goal tile (and its 5pts), so he should not get 3 pts.
                        if (($key = array_search($current_owner, $new_owners)) !== false) {
                            unset($new_owners[$key]);
                        }
                    }

                    // All others scores 3pts
                    foreach ($new_owners as $who_gets_it) {
                        $points = 3;

                        $pscore = $this->updatePlayerScore($who_gets_it, $points, false);
                        self::incStat($points, 'goal_most_points', $who_gets_it);

                        $log = clienttranslate('${player_name} is tie for goal ${goal} (${description}) and scores ${points_nbr} points');

                        $this->notifyAllPlayers('updateScore', $log,
                                                        array(
                                                            "i18n" => array("goal", "description"),
                                                            "player_id" => $who_gets_it,
                                                            "score_delta" => $points,
                                                            "vp_delta" => 0,
                                                            "score" => $pscore['score'],
                                                            "vp" => $pscore['vp'],
                                                            "player_name" => $players[ $who_gets_it ]['player_name'] ,
                                                            "points_nbr" => $points,
                                                            "goal" => $goal_type['name'],
                                                            "description" => $goal_type['description']
                                                       ) );
                    }
                }
            } else {
                // Normal case :
                // Who is going to get it

                if (count($new_owners) > 0) {
                    // Prestige is always returned to the center on a tie
                    if ($most['type'] == 226 && count($new_owners) > 1) {
                        $bCurrentOwnerMustLooseIt = true;
                    } // If the current owner is part of the new winner, situation remains stable (he was the first to get it)
                    elseif ($current_owner !== null && in_array($current_owner, $new_owners)) {
                        // Okay, remains in the same situation
                    } else {
                        // This goal must change hands!
                        $bCurrentOwnerMustLooseIt = true;

                        if (count($new_owners) > 1) {
                            // Multiple winners : no one gets it
                            // (only current owner loose it)
                        } else {
                            // One winner : he gets the goal !
                            $who_gets_it  = reset($new_owners);
                        }
                    }
                } else {
                    // No one can have this goal
                    $bCurrentOwnerMustLooseIt = true;
                }

                if ($current_owner !== null && $bCurrentOwnerMustLooseIt) {
                    // Remove it from current owner
                    $this->cards->moveCard($most['id'], 'obj_most', 0);

                    $points = $goal_type['points'];

                    $pscore = $this->updatePlayerScore($current_owner, -$points, false);
                    self::incStat(-$points, 'goal_most_points', $current_owner);

                    $log = clienttranslate('${player_name} loses goal ${goal} and loses ${points_nbr} points');
                    if ($most['type'] == 226) { // Prestige leader (not an goal)
                        $log = clienttranslate('${player_name} loses ${goal}');
                        self::setGameStateValue('prestigeOnLeaderTile', 0);
                        self::setGameStateValue('prestigeLeader', 0);
                    }
                    $this->notifyAllPlayers('updateScore', $log,
                                                    array(
                                                        "i18n" => array("goal"),

                                                        "player_id" => $current_owner,
                                                        "score_delta" => -$points,
                                                        "vp_delta" => 0,
                                                        "score" => $pscore['score'],
                                                        "vp" => $pscore['vp'],
                                                        "player_name" => $players[ $current_owner ]['player_name'] ,
                                                        "points_nbr" => $points,
                                                        "goal" => $goal_type['name']
                                                   ) );

                    $this->notifyAllPlayers('fullfillGoal', '', array('goal' => $most['id'], 'type'=> $most['type'], 'from' => $current_owner, 'to' => 'discard'));
                    $progress_tooltip = $this->getGoalProgressTooltip($goal_type, $this->getGoalProgress($goal_type));
                    $this->notifyAllPlayers('goalProgress', "", array('goal' => $most['id'], 'progress' => $progress_tooltip));
                }
                if ($who_gets_it !== null) {
                    // Give it to this player

                    $this->cards->moveCard($most['id'], 'obj_most', $who_gets_it);

                    $points = $goal_type['points'];

                    $pscore = $this->updatePlayerScore($who_gets_it, $points, false);
                    self::incStat($points, 'goal_most_points', $who_gets_it);

                    $log = clienttranslate('${player_name} fulfills goal ${goal} (${description}) and scores ${points_nbr} points');
                    if ($most['type'] == 226) { // Prestige leader (not a goal)
                        self::setGameStateValue('prestigeOnLeaderTile', 1);
                        self::setGameStateValue('prestigeLeader', $who_gets_it);
                        $log = clienttranslate('${player_name} gets ${goal}');
                    }

                    $this->notifyAllPlayers('updateScore', $log,
                                                    array(
                                                        "i18n" => array("goal", "description"),
                                                        "player_id" => $who_gets_it,
                                                        "score_delta" => $points,
                                                        "vp_delta" => 0,
                                                        "score" => $pscore['score'],
                                                        "vp" => $pscore['vp'],
                                                        "player_name" => $players[ $who_gets_it ]['player_name'] ,
                                                        "points_nbr" => $points,
                                                        "goal" => $goal_type['name'],
                                                        "description" => $goal_type['description']
                                                   ) );

                    $this->notifyAllPlayers('fullfillGoal', '', array('goal' => $most['id'], 'type' => $most['type'], 'to' => array($who_gets_it)));
                    $progress_tooltip = $this->getGoalProgressTooltip($goal_type, $this->getGoalProgress($goal_type));
                    $this->notifyAllPlayers('goalProgress', "", array('goal' => $most['id'], 'player' => $who_gets_it, 'progress' => $progress_tooltip));
                }
            }
        }
    }
}
