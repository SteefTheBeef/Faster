<?php

require_once "utils/MatchlogUtils.php";
require_once "utils/MatchlogConsole.php";

class MatchlogLaps {
    static function create($logState, $challengeInfo, $isMatch) {
        switch ($logState) {
            case "END_RACE":
                self::endRace($challengeInfo, $isMatch);
                break;
            case "BEGIN_ROUND":
                self::beginRound();
                break;
        }
    }

    private static function beginRound() {}

    private static function endRace($challengeInfo, $isMatch) {
        global $_players,$_PlayerList, $_NumberOfChecks,$_GameInfos,$_players_round_time,$_currentTime;

        $numberOfCheckpoints = $_NumberOfChecks;
        $players = $_players;
        $playerList = $_PlayerList;
        $currentTime = $_currentTime;
        $gameInfo = $_GameInfos;
        $playersRoundTime = $_players_round_time;

        if($numberOfCheckpoints < 1) {
            MatchlogConsole::consoleMatchlogEndRaceNoCheckpoints();
            return;
        }

        // make table
        $lastTime = 0;
        $finishedPlayers = array();
        $numberOfFinishers = 0;
        $minCPdelay = 99999;
        $lapsList= array();
        $lapsGroupedByLogin = array();
        $checkpointsPerLapGroupedByPlayer = array();

        foreach($players as $login => &$player){
            //console(print_r($player, true));
            if($player['CheckpointNumber'] > 0 && $player['LastCpTime'] > 0 && $player['LapNumber'] >= 0){
                if($player['FinalTime'] > 0) {
                    $numberOfFinishers++;
                }

                $finishedPlayers[] = self::createFinishedPlayer($player);
                $playerLaps = array();
                $lapNumber = 1;
                foreach($player['Laps'] as $key => $lapTime) {
                    $lap = self::createLapItem($player, $lapTime, $lapNumber);
                    $playerLaps[] = $lap;
                    $lapsList[] = $lap;
                    $lapNumber++;
                }

                $checkpointsPerLapGroupedByPlayer[]  = self::getPlayerCheckpointsAsOneLineString($player, $challengeInfo, $playerLaps);
                $lapsGroupedByLogin[] = self::getPlayerLapsAsOneLineString($playerLaps);
                if($player['LastCpTime'] > $lastTime) {
                    $lastTime = $player['LastCpTime'];
                }

            }else{
                MatchlogConsole::consoleMatchlogEndRace($player);
            }

            if($player['CPdelay'] > 0 && $player['CPdelay'] < $minCPdelay) {
                $minCPdelay = $player['CPdelay'];
            }

        }

        $timeHasFinished = self::getTimeHasFinished($gameInfo['LapsTimeLimit'], $currentTime, $playersRoundTime, $lastTime);

        if(!(count($finishedPlayers) > 0 && ($numberOfFinishers > 0 || $timeHasFinished))) {
            MatchlogConsole::consoleMatchlogEndRaceNoFinishedPlayers();
            return;
        }

        // sort all laps, the best ones first.
        usort($lapsList, 'sortLaps');

        // sort laps finishedPlayers, then make log and message
        usort($finishedPlayers,'matchlogRecCompareLaps');
        $date = date("Y-m-d H:i:s");

        $matchlogMessage = MatchlogUtils::getMatchlogTitle($challengeInfo, $isMatch ? 'MULTIMAP LAPS' : 'LAPS', '', $isMatch);
        $matchlogMessage .= self::writeAllPlayersScore($finishedPlayers, $minCPdelay);
        $matchlogMessage .= MatchlogUtils::writeSpectators($playerList);
        $matchlogMessage .= self::writePlayerLaps($lapsGroupedByLogin);
        $matchlogMessage .= self::writePlayerCheckpoints($checkpointsPerLapGroupedByPlayer);
        $matchlogMessage .= self::writeBestLaps($lapsList, $gameInfo);
        $matchlogMessage .= MatchlogUtils::writePlayers($playerList);
        $matchlogMessage .= self::writeRaceInfo($challengeInfo, $date, $_GameInfos, $isMatch);
        if (!$isMatch) {
            // to prevent this being sent two times in a row.
            // TODO: prevent this in a better way.
            self::chatMessageBestLaps($lapsList, $gameInfo);
        }


        matchlog($matchlogMessage."\n\n", $isMatch);
        console("to matchlog: ".$matchlogMessage);

        //Write checkpoints to local files
        self::WriteBest6LapsToFile($players, $checkpointsPerLapGroupedByPlayer, $challengeInfo);
    }

    private static function best6LapsOutput($player, $cps, $challengeInfo) {
        $output = trim($player['LastCpTime'])."\n".trim($cps);
        $challengeDetails = $challengeInfo["Name"].", ".$challengeInfo['Environnement'];
        return "[".date("Y-m-d, H:i:s")."]". $challengeDetails."\n$output\n";
    }
    private static function WriteBest6LapsToFile($players, $checkpointsPerLapGroupedByPlayer, $challengeInfo){
        $cuid = getChallengeID($challengeInfo);
        global $matchfile,$do_match_log, $_match_conf, $_DedConfig;


        foreach ($players as $login => &$player) {
            $result = "";

            if ($player['FinalTime'] > 0) {
                for($i = -1; $i < count($player['Checkpoints']) - 1; $i++) {
                    $result .= $player['Checkpoints'][$i].",";
                }

                $fileName = "fastlog/6laps/".$cuid."_".$player['Login'].".txt";

                if (!file_exists($fileName)) {
                    $myfile = fopen($fileName, "x+");
                    fwrite($myfile,MatchlogLaps::best6LapsOutput($player, $result, $challengeInfo));
                    fclose($myfile);
                    return;
                }

                $myfile = fopen($fileName, "r");
                $fileLines = array();

                while(!feof($myfile)) {
                    array_push($fileLines,fgets($myfile));
                }

                if (!$fileLines || count($fileLines) <= 2 || count($fileLines) > 4 || $player['LastCpTime'] < $fileLines[1]) {
                    $myfile = fopen($fileName, "w+");
                    fwrite($myfile,MatchlogLaps::best6LapsOutput($player, $result, $challengeInfo));
                    fclose($myfile);
                }
            }
        }
    }



    private static function writeRaceInfo($challengeInfo, $date, $gameInfo, $isMatch) {
        global $_match_conf,$_match_map;

        $matchMapNumber = $isMatch ? "{$_match_map}/{$_match_conf['NumberOfMaps']}" : "";

        $result = "\n* Race info:";
        $result .= "\nDate, ChallengeName, ChallengeNameWithColor, ChallengeID, ChallengeAuthor, Environment, GameMode, NumberOfLaps, IsMatch, MatchMapNumber";
        $result .= "\n".$date.",".stripColors($challengeInfo["Name"]).",".
            $challengeInfo["Name"].",".getChallengeID($challengeInfo).",".
            $challengeInfo["Author"].",".$challengeInfo['Environnement'].",LAPS,".$gameInfo["LapsNbLaps"].",".$isMatch.",".$matchMapNumber;

        return $result.MatchlogUtils::writeSectionDelimiter();
    }

    private static function writeBestLaps($bestLaps, $GameInfos) {
        $result = "\n* Best laps:\n";
        $result .= "Login,Rank,LapTime,LapNumber";

        // the number of laps should be the maximum.
        $count = min(sizeof($bestLaps), $GameInfos['LapsNbLaps']);
        for($i = 0; $i < $count; $i++){
            $place = $i+1;
            $bestLap = $bestLaps[$i];
            $result .= "\n".$bestLap['Login'].",".$place.",".$bestLap['LapTime'].",".$bestLap['LapNumber'];
        }

        return $result.MatchlogUtils::writeSectionDelimiter();
    }

    private static function writePlayerLaps($lapsGroupedByLogin) {
        $result = "\n* Laps grouped by player:\n";
        $result .= "Login,Laps\n";
        $count = count($lapsGroupedByLogin);
        for($i = 0; $i < $count; $i++){
            $sep = $i < $count - 1 ? "\n" : "";
            $result .= $lapsGroupedByLogin[$i].$sep;
        }

        return $result.MatchlogUtils::writeSectionDelimiter();
    }

    private static function getPlayerLapsAsOneLineString($playerLaps) {
        $result = "";
        for($i = 0; $i < count($playerLaps); $i++){
            $result .= $playerLaps[$i]['LapTime'].',' ;
        }

        return $playerLaps[0]['Login'].",".$result;
    }

    private static function getPlayerCheckpointsAsOneLineString($player, $challengeInfo, $playerLaps) {
        $result = "";
        $index = 0;
        foreach($playerLaps as $login => &$lap){
            $lap["HasBeenPassed"] = false;
            if ($index === 0) {
                $lap["AbsoluteTimeMs"] = $lap["LapTimeMs"];
            } else {
                $lap["AbsoluteTimeMs"] = $playerLaps[$index - 1]["AbsoluteTimeMs"] +  $lap["LapTimeMs"];
            }

            $index++;
        }

        $index = 0;
        for($i = -1; $i < count($player['Checkpoints']) - 1; $i++){
            $sep = ",";
            if($playerLaps[$index]["AbsoluteTimeMs"] === $player['Checkpoints'][$i]) {
                $sep = "#,";
                $index++;
            }
            //$sep = $i % $challengeInfo["NbCheckpoints"] === 0 ? "#," : ",";
            $result .= MwTimeToString($player['Checkpoints'][$i]).$sep ;
        }

        return $player['Login'].",".$result;
    }

    private static function writePlayerCheckpoints($checkpointsPerLapGroupedByPlayer) {
        $result = "\n* Checkpoints grouped by player:\n";
        $result .= "Login,Checkpoints\n";
        $count = count($checkpointsPerLapGroupedByPlayer);
        for($i = 0; $i < $count; $i++){
            $sep = $i < $count - 1 ? "\n" : "";
            $result .= $checkpointsPerLapGroupedByPlayer[$i].$sep ;
        }

        return $result.MatchlogUtils::writeSectionDelimiter();
    }

    private static function chatMessageBestLaps($bestLaps, $GameInfos) {
        addCall(null,'ChatSendServerMessage', '$i* Best laps');
        // the number of laps should be the maximum.
        $msg = "";
        $count = min(sizeof($bestLaps), $GameInfos['LapsNbLaps']);
        for($i = 0; $i < $count; $i++){
            $place = $i+1;
            $delimeter = $place % 6 === 0 ? "\n" : ",";
            $msg .= '$i$n$0f0'.$place.'. $ecc'.$bestLaps[$i]['LapTime'].", ".$bestLaps[$i]["NickNameWithColor"].$delimeter;
        }

        addCall(null,'ChatSendServerMessage', $msg);
    }

    private static function createFinishedPlayer($player) {
        //console(print_r($player));
        return array(
            'Login'=>$player['Login'],
            'NickName'=>$player['NickName'],
            'Check'=>$player['CheckpointNumber']+1,
            'Lap'=>$player['LapNumber'],
            'Time'=>$player['LastCpTime'],
            'BestLap'=>$player['BestLapTime'],
            'CPdelay'=>$player['CPdelay']
        );
    }

    private static function createLapItem($player, $lapTime, $lapNumber) {
        return array(
            'Login' => $player['Login'],/*//*/
            'NickName' => stripColors($player['NickName']),
            'NickNameWithColor' => $player['NickName'],
            'LapTimeMs' => $lapTime,
            'LapTime' => MwTimeToString($lapTime),
            'LapNumber' => $lapNumber,

        );
    }

    private static function getTimeHasFinished($lapsTimeLimit, $currentTime, $playersRoundTime, $lastTime) {
        if($lapsTimeLimit < 0) {
            return false;
        }

        if(($currentTime - $playersRoundTime) > $lapsTimeLimit){
            console("matchlogEndRace::Laps race finished by timelimit (race time)");
            return true;
        }

        if($lastTime + 10000 > $lapsTimeLimit){
            console("matchlogEndRace::Laps race finished by timelimit (player time)");
            return true;
        }

    }

    private static function writeAllPlayersScore($finishedPlayers, $minCPdelay) {
        $result = "\n* Scores:";
        $result .= "\nLogin,Rank,Lap,Checkpoints,Time,BestLap,CPdelay,Points";

        for($i = 0; $i < sizeof($finishedPlayers); $i++){
            $currentPlayer = $finishedPlayers[$i];
            $result .= self::writePlayerScore($currentPlayer, $finishedPlayers[0], $i, $minCPdelay);
        }

        return $result.MatchlogUtils::writeSectionDelimiter();
    }

    private static function writePlayerScore($player, $firstFinishedPlayer, $index, $minCPdelay) {
        $text = ($index+1).','.$player['Lap'].','.$player['Check'].','
            .MwTimeToString($player['Time']).','.MwTimeToString($player['BestLap']).','
            .(($player['CPdelay']-$minCPdelay)/1000).',';

        return "\n".stripColors($player['Login']).",".$text.''.self::getPointsForPlayer($player, $firstFinishedPlayer, $index);
    }

    private static function getPointsForPlayer($player, $firstFinishedPlayer, $index) {
        global $_lapspoints_notfinishmultiplier, $_lapspoints_rule, $_lapspoints_points, $_lapspoints_finishbonus;

        $lapsPointsRule = $_lapspoints_rule;
        $lapsPoints = $_lapspoints_points;
        $lapsPointsFinishBonus = $_lapspoints_finishbonus;
        $lapsPointsNotFinishMultiplier = $_lapspoints_notfinishmultiplier;
        $playerLapsPoints = 0;

        // main points
        if(isset($lapsPoints[$lapsPointsRule][$index])) {
            $playerLapsPoints += $lapsPoints[$lapsPointsRule][$index];
        } else {
            $playerLapsPoints += end($lapsPoints[$lapsPointsRule]);
        }


        if($player['Check'] >= $firstFinishedPlayer['Check']){
            // add finish bonus
            if(isset($lapsPointsFinishBonus[$lapsPointsRule]) && isset($lapsPointsFinishBonus[$lapsPointsRule][100])) {
                $playerLapsPoints += $lapsPointsFinishBonus[$lapsPointsRule][100];
            }

        }else{
            // not finished
            if(isset($lapsPointsNotFinishMultiplier[$lapsPointsRule])){
                // not finished multiplier
                $playerLapsPoints = (int) ceil($playerLapsPoints * $_lapspoints_notfinishmultiplier[$lapsPointsRule]);

            }elseif(isset($lapsPointsFinishBonus[$lapsPointsRule])){
                // or else partial race % bonuses
                $partial = (int) floor($player['Check'] * 100 / $firstFinishedPlayer['Check']);
                foreach($lapsPointsFinishBonus[$lapsPointsRule] as $val => $bonus){
                    if($partial >= $val){
                        $playerLapsPoints += $bonus;
                        break;
                    }
                }
            }
        }

        return $playerLapsPoints;
    }
}

function sortLaps($a, $b) {
    if($a['LapTimeMs']<$b['LapTimeMs'])
        return -1;
    elseif($a['LapTimeMs']>$b['LapTimeMs'])
        return 1;
}

// -----------------------------------
// compare function for usort, return -1 if $a should be before $b
function matchlogRecCompareLaps($a, $b)
{
    if($a['Check']>$b['Check'])
        return -1;
    elseif($a['Check']<$b['Check'])
        return 1;
    // same number of check, test times
    elseif($a['Time']<$b['Time'])
        return -1;
    elseif($a['Time']>$b['Time'])
        return 1;
    // same times, test bestlap times
    elseif($a['BestLap']<=0 && $b['BestLap']<=0)
        return -1;
    elseif($b['BestLap']<=0)
        return -1;
    elseif($a['BestLap']<=0)
        return 1;
    elseif($a['BestLap']<$b['BestLap'])
        return -1;
    elseif($a['BestLap']>$b['BestLap'])
        return 1;
    return -1;
}


