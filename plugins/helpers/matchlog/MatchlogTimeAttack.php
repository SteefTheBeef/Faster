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
        global $_PlayerList, $_GameInfos;
        $date = date("Y-m-d H:i:s");


        $matchlogMessage = MatchlogUtils::getMatchlogTitle($challengeInfo, 'TIME ATTACK');
        $matchlogMessage .= self::writeAllPlayersScore($ranking);
        $matchlogMessage .= self::writePlayerCheckpoints($ranking);
        $matchlogMessage .= MatchlogUtils::writeSpectators($_PlayerList);
        $matchlogMessage .= MatchlogUtils::writePlayers($_PlayerList);
        $matchlogMessage .= MatchlogUtils::writeRaceInfo($challengeInfo, $date, $_GameInfos, 'TIME ATTACK');
        matchlog($matchlogMessage."\n\n");
    }

    private static function writeAllPlayersScore($finishedPlayers) {
        $result = "\n* Scores:";
        $result .= "\nLogin,Rank,BestTime";

        for($i = 0; $i < sizeof($finishedPlayers); $i++){
            $currentPlayer = $finishedPlayers[$i];
            $result .= self::writePlayerScore($currentPlayer, $finishedPlayers[0], $i);
        }

        return $result.MatchlogUtils::writeSectionDelimiter();
    }

    private static function writePlayerScore($player, $firstFinishedPlayer, $index) {
        return "\n".stripColors($player['Login']).",".($index+1).','.MwTimeToString($player['BestTime']);
    }

    private static function writePlayerCheckpoints($players) {
       $result = "";

        foreach($players as $login => &$player){
            $playerStr = "";
            for($i = 0; $i < count($player['BestCheckpoints']) - 1; $i++){

                $sep = ",";
                $playerStr .= MwTimeToString($player['BestCheckpoints'][$i]).$sep;
            }

            $result .= "\n".$player['Login'].",".$playerStr;
        }

        return "\n* Checkpoints grouped by player:\nLogin,Checkpoints".$result.MatchlogUtils::writeSectionDelimiter();
    }
}

