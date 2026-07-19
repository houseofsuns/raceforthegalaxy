<?php
 /**
  * InvasionTrait.php
  *
  * Xeno Invasion expansion: invasion wave resolution, world damage/repair,
  * admiral track, and related end-of-game scoring.
  *
  */

use Bga\GameFramework\UserException;
use Bga\GameFramework\SystemException;

trait InvasionTrait
{
    function invasiondeckAutoReshuffle()
    {
        $this->notifyAllPlayers('reshuffle', clienttranslate('No more cards in invasion deck ! Wave 3 cards are shuffled back into the draw pile'), array());
    }
    function damageWorld($card_id)
    {
        $player_id = self::getCurrentPlayerId();

        $card = $this->cards->getCard($card_id);

        // Does the target world has a good on it?
        $good_id = self::getUniqueValueFromDB("SELECT card_id FROM card WHERE card_location='good' AND card_location_arg='$card_id'");
        // If it does, discard it before destroying the world
        if ($good_id !== null) {
            $this->cards->moveCard($good_id, $this->getDiscard($player_id), 0);
        }

        // Damage the world
        self::DbQuery("UPDATE card SET card_damaged=card_type WHERE card_id='$card_id'");
        self::DbQuery("UPDATE card SET card_type='1000' WHERE card_id='$card_id'"); // Special type "1000" for damaged world

        // Reduce the score
        $score_delta = $this->card_types[ $card['type'] ]['vp'] * -1;
        $pscore = $this->updatePlayerScore($player_id, $score_delta, false);
        $this->notifyAllPlayers('updateScore', '',
                                array(
                                    "player_id" => $player_id,
                                    "score" => $pscore['score'],
                                    "score_delta" => $score_delta,
                               ));

        // Refresh the military
        $this->updateMilforceIfNeeded($player_id);
        $this->notifyAllPlayers('updateSpecializedMilitary', '', $this->getSpecializedMilitary());

        $world_name = $this->card_types[ $card['type'] ]['name'];
        $card['damaged'] = $card['type'];
        $card['type'] = 1000;
        $this->notifyAllPlayers('damageWorld', clienttranslate('${player_name} damages ${world}'),
                                        array(
                                            "i18n" => array('world'),
                                            "player_name" => self::getCurrentPlayerName(),
                                            "card" => $card,
                                            "world" => $world_name
                                       ));
    }
    function xenoDonotrepulse()
    {
        self::checkAction('resolveInvasion');

        $this->gamestate->setPlayerNonMultiactive(self::getCurrentPlayerId(), 'invasion_end');
    }
    function chooseDamage($card_id)
    {
        self::checkAction('chooseDamage');

        $player_id = self::getCurrentPlayerId();

        $card = $this->cards->getCard($card_id);

        // Check that the card is in player's tableau
        if ($card['location'] != 'tableau' || $card['location_arg'] != $player_id) {
            throw new SystemException("This card is not in your tableau");
        }

        if ($this->card_types[ $card['type'] ]['type'] != 'world') {
            throw new UserException(self::_("You must select a world"));
        }

        if (self::getUniqueValueFromDB("SELECT card_damaged FROM card WHERE card_id='$card_id'") != 0) {
            throw new UserException(self::_("This world has been damaged already"));
        }

        $this->damageWorld($card_id);

        $this->gamestate->setPlayerNonMultiactive(self::getCurrentPlayerId(), 'damages_ok');
    }
    function repairDamaged($card_id, $discard_ids, $good_id)
    {
        self::checkAction('repairDamaged');

        $player_id = self::getCurrentPlayerId();

        $card = $this->cards->getCard($card_id);

        // Check that the card is in player's tableau
        if ($card['location'] != 'tableau' || $card['location_arg'] != $player_id) {
            throw new SystemException("This card is not in your tableau");
        }

        if ($this->card_types[ $card['type'] ]['type'] != 'world') {
            throw new UserException(self::_("You must select a world"));
        }

        $real_type = self::getUniqueValueFromDB("SELECT card_damaged FROM card WHERE card_id='$card_id'");
        if ($real_type == 0) {
            throw new SystemException("You must choose a damaged world");
        }

        $notifargs = array(
                        "i18n" => array('world'),
                        "player_name" => self::getCurrentPlayerName(),
                        "player_id" => $player_id,
                        "world" => $this->card_types[ $real_type ]['name']
                   );

        // Check available repair power
        $phase_repair = self::getUniqueValueFromDB("SELECT player_repair_charges FROM player WHERE player_id='$player_id'");

        if ($phase_repair > 0) {
            self::DbQuery("UPDATE player SET player_repair_charges=player_repair_charges-1 WHERE player_id='$player_id'");
            $log = clienttranslate('${player_name} repairs ${world} with his Production: Repair phase bonus');
            $notifargs['repair_power'] = true;
        } else {
            // Check if there is some 313 or 306 (cards with repair powers), unused
            $repair_power = self::getObjectFromDB("SELECT card_id, card_type FROM card
                                WHERE card_type IN ('313','306')
                                  AND card_status='0'
                                  AND card_location='tableau'
                                  AND card_location_arg='$player_id'
                                  LIMIT 1");

            if ($repair_power !== null) {
                $repair_world_id = $repair_power['card_id'];

                $log = clienttranslate('${player_name} repairs ${world} with ${repair} power.');
                $notifargs['i18n'][] = 'repair';
                $notifargs['repair'] = $this->card_types[ $repair_power['card_type'] ]['name'];
                $notifargs['repair_power'] = true;

                self::DbQuery("UPDATE card SET card_status=-1 WHERE card_id='$repair_world_id'");
            } else {
                if ($good_id != 0) {
                    $good = $this->cards->getCard($good_id);
                    if ($good['location'] != 'good') {
                        throw new SystemException("Invalid good");
                    }

                    $good_host = $good['location_arg'];
                    $host = $this->cards->getCard($good_host);
                    if ($host['location'] != 'tableau' || $host['location_arg'] != $player_id) {
                        throw new SystemException("This good is not in your tableau");
                    }

                    // Discard the good
                    $this->cards->moveCard($good_id, $this->getDiscard($player_id), 0);

                    $this->notifyAllPlayers('consume', '', array(
                                    "i18n" => array("world_name"),
                                    "player_id" => $player_id,
                                    "player_name" => self::getCurrentPlayerName(),
                                    "good_id" => $good_id,
                                    "world_id" => $card_id,
                                    "force" => 0
                               ));

                    $log = clienttranslate('${player_name} repairs ${world} by consuming a good.');
                } elseif (count($discard_ids) == 2) {
                    // Discard these 2 cards to repair the world
                    // Check that the cards are in player hand
                    $cards = $this->cards->getCards($discard_ids);

                    foreach ($cards as $card) {
                        if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) {
                            throw new SystemException("This card is not in your hand");
                        }
                    }

                    // Move to discard
                    $this->cards->moveCards($discard_ids, $this->getDiscard($player_id), 0);

                    $this->notifyUpdateCardCount();

                    // Notify
                    $this->notifyPlayer($player_id, "discard", '',
                                             array("cards" => $discard_ids) );


                    $log = clienttranslate('${player_name} repairs ${world} by discarding 2 cards.');
                } else {
                    throw new UserException(self::_("You must choose 2 cards OR 1 resource to discard to repair this world"));
                }
            }
        }

        // Repair the world
        self::DbQuery("UPDATE card SET card_type=card_damaged WHERE card_id='$card_id'");
        self::DbQuery("UPDATE card SET card_damaged='0' WHERE card_id='$card_id'");

        $card = $this->cards->getCard($card_id);
        $notifargs['card'] = $card;

        $this->notifyAllPlayers('repairWorld', $log, $notifargs);

        // Restore the score
        $score_delta = $this->card_types[ $card['type'] ]['vp'];
        $pscore = $this->updatePlayerScore($player_id, $score_delta, false);
        $this->notifyAllPlayers('updateScore', '',
                                array(
                                    "player_id" => $player_id,
                                    "score" => $pscore['score'],
                                    "vp" => 0,
                                    "score_delta" => $score_delta,
                                    "vp_delta" => 0
                               ));

        // Refresh the military
        $this->updateMilforceIfNeeded($player_id);
        $this->notifyAllPlayers('updateSpecializedMilitary', '', $this->getSpecializedMilitary());

        $card_type = $this->card_types[ $card['type'] ];
        $powers = isset($card_type['powers'][5]) ? $card_type['powers'][5] : array();
        // Note : copied from stProductionProcess code
        foreach ($powers as $key => $power) {
            // Necessary for the conversion to possibility. Usually done in scanTableau
            $powers[$key]['card_type'] = $card_type;
            $powers[$key]['card_id'] = $card_id;

            if ($power['power'] == 'draw') {
                $this->drawCardForPlayer($player_id, $power['arg']['card'], false, $card['type']);
            }

            if ($power['power'] != 'produce') {
                continue;
            }

            // Place a good on this card
            $good_card = $this->cards->pickCardForLocation($this->getDeck($player_id), 'good', $card_id);
            self::incStat(1, 'good_produced', $player_id);

            // Store good type in "card_status"
            $sql = "UPDATE card SET card_status='".$power['arg']['resource']."' ";
            $sql .= "WHERE card_id='".$good_card['id']."' ";
            self::DbQuery($sql);

            $sql = "INSERT INTO player_production (pp_player_id, pp_good_id, pp_card_id) VALUES ";
            $sql .= "($player_id, ".$power['arg']['resource'].",$card_id) ";
            self::DbQuery($sql);

            $this->notifyAllPlayers('goodproduction', '', array(
                        "world_id" => $card_id,
                        "good_type" => $power['arg']['resource'],
                        "good_id" => $good_card['id'],
                        "produced_by" => $player_id
                   ));

            if (isset($power['arg']['draw'])) {
                $this->drawCardForPlayer($player_id, $power['arg']['draw'], false, $card['type']);
            }
        }

        // If the card has produce powers, they are immediately usable. Refresh the windfallpower display
        if (count($powers) > 0) {
            $windfallPossibilities = $this->windfallPossibilities($powers);
            $windfallPossibilities['title'] = $this->getProduceTitle($player_id);
            $windfallPossibilities['single_power'] = true;
            $this->notifyPlayer($player_id, 'updateWindfallPowers', '', $windfallPossibilities);
        }

        if (!$this->hasProduceActions($player_id)) {
            $this->gamestate->setPlayerNonMultiactive($player_id, 'phaseCleared');
        }
    }
    function argInvasionGameResolution()
    {
        $player_milforce = self::getCollectionFromDB("SELECT player_id, player_milforce as base FROM player", true);
        $invasion = $this->getInvasionCards();
        foreach ($invasion as $player_id => $invasion_strength) {
            if ($invasion_strength > 100) {
                $invasion[ $player_id ] = intval($player_milforce[ $player_id ]) + $invasion_strength - 100;
            }
        }

        return array(
            'invasion' => $invasion,
            'force' => self::getCollectionFromDB("SELECT player_id, (player_xeno_milforce + CAST(player_tmp_milforce AS SIGNED) + CAST(player_tmp_xenoforce AS SIGNED)) f FROM player", true)
       );
    }
    function updateXenoTieBreaker()
    {
        $players = self::getCollectionFromDB("
            SELECT player_id, player_xeno_milforce_tiebreak
            FROM player
            WHERE player_xeno_milforce IN (
                SELECT player_xeno_milforce
                FROM player
                GROUP BY player_xeno_milforce
                HAVING COUNT(*)>1
           )", true);
        $this->notifyAllPlayers('updateXenoTieBreaker', '', $players);
    }

    function stInvasionGame()
    {
        self::DbQuery("UPDATE player SET player_xeno_victory='0', player_bunker_used='0'");

        $this->resetCardStatus();

        $current_wave = self::getGameStateValue('xeno_current_wave');

        if ($current_wave == -2) {   // First turn
            self::setGameStateValue('xeno_current_wave', -1);
            $this->notifyAllPlayers('updateWave', clienttranslate('No invasion this turn ...'), array('wave' => -1, 'remaining' => 0));

            $this->gamestate->nextState('nextRound');
        } elseif ($current_wave == -1) {   // Second turn
            self::setGameStateValue('xeno_current_wave', 0);
            $this->notifyAllPlayers('updateWave', clienttranslate('No invasion this turn ...'), array('wave' => 1, 'remaining' => $this->getWaveRemaining()));

            $this->gamestate->nextState('nextRound');
        } else {
            // Update admiral disk

            $players = $this->getPlayerAdmiralTrackMovingOrder();
            $playersinfos = self::loadPlayersBasicInfos();
            $total_force = 0;
            $moves = [];
            // We initialize the track with the current state
            $track = [];
            $sql = "SELECT player_id, player_xeno_milforce FROM player ORDER BY player_xeno_milforce, player_xeno_milforce_tiebreak";
            $res = self::getCollectionFromDB($sql, true);
            foreach ($res as $player_id => $xeno_milforce) {
                $track[$xeno_milforce][] = $player_id;
            }

            foreach ($players as $player) {
                $player_id = $player[ 'player_id' ];

                // Compute Xeno force for this player
                $new_force = $this->updateMilforceIfNeeded($player_id, false, true);

                if ($new_force != $player['player_xeno_milforce'] || $current_wave == 0) {
                    $moves[ $player['player_xeno_milforce'] ][ $new_force ][] = $player_id;
                    // Change => must set tiebreaker to the top of the stack
                    $top = self::getUniqueValueFromDB("SELECT MAX(player_xeno_milforce_tiebreak) FROM player WHERE player_xeno_milforce='$new_force'");
                    $top ++;
                    self::DbQuery("UPDATE player SET player_xeno_milforce_tiebreak='$top' WHERE player_id='$player_id'");
                }

                $total_force += $new_force;
            }

            if ($current_wave == 0) {
                self::setGameStateValue('xeno_current_wave', 1);
                $this->notifyAllPlayers('placeAdmiralDisks', '', self::getCollectionFromDB("SELECT player_id, player_xeno_milforce xeno_milforce, player_xeno_milforce_tiebreak xeno_milforce_tiebreak FROM player"));
            } else {
                // Normalize stacks so that all tiebreaks are consecutive and start from 1
                $stacks = self::getObjectListFromDB("
                    SELECT player_xeno_milforce
                    FROM player
                    GROUP BY player_xeno_milforce
                    HAVING COUNT(*)!=MAX(player_xeno_milforce_tiebreak)", true);

                foreach ($stacks as $stack) {
                    $stack_ids = self::getObjectListFromDB("
                        SELECT player_id
                        FROM player
                        WHERE player_xeno_milforce=$stack
                        ORDER BY player_xeno_milforce_tiebreak", true);
                    $i = 0;
                    foreach ($stack_ids as $player_id) {
                        ++$i;
                        self::DbQuery("UPDATE player SET player_xeno_milforce_tiebreak=$i WHERE player_id=$player_id");
                    }
                }

                // In order for the military_vs_xeno_arrow to stay out of the way
                // during the track animation, we update it before the disk animation
                // if it's increasing and after if it's decreasing
                if ($total_force > self::getGameStateValue('xeno_repulse')) {
                    $this->notifyAllPlayers('moveMilitaryVsXenoArrow', '', array('military_vs_xeno' => $total_force));
                }

                // Animate the admiral disk updates on the Xeno track
                foreach ($moves as $from => $dests) {
                    // Previously, we processed stacks from the bottom to preserve the order of disks
                    // moving to the same destination. For the animation, we start from the top
                    foreach (array_reverse($dests, true) as $dest => $player_ids) {
                        $stackMoves = [];
                        foreach ($player_ids as $player_id) {
                            $track[ $dest ][] = $player_id;
                            $stackMoves[] = array(
                                    'player_id' => $player_id,
                                    'dest' => $dest,
                                    'height' => count($track[ $dest]));
                        }

                        // We go through the depart stack from the bottom and rebuild a new stack without the disks which
                        // have moved. For those which stay, we descend the ones that need to.
                        $new_stack = [];
                        foreach ($track[ $from ] as $i => $player_id) {
                            if (!in_array($player_id, $player_ids)) {
                                $new_stack[] = $player_id;
                                if (count($new_stack) != $i + 1) {
                                    $stackMoves[] = array(
                                    'player_id' => $player_id,
                                    'dest' => $from,
                                    'height' => count($new_stack));
                                }
                            }
                        }
                        $track[ $from ] = $new_stack;
                        $this->notifyAllPlayers('moveAdmiralDisks', '', array('moves' => $stackMoves, 'scroll' => true));
                    }
                }

                if ($total_force < self::getGameStateValue('xeno_repulse')) {
                    $this->notifyAllPlayers('moveMilitaryVsXenoArrow', '', array('military_vs_xeno' => $total_force));
                }
            }

            $this->updateXenoTieBreaker();

            self::setGameStateValue('xeno_repulse', $total_force);

            $this->notifyAllPlayers('updateXenoRepulsion', clienttranslate('The total of Empire force against Xeno is now : ${force}'), array('force' => $total_force));

            if (self::getGameStateValue('xeno_repulse_goal') <= $total_force) {
                $this->notifyAllPlayers('simpleNote', clienttranslate("The Empire successfully manages to repulse the Xenos!! Game ends immediately"), array());

                $this->gamestate->nextState('nextRound');
                return ;
            }

            // Step 3 : distribute invasion cards
            $cards = $this->invasioncards->pickCardsForLocation(count($players), 'deck', 'inprogress');

            // Order them by type DESC
            usort($cards, array($this, 'sortInvasion'));
            $players = $this->getPlayerAdmiralTrackOrder();
            $current_wave = 0;
            foreach ($players as $player) {
                $card = array_shift($cards);

                $current_wave = $this->invasion_cards_types[ $card['type'] ]['wave'];

                $this->invasioncards->moveCard($card['id'], 'inprogress', $player);
            }

            self::setGameStateValue('xeno_current_wave', $current_wave);

            $invasionCards = $this->getInvasionCards();

            $this->notifyAllPlayers('dealInvasionCards', clienttranslate('Xeno Invasion (wave ${wave}) : an invasion card is given to each player'), array(
                'wave' => $current_wave,
                'remaining' => $this->getWaveRemaining(),
                'cards' => $invasionCards
           ));

            $pos = 1;
            foreach ($players as $player) {
                $invasion_value = $invasionCards[ $player ];

                if ($invasion_value < 100) {
                    $this->notifyAllPlayers('simpleNote', clienttranslate('${player_name} (position #${pos}) must face a invasion force ${value}.'), array(
                        'pos' => $pos,
                        'player_name' => $playersinfos[ $player ]['player_name'],
                        'value' => $invasion_value
                   ));
                } else {
                    $this->notifyAllPlayers('simpleNote', clienttranslate('${player_name} (position #${pos}) must face a invasion force Base military force + ${value}.'), array(
                        'pos' => $pos,
                        'player_name' => $playersinfos[ $player ]['player_name'],
                        'value' => ($invasion_value-100)
                   ));
                }

                $pos ++;
            }

            $this->gamestate->nextState('invasionResolution');
        }
    }

    function checkXenoForce($player_ids)
    {

        $players = self::loadPlayersBasicInfos();
        $admirals = $this->getPlayerAdmiralTrackOrder();
        $player_to_menace = $this->getInvasionCards();
        $player_to_force = self::getCollectionFromDB("SELECT player_id, (player_xeno_milforce + CAST(player_tmp_milforce AS SIGNED) + CAST(player_tmp_xenoforce AS SIGNED)) f, player_milforce baseforce FROM player");
        $pos = 1;
        $bAtLeastOneWin = false;

        foreach ($player_to_menace as $player_id => $menace) {
            if ($menace > 100) {
                $player_to_menace[ $player_id ] = ($player_to_force[ $player_id ][ 'baseforce' ] + $menace - 100);
            }
        }

        foreach ($admirals as $player_id) {
            if (in_array($player_id, $player_ids)) {
                if ($player_to_force[ $player_id ]['f'] > $player_to_menace[ $player_id ]) {
                    // This player wins!
                    $bAtLeastOneWin = true;

                    self::DbQuery("UPDATE player SET player_xeno_victory='1' WHERE player_id='$player_id'");

                    $score = $this->invasion_config[ count($players) ]['rewards'][$pos];

                    $pscore = $this->updatePlayerScore($player_id, $score, false, true);
                    self::incStat($score, 'defense_award_points', $player_id);

                    $this->notifyAllPlayers('updateScore', clienttranslate('${player_name}, force ${force} repulse the Xenos (${xeno}) and scores ${score_delta} points'),
                                            array(
                                                "score" => $pscore['score'],
                                                "vp" => $pscore['vp'],
                                                "player_id" => $player_id,
                                                "player_name" => $players[ $player_id ]['player_name'],
                                                "score_delta" => $score,
                                                "vp_delta" => 0,
                                                "force" => $player_to_force[ $player_id ]['f'],
                                                "xeno" => $player_to_menace[ $player_id ],
                                                "defense_award" => $pscore['defense_award']
                                           ) );

                    $this->gamestate->setPlayerNonMultiactive($player_id, 'invasion_end');
                } else {
                    // This player may use some power to increase its defense, so we are staying here for now...
                }
            }

            $pos ++;
        }
    }

    function stInvasionGameResolution()
    {
        $players = self::loadPlayersBasicInfos();
        $this->gamestate->setAllPlayersMultiactive();
        $this->checkXenoForce(array_keys($players));
    }

    function stInvasionGameDamage()
    {
        // Refresh military for all players. Someone might have discarded Anti-Xeno Milita.
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $this->updateMilforceIfNeeded($player_id);
        }
        self::DbQuery("UPDATE player SET player_tmp_milforce=0");
        $this->notifyAllPlayers('clearTmpMilforce', '', null);
        $this->moveJustDiscardedToDiscard();
        $this->notifyAllPlayers('updateSpecializedMilitary', '', $this->getSpecializedMilitary());

        // Active player that must damage a world
        $players = self::loadPlayersBasicInfos();
        $player_to_damage = self::getObjectListFromDB("SELECT player_id FROM player WHERE player_xeno_victory='0'", true);

        if (count($players) == count($player_to_damage)) {
            // No one manage to repulse the Xenos !!

            $defeat = self::incGameStateValue('xeno_empire_defeat', 1);

            if ($defeat == 1) {
                $this->notifyAllPlayers('empireDefeat', clienttranslate('No players manage to repulse the Xeno : Empire defeat marker set to 1! Be careful!'), array('defeat' => $defeat));
            } elseif ($defeat >= 2) {
                $this->notifyAllPlayers('empireDefeat', clienttranslate('Game End : No players manage to repulse the Xeno for the second time : Empire is defeated and end of game bonuses are not attributed.'), array('defeat' => $defeat));
            }
        }

        // Rotate admiral discs. The rules says to do this after dealing invasion cards
        // but it's simpler to do it after awards have been given
        // it might be less confusing for players too
        // The disc on the bottom is put to the top
        $stacks = self::getCollectionFromDB("
            SELECT player_xeno_milforce, COUNT(*) as top
            FROM player
            GROUP BY player_xeno_milforce
            HAVING COUNT(*)>1", true);
        $moves = [];
        foreach ($stacks as $space => $top) {
            self::DbQuery(
                "UPDATE player SET player_xeno_milforce_tiebreak = player_xeno_milforce_tiebreak - 1
                 WHERE player_xeno_milforce = $space");
            self::DbQuery(
                "UPDATE player SET player_xeno_milforce_tiebreak = $top
                 WHERE player_xeno_milforce = $space AND player_xeno_milforce_tiebreak = 0");

            // Animate the stack update
            $player_ids = self::getCollectionFromDB("
                SELECT player_id, player_xeno_milforce_tiebreak
                FROM player
                WHERE player_xeno_milforce = $space
                ", true);
            foreach ($player_ids as $player_id => $height) {
                $moves[] = array(
                    'player_id' => $player_id,
                    'dest' => $space,
                    'height' => intval($height));
            }
        }
        $this->notifyAllPlayers('moveAdmiralDisks', '', array('moves' => $moves, 'scroll' => false));

        $this->updateXenoTieBreaker();

        $cards = self::getObjectListFromDB("SELECT card_type, card_location_arg
                  FROM card
                  WHERE card_damaged='0'
                  AND card_location='tableau' ");

        $players_with_a_intact_world = array();
        foreach ($cards as $card) {
            if ($this->card_types[ $card['card_type'] ]['type'] == 'world') {
                $players_with_a_intact_world[ $card['card_location_arg'] ] = true;
            }
        }
        $players_with_a_intact_world = array_keys($players_with_a_intact_world);

        $players_to_damage_real = array();

        foreach ($player_to_damage as $player_id) {
            if (! in_array($player_id, $players_with_a_intact_world)) {
                $this->notifyAllPlayers('simpleNote', clienttranslate('${player_name} has no world to damage'), array(
                    'player_name' => $players[ $player_id ]['player_name']
               ));
            } else {
                $players_to_damage_real[] = $player_id;
            }
        }


        $this->gamestate->setPlayersMultiactive($players_to_damage_real, 'damages_ok');
    }

    function getInvasionCards()
    {
        $cards = $this->invasioncards->getCardsInLocation('inprogress');

        $player_to_value = array();
        foreach ($cards as $card) {
            $player_to_value[ $card['location_arg'] ] = $this->invasion_cards_types[ $card['type'] ]['value'];
        }

        return $player_to_value;
    }

    function sortInvasion($a, $b)
    {
        if ($a['type'] == $b['type']) {
            return 0;
        } elseif ($a['type'] > $b['type']) {
            return -1;
        } else {
            return 1;
        }
    }

    function getPlayerAdmiralTrackOrder()
    {
        $sql = "SELECT player_id
                FROM player
                ORDER BY player_xeno_milforce DESC, player_xeno_milforce_tiebreak DESC";
        return self::getObjectListFromDB($sql, true);
    }

    function getPlayerAdmiralTrackMovingOrder()
    {
        $sql = "SELECT player_id, player_xeno_milforce FROM player ";

        if (self::getGameStateValue('xeno_current_wave') <= 0) {
            // For the first wave, we place Admiral discs by turn order
            $sql .= "ORDER BY FIELD (player_id,". implode(",", $this->getTurnOrder()) .")";
        } else {
            // Trick : we process discs from the bottom to the top on each position. This way, tokens moving from the same position to the same positions stay in the same order
            $sql .= "ORDER BY player_xeno_milforce DESC, player_xeno_milforce_tiebreak ASC";
        }

        return self::getObjectListFromDB($sql);
    }
    function scoreGreatestAdmiralEffort()
    {
        $expansion = self::getGameStateValue('expansion');
        if ($expansion != 7) {
            return ;
        }

        $player_to_effort = self::getCollectionFromDB("SELECT player_id, player_effort FROM player", true);

        $players = self::loadPlayersBasicInfos();

        $winners = getKeysWithMaximum($player_to_effort);
        foreach ($winners as $player_id) {
            $player_name = $players[ $player_id ]['player_name'];
            $pscore = $this->updatePlayerScore($player_id, 5, false);
            self::incStat(5, 'greatest_contributor_points', $player_id);


            $this->notifyAllPlayers('updateScore', clienttranslate('Greatest Contributor to War effort : ${player_name} scores 5 points.'),
                                            array(
                                                "player_name" => $player_name,
                                                "player_id" => $player_id,
                                                "score" => $pscore['score'],
                                                "vp" => $pscore['vp'],
                                                "score_delta" => 5,
                                                "vp_delta" => 0     // Note: "consumption" vp
                                           ));
        }

        $xeno_force = [];
        foreach ($this->getSpecializedMilitary() as $player_id => $force) {
            $xeno_force[$player_id] = (int)$force['base'];
            if (isset ($force['xeno'])) {
                $xeno_force[$player_id] += $force['xeno'];
            }
        }

        $players = self::loadPlayersBasicInfos();

        $winners = getKeysWithMaximum($xeno_force);
        foreach ($winners as $player_id) {
            $player_name = $players[ $player_id ]['player_name'];
            $pscore = $this->updatePlayerScore($player_id, 5, false);
            self::incStat(5, 'greatest_admiral_points', $player_id);

            $this->notifyAllPlayers('updateScore', clienttranslate('Greatest Admiral : ${player_name} scores 5 points.'),
                                            array(
                                                "player_name" => $player_name,
                                                "player_id" => $player_id,
                                                "score" => $pscore['score'],
                                                "vp" => $pscore['vp'],
                                                "score_delta" => 5,
                                                "vp_delta" => 0     // Note: "consumption" vp
                                           ));
        }
    }
}
