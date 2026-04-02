<?php

class UMConfig {

    public $um3Semi;
    public $um4QualiBestRace;
    public $um4QualiBestLap;
    public $um4Semi;
    public $um4GF;


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



        $um4MapsTA = MapFactory::um4TA();
        $um4MapsPO = MapFactory::um4PO();

        $this->um4QualiBestRace = new UMConfigEntry(PointsDistributionFactory::um4QualiBestRace(), $um4MapsTA, array(), 4, 1, 'fastlog/um/quali');
        $this->um4QualiBestLap = new UMConfigEntry(PointsDistributionFactory::um4QualiBestLap(), $um4MapsTA, array(), 4, 1, 'fastlog/um/quali');
        $this->um4Semi = new UMConfigEntry(PointsDistributionFactory::um4Semi(), $um4MapsTA, array(), 6, 2, 'fastlog/um/semi');
        $this->um4GF = new UMConfigEntry(PointsDistributionFactory::um4GF(), $um4MapsPO, array(), 8, 3, 'fastlog/um/gf' );
        $this->um4GFPractice = new UMConfigEntry(PointsDistributionFactory::um4GF(), $um4MapsPO, array(), 8, 3, 'fastlog/um/gf-practice' );
    }
}