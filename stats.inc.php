<?php

/////////////////////////////////////////////////////////////////////
///// Game statistics description
/////

$stats_type = array(

    // Statistics global to table
    "table" => array(

        "turn_number" => array("id"=> 10,
                                "name" => totranslate("Number of turn"),
                                "type" => "int"),
        "phase_played" => array("id"=> 11,
                                "name" => totranslate("Number of phase played"),
                                "type" => "int"),
        "phase_explore" => array("id"=> 12,
                                "name" => totranslate("Number of explore phase played"),
                                "type" => "int"),
        "phase_develop" => array("id"=> 13,
                                "name" => totranslate("Number of develop phase played"),
                                "type" => "int"),
        "phase_settle" => array("id"=> 14,
                                "name" => totranslate("Number of settle phase played"),
                                "type" => "int"),
        "phase_consume" => array("id"=> 15,
                                "name" => totranslate("Number of consume phase played"),
                                "type" => "int"),
        "phase_produce" => array("id"=> 16,
                                "name" => totranslate("Number of produce phase played"),
                                "type" => "int")


   ),

    // Statistics existing for each player
    "player" => array(

        "start_world" => array("id" => 34,
                                "name" => totranslate("Start World"),
                                "type" => "int"),
        "milforce" => array("id"=> 10,
                                "name" => totranslate("Final military force"),
                                "type" => "int"),
        "good_produced" => array("id"=> 11,
                                "name" => totranslate("Number of goods produced"),
                                "type" => "int"),
        "tableau_count" => array("id"=> 12,
                                "name" => totranslate("Final number of cards in tableau"),
                                "type" => "int"),
        "chips_count" => array("id"=> 13,
                                "name" => totranslate("Victory point chips"),
                                "type" => "int"),
        "sixcostdev_points" => array("id"=> 14,
                                "name" => totranslate("Points wins with 6 cost development"),
                                "type" => "int"),
        "tableau_points" => array("id"=> 15,
                                "name" => totranslate("Points wins with card on tableau"),
                                "type" => "int"),

        // 24, 25 and 26 were older previous version which were always initialized (and thus incorrect). Do not reuse.
        "goal_first_points" => array("id"=> 27,
                                "name" => totranslate("Points wins with `first` goals"),
                                "type" => "int"),
        "goal_most_points" => array("id"=> 28,
                                "name" => totranslate("Points wins with `most` goals"),
                                "type" => "int"),
        "prestige_points" => array("id"=> 29,
                                "name" => totranslate("Points from prestige chips"),
                                "type" => "int"),


        "artefact_points" => array("id"=> 30,
                                "name" => totranslate("Points from alien artefacts"),
                                "type" => "int"),

        "defense_award_points" => array("id" => 31,
                                "name" => totranslate("Points from defense awards"),
                                "type" => "int"),
        "greatest_admiral_points" => array("id" => 32,
                                "name" => totranslate("Points from Greatest Admiral"),
                                "type" => "int"),
        "greatest_contributor_points" => array("id" => 33,
                                "name" => totranslate("Points from Greatest Contributor"),
                                "type" => "int"),


        "explore_p1_count" => array("id"=> 16,
                                "name" => totranslate("Explore +1/+1 count"),
                                "type" => "int"),
        "explore_p5_count" => array("id"=> 17,
                                "name" => totranslate("Explore +5/+0 count"),
                                "type" => "int"),
        "develop_count" => array("id"=> 18,
                                "name" => totranslate("Develop count"),
                                "type" => "int"),
        "settle_count" => array("id"=> 19,
                                "name" => totranslate("Settle count"),
                                "type" => "int"),
        "consumesell_count" => array("id"=> 20,
                                "name" => totranslate("Consume (sell) count"),
                                "type" => "int"),
        "consumex2_count" => array("id"=> 21,
                                "name" => totranslate("Consume (x2) count"),
                                "type" => "int"),
        "produce_count" => array("id"=> 22,
                                "name" => totranslate("Produce count"),
                                "type" => "int"),
        "cards_drawn" => array("id"=> 23,
                                "name" => totranslate("Total number of cards drawn"),
                                "type" => "int")
   ),

   "value_labels" => array(
       34 => array(
           -6 => totranslate("Gateway Station"),
           -5 => totranslate("Abandoned Mine Squatters"),
           -4 => totranslate("Terraforming Colonists"),
           -3 => totranslate("Galactic Trade Emissaries"),
           -2 => totranslate("Industrial Robots"),
           -1 => totranslate("Star Nomad Raiders"),
           0 => totranslate("Old Earth"),
           1 => totranslate("Epsilon Eridani"),
           2 => totranslate("Alpha Centauri"),
           3 => totranslate("New Sparta"),
           4 => totranslate("Earth's Lost Colony"),
           5 => totranslate("Separatist Colony"),
           6 => totranslate("Ancient Race"),
           7 => totranslate("Damaged Alien Factory"),
           8 => totranslate("Doomed World"),
           9 => totranslate("Rebel Cantina"),
           10 => totranslate("Galactic Developers"),
           11 => totranslate("Imperium Warlord"),
           12 => totranslate("Galactic Scavengers"),
           13 => totranslate("Uplift Mercenary Force"),
           14 => totranslate("Alien Research Team"),
           15 => totranslate("Rebel Freedom Fighters"),
           105 => totranslate("Sentient Robots"),
           106 => totranslate("Alien Artifact Hunters"),
           107 => totranslate("Rebel Mutineers"),
           108 => totranslate("Uplift Researchers"),
           109 => totranslate("Frontier Capital"),
           205 => totranslate("Alien First Contact Team"),
           206 => totranslate("Starry Rift Pioneers"),
           207 => totranslate("Rebel Cadre"),
           208 => totranslate("Terraforming Surveyors"),
           209 => totranslate("Anti-Xeno Defense Post"),
       )
   )

);
