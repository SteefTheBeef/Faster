<?php

require_once "utils/MatchlogUtils.php";

class MatchlogTimeAttack {
    static function create($logState, $challengeInfo, $ranking) {
        switch ($logState) {
            case "END_RACE":
                self::endRace($challengeInfo, $ranking);
                break;
        }
    }

    /**
     * @param $challengeInfo
     * @param $ranking
     * @param $playerList
     * @return void
     */
    private static function endRace($challengeInfo, $ranking) {
        global $_PlayerList;

        $matchlogMessage = MatchlogUtils::getMatchlogTitle($challengeInfo, 'TIMEATTACK');
        for($i = 0; $i < sizeof($ranking); $i++){
            $matchlogMessage .= "\n".$ranking[$i]['Rank'].','.MwTimeToString($ranking[$i]['BestTime']).','.stripColors($ranking[$i]['Login']).','.stripColors($ranking[$i]['NickName']);
        }

        $matchlogMessage .= MatchlogUtils::getTextSpectators($_PlayerList);

        matchlog($matchlogMessage."\n\n");
    }
}

