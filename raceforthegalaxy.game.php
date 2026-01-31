<?php
 /**
  * raceforthegalaxy.game.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  *
  *
  * raceforthegalaxy main game core
  *
  */

use Bga\GameFramework\Table;

class RaceForTheGalaxy extends Bga\GameFramework\Table
{
    private $notif_defered_id = 1;
    private $bUpdateCardCount = false;
    private $bUpdateCardCountDefered = false;

    function __construct()
    {
        parent::__construct();self::initGameStateLabels(array(
            "remainingVp" => 10,
            "repeatPhase" => 11,     // =0 (normal case), =1 (two players mode, when repeating phase II or III) DEPRECATED
            "improvedLogisticsPhase" => 12, //  DEPRECATED
            "draftRound" => 13,
            'prestigeLeader' => 14,
            'prestigeOnLeaderTile' => 15,
            "orbactionnbr" => 16,
            "orbteamhasmoved"=>17,
            "orbteam"=>18,
            "xeno_repulse" => 21,
            "xeno_repulse_goal" => 22,
            "xeno_current_wave" => 23,
            "xeno_empire_defeat" => 24,
            "current_round" => 30,
            "current_phase" => 31, // explore 10, develop 20/21, settle 30/31, consume 40, produce 50 (currently only used in develop and settle)
            "current_subphase" => 32, // always 1, except for settle: normal 1, Improved Logistics 2, Rebel Sneak Attack 3, Supply Convoy 4, TerraformingProject 5, Terraforming Engineers 6

            "expansion" => 100,
            "draft" => 101,
            "takeover" => 102,
            "search" => 103,
            "newWorlds" => 104,
            "presetHands" => 105,
            "goals" => 106,
            "reuseDraft" => 107,
        ));

        require('cards.inc.php');

        $this->cards = self::getNew("module.common.deck");
        $this->cards->init("card");
        $this->cards->autoreshuffle = true;
        $this->cards->autoreshuffle_trigger = array('obj' => $this, 'method' => 'deckAutoReshuffle');

        $this->orbcards = self::getNew("module.common.deck");
        $this->orbcards->init("orbcard");

        $this->artefacts = self::getNew("module.common.deck");
        $this->artefacts->init("artefact");

        $this->invasioncards = self::getNew("module.common.deck");
        $this->invasioncards->init("invasion");
        $this->invasioncards->autoreshuffle = true;
        $this->invasioncards->autoreshuffle_trigger = array('obj' => $this, 'method' => 'invasiondeckAutoReshuffle');
    }

    function getDeck($player_id)
    {
        if (self::getGameStateValue('draft') > 1) {
            $this->cards->autoreshuffle_custom = array(
                'pd'.$player_id => 'pdis'.$player_id
           );

            return 'pd'.$player_id;
        } else {
            return 'deck';
        }
    }
    function getDiscard($player_id)
    {
        return (self::getGameStateValue('draft') > 1) ? 'pdis'.$player_id : 'discard';
    }

    function deckAutoReshuffle($from_location)
    {
        if ($from_location == 'deck') {
            // Deck is reshuffled
            self::notifyAllPlayers('reshuffle', clienttranslate('No more cards in deck ! The discard pile is shuffled back into the draw pile'), array());
        } else {
            // pdXXXXXX
            $player_id = substr($from_location, 2);
            $players = self::loadPlayersBasicInfos();
            self::notifyAllPlayers('reshuffle', clienttranslate('No more cards in ${player_name}`s deck ! The discard pile is shuffled back into the draw pile'), array(
                'player_name' => $players[ $player_id ]['player_name']
           ));
        }
    }

    function invasiondeckAutoReshuffle()
    {
        self::notifyAllPlayers('reshuffle', clienttranslate('No more cards in invasion deck ! Wave 3 cards are shuffled back into the draw pile'), array());
    }


    protected function setupNewGame($players, $options = array())
    {
        $sql = "DELETE FROM player WHERE 1 ";
        self::DbQuery($sql);

        // Create players
        $players_nb = count($players);
        $default_color = array("ff0000", "008000", "0000ff", "ffa500", "000000", "ffffff");
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_color);
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes($player['player_name'])."','".addslashes($player['player_avatar'])."')";
        }
        $sql .= implode(',', $values);
        self::DbQuery($sql);

        self::reattributeColorsBasedOnPreferences($players, array("ff0000", "008000", "0000ff", "ffa500", "000000", "ffffff"));
        self::reloadPlayersBasicInfos();

        // Victory points availables (12 vp / players)
        self::setGameStateInitialValue('remainingVp', 12*$players_nb);

        self::setGameStateInitialValue('repeatPhase', 0);
        self::setGameStateInitialValue('improvedLogisticsPhase', 0);
        self::setGameStateInitialValue('draftRound', 1);
        self::setGameStateInitialValue('prestigeLeader', 0);
        self::setGameStateInitialValue('prestigeOnLeaderTile', 0);
        self::setGameStateInitialValue('search', 0);
        self::setGameStateInitialValue('current_round', 0);

        $expansion = self::getGameStateValue('expansion');
        $bGoals = self::getGameStateValue('goals');
        $rvi_scenario = self::getGameStateValue('takeover') == 3;
        $bNewWorlds = self::getGameStateValue( 'newWorlds' ) == 2;
        $presetHands = self::getGameStateValue( 'presetHands' );
        // Random takeover
        if (($expansion == 3  || $expansion == 4) && self::getGameStateValue('takeover') == 0) {
            $to = bga_rand(1,2);
            if ($to == 1) {
                self::notifyAllPlayers('', clienttranslate('Takeovers are allowed'), array());
            } else {
                self::notifyAllPlayers('', clienttranslate('Takeovers are disabled'), array());
            }
            self::setGameStateValue('takeover', $to);
        }

        $start_worlds = $this->start_world;

        // Initialize all the start worlds which have a preset hand associated
        if ($presetHands) {
            $start_worlds_with_preset = $start_worlds;
            // Remove Old Earth which doesn't have a preset hand
            $key = array_search(33, $start_worlds_with_preset);
            unset($start_worlds_with_preset[$key]);
            // If playing an expansion, we add the start world n°5
            if ($expansion == 2) {
                $start_worlds_with_preset[] = 105;
            } else if ($expansion == 5 || $expansion == 6) {
                $start_worlds_with_preset[] = 234;
            } else if ($expansion == 7 || $expansion == 8) {
                $start_worlds_with_preset[] = 274;
            }
            shuffle($start_worlds_with_preset);
        }

        if ($bNewWorlds) {
            $start_worlds[] = 314;
            $start_worlds[] = 315;
            $start_worlds[] = 316;
            $start_worlds[] = 317;
            $start_worlds[] = 318;
            $start_worlds[] = 319;
        }
        if ($expansion == 2 || $expansion == 3 || $expansion == 4) {
            // Adding new start world from The Gathering Storm
            $start_worlds[] = 107;
            $start_worlds[] = 111;
            $start_worlds[] = 118;
            $start_worlds[] = 105;
        }
        if ($expansion == 3 || $expansion == 4) {
            // Adding new start world from Rebel vs Imperium
            $start_worlds[] = 131;
            $start_worlds[] = 132;
            $start_worlds[] = 133;
        }
        if ($expansion == 4) {
            // Adding new start world from Brink of War
            $start_worlds[] = 181;
            $start_worlds[] = 182;
            $start_worlds[] = 183;
            $start_worlds[] = 184;

            // +5 VP
            self::incGameStateValue('remainingVp', 5);

            // Search / bonus card
            self::DbQuery("UPDATE player SET player_search='1' WHERE 1");
        }
        if ($expansion == 5 || $expansion == 6) {
            // Adding new start world from Alien Artifacts
            $start_worlds[] = 234;
            $start_worlds[] = 233;
            $start_worlds[] = 232;
            $start_worlds[] = 231;
            $start_worlds[] = 230;
        }
        if ($expansion == 7 || $expansion == 8) {
            // Adding new start world from Xeno invation
            $start_worlds[] = 270;
            $start_worlds[] = 271;
            $start_worlds[] = 272;
            $start_worlds[] = 273;
            $start_worlds[] = 274;
        }

        if ($rvi_scenario) {
            $start_worlds =  array(131, 133);
        }

        shuffle($start_worlds);
        $start_world_type_to_player = array();
        $beginners = [];

        $bDraftEnabled = (self::getGameStateValue('draft') > 1);
        $bStartWorldChoiceEnabled = ($bNewWorlds || $bDraftEnabled || $expansion == 3 || $expansion == 4 || $expansion == 5 || $expansion == 6 || $expansion == 7 || $expansion == 8)
            && !$rvi_scenario
            && $presetHands != 1;

        if ($presetHands == 1) {
            // Only deal start worlds with preset hands
            $start_worlds = $start_worlds_with_preset;
        }
        else if ($presetHands == 2) {
            // We first distribute start worlds with preset to beginners. Other players will draw normally.
            foreach ($players as $player_id => $player) {
                if (!$player['beginner']) {
                    continue;
                }
                $beginners[] = $player_id;
                $start_world = array_shift($start_worlds_with_preset);
                $start_world_type_to_player[ $start_world ] = $player_id;

                $key = array_search($start_world, $start_worlds);
                unset($start_worlds[$key]);
            }
        }

        if (!$bStartWorldChoiceEnabled) {
            // Normal Start world setup

            foreach ($players as $player_id => $player) {
                // Don't deal a second world to beginners
                if (in_array($player_id, $beginners)) {
                    continue;
                }
                $start_world_type_to_player[ array_shift($start_worlds) ] = $player_id;
            }
        } else {

            // We distribute TWO start world per player, one from each list (odd / even)
            $odd_start_worlds = array(32, 36); // red, impairs
            $even_start_worlds = array(33, 34, 35); // blue, pairs

            if ($bNewWorlds) {
                $odd_start_worlds[] = 314;
                $odd_start_worlds[] = 316;
                $odd_start_worlds[] = 318;
                $even_start_worlds[] = 315;
                $even_start_worlds[] = 317;
                $even_start_worlds[] = 319;
            }

            if ($expansion == 2 || $expansion == 3 || $expansion == 4) {
                $odd_start_worlds[] = 105;
                $odd_start_worlds[] = 118;
                $even_start_worlds[] = 107;
                $even_start_worlds[] = 111;
            }
            if ($expansion == 3  || $expansion == 4) {
                $odd_start_worlds[] = 131;
                $odd_start_worlds[] = 133;
                $even_start_worlds[] = 132;
            }
            if ($expansion == 4) {
                $even_start_worlds[] = 181;
                $even_start_worlds[] = 183;
                $odd_start_worlds[] = 182;
                $odd_start_worlds[] = 184;
            }
            if ($expansion == 5 || $expansion == 6) {
                // Adding new start world from Alien Artifacts
                $odd_start_worlds[] = 234;
                $odd_start_worlds[] = 232;
                $odd_start_worlds[] = 230;
                $even_start_worlds[] = 233;
                $even_start_worlds[] = 231;
            }
            if ($expansion == 7 || $expansion == 8) {
                // Adding new start worlds from Xeno Invasion
                $odd_start_worlds[] = 270;
                $odd_start_worlds[] = 272;
                $odd_start_worlds[] = 274;
                $even_start_worlds[] = 271;
                $even_start_worlds[] = 273;
            }

            shuffle($odd_start_worlds);
            shuffle($even_start_worlds);

            foreach ($players as $player_id => $player) {
                if (in_array($player_id, $beginners)) {
                    continue;
                }
                $start_world_type_to_player[ array_shift($odd_start_worlds) ] = $player_id;
                $start_world_type_to_player[ array_shift($even_start_worlds) ] = $player_id;
            }
        }

        if ($expansion == 5) {
            // Alien artifacts with orb
            $orb_cards = array(
                'a' => array(),
                'b' => array(),
                'sas' => array(),
                'sas2' => array()
           );
            $categ_to_type = [
                'a' => 0,
                'b' => 1,
                'sas' => 2,
                'sas2' => 3
            ];

            foreach ($this->orb_cards_types as $orb_type_id => $orb_type) {
                $categ = $this->orb_to_categ($orb_type_id);
                $orb_cards[$categ][] = array(
                    'type' => $orb_type_id,
                    'type_arg' => $categ_to_type[$categ],
                    'nbr' => 1
               );
            }

            foreach ($orb_cards as $categ => $cards) {
                $this->orbcards->createCards($cards, $categ);
            }
            $this->orbcards->shuffle('a');
            $this->orbcards->shuffle('b');

            // For each player under 5, remove 6 A and 3 B
            $a_to_remove = (5 - $players_nb) * 6;
            $b_to_remove = (5 - $players_nb) * 3;

            $this->orbcards->pickCardsForLocation($a_to_remove, 'a', 'removed');
            $this->orbcards->pickCardsForLocation($b_to_remove, 'b', 'removed');


            // Finally, put A cards above B cards
            // For that, we first switch all "a" cards to the top
            self::DbQuery("UPDATE orbcard SET card_location='deck', card_location_arg=card_location_arg+1000 WHERE card_location='a'");

            // ... and then
            $this->orbcards->moveAllCardsInLocationKeepOrder('b', 'deck');

            // Create artefacts
            $artefacts = array('A' => array(), 'B' => array());
            foreach ($this->artefact_types as $id => $type) {
                $artefacts[ $type[ 'level' ] ][] = array(
                    'type' => $id,
                    'type_arg' => $type['level'] == 'A'  ? 0 : 1,
                    'nbr' => $type['nbr']
               );
            }
            foreach ($artefacts as $type => $atfs) {
                $this->artefacts->createCards($atfs, $type);
            }
            $this->artefacts->shuffle('A');
            $this->artefacts->shuffle('B');
        }

        if ($expansion == 7) {
            // Xeno invasion

            $wave_to_cards = array(1 => array(), 2 => array(), 3 => array());
            foreach ($this->invasion_cards_types as $type_id => $type) {
                $wave_to_cards[ $type['wave'] ][] = array(
                    'type' => $type_id,
                    'type_arg' => $type['value'],
                    'nbr' => $type['nbr']
               );
            }
            foreach ($wave_to_cards as $wave_id => $wave_cards) {
                $this->invasioncards->createCards($wave_cards, 'wave'.$wave_id);
                $this->invasioncards->shuffle('wave'.$wave_id);
            }

            $nbr_cards = count($players) * 2; // 2 per players
            if (count($players) == 2) {
                $nbr_cards = 2; // 2 players expert => only 2 cards
            }

            $this->invasioncards->pickCardsForLocation($nbr_cards, 'wave1', 'waveok1');
            $this->invasioncards->pickCardsForLocation($nbr_cards, 'wave2', 'waveok2');


            // Finally, put wave 1 cards above wave 2 cards above wave 3 cards
            // For that, we first switch all wave 1 and wave 2 cards to +2000 and +1000 positions
            self::DbQuery("UPDATE invasion SET card_location_arg=card_location_arg+2000 WHERE card_location='waveok1'");
            self::DbQuery("UPDATE invasion SET card_location_arg=card_location_arg+1000 WHERE card_location='waveok2'");

            // ... and then
            $this->invasioncards->moveAllCardsInLocationKeepOrder('waveok1', 'deck');
            $this->invasioncards->moveAllCardsInLocationKeepOrder('waveok2', 'deck');
            $this->invasioncards->moveAllCardsInLocationKeepOrder('wave3', 'deck');

            self::setGameStateValue('xeno_current_wave', -2); // 2 first turn = no invasion
            self::setGameStateValue('xeno_empire_defeat', 0);
            self::setGameStateValue('xeno_repulse_goal', $this->invasion_config[ count($players) ]['goal']);
        }

        // Statistics initialization
        self::initStat('table', 'turn_number', 0);
        self::initStat('table', 'phase_played', 0);
        self::initStat('table', 'phase_explore', 0);
        self::initStat('table', 'phase_develop', 0);
        self::initStat('table', 'phase_settle', 0);
        self::initStat('table', 'phase_consume', 0);
        self::initStat('table', 'phase_produce', 0);

        self::initStat('player', 'milforce', 0);
        self::initStat('player', 'good_produced', 0);
        self::initStat('player', 'tableau_count', 0);
        self::initStat('player', 'chips_count', 0);
        self::initStat('player', 'sixcostdev_points', 0);
        self::initStat('player', 'tableau_points', 0);
        self::initStat('player', 'explore_p1_count', 0);
        self::initStat('player', 'explore_p5_count', 0);
        self::initStat('player', 'develop_count', 0);
        self::initStat('player', 'settle_count', 0);
        self::initStat('player', 'consumesell_count', 0);
        self::initStat('player', 'consumex2_count', 0);
        self::initStat('player', 'produce_count', 0);
        self::initStat('player', 'cards_drawn', 0);

        if ($bGoals) {
            self::initStat('player', 'goal_first_points', 0);
            self::initStat('player', 'goal_most_points', 0);
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

        // Create deck & shuffle
        $sql = "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg) VALUES ";
        $values = array();
        $i = 0;
        foreach ($this->card_types as $card_type_id => $card_type) {
            $bUseThisCard = false;

            if ($bNewWorlds && $card_type_id >= 314 && $card_type_id <= 319) {
                $bUseThisCard = true;
            } elseif ($rvi_scenario && ($card_type_id == 147 || $card_type_id == 149)) {
                // Delay putting Imperium Seat and Rebel Alliance in the deck for the RvI scenario
                // so that they aren't dealt to the players
                $bUseThisCard = false;
            } elseif ($expansion == 1 || $expansion == 0) {   // Base game
                if ($card_type_id >= 0 && $card_type_id < 100) {
                    $bUseThisCard = true;
                }
            } elseif ($expansion == 2) {   // The Gathering Storm

                if ($card_type_id >= 0 && $card_type_id < 120) {
                    $bUseThisCard = true;
                }

                if ($card_type_id == 110) {
                    $bUseThisCard = false; // Note : Don't use TGS version, it's obsolete now
                }

                if ($card_type_id == 12) {
                    $card_type['qt'] ++;    // One more Diplomat in this expansion
                }
            } elseif ($expansion == 3) {
                // Gathering Storm + Rebel vs Imperium

                if ($card_type_id >= 0 && $card_type_id < 170) {
                    $bUseThisCard = true;
                }

                if ($card_type_id == 56 || $card_type_id == 110) {
                    $bUseThisCard = false; // Note : 56 = gambling world first version / 110 = gathering storm version
                }

                if ($card_type_id == 12) {
                    $card_type['qt'] ++;    // One more Diplomat in this expansion (GS)
                }
                if ($card_type_id == 9) {
                    $card_type['qt'] ++;    // One more Research Lab in this expansion (RvsI)
                }
            } elseif ($expansion == 4) {
                // Gathering Storm + Rebel vs Imperium + Brink of War

                if ($card_type_id >= 0 && $card_type_id <= 220) {
                    $bUseThisCard = true;
                }

                if ($card_type_id == 56 || $card_type_id == 110) {
                    $bUseThisCard = false; // Note : 56 = gambling world first version / 110 = gathering storm version
                }

                if ($card_type_id == 12) {
                    $card_type['qt'] ++;    // One more Diplomat in this expansion (GS)
                }
                if ($card_type_id == 9) {
                    $card_type['qt'] ++;    // One more Research Lab in this expansion (RvsI)
                }
            } elseif ($expansion == 5 || $expansion == 6) {
                // Alien Artifacts only

                if ($card_type_id >= 0 && $card_type_id < 100) {
                    $bUseThisCard = true;
                }
                if ($card_type_id >= 230 && $card_type_id < 270) {
                    $bUseThisCard = true;
                }
            } elseif ($expansion == 7 || $expansion == 8) {
                // Xeno invasion only

                if ($card_type_id >= 0 && $card_type_id < 100) {
                    $bUseThisCard = true;
                }
                if ($card_type_id >= 270 && $card_type_id < 314) {
                    $bUseThisCard = true;
                }
            }

            if ($bUseThisCard) {
                $card_cost = $card_type['cost'];
                $card_target = 'deck';
                $card_target_arg = 0;

                if (isset($start_world_type_to_player[ $card_type_id ])) {
                    $player_id = $start_world_type_to_player[ $card_type_id ];
                    $card_target = 'tableau';
                    if (($bDraftEnabled || $bStartWorldChoiceEnabled) && !in_array($player_id, $beginners)) {
                        $card_target = 'hiddentableau';
                    }
                    $card_target_arg = $player_id;
                }

                for ($i=0; $i<$card_type['qt']; $i++) {
                    $values[] = "('$card_type_id', '$card_cost', '$card_target', '$card_target_arg')";
                }
            }
        }

        if ($bGoals) {
            foreach ($this->goal_types as $goal_id => $goal) {
                if (($expansion == 2 && $goal_id < 150)
                || ($expansion == 3 && $goal_id < 175)
                || ($expansion == 4 && $goal_id < 226)
            ) {
                    $values[] = "('$goal_id', '0', 'goal_".$goal['type']."', '0')";
                }
            }
        }

        // Prestige Leader
        if ($expansion == 4) {
            $values[] = "('226', 0, 'goal_pr', 0)";
        }

        $sql .= implode(',', $values);
        self::DbQuery($sql);

        $this->cards->shuffle('deck');
        $this->cards->shuffle('goal_first');
        $this->cards->shuffle('goal_most');

        if ($presetHands) {
            $preset_hands = array(
                1 => array(2, 16, 44, 59), // Public Works, Colony Ship, Gem World, Rebel Fuel Cache
                2 => array(4, 13, 66, 69), // Mining Robots, Expedition Force, Comet Zone, Rebel Miners
                3 => array(14, 15, 67, 83), // Export Duties, New Military Tactics, Former Penal Colony, Malevolent Life Forms
                4 => array(5, 12, 65, 72) // Space Marines, Contact Specialist, Avian Uplift Race, Spice World
            );

            // Add the preset hand from the expansion
            if ($expansion == 2) {
                $preset_hands[5] = array(106, 108, 109, 112); // Space Mercenaries, Alien toy shop, Deserted Alien World, Hive World
            } else if ($expansion == 5 or $expansion == 6) {
                $preset_hands[5] = array(235, 236, 237, 238); // Deep Space Symbionts, LTD., Rebel Gem Smugglers, Self-Repairing Alien Artillery, Imperium Stealth Tactics
            } else if ($expansion == 7 or $expansion == 8) {
                $preset_hands[5] = array(275, 276, 277, 278); // Uplift Terraforming, Alien Weapon Cache, Terraforming Uplift Project, Rebel Black Market Gangs
            }

            $sql = "SELECT card_type, card_location_arg FROM card WHERE card_location='tableau' ";
            $start_worlds = self::getObjectListFromDB($sql);
            foreach ($start_worlds as $start_world) {
                $player_id = $start_world['card_location_arg'];
                // Don't deal a preset hands to non-beginners with this option
                if ($presetHands == 2 && !in_array($player_id, $beginners)) {
                    continue;
                }
                $card_type = $this->card_types[ $start_world['card_type'] ];
                $startworld_number = $card_type['startworld_number'];
                foreach ($preset_hands[$startworld_number] as $preset_card_type) {
                    $preset_cards = $this->cards->getCardsOfType($preset_card_type);
                    $preset_card = array_shift($preset_cards);
                    $this->cards->moveCard($preset_card['id'], 'hand', $player_id);
                }
            }
        }
        if (! $bDraftEnabled && $presetHands != 1) {
            // 6 cards for each players hands
            foreach ($players as $player_id => $player) {
                if ($presetHands != 2 || !in_array($player_id, $beginners)) {
                    $this->cards->pickCards(6, 'deck', $player_id);
                }
            }
        }

        if ($rvi_scenario) {
            // RvI scenario, now we add Imperium Seat and Rebel Alliance to the deck
            $sql = "INSERT INTO card (card_type, card_type_arg, card_location) VALUES (147, 6, 'deck'), (149, 6, 'deck')";
            self::DbQuery($sql);
        }

        if ($bGoals) {
            // Pick goals
            $this->cards->pickCardsForLocation(2, 'goal_most', 'obj_most');
            $this->cards->pickCardsForLocation(4, 'goal_first', 'obj_first');
        }

        if ($expansion == 4) {
            // Goal "prestige leader" is always there
            $this->cards->pickCardsForLocation(1, 'goal_pr', 'obj_most');
        }

        if ($expansion == 5) {
            $this->placeSasCards();
        }
    }

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

    public function nameToColor($name)
    {
        $name = preg_replace('/\*(.*)\*/', '<span class="keyword uplift">$1</span>', $name);
        $name = preg_replace('/£(.*)£/', '<span class="keyword alien">$1</span>', $name);
        $name = preg_replace('/\!(.*)\!/', '<span class="keyword rebel">$1</span>', $name);
        $name = preg_replace('/\+(.*)\+/', '<span class="keyword imperium">$1</span>', $name);
        $name = preg_replace('/€(.*)€/', '<span class="keyword terraforming">$1</span>', $name);
        $name = preg_replace('/\@(.*)\@/', '<span class="keyword xeno">$1</span>', $name);
        $name = preg_replace('/\^(.*)\^/', '<span class="keyword antixeno">$1</span>', $name);

        return $name;
    }

    // Get all datas (complete reset request from client side)
    protected function getAllDatas()
    {
        $result = array('players' => array());

        // Add players RFTG specific infos
        $sql = "SELECT player_id id, player_score score, player_vp vp, player_milforce milforce, player_xeno_milforce xeno_milforce, player_effort effort, player_tmp_gene_force bunker_used ";
        if (self::getGameStateValue('expansion') == 4) {
            $sql .= ', player_prestige prestige, player_search prestige_search ';
        }
        if (self::getGameStateValue('expansion') == 7) {
            $sql .= ', player_defense_award defense_award ';
        }
        $sql .= "FROM player ";
        $sql .= "WHERE 1 ";
        $dbres = self::DbQuery($sql);
        while ($player = mysql_fetch_assoc($dbres)) {
            $result['players'][ $player['id'] ] = $player;
        }

        // Xeno tiebreak
        $player_tiebreaks = self::getCollectionFromDB("
            SELECT player_id, player_xeno_milforce_tiebreak
            FROM player
            WHERE player_xeno_milforce IN (
                SELECT player_xeno_milforce
                FROM player
                GROUP BY player_xeno_milforce
                HAVING COUNT(*) > 1
           )", true);
        foreach ($player_tiebreaks as $player_id => $tiebreak) {
            $result['players'][ $player_id ]['xeno_milforce_tiebreak'] = $tiebreak;
        }

        // Specialized military
        $result['specialized_military'] = $this->getSpecializedMilitary();

        // Add "alien"/"uplift"/.... labels to translated names
        foreach ($this->card_types as $card_type_id => $card_type) {
            $this->card_types[$card_type_id]['nametr'] = $this->nameToColor($this->card_types[$card_type_id]['nametr']);
        }

        // Card list (must generate HTML tooltip)
        $result['card_types'] = $this->card_types;
        $result['goal_types'] = $this->goal_types;
        $result['good_types'] = $this->good_types;
        foreach ($result['card_types'] as $card_type_id => $card) {
            // Card name
            $tooltip_html = "<div class='cardtooltip'>";
            $tooltip_html .= "<div class='cardtt_name'>".$card['nametr']."</div><hr/>";

            // World/Development, cost X, victory point
            $tooltip_html .= "<div class='cardtt_basicinfos'>";
            if ($card['type'] == 'development') {
                $tooltip_html .= self::_('Development');
            } else {
                $tooltip_html .= self::_('World');
            }
            $tooltip_html .= ' &bull; '.self::_('Cost').' '.$card['cost'];
            $tooltip_html .= ' &bull; '.self::_('Points').' ';

            if ($card['vp'] == 0 && $card['type'] == 'development' && $card['cost'] == 6) {
                $tooltip_html .= '?';
            } else {
                $tooltip_html .= $card['vp'];
            }

            $tooltip_html .= "</div>";

            // Categories (windfall/military/rebel)
            $tooltip_html .= "<div class='cardtt_category'>";
            $category_translated = array();
            foreach ($card['category'] as $category) {
                if ($category == 'chromosome') {
                    $category_translated[] = 'XX';
                } else {
                    $category_translated[] = self::_($category);
                }
            }
            $tooltip_html .= implode(' &bull; ', $category_translated);
            $tooltip_html .= "</div><hr/>";

            if ($card['name'] == "Alien Oort Cloud Refinery") {
                $tooltip_html .= "OORT_KIND";
            }

            // Card powers
            $special_powers = array();
            $tooltip_html .= "<div class='cardtt_powers'>";
            foreach ($card['powers'] as $phase_id => $powers) {
                foreach ($powers as $power) {
                    $tooltip_html .= "<div class='cardtt_power'>";
                    $tooltip_html .= $this->cardpower_to_html($phase_id, $power);
                    $tooltip_html .= "</div>";
                }
            }

            // Specifics
            if ($card['name'] == "Ancient Race") {
                $tooltip_html .= '<hr/>'.self::_("When Ancient Race is your start world, you start the game with 3 cards instead of 4");
            }
            if ($card['name'] == "Pan-Galactic Research") {
                $tooltip_html .= '<hr/>'.self::_("You can keep up to 12 cards at the end of each round.");
            }
            if ($card['name'] == "Hidden Fortress") {
                $tooltip_html .= '<hr/>'.self::_("<b>GAME END</b> : you trigger end of game at 14 (not 12) or more cards in tableau.");
            }
            if ($card['name'] == "Federation Capital") {
                $tooltip_html .= '<hr/>'.self::_("Score 1 point for each PRG at the end of the game.");
            }
            if ($card['name'] == "Psi-Crystal World") {
                $tooltip_html .= '<hr/>'.self::_("On round start, you select your action after all players has revealed theirs.");
            }
            if ($card['name'] == "Galactic Scavengers") {
                $tooltip_html .= '<hr/>'.self::_("When Galactic Scavengers is your start world, place 1 card from your hand under this world before your first action.");
            }
            if ($card['name'] == "Black Hole Miners") {
                $tooltip_html .= '<hr/>'.self::_("You can keep up to 12 cards at the end of each round.");
            }
            if ($card['name'] == "Retrofit & Salvage, inc.") {
                $tooltip_html .= '<hr/>'.self::_("<b>ROUND END</b> : after discarding, take all other player's discards into your hand and keep them.");
            }
            if ($card['name'] == "Alien Oort Cloud Refinery") {
                $tooltip_html .= '<hr/>'.self::_("The kind of this world can be changed anytime (and once before scoring)");
            }
            if ($card['name'] == "Alien Research Ship") {
                $tooltip_html .= '<hr/>'.self::_("Add an airlock card to Orb + put a new Survey team on it");
            }

            $tooltip_html .= "</div>";

            $result['card_types'][$card_type_id]['tooltip'] = $tooltip_html;
            $result['card_types'][$card_type_id]['kind'] = $this->getCardColorFromType($card);

            // 6 cost devs and worlds with similar scoring
            if ($card['type'] == 'development' && $card['cost'] == 6 && $card_type_id != 151
                || in_array($card_type_id, [247, 267, 283, 294])) {
                $result['card_types'][$card_type_id]['sixdev_scoring'] = $this->sixcostdev_html($card_type_id);
            }
        }

        $goals = $this->cards->getCardsInLocation('obj_first') + $this->cards->getCardsInLocation('obj_most');
        foreach ($goals as $goal) {
            $goal_type = $this->goal_types[ $goal['type'] ];
            // Don't track progress of first goals already awarded
            if ($goal_type['type'] != 'first' || $goal['location_arg'] == 0) {
                $progress = $this->getGoalProgress($goal_type);
                $result['goal_types'][$goal['type']]['progress'] = $this->getGoalProgressTooltip($goal_type, $progress);
            }
        }

        // Player's hand
        $player_id = self::getCurrentPlayerId();
        $result['hand'] = $this->cards->getCardsInLocation('hand', $player_id);

        $result['hand_count'] = $this->cards->countCardsByLocationArgs('hand');

        $result['expansion'] = self::getGameStateValue('expansion');

        // Tableaux
        $tableau = self::getObjectListFromDB("SELECT card_id FROM tableau_order", true);
        $damaged_worlds = self::getCollectionFromDB("SELECT card_id, card_damaged FROM card WHERE card_damaged!='0'", true);
        // Exclude cards that were "just played" (not supposed to be visible by other players with a refresh)
        $just_played_by_others = self::getObjectListFromDB("SELECT player_just_played FROM player WHERE player_id!=".$player_id, true);
        $dev_phase = $this->gamestate->state()['name'] == 'develop';


        if (count($tableau) != $this->cards->countCardsInLocation('tableau')) {
            // Old method which doesn't preserve tableau order, kept for transition
            $result['tableau'] = $this->cards->getCardsInLocation('tableau');
            foreach ($damaged_worlds as $world_id => $world_type) {
                $result['tableau'][$world_id]['damaged'] = $world_type;
            }

            foreach ($just_played_by_others as $card_id) {
                unset($result['tableau'][ $card_id ]);
            }
        } else {
            $result['tableau'] = array();
            foreach ($tableau as $card_id) {
                $card = $this->cards->getCard($card_id);
                $card_type = $this->card_types[ $card['type'] ];
                // Don't hide dev if we're not in the dev phase because player_just_played is also used for takeover
                if (in_array($card_id, $just_played_by_others)
                    && ($dev_phase || $card_type['type'] != 'development')) {
                    continue;
                }

                if (isset($damaged_worlds[$card['id']])) {
                    $card['damaged'] = $damaged_worlds[$card['id']];
                }
                $result['tableau'][] = $card;
            }
        }

        $result['hiddentableau'] = $this->cards->getCardsInLocation('hiddentableau', $player_id);
        $result['drafted'] = $this->cards->getCardsInLocation('drafted', $player_id);

        $result['explored'] = $this->cards->getCardsInLocation("explored", $player_id);

        if (count($this->scanTableau(2, $player_id, 'scavengerdev')) > 0) {
            $result['scavenger'] = $this->cards->getCardsInLocation('scavenger');
        }

        // Goods (we add good type to card infos)
        $result['good'] = array();
        $sql = "SELECT card_id good_id, card_location_arg world_id, card_status good_type FROM card ";
        $sql .= "WHERE card_location='good' ";
        $dbres = self::DbQuery($sql);
        while ($row = mysql_fetch_assoc($dbres)) {
            $result['good'][] = $row;
        }

        // Goods produced by the player
        $result['produced_goods'] = array (0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0);
        $produced_goods = self::getCollectionFromDB("
            SELECT pp_good_id, COUNT(*)
            FROM player_production
            WHERE pp_player_id=$player_id
            GROUP BY pp_good_id", true);
        foreach ($produced_goods as $good_type => $n) {
            $result['produced_goods'][$good_type] += $n;
            $result['produced_goods'][0] += $n;
        }

        $result['phase_choices'] = $this->getPhaseChoices();
        $state = $this->gamestate->state();
        $players = self::loadPlayersBasicInfos();
        if ($state['name'] == 'phaseChoice') {
            $result['phase_choices'] = $this->getPhaseChoices(self::getCurrentPlayerId());
        } else {
            $result['phase_choices'] = $this->getPhaseChoices();
        }

        $result['remain_chips'] = self::getGameStateValue('remainingVp');
        $result['draft'] = self::getGameStateValue('draft') > 1;
        if ($result['draft']) {
            $sql = "SELECT player_id, count(*) FROM player JOIN card ON card_location=CONCAT('pd',player_id) GROUP BY player_id";
            $result['pdeck'] = self::getCollectionFromDB($sql, true);
        } else {
            $result['deck'] = $this->cards->countCardInLocation('deck');
        }

        $result['takeovers'] = (in_array(self::getGameStateValue('expansion'), array(3,4)) && self::getGameStateValue('takeover') == 1 || self::getGameStateValue('takeover') == 3);
        $result['scavengercount'] = $this->cards->countCardInLocation('scavenger');
        $result['prestigeleadercount'] = self::getGameStateValue('prestigeOnLeaderTile');

        // Goals
        $result['goals'] = array(
            'first' => $this->cards->getCardsInLocation('obj_first'),
            'most' => $this->cards->getCardsInLocation('obj_most')
       );

        // Orb game
        if ($result['expansion'] == 5) {
            $result['orb_cards_types'] = $this->orb_cards_types;
            $result['artefact_types'] = $this->artefact_types;

            $result['orbcards'] = self::getObjectListFromDB("SELECT card_id,card_type, card_x,card_y,card_ori FROM orbcard WHERE card_location='orb' ORDER BY card_location_arg ASC");
            $result['orbhand'] = $this->orbcards->getCardsInLocation("hand", $player_id);
            $result['orb'] = $this->getOrb();
            $result['orb_deck'] = self::getCollectionFromDB("SELECT card_type_arg, COUNT(*) FROM orbcard WHERE card_location='deck' GROUP BY card_type_arg", true);
            $result['teams'] = $this->getTeams();
            $result['orbteamhasmoved'] = self::getGameStateValue('orbteamhasmoved');
            $result['priority']=self::getCollectionFromDB("SELECT player_id, player_orb_priority FROM player", true);
            $result['artefact'] = $this->artefacts->getCardsInLocation('orb');
            $result['arthand'] = $this->artefacts->getCardsInLocation('hand', $player_id);
            $result['artplayed'] = $this->artefacts->getCardsInLocation('tableau');
            foreach (array_keys($players) as $player_id) {
                $result['orbhand_count']['a'][$player_id] = 0;
                $result['orbhand_count']['b'][$player_id] = 0;
                $result['artefact_count']['A'][$player_id] = 0;
                $result['artefact_count']['B'][$player_id] = 0;
            }
            foreach ($this->orbcards->getCardsInLocation("hand") as $card) {
                $cat = $this->orb_to_categ($card['type']);
                if ($cat != 'sas2') {
                    $result['orbhand_count'][$cat][$card['location_arg']]  += 1;
                }
            }
            foreach ($this->artefacts->getCardsInLocation("hand") as $art) {
                $result['artefact_count'][$this->artefact_types[$art['type']]['level']][$art['location_arg']]  += 1;
            }
            foreach ($result['artefact'] as $card_id => $art) {
                $result['artefact'][$card_id]['type'] = 0;
            }
            foreach ($result['orb'] as $x => $column) {
                foreach ($column as $y => $square) {
                    if ($square['content_id'] != 0) {
                        $result['artefact'][ $square['content_id'] ]['x'] = $x;
                        $result['artefact'][ $square['content_id'] ]['y'] = $y;
                        $result['artefact'][ $square['content_id'] ]['content'] = $square['content'];
                    }
                }
            }
        }

        $result['xeno'] = array(
            'repulse' => self::getGameStateValue('xeno_repulse'),
            'repulse_goal' => self::getGameStateValue('xeno_repulse_goal'),
            'current_wave' => self::getGameStateValue('xeno_current_wave'),
            'empire_defeat' => self::getGameStateValue('xeno_empire_defeat'),
            'wave_remaining' => $this->getWaveRemaining()
       );

        return $result;
    }

    function getWaveRemaining()
    {
        // Get number of remaining card in current Xeno invasion wave

        $current_wave = self::getGameStateValue('xeno_current_wave');
        if ($current_wave < 0) {
            return 0;
        } else {
            if ($current_wave == 1) {
                return self::getUniqueValueFromDB("SELECT COUNT(card_id) FROM invasion WHERE card_location='deck' AND card_type <= 6");
            } elseif ($current_wave == 2) {
                return self::getUniqueValueFromDB("SELECT COUNT(card_id) FROM invasion WHERE card_location='deck' AND card_type >= 7 AND card_type <= 14");
            } elseif ($current_wave == 3) {
                return self::getUniqueValueFromDB("SELECT COUNT(card_id) FROM invasion WHERE card_location='deck' AND card_type >= 15");
            }
        }
    }

    // Return an array with options infos for this game
    function getGameOptionsInfos()
    {
        return array(

       );
    }

    function getGameProgression()
    {
        // Game progression: get player maximum score

        $remainingVp = self::getGameStateValue("remainingVp");
        $tableau_count = $this->cards->countCardsByLocationArgs('tableau');


        $initialVp = max(1, count($tableau_count) * 12);

        $vpProgression = round(($initialVp-$remainingVp) * 100 / $initialVp);
        $vpProgression = min($vpProgression, 100);
        $tableauProgression = 0;
        foreach ($tableau_count as $count) {
            $progression = round(($count-1)*100/11);   // Note: 1 card initial, and go to 12 cards
            $progression = min($progression, 100);
            $tableauProgression = max($tableauProgression, $progression);
        }

        return max($tableauProgression, $vpProgression);
    }

    function is_twoplayers()
    {
        $players = self::loadPlayersBasicInfos();
        if (count($players) == 2) {
            return true;
        } else {
            return false;
        }
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Notification defered
////////////

    // Remove a full notification reference (and cancel its send)
    function reset_defered_notif($notif_ref)
    {
        $sql = "DELETE FROM notification WHERE notification_reference='$notif_ref' ";
        self::DbQuery($sql);
    }

    // Defered "notify all players"
    function defered_notifyAllPlayers($notif_ref, $notif_type, $notif_log, $notif_args)
    {
        $notifications = $this->getDeferedNotification($notif_ref);
        $notifications[] = array(
            "type" => $notif_type,
            "log" => $notif_log,
            "args" => $notif_args
       );
        $this->storeDeferedNotification($notif_ref, $notifications);
    }
    // Defered "notify player"
    function defered_notifyPlayer($notif_ref, $notif_player, $notif_type, $notif_log, $notif_args)
    {
        $notifications = $this->getDeferedNotification($notif_ref);
        $notifications[] = array(
            "type" => $notif_type,
            "player" => $notif_player,
            "log" => $notif_log,
            "args" => $notif_args
       );
        $this->storeDeferedNotification($notif_ref, $notifications);
    }

    function send_defered_notif($notif_ref)
    {
        $notifications = $this->getDeferedNotification($notif_ref);
        foreach ($notifications as $notification) {
            if (isset($notification['player'])) {
                self::notifyPlayer($notification['player'], $notification['type'], $notification['log'], $notification['args']);
            } else {
                self::notifyAllPlayers($notification['type'], $notification['log'], $notification['args']);

                if ($notification['type'] == 'updatePrestige'
                    && $notification['args']['nbr'] < 0
                    && self::getGameStateValue('prestigeLeader') == $notification['args']['player_id']) {
                    $this->checkGoals('prestige_spent');
                }
            }
        }
        $this->reset_defered_notif($notif_ref);
    }

    private function getDeferedNotification($notif_ref)
    {
        $sql = "SELECT notification_contents FROM notification WHERE notification_reference='$notif_ref' ";
        $dbres = self::DbQuery($sql);
        $row = mysql_fetch_assoc($dbres);
        if ($row) {
            $contents = $row['notification_contents'];
            return unserialize($contents);
        } else {
            return array();
        }
    }

    private function storeDeferedNotification($notif_ref, $contents)
    {
        $contents_string = serialize($contents);
        $sql = "REPLACE INTO notification (notification_reference, notification_contents) VALUES ";
        $sql .= "('$notif_ref', '".addslashes($contents_string)."')";
        self::DbQuery($sql);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions    (functions used everywhere)
////////////

    function cardpower_to_html($phase_id, $power)
    {
        $expansion = self::getGameStateValue('expansion');
        $bXeno = $expansion == 7 || $expansion == 8;

        $html = '<b>';
        switch ($phase_id) {
            case 1:
                $html .= 'I';
                break;
            case 2:
                $html .= 'II';
                break;
            case 3:
                $html .= 'III';
                break;
            case 's':
                $html .= '$';
                break;
            case 4:
                $html .= 'IV';
                break;
            case 5:
                $html .= 'V';
                break;
        }
        $html .= '</b>: ';
        switch ($power['power']) {
            case 'militaryforce': //ok
                if ($power['arg']['force'] > 0) {
                    $html .= sprintf(self::_("+%s to your military force"), $power['arg']['force']);
                } else {
                    $html .= sprintf(self::_("-%s to your military force"), abs($power['arg']['force']));
                }
                if (isset($power['arg']['worldtype']) && count($power['arg']['worldtype']) == 1) {
                    $html .= ' '.sprintf(self::_("(for %s worlds)"), $this->good_types[ $power['arg']['worldtype'][0] ]);
                }
                if (isset($power['arg']['worldfilter']) &&  $power['arg']['worldfilter']=='rebel') {
                    $html .= ' '.sprintf(self::_("(for rebel worlds)"), $power['arg']['worldfilter']);
                }
                if (isset($power['condition'])) {
                    if ($power['condition'] == 'imperium') {
                        $html .= ' ('.self::_("If you have an Imperium card in your tableau").')';
                    }
                }
                if (isset($power['arg']['xenodefense'])) {
                    $html .= ' '.sprintf(self::_("(for Defense vs Xenos)"));
                }
                if (isset($power['arg']['xeno'])) {
                    $html .= ' '.sprintf(self::_("(for Xenos worlds)"));
                }

                break;
            case 'militaryforce_permilitary':
                if (isset($power['arg']['rebel'])) {
                    $html .= ' '.self::_("+1 to your military force for each Rebel military world in your tableau");
                } else {
                    $html .= ' '.self::_("+1 to your military force for each military world in your tableau");
                }
                break;
            case 'militaryforce_perchromosome':
                    $html .= ' '.self::_("+1 to your military force for each XX world in your tableau");
                break;
            case 'militaryforce_perimperium':
                    $html .= ' '.self::_("+1 Military for each Imperium card in your tableau");
                break;
            case 'militaryforce_percivil':
                    $html .= ' '.self::_("+1 to your military force for each non-military world in your tableau");

                if (isset($power['arg']['xenodefense'])) {
                    $html .= ' '.sprintf(self::_("(for Defense vs Xenos)"));
                }
                if (isset($power['arg']['xeno'])) {
                    $html .= ' '.sprintf(self::_("(for Xenos worlds)"));
                }

                break;
            case 'exploredraw'://ok
                if (isset($power['arg']['perrebel'])) {
                    $html .= sprintf(self::_("Draw %s more cards per Rebel Military World"), $power['arg']['card'] );
                } else {
                    $html .= sprintf(self::_("Draw %s more cards"), $power['arg']['card'] );
                }
                break;
            case 'explorekeep'://ok
                    $html .= sprintf(self::_("Keep %s more cards"), $power['arg']['card'] );
                break;
            case 'exploremix':
                    $html .= self::_("Combine your exploration draws with your hand before discarding");
                break;

            case 'draw'://ok
                    $html .= sprintf(self::_("Draw %s card(s)"), $power['arg']['card'] );
                if (isset($power['arg']['thendiscard'])) {
                    $html .= ' '.self::_("Then discard a card.");
                }
                break;
            case 'drawforeachgoodtype':
                    $html .= self::_("Draw a card for each different type of good produced");
                break;
            case 'drawifproduce'://ok
                    $html .= sprintf(self::_("Draw %s card id you produce a resource on this world"), $power['arg']['card'] );
                break;
            case 'discardtoputgood':
                    $html .= self::_("You may discard a card to put a good on this world when you place it") ;
                break;
            case 'settlereplace':
                    $html .= self::_("You may replace (at no cost) a non-military world with another non-military world of the same kind with a 0-3 higher cost and gain 1 PRG.");
                break;
            case 'drawifdev'://ok
                if (isset($power['arg']['pr'])) {
                    if (isset($power['rebel'])) {
                        $html .= sprintf(self::_("Gain %s PRG if you place a Rebel development"), $power['arg']['pr']) ;
                    } elseif (isset($power['onlyif_six_dev'])) {
                        $html .= sprintf(self::_("Gain %s PRG if you place a 6 cost development"), $power['arg']['pr']) ;
                    } else {
                        $html .= sprintf(self::_("Gain %s PRG if you place a development"), $power['arg']['pr']) ;
                    }
                } else {
                    if (isset($power['arg']['card']) && $power['arg']['card'] > 1) {
                        $html .= sprintf(self::_("Draw %s cards if you place a development"), $power['arg']['card']);
                    } else {
                        $html .= self::_("Draw a card if you place a development");
                    }
                }
                break;
            case 'drawifsettle':// ok
                if (isset($power['arg']['pr'])) {
                    if (isset($power['military'])) {
                        if (isset($power['rebel'])) {
                            $html .= sprintf(self::_("Gain %s PRG if you place a Rebel Military world"), $power['arg']['pr']) ;
                        }
                    } elseif (isset($power['production'])) {
                        $html .= sprintf(self::_("Gain %s PRG if you place a Production world"), $power['arg']['pr']) ;
                    } else {
                        $html .= sprintf(self::_("Gain %s PRG if you place a world"), $power['arg']['pr']) ;
                    }
                } else {
                    $html .= sprintf(self::_("Draw a %s card(s) if you settle a world."), $power['arg']['card']);

                    if (isset($power['arg']['thendiscard'])) {
                        $html .= ' '.self::_("Then discard a card.");
                    }
                }

                break;
            case 'drawformilitary':
                if (isset($power['arg']['eachtwo'])) {
                    $html .= self::_("Draw a card for each two Military worlds in your tableau");
                } elseif (! isset($power['filter'])) {
                        $html .= self::_("Draw a card for each Military world in your tableau");
                } elseif ($power['filter'] == 'rebel') {
                        $html .= self::_("Draw a card for each Rebel Military world in your tableau");
                }
                break;
            case 'drawforxenomilitary':
                    $html .= self::_("Draw a card for each Xeno Military world in your tableau");
                break;
            case 'drawforrebel':
                    $html .= self::_("Draw a card for each Rebel world in your tableau");
                break;
            case 'drawforchromosome':
                    $html .= self::_("Draw two cards for each XX world within your tableau");
                break;
            case 'drawforimperium':
                    $html .= self::_("Draw a card for each Imperium card in your tableau");
                break;
            case 'drawfordevelopment':
                    $html .= self::_("Draw a card for each 5+ cost development in your tableau");
                break;
            case 'drawforrebelmilitary':
                    $html .= self::_("Draw a card for each Rebel Military world in your tableau");
                break;

            case 'windfallproduce'://ok
                if (count($power['arg']['worldtype']) == 4) {
                    $html .= self::_("Produce on any windfall world");
                } else {
                    $html .= sprintf(self::_("Produce on a %s windfall world"), $this->good_types[ $power['arg']['worldtype'][0] ] );
                }

                if (isset($power['arg']['notthisworld'])) {
                    $html .= ' ('.self::_("not on this world").')';
                }

                break;
            case 'sellbonus'://ok
                if (isset($power['arg']['onecardby'])) {
                    $html .= sprintf(self::_("+%s when selling %s for each XX card in your tableau"), 1, $this->good_types[ $power['arg']['resource'][0] ]);
                } elseif (count($power['arg']['resource']) == 4) {
                        $html .= sprintf(self::_("+%s when selling any good"), $power['arg']['card']);
                } else {
                    $html .= sprintf(self::_("+%s when selling %s"), $power['arg']['card'], $this->good_types[ $power['arg']['resource'][0] ]);
                }
                if (isset($power['arg']['fromthisworld'])) {
                    $html .= ' '.self::_("(from this world)");
                }
                break;
            case 'produce'://ok
                    $html .= sprintf(self::_("Produce: %s"), $this->good_types[ $power['arg']['resource'] ] );
                if (isset($power['arg']['draw'])) {
                    $html .= ' '.sprintf(self::_("and draw %s card(s) if you produce a resource on this world"), $power['arg']['draw']);
                } elseif (isset($power['arg']['pr'])) {
                        $html .= ' '.sprintf(self::_("and gain %s PRG if you produce a good on this world"), $power['arg']['pr']);
                }
                break;
            case 'produceifdiscard'://ok
                    $html .= sprintf(self::_("May produce %s if you choose to discard a card (click on this card to produce)"), $this->good_types[ $power['arg']['resource'] ] );
                break;
            case 'windfallproduceifdiscard':
                if (isset($power['arg']['world_type'])) {
                    $html .= sprintf(self::_("You may produce on a %s windfall world if you discard a card"), $this->good_types[ $power['arg']['world_type'] ]);
                } else {
                    $html .= self::_("May produce on windfall if you choose to discard a card (click on this card to produce)");
                }
                break;
            case 'consume'://ok
                    $inputfactor = 1;
                    $vp_multiplicator = 1;
                if (isset($power['arg']['inputfactor'])) {
                    $inputfactor = $power['arg']['inputfactor'];
                }
                if (count($power['arg']['input']) == 4) {
                    $html .= sprintf(self::_("Consume %s good"), $inputfactor);
                } elseif (count($power['arg']['input']) == 1 && $power['arg']['input'][0] == 'pr') {
                        $html .= sprintf(self::_("Consume %s PRG"), $inputfactor);
                } elseif (count($power['arg']['input']) == 2 && isset($power['arg']['different'])) {
                    $html .= sprintf(self::_("Consume %s and %s"), $this->good_types[ $power['arg']['input'][0] ], $power['arg']['input'][1]=='*' ? self::_('any good') : $this->good_types[ $power['arg']['input'][1] ]);
                    $vp_multiplicator = 1;
                } else {
                        $html .= sprintf(self::_("Consume %s %s"), $inputfactor, $this->good_types[ $power['arg']['input'][0] ]);
                }

                if (isset($power['arg']['different']) && count($power['arg']['input']) != 2) {
                    $html .= ' '.self::_("different");
                }

                if (isset($power['arg']['fromthisworld'])) {
                    $html .= ' '.self::_("from this world");
                }

                    $html .= ' '.self::_("to gain").' ';

                if (isset($power['arg']['output']['vp'])) {
                    $html .= sprintf(self::_("%s victory point"), $power['arg']['output']['vp']*$vp_multiplicator);
                }
                if (isset($power['arg']['output']['card'])) {
                    if (isset($power['arg']['output']['vp'])) {
                        $html .= ' + ';
                    }
                    $html .= sprintf(self::_("%s card"), $power['arg']['output']['card']);
                }
                if (isset($power['arg']['output']['pr'])) {
                    if (isset($power['arg']['output']['vp']) || isset($power['arg']['output']['car'])) {
                        $html .= ' + ';
                    }
                    $html .= sprintf("%s PRG", $power['arg']['output']['pr']);
                }

                if (isset($power['arg']['repeat']) && $power['arg']['repeat'] != 1) {
                    $html .= ' '.sprintf(self::_(' (%s times)'), $power['arg']['repeat']);
                }

                break;
            case 'repair':
                    $html .= self::_("Repair a damaged world");
                break;
            case 'vpchip':
                    $html .= sprintf(self::_("%s victory point"), $power['vp']);
                break;
            case 'consumeall'://ok
                    $html .= self::_("Discard all remaining goods to get that number -1 VPs");
                break;
            case 'consumeforsell'://ok
                    $html .= self::_("Consume any good to draw card's equal to goods trade value");
                if ($power['arg']['usepowers'] == false) {
                    $html .= ' '.self::_("(don't use any sell powers)");
                }
                break;
            case 'cannotsell':
                    $html .= self::_("You cannot sell a resource from this world");
                break;
            case 'consumecard'://ok
                if (isset($power['arg']['output']['vp'])) {
                    $html .= sprintf(self::_("Discard up to %s cards to gain 1 victory point per card (can't be doubled)"), $power['arg']['repeat']);
                } elseif (isset($power['arg']['output']['card'])) {
                        $html .= sprintf(self::_("Discard 1 card to draw 1 card"), $power['arg']['repeat']);
                } elseif (isset($power['arg']['output']['pr'])) {
                        $html .= sprintf(self::_("Discard %s card(s) to gain %s PRG"), isset($power['arg']['inputfactor'])?$power['arg']['inputfactor']:1, $power['arg']['output']['pr']);
                }
                break;
            case 'devcost':
                    $html .= sprintf(self::_("Cost %s for development"), $power['arg']['cost']);
                break;
            case 'devcost_ondiscard':
                    $html .= sprintf(self::_("You may discard this card to place a development at %s cost"), $power['arg']['cost']);
                break;
            case 'settlecost':
                if (count($power['arg']['worldtype']) == 4) {
                    $html .= sprintf(self::_("Cost %s for any worlds"), $power['arg']['cost']);
                } else {
                    $html .= sprintf(self::_("Cost %s for %s worlds"), $power['arg']['cost'], $this->good_types[ $power['arg']['worldtype'][0] ]);
                }
                break;
            case 'drawforeach':
                    $html .= sprintf(self::_("Draw a card for each %s you produce"), $this->good_types[ $power['arg']['resource'] ]);
                break;
            case 'drawforeachtwo':
                    $html .= sprintf(self::_("Draw a card for each two resource you produce"));
                break;
            case 'drawforeachworld':
                    $html .= sprintf(self::_("Draw a card for each %s world in your tableau"), $this->good_types[ $power['arg']['worldtype'] ]);
                break;
            case 'diplomat'://ok
                    $html .= sprintf(self::_("You can settle a %s military world like a normal one"), isset($power['rebel']) ? self::_('rebel') : (isset($power['alien']) ? self::_('alien') : (isset($power['chromosome']) ? 'XX' : '')));
                if (isset($power['discount']) &&  $power['discount']<0) {
                    $html .= ' ('.sprintf(self::_("with a %s cost bonus"), $power['discount']).')';
                }

                if (isset($power['noalien'])) {
                    if ($bXeno) {
                        $html .= ' ('.self::_("not for Alien technology worlds nor Xeno worlds").')';
                    } else {
                        $html .= ' ('.self::_("not for Alien technology worlds").')';
                    }
                } elseif ($bXeno) {
                        $html .= ' ('.self::_("not for Xeno worlds").')';
                }
                break;
            case 'diplomatdiscount'://ok
                    $html .= sprintf(self::_("-%s cost when using a PFM power"), abs($power['discount']));
                break;
            case 'diplomatbonus':
                    $html .= sprintf(self::_("Gain %s PRG when using a PFM power"), abs($power['arg']['pr']));
                break;
            case 'cloaking':
                if (isset($power['gainprestige'])) {
                    $html .= self::_("You may discard this card to place a non-military world as a military world and gain 2 PRG (this can be combined with TKO but not PFM powers)");
                } else {
                    $html .= self::_("You may discard this card to place a non-military world as a military world with -2 cost (this cannot be combined with TKO or PFM powers)");
                }
                break;
            case 'colonyship': //ok
                    $html .= self::_("Discard this card from your tableau to reduce the cost of placing a non-military world to 0. This cannot be used to place an Alien production or windfall world");
                break;
            case 'bonusifbiggestprod': //ok
                if (isset($power['arg']['resource'])) {
                    $html .= sprintf(self::_("Draw %s cards if you produce more %s than the others players"), $power['arg']['card'], $this->good_types[ $power['arg']['resource'] ]);
                } else {
                    $html .= sprintf(self::_("Draw %s card(s) if you produce more goods than the other players"), $power['arg']['card']);
                }
                break;
            case 'bonusifmost':
                    $html .= self::_("Gain 1 PRG if you have the most XX worlds.");
                break;
            case 'militaryforcetmp': //ok
                    $html .= self::_("Add +3 to your military until the end of the phase");

                if (isset($power['arg']['xenodefense'])) {
                    $html .= ' '.sprintf(self::_("(for Defense vs Xenos)"));
                }
                if (isset($power['arg']['xeno'])) {
                    $html .= ' '.sprintf(self::_("(for Xenos worlds)"));
                }

                break;
            case 'militaryforcetmp_discard':
                if (isset($power['arg']['xenodefense'])) {
                    $html .= sprintf(self::_("Discard up to %s cards for a +1 military (Defense vs Xenos) until the end of the phase"), $power['arg']['repeat']);
                } elseif (isset($power['arg']['xeno'])) {
                        $html .= sprintf(self::_("Discard up to %s cards for a +1 military (for Xeno worlds) until the end of the phase"), $power['arg']['repeat']);
                } else {
                    $html .= sprintf(self::_("Discard up to %s cards for a +1 military until the end of the phase"), $power['arg']['repeat']);
                }
                break;
            case 'militaryforcetmp_prestige':
                    $html .= self::_("May spend 1 PRG for +3 military this phase");
                break;
            case 'randomsettle':
                    $html .= self::_("May, as your action, flip a card from the deck. If this is a civil world place it at zero cost. If not, keep it.");
                break;

            case 'good_for_military':
                if (is_array($power['good'])) {
                    $html .= sprintf(self::_("Discard one good for a +%s military until the end of the phase"), $power['arg']['force'] );
                } else {
                    $html .= sprintf(self::_("Discard one %s for a +%s military until the end of the phase"), $this->good_types[ $power['good'] ], $power['arg']['force'] );
                }

                if (isset($power['arg']['xeno'])) {
                    $html .= ' '.sprintf(self::_("(for Xenos worlds)"));
                }
                break;
            case 'good_for_military_defense':
                    if (is_array($power['good'])) {
                        $html .= sprintf(self::_("Discard one good for a +%s Defense vs. a Xeno Invasion this round"), $power['arg']['force'] );
                    } else {
                        $html .= sprintf(self::_("Discard one %s for a +%s Defense vs. a Xeno Invasion this round"), $this->good_types[ $power['good'] ], $power['arg']['force'] );
                    }
                break;
            case 'storeresources':
                    $html .= self::_("At the beginning of Production phase, move all Rare elements resources on this worlds (they may be spended or sell).");
                break;
            case 'gambling':
                    $html .= self::_("Give a number between 1 and 7 and draw a card. Keep if if the cost correspond to your number.");
                    if ($expansion == 1) {
                        $html .= "<hr><table id='gambling_world_stats'><tr><td/><th>0</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>7</th></tr>";
                        $html .= "<tr><td>{development}</td><td>0</td><td>12</td><td>12</td><td>4</td><td>8</td><td>2</td><td>12</td><td>0</td></tr>";
                        $html .= "<tr><td>{civil_world}</td><td>2</td><td>6</td><td>11</td><td>10</td><td>5</td><td>5</td><td>2</td><td>0</td></tr>";
                        $html .= "<tr><td>{military_world}</td><td>0</td><td>6</td><td>7</td><td>3</td><td>2</td><td>2</td><td>2</td><td>1</td></tr></table>";
                    } else if ($expansion == 2) {
                        $html .= "<hr><table id='gambling_world_stats'><tr><td/><th>0</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>7</th></tr>";
                        $html .= "<tr><td>{development}</td><td>0</td><td>15</td><td>12</td><td>6</td><td>8</td><td>2</td><td>15</td><td>0</td></tr>";
                        $html .= "<tr><td>{civil_world}</td><td>2</td><td>8</td><td>14</td><td>12</td><td>5</td><td>6</td><td>2</td><td>0</td></tr>";
                        $html .= "<tr><td>{military_world}</td><td>0</td><td>8</td><td>7</td><td>6</td><td>3</td><td>2</td><td>2</td><td>1</td></tr></table>";
                    }
                    break;
            case 'rvi_gambling':
                $html .= self::_("May ante a card from hand of cost or defense 1-6 to flip that number of cards; if none have a larger cost or defense, lose your ante; otherwise, keep your ante and any one flipped card.");
                break;

            case 'settletwice':
                    $html .= self::_("You may place a second world (do not use a power from the first world / do not draw a Settle bonus card for the second world)");
                break ;

            case 'defense':
                    $html .= self::_("+2 defense against a TKO for each Rebel military world in your tableau and +1 for each other military world");
                break ;
            case 'blocktakeover':
                    $html .= self::_("May spend 1 PRG to automatically defeat a TKO against any player tableau");
                break ;
            case 'discardtotakeover':
                if ($power['targetfilter'] == 'militaryforce') {
                    $html .= self::_("You may discard from tableau to takeover a military world from a tableau with at least +1 military");
                }
                if ($power['targetfilter'] == 'imperium') {
                    $html .= self::_("You may discard from tableau to takeover a military world from an Imperium tableau, adding +2 / Rebel military world in your tableau");
                }
                break;
            case 'takeover':
                if ($power['targetfilter'] == 'militaryforce') {
                    $html .= self::_("You may takeover a military world from a tableau with at least +1 military");
                }
                if ($power['targetfilter'] == 'rebel') {
                    $html .= self::_("You may takeover a Rebel military world");
                }
                if ($power['targetfilter'] == 'imperium') {
                    $html .= self::_("You may takeover a military world from an Imperium tableau, adding +2 / Rebel military world in your tableau");
                }
                if (isset($power['destroy'])) {
                    $html = self::_("You may takeover and DESTROY a military world from a tableau with at least +1 military, and gain 2 PRG");
                }
                break;
            case 'prestigetotakeover':
                    $html .= self::_("Spend 1 PRG to takeover from any player's tableau. If successful, gain 2 PRG.");
                break;
            case 'good_for_devcost':
                    $html .= sprintf(self::_("You may spend a %s to reduce (%s) the cost of a development"), $this->good_types[ $power['good'] ], $power['arg']['cost']);
                break;
            case 'good_for_settlecost':
                    $html .= sprintf(self::_("You may spend a %s to reduce (%s) the cost of a world"), $this->good_types[ $power['good'] ], $power['arg']['cost']);
                break;
            case 'production_goodonsettle':
                    $html .= self::_("Put a good on top of a production world after placing it.");
                break;
            case 'scavengerdev':
                    $html .= self::_("Put one card from a development payment under this world.");
                break;
            case 'scavengersettle':
                    $html .= self::_("Put one card from a world payment under this world.");
                break;
            case 'scavengerproduce':
                    $html .= self::_("Draw all cards saved under this world.");
                break;
            case 'militaryaftersettle':
                    $html .= self::_("You may discard this card to settle a military world after a successful settle (cannot combined with TKO or PFM powers)");
                break;
            case 'colonyship_aftersettle':
                    $html .= self::_("May in addition to any (or no) Settle action, discard this card from tableau to place a non-military world at 0 cost (cannot be used to place Alien technology worlds but can be used with PFM).");
                break;
            case 'additional_military':
                    $html .= self::_("After conquering a military world, you may conquer a second military world using excess military.");
                break;
            case 'orb_movement':
                    $html .= self::_("Orb Survey team are moving +2 squares.");
                break;
        }

        return $html;
    }

    function sixcostdev_html($card_type_id)
    {
        $html = "<table class='six_dev_scoring'>";

        switch ($card_type_id) {
            case 11:
                $html .= "<tr><td>{two_pts}</td><td>{brown_production}</td><td class='six_dev_scoring_text'>" . self::_("Rare elements production world")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{brown_windfall}</td><td class='six_dev_scoring_text'>" . self::_("Rare elements windfall world")."</td></tr>";
                $html .= "<tr><td rowspan=2>{two_pts}</td><td>{named_development}</td><td class='six_dev_scoring_name'>" . self::_("Mining Robots")."</td></tr>";
                $html .= "<tr><td>{named_development}</td><td class='six_dev_scoring_name'>" . self::_("Mining Conglomerate")."</td></tr>";
                break;
            case 21:
                $html .= "<tr><td>{three_pts}</td><td>{yellow_production}</td><td class='six_dev_scoring_text'>" . self::_("Alien technology production world")."</td></tr>";
                $html .= "<tr><td>{two_pts}</td><td>{yellow_windfall}</td><td class='six_dev_scoring_text'>" . self::_("Alien technology windfall world")."</td></tr>";
                $html .= "<tr><td>{two_pts}</td><td class='six_dev_scoring_card_only'>£ALIEN£</td><td colspan=2 class='six_dev_scoring_text'>" . self::_("other £ALIEN£ card (including this one)")."</td></tr>";
                break;
            case 22:
                $html .= "<tr><td>{two_pts}</td><td>{blue_production}</td><td class='six_dev_scoring_text'>" . self::_("Novelty production world")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{blue_windfall}</td><td class='six_dev_scoring_text'>" . self::_("Novelty windfall world")."</td></tr>";
                $html .= "<tr><td rowspan=2>{two_pts}</td><td>{named_development}</td><td class='six_dev_scoring_name'>" . self::_("Consumer Markets")."</td></tr>";
                $html .= "<tr><td>{grey_world}</td><td class='six_dev_scoring_name'>" . self::_("Expanding Colony")."</td></tr>";
                break;
            case 23:
                $html .= "<tr><td>{two_pts}</td><td>{six_development}</td><td class='six_dev_scoring_text'>" . self::_("6-cost development (including this one)")."</td></tr>";
                if (self::getGameStateValue('expansion') == 4) {
                    $html .= "<tr><td>{one_pt}</td><td>{development}</td><td class='six_dev_scoring_text'>" . self::_("other development")."</td></tr>";
                } else {
                    $html .= "<tr><td>{one_pt}</td><td>{dev_lower_than_six}</td><td class='six_dev_scoring_text'>" . self::_("other development")."</td></tr>";
                }
                break;
            case 24:
                $html .=  "<tr><td>{two_pts}</td><td>{rebel_military_world}</td><td class='six_dev_scoring_text'>" . self::_("Rebel military world")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{military_world}</td><td class='six_dev_scoring_text'>" . self::_("other military world")."</td></tr>";
                break;
            case 25:
                $html .= "<tr><td>{one_pt}</td><td>{three_vp_chips}</td><td class='six_dev_scoring_text'>" . self::_("every three VPs in chips, rounded down")."</td></tr>";
                $html .= "<tr><td rowspan=3>{three_pts}</td><td>{named_development}</td><td class='six_dev_scoring_name'>" . self::_("Research Labs")."</td></tr>";
                $html .= "<tr><td>{grey_world}</td><td class='six_dev_scoring_name'>" . self::_("Galactic Trendsetters")."</td></tr>";
                $html .= "<tr><td>{blue_world}</td><td class='six_dev_scoring_name'>" . self::_("Artist Colony")."</td></tr>";
                break;
            case 26:
                $html .= "<tr><td>{one_pt}</td><td>{dev_explore}</td><td class='six_dev_scoring_text'>" . self::_("development with an Explore power")."</td></tr>";
                $html .= "<tr><td>{two_pts}</td><td>{world_explore}</td><td class='six_dev_scoring_text'>" . self::_("world with an Explore power")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{world}</td><td class='six_dev_scoring_text'>" . self::_("other world")."</td></tr>";
                break;
            case 27:
                $html .= "<tr><td>{two_pts}</td><td>{blue_production}{brown_production}<br>{green_production}{yellow_production}</td><td class='six_dev_scoring_text'>" . self::_("production world")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{any_good}</td><td class='six_dev_scoring_text'>" . self::_("good at game end")."</td></tr>";
                break;
            case 28:
                $html .= "<tr><td>{two_pts}</td><td>{dev_consume}</td><td class='six_dev_scoring_text'>" . self::_("development with a Consume power (including this one)")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{world_consume}</td><td class='six_dev_scoring_text'>" . self::_("world with a Consume power")."</td></tr>";
                break;

            case 29:
                $html .= "<tr><td>{X_pts}</td><td>{X_military}</td><td class='six_dev_scoring_text'>" . self::_("total Military (count negative Military but do not count specialized Military)")."</td></tr>";
                break;

            case 30:
                $html .= "<tr><td>{two_pts}</td><td>{green_production}</td><td>{green_windfall}</td><td class='six_dev_scoring_text'>" . self::_("Genes world")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td colspan=2>{military_world}</td><td class='six_dev_scoring_text'>" . self::_("other military world")."</td></tr>";
                $html .= "<tr><td>{three_pts}</td><td colspan=2>{named_development}</td><td class='six_dev_scoring_name'>" . self::_("Contact Specialist")."</td></tr>";
                break;
            case 31:
                $html .= "<tr><td>{two_pts}</td><td>{dev_trade}</td><td class='six_dev_scoring_text'>" . self::_("development with a Trade power (including this one)")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{world_trade}</td><td class='six_dev_scoring_text'>" . self::_("world with a Trade power")."</td></tr>";
                break;

        // The Gathering storm
            case 100:
                $html .= "<tr><td>{two_pts}</td><td>{green_production}</td><td>{green_windfall}</td><td class='six_dev_scoring_text'>" . self::_("Genes world")."</td></tr>";
                $html .= "<tr><td>{three_pts}</td><td colspan=2>{named_development}</td><td class='six_dev_scoring_name'>" . self::_("Genetics Lab")."</td></tr>";
                break;
            case 101:
                $html .= "<tr><td>{two_pts}</td><td>{windfall_world}</td><td class='six_dev_scoring_text'>" . self::_("windfall world")."</td></tr>";
                $html .= "<tr><td>{two_pts}</td><td class='six_dev_scoring_card_only'>€TERRA€</td><td colspan=2 class='six_dev_scoring_text'>" . self::_("€TERRAFORMING€ card (including this one)")."</td></tr>";
                break;
            case 119:
                $html .= "<tr><td>{two_pts}</td><td class='six_dev_scoring_card_only'>+IMPERIUM+</td><td colspan=2 class='six_dev_scoring_text'>" . self::_("+IMPERIUM+ card (including this one)")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{military_world}</td><td class='six_dev_scoring_text'>" . self::_("other military world")."</td></tr>";
                break;

        // Rebel vs imperium
            case 146:
                $html .= "<tr><td>{two_pts}</td><td>{brown_production}</td><td>{brown_windfall}</td><td class='six_dev_scoring_text'>" . self::_("Rare elements world")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td colspan=2>{world}</td><td class='six_dev_scoring_text'>" . self::_("other world")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td colspan=2 class='six_dev_scoring_card_only'>€TERRA€</td><td colspan=3 class='six_dev_scoring_text'>" . self::_("€TERRAFORMING€ card")."</td></tr>";
                break;
            case 147:
                $html .= "<tr><td>{two_pts}</td><td class='six_dev_scoring_card_only'>+IMPERIUM+</td><td colspan=2 class='six_dev_scoring_text'>" . self::_("+IMPERIUM+ card (including this one)")."</td></tr>";
                $html .= "<tr><td>{two_pts}</td><td>{rebel_military_world}</td><td class='six_dev_scoring_text'>" . self::_("Rebel military world")."</td></tr>";
                break;
            case 148:
                $html .= "<tr class='six_dev_scoring_card_only'><td>{X_pts_no_slash}</td><td style='font-weight: bold'>1/3/6/10</td></tr>";
                $html .= "<tr class='six_dev_scoring_card_only'><td colspan=2>{different_kinds}</td></tr>";
                $html .= "<tr class='six_dev_scoring_tooltip_only'><td style='font-weight: bold'>1/3/6/10</td><td>{empty_pts}</td><td>" . self::_("1-4 different kinds of worlds")."{different_kinds}</td></tr>";
                $html .= "<tr><td>{three_pts}</td><td>{named_development}</td><td class='six_dev_scoring_name'>" . self::_("Diversified Economy")."</td></tr>";
                break;
            case 149:
                $html .= "<tr><td>{two_pts}</td><td class='six_dev_scoring_card_only'>!REBEL!</td><td colspan=2 class='six_dev_scoring_text'>" . self::_("!REBEL! card (including this one)")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{military_world}</td><td class='six_dev_scoring_text'>" . self::_("other military world")."</td></tr>";
                break;
            case 150:
                $html .= "<tr><td rowspan=3>{two_pts}</td><td>{named_development}</td><td class='six_dev_scoring_name'>" . self::_("Interstellar Bank")."</td></tr>";
                $html .= "<tr><td>{named_development}</td><td class='six_dev_scoring_name'>" . self::_("Investment Credits")."</td></tr>";
                $html .= "<tr><td>{grey_world}</td><td class='six_dev_scoring_name'>" . self::_("Gambling World")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{development}</td><td class='six_dev_scoring_text'>" . self::_("other development (including this one)")."</td></tr>";
                break;
            case 152:
                $html .= "<tr><td>{three_pts}</td><td class='six_dev_scoring_card_only'>{chromosome}</td><td colspan=2 class='six_dev_scoring_text'>" . self::_("*UPLIFT* world with XX")."</td></tr>";
                $html .= "<tr><td>{two_pts}</td><td class='six_dev_scoring_card_only'>*UPLIFT*</td><td colspan=2 class='six_dev_scoring_text'>" . self::_("other *UPLIFT* card (including this one)")."</td></tr>";
                break;

        // Brink of war
            case 192:
                $html .= "<tr><td>{X_pts}</td><td>{X_minus_military}</td><td class='six_dev_scoring_text'>" . self::_("total negative Military (count negative Military as positive victory points)")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{military_world}</td><td class='six_dev_scoring_text'>" . self::_("military world")."</td></tr>";
                $html .= "<tr><td>{two_pts}</td><td>{named_development}</td><td class='six_dev_scoring_name'>" . self::_("Pan-Galactic Mediator")."</td></tr>";
                break;
            case 193:
                $html .= "<tr><td>{two_pts}</td><td class='six_dev_scoring_card_only'>€TERRA€</td><td colspan=2 class='six_dev_scoring_text'>" . self::_("€TERRAFORMING€ card (including this one)")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{six_development}</td><td class='six_dev_scoring_text'>" . self::_("other 6-cost development")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{blue_production}{brown_production}<br>{green_production}{yellow_production}</td><td class='six_dev_scoring_text'>" . self::_("production world")."</td></tr>";
                break;
            case 197:
                $html .= "<tr><td>{two_pts}</td><td>{blue_production}</td><td>{blue_windfall}</td><td class='six_dev_scoring_text'>" . self::_("Novelty world")."</td></tr>";
                $html .= "<tr><td>{two_pts}</td><td colspan=2>{grey_world}</td><td class='six_dev_scoring_name'>" . self::_("Expanding Colony")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td colspan=2>{world}</td><td class='six_dev_scoring_text'>" . self::_("other world")."</td></tr>";
                break;
            case 199:
                $html .= "<tr><td>{one_pt}</td><td>PRG</td><td class='six_dev_scoring_text'>" . self::_("(additional)")."</td></tr>";
                $html .= "<tr><td rowspan=3>{two_pts}</td><td>{named_development}</td><td class='six_dev_scoring_name'>" . self::_("Export Duties")."</td></tr>";
                $html .= "<tr><td>{named_development}</td><td class='six_dev_scoring_name'>" . self::_("Galactic Renaissance")."</td></tr>";
                $html .= "<tr><td>{grey_world}</td><td class='six_dev_scoring_name'>" . self::_("Terraformed World")."</td></tr>";
                break;
            case 201:
                $html .= "<tr><td>{two_pts}</td><td class='six_dev_scoring_card_only'>£ALIEN£</td><td colspan=2 class='six_dev_scoring_text'>" . self::_("£ALIEN£ card (including this one)")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{blue_production}{brown_production}<br>{green_production}</td><td class='six_dev_scoring_text'>" . self::_("other (non-£ALIEN£) production world")."</td></tr>";
                break;

        // Alien artifacts

            case 260:
                $html .= "<tr><td>{three_pts}</td><td>{grey_world}</td><td class='six_dev_scoring_name'>" . self::_("Alien Rosetta Stone World")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td class='six_dev_scoring_card_only'>£ALIEN£</td><td colspan=2 class='six_dev_scoring_text'>" . self::_("other £ALIEN£ card (including this one)")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{alien_technology_token}</td><td class='six_dev_scoring_text'>" . self::_("Alien Technology token (additional)")."</td></tr>";
                break;
            case 261:
                $html .= "<tr><td>{two_pts}</td><td class='six_dev_scoring_card_only'>€TERRA€</td><td colspan=2 class='six_dev_scoring_text'>" . self::_("€TERRAFORMING€ card (including this one)")."</td></tr>";
                $html .= "<tr><td>{three_pts}</td><td>{grey_world}</td><td class='six_dev_scoring_name'>" . self::_("Terraformed World")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{civil_world}</td><td class='six_dev_scoring_text'>" . self::_("other non-military world")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{alien_science_token}</td><td class='six_dev_scoring_text'>" . self::_("Alien Science token (additional)")."</td></tr>";
                break;
            case 262:
                $html .= "<tr><td>{two_pts}</td><td colspan=2 class='six_dev_scoring_card_only'>*UPLIFT*</td><td colspan=3 class='six_dev_scoring_text'>" . self::_("*UPLIFT* card (including this one)")."</td></tr>";
                $html .= "<tr><td>{two_pts}</td><td>{green_production}</td><td>{green_windfall}</td><td class='six_dev_scoring_text'>" . self::_("other Genes world")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td colspan=2>{alien_uplift_token}</td><td class='six_dev_scoring_text'>" . self::_("Alien Uplift token (additional)")."</td></tr>";
                break;
            case 263:
                $html .= "<tr><td>{two_pts}</td><td class='six_dev_scoring_card_only'>+IMPERIUM+</td><td colspan=2 class='six_dev_scoring_text'>" . self::_("+IMPERIUM+ card (including this one)")."</td></tr>";
                $html .= "<tr><td>{three_pts}</td><td>{brown_civil_windfall}</td><td class='six_dev_scoring_name'>" . self::_("Blaster Gem Mines")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{military_world}</td><td class='six_dev_scoring_text'>" . self::_("other military world")."</td></tr>";
                break;
            case 264:
                $html .= "<tr><td>{one_pt}</td><td>{development}</td><td class='six_dev_scoring_text'>" . self::_("development (including this one)")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{blue_production}{brown_production}<br>{green_production}{yellow_production}</td><td class='six_dev_scoring_text'>" . self::_("production world")."</td></tr>";
                break;
            case 265:
                $html .= "<tr><td>{two_pts}</td><td colspan=2>{civil_world_trade}</td><td class='six_dev_scoring_text'>" . self::_("non-military world with a Trade power")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td colspan=2>{civil_world}</td><td class='six_dev_scoring_text'>" . self::_("other non-military world")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{dev_trade}</td><td>{military_world_trade}</td><td class='six_dev_scoring_text'>" . self::_("other card with a Trade power (including this one)")."</td></tr>";
                break;

        // Xeno invasion
            case 308:
                $html .= "<tr><td>{two_pts}</td><td class='six_dev_scoring_card_only'>^AXENO^</td><td>{world}</td><td class='six_dev_scoring_text'>" . self::_("^ANTi-XENO^ world")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{world}</td><td class='six_dev_scoring_text'>" . self::_("other world")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td class='six_dev_scoring_card_only'>^AXENO^</td><td>{development}</td><td class='six_dev_scoring_text'>" . self::_("^ANTi-XENO^ development (including this one)")."</td></tr>";
                break;
            case 309:
                $html .= "<tr><td>{one_pt}</td><td class='six_dev_scoring_card_only'>^AXENO^</td><td  colspan=2 class='six_dev_scoring_text'>" . self::_("^ANTi-XENO^ card (including this one)")."</td></tr>";
                $html .= "<tr><td>{two_pts}</td><td>{rebel_military_world}</td><td class='six_dev_scoring_text'>" . self::_("Rebel military world")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{xeno_military_world}</td><td class='six_dev_scoring_text'>" . self::_("other Xeno military world")."</td></tr>";
                break;
            case 310:
                $html .= "<tr><td>{three_pts}</td><td colspan=2>{brown_civil_windfall}</td><td class='six_dev_scoring_name'>" . self::_("Blaster Gem Mines")."</td></tr>";
                $html .= "<tr><td>{three_pts}</td><td colspan=2>{brown_civil_production}</td><td class='six_dev_scoring_name'>" . self::_("Imperium Armaments World")."</td></tr>";
                $html .= "<tr><td>{two_pts}</td><td colspan=2 class='six_dev_scoring_card_only'>+IMPERIUM+</td><td colspan=3 class='six_dev_scoring_text'>" . self::_("other +IMPERIUM+ card (including this one)")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{brown_production}</td><td>{brown_windfall}</td><td class='six_dev_scoring_text'>" . self::_("other Rare elements world")."</td></tr>";
                break;
            case 311:
                $html .= "<tr><td>{two_pts}</td><td>{xeno_military_world}</td><td class='six_dev_scoring_text'>" . self::_("Xeno military world")."</td></tr>";
                $html .= "<tr><td>{three_pts}</td><td>{yellow_civil_production}</td><td class='six_dev_scoring_name'>" . self::_("Alien Archives")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td class='six_dev_scoring_card_only'>£ALIEN£</td><td colspan=2 class='six_dev_scoring_text'>" . self::_("other £ALIEN£ card (including this one)")."</td></tr>";
                break;
            case 312:
                $html .= "<tr><td>{two_pts}</td><td colspan=2 class='six_dev_scoring_card_only'>*UPLIFT*</td><td colspan=3 class='six_dev_scoring_text'>" . self::_("*UPLIFT* card (including this one)")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{green_production}</td><td>{green_windfall}</td><td class='six_dev_scoring_text'>" . self::_("other Genes world")."</td></tr>";
                break;
            case 313:
                $html .= "<tr><td>{two_pts}</td><td class='six_dev_scoring_card_only'>€TERRA€</td><td colspan=2 class='six_dev_scoring_text'>" . self::_("€TERRAFORMING€ card (including this one)")."</td></tr>";
                $html .= "<tr><td>{three_pts}</td><td>{grey_world}</td><td class='six_dev_scoring_name'>" . self::_("Terraformed World")."</td></tr>";
                $html .= "<tr><td>{one_pt}</td><td>{blue_production}{brown_production}<br>{green_production}{yellow_production}</td><td class='six_dev_scoring_text'>" . self::_("production world")."</td></tr>";
                break;

            // Worlds with 6cost dev-like scoring

            case 247: // Alien Uplift Chamber
                $html .= "<tr><td>{two_pts}</td><td class='six_dev_scoring_card_only'>*UPLIFT*</td><td class='six_dev_scoring_text'>" . self::_("*UPLIFT* card (including this one)")."</td></tr>";
                break;
            case 267: // Rebel Resistance from Alien Artefact
                $html .= "<tr><td>{two_pts}</td><td class='six_dev_scoring_card_only'>!REBEL!</td><td colspan=2 class='six_dev_scoring_text'>" . self::_("!REBEL! card (including this one)")."</td></tr>";
                break;
            case 283: // Corrosive Uplift World
            case 294: // Uplift Coalition
                $html .= "<tr><td>{one_pt}</td><td class='six_dev_scoring_card_only'>{chromosome}</td><td class='six_dev_scoring_text'>" . self::_("*UPLIFT* world with XX (including this one)")."</td></tr>";
                break;
            }

        $html .= "</table>";
        return $html;
    }

    // Turn order is clockwise, starting with the player with the lowest numbered start world
    // In BGA, clockwise means top to bottom
    function getTurnOrder()
    {
        $order = array();
        $first = intval(self::getUniqueValueFromDB(
            "SELECT player_id FROM player
             ORDER BY player_startworld ASC
             LIMIT 0,1"));
        $next = $first;

        do {
            $order[] = $next;
            $next = $this->getPlayerAfter($next);
        } while ($next != $first);

        return $order;
    }

    function combinePhaseBonus($phase_id, $first, $second) {
        // We are in two player case, and 2 cards was played on the same phase
        // For phase 1 and 4 we do (sum + 1), the incerement signalling the double bonus
        // For phase 2 and 3, we need to know which phase prestige is applied to
        // 2 => 2 normal phases
        // 12 => first is prestige, second is normal
        // 22 => first is normal, second is prestige
        // For phase 1 and 4, there is only one phase so it doesn't matter if prestige is chosen for first or second
        // 2 for both bonuses
        // 12 for both bonuses and prestige
        // For phase 5 we too do (sum + 1) noting that the combination prestige+repair does not exist
        if ($phase_id == 1 && ($first == 100 || $second == 100)) {
            // + Orb case
            return $first + $second + 1;
        } elseif ($phase_id == 2 || $phase_id == 3) {
            // note that the order of insertion is preserved so this is the second choice
            return $first + 2 + 2*$second;
        } else {
            if ($first >= 10 || $second >= 10) {
                return 12;
            } elseif ($phase_id == 5) {
                return $first + $second + 1;
            } else {
                return 2;
            }
        }
    }

    // Return an array "player => bonus" with only players that choose the corresponding phase
    // (note: return void array if nobody choose the phase)
    function getPhaseChoice($phase_id)
    {
        $result = array();
        $sql = "SELECT phase_player,phase_bonus ";
        $sql .= "FROM phase ";
        $sql .= "WHERE phase_id='$phase_id' ";
        $dbres = self::DbQuery($sql);
        while ($row = mysql_fetch_assoc($dbres)) {
            if (isset($result[ $row['phase_player'] ])) {
                $result[ $row['phase_player'] ] = $this->combinePhaseBonus(
                    $phase_id, $result[ $row['phase_player'] ], $row['phase_bonus']);
            } else {
                $result[ $row['phase_player'] ] = $row['phase_bonus'];
            }
        }

        if ($phase_id == 3 && count($result) == 0
            && self::getGameStateValue('xeno_current_wave') == -2
            && $this->cards->countCardInLocation('hiddentableau') == 0) {
            // Exception : Xeno invasion first turn : there is always a Settle phase
            $result[ -1 ] = 0;
        }

        return $result;
    }

    // Return an array "phase => player => bonus"
    // (note: return void array if nobody choose the phase)
    // (filter by player if asked)
    function getPhaseChoices($player_id = null)
    {
        $result = array(1 => array(), 2 => array(), 3 => array(), 4 => array(), 5 => array());
        $sql = "SELECT phase_id, phase_player,phase_bonus ";
        $sql .= "FROM phase ";
        if ($player_id != null) {
            $sql .= "WHERE phase_player='$player_id'";
        }
        $dbres = self::DbQuery($sql);
        while ($row = mysql_fetch_assoc($dbres)) {
            if (isset($result[ $row['phase_id'] ][  $row['phase_player'] ])) {
                $result[ $row['phase_id'] ][ $row['phase_player'] ] = $this->combinePhaseBonus(
                    $row['phase_id'], $result[ $row['phase_id'] ][ $row['phase_player'] ], $row['phase_bonus']);
            } else {
                $result[ $row['phase_id'] ][  $row['phase_player'] ] = $row['phase_bonus'];
            }
        }

        if (count($result[ 3 ]) == 0
            && self::getGameStateValue('xeno_current_wave') == -2
            && $this->cards->countCardInLocation('hiddentableau') == 0) {
            // Exception : Xeno invasion first turn : there is always a Settle phase
            $result[ 3 ][ -1 ] = 0;
        }

        return $result;
    }

    // Scan tableau of one/all players, looking for the powers corresponding to specific phase
    //  if player_id=null => all players
    //  if power_filter=null => all powers
    //  if power_filter=array(..., ...) => only specified powers
    // Result is on the following form:
    //  player_id => array(
    //      power1, power2, power3, ...
    // )
    // ... except if player_id is specified (return directly list of powers)
    function scanTableau($phase_id, $player_id = null, $power_filter = null, $filter_available = false)
    {
        $result = array();
        $cards = $this->cards->getCardsInLocation('tableau', $player_id);

        // Passive powers from cards discarded from tableau are still active until the end of the phase
        // but active powers cannot be used again (card not available)
        if (!$filter_available) {
            $cards += $this->cards->getCardsInLocation('just_discarded', $player_id);
        }

        $card_ids = array_keys($cards);

        $card_status = array();
        if ($filter_available) {
            $sql = "SELECT card_id,card_status FROM card WHERE card_location='tableau'";
            if (! is_null($player_id)) {
                $sql .= " AND card_location_arg=$player_id";
            }

            $card_status= self::getCollectionFromDB($sql, true);
        }

        $player_to_previously_played = null;
        if (self::getGameStateValue('improvedLogisticsPhase') >= 1) {
            // Improved logistics phase : we must IGNORE cards that were previously played
            $player_to_previously_played = self::getCollectionFromDB("SELECT player_id, player_previously_played FROM player WHERE 1", true);
        }

        // We also ignore card just played. They are not active for the current action.
        // Important for takeovers which currently occur at the same time as primary settle.
        $player_to_player_just_played = self::getCollectionFromDB("SELECT player_id, player_just_played FROM player", true);

        foreach ($cards as $card) {
            $card_type_id = $card['type'];
            $card_type = $this->card_types[ $card_type_id ];

            if ($card_type_id == 106 || $card_type_id == 164) { // Space Mercenaries && Mercenary Fleet
                $disabled_status = -2; // their power isn't fully used until -2
            } else {
                $disabled_status = -1;
            }

            if ($filter_available && $card_status[$card['id']] == $disabled_status) {
                continue;
            }

            // If it's a dev, it means it's being used for a takeover, not that it's been just played
            if ($card['id'] == $player_to_player_just_played[ $card['location_arg'] ]
                && $card_type['type'] != 'development') {
                continue;
            }

            // Note: improved logistics ignore cards previously placed
            if ($player_to_previously_played !== null
                && $card['id'] == $player_to_previously_played[ $card['location_arg'] ]) {
                continue;
            }

            if (isset($card_type['powers'][ $phase_id ])) {
                $card_player_id = $card['location_arg'];
                if (! isset($result[ $card_player_id ])) {
                    $result[ $card_player_id ] = array();
                }

                foreach ($card_type['powers'][ $phase_id ] as $power) {
                    if ($power_filter===null || $power['power']==$power_filter) {
                        $power['card_id'] = $card['id'];
                        $power['card_type'] = $card['type'];
                        $result[ $card_player_id ][] = $power;
                    }
                }
            }
        }

        if ($player_id != null) {
            if (isset($result[ $player_id ])) {
                return $result[ $player_id ];
            } else {
                return array();
            }
        } else {
            return $result;
        }
    }

    // Return array "player => number of card to draw/keep during explored phase"
    function getExploredCardNumber()
    {
        $result = array();
        $players = self::loadPlayersBasicInfos();
        $phase_choice = $this->getPhaseChoice(1);

        foreach ($players as $player_id => $player) {
            if (! isset($phase_choice[ $player_id ])) {
                $result[ $player_id ] = array('keep' => 1, 'draw' => 2);
            } else {
                $choice = $phase_choice[ $player_id ];

                if ($choice >= 100) {
                    $choice -= 101; // Disable orb choice
                }

                if ($choice == 0) { // +1 +1 bonus
                    $result[ $player_id ] = array('keep' => 2, 'draw' => 3);
                } elseif ($choice == 1) { // +5 +0 bonus
                    $result[ $player_id ] = array('keep' => 1, 'draw' => 7);
                } elseif ($choice == 2) { // +5 +0 bonus AND +1 +1 bonus (2 players mode)
                    $result[ $player_id ] = array('keep' => 2, 'draw' => 8);
                } elseif ($choice == 10) { // +1 +1 boosted by +6 +1
                    $result[ $player_id ] = array('keep' => 3, 'draw' => 9);
                    $result[ $player_id ]['mix'] = true;
                } elseif ($choice == 11) { // +5 +0 boosted by +6 +1
                    $result[ $player_id ] = array('keep' => 2, 'draw' => 13);
                    $result[ $player_id ]['mix'] = true;
                } elseif ($choice == 12) { // +6 +1 boosted by +6 +1
                    $result[ $player_id ] = array('keep' => 3, 'draw' => 14);
                    $result[ $player_id ]['mix'] = true;
                } else {
                    $result[ $player_id ] = array('keep' => 1, 'draw' => 2);
                }
            }
        }

        $player_to_powers = $this->scanTableau(1);
        $expansion =  self::getGameStateValue("expansion");
        $bMixByDefault = ($expansion == 7 || $expansion == 8);
        $RebelResistance_player = null;

        foreach ($player_to_powers as $player_id => $powers) {
            foreach ($powers as $power) {
                if ($power['power'] == 'explorekeep') {
                    $result[ $player_id ]['keep'] += $power['arg']['card'];
                } elseif ($power['power'] == 'exploredraw') {
                    if (isset($power['arg']['perrebel'])) {
                        $RebelResistance_player = $player_id;
                    } else {
                        $result[ $player_id ]['draw'] += $power['arg']['card'];
                    }
                } elseif ($power['power'] == 'exploremix') {
                    $result[ $player_id ]['mix'] = true;
                }
            }
        }

        if ($RebelResistance_player !== null) {
            // Count rebel military world
            $player_id = $RebelResistance_player;
            $cards = $this->cards->getCardsInLocation('tableau', $player_id);
            foreach ($cards as $card) {
                $card_type=$this->card_types[$card['type']];
                if (in_array('military', $card_type['category']) && in_array('rebel', $card_type['category'])) {
                    $result[ $player_id ]['draw'] ++;
                }
            }
        }

        if ($bMixByDefault) {
            foreach ($result as $player_id => $dummy) {
                $result[ $player_id ]['mix'] = true;
            }
        }

        return $result;
    }

    // Get the number of cards to be discarded by each players
    function getEndRoundDiscardNumber()
    {
        $result = array();
        $players = self::loadPlayersBasicInfos();
        $card_in_hands = $this->cards->countCardsByLocationArgs('hand');
        $pan_galactic_discard_player = self::getUniqueValueFromDB("SELECT card_location_arg FROM card WHERE card_type='151' AND card_location='tableau'");
        $black_hole_discard_player = self::getUniqueValueFromDB("SELECT card_location_arg FROM card WHERE card_type='217' AND card_location='tableau'");

        foreach ($players as $player_id => $player) {
            $card_in_hand = isset($card_in_hands[ $player_id ]) ? $card_in_hands[ $player_id ] : 0;

            $max_in_hand = 10;
            if ($player_id == $pan_galactic_discard_player || $player_id == $black_hole_discard_player) {
                $max_in_hand = 12;
            }
            $result[ $player_id ] = max(0, $card_in_hand-$max_in_hand);
        }
        return $result;
    }

    // Returns whether a world is a production world or not
    function isProductionWorld($card_type)
    {
        if ($card_type['type'] != 'world' || ! isset($card_type['powers'][5])) {
            return false;
        }

        foreach ($card_type['powers'][5] as $thispower) {
            if ($thispower['power'] == 'produce' || $thispower['power'] == 'produceifdiscard') {
                return true;
            }
        }
        return false;
    }

    // Give cards and prestige to the player for the card they just played
    function phaseBonus($player_id)
    {
        $card_id = self::getUniqueValueFromDB("SELECT player_just_played FROM player WHERE player_id=$player_id");

        // If they haven't played anything, no bonus
        if (is_null($card_id)) {
            return;
        }

        $timing = self::getObjectFromDB("SELECT card_played_round round, card_played_phase phase, card_played_subphase subphase FROM card WHERE card_id = $card_id");
        $cround = self::getGameStateValue('current_round');
        $cphase = self::getGameStateValue('current_phase');
        $csubphase = self::getGameStateValue('current_subphase');

        // Check that the card was actually played right now
        // Especially with all the settle subphases this is important
        // The check could probably be done only on the subphase
        if ($timing['round'] != $cround || $timing['phase'] != $cphase || $timing['subphase'] != $csubphase) {
            return;
        }

        $drawCard = 0;  // Number of card drawn if you play this card successfully
        $drawPr = 0;    // Number of prestige if .....

        $card = $this->cards->getCard($card_id);
        $card_type = $this->card_types[ $card['type'] ];
        $phase = $card_type['type'] == 'development' ? 2 : 3;

        // Bonus from settle action
        if ($phase == 3 && self::getGameStateValue('improvedLogisticsPhase') == 0) {
            $phase_choice = $this->getPhaseChoice($phase);
            $bIsPhaseRepeat = (self::getGameStateValue('repeatPhase') == 1);
            $bPhaseBonus = isset($phase_choice[ $player_id ]) && (!$bIsPhaseRepeat || $phase_choice[ $player_id ] % 10 == 2);
            if ($bPhaseBonus) {
                ++$drawCard;
            }
        }

        // Bonus from card powers
        // It's important to filter_available so that cards played in this phase are not active
        // example: don't draw after placing Public Works
        $powers = $this->scanTableau($phase, $player_id, null, true);
        foreach ($powers as $power) {
            if ($power['power'] != 'drawifdev' && $power['power'] != 'drawifsettle') {
                continue;
            }

            if (isset($power['military']) && !in_array('military', $card_type['category'])) {
                continue;
            }

            if (isset($power['rebel']) && !in_array('rebel', $card_type['category'])) {
                continue;
            }

            if (isset($power['onlyif_six_dev']) && $card_type['cost'] != 6) {
                continue;
            }

            if (isset($power['production']) && !$this->isProductionWorld($card_type)) {
                continue;
            }

            if (isset($power['arg']['card'])) {
                $drawCard += $power['arg']['card'];
            }
            if (isset($power['arg']['pr'])) {
                $drawPr +=  $power['arg']['pr'];
            }
        }

         // Draw cards
        if ($drawCard > 0) {
            $this->drawCardForPlayer($player_id, $drawCard, true);
        }

         // Gain prestige
        if (in_array('prestige', $this->card_types[$card['type']]['category'])) {
            $card_status = self::getUniqueValueFromDB("SELECT card_status FROM card WHERE card_id=$card_id");
            if ($card_status != -2) { // If the card hasn't been taken over
                ++$drawPr; // Draw 1 prestige when placing a "prestige" category world
            }
        }

        if ($drawPr > 0) {
            $this->givePrestige($player_id, $drawPr, true);
        }
    }

    // Get the cost of the specific development, taking into account player's tableau
    // Additionally if cost is zero list actionable alternatives (like using R&D Crash Programm)
    function getDevCost($card_id)
    {
        $player_id = self::GetCurrentPlayerId();
        $phase_choice = $this->getPhaseChoice(2);
        $bIsPhaseRepeat = (self::getGameStateValue('repeatPhase') == 1);
        $bPhaseBonus = isset($phase_choice[$player_id]) && (!$bIsPhaseRepeat || $phase_choice[$player_id] % 10 == 2);
        $bPrestigeBonus = $bPhaseBonus && floor($phase_choice[$player_id] / 10) == self::getGameStateValue('repeatPhase') + 1;
        $card = $this->cards->getCard($card_id);

        if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) {
            throw new feException("This card is not in your hand");
        }

        $card_type = $this->card_types[ $card['type'] ];
        if ($card_type['type'] != 'development') {
            throw new feException(self::_('This is not a development'), true);
        }

        $cost = $card_type['cost'];
        if ($bPhaseBonus) {
            $cost --;
        }
        if ($bPrestigeBonus) {
            $cost -= 2;
        }

        $immediateAlternatives = [];
        $powers = $this->scanTableau(2, $player_id);
        foreach ($powers as $power) {
            if ($power['power'] == 'devcost') {
                $cost += $power['arg']['cost'];
            } elseif ($power['power'] == 'devcost_ondiscard') {
                $immediateAlternatives[] = ['kind' => "rdcrashprogram", 'card_id' => $power['card_id']];
            }
        }

        $cost = max(0, $cost);
        if ($cost == 0 && count($immediateAlternatives) > 0) {
            array_unshift($immediateAlternatives, ['kind' => 'pay']);
        } else {
            $immediateAlternatives = [];
        }
        $immediate = $cost == 0 && count($immediateAlternatives) == 0;
        if ($this->userPreferences->get($player_id, 10) == 1) {
            // Player wants to confirm placement, so no immediate action
            $immediate = false;
        }

        // We make this compatible to the return type of getWorldCost to avoid
        // even more churn. Maybe this can at some point be untangled.
        return array(
            'card' => $card,
            'cost' => $cost,
            'isWorld' => false,
            'immediate' => $immediate,
            'immediate_alternatives' => $immediateAlternatives,
            'military_force' => false,
            'use_contact_specialist' => false,
        );
    }

    // Get the cost of the specific world, taking into account player's tableau
    // Additionally if cost is zero list actionable alternatives (like paying via Contact Specialist for a world you could conquer with military)
    function getWorldCost($card_id, $cloaking_card_type = null)
    {
        $player_id = self::GetCurrentPlayerId();
        $phase_choice = $this->getPhaseChoice(3);
        $bIsPhaseRepeat = (self::getGameStateValue('repeatPhase') == 1);
        $bPhaseBonus = isset($phase_choice[$player_id]) && (!$bIsPhaseRepeat || $phase_choice[$player_id] % 10 == 2);
        $bPrestigeBonus = $bPhaseBonus && floor($phase_choice[$player_id] / 10) == self::getGameStateValue('repeatPhase') + 1;
        $bConvoy = self::checkAction('onlyremainingmilitary', false);
        $bTerraformingProject = self::checkAction('onlycivilnonalien', false);
        $bCloaking = ($cloaking_card_type !== null);


        $previous_type = null;
        if ($bConvoy) {
            // Get the type of the previously played world
            $previous_type = self::getUniqueValueFromDB("SELECT card_type FROM player INNER JOIN card ON card_id=player_previously_played WHERE player_id='$player_id'");
        }

        $card = $this->cards->getCard($card_id);
        $bOortCloud = $card['type'] == 220;

        if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) {
            throw new feException("This card is not in your hand");
        }

        $card_type = $this->card_types[ $card['type'] ];
        if ($card_type['type'] != 'world') {
            throw new feException(self::_('This is not a world'), true);
        }

        $bMilitaryWorld = false;
        $bContactSpecialistCase = false;
        $bUseMilitaryForce = false;
        $cost = $card_type['cost']; // In case of military worlds this is not an actionable number except if using a Contact Specialist.
                                    // However setting this to some sentinel value like -1 does not survive and end up as 0.
        if ($bCloaking || in_array('military', $card_type['category'])) { // Note : cloaking = conquer civil world as a military
            $bMilitaryWorld = true;

            if (! $bConvoy) {
                $militaryoptions = $this->getMilitaryForceForWorld($player_id, $card_type);
            } else {
                $militaryoptions = $this->getMilitaryForceForWorld($player_id, $card_type, true, $this->card_types[ $previous_type ]);
            }

            if ($militaryoptions['contactspecialist'] && !$bCloaking && !$bConvoy) {
                $cost = max(0, $card_type['cost']+$militaryoptions['contactspecialist_discount']);
                $bContactSpecialistCase = true;
            }

            if ($cloaking_card_type == 140) {
                $card_type['cost'] = max(0, $card_type['cost']-2);
            }
            $force = $militaryoptions['force'];

            $militarycost = $card_type['cost'];
            if ($previous_type !== null) {
                $militarycost += $this->card_types[ $previous_type ]['cost'];
            }

            if ($force >= $militarycost  && !$bTerraformingProject) {
                $bUseMilitaryForce = true;
            } else {
                // Not enough military force.
                if ($bContactSpecialistCase) {
                    // must use Contact Specialist; nothing to be done here
                } else if ($bTerraformingProject) {
                    throw new feException(self::_("You cannot settle a military world with Terraforming Project"), true);
                } else {
                    if ($previous_type === null) {
                        throw new feException(sprintf(self::_("Your military (%s) is not big enough"), $force), true);
                    } else {
                        throw new feException(sprintf(self::_("Your military (%s) is not big enough to conquer this world after the previous one."), $force), true);
                    }
                }
            }
        }

        if ($bTerraformingProject) {
            if ($this->getCardColorFromType($card_type) == 4) {
                throw new feException(self::_("You cannot settle an Alien world with Terraforming Project"), true);
            }
            $cost = 0;
        }

        if ($bConvoy && !$bMilitaryWorld) {
            throw new feException(self::_("You can only conquer a military world with Imperium Supply Convoy"), true);
        }

        if ($bPrestigeBonus && (!$bMilitaryWorld || $bContactSpecialistCase)) {
            $cost -= 3;
        }

        // DEPRECATED: This whole function no longer handles takeovers
        // // During a takeover, make sure we don't return cards in a disabled state (-1).
        // // This should probably always be the case as setting the state to -1 is a good way
        // // to make sure cards aren't active in the phase they are played. Although I might not
        // // see all the implications it can have yet so let's keep the change to a reduced scope for now
        $powers = $this->scanTableau(3, $player_id);
        $immediateAlternatives = [];
        $bHasMediator = false;
        foreach ($powers as $power) {
            if ($power['power'] == 'settlecost') {
                // This power is not valid for military
                if (! $bMilitaryWorld || $bContactSpecialistCase) {                // Note: In case of a contact specialist, powers apply too
                    // For world, worldtype must match
                    if (count($power['arg']['worldtype']) == 4) {
                        $cost += $power['arg']['cost']; // Any world
                    } elseif (count($power['arg']['worldtype']) == 1) {
                        $color_id = $this->getCardColorFromType($card_type);
                        if ($color_id == $power['arg']['worldtype'][0]) {
                            $cost += $power['arg']['cost'];
                        }
                    }
                }
            } elseif ($power['power'] == 'colonyship' && (!$bMilitaryWorld || $bContactSpecialistCase)) {
                $immediateAlternatives[] = ['kind' => "colonyship", 'card_id' => $power['card_id']];
            } elseif ($power['power'] == 'diplomatbonus') {
                $bHasMediator = true;
            }
        }
        $bHasSalvage = count($this->cards->getCardsOfTypeInLocation(218, null, 'tableau')) > 0;

        $cost = max(0, $cost);

        if ($bUseMilitaryForce && ($bHasMediator || ($bHasSalvage && $cost > 0)) && $bContactSpecialistCase) {
            array_unshift($immediateAlternatives, ['kind' => 'military'], ['kind' => 'pay']);
        } elseif ($cost == 0 && count($immediateAlternatives) > 0 && (!$bMilitaryWorld || $bContactSpecialistCase)) {
            array_unshift($immediateAlternatives, ['kind' => 'pay']);
            if ($bUseMilitaryForce) {
                array_unshift($immediateAlternatives, ['kind' => 'military']);
            }
        } else {
            $immediateAlternatives = [];
        }

        $immediate = count($immediateAlternatives) == 0 && ($bUseMilitaryForce || $cost == 0) && !$bOortCloud;
        if ($this->userPreferences->get($player_id, 10) == 1) {
            // Player wants to confirm placement, so no immediate action
            $immediate = false;
        }

        return array(
            'card' => $card,
            'cost' => $cost,
            'isWorld' => true,
            'immediate' => $immediate,
            'immediate_alternatives' => $immediateAlternatives,
            'military_force' => $bUseMilitaryForce, // may be conquered by military force
            'use_contact_specialist' => $bContactSpecialistCase, // is it possible to use a diplomat power?
        );
    }

    function notifyUpdateCardCount($bDefered = false)
    {
        $this->bUpdateCardCount = true;

        $this->bUpdateCardCountDefered = $bDefered;
    }

    function onEndAjaxAction()
    {
        if ($this->bUpdateCardCount) {
            $args = [
                    'hand' => $this->cards->countCardsByLocationArgs('hand'),
                    'tableau' => $this->cards->countCardsByLocationArgs('tableau')
            ];

            if (self::getGameStateValue('draft') > 1) {
                $sql = "SELECT player_id, count(*) FROM player JOIN card ON card_location=CONCAT('pd',player_id) GROUP BY player_id";
                $args['pdeck'] = self::getCollectionFromDB($sql, true);
            } else {
                $args['deck'] = $this->cards->countCardInLocation('deck');
            }

            if ($this->bUpdateCardCountDefered == false) {
                $this->notifyAllPlayers("updateCardCount", '', $args);
            } else {
                $this->defered_notifyAllPlayers( $this->notif_defered_id, "updateCardCount", '', $args);
            }
        }
    }

    // Update military force for current player if needed by card type
    // NOTE : now, we scan the WHOLE tableau to update the military force to make sure nothing has been forget
    function updateMilforceIfNeeded($player_id, $notif_defered = false, $bXenoForce = false)
    {
        $cards = $this->cards->getCardsInLocation('tableau', $player_id);

        if (! $bXenoForce) {
            $current_milforce = self::getUniqueValueFromDB("SELECT player_milforce FROM player WHERE player_id='$player_id'");
        } else {
            $current_milforce = self::getUniqueValueFromDB("SELECT player_xeno_milforce FROM player WHERE player_id='$player_id'");
        }

        $new_milforce = 0;
        $military_world_count = 0;
        $chromosome_world_count = 0;
        $civil_world_count = 0;
        $imperium_count = 0;
        $bHiddenFortress = false;
        $mil_per_chromosome = 0;
        $mil_per_imperium = 0;
        $mil_per_civil = 0;
        $current_xenodefense = 0;


        $expansion = self::getGameStateValue('expansion');
        $bHiddenFortressXeno = ($expansion == 7 || $expansion == 8);

        foreach ($cards as $card) {
            $card_type_id = $card['type'];

            $card_type = $this->card_types[ $card_type_id ];

            if (isset($card_type['powers'][3])) {
                foreach ($card_type['powers'][3] as $power) {
                    if ($power['power'] == 'militaryforce' && !isset($power['arg']['worldtype']) && !isset($power['arg']['worldfilter'])&& (!isset($power['arg']['xeno']) || $bXenoForce)&& !isset($power['arg']['xenodefense'])) {   // Note: only unspecialized military force is taking into account
                        $force_delta = $power['arg']['force'];

                        if (isset($power['condition'])) {
                            if ($power['condition'] == 'imperium') {
                                // Only valid if player has an imperium card
                                if ($this->checkAtLeastOneCardInTableauWithTag($player_id, 'imperium')) {
                                    $new_milforce += $force_delta;
                                }
                            }
                        } else {
                            // Standard case
                            $new_milforce += $force_delta;
                        }
                    } elseif ($power['power'] == 'militaryforce_permilitary') {
                        $bHiddenFortress = true;
                    } elseif ($power['power'] == 'militaryforce_perchromosome') {
                        $mil_per_chromosome ++;
                    } elseif ($power['power'] == 'militaryforce_perimperium') {
                        $mil_per_imperium ++;
                    } elseif ($power['power'] == 'militaryforce_percivil') {
                        $mil_per_civil ++;
                    }


                    if ($power['power'] == 'militaryforce' && $bXenoForce && isset($power['arg']['xenodefense'])) {
                        $current_xenodefense += $power['arg']['force'];
                    }
                }
            }

            if (in_array('military', $card_type['category']) && $card_type['type']=='world') {
                if ($bHiddenFortressXeno) {
                    if (in_array('rebel', $card_type['category'])) {
                        $military_world_count ++;
                    }
                } else {
                    $military_world_count ++;   // ALl military world
                }
            }
            if (in_array('chromosome', $card_type['category'])) {
                $chromosome_world_count ++;
            }
            if (in_array('imperium', $card_type['category'])) {
                $imperium_count ++;
            }

            if ($bXenoForce && $card_type['type'] == 'world'
                && !in_array('military', $card_type['category'])
                && $card_type_id != 1000) {
                $civil_world_count ++;
            }
        }

        if ($bHiddenFortress) {
            $new_milforce += $military_world_count;
        }

        $new_milforce += ($mil_per_chromosome * $chromosome_world_count);
        $new_milforce += ($mil_per_imperium * $imperium_count);
        $new_milforce += ($mil_per_civil * $civil_world_count);

        if ($new_milforce != $current_milforce || $bXenoForce) {
            if (! $bXenoForce) {
                $sql = "UPDATE player SET player_milforce='$new_milforce' WHERE player_id='$player_id' ";
            } else {
                $sql = "UPDATE player SET player_xeno_milforce='$new_milforce', player_tmp_xenoforce='$current_xenodefense' WHERE player_id='$player_id' ";
            }
            self::DbQuery($sql);

            if ($bXenoForce) {
                self::notifyAllPlayers('updateMilforce', '',
                                    array(
                                        "player_id" => $player_id,
                                        "xeno" => $new_milforce
                                   ));
            } else {
                if ($notif_defered) {
                    $this->defered_notifyAllPlayers($this->notif_defered_id, 'updateMilforce', '',
                                        array(
                                            "player_id" => $player_id,
                                            "force_delta" => $new_milforce-$current_milforce,
                                            "force" => $new_milforce
                                       ));
                } else {
                    self::notifyAllPlayers('updateMilforce', '',
                                        array(
                                            "player_id" => $player_id,
                                            "force_delta" => $new_milforce-$current_milforce,
                                            "force" => $new_milforce
                                       ));
                }
            }
        }

//        var_dump($new_milforce);
//        die('ok');

        return $new_milforce;
    }

    // For oort we check what the player has chosen
    function getCardColorFromType($card_type)
    {
        if ($card_type['name'] == 'Alien Oort Cloud Refinery') {
            return self::getUniqueValueFromDB("SELECT card_status FROM card WHERE card_type=220");
        }

        $card_color = null;
        if ($card_type['type'] == 'world') {
            if (isset($card_type['powers'][5])) {
                foreach ($card_type['powers'][5] as $power) {    // Production powers
                    if ($power['power']=='produce' || $power['power']=='produceifdiscard') {
                        $card_color = $power['arg']['resource'];
                    }
                }
            }

            if (in_array('windfall', $card_type['category'])) {
                $card_color = $card_type['windfalltype'];
            }
        }
        return $card_color;
    }

    // Return military options available for given player for give world type in an array:
    // force => the force available to take over this world
    // contactspecialist => true if a contact specialist can help
    // previous_type => use for imperium supply convoy
    function getMilitaryForceForWorld($player_id, $card_type, $bMyUseSpecialist = true, $previous_type = null)
    {
        $bContactSpecialist = false;
        $contactspecialist_discount = 0;
        $contactspecialist_discount_bonus = 0;

        $expansion = self::getGameStateValue('expansion');

        $bHiddenFortress = false;
        $bAntiXenoRebelForce = false;
        $mil_per_chromosome = 0;
        $mil_per_civil = 0;
        $mil_per_imperium = 0;
        $mil_for_previous = 0;

        $force = 0;

        $bRebel = in_array('rebel', $card_type['category']);
        $bXeno = in_array('xeno', $card_type['category']);
        $card_color = $this->getCardColorFromType($card_type);

        if ($previous_type !== null) {
            $bBisRebel = in_array('rebel', $previous_type['category']);
            $biscard_color = $this->getCardColorFromType($previous_type);
        }

        $powers = $this->scanTableau(3, $player_id);
        foreach ($powers as $power) {
            if ($power['power'] == 'militaryforce') {
                if (isset($power['arg']['worldfilter']) && $power['arg']['worldfilter'] == 'rebel') {
                    if ($bRebel) {
                        $force += $power['arg']['force'];
                    } elseif ($previous_type !== null && $bBisRebel) {
                        $mil_for_previous += $power['arg']['force'];
                    }
                } elseif (isset($power['arg']['worldtype']) && count($power['arg']['worldtype'])==1) {
                    if ($card_color == $power['arg']['worldtype'][0]) {
                        $force += $power['arg']['force'];
                    } elseif ($previous_type !== null && $biscard_color == $power['arg']['worldtype'][0]) {
                        $mil_for_previous += $power['arg']['force'];
                    }
                } elseif (isset($power['condition']) && $power['condition'] == 'imperium') {
                    // Only valid if player has an imperium card
                    if ($this->checkAtLeastOneCardInTableauWithTag($player_id, 'imperium')) {
                        $force += $power['arg']['force'];
                    }
                } elseif (isset($power['arg']['xeno'])) {
                    // Specialized force against Xeno world
                    if ($bXeno) {
                        $force += $power['arg']['force'];
                    }
                } elseif (isset($power['arg']['xenodefense'])) {   // Ignore it (not used for this one)
                } else {
                    $force += $power['arg']['force'];
                }
            } elseif ($power['power'] == 'diplomat' && $bMyUseSpecialist) {
                $bCanUseDiplomat = true;

                if (isset($power['noalien']) && $card_color == 4) {
                    $bCanUseDiplomat = false;
                }
                if (isset($power['alien']) && $card_color != 4) {
                    $bCanUseDiplomat = false;
                }
                if (isset($power['rebel']) && !in_array('rebel', $card_type['category'])) {
                    $bCanUseDiplomat = false;
                }
                if (isset($power['chromosome']) && !in_array('chromosome', $card_type['category'])) {
                    $bCanUseDiplomat = false;
                }
                if (in_array('xeno', $card_type['category'])) {
                    $bCanUseDiplomat = false;   // No diplomat on Xeno
                }

                if ($bCanUseDiplomat) {
                    $bContactSpecialist = true;
                    if (isset($power['discount'])) {
                        $contactspecialist_discount = min($contactspecialist_discount, $power['discount']);
                    }
                }
            } elseif ($power['power'] == 'diplomatdiscount' && $bMyUseSpecialist) {
                $contactspecialist_discount_bonus += $power['discount'];
            } elseif ($power['power'] == 'militaryforce_permilitary') {
                $bHiddenFortress = true;
                if ($expansion == 7 || $expansion == 8) {
                    $bAntiXenoRebelForce = true;    // This is Xeno Invasion version of Hidden fortress (only Rebel world)
                }
            } elseif ($power['power'] == 'militaryforce_perchromosome') {
                $mil_per_chromosome ++;
            } elseif ($power['power'] == 'militaryforce_percivil') {
                $mil_per_civil ++;
            } elseif ($power['power'] == 'militaryforce_perimperium') {
                $mil_per_imperium ++;
            }
        }

        if ($bHiddenFortress) {
            // Should count military worlds
            $military_world_count = 0;
            $cards = $this->cards->getCardsInLocation('tableau', $player_id);
            foreach ($cards as $card) {
                $card_type_id = $card['type'];
                $card_type = $this->card_types[ $card_type_id ];

                if (in_array('military', $card_type['category'])) {
                    if (!$bAntiXenoRebelForce || in_array('rebel', $card_type['category'])) {
                        $military_world_count ++;
                    }
                }
            }

            $force += $military_world_count;
        }
        if ($mil_per_chromosome > 0) {
            // Should count chromosome worlds
            $military_world_count = 0;
            $cards = $this->cards->getCardsInLocation('tableau', $player_id);
            foreach ($cards as $card) {
                $card_type_id = $card['type'];
                $card_type = $this->card_types[ $card_type_id ];

                if (in_array('chromosome', $card_type['category'])) {
                    $military_world_count ++;
                }
            }

            $force += ($military_world_count * $mil_per_chromosome);
        }
        if ($mil_per_imperium > 0) {
            // Should count imperium cards
            $military_world_count = 0;
            $cards = $this->cards->getCardsInLocation('tableau', $player_id);
            foreach ($cards as $card) {
                $card_type_id = $card['type'];
                $card_type = $this->card_types[ $card_type_id ];

                if (in_array('imperium', $card_type['category'])) {
                    $military_world_count ++;
                }
            }

            $force += ($military_world_count * $mil_per_imperium);
        }


        if ($bXeno) {    // Only against Xeno world
            if ($mil_per_civil > 0) {
                // Should count civil worlds
                $civil_world_count = 0;
                $cards = $this->cards->getCardsInLocation('tableau', $player_id);
                foreach ($cards as $card) {
                    $card_type_id = $card['type'];
                    $card_type = $this->card_types[ $card_type_id ];

                    if ($card_type['type'] == 'world'
                        && !in_array('military', $card_type['category'])
                        && $card_type_id != 1000) {
                        $civil_world_count ++;
                    }
                }

                $force += $civil_world_count;
            }
        }

        // When using Imperial Supply Convoy, add the specialized military for the first world up to its defense
        if ($previous_type !== null) {
            $force += min($previous_type['cost'], $mil_for_previous);
        }

        // Temporary force
        $sql = "SELECT player_tmp_milforce, player_tmp_gene_force, player_tmp_xenoforce FROM player WHERE player_id='$player_id' ";
        $tmpforce = self::getObjectFromDB($sql);
        $force += $tmpforce['player_tmp_milforce'];
        if ($this->getCardColorFromType($card_type) == 3) {
            $force += $tmpforce['player_tmp_gene_force'];
        }
        if ($bXeno) {
            $force += $tmpforce['player_tmp_xenoforce'];
        }

        // Space mercenaries ready to be used?
        // DEPRECATED : now space mercenaries are using player_tmp_milforce like New Military Tactics
        $bSpaceMercenaries = false;
        //$bSpaceMercenaries = self::getCollectionFromDB("SELECT card_id, card_type FROM card WHERE card_type IN ('106','153','157','164') AND card_location='tableau' AND card_location_arg='$player_id' AND card_status='-2'" , true);

        // Temporary force from phase
        $phase_choice = $this->getPhaseChoice(3);
        if (isset($phase_choice[ $player_id ]) && floor($phase_choice[ $player_id ] / 10) == self::getGameStateValue('repeatPhase') + 1) {
            $force += 2;
        }

        return array(
            'force' => $force,
            'contactspecialist' => $bContactSpecialist,
            'contactspecialist_discount' => ($contactspecialist_discount + $contactspecialist_discount_bonus),
            'mercenaries' => $bSpaceMercenaries
       );
    }

    function getSpecializedMilitary($init = false)
    {
        $players = self::loadPlayersBasicInfos();
        $current_wave = self::getGameStateValue('xeno_current_wave');

        if ($init) {
            $just_played_by_others = array();
        } else {
            $current_player_id = self::getCurrentPlayerId();
            $just_played_by_others = self::getObjectListFromDB("SELECT player_just_played FROM player WHERE player_id!=$current_player_id", true);
        }
        $res = self::getCollectionFromDB("SELECT player_id, player_milforce as base FROM player");

        foreach ($this->scanTableau(3) as $player_id => $powers) {
            foreach ($powers as $power) {
                if (in_array($power['card_id'], $just_played_by_others)) {
                    continue;
                }

                switch ($power['power']) {
                    case 'militaryforce':
                        if (isset($power['arg']['worldfilter']) && $power['arg']['worldfilter'] == 'rebel') {
                            if (! isset($res[$player_id]['rebel'])) {
                                $res[$player_id]['rebel'] = 0;
                            }
                            $res[$player_id]['rebel'] += $power['arg']['force'];
                        } elseif (isset($power['arg']['worldtype']) && count($power['arg']['worldtype'])==1) {
                            if (! isset($res[$player_id][ $power['arg']['worldtype'][0] ])) {
                                $res[$player_id][ $power['arg']['worldtype'][0] ] = 0;
                            }
                            $res[$player_id][ $power['arg']['worldtype'][0] ] += $power['arg']['force'];
                        } elseif (isset($power['arg']['xeno'])) {
                            if (! isset($res[$player_id]['xeno'])) {
                                $res[$player_id]['xeno'] = 0;
                            }
                            $res[$player_id]['xeno'] += $power['arg']['force'];
                        }
                        break;

                    // Anti-Xeno League
                    case 'militaryforce_percivil':
                        if (! isset($res[$player_id]['xeno'])) {
                            $res[$player_id]['xeno'] = 0;
                        }
                        $cards = $this->cards->getCardsInLocation('tableau', $player_id);
                        foreach ($cards as $card) {
                            $card_type_id = $card['type'];
                            $card_type = $this->card_types[ $card_type_id ];
                            if ($card_type['type'] == 'world'
                            && !in_array('military', $card_type['category'])
                            && $card_type_id != 1000) {
                                ++$res[$player_id]['xeno'];
                            }
                        }
                        break;

                    case 'militaryforcetmp':
                    case 'militaryforcetmp_discard':
                    case 'militaryforcetmp_prestige':
                    case 'good_for_military':
                        // Ignore temp Xeno and Xeno defense powers
                        if (isset($power['arg']['xeno']) || isset($power['arg']['xenodefense'])) {
                            break;
                        }

                        if (! isset($res[$player_id]['temp'])) {
                            $res[$player_id]['temp'] = 0;
                        }
                        $force = $power['arg']['force'];
                        if (isset($power['arg']['repeat'])) {
                            $force *= $power['arg']['repeat'];
                        }
                        $res[$player_id]['temp'] += $force;
                        break;
                }
            }
        }

        if ($init && self::getGameStateValue('presetHands')) {
            return $res;
        }

        // Xeno tie-break
        if (self::getGameStateValue('expansion') == 7 && !self::checkAction('initialdiscard', false)) {
            $players = $this->getPlayerAdmiralTrackMovingOrder();

            // We initialize the track with the current state
            $track = [];
            $sql = "SELECT player_id, player_xeno_milforce FROM player ORDER BY player_xeno_milforce, player_xeno_milforce_tiebreak";
            $current_track = self::getCollectionFromDB($sql, true);
            foreach ($current_track as $player_id => $xeno_milforce) {
                $track[$xeno_milforce][] = $player_id;
            }

            // We check how the track is going to evolve
            foreach ($players as $player) {
                $player_id = $player[ 'player_id' ];
                $current_force = $player['player_xeno_milforce'];

                // Compute Xeno force for this player
                $new_force = $res[$player_id]['base'];
                if (isset ($res[$player_id]['xeno'])) {
                    $new_force += $res[$player_id]['xeno'];
                }

                if ($new_force != $current_force || $current_wave <= 0) {
                    $key = array_search($player_id, $track[$current_force]);
                    unset($track[$current_force][$key]);
                    $track[$new_force][] = $player_id;
                }
            }

            // We go through the track and look for stacks. If we find any, add xeno_tiebreak to the results.
            foreach ($track as $space) {
                if (count($space) > 1) {
                    $i = 1;
                    foreach (array_reverse($space) as $player_id) {
                        $res[$player_id]['xeno_tiebreak'] = $i++;
                    }
                }
            }
        }

        return $res;
    }

    // Get all available goods (with type) for the given player
    // Result = <good_id> => (id, type)
    function getAllGoodsOfPlayer($player_id, $bExcludeCannotSell = false)
    {
        $result = array();
        $sql = "SELECT good.card_id id,good.card_status type FROM card good ";
        $sql .= "INNER JOIN card world ON world.card_id=good.card_location_arg ";
        $sql .= "WHERE good.card_location='good' ";
        $sql .= "AND world.card_location='tableau' ";   // .. world must be is in current player tableau
        $sql .= "AND world.card_location_arg='$player_id' ";
        if ($bExcludeCannotSell) {
            $sql .= " AND world.card_type NOT IN ('220', '246') ";
        }
        $dbres = self::DbQuery($sql);
        while ($row = mysql_fetch_assoc($dbres)) {
            $result[ $row['id'] ] = $row;
        }

        // Add good on artifacts with special id "artifact_<id>"
        $sql = "SELECT card_id
                FROM artefact
                WHERE card_type IN ('2','9')
                AND card_location='hand'
                AND card_location_arg='$player_id'
                ";
        $artefact_ress = self::getObjectListFromDB($sql, true);
        foreach ($artefact_ress as $artefact_id) {
            $result[ 'artefact_'.$artefact_id ] = array('id' => 'artefact_'.$artefact_id, 'type' => 4);
        }

        return $result;
    }

    // Put a good on this world if it is a windfall
    function windfallInitialProduction($card_id, $card_type, $defered = false)
    {
        $card = $this->cards->getCard($card_id);

        $player_id = $card['location_arg'];

        // If we have power "production_goodonsettle" somewhere, we should produce it anywhere
        $golden_age_terraforming = self::getUniqueValueFromDB("SELECT card_location_arg FROM card WHERE card_type='193' AND card_location='tableau' AND card_location_arg='$player_id'");

        if ($golden_age_terraforming!==null || in_array('windfall', $card_type['category'])) {
            $good_type = $this->getCardColorFromType($card_type);

            if ($good_type === null) {
                return ;
            }

            // Place a good on this card
            $good_card = $this->cards->pickCardForLocation($this->getDeck($card['location_arg']), 'good', $card_id);

            // Store good type in "card_status"
            $sql = "UPDATE card SET card_status='$good_type' ";
            $sql .= "WHERE card_id='".$good_card['id']."' ";
            self::DbQuery($sql);


            if ($defered) {
                $this->defered_notifyAllPlayers($this->notif_defered_id, 'goodproduction', '', array(
                        "world_id" => $card_id,
                        "good_type" => $good_type,
                        "good_id" => $good_card['id']
                   ));
            } else {
                self::notifyAllPlayers('goodproduction', '', array(
                        "world_id" => $card_id,
                        "good_type" => $good_type,
                        "good_id" => $good_card['id']
                   ));
            }
        }
    }

    // Return a list of usable produce powers in the form of possibilities
    function windfallPossibilities($powers)
    {
        $result = array();
        foreach ($powers as $power) {
            switch ($power['power']) {
                case 'windfallproduce':
                    if (count($power['arg']['worldtype']) == 4) {
                        $result[] = array('type' => 'all', 'reason' => $power['card_id']);    // Produce on any windfall
                    } else {
                        if (! isset($power['arg']['notthisworld'])) {
                            $result[] = array('type' => $power['arg']['worldtype'][0], 'reason' => $power['card_id']);
                        } else {
                            $result[] = array('type' => $power['arg']['worldtype'][0], 'reason' => $power['card_id'], 'notthisworld' => true);
                        }
                    }
                    break;

                case 'produceifdiscard':
                    $result[] = array('type' => 'produceifdiscard', 'reason' => $power['card_id']);    // Produce if discard a card
                    break;

                case 'windfallproduceifdiscard':
                    $result[] = array('type' => 'windfallproduceifdiscard', 'card' => $power['card_type'], 'reason' => $power['card_id'], 'world_type' => isset($power['arg']['world_type']) ? $power['arg']['world_type'] : null);    // Produce if discard a card
                    break;

                case 'repair':
                    $result[] = array('type' => 'repair', 'reason' => 'repair');
                    break;

                case 'drawforeachworld':
                    $result[] = array('type' => 'drawforeachworld', 'reason' => $power['card_id'], 'world_type' => $power['arg']['worldtype']);
                    break;

                case 'drawformilitary':
                    $result[] = array('type' => 'drawformilitary', 'reason' => $power['card_id']);
                    break;

                case 'drawforxenomilitary':
                    $result[] = array('type' => 'drawforxenomilitary', 'reason' => $power['card_id']);
                    break;

                case 'drawforeachgoodtype':
                    $result[] = array('type' => 'drawforeachgoodtype', 'reason' => $power['card_id']);
                    break;

                case 'drawforeach':
                    $result[] = array('type' => 'drawforeach', 'reason' => $power['card_id'], 'world_type' => $power['arg']['resource']);
                    break;

                case 'drawforeachtwo':
                    $result[] = array('type' => 'drawforeachtwo', 'reason' => $power['card_id']);
                    break;
            }
        }

        return $result;
    }

    // Return, for each players, a list of remaining windfallproduction
    function windfallproduction_state()
    {
        $result = array();
        $players = self::loadPlayersBasicInfos();
        $phase_choices = $this->getPhaseChoice(5);
        $player_powers = $this->scanTableau(5, null, null, true);
        foreach ($players as $player_id => $player) {
            $result[ $player_id ] = array();

            if (isset($phase_choices[ $player_id ])) {
                if ($phase_choices[ $player_id ] % 2 == 0) {     // Note: '1' means 'already used'
                    $result[ $player_id ][] = array('type' => 'all', 'reason' => 'phase');    // Phase bonus: produce on windfall
                }

                if ($phase_choices[ $player_id ] == 10) {     // Note: '11/12' means 'already used'
                    $result[ $player_id ][] = array('type' => 'all', 'reason' => 'phase');    // Phase bonus: produce on windfall
                    $result[ $player_id ][] = array('type' => 'all', 'reason' => 'phase');    // Phase bonus: produce on windfall
                } elseif ($phase_choices[ $player_id ] == 11) {     // Note: '11/12' means 'already used'
                   // One has been used already
                    $result[ $player_id ][] = array('type' => 'all', 'reason' => 'phase');    // Phase bonus: produce on windfall
                }
            }

            if (isset($player_powers[ $player_id ])) {
                $result[ $player_id ] = array_merge($result[$player_id], $this->windfallPossibilities($player_powers[ $player_id ]));
            }
        }

        $player_repair = self::getCollectionFromDB("SELECT player_id, player_tmp_gene_force FROM player WHERE player_tmp_gene_force>0");
        foreach ($player_repair as $player_id => $repair) {
            $result[ $player_id ][] = array('type' => 'repair', 'reason' => 'repair');
            if ($repair > 1) {
                $result[ $player_id ][] = array('type' => 'repair', 'reason' => 'repair');
            }
        }
        return $result;
    }

    // Return, for all players or for one player, the list of worlds/developments with consumption powers
    // that could be activated taking into account available goods and consumption powers already used.
    // Note: if a consumption power is "in used", the function returns corresponding card id if and only if
    //  consumption power can be use entirely again (it does not take into account resource already consumed nor previous repetitions)
    // When consume phase is initialized, we consider oort as possibly any kind to give the opportunity to the player
    // to change its kind to consume. After, we just look at it's actual kind.
    function getPossibleConsumptionCards($player_id_filter = null, $init_phase = false, $phase = 4)
    {
        $result = array();
        $players = self::loadPlayersBasicInfos();

        // Get all available goods (player_id => good_type => good number)

        $goods = array();
        foreach ($players as $player_id => $player) {
            $goods[ $player_id ] = array(1=>0,2=>0,3=>0,4=>0);
            $result[ $player_id ] = array("mand" => array(), "opt" => array());   // Mandatory / Optional
        }

        $sql = "SELECT good.card_id good_id, good.card_status good_type, world.card_location_arg good_player, world.card_id world_id, world.card_type world_type ";
        $sql .= "FROM card good ";
        $sql .= "INNER JOIN card world ON world.card_id=good.card_location_arg ";
        $sql .= "WHERE good.card_location='good' ";
        if ($player_id_filter != null) {
            $sql .= "AND world.card_location_arg='$player_id_filter' ";
        }
        $dbres = self::DbQuery($sql);
        $good_cards = array();
        $bOortGood = null;
        while ($row = mysql_fetch_assoc($dbres)) {
            $good_cards[] = $row;
            if ($init_phase && $row['world_type'] == 220) {
                $bOortGood = $row['good_player'];
            } else {
                $goods[ $row['good_player'] ][ $row['good_type'] ] ++;
            }
        }

        //

        $player_production = self::getDoubleKeyCollectionFromDB("SELECT pp_player_id, pp_good_id, '1' produced FROM player_production", true);

        // Already consumed (for "different" powers)
        $sql = "SELECT  player_id, player_consumed_types FROM player WHERE 1 ";
        $already_consumed = self::getCollectionFromDB($sql, true);

        foreach ($already_consumed as $player_id => $already) {
            if ($already == '') {
                $already_consumed[ $player_id ] = array();
            } else {
                $already_consumed[ $player_id ] = unserialize($already);
            }
        }

        // Adding artifact resources
        // Add good on artifacts with special id "artifact_<id>"
        $sql = "SELECT card_id, card_location_arg
                FROM artefact
                WHERE card_type IN ('2','9')
                AND card_location='hand'
                ";
        if ($player_id_filter != null) {
            $sql .= " AND card_location_arg='$player_id_filter' ";
        }

        $artefact_ress = self::getObjectListFromDB($sql);
        $number_of_artefact_goods = 0;
        foreach ($artefact_ress as $artefact) {
            $good_cards[] = array('good_id' => 'artefact_'.$artefact['card_id'], 'good_type' => 4, 'good_player' => $artefact['card_location_arg'], 'world_id' => 0);
            $goods[ $artefact['card_location_arg'] ][4]++;
            ++$number_of_artefact_goods;
        }


        // Get consumption powers
        $player_powers = $this->scanTableau($phase, $player_id_filter, null, true);
        if ($player_id_filter != null) {
            $player_powers = array($player_id_filter => $player_powers);
        }
        foreach ($player_powers as $player_id => $powers) {
            $total_goods_available = ($goods[ $player_id ][1]+$goods[ $player_id ][2]+$goods[ $player_id ][3]+$goods[ $player_id ][4]);
            if ($player_id == $bOortGood) {
                ++$total_goods_available;
            }

            foreach ($powers as $power) {
                $must_be_used = false;
                $could_be_used = false;
                $card = self::getObjectFromDB("SELECT card_type, card_status FROM card WHERE card_id=".$power['card_id']);

                if ($power['power'] == 'consume') {
                    if (isset($power['arg']['fromthisworld'])) {
                        // If there is a resource ON this world, it must be used
                        foreach ($good_cards as $good_card) {
                            if ($good_card['world_id'] == $power['card_id']) {
                                // There is a resource on this world => must use it
                                $must_be_used = true;
                            }
                        }
                    } else {
                        $inputfactor = isset($power['arg']['inputfactor']) ? $power['arg']['inputfactor'] : 1;
                        if (count($power['arg']['input']) == 4) {
                            if (! isset($power['arg']['different'])) {
                                // Any resource
                                if ($total_goods_available >= $inputfactor) {
                                    if ($total_goods_available - $number_of_artefact_goods < $inputfactor) {
                                        // It's not mandatory to use artefacts as goods
                                        $could_be_used = true;
                                    } else {
                                        // Ugly hack for Terraforming Colony.
                                        // Status 1 means good consume power used, but card consume still available, however, it's not mandatory.
                                        // I've put the hack the farthest I could to reduce the impact of the additional query.
                                        // This is ugly, but not much can be done about it as long as status are per card and not per power.
                                        if ($card['card_type'] != 253 || $card['card_status'] != 1) {
                                            $must_be_used = true;
                                        }
                                    }
                                }
                            } else {
                                // different resources must be available
                                $different_available = 0;
                                // Are all our alien goods artefact?
                                $alien_only_as_artefact = false;

                                if (! in_array(1, $already_consumed[ $player_id ])) {
                                    $different_available += ($goods[ $player_id ][1]>0 ? 1 : 0);
                                }
                                if (! in_array(2, $already_consumed[ $player_id ])) {
                                    $different_available += ($goods[ $player_id ][2]>0 ? 1 : 0);
                                }
                                if (! in_array(3, $already_consumed[ $player_id ])) {
                                    $different_available += ($goods[ $player_id ][3]>0 ? 1 : 0);
                                }
                                if (! in_array(4, $already_consumed[ $player_id ])) {
                                    $different_available += ($goods[ $player_id ][4]>0 ? 1 : 0);
                                    $alien_only_as_artefact = ($goods[ $player_id ][4] == $number_of_artefact_goods && $number_of_artefact_goods > 0);
                                }

                                if ($bOortGood == $player_id && $different_available < 4) {
                                    ++$different_available;
                                }

                                // If we have any good to consume and enough to feed the power (including what we've already consumed)
                                if ($different_available > 0 && $different_available + $card['card_status'] >= $inputfactor) {
                                    if ($alien_only_as_artefact && $different_available - 1 < $inputfactor) {
                                        // It's not mandatory to use artefacts as goods
                                        $could_be_used = true;
                                    } else {
                                        $must_be_used = true;
                                    }
                                }
                            }
                        } elseif (count($power['arg']['input']) == 2 && isset($power['arg']['different'])) {
                            // 2 different resources
                            $different_available = 0;
                            foreach ($power['arg']['input'] as $good_type) {
                                if ($good_type == '*') {
                                    if ($total_goods_available >= 2) {   // Note : if there are at least 2 goods, considering that the other one will be satisfied, there is enough goods!
                                        $different_available += 1;
                                    }
                                } else {
                                    if (! in_array($good_type, $already_consumed[ $player_id ])) {
                                        $different_available += ($goods[ $player_id ][ $good_type ]>0 ? 1 : 0);
                                    }
                                }

                                if ($different_available >= $inputfactor) {
                                    $must_be_used = true;
                                }
                            }
                        } else {
                            $good_type = $power['arg']['input'][0];

                            if ($good_type == 'pr') {
                                $could_be_used = true;
                            } else {
                                $number_of_goods = $goods[ $player_id ][ $good_type ];
                                if ($bOortGood == $player_id) {
                                    ++ $number_of_goods;
                                }

                                // Add goods already consumed
                                if ($number_of_goods > 0) {
                                    $number_of_goods += $card['card_status'];
                                }

                                if ($number_of_goods >= $inputfactor) {
                                    if ($number_of_goods - $number_of_artefact_goods < $inputfactor) {
                                        // It's not mandatory to use artefacts as goods
                                        $could_be_used = true;
                                    } else {
                                        $must_be_used = true;
                                    }
                                }
                            }
                        }
                    }
                } elseif ($power['power'] == 'consumeall' || $power['power'] == 'consumeforsell') {
                    // Any good is fine
                    if ($total_goods_available > 0) {
                        if ($total_goods_available == $number_of_artefact_goods) {
                            // It's not mandatory to use artefacts as goods
                            $could_be_used = true;
                        } else {
                            $must_be_used = true;
                        }
                    }
                } elseif ($power['power'] == 'consumecard') {
                    $could_be_used = true;
                }

                if ($must_be_used) {
                    $result[ $player_id ]['mand'][] = $power['card_id'];
                } elseif ($could_be_used) {
                    $result[ $player_id ]['opt'][] = $power['card_id'];
                }
            }
        }

        // In addition, gambling powers
        $player_with_casino = self::getCollectionFromDB("SELECT card_location_arg, card_id
                     FROM card
                     INNER JOIN player ON player_id=card_location_arg
                     WHERE card_type IN ('56','110','130')
                     AND card_location='tableau'
                     AND (card_id!=player_previously_played OR player_previously_played IS NULL)", true);
        foreach ($player_with_casino as $player_id => $card_id) {
            $result[ $player_id ]['opt'][] = $card_id;
        }

        return $result;
    }

    function cardsToSixDevelopmentsScore($cards, $dev_to_players, $player_infos, $oort_player = null, $oort_value = null)
    {
        $expansion = self::getGameStateValue('expansion');

        if ($oort_player !== null) {
            if ($oort_value === null) {
                // We call cardsToSixDevelopmentsScore FOUR times with all possible values, and get the best configuration for Oort
                $good_to_result = array(
                    1 => $this->cardsToSixDevelopmentsScore($cards, $dev_to_players, $player_infos, $oort_player, 1),
                    2 => $this->cardsToSixDevelopmentsScore($cards, $dev_to_players, $player_infos, $oort_player, 2),
                    3 => $this->cardsToSixDevelopmentsScore($cards, $dev_to_players, $player_infos, $oort_player, 3),
                    4 => $this->cardsToSixDevelopmentsScore($cards, $dev_to_players, $player_infos, $oort_player, 4)
               );


                $max_for_player = -100;
                $max_result = null;
                $best_good_id = null;
                foreach ($good_to_result as $good_id => $result) {
                    $score = 0;
                    foreach ($dev_to_players as $dev_id => $player_id) {
                        if ($player_id == $oort_player) {
                            if (isset($result[ $dev_id ])) {
                                $score += $result[ $dev_id ];
                            }
                        }
                    }

                    if ($score > $max_for_player) {
                        $max_for_player = $score;
                        $max_result = $result;
                        $best_good_id = $good_id;
                    }
                }

                $this->changeOortType($best_good_id);

                return $max_result;
            }
        }

        // Init
        $dev_to_points = array();
        foreach ($this->six_cost_developments as $dev_id) {
            $dev_to_points[ $dev_id ] = 0;
        }


        $player_to_worldtypes = array();

        // Perform the scan
        foreach ($cards as $card) {
            // Damaged worlds don't count
            if ($card['type'] == 1000) {
                continue;
            }
            $player_id = $card['location_arg'];

            if (! isset($player_to_worldtypes[ $player_id ])) {
                $player_to_worldtypes[ $player_id ] = array();
            }

            $card_type = $this->card_types[ $card['type'] ];

            if ($card['type'] == 220) {
                $card_type['windfalltype'] = $oort_value;
            }

            $dev_to_points_this_card = array();
            foreach ($this->six_cost_developments as $dev_id) {
                $dev_to_points_this_card[ $dev_id ] = 0;
            }

            $bWindfall = in_array('windfall', $card_type['category']);
            $bMilitary = in_array('military', $card_type['category']);
            $bAlien = in_array('alien', $card_type['category']);
            $bRebel = in_array('rebel', $card_type['category']);
            $bTerraforming = in_array('terraforming', $card_type['category']);
            $bImperium = in_array('imperium', $card_type['category']);
            $bUplift = in_array('uplift', $card_type['category']);
            $bChromosome = in_array('chromosome', $card_type['category']);
            $bXeno = in_array('xeno', $card_type['category']);
            $bAntiXeno = in_array('antixeno', $card_type['category']);
            $bWorld = ($card_type['type'] == 'world');
            $bTradePower = false;

            // Powers
            $card_color = null;
            foreach ($card_type['powers'] as $phase_id => $powers) {
                foreach ($powers as $power) {
                    if ($power['power'] == 'produce' || $power['power'] == 'produceifdiscard') {
                        if ($card_type['type'] == 'world' && $power['arg']['resource'] == 4) {
                            $dev_to_points_this_card[21] += ($bAlien ? 1 : 3);    // Alien tech institute (alien produce) (note: alien = 2 points anyway)
                        }
                        if ($card_type['type'] == 'world' && $power['arg']['resource'] == 1) {
                            $dev_to_points_this_card[22] += 2;    // Free trade association (blue produce)
                            $dev_to_points_this_card[197] += 1;    // Pan-Galactic Hologrid (blue produce)
                        }
                        if ($card_type['type'] == 'world' && $power['arg']['resource'] == 2) {
                            $dev_to_points_this_card[11] += 2;    // Mining league (brown produce)
                            $dev_to_points_this_card[146] += 2;    // Prospecting Guild (brown produce)
                        }
                        if ($card_type['type'] == 'world' && $power['arg']['resource'] == 3) {
                            $dev_to_points_this_card[30] += 2;    // Pan galactic league (green produce)
                        }
                        if ($card_type['type'] == 'world' && $power['arg']['resource'] == 3) {
                            $dev_to_points_this_card[100] += 2;    // Galactic Genome Project (green produce)
                        }

                        if ($card_type['type'] == 'world') {
                            $dev_to_points_this_card[27] += 2;    // Merchant guild
                            $dev_to_points_this_card[193] += 1;    // Golden Age of Terraforming
                            $dev_to_points_this_card[264] += 1;    // Galactic Expansionists
                            $dev_to_points_this_card[313] += 1;    // terraforming defenders
                        }

                        if ($card_type['type'] == 'world' && $power['arg']['resource'] != 4 && !$bAlien) {
                            $dev_to_points_this_card[201] += 1;    // Alien Cornucopia
                        }

                        $player_to_worldtypes[ $player_id ][ $power['arg']['resource'] ] = true;
                        $card_color = $power['arg']['resource'];
                    }
                }

                if ($phase_id == 1 && ($card['type'] != 256 || $expansion == 5)) {
                    $dev_to_points_this_card[26] += 1; // Galactic survey: SETI   (Note: Worlds with explore power gets in fact +2 because all worlds gets +1)
                }
                if ($card_type['type'] == 'development' && $phase_id == 4) {
                    $dev_to_points_this_card[28] += 2; // New economy
                }
                if ($card_type['type'] == 'world' && $phase_id == 4) {
                    $dev_to_points_this_card[28] += 1; // New economy
                }
                if ($card_type['type'] == 'development' && $phase_id == 's') {
                    $dev_to_points_this_card[31] += 2; // Trade league
                }
                if ($card_type['type'] == 'world' && $phase_id == 's') {
                    $dev_to_points_this_card[31] += 1; // Trade league
                }

                if ($phase_id == 's') {
                    $bTradePower = true;
                }
            }

            // Windfalls
            if ($bWindfall) {
                $windfalltype = $card_type['windfalltype'];

                if ($windfalltype == 4) {
                    $dev_to_points_this_card[21] += ($bAlien ? 0 : 2);    // Alien tech institute (alien windfall) (note: alien = 2 points anyway)
                }
                if ($windfalltype == 1) {
                    $dev_to_points_this_card[22] += 1;    // Free trade association (blue windfall)
                    $dev_to_points_this_card[197] += 1;    // Pan-Galactic Hologrid (blue windfall)
                }
                if ($windfalltype == 2) {
                    $dev_to_points_this_card[11] += 1;    // Mining league (brown windfall)
                    $dev_to_points_this_card[146] += 2;    // Prospecting Guild  (brown windfall)
                }
                if ($windfalltype == 3) {
                    $dev_to_points_this_card[30] += 2;    // Pan galactic league (green windfall)
                }
                if ($windfalltype == 3) {
                    $dev_to_points_this_card[100] += 2;    // Galactic Genome Project (green windfall)
                }

                $dev_to_points_this_card[101] += 2;    // Terraforming Guild (any windfall)

                $player_to_worldtypes[ $player_id ][ $windfalltype ] = true;
                $card_color = $windfalltype;
            }

            // Worlds
            if ($card_type['type']=='world') {
                $dev_to_points_this_card[26] += 1; // Galactic survey: SETI

                $dev_to_points_this_card[197] += 1; // Pan-Galactic Hologrid

                if ($dev_to_points_this_card[146] == 0) { // Prospecting Guild (Other world)
                    $dev_to_points_this_card[146] += 1;
                }
            }


            // Special labels
            if ($bAlien) {
                $dev_to_points_this_card[21] += 2;    // Alien tech institute (alien label)
                $dev_to_points_this_card[201] += 2;    // Alien Cornucopia (alien label)
            }

            if ($bTerraforming) {
                $dev_to_points_this_card[101] += 2;    // Terraforming Guild  (terraforming label)
                if ($dev_to_points_this_card[146] == 0) {
                    $dev_to_points_this_card[146] += 1;    // Prospecting Guild  (terraforming label)
                }
                $dev_to_points_this_card[193] += 2;    // Golden Age of Terraforming  (terraforming label)
                $dev_to_points_this_card[261] += 2;    // Terraforming Unlimited
            }

            if ($bImperium) {
                $dev_to_points_this_card[119] += 2;    // Imperium Lords
                $dev_to_points_this_card[147] += 2;    // Imperium Seat
                $dev_to_points_this_card[263] += 2;    // Imperium War Faction
            }

            if ($bUplift) {
                $dev_to_points_this_card[247] += 2;    // Uplift Chamber
                $dev_to_points_this_card[262] += 2;    // Uplift Alliance
            } elseif ($card_type['type'] == 'world' && $card_color == 3) {
                $dev_to_points_this_card[262] += 2;    // Uplift Alliance
            }

            if ($bChromosome) {
                $dev_to_points_this_card[152] += 3;    // Uplift Code
            } elseif ($bUplift) {
                $dev_to_points_this_card[152] += 2;    // Uplift Code
            }

            if ($bUplift && $bWorld && $bChromosome) {
                $dev_to_points_this_card[283] += 1;    // Corrosive Uplift World
                $dev_to_points_this_card[294] += 1;    // Uplift Coalition
            }

            if (!$bMilitary && $bTradePower && $card_type['type'] == 'world') {
                $dev_to_points_this_card[265] += 2;    // Wormhole prospectors
            } elseif (! $bMilitary && $card_type['type'] == 'world') {
                $dev_to_points_this_card[265] += 1;    // Wormhole prospectors
            } elseif ($bTradePower) {
                $dev_to_points_this_card[265] += 1;    // Wormhole prospectors
            }


            // 6 cost devs
            if ($card_type['type']=='development' && $card_type['cost']==6) {
                $dev_to_points_this_card[23] += 2;    // Galactic federation

                if ($dev_to_points_this_card[193] == 0) {
                    $dev_to_points_this_card[193] += 1;    //Golden Age of Terraforming
                }
            } elseif ($card_type['type']=='development' && $card_type['cost']!=6) {
                $dev_to_points_this_card[23] += 1;    // Galactic federation
            }

            if ($card_type['type']=='development') {
                $dev_to_points_this_card[264] += 1;    // Galactic Expansionists
            }

            // Military
            if ($bMilitary && $bRebel) {
                $dev_to_points_this_card[24] += 2;    // Galactic imperium
                $dev_to_points_this_card[147] += 2;    // Imperium Seat
                $dev_to_points_this_card[309] += 2;     // anti xeno rebel force
            } elseif ($bMilitary) {
                $dev_to_points_this_card[24] += 1;    // Galactic imperium

                if ($bXeno) {
                    $dev_to_points_this_card[309] += 1;     // anti xeno rebel force
                }
            }

            if ($bRebel) {
                $dev_to_points_this_card[149] += 2;    // Rebel Alliance
                $dev_to_points_this_card[267] += 2;    // Rebel Resistance
            } elseif ($bMilitary) {
                $dev_to_points_this_card[149] += 1;    // Rebel Alliance
            }

            if ($bMilitary) {
                if ($dev_to_points_this_card[30] == 0) { // Pan galactic league (Other military world)
                    $dev_to_points_this_card[30] += 1;
                }

                if ($dev_to_points_this_card[119] == 0) { // Imperium Lords
                    $dev_to_points_this_card[119] += 1;
                }
                $dev_to_points_this_card[192] += 1;   // Universal Peace Institute
            }

            // Specific cards
            if ($card_type['name'] == "Consumer Markets") {
                $dev_to_points_this_card[22] += 2;    // Free trade association
            }
            if ($card_type['name'] == "Expanding Colony") {
                $dev_to_points_this_card[22] += 2;    // Free trade association
            }
            if ($card_type['name'] == "Research Labs") {
                $dev_to_points_this_card[25] += 3;    // Galactic renaissance
            }
            if ($card_type['name'] == "Galactic Trendsetters") {
                $dev_to_points_this_card[25] += 3;    // Galactic renaissance
            }
            if ($card_type['name'] == "Artist Colony") {
                $dev_to_points_this_card[25] += 3;    // Galactic renaissance
            }
            if ($card_type['name'] == "Mining Robots") {
                $dev_to_points_this_card[11] += 2;    // Mining league
            }
            if ($card_type['name'] == "Mining Conglomerate") {
                $dev_to_points_this_card[11] += 2;    // Mining league
            }
            if ($card_type['name'] == "Contact Specialist") {
                $dev_to_points_this_card[30] += 3;    // Pan galactic league
            }
            if ($card_type['name'] == "Genetics Lab") {
                $dev_to_points_this_card[100] += 3;    // Galactic Genome Project
            }
            if ($card_type['name'] == "Diversified Economy") {
                $dev_to_points_this_card[148] += 3;    // Galactic Exchange
            }

            if ($card_type['name'] == "Interstellar Bank" || $card_type['name'] == "Investment Credits" || $card_type['name'] == "Gambling World") {
                $dev_to_points_this_card[150] += 2;   // Galactic Banker
            } elseif ($card_type['type'] == 'development') {
                $dev_to_points_this_card[150] += 1;   // Galactic Banker ("other development")
            }

            if ($card_type['name'] == "Pan-Galactic Mediator") {
                $dev_to_points_this_card[192] += 2;
            }
            if ($card_type['name'] == "Expanding Colony") {
                $dev_to_points_this_card[197] += 1;  // Note : already counting as a "world"
            }
            if ($card_type['name'] == "Export Duties") {
                $dev_to_points_this_card[199] += 2;  // Pan-Galactic Affluence
            }
            if ($card_type['name'] == "Galactic Renaissance") {
                $dev_to_points_this_card[199] += 2;   // Pan-Galactic Affluence
            }
            if ($card_type['name'] == "Terraformed World") {
                $dev_to_points_this_card[199] += 2;   // Pan-Galactic Affluence
            }
            if ($card_type['name'] == "Alien Rosetta Stone World") {
                $dev_to_points_this_card[260] += 3;   // Alien researchers
            } elseif ($bAlien) {
                $dev_to_points_this_card[260] += 1;   // Alien researchers
            }

            if ($card_type['name'] == "Terraformed World") {
                $dev_to_points_this_card[261] += 3;    // Terraforming Unlimited
            } elseif (! $bMilitary && $card_type['type']=='world' && !$bTerraforming) {
                $dev_to_points_this_card[261] += 1;    // Terraforming Unlimited
            }

            if ($card_type['name'] == "Blaster Gem Mine") {
                $dev_to_points_this_card[263] += 3;    // Imperium War Faction
            } elseif ($bMilitary && $card_type['type']=='world'
                     && $dev_to_points_this_card[263] == 0) { // Don't count twice for Imperium Military worlds
                $dev_to_points_this_card[263] += 1;    // Imperium War Faction
            }

            if ($card_type['name'] == "Blaster Gem Mine" || $card_type['name'] == "Imperium Armaments World") {
                $dev_to_points_this_card[310] += 3;    // imperium war profiteers
            } elseif ($bImperium) {
                $dev_to_points_this_card[310] += 2;    // imperium war profiteers
            } elseif ($bWorld && $card_color == 2) {
                $dev_to_points_this_card[310] += 1;    // imperium war profiteers
            }

            if ($bWorld && $bMilitary && $bXeno) {
                $dev_to_points_this_card[311] += 2;   //  alien historians
            } elseif ($card_type['name'] == "Alien Archives") {
                $dev_to_points_this_card[311] += 3;   //  alien historians
            } elseif ($bAlien) {
                $dev_to_points_this_card[311] += 1;   //  alien historians
            }

            if ($bUplift) {
                $dev_to_points_this_card[312] += 2;   //  uplift bio engineers
            } elseif ($bWorld && $card_color==3) {
                $dev_to_points_this_card[312] += 1;   //  uplift bio engineers
            }

            if ($card_type['name'] == "Terraformed World") {
                $dev_to_points_this_card[313] += 3;    //  terraforming defenders
            } elseif ($bTerraforming) {
                $dev_to_points_this_card[313] += 2;   //  terraforming defenders
            }

            if ($card_type['type'] == 'world') {
                if ($bAntiXeno) {
                    $dev_to_points_this_card[308] += 2;     // anti xeno league
                } else {
                    $dev_to_points_this_card[308] += 1;     // anti xeno league
                }
            } elseif ($card_type['type'] == 'development' && $bAntiXeno) {
                $dev_to_points_this_card[308] += 1;     // anti xeno league
            }

            if ($bAntiXeno) {
                $dev_to_points_this_card[309] += 1;     // anti xeno rebel force
            }


            // Player own all these points only if he own the corresponding development
            foreach ($dev_to_points_this_card as $dev_id => $points) {
                if (isset($dev_to_players[ $dev_id ]) && $dev_to_players[ $dev_id ] == $player_id) {
                    $dev_to_points[ $dev_id ] += $points;
                }
            }
        }

        if (isset($dev_to_players[ 25 ])) {
            // Galactic renaissance (1 pt per 3 vp chips)
            $dev_to_points[25] += floor($player_infos[ $dev_to_players[ 25 ] ][ 'player_vp' ] / 3);
        }
        if (isset($dev_to_players[ 27 ])) {
            // 2nd edition Merchant Guild (1pt per good)
            $sql = "SELECT count(*) FROM card good ";
            $sql .= "JOIN card world ON world.card_id=good.card_location_arg ";
            $sql .= "WHERE good.card_location='good' AND world.card_location_arg=" . $dev_to_players[ 27 ];
            $num_goods = self::getUniqueValueFromDB($sql);
            $dev_to_points[27] += $num_goods;
        }
        if (isset($dev_to_players[ 29 ])) {
            // New galatic order (military force)
            $dev_to_points[29] += $player_infos[ $dev_to_players[ 29 ] ][ 'player_milforce' ];
        }
        if (isset($dev_to_players[ 192 ])) {
            // Peace Institute (-military force)
            $dev_to_points[192] -= min(0, $player_infos[ $dev_to_players[ 192 ] ][ 'player_milforce' ]);
        }
        if (isset($dev_to_players[ 199 ])) {
            // Pan-Galactic Affluence (+prestige)
            $dev_to_points[199] += $player_infos[ $dev_to_players[ 199 ] ][ 'player_prestige' ];
        }
        if (isset($dev_to_players[ 148 ])) {
            // Points depending of number of different worlds
            $different_worlds = count($player_to_worldtypes[ $dev_to_players[ 148 ] ]);

            if ($different_worlds == 1) {
                $dev_to_points[ 148 ] += 1;
            } elseif ($different_worlds == 2) {
                $dev_to_points[ 148 ] += 3;
            } elseif ($different_worlds == 3) {
                $dev_to_points[ 148 ] += 6;
            } elseif ($different_worlds == 4) {
                $dev_to_points[ 148 ] += 10;
            }
        }

        if ($expansion == 5) {
            // Score artifacts

            // Alien Researchers
            if (isset( $dev_to_players[ 260 ] )) {
                $all_artefacts = $player_infos[ $dev_to_players[ 260 ] ]['artefacts'];
                foreach ($all_artefacts as $artefact) {
                    if ($artefact['type']==2 || $artefact['type']==9) {
                        $dev_to_points[260] ++;
                    }
                }
            }
            // Terraforming unlimited
            if (isset( $dev_to_players[ 261 ] )) {
                $all_artefacts = $player_infos[ $dev_to_players[ 261 ] ]['artefacts'];
                foreach ($all_artefacts as $artefact) {
                    if ($this->artefact_types[$artefact['type']]['alienscience']) {
                        $dev_to_points[261] ++;
                    }
                }
            }

            // Uplift alliance
            if (isset( $dev_to_players[ 262 ] )) {
                $all_artefacts = $player_infos[ $dev_to_players[ 262 ] ]['artefacts'];
                foreach ($all_artefacts as $artefact) {
                    if ($artefact['type']==4 || $artefact['type']==14) {
                        $dev_to_points[262] ++;
                    }
                }
            }
        }

        return $dev_to_points;
    }

    // Gain points for 6-cost developments of each players
    function getSixDevelopmentsPoints()
    {
        // Scan all cards in tableau
        $cards = $this->cards->getCardsInLocation('tableau');

        $dev_to_players = array();

        // Scan cards to find 6-cost developments
        $oort_player = null;
        foreach ($cards as $card) {
            $player_id = $card['location_arg'];
            $card_type = $this->card_types[ $card['type'] ];
            if ($card_type['type']=='development' && $card_type['cost']==6
                && $card['type']!= 151) { // Pan-Galactic Research doesn't have variable cost
                $dev_to_players[ $card['type'] ] = $player_id;
            }
            if ($card['type'] == 267 || $card['type'] == 247 || $card['type'] == 283 || $card['type'] == 294) {
                $dev_to_players[ $card['type'] ] = $player_id;  // Non 6 dev that brings points
            }

            if ($card['type'] == 220) {
                $oort_player = $player_id;
            }
        }

        // Additional infos needed
        $expansion = self::getGameStateValue('expansion');
        $player_infos = array();
        $sql = "SELECT player_id, player_vp, player_milforce, player_prestige FROM player ";
        $dbres = self::DbQuery($sql);
        while ($row = mysql_fetch_assoc($dbres)) {
            $player_infos[ $row['player_id'] ] = $row;

            if ($expansion == 5) {
                $player_infos[ $row['player_id'] ]['artefacts'] = $this->artefacts->getCardsInLocation('tableau', $row['player_id']);
            }
        }


        $dev_to_points = $this->cardsToSixDevelopmentsScore($cards, $dev_to_players, $player_infos, $oort_player );
        return array(
            "devpoints" => $dev_to_points,
            "devplayers" => $dev_to_players
       );
    }

    // Compute scores from 6 cost devs and give them to players
    function scoreSixDevelopments()
    {
        $players = self::loadPlayersBasicInfos();
        $sixdevpoints = $this->getSixDevelopmentsPoints();
        foreach ($sixdevpoints['devplayers'] as $dev_id => $player_id) {
            $points = $sixdevpoints['devpoints'][$dev_id];

            $pscore = $this->updatePlayerScore($player_id, $points, false);

            self::notifyAllPlayers('updateScore', clienttranslate('${player_name} gains ${points_nbr} with ${dev_name}'),
                                            array(
                                                "i18n" => array("dev_name"),
                                                "player_id" => $player_id,
                                                "score_delta" => $points,
                                                "vp_delta" => 0,
                                                "score" => $pscore['score'],
                                                "vp" => $pscore['vp'],
                                                "player_name" => $players[$player_id]['player_name'] ,
                                                "points_nbr" => $points,
                                                "dev_name" => $this->card_types[ $dev_id ]['name']
                                           ) );


            $card_type = $this->card_types[ $dev_id ];
            if ($card_type['type'] == 'world') {
                self::incStat($points, 'tableau_points', $player_id);
            } else {
                self::incStat($points, 'sixcostdev_points', $player_id);
            }
        }

        // Score also Federation Capital
        $player_capital = self::getObjectFromDB("SELECT player_id,player_prestige
                    FROM player
                    INNER JOIN card ON card_location_arg=player_id
                    WHERE card_type='187' and card_location='tableau'
                    ", true);
        if ($player_capital !== null) {
            $pscore = $this->updatePlayerScore($player_capital['player_id'], $player_capital['player_prestige'], false);

            self::notifyAllPlayers('updateScore', clienttranslate('${player_name} gains ${points_nbr} with ${dev_name}'),
                                            array(
                                                "i18n" => array("dev_name"),
                                                "player_id" => $player_capital['player_id'],
                                                "score_delta" => $player_capital['player_prestige'],
                                                "vp_delta" => 0,
                                                "score" => $pscore['score'],
                                                "vp" => $pscore['vp'],
                                                "player_name" => $players[ $player_capital['player_id'] ]['player_name'] ,
                                                "points_nbr" => $player_capital['player_prestige'],
                                                "dev_name" => $this->card_types[ 187 ]['name']
                                           ) );
                                           
            self::incStat($player_capital['player_prestige'], 'tableau_points', $player_capital['player_id']);
        }
    }

    function updatePlayerScore($player_id, $score_delta, $bIsVpChip, $bIsDefenseAward = False)
    {
        if ($bIsVpChip) {
            $sql = "UPDATE player SET player_score=player_score+$score_delta, player_vp=player_vp+$score_delta WHERE player_id=$player_id";
        } else if ($bIsDefenseAward) {
            $sql = "UPDATE player SET player_score=player_score+$score_delta, player_defense_award=player_defense_award+$score_delta WHERE player_id=$player_id";
        } else {
            $sql = "UPDATE player SET player_score=player_score+$score_delta WHERE player_id=$player_id";
        }
        self::DbQuery($sql);

        return self::getObjectFromDB("SELECT player_score score, player_vp vp, player_defense_award defense_award FROM player WHERE player_id=$player_id");
    }

    function givePrestige($player_id, $nbr, $bDefered = false, $reason_id = null)
    {
        if (self::getGameStateValue('expansion') != 4) {
            return ; // prestige is only used with Brink of War
        }

        $current = self::getUniqueValueFromDB("SELECT player_prestige FROM player WHERE player_id='$player_id'");
        $original = $current;

        if ($current+$nbr < 0) {
            throw new feException(self::_("You don't have enough Prestige to do this action"), true);
        }

        $current += $nbr;

        self::DbQuery("UPDATE player SET player_prestige='$current' WHERE player_id='$player_id'");
        $pscore = $this->updatePlayerScore($player_id, $nbr, false);

        try {
            self::incStat($nbr, 'prestige_points', $player_id);
        } catch (Exception $e) {
            // Don't crash if the prestige_points table hasn't been initialized.
            // This can be removed once all the turn by turn games
            // started before the prestige_points stat fixed are finished.
        }

        $players = self::loadPlayersBasicInfos();

        $args = array(
                                        "player_id" => $player_id,
                                        "player_name" => $players[ $player_id ]['player_name'],
                                        "nbr" => $nbr,
                                        "prestige" => $current,
                                        "nbrpos" => abs($nbr)
                                   );


        if ($reason_id !== null) {
            $log = clienttranslate('${card_name} : ${player_name} gets ${nbr} Prestige');
            if ($nbr < 0) {
                $log = clienttranslate('${card_name} : ${player_name} consumes ${nbrpos} Prestige');
            }

            $args['i18n'] = array('card_name');
            if (is_numeric($reason_id)) {
                $args['card_name'] = $this->card_types[ $reason_id ]['name'];
            } else {
                $args['card_name'] = $reason_id;
            }
        } else {
            $log = clienttranslate('${player_name} gets ${nbr} Prestige');
            if ($nbr < 0) {
                $log = clienttranslate('${player_name} consumes ${nbrpos} Prestige');
            }
        }

        $prestige_on_leadertile = self::getGameStateValue('prestigeOnLeaderTile');
        if (self::getGameStateValue('prestigeLeader') == $player_id) {
            if ($nbr > 0) {
                // Prestige you get goes on tile
                $prestige_on_leadertile = self::incGameStateValue('prestigeOnLeaderTile', $nbr);
            } else {
                // Some prestige may be taken from the tile
                $original_out_of_tile = $original - $prestige_on_leadertile;

                if ($original_out_of_tile + $nbr < 0) {
                    $taken_from_tile = abs($original_out_of_tile + $nbr);
                    $prestige_on_leadertile = self::incGameStateValue('prestigeOnLeaderTile', -$taken_from_tile);
                }
            }
        }
        $args['leadertile'] = $prestige_on_leadertile;


        if ($bDefered) {
            $this->defered_notifyAllPlayers($this->notif_defered_id, 'updatePrestige', $log,
                                            $args);

            $this->defered_notifyAllPlayers($this->notif_defered_id, 'updateScore', '',
                                            array(
                                                "player_id" => $player_id,
                                                "vp_delta" => 0,
                                                "score" => $pscore['score'],
                                                "vp" => $pscore['vp']
                                           ) );
        } else {
            self::notifyAllPlayers('updatePrestige', $log, $args);

            self::notifyAllPlayers('updateScore', '',
                                            array(
                                                "player_id" => $player_id,
                                                "vp_delta" => 0,
                                                "score" => $pscore['score'],
                                                "vp" => $pscore['vp']
                                           ) );

            // Rule: If an expenditure results in the Prestige Leader losing sole prestige lead,
            // the player immediately returns the tile to the center (or to a new Prestige Leader).
            if ($nbr < 0 && self::getGameStateValue('prestigeLeader') == $player_id) {
                $this->checkGoals('prestige_spent');
            }
        }
    }

    // Draw cards for specified player
    // Defer notification if specified
    function drawCardForPlayer($player_id, $card_drawn_nbr, $bDefered = false, $reason_id = null)
    {
        if ($card_drawn_nbr > 0) {
            $cardDrawn = $this->cards->pickCards($card_drawn_nbr, $this->getDeck($player_id), $player_id);
            self::notifyPlayer($player_id, 'drawCards', '', $cardDrawn);
            $this->notifyUpdateCardCount();

            self::incStat($card_drawn_nbr, 'cards_drawn', $player_id);

            $players = self::loadPlayersBasicInfos();

            $log = clienttranslate('${player_name} draw ${card_nbr} card(s)');
            $args = array(
                                            "player_name" => $players[ $player_id ]['player_name'],
                                            "card_nbr" => $card_drawn_nbr
                                       );

            if ($reason_id !== null) {
                if ($reason_id == 'prestigeleader') {
                    $log = clienttranslate('Prestige leader : ${player_name} has some prestige on Prestige leader tile and draw ${card_nbr} card(s)');
                } else {
                    $log = clienttranslate('${card_name} : ${player_name} draw ${card_nbr} card(s)');
                    $args['i18n'] = array('card_name');
                    $args['card_name'] = $this->card_types[ $reason_id ]['name'];
                }
            }

            if ($bDefered) {
                $this->defered_notifyAllPlayers($this->notif_defered_id, 'drawCards_def', $log, $args);
            } else {
                self::notifyAllPlayers('drawCards_def', $log, $args);
            }

            return $cardDrawn;
        } else {
            return array();
        }
    }

    // Activate "draw" powers linked to this phase
    function drawOnPhase($phase_id)
    {
        $expansion = self::getGameStateValue('expansion');

        $tableaux = $this->scanTableau($phase_id);
        $players = self::loadPlayersBasicInfos();
        $draw_for_each_genes_world = null;
        $draw_for_each_world_rare = null;
        $draw_for_military_world = null;
        $draw_for_military_rebel_world = null;
        $draw_for_chromosome = null;
        $draw_for_rebel = null;
        $draw_for_imperium = null;
        $draw_for_military_xeno = null;
        $draw_for_development = array();
        $damaged_worlds = array();

        // With Xeno Invasion, during produce, we check the damaged worlds of players with draw powers
        // they might want to repair them before using the power so that they draw more
        if ($phase_id == 5 && $expansion == 7) {
            $damaged_worlds = self::getDoubleKeyCollectionFromDB("
                SELECT card_location_arg, card_damaged, 1
                FROM card
                WHERE card_damaged!=0 AND card_location='tableau'");

            foreach ($damaged_worlds as $player_id => $types) {
                foreach (array_keys($types) as $type) {
                    $damaged_worlds[$player_id][$type] = $this->card_types[ $type ];
                }
            }
        }

        foreach ($tableaux as $player_id => $powers) {
            foreach ($powers as $power) {
                $card_id = $power['card_id'];
                if ($power['power'] == 'draw') {
                    $this->drawCardForPlayer($player_id, $power['arg']['card'], false, $power['card_type']);
                }
                if ($power['power'] == 'drawforeachworld') {
                    if ($power['arg']['worldtype'] == 3) { // draw for each Genes world in tableau (Pan-Galactic League)
                        $draw_for_each_genes_world = array('player' => $player_id, 'card' => $card_id);
                    } elseif ($power['arg']['worldtype'] == 2) { // Draw for each Rare element world in tableau (Imperium War Profiteers)
                        $draw_for_each_world_rare = array('player' => $player_id, 'card' => $card_id);
                    }
                }
                if ($power['power'] == 'drawformilitary') {
                    if (isset($power['filter']) && $power['filter'] == 'rebel') {
                        $draw_for_military_rebel_world = $player_id;
                    } else {
                        $draw_for_military_world = array('player' => $player_id, 'card' => $card_id);
                    }
                }
                if ($power['power'] == 'drawforchromosome') {
                    $draw_for_chromosome = $player_id;
                }
                if ($power['power'] == 'drawforrebel') {
                    $draw_for_rebel = $player_id;
                }
                if ($power['power'] == 'drawforrebelmilitary') {
                    $draw_for_military_rebel_world = $player_id;
                }
                if ($power['power'] == 'drawforimperium') {
                    $draw_for_imperium = $player_id;
                }
                if ($power['power'] == 'drawforxenomilitary') {
                    $draw_for_military_xeno = array('player' => $player_id, 'card' => $card_id);
                }
                if ($power['power'] == 'drawfordevelopment') {
                    $draw_for_development[] = $player_id;
                }

                if ($power['power'] == 'vpchip') {
                    // Special : get a VP chip instead of a card

                    $vpMultiplicator = 1;
                    $phaseChoice = $this->getPhaseChoice(4);
                    if (isset($phaseChoice[ $player_id ])) {
                        if ($phaseChoice[ $player_id ] == 1 || $phaseChoice[ $player_id ] == 2 || $phaseChoice[ $player_id ] == 10) {  // x2 or "sell + x2" or "sell + bonus"
                            $vpMultiplicator = 2;
                        } elseif ($phaseChoice[ $player_id ] == 11 || $phaseChoice[ $player_id ] == 12) {  // x2 + bonus or "sell + x2 + bonus"
                            $vpMultiplicator = 3;
                        }
                    }

                    // Win some vp(s)
                    $vp_wins = $power['vp'] * $vpMultiplicator;

                    $pscore = $this->updatePlayerScore($player_id, $vp_wins, true);

                    self::incGameStateValue('remainingVp', -$vp_wins);

                    self::notifyAllPlayers('updateScore', clienttranslate('${card_name} : ${player_name} scores ${score_delta} points'),
                                            array(
                                                "i18n" => ['card_name'],
                                                "card_name" => $this->card_types[ $power['card_type'] ]['name'],
                                                "score" => $pscore['score'],
                                                "vp" => $pscore['vp'],
                                                "player_id" => $player_id,
                                                "player_name" => $players[ $player_id ]['player_name'],
                                                "score_delta" => $vp_wins,
                                                "vp_delta" => $vp_wins
                                           ) );
                }
            }
        }

        if ($draw_for_each_genes_world != null) {  // Pan-Galactic League
            $player_id = $draw_for_each_genes_world['player'];

            $bSkip = false;
            if (isset ($damaged_worlds[$player_id])) {
                foreach ($damaged_worlds[$player_id] as $card_type) {
                    if ($this->getCardColorFromType($card_type) == 3) {
                        $bSkip = true;
                        break;
                    }
                }
            }

            if (! $bSkip) {
                $this->drawForEachWorld($draw_for_each_genes_world['card']);
            }
        }
        if ($draw_for_each_world_rare != null) {  // Imperium War Profiteers
            $player_id = $draw_for_each_world_rare['player'];
            $cards = $this->cards->getCardsInLocation('tableau', $player_id);
            $to_draw = 0;

            $bSkip = false;
            if (isset ($damaged_worlds[$player_id])) {
                foreach ($damaged_worlds[$player_id] as $card_type) {
                    if ($this->getCardColorFromType($card_type) == 2) {
                        $bSkip = true;
                        break;
                    }
                }
            }

            if (! $bSkip) {
                $this->drawForEachWorld($draw_for_each_world_rare['card']);
            }
        }

        if ($draw_for_military_world != null) {  // Imperium Lords and Anti-Xeno League
            $player_id = $draw_for_military_world['player'];
            $cards = $this->cards->getCardsInLocation('tableau', $player_id);
            $to_draw = 0;

            $bSkip = false;
            if (isset ($damaged_worlds[$player_id])) {
                foreach ($damaged_worlds[$player_id] as $card_type) {
                    if (in_array('military', $card_type['category'])) {
                        $bSkip = true;
                        break;
                    }
                }
            }

            if (! $bSkip) {
                $this->drawForEachWorld($draw_for_military_world['card']);
            }
        }

        if ($draw_for_military_rebel_world != null) {  // Specific: Rebel Resistance
            $player_id = $draw_for_military_rebel_world;
            $cards = $this->cards->getCardsInLocation('tableau', $player_id);
            $to_draw = 0;

            foreach ($cards as $card) {
                $card_type = $this->card_types[ $card['type'] ];
                if (in_array('military', $card_type['category']) && in_array('rebel', $card_type['category'])) {
                     $to_draw ++;
                }
            }

            $this->drawCardForPlayer($player_id, $to_draw, false, 267 );
        }

        if ($draw_for_military_xeno != null) {  // Alien Historians
            $player_id = $draw_for_military_xeno['player'];
            $cards = $this->cards->getCardsInLocation('tableau', $player_id);
            $to_draw = 0;

            $bSkip = false;
            if (isset ($damaged_worlds[$player_id])) {
                foreach ($damaged_worlds[$player_id] as $card_type) {
                    if (in_array('xeno', $card_type['category'])) {
                        $bSkip = true;
                        break;
                    }
                }
            }

            if (! $bSkip) {
                $this->drawForEachWorld($draw_for_military_xeno['card']);
            }
        }

        if ($draw_for_chromosome != null) { // Specific : Uplift Code
            $player_id = $draw_for_chromosome;
            $cards = $this->cards->getCardsInLocation('tableau', $player_id);
            $to_draw = 0;

            // Scan cards to find chromosome worlds
            foreach ($cards as $card) {
                $card_type = $this->card_types[ $card['type'] ];
                if (in_array('chromosome', $card_type['category']) && $card_type['type'] == 'world') {
                       $to_draw += 2;
                }
            }

            $this->drawCardForPlayer($player_id, $to_draw, false, 152 );
        }
        if ($draw_for_rebel != null) { // Specific : Rebel cantina
            $player_id = $draw_for_rebel;
            $cards = $this->cards->getCardsInLocation('tableau', $player_id);
            $to_draw = 0;

            // Scan cards to find 6-cost developments
            foreach ($cards as $card) {
                $card_type = $this->card_types[ $card['type'] ];
                if ($card_type['type'] == 'world' && in_array('rebel', $card_type['category'])) {
                     $to_draw ++;
                }
            }

            $this->drawCardForPlayer($player_id, $to_draw, false, 131 );
        }
        if ($draw_for_imperium != null) { // Specific : Imperium Fifth Column
            $player_id = $draw_for_imperium;
            $cards = $this->cards->getCardsInLocation('tableau', $player_id);
            $to_draw = 0;

            // Scan cards to find Imperium cards
            foreach ($cards as $card) {
                $card_type = $this->card_types[ $card['type'] ];
                if (in_array('imperium', $card_type['category'])) {
                     $to_draw ++;
                }
            }

            $this->drawCardForPlayer($player_id, $to_draw, false, 255 );
        }
        if (count($draw_for_development) > 0) { // Specific : Galactic Investors
            foreach ($draw_for_development as $player_id) {
                $cards = $this->cards->getCardsInLocation('tableau', $player_id);
                $to_draw = 0;

                // Scan cards to find 5+ cost developments
                foreach ($cards as $card) {
                    $card_type = $this->card_types[ $card['type'] ];
                    if ($card_type['type']=='development' && $card_type['cost']>=5) {
                        $to_draw++;
                    }
                }

                $this->drawCardForPlayer($player_id, $to_draw, false, 266 );
            }
        }
    }

    function getSellBonusForGoodType($player_id, $good_type, $good_from_world_id)
    {
        $bonus = 0;

        // Get all bonuses
        $powers = $this->scanTableau('s', $player_id);
        foreach ($powers as $power) {
            if ($power['power'] == 'sellbonus') {
                if (in_array($good_type, $power['arg']['resource'])) { // If the type of resource matches
                    $bonusthiscard = $power['arg']['card'];

                    if (isset($power['arg']['onecardby'])) {
                        // Get chromosome cards in tableau
                        $cards = $this->cards->getCardsInLocation('tableau', $player_id);
                        foreach ($cards as $card) {
                            $card_type = $this->card_types[ $card['type'] ];
                            if (in_array('chromosome', $card_type['category'])) {
                                ++$bonusthiscard;
                            }
                        }
                    }

                    if (isset($power['arg']['fromthisworld'])) {
                        if ($power['card_id'] == $good_from_world_id) {    // Apply bonus only if world attached with resource and
                            $bonus += $bonusthiscard;              // world attached with this power are the same
                        }
                    } else {
                        $bonus += $bonusthiscard;
                    }
                }
            }
        }

        return $bonus;
    }

    // Return "true" if there is at least one card in player tableau with given tag
    function checkAtLeastOneCardInTableauWithTag($player_id, $tag)
    {
        $cards = $this->cards->getCardsInLocation('tableau', $player_id);
        $bAtLeastOne = false;
        foreach ($cards as $card) {
            if (in_array($tag, $this->card_types[ $card['type'] ]['category'])) {
                return true;
            }
        }

        return false;
    }

    function discardFromTableau($card)
    {
        // If we were only given the id, get the card object
        if (gettype($card) == "string") {
            $card = $this->cards->getCard($card);
        }

        $card_id = $card['id'];
        $player_id = $card['location_arg'];

        // Does the  world has a good on it?
        $good_id = self::getUniqueValueFromDB("SELECT card_id FROM card WHERE card_location='good' AND card_location_arg=$card_id");
        // If it does, discard it
        if ($good_id !== null) {
            $this->cards->moveCard($good_id, $this->getDiscard($player_id), 0);
        }

        // For Galactic Scavengers, also discard scavenged cards
        if ($card['type'] == 181) {
            $this->cards->moveAllCardsInLocation('scavenger', $this->getDiscard($player_id));
        }

        $score_delta = $this->card_types[ $card['type'] ]['vp'] * -1;
        $this->cards->moveCard($card_id, 'just_discarded', $player_id);
        self::DbQuery("DELETE FROM tableau_order WHERE card_id=$card_id");
        $pscore = $this->updatePlayerScore($player_id, $score_delta, false);
        $this->defered_notifyAllPlayers($this->notif_defered_id, 'updateScore', '',
                                array(
                                    "player_id" => $player_id,
                                    "score" => $pscore['score'],
                                    "vp" => $pscore['vp'],
                                    "score_delta" => $score_delta,
                                    "vp_delta" => 0
                               ));
    }

    function moveJustDiscardedToDiscard()
    {
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $this->cards->moveAllCardsInLocation('just_discarded', $this->getDiscard($player_id), $player_id);
        }
    }

    // Returns the amount of temporary military that the player can still use
    // Since this is called at takeover declaration time, we don't take into
    // account affordability as the player might gain cards, goods and prestige
    // by the time we reach the takeover resolution phase.
    function remainingTempMilitary($player_id)
    {
        $powers = $this->scanTableau(3, $player_id, null, true);
        $res = 0;
        foreach ($powers as $power) {
            switch ($power['power']) {
                // All those powers can only be used once
                case 'militaryforcetmp':
                case 'militaryforcetmp_prestige':
                case 'good_for_military':
                    $res += $power['arg']['force'];
                    break;
                // This one might have been partially used. Used amount = -1 * card_status
                case 'militaryforcetmp_discard':
                    $card_status = self::getUniqueValueFromDB("SELECT card_status FROM card WHERE card_id=" . $power['card_id']);
                    $res += $power['arg']['repeat'] + $card_status;
                    break;
            }
        }
        return $res;
    }

    // Is this a military boost power that the player can afford
    function usableMilitaryBoost($player_id, $power)
    {
        switch ($power['power']) {
            case 'militaryforcetmp':
                return true; // Discard from tableau
            case 'militaryforcetmp_discard': // Discard from hand
                return $this->cards->countCardInLocation('hand', $player_id) > 0 ;
            case 'militaryforcetmp_prestige': // Spend prestige
                return self::getUniqueValueFromDB("SELECT player_prestige FROM player WHERE player_id='$player_id'") > 0;
            case 'good_for_military': // Spend a good
                $good_type = $power['good'];
                if (is_array($good_type)) {
                    $sql = "SELECT count(*) FROM card world JOIN card good ON good.card_location_arg=world.card_id WHERE good.card_location='good' AND world.card_location_arg=$player_id ";
                } else {
                    $sql = "SELECT count(*) FROM card world JOIN card good ON good.card_location_arg=world.card_id WHERE good.card_location='good' AND world.card_location_arg=$player_id AND good.card_status=$good_type";
                }
                return self::getUniqueValueFromDB($sql) > 0;
        }
        return false;
    }

    // Set all cards to "inactive", except Oort which stores its kind in card_status and has not activable power anyway
    function resetCardStatus()
    {
        $sql = "UPDATE card SET card_status='0' WHERE card_location='tableau' AND card_type!=220";
        self::DbQuery($sql);
    }

    function getProduceTitle($player_id, $powers = null)
    {
        if ($powers == null) {
            $powers = $this->usableProducePowers($player_id, $this->windfallproduction_state()[$player_id]);
        }
        $damagedCount = intval(self::getUniqueValueFromDB(
                "SELECT COUNT(*)
                 FROM card
                 WHERE card_damaged!='0'
                 AND card_location='tableau' AND card_location_arg=$player_id"));

        $windfall = False;
        $produceifdiscard = null;
        foreach ($powers as $power) {
            if ($power['type'] == 'all' || is_int($power['type']) || $power['type'] == 'windfallproduceifdiscard') {
                $windfall = True;
            } else if ($power['type'] == 'produceifdiscard') {
                $produceifdiscard = $power['reason'];
            }
        }

        $i = 0;
        $args = [];
        if ($windfall) {
            $args[sprintf('action%s', $i++)] = clienttranslate('produce on windfall worlds');
        }
        if ($produceifdiscard) {
            $card = $this->cards->getCard($produceifdiscard);
            $args[sprintf('action%s', $i++)] = clienttranslate('discard to produce on {world}');
            $args['world'] = $this->card_types[ $card['type'] ]['name'];
        }
        if ($damagedCount > 0) {
            $args[sprintf('action%s', $i++)] = clienttranslate('repair damaged worlds');
        }

        return $args;
    }

    // Returns true if the player still has some produce actions to do like
    // producing on a windfall or repairing a damaged world
    function hasProduceActions($player_id)
    {
        $windfallState = $this->windfallproduction_state();
        $possibilities = $windfallState[ $player_id ];
        $damagedCount = intval(self::getUniqueValueFromDB(
                "SELECT COUNT(*)
                 FROM card
                 WHERE card_damaged!='0'
                 AND card_location='tableau' AND card_location_arg=$player_id"));

        $usablePowers = $this->usableProducePowers($player_id, $possibilities);
        $res = count($usablePowers) > 0 || $damagedCount > 0;
        if ($res) {
            self::notifyPlayer($player_id, 'updateProduceTitle', '', $this->getProduceTitle($player_id, $usablePowers));
        }
        return $res;
    }

    function isTakeoverPower($power, $bDefense = true)
    {
        return ($power['power'] == 'takeover'
                  || $power['power'] == 'discardtotakeover'
                  || $power['power'] == 'prestigetotakeover'
                  || $power['power'] == 'blocktakeover' && $bDefense
                  || $power['power'] == 'defense' && $bDefense);
    }

    function disableCard($card_id)
    {
        self::DbQuery("UPDATE card SET card_status=-1 WHERE card_id=$card_id");
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    // Works for both activplayer and multiactiveplayer state types
    function nextState($next)
    {
        $state = $this->gamestate->state();
        $player_id = self::getCurrentPlayerId();

        if ($state['type'] == 'activeplayer') {
            $this->gamestate->nextState($next);
        } else {
            $this->gamestate->setPlayerNonMultiactive($player_id, $next);
        }
    }

    function initialdiscardhome($start_world_id, $cards_ids)
    {
        self::checkAction('initialdiscardhome');

        $player_id = self::getCurrentPlayerId();

        $card = $this->cards->getCard($start_world_id);

        // Check that the card is in player's tableau
        if ($card['location'] != 'hiddentableau' || $card['location_arg'] != $player_id) {
            throw new feException("This card is not in your tableau");
        }

        // Discard start world
        $this->cards->moveCard($start_world_id, $this->getDiscard($player_id), 0);
        self::notifyPlayer($player_id, 'discardfromtableau', '', array("card" => $start_world_id));

        $this->initialdiscard($cards_ids);
    }

    // Initial 2 cards discard
    function initialdiscard($cards_ids)
    {
        $bScavenger = false;
        if (self::checkAction('initialdiscard', false)) {
            $cards_to_discard = 2;
        } elseif (self::checkAction('initialdiscardScavenger')) {
            $cards_to_discard = 1;
            $bScavenger = true;
        }

        $player_id = self::getCurrentPlayerId();

        // Check that the 2 cards are in player hand
        $cards = $this->cards->getCards($cards_ids);


        // Ancient World (107)
        $bAncient = (self::getUniqueValueFromDB("SELECT card_id FROM card WHERE card_type='107' AND card_location='tableau' AND card_location_arg='$player_id'") !== null);

        $state = $this->gamestate->state();
        if ($bAncient) {
            if ($state['name'] == "initialDiscard") {
                $cards_to_discard = 3;
            } elseif ($state['name'] == "initialDiscardAncientRace") {
                $cards_to_discard = 1;
            }
        }

        if (count($cards_ids) != $cards_to_discard) {
            throw new feException("You must discard $cards_to_discard cards");
        }

        // Check the two are in player's hand
        foreach ($cards_ids as $card_id) {
            if ($cards[ $card_id ]['location'] != 'hand' || $cards[ $card_id ]['location_arg'] != $player_id) {
                throw new feException("This card is not in your hand");
            }

            // Move to discard
            $this->cards->moveCard($card_id, $this->getDiscard($player_id), 0);
        }


        // Notify
        $this->notifyPlayer($player_id, "discard", '',
                                 array("cards" => $cards_ids) );

        $this->notifyUpdateCardCount();

        if ($bScavenger) {
            $this->cards->moveCards($cards_ids, 'scavenger');
            self::notifyAllPlayers("scavengerUpdate", clienttranslate('${player_name} is placing a card under Galactic Scavengers'), array(
                'player_name' => self::getCurrentPlayerName(),
                'count' => $this->cards->countCardInLocation('scavenger'),
                'card' => $this->cards->getCard($cards_ids[0])
           ));
        }

        $this->nextState("phaseCleared");
    }

    // Phase choice
    // (note: supposed to be existing phases & bonus combinations at this step)
    //  $phase = phase number (1 to 5) or search (7)
    //  $bonus by phase:
    //    explore: 0 -> +1+1 ; 1 -> +5+0 ; 100 -> orb
    //    develop: 0 -> -1
    //    settle:  0 -> +1
    //    consume: 0 -> $ ; 1 -> x2
    //    produce: 0 -> windfall ; 3 -> repair
    //  $bBonusCard = prestige action
    function choosePhase($phase, $bonus, $bBonusCard = false)
    {
        self::checkAction("choosePhase");

        $player_id = self::getCurrentPlayerId();
        $bPreviousChoiceExists = false;

        if ($this->is_twoplayers()) {
            // Have to check the consistency with previous choices, if any
            $previousChoice = $this->getPhaseChoices($player_id);
            foreach ($previousChoice as $prev_phase => $players_choices) {
                if (isset($players_choices[ $player_id ])) {
                    $bPreviousChoiceExists = true;
                    $prev_bonus = $players_choices[ $player_id ]%10;

                    if ($players_choices[ $player_id ] >= 100) { // Orb case
                        $prev_bonus = 100;
                    }

                    if ($prev_phase == 1 && $phase == 1 && $bonus == $prev_bonus) {  // Explore
                        throw new feException(self::_("You already select this phase option"), true);
                    } elseif ($prev_phase == 4 && $phase == 4 && $bonus == $prev_bonus) { // Consume
                        throw new feException(self::_("You already select this phase option"), true);
                    } elseif ($prev_phase == 5 && $phase == 5 && $bonus == $prev_bonus) { // Production
                        throw new feException(self::_("You already select this phase option"), true);
                    }
                }
            }
        }

        if ($bBonusCard) {
            if (self::getUniqueValueFromDB("SELECT NOT player_search OR player_tmp_milforce OR player_tmp_gene_force FROM player WHERE player_id='".$player_id."'") == 1) {
                throw new feException("You already used your prestige/search card");
            }

            if ($phase == 7) {
                self::DbQuery("UPDATE player SET player_tmp_gene_force = 1 WHERE player_id=$player_id");
            } else {
                // We're not spending the prestige yet because if player cancel their action
                // it's tricky to refund because of pending notifications
                // Instead, use tmpmilforce to keep track of the debt

                if (self::getUniqueValueFromDB("SELECT player_prestige FROM player WHERE player_id=$player_id") == 0) {
                    throw new feException(self::_("You don't have enough Prestige to do this action"), true);
                }

                self::DbQuery("UPDATE player SET player_tmp_milforce = 1 WHERE player_id=$player_id");
            }

            $bonus += 10;
        }

        $sql = "INSERT INTO phase (phase_id,phase_player,phase_bonus) ";
        $sql .= "VALUES ('$phase','".$player_id."','$bonus') ";
        self::DbQuery($sql);

        if ($phase == 1 && $bonus%10 == 0) {
            self::incStat(1, 'explore_p1_count', $player_id);
        } elseif ($phase == 1 && $bonus%10 == 1) {
            self::incStat(1, 'explore_p5_count', $player_id);
        } elseif ($phase == 2) {
            self::incStat(1, 'develop_count', $player_id);
        } elseif ($phase == 3) {
            self::incStat(1, 'settle_count', $player_id);
        } elseif ($phase == 4 && $bonus%10 == 0) {
            self::incStat(1, 'consumesell_count', $player_id);
        } elseif ($phase == 4 && $bonus%10 == 1) {
            self::incStat(1, 'consumex2_count', $player_id);
        } elseif ($phase == 5) {
            self::incStat(1, 'produce_count', $player_id);
        }

        self::notifyPlayer($player_id, 'phase_choices', '', $this->getPhaseChoices($player_id));

        // Go to next phase when this is done
        if (! $this->is_twoplayers()) {
            // 1 choice to be made
            $this->nextState("phaseCleared");
        } else {
            $crystal_player = $this->getPsyCrystalPlayer();
            $bCrystalPlayer = ($player_id == $crystal_player);

            // 2 choices to be made
            if ($bPreviousChoiceExists || $bCrystalPlayer) { // Note : crystal player has only 1 phase to choose
                $this->nextState("phaseCleared");
            }
        }
    }

    function cancelPhase()
    {
        $this->gamestate->checkPossibleAction("cancelPhase");
        $player_id = self::getCurrentPlayerId();

        if (self::getGameStateValue('expansion') == 4) {
            // In Brink of War, if a prestige action has been chosen, we have to give back
            // the bonus card and prevent prestige spending
            $phase_choices = $this->getPhaseChoices($player_id);
            foreach ($phase_choices as $phase_choice) {
                if (isset($phase_choice[ $player_id ]) && $phase_choice[ $player_id ] >= 10) {
                    self::DbQuery("UPDATE player SET player_tmp_milforce=0, player_tmp_gene_force=0 WHERE player_id=$player_id");
                    self::notifyAllPlayers('prestige_search', '', array($player_id => 1));
                }
            }
        }
        self::DbQuery("DELETE FROM phase WHERE phase_player=$player_id");
        self::notifyPlayer($player_id, 'phase_choices', '', $this->getPhaseChoices($player_id));
        $this->gamestate->setPlayersMultiactive(array($player_id), "phaseCleared");
    }

    function exploreCardChoice($card_ids)
    {
        $player_id = self::getCurrentPlayerId();

        $bRviGambling = self::checkAction("gamble", false);
        $bScavenging = self::checkAction("scavenge", false);
        if ($bRviGambling || $bScavenging) { // Special case for RvI Gambling World and Scavenging
            $to_keep = 1;
        } else {
            self::checkAction("exploreCardChoice");
            $explored_cards = $this->getExploredCardNumber();
            $to_keep = $explored_cards[$player_id]['keep'];
            if ($to_keep != count($card_ids)) {
                throw new feException("You must keep $to_keep cards !");
            }
        }

        // Check these cards are in "explored" zone of player
        $cards = $this->cards->getCards($card_ids);
        foreach ($cards as $card) {
            if ($card['location'] != 'explored' || $card['location_arg'] != $player_id) {
                throw new feException("Card ".$card['id']." is not in player explored zone");
            }
        }

        // Move all these cards to player hand or scavenger
        $dest = $bScavenging ? 'scavenger' : 'hand';
        $this->cards->moveCards($card_ids, $dest, $player_id);

        // Move all remaining cards to discard
        $this->cards->moveAllCardsInLocation('explored', $this->getDiscard($player_id), $player_id);

        if ($bRviGambling) {
            self::notifyPlayer($player_id, 'keepcards', '', $cards);
            $card = array_shift($cards);
            $card_type = $this->card_types[ $card["type"] ];
            self::notifyAllPlayers('rviGamblingDone', clienttranslate('${player_name} wins his bet and keeps ${card_name}'), array(
                    "i18n" => array("card_name"),
                    'player_name' => self::getCurrentPlayerName(),
                    'player_id' => $player_id,
                    "card_name" => $card_type['name'])
           );

            $this->notifyUpdateCardCount();

            // Jump to next state if needed
            $can_be_used = $this->getPossibleConsumptionCards($player_id);
            $can_be_used = $can_be_used[ $player_id ];
            if ((count($can_be_used['mand']) + count($can_be_used['opt'])) == 0) {
                $this->gamestate->setPlayerNonMultiactive($player_id, "phaseCleared");
            }
        } elseif ($bScavenging) {
            $card = array_shift($cards);
            self::notifyPlayer($player_id, 'scavengeFromExplore', '', array(
                'count' => $this->cards->countCardInLocation('scavenger'),
                'card' => $card
           ));
            self::notifyPlayer($player_id, 'clearExplore', '', array());
            $this->defered_notifyAllPlayers($this->notif_defered_id, "scavengerUpdate", clienttranslate('${player_name} is placing a card under Galactic Scavengers'), array(
                'player_name' => self::getCurrentPlayerName(),
                'count' => $this->cards->countCardInLocation('scavenger')
           ));
            $this->gamestate->setPlayerNonMultiactive($player_id, "phaseCleared");
        } else {
            self::notifyPlayer($player_id, 'keepcards', '', $cards);
            self::notifyAllPlayers('keepcards_log', clienttranslate('${player_name} keeps ${nbr} cards'), array(
                    'player_name' => self::getCurrentPlayerName(),
                    'player_id' => $player_id,
                    'nbr' => $to_keep));

            $this->notifyUpdateCardCount();

            // Go to next phase when this is done
            $this->gamestate->setPlayerNonMultiactive($player_id, "phaseCleared");
        }
    }

    function nothingToPlay()
    {
        self::checkAction("nothingToPlay");
        $this->gamestate->setPlayerNonMultiactive(self::getCurrentPlayerId(), "phaseCleared");
    }

    // Try to play this development or world if possible without cost and
    // alternatives, otherwise return the cost (and if applicable the
    // alternatives like Colony Ship).
    function playCard($card_id)
    {
        if (self::checkAction("settle", false)) {
            $cardCost = $this->getWorldCost($card_id);
        } else {
            $cardCost = $this->getDevCost($card_id);
        }


        if ($cardCost['immediate']) {
            // Immediatly play in the straightforward case
            $this->playCardAndPay($card_id, [], ['immediate' => true]);
        } else {
            // Card must either be paid for or has a choice attached to it
            self::notifyPlayer(self::getCurrentPlayerId(), 'cardcost', '', $cardCost);
        }
    }

    // Play development or world 'for real'
    // Options (array)
    //  _ colonyship: settle with colony ship => colonyship card id
    //  _ settlereplace: replace world via Terraforming Engineers
    //  _ cloaking: settle with imperium cloaking technology
    //  _ rdcrashprogram: use R&D Crash Program to reduce cost
    //  _ forfree : place it for free (Wormhole ability)
    //  _ goods : goods used to reduce the cost
    //  _ arts : artifacts used to reduce the cost
    //  _ scavenger : preset the card to save via Scavenger
    //  _ oort : specify initial kind of Alien Oort Cloud Refinery
    //  _ immediate : placed immediately for zero cost
    //  _ mode : "military" or "pay" explicitly selecting way of settling
    function playCardAndPay($card_id, $money, $options)
    {
        $player_id = self::getCurrentPlayerId();
        $bCloaking = (isset($options['cloaking']));
        if (self::checkAction("settle", false) || self::checkAction("replaceWorld", false)) {
            if ($bCloaking) {
                $cloakingcard = $this->cards->getCard($options['cloaking']);
                if (! $cloakingcard) {
                    throw new feException("This card does not exist");
                }
                $res = $this->getWorldCost($card_id, $cloakingcard['type']);
            } else {
                $res = $this->getWorldCost($card_id);
            }
        } else {
            self::checkAction("develop");
            $res = $this->getDevCost($card_id);
        }

        if (isset($options['mode'])) {
            if ($options['mode'] == 'military' && !$res['military_force']) {
                throw new feException("Unable to use military force.");
            }
        } else {
            if ($res['military_force']) {
                $options['mode'] = 'military';
            } else {
                $options['mode'] = 'pay';
            }
        }
        if ($options['mode'] == 'military' && count($money)) {
            throw new feException("No payment in military mode.");
        }
        if ($options['mode'] != 'military' && $options['mode'] != 'pay') {
            throw new feException("Invalid mode value.");
        }


        $cost = $res['cost'];
        $card = $res['card'];
        $card_type = $this->card_types[ $card['type'] ];

        // Check if there is no card of this type in player's tableau
        $sql = "SELECT card_id FROM card WHERE card_location='tableau' AND card_location_arg=$player_id ";
        $sql .= "AND card_type='".$card['type']."' ";
        $dbres = self::DbQuery($sql);
        $row = mysql_fetch_assoc($dbres);
        if ($row) {
            throw new feException(self::_("You can't have 2 cards of the same type in your tableau"), true);
        }

        // See if all money cards are in player hand and are a correct number
        $money_cards = $this->cards->getCards($money);

        foreach ($money_cards as $money_card) {
            if ($money_card['location'] != 'hand' || $money_card['location_arg'] != $player_id) {
                throw new feException("This card is not in your hand");
            }

            if ($money_card['id'] == $card_id) {
                throw new feException("You cannot pay the cost of a card with this card");
            }
        }

        // Colony ship management
        $colony_ship_card_vp = 0;
        if (isset($options['colonyship'])) {
            // Checks
            $colonyship = $this->cards->getCard($options['colonyship']);
            if (! $colonyship) {
                throw new feException("This card does not exist");
            }
            if ($colonyship['location']!='tableau' || $colonyship['location_arg']!=$player_id) {
                throw new feException("This card is not in your tableau");
            }
            // Check that the card hasn't been played in this phase
            $previously_played = self::getUniqueValueFromDB("SELECT player_previously_played FROM player WHERE player_id=".$player_id);
            if ($previously_played == $colonyship['id']) {
                throw new feException(self::_("No powers from this world may be used during this phase"), true);
            }

            $card_name = $this->card_types[ $colonyship['type'] ]['name'];

            // Check this is not an alien world
            $world_color = $this->getCardColorFromType($card_type);
            if ($world_color == 4) {
                throw new feException(sprintf(self::_("Alien technology world can't be settled with %s"), self::_($card_name)), true);
            }

            // Remove colony ship
            $this->discardFromTableau($colonyship);

            self::notifyPlayer($player_id, 'discardfromtableau', '', array("card" => $options['colonyship']));
            $this->defered_notifyAllPlayers($this->notif_defered_id, 'discardfromtableau', clienttranslate('${player_name} uses a ${card_name} to pay'),
                                            array(
                                                "i18n" => array('card_name'),
                                                "player_name" => self::getCurrentPlayerName(),
                                                "player_id" => $player_id,
                                                "card" => $options['colonyship'],
                                                'card_name' => $card_name
                                           ));
            $cost = 0;
        }
        if (isset($options['forfree'])) {
            $cost = 0;
        }
        if (isset($options['settlereplace'])) {
            // Checks
            self::checkAction("replaceWorld");
            $players_with_engineers = self::getObjectListFromDB("SELECT card_location_arg
                     FROM card
                     WHERE card_type IN ('205')
                     AND card_location='tableau'
                    ", true);

            if (! in_array($player_id, $players_with_engineers)) {
                throw new feException("You cannot use Terraforming Engineers at this turn");
            }

            $to_replace = $this->cards->getCard($options['settlereplace']);
            if (! $to_replace) {
                throw new feException("This card does not exist");
            }
            if ($to_replace['location']!='tableau' || $to_replace['location_arg']!=$player_id) {
                throw new feException("This card is not in your tableau");
            }

            $previously_played = self::getUniqueValueFromDB("SELECT player_previously_played FROM player WHERE player_id=$player_id");
            if ($options['settlereplace'] == $previously_played) {
                throw new feException(self::_("You cannot replace a world which has been played in this phase"), true);
            }

            $to_replace_type = $this->card_types[ $to_replace['type'] ];

            if ($to_replace_type['type'] != 'world') {
                throw new feException(self::_("You must replace a world"), true);
            }

            if (in_array('military', $card_type['category'])) {
                throw new feException(self::_("Only non-military worlds can be replaced"), true);
            }
            if (in_array('military', $to_replace_type['category'])) {
                throw new feException(self::_("Only non-military worlds can be replaced"), true);
            }

            if ($card_type['cost'] < $to_replace_type['cost'] || $card_type['cost'] > ($to_replace_type['cost']+3)) {
                throw new feException("You can only choose a world with a cost between +0 and +3");
            }

            // Same kind
            // Note : it one or the other is the Alien Oort, they ARE from the same type.
            if ($to_replace['type'] != 220 && $card['type'] != 220) {
                if ($this->getCardColorFromType($card_type) != $this->getCardColorFromType($to_replace_type)) {
                    throw new feException(self::_("You must choose a world from the same kind (ie: same good color)"), true);
                }
            }
            if ($card['type'] == 220) {
                // Set the color of the Alien Oort Cloud Refinery by the constraints
                $impliedType = $this->getCardColorFromType($to_replace_type);
                if (isset($options['oort']) && $options['oort'] != $impliedType) {
                    throw new feException(self::_("Invalid choice of color (must be same good color)"), true);
                }
                $options['oort'] = $impliedType;
            }

            // Remove world to replace
            $this->discardFromTableau($options['settlereplace']);

            self::notifyPlayer($player_id, 'discardfromtableau', '', array("card" => $options['settlereplace']));
            self::notifyAllPlayers('discardfromtableau', clienttranslate('${player_name} uses a ${card_name} to replace ${world}'),
                                            array(
                                                "i18n" => array('card_name', 'world'),
                                                "player_name" => self::getCurrentPlayerName(),
                                                "player_id" => $player_id,
                                                "card" => $options['settlereplace'],
                                                'card_name' => $this->card_types[ 205 ]['name'],
                                                'world' => $to_replace_type['name'],
                                                'replace_with' => $card
                                           ));
            $cost = 0;

            $this->givePrestige($player_id, 1, true, 205);
        }
        if (isset($options['cloaking'])) {
            if (self::checkAction('onlyremainingmilitary', false)) {
                throw new feException(self::_("You cannot combine cloaking (military=>civil) with Imperium Supply Convoy"));
            }

            // Checks
            if ($cloakingcard['location']!='tableau' || $cloakingcard['location_arg']!=$player_id) {
                throw new feException("This card is not in your tableau");
            }

            // Check this is not a military world
            if (in_array('military', $card_type['category'])) {
                throw new feException(self::_("You must choose a non-military world"), true);
            }

            $card_name = $this->card_types[ $cloakingcard['type'] ]['name'];

            if ($cloakingcard['type'] == 194) {
                $this->givePrestige($player_id, 2, true, self::_($card_name));
            }

            // Remove cloaking card
            $this->discardFromTableau($cloakingcard);

            self::notifyPlayer($player_id, 'discardfromtableau', '', array("card" => $options['cloaking']));
            $this->defered_notifyAllPlayers($this->notif_defered_id, 'discardfromtableau', clienttranslate('${player_name} uses a ${card_name} to conquer a world'),
                                            array(
                                                "i18n" => array('card_name'),
                                                "player_name" => self::getCurrentPlayerName(),
                                                "player_id" => $player_id,
                                                "card" => $options['cloaking'],
                                                'card_name' => $card_name
                                           ));
        }

        // good_for_settlecost / good_for_devcost
        if (isset($options['goods']) &&  count($options['goods']) > 0) {
            $options['goods'] = array_unique($options['goods']);
            $good_to_use = 0;
            foreach ($options['goods'] as $good_id) {
                $good = $this->cards->getCard($good_id);
                if ($good['location'] != 'good') {
                    throw new feException("Invalid good");
                }

                $good_host = $good['location_arg'];
                $host = $this->cards->getCard($good_host);
                if ($host['location'] != 'tableau' || $host['location_arg'] != $player_id) {
                    throw new feException("This good is not in your tableau");
                }

                $good_type = self::getUniqueValueFromDB("SELECT card_status FROM card WHERE card_id='$good_id'");

                if ($res['isWorld']) {
                    if ($good_type != 3) {
                        throw new feException("This is not a gene good");
                    }
                } else {
                    if ($good_type != 2) {
                        throw new feException("This is not a rare good");
                    }
                }

                // Discard this good
                $this->cards->moveCard($good_id, $this->getDiscard($player_id), 0);

                $good_to_use ++;
            }


            // How many cards
            if ($res['isWorld']) {
                $cards_with_good_powers = array(193, 204);
            } else {
                $cards_with_good_powers = array(193);
            }

            $power_cards = self::getObjectListFromDB("SELECT  card_id, card_type FROM card
                WHERE card_location='tableau'
                      AND card_location_arg='$player_id'
                      AND card_type IN ('".implode("','", $cards_with_good_powers)."')
                      AND card_status != -1");
            $count_cards = count($power_cards);

            if ($count_cards < $good_to_use) {
                throw new feException(self::_("You do not have enough cards using these goods for this action"), true);
            }

            if ($res['isWorld']) {
                $cost -= (3*$good_to_use);
            } else {
                $cost -= (2*$good_to_use);
            }

            $cost = max(0, $cost);

            foreach ($options['goods'] as $good_id) {
                $world = array_pop($power_cards);
                $log = clienttranslate('${player_name} consumes a good with ${world_name} power to reduce the cost');

                $this->defered_notifyAllPlayers($this->notif_defered_id, 'consume', $log, array(
                                "i18n" => array("world_name"),
                                "player_id" => $player_id,
                                "player_name" => self::getCurrentPlayerName(),
                                "world_name" =>  $this->card_types[ $world['card_type'] ]['name'],
                                "good_id" => $good_id,
                                "world_id" => $world['card_id']
                           ));
            }
        }

        // R&D crash Program
        if (isset ($options['rdcrashprogram']) && !$res['isWorld']) {
            // Checks
            $rdcrashprogram= $this->cards->getCard($options['rdcrashprogram']);
            if (! $rdcrashprogram) {
                throw new feException("This card does not exist");
            }
            if ($rdcrashprogram['location']!='tableau' || $rdcrashprogram['location_arg']!=$player_id) {
                throw new feException("This card is not in your tableau");
            }

            $card_name = $this->card_types[ $rdcrashprogram['type'] ]['name'];
            $this->discardFromTableau($rdcrashprogram);

            $cost -= 3;
            $cost = max(0, $cost);

            self::notifyPlayer($player_id, 'discardfromtableau', '', array("card" => $options['rdcrashprogram']));
            $this->defered_notifyAllPlayers($this->notif_defered_id, 'discardfromtableau', clienttranslate('${player_name} uses ${card_name} to reduce the cost'),
                                            array(
                                                    "i18n" => array('card_name'),
                                                    "player_name" => self::getCurrentPlayerName(),
                                                    "player_id" => $player_id,
                                                    "card" => $options['rdcrashprogram'],
                                                    'card_name' => $card_name
                                           ));
        }

        // artifacts_for_settlecost / good_for_devcost
        if (isset($options['arts']) &&  count($options['arts']) > 0) {
            $options['arts'] = array_unique($options['arts']);
            $arts_to_use = array();
            $art_cost_reduction = 0;
            $artpoints = 0;
            foreach ($options['arts'] as $art_id) {
                $art = $this->artefacts->getCard($art_id);
                if ($art == null) {
                    throw new feException("Invalid good");
                }

                if ($art['location'] != 'hand') {
                    throw new feException("Invalid good");
                }

                if ($art['location_arg'] != $player_id) {
                    throw new feException("This artifact is not in your tableau");
                }

                $art_type = $art['type'];

                if ($art_type==1||$art_type==13) {
                    if (!$res['isWorld']) {
                        throw new feException(self::_("This artifact can only be used to reduce non-military world cost"), true);
                    }

                    $art_to_use[$art_id] = $art_type;
                    $art_cost_reduction += ($art_type == 1) ?  2 : 3;
                    $artpoints += $this->artefact_types[ $art_type ]['vp'];
                } elseif ($art_type == 4) {
                    if (!$res['isWorld']) {
                        throw new feException(self::_("This artifact can only be used to reduce Gene world cost"), true);
                    }
                    if ($this->getCardColorFromType($card_type) != 3) {
                        throw new feException(self::_("This artifact can only be used to reduce Gene world cost"), true);
                    }
                    $art_to_use[$art_id] = $art_type;
                    $art_cost_reduction += 2;
                    $artpoints += $this->artefact_types[ $art_type ]['vp'];
                } elseif ($art_type == 7 || $art_type == 10) {
                    if ($res['isWorld']) {
                        throw new feException(self::_("This artifact can only be used to reduce development cost"), true);
                    }
                    $art_to_use[$art_id] = $art_type;
                    $art_cost_reduction += (($art_type==7) ? 2 : 3) ;
                    $artpoints += $this->artefact_types[ $art_type ]['vp'];
                }

                // Discard this art
                $this->artefacts->moveCard($art_id, "tableau", $player_id);
            }

            $cost -= $art_cost_reduction;
            $cost = max(0, $cost);

            foreach ($art_to_use as $art_id => $art_type) {
                $log = clienttranslate('${player_name} uses an artefact to reduce the cost');

                $this->defered_notifyAllPlayers($this->notif_defered_id, 'consumeArtifact', $log, array(
                                "player_id" => $player_id,
                                "player_name" => self::getCurrentPlayerName(),
                                "artifact_id" => $art_id,
                                "artifact_type" => $art_type
                           ));
            }

            if ($artpoints > 0) {
                $pscore = $this->updatePlayerScore($player_id, $artpoints, false);
                self::incStat($artpoints, 'artefact_points', $player_id);

                $this->defered_notifyAllPlayers($this->notif_defered_id, 'updateScore', clienttranslate('${player_name} scores ${score_delta} with artifact(s).'),
                                                array(
                                                    "player_name" => self::getCurrentPlayerName(),
                                                    "player_id" => $player_id,
                                                    "score" => $pscore['score'],
                                                    "vp" => $pscore['vp'],
                                                    "score_delta" => $artpoints,
                                                    "vp_delta" => 0     // Note: "consumption" vp
                                               ));
            }
        }
        if ($options['mode'] == 'pay' && count($money) != $cost) {
            throw new feException("This card cost $cost and not ".count($money));
        }

        // Discard money cards
        $this->cards->moveCards($money, $this->getDiscard($player_id), 0);

        // Check if scavenger is in tableau
        $scavenger = $this->scanTableau(2, $player_id, 'scavengerdev');
        $need_scavenging = false;
        $card_to_save = null;

        if (count($scavenger) > 0) {
            $scavenger_id = $scavenger[0]['card_id'];

            if (isset($options['scavenger' ])) {
                $card_to_save = $options['scavenger'];
            } elseif (count($money) == 1) {
                // If cost is 1, always scavenge the one card
                $card_to_save = $money[0];
            } elseif (count($money) > 0) {
                // Prompt the player to scavenge one card
                $need_scavenging = true;
                $this->cards->moveCards($money, 'explored', $player_id);
            }
        }

        // Move development/world to player tableau
        $this->cards->moveCard($card_id, 'tableau', $player_id);
        self::DbQuery("INSERT INTO tableau_order VALUES ($card_id)");

        // Signal card as "just played" in order the other player cannot retrieve it with "getalldatas"
        $sql = "UPDATE player SET player_just_played='$card_id' WHERE player_id=$player_id";
        self::DbQuery($sql);

        // Mark the card as active so that its power cannot be used in this phase
        // Also record its exact time of play
        $cround = self::getGameStateValue('current_round');
        $cphase = self::getGameStateValue('current_phase');
        $csubphase = self::getGameStateValue('current_subphase');
        self::DbQuery("UPDATE card SET card_status=-1, card_played_round=$cround, card_played_phase=$cphase, card_played_subphase=$csubphase WHERE card_id=$card_id");

        // Keep these move information for the next game state
        self::notifyPlayer($player_id, 'playcard', '',
                           array("card" => $card,
                                 "money" => $money,
                                 "player" => $player_id,
                                 "immediate" => isset($options['immediate']) && $options['immediate'],
                                 "need_scavenging" => $need_scavenging));

        $log = clienttranslate('${player_name} plays a ${card_type_name} for ${cost}');
        if ($options['mode'] == 'military') {
            $log = clienttranslate('${player_name} plays a ${card_type_name} using military force');
        }
        if ($options['mode'] == 'pay' && $res['use_contact_specialist']) {
            $log = clienttranslate('${player_name} plays a ${card_type_name} using a power to pay for Military worlds for ${cost}');
        }

        if (self::checkAction('onlymilitarysettle', false)) {
            if ($options['mode'] != 'military') {
                throw new feException(self::_("You may only settle a military world using Rebel Sneak Attack"), true);
            }

            // Discard Rebel Sneak Attack
            $sneak = self::getUniqueValueFromDB("SELECT card_id
                         FROM card
                         INNER JOIN player ON player_id=card_location_arg
                         WHERE card_type='191'
                         AND card_location='tableau'
                         AND card_location_arg = $player_id", true);

            if ($sneak === null) {
                throw new feException("Cannot find Rebel Sneak attack on tableau");
            }

            $this->discardFromTableau($sneak);

            self::notifyPlayer($player_id, 'discardfromtableau', '', array("card" => $sneak));
            $this->defered_notifyAllPlayers($this->notif_defered_id, 'discardfromtableau', clienttranslate('${player_name} uses a ${card_name} to pay'),
                                            array(
                                                "i18n" => array('card_name'),
                                                "player_name" => self::getCurrentPlayerName(),
                                                "player_id" => $player_id,
                                                "card" => $sneak,
                                                'card_name' => $this->card_types[ 191 ]['name']
                                           ));
        }
        if (self::checkAction('onlycivilnonalien', false)) {
            // Discard Terraforming project
            $tproj = self::getUniqueValueFromDB("SELECT card_id
                         FROM card
                         INNER JOIN player ON player_id=card_location_arg
                         WHERE card_type='259'
                         AND card_location='tableau'
                         AND card_location_arg = $player_id", true);

            if ($tproj === null) {
                throw new feException("Cannot find Terraforming project on tableau");
            }

            $this->discardFromTableau($tproj);

            self::notifyPlayer($player_id, 'discardfromtableau', '', array("card" => $tproj));
            $this->defered_notifyAllPlayers($this->notif_defered_id, 'discardfromtableau', clienttranslate('${player_name} uses a ${card_name} to pay'),
                                            array(
                                                "i18n" => array('card_name'),
                                                "player_name" => self::getCurrentPlayerName(),
                                                "player_id" => $player_id,
                                                "card" => $tproj,
                                                'card_name' => $this->card_types[ 259 ]['name']
                                           ));
        }

        // Wormhole does its own notification for game log
        if (isset($options['forfree'])) {
            $log = '';
        }

        $this->defered_notifyAllPlayers($this->notif_defered_id, 'playcard', $log,
                                        array(
                                            "i18n" => array("card_type_name"),
                                            "player" => $player_id,
                                            "player_name" => self::getCurrentPlayerName(),
                                            "card_type_name" => $this->card_types[ $card['type'] ]['name'],
                                            "cost" => $cost,
                                            "card" => $card
                                        ));

        if (!is_null($card_to_save) && in_array($card_to_save, $money)) {
            // Save this card on scavenger
            $this->cards->moveCard($card_to_save, 'scavenger');
            self::notifyPlayer($player_id, 'scavengerUpdate', '', array(
                'count' => $this->cards->countCardInLocation('scavenger'),
                'card' => $this->cards->getCard($card_to_save)
           ));
            $this->defered_notifyAllPlayers($this->notif_defered_id, "scavengerUpdate", clienttranslate('${player_name} is placing a card under Galactic Scavengers'), array(
                'player_name' => self::getCurrentPlayerName(),
                'count' => $this->cards->countCardInLocation('scavenger'),
           ));
        }

        $this->notifyUpdateCardCount(true);

        // Score points for this card
        $pscore = $this->updatePlayerScore($player_id, $card_type['vp'], false);
        $this->defered_notifyAllPlayers($this->notif_defered_id, 'updateScore', '',
                                        array(
                                             "player_id" => $player_id,
                                             "score" => $pscore['score'],
                                             "vp" => $pscore['vp'],
                                             "score_delta" => $card_type['vp'],
                                             "vp_delta" => 0     // Note: "consumption" vp
                                       ));

        if ($options['mode'] == 'pay' && $res['use_contact_specialist']) {
            // Is there some bonus associated?
            $diplomatbonus = $this->scanTableau(3, $player_id, 'diplomatbonus');

            foreach ($diplomatbonus as $bonus) {
                // For now : +1 prestige is the only bonus
                $this->givePrestige($player_id, 1, true, $bonus['card_type']);
            }
        }

        // See if this card has some impact on military force
        // For worlds, wait until the end of the settle phase
        if (! $res['isWorld']) {
            $this->updateMilforceIfNeeded($player_id, true);
        }

        if (isset($options['oort'])) {
            self::DbQuery("UPDATE card SET card_status=".$options['oort']." WHERE card_type=220");
            $card_type['windfalltype'] = $options['oort'];
        }
        // See if this card is a windfall (comes with a resource)
        $this->windfallInitialProduction($card_id, $card_type, true);

        if (!$need_scavenging) {
            $this->gamestate->setPlayerNonMultiactive($player_id, "phaseCleared");
        }
    }

    function takeover($card_id, $target_id, $confirmed)
    {
        // Declare a takeovers

        $player_id = self::getCurrentPlayerId();

        //// Checks on source card
        $takeovercard = $this->cards->getCard($card_id);
        if (! $takeovercard) {
            throw new feException("This card does not exist");
        }
        if ($takeovercard['location']!='tableau' || $takeovercard['location_arg']!=$player_id) {
            throw new feException("This card is not in your tableau");
        }

        if ($takeovercard['type'] == 140) {
            $targetfilter = 'militaryforce';
        } elseif ($takeovercard['type'] == 149) {
            $targetfilter = 'imperium';
        } elseif ($takeovercard['type'] == 147) {
            $targetfilter = 'rebel';
        } elseif ($takeovercard['type'] == 198) {
            $targetfilter = null;
        } elseif ($takeovercard['type'] == 191) {
            $targetfilter = 'imperium';
        } elseif ($takeovercard['type'] == 195) {
            $targetfilter = 'militaryforce';
        } else {
            throw new feException("This card has no takeover power");
        }

        // Has the card already been used in this phase
        $card_status = self::getUniqueValueFromDB("SELECT card_status FROM card WHERE card_id=$card_id");
        if ($card_status == '-1') {
            throw new feException(self::_("Already used"), true);
        }

        // => okay, we may do a takeover using this card!

        //// Checks on target cards / target tableau

        // Check that target is a military world in another player's tableau
        $target_card = $this->cards->getCard($target_id);
        if (! $target_card) {
            throw new feException("This card does not exist");
        }
        if ($target_card['location']!='tableau' || $target_card['location_arg']==$player_id) {
            throw new feException("This card is not in one of your opponents tableau");
        }

        $opponent_id = $target_card['location_arg'];

        // Has the card been played in this phase
        $played = self::getObjectFromDB("SELECT player_just_played AS just, player_previously_played AS previously FROM player WHERE player_id=$opponent_id");
        if ($played['previously'] == $target_id) {
            throw new feException(self::_("You cannot target a world placed in this phase"), true);
        }

        $card_type = $this->card_types[ $target_card['type'] ];

        $imperiumInvasionFleet = null;
        if (! in_array('military', $card_type['category'])) {
            // Civil worlds can be attacked with Imperium Invasion Fleet
            $cards = $this->cards->getCardsInLocation('tableau', $player_id);
            foreach ($cards as $card) {
                if ($card['type'] == 194) {
                    $imperiumInvasionFleet = $card;
                    break;
                }
            }
            if (is_null($imperiumInvasionFleet)) {
                throw new feException(self::_("You can only takeover military worlds"), true);
            } elseif ($card_type['type'] != 'world') {
                throw new feException(self::_("You can only takeover worlds"), true);
            }
        }


        if ($targetfilter == 'militaryforce') {
            // Check that military force of this opponent is at least 1
            if (self::getUniqueValueFromDB("SELECT player_milforce FROM player WHERE player_id='$opponent_id'") <= 0) {
                throw new feException(self::_("This opponent does not have a military force of at least one."), true);
            }
        } elseif ($targetfilter == 'rebel') {
            // Check if target is a rebel world
            if (! in_array('rebel', $card_type['category'])) {
                throw new feException(self::_("This is not a REBEL military world"), true);
            }

            // The attacker is using IIF to attack Rebel Cantina. The defender is vulnerable to the attack
            // only if he has a Rebel military in his tableau. See: https://boardgamegeek.com/article/5390492#5390492
            if (! is_null($imperiumInvasionFleet)) {
                $opponent_cards = $this->cards->getCardsInLocation('tableau', $opponent_id);
                $bAtLeastOne = false;
                foreach ($opponent_cards as $opponent_card) {
                    // Card played in this phase cannot make the player targetable
                    if (in_array($opponent_card['id'], $played)) {
                        continue;
                    }

                    $opponent_card_type = $this->card_types[ $opponent_card['type'] ];

                    if (in_array('military', $opponent_card_type['category'])
                        && in_array('rebel', $opponent_card_type['category'])) {
                            $bAtLeastOne = true;
                            break;
                    }
                }

                if (! $bAtLeastOne) {
                    throw new feException(self::_("This opponent has no Rebel military world and is not vulnerable to this takeover effect."), true);
                }
            }
        } elseif ($targetfilter == 'imperium') {
            // Check that opponent has at least 1 imperium card
            $opponent_cards = $this->cards->getCardsInLocation('tableau', $opponent_id);
            $bAtLeastOne = false;
            foreach ($opponent_cards as $opponent_card) {
                // Card played in this phase cannot make the player targetable
                if (in_array($opponent_card['id'], $played)) {
                    continue;
                }

                if (in_array('imperium', $this->card_types[ $opponent_card['type'] ]['category'])) {
                    $bAtLeastOne = true;
                }
            }

            if (! $bAtLeastOne) {
                throw new feException(self::_("This opponent has no Imperium card and is not vulnerable to this takeover effect."), true);
            }
        }

        if (!$confirmed) {
            $defense = $this->getTakeOverDefense($opponent_id, $target_card);
            $tmp_defense = $this->remainingTempMilitary($opponent_id);
            if ($tmp_defense > 0) {
                $defense .= sprintf(self::_(", max: %s"), $defense + $tmp_defense);
            }

            $attack = $this->getTakeOverAttack($player_id, $takeovercard, $target_card);
            $tmp_attack = $this->remainingTempMilitary($player_id);
            if ($tmp_attack > 0) {
                $attack .= sprintf(self::_(", max: %s"), $attack + $tmp_attack);
            }

            self::notifyPlayer($player_id, 'confirmTakeover', '',
                    array( "target_id" =>  $target_id,
                            "target_name" =>  $card_type['name'],
                            "takeovercard_id" => $takeovercard['id'],
                            "takeovercard_name" => $this->card_types[ $takeovercard['type'] ]['name'],
                            "defense" => $defense,
                            "attack" => $attack
                   ));
            return;
        }

        // Checks has been done! This is possible to use this takeover effect :)
        $sql = "UPDATE player SET player_just_played='$card_id', player_takeover_target='$target_id' WHERE player_id='$player_id'";
        self::DbQuery($sql);

        if ($takeovercard['type'] == 198) {  // prestigetotakeover
            $this->givePrestige($player_id, -1, true, $takeovercard['type']);
        }

        if ($takeovercard['type'] == 140 || $takeovercard['type'] == 191) { // Imperium Cloaking Technology & Rebel Sneak Attack
        // discardtotakeover
            $this->discardFromTableau($takeovercard);

            // Notify
            self::notifyPlayer($player_id, 'discardfromtableau', '', array("card" => $takeovercard['id']));
            $this->defered_notifyAllPlayers($this->notif_defered_id, 'discardfromtableau', self::_('${player_name} discards ${card_name} to attempt a takeover'),
                    array(
                            "i18n" => ['card_name'],
                            "player_name" => self::getCurrentPlayerName(),
                            "card_name" => $this->card_types[ $takeovercard['type'] ]['name'],
                            "player_id" => $player_id,
                            "card" => $takeovercard['id']
                   ));
        }

        if (!is_null($imperiumInvasionFleet)) {
            // Players is discarding Imperium Invasion Fleet to takeover a civil world
            $this->discardFromTableau($imperiumInvasionFleet['id']);

            // Notify
            self::notifyPlayer($player_id, 'discardfromtableau', '', array("card" => $imperiumInvasionFleet['id']));
            $this->defered_notifyAllPlayers($this->notif_defered_id, 'discardfromtableau', self::_('${player_name} discards ${card_name} to takeover a non-military world'),
                    array(
                            "i18n" => ['card_name'],
                            "player_name" => self::getCurrentPlayerName(),
                            "card_name" => $this->card_types[ $imperiumInvasionFleet['type'] ]['name'],
                            "player_id" => $player_id,
                            "card" => $imperiumInvasionFleet['id']
                   ));
        }
        // Notify
        // Note : no need to notify, we can notify afterwards
//        $players = self::loadPlayersBasicInfos();
//        $log = clienttranslate('${player_name} wants to takeover ${card_type_name} from ${player_name2} using ${takeover_card}');
//        $this->defered_notifyAllPlayers($this->notif_defered_id, 'simpleNote', $log,
//                                        array(
//                                            "player_id" => $player_id,
//                                            "player_name" => self::getCurrentPlayerName(),
//                                            "card_type_name" => $card_type['name'],
//                                            "player_name2" => $players[ $opponent_id ]['player_name'],
//                                            "takeover_card" => $this->card_types[ $takeovercard['type'] ]['name']
//                                       ));

        $this->gamestate->setPlayerNonMultiactive($player_id, "phaseCleared");
    }

    function defeatTakeover($bChoice)
    {
        self::checkAction('pangalacticsecuritycouncil');

        if ($bChoice) {
            $securitycouncil = self::getUniqueValueFromDB("SELECT card_id
                         FROM card
                         INNER JOIN player ON player_id=card_location_arg
                         WHERE card_type='185'
                         AND card_location='tableau'
                         AND (card_id!=player_previously_played OR player_previously_played IS NULL)", true);

            $sql = "UPDATE card SET card_status='1' WHERE card_location='tableau' AND card_id = '$securitycouncil'  ";
            self::DbQuery($sql);

            // Remove this takeover
            $takeover = $this->getCurrentTakeOverSituation();
            self::DbQuery("UPDATE player SET player_just_played=NULL, player_takeover_target=NULL WHERE player_id='".$takeover['player_id']."'");

            $this->givePrestige(self::getCurrentPlayerId(), -1);

            self::notifyAllPlayers('simpleNote', clienttranslate('${player_name} chooses to use Pan-Galactic Security Council to defeat takeover'), array('player_name' => self::getCurrentPlayerName()));

            $this->gamestate->setPlayerNonMultiactive(self::getCurrentPlayerId(), 'cancel');
        } else {
            $this->gamestate->setPlayerNonMultiactive(self::getCurrentPlayerId(), 'continue');
        }
    }

    function goodForMilitary($card_id, $good_id)
    {
        self::checkAction('militaryboost');

        $bInvasionDefense = (self::checkAction('resolveInvasion', false));

        $player_id = self::getCurrentPlayerId();

        // After the initial settle (during takeover resolution, or improved logistics etc)
        // no need to defer notifications
        $state = $this->gamestate->state();
        $bDefered = $state['name'] == 'settle' && self::getGameStateValue('improvedLogisticsPhase') == 0;

        if (substr($good_id, 0, 9) == 'artifact_') {
            $artifact_id = substr($good_id, 9);

            $good_type = 4;
            $good_from_world_id = 0;
            $bArtifactResource = true;
        } else {
            $good = $this->cards->getCard($good_id);
            if ($good['location'] != 'good') {
                throw new feException("Invalid good");
            }

            $good_host = $good['location_arg'];
            $host = $this->cards->getCard($good_host);
            if ($host['location'] != 'tableau' || $host['location_arg'] != $player_id) {
                throw new feException("This good is not in your tableau");
            }

            $good_type = self::getUniqueValueFromDB("SELECT card_status FROM card WHERE card_id='$good_id'");
            $bArtifactResource = false;
        }
        $card = $this->cards->getCard($card_id);
        $card_type = $this->card_types[ $card['type'] ];
        $force = null;
        $bXenoForce = false;

        foreach ($card_type['powers'][3] as $power) {
            if ($power['power'] == 'good_for_military_defense') {
                if (!$bInvasionDefense) {
                    throw new feException(self::_("This power can be used only during the Invasion step"), true);
                }
            } elseif ($power['power'] != 'good_for_military') {
                continue;
            }

            if (is_int($power['good']) && $power['good'] != $good_type) {
                throw new feException(self::_("Wrong type of good"), true);
            }

            $force = $power['arg']['force'];
            $bXenoForce = isset($power['arg']['xeno']);
        }

        if (is_null($force)) {
            throw new feException(self::_("This card has no temporary military power"), true);
        }

        // Check that the card hasn't been played in this phase
        $previously_played = self::getUniqueValueFromDB("SELECT player_previously_played FROM player WHERE player_id=$player_id");
        if ($previously_played == $card_id) {
            throw new feException(self::_("No powers from this world may be used during this phase"), true);
        }

        if (self::getUniqueValueFromDB("SELECT card_status FROM card WHERE card_id='$card_id'") == -1) {
            throw new feException(self::_("This power has already been used"), true);
        }

        // Discard this good
        if (! $bArtifactResource) {
            // In all other cases, we consume the resource
            $this->cards->moveCard($good_id, $this->getDiscard($player_id), 0);
        } else {
            $this->useArtefact($artifact_id, 'goodForMilitary');
        }

        $this->disableCard($card_id);

        $log = clienttranslate('${player_name} consumes a good with ${world_name} power to increase its military (+${force})');

        if ($bXenoForce) {
            $log = clienttranslate('${player_name} consumes a good with ${world_name} power to increase its military (+${force}) against Xeno worlds');
            $col = "player_tmp_xenoforce";
        } else {
            $col = "player_tmp_milforce";
        }

        self::DbQuery("UPDATE player SET $col=$col+$force WHERE player_id=$player_id");

        $tmpMilForce = self::getUniqueValueFromDB("SELECT player_tmp_milforce + player_tmp_xenoforce FROM player WHERE player_id=$player_id");

        if ($bDefered) {
            $this->defered_notifyAllPlayers($this->notif_defered_id, $bArtifactResource ? 'simpleNote' : 'consume', $log, array(
                            "i18n" => array("world_name"),
                            "player_id" => $player_id,
                            "player_name" => self::getCurrentPlayerName(),
                            "world_name" =>  $card_type['name'],
                            "good_id" => $good_id,
                            "world_id" => $card_id,
                            "force" => $force
                       ));
            self::notifyPlayer($player_id, $bArtifactResource ? 'simpleNote' : 'consume', '', array(
                            "good_id" => $good_id,
                            "world_id" => $card_id
                       ));

            $this->defered_notifyAllPlayers($this->notif_defered_id, 'updateTmpMilforce', '',
                    array(
                            'tmp' => $tmpMilForce,
                            'player' => $player_id
                   ));
            self::notifyPlayer($player_id, 'updateTmpMilforce', '',
                                array(
                                    'tmp' => $tmpMilForce,
                                    'player' => $player_id
                               ));
        } else {
            self::notifyAllPlayers($bArtifactResource ? 'simpleNote' : 'consume', $log, array(
                    "i18n" => array("world_name"),
                    "player_id" => $player_id,
                    "player_name" => self::getCurrentPlayerName(),
                    "world_name" =>  $card_type['name'],
                    "good_id" => $good_id,
                    "world_id" => $card_id,
                    "force" => $force
           ));

            self::notifyAllPlayers('updateTmpMilforce', '',
                    array(
                            'tmp' => $tmpMilForce,
                            'player' => $player_id
                   ));
        }

        $state = $this->gamestate->state();
        if ($state['name'] == 'takeover_attackerboost' || $state['name'] == 'takeover_defenderboost') {
            $this->gamestate->nextState("boost");
        }

        if ($bInvasionDefense) {
            self::notifyAllPlayers('forceAgainstXeno', '', array('force' => self::getCollectionFromDB("SELECT player_id, (player_xeno_milforce + CAST(player_tmp_milforce AS SIGNED) + CAST(player_tmp_xenoforce AS SIGNED)) f FROM player", true)));
            $this->checkXenoForce(array($player_id));
        }
    }

    function prestigeformilitary($card_id)
    {
        self::checkAction('militaryboost');

        $player_id = self::getCurrentPlayerId();

        // Check that this card is in tableau
        $tactics = $this->cards->getCard($card_id);
        if (! $tactics) {
            throw new feException("This card does not exist");
        }
        if ($tactics['location']!='tableau' || $tactics['location_arg']!=$player_id) {
            throw new feException("This card is not in your tableau");
        }

        // Has the card already been used in this phase
        $card_status = self::getUniqueValueFromDB("SELECT card_status FROM card WHERE card_id=$card_id");
        if ($card_status == '-1') {
            throw new feException(self::_("Already used"), true);
        }

        // After the initial settle (during takeover resolution, or improved logistics etc)
        // no need to defer notifications
        $state = $this->gamestate->state();
        $bDefered = $state['name'] == 'settle' && self::getGameStateValue('improvedLogisticsPhase') == 0;

        if ($tactics['type'] == 186) { // Alien Booby Trap
            $sql = "UPDATE player SET player_tmp_milforce=player_tmp_milforce+3 WHERE player_id=$player_id";
            self::DbQuery($sql);

            $sql = "UPDATE card SET card_status=-1 WHERE card_id=$card_id";
            self::DbQuery($sql);

            self::notifyPlayer($player_id, 'updateTmpMilforce', '',
                                array(
                                    'tmp' => self::getUniqueValueFromDB("SELECT player_tmp_milforce FROM player WHERE player_id=$player_id"),
                                    'player' => $player_id
                               ));

            $this->givePrestige($player_id, -1, $bDefered, 186);
        }

        $state = $this->gamestate->state();
        if ($state['name'] == 'takeover_attackerboost' || $state['name'] == 'takeover_defenderboost') {
            $this->gamestate->nextState("boost");
        }
    }

    function militaryTactics($card_id, $discard_id)
    {
        self::checkAction('militaryboost');

        $player_id = self::getCurrentPlayerId();

        // After the initial settle (during takeover resolution, or improved logistics etc)
        // no need to defer notifications
        $state = $this->gamestate->state();
        $bDefered = $state['name'] == 'settle' && self::getGameStateValue('improvedLogisticsPhase') == 0;

        // Check that this card is in tableau
        $tactics = $this->cards->getCard($card_id);
        if (! $tactics) {
            throw new feException("This card does not exist");
        }
        if ($tactics['location']!='tableau' || $tactics['location_arg']!=$player_id) {
            throw new feException("This card is not in your tableau");
        }

        // Check that the card hasn't been played in this phase
        $previously_played = self::getUniqueValueFromDB("SELECT player_previously_played FROM player WHERE player_id=$player_id");
        if ($previously_played == $card_id) {
            throw new feException(self::_("No powers from this world may be used during this phase"), true);
        }

        if ($tactics['type'] == 302) {
            self::checkAction('resolveInvasion');
        }

        if ($tactics['type'] == 15 || $tactics['type']==238 || $tactics['type']==302) { // New military tactics and Imperium Stealth Tactics and Anti-Xeno Militia
        // Remove military tactics
            $this->discardFromTableau($tactics);

            if ($bDefered) {
                self::notifyPlayer($player_id, 'discardfromtableau', '', array("card" => $card_id));
                $this->defered_notifyAllPlayers($this->notif_defered_id, 'discardfromtableau', clienttranslate('${player_name} uses ${card_name}'),
                                                array(
                                                    "i18n" => array("card_name"),
                                                    "player_name" => self::getCurrentPlayerName(),
                                                    "card_name" => $this->card_types[ $tactics['type'] ]['name'],
                                                    "player_id" => $player_id,
                                                    "card" => $card_id
                                               ));
            } else {
                self::notifyAllPlayers('discardfromtableau', clienttranslate('${player_name} uses ${card_name}'),
                        array(
                                "i18n" => array("card_name"),
                                "player_name" => self::getCurrentPlayerName(),
                                "card_name" => $this->card_types[ $tactics['type'] ]['name'],
                                "player_id" => $player_id,
                                "card" => $card_id
                       ));
            }
            if ($tactics['type'] == 302) {
                $col = "player_tmp_xenoforce";
            } else {
                $col = "player_tmp_milforce";
            }

            $sql = "UPDATE player SET $col=$col+3 WHERE player_id=$player_id";
            self::DbQuery($sql);

            $tmpMilforce = self::getUniqueValueFromDB("SELECT player_tmp_milforce + player_tmp_xenoforce FROM player WHERE player_id=$player_id");
            if ($bDefered) {
                self::notifyPlayer($player_id, 'updateTmpMilforce', '',
                                array(
                                    'tmp' => $tmpMilforce,
                                    'player' => $player_id
                               ));
                $this->defered_notifyAllPlayers($this->notif_defered_id, 'updateTmpMilforce', '',
                        array(
                                'tmp' => $tmpMilforce,
                                "player" => $player_id
                       ));
            } else {
                self::notifyAllPlayers('updateTmpMilforce', '',
                        array(
                                'tmp' => $tmpMilforce,
                                "player" => $player_id
                       ));
            }
        } elseif ($tactics['type'] == 106 || $tactics['type'] == 164  || $tactics['type'] == 153  || $tactics['type'] == 157 || $tactics['type'] == 182  || $tactics['type'] == 190 || $tactics['type'] == 270) { // Space mercenaries / Mercenary fleet / ...
            $to_discard = $this->cards->getCards($discard_id);
            foreach ($to_discard as $card) {
                if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) {
                    throw new feException("This card is not in your hand");
                }
            }

            $max_mercenaries = 0;
            foreach ($this->card_types[ $tactics['type'] ]['powers'][3] as $power) {
                if ($power['power'] == 'militaryforcetmp_discard') {
                    $max_mercenaries += $power['arg']['repeat'];
                }
            }

            $used_mercenaries = self::getUniqueValueFromDB("SELECT card_status FROM card WHERE card_id='$card_id'") * -1 ;

            if ($used_mercenaries == $max_mercenaries) {
                throw new feException(self::_("This power has already been used"), true);
            }

            $numDiscard = sizeof($to_discard);
            if ($used_mercenaries + $numDiscard > $max_mercenaries) {
                throw new feException(sprintf(self::_("You can discard up to %s card(s) with this power"), $max_mercenaries - $used_mercenaries), true);
            }

            self::DbQuery("UPDATE card SET card_status=card_status-$numDiscard WHERE card_id='$card_id'");

            $sql = "UPDATE player SET player_tmp_milforce=player_tmp_milforce+$numDiscard WHERE player_id=$player_id";
            self::DbQuery($sql);
            $tmpMilforce = self::getUniqueValueFromDB("SELECT player_tmp_milforce FROM player WHERE player_id=$player_id");

            if ($bDefered) {
                self::notifyPlayer($player_id, 'updateTmpMilforce', '',
                                    array(
                                        'tmp' => $tmpMilforce,
                                        'player' => $player_id
                                   ));

                $this->defered_notifyAllPlayers($this->notif_defered_id, 'simpleNote', clienttranslate('${player_name} uses ${card_name} to temporary boost Military force'),
                                                array(
                                                    "i18n" => array("card_name"),
                                                    "card_name" => $this->card_types[ $tactics['type'] ]['name'],
                                                    "player_name" => self::getCurrentPlayerName()
                                               ));
                $this->defered_notifyAllPlayers($this->notif_defered_id, 'updateTmpMilforce', '',
                        array(
                                'tmp' => $tmpMilforce,
                                'player' => $player_id
                       ));
            } else {
                self::notifyAllPlayers('simpleNote', clienttranslate('${player_name} uses ${card_name} to temporary boost Military force'),
                        array(
                                "i18n" => array("card_name"),
                                "card_name" => $this->card_types[ $tactics['type'] ]['name'],
                                "player_name" => self::getCurrentPlayerName()
                       ));
                self::notifyAllPlayers('updateTmpMilforce', '',
                        array(
                                'tmp' => $tmpMilforce,
                                'player' => $player_id
                       ));
            }

            $this->cards->moveCards($discard_id, $this->getDiscard($player_id), 0);
            self::notifyPlayer($player_id, 'discard', '', array('cards' => $discard_id));

            self::notifyPlayer($player_id, 'mercenary_used', '', array('card' => $card_id));
        }

        $state = $this->gamestate->state();
        if ($state['name'] == 'takeover_attackerboost' || $state['name'] == 'takeover_defenderboost') {
            $this->gamestate->nextState("boost");
        }

        if ($state['name'] == 'invasionGameResolution') {
            self::notifyAllPlayers('forceAgainstXeno', '', array('force' => self::getCollectionFromDB("SELECT player_id, (player_xeno_milforce + CAST(player_tmp_milforce AS SIGNED) + CAST(player_tmp_xenoforce AS SIGNED)) f FROM player", true)));
            $this->checkXenoForce(array($player_id));
        }
    }

    function bunker($card_id)
    {
        self::checkAction('resolveInvasion');

        $player_id = self::getCurrentPlayerId();

        $card = $this->cards->getCard($card_id);
        if (! $card) {
            throw new feException("This card does not exist");
        }
        if ($card['location']!='hand' || $card['location_arg']!=$player_id) {
            throw new feException("This card is not in your hand");
        }

        if (self::getUniqueValueFromDB("SELECT player_tmp_gene_force FROM player WHERE player_id=$player_id") >= 1) {
            throw new feException(self::_("Your bunker power can be used only once per turn"), true);
        }

        $sql = "UPDATE player SET player_tmp_xenoforce=player_tmp_xenoforce+2, player_tmp_gene_force='1' WHERE player_id=$player_id";
        self::DbQuery($sql);
        $tmpMilforce = self::getUniqueValueFromDB("SELECT player_tmp_milforce + player_tmp_xenoforce FROM player WHERE player_id=$player_id");

        self::notifyPlayer($player_id, 'updateTmpMilforce', '',
                            array(
                                'tmp' => $tmpMilforce,
                                'player' => $player_id
                           ));

        self::notifyAllPlayers('simpleNote', clienttranslate('${player_name} uses Bunker and discard a card to temporary boost Military force (+2)'),
                                        array(
                                            "i18n" => array("card_name"),
                                            "player_name" => self::getCurrentPlayerName()
                                       ));
        self::notifyAllPlayers('updateTmpMilforce', '',
                array(
                        'tmp' => $tmpMilforce,
                        'player' => $player_id
               ));

        $this->cards->moveCard($card_id, $this->getDiscard($player_id), 0);
        self::notifyPlayer($player_id, 'discard', '', array('cards' => array($card_id)));
        $this->notifyUpdateCardCount();

        self::notifyAllPlayers('forceAgainstXeno', '', array('force' => self::getCollectionFromDB("SELECT player_id, (player_xeno_milforce + CAST(player_tmp_milforce AS SIGNED) + CAST(player_tmp_xenoforce AS SIGNED)) f FROM player", true)));
        $this->checkXenoForce(array($player_id));
    }

    function getSellPrice($player_id, $good_type, $world_id)
    {
        // Process price
        $price = $this->sell_prices[ $good_type ];

        // Get all bonuses
        $price += $this->getSellBonusForGoodType($player_id, $good_type, $world_id);

        $phase_choice = $this->getPhaseChoice(4);
        if (isset($phase_choice[ $player_id ]) && $phase_choice[ $player_id ] >= 10) {
            $price += 3;
        }
        return $price;
    }

    // Player is selling a resource
    function sell($card_id)
    {
        self::checkAction("sell");

        $player_id = self::getCurrentPlayerId();

        // Check that card_id refers to a good in player's tableau & get its type
        $sql = "SELECT good.card_id,good.card_status,world.card_id world_id, world.card_type world_type FROM card good ";
        $sql .= "INNER JOIN card world ON world.card_id=good.card_location_arg ";
        $sql .= "WHERE good.card_id='$card_id' ";
        $sql .= "AND good.card_location='good' ";
        $sql .= "AND world.card_location='tableau' ";   // .. world must be is in current player tableau
        $sql .= "AND world.card_location_arg=$player_id";
        $dbres = self::DbQuery($sql);
        $row = mysql_fetch_assoc($dbres);
        if (! $row) {
            throw new feException(self::_("You can only sell your own goods"), true);
        }
        $good_type = $row['card_status'];
        $world_id = $row['world_id'];

        $world_type = $row['world_type'];
        if ($world_type == 220 || $world_type == 246) {
            throw new feException(
                sprintf(self::_("You cannot sell a good from %s"),
                        $this->card_types[ $world_type ]['name']),
                true);
        }

        $price = $this->getSellPrice($player_id, $good_type, $world_id);

        // Consume this resource
        $this->cards->moveCard($card_id, $this->getDiscard($player_id), 0);
        self::notifyAllPlayers('consume', '', array(
                        "player_id" => $player_id,
                        "player_name" => self::getCurrentPlayerName(),
                        "good_id" => $card_id
                   ));

        // Give cards to player
        self::notifyAllPlayers('drawCards_log', clienttranslate('${player_name} sells a ${good_name} for ${card_nbr} card(s)'),
                                    array(
                                        "i18n" => array("good_name"),
                                        "player_name" => self::getCurrentPlayerName(),
                                        "player_id" => $player_id,
                                        "card_nbr" => $price,
                                        "good_name" => $this->good_types_untr[ $good_type ]
                                   ));
        $this->drawCardForPlayer($player_id, $price);


        $this->gamestate->setPlayerNonMultiactive($player_id, "sellcleared");
    }

    // Player is discarding a resource contributing to war effort
    function warEffort($card_id)
    {
        self::checkAction("warEffort");

        $player_id = self::GetCurrentPlayerId();

        // Check that card_id refers to a good in player's tableau & get its type
        $sql = "SELECT good.card_id,good.card_status,world.card_id world_id, world.card_type world_type FROM card good ";
        $sql .= "INNER JOIN card world ON world.card_id=good.card_location_arg ";
        $sql .= "WHERE good.card_id='$card_id' ";
        $sql .= "AND good.card_location='good' ";
        $sql .= "AND world.card_location='tableau' ";   // .. world must be is in current player tableau
        $sql .= "AND world.card_location_arg=$player_id";
        $dbres = self::DbQuery($sql);
        $row = mysql_fetch_assoc($dbres);
        if (! $row) {
            throw new feException(self::_("You can only discard your own goods"), true);
        }
        $good_type = $row['card_status'];
        $world_id = $row['world_id'];
        $world_type = $row['world_type'];


        // Consume this resource
        $this->cards->moveCard($card_id, $this->getDiscard($player_id), 0);
        self::notifyAllPlayers('consume', '', array(
                        "player_id" => $player_id,
                        "player_name" => self::getCurrentPlayerName(),
                        "good_id" => $card_id
                   ));


        // +1 VP for player
        $vp_wins = 1;

        $pscore = $this->updatePlayerScore($player_id, $vp_wins, true);

        self::DbQuery("UPDATE player SET player_effort=player_effort+1 WHERE player_id=$player_id");

        self::incGameStateValue('remainingVp', -$vp_wins);
        self::incGameStateValue('xeno_repulse_goal', -1);

        self::notifyAllPlayers('updateScore', clienttranslate('${player_name} contributes to war effort with a good on ${world} and score ${score_delta} point'),
                                array(
                                    "i18n" => array('world'),
                                    "score" => $pscore['score'],
                                    "vp" => $pscore['vp'],
                                    "player_id" => $player_id,
                                    "player_name" => self::getCurrentPlayerName(),
                                    "score_delta" => $vp_wins,
                                    "vp_delta" => $vp_wins,
                                    "war_effort" => self::getUniqueValueFromDB("SELECT player_effort FROM player WHERE player_id=$player_id"),
                                    "repulse_goal" => self::getGameStateValue('xeno_repulse_goal'),
                                    "world" => $this->card_types[ $world_type ]['name']
                               ) );


        // Get the remaining good number
        $sql = "SELECT COUNT(good.card_id) ";
        $sql .= "FROM card good ";
        $sql .= "INNER JOIN card world ON world.card_id=good.card_location_arg ";
        $sql .= "WHERE good.card_location='good'
                 AND world.card_location='tableau' AND world.card_location_arg=$player_id";

        if (self::getUniqueValueFromDB($sql) == 0) {
            $this->gamestate->setPlayerNonMultiactive($player_id, "phaseCleared");
        } else {
            // Stay there!
        }
    }

    function getConsumePowerInUse()
    {
        // Check if there is already a consume power in use for this player
        $player_id = self::getCurrentPlayerId();
        $sql = "SELECT card_id, card_type FROM card ";
        $sql .= "WHERE card_status>0 ";
        $sql .= "AND card_type != 253 "; // Special case for Terraforming Colony. It has 2 distinct consume powers
        $sql .= "AND card_type != 220 "; // Oort's kind is stored in card_status
        $sql .= "AND card_location='tableau' ";
        $sql .= "AND card_location_arg='$player_id' ";
        $dbres = self::DbQuery($sql);
        return mysql_fetch_assoc($dbres);
    }

    // Consume give good on given world
    function consume($good_id, $world_id)
    {
        self::checkAction('consume');

        $player_id = self::getCurrentPlayerId();

        if (substr($good_id, 0, 9) == 'artifact_') {
            $artifact_id = substr($good_id, 9);

            $good_type = 4;
            $good_from_world_id = 0;
            $bArtifactResource = true;
        } else {
            $bArtifactResource = false;

            // Check if good exists & retrieve associated world & type
            $sql = "SELECT good.card_id,good.card_status,world.card_id world_id FROM card good ";
            $sql .= "INNER JOIN card world ON world.card_id=good.card_location_arg ";
            $sql .= "WHERE good.card_id='$good_id' ";
            $sql .= "AND good.card_location='good' ";
            $sql .= "AND world.card_location='tableau' ";   // .. world must be is in current player tableau
            $sql .= "AND world.card_location_arg='$player_id' ";
            $dbres = self::DbQuery($sql);
            $row = mysql_fetch_assoc($dbres);
            if (! $row) {
                throw new feException("Unknow good");
            }
            $good_type = $row['card_status'];
            $good_from_world_id = $row['world_id'];
        }

        // Check if world exists & retrieve type
        $sql = "SELECT card_id,card_type,card_status FROM card ";
        $sql .= "WHERE card_id='$world_id' ";
        $sql .= "AND card_location='tableau' ";
        $sql .= "AND card_location_arg='$player_id' ";
        $dbres = self::DbQuery($sql);
        $row = mysql_fetch_assoc($dbres);
        if (! $row) {
            throw new feException("Unknow world");
        }
        $world_type_id = $row['card_type'];
        $world_status = $row['card_status'];
        $world_type = $this->card_types[ $world_type_id ];

        if (isset($world_type['powers'][4])) {
            $consumption_power = $world_type['powers'][4][0];
        } else {
            throw new feException(self::_("This world has no consumption power"), true);
        }

        // Check if this world consumption power correspond to this kind of resource
        $bConsumeAll = false;
        $bConsumeForSell = false;
        if ($consumption_power['power'] == 'consume') {
            if (in_array('*', $consumption_power['arg']['input'])) {   // Could be any type
            } elseif (! in_array($good_type, $consumption_power['arg']['input'])) {
                throw new feException(self::_("Wrong type of good"), true);
            }
        } elseif ($consumption_power['power'] == 'consumeall') {
            // Consume all remaining resources
            $bConsumeAll = true;
        } elseif ($consumption_power['power'] == 'consumeforsell') {
            // Consume a good like you sell it
            $bConsumeForSell = true;
        } else {
            throw new feException(self::_("This world has no consumption power"), true);
        }


        if (isset($consumption_power['arg']['fromthisworld'])) {
            if ($good_from_world_id != $world_id) {
                throw new feException(self::_("You must use a Good coming from this world"), true);
            }
        }

        $power_in_use = $this->getConsumePowerInUse();
        if ($power_in_use) {
            $card_id_in_use = $power_in_use['card_id'];
            $card_type_in_use = $power_in_use['card_type'];
        }

        $can_be_used = $this->getPossibleConsumptionCards($player_id)[ $player_id ];
        if ($power_in_use !== null && $world_id != $card_id_in_use) {
            if (in_array($power_in_use['card_id'], $can_be_used['mand'])) {
                throw new feException(sprintf(self::_("You must fully use the consume power of %s"), $this->nameToColor($this->card_types[$card_type_in_use]['nametr'])), true);
            } else {
                // The current power only has artefact to consume which is optional. So we can switch to another power, but we have to mark the current one as used
                self::DbQuery("UPDATE card SET card_status=-1 WHERE card_id=$card_id_in_use");
            }
        }

        if ($world_status == -1 || $world_type_id == 253 && $world_status == 1) {
            throw new feException(self::_('The consumption power of this world has been used already'), true);
        }

        $bUseNewWorld = false;
        if ($power_in_use == null) {
            // We are about to use a new consumption power => see first if we can use it until the end !
            $can_be_used = $this->getPossibleConsumptionCards($player_id);
            $can_be_used = $can_be_used[ $player_id ];
            if ((! in_array($world_id, $can_be_used['mand']))
             && ( ! in_array($world_id, $can_be_used['opt']))) {
                throw new feException(self::_("You don't have enough goods to use this consumption power"), true);
            }

            $bUseNewWorld = true;
        }

        if (isset($consumption_power['arg']['different'])) {
            // Check if this type of good has been consumed already
            $sql = "SELECT player_consumed_types FROM player WHERE player_id='$player_id' ";
            $dbres = self::DbQuery($sql);
            $row = mysql_fetch_assoc($dbres);
            $already_consumed = $row['player_consumed_types'];
            if ($already_consumed == '') {
                $already_consumed = array();
            } else {
                $already_consumed = unserialize($already_consumed);
            }
            if (in_array($good_type, $already_consumed)) {
                if ($card_type_in_use == 287 && $good_type == 3) {  // Note : Adaptable Uplift Race can ALWAYS accept Genes, even if a Gene has been consumed already
                } else {
                    throw new feException(self::_("This type of good has already been consumed with this power"), true);
                }
            }
            $already_consumed[] = $good_type;
            $sql = "UPDATE player SET player_consumed_types='".addslashes(serialize($already_consumed))."' WHERE player_id='$player_id' ";
            self::DbQuery($sql);
        }

        if (! $bArtifactResource) {
            // In all other cases, we consume the resource
            $this->cards->moveCard($good_id, $this->getDiscard($player_id), 0);
        } else {
            $this->useArtefact($artifact_id, 'consume');
        }

        $inputfactor = 1;
        if (isset($consumption_power['arg']['inputfactor'])) {
            $inputfactor = $consumption_power['arg']['inputfactor'];
        }

        $bActivatePower = true;

        if ($inputfactor > 1) {
            // We must consume several goods to activate this power.
            if (($world_status+1) < $inputfactor) {
                $new_status = $world_status+1;
                $sql = "UPDATE card SET card_status='$new_status' WHERE card_id='$world_id' ";
                self::DbQuery($sql);

                if (! $bArtifactResource) {
                    self::notifyAllPlayers('consume', '', array(
                                    "good_id" => $good_id,
                                    "world_id" => $world_id
                               ));
                }
                $bActivatePower = false;
            } else {
                // With our resource we manage to active the power of this world !
                // => let's continue normally
            }
        }

        if ($bActivatePower) {
            $log = clienttranslate('${player_name} uses ${world_name} consumption power');

            self::notifyAllPlayers($bArtifactResource ? 'simpleNote' : 'consume', $log, array(
                            "i18n" => array("world_name"),
                            "player_id" => $player_id,
                            "player_name" => self::getCurrentPlayerName(),
                            "world_name" => $world_type['name'],
                            "good_id" => $good_id,
                            "world_id" => $world_id
                       ));

            $vpMultiplicator = 1;
            $phaseChoice = $this->getPhaseChoice(4);
            if (isset($phaseChoice[ $player_id ])) {
                if ($phaseChoice[ $player_id ] == 1 || $phaseChoice[ $player_id ] == 2 || $phaseChoice[ $player_id ] == 10) {  // x2 or "sell + x2" or "sell + bonus"
                    $vpMultiplicator = 2;
                } elseif ($phaseChoice[ $player_id ] == 11 || $phaseChoice[ $player_id ] == 12) {  // x2 + bonus or "sell + x2 + bonus"
                    $vpMultiplicator = 3;
                }
            }

            // Apply the result of the consumption
            if ($consumption_power['power'] == 'consume' || $consumption_power['power'] == 'consumeall') {
                if (isset($consumption_power['arg']['output']['vp'])) {
                    // Win some vp(s)
                    $vp_wins = $consumption_power['arg']['output']['vp'];

                    if ($bUseNewWorld && $bConsumeAll) {   // With consumeall, the first use of the world gives one less VP
                        $vp_wins--;
                    }

                    // x2 vp if player choose this phase
                    $vp_wins *= $vpMultiplicator;

                    $pscore = $this->updatePlayerScore($player_id, $vp_wins, true);

                    self::incGameStateValue('remainingVp', -$vp_wins);

                    self::notifyAllPlayers('updateScore', clienttranslate('${player_name} scores ${score_delta} points'),
                                            array(
                                                "score" => $pscore['score'],
                                                "vp" => $pscore['vp'],
                                                "player_id" => $player_id,
                                                "player_name" => self::getCurrentPlayerName(),
                                                "score_delta" => $vp_wins,
                                                "vp_delta" => $vp_wins
                                           ) );
                }
                if (isset($consumption_power['arg']['output']['card'])) {
                    // Draw some cards
                    $this->drawCardForPlayer($player_id, $consumption_power['arg']['output']['card'], false, $world_type_id);
                }
                if (isset($consumption_power['arg']['output']['pr'])) {
                    // get prestige
                    $this->givePrestige($player_id, $consumption_power['arg']['output']['pr'], false, $world_type_id);
                }
            } elseif ($consumption_power['power'] == 'consumeforsell') {
                // Process price
                $price = $this->sell_prices[ $good_type ];

                if ($consumption_power['arg']['usepowers']) {
                    // Get all bonuses
                    $price += $this->getSellBonusForGoodType($player_id, $good_type, $good_from_world_id);
                }

                $this->drawCardForPlayer($player_id, $price);
            }

            // Consume power: can be used only 1 time except if "repeat > 1"
            $repeat = isset( $consumption_power['arg']['repeat']) ? $consumption_power['arg']['repeat'] : 1;
            $bPowerStillActive = true;

            // Special case for Terraforming Colony. We don't set status to -1 unless
            // the consume cards power has also been used
            if ($world_type_id == 253 && $world_status != 2) {
                $bPowerStillActive = false;
                // Reset "different resources used"
                $sql = "UPDATE player SET player_consumed_types='' WHERE player_id='$player_id' ";
                self::DbQuery($sql);
                $sql = "UPDATE card SET card_status=1 WHERE card_id='$world_id' ";
                self::DbQuery($sql);
            } elseif (($world_status+1) >= $repeat) {
                // Used the maximum number of time => inactive this power
                $sql = "UPDATE card SET card_status='-1' WHERE card_id='$world_id' ";
                self::DbQuery($sql);
                $bPowerStillActive = false;

                // Reset "different resources used"
                $sql = "UPDATE player SET player_consumed_types='' WHERE player_id='$player_id' ";
                self::DbQuery($sql);
            } else {
                // Can be used another time
                $new_status = $world_status+1;
                $sql = "UPDATE card SET card_status='$new_status' WHERE card_id='$world_id' ";
                self::DbQuery($sql);
            }

            // Do we still have some consumption possibilities ?
            $can_be_used = $this->getPossibleConsumptionCards($player_id);
            $can_be_used = $can_be_used[ $player_id ];

            if ($bPowerStillActive && ! in_array($world_id, $can_be_used['mand']) && ! in_array($world_id, $can_be_used['opt'])) {
                // Not enough resource to repeat the consumption again => inactivate this world
                $sql = "UPDATE card SET card_status='-1' WHERE card_id='$world_id' ";
                self::DbQuery($sql);
                $bPowerStillActive = false;

                // Reset "different resources used"
                $sql = "UPDATE player SET player_consumed_types='' WHERE player_id='$player_id' ";
                self::DbQuery($sql);
            }

            if ((count($can_be_used['mand']) + count($can_be_used['opt'])) == 0) {
                // No more possibility => jump to next state
                $this->gamestate->setPlayerNonMultiactive($player_id, "phaseCleared");
            }
        }
    }

    // Consume cards for VPs
    // (Note: this is too different from classic "consume" to share the same method)
    function consumecard($card_ids, $consumecard_card_id)
    {
        $phase = 4;
        if (self::checkAction('exploreconsume', false)) {
            $phase = 1;
        } else {
            self::checkAction('consume');
        }

        if (count($card_ids) > 2) {
            throw new feException("You can discard up to 2 cards");
        }

        $player_id = self::getCurrentPlayerId();
        $bPrestigeTrade = $consumecard_card_id == null;
        $consumecard_type = null;

        if ($bPrestigeTrade) {
            // Consumed using Prestige Trade bonus
            $phaseBonus = $this->getPhaseChoice(4);
            if ($phaseBonus[ $player_id ] != 10 && $phaseBonus[ $player_id ] != 12) {
                throw new feException("Prestige Trade action has not been chosen");
            }
        } else {
            // Check that consumecard_card_id exists, is in player's tableau and has this power
            // (with status "available")
            $sql = "SELECT card_id,card_type,card_status FROM card ";
            $sql .= "WHERE card_id='$consumecard_card_id' ";
            $sql .= "AND card_location='tableau' ";
            $sql .= "AND card_location_arg=$player_id";
            $dbres = self::DbQuery($sql);
            $row = mysql_fetch_assoc($dbres);
            if (! $row) {
                throw new feException("This card is not in your tableau");
            }

            $consumecard_type_id = $row['card_type'];
            $consumecard_status = $row['card_status'];
            $consumecard_type = $this->card_types[ $consumecard_type_id ];

            if ($consumecard_status == -1 || $consumecard_type_id == 253 && $consumecard_status == 2) {
                throw new feException(self::_('Already used'), true);
            }

            if (isset($consumecard_type['powers'][$phase])) {
                foreach ($consumecard_type['powers'][$phase] as $power) {
                    if ($power['power'] == 'consumecard') {
                        $consumption_power = $power;
                        break;
                    }
                }
                if (! isset ($consumption_power)) {
                    throw new feException("No consumecard power for this card");
                }
            } else {
                throw new feException("This card has no consumption power");
            }

            if (isset($consumption_power['arg']['inputfactor'])) {
                if (count($card_ids) !=  $consumption_power['arg']['inputfactor']) {
                    throw new feException("You must discard exactly ".$consumption_power['arg']['inputfactor'] ." cards to use this power");
                }
            }
            elseif (isset($consumption_power['arg']['repeat'])) {
                if (count($card_ids) >  $consumption_power['arg']['repeat']) {
                    throw new feException("You can't discard more than ".$consumption_power['arg']['repeat'] ." cards to use this power");
                }
            }
        }

        // Check that cards to discard are in player hands
        $cards = $this->cards->getCards($card_ids);
        foreach ($cards as $card) {
            if ($card['location']!='hand' || $card['location_arg']!=$player_id) {
                throw new feException("This card is not in your hand");
            }
        }

        // Discard these cards
        foreach ($cards as $card) {
            $this->cards->moveCard($card['id'], $this->getDiscard($player_id), 0);
        }

        $this->notifyPlayer($player_id, "discard", '',
                                 array("cards" => $card_ids) );

        if ($bPrestigeTrade) {
            self::notifyAllPlayers('consumecard', clienttranslate('${player_name} uses Prestige Trade bonus to consume cards'), array(
                    "player_id" => $player_id,
                    "player_name" => self::getCurrentPlayerName()
           ));

            // Store in player_tmp_milforce that the power has been used
            self::DbQuery("UPDATE player SET player_tmp_milforce=1 WHERE player_id=$player_id");
        } else {
            self::notifyAllPlayers('consumecard', clienttranslate('${player_name} uses ${world_name} consumption power'), array(
                            "i18n" => array("world_name"),
                            "player_id" => $player_id,
                            "player_name" => self::getCurrentPlayerName(),
                            "world_name" => $consumecard_type['name']
                       ));

            // For Terraforming Colony, don't set status to -1 unless we also used the consume good power
            if ($consumecard_type_id == 253 && $consumecard_status != 1) {
                $new_status = 2;
            } else {
                $new_status = -1;
            }

            $sql = "UPDATE card SET card_status='$new_status' WHERE card_id='$consumecard_card_id' ";
            self::DbQuery($sql);
        }

        if ($bPrestigeTrade || isset($consumption_power['arg']['output']['vp'])) {
            // Win VPs
            $vp_wins = count($card_ids);
            $pscore = $this->updatePlayerScore($player_id, $vp_wins, true);

            self::incGameStateValue('remainingVp', -$vp_wins);

            self::notifyAllPlayers('updateScore', clienttranslate('${player_name} scores ${score_delta} points'),
                                    array(
                                        "player_name" => self::getCurrentPlayerName(),
                                        "player_id" => $player_id,
                                        "score" => $pscore['score'],
                                        "vp" => $pscore['vp'],
                                        "score_delta" => $vp_wins,
                                        "vp_delta" => $vp_wins
                                   ) );
        } elseif (isset($consumption_power['arg']['output']['card'])) {
            $this->drawCardForPlayer($player_id, $consumption_power['arg']['output']['card'], false, $consumecard_type_id);
        } elseif (isset($consumption_power['arg']['output']['pr'])) {
            $this->givePrestige($player_id, $consumption_power['arg']['output']['pr'], false, $consumecard_type_id);
        }

        $this->notifyUpdateCardCount();

        // Jump to next state if needed
        $can_be_used = $this->getPossibleConsumptionCards($player_id, false, $phase);
        $can_be_used = $can_be_used[ $player_id ];
        if ((count($can_be_used['mand']) + count($can_be_used['opt'])) == 0) {
            $this->gamestate->setPlayerNonMultiactive($player_id, "phaseCleared");
        }
    }

    function consumeprestige($consumecard_card_id)
    {
        self::checkAction('consume');

        // Check that consumecard_card_id exists, is in player's tableau and has this power
        // (with status "available")
        $player_id = self::getCurrentPlayerId();
        $sql = "SELECT card_id,card_type,card_status FROM card ";
        $sql .= "WHERE card_id='$consumecard_card_id' ";
        $sql .= "AND card_location='tableau' ";
        $sql .= "AND card_location_arg=$player_id";
        $dbres = self::DbQuery($sql);
        $row = mysql_fetch_assoc($dbres);
        if (! $row) {
            throw new feException("This card is not in your tableau");
        }

        $consumecard_type_id = $row['card_type'];
        $consumecard_status = $row['card_status'];
        $consumecard_type = $this->card_types[ $consumecard_type_id ];

        if ($consumecard_status == -1) {
            throw new feException(self::_('Already used'), true);
        }

        if (isset($consumecard_type['powers'][4])) {
            $consumption_power = $consumecard_type['powers'][4][0];
            if ($consumption_power['power'] != 'consume') {
                throw new feException("No consumecard power for this card");
            }
        } else {
            throw new feException("This card has no consumption power");
        }

        // Check enough prestige
        $this->givePrestige($player_id, -1, false, $consumecard_type_id);


        self::notifyAllPlayers('consumeprestige', clienttranslate('${player_name} uses ${world_name} consumption power'), array(
                        "i18n" => array("world_name"),
                        "player_id" => $player_id,
                        "player_name" => self::getCurrentPlayerName(),
                        "world_name" => $consumecard_type['name']
                   ));

        $sql = "UPDATE card SET card_status='-1' WHERE card_id='$consumecard_card_id' ";
        self::DbQuery($sql);

        $vpMultiplicator = 1;
        $phaseChoice = $this->getPhaseChoice(4);
        if (isset($phaseChoice[ $player_id ])) {
            if ($phaseChoice[ $player_id ] == 1 || $phaseChoice[ $player_id ] == 2 || $phaseChoice[ $player_id ] == 10) {  // x2 or "sell + x2" or "sell + bonus"
                $vpMultiplicator = 2;
            } elseif ($phaseChoice[ $player_id ] == 11 || $phaseChoice[ $player_id ] == 12) {  // x2 + bonus or "sell + x2 + bonus"
                $vpMultiplicator = 3;
            }
        }


        if (isset($consumption_power['arg']['output']['vp'])) {
            // Win VPs
            $vp_wins = $consumption_power['arg']['output']['vp'];

            $vp_wins *= $vpMultiplicator;

            $pscore = $this->updatePlayerScore($player_id, $vp_wins, true);

            self::incGameStateValue('remainingVp', -$vp_wins);

            self::notifyAllPlayers('updateScore', clienttranslate('${player_name} scores ${score_delta} points'),
                                    array(
                                        "player_name" => self::getCurrentPlayerName(),
                                        "player_id" => $player_id,
                                        "score" => $pscore['score'],
                                        "vp" => $pscore['vp'],
                                        "score_delta" => $vp_wins,
                                        "vp_delta" => $vp_wins
                                   ) );
        } elseif (isset($consumption_power['arg']['output']['card'])) {
            $this->drawCardForPlayer($player_id, $consumption_power['arg']['output']['card'], false, $consumecard_type_id);
        }

        $this->notifyUpdateCardCount();

        // Jump to next state if needed
        $can_be_used = $this->getPossibleConsumptionCards($player_id);
        $can_be_used = $can_be_used[ $player_id ];
        if ((count($can_be_used['mand']) + count($can_be_used['opt'])) == 0) {
            $this->gamestate->setPlayerNonMultiactive($player_id, "phaseCleared");
        }
    }

    function wormhole()
    {
        self::checkAction('settle');

        $player_id = self::getCurrentPlayerId();

        $card = self::getObjectFromDB("SELECT card_id id, card_type type
                     FROM card
                     INNER JOIN player ON player_id=card_location_arg
                     WHERE card_type IN ('265')
                     AND card_location='tableau'
                     AND card_location_arg='$player_id'
                     AND (card_id!=player_previously_played OR player_previously_played IS NULL)");

        if ($card === null) {
            throw new feException("Your Wormhole Prospectors has been used already");
        }


        $gambling_card_id=$card['id'];
        $gambling_type = $this->card_types[ $card['type'] ];


        // Draw a card and see if player was right
        $cards = $this->cards->pickCards(1, $this->getDeck($player_id), $player_id);
        self::incStat(1, 'cards_drawn', $player_id);
        $card = $cards[0];
        $card_type = $this->card_types[ $card['type'] ];


        if ($card_type['type'] == 'world' && !in_array('military', $card_type['category'])) {
            $this->defered_notifyAllPlayers(  $this->notif_defered_id, 'simpleNote', clienttranslate('${player_name} uses Wormhole Prospectors power, flips a ${card_name} and settle it for free!'), array(
                        "i18n" => array("card_name"),
                        "player_id" => $player_id,
                        "player_name" => self::getCurrentPlayerName(),
                        "card_name" => $card_type['name']
                   ));

            $this->playCardAndPay( $card['id'], array(), array('forfree' => true));
        } else {
            // Lose, he can keep the card
            $this->defered_notifyAllPlayers($this->notif_defered_id, 'simpleNote', clienttranslate('${player_name} uses Wormhole Prospectors power, flips a ${card_name} and keep it'), array(
                        "i18n" => array("card_name"),
                        "player_id" => $player_id,
                        "player_name" => self::getCurrentPlayerName(),
                        "card_name" => $card_type['name']
                   ));

            self::notifyPlayer($player_id, 'drawCards', '', $cards);
            $this->notifyUpdateCardCount();
            $this->gamestate->setPlayerNonMultiactive($player_id, "phaseCleared");
        }
    }

    // Gambling: draw a card, and give it to player if he find the corresponding cost
    function gambling($number)
    {
        self::checkAction('consume');

        if ($number < 1 || $number > 7) {
            throw new feException("Bad number");
        }

        $player_id = self::getCurrentPlayerId();

        $card = self::getObjectFromDB("SELECT card_id id, card_type type
                     FROM card
                     INNER JOIN player ON player_id=card_location_arg
                     WHERE card_type IN ('56','110')
                     AND card_location='tableau'
                     AND card_location_arg='$player_id'
                     AND (card_id!=player_previously_played OR player_previously_played IS NULL)");

        if ($card === null) {
            throw new feException("Your Gambling world has been used already");
        }


        $gambling_card_id=$card['id'];
        $gambling_type = $this->card_types[ $card['type'] ];

        if (isset($gambling_type['powers'][4])) {
            $consumption_power = $gambling_type['powers'][4][1];
            if ($consumption_power['power'] != 'gambling') {
                throw new feException("No gambling power for this card");
            }
        } else {
            throw new feException("This card has no consumption power");
        }

        $sql = "UPDATE player SET player_previously_played='$gambling_card_id' WHERE player_id='$player_id' ";// So we don't use it twice. Note that we cannot use status cause it is already use for the other consumption power
        self::DbQuery($sql);

        // Draw a card and see if player was right
        $cards = $this->drawCardForPlayer($player_id, 1);
        $card = $cards[0];
        $card_type = $this->card_types[ $card['type'] ];


        if ($card_type['cost'] == $number) {
            // Win, he can keep the card
            self::notifyAllPlayers('gambling', clienttranslate('${player_name} uses gambling power with ${number} and gets a ${card_name} (${card_cost})'), array(
                        "i18n" => array("card_name"),
                        "player_id" => $player_id,
                        "player_name" => self::getCurrentPlayerName(),
                        "number" => $number,
                        "card_name" => $card_type['name'],
                        "card_cost" => $card_type['cost']
                   ));

            $this->notifyUpdateCardCount();
        } else {
            // Loose, drop the card
            self::notifyAllPlayers('gambling', clienttranslate('${player_name} uses gambling power with ${number} and did not get a ${card_name} (${card_cost})'), array(
                        "i18n" => array("card_name"),
                        "player_id" => $player_id,
                        "player_name" => self::getCurrentPlayerName(),
                        "number" => $number,
                        "card_name" => $card_type['name'],
                        "card_cost" => $card_type['cost']
                   ));


            $this->notifyPlayer($player_id, "discard", '',
                                 array("cards" => array($card['id'])) );
            $this->cards->moveCard($card['id'], $this->getDiscard($player_id), 0);
        }

        // Jump to next state if needed
        $can_be_used = $this->getPossibleConsumptionCards($player_id);
        $can_be_used = $can_be_used[ $player_id ];
        if ((count($can_be_used['mand']) + count($can_be_used['opt'])) == 0) {
            $this->gamestate->setPlayerNonMultiactive($player_id, "phaseCleared");
        }
    }

    // Gambling World from Rebel vs Imperium extension (ante a card)
    function rviGambling($ante_card_id)
    {
        self::checkAction('consume');
        $player_id = self::getCurrentPlayerId();

        $card = self::getObjectFromDB("SELECT card_id id, card_type type
                FROM card
                INNER JOIN player ON player_id=card_location_arg
                WHERE card_type=130
                AND card_location='tableau'
                AND card_location_arg='$player_id'
                AND (card_id!=player_previously_played OR player_previously_played IS NULL)");

        if ($card === null) {
            throw new feException("Your Gambling world has been used already");
        }

        $gambling_card_id=$card['id'];
        $gambling_type = $this->card_types[ $card['type'] ];

        if (isset($gambling_type['powers'][4])) {
            $consumption_power = $gambling_type['powers'][4][1];
            if ($consumption_power['power'] != 'rvi_gambling') {
                throw new feException("No gambling power for this card");
            }
        } else {
            throw new feException("This card has no consumption power");
        }

        $sql = "UPDATE player SET player_previously_played='$gambling_card_id' WHERE player_id='$player_id' ";// So we don't use it twice. Note that we cannot use status cause it is already use for the other consumption power
        self::DbQuery($sql);

        $ante_card = $this->cards->getCard($ante_card_id);
        $ante_card_type = $this->card_types[ $ante_card['type'] ];

        if ($ante_card['location'] != 'hand' || $ante_card['location_arg'] != $player_id) {
            throw new feException("This card is not in your hand");
        }

        self::notifyAllPlayers('simpleNote', clienttranslate('${player_name} uses Gambling World to ante ${card_name}'), array(
                "i18n" => array("card_name"),
                "player_id" => $player_id,
                "player_name" => self::getCurrentPlayerName(),
                "card_name" => $ante_card_type['name']
       ));

        $cost = intval($ante_card_type['cost']);
        if ($cost < 1 || $cost > 6) {
            throw new feException(self::_("You must choose a card of cost or defense between 1 and 6"), true);
        }

        $win = false;

        // Reveal cards
        $cards = $this->cards->pickCardsForLocation($cost, $this->getDeck($player_id), 'explored', $player_id);
        foreach ($cards as $card) {
            $card_type = $this->card_types[ $card['type'] ];
            self::notifyAllPlayers('revealCard', clienttranslate('${player_name} flips ${card_name}'), array(
                    'i18n' => array('card_name'),
                    'player_name' => self::getCurrentPlayerName(),
                    'card_name' => $card_type['name']
               ));

            if ($card_type['cost'] > $cost) {
                $win = true;
            }
        }

        if ($win) {
            self::NotifyPlayer($player_id, "rviGambling", '', $cards);
        } else {
            self::notifyAllPlayers('simpleNote', clienttranslate('None of the revealed cards have a cost or defense higher the ${cost}. ${player_name} loses his ante'), array(
                    "player_id" => $player_id,
                    "player_name" => self::getCurrentPlayerName(),
                    "cost" => $cost
           ));
            $this->notifyPlayer($player_id, "discard", '', array("cards" => array ($ante_card_id)));
            $this->cards->moveCard($ante_card_id, $this->getDiscard($player_id), 0);
            $this->cards->moveAllCardsInLocation('explored', $this->getDiscard($player_id), $player_id);

            // Jump to next state if needed
            $can_be_used = $this->getPossibleConsumptionCards($player_id);
            $can_be_used = $can_be_used[ $player_id ];
            if ((count($can_be_used['mand']) + count($can_be_used['opt'])) == 0) {
                $this->gamestate->setPlayerNonMultiactive($player_id, "phaseCleared");
            }
        }
    }

    // Jump to next state manually
    function stopConsumption()
    {
        $player_id = self::getCurrentPlayerId();
        if (self::checkAction('exploreconsume', false)) {
            $this->gamestate->setPlayerNonMultiactive($player_id, "phaseCleared");
        } else {
            self::checkAction('consume');

            $can_be_used = $this->getPossibleConsumptionCards($player_id)[ $player_id ];
            $power_in_use = $this->getConsumePowerInUse();
            if ($power_in_use && in_array($power_in_use['card_id'], $can_be_used['mand'])) {
                $card_type_in_use = $power_in_use['card_type'];
                throw new feException(sprintf(self::_("You must fully use the consume power of %s"), $this->nameToColor($this->card_types[$card_type_in_use]['nametr'])), true);
            }

            if ((count($can_be_used['mand'])) == 0) {
                $this->gamestate->setPlayerNonMultiactive($player_id, "phaseCleared");
            } else {
                $cards = $this->cards->getCards($can_be_used['mand']);
                $text = '';
                foreach ($cards as $card) {
                    if ($text != '') {
                        $text.= ', ';
                    }
                    $text .= $this->nameToColor($this->card_types[ $card['type'] ]['nametr']);
                }
                throw new feException(self::_("You still have some consumption power to use:").' '.$text, true);
            }
        }
    }

    function noWindfallProduction()
    {
        self::checkAction('nowindfallproduction');
        $this->gamestate->setPlayerNonMultiactive(self::getCurrentPlayerId(), "phaseCleared");
    }

    // Player wants to produce on a windfall world
    function windfallProduction($card_id, $discard_id = null, $options = array(), $player_id = null)
    {
        // If we passed player_id, it's an automatic production
        if (!$player_id) {
            self::checkAction("productionwindfall");
            $player_id = self::getCurrentPlayerId();
        }


        $bMustDiscard = false;

        // Check this card
        $card = $this->cards->getCard($card_id);
        if (! $card) {
            throw new feException("This card does not exists");
        }
        if ($card['location'] != 'tableau' && $card['location_arg'] != $player_id) {
            throw new feException("This card is not in your tableau");
        }

        if (!is_null(self::getUniqueValueFromDB("SELECT pp_card_id FROM player_production WHERE pp_card_id=$card_id"))) {
            throw new feException(self::_("This world has already produced a good in this phase"), true);
        }

        $card_type = $this->card_types[ $card['type'] ];
        if (isset($options['oort'])) {
            self::DbQuery("UPDATE card SET card_status=".$options['oort']." WHERE card_type=220");
            $card_type['windfalltype'] = $options['oort'];
        }
        // Get player possibilities
        $windfallState = $this->windfallproduction_state();
        $possibilities = $windfallState[ $player_id ];
        $possibility_to_use = null;

        if (! in_array('windfall', $card_type['category'])) {
            foreach ($possibilities as $power) {
                if ($power['type'] == 'windfallproduceifdiscard' && $power['reason'] == $card_id) {
                    throw new feException(self::_("To use this power, select a card from your hand to discard, then click on the windfall world you want to produce on"), true);
                }
            }
            throw new feException(self::_("This is not a windfall world"), true);
        }

        $windfall_type = $card_type['windfalltype'];

        // Try to use a specialized power first
        foreach ($possibilities as $possibility) {
            if ($possibility['reason'] != 'phase' && $possibility['type'] == $windfall_type) {
                if (! isset($possibility['notthisworld']) || $card_id != $possibility['reason']) {
                    $possibility_to_use = $possibility['reason'];
                    // Universal Symbiont has priority if it is applicable (even more specialized)
                    if (isset($possibility['notthisworld'])) {
                        break;
                    }
                }
            }
        }
        if ($possibility_to_use == null) {
            // Second try: try to use an unspecialized power, not the phase
            foreach ($possibilities as $possibility) {
                if ($possibility['reason'] != 'phase' && $possibility['type'] == 'all') {
                    $possibility_to_use = $possibility['reason'];
                }
            }
        }

        if ($possibility_to_use == null) {
            // Fourth, use discard (specialized)
            foreach ($possibilities as $possibility) {
                if ($possibility['type'] == 'windfallproduceifdiscard' && $discard_id !== null) {
                    if ($possibility['world_type'] !== null) {
                        if ($windfall_type == $possibility['world_type']) {
                            $possibility_to_use = $possibility['reason'];
                            $bMustDiscard = true;
                        }
                    }
                }
            }
        }
        if ($possibility_to_use == null) {
            // Fourth, use discard (unspecialized)
            foreach ($possibilities as $possibility) {
                if ($possibility['type'] == 'windfallproduceifdiscard' && $discard_id !== null) {
                    if ($possibility['world_type'] == null) {
                        $possibility_to_use = $possibility['reason'];
                        $bMustDiscard = true;
                    }
                }
            }
        }

        if ($possibility_to_use == null) {
            // Third and last try, use phase specific power
            foreach ($possibilities as $possibility) {
                if ($possibility['reason'] == 'phase') {
                    $possibility_to_use = 'phase';
                }
            }
        }

        if ($possibility_to_use == null) {
            throw new feException(self::_("None of your powers allow you to produce on this world"), true);
        }
        if ($this->cards->countCardInLocation('good', $card_id) != 0) {
            throw new feException(self::_('There is already a good in this world'), true);
        }

        ////// At this step, we have a possibility for sure

        if ($bMustDiscard) {
            // Move to discard
            $this->cards->moveCards(array($discard_id), $this->getDiscard($player_id), 0);

            $this->notifyUpdateCardCount();

            // Notify
            $this->notifyPlayer($player_id, "discard", '',
                                     array("cards" =>  array($discard_id)) );
        }

        // Put a resource on this windfall

        $good_card = $this->cards->pickCardForLocation($this->getDeck($player_id), 'good', $card_id);
        self::incStat(1, 'good_produced', $player_id);

        // Store good type in "card_status"
        $sql = "UPDATE card SET card_status='".$windfall_type."' ";
        $sql .= "WHERE card_id='".$good_card['id']."' ";
        self::DbQuery($sql);

        self::notifyAllPlayers('goodproduction', '', array(
                    "world_id" => $card_id,
                    "good_type" => $windfall_type,
                    "good_id" => $good_card['id'],
                    "windfallreason" => $possibility_to_use,
                    "produced_by" => $player_id
               ));

        $sql = "INSERT INTO player_production (pp_player_id, pp_good_id, pp_card_id) VALUES ";
        $sql .= "($player_id, $windfall_type, $card_id) ";
        self::DbQuery($sql);

        // Check "drawifproduce" powers
        if (isset($card_type['powers'][5])) {
            foreach ($card_type['powers'][5] as $power) {
                if ($power['power'] == 'drawifproduce') {
                    $card_drawn_nbr = $power['arg']['card'];
                    $this->drawCardForPlayer($player_id, $card_drawn_nbr, false, $card['type']);
                }
            }
        }

        // Put the corresponding world in inactive mode
        if ($possibility_to_use != 'phase') {
            $sql = "UPDATE card SET card_status='-1' WHERE card_id='$possibility_to_use' ";
            self::DbQuery($sql);
        } else {
            $sql = "UPDATE phase SET phase_bonus = phase_bonus+1 WHERE phase_id=5 AND phase_bonus!=3 AND phase_player=$player_id";
            self::DbQuery($sql);
        }

        // If no more power or no more world to produce on and no damaged worlds, inactive this player
        if (!$this->hasProduceActions($player_id)) {
            $this->gamestate->setPlayerNonMultiactive($player_id, 'phaseCleared');
        }
    }

    function changeOortType($kind_id)
    {
        self::DbQuery("UPDATE card SET card_status=$kind_id WHERE card_type=220");

        $res = self::getObjectFromDB("SELECT card_id, card_location_arg FROM card WHERE card_type=220");
        $oort_id = $res['card_id'];
        $player_name = self::loadPlayersBasicInfos()[$res['card_location_arg']]['player_name'];
        $card = $this->cards->getCard($oort_id);

        // Update tooltip and class
        self::notifyAllPlayers('oortKindChanged', clienttranslate('${player_name} changes ${card_name} to ${kind}'), array(
            "i18n" => ['card_name', 'kind'],
            "player_name" => $player_name,
            "card" => $card,
            "card_name" => $this->card_types[$card['type']]['name'],
            "kind_id" => $kind_id,
            "kind" => $this->good_types_untr[ $kind_id ]
       ));

        // If oort has a good on it, update it and its display
        $sql = "SELECT card_id FROM card "
              ."WHERE card_location = 'good' "
              ."AND card_location_arg = $oort_id";

        $good_id = self::getUniqueValueFromDB($sql);

        if ($good_id != null) {
            self::DbQuery("UPDATE card SET card_status=$kind_id WHERE card_id=".$good_id);
            self::notifyAllPlayers('goodproduction', '', array(
                    "world_id" => $oort_id,
                    "good_type" => $kind_id,
                    "good_id" => $good_id
           ));
        }
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
        self::notifyAllPlayers('updateScore', '',
                                array(
                                    "player_id" => $player_id,
                                    "score" => $pscore['score'],
                                    "score_delta" => $score_delta,
                               ));

        // Refresh the military
        $this->updateMilforceIfNeeded($player_id);
        self::notifyAllPlayers('updateSpecializedMilitary', '', $this->getSpecializedMilitary());

        $world_name = $this->card_types[ $card['type'] ]['name'];
        $card['damaged'] = $card['type'];
        $card['type'] = 1000;
        self::notifyAllPlayers('damageWorld', clienttranslate('${player_name} damages ${world}'),
                                        array(
                                            "i18n" => array('world'),
                                            "player_name" => self::getCurrentPlayerName(),
                                            "card" => $card,
                                            "world" => $world_name
                                       ));
    }

    function produceifdiscard($card_id, $discard_id)
    {
        self::checkAction("productionwindfall");

        $player_id = self::getCurrentPlayerId();

        // Check this card
        $card = $this->cards->getCard($card_id);
        if (! $card) {
            throw new feException("This card does not exists");
        }
        if ($card['location'] != 'tableau' && $card['location_arg'] != $player_id) {
            throw new feException("This card is not in your tableau");
        }

        $card_type = $this->card_types[ $card['type'] ];

        if ($card_type['powers'][5][0]['power'] != 'produceifdiscard') {
            throw new feException("This world has no produce if discard power");
        }

        // Check if there is a resource already in this world
        if ($this->cards->countCardInLocation('good', $card_id) != 0) {
            throw new feException(self::_('There is already a good in this world'), true);
        }

        $card_ids = array($discard_id);
        $cards = $this->cards->getCards($card_ids);
        foreach ($cards as $card) {
            if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) {
                throw new feException("This card is not in your hand");
            }
        }

        // Move to discard
        $this->cards->moveCards($card_ids, $this->getDiscard($player_id), 0);

        $this->notifyUpdateCardCount();

        // Notify
        $this->notifyPlayer($player_id, "discard", '',
                                 array("cards" => $card_ids) );



        // Put a resource on this world

        $good_card = $this->cards->pickCardForLocation($this->getDeck($player_id), 'good', $card_id);
        self::incStat(1, 'good_produced', $player_id);

        $windfall_type = 4; // for now, only Aliens

        // Store good type in "card_status"
        $sql = "UPDATE card SET card_status='".$windfall_type."' ";
        $sql .= "WHERE card_id='".$good_card['id']."' ";
        self::DbQuery($sql);

        $sql = "UPDATE card SET card_status='-1' WHERE card_id='$card_id' ";
        self::DbQuery($sql);


        self::notifyAllPlayers('goodproduction', '', array(
                    "world_id" => $card_id,
                    "good_type" => $windfall_type,
                    "good_id" => $good_card['id'],
                    "windfallreason" => $card_id,
                    "produced_by" => $player_id
               ));

        $sql = "INSERT INTO player_production (pp_player_id, pp_good_id, pp_card_id) VALUES ";
        $sql .= "($player_id, $windfall_type, $card_id) ";
        self::DbQuery($sql);

        // If no more power or no more world to produce on and no damaged worlds, inactive this player
        if (!$this->hasProduceActions($player_id)) {
            $this->gamestate->setPlayerNonMultiactive($player_id, 'phaseCleared');
        }
    }

    function endRoundDiscard($card_ids)
    {
        self::checkAction("endrounddiscard");

        // Check that the cards are in player hand
        $cards = $this->cards->getCards($card_ids);

        $player_id = self::getCurrentPlayerId();
        foreach ($cards as $card) {
            if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) {
                throw new feException("This card is not in your hand");
            }
        }

        $count = $this->getEndRoundDiscardNumber();
        if ($count[ $player_id ] != count($cards)) {
            throw new feException("You must discard ".$count[ $player_id ]." cards");
        }

        // Is there Retrofit ?
        $player_with_retrofit = self::getUniqueValueFromDB("SELECT card_location_arg
                     FROM card
                     WHERE card_type='218'
                     AND card_location='tableau'", true);

        if ($player_with_retrofit !== null && $player_with_retrofit != $player_id) {
            // Move to retrofit
            $this->cards->moveCards($card_ids, 'retrofit', $player_id);
        } else {
            // Move to discard
            $this->cards->moveCards($card_ids, $this->getDiscard($player_id), 0);
        }

        $this->notifyUpdateCardCount();

        // Notify
        $this->notifyPlayer($player_id, "discard", '',
                                 array("cards" => $card_ids) );

        $this->gamestate->setPlayerNonMultiactive($player_id, self::getGameStateValue('expansion') != 7 ?  "allPlayersValid" : "invasionGame");
    }

    function developdiscard($card_ids)
    {
        self::checkAction("developdiscard");

        // Check that the cards are in player hand
        $cards = $this->cards->getCards($card_ids);

        $player_id = self::getCurrentPlayerId();
        foreach ($cards as $card) {
            if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) {
                throw new feException("This card is not in your hand");
            }
        }

        $count = $this->getDevelopDiscard();
        if ($count[ $player_id ] != count($cards)) {
            throw new feException("You must discard ".$count[ $player_id ]." cards");
        }

        // Move to discard
        $this->cards->moveCards($card_ids, $this->getDiscard($player_id), 0);

        $this->notifyUpdateCardCount();

        // Notify
        $this->notifyPlayer($player_id, "discard", '',
                                 array("cards" => $card_ids) );

        $this->gamestate->setPlayerNonMultiactive($player_id, "done");
    }

    function settlediscard($card_ids)
    {
        self::checkAction("settlediscard");

        // Check that the cards are in player hand
        $cards = $this->cards->getCards($card_ids);

        $player_id = self::getCurrentPlayerId();
        foreach ($cards as $card) {
            if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) {
                throw new feException("This card is not in your hand");
            }
        }


        $count = $this->getSettleDiscard();

        if ($count[ $player_id ] != count($cards)) {
            throw new feException("You must discard ".$count[ $player_id ]." cards");
        }

        // Move to discard
        $this->cards->moveCards($card_ids, $this->getDiscard($player_id), 0);

        $this->notifyUpdateCardCount();

        // Notify
        $this->notifyPlayer($player_id, "discard", '',
                                 array("cards" => $card_ids) );

        $this->gamestate->setPlayerNonMultiactive($player_id, "done");
    }

    function discardToPutGood($card_ids)
    {
        self::checkAction("discardToPutGood");
        $player_id = self::getCurrentPlayerId();

        // Check that the cards are in player hand
        $cards = $this->cards->getCards($card_ids);

        foreach ($cards as $card) {
            if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) {
                throw new feException("This card is not in your hand");
            }
        }

        if (count($cards) != 1) {
            throw new feException("You must discard 1 cards");
        }

        // Move to discard
        $this->cards->moveCards($card_ids, $this->getDiscard($player_id), 0);

        $this->notifyUpdateCardCount();

        // Notify
        $this->notifyPlayer($player_id, "discard", '',
                                 array("cards" => $card_ids) );

        // Produce on world
        $world = self::getObjectFromDB("SELECT card_id, card_type FROM card WHERE card_type IN ('244', '286') AND card_location='tableau'");
        $card_id = $world['card_id'];
        $card_type = $this->card_types[ $world['card_type'] ];
        $good_type = $this->getCardColorFromType($card_type);

        // Place a good on this card
        $good_card = $this->cards->pickCardForLocation($this->getDeck($player_id), 'good', $card_id);

        // Store good type in "card_status"
        $sql = "UPDATE card SET card_status=$good_type ";
        $sql .= "WHERE card_id='".$good_card['id']."' ";
        self::DbQuery($sql);

        self::notifyAllPlayers('goodproduction', '', array(
                    "world_id" => $card_id,
                    "good_type" => $good_type,
                    "good_id" => $good_card['id']
               ));



        $this->gamestate->setPlayerNonMultiactive($player_id, "done");
    }

    function exploreDiscard($card_ids)
    {
        self::checkAction("exploreCardChoice");

        // Check that the cards are in player hand
        $cards = $this->cards->getCards($card_ids);

        $player_id = self::getCurrentPlayerId();
        foreach ($cards as $card) {
            if ($card['location'] != 'hand' || $card['location_arg'] != $player_id) {
                throw new feException("This card is not in your hand");
            }
        }

        $nbr = $this->getExploredCardNumber();
        if ($nbr[ $player_id ]['mix'] !== true) {
            throw new feException("You have no Exploremix power");
        }

        $to_discard = max(0, $nbr[ $player_id ]['draw'] - $nbr[ $player_id ]['keep']);
        if ($to_discard != count($card_ids)) {
            throw new feException("You must discard exactly $to_discard cards");
        }

        // Move to discard
        $this->cards->moveCards($card_ids, $this->getDiscard($player_id), 0);

        $this->notifyUpdateCardCount();

        // Notify
        $this->notifyPlayer($player_id, "discard", '',
                                 array("cards" => $card_ids) );

        $this->gamestate->setPlayerNonMultiactive($player_id, "phaseCleared");
    }

    function searchchoose($choice)
    {
        self::checkAction('searchActionChoose');

        $player_id = self::getActivePlayerId();

        if ($choice == 1) {
            // Keep
            self::incStat(1, 'cards_drawn', $player_id);
            self::DbQuery("DELETE FROM phase WHERE phase_player='$player_id' AND phase_id='7'");
            self::setGameStateValue('repeatPhase', 0);
            $this->cards->moveAllCardsInLocation('aside', $this->getDiscard($player_id));
            $this->gamestate->nextState('done');
        } else {
            // Discard
            $card_id = self::getGameStateValue('improvedLogisticsPhase');

            $this->cards->moveCard($card_id, 'aside', 0);

            $this->notifyUpdateCardCount();

            // Notify
            $this->notifyPlayer($player_id, "discard", '',
                                     array("cards" => array($card_id)) );

            $this->doSearch(self::getGameStateValue('search'));
        }
    }

    function search($category)
    {
        self::checkAction('searchAction');
        $this->doSearch($category);
    }

    function doSearch($category)
    {

        $player_id = self::getActivePlayerId();

        self::setGameStateValue('search', $category);

        // See first card on the drawpile
        while (true) {
            if ($this->cards->countCardInLocation($this->getDeck($player_id)) == 0
                 && $this->cards->countCardInLocation($this->getDiscard($player_id)) == 0) {
                // We already reshuffled the discard, search has failed
                self::DbQuery("UPDATE player SET player_search='1' WHERE player_id='$player_id'");
                self::notifyAllPlayers('searchFailed', clienttranslate('We could not find any cards of the specified category in the deck : ${player_name} keeps his search card.'), array('player_name' => self::getActivePlayerName()));
                self::DbQuery("DELETE FROM phase WHERE phase_player='$player_id' AND phase_id='7'");
                $this->cards->moveAllCardsInLocation('aside', $this->getDiscard($player_id));
                $this->gamestate->nextState('done');
                return ;
            }
            $card = $this->cards->pickCardForLocation($this->getDeck($player_id), 'aside');
            $card_type = $this->card_types[ $card['type'] ];

            $bDoesThisCardMatch = false;

            if ($category == 1) {
                if ($card_type['type'] == 'development') {
                    if (isset($card_type['powers'][3])) {
                        foreach ($card_type['powers'][3] as $power) {
                            if ($power['power'] == 'militaryforce' && !isset($power['arg']['worldtype']) && !isset($power['arg']['worldfilter'])) {
                                if ($power['arg']['force'] == 1 || $power['arg']['force'] == 2) {
                                    $bDoesThisCardMatch = true;
                                }
                            }
                        }
                    }
                }
            } elseif ($category == 2) {
                if ($card_type['type'] == 'world') {
                    if (in_array('military', $card_type['category'])
                     && in_array('windfall', $card_type['category'])) {
                        if ($card_type['cost'] >= 1 && $card_type['cost'] <= 2) {
                            $bDoesThisCardMatch = true;
                        }
                    }
                }
            } elseif ($category == 3) {
                if ($card_type['type'] == 'world') {
                    if (!in_array('military', $card_type['category'])
                     && in_array('windfall', $card_type['category'])) {
                        if ($card_type['cost'] >= 1 && $card_type['cost'] <= 2) {
                            $bDoesThisCardMatch = true;
                        }
                    }
                }
            } elseif ($category == 4) {
                if ($card_type['type'] == 'world' && in_array('chromosome', $card_type['category'])) {
                    $bDoesThisCardMatch = true;
                }
            } elseif ($category == 5) {
                if ($card_type['type'] == 'world') {
                    if (isset($card_type['windfalltype']) && $card_type['windfalltype']==4) {
                        $bDoesThisCardMatch = true;
                    } elseif ($card['type'] == 220) {
                        $bDoesThisCardMatch = true;
                    } elseif ($this->getCardColorFromType($card_type) == 4) {
                        $bDoesThisCardMatch = true;
                    }
                }
            } elseif ($category == 6) {
                if (isset($card_type['powers'][4])) {
                    foreach ($card_type['powers'][4] as $power) {
                        if ($power['power'] == 'consume'
                             && (isset($power['arg']['inputfactor']) && $power['arg']['inputfactor']>=2
                                 || isset($power['arg']['repeat']) && $power['arg']['repeat']>=2)
                             && (count($power['arg']['input']) == 0 || $power['arg']['input'][0] != 'pr')
                             || $power['power'] == 'consumeall') {
                            $bDoesThisCardMatch = true;
                        }
                    }
                }
            } elseif ($category == 7) {
                if ($card_type['type'] == 'world') {
                    if (in_array('military', $card_type['category'])) {
                        if ($card_type['cost'] >= 5) {
                            $bDoesThisCardMatch = true;
                        }
                    }
                }
            } elseif ($category == 8) {
                if ($card_type['type'] == 'development') {
                    if ($card_type['cost'] == 6 && $card['type'] != 151) {
                        $bDoesThisCardMatch = true;
                    }
                }
            } elseif ($category == 9) {
                if (isset($card_type['powers'][3])) {
                    foreach ($card_type['powers'][3] as $power) {
                        if ($this->isTakeoverPower($power)) {
                            $bDoesThisCardMatch = true;
                        }
                    }
                }
            } else {
                throw new feException("This category does not exists");
            }

            if ($bDoesThisCardMatch) {
                // We found our card !
                self::notifyAllPlayers('revealCard', clienttranslate('${player_name} draws ${reveal}'), array(
                    'i18n' => array('reveal'),
                    'player_name' => self::getActivePlayerName(),
                    'reveal' => $this->card_types[ $card['type'] ]['name']
               ));


                $this->cards->moveCard($card['id'], 'hand', $player_id);

                self::notifyPlayer($player_id, 'drawCards', '', array($card));
                $this->notifyUpdateCardCount();

                $oort_id = self::getUniqueValueFromDB("SELECT card_id FROM card WHERE card_type=220");
                $oortMayMatch = $category == 5 && $card['id'] == $oort_id;

                // Oort may or may not match alien search, player decide.
                if (self::getGameStateValue('repeatPhase') == 0 || $oortMayMatch) {
                    // First card kept : must choose if we keep it or not
                    self::setGameStateValue('improvedLogisticsPhase', $card['id']);
                    if (! $oortMayMatch) {
                        self::setGameStateValue('repeatPhase', 1);
                    }
                    $this->gamestate->nextState('maykeep');
                    return;
                } else {
                    // Automatically keep it! No choice!
                    self::incStat(1, 'cards_drawn', $player_id);
                    self::DbQuery("DELETE FROM phase WHERE phase_player='$player_id' AND phase_id='7'");
                    self::setGameStateValue('repeatPhase', 0);
                    $this->cards->moveAllCardsInLocation('aside', $this->getDiscard($player_id));
                    $this->gamestate->nextState('done');
                    return ;
                }
            } else {
                // Just notify
                self::notifyAllPlayers('revealCard', clienttranslate('${player_name} reveals ${reveal}'), array(
                    'i18n' => array('reveal'),
                    'player_name' => self::getActivePlayerName(),
                    'reveal' => $this->card_types[ $card['type'] ]['name']
               ));
            }
        }
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
            throw new feException("This card is not in your tableau");
        }

        if ($this->card_types[ $card['type'] ]['type'] != 'world') {
            throw new feException(self::_("You must select a world"), true);
        }

        if (self::getUniqueValueFromDB("SELECT card_damaged FROM card WHERE card_id='$card_id'") != 0) {
            throw new feException(self::_("This world has been damaged already"), true);
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
            throw new feException("This card is not in your tableau");
        }

        if ($this->card_types[ $card['type'] ]['type'] != 'world') {
            throw new feException(self::_("You must select a world"), true);
        }

        $real_type = self::getUniqueValueFromDB("SELECT card_damaged FROM card WHERE card_id='$card_id'");
        if ($real_type == 0) {
            throw new feException("You must choose a damaged world");
        }

        $notifargs = array(
                        "i18n" => array('world'),
                        "player_name" => self::getCurrentPlayerName(),
                        "player_id" => $player_id,
                        "world" => $this->card_types[ $real_type ]['name']
                   );

        // Check available repair power
        $phase_repair = self::getUniqueValueFromDB("SELECT player_tmp_gene_force FROM player WHERE player_id='$player_id'");

        if ($phase_repair > 0) {
            self::DbQuery("UPDATE player SET player_tmp_gene_force=player_tmp_gene_force-1 WHERE player_id='$player_id'");
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
                        throw new feException("Invalid good");
                    }

                    $good_host = $good['location_arg'];
                    $host = $this->cards->getCard($good_host);
                    if ($host['location'] != 'tableau' || $host['location_arg'] != $player_id) {
                        throw new feException("This good is not in your tableau");
                    }

                    // Discard the good
                    $this->cards->moveCard($good_id, $this->getDiscard($player_id), 0);

                    self::notifyAllPlayers('consume', '', array(
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
                            throw new feException("This card is not in your hand");
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
                    throw new feException(self::_("You must choose 2 cards OR 1 resource to discard to repair this world"), true);
                }
            }
        }

        // Repair the world
        self::DbQuery("UPDATE card SET card_type=card_damaged WHERE card_id='$card_id'");
        self::DbQuery("UPDATE card SET card_damaged='0' WHERE card_id='$card_id'");

        $card = $this->cards->getCard($card_id);
        $notifargs['card'] = $card;

        self::notifyAllPlayers('repairWorld', $log, $notifargs);

        // Restore the score
        $score_delta = $this->card_types[ $card['type'] ]['vp'];
        $pscore = $this->updatePlayerScore($player_id, $score_delta, false);
        self::notifyAllPlayers('updateScore', '',
                                array(
                                    "player_id" => $player_id,
                                    "score" => $pscore['score'],
                                    "vp" => 0,
                                    "score_delta" => $score_delta,
                                    "vp_delta" => 0
                               ));

        // Refresh the military
        $this->updateMilforceIfNeeded($player_id);
        self::notifyAllPlayers('updateSpecializedMilitary', '', $this->getSpecializedMilitary());

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

            self::notifyAllPlayers('goodproduction', '', array(
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
            self::notifyPlayer($player_id, 'updateWindfallPowers', '', $windfallPossibilities);
        }

        if (!$this->hasProduceActions($player_id)) {
            $this->gamestate->setPlayerNonMultiactive($player_id, 'phaseCleared');
        }
    }

    function drawForEachWorld($card_id)
    {
        $card = $this->cards->getCard($card_id);
        $player_id = $card['location_arg'];
        $cards = $this->cards->getCardsInLocation('tableau', $player_id);
        $to_draw = 0;
        if ($card['type'] == 30) {
            $world_type = 3;
        } elseif ($card['type'] == 310) {
            $world_type = 2;
        } elseif ($card['type'] == 119 || $card['type'] == 308) {
            $world_type = 'military';
        } elseif ($card['type'] == 311) {
            $world_type = 'xeno'; // Luckily, all xeno worlds are military
        }


        // Scan cards to find matching worlds
        foreach ($cards as $c) {
            $card_type = $this->card_types[ $c['type'] ];

            if (is_int($world_type)) {
                // If the player has Oort, we assume they change it, draw their cards and change
                // it back to whatever it was. It's a no-brainer, there is no downside to it.
                // Forcing the change to Genes and letting the player change it back might loose them the Largest Industry goal
                // on the next settle, especially since there is no visual feedback of Oort kind if it doesn't have a good
                if ($card_type['name'] == 'Alien Oort Cloud Refinery') {
                    ++ $to_draw;
                } elseif ($this->getCardColorFromType($card_type) == $world_type) {
                    ++ $to_draw;
                }
            } elseif (in_array($world_type, $card_type['category'])) {
                ++ $to_draw;
            }
        }

        if ($card['type'] == 308) {
            $to_draw = floor($to_draw / 2);
        }

        if ($to_draw > 0) {
            $this->drawCardForPlayer($player_id, $to_draw, false, $card['type']);
            $this->disableCard($card_id);
        }
    }

    function drawForEachGood($card_id)
    {
        $card = $this->cards->getCard($card_id);
        $player_id = $card['location_arg'];
        $card_type = $this->card_types[ $card['type'] ];
        $power = $card_type['powers'][5][0];
        $to_draw = 0;

        switch ($power['power']) {
            case 'drawforeach':
                $good_type = $power['arg']['resource'];
                $to_draw = self::getUniqueValueFromDB("
                    SELECT COUNT(*)
                    FROM player_production
                    WHERE pp_player_id=$player_id AND pp_good_id=$good_type");
                break;

            case 'drawforeachtwo':
                $to_draw = self::getUniqueValueFromDB("
                    SELECT COUNT(*)
                    FROM player_production
                    WHERE pp_player_id=$player_id");
                $to_draw = floor($to_draw / 2);
                break;

            case 'drawforeachgoodtype':
                $to_draw = self::getUniqueValueFromDB("
                    SELECT COUNT(DISTINCT pp_good_id)
                    FROM player_production
                    WHERE pp_player_id=$player_id");
                break;
        }

        if ($to_draw > 0) {
            $this->drawCardForPlayer($player_id, $to_draw, false, $card['type']);
            $this->disableCard($card_id);
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    function argPhaseChoice()
    {
        // With preset hands, we can reach this state before the player has joined the table
        try {
            $phasechoices = $this->getPhaseChoices(self::getCurrentPlayerId());
            $bCrystalPlayer = (self::getCurrentPlayerId() == $this->getPsyCrystalPlayer());
        } catch (Exception $e) {
            $phasechoices = $this->getPhaseChoices();
            $bCrystalPlayer = false;
        }

        return array(
            'searchavail' => self::getCollectionFromDB("SELECT player_id, 1 FROM player WHERE player_search > 0 AND player_tmp_milforce = 0 AND player_tmp_gene_force = 0", true),
            'hasprestige' => self::getCollectionFromDB("SELECT player_id, player_prestige FROM player WHERE player_prestige > 0", true),
            'phasechoices' => $phasechoices,
            'crystalplayer' => $bCrystalPlayer
       );
    }

    function argOrbAction()
    {
        $player_id = self::getActivePlayerId();
        return array(
            'orbactionnbr' => self::getGameStateValue('orbactionnbr'),
            'teams' => self::getObjectListFromDB("SELECT team_id FROM orbteam WHERE team_player=$player_id", true)
       );
    }

    function argOrbActionMove()
    {
        $movingPossibilities = $this->getPossibleMoves();

        return array(
            'orbactionnbr' => self::getGameStateValue('orbactionnbr'),
            'orbmoves' => $this->getTeamMove(self::getGameStateValue('orbteam')),
            'team' => self::getGameStateValue('orbteam'),
            'moves' => array_keys($movingPossibilities)
       );
    }

    function argBreedingTube()
    {
        $player_id = self::getActivePlayerId();
        return [
            'latest' => self::getUniqueValueFromDB("SELECT player_previously_played FROM player WHERE player_id=$player_id"),
            'orb_card_under' => $this->getCardUnderCurrentTeam()
        ];
    }

    function argExplore()
    {
        return $this->getExploredCardNumber();
    }

    function argDevelopDiscard()
    {
        return $this->getDevelopDiscard();
    }

    function argDiscardToPutGood()
    {
        $world_type_id = self::getUniqueValueFromDB(
            "SELECT card_type FROM card
             JOIN player ON player_just_played=card_id
             WHERE card_type IN ('244', '286')
             AND card_location='tableau'");

        if (is_null($world_type_id)) {
            return  array('world' => '');
        }

        return array(
            'world' =>  $this->card_types[ $world_type_id ]['name']
       );
    }

    function argSettleDiscard()
    {
        return $this->getSettleDiscard();
    }

    function argsTakeover()
    {
        $takeover = $this->getCurrentTakeOverSituation();

        $tmp_defense = $this->remainingTempMilitary($takeover['defender']);
        if ($tmp_defense > 0) {
            $takeover['cost'] .= sprintf(self::_(", max: %s"), $takeover['cost'] + $tmp_defense);
        }

        // Don't show attacker temp military for defense boost phase as the attacker can no longer use it at this point
        $state = $this->gamestate->state();
        if ($state['name'] == 'takeover_attackerboost') {
            $tmp_attack = $this->remainingTempMilitary($takeover['player_id']);
            if ($tmp_attack > 0) {
                $takeover['attacker_force'] .= sprintf(self::_(", max: %s"), $takeover['attacker_force'] + $tmp_attack);
            }
        }
        return $takeover;
    }

    function argConsumeSell()
    {
        $res = [];
        // Get all goods and the world they are on
        $sql = "SELECT good.card_id,
            good.card_status as good_type,
            world.card_id as world_id,
            world.card_location_arg as player_id
        FROM card good
        INNER JOIN card world ON world.card_id=good.card_location_arg
        WHERE good.card_location='good' AND world.card_type NOT IN (220, 246)";

        // Get the sell price of each good
        foreach ($this->getCollectionFromDB($sql) as $good_id  => $row) {
            $res[$good_id] = $this->getSellPrice($row['player_id'], $row['good_type'], $row['world_id']);
        }
        return $res;
    }
    function argConsume()
    {
        // Ge the player with Casino cards not consumed
        $player_with_casino = self::getCollectionFromDB("SELECT card_location_arg, card_location_arg
                     FROM card
                     INNER JOIN player ON player_id=card_location_arg
                     WHERE card_type IN ('56','110')
                     AND card_location='tableau'
                     AND (card_id!=player_previously_played OR player_previously_played IS NULL)", true);

        return array(

            'gambling' => $player_with_casino,
            'prestigeTrade' => $this->playersThatMayUsePrestigeTradeBonus()

       );
    }

    function argProductionWindfall()
    {
        $res = $this->windfallproduction_state();
        foreach ($res as $player_id => $powers) {
            $usablePowers = $this->usableProducePowers($player_id, $powers);
            $res[$player_id]['title'] = $this->getProduceTitle($player_id, $usablePowers);
        }
        return $res;
    }

    function argEndRoundDiscard()
    {
        return $this->getEndRoundDiscardNumber();
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

//////////////////////////////////////////////////////////////////////////////
//////////// Game state reactions   (reactions to game planned states from state machine
////////////

    function stInitialDiscardHome()
    {
        // Everybody is playing
        $this->gamestate->setAllPlayersMultiactive();
        $this->setBeginnersNonMultiactive();
    }

    function stShowTableau()
    {
        if ($this->cards->countCardsInLocation('hiddentableau') > 0) {
            $this->cards->moveAllCardsInLocationKeepOrder('hiddentableau', 'tableau');
            $this->initStartWorlds();
        }

        // Initialize tableau order
        $tableau = $this->cards->getCardsInLocation('tableau');
        foreach ($tableau as $start_world) {
            self::DbQuery("INSERT INTO tableau_order VALUES (".$start_world['id'].")");
        }

        // Initialize goal progress
        $this->checkGoals(3);

        $this->gamestate->nextState('phaseCleared');
    }

    function stInitialDiscard()
    {
        $this->initStartWorlds();

        if (self::getGameStateValue('presetHands') == 1) {
            $this->gamestate->nextState("phaseCleared");
            return;
        }

        // Everybody is playing
        $this->gamestate->setAllPlayersMultiactive();
        $this->setBeginnersNonMultiactive();
    }

    // Beginners with preset hands don't need to discard
    function setBeginnersNonMultiactive()
    {
        if (self::getGameStateValue('presetHands') != 2) {
            return;
        }
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            if ($player['player_beginner']) {
                $this->gamestate->setPlayerNonMultiactive($player_id, 'phaseCleared');
            }
        }
    }

    function initStartWorlds()
    {
        // Start world initial score, military and good

        $sql = "SELECT card_id, card_type, card_location_arg FROM card WHERE card_location='tableau' ";
        $start_worlds = self::getObjectListFromDB($sql);
        foreach ($start_worlds as $start_world) {
            $card_type = $this->card_types[ $start_world['card_type'] ];
            $player_id = $start_world['card_location_arg'];

            $startworld_number = $card_type['startworld_number'];
            $sql = "UPDATE player SET player_startworld='$startworld_number' WHERE player_id=$player_id";
            self::DbQuery($sql);

            if ($startworld_number >= 5) {
                $expansion = self::getGameStateValue('expansion');
                if ($expansion == 5 || $expansion == 6) {
                    $startworld_number += 100;
                } else if ($expansion == 7 || $expansion == 8) {
                    $startworld_number += 200;
                }
            }
            self::initStat('player', 'start_world', $startworld_number, $player_id);

            // Initial good on windfall world
            if (in_array('windfall', $card_type['category'])) {
                $this->windfallInitialProduction($start_world['card_id'], $card_type, true);
            }

            // Initial score
            $pscore = $this->updatePlayerScore($player_id, $card_type['vp'], false);

            self::notifyAllPlayers( 'updateScore', '',
                                    array(
                                        "player_id" => $player_id,
                                        "score" => $pscore['score'],
                                        "vp" => $pscore['vp'],
                                        "score_delta" =>$card_type['vp'],
                                        "vp_delta" => 0
                                   ) );

            // Initial military
            $this->updateMilforceIfNeeded($player_id, false);
            self::notifyAllPlayers('updateSpecializedMilitary', '', $this->getSpecializedMilitary(true));
        }

        $tableau = $this->cards->getCardsInLocation('tableau');
        self::notifyAllPlayers('showTableau', '', array('cards' => $tableau));

        $this->send_defered_notif($this->notif_defered_id);
    }

    function stSetupFinished()
    {
        $players = self::loadPlayersBasicInfos();

        // RvI 2P Takeover scenario. Players have discarded, now is the time to give them their 6 dev takeover card.
        if (self::getGameStateValue('takeover') == 3) {
            $tableau = $this->cards->getCardsInLocation('tableau');
            foreach ($tableau as $card) {
                if ($card['type'] == 131) { // Rebel Cantina
                    $this->drawCard('Rebel Alliance', $card['location_arg']);
                } elseif ($card['type'] == 133) { // Imperium Warlord'
                    $this->drawCard('Imperium Seat', $card['location_arg']);
                }
            }
            $this->gamestate->nextState("phaseCleared");
            return;
        }

        // AA Orb Game
        if (self::getGameStateValue('expansion') == 5) {
            // Then, distribute 3 "a" to each player
            foreach ($players as $player_id => $player) {
                $cards = $this->orbcards->pickCards(3, 'deck', $player_id);

                self::notifyPlayer($player_id, 'pickOrbCards', '', array('cards' => $cards));
            }
            $deck = self::getCollectionFromDB("SELECT card_type_arg, COUNT(*) FROM orbcard WHERE card_location='deck' GROUP BY card_type_arg", true);
            self::notifyAllPlayers('updateOrbCardCount', '', ['deck' => $deck]);

            // Priority

            $priority = 1;
            $player_to_priority = array();
            $last_player = null;
            foreach ($this->getTurnOrder() as $player_id) {
                $player_to_priority[ $player_id ] = $priority;
                self::DbQuery("UPDATE player SET player_orb_priority='$priority' WHERE player_id='$player_id'");
                $priority ++;

                $last_player = $player_id;
            }

            self::notifyAllPlayers('changeOrbPriority', '', array('priority' => $player_to_priority));

            $this->gamestate->changeActivePlayer($last_player);
            $this->gamestate->nextState("initialOrb");
            return;
        }

        // Ancient Race
        $player_with_ancient_race =
            self::getUniqueValueFromDB("SELECT card_location_arg
                     FROM card
                     WHERE card_type='107'
                     AND card_location='tableau'", true);

        if (! is_null($player_with_ancient_race)
                && $this->cards->countCardsInLocation('hand', $player_with_ancient_race) > 3) {
            $this->gamestate->changeActivePlayer($player_with_ancient_race);
            $this->gamestate->nextState("initialDiscardAncientRace");
            return;
        }

        // Galactic Scavengers
        $player_with_scavenger = self::getUniqueValueFromDB("SELECT card_location_arg
                     FROM card
                     INNER JOIN player ON player_id=card_location_arg
                     WHERE card_type='181'
                     AND card_location='tableau'", true);

        if (! is_null($player_with_scavenger)
                && $this->cards->countCardsInLocation('hand', $player_with_scavenger) > 3) {
            $this->gamestate->changeActivePlayer($player_with_scavenger);
            $this->gamestate->nextState("initialDiscardScavenger");
            return;
        }

        $this->gamestate->nextState("phaseCleared");
    }

    function getPsyCrystalPlayer()
    {
        return self::getUniqueValueFromDB("SELECT card_location_arg FROM card
                     WHERE card_location='tableau' AND card_type='211'");
    }

    function stPhaseChoice()
    {
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            self::giveExtraTime($player_id);
        }

        // Everybody is playing
        $this->gamestate->setAllPlayersMultiactive();

        self::incStat(1, 'turn_number');
        self::incGameStateValue('current_round', 1);
        self::setGameStateValue('current_phase', 0);
        self::setGameStateValue('current_subphase', 0);

        $crystal_player = $this->getPsyCrystalPlayer();
        if ($crystal_player !== null && !$this->is_twoplayers()) {
            $this->gamestate->setPlayerNonMultiactive($crystal_player, "phaseCleared");   // Crystal player has nothing to do here
        }
    }

    function stPhaseChoiceSignal()
    {
        // If anyone used prestige during phase choice, now is the time to notify
        $this->send_defered_notif($this->notif_defered_id);

        // Notify phase choices to all players
        self::notifyAllPlayers('phase_choices', '', $this->getPhaseChoices());

        $crystal_player = $this->getPsyCrystalPlayer();
        if ($crystal_player !== null && !self::isCurrentPlayerZombie()) {
            $phases_to_choose = $this->is_twoplayers() ? 2 : 1;
            if (self::getUniqueValueFromDB("SELECT COUNT(phase_id) FROM phase WHERE phase_player='$crystal_player'") < $phases_to_choose) {
                // A player still have 1 phase to choose
                $this->gamestate->changeActivePlayer($crystal_player);
                $this->gamestate->nextState('crystal');  // Normal case
            } else {
                $this->gamestate->nextState('startRound');  // Normal case
            }
        } else {
            $this->gamestate->nextState('startRound');  // Normal case
        }
    }

    function stSearchActionCheck()
    {
        // If any player has used a prestige action, now is the time to spend prestige
        $sql = "SELECT player_id, player_tmp_milforce FROM player WHERE player_tmp_milforce > 0";
        $players_used_prestige = $this->getCollectionFromDB($sql);
        foreach (array_keys($players_used_prestige) as $player) {
            $this->givePrestige($player, -1, false);
            self::DbQuery("UPDATE player SET player_search=0 WHERE player_id=$player");
        }
        self::DbQuery("UPDATE player SET player_tmp_milforce=0");
        self::setGameStateValue('improvedLogisticsPhase', 0); // Used to store the first picked card

        $player_prestige_search = self::getCollectionFromDB("SELECT player_id, player_search FROM player", true);
        self::notifyAllPlayers('prestige_search', '', $player_prestige_search);

        $player_phases = $this->getPhaseChoice(7);
        if (count($player_phases) == 0) {
            $this->gamestate->nextState('no_more_actions');
            return;
        }

        $sql = "UPDATE player SET player_search=0, player_tmp_gene_force=0 WHERE player_id IN (";
        $sql .= implode(',', array_keys($player_phases));
        $sql .= ")";
        self::DbQuery($sql);
        $player_prestige_search = self::getCollectionFromDB("SELECT player_id, player_search FROM player", true);
        self::notifyAllPlayers('prestige_search', '', $player_prestige_search);

        // Search is done by turn order
        foreach ($this->getTurnOrder() as $player) {
            if (array_key_exists($player, $player_phases)) {
                $this->gamestate->changeActivePlayer($player);
                $this->gamestate->nextState('search_action');
                return;
            }
        }
    }

    function stExplore()
    {
        $player_phases = $this->getPhaseChoice(1);
        if (count($player_phases) == 0) {
            $this->gamestate->nextState('phaseNotSelected');
            return;
        }

        self::incStat(1, 'phase_played');
        self::incStat(1, 'phase_explore');

        // Every players gets 2 cards in its "explored" zone, with bonuses from phase choice or cards powers
        $explored_card_number = $this->getExploredCardNumber();
        $players = self::loadPlayersBasicInfos();

        foreach ($this->getTurnOrder() as $player_id) {
            $card_numbers = $explored_card_number[$player_id];
            if (isset($card_numbers['mix']) || $card_numbers['keep'] >= $card_numbers['draw']) {
                // Explore mix : the player pick cards directly into his hand
                $cards = $this->cards->pickCardsForLocation($card_numbers['draw'], $this->getDeck($player_id), 'hand', $player_id);
                self::notifyPlayer($player_id, 'drawCards', '', $cards);
                $this->notifyUpdateCardCount();
            } else {
                // Standard case
                $cards = $this->cards->pickCardsForLocation($card_numbers['draw'], $this->getDeck($player_id), 'explored', $player_id);
                self::NotifyPlayer($player_id, "explored_choice", '', $cards);
            }
            self::notifyAllPlayers('explored_choice_log', clienttranslate('${player_name} draws ${nbr} cards'), array(
                    'player_name' => $players[ $player_id ]['player_name'],
                    'player_id' => $player_id,
                    'nbr' => $card_numbers['draw']));
        }

        $this->gamestate->setAllPlayersMultiactive();
        $this->notifyUpdateCardCount();

        // Inactive players that have "keep >= draw"
        foreach ($explored_card_number as $player_id => $number) {
            if ($number['keep'] >= $number['draw']) {
                $this->gamestate->setPlayerNonMultiactive($player_id, 'phaseCleared');
            }
        }
    }

    function stPostExploreProcess()
    {
        $this->checkGoals(1);
        $this->gamestate->nextState('');
    }

    function stDevelop()
    {

        $player_phases = $this->getPhaseChoice(2);
        if (count($player_phases) == 0) {
            $this->gamestate->nextState('phaseNotSelected');
            return;
        }

        $sql = "UPDATE player SET player_just_played=NULL, player_tmp_milforce='0',player_tmp_gene_force='0',player_tmp_xenoforce='0' WHERE 1 "; // Note : we are using player_tmp_milforce
        self::DbQuery($sql);
        $this->resetCardStatus();


        self::incStat(1, 'phase_played');
        self::incStat(1, 'phase_develop');
        $this->drawOnPhase(2);
        if (self::getGameStateValue('current_phase') >= 20) {
            self::incGameStateValue('current_phase', 1);
        } else {
            self::setGameStateValue('current_phase', 20);
        }
        self::setGameStateValue('current_subphase', 1);

//        $this->gamestate->setAllPlayersMultiactive();
        $this->gamestate->nextState('developdiscard');
    }

    function getSettleDiscard()
    {
        $player_to_discard = array();
        $draw_powers = $this->scanTableau( 3, null, 'drawifsettle');
        $player_to_lastplayed = self::getCollectionFromDB("SELECT player_id, player_just_played FROM player WHERE 1", true);
        foreach ($draw_powers as $player_id => $drawpowers) {
            foreach ($drawpowers as $drawpower) {
                if (isset($drawpower['arg']['thendiscard'])) {
                    if (isset($player_to_lastplayed[$player_id]) && $player_to_lastplayed[$player_id]==$drawpower['card_id']) {
                        // We just played this card, so it has no effect until next round
                    } else {
                        if (! isset($player_to_discard[ $player_id ])) {
                            $player_to_discard[ $player_id ] = 0;
                        }
                        $player_to_discard[ $player_id ] += $drawpower['arg']['thendiscard'];
                    }
                }
            }
        }


        foreach ($player_to_discard as $player_id => $to_discard) {
            if ($player_to_lastplayed[ $player_id ] === null) {
                unset($player_to_discard[ $player_id ]); // There is nothing to discard because no world has been settled!
            }
        }

        return $player_to_discard;
    }

    function getDevelopDiscard()
    {
        $player_to_discard = array();
        $draw_powers = $this->scanTableau( 2, null, 'draw');
        foreach ($draw_powers as $player_id => $drawpowers) {
            foreach ($drawpowers as $drawpower) {
                if (isset($drawpower['arg']['thendiscard'])) {
                    if (! isset($player_to_discard[ $player_id ])) {
                        $player_to_discard[ $player_id ] = 0;
                    }
                    $player_to_discard[ $player_id ] += $drawpower['arg']['thendiscard'];
                }
            }
        }

        return $player_to_discard;
    }

    function stSettleDiscard()
    {
        // Card draws for settle bonus must occur
        // after Ore-Rich World discard to put a good on it
        // but before Imperium Fuel Depot discard after settle
        $players = array_keys(self::loadPlayersBasicInfos());
        foreach ($players as $player_id) {
            $this->phaseBonus($player_id);
        }

        $player_to_discard = $this->getSettleDiscard();
        $this->send_defered_notif($this->notif_defered_id);
        $this->gamestate->setPlayersMultiactive(array_keys($player_to_discard), 'done');
    }

    function stDiscardToPutGood()
    {
        $world = self::getObjectFromDB("SELECT card_id, card_location_arg FROM card
            INNER JOIN player ON player_just_played=card_id
        WHERE card_type IN ('244', '286') AND card_location='tableau'");

        if ($world !== null) {
            // There are 2 worlds with the discardtoputgood power, but they are in different
            // extensions (AA and XI). So only one player can trigger this phase.
            $this->gamestate->setPlayersMultiactive(array($world['card_location_arg']), 'done');
        } else {
            $this->gamestate->nextState('done');
        }
    }

    function stDevelopDiscard()
    {
        $player_to_discard = $this->getDevelopDiscard();
        $this->send_defered_notif($this->notif_defered_id);
        $this->gamestate->setPlayersMultiactive(array_keys($player_to_discard), 'done');
    }

    function stDevelopNewActive()
    {
        $this->gamestate->setAllPlayersMultiactive();
    }

    function stDevelopProcess()
    {
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $this->phaseBonus($player_id);
        }

        $sql = "UPDATE player SET player_just_played=NULL WHERE 1 ";
        self::DbQuery($sql);

        $this->resetCardStatus();

        // Notify all players about cards played by the others
        $this->send_defered_notif($this->notif_defered_id);
        $this->notifyUpdateCardCount();
        self::notifyAllPlayers('updateSpecializedMilitary', '', $this->getSpecializedMilitary());

        $this->checkGoals(2);

        if ($this->is_twoplayers()) {
            // See if there is a need to repeat this phase again
            if (self::getGameStateValue('repeatPhase') == 0) {
                $bNeedRepeat = false;
                $player_phases = $this->getPhaseChoice(2);
                foreach ($player_phases as $player_id => $bonus) {
                    if ($bonus % 10 == 2) {
                        $bNeedRepeat = true;
                    }
                }

                if ($bNeedRepeat) {
                    self::setGameStateValue('repeatPhase', 1);
                    $this->gamestate->nextState('repeat');
                } else {
                    $this->gamestate->nextState('continue');
                }
            } else {
                // We already repeated this phase => let's continue
                self::setGameStateValue('repeatPhase', 0);
                $this->gamestate->nextState('continue');
            }
        } else {
            $this->gamestate->nextState('continue');
        }
    }

    function stSettle()
    {

        $player_phases = $this->getPhaseChoice(3);
        if (count($player_phases) == 0) {
            $this->gamestate->nextState('phaseNotSelected');
            return;
        }

        if (self::getGameStateValue('improvedLogisticsPhase') == 0) {
            self::incStat(1, 'phase_played');
            self::incStat(1, 'phase_settle');

            if (self::getGameStateValue('current_phase') >= 30) {
                self::incGameStateValue('current_phase', 1);
            } else {
                self::setGameStateValue('current_phase', 30);
            }
            self::setGameStateValue('current_subphase', 1);

            $sql = "UPDATE player SET player_just_played=NULL, player_takeover_target=NULL WHERE 1 ";
            self::DbQuery($sql);

            // Reset temporary military force
            // Note : temporary military is RESET when there are 2 consecutive settle phase in 2 players advanced games (read Tom Lehman answer here : https://boardgamegeek.com/thread/358370/qs-about-interactions-improved-logistics)
            $sql = "UPDATE player SET player_tmp_milforce='0', player_tmp_gene_force='0', player_tmp_xenoforce='0', player_previously_played=NULL WHERE 1 ";
            self::DbQuery($sql);

            $this->resetCardStatus();

            // Normal settle phase
            $this->drawOnPhase(3);

            $this->gamestate->setAllPlayersMultiactive();
        } elseif (self::getGameStateValue('improvedLogisticsPhase') == 1) {
            // Improved logistics phase

            // Note : we DONT set all cards to inactive in this case, because powers cannot be used twice

            // Note : we DO NOT reset temporary military force, because temporary military force that appears during the first settle can be used during the second
            //        settle.
            //        read: https://boardgamegeek.com/thread/1087399/space-mercenaries-improved-logistics

            // Active only players that has really played a world during the previous phase
            self::setGameStateValue('current_subphase', 2);
            $players_with_improved = $this->playersThatMayUseImprovedLogistics();
            $this->gamestate->setPlayersMultiactive($players_with_improved, 'phaseCleared');
        } elseif (self::getGameStateValue('improvedLogisticsPhase') == 2) {
            // Rebel Sneak Attack phase
            self::setGameStateValue('current_subphase', 3);
            $players_with_improved = $this->playersThatMayUseSneakAttack();
            $this->gamestate->setPlayersMultiactive($players_with_improved, 'phaseCleared');
        } elseif (self::getGameStateValue('improvedLogisticsPhase') == 3) {
            // Imperium Supply Convoy
            self::setGameStateValue('current_subphase', 4);
            $players_with_improved = $this->playersThatMayUseImperiumSupplyConvoy();
            $this->gamestate->setPlayersMultiactive($players_with_improved, 'phaseCleared');
        } elseif (self::getGameStateValue('improvedLogisticsPhase') == 4) {
            // Terraforming project
            self::setGameStateValue('current_subphase', 5);
            $players_with_improved = $this->playersThatMayUseTerraformingProject();
            $this->gamestate->setPlayersMultiactive($players_with_improved, 'phaseCleared');
        } elseif (self::getGameStateValue('improvedLogisticsPhase') == 5) {
            // Terraforming engineers
            self::setGameStateValue('current_subphase', 6);
            $players_with_improved = $this->playersThatMayUseTerraformingEngineers();
            $this->gamestate->setPlayersMultiactive($players_with_improved, 'phaseCleared');
        }
    }

    function stSettleProcess()
    {
        // Notify all players about cards played by the others
        $this->send_defered_notif($this->notif_defered_id);
        $this->notifyUpdateCardCount();

        if (self::getGameStateValue('improvedLogisticsPhase') == 0) {
            // Save what we played on the previous phase
            self::DbQuery("UPDATE player SET player_previously_played = player_just_played WHERE 1");

            $sql = "UPDATE player SET player_just_played=NULL WHERE 1 ";
            self::DbQuery($sql);


            // If some players has Improved logistics, they may decide to use it (=> additional Settle phase dedicated to them)
            $players_with_improved = $this->playersThatMayUseImprovedLogistics();
            $players_with_sneak = $this->playersThatMayUseSneakAttack();
            $players_with_convoy = $this->playersThatMayUseImperiumSupplyConvoy();
            $players_with_terraforming_project = $this->playersThatMayUseTerraformingProject();
            $players_with_terraforming_engineers = $this->playersThatMayUseTerraformingEngineers();

            if (count($players_with_improved) > 0) {
                // Okay, let's go for an improved Logistics settle phase
                self::setGameStateValue('improvedLogisticsPhase', 1);
                $this->gamestate->nextState('improvedlogistics');
                return ;
            }
            if (count($players_with_sneak) > 0) {
                // Okay, let's go for an sneak settle phase
                self::setGameStateValue('improvedLogisticsPhase', 2);
                $this->gamestate->nextState('sneakAttack');
                return ;
            }
            if (count($players_with_terraforming_engineers) > 0) {
                self::setGameStateValue('improvedLogisticsPhase', 5);
                $this->gamestate->nextState('terraformingEngineers');
                return ;
            }

            if (count($players_with_terraforming_project) > 0) {
                // Okay, let's go for an convoy settle phase
                self::setGameStateValue('improvedLogisticsPhase', 4);
                $this->gamestate->nextState('terraforming_project');
                return ;
            }
            if (count($players_with_convoy) > 0) {
                // Okay, let's go for an convoy settle phase
                self::setGameStateValue('improvedLogisticsPhase', 3);
                $this->gamestate->nextState('convoy');
                return ;
            }
        } elseif (self::getGameStateValue('improvedLogisticsPhase') == 1) {
            $players_with_sneak = $this->playersThatMayUseSneakAttack();
            $players_with_terraforming_engineers = $this->playersThatMayUseTerraformingEngineers();

            if (count($players_with_sneak) > 0) {
                // Okay, let's go for an sneak settle phase
                self::setGameStateValue('improvedLogisticsPhase', 2);
                $this->gamestate->nextState('sneakAttack');
                return ;
            } elseif (count($players_with_terraforming_engineers) > 0) {
                self::setGameStateValue('improvedLogisticsPhase', 5);
                $this->gamestate->nextState('terraformingEngineers');
                return ;
            } else {
                self::setGameStateValue('improvedLogisticsPhase', 0);
            }
        } elseif (self::getGameStateValue('improvedLogisticsPhase') == 2) {
            $players_with_terraforming_engineers = $this->playersThatMayUseTerraformingEngineers();

            if (count($players_with_terraforming_engineers) > 0) {
                self::setGameStateValue('improvedLogisticsPhase', 5);
                $this->gamestate->nextState('terraformingEngineers');
                return ;
            } else {
                self::setGameStateValue('improvedLogisticsPhase', 0);
            }
        } elseif (self::getGameStateValue('improvedLogisticsPhase') == 4) {
            $players_with_convoy = $this->playersThatMayUseImperiumSupplyConvoy();

            if (count($players_with_convoy) > 0) {
                // Okay, let's go for an convoy settle phase
                self::setGameStateValue('improvedLogisticsPhase', 3);
                $this->gamestate->nextState('convoy');
                return ;
            } else {
                self::setGameStateValue('improvedLogisticsPhase', 0);
            }
        } else {
            // We just finished an improved logistics / sneak phase => reset to normal, and continue as if nothing happens
            self::setGameStateValue('improvedLogisticsPhase', 0);
        }

        // End of the settle phase
        // Refresh military, clear temporary military, check goals conditions and clear played markers
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $this->updateMilforceIfNeeded($player_id);
        }
        self::DbQuery("UPDATE player SET player_tmp_milforce=0, player_tmp_gene_force=0");
        self::notifyAllPlayers('clearTmpMilforce', '', null);
        $this->checkGoals(3);
        self::DbQuery("UPDATE player SET player_just_played=NULL, player_previously_played=NULL");
        $this->moveJustDiscardedToDiscard();
        self::notifyAllPlayers('updateSpecializedMilitary', '', $this->getSpecializedMilitary());

        if ($this->is_twoplayers()) {
            // See if there is a need to repeat this phase again
            if (self::getGameStateValue('repeatPhase') == 0) {
                $bNeedRepeat = false;
                $player_phases = $this->getPhaseChoice(3);
                foreach ($player_phases as $player_id => $bonus) {
                    if ($bonus % 10 == 2) {
                        $bNeedRepeat = true;
                    }
                }

                if ($bNeedRepeat) {
                    self::setGameStateValue('repeatPhase', 1);
                    $this->gamestate->nextState('repeat');
                } else {
                    $this->gamestate->nextState('continue');
                }
            } else {
                // We already repeated this phase => let's continue
                self::setGameStateValue('repeatPhase', 0);
                $this->gamestate->nextState('continue');
            }
        } else {
            $this->gamestate->nextState('continue');
        }
    }

    function getTakeOverAttack($attacker, $takeovercard, $target)
    {
        $target_type=$this->card_types[$target['type']];
        $milforce = $this->getMilitaryForceForWorld($attacker, $target_type, false);
        $attack = $milforce['force'];

        // Rebel Sneak Attack and Rebel Alliance
        if ($takeovercard['type']== 191 || $takeovercard['type']== 149) {
            // +2 per rebel military world in attacker tableau
            $cards = $this->cards->getCardsInLocation('tableau', $attacker);
            foreach ($cards as $card) {
                if (in_array('rebel', $this->card_types[$card['type']]['category'])
                    && in_array('military', $this->card_types[$card['type']]['category'])) {
                    $attack += 2;
                }
            }
        }
        return $attack;
    }

    function getTakeOverDefense($defender, $target)
    {
        $target_type=$this->card_types[$target['type']];
        $milforce= $this->getMilitaryForceForWorld($defender, $target_type, false);
        $defense = $milforce['force'];


        // If the target is a civil world attacked by IIF, it is considered as a military world
        // for the purpose of this takeover. See: https://boardgamegeek.com/article/8157852#8157852
        if (count($this->scanTableau(3, $defender, 'militaryforce_permilitary')) > 0
             && !in_array('military', $target_type['category'])) {
            ++$defense;
        }

        // Defense power (Rebel Pact)
        if (count($this->scanTableau(3, $defender, 'defense')) > 0) {
            $cards = $this->cards->getCardsInLocation('tableau', $defender);
            $played = self::getObjectFromDB("SELECT player_just_played, player_previously_played FROM player WHERE player_id=$defender");

            foreach ($cards as $card) {
                // Skip cards played in this phase
                if (in_array($card['id'], $played)) {
                    continue;
                }

                if (in_array('military', $this->card_types[$card['type']]['category'])
                     || $card['id'] == $target['id']) { // Special case for civil world attacked by IIF, see above
                    ++$defense; // +1 for military world
                    if (in_array('rebel', $this->card_types[$card['type']]['category'])) {
                        ++$defense; // +2 for Rebel military world
                    }
                }
            }
        }

        return $defense + $target_type['cost'];
    }

    function getCurrentTakeOverSituation()
    {
        $takeover = self::getObjectFromDB("SELECT player_id, player_just_played, player_takeover_target
                                FROM player
                                WHERE player_takeover_target IS NOT NULL
                                ORDER BY FIELD (player_id,". implode(",", $this->getTurnOrder()) .")".
                                "LIMIT 0,1");

        if ($takeover === null) {
            return null;
        }

        $target = $this->cards->getCard($takeover['player_takeover_target']);
        $takeovercard = $this->cards->getCard($takeover['player_just_played']);
        $target_type=$this->card_types[$target['type']];

        $takeover['i18n'] = ['target_world', 'takeovercard_name'];
        $takeover['defender'] = $target['location_arg'];
        $takeover['target_type'] = $target_type;
        $takeover['target_world'] = $target_type['name'];
        $takeover['takeovercard'] = $this->card_types[ $takeovercard['type'] ];
        $takeover['takeovercard_name'] = $this->card_types[ $takeovercard['type'] ]['name'];
        $takeover['attacker_force'] = $this->getTakeOverAttack($takeover['player_id'], $takeovercard, $target);
        $takeover['cost'] = $this->getTakeOverDefense($target['location_arg'], $target);

        return $takeover;
    }

    function stAfterDevelopCheck()
    {
        $this->moveJustDiscardedToDiscard();

        $expansion = self::getGameStateValue('expansion');

        if ($expansion == 5) {
            // Check if some player played an Alien Research Ship (+ 1 airlock)
            $player_card = self::getObjectFromDB("SELECT player_id, card_id FROM player
                    INNER JOIN card ON card_id=player_just_played
                    WHERE card_type=257 AND card_status=-1
                    LIMIT 1");

            if ($player_card === null) {
                $this->gamestate->nextState('no_more_check');
            } else {
                $player_id = $player_card['player_id'];
                $card_id = $player_card['card_id'];

                // Mark this card so we do not loop
                self::DbQuery("UPDATE card SET card_status=0 WHERE card_id=$card_id");

                $this->gamestate->changeActivePlayer($player_id);

                // Give him a Sas card
                $card = $this->orbcards->pickCardForLocation('sas2', 'hand', $player_id);
                self::notifyPlayer($player_id, 'pickOrbCards', '', array(
                    'cards' => array($card)
                   ));

                $this->gamestate->nextState('orb_airlock');
            }
        } else {
            $this->gamestate->nextState('no_more_check');
        }
    }

    function stSettleTakeoverCheck()
    {
        // Notify all players about cards played by the others
        $this->send_defered_notif($this->notif_defered_id);

        // Check if there is a takeover in progress ...

        $takeOver = $this->getCurrentTakeOverSituation();

        if ($takeOver === null) {
            // No more takeovers !
            $this->gamestate->nextState("no_more_takeover");
            return ;
        } else {
            // We must resolve this takeover ...

            // ... active the attacker first
            $this->gamestate->changeActivePlayer($takeOver['player_id']);
            $this->gamestate->nextState("resolve_takeover");
        }
    }

    function stPanGalacticSecurityCouncil()
    {
        $player_with_securitycouncil = self::getUniqueValueFromDB("SELECT card_location_arg
                     FROM card
                     INNER JOIN player ON player_id=card_location_arg
                     WHERE card_type='185'
                     AND card_location='tableau'
                     AND card_status='0'
                     AND (card_id!=player_previously_played OR player_previously_played IS NULL)
                     AND (card_id!=player_just_played OR player_just_played IS NULL)", true);

        if ($player_with_securitycouncil === null) {
            $this->gamestate->nextState('continue');   // No security council
        } else {
            $this->send_defered_notif($this->notif_defered_id);
            $this->gamestate->setPlayersMultiactive(array($player_with_securitycouncil), 'continue');
        }
    }

    function stTakeOverMilitaryBoost()
    {
        // If player has some available and affordable temporary military powers, he can use them ...

        $powers = $this->scanTableau(3, self::getActivePlayerId(), null, true);
        $bAtLeastOneBoost = false;

        foreach ($powers as $power) {
            if ($this->usableMilitaryBoost(self::getActivePlayerId(), $power)) {
                $bAtLeastOneBoost = true;
                break;
            }
        }

        if ($bAtLeastOneBoost) {
            // Okay, stay at this step
        } else {
            // Jump to next
            $this->gamestate->nextState('done');
        }
    }

    function stSettleTakeOverNextBoost()
    {
        // active defender
        $takeover = $this->getCurrentTakeOverSituation();

        $this->gamestate->changeActivePlayer($takeover[ 'defender' ]);
        $this->gamestate->nextState('');
    }

    function noMoreBoost()
    {
        $this->gamestate->nextState('done');
    }

    function stSettleTakeoverResolution()
    {
        // Resolution of this takeover

        $players = self::loadPlayersBasicInfos();
        $takeover = $this->getCurrentTakeOverSituation();

        if ($takeover['cost'] <= $takeover['attacker_force']) {
            // Takeover success!

            // Move this card to player tableau
            if ($takeover['takeovercard']['name'] != "Imperium Planet Buster") {
                $this->cards->moveCard($takeover['player_takeover_target'], 'tableau', $takeover['player_id']);
                self::DbQuery("DELETE FROM tableau_order WHERE card_id=".$takeover['player_takeover_target']);
                self::DbQuery("INSERT INTO tableau_order VALUES (".$takeover['player_takeover_target'].")");
                // Disable the card so its power doesn't trigger in this phase
                self::DbQuery("UPDATE card SET card_status=-1 WHERE card_id=".$takeover['player_takeover_target']);
            }

            // Get the good on the world if any
            $sql = "SELECT card_id, card_status FROM card "
                  ."WHERE card_location = 'good' AND card_location_arg=".$takeover['player_takeover_target'];

            $good = self::getObjectFromDB($sql);

            // Succeeded takeover
            self::notifyAllPlayers("takeover", clienttranslate('${player_name} uses ${takeovercard_name} to takeover ${world} with a force of ${force} (defense : ${cost})'), array(
                'i18n' => ['takeovercard_name', 'world'],
                'player_name' => $players[ $takeover['player_id'] ]['player_name'],
                'takeovercard_name' => $takeover['takeovercard_name'],
                'world' => $takeover['target_world'],
                'force' => $takeover['attacker_force'],
                'cost'=> $takeover['cost'],
                'card_id' => $takeover['player_takeover_target'],
                'from' => $takeover['defender'],
                'to' => $takeover['player_id'],
                'card' => $this->cards->getCard($takeover['player_takeover_target']),
                'good_id' => $good == null ? null : $good['card_id'],
                'good_type' => $good == null ? null : $good['card_status']
           ));

            // If the world taken over is Galactic Scavengers, the scavenged cards move with the world
            if ($takeover['target_world'] == 'Galactic Scavengers' && $takeover['takeovercard']['name'] != "Imperium Planet Buster") {
                $this->cards->moveAllCardsInLocation('scavenger', 'scavenger', null, $takeover['player_id']);
                self::notifyAllPlayers("scavengerUpdate", '', [
                    'count' => $this->cards->countCardInLocation('scavenger'),
                    'player_id' => $takeover['player_id'],
                    'cards' => $this->cards->getCardsInLocation('scavenger')
                ]);
            }

            // Remove this takeover
            self::DbQuery("UPDATE player SET player_just_played='".$takeover['player_takeover_target']."', player_takeover_target=NULL WHERE player_id='".$takeover['player_id']."'");

            // Mark the card as "taken over" (card_status = -2)
            self::DbQuery("UPDATE card SET card_status=-2 WHERE card_id=".$takeover['player_takeover_target']);

            // + remove all other takeovers with the same targets
            $same_target = self::getObjectListFromDB("SELECT player_id FROM player WHERE player_takeover_target='".$takeover['player_takeover_target']."'  ", true);
            foreach ($same_target as $same_target_player) {
                self::notifyAllPlayers("simpleNote", clienttranslate('${player_name} takeover immediately fails cause its target has been taken over by another player'), array('player_name' => $players[ $same_target_player ]['player_name']));
            }
            self::DbQuery("UPDATE player SET player_just_played=NULL,player_takeover_target=NULL WHERE player_takeover_target='".$takeover['player_takeover_target']."'");

            $card = $this->cards->getCard($takeover['player_takeover_target']);

            if ($takeover['takeovercard']['name'] == "Imperium Planet Buster") {
                // DESTROY the target card
                $this->discardFromTableau($takeover['player_takeover_target']);

                $this->defered_notifyAllPlayers($this->notif_defered_id, 'discardfromtableau', clienttranslate('${takeovercard_name} : ${player_name} destroys ${world}'),
                                                array(
                                                    "i18n" => ['takeovercard_name', 'world'],
                                                    "takeovercard_name" => $takeover['takeovercard_name'],
                                                    "player_name" => $players[ $takeover['player_id'] ]['player_name'],
                                                    "player_id" => $takeover['defender'],
                                                    "card" => $takeover['player_takeover_target'],
                                                    "world" => $takeover['target_world']
                                               ));
            } else {
                // Update scores
                $vp = $this->card_types[ $card['type'] ]['vp'];

                // Attacker
                $pscore = $this->updatePlayerScore($takeover['player_id'], $vp, false);
                $this->defered_notifyAllPlayers($this->notif_defered_id, 'updateScore', '',
                        array(
                                "player_id" => $takeover['player_id'],
                                "score" => $pscore['score'],
                                "vp" => $pscore['vp'],
                                "score_delta" => $vp,
                                "vp_delta" => 0
                       ));

                // Defender
                $vp *= -1;
                $pscore = $this->updatePlayerScore($takeover['defender'], $vp, false);
                $this->defered_notifyAllPlayers($this->notif_defered_id, 'updateScore', '',
                        array(
                                "player_id" => $takeover['defender'],
                                "score" => $pscore['score'],
                                "vp" => $pscore['vp'],
                                "score_delta" => $vp,
                                "vp_delta" => 0
                       ));
            }

            $this->notifyUpdateCardCount();

            if ($takeover['takeovercard']['name'] == 'Interstellar Casus Belli' || $takeover['takeovercard']['name'] == "Imperium Planet Buster") {
                // +2 prestige
                $this->givePrestige($takeover['player_id'], 2, false, $takeover['takeovercard']['name']);
            }

            if (! in_array('military', $this->card_types[ $card['type'] ]['category'])) {
                // It's a civil world, meaning IIF was used: +2 prestige
                $this->givePrestige($takeover['player_id'], 2, false, "Imperium Invasion Fleet");
            }
        } else {
            // Takeover failed
            self::notifyAllPlayers("takeOverFailed", clienttranslate('${player_name} failed to takeover ${world} with a force of ${force} (needed force : ${cost})'), array(
                'i18n' => ['world'],
                'player_name' => $players[ $takeover['player_id'] ]['player_name'],
                'world' => $takeover['target_world'],
                'force' => $takeover['attacker_force'],
                'cost'=> $takeover['cost']
           ));

            // Remove this takeover
            self::DbQuery("UPDATE player SET player_just_played=NULL, player_takeover_target=NULL WHERE player_id='".$takeover['player_id']."'");
        }


        if ($takeover['takeovercard']['name'] != 'Imperium Cloaking Technology' && $takeover['takeovercard']['name'] != 'Rebel Sneak Attack') {
            // mark the card as used
            self::DbQuery("UPDATE card set card_status=-1 WHERE card_id=".$takeover['player_just_played']);
        }


        // Continue takeover resolution
        $this->gamestate->nextState("resolved");
    }

    function playersThatMayUseImprovedLogistics()
    {
        return self::getObjectListFromDB("SELECT card_location_arg FROM card
                     INNER JOIN player ON player_id=card_location_arg AND player_previously_played IS NOT NULL
                     WHERE card_location='tableau' AND card_type='102'", true);
    }
    function playersThatMayUseSneakAttack()
    {
        return self::getObjectListFromDB("SELECT card_location_arg FROM card
                     INNER JOIN player ON player_id=card_location_arg AND player_previously_played IS NOT NULL
                     WHERE card_location='tableau' AND card_type='191'", true);
    }
    function playersThatMayUseTerraformingProject()
    {
        return self::getObjectListFromDB("SELECT card_location_arg FROM card
                     INNER JOIN player ON player_id=card_location_arg
                     WHERE card_location='tableau' AND card_type='259'", true);
    }
    function playersThatMayUseTerraformingEngineers()
    {
        return self::getObjectListFromDB("SELECT card_location_arg FROM card
                     INNER JOIN player ON player_id=card_location_arg
                     WHERE card_location='tableau' AND card_type='205'", true);
    }
    function playersThatMayUseImperiumSupplyConvoy()
    {
        $res = array();
        $players = self::getObjectListFromDB("SELECT convoy.card_location_arg player, previousplay.card_type type FROM card convoy
                     INNER JOIN player ON player_id=convoy.card_location_arg AND player_previously_played IS NOT NULL
                     INNER JOIN card previousplay ON previousplay.card_id=player_previously_played
                     WHERE convoy.card_location='tableau' AND convoy.card_type='258'");

        foreach ($players as $player) {
            if (in_array('military', $this->card_types[ $player['type'] ]['category'])) {
                // Conquer a military !
                $res[] = $player['player'];
            }
        }

        return $res;
    }

    // Return the players who did a prestige trade and haven't used the bonus yet
    function playersThatMayUsePrestigeTradeBonus()
    {
        $playersDidPrestigeTrade = array();
        $phaseChoice = $this->getPhaseChoice(4);

        // Since there isn't a card to store the used state of the bonus, we hijack tmp_milforce
        $usedBonus = self::getCollectionFromDB("SELECT player_id, player_tmp_milforce FROM player", true);

        foreach ($phaseChoice as $player => $phaseBonus) {
            if (($phaseBonus == 10 || $phaseBonus == 12) && $usedBonus[$player] == 0) {
                $playersDidPrestigeTrade[] = $player;
            }
        }
        return $playersDidPrestigeTrade;
    }

    function stConsumesell()
    {
        // Before any other action, we reset "player_previously_played" because can interact with Gambling world
        self::DbQuery("UPDATE player SET player_previously_played=NULL WHERE 1");

        $player_phases = $this->getPhaseChoice(4);
        if (count($player_phases) == 0) {
            $this->gamestate->nextState('phaseNotSelected');
            return;
        }

        self::incStat(1, 'phase_played');
        self::incStat(1, 'phase_consume');
        $players_to_active = array();
        foreach ($player_phases as $player_id => $phase_option) {
            if ($phase_option == 0 || $phase_option == 2 || $phase_option==10 || $phase_option==12) {    // sell good or "sell good + x2" or sell+bonus or sell+x2+bonus
            // Check there is at least one good to sell
                if (count($this->getAllGoodsOfPlayer($player_id, true)) > 0) {
                    $players_to_active[] = $player_id;
                }
            }
        }

        $this->gamestate->setPlayersMultiactive($players_to_active, 'sellcleared');
    }
    function stConsume()
    {
        $this->resetCardStatus();

        $sql = "UPDATE player SET player_consumed_types='' WHERE 1 ";
        self::DbQuery($sql);

        $this->drawOnPhase(4);

        // See who can consume
        $player_to_consumptions = $this->getPossibleConsumptionCards(null, true);  // Note: no exception cause no world is 'in use'
        $playersWithPrestigeTradeBonus = $this->playersThatMayUsePrestigeTradeBonus();

        $players_to_active = array();
        foreach ($player_to_consumptions as $player_id => $consumptions) {
            if ((count($consumptions['mand']) + count($consumptions['opt'])) > 0
               || in_array($player_id, $playersWithPrestigeTradeBonus)) {
                $players_to_active[] = $player_id;
            }
        }


        // Set all players to active
        $this->gamestate->setPlayersMultiactive($players_to_active, 'phaseCleared');
    }
    function stExploreConsume()
    {
        $player_phases = $this->getPhaseChoice(1);
        if (count($player_phases) == 0) {
            $this->gamestate->nextState('phaseNotSelected');
            return;
        }

        $this->resetCardStatus();

        $sql = "UPDATE player SET player_consumed_types='' WHERE 1 ";
        self::DbQuery($sql);

        // See who can consume
        // Get consumption powers
        $player_powers = $this->scanTableau(1, null, 'consumecard', true);

        $players_to_active = array();

        foreach ($player_powers as $player_id => $powers) {
            if (count($powers) > 0) {
                $players_to_active[] = $player_id;
            }
        }

        $nextState = 'phaseCleared';
        $expansion = self::getGameStateValue('expansion');
        if ($expansion == 5) {
            $nextState = 'orbPlay';

            self::DbQuery("UPDATE orbteam SET team_cannotmove='0' WHERE 1");
            self::DbQuery("UPDATE player SET player_just_played='0' WHERE 1");// In this phase : used to know who has already played on the orb and who does not

            // Change priority order by placing first player who selected orb play
            $player_in_reverse_priority_order = self::getObjectListFromDB("SELECT player_id FROM player ORDER BY player_orb_priority DESC", true);
            foreach ($player_in_reverse_priority_order as $player_id) {
                if (isset($player_phases[ $player_id ]) && $player_phases[ $player_id ] >= 100) {
                    $this->movePlayerOrbTopPriority($player_id, false);
                }
            }

            $first_player = self::getUniqueValueFromDB("SELECT player_id FROM `player` WHERE player_orb_priority = (SELECT MIN(player_orb_priority) FROM player)");
            $this->gamestate->changeActivePlayer($first_player);

            if (isset($player_phases[ $first_player ]) && $player_phases[ $first_player ] >=100) {
                self::setGameStateValue('orbactionnbr', 3);
            } else {
                self::setGameStateValue('orbactionnbr', 2);
            }
            $this->initTeamMove($first_player);
            self::setGameStateValue('orbteamhasmoved', 0);
        }

        // Set all players to active
        $this->gamestate->setPlayersMultiactive($players_to_active, $nextState);
    }

    function getPlayerSurveyTeamMoves($player_id)
    {
        // Get 256 / Alien Survey Technology
        $sql = "SELECT card_id FROM card WHERE card_location='tableau' AND card_location_arg='$player_id' AND card_type='256'";
        $tech = self::getUniqueValueFromDB($sql);

        if ($tech === null) {
            return 4;
        } else {
            return 6;
        }
    }

    function stConsumeProcess()
    {
        // Clear player_tmp_milforce as it might have been used to mark prestige trade
        self::DbQuery("UPDATE player SET player_tmp_milforce=0");

        $this->send_defered_notif($this->notif_defered_id);
        $this->checkGoals(4);
        $this->gamestate->nextState('');
    }

    function stProductionIntro()
    {
        $player_phases = $this->getPhaseChoice(5);
        if (count($player_phases) == 0) {
            $this->gamestate->nextState('phaseNotSelected');
            return;
        }

        self::incStat(1, 'phase_played');
        self::incStat(1, 'phase_produce');

        $expansion = self::getGameStateValue('expansion');

        if ($expansion == 7) {
            $this->gamestate->nextState('warEffort'); // Go to "contributing to war effort" phase
        } else {
            $this->gamestate->nextState('production_next');
        }
    }

    function stWarEffort()
    {
        // Active player with at least 1 good

        $sql = "SELECT DISTINCT world.card_location_arg good_player ";
        $sql .= "FROM card good ";
        $sql .= "INNER JOIN card world ON world.card_id=good.card_location_arg ";
        $sql .= "WHERE good.card_location='good' ";
        $players_to_active = self::getObjectListFromDb($sql, true);

        $this->gamestate->setPlayersMultiactive($players_to_active, 'phaseCleared');
    }

    function stProductionProcess()
    {
        $player_phases = $this->getPhaseChoice(5);

        $sql = "DELETE FROM player_production WHERE 1";
        self::DbQuery($sql);

        $this->resetCardStatus();

        $players = self::loadPlayersBasicInfos();

        $this->drawOnPhase(5);

        foreach ($player_phases as $player_id => $phase_choice) {
            if ($phase_choice >= 10) {
                // Draw 3 cards
                $this->drawCardForPlayer($player_id, 3);
            }
        }

        // Tranship
        $tranship = self::getObjectFromDB("SELECT card_id, card_location_arg FROM card WHERE card_type='242' AND card_location='tableau'");
        if ($tranship !== null) {
            // Has tranship

            // Get all rare resources who are NOT on tranship
            $sql = "SELECT g.card_location_arg source, g.card_id FROM card g
                INNER JOIN card w ON g.card_location_arg=w.card_id AND w.card_location='tableau' AND w.card_location_arg='".$tranship['card_location_arg']."'
                WHERE g.card_location='good' AND g.card_status='2' AND g.card_location_arg!='".$tranship['card_id']."'";
            $goods = self::getCollectionFromDB($sql, true);

            if (count($goods) > 0) {
                $sql = "UPDATE card SET card_location_arg='".$tranship['card_id']."' WHERE card_id IN ('".implode("','", $goods ) ."') ";
                self::DbQuery($sql);

                self::notifyAllPlayers('tranship', clienttranslate('${player_name} transfers ${nbr} Rare elements to ${card_name}'), array(
                    'card_name' => $this->card_types[ 242 ]['name'],
                    'i18n' => array('card_name'),
                    'goods'=> $goods,
                    'card_id' => $tranship['card_id'],
                    'nbr' => count($goods),
                    'player_id' => $tranship['card_location_arg'],
                    'player_name' => $players[ $tranship['card_location_arg'] ]['player_name']
               ));
            }
        }


        // Every card with a production power is producing
        $tableaux = $this->scanTableau(5);
        foreach ($tableaux as $player_id => $powers) {
            foreach ($powers as $power) {
                $card_id = $power['card_id'];
                if ($power['power'] == 'produce') {
                    // See if there is already a good on this world
                    if ($this->cards->countCardInLocation('good', $card_id) == 0) {
                        // Place a good on this card
                        $good_card = $this->cards->pickCardForLocation($this->getDeck($player_id), 'good', $card_id);
                        self::incStat(1, 'good_produced', $player_id);

                        // Store good type in "card_status"
                        $sql = "UPDATE card SET card_status='".$power['arg']['resource']."' ";
                        $sql .= "WHERE card_id='".$good_card['id']."' ";
                        self::DbQuery($sql);

                        $sql = "INSERT INTO player_production (pp_player_id, pp_good_id, pp_card_id) VALUES ";
                        $sql .= "($player_id,".$power['arg']['resource'].",$card_id) ";
                        self::DbQuery($sql);

                        self::notifyAllPlayers('goodproduction', '', array(
                                    "world_id" => $card_id,
                                    "good_type" => $power['arg']['resource'],
                                    "good_id" => $good_card['id'],
                                    "produced_by" => $player_id
                               ));

                        if (isset($power['arg']['draw'])) {
                            // This player draw this number of cards, in addition
                            $this->drawCardForPlayer($player_id, $power['arg']['draw'], false, $power['card_type']);
                        }
                        if (isset($power['arg']['pr'])) {
                            $this->givePrestige($player_id, $power['arg']['pr'], false, $power['card_type']);
                        }
                    }
                } elseif ($power['power'] == 'scavengerproduce') {
                    // Draw all cards under Galactic Scavengers
                    $card = $this->cards->getCard($power['card_id' ]);
                    $player_id = $card['location_arg'];
                    $cards_to_draw = $this->cards->getCardsInLocation('scavenger');
                    if (count($cards_to_draw) > 0) {
                        $cardDrawn = $this->cards->pickCards(count($cards_to_draw), 'scavenger', $player_id);
                        self::notifyPlayer($player_id, 'drawCards', '', $cardDrawn);
                        $this->notifyUpdateCardCount();

                        $players = self::loadPlayersBasicInfos();

                        $log = clienttranslate('${player_name} draw ${card_nbr} card(s) from Galactic Scavengers');

                        $args = array(
                                                        "player_name" => $players[ $player_id ]['player_name'],
                                                        "card_nbr" => count($cards_to_draw)
                                                   );

                        self::notifyAllPlayers('drawCards_def', $log, $args);
                        self::notifyAllPlayers('scavengerUpdate', '', array('count' => 0));
                    }
                } elseif ($power['power'] == 'bonusifmost') {
                    // If player has the most chromosome worlds
                    $player_to_chromosome_world_count = array();
                    $cards = $this->cards->getCardsInLocation('tableau');
                    foreach ($cards as $card) {
                        if (in_array('chromosome', $this->card_types[ $card['type'] ]['category'])) {
                            if (! isset($player_to_chromosome_world_count[ $card['location_arg'] ])) {
                                $player_to_chromosome_world_count[ $card['location_arg'] ] = 0;
                            }

                            $player_to_chromosome_world_count[ $card['location_arg'] ]++;
                        }
                    }
                    $most = getKeyWithMaximum($player_to_chromosome_world_count);

                    if ($most == $player_id) {
                        // +1 Prestige
                        $this->givePrestige($player_id, 1, false, $power['card_type']);
                    }
                }
            }
        }

        // Now do the produce on windfalls which don't require decisions
        $windfallState = $this->windfallproduction_state();
        foreach ($windfallState as $player_id => $windfallPowers) {
            $this->automaticProduceOnWindfalls($player_id, $windfallPowers);
        }

        $expansion = self::getGameStateValue('expansion');
        if ($expansion == 7) {
                $expansion = self::getGameStateValue('expansion');
                self::DbQuery("UPDATE player SET player_tmp_gene_force='0' WHERE 1");

                // Player with a "production: repair" gets 2 extra repairs
                $phase_choices = $this->getPhaseChoice(5);
            foreach ($phase_choices as $player_id => $bonus) {
                if ($bonus >= 2) {
                    self::DbQuery("UPDATE player SET player_tmp_gene_force='2' WHERE player_id='$player_id'");
                }
            }
            $this->gamestate->nextState("production_done_xeno");
        } else {
            $this->gamestate->nextState("production_done");
        }
    }

    function automaticProduceOnWindfalls($player_id, $windfallPowers = null)
    {
        if ($windfallPowers == null) {
            $windfallPowers = $this->windfallproduction_state()[$player_id];
        }

        // Get all windfalls sorted by type
        $hasOort = false;
        $hasUniversalSymbionts = false;
        $windfall_kinds = [1 => [], 2 => [], 3 => [], 4 => []];
        $windfalls = [];
        $remaining_usable = 0;

        // We only track the number of damaged windfalls
        $damaged_windfall_kinds = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        $damaged_windfalls = 0;

        $cards = $this->cards->getCardsInLocation('tableau', $player_id);
        foreach ($cards as $card) {
            // If there is already a good, we can't produce here
            if ($this->cards->countCardInLocation('good', $card['id']) != 0) {
                continue;
            }

            // If it is a damaged windfall, we can't produce on it, but it's a potential target for later
            if ($card['type'] == 1000) {
                $real_type_id = self::getUniqueValueFromDB("SELECT card_damaged FROM card WHERE card_id=$card[id]");
                $real_type = $this->card_types[ $real_type_id ];
                if (in_array('windfall', $real_type['category'])) {
                    ++$damaged_windfall_kinds[$real_type['windfalltype']];
                    ++$damaged_windfalls;
                }
                continue;
            }

            // We're not producing on Oort, we let the player decide which good to produce
            if ($card['type'] == 220) {
                $hasOort = true;
                continue;
            }

            if ($card['type'] == 135) {
                // Universal Symbionts is in the tableau with no good
                $hasUniversalSymbionts = true;
            }

            $card_type = $this->card_types[ $card['type'] ];
            if (! in_array('windfall', $card_type['category'])) {
                continue;
            }

            $windfall_kinds[$card_type['windfalltype']][] = $card['id'];
            $windfalls[] = $card['id'];
        }

        // Let's see for each kind if we have enough specialized powers
        foreach (range(1, 4) as $kind) {
            $n_powers = 0;
            foreach ($windfallPowers as $power) {
                // Universal Symbionts: if there's a single Genes, can't produce. If there are 2, can only produce on the other
                if ($kind == 3 && isset($power['notthisworld']) && $hasUniversalSymbionts && count($windfall_kinds[$kind]) < 3) {
                    if (count($windfall_kinds[$kind]) == 2) {
                        foreach ($windfall_kinds[$kind] as $i => $card_id) {
                            if ($power['reason'] != $card_id) {
                                $this->windfallProduction($card_id, null, null, $player_id);
                                $key = array_search($card_id, $windfalls);
                                unset($windfalls[$key]);
                                unset($windfall_kinds[$kind][$i]);
                            }
                        }
                    }
                }
                else if ($power['type'] == $kind) {
                    $n_powers++;
                }
            }

            // If we have enough specialized powers to produce on all worlds of this kind, let's do it
            // We need one more in case of Oort so that we make sure to leave as much choice as possible to the player
            // We also keep spare ones for damaged worlds
            if ($n_powers >= count($windfall_kinds[$kind]) + $hasOort + $damaged_windfall_kinds[$kind]) {
                foreach ($windfall_kinds[$kind] as $card_id) {
                    $this->windfallProduction($card_id, null, null, $player_id);
                    $key = array_search($card_id, $windfalls);
                    unset($windfalls[$key]);
                }
            } else {
                $remaining_usable += $n_powers;
            }
        }

        // Do we have enough non-specific + remaining specifics to produce on all remaining windfalls?
        foreach ($windfallPowers as $power) {
            if ($power['type'] == 'all') {
                $remaining_usable++;
            }
        }
        // If we do, then let's produce on all remaining windfalls
        if ($remaining_usable >= count($windfalls) + $hasOort + $damaged_windfalls) {
            foreach ($windfalls as $card_id) {
                $this->windfallProduction($card_id, null, null, $player_id);
            }
        }
    }

    // Returns the list of produce powers which have a valid target in tableau
    function usableProducePowers($player_id, $powers)
    {
        $usable_powers = array();
        $cards = $this->cards->getCardsInLocation('tableau', $player_id);

        foreach ($powers as $power) {
            foreach ($cards as $card) {
                // If there is already a good, we can't produce here
                if ($this->cards->countCardInLocation('good', $card['id']) != 0) {
                    continue;
                }

                $card_type = $this->card_types[ $card['type'] ];

                if ($power['type'] == "produceifdiscard") {
                    if ($power['reason'] == $card['id']) {
                        $usable_powers[] = $power;
                        break;
                    }
                }

                if (isset ($power['notthisworld']) && $power['reason'] == $card['id']) {
                    continue;
                }

                if (! in_array('windfall', $card_type['category'])) {
                    continue;
                }

                // Has this world already produced ?
                if (! is_null(self::getUniqueValueFromDB("SELECT pp_card_id FROM player_production WHERE pp_card_id=".$card['id']))) {
                    continue;
                }

                // Produce on windfall if discard
                if ($power['type'] == 'windfallproduceifdiscard'
                    && ($power['world_type'] == null || $power['world_type'] == $card_type['windfalltype']) || $card_type['windfalltype'] == 'choice') {
                    $usable_powers[] = $power;
                    break;
                }

                // Standard windfall produce power
                if ($power['type'] != 'all' && $power['type'] != $card_type['windfalltype'] && $card_type['windfalltype'] != 'choice') {
                    continue;
                }

                // We found a world on which this power can produce
                $usable_powers[] = $power;
                break;
            }
        }
        return $usable_powers;
    }

    function stProductionWindfall()
    {
        // Player who choose the phase & player with windfall powers can choose which windfall is producing
        $windfallState = $this->windfallproduction_state();
        $active_players = array();
        $usable_powers = [];

        foreach ($windfallState as $player_id => $windfallPowers) {
            $usable_powers[$player_id] = $this->usableProducePowers($player_id, $windfallPowers);
            if (count($usable_powers[$player_id]) > 0) {
                $active_players[] = $player_id;
            }
        }

        $expansion = self::getGameStateValue('expansion');
        if ($expansion == 7) {
            // Get damaged worlds so we can active these players too
            $players = self::getObjectListFromDB("SELECT DISTINCT card_location_arg
                      FROM card
                      WHERE card_damaged!='0'
                      AND card_location='tableau' ", true);

            foreach ($players as $player_id) {
                $active_players[] = $player_id;
            }
        }

        $this->gamestate->setPlayersMultiactive($active_players, "phaseCleared");

        foreach ($active_players as $player_id) {
            $windfallState[$player_id]['title'] = $this->getProduceTitle($player_id, $usable_powers[$player_id]);
            self::notifyPlayer($player_id, 'updateWindfallPowers', '', $windfallState[$player_id]);
        }
    }

    function stPostProductionProcess()
    {
        // Analyse production via "player_production" table and give some bonuses when needed
        $tableaux = $this->scanTableau(5, null, null, true);
        foreach ($tableaux as $player_id => $powers) {
            foreach ($powers as $power) {
                $card_id = $power['card_id'];

                if ($power['power'] == 'drawforeach'            // draw a card for each <good> produced
                    || $power['power'] == 'drawforeachgoodtype' // draw for each different type of good produced
                    || $power['power'] == 'drawforeachtwo'      // draw a card for each two goods produced
                   ) {
                    $this->drawForEachGood($card_id);
                } elseif ($power['power'] == 'bonusifbiggestprod') { // Draw if produce the most X
                    $sql = "SELECT pp_player_id player_id, COALESCE(COUNT(*), 0) cnt FROM player_production ";
                    if (isset($power['arg']['resource'])) {
                        $good_type = $power['arg']['resource'];
                        $sql .= "WHERE pp_good_id='$good_type' ";
                    } else {
                        $sql .= "WHERE 1 ";
                    }
                    $sql .= "GROUP BY pp_player_id ";
                    $sql .= "ORDER BY cnt DESC ";
                    $dbres = self::DbQuery($sql);
                    $rowfirst = mysql_fetch_assoc($dbres);

                    if ($rowfirst) {
                        if ($rowfirst['player_id']==$player_id) {    // This player produce the biggest number
                            $rowsecond = mysql_fetch_assoc($dbres);
                            if ($rowsecond == null || $rowsecond['cnt']<$rowfirst['cnt']) {   // Okay, draw
                                $this->drawCardForPlayer($player_id, $power['arg']['card'], false, $power['card_type']);
                            }
                        }
                    }
                } elseif ($power['power'] == 'drawforeachworld'
                          || $power['power'] == 'drawformilitary'
                          || $power['power'] == 'drawforxenomilitary') {
                    $this->drawForEachWorld($card_id);
                }
            }
        }

        // Maybe some worlds have been repaired
        if (self::getGameStateValue('expansion') == 7) {
            self::notifyAllPlayers('updateSpecializedMilitary', '', $this->getSpecializedMilitary());
        }

        $this->checkGoals(5);

        $this->gamestate->nextState('');
    }
    function stEndRoundDiscard()
    {
        $this->checkGoals('discard');

        // Players with more than 10 cards must discard
        $to_discard = $this->getEndRoundDiscardNumber();
        $active_players = array();
        foreach ($to_discard as $player_id => $to_discard_nbr) {
            if ($to_discard_nbr > 0) {
                $active_players[] = $player_id;
            }
        }

        $this->gamestate->setPlayersMultiactive($active_players, self::getGameStateValue('expansion') != 7 ?  "allPlayersValid": "invasionGame");
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
        self::notifyAllPlayers('updateXenoTieBreaker', '', $players);
    }

    function stInvasionGame()
    {
        self::DbQuery("UPDATE player SET player_xeno_victory='0', player_tmp_gene_force='0'"); // Note : gene force used to store bunker usage

        $this->resetCardStatus();

        $current_wave = self::getGameStateValue('xeno_current_wave');

        if ($current_wave == -2) {   // First turn
            self::setGameStateValue('xeno_current_wave', -1);
            self::notifyAllPlayers('updateWave', clienttranslate('No invasion this turn ...'), array('wave' => -1, 'remaining' => 0));

            $this->gamestate->nextState('nextRound');
        } elseif ($current_wave == -1) {   // Second turn
            self::setGameStateValue('xeno_current_wave', 0);
            self::notifyAllPlayers('updateWave', clienttranslate('No invasion this turn ...'), array('wave' => 1, 'remaining' => $this->getWaveRemaining()));

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
                self::notifyAllPlayers('placeAdmiralDisks', '', self::getCollectionFromDB("SELECT player_id, player_xeno_milforce xeno_milforce, player_xeno_milforce_tiebreak xeno_milforce_tiebreak FROM player"));
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
                    self::notifyAllPlayers('moveMilitaryVsXenoArrow', '', array('military_vs_xeno' => $total_force));
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
                        self::notifyAllPlayers('moveAdmiralDisks', '', array('moves' => $stackMoves, 'scroll' => true));
                    }
                }

                if ($total_force < self::getGameStateValue('xeno_repulse')) {
                    self::notifyAllPlayers('moveMilitaryVsXenoArrow', '', array('military_vs_xeno' => $total_force));
                }
            }

            $this->updateXenoTieBreaker();

            self::setGameStateValue('xeno_repulse', $total_force);

            self::notifyAllPlayers('updateXenoRepulsion', clienttranslate('The total of Empire force against Xeno is now : ${force}'), array('force' => $total_force));

            if (self::getGameStateValue('xeno_repulse_goal') <= $total_force) {
                self::notifyAllPlayers('simpleNote', clienttranslate("The Empire successfully manages to repulse the Xenos!! Game ends immediately"), array());

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

            self::notifyAllPlayers('dealInvasionCards', clienttranslate('Xeno Invasion (wave ${wave}) : an invasion card is given to each player'), array(
                'wave' => $current_wave,
                'remaining' => $this->getWaveRemaining(),
                'cards' => $invasionCards
           ));

            $pos = 1;
            foreach ($players as $player) {
                $invasion_value = $invasionCards[ $player ];

                if ($invasion_value < 100) {
                    self::notifyAllPlayers('simpleNote', clienttranslate('${player_name} (position #${pos}) must face a invasion force ${value}.'), array(
                        'pos' => $pos,
                        'player_name' => $playersinfos[ $player ]['player_name'],
                        'value' => $invasion_value
                   ));
                } else {
                    self::notifyAllPlayers('simpleNote', clienttranslate('${player_name} (position #${pos}) must face a invasion force Base military force + ${value}.'), array(
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

                    self::notifyAllPlayers('updateScore', clienttranslate('${player_name}, force ${force} repulse the Xenos (${xeno}) and scores ${score_delta} points'),
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
        self::notifyAllPlayers('clearTmpMilforce', '', null);
        $this->moveJustDiscardedToDiscard();
        self::notifyAllPlayers('updateSpecializedMilitary', '', $this->getSpecializedMilitary());

        // Active player that must damage a world
        $players = self::loadPlayersBasicInfos();
        $player_to_damage = self::getObjectListFromDB("SELECT player_id FROM player WHERE player_xeno_victory='0'", true);

        if (count($players) == count($player_to_damage)) {
            // No one manage to repulse the Xenos !!

            $defeat = self::incGameStateValue('xeno_empire_defeat', 1);

            if ($defeat == 1) {
                self::notifyAllPlayers('empireDefeat', clienttranslate('No players manage to repulse the Xeno : Empire defeat marker set to 1! Be careful!'), array('defeat' => $defeat));
            } elseif ($defeat >= 2) {
                self::notifyAllPlayers('empireDefeat', clienttranslate('Game End : No players manage to repulse the Xeno for the second time : Empire is defeated and end of game bonuses are not attributed.'), array('defeat' => $defeat));
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
        self::notifyAllPlayers('moveAdmiralDisks', '', array('moves' => $moves, 'scroll' => false));

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
                self::notifyAllPlayers('simpleNote', clienttranslate('${player_name} has no world to damage'), array(
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

    function stEndRound()
    {
        // Cards from Retrofit => back to retrofit player
        if ($this->cards->countCardInLocation('retrofit') > 0) {
            $player_with_retrofit = self::getUniqueValueFromDB("SELECT card_location_arg
                         FROM card
                         WHERE card_type='218'
                         AND card_location='tableau'", true);

            $cardDrawn = $this->cards->getCardsInLocation('retrofit');
            if (self::getGameStateValue('draft') > 1) {
                // In draft game, the retrofit players draw from his deck as many cards as was discarded by other players
                $card_drawn_nbr = count($cardDrawn);
                foreach (array_keys($this->cards->countCardsByLocationArgs('retrofit')) as $player_id) {
                    $this->cards->moveAllCardsInLocation('retrofit', $this->getDiscard($player_id), $player_id, 0);
                }
                $cardDrawn = $this->cards->pickCards($card_drawn_nbr, $this->getDeck($player_with_retrofit), $player_with_retrofit);
            } else {
                $this->cards->moveAllCardsInLocation('retrofit', 'hand', null, $player_with_retrofit);
            }

            self::notifyPlayer($player_with_retrofit, 'drawCards', '', $cardDrawn);
            $this->notifyUpdateCardCount();

            self::incStat(count($cardDrawn), 'cards_drawn', $player_with_retrofit);

            $players = self::loadPlayersBasicInfos();
            self::notifyAllPlayers('simpleNote', clienttranslate('Retrofit & Salvage, inc. : ${player_name} gets ${nbr} card(s) from other players discards'), array(
                'player_name' => $players[ $player_with_retrofit ]['player_name'],
                'nbr' => count($cardDrawn)
           ));
        }

        $expansion = self::getGameStateValue('expansion');
        if ($expansion == 7) {
            self::DbQuery("UPDATE player SET player_tmp_xenoforce=0");
            self::notifyAllPlayers('clearTmpMilforce', '', null);

            // Maybe some worlds have been damaged
            self::notifyAllPlayers('updateSpecializedMilitary', '', $this->getSpecializedMilitary());


            if (self::getGameStateValue('xeno_current_wave') < 3) {
                $this->invasioncards->moveAllCardsInLocation('inprogress', 'removed');
            } else {
                $this->invasioncards->moveAllCardsInLocation('inprogress', 'discard');
            }
        }

        // End of game test
        $bEndOfGame = false;
        $bAttributeXenoGoals = false;
        if ($expansion == 7) {
            $bAttributeXenoGoals = true;

            if (self::getGameStateValue('xeno_empire_defeat') >= 2) {
                $bEndOfGame = true;
                $bAttributeXenoGoals = false;
            }

            if (! $bEndOfGame) {
                if (self::getGameStateValue('xeno_repulse_goal') <= self::getGameStateValue('xeno_repulse')) {
                    $bEndOfGame = true;
                }
            }
        }

        if (! $bEndOfGame) {
            if (self::getGameStateValue('remainingVp') <= 0) {
                $bEndOfGame = true;
                self::notifyAllPlayers('nomoreVp', clienttranslate("No more victory point chip: end of game"), array());
            }
        }

        if (! $bEndOfGame) {
            $cards_in_tableaux = $this->cards->countCardsByLocationArgs('tableau');
            $hidden_fortress_player = self::getUniqueValueFromDB("SELECT card_location_arg FROM card WHERE card_type='166' AND card_location='tableau'");

            foreach ($cards_in_tableaux as $player_id => $nbr) {
                $to_trigger_gameend = 12;
                if ($player_id == $hidden_fortress_player) {
                    $to_trigger_gameend = 14;
                }

                if ($nbr >= $to_trigger_gameend) {
                    $bEndOfGame = true;
                    self::notifyAllPlayers('nomoreVp', clienttranslate("There is a tableau with 12 cards or more: end of game"), array());
                    break;
                }
            }
        }

        $player_to_prestige = self::getCollectionFromDB("SELECT player_id,player_prestige FROM player", true);
        $this_obj_winners = getKeysWithMaximum($player_to_prestige);

        $max_prestige = $player_to_prestige[ reset($this_obj_winners) ];

        if ($max_prestige >= 15) {
            $bEndOfGame = true;
            self::notifyAllPlayers('nomoreVp', clienttranslate("A player has more than 15 prestige points : end of game"), array());
        }

        if (self::getGameStateValue('expansion') == 5) {
            if ($this->orbcards->countCardsInLocation('deck') == 0) {
                $bEndOfGame = true;
                self::notifyAllPlayers('nomoreVp', clienttranslate("The Orb card deck is empty : end of the game"), array());
            }
        }

        if ($bEndOfGame) {
            $this->scoreRemainingArtefacts();
            $this->scoreSixDevelopments();
            if ($bAttributeXenoGoals) {
                $this->scoreGreatestAdmiralEffort();
            }

            $this->checkGoals('endgame');

            // Final statistics /////////

            // Gets final military force & chips
            $sql = "SELECT player_id, player_milforce, player_vp FROM player" ;
            $dbres = self::DbQuery($sql);
            while ($row = mysql_fetch_assoc($dbres)) {
                self::setStat($row['player_milforce'], 'milforce', $row['player_id']);
                self::setStat($row['player_vp'], 'chips_count', $row['player_id']);
            }

            // Get final number of card in tableau
            $tableau_count = $this->cards->countCardsByLocationArgs("tableau");
            foreach ($tableau_count as $player_id => $count) {
                self::setStat($count, 'tableau_count', $player_id);
            }

            // Sum card on tableau VP
            $cards = $this->cards->getCardsInLocation('tableau');
            foreach ($cards as $card) {
                self::incStat($this->card_types[ $card['type'] ]['vp'], 'tableau_points', $card['location_arg']);
            }

            $this->gamestate->nextState('endGame');
        } else {
            // Prestige leader management
            $players = self::loadPlayersBasicInfos();

            if (self::getGameStateValue('prestigeLeader')!=0 && self::getGameStateValue('prestigeOnLeaderTile') > 0) {
                // leader gets 1 vp + 1 card
                $player_id = self::getGameStateValue('prestigeLeader');
                $pscore = $this->updatePlayerScore($player_id, 1, true);

                self::incGameStateValue('remainingVp', -1);
                self::setGameStateValue('prestigeOnLeaderTile', 0);

                self::notifyAllPlayers('updateScore', clienttranslate('Prestige leader : ${player_name} scores ${score_delta} points'),
                                        array(
                                            "score" => $pscore['score'],
                                            "vp" => $pscore['vp'],
                                            "player_id" => $player_id,
                                            "player_name" => $players[ $player_id ]['player_name'],
                                            "score_delta" => 1,
                                            "vp_delta" => 1
                                       ) );

                $this->drawCardForPlayer($player_id, 1, false, 'prestigeleader');
            } else {
                // All prestige leaders with at least 1 prestige gain 1 vp
                if ($max_prestige > 0) {
                    foreach ($this_obj_winners as $player_id) {
                        $pscore = $this->updatePlayerScore($player_id, 1, true);

                        self::incGameStateValue('remainingVp', -1);

                        self::notifyAllPlayers('updateScore', clienttranslate('Prestige leader : ${player_name} scores ${score_delta} points'),
                                                array(
                                                    "score" => $pscore['score'],
                                                    "vp" => $pscore['vp'],
                                                    "player_id" => $player_id,
                                                    "player_name" => $players[ $player_id ]['player_name'],
                                                    "score_delta" => 1,
                                                    "vp_delta" => 1
                                               ) );
                    }
                }
            }

            // Gaining a VP chip from prestige can earn a phase 4 goal
            $this->checkGoals(4);

            self::DbQuery("DELETE FROM phase");

            $this->gamestate->nextState('nextRound');
        }
    }

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
                throw new feException("Unknow goal : ".$goal_type['name']);
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
            self::notifyAllPlayers('goalProgress', "", $args);
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


            self::notifyAllPlayers('updateScore', clienttranslate('${player_name} fulfills goal ${goal} (${description}) and scores ${points_nbr} points'),
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
            self::notifyAllPlayers('fullfillGoal', '', array('goal' => $goal['id'], 'type' => $goal['type'], 'to' => $winners));
            $progress_tooltip = $this->getGoalProgressTooltip($goal_type, $this->getGoalProgress($goal_type));
            foreach ($winners as $winner) {
                self::notifyAllPlayers('goalProgress', "", array('goal' => $goal['id'], 'player' => $winner, 'progress' => $progress_tooltip));
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

                        self::notifyAllPlayers('updateScore', $log,
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
                    self::notifyAllPlayers('updateScore', $log,
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

                    self::notifyAllPlayers('fullfillGoal', '', array('goal' => $most['id'], 'type'=> $most['type'], 'from' => $current_owner, 'to' => 'discard'));
                    $progress_tooltip = $this->getGoalProgressTooltip($goal_type, $this->getGoalProgress($goal_type));
                    self::notifyAllPlayers('goalProgress', "", array('goal' => $most['id'], 'progress' => $progress_tooltip));
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

                    self::notifyAllPlayers('updateScore', $log,
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

                    self::notifyAllPlayers('fullfillGoal', '', array('goal' => $most['id'], 'type' => $most['type'], 'to' => array($who_gets_it)));
                    $progress_tooltip = $this->getGoalProgressTooltip($goal_type, $this->getGoalProgress($goal_type));
                    self::notifyAllPlayers('goalProgress', "", array('goal' => $most['id'], 'player' => $who_gets_it, 'progress' => $progress_tooltip));
                }
            }
        }
    }

    function draft($card_id)
    {
        self::checkAction('draft');
        $player_id = self::getCurrentPlayerId();
        $this->draft_card($card_id, $player_id);
        $this->gamestate->setPlayerNonMultiactive($player_id, 'draft');
    }

    function draft_card($card_id, $player_id)
    {
        // Get cards details
        $card = $this->cards->getCard($card_id);

        if (! $card) {
            throw new feException("Card not found");
        }
        if ($card['location']!='hand' || $card['location_arg'] != $player_id) {
            throw new feException("This card is not in your hand");
        }

        // Place this card in player's draft
        $this->cards->moveCard($card_id, 'drafted', $player_id);

        self::notifyPlayer($player_id, "drafted", '', array("card" => $card));

        // All remaining choices => to next player choice
        $draftRound = self::getGameStateValue('draftRound');
        $players = self::loadPlayersBasicInfos();

        if ($draftRound % 2 == 1) {
            $next_player_table = self::createNextPlayerTable(array_keys($players));
        } else {
            $next_player_table = self::createPrevPlayerTable(array_keys($players));
        }

        $nextPlayer = $next_player_table[ $player_id ];

        $this->cards->moveAllCardsInLocation('hand', 'nextchoice', $player_id, $nextPlayer);
    }

    function stDraftNewRound()
    {
        if (self::getGameStateValue('draft') < 2) {
            // No draft, go to game start
            if ($this->cards->countCardsInLocation('hiddentableau') > 0) {
                $this->gamestate->nextState("initialDiscardHomeWorld");
            } else {
                $this->gamestate->nextState("initialDiscard");
            }
            return;
        }

        if (self::getGameStateValue('reuseDraft') == 2) {
            $this->loadDraft();
        }

        // Preparing a new draft round ...
        $players = self::loadPlayersBasicInfos();
        $remainingCards = $this->cards->countCardsInLocation('deck');

        $nb_card_per_round = 5;
        $min_card_per_round = 5;

        // Brink of War changes the drafting rules
        $bBoW = self::getGameStateValue("expansion") == 4;
        if ($bBoW) {
            switch (count($players)) {
                case 2:
                    $nb_card_per_round = 5;
                    $min_card_per_round = 2;
                    break;
                case 3:
                    $nb_card_per_round = 5;
                    $min_card_per_round = 4;
                    break;
                case 4:
                    $nb_card_per_round = 7;
                    $min_card_per_round = 6;
                    break;
                case 5:
                    $nb_card_per_round = 9;
                    $min_card_per_round = 7;
                case 6:
                    $nb_card_per_round = 9;
                    $nb_card_per_round = 9;
            }
        }

        if ($remainingCards < $min_card_per_round*count($players)) {
            // Not enough cards in deck
            // ... remove remaining cards ...
            $this->cards->moveAllCardsInLocation('deck', 'removed');

            // We save the draft to be reused in a future game
            if (self::getGameStateValue('reuseDraft') == 1) {
                $this->saveDraft();
            }

            // Each player draw 6 cards from his own deck
            foreach ($players as $player_id => $player) {
                // Move all drafted cards to players own deck
                $this->cards->moveAllCardsInLocation('drafted', 'pd'.$player_id, $player_id );

                $this->cards->shuffle($this->getDeck($player_id));

                $cards = $this->cards->pickCards(6, $this->getDeck($player_id), $player_id);
                self::giveExtraTime($player_id);
                self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => $cards));
            }

            // ... and starts the game
            $this->gamestate->nextState("initialDiscardHomeWorld");
        } else {
            $remaining_draft_round = floor($remainingCards / ($nb_card_per_round*count($players)));

            $draftRound = self::incGameStateValue('draftRound', 1);

            if ($draftRound % 2 == 1) {
                self::notifyAllPlayers('simpleNote', clienttranslate('New draft round : you pass remaining cards to the player on your left.'), array());
            } else {
                self::notifyAllPlayers('simpleNote', clienttranslate("New draft round : you pass remaining cards to the player on your right"), array());
            }

            if ($remaining_draft_round > 1) {
                self::notifyAllPlayers('simpleNote', clienttranslate('(Note : ${round} remaining drafting round)'), array('round'=>$remaining_draft_round));
            } else {
                self::notifyAllPlayers('simpleNote', clienttranslate('This is the LAST drafting round.'), array());
            }

            // cards for each players hands
            foreach ($players as $player_id => $player) {
                $cards = $this->cards->pickCards($nb_card_per_round, 'deck', $player_id);
                self::giveExtraTime($player_id);
                self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => $cards));
            }
            $this->gamestate->nextState("newround");
            $this->gamestate->setAllPlayersMultiactive();
        }
    }

    function stDraftNextCard()
    {
        // Move the cards to the player hands
        $this->cards->moveAllCardsInLocationKeepOrder('nextchoice', 'hand');
        $cards = $this->cards->getCardsInLocation('hand');

        $player_to_cards = array();
        foreach ($cards as $card) {
            $player_id = $card['location_arg'];

            if (! isset($player_to_cards[ $player_id ])) {
                $player_to_cards[ $player_id ] = array();
            }
            $player_to_cards[ $player_id ][] = $card;
        }

        //We show the players the new card choices
        foreach ($player_to_cards as $player_id => $cards) {
            self::notifyPlayer($player_id, 'newCardChoice', '', array('cards' => $cards));
        }

        // If this is the last card, we draft it automatically
        if ($this->cards->countCardInLocation('hand') == count($player_to_cards)) {
            foreach ($player_to_cards as $player_id => $cards) {
                $card = array_shift($cards);
                $this->draft_card($card['id'], $player_id);
            }
            $this->gamestate->nextState('endRound');
        // Else, we go for another round
        } else {
            $this->gamestate->setAllPlayersMultiactive();
            $this->gamestate->nextState('nextCard');
        }
    }

    function stRemovedState()
    {
        $this->gamestate->nextState('next');
    }

    function saveDraft()
    {
        // Save expansion and new world options to make sure we are playing with the same deck
        $draft_data = [
            'expansion' => self::getGameStateValue('expansion'),
            'newWorlds' => self::getGameStateValue('newWorlds'),
            'draft'=> [],
        ];

        $sql = "
        SELECT card_id as id, card_type as type, card_location as location, card_location_arg as location_arg
        FROM card
        WHERE card_location NOT LIKE 'goal_%' AND card_location NOT LIKE 'obj_%'";
        $draft_data['draft'] = self::getCollectionFromDb( $sql );
        $json = json_encode($draft_data);
        $this->storeLegacyTeamData($json);
        self::notifyAllPlayers('message', clienttranslate('Draft has been successfully saved'), []);
    }

    function loadDraft()
    {
        $json = $this->retrieveLegacyTeamData();

        // Check if there is a valid save for this team of players
        if(!is_string($json)) {
            self::notifyAllPlayers('message', clienttranslate('No saved draft found for this table'), []);
            return;
        }

        // Check if the draft was made with the same deck
        $json = substr($json, 1, strlen($json)-2);
        $draft_data = json_decode($json, true);
        $same_deck = $draft_data['expansion'] == self::getGameStateValue('expansion')
                        && $draft_data['newWorlds'] == self::getGameStateValue('newWorlds');
        if (!$same_deck) {
            self::notifyAllPlayers('message', clienttranslate('Saved draft was made with a different deck'), []);
            return;
        }

        // Delete the existing cards to be replaced by the saved ones
        $sql = "DELETE FROM card WHERE card_location NOT LIKE 'goal_%' AND card_location NOT LIKE 'obj_%'";
        self::DbQuery($sql);
        foreach($draft_data['draft'] as $card_id => $card )
        {
            $sql = "INSERT INTO card (card_id, card_type, card_location, card_location_arg) VALUES (".$card['id'].", ".$card['type'].", '".$card['location']."', ".$card['location_arg'].")";
            self::DbQuery( $sql );
        }
        self::notifyAllPlayers('message', clienttranslate('Draft has been successfully loaded'), []);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// End of game management
////////////


    function stEndScore() {

        // For RFTG: sum of "goods available" + "cards in hand" = auxiliary score
        $hand_count = $this->cards->countCardsByLocationArgs('hand');

        $goods = array();
        $sql = "SELECT world.card_location_arg player_id, COUNT(good.card_id) cnt FROM card good ";
        $sql .= "INNER JOIN card world ON world.card_id=good.card_location_arg ";
        $sql .= "WHERE good.card_location='good' ";
        $sql .= "AND world.card_location='tableau' ";   // .. world must be is in current player tableau
        $sql .= "GROUP BY world.card_location_arg ";
        $dbres = self::DbQuery($sql);
        while ($row = mysql_fetch_assoc($dbres)) {
            $goods[ $row['player_id'] ] = $row['cnt'];
        }

        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $aux = 0;
            if (isset($hand_count[ $player_id ])) {
                $aux += $hand_count[ $player_id ];
            }
            if (isset($goods[ $player_id ])) {
                $aux += $goods[ $player_id ];
            }
            $sql = "UPDATE player SET player_score_aux='$aux' WHERE player_id='$player_id' ";
            self::DbQuery($sql);
        }

        $this->gamestate->nextState('');
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    function zombieTurn($state, $active_player)
    {
        if ($state['name'] == 'draft') {
            $card = $this->cards->pickCardForLocation('hand', 'hand', $active_player);   // Pick one random card
            $this->draft_card($card['id'], $active_player);
            $this->gamestate->setPlayerNonMultiactive($active_player, "draft");
        } elseif ($state['name'] == 'initialDiscard'
                || $state['name'] == 'initialDiscardAncientRace'
                || $state['name'] == 'initialDiscardScavenger'
            ) {
            $this->cards->moveAllCardsInLocation('hand', $this->getDiscard($active_player), $active_player, 0);   // Discard all
            $this->nextState("phaseCleared");
        } elseif ($state['name'] == 'initialDiscardHomeWorld') {
            $this->cards->moveAllCardsInLocation('hand', $this->getDiscard($active_player), $active_player, 0);   // Discard all
            $this->cards->moveAllCardsInLocation('hiddentableau', $this->getDiscard($active_player), $active_player, 0);   // Discard all
            $this->gamestate->setPlayerNonMultiactive($active_player, "phaseCleared");
        } elseif ($state['name'] == 'initialOrb') {
            $this->gamestate->nextState("orbNextPlayer");
        } elseif ($state['name'] == 'initialTeam') {
            $this->gamestate->nextState("orbTeamplace");
        } elseif ($state['name'] == 'phaseChoice'
                || $state['name'] == 'phaseChoiceCrystal') {
            $this->nextState("phaseCleared");
        } elseif ($state['name'] == 'orbActionMove') {
            $this->gamestate->nextState("orbPass");
        } elseif ($state['name'] == 'orbActionMoveDest'
                || $state['name'] == 'breedingTube'
                || $state['name'] == 'orbActionPlay'
                ) {
            $this->gamestate->nextState("orbDraw");
        } elseif ($state['name'] == 'orbActionDraw'
                || $state['name'] == 'orbActionBackToSas'
            ) {
            $this->gamestate->nextState("orbSkip");
        } elseif ($state['name'] == 'searchAction'
                || $state['name'] == 'searchActionChoose'
            ) {
            $this->gamestate->nextState("done");
        } elseif ($state['name'] == 'exploreconsume') {
            $this->gamestate->setPlayerNonMultiactive($active_player, "phaseCleared");
        } elseif ($state['name'] == 'explore') {
            $this->cards->moveAllCardsInLocation('explored', $this->getDiscard($active_player), $active_player, 0);   // Discard all
            $this->gamestate->setPlayerNonMultiactive($active_player, "phaseCleared");
        } elseif ($state['name'] == 'developdiscard') {
            $this->gamestate->setPlayerNonMultiactive($active_player, "done");
        } elseif ($state['name'] == 'develop') {
            $this->gamestate->setPlayerNonMultiactive($active_player, "phaseCleared");
        } elseif ($state['name'] == 'additionalSas') {
            $this->gamestate->setPlayerNonMultiactive($active_player, "orbNextPlayer");
        } elseif ($state['name'] == 'settle'
                || $state['name'] == 'discardtoputgood'
            ) {
            $this->gamestate->setPlayerNonMultiactive($active_player, "phaseCleared");
        } elseif ($state['name'] == 'settlediscard') {
            $this->gamestate->setPlayerNonMultiactive($active_player, "done");
        } elseif ($state['name'] == 'takeover_maydefeat') {
            $this->gamestate->setPlayerNonMultiactive($active_player, "continue");
        } elseif ($state['name'] == 'takeover_attackerboost'
                || $state['name'] == 'takeover_defenderboost'
            ) {
            $this->gamestate->setPlayerNonMultiactive($active_player, "done");
        } elseif ($state['name'] == 'consumesell') {
            $this->gamestate->setPlayerNonMultiactive($active_player, "sellcleared");
        } elseif ($state['name'] == 'consume') {
            $this->gamestate->setPlayerNonMultiactive($active_player, "phaseCleared");
        } elseif ($state['name'] == 'productionwindfall'
                || $state['name'] == 'warEffort'
            ) {
            $this->gamestate->setPlayerNonMultiactive($active_player, "phaseCleared");
        } elseif ($state['name'] == 'endrounddiscard') {
            $this->cards->moveAllCardsInLocation('hand', $this->getDiscard($active_player), $active_player, 0);   // Discard all
            $this->gamestate->setPlayerNonMultiactive($active_player, "allPlayersValid");
        } else {
            throw new feException("Zombie mode not supported at this game state:".$state['name']);
        }
    }


///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

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
                $state = $this->gamestate->state();
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
            $state = $this->gamestate->state()['name'];
            $expansion = self::getGameStateValue('expansion');
            if ($state == 'explore' && $expansion != 7 && $expansion != 8) {
                $cards = $this->cards->getCardsInLocation('explored');
                if (count($cards) == 0) {
                    $this->stExplore();
                }
            }
        }

        if ($from_version <= 2101250815) {
            $state = $this->gamestate->state()['name'];
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
                $state = $this->gamestate->state()['name'];
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
            $state = $this->gamestate->state()['name'];
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
    }

    ///////////////////////////////////////////////////////////
    ///////////// ORB play (Alien Artifacts) //////////////////

    function orbdraw()
    {
        self::checkAction('orbDraw');
        $player_id = self::getActivePlayerId();

        if (self::getGameStateValue('orbactionnbr') == 0) {
            throw new feException(self::_("You can only do 2 of the 3 Orb actions so you cannot do the Draw action"), true);
        }

        $card = $this->orbcards->pickCard('deck', $player_id);

        if ($card !== null) {
            self::notifyPlayer($player_id, 'pickOrbCards', '', array(
                'cards' => array($card)
               ));

            $deck = self::getCollectionFromDB("SELECT card_type_arg, COUNT(*) FROM orbcard WHERE card_location='deck' GROUP BY card_type_arg", true);

            self::notifyAllPlayers('drawOrb', clienttranslate('${player_name} draws an Orb card'), array(
                'player_name' => self::getActivePlayerName(),
                'player_id' => $player_id,
                'orbcard_type' =>  $this->orb_to_categ($card['type']),
                'deck' => $deck
               ));
        } else {
            throw new feException(self::_("There is no remaining orb card : you should pass instead."), true);
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

        self::notifyAllPlayers('changeOrbPriority', '', array('priority' => $player_to_priority));

        if ($bPass) {
            $this->gamestate->nextState('orbPass');
        }
    }

    function orbskip()
    {
        self::checkAction('orbSkip');
        // Skip this phase
        $state = $this->gamestate->state()['name'];
        if ($state == 'orbActionMove') {
            if (self::getGameStateValue('orbteamhasmoved')) {
                throw new feException("You cannot skip the action if you have moved a team");
            }
            $player_id = self::getActivePlayerId();
            if (!$this->orbcards->countCardInLocation('hand', $player_id)) {
                self::notifyAllPlayers('simpleNote', clienttranslate('${player_name} has no orb card, skipping the May Play Orb Card action'), [
                    'player_name' => self::getActivePlayerName()]);
                self::notifyPlayer($player_id, 'showMessage', '', [
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
            self::notifyAllPlayers('simpleNote', clienttranslate('${player_name} has no orb card, skipping the May Play Orb Card action'), [
                'player_name' => self::getActivePlayerName()]);
            self::notifyPlayer($player_id, 'showMessage', '', [
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
            throw new feException("this team does not exists");
        }

        if ($team['team_player'] != self::getActivePlayerId()) {
            throw new feException("this team does not belong to you");
        }

        if ($team['team_cannotmove'] == 1) {
            throw new feException(self::_("This team discovered an artifact this turn and cannot move again."), true);
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
            throw new feException("this team does not exists");
        }

        if ($team['team_player'] != self::getActivePlayerId()) {
            throw new feException("this team does not belong to you");
        }

        if ($x == $team['x'] && $y == $team['y']) {
            $this->gamestate->nextState('unselect');
        } else {
            if (! isset($moves[ $x.'_'.$y ])) {
                throw new feException(self::_("This is imposible to move here"), true);
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

            self::notifyAllPlayers('simpleNote', clienttranslate('${player_name} moves a team'), array(
                'player_name' => self::getActivePlayerName()
           ));

            foreach ($path as $step) {
                self::notifyAllPlayers('moveTeam', '', array(
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

                self::notifyPlayer($player_id, 'pickArtefact', '', array(
                    'card' => $artefact,
                    'x' => $x,
                    'y' => $y
               ));

                self::notifyAllPlayers('destroyArtefact', clienttranslate('${player_name} picks and artefact'), array(
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
                        self::notifyPlayer($player_id, 'pickOrbCards', '', array(
                            'cards' => array($card)
                           ));

                        $deck = self::getCollectionFromDB("SELECT card_type_arg, COUNT(*) FROM orbcard WHERE card_location='deck' GROUP BY card_type_arg", true);

                        self::notifyAllPlayers('drawOrb', clienttranslate('Breeding tube: ${player_name} draws an Orb card and must place it immediately.'), array(
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
                        self::notifyAllPlayers('simpleNote', clienttranslate("Breeding tube cannot apply because Orb card deck is empty"), array());
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
            throw new feException("this team does not exists");
        }

        if ($team['team_player'] != self::getActivePlayerId()) {
            throw new feException("this team does not belong to you");
        }

        $target = self::getUniqueValueFromDB("SELECT content FROM orb WHERE x='$x' AND y='$y'");

        if ($target == null) {
            throw new feException("Invalid destination");
        }
        if ($target != 'D' && $target != 'd') {
            throw new feException(self::_("You must choose a Sas (blue) square"), true);
        }

        $sql = "UPDATE orbteam SET team_x='$x', team_y='$y' WHERE team_id='$team_id'";
        self::DbQuery($sql);

        self::notifyAllPlayers('moveTeam', '', array(
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
            throw new feException("this team does not exists");
        }

        if ($team['team_player'] != self::getActivePlayerId()) {
            throw new feException("this team does not belong to you");
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
            throw new feException(self::_($msg), true);
        } else {
            self::notifyPlayer(self::getCurrentPlayerId(), 'putCardOnOrb', '', $args);
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
            throw new feException("Could not find the orb card at team place ".$team['x'].','.$team['y']);
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

        $state = $this->gamestate->state()['name'];
        $card = $this->orbcards->getCard($id);
        $args = [ 'card_id' => $id, 'x' => $x, 'y' => $y, 'ori' => $ori ];


        if ($card == null) {
            throw new feException("This orb card does not exists");
        }

        if ($bCheck) {
            if ($card['location'] != 'hand' && $card['location_arg'] != self::getActivePlayerId()) {
                throw new feException("This orb card is not in your hand");
            }
        }

        $teams = $this->getTeamsByLocation();
        $player_id = self::getActivePlayerId();

        $type = $this->orb_cards_types[ $card['type'] ];

        if ($state == 'additionalSas') {
            // Must check if this is a Sas card
            if ($card['type'] != 43 && $card['type'] != 44) {
                throw new feException(self::_("You must place the new airlock card which is in your hand"), true);
            }
        }
        if ($state == 'breedingTube') {
            $lastest = self::getUniqueValueFromDB("SELECT player_previously_played FROM player WHERE player_id='$player_id'");

            if ($lastest != $id) {
                throw new feException(self::_("You must choose the very latest orb card you just draw"), true);
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
                self::notifyAllPlayers("updateOrb", clienttranslate('${player_name} plays an Orb card'), array(
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
            self::notifyPlayer($player_id, 'putCardOnOrb', '',
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

            self::notifyAllPlayers("orbteam", clienttranslate('${player_name} has a new Survey team'), array(
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
                throw new feException(self::_("There is already a team there"), true);
            }


            // Acceptable spaces : on corners
            if (($x==1 && $y == 0)
             || ($x==1 && $y == 2)
             || ($x==-2 && $y == 0)
             || ($x==-2 && $y == 2)
            ) {
            } else {
                throw new feException(self::_("You must choose a corner of the sas"), true);
            }
        }

        // Okay, place a team there
        $sql = "INSERT INTO orbteam (team_x,team_y,team_player) VALUES ('$x','$y','$player_id')";
        self::DbQuery($sql);
        $team_id = self::DbGetLastId();

        self::notifyAllPlayers("orbteam", clienttranslate('${player_name} chooses a starting place for his/her team'), array(
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
            throw new feException("Invalid artifact");
        }
        if ($art['location'] != 'hand') {
            throw new feException("Invalid artifact");
        }
        if ($art['location_arg'] != $player_id) {
            throw new feException("Invalid artifact");
        }

        $state = $this->gamestate->state()['name'];
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
                throw new feException("Invalid artifact");
            }

            $bConsumeArt = true;
            $log = clienttranslate('${player_name} uses an artifact to cross one barrier');

            $this->incTeamCrossBarrier($team_id);
            $this->gamestate->nextState('useCrossBarier');
        }
        if ($reason == 'crosswall') {
            self::checkAction('useCrossBarier');

            if ($art['type'] != 3) {
                throw new feException("Invalid artifact");
            }

            $bConsumeArt = true;
            $log = clienttranslate('${player_name} uses an artifact to cross one wall or barrier');


            $this->incTeamCrossWall($team_id);
            $this->gamestate->nextState('useCrossBarier');
        }
        if ($reason == 'movebonus') {
            self::checkAction('useCrossBarier');

            if ($art['type'] != 5) {
                throw new feException("Invalid artifact");
            }

            $bConsumeArt = true;
            $log = clienttranslate('${player_name} uses an artifact to move 4 more squares');

            $this->addTeamMove(self::getGameStateValue('orbteam'), 4);
            $this->gamestate->nextState('useCrossBarier');
        }
        if ($reason == 'militaryboost') {
            self::checkAction('militaryboost');

            if ($art['type'] != 6 && $art['type'] != 8 && $art['type'] != 11 && $art['type'] != 12 && $art['type'] != 4) {
                throw new feException("Invalid artifact");
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
                self::notifyPlayer($player_id, 'updateTmpMilforce', '',
                                    array(
                                        'tmp' => self::getUniqueValueFromDB("SELECT player_tmp_milforce FROM player WHERE player_id='$player_id'"),
                                        'player' => $player_id
                                   ));
            }
        }
        if ($reason == 'sell') {
            self::checkAction('sell');

            if ($art['type'] != 2 && $art['type'] != 9) {
                throw new feException("Invalid artifact");
            }

            $bConsumeArt = true;
            $log = clienttranslate('${player_name} uses an artifact as an Alien resource');
            $price = 5;
            $good_type = 4;
            $price += $this->getSellBonusForGoodType($player_id, 4, 0);


            // Give cards to player
            $this->drawCardForPlayer($player_id, $price);
            self::notifyAllPlayers('drawCards_log', clienttranslate('${player_name} sells a ${good_name} for ${card_nbr} card(s)'),
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
                throw new feException("Invalid artifact");
            }

            $bConsumeArt = true;
            $log = clienttranslate('${player_name} uses an artifact as an Alien resource');
        }
        if ($reason == 'goodForMilitary') {
            self::checkAction('militaryboost');

            if ($art['type'] != 2 && $art['type'] != 9) {
                throw new feException("Invalid artifact");
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
                self::notifyAllPlayers('consumeArtifact', $log, $logarg);
            }

            $artpoints = $this->artefact_types[ $art['type'] ]['vp'];

            if ($artpoints > 0) {
                $pscore = $this->updatePlayerScore($player_id, $artpoints, false);
                self::incStat($artpoints, 'artefact_points', $player_id);

                self::notifyAllPlayers('updateScore', clienttranslate('${player_name} scores ${score_delta} with artifact(s).'),
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


            self::notifyAllPlayers('updateScore', clienttranslate('Greatest Contributor to War effort : ${player_name} scores 5 points.'),
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

            self::notifyAllPlayers('updateScore', clienttranslate('Greatest Admiral : ${player_name} scores 5 points.'),
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

            self::notifyAllPlayers('updateScore', clienttranslate('Visible feeding station : ${player_name} scores ${points_nbr} VP with ${nbr} artefacts x ${stations} visible stations.'),
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


    ///////////// DEBUG methods

    // Add this card to current player
    function ac($card_id)
    {
        if (!is_numeric($card_id)) {
            $card = $this->searchCard($card_id);
            $card_id = intval($card['type']);
        }

        $card_cost = $this->card_types[ $card_id ]['cost'];
        $player_id = self::getCurrentPlayerId();

        $sql = "INSERT INTO `card` (`card_type` ,`card_type_arg` ,`card_location` ,`card_location_arg`, `card_status`) ";
        $sql .= "VALUES ('$card_id', '$card_cost', 'hand', '".$player_id."', '0') ";
        self::DbQuery($sql);

        $card_id = self::DbGetLastId();
        $card = $this->cards->getCard($card_id);

        self::notifyPlayer($player_id, 'drawCards', '', array($card));
        $this->notifyUpdateCardCount();
    }

    function acs()
    {
        for ($i=270; $i<313; $i++) {
            $this->ac($i);
        }
    }

    function money()
    {
        for ($i=0; $i<=5; $i++) {
            $this->ac(bga_rand(1, 95));
        }
    }

    // Add this card to tableau
    function act($card_type_id, $no_good = false)
    {
        if (!is_numeric($card_type_id)) {
            $card = $this->searchCard($card_type_id);
            $card_type_id = intval($card['type']);
        }

        $card_cost = $this->card_types[ $card_type_id ]['cost'];
        $player_id = self::getCurrentPlayerId();

        $sql = "INSERT INTO `card` (`card_type` ,`card_type_arg` ,`card_location` ,`card_location_arg`, `card_status`) ";
        $sql .= "VALUES ('$card_type_id', $card_cost, 'tableau', $player_id, 0) ";
        self::DbQuery($sql);

        $card_id = self::DbGetLastId();
        $card = $this->cards->getCard($card_id);
        $card_type = $this->card_types[ $card_type_id ];

        self::notifyAllPlayers('playcard', '${card_name}: id=${card_id} ; type=${card_type}',
                                            array(
                                                "player" => $player_id,
                                                "card" => $card,
                                                "card_name" => $card_type['name'],
                                                "card_id" => $card_id,
                                                "card_type" => $card_type_id,
                                           ));
        if (!$no_good) {
            $this->windfallInitialProduction($card_id, $card_type);
        }
    }

    // 6 dev results
    function six()
    {
        $res = $this->getSixDevelopmentsPoints();

        $clean = array();
        foreach ($res['devpoints'] as $id => $val) {
            if ($val != 0) {
                $clean[ $id ] = $val;
            }
        }

        var_dump($clean);
        die('ok');
    }

    // Update military
    function mil()
    {
        $force = $this->updateMilforceIfNeeded(self::getCurrentPlayerId(), false);

        var_dump($force);
        die('ok');
    }

    // Card name can also be type number
    function searchCard($card_name)
    {
        $found = false;
        $card_type_ids = array();
        foreach ($this->card_types as $card_type_id => $card_type) {
            if ($card_type['name'] == $card_name || $card_type_id == $card_name) {
                $card_type_ids[] = $card_type_id;
            }
        }

        if (count($card_type_ids) == 0) {
            throw new feException("Can't find card $card_name");
        }

        $sql = "SELECT card_id FROM card WHERE card_type IN (";
        $sql .= implode(',', $card_type_ids);
        $sql .= ") AND card_location='deck' LIMIT 1";
        $card_id = self::getUniqueValueFromDB($sql);
        if ($card_id == null) {
            throw new feException("Can't find card $card_name in the deck");
        }
        return $this->cards->getCard($card_id);
    }

    // Draw a specific card from the deck and add it the current player hand
    // Can take a card name or card type id as argument
    function drawCard($card_name, $player_id = null)
    {
        if ($player_id == null) {
            $player_id = self::getCurrentPlayerId();
        }
        $card = $this->searchCard($card_name);
        $this->cards->moveCard($card['id'], 'hand', $player_id);
        self::notifyPlayer($player_id, 'drawCards', '', array($card));
        $this->notifyUpdateCardCount();
    }

    // Draw some cards for the current player
    function drawCards($n)
    {
        $this->drawCardForPlayer(self::getCurrentPlayerId(), $n);
    }

    function drawArtefact($type)
    {
        $player_id = self::getCurrentPlayerId();
        $artefact = $this->artefacts->pickCard($type, $player_id);
    }

    function debug_money() {
        $this->money();
    }

    function debug_ac(int $card_id) {
        $this->ac($card_id);
    }

    function debug_act(int $card_type_id, bool $no_good = false) {
        $this->act($card_type_id, $no_good);
    }

    // Add Orb card to player hand
    function debug_aoc(int $player_id, int $card_id)
    {
        $sql = "INSERT INTO `orbcard` (`card_type`, `card_type_arg`, `card_location` ,`card_location_arg`) ";
        $sql .= "VALUES ('$card_id', 0, 'hand', $player_id) ";
        self::DbQuery($sql);

        $card_id = self::DbGetLastId();
        $card = $this->orbcards->getCard($card_id);
        self::notifyPlayer($player_id, 'pickOrbCards', '', array(
            'cards' => array($card)
            ));
    }

    // Add this artefact to current player
    function debug_aa(int $player_id, int $artefact_id)
    {
        $sql = "INSERT INTO `artefact` (`card_type`, `card_type_arg`, `card_location`, `card_location_arg`) VALUES ('$artefact_id', 0, 'hand', $player_id) ";
        self::DbQuery($sql);
    }

    function debug_endGame()
    {
        self::setGameStateValue('remainingVp', 0);
    }

    public function debug_goToState(int $state = 98) {
      $this->gamestate->jumpToState($state);
    }

    function debug_dc(int $card_id) {
        $this->drawCard($card_id);
    }

}
