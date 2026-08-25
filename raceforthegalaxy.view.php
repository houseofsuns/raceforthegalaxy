<?php
 /**
  * raceforthegalaxy.view.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  *
  *
  * raceforthegalaxy main static view construction
  *
  */

  require_once(APP_BASE_PATH."view/common/game.view.php");

class view_raceforthegalaxy_raceforthegalaxy extends game_view
{
    function getGameName()
    {
        return "raceforthegalaxy";
    }

    function build_page($viewArgs)
    {
        // Get players
        $players = $this->game->loadPlayersBasicInfos();

        $currentPlayerId = $this->getCurrentPlayerId();

        $this->page->begin_block("raceforthegalaxy_raceforthegalaxy", "player");
        $this->page->begin_block("raceforthegalaxy_raceforthegalaxy", "priority");
        foreach ($players as $player_id => $player) {
            if ($player_id != $currentPlayerId) {
                $this->page->insert_block("player", array("PLAYER_ID" => $player['player_id'],
                                                          "PLAYER_NAME" => $player['player_name'],
                                                          "TABLEAU_NAME" => sprintf(self::_("%s's tableau"), $player['player_name'])));
            }

            $this->page->insert_block("priority", array("PLAYER_ID" => $player['player_id'],
                                                        "COLOR" => $player['player_color']));
        }

        $this->tpl['CONSUME_HELP'] = self::_("Click on a good, then click on the card you want to use to consume this good.");
        $this->tpl['OORT_HELP'] = self::_("Click on Alien Oort Cloud Refinery to change its type.");
        $this->tpl['USE_GAMBLING_WORLD'] = self::_("Use gambling world");
        $this->tpl['MY_DECK'] = self::_("My drafted deck");
        $this->tpl['DISCARD_FOR_MILITARY_HELP'] = self::_("You may discard cards to temporary increase your military force :");
        $this->tpl['CONSUME_FOR_MILITARY_HELP'] = self::_("You may consume a resource to temporary increase your military force :");
        $this->tpl['END_DISCARD'] = self::_("I'm done");

        $this->tpl['COMBINE'] = self::_("Combine");
        $this->tpl['DRAW'] = self::_("Draw");
        $this->tpl['WINDFALL'] = 'W';

        $this->tpl['ALIEN_ORB'] = self::_("Alien Orb");
        $this->tpl['ORB'] = self::_("Orb");
        $this->tpl['LB_ORB_DECK'] = self::_("Remaining orb cards:");
        $this->tpl['LB_MY_ORB_HAND'] = self::_("My Orb hand");

        $this->tpl['PLAY_WITH_EXPANSIONS'] = self::_("Explore the Galaxy!");
        $this->tpl['EXPLORE_GALAXY'] = self::_("New cards, new powers, new play modes ...").' '.self::_("Discover Race for the Galaxy expansions here on Board Game Arena !");
        $this->tpl['EXPLORE_GALAXY2'] = self::_("To access expansions you need to be a BGA Club member.");
        $this->tpl['NOT_A_MEMBER'] = self::_("Not a member yet?");

        $this->tpl['REPULSE_INVASION'] = self::_("Repulse invasion");
        $this->tpl['XENO_INVASION'] = self::_("Xeno Invasion");
        $this->tpl['CURRENT_WAVE'] = self::_("Current wave");
        $this->tpl['REMAINING_CARDS'] = self::_("remaining cards");
        $this->tpl['NUMBER_OF_EMPIRE_DEFEATS'] = self::_("Empire defeats number");
    }
}
