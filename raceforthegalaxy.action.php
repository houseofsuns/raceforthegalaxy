<?php
 /**
  * raceforthegalaxy.action.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  *
  *
  * raceforthegalaxy main action entry point
  *
  */


class action_raceforthegalaxy extends APP_GameAction
{
    private function startAction()
    {
        self::setAjaxMode();
        $this->game->saveInitialSixCostDevPointsState();
    }

    public function __default()
    {
        if (self::isArg('notifwindow')) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
        } else {
            $this->view = "raceforthegalaxy_raceforthegalaxy";
            self::trace("Complete reinitialization of board game");
        }
    }
    public function initialdiscard()
    {
        $this->startAction();

        $money_raw = self::getArg("cards", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($money_raw, -1) == ';') {
            $money_raw = substr($money_raw, 0, -1);
        }
        if ($money_raw == '') {
            $money = array();
        } else {
            $money = explode(';', $money_raw);
        }

        $result = $this->game->initialdiscard($money);
        self::ajaxResponse();
    }
    public function initialdiscardhome()
    {
        $this->startAction();

        $start_world = self::getArg("start_world", AT_posint, true);
        $cards_raw = self::getArg("cards", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($cards_raw, -1) == ';') {
            $cards_raw = substr($cards_raw, 0, -1);
        }
        if ($cards_raw == '') {
            $cards = array();
        } else {
            $cards = explode(';', $cards_raw);
        }

        $this->game->initialdiscardhome($start_world, $cards);

        self::ajaxResponse();
    }
    public function choosePhase()
    {
        $this->startAction();
        $phase = self::getArg("phase", AT_posint, true);
        $bonus = self::getArg("bonus", AT_posint, true);
        $bonuscard = self::getArg("cardbonus", AT_bool, false, false);
        $this->game->choosePhase($phase, $bonus, $bonuscard);
        self::ajaxResponse();
    }
    public function cancelPhase()
    {
        $this->startAction();
        $this->game->cancelPhase();
        self::ajaxResponse();
    }
    function defeatTakeover()
    {
        $this->startAction();
        $choice = self::getArg("choice", AT_bool, true);
        $this->game->defeatTakeover($choice);
        self::ajaxResponse();
    }
    function search()
    {
        $this->startAction();
        $category = self::getArg("category", AT_posint, true);
        $this->game->search($category);
        self::ajaxResponse();
    }
    function searchchoose()
    {
        $this->startAction();
        $action = self::getArg("actio", AT_posint, true);
        $this->game->searchchoose($action);
        self::ajaxResponse();
    }
    public function exploreCardChoice()
    {
        $this->startAction();
        $cards_raw = self::getArg("tokeep", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($cards_raw, -1) == ';') {
            $cards_raw = substr($cards_raw, 0, -1);
        }
        if ($cards_raw == '') {
            $cards = array();
        } else {
            $cards = explode(';', $cards_raw);
        }
        $result = $this->game->exploreCardChoice($cards);
        self::ajaxResponse();
    }

    public function nothingToPlay()
    {
        $this->startAction();
        $this->game->nothingToPlay();
        self::ajaxResponse();
    }
    public function playCard()
    {
        $this->startAction();
        $card = self::getArg("card", AT_posint, true);
        $this->game->playCard($card);
        self::ajaxResponse();
    }
    public function playCardAndPay()
    {
        $this->startAction();
        $card = self::getArg("card", AT_posint, true);

        $money_raw = self::getArg("money", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($money_raw, -1) == ';') {
            $money_raw = substr($money_raw, 0, -1);
        }
        if ($money_raw == '') {
            $money = array();
        } else {
            $money = explode(';', $money_raw);
        }

        $good_raw = self::getArg("goods", AT_numberlist, false, '');
        // Removing last ';' if exists
        if (substr($good_raw, -1) == ';') {
            $good_raw = substr($good_raw, 0, -1);
        }
        if ($good_raw == '') {
            $good = array();
        } else {
            $good = explode(';', $good_raw);
        }

        $art_raw = self::getArg("arts", AT_numberlist, false, '');
        // Removing last ';' if exists
        if (substr($art_raw, -1) == ';') {
            $art_raw = substr($art_raw, 0, -1);
        }
        if ($art_raw == '') {
            $art = array();
        } else {
            $art = explode(';', $art_raw);
        }


        $options = array();
        if (self::isArg('colonyship')) {   // Colonize with colonyship
            $options['colonyship'] = self::getArg('colonyship', AT_posint, true);
        }
        if (self::isArg('cloaking')) {   // Colonize with cloaking technology
            $options['cloaking'] = self::getArg('cloaking', AT_posint, true);
        }
        if (self::isArg('scavenger')) {
            $options['scavenger'] = self::getArg('scavenger', AT_posint, true);
        }
        if (self::isArg('settlereplace')) {
            $options['settlereplace'] = self::getArg('settlereplace', AT_posint, true);
        }
        if (self::isArg('oort')) {
            $options['oort'] = self::getArg('oort', AT_posint, true);
        }
        if (self::isArg('rdcrashprogram')) {   // Use R&D crash program to reduce cost
            $options['rdcrashprogram'] = self::getArg('rdcrashprogram', AT_posint, true);
        }
        if (self::isArg('mode')) {  // explicitly select 'military' or 'pay'
            $options['mode'] = self::getArg('mode', AT_alphanum, true);
        }

        $options['goods'] = $good;
        $options['arts'] = $art;

        $this->game->playCardAndPay($card, $money, $options);

        self::ajaxResponse();
    }
    public function militarytactics()
    {
        $this->startAction();
        $card = self::getArg("card", AT_posint, true);
        $discard_raw = self::getArg("discard", AT_numberlist, false, null);
        // Removing last ';' if exists
        if (substr($discard_raw, -1) == ';') {
            $discard_raw = substr($discard_raw, 0, -1);
        }
        if ($discard_raw == '') {
            $discard = array();
        } else {
            $discard = explode(';', $discard_raw);
        }
        $this->game->militaryTactics($card, $discard);
        self::ajaxResponse();
    }
    public function prestigeformilitary()
    {
        $this->startAction();
        $card = self::getArg("card", AT_posint, true);
        $this->game->prestigeformilitary($card);
        self::ajaxResponse();
    }

    public function sell()
    {
        $this->startAction();
        $card = self::getArg("card", AT_posint, true);
        $this->game->sell($card);
        self::ajaxResponse();
    }
    public function warEffort()
    {
        $this->startAction();
        $card = self::getArg("card", AT_posint, true);
        $this->game->warEffort($card);
        self::ajaxResponse();
    }


    public function consume()
    {
        $this->startAction();
        $good = self::getArg("good", AT_alphanum, true);
        $world = self::getArg("world", AT_posint, true);
        $this->game->consume($good, $world);
        self::ajaxResponse();
    }
    public function consumecard()
    {
        $this->startAction();
        $cards_raw = self::getArg("cards", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($cards_raw, -1) == ';') {
            $cards_raw = substr($cards_raw, 0, -1);
        }
        if ($cards_raw == '') {
            $cards = array();
        } else {
            $cards = explode(';', $cards_raw);
        }

        $consumecard_card_id = self::getArg("consumecard_card_id", AT_posint, false, null);
        $result = $this->game->consumecard($cards, $consumecard_card_id);
        self::ajaxResponse();
    }
    public function rviGambling()
    {
        $this->startAction();
        $ante = self::getArg("ante_card_id", AT_posint, true);
        $this->game->rviGambling($ante);
        self::ajaxResponse();
    }
    public function consumeprestige()
    {
        $this->startAction();
        $consumecard_card_id = self::getArg("consumecard_card_id", AT_posint, true);

        $result = $this->game->consumeprestige($consumecard_card_id);
        self::ajaxResponse();
    }
    public function gambling()
    {
        $this->startAction();
        $number = self::getArg("gambling", AT_posint, true);
        $this->game->gambling($number);
        self::ajaxResponse();
    }
    public function stopConsumption()
    {
        $this->startAction();
        $this->game->stopConsumption();
        self::ajaxResponse();
    }

    public function noWindfallProduction()
    {
        $this->startAction();
        $this->game->noWindfallProduction();
        self::ajaxResponse();
    }
    public function windfallProduction()
    {
        $this->startAction();




        $card = self::getArg("card", AT_posint, true);
        $discard_id = self::getArg("discard", AT_posint, false, null);

        $options = array();
        if (self::isArg('oort')) {
            $options['oort'] = self::getArg('oort', AT_posint, true);
        }

        $this->game->windfallProduction($card, $discard_id, $options);
        self::ajaxResponse();
    }
    public function produceifdiscard()
    {
        $this->startAction();
        $card = self::getArg("card", AT_posint, true);
        $discard = self::getArg("discard", AT_posint, true);
        $this->game->produceifdiscard($card, $discard);
        self::ajaxResponse();
    }

    public function endRoundDiscard()
    {
        $this->startAction();
        $cards_raw = self::getArg("cards", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($cards_raw, -1) == ';') {
            $cards_raw = substr($cards_raw, 0, -1);
        }
        if ($cards_raw == '') {
            $cards = array();
        } else {
            $cards = explode(';', $cards_raw);
        }
        $result = $this->game->endRoundDiscard($cards);
        self::ajaxResponse();
    }
    public function developdiscard()
    {
        $this->startAction();
        $cards_raw = self::getArg("cards", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($cards_raw, -1) == ';') {
            $cards_raw = substr($cards_raw, 0, -1);
        }
        if ($cards_raw == '') {
            $cards = array();
        } else {
            $cards = explode(';', $cards_raw);
        }
        $result = $this->game->developdiscard($cards);
        self::ajaxResponse();
    }
    public function settlediscard()
    {
        $this->startAction();
        $cards_raw = self::getArg("cards", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($cards_raw, -1) == ';') {
            $cards_raw = substr($cards_raw, 0, -1);
        }
        if ($cards_raw == '') {
            $cards = array();
        } else {
            $cards = explode(';', $cards_raw);
        }
        $result = $this->game->settlediscard($cards);
        self::ajaxResponse();
    }
    public function wormhole()
    {
        $this->startAction();
        $result = $this->game->wormhole();
        self::ajaxResponse();
    }

    public function discardToPutGood()
    {
        $this->startAction();
        $cards_raw = self::getArg("cards", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($cards_raw, -1) == ';') {
            $cards_raw = substr($cards_raw, 0, -1);
        }
        if ($cards_raw == '') {
            $cards = array();
        } else {
            $cards = explode(';', $cards_raw);
        }
        $result = $this->game->discardToPutGood($cards);
        self::ajaxResponse();
    }
    public function goodForMilitary()
    {
        $this->startAction();
        $card_id = self::getArg("card", AT_posint, true);
        $good_id = self::getArg("good", AT_alphanum, true);
        $result = $this->game->goodForMilitary($card_id, $good_id);
        self::ajaxResponse();
    }


    public function exploreDiscard()
    {
        $this->startAction();
        $cards_raw = self::getArg("cards", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($cards_raw, -1) == ';') {
            $cards_raw = substr($cards_raw, 0, -1);
        }
        if ($cards_raw == '') {
            $cards = array();
        } else {
            $cards = explode(';', $cards_raw);
        }
        $result = $this->game->exploreDiscard($cards);
        self::ajaxResponse();
    }

    public function draft()
    {
        $this->startAction();
        $card = self::getArg("card", AT_posint, true);
        $this->game->draft($card);
        self::ajaxResponse();
    }

    public function takeover()
    {
        $this->startAction();
        $card = self::getArg("card", AT_posint, true);
        $target = self::getArg("target", AT_posint, true);
        $confirmed = self::getArg("confirmed", AT_bool, true);
        $this->game->takeover($card, $target, $confirmed);
        self::ajaxResponse();
    }

    public function noMoreBoost()
    {
        $this->startAction();
        $this->game->noMoreBoost();
        self::ajaxResponse();
    }

    public function changeOortType()
    {
        $this->startAction();
        $kind = self::getArg("kind", AT_posint, true);
        $this->game->changeOortType($kind);
        self::ajaxResponse();
    }

    public function playOrbCard()
    {
        $this->startAction();

        $card = self::getArg("card", AT_posint, true);
        $x = self::getArg("x", AT_int, true);
        $y = self::getArg("y", AT_int, true);
        $ori = self::getArg("ori", AT_posint, true);
        $confirmed = self::getArg("confirmed", AT_bool, true);

        $this->game->placeOrbCard($card, $x, $y, $ori, true, $confirmed);

        self::ajaxResponse();
    }

    public function moveTeam()
    {
        $this->startAction();

        $x = self::getArg("x", AT_int, true);
        $y = self::getArg("y", AT_int, true);

        $this->game->moveTeam($x, $y);

        self::ajaxResponse();
    }
    public function moveTeamSelect()
    {
        $this->startAction();

        $team_id = self::getArg("id", AT_posint, true);

        $this->game->moveTeamSelect($team_id);

        self::ajaxResponse();
    }


    public function placeTeam()
    {
        $this->startAction();

        $x = self::getArg("x", AT_int, true);
        $y = self::getArg("y", AT_int, true);

        $this->game->placeTeam($x, $y);

        self::ajaxResponse();
    }

    public function orbdraw()
    {
        $this->startAction();
        $this->game->orbdraw();
        self::ajaxResponse();
    }
    public function orbpass()
    {
        $this->startAction();
        $this->game->orbpass();
        self::ajaxResponse();
    }
    public function orbskip()
    {
        $this->startAction();
        $this->game->orbskip();
        self::ajaxResponse();
    }
    public function orbstop()
    {
        $this->startAction();
        $this->game->orbstop();
        self::ajaxResponse();
    }
    public function orbendmoveaction()
    {
        $this->startAction();
        $this->game->orbendmoveaction();
        self::ajaxResponse();
    }


    public function useArtefact()
    {
        $this->startAction();

        $artifact_id = self::getArg("artifact", AT_posint, true);
        $reason = self::getArg("reason", AT_alphanum, true);

        $this->game->useArtefact($artifact_id, $reason);

        self::ajaxResponse();
    }
    public function orbBackToSas()
    {
        $this->startAction();

        $team_id = self::getArg("team", AT_posint, true);
        $x = self::getArg("x", AT_int, true);
        $y = self::getArg("y", AT_int, true);

        $this->game->orbBackToSas($team_id, $x, $y);

        self::ajaxResponse();
    }
    public function bunker()
    {
        $this->startAction();
        $card_id = self::getArg('card', AT_posint, true);
        $this->game->bunker($card_id);
        self::ajaxResponse();
    }
    public function xenoDonotrepulse()
    {
        $this->startAction();
        $this->game->xenoDonotrepulse();
        self::ajaxResponse();
    }
    public function chooseDamage()
    {
        $this->startAction();
        $card_id = self::getArg('card', AT_posint, true);
        $this->game->chooseDamage($card_id);
        self::ajaxResponse();
    }
    public function repairDamaged()
    {
        $this->startAction();
        $card_id = self::getArg('card', AT_posint, true);

        $cards_raw = self::getArg("discard_id", AT_numberlist, true);

        // Removing last ';' if exists
        if (substr($cards_raw, -1) == ';') {
            $cards_raw = substr($cards_raw, 0, -1);
        }
        if ($cards_raw == '') {
            $cards = array();
        } else {
            $cards = explode(';', $cards_raw);
        }

        $good_id = self::getArg('good', AT_posint, true);

        $this->game->repairDamaged($card_id, $cards, $good_id);
        self::ajaxResponse();
    }
    public function drawForEachWorld()
    {
        $this->startAction();
        $card_id = self::getArg('card', AT_posint, true);
        $this->game->drawForEachWorld($card_id);
        self::ajaxResponse();
    }
    public function drawForEachGood()
    {
        $this->startAction();
        $card_id = self::getArg('card', AT_posint, true);
        $this->game->drawForEachGood($card_id);
        self::ajaxResponse();
    }
}
