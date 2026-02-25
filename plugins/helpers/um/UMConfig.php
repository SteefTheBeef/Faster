<?php

require_once 'UMConfigEntry.php';
require_once 'UmMap.php';

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
                1 => 116,
                2 => 98,
                3 => 83,
                4 => 71,
                5 => 60,
                6 => 49,
                7 => 39,
                8 => 30,
                9 => 21,
                10 => 13,
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
            new UmMap("7yMu9T9aAjvWxsE5Kd8CEGJ2Ja1"),
            new UmMap("JMD02KeNXc6qccMNsNtolktLL88"),
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
        ));

        $this->um4Semi = new UMConfigEntry(array(
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
            23  => 2,
        ));

    }
}