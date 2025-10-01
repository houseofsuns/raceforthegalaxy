<?php

/////////////////////////////////////////////////////////////////////
///// Game options description
/////

$game_options = [

    100 => [
                'name' => totranslate('Expansion'),
                'values' => [
                            1 => ['name' => totranslate('Base game'), 'tmdisplay'=> totranslate('Base game')],
                            2 => ['name' => totranslate('The Gathering Storm'),  'tmdisplay' => totranslate('The Gathering Storm'),  'premium'=>true,  'nobeginner' => true],
                            3 => ['name' => totranslate('Rebel vs Imperium + The Gathering Storm'),  'tmdisplay' => totranslate('Rebel vs Imperium + The Gathering Storm'),  'premium'=>true,  'nobeginner' => true],
                            4 => ['name' => totranslate('Rebel vs Imperium + The Gathering Storm + Brink of War'),  'tmdisplay' => totranslate('Rebel vs Imperium + The Gathering Storm + Brink of War'),  'premium'=>true,  'nobeginner' => true],
                            5 => ['name' => totranslate('Alien Artifacts (with Orb)'),  'tmdisplay' => totranslate('Alien Artifacts (+ Orb)'),  'premium'=>true,  'nobeginner' => true],
                            6 => ['name' => totranslate('Alien Artifacts (no Orb)'),  'tmdisplay' => totranslate('Alien Artifacts (no Orb)'),  'premium'=>true,  'nobeginner' => true],
                            7 => ['name' => totranslate('Xeno Invasion (with Invasion)'),  'tmdisplay' => totranslate('Xeno Invasion (+ Invasion)'),  'premium'=>true,  'nobeginner' => true],
                            8 => ['name' => totranslate('Xeno Invasion (no Invasion)'),  'tmdisplay' => totranslate('Xeno Invasion (no Invasion)'),  'premium'=>true,  'nobeginner' => true],
                ],
                    'startcondition' => [
                        1 => [['type' => 'maxplayers', 'value' => 4, 'message' => totranslate('Base game is only available for 4 players maximum.')]],
                        2 => [['type' => 'maxplayers', 'value' => 5, 'message' => totranslate('The Gathering Storm is only available for 5 players maximum.')]],
                        3 => [],
                        4 => [],
                        5 => [['type' => 'maxplayers', 'value' => 5, 'message' => totranslate('Alien Artifacts is only available for 5 players maximum.')]],
                        6 => [['type' => 'maxplayers', 'value' => 5, 'message' => totranslate('Alien Artifacts is only available for 5 players maximum.')]],
                        7 => [['type' => 'maxplayers', 'value' => 5, 'message' => totranslate('Xeno Invasion is only available for 5 players maximum.')]],
                        8 => [['type' => 'maxplayers', 'value' => 5, 'message' => totranslate('Xeno Invasion is only available for 5 players maximum.')]],
                    ]
    ],
    106 => [
        'name' => totranslate('Goals'),
        'values' => [
            1 => ['name' => totranslate('On'), 'tmpdisplay' => totranslate('Goals'), 'nobeginner' => true],
            0 => ['name' => totranslate('Off')]
        ],
        'displaycondition' => [
                   ['type' => 'otheroption', 'id' => 100, 'value' => [2,3,4]]
                   ]
        ],
    102 => [
                'name' => totranslate('Takeovers'),
                'values' => [
                            0 => ['name' => totranslate('Random')],
                            2 => ['name' => totranslate('No takeover')],
                            1 => ['name' => totranslate('Allow takeovers'),  'tmdisplay' => totranslate('Takeovers'),  'premium'=>true,  'nobeginner' => true],
                            3 => ['name' => totranslate('2-Player Rebel vs Imperium Takeover Scenario'),  'tmdisplay' => totranslate('RvI scenario'),  'premium'=>true,  'nobeginner' => true],
                ],
                'displaycondition' => [   // Note: do not display this option unless these conditions are met
                   ['type' => 'otheroption', 'id' => 100, 'value' => [3,4]]
                ],
                'startcondition' => [
                        0 => [],
                        2 => [],
                        1 => [],
                        3 => [['type' => 'maxplayers', 'value' => 2, 'message' => totranslate('Rebel vs Imperium Takeover Scenario is available for 2 players only.')]],
                ],
      'disable' => true

    ],
// Be careful : 103 is used as a constant in the game.
    104 => [
                'name' => totranslate('New Worlds'),
                'values' => [
                        2 => [ 'name' => totranslate('Enabled') ],
                        1 => [ 'name' => totranslate('Disabled') ]
                ],
        'displaycondition' => [
            ['type' => 'otheroptionisnot', 'id' => 105, 'value' => 1]
        ]
    ],
    105 => [
        'name' => totranslate('Preset hands'),
        'values' => [
            0 => ['name' => totranslate('Off')],
            1 => ['name' => totranslate('On (all players)'), 'tmpdisplay' => totranslate('Preset hands')],
            2 => ['name' => totranslate('On (just beginners)'), 'tmpdisplay' => totranslate('Preset hands')]
        ],
        'displaycondition' => [
            [ 'type' => 'otheroption',
                                'id' => 201, // ELO OFF hardcoded framework option
                                'value' => 1], // 1 if OFF
            ['type' => 'otheroptionisnot', 'id' => 100, 'value' => [3,4]],
            ['type' => 'otheroptionisnot', 'id' => 101, 'value' => [2,3,4]]
        ]
    ],
    101 => [
                'name' => totranslate('Draft variant'),
                'values' => [
                            1 => ['name' => totranslate('No draft')],
                            2 => ['name' => totranslate('Draft 2-3 players'),  'tmdisplay' => totranslate('Draft'),  'premium'=>true,  'nobeginner' => true],
                            3 => ['name' => totranslate('Draft 2-5 players'),  'tmdisplay' => totranslate('Draft'),  'premium'=>true,  'nobeginner' => true],
                            4 => ['name' => totranslate('Draft 2-6 players'),  'tmdisplay' => totranslate('Draft'),  'premium'=>true,  'nobeginner' => true]
                ],
                'displaycondition' => [   // Note: do not display this option unless these conditions are met
                   ['type' => 'otheroption', 'id' => 100, 'value' => [2,3,4]],
                   ['type' => 'otheroptionisnot', 'id' => 102, 'value' => 3],
                   ['type' => 'otheroptionisnot', 'id' => 105, 'value' => [1,2]]
                ],
                'startcondition' => [
                    1 => [],
                    2 => [['type' => 'maxplayers', 'value' => 3,
                           'message' => totranslate('Draft option is available for 3 players maximum with this expansion')]],
                    3 => [['type' => 'maxplayers', 'value' => 5,
                           'message' => totranslate('Draft option is available for 5 players maximum with this expansion')],
                         ['type' => 'otheroptionisnot', 'id' => 100, 'value' => 2,
                          'message' => totranslate('5 player draft option requires at least Rebel vs Imperium')]],
                    4 => [['type' => 'otheroptionisnot', 'id' => 100, 'value' => 2,
                           'message' => totranslate('6 player draft option requires Brink of War')],
                          ['type' => 'otheroptionisnot', 'id' => 100, 'value' => 3,
                           'message' => totranslate('6 player draft option requires Brink of War')]]
                ],
                'disable' => true
    ],
    107 => [
        'name' => totranslate('Reuse draft'),
        'values' => [
            0 => ['name' => totranslate('Off')],
            1 => ['name' => totranslate('Save this draft for future game')],
            2 => ['name' => totranslate('Load draft from previous game')]
        ],
        'displaycondition' => [
            ['type' => 'otheroption', 'id' => 101, 'value' => [2,3,4]],
        ]
    ]
];

$game_preferences = [
    '1' => [
            'name' => totranslate('Design'),
            'needReload' => true,
            'values' => [
                    1 => ['name' => totranslate('Race for the Galaxy'), 'cssPref' => 'new_design'],
                    2 => ['name' => 'Board Game Arena', 'cssPref' => 'old_design']
            ]
    ],
    '2' => [
        'name' => totranslate('Card size'),
        'needReload' => true,
        'values' => [
            0 => ['name' => totranslate('Auto')],
            1 => ['name' => totranslate('Tiny'), 'cssPref' => 'card_size_tiny'],
            2 => ['name' => totranslate('Small'), 'cssPref' => 'card_size_small'],
            3 => ['name' => totranslate('Medium'), 'cssPref' => 'card_size_medium'],
            4 => ['name' => totranslate('Large'), 'cssPref' => 'card_size_large'],
            5 => ['name' => totranslate('Huge'), 'cssPref' => 'card_size_huge']
        ]
    ],
    '3' => [
        'name' => totranslate('Card on tooltip'),
        'needReload' => true,
        'values' => [
            0 => ['name' => totranslate('Auto')],
            1 => ['name' => totranslate('Off')],
            2 => ['name' => totranslate('Small'), 'cssPref' => 'card_tooltip_size_small'],
            3 => ['name' => totranslate('Medium'), 'cssPref' => 'card_tooltip_size_medium'],
            4 => ['name' => totranslate('Large'), 'cssPref' => 'card_tooltip_size_large'],
            5 => ['name' => totranslate('Huge'), 'cssPref' => 'card_tooltip_size_huge']
        ]
    ],
    '4' => [
        'name' => totranslate('Tooltip delay (ms)'),
        'needReload' => true,
        'values' => [
            0 => ['name' => totranslate('Auto')],
            1 => ['name' => '0'],
            2 => ['name' => '200'],
            3 => ['name' => '400'],
            4 => ['name' => '600'],
            5 => ['name' => '800'],
            6 => ['name' => '1000']
        ]
    ],
    '5' => [
        'name' => totranslate('Card name color'),
        'needReload' => true,
        'values' => [
            1 => ['name' => totranslate('Black'), 'cssPref' => 'card_name_black'],
            2 => ['name' => totranslate('White'), 'cssPref' => 'card_name_white']
        ]
    ],
    '6' => [
        'name' => totranslate('6 cost development scoring display'),
        'needReload' => true,
        'values' => [
            1 => ['name' => totranslate('Never'), 'cssPref' => 'sixdev_scoring_no'],
            2 => ['name' => totranslate('Hover / Tap'), 'cssPref' => 'sixdev_scoring_hover_tap'],
            3 => ['name' => totranslate('Always'), 'cssPref' => 'sixdev_scoring_yes']
        ],
        'default' => 2
    ],
    '7' => [
        'name' => totranslate('Confirm explore'),
        'needReload' => true,
        'values' => [
            1 => ['name' => totranslate('Always')],
            2 => ['name' => totranslate('Only multiple cards')],
            3 => ['name' => totranslate('Never')]
        ]
    ]
 ];
