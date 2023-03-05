<?php

class ReplayManager {
    public $pathToReplays = "";
    public function __construct($pathToReplays)
    {
        $this->pathToReplays = $pathToReplays;
        $this->setDefinitions();
    }

    private function setDefinitions() {
        // Check FAST version and game version before comparing against LAPS (FAST 3.2.2y at least doesn't have LAPS, etc. defined)
        // See includes/fast_config.php for relevant variables (FAST 4.0.0n usefully documents both TM2 and TMUF values)
        if(!defined('LAPS')){
            global $_FASTver, $_Game;
            if($_FASTver >= '4'){
                console("savebestghostsreplay: FAST >= 4 should have LAPS defined!");
            }
            // $_Game gets set in includes/fast_general.php.  Empirically have seen TMU or TM2.
            if($_Game == 'TMU'){
                define('LAPS',3);	// TMU/TMUF/TMNF
            }else{
                console("savebestghostsreplay: unknown game $_Game!");
                define('LAPS',3);	// Probably an older game - better to guess than just bomb out?
            }
        }
    }


    private function getReplayTimeMs($fileName) {
        $arr = explode("(", $fileName);

        if ($arr[1]) {
            $items = explode(")", $arr[1]);
            console("result0 " . $items[0]);
            $msString = explode("''", $items[0]);
            // calc time in Ms
            $ms = intval($msString[1]);
            console("ms " . $ms);

            $secondsString = explode("'", $msString[0]);
            $seconds = intval($secondsString[1]);
            console("seconds " . $seconds);

            $minuteString = $secondsString[0];
            $minutes = intval($minuteString);
            console("minutes " . $minutes);
            return $minutes * 60 * 1000 + $seconds * 1000 + $ms;
        }

        return 0;
    }

    private function getReplayLogin($fileName) {
        $arr = explode("_", $fileName);
        $arr2 = explode("(", $arr[1]);
        return $arr2[0];
    }

    private function getReplayChallenge($fileName) {
        $arr = explode("_", $fileName);
        $arr2 = explode("(", $arr[0]);
        return $arr2[0];
    }

    private function scanReplayDir() {
        $files = scandir($this->pathToReplays);
        console(print_r($files));

        $result = array();

        foreach ($files as $index => &$fileName) {
            $time = $this->getReplayTimeMs($fileName);
            $login = $this->getReplayLogin($fileName);
            $challenge = $this->getReplayChallenge($fileName);
            $result[] = array("ChallengeName" => $challenge, "Login" => $login, "Time" => $time);
        }

        console(print_r($result));

        return $result;
    }

    private function shouldSaveNewBetterReplay($currentReplays, $time) {
        if(!$currentReplays || count($currentReplays) <= 0) {
            console("NO REPLAYS");
            return true;
        }

        $replaysFromPlayer = array();
        foreach($currentReplays as $key => &$replay){
            if($time["Login"] === $replay["Login"]) {
                $replaysFromPlayer[] = $replay;
            }
        }

        if(count($replaysFromPlayer) <= 0) {
            console("NO REPLAYS FROM PLAYER");
            return true;
        }

        $bestReplay = $replaysFromPlayer[0];
        foreach($replaysFromPlayer as $key => &$replay){
            if ($replay["Time"] < $bestReplay["Time"]) {
                $bestReplay = $replay;
            }
        }

        console("bestReplay.Time ".$bestReplay["Time"]);
        console("time.Time ".$time["Time"]);

        return $time["Time"] < $bestReplay["Time"];
    }

    private function createTimesArray($GameInfos) {
        global $_players;
        $times = array();
        if($GameInfos['GameMode'] == LAPS){
            $BestTime = 'FinalTime';
            $BestCheckpoints = 'BestLapCheckpoints';
        }else{
            $BestTime = 'BestTime';
            $BestCheckpoints = 'BestCheckpoints';
        }

        // get best times of players
        foreach($_players as $key => &$player){
            //console(print_r($player));
            if(isset($player['Login']) && $player['Login'] == $key &&
                isset($player['Active']) && isset($player[$BestTime]) &&
                !is_LAN_login($player['Login']) &&
                ($player['Active'] || $player[$BestTime] > 0)){

                $login = $player['Login'];

                // add time only if no inconsistency
                if($GameInfos['GameMode'] == 3){
                    if($player["NbrLapsFinished"] === $GameInfos["LapsNbLaps"]) {
                        $times[] = array('Login'=>$login ,'Time' => $player[$BestTime]+0);
                    }
                } else {
                    if(count($player[$BestCheckpoints]) > 0 && $player[$BestTime] == end($player[$BestCheckpoints])){
                        $times[] = array('Login'=>$login ,'Time'=>$player[$BestTime]+0,'Checks'=>implode(',',$player[$BestCheckpoints]));
                    }
                }
            }
        }

        return $times;
    }

    function saveBetterReplay($event,$Ranking,$ChallengeInfo,$GameInfos) {
        global $_debug;

        // $_debug is set in fast_config.php (default level is 1)
        if($_debug > 2) {
            console("ReplayManager.saveReplay.Event[$event]()");
        }

        $times = $this->createTimesArray($GameInfos);

        // If there are no times, exit
        if(count($times) == 0){
            console("savebestghostsreplay$event - no times");
            return;
        }

        usort($times,"savebestghostsreplayRecCompare");

        $challengeName = $this->getChallengeName($ChallengeInfo);
        $currentReplays = $this->scanReplayDir();
        console(print_r($currentReplays, true));
        foreach($times as $key => &$time) {
            console("time".print_r($time, true));
            if ($this->shouldSaveNewBetterReplay($currentReplays, $time)) {
                $fileName = $this->createFileName($ChallengeInfo, $GameInfos, $time);
                $this->saveReplay($fileName, $time);
            }

        }

    }

    private function saveReplay($fileName, $time) {
        global $_client;
        if(!$_client->query('SaveBestGhostsReplay', $time['Login'], "UM/".$fileName)){
            console("savebestghostsreplayEndRace:: failed to store GhostsReplay!");
        }
    }

    private function getChallengeName($ChallengeInfo) {
        $rfile = pathinfo($ChallengeInfo['FileName'],PATHINFO_FILENAME);
        if ( substr($rfile,-4) == ".Map" ) {
            $rfile=substr($rfile,0,-4);
        } elseif ( substr($rfile,-10) == ".Challenge" ) {
            $rfile=substr($rfile,0,-10);
        }

        return $rfile;
    }

    private function createFileName($ChallengeInfo, $GameInfos, $time) {
        // If no times have been set, replay will fail to save.  Already special-cased that out though.
        // Note that console messages (e.g. from errors) are logged to fastlog/fastlog.<game>.<server login>.txt
        // Seems that SaveBestGhostsReplay doesn't work from relays, even if the relay's login is added to admin logins of the target server.
        //  ChallengeInfo['FileName'] isn't available to relays?  Must be obtainable somehow...
        //  Sometimes the relay doesn't get the best times?  Just from latency?
        $challengeName = $this->getChallengeName($ChallengeInfo);
        return sprintf('%s_%s(%s%03d)%s',$challengeName,$time['Login'],date("i\'s\'\'",$time['Time']/1000),$time['Time']%1000, '['.$GameInfos["LapsNbLaps"].' laps]').'.Replay.Gbx';
    }
}

function savebestghostsreplayRecCompare($a, $b)
{
    global $_players;
    // no best valid, use rank
    if($a['Best'] <= 0 && $b['Best'] <= 0)
        return ($_players[$a['Login']]['Rank'] < $_players[$b['Login']]['Rank']) ?  -1 : 1;
    // one best valid
    elseif($b['Best'] <= 0)
        return -1;
    // other best valid
    elseif($a['Best'] <= 0)
        return 1;
    // best a better than best b
    elseif($a['Best'] < $b['Best'])
        return -1;
    // best b better than best a
    elseif($a['Best'] > $b['Best'])
        return 1;
    // same best, use rank
    else
        return ($_players[$a['Login']]['BestDate'] < $_players[$b['Login']]['BestDate']) ?  -1 : 1;
}
