<?php
 /**
  * HtmlGenerationTrait.php
  *
  * Generate HTML output corresponding to game elements.
  *
  */

trait HtmlGenerationTrait
{
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
            case 187:
                $html .= "<tr><td>{one_pt}</td><td>PRG</td><td class='six_dev_scoring_text'>" . self::_("(additional)")."</td></tr>";
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
}
