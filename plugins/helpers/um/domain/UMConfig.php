<?php

class UMConfig {

    public $um3Semi;
    public $um4GF;
    public $um4QualiBestRace;
    public $um4QualiBestLap;
    public $um4Semi;


    public function __construct() {
        $this->um3Semi = new UMConfigEntry(
            array(0 => 1200,
                1 => 1050,
                2 => 960,
                3 => 900,
                4 => 840,
                5 => 810,
                6 => 780,
                7 => 750,
                8 => 720,
                9 => 690,
                10 => 660,
                11 => 630,
                12 => 600,
                13 => 570,
                14 => 540,
                15 => 510,
                16 => 480,
                17 => 450,
                18 => 420,
                19 => 390,
                20 => 360,
                21 => 330,
                22 => 300,
                23 => 270,
            ));

        $this->um4GF = new UMConfigEntry(
            array(0 => 150,
                1 => 120,
                2 => 100,
                3 => 80,
                4 => 70,
                5 => 60,
                6 => 50,
                7 => 40,
                8 => 30,
                9 => 20,
                10 => 10,
                11 => 5
            ));

        $um4MapsTA = array(
            // Stadium
            new UmMap(
                "rxOudfAAp8TOqTvuPb3ZP4CFFD2",
                'UM4 TA - BUNKERBUSTER',
                'shorty_88',
                'Stadium'
            ), // Speed
            new UmMap(
                "soKTEBzpg_iPyw2A4pPgkF_85J",
                '$i$fffUM4 TA - $000In aeternum',
                'jea_quilhas',
                'Speed'
            ), // Rally
            new UmMap(
                "zYqW_6lKUlVQXqxVOKn6y8QNq_k",
                '$o$000UM4 TA $0f0Deus $0f0Maximus',
                'blackcat111',
                'Rally'

            ), // Bay
            new UmMap(
                "nGdljXubw46e83kSOxmMgNUaYg4",
                'UM4 TA Phantom Island',
                'kybermato',
                'Bay'
            ), // Coast
            new UmMap(
                "Qjk_gRmQ2jHMxUhQ6mpo8OETWib",
                '$o$000UM4 TA $fa3Providence',
                'blackcat111',
                'Coast'
            ), // Alpine
            new UmMap(
                "OROjIlKmttKyDLxnvszbkBWp_m0",
                '$fffUM4 TA United Maniacs',
                'sandder',
                'Alpine',
                'Alpine'
            ), // Island
            new UmMap(
                "ntaH70PYsP6ndT_W5s9oK3tLtC6",
                '$F00UM4 TA $000Wh$222en $444Lan$666g$888ua$999ge $FFFFails',
                'mi_me',
                'Island'
            ),
        );

        $this->um4QualiBestRace = new UMConfigEntry(array(
            0 => 400,
            1 => 300,
            2 => 240,
            3 => 200,
            4 => 168,
            5 => 148,
            6 => 132,
            7 => 120,
            8 => 108,
            9 => 96,
            10 => 88,
            11 => 80,
            12 => 72,
            13 => 64,
            14 => 56,
            15 => 48,
            16 => 40,
            17 => 32,
            18 => 24,
            19 => 20,
            20 => 16,
            21 => 12,
            22 => 8,
            23 => 4,
        ), $um4MapsTA, array(), 4);

        $this->um4QualiBestLap = new UMConfigEntry(array(
            0 => 100,
            1 => 75,
            2 => 60,
            3 => 50,
            4 => 42,
            5 => 36,
            6 => 33,
            7 => 30,
            8 => 27,
            9 => 24,
            10 => 22,
            11 => 20,
            12 => 18,
            13 => 16,
            14 => 14,
            15 => 12,
            16 => 10,
            17 => 8,
            18 => 6,
            19 => 5,
            20 => 4,
            21 => 3,
            22 => 2,
            23 => 1,
        ), $um4MapsTA, array(), 4);

        // Without stints
//        $this->um4Semi = new UMConfigEntry(array(
//            0 => 900,
//            1 => 670,
//            2 => 540,
//            3 => 450,
//            4 => 380,
//            5 => 330,
//            6 => 300,
//            7 => 270,
//            8 => 240,
//            9 => 220,
//            10 => 200,
//            11 => 180,
//            12 => 160,
//            13 => 140,
//            14 => 120,
//            15 => 100,
//            16 => 80,
//            17 => 70,
//            18 => 60,
//            19 => 50,
//            20 => 40,
//            21 => 30,
//            22 => 20,
//            23 => 10,
//        ));

        // Trying to get it similar in scale to how UM3 was
        // Sum of (quali best race and best lap) * 3 for each index then divided by 2 because of 2 stints
        $this->um4Semi = new UMConfigEntry(array(
            0 => 750,
            1 => 600,
            2 => 500,
            3 => 400,
            4 => 350,
            5 => 300,
            6 => 250,
            7 => 225,
            8 => 200,
            9 => 180,
            10 => 160,
            11 => 140,
            12 => 125,
            13 => 110,
            14 => 95,
            15 => 80,
            16 => 65,
            17 => 55,
            18 => 45,
            19 => 35,
            20 => 25,
            21 => 20,
            22 => 15,
            23 => 10,
        ), $um4MapsTA, array(), 6, 2);
    }
    function generatePointsArray() {
        $first = 500;
        $last = 10;
        $spots = 24;

        $targetSecondPct = 0.77;
        $targetSecond = $first * $targetSecondPct;

        // rank 2 => t = 1/($spots-1)
        $t2 = 1 / ($spots - 1);

        // Solve for $power:
        // (targetSecond - first) / (last - first) = t2^power
        $ratio = ($targetSecond - $first) / ($last - $first);
        $power = log($ratio) / log($t2); // ~0.510...

        $umConfig['um4_semi'] = array();
        for ($rank = 1; $rank <= $spots; $rank++) {
            $t = ($rank - 1) / ($spots - 1);
            $umConfig['um4_semi'][$rank] = (int)round($first + ($last - $first) * pow($t, $power));
            $umConfig['um4_semi'][$rank] = round($umConfig['um4_semi'][$rank] / 10) * 2;
        }

        //console(print_r($umConfig['um4_semi'], true));

        // quick sanity check
        echo "power=" . $power . "\n";
        echo "1st=" . $umConfig['um4_semi'][1] . "\n";
        echo "2nd=" . $umConfig['um4_semi'][2] . " (" . round($umConfig['um4_semi'][2] / $umConfig['um4_semi'][1] * 100, 2) . "%)\n";
        echo "24th=" . $umConfig['um4_semi'][24] . "\n";
    }

}