<?php

require_once "utils/MatchlogUtils.php";
require_once "utils/MatchlogConsole.php";

class MatchlogLaps {
    static function create($logState, $challengeInfo) {
        switch ($logState) {
            case "END_RACE":
                self::endRace($challengeInfo);
                break;
            case "BEGIN_ROUND":
                self::beginRound();
                break;
        }
    }

    private static function beginRound() {}

    private static function endRace($challengeInfo) {
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

                $checkpointsPerLapGroupedByPlayer[]  = self::getPlayerCheckpointsAsOneLineString($player, $challengeInfo);
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

        $matchlogMessage = MatchlogUtils::getMatchlogTitle($challengeInfo, 'LAPS');
        $matchlogMessage .= "\nRank,Lap,Checkpoints,Time,BestLap,CPdelay,Points,Login,NickName";

        for($i = 0; $i < sizeof($finishedPlayers); $i++){
            $currentPlayer = $finishedPlayers[$i];
            $matchlogMessage .= self::getTextRowForPlayer($currentPlayer, $finishedPlayers[0], $i, $minCPdelay);
        }
        $matchlogMessage .= "\n--------------------";
        $matchlogMessage .= MatchlogUtils::getTextSpectators($playerList);
        $matchlogMessage .= self::getPlayerLapsLogText($lapsGroupedByLogin);
        $matchlogMessage .= "\n--------------------";
        $matchlogMessage .= self::getPlayerCheckpointsAsLogText($checkpointsPerLapGroupedByPlayer);
        $matchlogMessage .= "\n--------------------";
        $matchlogMessage .= self::getBestLapsLogText($lapsList, $gameInfo);
        $matchlogMessage .= "\n--------------------";
        self::chatMessageBestLaps($lapsList, $gameInfo);
        matchlog($matchlogMessage."\n\n");
        console("to matchlog: ".$matchlogMessage);
    }

    private static function getBestLapsLogText($bestLaps, $GameInfos) {
        $result = "\n* BestLaps\n";
        $result .= "Rank,LapTime,LapNumber,Login,NickName\n";

        // the number of laps should be the maximum.
        $count = min(sizeof($bestLaps), $GameInfos['LapsNbLaps']);
        for($i = 0; $i < $count; $i++){
            $place = $i+1;
            $bestLap = $bestLaps[$i];
            $result .= $place.",".$bestLap['LapTime'].",".$bestLap['LapNumber'].",".$bestLap['Login'].",".$bestLap["NickName"]."\n";
        }

        return $result;
    }

    private static function getPlayerLapsLogText($lapsGroupedByLogin) {
        $result = "\n* Laps grouped by player\n";
        $count = count($lapsGroupedByLogin);
        for($i = 0; $i < $count; $i++){
            $sep = $i < $count - 1 ? "\n" : "";
            $result .= $lapsGroupedByLogin[$i].$sep;
        }

        return $result;
    }

    private static function getPlayerLapsAsOneLineString($playerLaps) {
        $result = "";
        for($i = 0; $i < count($playerLaps); $i++){
            $result .= $playerLaps[$i]['LapTime'].',' ;
        }

        return $playerLaps[0]['Login'].",".$playerLaps[0]["NickName"].",".$result;
    }

    private static function getPlayerCheckpointsAsOneLineString($player, $challengeInfo) {
        $result = "";
        for($i = -1; $i < count($player['Checkpoints']) - 1; $i++){
            $sep = $i % $challengeInfo["NbCheckpoints"] === 1 ? "#," : ",";
            $result .= MwTimeToString($player['Checkpoints'][$i]).$sep ;
        }

        return $player['Login'].",".stripColors($player["NickName"]).",".$result;
    }

    private static function getPlayerCheckpointsAsLogText($checkpointsPerLapGroupedByPlayer) {
        $result = "\n* Checkpoints grouped by player\n";
        $count = count($checkpointsPerLapGroupedByPlayer);
        for($i = 0; $i < $count; $i++){
            $sep = $i < $count - 1 ? "\n" : "";
            $result .= $checkpointsPerLapGroupedByPlayer[$i].$sep ;
        }

        return $result;
    }

    private static function chatMessageBestLaps($bestLaps, $GameInfos) {
        addCall(null,'ChatSendServerMessage', '$i* Best laps');
        // the number of laps should be the maximum.
        $count = min(sizeof($bestLaps), $GameInfos['LapsNbLaps']);
        for($i = 0; $i < $count; $i++){
            $place = $i+1;
            $msg = '$i$n$0f0'.$place.'. $ecc'.$bestLaps[$i]['LapTime'].", ".$bestLaps[$i]["NickNameWithColor"];
            addCall(null,'ChatSendServerMessage', $msg);

        }
    }

    private static function createFinishedPlayer($player) {
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

    private static function getTextRowForPlayer($player, $firstFinishedPlayer, $index, $minCPdelay) {
        $text = "\n".($index+1).','.$player['Lap'].','.$player['Check'].','
            .MwTimeToString($player['Time']).','.MwTimeToString($player['BestLap']).','
            .(($player['CPdelay']-$minCPdelay)/1000).',';

        return $text.''.self::getPointsForPlayer($player, $firstFinishedPlayer, $index).','.stripColors($player['Login']).','.stripColors($player['NickName']);
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


