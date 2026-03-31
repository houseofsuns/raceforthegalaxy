{OVERALL_GAME_HEADER}

<div id="ios-auxilliary-wrapper" onclick="void(0);">
<!--
Empty mouse event handler to help with mobile Safari client handling according to
https://stackoverflow.com/questions/24077725/mobile-safari-sometimes-does-not-trigger-the-click-event
-->

<div id="phase_panel">
    <div class="phase" id="explore_phase">
        <div class="phase_icon" id="phase_icon_explore"></div>
        <div class="phase_content">{LB_EXPLORE}</div>
        <div class="phase_selected" id="phase_selected_1"></div>
    </div>
    <div class="phase" id="develop_phase">
        <div class="phase_icon" id="phase_icon_develop"></div>
         <div class="phase_content">{LB_DEVELOP}</div>
        <div class="phase_selected" id="phase_selected_2"></div>
    </div>
    <div class="phase" id="settle_phase">
        <div class="phase_icon" id="phase_icon_settle"></div>
        <div class="phase_content">{LB_SETTLE}</div>
        <div class="phase_selected" id="phase_selected_3"></div>
    </div>
    <div class="phase" id="consume_phase">
        <div class="phase_icon" id="phase_icon_consume"></div>
         <div class="phase_content">{LB_CONSUME}</div>
        <div class="phase_selected" id="phase_selected_4"></div>
    </div>
    <div class="phase" id="produce_phase">
        <div class="phase_icon" id="phase_icon_produce"></div>
         <div class="phase_content">{LB_PRODUCE}</div>
        <div class="phase_selected" id="phase_selected_5"></div>
    </div>
</div>

<div id="phasechoice_panel">
    <br class="clear" />
    <div class="phasechoice" >
        <a href="#" class="bgabutton bgabutton_blue phasechoicebtn" id="phasechoicebtn_1" role="button"><span class="normalbonus">+1 +1</span><span class="boosted">+7 +2 +{COMBINE}</span></a>
        <a href="#" class="bgabutton bgabutton_blue phasechoicebtn" id="phasechoicebtn_2" role="button"><span class="normalbonus">+5 +0</span><span class="boosted">+11 +1 +{COMBINE}</span></a>
        <a href="#" class="bgabutton bgabutton_blue phasechoicebtn" id="phasechoicebtn_8" style="display:none" role="button">{ORB}</a>
    </div>
    <div class="phasechoice" >
        <a href="#" class="bgabutton bgabutton_blue phasechoicebtn" id="phasechoicebtn_3" role="button"><span class="normalbonus">{LB_CHOOSE}</span><span class="boosted">-3</span></a>
    </div>
    <div class="phasechoice" >
        <a href="#" class="bgabutton bgabutton_blue phasechoicebtn" id="phasechoicebtn_4" role="button"><span class="normalbonus">{LB_CHOOSE}</span><span class="boosted">-3 +2</span></a>
    </div>
    <div class="phasechoice" >
        <a href="#" class="bgabutton bgabutton_blue phasechoicebtn" id="phasechoicebtn_5" role="button"><span class="normalbonus">$</span><span class="boosted">$+3 / x2</span></a>
        <a href="#" class="bgabutton bgabutton_blue phasechoicebtn" id="phasechoicebtn_6" role="button"><span class="normalbonus">x2</span><span class="boosted">x3</span></a>
    </div>
    <div class="phasechoice" >
        <a href="#" class="bgabutton bgabutton_blue phasechoicebtn" id="phasechoicebtn_7" role="button"><span class="normalbonus">{LB_CHOOSE}</span><span id="prodboosted" class="boosted">{DRAW} 3 + 2 {WINDFALL}</span></a>
        <a href="#" class="bgabutton bgabutton_blue phasechoicebtn" id="phasechoicebtn_9" role="button"><span class="normalbonus"><i class="fa fa-wrench fa-lg" aria-hidden="true"></i></span></a>
    </div>
</div>

<div id="generalinfos">
    <div id="deck" class="rftg_infoblock">
        &nbsp;<img src="{GAMETHEMEURL}img/deck.png" id="deck_img" /> x<span id="deck_size"></span>
    </div>
    <div id="vp_remain" class="rftg_infoblock">
        &nbsp;<span id="vp_nbr_remain_img" class="icon vp_chip vpchipcount"></span> x<span id="vp_nbr_remain"></span>
    </div>
    <div id="xeno_infos" class="rftg_infoblock">
        {REPULSE_INVASION} : <b><span id="repulse_players">0</span></b> / <b><span id="repulse_goal">28</span></b><br/>
    </div>
</div>

<div id="takeover_toggle_warp" class="rftg_infoblock">
    &nbsp;<img src="{GAMETHEMEURL}img/takeover-on.svg" class="takeover_toggle" id="takeovers_on_img" />
    <img src="{GAMETHEMEURL}img/takeover-off.svg" class="takeover_toggle" id="takeovers_off_img" />
</div>

<div id="goals_wrap">
    <div id="goals">
    </div>
</div>

<br class="clear" />

<div id="explore_panel" class="whiteblock">
    <h3>{LB_EXPLORATION_RESULT}</h3>
    <div id="explore_set">
    </div>
</div>

<div id="hand_panel" class="whiteblock">
    <h3>{LB_MY_HAND}</h3>
    <div class="clear"></div>
    <div id="discard_for_military_panel" class="whiteblock">
        <div class="icon20 icon20_help"></div> {DISCARD_FOR_MILITARY_HELP}
        <span class="bgabutton bgabutton_blue" id="end_military_discard">{END_DISCARD}</span>
    </div>
    <div class="clear"></div>
    <div id="player_hand">
    </div>
    <div id="player_hand_art">
    </div>
</div>

<div id="tableau_panel_{CURRENT_PLAYER_ID}" class="whiteblock tableau_panel">
    <h3 id="tableau_title_{CURRENT_PLAYER_ID}">{LB_MY_TABLEAU}</h3>
    <div id="consume_help" class="whiteblock" style="display:none;"><div class="icon20 icon20_help"></div> {CONSUME_HELP}</div>
    <div id="consume_for_military_panel" class="whiteblock">
        <div class="icon20 icon20_help"></div> {CONSUME_FOR_MILITARY_HELP}
        <span class="bgabutton bgabutton_blue" id="end_military_consume">{END_DISCARD}</span>
    </div>
    <div id="oort_help" class="whiteblock" style="display:none;"><div class="icon20 icon20_help"></div> {OORT_HELP}</div>
    <div id="gambling_panel" style="display:none">
        {USE_GAMBLING_WORLD} :
        <span id="gambling_1" class="bgabutton bgabutton_blue gambling_button">1</span>
        <span id="gambling_2" class="bgabutton bgabutton_blue gambling_button">2</span>
        <span id="gambling_3" class="bgabutton bgabutton_blue gambling_button">3</span>
        <span id="gambling_4" class="bgabutton bgabutton_blue gambling_button">4</span>
        <span id="gambling_5" class="bgabutton bgabutton_blue gambling_button">5</span>
        <span id="gambling_6" class="bgabutton bgabutton_blue gambling_button">6</span>
        <span id="gambling_7" class="bgabutton bgabutton_blue gambling_button">7</span>
    </div>
    <div id="tableau_{CURRENT_PLAYER_ID}">
    </div>
    <div class="playergoals_wrap">
        <div id="goals_{CURRENT_PLAYER_ID}" class="playergoals"></div>
    </div>
    <div class="player_played_art_wrap">
        <div id="player_played_art_{CURRENT_PLAYER_ID}" class="player_played_art">
        </div>
    </div>

    <br class="clear" />
</div>

<div id="scavenger_panel" class="whiteblock">
    <h3>{LB_GALACTIC_SCAVENGERS}</h3>
    <div id="scavenger_set">
    </div>
</div>

<div id="my_deck_wrap" class="whiteblock tableau_panel">
    <h3>{MY_DECK}</h3>
    <div id="my_deck">
    </div>
</div>

<div id="orb_hand_panel" class="whiteblock" style="display:none;">
    <h3>{LB_MY_ORB_HAND}</h3>
    <div id="player_hand_orb">
    </div>
</div>

<div id="orb_wrap" class="whiteblock" style="display:none; position: relative;">
    <div id="remaining_orb_wrap" class="rftg_board">
        {LB_ORB_DECK}
        <div class="imgtext orb_card_a"></div><span id="orb_deck_a">0</span>
        <div class="imgtext orb_card_b"></div><span id="orb_deck_b">0</span>
    </div>
    <h3 id="alien_orb_title">{ALIEN_ORB}
        <div id="priority_track">
            <!-- BEGIN priority -->
                <div id="priority_player_{PLAYER_ID}" class="priority_player priority_player_{COLOR}" style="background-color:#{COLOR};"></div>
            <!-- END priority -->

            <div id="priority_6" class="priority_place"></div><div id="priority_5" class="priority_place"></div><div id="priority_4" class="priority_place"></div><div id="priority_3" class="priority_place"></div><div id="priority_2" class="priority_place"></div><div id="priority_1" class="priority_place"></div>
        </div>
    </h3>
    <div id="alien_orb">
      <div id="map_scrollable" class="map_scrollable"><div id="orb_target"></div></div>
      <div id="map_surface" class="map_surface"></div>
      <div id="map_scrollable_oversurface" class="map_scrollable_oversurface">
        <div id="orbitems"></div>
        <div id="orbsquares"></div>
      </div>
      <a id="movetop" class="movetop" "href="#"></a>
      <a id="moveleft" class="moveleft" "href="#"></a>
      <a id="moveright" class="moveright" "href="#"></a>
      <a id="movedown" class="movedown" "href="#"></a>

    </div>
</div>

<!-- BEGIN player -->

<div id="tableau_panel_{PLAYER_ID}" class="whiteblock  tableau_panel">
    <h3 id="tableau_title_{PLAYER_ID}">{TABLEAU_NAME}</h3>
    <div id="tableau_{PLAYER_ID}">

    </div>
    <div class="playergoals_wrap">
        <div id="goals_{PLAYER_ID}" class="playergoals"></div>
    </div>
    <div class="player_played_art_wrap">
        <div id="player_played_art_{PLAYER_ID}" class="player_played_art">
        </div>
    </div>

    <br class="clear" />
</div>
<!-- END player -->

<div id="invasion_infos" class="whiteblock">
        <h3>{XENO_INVASION}</h3>
        <br/>
        <div>
            <div>
                {CURRENT_WAVE} : <b><span id="current_wave">0</span></b> (<b><span id="wave_remaining_cards">2</span></b> {REMAINING_CARDS})

                <span style="float:right">{NUMBER_OF_EMPIRE_DEFEATS} : <b><span id="empire_defeat">0</span></b> / 2</span>
            </div>
            <br/>
            <div id="xeno_repulse_track"></div>
        </div>
</div>

<div id="play_with_expansions" class="whiteblock" >
    <h3 style="display:block">{PLAY_WITH_EXPANSIONS}</h3>
    <br/>
    <p class="explore_galaxy_text">{EXPLORE_GALAXY}</p>
    <div id="explore_galaxy">
        <img src="{GAMETHEMEURL}/img/expansions.png"></img>
    </div>
    <p class="">{EXPLORE_GALAXY2}</p>
    <p><a href="{METASITEURL}/#!club" target="_blank" class="bgabutton bgabutton_blue">{NOT_A_MEMBER}</a></p>
</div>


<script type="text/javascript">

// Templates
var jstpl_player_board = '<div class="rftg_board">\
    <img src="{THEMEURL}img/common/hand.png" class="imgtext cardhandcount"/><span id="card_hand_nbr_${id}" class="cardhandcount"></span>\
    <span class="icon imgtext vp_chip vpchipcount"></span><span id="vp_nbr_${id}" class="vpchipcount"></span>\
    <span class="prestigecount"><span class="icon imgtext prestige prestigepanelcount"></span><span id="prestige_nbr_${id}" class="prestigepanelcount"></span></span>\
    <div class="imgtext milforcecount milforceicon" id="milforceicon_${id}"></div><span id="milforce_${id}" class="milforcecount"></span><span id="tmpmilforce_${id}" class="tmpmilforcecount"></span>\
    <img src="{GAMETHEMEURL}img/cardback.png" class="imgtext tableaucount" /><span id="tableau_nbr_${id}" class="tableaucount"></span>\
    <img src="{GAMETHEMEURL}img/pdeck.png" class="imgtext pdeck" /><span id="pdeck_${id}" class="pdeck"></span>\
    <div class="rvi_block boardblock">\
        <span id="imperium_vulnerability_${id}" class="imgtext imperium_vulnerability"></span>\
        <span id="rebel_vulnerability_${id}" class="imgtext rebel_vulnerability"></span>\
        <span id="prestige_search_${id}" class="imgtext prestige_search"></span>\
    </div>\
    <div class="aa_block boardblock">\
        <span class="orb_card_a_wrap"><div id="orb_card_a_${id}" class="imgtext orb_card_a"></div><span id="orb_card_a_count_${id}" class="orb_card_count">0</span></span>\
        <span class="orb_card_b_wrap" id="orb_card_b_wrap_${id}"><div id="orb_card_b_${id}" class="imgtext orb_card_b"></div><span id="orb_card_b_count_${id}">0</span></span>\
        <span class="artefact_A_wrap"><div id="artefact_A_${id}" class="imgtext artefact_A"></div><span id="artefact_A_count_${id}">0</span></span>\
        <span class="artefact_B_wrap" id="artefact_B_wrap_${id}"><div id="artefact_B_${id}" class="imgtext artefact_B"></div><span id="artefact_B_count_${id}">0</span></span>\
    </div>\
    <div class="xeno_block boardblock">\
        <span class="effort_wrap"><div id="effort_${id}" class="imgtext efforticon"></div><span id="effortcount_${id}" class="effortcount">0</span></span>\
        <span class="xenomilforce_wrap"><div id="xeno_milforce_${id}" class="imgtext xeno_milforceicon"></div><span id="xenomilforce_${id}" class="xenomilforcecount">0</span><span id="xenotiebreaker_${id}" class="xenotiebreaker"></span></span>\
        <span class="defenseaward_wrap"><div id="defense_award_${id}" class="imgtext xeno_defenseawardicon"></div><span id="defenseaward_${id}">0</span></span>\
    </div>\
    <div class="invasionforce boardblock" id="invasionforce_${id}"></div>\
    </div>';

var jstpl_card = '<div id="card_wrapper_${id}" class="card_wrapper card_type_${type} thickness">\
                    <div id="card_${id}" class="card" role="button" style="background-position: -${backx}px -${backy}px; background-image: url(${background})">\
                      <div id="good_place_${id}" class="good_place"></div>\
                      <div class="damage" id="damage_${id}"></div>\
                    </div>\
                  </div>';
var jstpl_tooltip_card = '<div class="card_tooltip"\
    style="margin:0 auto; background-image: url(${background}); background-position: -${backx}px -${backy}px">\
    <div class="cardname_tooltip"><span>${card_name}</span></div>${live_value}</div>';

var jstpl_good = '<div class="good_wrap" id="good_wrap_${good_id}"><div class="good good${good_type}" id="good_${good_id}" role="button">${indicator}<div id="goodsell_${good_id}" class="goodsell"></div></div></div>';

var jstpl_artefact = '<div id="artefact_${id}" class="artefact artefact_${type_arg}"></div>';
var jstpl_team = '<div id="team_${team_id}" class="team"></div>';


var jstpl_orbcard = '<div id="orbcard_${id}" class="orbcard" style="background-position: -${backx}px -${backy}px"></div>';

var jstpl_orb = '<div id="orb_${x}_${y}" class="orb" style="left:${left}px;top:${top}px"></div>';

var jtspl_orbcardhand = '<div id="rotcommand_${id}" class="rotcommand"></div>';

var jstpl_admiral = '<div id="admiral_${id}" class="admiral_disk admiral_${color}"></div>';
var jstpl_military_vs_xeno_arrow = '<div id="military_vs_xeno_arrow"></div>';
var jstpl_repulse_value_arrow = '<div id="repulse_value_arrow"></div>';
var jstpl_breeding_tube_tooltip = '<div><img style="vertical-align:middle" src="{GAMETHEMEURL}img/breeding_tube.png"/><span>${text}</span></div>'
var jstpl_goal_tooltip = '<div class="cardtooltip">\
    <div class="cardtt_name">${name}</div><hr/>\
    <div class="cardtt_basicinfos">\
        <p>${nb_points} ${points}</p>\
        <p>${description}</p>\
    </div>';

</script>

</div>

{OVERALL_GAME_FOOTER}
