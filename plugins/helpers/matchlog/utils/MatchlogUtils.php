<?php

class MatchlogUtils {

    // TODO: use atomic write in the future
    static function writeToFile($text, $fileName){
        //$fileName = "fastlog/um/matchlog.txt";
        //FastFile::atomicWrite($fileName, "###\n[".date("Y-m-d, H:i:s")."] $text\n", false);
        $myfile = fopen($fileName, "a");
        fwrite($myfile,"###\n[".date("Y-m-d, H:i:s")."] $text\n");
        fclose($myfile);
    }

    /**
     * @param $challengeInfo - The Challenge info
     * @param $prefix - TIMEATTACK, ROUNDS, LAPS, TEAMLAPS, STUNTS
     * @return string - The title
     */
    static function getMatchlogTitle($challengeInfo, $prefix, $suffix = "") {
        $cuid = getChallengeID($challengeInfo);
        return $prefix . ' MATCH on [' . stripColors($challengeInfo['Name']) . '] (' . $challengeInfo['Environnement'] . ',' . $cuid . ',' . stripColors($challengeInfo['Author']) . ')' . $suffix;
    }

    static function writeRaceInfo($challengeInfo, $date, $gameInfo, $gameMode) {
        $result = "\n* Race info:";
        $result .= "\nDate, ChallengeName, ChallengeNameWithColor, ChallengeID, ChallengeAuthor, Environment, GameMode, NumberOfLaps";
        $result .= "\n" . $date . "," . stripColors($challengeInfo["Name"]) . "," .
            $challengeInfo["Name"] . "," . getChallengeID($challengeInfo) . "," .
            $challengeInfo["Author"] . "," . $challengeInfo['Environnement'] . "," . $gameMode . "," . $gameInfo["LapsNbLaps"];

        return $result . MatchlogUtils::writeSectionDelimiter();
    }

    static function writePlayers($playersList) {
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

    static function writeSpectators($playersList) {
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

    static function writeSectionDelimiter() {
        return "\n--------------------";
    }

    static function getFilenameForCheckpointFile($login) {
        global $_GameInfos, $_ChallengeInfo;
        $fileName = "fastlog/um/" . $_GameInfos["LapsNbLaps"] . "laps/" . $_ChallengeInfo['UId'] . "_" . $login . ".txt";
        return $fileName;
    }

    static function getCheckpointsFromFileForPlayer($login) {
        $filename = self::getFilenameForCheckpointFile($login);

        if (!file_exists($filename)) {
            return;
        }

        $cpFile = fopen($filename, "r");
        if (!$cpFile) {
            return;
        }

        $fileArray = array();

        while (!feof($cpFile)) {
            $fileArray[] = fgets($cpFile);
        }

        $checkpointArray = explode(",", $fileArray[2]);
        array_shift($checkpointArray);
        array_pop($checkpointArray);

        return $checkpointArray;
    }

// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
    function matchlogRecCompare($a, $b) {
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
