<?php

///// Cards used in Rftg

$this->card_types = array(

////// Developments

   1 => array(
      "name" => "Investment Credits",
      "nametr" => self::_("Investment Credits"), "nametrc" => clienttranslate("Investment Credits"),
      "qt" => 2,
      "type" => "development",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            2 => array( array(
                "power" => "devcost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1
               )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   2 => array(
      "name" => "Public Works",
      "nametr" => self::_("Public Works"), "nametrc" => clienttranslate("Public Works"),
      "qt" => 2,
      "type" => "development",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            2 => array( array(
                "power" => "drawifdev",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
            )),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'vp' => 1),
                    "repeat" => 1
               )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   3 => array(
      "name" => "Genetics Lab",
      "nametr" => self::_("Genetics Lab"), "nametrc" => clienttranslate("Genetics Lab"),
      "qt" => 2,
      "type" => "development",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 3),
                    "card" => 1
                )
            )),
            5 => array( array(
                "power" => "windfallproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => array( 3),
                    "worldfilter" => array( 'windfall')
               )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   4 => array(
      "name" => "Mining Robots",
      "nametr" => self::_("Mining Robots"), "nametrc" => clienttranslate("Mining Robots"),
      "qt" => 2,
      "type" => "development",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            3 => array( array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1,
                    "worldtype" => array( 2)
                )
            )),
            5 => array( array(
                "power" => "windfallproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => array( 2),
                    "worldfilter" => array( 'windfall')
               )
            ))
      ),

      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   5 => array(
      "name" => "Space Marines",
      "nametr" => self::_("Space Marines"), "nametrc" => clienttranslate("Space Marines"),
      "qt" => 2,
      "type" => "development",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2
               )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   6 => array(
      "name" => "Mining Conglomerate",
      "nametr" => self::_("Mining Conglomerate"), "nametrc" => clienttranslate("Mining Conglomerate"),
      "qt" => 2,
      "type" => "development",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 2),
                    "card" => 1
               )
             )),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 2),
                    "output" => array( 'vp' => 1),
                    "repeat" => 2
               )
            )),
            5 => array( array(
                "power" => "bonusifbiggestprod",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => 2,
                    "card" => 2
               )
            ))

      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   7 => array(
      "name" => "Diversified Economy",
      "nametr" => self::_("Diversified Economy"), "nametrc" => clienttranslate("Diversified Economy"),
      "qt" => 2,
      "type" => "development",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'vp' => 3),
                    "inputfactor" => 3,
                    "repeat" => 1,
                    "different" => true
               )
            )),
            5 => array( array(
                "power" => "drawforeachgoodtype",
                "icon" => '',
                "text" => "",
                "arg" => array(
               )
            ))

      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   8 => array(
      "name" => "Replicant Robots",
      "nametr" => self::_("Replicant Robots"), "nametrc" => clienttranslate("Replicant Robots"),
      "qt" => 2,
      "type" => "development",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
            3 => array( array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -2,
                    "worldtype" => array( 1,2,3,4)
               )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   9 => array(
      "name" => "Research Labs",
      "nametr" => self::_("Research Labs"), "nametrc" => clienttranslate("Research Labs"),
      "qt" => 2,
      "type" => "development",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
            1 => array( array(
                "power" => "explorekeep",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
             )),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 3),
                    "output" => array( 'vp' => 1),
                    "repeat" => 1
                   )
            )),
            5 => array( array(
                "power" => "drawforeach",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => 4,
                    "card" => 1
               )
            ))

      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   10 => array(
      "name" => "Consumer Markets",
      "nametr" => self::_("Consumer Markets"), "nametrc" => clienttranslate("Consumer Markets"),
      "qt" => 2,
      "type" => "development",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(
           4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1),
                    "output" => array( 'vp' => 1),
                    "repeat" => 3
               )
            )),
           5 => array( array(
                "power" => "drawforeach",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => 1,
                    "card" => 1
               )
           )),
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   11 => array(
      "name" => "Mining League",
      "nametr" => self::_("Mining League"), "nametrc" => clienttranslate("Mining League"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
           4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 2),
                    "inputfactor" => 2,
                    "output" => array( 'vp' => 3),
                    "repeat" => 1
                )
            )),
          5 => array( array(
                "power" => "windfallproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => array( 2),
                    "worldfilter" => array( 'windfall')
                 )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   12 => array(
      "name" => "Contact Specialist", //diplomate
      "nametr" => self::_("Contact Specialist"), "nametrc" => clienttranslate("Contact Specialist"),
      "qt" => 2,
      "type" => "development",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
              3 => array(
                array(
                    "power" => "diplomat",
                    "noalien" => true,
                    "discount" => -1,
                    "icon" => '',
                    "text" => "",
                    "arg" => array()
               ),
                array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => -1
                   )
               )
            )
        ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   13 => array(
      "name" => "Expedition Force",
      "nametr" => self::_("Expedition Force"), "nametrc" => clienttranslate("Expedition Force"),
      "qt" => 2,
      "type" => "development",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
             3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
               ),
             ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   14 => array(
      "name" => "Export Duties",
      "nametr" => self::_("Export Duties"), "nametrc" => clienttranslate("Export Duties"),
      "qt" => 2,
      "type" => "development",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
                's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1,2,3,4),
                    "card" => 1
                )
                ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   15 => array(
      "name" => "New Military Tactics",
      "nametr" => self::_("New Military Tactics"), "nametrc" => clienttranslate("New Military Tactics"),
      "qt" => 2,
      "type" => "development",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforcetmp",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 3
               )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => true
   ),
   16 => array(
      "name" => "Colony Ship",
      "nametr" => self::_("Colony Ship"), "nametrc" => clienttranslate("Colony Ship"),
      "qt" => 2,
      "type" => "development",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            3 => array( array(
                "power" => "colonyship",
                "icon" => '',
                "text" => "",
                "arg" => array(
               )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => true
   ),
   17 => array(
      "name" => "Deficit Spending",
      "nametr" => self::_("Deficit Spending"), "nametrc" => clienttranslate("Deficit Spending"),
      "qt" => 2,
      "type" => "development",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            4 => array( array(
                "power" => "consumecard",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "output" => array( 'vp' => 1),
                    "repeat" => 2
               )
            ))
     ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   18 => array(
      "name" => "Interstellar Bank",
      "nametr" => self::_("Interstellar Bank"), "nametrc" => clienttranslate("Interstellar Bank"),
      "qt" => 2,
      "type" => "development",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
             2 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
             ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   19 => array(
      "name" => "Terraforming Robots",
      "nametr" => self::_("€Terraforming€ Robots"), "nametrc" => clienttranslate("Terraforming Robots"),
      "qt" => 2,
      "type" => "development",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
             3 => array( array(
                "power" => "drawifsettle",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
             )),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 2),
                    "output" => array( 'vp' => 1,'card'=>1),
                    "repeat" => 1
               )
            ))
      ),
      "category" => array( 'terraforming'),
      "text" => "",
      "power_to_active" => false
   ),
  20 => array(
      "name" => "Drop Ships",
      "nametr" => self::_("Drop Ships"), "nametrc" => clienttranslate("Drop Ships"),
      "qt" => 2,
      "type" => "development",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
              3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 3
               )
              ))
      ),
      "category" => array( ),
      "text" => "",
      "power_to_active" => false
   ),
   21 => array(
      "name" => "Alien Tech Institute",
      "nametr" => self::_("£Alien£ Tech Institute"), "nametrc" => clienttranslate("Alien Tech Institute"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            3 => array(
                array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => 2,
                        "worldtype" => array( 4)
                   )
               ),
                array(
                    "power" => "settlecost",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "cost" => -2,
                        "worldtype" => array( 4)
                   )
               )
          )
      ),
      "category" => array( 'alien'),
      "text" => "",
      "power_to_active" => false
   ),
   22 => array(
      "name" => "Free Trade Association",
      "nametr" => self::_("Free Trade Association"), "nametrc" => clienttranslate("Free Trade Association"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1),
                    "output" => array( 'vp' => 1, 'card'=>1),
                    "repeat" => 3
                ),
            )),
            5 => array( array(
                "power" => "windfallproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => array( 1),
                    "worldfilter" => array( 'windfall')
                )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   23 => array(
      "name" => "Galactic Federation",
      "nametr" => self::_("Galactic Federation"), "nametrc" => clienttranslate("Galactic Federation"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            2 => array( array(
                "power" => "devcost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -2
               )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   24 => array(
      "name" => "Galactic Imperium",
      "nametr" => self::_("Galactic +Imperium+"), "nametrc" => clienttranslate("Galactic Imperium"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 4,
                    "worldfilter" => 'rebel'
               )
            ))
      ),

      "category" => array( 'imperium'),
      "text" => "",
      "power_to_active" => false
   ),
   25 => array(
      "name" => "Galactic Renaissance",
      "nametr" => self::_("Galactic Renaissance"), "nametrc" => clienttranslate("Galactic Renaissance"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
              1 => array(
                array(
                    "power" => "exploredraw",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "card" => 2
                   )
               ),
                array(
                    "power" => "explorekeep",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "card" => 1
                   )
               )
          )
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   26 => array(
      "name" => "Galactic Survey: SETI",
      "nametr" => self::_("Galactic Survey: SETI"), "nametrc" => clienttranslate("Galactic Survey: SETI"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
              1 => array(
                array(
                    "power" => "exploredraw",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "card" => 2
                   )
               )
          )
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   27 => array(
      "name" => "Merchant Guild",
      "nametr" => self::_("Merchant Guild"), "nametrc" => clienttranslate("Merchant Guild"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
           5 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 2
               )
           ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   28 => array(
      "name" => "New Economy",
      "nametr" => self::_("New Economy"), "nametrc" => clienttranslate("New Economy"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            4 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   29 => array(
      "name" => "New Galactic Order",
      "nametr" => self::_("New Galactic Order"), "nametrc" => clienttranslate("New Galactic Order"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2
               )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   30 => array(
      "name" => "Pan-Galactic League",
      "nametr" => self::_("Pan-Galactic League"), "nametrc" => clienttranslate("Pan-Galactic League"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => -1
               )
            )),
            5 => array( array(
                "power" => "drawforeachworld",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => 3,
                    "card" => 1
               )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
   31 => array(
      "name" => "Trade League",
      "nametr" => self::_("Trade League"), "nametrc" => clienttranslate("Trade League"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1,2,3,4),
                    "card" => 1
               ),
            )),
            4 => array( array(
                "power" => "consumeforsell",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "usepowers" => true,
                    "repeat" => 1
               )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),


////// Worlds

    32 => array(
      "name" => "Epsilon Eridani",
      "nametr" => self::_("Epsilon Eridani"), "nametrc" => clienttranslate("Epsilon Eridani"),
      "qt" => 1,
      "type" => "world",
      "startworld_number" => 1,
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
               )
            )),
            4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'vp' => 1, 'card' => 1),
                      "repeat" => 1
                 )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    33 => array(
      "name" => "Old Earth",
      "nametr" => self::_("Old Earth"), "nametrc" => clienttranslate("Old Earth"),
      "qt" => 1,
      "type" => "world",
      "startworld_number" => 0,
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1,2,3,4),
                    "card" => 1
               )
            )),
             4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'vp' => 1),
                      "repeat" => 2
                 )
             ))

      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    34 => array(
      "name" => "Alpha Centauri",
      "nametr" => self::_("Alpha Centauri"), "nametrc" => clienttranslate("Alpha Centauri"),
      "qt" => 1,
      "type" => "world",
      "startworld_number" => 2,
      "cost" => 2,
      "vp" => 0,
      "powers" => array(
            3 => array(
                array(
                    "power" => "settlecost",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "cost" => -1,
                        "worldtype" => array( 2)
                   )
               ),
                array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => 1,
                        "worldtype" => array( 2)
                   )
               )
         )
      ),
      "category" => array('windfall'),
      "windfalltype" => 2,
      "text" => "",
      "power_to_active" => false
   ),
    35 => array(
      "name" => "Earth's Lost Colony",
      "nametr" => self::_("Earth's Lost Colony"), "nametrc" => clienttranslate("Earth's Lost Colony"),
      "qt" => 1,
      "type" => "world",
      "startworld_number" => 4,
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
                4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'vp' => 1),
                      "repeat" => 1
                 )
                )),
                5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 1
                 )
                ))

      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    36 => array(
      "name" => "New Sparta",
      "nametr" => self::_("New Sparta"), "nametrc" => clienttranslate("New Sparta"),
      "qt" => 1,
      "type" => "world",
      "startworld_number" => 3,
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2
               )
            ) )
      ),
      "category" => array('military'),
      "text" => "",
      "power_to_active" => false
   ),
    37 => array(
      "name" => "Pilgrimage World",
      "nametr" => self::_("Pilgrimage World"), "nametrc" => clienttranslate("Pilgrimage World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 0,
      "vp" => 2,
      "powers" => array(
            4 => array( array(
                "power" => "consumeall",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "output" => array( 'vp' => 1),
                    "repeat" => 9999    //infinite
               )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    38 => array(
      "name" => "Refugee World",
      "nametr" => self::_("Refugee World"), "nametrc" => clienttranslate("Refugee World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 0,
      "vp" => 1,
      "powers" => array(
          3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => -1
               )
          ))
      ),
      "category" => array('windfall'),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),
    39 => array(
      "name" => "Empath World",
      "nametr" => self::_("Empath World"), "nametrc" => clienttranslate("Empath World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => -1
               )
            ))
      ),
      "category" => array('windfall'),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    40 => array(
      "name" => "Expanding Colony",
      "nametr" => self::_("Expanding Colony"), "nametrc" => clienttranslate("Expanding Colony"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
             4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'vp' => 1),
                      "repeat" => 1
                 )
             )),
              5 => array( array(
                "power" => "windfallproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => array( 1),
                    "worldfilter" => array( 'windfall')
                )
              ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    41 => array(
      "name" => "The Last of the Uplift Gnarssh",
      "nametr" => self::_("The Last of the *Uplift* Gnarssh"), "nametrc" => clienttranslate("The Last of the Uplift Gnarssh"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 0,
      "powers" => array(
      ),
      "category" => array('windfall','military', 'uplift', 'chromosome'),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    42 => array(
      "name" => "Asteroid Belt",
      "nametr" => self::_("Asteroid Belt"), "nametrc" => clienttranslate("Asteroid Belt"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
      ),
      "category" => array( 'windfall'),
      "windfalltype" => 2,
      "text" => "",
      "power_to_active" => false
   ),
    43 => array(
      "name" => "Galactic Engineers",
      "nametr" => self::_("Galactic Engineers"), "nametrc" => clienttranslate("Galactic Engineers"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1,2,3,4),
                    "card" => 1
               )
            )),
             5 => array( array(
                "power" => "windfallproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => array( 1,2,3,4),
                    "worldfilter" => array( 'windfall')
                )
             ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    44 => array(
      "name" => "Gem World",    // Monde minéral
      "nametr" => self::_("Gem World"), "nametrc" => clienttranslate("Gem World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 1,
                      "draw" => 1
                 )
               ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    45 => array(
      "name" => "Pre-Sentient Race",    // Race primitive
      "nametr" => self::_("Pre-Sentient Race"), "nametrc" => clienttranslate("Pre-Sentient Race"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
      ),
      "category" => array( 'windfall'),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    46 => array(
      "name" => "Radioactive World",
      "nametr" => self::_("Radioactive World"), "nametrc" => clienttranslate("Radioactive World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
      ),
      "category" => array('windfall'),
      "windfalltype" => 2,
      "text" => "",
      "power_to_active" => false
   ),
    47 => array(
      "name" => "Black Market Trading World",
      "nametr" => self::_("Black Market Trading World"), "nametrc" => clienttranslate("Black Market Trading World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            4 => array( array(
                  "power" => "consumeforsell",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                    "usepowers" => false,
                    "repeat" => 1
                 )
               ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    48 => array(
      "name" => "Blaster Gem Mine",
      "nametr" => self::_("Blaster Gem Mine"), "nametrc" => clienttranslate("Blaster Gem Mine"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
               )
            ))
      ),
      "category" => array( "windfall"),
      "windfalltype" => 2,
      "text" => "",
      "power_to_active" => false
   ),
    49 => array(
      "name" => "Mining World",
      "nametr" => self::_("Mining World"), "nametrc" => clienttranslate("Mining World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
            5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 2,
                      "draw" => 1
                 )
               ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    50 => array(
      "name" => "Prosperous World",
      "nametr" => self::_("Prosperous World"), "nametrc" => clienttranslate("Prosperous World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
            4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'vp' => 1),
                      "repeat" => 1
                 )
            )),
             5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 1
                 )
               ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    51 => array(
      "name" => "Rebel Underground",
      "nametr" => self::_("!Rebel! Underground"), "nametrc" => clienttranslate("Rebel Underground"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 4,
      "powers" => array(
            5 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
            ))
      ),
      "category" => array( 'military','rebel'),
      "text" => "",
      "power_to_active" => false
   ),
    52 => array(
      "name" => "Deserted Alien Colony",
      "nametr" => self::_("Deserted £Alien£ Colony"), "nametrc" => clienttranslate("Deserted Alien Colony"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 4,
      "powers" => array(


      ),
      "category" => array( 'windfall','alien'),
      "windfalltype" => 4,
      "text" => "",
      "power_to_active" => false
   ),
    53 => array(
      "name" => "Alien Robotic Factory",
      "nametr" => self::_("£Alien£ Robotic Factory"), "nametrc" => clienttranslate("Alien Robotic Factory"),
      "qt" => 1,
      "type" => "world",
      "cost" => 6,
      "vp" => 5,
      "powers" => array(
            5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 4
                 )
               ))
      ),
      "category" => array( 'alien'),
      "text" => "",
      "power_to_active" => false
   ),
    54 => array(
      "name" => "Artist Colony",
      "nametr" => self::_("Artist Colony"), "nametrc" => clienttranslate("Artist Colony"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 1
                 )
                ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    55 => array(
      "name" => "Destroyed World",
      "nametr" => self::_("Destroyed World"), "nametrc" => clienttranslate("Destroyed World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 0,
      "powers" => array(
      ),
      "category" => array( 'windfall'),
      "windfalltype" => 2,
      "text" => "",
      "power_to_active" => false
   ),
    56 => array(
      "name" => "Gambling World",
      "nametr" => self::_("Gambling World"), "nametrc" => clienttranslate("Gambling World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
           4 => array(
                 array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'vp' => 1),
                      "repeat" => 1
                 )
                ),
                 array(
                  "power" => "gambling",
                  "icon" => '',
                  "text" => "",
                  "arg" => array()
                )

           )
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    57 => array(
      "name" => "New Survivalists",
      "nametr" => self::_("New Survivalists"), "nametrc" => clienttranslate("New Survivalists"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
           4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1),
                      "output" => array( 'card' => 1),
                      "repeat" => 1
                 )
           )),
            5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 1
                 )
               ))
      ),
      "category" => array( 'military'),
      "text" => "",
      "power_to_active" => false
   ),
    58 => array(    // Monde renégat
      "name" => "Outlaw World",
      "nametr" => self::_("Outlaw World"), "nametrc" => clienttranslate("Outlaw World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
            )
            )),
            4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'vp' => 1, 'card' => 1),
                      "repeat" => 1
                 )
            ))
      ),
      "category" => array( 'military'),
      "text" => "",
      "power_to_active" => false
   ),
    59 => array(
      "name" => "Rebel Fuel Cache",
      "nametr" => self::_("!Rebel! Fuel Cache"), "nametrc" => clienttranslate("Rebel Fuel Cache"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
      ),
      "category" => array( 'military', 'rebel', 'windfall'),
      "windfalltype" => 2,
      "text" => "",
      "power_to_active" => false
   ),
    60 => array(
      "name" => "Runaway Robots",
      "nametr" => self::_("Runaway Robots"), "nametrc" => clienttranslate("Runaway Robots"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
           5 => array( array(
                  "power" => "drawifproduce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "card" => 1
                 )
               ))
      ),
      "category" => array( 'military', 'windfall'),
      "windfalltype" => 2,
      "text" => "",
      "power_to_active" => false
   ),
    61 => array(
      "name" => "Secluded World",
      "nametr" => self::_("Secluded World"), "nametrc" => clienttranslate("Secluded World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'card' => 1),
                      "repeat" => 1
                 )
            )),

        5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 1
                 )
               ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    62 => array(
      "name" => "Star Nomad Lair",  // Campement nomade
      "nametr" => self::_("Star Nomad Lair"), "nametrc" => clienttranslate("Star Nomad Lair"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(

            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
            )),
             's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1),
                    "card" => 1,
                    "fromthisworld" => true
               )
             ))
      ),
      "category" => array( 'windfall', 'military'),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),
    63 => array(
      "name" => "Alien Robot Sentry",
      "nametr" => self::_("£Alien£ Robot Sentry"), "nametrc" => clienttranslate("Alien Robot Sentry"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 2,
      "powers" => array(
      ),
      "category" => array( 'windfall', 'military', 'alien'),
      "windfalltype" => 4,
      "text" => "",
      "power_to_active" => false
   ),
    64 => array(
      "name" => "Aquatic Uplift Race",
      "nametr" => self::_("Aquatic *Uplift* Race"), "nametrc" => clienttranslate("Aquatic Uplift Race"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 2,
      "powers" => array(
      ),
      "category" => array( 'windfall', 'military', 'uplift', 'chromosome'),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    65 => array(
      "name" => "Avian Uplift Race",
      "nametr" => self::_("Avian *Uplift* Race"), "nametrc" => clienttranslate("Avian Uplift Race"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 2,
      "powers" => array(
      ),
      "category" => array( 'windfall', 'military', 'uplift', 'chromosome'),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    66 => array(
      "name" => "Comet Zone",
      "nametr" => self::_("Comet Zone"), "nametrc" => clienttranslate("Comet Zone"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
            5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 2,
                      "draw" => 1
                 )
            ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    67 => array(
      "name" => "Former Penal Colony",
      "nametr" => self::_("Former Penal Colony"), "nametrc" => clienttranslate("Former Penal Colony"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(

        3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
               )
           ))

      ),
      "category" => array( 'windfall','military'),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),
    68 => array(
      "name" => "New Vinland",
      "nametr" => self::_("New Vinland"), "nametrc" => clienttranslate("New Vinland"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'card' => 2),
                      "repeat" => 1
                 )
            )),
            5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 1
                 )
               ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    69 => array(
      "name" => "Rebel Miners", // Mineurs
      "nametr" => self::_("!Rebel! Miners"), "nametrc" => clienttranslate("Rebel Miners"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
        5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 2
                 )
               ))
      ),
      "category" => array( 'military','rebel'),
      "text" => "",
      "power_to_active" => false
   ),
    70 => array(
      "name" => "Reptilian Uplift Race",
      "nametr" => self::_("Reptilian *Uplift* Race"), "nametrc" => clienttranslate("Reptilian Uplift Race"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 2,
      "powers" => array(
      ),
      "category" => array( 'military', 'windfall', 'uplift', 'chromosome'),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    71 => array(
      "name" => "Space Port",
      "nametr" => self::_("Space Port"), "nametrc" => clienttranslate("Space Port"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
        's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 2),
                    "card" => 2
               )
            )),
         5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 1
                 )
               ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    72 => array(
      "name" => "Spice World",
      "nametr" => self::_("Spice World"), "nametrc" => clienttranslate("Spice World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
        's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1),
                    "card" => 2
               )
            )),
         5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 1
                 )
               ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => true
   ),
    73 => array(
      "name" => "Alien Rosetta Stone World",
      "nametr" => self::_("£Alien£ Rosetta Stone World"), "nametrc" => clienttranslate("Alien Rosetta Stone World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 3,
      "powers" => array(
            3 => array(
              array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2,
                    "worldtype" => array( 4)
               )
             ),
              array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -2,
                    "worldtype" => array( 4)
               )
             )
           ),
            5 => array( array(
                "power" => "windfallproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => array( 4),
                    "worldfilter" => array( 'windfall')
                )
            ))
      ),
      "category" => array( 'alien'),
      "text" => "",
      "power_to_active" => false
   ),
    74 => array(
      "name" => "Bio-Hazard Mining World",// Monde minier hostile
      "nametr" => self::_("Bio-Hazard Mining World"), "nametrc" => clienttranslate("Bio-Hazard Mining World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 0,
      "powers" => array(
             's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 3),
                    "card" => 2
               )
             )),
             5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 2
                 )
               ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    75 => array(
      "name" => "Galactic Resort",
      "nametr" => self::_("Galactic Resort"), "nametrc" => clienttranslate("Galactic Resort"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
         4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'vp' => 1, 'card' => 1),
                      "repeat" => 1
                 )
           ))
      ),
      "category" => array('windfall'),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),
    76 => array(
      "name" => "Pirate World",
      "nametr" => self::_("Pirate World"), "nametrc" => clienttranslate("Pirate World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1),
                    "card" => 3,
                    "fromthisworld" => true
               )
            ))
      ),
      "category" => array( 'military','windfall'),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),
    77 => array(
      "name" => "Plague World", // Monde contaminé
      "nametr" => self::_("Plague World"), "nametrc" => clienttranslate("Plague World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 0,
      "powers" => array(
        4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 3),
                      "output" => array( 'vp' => 1, 'card' => 1),
                      "repeat" => 1
                 )
           )),

        5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 3
                 )
               ))


      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    78 => array(
      "name" => "Rebel Warrior Race",
      "nametr" => self::_("!Rebel! Warrior Race"), "nametrc" => clienttranslate("Rebel Warrior Race"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
        3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
               )
           ))
      ),
      "category" => array( "military", "windfall", "rebel"),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    79 => array(
      "name" => "Alien Robot Scout Ship",
      "nametr" => self::_("£Alien£ Robot Scout Ship"), "nametrc" => clienttranslate("Alien Robot Scout Ship"),
      "qt" => 1,
      "type" => "world",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
        3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
               )
           ))

      ),
      "category" => array(  "windfall", "military", "alien"),
      "windfalltype" => 4,
      "text" => "",
      "power_to_active" => false
   ),
    80 => array(
      "name" => "Deserted Alien Outpost",
      "nametr" => self::_("Deserted £Alien£ Outpost"), "nametrc" => clienttranslate("Deserted Alien Outpost"),
      "qt" => 1,
      "type" => "world",
      "cost" => 4,
      "vp" => 3,
      "powers" => array(

      ),
      "category" => array( "windfall", "alien"),
      "windfalltype" => 4,
      "text" => "",
      "power_to_active" => false
   ),
    81 => array(
      "name" => "Distant World",
      "nametr" => self::_("Distant World"), "nametrc" => clienttranslate("Distant World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1),
                    "card" => 3
               )
            )),
             5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 3
                 )
               ))

      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    82 => array(
      "name" => "Imperium Armaments World",
      "nametr" => self::_("+Imperium+ Armaments World"), "nametrc" => clienttranslate("Imperium Armaments World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
               )
            )),
             5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 2
                 )
               ))

      ),
      "category" => array( "imperium"),
      "text" => "",
      "power_to_active" => false
   ),
    83 => array(
      "name" => "Malevolent Life Forms",
      "nametr" => self::_("Malevolent Life Forms"), "nametrc" => clienttranslate("Malevolent Life Forms"),
      "qt" => 1,
      "type" => "world",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
             1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
             )),
             5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 3
                 )
               ))

      ),
      "category" => array( "military"),
      "text" => "",
      "power_to_active" => false
   ),
    84 => array(
      "name" => "Merchant World",
      "nametr" => self::_("Merchant World"), "nametrc" => clienttranslate("Merchant World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
              's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1,2,3,4),
                    "card" => 2
               )
              )),
             4 => array( array(
                "power" => "consumecard",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "output" => array( 'vp' => 1),
                    "repeat" => 2
               )
             ))
     ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    85 => array(
      "name" => "Tourist World",
      "nametr" => self::_("Tourist World"), "nametrc" => clienttranslate("Tourist World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
           4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "inputfactor" => 2,
                      "output" => array( 'vp' => 3),
                      "repeat" => 1
                 )
           ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    86 => array(
      "name" => "Galactic Trendsetters",
      "nametr" => self::_("Galactic Trendsetters"), "nametrc" => clienttranslate("Galactic Trendsetters"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(
            4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'vp' => 2),
                      "repeat" => 1
                 )
            ))

      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    87 => array(
      "name" => "Lost Alien Warship",
      "nametr" => self::_("Lost £Alien£ Warship"), "nametrc" => clienttranslate("Lost Alien Warship"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2
               )
            ))
      ),
      "category" => array( "windfall","military","alien"),
      "windfalltype" => 4,
      "text" => "",
      "power_to_active" => false
   ),
    88 => array(
      "name" => "Lost Species Ark World",   // Arche monde
      "nametr" => self::_("Lost Species Ark World"), "nametrc" => clienttranslate("Lost Species Ark World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(
            5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 3,
                      "draw" => 2
                 )
               ))


      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    89 => array(
      "name" => "New Earth",
      "nametr" => self::_("New Earth"), "nametrc" => clienttranslate("New Earth"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(
            4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'vp' => 1, 'card' => 1),
                      "repeat" => 1
                 )
            )),

            5 => array( array(
                      "power" => "produce",
                      "icon" => '',
                      "text" => "",
                      "arg" => array(
                          "resource" => 2
                     )
                   ))
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    90 => array(
      "name" => "Rebel Outpost",
      "nametr" => self::_("!Rebel! Outpost"), "nametrc" => clienttranslate("Rebel Outpost"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 5,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
               )
            ))
      ),
      "category" => array( 'military','rebel'),
      "text" => "",
      "power_to_active" => false
   ),
    91 => array(
      "name" => "Terraformed World",
      "nametr" => self::_("Terraformed World"), "nametrc" => clienttranslate("Terraformed World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 5,
      "powers" => array(
            4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'vp' => 1),
                      "repeat" => 1
                 )
            ))

      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    92 => array(
      "name" => "Deserted Alien Library",
      "nametr" => self::_("Deserted £Alien£ Library"), "nametrc" => clienttranslate("Deserted Alien Library"),
      "qt" => 1,
      "type" => "world",
      "cost" => 6,
      "vp" => 5,
      "powers" => array(

      ),
      "category" => array( "windfall", "alien"),
      "windfalltype" => 4,
      "text" => "",
      "power_to_active" => false
   ),
    93 => array(
      "name" => "Lost Alien Battle Fleet",  // Flotte alien perdue
      "nametr" => self::_("Lost £Alien£ Battle Fleet"), "nametrc" => clienttranslate("Lost Alien Battle Fleet"),
      "qt" => 1,
      "type" => "world",
      "cost" => 6,
      "vp" => 4,
      "powers" => array(
        3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 3
               )
           )),
        5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 4
                 )
               ))
      ),
      "category" => array( "military", "alien"),
      "text" => "",
      "power_to_active" => false
   ),
    94 => array(
      "name" => "Rebel Base",
      "nametr" => self::_("!Rebel! Base"), "nametrc" => clienttranslate("Rebel Base"),
      "qt" => 1,
      "type" => "world",
      "cost" => 6,
      "vp" => 6,
      "powers" => array(

      ),
      "category" => array( "military", "rebel"),
      "text" => "",
      "power_to_active" => false
   ),
    95 => array(
      "name" => "Rebel Homeworld",
      "nametr" => self::_("!Rebel! Homeworld"), "nametrc" => clienttranslate("Rebel Homeworld"),
      "qt" => 1,
      "type" => "world",
      "cost" => 7,
      "vp" => 7,
      "powers" => array(

      ),
      "category" => array( "military", "rebel" ),
      "text" => "",
      "power_to_active" => false
   ),



    /*********************************************************************************************************************/
    /*********************************************************************************************************************/
    /*********************************************************************************************************************/
    /*********************************************************************************************************************/
    /*********** EXTENSION 1 *******************/


    100 => array(
      "name" => "Galactic Genome Project",
      "nametr" => self::_("Galactic Genome Project"),
      "nametrc" => clienttranslate("Galactic Genome Project"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 3),
                    "output" => array( 'vp' => 3),
                    "inputfactor" => 2,
                    "repeat" => 1
               )
            )),

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    101 => array(
      "name" => "Terraforming Guild",
      "nametr" => self::_("€Terraforming€ Guild"),
      "nametrc" => clienttranslate("Terraforming Guild"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
             3 => array( array(
                "power" => "drawifsettle",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
             )),
             5 => array( array(
                "power" => "windfallproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => array( 1,2,3,4),
                    "worldfilter" => array( 'windfall')
                )
             ))


      ),
      "category" => array( 'terraforming' ),
      "text" => "",
      "power_to_active" => false
   ),
    102 => array(
      "name" => "Improved Logistics",
      "nametr" => self::_("Improved Logistics"),
      "nametrc" => clienttranslate("Improved Logistics"),
      "qt" => 2,
      "type" => "development",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
             3 => array( array(
                "power" => "settletwice",
                "icon" => '',
                "text" => "",
                "arg" => array(
               )
             )),

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    103 => array(
      "name" => "Galactic Bazaar",
      "nametr" => self::_("Galactic Bazaar"),
      "nametrc" => clienttranslate("Galactic Bazaar"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1),
                    "card" => 1
                )
            )),
            4 => array( array(
                "power" => "consumecard",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "output" => array( 'vp' => 1),
                    "repeat" => 1
               )
            ))

      ),
      "category" => array('windfall'),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false

   ),
    104 => array(
      "name" => "Rebel Colony",
      "nametr" => self::_("!Rebel! Colony"),
      "nametrc" => clienttranslate("Rebel Colony"),
      "qt" => 1,
      "type" => "world",
      "cost" => 4,
      "vp" => 4,
      "powers" => array(
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'vp' => 1),
                    "repeat" => 1
               )
            ))

      ),
      "category" => array(  'military', 'rebel'),
      "text" => "",
      "power_to_active" => false
   ),
    105 => array(
      "name" => "Separatist Colony",
      "nametr" => self::_("Separatist Colony"),
      "nametrc" => clienttranslate("Separatist Colony"),
      "qt" => 1,
      "type" => "world",
      "startworld_number" => 5,
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
          1 => array(
            array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 2
               )
           )),
          3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
               )
           ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    106 => array(
      "name" => "Space Mercenaries",
      "nametr" => self::_("Space Mercenaries"),
      "nametrc" => clienttranslate("Space Mercenaries"),
      "qt" => 2,
      "type" => "development",
      "cost" => 1,
      "vp" => 0,
      "powers" => array(
            3 => array(
                array(
                 "power" => "militaryforce",
                 "icon" => '',
                 "text" => "",
                 "arg" => array(
                     "force" => 1
                )
               ),

               array(
                "power" => "militaryforcetmp_discard",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "repeat" => 2
               )
            ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => true
   ),
    107 => array(
      "name" => "Ancient Race",
      "nametr" => self::_("Ancient Race"),
      "nametrc" => clienttranslate("Ancient Race"),
      "qt" => 1,
      "type" => "world",
      "startworld_number" => 6,
      "cost" => 2,
      "vp" => 1,
      "powers" => array(

      ),
      "category" => array('windfall'),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    108 => array(
      "name" => "Alien toy shop",
      "nametr" => self::_("£Alien£ toy shop"),
      "nametrc" => clienttranslate("Alien toy shop"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 1,
      "powers" => array(
            4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 4),
                      "output" => array( 'vp' => 2),
                      "repeat" => 1,
                      "fromthisworld" => true
                 )
            )),

      ),
      "category" => array( 'alien','windfall' ),
      "windfalltype" => 4,
      "text" => "",
      "power_to_active" => false
   ),
    109 => array(
      "name" => "Deserted Alien World",
      "nametr" => self::_("Deserted £Alien£ World"),
      "nametrc" => clienttranslate("Deserted Alien World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 2,
      "powers" => array(
          1 => array(
            array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
           )),
          3 => array(
                array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => 2,
                        "worldtype" => array( 4)
                   )
               ),
                array(
                    "power" => "settlecost",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "cost" => -2,
                        "worldtype" => array( 4)
                   )
               )
           )
      ),
      "category" => array(  'alien'),
      "text" => "",
      "power_to_active" => false
   ),
   // Not used anymore, will be deleted
    110 => array(
      "name" => "Gambling World",
      "nametr" => self::_("Gambling World"), "nametrc" => clienttranslate("Gambling World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
           4 => array(
                 array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'vp' => 1),
                      "repeat" => 1
                 )
                ),
                 array(
                  "power" => "gambling",
                  "icon" => '',
                  "text" => "",
                  "arg" => array()
                )

           )
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    111 => array(
      "name" => "Doomed World",
      "nametr" => self::_("Doomed World"),
      "nametrc" => clienttranslate("Doomed World"),
      "qt" => 1,
      "type" => "world",
      "startworld_number" => 8,
      "cost" => 1,
      "vp" => -1,
      "powers" => array(
          1 => array(
            array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
           )),
            3 => array( array(
                "power" => "colonyship",
                "icon" => '',
                "text" => "",
                "arg" => array(
               )
            ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    112 => array(
      "name" => "Hive World",
      "nametr" => self::_("Hive World"),
      "nametrc" => clienttranslate("Hive World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
                5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 3
                 )
                ))

      ),
      "category" => array( 'military' ),
      "text" => "",
      "power_to_active" => false
   ),
    113 => array(
      "name" => "Volcanic World",
      "nametr" => self::_("Volcanic World"),
      "nametrc" => clienttranslate("Volcanic World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
                5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 2
                 )
                ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    114 => array(
      "name" => "Clandestine Uplift Lab",
      "nametr" => self::_("Clandestine *Uplift* Lab"),
      "nametrc" => clienttranslate("Clandestine Uplift Lab"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
          1 => array(
            array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
           )),
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 3),
                    "card" => 0,
                    "onecardby" => 'chromosome'
                )
            )),
           4 => array(
                 array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'vp' => 1, 'card'=>1),
                      "repeat" => 1
                  )
                ),
          )


      ),
      "category" => array( 'uplift', 'chromosome', 'military'  ),
      "text" => "",
      "power_to_active" => false
   ),
    115 => array(
      "name" => "Smuggling Lair",
      "nametr" => self::_("Smuggling Lair"),
      "nametrc" => clienttranslate("Smuggling Lair"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
           4 => array(
                 array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'card'=>2),
                      "repeat" => 1
                  )
                ),
          ),

      ),
      "category" => array('windfall', 'military'),
      "windfalltype" => 2,
      "text" => "",
      "power_to_active" => false
   ),
    116 => array(
      "name" => "Galactic Studios",
      "nametr" => self::_("Galactic Studios"),
      "nametrc" => clienttranslate("Galactic Studios"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(
           4 => array(
                 array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'vp' => 1, 'card'=>1),
                      "repeat" => 1
                  )
                ),
          ),
           5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 1,
                      "draw" => 1
                 )
               ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    117 => array(
      "name" => "Rebel Sympathisers",
      "nametr" => self::_("!Rebel! Sympathisers"),
      "nametrc" => clienttranslate("Rebel Sympathisers"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
           5 => array( array(
                  "power" => "drawifproduce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "card" => 1
                 )
               ))

      ),
      "category" => array( 'military','windfall' ,'rebel'),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),
    118 => array(
      "name" => "Damaged Alien Factory",
      "nametr" => self::_("Damaged £Alien£ Factory"),
      "nametrc" => clienttranslate("Damaged Alien Factory"),
      "qt" => 1,
      "type" => "world",
      "startworld_number" => 7,
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
        5 => array( array(
                  "power" => "produceifdiscard",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 4
                 )
               ))

      ),
      "category" => array( 'military','alien' ),
      "text" => "",
      "power_to_active" => false
   ),
    119 => array(
      "name" => "Imperium Lords",
      "nametr" => self::_("+Imperium+ Lords"),
      "nametrc" => clienttranslate("Imperium Lords"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
        5 => array( array(
                  "power" => "drawformilitary",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                 )
               ))

      ),
      "category" => array( 'imperium' ),
      "text" => "",
      "power_to_active" => false
   ),


    /*********************************************************************************************************************/
    /*********************************************************************************************************************/
    /*********************************************************************************************************************/
    /*********************************************************************************************************************/
    /*********** EXTENSION 2 *******************/


    130 => array(
      "name" => "Gambling World",
      "nametr" => self::_("Gambling World"), "nametrc" => clienttranslate("Gambling World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
           4 => array(
                 array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'vp' => 1),
                      "repeat" => 1
                 )
                ),
                 array(
                  "power" => "rvi_gambling",
                  "icon" => '',
                  "text" => "",
                  "arg" => array()
                )

           )
      ),
      "category" => array(),
      "text" => "",
      "power_to_active" => false
   ),
    131 => array(
      "name" => "Rebel Cantina",
      "nametr" => self::_("!Rebel! Cantina"),
      "nametrc" => clienttranslate("Rebel Cantina"),
      "qt" => 1,
      "type" => "world",
      "startworld_number" => 9,
      "cost" => 2,
      "vp" => 0,
      "powers" => array(
              3 => array(
                array(
                    "power" => "diplomat",
                    "noalien" => true,
                    "discount" => 0,
                    "icon" => '',
                    "text" => "",
                    "arg" => array()
               ),
            ),
            5 => array( array(
                      "power" => "drawforrebel",
                      "icon" => '',
                      "text" => "",
                      "arg" => array(
                     )
                   ))

      ),
      "category" => array( 'rebel'  ),
      "text" => "",
      "power_to_active" => false
   ),
    132 => array(
      "name" => "Galactic Developers",
      "nametr" => self::_("Galactic Developers"),
      "nametrc" => clienttranslate("Galactic Developers"),
      "qt" => 1,
      "type" => "world",
      "startworld_number" => 10,
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
             2 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
             )),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'vp' => 1),
                    "repeat" => 1
               )
            ))


      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    133 => array(
      "name" => "Imperium Warlord",
      "nametr" => self::_("+Imperium+ Warlord"),
      "nametrc" => clienttranslate("Imperium Warlord"),
      "qt" => 1,
      "type" => "world",
      "startworld_number" => 11,
      "cost" => 2,
      "vp" => 2,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
             3 => array(
                 array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => 1
                   ),
                ),
                 array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => 1,
                        "worldfilter" => 'rebel'
                   )
               )
            )

      ),
      "category" => array( "imperium" ),
      "text" => "",
      "power_to_active" => false
   ),
    134 => array(
      "name" => "Insect Uplift Race",
      "nametr" => self::_("Insect *Uplift* Race"),
      "nametrc" => clienttranslate("Insect Uplift Race"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 2,
      "powers" => array(

      ),
      "category" => array( "uplift", "chromosome","military", "windfall" ),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    135 => array(
      "name" => "Universal Symbionts",
      "nametr" => self::_("Universal Symbionts"),
      "nametrc" => clienttranslate("Universal Symbionts"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 1,
      "powers" => array(
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'vp' => 1),
                    "repeat" => 1
               )
            )),
            5 => array( array(
                "power" => "windfallproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => array( 3),
                    "worldfilter" => array( 'windfall'),
                    "notthisworld" => true
                )
            ))

      ),
      "category" => array( "windfall" ),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    136 => array(
      "name" => "Gene Designers",
      "nametr" => self::_("Gene Designers"),
      "nametrc" => clienttranslate("Gene Designers"),
      "qt" => 1,
      "type" => "world",
      "cost" => 6,
      "vp" => 3,
      "powers" => array(
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 3),
                    "output" => array( 'vp' => 1, 'card' => 1),
                    "repeat" => 3
               )
            )),

            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 3
             )
            ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    137 => array(
      "name" => "Alien Uplift Center",
      "nametr" => self::_("£Alien£ *Uplift* Center"),
      "nametrc" => clienttranslate("Alien Uplift Center"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 4,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
            3 => array(
              array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2,
                    "worldtype" => array( 3)
               )
             ),
              array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -2,
                    "worldtype" => array( 3)
               )
             )
           ),

      ),
      "category" => array( "alien","uplift","military","windfall" ),
      "windfalltype" => 4,
      "text" => "",
      "power_to_active" => false
   ),
    138 => array(
      "name" => "Alien Data Repository",
      "nametr" => self::_("£Alien£ Data Repository"),
      "nametrc" => clienttranslate("Alien Data Repository"),
      "qt" => 1,
      "type" => "world",
      "cost" => 7,
      "vp" => 6,
      "powers" => array(
            1 => array( array(
                "power" => "exploremix",
                "icon" => '',
                "text" => "",
                "arg" => array(
               ),
            )),
            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 4
             )
            ))

      ),
      "category" => array( "alien"  ),
      "text" => "",
      "power_to_active" => false
   ),
    139 => array(
      "name" => "Alien Monolith",
      "nametr" => self::_("£Alien£ Monolith"),
      "nametrc" => clienttranslate("Alien Monolith"),
      "qt" => 1,
      "type" => "world",
      "cost" => 8,
      "vp" => 8,
      "powers" => array(
      ),
      "category" => array(  "alien","military","windfall","prestige"),
      "windfalltype" => 4,
      "text" => "",
      "power_to_active" => false
   ),
    140 => array(
      "name" => "Imperium Cloaking Technology",
      "nametr" => self::_("+Imperium+ Cloaking Technology"),
      "nametrc" => clienttranslate("Imperium Cloaking Technology"),
      "qt" => 2,
      "type" => "development",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
              3 => array(
                array(
                    "power" => "cloaking",
                    "discount" => -2,
                    "icon" => '',
                    "text" => "",
                    "arg" => array()
               ),
                array(
                    "power" => "discardtotakeover",
                    "targetfilter" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                   )
               )
            )

      ),
      "category" => array( "imperium" ),
      "text" => "",
      "power_to_active" => false
   ),
    141 => array(
      "name" => "Galactic Advertisers",
      "nametr" => self::_("Galactic Advertisers"),
      "nametrc" => clienttranslate("Galactic Advertisers"),
      "qt" => 2,
      "type" => "development",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1,2,3,4),
                    "card" => 1
                )
            )),
            4 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
            ))


      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    142 => array(
      "name" => "Imperium Troops",
      "nametr" => self::_("+Imperium+ Troops"),
      "nametrc" => clienttranslate("Imperium Troops"),
      "qt" => 2,
      "type" => "development",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
             3 => array(
                 array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => 1
                   ),
                ),
                 array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => 1,
                        "worldfilter" => 'rebel'
                   )
               )
            )

      ),
      "category" => array( "imperium" ),
      "text" => "",
      "power_to_active" => false
   ),
    143 => array(
      "name" => "Rebel Pact",
      "nametr" => self::_("!Rebel! Pact"),
      "nametrc" => clienttranslate("Rebel Pact"),
      "qt" => 2,
      "type" => "development",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            1 => array( array(
                "power" => "exploremix",
                "icon" => '',
                "text" => "",
                "arg" => array(
               ),
            )),

             3 => array(
                 array(
                    "power" => "diplomatdiscount",
                    "discount" => -2,
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                   ),
                ),
                 array(
                    "power" => "defense",
                    "bonus" => 1,
                    "rebel" => 2,
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                   )
               )
              )

      ),
      "category" => array( "rebel" ),
      "text" => "",
      "power_to_active" => false
   ),
    145 => array(
      "name" => "Galactic Salon",
      "nametr" => self::_("Galactic Salon"),
      "nametrc" => clienttranslate("Galactic Salon"),
      "qt" => 2,
      "type" => "development",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
            4 => array( array(
                "power" => "vpchip",
                "vp" => 1,
                "icon" => '',
                "text" => "",
                "arg" => array(
               )
            ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    146 => array(
      "name" => "Prospecting Guild",
      "nametr" => self::_("Prospecting Guild"),
      "nametrc" => clienttranslate("Prospecting Guild"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            1 => array( array(
                "power" => "exploremix",
                "icon" => '',
                "text" => "",
                "arg" => array(
               ),
            )),
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1,2,3,4),
                    "card" => 1
                )
            )),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 2),
                    "output" => array( 'vp' => 1,'card'=>1),
                    "repeat" => 1
               )
            ))


      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    147 => array(
      "name" => "Imperium Seat",
      "nametr" => self::_("+Imperium+ Seat"),
      "nametrc" => clienttranslate("Imperium Seat"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
        3 => array(
                 array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => 1,
                        "worldfilter" => 'rebel'
                   )
               ),
                 array(
                    "power" => "takeover",
                    "targetfilter" => "rebel",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                   )
               )
           )
      ),
      "category" => array(  "imperium" ),
      "text" => "",
      "power_to_active" => false
   ),
    148 => array(
      "name" => "Galactic Exchange",
      "nametr" => self::_("Galactic Exchange"),
      "nametrc" => clienttranslate("Galactic Exchange"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'vp' => 1, 'card' => 1),
                    "inputfactor" => 1,
                    "repeat" => 4,
                    "different" => true
               )
            )),

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    149 => array(
      "name" => "Rebel Alliance",
      "nametr" => self::_("!Rebel! Alliance"),
      "nametrc" => clienttranslate("Rebel Alliance"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
              3 => array(
                array(
                    "power" => "diplomat",
                    "discount" => -2,
                    "rebel" => true,
                    "icon" => '',
                    "text" => "",
                    "arg" => array()
               ),
                array(
                    "power" => "takeover",
                    "targetfilter" => "imperium",
                    "bonus" => "two_per_rebel",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                   )
               )
            )

      ),
      "category" => array( "rebel" ),
      "text" => "",
      "power_to_active" => false
   ),
    150 => array(
      "name" => "Galactic Bankers",
      "nametr" => self::_("Galactic Bankers"),
      "nametrc" => clienttranslate("Galactic Bankers"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            2 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
            )),
            4 => array( array(
                "power" => "consumecard",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "output" => array( 'vp' => 1),
                    "repeat" => 2
               )
            ))

      ),
      "category" => array( "prestige" ),
      "text" => "",
      "power_to_active" => false
   ),
    151 => array(
      "name" => "Pan-Galactic Research",
      "nametr" => self::_("Pan-Galactic Research"),
      "nametrc" => clienttranslate("Pan-Galactic Research"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 4,
      "powers" => array(
              1 => array(
                array(
                    "power" => "exploredraw",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "card" => 2
                   )
               ),
                array(
                    "power" => "explorekeep",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "card" => 1
                   )
               )

           ),
            2 => array( array(
                "power" => "devcost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1
               )
            )),
            5 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 2
               )
            )),


      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    152 => array(
      "name" => "Uplift Code",
      "nametr" => self::_("*Uplift* Code"),
      "nametrc" => clienttranslate("Uplift Code"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
        5 => array( array(
                  "power" => "drawforchromosome",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                 )
               ))
      ),
      "category" => array( "uplift" ,"prestige"),
      "text" => "",
      "power_to_active" => false
   ),
    153 => array(
      "name" => "Primitive Rebel World",
      "nametr" => self::_("Primitive !Rebel! World"),
      "nametrc" => clienttranslate("Primitive Rebel World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            3 => array(
               array(
                "power" => "militaryforcetmp_discard",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "repeat" => 1
               )
            ))

      ),
      "category" => array( "rebel", "windfall", "military" ),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => true
   ),
    154 => array(
      "name" => "Devolved Uplift Race",
      "nametr" => self::_("Devolved *Uplift* Race"),
      "nametrc" => clienttranslate("Devolved Uplift Race"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
                5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 1
                 )
                ))

      ),
      "category" => array( "military", "uplift", "chromosome" ),
      "text" => "",
      "power_to_active" => false
   ),
    155 => array(
      "name" => "Dying Colony",
      "nametr" => self::_("Dying Colony"),
      "nametrc" => clienttranslate("Dying Colony"),
      "qt" => 1,
      "type" => "world",
      "cost" => 0,
      "vp" => 0,
      "powers" => array(
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'vp' => 1),
                    "repeat" => 1
               )
            ))

      ),
      "category" => array( "windfall" ),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),
    156 => array(
      "name" => "Smuggling World",
      "nametr" => self::_("Smuggling World"),
      "nametrc" => clienttranslate("Smuggling World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 0,
      "powers" => array(
            1 => array( array(
                "power" => "exploremix",
                "icon" => '',
                "text" => "",
                "arg" => array(
               ),
            )),
            3 => array(
              array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "worldtype" => array( 1)
               )
             ),
              array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1,
                    "worldtype" => array( 1)
               )
             )
           ),
            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 1
             )
            ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    157 => array(
      "name" => "Rebel Convict Mines",
      "nametr" => self::_("!Rebel! Convict Mines"),
      "nametrc" => clienttranslate("Rebel Convict Mines"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            3 => array(
               array(
                "power" => "militaryforcetmp_discard",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "repeat" => 1
               )
            ))

      ),
      "category" => array( "rebel","windfall", "military" ),
      "windfalltype" => 2,
      "text" => "",
      "power_to_active" => true
   ),
    158 => array(
      "name" => "Gem Smugglers",
      "nametr" => self::_("Gem Smugglers"),
      "nametrc" => clienttranslate("Gem Smugglers"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 1,
      "powers" => array(
            1 => array( array(
                "power" => "exploremix",
                "icon" => '',
                "text" => "",
                "arg" => array(
               ),
            )),
            3 => array(
              array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1,
                    "worldtype" => array( 2)
               )
             ),
              array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => 1,
                        "worldtype" => array( 2)
                   )
               )
           ),

      ),
      "category" => array( "windfall" ),
      "windfalltype" => 2,
      "text" => "",
      "power_to_active" => false
   ),
    159 => array(
      "name" => "Blaster Runners",
      "nametr" => self::_("Blaster Runners"),
      "nametrc" => clienttranslate("Blaster Runners"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            1 => array( array(
                "power" => "exploremix",
                "icon" => '',
                "text" => "",
                "arg" => array(
               ),
            )),
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
               )
            ))
      ),
      "category" => array( "military" ),
      "text" => "",
      "power_to_active" => false
   ),
    160 => array(
      "name" => "Interstellar Prospectors",
      "nametr" => self::_("Interstellar Prospectors"),
      "nametrc" => clienttranslate("Interstellar Prospectors"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
            5 => array( array(
                "power" => "windfallproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => array( 2),
                    "worldfilter" => array( 'windfall')
               )
             ),
              array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 2
                 )
               ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    161 => array(
      "name" => "Rebel Stronghold",
      "nametr" => self::_("!Rebel! Stronghold"),
      "nametrc" => clienttranslate("Rebel Stronghold"),
      "qt" => 1,
      "type" => "world",
      "cost" => 9,
      "vp" => 9,
      "powers" => array(
      ),
      "category" => array(  "military","rebel","prestige"),
      "text" => "",
      "power_to_active" => false
   ),
    162 => array(
      "name" => "Abandoned Alien Uplift Camp",
      "nametr" => self::_("Abandoned £Alien£ *Uplift* Camp"),
      "nametrc" => clienttranslate("Abandoned Alien Uplift Camp"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 2,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
            3 => array(
              array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -2,
                    "worldtype" => array( 3)
               )
             ),
              array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2,
                    "worldtype" => array( 3)
               )
             )


           ),

      ),
      "category" => array(  "alien","uplift"),
      "text" => "",
      "power_to_active" => false
   ),
    163 => array(
      "name" => "Trading Outpost",
      "nametr" => self::_("Trading Outpost"),
      "nametrc" => clienttranslate("Trading Outpost"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            1 => array( array(
                "power" => "exploremix",
                "icon" => '',
                "text" => "",
                "arg" => array(
               ),
            )),
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1,2,3,4),
                    "card" => 2
            )))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    164 => array(
      "name" => "Mercenary Fleet",
      "nametr" => self::_("Mercenary Fleet"),
      "nametrc" => clienttranslate("Mercenary Fleet"),
      "qt" => 2,
      "type" => "development",
      "cost" => 3,
      "vp" => 1,
      "powers" => array(
            3 => array(
                array(
                 "power" => "militaryforce",
                 "icon" => '',
                 "text" => "",
                 "arg" => array(
                     "force" => 2
                )
               ),

               array(
                "power" => "militaryforcetmp_discard",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "repeat" => 2
               )
            ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => true
   ),
    165 => array(
      "name" => "Imperium Blaster Gem Consortium",
      "nametr" => self::_("+Imperium+ Blaster Gem Consortium"),
      "nametrc" => clienttranslate("Imperium Blaster Gem Consortium"),
      "qt" => 1,
      "type" => "world",
      "cost" => 6,
      "vp" => 4,
      "powers" => array(
            3 => array(
                array(
                 "power" => "militaryforce",
                 "icon" => '',
                 "text" => "",
                 "arg" => array(
                     "force" => 1
                )
               )
           ),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 2),
                    "output" => array( 'vp' => 1,'card'=>2),
                    "repeat" => 1
               )
            )),
            5 => array(  array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 2
                 )
               ))


      ),
      "category" => array(  "imperium","prestige"),
      "text" => "",
      "power_to_active" => false
   ),
    166 => array(
      "name" => "Hidden Fortress",
      "nametr" => self::_("Hidden Fortress"),
      "nametrc" => clienttranslate("Hidden Fortress"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce_permilitary",
                "icon" => '',
                "text" => "",
                "arg" => array(
               )
            ))

      ),
      "category" => array(  "military"),
      "text" => "",
      "power_to_active" => false
   ),
    167 => array(
      "name" => "R&D Crash Program",
      "nametr" => self::_("R&D Crash Program"),
      "nametrc" => clienttranslate("R&D Crash Program"),
      "qt" => 2,
      "type" => "development",
      "cost" => 1,
      "vp" => 0,
      "powers" => array(
            2 => array( array(
                "power" => "devcost_ondiscard",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -3
               )
            )),
            4 => array( array(
                "power" => "consumecard",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "output" => array( 'card' => 1),
                    "repeat" => 1
               )
            ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => true
   ),




    /*********************************************************************************************************************/
    /*********************************************************************************************************************/
    /*********************************************************************************************************************/
    /*********************************************************************************************************************/
    /*********** EXTENSION 3 *******************/


    181 => array(
      "name" => "Galactic Scavengers",
      "nametr" => self::_("Galactic Scavengers"),
      "nametrc" => clienttranslate("Galactic Scavengers"),
      "startworld_number" => 12,
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 0,
      "powers" => array(
            2 => array( array(
                "power" => "scavengerdev",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1
               )
            )),
            3 => array( array(
                "power" => "scavengersettle",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1
               )
            )),
            5 => array( array(
                "power" => "scavengerproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
               )
            ))
      ),
      "category" => array( "windfall" ),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),

    182 => array(
      "name" => "Uplift Mercenary Force",
      "nametr" => self::_("*Uplift* Mercenary Force"),
      "nametrc" => clienttranslate("Uplift Mercenary Force"),
      "startworld_number" => 13,
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 0,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
            3 => array( array(
                    "power" => "militaryforce_perchromosome",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                   )
                  ),
                   array(
                    "power" => "militaryforcetmp_discard",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => 1,
                        "repeat" => 1
                   )
               )


           )

      ),
      "category" => array( "uplift", "chromosome" ),
      "text" => "",
      "power_to_active" => false
   ),

    183 => array(
      "name" => "Alien Research Team",
      "nametr" => self::_("£Alien£ Research Team"),
      "nametrc" => clienttranslate("Alien Research Team"),
      "startworld_number" => 14,
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 2
               ),
            )),

            3 => array(
              array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1,
                    "worldtype" => array( 4)
               )
             ),
                array(
                    "power" => "diplomat",
                    "discount" => 0,
                    "nootherdiscount" => true,
                    "alien" => true,
                    "icon" => '',
                    "text" => "",
                    "arg" => array()
               ),
           ),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 4),
                    "output" => array( 'pr' => 1),
                    "repeat" => 1
                  )
              )
           )

      ),
      "category" => array( "alien" ),
      "text" => "",
      "power_to_active" => false
   ),

    184 => array( // DONE
      "name" => "Rebel Freedom Fighters",
      "nametr" => self::_("!Rebel! Freedom Fighters"),
      "nametrc" => clienttranslate("Rebel Freedom Fighters"),
      "startworld_number" => 15,
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 1,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
            2 => array( array(
                "power" => "drawifdev",
                "rebel" => true,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "pr" => 1
               )
            )),
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
               )
              ),
               array(
                    "power" => "militaryforce",
                    "condition" => "imperium",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => -2
                   )
               ),
                array(
                    "power" => "drawifsettle",
                    "military" => true,
                    "rebel" => true,
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "pr" => 1
                   )
                )
           )

      ),
      "category" => array(  "rebel" ,"military"),
      "text" => "",
      "power_to_active" => false
   ),

    185 => array(
      "name" => "Pan-Galactic Security Council",
      "nametr" => self::_("Pan-Galactic Security Council"),
      "nametrc" => clienttranslate("Pan-Galactic Security Council"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
            3 => array( array(
                "power" => "blocktakeover",
                "icon" => '',
                "text" => "",
                "arg" => array(
               )
              )
           ),
            4 => array(
                   array(
                    "power" => "consumecard",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "output" => array( 'pr' => 1),
                        "repeat" => 1,
                        'inputfactor' => 2
                   )
               )
          )

      ),
      "category" => array(  "prestige"),
      "text" => "",
      "power_to_active" => false
   ),

    186 => array(
      "name" => "Alien Booby Trap",
      "nametr" => self::_("£Alien£ Booby Trap"),
      "nametrc" => clienttranslate("Alien Booby Trap"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(

        3 => array(
               array(
                "power" => "militaryforcetmp_prestige",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 3,
                    "repeat" => 1
               )
          )
       ),

        5 => array( array(
                  "power" => "windfallproduceifdiscard",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "world_type" => 4
                 )
               ))

        ),
      "category" => array(  "military", "prestige","alien"),
      "text" => "",
      "power_to_active" => false
   ),

    187 => array(
      "name" => "Federation Capital",
      "nametr" => self::_("Federation Capital"),
      "nametrc" => clienttranslate("Federation Capital"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 0,
      "powers" => array(
            2 => array( array(
                "power" => "drawifdev",
                'onlyif_six_dev' => true,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "pr" => 1
               )
            )),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'pr' => 1),
                    "repeat" => 1
               )
            ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),

    // ok
    188 => array(
      "name" => "Imperium Capital",
      "nametr" => self::_("+Imperium+ Capital"),
      "nametrc" => clienttranslate("Imperium Capital"),
      "qt" => 1,
      "type" => "world",
      "cost" => 6,
      "vp" => 6,
      "powers" => array(

            3 => array(
                array(
                    "power" => "drawifsettle",
                    "military" => true,
                    "rebel" => true,
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "pr" => 1
                   )
                )
           ),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'pr' => 2),
                    "inputfactor" => 2,
                    "repeat" => 1
               )
            ))

      ),
      "category" => array( "imperium", "prestige" ),
      "text" => "",
      "power_to_active" => false
   ),

    189 => array(
      "name" => "Rebel Council",
      "nametr" => self::_("!Rebel! Council"),
      "nametrc" => clienttranslate("Rebel Council"),
      "qt" => 1,
      "type" => "world",
      "cost" => 8,
      "vp" => 8,
      "powers" => array(
            2 => array( array(
                "power" => "drawifdev",
                "rebel" => true,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "pr" => 1
               )
            )),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'vp' => 1)
               )
            ))

      ),
      "category" => array( "rebel","military","prestige" ),
      "text" => "",
      "power_to_active" => false
   ),

    190 => array(
      "name" => "Rebel Troops",
      "nametr" => self::_("!Rebel! Troops"),
      "nametrc" => clienttranslate("Rebel Troops"),
      "qt" => 2,
      "type" => "development",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(

            3 => array(
                array(
                 "power" => "militaryforce",
                 "icon" => '',
                 "text" => "",
                 "arg" => array(
                     "force" => 1
                )
               ),

               array(
                "power" => "militaryforcetmp_discard",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "repeat" => 1
               )
            )),

            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'card' => 2)
               )
            ))


      ),
      "category" => array( "rebel"  ),
      "text" => "",
      "power_to_active" => false
   ),

    191 => array(
      "name" => "Rebel Sneak Attack",
      "nametr" => self::_("!Rebel! Sneak Attack"),
      "nametrc" => clienttranslate("Rebel Sneak Attack"),
      "qt" => 2,
      "type" => "development",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            1 => array( array(
                "power" => "exploremix",
                "icon" => '',
                "text" => "",
                "arg" => array(
               ),
            )),
            3 => array(

                array(
                    "power" => "militaryaftersettle",
                    "icon" => '',
                    "text" => "",
                    "arg" => array()
               )    ,
                array(
                    "power" => "discardtotakeover",
                    "targetfilter" => "imperium",
                    "bonus" => "two_per_rebel",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                   )
               )

           )

      ),
      "category" => array( "rebel" ),
      "text" => "",
      "power_to_active" => false
   ),

    192 => array(
      "name" => "Universal Peace Institute",
      "nametr" => self::_("Universal Peace Institute"),
      "nametrc" => clienttranslate("Universal Peace Institute"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            3 => array(
                array(
                    "power" => "settlecost",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "cost" => -2,
                        "worldtype" => array( 1,2,3,4)
                   )
               ),
                array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => -2
                   )
               )

           ),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "inputfactor" => 2,
                    "output" => array( 'vp' => 1, 'card' => 1, 'pr' => 1),
                    "repeat" => 1
               )
            )),


      ),
      "category" => array( "prestige" ),
      "text" => "",
      "power_to_active" => false
   ),


    193 => array(
      "name" => "Golden Age of Terraforming",
      "nametr" => self::_("Golden Age of €Terraforming€"),
      "nametrc" => clienttranslate("Golden Age of Terraforming"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            2 => array( array(
                "power" => "good_for_devcost",
                "good" => 2,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -2
               )
            )),
            3 => array(
                array(
                "power" => "good_for_settlecost",
                "good" => 3,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -3
                )
              ),
                array(
                "power" => "production_goodonsettle",
                "good" => 3,
                "icon" => '',
                "text" => "",
                "arg" => array(
                )
              )

           )

      ),
      "category" => array( "terraforming","prestige" ),
      "text" => "",
      "power_to_active" => false
   ),

    194 => array(
      "name" => "Imperium Invasion Fleet",
      "nametr" => self::_("+Imperium+ Invasion Fleet"),
      "nametrc" => clienttranslate("Imperium Invasion Fleet"),
      "qt" => 2,
      "type" => "development",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(
            3 => array(
                array(
                 "power" => "militaryforce",
                 "icon" => '',
                 "text" => "",
                 "arg" => array(
                     "force" => 3
                )
               ),
                array(
                 "power" => "militaryforce",
                 "icon" => '',
                 "text" => "",
                 "arg" => array(
                     "force" => 1,
                     "worldfilter" => 'rebel'
                )
               ),
                array(
                    "power" => "cloaking",
                    "discount" => 0,
                    "takeover" => true,
                    "gainprestige" => 2,
                    "icon" => '',
                    "text" => "",
                    "arg" => array()
               ),

               ),


      ),
      "category" => array( "prestige", "imperium" ),
      "text" => "",
      "power_to_active" => false
   ),

    // ok
    195 => array(
      "name" => "Imperium Planet Buster",
      "nametr" => self::_("+Imperium+ Planet Buster"),
      "nametrc" => clienttranslate("Imperium Planet Buster"),
      "qt" => 1,
      "type" => "development",
      "cost" => 9,
      "vp" => 9,
      "powers" => array(

            3 => array(
                array(
                 "power" => "militaryforce",
                 "icon" => '',
                 "text" => "",
                 "arg" => array(
                     "force" => 3
                )
               ),

                array(
                    "power" => "takeover",
                    "targetfilter" => "militaryforce",
                    "destroy" => true,
                    "gainprestige" => 2,
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                   )
               )
          )

      ),
      "category" => array("imperium","prestige"  ),
      "text" => "",
      "power_to_active" => false
   ),

    196 => array(
      "name" => "Galactic Markets",
      "nametr" => self::_("Galactic Markets"),
      "nametrc" => clienttranslate("Galactic Markets"),
      "qt" => 2,
      "type" => "development",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
             3 => array( array(
                "power" => "drawifsettle",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
             )),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'vp' => 1),
                    "repeat" => 3
               )
            )),
            5 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
            ))


      ),
      "category" => array( "prestige" ),
      "text" => "",
      "power_to_active" => false
   ),

    197 => array(
      "name" => "Pan-Galactic Hologrid",
      "nametr" => self::_("Pan-Galactic Hologrid"),
      "nametrc" => clienttranslate("Pan-Galactic Hologrid"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            1 => array(
                   array(
                    "power" => "consumecard",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "output" => array( 'pr' => 1),
                        "repeat" => 1,
                   )
               )
            ),
             3 => array( array(
                "power" => "drawifsettle",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
             )),
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1),
                    "card" => 2
                )
            )),


      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),

    // ok
    198 => array(
      "name" => "Interstellar Casus Belli",
      "nametr" => self::_("Interstellar Casus Belli"),
      "nametrc" => clienttranslate("Interstellar Casus Belli"),
      "qt" => 2,
      "type" => "development",
      "cost" => 4,
      "vp" => 0,
      "powers" => array(
            3 => array(
                    array(
                        "power" => "prestigetotakeover",
                        "gainprestige" => 2,
                        "icon" => '',
                        "text" => "",
                        "arg" => array(
                       )
                   )
           ),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 'pr'),
                    "output" => array( 'vp' => 3),
                    "repeat" => 1
               )
            ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),

    199 => array(
      "name" => "Pan-Galactic Affluence",
      "nametr" => self::_("Pan-Galactic Affluence"),
      "nametrc" => clienttranslate("Pan-Galactic Affluence"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            2 => array( array(
                "power" => "drawifdev",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "pr" => 1
               )
            )),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    'inputfactor' => 2,
                    "output" => array( 'vp' => 1, 'pr' => 1),
                    "repeat" => 1
               )
            )),
            5 => array( array(
                "power" => "bonusifbiggestprod",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
            ))


      ),
      "category" => array( "prestige" ),
      "text" => "",
      "power_to_active" => false
   ),

    200 => array(
      "name" => "Galactic Power Brokers",
      "nametr" => self::_("Galactic Power Brokers"),
      "nametrc" => clienttranslate("Galactic Power Brokers"),
      "qt" => 2,
      "type" => "development",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(

            2 => array(
                array(
                    "power" => "draw",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "card" => 2,
                        "thendiscard" => 1
                   )
               ),
           ),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 'pr'),
                    'inputfactor' => 1,
                    "output" => array( 'card' => 3),
                    "repeat" => 1
               )
            )),


      ),
      "category" => array( "prestige" ),
      "text" => "",
      "power_to_active" => false
   ),

    201 => array(
      "name" => "Alien Cornucopia",
      "nametr" => self::_("£Alien£ Cornucopia"),
      "nametrc" => clienttranslate("Alien Cornucopia"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(

             3 => array( array(
                "power" => "drawifsettle",
                "production" => true,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "pr" => 1
               )
             )),

            5 => array(
                array(
                    "power" => "draw",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "card" => 1
                   )
               ),
           ),

      ),
      "category" => array(  "alien"),
      "text" => "",
      "power_to_active" => false
   ),

    // ok
    202 => array(
      "name" => "Pan-Galactic Mediator",
      "nametr" => self::_("Pan-Galactic Mediator"),
      "nametrc" => clienttranslate("Pan-Galactic Mediator"),
      "qt" => 2,
      "type" => "development",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => -1
               )
            ),
            array(
                "power" => "diplomatbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "pr" => 1
               )

            )

           )

      ),
      "category" => array(  "prestige"),
      "text" => "",
      "power_to_active" => false
   ),

    203 => array(
      "name" => "Ravaged Uplift World",
      "nametr" => self::_("Ravaged *Uplift* World"),
      "nametrc" => clienttranslate("Ravaged Uplift World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => -1,
      "powers" => array(
              3 => array(
                array(
                    "power" => "diplomat",
                    "discount" => 0,
                    "fullcost"=> true,
                    "chromosome" => true,
                    "icon" => '',
                    "text" => "",
                    "arg" => array()
               ),

            ),

            5 => array( array(
                "power" => "bonusifmost",
                "cardfilter" => "chromosome",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    'pr' => 1
               )
            ))

      ),
      "category" => array(  "uplift", "chromosome", "windfall"),
      "windfalltype"=>3,
      "text" => "",
      "power_to_active" => false
   ),

    204 => array(
      "name" => "Lifeforms, inc.",
      "nametr" => self::_("Lifeforms, inc."),
      "nametrc" => clienttranslate("Lifeforms, inc."),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 1,
      "powers" => array(
            3 => array(
                array(
                "power" => "good_for_settlecost",
                "good" => 3,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -3
                )
              )
           ),
            5 => array(
             array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 3
             )
            ),
             array(
              "power" => "windfallproduceifdiscard",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "world_type" => 3
             )
            )

           )



      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),

    205 => array(
      "name" => "Terraforming Engineers",
      "nametr" => self::_("€Terraforming€ Engineers"),
      "nametrc" => clienttranslate("Terraforming Engineers"),
      "qt" => 2,
      "type" => "development",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
            3 => array(
                array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1,
                    "worldtype" => array( 1,2,3,4)
                )
               ),
                array(
                "power" => "settlereplace",
                "icon" => '',
                "text" => "",
                "arg" => array(
                )
               )

           ),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'vp' => 1),
                    "repeat" => 1
               )
            )),

      ),
      "category" => array( "terraforming" ),
      "text" => "",
      "power_to_active" => false
   ),

    206 => array(
      "name" => "Alien Burial Site",
      "nametr" => self::_("£Alien£ Burial Site"),
      "nametrc" => clienttranslate("Alien Burial Site"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(

            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1),
                    "card" => 1
                )
            )),
            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 1
             )
            ))

      ),
      "category" => array( "alien","prestige" ),
      "text" => "",
      "power_to_active" => false
   ),


    207 => array(
      "name" => "Information Hub",
      "nametr" => self::_("Information Hub"),
      "nametrc" => clienttranslate("Information Hub"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
            1 => array( array(
                "power" => "exploremix",
                "icon" => '',
                "text" => "",
                "arg" => array(
               ),
            )),
            2 => array(
                array(
                    "power" => "draw",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "card" => 1,
                        "thendiscard" => 1
                   )
               ),
           ),
            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 1
             )
            ))

      ),
      "category" => array( "military", "prestige" ),
      "text" => "",
      "power_to_active" => false
   ),

    208 => array(
      "name" => "Alien Tourist Attraction",
      "nametr" => self::_("£Alien£ Tourist Attraction"),
      "nametrc" => clienttranslate("Alien Tourist Attraction"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'vp' => 1, 'card' => 2),
                    "repeat" => 1
               )
            )),


      ),
      "category" => array(  "alien","prestige","windfall"),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),

    209 => array(
      "name" => "Mining Mole Uplift Race",
      "nametr" => self::_("Mining Mole *Uplift* Race"),
      "nametrc" => clienttranslate("Mining Mole Uplift Race"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
            3 => array( array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1,
                    "worldtype" => array( 2)
                )
            )),
            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 2
             )
            ))


      ),
      "category" => array( "uplift", "chromosome" ),
      "text" => "",
      "power_to_active" => false
   ),

    210 => array(
      "name" => "Imperium Fuel Depot",
      "nametr" => self::_("+Imperium+ Fuel Depot"),
      "nametrc" => clienttranslate("Imperium Fuel Depot"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 1,
      "powers" => array(
             3 => array( array(
                "power" => "drawifsettle",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 2,
                    'thendiscard' => 1
               )
             )),
            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 2
             )
            ))

      ),
      "category" => array(  "imperium","military"),
      "text" => "",
      "power_to_active" => false
   ),

    211 => array(
      "name" => "Psi-Crystal World",
      "nametr" => self::_("Psi-Crystal World"),
      "nametrc" => clienttranslate("Psi-Crystal World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
             3 => array(
                array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => -1
                   )
               )
            )

      ),
      "category" => array(  "prestige" , "windfall"),
      "windfalltype" => 2,
      "text" => "",
      "power_to_active" => false
   ),

    212 => array(
      "name" => "Rebel Fuel Refinery",
      "nametr" => self::_("!Rebel! Fuel Refinery"),
      "nametrc" => clienttranslate("Rebel Fuel Refinery"),
      "qt" => 1,
      "type" => "world",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
            3 => array(
               array(
                "power" => "good_for_military",
                "good" => 2,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2,
                    "repeat" => 1
               )
            ))

      ),
      "category" => array( "prestige","military","windfall" ,"rebel"),
      "windfalltype" => 2,
      "text" => "",
      "power_to_active" => false
   ),

    213 => array(
      "name" => "Uplift Gene Breeders",
      "nametr" => self::_("*Uplift* Gene Breeders"),
      "nametrc" => clienttranslate("Uplift Gene Breeders"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 0,
      "powers" => array(
                5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 3,
                      'pr' => 1
                 )
                ))

      ),
      "category" => array( "uplift"),
      "text" => "",
      "power_to_active" => false
   ),

    214 => array(
      "name" => "Uplift Revolt World",
      "nametr" => self::_("*Uplift* Revolt World"),
      "nametrc" => clienttranslate("Uplift Revolt World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
            3 => array( array(
                    "power" => "militaryforce_perchromosome",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                   )
                  )),

      ),
      "category" => array( "military", "windfall", "uplift", "chromosome" ),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),

    215 => array(
      "name" => "Alien Departure Point",
      "nametr" => self::_("£Alien£ Departure Point"),
      "nametrc" => clienttranslate("Alien Departure Point"),
      "qt" => 1,
      "type" => "world",
      "cost" => 9,
      "vp" => 9,
      "powers" => array(
            1 => array(
                   array(
                    "power" => "consumecard",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "output" => array( 'pr' => 1),
                        "repeat" => 1,
                   )
               )
            ),
                5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 4
                 )
                ))

      ),
      "category" => array( "alien","prestige" ),
      "text" => "",
      "power_to_active" => false
   ),

    216 => array(
      "name" => "Alien Guardian",
      "nametr" => self::_("£Alien£ Guardian"),
      "nametrc" => clienttranslate("Alien Guardian"),
      "qt" => 1,
      "type" => "world",
      "cost" => 9,
      "vp" => 9,
      "powers" => array(
            4 => array(
                   array(
                    "power" => "consumecard",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "output" => array( 'pr' => 1),
                        "repeat" => 1,
                        'inputfactor' => 2
                   )
               )
          )

      ),
      "category" => array( "alien","prestige","military", "windfall" ),
      "windfalltype" => 4,
      "text" => "",
      "power_to_active" => false
   ),

    217 => array(
      "name" => "Black Hole Miners",
      "nametr" => self::_("Black Hole Miners"),
      "nametrc" => clienttranslate("Black Hole Miners"),
      "qt" => 1,
      "type" => "world",
      "cost" => 4,
      "vp" => 0,
      "powers" => array(
            5 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 3
               )
            ))

      ),
      "category" => array( "prestige" ),
      "text" => "",
      "power_to_active" => false
   ),

    218 => array(
      "name" => "Retrofit & Salvage, inc.",
      "nametr" => self::_("Retrofit & Salvage, inc."),
      "nametrc" => clienttranslate("Retrofit & Salvage, inc."),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            2 => array( array(
                "power" => "devcost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1
               )
            )),
            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 1
             )
            ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),

    219 => array(
      "name" => "Universal Exports",
      "nametr" => self::_("Universal Exports"),
      "nametrc" => clienttranslate("Universal Exports"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
            1 => array( array(
                "power" => "exploremix",
                "icon" => '',
                "text" => "",
                "arg" => array(
               ),
            )),
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1,2,3,4),
                    "card" => 1
                )
            )),
            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 1
             )
             ),
              array(
                  "power" => "windfallproduceifdiscard",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                 )
               )
           )

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),

    220 => array(
      "name" => "Alien Oort Cloud Refinery",
      "nametr" => self::_("£Alien£ Oort Cloud Refinery"),
      "nametrc" => clienttranslate("Alien Oort Cloud Refinery"),
      "qt" => 1,
      "type" => "world",
      "cost" => 0,
      "vp" => 1,
      "powers" => array(
            's' => array( array(
                "power" => "cannotsell",
                "icon" => '',
                "text" => "",
                "arg" => array(
                )
            )),

      ),
      "category" => array(  "windfall", "alien"),
      "windfalltype" => 'choice',
      "text" => "",
      "power_to_active" => false
   ),




    /*********************************************************************************************************************/
    /*********************************************************************************************************************/
    /*********************************************************************************************************************/
    /*********************************************************************************************************************/
    /*********** EXTENSION 4 - ALIEN ARTIFACTS *******************/

    230 => array(
      "startworld_number" => 9,
      "name" => "Frontier Capital",
      "nametr" => self::_("Frontier Capital"),
      "nametrc" => clienttranslate("Frontier Capital"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(

            2 => array( array(
                "power" => "drawifdev",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
            )),
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
               )
            ))
      ),
      "category" => array(  'military'),
      "text" => "",
      "power_to_active" => false
   ),
    231 => array(
      "startworld_number" => 8,
      "name" => "Uplift Researchers",
      "nametr" => self::_("*Uplift* Researchers"),
      "nametrc" => clienttranslate("Uplift Researchers"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),

            3 => array(
                array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1,
                    "worldtype" => array( 1,2,3,4)
                )
               ),
                array(
                    "power" => "diplomat",
                    "discount" => 0,
                    "chromosome" => true,
                    "icon" => '',
                    "text" => "",
                    "arg" => array()
               ),

           ),

      ),
      "category" => array(  'uplift'),
      "text" => "",
      "power_to_active" => false
   ),
    232 => array(
      "startworld_number" => 7,
      "name" => "Rebel Mutineers",
      "nametr" => self::_("!Rebel! Mutineers"),
      "nametrc" => clienttranslate("Rebel Mutineers"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 0,
      "powers" => array(

            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),

            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
               )
            ))



      ),
      "category" => array(  "military","rebel","windfall"),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),
    233 => array(
      "startworld_number" => 6,
      "name" => "Alien Artifact Hunters",
      "nametr" => self::_("£Alien£ Artifact Hunters"),
      "nametrc" => clienttranslate("Alien Artifact Hunters"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 1,
      "powers" => array(
              1 => array(
                array(
                    "power" => "exploredraw",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "card" => 1
                   )
               ),
                array(
                    "power" => "explorekeep",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "card" => 1
                   )
               )
            ),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 4),
                    "output" => array( 'vp' => 2),
                    "repeat" => 1
               )
            ))


      ),
      "category" => array( "alien" ),
      "text" => "",
      "power_to_active" => false
   ),
    234 => array(
      "startworld_number" => 5,
      "name" => "Sentient Robots",
      "nametr" => self::_("Sentient Robots"),
      "nametrc" => clienttranslate("Sentient Robots"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(

            3 => array(
                array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1,
                    "worldtype" => array( 1,2,3,4)
                )
               ),
                 array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
                   )
               )

           ),

             5 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
             ))


      ),
      "category" => array( "military" ),
      "text" => "",
      "power_to_active" => false
   ),
    235 => array(
      "name" => "Deep Space Symbionts, LTD.",
      "nametr" => self::_("Deep Space Symbionts, LTD."),
      "nametrc" => clienttranslate("Deep Space Symbionts, LTD."),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 1,
      "powers" => array(

            2 => array( array(
                "power" => "devcost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1
               )
            ))


      ),
      "category" => array( "windfall" ),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    236 => array(
      "name" => "Rebel Gem Smugglers",
      "nametr" => self::_("!Rebel! Gem Smugglers"),
      "nametrc" => clienttranslate("Rebel Gem Smugglers"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 0,
      "powers" => array(

            3 => array(
               array(
                "power" => "good_for_military",
                "good" => 2,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2,
                    "repeat" => 1
               )
            )),


           5 => array( array(
                  "power" => "drawifproduce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "card" => 1
                 )
               ))


      ),
      "category" => array( "windfall","rebel","military" ),
      "windfalltype" => 2,
      "text" => "",
      "power_to_active" => false
   ),
    237 => array(
      "name" => "Self-Repairing Alien Artillery",
      "nametr" => self::_("Self-Repairing £Alien£ Artillery"),
      "nametrc" => clienttranslate("Self-Repairing Alien Artillery"),
      "qt" => 1,
      "type" => "world",
      "cost" => 4,
      "vp" => 1,
      "powers" => array(

            3 => array(
               array(
                "power" => "good_for_military",
                "good" => 4,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 3,
                    "repeat" => 1
               )
            )),


            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 4
             )
            ))


      ),
      "category" => array( "alien","military" ),
      "text" => "",
      "power_to_active" => false
   ),
    238 => array(
      "name" => "Imperium Stealth Tactics",
      "nametr" => self::_("+Imperium+ Stealth Tactics"),
      "nametrc" => clienttranslate("Imperium Stealth Tactics"),
      "qt" => 2,
      "type" => "development",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(

            3 => array( array(
                "power" => "militaryforcetmp",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 3
               )
            ),
             array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
                   )
               )

            )


      ),
      "category" => array(  "imperium"),
      "text" => "",
      "power_to_active" => false
   ),
    239 => array(
      "name" => "Amphibian Uplift Race",
      "nametr" => self::_("Amphibian *Uplift* Race"),
      "nametrc" => clienttranslate("Amphibian Uplift Race"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 2,
      "powers" => array(

      ),
      "category" => array( "military","uplift", "chromosome","windfall" ),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    240 => array(
      "name" => "Arboreal Uplift Race",
      "nametr" => self::_("Arboreal *Uplift* Race"),
      "nametrc" => clienttranslate("Arboreal Uplift Race"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 2,
      "powers" => array(

      ),
      "category" => array( "uplift", "chromosome","military","windfall" ),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    241 => array(
      "name" => "Designer Species, ULTD.",
      "nametr" => self::_("Designer Species, ULTD."),
      "nametrc" => clienttranslate("Designer Species, ULTD."),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(

           4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 3),
                    "inputfactor" => 2,
                    "output" => array( 'vp' => 3),
                    "repeat" => 1
                )
            )),

            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 3
             )
            ))


      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),

    242 => array(
      "name" => "Tranship Point",
      "nametr" => self::_("Tranship Point"),
      "nametrc" => clienttranslate("Tranship Point"),
      "qt" => 1,
      "type" => "world",
      "cost" => 0,
      "vp" => 2,
      "powers" => array(

            3 => array( array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1,
                    "worldtype" => array( 2)
                )
            )),

            5 => array( array(
              "power" => "storeresources",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 2
             )
            ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    243 => array(
      "name" => "Imperium Blaster Gem Depot",
      "nametr" => self::_("+Imperium+ Blaster Gem Depot"),
      "nametrc" => clienttranslate("Imperium Blaster Gem Depot"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(

            3 => array(
             array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2
                   )
               )

            ),
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 2),
                    "card" => 1
                )
            )),


      ),
      "category" => array( "military","windfall","imperium" ),
      "windfalltype" => 2,
      "text" => "",
      "power_to_active" => false
   ),
    244 => array(
      "name" => "Ore-Rich World",
      "nametr" => self::_("Ore-Rich World"),
      "nametrc" => clienttranslate("Ore-Rich World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(

            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 2
             )
            ),
            array(
              "power" => "discardtoputgood",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 2
             )
            )

           )


      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    245 => array(
      "name" => "Jumpdrive Fuel Refinery",
      "nametr" => self::_("Jumpdrive Fuel Refinery"),
      "nametrc" => clienttranslate("Jumpdrive Fuel Refinery"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(

             3 => array( array(
                "power" => "drawifsettle",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
             )),

            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 2
             )
            ))


      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    246 => array(
      "name" => "Alien Fuel Refinery",
      "nametr" => self::_("£Alien£ Fuel Refinery"),
      "nametrc" => clienttranslate("Alien Fuel Refinery"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 2,
      "powers" => array(

            2 => array( array(
                "power" => "devcost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1
               )
            )),

            's' => array( array(
                "power" => "cannotsell",
                "icon" => '',
                "text" => "",
                "arg" => array(
                )
            )),


            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 4
             )
            ))

      ),
      "category" => array(  "alien"),
      "text" => "",
      "power_to_active" => false
   ),
    247 => array(
      "name" => "Alien Uplift Chamber",
      "nametr" => self::_("£Alien£ *Uplift* Chamber"),
      "nametrc" => clienttranslate("Alien Uplift Chamber"),
      "qt" => 1,
      "type" => "world",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(

            3 => array(
                array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => 2,
                        "worldtype" => array( 3)
                   )
               ),
                array(
                    "power" => "settlecost",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "cost" => -2,
                        "worldtype" => array( 3)
                   )
               )
          ),

           5 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
           ))


      ),
      "category" => array(  "alien","uplift","windfall"),
      "windfalltype" => 4,
      "text" => "",
      "power_to_active" => false
   ),
    248 => array(
      "name" => "Alien Sentinels",
      "nametr" => self::_("£Alien£ Sentinels"),
      "nametrc" => clienttranslate("Alien Sentinels"),
      "qt" => 1,
      "type" => "world",
      "cost" => 9,
      "vp" => 9,
      "powers" => array(

      ),
      "category" => array( "military","alien","windfall" ),
      "windfalltype" => 4,
      "text" => "",
      "power_to_active" => false
   ),
    249 => array(
      "name" => "Galactic Survey Headquarters",
      "nametr" => self::_("Galactic Survey Headquarters"),
      "nametrc" => clienttranslate("Galactic Survey Headquarters"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(

            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 2
               ),
            )),

             's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1),
                    "card" => 1,
                    "fromthisworld" => true
               )
             )),

            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 1
             )
            ))


      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),

    250 => array(
      "name" => "Interstellar Trade Port",
      "nametr" => self::_("Interstellar Trade Port"),
      "nametrc" => clienttranslate("Interstellar Trade Port"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(

            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'vp' => 1,'card'=>1),
                    "repeat" => 4,
                    "different" => true
               )
            )),


            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 1
             )
            ))


      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    251 => array(
      "name" => "Galactic News Hub",
      "nametr" => self::_("Galactic News Hub"),
      "nametrc" => clienttranslate("Galactic News Hub"),
      "qt" => 1,
      "type" => "world",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(

           4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1),
                    "inputfactor" => 2,
                    "output" => array( 'vp' => 3),
                    "repeat" => 1
                )
            )),

            5 => array( array(
                "power" => "bonusifbiggestprod",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => 1,
                    "card" => 1
               )
            ))


      ),
      "category" => array( "windfall" ),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),
    252 => array(
      "name" => "Mercenary Guild",
      "nametr" => self::_("Mercenary Guild"),
      "nametrc" => clienttranslate("Mercenary Guild"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
            3 => array(
                array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => 2
                   )
               ),
           )
      ),
      "category" => array( "windfall","military" ),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),
    253 => array(
      "name" => "Terraforming Colony",
      "nametr" => self::_("€Terraforming€ Colony"),
      "nametrc" => clienttranslate("Terraforming Colony"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 2,
      "powers" => array(

           4 => array(

           array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'vp' => 1, 'card' => 2),
                    "repeat" => 1
                )
            ),

           array(
                "power" => "consumecard",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "output" => array( 'vp' => 1),
                    "repeat" => 1
               )
           ),



         ),


      ),
      "category" => array( "windfall", 'terraforming'),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),
    254 => array(
      "name" => "Rebel Uplift World",
      "nametr" => self::_("!Rebel! *Uplift* World"),
      "nametrc" => clienttranslate("Rebel Uplift World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            3 => array( array(
                    "power" => "militaryforce_perchromosome",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                   )
                  ),
           )
      ),
      "category" => array( "rebel","military","uplift", "chromosome" ),
      "text" => "",
      "power_to_active" => false
   ),
    255 => array(
      "name" => "Imperium Fifth Column",
      "nametr" => self::_("+Imperium+ Fifth Column"),
      "nametrc" => clienttranslate("Imperium Fifth Column"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 0,
      "powers" => array(

            3 => array(
                array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => 1
                   )
               ),
           ),

            5 => array( array(
                      "power" => "drawforimperium",
                      "icon" => '',
                      "text" => "",
                      "arg" => array(
                     )
                   ))



      ),
      "category" => array( "military","imperium" ),
      "text" => "",
      "power_to_active" => false
   ),
    256 => array(
      "name" => "Alien Survey Technology",
      "nametr" => self::_("£Alien£ Survey Technology"),
      "nametrc" => clienttranslate("Alien Survey Technology"),
      "qt" => 2,
      "type" => "development",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(

            1 => array( array(
                "power" => "orb_movement",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "bonus" => 2
               )
             )),
            3 => array(
                array(
                    "power" => "diplomat",
                    "alien" => true,
                    "icon" => '',
                    "text" => "",
                    "arg" => array()
               ),
           )


      ),
      "category" => array( "alien" ),
      "text" => "",
      "power_to_active" => false
   ),
    257 => array(
      "name" => "Alien Research Ship",
      "nametr" => self::_("£Alien£ Research Ship"),
      "nametrc" => clienttranslate("Alien Research Ship"),
      "qt" => 2,
      "type" => "development",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(

            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),

            3 => array(
              array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "worldtype" => array( 4)
               )
             ),
              array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1,
                    "worldtype" => array( 4)
               )
             )
           ),
            5 => array( array(
                "power" => "windfallproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => array( 4),
                    "worldfilter" => array( 'windfall')
                )
            ))



      ),
      "category" => array("alien"  ),
      "text" => "",
      "power_to_active" => false
   ),
    258 => array(
      "name" => "Imperium Supply Convoy",
      "nametr" => self::_("+Imperium+ Supply Convoy"),
      "nametrc" => clienttranslate("Imperium Supply Convoy"),
      "qt" => 2,
      "type" => "development",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(

            3 => array(
              array(
                "power" => "additional_military",
                "icon" => '',
                "text" => "",
                "arg" => array(
               )
             )
           ),
           5 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
           ))



      ),
      "category" => array( "imperium" ),
      "text" => "",
      "power_to_active" => false
   ),
    259 => array(
      "name" => "Terraforming project",
      "nametr" => self::_("€Terraforming€ project"),
      "nametrc" => clienttranslate("Terraforming project"),
      "qt" => 2,
      "type" => "development",
      "cost" => 3,
      "vp" => 0,
      "powers" => array(

            3 => array( array(
                "power" => "colonyship_aftersettle",
                "icon" => '',
                "text" => "",
                "arg" => array(
               )
            )),


            5 => array( array(
                "power" => "windfallproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => array( 2),
                    "worldfilter" => array( 'windfall')
               )
            ))


      ),
      "category" => array(  "terraforming"),
      "text" => "",
      "power_to_active" => false
   ),

    260 => array(
      "name" => "Alien Researchers",
      "nametr" => self::_("£Alien£ Researchers"),
      "nametrc" => clienttranslate("Alien Researchers"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(

            3 => array(
              array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 3,
                    "worldtype" => array( 4)
               )
             ),
              array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -3,
                    "worldtype" => array( 4)
               )
             )
           ),

            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 4),
                    "output" => array( 'vp' => 2),
                    "repeat" => 1
               )
            ))


      ),
      "category" => array( "alien" ),
      "text" => "",
      "power_to_active" => false
   ),

    261 => array(
      "name" => "Terraforming Unlimited",
      "nametr" => self::_("€Terraforming€ Unlimited"),
      "nametrc" => clienttranslate("Terraforming Unlimited"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(

            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),

            3 => array( array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1,
                    "worldtype" => array( 1,2,3,4)
               )
            )),

            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 2),
                    "output" => array( 'vp' => 2),
                    "repeat" => 1
               )
            ))


      ),
      "category" => array( "terraforming" ),
      "text" => "",
      "power_to_active" => false
   ),
    262 => array(
      "name" => "Uplift Alliance",
      "nametr" => self::_("*Uplift* Alliance"),
      "nametrc" => clienttranslate("Uplift Alliance"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(

            3 => array(
                array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => 2,
                        "worldtype" => array( 3)
                   )
               ),
                array(
                    "power" => "settlecost",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "cost" => -2,
                        "worldtype" => array( 3)
                   )
               ),
           ),
            5 => array( array(
                "power" => "windfallproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => array( 3),
                    "worldfilter" => array( 'windfall')
               )
            ))



      ),
      "category" => array( "uplift" ),
      "text" => "",
      "power_to_active" => false
   ),
    263 => array(
      "name" => "Imperium War Faction",
      "nametr" => self::_("+Imperium+ War Faction"),
      "nametrc" => clienttranslate("Imperium War Faction"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(

            3 => array(
               array(
                "power" => "good_for_military",
                "good" => 2,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2,
                    "repeat" => 1
               )
            )),



            5 => array( array(
                "power" => "drawforeach",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => 2,
                    "card" => 1
               )
            ))


      ),
      "category" => array( "imperium" ),
      "text" => "",
      "power_to_active" => false
   ),
    264 => array(
      "name" => "Galactic Expansionists",
      "nametr" => self::_("Galactic Expansionists"),
      "nametrc" => clienttranslate("Galactic Expansionists"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
             2 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
             )),

             3 => array( array(
                "power" => "drawifsettle",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
             )),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'vp' => 1),
                    "repeat" => 1
               )
            ))


      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    265 => array(
      "name" => "Wormhole Prospectors",
      "nametr" => self::_("Wormhole Prospectors"),
      "nametrc" => clienttranslate("Wormhole Prospectors"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(

             3 => array( array(
                "power" => "randomsettle",
                "icon" => '',
                "text" => "",
                "arg" => array(
               )
             )),

            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1,2,3,4),
                    "card" => 2
               )
            )),

            4 => array( array(
                "power" => "consumecard",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "output" => array( 'vp' => 1),
                    "repeat" => 2
               )
            ))


      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => true
   ),
    266 => array(
      "name" => "Galactic Investors",
      "nametr" => self::_("Galactic Investors"),
      "nametrc" => clienttranslate("Galactic Investors"),
      "qt" => 2,
      "type" => "development",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(
            2 => array( array(
                "power" => "drawifdev",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 2
               )
            )),
            5 => array( array(
                      "power" => "drawfordevelopment",
                      "icon" => '',
                      "text" => "",
                      "arg" => array(
                     )
                   ))


      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    267 => array(
      "name" => "Rebel Resistance",
      "nametr" => self::_("!Rebel! Resistance (AA)"),
      "nametrc" => clienttranslate("Rebel Resistance (AA)"),
      "qt" => 1,
      "type" => "world",
      "cost" => 8,
      "vp" => 0,
      "powers" => array(

            5 => array( array(
                      "power" => "drawforrebelmilitary",
                      "icon" => '',
                      "text" => "",
                      "arg" => array(
                     )
                   ))


      ),
      "category" => array( "rebel","military" ),
      "text" => "",
      "power_to_active" => false
   ),
    268 => array(
      "name" => "Scientific Cruisers",
      "nametr" => self::_("Scientific Cruisers"),
      "nametrc" => clienttranslate("Scientific Cruisers"),
      "qt" => 2,
      "type" => "development",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(

            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
            3 => array(
                array(
                    "power" => "militaryforce",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "force" => 1
                   )
               ),
                array(
                    "power" => "settlecost",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "cost" => -1,
                        "worldtype" => array( 1,2,3,4)
                   )
               )
          ),

           4 => array( array(
                  "power" => "consume",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "input" => array( 1,2,3,4),
                      "output" => array( 'card' => 2),
                      "repeat" => 1
                 )
           )),


      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),


    /*********************************************************************************************************************/
    /*********************************************************************************************************************/
    /*********************************************************************************************************************/
    /*********************************************************************************************************************/
    /*********** EXTENSION 5 - XENO invasion  *******************/


    270 => array(
      "name" => "Anti-Xeno Defense Post",
      "nametr" => self::_("^Anti-Xeno^ Defense Post"),
      "nametrc" => clienttranslate("Anti-Xeno Defense Post"),
      "startworld_number" => 9,
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 2,
      "powers" => array(
            3 => array(
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
               )
             ),
              array(
                "power" => "militaryforcetmp_discard",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "xenodefense" => true,
                    "repeat" => 1
              )

            )

        ),
            5 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
            ))
     ),
      "category" => array( "antixeno","military" ),
      "text" => "",
      "power_to_active" => false
   ),
    271 => array(
      "name" => "Terraforming Surveyors",
      "nametr" => self::_("€Terraforming€ Surveyors"),
      "nametrc" => clienttranslate("Terraforming Surveyors"),
      "startworld_number" => 8,
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 0,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 2
               ),
            )),
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1,2,3,4),
                    "card" => 2
                )
            )),

      ),
      "category" => array( "terraforming" ),
      "text" => "",
      "power_to_active" => false
   ),
    272 => array(
      "name" => "Rebel Cadre",
      "nametr" => self::_("!Rebel! Cadre"),
      "nametrc" => clienttranslate("Rebel Cadre"),
      "startworld_number" => 7,
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 0,
      "powers" => array(
            3 => array(
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
               )
             )
           ),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'card' => 2),
                    "repeat" => 1
               )
            ))

      ),
      "category" => array( "military","rebel" ),
      "text" => "",
      "power_to_active" => false
   ),
    273 => array(
      "name" => "Starry Rift Pioneers",
      "nametr" => self::_("Starry Rift Pioneers"),
      "nametrc" => clienttranslate("Starry Rift Pioneers"),
      "startworld_number" => 6,
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 0,
      "powers" => array(
            3 => array(
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "xenodefense" => true
               )
             )
           ),
            5 => array( array(
                "power" => "windfallproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => array( 1,2,3,4),
                    "worldfilter" => array( 'windfall')
               )
            ))

      ),
      "category" => array( "windfall" ),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),
    274 => array(
      "name" => "Alien First Contact Team",
      "nametr" => self::_("£Alien£ First Contact Team"),
      "nametrc" => clienttranslate("Alien First Contact Team"),
      "startworld_number" => 5,
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
            3 => array(
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
               )
             ),
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "xeno" => true
               )
             ),
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "worldtype" => array( 4)
               )
             )
           ),

      ),
      "category" => array( "military","alien" ),
      "text" => "",
      "power_to_active" => false
   ),
    275 => array(
      "name" => "Uplift Terraforming",
      "nametr" => self::_("*Uplift* €Terraforming€"),
      "nametrc" => clienttranslate("Uplift Terraforming"),
      "qt" => 2,
      "type" => "development",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
            3 => array( array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -1,
                    "worldtype" => array( 1,2,3,4)
                )
               ),
                array(
                    "power" => "diplomat",
                    "discount" => 0,
                    "chromosome" => true,
                    "icon" => '',
                    "text" => "",
                    "arg" => array()
               ),

           ),
            5 => array( array(
                  "power" => "windfallproduceifdiscard",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "world_type" => 3
                 )
               ))

      ),
      "category" => array( "uplift","terraforming" ),
      "text" => "",
      "power_to_active" => false
   ),
    276 => array(
      "name" => "Alien Weapon Cache",
      "nametr" => self::_("£Alien£ Weapon Cache"),
      "nametrc" => clienttranslate("Alien Weapon Cache"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 1,
      "powers" => array(
            3 => array(
               array(
                "power" => "good_for_military",
                "good" => 4,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 3,
                    "xeno" => true,
                    "repeat" => 1
               )
            ))

      ),
      "category" => array( "alien","military","windfall" ),
      "windfalltype" => 4,
      "text" => "",
      "power_to_active" => false
   ),
    277 => array(
      "name" => "Terraforming Uplift Project",
      "nametr" => self::_("€Terraforming€ *Uplift* Project"),
      "nametrc" => clienttranslate("Terraforming Uplift Project"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 0,
      "powers" => array(
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 2,3),
                    "inputfactor" => 2,
                    "different" => true,
                    "output" => array( 'vp' => 3),
                    "repeat" => 1
               )
            ))

      ),
      "category" => array( "uplift","terraforming","chromosome","windfall" ),
      "windfalltype" => 2,
      "text" => "",
      "power_to_active" => false
   ),
    278 => array(
      "name" => "Rebel Black Market Gangs",
      "nametr" => self::_("!Rebel! Black Market Gangs"),
      "nametrc" => clienttranslate("Rebel Black Market Gangs"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 0,
      "powers" => array(
            3 => array(
               array(
                "power" => "good_for_military",
                "good" => 1,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "repeat" => 1
               )
            )),
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1),
                    "card" => 1
                )
            )),

      ),
      "category" => array( "military","windfall","rebel" ),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),
    279 => array(
      "name" => "Uplift Descendants",
      "nametr" => self::_("*Uplift* Descendants"),
      "nametrc" => clienttranslate("Uplift Descendants"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 0,
      "powers" => array(
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,3),
                    "inputfactor" => 2,
                    "different" => true,
                    "output" => array( 'vp' => 3),
                    "repeat" => 1
               )
            )),
            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 1
             )
            ))

      ),
      "category" => array( "uplift","chromosome" ),
      "text" => "",
      "power_to_active" => false
   ),
    280 => array(
      "name" => "Novelty Peddlers",
      "nametr" => self::_("Novelty Peddlers"),
      "nametrc" => clienttranslate("Novelty Peddlers"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
            3 => array(
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => -1,
               )
             ),
           ),
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1),
                    "card" => 2
                )
            )),
            4 => array( array(
                "power" => "consumecard",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "output" => array( 'vp' => 1),
                    "repeat" => 1
               )
            ))

      ),
      "category" => array( "windfall" ),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),
    281 => array(
      "name" => "Anti-Xeno Embassy",
      "nametr" => self::_("^Anti-Xeno^ Embassy"),
      "nametrc" => clienttranslate("Anti-Xeno Embassy"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(
              3 => array(
                array(
                    "power" => "diplomat",
                    "noalien" => true,
                    "discount" => -1,
                    "icon" => '',
                    "text" => "",
                    "arg" => array()
               ),
           ) ,
            4 => array( array(
                "power" => "consumecard",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "output" => array( 'vp' => 1),
                    "repeat" => 1
               )
            )),
            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 1
             )
            ))

      ),
      "category" => array( "antixeno" ),
      "text" => "",
      "power_to_active" => false
   ),
    282 => array(
      "name" => "Galactic Clearinghouse",
      "nametr" => self::_("Galactic Clearinghouse"),
      "nametrc" => clienttranslate("Galactic Clearinghouse"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1,2,3,4),
                    "card" => 1
                )
            )),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'vp' => 1),
                    "repeat" => 4,
                    "different" => true
               )
            )),
            5 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
            ))

      ),
      "category" => array( "windfall" ),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),
    283 => array(
      "name" => "Corrosive Uplift World",
      "nametr" => self::_("Corrosive *Uplift* World"),
      "nametrc" => clienttranslate("Corrosive Uplift World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 0,
      "powers" => array(
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 2,3),
                    "different" => true,
                    "inputfactor" => 2,
                    "output" => array( 'vp' => 3),
                    "repeat" => 1
               )
            )),
            5 => array( array(
              "power" => "produce",
              "icon" => '',
              "text" => "",
              "arg" => array(
                  "resource" => 2
             )
            ))

      ),
      "category" => array( "uplift","chromosome" ),
      "text" => "",
      "power_to_active" => false
   ),
    284 => array(
      "name" => "Imperium Munitions, Inc.",
      "nametr" => self::_("+Imperium+ Munitions, Inc."),
      "nametrc" => clienttranslate("Imperium Munitions, Inc."),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 4,
      "powers" => array(
            3 => array(
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2,
               )
             ),
           ),
            5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 2,
                      "draw" => 1
                 )
               ))

      ),
      "category" => array( "imperium" ),
      "text" => "",
      "power_to_active" => false
   ),
    285 => array(
      "name" => "Anti-Xeno Refugees",
      "nametr" => self::_("^Anti-Xeno^ Refugees"),
      "nametrc" => clienttranslate("Anti-Xeno Refugees"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 0,
      "powers" => array(
            3 => array(
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "xeno" => true
               )
             ),
               array(
                "power" => "good_for_military_defense",
                "good" => array( 1,2,3,4),
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2,
                    "xeno" => true,
                    "repeat" => 1
               )
             )


           ),

      ),
      "category" => array( "windfall","antixeno" ),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    286 => array(
      "name" => "Pan-Galactic Disease Center",
      "nametr" => self::_("Pan-Galactic Disease Center"),
      "nametrc" => clienttranslate("Pan-Galactic Disease Center"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 1,
      "powers" => array(
            3 => array(
               array(
                "power" => "good_for_military_defense",
                "good" => 3,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 3,
                    "xeno" => true,
                    "repeat" => 1
               )
             ),
                array(
                  "power" => "discardtoputgood",
                  "icon" => '',
                  "text" => "",

                  "arg" => array(
                      "resource" => 3
                 )
               )


           ),
            5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 3,
                 )
               ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    287 => array(
      "name" => "Adaptable Uplift Race",
      "nametr" => self::_("Adaptable *Uplift* Race"),
      "nametrc" => clienttranslate("Adaptable Uplift Race"),
      "qt" => 1,
      "type" => "world",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 3,'*'),
                    "inputfactor" => 2,
                    "different" => true,
                    "output" => array( 'vp' => 2, 'card' => 2),
                    "repeat" => 1
               )
            )),

      ),
      "category" => array( "uplift","windfall","chromosome" ),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    288 => array(
      "name" => "Anti-Xeno Alien Bunker",
      "nametr" => self::_("^Anti-Xeno^ £Alien£ Bunker"),
      "nametrc" => clienttranslate("Anti-Xeno Alien Bunker"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 1,
      "powers" => array(
            3 => array(
               array(
                "power" => "good_for_military_defense",
                "good" => 4,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 3,
                    "xeno" => true,
                    "repeat" => 1
               )
             ),
           ),
            5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 4,
                 )
               ))

      ),
      "category" => array( "antixeno","alien" ),
      "text" => "",
      "power_to_active" => false
   ),
    289 => array(
      "name" => "Alien Archives",
      "nametr" => self::_("£Alien£ Archives"),
      "nametrc" => clienttranslate("Alien Archives"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 4,
      "powers" => array(

            3 => array( array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -2,
                    "worldtype" => array( 4)
                )
            )),


        5 => array( array(
                  "power" => "produceifdiscard",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 4
                 )
               ))

      ),
      "category" => array( "alien" ),
      "text" => "",
      "power_to_active" => false
   ),


    290 => array(
      "name" => "Rebel Bunker",
      "nametr" => self::_("!Rebel! Bunker"),
      "nametrc" => clienttranslate("Rebel Bunker"),
      "qt" => 1,
      "type" => "world",
      "cost" => 1,
      "vp" => 0,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "xenodefense" => true
               )
            ))

      ),
      "category" => array( "windfall","military","rebel" ),
      "windfalltype" => 2,
      "text" => "",
      "power_to_active" => false
   ),
    291 => array(
      "name" => "Xeno-Held Imperium Mine",
      "nametr" => self::_("@Xeno@-Held +Imperium+ Mine"),
      "nametrc" => clienttranslate("Xeno-Held Imperium Mine"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 0,
      "powers" => array(
            3 => array( array(
                    "power" => "militaryforce_perimperium",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                   )
                  ),
           ),
            5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 2,
                 )
               ))

      ),
      "category" => array( "xeno","imperium","military" ),
      "text" => "",
      "power_to_active" => false
   ),
    292 => array(
      "name" => "Xeno-Infested Uplift World",
      "nametr" => self::_("@Xeno@-Infested *Uplift* World"),
      "nametrc" => clienttranslate("Xeno-Infested Uplift World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 2,
      "vp" => 1,
      "powers" => array(

      ),
      "category" => array( "windfall","uplift","xeno","military","chromosome" ),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    293 => array(
      "name" => "Rebel Resistance",
      "nametr" => self::_("!Rebel! Resistance (XI)"),
      "nametrc" => clienttranslate("Rebel Resistance (XI)"),
      "qt" => 1,
      "type" => "world",
      "cost" => 3,
      "vp" => 1,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1,
                    "perrebel" => true
               ),
            )),
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2,
                    "xeno" => true
               )
            )),
            5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 2,
                 )
               ))

      ),
      "category" => array( "rebel","military" ),
      "text" => "",
      "power_to_active" => false
   ),
    294 => array(
      "name" => "Uplift Coalition",
      "nametr" => self::_("*Uplift* Coalition"),
      "nametrc" => clienttranslate("Uplift Coalition"),
      "qt" => 1,
      "type" => "world",
      "cost" => 4,
      "vp" => 0,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
            3 => array( array(
                    "power" => "militaryforce_perchromosome",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                   )
                  )),

      ),
      "category" => array( "windfall","uplift","chromosome","military" ),
      "windfalltype" => 3,
      "text" => "",
      "power_to_active" => false
   ),
    295 => array(
      "name" => "Xeno Colony",
      "nametr" => self::_("@Xeno@ Colony"),
      "nametrc" => clienttranslate("Xeno Colony"),
      "qt" => 1,
      "type" => "world",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
            5 => array( array(
                  "power" => "produce",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                      "resource" => 3,
                 )
               ))

      ),
      "category" => array( "military","xeno" ),
      "text" => "",
      "power_to_active" => false
   ),
    296 => array(
      "name" => "Xeno Hostage Planet",
      "nametr" => self::_("@Xeno@ Hostage Planet"),
      "nametrc" => clienttranslate("Xeno Hostage Planet"),
      "qt" => 1,
      "type" => "world",
      "cost" => 5,
      "vp" => 4,
      "powers" => array(

      ),
      "category" => array( "xeno","military","windfall" ),
      "windfalltype" => 1,
      "text" => "",
      "power_to_active" => false
   ),
    297 => array(
      "name" => "Xeno-Infested Alien Outpost",
      "nametr" => self::_("@Xeno@-Infested £Alien£ Outpost"),
      "nametrc" => clienttranslate("Xeno-Infested Alien Outpost"),
      "qt" => 1,
      "type" => "world",
      "cost" => 7,
      "vp" => 5,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2
               )
            )),

      ),
      "category" => array( "alien","xeno","military","windfall" ),
      "windfalltype" => 4,
      "text" => "",
      "power_to_active" => false
   ),
    298 => array(
      "name" => "Alien Defense Center",
      "nametr" => self::_("£Alien£ Defense Center"),
      "nametrc" => clienttranslate("Alien Defense Center"),
      "qt" => 1,
      "type" => "world",
      "cost" => 8,
      "vp" => 8,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 3,
                    "xeno" => true
               )
            )),

      ),
      "category" => array( "alien","windfall","military" ),
      "windfalltype" => 4,
      "text" => "",
      "power_to_active" => false
   ),
    299 => array(
      "name" => "Xeno Warrior Colony",
      "nametr" => self::_("@Xeno@ Warrior Colony"),
      "nametrc" => clienttranslate("Xeno Warrior Colony"),
      "qt" => 1,
      "type" => "world",
      "cost" => 6,
      "vp" => 5,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "xeno" => true
               )
            )),

      ),
      "category" => array( "xeno","military" ),
      "text" => "",
      "power_to_active" => false
   ),
    300 => array(
      "name" => "Xeno-Occupied Rebel World",
      "nametr" => self::_("@Xeno@-Occupied !Rebel! World"),
      "nametrc" => clienttranslate("Xeno-Occupied Rebel World"),
      "qt" => 1,
      "type" => "world",
      "cost" => 8,
      "vp" => 7,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2,
                    "xenodefense" => true
               )
            )),

      ),
      "category" => array( "military","xeno","rebel" ),
      "text" => "",
      "power_to_active" => false
   ),
    301 => array(
      "name" => "Xeno Forward Command",
      "nametr" => self::_("@Xeno@ Forward Command"),
      "nametrc" => clienttranslate("Xeno Forward Command"),
      "qt" => 1,
      "type" => "world",
      "cost" => 9,
      "vp" => 9,
      "powers" => array(

      ),
      "category" => array( "xeno","military" ),
      "text" => "",
      "power_to_active" => false
   ),
    302 => array(
      "name" => "Anti-Xeno Militia",
      "nametr" => self::_("^Anti-Xeno^ Militia"),
      "nametrc" => clienttranslate("Anti-Xeno Militia"),
      "qt" => 2,
      "type" => "development",
      "cost" => 1,
      "vp" => 0,
      "powers" => array(
            3 => array(
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
               )
              ),
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "xeno" => true
               )
              ),
               array(
                "power" => "militaryforcetmp",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 3,
                    "xenodefense" => true
               )
              )


           ),

      ),
      "category" => array(  "antixeno"),
      "text" => "",
      "power_to_active" => false
   ),
    303 => array(
      "name" => "Alien Weapon Plans",
      "nametr" => self::_("£Alien£ Weapon Plans"),
      "nametrc" => clienttranslate("Alien Weapon Plans"),
      "qt" => 2,
      "type" => "development",
      "cost" => 1,
      "vp" => 1,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
             3 => array(
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2,
                    "worldtype" => array( 4)
               )
              ),
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "xeno" => true
               )
              ),
            )

      ),
      "category" => array( "alien" ),
      "text" => "",
      "power_to_active" => false
   ),
    304 => array(
      "name" => "Anti-Xeno Assault Troops",
      "nametr" => self::_("^Anti-Xeno^ Assault Troops"),
      "nametrc" => clienttranslate("Anti-Xeno Assault Troops"),
      "qt" => 2,
      "type" => "development",
      "cost" => 3,
      "vp" => 2,
      "powers" => array(
             3 => array(
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2
               )
              ),
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1,
                    "xeno" => true
               )
              ),
            )

      ),
      "category" => array( "antixeno" ),
      "text" => "",
      "power_to_active" => false
   ),
    305 => array(
      "name" => "Imperium Arms Factory",
      "nametr" => self::_("+Imperium+ Arms Factory"),
      "nametrc" => clienttranslate("Imperium Arms Factory"),
      "qt" => 2,
      "type" => "development",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
            3 => array(
               array(
                "power" => "good_for_military",
                "good" => 2,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2,
                    "repeat" => 1
               )
            )),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 2),
                    "output" => array( 'vp' => 1,'card'=>1),
                    "repeat" => 2
               )
            )),
             5 => array( array(
                "power" => "windfallproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => array( 2),
                    "worldfilter" => array( 'windfall')
               )
             ))


      ),
      "category" => array(  "imperium"),
      "text" => "",
      "power_to_active" => false
   ),
    306 => array(
      "name" => "Construction Corps",
      "nametr" => self::_("Construction Corps"),
      "nametrc" => clienttranslate("Construction Corps"),
      "qt" => 2,
      "type" => "development",
      "cost" => 4,
      "vp" => 2,
      "powers" => array(
            2 => array( array(
                "power" => "devcost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -2
               )
            )),
            5 => array( array(
                  "power" => "repair",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                 )
               ))

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    307 => array(
      "name" => "Galactic Home Front",
      "nametr" => self::_("Galactic Home Front"),
      "nametrc" => clienttranslate("Galactic Home Front"),
      "qt" => 2,
      "type" => "development",
      "cost" => 5,
      "vp" => 3,
      "powers" => array(
            3 => array(
               array(
                "power" => "good_for_military_defense",
                "good" => 1,
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2,
                    "xeno" => true,
                    "repeat" => 1
               )
            )),
            4 => array(
                array(
                    "power" => "consumecard",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "output" => array( 'vp' => 1),
                        "repeat" => 1
                   )
                    ),
                array(
                "power" => "vpchip",
                "vp" => 1,
                "icon" => '',
                "text" => "",
                "arg" => array(
               )
            )


           ),
            5 => array( array(
                "power" => "drawforeachtwo",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               )
            ))


      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),
    308 => array(
      "name" => "Anti-Xeno League",
      "nametr" => self::_("^Anti-Xeno^ League"),
      "nametrc" => clienttranslate("Anti-Xeno League"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            3 => array( array(
                    "power" => "militaryforce_percivil",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "xeno" => true
                   )
                  )),
        5 => array( array(
                  "power" => "drawformilitary",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                    "eachtwo" => true
                 )
               ))

      ),
      "category" => array( "antixeno" ),
      "text" => "",
      "power_to_active" => false
   ),
    309 => array(
      "name" => "Anti-Xeno Rebel Force",
      "nametr" => self::_("^Anti-Xeno^ !Rebel! Force"),
      "nametrc" => clienttranslate("Anti-Xeno Rebel Force"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            3 => array( array(
                "power" => "militaryforce_permilitary",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "rebel" => true
               )
            ))

      ),
      "category" => array( "antixeno","rebel" ),
      "text" => "",
      "power_to_active" => false
   ),
    310 => array(
      "name" => "Imperium War Profiteers",
      "nametr" => self::_("+Imperium+ War Profiteers"),
      "nametrc" => clienttranslate("Imperium War Profiteers"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            3 => array( array(
                "power" => "settlecost",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "cost" => -2,
                    "worldtype" => array( 2)
                )
            )),
            5 => array( array(
                "power" => "drawforeachworld",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => 2,
                    "card" => 1
               )
            ))

      ),
      "category" => array( "imperium" ),
      "text" => "",
      "power_to_active" => false
   ),
    311 => array(
      "name" => "Alien Historians",
      "nametr" => self::_("£Alien£ Historians"),
      "nametrc" => clienttranslate("Alien Historians"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            1 => array( array(
                "power" => "exploredraw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
               ),
            )),
             3 => array(
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2,
                    "worldtype" => array( 4)
               )
              ),
               array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2,
                    "xeno" => true
               )
              ),
            ),
            5 => array( array(
                      "power" => "drawforxenomilitary",
                      "icon" => '',
                      "text" => "",
                      "arg" => array(
                     )
                   ))


      ),
      "category" => array( "alien" ),
      "text" => "",
      "power_to_active" => false
   ),
    312 => array(
      "name" => "Uplift Bio-Engineers",
      "nametr" => self::_("*Uplift* Bio-Engineers"),
      "nametrc" => clienttranslate("Uplift Bio-Engineers"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 3),
                    "output" => array( 'vp' => 2, 'card' => 1),
                    "repeat" => 1
               )
            )),
            5 => array( array(
                "power" => "windfallproduce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "worldtype" => array( 3),
                    "worldfilter" => array( 'windfall')
               )
            ))

      ),
      "category" => array( "uplift" ),
      "text" => "",
      "power_to_active" => false
   ),
    313 => array(
      "name" => "Terraforming Defenders",
      "nametr" => self::_("€Terraforming€ Defenders"),
      "nametrc" => clienttranslate("Terraforming Defenders"),
      "qt" => 1,
      "type" => "development",
      "cost" => 6,
      "vp" => 0,
      "powers" => array(
            3 => array(
               array(
                "power" => "good_for_military_defense",
                "good" => array( 1,2,3,4),
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 2,
                    "xeno" => true,
                    "repeat" => 1
               )
             )


           ),
            4 => array( array(
                "power" => "consume",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "input" => array( 1,2,3,4),
                    "output" => array( 'vp' => 2),
                    "repeat" => 1
               )
            )),
            5 => array( array(
                  "power" => "repair",
                  "icon" => '',
                  "text" => "",
                  "arg" => array(
                 )
               ))

      ),
      "category" => array( "terraforming" ),
      "text" => "",
      "power_to_active" => false
   ),

/******************************************/
/*********** New Worlds *******************/
/******************************************/


    314 => array(
        "startworld_number" => -1,
        "name" => "Star Nomad Raiders",
        "nametr" => self::_("Star Nomad Raiders"),
        "nametrc" => clienttranslate("Star Nomad Raiders"),
        "qt" => 1,
        "type" => "world",
        "cost" => 2,
        "vp" => 1,
        "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
                )
            ) ),
            's' => array( array(
                "power" => "sellbonus",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => array( 1,2,3,4 ),
                    "card" => 2
                ),
            ) ),
        ),
        "category" => array( 'military' ),
        "text" => "",
        "power_to_active" => false
    ),
    315 => array(
        "startworld_number" => -2,
        "name" => "Industrial Robots",
        "nametr" => self::_("Industrial Robots"),
        "nametrc" => clienttranslate("Industrial Robots"),
        "qt" => 1,
        "type" => "world",
        "cost" => 2,
        "vp" => 1,
        "powers" => array(
            2 => array( array(
                "power" => "drawifdev",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
                )
            ) ),
            5 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
                )
            ) )
        ),
        "category" => array(),
        "text" => "",
        "power_to_active" => false
    ),
    316 => array(
        "startworld_number" => -3,
        "name" => "Galactic Trade Emissaries",
        "nametr" => self::_("Galactic Trade Emissaries"),
        "nametrc" => clienttranslate("Galactic Trade Emissaries"),
        "qt" => 1,
        "type" => "world",
        "cost" => 2,
        "vp" => 1,
        "powers" => array(
            3 => array( array(
                "power" => "diplomat",
                "noalien" => true,
                "discount" => -1,
                "icon" => '',
                "text" => "",
                "arg" => array()
            ) ),
            4 => array( array(
                "power" => "draw",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
                )
            ) )
        ),
        "category" => array(),
        "text" => "",
        "power_to_active" => false
    ),
    317 => array(
        "startworld_number" => -4,
        "name" => "Terraforming Colonists",
        "nametr" => self::_("€Terraforming€ Colonists"),
        "nametrc" => clienttranslate("Terraforming Colonists"),
        "qt" => 1,
        "type" => "world",
        "cost" => 2,
        "vp" => 1,
        "powers" => array(
            3 => array( array(
                "power" => "drawifsettle",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "card" => 1
                )
            ) ),
            5 => array( array(
                "power" => "windfallproduceifdiscard",
                "icon" => '',
                "text" => "",
                "arg" => array()
            ) )
        ),
        "category" => array( 'terraforming' ),
        "text" => "",
        "power_to_active" => false
    ),
    318 => array(
        "startworld_number" => -5,
        "name" => "Abandoned Mine Squatters",
        "nametr" => self::_("Abandoned Mine Squatters"),
        "nametrc" => clienttranslate("Abandoned Mine Squatters"),
        "qt" => 1,
        "type" => "world",
        "cost" => 2,
        "vp" => 0,
        "powers" => array(
            3 => array( array(
                "power" => "militaryforce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "force" => 1
                )
            ) ),
            5 => array( array(
                "power" => "produce",
                "icon" => '',
                "text" => "",
                "arg" => array(
                    "resource" => 2
                )
            ) )
        ),
        "category" => array( 'military' ),
        "text" => "",
        "power_to_active" => false
    ),
    319 => array(
            "startworld_number" => -6,
            "name" => "Gateway Station",
            "nametr" => self::_("Gateway Station"),
            "nametrc" => clienttranslate("Gateway Station"),
            "qt" => 1,
            "type" => "world",
            "cost" => 2,
            "vp" => 1,
            "powers" => array(
                4 => array( array(
                    "power" => "consume",
                    "icon" => '',
                    "text" => "",
                    "arg" => array(
                        "input" => array( 1, 2, 3, 4 ),
                        "inputfactor" => 2,
                        "output" => array( 'vp' => 1, 'card' => 3 ),
                        "repeat" => 1
                    )
                ) ),
            ),
            "category" => array( "windfall" ),
            "windfalltype" => 1,
            "text" => "",
            "power_to_active" => false
    ),

    1000 => array( // Specific : damaged world
      "name" => "Damaged world",
      "nametr" => self::_("Damaged world"),
      "nametrc" => clienttranslate("Damaged world"),
      "qt" => 0,
      "type" => "world",
      "cost" => 0,
      "vp" => 0,
      "powers" => array(
      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),


/*
    260 => array(
      "name" => "",
      "nametr" => self::_(""),
      "nametrc" => clienttranslate(""),
      "qt" => 1,
      "type" => "world",
      "cost" => ,
      "vp" => ,
      "powers" => array(

      ),
      "category" => array(  ),
      "text" => "",
      "power_to_active" => false
   ),


*/





);

$this->goal_types = array(

    120 => array(
        'type' => 'first',
        'phases' => array( 2, 3),
        'points' => 3,
        'name' => clienttranslate("Innovation Leader"),
        'description' => clienttranslate("First player to have at least one power for each phase (including Sell)")
   ),
    121 => array(
        'type' => 'first',
        'phases' => array( 3),
        'points' => 3,
        'name' => clienttranslate("System diversity"),
        'description' => clienttranslate("First to have at least one production or windfall world of each kind.")
   ),
    122 => array(
        'type' => 'first',
        'phases' => array( 2),
        'points' => 3,
        'name' => clienttranslate("Galactic Status"),
        'description' => clienttranslate("First to place a 6-cost &lt;?&gt; development.")
   ),
    123 => array(
        'type' => 'first',
        'phases' => array( 'discard'),
        'points' => 3,
        'name' => clienttranslate("Budget Surplus"),
        'description' => clienttranslate("First to discard at least one card at round end.")
   ),
    124 => array(
        'type' => 'first',
        'phases' => array( 2,3),
        'points' => 3,
        'name' => clienttranslate("Overlord Discoveries"),
        'description' => clienttranslate("First to have at least three £ALIEN£ cards in tableau.")
   ),
    125 => array(
        'type' => 'first',
        'phases' => array( 4),
        'points' => 3,
        'name' => clienttranslate("Galactic Standard of Living"),
        'description' => clienttranslate("First to have 5 (or more) VPs in chips.")
   ),

    126 => array(
        'type' => 'most',
        'phases' => array( 2,3),
        'points' => 5,
        'name' => clienttranslate("Greatest Military"),
        'description' => clienttranslate("At least six and the most total military in tableau.")
   ),
    127 => array(
        'type' => 'most',
        'phases' => array( 3),
        'points' => 5,
        'name' => clienttranslate("Production Leader"),
        'description' => clienttranslate("At least four and the most production worlds, of any kind, in tableau.")
   ),
    128 => array(
        'type' => 'most',
        'phases' => array( 2, 3),
        'points' => 5,
        'name' => clienttranslate("Greatest Infrastructure"),
        'description' => clienttranslate("At least four and the most developments in tableau.")
   ),
    129 => array(
        'type' => 'most',
        'phases' => array( 3),
        'points' => 5,
        'name' => clienttranslate("Largest Industry"),
        'description' => clienttranslate("At least three and the most Novelty and/or Rare production or windfall worlds in tableau.")
   ),


    // Rebel vs Imperium
    170 => array(
        'type' => 'first',
        'phases' => array( 3,4,5),
        'points' => 3,
        'name' => clienttranslate("Galactic Riches"),
        'description' => clienttranslate("First to have at least four goods in tableau.")
   ),
    171 => array(
        'type' => 'first',
        'phases' => array( 2,3),
        'points' => 3,
        'name' => clienttranslate("Expansion Leader"),
        'description' => clienttranslate("First to have at least 8 cards in tableau.")
   ),
    172 => array(
        'type' => 'first',
        'phases' => array( 2,3),
        'points' => 3,
        'name' => clienttranslate("Uplift Knowledge"),
        'description' => clienttranslate("First to have at least three *UPLIFT* cards in tableau.")
   ),
    173 => array(
        'type' => 'most',
        'phases' => array( 3),
        'points' => 5,
        'name' => clienttranslate("Propaganda Edge"),
        'description' => clienttranslate("At least three and the most !Rebel! Military worlds.")
   ),
    174 => array(
        'type' => 'most',
        'phases' => array( 2,3),
        'points' => 5,
        'name' => clienttranslate("Research Leader"),
        'description' => clienttranslate("At least three and the most cards with Explore (Phase I) powers in tableau")
   ),

    /// Brink of war
    221 => array(
        'type' => 'most',
        'phases' => array( 1,2,3,4,5),
        'points' => 5,
        'name' => clienttranslate("Galactic Prestige"),
        'description' => clienttranslate("At least three and the most Prestige chips.")
   ),
    222 => array(
        'type' => 'most',
        'phases' => array( 2,3),
        'points' => 5,
        'name' => clienttranslate("Prosperity Lead"),
        'description' => clienttranslate("At least three and the most cards with Consume (Phase IV) powers in tableau.")
   ),
    223 => array(
        'type' => 'first',
        'phases' => array( 1,2,3,4,5),
        'points' => 3,
        'name' => clienttranslate("Galactic Standing"),
        'description' => clienttranslate("First to have at least two prestige chips and at least three VP chips.")
   ),
    224 => array(
        'type' => 'first',
        'phases' => array( 2,3),
        'points' => 3,
        'name' => clienttranslate("Military Influence"),
        'description' => clienttranslate("First to have either at least three +IMPERIUM+ cards or at least four military worlds in tableau.")
   ),
    225 => array(
        'type' => 'first',
        'phases' => array( 2,3),
        'points' => 3,
        'name' => clienttranslate("Peace/War Leader"),
        'no_takeover_name' => clienttranslate("Peace Leader"),
        'description' => clienttranslate("First to have either Military less than zero, with at least two worlds in tableau, or a takeover power, with at least two military worlds in tableau."),
        'no_takeover_description' => clienttranslate("First to have Military less than zero, with at least two worlds in tableau.")
   ),

    226 => array(
        'type' => 'pr',
        'phases' => array( 1,2,3,4,5,'discard','prestige_spent'),
        'points' => 0,
        'name' => clienttranslate("Prestige leader"),
        'description' => clienttranslate("All players with the most prestige gets 1 VP at the beginning of each turn. If there is a sole Prestige Leader and he gained prestige last turn, then he also gets 1 card.")
   ),


);

$this->good_types = array(
    1 => self::_('Novelty goods'),
    2 => self::_('Rare elements'),
    3 => self::_('Genes'),
    4 => self::_('Alien technology')
);

$this->good_types_untr = array(
    1 => clienttranslate('Novelty goods'),
    2 => clienttranslate('Rare elements'),
    3 => clienttranslate('Genes'),
    4 => clienttranslate('Alien technology')
);

$this->start_world = array(32,33,34,35,36);

$this->sell_prices = array(
    1 => 2,
    2 => 3,
    3 => 4,
    4 => 5
);

$this->six_cost_developments = array(
    11,21,22,23,24,25,26,27,28,29,30,31,
    100,101,119,
    146,147,148,149,150,152,
    187,192,193,197,199,201,
    247,260,261,262,263,264,265,267,
    308,309,310,311,312,313, 283,294

);

$this->dummy_categories = array(    // Used for translations
    self::_('windfall'), self::_('uplift'), self::_('rebel'), self::_('military'), self::_('imperium'), self::_("alien"), self::_("terraforming"),
    self::_('chromosome'), self::_('xeno'), self::_('antixeno')
);

$this->orb_cards_types = array(

    1 => array(
        'horwalls' => array( 'X40','000','6XX'),
        'verwalls' => array( 'XX','X0','0X','00'),
        'content' => array( 'B00','00T')
   ),
    2 => array(
        'horwalls' => array( '0XX','00X','0XX'),
        'verwalls' => array( 'X0','05','00','0X'),
        'content' => array( '000','00B')
   ),
    3 => array(
        'horwalls' => array( 'XX0','00X','XX0'),
        'verwalls' => array( 'XX','X0','00','00'),
        'content' => array( 'B00','000')
   ),
    4 => array(
        'horwalls' => array( '0XX','0X0','0XX'),
        'verwalls' => array( '0X','X0','00','XX'),
        'content' => array( '0B0','TS0')
   ),
    5 => array(
        'horwalls' => array( '4XX','000','0XX'),
        'verwalls' => array( 'X0','0X','X0','XX'),
        'content' => array( '00B','T00')
   ),
    6 => array(
        'horwalls' => array( 'X0X','000','0XX'),
        'verwalls' => array( 'X0','0X','X0','X4'),
        'content' => array( '00B','000')
   ),
    7 => array(
        'horwalls' => array( 'XX4','000','0XX'),
        'verwalls' => array( 'X0','0X','X0','5X'),
        'content' => array( '000','0B0')
   ),
    8 => array(
        'horwalls' => array( '0X0','000','0X4'),
        'verwalls' => array( '00','6X','X0','XX'),
        'content' => array( '0B0','000')
   ),
    9 => array(
        'horwalls' => array( 'XXX','000','XX4'),
        'verwalls' => array( 'XX','00','00','X4'),
        'content' => array( '00T','B00')
   ),
//    10 => array(
//        'horwalls' => array( '','',''),
//        'verwalls' => array( '','','',''),
//        'content' => array( '','')
//   ),

//////////////////

    11 => array(
        'horwalls' => array( 'XX0','0XX','X0X'),
        'verwalls' => array( '00','03','00','00'),
        'content' => array( '00S','0TS')
   ),
    12 => array(
        'horwalls' => array( 'X0X','XX0','0XX'),
        'verwalls' => array( '00','00','04','00'),
        'content' => array( '0S0','0T0')
   ),
    13 => array(
        'horwalls' => array( '0X0','X03','X00'),
        'verwalls' => array( '00','05','XX','00'),
        'content' => array( '0S0','T00')
   ),
    14 => array(
        'horwalls' => array( '0X0','000','X00'),
        'verwalls' => array( '0X','X0','X0','XX'),
        'content' => array( '0T0','S0S')
   ),
    15 => array(
        'horwalls' => array( 'XXX','06X','0XX'),
        'verwalls' => array( 'X0','00','00','X0'),
        'content' => array( 'S0B','00T')
   ),
    16 => array(
        'horwalls' => array( 'XX4','0X0','XX0'),
        'verwalls' => array( 'X6','00','00','X0'),
        'content' => array( 'B00','000')
   ),
    17 => array(
        'horwalls' => array( 'XX0','0XX','X65'),
        'verwalls' => array( 'XX','00','00','0X'),
        'content' => array( '000','B00')
   ),
    18 => array(
        'horwalls' => array( '0XX','000','XX5'),
        'verwalls' => array( '0X','X0','X0','4X'),
        'content' => array( '0B0','S00')
   ),
    19 => array(
        'horwalls' => array( '470','000','XX0'),
        'verwalls' => array( 'X5','X0','X0','0X'),
        'content' => array( '0B0','000')
   ),
    20 => array(
        'horwalls' => array( 'XX0','000','XX0'),
        'verwalls' => array( 'X6','00','00','X0'),
        'content' => array( 'B00','000')
   ),
//////////////////

    21 => array(
        'horwalls' => array( 'XX0','000','0X0'),
        'verwalls' => array( '6X','0X','XX','00'),
        'content' => array( '000','0!0')
   ),
    22 => array(
        'horwalls' => array( '0X0','000','0X0'),
        'verwalls' => array( 'XX','5X','XX','X0'),
        'content' => array( '000','0!0')
   ),
    23 => array(
        'horwalls' => array( '0XX','0XX','X0X'),
        'verwalls' => array( 'X0','00','00','X0'),
        'content' => array( '00!','S00')
   ),
    24 => array(
        'horwalls' => array( 'XX3','0XX','0X0'),
        'verwalls' => array( 'X0','00','00','XX'),
        'content' => array( 'S0!','000')
   ),
    25 => array(
        'horwalls' => array( '0X0','00X','00X'),
        'verwalls' => array( '0X','0X','00','XX'),
        'content' => array( '000','00!')
   ),
    26 => array(
        'horwalls' => array( 'X0X','0X0','5X6'),
        'verwalls' => array( '4X','00','00','3X'),
        'content' => array( '000','0A0')
   ),
    27 => array(
        'horwalls' => array( 'XX0','000','X00'),
        'verwalls' => array( '0X','0X','3X','X0'),
        'content' => array( '000','A00')
   ),
    28 => array(
        'horwalls' => array( 'XX4','004','X0X'),
        'verwalls' => array( '00','0X','0X','0X'),
        'content' => array( 'S00','00A')
   ),
    29 => array(
        'horwalls' => array( 'X00','000','X0X'),
        'verwalls' => array( '0X','0X','0X','X0'),
        'content' => array( '000','!00')
   ),
    30 => array(
        'horwalls' => array( '0X0','50X','X0X'),
        'verwalls' => array( 'X0','X0','00','X0'),
        'content' => array( 'TST','000')
   ),
///////////////////////////

    31 => array(
        'horwalls' => array( 'X5X','000','X00'),
        'verwalls' => array( 'XX','0X','00','00'),
        'content' => array( '000','AT0')
   ),
    32 => array(
        'horwalls' => array( '0X0','000','XX0'),
        'verwalls' => array( 'XX','X0','X3','0X'),
        'content' => array( 'TA0','000')
   ),
    33 => array(
        'horwalls' => array( 'XX0','X00','XX4'),
        'verwalls' => array( '0X','00','0X','X0'),
        'content' => array( '000','A0T')
   ),
    34 => array(
        'horwalls' => array( 'XXX','X4X','XX0'),
        'verwalls' => array( '00','X0','00','00'),
        'content' => array( 'TA0','000')
   ),
    35 => array(
        'horwalls' => array( 'X0X','030','X4X'),
        'verwalls' => array( 'XX','05','06','XX'),
        'content' => array( 'T00','00!')
   ),
    36 => array(
        'horwalls' => array( 'XX0','XX0','0X0'),
        'verwalls' => array( '00','03','X0','X0'),
        'content' => array( '0!0','000')
   ),
    37 => array(
        'horwalls' => array( 'X0X','006','X0X'),
        'verwalls' => array( 'X0','4X','0X','X0'),
        'content' => array( '00!','000')
   ),
    38 => array(
        'horwalls' => array( '0X0','XX0','0X0'),
        'verwalls' => array( '0X','00','0X','X3'),
        'content' => array( '000','0!0')
   ),
    39 => array(
        'horwalls' => array( 'XX0','030','0X0'),
        'verwalls' => array( 'XX','00','50','X0'),
        'content' => array( '0!0','S00')
   ),
    40 => array(
        'horwalls' => array( 'X50','0X0','0X0'),
        'verwalls' => array( '4X','00','X0','0X'),
        'content' => array( '0!0','0S0')
   ),
////////////////////////////////////

    41 => array(
        'horwalls' => array( '0X0','000','000'),
        'verwalls' => array( '0X','00','00','0X'),
        'content' => array( '...','.D.')
   ),
    42 => array(
        'horwalls' => array( '000','000','X00'),
        'verwalls' => array( 'X0','00','00','3X'),
        'content' => array( 'TD.','...')
   ),
    43 => array(
        'horwalls' => array( '00X','000','0XX'),
        'verwalls' => array( 'X0','0X','0X','X0'),
        'content' => array( '...','.d.')
   ),
    44 => array(
        'horwalls' => array( '00X','000','0XX'),
        'verwalls' => array( 'X0','0X','0X','X0'),
        'content' => array( '...','.d.')
   ),
    45 => array(
        'horwalls' => array( '0X3','000','0X0'),
        'verwalls' => array( 'X4','0X','X6','XX'),
        'content' => array( '00A','0A0')
   ),
    46 => array(
        'horwalls' => array( 'XXX','300','5XX'),
        'verwalls' => array( '06','04','00','X0'),
        'content' => array( '00A','A00')
   ),
    47 => array(
        'horwalls' => array( '0XX','4X0','X60'),
        'verwalls' => array( '0X','X0','0X','X0'),
        'content' => array( '0A0','0A0')
   ),
    48 => array(
        'horwalls' => array( 'X0X','X0X','X0X'),
        'verwalls' => array( '0X','50','53','XX'),
        'content' => array( '00A','A0T')
   ),
    49 => array(
        'horwalls' => array( '005','X0X','XX0'),
        'verwalls' => array( '0X','00','XX','X0'),
        'content' => array( '00A','!0T')
   ),

    50 => array(
        'horwalls' => array( 'XX0','040','0X0'),
        'verwalls' => array( 'X0','0X','X0','X0'),
        'content' => array( '0A0','0T0')
   ),

);

$this->artefact_types = array(

    1 => array(
        'level' => 'A',
        'descr' => clienttranslate('-2 on cost of a non-military world.'),
        'nbr' => 3,
        'vp' => 2,
        'alienscience' => true
   ),
    2 => array(
        'level' => 'A',
        'descr' => clienttranslate('Can be played instead of an Alien resource'),
        'nbr' => 4,
        'vp' => 0,
        'alienscience' => false
   ),


    3 => array(
        'level' => 'A',
        'descr' => clienttranslate('One of your team can go through a wall (or a barrier) during its next move.'),
        'nbr' => 5,
        'vp' => 2,
        'alienscience' => false
   ),
    4 => array(
        'level' => 'A',
        'descr' => clienttranslate('-2 on cost (or +2 military) for Genetic Worlds until the end of the phase.'),
        'nbr' => 4,
        'vp' => 0,
        'alienscience' => false,
        'station' => true
   ),
    5 => array(
        'level' => 'A',
        'descr' => clienttranslate('A team can move 4 more squares.'),
        'nbr' => 6,
        'vp' => 2,
        'alienscience' => false
   ),
    6 => array(
        'level' => 'A',
        'descr' => clienttranslate('One of your team can go through any barrier OR Improve your military by +2 until the end of the phase.'),
        'nbr' => 2,
        'vp' => 1,
        'alienscience' => false
   ),
    7 => array(
        'level' => 'A',
        'descr' => clienttranslate('-2 on development cost'),
        'nbr' => 3,
        'vp' => 1,
        'alienscience' => true
   ),
    8 => array(
        'level' => 'A',
        'descr' => clienttranslate('One of your team can go through any barrier OR Improve your military by +1 until the end of the phase.'),
        'nbr' => 3,
        'vp' => 2,
        'alienscience' => false
   ),

    9 => array(
        'level' => 'B',
        'descr' => clienttranslate('Can be played instead of an Alien resource'),
        'nbr' => 3,
        'vp' => 3,
        'alienscience' => false
   ),
    10 => array(
        'level' => 'B',
        'descr' => clienttranslate('-3 on development cost'),
        'nbr' => 2,
        'vp' => 1,
        'alienscience' => true
   ),
    11 => array(
        'level' => 'B',
        'descr' => clienttranslate('One of your team can go through any barrier OR Improve your military by +2 until the end of the phase.'),
        'nbr' => 2,
        'vp' => 3,
        'alienscience' => false
   ),
    12 => array(
        'level' => 'B',
        'descr' => clienttranslate('One of your team can go through any barrier OR Improve your military by +3 until the end of the phase.'),
        'nbr' => 2,
        'vp' => 2,
        'alienscience' => false
   ),
    13 => array(
        'level' => 'B',
        'descr' => clienttranslate('-3 on cost of a non-military world.'),
        'nbr' => 2,
        'vp' => 2,
        'alienscience' => true
   ),
    14 => array(
        'level' => 'B',
        'descr' => clienttranslate('No special power.'),
        'nbr' => 4,
        'vp' => 2,
        'alienscience' => false,
        'station' => true
   ),



);


$this->invasion_cards_types = array(


    1 => array( 'wave' => 1, 'value' => -1, 'nbr' => 2),
    2 => array( 'wave' => 1, 'value' => 0, 'nbr' => 2),
    3 => array( 'wave' => 1, 'value' => 1, 'nbr' => 3),
    4 => array( 'wave' => 1, 'value' => 2, 'nbr' => 2),
    5 => array( 'wave' => 1, 'value' => 3, 'nbr' => 1),
    6 => array( 'wave' => 1, 'value' => 4, 'nbr' => 1),

    7 => array( 'wave' => 2, 'value' => 0, 'nbr' => 2),
    8 => array( 'wave' => 2, 'value' => 1, 'nbr' => 2),
    9 => array( 'wave' => 2, 'value' => 2, 'nbr' => 2),
    10 => array( 'wave' => 2, 'value' => 3, 'nbr' => 1),
    11 => array( 'wave' => 2, 'value' => 4, 'nbr' => 1),
    12 => array( 'wave' => 2, 'value' => 5, 'nbr' => 1),
    13 => array( 'wave' => 2, 'value' => 6, 'nbr' => 1),
    14 => array( 'wave' => 2, 'value' => 7, 'nbr' => 1),


    15 => array( 'wave' => 3, 'value' => 3, 'nbr' => 4),
    16 => array( 'wave' => 3, 'value' => 4, 'nbr' => 3),
    17 => array( 'wave' => 3, 'value' => 5, 'nbr' => 1),
    18 => array( 'wave' => 3, 'value' => 6, 'nbr' => 1),
    19 => array( 'wave' => 3, 'value' => 7, 'nbr' => 1),
    20 => array( 'wave' => 3, 'value' => 8, 'nbr' => 2),
    21 => array( 'wave' => 3, 'value' => 9, 'nbr' => 2),
    22 => array( 'wave' => 3, 'value' => 103, 'nbr' => 1),
    23 => array( 'wave' => 3, 'value' => 104, 'nbr' => 1),
    24 => array( 'wave' => 3, 'value' => 105, 'nbr' => 1),

);

$this->invasion_config = array(

    2 => array(
        'goal' => 22,
        'rewards' => array(
            1 => 1,
            2 => 2
       )
   ),
    3 => array(
        'goal' => 28,
        'rewards' => array(
            1 => 1,
            2 => 1,
            3 => 2
       )
   ),
    4 => array(
        'goal' => 33,
        'rewards' => array(
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 2
       )
   ),
    5 => array(
        'goal' => 39,
        'rewards' => array(
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 2,
            5 => 2
       )
   ),


);
