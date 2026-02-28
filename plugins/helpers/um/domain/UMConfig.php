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

        $this->um4QualiBestRace = new UMConfigEntry(array(
            0 => 200,
            1 => 150,
            2 => 120,
            3 => 100,
            4 => 84,
            5 => 74,
            6 => 66,
            7 => 60,
            8 => 54,
            9 => 48,
            10 => 44,
            11 => 40,
            12 => 36,
            13 => 32,
            14 => 28,
            15 => 24,
            16 => 20,
            17 => 16,
            18 => 12,
            19 => 10,
            20 => 8,
            21 => 6,
            22 => 4,
            23 => 2,
        ), array(
            //new UmMap("7yMu9T9aAjvWxsE5Kd8CEGJ2Ja1"), // Rally Test Map
            new UmMap("rxOudfAAp8TOqTvuPb3ZP4CFFD2"), // Stadium
            new UmMap("JQ_Us9p1shVrQdlBoEFA7mhStum"), // Desert
            new UmMap("zYqW_6lKUlVQXqxVOKn6y8QNq_k"), // Rally
            new UmMap("nGdljXubw46e83kSOxmMgNUaYg4"), // Bay
            new UmMap("Qjk_gRmQ2jHMxUhQ6mpo8OETWib"), // Coast
            new UmMap("OROjIlKmttKyDLxnvszbkBWp_m0"), // Snow
            new UmMap("ntaH70PYsP6ndT_W5s9oK3tLtC6"), // Island

        ));

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
        ), $this->um4QualiBestRace->maps);

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
            0 => 450,
            1 => 335,
            2 => 270,
            3 => 225,
            4 => 190,
            5 => 165,
            6 => 150,
            7 => 135,
            8 => 120,
            9 => 110,
            10 => 100,
            11 => 90,
            12 => 80,
            13 => 70,
            14 => 60,
            15 => 50,
            16 => 40,
            17 => 35,
            18 => 30,
            19 => 25,
            20 => 20,
            21 => 15,
            22 => 10,
            23 => 5,
        ));

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