<?php
require_once(dirname(__FILE__)."/../../challenge/challenge.php");


class MatchlogUtils
{
    /**
     * @param $challengeInfo - The Challenge info
     * @param $prefix - TIMEATTACK, ROUNDS, LAPS, TEAMLAPS, STUNTS
     * @return string - The title
     */
    static function getMatchlogTitle($challengeInfo, $prefix, $suffix = "")
    {
        $cuid = getChallengeID($challengeInfo);
        return $prefix . ' MATCH on [' . stripColors($challengeInfo['Name']) . '] (' . $challengeInfo['Environnement'] . ',' . $cuid . ',' . stripColors($challengeInfo['Author']) . ')' . $suffix;
    }

    static function writeRaceInfo($challengeInfo, $date, $gameInfo, $gameMode)
    {
        $result = "\n* Race info:";
        $result .= "\nDate, ChallengeName, ChallengeNameWithColor, ChallengeID, ChallengeAuthor, Environment, GameMode, NumberOfLaps";
        $result .= "\n" . $date . "," . stripColors($challengeInfo["Name"]) . "," .
            $challengeInfo["Name"] . "," . getChallengeID($challengeInfo) . "," .
            $challengeInfo["Author"] . "," . $challengeInfo['Environnement'] . "," . $gameMode . "," . $gameInfo["LapsNbLaps"];

        return $result . MatchlogUtils::writeSectionDelimiter();
    }

    static function writePlayers($playersList)
    {
        $result = "\n* Players:\n";
        $result .= "Login, NickName, NickNameWithColor";
        for ($i = 0; $i < sizeof($playersList); $i++) {
            if ((isset($playersList[$i]['IsSpectator']) && ($playersList[$i]['IsSpectator'] == 1)) === false) {
                $result .= "\n" . stripColors($playersList[$i]['Login']) . ','
                    . stripColors($playersList[$i]['NickName']) . ','
                    . $playersList[$i]['NickName'];
            }
        }

        return $result . self::writeSectionDelimiter();
    }

    static function writeSpectators($playersList)
    {
        $text = "";
        $separator = "\n* Spectators: ";
        for ($i = 0; $i < sizeof($playersList); $i++) {
            if (isset($playersList[$i]['IsSpectator']) && ($playersList[$i]['IsSpectator'] == 1)) {
                $text .= $separator . stripColors($playersList[$i]['Login']);
                $separator = ', ';
            }
        }

        if ($text) {
            return $text . self::writeSectionDelimiter();
        }

        return "";
    }

    static function writeSectionDelimiter()
    {
        return "\n--------------------";
    }

    static function getCheckpointsFileNameForPlayer($playerLogin)
    {
        global $_GameInfos, $_ChallengeInfo;
        return "fastlog/" . $_GameInfos["LapsNbLaps"] . "laps/" . $_ChallengeInfo['UId'] . "_" . $playerLogin . ".txt";
    }

// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
    function matchlogRecCompare($a, $b)
    {
        if ($a['FinalTime'] <= 0 && $b['FinalTime'] <= 0)
            return strcmp($a['NickName'], $b['NickName']);
        elseif ($b['FinalTime'] <= 0)
            return -1;
        elseif ($a['FinalTime'] <= 0)
            return 1;

        // both best ok, so...
        elseif ($a['FinalTime'] < $b['FinalTime'])
            return -1;
        elseif ($a['FinalTime'] > $b['FinalTime'])
            return 1;
        return -1;
    }
}
