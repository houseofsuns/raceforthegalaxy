<?php
 /**
  * states.game.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  *
  *
  * Testlayout game states
  *
  */

use Bga\GameFramework\GameStateBuilder;

/*
*
*   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
*   in a very easy way from this configuration file.
*
*
*   States types:
*   _ manager: game manager can make the game progress to the next state.
*   _ game: this is an (unstable) game state. the game is going to progress to the next state as soon as current action has been accomplished
*   _ activeplayer: an action is expected from the activeplayer
*
*   Arguments:
*   _ possibleactions: array that specify possible player actions on this step (for state types "manager" and "activeplayer")
*       (correspond to actions names)
*   _ action: name of the method to call to process the action (for state type "game")
*   _ transitions: name of transitions and corresponding next state
*       (name of transitions correspond to "nextState" argument)
*   _ description: description is displayed on top of the main content.
*   _ descriptionmyturn (optional): alternative description displayed when it's player's turn
*
*/

$machinestates = array(

    1 => array(
        "name" => "gameSetup",
        "description" => clienttranslate("Game setup"),
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array("" => 100)
   ),

    ///// Drafting variant
    100 => array(
        "name" => "draftNewRound",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stDraftNewRound",
        "type" => "game",
        "transitions" => array("newround" => 101, "initialDiscard" => 2, "initialDiscardHomeWorld" => 3)
   ),
    101 => array(
        "name" => "draft",
        "description" => clienttranslate('Initial draft : Everyone must choose 1 card for their own deck'),
        "descriptionmyturn" => clienttranslate('Initial draft : ${you} must choose 1 card for your own deck'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array("draft"),
        "transitions" => array("draft" => 102,
            "initialDiscardHomeWorld" => 3) // Temporary fix
   ),
    102 => array(
        "name" => "draftNextCard",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stDraftNextCard",
        "type" => "game",
        "transitions" => array("endRound" => 100, "nextCard" => 101)
   ),




    ///// "normal" game starts
    2 => array(
            "name" => "initialDiscard",
            "description" => clienttranslate('Everyone must discard 2 cards from his hand'),
            "descriptionmyturn" => clienttranslate('${you} must discard 2 cards from your hand'),
            "action" => "stInitialDiscard",
            "type" => "multipleactiveplayer",
            "possibleactions" => array("initialdiscard"),
            "transitions" => array("phaseCleared" => 4)
   ),

    // Start world choice
        3 => array(
        "name" => "initialDiscardHomeWorld",
        "description" => clienttranslate('Everyone must discard one of their two starting worlds and 2 cards from their hand'),
        "descriptionmyturn" => clienttranslate('${you} must discard one of your two starting worlds and 2 cards from your hand'),
        "action" => "stInitialDiscardHome",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("initialdiscardhome", "initialdiscard"),
        "transitions" => array("phaseCleared" => 4)
   ),

    4 => array(
        "name" => "showTableau",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stShowTableau",
        "type" => "game",
        "transitions" => array("phaseCleared" => 5)
   ),

    5 => array(
       "name" => "setupFinished",
       "description" => '',
       "descriptionmyturn" => '',
       "action" => "stSetupFinished",
       "type" => "game",
       "transitions" => array("initialDiscardAncientRace" => 500, "initialDiscardScavenger" => 501, "initialOrb" => 6, "phaseCleared" => 10)
   ),

    500 => array(
            "name" => "initialDiscardAncientRace",
            "description" => clienttranslate('Ancient Race: ${actplayer} must discard an additional card'),
            "descriptionmyturn" => clienttranslate('Ancient Race: ${you} must discard an additional card'),
            "type" => "activeplayer",
            "possibleactions" => array("initialdiscard"),
            "transitions" => array("phaseCleared" => 5)
   ),

    501 => array(
        "name" => "initialDiscardScavenger",
        "description" => clienttranslate('Galactic Scavengers: ${actplayer} must place a card under Galactic Scavengers'),
        "descriptionmyturn" => clienttranslate('Galactic Scavengers: ${you} must place a card under Galactic Scavengers'),
        "type" => "activeplayer",
        "possibleactions" => array("initialdiscardScavenger"),
        "transitions" => array("phaseCleared" => 5)
   ),


    // Initial Orb play

    6 => array(
        "name" => "initialOrb",
        "description" => clienttranslate('${actplayer} must play an Orb card'),
        "descriptionmyturn" => clienttranslate('${you} must play an Orb card'),
        "type" => "activeplayer",
        "possibleactions" => array("orbPlay"),
        "transitions" => array("orbNextPlayer" => 7)
   ),
    7 => array(
        "name" => "initialTeam",
        "description" => clienttranslate('${actplayer} must place his/her team in a corner of the Orb sas'),
        "descriptionmyturn" => clienttranslate('${you} must place your team in a corner of the Orb sas'),
        "type" => "activeplayer",
        "possibleactions" => array("orbTeamplace"),
        "transitions" => array("orbTeamplace" => 8)
   ),
    8 => array(
        "name" => "initialOrbNextPlayer",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stInitialOrbNextPlayer",
        "type" => "game",
        "transitions" => array("next" => 6, "end" => 10)
   ),

    9 => array(
        "name" => "removedState",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stRemovedState",
        "type" => "game",
        "transitions" => array("next" => 5, "initialDiscardScavenger" => 501)
   ),


    //////////////////////////////////////////////////////////////////////////////////////:
    ////// NORMAL PLAY

    10 => array(
        "name" => "phaseChoice",
        "description" => clienttranslate('Everyone must choose a phase to play'),
        "descriptionmyturn" => clienttranslate('${you} must choose a phase to play'),
        "action" => "stPhaseChoice",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("choosePhase", "cancelPhase"),
        "args" => "argPhaseChoice",
        "updateGameProgression" => true,
        "transitions" => array("phaseCleared" => 11)
   ),
    11 => array(
        "name" => "phaseChoiceSignal",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stPhaseChoiceSignal",
        "type" => "game",
        "transitions" => array("startRound" => 200, "crystal" => 12)
   ),

    // Additional phase choice for psi-crystal
    12 => array(
        "name" => "phaseChoiceCrystal",
        "description" => clienttranslate('Psi-Crystal World : ${actplayer} must choose a phase to play'),
        "descriptionmyturn" => clienttranslate('Psi-Crystal World : ${you} must choose a phase to play'),
        "type" => "activeplayer",
        "args" => "argPhaseChoice",
        "possibleactions" => array("choosePhase"),
        "transitions" => array("phaseCleared" => 11)
   ),

    // 15=>18 : Orb play (Alien artifact)

    15 => array(
        "name" => "orbActionMove",
        "description" => clienttranslate('${actplayer} may choose an Orb exploration team to move'),
        "descriptionmyturn" => clienttranslate('${you} may choose an Orb exploration team to move'),
        "type" => "activeplayer",
        "args" => "argOrbAction",
        "possibleactions" => array("orbMoveSelect", "orbPass", "orbSkip", "militaryboost", "orbEndMoveAction"),
        "transitions" => array("orbMove" => 155, "orbSkip" => 16, "orbPlay" => 16, "orbPass" => 188, "orbDraw" => 17)
   ),
    155 => array(
        "name" => "orbActionMoveDest",
        "description" => clienttranslate('${actplayer} may move an Orb exploration team (${orbmoves} squares)'),
        "descriptionmyturn" => clienttranslate('${you} may move an Orb exploration team (${orbmoves} squares)'),
        "type" => "activeplayer",
        "args" => "argOrbActionMove",
        "possibleactions" => array("orbMoveDest", "useCrossBarier", "orbStop", "orbEndMoveAction"),
        "transitions" => array("orbPlay" => 16, "unselect" => 15, "continue" => 155, "useCrossBarier" => 155, "breedingTube" => 156, "orbDraw" => 17)
   ),
    156 => array(
        "name" => "breedingTube",
        "description" => clienttranslate('Breeding tube: ${actplayer} must play his/her latest Orb card'),
        "descriptionmyturn" => clienttranslate('Breeding tube: ${you} must play the Orb card you just draw.'),
        "type" => "activeplayer",
        "args" => "argBreedingTube",
        "possibleactions" => array("orbPlay", "onlyLatest", "orbEndMoveAction"),
        "transitions" => array("orbPlay" => 16, "moveOtherTeam" => 15, "orbDraw" => 17)
   ),

    16 => array(
        "name" => "orbActionPlay",
        "description" => clienttranslate('${actplayer} may play an Orb card'),
        "descriptionmyturn" => clienttranslate('${you} may play an Orb card'),
        "type" => "activeplayer",
        "args" => "argOrbAction",
        "possibleactions" => array("orbPlay", "orbSkip"),
        "transitions" => array("orbDraw" => 17, "orbSkip" => 17, "orbNextPlayer" => 18)
   ),
    17 => array(
        "name" => "orbActionDraw",
        "description" => clienttranslate('${actplayer} may draw an Orb card'),
        "descriptionmyturn" => clienttranslate('${you} may draw an Orb card'),
        "type" => "activeplayer",
        "args" => "argOrbAction",
        "possibleactions" => array("orbDraw", "orbSkip"),
        "transitions" => array("orbDraw" => 18, "orbSkip" => 18)
   ),
    188 => array(
        "name" => "orbActionBackToSas",
        "description" => clienttranslate('${actplayer} may move his/her team to a Sas'),
        "descriptionmyturn" => clienttranslate('${you} may move your team to a Sas'),
        "type" => "activeplayer",
        "possibleactions" => array("orbBackToSas" , "orbSkip"),
        "transitions" => array("orbSkip" => 18)
   ),


    18 => array(
        "name" => "orbNextPlayer",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stOrbNextPlayer",
        "type" => "game",
        "transitions" => array("next" => 15, "end" => 20)
   ),



    // Search action

    200 => array(
        "name" => "searchActionCheck",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stSearchActionCheck",
        "type" => "game",
        "transitions" => array("no_more_actions" => 19, "search_action"=>201)
   ),
    201 => array(
        "name" => "searchAction",
        "description" => clienttranslate('${actplayer} must select a category of card to search for'),
        "descriptionmyturn" => clienttranslate('${you} must select a category of card to search for'),
        "type" => "activeplayer",
        "possibleactions" => array("searchAction"),
        "transitions" => array("done" => 200, "maykeep"=>202)
   ),
    202 => array(
        "name" => "searchActionChoose",
        "description" => clienttranslate('${actplayer} must choose to keep this card or search for another one'),
        "descriptionmyturn" => clienttranslate('${you} must choose to keep this card or search for another one'),
        "type" => "activeplayer",
        "possibleactions" => array("searchActionChoose"),
        "transitions" => array("done" => 200, "keep" => 200, "another"=>201, "maykeep"=>202)
   ),


    // Explore

    19 => array(
        "name" => "exploreconsume",
        "description" => clienttranslate('Explore: Some players may use discard for prestige powers'),
        "descriptionmyturn" => clienttranslate('Explore: ${you} may use discard for prestige powers'),
        "action" => "stExploreConsume",
        "type" => "multipleactiveplayer",
        "args" => "argConsume",
        "possibleactions" => array("exploreconsume", "noMoreConsume"),
        "transitions" => array("phaseCleared" => 20, "phaseNotSelected" => 30, "orbPlay" => 15)
   ),


    20 => array(
        "name" => "explore",
        "description" => clienttranslate('Explore: Everyone must choose what cards to keep'),
        "descriptionmyturn" => clienttranslate('Explore: ${you} must choose ${titlearg1} cards to keep'),
        "action" => "stExplore",
        "args" => "argExplore",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("exploreCardChoice"),
        "transitions" => array("phaseNotSelected" => 30, "phaseCleared" => 21)
   ),

    21 => array(
        "name" => "post_explore_process",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stPostExploreProcess",
        "type" => "game",
        "transitions" => array("" => 30)
   ),


    // Develop
    30 => array(
        "name" => "develop",
        "description" => clienttranslate('Develop: Everyone can place a development'),
        "descriptionmyturn" => clienttranslate('Develop: ${you} can place a development'),
        "action" => "stDevelop",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("nothingToPlay","develop", "scavenge"),
        "transitions" => array("developdiscard" => 230, "phaseNotSelected" => 40, "phaseCleared" => 31)
   ),
    230 => array(
        "name" => "developdiscard",
        "description" => clienttranslate('Develop: some players must discard cards'),
        "descriptionmyturn" => clienttranslate('Develop: ${you} must discard ${titlearg1} card(s)'),
        "action" => "stDevelopDiscard",
        "args" => "argDevelopDiscard",
        "type" => "multipleactiveplayer",


        "possibleactions" => array("developdiscard"),
        "transitions" => array("done" => 231)
   ),
    231 => array(
        "name" => "develop",
        "description" => clienttranslate('Develop: Everyone can place a development'),
        "descriptionmyturn" => clienttranslate('Develop: ${you} can place a development'),
        "action" => "stDevelopNewActive", // Note : same as develop action except than state 230 has been successfully passed!
        "type" => "multipleactiveplayer",
        "possibleactions" => array("nothingToPlay","develop", "scavenge"),
        "transitions" => array("phaseNotSelected" => 40, "phaseCleared" => 311)
   ),

    311 => array(
        "name" => "afterdevelopcheck",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stAfterDevelopCheck",
        "type" => "game",
        "transitions" => array("no_more_check" => 31, "orb_airlock"=>312)
   ),
    312 => array(
        "name" => "additionalSas",
        "description" => clienttranslate('Alien Research Ship : ${actplayer} must add a Sas card to the Orb'),
        "descriptionmyturn" => clienttranslate('Alien Research Ship : ${you} must add a Sas card to the Orb'),
        "type" => "activeplayer",
        "possibleactions" => array("orbPlay", "onlySasCard"),
        "transitions" => array("orbNextPlayer" => 311)
   ),


    31 => array(
        "name" => "developprocess",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stDevelopProcess",
        "type" => "game",
        "transitions" => array("continue" => 40, "repeat"=>30)
   ),

    // Settle
    40 => array(
        "name" => "settle",
        "description" => clienttranslate('Settle: Everyone can place a world'),
        "descriptionmyturn" => clienttranslate('Settle: ${you} can place a world'),
        "action" => "stSettle",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("nothingToPlay","settle", "militaryboost", "scavenge"),
        "transitions" => array("phaseNotSelected" => 50, "phaseCleared" => 43)
   ),
    41 => array(
        "name" => "discardtoputgood",
        "description" => clienttranslate('${world}: Some player may discard a card to put a good on it'),
        "descriptionmyturn" => clienttranslate('${world}: ${you} may discard a card to put a good on it'),
        "action" => "stDiscardToPutGood",
        "args" => "argDiscardToPutGood",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("discardToPutGood", "nothingToPlay"),
        "transitions" => array("done" => 341, "phaseCleared" => 341)
   ),
    341 => array(
        "name" => "settlediscard",
        "description" => clienttranslate('Imperium Fuel Depot: Some player must discard a card'),
        "descriptionmyturn" => clienttranslate('Imperium Fuel Depot: ${you} must discard a card'),
        "action" => "stSettleDiscard",
        "args" => "argSettleDiscard",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("settlediscard"),
        "transitions" => array("done" => 241)
   ),

    241 => array(
        "name" => "settleprocess",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stSettleProcess",
        "type" => "game",
        "transitions" => array("continue" => 50, "repeat"=>40, "improvedlogistics" => 42, "sneakAttack" => 242, "convoy" => 342, "terraforming_project" => 442, "terraformingEngineers" => 542)
   ),


    42 => array(
        "name" => "settle",
        "description" => clienttranslate('Improved logistics : some players may place another world'),
        "descriptionmyturn" => clienttranslate('Improved logistics: ${you} may place another world'),
        "action" => "stSettle",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("nothingToPlay","settle", "militaryboost", "scavenge"),
        "transitions" => array("phaseNotSelected" => 50, "phaseCleared" => 43)
   ),
    242 => array(
        "name" => "settle",
        "description" => clienttranslate('Rebel Sneak Attack : some players may place another world'),
        "descriptionmyturn" => clienttranslate('Rebel Sneak Attack: ${you} may discard Rebel Sneak Attack to place a military world'),
        "action" => "stSettle",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("nothingToPlay","settle", "militaryboost","onlymilitarysettle"),
        "transitions" => array("phaseNotSelected" => 50, "phaseCleared" => 41)
   ),
    342 => array(
        "name" => "settle",
        "description" => clienttranslate('Imperium Supply Convoy : some players may conquer another world'),
        "descriptionmyturn" => clienttranslate('Imperium Supply Convoy: ${you} may conquer another military world with remaining military'),
        "action" => "stSettle",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("nothingToPlay","settle", "militaryboost","onlyremainingmilitary"),
        "transitions" => array("phaseNotSelected" => 50, "phaseCleared" => 41)
   ),
    442 => array(
        "name" => "settle",
        "description" => clienttranslate('Terraforming Project : some players may settle another world'),
        "descriptionmyturn" => clienttranslate('Terraforming Project: ${you} may discard Terraforming project to settle another (non military, non alien) world for free'),
        "action" => "stSettle",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("nothingToPlay","settle", "militaryboost","onlycivilnonalien"),
        "transitions" => array("phaseNotSelected" => 50, "phaseCleared" => 41)
   ),
    542 => array(
        "name" => "settle",
        "description" => clienttranslate('Terraforming Engineers: some players may replace a world'),
        "descriptionmyturn" => clienttranslate('Terraforming Engineers: ${you} may replace a world'),
        "action" => "stSettle",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("nothingToPlay","replaceWorld"),
        "transitions" => array("phaseNotSelected" => 50, "phaseCleared" => 41)
   ),


    // (Settle/takeovers)
    43 => array(
        "name" => "settletakeovercheck",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stSettleTakeoverCheck",
        "type" => "game",
        "transitions" => array("no_more_takeover" => 41, "resolve_takeover"=>49)
   ),
    49 => array(
        "name" => "takeover_maydefeat",
        "description" => clienttranslate('Pan-Galactic Security Council : ${actplayer} may defeat the following takeover : `${target_world}` (defense ${cost}) with `${takeovercard_name}` (attacker force : ${attacker_force})'),
        "descriptionmyturn" => clienttranslate('Pan-Galactic Security Council : ${you} may defeat the following takeover : `${target_world}` (defense ${cost}) with `${takeovercard_name}` (attacker force : ${attacker_force})'),
        "action" => "stPanGalacticSecurityCouncil",
        "args" => "argsTakeover",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("pangalacticsecuritycouncil"),
        "transitions" => array("cancel" => 43, "continue" => 44)
   ),

    44 => array(
        "name" => "takeover_attackerboost",
        "description" => clienttranslate('Takeover of `${target_world}` (defense ${cost}) with `${takeovercard_name}` (attack ${attacker_force}): ${actplayer} may boost his military force'),
        "descriptionmyturn" => clienttranslate('Takeover of `${target_world}` (defense ${cost}) with `${takeovercard_name}` (attack ${attacker_force}): ${you} may boost your military force'),
        "action" => "stTakeOverMilitaryBoost",
        "args" => "argsTakeover",
        "type" => "activeplayer",
        "possibleactions" => array("militaryboost"),
        "transitions" => array("done" => 48, "boost" => 44)
   ),
    48 => array(
        "name" => "settletakeover_nextboost",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stSettleTakeOverNextBoost",
        "type" => "game",
        "transitions" => array("" => 45)
   ),

    45 => array(
        "name" => "takeover_defenderboost",
        "description" => clienttranslate('Takeover of `${target_world}` (defense ${cost}) with `${takeovercard_name}` (attack ${attacker_force}): ${actplayer} may boost his military force'),
        "descriptionmyturn" => clienttranslate('Takeover of `${target_world}` (defense ${cost}) with `${takeovercard_name}` (attack ${attacker_force}): ${you} may boost your military force'),
        "action" => "stTakeOverMilitaryBoost",
        "args" => "argsTakeover",
        "type" => "activeplayer",
        "possibleactions" => array("militaryboost"),
        "transitions" => array("done" => 46, "boost" => 45)
   ),
    46 => array(
        "name" => "settletakeoverresolution",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stSettleTakeoverResolution",
        "type" => "game",
        "transitions" => array("resolved" => 43)
   ),



    // Consume
    50 => array(
        "name" => "consumesell",
        "description" => clienttranslate('Consume: Some players must sell a resource'),
        "descriptionmyturn" => clienttranslate('Consume: ${you} must seel a resource'),
        "action" => "stConsumesell",
        "type" => "multipleactiveplayer",
        "args" => "argConsumesell",
        "possibleactions" => array("sell"),
        "transitions" => array("phaseNotSelected" => 60,   // Note: meaning = no "consume" phase at all
                                "nosell" => 51,             // Note: meaning = no player in position to sell (because no resource or no "seel" special phase)
                                "sellcleared" => 51         // Note: meaning = all players who can make a sell made a sell
                                )
   ),
    51 => array(
        "name" => "consume",
        "description" => clienttranslate('Consume: Everyone must consume resources'),
        "descriptionmyturn" => clienttranslate('Consume: ${you} must consome resources'),
        "action" => "stConsume",
        "type" => "multipleactiveplayer",
        "args" => "argConsume",
        "possibleactions" => array("consume", "noMoreConsume", "gamble", "selectGood"),
        "transitions" => array("phaseCleared" => 52)
   ),
    52 => array(
        "name" => "consumeprocess",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stConsumeProcess",
        "type" => "game",
        "transitions" => array("" => 60)
   ),

    // Produce
    60 => array(
        "name" => "productionintro",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stProductionIntro",
        "type" => "game",
        "transitions" => array("phaseNotSelected" => 70, "production_next" => 69, "warEffort" => 661)
   ),
    661 => array(
        "name" => "warEffort",
        "description" => clienttranslate('Production: Some players may discard goods to contribute to War effort'),
        "descriptionmyturn" => clienttranslate('Production: ${you} may discard goods to contribute to War effort (+1 VP / good)'),
        "action" => "stWarEffort",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("warEffort", "nothingToPlay"),
        "transitions" => array("phaseCleared" => 69)
   ),

    69 => array(
        "name" => "productionprocess",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stProductionProcess",
        "type" => "game",
        "transitions" => array("production_done" => 61, "production_done_xeno" => 161)
   ),


    61 => array(
        "name" => "productionwindfall",
        "description" => clienttranslate('Production: Some players may use produce powers'),
        "descriptionmyturn" => '',
        "action" => "stProductionWindfall",
        "args" => "argProductionWindfall",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("productionwindfall", "nowindfallproduction"),
        "transitions" => array("phaseCleared" => 62)
   ),
    161 => array(
        "name" => "productionwindfall",
        "description" => clienttranslate('Production: Some players may use produce powers'),
        "descriptionmyturn" => '',
        "action" => "stProductionWindfall",
        "args" => "argProductionWindfall",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("productionwindfall", "nowindfallproduction", "repairDamaged", "selectGood"),
        "transitions" => array("phaseCleared" => 62)
   ),

    62 => array(
        "name" => "postproductionprocess",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stPostProductionProcess",
        "type" => "game",
        "transitions" => array("" => 70)
   ),

    // Turn end
    70 => array(
        "name" => "endturndiscard",
        "description" => clienttranslate('End of round : Players with more than 10 cards must discard'),
        "descriptionmyturn" => clienttranslate('End of round : ${you} must discard ${titlearg1} cards'),
        "type" => "multipleactiveplayer",
        "action" => "stEndTurnDiscard",
        "args" => "argEndTurnDiscard",
        "possibleactions" => array("endturndiscard"),
        "transitions" => array("allPlayersValid" => 71, "invasionGame" => 171)
   ),
    71 => array(
        "name" => "endTurn",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stEndTurn",
        "type" => "game",
        "transitions" => array("endGame" => 98, "nextTurn" => 10)
   ),

    // Invasion game
    171 => array(
        "name" => "invasionGame",
        "description" => '',
        "descriptionmyturn" => '',
        "action" => "stInvasionGame",
        "type" => "game",
        "transitions" => array("invasionResolution" => 172, "nextTurn" => 71)
   ),
    172 => array(
        "name" => "invasionGameResolution",
        "description" => clienttranslate('Xeno Invasion: Some player may use temporary Xeno defense powers'),
        "descriptionmyturn" => clienttranslate('Xeno Invasion (force: ${titlearg1}): ${you} may use temporary Xeno defense powers (your force: ${titlearg2})'),
        "action" => "stInvasionGameResolution",
        "type" => "multipleactiveplayer",
        "args" => "argInvasionGameResolution",
        "possibleactions" => array("resolveInvasion", "militaryboost"),
        "transitions" => array("invasion_end" => 173)
   ),
    173 => array(
        "name" => "invasionGameDamage",
        "description" => clienttranslate('Xeno Invasion: Some players must choose a world to damage'),
        "descriptionmyturn" => clienttranslate('Xeno Invasion: ${you} must choose a world to damage due to your defeat'),
        "action" => "stInvasionGameDamage",
        "type" => "multipleactiveplayer",
        "possibleactions" => array("chooseDamage"),
        "transitions" => array("damages_ok" => 71)
   ),



    // Game end

    98 => GameStateBuilder::endScore()->build(),

);
