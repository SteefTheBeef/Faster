<?php
require_once(dirname(__FILE__)."/../../challenge/challenge.php");


class MatchlogUtils {
    /**
     * @param $challengeInfo - The Challenge info
     * @param $prefix - TIMEATTACK, ROUNDS, LAPS, TEAMLAPS, STUNTS
     * @return string - The title
     */
    static function getMatchlogTitle($challengeInfo, $prefix, $suffix = "") {
        $cuid = getChallengeID($challengeInfo);
        return $prefix.' MATCH on ['.stripColors($challengeInfo['Name']).'] ('.$challengeInfo['Environnement'].',' .$cuid.','.stripColors($challengeInfo['Author']).')'.$suffix;
    }

    static function getChallengeNameText($challengeInfo) {
        $stripped = stripColors($challengeInfo['Name']);
        $suffix = $stripped === $challengeInfo['Name'] ? "" : ",".$challengeInfo['Name'];
        return "\nChallenge name: ".stripColors($challengeInfo['Name']).$suffix;
    }

    static function getChallengeIDText($challengeInfo) {
        return "\nChallenge ID: ".getChallengeID($challengeInfo);
    }
    static function getChallengeEnviText($challengeInfo) {
        return "\nChallenge envi: ".$challengeInfo['Environnement'];
    }

    static function getChallengeAuthorText($challengeInfo) {
        return "\nChallenge author: ".$challengeInfo['Author'];
    }

    static function writeRaceInfo($challengeInfo, $date, $gameInfo, $gameMode) {
        //console($challengeInfo);
        //console($date);
        //console($gameInfo);
        //console($gameMode);

        $result = "\n* Race info:";
        $result .= "\nDate, ChallengeName, ChallengeNameWithColor, ChallengeID, ChallengeAuthor, Environment, GameMode, NumberOfLaps";
        $result .= "\n".$date.",".stripColors($challengeInfo["Name"]).",".
            $challengeInfo["Name"].",".getChallengeID($challengeInfo).",".
            $challengeInfo["Author"].",".$challengeInfo['Environnement'].",".$gameMode.",".$gameInfo["LapsNbLaps"];

        return $result.MatchlogUtils::writeSectionDelimiter();
    }

    static function writePlayers($playersList) {
        $result = "\n* Players:\n";
        $result .= "Login, NickName, NickNameWithColor";
        for($i = 0; $i < sizeof($playersList); $i++){
            if((isset($playersList[$i]['IsSpectator']) && ($playersList[$i]['IsSpectator'] == 1)) === false) {
                $result .= "\n".stripColors($playersList[$i]['Login']).','
                    .stripColors($playersList[$i]['NickName']).','
                    .$playersList[$i]['NickName'];
            }
        }

        return $result.self::writeSectionDelimiter();
    }

    static function writeSpectators($playersList) {
        $text = "";
        $separator = "\n* Spectators: ";
        for($i = 0; $i < sizeof($playersList); $i++){
            if(isset($playersList[$i]['IsSpectator']) && ($playersList[$i]['IsSpectator'] == 1)){
                $text .= $separator.stripColors($playersList[$i]['Login']);
                $separator = ', ';
            }
        }

        if ($text) {
            return $text.self::writeSectionDelimiter();
        }

        return "";
    }

    static function endRound() {
        global $_GameInfos, $_debug, $_players_round_current, $_teams;

        $times = self::getPlayerTimes();

        if($_debug>1) debugPrint('matchlogEndRound - times',$times);

        if(count($times) <= 0) {
            return;
        }

        usort($times,'matchlogRecCompare');

        $msg2 = 'Round-'.$_players_round_current;
        $sep2 = ':';

        for($i=0; $i<count($times); $i++) {
            if($i===0) {
                if($_GameInfos['GameMode']==2) {
                    $msg2 .= '(B='.$_teams[0]['Score'].',R='.$_teams[1]['Score'].')';
                } else {
                    $msg2 .= '('.MwTimeToString($times[$i]['FinalTime']).')';
                }
            }

            $msg2 .= $sep2.stripColors($times[$i]['NickName']);
            $sep2 = ',';
        }

        $sep2 = "\nTimes: ";
        for($i=0;$i<count($times)&&$i<10;$i++){
            $msg2 .= $sep2.stripColors($times[$i]['Login']).'('.MwTimeToString($times[$i]['FinalTime']).')';
            $sep2 = ', ';
        }

        matchlog($msg2);
        // don't show in chat for TMU dedicated (visible in manialinks)
        //addCall(null,'ChatSendServerMessage', $msg);
    }


    private static function getPlayerTimes() {
        global $_players;

        $times = array();
        foreach($_players as $login => &$player){
            if($player['FinalTime']>0){

                $times[] = array('Login'=>$player['Login'],
                    'NickName'=>$player['NickName'],
                    'FinalTime'=>$player['FinalTime'],
                    'TeamId'=>$player['TeamId']);
            }
        }

        return $times;
    }

    static function writeSectionDelimiter() {
        return "\n--------------------";
    }

}

// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function matchlogRecCompare($a, $b){
    if($a['FinalTime']<=0 && $b['FinalTime']<=0)
        return strcmp($a['NickName'],$b['NickName']);
    elseif($b['FinalTime']<=0)
        return -1;
    elseif($a['FinalTime']<=0)
        return 1;

    // both best ok, so...
    elseif($a['FinalTime']<$b['FinalTime'])
        return -1;
    elseif($a['FinalTime']>$b['FinalTime'])
        return 1;
    return -1;
}

