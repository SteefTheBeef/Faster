<?php

require_once "utils/MatchlogUtils.php";

require_once(dirname(__FILE__)."/../utils/stringUtils.php");

class MatchlogTeams {
    static function create($logState, $challengeInfo, $ranking) {
        switch ($logState) {
            case "END_RACE":
                MatchlogTeams::endRace($challengeInfo, $ranking);
                break;
            case "BEGIN_ROUND":
                MatchlogTeams::beginRound();
                break;
            case "END_ROUND":
                MatchlogUtils::endRound();
                break;
        }
    }

    /**
     * @param $challengeInfo
     * @param $ranking
     * @param $playerList
     * @return void
     */
    static function endRace($challengeInfo, $ranking) {
        global $_PlayerList, $_players, $_players_round_current;

        // add the last team score line before starting the final score match log
        matchlog(self::getTextTeamScores($ranking, 'Score'));

        //[11/21,22:02:04] TEAM MATCH on [ULR6 -  Scrape it] (Bay,xCuDJWONR8ojVmRfVPUkD5dX9u6,djcomixxx) [14r]
        $matchlogMessage2 = MatchlogUtils::getMatchlogTitle($challengeInfo, 'TEAM', ' ['.$_players_round_current.'r]');

        // Final Score: $00FBlue Team 6 <> 8 $F00Red Team
        $matchlogMessage2 .= self::getTextTeamScores($ranking, '\nFinal Score: ');

        // * Blue players: jonny666777[[TnT]jonny], bo.omz0r[tnt.passi], rudi0800[TnT.Norman]
        $matchlogMessage2 .= self::getTextTeamLineUp($_PlayerList, 'Blue');

        // * Red players: zickman13[rev.nekoツ], zepset[rev. Ζєрѕєт], chris_ri[rev.Luffy]
        $matchlogMessage2 .= self::getTextTeamLineUp($_PlayerList, 'Red');

        // These are not used very often I think. could be removed?
        $matchlogMessage2 .= MatchlogUtils::getTextSpectators($_PlayerList);
        $matchlogMessage2 .= self::getTextAllPlayers($_PlayerList);

        // * Results: jonny666777(3,3,5,2,6,4,3,5,2,3,5,2,2,4), bo.omz0r(1,1,1,5,1,1,5,2,4,5,1,3,5,1), zickman13(2,4,6,3,2,2,6,4,1,4,3,1,1,3),
        // zepset(4,2,3,1,3,3,1,1,3,1,2,6,4,2), chris_ri(5,6,4,4,4,5,4,6,6,2,4,5,3,5), rudi0800(6,5,2,6,5,6,2,3,5,6,6,4,6,6)
        $matchlogMessage2 .= self::getTextPlayerRoundPositionResults($_players);

        matchlog($matchlogMessage2."\n\n");
    }

    private static function beginRound() {
        global $_Status, $_Ranking, $_matchlog_Ranking, $_teamcolor;

        if ($_Status['Code'] >= 3) {
            // the score has changed
            if($_Ranking[0]['Score'] != $_matchlog_Ranking[0]['Score'] || $_Ranking[1]['Score'] != $_matchlog_Ranking[1]['Score']){
                $tnick0 = stripColors(''.$_Ranking[0]['NickName']);
                $tnick1 = stripColors(''.$_Ranking[1]['NickName']);

                $msg = '$z $ddd* Score: '.$_teamcolor[0].getTeamName($_Ranking[0]).' '.$_Ranking[0]['Score'];
                $msg .= '$ddd <> '.$_teamcolor[1].$_Ranking[1]['Score'].' '.getTeamName($_Ranking[1]).'$z';

                addCall(null,'ChatSendServerMessage', $msg);
                console('Score - '.stripColors($msg));

                matchlog(getTextTeamScores($_Ranking, 'Score'));
            }
        }

        $_matchlog_Ranking = $_Ranking;

    }

    private static function endRound() {

    }


    private static function getTeamName($rankingItem){
        return ''.stripColors(''.$rankingItem['NickName']);
    }

    /**
     * @param $ranking
     * @param $prefix
     * @return string - example: Score: Blue Team 6 <> 7 Red Team
     */
    private static function getTextTeamScores($ranking, $prefix) {
        $teamName0 = self::getTeamName($ranking[0]);
        $teamName1 = self::getTeamName($ranking[1]);;
        return $prefix .': '.$teamName0.' '.$ranking[0]['Score'].' <> '.$ranking[1]['Score'].' '.$teamName1."\n";
    }



    /**
     * @param $player
     * @return string - example: ruso001[DND.Sonic]
     */
    private static function getPlayerLoginAndName($player) {
        return stripColors($player['Login']).'['.stripColors($player['NickName']).']';
    }

    /**
     * @param $playerList
     * @param $teamColor
     * @return string - Example: jasper1[[DND.Jasper], beez02[DND.Beez], ruso001[DND.Sonic]
     */
    private static function getTextTeamLineUp($playerList, $teamColor) {
        $result = "";
        $sep = "\n* {$teamColor} players: ";
        for($i = 0; $i < sizeof($playerList); $i++){
            if(isset($playerList[$i]['TeamId']) && ($playerList[$i]['TeamId']==1)){
                $result .= $sep.self::getPlayerLoginAndName($playerList[$i]);
                $sep = ', ';
            }
        }

        return $result;
    }

    /**
     * Backup function, if server doesn't give player team info
     * @param $playerList
     * @return string - Example: jasper1[[DND.Jasper], beez02[DND.Beez], ruso001[DND.Sonic]
     */
    private static function getTextAllPlayers($playerList) {
        $result = "";
        $sep = "\n* All players: ";  // server don't give player team info
        for($i = 0; $i < sizeof($playerList); $i++){
            if(!isset($playerList[$i]['TeamId'])){
                $result .= $sep.self::getPlayerLoginAndName($playerList[$i]);
                $sep = ', ';
            }
        }

        return $result;
    }

    private static function getTextPlayerRoundPositionResults($players) {
        global $_players_round_current;

        $result = "";
        $sep = "\n* Results: "; // pos in each round

        foreach($players as $login => &$player){
            if(count($player['RoundsPos']) <= 0){
                continue;
            }

            $rend = endkey($player['RoundsPos']);
            if($rend < $_players_round_current) {
                $rend = $_players_round_current;
            }

            $result .= $sep.toString($login).'(';
            $sep2 = '';

            for($rnum=1; $rnum<=$rend; $rnum++){
                if(isset($player['RoundsPos'][$rnum])) {
                    $result .= $sep2.$player['RoundsPos'][$rnum];
                } else {
                    $result .= $sep2.'*';
                }
                $sep2 = ',';
            }
            $result .= ')';
            $sep = ', ';
        }

        return $result;
    }
}


