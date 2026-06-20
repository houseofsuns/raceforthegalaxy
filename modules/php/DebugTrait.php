<?php
 /**
  * DebugTrait.php
  *
  * Debug functionality available via the Studio interface.
  *
  */

use Bga\GameFramework\SystemException;

trait DebugTrait
{
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

        $this->notifyPlayer($player_id, 'drawCards', '', array($card));
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

        $this->notifyAllPlayers('playcard', '${card_name}: id=${card_id} ; type=${card_type}',
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
            throw new SystemException("Can't find card $card_name");
        }

        $sql = "SELECT card_id FROM card WHERE card_type IN (";
        $sql .= implode(',', $card_type_ids);
        $sql .= ") AND card_location='deck' LIMIT 1";
        $card_id = self::getUniqueValueFromDB($sql);
        if ($card_id == null) {
            throw new SystemException("Can't find card $card_name in the deck");
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
        $this->notifyPlayer($player_id, 'drawCards', '', array($card));
        $this->notifyUpdateCardCount();
    }

    // Draw some cards for the current player
    function drawCards($n)
    {
        $this->drawCardForPlayer(self::getCurrentPlayerId(), $n);
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
        $this->notifyPlayer($player_id, 'pickOrbCards', '', array(
            'cards' => array($card)
            ));
    }

    // Add this artefact to a player's hand
    function debug_aa(int $player_id, int $artefact_type_id)
    {
        $sql = "INSERT INTO `artefact` (`card_type`, `card_type_arg`, `card_location`, `card_location_arg`) VALUES ('$artefact_type_id', 0, 'hand', $player_id) ";
        self::DbQuery($sql);
        $card_id = self::DbGetLastId();
        $artefact = $this->artefacts->getCard($card_id);
        // Studio debug helpers bypass the normal orb pickup flow, so we need
        // to send the hand-update notification explicitly.
        $this->notifyPlayer($player_id, 'pickArtefact', '', array(
            'card' => $artefact,
        ));
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
