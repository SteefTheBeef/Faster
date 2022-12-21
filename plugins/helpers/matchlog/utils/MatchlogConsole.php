<?php

class MatchlogConsole {
    static function consoleMatchlogEndRace($player) {
        global $_debug;

        if($_debug>1) {
            // Player did not end the race
            console("matchlogEndRace:: skipped: {$player['Login']},{$player['Active']},{$player['CheckpointNumber']},
                {$player['Score']},{$player['LapNumber']},{$player['LastCpTime']},{$player['BestLapTime']}");
        }
    }

    static function consoleMatchlogEndRaceNoFinishedPlayers() {
        global $_debug;

        if($_debug>1) {
            console("matchlogEndRace:: Not sent to matchlog:  no finishedPlayers or none have finished !!!");
        }
    }

    static function consoleMatchlogEndRaceNoCheckpoints() {
        global $_debug;

        if($_debug>1) {
            console("matchlogEndRace:: Not sent to matchlog: _NumberOfChecks=0 !!!");
        }
    }
}

