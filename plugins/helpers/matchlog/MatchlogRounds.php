<?php

require_once(dirname(__FILE__)."/../utils/stringUtils.php");
require_once "utils/MatchlogUtils.php";

class MatchlogRounds {
    static function create($logState, $challengeInfo, $ranking) {
        switch ($logState) {
            case "END_RACE":
                self::endRace($challengeInfo, $ranking);
                break;
            case "BEGIN_ROUND":
                self::beginRound();
                break;
            case "END_ROUND":
                MatchlogUtils::endRound();
                break;
        }
    }

    private static function beginRound() {

    }

    /**
     * @param $challengeInfo
     * @param $ranking
     * @param $playerList
     * @return void
     */
    private static function endRace($challengeInfo, $ranking) {
        global $_players_round_current, $_PlayerList, $_players;

        $matchlogMessage = MatchlogUtils::getMatchlogTitle($challengeInfo, 'ROUNDS',  '['.$_players_round_current.'r]');

        for($i = 0; $i < sizeof($ranking); $i++){
            $matchlogMessage .= "\n".$ranking[$i]['Rank'].','.$ranking[$i]['Score'].','.MwTimeToString($ranking[$i]['BestTime']).','.stripColors($ranking[$i]['Login']).','.stripColors($ranking[$i]['NickName']);
        }

        $matchlogMessage .= MatchlogUtils::getTextSpectators($_PlayerList);

        $sep = "\n* Results: "; // pos in each round
        foreach($_players as $login => &$pl){
            $login = toString($login);
            if(count($pl['RoundsPos'])>0){
                $rend = endkey($pl['RoundsPos']);
                if($rend < $_players_round_current) {
                    $rend = $_players_round_current;
                }

                $matchlogMessage .= $sep.$login.'(';
                $sep2 = '';
                for($rnum=1; $rnum<=$rend; $rnum++){
                    if(isset($pl['RoundsPos'][$rnum]))
                        $matchlogMessage .= $sep2.$pl['RoundsPos'][$rnum];
                    else
                        $matchlogMessage .= $sep2.'*';
                    $sep2 = ',';
                }
                $matchlogMessage .= ')';
                $sep = ', ';
            }
        }
        matchlog($matchlogMessage."\n\n");
    }
}

