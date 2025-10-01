<?php

$gameinfos = array(

// Game publisher
'publisher' => 'Rio Grande Games',

// Url of game publisher website
'publisher_website' => 'http://www.riograndegames.com/',

// Board Game Geek ID of the publisher
'publisher_bgg_id' => 3,

// Board game geek if of the game
'bgg_id' => 28143,


// Players configuration that can be played (ex: 2 to 4 players)
'players' => array( 2,3,4,5,6 ),

// Suggest players to play with this number of players. Must be null if there is no such advice, or if there is only one possible player configuration.
'suggest_player_number' => 2,

// Discourage players to play with this number of players. Must be null if there is no such advice.
'not_recommend_player_number' => array( ),

'tie_breaker_description' => totranslate( "Number of cards left in hand + number of goods" ),


// Estimated game duration, in minutes (used only for the launch, afterward the real duration is computed)
'estimated_duration' => 8,

// Time in second add to a player when "giveExtraTime" is called (speed profile = fast)
'fast_additional_time' => 90,

// Time in second add to a player when "giveExtraTime" is called (speed profile = medium)
'medium_additional_time' => 110,

// Time in second add to a player when "giveExtraTime" is called (speed profile = slow)
'slow_additional_time' => 130,

// Is this game cooperative (all players wins together or loose together)
'is_coop' => 0,

// Favorite colors support : if set to "true", support attribution of favorite colors based on player's preferences (see reattributeColorsBasedOnPreferences PHP method)
'favorite_colors_support' => true,


// Game interface width range (pixels)
// Note: game interface = space on the left side, without the column on the right
'game_interface_width' => array(

    // Minimum width
    //  default: 740
    //  maximum possible value: 740 (ie: your game interface should fit with a 740px width (correspond to a 1024px screen)
    //  minimum possible value: 320 (the lowest value you specify, the better the display is on mobile)
    'min' => 650,
),
);
