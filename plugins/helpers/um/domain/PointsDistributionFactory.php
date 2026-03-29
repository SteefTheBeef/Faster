<?php

class PointsDistributionFactory {
    public static function um4QualiBestRace() {
        return array(
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
        );
    }

    public static function um4QualiBestLap() {
        return array(
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
        );
    }

    public static function um4Semi() {
        return array(
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
            23 => 10
        );
    }

    public static function um4GF() {
        return array(
            0 => 150,
            1 => 120,
            2 => 100,
            3 => 90,
            4 => 80,
            5 => 70,
            6 => 60,
            7 => 50,
            8 => 40,
            9 => 30,
            10 => 20,
            11 => 10
        );
    }
    static function generatePointsArray() {
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