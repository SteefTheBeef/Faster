<?php

require_once(dirname(__FILE__)."/../utils/stringUtils.php");
require_once "utils/MatchlogUtils.php";

class MatchlogRounds {
    static function create($logState, $challengeInfo, $ranking) {
        switch ($logState) {
            case "END_RACE":
                self::endRace($challengeInfo, $ranking);
                break;
            case "BEGIN_RACE":
                self::beginRace();
                break;
            case "BEGIN_ROUND":
                self::beginRound();
                break;
             case "END_ROUND":
                 self::endRound();
                 break;
        }
    }

    private static function beginRound() {

    }

    private static function beginRace() {
        self::removeFile("fastlog/rounds.txt");
        self::removeFile("fastlog/checkpoints.txt");
    }

    static function removeFile($fileName) {
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }

    /**
     * @param $challengeInfo
     * @param $ranking
     * @param $playerList
     * @return void
     */
    private static function endRace($challengeInfo, $ranking) {
        global $_players_round_current, $_players_round_time, $_players_rounds_scores, $_PlayerList, $_players;
        $date = date("Y-m-d H:i:s");

        $matchlogMessage = MatchlogUtils::getMatchlogTitle($challengeInfo, 'ROUNDS');
        $matchlogMessage .= self::writeAllPlayersScore($ranking);
        $matchlogMessage .= self::writePlayerRounds();
        $matchlogMessage .= self::writePlayerCheckpoints();
        $matchlogMessage .= MatchlogUtils::writeRaceInfo($challengeInfo, $date, $_GameInfos, 'ROUNDS');
        matchlog($matchlogMessage."\n\n");
    }

    static function writePlayerRounds() {
         $result = "\n* Rounds grouped by player:";
         $result .= "\nLogin,Rounds";
         $contents = file_get_contents("fastlog/rounds.txt");
         $rows = explode("\n", trim($contents));

         foreach ($rows as $row) {
             $parts = array_map('trim', explode(",", $row));
             $login = $parts[0];
             $result .= "\n".$row;
         }

        return $result.MatchlogUtils::writeSectionDelimiter();
    }

    static function writePlayerCheckpoints() {
         $result = "\n* Checkpoints grouped by player:";
         $result .= "\nLogin,Checkpoints";

         $contents = file_get_contents("fastlog/checkpoints.txt");
         $rows = explode("\n", trim($contents));

         foreach ($rows as $row) {
             $parts = array_map('trim', explode(",", $row));
             $login = $parts[0];
             $result .= "\n".$row;
         }

        return $result.MatchlogUtils::writeSectionDelimiter();
    }

    private static function writeAllPlayersScore($finishedPlayers) {
        $result = "\n* Scores:";
        $result .= "\nLogin,Rank,Points";

        for($i = 0; $i < sizeof($finishedPlayers); $i++){
            $currentPlayer = $finishedPlayers[$i];
            $result .= self::writePlayerScore($currentPlayer, $finishedPlayers[0], $i);
        }

        return $result.MatchlogUtils::writeSectionDelimiter();
    }

    private static function writePlayerScore($player, $firstFinishedPlayer, $index) {
        //console(print_r($player));
        return "\n".stripColors($player['Login']).",".($index+1).','.$player['Score'];
    }

    static function checkpointsMwTimeToString($checkpointsArr) {
        $result = array();
        foreach ($checkpointsArr as $checkpoint) {
            $result[] = MwTimeToString($checkpoint);
        }

        return $result;
    }

    static function endRound() {
        global $_GameInfos, $_debug, $_players_round_current, $_teams;
        $times = self::getPlayerTimes();

        if($_debug>1) debugPrint('matchlogEndRound - times',$times);

        if(count($times) <= 0) {
            return;
        }

        usort($times,'matchlogRecCompare');
        $roundsData = self::getRoundsDataForCurrentMap();
        $checkpointsData = self::getCheckpointsDataForCurrentMap();

        foreach ($times as $time) {
            $login = $time["Login"];
            if (isset($roundsData[$login])) {
                $playerRounds = &$roundsData[$login];
                $playerCheckpoints = &$checkpointsData[$login];
                for ($i = 1; $i <= $_players_round_current; $i++) {
                    if (!isset($playerRounds[$i])) {
                        $playerRounds[$i] = $i == $_players_round_current ? MwTimeToString($time["FinalTime"]) : "";
                        $playerCheckpoints[$i] = $i == $_players_round_current ? implode(self::checkpointsMwTimeToString($time["Checkpoints"]), "|") : "";
                    }
                }

            } else {
                // Adding a new player
                $roundsData[$login] = array($login);
                $checkpointsData[$login] = array($login);
                for ($i = 1; $i <= $_players_round_current; $i++) {
                    $roundsData[$login][$i] = "";
                    $checkpointsData[$login][$i] = "";
                    if ($i == $_players_round_current) {
                        $roundsData[$login][$i] = MwTimeToString($time["FinalTime"]);
                        $checkpointsData[$login][$i] = implode(self::checkpointsMwTimeToString($time["Checkpoints"]), "|");
                    }
                }

            }
        }

        self::writeRoundsDataForCurrentMap($roundsData);
        self::writeCheckpointsDataForCurrentMap($checkpointsData);
    }

    static function getPlayerTimes() {
        global $_players;

        $times = array();
        foreach($_players as $login => &$player){
            if($player['FinalTime']>0){

                $times[] = array('Login'=>$player['Login'],
                    'NickName'=>$player['NickName'],
                    'FinalTime'=>$player['FinalTime'],
                    'Checkpoints' => $player['Checkpoints'],
                    'TeamId'=>$player['TeamId']);
            }
        }

        return $times;
    }

    static function getRoundsDataForCurrentMap() {
        return self::getDataForCurrentMap("fastlog/rounds.txt");
    }

    static function getCheckpointsDataForCurrentMap() {
        return self::getDataForCurrentMap("fastlog/checkpoints.txt");
    }

    static function getDataForCurrentMap($fileName) {
        $players = [];

        if (!file_exists($fileName)) {
            return $players;
        }

        $contents = file_get_contents($fileName);
        $rows = explode("\n", trim($contents));

        foreach ($rows as $row) {
            $parts = array_map('trim', explode(",", $row));
            $login = $parts[0];
            $players[$login] = $parts;
        }

        return $players;
    }

    static function writeRoundsDataForCurrentMap($data) {
        self::writeDataForCurrentMap("fastlog/rounds.txt", $data);
    }

    static function writeCheckpointsDataForCurrentMap($data) {
        // write checkpoints to temp file as we want to add this data to the big matchlog after the map is done.
        self::writeDataForCurrentMap("fastlog/checkpoints.txt", $data);
    }

    static function writeDataForCurrentMap($fileName, $data) {
        $handle = fopen($fileName, "w");
        $roundsResult = "";

        foreach($data as $arr) {
            $playerRow = implode(",", $arr);
             fwrite($handle, $playerRow . "\n"); // skriv rad + radbrytning
        }

        fclose($handle);
    }
}

