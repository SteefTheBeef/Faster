<?php

class Matchlog {

    /**
     * @param $logState - END_RACE, END_ROUND, BEGIN_ROUND
     * @param $gameMode
     * @param $challengeInfo
     * @param $ranking
     * @return void
     */
    static function create($logState, $gameMode, $challengeInfo, $ranking, $isMatch = false) {
        switch($gameMode) {
            case 0:
            case 5: // Rounds
                MatchlogRounds::create($logState, $challengeInfo, $ranking);
                break;
            case 1: // TimeAttack
                MatchlogTimeAttack::create($logState, $challengeInfo, $ranking);
                break;
            case 2: // Teams
                MatchlogTeams::create($logState, $challengeInfo, $ranking);
                break;
            case 3: // Laps
                MatchlogLaps::create($logState, $challengeInfo, $isMatch);
                break;
            case 4: // Stunts
                MatchlogStunts::create($logState, $challengeInfo, $ranking);
        }
    }
}
